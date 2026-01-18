<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : REQUEST.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2022
 *	https://www.flynax.com
 *
 ******************************************************************************/

// Include PSR-4 autoloader
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

define('ANDROID_APP', true);

$item = $_REQUEST['item'];
$response_deflate = isset($_REQUEST['deflate']);
$response_dely = isset($_REQUEST['sleep']);
$request_lang = strtolower($_REQUEST['lang']);
$json_support = $_REQUEST['json'];

if (!$item) {
    exit;
}

/* system config */
require_once '../../includes/config.inc.php';

session_start();

require_once RL_CLASSES . 'rlDb.class.php';
require_once RL_CLASSES . 'reefless.class.php';

$rlDb = new rlDb();
$reefless = new reefless();

/* load classes */
$reefless->connect(RL_DBHOST, RL_DBPORT, RL_DBUSER, RL_DBPASS, RL_DBNAME);
$reefless->loadClass('Debug');
$reefless->loadClass('Config');

$config = $rlConfig->allConfig();

$reefless->loadClass('Lang');
$reefless->loadClass('Valid');
$reefless->loadClass('Hook');
$reefless->loadClass('Cache');
$reefless->loadClass('Account');
$reefless->loadClass('Common');


/* get available languages */
$languages = $rlLang->getLanguagesList();
$reefless->rlArraySort($languages, 'Order');

define('RL_PLUGIN_ANDROID', RL_PLUGINS . 'androidConnect' . RL_DS);

/* define system language */
if (!$rlDb->getOne('ID', "`Code` = '{$request_lang}' AND `Status` = 'active'", 'android_languages')) {
    $request_lang = $config['lang'];
} else if (!array_key_exists($request_lang, $languages)) {
    $request_lang = $config['lang'];
}
$rlLang->defineLanguage($request_lang);
$rlLang->modifyLanguagesList($languages);

// get list of plugins
if (!$GLOBALS['plugins']) {
    $plugins = $GLOBALS['rlCommon']->getInstalledPluginsList();
    $GLOBALS['plugins'] = $plugins;
}

$reefless->loadClass('ListingTypes', null, false, true);
$reefless->loadClass('AndroidConnect', null, 'androidConnect');
$reefless->loadClass('Smarty', null, 'androidConnect');

$rlCommon->getHooks();

if (file_exists(RL_CLASSES . 'rlEscort.class.php')) {
    $reefless->loadClass('Escort');
}
if (file_exists(RL_CLASSES . 'rlSecurity.class.php')) {
    require_once RL_CLASSES . 'rlSecurity.class.php';
}

if ($GLOBALS['plugins']['shoppingCart']) {
    $reefless->loadClass('ShoppingCart', null, 'shoppingCart');
}

// disabled members ship
$config['membership_module'] = 0;
$config['allow_listing_plans'] = 0;
$reefless->loadClass('MembershipPlan');


/* assign base path */
$bPath = RL_URL_HOME;
if ($config['lang'] != RL_LANG_CODE && $config['mod_rewrite']) {
    $bPath .= RL_LANG_CODE . '/';
}
if (!$config['mod_rewrite']) {
    $bPath .= 'index.php';
}

$rlHook->load('seoBase');

define('SEO_BASE', $bPath);

$response_type = 'xml';

/* utf8 library functions */
function loadUTF8functions()
{
    $names = func_get_args();

    if (empty($names)) {
        return false;
    }

    foreach ($names as $name) {
        if (file_exists(RL_LIBS . 'utf8' . RL_DS . 'utils' . RL_DS . $name . '.php')) {
            require_once RL_LIBS . 'utf8' . RL_DS . 'utils' . RL_DS . $name . '.php';
        }
    }
}

/* set timezone */
if ($config['timezone']) {
    $rlAndroidConnect->setTimeZone($config['timezone']);
}

/* load all fronEnd phrases if NO item requested */
if (!in_array($item, array('isPluginAvailable'))) {
    $lang = $rlLang->getLangBySide('frontEnd', RL_LANG_CODE);
    $GLOBALS['lang'] = &$lang;
}

/* requested items handler */
switch ($item) {
    case 'isPluginAvailable':
        if ($json_support) {
            $response_type = "json";
        }
        $plugin = $rlDb->fetch(array('ID', 'Version'), array('Status' => 'active', 'Key' => 'androidConnect'), null, 1, 'plugins', 'row');

        $response[] = array('available' => $plugin ? 1 : 0,
            'version' => $plugin['Version'],
            'app_version' => $plugin ? $config['android_version'] : "",
            'android_lang' => $request_lang,
            'https' => $reefless->isHTTPS(),
        );
        break;

    case 'getCache':
        $countDate = (int) $_REQUEST['countDate'];
        $tablet = (int) $_REQUEST['tablet'];
        $username = $rlValid->xSql($_REQUEST['username']);
        $passwordHash = $rlValid->xSql(urldecode($_REQUEST['passwordHash']));

        if ($json_support) {
            $response_type = "json";
            $response = $rlAndroidConnect->getCacheJson($countDate, $tablet, $username, $passwordHash);
        } else {
            $response = $rlAndroidConnect->getCache($countDate, $tablet, $username, $passwordHash);
        }
        break;

    case 'getHomeListings':
        $start = (int) $_REQUEST['start'];

        $response = $rlAndroidConnect->getHomeListings($start);
        break;

    case 'getRecentlyAdded':
        $type = $rlValid->xSql($_REQUEST['type']);
        $start = (int) $_REQUEST['start'];

        if (!$type) {
            foreach ($rlAndroidConnect->types as $key => $value) {
                $type = $key;
                break;
            }
        }

        if (!$type) {
            $mess = "Unable to fetch recently added listings, no listing type specified";
            $GLOBALS['rlDebug']->logger('ANDROID: ' . $mess);
            die($mess);
        }

        if (!array_key_exists($type, $rlAndroidConnect->types)) {
            $mess = "Request listing type key doesn't exist";
            $GLOBALS['rlDebug']->logger('ANDROID: ' . $mess);
            die($mess);
        }

        $response = $rlAndroidConnect->getRecentlyAdded($type, $start);
        break;

    case 'actionFavorite':
        $account_id = (int) $_REQUEST['account_id'];
        $listing_id = (int) $_REQUEST['listing_id'];
        $mode = $rlValid->xSql($_REQUEST['mode']);

        $response = $rlAndroidConnect->actionFavorite($account_id, $listing_id, $mode);
        break;

    case 'getFavorites':
        $start = (int) $_REQUEST['start'];
        $IDs = $rlValid->xSql($_REQUEST['ids']);
        $account_id = (int) $_REQUEST['account_id'];

        // remove empty ids
        $IDs = explode(",", $IDs);
        foreach ($IDs as $fKey => $fVal) {
            if (!$fVal) {
                unset($IDs[$fKey]);
            }
        }

        // sync ids
        if ($start <= 1 && $account_id) {
            $IDs = $rlAndroidConnect->synchronizeFavorites($IDs, $account_id);
        }

        $response = $rlAndroidConnect->getListingByIDs($IDs, $start, $account_id);
        break;

    case 'getListingDetails':
        $id = (int) $_REQUEST['id'];
        $account_id = $_REQUEST['account_id'] ? (int) $_REQUEST['account_id'] : false;
        if (!$id) {
            $mess = "Request listing details failed, ID don't specified";
            $GLOBALS['rlDebug']->logger('ANDROID: ' . $mess);
            die($mess);
        }
        $response = $rlAndroidConnect->getListingDetails($id, $account_id);
        break;

    case 'getComments':

        $id = (int) $_REQUEST['listing_id'];
        $account_id = $_REQUEST['account_id'] ? (int) $_REQUEST['account_id'] : false;
        $start = (int) $_REQUEST['start'];

        if (!$id) {
            $mess = "Request listing details failed, ID don't specified";
            $GLOBALS['rlDebug']->logger('ANDROID: ' . $mess);
            die($mess);
        }
        $response = $rlAndroidConnect->getComments($id, $account_id, $start, true);
        // $f = fopen('log.txt', 'w+');
        // ob_start();
        // print_r($response);
        // $out = ob_get_clean();
        // fputs($f, $out);
        // fclose($f);
        // exit;
        break;

    case 'getAccountDetails':
        $id = (int) $_REQUEST['id'];

        if (!$id) {
            $mess = "Request account details failed, ID don't specified";
            $GLOBALS['rlDebug']->logger('ANDROID: ' . $mess);
            die($mess);
        }

        $response = $rlAndroidConnect->getAccountDetails($id);
        break;

    case 'searchResults':
        $start = (int) $_REQUEST['start'];
        $form_data = trim(urldecode($_REQUEST['data']), '{}');
        $type = $rlValid->xSql($_REQUEST['type']);
        $sort = $rlValid->xSql($_REQUEST['sort']);

        $response = $rlAndroidConnect->searchResults($form_data, $type, $start, $sort);
        break;

    case 'getCatTree':
        if ($json_support) {
            $response_type = "json";
        }
        $parent = (int) $_REQUEST['parent'];
        $type = $rlValid->xSql($_REQUEST['type']);

        $response = $rlAndroidConnect->getCatTree($type, $parent);
        break;

    case 'getCategories':
        if ($json_support) {
            $response_type = "json";
        }
        $parent = (int) $_REQUEST['parent'];
        $type = $rlValid->xSql($_REQUEST['type']);

        $response = $rlAndroidConnect->getCategories($type, $parent);
        break;

    case 'getListingsByCategory':
        $category_id = (int) $_REQUEST['id'];
        $start = (int) $_REQUEST['start'];
        $type = $rlValid->xSql($_REQUEST['type']);
        $sort = $rlValid->xSql($_REQUEST['sort']);

        $response = $rlAndroidConnect->getListingsByCategory($category_id, $start, $type, $sort);
        break;

    case 'getAccounts':
        $accountTypeKey = $rlValid->xSql($_REQUEST['type']);
        $start = (int) $_REQUEST['start'];
        $char = $rlValid->xSql($_REQUEST['char']);

        $response = $rlAndroidConnect->getAccountsByType($accountTypeKey, $start, $char);
        break;

    case 'getListingsByAccount':
        $account_id = (int) $_REQUEST['id'];
        $start = (int) $_REQUEST['start'];

        if (!$account_id) {
            $mess = "Request account listings failed, ID don't specified";
            $GLOBALS['rlDebug']->logger('ANDROID: ' . $mess);
            die($mess);
        }

        $response = $rlAndroidConnect->getListingsByAccount($account_id, $start);
        break;

    case 'getListingsByLatLng':
        $type = $rlValid->xSql($_REQUEST['type']);
        $start = (int) $_REQUEST['start'];
        $coordinates = array(
            'centerLat' => (double) $_REQUEST['centerLat'],
            'centerLng' => (double) $_REQUEST['centerLng'],
            'northEastLat' => (double) $_REQUEST['northEastLat'],
            'northEastLng' => (double) $_REQUEST['northEastLng'],
            'southWestLat' => (double) $_REQUEST['southWestLat'],
            'southWestLng' => (double) $_REQUEST['southWestLng'],
        );

        $response = $rlAndroidConnect->getListingsByLatLng($type, $start, $coordinates);
        break;

    case 'keywordSearch':
        if ($json_support) {
            $response_type = "json";
        }
        $query = $rlValid->xSql($_REQUEST['query']);
        $start = (int) $_REQUEST['start'];

        $response = $rlAndroidConnect->keywordSearch($query, $start);
        break;

    case 'searchAccounts':
        $start = (int) $_REQUEST['start'];
        $form_data = trim(urldecode($_REQUEST['data']), '{}');
        $type = $rlValid->xSql($_REQUEST['type']);

        $response = $rlAndroidConnect->searchAccount($form_data, $type, $start);
        break;

    case 'sendEmail':
        $from = $rlValid->xSql($_REQUEST['from']);
        $to = $rlValid->xSql($_REQUEST['to']);

        $tpl = array(
            'subject' => $rlValid->xSql($_REQUEST['subject']),
            'body' => $rlValid->xSql($_REQUEST['body']),
        );

        $reefless->loadClass('Mail');
        $rlMail->send($tpl, $to, null, $from);

        break;

    case 'getMultiFieldNext':
        if ($json_support) {
            $response_type = "json";
        }
        $parent = $rlValid->xSql($_REQUEST['parent']);

        $reefless->loadClass('MultiField', null, 'multiField');
        $order_type = $rlDb->getOne('Order_type', "`Key` = '{$parent}'", 'data_formats');

        if (method_exists($rlMultiField, 'getData')) {
            $response = $rlMultiField->getData($parent, true, $order_type);
        } else {
            $response = $rlMultiField->getMDF($parent, $order_type, true);
        }

        foreach ($response as $k => &$v) {
            unset($v['Position'], $v['ID'], $v['Parent_ID'], $v['pName']);
        }
        break;

    case 'loginAttempt':
        $username = $rlValid->xSql($_REQUEST['username']);
        $password = $rlValid->xSql($_REQUEST['password']);

        $response = $rlAndroidConnect->loginAttempt($username, $password);
        break;

    case 'deleteAccount':
        $account_id = (int) $_REQUEST['account_id'];
        $password_hash = $rlValid->xSql($_REQUEST['password_hash']);
        $password_confirm = $rlValid->xSql($_REQUEST['password_confirm']);

        $response = $rlAndroidConnect->deleteAccount($account_id, $password_hash, $password_confirm);
        break;

    case 'hybridAuthLogin':
        $response_type = "json";
        $response = $rlAndroidConnect->hybridAuthLogin($_POST);
        break;

    case 'hybridAuthLoginVerifyPassword':
        $response_type = "json";
        $response = $rlAndroidConnect->hybridAuthLoginVerifyPassword($_POST);
        break;

    case 'createAccount':
        $response = $rlAndroidConnect->createAccount($_POST);
        break;

    case 'getAccountForms':
        $response = $rlAndroidConnect->getAccountForms();
        break;

    case 'addComment':
        $response = $rlAndroidConnect->addComment($_POST);
        break;

    case 'resetPassword':
        $response = $rlAndroidConnect->resetPassword($_POST['email']);
        break;

    case 'uploadProfileImage':
        $account_id = (int) $_REQUEST['account_id'];
        $passwordHash = $rlValid->xSql($_REQUEST['password_hash']);

        $response = $rlAndroidConnect->uploadProfileImage($account_id, $passwordHash);
        break;

    case 'getProfileForm':
        if ($json_support) {
            $response_type = "json";
        }
        $account_type = $rlValid->xSql($_REQUEST['type']);
        $account_id = (int) $_REQUEST['id'];

        $response = $rlAndroidConnect->getProfileForm($account_type, $account_id);
        break;

    case 'getAccountStat':
        $account_id = (int) $_REQUEST['account_id'];
        $passwordHash = $rlValid->xSql($_REQUEST['password_hash']);

        $response = $rlAndroidConnect->getAccountStat($account_id, $passwordHash);
        break;

    case 'updateProfile':

        $account_id = (int) $_REQUEST['account_id'];
        $passwordHash = $rlValid->xSql($_REQUEST['password_hash']);

        $response = $rlAndroidConnect->updateProfile($account_id, $passwordHash);
        break;

    case 'updateProfileEmail':
        $account_id = (int) $_REQUEST['account_id'];
        $passwordHash = $rlValid->xSql($_REQUEST['password_hash']);
        $new_email = $rlValid->xSql($_REQUEST['new_email']);

        $response = $rlAndroidConnect->updateProfileEmail($account_id, $passwordHash, $new_email);
        break;

    case 'changePassword':
        $account_id = (int) $_REQUEST['account_id'];
        $password = $rlValid->xSql($_REQUEST['password_hash']);
        $newPassword = $rlValid->xSql($_REQUEST['new_password_hash']);

        $response = $rlAndroidConnect->changePassword($account_id, $password, $newPassword);
        break;

    case 'getAddListingData':
        $account_id = (int) $_REQUEST['account_id'];
        $passwordHash = $rlValid->xSql($_REQUEST['password_hash']);

        $id = (int) $_REQUEST['id'];
        $account_type = $rlValid->xSql($_REQUEST['account_type']);
        $listing_type = $rlValid->xSql($_REQUEST['listing_type']);

        // get plans
        $plans = $rlAndroidConnect->getPlans($account_id, $passwordHash, $id, $account_type, false);

        $array = array();
        $form = $rlAndroidConnect->getFormFields($id, $listing_type, $array);

        if ($json_support) {
            $response_type = "json";

            $response['plans'] = $plans;
            $response['form'] = $form;
        } else {
            $rlAndroidConnect->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';
            $response .= '<data>';

            $response .= '<plans>';
            $response .= $rlAndroidConnect->printValue($plans);
            $response .= '</plans>';

            $response .= '<form>';
            $response .= $rlAndroidConnect->printValue($form);
            $response .= '</form>';

            $response .= '</data>';
        }
        unset($plans, $form);
        break;

    case 'addListing':
        $account_id = (int) $_REQUEST['account_id'];
        $passwordHash = $rlValid->xSql($_REQUEST['password_hash']);

        $response = $rlAndroidConnect->addListing($account_id, $passwordHash);
        break;

    case 'editListing':
        $account_id = (int) $_REQUEST['account_id'];
        $listing_id = (int) $_REQUEST['listing_id'];
        $passwordHash = $rlValid->xSql($_REQUEST['password_hash']);

        $response = $rlAndroidConnect->editListing($listing_id, $account_id, $passwordHash);
        break;

    case 'savePicture':
        $account_id = (int) $_REQUEST['account_id'];
        $passwordHash = $rlValid->xSql($_REQUEST['password_hash']);

        $response = $rlAndroidConnect->savePicture($account_id, $passwordHash);
        break;

    case 'getMyListings':
        if ($json_support) {
            $response_type = "json";
        }
        $type = $rlValid->xSql($_REQUEST['type']);
        $start = (int) $_REQUEST['start'];

        $account_id = (int) $_REQUEST['account_id'];
        $passwordHash = $rlValid->xSql($_REQUEST['password_hash']);

        $response = $rlAndroidConnect->getMyListings($type, $account_id, $passwordHash, $start);
        break;

    case 'getEditListingInfo':
        if ($json_support) {
            $response_type = "json";
        }
        $listing_type = $rlValid->xSql($_GET['listing_type']);
        $listing_id = (int) $_GET['listing_id'];

        $account_id = (int) $_REQUEST['account_id'];
        $passwordHash = $rlValid->xSql($_REQUEST['password_hash']);
        $category_id = $rlValid->xSql($_REQUEST['listing_category_id']);

        $response = $rlAndroidConnect->getEditListingInfo($listing_id, $listing_type, $account_id, $passwordHash, $category_id);
        break;

    case 'removeListing':
        $account_id = (int) $_REQUEST['account_id'];
        $listing_id = (int) $_REQUEST['listing_id'];
        $passwordHash = $rlValid->xSql($_REQUEST['password_hash']);

        $response = $rlAndroidConnect->removeListing($listing_id, $account_id, $passwordHash);
        break;

    case 'validateTransaction':
        $account_id = (int) $_REQUEST['account_id'];
        $passwordHash = $rlValid->xSql($_REQUEST['password_hash']);

        $payment = array(
            'service' => $_REQUEST['payment_service'],
            'tracking_id' => $_REQUEST['payment_tracking_id'],
            'item' => $rlValid->xSql($_REQUEST['payment_item']),
            'title' => $rlValid->xSql($_REQUEST['payment_title']),
            'plan' => (int) $_REQUEST['payment_plan'], // plan id
            'id' => (int) $_REQUEST['payment_id'], // item id
            'amount' => (double) $_REQUEST['payment_amount'],
            'featured' => (int) $_REQUEST['payment_featured'],
            'subscription' => (int) $_REQUEST['subscription'],
            'gateway' => $rlValid->xSql($_REQUEST['payment_gateway']),
        );

        $response = $rlAndroidConnect->validateTransaction($account_id, $passwordHash, $payment);
        break;

    case 'validateYookassaTransaction':
        $account_id = (int) $_REQUEST['account_id'];
        $passwordHash = $rlValid->xSql($_REQUEST['password_hash']);

        $response = $rlAndroidConnect->validateYookassaTransaction($account_id, $passwordHash);
        break;

    case 'confirmYookassaPayment':
        $account_id = (int) $_REQUEST['account_id'];
        $passwordHash = $rlValid->xSql($_REQUEST['password_hash']);

        $response = $rlAndroidConnect->confirmYookassaPayment($account_id, $passwordHash);
        break;

    case 'upgradePlan':
        $account_id = (int) $_REQUEST['account_id'];
        $passwordHash = $rlValid->xSql($_REQUEST['password_hash']);

        $listing_id = (int) $_REQUEST['listing_id'];
        $plan_id = (int) $_REQUEST['plan_id'];
        $listing_mode = $rlValid->xSql($_REQUEST['appearance']);

        $response = $rlAndroidConnect->upgradePlan($account_id, $passwordHash, $listing_id, $plan_id, $listing_mode);
        break;

    case 'getPlans':
        if ($json_support) {
            $response_type = "json";
        }
        $account_id = (int) $_REQUEST['account_id'];
        $password_hash = $rlValid->xSql($_REQUEST['password_hash']);

        $category_id = (int) $_REQUEST['category_id'];
        $account_type = $rlValid->xSql($_REQUEST['account_type']);
        $featured_only = (bool) $_REQUEST['featured_only'];

        $response = $rlAndroidConnect->getPlans($account_id, $password_hash, $category_id, $account_type, $featured_only);
        break;

    case 'upgradePackages':
        $account_id = (int) $_REQUEST['account_id'];
        $password_hash = $rlValid->xSql($_REQUEST['password_hash']);
        $package_id = (int) $_REQUEST['package_id'];
        $plan_id = (int) $_REQUEST['plan_id'];
        $service = $rlValid->xSql($_REQUEST['service']);

        $response = $rlAndroidConnect->upgradePackages($account_id, $password_hash, $package_id, $plan_id, $service);
        break;

    case 'getMyPackages':
        $account_id = (int) $_REQUEST['account_id'];
        $password_hash = $rlValid->xSql($_REQUEST['password_hash']);
        $account_type = $rlValid->xSql($_REQUEST['account_type']);

        $response = $rlAndroidConnect->getMyPackages($account_id, $password_hash, $account_type);
        break;

    case 'getPackages':
        $account_id = (int) $_REQUEST['account_id'];
        $password_hash = $rlValid->xSql($_REQUEST['password_hash']);
        $account_type = $rlValid->xSql($_REQUEST['account_type']);

        $response = $rlAndroidConnect->getPackages($account_id, $password_hash, $account_type);
        break;

    case 'getConversations':
        $account_id = (int) $_REQUEST['account_id'];
        $passwordHash = $rlValid->xSql($_REQUEST['password_hash']);

        $response = $rlAndroidConnect->getConversations($account_id, $passwordHash);
        break;

    case 'fetchMessages':
        $account_id = (int) $_REQUEST['account_id'];
        $start = (int) $_REQUEST['start'];
        $user_id = $rlValid->xSql($_REQUEST['user_id']);
        $admin = (int) $_REQUEST['admin'];

        $response = $rlAndroidConnect->fetchMessages($account_id, $user_id, $start, $admin);
        break;

    case 'sendMessage':
        $account_id = (int) $_REQUEST['from'];
        $user_id = (int) $_REQUEST['to'];
        $listing_id = (int) $_REQUEST['listing_id'];
        $notification = (int) $_REQUEST['notification'];

        $response = $rlAndroidConnect->sendMessage($account_id, $user_id, $listing_id, $notification);
        break;

    case 'contactOwner':
        $response = $rlAndroidConnect->contactOwner();
        break;

    case 'getCountMessages':
        $account_id = (int) $_REQUEST['account_id'];
        $user_id = (int) $_REQUEST['user_id'];

        $response = $rlAndroidConnect->getCountMessages($account_id, $user_id);
        break;

    case 'removeMessages':
        $account_id = (int) $_REQUEST['account_id'];
        $passwordHash = $rlValid->xSql($_REQUEST['password_hash']);
        $user_id = $rlValid->xSql($_REQUEST['user_id']);

        $response = $rlAndroidConnect->removeMessages($account_id, $passwordHash, $user_id);
        break;

    case 'registrNotification':
        if ($json_support) {
            $response_type = "json";
        }
        $account_id = (int) $_REQUEST['account_id'];
        $phone_id = $rlValid->xSql($_REQUEST['phone_id']);
        $token = $rlValid->xSql($_REQUEST['id']);
        $status = $rlValid->xSql($_REQUEST['status']);
        $language = $rlValid->xSql($_REQUEST['language']);

        $response = $rlAndroidConnect->registerForRemoteNotification($account_id, $phone_id, $token, $status, $language);
        break;

    case 'saveLangForNotification':
        if ($json_support) {
            $response_type = "json";
        }
        $account_id = (int) $_REQUEST['account_id'];
        $phone_id = $rlValid->xSql($_REQUEST['phone_id']);
        $language = $rlValid->xSql($_REQUEST['language']);

        $response = $rlAndroidConnect->saveLangForNotification($account_id, $phone_id, $language);
        break;

    case 'saveSearch':
        $account_id = (int) $_REQUEST['account_id'];
        $passwordHash = $rlValid->xSql($_REQUEST['password_hash']);

        $response = $rlAndroidConnect->saveSearch($account_id, $passwordHash);
        break;

    case 'runSaveSearch':
        $id = (int) $_REQUEST['id'];
        $start = (int) $_REQUEST['start'];
        $find_ids = $rlValid->xSql($_REQUEST['find_ids']);
        $sort = $rlValid->xSql($_REQUEST['sort']);

        $response = $rlAndroidConnect->runSaveSearch($id, $start, $find_ids, $sort);
        break;

    case 'getMySaveSearch':
        $account_id = (int) $_REQUEST['account_id'];
        $passwordHash = $rlValid->xSql($_REQUEST['password_hash']);

        $response = $rlAndroidConnect->getMySaveSearch($account_id, $passwordHash);
        break;

    case 'actionSavedSearch':
        $account_id = (int) $_REQUEST['account_id'];
        $passwordHash = $rlValid->xSql($_REQUEST['password_hash']);

        $response = $rlAndroidConnect->actionSavedSearch($account_id, $passwordHash);
        break;

    case 'sendReportBroken':
        $response = $rlAndroidConnect->sendReportBroken();
        break;

    case 'zipLocation':
        if ($json_support) {
            $response_type = "json";
        }
        $query = $rlValid->xSql($_REQUEST['query']);

        $response = $rlAndroidConnect->zipLocation($query);
        break;

    case 'placesСoordinates':
        if ($json_support) {
            $response_type = "json";
        }
        $place_id = $rlValid->xSql($_REQUEST['place_id']);

        $response = $rlAndroidConnect->placesСoordinates($place_id);
        break;
}

if ($_GET['print']) {
    print_r($response);
}

$rlAndroidConnect->send($response, $response_type, $item);
