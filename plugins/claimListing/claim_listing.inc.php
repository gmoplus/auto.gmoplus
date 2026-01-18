<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : CLAIM_LISTING.INC.PHP
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

$listing_id                     = (int) $_GET['id'];
$claim_request['method']        = $_POST['claim_method'];
$claim_request['received_code'] = $_POST['received_code'];

if (!defined('IS_LOGIN')) {
    $quick_types = [];
    foreach ($rlAccountTypes->types as &$account_type) {
        if ($account_type['Quick_registration']) {
            $quick_types[] = $account_type;
        }
    }
    $rlSmarty->assign_by_ref('quick_types', $quick_types);
}

if ($page_info['Controller'] == 'claim_listing' && $listing_id) {
    $reefless->loadClass('ClaimListing', null, 'claimListing');

    // get listing info
    $listing_info = $rlClaimListing->getListingInfo($listing_id);
    $rlSmarty->assign_by_ref('listing_info', $listing_info);

    // Get value of phone number for showing to visitor
    if ($listing_info[$config['cl_phone_field']]) {
        $parsedPhone = $reefless->parsePhone(
            $listing_info[$config['cl_phone_field']],
            $rlDb->fetch('*', ['Key' => $config['cl_phone_field']], null, 1, 'listing_fields', 'row'),
            false
        );

        if (method_exists($reefless, 'getPlainPhoneNumber')) {
            $plainPhoneNumber = $reefless->getPlainPhoneNumber($parsedPhone);
        } else {
            $plainPhoneNumber = preg_replace('/\D/', '', $parsedPhone);
        }
        $rlSmarty->assign_by_ref('plainPhoneNumber', $plainPhoneNumber);
    }

    // find all listings with same phone
    if ($config['cl_phone_field'] && $config['cl_by_same_phone']) {
        $rlSmarty->assign_by_ref(
            'cl_phone_listings',
            $rlClaimListing->getListingsByPhone($listing_info[$config['cl_phone_field']], $listing_info['Account_ID'])
        );

        $claim_request['phone_listings'] = $_POST['phone_listings'];
    }

    // find all listings with same email
    if ($config['cl_email_field'] && $config['cl_by_same_email']) {
        $rlSmarty->assign_by_ref(
            'cl_email_listings',
            $rlClaimListing->getListingsByEmail($listing_info[$config['cl_email_field']], $listing_info['Account_ID'])
        );

        $claim_request['email_listings'] = $_POST['email_listings'];
    }

    if ($account_info['ID'] != $listing_info['Account_ID']) {
        if ($claim_request['method']) {
            $message = $rlClaimListing->claimAd($claim_request);

            // Errors handler
            if (is_array($message) && isset($message['errors']) && isset($message['error_fields'])) {
                $errors       = $message['errors'];
                $error_fields = $message['error_fields'];
            } else {
                // Build url to my listings page
                if ($config['one_my_listings_page']) {
                    $page_key = 'my_all_ads';
                } else {
                    $listing = $rlListings->getListing($listing_info['ID']);
                    $page_key = 'my_' . $listing['Listing_type'];
                }
                $my_listings_url = $reefless->getPageUrl($page_key);

                // Save notice
                $reefless->loadClass('Notice');
                $rlNotice->saveNotice($message);

                // Redirect to my listings page
                $reefless->redirect(null, $my_listings_url);
            }
        }
    } else {
        $message = $errors = $lang['cl_claim_error'];
        $rlSmarty->assign('message', $message);
    }
}
