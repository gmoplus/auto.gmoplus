<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : ESCROW.PHP
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

namespace ShoppingCart;

use ShoppingCart\Payment;

/**
 * @since 3.1.0
 */
class Escrow
{
    /**
     * Confirm order by safe deal
     *
     * @param string $action
     * @param int $orderID
     * @param int $accountID
     * @param string $message
     * @return bool|object
     */
    public function makeAction(string $action, int $orderID, int $accountID = 0, $message = '')
    {
        global $rlDb;

        if (!$action || !$orderID || !$accountID) {
            $this->logger('No Order Action, ID or Account ID');
            return false;
        }

        $orders = new Orders();
        $orderInfo = $orders->get($orderID, true, true);

        if (!$orderInfo) {
            $this->logger('No Order information');
            return false;
        }

        $actions = [
            'confirm' => [
                'status' => 'confirmed',
                'method' => 'confirmEscrow'
            ],
            'cancel' => [
                'status' => 'canceled',
                'method' => 'cancelEscrow'
            ]
        ];

        if (!isset($actions[$action])) {
            $this->logger('Escrow action is not correct');
            return false;
        }

        if (!$orderInfo['Escrow'] || $orderInfo['Escrow_status'] != 'pending') {
            $this->logger('Escrow status is not pending');
            return false;
        }

        // Confirm in payment service
        $rlGateway = $this->getOrderGatewayObj($orderID, $orderInfo['Type'], $orderInfo['Buyer_ID']);

        if (!method_exists($rlGateway, $actions[$action]['method'])) {
            $this->logger('The method ' . $actions[$action]['method'] . ' not found in Gateway class');
            return false;
        }

        $method = $actions[$action]['method'];

        if ($rlGateway->$method($orderInfo)) {
            $this->sendNotification($action, $orderInfo, $message);

            return $rlDb->updateOne(array(
                'fields' => array(
                    'Escrow_status' => $actions[$action]['status'],
                    'Refund_reason' => $message
                ),
                'where' => array('ID' => $orderID, 'Buyer_ID' => $accountID),
            ), 'shc_orders');
        }

        $this->logger('The ' .$action . ' order failed');

        return false;

    }

    /**
     * Get payment gateway class
     *
     * @param int $orderID
     * @param string $service
     * @param int $accountID
     * @return object|\stdClass
     */
    public function getOrderGatewayObj(int $orderID, string $service, int $accountID)
    {
        global $rlDb;

        $sql = "SELECT * FROM `{db_prefix}transactions` WHERE `Item_ID` = {$orderID} ";
        $sql .= "AND `Service` = '{$service}' AND `Status` = 'paid' AND `Account_ID` = {$accountID} ";
        $sql .= "ORDER BY `Date` DESC";

        $txnInfo = $rlDb->getRow($sql);

        $sql = "SELECT * FROM `{db_prefix}payment_gateways` WHERE `Key` = '{$txnInfo['Gateway']}' ";
        $gatewayInfo = $rlDb->getRow($sql);

        $gateway = ucfirst($txnInfo['Gateway']);
        $rlGateway = self::getInstanceGateway($gateway, $gatewayInfo['Plugin']);

        if (is_object($rlGateway)) {
            return $rlGateway;
        }

        return new \stdClass();
    }

    /**
     * Send Notification to Buyer and Seller
     *
     * @param string $action
     * @param array $orderInfo
     * @param string $message
     */
    public function sendNotification(string $action, array $orderInfo, $message = '')
    {
        global $rlMail, $lang, $rlSmarty;

        $targets = ['add_listing', 'shopping_cart', 'my_shopping_cart', 'my_purchases'];
        $lang = array_merge($lang, $GLOBALS['rlShoppingCart']->getPhrases($targets));
        $rlSmarty->assign('lang', $lang);

        $rlSmarty->assign('showDigital', true);
        $rlSmarty->assign_by_ref('order_info', $orderInfo);
        $details = $rlSmarty->fetch(RL_PLUGINS . 'shoppingCart/view/order_details_mail.tpl', null, null, false);

        // send payment notification email to buyer
        $mailTpl = $rlMail->getEmailTemplate('shc_order_escrow_' . $action . '_buyer');

        $find = array('{buyer}', '{order_key}', '{details}');
        $replace = array($orderInfo['bFull_name'], $orderInfo['Order_key'], $details);

        $mailTpl['subject'] = str_replace('{order_key}', $orderInfo['Order_key'], $mailTpl['subject']);
        $mailTpl['body'] = str_replace($find, $replace, $mailTpl['body']);

        $rlMail->send($mailTpl, $orderInfo['bMail']);

        // send payment notification email to seller
        $mailTpl = $rlMail->getEmailTemplate('shc_order_escrow_' . $action . '_seller');

        $find = array('{seller}', '{order_key}', '{details}', '{reason}');
        $replace = array($orderInfo['dFull_name'], $orderInfo['Order_key'], $details, $message);

        $mailTpl['subject'] = str_replace('{order_key}', $orderInfo['Order_key'], $mailTpl['subject']);
        $mailTpl['body'] = str_replace($find, $replace, $mailTpl['body']);

        $rlMail->send($mailTpl, $orderInfo['dMail']);
    }

    /**
     * Check payout status in payment
     *
     * @param array $orderInfo
     * @return void
     */
    public function checkPayment(array $orderInfo) : void
    {
        $rlGateway = $this->getOrderGatewayObj($orderInfo['ID'], $orderInfo['Type'], $orderInfo['Buyer_ID']);

        if (!method_exists($rlGateway, 'checkPayout')) {
            $this->logger('The method checkPayout not found in Gateway class');
            return;
        }

        $rlGateway->checkPayout($orderInfo['Payout_ID'], $orderInfo['ID']);
    }

    /**
     * Create instance of gateway object
     *
     * @param  string $gateway
     * @param  string $plugin
     * @return object
     */
    public static function getInstanceGateway($gateway = '', $plugin = '')
    {
        if (!$gateway) {
            return new \stdClass();
        }
        $className = ucfirst($gateway);
        $GLOBALS['reefless']->loadClass($className, null, $plugin);
        return $GLOBALS['rl' . $className];
    }

    /**
     * Save error in log
     *
     * @param $error
     */
    public function logger($error = '')
    {
        $log = sprintf("\n%s:\n%s", date('Y.m.d H:i:s'), $error);
        file_put_contents(RL_PLUGINS . 'shoppingCart/errors.log', $log, FILE_APPEND);
    }
}
