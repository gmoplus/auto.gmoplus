<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : UPS.PHP
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
use ShoppingCart\XMLParser;

class Ups extends ShippingMethod
{
    /**
     * Initialize method information
     */
    public function init()
    {
        $shipping = new Shipping();
        $this->methodInfo = $shipping->getMethod('ups');

        if ($this->methodInfo['Settings']) {
            $this->methodInfo['Settings'] = unserialize($this->methodInfo['Settings']);
        }

        $host = $this->methodInfo['Test_mode']
        ? 'https://wwwcie.ups.com/webservices/Rate'
        : 'https://www.ups.com/ups.app/xml/Rate';

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

        $packageType = '';

        switch ($request['package_type']) {
            case 'type_letter':
                $packageType = '01';
                break;
            case 'large_envelope':
                $packageType = '';
                break;
            case 'package':
                $packageType = '02';
                break;
            case 'large_package':
                $packageType = '2c';
                break;
            case 'irregular_package':
                $packageType = '00';
                break;

            default:
                $packageType = $method['package_types']['value'];
                break;
        }

        self::loadStaticData();
        $serviceName = $GLOBALS['upsServices'][$request['service']]['name'];
        $packageTypeName = $GLOBALS['upsPackagingItems'][$packageType];

        $data = [
            'ups_userid' => $method['username']['value'],
            'ups_password' => $method['password']['value'],
            'ups_key' => $method['api_key']['value'],
            'item_name' => $item['Item'] ?: $item['listing_title'],
            'shipper_name' => $request['shipper_name'],
            'shipper_number' => '',
            'address_from' => $request['address_from'],
            'city_from' => $request['city_from'],
            'state_from' => $request['state_from'],
            'postcode_from' => $request['postcode_from'],
            'country_from' => $request['country_from'],
            'recipient_name' => $request['recipient_name'],
            'address_to' => $request['address_to'],
            'city_to' => $request['city_to'],
            'state_to' => $request['state_to'],
            'postcode_to' => $request['postcode_to'],
            'country_to' => $request['country_to'],
            'service' => $request['service'],
            'service_name' => $serviceName,
            'packaging' => $packageType,
            'packaging_name' => $packageTypeName,
            'length_code' => strtoupper($method['length_type']['value']),
            'length_code_name' => '',
            'length' => $request['length'],
            'width' => $request['width'],
            'height' => $request['height'],
            'weight_units' => strtoupper($GLOBALS['config']['shc_weight_unit']),
            'weight_units_name' => '',
            'weight' => $request['weight'],
            'currency' => 'USD',
            'total' => $request['total'],
        ];

        // set request options
        $rlSmarty->assign_by_ref('request', $data);
        $xmlRequest = $rlSmarty->fetch(RL_PLUGINS . 'shoppingCart/shipping/ups/view/request.tpl', null, null, false);

        $xml = $this->post($xmlRequest);

        if ($this->methodInfo['Test_mode']) {
            $log = sprintf("\n%s:\n%s\n", date('Y.m.d H:i:s'), $xml);
            file_put_contents(RL_PLUGINS . 'shoppingCart/shipping/ups/response.log', $log, FILE_APPEND);
        }

        $response = XMLParser::parse($xml);
        $status = $response['soapenv:Envelope']['soapenv:Body']['rate:RateResponse']['common:Response']['common:ResponseStatus']['common:Code'];
        $rate = $response['soapenv:Envelope']['soapenv:Body']['rate:RateResponse']['rate:RatedShipment']['rate:TotalCharges'];

        if ($status == 1) {
            $quote = array(
                'service' => $request['service'],
                'title' => $serviceName,
                'total' => (float) $rate['rate:MonetaryValue'],
                'currency' => 'USD',
                'delivered' => '',
            );
        } else {
            $quote['error'] = $GLOBALS['rlLang']->getSystem('shc_selected_service_unavailable');
            $log = sprintf("\n%s:\n%s\n", date('Y.m.d H:i:s'), $xml);
            file_put_contents(RL_PLUGINS . 'shoppingCart/shipping/ups/response.log', $log, FILE_APPEND);
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
            && $this->methodInfo['Settings']['username']['value']
            && $this->methodInfo['Settings']['password']['value']
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
        global $rlSmarty, $upsPickupMethods, $upsPackagingItems,
        $upsOrigins, $upsServices, $upsQuoteTypes, $upsClassifications;

        require_once RL_PLUGINS . 'shoppingCart/shipping/ups/static.inc.php';

        if ($rlSmarty) {
            $rlSmarty->assign_by_ref('shc_ups_pickup_methods', $upsPickupMethods);
            $rlSmarty->assign_by_ref('shc_ups_package_types', $upsPackagingItems);
            $rlSmarty->assign_by_ref('ups_origins', $upsOrigins);
            $rlSmarty->assign_by_ref('shc_ups_services', $upsServices);
            $rlSmarty->assign_by_ref('ups_quote_type', $upsQuoteTypes);
            $rlSmarty->assign_by_ref('ups_classification', $upsClassifications);
        }
    }
}
