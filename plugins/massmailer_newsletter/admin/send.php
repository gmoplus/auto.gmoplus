<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : SEND.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2022
 *	https://www.flynax.com
 *
 ******************************************************************************/

use Flynax\Utils\Valid;

/* system config */
require_once '../../../includes/config.inc.php';
require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
require_once RL_LIBS . 'system.lib.php';

$reefless->loadClass('Mail');

$id              = (int) $_POST['id'];
$index           = (int) $_POST['index'];
$selected_emails = [];

if ($_POST['selected_emails']) {
    foreach ($_POST['selected_emails'] as $email) {
        $selected_emails[] = Valid::escape($email);
    }
}

$massmailer = $rlDb->fetch('*', array('ID' => $id), " AND `Status` <> 'trash'", 1, 'massmailer', 'row');

if (!$massmailer) {
    echo false;
    exit;
}

if ($_SESSION['massmailer_sending']) {
    $emails = $_SESSION['massmailer_sending'];
} else {
    /* get "contact us" form visitors */
    if ($massmailer['Recipients_contact_us']) {
        $rlDb->setTable('contacts');
        $email_stack[] = $rlDb->fetch(
            array('Email` as `Mail', 'Name', 'Date`, 3 AS `MN_module'),
            array('Subscribe' => 1),
            "AND `Status` <> 'trash' AND `Email` <> ''"
        );
    }
    /* get newsletter subscribers */
    if ($massmailer['Recipients_newsletter']) {
        $rlDb->setTable('subscribers');
        $email_stack[] = $rlDb->fetch(
            array('Mail', 'Name', 'ID', 'Date`, 2 AS `MN_module'),
            array('Status' => 'active'),
            "AND `Mail` <> ''"
        );
    }
    /* get system accounts */
    if (!empty($massmailer['Recipients_accounts'])) {
        $rlDb->setTable('accounts');
        $email_stack[] = $rlDb->fetch(
            array('Mail', 'First_name', 'Last_name', 'Date', 'Username`, 1 AS `MN_module'),
            array('Status' => 'active', 'Subscribe' => 1),
            "AND FIND_IN_SET(`Type`, '{$massmailer['Recipients_accounts']}') > 0 AND `Mail` <> ''"
        );
    }

    $reefless->loadClass('MassmailerNewsletter', null, 'massmailer_newsletter');
    /* re-structure emails array */
    foreach ($email_stack as $stack) {
        if ($stack) {
            foreach ($stack as $item) {
                if ($item['Username']) {
                    $item['Name'] = $item['First_name'] || $item['Last_name']
                    ? trim($item['First_name'] . ' ' . $item['Last_name'])
                    : $item['Username'];
                }
                $emails[$item['Mail']] = $item;
            }
        }
    }

    $_SESSION['massmailer_sending'] = $emails;
}

foreach ($emails as $key => $value) {
    if (false === array_search($value['Mail'], $selected_emails)) {
        unset($emails[$key]);
    }
}

if (count($emails) <= 20) {
    $counter = $index;
    $multiplier = 0;
} elseif (count($emails) > 20 && count($emails) <= 500) {
    $counter = $index * 10;
    $multiplier = 10;
} elseif (count($emails) > 500) {
    $counter = $index * 100;
    $multiplier = 100;
}

$indexes = array_keys($emails);

for ($i = $counter; $i <= $counter + $multiplier; $i++) {
    $send_to = $emails[$indexes[$i]];
    $items['count'] = count($emails);

    if ($send_to) {
        $find = array('{name}', '{username}', '{site_name}', '{site_url}', '{site_email}');
        $replace = array(
            $send_to['Name'],
            $send_to['Name'],
            $lang['pages+title+home'],
            RL_URL_HOME,
            $config['site_main_email']
        );
        $mail_tpl['subject'] = str_replace($find, $replace, $massmailer['Subject']);
        $mail_tpl['body']    = str_replace($find, $replace, $massmailer['Body']);

        /* append unsubscribe footer */
        $path = $rlDb->getOne('Path', "`Key` = 'massmailer_newsletter_newsletter'", 'pages');
        $unsubscribe_link = RL_URL_HOME . RL_LANG_CODE . '/';
        $unsubscribe_link .= $config['mod_rewrite']
        ? $path . '/unsubscribe.html?'
        : 'index.php?page=' . $path . '&nvar_1=unsubscribe&';
        $unsubscribe_link .= 'hash=' . $send_to['MN_module'] . md5($send_to['Mail']) . md5($send_to['Date']);
        $unsubscribe_link = '<a href="' . $unsubscribe_link . '">$1</a>';

        $mail_tpl['body'] = preg_replace('/\[(.*)\]/', $unsubscribe_link, $mail_tpl['body']);

        $rlMail->send($mail_tpl, $send_to['Mail'], $config['owner_name'], $config['site_main_email']);
        $items['data'][] = $send_to;

        if ($index == count($emails) - 1) {
            unset($_SESSION['massmailer_sending']);
        }
    } else {
        $items['send'] = 0;
        unset($_SESSION['massmailer_sending']);
    }
}
echo json_encode($items);
