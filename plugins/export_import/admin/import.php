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
require_once('../../../includes/config.inc.php');
require_once(RL_ADMIN_CONTROL . 'ext_header.inc.php');
require_once(RL_LIBS . 'system.lib.php');

$reefless->loadClass('Actions');
$reefless->loadClass('Cache');
$reefless->loadClass('Categories');
$reefless->loadClass('Listings');

if ($config['membership_module']) { 
    $reefless->loadClass('Account');
    $reefless->loadClass('MembershipPlan');
}

$limit = $_SESSION['iel_data']['post']['import_per_run'];
$start = (int)$_GET['index'];

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
	$_SESSION['iel_data']['post']['import_account_id'],
	$_SESSION['iel_data']['post']['import_plan_id'],
	$_SESSION['iel_data']['post']['import_paid'],
	$_SESSION['iel_data']['post']['import_status']
);

$items['from'] = $start + $limit;
$items['to'] = $start + ($limit * 2) - 1;
$items['count'] = count($available_rows);

if ($items['count'] <= $items['from']) {
    $_SESSION['iel_data']['completed'] = true;
}

echo json_encode($items);
