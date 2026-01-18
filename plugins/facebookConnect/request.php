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

use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\FacebookSession;
use Facebook\GraphUser;

// force disable output of PHP notifications
error_reporting(E_ERROR);
ini_set('display_errors', 0);

// Load system config file
require_once __DIR__ . '/../../includes/config.inc.php';

/**
 * Register the autoloader for the Facebook SDK classes.
 * Based off the official PSR-4 autoloader example found here:
 * https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
 *
 * @param string $class The fully-qualified class name.
 * @return void
 */
function facebook_api_autoloader($class)
{
    // project-specific namespace prefix
    $prefix = 'Facebook\\';

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/Facebook/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
}
spl_autoload_register('facebook_api_autoloader');

/**
 * Save errors/exeptions to log file
 */
function _saveErrorAndRedirectToHomePage($error)
{
    $message = null;

    if (is_object($error)) {
        $message = $error->getMessage();
    } elseif (is_string($error)) {
        $message = $error;
    }

    if ($message != null) {
        $log_message = sprintf('%s | FB: %s', date('d M (h:i:s)'), $message) . PHP_EOL;
        file_put_contents(RL_TMP . 'errorLog/errors.log', $log_message, FILE_APPEND);
    }

    // save error notice
    $_SESSION['notice'] = $message;
    $_SESSION['notice_type'] = 'error';

    header('Location: ' . RL_URL_HOME);
    exit;
}

/**
 * Generate link to page based on mod_rewrite
 */
function _generateLinkWithPageKey($key)
{
    $page_path = $_SESSION['facebook_page_' . $key];
    $link = $_SESSION['facebook_base_url'];
    $link .= $GLOBALS['config']['mod_rewrite'] ? $page_path . '.html' : '?page=' . $page_path;

    return $link;
}

/** Common **/

$domain_info = parse_url(RL_URL_HOME);
$domain_info['domain'] = "." . preg_replace("/^(www.)?/", "", $domain_info['host']);
$domain_info['path'] = '/' . trim(RL_DIR, '/');
session_set_cookie_params(0, $domain_info['path'], $domain_info['domain']);
session_start();

$valid_website_url = (stristr($_SERVER['HTTPS'], 'on')
    ? str_replace('http:', 'https:', RL_URL_HOME) 
    : RL_URL_HOME
);

// load database instance
require_once RL_CLASSES . 'rlDb.class.php';

$rlDb = new rlDb();
$rlDb->connect(RL_DBHOST, RL_DBPORT, RL_DBUSER, RL_DBPASS, RL_DBNAME);
$db_prefix = RL_DBPREFIX;

// get plugin configs
$sql = "
    SELECT `Key`, `Default` FROM `{$db_prefix}config` 
    WHERE `Plugin` = 'facebookConnect' AND `Type` <> 'divider' 
    OR `Key` IN ('mod_rewrite', 'lang')
";
$config = $rlDb->getAll($sql, array('Key', 'Default'));

// get plugin configs
$lang_code = isset($_SESSION['facebook_lang_code']) ? $_SESSION['facebook_lang_code'] : $config['lang'];
$sql = "
    SELECT `Key`, `Value` FROM `{$db_prefix}lang_keys` 
    WHERE `Code` = '{$lang_code}' AND `Plugin` = 'facebookConnect' 
    AND `Key` LIKE 'fConnect\_api\_%'
";
$lang = $rlDb->getAll($sql, array('Key', 'Value'));

// deep checking
if (0 === (int) $config['facebookConnect_module']
    || empty($config['facebookConnect_appid'])
    || empty($config['facebookConnect_secret'])
) {
    _saveErrorAndRedirectToHomePage($lang['fConnect_api_wrong_configuration']);
}

FacebookSession::setDefaultApplication(
    $config['facebookConnect_appid'],
    $config['facebookConnect_secret']
);

$callback_url = sprintf('%s/plugins/facebookConnect/request.php', rtrim($valid_website_url, '/'));
$helper = new FacebookRedirectLoginHelper($callback_url);
$session = null;
$me = null;

try {
    $session = $helper->getSessionFromRedirect();
} catch (FacebookRequestException $exception) {
    _saveErrorAndRedirectToHomePage($exception);
}

if ($session == null) {
    $permissions = array('email');
    $loginUrl = $helper->getLoginUrl($permissions);
    header('Location: ' . $loginUrl);
    exit;
}

try {
    $parameters = array(
        'appsecret_proof' => hash_hmac('sha256', $session->getToken(), $config['facebookConnect_secret'])
    );
    $request = new FacebookRequest($session, 'GET', '/me?fields=id,name,email,verified', $parameters);
    $me = $request->execute()->getGraphObject(GraphUser::className());
} catch (FacebookRequestException $exception) {
    _saveErrorAndRedirectToHomePage($exception);
} catch (\Exception $exception) {
    _saveErrorAndRedirectToHomePage($exception);
}

if ($me != null && 0 !== $facebook_id = (int) $me->getId()) {
    $account_exists = $rlDb->getRow("
        SELECT `ID` FROM `{$db_prefix}accounts` 
        WHERE `facebook_ID` = {$facebook_id}
    ");
    $_SESSION['facebook_info'] = $me->asArray();

    // login process
    if (!empty($account_exists)) {
        $_SESSION['facebook_process'] = 'login';
        header('Location: ' . _generateLinkWithPageKey('login'));
    }
    // registration process
    else {
        $_SESSION['facebook_process'] = 'registration';
        header('Location: ' . _generateLinkWithPageKey('registration'));
    }
} else {
    _saveErrorAndRedirectToHomePage($lang['fConnect_api_wrong_response']);
}
