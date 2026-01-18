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

$GLOBALS['reefless']->loadClass('Json');

$shc_steps = array(
    'cart' => array(
        'name' => $GLOBALS['lang']['shc_step_cart'],
        'caption' => true,
        'key' => 'cart',
        'auction' => false,
    ),
    'auth' => array(
        'name' => $GLOBALS['lang']['shc_step_auth'],
        'caption' => true,
        'path' => 'auth',
        'key' => 'auth',
        'auction' => false,
    ),
    'shipping' => array(
        'name' => $GLOBALS['lang']['shc_step_shipping'],
        'caption' => true,
        'path' => 'shipping',
        'key' => 'shipping',
        'auction' => true,
    ),
    'checkout' => array(
        'name' => $GLOBALS['lang']['checkout'],
        'caption' => true,
        'path' => 'checkout',
        'key' => 'checkout',
        'auction' => true,
    ),
    'done' => array(
        'name' => $GLOBALS['lang']['done'],
        'path' => 'done',
        'key' => 'done',
        'auction' => true,
    ),
);

$countries = '[
    {"Country_code":"AF","Country_name":"Afghanistan"},{"Country_code":"AX","Country_name":"Aland Islands"},{"Country_code":"AL","Country_name":"Albania"},
    {"Country_code":"DZ","Country_name":"Algeria"},{"Country_code":"AS","Country_name":"American Samoa"},{"Country_code":"AD","Country_name":"Andorra"},
    {"Country_code":"AO","Country_name":"Angola"},{"Country_code":"AI","Country_name":"Anguilla"},{"Country_code":"AQ","Country_name":"Antarctica"},
    {"Country_code":"AG","Country_name":"Antigua and Barbuda"},{"Country_code":"AR","Country_name":"Argentina"},{"Country_code":"AM","Country_name":"Armenia"},
    {"Country_code":"AW","Country_name":"Aruba"},{"Country_code":"AU","Country_name":"Australia"},{"Country_code":"AT","Country_name":"Austria"},
    {"Country_code":"AZ","Country_name":"Azerbaijan"},{"Country_code":"BS","Country_name":"Bahamas"},{"Country_code":"BH","Country_name":"Bahrain"},
    {"Country_code":"BD","Country_name":"Bangladesh"},{"Country_code":"BB","Country_name":"Barbados"},{"Country_code":"BY","Country_name":"Belarus"},
    {"Country_code":"BE","Country_name":"Belgium"},{"Country_code":"BZ","Country_name":"Belize"},{"Country_code":"BJ","Country_name":"Benin"},
    {"Country_code":"BM","Country_name":"Bermuda"},{"Country_code":"BT","Country_name":"Bhutan"},{"Country_code":"BO","Country_name":"Bolivia"},
    {"Country_code":"BA","Country_name":"Bosnia and Herzegovina"},{"Country_code":"BW","Country_name":"Botswana"},
    {"Country_code":"BV","Country_name":"Bouvet Island"},{"Country_code":"BR","Country_name":"Brazil"},{"Country_code":"IO","Country_name":"British Indian Ocean Territory"},
    {"Country_code":"BN","Country_name":"Brunei Darussalam"},{"Country_code":"BG","Country_name":"Bulgaria"},{"Country_code":"BF","Country_name":"Burkina Faso"},
    {"Country_code":"BI","Country_name":"Burundi"},{"Country_code":"KH","Country_name":"Cambodia"},{"Country_code":"CM","Country_name":"Cameroon"},
    {"Country_code":"CA","Country_name":"Canada"},{"Country_code":"CV","Country_name":"Cape Verde"},{"Country_code":"KY","Country_name":"Cayman Islands"},
    {"Country_code":"CF","Country_name":"Central African Republic"},{"Country_code":"TD","Country_name":"Chad"},{"Country_code":"CL","Country_name":"Chile"},
    {"Country_code":"CN","Country_name":"China"},{"Country_code":"CX","Country_name":"Christmas Island"},{"Country_code":"CC","Country_name":"Cocos (Keeling) Islands"},
    {"Country_code":"CO","Country_name":"Colombia"},{"Country_code":"KM","Country_name":"Comoros"},{"Country_code":"CG","Country_name":"Congo"},
    {"Country_code":"CD","Country_name":"Congo, The Democratic Republic of the"},{"Country_code":"CK","Country_name":"Cook Islands"},
    {"Country_code":"CR","Country_name":"Costa Rica"},{"Country_code":"CI","Country_name":"Cote D\'Ivoire"},{"Country_code":"HR","Country_name":"Croatia"},
    {"Country_code":"CU","Country_name":"Cuba"},{"Country_code":"CY","Country_name":"Cyprus"},{"Country_code":"CZ","Country_name":"Czech Republic"},
    {"Country_code":"DK","Country_name":"Denmark"},{"Country_code":"DJ","Country_name":"Djibouti"},{"Country_code":"DM","Country_name":"Dominica"},
    {"Country_code":"DO","Country_name":"Dominican Republic"},{"Country_code":"TL","Country_name":"East Timor"},{"Country_code":"EC","Country_name":"Ecuador"},
    {"Country_code":"EG","Country_name":"Egypt"},{"Country_code":"SV","Country_name":"El Salvador"},{"Country_code":"GQ","Country_name":"Equatorial Guinea"},
    {"Country_code":"ER","Country_name":"Eritrea"},{"Country_code":"EE","Country_name":"Estonia"},{"Country_code":"ET","Country_name":"Ethiopia"},
    {"Country_code":"FK","Country_name":"Falkland Islands (Malvinas)"},{"Country_code":"FO","Country_name":"Faroe Islands"},{"Country_code":"FJ","Country_name":"Fiji"},
    {"Country_code":"FI","Country_name":"Finland"},{"Country_code":"FR","Country_name":"France"},{"Country_code":"GF","Country_name":"French Guiana"},
    {"Country_code":"PF","Country_name":"French Polynesia"},{"Country_code":"TF","Country_name":"French Southern Territories"},{"Country_code":"GA","Country_name":"Gabon"},
    {"Country_code":"GM","Country_name":"Gambia"},{"Country_code":"GE","Country_name":"Georgia"},{"Country_code":"DE","Country_name":"Germany"},
    {"Country_code":"GH","Country_name":"Ghana"},{"Country_code":"GI","Country_name":"Gibraltar"},{"Country_code":"GR","Country_name":"Greece"},
    {"Country_code":"GL","Country_name":"Greenland"},{"Country_code":"GD","Country_name":"Grenada"},{"Country_code":"GP","Country_name":"Guadeloupe"},
    {"Country_code":"GU","Country_name":"Guam"},{"Country_code":"GT","Country_name":"Guatemala"},{"Country_code":"GG","Country_name":"Guernsey"},
    {"Country_code":"GN","Country_name":"Guinea"},{"Country_code":"GW","Country_name":"Guinea-Bissau"},{"Country_code":"GY","Country_name":"Guyana"},
    {"Country_code":"HT","Country_name":"Haiti"},{"Country_code":"HM","Country_name":"Heard Island and McDonald Islands"},
    {"Country_code":"VA","Country_name":"Holy See (Vatican City State)"},{"Country_code":"HN","Country_name":"Honduras"},{"Country_code":"HK","Country_name":"Hong Kong"},
    {"Country_code":"HU","Country_name":"Hungary"},{"Country_code":"IS","Country_name":"Iceland"},{"Country_code":"IN","Country_name":"India"},
    {"Country_code":"ID","Country_name":"Indonesia"},{"Country_code":"IR","Country_name":"Iran, Islamic Republic of"},{"Country_code":"IQ","Country_name":"Iraq"},
    {"Country_code":"IE","Country_name":"Ireland"},{"Country_code":"IM","Country_name":"Isle of Man"},{"Country_code":"IL","Country_name":"Israel"},
    {"Country_code":"IT","Country_name":"Italy"},{"Country_code":"JM","Country_name":"Jamaica"},{"Country_code":"JP","Country_name":"Japan"},
    {"Country_code":"JE","Country_name":"Jersey"},{"Country_code":"JO","Country_name":"Jordan"},{"Country_code":"KZ","Country_name":"Kazakhstan"},
    {"Country_code":"KE","Country_name":"Kenya"},{"Country_code":"KI","Country_name":"Kiribati"},{"Country_code":"KP","Country_name":"Korea, Democratic People\'s Republic of"},
    {"Country_code":"KR","Country_name":"Korea, Republic of"},{"Country_code":"KW","Country_name":"Kuwait"},{"Country_code":"KG","Country_name":"Kyrgyzstan"},
    {"Country_code":"LA","Country_name":"Lao People\'s Democratic Republic"},{"Country_code":"LV","Country_name":"Latvia"},{"Country_code":"LB","Country_name":"Lebanon"},
    {"Country_code":"LS","Country_name":"Lesotho"},{"Country_code":"LR","Country_name":"Liberia"},{"Country_code":"LY","Country_name":"Libyan Arab Jamahiriya"},
    {"Country_code":"LI","Country_name":"Liechtenstein"},{"Country_code":"LT","Country_name":"Lithuania"},{"Country_code":"LU","Country_name":"Luxembourg"},
    {"Country_code":"MO","Country_name":"Macau"},{"Country_code":"MK","Country_name":"Macedonia"},{"Country_code":"MG","Country_name":"Madagascar"},
    {"Country_code":"MW","Country_name":"Malawi"},{"Country_code":"MY","Country_name":"Malaysia"},{"Country_code":"MV","Country_name":"Maldives"},
    {"Country_code":"ML","Country_name":"Mali"},{"Country_code":"MT","Country_name":"Malta"},{"Country_code":"MH","Country_name":"Marshall Islands"},
    {"Country_code":"MQ","Country_name":"Martinique"},{"Country_code":"MR","Country_name":"Mauritania"},{"Country_code":"MU","Country_name":"Mauritius"},
    {"Country_code":"YT","Country_name":"Mayotte"},{"Country_code":"MX","Country_name":"Mexico"},{"Country_code":"FM","Country_name":"Micronesia, Federated States of"},
    {"Country_code":"MD","Country_name":"Moldova, Republic of"},{"Country_code":"MC","Country_name":"Monaco"},{"Country_code":"MN","Country_name":"Mongolia"},
    {"Country_code":"ME","Country_name":"Montenegro"},{"Country_code":"MS","Country_name":"Montserrat"},{"Country_code":"MA","Country_name":"Morocco"},
    {"Country_code":"MZ","Country_name":"Mozambique"},{"Country_code":"MM","Country_name":"Myanmar"},{"Country_code":"NA","Country_name":"Namibia"},
    {"Country_code":"NR","Country_name":"Nauru"},{"Country_code":"NP","Country_name":"Nepal"},{"Country_code":"NL","Country_name":"Netherlands"},
    {"Country_code":"AN","Country_name":"Netherlands Antilles"},{"Country_code":"NC","Country_name":"New Caledonia"},{"Country_code":"NZ","Country_name":"New Zealand"},
    {"Country_code":"NI","Country_name":"Nicaragua"},{"Country_code":"NE","Country_name":"Niger"},{"Country_code":"NG","Country_name":"Nigeria"},
    {"Country_code":"NU","Country_name":"Niue"},{"Country_code":"NF","Country_name":"Norfolk Island"},{"Country_code":"MP","Country_name":"Northern Mariana Islands"},
    {"Country_code":"NO","Country_name":"Norway"},{"Country_code":"OM","Country_name":"Oman"},{"Country_code":"PK","Country_name":"Pakistan"},
    {"Country_code":"PW","Country_name":"Palau"},{"Country_code":"PS","Country_name":"Palestinian Territory"},{"Country_code":"PA","Country_name":"Panama"},
    {"Country_code":"PG","Country_name":"Papua New Guinea"},{"Country_code":"PY","Country_name":"Paraguay"},{"Country_code":"PE","Country_name":"Peru"},
    {"Country_code":"PH","Country_name":"Philippines"},{"Country_code":"PN","Country_name":"Pitcairn"},{"Country_code":"PL","Country_name":"Poland"},
    {"Country_code":"PT","Country_name":"Portugal"},{"Country_code":"PR","Country_name":"Puerto Rico"},{"Country_code":"QA","Country_name":"Qatar"},
    {"Country_code":"RE","Country_name":"Reunion"},{"Country_code":"RO","Country_name":"Romania"},{"Country_code":"RU","Country_name":"Russian Federation"},
    {"Country_code":"RW","Country_name":"Rwanda"},{"Country_code":"SH","Country_name":"Saint Helena"},{"Country_code":"KN","Country_name":"Saint Kitts and Nevis"},
    {"Country_code":"LC","Country_name":"Saint Lucia"},{"Country_code":"PM","Country_name":"Saint Pierre and Miquelon"},
    {"Country_code":"VC","Country_name":"Saint Vincent and the Grenadines"},{"Country_code":"WS","Country_name":"Samoa"},{"Country_code":"SM","Country_name":"San Marino"},
    {"Country_code":"ST","Country_name":"Sao Tome and Principe"},{"Country_code":"SA","Country_name":"Saudi Arabia"},{"Country_code":"SN","Country_name":"Senegal"},
    {"Country_code":"RS","Country_name":"Serbia"},{"Country_code":"SC","Country_name":"Seychelles"},{"Country_code":"SL","Country_name":"Sierra Leone"},
    {"Country_code":"SG","Country_name":"Singapore"},{"Country_code":"SK","Country_name":"Slovakia"},{"Country_code":"SI","Country_name":"Slovenia"},
    {"Country_code":"SB","Country_name":"Solomon Islands"},{"Country_code":"SO","Country_name":"Somalia"},{"Country_code":"ZA","Country_name":"South Africa"},
    {"Country_code":"GS","Country_name":"South Georgia and the South Sandwich Islands"},{"Country_code":"ES","Country_name":"Spain"},
    {"Country_code":"LK","Country_name":"Sri Lanka"},{"Country_code":"SD","Country_name":"Sudan"},{"Country_code":"SR","Country_name":"Suriname"},
    {"Country_code":"SJ","Country_name":"Svalbard and Jan Mayen"},{"Country_code":"SZ","Country_name":"Swaziland"},{"Country_code":"SE","Country_name":"Sweden"},
    {"Country_code":"CH","Country_name":"Switzerland"},{"Country_code":"SY","Country_name":"Syrian Arab Republic"},
    {"Country_code":"TW","Country_name":"Taiwan (Province of China)"},{"Country_code":"TJ","Country_name":"Tajikistan"},{"Country_code":"TZ","Country_name":"Tanzania, United Republic of"},
    {"Country_code":"TH","Country_name":"Thailand"},{"Country_code":"TG","Country_name":"Togo"},{"Country_code":"TK","Country_name":"Tokelau"},{"Country_code":"TO","Country_name":"Tonga"},
    {"Country_code":"TT","Country_name":"Trinidad and Tobago"},{"Country_code":"TN","Country_name":"Tunisia"},{"Country_code":"TR","Country_name":"Turkey"},
    {"Country_code":"TM","Country_name":"Turkmenistan"},{"Country_code":"TC","Country_name":"Turks and Caicos Islands"},{"Country_code":"TV","Country_name":"Tuvalu"},
    {"Country_code":"UG","Country_name":"Uganda"},{"Country_code":"UA","Country_name":"Ukraine"},{"Country_code":"AE","Country_name":"United Arab Emirates"},
    {"Country_code":"GB","Country_name":"United Kingdom"},{"Country_code":"US","Country_name":"United States"},{"Country_code":"UM","Country_name":"United States Minor Outlying Islands"},
    {"Country_code":"UY","Country_name":"Uruguay"},{"Country_code":"UZ","Country_name":"Uzbekistan"},{"Country_code":"VU","Country_name":"Vanuatu"},
    {"Country_code":"VE","Country_name":"Venezuela"},{"Country_code":"VN","Country_name":"Vietnam"},{"Country_code":"VG","Country_name":"Virgin Islands, British"},
    {"Country_code":"VI","Country_name":"Virgin Islands, U.S."},{"Country_code":"WF","Country_name":"Wallis and Futuna"},{"Country_code":"EH","Country_name":"Western Sahara"},
    {"Country_code":"YE","Country_name":"Yemen"},{"Country_code":"ZM","Country_name":"Zambia"},{"Country_code":"ZW","Country_name":"Zimbabwe"}
]';

$countries = preg_replace('/(\n|\t|\r)?/', '', $countries);

$countries = $GLOBALS['rlJson']->decode($countries);

$states = array();
$states_tmp = '[
    {"State_code":"AL","State_name":"Alabama"},{"State_code":"AK","State_name":"Alaska"},{"State_code":"AZ","State_name":"Arizona"},
    {"State_code":"AR","State_name":"Arkansas"},{"State_code":"CA","State_name":"California"},{"State_code":"CO","State_name":"Colorado"},
    {"State_code":"CT","State_name":"Connecticut"},{"State_code":"DE","State_name":"Delaware"},{"State_code":"FL","State_name":"Florida"},
    {"State_code":"GA","State_name":"Georgia"},{"State_code":"HI","State_name":"Hawaii"},{"State_code":"ID","State_name":"Idaho"},
    {"State_code":"IL","State_name":"Illinois"},{"State_code":"IN","State_name":"Indiana"},{"State_code":"IA","State_name":"Iowa"},
    {"State_code":"KS","State_name":"Kansas"},{"State_code":"KY","State_name":"Kentucky"},{"State_code":"LA","State_name":"Louisiana"},
    {"State_code":"ME","State_name":"Maine"},{"State_code":"MD","State_name":"Maryland"},{"State_code":"MA","State_name":"Massachusetts"},
    {"State_code":"MI","State_name":"Michigan"},{"State_code":"MN","State_name":"Minnesota"},{"State_code":"MS","State_name":"Mississippi"},
    {"State_code":"MO","State_name":"Missouri"},{"State_code":"MT","State_name":"Montana"},{"State_code":"NE","State_name":"Nebraska"},
    {"State_code":"NV","State_name":"Nevada"},{"State_code":"NH","State_name":"New Hampshire"},{"State_code":"NJ","State_name":"New Jersey"},
    {"State_code":"NM","State_name":"New Mexico"},{"State_code":"NY","State_name":"New York"},{"State_code":"NC","State_name":"North Carolina"},
    {"State_code":"ND","State_name":"North Dakota"},{"State_code":"OH","State_name":"Ohio"},{"State_code":"OK","State_name":"Oklahoma"},
    {"State_code":"OR","State_name":"Oregon"},{"State_code":"PA","State_name":"Pennsylvania"},{"State_code":"RI","State_name":"Rhode Island"},
    {"State_code":"SC","State_name":"South Carolina"},{"State_code":"SD","State_name":"South Dakota"},{"State_code":"TN","State_name":"Tennessee"},
    {"State_code":"TS","State_name":"Texas"},{"State_code":"UT","State_name":"Utah"},{"State_code":"VT","State_name":"Vermont"},
    {"State_code":"VA","State_name":"Virginia"},{"State_code":"WA","State_name":"Washington"},{"State_code":"WV","State_name":"West Virginia"},
    {"State_code":"WI","State_name":"Wisconsin"},{"State_code":"WY","State_name":"Wyoming"},{"State_code":"DC","State_name":"District of Columbia"},
    {"State_code":"AS","State_name":"American Samoa"},{"State_code":"GU","State_name":"Guam"},{"State_code":"MP","State_name":"Northern Mariana Islands"},
    {"State_code":"PR","State_name":"Puerto Rico"}
]';

$states_tmp = preg_replace('/(\n|\t|\r)?/', '', $states_tmp);
$states_tmp = $GLOBALS['rlJson']->decode($states_tmp);

foreach ($states_tmp as $k => $v) {
    $states[$k] = array(
        'code' => $v->State_code,
        'name' => $v->State_name,
    );
}

$package_types = array(
    'letter' => $GLOBALS['lang']['shc_package_type_letter'],
    'large_envelope' => $GLOBALS['lang']['shc_package_type_large_envelope'],
    'package' => $GLOBALS['lang']['shc_package_type_package'],
    'large_package' => $GLOBALS['lang']['shc_package_type_large_package'],
    'irregular_package' => $GLOBALS['lang']['shc_package_type_irregular_package'],
);

$time_n = array(2, 3, 4, 5, 10, 15, 20, 30);
$handling_time = array(
    '0' => $GLOBALS['lang']['shc_handling_time_0'],
    '1' => $GLOBALS['lang']['shc_handling_time_1'],
);

foreach ($time_n as $n) {
    $handling_time[$n] = str_replace('{number}', $n, $GLOBALS['lang']['shc_handling_time_n']);
}

$google_autocomplete = array(
    array('key' => 'country', 'name' => $GLOBALS['lang']['billing_country']),
    array('key' => 'administrative_area_level_1', 'name' => $GLOBALS['lang']['billing_state']),
    array('key' => 'locality', 'name' => $GLOBALS['lang']['billing_city']),
    array('key' => 'postal_code', 'name' => $GLOBALS['lang']['billing_zip']),
    array('key' => 'route', 'name' => $GLOBALS['lang']['billing_address']),
);

$upsPickupMethods = array(
    '01' => $GLOBALS['lang']['shc_ups_pickup_regular_daily_pickup'],
    '03' => $GLOBALS['lang']['shc_ups_pickup_customer_counter'],
    '06' => $GLOBALS['lang']['shc_ups_pickup_one_time_pickup'],
    '07' => $GLOBALS['lang']['shc_ups_pickup_on_call_air'],
    '19' => $GLOBALS['lang']['shc_ups_pickup_letter_center'],
    '20' => $GLOBALS['lang']['shc_ups_pickup_air_service_center'],
    '11' => $GLOBALS['lang']['shc_ups_pickup_suggested_retail_rates'],
);

$upsPackagingItems = array(
    '00' => $GLOBALS['lang']['shc_ups_packaging_unknown'],
    '01' => $GLOBALS['lang']['shc_ups_packaging_letter'],
    '02' => $GLOBALS['lang']['shc_ups_packaging_package'],
    '03' => $GLOBALS['lang']['shc_ups_packaging_tube'],
    '04' => $GLOBALS['lang']['shc_ups_packaging_pak'],
    '21' => $GLOBALS['lang']['shc_ups_packaging_express_box'],
    '24' => $GLOBALS['lang']['shc_ups_packaging_25kg_box'],
    '25' => $GLOBALS['lang']['shc_ups_packaging_10kg_box'],
    '30' => $GLOBALS['lang']['shc_ups_packaging_pallet'],
    '2a' => $GLOBALS['lang']['shc_ups_packaging_small_express_box'],
    '2b' => $GLOBALS['lang']['shc_ups_packaging_medium_express_box'],
    '2c' => $GLOBALS['lang']['shc_ups_packaging_large_express_box'],
);

$upsOrigins = array(
    'US' => $GLOBALS['lang']['shc_ups_origin_us'],
    'CA' => $GLOBALS['lang']['shc_ups_origin_ca'],
    'EU' => $GLOBALS['lang']['shc_ups_origin_eu'],
    'PR' => $GLOBALS['lang']['shc_ups_origin_pr'],
    'MX' => $GLOBALS['lang']['shc_ups_origin_mx'],
    'other' => $GLOBALS['lang']['ups_origin_other'],
);

$upsShippingServices = array(
    /* US,CA,PR */
    '01' => array('origin' => "US,CA,PR", 'code' => "01", 'name' => $GLOBALS['lang']['shc_ups_service_ups_next_day_air']),
    '02' => array('origin' => "US,CA,PR", 'code' => "02", 'name' => $GLOBALS['lang']['shc_ups_service_ups_2nd_day_air']),
    '03' => array('origin' => "US,PR", 'code' => "03", 'name' => $GLOBALS['lang']['shc_ups_service_ups_ground']),
    '12' => array('origin' => "US,CA", 'code' => "12", 'name' => $GLOBALS['lang']['shc_ups_service_ups_3_day_select']),
    '13' => array('origin' => "US,CA", 'code' => "13", 'name' => $GLOBALS['lang']['shc_ups_service_ups_next_day_air_saver']),
    '14' => array('origin' => "US,CA,PR", 'code' => "14", 'name' => $GLOBALS['lang']['shc_ups_service_ups_express_early_am']),
    '59' => array('origin' => "US", 'code' => "59", 'name' => $GLOBALS['lang']['shc_ups_service_ups_2nd_day_air_am']),

    /* ALL */
    '07' => array('origin' => "US,CA,PR,MX,EU,other", 'code' => "07", 'name' => $GLOBALS['lang']['shc_ups_service_ups_express']),
    '08' => array('origin' => "US,CA,PR,MX,EU,other", 'code' => "08", 'name' => $GLOBALS['lang']['shc_ups_service_ups_expedited']),
    '11' => array('origin' => "US,CA,EU,other", 'code' => "11", 'name' => $GLOBALS['lang']['shc_ups_service_ups_standard']),
    '54' => array('origin' => "US,CA,PR,MX,EU,other", 'code' => "54", 'name' => $GLOBALS['lang']['shc_ups_service_ups_worldwide_express_plus']),
    '65' => array('origin' => "US,CA,PR,MX,EU,other", 'code' => "65", 'name' => $GLOBALS['lang']['shc_ups_service_ups_saver']),

    /* EU */
    '82' => array('origin' => "EU", 'code' => "82", 'name' => $GLOBALS['lang']['shc_ups_service_ups_today_standard']),
    '83' => array('origin' => "EU", 'code' => "83", 'name' => $GLOBALS['lang']['shc_ups_service_ups_today_dedicated_courier']),
    '84' => array('origin' => "EU", 'code' => "84", 'name' => $GLOBALS['lang']['shc_ups_service_ups_today_intercity']),
    '85' => array('origin' => "EU", 'code' => "85", 'name' => $GLOBALS['lang']['shc_ups_service_ups_today_express']),
    '86' => array('origin' => "EU", 'code' => "86", 'name' => $GLOBALS['lang']['shc_ups_service_ups_today_express_saver']),
);

$upsQuoteTypes = array(
    array('key' => 'residential', 'name' => $GLOBALS['lang']['shc_ups_quote_type_residential']),
    array('key' => 'commercial', 'name' => $GLOBALS['lang']['shc_ups_quote_type_commercial']),
);

$upsClassifications = array('01', '03', '04');

$fedexServices = array(
    array('key' => "europe_first_international_priority", 'name' => $GLOBALS['lang']['shc_europe_first_international_priority']),
    array('key' => "fedex_1_day_freight", 'name' => $GLOBALS['lang']['shc_fedex_1_day_freight']),
    array('key' => "fedex_2_day", 'name' => $GLOBALS['lang']['shc_fedex_2_day']),
    array('key' => "fedex_2_day_am", 'name' => $GLOBALS['lang']['shc_fedex_2_day_am']),
    array('key' => "fedex_2_day_freight", 'name' => $GLOBALS['lang']['shc_fedex_2_day_freight']),
    array('key' => "fedex_3_day_freight", 'name' => $GLOBALS['lang']['shc_fedex_3_day_freight']),
    array('key' => "fedex_distance_deferred", 'name' => $GLOBALS['lang']['shc_fedex_distance_deferred']),
    array('key' => "fedex_express_saver", 'name' => $GLOBALS['lang']['shc_fedex_express_saver']),
    array('key' => "fedex_first_freight", 'name' => $GLOBALS['lang']['shc_fedex_first_freight']),
    array('key' => "fedex_freight_economy", 'name' => $GLOBALS['lang']['shc_fedex_freight_economy']),
    array('key' => "fedex_freight_priority", 'name' => $GLOBALS['lang']['shc_fedex_freight_priority']),
    array('key' => "fedex_ground", 'name' => $GLOBALS['lang']['shc_fedex_ground']),
    array('key' => "fedex_next_day_afternoon", 'name' => $GLOBALS['lang']['shc_fedex_next_day_afternoon']),
    array('key' => "fedex_next_day_early_morning", 'name' => $GLOBALS['lang']['shc_fedex_next_day_early_morning']),
    array('key' => "fedex_next_day_end_of_day", 'name' => $GLOBALS['lang']['shc_fedex_next_day_end_of_day']),
    array('key' => "fedex_next_day_freight", 'name' => $GLOBALS['lang']['shc_fedex_next_day_freight']),
    array('key' => "fedex_next_day_mid_morning", 'name' => $GLOBALS['lang']['shc_fedex_next_day_mid_morning']),
    array('key' => "first_overnight", 'name' => $GLOBALS['lang']['shc_first_overnight']),
    array('key' => "ground_home_delivery", 'name' => $GLOBALS['lang']['shc_ground_home_delivery']),
    array('key' => "international_economy", 'name' => $GLOBALS['lang']['shc_international_economy']),
    array('key' => "international_economy_freight", 'name' => $GLOBALS['lang']['shc_international_economy_freight']),
    array('key' => "international_first", 'name' => $GLOBALS['lang']['shc_international_first']),
    array('key' => "international_priority", 'name' => $GLOBALS['lang']['shc_international_priority']),
    array('key' => "international_priority_freight", 'name' => $GLOBALS['lang']['shc_international_priority_freight']),
    array('key' => "priority_overnight", 'name' => $GLOBALS['lang']['shc_priority_overnight']),
    array('key' => "same_day", 'name' => $GLOBALS['lang']['shc_same_day']),
    array('key' => "same_day_city", 'name' => $GLOBALS['lang']['shc_same_day_city']),
    array('key' => "smart_post", 'name' => $GLOBALS['lang']['shc_smart_post']),
    array('key' => "standard_overnight", 'name' => $GLOBALS['lang']['shc_standard_overnight']),
);

$fedexDropoffTypes = array(
    'business_service_center' => $GLOBALS['lang']['shc_business_service_center'],
    'drop_box' => $GLOBALS['lang']['shc_drop_box'],
    'regular_pickup' => $GLOBALS['lang']['shc_regular_pickup'],
    'request_courier' => $GLOBALS['lang']['shc_request_courier'],
    'station' => $GLOBALS['lang']['shc_station'],
);

$fedexPackagingTypes = array(
    'fedex_10kg_box' => $GLOBALS['lang']['shc_fedex_10kg_box'],
    'fedex_25kg_box' => $GLOBALS['lang']['shc_fedex_25kg_box'],
    'fedex_box' => $GLOBALS['lang']['shc_fedex_box'],
    'fedex_envelope' => $GLOBALS['lang']['shc_fedex_envelope'],
    'fedex_extra_large_box' => $GLOBALS['lang']['shc_fedex_extra_large_box'],
    'fedex_large_box' => $GLOBALS['lang']['shc_fedex_large_box'],
    'fedex_medium_box' => $GLOBALS['lang']['shc_fedex_medium_box'],
    'fedex_pak' => $GLOBALS['lang']['shc_fedex_pak'],
    'fedex_small_box' => $GLOBALS['lang']['shc_fedex_small_box'],
    'fedex_tube' => $GLOBALS['lang']['shc_fedex_tube'],
    'your_packaging' => $GLOBALS['lang']['shc_your_packaging'],
);

$shippingStatuses = array(
    array('Key' => 'pending', 'name' => $GLOBALS['lang']['pending']),
    array('Key' => 'processing', 'name' => $GLOBALS['lang']['shc_processing']),
    array('Key' => 'shipped', 'name' => $GLOBALS['lang']['shc_shipped']),
    array('Key' => 'declined', 'name' => $GLOBALS['lang']['shc_declined']),
    array('Key' => 'open', 'name' => $GLOBALS['lang']['shc_open']),
    array('Key' => 'delivered', 'name' => $GLOBALS['lang']['shc_delivered']),
);
