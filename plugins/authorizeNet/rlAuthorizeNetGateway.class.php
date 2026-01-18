<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.7.2
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: RLAUTHORIZENETGATEWAY.CLASS.PHP
 *
 *	The software is a commercial product delivered under single, non-exclusive,
 *	non-transferable license for one domain or IP address. Therefore distribution,
 *	sale or transfer of the file in whole or in part without permission of Flynax
 *	respective owners is considered to be illegal and breach of Flynax License End
 *	User Agreement.
 *
 *	You are not allowed to remove this information from the file without permission
 *	of Flynax respective owners.
 *
 *	Flynax Classifieds Software 2022 |  All copyrights reserved.
 *
 *	https://www.flynax.com/
 *
 ******************************************************************************/

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

class rlAuthorizeNetGateway extends rlGateway
{
    /**
     * Accept Suite API host
     *
     * @var string
     */
    protected $api_host;

    /**
     * Merchant Authentication object
     *
     * @var object
     */
    protected $merchantAuthentication;

    /**
     * Available methods
     *
     * @var array
     */
    protected $methods = array('SIM', 'AIM', 'ARB');

    /**
     * Method of gateway
     *
     * @var string
     */
    protected $method;

    /**
     * Environment mode
     *
     * @var string
     */
    protected $environment = '';

    /**
     * Initialize payment process
     */
    public function init()
    {
        global $config, $rlDb, $rlPayment;

        require_once RL_PLUGINS . 'authorizeNet/vendor/autoload.php';

        $this->api_host = $config['authorizeNet_sandbox'] 
        ? 'https://test.authorize.net/payment/payment' 
        : 'https://accept.authorize.net/payment/payment';

        if (!function_exists('curl_init')) {
            throw new Exception('AuthorizeNetSDK needs the CURL PHP extension.');
        }
        if (!function_exists('simplexml_load_file')) {
            throw new Exception('The AuthorizeNet SDK requires the SimpleXML PHP extension.');
        }

        $this->merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $this->merchantAuthentication->setName($config['authorizeNet_account_id']);
        $this->merchantAuthentication->setTransactionKey($config['authorizeNet_transaction_key']);
        $this->environment = $config['authorizeNet_sandbox'] ? 'SANDBOX' : 'PRODUCTION';

        define("AUTHORIZENET_LOG_FILE", RL_PLUGINS . 'authorizeNet/log');

        $GLOBALS['reefless']->loadClass('Subscription');
    }

    /**
     * Start payment process
     */
    public function call()
    {
        if ($_POST['form'] == 'checkout') {
            $this->init();

            // set payment options
            if (!$this->getTransactionID()) {
                $this->setTransactionID();
            }

            $this->setMethod($GLOBALS['config']['authorizeNet_type']);

            if (!$this->getMethod()) {
                $this->errors[] = $GLOBALS['lang']['aNet_method_no_selected'];
                return false;
            }
            if ($GLOBALS['rlPayment']->isRecurring()) {
                $this->setMethod('ARB');
                $this->_callARB();
            } else {
                eval('return $this->_call' . strtoupper($this->getMethod()) . '();');
            }
        }
    }

    /**
     * Complate payment process (actual only for SIM method)
     */
    public function callBack()
    {
        global $rlDebug, $config;

        if (!isset($_REQUEST['x_trans_id'])) {
            return;
        }

        if ($config['authorizeNet_sandbox']) {
            $log = sprintf("\n%s:\n%s\n", date('Y.m.d H:i:s'), print_r($_REQUEST, true));
            file_put_contents(RL_PLUGINS . 'authorizeNet/response.log', $log, FILE_APPEND);
        }

        if (isset($_REQUEST['reference'])) {
            $this->init();
            $txn_id = $GLOBALS['rlValid']->xSql($_REQUEST['reference']);
            $txn_gateway = $GLOBALS['rlValid']->xSql($_REQUEST['x_trans_id']);
            $txn_info = $this->getTransactionByReference($txn_id);

            $request = new AnetAPI\GetTransactionDetailsRequest();
            $request->setMerchantAuthentication($this->merchantAuthentication);
            $request->setTransId($txn_gateway);

            $controller = new AnetController\GetTransactionDetailsController($request);
            $response = $controller->executeWithApiResponse($config['authorizeNet_sandbox'] 
                ? \net\authorize\api\constants\ANetEnvironment::SANDBOX 
                : \net\authorize\api\constants\ANetEnvironment::PRODUCTION);

            if ($config['authorizeNet_sandbox']) {
                $log = sprintf("\n%s:\n%s\n", date('Y.m.d H:i:s'), print_r($response, true));
                file_put_contents(RL_PLUGINS . 'authorizeNet/response.log', $log, FILE_APPEND);
            }

            if ($response != null && strtoupper($response->getMessages()->getResultCode()) == 'OK') {
                $items = explode("|", base64_decode($txn_info['Item_data']));

                $data = array(
                    'plan_id' => $items[0],
                    'item_id' => $items[1],
                    'account_id' => $items[2],
                    'total' => $txn_info['Total'],
                    'txn_id' => $items[10],
                    'txn_gateway' => $txn_gateway,
                    'params' => $items[12],
                );
                $GLOBALS['rlPayment']->complete($data, $items[4], $items[5], $items[9] ? $items[9] : false);
            } else {
                $errorMessages = $response->getMessages()->getMessage();
                $rlDebug->logger("authorizeNet: code - {$errorMessages[0]->getCode()}; message - ({$errorMessages[0]->getText()})");
            }
        }
    }

    /**
     * Create simple payment
     */
    protected function _callSIM()
    {
        global $rlPayment, $config, $lang, $account_info;

        $this->init();

        $profile = $GLOBALS['rlAccount']->getProfile((int) $account_info['ID']);

        // Create order information
        $order = new AnetAPI\OrderType();
        $order->setInvoiceNumber(rand(10000, 99999));
        $order->setDescription($rlPayment->getOption('item_name'));

        $customField = new AnetAPI\UserFieldType();
        $customField->setName("reference");
        $customField->setValue($this->getTransactionID());

        //create a transaction
        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");
        $transactionRequestType->setAmount(number_format($rlPayment->getOption('total'), 2, '.', ''));
        $transactionRequestType->setOrder($order);
        $transactionRequestType->addToUserFields($customField);

        // Set Hosted Form options
        $successURL = $rlPayment->getOption('success_url');
        $cancelURL = $rlPayment->getOption('cancel_url');

        $settings = [
            'hostedPaymentButtonOptions' => "{\"text\": \"Pay\"}",
            'hostedPaymentOrderOptions' => "{\"show\": true, \"merchantName\": \"{$lang['pages+name+home']}\"}",
            'hostedPaymentReturnOptions' => "{\"url\": \"{$successURL}\", \"cancelUrl\": \"{$cancelURL}\", \"showReceipt\": true}"
        ];

        // Build transaction request
        $request = new AnetAPI\GetHostedPaymentPageRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication);
        $request->setRefId($this->getTransactionID());
        $request->setTransactionRequest($transactionRequestType);

        foreach ($settings as $sKey => $sVal) {
            $setting = new AnetAPI\SettingType();
            $setting->setSettingName($sKey);
            $setting->setSettingValue($sVal);

            $request->addToHostedPaymentSettings($setting);
        }

        //execute request
        $controller = new AnetController\GetHostedPaymentPageController($request);

        $response = $controller->executeWithApiResponse($config['authorizeNet_sandbox'] 
            ? \net\authorize\api\constants\ANetEnvironment::SANDBOX 
            : \net\authorize\api\constants\ANetEnvironment::PRODUCTION);

        if (($response != null) && (strtoupper($response->getMessages()->getResultCode()) == 'OK')) {
            $token = $response->getToken();

            $this->updateTransaction(array(
                'Txn_ID' => $this->getTransactionID(),
                'Item_data' => $rlPayment->buildItemData(false),
            ));

            $this->setOption('token', $token);
            $this->buildPage();
        } else {
            $errorMessages = $response->getMessages()->getMessage();
            $this->errors[] = 'Failed to get hosted payment page token' 
                . 'Error Code: ' . $errorMessages[0]->getCode() 
                . ', Error Message: ' . $errorMessages[0]->getText();
        }
    }

    /**
     * Create advanced payment
     */
    protected function _callAIM()
    {
        global $rlPayment, $account_info, $config;

        if ($_POST['form'] == 'checkout') {
            $this->validate();
            if (!$this->hasErrors()) {

                $form = $_POST['f'];
                $profile = $GLOBALS['rlAccount']->getProfile((int) $account_info['ID']);

                // Create the payment data for a credit card
                $creditCard = new AnetAPI\CreditCardType();
                $creditCard->setCardNumber($form['card_number']);
                $creditCard->setExpirationDate($form['exp_year'] . '-' .$form['exp_month']);
                $creditCard->setCardCode($form['card_verification_code']);

                // Add the payment data to a paymentType object
                $paymentOne = new AnetAPI\PaymentType();
                $paymentOne->setCreditCard($creditCard);

                // Create order information
                $order = new AnetAPI\OrderType();
                $order->setInvoiceNumber(rand(10000, 99999));
                $order->setDescription($rlPayment->getOption('item_name'));

                // Set the customer's Bill To address
                $customerAddress = new AnetAPI\CustomerAddressType();
                $customerAddress->setFirstName($form['first_name'] ? $form['first_name'] : $account_info['First_name']);
                $customerAddress->setLastName($form['last_name'] ? $form['last_name'] : $account_info['Last_name']);
                $customerAddress->setCompany($account_info['company']);
                $customerAddress->setAddress($form['address'] ? $form['address'] : $account_info['address']);
                $customerAddress->setCity($form['city'] ? $form['city'] : $profile['Fields']['country_level2']['value']);
                $customerAddress->setState($form['region'] ? $form['region'] : $profile['Fields']['country_level1']['value']);
                $customerAddress->setZip($form['zip'] ? $form['zip'] : $account_info['zip_code']);
                $customerAddress->setCountry($form['b_country'] ? $form['b_country'] : $profile['Fields']['country']['value']);

                // Set the customer's identifying information
                $customerData = new AnetAPI\CustomerDataType();
                $customerData->setType('individual');
                $customerData->setId($account_info['ID']);
                $customerData->setEmail($account_info['Mail']);

                // Add values for transaction settings
                $duplicateWindowSetting = new AnetAPI\SettingType();
                $duplicateWindowSetting->setSettingName('duplicateWindow');
                $duplicateWindowSetting->setSettingValue('60');

                // Create a TransactionRequestType object and add the previous objects to it
                $transactionRequestType = new AnetAPI\TransactionRequestType();
                $transactionRequestType->setTransactionType('authCaptureTransaction'); 
                $transactionRequestType->setAmount($rlPayment->getOption('total'));
                $transactionRequestType->setOrder($order);
                $transactionRequestType->setPayment($paymentOne);
                $transactionRequestType->setBillTo($customerAddress);
                $transactionRequestType->setCustomer($customerData);
                $transactionRequestType->addToTransactionSettings($duplicateWindowSetting);

                // Assemble the complete transaction request
                $request = new AnetAPI\CreateTransactionRequest();
                $request->setMerchantAuthentication($this->merchantAuthentication);
                $request->setRefId($this->getTransactionID());
                $request->setTransactionRequest($transactionRequestType);

                // Create the controller and get the response
                $controller = new AnetController\CreateTransactionController($request);

                $response = $controller->executeWithApiResponse($config['authorizeNet_sandbox'] 
                    ? \net\authorize\api\constants\ANetEnvironment::SANDBOX 
                    : \net\authorize\api\constants\ANetEnvironment::PRODUCTION);

                if ($response != null) {
                    if ($config['authorizeNet_sandbox']) {
                        $log = sprintf("\n%s:\n%s\n", date('Y.m.d H:i:s'), print_r($response, true));
                        file_put_contents(RL_PLUGINS . 'authorizeNet/response.log', $log, FILE_APPEND);
                    }

                    // Check to see if the API request was successfully received and acted upon
                    if (strtoupper($response->getMessages()->getResultCode()) == 'OK') {
                        $tresponse = $response->getTransactionResponse();

                        if ($tresponse != null && $tresponse->getMessages() != null) {
                            if ($tresponse->getResponseCode() == 1) {
                                $txn_gateway = $tresponse->getTransId();
                                $txnInfo = array(
                                    'plan_id' => $rlPayment->getOption('plan_id'),
                                    'item_id' => $rlPayment->getOption('item_id'),
                                    'account_id' => $rlPayment->getOption('account_id'),
                                    'total' => $rlPayment->getOption('total'),
                                    'txn_id' => $rlPayment->getTransactionID(),
                                    'txn_gateway' => !empty($txn_gateway) ? $txn_gateway : $this->getTransactionID(),
                                    'params' => $rlPayment->getOption('params'),
                                );

                                $rlPayment->complete(
                                    $txnInfo,
                                    $rlPayment->getOption('callback_class'),
                                    $rlPayment->getOption('callback_method'),
                                    $rlPayment->getOption('plugin') ? $rlPayment->getOption('plugin') : false
                                );
                                $GLOBALS['reefless']->redirect(false, $rlPayment->getOption('success_url'));
                                exit;
                            }
                        } else {
                            $this->errors[] = 'Transaction Failed';
                            if ($tresponse->getErrors() != null) {
                                $this->errors[] = 'Error Code: ' . $tresponse->getErrors()[0]->getErrorCode() 
                                    . ', Error Message: ' . $tresponse->getErrors()[0]->getErrorText();
                            }
                        }
                    } else {
                        $tresponse = $response->getTransactionResponse();

                        if ($tresponse != null && $tresponse->getErrors() != null) {
                            $this->errors[] = 'Error Code: ' . $tresponse->getErrors()[0]->getErrorCode() 
                                . ', Error Message: ' . $tresponse->getErrors()[0]->getErrorText();
                        } else {
                            $this->errors[] = 'Error Code: ' . $response->getMessages()->getMessage()[0]->getCode() 
                                . ', Error Message: ' . $response->getMessages()->getMessage()[0]->getText();
                        }
                    }
                } else {
                    $this->errors[] = 'No response returned';
                }
            }
        }
    }

    /**
     * Create subscription
     */
    protected function _callARB()
    {
        global $rlPayment, $account_info, $lang, $reefless, $rlDb, $rlSubscription, $config;

        if ($_POST['form'] == 'checkout') {
            $plan_info = $rlSubscription->getPlan(
                $rlSubscription->getService($rlPayment->getOption('service')), 
                $rlPayment->getOption('plan_id')
            );

            $unit = $plan_info['Period'] . 's';

            if (!in_array($unit, ['days', 'months'])) {
                $this->errors[] = $lang['authorizeNet_unit_error'];
            }

            $this->validate();
            if (!$this->hasErrors()) {
                $form = $_POST['f'];

                // Subscription Type Info
                $subscription = new AnetAPI\ARBSubscriptionType();
                $subscription->setName($plan_info['name']);

                $interval = new AnetAPI\PaymentScheduleType\IntervalAType();
                $interval->setLength($plan_info['sop_authorizenet_interval_length']);
                $interval->setUnit($unit);

                $paymentSchedule = new AnetAPI\PaymentScheduleType();
                $paymentSchedule->setInterval($interval);
                $paymentSchedule->setStartDate(new DateTime(date('Y-m-d')));
                $paymentSchedule->setTotalOccurrences($plan_info['Period_total']);
                $paymentSchedule->setTrialOccurrences(0);

                $subscription->setPaymentSchedule($paymentSchedule);
                $subscription->setAmount($rlPayment->getOption('total'));
                $subscription->setTrialAmount("0.00");

                $creditCard = new AnetAPI\CreditCardType();
                $creditCard->setCardNumber($form['card_number']);
                $creditCard->setExpirationDate($form['exp_year'] . '-' .$form['exp_month']);
                //$creditCard->setCardCode($form['card_verification_code']);

                $payment = new AnetAPI\PaymentType();
                $payment->setCreditCard($creditCard);
                $subscription->setPayment($payment);

                $order = new AnetAPI\OrderType();
                $order->setInvoiceNumber(rand(10000, 99999));
                $order->setDescription($rlPayment->getOption('item_name'));
                $subscription->setOrder($order); 

                $billTo = new AnetAPI\NameAndAddressType();
                $billTo->setFirstName($account_info['First_name']);
                $billTo->setLastName($account_info['Last_name']);

                $subscription->setBillTo($billTo);

                $request = new AnetAPI\ARBCreateSubscriptionRequest();
                $request->setmerchantAuthentication($this->merchantAuthentication);
                $request->setRefId($this->getTransactionID());
                $request->setSubscription($subscription);
                $controller = new AnetController\ARBCreateSubscriptionController($request);

                $response = $controller->executeWithApiResponse($config['authorizeNet_sandbox'] 
                    ? \net\authorize\api\constants\ANetEnvironment::SANDBOX 
                    : \net\authorize\api\constants\ANetEnvironment::PRODUCTION);

                if (($response != null) && (strtoupper($response->getMessages()->getResultCode()) == 'OK')) {
                    $txn_id = $response->getRefID();
                    $subscription_id = $response->getSubscriptionId();
                    $sql = "SELECT * FROM `{db_prefix}subscriptions` WHERE `Subscription_ID` = '" . $subscription_id . "'";
                    $subscription_info = $rlDb->getRow($sql);

                    if ($subscription_info['Subscription_ID']) {
                        $update = array(
                            'fields' => array(
                                'Date' => 'NOW()',
                                'Txn_ID' => $txn_id,
                                'Count' => $subscription_info['Count'] + 1,
                            ),
                            'where' => array(
                                'Subscription_ID' => $subscription_id,
                            ),
                        );

                        $action = $rlDb->updateOne($update, 'subscriptions');
                    } else {
                        $params = $rlPayment->getOption('params');
                        if (is_array($params)) {
                            $params = implode(',', $params);
                        }

                        $sql = "SELECT * FROM `{db_prefix}payment_gateways` WHERE `Key` = 'authorizeNet'";
                        $gateway_info = $rlDb->getRow($sql);

                        $insert = array(
                            'Service' => $rlSubscription->getService($rlPayment->getOption('service')),
                            'Account_ID' => $rlPayment->getOption('account_id'),
                            'Item_ID' => $rlPayment->getOption('item_id'),
                            'Plan_ID' => $rlPayment->getOption('plan_id'),
                            'Total' => $rlPayment->getOption('total'),
                            'Gateway_ID' => $gateway_info['ID'],
                            'Item_name' => $rlPayment->getOption('item_name'),
                            'Date' => 'NOW()',
                            'Txn_ID' => $txn_id,
                            'Subscription_ID' => $subscription_id,
                            'authorizeNet_item_data' => base64_encode($rlPayment->getOption('callback_class') . '|' .
                                $rlPayment->getOption('callback_method') . '|' .
                                ($rlPayment->getOption('plugin') ? $rlPayment->getOption('plugin') : false) . '|' .
                                $rlPayment->getTransactionID() . '|' .
                                $params
                            ),
                            'Count' => 0,
                        );

                        $action = $rlDb->insertOne($insert, 'subscriptions');
                    }

                    if ($action) {
                        $data = array(
                            'plan_id' => $rlPayment->getOption('plan_id'),
                            'item_id' => $rlPayment->getOption('item_id'),
                            'account_id' => $rlPayment->getOption('account_id'),
                            'total' => $rlPayment->getOption('total'),
                            'txn_id' => $rlPayment->getTransactionID(),
                            'txn_gateway' => $txn_id,
                            'params' => $rlPayment->getOption('params'),
                        );

                        $rlPayment->complete(
                            $data,
                            $rlPayment->getOption('callback_class'),
                            $rlPayment->getOption('callback_method'),
                            $rlPayment->getOption('plugin') ? $rlPayment->getOption('plugin') : false
                        );
                        $reefless->redirect(false, $rlPayment->getOption('success_url'));
                        exit;
                    }
                } else {
                    $errorMessages = $response->getMessages()->getMessage();
                    $this->errors[] = 'Error Code: ' . $errorMessages[0]->getCode() 
                        . ', Error Message: ' . $errorMessages[0]->getText();
                }
            }
        }
    }

    /**
     * Cancel subscription
     *
     * @param array $subscription
     */
    public function cancelSubscription(&$subscription)
    {
        global $config;

        if (!$subscription) {
            return false;
        }

        $this->init();

        $request = new AnetAPI\ARBCancelSubscriptionRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication);
        $request->setRefId($subscription['Txn_ID']);
        $request->setSubscriptionId($subscription['Subscription_ID']);

        $controller = new AnetController\ARBCancelSubscriptionController($request);

        $response = $controller->executeWithApiResponse($config['authorizeNet_sandbox'] 
            ? \net\authorize\api\constants\ANetEnvironment::SANDBOX 
            : \net\authorize\api\constants\ANetEnvironment::PRODUCTION);

        if ($response != null && strtoupper($response->getMessages()->getResultCode()) == 'OK') {
            return true;
        } else {
            $errorMessages = $response->getMessages()->getMessage();
            $GLOBALS['rlDebug']->logger("authorizeNet: " . $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText());
        }
        return false;
    }

    /**
     * Check settings of the gateway
     */
    public function isConfigured()
    {
        global $config;

        if ($config['authorizeNet_transaction_key'] && $config['authorizeNet_account_id']) {
            return true;
        }

        return false;
    }

    /**
     * Set payment method
     *
     * @param  string $name
     * @return null
     */
    public function setMethod($name = false)
    {
        if (in_array(strtoupper($name), $this->methods)) {
            $this->method = strtoupper($name);
        }
    }

    /**
     * Get payment method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Validate credit card details
     */
    public function validate()
    {
        global $lang;

        $data = $_POST['f'];

        if (empty($data['card_number'])) {
            $this->errors[] = str_replace('{field}', "<b>\"{$lang['card_number']}\"</b>", $lang['notice_field_empty']);
        }
        if (!$data['exp_month']) {
            $this->errors[] = str_replace('{field}', "<b>\"{$lang['month']}\"</b>", $lang['notice_field_empty']);
        }
        if (!$data['exp_year']) {
            $this->errors[] = str_replace('{field}', "<b>\"{$lang['year']}\"</b>", $lang['notice_field_empty']);
        }
        if (empty($data['card_verification_code'])) {
            $this->errors[] = str_replace('{field}', "<b>\"{$lang['card_verification_code']}\"</b>", $lang['notice_field_empty']);
        }
    }

    /**
     * Get subscription details
     *
     * @deprecated 2.4.0
     */
    public function getSubscription()
    {}

    /**
     * Get secret hash
     *
     * @deprecated 2.4.0
     */
    protected function getHash()
    {}
}
