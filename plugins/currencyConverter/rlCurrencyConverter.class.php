<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLCURRENCYCONVERTER.CLASS.PHP
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

/**
 * @since 4.6.0 - entire class methods rewrote
 */
class rlCurrencyConverter
{
    /**
     * "Country code" to "Currency code" mapping
     * 
     * @var array
     */
    public $mapping = array();

    /**
     * Currencies exchange rates feed
     * 
     * @var string
     */
    public $feedURL = 'https://www.floatrates.com/daily/usd.xml';

    /**
     * Default price field key
     * 
     * @var string
     */
    public $priceFieldKey = 'price';

    /**
     * Default price data entry prefix
     * 
     * @var string
     */
    private $currencyDataEntryPrefix = 'currency_';
    
    /**
     * System currencies mapping, "Currency code" to "Currency key"
     * 
     * @var array
     */
    public $systemCurrency = array(
        'USD' => 'dollar',
        'GBP' => 'pound',
        'EUR' => 'euro'
    );

    /**
     * "seoBase" hook code pattern. This hook uses as cache statement for the plugin.
     * 
     * @var string
     */
    public $specialBlock = <<< FL
        \$GLOBALS['reefless']->loadClass('CurrencyConverter', null, 'currencyConverter');
        
        \$rates = array({rates_items});
        \$GLOBALS['rlSmarty']->assign_by_ref('curConv_rates', \$rates);

        \$GLOBALS['rlCurrencyConverter']->rates = \$rates;
        \$GLOBALS['rlCurrencyConverter']->detectCurrency();
FL;

    /**
     * Available rates
     */
    public $rates = false;

    /**
     * Class constructor
     */
    public function __construct()
    {
        global $rlSmarty, $config;

        // set mapping
        $this->setMapping();

        // register smarty modifier
        if (is_object('rlSmarty')) {
            $rlSmarty->register_modifier('flHtmlEntitiesDecode', 'flHtmlEntitiesDecode');
        }

        // define price field key
        if ($config['price_tag_field']) {
            $this->priceFieldKey = $config['price_tag_field'];
        }

        // previous plugin version usage fallback
        $config['currencyConverter_price_field'] = $config['price_tag_field'];
        $config['currencyConverter_featured'] = true;
    }

    /**
     * Plugin installer
     */
    public function install()
    {
        global $rlDb;

        // create rates table
        $sql = "
            CREATE TABLE IF NOT EXISTS `" . RL_DBPREFIX . "currency_rate` (
            `ID` INT NOT NULL AUTO_INCREMENT,
            `Code` VARCHAR(3) NOT NULL,
            `Key` VARCHAR(15) NOT NULL,
            `Rate` DOUBLE NOT NULL,
            `Country` VARCHAR(255) NOT NULL,
            `Symbol` varchar(255) CHARACTER SET utf8 NOT NULL,
            `Sticky` enum('0','1') NOT NULL DEFAULT '0',
            `Position` int(3) NOT NULL,
            `Date` DATE NOT NULL,
            `Status` ENUM('active', 'approval') DEFAULT 'active' NOT NULL,
            PRIMARY KEY (`ID`)
            ) DEFAULT CHARSET=utf8;
        ";
        $rlDb->query($sql);

        // prepare hook for cache
        $rlDb->updateOne([
            'fields' => ['Class' => ''],
            'where'  => ['Name' => 'seoBase', 'Plugin' => 'currencyConverter']
        ], 'hooks');

        // insert rates
        $this->updateRates(true);
    }

    /**
     * Plugin un-installer
     */
    public function uninstall()
    {
        $GLOBALS['rlDb']->query("DROP TABLE IF EXISTS `" . RL_DBPREFIX . "currency_rate`");
    }

    /**
     * @hook cronAdditional
     */
    public function hookCronAdditional()
    {
        if (!$GLOBALS['config']['currencyConverter_update']) {
            return;
        }

        $this->updateRates();
    }

    /**
     * @hook listingsModifyWhereSearch
     * 
     * @param string &$sql  - Main sql query
     * @param array  &$data - Search data
     */
    public function hookListingsModifyWhereSearch(&$sql, &$data)
    {
        // currency search mode
        if ($data[$this->priceFieldKey]['currency']) {
            if (is_numeric(strpos($data[$this->priceFieldKey]['currency'], 'currency_'))) {
                $code = strtoupper(str_replace('currency_', '', $data[$this->priceFieldKey]['currency']));
            } else {
                $code = $data[$this->priceFieldKey]['currency'];
            }

            $requested_rate = $this->rates[$code]['Rate'];

            if ($requested_rate) {
                // remove default search by price from sql request
                $pattern = array(
                    "/(\\s+AND\\sLOCATE\\('.*',\\s`T1`\\.`{$this->priceFieldKey}`\\)\\s\\>\\s[0-9\\.,]+)/",
                    "/(\\s+AND\\sROUND\\(`T1`\\.`{$this->priceFieldKey}`(,\\s?2)?\\)\\s\\>\\=\\s'[0-9\\.,]+')/",
                    "/(\\s+AND\\sROUND\\(`T1`\\.`{$this->priceFieldKey}`(,\\s?2)?\\)\\s\\<\\=\\s'[0-9\\.,]+')/"
                );

                $sql = preg_replace($pattern, '', $sql);

                // reassing user currency
                $GLOBALS['rlSmarty']->assign_by_ref('curConv_code', $code);

                // converted search
                if ($from = $data[$this->priceFieldKey]['from']) {
                    $from /= $requested_rate;
                    $sql .= "AND SUBSTRING_INDEX(`T1`.`{$this->priceFieldKey}`, '|', 1)/IF(`CURCONV`.`Rate` IS NULL, 1, `CURCONV`.`Rate`) >= {$from} ";
                }

                if ($orig_to = $to = $data[$this->priceFieldKey]['to']) {
                    $to /= $requested_rate;
                    $sql .= "AND SUBSTRING_INDEX(`T1`.`{$this->priceFieldKey}`, '|', 1)/IF(`CURCONV`.`Rate` IS NULL, 1, `CURCONV`.`Rate`) <= {$to} ";
                }
            }
        }
    }

    /**
     * @hook listingsModifyJoinSearch
     * 
     * @param string &$sql  - Main sql query
     * @param array  &$data - Search data
     */
    public function hookListingsModifyJoinSearch(&$sql, &$data)
    {
        if (($data[$this->priceFieldKey]['currency'] || $data['sort_by'] == $this->priceFieldKey)
            && !is_numeric(strpos($sql, 'AS `CURCONV`'))
        ) {
            $sql .= "
                LEFT JOIN `" . RL_DBPREFIX . "currency_rate` AS `CURCONV` 
                ON REPLACE(SUBSTRING_INDEX(`T1`.`{$this->priceFieldKey}`, '|', -1), '{$this->currencyDataEntryPrefix}', '') = `CURCONV`.`Key` 
                AND `CURCONV`.`Status` = 'active' 
            ";
        }
    }

    /**
     * @hook listingsModifyFieldSearch
     * 
     * @param string &$sql  - Main sql query
     * @param array  &$data - Search data
     */
    public function hookListingsModifyFieldSearch(&$sql, &$data)
    {
        global $custom_order;

        if ($data['sort_by'] == $this->priceFieldKey) {
            $sql .= "SUBSTRING_INDEX(`T1`.`{$this->priceFieldKey}`, '|', 1)/IF(`CURCONV`.`Rate` IS NULL, 1, `CURCONV`.`Rate`) AS `cc_price_tmp`, ";
            $custom_order = 'cc_price_tmp';
        }
    }

    /**
     * @hook listingsModifyJoin
     */
    public function hookListingsModifyJoin()
    {
        global $sql, $order_field;

        if ($order_field == $this->priceFieldKey && !is_numeric(strpos($sql, 'AS `CURCONV`'))) {
            $sql .= "
                LEFT JOIN `" . RL_DBPREFIX . "currency_rate` AS `CURCONV` 
                ON REPLACE(SUBSTRING_INDEX(`T1`.`{$this->priceFieldKey}`, '|', -1), '{$this->currencyDataEntryPrefix}', '') = `CURCONV`.`Key`
                AND `CURCONV`.`Status` = 'active' 
            ";
        }
    }

    /**
     * @hook listingsModifyField
     */
    public function hookListingsModifyField()
    {
        global $sql, $order_field, $custom_order;

        if ($order_field == $this->priceFieldKey) {
            $sql .= "SUBSTRING_INDEX(`T1`.`{$this->priceFieldKey}`, '|', 1)/IF(`CURCONV`.`Rate` IS NULL, 1, `CURCONV`.`Rate`) AS `cc_price_tmp`, ";
            $custom_order = 'cc_price_tmp';
        }
    }

    /**
     * @hook tplHeaderUserNav
     */
    public function hookTplHeaderUserNav()
    {
        $has_active = false;

        foreach ($GLOBALS['rlSmarty']->_tpl_vars['curConv_rates'] as $rate) {
            if ($rate['Status'] == 'active') {
                $has_active = true;
                break;
            }
        }

        if ($has_active) {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'currencyConverter' . RL_DS . 'user_navbar.tpl');
        }
    }

    /**
     * @hook tplHeader
     */
    public function hookTplHeader()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'currencyConverter' . RL_DS . 'header.tpl');
    }

    /**
     * @hook staticDataRegister
     */
    public function hookStaticDataRegister()
    {
        $GLOBALS['rlStatic']->addJS(RL_PLUGINS_URL . 'currencyConverter/static/lib.js');
    }

    /**
     * @hook tplPrintPage
     */
    public function hookTplPrintPage()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'currencyConverter' . RL_DS . 'header.tpl');
        echo '<script src="' . RL_PLUGINS_URL . 'currencyConverter/static/lib.js"></script>';
    }

    /**
     * @hook apAjaxRequest
     */
    public function hookApAjaxRequest()
    {
        global $out, $item;

        switch ($item) {
            case 'currencyConverter_update_rates':
                if ($this->updateRates()) {
                    $out = array('status' => 'OK');
                } else {
                    $out = array(
                        'status' => 'ERROR',
                        'message' => $GLOBALS['rlLang']->getSystem('currencyConverter_update_rss_fail')
                    );
                }
                break;

            case 'currencyConverter_add_rate':
                if (true === $result = $this->addCurrency($_REQUEST['code'], $_REQUEST['rate'], $_REQUEST['name'], $_REQUEST['symbols'], $_REQUEST['status'])) {
                    $out = array('status' => 'OK');
                } else {
                    $out = array(
                        'status' => 'ERROR',
                        'message' => $result
                    );
                }
                break;

            case 'currencyConverter_mass_action':
                if ($this->massAction($_REQUEST['ids'], $_REQUEST['action'])) {
                    $out = array('status' => 'OK');
                } else {
                    $out = array('status' => 'ERROR');
                }
                break;
        }
    }

    /**
     * Get rates data array
     *
     * @since 3.3.2
     *
     * @return array - Rates data array
     */
    public function getRates(): array
    {
        global $rlDb;

        if (!$this->rates) {
            $hook_code = $rlDb->getOne('Code', "`Name` = 'seoBase' AND `Plugin` = 'currencyConverter'", 'hooks');
            if ($hook_code) {
                eval($hook_code);
            }
        }

        return $this->rates ?: [];
    }

    /**
     * Mass actions handler
     * 
     * @param  string $id_list - List of ids to apply action to, used slash as separater, ex: 1|2|52
     * @param  string $action  - Activate or approve
     * @return bool            - "true" if the mass action applied successfully
     */
    public function massAction($id_list = null, $action = 'activate')
    {
        $set_status = $action == 'activate' ? 'active' : 'approval';
        $IDs = explode('|', $id_list);

        $sql = "
            UPDATE `" . RL_DBPREFIX . "currency_rate` 
            SET `Status` = '{$set_status}'
            WHERE `ID` IN (" . implode(', ', $IDs) . ")
        ";

        $GLOBALS['rlDb']->query($sql);

        $this->updateHook();

        return true;
    }
    
    /**
     * Add new currency
     *
     * @since 3.2.0 - $symbols parameter addded
     *
     * @param string $code    - Currency code abbr
     * @param double $rate    - Currency rate
     * @param string $name    - Currency name
     * @param string $symbols - Currency symbols (comma separated)
     * @param string $status  - Currency status
     */
    public function addCurrency($code = null, $rate = 0, $name = null, $symbols = null, $status = 'active')
    {
        global $lang, $config, $rlDb, $rlLang;

        $GLOBALS['reefless']->loadClass('Actions');

        $GLOBALS['rlValid']->sql($code);
        if ($exist = $rlDb->getOne('ID', "`Code` = '{$code}'", 'currency_rate')) {
            $errors[] = str_replace('{code}', $code, $rlLang->getSystem('currencyConverter_code_exists'));
        }
        
        preg_match('/([A-Z]{3})/', $code, $matches);
        if (!$matches[1]){
            $errors[] = $rlLang->getSystem('currencyConverter_code_wrong');
        }
        
        preg_match('/^([0-9\.]+)$/', $rate, $matches_rate);
        if (!$matches_rate[1]) {
            $errors[] = $rlLang->getSystem('currencyConverter_rate_wrong');
        }
        
        if ($errors) {
            return $errors;
        } else {
            $position = $rlDb->getRow("SELECT MAX(`Position`) AS `Max` FROM `{db_prefix}currency_rate` WHERE `Sticky` = '1'", 'Max');

            $insert = array(
                'Code' => $code,
                'Rate' => $rate,
                'Key' => $code,
                'Country' => $name,
                'Symbol' => $symbols,
                'Date' => 'NOW()',
                'Sticky' => '1',
                'Position' => ++$position,
                'Status' => $status
            );
            $GLOBALS['rlActions']->insertOne($insert, 'currency_rate');
            
            $this->updateHook();

            return true;
        }
    }

    /**
     * Update/insert currency rates
     * 
     * @param bool $insert_mode - Enables insert (only) mode
     */
    public function updateRates($insert_mode = false)
    {
        global $config, $rlDb;

        $content = $GLOBALS['reefless']->getPageContent($this->feedURL);

        $GLOBALS['reefless']->loadClass('Rss');
        $GLOBALS['rlRss']->items_number = 300;
        $GLOBALS['rlRss']->createParser($content);
        $rates = $GLOBALS['rlRss']->getRssContent();
        unset($GLOBALS['rlRss']);

        if (empty($rates)) {
            return false;
        }

        // add default usd currency
        if ($insert_mode) {
            $rlDb->query("
                INSERT INTO `" . RL_DBPREFIX . "currency_rate` 
                (`Rate`, `Key`, `Country`, `Date`, `Code`, `Symbol`) 
                VALUES ('1', 'dollar', 'United States', NOW(), 'USD', '$')
            ");
        }

        foreach ($rates as $rate) {
            // get currency code
            preg_match('/.*\=.*\s([a-zA-Z]{3})/', $rate['title'], $code_matches);
            $key = $code = $code_matches[1];

            // get rate
            preg_match('/.*\=\s([0-9\.\,]*)\s(.*)/', $rate['description'], $matches);
            $rate = str_replace(',', '', $matches[1]);
            $country = $matches[2];

            if ($rate && $code) {
                if (!$insert_mode && $rlDb->getOne('ID', "`Code` = '{$code}'", 'currency_rate')) {
                    $rlDb->query("
                        UPDATE `" . RL_DBPREFIX . "currency_rate`
                        SET `Rate` = '{$rate}', `Date` = NOW()
                        WHERE `Code` = '{$code}' LIMIT 1
                    ");
                } else {
                    switch ($code) {
                        case 'EUR':
                            $symbol = '&euro;';
                            $key = 'euro';
                            break;
                        case 'GBP':
                            $symbol = '&pound;';
                            $key = 'pound';
                            break;
                        default:
                            $symbol = $this->getSign($code);
                            $key = $code;
                            break;
                    }

                    $rlDb->query("
                        INSERT INTO `" . RL_DBPREFIX . "currency_rate` 
                        (`Rate`, `Key`, `Country`, `Symbol`, `Date`, `Code`) 
                        VALUES ('{$rate}', '{$key}', '{$country}', '{$symbol}', NOW(), '{$code}')
                    ");
                }
            }
        }

        $this->updateHook();

        return true;
    }
    
    /**
     * Update seoBase hook code
     */
    public function updateHook()
    {
        global $rlDb;

        $rlDb->setTable('currency_rate');
        $rates = $rlDb->fetch(
            array('Code', 'Key', 'Rate', 'Symbol', 'Sticky', 'Status'),
            null,
            "ORDER BY `Sticky` DESC, `Position` ASC"
        );
        
        if (!$rates) {
            return false;
        }
            
        foreach ($rates as $rate) {
            $items .= "'{$rate['Key']}' => array(
                'Rate' => '{$rate['Rate']}',
                'Code' => '{$rate['Code']}',
                'Symbol' => '{$rate['Symbol']}',
                'Sticky' => '{$rate['Sticky']}',
                'Status' => '{$rate['Status']}'
            ),";
        }
        
        $update['fields']['Code'] = str_replace('{rates_items}', rtrim($items, ','), $this->specialBlock);
        $update['where'] = array(
            'Plugin' => 'currencyConverter',
            'Name' => 'seoBase'
        );
        
        $rlDb->rlAllowHTML = true;
        $rlDb->updateOne($update, 'hooks');
        $rlDb->rlAllowHTML = false;
    }
    
    /**
     * Detect default currency and sign
     */
    public function detectCurrency()
    {
        global $rlSmarty, $reefless;

        $currency_code = false;
        $country_code = $_SESSION['GEOLocationData']->Country_code;

        // get currency from cookies
        if ($_COOKIE['curConv_code'] && $this->isActive($_COOKIE['curConv_code'])) {
            $currency_code = $_COOKIE['curConv_code'];
        }
        // get currency by country
        elseif ($country_code) {
            if ($this->isActive($this->mapping[$country_code])) {
                $currency_code = $this->mapping[$country_code];
            } else {
                $system_key = $this->systemCurrency[$this->mapping[$country_code]];
                if ($this->isActive($system_key)) {
                    $currency_code = $system_key;
                }
            }
        }

        // get default currency
        if (!$currency_code) {
            $GLOBALS['rlDb']->outputRowsMap = [false, 'Key'];
            $currencies = $GLOBALS['rlDb']->fetch(
                ['Key'],
                ['Parent_ID' => 1, 'Status' => 'active'],
                "ORDER BY `Default` DESC",
                null,
                'data_formats'
            );

            // Fix not system currency prefix
            foreach ($currencies as $currency_key) {
                if (strpos($currency_key, '_')) {
                    $current_data  = explode('_', $currency_key);
                    $check_code = strtoupper($current_data[1]);
                } else {
                    $check_code = $currency_key;
                }

                if ($this->isActive($check_code)) {
                    $currency_code = $check_code;
                    break;
                } elseif ($this->isActive(strtoupper($check_code))) {
                    $currency_code = strtoupper($check_code);
                    break;
                }
            }
        }

        $rlSmarty->assign_by_ref('curConv_code', $currency_code);

        if (!$_COOKIE['curConv_code'] && $currency_code) {
            if (method_exists($reefless, 'createCookie')) {
                $reefless->createCookie('curConv_code', $currency_code, time()+2678400);
            } else {
                setcookie('curConv_code', $currency_code, time()+2678400, '/');
            }
        }

        // get display sign
        if ($currency_code && $this->isActive($currency_code)) {
            $signs = explode(',', $this->rates[$currency_code]['Symbol']);
            $currency_sign = $signs[0] ? $signs[0] : $currency_code;
        } else {
            $currency_sign = '-//-';
        }

        $rlSmarty->assign_by_ref('curConv_sign', $currency_sign);

        // send sign length to smarty
        $str_func = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
        $rlSmarty->assign_by_ref('sign_length' , $str_func($currency_sign));

        // previous plugin version usage fallback
        $curConv_country['Currency'] = $currency_code;
        $rlSmarty->assign_by_ref('curConv_country', $curConv_country);
    }

    /**
     * Check is the given currency exists and has active status
     *
     * @since 3.2.0
     *
     * @param  string  $code - currency
     * @return boolean
     */
    private function isActive($code = null)
    {
        if (!$code) {
            return false;
        }

        return $this->rates[$code] && $this->rates[$code]['Status'] == 'active';
    }

    /**
     * Set "country code" to "currency code" mapping
     */
    public function setMapping()
    {
        $this->mapping = array(
            'NZ' => 'NZD',
            'CK' => 'NZD',
            'NU' => 'NZD',
            'PN' => 'NZD',
            'TK' => 'NZD',
            'AU' => 'AUD',
            'CX' => 'AUD',
            'CC' => 'AUD',
            'HM' => 'AUD',
            'KI' => 'AUD',
            'NR' => 'AUD',
            'NF' => 'AUD',
            'TV' => 'AUD',
            'AS' => 'EUR',
            'AD' => 'EUR',
            'AT' => 'EUR',
            'BE' => 'EUR',
            'FI' => 'EUR',
            'FR' => 'EUR',
            'GF' => 'EUR',
            'TF' => 'EUR',
            'DE' => 'EUR',
            'GR' => 'EUR',
            'GP' => 'EUR',
            'IE' => 'EUR',
            'IT' => 'EUR',
            'LU' => 'EUR',
            'MQ' => 'EUR',
            'YT' => 'EUR',
            'MC' => 'EUR',
            'NL' => 'EUR',
            'PT' => 'EUR',
            'RE' => 'EUR',
            'WS' => 'EUR',
            'SM' => 'EUR',
            'SI' => 'EUR',
            'ES' => 'EUR',
            'VA' => 'EUR',
            'GS' => 'GBP',
            'GB' => 'GBP',
            'JE' => 'GBP',
            'IO' => 'USD',
            'GU' => 'USD',
            'MH' => 'USD',
            'FM' => 'USD',
            'MP' => 'USD',
            'PW' => 'USD',
            'PR' => 'USD',
            'TC' => 'USD',
            'US' => 'USD',
            'UM' => 'USD',
            'VG' => 'USD',
            'VI' => 'USD',
            'HK' => 'HKD',
            'CA' => 'CAD',
            'JP' => 'JPY',
            'AF' => 'AFN',
            'AL' => 'ALL',
            'DZ' => 'DZD',
            'AI' => 'XCD',
            'AG' => 'XCD',
            'DM' => 'XCD',
            'GD' => 'XCD',
            'MS' => 'XCD',
            'KN' => 'XCD',
            'LC' => 'XCD',
            'VC' => 'XCD',
            'AR' => 'ARS',
            'AM' => 'AMD',
            'AW' => 'ANG',
            'AN' => 'ANG',
            'AZ' => 'AZN',
            'BS' => 'BSD',
            'BH' => 'BHD',
            'BD' => 'BDT',
            'BB' => 'BBD',
            'BY' => 'BYR',
            'BZ' => 'BZD',
            'BJ' => 'XOF',
            'BF' => 'XOF',
            'GW' => 'XOF',
            'CI' => 'XOF',
            'ML' => 'XOF',
            'NE' => 'XOF',
            'SN' => 'XOF',
            'TG' => 'XOF',
            'BM' => 'BMD',
            'BT' => 'INR',
            'IN' => 'INR',
            'BO' => 'BOB',
            'BW' => 'BWP',
            'BV' => 'NOK',
            'NO' => 'NOK',
            'SJ' => 'NOK',
            'BR' => 'BRL',
            'BN' => 'BND',
            'BG' => 'BGN',
            'BI' => 'BIF',
            'KH' => 'KHR',
            'CM' => 'XAF',
            'CF' => 'XAF',
            'TD' => 'XAF',
            'CG' => 'XAF',
            'GQ' => 'XAF',
            'GA' => 'XAF',
            'CV' => 'CVE',
            'KY' => 'KYD',
            'CL' => 'CLP',
            'CN' => 'CNY',
            'CO' => 'COP',
            'KM' => 'KMF',
            'CD' => 'CDF',
            'CR' => 'CRC',
            'HR' => 'HRK',
            'CU' => 'CUP',
            'CY' => 'CYP',
            'CZ' => 'CZK',
            'DK' => 'DKK',
            'FO' => 'DKK',
            'GL' => 'DKK',
            'DJ' => 'DJF',
            'DO' => 'DOP',
            'TP' => 'IDR',
            'ID' => 'IDR',
            'EC' => 'ECS',
            'EG' => 'EGP',
            'SV' => 'SVC',
            'ER' => 'ETB',
            'ET' => 'ETB',
            'EE' => 'EEK',
            'FK' => 'FKP',
            'FJ' => 'FJD',
            'PF' => 'XPF',
            'NC' => 'XPF',
            'WF' => 'XPF',
            'GM' => 'GMD',
            'GE' => 'GEL',
            'GI' => 'GIP',
            'GT' => 'GTQ',
            'GN' => 'GNF',
            'GY' => 'GYD',
            'HT' => 'HTG',
            'HN' => 'HNL',
            'HU' => 'HUF',
            'IS' => 'ISK',
            'IR' => 'IRR',
            'IQ' => 'IQD',
            'IL' => 'ILS',
            'JM' => 'JMD',
            'JO' => 'JOD',
            'KZ' => 'KZT',
            'KE' => 'KES',
            'KP' => 'KPW',
            'KR' => 'KRW',
            'KW' => 'KWD',
            'KG' => 'KGS',
            'LA' => 'LAK',
            'LV' => 'LVL',
            'LB' => 'LBP',
            'LS' => 'LSL',
            'LR' => 'LRD',
            'LY' => 'LYD',
            'LI' => 'CHF',
            'CH' => 'CHF',
            'LT' => 'LTL',
            'MO' => 'MOP',
            'MK' => 'MKD',
            'MG' => 'MGA',
            'MW' => 'MWK',
            'MY' => 'MYR',
            'MV' => 'MVR',
            'MT' => 'MTL',
            'MR' => 'MRO',
            'MU' => 'MUR',
            'MX' => 'MXN',
            'MD' => 'MDL',
            'MN' => 'MNT',
            'MA' => 'MAD',
            'EH' => 'MAD',
            'MZ' => 'MZN',
            'MM' => 'MMK',
            'NA' => 'NAD',
            'NP' => 'NPR',
            'NI' => 'NIO',
            'NG' => 'NGN',
            'OM' => 'OMR',
            'PK' => 'PKR',
            'PA' => 'PAB',
            'PG' => 'PGK',
            'PY' => 'PYG',
            'PE' => 'PEN',
            'PH' => 'PHP',
            'PL' => 'PLN',
            'QA' => 'QAR',
            'RO' => 'RON',
            'RU' => 'RUB',
            'RW' => 'RWF',
            'ST' => 'STD',
            'SA' => 'SAR',
            'SC' => 'SCR',
            'SL' => 'SLL',
            'SG' => 'SGD',
            'SK' => 'SKK',
            'SB' => 'SBD',
            'SO' => 'SOS',
            'ZA' => 'ZAR',
            'LK' => 'LKR',
            'SD' => 'SDG',
            'SR' => 'SRD',
            'SZ' => 'SZL',
            'SE' => 'SEK',
            'SY' => 'SYP',
            'TW' => 'TWD',
            'TJ' => 'TJS',
            'TZ' => 'TZS',
            'TH' => 'THB',
            'TO' => 'TOP',
            'TT' => 'TTD',
            'TN' => 'TND',
            'TR' => 'TRY',
            'TM' => 'TMT',
            'UG' => 'UGX',
            'UA' => 'UAH',
            'AE' => 'AED',
            'UY' => 'UYU',
            'UZ' => 'UZS',
            'VU' => 'VUV',
            'VE' => 'VEF',
            'VN' => 'VND',
            'YE' => 'YER',
            'ZM' => 'ZMK',
            'ZW' => 'ZWD',
            'BA' => 'EUR',
            'ME' => 'EUR'
        );
    }

    /**
     * Get currency sign
     *
     * @since 3.2.0
     *
     * @param  string $code - Currency code
     * @return string       - Currency simbol
     */
    public function getSign($code = '') {
        if (!$code) {
            return '';
        }

        $mapping = [
            'ALL' => 'Lek',
            'AFN' => '؋',
            'AWG' => 'ƒ',
            'AZN' => '₼',
            'BYN' => 'Br',
            'BZD' => 'BZ$',
            'BOB' => '$b',
            'BAM' => 'KM',
            'BWP' => 'P',
            'BRL' => 'R$',
            'KHR' => '៛',
            'CRC' => '₡',
            'HRK' => 'kn',
            'CZK' => 'Kč',
            'DKK' => 'kr',
            'DOP' => 'RD$',
            'GHS' => '¢',
            'GTQ' => 'Q',
            'HNL' => 'L',
            'HUF' => 'Ft',
            'INR' => '₹',
            'IDR' => 'Rp',
            'ILS' => '₪',
            'JMD' => 'J$',
            'JPY' => '¥',
            'KZT' => '₸',
            'KGS' => 'С̲',
            'LAK' => '₭',
            'MKD' => 'ден',
            'MYR' => 'RM',
            'MNT' => '₮',
            'MZN' => 'MT',
            'ANG' => 'ƒ',
            'NIO' => 'C$',
            'NGN' => '₦',
            'PAB' => 'B/.',
            'PYG' => 'Gs',
            'PEN' => 'S/.',
            'PLN' => 'zł',
            'RON' => 'lei',
            'RUB' => '₽',
            'RSD' => 'Дин.',
            'SOS' => 'S',
            'ZAR' => 'R',
            'TWD' => 'NT$',
            'THB' => '฿',
            'TTD' => 'TT$',
            'TRY' => '₺',
            'UAH' => '₴',
            'UYU' => '$U',
            'VEF' => 'Bs',
            'VND' => '₫',
            'ZWD' => 'Z$',
            'TOP' => 'T$',
            'UGX' => 'USh',
            'AOA' => 'Kz'
        ];

        return $mapping[strtoupper($code)];
    }
}

/**
 * Convert HTML entities back to characters
 * 
 * @param  string - Reqested string
 * @return string - Converted characters
 */
function flHtmlEntitiesDecode($string = false)
{
    return html_entity_decode($string, null, 'utf-8');
}
