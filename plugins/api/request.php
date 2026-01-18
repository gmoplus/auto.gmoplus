<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: REQUEST.PHP
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

define('API_START', microtime(true));

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$api = require_once __DIR__ . '/bootstrap.php';

/* load system configurations */
$GLOBALS['config'] = $config = rl('Config')->allConfig();
rl('ApiPlugin', null, 'api')->setLocale();
rl('Smarty', null, 'api');

rl('reefless')->setTimeZone();
rl('reefless')->setLocalization();

/* define is agent */
define('IS_BOT', rl('reefless')->isBot());

$lang = $GLOBALS['lang'] = rl('Lang')->getLangBySide('frontEnd', RL_LANG_CODE);

rl('Lang')->extDefineLanguage();
$languages = rl('Lang')->getLanguagesList();
rl('ListingTypes', null, null, true);
rl('Listings');
rl('Actions');
rl('Navigator');

// Service package
if ($config['package_name'] == 'service') {
    rl('Service');
}

/* assign base path */
$bPath = RL_URL_HOME;
if ($config['lang'] != RL_LANG_CODE && $config['mod_rewrite']) {
    $bPath .= RL_LANG_CODE . '/';
}


define('SEO_BASE', $bPath);

// Load the API routes
require_once __DIR__ . '/src/routes.php';

// Instantiate the requests
$request = Illuminate\Http\Request::createFromGlobals();

try {
    $response = $api->router->dispatch($request);
    if ($response->original) {
        $response->header('Charset', 'utf-8');
        $response->header('Content-Type','application/json; charset=utf-8');
        $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);

        if ($_GET['print']) {
            print_r($response->original);
            exit;
        }
    }

    $response->send();
} catch (NotFoundHttpException $notFound) {
    response('Oops! this page does not exists', 400)->send();
} finally {
    rl('Db')->connectionClose();
}
