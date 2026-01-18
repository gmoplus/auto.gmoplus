<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: SHOPPING.PHP
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

use \Flynax\Utils\Valid;
use \ShoppingCart\Currency;

/**
 * @since 3.0.0
 */
class Shopping
{
    /**
     * Add item to cart
     *
     * @param  int $item_id
     * @return bool
     */
    public function addItem($item_id = 0, $listing_info = array())
    {
        global $rlDb, $account_info;

        if (!$item_id) {
            return false;
        }

        $shc_txn_id = $this->getRelevanceKey($listing_info);

        $sql = "SELECT `ID`,`Quantity` FROM `{db_prefix}shc_order_details` ";
        $sql .= "WHERE `Order_key` = '{$shc_txn_id}' AND `Item_ID` = '{$item_id}' AND `Status` = 'active'";
        $item_info = $rlDb->getRow($sql);

        $currency = new Currency();

        $listing_options = $rlDb->fetch(
            '*',
            array('Listing_ID' => $listing_info['ID']),
            null,
            1,
            'shc_listing_options',
            'row'
        );

        if (empty($item_info['ID'])) {
            $insert = array(
                'Order_key' => $shc_txn_id,
                'Item_ID' => $item_id,
                'Item' => $listing_info['listing_title'],
                'Price' => (float) $currency->convertPrice($listing_info[$GLOBALS['config']['price_tag_field']]),
                'Date' => 'NOW()',
                'Dealer_ID' => $listing_info['Account_ID'],
                'Buyer_ID' => $account_info['ID'] ? $account_info['ID'] : 0,
                'Quantity' => 1,
                'Digital' => (int) $listing_options['Digital'],
            );

            if ($listing_info['Main_photo']) {
                $image = RL_FILES_URL . $listing_info['Main_photo'];
                $blob = file_get_contents($image);
                $insert['Image'] = base64_encode($blob);
            }

            $result = $rlDb->insertOne($insert, 'shc_order_details');
        } else {
            $quantity = !$listing_options['Digital'] && !$listing_options['Quantity_unlim'] ? $item_info['Quantity'] + 1 : 1;
            $update = array(
                'fields' => array('Quantity' => $quantity),
                'where' => array('ID' => $item_info['ID']),
            );

            if (defined('IS_LOGIN') && empty($item_info['Buyer_ID'])) {
                $update['fields']['Buyer_ID'] = $account_info['ID'];
            }

            $result = $rlDb->updateOne($update, 'shc_order_details');
        }

        if ($result) {
            if ($GLOBALS['config']['shc_items_cart_duration'] != 'unlimited'
                && !$listing_options['Digital']
                && !$listing_options['Quantity_unlim']
            ) {
                $sql = "UPDATE `{db_prefix}listings` SET `shc_quantity` = `shc_quantity` - 1 WHERE `ID` = {$item_id}";
                $rlDb->query($sql);
            }

            return true;
        }

        return false;
    }

    /**
     * Delete item from cart
     *
     * @param  int $id
     * @param  int $item_id
     * @return bool
     */
    public function deleteItem($id = 0, $item_id = 0, $item = array())
    {
        global $rlDb;

        $id = (int) $id;
        $item_id = (int) $item_id;

        if (!$id || !$item_id) {
            return false;
        }

        // get item details
        if (!$item) {
            $sql = "SELECT * FROM `{db_prefix}shc_order_details` WHERE `ID` = {$id}";
            $item = $rlDb->getRow($sql);
        }

        if ($item) {
            $result = $rlDb->delete(array('ID' => $id, 'Item_ID' => $item_id), 'shc_order_details');

            if ($result && $GLOBALS['config']['shc_items_cart_duration'] != 'unlimited') {
                // update quantity
                $quantity = (int) $item['Quantity'];
                $sql = "UPDATE `{db_prefix}listings` SET `shc_quantity` = `shc_quantity` + " . $quantity . ", ";
                $sql .= "`shc_available` = '1' WHERE `ID` = {$item['Item_ID']}";
                $rlDb->query($sql);
            }

            // check if order exists and not have anymore items
            $sql = "SELECT * FROM `{db_prefix}shc_order_details` WHERE `Order_key` = '{$this->getOrderKey()}'";
            $items = $rlDb->getAll($sql);

            if (count($items) <= 0) {
                $rlDb->delete(array('Order_key' => $this->getOrderKey() . '-D' . $item['Dealer_ID']), 'shc_orders', null, 1);
            }

            return $result;
        }

        return false;
    }

    /**
     * Delete one item from cart
     *
     * @since  3.1.3
     * @param  int   $id
     * @param  int   $item_id
     * @param  array $item
     * @return bool
     */
    public function deleteOneItem($id = 0, $item_id = 0, $item = array())
    {
        global $rlDb;

        $id = (int) $id;
        $item_id = (int) $item_id;

        if (!$id || !$item_id) {
            return false;
        }

        // get item details
        if (!$item) {
            $sql = "SELECT * FROM `{db_prefix}shc_order_details` WHERE `ID` = {$id}";
            $item = $rlDb->getRow($sql);
        }

        if ($item) {
            if ($item['Quantity'] > 1) {
                $update = array(
                    'fields' => array('Quantity' => (int) $item['Quantity'] - 1),
                    'where' => array('ID' => $id, 'Item_ID' => $item_id),
                );
                $result = $rlDb->updateOne($update, 'shc_order_details');

                if ($result && $GLOBALS['config']['shc_items_cart_duration'] != 'unlimited') {
                    // update quantity
                    $quantity = (int) $item['Quantity']+1;
                    $sql = "UPDATE `{db_prefix}listings` SET `shc_quantity` = `shc_quantity` + 1, ";
                    $sql .= "`shc_available` = '1' WHERE `ID` = {$item['Item_ID']}";
                    $rlDb->query($sql);
                }
            }
            else {
                $result = $rlDb->delete(array('ID' => $id, 'Item_ID' => $item_id), 'shc_order_details');
                if ($result && $GLOBALS['config']['shc_items_cart_duration'] != 'unlimited') {
                    // update quantity
                    $quantity = (int) $item['Quantity'];
                    $sql = "UPDATE `{db_prefix}listings` SET `shc_quantity` = `shc_quantity` + " . $quantity . ", ";
                    $sql .= "`shc_available` = '1' WHERE `ID` = {$item['Item_ID']}";
                    $rlDb->query($sql);
                }
            }

            // check if order exists and not have anymore items
            $sql = "SELECT * FROM `{db_prefix}shc_order_details` WHERE `Order_key` = '{$this->getOrderKey()}'";
            $items = $rlDb->getAll($sql);

            if (count($items) <= 0) {
                $rlDb->delete(array('Order_key' => $this->getOrderKey() . '-D' . $item['Dealer_ID']), 'shc_orders', null, 1);
            }

            return $result;
        }

        return false;
    }

    /**
     * Clear cart
     */
    public function clearCart()
    {
        global $rlDb;

        $sql = "SELECT * FROM `{db_prefix}shc_order_details` WHERE `Order_key` = '{$this->getOrderKey()}'";
        $items = $rlDb->getAll($sql);

        $result = $rlDb->delete(array('Order_key' => $this->getOrderKey()), 'shc_order_details', null, 0);

        if ($result && $GLOBALS['config']['shc_items_cart_duration'] != 'unlimited') {
            $dealers = [];

            // return quantity
            foreach ($items as $iKey => $iVal) {
                $sql = "UPDATE `{db_prefix}listings` SET `shc_quantity` = `shc_quantity` + {$iVal['Quantity']}, ";
                $sql .= "`shc_available` = '1' WHERE `ID` = '{$iVal['Item_ID']}' LIMIT 1";
                $rlDb->query($sql);

                if (!in_array($iVal['Dealer_ID'], $dealers)) {
                    $dealers[] = $iVal['Dealer_ID'];
                }
            }

            if (count($dealers) > 0) {
                foreach ($dealers as $dealer) {
                    $rlDb->delete(array('Order_key' => $this->getOrderKey() . '-D' . $dealer), 'shc_orders', null, 1);
                }
            }
        }

        $this->clearCookie(true);
    }

    /**
     * Get order items
     *
     * @param  int $limit
     * @param  string $order_key
     * @return array
     */
    public function getItems($limit = 0, $order_key = '')
    {
        global $account_info, $rlDb;

        if (!$order_key) {
            $order_key = $this->getOrderKey();
        }

        $sql = "SELECT `T1`.* FROM `{db_prefix}shc_order_details` AS `T1` ";
        $sql .= "WHERE `T1`.`Order_key` = '{$order_key}' AND `T1`.`Status` <> 'completed' ORDER BY `T1`.`Date` DESC";
        if ($limit) {
            $sql .= " LIMIT " . $limit;
        }

        $items = $GLOBALS['rlDb']->getAll($sql);

        if ($items) {
            foreach ($items as $iKey => $iVal) {
                $listing = $GLOBALS['rlListings']->getListing($iVal['Item_ID'], true);

                if ($listing) {
                    $listing_options = $rlDb->fetch(
                        '*',
                        array('Listing_ID' => $iVal['Item_ID']),
                        null,
                        1,
                        'shc_listing_options',
                        'row'
                    );

                    $items[$iKey]['shc_available'] = $listing['shc_available'];
                    if ($iVal['Status'] == 'deleted'
                        || ($listing_options['Quantity_real'] <= 0 && !$iVal['Digital'])
                        || $iVal['Dealer_ID'] == $account_info['ID']
                    ) {
                        $items[$iKey]['shc_available'] = false;
                    }

                    $items[$iKey]['Main_photo'] = $listing['Main_photo'];
                    $items[$iKey]['photo_tmp'] = $iVal['Image'];
                    $items[$iKey]['shc_quantity'] = $listing['shc_quantity'];
                    $items[$iKey]['weight'] = $listing['shc_weight'];
                    $items[$iKey]['listing_link'] = $listing['listing_link'];
                } else {
                    $items[$iKey]['Status'] = 'deleted';
                }
            }

            return $items;
        }

        return array();
    }

    /**
     * Get item info
     *
     * @since 3.1.3
     * @param  int $listing_id
     * @param  string $order_key
     * @return array
     */
    public function getItem($listing_id = 0, $order_key = '')
    {
        global $account_info, $rlDb;

        if (!$listing_id) {
            return [];
        }
        if (!$order_key) {
            $order_key = $this->getOrderKey();
        }

        $sql = "SELECT * FROM `{db_prefix}shc_order_details` ";
        $sql .= "WHERE `Item_ID` = '{$listing_id}' AND `Order_key` = '{$order_key}' AND `Status` <> 'completed' ORDER BY `Date` DESC";

        $item = $GLOBALS['rlDb']->getRow($sql);

        if ($item) {
            $listing = $GLOBALS['rlListings']->getListing($item['Item_ID'], true);

            if ($listing) {
                $listing_options = $rlDb->fetch(
                    '*',
                    array('Listing_ID' => $listing_id),
                    null,
                    1,
                    'shc_listing_options',
                    'row'
                );

                $item['shc_available'] = true;
                if ($item['Status'] == 'deleted'
                    || ($listing_options['Quantity_real'] <= 0 && !$item['Digital'])
                    || $item['Dealer_ID'] == $account_info['ID']
                ) {
                    $item['shc_available'] = false;
                }

                $item['Main_photo'] = $listing['Main_photo'];
                $item['photo_tmp'] = $item['Image'];
                $item['shc_quantity'] = $listing['shc_quantity'];
                $item['weight'] = $listing['shc_weight'];
                $item['listing_link'] = $listing['listing_link'];
            } else {
                $item['Status'] = 'deleted';
            }

            return $item;
        }

        return [];
    }

    /**
     * Build cart items in box
     *
     * @return string
     */
    public function buildCartContent()
    {
        global $rlSmarty, $config;

        $items = self::getItems($config['shc_count_items_block']);

        $this->updateCookie($items);

        $rlSmarty->assign_by_ref('shcItems', $items);
        $rlSmarty->assign('rlTplBase', RL_URL_HOME . 'templates/' . $config['template'] . '/');
        $content = $rlSmarty->fetch(RL_PLUGINS . 'shoppingCart/view/cart_items.tpl');

        return $content;
    }

    /**
     * Get total details in cart
     *
     * @return array
     */
    public function getTotalInfo()
    {
        $sql = "SELECT COUNT(`ID`) AS `count`, SUM(`Price` * `Quantity`) AS `total` ";
        $sql .= "FROM `{db_prefix}shc_order_details` ";
        $sql .= "WHERE `Order_key` = '{$this->getOrderKey()}' AND `Status` = 'active' ";
        $total_info = $GLOBALS['rlDb']->getRow($sql);

        return $total_info;
    }

    /**
     * Update temporary items
     *
     * @param array $items
     */
    public function updateCookie($items)
    {
        if (!is_array($items)) {
            return;
        }

        if (count($items) <= 0) {
            $_SESSION['shc_items'] = '';
            $_SESSION['shc_items_info'] = ['count' => 0, 'total' => 0];
            return;
        }

        $_SESSION['shc_items'] = serialize($items);
        $_SESSION['shc_items_info'] = $this->getTotalInfo();
    }

    /**
     * Clear cookie
     *
     * @param bool $update
     * @param bool $uninstall
     */
    public function clearCookie($update = false, $uninstall = false)
    {
        global $config, $reefless;

        $items = $uninstall ? false : $this->getItems();

        if ($items) {
            $_SESSION['shc_items'] = $items;
        }

        if ($config['shc_method'] == 'single' || ($config['shc_method'] == 'multi' && count($items) <= 0)) {
            $domain = $GLOBALS['domain_info'];
            if ($update) {
                $txn_id = $reefless->generateHash(8, 'upper');
                $reefless->createCookie('shc_txn_id', $txn_id, time() + 61516800);
            } else {
                $reefless->createCookie('shc_txn_id', '', time() - 3600);
            }
        }

        unset($_SESSION['order_info'], $_SESSION['shc_dealer'], $_SESSION['shc_items'], $_SESSION['shc_items_info']);
    }

    /**
     * Get order key
     *
     * @return string
     */
    public function getOrderKey()
    {
        return Valid::escape($_COOKIE['shc_txn_id']);
    }

    /**
     * Generate new order key
     *
     * @param $txnID string
     * @param $needNew bool
     */
    public function setOrderKey($txnID = '', $needNew = false)
    {
        global $reefless;

        if (!empty($txnID)) {
            $reefless->createCookie('shc_txn_id', $txnID, time() + 61516800);
            return;
        }

        if (!isset($_COOKIE['shc_txn_id']) || $needNew) {
            $reefless->createCookie('shc_txn_id', $reefless->generateHash(8, 'upper'), time() + 61516800);
        }
    }

    /**
     * Check unavailable listings
     */
    public function checkUnavailableListings()
    {
        global $sql, $config;

        if (!$config['shc_show_unavailable_listings']) {
            $sql .= "AND ((`T1`.`shc_mode` = 'fixed' AND `T1`.`shc_quantity` > 0 AND `T1`.`shc_available` = '1') OR `T1`.`shc_mode` <> 'fixed') ";
        }

        if ($config['shc_module_auction']) {
            $sql .= "AND `T1`.`shc_auction_status` <> 'closed' ";
        }
    }

    /**
     * Refresh cart items by interval
     */
    public function refreshCartItems()
    {
        global $rlDb, $config;

        if ($config['shc_items_cart_duration'] == 'unlimited') {
            return;
        }

        $interval = (int) $config['shc_interval_refresh_cart'];
        $sql = "SELECT * FROM `{db_prefix}shc_order_details` ";
        $sql .= "WHERE UNIX_TIMESTAMP(NOW()) > UNIX_TIMESTAMP(DATE_ADD(`Date`, INTERVAL {$interval} HOUR)) ";
        $sql .= "AND `Status` = 'active'";
        $items = $rlDb->getAll($sql);

        if ($items) {
            foreach ($items as $item) {
                $rlDb->delete(array('ID' => $item['ID']), 'shc_order_details');

                $sql = "UPDATE `{db_prefix}listings` ";
                $sql .= "SET `shc_quantity` = `shc_quantity` + {$item['Quantity']}, `shc_available` = '1' ";
                $sql .= "WHERE `ID` = '{$item['Item_ID']}' LIMIT 1";
                $rlDb->query($sql);
            }
        }
    }

    public function synchronizeItems($check = false)
    {
        global $rlAccount, $rlDb, $account_info, $config;

        $orderKey = $this->getOrderKey();

        if ($rlAccount->isLogin() && (!$_SESSION['shcControlLogged'] || $check)) {
            $_SESSION['shcControlUnLogged'] = false;
            $sql = "SELECT COUNT(`ID`) AS `total` FROM `{db_prefix}shc_order_details` ";
            $sql .= "WHERE `Order_key` = '{$orderKey}' AND `Status` = 'active' ";
            $itemsByKey = $rlDb->getRow($sql);

            // get cart items by account
            $sql = "SELECT * FROM `{db_prefix}shc_order_details` ";
            $sql .= "WHERE `Buyer_ID` = {$_SESSION['account']['ID']} ";
            $sql .= "AND `Order_key` <> '{$orderKey}' AND `Status` = 'active' ORDER BY `Date` DESC ";
            $itemsByAccount = $rlDb->getAll($sql);

            if ($itemsByAccount) {
                $currentOrderKey = $itemsByAccount[0]['Order_key'];
                if ($itemsByKey['total'] > 0) {
                    $update = [
                        'fields' => [
                            'Order_key' => $currentOrderKey,
                            'Buyer_ID' => $_SESSION['account']['ID']
                        ],
                        'where' => [
                            'Order_key' => $orderKey,
                            'Buyer_ID' => 0,
                            'Status' => 'active'
                        ],
                    ];
                } else {
                    $update = [
                        'fields' => [
                            'Order_key' => $currentOrderKey
                        ],
                        'where' => [
                            'Buyer_ID' => $_SESSION['account']['ID'],
                            'Status' => 'active'
                        ],
                    ];
                }

                $rlDb->update($update, 'shc_order_details');
                $this->setOrderKey($currentOrderKey);
                $this->updateCookie($this->getItems($config['shc_count_items_block'], $currentOrderKey));
            }

            $_SESSION['shcControlLogged'] = true;
        }

        if (!$rlAccount->isLogin() && (!$_SESSION['shcControlUnLogged'] || $check)) {
            $_SESSION['shcControlLogged'] = false;
            $sql = "SELECT * FROM `{db_prefix}shc_order_details` ";
            $sql .= "WHERE `Order_key` = '{$orderKey}' AND `Buyer_ID` > 0 AND `Status` = 'active' ";
            $itemsByKey = $rlDb->getAll($sql);

            if ($itemsByKey) {
                $this->setOrderKey(null, true);
                $items = [];
                $this->updateCookie($items);
            }
            $_SESSION['shcControlUnLogged'] = true;
        }
    }

    /**
     * Get relevance order key
     *
     * @since 3.1.0
     * @param array $itemInfo
     * @return string
     */
    public function getRelevanceKey(array $itemInfo) : string
    {
        global $rlDb;

        $orderKey = $this->getOrderKey() . '-D' . $itemInfo['Account_ID'];
        $orderID = $rlDb->getOne('ID',  "`Order_key` = '{$orderKey}' AND `Status` = 'paid'", 'shc_orders');

        if (!empty($orderID)) {
            $this->setOrderKey(null, true);
            return $this->getOrderKey();
        }

        return $this->getOrderKey();
    }
}
