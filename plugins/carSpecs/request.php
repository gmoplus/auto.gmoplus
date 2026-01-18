<?php
/**copyright**/

header('Access-Control-Allow-Origin: *');

define('AJAX_FILE', true);

require_once('../../includes/config.inc.php');
require_once(RL_INC . 'control.inc.php');

// set language
$request_lang = $request_lang ? $request_lang : $config['lang'];
$rlValid->sql($request_lang);

$languages = $rlLang->getLanguagesList();
$request_lang = @$_REQUEST['lang'];
$rlLang->defineLanguage($request_lang);

// load listing types
$reefless->loadClass('ListingTypes', null, false, true);

// get page paths
$reefless->loadClass('Navigator');
$pages = $rlNavigator->getAllPages();

// load classes
$reefless -> loadClass('Account');

// define seo base
$seo_base = RL_URL_HOME;
if ($config['lang'] != RL_LANG_CODE && $config['mod_rewrite']) {
    $seo_base .= RL_LANG_CODE . '/';
}
if (!$config['mod_rewrite']) {
    $seo_base .= 'index.php';
}

$rlHook->load('seoBase');
define('SEO_BASE', $seo_base);

// validate data
$request_mode = $rlValid->xSql($_REQUEST['mode']);
$request_item = $rlValid->xSql($_REQUEST['item']);

// out variable will be printed as response
$out = '';


$reefless->loadClass('CarSpecs', null, 'carSpecs');
$rlCarSpecs->hookAjaxRequest($out, $request_mode, $request_item, $request_item );

if (!empty($out)) {
    $reefless->loadClass('Json');
    echo $rlJson->encode($out);
} else {
    echo null;
}
