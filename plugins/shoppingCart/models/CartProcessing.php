<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: CARTPROCESSING.PHP
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

namespace ShoppingCart;

use Flynax\Utils\Valid;
use \ShoppingCart\Orders;
use \ShoppingCart\Shopping;

/**
 * @since 3.0.0
 */
class CartProcessing extends \Flynax\Abstracts\AbstractSteps
{
    /**
     * Order options
     *
     * @var array
     */
    public $options;

    /**
     * Dealer ID
     *
     * @var int
     */
    public $dealer;

    /**
     * Cart details
     *
     * @var array
     */
    public $cart;

    /**
     * Single seller mode in card processing
     *
     * @since 3.1.1
     *
     * @var boolean
     */
    public $singleSeller = false;

    /**
     * Initialize cart processing
     *
     * @param array $page_info
     */
    public function init(&$page_info = null, &$account_info = null, &$errors = null)
    {
        global $bread_crumbs, $lang, $sError, $reefless, $rlSmarty;

        // Initialize model
        parent::init();

        // check availability of step
        if (!defined('IS_LOGIN') && $this->step != 'cart' && $this->step != 'auth') {
            $sError = true;

            $redirect = $reefless->getPageUrl('shc_my_shopping_cart', array('step' => $this->steps['auth']['path']));
            $reefless->redirect(null, $redirect);
            exit;
        }

        // set dealer
        if ($_POST['dealer']) {
            $this->setDealer($_POST['dealer']);
        }

        // set bread crumbs
        if (!empty($this->step)) {
            if ($this->step != 'cart') {
                $bread_crumbs[] = array(
                    'name' => $lang['shc_step_' . $this->step],
                );
            }

            $step_name = $this->step == 'checkout' ? 'checkout' : 'shc_step_' . $this->step;
            $rlSmarty->assign('step_name', $lang[$step_name]);
        }

        // get cart
        $this->cart = $this->getCart();

        $this->skipShipping();

        if (!$this->cart) {
            $rlSmarty->assign('no_access', true);
        }

        $rlSmarty->assign('order_key', $this->getOrderKey());
        $rlSmarty->assign('single_seller', $this->singleSeller);
    }

    /**
     * Set order option
     *
     * @param string $key
     * @param string $value
     */
    public function setOption($key = '', $value = '')
    {
        $this->options[$key] = $_SESSION['shc_options'][$key] = $value;
    }

    /**
     * Get order option
     *
     * @param  string $key
     * @return mixed
     */
    public function getOption($key = '')
    {
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }
        return null;
    }

    /**
     * Get dealer ID
     *
     * @return int
     */
    public function getDealer()
    {
        return $this->dealer;
    }

    /**
     * Set dealer ID
     *
     * @param int $id
     */
    public function setDealer($id = 0)
    {
        $this->dealer = (int) $id;
    }

    /**
     * Step cart
     */
    public function stepCart()
    {
        global $errors, $shc_steps, $account_info;

        $this->loadCartInstance();

        $shopping = new Shopping();

        if ($_POST['form']) {
            if (!empty($_POST['quantity'])) {
                $this->updateCart($_POST['quantity']);
            }

            $orders = new Orders();
            $orderInfo = $orders->get($this->getOrderKey());
            $extend = [];
            $orderID = null;

            if ($orderInfo) {
                $orderID = $orderInfo['ID'];
                $update = array(
                    'fields' => array(
                        'Total' => (float) $this->cart['total'],
                    ),
                    'where' => array('ID' => $orderInfo['ID']),
                );

                $orders->update($update);
                $orders->setID($orderInfo['ID']);
            } else {
                if (!$this->checkDealerSubstitution()) {
                    $errors[] = 'Hack detected; dealer is substituted';
                    return;
                }

                $orderKey = $shopping->getOrderKey();

                if ($this->getDealer()) {
                    $orderKey .= '-D' . $this->getDealer();
                }
                $order = array(
                    'Type' => 'shopping',
                    'Order_key' => $orderKey,
                    'Total' => (float) $this->cart['total'],
                    'Dealer_ID' => $this->getDealer(),
                    'Buyer_ID' => (int) $GLOBALS['account_info']['ID'],
                    'Date' => 'NOW()',
                );

                $orders->create($order);
                $orderID = $orders->getID();
            }

            // Complete the order with cash payment option if the shipping step is not available
            if (!$shc_steps['checkout'] && !$shc_steps['shipping'] && $account_info) {
                $orders->completeByCash($orderID, (int) $account_info['ID']);
                $extend = [
                    'type' => 'param',
                    'data' => '?item=' . $orderID . '&completed',
                    'key' => 'item',
                    'value' => $orderID . '&completed'
                ];
            }

            $this->redirectToNextStep($extend);
        } else {
            // Update cart items in cookies
            $shopping->updateCookie($shopping->getItems());
        }
    }

    /**
     * Step auth
     */
    public function stepAuth()
    {
        global $errors, $shc_steps;

        if (!empty($_POST['form'])) {
            $this->auth();

            if (!$errors) {
                $extend = [];
                $order_id = (int) $_SESSION['shc_options']['ID'];
                $account_id = (int) $_SESSION['account']['ID'];

                if ($order_id) {
                    $update = array(
                        'fields' => array(
                            'Buyer_ID' =>  $account_id,
                        ),
                        'where' => array('ID' => $order_id),

                    );
                    $GLOBALS['rlDb']->update($update, 'shc_orders');
                }

                // Complete the order with cash payment option if the shipping step is not available
                if (!$shc_steps['checkout'] && !$shc_steps['shipping']) {
                    $orders = new Orders();
                    $orders->completeByCash($order_id, $account_id);
                    $extend = [
                        'type' => 'param',
                        'data' => '?item=' . $order_id . '&completed',
                        'key' => 'item',
                        'value' => $order_id . '&completed'
                    ];
                }

                $this->redirectToNextStep($extend);
            }
        }

        $GLOBALS['rlStatic']->addHeaderCss(
            RL_TPL_BASE . 'components/auth-form-section/auth-form-section.css',
            $GLOBALS['page_info']['Controller']
        );
    }

    /**
     * Step shipping
     */
    public function stepShipping()
    {
        global $lang, $rlSmarty, $errors, $account_info, $config, $shc_steps;

        $this->loadCartInstance();

        $shipping = new Shipping();
        $shipping->init();

        $orders = new Orders();
        $orderInfo = $orders->get($this->getOrderKey());

        if (!$_POST['form']) {
            $profile = $GLOBALS['rlAccount']->getProfile((int) $account_info['ID']);

            if ($profile['Fields']) {
                $location_fields = ['location', 'location_level2', 'location_level3'];
                $field_index = 0;

                foreach ($profile['Fields'] as $field) {
                    if ($field['Condition']
                        && $field['Condition'] == $GLOBALS['rlMultiField']->geoFormatData['Key']
                        && $GLOBALS['rlShoppingCart']->allowMultifield
                        && is_object($GLOBALS['rlMultiField'])
                    ) {
                        $simulate_multifields['fields'][$location_fields[$field_index]] = $account_info[$field['Key']];
                        $field_index++;
                    } elseif ($account_info[$field['Key']]) {
                        $simulate_multifields['fields'][$field['Key']] = $account_info[$field['Key']];
                    }
                }

                $orderInfo['Shipping_options'] = $simulate_multifields;
            }

            $shipping->simulatePost($orderInfo, $this->cart['items']);
        }

        $rlSmarty->assign('multi_format_keys', explode('|', $GLOBALS['config']['mf_format_keys']));
        $rlSmarty->assign('mf_form_prefix', 'f');

        if (!isset($lang['shc_shipping_price'])) {
            $lang = array_merge($lang, $GLOBALS['rlShoppingCart']->getPhrases(['add_listing', 'shopping_cart']));
        }

        if ($_POST['form']) {
            $this->validate();

            if (!$errors) {
                $extend = [];
                $result = $orders->saveShippingData($_POST['f'], $this->cart);

                if ($result && !$errors) {
                    // Complete the order with cash payment option
                    if (!$shc_steps['checkout']) {
                        $orders->completeByCash($orderInfo['ID'], (int) $GLOBALS['account_info']['ID']);
                        $extend = [
                            'type' => 'param',
                            'data' => '?item=' . $orderInfo['ID'] . '&completed',
                            'key' => 'item',
                            'value' => $orderInfo['ID'] . '&completed'
                        ];
                    }

                    $this->redirectToNextStep($extend);
                }
            }
        }
    }

    /**
     * Step confirmation
     */
    public function stepConfirmation()
    {
        $this->loadCartInstance();

        $orders = new Orders();
        $orderInfo = $orders->get($this->getOrderKey());
        $GLOBALS['rlSmarty']->assign_by_ref('order_info', $orderInfo);

        if ($_POST['form']) {
            $this->redirectToNextStep();
        }
    }

    /**
     * Step checkout
     */
    public function stepCheckout()
    {
        global $rlPayment, $sError, $shc_steps, $reefless, $config, $lang, $errors, $account_info;

        $this->loadCartInstance();

        $orderKey = $this->getOrderKey();

        if ($_GET['item']) {
            $orderKey = (int) $_GET['item'];
        }

        $orders = new Orders();
        $orderInfo = $orders->get($orderKey);

        if (!$orderInfo || $orderInfo['Buyer_ID'] != $account_info['ID']) {
            $sError = true;
            return;
        }

        if ($orderInfo['Status'] == 'paid') {
            $this->redirectToNextStep();
            return;
        }

        if (!isset($lang['shc_shipping_price'])) {
            $lang = array_merge($lang, $GLOBALS['rlShoppingCart']->getPhrases(['add_listing', 'shopping_cart']));
        }

        $dealerID = $this->getDealer();

        if (!$dealerID && $orderInfo) {
            $dealerID = $orderInfo['Dealer_ID'];
        }

        if ($orderInfo['Bank_transfer'] && $_POST['gateway'] != 'bankWireTransfer') {
            $update = array(
                'fields' => array('Bank_transfer' => '0'),
                'where' => array(
                    'ID' => $rlPayment->getOption('item_id')
                )
            );

            $GLOBALS['rlDb']->updateOne($update, 'shc_orders');
        }

        if ($_POST['step'] == 'checkout' && $_POST['gateway'] == 'cash') {
            $options = [];
            if ($config['shc_method'] == 'multi') {
                $options = $GLOBALS['rlShoppingCart']->getAccountOptions($dealerID);
            }

            if (!$config['shc_allow_cash'] || ($config['shc_method'] == 'multi' && !$options['allow_cash'])) {
                $errors[] = $lang['shc_cash_unvailable'];
                return;
            }

            $orders->completeByCash($orderInfo['ID'], (int) $GLOBALS['account_info']['ID']);
            $extend = [
                'type' => 'param',
                'data' => '?item=' . $orderInfo['ID'] . '&completed',
                'key' => 'item',
                'value' => $orderInfo['ID'] . '&completed'
            ];
            $this->redirectToNextStep($extend);
            return;
        }

        $GLOBALS['rlSmarty']->assign_by_ref('order_info', $orderInfo);

        if (!$rlPayment->isPrepare()) {
            $success_url = $reefless->getPageUrl('shc_my_shopping_cart', array('step' => $shc_steps['done']['path']));
            $success_url .= $config['mod_rewrite'] ? '?' : '&';
            $success_url .= 'item=' . $orderInfo['ID'] . '&completed';

            $cancel_url = $reefless->getPageUrl('shc_my_shopping_cart', array('step' => $shc_steps['checkout']['path']));
            $cancel_url .= $config['mod_rewrite'] ? '?' : '&';
            $cancel_url .= 'item=' . $orderInfo['ID'] . '&canceled';

            $item = $lang['shc_order_key'] .' ' . $orderInfo['Order_key'] . ' (#' . $orderInfo['ID'] . ')';

            $rlPayment->clear();

            // set payment options
            $rlPayment->setOption('service', 'shopping');
            $rlPayment->setOption('total', $orderInfo['Total_source']);
            $rlPayment->setOption('item_id', $orderInfo['ID']);
            $rlPayment->setOption('item_name', $item);
            $rlPayment->setOption('account_id', (int) $GLOBALS['account_info']['ID']);
            $rlPayment->setOption('callback_class', 'rlShoppingCart');
            $rlPayment->setOption('callback_method', 'completeOrder');
            $rlPayment->setOption('cancel_url', $cancel_url);
            $rlPayment->setOption('success_url', $success_url);
            $rlPayment->setOption('plugin', 'shoppingCart');

            // set commission value
            if ($orderInfo['commission']) {
                $rlPayment->setOption('commission', $orderInfo['commission']);
            }

            // set dealer ID
            if ($config['shc_method'] == 'multi') {
                $rlPayment->setOption('dealer_id', $dealerID);
            }

            $rlPayment->init($errors);
        } else {
            $rlPayment->checkout($errors);
        }
    }

    /**
     * Step done
     */
    public function stepDone()
    {
        global $sError, $rlSmarty, $no_access, $config;
        $itemID = (int) $_GET['item'];

        $orders = new Orders();
        $shopping = new Shopping();

        $orderInfo = $orders->get($itemID);

        if ($orderInfo) {
            $shopping->clearCookie(true);
            $rlSmarty->assign('shcItems', $shopping->getItems($config['shc_count_items_block']));
            $rlSmarty->assign_by_ref('shcTotalInfo', $shopping->getTotalInfo());

            $no_access = true;
            $rlSmarty->assign('shcIsPaid', $orderInfo['Status'] == 'paid' ? true : false);
        } else {
            $sError = true;
        }
    }

    /**
     * Get cart details
     *
     * @return array
     */
    public function getCart()
    {
        global $rlDb, $config, $account_info, $lang;

        $orders = new Orders();
        $shopping = new Shopping();
        $order_key = $shopping->getOrderKey();
        $total = 0;
        $commission = 0;
        $shipping_fixed_price = 0;

        $sql = "SELECT * FROM `{db_prefix}shc_order_details` WHERE `Order_key` = '{$order_key}' ";

        if ($this->step == 'checkout' && $orders->getID()) {
            // solution for bankWireTransfer
            $sql .= "AND (`Order_ID` = {$orders->getID()} OR `Status` <> 'completed') ";
        } else {
            $sql .= "AND `Status` <> 'completed' ";
        }
        if ($GLOBALS['config']['shc_method'] == 'multi' && $this->getDealer()) {
            $sql .= "AND `Dealer_ID` = '{$this->getDealer()}' ";
        }

        $sql .= "ORDER BY `Date` DESC";

        $items = $rlDb->getAll($sql);
        $unavailable_count = 0;
        $seller_ids = [];

        if ($items) {
            foreach ($items as $iKey => $iVal) {
                $listing = $GLOBALS['rlListings']->getListing($iVal['Item_ID'], true);

                if (!$listing) {
                    $update = array(
                        'fields' => array(
                            'Status' => 'deleted',
                        ),
                        'where' => array('ID' => $iVal['ID']),
                    );

                    $rlDb->updateOne($update, 'shc_order_details');
                    $items[$iKey]['Status'] = 'deleted';
                }

                $listing_options = $rlDb->fetch(
                    '*',
                    array('Listing_ID' => $iVal['Item_ID']),
                    null,
                    1,
                    'shc_listing_options',
                    'row'
                );

                $seller_ids[] = $listing['Account_ID'];

                $items[$iKey]['shc_available'] = $listing['shc_available'];
                if ($items[$iKey]['Status'] == 'deleted'
                    || ($listing_options['Quantity_real'] <= 0 && !$iVal['Digital'])
                    || $iVal['Dealer_ID'] == $account_info['ID']
                ) {
                    $items[$iKey]['shc_available'] = false;
                    $unavailable_count++;
                } else {
                    $unavailable_count += $listing['shc_available'] ? 0 : 1;
                }

                $items[$iKey]['main_photo'] = $listing['Main_photo'];
                $items[$iKey]['shc_quantity'] = $listing['shc_quantity'];
                $items[$iKey]['weight'] = $listing_options['Weight'];
                $items[$iKey]['commission'] = $listing_options['Commission'];
                $items[$iKey]['listing_link'] = $listing['listing_link'];

                // Recalculate quantity
                if (!$listing_options['Digital'] && $iVal['Quantity'] > $listing['shc_quantity']) {
                    $items[$iKey]['Quantity'] = $iVal['Quantity'] = $listing['shc_quantity'];
                    $items[$iKey]['Quantity_changed'] = true;
                }

                // shipping
                if ($listing_options) {
                    $items[$iKey]['shipping_item_options'] = unserialize($iVal['Shipping_item_options']);
                    $items[$iKey]['services'] = unserialize($iVal['Quote']);
                    $items[$iKey]['shipping'] = unserialize($listing_options['Shipping_options']);
                    $items[$iKey]['Dimensions'] = unserialize($listing_options['Dimensions']);
                    $items[$iKey]['Weight'] = $listing_options['Weight'];
                    $items[$iKey]['Package_type'] = $listing_options['Package_type'];
                    $items[$iKey]['Shipping_price_type'] = $listing_options['Shipping_price_type'];
                    $items[$iKey]['Shipping_price'] = (float) $listing_options['Shipping_price'];
                    $items[$iKey]['Shipping_discount'] = (float) $listing_options['Shipping_discount'];
                    $items[$iKey]['Shipping_discount_at'] = (float) $listing_options['Shipping_discount_at'];
                    $items[$iKey]['Shipping_fixed_prices'] = $config['shc_shipping_price_fixed'] == 'multi' 
                    ? unserialize($listing_options['Shipping_fixed_prices']) 
                    : [];
                    $items[$iKey]['Digital'] = (int) $listing_options['Digital'];
                    $items[$iKey]['Quantity_unlim'] = (int) $listing_options['Quantity_unlim'];
                    $items[$iKey]['Shipping_method_fixed'] = $listing_options['Shipping_method_fixed'] 
                    ? explode(',', $listing_options['Shipping_method_fixed']) 
                    : [];
                }

                // calculate total price
                if ($items[$iKey]['shc_available']) {
                    $items[$iKey]['price_original'] = $GLOBALS['rlShoppingCart']->getPrice($listing[$config['price_tag_field']]);
                    $items[$iKey]['total'] = round(($iVal['Quantity'] * $iVal['Price']), 2);
                    $total += (float) $items[$iKey]['total'];
                    $commission += (float) $listing_options['Commission'];
                    $shipping_fixed_price += (float) $items[$iKey]['Shipping_fixed_price'];
                }

                if (!$config['shc_shipping_step']) {
                    $items[$iKey]['pickup_details'] = $this->getPickupDetails($listing);
                }
            }

            $this->singleSeller = count(array_unique($seller_ids)) === 1;
        }

        $cart['items'] = $items;
        $cart['total'] = round($total, 2);
        $cart['shipping_price'] = round($shipping_fixed_price, 2);
        $cart['commission'] = round($commission, 2);
        $cart['isAvailable'] = (count($items) - $unavailable_count) > 0 ? true : false;
        $cart['hasUnavailable'] = $unavailable_count > 0 ? true : false;

        return $cart;
    }

    /**
     * Get item/listing pickup details
     *
     * @since 3.1.1
     *
     * @param  array $listingData - Listing data array
     * @return array              - Location data array, address and coordinates
     */
    public function getPickupDetails(array $listingData): array
    {
        global $rlDb, $rlListings, $rlListingTypes, $lang;

        $listing_type = $rlListingTypes->types[$listingData['Listing_type']];
        $listing = $rlListings->getListingDetails($listingData['Category_ID'], $listingData, $listing_type);

        $fields = $rlListings->fieldsList;
        $location = [];

        foreach ($fields as $key => $value) {
            if ($fields[$key]['Map'] && !empty($listingData[$fields[$key]['Key']])) {
                $location['address'][] = [
                    'name' => $lang[$value['pName']],
                    'value' => $value['value']
                ];
            }
        }

        if ($listingData['Loc_latitude'] != '' && $listingData['Loc_longitude'] != '') {
            $location['coordinates'] = [
                'lat' => $listingData['Loc_latitude'],
                'lng' => $listingData['Loc_longitude'],
            ];
        }

        return $location;
    }

    /**
     * Prepare Items By Dealers
     *
     * @return array
     */
    public function prepareItemsByDealers()
    {
        $data = array();

        if (!$this->cart) {
            return $data;
        }

        // get dealers
        $dealers = array();
        foreach ($this->cart['items'] as $iKey => $iVal) {
            if (!in_array($iVal['Dealer_ID'], $dealers)) {
                $dealers[] = $iVal['Dealer_ID'];
            }
        }

        foreach ($dealers as $dkey => $dVal) {
            $total = 0;
            $dealer_info = $GLOBALS['rlDb']->fetch(
                array('ID', 'First_name', 'Last_name', 'Username'),
                array('ID' => $dVal),
                null,
                1,
                'accounts',
                'row'
            );

            if ($dealer_info) {
                $dealer_info['Full_name'] = $dealer_info['First_name'] && $dealer_info['Last_name']
                ? $dealer_info['First_name'] . ' ' . $dealer_info['Last_name']
                : $dealer_info['Username'];

                $dealer_info['Full_name'] = trim($dealer_info['Full_name']);
            }

            foreach ($this->cart['items'] as $iKey => $iVal) {
                if ($iVal['Dealer_ID'] == $dVal) {
                    $data[$dVal]['items'][] = $iVal;
                    $total += $iVal['Price'] * $iVal['Quantity'];
                }
            }

            $data[$dVal]['total'] = round($total, 2);
            $data[$dVal]['ID'] = $dVal;
            $data[$dVal]['Full_name'] = $dealer_info['Full_name'];

            $dealer_info = array();
        }

        return $data;
    }

    /**
     * Update cart
     *
     * @param array $data
     */
    public function updateCart($data = array())
    {
        global $rlDb, $config;

        if (!$data) {
            return;
        }

        foreach ($this->cart['items'] as $key => $iVal) {
            if (($iVal['Dealer_ID'] != $this->getDealer() && $GLOBALS['config']['shc_method'] == 'multi') || !$iVal['shc_available']) {
                continue;
            }

            if (isset($data[$iVal['ID']])) {
                $update = array(
                    'fields' => array('Quantity' => $data[$iVal['ID']]),
                    'where' => array('ID' => $iVal['ID']),
                );

                $rlDb->updateOne($update, 'shc_order_details');

                // update listings
                if ($iVal['Quantity'] != $data[$iVal['ID']] && $config['shc_items_cart_duration'] != 'unlimited') {
                    if ($iVal['Quantity'] < $data[$iVal['ID']]) {
                        $quantity = $data[$iVal['ID']] - $iVal['Quantity'];
                        $sql = "UPDATE `{db_prefix}listings` SET `shc_quantity` = `shc_quantity` - {$quantity} WHERE `ID` = '{$iVal['Item_ID']}' LIMIT 1";
                    } else {
                        $quantity = $iVal['Quantity'] - $data[$iVal['ID']];
                        $sql = "UPDATE `{db_prefix}listings` SET `shc_quantity` = `shc_quantity` + {$quantity} WHERE `ID` = '{$iVal['Item_ID']}' LIMIT 1";
                    }

                    $rlDb->query($sql);
                }
            }
        }

        $shopping = new Shopping();
        $items = $shopping->getItems($config['shc_count_items_block']);
        $shopping->updateCookie($items);
    }

    /**
     * Handler auth step
     */
    public function auth()
    {
        global $rlAccount, $rlSmarty, $account_info, $errors, $error_fields, $rlHook, $rlDb, $config, $lang;

        if (defined('IS_LOGIN')) {
            return;
        }

        $quick_auth = false;
        $login_data = $_POST['login'];
        $register_data = $_POST['register'];

        if ($register_data['email'] && !isset($register_data['name'])) {
            $exp_email = explode('@', $register_data['email']);
            $register_data['name'] = $rlAccount->makeUsernameUnique($exp_email[0]);
        }

        // Login
        if ($login_data['username'] && $login_data['password']) {
            $quick_auth = true;

            if (true === $response = $rlAccount->login($login_data['username'], $login_data['password'])) {
                $account_info = $_SESSION['account'];

                $rlSmarty->assign('isLogin', $account_info['Full_name']);
                define('IS_LOGIN', true);
            } else {
                $errors = array_merge($errors, $response);
                $error_fields .= 'login[username],login[password],';
            }
        }
        // Register
        elseif ($register_data['name'] && $register_data['email']) {
            $quick_auth = true;

            // Validate email
            if (!Valid::isEmail($register_data['email'])) {
                $errors[] = $lang['notice_bad_email'];
                $error_fields .= 'register[email],';
            }
            // Check for duplicate email
            elseif ($rlDb->getOne('ID', "`Mail` = '{$register_data['email']}' AND `Status` <> 'trash'", 'accounts')) {
                $errors[] = str_replace('{email}', $register_data['email'], $lang['notice_account_email_exist']);
                $error_fields .= 'register[email],';
            }

            $rlHook->load('phpAddListingQuickRegistrationValidate', $register_data, $errors, $error_fields);

            if (!$errors) {
                if ($new_account = $rlAccount->quickRegistration(
                    $register_data['name'],
                    $register_data['email'],
                    false,
                    $register_data['type'])
                ) {
                    $rlAccount->login($new_account[0], $new_account[1]);

                    $account_info = $_SESSION['account'];
                    $rlSmarty->assign('isLogin', $account_info['Full_name']);
                    define('IS_LOGIN', true);

                    $rlHook->load('phpAddListingAfterQuickRegistration', $new_account, $register_data); // >= v4.5

                    // Send login details to user
                    $GLOBALS['reefless']->loadClass('Mail');

                    $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('quick_account_created');
                    $find = array('{login}', '{password}', '{name}');
                    $replace = array($new_account[0], $new_account[1], $account_info['Full_name']);

                    $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
                    $GLOBALS['rlMail']->send($mail_tpl, $register_data['email']);
                }
            }
        }

        if ($quick_auth) {
            $rlSmarty->assign('account_info', $account_info);
        } else {
            $errors[] = $lang['quick_signup_fail'];
        }
    }

    /**
     * Load instance of cart to step
     */
    public function loadCartInstance()
    {
        global $no_access;

        // get instance of cart
        $cart_instance = $this->cart;

        if (!$cart_instance['items']) {
            $no_access = true;
        }

        if ($GLOBALS['config']['shc_method'] == 'multi') {
            $cart_instance['dealers'] = $this->prepareItemsByDealers();
        }

        $GLOBALS['rlSmarty']->assign_by_ref('cart', $cart_instance);
    }

    /**
     * Validate form fields
     */
    public function validate()
    {
        global $errors, $error_fields, $rlSmarty;

        $shipping = new Shipping();

        $fields = $shipping->getShippingFields();

        if ($fields) {
            if ($backErrors = $GLOBALS['rlCommon']->checkDynamicForm($_POST['f'], $fields, 'f')) {
                foreach ($backErrors as $error) {
                    $errors[] = $error;
                    $rlSmarty->assign('fixed_message', true);
                }
            }
        }

        // validate shipping method for each item
        foreach ($this->cart['items'] as $iKey => $iValue) {
            if ($iValue['Digital'] || !$iValue['shc_available']) {
                continue;
            }

            if ($iValue['Shipping_price_type'] == 'fixed' && $GLOBALS['config']['shc_shipping_price_fixed'] == 'multi') {
                if ($_POST['items'][$iValue['ID']]['fixed_price'] == '') {
                    $errors[] = str_replace('{field}', '<span class="field_error">' . $GLOBALS['lang']['shc_shipping_price'] . ' (' . $iValue['Item'] . ')</span>', $GLOBALS['lang']['notice_select_empty']);
                    $error_fields[] .= "items[{$iValue['ID']}][fixed_price]";
                }
            } elseif ($iValue['Shipping_price_type'] == 'calculate') {
                if (empty($_POST['items'][$iValue['ID']]['method'])) {
                    $errors[] = str_replace('{field}', '<span class="field_error">' . $GLOBALS['lang']['shc_shipping_method'] . ' (' . $iValue['Item'] . ')</span>', $GLOBALS['lang']['notice_select_empty']);
                    $error_fields[] .= "items[{$iValue['ID']}][method]";
                }
            }
        }
    }

    /**
     * Get dealer details
     *
     * @param int $dealerID
     * @return data
     */
    public function getDealerInfo($dealerID = 0)
    {
        $dealerID = $dealerID ?: $this->getDealer();
        $dealer_info = $GLOBALS['rlAccount']->getProfile($dealerID);

        if ($dealer_info) {
            $dealer_info['shipping'] = unserialize(trim($dealer_info['Shipping']));
        }

        return $dealer_info;
    }

    /**
     * Skip Shipping step if cart contain only digital products
     */
    public function skipShipping()
    {
        global $config, $shc_steps;

        if (!$config['shc_digital_product']) {
            return; 
        }

        $notDigital = false;

        foreach ($this->cart['items'] as $key => $value) {
            if (!$value['digital']) {
                $notDigital = true;
                break;
            }
        }

        if (!$notDigital) {
            unset($shc_steps['shipping'], $this->steps['shipping']);
        }
    }

    /**
     * Check dealer substitution
     * Prevent hack attack
     *
     * @return bool
     */
    public function checkDealerSubstitution()
    {
        $exists = false;

        foreach ($this->cart['items'] as $key => $iVal) {
            if ($iVal['Dealer_ID'] == $this->getDealer()) {
                $exists = true;
            }
        }

        return $exists;
    }

    /**
     * Get order key
     *
     * @return string
     */
    public function getOrderKey()
    {
        $shopping = new Shopping();
        $orderKey = $shopping->getOrderKey();
        if ($this->getDealer()) {
            $orderKey .= '-D' . $this->getDealer();
        }

        return $orderKey;
    }
}
