<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.8.1
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: REQUESTS.INC.PHP
 *
 *	The software is a commercial product delivered under single, non-exclusive,
 *	non-transferable license for one domain or IP address. Therefore distribution,
 *	sale or transfer of the file in whole or in part without permission of Flynax
 *	respective owners is considered to be illegal and breach of Flynax License End
 *	User Agreement.
 *
 *	You are not allowed to remove this information from the file without permission
 *	of Flynax respective owners.
 *
 *	Flynax Classifieds Software 2022 |  All copyrights reserved.
 *
 *	https://www.flynax.com/
 *
 ******************************************************************************/

$reefless->loadClass('Notice');
$reefless->loadClass('BankWireTransferGateway', null, 'bankWireTransfer');

$item_id = (int) $_GET['item'];

if ($item_id) {
    $txn_info = $rlDb->fetch('*', array('ID' => $item_id), null, 1, 'transactions', 'row');
    $buyer = $rlAccount->getProfile((int) $txn_info['Account_ID'], true);
    $payment_details = $rlBankWireTransferGateway->getPaymentDetails($txn_info['Dealer_ID']);

    $rlHook->load('phpBankWireTransferRequestDetails');

    $txn_info['Status'] = $lang[$txn_info['Status']];
    $rlSmarty->assign_by_ref('buyer', $buyer);
    $rlSmarty->assign_by_ref('txn_info', $txn_info);
    $rlSmarty->assign_by_ref('payment_details', $payment_details['content']);

    $bread_crumbs[] = array(
        'name' => $lang['bwt_request_details'] . ' (#' . $txn_info['Txn_ID'] . ')',
    );
} else {
    $lang['bwt_doc_file'] = $GLOBALS['rlLang']->getPhrase('bwt_doc_file', null, null, true);
    $pInfo['current'] = (int) $_GET['pg'];
    $page = $pInfo['current'] ? $pInfo['current'] - 1 : 0;

    $from = $page * $config['listings_per_page'];
    $limit = $config['listings_per_page'];

    $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.*, `T2`.`Username`, `T2`.`Own_address`, ";
    $sql .= "IF(`T2`.`Last_name` <> '' AND `T2`.`First_name` <> '', ";
    $sql .= "CONCAT(`T2`.`First_name`, ' ', `T2`.`Last_name`), `T2`.`Username`) AS `Full_name` ";
    $sql .= "FROM `{db_prefix}transactions` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Account_ID` = `T2`.`ID` ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' AND `T1`.`Dealer_ID` = '{$account_info['ID']}' ";
    $sql .= "ORDER BY `T1`.`Date` DESC LIMIT {$from}, {$limit}";

    $requests = $rlDb->getAll($sql);

    foreach ($requests as $key => $val) {
        $requests[$key]['Type'] = $lang[$val['Type']];
        $requests[$key]['pStatus'] = $val['Status'];
        $requests[$key]['Status'] = $lang[$val['Status']];
    }

    $calc = $rlDb->getRow("SELECT FOUND_ROWS() AS `calc`");

    $pInfo['calc'] = $calc['calc'];
    $rlSmarty->assign_by_ref('pInfo', $pInfo);

    $rlHook->load('phpBankWireTransferRequests', $requests);

    $rlSmarty->assign_by_ref('requests', $requests);
}
