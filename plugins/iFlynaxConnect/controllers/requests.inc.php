<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : REQUESTS.INC.PHP
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

$response = array();
$account_id = intval($account_info['ID']);
$_cmd = $_REQUEST['cmd'];

switch ($_cmd) {
    case 'multifield':
        $response = $iOSHandler->getMDF($_REQUEST['parent']);
        break;

    case 'categories':
        $ltype_key = $rlValid->xSql($_REQUEST['ltype_key']);
        $parent_category = intval($_REQUEST['category_id']);
        $mf_value = intval($_REQUEST['mf_value']);
        $all_categories = ($mf_value || intval($_REQUEST['all'])) ? true : false;

        $response = $iOSHandler->getCategories($ltype_key, $parent_category, $mf_value, $all_categories);
        break;

    case 'listingsByCategory':
        $category_id = intval($_REQUEST['cid']);
        $stack = intval($_REQUEST['stack']) ?: 1;
        $ltype = $rlValid->xSql($_REQUEST['ltype']);
        $sort = $rlValid->xSql($_REQUEST['sort']);

        $response = $iOSHandler->getListingsByCategory($category_id, $stack, $ltype, $sort);
        break;

    case 'getAccounts':
        $atype = $rlValid->xSql($_REQUEST['atype']);
        $stack = intval($_REQUEST['stack']);
        $char = mb_convert_case($_REQUEST['char'], MB_CASE_TITLE, 'UTF-8');
        $alphabet = explode(',', $lang['alphabet_characters']);

        $response = $iOSHandler->getAccountsByType($atype, $stack, $char);
        break;

    case 'searchListings':
        $type = $rlValid->xSql($_REQUEST['type']);
        $sort = $rlValid->xSql($_REQUEST['sort']);
        $stack = intval($_REQUEST['stack']);
        $form_data = $_REQUEST['f'] ? $_REQUEST['f'] : array();

        $response = $iOSHandler->searchListings($form_data, $type, $stack, $sort);
        break;

    case 'keywordSearch':
        $query = $rlValid->xSql($_REQUEST['query']);
        $stack = intval($_REQUEST['stack']);

        $response = $iOSHandler->keywordSearch($query, $stack);
        break;

    case 'searchAccounts':
        $atype = $rlValid->xSql($_REQUEST['atype']);
        $stack = intval($_REQUEST['stack']);
        $form_data = $_REQUEST['f'];

        $response = $iOSHandler->searchAccounts($form_data, $atype, $stack);
        break;

    case 'fetchSellerInfo':
        $account_id = intval($_REQUEST['aid']);

        $response = $iOSHandler->fetchSellerInfo($account_id);
        break;

    case 'getListingsByAccount':
        $account_id = intval($_REQUEST['aid']);
        $stack = intval($_REQUEST['stack']);

        $response = $iOSHandler->getListingsByAccount($account_id, $stack);
        break;

    case 'getListingsByLatLng':
        $ltype = $rlValid->xSql($_REQUEST['ltype']);
        $stack = intval($_REQUEST['stack']);

        $coordinates = array(
            'centerLat' => doubleval($_REQUEST['centerLat']),
            'centerLng' => doubleval($_REQUEST['centerLng']),
            'northEastLat' => doubleval($_REQUEST['northEastLat']),
            'northEastLng' => doubleval($_REQUEST['northEastLng']),
            'southWestLat' => doubleval($_REQUEST['southWestLat']),
            'southWestLng' => doubleval($_REQUEST['southWestLng'])
        );

        $response = $iOSHandler->getListingsByLatLng($ltype, $coordinates);
        break;

    case 'conversations':
        $response = $iOSHandler->getConversations($account_id);
        break;

    case 'removeConversation':
        $authorId = intval($_REQUEST['authorId']);
        $response = $iOSHandler->removeConversation($authorId, $account_id);
        break;

    case 'fetchMessages':
        $response = $iOSHandler->fetchMessages($account_id, $_REQUEST['recipient']);
        break;

    case 'sendMessageTo':
        $sender = intval($account_info['ID']);
        $recipient = intval($_REQUEST['recipient']);
        $message = $rlValid->xSql(urldecode($_REQUEST['message']));

        $response = $iOSHandler->sendMessageTo($sender, $recipient, $message);
        break;

    case 'getAddListingData':
        $category_id  = intval($_REQUEST['cid']);
        $account_type = $account_info['Type'];
        $listing_type = $rlValid->xSql($_REQUEST['ltype']);

        $_form  = array();
        $_plans = $iOSHandler->getPlans($account_id, $category_id, $account_type);

        if (!empty($_plans)) {
            $_form  = $iOSHandler->getFormFields($category_id, $listing_type);
        }

        $response = array(
            'plans' => $_plans,
            'form'  => $_form
        );
        break;

    case 'getEditListingData':
        $listing_id   = intval($_REQUEST['lid']);
        $account_type = $account_info['Type'];

        $response = $iOSHandler->getEditListingData($listing_id, $account_id, $account_type);
        break;

    case 'getPlans':
        $featured_only = intval($_REQUEST['featured_only']);
        $category_id = intval($_REQUEST['cid']);
        $account_type = $account_info['Type'];
        $plan_id = intval($_REQUEST['plan_id']);

        $response = $iOSHandler->getPlans($account_id, $category_id, $account_type, $featured_only, $plan_id);
        break;

    case 'addListing':
    case 'editListing':
        $ltype_key   = $rlValid->xSql($_REQUEST['ltype']);
        $category_id = intval($_REQUEST['category']);
        $plan_id     = intval($_REQUEST['plan']);
        $form_data   = $_REQUEST['f'];
        $_func       = $_cmd;

        $response = $iOSHandler->{$_func}($account_id, $ltype_key, $category_id, $plan_id, $form_data);
        break;

    case 'removeListing':
        $listing_id = intval($_REQUEST['lid']);

        $response = $iOSHandler->removeListing($listing_id, $account_id);
        break;

    case 'savePicture':
    case 'saveListingPhoto':
        $listing_id = intval($_REQUEST['lid']);
        $info = array(
            'photo_id' => $_REQUEST['pid'],
            'desc' => $_REQUEST['desc'],
            'primary' => $_REQUEST['primary'],
            'last' => intval($_REQUEST['last']),
            'orientation' => intval($_REQUEST['orientation']),
        );

        $response = $iOSHandler->saveListingPhoto($listing_id, $info);
        break;

    case 'getComments':
        $listing_id = intval($_REQUEST['lid']);
        $stack      = intval($_REQUEST['stack']);

        $response = $iOSHandler->getComments($listing_id, $account_id, $stack, true);
        break;

    case 'addComment':
        $response = $iOSHandler->addComment($_REQUEST);
        break;

    case 'resetPassword':
        $response = $iOSHandler->resetPassword($_REQUEST['email']);
        break;

    case 'registration':
        $response = $iOSHandler->registration(array(
            'username'   => $_REQUEST['username'],
            'password'   => $_REQUEST['password'],
            'email'      => $_REQUEST['email'],
            'type'       => $_REQUEST['type'],
            'account'    => $_REQUEST['account'],
        ));
        break;

    case 'registerForRemoteNotification':
        $token = $_REQUEST['push_token'];

        $response = $iOSHandler->registerForRemoteNotification($account_id, $token);
        break;

    case 'validateTransaction':
        $payment = array(
            'receipt' => $_REQUEST['payment_receipt'],
            'item' => $_REQUEST['payment_item'],
            'title' => $rlValid->xSql($_REQUEST['payment_title']),
            'plan' => intval($_REQUEST['payment_plan']), // plan id
            'id' => intval($_REQUEST['payment_id']), // item id
            'gateway' => $_REQUEST['payment_gateway'],
            'featured' => ($_REQUEST['payment_item'] == 'featured'),
            // Pay info
            'amount' => doubleval($_REQUEST['payment_amount']),
            'currencyCode' => $_REQUEST['payment_currencyCode'],
            'currencySymbol' => $_REQUEST['payment_currencySymbol'],
        );

        $response = $iOSHandler->validateTransaction($account_id, $payment);
        break;

    case 'upgradePlan':
        $listing_id = intval($_REQUEST['lid']);
        $plan_id = intval($_REQUEST['plan_id']);
        $listing_mode = $_REQUEST['appearance'];

        // print_r(array($listing_id, $plan_id, $listing_mode));
        $response['success'] = $iOSHandler->upgradePlan($account_id, $listing_id, $plan_id, $listing_mode);
        break;

    case 'reportBrokenListing':
        $listing_id = intval($_REQUEST['lid']);
        $message = $_REQUEST['message'];
        $point_key = $_REQUEST['point_key'];

        $response = $iOSHandler->reportBrokenListing($account_id, $listing_id, $message, $point_key);
        break;

    case 'staticPageContent':
        $page_key = $rlValid->xSql($_REQUEST['page']);

        $response = $iOSHandler->staticPageContent($page_key);
        break;

    case 'saveSearch':
        $ltype = $rlValid->xSql($_REQUEST['ltype']);
        $fields = $rlValid->xSql($_REQUEST['fields']);

        $response = $iOSHandler->saveSearch($account_id, $ltype, $fields);
        break;

    case 'runSavedSearch':
        $stack = intval($_REQUEST['stack']);
        $sid = intval($_REQUEST['sid']);
        $ids = $rlValid->xSql($_REQUEST['ids']);

        $response = $iOSHandler->runSavedSearch($sid, $stack, $ids);
        break;

    case 'getMySavedSearch':
        $response = $iOSHandler->getMySavedSearch($account_id);
        break;

    case 'actionSavedSearch':
        $action = intval($_REQUEST['action']);
        $action_id = intval($_REQUEST['action_id']);

        $response = $iOSHandler->actionSavedSearch($action, $action_id, $account_id);
        break;

    case 'socialNetworkLogin':
        $firstNameFallback = !empty($_REQUEST['first_name'])
            ? $_REQUEST['first_name']
            : explode('@', $_REQUEST['email'])[0];

        $accountTypeKey = !empty($_REQUEST['type_key'])
            ? $rlValid->xSql($_REQUEST['type_key'])
            : '';

        $response = $iOSHandler->socialNetworkLogin([
            'provider'   => $_REQUEST['provider'],
            'fid'        => $_REQUEST['fid'],
            'email'      => $_REQUEST['email'],
            'first_name' => $firstNameFallback,
            'last_name'  => $_REQUEST['last_name'],
            'type_key'   => $accountTypeKey,
        ]);
        break;

    case 'socialNetworkLoginVerifyUserPasswordByEmail':
        $email    = $rlValid->xSql($_REQUEST['email']);
        $password = $rlValid->xSql($_REQUEST['password']);

        $response = $iOSHandler->socialNetworkLoginVerifyUserPasswordByEmail($email, $password);
        break;

    case 'placesAutocomplete':
        $location = $rlValid->xSql($_REQUEST['loc']);
        $response = $iOSHandler->placesAutocomplete($location);
        break;

    default:
        $response['api_error'] = 'unrecognized _cmd parametr';
        break;
}

// send response to iOS device
$iOSHandler->send($response);
