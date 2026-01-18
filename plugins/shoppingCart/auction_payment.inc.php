<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : AUCTION_PAYMENT.INC.PHP
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

use \ShoppingCart\AuctionPayment;

require_once RL_PLUGINS . 'shoppingCart/static.inc.php';

if (false === IS_BOT) {
    if (isset($_REQUEST['xjxfun'])) {
        die('xajax restricted in "auction_payment" controller');
    }

    $errors = array();
    $no_access = false;
    $lang = array_merge($lang, $rlShoppingCart->getPhrases(['my_shopping_cart']));

    // remove unavailable steps
    foreach ($shc_steps as $k => $v) {
        if (!$v['auction']) {
            unset($shc_steps[$k]);
        }
    }

    $rlHook->load('shoppingCartAuctionProcessTop', $shc_steps, $errors, $no_access);

    $rlSmarty->assign('shc_steps', $shc_steps);

    if (!$errors && !$no_access) {
        // Remove instance
        if (!$_POST['from_post']
            && !array_key_exists($_GET['nvar_1'], $shc_steps)
            && $_GET['nvar_1'] != 'done'
            && !$_GET['step']
            && !isset($_GET['edit'])
        ) {
            AuctionPayment::removeInstance();
        }

        // Get/create AuctionPayment instance
        $auctionProcessing = AuctionPayment::getInstance();

        $rlHook->load('shoppingCartAuctionProcessInit', $auctionProcessing);

        // Set default config
        $shopping_cart_config = [
            'controller' => 'shc_auction_payment',
            'pageKey' => $page_info['Key'],
            'steps' => &$shc_steps,
        ];

        $auctionProcessing->setConfig($shopping_cart_config);

        // Initialize
        $auctionProcessing->init();

        // Process step
        $auctionProcessing->processStep();

        // Save instance
        AuctionPayment::saveInstance($auctionProcessing);
    }

    $rlSmarty->assign('no_access', $no_access);

    $rlHook->load('shoppingCartAuctionProcessBottom');
}
