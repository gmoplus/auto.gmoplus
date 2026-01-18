<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLCCBILLGATEWAY.CLASS.PHP
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

class rlCcbillGateway extends rlGateway
{
    /**
     * API host
     *
     * @var string
     */
    protected $api_host;

    /**
     * Available currencies on payment server
     *
     * @var []
     */
    protected static $currencies = array(
        'AUD' => '036',
        'CAD' => '124',
        'JPY' => '392',
        'GBP' => '826',
        'USD' => '840',
        'EUR' => '978',
    );

    /**
     * Available languages on payment server
     *
     * @var []
     */
    protected static $languages = array(
        'en' => 'English',
        'es' => 'Español',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'zh' => 'Traditional Chinese',
        'pt' => 'Português Brasileiro',
        'in' => 'Hindi',
        'sv' => 'Svenska',
        'dk' => 'Dansk',
        'no' => 'Norsk',
    );

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->setTestMode($GLOBALS['config']['ccbill_test_mode'] ? true : false);
        $this->api_host = 'https://bill.ccbill.com/jpost/signup.cgi';
        if (isset($_SESSION['ccbill_txn_id'])) {
            $this->transaction_id = $_SESSION['ccbill_txn_id'];
        }
    }

    /**
     * Start payment process
     */
    public function call()
    {
        global $rlPayment, $config, $account_info, $reefless;

        // set payment options
        if (!$this->getTransactionID()) {
            $this->setTransactionID();
        }
        
        $reefless->loadClass('Subscription');
        $reefless->loadClass('Ccbill', null, 'ccbill');

        $settings = rlCcbill::getSettingsItem();
        $subscription_plan = $GLOBALS['rlSubscription']->getPlan($rlPayment->getOption('service'), $rlPayment->getOption('plan_id'));
        $currency = isset(self::$currencies[$config['system_currency_code']]) ? self::$currencies[$config['system_currency_code']] : '840';
        if (strpos($settings['Allowed_types'], ',') > 0) {
            foreach (explode(',', $settings['Allowed_types']) as $item) {
                $allowed_types[] = $item . ':' . $currency;
            }
            $subscriptionTypeId = false;
            $allowed_types = implode(',', $allowed_types);
        } else {
            $allowed_types = $settings['Allowed_types'] . ':' . $currency;
            $subscriptionTypeId = $settings['Allowed_types'];
        }
        $lang = isset(self::$languages[strtolower(RL_LANG_CODE)])
        ? self::$languages[strtolower(RL_LANG_CODE)]
        : 'English';

        $this->setOption('clientAccnum', $config['ccbill_clientAccnum']);
        $this->setOption('clientSubacc', $config['ccbill_clientSubacc']);
        $this->setOption('formName', $settings['Form']);
        $this->setOption('item_number', $rlPayment->buildItemData());
        $this->setOption('transactionId', $this->getTransactionID());
        $this->setOption('language', $lang);
        $this->setOption('allowedTypes', $allowed_types);
        if ($subscriptionTypeId) {
            $this->setOption('subscriptionTypeId', $subscriptionTypeId);
        }
        // customer info
        $this->setOption('customer_fname', $account_info['First_name']);
        $this->setOption('customer_lname', $account_info['Last_name']);
        $this->setOption('email', $account_info['Mail']);
        $this->setOption('address1', $account_info['address']);
        $this->setOption('city', $account_info['city']);
        $this->setOption('state', $account_info['state']);
        $this->setOption('zipcode', $account_info['zip_code']);
        $this->setOption('country', $account_info['country']);

        if (!$this->hasErrors()) {
            $this->updateTransaction(array(
                'Txn_ID' => $this->getTransactionID(),
                'Item_data' => $rlPayment->buildItemData(),
            ));
            $this->buildPage();
        }
    }

    /**
     * Complete payment process
     */
    public function callBack()
    {
        global $config;

        if ($GLOBALS['config']['ccbill_test_mode']) {
            $file = fopen(RL_PLUGINS . 'ccbill' . RL_DS . 'response.log', 'a');

            if ($file) {
                $line = "\n\n" . date('Y.m.d H:i:s') . ":\n";
                fwrite($file, $line);

                foreach ($_REQUEST as $p_key => $p_val) {
                    $line = "{$_SERVER['REQUEST_METHOD']}: {$p_key} => {$p_val}\n";
                    fwrite($file, $line);
                }
            }
        }
        if (isset($_REQUEST['clientAccnum'])) {
            $txn_id = $GLOBALS['rlValid']->xSql($_REQUEST['subscription_id']);
            $errors = false;

            $items = explode("|", base64_decode($_REQUEST['item_number']));

            if ($config['ccbill_clientAccnum'] != $_REQUEST['clientAccnum']
                || $config['ccbill_clientSubacc'] != $_REQUEST['clientSubacc']
            ) {
                $GLOBALS['rlDebug']->logger("CCBill: account number doesn't match {$_REQUEST['clientAccnum']},{$_REQUEST['clientSubacc']}");
                $errors = true;
            }

            if ($items) {
                if (!$errors) {
                    $subscription_id = $items[11];
                    $transaction_id = (int) $items[10];
                    $txn_info = $GLOBALS['rlDb']->getRow("SELECT * FROM `" . RL_DBPREFIX . "transactions` WHERE `ID` = '{$transaction_id}' LIMIT 1");

                    $response = array(
                        'plan_id' => $items[0],
                        'item_id' => $items[1],
                        'account_id' => $items[2],
                        'total' => $txn_info['Total'],
                        'txn_id' => $transaction_id,
                        'txn_gateway' => $txn_id,
                        'params' => $items[12],
                    );
                    $GLOBALS['rlPayment']->complete($response, $items[4], $items[5], $items[9] ? $items[9] : false);

                    if ($subscription_id) {
                        $GLOBALS['reefless']->loadClass('Subscription');
                        $subscription_info = $GLOBALS['rlSubscription']->getSubscription($subscription_id);

                        if ($subscription_info) {
                            $update = array(
                                'fields' => array(
                                    'Txn_ID' => $txn_gateway,
                                    'Count' => $subscription_info['Count'] + 1,
                                    'Status' => 'active',
                                    'Subscription_ID' => $subscr_id,
                                    'Customer_ID' => $receiver_id,
                                    'Date' => 'NOW()',

                                ),
                                'where' => array('ID' => $subscription_id),
                            );

                            $GLOBALS['rlActions']->updateOne($update, 'subscriptions');
                        }
                    }

                    // clear item data field in system transaction
                    $update = array(
                        'Item_data' => '',
                    );
                    $this->updateTransaction($update);
                }
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
        
        $GLOBALS['reefless']->loadClass('Ccbill', null, 'ccbill');

        $settings = rlCcbill::getSettingsItem();
        if ($config['ccbill_clientAccnum']
            && $config['ccbill_clientSubacc']
            && $settings['Form']
            && $settings['Allowed_types']
        ) {
            return true;
        }
        return false;
    }
}
