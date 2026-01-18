<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : CONTROL.INC.PHP
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

require_once(RL_CLASSES . 'rlDb.class.php');
require_once(RL_CLASSES . 'reefless.class.php');

$rlDb = new rlDb();
$reefless = new reefless();

/* load classes */
$reefless->connect(RL_DBHOST, RL_DBPORT, RL_DBUSER, RL_DBPASS, RL_DBNAME);
$reefless->loadClass('Debug');
$reefless->loadClass('Config');
$reefless->loadClass('Lang');
$reefless->loadClass('Valid');
$reefless->loadClass('Hook');
$reefless->loadClass('Listings');
$reefless->loadClass('Categories');

// load system configurations
$config = $rlConfig->allConfig();
$GLOBALS['config'] = $config;

$reefless->loadClass('ListingTypes');

$reefless->loadClass('Common');
$plugins = $rlCommon->getInstalledPluginsList();

// utf8 library functions
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
