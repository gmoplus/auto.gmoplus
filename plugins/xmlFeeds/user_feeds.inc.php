<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : USER_FEEDS.INC.PHP
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

$formats = $rlDb->fetch(['Key', 'Name'], array('Status'=>'active'), "AND FIND_IN_SET('import', `Format_for`) ORDER BY `Key`", null, 'xml_formats');

if (!$formats) {
    $errors[] = $lang['xf_configure_formats'];
} else {
    $rlSmarty->assign_by_ref('formats', $formats);

    $reefless->loadClass('XmlImport', null, 'xmlFeeds');

    if ($_POST['submit']) {
        $feed_url = $_POST['feed_url'];
        $format = $_POST['xml_format'];
        $name = $_POST['feed_name'];
        $feed_key = $rlValid->str2key($name) . "_" . $account_info['ID'] . "_" . rand();

        if (!$format || $format == "0") {
            $errors[] = str_replace('{field}', '<span class="field_error">'. $lang['xf_format'] .'</span>', $lang['notice_field_empty']);
            $error_fields .= 'xml_format,';
        }

        if (!trim($feed_url)) {
            $errors[] = str_replace('{field}', '<span class="field_error">'. $lang['xf_feed_url'] .'</span>', $lang['notice_field_empty']);
            $error_fields .= 'feed_url,';
        } elseif ($rlDb->getOne('Key', "`Url` = '{$feed_url}'", "xml_feeds")) {
            $errors[] = $lang['xf_notice_url_exist'];
            $error_fields .= 'feed_url,';
        } elseif (!$rlValid->isUrl($feed_url)) {
            $errors[] = str_replace('{field}', '<span class="field_error">'. $lang['xf_feed_url'] .'</span>', $lang['notice_field_incorrect']);
            $error_fields .= 'feed_url,';
        }

        if (!$errors) {
            $insert['Key'] = $feed_key;
            $insert['Name'] = $name;
            $insert['Url'] = $feed_url;
            $insert['Account_ID'] = $account_info['ID'];
            $insert['Format'] = $format;
            $insert['Plan_ID'] = $rlDb->getOne("ID", "`Status` = 'active' AND `Price` = 0", "listing_plans");
            $insert['Default_category'] = '';
            $insert['Listings_status'] = $config['xml_users_feeds_status'] == 'active' ? 'active' : 'approval';
            $insert['Status'] = 'pending';

            if ($rlDb->insertOne($insert, "xml_feeds")) {
                $reefless->loadClass('Notice');
                $rlNotice->saveNotice($lang['xf_feed_submitted']);
                $reefless->refresh();
            }
        }
    }

    $sql = "SELECT *, ";
    $sql .="IF(UNIX_TIMESTAMP(`Lastrun`) = 0, 0, `Lastrun`) AS `Lastrun` ";
    $sql .="FROM `{db_prefix}xml_feeds` ";
    $sql .="WHERE `Account_ID` = {$account_info['ID']} ";
    $feeds = $rlDb->getAll($sql);

    $rlSmarty->assign("feeds", $feeds);

    $reefless->loadClass('XmlFeeds', null, "xmlFeeds");
    $rlXajax->registerFunction(array('deleteXmlFeed', $rlXmlFeeds, 'ajaxDeleteXmlFeed'));
}
