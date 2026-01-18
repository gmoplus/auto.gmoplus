<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLAPIPLUGIN.CLASS.PHP
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

use Flynax\Abstracts\AbstractPlugin;
use Flynax\Interfaces\PluginInterface;
use Flynax\Api\Http\Controllers\V1\MessagesController;
use Flynax\Api\Http\Controllers\V1\PushNotificationController;

require_once RL_PLUGINS .'api' . RL_DS .'vendor' . RL_DS . 'autoload.php';

/**
 * Flynax API plugin class
 */
class rlApiPlugin extends AbstractPlugin implements PluginInterface
{
    /**
     * Execute arbitrary changes after installation
     *
     * @return void
     */
    public function install()
    {
        // create android push_token table
        $sql_create = "`ID` int(50) NOT NULL AUTO_INCREMENT,
            `Token` varchar(255) NOT NULL,
            `Phone_ID` varchar(255) NOT NULL,
            `Account_ID` int(6) NOT NULL DEFAULT '0',
            `Platform` varchar(50) NOT NULL DEFAULT '',
            `Language` varchar(2) NOT NULL,
            `Status` enum('active','inactive') NOT NULL DEFAULT 'active',
            PRIMARY KEY (`ID`)";
        $GLOBALS['rlDb']->createTable("api_push_tokens", $sql_create, RL_DBPREFIX, "DEFAULT CHARSET=utf8");
    }

    /**
     * Execute arbitrary changes after uninstall
     *
     * @return void
     */
    public function uninstall()
    {
        global $rlDb;
        $rlDb->dropTables(
            [
                'api_push_tokens',
            ]
        );
    }

    /**
     * Set locale
     *
     * @return void
     */
    public function setLocale()
    {
        if (isset($_GET['lang']) && $GLOBALS['rlDb']->getOne('Code', "`Code` = '{$_GET['lang']}'", 'languages')) {
            $lang = $_GET['lang'];
        }
        else {
            $lang = $GLOBALS['config']['lang'];
        }
        $date_format = $GLOBALS['rlDb']->getOne('Date_format', "`Code` = '{$_GET['lang']}'", 'languages');
        if ($date_format) {
            define('RL_DATE_FORMAT', $date_format);
        }
        define('RL_LANG_CODE', $lang);
    }

    /**
    * Add location condition to the search query
    *
    * @hook - listingsModifyWhereSearch
    **/
    public function hookListingsModifyWhereSearch(&$sql)
    {
        if ($this->coordinates) {
            $sql .= "AND `T1`.`Loc_latitude` != '0' AND `T1`.`Loc_longitude` != '0'";

            $sql .= "AND (`T1`.`Loc_latitude` BETWEEN {$this->coordinates['southWestLat']} AND {$this->coordinates['northEastLat']}) ";
            if ($this->coordinates['southWestLng'] > $this->coordinates['northEastLng']) {
                $sql .= "AND ((`T1`.`Loc_longitude` BETWEEN {$this->coordinates['southWestLng']} AND 180) ";
                $sql .= "OR (`T1`.`Loc_longitude` BETWEEN -180 AND {$this->coordinates['northEastLng']})) ";
            } else {
                $sql .= "AND (`T1`.`Loc_longitude` BETWEEN {$this->coordinates['southWestLng']} AND {$this->coordinates['northEastLng']}) ";
            }
        }

        if (defined('FLUTTER_SAVED_SEARCH_IDS') and FLUTTER_SAVED_SEARCH_IDS) {
            $sql .= sprintf(" AND `T1`.`ID` IN(%s) ", FLUTTER_SAVED_SEARCH_IDS);
        }
    }

    /**
     * @hook apMixConfigItem
     *
     * @param array $config - configs
     */
    public function hookApMixConfigItem(&$config)
    {
        if ($config['Key'] == 'api_firebase_service' && $config['Values']) {
            $config['Default'] = $GLOBALS['rlDb']->getOne('Values', "`Key` = 'api_firebase_service'", 'config');
        }
    }

    /**
     * @hook  apPhpConfigBeforeUpdate
     */
    public function hookApPhpConfigBeforeUpdate()
    {
        global $update;
        foreach ($update as $key => &$value) {
            if ($value['where']['Key'] == 'api_firebase_service') {
                $value['fields']['Values'] = $value['fields']['Default'];
            }
        }
    }

    /**
     * @hook rlMessagesAjaxAfterMessageSent
     *
     * @param int $user_id
     * @param int $message
     * @param string $admin 
     * @return
     */
    public function hookRlMessagesAjaxAfterMessageSent($user_id, $message, $admin)
    {
        $pushController = new PushNotificationController();
        if (false !== $tokenIDs = $pushController->getActiveTokens($user_id)) {
            $messagesController = new MessagesController();

            $messageNew = $messagesController->getLatestMessage($user_id, 'To');
            $data['notify_key'] = 'message';
            foreach ($messageNew as $key => $val) {
                if ($key == "From" || $key == "To") {
                    $data[$key . "_id"] = $val;
                } else {
                    $data[$key] = $val;
                }
            }

            $notify = [
                'title_key' => 'new_message',
                'body' => $messageNew['Message']
            ];

            $pushController->buildSendPushNotification($tokenIDs, $notify, $data);
        }
    }

    /**
     * @hook  rlMessagesAjaxContactOwnerAfterSend
     */
    public function hookRlMessagesAjaxContactOwnerAfterSend($name, $email, $phone, $message, $listing_id)
    {
        $pushController = new PushNotificationController();
        $messagesController = new MessagesController();
        $messageNew = $messagesController->getLatestMessage($GLOBALS['account_info']['ID'], 'From');
        if (false !== $tokenIDs = $pushController->getActiveTokens($messageNew['To'])) {
            $data['notify_key'] = 'message';
            foreach ($messageNew as $key => $val) {
                if ($key == "From" || $key == "To") {
                    $data[$key . "_id"] = $val;
                } else {
                    $data[$key] = $val;
                }
            }

            $notify = [
                'title_key' => 'new_message',
                'body' => $messageNew['Message']
            ];

            $pushController->buildSendPushNotification($tokenIDs, $notify, $data);
        }
    }

    /**
     * cronSavedSearchNotify
     *
     * @param array $info         - save search content info
     * @param array $listings     - find listings
     * @param array $account_info - account info
     *
     **/
    public function hookCronSavedSearchNotify($info, $listings, $account_info)
    {
        global $config, $rlDb;

        $pushController = new PushNotificationController();
        if (false !== $tokenIDs = $pushController->getActiveTokens($account_info['ID'])) {
            $data['notify_key'] = 'save_search';

            $oldCount = $info['Matches'] ? count(explode(",", $info['Matches'])) : 0;
            $newCount = count(explode(",", $listings)) - $oldCount;

            $data['ID'] = $info['ID'];
            $data['IDs'] = $listings;

            $notify = [
                'title_key' => 'app_ad_alert',
                'body' => str_replace('{count}', $newCount, rl('Lang')->getPhrase('app_new_saved_search', null, null, true))
            ];
            $pushController->buildSendPushNotification($tokenIDs, $notify, $data);
        }
    }
}
