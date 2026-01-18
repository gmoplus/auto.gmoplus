<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: YOOKASSACONTROLLER.PHP
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

use Illuminate\Http\Request;
use YooKassa\Client;

class YookassaController extends BaseController
{
    public function __construct()
    {
        rl('reefless')->loadClass('YandexKassaGateway', null, 'yandexKassa');
        rl('YandexKassaGateway')->setTestMode($GLOBALS['config']['yandexKassa_test_mode'] ? true : false);
    }

    /**
     * Initialize payment library
     */
    public function init()
    {
        require_once RL_PLUGINS . 'yandexKassa' . RL_DS . 'vendor' . RL_DS . 'autoload.php';

        // Update settings for mobile app Yookassa
        $GLOBALS['config']['yandexKassa_api_id'] = $GLOBALS['config']['app_yookassa_store_id'];
        $GLOBALS['config']['yandexKassa_secret_key'] = $GLOBALS['config']['app_yookassa_secret_key'];
        $GLOBALS['config']['system_currency_code'] = $GLOBALS['config']['app_manager_billing_currency'];

        rl('reefless')->loadClass('Payment');
        rl('reefless')->loadClass('YandexKassa', null, 'yandexKassa');

        rl('YandexKassa')->initGateway();

        $this->client = new Client();
        $this->client->setAuth($GLOBALS['config']['app_yookassa_store_id'], $GLOBALS['config']['app_yookassa_secret_key']);
    }


    /**
     * Start payment process
     *
     * @return array
     */
    public function validateYookassaTransaction()
    {
        $payment = array(
            'service' => $_POST['service'],
            'tracking_id' => $_POST['tracking_id'],
            'type_method' => $_POST['type_method'],
            'title' => rl('Valid')->xSql($_POST['title']),
            'plan' => (int) $_POST['plan'], // plan id
            'id' => (int) $_POST['id'], // item id
            'amount' => (double) $_POST['amount'],
            'featured' => (int) $_POST['featured'],
            'subscription' => (int) $_POST['subscription'],
            'gateway' => rl('Valid')->xSql($_POST['gateway']),
        );

        $request = array(
            'payment_token' => $payment['tracking_id'],
            'description' => $payment['title'],
            'amount' => array(
                'value' => number_format($payment['amount'], 2, '.', ''),
                'currency' => $GLOBALS['config']['app_manager_billing_currency'],
                // 'capture' => true,
            ),
        );

        $txn_id = uniqid('', true);

        $responseData = $this->client->createPayment($request, $txn_id);
        rl('Payment')->clear();

        $callback_method = '';
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
        rl('Payment')->setOption('plan_key', $plan_key ? $plan_key : '');
        rl('Payment')->setOption('account_id', (int) $_POST['account_id']);
        rl('Payment')->setOption('callback_class', $callback_class);
        rl('Payment')->setOption('callback_method', $callback_method);
        rl('Payment')->setGateway('yandexKassa');

        rl('Payment')->createTransaction();
        
        if ($responseData->_status == 'pending' || $responseData->_status == 'succeeded' || $responseData->_status == 'waiting_for_capture') {
            rl('YandexKassaGateway')->updateTransaction(array(
                'Txn_ID' => $responseData->_id,
                'Payment_ID' => $responseData->_id,
                'Item_data' => rl('Payment')->buildItemData(true),
            ));

            $response['success']['id'] = rl('Payment')->getTransactionID();
            $response['success']['txn_id'] = $responseData->_id;

            if ($responseData->_confirmation) {
                $response['success']['confirmation_url'] = $responseData->_confirmation->_confirmationUrl;
            }
            else {
                if ($responseData->_status == 'waiting_for_capture') {
                    $txn_info = rl('YandexKassaGateway')->getTransactionByReference(rl('Db')->getOne('Txn_ID', "`ID` = '{$response['success']['id']}'", 'transactions'));
                    $items = explode("|", base64_decode($txn_info['Item_data']));

                    $result = rl('YandexKassaGateway')->confirm($txn_info, $items, $txn_info['Total']);

                    if ($result) {
                        $response['status'] = 'complete';
                    }
                }
                else {
                    $response['status'] = 'complete';
                }

                $response['callback_class'] = $callback_class;
                $response['callback_method'] = $callback_method;
            }

        } else {
            $response['error'] = true;
        }

        return $response;
    }

    /**
     * Confirm payment process
     *
     * @return array
     */
    public function confirmYookassaPayment()
    {
        $id = $_POST['id'];
        $txn_id = $_POST['txn_id'];
        
        $idempotenceKey = uniqid('', true);

        try {
            $txn_info = rl('YandexKassaGateway')->getTransactionByReference(rl('Db')->getOne('Txn_ID', "`ID` = '{$id}'", 'transactions'));
            $items = explode("|", base64_decode($txn_info['Item_data']));

            $res = $this->client->capturePayment(
                array(
                    'amount' => array(
                        'value' => number_format($txn_info['Total'], 2),
                        'currency' => $GLOBALS['config']['app_manager_billing_currency'],
                    ),
                ),
                $txn_id,
                $idempotenceKey
            );
            
            if ($res->_status == 'succeeded') {

                $data = array(
                    'plan_id' => $items[0],
                    'item_id' => $items[1],
                    'account_id' => $items[2],
                    'total' => $txn_info['Total'],
                    'txn_id' => (int) $id,
                    'txn_gateway' => $txn_id,
                    'params' => $items[12],
                );

                rl('Payment')->complete($data, $items[4], $items[5], $items[9] ? $items[9] : false);
                $response['status'] = 'complete';
                $response['id'] = $items[5] == 'upgradeListing' ? $txn_info['Item_ID'] : $txn_info['Plan_ID'];
                $response['callback_class'] = $items[4];
                $response['callback_method'] = $items[5];
            }
        } catch (Exception $e) {
            $GLOBALS['rlDebug']->logger("yooKassa: " . $e->getMessage());
            $response = false;
        }


        return $response;
    }

    /**
     * Get transaction details
     *
     * @param int $orderID
     * @return array
     */
    public function getTxnInfo(int $txt_id) : array
    {
        $sql = "SELECT * FROM `{db_prefix}transactions` WHERE `Txn_ID` = {$txt_id}";

        return (array) rl('Db')->getRow($sql);
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
