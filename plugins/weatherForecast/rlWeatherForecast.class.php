<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.8.0
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: RLWEATHERFORECAST.CLASS.PHP
 *
 *	The software is a commercial product delivered under single, non-exclusive,
 *	non-transferable license for one domain or IP address. Therefore distribution,
 *	sale or transfer of the file in whole or in part without permission of Flynax
 *	respective owners is considered to be illegal and breach of Flynax License End
 *	User Agreement.
 *
 *	You are not allowed to remove this information from the file without permission
 *	of Flynax respective owners.
 *
 *	Flynax Classifieds Software 2022 |  All copyrights reserved.
 *
 *	https://www.flynax.com/
 *
 ******************************************************************************/

class rlWeatherForecast
{
    /**
    * @var weather condition mapping
    */
    private $condition_mapping = array(
        'light_rain' => array(200, 302, 500),
        'rain' => array(201, 312, 501, 520, 521, 531),
        'heavy_rain' => array(202, 313, 314, 321, 502, 503, 504, 522),
        'freezing_rain' => array(511),
        'thunderstorm' => array(210, 211, 212, 221),
        'drizzle' => array(230, 231, 232, 300, 301, 310, 311),
        'light_snow' => array(600),
        'snow' => array(601),
        'heavy_snow' => array(602, 620),
        'sleet' => array(611, 612, 615, 616),
        'snow_flurries' => array(621, 622),
        'mist' => array(701),
        'smoky' => array(711),
        'haze' => array(721),
        'dust' => array(731, 761),
        'sand_storm' => array(751),
        'foggy' => array(741),
        'volcanic_ash' => array(762),
        'squalls' => array(771),
        'tornado' => array(781, 900),
        'clear_sky' => array(850),
        'sunny' => array(800),
        'partly_cloudy' => array(801),
        'mostly_cloudy' => array(802, 803),
        'cloudy' => array(804),
        'storm' => array(901, 960, 961),
        'hurricane' => array(902, 962),
        'cold' => array(903),
        'hot' => array(904),
        'hail' => array(906),
        'calm' => array(951),
        'breeze' => array(952, 953, 954, 955),
        'windy' => array(905, 956, 957),
        'gale' => array(958, 959),
    );

    /**
    * @var adapted weather condition mapping
    */
    public $condition_codes;

    /**
    * class constructor
    *
    */
    public function __construct() {
        // adapt condition mapping for better usage
        foreach ($this->condition_mapping as $cond_key => $codes) {
            foreach($codes as $code) {
                $this->condition_codes[$code] = $cond_key;
            }
        }

        if (!$_SESSION['GEOLocationData']->City) {
            $GLOBALS['config']['weatherForecast_use_geo'] = false;
        }
    }

    /**
     * weather condition handler for Smarty
     *
     * @package SMARTY
     * @since 3.0.0
     *
     */
    public function weatherCondition($params = false) {
        $id = $params['icon'] == '01n' && $params['id'] == 800 ? 850 : $params['id'];

        $key = 'weatherForecast_cond_' . $this->condition_codes[$id];
        return $GLOBALS['lang'][$key];
    }

    /**
     * Visitor temperature unit auto detection
     *
     * @since 3.3.0
     */
    public function detectUnit()
    {
        global $config;

        if (!$config['weatherForecast_units_auto_detect']) {
            return;
        }

        if (!$_SESSION['GEOLocationData'] || !$_SESSION['GEOLocationData']->Country_code) {
            return;
        }

        $visitor_country = strtoupper($_SESSION['GEOLocationData']->Country_code);
        $fahrenheit_countries = ['BS', 'PW', 'BZ', 'KY', 'FM', 'MH', 'US', 'VI', 'GU', 'PR'];

        $config['weatherForecast_units'] = in_array($visitor_country, $fahrenheit_countries)
        ? 'Fahrenheit'
        : 'Celsius';
    }

    /**
     * Convert forecast unites to visitor's preferred unit
     *
     * @since 3.3.0
     */
    public function convertUnit(&$forecast)
    {
        if (!$forecast['forecast']) {
            return;
        }

        foreach ($forecast['forecast'] as &$item) {
            $item['temp'] = $this->formatTemp($item['temp']);
            $item['temp_min'] = $this->formatTemp($item['temp_min']);
            $item['temp_max'] = $this->formatTemp($item['temp_max']);
        }
    }

    /**
     * Format temperature
     *
     * @since 3.3.0
     *
     * @param  int $temp - Temperature
     * @return string    - Formatted temperature
     */
    private function formatTemp($temp)
    {
        global $config;

        if ($config['weatherForecast_units'] == 'Fahrenheit') {
            $temp = $temp * 1.8 + 32;
        }

        $temp = round($temp);

        $temp = $temp > 0 ? '+' . $temp : $temp;
        $sign = $config['weatherForecast_units'] == 'Celsius' ? ' °C' : ' °F';
        $temp .= $sign;

        return $temp;
    }

    /**
     * Prepare listing weather data
     *
     * @since 3.0.0
     * @hook listingDetailsBottom
     */
    public function hookListingDetailsBottom()
    {
        global $config, $rlSmarty, $fields_list, $listing_data;

        if (!$config['weatherForecast_mapping_city']) {
            $wf_error = 'No mapping data for <b>city</b>, please go to <b>Admin Panel >> Basic Settings >> Weather Forecast</b> and setup fields mapping.';
            $rlSmarty->assign_by_ref('wf_error', $wf_error);

            return;
        }

        foreach ($fields_list as $field) {
            if ($field['Map'] && !empty($listing_data[$field['Key']])) {
                switch ($field['Key']) {
                    case $config['weatherForecast_mapping_country']:
                        $key = str_replace($field['Key'] . '_', '', $field['source'][0]);
                        $country = $this->countryIso[$key] ?: $field['value'];
                        break;
                        
                    case $config['weatherForecast_mapping_city']:
                        $city = $field['value'];
                        break;
                }
            }
        }

        // Define location mode
        if ($listing_data['Loc_latitude'] && $listing_data['Loc_longitude']) {
            $location = $listing_data['Loc_latitude'] . ',' . $listing_data['Loc_longitude'];
            $rlSmarty->assign('weatherForecast_listing_coordinates', true);
        } else {
            $location = $city . ',' . $country;
        }

        $rlSmarty->assign_by_ref('weatherForecast_listing_city', $city);
        $rlSmarty->assign_by_ref('weatherForecast_listing_location', $location); // Coordinates or location
    }

    /**
     * Display listng weather forecast template on listing details page
     *
     * @since 3.1.0
     * @hook listingDetailsPreFields
     */
    public function hookListingDetailsPreFields()
    {
        if ($GLOBALS['config']['weatherForecast_listing_module'] 
            && $GLOBALS['listing_type']['Weather_forecast']
        ) {
            $GLOBALS['rlSmarty']->display(RL_ROOT . 'plugins' . RL_DS . 'weatherForecast' . RL_DS . 'weatherForecast.listing.tpl');
        }
    }

    /**
     * Register static data
     * 
     * @hook staticDataRegister
     */
    public function hookStaticDataRegister()
    {
        global $rlStatic;

        $rlStatic->addBoxFooterCSS(RL_PLUGINS_URL . 'weatherForecast/static/style.css', 'weatherForecast_block');
        $rlStatic->addBoxJS(RL_PLUGINS_URL . 'weatherForecast/static/lib.js', 'weatherForecast_block');

        $rlStatic->addFooterCSS(RL_PLUGINS_URL . 'weatherForecast/static/style.css', 'listing_details');
        $rlStatic->addJS(RL_PLUGINS_URL . 'weatherForecast/static/lib.js', 'listing_details');

        $GLOBALS['rlSmarty']->register_function('weatherCondition', array($this, 'weatherCondition'));
    }

    /**
     * Adds js data to the page footer
     */
    public function hookTplFooter()
    {
        global $rlSmarty;

        $rlSmarty->assign_by_ref('condition_codes', $this->condition_codes);
        $rlSmarty->display(RL_ROOT . 'plugins' . RL_DS . 'weatherForecast' . RL_DS . 'js.tpl');
    }

    private $countryIso = array(
        'aland' => 'AX',
        'afghanistan' => 'AF',
        'albania' => 'AL',
        'algeria' => 'DZ',
        'american_samoa' => 'AS',
        'andorra' => 'AD',
        'angola' => 'AO',
        'anguilla' => 'AI',
        'antarctica' => 'AQ',
        'antigua_and_barbuda' => 'AG',
        'argentina' => 'AR',
        'armenia' => 'AM',
        'aruba' => 'AW',
        'australia' => 'AU',
        'austria' => 'AT',
        'azerbaijan' => 'AZ',
        'bahamas' => 'BS',
        'bahrain' => 'BH',
        'bangladesh' => 'BD',
        'barbados' => 'BB',
        'belarus' => 'BY',
        'belgium' => 'BE',
        'belize' => 'BZ',
        'benin' => 'BJ',
        'bermuda' => 'BM',
        'bhutan' => 'BT',
        'bolivia' => 'BO',
        'bosnia_and_herzegovina' => 'BA',
        'botswana' => 'BW',
        'bouvet_island' => 'BV',
        'brazil' => 'BR',
        'british_indian_ocean_territory' => 'IO',
        'british_virgin_islands' => 'VG',
        'brunei' => 'BN',
        'bulgaria' => 'BG',
        'burkina_faso' => 'BF',
        'burundi' => 'BI',
        'cambodia' => 'KH',
        'cameroon' => 'CM',
        'canada' => 'CA',
        'cape_verde' => 'CV',
        'cayman_islands' => 'KY',
        'central_african_republic' => 'CF',
        'chad' => 'TD',
        'chile' => 'CL',
        'china' => 'CN',
        'christmas_island' => 'CX',
        'cocos_keeling_islands' => 'CC',
        'colombia' => 'CO',
        'comoros' => 'KM',
        'republic_of_the_congo' => 'CD',
        'congo' => 'CG',
        'cook_islands' => 'CK',
        'costa_rica' => 'CR',
        'ivory_coast' => 'CI',
        'croatia' => 'HR',
        'cuba' => 'CU',
        'curacao' => 'CW',
        'cyprus' => 'CY',
        'czech_republic' => 'CZ',
        'denmark' => 'DK',
        'djibouti' => 'DJ',
        'dominica' => 'DM',
        'dominican_republic' => 'DO',
        'ecuador' => 'EC',
        'egypt' => 'EG',
        'el_salvador' => 'SV',
        'equatorial_guinea' => 'GQ',
        'eritrea' => 'ER',
        'estonia' => 'EE',
        'ethiopia' => 'ET',
        'falkland_islands' => 'FK',
        'faroe_islands' => 'FO',
        'fiji' => 'FJ',
        'finland' => 'FI',
        'france' => 'FR',
        'french_guiana' => 'GF',
        'french_polynesia' => 'PF',
        'french_southern_territories' => 'TF',
        'gabon' => 'GA',
        'gambia' => 'GM',
        'georgia' => 'GE',
        'germany' => 'DE',
        'ghana' => 'GH',
        'gibraltar' => 'GI',
        'greece' => 'GR',
        'greenland' => 'GL',
        'grenada' => 'GD',
        'guadeloupe' => 'GP',
        'guam' => 'GU',
        'guatemala' => 'GT',
        'guernsey' => 'GG',
        'guinea' => 'GN',
        'guinea_bissau' => 'GW',
        'guyana' => 'GY',
        'haiti' => 'HT',
        'heard_and_mc_donald_islands' => 'HM',
        'honduras' => 'HN',
        'hong_kong' => 'HK',
        'hungary' => 'HU',
        'iceland' => 'IS',
        'india' => 'IN',
        'indonesia' => 'ID',
        'iran' => 'IR',
        'iraq' => 'IQ',
        'ireland' => 'IE',
        'isle_of_man' => 'IM',
        'israel' => 'IL',
        'italy' => 'IT',
        'jamaica' => 'JM',
        'japan' => 'JP',
        'jersey' => 'JE',
        'hashemite_kingdom_of_jordan' => 'JO',
        'kazakhstan' => 'KZ',
        'kenya' => 'KE',
        'kiribati' => 'KI',
        'north_korea' => 'KP',
        'republic_of_korea' => 'KR',
        'kuwait' => 'KW',
        'kyrgyzstan' => 'KG',
        'laos' => 'LA',
        'latvia' => 'LV',
        'lebanon' => 'LB',
        'lesotho' => 'LS',
        'liberia' => 'LR',
        'libya' => 'LY',
        'liechtenstein' => 'LI',
        'republic_of_lithuania' => 'LT',
        'lithuania' => 'LT',
        'luxembourg' => 'LU',
        'macao' => 'MO',
        'macedonia' => 'MK',
        'madagascar' => 'MG',
        'malawi' => 'MW',
        'malaysia' => 'MY',
        'maldives' => 'MV',
        'mali' => 'ML',
        'malta' => 'MT',
        'marshall_islands' => 'MH',
        'saint_martin' => 'MQ',
        'mauritania' => 'MR',
        'mauritius' => 'MU',
        'mayotte' => 'YT',
        'mexico' => 'MX',
        'federated_states_of_micronesia' => 'FM',
        'republic_of_moldova' => 'MD',
        'monaco' => 'MC',
        'mongolia' => 'MN',
        'montenegro' => 'ME',
        'montserrat' => 'MS',
        'morocco' => 'MA',
        'mozambique' => 'MZ',
        'myanmar_burma' => 'MM',
        'namibia' => 'NA',
        'nauru' => 'NR',
        'nepal' => 'NP',
        'netherlands' => 'NL',
        'netherlands_antilles' => 'AN',
        'new_caledonia' => 'NC',
        'new_zealand' => 'NZ',
        'nicaragua' => 'NI',
        'niger' => 'NE',
        'nigeria' => 'NG',
        'niue' => 'NU',
        'norfolk_island' => 'NF',
        'northern_mariana_islands' => 'MP',
        'norway' => 'NO',
        'oman' => 'OM',
        'pakistan' => 'PK',
        'palau' => 'PW',
        'palestine' => 'PS',
        'panama' => 'PA',
        'papua_new_guinea' => 'PG',
        'paraguay' => 'PY',
        'peru' => 'PE',
        'philippines' => 'PH',
        'pitcairn_islands' => 'PN',
        'poland' => 'PL',
        'portugal' => 'PT',
        'puerto_rico' => 'PR',
        'qatar' => 'QA',
        'reunion' => 'RE',
        'romania' => 'RO',
        'russia' => 'RU',
        'rwanda' => 'RW',
        'saint_barthelemy' => 'BL',
        'saint_helena' => 'SH',
        'saint_kitts_and_nevis' => 'KN',
        'saint_lucia' => 'LC',
        'saint_pierre_and_miquelon' => 'PM',
        'saint_vincent_and_the_grenadines' => 'VC',
        'samoa' => 'WS',
        'san_marino' => 'SM',
        'sao_tome_and_principe' => 'ST',
        'saudi_arabia' => 'SA',
        'senegal' => 'SN',
        'serbia' => 'RS',
        'seychelles' => 'SC',
        'sierra_leone' => 'SL',
        'singapore' => 'SG',
        'sint_maarten' => 'SX',
        'slovak_republic' => 'SK',
        'slovenia' => 'SI',
        'solomon_islands' => 'SB',
        'somalia' => 'SO',
        'south_africa' => 'ZA',
        'south_georgia_and_the_south_sandwich_islands' => 'GS',
        'south_sudan' => 'SS',
        'spain' => 'ES',
        'sri_lanka' => 'LK',
        'sudan' => 'SD',
        'suriname' => 'SR',
        'svalbard_and_jan_mayen' => 'SJ',
        'swaziland' => 'SZ',
        'sweden' => 'SE',
        'switzerland' => 'CH',
        'syria' => 'SY',
        'taiwan' => 'TW',
        'tajikistan' => 'TJ',
        'tanzania' => 'TZ',
        'thailand' => 'TH',
        'east_timor' => 'TL',
        'togo' => 'TG',
        'tokelau' => 'TK',
        'tonga' => 'TO',
        'trinidad_and_tobago' => 'TT',
        'tunisia' => 'TN',
        'turkey' => 'TR',
        'turkmenistan' => 'TM',
        'turks_and_caicos_islands' => 'TC',
        'tuvalu' => 'TV',
        'uganda' => 'UG',
        'ukraine' => 'UA',
        'united_arab_emirates' => 'AE',
        'united_kingdom' => 'GB',
        'united_states' => 'US',
        'u_s_minor_outlying_islands' => 'UM',
        'uruguay' => 'UY',
        'uzbekistan' => 'UZ',
        'vanuatu' => 'VU',
        'vatican_city' => 'VA',
        'venezuela' => 'VE',
        'vietnam' => 'VN',
        'u_s_virgin_islands' => 'VI',
        'wallis_and_futuna' => 'WF',
        'western_sahara' => 'EH',
        'yemen' => 'YE',
        'zambia' => 'ZM',
        'zimbabwe' => 'ZW'
    );

    /**
    * @deprecated 3.1.0 - Use rlWeatherForecastAdmin::updateBox
    **/
    public function updateBox() {}

    /**
     * @deprecated 3.1.0 - Use rlWeatherForecastAdmin::get
     */
    public function get() {}
}
