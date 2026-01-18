<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : DHL.PHP
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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use ShoppingCart\Shipping;

/**
 * @since 3.1.0
 */
class Dhl extends ShippingMethod
{
    /**
     * DHL access token
     *
     * @var string
     */
    protected $headers = [];

    /**
     * API Headers
     *
     * @var array
     */
    protected $token = [];

    /**
     * Initialize method information
     */
    public function init()
    {
        $shipping = new Shipping();
        $this->methodInfo = $shipping->getMethod('dhl');

        if ($this->methodInfo['Settings']) {
            $this->methodInfo['Settings'] = unserialize($this->methodInfo['Settings']);
        }

        $host = $this->methodInfo['Test_mode']
        ? 'https://api-sandbox.dhlecommerce.com'
        : 'https://xmlpi-ea.dhl.com/XMLShippingServlet';

        $this->setAPIHost($host);
        self::loadStaticData();
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
        $quote = array();

        if (!$this->getMethod()) {
            return $quote;
        }

        $method = $this->getMethod()['Settings'];
        $query = '/flc/v1/quote';

        $data = [
            'pickupAccount' => $method['pickup_account']['value'],
            'itemSeller' => $request['shipper_name'],
            'pricingStrategy' => $request['shipping']['hsCode'] ? 'EXACT' : 'MINIMUM',
            'senderAddress' => [
                'state' => strtoupper($request['state_from']),
                'country' => strtoupper($request['country_from']),
            ],
            'consigneeAddress' => [
                'country' => strtoupper($request['country_to']),
            ],
            'outputCurrency' => 'USD',
            'clearanceMode' => 'COURIER',
            'endUse' => 'PERSONAL',
            'transportMode' => strtoupper($request['shipping']['transport_mode']),
            'customsDetails' => [
                'itemId' => 'ITEM_1' . $item['Item_ID'],
                'hsCode' => $request['shipping']['hsCode'],
            ],
            'width' => [
                'value' => $request['width'],
                'unit' => strtoupper($method['length_type']['value']),
            ],
            'length' => [
                'value' => $request['length'],
                'unit' => strtoupper($method['length_type']['value']),
            ],
            'height' => [
                'value' => $request['height'],
                'unit' => strtoupper($method['length_type']['value']),
            ],
            'weight' => [
                'value' => $request['weight'],
                'unit' => strtoupper($GLOBALS['config']['shc_weight_unit']),
            ],

        ];
        $response = $this->callApi($query, 'POST', $data);

        if ($this->methodInfo['Test_mode']) {
            $log = sprintf("\n%s:\n%s\n", date('Y.m.d H:i:s'), print_r($response, true));
            file_put_contents(RL_PLUGINS . 'shoppingCart/shipping/dhl/response.log', $log, FILE_APPEND);
        }

        if ($response['quoteID']) {
            $quote = array(
                'service' => 'DHL',
                'title' => $response['feeTotals']['name'],
                'total' => (float) $response['feeTotals']['value'],
                'currency' => 'USD',
            );
        } else {
            $quote['error'] = $response->message;
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
        if ($this->methodInfo['Settings']['client_id']['value'] && $this->methodInfo['Settings']['client_secret']['value']) {
            return true;
        }

        return false;
    }

    /**
     * Authorization
     *
     * @return bool
     */
    public function login()
    {
        $query = '/account/v1/auth/accesstoken';

        $encoded = base64_decode($this->methodInfo['Settings']['client_id']['value'] . ':' . $this->methodInfo['Settings']['client_secret']['value']);

        $this->headers = array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => "Basic {$encoded}",
        );

        if (!$this->isTokenExpired()) {
            $this->getToken();
            return true;
        }

        $response = $this->callApi($query, 'GET');

        if ($response->body['access_token']) {
            $this->token = $response->body['access_token'];
            $this->saveToken($response->body['expires_in']);
            return true;
        } else {
            $log = sprintf("\n%s:\n%s\n", date('Y.m.d H:i:s'), print_r($response->body, true));
            file_put_contents(RL_PLUGINS . 'shoppingCart/shipping/dhl/response.log', $log, FILE_APPEND);
        }

        return false;
    }

    /**
     * Save access token
     *
     * @param string $expires_in
     */
    public function saveToken($expires_in = '')
    {
        $settings = [
            'client_id' => [
                'type' => 'text',
                'key' => 'client_id',
                'value' => $this->methodInfo['Settings']['client_id']['value'],
            ],
            'client_secret' => [
                'type' => 'text',
                'key' => 'client_secret',
                'value' => $this->methodInfo['Settings']['client_secret']['value'],
            ],
            'pickup_account' => [
                'type' => 'text',
                'key' => 'pickup_account',
                'value' => $this->methodInfo['Settings']['pickup_account']['value'],
            ],
            'length_type' => [
                'type' => 'radio',
                'key' => 'length_type',
                'source' => 'dhlLengthTypes',
                'value' => $this->methodInfo['Settings']['length_type']['value'],
                'phrase_key' => 'shc_ups_length_type',
            ],
            'access_token' => [
                'type' => 'hidden',
                'key' => 'access_token',
                'value' => $this->token,
            ],
            'expires_in' => [
                'type' => 'hidden',
                'key' => 'expires_in',
                'value' => time() + $expires_in,
            ],
        ];

        $update = [
            'fields' => [
                'Settings' => serialize($settings),
            ],
            'where' => ['Key' => 'dhl'],
        ];

        $GLOBALS['rlDb']->updateOne($update, 'shc_shipping_methods', ['Settings']);
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        $this->token = $this->methodInfo['Settings']['access_token']['value'];
        return $this->token;
    }

    /**
     * Check is token expired
     *
     * @return bool
     */
    public function isTokenExpired()
    {
        return time() > $this->methodInfo['Settings']['expires_in']['value'];
    }

    /**
     * Prepare adapted for CarSpecs Guzzle client
     *
     * @return \GuzzleHttp\Client
     */
    protected function prepareClient()
    {
        return new Client(array('headers' => $this->headers));
    }

    /**
     * Call DHL API
     *
     * @param string  $endpoint - API endpoint
     * @param string  $type     - Request type: post, get
     * @param array   $data     - Sending data
     *
     * @return \stdClass
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function callApi($endpoint, $type, $data = array())
    {
        $type = trim(strtoupper($type));
        if (!in_array($type, array('GET', 'POST', 'PUT', 'PATCH'))) {
            return false;
        }

        $client = $this->prepareClient();

        try {
            $url = $this->prepareUrl($endpoint);

            $requestData = array(
                'body' => json_encode($data),
            );
            $res = $client->request($type, $url, $requestData);

            return $this->generateResponse('success', $res);
        } catch (ClientException $e) {
            return $this->generateError('client_error', $e->getMessage(), 403);
        } catch (\Exception $e) {
            return $this->generateError('server_exception', $e->getMessage(), 500);
        }
    }

    /**
     * Generate API response
     *
     * @param string                    $type     - Response type
     * @param \GuzzleHttp\Psr7\Response $response - Guzzle response object
     *
     * @return object
     */
    public function generateResponse($type, $response)
    {
        $responseBody = $type == 'success'
        ? json_decode((string) $response->getBody(), true)
        : $response->getMessage();

        $out = new \stdClass();
        $out->type = $type;
        $out->status = $response->getStatusCode();
        $out->phrase = $response->getReasonPhrase();
        $out->body = $responseBody;

        return $out;
    }

    /**
     * Generate error response from API
     *
     * @param string  $code    - Error code
     * @param  string $message - Error message
     * @param int     $status  - Code of the error
     *
     * @return \stdClass
     */
    public function generateError($code, $message, $status = 500)
    {
        $out = new \stdClass();
        $out->type = 'error';
        $out->status = $status;
        $out->phrase = $code;
        $out->message = $message;

        return $out;
    }

    /**
     * Load static data
     */
    public static function loadStaticData()
    {
        global $rlSmarty, $dhlTransportMode, $dhlLengthTypes;

        require_once RL_PLUGINS . 'shoppingCart/shipping/dhl/static.inc.php';

        if ($rlSmarty) {
            $rlSmarty->assign_by_ref('shc_transport_modes', $dhlTransportMode);
            $rlSmarty->assign_by_ref('shc_length_types', $dhlLengthTypes);
        }
    }
}
