<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RESPONSE.PHP
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

/**
 * Class Response
 *
 * @since 2.0.0
 *
 * @package Flynax\Plugin\WordPressBridge
 */
class Response
{
    /**
     * Generate JSON response
     *
     * @param array $array - Provided data will be converted to JSON
     */
    public static function json($array)
    {
        header('Content-Type: application/json');

        $data = [
            'data' => $array,
        ];
        echo json_encode($data);
    }

    /**
     * Generate error response
     *
     * @param string $message - Error message
     * @param int    $code    - HTTP response error code
     */
    public static function error($message, $code = 500)
    {
        http_response_code($code);

        $data = [
            'message' => $message,
            'code' => $code,
        ];

        self::json($data);
    }

    /**
     * Generate success response
     *
     * @param string|array $message - Response answer (string) message / (array) body
     * @param int          $code    - HTTP response success code
     */
    public static function success($message, $code = 200)
    {
        http_response_code($code);

        $data['code'] = $code;

        if (!is_array($message)) {
            $data['message'] = $message;
        } else {
            $data = $message;
        }

        self::json($data);
    }
}
