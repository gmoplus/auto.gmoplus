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
 *	Copyrights Flynax Classifieds Software | 2023
 *	https://www.flynax.com
 *
 ******************************************************************************/

// Include PSR-4 autoloader
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

require_once '../../includes/config.inc.php';
require_once RL_PLUGINS . 'iFlynaxConnect' . RL_DS . 'config.inc.php';
require_once RL_IPHONE_CONTROLLERS . 'control.inc.php';

// force disable output of PHP notifications
error_reporting(E_ERROR);
ini_set('display_errors', 0);

/* convert JSON body into array */
if (preg_match('/application\/json/', $_SERVER['CONTENT_TYPE'])) {
    $jsonBody = file_get_contents('php://input');
    $inputJSON = json_decode($jsonBody, true);

    if (is_array($inputJSON)) {
        if (is_array($_REQUEST)) {
            $_REQUEST = array_merge_recursive($_REQUEST, $inputJSON);
        } else {
            $_REQUEST = $inputJSON;
        }
    } else {
        $iOSHandler->send(array('api_error' => 'wrong_intput_data_type'));
    }
}
/* convert JSON body into array END */

// load system configurations
$config = $rlConfig->allConfig();

// check it anyway!
$_where = array('Status' => 'active', 'Key' => 'iFlynaxConnect');
$plugin = $rlDb->fetch(array('Version'), $_where, null, 1, 'plugins', 'row');

if (!$plugin) {
    $iOSHandler->send(array(
        'available' => false,
        'api_error' => 'plugin_inactive'
    ));
}

// it's ping from app?
if (array_key_exists('ping', $_REQUEST)) {
    $response = array(
        'available' => true,
        'version' => $plugin['Version'],
        'app_version' => $config['app_version']
    );
    $iOSHandler->send($response);
}

// check security token
if (empty($config['iflynax_synch_code']) || $_REQUEST['synch_code'] != $config['iflynax_synch_code']) {
    $iOSHandler->send(array('api_error' => 'error_synch_code'));
}

$iOSHandler->loadActivePluginsList();

// define app lang code
define('APP_LANG_CODE', $iOSHandler->getAppLanguage());

// define website lang code
define('RL_LANG_CODE', $iOSHandler->getSiteLanguage(APP_LANG_CODE));

/* define seo_base */
$_seo_base = RL_URL_HOME;
if ($config['mod_rewrite'] && $config['lang'] != RL_LANG_CODE) {
    $_seo_base .= RL_LANG_CODE . '/';
} elseif (!$config['mod_rewrite']) {
    $_seo_base .= 'index.php';
}
define('SEO_BASE', $_seo_base);
/* define seo_base end */

// load all fronEnd phrases
$lang = $rlLang->getLangBySide('frontEnd', RL_LANG_CODE);
$GLOBALS['lang'] = &$lang;

// fetch and assign listing types to iOSHandler::listing_types
$reefless->loadClass('ListingTypes', null, false, true);
$iOSHandler->listing_types = $rlListingTypes->types;

define('RL_DATE_FORMAT', $rlDb->getOne('Date_format', "`Code` = '" . APP_LANG_CODE . "'", 'iflynax_languages'));
define('RL_TPL_BASE', RL_URL_HOME . 'templates/' . $config['template'] . '/');

// load system libs
require_once RL_LIBS . 'system.lib.php';

// set timezone
if (method_exists($reefless, 'setTimeZone')) {
    $reefless->setTimeZone();
}

// login attempts control
if (method_exists($reefless, 'loginAttempt')) {
    $reefless->loginAttempt();
}

if (file_exists(RL_PLUGINS . 'shoppingCart' . RL_DS . 'rlShoppingCart.class.php')) {
    $reefless->loadClass('ShoppingCart', null, 'shoppingCart');
}

/* HOT FIX: disabled  membership plans */
if (file_exists(RL_CLASSES . 'rlMembershipPlan.class.php')) {
    $config['membership_module'] = 0;
    $config['allow_listing_plans'] = 0;
    $reefless->loadClass('MembershipPlan');
}
/* FIX END */

// simulate auto login by token
if (isset($_REQUEST['accountToken']) && !empty($_REQUEST['accountToken'])) {
    if (!$rlAccount->isLogin()) {
        if (false === $iOSHandler->loginWithToken($_REQUEST['accountToken'])) {
            $iOSHandler->send(array('api_error' => 'error_session_expired'));
        }
    }
}

// check user login
if ($rlAccount->isLogin()) {
    $account_info = $_SESSION['account'];
    define('IS_LOGIN', true);
}

// account abilities handler
$deny_pages = array();
$admin_only_types = 0;

foreach ($iOSHandler->listing_types as $lKey => $lType) {
    /**
     * @todo remove once the empty type assign is fixed in Events plugins
     */
    if (!$lType['ID']) {
        unset($iOSHandler->listing_types[$lKey]);
        continue;
    }

    if ($_SESSION['abilities'] && !in_array($lType['Key'], $_SESSION['abilities'])) {
        $deny_pages[] = 'my_' . $lType['Key'];
    }

    // count admin only types
    $admin_only_types += $lType['Admin_only'] ? 1 : 0;
}

if (empty($_SESSION['abilities'])
    || empty($iOSHandler->listing_types)
    || $admin_only_types == count($iOSHandler->listing_types)
) {
    $deny_pages[] = 'add_listing';
    $deny_pages[] = 'payment_history';
    $deny_pages[] = 'my_packages';
}

$controller = $_REQUEST['controller'];
if (file_exists(RL_IPHONE_CONTROLLERS . $controller . '.inc.php')) {
    require_once RL_IPHONE_CONTROLLERS . $controller . '.inc.php';
}
// return API error to device
else {
    $iOSHandler->send(array('api_error' => 'error_invalid_controller'));
}
