<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : MIGRATEDATA.PHP
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

/**
 * @since 3.0.0
 */
class MigrateData
{
    /**
     * Map Listing Fields
     *
     * @var array
     */
    protected $mapListingFields = [
        'shc_start_price' => 'Start_price',
        'shc_reserved_price' => 'Reserved_price',
        'shc_bid_step' => 'Bid_step',
        'shc_max_bid' => 'Max_bid',
        'shc_weight' => 'Weight',
        'shc_end_time' => 'End_time',
        'shc_auction_won' => 'Auction_won',
        'shc_shipping_options' => 'Shipping_options',
        'shc_package_type' => 'Package_type',
        'shc_dimensions' => 'Dimensions',
        'shc_handling_time' => 'Handling_time',
        'shc_shipping_price_type' => 'Shipping_price_type',
        'shc_shipping_price' => 'Shipping_price',
        'shc_commission' => 'Commission',
    ];

    /**
     * Copy listing options to new table
     *
     * @param int $limit
     */
    public function migrateListings($limit = 100)
    {
        global $rlDb, $config;

        $settings = $_SESSION['shcMigrateFields'];

        if (!$settings) {
            return;
        }

        $sql = "SELECT * FROM `{db_prefix}listings` ";
        $sql .= "WHERE `Status` <> 'trash' AND `shc_migrate` = '0' ";
        $sql .= "AND (`shc_mode` = 'fixed' OR `shc_mode` = 'auction') ";
        $sql .= "LIMIT {$limit}";

        $data = $rlDb->getAll($sql);

        if ($data) {
            foreach ($data as $key => $value) {
                $sql = "SELECT `ID` FROM `{db_prefix}shc_listing_options` WHERE `Listing_ID` = {$value['ID']}";
                $itemExists = $rlDb->getRow($sql);

                if ($itemExists) {
                    continue;
                }

                $insert = [
                    'Listing_ID' => $value['ID'],
                ];
                foreach ($this->mapListingFields as $fKey => $fVal) {
                    if (isset($value[$fKey])) {
                        $insert[$fVal] = $value[$fKey];
                    }
                }

                $rlDb->insertOne($insert, 'shc_listing_options');

                $update = array(
                    'fields' => ['shc_migrate' => 1],
                    'where' => array(
                        'ID' => $value['ID'],
                    ),
                );

                $rlDb->updateOne($update, 'listings');
            }

            $_SESSION['shcMigrateFields']['total_listings'] -= count($data);
        }
    }

    /**
     * Copy account options to new table
     *
     * @param int $limit
     */
    public function migrateAccounts($limit = 100)
    {
        global $rlDb, $config;

        $settings = $_SESSION['shcMigrateFields'];

        if (!$settings) {
            return;
        }

        if (!$_SESSION['shcMigrateFields']['added']) {
            $sql = "SHOW COLUMNS FROM `{db_prefix}accounts`";
            $fields = $rlDb->getAll($sql);

            foreach ($fields as $fKey => $fVal) {
                if (substr_count($fVal['Field'], 'shc_') > 0 
                    && !in_array($fVal['Field'], ['shc_shipping_settings', 'shc_migrate'])
                ) {
                    $field = str_replace('shc_', '', $fVal['Field']);
                    $rlDb->addColumnToTable($field, "{$fVal['Type']} NOT NULL DEFAULT '{$fVal['Default']}'", 'shc_account_settings');
                }
            }
            $_SESSION['shcMigrateFields']['added'] = true;
        }

        $sql = "SELECT * FROM `{db_prefix}accounts` ";
        $sql .= "WHERE `Status` <> 'trash' AND `shc_migrate` = '0' ";
        $sql .= "AND FIND_IN_SET(`Type`, '{$config['shc_account_types']}') > 0 ";
        $sql .= "LIMIT {$limit}";

        $data = $rlDb->getAll($sql);

        if ($data) {
            foreach ($data as $key => $value) {
                $sql = "SELECT `ID` FROM `{db_prefix}shc_account_settings` WHERE `Account_ID` = {$value['ID']}";
                $itemExists = $rlDb->getRow($sql);

                if ($itemExists) {
                    continue;
                }

                $insert = [
                    'Account_ID' => $value['ID'],
                ];
                foreach ($value as $fKey => $fVal) {
                    if (substr_count($fKey, 'shc_') > 0 &&  $fKey != 'shc_migrate') {
                        $field = str_replace('shc_', '', $fKey);
                        if ($fKey == 'shc_shipping_settings') {
                            $field = 'Shipping';
                        }
                        $insert[$field] = $fVal;
                    }
                }

                $rlDb->insertOne($insert, 'shc_account_settings');
            }

            $_SESSION['shcMigrateFields']['total_accounts'] -= count($data);
        }
    }

    /**
     * Get total rows
     */
    public function getTotal()
    {
        $sql = "SELECT COUNT(`ID`) AS `total` FROM `{db_prefix}listings` ";
        $sql .= "WHERE `Status` <> 'trash' AND `shc_migrate` = '0' ";
        $sql .= "AND (`shc_mode` = 'fixed' OR `shc_mode` = 'auction') ";

        $totalListings = $GLOBALS['rlDb']->getRow($sql);

        $sql = "SELECT COUNT(`ID`) AS `total` FROM `{db_prefix}accounts` ";
        $sql .= "WHERE `Status` <> 'trash' AND `shc_migrate` = '0' ";
        $sql .= "AND FIND_IN_SET(`Type`, '{$GLOBALS['config']['shc_account_types']}') > 0 ";

        $totalAccounts = $GLOBALS['rlDb']->getRow($sql);

        return ['totalListings' => $totalListings['total'], 'totalAccounts' => $totalAccounts['total']];
    }

    /**
     * Complete process
     */
    public function complete()
    {
        global $rlDb;

        // delete listing fields
        $this->mapListingFields['shc_migrate'] = '';
        $rlDb->dropColumnsFromTable(
            array_keys($this->mapListingFields),
            'listings'
        );

        // delete account fields
        $sql = "SHOW COLUMNS FROM `{db_prefix}accounts`";
        $fields = $rlDb->getAll($sql);

        foreach ($fields as $fKey => $fVal) {
            if (substr_count($fVal['Field'], 'shc_') > 0) {
                $rlDb->dropColumnFromTable($fVal['Field'], 'accounts');
            }
        }

        $update = array(
            'fields' => array(
                'Default' => 0,
            ),
            'where' => array('Key' => 'shc_migrate'),

        );
        $rlDb->updateOne($update, 'config');

        $rlDb->dropColumnFromTable('shc_migrate', 'listings');
        $rlDb->dropColumnFromTable('shc_migrate', 'accounts');

        // delete hook
        $sql = "DELETE FROM `{db_prefix}hooks` WHERE `Name` = 'apNotifications' AND `Plugin` = 'shopping_cart'";
        $rlDb->query($sql);

        unset($_SESSION['shcMigrateFields']);
    }

    /**
     * Migration process by ajax request
     */
    public function ajaxMigrateDate(&$out)
    {
        set_time_limit(0);

        $limit = (int) $_SESSION['shcMigrateFields']['per_run'];
        $start = (int) $_GET['index'];

        if ($_SESSION['shcMigrateFields']['total_listings'] >= $limit) {
            $this->migrateListings($limit);
        } elseif($_SESSION['shcMigrateFields']['total_listings'] > 0 && $_SESSION['shcMigrateFields']['total_listings'] < $limit) {
            $this->migrateListings($_SESSION['shcMigrateFields']['total_listings']);
            $this->migrateAccounts($limit - $_SESSION['shcMigrateFields']['total_listings']);
        } else {
            $this->migrateAccounts($limit);
        }

        $out['from'] = $start + $limit;
        $out['to'] = $start + ($limit * 2) - 1;
        $out['count'] = (int) $_SESSION['shcMigrateFields']['total'];

        if ($out['to'] >= $_SESSION['shcMigrateFields']['total']) {
            $this->complete();
        }
    }

    /**
     * Init migration page
     */
    public function init($module)
    {
        global $bcAStep, $lang, $reefless;

        if ($module != 'migrate_data') {
            $notices = [];
            $this->printNotice($notices, true);
            return;
        }

        if (!$GLOBALS['rlDb']->columnExists('shc_migrate', 'listings')) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php?controller=shopping_cart";
            $reefless->redirect(false, $redirect_url);
            return;
        }

        $bcAStep[0] = array(
            'name' => $lang['shc_migrate_data'],
            'Controller' => 'shopping_cart',
            'Vars' => '&module=migrate_data',
        );

        if (!$_SESSION['shcMigrateFields']) {
            $totalInfo = $this->getTotal();

            $_SESSION['shcMigrateFields'] = [
                'total_listings' => $totalInfo['totalListings'],
                'total_accounts' => $totalInfo['totalAccounts'],
                'total' => $totalInfo['totalListings'] + $totalInfo['totalAccounts'],
                'per_run' => 100,
            ];
        }

        $GLOBALS['rlSmarty']->assign_by_ref('shcMigrateFields', $_SESSION['shcMigrateFields']);
    }

    /**
     * Show notification about migration
     */
    public function printNotice(&$notices = [], $out = false)
    {
        global $config, $lang;

        if (!isset($config['shc_migrate'])) {
            $config['shc_migrate'] = 0;
        }
        if ($config['shc_migrate'] > 0) {
            $url = RL_URL_HOME . ADMIN . '/index.php?controller=shopping_cart&module=migrate_data';
            $link = '<a href="' . $url . '">' . $lang['here'] . '</a>';

            $totalInfo = $this->getTotal();
            $notices[] = str_replace(
                array('{count_listings}', '{count_accounts}', '{here}'),
                array($totalInfo['totalListings'], $totalInfo['totalAccounts'], $link),
                $lang['shc_migrate_notice']
            );

            if ($out) {
                $GLOBALS['rlSmarty']->assign_by_ref('alerts', $notices[0]);
            }
        }
    }
}
