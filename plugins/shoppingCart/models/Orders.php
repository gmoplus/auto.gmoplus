<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: ORDERS.PHP
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

use \ShoppingCart\CartProcessing;
use \ShoppingCart\Currency;
use \ShoppingCart\Shipping;

/**
 * @since 3.0.0
 */
class Orders
{
    /**
     * Order ID
     *
     * @var int
     */
    protected $orderID = 0;

    /**
     * Total found rows
     *
     * @var int
     */
    protected $totalRows = 0;

    /**
     * Class constructor
     */
    public function __construct()
    {
        if (isset($_SESSION['shc_options']['ID'])) {
            $this->orderID = (int) $_SESSION['shc_options']['ID'];
        }
    }

    /**
     * Create order with basic data
     *
     * @param array $data
     */
    public function create($data = [])
    {
        global $rlDb, $account_info, $config;

        if (!$data) {
            return;
        }

        // check empty orders
        $sql = "SELECT `T1`.`ID`, count(`T2`.`ID`) AS `totalItems` ";
        $sql .= "FROM `{db_prefix}shc_orders` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}shc_order_details` AS `T2` ON `T1`.`Order_key` = CONCAT(`T2`.`Order_key`, '-D', `T2`.`Dealer_ID`) ";
        $sql .= "WHERE `T1`.`Buyer_ID` = '{$account_info['ID']}' AND `T1`.`Status` = 'unpaid' ";
        $tmpOrders = $rlDb->getAll($sql);

        if ($tmpOrders) {
            foreach ($tmpOrders as $key => $value) {
                if ($value['totalItems'] <= 0) {
                    $rlDb->delete(['ID' => $value['ID']], 'shc_orders');
                }
            }
        }

        // Calculate basic commission
        $commission = PriceFormat::calculateCommission($data['Total'], true);
        $data['Commission'] = (float) $config['shc_commission'];
        $data['Commission_total'] = round($commission, 2);

        $rlDb->insertOne($data, 'shc_orders');
        $this->setID($rlDb->insertID());
    }

    /**
     * Update order information
     *
     * @param array $data
     * @param array $htmlFields
     */
    public function update($data = [], $htmlFields = [])
    {
        if (!$data) {
            return;
        }

        $GLOBALS['rlDb']->updateOne($data, 'shc_orders', $htmlFields);
    }

    /**
     * Delete order
     *
     * @param int $id
     */
    public function delete($id = 0)
    {
        global $rlDb, $rlMail, $config, $reefless, $rlSmarty, $lang, $rlShoppingCart;

        if (!$id) {
            return false;
        }

        $reefless->loadClass('Listings');
        $reefless->loadClass('Mail');

        if (!is_object($rlSmarty)) {
            require_once RL_LIBS . 'smarty' . RL_DS . 'Smarty.class.php';
            $reefless->loadClass('Smarty');
        }

        $rlSmarty->registerFunctions();

        $orderInfo = $this->get((int) $id, true, true);

        if ($rlDb->delete(array('ID' => (int) $id), 'shc_orders')) {
            $rlDb->delete(array('Order_ID' => (int) $id), 'shc_order_details');

            $lang = array_merge($lang, $rlShoppingCart->getPhrases(['add_listing', 'shopping_cart', 'my_shopping_cart']));
            $rlSmarty->assign('lang', $lang);
            $rlSmarty->assign('order_info', $orderInfo);
            $details = $rlSmarty->fetch(RL_PLUGINS . 'shoppingCart/view/order_details_mail.tpl', null, null, false);

            // send notification buyer
            $mail_tpl = $rlMail->getEmailTemplate('shc_order_remove_by_admin');

            $find = array('{name}', '{order_key}', '{details}');
            $replace = array($orderInfo['bFull_name'], $orderInfo['Order_key'], $details);
            $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);

            $rlMail->send($mail_tpl, $orderInfo['bMail'], null, $config['site_main_email'], $_SESSION['sessAdmin']['name']);

            // send notification dealer
            if ($config['shc_method'] == 'multi') {
                $mail_tpl = $rlMail->getEmailTemplate('shc_order_remove_by_admin_dealer');

                $find = array('{name}', '{order_key}', '{details}');
                $replace = array($orderInfo['dFull_name'], $orderInfo['Order_key'], $details);
                $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);

                $rlMail->send($mail_tpl, $orderInfo['dMail'], null, $config['site_main_email'], $_SESSION['sessAdmin']['name']);
            }

            return true;
        }

        return false;
    }

    /**
     * Get order info
     *
     * @param int $id
     * @param bool $details
     * @param bool $full
     * @return array
     */
    public function get($id = 0, $details = false, $full = false, $accountField = '')
    {
        global $account_info, $lang;

        if (!$id) {
            return [];
        }

        $where = is_int($id) ? "`T1`.`ID` = '{$id}'" : "`T1`.`Order_key` = '{$id}'";

        $sql = "SELECT `T1`.*, `T2`.`Username` AS `bUsername`, `T2`.`Own_address` AS `bOwn_address`, `T2`.`Mail` AS `bMail`, ";
        $sql .= "IF(`T2`.`Last_name` <> '' AND `T2`.`First_name` <> '', CONCAT(`T2`.`First_name`, ' ', `T2`.`Last_name`), `T2`.`Username`) AS `bFull_name`, ";
        $sql .= "`T3`.`Username` AS `dUsername`, `T3`.`Own_address` AS `dOwn_address`, `T3`.`Mail` AS `dMail`, ";
        $sql .= "IF(`T3`.`Last_name` <> '' AND `T3`.`First_name` <> '', CONCAT(`T3`.`First_name`, ' ', `T3`.`Last_name`), `T3`.`Username`) AS `dFull_name` ";
        $sql .= "FROM `{db_prefix}shc_orders` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Buyer_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T3` ON `T1`.`Dealer_ID` = `T3`.`ID` ";
        $sql .= "WHERE {$where} ";
        if ($accountField) {
            $sql .= "AND `T1`.`{$accountField}` = {$account_info['ID']} ";
        }
        $orderInfo = $GLOBALS['rlDb']->getRow($sql);

        if ($orderInfo) {
            $this->adaptShippingOptions($orderInfo);

            if ($details) {
                $orderInfo['cart'] = $this->getDetails($orderInfo['Order_key'], $full);
                $orderInfo['items'] = $orderInfo['cart']['items'];

                foreach ($orderInfo['items'] as $iKey => $iVal) {
                    $orderInfo['items'][$iKey]['Price'] = $GLOBALS['rlShoppingCart']::addCurrency($iVal['Price']);
                    $orderInfo['items'][$iKey]['total'] = $GLOBALS['rlShoppingCart']::addCurrency($iVal['total']);

                    if ($iVal['Shipping_price_type'] == 'fixed') {
                        $orderInfo['items'][$iKey]['shipping_item_options']['service'] = $lang['shc_' . $iVal['shipping_item_options']['method']];
                        continue;
                    }

                    $itemShipping = $iVal['shipping_item_options'];
                    if (empty($itemShipping['service'])) {
                        $service = $lang['shipping_methods+name+' . $itemShipping['method']];
                        if (!empty($itemShipping['domestic_services'])) {
                            $service .= " ({$itemShipping['domestic_services']})";
                        }
                        if (!empty($itemShipping['international_services'])) {
                            $service .= " ({$itemShipping['international_services']})";
                        }
                        $orderInfo['items'][$iKey]['shipping_item_options']['service'] = $service;
                    }
                }
                $orderInfo['cart']['items'] = $orderInfo['items'];
            }

            $orderInfo['Commission'] = $GLOBALS['rlShoppingCart']::addCurrency($orderInfo['Commission']);
            $orderInfo['Total_source'] = $orderInfo['Total'];
            $orderInfo['Total'] = $GLOBALS['rlShoppingCart']::addCurrency($orderInfo['Total']);
            $orderInfo['Shipping_price'] = $GLOBALS['rlShoppingCart']::addCurrency($orderInfo['Shipping_price']);
        }

        return $orderInfo;
    }

    /**
     * Get order details
     *
     * @param int $orderID
     * @param bool $isActive
     * @return array
     */
    public function getDetails($orderID = 0, $isActive = true)
    {
        global $rlDb, $config;

        if (!$orderID) {
            return [];
        }

        $total = 0;
        $commission = 0;
        $shipping_fixed_price = 0;
        $sqlWhere = is_int($orderID)
        ? "`Order_ID` = '{$orderID}'"
        : "`Order_key` = '" . explode('-', $orderID)[0] . "'";

        $sql = "SELECT * FROM `{db_prefix}shc_order_details` WHERE {$sqlWhere} ";
        if (!$isActive) {
            $sql .= "AND `Status` = 'active' ";
        }
        $sql .= "ORDER BY `Date` DESC";

        $items = $rlDb->getAll($sql);

        if ($items) {
            foreach ($items as $iKey => $iVal) {
                $listing = $GLOBALS['rlListings']->getListing($iVal['Item_ID'], true);
                $listing_options = $rlDb->fetch(
                    '*',
                    array('Listing_ID' => $iVal['Item_ID']),
                    null,
                    1,
                    'shc_listing_options',
                    'row'
                );

                $items[$iKey]['shc_available'] = $listing['shc_available'];
                if ($items[$iKey]['Status'] == 'deleted' || ($listing_options['Quantity_real'] <= 0 && !$iVal['Digital'])) {
                    $items[$iKey]['shc_available'] = false;
                }

                $items[$iKey]['main_photo'] = $listing['Main_photo'];
                $items[$iKey]['quantity'] = $listing['shc_quantity'];
                $items[$iKey]['weight'] = $listing_options['Weight'];
                $items[$iKey]['commission'] = $listing_options['Commission'];
                $items[$iKey]['listing_link'] = $listing['listing_link'];

                // shipping
                if ($listing_options) {
                    $items[$iKey]['shipping_item_options'] = unserialize($iVal['Shipping_item_options']);
                    $items[$iKey]['shipping_options'] = unserialize($listing_options['Shipping_options']);
                    $items[$iKey]['dimensions'] = unserialize($listing_options['Dimensions']);
                    $items[$iKey]['Shipping_price_type'] = $listing_options['Shipping_price_type'];
                    $items[$iKey]['Shipping_price'] = (float) $listing_options['Shipping_price'];
                    if ($config['shc_shipping_price_fixed'] == 'multi') {
                        $items[$iKey]['Shipping_fixed_prices'] = unserialize($listing_options['Shipping_fixed_prices']);
                    }
                    if ($config['shc_digital_product']) {
                        $items[$iKey]['Digital'] = (bool) $listing_options['Digital'];
                        $items[$iKey]['Digital_product'] = $listing_options['Digital_product'];
                        $items[$iKey]['Quantity_unlim'] = $listing_options['Quantity_unlim'];
                    }
                }

                // calculate total price
                $items[$iKey]['price_original'] = $GLOBALS['rlShoppingCart']->getPrice($listing[$config['price_tag_field']]);
                $items[$iKey]['total'] = round(($iVal['Quantity'] * $iVal['Price']), 2);
                $total += (float) $items[$iKey]['total'];
                $commission += (float) $listing_options['Commission'];
                $shipping_fixed_price += (float) $items[$iKey]['Shipping_fixed_price'];
            }
        }

        $cart['items'] = $items;
        $cart['total'] = round($total, 2);
        $cart['shipping_price'] = round($shipping_fixed_price, 2);
        $cart['commission'] = round($commission, 2);

        return $cart;
    }

    /**
     * Adapt shipping options
     *
     * @param array $orderInfo
     */
    public function adaptShippingOptions(&$orderInfo)
    {
        global $config;

        if (!$orderInfo['Shipping_options']) {
            return;
        }

        $orderInfo['Shipping_options'] = unserialize($orderInfo['Shipping_options']);

        $shipping = new Shipping();
        $fields = $shipping->getShippingFields();

        if ($fields) {
            $options = $orderInfo['Shipping_options']['fields'];
            $multifield_format_key = 'countries';

            if ($config['shc_use_multifield'] && $config['mf_geo_data_format']) {
                $get_format_data = json_decode($config['mf_geo_data_format'], true);
                $multifield_format_key = $get_format_data['Key'] ?: $multifield_format_key;
            }

            foreach ($fields as $fKey => $fValue) {
                if (!empty($options[$fValue['Key']])) {
                    if ($config['shc_use_multifield'] && preg_match('/location_level/i', $fValue['Key'])) {
                        $fValue['Type'] = 'select';
                        $fValue['Condition'] = $multifield_format_key;
                    }
                    $fields[$fKey]['value'] = $GLOBALS['rlCommon']->adaptValue(
                        $fValue,
                        $options[$fValue['Key']],
                        'shipping'
                    );
                }

                if (empty($fields[$fKey]['value'])) {
                    unset($fields[$fKey]);
                }
            }

            $orderInfo['fields'] = $fields;
        }
    }

    /**
     * Save shipping data
     *
     * @param array $data
     * @param array $cartInstance
     */
    public function saveShippingData($data = [], $cartInstance = [])
    {
        global $rlDb, $rlLang, $errors, $error_fields, $account_info, $config, $reefless;

        $cartProcessing = new CartProcessing();
        $shipping = new Shipping();
        $currency = new Currency();
        $dealerInfo = [];

        // save shipping information
        $shippingOptions = $this->prepareShippingOptionsToDb();
        $shippingData = array(
            'fields' => array(
                'Shipping_options' => serialize($shippingOptions),
            ),
            'where' => array('ID' => $this->getID()),

        );
        $this->update($shippingData, array('Shipping_options'));

        foreach ($data as $sKey => $sVal) {
            $cartProcessing->setOption($sKey, $sVal);
        }

        if ($cartProcessing->getDealer()) {
            $dealerInfo = $cartProcessing->getDealerInfo();
        }

        // calculate shipping rate
        // if only enable automatic calculate option in shipping method
        foreach ($cartInstance['items'] as $iKey => $iValue) {
            $pItemsVal = $_POST['items'][$iValue['ID']];
            $itemMethod = $pItemsVal['method'];

            if (!$iValue['shc_available'] || !empty($iValue['Digital'])) {
                continue;
            }

            if ($iValue['Shipping_price_type'] == 'free') {
                $phraseKey = $pItemsVal['shipping_method_fixed'] ? 'shc_' . $pItemsVal['shipping_method_fixed'] : 'pickup';

                $update_item = array(
                    'fields' => array(
                        'Shipping_item_options' => serialize(array(
                            'total' => 0,
                            'title' => $rlLang->getPhrase($phraseKey, null, null, true),
                            'key' => $phraseKey,
                            'method' => $pItemsVal['shipping_method_fixed']
                        )),
                        'Buyer_ID' => $account_info['ID'],
                    ),
                    'where' => array('ID' => $iValue['ID']),

                );

                $rlDb->updateOne(
                    $update_item,
                    'shc_order_details',
                    array('Shipping_item_options')
                );
                continue;
            }

            // if fixed price
            if ($iValue['Shipping_price_type'] == 'fixed') {
                if ($pItemsVal['shipping_method_fixed'] == 'pickup') {
                    $update_item = array(
                        'fields' => array(
                            'Shipping_item_options' => serialize(array(
                                'method' => 'pickup',
                                'total' => 0,
                                'title' => $rlLang->getPhrase('shc_pickup', null, null, true),
                            )),
                            'Buyer_ID' => $account_info['ID'],
                        ),
                        'where' => array('ID' => $iValue['ID']),
                    );
                    $rlDb->updateOne(
                        $update_item,
                        'shc_order_details',
                        array('Shipping_item_options')
                    );

                    continue;
                }
                if ($config['shc_shipping_price_fixed'] == 'multi') {
                    $prices = $iValue['Shipping_fixed_prices'];
                    $fixedPrice = 0;
                    if ($prices) {
                        foreach ($prices as $pKey => $pVal) {
                            if ($pKey == $pItemsVal['fixed_price']) {
                                $fixedPrice = (float) $pVal['price'];
                                $fixedIndex = $pKey;
                            }
                        }
                    }
                    $data['shipping_price'] += $this->getShippingDiscount($fixedPrice, $iValue);
                    $update_item = array(
                        'fields' => array(
                            'Shipping_item_options' => serialize(array(
                                'method' => 'courier',
                                'total' => $this->getShippingDiscount($fixedPrice, $iValue),
                                'fixed_index' => $fixedIndex,
                                'title' => $rlLang->getPhrase('shipping_methods+name+courier', null, null, true),
                            )),
                            'Buyer_ID' => $account_info['ID'],
                        ),
                        'where' => array('ID' => $iValue['ID']),
                    );
                } else {
                    $data['shipping_price'] += (float) $this->getShippingDiscount($iValue['Shipping_price'], $iValue);
                    $update_item = array(
                        'fields' => array(
                            'Shipping_item_options' => serialize(array(
                                'method' => 'courier',
                                'total' => (float) $this->getShippingDiscount($iValue['Shipping_price'], $iValue),
                                'title' => $rlLang->getPhrase('shipping_methods+name+courier', null, null, true),
                            )),
                            'Buyer_ID' => $account_info['ID'],
                        ),
                        'where' => array('ID' => $iValue['ID']),
                    );
                }
                $rlDb->updateOne(
                    $update_item,
                    'shc_order_details',
                    array('Shipping_item_options')
                );

                continue;
            }

            if (!$itemMethod && $iValue['Shipping_price_type'] == 'calculate') {
                $f_name = '<span class="field_error">' . $rlLang->getPhrase('shc_shipping_method', null, null, true) . ' (' . $iValue['Item'] . ')</span>';
                $errors[] = str_replace('{field}', $f_name, $rlLang->getPhrase('notice_select_empty', null, null, true));
                $error_fields[] .= "items[{$iValue['ID']}][method]";

                continue;
            }

            if (empty($pItemsVal[$itemMethod]['service'])
                && $iValue['Shipping_price_type'] == 'calculate'
                && $iValue['shipping'][$itemMethod]['auto']
                && $itemMethod != 'DHL'
            ) {
                if ($itemMethod == 'usps'
                    && ($pItemsVal['usps']['domestic_services']
                        || $pItemsVal['usps']['international_services']
                    )
                ) {
                    // handle secondary level shipping
                } else {
                    $f_name = '<span class="field_error">' . $rlLang->getPhrase('shc_ups_service', null, null, true) . ' (' . $iValue['Item'] . ')</span>';
                    $errors[] = str_replace('{field}', $f_name, $rlLang->getPhrase('notice_select_empty', null, null, true));
                    $error_fields[] .= "items[{$iValue['ID']}][method]";

                    continue;
                }
            }

            if ($iValue['shipping'][$itemMethod]['auto']) {
                if ($iValue['services']) {
                    if (isset($iValue['services']['total'])) {
                        $quotePrice = (float) $iValue['services']['total'];
                    } else {
                        foreach ($iValue['services'] as $service) {
                            if ($service['service'] == $pItemsVal[$itemMethod]['service']) {
                                $quotePrice = (float) $service['total'];
                                break;
                            }
                        }
                    }
                } else {
                    $request = $shipping->prepareQuoteData($itemMethod, $_POST, $iValue, $iValue);

                    $methodClass = '\ShoppingCart\Shipping\\' . ucfirst($itemMethod);
                    $methodClass = new $methodClass();
                    $methodClass->init();
                    $quote = $methodClass->getQuote($request, $iValue);

                    if (!$quote['error']) {
                        if ($quote['total']) {
                            $quotePrice = (float) $quote['total'];
                        } else {
                            $reefless->rlArraySort($quote, 'total');
                            $quotePrice = (float) $quote[0]['total'];
                        }
                    }

                    if (!$quote['error']) {
                        $shipping->addQuote($quote, $iValue['ID']);
                    }
                }

                if (!$quote['error']) {
                    $data['shipping_price'] += (float) $currency->convertPrice($quotePrice, 'USD');
                    $itemQuote = [
                        'method' => $itemMethod,
                        'service' => $pItemsVal[$itemMethod]['service'],
                        'title' => $pItemsVal[$itemMethod]['title'],
                        'domestic_services' => $pItemsVal[$itemMethod]['domestic_services'],
                        'international_services' => $pItemsVal[$itemMethod]['international_services'],
                        'total' => $quotePrice,
                    ];

                    $update_item = array(
                        'fields' => array(
                            'Shipping_item_options' => serialize($itemQuote),
                            'Buyer_ID' => $account_info['ID'],
                        ),
                        'where' => array('ID' => $iValue['ID']),
                    );

                    $rlDb->updateOne(
                        $update_item,
                        'shc_order_details',
                        array('Shipping_item_options')
                    );
                } else {
                    $errors[] = $quote['error'];
                }
            } else {
                $data['shipping_price'] += (float) $this->getShippingDiscount($iValue['shipping'][$itemMethod]['price'], $iValue);

                $update_item = array(
                    'fields' => array(
                        'Shipping_item_options' => serialize(array(
                            'method' => $pItemsVal['method'],
                            'service' => $pItemsVal[$itemMethod]['service'],
                            'total' => (float) $this->getShippingDiscount($iValue['shipping'][$itemMethod]['price'], $iValue),
                            'title' => $itemMethod,
                        )),
                        'Buyer_ID' => $account_info['ID'],
                    ),
                    'where' => array('ID' => $iValue['ID']),
                );

                $rlDb->updateOne(
                    $update_item,
                    'shc_order_details',
                    array('Shipping_item_options')
                );
            }
        }

        if (!$errors) {
            $cartProcessing->setOption('shipping_price', $data['shipping_price']);
            $cartProcessing->setOption(
                'total',
                round(($cartInstance['total'] + $data['shipping_price']), 2)
            );
            $cartProcessing->setOption('commission', round($cartInstance['commission'], 2));
            $shippingData = array(
                'fields' => array(
                    'Shipping_price' => $data['shipping_price'],
                    'Total' => round(($cartInstance['total'] + $data['shipping_price']), 2),
                    'Commission' => (float) $config['shc_commission'],
                    'Commission_total' => round($cartInstance['commission'], 2),
                ),
                'where' => array('ID' => $this->getID()),

            );

            $this->update($shippingData);
        }

        return true;
    }

    /**
     * Complete order
     *
     * @param int $itemID
     * @param int $accountID
     * @param bool $cash
     */
    public function complete($itemID = 0, $accountID = 0, $cash = false)
    {
        global $rlMail, $rlSmarty, $rlDb, $config, $lang;

        if (!$itemID || !$accountID) {
            return;
        }

        if (!is_object($rlSmarty)) {
            require_once RL_LIBS . 'smarty' . RL_DS . 'Smarty.class.php';
            $GLOBALS['reefless']->loadClass('Smarty');
        }
        $rlSmarty->registerFunctions();

        $orderInfo = $this->get((int) $itemID, true, false);

        if ($orderInfo['Buyer_ID'] == $accountID) {
            $GLOBALS['reefless']->loadClass('Mail');
            $orderKey = explode("-", $orderInfo['Order_key']);

            if ($cash) {
                $updateOrder = array(
                    'Status' => 'pending',
                    'Cash' => '1',
                );
            } else {
                $updateOrder = array(
                    'Status' => 'paid',
                    'Pay_date' => 'NOW()',
                );

                $orderInfo['Status'] = 'paid';
            }

            $isDigital = true;
            foreach ((array) $orderInfo['cart']['items'] as $_item) {
                if (!$_item['Digital']) {
                    $isDigital = false;
                    break;
                }
            }
            $updateOrder['Shipping_status'] = $orderInfo['isDigital'] = $isDigital ? 'delivered' : 'pending';

            /**
             * @since 3.0.2
             */
            $GLOBALS['rlHook']->load('phpShcOrderComplete', $updateOrder, $orderInfo);

            $rlDb->updateOne(array(
                'fields' => $updateOrder,
                'where' => array('ID' => $itemID),
            ), 'shc_orders');

            $auction = new Auction();
            $auctionBoughtNow = $rlMail->getEmailTemplate('auction_bought_now');

            foreach ((array) $orderInfo['cart']['items'] as $iKey => $iVal) {
                if (!$iVal['shc_available']) {
                    // Delete unavailable item from the cart
                    $rlDb->delete(['ID' => $iVal['ID']], 'shc_order_details');
                    continue;
                }

                $quantity = (int) $iVal['Quantity'];

                if ($iVal['shc_mode'] == 'auction') {
                    $rlDb->updateOne(array(
                        'fields' => array(
                            'shc_auction_status' => 'closed',
                            'shc_end_time' => 'NOW()',
                            'shc_quantity' => 0,
                        ),
                        'where' => array(
                            'ID' => (int) $iVal['Item_ID'],
                        ),
                    ), 'listings');

                    $bidders = $auction->getBidders($iVal['Item_ID']);

                    if ($bidders) {
                        foreach ($bidders as $bKey => $bValue) {
                            $copy_auctionBoughtNow = $auctionBoughtNow;

                            $search = array('{bidder_name}', '{item}', '{date}');
                            $replace = array($bVal['Full_name'], $order_info['title'], date('Y-m-d H:i:s'));

                            $copy_auctionBoughtNow['body'] = str_replace($search, $replace, $copy_auctionBoughtNow['body']);
                            $GLOBALS['rlMail']->send($copy_auctionBoughtNow, $bVal['Mail']);
                            unset($copy_auctionBoughtNow);
                        }
                    }
                }

                if (!$iVal['Digital'] || ($iVal['Digital'] && !$iVal['Quantity_unlim'])) {
                    if ($config['shc_items_cart_duration'] == 'unlimited') {
                        $listing = $GLOBALS['rlListings']->getListing($iVal['Item_ID']);
                        // update quantity
                        $sql = "UPDATE `{db_prefix}listings` SET `shc_quantity` = `shc_quantity` - {$quantity} ";
                        if ($listing['shc_quantity'] <= $quantity) {
                            $sql .= ", `shc_available` = '0' ";
                        }
                        $sql .= "WHERE `ID` = {$iVal['Item_ID']}";
                        $rlDb->query($sql);

                        // update cart another users
                        $quantityLeft = $listing['shc_quantity'] - $quantity;
                        $quantityLeft = $quantityLeft < 0 ? 0 : $quantityLeft;

                        $sql = "SELECT * FROM `{db_prefix}shc_order_details` ";
                        $sql .= "WHERE `Item_ID` = {$iVal['Item_ID']} AND `Status` <> 'completed' ";
                        $sql .= "AND `Quantity` > {$quantityLeft}";
                        $itemsReserved = $rlDb->getAll($sql);

                        if ($itemsReserved) {
                            foreach ($itemsReserved as $irKey => $irVal) {
                                $sql = "UPDATE `{db_prefix}shc_order_details` SET `Quantity` = {$quantityLeft} ";
                                $sql .= "WHERE `ID` = {$iVal['ID']}";
                                $rlDb->query($sql);
                            }
                        }
                    } elseif ($listing['shc_quantity'] <= $quantity) {
                        $sql = "UPDATE `{db_prefix}listings` SET `shc_available` = '0' ";
                        $sql .= "WHERE `ID` = {$iVal['Item_ID']}";
                        $rlDb->query($sql);
                    }

                    // update quantity real
                    $quantity = (int) $iVal['Quantity'];
                    $sql = "UPDATE `{db_prefix}shc_listing_options` SET `Quantity_real` = `Quantity_real` - {$quantity} ";
                    $sql .= "WHERE `Listing_ID` = {$iVal['Item_ID']} LIMIT 1";
                    $rlDb->query($sql);
                }
            }

            // update order details
            $rlDb->updateOne(array(
                'fields' => array(
                    'Order_ID' => $itemID,
                    'Status' => 'completed',
                ),
                'where' => array(
                    'Order_key' => $orderKey[0],
                    'Dealer_ID' => $orderInfo['Dealer_ID'],
                ),
            ), 'shc_order_details');

            $targets = ['add_listing', 'shopping_cart', 'my_shopping_cart', 'my_purchases'];
            $lang = array_merge($lang, $GLOBALS['rlShoppingCart']->getPhrases($targets));
            $rlSmarty->assign('lang', $lang);

            $rlSmarty->assign('showDigital', true);
            $rlSmarty->assign_by_ref('order_info', $orderInfo);
            $details = $rlSmarty->fetch(RL_PLUGINS . 'shoppingCart/view/order_details_mail.tpl', null, null, false);

            $paymentType = $cash ? 'cash' : 'payment';
            $rlSmarty->assign('paymentType', $paymentType);

            // send payment notification email to buyer
            $mailTpl = $rlMail->getEmailTemplate('shc_order_' . $paymentType . '_accepted');

            $find = array('{name}', '{order_key}', '{details}');
            $replace = array($orderInfo['bFull_name'], $orderInfo['Order_key'], $details);

            $mailTpl['body'] = str_replace($find, $replace, $mailTpl['body']);

            $rlMail->send($mailTpl, $orderInfo['bMail']);

            // send payment notification email to admin
            $mailTpl = $rlMail->getEmailTemplate('shc_order_' . $paymentType . '_accepted_admin');

            $find = array('{name}', '{order_key}', '{details}');
            $replace = array($orderInfo['dFull_name'], $orderInfo['Order_key'], $details);

            $mailTpl['body'] = str_replace($find, $replace, $mailTpl['body']);
            $mailTpl['subject'] = str_replace('{order_key}', $orderInfo['Order_key'], $mailTpl['subject']);

            $rlMail->send($mailTpl, $config['notifications_email']);

            // send payment notification email to dealer
            if ($config['shc_method'] == 'multi') {
                $rlMail->send($mailTpl, $orderInfo['dMail']);
            }
        }
    }

    /**
     * Get order ID
     *
     * @return int
     */
    public function getID()
    {
        return $this->orderID;
    }

    /**
     * set order ID
     *
     * @param int $id
     */
    public function setID($id = 0)
    {
        $this->orderID = $_SESSION['shc_options']['ID'] = (int) $id;
    }

    /**
     * Prepare shipping data to save
     */
    public function prepareShippingOptionsToDb()
    {
        global $rlValid;

        $prepared = [];
        $data = $_POST['f'];
        $shipping = new Shipping();

        $fields = $shipping->getShippingfields();

        foreach ($fields as $fIndex => $fRow) {
            $sFields[$fIndex] = $fields[$fIndex]['Key'];
        }

        foreach ($data as $key => $value) {
            $poss = array_search($key, $sFields);

            switch ($fields[$poss]['Type']) {
                case 'text':
                    if ($fields[$poss]['Multilingual'] && count($GLOBALS['languages']) > 1) {
                        $out = '';
                        foreach ($GLOBALS['languages'] as $language) {
                            $val = $data[$key][$language['Code']];
                            if ($val) {
                                $out .= "{|{$language['Code']}|}" . $val . "{|/{$language['Code']}|}";
                            }
                        }

                        $prepared['fields'][$key] = $out;
                    } else {
                        $prepared['fields'][$key] = $data[$key];
                    }

                    break;

                case 'select':
                case 'bool':
                case 'radio':
                    $prepared['fields'][$key] = $data[$key];

                    break;

                case 'number':
                    $prepared['fields'][$key] = preg_replace('/[^\d|.]/', '', $data[$key]);

                    break;

                case 'phone':
                    $out = '';

                    /* code */
                    if ($fields[$poss]['Opt1']) {
                        $code = $rlValid->xSql(substr($data[$key]['code'], 0, $fields[$poss]['Default']));
                        $out = 'c:' . $code . '|';
                    }

                    /* area */
                    $area = $rlValid->xSql($data[$key]['area']);
                    $out .= 'a:' . $area . '|';

                    /* number */
                    $number = $rlValid->xSql(substr($data[$key]['number'], 0, $fields[$poss]['Values']));
                    $out .= 'n:' . $number;

                    /* extension */
                    if ($fields[$poss]['Opt2']) {
                        $ext = $rlValid->xSql($data[$key]['ext']);
                        $out .= '|e:' . $ext;
                    }

                    $prepared['fields'][$key] = $out;
                    break;

                case 'mixed':
                    $prepared['fields'][$key] = $data[$key]['value'] . '|' . $data[$key]['df'];
                    break;

                case 'unit':
                    $prepared['fields'][$key] = $data[$key]['value'] . '|' . $data[$key]['unit'];
                    break;

                case 'checkbox';

                    unset($data[$key][0], $chValues);
                    foreach ($data[$key] as $chRow) {
                        $chValues .= $chRow . ",";
                    }
                    $chValues = substr($chValues, 0, -1);

                    $prepared['fields'][$key] = $chValues;

                    break;
            }
        }

        return $prepared;
    }

    /**
     * Get my orders purchased/sold
     *
     * @param int $limit
     * @param int $start
     * @param string $type
     * @return array
     */
    public function getMyOrders($limit = 0, $start = 0, $type = 'purchased')
    {
        global $account_info, $rlDb, $lang;

        $fieldAccount = $type == 'purchased' ? 'Buyer_ID' : 'Dealer_ID';

        $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.* ";
        $sql .= "FROM `{db_prefix}shc_orders` AS `T1` ";
        $sql .= "WHERE `T1`.`{$fieldAccount}` = '{$account_info['ID']}' ";

        if ($type == 'purchased') {
            $sql .= "AND `T1`.`Type` = 'shopping' ";
        }

        if ($type == 'sold') {
            $sql .= "AND (`T1`.`Status` = 'paid' OR (`T1`.`Status` = 'pending' AND `T1`.`Cash` = '1')) ";
        }

        $GLOBALS['rlHook']->load('shcMyOrdersSqlWhere', $sql, $type);

        $sql .= "ORDER BY `T1`.`Date` DESC LIMIT {$start}, {$limit}";
        $orders = $rlDb->getAll($sql);

        $calc = $rlDb->getRow("SELECT FOUND_ROWS() AS `calc`");
        $this->totalRows = $calc['calc'];

        if ($orders) {
            foreach ($orders as $key => $order) {
                $sqlwhere = $order['Total'] == 'paid'
                ? "`Order_ID` = {$order['ID']}"
                : "`Order_key` = '" . explode('-', $order['Order_key'])[0] . "'";
                $sql = "SELECT * FROM `{db_prefix}shc_order_details` WHERE {$sqlwhere} ORDER BY `Date` DESC";
                $orders[$key]['items'] = $rlDb->getAll($sql);
                $orders[$key]['Total'] = $GLOBALS['rlShoppingCart']::addCurrency($order['Total']);
                $orders[$key]['Commission'] = $GLOBALS['rlShoppingCart']::addCurrency($order['Commission']);
                $orders[$key]['Escrow_status_name'] = $order['Escrow_status'] == 'pending' || $order['Escrow_status'] == 'canceled'
                    ? $lang[$order['Escrow_status']]
                    : $lang['shc_escrow_' . $order['Escrow_status']];
            }
        }

        return $orders;
    }

    /**
     * Get total rows in query
     *
     * @return int
     */
    public function getTotalRows()
    {
        return (int) $this->totalRows;
    }

    /**
     * Get shipping discount
     *
     * @param float $total
     * @param array $item
     * @return float
     */
    public function getShippingDiscount($total = 0, $item = [])
    {
        if (!$item) {
            return $total;
        }

        if ($item['Quantity'] >= $item['Shipping_discount_at']) {
            $total = $total - ($total * $item['Shipping_discount'] / 100);
        }

        return $total;
    }

    /**
     * Prepare download request
     *
     * @param array $order
     * @param string
     */
    public function prepareDownloadRequest($order)
    {
        $request = base64_encode(md5($order['Buyer_ID']) . '|' . $order['ID']);
        $url = RL_PLUGINS_URL . 'shoppingCart/product_download.php?r=' . $request;
        return $url;
    }

    /**
     * Complete order by cash
     *
     * @param array $order
     * @param int $accountID
     */
    public function completeByCash($orderID, $accountID)
    {
        if (!$orderID || !$accountID) {
            return;
        }

        $this->complete($orderID, $accountID, true);
    }

    /**
     * Change payment status to paid
     *
     * @param int $orderID
     * @param int $accountID
     * @return bool
     */
    public function makePaid($orderID, $accountID)
    {
        if (!$orderID || !$accountID) {
            return false;
        }

        return $GLOBALS['rlDb']->updateOne(array(
            'fields' => array(
                'Status' => 'paid',
                'Pay_date' => 'NOW()',
            ),
            'where' => array('ID' => $orderID, 'Dealer_ID' => $accountID),
        ), 'shc_orders');
    }

    /**
     * Checkout unpaid order from purchases page
     *
     * @param array $order
     */
    public function checkout($orderInfo = [])
    {
        global $rlPayment, $sError, $shc_steps, $reefless, $config, $lang, $errors, $account_info, $rlSmarty;

        if (!$orderInfo) {
            return false;
        }

        $cart = $this->getDetails($orderInfo['Order_key']);
        $rlSmarty->assign_by_ref('cart', $cart);
        $rlSmarty->assign_by_ref('order_info', $orderInfo);

        $success_url = $reefless->getPageUrl('shc_purchases');
        $success_url .= $config['mod_rewrite'] ? '?' : '&';
        $success_url .= 'item=' . $orderInfo['ID'] . '&completed';

        $cancel_url = $reefless->getPageUrl('shc_purchases', array('step' => 'checkout'));
        $cancel_url .= $config['mod_rewrite'] ? '?' : '&';
        $cancel_url .= 'item=' . $orderInfo['ID'] . '&canceled';

        if ($_POST['step'] == 'checkout' && $_POST['gateway'] == 'cash') {
            $options = [];
            if ($config['shc_method'] == 'multi') {
                $options = $GLOBALS['rlShoppingCart']->getAccountOptions((int) $orderInfo['Dealer_ID']);
            }

            if (!$config['shc_allow_cash'] || ($config['shc_method'] == 'multi' && !$options['Allow_cash'])) {
                $errors[] = $lang['shc_cash_unvailable'];
                return;
            }

            $this->completeByCash($orderInfo['ID'], (int) $GLOBALS['account_info']['ID']);
            $extend = [
                'type' => 'param',
                'data' => '?item=' . $orderInfo['ID'] . '&completed',
                'key' => 'item',
                'value' => $orderInfo['ID'] . '&completed'
            ];
            $reefless->redirect(false, $success_url);
            return;
        }

        if (!$rlPayment->isPrepare()) {
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
                $rlPayment->setOption('dealer_id', $orderInfo['Dealer_ID']);
            }

            $rlPayment->init($errors);
        } else {
            $rlPayment->checkout($errors);
        }
    }
}
