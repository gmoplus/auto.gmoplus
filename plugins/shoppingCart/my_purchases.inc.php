<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : MY_PURCHASES.INC.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2023
 *	https://www.flynax.com
 *
 ******************************************************************************/

use \ShoppingCart\Orders;
use \ShoppingCart\PrintOrder;
use \ShoppingCart\Escrow;

if (!defined('IS_LOGIN')) {
    $reefless->redirect(false, $reefless->getPageUrl('login'));
}

$reefless->loadClass('ShoppingCart', null, 'shoppingCart');

$step = $_GET['nvar_1'] ? $_GET['nvar_1'] : $_REQUEST['step'];
$itemID = intval($_REQUEST['item']);
$ordersObj = new \ShoppingCart\Orders();

if (!isset($lang['shc_shipping_price']) || !isset($lang['shc_shipping_status'])) {
    $lang = array_merge($lang, $GLOBALS['rlShoppingCart']->getPhrases(['add_listing', 'shopping_cart', 'my_shopping_cart', 'listing_details']));
}

if ($itemID) {
    $orderInfo = $ordersObj->get($itemID, true, true, 'Buyer_ID');
    $rlSmarty->assign('itemID', $itemID);

    if ($orderInfo['Status'] == 'paid' && $orderInfo['Escrow_status'] == 'pending' && $config['shc_escrow'] && $config['shc_method'] == 'multi') {
        $escrow = new Escrow();
        $escrow->checkPayment($orderInfo);
    }

    if (!$orderInfo) {
        $sError = true;
        return;
    }

    $rlSmarty->assign('step', $step);
    $rlSmarty->assign_by_ref('orderInfo', $orderInfo);

    if (isset($_GET['completed'])) {
        $reefless->loadClass('Notice');
        $message = $orderInfo['Status'] == 'paid' ? $lang['shc_done_notice'] : $lang['shc_waiting_payment'];
        $rlNotice->saveNotice($message);
    }
    if (isset($_GET['canceled'])) {
        $errors[] = $lang['payment_canceled'];
    }

    if ($step == 'checkout') {
        $bread_crumbs[] = array(
            'name' => $lang['checkout'],
        );

        $ordersObj->checkout($orderInfo);
        return;
    }

    $bread_crumbs[] = array(
        'name' => $lang['shc_order_details'] . ' (#' . $orderInfo['Order_key'] . ')',
    );

    $page_info['name']  .= ' (#' . $orderInfo['Order_key'] . ')';
    $page_info['title'] .= ' (#' . $orderInfo['Order_key'] . ')';

    if (!isset($lang['shc_dealer'])) {
        $lang = array_merge($lang, $rlShoppingCart->getPhrases(['add_listing', 'shopping_cart', 'my_shopping_cart']));
    }

    if (isset($_GET['print'])) {
        PrintOrder::print();
    }

    $printUrl = $reefless->getPageUrl('shc_purchases') . '?item=' . $itemID . '&print';

    $navIcons[] = '<a title="' . $lang['print_page'] . '" ref="nofollow" class="print" href="' . $printUrl . '"> <span></span> </a>';
    $rlSmarty->assign_by_ref('navIcons', $navIcons);
} else {
    $pInfo['current'] = (int) $_GET['pg'];
    $page = $pInfo['current'] ? $pInfo['current'] - 1 : 0;

    $start = $page * $config['shc_orders_per_page'];
    $limit = $config['shc_orders_per_page'];

    $orders = $ordersObj->getMyOrders($limit, $start);

    $pInfo['calc'] = $ordersObj->getTotalRows();
    $rlSmarty->assign_by_ref('pInfo', $pInfo);

    $rlHook->load('phpShcPurchasesBottom');

    $rlSmarty->assign_by_ref('orders', $orders);
}
