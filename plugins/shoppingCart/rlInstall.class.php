<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLINSTALL.CLASS.PHP
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

use \ShoppingCart\Currency;

class rlInstall
{
    /**
     * Install plugin to software
     */
    public function install()
    {
        global $rlDb, $languages;

        set_time_limit(0);

        $rlDb->createTable('shc_orders', "
            `ID` int(11) NOT NULL auto_increment,
            `Type` enum('shopping','auction') NOT NULL default 'shopping',
            `Item_ID` int(11) NOT NULL default '0',
            `Txn_ID` varchar(50) NOT NULL default '',
            `Order_key` varchar(50) NOT NULL default '',
            `Total`  double NOT NULL default '0',
            `Total_payout`  double NOT NULL default '0',
            `Dealer_ID`  int(11) NOT NULL default '0',
            `Buyer_ID` int(11) NOT NULL default '0',
            `Date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `Pay_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `Status` enum('paid','unpaid','pending','canceled') NOT NULL default 'unpaid',
            `Shipping_status` enum('pending','processing','shipped','declined','open','delivered') NOT NULL default 'pending',
            `Shipping_price` double NOT NULL default '0',
            `Shipping_options` text NOT NULL,
            `Commission`  double NOT NULL default '0',
            `Commission_total`  double NOT NULL default '0',
            `Tracking_number` varchar(50) NOT NULL default '',
            `location_level1` varchar(50) NOT NULL default '',
            `location_level2` varchar(100) NOT NULL default '',
            `location_level3` varchar(100) NOT NULL default '',
            `Cash` enum('0','1') NOT NULL default '0',
            `Bank_transfer` enum('0','1') NOT NULL default '0',
            `zip` varchar(30) NOT NULL default '',
            `address` varchar(255) NOT NULL default '',
            `Escrow` enum('0','1') NOT NULL default '0',
            `Escrow_status` enum('pending', 'confirmed', 'canceled') NOT NULL default 'pending',
            `Escrow_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `Deal_ID` varchar(150) NOT NULL default '',
            `Payout_ID` varchar(150) NOT NULL default '',
            `Refund_ID` varchar(150) NOT NULL default '',
            `Refund_reason` text NOT NULL,
            PRIMARY KEY (`ID`),
            KEY `Item_ID` (`Item_ID`),
            KEY `Dealer_ID` (`Dealer_ID`),
            KEY `Buyer_ID` (`Buyer_ID`)
        ");

        $rlDb->createTable('shc_order_details', "
            `ID` int(11) NOT NULL auto_increment,
            `Order_key` varchar(50) NOT NULL default '',
            `Order_ID` int(11) NOT NULL default '0',
            `Dealer_ID` int(11) NOT NULL default '0',
            `Buyer_ID` int(11) NOT NULL default '0',
            `Item_ID` int(11) NOT NULL default '0',
            `Item` varchar(255) NOT NULL default '',
            `Price` double NOT NULL default '0',
            `Quantity` int(11) NOT NULL default '1',
            `Date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `Status` enum('active','completed','deleted') NOT NULL default 'active',
            `Shipping_item_options` text NOT NULL,
            `Quote` text NOT NULL,
            `Free_shipping` enum('0','1') NOT NULL default '0',
            `Image` mediumblob NOT NULL,
            `Digital` enum('0','1') NOT NULL default '0',
            PRIMARY KEY (`ID`),
            KEY `Order_ID` (`Order_ID`),
            KEY `Order_key` (`Order_key`),
            KEY `Dealer_ID` (`Dealer_ID`),
            KEY `Buyer_ID` (`Buyer_ID`)
        ");

        $rlDb->createTable('shc_bids', "
            `ID` int(11) NOT NULL auto_increment,
            `Item_ID` int(11) NOT NULL default '0',
            `Dealer_ID` int(11) NOT NULL default '0',
            `Buyer_ID` int(11) NOT NULL default '0',
            `Number` int(11) NOT NULL default '0',
            `Total` double NOT NULL default '0',
            `Date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (`ID`),
            KEY `Item_ID` (`Item_ID`),
            KEY `Dealer_ID` (`Dealer_ID`),
            KEY `Buyer_ID` (`Buyer_ID`)
        ");

        $rlDb->createTable('shc_shipping_methods', "
            `ID` int(11) NOT NULL auto_increment,
            `Key` varchar(50) NOT NULL default '',
            `Status` enum('active','approval') NOT NULL default 'active',
            `Type` enum('online','offline') NOT NULL default 'online',
            `Services` TEXT NOT NULL,
            `Settings` MEDIUMTEXT NOT NULL,
            `Test_mode` enum('0','1') NOT NULL default '0',
            PRIMARY KEY (`ID`)
        ");

        $rlDb->createTable('shc_account_settings', "
            `ID` int(11) NOT NULL auto_increment,
            `Account_ID` INT DEFAULT '0' NOT NULL,
            `paypal_email` VARCHAR(100) NOT NULL,
            `paypal_enable` ENUM('0','1') NOT NULL DEFAULT '0',
            `2co_id` VARCHAR(100) NOT NULL,
            `2co_secret_word` VARCHAR(150) NOT NULL,
            `2co_secret_key` VARCHAR(150) NOT NULL,
            `2co_enable` ENUM('0','1') NOT NULL DEFAULT '0',
            `allow_cash` ENUM('0','1') NOT NULL DEFAULT '0',
            `Shipping` MEDIUMTEXT NOT NULL,
            PRIMARY KEY (`ID`),
            KEY `Account_ID` (`Account_ID`)
        ");

        $rlDb->createTable('shc_listing_options', "
            `ID` int(11) NOT NULL auto_increment,
            `Listing_ID` INT DEFAULT '0' NOT NULL,
            `Start_price` DOUBLE DEFAULT '0' NOT NULL,
            `Reserved_price` DOUBLE DEFAULT '0' NOT NULL,
            `Bid_step` DOUBLE DEFAULT '0' NOT NULL,
            `Max_bid` DOUBLE DEFAULT '0' NOT NULL,
            `Weight` DOUBLE DEFAULT '0' NOT NULL,
            `End_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            `Auction_status` ENUM('active', 'closed') DEFAULT 'active' NOT NULL,
            `Auction_won` VARCHAR(50) NOT NULL,
            `Shipping_options` MEDIUMTEXT NOT NULL,
            `Package_type` VARCHAR(50) DEFAULT '' NOT NULL,
            `Dimensions` VARCHAR(150) DEFAULT '' NOT NULL,
            `Handling_time` VARCHAR(50) DEFAULT '' NOT NULL,
            `Shipping_price_type` ENUM('free', 'fixed', 'calculate') DEFAULT 'free' NOT NULL,
            `Shipping_price` DOUBLE DEFAULT '0' NOT NULL,
            `Shipping_fixed_prices` TEXT NOT NULL,
            `Shipping_discount` DOUBLE DEFAULT '0' NOT NULL,
            `Shipping_discount_at` INT(4) DEFAULT '0' NOT NULL,
            `Use_system_shipping_config` ENUM('0', '1') DEFAULT '0' NOT NULL,
            `Commission` DOUBLE DEFAULT '0' NOT NULL,
            `Digital` ENUM('0','1') NOT NULL default '0',
            `Quantity_unlim` ENUM('0','1') NOT NULL default '0',
            `Digital_product` VARCHAR(100) NOT NULL,
            `Shipping_method_fixed` VARCHAR(20) NOT NULL,
            `Quantity_real` int(11) NOT NULL default '1',
            `Escrow` enum('0','1') NOT NULL default '0',
            PRIMARY KEY (`ID`),
            KEY `Listing_ID` (`Listing_ID`)
        ");

        $rlDb->addColumnsToTable(
            array(
                'shc_mode' => "ENUM('auction', 'fixed', 'listing') DEFAULT 'listing' NOT NULL",
                'shc_quantity' => "INT DEFAULT '0' NOT NULL",
                'shc_available' => "ENUM( '0', '1' ) DEFAULT '0' NOT NULL",
                'shc_start_time' => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
                'shc_days' => "INT(4) DEFAULT '0' NOT NULL",
                'shc_total_bids' => "INT(4) DEFAULT '0' NOT NULL",
                'shc_auction_status' => "ENUM('active', 'closed') DEFAULT 'active' NOT NULL",
            ),
            'listings'
        );

        $rlDb->createTable('shc_shipping_fields', "
            `ID` int(11) NOT NULL auto_increment,
            `Key` VARCHAR(50) NOT NULL,
            `Type` ENUM('bool','text','textarea','number','phone','date','mixed','price','select','radio','checkbox','file','accept','image') NOT NULL DEFAULT 'text',
            `Default` VARCHAR(255) NOT NULL,
            `Values` MEDIUMTEXT NOT NULL,
            `Condition` VARCHAR(50) NOT NULL,
            `Multilingual` ENUM('0','1') NOT NULL DEFAULT '0',
            `Required` ENUM('0','1') NOT NULL DEFAULT '0',
            `Map` ENUM('0','1') NOT NULL DEFAULT '0',
            `Contact` ENUM('0','1') NOT NULL DEFAULT '0',
            `Add_page` ENUM('0','1') NOT NULL DEFAULT '0',
            `Details_page` ENUM('0','1') NOT NULL DEFAULT '0',
            `Opt1` ENUM('0','1') NOT NULL DEFAULT '0',
            `Opt2` VARCHAR(255) NOT NULL,
            `Opt3` MEDIUMTEXT NOT NULL,
            `Status` ENUM('active','approval','trash') NOT NULL DEFAULT 'active',
            `Readonly` ENUM('0','1') NOT NULL DEFAULT '0',
            `Hidden` ENUM('0','1') NOT NULL DEFAULT '0',
            `Autocomplete` ENUM('0','1') NOT NULL DEFAULT '0',
            `Google_autocomplete` VARCHAR(50) NOT NULL,
            PRIMARY KEY (`ID`)
        ");

        $rlDb->createTable('shc_shipping_form', "
            `ID` int(11) NOT NULL auto_increment,
            `Position` INT(4) NOT NULL DEFAULT '0',
            `Category_ID` INT(4) NOT NULL DEFAULT '0',
            `Group_ID` INT(4) NOT NULL DEFAULT '0',
            `Field_ID` INT(4) NOT NULL DEFAULT '0',
            PRIMARY KEY (`ID`)
        ");

        // insert shipping methods
        $dhl = [
            'client_id' => [
                'type' => 'text',
                'key' => 'client_id',
            ],
            'client_secret' => [
                'type' => 'text',
                'key' => 'client_secret',
            ],
            'pickup_account' => [
                'type' => 'text',
                'key' => 'pickup_account',
            ],
            'length_type' => [
                'type' => 'radio',
                'key' => 'length_type',
                'source' => 'dhlLengthTypes',
                'phrase_key' => 'shc_ups_length_type',
            ],
        ];

        $ups = [
            'api_key' => [
                'type' => 'text',
                'key' => 'api_key',
            ],
            'username' => [
                'type' => 'text',
                'key' => 'username',
            ],
            'password' => [
                'type' => 'text',
                'key' => 'password',
            ],
            'package_types' => [
                'type' => 'text',
                'key' => 'package_types',
            ],
            'quote_type' => [
                'type' => 'select',
                'key' => 'quote_type',
                'source' => 'upsQuoteTypes',
            ],
            'length_type' => [
                'type' => 'radio',
                'key' => 'length_type',
                'source' => 'upsLengthTypes',
            ],
            'insurance' => [
                'type' => 'bool',
                'key' => 'insurance',
            ],
            'classification' => [
                'type' => 'select',
                'key' => 'classification',
                'source' => 'upsClassifications',
            ],
        ];

        $fedex = [
            'api_key' => [
                'type' => 'text',
                'key' => 'api_key',
            ],
            'account' => [
                'type' => 'text',
                'key' => 'account',
            ],
            'password' => [
                'type' => 'text',
                'key' => 'password',
            ],
            'meter' => [
                'type' => 'text',
                'key' => 'meter',
            ],
            'rate_type' => [
                'type' => 'select',
                'key' => 'rate_type',
                'source' => 'fedexRateTypes',
            ],
            'length_type' => [
                'type' => 'radio',
                'key' => 'length_type',
                'source' => 'fedexLengthTypes',
                'phrase_key' => 'shc_ups_length_type',
            ],
        ];

        $usps = [
            'userid' => [
                'type' => 'text',
                'key' => 'userid',
            ],
        ];

        $shipping_methods = array(
            array('Key' => 'ups', 'Type' => 'online', 'Status' => 'approval', 'Settings' => serialize($ups)),
            array('Key' => 'dhl', 'Type' => 'online', 'Status' => 'approval', 'Settings' => serialize($dhl)),
            array('Key' => 'fedex', 'Type' => 'online', 'Status' => 'approval', 'Settings' => serialize($fedex)),
            array('Key' => 'usps', 'Type' => 'online', 'Status' => 'approval', 'Settings' => serialize($usps)),
        );

        $rlDb->insert($shipping_methods, 'shc_shipping_methods');

        $rlDb->addColumnsToTable(
            array(
                'shc_module' => "ENUM('0', '1') NOT NULL DEFAULT '1' AFTER `Status`",
                'shc_auction' => "ENUM('0', '1') NOT NULL DEFAULT '1' AFTER `shc_module`",
            ),
            'listing_types'
        );

        $rlDb->addColumnToTable(
            'shc_module',
            "ENUM( '0', '1' ) NOT NULL DEFAULT '1' AFTER `Status`",
            'membership_plans'
        );

        $rlDb->addColumnToTable(
            'Parallel',
            "ENUM( '0', '1' ) NOT NULL DEFAULT '0' AFTER `Status`",
            'payment_gateways'
        );

        $rlDb->addColumnToTable(
            'is_commission',
            "ENUM( '0', '1' ) NOT NULL DEFAULT '0'",
            'transactions'
        );

        $rlDb->addColumnToTable(
            'Plugin',
            "VARCHAR(30) NOT NULL",
            'admin_controllers'
        );

        $controller = array(
            'Parent_ID' => 25,
            'Position' => 2,
            'Key' => 'shopping_cart',
            'Controller' => 'shopping_cart',
            'Vars' => 'module=shipping_form',
            'Plugin' => 'shoppingCart',
        );

        $rlDb->insertOne($controller, 'admin_controllers');

        if ($languages) {
            foreach ((array) $languages as $lKey => $lValue) {
                $insert = array(
                    'Code' => $lValue['Code'],
                    'Module' => 'admin',
                    'Key' => 'admin_controllers+name+shopping_cart',
                    'Value' => 'Shipping Form',
                    'Plugin' => 'shoppingCart',
                );

                $rlDb->insertOne($insert, 'lang_keys');

                foreach ($shipping_methods as $shKey => $shVal) {
                    $insert = array(
                        'Code' => $lValue['Code'],
                        'Module' => 'common',
                        'Key' => 'shipping_methods+name+' . $shVal['Key'],
                        'Value' => ucfirst($shVal['Key']),
                        'Plugin' => 'shoppingCart',
                    );

                    $rlDb->insertOne($insert, 'lang_keys');
                }
            }
        }

        $sql = "INSERT INTO `{db_prefix}shc_shipping_fields` (`ID`, `Key`, `Type`, `Default`, `Values`, `Condition`, `Multilingual`, `Required`, `Map`, `Contact`, `Add_page`, `Details_page`, `Opt1`, `Opt2`, `Status`, `Readonly`) VALUES
            (1, 'location_level1', 'select', '', '', 'countries', '0', '0', '0', '0', '1', '1', '0', '', 'active', '1'),
            (2, 'location_level2', 'text', '', '150', '', '', '0', '0', '0', '1', '1', '0', '', 'active', '1'),
            (3, 'location_level3', 'text', '', '150', '', '', '0', '0', '0', '1', '1', '0', '', 'active', '1'),
            (4, 'zip', 'text', '', '150', '', '', '0', '0', '0', '1', '1', '0', '', 'active', '1'),
            (5, 'address', 'text', '', '255', '', '', '0', '0', '0', '1', '1', '0', '', 'active', '1');";
        $rlDb->query($sql);

        $sql = "INSERT INTO `{db_prefix}shc_shipping_form` (`ID`, `Position`, `Category_ID`, `Group_ID`, `Field_ID`) VALUES
            (1, 1, 1, 0, 1),
            (2, 2, 1, 0, 2),
            (3, 3, 1, 0, 3),
            (4, 4, 1, 0, 4),
            (5, 5, 1, 0, 5);";
        $rlDb->query($sql);

        // get statistic by listings
        $sql = "SELECT COUNT(`ID`) AS `total`, MAX(`ID`) AS `lastID` FROM `{db_prefix}listings` WHERE `Status` = 'active'";
        $listingsInfo = $rlDb->getRow($sql);

        $updateConfig[] = array(
            'fields' => array('Default' => $listingsInfo['total']),
            'where' => array('Key' => 'shc_count_exists_listings'),
        );
        $updateConfig[] = array(
            'fields' => array('Default' => $listingsInfo['lastID']),
            'where' => array('Key' => 'shc_update_listings'),
        );

        $rlDb->update($updateConfig, 'config');

        $this->checkCurrency();
    }

    /**
     * Uninstall plugin to software
     */
    public function uninstall()
    {
        global $rlDb;

        // delete listing fields
        $sql = "SHOW COLUMNS FROM `{db_prefix}listings` WHERE `Field` RLIKE 'shc_(.*)$'";
        $lfields = $rlDb->getAll($sql);

        if (!empty($lfields)) {
            foreach ($lfields as $lfKey => $lfVal) {
                if ($lfVal['Field']) {
                    $rlDb->dropColumnFromTable($lfVal['Field'], 'listings');
                }
            }
        }

        // delete tables
        $rlDb->dropTables(array(
            'shc_orders',
            'shc_order_details',
            'shc_bids',
            'shc_shipping_methods',
            'shc_account_settings',
            'shc_listing_options',
            'shc_shipping_fields',
            'shc_shipping_form',
        ));

        $rlDb->dropColumnFromTable('shc_module', 'listing_types');
        $rlDb->dropColumnFromTable('is_commission', 'transactions');
        $rlDb->dropColumnFromTable('Plugin', 'admin_controllers');
        $rlDb->dropColumnFromTable('shc_module', 'membership_plans');

        // delete admin_controllers
        $sql = "DELETE FROM `{db_prefix}admin_controllers` WHERE `Key` = 'shopping_cart'";
        $rlDb->query($sql);
    }

    /**
     * Update plugin
     *
     * @param mixed $version
     */
    public function update($version = false)
    {
        global $rlDb;

        if (!$version) {
            return;
        }

        switch ($version) {
            case '2.0.0':
                // create table shipping methods
                $rlDb->query("DROP TABLE IF EXISTS `" . RL_DBPREFIX . "shc_shipping_methods`;");
                $sql = "CREATE TABLE `" . RL_DBPREFIX . "shc_shipping_methods` (
                    `ID` int(11) NOT NULL auto_increment,
                    `Key` varchar(50) NOT NULL default '',
                    `Status` enum('active','approval') NOT NULL default 'active',
                    `Type` enum('online','offline') NOT NULL default 'online',
                    `Services` text NOT NULL,
                  PRIMARY KEY (`ID`)
                ) DEFAULT CHARSET=utf8";

                $rlDb->query($sql);

                $sql = "ALTER TABLE `" . RL_DBPREFIX . "shc_order_details` ADD `Shipping_item_options` MEDIUMTEXT NOT NULL;";
                $rlDb->query($sql);

                // delete field from order table
                $sql = "ALTER TABLE `" . RL_DBPREFIX . "shc_orders` DROP `UPSService`";
                $rlDb->query($sql);

                $sql = "ALTER TABLE `" . RL_DBPREFIX . "shc_orders` DROP `Weight`";
                $rlDb->query($sql);

                $sql = "ALTER TABLE `" . RL_DBPREFIX . "shc_orders` DROP `Shipping_method`";
                $rlDb->query($sql);

                $sql = "ALTER TABLE `" . RL_DBPREFIX . "shc_orders` CHANGE COLUMN `pStatus` `Status` enum('paid','unpaid','pending') NOT NULL default 'unpaid';";
                $rlDb->query($sql);

                $sql = "ALTER TABLE `" . RL_DBPREFIX . "shc_orders` DROP `Length`";
                $rlDb->query($sql);

                $sql = "ALTER TABLE `" . RL_DBPREFIX . "shc_orders` DROP `Width`";
                $rlDb->query($sql);

                $sql = "ALTER TABLE `" . RL_DBPREFIX . "shc_orders` DROP `Height`";
                $rlDb->query($sql);

                // delete field from order details table
                $sql = "ALTER TABLE `" . RL_DBPREFIX . "shc_order_details` DROP `Delivery`";
                $rlDb->query($sql);

                // insert shipping methods
                $shipping_methods[] = array('Key' => 'UPS', 'Type' => 'online');
                $shipping_methods[] = array('Key' => 'DHL', 'Type' => 'online');
                $shipping_methods[] = array('Key' => 'fedex', 'Type' => 'online');
                $shipping_methods[] = array('Key' => 'USPS', 'Type' => 'online');
                $shipping_methods[] = array('Key' => 'courier', 'Type' => 'offline');
                $shipping_methods[] = array('Key' => 'pickup', 'Type' => 'offline');

                $rlDb->insert($shipping_methods, 'shc_shipping_methods');

                break;
        }
    }

    /**
     * Check system currency
     */
    public function checkCurrency()
    {
        $currency = new \ShoppingCart\Currency();
        $currency->addSystemCurrency();
        $currency->importRates();
    }

    /**
     * Update to 3.0.0
     */
    public function update300()
    {
        global $rlDb, $languages;

        $filesystem = new \Symfony\Component\Filesystem\Filesystem;
        $filesystem->mirror(RL_UPLOAD . 'shoppingCart/vendor', RL_PLUGINS . 'shoppingCart/vendor');

        rlShoppingCart::boot();

        $rlDb->addColumnsToTable(
            array(
                'Tracking_number' => "varchar(50) NOT NULL default ''",
                'location_level1' => "varchar(50) NOT NULL default ''",
                'location_level2' => "varchar(100) NOT NULL default ''",
                'location_level3' => "varchar(100) NOT NULL default ''",
                'Cash' => "enum('0','1') NOT NULL default '0'",
                'Bank_transfer' => "enum('0','1') NOT NULL default '0'",
            ),
            'shc_orders'
        );

        $rlDb->dropColumnsFromTable(
            array(
                'Country',
                'State',
                'Zip_code',
                'City',
                'Name',
                'Mail',
                'Phone',
                'Vat_no',
                'Comment'
            ),
            'shc_orders'
        );

        $rlDb->addColumnsToTable(
            array(
                'Image' => "mediumblob NOT NULL",
                'Digital' => "enum('0','1') NOT NULL default '0'",
            ),
            'shc_order_details'
        );

        $rlDb->addColumnsToTable(
            array(
                'Settings' => "MEDIUMTEXT NOT NULL",
                'Test_mode' => "enum('0','1') NOT NULL default '0'",
            ),
            'shc_shipping_methods'
        );

        $rlDb->createTable('shc_account_settings', "
            `ID` int(11) NOT NULL auto_increment,
            `Account_ID` INT DEFAULT '0' NOT NULL,
            `paypal_email` VARCHAR(100) NOT NULL,
            `paypal_enable` ENUM('0','1') NOT NULL DEFAULT '0',
            `2co_id` VARCHAR(100) NOT NULL,
            `2co_secret_word` VARCHAR(150) NOT NULL,
            `2co_secret_key` VARCHAR(150) NOT NULL,
            `2co_enable` ENUM('0','1') NOT NULL DEFAULT '0',
            `allow_cash` ENUM('0','1') NOT NULL DEFAULT '0',
            `Shipping` MEDIUMTEXT NOT NULL,
            PRIMARY KEY (`ID`),
            KEY `Account_ID` (`Account_ID`)
        ");

        $rlDb->createTable('shc_listing_options', "
            `ID` int(11) NOT NULL auto_increment,
            `Listing_ID` INT DEFAULT '0' NOT NULL,
            `Start_price` DOUBLE DEFAULT '0' NOT NULL,
            `Reserved_price` DOUBLE DEFAULT '0' NOT NULL,
            `Bid_step` DOUBLE DEFAULT '0' NOT NULL,
            `Max_bid` DOUBLE DEFAULT '0' NOT NULL,
            `Weight` DOUBLE DEFAULT '0' NOT NULL,
            `End_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            `Auction_status` ENUM('active', 'closed') DEFAULT 'active' NOT NULL,
            `Auction_won` VARCHAR(50) NOT NULL,
            `Shipping_options` MEDIUMTEXT NOT NULL,
            `Package_type` VARCHAR(50) DEFAULT '' NOT NULL,
            `Dimensions` VARCHAR(150) DEFAULT '' NOT NULL,
            `Handling_time` VARCHAR(50) DEFAULT '' NOT NULL,
            `Shipping_price_type` ENUM('free', 'fixed', 'calculate') DEFAULT 'free' NOT NULL,
            `Shipping_price` DOUBLE DEFAULT '0' NOT NULL,
            `Shipping_fixed_prices` TEXT NOT NULL,
            `Shipping_discount` DOUBLE DEFAULT '0' NOT NULL,
            `Shipping_discount_at` INT(4) DEFAULT '0' NOT NULL,
            `Use_system_shipping_config` ENUM('0', '1') DEFAULT '0' NOT NULL,
            `Commission` DOUBLE DEFAULT '0' NOT NULL,
            `Digital` ENUM('0','1') NOT NULL default '0',
            `Quantity_unlim` ENUM('0','1') NOT NULL default '0',
            `Digital_product` VARCHAR(100) NOT NULL,
            `Shipping_method_fixed` VARCHAR(20) NOT NULL,
            `Quantity_real` int(11) NOT NULL default '1',
            PRIMARY KEY (`ID`),
            KEY `Listing_ID` (`Listing_ID`)
        ");

        $rlDb->createTable('shc_shipping_fields', "
            `ID` int(11) NOT NULL auto_increment,
            `Key` VARCHAR(50) NOT NULL,
            `Type` ENUM('bool','text','textarea','number','phone','date','mixed','price','select','radio','checkbox','file','accept','image') NOT NULL DEFAULT 'text',
            `Default` VARCHAR(255) NOT NULL,
            `Values` MEDIUMTEXT NOT NULL,
            `Condition` VARCHAR(50) NOT NULL,
            `Multilingual` ENUM('0','1') NOT NULL DEFAULT '0',
            `Required` ENUM('0','1') NOT NULL DEFAULT '0',
            `Map` ENUM('0','1') NOT NULL DEFAULT '0',
            `Contact` ENUM('0','1') NOT NULL DEFAULT '0',
            `Add_page` ENUM('0','1') NOT NULL DEFAULT '0',
            `Details_page` ENUM('0','1') NOT NULL DEFAULT '0',
            `Opt1` ENUM('0','1') NOT NULL DEFAULT '0',
            `Opt2` VARCHAR(255) NOT NULL,
            `Status` ENUM('active','approval','trash') NOT NULL DEFAULT 'active',
            `Readonly` ENUM('0','1') NOT NULL DEFAULT '0',
            `Google_autocomplete` VARCHAR(50) NOT NULL,
            PRIMARY KEY (`ID`)
        ");

        $rlDb->createTable('shc_shipping_form', "
            `ID` int(11) NOT NULL auto_increment,
            `Position` INT(4) NOT NULL DEFAULT '0',
            `Category_ID` INT(4) NOT NULL DEFAULT '0',
            `Group_ID` INT(4) NOT NULL DEFAULT '0',
            `Field_ID` INT(4) NOT NULL DEFAULT '0',
            PRIMARY KEY (`ID`)
        ");

        // insert shipping methods
        $dhl = [
            'client_id' => [
                'type' => 'text',
                'key' => 'client_id',
            ],
            'client_secret' => [
                'type' => 'text',
                'key' => 'client_secret',
            ],
            'pickup_account' => [
                'type' => 'text',
                'key' => 'pickup_account',
            ],
            'length_type' => [
                'type' => 'radio',
                'key' => 'length_type',
                'source' => 'dhlLengthTypes',
                'phrase_key' => 'shc_ups_length_type',
            ],
        ];

        $ups = [
            'api_key' => [
                'type' => 'text',
                'key' => 'api_key',
            ],
            'username' => [
                'type' => 'text',
                'key' => 'username',
            ],
            'password' => [
                'type' => 'text',
                'key' => 'password',
            ],
            'package_types' => [
                'type' => 'text',
                'key' => 'package_types',
            ],
            'quote_type' => [
                'type' => 'select',
                'key' => 'quote_type',
                'source' => 'upsQuoteTypes',
            ],
            'length_type' => [
                'type' => 'radio',
                'key' => 'length_type',
                'source' => 'upsLengthTypes',
            ],
            'insurance' => [
                'type' => 'bool',
                'key' => 'insurance',
            ],
            'classification' => [
                'type' => 'select',
                'key' => 'classification',
                'source' => 'upsClassifications',
            ],
        ];

        $fedex = [
            'api_key' => [
                'type' => 'text',
                'key' => 'api_key',
            ],
            'account' => [
                'type' => 'text',
                'key' => 'account',
            ],
            'password' => [
                'type' => 'text',
                'key' => 'password',
            ],
            'meter' => [
                'type' => 'text',
                'key' => 'meter',
            ],
            'rate_type' => [
                'type' => 'select',
                'key' => 'rate_type',
                'source' => 'fedexRateTypes',
            ],
            'length_type' => [
                'type' => 'radio',
                'key' => 'length_type',
                'source' => 'fedexLengthTypes',
                'phrase_key' => 'shc_ups_length_type',
            ],
        ];

        $usps = [
            'userid' => [
                'type' => 'text',
                'key' => 'userid',
            ],
        ];

        $shipping_methods = array(
            array('Key' => 'ups', 'Type' => 'online', 'Status' => 'approval', 'Settings' => serialize($ups)),
            array('Key' => 'dhl', 'Type' => 'online', 'Status' => 'approval', 'Settings' => serialize($dhl)),
            array('Key' => 'fedex', 'Type' => 'online', 'Status' => 'approval', 'Settings' => serialize($fedex)),
            array('Key' => 'usps', 'Type' => 'online', 'Status' => 'approval', 'Settings' => serialize($usps)),
        );

        $rlDb->query("TRUNCATE TABLE {db_prefix}shc_shipping_methods");
        $rlDb->insert($shipping_methods, 'shc_shipping_methods');

        $rlDb->addColumnToTable(
            'shc_module',
            "ENUM( '0', '1' ) NOT NULL DEFAULT '1' AFTER `Status`",
            'membership_plans'
        );

        $rlDb->addColumnToTable(
            'shc_auction',
            "ENUM( '0', '1' ) NOT NULL DEFAULT '1' AFTER `Status`",
            'listing_types'
        );

        $rlDb->addColumnToTable(
            'Parallel',
            "ENUM( '0', '1' ) NOT NULL DEFAULT '0' AFTER `Status`",
            'payment_gateways'
        );

        $rlDb->addColumnToTable(
            'is_commission',
            "ENUM( '0', '1' ) NOT NULL DEFAULT '0'",
            'transactions'
        );

        $rlDb->addColumnToTable(
            'Plugin',
            "VARCHAR(30) NOT NULL",
            'admin_controllers'
        );

        $controller = array(
            'Parent_ID' => 25,
            'Position' => 2,
            'Key' => 'shopping_cart',
            'Controller' => 'shopping_cart',
            'Vars' => 'module=shipping_form',
            'Plugin' => 'shoppingCart',
        );

        $rlDb->insertOne($controller, 'admin_controllers');

        if ($languages) {
            foreach ((array) $languages as $lKey => $lValue) {
                $insert = array(
                    'Code' => $lValue['Code'],
                    'Module' => 'admin',
                    'Key' => 'admin_controllers+name+shopping_cart',
                    'Value' => 'Shipping Form',
                    'Plugin' => 'shoppingCart',
                );

                $rlDb->insertOne($insert, 'lang_keys');

                foreach ($shipping_methods as $shKey => $shVal) {
                    $insert = array(
                        'Code' => $lValue['Code'],
                        'Module' => 'common',
                        'Key' => 'shipping_methods+name+' . $shVal['Key'],
                        'Value' => ucfirst($shVal['Key']),
                        'Plugin' => 'shoppingCart',
                    );

                    $rlDb->insertOne($insert, 'lang_keys');
                }
            }
        }

        $sql = "INSERT INTO `{db_prefix}shc_shipping_fields` (`ID`, `Key`, `Type`, `Default`, `Values`, `Condition`, `Multilingual`, `Required`, `Map`, `Contact`, `Add_page`, `Details_page`, `Opt1`, `Opt2`, `Status`, `Readonly`) VALUES
            (1, 'location_level1', 'select', '', '', 'countries', '0', '0', '0', '0', '1', '1', '0', '', 'active', '1'),
            (2, 'location_level2', 'text', '', '150', '', '', '0', '0', '0', '1', '1', '0', '', 'active', '1'),
            (3, 'location_level3', 'text', '', '150', '', '', '0', '0', '0', '1', '1', '0', '', 'active', '1'),
            (4, 'zip', 'text', '', '150', '', '', '0', '0', '0', '1', '1', '0', '', 'active', '1'),
            (5, 'address', 'text', '', '255', '', '', '0', '0', '0', '1', '1', '0', '', 'active', '1');";
        $rlDb->query($sql);

        $sql = "INSERT INTO `{db_prefix}shc_shipping_form` (`ID`, `Position`, `Category_ID`, `Group_ID`, `Field_ID`) VALUES
            (1, 1, 1, 0, 1),
            (2, 2, 1, 0, 2),
            (3, 3, 1, 0, 3),
            (4, 4, 1, 0, 4),
            (5, 5, 1, 0, 5);";
        $rlDb->query($sql);

        $this->checkCurrency();

        // outdated files
        $deleteFiles = [
            'account_settings.tpl',
            'account_settings_responsive_42.tpl',
            'add_cart_browse.tpl',
            'admin/auction.inc.php',
            'admin/auction.tpl',
            'admin/auction_details.tpl',
            'admin/configs.inc.php',
            'admin/configs.tpl',
            'admin/fields.tpl',
            'admin/footer.tpl',
            'admin/listing_type.tpl',
            'admin/order_details.tpl',
            'admin/price_format_form.tpl',
            'admin/shipping.inc.php',
            'admin/shipping.tpl',
            'auction_details.tpl',
            'auction_details_live.tpl',
            'auction_details_live_responsive_42.tpl',
            'auction_details_responsive_42.tpl',
            'auction_payment_responsive_42.tpl',
            'bank_transfer_listing_details.tpl',
            'bid_history.tpl',
            'bid_history_responsive_42.tpl',
            'bids.tpl',
            'bids_responsive_42.tpl',
            'box.tpl',
            'box_container.tpl',
            'box_responsive_42.tpl',
            'cart.tpl',
            'cart_items_responsive_42.tpl',
            'cart_responsive_42.tpl',
            'cron.php',
            'fields.tpl',
            'fields_responsive_42.tpl',
            'footer.tpl',
            'gateways_44.tpl',
            'items.tpl',
            'items_responsive_42.tpl',
            'listing.tpl',
            'listing_details.tpl',
            'listing_details_responsive_42.tpl',
            'listing_details_responsive_44.tpl',
            'my_auctions_responsive_42.tpl',
            'my_cart_block.tpl',
            'my_items_sold_responsive_42.tpl',
            'my_listings.tpl',
            'my_purchases_responsive_42.tpl',
            'my_shopping_cart_responsive_42.tpl',
            'order_details.tpl',
            'order_details_print.tpl',
            'order_details_responsive_42.tpl',
            'price_format_form.tpl',
            'renew_auction.tpl',
            'renew_auction_responsive_42.tpl',
            'rlAuction.class.php',
            'rlAuctionPayment.class.php',
            'rlCartPayment.class.php',
            'rlShipping.class.php',
            'shipping/add_listing_fedex.tpl',
            'shipping/add_listing_fedex_responsive_42.tpl',
            'shipping/add_listing_ups.tpl',
            'shipping/add_listing_ups_responsive_42.tpl',
            'shipping/add_listing_usps.tpl',
            'shipping/add_listing_usps_responsive_42.tpl',
            'shipping/ap_add_listing_fedex.tpl',
            'shipping/ap_add_listing_ups.tpl',
            'shipping/ap_add_listing_usps.tpl',
            'shipping/rlCourier.class.php',
            'shipping/rlDHL.class.php',
            'shipping/rlFedex.class.php',
            'shipping/rlFree.class.php',
            'shipping/rlPickup.class.php',
            'shipping/rlShippingMethod.class.php',
            'shipping/rlUPS.class.php',
            'shipping/rlUSPS.class.php',
            'shipping/rlXMLParser.class.php',
            'shipping/shipping_add_listing.tpl',
            'shipping/shipping_add_listing_responsive_42.tpl',
            'shipping/shipping_fields_fedex.tpl',
            'shipping/shipping_fields_fedex_responsive_42.tpl',
            'shipping/shipping_fields_ups.tpl',
            'shipping/shipping_fields_ups_responsive_42.tpl',
            'shipping/shipping_fields_usps.tpl',
            'shipping/shipping_fields_usps_responsive_42.tpl',
            'shipping/usps_settings_admin.tpl',
            'shipping_fields.tpl',
            'shipping_fields_responsive_42.tpl',
            'static/aStyle.css',
            'static/bids.png',
            'static/boats_seaman.css',
            'static/boats_seaman.png',
            'static/default.css',
            'static/default.png',
            'static/general_modern.css',
            'static/general_modern.png',
            'static/general_simple_blue.css',
            'static/general_simple_blue.png',
            'static/general_simple_green.css',
            'static/general_simple_green.png',
            'static/general_simple_red.css',
            'static/general_simple_red.png',
            'static/general_sky.css',
            'static/general_sky.png',
            'static/responsive_42.css',
            'static/responsive_42.png',
            'static/style.css',
            'print.inc.php',
            'print.tpl',
        ];

        foreach ($deleteFiles as $key => $value) {
            if (file_exists(RL_PLUGINS . 'shoppingCart/' . $value)) {
                unlink(RL_PLUGINS . 'shoppingCart/' . $value);
            }
        }

        $langKeys = [
            'config+name+shc_fedex_rate_type',
            'config+name+shc_fedex_packaging_type',
            'config+name+shc_fedex_dropoff_type',
            'config+name+shc_fedex_services',
            'config+name+shc_fedex_test_mode',
            'config+name+shc_fedex_meter',
            'config+name+shc_fedex_account',
            'config+name+shc_fedex_password',
            'config+name+shc_fedex_key',
            'config+name+shc_usps_international_services',
            'config+name+shc_usps_domestic_services',
            'config+name+shc_usps_machinable',
            'config+name+shc_usps_container',
            'config+name+shc_usps_size',
            'config+name+shc_usps_test_mode',
            'config+name+shc_usps_userid',
            'config+name+shc_width',
            'config+name+shc_height',
            'config+name+shc_length',
            'config+name+shc_length_type',
            'config+name+shc_weight_type',
            'config+des+shc_use_box',
            'config+name+shc_use_box',
            'config+name+shc_ups_test_mode',
            'config+name+shc_ups_insurance',
            'config+name+shc_ups_quote_type',
            'config+name+shc_ups_classification',
            'config+name+shc_ups_services',
            'config+name+shc_ups_origin',
            'config+name+shc_ups_package_types',
            'config+name+shc_ups_pickup_methods',
            'config+name+shc_ups_password',
            'config+name+shc_ups_key',
            'config+name+shc_ups_username',
            'config+name+shc_dhl_test_mode',
            'config+name+shc_dhl_password',
            'config+name+shc_dhl_site_id',
            'config+name+shc_listing_field_price',
            'config+name+shc_fields_position_type',
            'config+name+shc_fields_position',
            'shc_notice_orders_mass_deleted',
            'shc_do_you_want_delete_item',
            'shc_empty_shipping_methods',
            'shc_mail_type_postcard',
            'shc_mail_type_package_service',
            'shc_mail_type_parcel',
            'shc_mail_type_flat',
            'shc_mail_type_letter',
            'shc_ext_commission',
            'shc_free_shipping',
            'shopping',
            'auction',
            'shc_auction_start_date',
            'shc_use_system_shipping_config_des',
            'shc_use_system_shipping_config',
            'shc_country_not_define',
            'shc_shipping_price_type_des',
            'shc_shipping_price_type',
            'shc_configure_shipping_method',
            'shc_shipping_methdos',
            'shc_payment_gateway',
            'shc_use_another_payment_gatyeway',
            'shc_shipping_method_select',
            'free_shipping',
            'shc_shipping_cart_details',
            'shc_shipping_method_details',
            'shc_shipping_personal_details',
            'shc_shipping_location_details',
            'shc_package_details',
            'shc_orders_per_page',
            'shc_registration',
            'shc_login',
            'account_fields+name+shc_2co_secret_word',
            'account_fields+name+shc_2co_id',
            'shc_use_2co',
            'shc_2co',
            'account_fields+name+shc_paypal_email',
            'shc_use_paypal',
            'shc_paypal',
            'shc_ups_service',
            'shc_ups_package_type',
            'shc_ups_pickup_method',
            'shc_details',
            'shc_ups_services_help',
            'shc_ups_available_methods',
            'shc_auto_rate_period',
            'shc_ups_dimensions_help',
            'shc_ups_dimensions',
            'shc_ups_height',
            'shc_ups_width',
            'shc_ups_length',
            'ups_origin_other',
            'ups_length_in',
            'ups_length_cm',
            'ups_weight_lbs',
            'ups_weight_kgs',
            'shc_ups_test_mode',
            'shc_ups_weight_type',
            'shc_ups_quote_type_commercial',
            'shc_ups_quote_type_residential',
            'shc_ups_services',
            'shc_ups_password',
            'shc_ups_username',
            'shc_ups_key',
            'shc_settings_ups',
            'shc_ups_packaging_large_express_box',
            'shc_ups_packaging_medium_express_box',
            'shc_ups_packaging_small_express_box',
            'shc_ups_packaging_pallet',
            'shc_ups_packaging_10kg_box',
            'shc_ups_packaging_25kg_box',
            'shc_ups_packaging_express_box',
            'shc_ups_packaging_pak',
            'shc_ups_packaging_tube',
            'shc_ups_packaging_package',
            'shc_ups_packaging_letter',
            'shc_ups_packaging_unknown',
            'shc_ups_pickup_suggested_retail_rates',
            'shc_ups_pickup_air_service_center',
            'shc_ups_pickup_letter_center',
            'shc_ups_pickup_on_call_air',
            'shc_ups_pickup_one_time_pickup',
            'shc_ups_pickup_customer_counter',
            'shc_ups_pickup_regular_daily_pickup',
            'shc_shipping_location_notice',
            'shc_shipping_location',
            'shc_fedex_rate_type_none',
            'shc_fedex_rate_type_list',
            'shc_fedex_rate_type_account',
            'shc_shipping_settings',
            'shc_standard_overnight',
            'shc_usps_services_domestic',
            'shc_smart_post',
            'shc_same_day_city',
            'shc_same_day',
            'shc_priority_overnight',
            'shc_international_priority_freight',
            'shc_international_priority',
            'shc_international_first',
            'shc_international_economy_freight',
            'shc_international_economy',
            'shc_ground_home_delivery',
            'shc_first_overnight',
            'shc_fedex_next_day_mid_morning',
            'shc_fedex_next_day_freight',
            'shc_fedex_next_day_end_of_day',
            'shc_fedex_next_day_early_morning',
            'shc_fedex_next_day_afternoon',
            'shc_fedex_ground',
            'shc_fedex_freight_priority',
            'shc_fedex_freight_economy',
            'shc_fedex_first_freight',
            'shc_fedex_express_saver',
            'shc_fedex_distance_deferred',
            'shc_fedex_3_day_freight',
            'shc_fedex_2_day_freight',
            'shc_fedex_2_day_am',
            'shc_fedex_2_day',
            'shc_fedex_1_day_freight',
            'shc_europe_first_international_priority',
            'shc_station',
            'shc_request_courier',
            'shc_regular_pickup',
            'shc_drop_box',
            'shc_business_service_center',
            'shc_your_packaging',
            'shc_fedex_tube',
            'shc_fedex_small_box',
            'shc_fedex_pak',
            'shc_fedex_medium_box',
            'shc_fedex_large_box',
            'shc_fedex_extra_large_box',
            'shc_fedex_envelope',
            'shc_fedex_box',
            'shc_fedex_25kg_box',
            'shc_postcards',
            'shc_fedex_10kg_box',
            'shc_priority_mail_flat_rate_small_box',
            'shc_first_class_mail_international_parcels',
            'shc_first_class_mail_international_flats',
            'shc_first_class_mail_international_letters',
            'shc_global_express_guaranteed_envelope',
            'shc_priority_mail_flat_rate_large_box',
            'shc_express_mail_international_flat_rate_envelope',
            'shc_priority_mail_flat_rate_box',
            'shc_priority_mail_flat_rate_envelope',
            'shc_global_express_guaranteed_non_document_non_rectangular',
            'shc_global_express_guaranteed_non_document',
            'shc_global_express_guaranteed_document_used',
            'shc_global_express_guaranteed',
            'shc_priority_mail_international',
            'shc_express_mail_international',
            'shc_container_nonrectangular',
            'shc_container_rectangular',
            'shc_container_regionalrateboxc',
            'shc_container_regionalrateboxb',
            'shc_container_regionalrateboxa',
            'shc_container_lg_flat_rate_box',
            'shc_container_md_flat_rate_box',
            'shc_container_sm_flat_rate_box',
            'shc_container_flat_rate_box',
            'shc_container_gift_card_flat_rate_envelope',
            'shc_container_window_flat_rate_envelope',
            'shc_container_sm_flat_rate_envelope',
            'shc_container_legal_flat_rate_envelope',
            'shc_container_padded_flat_rate_envelope',
            'shc_container_flat_rate_envelope',
            'shc_container_variable',
            'shc_plus',
            'shc_online',
            'shc_library',
            'shc_media',
            'shc_standard_post',
            'shc_priority_mail_express_hfp_cpp',
            'shc_priority_mail_express_hfp_commercial',
            'shc_priority_mail_express_hfp',
            'shc_priority_mail_express_sh_commercial',
            'shc_priority_mail_express_sh',
            'shc_priority_mail_express_cpp',
            'shc_priority_mail_express_commercial',
            'shc_priority_mail_express',
            'shc_priority_hfp_cpp',
            'shc_priority_hfp_commercial',
            'shc_priority_cpp',
            'shc_priority_commercial',
            'shc_priority',
            'shc_first_class_hfp_commercial',
            'shc_first_class_commercial',
            'shc_first_class',
            'shc_handling_time_30',
            'shc_handling_time_20',
            'shc_handling_time_15',
            'shc_handling_time_10',
            'shc_handling_time_5',
            'shc_handling_time_4',
            'shc_handling_time_3',
            'shc_handling_time_2',
            'listing_fields+des+shc_handling_time',
            'listing_fields+des+shc_package_type',
            'listing_fields+des+shc_dimensions',
            'listing_fields+name+shc_handling_time',
            'listing_fields+name+shc_package_type',
            'listing_fields+name+shc_dimensions',
            'shc_ups_service_ups_today_express_saver',
            'shc_ups_service_ups_today_express',
            'shc_ups_service_ups_today_intercity',
            'shc_ups_service_ups_today_dedicated_courier',
            'shc_ups_service_ups_today_standard',
            'shc_ups_service_ups_saver',
            'shc_ups_service_ups_worldwide_express_plus',
            'shc_ups_service_ups_standard',
            'shc_ups_service_ups_expedited',
            'shc_ups_service_ups_express',
            'shc_ups_service_ups_2nd_day_air_am',
            'shc_ups_service_ups_express_early_am',
            'shc_ups_service_ups_next_day_air_saver',
            'shc_ups_service_ups_3_day_select',
            'shc_ups_service_ups_ground',
            'shc_ups_service_ups_2nd_day_air',
            'shc_ups_service_ups_next_day_air',
            'ups_origin_other',
            'shc_ups_origin_mx',
            'shc_ups_origin_pr',
            'shc_ups_origin_eu',
            'shc_ups_origin_ca',
            'shc_ups_origin_us',
            'shipping_methods+name+pickup',
            'shipping_methods+name+courier',
            'shipping_methods+name+USPS',
            'shipping_methods+name+DHL',
            'shipping_methods+name+UPS',
            'shc_shipping_method_failed',
            'shc_services',
            'shc_weight_unit_kg',
            'shc_weight_unit_gr',
            'shc_weight_unit',
            'has_winner',
            'shc_auction_open',
            'shc_auction_finished',
            'auction_type',
            'shc_my_bids',
            'shc_item_details',
            'shc_auction_number',
            'shc_auction_already_paid',
            'shc_auction_failed',
            'shc_not_available_payment_gateways',
            'shc_payment_method',
            'shc_available_payment_gateways',
            'shc_available_shipping_methods',
            'shc_quote_title',
            'shc_quote_days',
            'shc_quote_code',
            'shc_change_shipping_method',
            'shc_my_cart',
            'shc_auction_status',
            'shc_price_field_not_selected',
            'shc_duration',
            'shc_price_buy_now',
            'shc_mode_auction',
            'shc_auto_rate',
            'shc_dhl_test_mode',
            'shc_dhl_password',
            'shc_dhl_site_id',
            'shc_time_format',
            'shc_count_items_block',
            'shc_view_bid_history',
            'shc_method',
            'shc_module',
            'shc_method_notice',
            'shc_listing_field_price',
            'shc_form_bottom',
            'shc_place_in_form',
            'shc_fields_prepend',
            'shc_fields_append',
            'shc_settings_saved',
            'shc_form_top',
            'shc_settings_manager',
            'shc_settings_dhl',
            'shc_fields_position',
            'shc_fields_position_type',
            'ext_start_time',
            'shc_configs',
            'ext_total_bids',
            'ext_payment_status',
            'ext_shc_progress',
            'ext_left_time',
            'ext_start_price',
            'ext_shc_declined',
            'ext_shc_open',
            'ext_shc_delivered',
            'ext_shc_closed',
            'ext_auction_status',
            'ext_ext_shc_shipped',
            'ext_shipping_status',
            'ext_shc_paid',
            'ext_shc_unpaid',
            'ext_shc_pending',
            'ext_shc_processing',
            'ext_shc_order_key',
            'shc_update_order_error',
            'ext_shc_buyer',
            'ext_shc_dealer',
            'ext_shc_item',
            'shc_payment_gateway_not_availble',
            'shc_notice_order_delete',
            'shc_view_order_details',
            'shc_incorrect_payment_details',
            'shc_name',
            'shc_comment',
            'shc_active',
            'shc_txn_id',
            'shc_gateway',
            'shc_pay_date',
            'shc_items',
            'shc_pending',
            'shc_unpaid',
            'shc_paid',
            'shc_no_orders',
            'shc_city',
            'shc_zip',
            'shc_vat_no',
            'shc_address',
            'shc_country',
            'shc_state',
            'shc_step_done',
            'shc_phone',
            'shc_confirmation',
            'shc_step_checkout',
            'shc_shipping_ups',
            'shc_shipping_dhl',
            'shc_shipping_courier',
            'shc_shipping_pickup',
            'shc_total_cost',
            'shc_item',
            'shc_subtotal',
            'shc_total',
            'shc_price',
            'shc_view_shopping_cart',
            'shc_checkout',
            'listing_fields+name+shc_days',
            'listing_fields+name+shc_reserved_price',
            'listing_fields+name+shc_weight',
            'listing_fields+name+shc_quantity',
            'listing_fields+name+shc_available',
            'listing_fields+name+shc_bid_step',
            'listing_fields+name+shc_start_price',
            'listing_groups+name+shopping_cart',
            'blocks+name+shc_my_cart',
        ];
        $langKeys = implode(',', $langKeys);

        $sql = "DELETE FROM `{db_prefix}lang_keys` WHERE `Plugin` = 'shoppingCart' ";
        $sql .= "AND FIND_IN_SET(`Key`, '{$langKeys}') > 0 ";
        $rlDb->query($sql);

        $hooks = [
            'listingAfterStats',
            'apPhpListingsAjaxDeleteListing',
            'apExtTransactionsData',
            'listingAfterPrice',
            'listingAfterFields',
            'paymentControllerValidate',
            'listingDetailsPreFields',
            'tplUserNavbar',
            'tplHeaderNav',
            'sitemapExcludedPages',
            'shoppingCartCurrencyRates',
        ];
        $hooks = implode(',', $hooks);

        $sql = "DELETE FROM `{db_prefix}hooks` WHERE `Plugin` = 'shoppingCart' ";
        $sql .= "AND FIND_IN_SET(`Name`, '{$hooks}') > 0 ";
        $rlDb->query($sql);

        // add migrate information
        $rlDb->addColumnToTable('shc_migrate', "ENUM('0','1') NOT NULL DEFAULT '0'", 'listings');
        $rlDb->addColumnToTable('shc_migrate', "ENUM('0','1') NOT NULL DEFAULT '0'", 'accounts');

        $migrateData = new \ShoppingCart\Admin\MigrateData();
        $totalInfo = $migrateData->getTotal();

        $configMigrate = array(
            'Key' => 'shc_migrate',
            'Default' => (int) $totalInfo['totalListings'] + (int) $totalInfo['totalAccounts'],
            'Plugin' => 'shoppingCart',
        );

        $rlDb->insertOne($configMigrate, 'config');

        $newUpdatePhrases = [
            'shc_migrate' => 'Migrate',
            'shc_before_migrate_hint' => 'Migration is in process; please, keep the page open until the migration is done.',
            'shc_migrate_data' => 'Migrate listings and accounts',
            'shc_migrate_notice' => 'You have {count_listings} listings and {count_accounts} accounts with enabled shopping and auction options. Please click {here} to migrate the data to new tables. Otherwise, the Plugin will work incorrectly for the current listings.',
            'shc_migration_caption' => 'The migration is in progress, please be patient.',
            'shc_migrate_completed' => 'The migration has been completed. The shopping and auction options have been moved to the new tables.',

        ];
        foreach ((array) $languages as $lKey => $lValue) {
            foreach ($newUpdatePhrases as $k => $v) {
                $insertPhrase = array(
                    'Code' => $lValue['Code'],
                    'Module' => 'admin',
                    'Key' => $k,
                    'Value' => $v,
                    'Plugin' => 'shoppingCart',
                    'Target_key' => 'shopping_cart',
                );
                $rlDb->insertOne($insertPhrase, 'lang_keys');
            }
        }

        $insertHook = array(
            'Class' => 'ShoppingCart',
            'Name' => 'apNotifications',
            'Plugin' => 'shoppingCart',
        );
        $rlDb->insertOne($insertHook, 'hooks');
    }

    /**
     * Update to 3.0.1
     */
    public function update301()
    {
        global $rlDb;

        $rlDb->addColumnToTable('Autocomplete', "ENUM('0','1') NOT NULL DEFAULT '0'", 'shc_shipping_fields');

        $hooks = [
            'listingAfterStats',
            'apPhpListingsAjaxDeleteListing',
            'apExtTransactionsData',
            'listingAfterPrice',
            'listingAfterFields',
            'paymentControllerValidate',
            'listingDetailsPreFields',
            'tplUserNavbar',
            'tplHeaderNav',
            'sitemapExcludedPages',
            'shoppingCartCurrencyRates',
            'afterListingDone',
        ];
        $hooks = implode(',', $hooks);

        $sql = "DELETE FROM `{db_prefix}hooks` WHERE `Plugin` = 'shoppingCart' ";
        $sql .= "AND FIND_IN_SET(`Name`, '{$hooks}') > 0 ";
        $rlDb->query($sql);

        $phrases = [
            'shc_do_you_want_to_add_list',
            'shc_step_confirmation',
            'blocks+name+shc_my_cart',
        ];
        $phrases = implode(',', $phrases);

        $sql = "DELETE FROM `{db_prefix}lang_keys` WHERE `Plugin` = 'shoppingCart' ";
        $sql .= "AND FIND_IN_SET(`Key`, '{$phrases}') > 0 ";
        $rlDb->query($sql);

        $sql = "DELETE FROM `{db_prefix}blocks` WHERE `Plugin` = 'shoppingCart' ";
        $sql .= "AND `Key` = 'shc_my_cart' ";
        $rlDb->query($sql);

        $needUpdate = false;
        $fields = $rlDb->getAll("SHOW COLUMNS FROM `{db_prefix}shc_orders`");
        foreach ($fields as $field) {
            if ($field['Field'] == 'Type' && substr_count($field['Type'], 'shopping') <= 0) {
                $needUpdate = true;
            }
        }

        if ($needUpdate) {
            $rlDb->query("ALTER TABLE `{db_prefix}shc_orders` MODIFY COLUMN `Type` enum('shopping','auction') NOT NULL default 'shopping';");
        }
    }

    /**
     * Update to 3.0.2
     */
    public function update302()
    {
        global $rlDb;

        $rlDb->query("ALTER TABLE " . RL_DBNAME . ".`{db_prefix}shc_orders` ENGINE=InnoDB;");
        $rlDb->query("ALTER TABLE " . RL_DBNAME . ".`{db_prefix}shc_order_details` ENGINE=InnoDB;");
        $rlDb->query("ALTER TABLE " . RL_DBNAME . ".`{db_prefix}shc_bids` ENGINE=InnoDB;");
        $rlDb->query("ALTER TABLE " . RL_DBNAME . ".`{db_prefix}shc_shipping_methods` ENGINE=InnoDB;");
        $rlDb->query("ALTER TABLE " . RL_DBNAME . ".`{db_prefix}shc_account_settings` ENGINE=InnoDB;");
        $rlDb->query("ALTER TABLE " . RL_DBNAME . ".`{db_prefix}shc_listing_options` ENGINE=InnoDB;");
        $rlDb->query("ALTER TABLE " . RL_DBNAME . ".`{db_prefix}shc_shipping_form` ENGINE=InnoDB;");
        $rlDb->query("ALTER TABLE " . RL_DBNAME . ".`{db_prefix}shc_shipping_fields` ENGINE=InnoDB;");
    }

    /**
     * Update to 3.1.0
     */
    public function update310(): void
    {
        global $rlDb;

        $rlDb->addColumnToTable('Escrow', "ENUM('0','1') NOT NULL DEFAULT '0'", 'shc_listing_options');

        $rlDb->addColumnsToTable(
            array(
                'Escrow' => "ENUM('0','1') DEFAULT '0' NOT NULL",
                'Escrow_status' => "ENUM( 'pending', 'confirmed', 'canceled') DEFAULT 'pending' NOT NULL",
                'Escrow_date' => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
                'Deal_ID' => "varchar(150) NOT NULL default ''",
                'Payout_ID' => "varchar(150) NOT NULL default ''",
                'Refund_ID' => "varchar(150) NOT NULL default ''",
                'Refund_reason' => "text NOT NULL default ''",
            ),
            'shc_orders'
        );

        $rlDb->query("ALTER TABLE `{db_prefix}shc_orders` MODIFY COLUMN `Status` enum('paid','unpaid','pending','canceled') NOT NULL default 'unpaid';");

        $rlDb->addColumnToTable('Hidden', "ENUM('0','1') NOT NULL DEFAULT '0'", 'shc_shipping_fields');
        $rlDb->addColumnToTable('Total_payout', "double NOT NULL default '0'", 'shc_orders');

        $filesystem = new \Flynax\Component\Filesystem();
        $oldVendor = RL_PLUGINS . 'shoppingCart/vendor/';
        $filesystem->remove($oldVendor);
        $copyFunction = method_exists($filesystem, 'copyTo') ? 'copyTo' : 'copy';
        $filesystem->$copyFunction(RL_UPLOAD . 'shoppingCart/vendor/', $oldVendor);
    }

    /**
     * Update to 3.1.1
     */
    public function update311(): void
    {
        unlink(RL_PLUGINS . 'shoppingCart/view/auth_form.tpl');
    }

    /**
     * Update to 3.1.2
     */
    public function update312(): void
    {
        $GLOBALS['rlDb']->addColumnToTable('Opt3', "MEDIUMTEXT NOT NULL", 'shc_shipping_fields');
    }
}
