<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: AUCTIONPAYMENT.PHP
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
use \ShoppingCart\Shipping;

/**
 * @since 3.0.0
 */
class AuctionPayment extends \Flynax\Abstracts\AbstractSteps
{
    /**
     * Order Info
     *
     * @var array
     */
    public $orderInfo;

    /**
     * Initialize auction payment processing
     */
    public function init(&$page_info = null, &$account_info = null, &$errors = null)
    {
        global $bread_crumbs, $lang, $sError, $reefless, $rlSmarty;

        // Initialize model
        parent::init();

        $itemID = (int) $_GET['item'];

        if (!$itemID || !defined('IS_LOGIN')) {
            $sError = true;
            return;
        }

        $this->orderInfo = $this->getOrder($itemID);

        // check availability of step
        if ($this->orderInfo['Status'] == 'paid' && $this->step != 'done') {
            $sError = true;

            $redirect = $reefless->getPageUrl('shc_auction_payment', array('step' => $this->steps['done']['path']));
            $redirect .= $config['mod_rewrite'] ? '?' : '&';
            $redirect .= 'item=' . $itemID . '&completed';
            $reefless->redirect(null, $redirect);
            exit;
        }

        // set bread crumbs
        if (!empty($this->step)) {
            if ($this->step != 'shipping') {
                $bread_crumbs[] = array(
                    'name' => $lang['shc_step_' . $this->step],
                );
            }

            $rlSmarty->assign('step_name', $lang['shc_step_' . $this->step]);
        }

        $rlSmarty->assign_by_ref('order_info', $this->orderInfo);
        $rlSmarty->assign_by_ref('cart', $this->orderInfo['cart']);

        if (!$this->orderInfo) {
            $rlSmarty->assign('no_access', true);
        }
    }

    /**
     * Validate form fields
     */
    public function validate()
    {
        global $errors, $error_fields;

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
            if ($iValue['Shipping_price_type'] == 'fixed' && $GLOBALS['config']['shc_shipping_price_fixed'] == 'multi') {
                if (empty($_POST['items'][$iValue['ID']]['fixed_price'])) {
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
     * Step shipping
     */
    public function stepShipping()
    {
        global $lang;

        $shipping = new Shipping();
        $shipping->init();

        if (!$_POST['form']) {
            $shipping->simulatePost($this->orderInfo, $this->orderInfo['cart']['items']);
        }

        if (!isset($lang['shc_shipping_price'])) {
            $lang = array_merge($lang, $GLOBALS['rlShoppingCart']->getPhrases(['add_listing', 'shopping_cart']));
        }

        if ($_POST['form']) {
            $this->validate();

            if (!$errors) {
                $orders = new Orders();
                $orders->setID($this->orderInfo['ID']);
                $orders->saveShippingData($_POST['f'], $this->orderInfo['cart']);

                $this->redirectToNextStep(['type' => 'param', 'data' => '?item=' . $this->orderInfo['ID']]);
            }
        }
    }

    /**
     * Step checkout
     */
    public function stepCheckout()
    {
        global $rlPayment, $sError, $shc_steps, $reefless, $config, $lang, $errors;

        if (!$this->orderInfo) {
            $sError = true;
            return;
        }

        if ($this->orderInfo['Status'] == 'paid') {
            $this->redirectToNextStep();
            return;
        }

        if (!isset($lang['shc_shipping_price'])) {
            $lang = array_merge($lang, $GLOBALS['rlShoppingCart']->getPhrases(['add_listing', 'shopping_cart', 'my_shopping_cart']));
        }

        if ($_POST['step'] == 'checkout' && $_POST['gateway'] == 'cash') {
            $options = [];
            if ($config['shc_method'] == 'multi') {
                $options = $GLOBALS['rlShoppingCart']->getAccountOptions((int) $this->orderInfo['Dealer_ID']);
            }

            if (!$config['shc_allow_cash'] || ($config['shc_method'] == 'multi' && !$options['allow_cash'])) {
                $errors[] = $lang['shc_cash_unvailable'];
                return;
            }

            $auction = new Auction();
            $auction->completeByCash($this->orderInfo['ID'], (int) $GLOBALS['account_info']['ID']);
            $extend = [
                'type' => 'param',
                'data' => '?item=' . $this->orderInfo['ID'] . '&completed',
                'key' => 'item',
                'value' => $this->orderInfo['ID'] . '&completed'
            ];
            $this->redirectToNextStep($extend);
            return;
        }

        if (!$rlPayment->isPrepare()) {
            $success_url = $reefless->getPageUrl('shc_auction_payment', array('step' => $shc_steps['done']['path']));
            $success_url .= $config['mod_rewrite'] ? '?' : '&';
            $success_url .= 'item=' . $this->orderInfo['ID'] . '&completed';

            $cancel_url = $reefless->getPageUrl('shc_auction_payment', array('step' => $shc_steps['checkout']['path']));
            $cancel_url .= $config['mod_rewrite'] ? '?' : '&';
            $cancel_url .= 'item=' . $this->orderInfo['ID'] . '&canceled';

            $item = $lang['shc_order_key'] . ' ' . $this->orderInfo['Order_key'];

            $rlPayment->clear();

            // set payment options
            $rlPayment->setOption('service', 'auction');
            $rlPayment->setOption('total', $this->orderInfo['Total']);
            $rlPayment->setOption('item_id', $this->orderInfo['ID']);
            $rlPayment->setOption('item_name', $item);
            $rlPayment->setOption('account_id', (int) $GLOBALS['account_info']['ID']);
            $rlPayment->setOption('callback_class', 'rlShoppingCart');
            $rlPayment->setOption('callback_method', 'completeAuction');
            $rlPayment->setOption('cancel_url', $cancel_url);
            $rlPayment->setOption('success_url', $success_url);
            $rlPayment->setOption('plugin', 'shoppingCart');

            // set dealer ID
            if ($config['shc_method'] == 'multi') {
                $rlPayment->setOption('dealer_id', (int) $this->orderInfo['Dealer_ID']);
            }

            // set commission value
            if ($this->orderInfo['commission']) {
                $rlPayment->setOption('commission', $this->orderInfo['commission']);
            }

            // set dealer ID
            if ($config['shc_method'] == 'multi') {
                $rlPayment->setOption('dealer_id', $this->orderInfo['Dealer_ID']);
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
        global $sError, $rlSmarty, $no_access;

        $itemID = (int) $_GET['item'];

        $orderInfo = $this->getOrder($itemID, true);

        if ($orderInfo) {
            $no_access = true;
            $rlSmarty->assign('shcIsPaid', $orderInfo['Status'] == 'paid' ? true : false);
        } else {
            $sError = true;
        }
    }

    /**
     * Get cart details
     *
     * @see modules/CardProcessing::getCart()
     * @todo - Looks like this getOrder() method do the same as modules/CardProcessing::getCart()
     *         Probably it is possible to unify
     *
     * @param int $itemID
     * @param bool $short
     * @return array
     */
    public function getOrder($itemID = 0, $short = false)
    {
        global $rlDb, $config, $account_info;

        $sql = "SELECT * FROM `{db_prefix}shc_orders` WHERE `ID` = '{$itemID}'";
        $orderInfo = $rlDb->getRow($sql);

        if (!$orderInfo) {
            return [];
        }

        if ($short) {
            return $orderInfo;
        }

        $orderKey = explode("-", $orderInfo['Order_key']);
        $total = 0;
        $commission = 0;
        $shipping_fixed_price = 0;

        $sql = "SELECT `T1`.* FROM `{db_prefix}shc_order_details` AS `T1` ";
        $sql .= "WHERE `T1`.`Order_key` = '{$orderKey[0]}' AND `T1`.`Status` <> 'completed' ";
        $sql .= "ORDER BY `T1`.`Date` DESC";

        $items = $rlDb->getAll($sql);
        $unavailable_count = 0;

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

                $items[$iKey]['shc_available'] = $listing['shc_available'];
                if ($items[$iKey]['Status'] == 'deleted'
                    || $iVal['Quantity'] <= 0
                    || $iVal['Dealer_ID'] == $account_info['ID']
                ) {
                    $items[$iKey]['shc_available'] = false;
                    $unavailable_count++;
                } else {
                    $unavailable_count += $listing['shc_available'] ? 0 : 1;
                }

                $listing_options = $rlDb->fetch(
                    '*',
                    array('Listing_ID' => $iVal['Item_ID']),
                    null,
                    1,
                    'shc_listing_options',
                    'row'
                );

                $items[$iKey]['main_photo'] = $listing['Main_photo'];
                $items[$iKey]['shc_quantity'] = $listing['shc_quantity'];
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
                    $items[$iKey]['Shipping_discount'] = (float) $listing_options['Shipping_discount'];
                    $items[$iKey]['Shipping_discount_at'] = (float) $listing_options['Shipping_discount_at'];
                    if ($config['shc_shipping_price_fixed'] == 'multi') {
                        $items[$iKey]['Shipping_fixed_prices'] = unserialize($listing_options['Shipping_fixed_prices']);
                    }
                    $items[$iKey]['Digital'] = (int) $listing_options['Digital'];
                    $items[$iKey]['Quantity_unlim'] = (int) $listing_options['Quantity_unlim'];
                    $items[$iKey]['Shipping_method_fixed'] = $listing_options['Shipping_method_fixed']
                    ? explode(',', $listing_options['Shipping_method_fixed'])
                    : [];
                }

                // calculate total price
                $items[$iKey]['price_original'] = $GLOBALS['rlShoppingCart']->getPrice($listing['price']);
                $items[$iKey]['total'] = round(($iVal['Quantity'] * $iVal['Price']), 2);
                $total += (float) $items[$iKey]['total'];
                $commission += (float) $listing_options['Commission'];
                $shipping_fixed_price += (float) $items[$iKey]['Shipping_fixed_price'];
            }
        }

        $orderInfo['cart']['items'] = $items;
        $orderInfo['cart']['total'] = round($total, 2);
        $orderInfo['cart']['shipping_price'] = round($shipping_fixed_price, 2);
        $orderInfo['cart']['commission'] = round($commission, 2);
        $orderInfo['cart']['isAvailable'] = (count($items) - $unavailable_count) > 0 ? true : false;
        $orderInfo['cart']['hasUnavailable'] = $unavailable_count > 0 ? true : false;

        return $orderInfo;
    }
}
