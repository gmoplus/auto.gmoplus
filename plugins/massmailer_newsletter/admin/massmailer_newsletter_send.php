<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : MASSMAILER_NEWSLETTER_SEND.PHP
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

/* system config */
require_once '../../../includes/config.inc.php';
require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
require_once RL_LIBS . 'system.lib.php';

$reefless->loadClass('Mail');

$id = (int) $_GET['mail_tpl_id'];
$massmailer = $rlDb->fetch('*', array('ID' => $id), " AND `Status` <> 'trash'", 1, 'massmailer', 'row');

//get accounts
if (!empty($massmailer['Recipients_accounts'])) {
    $account_types = explode(",", $massmailer['Recipients_accounts']);

    if ($account_types) {
        foreach ($account_types as $key => $val) {
            $email_account_site[] = $rlDb->fetch(
                array('Mail'),
                array('Status' => 'active', 'Type' => $val),
                "AND `Mail` <> ''",
                null,
                'accounts',
                'all'
            );
        }
    }
}
$newslaters_accouts = $massmailer['Recipients_newsletter'];

if ($newslaters_accouts) {
    if ($naccounts = $rlDb->fetch(
        array('Mail'),
        array('Status' => 'active'),
        "AND `Mail` <> ''",
        null,
        'subscribe',
        'all')
    ) {
        $email_account_site[] = $naccounts;
    }
}

$contact_us_accouts = $massmailer['Recipients_contact_us'];

if ($contact_us_accouts) {
    $email_account_site[] = $rlDb->fetch(
        array('Email` as `Mail'),
        array('Status' => 'active'),
        "AND `Email` <> ''",
        null,
        'contacts',
        'all'
    );
}
foreach ($email_account_site as $key => $val) {
    if (!empty($email_account_site[$key])) {
        foreach ($email_account_site[$key] as $key2 => $val2) {
            if (!empty($email_account_site[$key][$key2])) {
                $email_accounts_site[] = $val2;
            }
        }
    }
}
$mail_tpl['subject'] = $massmailer['Subject'];
$mail_tpl['body'] = $massmailer['Body'];

$items['count'] = count($email_accounts_site);

if ($GLOBALS['rlMail']->send($mail_tpl, $email_accounts_site[$_GET['step']]['Mail'], false, $massmailer['From'])) {
    $items['send'] = 1;
    $items['emails'] = $email_accounts_site[$_GET['step']];
    echo json_encode($items);
} else {
    $items['send'] = 0;
    echo json_encode($items);
}
