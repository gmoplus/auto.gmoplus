<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RV_LISTINGS.INC.PHP
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

$reefless->loadClass('RecentlyViewed', null, 'recentlyViewed');

if ($account_info['ID'] && $_SESSION['sync_rv_complete']) {
    $rv_listings_ids = $rlDb->getOne('rv_listings', "`ID` = {$account_info['ID']}", 'accounts');
    if (substr($rv_listings_ids, -1, 1) == ',') {
        $rv_listings_ids = substr_replace($rv_listings_ids, '', strrpos($rv_listings_ids, ','));
    }
    $tmp_rv_listings = $rlRecentlyViewed->getRvListings($rv_listings_ids, false, true);

    if ($tmp_rv_listings) {
        $rv_listings       = [];
        $storageListings   = [];

        // removing inactive listings from storage and DB
        foreach ($tmp_rv_listings as $listing) {
            if ($listing['Listing_status'] === 'active'
                && $listing['Category_status'] === 'active'
                && $listing['Owner_status'] === 'active'
            ) {
                $rv_listings[] = $listing;
                $rv_ids        = $rv_ids ? ($rv_ids . ',' . $listing['ID']) : $listing['ID'];
            }
        }

        $rlDb->query(
            "UPDATE `{db_prefix}accounts` SET `rv_listings` = '{$rv_ids}' 
             WHERE `ID` = {$account_info['ID']} LIMIT 1"
        );

        foreach ($rv_listings as $key => $listing) {
            $storageListings[] = [
                $listing['ID'],
                $listing['Main_photo'],
                $GLOBALS['pages']['lt_' . $listing['Listing_type']],
                $listing['Path'] . '/' . $GLOBALS['rlSmarty']->str2path($listing['listing_title']),
                trim(preg_replace('/\s+/', ' ', addslashes($listing['listing_title']))),
                $listing['url'] ?: 'false',
            ];
        }

        $rlSmarty->assign('inactive_listings', 1);
        $rlSmarty->assign('rvStorageListings', $storageListings);

        $rv_listings_ids = $rv_ids;
    }

    $pInfo['current'] = (int) $_GET['pg'];
    $pInfo['calc']    = count(explode(',', $rv_listings_ids));
    $rlSmarty->assign_by_ref('pInfo', $pInfo);

    $rv_listings = $rlRecentlyViewed->getRvListings($rv_listings_ids, $pInfo['current']);
    $rlSmarty->assign('listings', $rv_listings);

    if ($rv_listings) {
        $navIcons[] = <<< HTML
            <a class="button low rv_del_listings" style="margin-top: 6px;" href="javascript:void(0)">
                <span>{$lang['rv_del_listings']}</span>
            </a>
HTML;
        $rlSmarty->assign_by_ref('navIcons', $navIcons);
    }
}
