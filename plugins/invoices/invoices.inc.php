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

$reefless->loadClass('Notice');
$reefless->loadClass('Actions');
$reefless->loadClass('Invoices', null, 'invoices');

$invoice_id = $_GET['nvar_1'] ? $_GET['nvar_1'] : $_REQUEST['item'];
$invoice_id = $rlValid->xSql($invoice_id);

if (!empty($invoice_id)) {
    $invoice_info = $rlInvoices->getInvoice($invoice_id, $account_info['ID']);
    $rlSmarty->assign_by_ref('invoice_info', $invoice_info);

    $page_info['name'] = $invoice_info['pStatus'] == 'paid' ? $lang['invoice_details'] : $invoice_info['Subject'];

    $bread_crumbs[] = array(
        'name' => $invoice_info['Txn_ID'],
    );

    if (!empty($invoice_info)) {
        if ($invoice_info['pStatus'] == 'unpaid') {
            $cancel_url = $reefless->getPageUrl('invoices');
            $cancel_url .= ($GLOBALS['config']['mod_rewrite'] ? '?' : '&') . 'canceled';

            $success_url = $reefless->getPageUrl('invoices');
            $success_url .= ($GLOBALS['config']['mod_rewrite'] ? '?' : '&') . 'completed';

            if (!$rlPayment->isPrepare()) {
                // clear payment options
                $rlPayment->clear();

                $rlHook->load('addInvoiceCheckoutPreRedirect');

                // set payment options
                $rlPayment->setOption('service', 'invoice');
                $rlPayment->setOption('total', $invoice_info['Total']);
                $rlPayment->setOption('item_id', $invoice_info['ID']);
                $rlPayment->setOption('item_name', $invoice_info['Subject'] . ' (#' . $invoice_id . ')');
                $rlPayment->setOption('account_id', $account_info['ID']);
                $rlPayment->setOption('callback_class', 'rlInvoices');
                $rlPayment->setOption('callback_method', 'completeTransaction');
                $rlPayment->setOption('cancel_url', $cancel_url);
                $rlPayment->setOption('success_url', $success_url);
                $rlPayment->setOption('plugin', 'invoices');

                $rlPayment->init($errors);
            } else {
                $rlPayment->checkout($errors);
            }
        }

        /* enable print page */
        $print = array(
            'item' => 'invoice',
            'id' => $invoice_id,
        );
        $rlSmarty->assign_by_ref('print', $print);
    } else {
        $sError = true;
    }
} else {
    if (isset($_GET['canceled']) || isset($_GET['completed'])) {
        if (isset($_GET['completed'])) {
            $phrase_key = $rlPayment->getOption('gateway') == 'bankWireTransfer'
            ? 'invoices_payment_bank_transfer'
            : 'invoices_payment_completed';

            $_SESSION['unpaidInvoices'] = $rlInvoices->getInvoicesByStatus($account_info['ID'], 'unpaid');
            $rlNotice->saveNotice($lang[$phrase_key]);
        }
        if (isset($_GET['canceled'])) {
            $errors[] = $lang['invoices_payment_canceled'];
            $rlSmarty->assign_by_ref('errors', $errors);
        }
    }

    $pInfo['current'] = (int) $_GET['pg'];
    $page = $pInfo['current'] ? $pInfo['current'] - 1 : 0;

    $invoices = $rlInvoices->getInvoices($account_info['ID'], $page);
    $pInfo['calc'] = $rlInvoices->calc;

    $rlSmarty->assign_by_ref('invoices', $invoices);
    $rlSmarty->assign_by_ref('pInfo', $pInfo);
}
