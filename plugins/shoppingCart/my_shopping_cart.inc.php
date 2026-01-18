<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: MY_SHOPPING_CART.INC.PHP
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
 *  Flynax Classifieds Software 2025 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

use \ShoppingCart\CartProcessing;
use \ShoppingCart\Payment;

require_once RL_PLUGINS . 'shoppingCart/static.inc.php';

if (false === IS_BOT) {
    if (isset($_REQUEST['xjxfun'])) {
        die('xajax restricted in "my_shopping_cart" controller');
    }

    $lang = array_merge($lang, $GLOBALS['rlShoppingCart']->getPhrases(['add_listing']));

    $errors = array();
    $no_access = false;

    if (defined('IS_LOGIN')) {
        unset($shc_steps['auth']);
    }

    if (!$config['shc_shipping_step']) {
        unset($shc_steps['shipping']);
    }

    $payment = new Payment();
    if (!$payment->getActiveGateways() && $config['shc_allow_cash']) {
        unset($shc_steps['checkout']);
        $rlSmarty->assign('cash_only', true);
    }

    $rlHook->load('shoppingCartProcessOrderTop', $shc_steps, $errors, $no_access);

    if (!$errors && !$no_access) {

        // Remove instance
        if (!$_POST['from_post']
            && !array_key_exists($_GET['nvar_1'], $shc_steps)
            && $_GET['nvar_1'] != 'done'
            && !$_GET['step']
            && !isset($_GET['edit'])
        ) {
            CartProcessing::removeInstance();
        }

        // Get/create CartProcessing instance
        $cartProcessing = CartProcessing::getInstance();

        $rlHook->load('shoppingCartProcessOrderInit', $cartProcessing);

        // Set default config
        $shopping_cart_config = [
            'controller' => 'my_shopping_cart',
            'pageKey' => $page_info['Key'],
            'steps' => &$shc_steps,
        ];

        $cartProcessing->setConfig($shopping_cart_config);

        // Initialize
        $cartProcessing->init($page_info);

        // Process step
        $cartProcessing->processStep();

        // Save instance
        CartProcessing::saveInstance($cartProcessing);
    }

    $rlSmarty->assign_by_ref('shc_steps', $shc_steps);
    $rlSmarty->assign('no_access', $no_access);

    // check availability of step
    if (!defined('IS_LOGIN')
        && $cartProcessing->step != 'cart'
        && $cartProcessing->step != 'auth'
    ) {
        $sError = true;
        $step = $cartProcessing->getStep('auth', 'path');
        $reefless->redirect(null, $reefless->getPageUrl('shc_my_shopping_cart', array('step' => $step)));
    }

    $rlHook->load('shoppingCartProcessOrderBottom');
}
