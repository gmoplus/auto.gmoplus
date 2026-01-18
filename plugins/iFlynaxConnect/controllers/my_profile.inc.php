<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : MY_PROFILE.INC.PHP
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

$account_id = intval($_REQUEST['id']);
$type       = $rlValid->xSql($_REQUEST['type']);
$tablet     = intval($_REQUEST['tablet']);
$action     = $_REQUEST['action'];
$response   = array();

switch ($action) {
    case 'change_password':
        $old_pass = $_REQUEST['old_pass'];
        $new_pass = $_REQUEST['new_pass'];
        $response = $iOSHandler->changeAccountPassword($account_id, $old_pass, $new_pass);
        break;

    case 'upload_image':
        $account_id = intval($account_info['ID']);

        $response = $iOSHandler->uploadProfileImage($account_id);
        break;

    case 'profile_info':
        $response = $iOSHandler->fetchUserShortInfo($account_id);
        break;

    case 'profileForm':
        $response = $iOSHandler->getProfileForm($type, $account_id);
        break;

    case 'updateProfile':
        $form_data = $_REQUEST['f'];

        $response = $iOSHandler->updateMyProfile($form_data);
        break;

    case 'updateProfileEmail':
        $account_id = intval($account_info['ID']);
        $new_email = $rlValid->xSql($_REQUEST['email']);

        $response = $iOSHandler->updateProfileEmail($account_id, $new_email);
        break;

    case 'deleteAccount':
        $account_id = (int) $account_info['ID'];
        $password = \Flynax\Utils\Valid::escape($_REQUEST['password']);

        $response = $iOSHandler->deleteAccount($account_id, $password);
        break;
}

// send response to iOS device
$iOSHandler->send($response);
