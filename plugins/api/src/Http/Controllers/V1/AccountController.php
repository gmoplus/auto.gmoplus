<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: ACCOUNTCONTROLLER.PHP
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

use Illuminate\Http\Request;
use Flynax\Api\Http\Controllers\V1\GeoLocationController;
use Flynax\Utils\Profile;

class AccountController extends BaseController
{
    /**
     * @var Account types
     **/
    public $account_types;

    public function __construct()
    {
        if (file_exists(RL_CLASSES . 'rlSecurity.class.php')) {
            require_once RL_CLASSES . 'rlSecurity.class.php';
        }
    }

    /**
     * Get account types
     *
     **/
    public function getAccountTypes()
    {
        if (!$this->account_types) {
            $except_type_keys = array('visitor', 'affiliate');
            $this->account_types = rl('Account')->getAccountTypes($except_type_keys);
        }

        return $this->account_types;
    }

    /**
     * Get account details
     *
     **/
    public function getAccountDetails()
    {
        if ($_REQUEST['account_id'] && $_REQUEST['account_password']) {
            $this->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password']);
        }

        $account_data = $this->getProfile((int) $_REQUEST['user_id'], false);

        $account_data['account_page_info'] = str_replace('{account_type}', $account_data['Type_name'], $GLOBALS['lang']['account_type_details']);


        // membeship services
        rl('MembershipPlan')->isContactsAllow();
        rl('MembershipPlan')->isSendMessage();

        if ($GLOBALS['account_info']['ID'] == $account_data['ID']) {
            rl('MembershipPlan')->is_contact_allowed = true;
        }
        if ($GLOBALS['account_info']['ID'] != $account_data['ID']) {
            rl('MembershipPlan')->fakeValues($account_data['Fields']);
        }
        $response['membership']['send_message_allowed'] = rl('MembershipPlan')->is_send_message_allowed;
        $response['membership']['contact_allowed'] = rl('MembershipPlan')->is_contact_allowed;

        $response['status'] = 'ok';
        $response['account'] = $account_data;
        return $response;
    }

    /**
     * Registration from app
     *
     **/
    public function registration()
    {
        global $lang, $config, $account_types;

        if ($_POST['post']) {
            $_POST = json_decode($_POST['post'], true);
        }
        if ($_POST['step1']) {
            $step1 = json_decode($_POST['step1'], true);
        }

        if ($_POST['step2']) {
            $step2 = json_decode($_POST['step2'], true);
        }

        if ($step1) {
            $account_types = $this->getAccountTypes();
            $selected_atype = $account_types[$step1['account_type_id']];

            if ($config['account_login_mode'] == 'email') {
                $exp_email = explode('@', $step1['mail']);
                $step1['username'] = rl('Account')->makeUsernameUnique($exp_email[0]);
            }

            if (!rl('Account')->validateUsername($step1['username'])) {
                $errors[] = str_replace('{field}', $lang['username'], $lang['notice_field_not_valid']);
            } else {
                rl('Valid')->sql($step1['username']);
                if ( rl('Db')->getOne('ID', "`Username` = '{$step1['username']}'", 'accounts')) {
                    $errors[] = str_replace('{username}', $step1['username'], $lang['notice_account_exist']);
                }
            }

            // check email
            if (!rl('Valid')->isEmail($step1['mail'])) {
                $errors[] = $lang['notice_bad_email'];
            }
            rl('Valid')->sql($step1['mail']);

            if ($step1['mail']) {
                rl('Valid')->sql($step1['mail']);
                $exist = (bool) rl('Db')->getOne('ID', "`Mail` = '{$step1['mail']}'", 'accounts');
                $message = str_replace('{email}', $step1['mail'], $lang['notice_account_email_exist']);

                rl('Hook')->load('phpAjaxValidateProfileEmail', $step1['mail'], $message, $exist); // from v4.0.2

                if ($exist) {
                    $errors[] = $message;
                }
            }

            // check personal address
            if ($selected_atype['Own_location']) {
                if ($GLOBALS['plugins']['multiField'] && $config['mf_multilingual_path']){
                    rl('GeoFilter', null, 'multiField')->definePathField();
                }
                $wildcard_deny = explode(',', $GLOBALS['config']['account_wildcard_deny']);
                // validate
                $error = $errors_trigger = '';
                rl('Account')->validateUserLocation($step1['location'], $error, $errors_trigger, false);
                rl('Hook')->load('phpAjaxValidateProfileLocation', $step1['location'], $wildcard_deny, $errors_trigger);
                if ($error) {
                    $errors[] = $error;
                }
            }

            $fields = rl('Account')->getFields((int) $step1['account_type_id']);
            $fields = rl('Lang')->replaceLangKeys($fields, 'account_fields', array('name'));
            $GLOBALS['fields'] = $fields;
            if ($step2 && $fields) {
                if ($back_errors = rl('Common')->checkDynamicForm($step2, $fields, 'account')) {
                    foreach ($back_errors as $error) {
                        $errors[] = $error;
                    }
                }

                rl('Hook')->load('beforeRegister');
            }
        }

        if ($errors) {
            $response['status'] = 'error';
            $response['errors'] = $errors;
        }
        else {
            rl('reefless')->loadClass('Resize');
            rl('reefless')->loadClass('Mail');

            /* personal address handler */
            $step1['location'] = trim($step1['location']);

            if (!$step1['location']) {
                $step1['location'] = $step1['username'];
            }

            if ($selected_atype['Own_location'] && !$step1['location']) {
                $step1['location'] = rl('Valid')->str2path($step1['username']);
            }

            $account_id = 0;

            if ($result = rl('Account')->registration($step1['account_type_id'], $step1, $step2, $fields)) {

                $account_id = $_SESSION['registration']['account_id'];

                rl('Hook')->load('registerSuccess');

                // Set membership plan
                if ($step1['plan_id']) {
                    $update = array(
                        'fields' => array(
                            'Plan_ID' => $step1['plan_id'],
                        ),
                        'where'  => array(
                            'ID' => (int) $account_id,
                        ),
                    );

                    $plan = rl('MembershipPlan')->getPlan($step1['plan_id']);
                    if (!$plan['Price']) {
                        $update['fields']['Pay_date'] = 'NOW()';
                        rl('Account')->upgrade($account_id, $step1['plan_id'], false, true);
                    }

                    rl('Db')->updateOne($update, 'accounts');
                }

                $account_data = $this->getProfile($account_id, false);

                // send notification to user and admin
                if (!$account_data['Confirm_code']) {
                    $account_data['Confirm_code'] = rl('Db')->getOne('Confirm_code', "`ID` = '{$account_id}'", 'accounts');
                }
                rl('Account')->sendRegistrationNotification($account_data);

                $account_pass = rl('Db')->getOne('Password', "`ID` = '{$account_id}'", 'accounts');
                $message = '';
                if ($selected_atype['Auto_login'] && !$selected_atype['Email_confirmation'] && !$selected_atype['Admin_confirmation']) {

                    $account_data['password'] = $account_pass;
                    $response['data'] = $account_data;
                    $message = str_replace(
                        ['[', ']'],
                        '',
                        rl('Lang')->getPhrase('registration_complete_auto_login', null, null, true)
                    );
                }
                else if ($selected_atype['Email_confirmation']) {
                    $response['data']['status'] = 'pending';
                    $message = str_replace(
                        ['{email}'],
                        $step1['mail'],
                        rl('Lang')->getPhrase('registration_complete_incomplete', null, null, true)
                    );
                }
                else if ($selected_atype['Admin_confirmation']) {
                    $response['data']['status'] = 'pending';
                    $message = rl('Lang')->getPhrase('registration_complete_pending', null, null, true);
                }
                else {
                    $account_data['password'] = $account_pass;
                    $response['data'] = $account_data;
                    $message = str_replace(
                        ['[', ']'],
                        '',
                        rl('Lang')->getPhrase('registration_complete_active', null, null, true)
                    );
                }

                $response['status'] = 'ok';
                $response['message'] = $message;
            }
        }

        return $response;
    }

    /**
     * Remove account from app
     * @since 1.0.1
     **/
    public function removeAccount()
    {
        $account_id = (int) $_REQUEST['account_id'];
        $password = $_REQUEST['password'];
        $db_pass = rl('Db')->fetch(array('Password'), array('ID' => $account_id), null, null, 'accounts', 'row');
        if (\FLSecurity::verifyPassword($password, $db_pass['Password'])
                && rl('Admin', 'admin')->deleteAccountDetails($account_id, null, true)
            ) {
            $response = array('status' => 'ok', 'message' => $GLOBALS['lang']['remote_delete_account_removed']);
        }
        else {
            $response = array('status' => 'error', 'message' => $GLOBALS['lang']['notice_pass_bad']);
        }
        return $response;
    }

    /**
     * Is allow send message
     * @since 1.0.1
     **/
    public function isSendMessage()
    {
        if ($_REQUEST['account_id'] && $_REQUEST['account_password'] && !$_SESSION['account']) {
            (new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password']);
        }

        // Allow send message
        rl('MembershipPlan')->isSendMessage();
        $response['allow_send_message'] = rl('MembershipPlan')->is_send_message_allowed ? 1 : 0;
        return $response;
    }

    /**
     * Login attempt, checks if login details are correct
     *
     * @param string $account_id - $account_id
     * @param string $password   - password
     *
     **/
    public function issetAccount($account_id, $password)
    {
        global $account_info;
        if (!$account_id || !$password) {
            return false;
        }

        $password = urldecode($password);

        if (!rl('Db')->getOne('ID', "`ID` = {$account_id} AND `Password` = '{$password}'", 'accounts')) {
            return false;
        }
        if (!$_SESSION['account']) {
            $account = rl('Account')->getAccountBaseInfo((int) $account_id, function(&$sql) {
                rl('Hook')->load('loginIfRememberSql', $sql);
            });
            $account['Full_name'] = rl('Account')->getFullName($account);
            $account['Password'] = md5($account['Password']);
            $account['Abilities'] = $account['Abilities'] ? explode(',', $account['Abilities']) : [];

            // get membership plan
            if ($GLOBALS['config']['membership_module'] && $account['Plan_ID']) {
                $account['plan'] = rl('MembershipPlan')->getPlan((int) $account['Plan_ID'], true, $account);

                $expiration_date = strtotime($account['Pay_date']) + ((int) $account['plan']['Plan_period'] * 86400);
                $account['Status'] = time() > $expiration_date && $account['plan']['Plan_period'] > 0 ? 'expired' : $account['Status'];
            }

            $account_info = $_SESSION['account'] = $account;
            define('IS_LOGIN', true);
        }

        return true;
    }

    /**
     * Get profile data
     *
     * @param string $account_id - account_id
     * @param string $edit_mode  - password
     *
     **/
    public function getProfile($account_id = 0, $edit_mode = false)
    {
        if (!$account_id) {
            return;
        }

        $profile = rl('Account')->getProfile((int) $account_id, $edit_mode);

        $date = date(str_replace(array('%', 'b'), array('', 'M'), RL_DATE_FORMAT), strtotime($profile['Date']));
        $profile['since_date'] = str_replace(
            ['{account_type}', '{date}'],
            [$profile['Type_name'], $date],
            $GLOBALS['lang']['account_type_since_data']
        );

        if ($profile['Fields']) {
            foreach ($profile['Fields'] as &$field) {
                $field['name'] = strip_tags($field['name']);
                $field['item'] = 'field';
                if ($field['Type'] == 'phone' && $field['value'] && !$field['Hidden'] && !$edit_mode) {
                    $field['Hidden'] = rl('Db')->getOne('Hidden', "`Key` = '{$field['Key']}'", 'account_fields');
                    if ($field['Hidden']) {
                        $field['value'] = rl('reefless')->parsePhone($profile[$field['Key']], $field, true);
                        $field['value_default'] = rl('reefless')->parsePhone($profile[$field['Key']], $field, false);
                    }
                }
                $field['value'] = AppController::adaptValue($field);
            }
        }
        Profile::prepareURL($profile);

        if ($GLOBALS['config']['membership_module']) {

            $profile['membership_plan'] = $this->getAccountPlan($profile);
        }

        return $profile;
    }

    /**
     * Get plan by account
     **/
    public function getAccountPlan($profile)
    {
        if (!is_array($profile)) {
            $profile = rl('Account')->getProfile((int) $profile, false);
        }

        $membership_plan = rl('MembershipPlan')->getPlanByProfile($profile);
        if (!$membership_plan['ID']) {
            return;
        }

        $out_services = [];
        if ($membership_plan['Services']) {
            $services = rl('MembershipPlan')->getServices();
            foreach($services as $key => $val) {
                $out_services[$key] = $val;
                $out_services[$key]['Status'] = $membership_plan['Services'][$key] ? 'active' : 'inactive';
            }
            $membership_plan['Services'] = $out_services;
        }

        return $membership_plan;
    }

    /**
     * Login attempt, checks if login details are correct
     **/
    public function login()
    {
        global $config;

        $username = $_GET['username'] ? $_GET['username'] : 0;
        $password = $_GET['password'] ? $_GET['password'] : 0;

        if (!$username || !$password) {
            return false;
        }
        $password = urldecode($password);

        $match_field = $config['account_login_mode'] == 'email' ? 'Mail' : 'Username';

        $account = rl('Db')->fetch(array('ID', 'Status', 'Password'), array($match_field => $username), "AND `Status` <> 'incomplete'", 1, 'accounts', 'row');

        $verified = \FLSecurity::verifyPassword($password, $account['Password']);
        if ($verified) {
            $info = $account;
        }

        $response = [];
        if (!$info) {
            $response['status'] = 'error';

            $mess_key = $match_field == 'email' ? 'notice_incorrect_auth_email' : 'notice_incorrect_auth';
            $response['message'] = $GLOBALS['lang'][$mess_key];
        }
        else {
            $account_data = $this->getProfile($info['ID'], false);
            $account_data['password'] = $account['Password'];

            // Allow send message
            $_REQUEST['account_id'] = $account['ID'];
            $_REQUEST['account_password'] = $account['Password'];
            $allowSendMessage = $this->isSendMessage();
            $account_data['allow_send_message'] = $allowSendMessage['allow_send_message'];

            // Shopping cart items
            if ($GLOBALS['plugins']['shoppingCart']) {
                $shoppingItems = [];
                (new ShoppingCartController)->getItems($shoppingItems);
                $account_data['shopping'] = $shoppingItems['shopping'];
                unset($shoppingItems);
            }

            $response['status'] = 'ok';
            $response['data'] = $account_data;
        }

        return $response;
    }

    /**
     * Upload profile photo
     **/
    public function uploadProfilePhoto()
    {
        global $config;

        if ($_REQUEST['account_id'] && $_REQUEST['account_password']) {
            if ($this->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
                $this->getProfile($_REQUEST['account_id']);
                $response = (new ProfileThumbnailUploadAdapter())->uploadFromGlobals();
                if (isset($response['error'])) {
                    $response['status'] = 'error';
                    $response['message'] = $response['error'];
                }
                else {
                    $response['status'] = 'ok';
                }
            }
        }
        else {
            $response['status'] = 'error';
            $response['message'] = $GLOBALS['lang']['error_request_api'];
        }

        return $response;
    }

    /**
     * Update profile email
     **/
    public function updateProfileEmail()
    {
        $account_id = $_POST['account_id'];
        $password = $_POST['account_password'];
        $new_email = $_POST['email'];

        if ($this->issetAccount($account_id, $password)) {

            $email_exist = rl('Db')->getOne('Mail', "`Mail` = '{$new_email}' AND `ID` <> '{$account_id}'", 'accounts');
            if ($email_exist) {
                $response['status'] = 'error';
                $response['message'] = str_replace('{email}', $new_email, $GLOBALS['lang']['notice_account_email_exist']);
            }
            else {
                $update = array(
                    'fields' => array(
                        'Mail_tmp' => $new_email,
                    ),
                    'where' => array(
                        'ID' => $account_id,
                    ),
                );
                rl('Db')->updateOne($update, 'accounts');

                rl('Account')->sendEditEmailNotification($account_id, $new_email);

                $response['status'] = 'success';
                $response['message'] = $GLOBALS['lang']['changes_saved'];
            }
        }
        else {
            $response['status'] = 'error';
            $response['message'] = $GLOBALS['lang']['error_request_api'];
        }

        return $response;
    }

    /**
     * Edit profile
     **/
    public function editProfile()
    {
        global $account_info, $profile_data, $config, $lang;

        $account_id = $_POST['account_id'];
        $password = $_POST['account_password'];

        $profile_data['lang'] = $_POST['language'];
        $profile_data['location'] = $_POST['location'];
        $profile_data['display_email'] = $_POST['display_email'];

        if ($_POST['subscribe']) {
            $_POST['profile']['mn_subscribe'] = $_POST['subscribe'];
        }

        if ($this->issetAccount($account_id, $password)) {
            $account_info = $this->getProfile($account_id, false);

            // validate personal address
            if ($account_info['Own_location']) {
                $location = trim($_POST['location']);
                $wildcard_deny = explode(',', $config['account_wildcard_deny']);

                $deny_pages_tmp = rl('Db')->fetch(['Path'], null, "WHERE `Path` <> ''", null, 'pages');
                foreach ($deny_pages_tmp as $deny_page) {
                    $wildcard_deny[] = $deny_page['Path'];
                }
                unset($deny_pages_tmp);

                $wildcard_deny[] = RL_ADMIN;

                preg_match('/[\W]+/', $location, $matches);

                if (empty($location) || !empty($matches)) {
                    $response['status'] = 'error';
                    $response['message'] = $lang['personal_address_error'];
                } else if (strlen($location) < 3) {
                    $response['status'] = 'error';
                    $response['message'] = $lang['personal_address_length_error'];
                }
                /* check for uniqueness */
                else if (in_array($location, $wildcard_deny)
                    || rl('Db')->getOne('ID', "`Own_address` = '{$location}' AND `ID` != {$account_info['ID']}", 'accounts')) {
                    $response['status'] = 'error';
                    $response['message'] = $lang['personal_address_in_use'];
                }
            }

            rl('Hook')->load('profileEditProfileValidate');

            if ($response['status'] != 'error') {
                $profile_data['mail'] = $account_info['Mail'];

                if (rl('Account')->editProfile($profile_data, $account_id)) {
                    rl('Hook')->load('profileEditProfileDone');

                    $response['status'] = 'success';
                    $response['message'] = $GLOBALS['lang']['changes_saved'];
                }
            }
        }
        else {
            $response['status'] = 'error';
            $response['message'] = $GLOBALS['lang']['error_request_api'];
        }

        return $response;
    }


    public function editAccount()
    {
        global $lang, $errors;

        if ($_POST['post']) {
            $_POST = json_decode($_POST['post'], true);
        }
        $account_id = $_POST['account_id'];
        $password = $_POST['account_password'];


        if ($this->issetAccount($account_id, $password)) {
            $profile_info = rl('Account')->getProfile((int) $account_id, true);

            if ($back_errors = rl('Common')->checkDynamicForm($_POST, $profile_info['Fields'], 'account')) {
                foreach ($back_errors as $error) {
                    $errors .= strip_tags($error);
                }
                $response['status'] = 'error';
                $response['message'] = $errors;
            }
            else {
                rl('Account')->editAccount($_POST, $profile_info['Fields'], $account_id);
                $response['status'] = 'ok';
                $response['message'] = $lang['notice_account_edited'];
            }


        }


        return $response;
    }

    /**
     * Change account password
     **/
    public function changePassword()
    {
        global $lang;

        $account_id = $_POST['account_id'];
        $password = $_POST['password'];
        $new_password = $_POST['password_new'];

        $account = rl('Db')->fetch(array('ID', 'Password'), array('ID' => $account_id), "AND `Status` <> 'incomplete'", 1, 'accounts', 'row');

        $verified = \FLSecurity::verifyPassword($password, $account['Password']);
        if ($verified) {
            $password_hash = $account['Password'];
        }

        if ($this->issetAccount($account_id, $password_hash)) {
            $new_password_hash = \FLSecurity::cryptPassword($new_password);

            $update = array(
                'fields' => array(
                    'Password' => $new_password_hash,
                ),
                'where' => array(
                    'ID' => $account_id,
                ),
            );
            rl('Db')->updateOne($update, 'accounts');
            $response['status'] = 'success';
            $response['message'] = $lang['changes_saved'];
            $response['password'] = $new_password_hash;
        }
        else {
            $response['status'] = 'error';
            $response['message'] = $lang['notice_incorrect_current_pass'];
        }


        return $response;
    }

    /**
     * Get Account type fields
     **/
    public function getAccountTypeFields()
    {
        global $lang;

        $account_id = $_REQUEST['account_id'];
        $password = $_REQUEST['account_password'];

        if ($this->issetAccount($account_id, $password)) {
            $account_info = $this->getProfile($account_id, true);
            $fields = $account_info['Fields'];

            $fields = AppController::adaptFields($account_info['Fields'], $account_info, 'account');

            $response['status'] = 'success';
            $response['fields'] = $fields;
        }

        return $response;
    }



    /**
     * Reset password
     **/
    public function resetPassword()
    {
        global $config;

        $email = $_REQUEST['email'];
        if (!$email) {
            $out['errors'] = 'email';
        } else if (!$out['errors']) {
            /* check email */
            $account_id = rl('Db')->getOne('ID', "`Mail` = '{$email}'", 'accounts');

            if (!$account_id) {
                $out['errors'] = 'email';
            }
        }

        /* send "reset password" link */
        if (!$out['errors']) {
            $profile_info = rl('Account')->getProfile((int) $account_id);

            $mail_tpl = rl('Mail')->getEmailTemplate('remind_password_request');

            $hash_key = rl('reefless')->generateHash();
            $hash = md5($hash_key) . md5($config['security_key']);

            $sql = "UPDATE `{db_prefix}accounts` SET `Password_hash` = '{$hash_key}' WHERE `ID` = '{$account_id}' LIMIT 1";
            rl('Db')->query($sql);

            $link = rl('reefless')->getPageUrl('remind', false, false, 'hash=' . $hash );
            $link = '<a href="' . $link . '">' . $link . '</a>';

            $mail_tpl['body'] = str_replace(
                array('{link}', '{name}'),
                array($link, $profile_info['Full_name']),
                $mail_tpl['body']
            );
            rl('Mail')->send($mail_tpl, $email);

            $out['message'] = $GLOBALS['lang']['reset_password_link_sent_app'];
            $out['success'] = true;
        }

        $response = $out;

        return $response;
    }

    /**
     * Get account forms
     **/
    public function getAccountForms()
    {
        $type_fields = array();

        $account_types = $this->getAccountTypes();

        foreach ($account_types as $type) {
            $fields = rl('Account')->getFields($type['ID']);
            $fields = rl('Lang')->replaceLangKeys($fields, 'account_fields', array('name', 'default', 'description'));
            $fields = rl('Common')->fieldValuesAdaptation($fields, 'account_fields');
            $type_fields['fields'][$type['Key']] = AppController::adaptFields($fields, [], 'account');
        }

        // Get a list with agreement fields
        $agreement = array();
        $agreement_tmp = rl('Account')->getAgreementFields();

        foreach ($agreement_tmp as $value) {
            $tmp_item['Key'] = $value['Key'];
            $pageInfo = rl('Db')->fetch(
                array('Page_type', 'Key', 'Controller'),
                array('Status' => 'active', 'Key' => $value['Default']),
                null,
                null,
                'pages',
                'row'
            );

            $tmp_item['Type'] = 'accept';
            $tmp_item['Page_type'] = $pageInfo['Page_type'];
            $tmp_item['Required'] = '1';
            $tmp_item['name'] = $GLOBALS['lang']['pages+name+' . $value['Default']];

            switch ($pageInfo['Page_type']) {
                case 'system':
                    $tmp_item['value'] = rl('reefless')->getPageUrl($pageInfo['Key']);
                    break;

                case 'static':

                    $tmp_item['value'] = rl('Lang')->getPhrase('pages+content+' . $value['Default'], null, null, true);
                    break;

                case 'external':
                    $tmp_item['value'] = $pageInfo['Controller'];
                    break;
            }
            if ($value['Values']) {
                foreach (explode(',', $value['Values']) as $val) {
                    $agreement[$val][$tmp_item['Key']] = $tmp_item;
                }
            } else {
                foreach ($account_types as $type) {
                    $agreement[$type['Key']][$tmp_item['Key']] = $tmp_item;
                }
            }
        }
        $type_fields['agreement'] = $agreement;

        if ($GLOBALS['config']['membership_module'] && $_REQUEST['registration']) {
            $plans = [];
            foreach ($account_types as $type) {
                $plans[$type['Key']] = $this->getMembershipPlans($type['Key']);
            }
            $type_fields['plans'] = $plans;
        }

        $response = $type_fields;

        return $response;
    }

    /**
     * Get membership plans by type
     **/
    public function membershipPlansByType()
    {
        if ($this->issetAccount($_POST['account_id'], $_POST['account_password'])) {
            $response['plans'] = $this->getMembershipPlans($_POST['account_type']);
        }
        $response['status'] = 'ok';
        return $response;
    }

    /**
     * Get memberships
     **/
    public function getMembershipPlans($type)
    {
        $plans = rl('MembershipPlan')->getPlansByType($type);
        foreach($plans as $key => &$plan) {
            foreach($plan['Services'] as $k => &$service) {
                $service['name'] = rl('Lang')->getPhrase('membership_services+name+' . $service['Key']);
            }
        }
        return $plans;
    }

    /**
     * Upgrade membership plan
     **/
    public function upgradeAccountPlan()
    {
        $account_id = $_POST['account_id'];
        $password = $_POST['account_password'];

        if ($this->issetAccount($account_id, $password)) {
            $plan_id = $_POST['plan_id'];
            $renew = $_POST['renew'] ? true : false;
            $new = $_POST['new'] ? true : false;

            rl('Account')->upgrade($account_id, $plan_id, $renew, $new);
            $response['plan'] = $this->getAccountPlan($account_id);
        }
        $response['status'] = 'ok';

        return $response;
    }


    /**
     * Get form for social auth
     *
     * @return array of the user
     **/
    public function getAccountSocialAuthForms()
    {
        $types = $this->getAccountTypes();

        // Get a list with agreement fields
        $agreement = array();
        $agreement_tmp = rl('Account')->getAgreementFields();

        foreach ($agreement_tmp as $value) {
            $tmp_item['Key'] = $value['Key'];
            $pageInfo = rl('Db')->fetch(
                array('Page_type', 'Key', 'Controller'),
                array('Status' => 'active', 'Key' => $value['Default']),
                null,
                null,
                'pages',
                'row'
            );

            $tmp_item['Type'] = 'accept';
            $tmp_item['Page_type'] = $pageInfo['Page_type'];
            $tmp_item['Required'] = '1';
            $tmp_item['name'] = $GLOBALS['lang']['pages+name+' . $value['Default']];

            switch ($pageInfo['Page_type']) {
                case 'system':
                    $tmp_item['value'] = rl('reefless')->getPageUrl($pageInfo['Key']);
                    break;

                case 'static':
                    $tmp_item['value'] = rl('Lang')->getPhrase('pages+content+' . $value['Default'], null, null, true);
                    break;

                case 'external':
                    $tmp_item['value'] = $pageInfo['Controller'];
                    break;
            }
            if ($value['Values']) {
                foreach (explode(',', $value['Values']) as $val) {
                    $agreement[$val][$tmp_item['Key']] = $tmp_item;
                }
            } else {
                foreach ($types as $type) {
                    $agreement[$type['Key']][$tmp_item['Key']] = $tmp_item;
                }
            }
        }
        return $agreement;
    }

    /**
     * Login or regist by plugin socail auth
     *
     * @param array  $data - user data
     *
     * @return array of the user
     **/
    public function hybridAuthLogin()
    {
        if ($GLOBALS['plugins']['hybridAuthLogin'] && $_REQUEST['provider']) {
            rl('reefless')->loadClass('HybridAuthLogin', null, 'hybridAuthLogin');
            $hybridAuthApi = new \Flynax\Plugins\HybridAuth\API();
            $GLOBALS['config']['security_login_attempt_user_module'] = 0;

            $userData = [
                'fid' => $_REQUEST['fid'],
                'email' => $_REQUEST['email'],
                'provider' => $_REQUEST['provider'],
                'first_name' => $_REQUEST['first_name'],
                'last_name' => $_REQUEST['last_name'],
                'verified' => 1,
            ];

            if ($_REQUEST['account_type_id'] && $_REQUEST['account_type_id'] != 'will-be-set') {
                $userData['account_type'] = $_REQUEST['account_type_id'];
            }

            $result = $hybridAuthApi->processUser($userData);

            $response['action'] = $result['action'];

            switch ($result['action']) {
                case 'login':
                case 'registered':
                    $account_data = $this->getProfile($result['user_data']['ID']);
                    $account_data['password'] = rl('Db')->getOne('Password', "`ID` = '{$result['user_data']['ID']}'", 'accounts');

                    $response['account_data'] = $account_data;

                    $response['status'] = 'ok';
                    break;

                case 'need_register':
                    $response['data'] = $this->getAccountSocialAuthForms();
                    $response['status'] = 'ok';
                    break;

                case 'validation':
                    $errors = [];
                    foreach ($result['errors'] as $key => $value) {
                        $errors['error_' . $key] = str_replace('"', "'", $value);
                    }
                    $response['errors'] = $errors;
                    break;
            }
        }



        return $response;
    }

    /**
     * Verify user password
     *
     * @return array $result - of the user
     **/
    public function hybridAuthLoginVerifyPassword()
    {
        $email = $_REQUEST['email'];
        $password = $_REQUEST['password'];
        rl('reefless')->loadClass('HybridAuthLogin', null, 'hybridAuthLogin');
        if ($password && rl('HybridAuthLogin')->verifyUserPasswordByEmail($email, $password)) {
            $user_id = rl('Db')->getOne('ID', "`Mail` = '{$email}'", 'accounts');
            $account_data = $this->getProfile($user_id, false);
            $account_data['password'] = rl('Db')->getOne('Password', "`ID` = '{$user_id}'", 'accounts');
            $result['data'] = $account_data;
        } else {
            $result['message'] = $GLOBALS['lang']['dialog_password_incorrect'];
        }
        return $result;
    }

    /**
     * Save push token
     *
     * @param array  $data - data
     *
     * @return bool
     **/
    public function savePushToken()
    {
        $account_id = $_POST['account_id'];
        $phoneUniqID = $_POST['uniqID'];
        $tokenID = $_POST['tokenID'];
        $push_lang = $_POST['lang'];
        $platform = $_POST['platform'];
        $status = $_POST['status'] == '1' ? 'active' : 'inactive';

        if ($tokenID) {
            if (rl('Db')->getOne('Phone_ID', "`Phone_ID` = '{$phoneUniqID}'", 'api_push_tokens')) {
                $update = array(
                    'fields' => array(
                        'Token' => $tokenID,
                        'Language' => $push_lang,
                        'Platform' => $platform,
                        'Status' => $status,
                    ),
                    'where'  => array(
                        'Phone_ID' => $phoneUniqID,
                    ),
                );
                if($account_id) {
                    $update['fields']['Account_ID'] = $account_id;
                }

                rl('Db')->updateOne($update, 'api_push_tokens');
            }
            else {
                $insert = array(
                    'Account_ID' => $account_id,
                    'Phone_ID' => $phoneUniqID,
                    'Token' => $tokenID,
                    'Language' => $push_lang,
                    'Platform' => $platform,
                    'Status' => $status,
                );
                rl('Db')->insertOne($insert, 'api_push_tokens');
            }
        }
        $response['result'] = true;
        return $response;
    }

    /**
     * Save phone click
     *
     * @param array $_REQUEST
     *
     * @return bool
     **/
    public function savePhoneClick()
    {
        if ($listingID = (int) $_REQUEST['listing_id']) {
            rl('Db')->insertOne([
                'Listing_ID' => $listingID,
                'Account_ID' => $_REQUEST['account_id'] ? $_REQUEST['account_id'] : 0,
                'Date'       => 'NOW()',
                'IP'         => \Flynax\Utils\Util::getClientIP(),
            ], 'phone_clicks');
            $response['status'] = 'OK';
        }
        return $response;
    }

    /**
     * Buld photos url
     *
     * @param array $accounts - accounts
     *
     * @return array - accounts information
     **/
    public function buildPhotosAccountUrl(&$accounts)
    {
        if ($accounts['ID']) {
            if ($accounts['Photo']) {
                $accounts['Photo'] = RL_FILES_URL . $accounts['Photo'];
            }

            if ($accounts['Photo_x2']) {
                $accounts['Photo_x2'] = RL_FILES_URL . $accounts['Photo_x2'];
            }

            rl('Hook')->load('phpApiBuildAccountPhotoUrl', $accounts);
        }
        else {
            foreach ($accounts as &$account) {
                if ($account['Photo']) {
                    $account['Photo'] = RL_FILES_URL . $account['Photo'];
                }
                if ($account['Photo_x2']) {
                    $account['Photo_x2'] = RL_FILES_URL . $account['Photo_x2'];
                }

                rl('Hook')->load('phpApiBuildAccountPhotoUrl', $account);
            }
        }
    }

    /**
     * Get account forms
     **/
    public function getMainAccounts()
    {
        $accounts = [];

        $typeKeys = $GLOBALS['config']['app_account_types'];
        if (!$typeKeys) {
            return $accounts;
        }
        $typeKeys = explode(',', $typeKeys);
        $isFeatured = false;
        if ($typeKeys[0] == 'featured') {
            $isFeatured = true;
            unset($typeKeys[0]);
        }

        foreach($typeKeys as $key) {
            if ($isFeatured) {
                $accounts[$key] = rl('Account')->getFeatured(
                    $key,
                    $GLOBALS['config']['app_account_types_count'],
                    false
                );
            }
            else {
                if ($GLOBALS['plugins']['multiField'] && rl('GeoFilter', null, 'multiField')->geo_format) {
                    $pageKey = 'at_' . $key;
                    $geoLocationController = new GeoLocationController;
                    $geoLocationController->appliedLocation($pageKey);
                }
                // Set global alphabet
                global $alphabet;
                $alphabet = explode(',', $GLOBALS['lang']['alphabet_characters']);
                $alphabet = array_map('trim', $alphabet);

                $sorting = [];
                $sortBy = $_REQUEST['sort_by'] ? $_REQUEST['sort_by'] : '';
                $sortType = $_REQUEST['sort_type'] ? $_REQUEST['sort_type'] : 'desc';

                $accounts[$key] = rl('Account')->getDealersByChar(
                    '',
                    $GLOBALS['config']['app_account_types_count'],
                    1,
                    rl('AccountTypes')->types[$key],
                    $sorting,
                    $sortBy,
                    $sortType
                );
            }
            if ($accounts[$key]) {
                Profile::prepareURL($accounts[$key]);
            }
        }

        return $accounts;
    }

    /**
     * Get account search form
     *
     * return @array
     **/
    public function getAccountSearchForm()
    {
        $id = $_REQUEST['id'];
        $form = rl('Account')->buildSearch($id);
        $response['fields'] = AppController::adaptFields($form, [], 'account');
        return $response;
    }

    /**
     * Get accounts by char
     *
     * return @array
     **/
    public function getDealersByChar()
    {
        // Set global alphabet
        global $alphabet;
        $alphabet = explode(',', $GLOBALS['lang']['alphabet_characters']);
        $alphabet = array_map('trim', $alphabet);

        $accounts = [];

        $char = $_REQUEST['char'];
        $start = $_REQUEST['start'];
        $accountType = rl('Account')->getTypeDetails($_REQUEST['account_type']);
        $sorting = [];
        $sortBy = $_REQUEST['sort_by'] ? $_REQUEST['sort_by'] : '';
        $sortType = $_REQUEST['sort_type'] ? $_REQUEST['sort_type'] : 'desc';

        if ($GLOBALS['plugins']['multiField'] && rl('GeoFilter', null, 'multiField')->geo_format) {
            $pageKey = 'at_' . $_REQUEST['account_type'];
            $geoLocationController = new GeoLocationController;
            $geoLocationController->appliedLocation($pageKey);
        }

        $accounts = rl('Account')->getDealersByChar(
            $char,
            $GLOBALS['config']['dealers_per_page'],
            $start,
            $accountType,
            $sorting,
            $sortBy,
            $sortType
        );

        if ($accounts) {
            Profile::prepareURL($accounts);
        }

        $response['accounts'] = $accounts;
        $response['count'] = rl('Account')->calc_alphabet;

        return $response;
    }

    /**
     * Search accounts
     *
     * return @array
     **/
    public function searchDealers()
    {
        $accounts = [];

        $data = json_decode($_POST['form'], true);
        $start = $_REQUEST['start'];
        $accountType = rl('Account')->getTypeDetails($_REQUEST['account_type']);
        $fields = rl('Account')->buildSearch($accountType['ID']);

        if ($GLOBALS['plugins']['multiField'] && rl('GeoFilter', null, 'multiField')->geo_format) {
            $pageKey = 'at_' . $_REQUEST['account_type'];
            $geoLocationController = new GeoLocationController;
            $geoLocationController->appliedLocation($pageKey);
        }

        $accounts = rl('Account')->searchDealers(
            $data,
            $fields,
            $GLOBALS['config']['dealers_per_page'],
            $start,
            $accountType
        );

        if ($accounts) {
            Profile::prepareURL($accounts);
        }

        $response['accounts'] = $accounts;
        $response['count'] = rl('Account')->calc;
        return $response;
    }

    // if (defined('API_START')) {
    //     $f = fopen(RL_PLUGINS . 'api'.RL_DS.'log.txt', 'w+');
    //     ob_start();
    //     print_r($response);
    //     $out = ob_get_clean();
    //     fputs($f, $out);
    //     fclose($f);
    // }
}
