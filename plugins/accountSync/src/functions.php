<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.7.0
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: FUNCTIONS.PHP
 *
 *	The software is a commercial product delivered under single, non-exclusive,
 *	non-transferable license for one domain or IP address. Therefore distribution,
 *	sale or transfer of the file in whole or in part without permission of Flynax
 *	respective owners is considered to be illegal and breach of Flynax License End
 *	User Agreement.
 *
 *	You are not allowed to remove this information from the file without permission
 *	of Flynax respective owners.
 *
 *	Flynax Classifieds Software 2022 |  All copyrights reserved.
 *
 *	https://www.flynax.com/
 *
 ******************************************************************************/

use Flynax\Components\ObjectsContainer;

if (!function_exists('asMake')) {
    /**
     * Get instance of the Flynax class
     *
     * @param string $className - Flynax class name
     *
     * @return mixed
     */
    function asMake($className)
    {
        if (!$className) {
            return false;
        }

        return ObjectsContainer::i()->make($className);
    }
}

if (!function_exists('asLang')) {
    function asLang($key)
    {
        return (string) $GLOBALS['lang'][$key];
    }
}
