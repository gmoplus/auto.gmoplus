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

$GLOBALS['upsPickupMethods'] = array(
    '01' => 'Daily pickup',
    '03' => 'Customer counter',
    '06' => 'One time pickup',
    '07' => 'On call air pickup',
    '19' => 'Letter center',
    '20' => 'Air service center',
    '11' => 'Suggested retail rates (UPS Store)',
);

$GLOBALS['upsPackagingItems'] = array(
    '00' => 'Unknown',
    '01' => 'UPS letter',
    '02' => 'Package',
    '03' => 'Tube',
    '04' => 'Pak',
    '21' => 'Express box',
    '24' => '25KG box',
    '25' => '10KG box',
    '30' => 'Pallet',
    '2a' => 'Small express box',
    '2b' => 'Medium express box',
    '2c' => 'Large express box',
);

$GLOBALS['upsOrigins'] = array(
    'US' => 'US Origin',
    'CA' => 'Canada Origin',
    'EU' => 'European Union Origin',
    'PR' => 'Puerto Rico Origin',
    'MX' => 'Mexico Origin',
    'other' => 'Other regions',
);

$GLOBALS['upsServices'] = array(
    /* US,CA,PR */
    '01' => array('origin' => "US,CA,PR", 'code' => "01", 'name' => 'UPS Next Day Air'),
    '02' => array('origin' => "US,CA,PR", 'code' => "02", 'name' => 'UPS 2nd Day Air'),
    '03' => array('origin' => "US,PR", 'code' => "03", 'name' => 'UPS Ground'),
    '12' => array('origin' => "US,CA", 'code' => "12", 'name' => 'UPS 3 Day Select'),
    '13' => array('origin' => "US,CA", 'code' => "13", 'name' => 'UPS Next Day Air Saver'),
    '14' => array('origin' => "US,CA,PR", 'code' => "14", 'name' => 'UPS Express Early A.M.'),
    '59' => array('origin' => "US", 'code' => "59", 'name' => 'UPS 2nd Day Air AM'),

    /* ALL */
    '07' => array('origin' => "US,CA,PR,MX,EU,other", 'code' => "07", 'name' => 'UPS Express'),
    '08' => array('origin' => "US,CA,PR,MX,EU,other", 'code' => "08", 'name' => 'UPS Expedited'),
    '11' => array('origin' => "US,CA,EU,other", 'code' => "11", 'name' => 'UPS Standard'),
    '54' => array('origin' => "US,CA,PR,MX,EU,other", 'code' => "54", 'name' => 'UPS Worldwide Express Plus'),
    '65' => array('origin' => "US,CA,PR,MX,EU,other", 'code' => "65", 'name' => 'UPS Saver'),

    /* EU */
    '82' => array('origin' => "EU", 'code' => "82", 'name' => 'UPS Today Standard'),
    '83' => array('origin' => "EU", 'code' => "83", 'name' => 'UPS Today Dedicated Courier'),
    '84' => array('origin' => "EU", 'code' => "84", 'name' => 'UPS Today Intercity'),
    '85' => array('origin' => "EU", 'code' => "85", 'name' => 'UPS Today Express'),
    '86' => array('origin' => "EU", 'code' => "86", 'name' => 'UPS Today Express Saver'),
);

$GLOBALS['upsQuoteTypes'] = array(
    'residential' => 'Residential',
    'commercial' => 'Commercial',
);

$GLOBALS['upsLengthTypes'] = array(
    'cm' => 'Centimeters',
    'in' => 'Inches',
);

$GLOBALS['upsClassifications'] = array('01' => '01', '03' => '03', '04' => '04');
