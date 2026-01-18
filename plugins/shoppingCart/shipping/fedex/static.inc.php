<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : STATIC.INC.PHP
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

$GLOBALS['fedexServices'] = array(
    array('key' => "europe_first_international_priority", 'name' => 'Europe first international priority'),
    array('key' => "fedex_1_day_freight", 'name' => 'Fedex 1 day freight'),
    array('key' => "fedex_2_day", 'name' => 'Fedex 2 day'),
    array('key' => "fedex_2_day_am", 'name' => 'Fedex 2 day am'),
    array('key' => "fedex_2_day_freight", 'name' => 'Fedex 2 day freight'),
    array('key' => "fedex_3_day_freight", 'name' => 'Fedex 3 day freight'),
    array('key' => "fedex_distance_deferred", 'name' => 'Fedex distance deferred'),
    array('key' => "fedex_express_saver", 'name' => 'Fedex express saver'),
    array('key' => "fedex_first_freight", 'name' => 'Fedex first freight'),
    array('key' => "fedex_freight_economy", 'name' => 'Fedex freight economy'),
    array('key' => "fedex_freight_priority", 'name' => 'Fedex freight priority'),
    array('key' => "fedex_ground", 'name' => 'Fedex ground'),
    array('key' => "fedex_next_day_afternoon", 'name' => 'Fedex next day afternoon'),
    array('key' => "fedex_next_day_early_morning", 'name' => 'Fedex next day early morning'),
    array('key' => "fedex_next_day_end_of_day", 'name' => 'Fedex next day end of day'),
    array('key' => "fedex_next_day_freight", 'name' => 'Fedex next day freight'),
    array('key' => "fedex_next_day_mid_morning", 'name' => 'Fedex next day mid morning'),
    array('key' => "first_overnight", 'name' => 'First overnight'),
    array('key' => "ground_home_delivery", 'name' => 'Ground home delivery'),
    array('key' => "international_economy", 'name' => 'International economy'),
    array('key' => "international_economy_freight", 'name' => 'International economy freight'),
    array('key' => "international_first", 'name' => 'International first'),
    array('key' => "international_priority", 'name' => 'International priority'),
    array('key' => "international_priority_freight", 'name' => 'International priority freight'),
    array('key' => "priority_overnight", 'name' => 'Priority priority_overnight'),
    array('key' => "same_day", 'name' => 'Same day'),
    array('key' => "same_day_city", 'name' => 'Same day city'),
    array('key' => "smart_post", 'name' => 'Smart post'),
    array('key' => "standard_overnight", 'name' => 'Standard overnight'),
);

$GLOBALS['fedexDropoffTypes'] = array(
    'business_service_center' => 'Business service center',
    'drop_box' => 'Drop box',
    'regular_pickup' => 'Regular pickup',
    'request_courier' => 'Request courier',
    'station' => 'Station',
);

$GLOBALS['fedexPackagingTypes'] = array(
    'fedex_10kg_box' => 'Fedex 10kg box',
    'fedex_25kg_box' => 'Fedex 25kg box',
    'fedex_box' => 'Fedex box',
    'fedex_envelope' => 'Fedex envelope',
    'fedex_extra_large_box' => 'Fedex extra large box',
    'fedex_large_box' => 'Fedex large box',
    'fedex_medium_box' => 'Fedex medium box',
    'fedex_pak' => 'Fedex pak',
    'fedex_small_box' => 'Fedex small box',
    'fedex_tube' => 'Fedex tube',
    'your_packaging' => 'Your packaging',
);

$GLOBALS['fedexRateTypes'] = array(
    'LIST' => 'List rate',
    'PREFERRED' => 'Account rate',
    'NONE' => 'None',
);

$GLOBALS['fedexLengthTypes'] = array(
    'cm' => 'Centimeters',
    'in' => 'Inches',
);
