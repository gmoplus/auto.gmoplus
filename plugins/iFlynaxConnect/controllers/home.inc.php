<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : HOME.INC.PHP
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

$stack = intval($_REQUEST['stack']);
$tablet = intval($_REQUEST['tablet']);

// trigger for hooks: listingsModifyWhereFeatured,listingsModifyWhereByPeriod
define('WITH_PICTURES_ONLY', false);

// get ads
$listings = $iOSHandler->getAdsForMainScreen($stack, $tablet);

if (defined('CONTROLLER') && CONTROLLER == 'home') {
	$response['home_screen_ads'] = & $listings;
	return;
}

// send response to iOS device
$iOSHandler->send($listings);
