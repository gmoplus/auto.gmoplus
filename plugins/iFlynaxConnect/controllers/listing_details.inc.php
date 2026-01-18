<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : LISTING_DETAILS.INC.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2024
 *	https://www.flynax.com
 *
 ******************************************************************************/

$listing_id = intval($_REQUEST['lid']);

// get main listing info
$sql = "SELECT `T1`.*, `T2`.`Path`, `T2`.`Type` AS `Listing_type`, `T2`.`Key` AS `Cat_key`, `T2`.`Type` AS `Cat_type`, `T2`.`Path` AS `Cat_path`, ";
$sql.= "`T3`.`Image`, `T3`.`Image_unlim`, `T3`.`Video`, `T3`.`Video_unlim`, CONCAT('categories+name+', `T2`.`Key`) AS `Category_pName` ";
$sql.= "FROM `" . RL_DBPREFIX . "listings` AS `T1` ";
$sql.= "LEFT JOIN `" . RL_DBPREFIX . "categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
$sql.= "LEFT JOIN `" . RL_DBPREFIX . "listing_plans` AS `T3` ON `T1`.`Plan_ID` = `T3`.`ID` ";
$sql.= "LEFT JOIN `" . RL_DBPREFIX . "accounts` AS `T5` ON `T1`.`Account_ID` = `T5`.`ID` ";
$sql.= "WHERE `T1`.`ID` = {$listing_id} AND `T5`.`Status` = 'active' LIMIT 1";
$listing_details = $rlDb->getRow($sql);

if (empty($listing_details)) {
	$iOSHandler->send(array('error' => 'error_ad_details_empty'));
}

// initial response
$response = array();

// load main listings class
$reefless->loadClass('Listings');
$reefless->loadClass('Categories');

// define listing type
$listing_type = $iOSHandler->listing_types[$listing_details['Listing_type']];

// define category id
$category_id = (int)$listing_details['Category_ID'];

// define account id
$account_id = intval($account_info['ID']);

// count uniq listing visits
if ($config['count_listing_visits']) {
	$rlListings->countVisit($listing_id);
}

// get listing photos
$limit_listing_photos = intval($listing_details['Image']);
if (false !== $listing_photos = $iOSHandler->getListingPhotos($listing_id, $limit_listing_photos)) {
	$response['photos'] = $listing_photos;
	unset($listing_photos);
}

// get listing videos
$limit_listing_videos = intval($listing_details['Video']);
if (false !== $listing_videos = $iOSHandler->getListingVideos($listing_id, $limit_listing_videos)) {
    $response['videos'] = $listing_videos;
    unset($listing_videos);
}

// collect mobile numbers for bottom panel
$response['mobile_numbers'] = array();

// get seller information
$seller_id = intval($listing_details['Account_ID']);
$seller_info = $iOSHandler->fetchSellerInfo($seller_id, $response['mobile_numbers']);
$response['sellerInfo'] = & $seller_info;

// build link for ability to Share in App
$ltype_page_path = $iOSHandler->getPagePath($listing_type['Page_key']);
$listing_title = $rlListings->getListingTitle($category_id, $listing_details, $listing_type['Key']);
$seo_link = RL_URL_HOME . $ltype_page_path . '/' . $listing_details['Cat_path'] . '/' . $rlSmarty->str2path($listing_title) . '-' . $listing_id . '.html';
$response['seo_link'] = $seo_link;

// get similar ads if exists
$display_similar_ads = false; // it's a tmp trigger
if ($display_similar_ads && array_key_exists('sl_relevance_mode', $config)) {
	$reefless->loadClass('SimilarListings', null, 'similarListings');

	$similar_ads = $rlSimilarListings->getListings($listing_id);

	if ($similar_ads) {
		$iOSHandler->adaptSimilarListings($similar_ads);

		$response['similar_ads'] = $similar_ads;
		unset($similar_ads);
	}
}

// get plugins if exists
if (array_key_exists('comment_auto_approval', $config)) {
    $comments = $iOSHandler->getComments($listing_id, $account_id, 0, false);
    $response['comments'] = $comments['comments'];
    $response['comments_calc'] = $comments['calc'];
    unset($comments);
}

// get listing sections
$sections = $rlListings->getListingDetails($category_id, $listing_details, $listing_type);

// clear memory
unset($listing_details);

// remove fields if empty sections
$iOSHandler->adaptListingDetailSections($sections);

// build response
$response['sections'] = array();
$skip_field_types = array();

foreach ($sections as $section) {
	if (empty($section['Fields'])) continue;

	$replaceTrigger = false;
	$sIndex = count($response['sections']);
	if (empty($section['name']) && $sIndex) {
		$replaceTrigger = true;
		$sIndex--;
	}

	if (!$replaceTrigger) {
		$response['sections'][$sIndex]['title'] = strval($section['name']);
    }

	foreach ($section['Fields'] as $field) {
	    if (!$field['Details_page']) {
	        continue;
        } else if (in_array($field['Type'], $skip_field_types)) {
            continue;
        }

        $fIndex = $response['sections'][$sIndex]['rows']
        ? count($response['sections'][$sIndex]['rows'])
        : 0;
        $response['sections'][$sIndex]['rows'][$fIndex] = array(
            'id' => intval($field['ID']),
            'key' => strval($field['Key']),
            'type' => strval($field['Type']),
            'title' => strval($field['name']),
            'value' => $iOSHandler->cleanString($field['value'])
        );

        if (in_array($field['Condition'], array('isUrl', 'isEmail'))) {
            $response['sections'][$sIndex]['rows'][$fIndex]['condition'] = $field['Condition'];
        } elseif ($field['Type'] == 'phone' || $field['Key'] == 'phone') {
            $_phone_number = (string) preg_replace('/\W+/i', '', $field['value']);

            $response['sections'][$sIndex]['rows'][$fIndex] += array(
                'condition' => 'isPhone',
                'phoneNumber' => $_phone_number,
            );

            $response['mobile_numbers'][] = array(
                'title' => $iOSHandler->cleanString($field['value']),
                'value' => $_phone_number,
            );
        }
	}

	if (empty($response['sections'][$sIndex]['rows'])) {
	    unset($response['sections'][$sIndex]);
    }
}

// clear memory
unset($sections);

// send response to iOS device
$iOSHandler->send($response);
