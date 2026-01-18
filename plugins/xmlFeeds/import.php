<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: IMPORT.PHP
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

set_time_limit(0);

if (defined('REALM')) {
    echo '<link href="' . str_replace('http://', '//', RL_PLUGINS_URL) . 'xmlFeeds/static/import_progress.css" type="text/css" rel="stylesheet" />';
} else {
    require_once dirname(dirname(__DIR__)) . '/includes/config.inc.php';
    require_once 'control.inc.php';

    $languages = $rlLang->getLanguagesList();
    $rlLang->defineLanguage();
    $lang = $rlLang->getLangBySide('admin', $config['lang']);
}

$feed2run = $argv[1] ?: $_GET['feed'];

$sql = "SELECT `T1`.*, `T1`.`Key` as `Feed`, `T2`.`Key` as `Format`, `T2`.`Xpath`, `T2`.`New_parser` ";
$sql .="FROM `{db_prefix}xml_feeds` AS `T1` ";
$sql .="LEFT JOIN `{db_prefix}xml_formats` AS `T2` ON `T2`.`Key` = `T1`.`Format` ";
$sql .="WHERE `T1`.`Status` = 'active' AND `T2`.`Status` = 'active' ";

if ($feed2run) {
    $sql .="AND `T1`.`Key` ='" . $feed2run . "' ";
}

$sql .="ORDER BY `Lastrun` ASC ";

if ($config['xml_feeds_per_run'] && !$feed2run) {
    $sql .="LIMIT 0, " . $config['xml_feeds_per_run'];
}

$feeds = $rlDb->getAll($sql);

/**
 * Emulate admin realm to pass owner checking condition in
 * ListingPictureUpload class constructor
 *
 * @todo - Remove in 4.7.1 software version and rework pictures upload system
 */
define('REALM', 'admin');

$reefless->loadClass('XmlImport', null, 'xmlFeeds');

foreach ($feeds as $feed_key => $feed) {
    $import_completed = false;

    $rlXmlImport->feed = $feed;
    $rlXmlImport->import();
    $rlXmlImport->clearVars();

    $sql ="UPDATE `{db_prefix}xml_feeds` SET `Lastrun` = NOW() ";
    $sql .=", `Run` = `Run` + 1 ";
    $sql .="WHERE `Key` = '{$feed['Key']}'";

    $rlDb->query($sql);
}

$rlDb->connectionClose();
