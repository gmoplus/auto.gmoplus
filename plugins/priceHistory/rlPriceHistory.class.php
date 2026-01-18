<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLPRICEHISTORY.CLASS.PHP
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2024 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

use Flynax\Utils\Util;

class rlPriceHistory
{
    /**
     * @var array - Price History plugin configuration
     */
    private $config;

    /**
     * rlPriceHistory constructor.
     */
    public function __construct()
    {
        $root_path = RL_PLUGINS . 'priceHistory/';
        $root_url = RL_PLUGINS_URL . 'priceHistory/';

        $config = array(
            'path' => array(
                'root' => $root_path,
                'static' => $root_path . 'static/',
                'view' => $root_path . 'view/',
            ),
            'url' => array(
                'root' => $root_url,
                'static' => $root_url . 'static/',
            ),
        );

        $this->config = $config;
    }

    /**
     * Get price history of the Listing
     *
     * @return array - price history array
     **/
    public function getPriceHistory()
    {
        global $lang, $config, $listing, $listing_id, $plugins, $rlDb, $listing_type, $rlCurrencyConverter;

        $GLOBALS['reefless']->loadClass('Valid');

        $sql = "SELECT * FROM `" . RL_DBPREFIX . "price_history` WHERE `Listing_id` = {$listing_id} ORDER BY `Date`";
        $price_history_array = $rlDb->getAll($sql);

        if (!$price_history_array) {
            return [];
        }

        $data = array();
        $listing_fields = array();

        foreach ($listing as $group => $gr_value) {
            foreach ($gr_value['Fields'] as $field_key => $field) {
                $listing_fields[$field_key] = $field;
            }
        }

        $base_data = explode('|', $price_history_array[0]['Price']);
        $base_currency = $base_data[1];

        if ($plugins['currencyConverter']) {
            $base_currency_code = str_replace('currency_', '', $base_currency);
            $base_currency_code = strlen($base_currency_code) === 3 ? strtoupper($base_currency_code) : $base_currency_code;
            $base_currency_code = $rlCurrencyConverter->systemCurrency[$base_currency_code] ?: $base_currency_code;

            if ($base_currency_code) {
                $base_currency_rate = $rlCurrencyConverter->rates[$base_currency_code]['Rate'];
            }
        }

        foreach ($price_history_array as $key => $value) {
            $price_array = explode('|', $value['Price']);
            $current_price = $price_array[0];
            $currency = $price_array[1] ? $lang['data_formats+name+' . $price_array[1]] : '';

            if (doubleval($current_price) != $current_price) {
                continue;
            }

            $tmp_price_array['Price'] = $current_price;
            $tmp_price_array['Currency'] = $currency;
            $price_string = $GLOBALS['rlValid']->str2money($current_price, $listing_type['Show_cents']);
            if ($config['system_currency_position'] == 'before') {
                $tmp_price_array['Price_value'] = $currency . ' ' . $price_string;
            } else {
                $tmp_price_array['Price_value'] = $price_string . ' ' . $currency;
            }
            $tmp_price_array['Date'] = date('Y/m/d', strtotime($value['Date']));
            $tmp_price_array['Event'] = $lang['ph_listed_for_sale'];

            if ($config['ph_sqft_enable']) {
                if ($config['square_feet_price_key']) {
                    $tmp_price_array['square_feet_price'] = $listing_fields[$config['square_feet_price_key']]['value'];
                } else {
                    $square_feet_tag = $config['square_feet_key'] ?: 'square_feet';
                    if ($listing_fields[$square_feet_tag]) {
                        if ($listing_fields[$square_feet_tag]['Type'] == 'mixed') {
                            $square_feet_source = explode('|', $listing_fields[$square_feet_tag]['source'][0]);
                            $square_feet_price = round($current_price / $square_feet_source[0], 3);
                            $tmp_price_array['square_feet_price'] = $square_feet_price;
                        }
                    } else {
                        $tmp_price_array['square_feet_price'] = "--";
                    }
                }
            }

            if (isset($prev_price)) {
                if ($plugins['currencyConverter'] && $base_currency_rate && $base_currency != $price_array[1]) {
                    $currency_code = str_replace('currency_', '', $price_array[1]);
                    $currency_code = strlen($currency_code) === 3 ? strtoupper($currency_code) : $currency_code;
                    $currency_code = $rlCurrencyConverter->systemCurrency[$currency_code] ?: $currency_code;

                    if ($currency_code && $currency_rate = $rlCurrencyConverter->rates[$currency_code]['Rate']) {
                        if ($base_currency_rate === '1') {
                            if ($currency_rate !== '1') {
                                $converted_price = $current_price / $currency_rate;
                            }
                        } else {
                            if ($currency_rate === '1') {
                                $converted_price = $current_price * $base_currency_rate;
                            } else {
                                $converted_price = $current_price / $currency_rate;
                                $converted_price = $converted_price * $base_currency_rate;
                            }
                        }
                        $current_price = round($converted_price, 2);
                    }
                }

                if ($prev_price != $current_price) {
                    if ($current_price > $prev_price) {
                        $tmp_price_array['price_diff_class'] = 'increase';
                        $diff = $current_price - $prev_price;
                    }
                    if ($current_price < $prev_price) {
                        $tmp_price_array['price_diff_class'] = 'reduce';
                        $diff = $prev_price - $current_price;
                    }
                }

                $tmp_price_array['tmp_price'] = $current_price;
                $tmp_price_array['price_diff'] = $diff;
                $price_percent = $prev_price ? round(($diff * 100) / $prev_price, 1) : $diff;

                $tmp_price_array['precent_diff'] = $price_percent;
                $tmp_price_array['Event'] = $lang['ph_price_changed'];
            }

            $data[$key] = $tmp_price_array;

            $prev_price = $current_price;
        }

        return $data;
    }

    /**
     * Edit some price history row
     *
     * @param array $ph_array - array of editing pricehistory row from the Admin panel
     * @return void
     **/
    public function apEditPriceHistory($ph_array)
    {
        global $listing_id, $reefless, $rlActions;

        $reefless->loadClass('Actions');

        $insertData = array();
        $updateData = array();

        foreach ($ph_array as $ph_key => $ph_val) {
            if ($ph_val['id']) {
                $updateData['Price'] = $ph_val['price'] . "|" . $ph_val['ccode'];
                $updateData['Date'] = $ph_val['ph_data'];
                $updateData['Listing_id'] = $listing_id;
                $updateData['IP'] = $reefless->getClientIpAddress();


                $update_price_history = array(
                    'fields' => $updateData,
                    'where' => array(
                        'ID' => $ph_val['id'],
                    ),
                );
                $rlActions->updateOne($update_price_history, 'price_history');
            } else {
                $insertData['Price'] = $ph_val['price'] . "|" . $ph_val['ccode'];
                $insertData['Date'] = $ph_val['ph_data'];
                $insertData['Listing_id'] = $listing_id;
                $insertData['IP'] = $reefless->getClientIpAddress();
                $rlActions->insertOne($insertData, 'price_history');
            }
        }
    }

    /**
     * Remove price history row from admin panel
     *
     * @since 1.2.2 - Removed $row_id parameter
     *
     * @param  int  $id - ID of the "pricehistory" record in database
     * @return bool     - Result of the DB query
     */
    public function ajaxDeletePriceHistory($id = 0)
    {
        if (!$id = (int) $id) {
            return false;
        }

        return $GLOBALS['rlDb']->query("DELETE FROM `{db_prefix}price_history` WHERE `ID` = {$id} LIMIT 1");
    }

    /**
     * @hook editListingAdditionalInfo
     *
     * @param  Flynax\Classes\EditListing $editListing - Instance of the EditListing class
     * @param  array                      $data        - New listing data
     *
     * @return bool
     */
    public function hookEditListingAdditionalInfo($editListing = null, $data = array()): bool
    {
        global $rlDb;

        $listingID = $editListing && $editListing->listingID ? $editListing->listingID : 0;

        if (!$listingID || defined('IS_ESCORT')) {
            return false;
        }

        $listing  = !empty($data) ? $data : $GLOBALS['data'];
        $priceTag = $GLOBALS['config']['price_tag_field'] ?: 'price';
        $oldPrice = $rlDb->getOne($priceTag, "`ID` = {$listingID}", 'listings') ?: 0;
        $newPrice = sprintf('%d|%s', $listing[$priceTag]['value'], $listing[$priceTag]['currency']);

        if ($this->price2Float($oldPrice) !== $this->price2Float($newPrice)) {
            if (!$rlDb->getOne('ID', "`Listing_id` = {$listingID}", 'price_history')) {
                $rlDb->insertOne([
                    'price'      => $oldPrice,
                    'Date'       => $editListing->listingData['Date'],
                    'Listing_id' => $listingID,
                    'IP'         => Util::getClientIP(),
                ], 'price_history');
            }

            $rlDb->insertOne([
                'price'      => $newPrice,
                'Date'       => date("Y-m-d H:i:s"),
                'Listing_id' => $listingID,
                'IP'         => Util::getClientIP(),
            ], 'price_history');
        }

        return true;
    }

    /**
     * Parse formated Price field and return numeric part
     *
     * @since 1.2.0
     *
     * @param  string $str - Flynax formatted price value
     * @return float       - Numeric part of provided price string
     */
    public function price2Float($str = '')
    {
        if (!$str) {
            return 0.0;
        }

        $priceParts = explode('|', $str);

        return (float) $priceParts[0];
    }

    /**
     * @hook staticDataRegister
     * @param rlStatic $rlStatic
     */
    public function hookStaticDataRegister($rlStatic = null)
    {
        $rlStatic = $rlStatic !== null ? $rlStatic : $GLOBALS['rlStatic'];

        $css = $this->config['url']['static'] . 'style.css';
        $js = $this->config['url']['static'] . 'lib.js';

        $rlStatic->addBoxFooterCSS($css, 'price_history');
        $rlStatic->addBoxJS($js, 'price_history');
    }

    /**
     * @hook  specialBlock
     * @since 1.2.0
     */
    public function hookSpecialBlock()
    {
        global $blocks;

        if ($GLOBALS['page_info']['Key'] != 'view_details') {
            unset($blocks['price_history']);
            $GLOBALS['rlCommon']->defineBlocksExist($blocks);
        }
    }

    /**
     * @since 1.2.0
     */
    public function install()
    {
        global $rlDb;

        $sql = "CREATE TABLE IF NOT EXISTS `" . RL_DBPREFIX . "price_history` (
              `ID` INT(11) NOT NULL AUTO_INCREMENT,
              `Price` VARCHAR(100) CHARACTER SET utf8 NOT NULL,
              `Listing_id` INT(11) NOT NULL,
              `Date` DATETIME,
              `IP` VARCHAR(20) CHARACTER SET utf8 NOT NULL,
              PRIMARY KEY  (`ID`)
        ) DEFAULT CHARSET=utf8;";
        $rlDb->query($sql);

        $sql = "UPDATE `" . RL_DBPREFIX . "blocks` SET `Sticky` = 0, `Cat_sticky` = '1',  ";
        $sql .= "`Page_ID` = (SELECT `ID` FROM `" . RL_DBPREFIX . "pages` WHERE `Key` = 'view_details' LIMIT 1) ";
        $sql .= "WHERE `Key` = 'price_history' ";
        $rlDb->query($sql);
    }

    /**
     * @hook  tplHeader
     * @since 1.2.0
     */
    public function hookTplHeader()
    {
        if ($GLOBALS['page_info']['Controller'] == 'listing_details' && $GLOBALS['listing_data']) {
            $GLOBALS['rlSmarty']->display($this->config['path']['view'] . 'tplHeader.tpl');
        } else {
            unset($GLOBALS['blocks']['price_history']);
            $GLOBALS['rlCommon']->defineBlocksExist($GLOBALS['blocks']);
        }
    }

    /**
     * @hook  apTplFooter
     * @since 1.2.0
     */
    public function hookApTplFooter()
    {
        if ($_GET['controller'] == 'listings' && $_GET['action'] == 'edit') {
            $GLOBALS['rlSmarty']->display($this->config['path']['view'] . 'apTplFooter.tpl');
        }
    }

    /**
     * @since 1.2.0
     */
    public function uninstall()
    {
        $GLOBALS['rlDb']->query("DROP TABLE IF EXISTS `" . RL_DBPREFIX . "price_history`");
    }

    /**
     * @hook  ahpListingsValidate
     * @since 1.2.0
     */
    public function hookApPhpListingsValidate()
    {
        global $data, $lang;

        $ph_array = array();

        if ($data['price_hisory']) {
            $ph_array = $data['price_hisory'];
            unset($data['price_hisory']);
        }

        $date_error = false;
        foreach ($ph_array as $ph_item) {
            if (!$ph_item['ph_data']) {
                $date_error = true;
                break;
            }
        }

        if ($date_error) {
            $GLOBALS['errors'] = str_replace('{field}', '<b>"' . $lang['date'] . '"</b>', $lang['notice_field_empty']);
        }

        $this->apEditPriceHistory($ph_array);
    }

    /**
     * @hook  apAjaxRequest
     * @since 1.2.0
     * @param array  $out  - Ajax output array
     * @param string $item - Catching case
     */
    public function hookApAjaxRequest(&$out = array(), &$item = '')
    {
        $item = $item !== null ? $item : $GLOBALS['item'];
        $out = $out !== null ? $out : $GLOBALS['out'];

        switch ($item) {
            case 'phRemovePriceRow':
                $id = $GLOBALS['rlValid']->xSql($_REQUEST['id']);
                $out['status'] = 'OK';

                if (!$this->ajaxDeletePriceHistory($id)) {
                    $out['status'] = 'ERROR';
                }
                break;
        }
    }

    /**
     * @hook  listingDetailsBottom
     * @since 1.2.0
     */
    public function hookListingDetailsBottom()
    {
        global $blocks;

        if (!$this->isPriceHistoryExist($GLOBALS['listing_id'])) {
            unset($blocks['price_history']);
            $GLOBALS['rlCommon']->defineBlocksExist($blocks);
        }
    }

    /**
     * Checking does price history is exist for provided Listing
     *
     * @param  int $listingID
     * @return bool
     */
    public function isPriceHistoryExist($listingID = 0)
    {
        if (!$listingID) {
            return false;
        }

        return (bool) $GLOBALS['rlDb']->getOne('ID', "`Listing_id` = '{$listingID}'", 'price_history');
    }

    /**
     * @since 1.2.0
     */
    public function update120()
    {
        $sql = "DELETE FROM `" . RL_DBPREFIX . "hooks` WHERE `Plugin` = 'priceHistory' ";
        $sql .= "AND `Name` IN('apPhpListingsBottom','tplFooter')";
        $GLOBALS['rlDb']->query($sql);
    }

    /**
     * @since 1.2.1
     */
    public function update121()
    {
        $sql = "UPDATE `" . RL_DBPREFIX . "lang_keys` SET `Value` = 'Key of Square feet field' ";
        $sql .= "WHERE `Key` = 'config+name+square_feet_key' AND `Plugin` = 'priceHistory'";
        $GLOBALS['rlDb']->query($sql);
    }

    /**
     * Update to 1.2.2 version of the plugin
     */
    public function update122()
    {
        $GLOBALS['rlDb']->query(
            "DELETE FROM `{db_prefix}config`
             WHERE `Key` = 'ph_enable' AND `Plugin` = 'priceHistory'"
        );
        $GLOBALS['rlDb']->query(
            "DELETE FROM `{db_prefix}lang_keys`
             WHERE (`Key` = 'config+name+ph_enable' OR `Key` = 'price_history_empty')
               AND `Plugin` = 'priceHistory'"
        );

        if (in_array('ru', array_keys($GLOBALS['languages']))) {
            $russianTranslation = json_decode(file_get_contents(RL_PLUGINS . 'priceHistory/i18n/ru.json'), true);
            foreach ($russianTranslation as $phraseKey => $phrase) {
                if ($GLOBALS['rlDb']->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $GLOBALS['rlDb']->updateOne([
                        'fields' => ['Value' => $phrase],
                        'where'  => ['Key'   => $phraseKey, 'Code' => 'ru'],
                    ], 'lang_keys');
                } else {
                    $GLOBALS['rlDb']->insertOne([
                        'Code'   => 'ru',
                        'Module' => 'common',
                        'Key'    =>  $phraseKey,
                        'Value'  => $russianTranslation[$phraseKey],
                        'Plugin' => 'priceHistory',
                    ], 'lang_keys');
                }

            }
        }
    }

    /**
     * @deprecated 1.2.0
     */
    public function hookTplFooter()
    {}
}
