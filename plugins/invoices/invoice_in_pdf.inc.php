<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : INVOICE_IN_PDF.INC.PHP
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

$reefless->loadClass('Invoices', null, 'invoices');

$type = $_GET['nvar_1'] ? $_GET['nvar_1'] : $_REQUEST['type'];
$invoice_id = (int) $_REQUEST['item'];

if ($type && $invoice_id) {
    $rlInvoices->initPDF();
    $rlInvoices->buildPDF($type, array('txn_id' => $invoice_id), true);
} else {
    $sError = true;
}
