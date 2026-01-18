<?php
/**copyright**/

namespace Flynax\Plugins\CarSpecs\Modules;

use Flynax\Plugins\CarSpecs\Cache;
use Flynax\Plugins\CarSpecs\Interfaces\ModuleInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * Class Carmotorspecs
 *
 * @since   2.1.0
 *
 * @package Flynax\Plugins\CarSpecs\Modules
 */
class Carmotorspecs implements ModuleInterface
{
    /**
     * @var string - carMotorSpecs access token
     */
    protected $accessToken;

    /**
     * @var string - url for sending login requests
     */
    protected $oauthLoginUrl = 'https://api.motorspecs.com/oauth';

    /**
     * @var string - url for sending api calls to the CarSpecs endpoints
     */
    protected $apiUrl = 'https://api.motorspecs.com';

    /**
     * @var bool - Does API require authentication
     */
    public $isAuthRequired = true;

    /**
     * @var string - CarSpecs API client ID
     */
    protected $clientID = '';

    /**
     * @var string - CarSpecs API client secret
     */
    protected $clientKey = '';

    /**
     * @var array - API Headers
     */
    private $headers = array();

    /**
     * @var \GuzzleHttp\Psr7\Response
     */
    protected $apiAnswer;

    /**
     * CarMotorSpecs constructor.
     *
     * @param string $clientID
     * @param string $clientKey
     */
    public function __construct($clientID = '', $clientKey = '')
    {
        if ($clientID && $clientID) {
            $this->setClientID($clientID);
            $this->setClientKey($clientKey);
        }
    }

    /**
     * Add header to the request
     *
     * @param array $headers - HTTP Request headers
     *
     * @return \Flynax\Plugins\CarSpecs\Modules\Carmotorspecs
     */
    public function withHeaders($headers)
    {
        $new = clone $this;
        $new->headers += $headers;

        return $new;
    }

    /**
     * Authenticate user
     *
     * @return \Flynax\Plugins\CarSpecs\Modules\Carmotorspecs $this
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function auth()
    {
        $clientID = $this->getClientID();
        $clientSecret = $this->getClientKey();

        if ($this->accessToken) {
            $this->headers['Authorization'] = 'Bearer ' . $this->accessToken;

            return $this;
        }

        if ($token = $this->login($clientID, $clientSecret)) {
            $this->accessToken = $token;
            $this->headers['Authorization'] = 'Bearer ' . $this->accessToken;
        }

        return $this;
    }

    /**
     * Prepare adapted for CarSpecs Guzzle client
     *
     * @return \GuzzleHttp\Client
     */
    protected function prepareClient()
    {
        if (!in_array('Accept', array_keys($this->headers))) {
            $this->headers += array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            );
        }

        return new Client(array('headers' => $this->headers));
    }


    /**
     * Handle xAJAX request from the CarSpecs Module
     */
    public function afterPostSending($serviceInfo)
    {
        $mode = $_REQUEST['mode'];
        $result = array(
            'status' => 'ERROR',
        );

        switch ($mode) {
            case 'cs_checkRegNumber':
                $regNumber = (string) $_REQUEST['reg_number'];
                $mileage = (int) $_REQUEST['odometr'];

                $this->setClientID($serviceInfo['Login']);
                $this->setClientKey($serviceInfo['Api_key']);
                $result = $this->getCarInfo($regNumber);
                break;
        }

        return $result;
    }

    /**
     * Login to the CarMotorSpecs API service
     *
     * @param string $clientID
     * @param string $clientSecret
     *
     * @return bool|string - Token if everything was successful and false if something went wrong
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function login($clientID = '', $clientSecret = '')
    {
        $credentials = array(
            'grant_type' => 'client_credentials',
            'client_id' => $clientID,
            'client_secret' => $clientSecret,
        );

        if (!$this->isTokenExpired()) {
            return $this->getToken();
        }

        $responseBody = $this->callApi('oauth', 'post', $credentials);

        if ($responseBody->status == 200) {
            $tokenInfo = $responseBody->body;
            $tokenInfo['expires_in'] = time() + $tokenInfo['expires_in'];
            Cache::set('token', json_encode($tokenInfo), 'car_specs');

            return $responseBody->body['token'];
        }

        return false;
    }

    /**
     * Call CarSpecs API
     *
     * @param string  $endpoint - API endpoint
     * @param  string $type     - Request type: post, get
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
     * Get car info - identifier + CarSpecs
     *
     * @param  string $regNumber - Car plate number
     *
     * @return mixed
     */
    public function getCarInfo($regNumber)
    {
        $response = $this->carIdentity($regNumber);

        if ($response->type == 'error' || $response) {
            // todo: handle error
        }


        if ($response) {
            $specResponse = $this->getCarSpecs($regNumber);
            $response += $specResponse;

            return $response;
        }
    }

    /**
     * Getting CarSpecs info by plate number
     *
     * @param string $regNumber - Car Plate number
     *
     * @return mixed
     */
    public function getCarSpecs($regNumber)
    {
        $headers = array(
            'Accept' => 'application/vnd.specs.v2+json',
            'Content-Type' => 'application/vnd.specs.v2+json',
        );

        $data = array(
            'registration' => $regNumber,
        );

        $apiResponse = $this->withHeaders($headers)->auth()->post('specs/standard', $data);
        $apiResponseBody = $apiResponse->body;

        return $this->adaptCarSpecsResponse($apiResponseBody);
    }

    /**
     * Prepare 'specs/standard' getaway to readable format
     *
     * @param array $apiAnswer - Getaway response
     *
     * @return mixed
     */
    public function adaptCarSpecsResponse($apiAnswer)
    {
        $preparedArray = array();

        $topFeatures = $apiAnswer['topFeatures'];
        foreach ($topFeatures as $key => $value) {
            $key = $GLOBALS['rlValid']->str2key($value['name']);
            $preparedArray['topFeatures'][$key] = $value['name'];
        }
        $standardSpecification = $apiAnswer['standardSpecification'];
        foreach ($standardSpecification as $key => $spec) {
            $specKey = key($spec);

            foreach ($spec[$specKey] as $sKey => $item) {
                $newSpec[$specKey][$sKey] = array(
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'value' => $item['items'] ?: $item['value'],
                );
            }
        }

        if ($newSpec) {
            $preparedArray += $newSpec;
        }

        return $GLOBALS['rlCarSpecs']->extractSubNodes($preparedArray);
    }

    /**
     * Identify main car option by registration number
     *
     * @param string $regNumber
     *
     * @return array|bool
     */
    public function carIdentity($regNumber)
    {
        if (!$regNumber) {
            return false;
        }

        $data = array(
            'registration' => $regNumber,
        );

        $headers = array(
            'Accept' => 'application/vnd.identity-specs.v2+json',
            'Content-Type' => 'application/vnd.identity-specs.v2+json',
        );

        $response = $this->auth()->withHeaders($headers)->post('identity-specs/lookup', $data);

        return $response->type == 'success' ? $this->prepareDataForMapping($response) : false;
    }

    /**
     * Adapt car Identity Endpoint for proper readable format
     *
     * @param array $responseFromAPI - Response from 'identity-specs/lookup' getaway
     *
     * @return array
     */
    public function prepareDataForMapping($responseFromAPI)
    {
        $responseBody = $responseFromAPI->body;

        return array(
            'id' => $responseBody['id'],
            'registration' => $responseBody['registration'],
            'vehicleId' => $responseBody['vehicleId'],
            'vehicle_info' => array(
                'mvris' => $responseBody['vehicle']['mvris'],
                'dvla' => $responseBody['vehicle']['dvla'],
            ),
        );
    }


    /**
     * Get API result as array
     *
     * @return mixed
     */
    public function asArray()
    {
        return (array) $this->handleAnswer(true);
    }

    /**
     * Get API result as object
     */
    public function asObject()
    {
        return $this->handleAnswer(false);
    }

    /**
     * Handler API answer
     *
     * @return \stdClass
     */
    public function handleAnswer($asArray = false)
    {
        $shortStatusCode = intval($this->apiAnswer->getStatusCode() / 10);
        $response = null;

        switch ($shortStatusCode) {
            case 20:
                $response = $this->handleSuccess($this->apiAnswer, $asArray);
                break;
            case 40:
                $response = $this->handleErrorResponse($this->apiAnswer, 'client');
                break;
            case 50:
                $response = $this->handleErrorResponse($this->apiAnswer, 'server');
                break;
        }

        return $response;
    }

    /**
     * Handle CarSpecs API error response
     *
     * @param object $apiAnswer
     * @param string $type - Error type
     *
     * @return \stdClass
     */
    public function handleErrorResponse($apiAnswer, $type)
    {
        $errorObject = json_decode($apiAnswer->getBody()->getContents());

        $answer = new \stdClass();
        $answer->status = 'ERROR';
        $answer->type = $type;
        $answer->title = $errorObject->title;
        $answer->message = $errorObject->detail;

        return $answer;
    }

    /**
     * Handle success answer and return in proper for reading in AJAX format
     *
     * @param \GuzzleHttp\Psr7\Response $apiAnswer
     *
     * @return \stdClass
     */
    public function handleSuccess($apiAnswer, $asArray = false)
    {
        $answer = new \stdClass();
        $answer->status = 'OK';
        $answer->result = json_decode($this->apiAnswer->getBody()->getContents(), $asArray);

        return $answer;
    }


    /**
     * Prepare full URL to the API by endpoint
     *
     * @param string $endpoint - CarSpecs API endpoint
     *
     * @return string - Full URL to the API
     */
    public function prepareUrl($endpoint)
    {
        return $this->apiUrl . '/' . $endpoint;
    }

    /**
     * Send POST request to the CarSpecs API
     *
     * @param string $endpoint - CarSpecs API endpoint
     * @param array  $data     - Sending data
     *
     * @return \stdClass - API result
     */
    public function post($endpoint, $data = array())
    {
        return $this->callApi($endpoint, 'post', $data);
    }

    /**
     * Send GET request to the CarSpecs API
     *
     * @param string $endpoint - CarSpecs API endpoint
     * @param array  $data     - Sending data
     *
     * @return \stdClass - API result
     */
    public function get($endpoint, $data = array())
    {
        return $this->callApi($endpoint, 'get', $data);
    }

    /**
     * clientID property getter
     *
     * @return string
     */
    public function getClientID()
    {
        return $this->clientID;
    }

    /**
     * clientID property setter
     *
     * @param int|string $clientID
     */
    public function setClientID($clientID)
    {
        $this->clientID = $clientID;
    }

    /**
     * clientKey property getter
     *
     * @return string
     */
    public function getClientKey()
    {
        return $this->clientKey;
    }

    /**
     * clientKey property setter
     *
     * @param string $clientKey
     */
    public function setClientKey($clientKey)
    {
        $this->clientKey = $clientKey;
    }

    public function getTokenInfo()
    {
        return json_decode(Cache::get('token', 'car_specs'));
    }

    public function getToken()
    {
        $tokenInfo = $this->getTokenInfo();
        return $tokenInfo->access_token;
    }

    public function isTokenExpired()
    {
        $tokenInfo = $this->getTokenInfo();
        return time() > $tokenInfo->expires_in;
    }
}
