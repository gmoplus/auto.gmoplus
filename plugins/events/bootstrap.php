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

require_once __DIR__ . '/vendor/autoload.php';

use \Flynax\Components\ObjectsContainer;

if (!function_exists('eventsContainerMake')) {
    /**
     * Flynax object container helper
     *
     * @param  string $className - Flynax class name
     * @return bool|mixed
     */
    function eventsContainerMake($className = '')
    {
        if (!$className) {
            return false;
        }

        return ObjectsContainer::i()->make($className);
    }
}
