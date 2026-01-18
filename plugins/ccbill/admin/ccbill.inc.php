<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : CCBILL.INC.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2022
 *	https://www.flynax.com
 *
 ******************************************************************************/

$reefless->loadClass('Plan');
$reefless->loadClass('Ccbill', null, 'ccbill');
if ($GLOBALS['membership_module']) {
    $reefless->loadClass('MembershipPlansAdmin', 'admin');
}
if ($rlCcbill->isPluginInstalled('banners')) {
    $reefless->loadClass('Banners', null, 'banners');
}
if ($rlCcbill->isPluginInstalled('payAsYouGoCredits')) {
    $reefless->loadClass('Credits', null, 'payAsYouGoCredits');
}

// prepare object plans by group
$listing_plans = $rlPlan->getPlans();
$groups = array(
    'listing_plans' => array(
        'name' => $lang['ccbill_listing_plans'],
        'Key' => 'listing_plans',
        'items' => $listing_plans,
    ),
);

if ($GLOBALS['membership_module']) {
    $membership_plans = $rlMembershipPlansAdmin->getPlans();
    $groups['membership_plans'] = array(
        'name' => $lang['ccbill_membership_plans'],
        'Key' => 'membership_plans',
        'items' => $membership_plans,
        'service' => 'membership',
    );
}
if ($rlCcbill->isPluginInstalled('banners')) {
    $banner_plans = $rlBanners->getBannerPlans();
    $groups['banner_plans'] = array(
        'name' => $lang['ccbill_banner_plans'],
        'Key' => 'banner_plans',
        'items' => $banner_plans,
        'service' => 'banners',
    );
}
if ($rlCcbill->isPluginInstalled('payAsYouGoCredits')) {
    $credit_packages = $rlCredits->get();
    $groups['credit_packages'] = array(
        'name' => $lang['ccbill_credit_packages'],
        'Key' => 'credit_packages',
        'items' => $credit_packages,
        'service' => 'credits',
    );
}

if ($_POST['f']) {
    $rlCcbill->setObjectItems($groups);
    $rlCcbill->saveSettings($_POST['f']);
}

$rlCcbill->getSettings($groups);
$rlSmarty->assign_by_ref('ccbill_groups', $groups);
