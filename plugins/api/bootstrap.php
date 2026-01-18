<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: BOOTSTRAP.PHP
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

require_once __DIR__ . '/../../includes/config.inc.php';

$core_autoload = require_once __DIR__ . '/../../vendor/autoload.php';
$api_autoload  = require_once __DIR__ . '/vendor/autoload.php';

// Instantiate the API container
$api = new Flynax\Api\Api(__DIR__);

rl('Db')->connect(RL_DBHOST, RL_DBPORT, RL_DBUSER, RL_DBPASS, RL_DBNAME);

// Is installed plugin
$version = rl('Db')->getOne('Version', "`Key` = 'api' AND `Status` = 'active'", 'plugins');
if (!$version) {
   exit;
}

rl('Debug');
rl('Config');
rl('Lang');
rl('Valid');
rl('Hook');
rl('Cache');
rl('Common');
rl('Account');
rl('MembershipPlan');

// get list of plugins
if (!$GLOBALS['plugins']) {
    $plugins = rl('Common')->getInstalledPluginsList();
    $GLOBALS['plugins'] = $plugins;
}

/* utf8 library functions */
if (!function_exists('loadUTF8functions')) {
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
}
// load system libs
require_once RL_LIBS . 'system.lib.php';

return $api;
