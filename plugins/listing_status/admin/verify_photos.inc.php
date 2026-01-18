<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : VERIFY_PHOTOS.INC.PHP
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

use Flynax\Utils\Valid;

if ($_GET['q'] == 'ext') {
    // system config
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    if ($_GET['action'] == 'update') {

        $field = Valid::escape($_GET['field']);
        $value = Valid::escape(nl2br($_GET['value']));
        $id = (int) $_GET['id'];

        /* update block */
        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where' => array(
                'ID' => $id,
            ),
        );
        $rlDb->updateOne($updateData, 'listings');

        if ($value == 'verified') {
            $sql = "SELECT `T2`.`Username`, `T2`.`Mail` ";
            $sql .= "FROM `{db_prefix}listings` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Account_ID` = `T2`.`ID` ";
            $sql .= "WHERE `T1`.`ID` = {$id} ";
            $data = $rlDb->getRow($sql);

            $reefless->loadClass('Mail');
            $mail_tpl = $rlMail->getEmailTemplate('verified_photos_active');

            $m_find = array('{id}', '{name}');
            $m_replace = array(
                $id,
                $data['Username'],
            );
            $mail_tpl['body'] = str_replace($m_find, $m_replace, $mail_tpl['body']);
            $rlMail->send($mail_tpl, $data['Mail']);
        }
        exit;
    }

    // data read
    $limit       = (int) $_GET['limit'];
    $start       = (int) $_GET['start'];
    $fListing_id = (int) $_GET['f_listing_id'];
    $fEmail      = Valid::escape($_GET['f_email']);
    $fUsername   = Valid::escape($_GET['f_Account']);
    $fVerified   = Valid::escape($_GET['f_verified']);

    $where = "";
    if ($fListing_id || $fEmail || $fUsername) {
        if ($fListing_id) {
            $where .= "AND `T1`.`ID` = '" . $fListing_id . "' ";
        }
        if ($fEmail) {
            $where .= "AND `T5`.`Mail` = '" . $fEmail . "' ";
        }
        if ($fUsername) {
            $where .= "AND `T5`.`Username` = '" . $fUsername . "' ";
        }
    }

    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.* ";
    $sql .= ", `T2`.`ID` AS `ID`, `T2`.`Image` AS `ls_image`, `T2`.`Description` AS `ls_desc`, `T2`.`Date` AS `ls_date` ";
    $sql .= ", `T3`.`Key` AS `Verified`, `T4`.`Type` AS `Listing_type` ";
    $sql .= ", `T5`.`First_name`, `T5`.`Last_name`, `T5`.`Username` ";
    $sql .= "FROM `{db_prefix}listings` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}listing_status_verified` AS `T2` ON `T1`.`ID` = `T2`.`Listing_ID` ";
    $sql .= "LEFT JOIN `{db_prefix}listing_status_data` AS `T3` ON `T1`.`ID` = `T3`.`Listing_ID` ";
    $sql .= "LEFT JOIN `{db_prefix}categories` AS `T4` ON `T1`.`Category_ID` = `T4`.`ID` ";
    $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T5` ON `T1`.`Account_ID` = `T5`.`ID` ";
    $sql .= "WHERE `T3`.`Type` = 'verify' AND ";
    if ($fVerified) {
        $sql .= "(`T1`.`Sub_status_data` = '" . $fVerified . "' OR FIND_IN_SET( '" . $fVerified . "' , `T1`.`Sub_status_data`) > 0 ) ";
    } else {
        $sql .= "(`T1`.`Sub_status_data` = 'verified' OR FIND_IN_SET( 'verified' , `T1`.`Sub_status_data`) > 0 ";
        $sql .= "OR `T1`.`Sub_status_data` = 'wait' OR FIND_IN_SET( 'wait' , `T1`.`Sub_status_data`) > 0 )";
    }
    $sql .= $where;
    $sql .= "ORDER BY `T1`.`ID` DESC ";
    $sql .= "LIMIT {$start}, {$limit}";

    $data = $rlDb->getAll($sql);
    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");
    
    $reefless->loadClass('Listings');
    foreach ($data as $key => $value) {
        $url = RL_URL_HOME . ADMIN . '/index.php?controller=listing_status&module=verify_photos&action=view&id=' . $value['ID'];
        $data[$key]['listing_title'] = '<a href="'.$url.'">' . $rlListings->getListingTitle($value['Category_ID'], $value, $value['Listing_type']) . '</a>';
        $data[$key]['Verified_key']  = $value['Verified'];
        $data[$key]['Verified']      = $data[$key]['Verified']=='verified' ? $lang['ls_' . $data[$key]['Verified']] : $lang['pending'];
        $data[$key]['ls_desc']       = $data[$key]['ls_desc'] ? $data[$key]['ls_desc'] : $lang['not_available'];

        // get account name and build url
        $data[$key]['Account_name'] = $data[$key]['First_name'] || $data[$key]['Last_name'] ? $data[$key]['First_name'] . ' ' . $data[$key]['Last_name'] : $data[$key]['Username'];
        $data[$key]['Account_url'] = RL_URL_HOME . ADMIN .'/index.php?controller=accounts&action=view&userid=' . $data[$key]['Account_ID'];
        $data[$key]['Account'] = '<a target="_blank" alt="' . $lang['view_account'] . '" title="' . $lang['view_account'] . '" href="' . $data[$key]['Account_url'] . '">' . $data[$key]['Account_name'] . '</a>';
    }

    $output['total'] = $count['count'];
    $output['data'] = $data;
    
    echo json_encode($output);
    exit;
} else {

    $request_id = (int) $_GET['id'];

    $sql = "SELECT `T1`.`Listing_ID`, `T1`.`Image` AS `ls_image`, `T1`.`Description` AS `ls_desc`, `T1`.`Date` AS `ls_date` ";
    $sql .= ", `T2`.`Key` AS `Verified` ";
    $sql .= "FROM `{db_prefix}listing_status_verified` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}listing_status_data` AS `T2` ON `T1`.`Listing_ID` = `T2`.`Listing_ID` ";
    $sql .= "WHERE `T2`.`Type` = 'verify' AND `T1`.`ID` = {$request_id}";
    $verified_data = $rlDb->getRow($sql);

    if ($verified_data) {

        $reefless->loadClass('Listings');
        $reefless->loadClass('Account');
        $listing_id = $verified_data['Listing_ID'];        
        $rlSmarty->assign_by_ref('verified_data', $verified_data);

        //get listing info
        $sql = "SELECT `T1`.*, `T2`.`Path`, `T2`.`Type` AS `Listing_type`, `T2`.`Key` AS `Category_key`, ";
        $sql .= "`T2`.`Path` AS `Category_path`, `T2`.`Parent_IDs`, ";
        $sql .= "`T3`.`Image`, `T3`.`Image_unlim`, `T3`.`Video`, `T3`.`Video_unlim` ";
        $sql .= "FROM `" . RL_DBPREFIX . "listings` AS `T1` ";
        $sql .= "LEFT JOIN `" . RL_DBPREFIX . "categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `" . RL_DBPREFIX . "listing_plans` AS `T3` ON `T1`.`Plan_ID` = `T3`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = '{$listing_id}' LIMIT 1";
        $listing_data = $rlDb->getRow($sql);
       
        $listing_data['category_name'] = $lang['categories+name+' . $listing_data['Category_key']];
        $listing_data['listing_title'] = $rlListings->getListingTitle($listing_data['Category_ID'], $listing_data, $listing_data['Listing_type']);
        
        $rlSmarty->assign_by_ref('listing_data', $listing_data);

        $bcAStep[0] = array('name' => $lang['ls_verify_photo_request'], 'Controller' => 'listing_status', 'Vars' => 'module=verify_photos');
        $bcAStep[1] = array('name' => $listing_data['listing_title']);
       
        if (isset($_POST['submit'])) {

            /* update block */
            $updateData = array(
                'fields' => array(
                    'Key' => $_POST['status'],
                ),
                'where' => array(
                    'Type'       => 'verify',
                    'Listing_ID' => $listing_id,
                ),
            );
            $rlDb->updateOne($updateData, 'listing_status_data');

            $listing_info = $rlDb->fetch(array('Sub_status_data', 'Account_ID'), array('ID' => $listing_id), null, null, 'listings', 'row');

            /* update block */
            $update = array(
                'fields' => array(
                    'Sub_status_data' => str_replace($verified_data['Verified'], $_POST['status'], $listing_data['Sub_status_data']),
                ),
                'where' => array(
                    'ID' => $listing_id,
                ),
            );
            $rlDb->updateOne($update, 'listings');

            $account_info = $rlAccount->getProfile((int)$listing_data['Account_ID']);
            $data['url'] = $reefless->getListingUrl($listing_data);
            $title = '<a href="'.$data['url'].'">' . $listing_data['listing_title'] . '</a>';

            $reefless->loadClass('Mail');
            $mailTpl = $rlMail->getEmailTemplate('verified_photos_active', $account_info['Lang']);

            $mFind = array('{id}', '{title}', '{name}');
            $mReplace = array(
                $listing_id,
                $title,
                $account_info['Full_name'],
            );
            $mailTpl['body'] = str_replace($mFind, $mReplace, $mailTpl['body']);
            $rlMail->send($mailTpl, $account_info['Mail']);
          
            $message = $lang['ls_set_verified'];

            $reefless->loadClass('Notice');
            $rlNotice->saveNotice($message);
            $aUrl = array('controller' => $controller, 'module' => 'verify_photos');
            $reefless->redirect($aUrl);
        } else if (isset($_POST['status']) && $_POST['status'] == 'cancel') {
            
            unlink(RL_FILES . 'verified_photos/' . $verified_data['ls_image']);
            $rlDb->delete(['Listing_ID' => $listing_id], 'listing_status_verified');
            $rlDb->delete(['Listing_ID' => $listing_id], 'listing_status_data');

            $subStatusData = explode(',', $listing_data['Sub_status_data']);

            if (in_array('wait', $subStatusData)) {
                unset($subStatusData[array_search('wait', $subStatusData)]);
            }
            /* update block */
            $update = array(
                'fields' => array(
                    'Sub_status_data' => implode(',', $subStatusData),
                ),
                'where' => array(
                    'ID' => $listing_id,
                ),
            );
            $rlDb->updateOne($update, 'listings');

            $account_info = $rlAccount->getProfile((int)$listing_data['Account_ID']);
            
            $data['url'] = $reefless->getListingUrl($listing_data);
            $title = '<a href="' . $data['url'] . '">' . $listing_data['listing_title'] . '</a>';

            $email_key = 'verified_photos_cancel';
            $reefless->loadClass('Mail');
            $mailTpl = $rlMail->getEmailTemplate($email_key, $account_info['Lang']);

            $mFind = array('{id}', '{title}', '{name}', '{reason}');
            $mReplace = array(
                $listing_id,
                $title,
                $account_info['Full_name'],
                $_POST['message'] ? $_POST['message'] : $lang['not_available'],
            );
            $mailTpl['body'] = str_replace($mFind, $mReplace, $mailTpl['body']);
            
            $rlMail->send($mailTpl, $account_info['Mail']);

            $message = $lang['ls_set_verify_cancel'];

            $reefless->loadClass('Notice');
            $rlNotice->saveNotice($message);
            $aUrl = array('controller' => $controller, 'module' => 'verify_photos');
            $reefless->redirect($aUrl);
        }
        else {
            $reefless->loadClass('Account');
            $reefless->loadClass('Message');

            /* register ajax methods */
            $rlXajax->registerFunction(array('contactOwner', $rlMessage, 'ajaxContactOwnerAP'));

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

            /* get listing photos */
            if ($listing_id) {
                $photos = $rlDb->fetch('*', array('Listing_ID' => $listing_id, 'Status' => 'active'), "AND `Thumbnail` <> '' AND `Photo` <> '' ORDER BY `Position`", $listing_data['Image'], 'listing_photos');
                $rlSmarty->assign_by_ref('photos', $photos);
            }

            /* get account information */
            $account_id = $listing_data['Account_ID'];

            if ((int) $account_id) {
                $account_info = $rlAccount->getProfile((int) $account_id);
                $rlSmarty->assign_by_ref('account_info', $account_info);
            }
        }
    }
    else {
        $bcAStep = $lang['ls_verify_photo_request'];
    }
}
