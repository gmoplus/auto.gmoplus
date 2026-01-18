<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLCOINGATEGATEWAY.CLASS.PHP
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

class rlCoinGateGateway extends rlGateway
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        if (isset($_SESSION['coinGate_txn_id'])) {
            $this->transaction_id = $_SESSION['coinGate_txn_id'];
        }
    }

    /**
     * Initialize CoinGate library
     */
    public function init()
    {
        global $config, $rlPayment, $rlDb;

        require_once RL_PLUGINS . '/coinGate/vendor/autoload.php';

        if (defined('REALM') && $_POST['post_config']['coinGate_auth_token']) {
            $environment = $_POST['post_config']['coinGate_test_mode'] ? 'sandbox' : 'live';
            $auth_token = $_POST['post_config']['coinGate_auth_token'];
        } else {
            $environment = $config['coinGate_test_mode'] ? 'sandbox' : 'live';
            if ($config['shc_method'] == 'multi' && in_array($rlPayment->getOption('service'), ['shopping', 'auction'])) {
                $dealerID = $rlPayment->getOption('dealer_id');
                if ($rlDb->tableExists('shc_account_settings')) {
                    $GLOBALS['reefless']->loadClass('ShoppingCart', null, 'shoppingCart');
                    $options = $GLOBALS['rlShoppingCart']->getAccountOptions($dealerID);
                } else {
                    $options = $rlDb->fetch('*', array('ID' => $dealerID), null, 1, 'accounts', 'row');
                }

                $auth_token = $options['coinGate_auth_token'];
                $config['coinGate_receive_currency'] = $options['coinGate_receive_currency'] ?: $options['shc_coinGate_receive_currency'];
            } else {
                $auth_token = $config['coinGate_auth_token'];
            }
        }

        \CoinGate\CoinGate::config(array(
            'environment' => $environment,
            'auth_token' => $auth_token,
            'curlopt_ssl_verifypeer' => true,
        ));
    }

    /**
     * Start payment process
     */
    public function call()
    {
        global $rlPayment, $config, $lang, $reefless;

        // set payment options
        if (!$this->getTransactionID()) {
            $this->setTransactionID();
        }

        $this->init();

        $request = array(
            'order_id' => $this->getTransactionID(),
            'price_amount' => $rlPayment->getOption('total'),
            'price_currency' => $config['system_currency_code'],
            'receive_currency' => $config['coinGate_receive_currency'],
            'callback_url' => $rlPayment->getNotifyURL() . '?gateway=coinGate',
            'cancel_url' => $rlPayment->getOption('cancel_url'),
            'success_url' => $rlPayment->getOption('success_url'),
            'title' => $rlPayment->getOption('item_name'),
            'description' => $rlPayment->getOption('item_name'),
        );

        $link = $reefless->getPageUrl('contact_us');
        $link = '<a href="' . $link . '">' . $lang['coinGate_contact'] . '</a>';
        $request_error = str_replace('[contact]', $link, $lang['coinGate_request_error']);

        try {
            $response = \CoinGate\Merchant\Order::createOrFail($request);

            if ($response->payment_url) {
                $this->updateTransaction(array(
                    'Txn_ID' => $response->order_id,
                    'Token' => $response->token,
                    'Item_data' => $rlPayment->buildItemData(true),
                ));

                $reefless->redirect(false, $response->payment_url);
            } else {
                $this->errors[] = $response->error ? $response->error : $request_error;
            }
        } catch (\CoinGate\APIError\APIError $e) {
            $this->errors[] = $request_error;
        }
    }

    /**
     * Callback payment response
     */
    public function callBack()
    {
        global $config, $rlDebug;

        $errors = false;

        header("HTTP/1.1 200 OK");

        if ($config['coinGate_test_mode']) {
            $log = sprintf("\n%s:\n%s\n", date('Y.m.d H:i:s'), print_r($_REQUEST, true));
            file_put_contents(RL_PLUGINS . 'coinGate/response.log', $log, FILE_APPEND);
        }

        $txn_gateway = $GLOBALS['rlValid']->xSql($_REQUEST['order_id']);

        if ($txn_gateway) {
            $pstatus = true;
            if (version_compare($config['rl_version'], '4.6.2') < 0) {
                $pstatus = false;
            }

            $txn_info = $this->getTransactionByReference($txn_gateway, $pstatus);

            if ($txn_info) {
                $items = explode("|", base64_decode($txn_info['Item_data']));

                if ($txn_info['Token'] != $_REQUEST['token']) {
                    $rlDebug->logger("coinGate: the token invalid - ({$_REQUEST['token']})");
                    $errors = true;
                }

                $statuses = array('paid', 'confirming');
                if (!in_array($_REQUEST['status'], $statuses)) {
                    $rlDebug->logger("coinGate: the payment has been canceled - ({$_REQUEST['status']})");
                    $errors = true;
                }

                if (!$errors) {
                    $response = array(
                        'plan_id' => $items[0],
                        'item_id' => $items[1],
                        'account_id' => $items[2],
                        'total' => (float) $_REQUEST['price_amount'],
                        'txn_id' => (int) $items[10],
                        'txn_gateway' => $txn_gateway,
                        'params' => $items[12],
                    );

                    $GLOBALS['rlPayment']->complete($response, $items[4], $items[5], $items[9] ? $items[9] : false);
                }
            } else {
                $rlDebug->logger("coinGate: transaction doesn't exists - ({$txn_gateway})");
            }
        }
    }

    /**
     * Check settings of the gateway
     *
     * @return bool
     */
    public function isConfigured()
    {
        global $config;

        if ($config['coinGate_auth_token']
            && $config['coinGate_receive_currency']
        ) {
            return true;
        }
        return false;
    }
}
