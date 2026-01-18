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
 *	Copyrights Flynax Classifieds Software | 2023
 *	https://www.flynax.com
 *
 ******************************************************************************/

require_once RL_CLASSES . 'rlDb.class.php';

// connect to database
$rlDb = new rlDb;
$rlDb->connect(RL_DBHOST, RL_DBPORT, RL_DBUSER, RL_DBPASS, RL_DBNAME);

// The plugin installed and active; let's go!
session_start();

// init website main class
require_once RL_CLASSES . 'reefless.class.php';
$reefless = new reefless;

/* Emulate smarty class */
class Smarty
{
    public function __call($name, $arguments)
    {}
}
require_once RL_CLASSES . 'rlSmarty.class.php';
class FakeSmarty extends rlSmarty {
    public function __construct()
    {}
}
$rlSmarty = new FakeSmarty;
/* Emulate smarty class END */

// load helper classes
$reefless->loadClass('Debug');
$reefless->loadClass('Config');
$reefless->loadClass('Common');
$reefless->loadClass('Lang');
$reefless->loadClass('Valid');
$reefless->loadClass('Hook');
$reefless->loadClass('Account');
$reefless->loadClass('Cache');

// init app main class
require_once RL_PLUGINS . 'iFlynaxConnect' . RL_DS . 'rlIFlynaxConnect.class.php';
$iOSHandler = new rlIFlynaxConnect;

// utf8 library functions if necessary
if (function_exists('loadUTF8functions') === false) {
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
