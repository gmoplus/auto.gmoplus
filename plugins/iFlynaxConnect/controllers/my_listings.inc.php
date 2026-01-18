<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : MY_LISTINGS.INC.PHP
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
$type = $rlValid->xSql($_REQUEST['type']);
$action = $_REQUEST['cmd'];

$response = array(
    'listings' => array(),
    'calc' => 0
);

switch ($action) {
    case 'fetch':
        $reefless->loadClass('Listings');
        $listings = $rlListings->getMyListings($type, 'Date', 'DESC', $stack, $config['iflynax_grid_listings_number']);

        foreach ($listings as $key => $entry) {
            $response['listings'][] = $iOSHandler->adaptShortFormWithData($entry, true);
        }
        unset($listings);

        $response['calc'] = intval($rlListings->calc);
        break;

    case 'remove':
        
        break;
}

$iOSHandler->send($response);
