<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: REQUESTS.PHP
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

// Load system config file
use Flynax\Plugin\WordPressBridge\Router;

require __DIR__ . '/../../includes/config.inc.php';
require RL_INC . 'control.inc.php';

// set language
$request_lang = @$_REQUEST['lang'] ?: $config['lang'];
$rlValid->sql($request_lang);

$languages = $rlLang->getLanguagesList();
$rlLang->defineLanguage($request_lang);
$rlLang->modifyLanguagesList($languages);

$lang = $rlLang->getLangBySide('common', RL_LANG_CODE);
$GLOBALS['lang'] = &$lang;

$seo_base = RL_URL_HOME;
if ($config['lang'] != RL_LANG_CODE && $config['mod_rewrite']) {
    $seo_base .= RL_LANG_CODE . '/';
}
if (!$config['mod_rewrite']) {
    $seo_base .= 'index.php';
}

$reefless->loadClass('Admin', 'admin');
$reefless->loadClass('WordpressBridge', null, 'wordpressBridge');

Router::load('routes.php');

$rlDb->connectionClose();
