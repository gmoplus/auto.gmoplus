<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.7.0
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: RESPONSE.PHP
 *
 *	The software is a commercial product delivered under single, non-exclusive,
 *	non-transferable license for one domain or IP address. Therefore distribution,
 *	sale or transfer of the file in whole or in part without permission of Flynax
 *	respective owners is considered to be illegal and breach of Flynax License End
 *	User Agreement.
 *
 *	You are not allowed to remove this information from the file without permission
 *	of Flynax respective owners.
 *
 *	Flynax Classifieds Software 2022 |  All copyrights reserved.
 *
 *	https://www.flynax.com/
 *
 ******************************************************************************/

namespace Flynax\Plugins\AccountSync\Http;

class Response
{
    /**
     * Successful response
     */
    const SUCCESS = 200;

    /**
     * Record has been created or changed
     */
    const CREATED = 201;

    /**
     * Something went wrong on the server side
     */
    const SERVER_ERROR = 500;

    /**
     * Client has been sent wrong request or provided wrong data
     */
    const BAD_REQUEST = 400;

    /**
     * Client was unauthorized
     */
    const UNAUTHORIZED = 401;

    /**
     * Entry not found during request
     */
    const NOT_FOUND = 404;

    /**
     * Generate JSON response
     *
     * @param array $array - Provided data will be converted to JSON
     */
    public static function json($array)
    {
        header('Content-Type: application/json');
        echo json_encode($array, JSON_UNESCAPED_SLASHES);

        die();
    }

    /**
     * Generate error response
     *
     * @param mixed $message - Error message
     * @param int    $code    - HTTP response error code
     * @param string $reason
     */
    public static function error($message, $code = 500, $reason = 'server_error')
    {
        http_response_code($code);

        $data = [
            'code' => $code,
            'reason' => $reason,
            'message' => $message,
        ];

        self::json($data);
    }

    /**
     * Generate success response
     *
     * @param string|array $message    - Response answer (string) message / (array) body
     * @param int          $code       - HTTP response success code
     * @param string       $codePhrase - Response code
     */
    public static function success($message, $code = 200, $codePhrase = 'success')
    {
        http_response_code($code);

        $data['code'] = $code;
        $data['code_phrase'] = $codePhrase;

        if (!is_array($message)) {
            $data['message'] = $message;
        } else {
            $data += $message;
        }

        self::json($data);
    }
}
