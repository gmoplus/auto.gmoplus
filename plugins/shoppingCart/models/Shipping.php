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

use \Flynax\Utils\Util;
use \ShoppingCart\Orders;

/**
 * @since 3.0.0
 */
class Shipping
{
    /**
     * Method details
     *
     * @var array
     */
    public $method;

    /**
     * Shipping methods
     *
     * @var array
     */
    public $methods;

    /**
     * Prepare listing fields on add/edit page
     */
    public function prepareDataFields()
    {
        global $rlSmarty, $package_types, $handling_time;

        require_once RL_PLUGINS . 'shoppingCart/static.inc.php';

        $rlSmarty->assign_by_ref('shc_package_type', $package_types);
        $rlSmarty->assign_by_ref('shc_handling_time', $handling_time);

        $this->getMethods(true);
        $is_calculated = false;

        foreach ($this->methods as $key => $val) {
            if ($val['Type'] == 'online') {
                $is_calculated = true;
            }

            $methodClass = '\ShoppingCart\Shipping\\' . ucfirst($val['Key']);

            $instance = new $methodClass();
            $instance->prepareFields();
        }

        $rlSmarty->assign('is_calculated', $is_calculated);
        $rlSmarty->assign('shipping_methods_path', RL_PLUGINS . 'shoppingCart/shipping/');
    }

    /**
     * Get shipping details of listing
     *
     * @param int $listing_id
     * @return array
     */
    public function getItem($listing_id = 0)
    {
        if (!$listing_id) {
            return array();
        }

        return $GLOBALS['rlDb']->fetch(
            '*',
            array('Listing_ID' => $listing_id),
            null,
            1,
            'shc_listing_options',
            'row'
        );
    }

    /**
     * Get method details
     *
     * @param string $key
     * @return array
     */
    public function getMethod($key = '')
    {
        if (!$this->method) {
            $sql = "SELECT * FROM `{db_prefix}shc_shipping_methods` WHERE `Key` = '{$key}' LIMIT 1";

            $this->method = $GLOBALS['rlDb']->getRow($sql);

            if ($this->method) {
                $this->method['name'] = $GLOBALS['lang']['shipping_methods+name+' . $key];
            }
        }

        return $this->method;
    }

    /**
     * Get all methods
     *
     * @param boolean $output
     * @return array
     */
    public function getMethods($output = false)
    {
        if (!$this->methods) {
            $sql = "SELECT * FROM `{db_prefix}shc_shipping_methods` WHERE `Status` = 'active' ORDER BY `ID` DESC";
            $this->methods = $GLOBALS['rlDb']->getAll($sql);

            if ($this->methods) {
                foreach ($this->methods as $mKey => $mValue) {
                    $this->methods[$mKey]['name'] = $GLOBALS['lang']['shipping_methods+name+' . $mValue['Key']];
                }
            }
        }

        if ($output) {
            $GLOBALS['rlSmarty']->assign_by_ref('shipping_methods', $this->methods);
            return;
        }

        return $this->methods;
    }

    /**
     * Get country code by name
     *
     * @param string $country
     * @return string
     */
    public static function getCountryCode($country = '')
    {
        if (!$country) {
            return null;
        }

        $countries = Util::getCountries();

        $code = false;
        $country = str_replace("_", "", $country);

        foreach ($countries as $key => $val) {
            if (strtolower($country) == strtolower($val) || $country == $key) {
                $code = trim($key);
                break;
            }
        }

        return $code;
    }

    /**
     * Save Tracking Number
     *
     * @param int $item_id
     * @param string $tracking_number
     */
    public function saveTrackingNumber($item_id = 0, $tracking_number = '')
    {
        global $rlDb;

        if (!$item_id || !$tracking_number) {
            return;
        }

        $update = array(
            'fields' => array(
                'Tracking_number' => $tracking_number,
            ),
            'where' => array('ID' => $item_id),

        );
        return $GLOBALS['rlDb']->updateOne($update, 'shc_orders');
    }

    /**
     * Change shipping status
     *
     * @param int $item_id
     * @param string $status
     */
    public function changeStatus($item_id = 0, $status = '')
    {
        if (!$item_id || !$status) {
            return false;
        }

        $orders = new Orders();
        $order_info = $orders->get((int) $item_id);

        if (!$order_info) {
            return false;
        }

        $update = array(
            'fields' => array(
                'Shipping_status' => $status,
            ),
            'where' => array(
                'ID' => $item_id,
            ),
        );

        if ($GLOBALS['rlDb']->updateOne($update, 'shc_orders')) {
            // send notification to buyer
            $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('shc_shipping_status_changed');
            $statusPhrase = $GLOBALS['rlLang']->getSystem($status == 'pending' ?: 'shc_' . $status);

            $find = array('{buyer}', '{order_key}', '{status}');
            $replace = array($order_info['bFull_name'], $order_info['Order_key'], $statusPhrase);

            $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
            $GLOBALS['rlMail']->send($mail_tpl, $order_info['bMail']);

            return true;
        }

        return false;
    }

    /**
     * Convert value of price
     *
     * @param array $quote
     * @return double
     */
    public static function convert($quote = array())
    {
        if (!$quote) {
            return 0;
        }

        if (!$quote['currency'] || !$quote['total']) {
            return 0;
        }

        if ($GLOBALS['shcRates']) {
            $symbol = trim($GLOBALS['shcRates'][$quote['currency']]['Symbol']);
            if ($GLOBALS['config']['system_currency'] != $quote['currency']
                && $GLOBALS['config']['system_currency'] != $symbol
            ) {
                $rate = (float) $GLOBALS['shcRates'][$quote['currency']]['Rate'];
                $new_total = round($total * $rate, 2);

                return $new_total;
            }
        }

        return $quote['total'];
    }

    /**
     * Get shipping fields
     *
     * @return array
     */
    public function getShippingFields($output = false)
    {
        global $rlDb, $lang, $rlSmarty, $config;

        $sql = "SELECT `T1`.* ";
        $sql .= "FROM `{db_prefix}shc_shipping_fields` AS `T1` ";
        $sql .= "WHERE `T1`.`Status` = 'active' ";

        $data = $rlDb->getAll($sql, 'ID');
        $fields = array();

        if ($data) {
            $data = $GLOBALS['rlLang']->replaceLangKeys($data, 'shc_shipping_fields', array('name', 'default'));
            $relations = $rlDb->fetch('*', null, "ORDER BY `Position` ASC", null, 'shc_shipping_form');

            $index = key($data);
            foreach ($relations as $k => $v) {
                if (isset($data[$v['Field_ID']])) {
                    $data[$v['Field_ID']]['pName'] = 'shc_shipping_fields+name+' . $data[$v['Field_ID']]['Key'];
                    $data[$v['Field_ID']]['pDescription'] = 'shc_shipping_fields+description+' . $data[$v['Field_ID']]['Key'];

                    if ($config['shc_use_multifield'] && !$config['shc_shipping_calc'] && $v['Field_ID'] == $index) {
                        $data[$index]['Key'] = substr($data[$index]['Key'], 0, strrpos($data[$index]['Key'], '_'));
                    }

                    $fields[] = $data[$v['Field_ID']];
                }
            }
        }

        if ($fields) {
            $fields = $GLOBALS['rlCommon']->fieldValuesAdaptation($fields, 'shc_shipping_fields');

            if (!$config['shc_use_multifield'] || $config['shc_shipping_calc']) {
                $tmp = Util::getCountries();

                foreach ($tmp as $key => $value) {
                    $countries[] = [
                        'Key' => $key,
                        'Default' => 0,
                        'name' => $value,
                    ];
                }

                foreach ($fields as $key => $value) {
                    if ($value['Key'] == 'location_level1') {
                        $fields[$key]['Condition'] = 'years';
                        $fields[$key]['Values'] = $countries;
                    }
                }
            }
        }

        if ($output) {
            $rlSmarty->assign_by_ref('shcShippingfields', $fields);

            return [];
        }

        return $fields;
    }

    /**
     * Initialize shipping option
     */
    public function init()
    {
        $this->getShippingfields(true);
        $this->getMethods(true);

        foreach ($this->methods as $key => $val) {
            $methodClass = '\ShoppingCart\Shipping\\' . ucfirst($val['Key']);

            $instance = new $methodClass();
            $instance->prepareFields();
        }
    }

    /**
     * Simulate shipping post
     *
     * @param array $orderInfo
     * @param array $cartItems
     */
    public function simulatePost($orderInfo = [], $cartItems = [])
    {
        global $reefless;

        if (!$orderInfo) {
            return;
        }

        $fields = $this->getShippingfields();

        if (!is_array($orderInfo['Shipping_options'])) {
            $orderInfo['Shipping_options'] = unserialize($orderInfo['Shipping_options']);
        }
        $data = $orderInfo['Shipping_options']['fields'];

        foreach ($fields as &$field) {
            if ($data[$field['Key']] == '') {
                continue;
            }

            switch ($field['Type']) {
                case 'mixed':
                    $value = false;
                    $value = explode('|', $data[$field['Key']]);

                    $_POST['f'][$field['Key']]['value'] = $value[0];
                    $_POST['f'][$field['Key']]['df'] = $value[1];
                    break;

                case 'phone':
                    $_POST['f'][$field['Key']] = $reefless->parsePhone($data[$field['Key']]);
                    break;

                case 'unit':
                    $unit = false;
                    $unit = explode('|', $data[$field['Key']]);

                    $_POST['f'][$field['Key']]['value'] = $unit[0];
                    $_POST['f'][$field['Key']]['unit'] = $unit[1];
                    break;

                case 'checkbox':
                    $ch_items = null;
                    $ch_items = explode(',', $data[$field['Key']]);

                    $_POST['f'][$field['Key']] = $ch_items;
                    unset($ch_items);
                    break;

                case 'text':
                    if ($field['Multilingual'] && count($GLOBALS['languages']) > 1) {
                        $_POST['f'][$field['Key']] = $reefless->parseMultilingual($data[$field['Key']]);
                    } else {
                        $_POST['f'][$field['Key']] = $data[$field['Key']];
                    }
                    break;

                default:
                    $_POST['f'][$field['Key']] = $data[$field['Key']];
                    break;
            }
        }

        if ($cartItems) {
            foreach ($cartItems as $key => $item) {
                $_method = $item['shipping_item_options']['method'];
                $_POST['items'][$item['ID']]['fixed_index'] = $item['shipping_item_options']['fixed_index'];
                $_POST['items'][$item['ID']]['shipping_method_fixed'] = $_method;
                $_POST['items'][$item['ID']][$_method]['service'] = $item['shipping_item_options']['service'];
                $_POST['items'][$item['ID']][$_method]['total'] = $item['shipping_item_options']['total'];

                if ($_method == 'usps') {
                    $_POST['items'][$item['ID']][$_method]['domestic_services'] = $item['shipping_item_options']['domestic_services'];
                    $_POST['items'][$item['ID']][$_method]['international_services'] = $item['shipping_item_options']['international_services'];
                }
            }
        }
    }

    /**
     * Prepare quote data
     *
     * @param array $method
     * @param array $form
     * @param array $item
     * @param array $listing
     * @return array;
     */
    public function prepareQuoteData($method = '', $form = [], $item = [], $listing = [])
    {
        global $config, $account_info, $rlDb;

        $phoneField = $GLOBALS['rlDb']->getRow("SELECT * FROM `{db_prefix}account_fields` WHERE `Key` = 'phone'");

        $dealerID = $item['Dealer_ID'];
        $dealerInfo = $GLOBALS['rlAccount']->getProfile((int) $dealerID, true);
        if ($config['shc_method'] == 'multi') {
            $accountOptions = $GLOBALS['rlShoppingCart']->getAccountOptions($dealerID);

            if ($accountOptions) {
                $location = unserialize($accountOptions['Shipping']);
            }

            // adapt account phone
            if ($phoneField) {
                $phone = $GLOBALS['reefless']->parsePhone($dealerInfo['phone'], $phoneField);
            }
            $phone = str_replace(array('+', '-', '(', ')', ' '), '', $phone);

            $shipper = [
                'country' => $location['location_level1'],
                'state' => $location['location_level2'],
                'city' => $location['location_level3'],
                'zip' => $location['zip'],
                'address' => $location['address'],
                'name' => $dealerInfo['Full_name'],
                'phone' => $phone,
            ];
        } else {
            $shipperAddress = $rlDb->getOne('Values', "`Key` = 'shc_shipper_address'", 'config');
            $location = unserialize($shipperAddress);

            $shipper = [
                'country' => $location['location_level1'],
                'state' => $location['location_level2'],
                'city' => $location['location_level3'],
                'zip' => $location['zip'],
                'name' => $dealerInfo['Full_name'],
                'address' => $location['address'],
                'phone' => '',
            ];
        }

        $data = $form['f'];
        $methodInfo = $form['items'][$item['ID']][$method];
        // adapt account phone
        if ($phoneField) {
            $phoneTo = $GLOBALS['reefless']->parsePhone($account_info['phone'], $phoneField);
        }
        $phoneTo = str_replace(array('+', '-', '(', ')', ' '), '', $phoneTo);

        // shipping request details
        $preparedData = array(
            // shipper location
            'country_from' => $shipper['country'],
            'city_from' => $shipper['city'],
            'state_from' => $shipper['state'],
            'postcode_from' => $shipper['zip'],
            'name_from' => $shipper['name'],
            'phone_from' => $shipper['phone'],
            'address_from' => $shipper['address'],
            'shipper_name' => $shipper['name'],

            // recipient location
            'country_to' => $data['location_level1'],
            'city_to' => $data['location_level3'],
            'state_to' => $data['location_level2'],
            'postcode_to' => $data['zip'],
            'company_to' => $account_info['company_name'],
            'phone_to' => $phoneTo,
            'address_to' => $data['address'],
            'recipient_name' => $account_info['Full_name'],

            'width' => $listing['Dimensions']['width'],
            'height' => $listing['Dimensions']['height'],
            'length' => $listing['Dimensions']['length'],
            'weight' => (float) $listing['Weight'],
            'total' => round($item['Price'] * $item['Quantity'], 2),
            'service' => $methodInfo['service'],
            'international_services' => $methodInfo['international_services'],
            'domestic_services' => $methodInfo['domestic_services'],
            'package_type' => $listing['Package_type'],
            'shipping' => $listing['Shipping_options'],
        );

        return $preparedData;
    }

    /**
     * Add quote to item
     *
     * @param array $quote
     * @param int $itemID
     */
    public function addQuote($quote = [], $itemID = 0)
    {
        $update = array(
            'fields' => array(
                'Quote' => serialize($quote),
            ),
            'where' => array(
                'ID' => $itemID,
            ),
        );

        $GLOBALS['rlDb']->updateOne($update, 'shc_order_details', ['Quote']);
    }

    /**
     * Upload shipping files
     *
     * @param array $fields
     * @param array $item
     * @return array
     */
    public function uploadFiles(array $fields, array $item = []) : array
    {
        if (!$fields) {
            return [];
        }

        $result = [];

        foreach ($fields as $field) {
            if ($field['Type'] == 'file') {
                $file_name = 'shc_file_' . time() . mt_rand();
                $file_name = $GLOBALS['rlActions']->upload('file', $file_name, false, false, '', false);

                if ($file_name) {
                    if ($item[$field['Key']] && file_exists(RL_FILES . $item[$field['Key']])) {
                        unlink(RL_FILES . $item[$field['Key']]);
                    }

                    $dir = RL_FILES . 'shc-files' . RL_DS . date('m-Y') . RL_DS;
                    $dir_name = 'shc-files/' . date('m-Y') . '/';
                    $GLOBALS['reefless']->rlMkdir($dir);
                    rename(RL_FILES . $file_name, $dir . $file_name);
                    $result[$field['Key']] = $dir_name . $file_name;
                }
            }
        }

        return $result;
    }
}
