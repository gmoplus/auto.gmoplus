<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLSHOPPINGCART.CLASS.PHP
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

use \ShoppingCart\Auction;
use \ShoppingCart\Currency;
use \ShoppingCart\MyAuctions;
use \ShoppingCart\Orders;
use \ShoppingCart\Payment;
use \ShoppingCart\PriceFormat;
use \ShoppingCart\Shipping;
use \ShoppingCart\Shopping;
use \ShoppingCart\PrintOrder;
use \ShoppingCart\Escrow;
use \ShoppingCart\EscrowTest;

class rlShoppingCart
{
    /**
     * Is multifield plugin installed and configured in shopping cart
     *
     * @since 3.1.0
     * @var boolean
     */
    public $allowMultifield = false;

    /**
     * List of system payment gateways
     * @since 3.1.0
     * @var string[]
     */
    public $systemPaymentGateways = ['paypal', '2co', 'yoomoney'];

    /**
     * rlShoppingCart constructor
     */
    public function __construct()
    {
        self::boot();
        $this->checkMultifield();
    }

    /**
     * Check is multifield installed and configured
     *
     * @since 3.1.0
     */
    public function checkMultifield()
    {
        global $config;

        if ($GLOBALS['plugins']['multiField']
            && $config['shc_use_multifield']
            && !$config['shc_shipping_calc']
            && $config['mf_geo_data_format']) {
            $this->allowMultifield = true;
        }

        if (is_object($GLOBALS['rlSmarty'])) {
            $GLOBALS['rlSmarty']->assign('allowMultifield', $this->allowMultifield);
        }
    }

    /**
     * Initialize autoload file
     */
    public static function boot()
    {
        // Need checking on existing the file because at the time of the update the file may not exist.
        if (file_exists(RL_PLUGINS . 'shoppingCart/vendor/autoload.php')) {
            require_once __DIR__ . '/vendor/autoload.php';
        }
    }

    /**
     * @hook apTplHeader
     */
    public function hookApTplHeader()
    {
        global $controller;

        $list = ['shopping_cart', 'listings'];

        if (!in_array($controller, $list)) {
            return;
        }

        $url = RL_PLUGINS_URL . 'shoppingCart/static/';
        echo '<link href="' . $url . 'style_admin.css" type="text/css" rel="stylesheet" />';

        switch ($controller) {
            case 'shopping_cart':
                echo '<script src="' . $url . 'lib_admin.js"></script>';
                break;

            case 'listings':
                echo '<script src="' . $url . 'lib_admin_listings.js"></script>';
                break;
        }
    }

    /**
     * @hook staticDataRegister
     *
     * @param \rlStatic $rlStatic
     */
    public function hookStaticDataRegister(&$rlStatic)
    {
        global $config;

        $pagesJS = [
            'add_listing',
            'edit_listing',
            'my_listings',
            'listing_details',
            'profile',
            'my_shopping_cart',
            'my_auctions',
            'auction_payment',
            'my_items_sold',
            'my_purchases',
        ];

        $rlStatic->addJS(RL_PLUGINS_URL . 'shoppingCart/static/lib_basic.js');
        $rlStatic->addJS(RL_PLUGINS_URL . 'shoppingCart/static/lib.js', $pagesJS);

        if ($this->allowMultifield) {
            $rlStatic->addJS(RL_PLUGINS_URL . 'multiField/static/lib.js', $pagesJS);
        }

        $rlStatic->addHeaderCSS(RL_PLUGINS_URL . 'shoppingCart/static/my-cart.css', [
            'my_shopping_cart',
            'my_items_sold',
            'my_purchases',
            'my_auctions'
        ]);
        $rlStatic->addHeaderCSS(RL_PLUGINS_URL . 'shoppingCart/static/manage-listing.css', ['add_listing', 'edit_listing']);
        $rlStatic->addHeaderCSS(RL_PLUGINS_URL . 'shoppingCart/static/my-listings.css', 'my_listings');
        $rlStatic->addHeaderCSS(RL_PLUGINS_URL . 'shoppingCart/static/details.css', ['listing_details', 'add_listing']);
    }

    /**
     * @hook tplHeader
     */
    public function hookTplHeader()
    {
        if (!self::isConfigured()) {
            return;
        }

        self::loadTpl('header');
    }

    /**
     * @hook  tplFooter
     */
    public function hookTplFooter()
    {
        if (!self::isConfigured()) {
            return;
        }

        if (version_compare($GLOBALS['plugins']['currencyConverter'], '3.0.0') >= 0) {
            $currencyConverter = true;
        } else {
            $currencyConverter = false;
        }

        $GLOBALS['rlSmarty']->assign_by_ref('currencyConverter', $currencyConverter);

        self::loadTpl('footer');
    }

    /**
     * @hook tplHeaderUserNav
     */
    public function hookTplHeaderUserNav()
    {
        global $tpl_settings, $page_info;

        // Don't display cart in top on Order Processing page
        if ($page_info['Controller'] == 'my_shopping_cart') {
            return;
        }

        if (version_compare($GLOBALS['config']['rl_version'], '4.8.2') <= 0
            && $tpl_settings['name'] == 'general_cragslist_wide'
        ) {
            return;
        }

        if ($tpl_settings['name'] == 'general_cragslist_wide') {
            self::loadTpl('cart');
        }
    }

    /**
     * @hook tplHeaderUserArea
     *
     * @since 3.0.0
     */
    public function hookTplHeaderUserArea()
    {
        global $tpl_settings, $page_info;

        // Don't display cart in top on Order Processing page
        if ($page_info['Controller'] == 'my_shopping_cart') {
            return;
        }

        if (!self::isConfigured()) {
            return;
        }

        if ($tpl_settings['name'] == 'general_cragslist_wide') {
            return;
        }

        self::loadTpl('cart');
    }

    /**
     * @hook  pageinfoArea
     */
    public function hookPageinfoArea()
    {
        global $deny_pages, $config;

        if (!empty($config['shc_account_types'])) {
            $allowed_account_types = explode(",", $config['shc_account_types']);

            if (!in_array($GLOBALS['account_info']['Type'], $allowed_account_types)) {
                $deny_pages[] = 'shc_my_items_sold';
            }
        }
        if (!$config['shc_module_auction']) {
            $deny_pages[] = 'shc_auctions';
        }
    }

    /**
     * @hook  specialBlock
     */
    public function hookSpecialBlock()
    {
        global $config, $rlSmarty;

        if (!self::isConfigured()) {
            return;
        }

        $shopping = new Shopping();
        $shopping->synchronizeItems(isset($_GET['logout']) ? true : false);

        $shopping->setOrderKey();

        $items = isset($_SESSION['shc_items'])
        ? unserialize(trim(stripcslashes($_SESSION['shc_items'])))
        : $shopping->getItems($config['shc_count_items_block']);

        $shopping->updateCookie($items);

        $totalInfo = $_SESSION['shc_items_info'] ?: $shopping->getTotalInfo();

        $GLOBALS['rlSmarty']->assign_by_ref('shcItems', $items);
        $GLOBALS['rlSmarty']->assign_by_ref('shcTotalInfo', $totalInfo);

        if (!$config['shc_module']) {
            $account_menu = $rlSmarty->get_template_vars('account_menu');
            foreach ($account_menu as $key => $item) {
                if ($item['Key'] == 'shc_my_shopping_cart') {
                    unset($account_menu[$key]);
                    break;
                }
            }
            $rlSmarty->assign_by_ref('account_menu', $account_menu);
        }

        $name = $GLOBALS['tpl_settings']['name'];
        $GLOBALS['rlSmarty']->assign('sc_is_nova', boolval(strpos($name, '_nova')));
        $GLOBALS['rlSmarty']->assign('sc_is_flatty', (strpos($name, '_flatty') + 7) == strlen($name));
        $GLOBALS['rlSmarty']->assign('sc_hide_name', in_array($name, ['escort_sun_cocktails_wide', 'general_cragslist_wide']));
    }

    /**
     * @hook  listingDetailsBottom
     */
    public function hookListingDetailsBottom()
    {
        global $listing_data, $lang, $rlStatic, $page_info, $rlSmarty, $rlDb;

        // if listing preview step
        if ($page_info['Controller'] == 'add_listing') {
            global $instance, $listing;

            if (is_object($instance)) {
                $listing_data = $instance->listingData;
            }
            $listingTmp = $rlDb->fetch('*', array('ID' => $listing_data['ID']), null, null, 'listings', 'row');

            foreach($listingTmp as $k => $v) {
                if (substr_count($k, 'shc_') > 0) {
                    $listing_data[$k] = $v;
                }
            }
        }

        if (!self::isConfigured() || $listing_data['shc_mode'] == 'listing') {
            return;
        }

        if (!isset($lang['shc_start_price']) || $page_info['Controller'] == 'add_listing') {
            $lang = array_merge($lang, $this->getPhrases(['listing_details', 'add_listing', 'shopping_cart', 'my_shopping_cart']));
        }

        $options = $this->getListingOptions($listing_data['ID'], $listing_data);

        if (is_array($listing_data['Shipping_options'])) {
            foreach ($listing_data['Shipping_options'] as $shKey => $shValue) {
                if ($shValue['enable']) {
                    $listing_data['has_shipping'] = true;
                }
            }
        }

        if ($listing_data['shc_mode'] == 'auction' && $GLOBALS['config']['shc_module_auction']) {
            $rlStatic->addJS(RL_PLUGINS_URL . 'shoppingCart/static/moment.min.js');
            $rlStatic->addJS(RL_PLUGINS_URL . 'shoppingCart/static/moment-timezone-with-data.min.js');

            $auction = new Auction();
            $auction->adaptOptions($listing_data, $options);

            // if listing preview step
            if (isset($listing_data['shc']) && $page_info['Controller'] == 'add_listing') {
                $listing_data['shc']['Status'] = $listing_data['Status'];

                if ($listing_data['Status'] == 'incomplete') {
                    $listing_data['shc']['Status']  = 'active';
                }
            }
        }
        $rlSmarty->assign_by_ref('listing_data', $listing_data);

        $payment = new Payment();
        $payment->getGateways($listing_data['Account_ID'], true);

        $lang['shc_after_more'] = str_replace('{count}', $listing_data['Shipping_discount_at'], $lang['shc_after_more']);
        if ($listing_data['Handling_time'] > 1) {
            $lang['shc_handling_time_' . $listing_data['Handling_time']] = str_replace(
                '{number}',
                $listing_data['Handling_time'],
                $lang['shc_handling_time_n']
            );
        }
    }

    /**
     * @hook listing_details_sidebar
     */
    public function hookListing_details_sidebar()
    {
        global $lang;

        if (!self::isConfigured()) {
            return;
        }

        if (!isset($lang['shc_handling_time'])) {
            $lang = array_merge($lang, $this->getPhrases('add_listing'));
        }

        self::loadTpl('listing_details');
    }

    /**
     * @hook  listingDetailsBottomTpl
     */
    public function hookListingDetailsBottomTpl()
    {
        if (!self::isConfigured()) {
            return;
        }

        self::loadTpl('bid_history');
    }

    /**
     * @hook  addListingPreFields
     */
    public function hookAddListingPreFields()
    {
        global $reefless, $account_info, $rlAccount, $lang, $config, $rlSmarty;

        $account_types = explode(",", $config['shc_account_types']);
        $listing_type = $rlSmarty->_tpl_vars['manageListing']->listingType;

        if (!self::isConfigured()
            || (!$listing_type['shc_module'] && !$listing_type['shc_auction'])
            || ($config['membership_module'] && !$account_info['plan']['shc_module'] && $rlAccount->isLogin())
            || ($rlAccount->isLogin() && !in_array($account_info['Type'], $account_types))
        ) {
            return;
        }

        if ($price = $rlSmarty->_tpl_vars['manageListing']->listingData[$config['price_tag_field']]) {
            $price_currency = explode('|', $price);
            $price_currency = $lang['data_formats+name+' . $price_currency[1]];
        } else {
            $format = $GLOBALS['rlCategories']->getDf('currency');
            $price_currency = $lang[$format[0]['pName']];
        }

        $rlSmarty->assign('defaultCurrencyName', $price_currency);

        $rlSmarty->assign_by_ref('isLogin', $rlAccount->isLogin());

        if (!isset($lang['shc_start_price'])) {
            $lang = array_merge($lang, $this->getPhrases(['add_listing', 'shopping_cart', 'my_shopping_cart']));
        }

        $isAuctionActive = false;
        $manageListing = $rlSmarty->_tpl_vars['manageListing'];
        if ($manageListing->listingData['shc_mode'] == 'auction') {
            $auction = new Auction();
            $totalBids = $auction->getTotalBids($manageListing->listingID);

            if ($manageListing->listingData['shc_auction_status'] == 'active' && $totalBids > 0) {
                $isAuctionActive = true;
            }
        }
        $rlSmarty->assign('isAuctionActive', $isAuctionActive);

        $shipping = new Shipping();
        $shipping->prepareDataFields();
        PriceFormat::prepareTabs($listing_type);
        self::loadTpl('price_format_form');
    }

    /**
     * @hook afterListingCreate
     */
    public function hookAfterListingCreate($AddListing)
    {
        if (self::isConfigured()) {
            PriceFormat::saveOptions($AddListing->listingID);
        }
    }

    /**
     * @hook afterListingEdit
     */
    public function hookAfterListingEdit($EditListing)
    {
        if (self::isConfigured()) {
            PriceFormat::saveOptions($EditListing->listingID);
        }
    }

    /**
     * @hook afterListingUpdate
     */
    public function hookAfterListingUpdate($AddListing)
    {
        if (self::isConfigured()) {
            PriceFormat::saveOptions($AddListing->listingID);
        }
    }

    /**
     * @hook addListingPostSimulation
     */
    public function hookAddListingPostSimulation($EditListing)
    {
        if (self::isConfigured()) {
            PriceFormat::simulatePostData($EditListing->listingData);
        }
    }

    /**
     * @hook editListingPostSimulation
     */
    public function hookEditListingPostSimulation($EditListing)
    {
        if (self::isConfigured()) {
            PriceFormat::simulatePostData($EditListing->listingData);
        }
    }

    /**
     * @hook profileController
     */
    public function hookProfileController()
    {
        global $config, $tabs, $account_info, $profile_info, $lang, $rlSmarty;

        if (self::isConfigured()) {
            $account_types = explode(",", $config['shc_account_types']);

            if (!$account_info['Abilities'] || !in_array($account_info['Type'], $account_types)) {
                return;
            }

            if ($config['shc_method'] == 'multi') {
                $tabs['shoppingCart'] = array(
                    'key' => 'shoppingCart',
                    'name' => $lang['shc_account_settings'],
                );

                $rlSmarty->assign_by_ref('tabs', $tabs);

                $payment = new Payment();
                $payment->saveAccountSettings();

                $gateways = $payment->getActiveGateways();

                if ($gateways) {
                    foreach ($gateways as $key => $value) {
                        $gateways[$key]['name'] = $lang['payment_gateways+name+' . $value['Key']];
                    }
                }
                $rlSmarty->assign_by_ref('payment_gateways', $gateways);

                $lang = array_merge($lang, $GLOBALS['rlLang']->getAdminPhrases(RL_LANG_CODE, 'active', 'settings'));
                $lang = array_merge($lang, $this->getPhrases('add_listing', 'payment_gateways'));

                // prepare config names
                $shcLang = [];
                foreach ($lang as $lKey => $lVal) {
                    $pos = strrpos($lKey, '+') + 1;
                    if (substr_count($lKey, 'config+name+') > 0) {
                        $shcLang[substr($lKey, $pos)] = $lVal;
                    }
                    if (substr_count($lKey, 'config+des+') > 0) {
                        $shcLang[substr($lKey, $pos) . '_des'] = $lVal;
                    }
                }

                $rlSmarty->assign_by_ref('shcLang', $shcLang);

                $shipping = new Shipping();
                $shipping->getShippingFields(true);
            }
        }
    }

    /**
     * @hook profileBlock
     */
    public function hookProfileBlock()
    {
        global $config;

        if (self::isConfigured()) {
            $account_types = explode(",", $config['shc_account_types']);
            if ($config['shc_method'] != 'multi' || !in_array($GLOBALS['account_info']['Type'], $account_types)) {
                return;
            }

            self::loadTpl('account_settings');
        }
    }

    /**
     * @hook phpListingsAjaxDeleteListing
     */
    public function hookPhpListingsAjaxDeleteListing($listing = array())
    {
        if (!$listing) {
            return;
        }

        $sql = "UPDATE `{db_prefix}shc_order_details` SET `Status` = 'deleted' ";
        $sql .= "WHERE `Item_ID` = '{$listing['ID']}' AND `Status` = 'active' ";
        $GLOBALS['rlDb']->query($sql);
    }

    /**
     * @hook apTplListingsFormAdd
     */
    public function hookApTplListingsFormAdd()
    {
        global $reefless, $listing_type, $lang;

        if (!self::isConfigured()
            || (!$listing_type['shc_module'] && !$listing_type['shc_auction'])
        ) {
            return;
        }

        if (!isset($lang['shc_start_price'])) {
            $lang = array_merge($lang, $this->getPhrases(['add_listing', 'shopping_cart', 'my_shopping_cart']));
        }

        $shipping = new Shipping();
        $shipping->prepareDataFields();
        PriceFormat::prepareTabs($listing_type);

        self::loadTpl('price_format_form', 'admin');
    }

    /**
     * @hook apTplListingsFormEdit
     */
    public function hookApTplListingsFormEdit()
    {
        global $reefless, $listing, $rlListingTypes, $lang, $listing_type;

        $listing_type = $rlListingTypes->types[$listing['Listing_type']];

        if (!self::isConfigured()
            || (!$listing_type['shc_module'] && !$listing_type['shc_auction'])
        ) {
            return;
        }

        if (!isset($lang['shc_start_price'])) {
            $lang = array_merge($lang, $this->getPhrases(['add_listing', 'shopping_cart', 'my_shopping_cart']));
        }

        $shipping = new Shipping();
        $shipping->prepareDataFields();
        PriceFormat::prepareTabs($listing_type);

        self::loadTpl('price_format_form', 'admin');
    }

    /**
     * @hook apPhpListingsPost
     */
    public function hookApPhpListingsPost()
    {
        global $listing;

        if (self::isConfigured()) {
            PriceFormat::simulatePostData($listing);
        }
    }

    /**
     * @hook apPhpListingsAfterAdd
     */
    public function hookApPhpListingsAfterAdd()
    {
        global $listing_id;

        if (self::isConfigured()) {
            PriceFormat::saveOptions($listing_id);
        }
    }

    /**
     * @hook apPhpListingsAfterEdit
     */
    public function hookApPhpListingsAfterEdit()
    {
        global $listing_id;

        if (self::isConfigured()) {
            PriceFormat::saveOptions($listing_id);
        }
    }

    /**
     * @hook cronAdditional
     */
    public function hookCronAdditional()
    {
        global $config;

        if (!self::isConfigured()) {
            return;
        }

        if ($config['shc_module_auction']) {
            $auction = new Auction();
            $auction->closeExipredItems();

            if ($config['shc_auto_rate'] && $config['shc_auto_rate_period']) {
                $auction->setAutomaticallyRate();
            }
        }

        if ($config['shc_module']) {
            $shopping = new Shopping();
            $shopping->refreshCartItems();
        }
    }

    /**
     * @hook apTplListingTypesForm
     */
    public function hookApTplListingTypesForm()
    {
        if (self::isConfigured()) {
            self::loadTpl('listing_type', 'admin');
        }
    }

    /**
     * @hook apPhpListingTypesPost
     */
    public function hookApPhpListingTypesPost()
    {
        global $type_info;

        if (self::isConfigured()) {
            $_POST['shc_module'] = $type_info['shc_module'];
            $_POST['shc_auction'] = $type_info['shc_auction'];
        }
    }

    /**
     * @hook apPhpListingTypesBeforeAdd
     */
    public function hookApPhpListingTypesBeforeAdd()
    {
        global $data;

        if (self::isConfigured()) {
            $data['shc_module'] = (int) $_POST['shc_module'];
            $data['shc_auction'] = (int) $_POST['shc_auction'];
        }
    }

    /**
     * @hook apPhpListingTypesBeforeEdit
     */
    public function hookApPhpListingTypesBeforeEdit()
    {
        global $update_date;

        if (self::isConfigured()) {
            $update_date['fields']['shc_module'] = (int) $_POST['shc_module'];
            $update_date['fields']['shc_auction'] = (int) $_POST['shc_auction'];
        }
    }

    /**
     * @hook listingsModifyWhere
     */
    public function hookListingsModifyWhere()
    {
        $this->checkUnavailableListings();
    }

    /**
     * @hook listingsModifyWhereMyFavorite
     */
    public function hookListingsModifyWhereMyFavorite()
    {
        $this->checkUnavailableListings();
    }

    /**
     * @hook listingsModifyWhereByPeriod
     */
    public function hookListingsModifyWhereByPeriod()
    {
        $this->checkUnavailableListings();
    }

    /**
     * @hook listingsModifyWhereByAccount
     */
    public function hookListingsModifyWhereByAccount()
    {
        $this->checkUnavailableListings();
    }

    /**
     * @hook listingsModifyWhereSearch
     */
    public function hookListingsModifyWhereSearch()
    {
        $this->checkUnavailableListings();
    }

    /**
     * Check unavailable listings
     */
    public function checkUnavailableListings()
    {
        if (!self::isConfigured()) {
            return;
        }

        $shopping = new Shopping();
        $shopping->checkUnavailableListings();
    }

    /**
     * @hook ajaxRequest
     *
     * @since 3.0.0
     */
    public function hookAjaxRequest(&$out, $request_mode, $request_item, $request_lang)
    {
        global $lang, $config, $rlDb, $account_info, $reefless, $rlSmarty, $rlListings;

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

        $reefless->loadClass('Listings');
        $reefless->loadClass('Mail');
        $reefless->loadClass('Payment');

        if (!is_object($GLOBALS['rlGateway'])) {
            require_once RL_CLASSES . 'rlGateway.class.php';
        }

        switch ($request_mode) {
            case 'shoppingCartAddItem':
                $item_id = (int) $request_item;
                $is_auction = (bool) $_REQUEST['is_auction'];
                $listing_info = $rlListings->getListing($item_id, true);

                $shopping = new Shopping();

                // check digital
                $options = $this->getListingOptions($item_id, $listing_info);
                if ($listing_info['Digital'] && $listing_info['Quantity_unlim']) {
                    $shc_txn_id = $shopping->getOrderKey();
                    $alreadyAdded = $rlDb->getOne(
                        'ID',
                        "`Order_key` = '{$shc_txn_id}' AND `Item_ID` = '{$item_id}' AND `Status` = 'active'",
                        'shc_order_details'
                    );

                    if ($alreadyAdded) {
                        $message = $GLOBALS['rlLang']->getSystem('shc_already_added');
                        $error = true;
                    }
                }

                // Check quantity and availability
                if (!$listing_info['shc_available']
                    || ($listing_info['shc_quantity'] <= 0 && !$listing_info['Digital'] && !$listing_info['Quantity_unlim'])
                ) {
                    $message = $GLOBALS['rlLang']->getSystem('shc_not_availble');
                    $error = true;
                }

                if ($account_info && $account_info['ID'] == $listing_info['Account_ID']) {
                    $message = $lang['shc_add_item_owner'];
                    $error = true;
                }

                if (!$error) {
                    $result = $shopping->addItem($item_id, $listing_info);

                    if ($result) {
                        $message = $lang['shc_add_item_notice'];
                        if ($is_auction) {
                            $message .= ' <br />' . $GLOBALS['rlLang']->getPhrase('shc_auction_reserved_notice', null, null, true);
                        }
                    } else {
                        $message = $lang['shc_add_item_error'];
                    }

                    $total_info = $shopping->getTotalInfo();

                    $out = array(
                        'status' => $result ? 'OK' : 'ERROR',
                        'message' => $message,
                        'content' => $shopping->buildCartContent(),
                        'total' => self::addCurrency($total_info['total']),
                        'count' => (int) $total_info['count'],
                        'count_item' => (int) $listing_info['shc_quantity'] - 1,
                        'item_info' => $shopping->getItem($item_id),
                        'auction_reserved' => $is_auction ? $lang['shc_auction_reserved'] : '',
                    );
                } else {
                    $out = array(
                        'status' => 'ERROR',
                        'message' => $message,
                    );
                }

                break;

            case 'shoppingCartDeleteOneItem':
                $id = (int) $request_item;
                $item_id = (int) $_REQUEST['item_id'];
                $step = $_REQUEST['step'];
                $shopping = new Shopping();

                $sql = "SELECT * FROM `{db_prefix}shc_order_details` WHERE `ID` = {$id}";
                $item_info = $rlDb->getRow($sql);

                // check item by byuer
                if ($item_info['Buyer_ID'] != $account_info['ID'] && !empty($item_info['Buyer_ID'])) {
                    $message = $lang['shc_delete_item_error'];
                    $error = true;
                }

                if (!$error) {
                    // Get all pages keys/paths
                    $result = $shopping->deleteOneItem($id, $item_id, $item_info);

                    if ($result) {
                        $message = $lang['shc_delete_item_notice'];
                    } else {
                        $message = $lang['shc_delete_item_error'];
                    }

                    $total_info = $shopping->getTotalInfo();
                    $listing_info = $rlListings->getListing($item_id);

                    $out = array(
                        'status' => $result ? 'OK' : 'ERROR',
                        'message' => $message,
                        'content' => $shopping->buildCartContent(),
                        'total' => self::addCurrency($total_info['total']),
                        'count' => (int) $total_info['count'],
                        'count_item' => (int) $listing_info['shc_quantity'],
                        'item_info' => $shopping->getItem($item_id),
                        'empty_cart' => $lang['shc_empty_cart'],
                    );
                } else {
                    $out = array(
                        'status' => 'ERROR',
                        'message' => $message,
                    );
                }

                break;
            case 'shoppingCartDeleteItem':
                $id = (int) $request_item;
                $item_id = (int) $_REQUEST['item_id'];
                $step = $_REQUEST['step'];
                $shopping = new Shopping();

                $sql = "SELECT * FROM `{db_prefix}shc_order_details` WHERE `ID` = {$id}";
                $item_info = $rlDb->getRow($sql);

                // check item by byuer
                if ($item_info['Buyer_ID'] != $account_info['ID'] && !empty($item_info['Buyer_ID'])) {
                    $message = $lang['shc_delete_item_error'];
                    $error = true;
                }

                if (!$error) {
                    // Get all pages keys/paths
                    $pages = $GLOBALS['rlNavigator']->getAllPages();
                    $rlSmarty->assign_by_ref('pages', $pages);

                    $result = $shopping->deleteItem($id, $item_id, $item_info);

                    if ($result) {
                        $message = $lang['shc_delete_item_notice'];
                    } else {
                        $message = $lang['shc_delete_item_error'];
                    }

                    $total_info = $shopping->getTotalInfo();
                    $listing_info = $rlListings->getListing($item_id);

                    $out = array(
                        'status' => $result ? 'OK' : 'ERROR',
                        'message' => $message,
                        'content' => $shopping->buildCartContent(),
                        'total' => self::addCurrency($total_info['total']),
                        'count' => (int) $total_info['count'],
                        'count_item' => (int) $listing_info['shc_quantity'],
                        'item_info' => $shopping->getItem($item_id),
                        'empty_cart' => $lang['shc_empty_cart'],
                    );
                } else {
                    $out = array(
                        'status' => 'ERROR',
                        'message' => $message,
                    );
                }

                break;

            case 'shoppingCartClearCart':
                $orderKey = $GLOBALS['rlValid']->xSql($_REQUEST['key']);
                $step = $_REQUEST['step'];

                $shopping = new Shopping();
                $orders = new Orders();

                $shopping->clearCart();

                $out = array(
                    'status' => 'OK',
                    'message' => $lang['shc_cart_clear_success'],
                    'content' => $shopping->buildCartContent(),
                    'total' => self::addCurrency(),
                    'count' => 0,
                );

                if ($orderKey && in_array($step, ['shipping', 'checkout']) && $account_info) {
                    $$orderID = $rlDb->getOne('ID', "`Order_key` = '{$orderKey}'", 'shc_orders');
                    $orders->delete($orderID);
                    $out['url'] = $reefless->getPageUrl('shc_my_shopping_cart');
                    $out['empty_cart'] = $lang['shc_empty_cart'];
                }
                break;

            case 'shoppingCheckAccountSettings':
                $status = 'ERROR';
                $gateways = $this->getPaymentGateways(false, $account_info['ID']);

                if (count($gateways) > 0) {
                    $status = 'OK';
                }

                $out = array(
                    'status' => $status,
                    'message' => $status == 'ERROR' ? $lang['shc_account_settings_empty'] : '',
                );
                break;

            case 'calculateCommission':
                $price = (float) $request_item;
                $is_auction = (int) $_REQUEST['is_auction'];
                $commission = PriceFormat::calculateCommission($price, $is_auction);

                $out = array(
                    'status' => 'OK',
                    'price' => $price,
                    'commission' => $commission,
                );
                break;

            case 'auctionAddBid':
                $id = (int) $request_item;
                $rate = (float) $_REQUEST['rate'];
                $error = false;
                $messages = [];
                $auction = new Auction();
                $lang = array_merge($lang, $this->getPhrases(['listing_details']));

                if (!$GLOBALS['rlAccount']->isLogin()) {
                    $out = array(
                        'status' => 'ERROR',
                        'url' => $reefless->getPageUrl('login'),
                    );
                    $error = true;
                }

                $item_info = $GLOBALS['rlListings']->getListing($id, true);
                $options = $this->getListingOptions($id, $item_info);

                $last_bid = $auction->getLastBid($id);
                $min_rate_bid = $last_bid['Total']
                ? round($last_bid['Total'] + $options['Bid_step'], 2)
                : round($options['Start_price'] + $options['Bid_step'], 2);

                if ($rate < $min_rate_bid) {
                    $messages[] = $lang['shc_rate_failed'];
                    $error = true;
                }

                if ($account_info['ID'] == $item_info['Account_ID']) {
                    $messages[] = $lang['shc_add_item_owner_auction'];
                    $error = true;
                }

                if ($last_bid['Buyer_ID'] == $account_info['ID']) {
                    $messages[] = $lang['shc_repeated_bid'];
                    $error = true;
                }

                if (!$error) {
                    $result = $auction->ajaxAddBid($id, $rate, $item_info);
                    $price = explode('|', $item_info[$config['price_tag_field']]);

                    $out = array(
                        'status' => 'OK',
                        'message' => $lang['shc_add_bid_success'],
                        'count' => $result['count'],
                        'bidders' => $result['bidders'],
                        'content' => $result['content'],
                        'min_bid' => $result['min_bid'],
                        'rate' => $result['rate'],
                        'number' => $result['number'],
                        'hide_buy_now' => $result['current_bid'] > (float) $price[0] ? true : false,
                    );
                } else {
                    $out = array(
                        'status' => 'ERROR',
                        'message' => $messages,
                    );
                }
                break;

            case 'auctionCancelBid':
                $id = (int) $request_item;
                $controller = $_REQUEST['controller'];
                $itemID = (int) $_REQUEST['itemID'];

                $lang = array_merge($lang, $this->getPhrases(['listing_details']));

                $auction = new Auction();
                $result = $auction->ajaxCancelBid($id);

                if ($result) {
                    $out = array(
                        'status' => 'OK',
                        'message' => $lang['shc_bid_deleted_success'],
                        'count' => $result['count'],
                        'bidders' => $result['bidders'],
                        'content' => $result['content'],
                        'min_bid' => $result['min_bid'],
                        'price' => $result['price'],
                    );

                    if ($controller == 'my_auctions') {
                        $myAuctionObj = new MyAuctions();
                        $bids = $myAuctionObj->getMyBids($itemID,  'live');

                        if (count($bids) <= 0) {
                            $out['url'] = $reefless->getPageUrl('shc_auctions') . '#live';
                        }
                    }
                } else {
                    $out = array(
                        'status' => 'ERROR',
                        'message' => $lang['shc_delete_bid_fail'],
                    );
                }
                break;

            case 'renewAuction':
                $item_id = (int) $request_item;

                $auction = new Auction();
                $result = $auction->ajaxRenewAuction($item_id);

                $lang = array_merge($lang, $this->getPhrases(['add_listing']));

                $out = array(
                    'status' => $result ? 'OK' : 'ERROR',
                    'message' => $lang['shc_renew_auction_' . ($result ? 'success' : 'failed')],
                );
                break;

            case 'closeAuction':
                $item_id = (int) $request_item;

                $auction = new Auction();
                $result = $auction->ajaxCloseAuction($item_id);

                $lang = array_merge($lang, $this->getPhrases(['add_listing']));

                $out = array(
                    'status' => $result ? 'OK' : 'ERROR',
                    'message' => $lang['shc_close_auction_' . ($result ? 'success' : 'failed')],
                );
                break;

            case 'shoppingCartSaveTrackingNumber':
                $item_id = (int) $request_item;
                $number = $GLOBALS['rlValid']->xSql($_REQUEST['number']);

                $shipping = new Shipping();
                $result = $shipping->saveTrackingNumber($item_id, $number);

                $message = $GLOBALS['rlLang']->getSystem('shc_tracking_number_added_' . ($result ? 'success' : 'failed'));
                $out = array(
                    'status' => $result ? 'OK' : 'ERROR',
                    'message' => $message,
                    'content' => $number,
                );
                break;

            case 'shoppingCartChangeShippingStatus':
                $item_id = (int) $request_item;
                $status = $GLOBALS['rlValid']->xSql($_REQUEST['status']);

                $shipping = new Shipping();
                $result = $shipping->changeStatus($item_id, $status);

                $message = $GLOBALS['rlLang']->getSystem(
                    $result
                    ? 'shc_notice_shipping_status_changed'
                    : 'shc_shipping_status_failed'
                );
                $out = array(
                    'status' => $result ? 'OK' : 'ERROR',
                    'message' => $message,
                );
                break;

            case 'shoppingCartBidsOffers':
                $mode = $request_item;
                $pageNumber = (int) $_REQUEST['page'];
                $pInfo['current'] = $pageNumber;
                $tplFile = $pageNumber && $pageNumber > 1 ? 'my_auctions_items.tpl' : 'my_auctions.tpl';
                $myAuctions = new MyAuctions();

                switch ($mode) {
                    case 'dontwin':
                        $auctions = $myAuctions->getNotWonAuctions($pageNumber, $config['shc_orders_per_page'], 'dontwin');
                        break;

                    case 'live':
                        $auctions = $myAuctions->getNotWonAuctions($pageNumber, $config['shc_orders_per_page'], 'live');
                        break;

                    case 'winnerbids':
                    default:
                        $tplFile = $pageNumber && $pageNumber > 1 ? 'my_auctions_won_items.tpl' : 'my_auctions_won.tpl';
                        $auctions = $myAuctions->getMyAuctions($pageNumber, $config['shc_orders_per_page']);
                        break;
                }

                $lang = array_merge($lang, $this->getPhrases(['my_auctions', 'listing_details']));

                $rlSmarty->assign_by_ref('pInfo', $pInfo);
                $rlSmarty->assign_by_ref('auction_mod', $mode);

                if ($auctions) {
                    $rlSmarty->assign_by_ref('auctions', $auctions);
                    $tpl = RL_PLUGINS . 'shoppingCart/view/' . $tplFile;

                    $out = array(
                        'status' => 'OK',
                        'content' => $rlSmarty->fetch($tpl, null, null, false),
                        'count' => count($auctions)
                    );
                } else {
                    $out = array(
                        'status' => 'OK',
                        'content' => '',
                        'count' => 0
                    );
                }
                break;

            case 'shoppingCartGetQuote':
                $itemID = (int) $request_item;
                $method = $GLOBALS['rlValid']->xSql($_REQUEST['method']);
                parse_str($_REQUEST['form'], $form);

                $sql = "SELECT * FROM `{db_prefix}shc_order_details` WHERE `ID` = {$itemID}";
                $itemInfo = $rlDb->getRow($sql);
                $listing = $GLOBALS['rlListings']->getListing($itemInfo['Item_ID'], true);
                $options = $this->getListingOptions($itemInfo['Item_ID'], $listing);

                $shipping = new Shipping();
                $request = $shipping->prepareQuoteData($method, $form, $itemInfo, $listing);

                $methodClass = '\ShoppingCart\Shipping\\' . ucfirst($method);
                $methodClass = new $methodClass();
                $methodClass->init();
                if ($methodClass->isConfigured()) {
                    $quote = $methodClass->getQuote($request, $itemInfo);
                } else {
                    $error = $GLOBALS['rlLang']->getSystem('shc_shipping_method_not_configured');
                    $quote['error'] = str_replace('{method}', strtoupper($method), $error);
                }

                $out = array(
                    'status' => !$quote['error'] ? 'OK' : 'ERROR',
                    'quote' => $quote,
                    'multi' => $quote['error'] || $quote['total'] ? false : true,
                );

                if ($out['multi']) {
                    $reefless->rlArraySort($out['quote'], 'total');
                }

                if (!$quote['error']) {
                    $shipping->addQuote($quote, $itemID);
                }
                break;

            case 'shoppingDownloadFile':
                $itemID = (int) $request_item;

                $error = '';
                $link = '';

                $sql = "SELECT * FROM `{db_prefix}shc_order_details` WHERE `ID` = {$itemID}";
                $itemInfo = $rlDb->getRow($sql);

                $orders = new Orders();
                $order = $orders->get((int) $itemInfo['Order_ID']);

                if ($order['Status'] == 'paid') {
                    $options = $this->getListingOptions($itemInfo['Item_ID']);

                    if ($options['Digital'] && $options['Digital_product'] && $order['Buyer_ID'] == $account_info['ID']) {
                        $file = RL_FILES . $options['Digital_product'];

                        if (file_exists($file)) {
                            $_SESSION['shcDownloadFile'] = base64_encode($file);
                            $link = $orders->prepareDownloadRequest($order);
                        } else {
                            $error = $lang['shc_file_not_found'];
                        }
                    } else {
                        $error = $lang['shc_access_denied_file'];
                    }
                } else {
                    $error = $lang['shc_access_denied_file'];
                }

                $out = array(
                    'status' => !$error ? 'OK' : 'ERROR',
                    'message' => $error,
                    'request' => $link,
                );
                break;

            case 'shoppingCartDeleteFile':
                $itemID = (int) $request_item;

                $sql = "SELECT * FROM `{db_prefix}listings` WHERE `ID` = {$itemID}";
                $listing = $rlDb->getRow($sql);

                if ($listing['Account_ID'] == $account_info['ID']) {
                    $result = $this->deleteFile($itemID);
                }

                $out = [
                    'status' => $result ? 'OK' : 'ERROR',
                    'message' => $result ? $lang['item_deleted'] : $lang['shc_file_not_found'],
                ];

                break;

            case 'shoppingShowAuctionInfo':
                $itemID = (int) $request_item;

                $myAuctions = new \ShoppingCart\MyAuctions();
                $auctionInfo = $myAuctions->getAuctionLiveInfo($itemID);

                $lang = array_merge($lang, $this->getPhrases(['listing_details']));

                $rlSmarty->assign_by_ref('auctionInfo', $auctionInfo);
                $tpl = RL_PLUGINS . 'shoppingCart/view/auction_info_short.tpl';

                $out = array(
                    'status' => 'OK',
                    'content' => $rlSmarty->fetch($tpl, null, null, false),
                );

                break;

            case 'shoppingCartMakePaid':
                $orderID = (int) $request_item;
                $orders = new Orders();

                $lang = array_merge($lang, $this->getPhrases(['my_items_sold']));
                $result = $orders->makePaid($orderID, $account_info['ID']);

                $out = array(
                    'status' => $result ? 'OK' : 'ERROR',
                    'message' => $result ? $lang['shc_set_paid_success'] : 'Failed change payment status',
                    'status_value' => $lang['paid']
                );
                break;

            case 'shoppingCartConfirmOrder':
                $orderID = (int) $request_item;
                $lang = array_merge($lang, $this->getPhrases(['shopping_cart']));

                $escrow = new Escrow();

                $result = $escrow->makeAction('confirm', $orderID, $account_info['ID']);

                $out = array(
                    'status' => $result ? 'OK' : 'ERROR',
                    'message' => $result ? $lang['shc_order_confirmed_success'] : 'Failed confirm order',
                    'text' => $lang['shc_escrow_confirmed']
                );
                break;

            case 'shoppingCartCancelOrder':
                $orderID = (int) $request_item;
                $lang = array_merge($lang, $this->getPhrases(['shopping_cart']));
                $reason = $GLOBALS['rlValid']->xSql($_REQUEST['reason']);
                $escrow = new Escrow();

                $result = $escrow->makeAction('cancel', $orderID, $account_info['ID'], $reason);

                $out = array(
                    'status' => $result ? 'OK' : 'ERROR',
                    'message' => $result ? $lang['shc_order_canceled_success'] : 'Failed cancel order',
                    'text' => $lang['shc_escrow_canceled']
                );
                break;
        }
    }

    /**
     * Check ajaxRequest request
     * @since 3.0.0
     *
     * @param string $request_mode
     * @return bool
     */
    public function isAjaxModeValid($request_mode = '')
    {
        $ajaxRequests = array(
            'shoppingCartAddItem',
            'shoppingCartDeleteOneItem',
            'shoppingCartDeleteItem',
            'shoppingCartClearCart',
            'shoppingCheckAccountSettings',
            'calculateCommission',
            'auctionAddBid',
            'auctionCancelBid',
            'renewAuction',
            'closeAuction',
            'shoppingCartSaveTrackingNumber',
            'shoppingCartChangeShippingStatus',
            'shoppingCartBidsOffers',
            'shoppingCartGetQuote',
            'shoppingDownloadFile',
            'shoppingCartDeleteFile',
            'shoppingShowAuctionInfo',
            'shoppingCartMakePaid',
            'shoppingCartConfirmOrder',
            'shoppingCartCancelOrder'
        );

        return (bool) ($request_mode && in_array($request_mode, $ajaxRequests));
    }

    /**
     * @hook smartyCompileFileTop
     */
    public function hookSmartyCompileFileTop($compiled_content, &$source_content, $resource_name)
    {
        if (self::isConfigured()) {
            if (!defined('REALM')) {
                $source_content = str_replace(
                    'cart_items_responsive_42.tpl',
                    'view' . RL_DS . 'cart_items.tpl',
                    $source_content
                );
            }
        }
    }

    /**
     * @hook apTplFieldsForm
     */
    public function hookApTplFieldsForm()
    {
        global $controller;

        if (self::isConfigured() && $controller == 'shopping_cart') {
            require_once RL_PLUGINS . 'shoppingCart/static.inc.php';

            $GLOBALS['rlSmarty']->assign_by_ref('shc_google_autocomplete', $google_autocomplete);

            self::loadTpl('field_form', 'admin');
        }
    }

    /**
     * @hook apTplFooter
     */
    public function hookApTplFooter()
    {
        global $controller;

        if (self::isConfigured() && $controller == 'listings') {
            self::loadTpl('tabs_handler', 'admin');
        }
    }

    /**
     * Display icon in listing grids
     *
     * @hook listingNavIcons
     */
    public function hookListingNavIcons()
    {
        global $rlSmarty;

        if ($rlSmarty->_tpl_vars['listing']['shc_mode'] == 'auction') {
            if (!is_object($GLOBALS['_auction'])) {
                $GLOBALS['_auction'] = new \ShoppingCart\Auction();
            }
            $rlSmarty->_tpl_vars['listing']['left_time'] = $GLOBALS['_auction']->getTimeLeft($rlSmarty->_tpl_vars['listing']);
        }

        self::loadTpl('grid_icon');
    }

    /**
     * Check listing data
     *
     * @hook addListingFormDataChecking
     */
    public function hookAddListingFormDataChecking(&$addListing, $data, &$errors, &$error_fields)
    {
        $this->validateListingData($addListing, $data, $errors, $error_fields);
    }

    /**
     * Display icon in listing grids
     *
     * @hook tplFeaturedItemIcon
     */
    public function hookTplFeaturedItemIcon()
    {
        global $rlSmarty;

        if ($rlSmarty->_tpl_vars['featured_listing']['shc_mode'] == 'auction') {
            if (!is_object($GLOBALS['_auction'])) {
                $GLOBALS['_auction'] = new \ShoppingCart\Auction();
            }
            $rlSmarty->_tpl_vars['featured_listing']['left_time'] = $GLOBALS['_auction']->getTimeLeft($rlSmarty->_tpl_vars['featured_listing']);
        }
        self::loadTpl('grid_icon_featured');
    }

    /**
     * Ajax request in admin panel
     *
     * @param array $out
     * @param string $item
     * @hook apAjaxRequest
     */
    public function hookApAjaxRequest(&$out, $item)
    {
        global $config, $rlDb, $lang, $reefless, $rlSmarty;

        if (!$this->isApAjaxModeValid($item)) {
            return;
        }

        if (!is_object($rlSmarty)) {
            require_once RL_LIBS . 'smarty' . RL_DS . 'Smarty.class.php';
            $reefless->loadClass('Smarty');
        }

        switch ($item) {
            case 'shoppingCartConvertPrices':
                $currency = new Currency();

                $limit = intval($_REQUEST['limit'] ?: 100);
                $start = intval($_REQUEST['start'] ?: 0);
                $sqlCalc = "";

                $priceField = $config['price_tag_field'];

                if ($start == 0) {
                    $sqlCalc = "SQL_CALC_FOUND_ROWS";
                }
                $sql = "SELECT {$sqlCalc} `ID`, `{$priceField}` FROM `{db_prefix}listings` ";
                $sql .= "WHERE `{$priceField}` <> '' ";
                $sql .= "LIMIT {$start},{$limit}";

                $listings = $rlDb->getAll($sql);

                if ($start == 0) {
                    $calc = $rlDb->getRow("SELECT FOUND_ROWS() AS `calc`", 'calc');
                    $_SESSION['shoppingCartConvertPrices']['total'] = $calc;

                    $currency->addSystemCurrency();
                }

                // Converting prices of listings
                if ($listings) {
                    $currency->convertExistsPrice($listings);

                    $action = ($start + $limit) >= $_SESSION['shoppingCartConvertPrices']['total']
                    ? 'completed'
                    : 'next';

                    $out = array(
                        'status' => 'OK',
                        'action' => $action,
                        'progress' => floor((($start + $limit) * 100) / $_SESSION['shoppingCartConvertPrices']['total']),
                    );

                    if ($action == 'completed') {
                        unset($_SESSION['shoppingCartConvertPrices']);
                    }
                } else {
                    $out = array(
                        'status' => 'OK',
                        'action' => 'completed',
                        'progress' => 100,
                    );
                }
                break;

            case 'shoppingCartDeleteOrder':
                $ids = explode('|', $_REQUEST['id']);
                $result = false;

                if (count($ids) > 0) {
                    $orders = new Orders();

                    $result = true;
                    foreach ($ids as $id) {
                        if (!$orders->delete($id)) {
                            $result = false;
                        }
                    }
                }

                $out['status'] = $result ? 'OK' : 'ERROR';

                break;

            case 'shippingFieldDelete':
                $key = $_REQUEST['key'];
                $GLOBALS['rlValid']->sql($key);
                $error = '';

                if (!$key) {
                    $error = 'Can not delete shipping field, field with requested Key does not exist';
                }

                // get field info
                $field = $rlDb->fetch('*', array('Key' => $key), null, 1, 'shc_shipping_fields', 'row');

                if ($field['Readonly']) {
                    $error = str_replace('{field}', $lang['shc_shipping_fields+name+' . $key], $lang['field_protected']);
                }

                if (!$error) {
                    $shippingFields = new \ShoppingCart\Admin\ShippingFields();
                    $result = $shippingFields->delete($field);

                    $out = [
                        'status' => $result ? 'OK' : 'ERROR',
                        'message' => $result ? $lang['field_deleted'] : 'Can not delete shipping field',
                    ];
                } else {
                    $out = [
                        'status' => 'ERROR',
                        'message' => $error,
                    ];
                }

                break;

            case 'shoppingCartDeleteAuctionItem':
                $ids = explode('|', $_REQUEST['id']);
                $result = false;

                if (count($ids) > 0) {
                    $auction = new \ShoppingCart\Admin\Auction();

                    $result = true;
                    foreach ($ids as $id) {
                        if (!$auction->delete($id)) {
                            $result = false;
                        }
                    }
                }

                $out = [
                    'status' => $result ? 'OK' : 'ERROR',
                    'message' => $result ? $lang['item_deleted'] : 'Can not delete the auction',
                ];

                break;

            case 'shoppingCartDeleteBid':
                $ids = explode(',', $_REQUEST['id']);
                $result = false;

                if (count($ids) > 0) {
                    $auction = new \ShoppingCart\Admin\Auction();

                    $result = true;
                    foreach ($ids as $id) {
                        if (!$auction->deleteBid($id)) {
                            $result = false;
                        }
                    }
                }

                $out = [
                    'status' => $result ? 'OK' : 'ERROR',
                    'message' => $result ? $lang['item_deleted'] : 'Can not delete the bid',
                ];

                break;

            case 'shoppingDownloadFile':
                $itemID = (int) $_REQUEST['itemID'];

                $error = '';
                $link = '';

                $sql = "SELECT * FROM `{db_prefix}shc_order_details` WHERE `ID` = {$itemID}";
                $itemInfo = $rlDb->getRow($sql);

                $sql = "SELECT * FROM `{db_prefix}shc_orders` WHERE `ID` = {$itemInfo['Order_ID']}";
                $order = $rlDb->getRow($sql);

                if ($order['Status'] == 'paid') {
                    $options = $this->getListingOptions($itemInfo['Item_ID']);

                    if ($options['Digital'] && $options['Digital_product']) {
                        $file = RL_FILES . $options['Digital_product'];

                        if (file_exists($file)) {
                            $_SESSION['shcDownloadFile'] = base64_encode($file);
                            $request = base64_encode(md5($_SESSION['sessAdmin']['user_id']) . '|' . $order['ID']);
                            $link = RL_PLUGINS_URL . 'shoppingCart/product_download.php?r=' . $request;
                        } else {
                            $error = $lang['shc_file_not_found'];
                        }
                    } else {
                        $error = $lang['shc_access_denied_file'];
                    }
                } else {
                    $error = $lang['shc_access_denied_file'];
                }

                $out = array(
                    'status' => !$error ? 'OK' : 'ERROR',
                    'message' => $error,
                    'request' => $link,
                );
                break;

            case 'shoppingCartDeleteFile':
                $itemID = (int) $_REQUEST['itemID'];
                $result = $this->deleteFile($itemID);

                $out = [
                    'status' => $result ? 'OK' : 'ERROR',
                    'message' => $result ? $lang['item_deleted'] : $lang['shc_file_not_found'],
                ];

                break;

            case 'shoppingCartCancelUpdateListings':
                $update = array(
                    'fields' => array(
                        'Default' => 0,
                    ),
                    'where' => array('Key' => 'shc_update_listings'),

                );
                $result = $rlDb->updateOne($update, 'config');

                $out = [
                    'status' => $result ? 'OK' : 'ERROR',
                ];
                break;

            case 'shoppingCartUpdateListings':
                set_time_limit(0);

                $limit = (int) $_SESSION['updateListings']['info']['per_run'];
                $start = (int) $_GET['index'];

                $updateListings = new ShoppingCart\Admin\UpdateListings();
                $updateListings->update($limit);

                $out['from'] = $start + $limit;
                $out['to'] = $start + ($limit * 2) - 1;
                $out['count'] = (int) $_SESSION['updateListings']['info']['total'];

                if ($out['to'] >= $_SESSION['updateListings']['info']['total']) {
                    $update = array(
                        'fields' => array(
                            'Default' => 0,
                        ),
                        'where' => array('Key' => 'shc_update_listings'),

                    );
                    $rlDb->updateOne($update, 'config');
                }
                break;

            case 'calculateCommission':
                $price = (float) $_REQUEST['price'];
                $is_auction = (int) $_REQUEST['is_auction'];
                $commission = PriceFormat::calculateCommission($price, $is_auction);

                $out = array(
                    'status' => 'OK',
                    'price' => $price,
                    'commission' => $commission,
                );
                break;

            case 'shoppingCartMakePaid':
                $orderID = (int) $_REQUEST['orderID'];
                $accountID = (int) $_REQUEST['accountID'];
                $orders = new Orders();

                $lang = array_merge($lang, $this->getPhrases(['my_items_sold']));
                $result = $orders->makePaid($orderID, $accountID);

                $out = array(
                    'status' => $result ? 'OK' : 'ERROR',
                    'message' => $result ? $lang['shc_set_paid_success'] : 'Failed change payment status',
                    'status_value' => $lang['paid']
                );
                break;

            case 'shoppingCartMigrateDate':
                $migrateData = new \ShoppingCart\Admin\MigrateData();
                $migrateData->ajaxMigrateDate($out);
                break;

            case 'shoppingCartConfirmOrder':
                $orderID = (int) $_REQUEST['orderID'];
                $accountID = (int) $_REQUEST['accountID'];
                $escrow = new Escrow();

                $reefless->loadClass('Listings');
                $reefless->loadClass('Account');

                $result = $escrow->makeAction('confirm', $orderID, $accountID);
                $lang = array_merge($lang, $this->getPhrases(['shopping_cart']));

                $out = array(
                    'status' => $result ? 'OK' : 'ERROR',
                    'message' => $result ? $lang['shc_order_confirmed_success'] : 'Failed confirm order',
                    'text' => $lang['shc_escrow_confirmed']
                );
                break;
        }
    }

    /**
     * Check ApAjaxRequest request
     * @since 3.1.0
     *
     * @param string $request_mode
     * @return bool
     */
    public function isApAjaxModeValid($request_mode = '')
    {
        $ajaxRequests = array(
            'shoppingCartConvertPrices',
            'shoppingCartDeleteOrder',
            'shippingFieldDelete',
            'shoppingCartDeleteAuctionItem',
            'shoppingCartDeleteBid',
            'shoppingDownloadFile',
            'shoppingCartDeleteFile',
            'shoppingCartCancelUpdateListings',
            'shoppingCartUpdateListings',
            'calculateCommission',
            'shoppingCartMakePaid',
            'shoppingCartMigrateDate',
            'shoppingCartConfirmOrder',
        );

        return (bool) ($request_mode && in_array($request_mode, $ajaxRequests));
    }

    /**
     * Mark item in cart as deleted
     *
     * @hook phpAfterDeleteListing
     */
    public function hookPhpAfterDeleteListing($listing = [])
    {
        if (!$listing) {
            return;
        }

        $sql = "UPDATE `{db_prefix}shc_order_details` SET `Status` = 'deleted' ";
        $sql .= "WHERE `Item_ID` = '{$listing['ID']}' AND `Status` = 'active' ";
        $GLOBALS['rlDb']->query($sql);
    }

    /**
     * @hook apTplMembershipPlansForm
     */
    public function hookApTplMembershipPlansForm()
    {
        if (self::isConfigured()) {
            self::loadTpl('membership_plan', 'admin');
        }
    }

    /**
     * @hook apPhpMembershipPlansPost
     */
    public function hookApPhpMembershipPlansPost()
    {
        global $plan_info;

        if (self::isConfigured()) {
            $_POST['shc_module'] = $plan_info['shc_module'];
        }
    }

    /**
     * @hook apPhpMembershipPlansBeforeAdd
     */
    public function hookApPhpMembershipPlansBeforeAdd(&$data)
    {
        if (self::isConfigured()) {
            $data['shc_module'] = (int) $_POST['shc_module'];
        }
    }

    /**
     * @hook apPhpMembershipPlansBeforeEdit
     */
    public function hookApPhpMembershipPlansBeforeEdit(&$update_plan)
    {
        if (self::isConfigured()) {
            $update_plan['fields']['shc_module'] = (int) $_POST['shc_module'];
        }
    }

    /**
     * @hook myListingsIcon
     */
    public function hookMyListingsIcon()
    {
        if (self::isConfigured()) {
            self::loadTpl('my_listing');
        }
    }

    /**
     * @hook myListingsBottom
     */
    public function hookMyListingsBottom()
    {
        if (self::isConfigured()) {
            self::loadTpl('my_listings');
        }
    }

    /**
     * @hook phpListingsGetMyListings
     */
    public function hookPhpListingsGetMyListings(&$listing)
    {
        if (self::isConfigured() && $listing['shc_mode'] == 'auction') {
            $auction = new Auction();
            $options = $this->getListingOptions($listing['ID'], $listing);
            $auction->adaptOptions($listing, $options);
        }
    }

    /**
     * @hook phpGetPaymentGateways
     *
     * @since 3.0.0
     *
     * @param array $gateways
     * @param array $content
     */
    public function hookPhpGetPaymentGateways(&$gateways, &$content)
    {
        global $config, $rlPayment;

        $availableGateways = explode(',', $config['shc_payment_gateways']);
        $service = $rlPayment->getoption('service');
        if (in_array($service, ['shopping', 'auction']) && $gateways) {
            foreach ($gateways as $key => $gateway) {
                if (!in_array($gateway['Key'], $availableGateways)) {
                    unset($gateways[$key]);
                }
            }
        }

        if ($config['shc_method'] === 'multi'
            && in_array($GLOBALS['page_info']['Key'], ['shc_my_shopping_cart', 'shc_purchases'])
        ) {
            $dealerID = $rlPayment->getOption('dealer_id');
            $options = $this->getAccountOptions($dealerID);

            $gateways = array_filter($gateways, static function ($gateway) use ($options) {
                return (bool) $options[$gateway['Key'] . '_enable'];
            });
        }
    }

    /**
     * @hook phpGetPaymentGatewaysWhere
     *
     * @since 3.0.0
     *
     * @param string $sql
     */
    public function hookPhpGetPaymentGatewaysWhere(&$sql)
    {
        global $config, $rlPayment;

        $service = $rlPayment->getoption('service');
        if (in_array($service, ['shopping', 'auction'])
            && $config['shc_method'] == 'multi'
            && $config['shc_commission_enable']
        ) {
            $sql .= "AND `Parallel` = '1' ";
        }
    }

    /**
     * @hook preCheckoutPayment
     *
     * @since 3.0.0
     */
    public function hookPreCheckoutPayment()
    {
        global $errors, $rlPayment;

        $gateway = $_POST['gateway'] ? $_POST['gateway'] : $rlPayment->getGateway();

        $payment = new Payment();
        $result = $payment->setSellerPaymentsCredentials($gateway, $rlPayment->getOption('dealer_id'));

        if (!$result) {
            $errors[] = $GLOBALS['rlLang']->getSystem('shc_payment_gateway_not_configured');
        }
    }

    /**
     * @hook loadPaymentForm
     *
     * @since 3.0.0
     *
     * @param array $gatewayInfo
     */
    public function hookLoadPaymentForm(&$gatewayInfo)
    {
        global $config, $rlPayment;

        $payment = new Payment();
        $result = $payment->setSellerPaymentsCredentials($gatewayInfo['Key'], $rlPayment->getOption('dealer_id'));

        if (!$result) {
            $out = array(
                'status' => 'OK',
                'html' => '<script>printMessage("error", "' . $GLOBALS['rlLang']->getSystem('shc_payment_gateway_not_configured') . '");</script>',
            );
            print(json_encode($out));exit;
        }

        $GLOBALS['rlSmarty']->assign_by_ref('config', $config);
    }

    /**
     * @hook tplPrintPage
     *
     * @since 3.0.0
     */
    public function hookTplPrintPage()
    {
        global $page_info, $orderInfo, $auction_info;

        $print = new PrintOrder();

        switch ($page_info['Controller']) {
            case 'my_purchases':
            case 'my_items_sold':
                $print->printShopping($orderInfo);
                break;

            case 'my_auctions' :
                $print->printAuction($auction_info);
                break;
        }
    }

    /**
     * @hook apExtTransactionItem
     *
     * @since 3.0.0
     */
    public function hookApExtTransactionItem(&$transaction, $key, $value)
    {
        global $lang, $rlLang;

        if (!in_array($value['Service'], ['shopping', 'auction'])) {
            return;
        }

        if (!isset($lang['shc_orders'])) {
            $lang['shc_orders'] = $rlLang->getPhrase(['key' => 'shc_orders', 'db_check' => true]);
            $lang['shc_auction'] = $rlLang->getPhrase(['key' => 'shc_auction', 'db_check' => true]);
        }

        $transaction['Service'] = $lang[$value['Service'] == 'shopping' ? 'shc_orders' : 'shc_auction'];
    }

    /**
     * Check if plugin is available
     *
     * @return bool
     */
    public static function isConfigured()
    {
        global $config;

        if (($config['shc_module'] || $config['shc_module_auction'])
            && !defined('ANDROID_APP')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Load temaplte file
     *
     * @param string $name
     * @param string $dir
     */
    public static function loadTpl($name = '', $dir = '')
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'shoppingCart' . RL_DS . ($dir ? $dir . RL_DS : '') . 'view' . RL_DS . $name . '.tpl');
    }

    /**
     * Add currency to price
     *
     * @param  double $total
     * @return double
     */
    public static function addCurrency($total = 0)
    {
        global $config;

        $total = is_array($total) ? $total['total'] : $total;

        $total = number_format(
            $total,
            2,
            $config['price_separator'],
            $config['price_delimiter']
        );

        if ($config['system_currency_position'] == 'before') {
            $total = $config['system_currency'] . ' ' . $total;
        } else {
            $total = $total . ' ' . $config['system_currency'];
        }

        return $total;
    }

    /**
     * Complete order
     *
     * @param int $itemID
     * @param int $planID - not used in this method, but payment system will send it variable
     * @param int $accountID
     */
    public function completeOrder($itemID = 0, $planID = 0, $accountID = 0)
    {
        $orders = new Orders();
        $orders->complete($itemID, $accountID);
    }

    /**
     * Complete auction
     *
     * @param int $itemID
     * @param int $planID - not used in this method, but payment system will send it variable
     * @param int $accountID
     */
    public function completeAuction($itemID = 0, $planID = 0, $accountID = 0)
    {
        $auction = new Auction();
        $auction->complete($itemID, $accountID);
    }

    /**
     * Get plugin phrases by target
     *
     * @param  mixed $targetKey    - Controller key
     * @return bool $addJS         - Add phrases to js
     * @return array               - Phrases
     */
    public function getPhrases($targetKey, $addJS = false)
    {
        global $js_keys;

        $langCode = RL_LANG_CODE;

        if (is_array($targetKey)) {
            $sql_tk = "(`Target_key` = '" . implode("' OR `Target_key` = '", $targetKey) . "') ";
        } else {
            $sql_tk = "`Target_key` = '{$targetKey}' ";
        }

        $sql = "
            WHERE `Status` = 'active' AND `Code` = '{$langCode}' AND
            (({$sql_tk} AND `Plugin` = 'shoppingCart')
            OR `Key` IN ('config+name+general_common'))
        ";

        $phrases = $GLOBALS['rlLang']->preparePhrases($sql);

        if ($addJS) {
            foreach ($phrases as $key => $value) {
                $js_keys[] = $key;
            }
        }

        return $phrases;
    }

    /**
     * Get original listing price
     *
     * @param  mixed $value
     * @return string
     */
    public static function getPrice($value = '')
    {
        global $rlValid;

        $price = explode('|', $value);
        $currency = $price[1] ? $GLOBALS['lang']['data_formats+name+' . $price[1]] : '';

        if ($GLOBALS['config']['system_currency_position'] == 'before') {
            $out = $currency . ' ' . $rlValid->str2money($price[0], true);
        } else {
            $out = $rlValid->str2money($price[0], true) . ' ' . $currency;
        }

        return $out;
    }

    /**
     * Get listing options
     *
     * @param  int $listingID
     * @param  array $listing
     * @return array
     */
    public function getListingOptions($listingID, &$listing = [])
    {
        if (!$listingID) {
            return [];
        }

        $options = $GLOBALS['rlDb']->fetch('*', array('Listing_ID' => $listingID), null, 1, 'shc_listing_options', 'row');
        $serializeble = ['Shipping_options', 'Dimensions', 'Shipping_fixed_prices'];
        $explodable = ['Shipping_method_fixed'];

        if ($options && $listing) {
            foreach ($options as $opKey => $opValue) {
                if ($opKey == 'ID') {
                    continue;
                }
                $listing[$opKey] = in_array($opKey, $serializeble) ? unserialize(trim($opValue)) : $opValue;

                if (in_array($opKey, $explodable)) {
                    $listing[$opKey] = explode(',', $opValue);
                }
            }
        }

        return $options;
    }

    /**
     * Get account options
     *
     * @param int $accountID
     * @param bool $output
     * @return array
     */
    public function getAccountOptions($accountID = 0, $output = false)
    {
        global $rlDb;

        $options = $rlDb->fetch('*', array('Account_ID' => $accountID), null, 1, 'shc_account_settings', 'row');

        if (!$options) {
            $insert = [
                'Account_ID' => $accountID,
            ];

            if ($rlDb->insertOne($insert, 'shc_account_settings')) {
                $options = $rlDb->fetch('*', array('Account_ID' => $accountID), null, 1, 'shc_account_settings', 'row');
            }
        }

        if ($output) {
            $GLOBALS['rlSmarty']->assign_by_ref('shcAccountSettings', $options);
        }

        return $options;
    }

    /**
     * Save account options
     *
     * @param int $accountID
     * @return array
     */
    public function saveAccountSettings($data = [])
    {
        global $account_info, $rlHook;

        $options = $this->getAccountOptions($account_info['ID']);

        foreach ($data as $key => $val) {
            if (!isset($options[$key])) {
                unset($data[$key]);
            }
        }

        $update = array(
            'fields' => $data,
            'where' => array(
                'Account_ID' => $account_info['ID'],
            ),
        );

        $htmlFields = array('Shipping', '2co_secret_key', '2co_secret_word');

        /**
         * @since 3.0.1
         */
        $rlHook->load('shcSaveAccountSettings', $htmlFields, $update);

        return $GLOBALS['rlDb']->updateOne($update, 'shc_account_settings', $htmlFields);
    }

    /**
     * delete digital product
     *
     * @param int $itemID
     */
    public function deleteFile($itemID = 0)
    {
        $options = $GLOBALS['rlDb']->fetch('*', array('Listing_ID' => $itemID), null, 1, 'shc_listing_options', 'row');

        if ($options['Digital'] && $options['Digital_product']) {
            $file = RL_FILES . $options['Digital_product'];

            if (file_exists($file)) {
                unlink($file);
                return true;
            }
        }

        return false;
    }

    /**
     * Check fields of payment gateways
     */
    public function checkFieldsPaymentGateways()
    {
        global $rlDb;

        $gateways = $rlDb->fetch('*', null, null, null, 'payment_gateways');

        foreach ($gateways as $gateway) {
            if (!in_array($gateway['Key'], $this->systemPaymentGateways, true)) {
                $rlGateway = self::getInstanceGateway($gateway['Key'], $gateway['Plugin']);

                if (method_exists($rlGateway, 'addAccountFields')) {
                    $rlGateway->addAccountFields();
                }
            }
        }
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
            return new stdClass();
        }

        $className = ucfirst($gateway);
        $GLOBALS['reefless']->loadClass($className, null, $plugin);
        return $GLOBALS['rl' . $className];
    }

    /**
     * @hook editListingDataChecking
     *
     * @since 3.0.0
     */
    public function hookEditListingDataChecking(&$editListing, &$data, &$errors, &$error_fields)
    {
        $this->validateListingData($editListing, $data, $errors, $error_fields);
    }

    /**
     * Create instance of gateway object
     *
     * @since 3.0.0
     *
     * @param  object $objectListing
     * @param  array $data
     * @param  array $errors
     * @param  array $error_fields
     */
    public function validateListingData(&$objectListing, &$data, &$errors, &$error_fields)
    {
        global $lang, $config, $l_deny_files_regexp;

        $fshc = $_POST['fshc'];

        if ($fshc['shc_mode'] != 'fixed' && $fshc['shc_mode'] != 'auction') {
            return;
        }

        if (empty($fshc['shc_mode']) && isset($data[$config['price_tag_field']])) {
            $errors[] = str_replace(
                '{field}',
                '<span class="field_error">"' . $lang['shc_price_format'] . '"</span>',
                $lang['notice_field_empty']
            );
        }

        if ($_SESSION['account']) {
            $account_types = explode(",", $config['shc_account_types']);

            if (!in_array($_SESSION['account']['Type'], $account_types)) {
                $errors[] = $lang['shc_account_type_not_available'];
            }

            switch ($fshc['shc_mode']) {
                case 'fixed':
                    if (empty($fshc['shc_quantity']) && !$fshc['digital']) {
                        $errors[] = str_replace(
                            '{field}',
                            '<span class="field_error">"' . $lang['shc_quantity'] . '"</span>',
                            $lang['notice_field_empty']
                        );
                    }
                    break;

                case 'auction':
                    if (empty($fshc['shc_bid_step'])) {
                        $errors[] = str_replace(
                            '{field}',
                            '<span class="field_error">"' . $lang['shc_bid_step'] . '"</span>',
                            $lang['notice_field_empty']
                        );
                    }
                    if (empty($fshc['shc_days'])) {
                        $errors[] = str_replace(
                            '{field}',
                            '<span class="field_error">"' . $lang['shc_days'] . '"</span>',
                            $lang['notice_field_empty']
                        );
                    }
                    break;
            }
        } else {
            $errors[] = $lang['shc_price_format_not_available'];
        }

        if ($config['shc_digital_product'] && !empty($_FILES['fshc']['digital_product']['name'])) {
            $file_ext = explode('.', $_FILES['fshc']['digital_product']['name']);
            $file_ext = array_reverse($file_ext);
            $file_ext = $file_ext[0];

            if (!$GLOBALS['rlValid']->isFile('zip', $file_ext)
                || preg_match($l_deny_files_regexp, $_FILES['fshc']['digital_product']['name'])
            ) {
                $errors[] = str_replace(
                    array('{field}', '{ext}'),
                    array(
                        '<span class="field_error">"' . $lang['shc_digital_product'] . '"</span>',
                        '<span class="field_error">"' . $file_ext . '"</span>',
                    ),
                    $GLOBALS['lang']['notice_bad_file_ext']
                );

                // remove tmp file
                unset($_FILES['fshc']['digital_product']['name']);
            }
        }

        $fixedMethods = is_array($fshc['shipping_method_fixed']) ? $fshc['shipping_method_fixed'] : [];
        if (in_array($fshc['shc_shipping_price_type'], ['free', 'fixed'])
            && count($fixedMethods) <= 0
            && !$fshc['digital']
        ) {
            if (!isset($lang['shc_shipping_method'])) {
                $lang = array_merge($lang, $this->getPhrases(['my_shopping_cart']));
            }

            $errors[] = str_replace(
                '{field}',
                '<span class="field_error">"' . $lang['shc_shipping_method'] . '"</span>',
                $lang['notice_checkbox_empty']
            );
        }
    }

    /**
     * @hook phpGetPaymentGatewaysAfter
     *
     * @since 3.0.0
     */
    public function hookPhpGetPaymentGatewaysAfter(&$content)
    {
        global $config, $rlPayment;

        $options = [];
        $service = $rlPayment->getOption('service');

        if ($config['shc_method'] == 'multi') {
            $dealerID = $rlPayment->getOption('dealer_id');
            $options = $this->getAccountOptions($dealerID);
        }

        if (!in_array($service, ['shopping', 'auction'])
            || !$config['shc_allow_cash']
            || ($config['shc_method'] == 'multi' && !$options['allow_cash'])
        ) {
            return;
        }

        $content .= $GLOBALS['rlSmarty']->fetch(RL_PLUGINS . 'shoppingCart/view/cash.tpl', null, null, false);
    }

    /**
     * @hook myListingsPreSelect
     *
     * @since 3.0.0
     */
    public function hookMyListingsPreSelect()
    {
        global $lang;

        if (!isset($lang['shc_winner'])) {
            $lang = array_merge($lang, $this->getPhrases(['listing_details', 'shopping_cart']));
        }
    }

    /**
     * @hook phpBankWireTransferAfterComplete
     *
     * @since 3.0.0
     */
    public function hookPhpBankWireTransferAfterComplete(&$rlPayment)
    {
        global $rlDb, $config;

        if ($rlPayment->getOption('service') != 'shopping' || $config['shc_method'] != 'multi') {
            return;
        }

        $update = array(
            'fields' => array(
                'Bank_transfer' => '1'
            ),
            'where' => array(
                'ID' => $rlPayment->getOption('item_id')
            )
        );

        $rlDb->updateOne($update, 'shc_orders');

        $update = array(
            'fields' => array(
                'Status' => 'completed',
                'Order_ID' => $rlPayment->getOption('item_id'),
            ),
            'where' => array(
                'Order_key' => $_COOKIE['shc_txn_id'],
                'Dealer_ID' => $rlPayment->getOption('dealer_id')
            )
        );

        $rlDb->updateOne($update, 'shc_order_details');
        $shopping = new Shopping();
        $items = $shopping->getItems($config['shc_count_items_block']);
        $shopping->updateCookie($items);
    }

    /**
     * Print notice in admin panel
     *
     * @since 3.0.0
     * @hook apNotifications
     *
     * @param array $notices - global notifications array
     */
    public function hookApNotifications(&$notices)
    {
        $migrateData = new \ShoppingCart\Admin\MigrateData();
        $migrateData->printNotice($notices);
    }

    /**
     * @hook phpLoginSaveSessionData
     *
     * @since 3.0.2
     */
    public function hookPhpLoginSaveSessionData()
    {
        $shopping = new Shopping();
        $shopping->synchronizeItems();
    }

    /**
     * @hook phpLogOut
     *
     * @since 3.0.2
     */
    public function hookPhpLogOut()
    {
        $shopping = new Shopping();
        $shopping->synchronizeItems();
    }

    /**
     * @hook phpMyAdsSearchMiddle
     *
     * @since 3.0.2
     */
    public function hookPhpMyAdsSearchMiddle()
    {
        global $listings;

        $auction = new Auction();

        foreach ($listings as &$listing) {
            if ($listing['shc_mode'] == 'auction') {
                $options = $this->getListingOptions($listing['ID'], $listing);
                $auction->adaptOptions($listing, $options);
            }
        }
    }

    /**
     * Get escrow test class
     *
     * @since 3.1.0
     *
     * @return object
     */
    public function getEscrowTest()
    {
        return new EscrowTest();
    }
}
