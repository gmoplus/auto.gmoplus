<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: CRON_PHOTOS.PHP
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
 *  Flynax Classifieds Software 2025 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

require_once(str_replace('plugins' . DIRECTORY_SEPARATOR . 'xmlFeeds', 'includes', dirname(__FILE__)) . '/config.inc.php');
require_once('control.inc.php');

$listings_limit = 20;

$sql ="SELECT `Listing_ID` FROM `{db_prefix}xml_photos` ";
$sql .="WHERE `Status` != 'in_progress' ";
$sql .="GROUP BY `Listing_ID` ";
$sql .="ORDER BY `Listing_ID` ";
$sql .="LIMIT {$listings_limit} ";

$listing_ids = $rlDb->getAll($sql, array(false, 'Listing_ID'));

$ids = '';
foreach ($listing_ids as $id) {
    $ids .=$id . ",";
}
$ids = substr($ids, 0, -1);

$sql = "UPDATE `{db_prefix}xml_photos` SET `Status` = 'in_progress' ";
$sql .="WHERE FIND_IN_SET(`Listing_ID`, '{$ids}')";

$rlDb->query($sql);

if (!$listing_ids) {
    $rlDb->connectionClose();
    exit;//nothing to copy
}

$reefless->loadClass('XmlImport', null, 'xmlFeeds');

foreach ($listing_ids as $key => $listing_id) {
    $sql = "SELECT * FROM `{db_prefix}xml_photos` WHERE `Listing_ID` = {$listing_id}";
    $photos = $rlDb->getAll($sql, array(false, "Source"));

    $rlXmlImport->copyPhotos($photos, $listing_id);

    $sql = "DELETE FROM `{db_prefix}xml_photos` WHERE `Listing_ID` = {$listing_id}";
    $rlDb->query($sql);
}

$rlDb->connectionClose();
