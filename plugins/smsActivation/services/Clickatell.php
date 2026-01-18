<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: CLICKATELL.PHP
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

namespace SmsActivation\Services;

class Clickatell
{
    /**
     * Send request to sms server
     *
     * @param string $smsCode
     * @param mixed $phone
     * @param string $message
     *
     * @return mixed
     */
    public function send($smsCode = '', $phone = '', $message = '')
    {
        global $config;

        if (!$phone || !$message) {
            return false;
        }
        $phone = str_replace(array('+', '-', '(', ')', ' '), '', $phone);
        $sms_text = str_replace('{code}', $smsCode, $message);

        if ($config['sms_activation_method'] == 'rest') {
            $clickatell = new \Clickatell\Rest($config['sms_activation_api_key']);

            try {
                $response = $clickatell->sendMessage(array('to' => array($phone), 'content' => $sms_text));
            } catch (\Clickatell\ClickatellException $e) {
                $result = $GLOBALS['lang']['smsActivation_rest_error'];
                $GLOBALS['rlDebug']->logger('SMS Activation Plugin, Clickatell Error: ' . $e->getMessage());
                return $result;
            }
        } else {
            $clickatell_url = 'https://platform.clickatell.com/messages/http/send?';
            $request = 'apiKey=' . $config['sms_activation_api_key'];
            $request .= '&to=' . $phone . '&content=' . $sms_text;
            $response = $GLOBALS['reefless']->getPageContent($clickatell_url . $request);
            $response = json_decode($response, true);
            $response = $response['messages'];
        }

        if (!empty($response)) {
            foreach ($response as $key => $item) {
                if ($item['accepted']) {
                    $result = 'OK';
                } else {
                    $result = $item['error'];
                }
            }
        } else {
            $result = $GLOBALS['lang']['smsActivation_rest_error'];
        }

        return $result;
    }
}
