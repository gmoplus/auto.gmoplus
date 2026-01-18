<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : PRICEFORMAT.PHP
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

namespace ShoppingCart;

/**
 * @since 3.0.0
 */
class PriceFormat
{
    /**
     * Save shopping caer & bidding options;
     *
     * @param  int $listing_id
     * @return null
     */
    public static function saveOptions($listing_id = 0, $data = false)
    {
        global $rlDb, $config;

        if (!$listing_id) {
            return;
        }

        $listing_id = (int) $listing_id;
        $data = $data ?: $_POST['fshc'];
        $f_price = $config['price_tag_field'];

        $price = (float) $_POST['f'][$f_price]['value'];
        $commission = self::calculateCommission($price);

        if ($config['shc_commission'] && $config['shc_method'] == 'multi') {

            $sql = "SELECT * FROM `{db_prefix}listings` WHERE `ID` = '{$listing_id}' LIMIT 1";
            $listing_info = $rlDb->getRow($sql);

            if ($listing_info) {
                if ($listing_info[$f_price]) {
                    $listing_info[$f_price] = explode('|', $listing_info[$f_price]);
                    $price_value = $price . '|' . $listing_info[$f_price][1];
                } else {
                    $price_value = $price . '|' . $_POST['f'][$f_price]['currency'];
                }
            }
        }

        if ($data) {
            switch ($data['shc_mode']) {
                case 'auction':
                case 'fixed':
                    $fields = array(
                        'shc_mode' => $data['shc_mode'],
                        'shc_quantity' => $data['shc_quantity'] > 0 ? (int) $data['shc_quantity'] : 1,
                        'shc_available' => (int) $data['shc_available'],
                        'shc_start_time' => $data['shc_mode'] == 'auction' ? 'NOW()' : '0000-00-00 00:00:00',
                        'shc_days' => (int) $data['shc_days'],
                    );

                    $file_name = '';
                    if ($config['shc_digital_product']) {
                        // Check exists file
                        if ($data['sys_exist_digital_product'] && !empty($_FILES['digital_product']['tmp_name'])) {
                            // Remove old file
                            if (file_exists(RL_FILES . $data['sys_exist_digital_product'])) {
                                unlink(RL_FILES . $data['sys_exist_digital_product']);
                            }
                        }

                        // Upload new file
                        if (!empty($_FILES['digital_product']['tmp_name'])) {
                            $GLOBALS['reefless']->loadClass('Actions');

                            $file_name = 'listing_digital_' . time() . mt_rand();
                            $file_name = $GLOBALS['rlActions']->upload('digital_product', $file_name, false, false, 'fshc', false);

                            if ($file_name) {
                                $dir = RL_FILES . date('m-Y') . RL_DS . 'ad' . $listing_id . RL_DS;
                                $dir_name = date('m-Y') . '/ad' . $listing_id . '/';
                                $GLOBALS['reefless']->rlMkdir($dir);
                                rename(RL_FILES . $file_name, $dir . $file_name);
                                $file_name = $dir_name . $file_name;
                            }
                        }
                    }

                    $options = array(
                        'Listing_ID' => (int) $listing_id,
                        'Start_price' => (float) $data['shc_start_price'],
                        'Reserved_price' => (float) $data['shc_reserved_price'],
                        'Bid_step' => (float) $data['shc_bid_step'],
                        'Weight' => (float) $data['shc_weight'],
                        'Shipping_options' => serialize($_POST['shipping']),
                        'Package_type' => $data['shc_package_type'],
                        'Dimensions' => serialize($data['shc_dimensions']),
                        'Handling_time' => $data['shc_handling_time'],
                        'Shipping_price_type' => $data['shc_shipping_price_type'],
                        'Shipping_price' => (float) $data['shc_shipping_price'],
                        'Shipping_fixed_prices' => serialize($data['shc_shipping_fixed_prices']),
                        'Use_system_shipping_config' => (int) $data['shc_use_system_shipping_config'],
                        'Commission' => $commission,
                        'Shipping_discount' => (float) $data['shc_shipping_discount'],
                        'Shipping_discount_at' => (int) $data['shc_shipping_discount_at'],
                        'Digital' => (int) $data['digital'],
                        'Quantity_unlim' => (int) $data['quantity_unlim'],
                        'Digital_product' => $file_name ?: $data['sys_exist_digital_product'],
                        'Quantity_real' => $data['shc_quantity'] > 0 ? (int) $data['shc_quantity'] : 1,
                        'Shipping_method_fixed' => is_array($data['shipping_method_fixed'])
                            ? implode(',', $data['shipping_method_fixed'])
                            : '',
                    );
                    break;

                case 'listing':
                    $fields = array(
                        'shc_mode' => $data['shc_mode'],
                        'shc_available' => 0,
                    );

                    $options = array(
                        'Start_price' => 0,
                        'Reserved_price' => 0,
                        'Bid_step' => 0,
                        'Weight' => 0,
                        'Shipping_options' => '',
                        'Package_type' => '',
                        'Dimensions' => '',
                        'Handling_time' => '',
                        'Shipping_price_type' => '',
                        'Shipping_price' => 0,
                        'Use_system_shipping_config' => 0,
                        'Commission' => 0,
                        'Digital' => 0,
                        'Digital_product' => '',
                    );
                    break;
            }

            if ($data['shc_mode'] == 'auction'
                && $data['shc_edit']
                && !$data['shc_update_start_time']
                && !$data['shc_first_edit']
            ) {
                unset($fields['shc_start_time']);
            }

            if ($config['shc_commission_enable']
                && $config['shc_commission'] > 0
                && $config['shc_method'] == 'multi'
                && in_array($data['shc_mode'], array('auction', 'fixed'))
                && $config['shc_commission_add']
            ) {
                $fields[$f_price] = $price_value;
            }

            $update = array(
                'fields' => $fields,
                'where' => array(
                    'ID' => (int) $listing_id,
                ),
            );

            $rlDb->updateOne($update, 'listings');

            $exist_item = $rlDb->fetch('*', array('Listing_ID' => $listing_id), null, 1, 'shc_listing_options', 'row');

            if (!$exist_item) {
                $rlDb->insertOne(
                    $options,
                    'shc_listing_options',
                    array('Shipping_options', 'Dimensions', 'Shipping_fixed_prices')
                );
            } else {
                $update = array(
                    'fields' => $options,
                    'where' => array(
                        'Listing_ID' => (int) $listing_id,
                    ),
                );

                $rlDb->updateOne(
                    $update,
                    'shc_listing_options',
                    array('Shipping_options', 'Dimensions', 'Shipping_fixed_prices')
                );
            }
        }
    }

    /**
     * Simulate post data
     *
     * @param array $listing
     */
    public static function simulatePostData($listing = array())
    {
        global $config;

        $id = $listing['ID'];
        $options = $GLOBALS['rlDb']->fetch('*', array('Listing_ID' => $id), null, 1, 'shc_listing_options', 'row');

        $_POST['fshc'] = array(
            'shc_mode' => $listing['shc_mode'],
            'shc_quantity' => $listing['shc_quantity'],
            'shc_available' => $listing['shc_available'],
            'shc_start_price' => $options['Start_price'],
            'shc_reserved_price' => $options['Reserved_price'],
            'shc_bid_step' => $options['Bid_step'],
            'shc_days' => $listing['shc_days'],
            'shc_weight' => $options['Weight'],
            'shc_use_system_shipping_config' => $options['Use_system_shipping_config'],
            'shc_shipping_price_type' => $options['Shipping_price_type'],
            'shc_shipping_price' => $options['Shipping_price'],
            'shc_shipping_fixed_prices' => unserialize(trim($options['Shipping_fixed_prices'])),
            'shc_package_type' => $options['Package_type'],
            'shc_handling_time' => $options['Handling_time'],
            'shc_dimensions' => unserialize(trim($options['Dimensions'])),
            'shc_commission' => $options['Commission'],
            'shc_shipping_discount' => $options['Shipping_discount'],
            'shc_shipping_discount_at' => $options['Shipping_discount_at'],
            'sys_exist_digital_product' => $options['Digital_product'],
            'digital' => $options['Digital'],
            'quantity_unlim' => $options['Quantity_unlim'],
            'digital_product' => $options['Digital_product'],
            'sys_exist_digital_product' => $options['Digital_product'],
            'shipping_method_fixed' => explode(',', $options['Shipping_method_fixed']),
        );

        $_POST['shipping'] = unserialize(trim($options['Shipping_options']));

        if ($config['shc_commission_add']) {
            $price = (float) $_POST['f'][$config['price_tag_field']]['value'] - (float) $options['Commission'];
            $_POST['f'][$config['price_tag_field']]['value'] = $price;
        }
    }

    /**
     * Calculate value of commission at item price
     *
     * @param  double $price
     * @param  bool $auction
     * @return double
     */
    public static function calculateCommission(&$price = 0, $auction = false)
    {
        global $config;

        if (!$price || !$config['shc_commission_enable']) {
            return 0;
        }

        $rate = (float) $config['shc_commission'];

        if ($config['shc_commission_type'] == 'percent') {
            $commission = round(($price * $rate) / 100, 2);
        } else {
            $commission = round($rate, 2);
        }

        $commission = number_format($commission, 2, '.', '');

        if ($config['shc_commission_add'] && !$auction) {
            $price = number_format(round($price + $commission, 2), 2, '.', '');
        } else {
            $price = number_format(round($price - $commission, 2), 2, '.', '');
        }

        return $commission;
    }

    /**
     * Prepare tabs on add/edit listing
     *
     * @since 3.1.0 - $listingType param added
     *
     * @param array $listingType - Listing type data
     */
    public static function prepareTabs($listingType)
    {
        global $lang, $config, $rlSmarty;

        $tabs_source = array(
            'auction' => array(
                'module' => 'auction',
                'name' => $lang['shc_auction'],
                'status' => $config['shc_module_auction'] && $listingType['shc_auction'] ? true : false,
            ),
            'fixed' => array(
                'module' => 'fixed',
                'name' => $lang['shc_mode_fixed'],
                'status' => $config['shc_module'] && $listingType['shc_module'] ? true : false,
            ),
            'listing' => array(
                'module' => 'listing',
                'name' => $lang['shc_mode_listing'],
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

        $rlSmarty->assign_by_ref('shcTabs', $tabs);

        $currency = new Currency();
        $systemCurrencyKey = $currency->getSystemCurrencyKey();
        $rlSmarty->assign('systemCurrencyKey', $systemCurrencyKey);

        $_POST['fshc']['shipping_method_fixed'] = is_array($_POST['fshc']['shipping_method_fixed'])
        ? $_POST['fshc']['shipping_method_fixed']
        : [];
    }
}
