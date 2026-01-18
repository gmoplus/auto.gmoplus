<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : SEARCH.INC.PHP
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

$rlSmarty -> assign('no_h1', true);

$reefless -> loadClass('Listings');
$reefless -> loadClass('SearchByDistance', null, 'search_by_distance');

// Define default country name
if ($config['sbd_default_country']) {
    $country_name = array_search($config['sbd_default_country'], $rlSearchByDistance->country_iso);
    $country_name = ucwords(str_replace('_', ' ', $country_name));
    $rlSmarty->assign_by_ref('country_name', $country_name);
}
// Redefine option to allow visitor search the location location instead of zip
elseif (!$config['sbd_country_field']) {
    $config['sbd_search_mode'] = 'mixed';
}

$reefless -> loadClass('Search');

// Get search forms
foreach ($rlListingTypes->types as $type_key => $listing_type) {
    if ($listing_type['Search_page']) {
        if ($search_form = $rlSearch->buildSearch($type_key.'_quick')) {
            $form_key = $type_key.'_quick';
            $out_search_forms[$form_key]['data'] = $search_form;
            $out_search_forms[$form_key]['name'] = $lang['search_forms+name+'.$form_key];
            $out_search_forms[$form_key]['listing_type'] = $type_key;

            $search_types[] = $listing_type;
        }
    }

    unset($search_form);
}

$rlSmarty->assign_by_ref('search_forms', $out_search_forms);
$rlSmarty->assign_by_ref('search_types', $search_types);
