<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: SHOPPINGCARTCONTROLLER.PHP
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

namespace Flynax\Api\Http\Controllers\V1;

use \ShoppingCart\CartProcessing;
use \ShoppingCart\Shopping;
use \ShoppingCart\Shipping;
use \ShoppingCart\Auction;
use \ShoppingCart\Orders;

/**
 * ShoppingCart Controller
 *
 * @since 1.0.2
 */
class ShoppingCartController extends BaseController
{
    public function __construct()
    {
        rl('ShoppingCart', null, 'shoppingCart');
    }

    /**
     * Updated add/edit price field
     */
    public function buildAddEditShoppingFields(&$fields, $listing_type)
    {
        global $account_info, $lang, $config;

        $account_types = explode(",", $GLOBALS['config']['shc_account_types']);

        if ((!$listing_type['shc_module'] && !$listing_type['shc_auction'])
            || ($GLOBALS['config']['membership_module'] && !$account_info['plan']['shc_module'])
            || !in_array($account_info['Type'], $account_types)
        ) {
            return;
        }

        if (!isset($lang['shc_handling_time_0']) ) {
            $lang = array_merge($lang, rl('ShoppingCart', null, 'shoppingCart')->getPhrases(['listing_details', 'add_listing','shopping_cart']));
        }
        require_once RL_PLUGINS . 'shoppingCart/static.inc.php';

        $priceKey = $GLOBALS['config']['price_tag_field']; 
        if ($fields[$priceKey]) {
            $fields[$priceKey]['shopping'] = 1;
        }

        $shoppingForm = [];

        $tabs_source = array(
            'auction' => array(
                'module' => 'auction',
                'name' => rl('Lang')->getPhrase('shc_auction', null, null, true),
                'status' => $config['shc_module_auction'] && $listing_type['shc_auction'] ? true : false,
            ),
            'fixed' => array(
                'module' => 'fixed',
                'name' => rl('Lang')->getPhrase('shc_mode_fixed', null, null, true),
                'status' => $config['shc_module'] && $listing_type['shc_module'] ? true : false,
            ),
            'listing' => array(
                'module' => 'listing',
                'name' => rl('Lang')->getPhrase('shc_mode_listing', null, null, true),
                'status' => $config['shc_module_listing'] ? true : false,
            ),
        );

        $tabs = array();
        $sorted = explode(',', $config['shc_price_format_tabs']);

        if ($sorted) {
            foreach ($sorted as $key => $val) {
                if (!$tabs_source[$val]['status']) {
                    continue;
                }

                $tabs[$val] = $tabs_source[$val];
            }
        }
        $shoppingForm['tabs'] = $tabs;
        $shoppingForm['handling_time'] = $handling_time;
        $shoppingForm['shc_package_type'] = $package_types;
        $shipping = new Shipping();
        $shoppingForm['shipping_methods'] = $shipping->getMethods();

        return $shoppingForm;
    }

    /**
     * Shopping action
     */
    public function shoppingAction()
    {
        $response = [];
        (new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password']);

        $this->getOrderKey(true);

        switch ($_REQUEST['mode']) {
            case 'shoppingCartAddItem':
                rl('ShoppingCart')->hookAjaxRequest($response, $_REQUEST['mode'], $_REQUEST['item_id'], RL_LANG_CODE);
            break;
            case 'shoppingCartDeleteItem':
            case 'shoppingCartDeleteOneItem':
                rl('ShoppingCart')->hookAjaxRequest($response, $_REQUEST['mode'], $_REQUEST['id'], RL_LANG_CODE);
            break;
        }
        if ($_REQUEST['cartMode']) {
            $response = $this->getShoppingCartItems();
        }

        return $response;
    }

    /**
     * Get order Key
     * 
     * bool $needNewKey - create a new key if need
     */
    public function getOrderKey($needNewKey = false) 
    {
        $accountID = $_REQUEST['account_id'];
        $orderKey = rl('Db')->getOne('Order_key',  "`Buyer_ID` = '{$accountID}' AND `Status` = 'active'", 'shc_order_details');

        if (!$orderKey && $needNewKey) {
            $orderKey = rl('reefless')->generateHash(8, 'upper');
        }

        if ($orderKey) {
            $_COOKIE['shc_txn_id'] = $orderKey;
        }
        return $orderKey;
    }

    /**
     * Get items
     */
    public function getItems(&$out)
    {
        if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
            $orderKey = $this->getOrderKey(false);
            
            if ($orderKey) {
                $shopping = new Shopping();
                $items = $shopping->getItems(0, $orderKey);
                if ($items) {
                    (new ListingsController())->buildPhotosUrl($items);
                    $tmp = [];
                    foreach($items as $item) {
                        $tmp[$item['Item_ID']] = $item;
                    }
                    $out['shopping'] = $tmp;
                }
                unset($items, $tmp);
            }
        }
    }

    /**
     * Get Shopping Items
     *
     * return @responce
     **/
    public function getShoppingCartItems()
    {
        $response = [];
        $this->getOrderKey(true);
        $cartProcessing = new CartProcessing();
        $cart = $cartProcessing->getCart();

        if (array_key_exists('main_photo', $cart['items'][0])) {
            foreach($cart['items'] as &$item) {
                $item['Main_photo'] = $item['main_photo'] ? $item['main_photo'] : '';
            }
        }
        (new ListingsController())->buildPhotosUrl($cart['items']);

        $shipping = new Shipping();
        $fields = $shipping->getShippingFields();

        $shippingKeys = [
            'location',
            'location_level1',
            'location_level2',
            'location_level3',
            'zip',
        ];
        
        $shFields = [];
        foreach($fields as $field) {
            if (in_array($field['Key'], $shippingKeys) || $field['Map']) {
                continue;
            }
            if ($field['Key'] == 'address') {
                $field['Add_page'] = 0;
            }
            $shFields[$field['Key']] = $field;
        }
        unset($fields);

        $response['cart'] = $cart;
        $response['fields'] = $shFields;

        return $response;
    }

    /**
     * Update data before payment
     *
     * return @responce
     **/
    public function prepareCartBeforePayment()
    {
        $response = [];
        if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
            
            $itemData = $_POST['f'] = json_decode($_POST['form'], true);
            $_POST['items'] = json_decode($_POST['items'], true);
            
            $orderKey = $this->getOrderKey(true);

            $cartProcessing = new CartProcessing();
            $orders = new Orders();
            $cart = $cartProcessing->getCart();

            $cartProcessing->setDealer($cart['items'][0]['Dealer_ID']); 
            if ($cartProcessing->getDealer()) {
                $orderKey .= '-D' . $cartProcessing->getDealer();
            }

            $orderInfo = $orders->get($orderKey);
            if ($orderInfo) {
                $update = array(
                    'fields' => array(
                        'Total' => (float) $cart['total'],
                    ),
                    'where' => array('ID' => $orderInfo['ID']),
                );

                $orders->update($update);
                $orders->setID($orderInfo['ID']);
            } else {
                $order = array(
                    'Type' => 'shopping',
                    'Order_key' => $orderKey,
                    'Total' => (float) $cart['total'],
                    'Dealer_ID' => $cartProcessing->getDealer(),
                    'Buyer_ID' => (int) $GLOBALS['account_info']['ID'],
                    'Date' => 'NOW()',
                );
                $orders->create($order);
                $orderInfo = $orders->get($orderKey);
            }
            $orders->saveShippingData($itemData, $cart);

            $orderInfo['name'] = rl('Lang')->getPhrase('shc_order_key', null, null, true) .' ' . $orderInfo['Order_key'] . ' (#' . $orderInfo['ID'] . ')';
            $response = $orderInfo;
        }

        return $response;
    }

    /**
     * Get my purchases
     *
     * return @responce
     **/
    public function getMySoldPurchaseItems()
    {
        global $shippingStatuses;
        $response = [];
        if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
            $orders = new Orders();

            $mode = $_REQUEST['mode'];
            $limit = $GLOBALS['config']['shc_orders_per_page'];
            $start = $_REQUEST['start'] ? $_REQUEST['start'] : 0;
            $start = $start * $limit;

            $response['items'] = $orders->getMyOrders($limit, $start, $mode);
            $response['total'] = $orders->getTotalRows();

            if ($mode == 'sold') {
                require_once RL_PLUGINS . 'shoppingCart/static.inc.php';
                foreach($shippingStatuses as &$shipping) {
                    if (!$shipping['name']) {
                        $shipping['name'] = rl('Lang')->getPhrase('shc_'.$shipping['Key'], null, null, true);
                    }
                }
                $response['shipping_statuses'] = $shippingStatuses;
            }

        }
        return $response;
    }

    /**
     * Get order
     *
     * return @responce
     **/
    public function getOrder()
    {
        $response = [];
        if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
            $id = (int) $_REQUEST['id'];
            $details = $_REQUEST['details'] ? true : false;
            $full = $_REQUEST['full'] ? true : false;
            $accountFields = $_REQUEST['account_field'] ? $_REQUEST['account_field'] : '' ;
            $orders = new Orders();

            $orderInfo = $orders->get($id, $details, $full, $accountFields);

            if ($orderInfo['cart']['items']) {
                $items = $orderInfo['cart']['items'];
                foreach($items as &$item) {
                    $item['Main_photo'] = $item['main_photo'] ? $item['main_photo'] : '';
                }
                (new ListingsController())->buildPhotosUrl($items);
                $orderInfo['cart']['items'] = $items;
            }
            $response['order'] = $orderInfo;
        }
        return $response;
    }

    /**
     * Get Auction Data
     *
     * return @responce
     **/
    public function shoppingCartChangeShippingStatus()
    {
        $response = [];
        if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
            $item_id = (int) $_REQUEST['item_id'];
            $status = $GLOBALS['rlValid']->xSql($_REQUEST['status']);

            $shipping = new Shipping();
            rl('Mail');
            $result = $shipping->changeStatus($item_id, $status);
            $msgKey = $result ? 'shc_notice_shipping_status_changed' : 'shc_shipping_status_failed';
            $response['message'] = rl('Lang')->getPhrase($msgKey, null, null, true);
        }
        return $response;
    }

    /**
     * Get Auction Data
     *
     * return @responce
     **/
    public function getAuction(&$listing)
    {
        $options = rl('ShoppingCart')->getListingOptions($listing['ID'], $listing);

        $auction = new Auction();
        $auction->adaptOptions($listing, $options);

        // get bids
        $listing['bids'] = $auction->getBids($listing['ID']);
    }
    
}
