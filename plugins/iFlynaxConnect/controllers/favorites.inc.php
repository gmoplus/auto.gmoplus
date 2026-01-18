<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : FAVORITES.INC.PHP
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
$ids = $rlValid->xSql($_REQUEST['ids']);
$action = $_REQUEST['action'] ? $_REQUEST['action'] : 'fetch';
$listing_id = intval($_REQUEST['lid']);
$account_id = intval($account_info['ID']);
$response = array();

$_COOKIE['favorites'] = $ids;

$reefless->loadClass('Listings');
$reefless->loadClass('Actions');

switch ($action) {
    case 'fetch':
        $response = array(
            'listings' => array(),
            'calc' => 0,
        );
        $listings = $rlListings->getMyFavorite('ID', 'ASC', $stack, $config['iflynax_grid_listings_number']);

        if (!empty($listings)) {
            foreach($listings as $key => $entry) {
                $response['listings'][] = $iOSHandler->adaptShortFormWithData($entry);
            }
            $response['calc'] = intval($rlListings->calc);

            // clear memory
            unset($listings);
        }
        break;

    case 'add':
        $iOSHandler->addToFavorites($listing_id, $account_id);
        break;

    case 'remove':
        $iOSHandler->removeFromFavorites($listing_id, $account_id);
        break;
}

// send response to iOS device
$iOSHandler->send($response);
