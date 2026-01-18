<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : SHOPPING.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2022
 *	https://www.flynax.com
 *
 ******************************************************************************/

namespace ShoppingCart\Admin;

use \ShoppingCart\PriceFormat;

/**
 * @since 3.0.0
 */
class Shopping
{
    /**
     * Get orders
     *
     * @param int $start
     * @param int $limit
     * @return array
     */
    public function getOrders($start = 0, $limit = 0)
    {
        global $rlDb, $lang, $config;

        $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.*, `T3`.`Username` AS `bUsername`, ";
        $sql .= "`T3`.`Last_name` AS `bLast_name`,  `T3`.`First_name` AS `bFirst_name`, ";
        $sql .= "`T4`.`First_name` AS `dFirst_name`, ";
        $sql .= "`T4`.`Username` AS `dUsername`, `T4`.`Last_name` AS `dLast_name`, ";
        $sql .= "GROUP_CONCAT(SUBSTRING_INDEX(`T5`.`Item`, ', $', 1) ";
        $sql .= "ORDER BY `T5`.`Date` DESC SEPARATOR '<br />') AS `title` ";
        $sql .= "FROM `{db_prefix}shc_orders` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T3` ON `T1`.`Buyer_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T4` ON `T1`.`Dealer_ID` = `T4`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}shc_order_details` AS `T5` ";
        $sql .= "ON `T1`.`Order_key` = CONCAT(`T5`.`Order_key`, '-D', `T1`.`Dealer_ID`) AND `T5`.`Dealer_ID` = `T1`.`Dealer_ID` ";
        $sql .= "WHERE `T1`.`Status` <> 'trash' AND `T1`.`Type` = 'shopping' ";
        $sql .= "GROUP BY `T1`.`ID` ";
        $sql .= "ORDER BY `T1`.`Date` DESC LIMIT {$start}, {$limit}";

        $data = $rlDb->getAll($sql);
        $total_rows = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

        foreach ($data as $key => $val) {
            $data[$key]['pStatus'] = $lang[$val['Status']];
            $data[$key]['Shipping_status'] = $val['Shipping_status'] == 'pending'
            ? $lang[$val['Shipping_status']]
            : $lang['shc_' . $val['Shipping_status']];

            if ($config['shc_commission_enable'] && $config['shc_commission'] > 0 && $config['shc_method'] == 'multi') {
                $data[$key]['Commission_total'] = $val['Commission'] ?: PriceFormat::calculateCommission($val['Total'], true);
            }
            $data[$key]['bFull_name'] = trim($val['bFirst_name'] || $val['bLast_name'] 
                ? $val['bFirst_name'] . ' ' . $val['bLast_name'] 
                : $val['bUsername']);
            $data[$key]['dFull_name'] = trim($val['dFirst_name'] || $val['dLast_name'] 
                ? $val['dFirst_name'] . ' ' . $val['dLast_name'] 
                : $val['dUsername']);
        }

        return array('data' => $data, 'total' => $total_rows['count']);
    }

    /**
     * Get order details
     */
    public function apOrderDetails()
    {
        global $sError, $rlDb, $lang, $rlSmarty, $config;

        $item_id = (int) $_GET['item'];

        if (!$item_id) {
            $sError = true;
            return;
        }

        $sql = "SELECT `T1`.*, ";
        $sql .= "`T2`.`Username` AS `dUsername`, IF(`T2`.`Last_name` <> '' AND `T2`.`First_name` <> '', ";
        $sql .= "CONCAT(`T2`.`First_name`, ' ', `T2`.`Last_name`), `T2`.`Username`) AS `dFull_name`, ";
        $sql .= "`T3`.`Username` AS `bUsername`, IF(`T3`.`Last_name` <> '' AND `T3`.`First_name` <> '', ";
        $sql .= "CONCAT(`T3`.`First_name`, ' ', `T3`.`Last_name`), `T3`.`Username`) AS `bFull_name` ";
        $sql .= "FROM `{db_prefix}shc_orders` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Dealer_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T3` ON `T1`.`Buyer_ID` = `T3`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = '{$item_id}' ";
        $sql .= "LIMIT 1";

        $order_info = $rlDb->getRow($sql);

        if ($order_info) {
            $orders = new \ShoppingCart\Orders();
            $orders->adaptShippingOptions($order_info);

            $total = 0;

            $order_info['pStatus'] = $lang['shc_' . $order_info['pStatus']];
            $order_info['Shipping_status'] = $lang['shc_' . $order_info['Shipping_status']];

            $sql = "SELECT `T1`.* ";
            $sql .= "FROM `{db_prefix}shc_order_details` AS `T1` ";
            $sql .= "WHERE `T1`.`Order_key` = '" . explode('-', $order_info['Order_key'])[0] . "'  ";
            $sql .= "AND `T1`.`Dealer_ID` = '{$order_info['Dealer_ID']}' ";
            $sql .= "ORDER BY `T1`.`Date` DESC";

            $order_info['items'] = $rlDb->getAll($sql);

            if ($order_info['items']) {
                foreach ($order_info['items'] as $iKey => $iVal) {
                    $order_info['items'][$iKey]['shipping_item_options'] = unserialize($iVal['Shipping_item_options']);
                    $order_info['items'][$iKey]['total'] = round(($iVal['Price'] * $iVal['Quantity']), 2);

                    // get listing details
                    $listing = $GLOBALS['rlListings']->getListing($iVal['Item_ID'], true);
                    $listing_options = $rlDb->fetch(
                        '*',
                        array('Listing_ID' => $iVal['Item_ID']),
                        null,
                        1,
                        'shc_listing_options',
                        'row'
                    );

                    $itemShipping = $order_info['items'][$iKey]['shipping_item_options'];
                    if (empty($itemShipping['service'])) {
                        $service = $lang['shipping_methods+name+' . $itemShipping['method']];
                        if (!empty($itemShipping['domestic_services'])) {
                            $service .= " ({$itemShipping['domestic_services']})";
                        }
                        if (!empty($itemShipping['international_services'])) {
                            $service .= " ({$itemShipping['international_services']})";
                        }
                        $order_info['items'][$iKey]['shipping_item_options']['service'] = $service;
                    }

                    if ($listing) {
                        $order_info['items'][$iKey]['Main_photo'] = $listing['Main_photo'];
                        $order_info['items'][$iKey]['listing_link'] = $listing['listing_link'];
                    }

                    if ($listing_options) {
                        $order_info['items'][$iKey]['shipping_options'] = unserialize($listing_options['Shipping_options']);
                        $order_info['items'][$iKey]['dimensions'] = unserialize($listing_options['Dimensions']);
                        $order_info['items'][$iKey]['Shipping_price_type'] = $listing_options['Shipping_price_type'];
                        $order_info['items'][$iKey]['Shipping_price'] = (float) $listing_options['Shipping_price'];
                        if ($config['shc_shipping_price_fixed'] == 'multi') {
                            $order_info['items'][$iKey]['Shipping_fixed_prices'] = unserialize($listing_options['Shipping_fixed_prices']);
                        }
                        if ($config['shc_digital_product']) {
                            $order_info['items'][$iKey]['Digital'] = (bool) $listing_options['Digital'];
                            $order_info['items'][$iKey]['Digital_product'] = $listing_options['Digital_product'];
                        }

                        if ($listing_options['Shipping_price_type'] == 'fixed') {
                            $order_info['items'][$iKey]['shipping_item_options']['service'] = $lang['shc_' . $itemShipping['method']];
                            continue;
                        }
                    }

                    $total += $order_info['items'][$iKey]['total'];
                }
            }

            // get transaction info
            $sql = "SELECT * FROM `{db_prefix}transactions` WHERE `Item_ID` = {$item_id} ";
            $sql .= "AND `Service` = 'shopping' AND `Account_ID` = {$order_info['Buyer_ID']}";
            $order_info['txn_info'] = $rlDb->getRow($sql);

            if ($order_info['txn_info']) {
                $order_info['txn_info']['Gateway'] = $lang['payment_gateways+name+' . $order_info['txn_info']['Gateway']];
            }

            if ($config['shc_commission_enable'] && $config['shc_commission'] > 0 && $config['shc_method'] == 'multi') {
                $order_info['Commission_total'] = $order_info['Commission'] ?: PriceFormat::calculateCommission($order_info['Total'], true);
            }

            $rlSmarty->assign_by_ref('order_info', $order_info);
            $rlSmarty->assign_by_ref('total', $total);
        }
    }
}
