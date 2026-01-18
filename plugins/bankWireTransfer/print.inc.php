<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.8.1
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: PRINT.INC.PHP
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

$reefless->loadClass('BankWireTransferGateway', null, 'bankWireTransfer');

$Txn_ID = $rlValid->xSql($_GET['txn_id']);

if (!empty($Txn_ID)) {
    if (!is_object('rlGateway')) {
        require_once RL_CLASSES . 'rlGateway.class.php';
    }
    // get transaction info
    $txn_info = $rlBankWireTransferGateway->getTransactionByReference($Txn_ID);

    if ($txn_info && $txn_info['Account_ID'] == (int) $account_info['ID'] 
        || $transaction_info['Account_ID'] == $_SESSION['registration']['account_id']
    ) {
        $rlSmarty->assign_by_ref('txn_info', $txn_info);

        $payment_details = $rlBankWireTransferGateway->getPaymentDetails($txn_info['Dealer_ID']);
        $rlSmarty->assign_by_ref('payment_details', $payment_details);

        $rlSmarty->display(RL_PLUGINS . 'bankWireTransfer' . RL_DS . 'print.tpl');
    }
    exit;
} else {
    $sError = true;
}
