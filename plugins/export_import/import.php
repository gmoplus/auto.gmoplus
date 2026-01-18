<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLEXPORTIMPORT.CLASS.PHP
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2024 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

set_time_limit(0);

/* system config */
require_once('../../includes/config.inc.php');
require_once(RL_INC . 'control.inc.php');

$reefless->loadClass('ListingTypes');
$reefless->loadClass('Actions');
$reefless->loadClass('Cache');
$reefless->loadClass('Categories');
$reefless->loadClass('Listings');

$limit = $_SESSION['iel_data']['post']['import_per_run'];
$start = (int) $_GET['index'];

$account_info = $_SESSION['account'];

/* exit if user not logged in */
$reefless->loadClass('Account');

$languages = $rlLang->getLanguagesList();
$rlLang->defineLanguage($rlNavigator->cLang);

if ($config['membership_module']) {
    $reefless->loadClass('MembershipPlan');
}

if (!$rlAccount->isLogin()) {
	exit;
}

/* prepare available listings */
foreach ($_SESSION['iel_data']['post']['rows_tmp'] as $index => $val)
{
	$available_rows[] = $index;
}

$reefless->loadClass('ExportImport', null, 'export_import');
$rlExportImport->import(
	$_SESSION['iel_data']['post']['data'],
	$available_rows,
	$_SESSION['iel_data']['post']['cols'],
	$_SESSION['iel_data']['post']['field'],
	$start,
	$limit,
	$_SESSION['iel_data']['post']['import_listing_type'],
	$_SESSION['iel_data']['post']['import_category_id'],
	$account_info['ID'],
	$_SESSION['iel_data']['post']['import_plan_id'],
	false,
	$_SESSION['iel_data']['post']['import_status'],
	true
);

$items['from'] = $start + $limit;
$items['to'] = $start + ($limit * 2) - 1;
$items['count'] = count($available_rows);

echo json_encode($items);
