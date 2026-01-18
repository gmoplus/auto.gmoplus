<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: PAYPALREST.GATEWAY.PHP
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

use YooKassa\Client;

class rlYooKassaGateway extends rlGateway
{
    /**
     * Client object
     *
     * @var object
     */
    public $client;

    /**
     * Conrtructor
     */
    public function __construct()
    {
        $this->setTestMode($GLOBALS['config']['yandexKassa_test_mode'] ? true : false);
    }

    /**
     * Initialize payment library
     */
    public function init()
    {
        global $config, $rlPayment, $rlDb, $lang;

        require_once RL_PLUGINS . 'yandexKassa' . RL_DS . 'vendor' . RL_DS . 'autoload.php';

        $GLOBALS['reefless']->loadClass('Payment');
        $GLOBALS['reefless']->loadClass('YandexKassa', null, 'yandexKassa');
        $GLOBALS['rlYandexKassa']->initGateway();

        $this->client = new Client();
        $this->client->setAuth($config['android_yookassa_store_id'], $config['android_yookassa_key_site']);
    }

    /**
     * Start payment process
     *
     * @return array
     */
    public function validateYookassaTransaction()
    {
        global $config, $reefless, $rlDb, $rlPayment, $errors, $lang, $rlValid;

        $payment = array(
            'service' => $_REQUEST['payment_service'],
            'tracking_id' => $_REQUEST['payment_tracking_id'],
            'type_method' => $_REQUEST['payment_type_method'],
            'item' => $rlValid->xSql($_REQUEST['payment_item']),
            'title' => $rlValid->xSql($_REQUEST['payment_title']),
            'plan' => (int) $_REQUEST['payment_plan'], // plan id
            'id' => (int) $_REQUEST['payment_id'], // item id
            'amount' => (double) $_REQUEST['payment_amount'],
            'featured' => (int) $_REQUEST['payment_featured'],
            'subscription' => (int) $_REQUEST['subscription'],
            'gateway' => $rlValid->xSql($_REQUEST['payment_gateway']),
        );

        $request = array(
            'payment_token' => $payment['tracking_id'],
            'description' => $payment['title'],
            'amount' => array(
                'value' => number_format($payment['amount'], 2, '.', ''),
                'currency' => $config['android_billing_currency'],
                // 'capture' => true,
            ),
        );

        $txn_id = uniqid('', true);

        $response = $this->client->createPayment($request, $txn_id);
        $GLOBALS['rlPayment']->clear();

        $callback_method = '';
        switch ($payment['service']) {
            case 'listings':
            case 'listing':
                $callback_method = 'upgradeListing';
                break;

            case 'upgradePackage':
                $callback_method = 'upgradePackage';
                break;

            case 'purchasePackage':
                $callback_method = 'purchasePackage';
                break;
        }

        $GLOBALS['rlPayment']->setOption('service', $payment['item']);
        $GLOBALS['rlPayment']->setOption('total', $payment['amount']);
        $GLOBALS['rlPayment']->setOption('plan_id', $payment['plan']);
        $GLOBALS['rlPayment']->setOption('item_id', $payment['id']);
        $GLOBALS['rlPayment']->setOption('item_name', $payment['title']);
        $GLOBALS['rlPayment']->setOption('plan_key', 'listing_plans+name+' . $rlDb->getOne('Key', "`ID` = '{$payment['plan']}'", 'listing_plans'));
        $GLOBALS['rlPayment']->setOption('account_id', (int) $_REQUEST['account_id']);
        $GLOBALS['rlPayment']->setOption('callback_class', 'rlListings');
        $GLOBALS['rlPayment']->setOption('callback_method', $callback_method);
        $GLOBALS['rlPayment']->setGateway($payment['gateway']);
        $GLOBALS['rlPayment']->createTransaction();

        if ($response->_status == 'pending' || $response->_status == 'waiting_for_capture') {
            $this->updateTransaction(array(
                'Txn_ID' => $response->_id,
                'Payment_ID' => $response->_id,
                'Item_data' => $rlPayment->buildItemData(true),
            ));

            $out['success']['id'] = $GLOBALS['rlPayment']->getTransactionID();
            $out['success']['txn_id'] = $response->_id;

            if ($response->_status == 'waiting_for_capture') {
                $out['status'] = 'complete';
            }
            else {
                $out['success']['confirmation_url'] = $response->_confirmation->_confirmationUrl;
            }

        } else {
            $out['error'] = true;
        }

        return $out;
    }

    /**
     * Confirm payment process
     *
     * @return array
     */
    public function confirmYookassaPayment()
    {
        global $config, $rlPayment, $errors, $lang, $rlValid;

        $GLOBALS['response_type'] = "json";
        $id = $_REQUEST['id'];
        $txn_id = $_REQUEST['txn_id'];

        $idempotenceKey = uniqid('', true);

        try {
            $txn_info = $this->getTransactionByReference($GLOBALS['rlDb']->getOne('Txn_ID', "`ID` = '{$id}'", 'transactions'));
            $items = explode("|", base64_decode($txn_info['Item_data']));

            $response = $this->client->capturePayment(
                array(
                    'amount' => array(
                        'value' => number_format($txn_info['Total'], 2),
                        'currency' => $config['android_billing_currency'],
                    ),
                ),
                $txn_id,
                $idempotenceKey
            );

            if ($response->_status == 'succeeded') {

                $data = array(
                    'plan_id' => $items[0],
                    'item_id' => $items[1],
                    'account_id' => $items[2],
                    'total' => $txn_info['Total'],
                    'txn_id' => (int) $id,
                    'txn_gateway' => $txn_id,
                    'params' => $items[12],
                );

                $GLOBALS['rlPayment']->complete($data, $items[4], $items[5], $items[9] ? $items[9] : false);
                $out['status'] = 'complete';
                $out['method'] = $items[5];
                $out['id'] = $items[5] == 'upgradeListing' ? $txn_info['Item_ID'] : $txn_info['Plan_ID'];
            }
        } catch (Exception $e) {
            $GLOBALS['rlDebug']->logger("yooKassa: " . $e->getMessage());
            $out = false;
        }
        return $out;
    }

    /**
     * Call payment response
     */
    public function call()
    {
    }

    /**
     * Callback payment response
     */
    public function callBack()
    {
    }

    /**
     * Check settings of the gateway
     */
    public function isConfigured()
    {
    }
}
