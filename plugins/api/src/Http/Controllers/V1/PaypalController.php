<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: PAYPALCONTROLLER.PHP
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

namespace Flynax\Api\Http\Controllers\V1;

class PaypalController extends BaseController
{
    public function __construct()
    {
        require_once RL_CLASSES . 'rlGateway.class.php';
        rl('reefless')->loadClass('Paypal');
        PageController::getAllPages();
    }

    /**
     * call paypal
     *
     * @return array
     */
    public function callPaypal()
    {
        if (!defined('RL_TPL_BASE')) {
            define('RL_TPL_BASE', RL_URL_HOME . 'templates/' . $GLOBALS['config']['template'] . '/');
        }

        $payment = array(
            'service' => $_REQUEST['service'],
            'title' => rl('Valid')->xSql($_REQUEST['title']),
            'plan' => (int) $_REQUEST['plan'], // plan id
            'id' => (int) $_REQUEST['id'], // item id
            'amount' => (double) $_REQUEST['amount'],
            'featured' => (int) $_REQUEST['featured'],
            'subscription' => (int) $_REQUEST['subscription'],
            'gateway' => rl('Valid')->xSql($_REQUEST['gateway']),
        );

        rl('Payment')->clear();

        switch ($payment['service']) {
            case 'membership':
                $callback_method = 'upgrade';
                $callback_class = 'rlAccount';
                $table = 'membership_plans';
                $item_name = $payment['title'];
                $plan_key = 'membership_plans+name+' . rl('Db')->getOne('Key', "`ID` = '{$payment['plan']}'", 'membership_plans');
                break;

            case 'listing':
                $callback_method = 'upgradeListing';
                $callback_class = 'rlListings';
                $table = 'listing_plans';
                $item_name = $payment['title'] . ' (#' . $payment['id'] . ')';
                $plan_key = 'listing_plans+name+' . rl('Db')->getOne('Key', "`ID` = '{$payment['plan']}'", 'listing_plans');
                break;

            case 'upgradePackage':
            case 'purchasePackage':
                $callback_method = $payment['service'];
                $callback_class = 'rlListings';
                $table = 'listing_plans';
                $item_name = $payment['title'];
                $plan_key = 'listing_plans+name+' . rl('Db')->getOne('Key', "`ID` = '{$payment['plan']}'", 'listing_plans');
                $payment['service'] = 'package';
                break;

            case 'shopping':
                $callback_method = 'completeOrder';
                $callback_class = 'rlShoppingCart';
                $item_name = $payment['title'];
                rl('Payment')->setOption('plugin', 'shoppingCart');
                break;
        }

        rl('Payment')->setOption('service', $payment['service']);
        rl('Payment')->setOption('total', $payment['amount']);
        rl('Payment')->setOption('plan_id', $payment['plan']);
        rl('Payment')->setOption('item_id', $payment['id']);
        rl('Payment')->setOption('item_name', $item_name);
        rl('Payment')->setOption('plan_key', $plan_key);
        rl('Payment')->setOption('account_id', (int) $_REQUEST['account_id']);
        rl('Payment')->setOption('callback_class', $callback_class);
        rl('Payment')->setOption('callback_method', $callback_method);
        rl('Payment')->setGateway($payment['gateway']);
        rl('Payment')->createTransaction();
        $url = rl('Paypal')->call();

        return $url;
    }
}
