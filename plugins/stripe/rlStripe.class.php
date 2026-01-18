<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLSTRIPE.CLASS.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2024
 *	https://www.flynax.com
 *
 ******************************************************************************/

class rlStripe
{
    /**
     * @hook shoppingCartAccountSettings
     * @since 1.2.0
     */
    public function hookShoppingCartAccountSettings()
    {
        global $config, $rlSmarty, $plugins;

        $gateways = explode(',', $config['shc_payment_gateways']);

        if ((!$config['shc_module'] && !$config['shc_module_auction'])
            || $GLOBALS['rlPayment']->gateways['stripe']['Status'] != 'active'
            || !in_array('stripe', $gateways)
        ) {
            return;
        }
        $isSupported = false;
        
        if (version_compare($plugins['shoppingCart'], '2.1.1') > 0) {
            $isSupported = true;
        }
        $rlSmarty->assign('isStripeSupported', $isSupported);
        $rlSmarty->display(RL_PLUGINS . 'stripe/account_settings.tpl');
    }

    /**
     * @hook phpGetPaymentGateways
     * @since 1.2.0
     */
    public function hookPhpGetPaymentGateways(&$gateways, &$content)
    {
        $stripe_index = $GLOBALS['config']['stripe_index'] ? 'true' : 'false';

        $this->initGateway();
        if ($GLOBALS['rlStripeGateway']->isConfigured()) {
            $content .= <<< FL
<script>rlConfig['stripe_index'] = {$stripe_index};</script>
<script src="https://js.stripe.com/v3/"></script>
FL;
        }
    }

    /**
     * Initialize gateway class
     * @since 1.2.0
     */
    public function initGateway()
    {
        global $reefless;

        if (!is_object('rlGateway')) {
            require_once RL_CLASSES . 'rlGateway.class.php';
        }
        $reefless->loadClass('StripeGateway', null, 'stripe');

        if ($GLOBALS['config']['shc_method'] == 'multi') {
            $reefless->loadClass('ShoppingCart', null, 'shoppingCart');
        }
        
        $GLOBALS['rlStripeGateway']->init();
    }

    /**
     * @hook apPaymentGatewaysValidate
     * @since 2.0.0
     */
    public function hookApPaymentGatewaysValidate(&$errors, $i_key)
    {
        if ($i_key == 'stripe') {
            if (extension_loaded('curl')) {
                // check public key
                if ($result = $this->checkAPIKey($_POST['post_config']['stripe_publishable_key'])) {
                    if ($result != 'OK') {
                        $errors[] = str_replace('Key', 'Publishable Key', $result);
                    }
                }

                // check secret key
                if ($result = $this->checkAPIKey($_POST['post_config']['stripe_secret_key'])) {
                    if ($result != 'OK') {
                        $errors[] = str_replace('Key', 'Secret Key', $result);
                    }
                }
            }
        }
    }

    /**
     * Validate API Key
     *
     * @since 2.0.0
     * @param  string $key
     * @return string
     */
    public function checkAPIKey($key = '')
    {
        $result = 'OK';
        $curl = curl_version();
        $sslVersion = isset($curl['ssl_version']) ? $curl['ssl_version'] : '';
        $test_card = "card[number]=4242424242424242&card[exp_month]=12&card[exp_year]=2017&card[cvc]=123";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/tokens");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $test_card);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        if (substr_compare($sslVersion, "NSS/", 0, strlen("NSS/")) != 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        }
        curl_setopt($ch, CURLOPT_USERPWD, $key . ":");

        $response = json_decode(curl_exec($ch), true);

        if (curl_errno($ch)) {
            $result = 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        if (substr($response["error"]["message"], 0, 24) == "Invalid API Key provided") {
            $result = "Invalid API Key provided";
        }
        return $result;
    }

    /**
     * Cancel subscription for specific plan
     * This method is temporary solution till moment when the problem will be fixed in core
     *
     * @since 2.1.0
     *
     * @param array $subscription
     */
    public function cancelSubscription($subscription = array())
    {
        global $rlStripeGateway;

        $this->initGateway();

        return $rlStripeGateway->cancelSubscription($subscription);
    }

    /**
     * @hook ajaxRequest
     *
     * @since 2.2.0
     */
    public function hookAjaxRequest(&$out, $request_mode, $request_item, $request_lang)
    {
        global $lang, $config, $rlDb, $account_info, $reefless, $rlSmarty, $rlPayment;

        if (!$this->isAjaxModeValid($request_mode)) {
            return;
        }

        if (!$account_info && $_SESSION['account']) {
            $account_info = $_SESSION['account'];
        }

        $error = false;

        if (!$lang) {
            $lang = $GLOBALS['rlLang']->getLangBySide('frontEnd', $request_lang);
        }

        $rlSmarty->assign_by_ref('lang', $lang);

        if ($account_info) {
            $rlSmarty->assign('isLogin', true);
        }

        $reefless->loadClass('Payment');
        $this->initGateway();

        switch ($request_mode) {
            case 'stripePaymentIntent':
                \Stripe\Stripe::setApiKey($config['stripe_secret_key']);

                $service = $rlPayment->getOption('service');
                $price = $rlPayment->getOption('total');

                $input = file_get_contents('php://input');
                $body = json_decode($input);

                if (!$rlPayment->getOption('total')) {
                    $error = str_replace(
                        '{option}',
                        $lang['payment_option_total'],
                        $lang['required_payment_option_error']
                    );
                    http_response_code(400);
                    $out = ['error' => $error];
                }

                if (json_last_error() !== JSON_ERROR_NONE) {
                    http_response_code(400);
                    $out = ['error' => 'Invalid request.'];
                }

                if ($out['error']) {
                    return;
                }

                $GLOBALS['rlStripeGateway']->updateTransaction([
                    'Gateway' => 'stripe',
                ]);

                try {
                    if ($body->paymentMethodId != null) {
                        $sellerAccount = [];
                        $request = [
                            'payment_method_types' => ['card'],
                            "amount" => $price * 100,
                            "currency" => $config['system_currency_code'],
                            "payment_method" => $body->paymentMethodId,
                            "confirmation_method" => 'manual',
                            "confirm" => true,
                        ];

                        if (in_array($service, ['shopping', 'auction'])
                            && $config['shc_method'] == 'multi'
                            && $config['shc_commission_enable']
                        ) {
                            if ($config['stripe_account_id']) {
                                $itemID = $rlPayment->getOption('item_id');
                                $orderInfo = $rlDb->fetch('*', array('ID' => $itemID), null, 1, 'shc_orders', 'row');
                                $commission = (float) $orderInfo['Commission_total'] * 100;
                                $request['application_fee_amount'] = $commission;
                                $sellerAccount = ['stripe_account' => $config['stripe_account_id']];
                            } else {
                                $out = [
                                    'error' => $lang['stripe_account_not_provided'],
                                ];
                                return;
                            }
                        }

                        // Create new PaymentIntent with a PaymentMethod ID from the client.
                        $intent = \Stripe\PaymentIntent::create($request, $sellerAccount);
                        // After create, if the PaymentIntent's status is succeeded, fulfill the order.
                    } else if ($body->paymentIntentId != null) {
                        // Confirm the PaymentIntent to finalize payment after handling a required action
                        // on the client.
                        $intent = \Stripe\PaymentIntent::retrieve($body->paymentIntentId);
                        $intent->confirm();
                    }
                    $GLOBALS['rlStripeGateway']->setRealPayoutPrice($intent, $config['stripe_account_id'], $itemID, $service);
                    $out = $this->generateResponse($intent);
                } catch(\Stripe\Exception\CardException $e) {
                    $out = ['error' => "A payment error occurred: {$e->getError()->message}"];
                } catch (\Stripe\Exception\InvalidRequestException $e) {
                    $out = ['error' => "An invalid request occurred."];
                } catch (Exception $e) {
                    $out = ['error' => "Another problem occurred, maybe unrelated to Stripe."];
                }
                break;

            case 'stripeKeys':
                $out = array(
                    'publishableKey' => $config['stripe_publishable_key'],
                );

                $input = file_get_contents('php://input');
                $body = json_decode($input);

                if ($body->subscription) {
                    $out['customerId'] = $GLOBALS['rlStripeGateway']->getCustomerID();
                    $out['priceId'] = $GLOBALS['rlStripeGateway']->getPriceID();
                }
                $service = $rlPayment->getOption('service');
                if (in_array($service, ['shopping', 'auction'])
                    && $config['shc_method'] == 'multi'
                    && $config['shc_commission_enable']
                    // && !$config['shc_escrow']
                ) {
                    if ($config['stripe_account_id']) {
                        $out['stripeAccount'] = $config['stripe_account_id'];
                    }
                }
                break;

            case 'stripeComplete':
                $paymentIntent = json_decode($_REQUEST['paymentIntent'], true);

                if ($paymentIntent['status'] == 'succeeded') {
                    $GLOBALS['rlStripeGateway']->callBackPI($paymentIntent);
                    // save response to log
                    if ($config['stripe_test_mode']) {
                        file_put_contents(RL_PLUGINS . 'stripe/response.log', print_r($paymentIntent, true), FILE_APPEND);
                    }

                    $out = array(
                        'status' => 'OK',
                        'successUrl' => $rlPayment->getOption('success_url'),
                    );
                } else {
                    // save response to log
                    if ($config['stripe_test_mode']) {
                        file_put_contents(RL_PLUGINS . 'stripe/response.log', print_r($paymentIntent, true), FILE_APPEND);
                    }
                    $out = array(
                        'status' => 'ERROR',
                        'errorUrl' => $rlPayment->getOption('cancel_url'),
                    );
                }
                break;

            case 'stripeCreateSubscription':
                $input = file_get_contents('php://input');
                $request = json_decode($input, true);
                $result = $GLOBALS['rlStripeGateway']->createSubscriptionPayment(
                    $request['customerId'],
                    $request['paymentMethodId'],
                    $request['priceId']
                );

                if ($result['error']) {
                    http_response_code(400);
                }

                $out = $result;

                break;

            case 'stripeSubscriptionComplete':
                $request = $_REQUEST['request'];

                $GLOBALS['rlStripeGateway']->updateTransaction([
                    'Gateway' => 'stripe',
                    'Item_data' => $rlPayment->buildItemData(),
                ]);

                $sql = "SELECT * FROM `{db_prefix}subscriptions` WHERE `Subscription_ID` = '" . $request['id'] . "'";
                $subscriptionInfo = $GLOBALS['rlDb']->getRow($sql);

                if ($subscriptionInfo['Status'] == 'active') {
                    $out = array(
                        'status' => 'OK',
                        'successUrl' => $rlPayment->getOption('success_url'),
                    );
                } else {
                    // save response to log
                    if ($config['stripe_test_mode']) {
                        file_put_contents(RL_PLUGINS . 'stripe/response.log', print_r($request, true), FILE_APPEND);
                    }
                    $out = array(
                        'status' => 'ERROR',
                        'errorUrl' => $rlPayment->getOption('cancel_url'),
                    );
                }
                break;

            case 'stripeConnect':
                $result = $GLOBALS['rlStripeGateway']->connectToStripe();

                if ($result['accountLink']->url) {
                    $out = array(
                        'status' => 'OK',
                        'stripeUrl' => $result['accountLink']->url,
                    );
                } else {
                    // save response to log
                    if ($config['stripe_test_mode']) {
                        file_put_contents(RL_PLUGINS . 'stripe/response.log', print_r($result, true), FILE_APPEND);
                    }
                    $out = array(
                        'status' => 'ERROR',
                        'message' => $result->error,
                    );
                }
                break;

            case 'stripeDeleteAccount':
                $result = $GLOBALS['rlStripeGateway']->deleteAccount($account_info['ID']);

                if ($result && !$result->error) {
                    $out = array(
                        'status' => 'OK',
                        'url' => $reefless->getPageUrl('my_profile', false, false, 'deleted'),
                    );
                } else {
                    // save response to log
                    if ($config['stripe_test_mode']) {
                        file_put_contents(RL_PLUGINS . 'stripe/response.log', print_r($result, true), FILE_APPEND);
                    }
                    $out = array(
                        'status' => 'ERROR',
                        'message' => $result->error,
                    );
                }
                break;
        }
    }

    /**
     * Check ajaxRequest request
     * @since 2.2.0
     *
     * @param string $request_mode
     * @return bool
     */
    public function isAjaxModeValid($request_mode = '')
    {
        $ajaxRequests = array(
            'stripePaymentIntent',
            'stripeKeys',
            'stripeComplete',
            'stripeCreateSubscription',
            'stripeSubscriptionComplete',
            'stripeConnect',
            'stripeDeleteAccount',
        );

        return (bool) ($request_mode && in_array($request_mode, $ajaxRequests));
    }

    public function generateResponse($intent)
    {
        global $config;

        if ($config['stripe_test_mode']) {
            file_put_contents(RL_PLUGINS . 'stripe/intent.log', print_r($intent, true), FILE_APPEND);
        }

        switch ($intent->status) {
            case "requires_action":
            case "requires_source_action":
                // Card requires authentication
                return [
                    'requiresAction' => true,
                    'paymentIntentId' => $intent->id,
                    'clientSecret' => $intent->client_secret,
                ];
            case "requires_payment_method":
            case "requires_source":
                // Card was not properly authenticated, suggest a new payment method
                return [
                    error => "Your card was denied, please provide a new payment method",
                ];
            case "succeeded":
                // Payment is complete, authentication not required
                // To cancel the payment after capture you will need to issue a Refund (https://stripe.com/docs/api/refunds)
                return ['clientSecret' => $intent->client_secret];
        }
    }

    /**
     * @hook loadPaymentForm
     *
     * @since 2.2.0
     */
    public function hookLoadPaymentForm($gatewayInfo)
    {
        global $rlPayment, $config;

        if ($gatewayInfo['Key'] != 'stripe') {
            return;
        }

        $this->initGateway();

        $isConfigured = true;

        if (!$GLOBALS['rlStripeGateway']->isConfigured()) {
            $isConfigured = false;
        }

        $libFile = 'lib';
        if ($rlPayment->isRecurring()) {
            $libFile = 'lib.subscription';
        }

        $GLOBALS['rlSmarty']->assign('isStripeConfigured', $isConfigured);
        $GLOBALS['rlSmarty']->assign('libFile', $libFile);
    }

    /**
     * @hook profileController
     *
     * @since 2.2.0
     */
    public function hookProfileController()
    {
        global $config, $lang, $rlNotice, $rlShoppingCart, $rlStripeGateway, $errors;

        if ($config['shc_method'] != 'multi' || !$config['shc_commission'] || !$GLOBALS['rlDb']->tableExists('shc_account_settings')) {
            return;
        }

        $GLOBALS['reefless']->loadClass('Notice');
        $GLOBALS['rlStatic']->addHeaderCSS(RL_PLUGINS_URL . 'stripe/static/style_connect.css');

        if (isset($_GET['deleted'])) {
            $rlNotice->saveNotice($lang['stripe_delete_ok']);
        }

        $connect = $_GET['nvar_1'] ? $_GET['nvar_1'] : $_REQUEST['connect'];
        $options = $rlShoppingCart->getAccountOptions($GLOBALS['account_info']['ID']);

        if ($connect == 'stripe' && isset($_GET['key'])) {
            // clear incorrect errors 
            $errors = [];

            $accountID = base64_decode($_GET['key']);

            if ($options['stripe_account_id'] == $accountID && !$options['stripe_account_confirmed']) {
                $rlShoppingCart->saveAccountSettings([
                    'stripe_account_confirmed' => '1',
                ]);
                $rlNotice->saveNotice($lang['stripe_connect_confirmed']);
            } elseif ($options['stripe_account_confirmed']) {
                $rlNotice->saveNotice($lang['stripe_connect_exists'], 'warning');
            } else {
                $rlNotice->saveNotice($lang['stripe_connected_error'], 'error');
            }
        }
        $this->initGateway();

        if (!$_SESSION['stripeAccountOpt'] || $options['stripe_account_id'] != $_SESSION['stripeAccountOpt']['id']) {
            $this->initGateway();

            $stripeAccount = $rlStripeGateway->getConnectedAccount($options['stripe_account_id']);
            $stripeLogin = $rlStripeGateway->createLoginLink($options['stripe_account_id']);

            $_SESSION['stripeAccountOpt'] = [
                'id' => $stripeAccount->id,
                'business_type' => $stripeAccount->business_type,
                'verification' => $stripeAccount->individual->verification->status,
                'display_name' => $stripeAccount->settings->dashboard->display_name,
                'charges_enabled' => $stripeAccount->business_type,
                'payouts_enabled' => $stripeAccount->business_type,
                'status' => $stripeAccount->charges_enabled && $stripeAccount->payouts_enabled
                ? $lang['stripe_enabled']
                : $lang['stripe_restricted'],
            ];

            $_SESSION['stripeAccountOpt']['url'] = $stripeLogin->url;
        }

        $GLOBALS['rlSmarty']->assign('shcAccountSettings', $options);
        $GLOBALS['rlSmarty']->assign('stripeAccount', $_SESSION['stripeAccountOpt']);
    }

    /**
     * Add account fields for shopping cart & bidding plugin
     *
     * @since 2.2.0
     */
    public function addAccountFields()
    {
        global $rlDb, $plugins;

        $rlDb->addColumnsToTable(
            array(
                'stripe_customer_id' => "varchar(50) NOT NULL default ''",
            ),
            'accounts'
        );

        if (!$plugins['shoppingCart']) {
            return;
        }

        $accountsTable = 'shc_account_settings';

        if (!$rlDb->tableExists('shc_account_settings')) {
            $accountsTable = 'accounts';

            if (version_compare($GLOBALS['config']['rl_version'], '4.8.2') >= 0) {
                return;
            }
        }

        $rlDb->addColumnsToTable(
            array(
                'stripe_enable' => "ENUM('0','1') NOT NULL DEFAULT '0'",
                'stripe_publishable_key' => "varchar(150) NOT NULL default ''",
                'stripe_secret_key' => "varchar(150) NOT NULL default ''",
                'stripe_account_id' => "varchar(50) NOT NULL default ''",
                'stripe_account_confirmed' => "ENUM('0','1') NOT NULL DEFAULT '0'",
                'stripe_payment_method' => "varchar(50) NOT NULL default ''",
            ),
            $accountsTable
        );
    }

    /**
     * @hook apTplPaymentGatewaysBottom
     *
     * @version    2.2.2 Method reverted in the class
     * @deprecated 2.1.0
     * @since      1.2.0
     */
    public function hookApTplPaymentGatewaysBottom()
    {
        if ($_GET['item'] == 'stripe') {
            $url = 'https://www.flynax.com/files/manuals/stripe-speed-configuration-guide.pdf';
            echo <<< FL
<script type="text/javascript">
    $(document).ready(function(){
        $('select[name="status"]').closest('tr').after('<tr><td></td><td class="field"><a class="static" href="{$url}" target="_blank">{$GLOBALS['lang']['stripe_speed_configuration_guide']}</a></td></tr>');
    });
</script>
FL;
        }
    }

    /**
     * Confirm order by buyer and make payout to seller
     *
     * @since 2.3.0
     *
     * @param array $orderInfo
     * @return bool
     */
    public function confirmEscrow(array $orderInfo) : bool
    {
        global $config;

        $this->initGateway();
        $accountSettings = $GLOBALS['rlShoppingCart']->getAccountOptions($orderInfo['Dealer_ID']);

        $feeTotal = (float) $orderInfo['Commission_total'];
        $total = 0;
        if(preg_match('/\d+(?:\.\d+)?/',$orderInfo['Total'],$matches)){
            $total = $matches[0];
        }
        $total = $orderInfo['Total_payout'] ? $orderInfo['Total_payout'] : (float) $total - $feeTotal;

        $result = new stdClass();
        if ($config['stripe_test_mode']) {
            file_put_contents(RL_PLUGINS . 'stripe/payouts.log', "\n" . date('Y.m.d H:i:s') . ' $total: ' . $total, FILE_APPEND);
        }

        if ($total <= 0) {
            return false;
        }

        try {
            $result = $GLOBALS['rlStripeGateway']->stripe->payouts->create([
                'amount' => $total * 100,
                'currency' => $config['system_currency_code'],
            ], [
                'stripe_account' => $accountSettings['stripe_account_id'],
            ]);
        } catch (Exception $e) {
            $result->error = $e->getMessage();
        }

        if ($config['stripe_test_mode']) {
            file_put_contents(RL_PLUGINS . 'stripe/payouts.log', "\n" . date('Y.m.d H:i:s') . ': ' . print_r($result, true), FILE_APPEND);
        }

        return in_array($result->status, ['paid', 'pending', 'in_transit']);
    }

    /**
     * Cancel order by buyer and refund payment
     *
     * @since 2.3.0
     *
     * @param array $orderInfo
     * @return bool
     */
    public function cancelEscrow(array $orderInfo) : bool
    {
        global $rlDb, $config;

        $this->initGateway();

        if (!$orderInfo) {
            return false;
        }

        $result = new stdClass();
        $accountSettings = $GLOBALS['rlShoppingCart']->getAccountOptions($orderInfo['Dealer_ID']);

        try {
            $result = $GLOBALS['rlStripeGateway']->stripe->refunds->create([
                'payment_intent' => $orderInfo['Deal_ID'],
                'refund_application_fee' => true,
            ], ['stripe_account' => $accountSettings['stripe_account_id']]);
        } catch (Exception $e) {
            $result->error = $e->getMessage();
        }

        if ($config['stripe_test_mode']) {
            file_put_contents(RL_PLUGINS . 'stripe/refunds.log', "\n" . date('Y.m.d H:i:s') . ': ' . print_r($result, true), FILE_APPEND);
        }

        return $result->status == 'succeeded';
    }

    /**
     * @deprecated 2.2.0
     *
     * @hook apPhpListingPlansAfterAdd
     */
    public function hookApPhpListingPlansAfterAdd()
    {}

    /**
     * @deprecated 2.2.0
     *
     * @hook apPhpListingPlansAfterEdit
     */
    public function hookApPhpListingPlansAfterEdit()
    {}

    /**
     * @deprecated 2.2.0
     *
     * @hook apPhpMembershipPlansAfterAdd
     */
    public function hookApPhpMembershipPlansAfterAdd(&$key)
    {}

    /**
     * @deprecated 2.2.0
     *
     * @hook apPhpMembershipPlansAfterEdit
     */
    public function hookApPhpMembershipPlansAfterEdit(&$key)
    {}
}
