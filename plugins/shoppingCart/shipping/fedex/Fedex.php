<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : FEDEX.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2023
 *	https://www.flynax.com
 *
 ******************************************************************************/

namespace ShoppingCart\Shipping;

use ShoppingCart\Shipping;

class Fedex extends ShippingMethod
{
    /**
     * Initialize method information
     */
    public function init()
    {
        $shipping = new Shipping();
        $this->methodInfo = $shipping->getMethod('fedex');

        if ($this->methodInfo['Settings']) {
            $this->methodInfo['Settings'] = unserialize($this->methodInfo['Settings']);
        }

        $host = $this->methodInfo['Test_mode']
        ? 'https://wsbeta.fedex.com:443/web-services'
        : 'https://gateway.fedex.com/web-services/';

        $this->setAPIHost($host);
        $this->loadStaticData();
    }

    /**
     * Get quote
     *
     * @param array $request
     * @param array $item
     *
     * @return array
     */
    public function getQuote($request = array(), $item = array())
    {
        global $rlSmarty;

        $quote = array();

        if (!$this->getMethod()) {
            return $quote;
        }

        $method = $this->getMethod()['Settings'];

        $data = [
            'api_key' => $method['api_key']['value'],
            'password' => $method['password']['value'],
            'account' => $method['account']['value'],
            'meter' => $method['meter']['value'],
            'dropoff_type' => strtoupper($request['shipping']['fedex']['dropoff_type']),
            'rate_type' => strtoupper($method['rate_type']['value']),
            'packaging_type' => strtoupper($request['shipping']['fedex']['packaging_type']),
            'service' => strtoupper($request['service']),
            'shipper_name' => $request['shipper_name'],
            'address_from' => $request['address_from'],
            'city_from' => strtoupper($request['city_from']),
            'state_from' => strtoupper($request['state_from']),
            'postcode_from' => strtoupper($request['postcode_from']),
            'country_from' => strtoupper($request['country_from']),
            'phone_from' => $request['phone_from'],
            'recipient_name' => $request['recipient_name'],
            'address_to' => $request['address_to'],
            'city_to' => strtoupper($request['city_to']),
            'state_to' => strtoupper($request['state_to']),
            'postcode_to' => ($request['postcode_to']),
            'country_to' => strtoupper($request['country_to']),
            'phone_to' => $request['phone_to'],
            'length_code' => strtoupper($method['length_type']['value']),
            'length' => $request['length'],
            'width' => $request['width'],
            'height' => $request['height'],
            'weight_units' => $GLOBALS['config']['shc_weight_unit'] == 'lbs' ? 'LB' : 'KG',
            'weight_units_name' => '',
            'weight' => $request['weight'],
            'currency' => 'USD',
            'total' => $request['total'],
            'txn_id' => rand(100000, 999999),
        ];
        $rlSmarty->assign_by_ref('request', $data);
        $xmlRequest = $rlSmarty->fetch(RL_PLUGINS . 'shoppingCart/shipping/fedex/view/request.tpl', null, null, false);

        $xml = $this->post($xmlRequest);

        if ($this->methodInfo['Test_mode']) {
            $log = sprintf("\n%s:\n%s\n", date('Y.m.d H:i:s'), $xml);
            file_put_contents(RL_PLUGINS . 'shoppingCart/shipping/fedex/response.log', $log, FILE_APPEND);
        }

        $response = $this->parseSOAP($xml, 'SOAP-ENV:Body');

        if (!$response) {
            $response = $this->parseSOAP($xml, 'SOAP-ENV:Fault');
        }

        $rKeys = array_keys($response);
        $prefix = '';
        if (preg_match('/RateReply/i', $rKeys[0] )) {
            $prefix = $rKeys[0] == 'RateReply' ? '' : str_replace('RateReply', '', $rKeys[0]);

            if ($prefix != '') {
                $quote['error'] = $response[$prefix . 'RateReply'][$prefix . 'Notifications'][$prefix . 'Message'];
                return $quote;
            }
        }

        $status = $response['RateReply']['HighestSeverity'];

        if (strtoupper($status) == 'SUCCESS' || strtoupper($status) == 'WARNING') {
            $rate = $response['RateReply']['RateReplyDetails']['RatedShipmentDetails'][0]['ShipmentRateDetail']['TotalBaseCharge'];
            $names = $response['RateReply']['RateReplyDetails']['ServiceDescription']['Names'];
            $title = '';

            foreach ($names as $key => $value) {
                if ($value['Type'] == 'long' && $value['Encoding'] == 'ascii') {
                    $title = $value['Value'];
                    break;
                }
            }
            $quote = array(
                'service' => $response['RateReply']['RateReplyDetails']['ServiceType'],
                'title' => $title,
                'total' => (float) $rate['Amount'],
                'currency' => $rate['Currency'],
                'delivered' => '',
            );
        } elseif (strtoupper($status) == 'ERROR' || strtoupper($status) == 'FAILURE') {
            $errors = $response['RateReply']['Notifications'];
            if (isset($errors['Message'])) {
                $quote['error'] = $errors['Message'];
            } else {
                foreach ($errors as $key => $value) {
                    $quote['error'] .= $value['Message'] . '<br>';
                }
            }
        } else {
            $quote['error'] = $response['detail']['desc'];
        }

        return $quote;
    }

    /**
     * Prepare data of form fields
     */
    public function prepareFields()
    {
        self::loadStaticData();
    }

    /**
     * Check if configured this method
     *
     * @return bool
     */
    public function isConfigured()
    {
        if ($this->methodInfo['Settings']['api_key']['value']
            && $this->methodInfo['Settings']['account']['value']
            && $this->methodInfo['Settings']['password']['value']
            && $this->methodInfo['Settings']['meter']['value']
        ) {
            return true;
        }

        return false;
    }

    /**
     * Load static data
     */
    public static function loadStaticData()
    {
        global $rlSmarty, $fedexServices, $fedexDropoffTypes, $fedexPackagingTypes;

        require_once RL_PLUGINS . 'shoppingCart/shipping/fedex/static.inc.php';

        if ($rlSmarty) {
            $rlSmarty->assign_by_ref('shc_fedex_services', $fedexServices);
            $rlSmarty->assign_by_ref('shc_fedex_dropoff_types', $fedexDropoffTypes);
            $rlSmarty->assign_by_ref('shc_fedex_packaging_types', $fedexPackagingTypes);
        }
    }
}
