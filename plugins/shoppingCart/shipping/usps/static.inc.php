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

$uspsContainers = array(
    'VARIABLE' => array(
        'name' => 'Variable',
        'domestic' => 1,
        'internationalal ' => 0,
    ),
    'FLAT RATE ENVELOPE' => array(
        'name' => 'Flat rate envelope',
        'domestic' => 1,
        'international' => 0,
    ),
    'PADDED FLAT RATE ENVELOPE' => array(
        'name' => 'Padded flat rate envelope',
        'domestic' => 1,
        'international' => 0,
    ),
    'LEGAL FLAT RATE ENVELOPE' => array(
        'name' => 'Legal flat rate envelope',
        'domestic' => 1,
        'international' => 0,
    ),
    'SM FLAT RATE ENVELOPE' => array(
        'name' => 'SM flat rate envelope',
        'domestic' => 1,
        'international' => 0,
    ),
    'WINDOW FLAT RATE ENVELOPE' => array(
        'name' => 'Window flat rate envelope',
        'domestic' => 1,
        'international' => 0,
    ),
    'GIFT CARD FLAT RATE ENVELOPE' => array(
        'name' => 'Gift card flat rate envelope',
        'domestic' => 1,
        'international' => 0,
    ),
    'FLAT RATE BOX' => array(
        'name' => 'Flat rate box',
        'domestic' => 1,
        'international' => 0,
    ),
    'SM FLAT RATE BOX' => array(
        'name' => 'SM flat rate box',
        'domestic' => 1,
        'international' => 0,
    ),
    'MD FLAT RATE BOX' => array(
        'name' => 'MD flat rate box',
        'domestic' => 1,
        'international' => 0,
    ),
    'LG FLAT RATE BOX' => array(
        'name' => 'LG flat rate box',
        'domestic' => 1,
        'international' => 0,
    ),
    'REGIONALRATEBOXA' => array(
        'name' => 'Regionalrateboxa',
        'domestic' => 1,
        'international' => 0,
    ),
    'REGIONALRATEBOXB' => array(
        'name' => 'Regionalrateboxb',
        'domestic' => 1,
        'international' => 0,
    ),
    'REGIONALRATEBOXC' => array(
        'name' => 'Regionalrateboxc',
        'domestic' => 1,
        'international' => 0,
    ),
    'RECTANGULAR' => array(
        'name' => 'Rectangular',
        'domestic' => 1,
        'international' => 1,
    ),
    'NONRECTANGULAR' => array(
        'name' => 'Non Rectangular',
        'domestic' => 1,
        'international' => 1,
    ),
);

$uspsServicesDomestic = array(
    array('key' => "ALL", 'name' => 'All'),
    array('key' => "FIRST CLASS", 'name' => 'First class'),
    array('key' => "FIRST CLASS COMMERCIAL", 'name' => 'First class commercial'),
    array('key' => "FIRST CLASS HFP COMMERCIAL", 'name' => 'First class HFP commercial'),
    array('key' => "PRIORITY", 'name' => 'Priority'),
    array('key' => "PRIORITY COMMERCIAL", 'name' => 'Priority commercial'),
    array('key' => "PRIORITY CPP", 'name' => 'Priority CPP'),
    array('key' => "PRIORITY HFP COMMERCIAL", 'name' => 'Priority HFP commercial'),
    array('key' => "PRIORITY HFP CPP", 'name' => 'Priority HFP CPP'),
    array('key' => "PRIORITY MAIL EXPRESS", 'name' => 'Priority mail express'),
    array('key' => "PRIORITY MAIL EXPRESS COMMERCIAL", 'name' => 'Priority mail express commercial'),
    array('key' => "PRIORITY MAIL EXPRESS CPP", 'name' => 'Priority mail express CPP'),
    array('key' => "PRIORITY MAIL EXPRESS SH", 'name' => 'Priority mail express SH'),
    array('key' => "PRIORITY MAIL EXPRESS SH COMMERCIAL", 'name' => 'Priority mail express SH commercial'),
    array('key' => "PRIORITY MAIL EXPRESS HFP", 'name' => 'Priority mail express HFP'),
    array('key' => "PRIORITY MAIL EXPRESS HFP COMMERCIAL", 'name' => 'Priority mail express HFP commercial'),
    array('key' => "PRIORITY MAIL EXPRESS HFP CPP", 'name' => 'Priority mail express HFP CPP'),
    array('key' => "STANDARD POST", 'name' => 'Standard post'),
    array('key' => "MEDIA", 'name' => 'Media'),
    array('key' => "LIBRARY", 'name' => 'Library'),
    array('key' => "ONLINE", 'name' => 'Online'),
    array('key' => "PLUS", 'name' => 'Plus'),
);

$uspsServicesInternational = array(
    'Express Mail International',
    'Priority Mail International',
    'Global Express Guaranteed (Document and Non-document)',
    'Global Express Guaranteed Document used',
    'Global Express Guaranteed Non-Document Rectangular shape',
    'Global Express Guaranteed Non-Document Non-Rectangular',
    'Priority Mail Flat Rate Envelope',
    'Priority Mail Flat Rate Box',
    'Express Mail International Flat Rate Envelope',
    'Priority Mail Flat Rate Large Box',
    'Global Express Guaranteed Envelope',
    'First Class Mail International Letters',
    'First Class Mail International Flats',
    'First Class Mail International Parcels',
    'Priority Mail Flat Rate Small Box',
    'Postcards',
);

$uspsMailTypesDomestic = array(
    'Letter',
    'Flat',
    'Parcel',
    'Postcard',
    'Package service',
);

$uspsMailTypesInternational = array(
    'All',
    'Package',
    'Envelope',
    'LargeEnvelope',
    'FlatRate',
);
