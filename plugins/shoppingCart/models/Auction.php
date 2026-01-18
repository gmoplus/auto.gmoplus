<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: AUCTION.PHP
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

use \ShoppingCart\PriceFormat;

/**
 * @since 3.0.0
 */
class Auction
{
    /**
     * Adaptation auction options for listing details page
     *
     * @param array $listing_data
     * @param array $options
     */
    public function adaptOptions(&$listing, $options = array())
    {
        global $tabs, $rlSmarty, $lang, $config, $page_info;

        if (!$listing) {
            return;
        }

        if ($page_info['Controller'] == 'listing_type') {
            $tabs['shoppingCart'] = array(
                'key' => 'shoppingCart',
                'name' => $lang['shc_bid_history'],
            );
        }

        $bids = $this->getBids($listing['ID']);
        $rlSmarty->assign_by_ref('bids', $bids);

        $r_link = $GLOBALS['reefless']->getPageUrl('registration');

        $shc_add_bid_not_login = preg_replace(
            '/(\[(\pL.*)\])(.*)?(\[(\pL.*)\])/u',
            "<a id=\"shc_add_bid\" href=\"javascript:void(0);\">$2</a>$3<a href=\"{$r_link}\" target=\"_blank\">$5</a>",
            $lang['shc_add_bid_not_login']
        );

        $rlSmarty->assign('shc_add_bid_not_login', $shc_add_bid_not_login);

        $time_left = $this->getTimeLeft($listing, true);
        $current_bid = $bids[0];
        $price_data = explode('|', $listing[$config['price_tag_field']]);
        $listing['shc'] = array(
            'total_bids' => count($bids),
            'current_bid' => $current_bid['Total'] ? (float) $current_bid['Total'] : (float) $options['Start_price'],
            'currency' => $lang['data_formats+name+' . $price_data[1]],
            'time_left' => $time_left['text'],
            'time_left_value' => $time_left['value'],
            'current_time' => $time_left['current_time'],
            'min_rate_bid' => $current_bid['Total']
            ? round($current_bid['Total'] + $options['Bid_step'], 2)
            : round($options['Start_price'] + $options['Bid_step'], 2),
            'bidders' => $this->getUniqueBidders($bids),
            'Auction_won' => (int) $options['Auction_won'],
            'buy_now_allowed' => $current_bid['Total'] <= $price_data[0] ? true : false,
            'Status' => $listing['Status'],
        );

        $listing['shc']['shc_min_bid'] = str_replace('{total}', $listing['shc']['min_rate_bid'], $lang['shc_min_bid']);

        // get winner info
        if ($options['Auction_won']) {
            $winner_info = $GLOBALS['rlAccount']->getProfile((int) $options['Auction_won']);
            $listing['shc']['winner'] = $winner_info;
            $rlSmarty->assign_by_ref('winner_info', $winner_info);
        }
    }

    /**
     * Get time left
     *
     * @param array $listing
     * @param bool $output
     * @return string
     */
    public function getTimeLeft($listing, $output = false)
    {
        if (!$listing) {
            return false;
        }

        $current_time = time();
        $shc_start_time = strtotime($listing['shc_start_time']);
        $auction_start = (int) $shc_start_time + ((int) $listing['shc_days'] * 86400);
        $diff = $auction_start - $current_time;

        if ($diff <= 0) {
            if ($output) {
                $time_left = array(
                    'text' => $GLOBALS['lang']['shc_auction_closed'],
                    'value' => 0,
                    'current_time' => (int) $current_time,
                );

                return $time_left;
            }
            return $GLOBALS['lang']['shc_auction_closed'];
        }

        $y = 365 * 60 * 60 * 24;
        $m = 30 * 60 * 60 * 24;
        $d = 60 * 60 * 24;

        $years = floor($diff / $y);
        $months = floor(($diff - $years * $y) / $m);
        $days = floor(($diff - $years * $y - $months * $m) / $d);
        $hours = floor(($diff - $years * $y - $months * $m - $days * $d) / (60 * 60));
        $minutes = floor(($diff - $years * $y - $months * $m - $days * $d - $hours * 60 * 60) / 60);

        $timeAttr = explode('|', $GLOBALS['lang']['shc_auction_time_attr']);

        $date = $years > 0 ? (int) $years . $timeAttr[0] . ' ' : '';
        $date .= $months > 0 ? (int) $months . $timeAttr[1] . ' ' : '';
        $date .= $days > 0 ? (int) $days . $timeAttr[2] . ' ' : '';
        $date .= $hours > 0 ? (int) $hours . $timeAttr[3] . ' ' : '';
        $date .= (int) $minutes . $timeAttr[4];
        $date = trim($date);

        if ($output) {
            $time_left = array(
                'text' => $date,
                'value' => (int) $diff,
                'current_time' => (int) $current_time,
            );

            return $time_left;
        }

        return $date;
    }

    /**
     * Get unique bidders
     *
     * @param array $bids
     * @return array
     */
    public function getUniqueBidders($bids = array())
    {
        if (!$bids) {
            return 0;
        }

        $tmp = false;

        foreach ($bids as $bKey => $bVal) {
            $tmp[$bVal['Buyer_ID']]++;
        }

        return count($tmp);
    }

    /**
     * Get bids of acution
     *
     * @param int $itemID
     * @return array
     */
    public function getBids($itemID = 0)
    {
        if (!$itemID) {
            return [];
        }

        $sql = "SELECT `T1`.*, `T2`.`Username`, `T2`.`ID` AS `bidder_id`, ";
        $sql .= "IF(`T2`.`Last_name` <> '' AND `T2`.`First_name` <> '', ";
        $sql .= "CONCAT(`T2`.`First_name`, ' ', `T2`.`Last_name`), `T2`.`Username`) AS `bidder` ";
        $sql .= "FROM `{db_prefix}shc_bids` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Buyer_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`Item_ID` = '{$itemID}' ";
        $sql .= "GROUP BY `T1`.`ID` ";
        $sql .= "ORDER BY `T1`.`ID` DESC";

        return $GLOBALS['rlDb']->getAll($sql);
    }

    /**
     * Add bid to auction
     *
     * @param  int $id
     * @param  double $rate
     * @param  array $item_info
     * @return array
     */
    public function ajaxAddBid($id = 0, $rate = 0, $item_info = array())
    {
        global $reefless, $rlDb, $account_info, $rlShoppingCart, $rlMail, $rlAccount, $rlSmarty, $config;

        if (!$id || !$account_info) {
            return [];
        }

        $reefless->loadClass('Mail');

        $sql = "SELECT MAX(`T1`.`Number`) AS `max`, MAX(`T1`.`Total`) AS `max_bid`, ";
        $sql .= "(SELECT `T2`.`Buyer_ID` FROM `{db_prefix}shc_bids` AS `T2` WHERE `T2`.`ID` = MAX(`T1`.`ID`) LIMIT 1) AS `Buyer_ID` ";
        $sql .= "FROM `{db_prefix}shc_bids` AS `T1` ";
        $sql .= "WHERE `Item_ID` = '{$id}' ";

        $last_bid_info = $rlDb->getRow($sql);

        if ($last_bid_info['max_bid'] >= $rate) {
            return [];
        }

        $number = (int) $last_bid_info['max'];
        $current_bid = $rate + (int) $item_info['Bid_step'];
        $number++;

        $insert = array(
            'Item_ID' => $id,
            'Dealer_ID' => (int) $item_info['Account_ID'],
            'Buyer_ID' => (int) $account_info['ID'],
            'Number' => $number,
            'Total' => $rate,
            'Date' => 'NOW()',
        );

        if ($rlDb->insertOne($insert, 'shc_bids')) {
            // increase total bids
            $sql = "UPDATE `{db_prefix}listings` SET `shc_total_bids` = `shc_total_bids` + 1 WHERE `ID` = '{$id}' LIMIT 1";
            $rlDb->query($sql);

            $update = array(
                'fields' => array(
                    'Max_bid' => $rate,
                ),
                'where' => array(
                    'Listing_ID' => $id,
                ),
            );

            $rlDb->updateOne($update, 'shc_listing_options');

            // update count bidders
            $bids = $this->getBids($id);
            $bidders = $this->getUniqueBidders($bids);

            // prepare price values to mail
            $max_bid = $rlShoppingCart->addCurrency($last_bid_info['max_bid']);
            $rate_to_mail = $rlShoppingCart->addCurrency($rate);
            $start_price = $rlShoppingCart->addCurrency($item_info['Start_price']);

            // send notification to previous member
            $buyer_info = $rlAccount->getProfile((int)$last_bid_info['Buyer_ID'], true);
            if ($buyer_info) {
                $increase_auction_bid = $rlMail->getEmailTemplate('increase_auction_bid');

                $search = array('{bidder_name}', '{item}', '{your_last_bid}', '{current_bid}', '{date}', '{link}');
                $replacement = array($buyer_info['Full_name'], $item_info['listing_title'], $max_bid, $rate_to_mail, date('Y-m-d: H:i:s'), $item_info['listing_link']);
                $increase_auction_bid['body'] = str_replace($search, $replacement, $increase_auction_bid['body']);
                $rlMail->send($increase_auction_bid, $buyer_info['Mail']);
            }

            // send notification to owner listing
            $seller_info = $rlAccount->getProfile((int) $item_info['Account_ID'], true);
            $new_auction_bid = $rlMail->getEmailTemplate('new_auction_bid');

            $search = array('{dealer_name}', '{item}', '{bidder}', '{current_bid}', '{date}', '{link}', '{start_price}');
            $replacement = array($seller_info['Full_name'], $item_info['listing_title'], $account_info['Full_name'], $rate_to_mail, date('Y-m-d H:i:s'), $item_info['listing_link'], $start_price);
            $new_auction_bid['body'] = str_replace($search, $replacement, $new_auction_bid['body']);
            $rlMail->send($new_auction_bid, $seller_info['Mail']);

            $bids = $this->getBids($item_info['ID']);
            $rlSmarty->assign_by_ref('bids', $bids);
            $price = explode('|', $item_info[$config['price_tag_field']])[0];

            $result = array(
                'count' => count($bids),
                'bidders' => $bidders,
                'number' => $number,
                'content' => $rlSmarty->fetch(RL_PLUGINS . 'shoppingCart/view/bids.tpl', null, null, false),
                'min_bid' => str_replace("{total}", $current_bid, $GLOBALS['lang']['shc_min_bid']),
                'rate' => number_format($rate, 2),
                'current_bid' => $current_bid,
                'hide_buy_now' => $price < $max_bid ? true : false,
            );

            return $result;
        }

        return [];
    }

    /**
     * Cancel id by buyer
     *
     * @param int $id
     * @return array
     */
    public function ajaxCancelBid($id = 0)
    {
        global $rlShoppingCart, $account_info, $rlDb, $config, $rlSmarty, $lang;

        if (!$id) {
            return [];
        }

        $bid_info = $rlDb->fetch('*', array('ID' => $id), null, 1, 'shc_bids', 'row');
        $item_info = $GLOBALS['rlListings']->getListing($bid_info['Item_ID'], true);

        if ($bid_info) {
            $sql = "DELETE FROM `{db_prefix}shc_bids` WHERE `ID` = '{$id}' ";
            $sql .= "AND (`Buyer_ID` = '{$account_info['ID']}' OR `Dealer_ID` = '{$account_info['ID']}') LIMIT 1";

            if ($rlDb->query($sql)) {
                $GLOBALS['reefless']->loadClass('Mail');

                $seller_info = $GLOBALS['rlAccount']->getProfile((int) $bid_info['Dealer_ID'], true);

                // get previous bid
                $sql = "SELECT * FROM `{db_prefix}shc_bids` WHERE `Item_ID` = '{$bid_info['Item_ID']}' ORDER BY `Total` DESC LIMIT 1";
                $previous_bid_info = $rlDb->getRow($sql);

                $price = $previous_bid_info['Total'] > 0 ? $previous_bid_info['Total'] : $item_info['shc_start_price'];
                $current_bid = $price + $listing_data['shc_bid_step'];

                // increase total bids
                $sql = "UPDATE `{db_prefix}listings` SET `shc_total_bids` = `shc_total_bids` - 1 ";
                $sql .= "WHERE `ID` = '{$bid_info['Item_ID']}' LIMIT 1";
                $rlDb->query($sql);

                // update max bid
                $sql = "UPDATE `{db_prefix}shc_listing_options` SET `Max_bid` = '" . (float) $previous_bid_info['Total'] . "' ";
                $sql .= "WHERE `Listing_ID` = '{$bid_info['Item_ID']}' LIMIT 1";
                $rlDb->query($sql);

                // send notification to bidder
                $auction_cancel_bid = $GLOBALS['rlMail']->getEmailTemplate('auction_cancel_bid');

                $search = array('{bidder}', '{item}', '{date}', '{link}');
                $replacement = array($account_info['Full_name'], $item_info['listing_title'], date('Y-m-d'), $item_info['listing_link']);
                $auction_cancel_bid['body'] = str_replace($search, $replacement, $auction_cancel_bid['body']);
                $GLOBALS['rlMail']->send($auction_cancel_bid, $account_info['Mail']);

                // send notification to dealer
                $auction_cancel_bid_dealer = $GLOBALS['rlMail']->getEmailTemplate('auction_cancel_bid_dealer');

                $search = array('{dealer}', '{bidder}', '{item}', '{date}', '{link}');
                $replacement = array($seller_info['Full_name'], $account_info['Full_name'], $item_info['listing_title'], date('Y-m-d'), $item_info['listing_link']);
                $auction_cancel_bid_dealer['body'] = str_replace($search, $replacement, $auction_cancel_bid_dealer['body']);
                $GLOBALS['rlMail']->send($auction_cancel_bid_dealer, $seller_info['Mail']);

                // send notification previous bidder
                $previous_bidder_info = $GLOBALS['rlAccount']->getProfile($previous_bid_info['Buyer_ID']);

                if ($previous_bidder_info) {
                    $total = $rlShoppingCart->addCurrency($previous_bid_info['Total']);
                    $auction_bid_first_place = $GLOBALS['rlMail']->getEmailTemplate('auction_bid_first_place');

                    $search = array('{bidder}', '{item}', '{total}', '{date}', '{link}');
                    $replacement = array($previous_bidder_info['Full_name'], $item_info['listing_title'], $total, date('Y-m-d'), $item_info['listing_link']);
                    $auction_bid_first_place['body'] = str_replace($search, $replacement, $auction_bid_first_place['body']);
                    $GLOBALS['rlMail']->send($auction_bid_first_place, $previous_bidder_info['Mail']);
                }

                $bids = $this->getBids($item_info['ID']);
                $rlSmarty->assign_by_ref('bids', $bids);
                $rlSmarty->assign_by_ref('lang', $lang);
                $rlSmarty->assign('account_info', $account_info);

                $result = array(
                    'bids' => count($bids),
                    'bidders' => $this->getUniqueBidders($bids),
                    'content' => $rlSmarty->fetch(RL_PLUGINS . 'shoppingCart/view/bids.tpl', null, null, false),
                    'min_bid' => str_replace("{total}", $current_bid, $GLOBALS['lang']['shc_min_bid']),
                    'price' => number_format($price, 2),
                );

                return $result;
            }
        }

        return [];
    }

    /**
     * Renew auction
     *
     * @param int $item_id
     * @return bool
     */
    public function ajaxRenewAuction($item_id = 0)
    {
        global $rlShoppingCart, $reefless, $rlDb, $account_info, $rlMail, $pages;

        if (!$item_id) {
            return false;
        }

        $reefless->loadClass('Mail');
        $item_info = $GLOBALS['rlListings']->getListing($item_id, true);
        $options = $rlShoppingCart->getListingOptions($item_id, $item_info);

        if ($item_info['shc_auction_status'] == 'closed') {
            $update = array(
                'fields' => array(
                    'shc_start_time' => 'NOW()',
                    'shc_total_bids' => 0,
                    'shc_quantity' => 1,
                    'shc_auction_status' => 'active',
                ),
                'where' => array(
                    'ID' => $item_id,
                ),
            );

            $rlDb->updateOne($update, 'listings');

            $update = array(
                'fields' => array(
                    'End_time' => '0000-00-00 00:00:00',
                    'Max_bid' => 0,
                    'Auction_won' => '',
                ),
                'where' => array(
                    'ID' => $options['ID'],
                ),
            );

            $rlDb->updateOne($update, 'shc_listing_options');

            // send notification to previous bidders
            $sql = "SELECT `T1`.*, `T2`.`Mail`, ";
            $sql .= "IF(`T2`.`Last_name` <> '' AND `T2`.`First_name` <> '', CONCAT(`T2`.`First_name`, ' ', `T2`.`Last_name`), `T2`.`Username`) AS `Full_name` ";
            $sql .= "FROM `{db_prefix}shc_bids` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Buyer_ID` = `T2`.`ID` ";
            $sql .= "WHERE `T1`.`Item_ID` = '{$item_id}' ";
            $sql .= "GROUP BY `T1`.`Buyer_ID` ";

            $bidders = $rlDb->getAll($sql);

            if ($bidders) {
                // delete old bids
                $sql = "DELETE FROM `{db_prefix}shc_bids` WHERE `Item_ID` = '{$item_id}' ";
                $rlDb->query($sql);

                $renew_auction_bidder = $rlMail->getEmailTemplate('renew_auction_bidder');

                foreach ($bidders as $bKey => $bVal) {
                    $copy_renew_auction_bidder = $renew_auction_bidder;

                    $search = array(
                        '{name}',
                        '{item}',
                        '{start_price}',
                        '{days}',
                        '{date}',
                        '{link}',
                    );
                    $replacement = array(
                        $bVal['Full_name'],
                        $item_info['listing_title'],
                        $GLOBALS['rlShoppingCart']::addCurrency($options['Start_price']),
                        $item_info['shc_days'],
                        date('Y-m-d: H:i:s'),
                        $item_info['listing_link'],
                    );

                    $copy_renew_auction_bidder['body'] = str_replace($search, $replacement, $copy_renew_auction_bidder['body']);
                    $rlMail->send($copy_renew_auction_bidder, $bVal['Mail']);

                    unset($copy_renew_auction_bidder);
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Close auction by dealer
     *
     * @param int $item_id
     * @return bool
     */
    public function ajaxCloseAuction($item_id = 0)
    {
        global $rlShoppingCart, $reefless, $rlDb, $account_info, $rlMail, $pages;

        if (!$item_id) {
            return false;
        }

        $reefless->loadClass('Listings');
        $reefless->loadClass('Mail');
        $item_info = $GLOBALS['rlListings']->getListing($item_id, true);

        if ($item_info['shc_auction_status'] != 'active') {
            return false;
        }

        $options = $rlShoppingCart->getListingOptions($item_id, $item_info);

        $sql = "SELECT `T1`.*, `T2`.`Mail`, ";
        $sql .= "IF(`T2`.`Last_name` <> '' AND `T2`.`First_name` <> '', CONCAT(`T2`.`First_name`, ' ', `T2`.`Last_name`), `T2`.`Username`) AS `Full_name` ";
        $sql .= "FROM `{db_prefix}shc_bids` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Buyer_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`Item_ID` = '{$item_id}' ";
        $sql .= "ORDER BY `T1`.`ID` ASC";

        $bidders = $rlDb->getAll($sql);
        $lastBidder = $bidders[count($bidders) - 1];

        $update = array(
            'fields' => array(
                'shc_total_bids' => count($bidders),
                'shc_auction_status' => 'closed',
            ),
            'where' => array(
                'ID' => $item_id,
            ),
        );

        $rlDb->updateOne($update, 'listings');

        $update = array(
            'fields' => array(
                'End_time' => 'NOW()',
                'Max_bid' => $lastBidder['Total'],
                'Auction_won' => $lastBidder['Buyer_ID'],
            ),
            'where' => array(
                'ID' => $options['ID'],
            ),
        );

        $rlDb->updateOne($update, 'shc_listing_options');

        // send notification to bidders
        if ($bidders) {
            $sent = [];
            $auction_closed_seller = $rlMail->getEmailTemplate('auction_closed_seller');

            foreach ($bidders as $bKey => $bVal) {
                if ($bVal['ID'] == $lastBidder['ID']) {
                    $auction_closed_seller_winner = $rlMail->getEmailTemplate('auction_closed_seller_winner');

                    $search = array(
                        '{buyer}',
                        '{seller}',
                        '{item}',
                        '{start_price}',
                        '{price}',
                        '{date}',
                        '{link}',
                    );
                    $replacement = array(
                        $bVal['Full_name'],
                        $account_info['Full_name'],
                        $item_info['listing_title'],
                        $GLOBALS['rlShoppingCart']::addCurrency($options['Start_price']),
                        $GLOBALS['rlShoppingCart']::addCurrency($lastBidder['Total']),
                        date('Y-m-d: H:i:s'),
                        $item_info['listing_link'],
                    );

                    $auction_closed_seller_winner['body'] = str_replace($search, $replacement, $auction_closed_seller_winner['body']);
                    $rlMail->send($auction_closed_seller_winner, $bVal['Mail']);
                } else {
                    if (in_array($bVal['Buyer_ID'], $sent) || $bVal['Buyer_ID'] == $lastBidder['Buyer_ID']) {
                        continue;
                    }

                    $sent[] = $bVal['Buyer_ID'];
                    $copy_auction_closed_seller = $auction_closed_seller;

                    $search = array(
                        '{buyer}',
                        '{item}',
                        '{start_price}',
                        '{price}',
                        '{date}',
                        '{link}',
                    );
                    $replacement = array(
                        $bVal['Full_name'],
                        $item_info['listing_title'],
                        $GLOBALS['rlShoppingCart']::addCurrency($options['Start_price']),
                        $GLOBALS['rlShoppingCart']::addCurrency($lastBidder['Total']),
                        date('Y-m-d: H:i:s'),
                        $item_info['listing_link'],
                    );

                    $copy_auction_closed_seller['body'] = str_replace($search, $replacement, $copy_auction_closed_seller['body']);
                    $rlMail->send($copy_auction_closed_seller, $bVal['Mail']);

                    unset($copy_auction_closed_seller);
                }
            }
        }

        return true;
    }

    /**
     * Delete bid
     *
     * @param int $id
     * @return array
     */
    public function ajaxDeleteBid($id = 0)
    {
        global $rlDb, $lang, $config, $rlMail, $reefless;

        $response = [
            'status' => 'ERROR',
            'message' => $lang['error'],
        ];

        if (!$id) {
            return $response;
        }

        // get bid
        $sql = "SELECT * FROM `{db_prefix}shc_bids` WHERE `ID` = '{$id}' ORDER BY `Total` DESC LIMIT 1";
        $bid_info = $rlDb->getRow($sql);

        $sql = "DELETE FROM `{db_prefix}shc_bids` WHERE `ID` = '{$id}' LIMIT 1";

        if ($rlDb->query($sql)) {
            $reefless->loadClass('Account');
            $reefless->loadClass('Listings');
            $reefless->loadClass('Mail');

            // get bidder
            $bidder_info = $GLOBALS['rlAccount']->getProfile($bid_info['Buyer_ID']);

            // get previous bid
            $sql = "SELECT * FROM `{db_prefix}shc_bids` WHERE `Item_ID` = '{$bid_info['Item_ID']}' ORDER BY `Total` DESC LIMIT 1";
            $previous_bid_info = $rlDb->getRow($sql);

            // increase total bids
            $sql = "UPDATE `{db_prefix}listings` SET `shc_total_bids` = `shc_total_bids` - 1 WHERE `ID` = '{$bid_info['Item_ID']}' LIMIT 1 ";
            $rlDb->query($sql);

            $update = array(
                'fields' => array(
                    'Max_bid' => (float) $previous_bid_info['Total'],
                ),
                'where' => array(
                    'Listing_ID' => $bid_info['Item_ID'],
                ),
            );

            $rlDb->updateOne($update, 'shc_listing_options');

            $item_info = $GLOBALS['rlListings']->getListing($bid_info['Item_ID'], true);

            // send notification to bidder
            $auction_delete_bid = $rlMail->getEmailTemplate('auction_delete_bid');

            $search = array('{bidder}', '{item}', '{date}', '{link}');
            $replacement = array($bidder_info['Full_name'], $item_info['listing_title'], date('Y-m-d'), $item_info['listing_link']);
            $auction_delete_bid['body'] = str_replace($search, $replacement, $auction_delete_bid['body']);
            $rlMail->send($auction_delete_bid, $bidder_info['Mail']);

            $response = [
                'status' => 'OK',
                'message' => $lang['shc_bid_deleted_success'],
            ];
        }

        return $response;
    }

    /**
     * Close exipred auctions
     */
    public function closeExipredItems()
    {
        global $rlShoppingCart, $rlDb, $rlMail, $rlListings, $rlListingTypes, $pages, $rlSmarty, $config, $rlAccount;

        $sql = "SELECT `T1`.*, ";
        $sql .= "IF(TIMESTAMPDIFF(HOUR, `T1`.`shc_start_time`, NOW()) > `T1`.`shc_days` * 24, '1', '0') `expired`, ";
        $sql .= "COUNT(`T2`.`ID`) AS `total_bids`, MAX(`T2`.`Total`) AS `max_bid`, ";
        $sql .= "(SELECT `T5`.`Buyer_ID` FROM `{db_prefix}shc_bids` AS `T5` WHERE `T5`.`ID` = MAX(`T2`.`ID`) LIMIT 1) AS `Buyer_ID`, ";
        $sql .= "MAX(`T2`.`ID`) AS `Bid_ID`, ";
        $sql .= "IF(`T3`.`Last_name` <> '' AND `T3`.`First_name` <> '', CONCAT(`T3`.`First_name`, ' ', `T3`.`Last_name`), `T3`.`Username`) AS `dFull_name`, `T3`.`Mail` AS `dMail`, ";
        $sql .= "`T4`.`Path` AS `Cat_path`, `T4`.`Key` AS `Cat_key`, `T4`.`Type` AS `Listing_type`, `T4`.`Type` AS `Cat_type`, ";
        $sql .= "`T6`.`Reserved_price`, `T6`.`Shipping_price_type` ";

        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}shc_bids` AS `T2` ON `T1`.`ID` = `T2`.`Item_ID` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T3` ON `T1`.`Account_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T4` ON `T1`.`Category_ID` = `T4`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}shc_listing_options` AS `T6` ON `T1`.`ID` = `T6`.`Listing_ID` ";

        $sql .= "WHERE `T1`.`shc_auction_status` <> 'closed' AND `T1`.`shc_mode` = 'auction' ";
        $sql .= "GROUP BY `T1`.`ID` ";
        $sql .= "ORDER BY `T1`.`ID` DESC LIMIT {$config['listings_number']}";

        $items = $rlDb->getAll($sql);

        if ($items) {
            $auction_close_dealer = $rlMail->getEmailTemplate('cron_auction_close_dealer');
            $auction_close_dealer_ww = $rlMail->getEmailTemplate('cron_auction_close_dealer_ww');
            $auction_winner = $rlMail->getEmailTemplate('cron_auction_winner');

            foreach ($items as $key => $value) {
                $winner = false;

                // get item title
                $listing_type = $GLOBALS['rlListingTypes']->types[$value['Listing_type']];
                $value['title'] = $GLOBALS['rlListings']->getListingTitle($value['Category_ID'], $value, $listing_type['Key']);

                if ($value['expired']) {
                    // close auction
                    $update = array(
                        'fields' => array('End_time' => 'NOW()'),
                        'where' => array('Listing_ID' => $value['ID']),
                    );

                    $rlDb->updateOne($update, 'shc_listing_options');

                    $update = array(
                        'fields' => array('shc_auction_status' => 'closed'),
                        'where' => array('ID' => $value['ID']),
                    );

                    $rlDb->updateOne($update, 'listings');

                    $sql = "SELECT * FROM `{db_prefix}shc_bids` WHERE `ID` = '{$value['Bid_ID']}'";
                    $bid_info = $rlDb->getRow($sql);

                    // define winner
                    if ($value['total_bids'] > 0 && $value['Reserved_price'] <= $value['max_bid']) {
                        $winner = true;

                        // get buyer info
                        $buyer_info = $rlAccount->getProfile((int) $value['Buyer_ID']);

                        if ($config['shc_commission_enable'] && $config['shc_commission'] > 0 && $config['shc_method'] == 'multi') {
                            $commission = PriceFormat::calculateCommission($value['max_bid'], true);
                        }

                        $orderKey = $GLOBALS['reefless']->generateHash(8, 'upper');
                        $insert = array(
                            'Order_key' => $orderKey . '-D' . (int) $value['Account_ID'],
                            'Type' => 'auction',
                            'Item_ID' => (int) $value['ID'],
                            'Txn_ID' => $orderKey,
                            'Dealer_ID' => (int) $value['Account_ID'],
                            'Buyer_ID' => (int) $bid_info['Buyer_ID'],
                            'Total' => (float) $value['max_bid'],
                            'Date' => 'NOW()',
                        );

                        if ($config['shc_commission'] && $config['shc_method'] == 'multi') {
                            $insert['Commission'] = (float) $config['shc_commission'];
                            $insert['Commission_total'] = $commission;
                        }

                        if ($rlDb->insertOne($insert, 'shc_orders')) {
                            $order_id = $rlDb->insertID();

                            // add order details
                            $rlDb->insertOne(array(
                                'Order_key' => $orderKey,
                                'Order_ID' => $order_id,
                                'Dealer_ID' => $value['Account_ID'],
                                'Buyer_ID' => $bid_info['Buyer_ID'],
                                'Item_ID' => $value['ID'],
                                'Item' => $value['title'],
                                'Price' => (float) $value['max_bid'],
                                'Date' => 'Now()',
                                'Free_shipping' => $value['Shipping_price_type'] == 'free' ? 1 : 0,
                            ), 'shc_order_details');

                            // set winner
                            $update = array(
                                'fields' => array(
                                    'Auction_won' => $value['Buyer_ID'],
                                ),
                                'where' => array(
                                    'Listing_ID' => $value['ID'],
                                ),
                            );

                            $rlDb->updateOne($update, 'shc_listing_options');

                            // send notification to winner
                            $copy_auction_winner = $auction_winner;

                            $listing_link = $GLOBALS['reefless']->getListingUrl($value, $buyer_info['Lang']);

                            $search = array(
                                '{name}',
                                '{owner}',
                                '{item}',
                                '{price}',
                                '{date}',
                                '{link}',
                            );
                            $replacement = array(
                                $buyer_info['Full_name'],
                                $value['dFull_name'],
                                $value['title'],
                                $rlShoppingCart->addCurrency($value['max_bid']),
                                date('Y-m-d H:i:s'),
                                $listing_link,
                            );

                            $copy_auction_winner['body'] = str_replace($search, $replacement, $copy_auction_winner['body']);
                            $GLOBALS['rlMail']->send($copy_auction_winner, $buyer_info['Mail']);
                            unset($copy_auction_winner, $search, $replacement);
                        }
                    }

                    $total = $rlShoppingCart->addCurrency($value['max_bid']);
                    $details = "
{$GLOBALS['lang']['item']}: {$value['title']}<br />
{$GLOBALS['lang']['total']}: {$total}<br />
{$GLOBALS['lang']['shc_bids']}: {$value['total_bids']}<br />
{$GLOBALS['lang']['date']}: " . date('Y-m-d H:i:s') . "<br />
";

                    // send notification to dealer
                    if ($winner) {
                        $copy_auction_close_dealer_ww = $auction_close_dealer_ww;

                        $search = array('{dealer_name}', '{buyer}', '{details}');
                        $replacement = array($value['dFull_name'], $buyer_info['Full_name'], $details);

                        $copy_auction_close_dealer_ww['body'] = str_replace($search, $replacement, $copy_auction_close_dealer_ww['body']);
                        $GLOBALS['rlMail']->send($copy_auction_close_dealer_ww, $value['dMail']);
                        unset($copy_auction_close_dealer_ww, $search, $replacement);
                    } else {
                        // close without winner
                        $copy_auction_close_dealer = $auction_close_dealer;

                        $search = array('{dealer_name}', '{details}');
                        $replacement = array($value['dFull_name'], $details);

                        $copy_auction_close_dealer['body'] = str_replace($search, $replacement, $copy_auction_close_dealer['body']);
                        $GLOBALS['rlMail']->send($copy_auction_close_dealer, $value['dMail']);
                        unset($copy_auction_close_dealer, $search, $replacement);
                    }
                }

                unset($buyer_info);
            }
        }
    }

    /**
     * Set automatically rate of auctions
     */
    public function setAutomaticallyRate()
    {
        global $rlDb, $config, $rlListingTypes, $rlListings, $rlMail;

        if (!$config['shc_auto_rate']) {
            return;
        }

        $sql = "SELECT `T1`.*, `T3`.`Path`, `T3`.`Type` AS `Listing_type`, `T3`.`Key` AS `Cat_key`,  ";
        $sql .= "`T3`.`Type` AS `Cat_type`, `T3`.`Path` AS `Cat_path`, MAX(`T2`.`Date`) AS `max_date`, `T4`.`Bid_step` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}shc_bids` AS `T2` ON `T1`.`ID` = `T2`.`Item_ID` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}shc_listing_options` AS `T4` ON `T1`.`ID` = `T4`.`Listing_ID` ";
        $sql .= "WHERE `T1`.`shc_auction_status` <> 'closed' AND TIMESTAMPDIFF(HOUR, `T1`.`shc_start_time`, NOW()) < `T1`.`shc_days` * 24 ";
        $sql .= "GROUP BY `T1`.`ID` ";
        $sql .= "HAVING TIMESTAMPDIFF(HOUR, MAX(`T2`.`Date`), NOW()) > {$config['shc_auto_rate_period']} ";
        $sql .= "LIMIT {$config['listings_number']}";

        $items = $rlDb->getAll($sql);

        if ($items) {
            $increase_auction_bid = $rlMail->getEmailTemplate('increase_auction_bid_auto');

            foreach ($items as $key => $val) {
                $sql = "SELECT MAX(`Total`) as `max_bid`, COUNT(`ID`) AS `total_bids` ";
                $sql .= "FROM `{db_prefix}shc_bids` WHERE `Item_ID` = '{$val['ID']}' ";
                $bidInfo = $rlDb->getRow($sql);

                $current_bid = $bidInfo['max_bid'];
                $new_bid = (float) $bidInfo['max_bid'] + (float) $val['Bid_step'];

                $insert = array(
                    'Item_ID' => $val['ID'],
                    'Dealer_ID' => $val['Account_ID'],
                    'Number' => $bidInfo['total_bids'] + 1,
                    'Total' => $new_bid,
                    'Date' => 'NOW()',
                );

                if ($rlDb->insertOne($insert, 'shc_bids')) {
                    // increase total bids
                    $sql = "UPDATE `{db_prefix}listings` SET `shc_total_bids` = `shc_total_bids` + 1 WHERE `ID` = '{$val['ID']}' LIMIT 1";
                    $rlDb->query($sql);

                    $update = array(
                        'fields' => array(
                            'Max_bid' => $new_bid,
                        ),
                        'where' => array(
                            'Listing_ID' => $val['ID'],
                        ),
                    );

                    $rlDb->updateOne($update, 'shc_listing_options');

                    $last_bid_info = $rlDb->fetch('*', array('ID' => $val['ID']), false, 1, 'shc_bids', 'row');

                    if ($last_bid_info['Byuer_ID']) {
                        // get buyer info
                        $buyer_info = $rlDb->fetch(
                            array('ID', 'Username', 'Last_name', 'First_name', 'Mail'),
                            array('ID' => $last_bid_info['Byuer_ID']),
                            null,
                            1,
                            'accounts',
                            'row'
                        );

                        // get item details
                        $listing_type = $rlListingTypes->types[$val['Listing_type']];
                        $listing_title = $rlListings->getListingTitle($val['Category_ID'], $val, $listing_type['Key']);

                        $listing_link = $GLOBALS['reefless']->getListingUrl($val);

                        // send notification to buyer
                        $copy_increase_auction_bid = $increase_auction_bid;

                        $search = array(
                            '{item}',
                            '{your_last_bid}',
                            '{current_bid}',
                            '{date}',
                            '{link}',
                        );

                        $replacement = array(
                            $listing_title,
                            number_format($bidInfo['max_bid'], 2),
                            number_format($new_bid, 2),
                            date('Y-m-d: H:i:s'),
                            $listing_link,
                        );

                        $copy_increase_auction_bid['body'] = str_replace($search, $replacement, $copy_increase_auction_bid['body']);
                        $rlMail->send($copy_increase_auction_bid, $buyer_info['Mail']);

                        unset($copy_increase_auction_bid, $buyer_info, $listing_title, $listing_link);
                    }
                }
            }
        }

        unset($items);
    }

    /**
     * Get last bid by item
     *
     * @param int $item_id
     * @return array
     */
    public function getLastBid($item_id = 0)
    {
        if (!$item_id) {
            return array();
        }

        $sql = "SELECT * FROM `{db_prefix}shc_bids` WHERE `Item_ID` = '{$item_id}' ORDER BY `Total` DESC";

        return $GLOBALS['rlDb']->getRow($sql);
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
        global $rlMail, $rlSmarty, $rlDb, $config, $reefless;

        if (!$itemID || !$accountID) {
            return;
        }

        $orders = new Orders();
        $orderInfo = $orders->get((int) $itemID, true);

        if ($orderInfo['Buyer_ID'] == $accountID) {
            $reefless->loadClass('Mail');
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
            }
            $rlDb->updateOne(array(
                'fields' => $updateOrder,
                'where' => array('ID' => $itemID),
            ), 'shc_orders');

            // update order details
            $rlDb->updateOne(array(
                'fields' => array(
                    'Status' => 'completed',
                ),
                'where' => array(
                    'Order_ID' => $itemID
                ),
            ), 'shc_order_details');

            if (!is_object($rlSmarty)) {
                require_once RL_LIBS . 'smarty' . RL_DS . 'Smarty.class.php';
                $reefless->loadClass('Smarty');
            }

            $paymentType = $cash ? 'cash' : 'payment';
            $rlSmarty->assign('paymentType', $paymentType);

            $rlSmarty->assign('order_info', $orderInfo);
            $details = $rlSmarty->fetch(RL_PLUGINS . 'shoppingCart/order_details_mail.tpl', null, null, false);

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

            $GLOBALS['rlMail']->send($mailTpl, $config['notifications_email']);

            // send payment notification email to dealer
            if ($config['shc_method'] == 'multi') {
                $GLOBALS['rlMail']->send($mailTpl, $orderInfo['dMail']);
            }

            return true;
        }

        return false;
    }

    /**
     * Get total bids in auction
     *
     * @param int $auctionID
     * @return int
     */
    public function getTotalBids($auctionID = 0)
    {
        if (!$auctionID) {
            return 0;
        }

        $sql = "SELECT COUNT(`ID`) AS `total` FROM `{db_prefix}shc_bids` WHERE `Item_ID` = {$auctionID} ";
        $bidsInfo = $GLOBALS['rlDb']->getRow($sql);

        return (int) $bidsInfo['total'];
    }

    /**
     * Complete order by cash
     *
     * @param array $order
     * @param int $accountID
     */
    public function completeByCash($orderID, $accountID = 0)
    {
        if (!$orderID || !$accountID) {
            return;
        }

        $this->complete($orderID, $accountID, true);
    }
}
