<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : INVOICES.INC.PHP
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

/* ext js action */

use Flynax\Utils\Valid;

if ($_GET['q'] == 'ext') {
    // system config
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $search = Valid::escape($_GET['search']);

    $sort = Valid::escape($_GET['sort']);
    $sortDir = Valid::escape($_GET['dir']);

    $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.*, `T2`.`Item_ID`, `T1`.`Account_ID`, `T3`.`Username`, `T3`.`Last_name`,  `T3`.`First_name` ";
    $sql .= "FROM `{db_prefix}invoices` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}transactions` AS `T2` ON `T1`.`Txn_ID` = `T2`.`Txn_ID` ";
    $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T3` ON `T1`.`Account_ID` = `T3`.`ID` ";

    $sql .= "WHERE `T1`.`pStatus` <> 'trash' ";

    $username   = Valid::escape($_GET['username']);
    $invoiceId = Valid::escape($_GET['invoice_id']);

    if ($search) {
        if (!empty($username)) {
            $sql .= " AND `T3`.`Username` LIKE '%{$username}%' ";
        }
        if (!empty($invoiceId)) {
            $sql .= " AND `T1`.`Txn_ID` LIKE '%{$invoiceId}%' ";
        }
        if (!empty($_GET['invoice_status'])) {
            $status = $_GET['invoice_status'];

            if (in_array($status, array('paid', 'unpaid'))) {
                $sql .= " AND `T1`.`pStatus` = '{$status}' ";
            }
        }
    }

    $sql .= "ORDER BY ";
    if ($sort == 'Username') {
        $sql .= "`T3`.`{$sort}` {$sortDir} ";
    } else {
        $sql .= "`T1`.`{$sort}` {$sortDir} ";
    }

    $sql .= "LIMIT {$start}, {$limit}";

    $data = $rlDb->getAll($sql);
    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    foreach ($data as $key => $val) {
        $data[$key]['pStatus'] = $lang[$val['pStatus']];
    }

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
    exit();
}

$reefless->loadClass('Invoices', null, 'invoices');

if (isset($_GET['action'])) {
    // get all languages
    $allLangs = $GLOBALS['languages'];
    $rlSmarty->assign_by_ref('allLangs', $allLangs);

    $bcAStep[] = array('name' => $_GET['action'] == 'add' ? $lang['invoices_add_item'] : $lang['invoices_edit_item']);

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        // get account types
        $reefless->loadClass('Account');

        // get current plan info
        if (isset($_GET['item'])) {
            $id = (int) $_GET['item'];

            $invoice_info = $rlDb->fetch('*', array('ID' => $id), null, null, 'invoices', 'row');
            $rlSmarty->assign_by_ref('invoice_info', $invoice_info);
        }

        if (isset($_POST['submit'])) {
            $errors = $error_fields = array();

            if (empty($_POST['account'])) {
                array_push($errors, str_replace('{field}', "<b>{$lang['username']}</b>", $lang['notice_field_empty']));
                array_push($error_fields, "account");
            }

            if (empty($_POST['total'])) {
                array_push($errors, str_replace('{field}', "<b>{$lang['price']}</b>", $lang['notice_field_empty']));
                array_push($error_fields, "total");
            }

            if ($_POST['total'] < 0) {
                array_push($errors, str_replace('{field}', "<b>{$lang['price']}</b>", $lang['invoices_price_error']));
                array_push($error_fields, "total");
            }

            if (empty($_POST['subject'])) {
                array_push($errors, str_replace('{field}', "<b>{$lang['subject']}</b>", $lang['notice_field_empty']));
                array_push($error_fields, "subject");
            }

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                if ($_GET['action'] == 'add') {
                    $username = Valid::escape($_POST['account']);

                    $sql = "SELECT `ID`,`Username`,`First_name`,`Last_name`,`Mail` FROM `{db_prefix}accounts` WHERE `Username` = '{$username}'";
                    $account_info = $rlDb->getRow($sql);

                    $account_info['Full_name'] = trim(
                        $account_info['First_name'] || $account_info['Last_name']
                        ? $account_info['First_name'] . ' ' . $account_info['Last_name']
                        : $account_info['Username']
                    );

                    // generate invoice ID
                    $invoice_txn_id = $rlInvoices->generate($config['invoices_txn_tpl']);

                    // write main plan information
                    $data = array(
                        'Account_ID' => (int) $account_info['ID'],
                        'Total' => (float) $_POST['total'],
                        'Txn_ID' => $invoice_txn_id,
                        'Subject' => $_POST['subject'],
                        'Description' => $_POST['description'],
                        'Date' => 'NOW()',
                    );

                    if ($action = $rlDb->insertOne($data, 'invoices', array('Description'))) {
                        $invoice_id = method_exists($rlDb, 'insertID') ? $rlDb->insertID() : mysql_insert_id();

                        $reefless->loadClass('Mail');
                        $mail_tpl = $rlMail->getEmailTemplate('create_invoice');

                        $link = $reefless->getPageUrl('invoices', array('item' => $data['Txn_ID']));
                        $total = $config['system_currency_position'] == 'before' 
                        ? $config['system_currency'] . number_format($data['Total'], 2, '.', '') 
                        : number_format($data['Total'], 2, '.', '') . ' ' . $config['system_currency'];

                        $find = array(
                            '{invoice_id}',
                            '{name}',
                            '{link}',
                            '{subject}',
                            '{amount}',
                            '{date}',
                            '{description}',
                        );

                        $replace = array(
                            $invoice_txn_id,
                            $account_info['Full_name'],
                            '<a href="' . $link . '">' . $link . '</a>',
                            $data['Subject'],
                            $total,
                            date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT)),
                            $data['Description'],
                        );

                        $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
                        $mail_tpl['subject'] = str_replace($find, $replace, $mail_tpl['subject']);
                        $rlMail->send($mail_tpl, $account_info['Mail']);

                        $message = $lang['invoices_item_added'];
                        $aUrl = array("controller" => $controller);
                    } else {
                        trigger_error("Can't add new banner plan (MYSQL problems)", E_WARNING);
                        $rlDebug->logger("Can't add new banner plan (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    $update_data = array(
                        'fields' => array(
                            'Total' => $_POST['total'],
                            'Subject' => $_POST['subject'],
                            'Description' => $_POST['description'],
                        ),
                        'where' => array('ID' => $id),
                    );
                    $action = $rlDb->updateOne($update_data, 'invoices', array('Description'));

                    $message = $lang['invoices_item_edited'];
                    $aUrl = array("controller" => $controller);
                }

                if ($action) {
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                }
            }
        }
    } elseif ($_GET['action'] == 'view') {
        $bcAStep = $lang['invoice_view_details'];

        /* get transaction info    */
        $sql = "SELECT `T1`.*, `T2`.`Item_ID`, `T2`.`Plan_ID`, `T2`.`Service`, `T3`.`Username`, `T3`.`Last_name`,  `T3`.`First_name` ";
        $sql .= "FROM `{db_prefix}invoices` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}transactions` AS `T2` ON `T1`.`Txn_ID` = `T2`.`Txn_ID` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T3` ON `T1`.`Account_ID` = `T3`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = '{$_GET['item']}' ";
        $sql .= "LIMIT 1";

        $invoice_info = $rlDb->getRow($sql);
        $rlSmarty->assign_by_ref('invoice_info', $invoice_info);
    }
} else {   
    // filter invoice statuses
    $invoice_statuses = array(
        'paid',
        'unpaid'
    );
    $rlSmarty->assign_by_ref('invoice_statuses', $invoice_statuses);
}

/* register ajax methods */
$rlXajax->registerFunction(array('deleteItem', $rlInvoices, 'ajaxDeleteItem'));
