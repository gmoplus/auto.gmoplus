<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: PUSHNOTIFICATIONCONTROLLER.PHP
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

namespace Flynax\Api\Http\Controllers\V1;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class PushNotificationController extends BaseController
{
    // push services
    public $factory;

    public function __construct()
    {
        if ($GLOBALS['config']['api_firebase_service']) {
            $serviceAccountKey = $GLOBALS['rlDb']->getOne('Values', "`Key` = 'api_firebase_service'", 'config');
            $this->factory = (new Factory)->withServiceAccount($serviceAccountKey);   
        }
    }
    
    /**
     * Get active tokens
     *
     * @param int $account_id
     * @return 
     */
    public function getActiveTokens($account_id)
    {
        $ids = [];
        if ($account_id) {
            $where = [
                'Account_ID' => $account_id,
                'Status' => 'active',
            ];
            $ids = rl('Db')->fetch('*', $where, null, null, 'api_push_tokens');
        }
        return $ids ? $ids : '';
    }

    
    /**
     * Build push notification
     *
     * @param array $tokens
     * @param array $notify
     * @param array $data
     * @return 
     */
    public function buildSendPushNotification($tokens, $notify, $data)
    {
        if ($tokens) {
            foreach($tokens as $key => $token) {
                $notify['title'] = rl('Lang')->getPhrase($notify['title_key'], $token['Language'], null, true);
                $data['title'] = $notify['title'];
                $data['body'] = $notify['body'];

                $this->sendPushNotification($token['Token'], $notify, $data);
            }
        }
    }

    /**
     * Send push notification
     *
     * @param array $tokenID
     * @param array $notify
     * @param array $data
     * @return 
     */
    public function sendPushNotification($tokenID, $notify, $data)
    {
        if ($GLOBALS['config']['api_firebase_service'] && $tokenID) {
            $this->messaging = $this->factory->createMessaging();

            $message = CloudMessage::withTarget('token', $tokenID)
                ->withNotification($notify)
                ->withData($data);
            $this->messaging->send($message);
        }
    }
}
