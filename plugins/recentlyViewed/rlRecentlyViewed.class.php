<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLRECENTLYVIEWED.CLASS.PHP
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
 *  Flynax Classifieds Software 2025 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

use Flynax\Abstracts\AbstractPlugin;
use Flynax\Interfaces\PluginInterface;

class rlRecentlyViewed extends AbstractPlugin implements PluginInterface
{
    /**
     * Total number of saved listings
     * @since 1.3.0
     */
    public const TOTAL_COUNT = 100;

    /**
     * @since 1.2.4
     * @var   bool
     */
    public $isShowBox = false;

    /**
     * Path of plugin directory
     * @since 1.2.4
     */
    protected const PLUGIN_DIR = RL_PLUGINS . 'recentlyViewed/';

    /**
     * URL of static directory in the plugin
     * @since 1.2.4
     */
    protected const PLUGIN_STATIC_URL = RL_PLUGINS_URL . 'recentlyViewed/static/';

    /**
     * Add viewed listing to DB
     *
     * @param array $listing_data
     */
    public function addRvListing($listing_data = array())
    {
        global $config, $account_info, $rlDb;

        if (!$listing_data || !defined('IS_LOGIN')) {
            return false;
        }

        $GLOBALS['reefless']->loadClass('Actions');

        if ($rv_listings = $rlDb->getOne('rv_listings', "`ID` = '{$account_info['ID']}'", 'accounts')) {
            $rv_listings = explode(',', $rv_listings);

            foreach ($rv_listings as $id => $listing) {
                if ($listing != $listing_data['ID']) {
                    $new_rv_listings[] = $listing;
                }
            }

            if ($new_rv_listings) {
                array_unshift($new_rv_listings, $listing_data['ID']);
            }

            if ($new_rv_listings && count($new_rv_listings) > self::TOTAL_COUNT) {
                $new_rv_listings = array_slice($new_rv_listings, 0, self::TOTAL_COUNT);
            }

            $new_rv_listings = $new_rv_listings ? implode(',', $new_rv_listings) : '';

            $update_rv_listings = array(
                'fields' => array(
                    'rv_listings' => $new_rv_listings,
                ),
                'where'  => array(
                    'ID' => $account_info['ID'],
                ),
            );

            $rlDb->updateOne($update_rv_listings, 'accounts');
        } else {
            $update_rv_listings = array(
                'fields' => array(
                    'rv_listings' => $listing_data['ID'],
                ),
                'where'  => array(
                    'ID' => $account_info['ID'],
                ),
            );

            $rlDb->updateOne($update_rv_listings, 'accounts');
        }
    }

    /**
     * Get viewed listings from DB
     *
     * @param  string $rv_listings_ids - IDs of viewed listings
     * @param  int    $start           - Page number
     * @param  bool   $all             - Get details of all listings
     * @return array                   - Array of listings
     */
    public function getRvListings($rv_listings_ids = '', $start = 0, $all = false)
    {
        global $config, $sql, $rlListings, $rlCommon, $rlDb;

        if (!$rv_listings_ids) {
            return [];
        }

        if (is_string($rv_listings_ids)) {
            $rv_listings_ids = explode(',', $rv_listings_ids);
        }

        /* define start position */
        if (!$all) {
            $limit = $config['listings_per_page'];
            $start = $start > 1 ? ($start - 1) * $limit : 0;

            $rv_listings_ids = array_slice($rv_listings_ids, $start, $limit);
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.*, `T4`.`Path`, `T4`.`Type` AS `Listing_type`, DATE(`T1`.`Date`) AS `Post_date`, ";

        $GLOBALS['rlHook']->load('listingsModifyField', $sql);

        $sql .= "IF(`T1`.`Featured_date` <> '0000-00-00 00:00:00', '1', '0') `Featured`, ";
        $sql .= "`T4`.`Parent_ID`, `T4`.`Key` AS `Cat_key`, `T4`.`Key`, ";
        $sql .= "`T1`.`Status` AS `Listing_status`, `T4`.`Status` AS `Category_status`, `T7`.`Status` AS `Owner_status` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T4` ON `T1`.`Category_ID` = `T4`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T7` ON `T1`.`Account_ID` = `T7`.`ID` ";
        $sql .= "WHERE `T1`.`ID` IN ('" . implode("','", $rv_listings_ids) . "') ";
        $sql .= "ORDER BY FIND_IN_SET(`T1`.`ID`, '" . implode(',', $rv_listings_ids) . "')";
        $rv_listings = $rlDb->getAll($sql);

        if (empty($rv_listings)) {
            return [];
        }

        $rlListings->calc = $rlDb->getRow("SELECT FOUND_ROWS() AS `calc`", 'calc');
        $rv_listings = $GLOBALS['rlLang']->replaceLangKeys($rv_listings, 'categories', 'name');

        foreach ($rv_listings as &$listing) {
            // populate fields
            $fields = $rlListings->getFormFields($listing['Category_ID'], 'short_forms', $listing['Listing_type']);
            foreach ($fields as $fKey => $fValue) {
                $fields[$fKey]['value'] = $rlCommon->adaptValue($fValue, $listing[$fKey], 'listing', $listing['ID']);
            }
            $listing['fields'] = $fields;
            $listing['listing_title'] = $rlListings->getListingTitle(
                $listing['Category_ID'],
                $listing,
                $listing['Listing_type']
            );

            $listing['url'] = $GLOBALS['reefless']->getListingUrl($listing);
        }

        return $rv_listings;
    }

    /**
     * Remove viewed listing from DB
     *
     * @since 1.2.0 - Method changed to simple ajax
     *
     * @param int $rv_listing_id - ID of viewed listing
     */
    public function ajaxRemoveRvListing($rv_listing_id = 0)
    {
        global $account_info, $rlDb, $rlLang;

        $rv_listing_id = (int) $rv_listing_id;

        if ($rv_listing_id && $account_info['ID']) {
            if ($rv_listings = $rlDb->getOne('rv_listings', "`ID` = {$account_info['ID']}", 'accounts')) {
                $rv_listings = explode(',', $rv_listings);
                foreach ($rv_listings as $listing) {
                    if ($listing != $rv_listing_id) {
                        $new_rv_listings[] = $listing;
                    }
                }
                $new_rv_listings = implode(',', $new_rv_listings);

                $update_rv_listings = array(
                    'fields' => array(
                        'rv_listings' => $new_rv_listings,
                    ),
                    'where'  => array(
                        'ID' => $account_info['ID'],
                    ),
                );

                $rlDb->updateOne($update_rv_listings, 'accounts');
            }

            $out = array(
                'status' => 'OK',
                'data'   => $rlLang->getPhrase('rv_del_listing_success', null, null, true),
            );
        } else {
            $out = array(
                'status'  => 'ERROR',
                'message' => $rlLang->getSystem('rv_remove_listing_notify_fail'),
            );
        }

        return $out;
    }

    /**
     * Remove all viewed listings from list
     *
     * @since 1.2.0 - Method changed to simple ajax
     */
    public function ajaxRemoveAllRvListings()
    {
        global $account_info, $rlLang;

        if ($account_info['ID']) {
            $GLOBALS['rlDb']->query("
                UPDATE `{db_prefix}accounts` SET `rv_listings` = ''
                WHERE `ID` = {$account_info['ID']} LIMIT 1
            ");

            $out = array(
                'status' => 'OK',
                'data'   => $rlLang->getPhrase('rv_del_listings_success', null, null, true),
            );
        } else {
            $out = array(
                'status'  => 'ERROR',
                'message' => $rlLang->getSystem('rv_remove_listings_notify_fail'),
            );
        }

        return $out;
    }

    /**
     * Load viewed listings from storage
     *
     * @param  string $rv_ids  - IDs of viewed listings
     * @param  int    $pg      - Number of page in pagination
     * @param  array  $storage - Data of viewed listings from local storage
     * @return mixed           - Updated data of viewed listings or error
     */
    public function ajaxLoadRvListings($rv_ids = '', $pg = 0, $storage = array())
    {
        global $lang, $pages, $config, $rlSmarty, $reefless, $rlMembershipPlan, $rlCommon, $rlAccount, $rlHook,
        $rlXajax, $page_info, $block_keys, $blocks, $l_block_sides, $rlLang;

        if ($rv_ids) {
            $inactive_listings = false;
            $pInfo['current']  = (int) $pg;
            $tmp_rv_listings   = $this->getRvListings($rv_ids, $pInfo['current']);

            foreach ($tmp_rv_listings as $listing) {
                if ($listing['Listing_status'] === 'active'
                    && $listing['Category_status'] === 'active'
                    && $listing['Owner_status'] === 'active'
                ) {
                    $rv_listings[] = $listing;
                } else {
                    $inactive_listings = true;
                }
            }

            // Remove inactive/deleted/expired listings from storage and DB
            if (!$inactive_listings) {
                $start = $pInfo['current'] > 1 ? (($pInfo['current'] - 1) * (int) $config['listings_per_page']) : 0;
                if (count($tmp_rv_listings) < count(array_slice(explode(',', $rv_ids), $start, $config['listings_per_page']))) {
                    $inactive_listings = true;
                }
                unset($start);

                // check correct main photo of listing
                foreach ($tmp_rv_listings as $tmp_listing) {
                    foreach ($storage as $st_listing) {
                        if ($tmp_listing['ID'] == $st_listing[0] && $tmp_listing['Main_photo'] != $st_listing[1]) {
                            $inactive_listings = true;
                        }
                    }
                }
            }

            if ($rv_listings) {
                $rlSmarty->assign_by_ref('listings', $rv_listings);

                if ($inactive_listings) {
                    $tmp_rv_listings = $this->getRvListings($rv_ids, false, true);
                    $rv_listings     = array();

                    foreach ($tmp_rv_listings as $tmp_listing) {
                        if ($tmp_listing['Listing_status'] === 'active'
                            && $tmp_listing['Category_status'] === 'active'
                            && $tmp_listing['Owner_status'] === 'active'
                        ) {
                            $rv_listings[] = $tmp_listing;
                        }
                    }

                    $st_listings = array();
                    foreach ($rv_listings as $listing) {
                        $st_listings[] = array(
                            $listing['ID'],
                            $listing['Main_photo'],
                            $pages['lt_' . $listing['Listing_type']],
                            $listing['Path'] . "/" . $rlSmarty->str2path($listing['listing_title']),
                            trim(preg_replace('/\s+/', ' ', addslashes($listing['listing_title']))),
                            ($listing['url'] ?: false),
                        );
                    }
                    $st_listings = json_encode($st_listings);
                }
            }

            $pInfo['calc'] = count($inactive_listings ? explode(',', $rv_ids) : $storage);
            $rlSmarty->assign_by_ref('pInfo', $pInfo);
            $rlSmarty->assign_by_ref('lang', $lang);
            $rlSmarty->assign_by_ref('config', $config);

            // define $rlTplBase variable for smarty
            define('RL_TPL_BASE', RL_URL_HOME . 'templates/' . $config['template'] . '/');
            $rlSmarty->assign('rlTplBase', RL_TPL_BASE);

            $page_info = $GLOBALS['rlDb']->getRow("
                SELECT * FROM `{db_prefix}pages`
                WHERE `Key` = 'rv_listings' AND `Status` = 'active' LIMIT 1
            ");

            require RL_LIBS . 'system.lib.php';
            require RL_ROOT . 'templates' . RL_DS . $config['template'] . RL_DS . 'settings.tpl.php';

            // remove hooks from another plugins to prevent fatal error which use $rlXajax->registerFunction() method
            $reflection = new ReflectionProperty($rlHook, 'hooks');
            $reflection->setAccessible(true);

            $hooks = array();
            if ($GLOBALS['plugins']['banners']) {
                $hooks = array(
                    'specialBlock' => array(
                        0 => array(
                            'plugin' => 'banners',
                            'code'   => '$GLOBALS[\'reefless\']->loadClass(\'Banners\', null, \'banners\');
                                       $GLOBALS[\'rlBanners\']->prepareBannersList();',
                        ),
                    ),
                );

                require RL_LIBS . 'ajax/xajax_core/xajax.inc.php';

                $rlXajax              = new xajax();
                $_response            = new xajaxResponse();
                $GLOBALS['_response'] = $_response;

                $rlXajax->configure('javascript URI', RL_URL_HOME . 'libs/ajax/');
                $rlXajax->configure('debug', RL_AJAX_DEBUG);
                $rlXajax->setCharEncoding('UTF-8');
            }

            $reflection->setValue($rlHook, $hooks);

            $GLOBALS['deny_pages'] = [];
            require RL_CONTROL . 'common.inc.php';

            $out = array(
                'status' => 'OK',
                'data'   => array(
                    'listings' => $rv_listings ? $rlSmarty->fetch(self::PLUGIN_DIR . 'rv_listings.tpl') : false,
                    'storage'  => $st_listings ?: '',
                ),
            );
        } else {
            $out = array(
                'status'  => 'ERROR',
                'message' => $rlLang->getSystem('rv_get_listings_notify_fail'),
            );
        }

        return $out;
    }

    /**
     * Synchronization of local viewed and saved in DB listings
     *
     * @since 1.2.0 - Method changed to simple ajax
     *
     * @param  array $rv_storage_ids - IDs of viewed listings from storage
     * @return bool                  - True or false
     */
    public function ajaxSyncRvListings($rv_storage_ids = array())
    {
        global $account_info, $rlDb, $pages, $rlLang;

        if ($account_info['ID']) {
            $rv_listings_ids = $rlDb->getOne('rv_listings', "`ID` = {$account_info['ID']}", 'accounts');

            if (substr($rv_listings_ids, -1, 1) == ',') {
                $rv_listings_ids = substr_replace($rv_listings_ids, '', strrpos($rv_listings_ids, ','));
            }

            $rv_db_listings = explode(',', $rv_listings_ids);
            $rv_st_listings = explode(',', $rv_storage_ids);

            if (count($rv_db_listings) > count($rv_st_listings)) {
                // add missing listings to storage from DB
                for ($i = count($rv_db_listings); $i >= 0; $i--) {
                    if (!in_array($rv_db_listings[$i], $rv_st_listings) && (int) $rv_db_listings[$i]) {
                        array_unshift($rv_st_listings, $rv_db_listings[$i]);
                    }
                }

                $rv_ids = implode(',', $rv_st_listings);
            } else {
                // add missing listings to DB from storage
                for ($i = count($rv_st_listings); $i >= 0; $i--) {
                    if (!in_array($rv_st_listings[$i], $rv_db_listings) && (int) $rv_st_listings[$i]) {
                        array_unshift($rv_db_listings, $rv_st_listings[$i]);
                    }
                }

                $rv_ids = implode(',', $rv_db_listings);
                $rlDb->query("
                    UPDATE `{db_prefix}accounts` SET `rv_listings` = '{$rv_ids}'
                    WHERE `ID` = {$account_info['ID']} LIMIT 1
                ");
            }

            $tmp_rv_listings = $this->getRvListings($rv_ids, false, true);

            if ($tmp_rv_listings) {
                $rv_ids = '';

                // removing inactive listings from storage and DB
                foreach ($tmp_rv_listings as $listing) {
                    if ($listing['Listing_status'] === 'active'
                        && $listing['Category_status'] === 'active'
                        && $listing['Owner_status'] === 'active'
                    ) {
                        $rv_listings[] = $listing;
                        $rv_ids        = $rv_ids ? ($rv_ids . ',' . $listing['ID']) : $listing['ID'];
                    }
                }

                $rlDb->query("
                    UPDATE `{db_prefix}accounts` SET `rv_listings` = '{$rv_ids}'
                    WHERE `ID` = {$account_info['ID']} LIMIT 1
                ");

                $st_listings = array();
                foreach ($rv_listings as $listing) {
                    $st_listings[] = array(
                        $listing['ID'],
                        $listing['Main_photo'],
                        $pages['lt_' . $listing['Listing_type']],
                        $listing['Path'] . '/' . $GLOBALS['rlSmarty']->str2path($listing['listing_title']),
                        trim(preg_replace('/\s+/', ' ', addslashes($listing['listing_title']))),
                        $listing['url'] ?: false,
                    );
                }
                $st_listings = json_encode($st_listings);
            }

            $_SESSION['sync_rv_complete'] = 1;

            $out = array(
                'status' => 'OK',
                'data'   => $st_listings ?: false,
            );
        } else {
            $out = array(
                'status'  => 'ERROR',
                'message' => $rlLang->getSystem('rv_get_listings_notify_fail'),
            );
        }

        return $out;
    }

    /**
     * Get standard box listings
     *
     * @since 1.4.0
     * @param  string $ids - Listing IDs comma separated
     * @return array       - Standard ajax response
     */
    public function ajaxGetStandardBoxListings(string $ids): array
    {
        global $rlSmarty, $config;

        if (!$ids) {
            return ['status' => 'ERROR'];
        }

        $listings = $this->getRvListings($ids);
        $rlSmarty->assign_by_ref('listings', $listings);

        require sprintf('%stemplates/%s/settings.tpl.php', RL_ROOT, $config['template']);

        $rlSmarty->preAjaxSupport();

        // Enable 'photo' option for all listing types to avoid mess in box
        foreach ($GLOBALS['rlListingTypes']->types as &$type) {
            $type['Photo_back'] = $type['Photo'];
            $type['Photo'] = 1;
        }

        $tpl = 'blocks' . RL_DS . 'featured.tpl';
        $html = $rlSmarty->fetch($tpl, null, null, false);

        // Reset 'photo' option
        foreach ($GLOBALS['rlListingTypes']->types as &$type) {
            $type['Photo'] = $type['Photo_back'];
            unset($type['Photo_back']);
        }

        $data = [
            'status' => 'OK',
            'html' => $html
        ];

        return $data;
    }

    /**
     * @hook  listingDetailsTop
     * @since 1.2.0
     */
    public function hookListingDetailsTop(): void
    {
        $this->addRvListing($GLOBALS['listing_data']);
    }

    /**
     * @hook  listingDetailsTopTpl
     * @since 1.2.0
     */
    public function hookListingDetailsTopTpl(): void
    {
        if ($GLOBALS['page_info']['Controller'] !== 'listing_details') {
            return;
        }

        $GLOBALS['rlSmarty']->assign('rv_total_count', self::TOTAL_COUNT);
        $GLOBALS['rlSmarty']->display(self::PLUGIN_DIR . 'rv_add_listing.tpl');
    }

    /**
     * @hook  listingNavIcons
     * @since 1.2.0
     */
    public function hookListingNavIcons(): void
    {
        if ($GLOBALS['page_info']['Controller'] === 'rv_listings') {
            $GLOBALS['rlSmarty']->display(self::PLUGIN_DIR . 'rv_del_icon.tpl');
        }
    }

    /**
     * Show style for Standard Box mode
     *
     * @since 1.4.0
     * @hook tplHeader
     */
    public function hookTplHeader(): void
    {
        if (!$this->isShowBox || $GLOBALS['config']['rv_box_type'] != 'standard_block') {
            return;
        }

        echo <<< HTML
<style>
@media screen and (min-width: 1200px) {
    .rv_listings_dom .featured {
        flex-wrap: nowrap;
        overflow: hidden;
    }
}
.recentlyViewed:not(.rv-rendered) {
    visibility: hidden;
    height: 1px;
    overflow: hidden;
    padding: 0 !important;
    margin: 0 !important;
}
section section.recentlyViewed ul.featured {
    margin-bottom: 0;
}
</style>
HTML;
    }

    /**
     * @hook  tplFooter
     * @since 1.2.0
     */
    public function hookTplFooter(): void
    {
        if ($GLOBALS['config']['rv_box_type'] == 'bottom_bar'
            && $GLOBALS['page_info']['Controller'] != 'listing_details'
            && $this->isShowBox
        ) {
            $GLOBALS['rlSmarty']->display(self::PLUGIN_DIR . 'rv_block.tpl');
        }
    }

    /**
     * @hook  ajaxRequest
     * @since 1.2.0
     * @throws ReflectionException
     */
    public function hookAjaxRequest(&$out, $mode = '', $item = '', $request_lang = '')
    {
        global $account_info;

        if (!$this->isValidAjaxRequest($mode)) {
            return false;
        }

        $account_info = $_SESSION['account'];

        switch ($mode) {
            case 'rvRemoveAllListings':
                $out = $this->ajaxRemoveAllRvListings();
                break;
            case 'rvRemoveListing':
                $out = $this->ajaxRemoveRvListing($item);
                break;
            case 'rvLoadListings':
                $out = $this->ajaxLoadRvListings($item['ids'], $item['pg'], $item['storage']);
                break;
            case 'rvSyncListings':
                $out = $this->ajaxSyncRvListings($item);
                break;
            case 'rvGetStandardBoxListings':
                $out = $this->ajaxGetStandardBoxListings($item);
                break;
        }
    }

    /**
     * @hook  apTplContentBottom
     * @since 1.2.0
     */
    public function hookApTplContentBottom(): void
    {
        // hide unnecessary options for plugin box
        if ($GLOBALS['controller'] === 'blocks' && $_GET['block'] === 'rv_listings') {
            echo "<script>"
                . "$('#btypes').hide();"
                . "$('#cats,.lang_add,[name=\"status\"],[name=\"side\"]').closest('tr').hide()</script>";
        }
    }

    /**
     * @hook  specialBlock
     * @since 1.2.0
     */
    public function hookSpecialBlock(): void
    {
        global $blocks, $rlStatic, $page_info;

        if ($blocks['rv_listings'] && $page_info['Controller'] !== 'rv_listings') {
            $this->isShowBox = true;

            if ($GLOBALS['config']['rv_box_type'] == 'bottom_bar') {
                $GLOBALS['lang']['rv_listings'] = $blocks['rv_listings']['name'];
                unset($blocks['rv_listings']);
                $GLOBALS['rlCommon']->defineBlocksExist($blocks);
                $rlStatic->addFooterCSS(self::PLUGIN_STATIC_URL  . 'style.css');
            } else {
                $blocks['rv_listings']['Content'] = "{include file=\$smarty.const.RL_PLUGINS|cat:'recentlyViewed/rv_block.tpl'}";
            }
        } else {
            unset($blocks['rv_listings']);
            $GLOBALS['rlCommon']->defineBlocksExist($blocks);
        }

        if ($this->isShowBox || in_array($page_info['Key'], ['view_details', 'rv_listings'])) {
            $rlStatic->addJS(self::PLUGIN_STATIC_URL . 'xdLocalStorage.min.js');
            $rlStatic->addJS(self::PLUGIN_STATIC_URL . 'lib.js');
        }

        $GLOBALS['rlSmarty']->assign('rvShowBox', $this->isShowBox);
    }

    /**
     * @hook  apMixConfigItem
     *
     * @param  array $value
     * @param  array $systemSelects - Required configs with "select" type
     * @return void
     */
    public function hookApMixConfigItem(&$value, &$systemSelects = null)
    {
        if ($value['Key'] !== 'rv_box_type') {
            return;
        }

        // Mark field as "required" to remove "- Select -" option
        $systemSelects[] = 'rv_box_type';
    }

    /**
     * Install process
     *
     * @since 1.2.0
     */
    public function install(): void
    {
        global $rlDb;

        $rlDb->addColumnsToTable(['rv_listings' => 'VARCHAR(255) NOT NULL AFTER `Status`'], 'accounts');

        $lTypesPages = $rlDb->getRow(
            "SELECT GROUP_CONCAT(`Key`) FROM `{db_prefix}pages`
            WHERE `Controller` = 'listing_type' AND `Status` = 'active' AND `Key` LIKE 'lt\_%'",
            'GROUP_CONCAT(`Key`)'
        );
        $keysPages = "home,listings,view_details,rv_listings,{$lTypesPages}";
        $idsPages  = $rlDb->getRow(
            "SELECT GROUP_CONCAT(`ID`) FROM `{db_prefix}pages`
            WHERE `Key` IN ('" . str_replace(',', "','", $keysPages) . "')",
            'GROUP_CONCAT(`ID`)'
        );

        $rlDb->updateOne([
            'fields' => ['Sticky' => '0', 'Cat_sticky' => '1', 'Page_ID' => $idsPages],
            'where'  => ['Key' => 'rv_listings']
        ], 'blocks');
    }

    /**
     * Uninstall process
     *
     * @since 1.2.0
     */
    public function uninstall(): void
    {
        $GLOBALS['rlDb']->dropColumnFromTable('rv_listings', 'accounts');
    }

    /**
     * Update to 1.1.2 version
     */
    public function update112(): void
    {
        $GLOBALS['rlDb']->query(
            "DELETE FROM `{db_prefix}hooks`
            WHERE `Name` = 'tplHeader' AND `Plugin` = 'recentlyViewed'"
        );
    }

    /**
     * Update to 1.2.0 version
     */
    public function update120(): void
    {
        global $rlDb;

        $lTypesPages = $rlDb->getRow(
            "SELECT GROUP_CONCAT(`Key`) FROM `{db_prefix}pages`
            WHERE `Controller` = 'listing_type' AND `Status` = 'active' AND `Key` LIKE 'lt\_%'",
            'GROUP_CONCAT(`Key`)'
        );
        $keysPages   = "home,listings,view_details,rv_listings,{$lTypesPages}";
        $idsPages    = $rlDb->getRow(
            "SELECT GROUP_CONCAT(`ID`) FROM `{db_prefix}pages`
            WHERE `Key` IN ('" . str_replace(',', "','", $keysPages) . "')",
            'GROUP_CONCAT(`ID`)'
        );

        $rlDb->updateOne([
            'fields' => ['Sticky' => '0', 'Page_ID' => $idsPages],
            'where' => ['Key' => 'rv_listings']
        ], 'blocks');
    }

    /**
     * Update to 1.2.4 version
     */
    public function update124(): void
    {
        global $rlDb, $config;

        $rlDb->query(
            "DELETE FROM `{db_prefix}config`
            WHERE `Key` = 'rv_allowed_pages' AND `Plugin` = 'recentlyViewed'"
        );

        $sticky = $config['rv_allowed_pages'] == 'rv_all_pages' ? '1' : '0';

        if ($config['rv_allowed_pages']) {
            $keysPages = $config['rv_allowed_pages'];
        } else {
            $lTypesPages = $rlDb->getRow(
                "SELECT GROUP_CONCAT(`Key`) FROM `{db_prefix}pages`
                WHERE `Controller` = 'listing_type' AND `Status` = 'active' AND `Key` LIKE 'lt\_%'",
                'GROUP_CONCAT(`Key`)'
            );
            $keysPages = "home,listings,view_details,rv_listings,{$lTypesPages}";
        }

        $idsPages = $rlDb->getRow(
            "SELECT GROUP_CONCAT(`ID`) FROM `{db_prefix}pages`
             WHERE `Key` IN ('" . str_replace(',', "','", $keysPages) . "')",
            'GROUP_CONCAT(`ID`)'
        );

        $rlDb->updateOne([
            'fields' => ['Sticky' => $sticky, 'Cat_sticky' => '1',  'Page_ID' => $idsPages],
            'where' => ['Key' => 'rv_listings']
        ], 'blocks');

        $rlDb->query(
            "DELETE FROM `{db_prefix}hooks`
            WHERE `Name` IN ('apPhpBlocksAfterEdit', 'staticDataRegister') AND `Plugin` = 'recentlyViewed'"
        );
    }

    /**
     * Update to 1.2.6 version
     */
    public function update126(): void
    {
        global $rlDb, $languages;

        foreach (['rv_listings', 'rv_history_link'] as $phraseKey) {
            $rlDb->updateOne([
                'fields' => ['Module' => 'box', 'Target_key' => 'rv_listings',  'JS' => '1'],
                'where'  => ['Key'    => $phraseKey],
            ], 'lang_keys');
        }

        $rlDb->updateOne([
            'fields' => ['Module' => 'frontEnd', 'Target_key' => 'rv_listings',  'JS' => '1'],
            'where'  => ['Key'    => 'rv_no_listings'],
        ], 'lang_keys');

        foreach (['rv_del_listings', 'rv_del_listing'] as $phraseKey) {
            $rlDb->updateOne([
                'fields' => ['Module' => 'frontEnd', 'Target_key' => 'rv_listings'],
                'where'  => ['Key'    => $phraseKey],
            ], 'lang_keys');
        }

        $phrases = ['rv_del_listing_notice', 'rv_del_listing_success', 'rv_del_listings_notice', 'rv_del_listings_success'];
        foreach ($phrases as $phraseKey) {
            $rlDb->updateOne([
                'fields' => ['Module' => 'frontEnd', 'Target_key' => 'rv_listings',  'JS' => '1'],
                'where'  => ['Key'    => $phraseKey],
            ], 'lang_keys');
        }

        $phrases = ['rv_get_listings_notify_fail', 'rv_remove_listing_notify_fail', 'rv_remove_listings_notify_fail'];
        foreach ($phrases as $phraseKey) {
            $rlDb->updateOne([
                'fields' => ['Module' => 'system'],
                'where'  => ['Key'    => $phraseKey],
            ], 'lang_keys');
        }

        if (array_key_exists('ru', $languages)) {
            $russianTranslation = json_decode(file_get_contents(RL_UPLOAD . 'recentlyViewed/i18n/ru.json'), true);
            foreach (['pages+title+rv_listings', 'pages+name+rv_listings', 'blocks+name+rv_listings'] as $phraseKey) {
                $rlDb->updateOne([
                    'fields' => ['Value' => $russianTranslation[$phraseKey]],
                    'where'  => ['Key'   => $phraseKey, 'Code' => 'ru'],
                ], 'lang_keys');
            }
        }
    }

    /**
     * Update to 1.3.0 version
     */
    public function update130(): void
    {
        $GLOBALS['rlDb']->query(
            "DELETE FROM `{db_prefix}config`
             WHERE `Key` IN ('rv_module','rv_count_per_page','rv_total_count') AND `Plugin` = 'recentlyViewed'"
        );
        $GLOBALS['rlDb']->query(
            "DELETE FROM `{db_prefix}config_groups`
             WHERE `Key` = 'recently_viewed' AND `Plugin` = 'recentlyViewed'"
        );
        $GLOBALS['rlDb']->query(
            "DELETE FROM `{db_prefix}lang_keys`
             WHERE `Plugin` = 'recentlyViewed' AND `Key` IN (
                 'config+name+rv_module',
                 'config+name+rv_count_per_page',
                 'config+name+rv_total_count',
                 'config_groups+name+recently_viewed'
             )"
        );
    }

    /**
     * Update to 1.4.0 version
     */
    public function update140(): void
    {
        global $rlDb;

        $phrases = $rlDb->fetch(['Key', 'Value', 'Code'], ['Key' => 'rv_listings', 'Plugin' => 'recentlyViewed'], null, null, 'lang_keys');
        foreach ($phrases as $phrase) {
            $rlDb->updateOne([
                'where' => ['Key' => 'blocks+name+rv_listings', 'Code' => $phrase['Code'], 'Plugin' => 'recentlyViewed'],
                'fields' => ['Value' => $phrase['Value']],
            ], 'lang_keys');
        }

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
             WHERE `Plugin` = 'recentlyViewed' AND `Key` IN (
                 'rv_listings'
             )"
        );
    }

    /**
     * Check correct key of ajax requests
     *
     * @since 1.2.4
     *
     * @param string $mode
     *
     * @return bool
     */
    public function isValidAjaxRequest(string $mode = ''): bool
    {
        $validRequests = [
            'rvRemoveAllListings',
            'rvRemoveListing',
            'rvLoadListings',
            'rvSyncListings',
            'rvGetStandardBoxListings',
        ];

        return ($mode && in_array($mode, $validRequests));
    }
}
