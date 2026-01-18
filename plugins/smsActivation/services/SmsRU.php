<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : SMSRU.PHP
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

namespace SmsActivation\Services;

use Flynax\Utils\Util;

class SmsRU
{
    private $apiHost = 'https://sms.ru';
    private $countRepeat = 5;

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

        if ($config['sms_activation_auth_method'] === 'call') {
            $url = $this->apiHost . '/code/call?';
            $request = 'api_id=' . $config['sms_activation_smsru_api_key'];
            $request .= '&ip=' . Util::getClientIp();
            $request .= '&phone=' . $phone;
            $request .= '&json=1';
            $response = $GLOBALS['reefless']->getPageContent($url . $request);
            $response = json_decode($response);

            if ($response->status == 'OK') {
                $result = 'OK';

                $accountID = $GLOBALS['account_info'] && $GLOBALS['account_info']['ID']
                    ? $GLOBALS['account_info']['ID']
                    : ($_SESSION['registration'] && $_SESSION['registration']['account_id'] ? $_SESSION['registration']['account_id'] : 0);

                if ($response->code && $accountID) {
                    $GLOBALS['rlSmsActivation']->updateVerificationDetails(
                        $accountID,
                        ['smsActivation_code' => $response->code]
                    );
                }
            } else {
                $result = $GLOBALS['lang']['smsActivation_rest_error'];
                $GLOBALS['rlDebug']->logger('SMS Activation Plugin, SMS.RU Error: ' . $response->status_text . ', code: ' . $response->status_code);
            }
        } elseif ($config['sms_activation_method'] == 'rest') {
            $data = new \stdClass();
            $data->to = $phone;
            $data->text = $sms_text;
            $data->from = $config['sms_activation_from'] ? $config['sms_activation_from'] : '';
            $response = $this->sendOne($data);

            if ($response->status == 'OK') {
                $result = 'OK';
            } else {
                $result = $GLOBALS['lang']['smsActivation_rest_error'];
                $GLOBALS['rlDebug']->logger('SMS Activation Plugin, SMS.RU Error: ' . $response->status_text . ', code: ' . $response->status_code);
            }
        } else {
            $url = $this->apiHost . '/sms/send?';
            $request = 'api_id=' . $config['sms_activation_smsru_api_key'];
            $request .= '&to=' . $phone . '&msg=' . urlencode(iconv('windows-1251', 'utf-8', $sms_text)). '&json=1';
            $response = $GLOBALS['reefless']->getPageContent($url . $request);
            $response = json_decode($response);

            if ($response->status == 'OK') {
                $result = 'OK';
            } else {
                $result = $GLOBALS['lang']['smsActivation_rest_error'];
                $GLOBALS['rlDebug']->logger('SMS Activation Plugin, SMS.RU Error: ' . $response->status_text . ', code: ' . $response->status_code);
            }
        }

        return $result;
    }

    public function sendOne($post = [])
    {
        $url = $this->apiHost . '/sms/send';
        $request = $this->request($url, $post);
        $resp = $this->checkReplyError($request, 'send');

        if ($resp->status == 'OK') {
            $temp = (array) $resp->sms;
            unset($resp->sms);

            $temp = array_pop($temp);

            if ($temp) {
                return $temp;
            } else {
                return $resp;
            }
        }
        else {
            return $resp;
        }
    }

    public function getStatus($id)
    {
        $url = $this->apiHost . '/sms/status';

        $post = new stdClass();
        $post->sms_id = $id;

        $request = $this->request($url, $post);
        return $this->checkReplyError($request, 'getStatus');
    }

    private function request($url, $post = FALSE)
    {
        if ($post) {
            $r_post = $post;
        }

        $ch = curl_init($url . "?json=1");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        if (!$post) {
            $post = new stdClass();
        }

        if (empty($post->api_id) && $post->api_id != 'none') {
            $post->api_id = $GLOBALS['config']['sms_activation_smsru_api_key'];
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query((array) $post));

        $body = curl_exec($ch);
        if ($body === FALSE) {
            $error = curl_error($ch);
        }
        else {
            $error = FALSE;
        }
        curl_close($ch);
        if ($error && $this->countRepeat > 0) {
            $this->countRepeat--;
            return $this->request($url, $r_post);
        }

        return $body;
    }

    private function checkReplyError($res)
    {
        if (!$res) {
            $temp = new stdClass();
            $temp->status = "ERROR";
            $temp->status_code = "000";
            $temp->status_text = "Can't connect to SMS.RU server.";
            return $temp;
        }

        $result = json_decode($res);

        if (!$result || !$result->status) {
            $temp = new stdClass();
            $temp->status = "ERROR";
            $temp->status_code = "000";
            $temp->status_text = "Can't connect to SMS.RU server.";
            return $temp;
        }

        return $result;
    }
}
