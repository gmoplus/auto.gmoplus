<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : REQUEST.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2022
 *	https://www.flynax.com
 *
 ******************************************************************************/

namespace Flynax\Plugin\WordPressBridge;

use Flynax\Plugin\WordPressBridge\WordPressAPI\Token;

/**
 * Class Request
 *
 * @since 2.0.0
 *
 * @package Flynax\Plugin\WordPressBridge
 */
class Request
{
    /**
     * Getting URI of the Request
     *
     * @return string
     */
    public static function uri()
    {
        $uri = $_SERVER['REQUEST_URI'];

        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = str_replace('/plugins/wordpressBridge/requests.php', '', $uri);

        if (!empty(RL_DIR)) {
            $uri = str_replace(RL_DIR, '', $uri);
        }

        $uri = rawurldecode($uri);

        return $uri;
    }

    /**
     * Get request method
     *
     * @return string
     */
    public static function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Send POST request to the provided URL with parameters (submit method helper)
     *
     * @param string $url  - URL to which will be request send
     * @param array  $data - Data which you want to send with request
     * @param bool   $auth - Send auth parameters to the request
     *
     * @return \stdClass
     */
    public static function post($url, $data, $auth = false)
    {
        $self = new self();

        if ($auth) {
            $token = new Token();
            $data['wp_token'] = $token->getWpToken();
        }

        return $self->submit('POST', $url, $data);
    }

    /**
     * Send GET request to the provided URL with parameters (submit method helper)
     *
     *
     * @param string $url       - URL to which will be request send
     * @param array  $arguments - Data which you want to send with request
     * @param bool   $auth      - Send auth parameters to the request
     *
     * @return \stdClass
     */
    public static function get($url, $arguments = array(), $auth = false)
    {
        $self = new self();

        if ($auth) {
            $token = new Token();
            $arguments['wp_token'] = $token->getWpToken();
        }

        if (!empty($arguments)) {
            $url = $url . '&' . http_build_query($arguments);
        }

        return $self->submit('GET', $url);
    }

    /**
     * Send request to the URL
     *
     * @param string $type - Type of the request: get, post
     * @param string $url
     * @param array  $data
     *
     * @return \stdClass
     */
    public function submit($type, $url, $data = array())
    {
        $type = strtolower($type);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if ($type == 'post') {
            curl_setopt($ch, CURLOPT_POST, count($data));

            if ($data) {
                $urlWithData = http_build_query($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $urlWithData);
            }
        }

        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $out = new \stdClass();
        $out->status = $status;
        $out->response = json_decode($result, true);

        return $out;
    }
}
