<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : BOOTSTRAP.PHP
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

use Flynax\Plugins\PWA\Config;

require_once __DIR__ . '/vendor/autoload.php';

$appKey = $GLOBALS['rlConfig']->getConfig('pwa_key');

define('PWA_APP_KEY', 'flynax_pwa');

define('PWA_ROOT', RL_PLUGINS . 'PWA/');
define('PWA_ROOT_URL', RL_PLUGINS_URL . 'PWA/');

define('PWA_FILES_PATH', PWA_ROOT . 'files/');
define('PWA_FILES_URL', PWA_ROOT_URL . 'files/');

/* set vapid public and private keys */
if ($vapid_public = $GLOBALS['rlConfig']->getConfig('pwa_vapid_public')) {
    Config::i()->setConfig('vapid_public', $vapid_public);
}

if ($vapid_private = $GLOBALS['rlConfig']->getConfig('pwa_vapid_private')) {
    Config::i()->setConfig('vapid_private', $vapid_private);
}

/* set all multi-language configurations which will be stored in the lang_keys table */
Config::i()->multiLangConfigs = ['name', 'short_name', 'description'];
Config::i()->fetchAllConfigs();

$pluginMeta = simplexml_load_string(file_get_contents(RL_PLUGINS . 'PWA/install.xml'));
define('PWA_PLUGIN_VERSION', (string) $pluginMeta->version);

/* set subscription columns */
$subscriptions = [
    'new_listing' => 'Alerts',
    'new_message' => 'Messages',
];
Config::i()->setConfig('account_subscription', $subscriptions);
