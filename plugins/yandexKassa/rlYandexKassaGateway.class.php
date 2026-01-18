<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLYANDEXKASSAGATEWAY.CLASS.PHP
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

use YooKassa\Client;
use ShoppingCart\Payment;

class rlYandexKassaGateway extends rlGateway
{
    /**
     * API host
     *
     * @var string
     */
    public $api_host;

    /**
     * Client object
     *
     * @var object
     */
    public $client;

    /**
     * Account Settings
     *
     * @var array
     */
    public $accountSettings;

    /**
     * Conrtructor
     */
    public function __construct()
    {
        $this->setTestMode($GLOBALS['config']['yandexKassa_test_mode'] ? true : false);

        if (isset($_SESSION['yandexKassa_txn_id'])) {
            $this->transaction_id = $_SESSION['yandexKassa_txn_id'];
        }
    }

    /**
     * Initialize payment library
     */
    public function init()
    {
        global $config, $rlPayment, $rlDb, $lang;

        require_once RL_PLUGINS . 'yandexKassa' . RL_DS . 'vendor' . RL_DS . 'autoload.php';

        if ($config['shc_method'] == 'multi' && in_array($rlPayment->getOption('service'), ['shopping', 'auction'])) {
            $dealerID = $rlPayment->getOption('dealer_id');
            if ($rlDb->tableExists('shc_account_settings')) {
                $GLOBALS['reefless']->loadClass('ShoppingCart', null, 'shoppingCart');
                $this->accountSettings = $GLOBALS['rlShoppingCart']->getAccountOptions($dealerID);
            } else {
                $fields = [
                    'yandexKassa_enable', 
                    'yandexKassa_api_id', 
                    'yandexKassa_secret_key', 
                    'yandexKassa_account_id', 
                    'yandexKassa_payment_method',
                    'yandexKassa_saved_card'
                ];
                $this->accountSettings = $rlDb->fetch($fields, array('ID' => $dealerID), null, 1, 'accounts', 'row');
            }

            if (!$config['shc_escrow'] && !$config['shc_commission_enable'] && $config['shc_commission'] <= 0) {
                if (empty($this->accountSettings['yandexKassa_api_id']) || empty($this->accountSettings['yandexKassa_secret_key'])) {
                    $this->errors[] = $lang['yandexKassa_empty_seller_id'];
                }
                $config['yandexKassa_api_id'] = $this->accountSettings['yandexKassa_api_id'];
                $config['yandexKassa_secret_key'] = $this->accountSettings['yandexKassa_secret_key'];
                if ($this->accountSettings['yandexKassa_payment_method']) {
                    $config['yandexKassa_payment_method'] = $this->accountSettings['yandexKassa_payment_method'];
                }
            }

            if ($config['shc_escrow'] && empty($this->accountSettings['yandexKassa_saved_card'])) {
                $this->errors[] = $lang['yandexKassa_empty_seller_id'];
            }
        }

        $this->client = new Client();
        $this->client->setAuth($config['yandexKassa_api_id'], $config['yandexKassa_secret_key']);
    }

    /**
     * Start payment process
     */
    public function call()
    {
        global $config, $rlPayment, $errors, $lang, $account_info;

        // set payment options
        if (!$this->getTransactionID()) {
            $this->setTransactionID();
        }
        $this->init();

        $method = $config['yandexKassa_payment_method'];

        if (in_array($method, array('mobile_balance', 'alfabank'))) {
            $type = 'external';
        } else {
            $type = 'redirect';
        }

        $phone = $_POST['yandexKassa_phone'];
        $txn_id = uniqid('', true);
        
        if ($type == 'external' && empty($phone)) {
            $this->errors[] = $lang['yandexKassa_phone_error'];
        }

        $request = array(
            'amount' => array(
                'value' => number_format($rlPayment->getOption('total'), 2, '.', ''),
                'currency' => $config['system_currency_code'],
                'capture' => false,
            ),
        );

        if (!empty($method) && $method != 'all') {
            $request['payment_method_data'] = array(
                'type' => $method,
            );
            
            if ($method == 'qiwi') {
                $request['payment_method_data']['phone'] = $config['yandexKassa_qiwi_phone'];
            }
            
            if ($method == 'alfabank') {
                $request['payment_method_data']['login'] = $phone;
            }
        }

        $request['description'] = $rlPayment->getOption('item_name');

        if ($type == 'external') {
            if ($method == 'alfabank') {
                $request['confirmation'] = array(
                    'type' => $type
                );
            } else {
                $request['confirmation'] = array(
                    'type' => $type,
                    'phone' => $phone,
                );
            }
        } else {
            $separator = $config['mod_rewrite'] ? '?' : '&';
            $return_url = $rlPayment->getNotifyURL() . $separator . 'gateway=yandexKassa&txn_id=' . $this->getTransactionID();
            $request['confirmation'] = array(
                'type' => $type,
                'return_url' => $return_url,
            );
        }

        $itemID = $rlPayment->getOption('item_id');

        // yookassa split payment
        if (in_array($rlPayment->getOption('service'), ['shopping', 'auction']) 
            && $config['shc_method'] == 'multi' 
            && $config['shc_commission_enable'] 
            && $config['shc_commission'] > 0
            && !$config['shc_escrow']
        ) {
            if (empty($this->accountSettings['yandexKassa_account_id'])) {
                $this->errors[] = $lang['yandexKassa_empty_seller_id'];
            }
            $orderInfo = $GLOBALS['rlDb']->fetch('*', array('ID' => $itemID), null, 1, 'shc_orders', 'row');
            $feeTotal = (float) $orderInfo['Commission_total'];

            $total = $rlPayment->getOption('total') - $feeTotal;
            $request['transfers'] = [
                [
                    'account_id' => $this->accountSettings['yandexKassa_account_id'],
                    'amount' => [
                        'value' => number_format($total, 2, '.', ''),
                        'currency' => $config['system_currency_code'],
                    ],
                    'platform_fee_amount' => [
                        'value' => number_format($feeTotal, 2, '.', ''),
                        'currency' => $config['system_currency_code'],
                    ],
                    'metadata' => [
                        'order_id' => $this->getTransactionID(),
                    ],
                ]
            ];
        }

        // yookassa safe deal payment
        if (in_array($rlPayment->getOption('service'), ['shopping', 'auction'])
            && $config['shc_escrow']
            && Payment::isEscrow('yandexKassa')
        ) {
            if (!$config['shc_debug_escrow']) {
                $orderInfo = $GLOBALS['rlDb']->fetch('*', array('ID' => $itemID), null, 1, 'shc_orders', 'row');
                $feeTotal = (float) $orderInfo['Commission_total'];

                $dealID = $this->createDeal($itemID);

                $total = $rlPayment->getOption('total') - $feeTotal;
                $total = number_format($total, 2, '.', '');

                $request['deal'] = [
                    'id' => $dealID,
                    'settlements' => array(
                        array(
                            'type' => 'payout',
                            'amount' => array(
                                'value' => $total,
                                'currency' => $config['system_currency_code']
                            )
                        )
                    )
                ];
            } else {
                $GLOBALS['rlShoppingCart']->getEscrowTest()->initSafeDeal($itemID);
            }
        }

        $request['metadata'] = [
            'cms_name' => 'flynax',
            'order_id' => $config['shc_escrow'] ? $itemID : $this->getTransactionID(),
        ];

        if (!$this->errors) {
            try {

                // Include checks in the past payment or not
                $res_me = $this->client->me();
                if ($res_me['fiscalization_enabled']) {

                    $request['receipt'] = array(
                        "customer" => array(
                            "full_name" => $account_info['Full_name'] ? $account_info['Full_name'] : $account_info['Username'],
                            'email' => $account_info['Mail'],
                            // 'phone' => '+79000000000',
                        ),
                        "items" => array(
                            array(
                                "description" => $request['description'],
                                "quantity" => "1.00",
                                "amount" => array(
                                    "value" => $total ? $total : number_format($rlPayment->getOption('total'), 2, '.', ''),
                                    "currency" => $config['system_currency_code']
                                ),
                                "vat_code" => "2",
                                "payment_mode" => "full_prepayment",
                                "payment_subject" => "commodity"
                            )
                        )
                    );
                }

                $response = $this->client->createPayment($request, $txn_id);

                if ($response->_status == 'pending' && $response->_id) {
                    $this->updateTransaction(array(
                        'Txn_ID' => $this->getTransactionID(),
                        'Payment_ID' => $response->_id,
                        'Item_data' => $rlPayment->buildItemData(true),
                    ));

                    if ($type == 'external') {
                        $options = array(
                            'complete' => true,
                            'id' => $response->_id,
                        );
                        $GLOBALS['rlSmarty']->assign_by_ref('yandexKassa', $options);
                        $rlPayment->enableForm();
                    } else {
                        $GLOBALS['reefless']->redirect(false, $response->_confirmation->_confirmationUrl);
                    }
                } else {
                    if (isset($response['description'])) {
                        $this->errors[] = $response['description'];
                    }
                }
            } catch (Exception $e) {
                $this->errors[] = $e->getMessage();
            }
        }
    }

    /**
     * Callback payment response
     */
    public function callBack()
    {
        global $config;

        if ($config['yandexKassa_test_mode']) {
            $log = sprintf("\n%s:\n%s\n", date('Y.m.d H:i:s'), print_r($_REQUEST, true));
            file_put_contents(RL_PLUGINS . 'yandexKassa/response.log', $log, FILE_APPEND);
        }

        $error = false;
        $txn_id = $GLOBALS['rlValid']->xSql($_REQUEST['txn_id']);
        $txn_info = $this->getTransactionByReference($txn_id);
        $redirect_url = $GLOBALS['rlPayment']->getDefaultFailURL();

        if ($txn_info) {
            $this->init();
            $items = explode("|", base64_decode($txn_info['Item_data']));

            $result = $this->confirm($txn_info, $items, $txn_info['Total']);

            if ($result) {
                $redirect_url = $items[7];
            } else {
                $redirect_url = $items[6];
            }
        } else {
            $GLOBALS['rlDebug']->logger("yandexKassa: transaction not found;");
        }
        
        $GLOBALS['reefless']->redirect(false, $redirect_url);
    }

    /**
     * Confirm payment
     *
     * @param  array $txnInfo
     * @return bool
     */
    public function confirm($txnInfo, $items = array(), $total = 0)
    {
        global $rlPayment, $config;
        
        if (!$txnInfo['Payment_ID']) {
            return false;
        }

        $idempotenceKey = uniqid('', true);

        $request = array(
            'amount' => array(
                'value' => number_format($total, 2, '.', ''),
                'currency' => $config['system_currency_code'],
            ),
        );

        if (in_array($txnInfo['Service'], ['shopping', 'auction'])
            && $config['shc_escrow']
            && Payment::isEscrow('yandexKassa')
            && !$config['shc_debug_escrow']
        ) {
            $orderInfo = $GLOBALS['rlDb']->fetch('*', array('ID' => $txnInfo['Item_ID']), null, 1, 'shc_orders', 'row');
            $feeTotal = (float) $orderInfo['Commission_total'];

            $request['deal'] = array(
                'settlements' => array(
                    array(
                        'type' => 'payout',
                        'amount' => array(
                            'value' => number_format($total - $feeTotal, 2),
                            'currency' => $config['system_currency_code']
                        )
                    )
                )
            );
        }

        try {
            $response = $this->client->capturePayment(
                $request,
                $txnInfo['Payment_ID'],
                $idempotenceKey
            );

            if ($response->_status == 'succeeded') {
                $data = array(
                    'plan_id' => $items[0],
                    'item_id' => $items[1],
                    'account_id' => $items[2],
                    'total' => $total,
                    'txn_id' => (int) $items[10],
                    'txn_gateway' => $txnInfo['Payment_ID'],
                    'params' => $items[12],
                );
                $rlPayment->complete($data, $items[4], $items[5], $items[9] ? $items[9] : false);
                return true;
            }
        } catch (Exception $e) {
            $GLOBALS['rlDebug']->logger("yandexKassa: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Check settings of the gateway
     */
    public function isConfigured()
    {
        global $config;

        if ($config['yandexKassa_api_id']
            && $config['yandexKassa_secret_key']
        ) {
            return true;
        }

        return false;
    }

    /**
     * Create deal
     *
     * @since 1.2.0
     *
     * @param int $orderID
     * @return string
     */
    public function createDeal(int $orderID) : string
    {
        global $rlDb;

        $orderInfo = $rlDb->fetch('*', ['ID' => $orderID], null, 1, 'shc_orders', 'row');

        if ($orderInfo['Deal_ID']) {
             return $orderInfo['Deal_ID'];
        }

        $status = '';

        $idempotenceKey = uniqid('', true);
        try {
            $deal = $this->client->createDeal(
                array(
                    'type' => 'safe_deal',
                    'fee_moment' => 'payment_succeeded',
                    'metadata' => array(
                        'order_id' => $orderID,
                    ),
                    'description' => $GLOBALS['rlPayment']->getOption('item_name'),
                ),
                $idempotenceKey
            );

            //get deal status
            $status = $deal->getStatus();
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }

        if ($status == 'opened') {
            $update = array(
                'fields' => array(
                    'Escrow' => '1',
                    'Escrow_date' => date('Y-m-d H:i:s', strtotime($deal->getExpiresAt())),
                    'Deal_ID' => $deal->getId(),
                ),
                'where' => array('ID' => $orderID),

            );
            $GLOBALS['rlDb']->updateOne($update, 'shc_orders');

            return $deal->getId();
        }

        return '';
    }

    /**
     * Create payout to seller
     *
     * @since 1.2.0
     *
     * @param array $txnInfo
     * @param string $DealID
     * @param double $total
     * @return bool
     */
    public function payout(array $txnInfo, string $DealID, float $total) : bool
    {
        global $rlDb, $config;

        if (empty($txnInfo)) {
            $this->logger("payout method, transaction not found;");
            return false;
        }

        $accountSettings = $GLOBALS['rlShoppingCart']->getAccountOptions($txnInfo['Dealer_ID']);
        $payoutDetails = explode('|', $accountSettings['yandexKassa_saved_card']);

        if (empty($payoutDetails[2])) {
            $this->logger("payout method, seller doesn't provide payout details;");
            return false;
        }

        $idempotenceKey = uniqid('', true);
        $response = $this->client->createPayout(array(
            'amount' => array(
                'value' => number_format($total, 2),
                'currency' => $config['system_currency_code'],
            ),
            'payout_token' => $payoutDetails[2],
            'description' => $txnInfo['Item'],
            'metadata' => array(
                'order_id' => $txnInfo['Item_ID']
            ),
            'deal' => array(
                'id' => $DealID,
            ),
        ), $idempotenceKey);

        $status = $response->getStatus();
        if (!in_array($status, ['pending', 'succeeded'])) {
            $this->logger("payout method, payout response, " . print_r($response, true));
            return false;
        }

        $update = array(
            'fields' => array(
                'Payout_ID' => $response->getId(),
                'Escrow_status' => self::associateStatus($status),
            ),
            'where' => array('ID' => $txnInfo['Item_ID']),

        );

        return $rlDb->updateOne($update, 'shc_orders');
    }

    /**
     * Check status of payout
     *
     * @since 1.2.0
     *
     * @param string $payoutID
     * @param int $orderID
     * @return string
     */
    public function checkPayout(string $payoutID, int $orderID) : string
    {
        global $rlDb;

        if (!$payoutID) {
            return '';
        }

        $this->init();

        $response = $this->client->getPayoutInfo($payoutID);

        $status = $response->getStatus();
        if (!in_array($status, ['canceled', 'succeeded', 'pending'])) {
            $this->logger("payout method, check payout response, " . print_r($response, true));
            return false;
        }

        $update = array(
            'fields' => array(
                'Escrow_status' => self::associateStatus($status),
            ),
            'where' => array('ID' => $orderID),

        );

        return $rlDb->updateOne($update, 'shc_orders');
    }

    /**
     * Refund payment
     *
     * @since 1.2.0
     *
     * @param array $txnInfo
     * @return bool
     */
    public function refund(array $txnInfo) : bool
    {
        global $rlDb, $config;

        if (empty($txnInfo)) {
            $this->logger("payout method, transaction not found;");
            return false;
        }

        $idempotenceKey = uniqid('', true);
        $response = $this->client->createRefund(
            array(
                'payment_id' => $txnInfo['Payment_ID'],
                'amount' => array(
                    'value' => number_format($txnInfo['Total'], 2),
                    'currency' => $config['system_currency_code'],
                ),
                'description' => $txnInfo['Item_name'],
                'deal' => array(
                    'refund_settlements' => array(
                        array(
                            'type' => 'payout',
                            'amount' => array(
                                'value' => number_format($txnInfo['Total'], 2),
                                'currency' => $config['system_currency_code'],
                            )
                        )
                    )
                ),
            ),
            $idempotenceKey
        );

        $status = $response->getStatus();
        if ($status != 'succeeded') {
            $this->logger("payout method, refund response, " . print_r($response, true));
            return false;
        }

        $update = array(
            'fields' => array(
                'Refund_ID' => $response->getId(),
                'Escrow_status' => self::associateStatus($status),
                'Status' => 'canceled',
            ),
            'where' => array('ID' => $txnInfo['Item_ID']),
        );

        return $rlDb->updateOne($update, 'shc_orders');
    }

    /**
     * Associate status in order
     *
     * @since 1.2.0
     *
     * @param string $status
     * @return string
     */
    public static function associateStatus(string $status) : string
    {
        $statuses = [
            'pending' => 'pending',
            'succeeded' => 'confirmed',
            'canceled' => 'canceled'
        ];

        return $statuses[$status];
    }

    /**
     * Write message to log
     *
     * @since 1.2.0
     *
     * @param string $message
     * @return string
     */
    public function logger(string $message) : void
    {
        file_put_contents(RL_PLUGINS . 'yandexKassa/errors.log', "\n" .  date('Y.m.d H:i:s') . ': ' . $message, FILE_APPEND);
    }
}
