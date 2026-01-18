<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: MY_ITEMS_SOLD.INC.PHP
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2024 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

use \ShoppingCart\MyAuctions;
use \ShoppingCart\PrintOrder;

if (!defined('IS_LOGIN')) {
    $reefless->redirect(false, $reefless->getPageUrl('login'));
}

require_once RL_PLUGINS . 'shoppingCart/static.inc.php';

$reefless->loadClass('Notice');
$reefless->loadClass('Listings');
$reefless->loadClass('ShoppingCart', null, 'shoppingCart');

$itemID = intval($_REQUEST['item']);
$pageMode = $_GET['nvar_1'] ? $_GET['nvar_1'] : $_GET['mode'];
$rlSmarty->assign_by_ref('auction_mod', $pageMode);
$myAuctionObj = new MyAuctions();

if ($itemID) {
    $lang = array_merge($lang, $rlShoppingCart->getPhrases(['add_listing', 'shopping_cart', 'listing_details', 'my_purchases', 'my_shopping_cart']));

    // get auction info
    if ($pageMode == 'live') {
        $auction_info = $myAuctionObj->getAuctionLiveInfo($itemID, true);
    } else {
        $lang['pay_date'] = $rlLang->getPhrase('ext_payed', RL_LANG_CODE, false, true);
        $auction_info = $myAuctionObj->getAuctionInfo($itemID, true);
    }
    // get my bids
    $auction_info['bids'] = $myAuctionObj->getMyBids($pageMode == 'live' ? $auction_info['ID'] : $auction_info['Item_ID'], $pageMode);

    $rlSmarty->assign_by_ref('auction_info', $auction_info);

    // add bread crumbs item
    unset($bread_crumbs[count($bread_crumbs) - 1]);

    $bread_crumbs[] = array(
        'name' => $GLOBALS['lang']['pages+name+shc_auctions'],
        'title' => $GLOBALS['lang']['pages+name+shc_auctions'],
        'path' => $pages['shc_auctions'] . ($pageMode ? '/' . $pageMode : ''),
    );

    $bread_crumbs[] = array(
        'name' => $auction_info['Txn_ID'] ? $lang['shc_auction_number'] . $auction_info['Txn_ID'] : $lang['shc_auction_details'],
    );

    if (isset($_GET['print'])) {
        PrintOrder::print();
    }

    $printUrl = $reefless->getPageUrl('shc_auctions') . '?item=' . $itemID . '&print';

    $navIcons[] = '<a title="' . $lang['print_page'] . '" ref="nofollow" class="print" href="' . $printUrl . '"> <span></span> </a>';
    $rlSmarty->assign_by_ref('navIcons', $navIcons);
} else {
    $tabs = array(
        'winnerbids' => array(
            'key' => 'winnerbids',
            'name' => $lang['shc_winner_bids'],
        ),
        'live' => array(
            'key' => 'live',
            'name' => $lang['shc_member_bids'],
        ),
        'dontwin' => array(
            'key' => 'dontwin',
            'name' => $lang['shc_dont_win'],
        ),
    );
    $rlSmarty->assign_by_ref('tabs', $tabs);
}
