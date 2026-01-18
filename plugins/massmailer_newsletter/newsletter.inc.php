<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : NEWSLETTER.INC.PHP
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

use Flynax\Utils\Valid;


$reefless->loadClass('MassmailerNewsletter', false, 'massmailer_newsletter');

if ($_GET['nvar_1'] == 'unsubscribe') {
    $page_info['name'] = $lang['massmailer_newsletter_unsubscribe'];

    // get required variables from get hash
    $hash  = Valid::escape($_GET['hash']);
    $type  = $hash[0];
    $email = substr($hash, 1, 32);
    $date  = substr($hash, 33);

    switch ($type) {
        case 1:
            $select = "`ID`, IF(`First_name` OR `Last_name`, ";
            $select .= "CONCAT(`First_name`, ' ', `Last_name`), `Username`) AS `subscriber_name`";
            $table = 'accounts';
            $field = 'Subscribe';
            $where = 'Mail';
            $value = 0;

            break;
        case 2:
            $select = "`ID`, `Name` AS `subscriber_name`";
            $table = 'subscribers';
            $field = 'Status';
            $where = 'Mail';
            $value = 'approval';

            break;
        case 3:
            $select = "`ID`, `Name` AS `subscriber_name`";
            $table = 'contacts';
            $where = 'Email';
            $field = 'Subscribe';
            $value = 0;

            break;
    }
    if ($table && $field && $where) {
        $subscribe_info = $rlDb->getRow("
            SELECT {$select} 
            FROM `{db_prefix}{$table}` 
            WHERE MD5(`{$where}`) = '{$email}' AND MD5(`Date`) = '{$date}' AND `{$field}` <> '{$value}'
        ");

        $rlSmarty->assign_by_ref('subscribe_info', $subscribe_info);
    }

    if ($_POST['action'] == 'unsubscribe') {
        if ($subscribe_info['ID']) {
            if ($type == 2) {
                $rlDb->query("
                    DELETE FROM `{db_prefix}subscribers` 
                    WHERE `ID` = {$subscribe_info['ID']} LIMIT 1
                ");
            } else {
                /* update status */
                $reefless->loadClass('Actions');

                $update = array(
                    'fields' => array(
                        $field => $value,
                    ),
                    'where' => array(
                        'ID' => $subscribe_info['ID'],
                    ),
                );
                $rlDb->updateOne($update, $table);
            }
            $rlSmarty->assign('unsubscribed', true);
        } else {
            $errors[] = str_replace(
                '[sitename]',
                $GLOBALS['lang']['pages+title+home'],
                $rlMassmailerNewsletter->getPhrase('massmailer_newsletter_incorrect_request')
            );
            $rlSmarty->assign_by_ref('errors', $errors);
        }
    }
} else {
    $page_info['name'] = $lang['massmailer_newsletter_subscribe'];
    $key = Valid::escape($_GET['key']);

    $entry = $rlDb->fetch(array('ID', 'Status'), array('Confirm_code' => $key), null, 1, 'subscribers', 'row');

    if (empty($entry)) {
        $errors[] = $rlMassmailerNewsletter->getPhrase('massmailer_newsletter_incorrect_link');
        $rlSmarty->assign_by_ref('errors', $errors);
    } else {
        $subscriber = $rlDb->fetch(
            array('ID', 'Status', 'Name', 'Date'),
            array('Confirm_code' => $key),
            null,
            1,
            'subscribers',
            'row'
        );
        $rlSmarty->assign_by_ref('subscriber', $subscriber);

        if ($entry['Status'] == 'incomplete') {
            $rlDb->query("
                UPDATE `{db_prefix}subscribers` SET `Status` = 'active', `Confirm_code` = '' 
                WHERE `ID` = {$entry['ID']} LIMIT 1
            ");

            $reefless->loadClass('Notice');
            $rlNotice->saveNotice(
                str_replace(
                    '[sitename]',
                    $GLOBALS['lang']['pages+title+home'],
                    $rlMassmailerNewsletter->getPhrase('massmailer_newsletter_person_subscibed')
                )
            );
        }
    }
}
