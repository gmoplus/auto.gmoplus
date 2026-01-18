<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLCLAIMLISTING.CLASS.PHP
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

use Flynax\Utils\Util;

class rlClaimListing extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * Path of plugin directory
     * @since 1.3.0
     * @string
     */
    public const PLUGIN_DIR = RL_PLUGINS . 'claimListing/';

    /**
     * Path of view directory for admin side
     * @since 1.3.0
     * @string
     */
    public const ADMIN_VIEW_DIR = self::PLUGIN_DIR . 'admin/view/';

    /**
     * Add claim direct field to account
     *
     * @hook apTplAccountsForm
     */
    public function hookApTplAccountsForm()
    {
        global $lang, $account_info;

        if ($GLOBALS['config']['cl_module']) {
            $claim_direct_html = "
                <table class=\"form\">
                <tr>
                    <td class=\"name\">{$lang['cl_allow_to_claim']}</td>
                    <td class=\"field\">
                        <label><input type=\"radio\" name=\"profile[cl_direct]\" value=\"1\" " . ($account_info['cl_direct'] ? "checked=\"checked\"" : "") . " /> {$lang['yes']}</label>
                        <label><input type=\"radio\" name=\"profile[cl_direct]\" value=\"0\" " . (!$account_info['cl_direct'] ? "checked=\"checked\"" : "") . " /> {$lang['no']}</label>
                    </td>
                </tr>
                </table>";

            echo $claim_direct_html;
        }
    }

    /**
     * Get listing info
     *
     * @param int $id
     */
    public function getListingInfo($id = 0)
    {
        global $rlDb;

        $id = (int) $id;

        if (!$id) {
            return;
        }

        /* get listing info */
        $sql = "SELECT * FROM `" . RL_DBPREFIX . "listings` ";
        $sql .= "WHERE `ID` = {$id} AND `Status` = 'active' LIMIT 1";
        $listing_info = $rlDb->getRow($sql);

        /* get owner info */
        if ($listing_info['Account_ID']) {
            $sql = "SELECT * FROM `" . RL_DBPREFIX . "accounts` ";
            $sql .= "WHERE `ID` = {$listing_info['Account_ID']} AND `Status` = 'active' LIMIT 1";
            $owner_info = $rlDb->getRow($sql);

            $listing_info['Owner_info'] = $owner_info;
        }

        return $listing_info;
    }

    /**
     * Claim listing process
     *
     * @param array $claim_request - Claim request info (method,recieved code)
     */
    public function claimAd($claim_request = array())
    {
        global $account_info, $listing_info, $lang, $config, $reefless, $rlDb, $rlValid, $rlMail, $rlAccount, $rlSmarty,
        $rlCrop, $rlResize;

        $listing_id = (int) $_GET['id'];

        if (!$listing_info) {
            $listing_info = $this->getListingInfo($listing_id);
        }

        if (!$claim_request['method'] || !$listing_info['ID']) {
            return;
        }

        /* registration or login */
        if (!defined('IS_LOGIN')) {
            $reg_username = $_POST['reg_username'];
            $reg_email    = $_POST['reg_email'];
            $reg_type     = $_POST['reg_type'];
            $log_username = $_POST['log_username'];
            $log_password = $_POST['log_password'];

            // build user name if user set email and select account type only
            if ($reg_type && !$reg_username) {
                $reg_username = explode('@', $reg_email);
                $reg_username = $rlAccount->makeUsernameUnique($reg_username[0]);
            }

            /* quick registration process */
            if ($reg_username && $reg_email) {
                /* checking, if this email exist already */
                if ($exist_email = $rlDb->getOne('ID', "`Mail` = '{$reg_email}' AND `Status` <> 'trash'", 'accounts')) {
                    $message['errors'] = str_replace(
                        '{email}',
                        '<span class="field_error">' . $reg_email . '</span>',
                        $lang['notice_account_email_exist']
                    );
                    $message['error_fields'] = 'reg_email';
                }

                /* validation */
                if (!$rlValid->isEmail($reg_email)) {
                    $message['errors']       = $lang['notice_bad_email'];
                    $message['error_fields'] = 'reg_email';
                }

                /* quick registration */
                if (!isset($message['errors']) && !isset($message['error_fields'])) {
                    if ($new_account = $rlAccount->quickRegistration($reg_username, $reg_email, 0, $reg_type)) {
                        $rlAccount->login($new_account[0], $new_account[1]);

                        $rlSmarty->assign('isLogin', $_SESSION['username']);
                        define('IS_LOGIN', true);

                        $account_info = $_SESSION['account'];
                        $rlSmarty->assign_by_ref('account_info', $account_info);

                        /* send login details to user */
                        $reefless->loadClass('Mail');
                        $mail_tpl = $rlMail->getEmailTemplate('quick_account_created');
                        $find     = array('{login}', '{password}', '{name}');
                        $replace  = array($new_account[0], $new_account[1], $account_info['Full_name']);

                        $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
                        $rlMail->send($mail_tpl, $reg_email);
                    }
                }
            }

            /* login process */
            if ($log_username && $log_password) {
                if (true === $res = $rlAccount->login($log_username, $log_password)) {
                    $rlSmarty->assign('isLogin', $_SESSION['username']);
                    define('IS_LOGIN', true);

                    $account_info = $_SESSION['account'];
                    $rlSmarty->assign_by_ref('account_info', $account_info);

                    // prevent claim own listings
                    if ($account_info['ID'] == $listing_info['Account_ID']) {
                        $reefless->referer();
                    }
                } else {
                    $message['errors']       = $res;
                    $message['error_fields'] = 'log_username,log_password';
                }
            }
        }

        $reefless->loadClass('Actions');

        /* claim process */
        switch ($claim_request['method']) {
            case 'phone':
            case 'email':
                if ($claim_request['received_code'] == base64_decode($_SESSION['cl_code']) && $account_info['ID']) {
                    // add claim data
                    $claim_info = array(
                        'Date'         => 'NOW()',
                        'Claim_method' => $claim_request['method'],
                        'Data'         => $claim_request['method'] == 'phone'
                        ? $listing_info[$config['cl_phone_field']]
                        : $listing_info[$config['cl_email_field']],
                        'Account_ID'   => $account_info['ID'],
                        'Listing_ID'   => $listing_info['ID'],
                        'IP'           => Util::getClientIP(),
                        'Status'       => 'active',
                    );

                    if ($claim_request['phone_listings']) {
                        $claim_info['Listings_IDs'] = $this->getListingsByPhone(
                            $listing_info[$config['cl_phone_field']],
                            $listing_info['Account_ID'],
                            true
                        );
                    } else if ($claim_request['email_listings']) {
                        $claim_info['Listings_IDs'] = $this->getListingsByEmail(
                            $listing_info[$config['cl_email_field']],
                            $listing_info['Account_ID'],
                            true
                        );
                    }
                    $rlDb->insert($claim_info, 'claim_requests');

                    // change owner of listing and deny to claim this listing
                    if ($claim_request['phone_listings']) {
                        $where = array(
                            'Account_ID'              => $listing_info['Account_ID'],
                            $config['cl_phone_field'] => $listing_info[$config['cl_phone_field']],
                        );
                    } else if ($claim_request['email_listings']) {
                        $where = array(
                            'Account_ID'              => $listing_info['Account_ID'],
                            $config['cl_email_field'] => $listing_info[$config['cl_email_field']],
                        );
                    } else {
                        $where = array('ID' => $listing_info['ID']);
                    }
                    $rlDb->update(
                        array(
                            'fields' => array(
                                'Account_ID' => $account_info['ID'],
                                'cl_direct'  => '0',
                            ),
                            'where'  => $where,
                        ),
                        'listings'
                    );

                    $message = $lang['cl_success'];
                    unset($_SESSION['cl_code']);
                } elseif (!$message) {
                    $message = $lang['cl_fail'];
                }
                break;
            case 'form':
                if ($_FILES['attached_img']['name'] && $account_info['ID']) {
                    $img_ext  = end(explode('.', $_FILES['attached_img']['name']));
                    $img_name = 'claim_img_' . time() . mt_rand() . '.' . $img_ext;

                    $dir = RL_FILES . 'claim_images' . RL_DS;
                    $reefless->rlMkdir($dir);

                    $img_location = $dir . $img_name;

                    // move attached img
                    if (move_uploaded_file($_FILES['attached_img']['tmp_name'], $img_location)) {
                        $reefless->loadClass('Crop');
                        $reefless->loadClass('Resize');

                        $rlCrop->loadImage($img_location);
                        $rlCrop->cropBySize(
                            $config['pg_upload_large_width'] ? $config['pg_upload_large_width'] : 640,
                            $config['pg_upload_large_height'] ? $config['pg_upload_large_height'] : 480
                        );
                        $rlCrop->saveImage($img_location, $config['img_quality']);
                        $rlCrop->flushImages();

                        $rlResize->resize(
                            $img_location,
                            $img_location,
                            'C',
                            array(
                                $config['pg_upload_large_width'] ? $config['pg_upload_large_width'] : 640,
                                $config['pg_upload_large_height'] ? $config['pg_upload_large_height'] : 480,
                            ),
                            true,
                            false
                        );
                    }

                    // create claim request
                    if (is_readable($img_location) && is_readable($img_location)) {
                        $request_info = array(
                            'Date'         => 'NOW()',
                            'Claim_method' => 'image',
                            'Data'         => $img_name,
                            'Account_ID'   => $account_info['ID'],
                            'Listing_ID'   => $listing_info['ID'],
                            'IP'           => Util::getClientIP(),
                        );

                        $success    = $rlDb->insertOne($request_info, 'claim_requests');
                        $request_id = $rlDb->insertID();
                    } else {
                        $GLOBALS['rlDebug']->logger("Can't upload attached image for claim request");
                        $message = $lang['cl_image_upload_fail'];
                    }

                    if ($success) {
                        $message = $lang['cl_request_created'];

                        $link = RL_URL_HOME . ADMIN;
                        $link .= '/index.php?controller=claimListing&action=view&item=' . $request_id;
                        $link = "<a href=\"{$link}\">{$lang['view_details']}</a>";

                        // send request details to admin
                        $reefless->loadClass('Mail');
                        $mail_tpl = $rlMail->getEmailTemplate('claim_request_admin');
                        $mail_tpl['body'] = strtr($mail_tpl['body'], ['{link}' => $link]);

                        $rlMail->send($mail_tpl, $config['notifications_email']);
                    }
                } elseif (!$message) {
                    $message = $lang['cl_fail'];
                }
                break;
        }

        return $message;
    }

    /**
     * Send code for confirmation
     *
     * @since 1.1.0 - Package changed from xAjax to ajax
     * @package ajax
     *
     * @param string $claim_method - Method of confirmation
     * @param int    $listing_id
     */
    public function ajaxSendCode($claim_method = '', $listing_id = 0)
    {
        global $lang, $config, $rlMail, $reefless;

        $listing_info = $GLOBALS['rlListings']->getListing($listing_id);

        if (!$listing_info || !$claim_method || !function_exists('curl_version')) {
            return array(
                'status'  => 'ERROR',
                'message' => $lang['cl_send_code_notify_fail'],
            );
        }

        // generation code
        $code                = rand(1000, 9999);
        $_SESSION['cl_code'] = base64_encode($code);

        // SMS method
        if ($claim_method == 'phone') {
            $phone_number = $listing_info[$config['cl_phone_field']];

            // sending SMS to phone number
            if ($phone_number) {
                // validate phone number
                if (false !== strpos($phone_number, 'c:')) {
                    $field = $GLOBALS['rlDb']->fetch(
                        array('Opt1'),
                        array('Key' => $config['cl_phone_field']),
                        null,
                        1,
                        'listing_fields',
                        'row'
                    );
                    $phone_number = $reefless->parsePhone($phone_number, $field);
                }
                $phone_number = str_replace(['+', '-', '(', ')', ' ', 'a:', '|n:'], '', $phone_number);

                // Build SMS text
                $sms_text = strtr(
                    $lang['cl_sms_text'],
                    ['{code}' => $code, '{site_name}' => $lang['pages+title+home'], '<br/>' => ' ']
                );

                if ($config['cl_sms_service'] === 'SMS.RU') {
                    $data         = new stdClass();
                    $data->to     = $phone_number;
                    $data->text   = $sms_text;
                    $data->api_id = $config['cl_sms_ru_api_key_rest'];

                    // Allows to send the SMS in test mode without sending real message for checking response
                    // $data->test = 1;

                    $url = 'https://sms.ru/sms/send?json=1&' . http_build_query((array) $data);
                    $result = $reefless->getPageContent($url);
                    $result = $result ? json_decode($result) : null;

                    if ($result && $result->status === 'OK' && $result->sms) {
                        $response = (array) $result->sms;
                        $response = array_pop($response);

                        if ($response->status === 'ERROR' && $response->status_text) {
                            $error = $response->status_text;
                            $GLOBALS['rlDebug']->logger('Claim Listing Plugin, SMS.RU Error: ' . $error);
                        }
                    } else {
                        $error = $lang['cl_send_code_notify_fail'];
                    }
                } else {
                    // send SMS via REST API
                    if ($config['cl_clickatell_api_key_rest']) {
                        require_once __DIR__ . '/vendor/autoload.php';

                        $clickatell = new \Clickatell\Rest($config['cl_clickatell_api_key_rest']);

                        $data = ['to' => [$phone_number], 'content' => $sms_text];

                        if ($config['cl_clickatell_api_phone']) {
                            $fromNumber = str_replace(
                                ['+', '-', '(', ')', ' ', 'a:', '|n:'],
                                '',
                                $config['cl_clickatell_api_phone']
                            );

                            $data['from'] = $fromNumber;
                        }

                        try {
                            $result = $clickatell->sendMessage($data);
                            $error  = $result[0]['error'];
                        } catch (\Clickatell\ClickatellException $e) {
                            $error = $lang['cl_send_code_notify_fail'];
                            $GLOBALS['rlDebug']->logger('Claim Listing Plugin, Clickatell Error: ' . $e->getMessage());
                        }
                    }
                    // send SMS via HTTP API
                    else {
                        $request = 'user=' . $config['cl_clickatell_username'] . '&password=';
                        $request .= $config['cl_clickatell_password'] . '&api_id=' . $config['cl_clickatell_api_id'];
                        $request .= '&to=' . $phone_number . '&text=' . $sms_text;

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, 'http://api.clickatell.com/http/sendmsg');
                        curl_setopt($ch, CURLOPT_HEADER, 0);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        $response = curl_exec($ch);
                        curl_close($ch);

                        $error = false === strpos($response, 'ID:') ? $response : '';
                    }
                }

                if ($error) {
                    return array(
                        'status'  => 'ERROR',
                        'message' => str_replace('{error}', '<b>' . $error . '</b>', $lang['cl_sms_fail']),
                    );
                }
            } else {
                return array(
                    'status'  => 'ERROR',
                    'message' => str_replace(
                        '{field}',
                        '<b>' . $lang['listing_fields+name+' . $config['cl_phone_field']] . '</b>',
                        $lang['cl_field_missing']
                    ),
                );
            }
        }
        /* EMAIL method */
        elseif ($claim_method == 'email') {
            if ($listing_info[$config['cl_email_field']]) {
                $reefless->loadClass('Mail');

                // send request to listing email
                $mail_tpl         = $rlMail->getEmailTemplate('claim_request');
                $mail_tpl['body'] = str_replace('{code}', $code, $mail_tpl['body']);
                $rlMail->send($mail_tpl, $listing_info[$config['cl_email_field']]);
            } else {
                return array(
                    'status'  => 'ERROR',
                    'message' => str_replace(
                        '{field}',
                        '<b>' . $lang['listing_fields+name+' . $config['cl_email_field']] . '</b>',
                        $lang['cl_field_missing']
                    ),
                );
            }
        }

        return array('status' => 'OK');
    }

    /**
     * Check recieved code
     *
     * @since 1.1.0 - Package changed from xAjax to ajax
     * @package ajax
     *
     * @param string $code - Code for confirmation
     */
    public function ajaxCheckCode($code = '')
    {
        global $lang;

        $code = (string) $code;

        if (!$code || !$_SESSION['cl_code']) {
            return false;
        }

        if ($code == base64_decode($_SESSION['cl_code'])) {
            return array('status' => 'OK', 'message' => $lang['cl_correct_code']);
        } else {
            return array('status' => 'ERROR', 'message' => $lang['cl_wrong_code']);
        }
    }

    /**
     * Delete claim request details
     *
     * @since 1.1.0 - Package changed from xAjax to ajax
     * @package ajax
     *
     * @param int $id
     */
    public function ajaxDeleteClaimRequest($id = 0)
    {
        global $lang, $rlDb;

        $id = (int) $id;

        if (!$id) {
            return array('status' => 'ERROR', 'message' => $lang['cl_remove_request_notify_fail']);
        }

        // get info about claim request
        $info = $rlDb->getRow("SELECT `Claim_method`, `Data` FROM `{db_prefix}claim_requests` WHERE `ID` = {$id}");

        // remove attached image
        if ($info['Data'] && $info['Claim_method'] == 'image') {
            unlink(RL_FILES . 'claim_images/' . $info['Data']);
        }

        // remove claim request
        $rlDb->query("DELETE FROM `" . RL_DBPREFIX . "claim_requests` WHERE `ID` = {$id} LIMIT 1");

        return array('status' => 'OK', 'message' => $lang['cl_request_deleted']);
    }

    /**
     * Get claim requests (admin side)
     *
     * @param int $start - Start position
     * @param int $limit - Limit of count request
     */
    public function getClaimRequests($start = 0, $limit = 0)
    {
        global $rlDb, $rlListings, $lang;

        if (!is_numeric($start) || !is_numeric($limit)) {
            return;
        }

        $GLOBALS['reefless']->loadClass('Account');

        /* get general of claim requests */
        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM `" . RL_DBPREFIX . "claim_requests` ";
        $sql .= "ORDER BY `ID` ASC LIMIT {$start}, {$limit}";
        $cl_requests = $rlDb->getAll($sql);

        /* get count of claim requests */
        $count_requests = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

        foreach ($cl_requests as &$cl_request) {
            // get listing title and build url
            $listing               = $rlListings->getListing($cl_request['Listing_ID'], true);
            $link                  = RL_URL_HOME . ADMIN . '/index.php?controller=listings&action=view&id=' . $cl_request['Listing_ID'];
            $cl_request['Listing'] = '<a target="_blank" alt="' . $lang['listing_details'] . '" title="';
            $cl_request['Listing'] .= $lang['listing_details'] . "\" href=\"{$link}\">{$listing['listing_title']}</a>";

            // get account info
            $account_info = $GLOBALS['rlAccount']->getProfile((int) $cl_request['Account_ID']);

            // get account name and build url
            if ($account_info) {
                $link = RL_URL_HOME . ADMIN . '/index.php?controller=accounts&action=view&userid=';
                $link .= $cl_request['Account_ID'];

                $cl_request['Account_name'] = $account_info['Full_name'];
                $cl_request['Account'] = '<a target="_blank" alt="' . $lang['view_account'] . '" title="';
                $cl_request['Account'] .= $lang['view_account'] . '" href="' . $link . '">' . $cl_request['Account_name'];
                $cl_request['Account'] .= '</a>';
            } else {
                $cl_request['Account'] .= $lang['not_available'];
            }

            // upper case of claim method
            $cl_request['Claim_method'] = $cl_request['Claim_method'] === 'image'
                ? $lang['photo']
                : ucfirst($cl_request['Claim_method']);

            // set status (Confirmed/Pending)
            $cl_request['Status'] = $cl_request['Status'] == 'active'
            ? $lang['cl_confirmed']
            : $lang[$cl_request['Status']];
        }

        return array('data' => $cl_requests, 'count' => $count_requests);
    }

    /**
     * Get claim info (admin side)
     */
    public function prepareRequestDetails()
    {
        global $lang, $claim_info, $rlDb, $rlListings, $rlSmarty, $rlListingTypes, $rlAccount;

        /* get listing info */
        $listing_id = (int) $claim_info['Listing_ID'];

        if ($listing_id) {
            $sql = "SELECT `T1`.*, `T2`.`Path`, `T2`.`Type` AS `Listing_type`, `T2`.`Key` AS `Category_key`, ";
            $sql .= "`T3`.`Image`, `T3`.`Image_unlim`, `T3`.`Video`, `T3`.`Video_unlim` ";
            $sql .= "FROM `" . RL_DBPREFIX . "listings` AS `T1` ";
            $sql .= "LEFT JOIN `" . RL_DBPREFIX . "categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
            $sql .= "LEFT JOIN `" . RL_DBPREFIX . "listing_plans` AS `T3` ON `T1`.`Plan_ID` = `T3`.`ID` ";
            $sql .= "LEFT JOIN `" . RL_DBPREFIX . "accounts` AS `T5` ON `T1`.`Account_ID` = `T5`.`ID` ";
            $sql .= "WHERE `T1`.`ID` = '{$listing_id}' LIMIT 1";
            $listing_data                  = $rlDb->getRow($sql);
            $listing_data['category_name'] = $lang['categories+name+' . $listing_data['Category_key']];
            $rlSmarty->assign_by_ref('listing_data', $listing_data);
        }

        /* define listing type */
        if ($listing_data['Listing_type']) {
            $listing_type = $rlListingTypes->types[$listing_data['Listing_type']];
            $rlSmarty->assign_by_ref('listing_type', $listing_type);
        }

        /* build listing structure */
        if ($listing_data['Category_ID'] && $listing_type) {
            $listing = $rlListings->getListingDetails($listing_data['Category_ID'], $listing_data, $listing_type);
            $rlSmarty->assign('listing', $listing);
        }

        /* get listing title */
        if ($listing_data['Category_ID'] && $listing_type['Key']) {
            $listing_title = $rlListings->getListingTitle(
                $listing_data['Category_ID'],
                $listing_data,
                $listing_type['Key']
            );
            $rlSmarty->assign('cpTitle', $listing_title);
        }

        /* get listing photos */
        if ($listing_id) {
            $photos = $rlDb->fetch(
                '*',
                array('Listing_ID' => $listing_id, 'Status' => 'active'),
                "AND `Thumbnail` <> '' AND `Photo` <> '' ORDER BY `Position`",
                $listing_data['Image'],
                'listing_photos'
            );
            $rlSmarty->assign_by_ref('photos', $photos);
        }

        /* get account information */
        $account_id = $claim_info['Status'] == 'pending' ? $claim_info['Account_ID'] : $listing_data['Account_ID'];

        if ((int) $account_id) {
            $account_info = $rlAccount->getProfile((int) $account_id);
            $rlSmarty->assign_by_ref('account_info', $account_info);
        }
    }

    /**
     * Get all listings by found value
     *
     * @since 1.1.0
     *
     * @param  string     $field
     * @param  string     $value
     * @param  int        $account_id - ID of owner of listing
     * @param  bool       $get_ids    - Switch to get ID's of found listings
     * @return int|string
     */
    public function getListingsByField($field = '', $value = '', $account_id = 0, $get_ids = false)
    {
        global $rlDb;

        if (!$field || !$value || !$account_id) {
            return 0;
        }

        $ids   = 'GROUP_CONCAT(`T1`.`ID`)';
        $count = 'COUNT(*)';

        $sql = "SELECT " . ($get_ids ? $ids : $count) . " FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Account_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`Status` = 'active' AND `T1`.`{$field}` = '{$value}' ";
        $sql .= "AND (`T1`.`cl_direct` = 1 OR `T2`.`cl_direct` = 1) AND `T2`.`ID` = {$account_id}";
        return $get_ids ? $rlDb->getRow($sql, $ids) : (int) $rlDb->getRow($sql, $count);
    }

    /**
     * Get count of listings with same phone
     *
     * @since 1.1.0
     *
     * @param  string $phone
     * @param  int    $account_id - ID of owner of listing
     * @param  bool   $get_ids    - Switch to get ID's of found listings
     * @return mixed
     */
    public function getListingsByPhone($phone = '', $account_id = 0, $get_ids = false)
    {
        return $this->getListingsByField($GLOBALS['config']['cl_phone_field'], $phone, $account_id, $get_ids);
    }

    /**
     * Get count of listings with same email
     *
     * @since 1.1.0
     *
     * @param  string $email
     * @param  int    $account_id - ID of owner of listing
     * @param  bool   $get_ids    - Switch to get ID's of found listings
     * @return mixed
     */
    public function getListingsByEmail($email = '', $account_id = 0, $get_ids = false)
    {
        return $this->getListingsByField($GLOBALS['config']['cl_email_field'], $email, $account_id, $get_ids);
    }

    /**
     * Get all claimed listings (admin side)
     *
     * @since 1.1.0
     *
     * @param string $ids   - ID's of all claimed listings
     * @param int    $start - Start position
     * @param int    $limit - Limit of count request
     */
    public function getClAPListings($ids = '', $start = 0, $limit = 0)
    {
        global $reefless, $lang, $rlDb;

        if (!is_numeric($start) || !is_numeric($limit) || !$ids) {
            return;
        }

        $reefless->loadClass('Account');
        $reefless->loadClass('Listings');

        $sql .= "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, `T1`.`ID` AS `Listing_ID`, `T3`.`Type` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
        $sql .= "WHERE `T1`.`ID` IN({$ids}) AND `T1`.`Status`='active' LIMIT {$start},{$limit}";
        $listings = $rlDb->getAll($sql);
        $count    = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`", 'count');

        foreach ($listings as &$listing) {
            // get listing title and build url
            $listing            = $GLOBALS['rlListings']->getListing($listing['ID'], true);
            $link               = RL_URL_HOME . ADMIN . '/index.php?controller=listings&action=view&id=' . $listing['ID'];
            $listing['Listing'] = '<a target="_blank" alt="' . $lang['listing_details'] . '" title="';
            $listing['Listing'] .= $lang['listing_details'] . "\" href=\"{$link}\">{$listing['listing_title']}</a>";

            // get account info
            $account_info = $GLOBALS['rlAccount']->getProfile((int) $listing['Account_ID']);

            // get account name and build url
            if ($account_info) {
                $link = RL_URL_HOME . ADMIN . '/index.php?controller=accounts&action=view&userid=';
                $link .= $listing['Account_ID'];
                $listing['Account_name'] = $account_info['Full_name'];
                $listing['Account'] = '<a target="_blank" alt="' . $lang['view_account'] . '" title="';
                $listing['Account'] .= $lang['view_account'] . '" href="' . $link . '">' . $listing['Account_name'];
                $listing['Account'] .= '</a>';
            } else {
                $listing['Account'] .= $lang['not_available'];
            }

            // adapt phrases of status
            $listing['Status'] = $lang[$listing['Status']];
        }

        return array('data' => $listings, 'count' => $count);
    }

    /**
     * @since 1.0.1 - Install process
     */
    public function install()
    {
        global $rlDb;

        $rlDb->addColumnToTable('cl_direct', "ENUM('0','1') NOT NULL DEFAULT '0'", 'accounts');
        $rlDb->addColumnToTable('cl_direct', "ENUM('0','1') NOT NULL DEFAULT '0'", 'listings');

        $rlDb->createTable(
            'claim_requests',
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
            `Date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `Claim_method` enum('phone','email', 'image') NOT NULL DEFAULT 'image',
            `Data` varchar(255) NOT NULL,
            `Account_ID` int(11) NOT NULL DEFAULT 0,
            `Listing_ID` int(11) NOT NULL DEFAULT 0,
            `IP` varchar(25) NOT NULL,
            `Listings_IDs` varchar(255) NOT NULL,
            `Status` enum('active','pending') NOT NULL DEFAULT 'pending',
            PRIMARY KEY (`ID`)"
        );
    }

    /**
     * @since 1.0.1 - Uninstall process
     */
    public function uninstall()
    {
        global $rlDb;

        // removing images from claim requests
        $GLOBALS['reefless']->deleteDirectory(RL_FILES . 'claim_images/');

        // removing general table and additional columns
        $rlDb->dropTable('claim_requests');
        $rlDb->dropColumnFromTable('cl_direct', 'listings');
        $rlDb->dropColumnFromTable('cl_direct', 'accounts');
    }

    /**
     * Update process of the plugin (copy from core)
     *
     * @since 1.1.0
     * @todo        - Remove this method when compatibility will be >= 4.6.2
     *
     * @param string $version
     */
    public function update($version)
    {
        $version_method = 'update' . (int) str_replace('.', '', $version);
        if (method_exists($this, $version_method)) {
            $this->$version_method();
        }
    }

    /**
     * Update to 1.1.0 version
     */
    public function update110()
    {
        global $rlDb;

        $rlDb->addColumnToTable('Listings_IDs', "VARCHAR(255) NOT NULL", 'claim_requests');

        @unlink(self::PLUGIN_DIR . 'claim_listing_42.tpl');
        @unlink(self::PLUGIN_DIR . 'button_responsive_42.tpl');

        // update position of configs
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 7 WHERE `Key` = 'cl_by_same_phone'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 8 WHERE `Key` = 'cl_clickatell_divider'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 9 WHERE `Key` = 'cl_clickatell_username'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 10 WHERE `Key` = 'cl_clickatell_api_id'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 11 WHERE `Key` = 'cl_clickatell_password'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 12 WHERE `Key` = 'cl_clickatell_divider_rest'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 13 WHERE `Key` = 'cl_clickatell_api_key_rest'");
    }

    /**
     * Update to 1.2.0 version
     */
    public function update120()
    {
        global $rlDb;

        // update position of configs
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 10 WHERE `Key` = 'cl_clickatell_api_phone'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 11 WHERE `Key` = 'cl_clickatell_divider'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 12 WHERE `Key` = 'cl_clickatell_api_id'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 13 WHERE `Key` = 'cl_clickatell_username'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 14 WHERE `Key` = 'cl_clickatell_password'");
    }

    /**
     * Update to 1.2.2 version
     */
    public function update122()
    {
        global $languages, $rlDb;

        if (array_key_exists('ru', $languages)) {
            $russianTranslation = json_decode(file_get_contents(RL_UPLOAD . 'claimListing/i18n/ru.json'), true);

            foreach ($russianTranslation as $phraseKey => $phraseValue) {
                if (!$rlDb->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $insertPhrase = $rlDb->fetch(
                        ['Module', 'Key', 'Plugin'],
                        ['Code' => $GLOBALS['config']['lang'], 'Key' => $phraseKey],
                        null, 1, 'lang_keys', 'row'
                    );

                    $insertPhrase['Code']  = 'ru';
                    $insertPhrase['Value'] = $phraseValue;

                    $rlDb->insertOne($insertPhrase, 'lang_keys');
                } else {
                    $rlDb->updateOne([
                        'fields' => ['Value' => $phraseValue],
                        'where' => ['Key'   => $phraseKey, 'Code' => 'ru'],
                    ], 'lang_keys');
                }
            }
        }
    }

    /**
     * Update to 1.3.0 version
     */
    public function update130(): void
    {
        global $rlDb;

        // Update position of configs
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 9 WHERE `Key` = 'cl_clickatell_divider_rest'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 10 WHERE `Key` = 'cl_clickatell_api_key_rest'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 11 WHERE `Key` = 'cl_clickatell_api_phone'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 12 WHERE `Key` = 'cl_clickatell_divider'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 13 WHERE `Key` = 'cl_clickatell_api_id'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 14 WHERE `Key` = 'cl_clickatell_username'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 15 WHERE `Key` = 'cl_clickatell_password'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 16 WHERE `Key` = 'cl_divider_sms_ru'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 17 WHERE `Key` = 'cl_sms_ru_api_key_rest'");
    }

    /**
     * @hook  apPhpAccountsAfterAdd
     * @since 1.0.1
     */
    public function hookApPhpAccountsAfterAdd()
    {
        $this->saveClDirectConfigInAccounts();
    }

    /**
     * @hook  apPhpAccountsAfterEdit
     * @since 1.0.1
     */
    public function hookApPhpAccountsAfterEdit()
    {
        $this->saveClDirectConfigInAccounts();
    }

    /**
     * Save value to accounts
     *
     * @since 1.0.1
     *
     * @return bool - true/false
     */
    public function saveClDirectConfigInAccounts()
    {
        global $config;

        if (!$config['cl_module'] || !isset($_POST['profile']['cl_direct'])) {
            return false;
        }

        if ($config['cl_module'] && isset($_POST['profile']['cl_direct'])) {
            $column = $config['account_login_mode'] == 'username' || !$config['account_login_mode']
            ? 'Username'
            : 'Mail';

            $value = $config['account_login_mode'] == 'username' || !$config['account_login_mode']
            ? $_POST['profile']['username']
            : $_POST['profile']['mail'];

            $GLOBALS['rlDb']->query("
                UPDATE `" . RL_DBPREFIX . "accounts` SET `cl_direct` = '{$_POST['profile']['cl_direct']}'
                WHERE `{$column}` = '{$value}'
            ");
        }
    }

    /**
     * @hook  apMixConfigItem
     * @since 1.0.1
     *
     * @param array $value
     */
    public function hookApMixConfigItem(&$value)
    {
        global $rlDb;

        if (!in_array($value['Key'], array('cl_phone_field', 'cl_email_field'))) {
            return;
        }

        $allowed_types   = $value['Key'] == 'cl_phone_field' ? "'text','number', 'phone'" : "'text'";
        $value['Values'] = array();

        $rlDb->setTable('listing_fields');
        $fields = $rlDb->fetch(
            array('Key'),
            array('Status' => 'active'),
            "AND `Type` IN (" . $allowed_types . ")"
        );

        foreach ($fields as $item) {
            $value['Values'][] = array(
                'ID'   => $item['Key'],
                'name' => $GLOBALS['lang']['listing_fields+name+' . $item['Key']],
            );
        }
    }

    /**
     * @hook  listing_details_sidebar
     * @since 1.0.1
     */
    public function hookListing_details_sidebar()
    {
        if ($GLOBALS['config']['cl_module']) {
            $GLOBALS['rlSmarty']->display(self::PLUGIN_DIR . 'button.tpl');
        }
    }

    /**
     * @hook  staticDataRegister
     * @since 1.1.0
     */
    public function hookStaticDataRegister()
    {
        $GLOBALS['rlStatic']->addHeaderCss(RL_TPL_BASE . 'controllers/add_listing/add_listing.css', 'claim_listing');
    }

    /**
     * @hook  ajaxRequest
     * @since 1.1.0
     */
    public function hookAjaxRequest(&$out, $mode = '', $item = '', $request_lang = '')
    {
        global $lang, $config;

        if (($mode != 'claimSendCode' && $mode != 'claimCheckCode') || !$item) {
            return;
        }

        $lang = $GLOBALS['rlLang']->getLangBySide('frontEnd', $request_lang ?: RL_LANG_CODE);

        switch ($mode) {
            case 'claimSendCode':
                $out = $this->ajaxSendCode($item['method'], $item['id']);
                break;
            case 'claimCheckCode':
                $out = $this->ajaxCheckCode($item);
                break;
        }
    }

    /**
     * @hook  apAjaxRequest
     * @since 1.1.0
     */
    public function hookApAjaxRequest(&$out, $item)
    {
        if ($item == 'deleteClaimRequest' && (int) $_REQUEST['id']) {
            $out = $this->ajaxDeleteClaimRequest($_REQUEST['id']);
        }
    }

    /**
     * @hook  sitemapExcludedPages
     * @since 1.2.0
     */
    public function hookSitemapExcludedPages(&$urls)
    {
        $urls = array_merge($urls, array('claim_listing'));
    }

    /**
     * @hook  tplHeader
     * @since 1.2.0
     */
    public function hookTplHeader()
    {
        if ($GLOBALS['page_info']['Key'] !== 'claim_listing') {
            return;
        }

        echo <<<CSS
        <style>
            @media screen and (max-width: 383px) {
                .cl_form_method .file-input input.file-name {
                    width: 180px;
                }
            }
        </style>
CSS;
    }

    /**
     * @hook  apTplFooter
     * @since 1.3.0
     */
    public function hookApTplFooter(): void
    {
        if ($_GET['controller'] !== 'settings') {
            return;
        }

        $GLOBALS['rlSmarty']->display(self::ADMIN_VIEW_DIR . 'settings.tpl');
    }

    /*** DEPRECATED METHODS ***/

    /**
     * @hook       listingDetailsAfterStats
     * @since      1.0.1
     * @deprecated 1.1.0
     */
    public function hookListingDetailsAfterStats()
    {}

    /**
     * @hook       listingDetailsNavIcons
     * @since      1.0.1
     * @deprecated 1.1.0
     */
    public function hookListingDetailsNavIcons()
    {}
}
