<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLSTRIPEGATEWAY.CLASS.PHP
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

class rlStripeGateway extends rlGateway
{
    /**
     * Stripe object
     *
     * @since 2.2.0
     *
     * @var object
     */
    public $stripe;

    /**
     * Initialize payment library
     */
    public function init()
    {
        global $config, $rlPayment, $rlDb;

        require_once RL_PLUGINS . 'stripe/vendor/autoload.php';

        $service = is_object($rlPayment) ? $rlPayment->getOption('service') : '';
        if (in_array($service, ['shopping', 'auction']) && $config['shc_method'] == 'multi') {
            $dealerID = $rlPayment->getOption('dealer_id');
            if ($rlDb->tableExists('shc_account_settings')) {
                $GLOBALS['reefless']->loadClass('ShoppingCart', null, 'shoppingCart');
                $options = $GLOBALS['rlShoppingCart']->getAccountOptions($dealerID);
            } else {
                $fields = [
                    'stripe_enable', 
                    'stripe_publishable_key', 
                    'stripe_secret_key', 
                    'stripe_account_id', 
                    'stripe_account_confirmed',
                    'stripe_payment_method'
                ];
                $options = $rlDb->fetch($fields, array('ID' => $dealerID), null, 1, 'accounts', 'row');
            }
            if (!$config['shc_commission_enable']) {
                $config['stripe_publishable_key'] = $options['stripe_publishable_key'];
                $config['stripe_secret_key'] = $options['stripe_secret_key'];
            }
            $config['stripe_account_id'] = $options['stripe_account_id'];
        }

        if ($this->isConfigured()) {
            $this->stripe = new Stripe\StripeClient($config['stripe_secret_key']);
        }
        $GLOBALS['reefless']->loadClass('Subscription');
    }

    /**
     * Start payment process
     */
    public function call()
    {}

    /**
     * Complete payment process
     */
    public function callBack()
    {}

    /**
     * Callback payment intent
     *
     * @param object $paymentIntent
     */
    public function callBackPI($paymentIntent)
    {
        global $rlPayment, $config;

        $response = array(
            'plan_id' => $rlPayment->getOption('plan_id'),
            'item_id' => $rlPayment->getOption('item_id'),
            'account_id' => $rlPayment->getOption('account_id'),
            'total' => $rlPayment->getOption('total'),
            'txn_id' => $rlPayment->getTransactionID(),
            'txn_gateway' => $paymentIntent['id'],
            'params' => $rlPayment->getOption('params'),
        );
        $service = $rlPayment->getOption('service');
        if (in_array($service, ['shopping', 'auction'])
            && $config['shc_method'] == 'multi'
            && $config['shc_commission_enable']
            && $config['shc_escrow']
        ) {
            $date = date('Y-m-d H:i:s');
            $update = array(
                'fields' => array(
                    'Escrow' => '1',
                    'Escrow_date' => date('Y-m-d H:i:s', strtotime($date . ' + 90 days')),
                    'Deal_ID' => $paymentIntent['id'],
                ),
                'where' => array('ID' => $rlPayment->getOption('item_id')),

            );
            $GLOBALS['rlDb']->updateOne($update, 'shc_orders');
        }

        $rlPayment->complete(
            $response,
            $rlPayment->getOption('callback_class'),
            $rlPayment->getOption('callback_method'),
            $rlPayment->getOption('plugin') ? $rlPayment->getOption('plugin') : false
        );
    }

    /**
     * Create subscription payment
     *
     * @param array $customerId
     * @param string $paymentMethodId
     * @param string $priceId
     *
     * @return object
     */
    public function createSubscriptionPayment($customerId = '', $paymentMethodId = '', $priceId = '')
    {
        global $rlPayment, $rlDb;

        $action = false;
        $error = '';
        $this->init();

        // check webhook
        $webhook = $this->controlWebHooks();
        if (!$webhook->id) {
            $webhook = $this->createEndpoint();
        }

        if (!$webhook->id) {
            return [
                'error' => [
                    'message' => "Can't create webhook",
                ],
            ];
        }

        try {
            $payment_method = $this->stripe->paymentMethods->retrieve($paymentMethodId);
            $payment_method->attach([
                'customer' => $customerId,
            ]);
        } catch (\Stripe\Exception\CardException $e) {
            return [
                'error' => [
                    'message' => $e->getError()->message,
                ],
            ];
        }

        try {
            $request = [
                'customer' => $customerId,
                'collection_method' => 'charge_automatically',
                'default_payment_method' => $paymentMethodId,
                'items' => [
                    [
                        'price' => $priceId,
                    ],
                ],
                'expand' => ['latest_invoice.payment_intent'],
            ];

            if ($account_info['stripe_payment_method']) {
                $request['default_payment_method'] = $account_info['stripe_payment_method'];
            }

            $subscription = $this->stripe->subscriptions->create($request);

            $txn_id = $subscription->id;

            if ($subscription->id) {
                $sql = "SELECT * FROM `{db_prefix}subscriptions` WHERE `Subscription_ID` = '" . $subscription->id . "'";
                $subscription_info = $GLOBALS['rlDb']->getRow($sql);

                if ($subscription_info['Subscription_ID']) {
                    $update = array(
                        'fields' => array(
                            'Date' => 'NOW()',
                            'Txn_ID' => $txn_id,
                            'Count' => $subscription_info['Count'] + 1,
                        ),
                        'where' => array(
                            'Subscription_ID' => $subscription->id,
                        ),
                    );

                    $action = $rlDb->updateOne($update, 'subscriptions');
                } else {
                    $sql = "SELECT * FROM `{db_prefix}payment_gateways` WHERE `Key` = 'stripe'";
                    $gateway_info = $rlDb->getRow($sql);

                    $insert = array(
                        'Service' => $GLOBALS['rlSubscription']->getService($rlPayment->getOption('service')),
                        'Account_ID' => $rlPayment->getOption('account_id'),
                        'Item_ID' => $rlPayment->getOption('item_id'),
                        'Plan_ID' => $rlPayment->getOption('plan_id'),
                        'Total' => $rlPayment->getOption('total'),
                        'Gateway_ID' => $gateway_info['ID'],
                        'Item_name' => $rlPayment->getOption('item_name'),
                        'Date' => 'NOW()',
                        'Txn_ID' => $txn_id,
                        'Customer_ID' => $customerId,
                        'Subscription_ID' => $subscription->id,
                        'Stripe_item_data' => base64_encode($rlPayment->getOption('callback_class') . '|' .
                            $rlPayment->getOption('callback_method') . '|' .
                            ($rlPayment->getOption('plugin') ? $rlPayment->getOption('plugin') : false) . '|' .
                            $rlPayment->getTransactionID() . '|' .
                            $rlPayment->getOption('params')
                        ),
                        'Count' => 0,
                    );

                    $action = $rlDb->insertOne($insert, 'subscriptions');
                }
            }
        } catch (\Stripe\Exception\CardException $e) {
            return [
                'error' => [
                    'message' => $e->getError()->message,
                ],
            ];
        }

        return $subscription;
    }

    /**
     * Cancel subscription for specific plan
     *
     * @param array $subscription
     */
    public function cancelSubscription($subscription = array())
    {
        if (!$subscription) {
            return;
        }

        $this->init();

        $subscription = $this->stripe->subscriptions->retrieve($subscription['Subscription_ID']);
        $action = $subscription->delete();

        return $action;
    }

    /**
     * Set subscription mode
     *
     * @param boolean $mode
     */
    public function setSubscription($mode = true)
    {
        $this->subscription = (bool) $mode;
    }

    /**
     * Check settings of the gateway
     */
    public function isConfigured()
    {
        global $config;

        if ($config['stripe_publishable_key'] && $config['stripe_secret_key']) {
            return true;
        }

        return false;
    }

    /**
     * Get plan details by service
     */
    protected function getItemPlan()
    {
        global $rlPayment, $rlSubscription;

        $plan_info = array();
        $table = $this->getPlanTable();

        $GLOBALS['rlHook']->load('stripeGetItemPlan');

        if ($table) {
            $sql = "SELECT * FROM `{db_prefix}" . $table . "` WHERE `ID` = '" . $rlPayment->getOption('plan_id') . "' LIMIT 1";
            $plan_info = $GLOBALS['rlDb']->getRow($sql);

            if ($plan_info) {
                $plan_info['sPlan'] = $rlSubscription->getPlan(
                    $rlSubscription->getService($rlPayment->getOption('service')),
                    $rlPayment->getOption('plan_id')
                );
            }
        }

        return $plan_info;
    }

    /**
     * Get callback response after payment
     */
    public function getWebhookResponse()
    {
        $input = @file_get_contents("php://input");

        // save response to log
        if ($GLOBALS['config']['stripe_test_mode']) {
            $log = sprintf("\n%s:\n%s\n", date('Y.m.d H:i:s'), $input);
            file_put_contents(RL_PLUGINS . 'stripe/response.log', $log, FILE_APPEND);
        }

        $response = json_decode($input);

        return $response;
    }

    /**
     * Get subscription ID
     *
     * @param mixed $subscription_id
     * @return array
     */
    public function getSubscriptionByID($subscription_id = false)
    {
        if (!$subscription_id) {
            return false;
        }

        $sql = "SELECT * FROM `{db_prefix}subscriptions` WHERE `Subscription_ID` = '" . $subscription_id . "' LIMIT 1";
        $subscription_info = $GLOBALS['rlDb']->getRow($sql);

        if ($subscription_info) {
            $subscription_info['Stripe_item_data'] = explode("|", base64_decode($subscription_info['Stripe_item_data']));
        }

        return $subscription_info;
    }

    /**
     * Validate credit card details
     */
    public function validate()
    {
        global $lang;

        $data = $_POST['f'];

        if (!$data['card_number']) {
            $this->errors[] = str_replace('{field}', "<b>\"{$lang['stripe_card_number']}\"</b>", $lang['notice_field_empty']);
        }
        if (!$data['exp_month']) {
            $this->errors[] = str_replace('{field}', "<b>\"{$lang['stripe_card_expiry_month']}\"</b>", $lang['notice_field_empty']);
        }
        if (!$data['exp_year']) {
            $this->errors[] = str_replace('{field}', "<b>\"{$lang['stripe_card_expiry_year']}\"</b>", $lang['notice_field_empty']);
        }
        if (empty($data['card_verification_code'])) {
            $this->errors[] = str_replace('{field}', "<b>\"{$lang['card_verification_code']}\"</b>", $lang['notice_field_empty']);
        }
    }

    /**
     * Create customer in Stripe service
     *
     * @since 2.1.0
     */
    public function createCustomer()
    {
        global $account_info, $rlAccount;

        $customer = $this->getCustomer();
        $update = false;
        $accountID = $rlAccount->isLogin() ? $account_info['ID'] : (int) $_SESSION['registration']['account_id'];

        $profile = $rlAccount->getProfile((int) $accountID);
        $country = $this->getCountryCode($profile['Fields']['country']['value']);

        if (empty($country)) {
            $country = $profile['Fields']['country']['value'];
        }

        $request = [
            'address' => [
                'line1' => $profile['address'],
                'city' => $profile['Fields']['country_level2']['value'],
                'country' => $country,
                'postal_code' => $profile['zip_code'],
                'state' => $profile['Fields']['country_level1']['value'],
            ],
            'description' => $profile['about_me'],
            'email' => $profile['Mail'],
            'name' => $profile['Type'] == 'private' ? $profile['Full_name'] : $profile['company_name'],
        ];

        if ($customer->id) {
            $this->stripe->customers->update(
                $customer->id,
                $request
            );

            $update = true;
        } else {
            $customer = $this->stripe->customers->create($request);
        }

        $account_info['stripe_customer_id'] = $_SESSION['account']['stripe_customer_id'] = $customer->id;

        $update = [
            'fields' => [
                'stripe_customer_id' => $customer->id,
            ],
            'where' => ['ID' => $accountID],
        ];

        $GLOBALS['rlDb']->updateOne($update, 'accounts');

        return $customer;
    }

    /**
     * Get customer information from Stripe service
     *
     * @since 2.1.0
     *
     * @return array
     */
    public function getCustomer()
    {
        global $account_info;

        $customer = [];

        if (empty($account_info['stripe_customer_id'])) {
            return $customer;
        }

        try {
            $customer = $this->stripe->customers->retrieve($account_info['stripe_customer_id']);
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }

        return $customer;
    }

    /**
     * Get customer ID
     *
     * @since 2.1.0
     *
     * @return string
     */
    public function getCustomerID()
    {
        global $account_info;

        if (!empty($account_info['stripe_customer_id'])) {
            $customer = $this->getCustomer();

            if ($customer->id) {
                return $account_info['stripe_customer_id'];
            }
        }

        $customer = $this->createCustomer();

        if ($customer) {
            return $customer->id;
        }

        return null;
    }

    /**
     * Get country code by name
     *
     * @since 2.1.0
     *
     * @param  string $country
     * @return string
     */
    public static function getCountryCode($country = '')
    {
        if (!$country) {
            return null;
        }

        $url = RL_PLUGINS_URL . 'stripe/countries.json';
        $countries = $GLOBALS['reefless']->getPageContent($url);
        $countries = json_decode($countries, true);

        $code = false;
        $country = str_replace("_", " ", $country);

        foreach ($countries as $key => $val) {
            if (strtolower($country) == strtolower($val) || $country == $key) {
                $code = trim($key);
                break;
            }
        }

        return $code;
    }

    /**
     * Create webhook endpoint for recurring option
     *
     * @since 2.1.0
     *
     * @return array
     */
    public function createEndpoint()
    {
        if (!empty($GLOBALS['config']['stripe_endpoint'])) {
            $response = $this->getEndpoint();
        }

        if (!$response->id) {
            $response = $this->stripe->webhookEndpoints->create([
                'url' => RL_PLUGINS_URL . 'stripe/subscription.php',
                'enabled_events' => [
                    'customer.subscription.updated',
                    'invoice.payment_succeeded',
                    'charge.succeeded',
                ],
            ]);

            if ($response) {
                $update = [
                    'fields' => ['Default' => $response->id],
                    'where' => ['Key' => 'stripe_endpoint'],
                ];

                $GLOBALS['rlDb']->updateOne($update, 'config');
            }
        }

        return $response;
    }

    /**
     * Get webhook endpoint for recurring option
     *
     * @since 2.1.0
     *
     * @return array
     */
    public function getEndpoint()
    {
        global $config;

        if (!empty($config['stripe_endpoint'])) {
            $response = $this->stripe->webhookEndpoints->retrieve($config['stripe_endpoint']);
        } else {
            $response = '';
            $webhooks = $this->stripe->webhookEndpoints->all(['limit' => 10]);

            if ($webhooks->data) {
                $host = parse_url(RL_URL_HOME, PHP_URL_HOST);
                foreach ($webhooks as $webhook) {
                    if (substr_count($webhook->url, $host) > 0) {
                        $response = $webhook;
                    }
                }

                if ($response) {
                    $update = [
                        'fields' => ['Default' => $response->id],
                        'where' => ['Key' => 'stripe_endpoint'],
                    ];

                    $GLOBALS['rlDb']->updateOne($update, 'config');
                }
            }
        }

        return $response;
    }

    /**
     * Get payment method by customer from Stripe service
     *
     * @since 2.1.0
     *
     * @param  string $customerID
     * @return string
     */
    public function getPaymentMethod($customerID = '')
    {
        $methodID = '';

        $methods = \Stripe\PaymentMethod::all([
            'customer' => $customerID,
            'type' => 'card',
        ]);

        if ($methods->data) {
            $methodID = $methods->data[0]->id;
        }

        return $methodID;
    }

    /**
     * Get webhooks from Stripe service
     *
     * @since 2.2.0
     * @return array
     */
    public function getWebHooks()
    {
        global $config, $domain_info;

        $hooks = $this->stripe->webhookEndpoints->all(['limit' => 50]);
        $data = [];

        if ($hooks) {
            foreach ($hooks->data as $hook) {
                $isAvailable = true;

                if ((!$config['stripe_test_mode'] && !$hook->livemode)
                    || ($config['stripe_test_mode'] && $hook->livemode)
                ) {
                    $isAvailable = false;
                }

                if (substr_count($hook->url, $domain_info['host']) <= 0 || !$isAvailable) {
                    continue;
                }
                $data[] = [
                    'id' => $hook->id,
                    'status' => $hook->status,
                    'url' => $hook->url,
                    'created' => $hook->created,
                ];
            }

            $GLOBALS['reefless']->rlArraySort($data, 'created', SORT_DESC);
        }

        return $data;
    }

    /**
     * Control webhooks from Stripe service
     *
     * @since 2.2.0
     * @return object
     */
    public function controlWebHooks()
    {
        global $config;

        $hooks = $this->getWebHooks();
        $index = '';
        $data = [];

        if ($hooks) {
            $index = array_search($config['stripe_endpoint'], array_column($hooks, 'id'));

            if ($index == '') {
                $index = 0;
                $config['stripe_endpoint'] = $hooks[$index]['id'];

                $update = [
                    'fields' => ['Default' => $hooks[$index]['id']],
                    'where' => ['Key' => 'stripe_endpoint'],
                ];

                $GLOBALS['rlDb']->updateOne($update, 'config');
            }

            $data = $hooks[$index];

            foreach ($hooks as $key => $hook) {
                if ($key == $index || $hook['status'] != 'enabled') {
                    continue;
                }

                $webhook_endpoint = $this->stripe->webhookEndpoints->retrieve($hook['id']);
                $webhook_endpoint->delete();
            }
        }

        return (object) $data;
    }

    /**
     * Get Price ID
     *
     * @since 2.2.0
     *
     * @return string
     */
    public function getPriceID()
    {
        global $rlPayment;

        $plan = $this->getItemPlan();

        if (!empty($plan['stripe_key'])) {
            try {
                $price = $this->stripe->prices->retrieve(
                    $plan['stripe_key'],
                    []
                );
            } catch (Exception $e) {
                file_put_contents(RL_PLUGINS . 'stripe/errors.log', "\n" . date('Y.m.d H:i:s') . ': ' . $e->getMessage(), FILE_APPEND);
            }

            if (!$error) {
                if ($price->id) {
                    return $plan['stripe_key'];
                }
            }
        }

        $price = $this->createPrice($plan);

        if ($price) {
            return $price;
        }

        return null;
    }

    /**
     * Create price
     *
     * @since 2.2.0
     *
     * @param array $plan
     *
     * @return string
     */
    public function createPrice($plan = [])
    {
        global $rlPayment, $config;

        $error = '';

        $table = $this->getPlanTable();
        $phraseKey = $table . '+name+' . $plan['Key'];

        try {
            $product = $this->stripe->products->create([
                'name' => $GLOBALS['lang'][$phraseKey],
            ]);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        if ($product->id) {
            try {
                $price = $this->stripe->prices->create([
                    'unit_amount' => $rlPayment->getOption('total') * 100,
                    'currency' => $config['system_currency_code'],
                    'recurring' => ['interval' => $plan['sPlan']['Period']],
                    'product' => $product->id,
                ]);
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        if ($error) {
            file_put_contents(RL_PLUGINS . 'stripe/errors.log', "\n" . date('Y.m.d H:i:s') . ': ' . $error, FILE_APPEND);
        }

        if ($price->id) {
            $update = [
                'fields' => [
                    'stripe_key' => $price->id,
                ],
                'where' => ['ID' => $plan['ID']],
            ];

            $GLOBALS['rlDb']->updateOne($update, $table);

            return $price->id;
        }

        return false;
    }

    /**
     * Get plan table
     *
     * @since 2.2.0
     *
     * @return string
     */
    public function getPlanTable()
    {
        $service = $GLOBALS['rlPayment']->getOption('service');

        if (in_array($service, array('listing', 'package', 'featured'))) {
            $table = 'listing_plans';
        } elseif ($service == 'banner') {
            $table = 'banner_plans';
        } elseif ($service == 'membership') {
            $table = 'membership_plans';
        }

        return $table;
    }

    /**
     * Seller connect to Stripe
     *
     * @since 2.2.0
     *
     * @return array
     */
    public function connectToStripe()
    {
        global $rlPayment, $config, $rlAccount, $reefless;

        $error = '';

        $accountID = $_SESSION['account'] ? $_SESSION['account']['ID'] : (int) $_SESSION['registration']['account_id'];

        $profile = $rlAccount->getProfile((int) $accountID);
        $country = $this->getCountryCode($profile['Fields']['country']['value']);

        if (empty($country)) {
            $country = $profile['Fields']['country']['value'];
        }

        try {
            $request = [
                'type' => 'express',
                'country' => $country ? $country : 'US',
                'email' => $profile['Mail'],
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
            ];
            if ($config['shc_escrow']) {
                $request['settings'] = [
                    'payouts' => [
                        'schedule' => [
                            'interval' => 'manual'
                        ]
                    ]
                ];
            }
            $account = $this->stripe->accounts->create($request);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        if ($account->id) {
            try {
                $connectKey = base64_encode($account->id);
                $accountLink = $this->stripe->accountLinks->create([
                    'account' => $account->id,
                    'refresh_url' => $reefless->getPageUrl('my_profile'),
                    'return_url' => $reefless->getPageUrl('my_profile', ['connect' => 'stripe'], false, 'key=' . $connectKey),
                    'type' => 'account_onboarding',
                ]);
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        if ($error) {
            file_put_contents(RL_PLUGINS . 'stripe/errors.log', "\n" . date('Y.m.d H:i:s') . ': ' . $error, FILE_APPEND);
        }

        if ($accountLink->url) {
            if ($config['shc_module'] || $config['shc_module_auction']) {
                $GLOBALS['rlShoppingCart']->saveAccountSettings([
                    'stripe_account_id' => $account->id,
                ]);
            }

            return ['account' => $accountLink, 'accountLink' => $accountLink];
        }

        return ['error' => $error];
    }

    /**
     * Seller connect to Stripe
     *
     * @since 2.2.0
     * @param string $id
     *
     * @return object
     */
    public function getConnectedAccount($id = '')
    {
        global $errors;

        if (!$id) {
            return [];
        }

        try {
            $account = $this->stripe->accounts->retrieve($id, []);

            if ($account->id) {
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }

        return $account;
    }

    /**
     * Create a single-use login link to Stripe
     *
     * @since 2.2.0
     * @param string $id
     *
     * @return object
     */
    public function createLoginLink($id = '')
    {
        global $errors;

        if (!$id) {
            return [];
        }

        try {
            $loginInfo = $this->stripe->accounts->createLoginLink($id, []);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }

        return $loginInfo;
    }

    /**
     * Delete account
     *
     * @since 2.2.0
     * @param int $accountID
     *
     * @return object
     */
    public function deleteAccount($accountID = 0)
    {
        global $rlShoppingCart;

        $options = $rlShoppingCart->getAccountOptions($accountID);
        if (!$options) {
            return [];
        }

        $result = new stdClass();

        try {
            $result = $this->stripe->accounts->delete($options['stripe_account_id'], []);

            if ($result->deleted) {
                $rlShoppingCart->saveAccountSettings([
                    'stripe_account_confirmed' => '0',
                    'stripe_account_id' => '',
                ]);
            }
        } catch (Exception $e) {
            $result->error = $e->getMessage();
        }

        return $result;
    }

    /**
     * Set realPayout price to payout
     *
     * @since 2.3.0
     *
     * @param object $intent
     * @param string $accountID
     * @param int    $itemID
     * @param string $service
     *
     * @return object
     */
    public function setRealPayoutPrice($intent, $stripeAccountID = '', $itemID = 0, $service = '')
    {
        global $config;

        $this->init();

        if (!$stripeAccountID || !$itemID) {
            return;
        }

        $transaction = $this->stripe->balanceTransactions->retrieve(
            $intent->charges->data[0]->balance_transaction,
            [],
            ['stripe_account' => $stripeAccountID]
        );

        if ($service && in_array($service, ['shopping', 'auction'])
            && $config['shc_method'] == 'multi'
            && $config['shc_commission_enable']
            && $config['shc_escrow']
        ) {
            $update = [
                'fields' => [
                    'Total_payout' => round((float) $transaction->net / 100, 2),
                ],
                'where' => ['ID' => $itemID],
            ];
            $GLOBALS['rlDb']->updateOne($update, 'shc_orders');
        }

        if ($config['stripe_test_mode']) {
            file_put_contents(RL_PLUGINS . 'stripe/transaction.log', print_r($transaction, true), FILE_APPEND);
        }
    }

    /**
     * @deprecated 2.1.0
     *
     * Get card types
     *
     * @param bool $output
     * @return array
     */
    public function getCardTypes($output = true)
    {}

    /**
     * @deprecated 2.1.0
     *
     * Check subscription mode
     *
     * @return bool
     */
    public function isSubscription()
    {}

    /**
     * @deprecated 2.2.0
     *
     * Create single payment
     */
    public function createSinglePayment()
    {}

    /**
     * @deprecated 2.2.0
     *
     * Create subscription plan
     *
     * @since 2.1.0 - Added $table param; deleted $key, $name and $total params
     *
     * @param array $plan_info
     * @param string $period
     * @param string $table
     *
     * @return array
     */
    public function createSubscriptionPlan($plan_info = array(), $period = 'month', $table = '')
    {}

    /**
     * @deprecated 2.2.0
     *
     * Delete subscription plan
     *
     * @since 2.1.0 - Param $key changed to $plan_info
     *
     * @param array $plan_info
     */
    public function deleteSubscriptionPlan($plan_info = array(), $table = '')
    {}

    /**
     * @deprecated 2.2.0
     *
     * Get plans available on stripe service
     */
    public function getPlans()
    {}
}
