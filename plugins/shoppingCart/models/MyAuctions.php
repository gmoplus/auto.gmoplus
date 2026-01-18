<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: MYAUCTIONS.PHP
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

use \ShoppingCart\Auction;
use \ShoppingCart\Orders;

/**
 * @since 3.0.0
 */
class MyAuctions
{
    /**
     * Get completed auction info
     *
     * @param int $itemID
     * @param bool $buyer
     * @return array
     */
    public function getAuctionInfo($itemID = 0, $buyer = false)
    {
        global $rlDb, $account_info;

        if (!$itemID) {
            return [];
        }

        $sql = "SELECT `T1`.*, `T2`.`Username` AS `dUsername`, `T2`.`Mail` AS `dMail`, `T2`.`Own_address` AS `dOwn_address`, ";
        $sql .= "IF(`T2`.`Last_name` <> '' AND `T2`.`First_name` <> '', CONCAT(`T2`.`First_name`, ' ', `T2`.`Last_name`), `T2`.`Username`) AS `dFull_name` ";

        if ($buyer) {
            $sql .= ", `T3`.`Username` AS `bUsername`, `T3`.`Mail` AS `bMail`, `T3`.`Own_address` AS `bOwn_address`, ";
            $sql .= "IF(`T3`.`Last_name` <> '' AND `T3`.`First_name` <> '', CONCAT(`T3`.`First_name`, ' ', `T3`.`Last_name`), `T3`.`Username`) AS `bFull_name` ";
        }

        $sql .= "FROM `{db_prefix}shc_orders` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Dealer_ID` = `T2`.`ID` ";

        if ($buyer) {
            $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T3` ON `T1`.`Buyer_ID` = `T3`.`ID` ";
        }
        $sql .= "WHERE `T1`.`ID` = '{$itemID}' ";

        $orderInfo = $rlDb->getRow($sql);

        if ($orderInfo) {
            $orders = new Orders();
            $orders->adaptShippingOptions($orderInfo);
            $orderInfo['Total'] = $GLOBALS['rlShoppingCart']->addCurrency($orderInfo['Total']);
            $orderInfo['item'] = $this->getDetails($orderInfo['Item_ID'], $itemID);

            if ($orderInfo['Status'] == 'paid') {
                $sql = "SELECT * FROM `{db_prefix}transactions` WHERE `Item_ID` = '{$itemID}' ";
                $sql .= "AND `Service` = 'auction' AND `Account_ID` = {$account_info['ID']}";
                $txnInfo = $rlDb->getRow($sql);
                $orderInfo['Gateway'] = $GLOBALS['lang']['payment_gateways+name+' . $txnInfo['Gateway']];
                $orderInfo['Txn_ID'] = $txnInfo['Txn_ID'];
            }
            return $orderInfo;
        }

        return [];
    }

    /**
     * Get live auction info
     *
     * @param int $itemID
     * @return array
     */
    public function getAuctionLiveInfo($itemID = false)
    {
        global $pages, $rlSmarty, $rlListings, $rlListingTypes, $account_info, $config;

        if (!$itemID) {
            return false;
        }

        $listing = $GLOBALS['rlListings']->getListing($itemID, true);

        if ($listing) {
            $listing['seller'] = $GLOBALS['rlAccount']->getProfile((int) $listing['Account_ID'], true);
            $options = $GLOBALS['rlShoppingCart']->getListingOptions($itemID, $listing);

            $auction = new Auction();
            $auction->adaptOptions($listing, $options);
            $price = explode('|', $listing[$config['price_tag_field']]);
            $listing[$config['price_tag_field']] = $GLOBALS['rlShoppingCart']->addCurrency($price[0]);
        }

        return $listing;
    }

    /**
     * Get my bids
     *
     * @param int $itemID
     * @return array
     */
    public function getMyBids($itemID = 0, $pageMode = '')
    {
        global $account_info, $auction_info;

        if (!$itemID) {
            return [];
        }

        $sql = "SELECT * FROM `{db_prefix}shc_bids` WHERE `Item_ID` = '{$itemID}' ";
        if ($pageMode != 'live') {
            $sql .= " AND `Buyer_ID` = '{$account_info['ID']}' ";
        }
        $sql .= "ORDER BY `ID` DESC";

        $bids = $GLOBALS['rlDb']->getAll($sql);

        if ($bids) {
            foreach ($bids as $key => $val) {
                $bids[$key]['Total'] = $GLOBALS['rlShoppingCart']->addCurrency($val['Total']);
                $bids[$key]['Date'] = date(str_replace(array('%', 'b'), array('', 'M'), RL_DATE_FORMAT) . ' H:i:s', strtotime($val['Date']));

                if (!$auction_info['my_total_price'] && $val['Buyer_ID'] == $account_info['ID']) {
                    $auction_info['my_total_price'] = $bids[$key]['Total'];
                }
            }
        }

        return $bids;
    }

    /**
     * Get my auctions
     *
     * @param int $start
     * @param int $limit
     * @return array
     */
    public function getMyAuctions($start = 0, $limit = 0)
    {
        global $account_info, $rlDb;

        $start = $start > 1 ? ($start - 1) * $limit : 0;

        $sql = "SELECT `T1`.* ";
        $sql .= "FROM `{db_prefix}shc_orders` AS `T1` ";
        $sql .= "WHERE `T1`.`Buyer_ID` = '{$account_info['ID']}' AND `T1`.`Type` = 'auction' ";
        $sql .= "ORDER BY `T1`.`Date` DESC ";
        $sql .= "LIMIT {$start},{$limit}";

        $auctions = $rlDb->getAll($sql);

        foreach ($auctions as $key => $value) {
            $auctions[$key]['Total'] = $GLOBALS['rlShoppingCart']->addCurrency($value['Total']);
            $auctions[$key]['item_details'] = $GLOBALS['rlListings']->getListing($value['Item_ID'], true);
        }

        return $auctions;
    }

    /**
     * Get live or not won auctions
     *
     * @param int $start
     * @param int $limit
     * @param bool $module
     * @return array
     */
    public function getNotWonAuctions($start = 0, $limit = 0, $module = false)
    {
        global $reefless, $account_info, $rlListings, $rlListingTypes, $rlDb, $rlShoppingCart;

        if (!$rlListingTypes) {
            $reefless->loadClass('ListingTypes');
        }

        $start = $start > 1 ? ($start - 1) * $limit : 0;

        $sql = "SELECT `T1`.*, `T2`.`Path`, `T2`.`Type` AS `Listing_type`, ";
        $sql .= "`T2`.`Key` AS `Cat_key`, `T2`.`Type` AS `Cat_type`, `T2`.`Path` AS `Cat_path`, ";
        $sql .= "COUNT(`T3`.`ID`) AS `my_total_bids`, MAX(`T3`.`Total`) AS `my_total_price`, ";
        $sql .= "(SELECT MAX(`Total`) FROM `{db_prefix}shc_bids` WHERE `Item_ID` = `T1`.`ID`) AS `total`, ";
        $sql .= "`T7`.`Auction_won`, `T7`.`End_time` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}shc_bids` AS `T3` ON `T1`.`ID` = `T3`.`Item_ID` AND `T3`.`Buyer_ID` = '{$account_info['ID']}' ";
        $sql .= "LEFT JOIN `{db_prefix}shc_orders` AS `T4` ON `T1`.`ID` = `T4`.`Item_ID` AND `T4`.`Type` = 'auction' ";
        $sql .= "LEFT JOIN `{db_prefix}shc_listing_options` AS `T7` ON `T1`.`ID` = `T7`.`Listing_ID` ";
        $sql .= "WHERE ";
        if ($module != 'dontwin') {
            $sql .= " (`T4`.`Item_ID` IS NULL OR `T4`.`Item_ID` = '') ";
        } else {
            $sql .= "`T7`.`Auction_won` <> '{$account_info['ID']}' ";
        }
        if ($module == 'live') {
            $sql .= "AND (`T1`.`shc_auction_status` <> 'closed' AND  TIMESTAMPDIFF(HOUR, `T1`.`shc_start_time`, NOW()) < `T1`.`shc_days` * 24) ";
        } else {
            $sql .= "AND (`T1`.`shc_auction_status` = 'closed' OR TIMESTAMPDIFF(HOUR, `T1`.`shc_start_time`, NOW()) >= `T1`.`shc_days` * 24) ";
        }
        $sql .= "GROUP BY `T1`.`ID` ";
        $sql .= "HAVING COUNT(`T3`.`ID`) > 0 ";
        $sql .= "ORDER BY `T1`.`Date` DESC ";

        $sql .= "LIMIT {$start},{$limit}";

        $auctions = $rlDb->getAll($sql);

        if (empty($auctions)) {
            return false;
        }

        $auction = new Auction();

        foreach ($auctions as $key => $value) {
            $listing_type = $rlListingTypes->types[$value['Listing_type']];
            $auctions[$key]['listing_title'] = $rlListings->getListingTitle($value['Category_ID'], $value, $listing_type['Key']);
            $auctions[$key]['listing_link'] = $reefless->getListingUrl($value);
            $auctions[$key]['my_total_price'] = $rlShoppingCart->addCurrency($value['my_total_price']);
            $auctions[$key]['total'] = $rlShoppingCart->addCurrency($value['total']);

            if ($module == 'live') {
                $auctions[$key]['time_left'] = $auction->getTimeLeft($value);
            }
        }

        return $auctions;
    }

    /**
     * Get details auction
     *
     * @param int $itemID
     * @param int $orderID
     * @return array
     */
    public function getDetails($itemID = 0, $orderID = 0)
    {
        if (!$itemID) {
            return [];
        }

        $listing = $GLOBALS['rlListings']->getListing($itemID, true);

        if ($listing) {
            $options = $GLOBALS['rlShoppingCart']->getListingOptions($itemID, $listing);

            $sql = "SELECT * FROM `{db_prefix}shc_order_details` WHERE `Order_ID` = '{$orderID}'";
            $listing['item'] = $GLOBALS['rlDb']->getRow($sql);

            if ($listing['item']['Shipping_item_options']) {
                $listing['item']['shipping_item_options'] = unserialize($listing['item']['Shipping_item_options']);
            }
        }

        return $listing;
    }

    /**
     * @deprecated 3.1.1
     */
    public function getTotal()
    {}
}
