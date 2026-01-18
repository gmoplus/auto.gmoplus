<?php

// Load system config file
use Flynax\Plugins\AccountSync\Router;

require_once __DIR__ . '/../../includes/config.inc.php';
require_once RL_INC . 'control.inc.php';
require_once RL_CLASSES . "rlSecurity.class.php";

// set language
$request_lang = @$_REQUEST['lang'] ?: $config['lang'];
$rlValid->sql($request_lang);

$languages = $rlLang->getLanguagesList();
$rlLang->defineLanguage($request_lang);
$rlLang->modifyLanguagesList($languages);

$seo_base = RL_URL_HOME;
if ($config['lang'] != RL_LANG_CODE && $config['mod_rewrite']) {
    $seo_base .= RL_LANG_CODE . '/';
}
if (!$config['mod_rewrite']) {
    $seo_base .= 'index.php';
}

$reefless->loadClass('AccountSync', null, 'accountSync');

Router::load('routes.php');

$rlDb->connectionClose();
