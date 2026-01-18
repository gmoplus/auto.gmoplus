<?php
/**copyright**/

namespace Flynax\Plugins\CarSpecs\HTTP;

/**
 * Class Request
 *
 * @since 2.1.0
 * @package Flynax\Plugins\CarSpecs\HTTP
 */
class Request
{
    /**
     * @var string
     */
    public $baseUri = '';

    /**
     * Send Request
     *
     * @param $endpoint
     * @param $params
     * @return mixed
     */
    public function send($endpoint, $params)
    {
        return $this->_sendQuery($endpoint, $params);
    }

    /**
     * Send HTTP request to the VinAudit API
     *
     * @param string $endpoint - API Endpoint
     * @param array  $params
     *
     * @return mixed
     */
    private function _sendQuery($endpoint, $params)
    {
        $url = $this->baseUri . $endpoint;
        $params['format'] = 'json';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        $content = curl_exec($curl);
        curl_close($curl);

        return json_decode($content);

    }

    /**
     * Set API Base URL
     *
     * @param $url
     */
    public function setBaseURI($url)
    {
        $this->baseUri = $url;
    }

    /**
     * Download PDF file from API
     *
     * @param string $endpoint - File providing API endpoint
     * @param array  $params   - Request additional parameters
     * @param string $fileName - Saving file name with extension
     *
     * @return bool
     */
    public function download($endpoint, $params, $fileName)
    {
        $url = $this->baseUri . $endpoint;
        $params['format'] = 'json';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 100);
        $content = curl_exec($curl);
        $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        curl_close($curl);

        if ($contentType == 'application/pdf') {
            file_put_contents($fileName, $content);
            return true;
        }

        return false;
    }
}
