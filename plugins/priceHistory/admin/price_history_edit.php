<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : PRICE_HISTORY_EDIT.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2024
 *	https://www.flynax.com
 *
 ******************************************************************************/

require_once '../../../includes/config.inc.php';
require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
require_once RL_LIBS . 'system.lib.php';
require_once RL_LIBS . 'smarty' . RL_DS . 'Smarty.class.php';

$reefless->loadClass('Smarty');
$listing_id = (int) $_GET['id'];

$sql = "SELECT * FROM `" . RL_DBPREFIX . "price_history` WHERE `Listing_id` = {$listing_id} ";
$ph_info = $rlDb->getAll($sql);
foreach ($ph_info as $ph_key => $ph_value) {
    $price_array = explode('|', $ph_value['Price']);
    $ph_info[$ph_key]['price_value'] = $price_array[0];
    $ph_info[$ph_key]['price_currency'] = $price_array[1];
    $ph_info[$ph_key]['Date'] = $ph_value['Date'] == '0000-00-00 00:00:00' ? '' : date('Y-m-d', strtotime($ph_value['Date']));
}

$currecny_data_format = $rlDb->getOne('ID', "`Key` = 'currency'", 'data_formats');
$sql = "SELECT `Key` FROM `" . RL_DBPREFIX . "data_formats` WHERE `Parent_ID` = {$currecny_data_format}";
$currency_array = $rlDb->getAll($sql, array(false, 'Key'));

foreach ($currency_array as $currency_key => $currency) {
    $tmp_array['currency_key'] = $currency;
    $tmp_array['currency_value'] = $lang['data_formats+name+' . $currency];
    $currency_array[$currency_key] = $tmp_array;
}

$rlSmarty->assign('ph_info', $ph_info);
$rlSmarty->assign('currencies', $currency_array);
$rlSmarty->assign('lang', $GLOBALS['lang']);
$rlSmarty->display(RL_PLUGINS . 'priceHistory' . RL_DS . 'admin' . RL_DS . 'price_history_edit.tpl');
