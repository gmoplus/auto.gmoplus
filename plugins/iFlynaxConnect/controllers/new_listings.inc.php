<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : NEW_LISTINGS.INC.PHP
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

$response = array(
    'listings' => array(),
    'calc' => 0
);

// simulate rlListingTypes class
$rlListingTypes = new stdClass;
$rlListingTypes->types = & $iOSHandler->listing_types;

$reefless->loadClass('Listings');
$listings = $rlListings->getRecentlyAdded($stack, $config['iflynax_grid_listings_number'], $type);

if (empty($listings)) {
	$iOSHandler->send($response);
}

$sections_diff = array();
$_sections = array();

foreach ($listings as $key => $entry) {
	// build section
	$date_diff = intval($entry['Date_diff']);
	if (!array_key_exists($date_diff, $sections_diff)) {
		$section_index = count($_sections);
		$sections_diff[$date_diff] = $section_index;

		// init the section
		$_sections[$section_index] = array(
			'title' => $iOSHandler->buildSectionTitleWithDateDiff($date_diff, $entry['Post_date']),
			'rows' => array()
		);
	}

	$section = &$_sections[$sections_diff[$date_diff]];
	$listing = $iOSHandler->adaptShortFormWithData($entry);

	// put row to section
	$section['rows'][] = $listing;
	unset($listing);
}
$response['listings'] = $_sections;
$response['calc'] = intval($rlListings->calc);

// clear memory
unset($listings, $sections_diff, $_sections);

$iOSHandler->send($response);
