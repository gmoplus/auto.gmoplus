<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : AUCTION.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2023
 *	https://www.flynax.com
 *
 ******************************************************************************/

namespace ShoppingCart\Admin;

use \ShoppingCart\PriceFormat;

/**
 * @since 3.0.0
 */
class Auction
{
    /**
     * Get auction details
     */
    public function apAuctionDetails()
    {
        global $rlDb, $rlAccount, $config;

        $item_id = (int) $_GET['item_id'];

        $auction = $GLOBALS['rlListings']->getListing($item_id, true);

        if ($auction) {
            $auction['options'] = $rlDb->fetch('*', array('Listing_ID' => $item_id), null, 1, 'shc_listing_options', 'row');
            $auction['order'] = $rlDb->fetch('*', array('Item_ID' => $item_id), null, 1, 'shc_orders', 'row');
            if ($auction['order']) {
                $auction['buyer'] = $rlAccount->getProfile((int) $auction['order']['Buyer_ID'], true);

                $_POST['f'] = unserialize($auction['order']['Shipping_options']);
                $shipping = new \ShoppingCart\Shipping();
                $shipping->getShippingFields(true);
            }
            $auction['dealer'] = $rlAccount->getProfile((int) $auction['Account_ID'], true);

            $auctionObj = new \ShoppingCart\Auction();
            $left_time = $auctionObj->getTimeLeft($auction);
            $auction['left_time'] = $left_time;

            $phraseKey = $auction['shc_auction_status'] == 'closed' ? 'shc_closed' : $auction['shc_auction_status'];
            $auction['shc_auction_status'] = $GLOBALS['rlLang']->getSystem($phraseKey);
        }

        $GLOBALS['rlSmarty']->assign_by_ref('auction_info', $auction);
    }

    /**
     * Prepare auction search options
     */
    public function apAuctionList()
    {
        global $lang, $rlSmarty;

        // search options
        $auction_status = array(
            'active' => array(
                'key' => 'active',
                'name' => $lang['active'],
            ),
            'closed' => array(
                'key' => 'closed',
                'name' => $lang['shc_closed'],
            ),
        );

        $payment_status = array(
            'paid' => array(
                'key' => 'paid',
                'name' => $lang['paid'],
            ),
            'unpaid' => array(
                'key' => 'unpaid',
                'name' => $lang['unpaid'],
            ),
        );

        $rlSmarty->assign_by_ref('shc_auction_status', $auction_status);
        $rlSmarty->assign_by_ref('shc_payment_status', $payment_status);
    }

    /**
     * Get auctions
     *
     * @param int $start
     * @param int $limit
     * @return array
     */
    public function getOrders($start = 0, $limit = 0)
    {
        global $rlDb, $lang, $rlValid, $config;

        $username = $rlValid->xSql($_GET['username']);
        $shc_auction_status = $rlValid->xSql($_GET['shc_auction_status']);
        $shc_payment_status = $rlValid->xSql($_GET['shc_payment_status']);
        $has_winner = $rlValid->xSql($_GET['has_winner']);

        $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.*, ";
        $sql .= "IF(`T3`.`Last_name` <> '' AND `T3`.`First_name` <> '', ";
        $sql .= "CONCAT(`T3`.`First_name`, ' ', `T3`.`Last_name`), `T3`.`Username`) AS `Full_name`, ";
        $sql .= "`T3`.`Username`, `T5`.`Status` AS `pStatus`, `T4`.`Key` AS `Category_key`, `T4`.`Type` AS `Listing_type`, ";
        $sql .= "`T2`.`Start_price`, `T2`.`Reserved_price`, `T2`.`Bid_step`, `T2`.`Max_bid`, ";
        $sql .= "`T2`.`End_time`, `T2`.`Auction_won`, `T2`.`Commission`, ";
        $sql .= "`T5`.`Escrow`, `T5`.`Escrow_status`, `T5`.`Escrow_date` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}shc_listing_options` AS `T2` ON `T1`.`ID` = `T2`.`Listing_ID` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T3` ON `T1`.`Account_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T4` ON `T1`.`Category_ID` = `T4`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}shc_orders` AS `T5` ON `T1`.`ID` = `T5`.`Item_ID` ";
        $sql .= "WHERE `T1`.`shc_mode` = 'auction' ";

        if ($_GET['search']) {
            if ($username) {
                $sql .= " AND `T3`.`Username` LIKE '%{$username}%' ";
            }

            if ($shc_auction_status) {
                $sql .= " AND `T2`.`Auction_status` LIKE '{$shc_auction_status}' ";
            }

            if ($shc_payment_status && $has_winner == 'yes') {
                $sql .= " AND `T5`.`Status` LIKE '{$shc_payment_status}' ";
            } elseif ($has_winner == 'no') {
                $sql .= " AND `T5`.`Item_ID` <> `T1`.`ID` ";
            }
        }

        $sql .= "GROUP BY `T1`.`ID` ";
        $sql .= "ORDER BY `T1`.`Date` DESC LIMIT {$start}, {$limit}";

        $data = $rlDb->getAll($sql);
        $total_rows = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

        $auction = new \ShoppingCart\Auction();

        foreach ($data as $key => $value) {
            $left_time = $auction->getTimeLeft($value, true);

            if ($value['shc_auction_status'] == 'closed' && !$value['Auction_won']) {
                $data[$key]['pStatus'] = $lang['shc_no_winner'];
            } else {
                $data[$key]['pStatus'] = $value['pStatus'] ? $lang[$value['pStatus']] : $lang['shc_progress'];
            }
            $phraseKey = $value['shc_auction_status'] == 'closed' ? 'shc_closed' : $value['shc_auction_status'];
            $data[$key]['shc_auction_status'] = $lang[$phraseKey];

            $data[$key]['Item'] = $GLOBALS['rlListings']->getListingTitle(
                $data[$key]['Category_ID'],
                $data[$key],
                $value['Listing_type']
            );

            $data[$key]['Full_name'] = empty($data[$key]['Account_ID'])
            ? $lang['administrator']
            : $data[$key]['Full_name'];
            $data[$key]['left_time'] = $left_time['value'] > 0 ? $left_time['text'] : 0;

            $price = explode("|", $value['price']);
            $data[$key]['Price'] = (float) $price[0];

            if ($config['shc_commission_enable'] && $config['shc_commission'] > 0 && $config['shc_method'] == 'multi') {
                $data[$key]['Commission_total'] = PriceFormat::calculateCommission($value['Max_bid'], true);
            }
        }

        return array('data' => $data, 'total' => $total_rows['count']);
    }

    /**
     * Get bids
     *
     * @param int $auction_id
     * @param int $start
     * @param int $limit
     * @return array
     */
    public function getBids($auction_id = 0, $start = 0, $limit = 0)
    {
        global $rlDb, $config;

        if (!$auction_id) {
            return array();
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.*, `T2`.`Username` AS `bUsername`, `T3`.`Username` AS `dUsername`, ";
        $sql .= "IF(`T2`.`Last_name` <> '' OR `T2`.`First_name` <> '', ";
        $sql .= "CONCAT(`T2`.`First_name`, ' ', `T2`.`Last_name`), `T2`.`Username`) AS `bFull_name`, ";
        $sql .= "IF(`T3`.`Last_name` <> '' OR `T3`.`First_name` <> '', ";
        $sql .= "CONCAT(`T3`.`First_name`, ' ', `T3`.`Last_name`), `T3`.`Username`) AS `dFull_name` ";
        $sql .= "FROM `{db_prefix}shc_bids` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Buyer_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T3` ON `T1`.`Dealer_ID` = `T3`.`ID` ";
        $sql .= "WHERE `T1`.`Item_ID` = '{$auction_id}' ";
        $sql .= "GROUP BY `T1`.`ID` ";
        $sql .= "ORDER BY `T1`.`Date` DESC LIMIT {$start}, {$limit}";

        $data = $rlDb->getAll($sql);
        $total_rows = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

        if ($data) {
            foreach ($data as $key => $value) {
                if ($config['shc_commission_enable'] && $config['shc_commission'] > 0 && $config['shc_method'] == 'multi') {
                    $data[$key]['Commission_total'] = PriceFormat::calculateCommission($value['Total'], true);
                }
            }
        }

        return array('data' => $data, 'total' => $total_rows['count']);
    }

    /**
     * Delete auction
     *
     * @param int $id
     */
    public function delete($id = 0)
    {
        global $rlDb;

        if (!$id) {
            return;
        }

        $order = $rlDb->fetch('*', array('ID' => $id), null, 1, 'shc_orders', 'row');

        if (!$order) {
            return false;
        }

        if ($rlDb->delete(array('ID' => $id), 'shc_orders')) {
            $rlDb->delete(array('Order_ID' => $id), 'shc_order_details');
            $rlDb->delete(array('Item_ID' => $order['Item_ID']), 'shc_bids');

            return true;
        }

        return false;
    }

    /**
     * Delete a bid
     *
     * @param int $id
     */
    public function deleteBid($id = 0)
    {
        global $rlDb, $rlAccount, $rlMail;

        if (!$id) {
            return;
        }

        $bidInfo = $rlDb->fetch('*', array('ID' => $id), null, 1, 'shc_bids', 'row');

        if (!$bidInfo) {
            return false;
        }

        if ($rlDb->delete(array('ID' => $id), 'shc_bids')) {
            $GLOBALS['reefless']->loadClass('Listings');
            $GLOBALS['reefless']->loadClass('Account');
            $GLOBALS['reefless']->loadClass('Mail');

            $buyerInfo = $rlAccount->getProfile($bidInfo['Buyer_ID'], true);
            $sellerInfo = $rlAccount->getProfile($bidInfo['Dealer_ID'], true);
            $listing = $GLOBALS['rlListings']->getListing($bidInfo['Item_ID'], true);
            $options = $GLOBALS['rlShoppingCart']->getListingOptions($bidInfo['Item_ID'], $listing);

            // get previous bid
            $sql = "SELECT * FROM `{db_prefix}shc_bids` WHERE `Item_ID` = '{$bidInfo['Item_ID']}' ORDER BY `Total` DESC LIMIT 1";
            $prevBid = $rlDb->getRow($sql);

            $price = $prevBid['Total'] > 0 ? $prevBid['Total'] : $options['Start_price'];
            $current_bid = $price + $options['Bid_step'];

            // decrease total bids
            $sql = "UPDATE `{db_prefix}listings` SET `shc_total_bids` = `shc_total_bids` - 1 ";
            $sql .= "WHERE `ID` = '{$bidInfo['Item_ID']}' LIMIT 1";
            $rlDb->query($sql);

            $sql = "UPDATE `{db_prefix}shc_listing_options` SET `Max_bid` = '" . (float) $prevBid['Total'] . "' ";
            $sql .= "WHERE `ID` = '{$options['ID']}' LIMIT 1";
            $rlDb->query($sql);

            // send notification to bidder
            $auction_cancel_bid = $rlMail->getEmailTemplate('auction_cancel_bid');

            $search = array('{bidder}', '{item}', '{date}', '{link}');
            $replacement = array($buyerInfo['Full_name'], $listing['listing_title'], date('Y-m-d'), $listing['listing_link']);
            $auction_cancel_bid['body'] = str_replace($search, $replacement, $auction_cancel_bid['body']);
            $rlMail->send($auction_cancel_bid, $buyerInfo['Mail']);

            // send notification to dealer
            $auction_cancel_bid_dealer = $rlMail->getEmailTemplate('auction_cancel_bid_dealer');

            $search = array('{dealer}', '{bidder}', '{item}', '{date}', '{link}');
            $replacement = array($sellerInfo['Full_name'], $buyerInfo['Full_name'], $listing['listing_title'], date('Y-m-d'), $listing['listing_link']);
            $auction_cancel_bid_dealer['body'] = str_replace($search, $replacement, $auction_cancel_bid_dealer['body']);
            $rlMail->send($auction_cancel_bid_dealer, $sellerInfo['Mail']);

            // send notification previous bidder
            $prevBidder = $rlAccount->getProfile($prevBid['Buyer_ID']);

            if ($prevBidder) {
                $total = rlShoppingCart::addCurrency($prevBid['Total']);
                $auction_bid_first_place = $rlMail->getEmailTemplate('auction_bid_first_place');

                $search = array('{bidder}', '{item}', '{total}', '{date}', '{link}');
                $replacement = array($prevBidder['Full_name'], $listing['listing_title'], $total, date('Y-m-d'), $listing['listing_link']);
                $auction_bid_first_place['body'] = str_replace($search, $replacement, $auction_bid_first_place['body']);
                $rlMail->send($auction_bid_first_place, $prevBidder['Mail']);
            }
            return true;
        }

        return false;
    }
}
