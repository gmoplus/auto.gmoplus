<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : CACHING.INC.PHP
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

$response = array(
    'configs' => array(),
    'languages' => array(),
    'lang_keys' => array(),
    'listing_types' => array(),
    'account_types' => array(),
    'categories' => array(),
    'lfields' => array(),
    'afields' => array(),
    'search_forms' => array(),
    'nearby_ads_sform' => array(),
    'account_search_forms' => array(),
);

// add app configs
$response['configs'] = $iOSHandler->getConfigs();

/* languages */
$rlDb->setTable('iflynax_languages');
$language_fields = array('Code', 'Direction', 'Key', 'Date_format');
$languages = $rlDb->fetch($language_fields, array('Status' => 'active'));

$lang_codes = array();
if (count($languages) > 1) {
    foreach ($languages as $language) {
        $lang_keys[] = $language['Key'];
    }

    $another_languages_names = array();
    $_where = "WHERE `Key` IN ('" . implode("','", $lang_keys) . "')";
    $_names = $rlDb->fetch(array('Key', 'Value'), null, $_where, null, 'iflynax_phrases');

    foreach ($_names as $_lang) {
        $_languages[$_lang['Key']] = $_lang['Value'];
    }
}
/* languages end */

/* language phrases */
foreach ($languages as $language) {
	$code = $language['Code'];
	$response['lang_keys'][$code] = $iOSHandler->getLangPhrases($code);

    if (is_array($_languages)) {
        $response['lang_keys'][$code] = array_merge($response['lang_keys'][$code], $_languages);
    }

	$language['name'] = strval($response['lang_keys'][$code][$language['Key']]);
	$response['languages'][$code] = $language;
}
unset($languages);
/* language phrases end */

/* listing types */
if ($iOSHandler->listing_types) {
	foreach ($iOSHandler->listing_types as $type) {
        $type_key = $type['Key'];

		$response['listing_types'][$type_key] = array(
			'key'      => strval($type_key),
			'photo'    => (bool)$type['Photo'],
			'video'    => (bool)$type['Video'],
			'page'     => version_compare($config['rl_version'], '4.7.0', '>=') ?: (bool) $type['Page'],
            'search'   => (bool)$type['Search'],
			'aSearch'  => (bool)$type['Advanced_search_availability'],
            'position' => intval($type['iFlynax_position']),
			'icon'     => $type['iFlynax_icon'] ? 'ltype_' . $type['iFlynax_icon'] : 'menu_icon_default',
			'name'     => $iOSHandler->trueNameOrKeyInstead($lang['listing_types+name+' . $type_key], $type_key),

            'categoriesSortBy' => ($type['Cat_order_type'] == 'alphabetic') ? 'name' : 'position',
            'photoRequired'    => (bool) $type['Photo_required'],
		);

        // categories 1 level
        $response['categories'][$type_key] = $iOSHandler->getCategories($type_key, 0, false, true);
	}
}
/* listing types end */

// home screen ads
define('CONTROLLER', 'home');
require_once(RL_IPHONE_CONTROLLERS . CONTROLLER . '.inc.php');

// add account types
$iOSHandler->getAccountTypes($response);

/* defined multifield trigger */
// TODO: move it to another place
$mfield_plugin = $rlDb->getOne('Key', "`Status` = 'active' AND `Key` = 'multiField'", 'plugins');
define('MULTI_FIELD_PLUGIN_INSTALLED', !empty($mfield_plugin));
/* defined multifield trigger END */

// account fields
$response['lfields'] = $iOSHandler->activeFields($mfield_plugin, 'listing_fields');

// listings fields
$response['afields'] = $iOSHandler->activeFields($mfield_plugin, 'account_fields');

// fetch search forms
$reefless->loadClass('Search');
$response['search_forms'] = $iOSHandler->getListingTypeSearchForms();
$response['nearby_ads_sform'] = $iOSHandler->getNearbyAdsSearchForm();
$response['account_search_forms'] = $iOSHandler->getAccountSearchForms();

// Google AdMob
$response['google_admob'] = $iOSHandler->getGoogleAdmob();

// !!!TEMPORARY!!! - LOOK AT ME
if (defined('IS_LOGIN') && $account_id = intval($account_info['ID'])) {
    $device_lang = $iOSHandler->getAppLanguage();
    $sql = "UPDATE `" . RL_DBPREFIX . "iflynax_push_tokens` SET `Language` = '{$device_lang}' ";
    $sql .= "WHERE `Account_id` = " . $account_id;
    $rlDb->query($sql);
}

// send response to iOS device
$iOSHandler->send($response);
