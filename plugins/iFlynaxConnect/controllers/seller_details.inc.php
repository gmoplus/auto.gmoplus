<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : SELLER_DETAILS.INC.PHP
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

$account_id = intval($_REQUEST['aid']);

// fetch seller info
$response['sellerInfo'] = $iOSHandler->fetchSellerInfo($account_id);

// fetch a first stack of seller ads
$response['sellerAds'] = $iOSHandler->getListingsByAccount($account_id);

// reassign location details
if (isset($response['sellerInfo']['location'])) {
    $response['location'] = $response['sellerInfo']['location'];
    unset($response['sellerInfo']['location']);
}

// send response to iOS device
$iOSHandler->send($response);
