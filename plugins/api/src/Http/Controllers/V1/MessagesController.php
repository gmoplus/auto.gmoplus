<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: MESSAGESCONTROLLER.PHP
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

class MessagesController extends BaseController
{
    /**
     * Get contacts
     *
     * @return array - contacts information
     **/
    public function getContacts()
    {
        $contacts = [];
        if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
            rl('Message');
            $data = rl('Message')->getContacts();

            foreach($data as $key => &$value) {
                if ($value['Photo']) {
                    $value['Photo'] = RL_FILES_URL . $value['Photo'];
                }
                if ($value['Photo_x2']) {
                    $value['Photo_x2'] = RL_FILES_URL . $value['Photo_x2'];
                }
                $value['Contact_ID'] = $key;
                $contacts[] = $value;
            }
        }
        $responce = ['status'=> 'ok', 'results'=> $contacts];

        return $responce;
    }

    /**
     * Get messages
     *
     * @return array - messages in chat
     **/
    public function getMessages()
    {
        $messages = [];
        if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
            rl('Message');
            rl('Listings');
            $userID = $_REQUEST['user_id'];
            $noUpdate = false;
            $visitorMail = $_REQUEST['visitor_mail'];
            $admin = $_REQUEST['admin'] ? true : false;
            $messages = rl('Message')->getMessages($userID, $noUpdate, $visitorMail, $admin);
        }
        
        $responce = ['status'=> 'ok', 'results'=> $messages];

        return $responce;
    }

    /**
     * Send message
     *
     * @return array - send message
     **/
    public function sendMessage()
    {
        global $_response;
        if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
            $_response = rl('Smarty', null, 'api');
            rl('Message');
            rl('Listings');

            $noUpdate = false;
            $userID = $_REQUEST['user_id'];
            $message = $_REQUEST['message'];
            $admin = $_REQUEST['admin'] ? true : false;
            $visitor_mail = $_REQUEST['visitor_mail'];

            if ($visitor_mail) {
                rl('Message')->contactVisitor($message, $visitor_mail, $_REQUEST['name']);
            }
            else {
                rl('Message')->ajaxSendMessage($userID, $message, $admin);
            }
            $messages = rl('Message')->getMessages($userID, $noUpdate, $visitor_mail, $admin);
        }
        
        $responce = ['status'=> 'ok', 'results'=> $messages];

        return $responce;
    }
    

    /**
     * Remove messages
     *
     * @return array - IDs
     **/
    public function removeMessages()
    {
        global $_response;
        $messages = [];

        if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
            $_response = rl('Smarty', null, 'api');
            rl('Listings');

            $userID = $_REQUEST['user_id'];
            $noUpdate = false;
            $admin = $_REQUEST['admin'] ? true : false;
            $visitorMail = $_REQUEST['visitor_mail'];
            $ids = $_REQUEST['ids'];

            rl('Message')->ajaxRemoveMsg($ids, $userID, $admin);

            $messages = rl('Message')->getMessages($userID, $noUpdate, $visitorMail, $admin);

        }
        
        $responce = ['status'=> 'ok', 'results'=> $messages];

        return $responce;
    }

    /**
     * Remove contacts
     *
     * @return array - IDs
     **/
    public function removeContacts()
    {
        global $_response;
        $contacts = [];

        if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
            rl('Listings');
            $_response = rl('Smarty', null, 'api');

            $ids = $_REQUEST['ids'];
            rl('Message')->ajaxRemoveContacts($ids);
            $responce = $this->getContacts();
        }

        return $responce;
    }
    
    /**
     * Get latest message
     *
     * @return array
     **/
    public function getLatestMessage($userID, $field)
    {
        // Get latest message
        $sql = "SELECT `T1`.*, `T2`.`Username`, `T2`.`First_name`, `T2`.`Last_name`, `T2`.`Photo` ";
        $sql .= "FROM `{db_prefix}messages` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`From` = `T2`.`ID` ";
        if (!$userID) {
            $userID = "-1";
            $sql .= "WHERE `From` = '{$userID}' ORDER BY `T1`.`ID` Desc";
        } else {
            $sql .= "WHERE `{$field}` = '{$userID}' ORDER BY `T1`.`ID` Desc";
        }
        $message = rl('Db')->getRow($sql);

        $message['Count'] = $message['Status'] == 'new' ? 1: 0;
        if ($message['Photo']) {
            $message['Photo'] = RL_FILES_URL . $message['Photo'];
        }

        // Set full name
        if (!$userID || $userID == '-1') {
            $message['Full_name'] = $message['Visitor_name'];
            $message['Contact_ID'] = $message['Visitor_mail'];
        } elseif ($value['Admin']) {
            $message['Full_name'] = $GLOBALS['lang']['administrator'];
            $message['Contact_ID'] = $userID . '_admin';
        } else {
            $message['Full_name'] = $message['First_name'] || $message['Last_name']
                ? $message['First_name'] . ' ' . $message['Last_name']
                : $message['Username'];
            $message['Contact_ID'] = $userID;
        }

        // build listing link
        if ($message['Listing_ID']) {
            $sql = "SELECT `T1`.*, `T2`.`Type` AS `Listing_type` FROM `{db_prefix}listings` AS `T1` ";
            $sql .= "JOIN `{db_prefix}categories` AS `T2` ON `T2`.`ID` = `T1`.`Category_ID` ";
            $sql .= "WHERE `T1`.`ID` = {$message['Listing_ID']} LIMIT 1";
            $listing_info = rl('Db')->getRow($sql);

            $listing_info['listing_title'] = rl('Listings')->getListingTitle(
                $listing_info['Category_ID'],
                $listing_info,
                $listing_info['Listing_type']
            );

            $message['listing_url'] = rl('reefless')->url('listing', $listing_info);
            $message['listing_title'] = $listing_info['listing_title'];
        }

        return $message;
    }

    
    /**
     * Contact owner
     *
     * @return array
     **/
    public function contactOwner()
    {
        $GLOBALS['config']['security_img_contact_seller'] = '';

        if ($_REQUEST['account_password']) {
            (new AccountController)->issetAccount($_REQUEST['user_id'], $_REQUEST['account_password']);
        }

        $account_id = $_REQUEST['user_id'];
        $listing_id = $_REQUEST['listing_id'];
        $name = $_REQUEST['name'];
        $email = $_REQUEST['mail'];
        $phone = $_REQUEST['phone'];
        $message = $_REQUEST['message'];
        $box_index = '';
        $code = '';

        $responce = rl('Message')->contactOwner($name, $email, $phone, $message, $code, $listing_id, $box_index, $account_id);

        return $responce;
    }

    /**
     * Update message status 
     *
     * @return array
     **/
    public function updateMessageStatus()
    {
        if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
            $update = [
                'fields' => ['Status' => $_REQUEST['status']],
                'where' => ['ID' => $_REQUEST['message_id']],
            ];
            rl('Db')->updateOne($update, 'messages');
        }
        return ['status'=> 'ok'];
    }
}
