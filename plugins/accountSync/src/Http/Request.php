<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: REQUEST.PHP
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

namespace Flynax\Plugins\AccountSync\Http;

class Request
{
    /**
     * Get current request URI
     * @return mixed
     */
    public static function uri()
    {
        $uri = $_SERVER['REQUEST_URI'];

        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        $uri = rawurldecode($uri);

        return $uri;
    }

    /**
     * Get current request method
     * @return mixed
     */
    public static function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Send request to the URL with some data
     *
     * @param string $type - Sending request type: post, get, delete
     * @param string $url  - To which URL will be sent this request
     * @param string $data - What data you want to send to this request
     *
     * @return \stdClass - Response
     */
    public function send($type, $url, $data)
    {
        $type = strtolower($type);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        switch ($type) {
            case 'post':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'delete':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
        }

        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($result === false) {
            throw new \Exception(curl_error($ch));
        }

        curl_close($ch);

        $out = new \stdClass();
        $out->status = $status;
        $out->body = json_decode($result, true);

        return $out;
    }

    /**
     * Get request parameters
     * @return mixed
     */
    public function getParams()
    {
        $returnData = [];
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                $jsonStr = file_get_contents("php://input");
                $returnData = json_decode($jsonStr, true);
                break;

            default:
                $returnData = $_REQUEST;
                break;
        }
        return $returnData;
    }
}
