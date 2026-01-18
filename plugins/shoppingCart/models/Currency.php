<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: MY_ITEMS_SOLD.INC.PHP
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

namespace ShoppingCart;

/**
 * @since 3.0.0
 */
class Currency
{
    /**
     * Default price data entry prefix
     *
     * @var string
     */
    private static $currencyDataEntryPrefix = 'currency_';

    /**
     * System currencies mapping, "Currency code" to "Currency key"
     *
     * @var array
     */
    public static $systemCurrency = [
        'dollar' => 'USD',
        'pound' => 'GBP',
        'euro' => 'EUR',
    ];

    /**
     * Update/insert currency rates
     */
    public function importRates()
    {
        $feedURL = 'http://www.floatrates.com/daily/usd.xml';

        $content = $GLOBALS['reefless']->getPageContent($feedURL);

        $GLOBALS['reefless']->loadClass('Rss');
        $GLOBALS['rlRss']->items_number = 300;
        $GLOBALS['rlRss']->createParser($content);
        $rates = $GLOBALS['rlRss']->getRssContent();
        unset($GLOBALS['rlRss']);

        if (empty($rates)) {
            return false;
        }

        // add default usd currency
        $data['USD'] = [
            'Rate' => 1,
            'Code' => 'USD',
        ];

        foreach ($rates as $rate) {
            // get currency code
            preg_match('/.*\=.*\s([a-zA-Z]{3})/', $rate['title'], $code_matches);
            $code = $code_matches[1];

            // get rate
            preg_match('/.*\=\s([0-9\.\,]*)\s(.*)/', $rate['description'], $matches);
            $rate = str_replace(',', '', $matches[1]);
            $country = $matches[2];

            if ($rate && $code) {
                $data[$code] = [
                    'Rate' => $rate,
                    'Code' => $code,
                ];
            }
        }

        $data = json_encode($data);
        file_put_contents(RL_PLUGINS . 'shoppingCart/currencies.json', $data);
    }

    /**
     * Convert exists price to system currency
     *
     * @param array $listings
     */
    public function convertExistsPrice($listings = [])
    {
        global $config, $rlDb;

        $priceField = $config['price_tag_field'];
        $code = $config['system_currency_code'];

        $rates = $this->getRates();

        if (in_array($code, array_values(self::$systemCurrency))) {
            foreach (self::$systemCurrency as $key => $val) {
                if ($val == $code) {
                    $newCurrency = $key;
                    break;
                }
            }
        } else {
            $newCurrency = self::$currencyDataEntryPrefix . $code;
        }

        if ($listings) {
            foreach ($listings as $listing) {
                $temp = explode('|', $listing[$priceField]);
                $price = $temp[0];
                $currency = $temp[1];

                if ($code != self::$systemCurrency[$currency] && $currency != self::$currencyDataEntryPrefix . strtolower($code)) {
                    if (isset(self::$systemCurrency[$currency])) {
                        $rate = self::$systemCurrency[$currency];
                    }
                    if (preg_match('/' . self::$currencyDataEntryPrefix . '/', $currency, $matches)) {
                        $rate = strtoupper(str_replace(self::$currencyDataEntryPrefix, '', $currency));
                    }
                    $usdPrice = $rates[$rate]['Rate'] > 0 ? $price / $rates[$rate]['Rate'] : $price;
                    $newPrice = number_format($usdPrice * $rates[$code]['Rate'], 2, '.', '');

                    $update = [
                        'fields' => [$priceField => $newPrice . '|' . $newCurrency],
                        'where' => ['ID' => $listing['ID']],
                    ];
                    $rlDb->updateOne($update, 'listings');
                }
            }
        }
    }

    /**
     * Get system currency key
     */
    public function getSystemCurrencyKey()
    {
        $defaultKey = '';
        $code = $GLOBALS['config']['system_currency_code'];

        if (in_array($code, array_values(self::$systemCurrency))) {
            foreach (self::$systemCurrency as $key => $val) {
                if ($val == $code) {
                    $defaultKey = $key;
                    break;
                }
            }
        } else {
            $defaultKey = self::$currencyDataEntryPrefix . $code;
        }

        return $defaultKey;
    }

    /**
     * Convert single price to system currency
     *
     * @param string $priceValue
     * @param string $currency
     * @return double
     */
    public function convertPrice($priceValue = '', $currency = '')
    {
        global $config, $rlDb;

        $priceField = $config['price_tag_field'];
        $code = $config['system_currency_code'];

        $rates = $this->getRates();

        $temp = explode('|', $priceValue);
        $price = $temp[0];
        $currency = $currency ?: $temp[1];

        $currency = 0 === strpos($currency, self::$currencyDataEntryPrefix)
            ? strtoupper(str_replace(self::$currencyDataEntryPrefix, '', $currency))
            : $currency;

        if (isset(self::$systemCurrency[$currency])) {
            $currency = self::$systemCurrency[$currency];
        }

        if ($code != self::$systemCurrency[$currency]
            && $currency != self::$currencyDataEntryPrefix . strtolower($code)
            && $rates[$currency]['Rate'] > 0
        ) {
            $usdPrice = $rates[$currency]['Rate'] > 0 ? $price / $rates[$currency]['Rate'] : $price;
            $newPrice = number_format($usdPrice * $rates[$code]['Rate'], 2, '.', '');
        } else {
            $newPrice = $price;
        }

        return $newPrice;
    }

    /**
     * Get rates
     *
     * @return array
     */
    public function getRates()
    {
        global $plugins;

        if (!$plugins) {
            $plugins = $GLOBALS['rlCommon']->getInstalledPluginsList();
        }

        if ($plugins['currencyConverter']) {
            $sql = "SELECT * FROM `{db_prefix}currency_rate` ";
            $rates = $GLOBALS['rlDb']->getAll($sql, 'Code');
        } else {
            $url = RL_PLUGINS_URL . 'shoppingCart/currencies.json';

            $rates = $GLOBALS['reefless']->getPageContent($url);
            $rates = json_decode($rates, true);
        }

        return $rates;
    }

    /**
     * Add system currency to currencies data format if not exists
     *
     * @since 3.1.1
     */
    public function addSystemCurrency()
    {
        global $config, $rlDb, $languages, $rlLang;

        if (!$languages) {
            $languages = $rlLang->getLanguagesList();
            $rlLang->modifyLanguagesList($languages);
        }

        $GLOBALS['reefless']->loadClass('Categories');
        $cList = $GLOBALS['rlCategories']->getDF('currency');

        $systemCurrency = [
            'dollar' => 'USD',
            'pound' => 'GBP',
            'euro' => 'EUR',
        ];

        $exists = false;
        $parentID = $cList[0]['Parent_ID'];
        $code = strtoupper($config['system_currency_code']);
        $iKey = 'currency_' . $code;

        foreach ($cList as $key => $val) {
            if ($systemCurrency[$val['Key']] == $code || preg_match('/' . $code . '/', $val['Key'])) {
                $exists = true;
            }
        }

        if (!$exists) {
            $dataFormat = array(
                'Parent_ID' => $parentID,
                'Key' => $iKey,
            );

            $rlDb->insertOne($dataFormat, 'data_formats');

            foreach ($languages as $lKey => $lValue) {
                $insert = array(
                    'Code' => $lValue['Code'],
                    'Module' => 'common',
                    'Key' => 'data_formats+name+' . $iKey,
                    'Value' => $config['system_currency'] ?: $code,
                );

                $rlDb->insertOne($insert, 'lang_keys');
            }

            $GLOBALS['rlCache']->updateDataFormats();
        }
    }
}
