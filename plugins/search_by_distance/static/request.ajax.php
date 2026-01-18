<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : REQUEST.AJAX.PHP
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

header('Access-Control-Allow-Origin: *');

define('AJAX_FILE', true);

require_once('../../../includes/config.inc.php');
require_once(RL_INC . 'control.inc.php');

// set language
$request_lang = @$_REQUEST['lang'] ?: $config['lang'];
$rlValid->sql($request_lang);

$languages = $rlLang->getLanguagesList();
$rlLang->defineLanguage($request_lang);
$rlLang->modifyLanguagesList($languages);

// load listing types
$reefless->loadClass('ListingTypes', null, false, true);

// get page paths
$reefless->loadClass('Navigator');
$pages = $rlNavigator->getAllPages();

// load classes
$reefless -> loadClass('Account');
$reefless -> loadClass('MembershipPlan');

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
$out = array();

// ajax request hook
$rlHook->load('ajaxRequest', $out, $request_mode, $request_item, $request_lang);

if (!empty($out)) {
    echo json_encode($out);
} else {
    echo null;
}
