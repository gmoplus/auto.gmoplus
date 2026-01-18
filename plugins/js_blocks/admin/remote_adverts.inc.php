<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : REMOTE_ADVERTS.INC.PHP
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

$rlSmarty->assign('listing_types', $rlListingTypes->types);

$reefless->loadClass('RemoteAdverts', null, 'js_blocks');
$rlXajax->registerFunction(['loadCategories', $rlRemoteAdverts, 'ajaxLoadCategories']);

$boxID = 'ra' . mt_rand();
$_SESSION['raBoxes'][] = $boxID;

$out = '<div id="' . $boxID . '"> </div>';
$out .= '<script async="true" type="text/javascript" src="' . RL_PLUGINS_URL . 'js_blocks/blocks.inc.php[aurl]"></script>';

$rlSmarty->assign('out', $out);
$rlSmarty->assign('box_id', $boxID);

$rlRemoteAdverts->removeOldCacheFiles();
