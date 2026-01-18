<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLAFFILIATE.CLASS.PHP
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

use Flynax\Utils\StringUtil;
use Flynax\Utils\Util;

class rlAffiliate extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * Install process
     */
    public function install()
    {
        global $reefless, $rlDb;

        // create affiliate account type if it not exist
        if (!$rlDb->getRow("SELECT `ID` FROM `{db_prefix}account_types` WHERE `Key` LIKE 'affiliate'")) {
            // get max position
            $position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}account_types`");

            // write main type information
            $data = array(
                'Key'                => 'affiliate',
                'Status'             => 'active',
                'Abilities'          => '',
                'Page'               => 0,
                'Own_location'       => 0,
                'Email_confirmation' => 0,
                'Admin_confirmation' => 0,
                'Auto_login'         => 1,
                'Position'           => $position['max'] + 1,
            );

            if ($rlDb->insertOne($data, 'account_types')) {
                // add enum option to listing plans table
                $GLOBALS['rlActions']->enumAdd('listing_plans', 'Allow_for', $data['Key']);
            }
        }

        // create affiliate table
        $rlDb->createTable(
            'affiliate',
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
            `Cron` enum('0','1') NOT NULL DEFAULT '0',
            `Affiliate_ID` int(11) NOT NULL,
            `Referral_ID` int(11) NOT NULL,
            `IP` varchar(255) NOT NULL,
            `Referring_Url` varchar(255) NOT NULL,
            `Date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            `Country_name` varchar(255) NOT NULL,
            `Region` varchar(255) NOT NULL,
            `City` varchar(255) NOT NULL,
            `Item_ID` int(11) NOT NULL,
            `Plan_ID` int(11) NOT NULL,
            `Type` enum('visit','register','listing','membership') NOT NULL DEFAULT 'visit',
            `Commission` float(14,2) NOT NULL,
            `Commission_Type` enum('fixed','percentage') NOT NULL DEFAULT 'fixed',
            `Status` enum('refused','pending','ready','deposited') NOT NULL DEFAULT 'pending',
            PRIMARY KEY (`ID`)"
        );

        // create affiliate account billing details table
        $rlDb->createTable(
            'aff_billing_details',
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
            `Affiliate_ID` int(11) NOT NULL,
            `Billing_type` enum('paypal','western_union','bank_wire') NOT NULL DEFAULT 'paypal',
            `Paypal_email` varchar(255) NOT NULL,
            `WU_country` varchar(255) NOT NULL,
            `WU_city` varchar(255) NOT NULL,
            `WU_fullname` varchar(255) NOT NULL,
            `Bank_wire_details` varchar(255) NOT NULL,
            PRIMARY KEY (`ID`)"
        );

        // create affiliate banners table
        $rlDb->createTable(
            'aff_banners',
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
            `Key` varchar(255) NOT NULL,
            `Width` int(11) NOT NULL,
            `Height` int(11) NOT NULL,
            `Image` varchar(255) NOT NULL,
            `Clicks` int(11) NOT NULL,
            `Status` enum('approval','active') NOT NULL DEFAULT 'active',
            PRIMARY KEY (`ID`)"
        );

        // create affiliate payouts table
        $rlDb->createTable(
            'aff_payouts',
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
            `Affiliate_ID` int(11) NOT NULL,
            `Deals_IDs` varchar(255) NOT NULL,
            `Date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            `Status` enum('deposited','refused') NOT NULL DEFAULT 'deposited',
            PRIMARY KEY (`ID`)"
        );

        // add Aff_commission and Aff_commission_type columns to listing plans
        $rlDb->addColumnsToTable(
            array(
                'Aff_commission'      => "FLOAT NOT NULL DEFAULT '0'",
                'Aff_commission_type' => "ENUM('fixed','percentage') NOT NULL DEFAULT 'fixed'",
            ),
            'listing_plans'
        );

        // add Aff_commission and Aff_commission_type columns to membership plans
        $rlDb->addColumnsToTable(
            array(
                'Aff_commission'      => "FLOAT NOT NULL DEFAULT '0'",
                'Aff_commission_type' => "ENUM('fixed','percentage') NOT NULL DEFAULT 'fixed'",
            ),
            'membership_plans'
        );

        // remove plugin value for static page
        if ($rlDb->getRow("SELECT `ID` FROM `{db_prefix}pages` WHERE (`Key` LIKE 'aff_program_page' OR `Key` LIKE 'aff_terms_of_use_program_page')")) {
            $rlDb->query("UPDATE `{db_prefix}pages` SET `Plugin` = '' WHERE (`Key` LIKE 'aff_program_page' OR `Key` LIKE 'aff_terms_of_use_program_page')");
        }

        // deny affiliate pages for another account types
        $tmp_account_types = $rlDb->fetch(array('ID'), null, "WHERE `Key` <> 'affiliate'", null, 'account_types');
        foreach ($tmp_account_types as $account_type) {
            $account_types = $account_types ? $account_types . ',' . $account_type['ID'] : $account_type['ID'];
        }

        if ($account_types) {
            // General Stats page
            if ($rlDb->getRow("SELECT `ID` FROM `{db_prefix}pages` WHERE `Key` LIKE 'aff_general_stats'")) {
                $rlDb->query("UPDATE `{db_prefix}pages` SET `Deny` = '{$account_types}' WHERE `Key` LIKE 'aff_general_stats'");
            }
            // Banners page
            if ($rlDb->getRow("SELECT `ID` FROM `{db_prefix}pages` WHERE `Key` LIKE 'aff_banners'")) {
                $rlDb->query("UPDATE `{db_prefix}pages` SET `Deny` = '{$account_types}' WHERE `Key` LIKE 'aff_banners'");
            }
            // Commissions page
            if ($rlDb->getRow("SELECT `ID` FROM `{db_prefix}pages` WHERE `Key` LIKE 'aff_commissions'")) {
                $rlDb->query("UPDATE `{db_prefix}pages` SET `Deny` = '{$account_types}' WHERE `Key` LIKE 'aff_commissions'");
            }
            // Payment History page
            if ($rlDb->getRow("SELECT `ID` FROM `{db_prefix}pages` WHERE `Key` LIKE 'aff_payment_history'")) {
                $rlDb->query("UPDATE `{db_prefix}pages` SET `Deny` = '{$account_types}' WHERE `Key` LIKE 'aff_payment_history'");
            }
            // Traffic Log page
            if ($rlDb->getRow("SELECT `ID` FROM `{db_prefix}pages` WHERE `Key` LIKE 'aff_traffic_log'")) {
                $rlDb->query("UPDATE `{db_prefix}pages` SET `Deny` = '{$account_types}' WHERE `Key` LIKE 'aff_traffic_log'");
            }
        }

        // remove Affiliate menu box from other pages except affiliate's and "My Profile", "My Messages"
        $tmp_aff_pages = $rlDb->fetch(array('ID'), null, "WHERE FIND_IN_SET(`Key`, 'aff_general_stats,aff_banners,aff_commissions,aff_payment_history,aff_traffic_log,my_profile,my_messages')", null, 'pages');

        foreach ($tmp_aff_pages as $aff_page) {
            $aff_pages = $aff_pages ? $aff_pages . ',' . $aff_page['ID'] : $aff_page['ID'];
        }

        if ($aff_pages && $rlDb->getRow("SELECT `ID` FROM `{db_prefix}blocks` WHERE `Key` LIKE 'aff_menu'")) {
            $rlDb->query("UPDATE `{db_prefix}blocks` SET `Page_ID` = '{$aff_pages}', `Sticky` = '0', `Position` = '0' WHERE `Key` LIKE 'aff_menu'");
        }
    }

    /**
     * Uninstall process
     */
    public function uninstall()
    {
        global $config, $rlActions, $rlDb;

        $key = 'affiliate';

        // delete accounts with affiliate account type
        $rlDb->setTable('accounts');
        if ($accounts = $rlDb->fetch(array('ID', 'Username', 'First_name', 'Last_name', 'Mail'), array('Type' => $key))) {
            $rlDb->resetTable();

            foreach ($accounts as $account) {
                $rlActions->delete(array('ID' => $account['ID']), array('accounts'), false, false, $account['ID']);
            }
        }

        // remove enum option from listing plans table
        $rlActions->enumRemove('listing_plans', 'Allow_for', $key);

        $tmp_config_cache = $config['trash'];
        $config['trash']  = 0;

        // delete account type
        $lang_keys[] = array('Key' => 'account_types+name+' . $key);
        $lang_keys[] = array('Key' => 'account_types+desc+' . $key);
        $rlActions->delete(array('Key' => $key), array('account_types'), false, false, $key, $lang_keys);

        $config['trash'] = $tmp_config_cache;

        // delete affiliate program page & affiliate term of use page
        if ($rlDb->getRow("SELECT `ID` FROM `{db_prefix}pages` WHERE (`Key` LIKE 'aff_program_page' OR `Key` LIKE 'aff_terms_of_use_program_page')")) {
            $rlDb->query("DELETE FROM `{db_prefix}pages` WHERE (`Key` LIKE 'aff_program_page' OR `Key` LIKE 'aff_terms_of_use_program_page')");
        }

        // delete tables of plugin
        $rlDb->dropTables(array('affiliate', 'aff_billing_details', 'aff_banners', 'aff_payouts'));

        // delete commissions columns from listing plans
        $rlDb->dropColumnsFromTable(array('Aff_commission', 'Aff_commission_type'), 'listing_plans');

        // delete commissions columns from membership plans
        $rlDb->dropColumnsFromTable(array('Aff_commission', 'Aff_commission_type'), 'membership_plans');

        // removing affiliate banners and folder
        if ($images = glob(RL_FILES . 'aff_images/*', GLOB_MARK)) {
            foreach ($images as $image) {
                unlink($image);
            }
        }
        rmdir(RL_FILES . 'aff_images/');
    }

    /**
     * Update process of the plugin (copy from core)
     * @param string $version
     */
    public function update($version)
    {
        $version_method = 'update' . (int) str_replace('.', '', $version);
        if (method_exists($this, $version_method)) {
            $this->$version_method();
        }
    }

    /**
     * Update to 1.0.3 version
     */
    public function update103()
    {
        $GLOBALS['rlDb']->query(
            "UPDATE `{db_prefix}lang_keys` SET `Value` = REPLACE(`Value`, '{username}', '{name}')
             WHERE `Key` = 'email_templates+body+affiliate_account_created'"
        );
    }

    /**
     * Update to 1.1.0 version
     */
    public function update110()
    {
        global $rlDb;

        $rlDb->addColumnsToTable(
            array(
                'Aff_commission'      => "FLOAT NOT NULL DEFAULT '0'",
                'Aff_commission_type' => "ENUM('fixed','percentage') NOT NULL DEFAULT 'fixed'",
            ),
            'membership_plans'
        );

        $rlDb->query("DELETE FROM `{db_prefix}hooks` WHERE `Name` = 'tplFooter' AND `Plugin` = 'affiliate'");
    }

    /**
     * Update to 1.3.3 version
     */
    public function update133()
    {
        if (in_array('ru', array_keys($GLOBALS['languages']))) {
            $russianTranslation = json_decode(file_get_contents(RL_UPLOAD . 'affiliate/i18n/ru.json'), true);
            foreach (['pages+name+aff_program_page', 'pages+title+aff_program_page'] as $phraseKey) {
                $GLOBALS['rlDb']->updateOne([
                    'fields' => ['Value' => $russianTranslation[$phraseKey]],
                    'where'  => ['Key'   => $phraseKey, 'Code' => 'ru'],
                ], 'lang_keys');
            }
        }
    }

    /**
     * Create new affiliate event
     *
     * @param int    $affiliate_ID        - ID of affiliate account
     * @param int    $referral_ID         - ID of referral account
     * @param int    $plan_ID             - ID of plan
     * @param string $type                - Type of event (visit | register | listing | membership)
     * @param int    $item_ID             - ID of listing
     * @param float  $aff_commission
     * @param string $aff_commission_type - Type of commision (fixed | percentage)
     */
    public function createEvent(
        $affiliate_ID,
        $referral_ID,
        $plan_ID,
        $type,
        $item_ID = 0,
        $aff_commission = 0,
        $aff_commission_type = ''
    ) {
        global $reefless, $config;

        if (!$config['affiliate_module']) {
            return;
        }

        if ($affiliate_ID) {
            $insert['Affiliate_ID'] = $affiliate_ID;
        }

        if ($referral_ID) {
            $insert['Referral_ID'] = $referral_ID;
        }

        if ($plan_ID) {
            $insert['Plan_ID'] = $plan_ID;
        }

        if ($type) {
            $insert['Type'] = $type;
        }

        if ($item_ID) {
            $insert['Item_ID'] = $item_ID;
        }

        if ($aff_commission) {
            $insert['Commission'] = $aff_commission;
        }

        if ($aff_commission_type) {
            $insert['Commission_Type'] = $aff_commission_type;
        }

        // get client IP
        $ip = $reefless->getClientIpAddress();

        if (($insert['Affiliate_ID'] || $insert['Referral_ID']) && $ip) {
            $insert['IP']            = $ip;
            $insert['Referring_Url'] = $type == 'visit' && $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : '';
            $insert['Date']          = 'NOW()';
            $insert['Country_name']  = $_SESSION['GEOLocationData']->Country_name ? $_SESSION['GEOLocationData']->Country_name : '';
            $insert['Region']        = $_SESSION['GEOLocationData']->Region ? $_SESSION['GEOLocationData']->Region : '';
            $insert['City']          = $_SESSION['GEOLocationData']->City ? $_SESSION['GEOLocationData']->City : '';
        }

        if (is_array($insert)) {
            $GLOBALS['rlDb']->insertOne($insert, 'affiliate');
        }
    }

    /**
     * Quick registration new affiliate account
     *
     * @param string $name
     * @param string $email
     */
    public function registration($name, $email)
    {
        global $errors, $error_fields, $lang, $reefless, $rlAccount, $rlSmarty, $rlMail, $rlDb;

        // Check the correct of email
        if (!$GLOBALS['rlValid']->isEmail($email)) {
            $errors[] = $lang['notice_bad_email'];
            $error_fields .= 'register[email],';
        }
        // Check the existence of email
        else if ($account = $rlDb->getOne('ID', "`Mail` = '{$email}' AND `Status` <> 'trash'", 'accounts')) {
            $errors[] = str_replace(
                '{email}',
                '<span class="field_error">' . $email . '</span>',
                $lang['notice_account_email_exist']
            );
            $error_fields .= 'register[email],';
        }

        // Register new affiliate account
        if (!$errors) {
            $_SESSION['affiliate_email'] = $email;

            if ($new_account = $rlAccount->quickRegistration($name, $email)) {
                // Custom update for escort package
                if (file_exists(RL_CLASSES . 'rlEscort.class.php') && $affiliate_ID = $new_account[2]) {
                    $rlDb->updateOne([
                        'fields' => ['Type' => 'affiliate'],
                        'where'  => ['ID' => $affiliate_ID]
                    ], 'accounts');
                }

                $rlAccount->login($new_account[0], $new_account[1]);
                $rlSmarty->assign('isLogin', $_SESSION['username']);
                define('IS_LOGIN', true);

                $account_info = $_SESSION['account'];
                $rlSmarty->assign_by_ref('account_info', $account_info);

                $reefless->loadClass('Mail');

                // Save temp password in email
                if (method_exists($rlMail, 'addMailTemplateForSendingPassword')) {
                    $rlMail->addMailTemplateForSendingPassword('affiliate_account_created');
                }

                $mail_tpl         = $rlMail->getEmailTemplate('affiliate_account_created');
                $mail_tpl['body'] = str_replace(
                    ['{name}', '{login}', '{password}'],
                    [$account_info['Full_name'], trim($new_account[0]), $new_account[1]],
                    $mail_tpl['body']
                );
                $rlMail->send($mail_tpl, $email);

                $reefless->loadClass('Notice');
                $GLOBALS['rlNotice']->saveNotice($lang['notice_logged_in']);

                // Register new referral account
                $affiliateID = (int) $_COOKIE['Affiliate_ID'];

                if ($affiliateID && $account_info['ID']) {
                    $this->registerNewReferral($affiliateID, $account_info['ID']);
                }

                $GLOBALS['rlHook']->load('loginSuccess');
            }
        }
    }

    /**
     * Get general statistics about affiliate activities
     */
    public function getStats()
    {
        global $config, $account_info, $rlSmarty, $rlDb;

        if (!defined('IS_LOGIN') || !$account_info['ID'] || !$config['affiliate_module']) {
            return;
        }

        if (!$account_info['Aff_billing_details'] = $rlDb->getOne(
            'ID',
            "`Affiliate_ID` = '{$account_info['ID']}'",
            'aff_billing_details'
        )) {
            $rlSmarty->assign('my_profile_url', $GLOBALS['reefless']->getPageUrl('my_profile'));
        }

        $last_payout_id = (int) $rlDb->getOne(
            'ID',
            "`Affiliate_ID` = {$account_info['ID']} ORDER BY `Date` DESC",
            'aff_payouts'
        );

        if ($last_payout_id) {
            $last_payout = $rlDb->fetch('*', array('ID' => $last_payout_id), null, null, 'aff_payouts', 'row');

            $where = " AND UNIX_TIMESTAMP(`Date`) >= UNIX_TIMESTAMP('{$last_payout['Date']}') ";
            $where .= "AND `ID` NOT IN ({$last_payout['Deals_IDs']})";
        }

        /* get current activities */
        $visitors = $rlDb->getRow("
            SELECT COUNT('*') AS `Count` FROM `{db_prefix}affiliate`
            WHERE `Type` = 'visit' AND `Affiliate_ID` = {$account_info['ID']}" . $where
        );
        $unique_visitors = $rlDb->getRow("
            SELECT COUNT(DISTINCT `IP`) AS `Count` FROM `{db_prefix}affiliate`
            WHERE `Type` = 'visit' AND `Affiliate_ID` = {$account_info['ID']}" . $where
        );
        $registered = $rlDb->getRow("
            SELECT COUNT('*') AS `Count` FROM `{db_prefix}affiliate`
            WHERE `Type` = 'register' AND `Affiliate_ID` = {$account_info['ID']}" . $where
        );
        $transactions = $rlDb->getRow("
            SELECT COUNT('*') AS `Count` FROM `{db_prefix}affiliate`
            WHERE (`Type` <> 'visit') AND `Affiliate_ID` = {$account_info['ID']}" . $where
        );
        /* get current activities end */

        /* build pending earnings */
        $pending_earnings = $rlDb->getRow("
            SELECT SUM(`Commission`) AS `Commission` FROM `{db_prefix}affiliate`
            WHERE `Commission` <> '' AND `Commission` > 0 AND `Affiliate_ID` = {$account_info['ID']}
            AND `Status` = 'pending'" . $where
        );
        $pending_earnings = (float) $pending_earnings['Commission'] ? $pending_earnings['Commission'] : 0;

        if ($pending_earnings) {
            $pending_earnings = $config['system_currency_position'] == 'before'
            ? ($config['system_currency'] . ' ' . $pending_earnings)
            : ($pending_earnings . ' ' . $config['system_currency']);
        }
        /* build pending earnings end */

        /* build available earnings */
        $available_earnings = $rlDb->getRow("
            SELECT SUM(`Commission`) AS `Commission` FROM `{db_prefix}affiliate`
            WHERE `Commission` <> '' AND `Commission` > 0 AND `Affiliate_ID` = {$account_info['ID']}
            AND `Status` = 'ready'" . $where
        );
        $available_earnings = (float) $available_earnings['Commission'] ? $available_earnings['Commission'] : 0;

        if ($available_earnings) {
            $available_earnings = $config['system_currency_position'] == 'before'
            ? ($config['system_currency'] . ' ' . $available_earnings)
            : ($available_earnings . ' ' . $config['system_currency']);
        }
        /* build available earnings end */

        $stats['Current'] = [
            'Visitors'           => intval($visitors['Count']) ? $visitors['Count'] : 0,
            'Unique_visitors'    => intval($unique_visitors['Count']) ? $unique_visitors['Count'] : 0,
            'Registered'         => intval($registered['Count']) ? $registered['Count'] : 0,
            'Transactions'       => intval($transactions['Count']) ? $transactions['Count'] : 0,
            'Pending_earnings'   => $pending_earnings,
            'Available_earnings' => $available_earnings,
        ];

        /* get total activities */
        $visitors = $rlDb->getRow("
            SELECT COUNT('*') AS `Count` FROM `{db_prefix}affiliate`
            WHERE `Type` = 'visit' AND `Affiliate_ID` = {$account_info['ID']}
        ");
        $unique_visitors = $rlDb->getRow("
            SELECT COUNT(DISTINCT `IP`) AS `Count` FROM `{db_prefix}affiliate`
            WHERE `Type` = 'visit' AND `Affiliate_ID` = {$account_info['ID']}
        ");
        $registered = $rlDb->getRow("
            SELECT COUNT('*') AS `Count` FROM `{db_prefix}affiliate`
            WHERE `Type` = 'register' AND `Affiliate_ID` = {$account_info['ID']}
        ");
        $transactions = $rlDb->getRow("
            SELECT COUNT('*') AS `Count` FROM `{db_prefix}affiliate`
            WHERE (`Type` <> 'visit') AND `Affiliate_ID` = {$account_info['ID']}
        ");
        /* get total activities end */

        /* build total earnings */
        $earnings = $rlDb->getRow("
            SELECT SUM(`Commission`) AS `Commission` FROM `{db_prefix}affiliate`
            WHERE `Commission` <> '' AND `Commission` > 0 AND `Affiliate_ID` = {$account_info['ID']}
        ");
        $earnings = (float) $earnings['Commission'] ? $earnings['Commission'] : 0;

        if ($earnings) {
            $earnings = $config['system_currency_position'] == 'before'
            ? ($config['system_currency'] . ' ' . $earnings)
            : ($earnings . ' ' . $config['system_currency']);
        }
        /* build total earnings end */

        $stats['Total'] = array(
            'Visitors'        => intval($visitors['Count']) ? $visitors['Count'] : 0,
            'Unique_visitors' => intval($unique_visitors['Count']) ? $unique_visitors['Count'] : 0,
            'Registered'      => intval($registered['Count']) ? $registered['Count'] : 0,
            'Transactions'    => intval($transactions['Count']) ? $transactions['Count'] : 0,
            'Earnings'        => $earnings,
        );

        $rlSmarty->assign_by_ref('stats', $stats);
    }

    /**
     * Get info about traffic
     */
    public function getTrafficLog()
    {
        global $rlSmarty, $config, $account_info, $lang, $rlDb;

        if (!defined('IS_LOGIN') || !$account_info['ID'] || !$config['affiliate_module']) {
            return;
        }

        // get current page
        $pInfo['current'] = (int) $_GET['pg'];

        // define start position
        $limit = (int) $config['aff_items_per_page'] > 0 ? $config['aff_items_per_page'] : 10;
        $start = $pInfo['current'] > 1 ? ($pInfo['current'] - 1) * $limit : 0;

        $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.* ";
        $sql .= "FROM `{db_prefix}affiliate` AS `T1` ";
        $sql .= "WHERE `T1`.`Affiliate_ID` = {$account_info['ID']} ";
        $sql .= "AND (`T1`.`Type` = 'visit' OR `T1`.`Type` = 'register') ";
        $sql .= "ORDER BY `T1`.`Date` DESC ";
        $sql .= "LIMIT {$start}, {$limit}";
        $traffic_log = $rlDb->getAll($sql);

        // count total traffic
        $calc          = $rlDb->getRow("SELECT FOUND_ROWS() AS `calc`");
        $pInfo['calc'] = $calc['calc'];
        $rlSmarty->assign_by_ref('pInfo', $pInfo);

        foreach ($traffic_log as &$value) {
            $value['Type'] = $lang['aff_type_' . $value['Type']] ? $lang['aff_type_' . $value['Type']] : $value['Type'];
        }

        $rlSmarty->assign_by_ref('traffic_log', $traffic_log);
    }

    /**
     * Get info about payouts
     */
    public function getPaymentHistory()
    {
        global $rlSmarty, $config, $account_info, $pages, $rlDb;

        if (!defined('IS_LOGIN') || !$account_info['ID'] || !$config['affiliate_module']) {
            return;
        }

        // get current page
        $pInfo['current'] = (int) $_GET['pg'];

        // define start position
        $limit = (int) $config['aff_items_per_page'] > 0 ? $config['aff_items_per_page'] : 10;
        $start = $pInfo['current'] > 1 ? ($pInfo['current'] - 1) * $limit : 0;

        $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.* ";
        $sql .= "FROM `{db_prefix}aff_payouts` AS `T1` ";
        $sql .= "WHERE `T1`.`Affiliate_ID` = {$account_info['ID']} ";
        $sql .= "AND `Status` = 'deposited' ";
        $sql .= "ORDER BY `T1`.`Date` DESC ";
        $sql .= "LIMIT {$start}, {$limit}";
        $payouts = $rlDb->getAll($sql);

        // count total entries of payouts
        $calc          = $rlDb->getRow("SELECT FOUND_ROWS() AS `calc`");
        $pInfo['calc'] = $calc['calc'];
        $rlSmarty->assign_by_ref('pInfo', $pInfo);

        foreach ($payouts as &$payout) {
            // count deals
            $payout['Payouts_count'] = count(explode(',', $payout['Deals_IDs']));

            // count amount by payout
            $payout['Amount']               = $rlDb->getRow("SELECT SUM(`Commission`) AS `Commission` FROM `{db_prefix}affiliate` WHERE `Commission` <> '' AND `Commission` > 0 AND `Affiliate_ID` = '{$account_info['ID']}' AND FIND_IN_SET(`ID`, '{$payout['Deals_IDs']}') ");
            $payout['Amount']['Commission'] = $payout['Amount']['Commission'] > 0 ? $payout['Amount']['Commission'] : '0.00';
            $payout['Amount']               = $config['system_currency_position'] == 'before' ? ($config['system_currency'] . ' ' . $payout['Amount']['Commission']) : ($payout['Amount']['Commission'] . ' ' . $config['system_currency']);

            // link to view details
            $payout['Details_link'] = $GLOBALS['reefless']->getPageUrl('aff_payment_history') . '?id=' . $payout['ID'];
        }

        $rlSmarty->assign_by_ref('payouts', $payouts);
    }

    /**
     * Get info about payouts (admin side)
     */
    public function getApPaymentHistory()
    {
        global $reefless, $rlSmarty, $rlAccount, $lang, $config, $rlDb;

        // data read
        $limit = (int) $_GET['limit'];
        $start = (int) $_GET['start'];

        if (!is_numeric($start) || !is_numeric($limit)) {
            return;
        }

        // add filters
        foreach ($_GET as $filter => $val) {
            switch ($filter) {
                case 'Affiliate':
                    if ($val) {
                        $join .= "LEFT JOIN `{db_prefix}accounts` AS `Affiliates` ON `T1`.`Affiliate_ID` = `Affiliates`.`ID` ";
                        $where .= "`Affiliates`.`Username` LIKE '{$val}' AND ";
                    }
                    break;

                case 'date_from':
                    if ($val) {
                        $where .= "UNIX_TIMESTAMP(DATE(`T1`.`Date`)) >= UNIX_TIMESTAMP('{$val}') AND ";
                    }
                    break;

                case 'date_to':
                    if ($val) {
                        $where .= "UNIX_TIMESTAMP(DATE(`T1`.`Date`)) <= UNIX_TIMESTAMP('{$val}') AND ";
                    }
                    break;
            }
        }

        $reefless->loadClass('Account');

        // get payouts by limit
        $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.* ";
        $sql .= "FROM `{db_prefix}aff_payouts` AS `T1` ";

        $sql = $join ? $sql . $join : $sql;
        $sql .= "WHERE `T1`.`Status` = 'deposited' ";
        $sql = $where ? $sql . 'AND ' . rtrim($where, 'AND ') : $sql;

        $sql .= "ORDER BY `T1`.`Date` DESC ";
        $sql .= "LIMIT {$start}, {$limit}";
        $data = $rlDb->getAll($sql);

        // count total events
        $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

        foreach ($data as &$value) {
            // get affiliate full name
            $affiliate          = $rlAccount->getProfile((int) $value['Affiliate_ID']);
            $value['Affiliate'] = RL_URL_HOME . ADMIN . '/index.php?controller=accounts&action=view&userid=' . $affiliate['ID'];
            $value['Affiliate'] = '<a target="_blank" alt="' . $lang['view_account'] . '" title="' . $lang['view_account'] . '" href="' . $value['Affiliate'] . '">' . trim($affiliate['Full_name']) . '</a>';

            // count the number of deals
            $value['Count_deals'] = count(explode(',', $value['Deals_IDs']));

            // count amount by payout
            $value['Amount']               = $rlDb->getRow("SELECT SUM(`Commission`) AS `Commission` FROM `{db_prefix}affiliate` WHERE `Commission` <> '' AND `Commission` > 0 AND `Affiliate_ID` = '{$value['Affiliate_ID']}' AND FIND_IN_SET(`ID`, '{$value['Deals_IDs']}') ");
            $value['Amount']['Commission'] = $value['Amount']['Commission'] > 0 ? $value['Amount']['Commission'] : '0.00';
            $value['Amount']               = $config['system_currency_position'] == 'before' ? ($config['system_currency'] . ' ' . $value['Amount']['Commission']) : ($value['Amount']['Commission'] . ' ' . $config['system_currency']);
        }

        return array('data' => $data, 'count' => $count['count']);
    }

    /**
     * Get info about payout (frontend)
     */
    public function getPayoutDetails()
    {
        global $reefless, $rlSmarty, $rlAccount, $rlPlan, $lang, $config, $payout, $bread_crumbs, $page_info, $rlDb;
        // payout ID
        $id = (int) $_GET['id'];

        if (!is_numeric($id)) {
            return;
        }

        $reefless->loadClass('Account');
        $reefless->loadClass('Plan');

        // get payout info
        $payout = $rlDb->fetch('*', array('ID' => $id, 'Status' => 'deposited'), null, null, 'aff_payouts', 'row');

        // get affiliate info
        $affiliate               = $rlAccount->getProfile((int) $payout['Affiliate_ID']);
        $payout['Aff_Full_name'] = trim($affiliate['Full_name']);
        $payout['Aff_email']     = $affiliate['Mail'] ? '<a href="mailto:' . $affiliate['Mail'] . '">' . $affiliate['Mail'] . '</a>' : '';

        // count the number of deals
        $payout['Count_deals'] = count(explode(',', $payout['Deals_IDs']));

        // count amount by payout
        $payout['Amount']               = $rlDb->getRow("SELECT SUM(`Commission`) AS `Commission` FROM `{db_prefix}affiliate` WHERE `Commission` <> '' AND `Commission` > 0 AND `Affiliate_ID` = '{$payout['Affiliate_ID']}' AND FIND_IN_SET(`ID`, '{$payout['Deals_IDs']}') ");
        $payout['Amount']['Commission'] = $payout['Amount']['Commission'] > 0 ? $payout['Amount']['Commission'] : '0.00';
        $payout['Amount']               = $config['system_currency_position'] == 'before' ? ($config['system_currency'] . ' ' . $payout['Amount']['Commission']) : ($payout['Amount']['Commission'] . ' ' . $config['system_currency']);

        // get info about each of deals
        $deals = $rlDb->fetch(
            '*',
            null,
            "WHERE FIND_IN_SET(`ID`, '{$payout['Deals_IDs']}') ORDER BY `Date` DESC",
            null,
            'affiliate'
        );

        foreach ($deals as $deal) {
            if ($deal['Type'] == 'listing') {
                // build listing url
                $listing_url                          = $reefless->getListingUrl((int) $deal['Item_ID']);
                $payout['Deals'][$deal['ID']]['Item'] = '<a href="' . $listing_url . '">' . $lang['aff_details_item'] . ' #' . $deal['Item_ID'] . '</a>';

                // get plan name
                $plan                                 = $rlPlan->getPlan($deal['Plan_ID']);
                $payout['Deals'][$deal['ID']]['Plan'] = $plan['name'] ? $plan['name'] : $lang['not_available'];

                // get posted date
                $payout['Deals'][$deal['ID']]['Posted'] = $rlDb->getOne('Date', "`ID` = '{$deal['Item_ID']}'", 'listings');
            } else {
                $referral                             = $rlAccount->getProfile((int) $deal['Referral_ID']);
                $payout['Deals'][$deal['ID']]['Item'] = trim($referral['Full_name']);

                // get created date
                $payout['Deals'][$deal['ID']]['Posted'] = $deal['Date'];
            }

            // build description
            $payout['Deals'][$deal['ID']]['Description'] = "<table><tr><td><b>{$lang['aff_commissions_type']}:</b> ";
            if ($deal['Type'] == 'listing') {
                $payout['Deals'][$deal['ID']]['Description'] .= $lang['listing'];
            } elseif ($deal['Type'] == 'register') {
                $payout['Deals'][$deal['ID']]['Description'] .= $lang['aff_referral_user'];
            } elseif ($deal['Type'] == 'membership') {
                $payout['Deals'][$deal['ID']]['Description'] .= $lang['aff_type_membership'];
            }
            $payout['Deals'][$deal['ID']]['Description'] .= "</td></tr>";
            if ($deal['Type'] == 'listing') {
                $payout['Deals'][$deal['ID']]['Description'] .= "<tr><td><b>{$lang['plan']}:</b> {$payout['Deals'][$deal['ID']]['Plan']}</td></tr>";
            }
            $payout['Deals'][$deal['ID']]['Description'] .= "</table>";

            // get commission
            $payout['Deals'][$deal['ID']]['Commission'] = $config['system_currency_position'] == 'before'
            ? $config['system_currency'] . ' ' . $deal['Commission']
            : $deal['Commission'] . ' ' . $config['system_currency'];
        }

        $rlSmarty->assign_by_ref('payout', $payout);

        // show breadcrumbs in page
        $bread_crumbs[]    = array('name' => $lang['aff_payout_details']);
        $page_info['name'] = $lang['aff_payout_details'];
    }

    /**
     * Get info about commissions
     */
    public function getCommissions()
    {
        global $config, $account_info, $lang, $pages, $reefless, $rlSmarty, $rlPlan, $rlAccount, $rlDb;

        if (!defined('IS_LOGIN') || !$account_info['ID'] || !$config['affiliate_module']) {
            return;
        }

        $reefless->loadClass('Plan');

        // get current page
        $pInfo['current'] = (int) $_GET['pg'];

        // define start position
        $limit              = (int) $config['aff_items_per_page'] > 0 ? $config['aff_items_per_page'] : 10;
        $start              = $pInfo['current'] > 1 ? ($pInfo['current'] - 1) * $limit : 0;
        $aff_pending_period = (int) $config['aff_pending_period'] > 0 ? $config['aff_pending_period'] : 30;

        $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.*, `T1`.`Date` + INTERVAL {$aff_pending_period} DAY AS `Deposit_date` ";
        $sql .= "FROM `{db_prefix}affiliate` AS `T1` ";
        $sql .= "WHERE `T1`.`Affiliate_ID` = {$account_info['ID']} ";
        $sql .= "AND `Type` <> 'visit' ";
        $sql .= "ORDER BY `T1`.`Date` DESC ";
        $sql .= "LIMIT {$start}, {$limit}";
        $commissions = $rlDb->getAll($sql);

        // count total entries of commissions
        $calc          = $rlDb->getRow("SELECT FOUND_ROWS() AS `calc`");
        $pInfo['calc'] = $calc['calc'];
        $rlSmarty->assign_by_ref('pInfo', $pInfo);

        foreach ($commissions as &$commission) {
            $commission['Status'] = (float) $commission['Commission']
            ? $lang['aff_status_' . $commission['Status']]
            : $lang['aff_status_not_cashing'];

            // get plan name
            $plan               = $rlPlan->getPlan($commission['Plan_ID']);
            $commission['Plan'] = $plan['name'] ? $plan['name'] : $lang['not_available'];
            unset($plan);

            // build description
            $commission['Description'] = "<table><tr><td><b>{$lang['aff_commissions_type']}:</b> ";
            if ($commission['Type'] == 'listing') {
                $commission['Description'] .= $lang['listing'];
            } elseif ($commission['Type'] == 'register') {
                $commission['Description'] .= $lang['aff_referral_user'];
            } elseif ($commission['Type'] == 'membership') {
                $commission['Description'] .= $lang['aff_type_membership'];
            }
            $commission['Description'] .= "</td></tr>";

            if ($commission['Type'] == 'listing') {
                $commission['Description'] .= "<tr><td><b>{$lang['plan']}:</b> {$commission['Plan']}</td></tr>";
            }

            $commission['Description'] .= "</table>";

            if ($commission['Type'] == 'listing') {
                // build listing url
                $listing_url        = $reefless->getListingUrl((int) $commission['Item_ID']);
                $commission['Item'] = '<a target="_blank" href="' . $listing_url . '">' . $lang['aff_details_item'] .
                    ' #' . $commission['Item_ID'] . '</a>';
            } else {
                $referral           = $rlAccount->getProfile((int) $commission['Referral_ID']);
                $commission['Item'] = trim($referral['Full_name']);
            }

            $commission['Commission'] = $config['system_currency_position'] == 'before'
            ? ($config['system_currency'] . ' ' . $commission['Commission'])
            : ($commission['Commission'] . ' ' . $config['system_currency']);
        }

        $rlSmarty->assign_by_ref('commissions', $commissions);
    }

    /**
     * Get banners
     */
    public function getBanners()
    {
        global $rlSmarty, $config, $account_info, $lang, $rlDb;

        if (!defined('IS_LOGIN') || !$config['affiliate_module'] || $account_info['Type'] != 'affiliate') {
            return;
        }

        // get current page
        $pInfo['current'] = (int) $_GET['pg'];

        // define start position
        $limit = (int) $config['aff_items_per_page'] > 0 ? $config['aff_items_per_page'] : 10;
        $start = $pInfo['current'] > 1 ? ($pInfo['current'] - 1) * $limit : 0;

        $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.* ";
        $sql .= "FROM `{db_prefix}aff_banners` AS `T1` ";
        $sql .= "WHERE `T1`.`Status` = 'active' ";
        $sql .= "ORDER BY `T1`.`ID` DESC ";
        $sql .= "LIMIT {$start}, {$limit}";
        $banners = $rlDb->getAll($sql);

        foreach ($banners as &$banner) {
            $banner['Name']          = $lang['aff_banner_' . $banner['Key']];
            $banner['Image_URL']     = RL_FILES_URL . 'aff_images/' . $banner['Image'];
            $banner['Affiliate_URL'] = RL_URL_HOME . '?aff=' . $account_info['ID'] . '&b=' . $banner['ID'];
        }

        $rlSmarty->assign_by_ref('banners', $banners);

        // count total traffic
        $calc          = $rlDb->getRow("SELECT FOUND_ROWS() AS `calc`");
        $pInfo['calc'] = $calc['calc'];
        $rlSmarty->assign_by_ref('pInfo', $pInfo);
    }

    /**
     * Get info about affiliate events (to Manager in Admin side)
     */
    public function getAffiliateEvents()
    {
        global $reefless, $rlAccount, $lang, $config, $rlDb;

        // data read
        $limit = (int) $_GET['limit'];
        $start = (int) $_GET['start'];

        if (!is_numeric($start) || !is_numeric($limit) || !defined('REALM')) {
            return;
        }

        // add filters
        foreach ($_GET as $filter => $val) {
            switch ($filter) {
                case 'Affiliate':
                    if ($val) {
                        $join .= "LEFT JOIN `{db_prefix}accounts` AS `Affiliates` ON `T1`.`Affiliate_ID` = `Affiliates`.`ID` ";
                        $where .= "`Affiliates`.`Username` LIKE '{$val}' AND ";
                    }
                    break;

                case 'Referral':
                    if ($val) {
                        $join .= "LEFT JOIN `{db_prefix}accounts` AS `Referrals` ON `T1`.`Referral_ID` = `Referrals`.`ID` ";
                        $where .= "`Referrals`.`Username` LIKE '{$val}' AND ";
                    }
                    break;

                case 'date_from':
                    if ($val) {
                        $where .= "UNIX_TIMESTAMP(DATE(`T1`.`Date`)) >= UNIX_TIMESTAMP('{$val}') AND ";
                    }
                    break;

                case 'date_to':
                    if ($val) {
                        $where .= "UNIX_TIMESTAMP(DATE(`T1`.`Date`)) <= UNIX_TIMESTAMP('{$val}') AND ";
                    }
                    break;

                case 'event_type':
                    if ($val) {
                        $where .= "`T1`.`Type` LIKE '{$val}' AND ";
                    }
                    break;

                case 'status':
                    if ($val) {
                        $where .= "`T1`.`Status` LIKE '{$val}' AND ";
                    }
                    break;
            }
        }

        // remote sorting from grid
        $sort_field = $_GET['sort'];
        $sort_type  = $_GET['dir'];

        $reefless->loadClass('Account');
        $reefless->loadClass('Plan');
        $reefless->loadClass('MembershipPlansAdmin', 'admin');

        // get affiliate events by limit
        $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.*, `T1`.`Status` AS `Aff_status`, `T1`.`Type` AS `Aff_type` ";
        $sql .= "FROM `{db_prefix}affiliate` AS `T1` ";

        $sql = $join ? $sql . $join : $sql;
        $sql = $where ? $sql . "WHERE " . rtrim($where, 'AND ') : $sql;

        $sql .= "ORDER BY ";

        // remote sorting from grid
        if ($sort_field && $sort_type) {
            if ($sort_field == 'Plan') {
                $sort_field = 'Plan_ID';
            } elseif ($sort_field == 'Affiliate') {
                $sort_field = 'Affiliate_ID';
            } elseif ($sort_field == 'Referral') {
                $sort_field = 'Referral_ID';
            } elseif ($sort_field == 'Aff_status') {
                $sort_field = 'Status';
            } elseif ($sort_field == 'Location') {
                $sort_field = 'Country_name';
            }

            $sql .= "`T1`.`{$sort_field}` {$sort_type} ";
        } else {
            $sql .= "`T1`.`Date` DESC ";
        }

        $sql .= "LIMIT {$start}, {$limit}";
        $data = $rlDb->getAll($sql);

        // count total events
        $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

        foreach ($data as &$value) {
            // get affiliate full name
            $affiliate          = $rlAccount->getProfile((int) $value['Affiliate_ID']);
            $value['Affiliate'] = RL_URL_HOME . ADMIN . '/index.php?controller=accounts&action=view&userid=' . $affiliate['ID'];
            $value['Affiliate'] = '<a target="_blank" alt="' . $lang['view_account'] . '" title="' . $lang['view_account'] . '" href="' . $value['Affiliate'] . '">' . $affiliate['Full_name'] . '</a>';

            // get referral full name
            $referral          = $rlAccount->getProfile((int) $value['Referral_ID']);
            $value['Referral'] = $referral['ID'] ? RL_URL_HOME . ADMIN . '/index.php?controller=accounts&action=view&userid=' . $referral['ID'] : '';
            $value['Referral'] = $value['Referral'] ? '<a target="_blank" alt="' . $lang['view_account'] . '" title="' . $lang['view_account'] . '" href="' . $value['Referral'] . '">' . $referral['Full_name'] . '</a>' : $lang['website_visitor'];

            // collect location info
            if ($value['Country_name']) {
                $value['Location'] = $value['Country_name'];
            }
            if ($value['Region']) {
                $value['Location'] = $value['Location'] ? $value['Location'] . ', ' . $value['Region'] : $value['Region'];
            }
            if ($value['City']) {
                $value['Location'] = $value['Location'] ? $value['Location'] . ', ' . $value['City'] : $value['City'];
            }

            if ($value['Aff_type'] == 'listing') {
                // get plan name
                $plan          = $GLOBALS['rlPlan']->getPlan($value['Plan_ID']);
                $value['Plan'] = $plan['name'] ? $plan['name'] : $lang['not_available'];

                // build plan url
                $url           = RL_URL_HOME . ADMIN . '/index.php?controller=listing_plans&action=edit&plan=' . $plan['Key'];
                $link          = "<a target=\"_blank\" title=\"{$lang['view_details']}\" href=\"{$url}\">{$value['Plan']}</a>";
                $value['Plan'] = $plan['ID'] ? $link : $value['Plan'];
            } elseif ($value['Aff_type'] == 'membership') {
                // get plan name
                $plan          = $GLOBALS['rlMembershipPlansAdmin']->getPlan($value['Plan_ID']);
                $value['Plan'] = $plan['name'] ? $plan['name'] : $lang['not_available'];

                // build plan url
                $url           = RL_URL_HOME . ADMIN . '/index.php?controller=membership_plans&action=edit&plan=' . $plan['Key'];
                $link          = "<a target=\"_blank\" title=\"{$lang['view_details']}\" href=\"{$url}\">{$value['Plan']}</a>";
                $value['Plan'] = $plan['ID'] ? $link : $value['Plan'];
            } else {
                $value['Plan'] = $lang['not_available'];
            }

            // build commission for Listing type of event
            if ($value['Type'] == 'listing') {
                // build listing url
                if ($value['Item_ID'] && $rlDb->getOne('ID', "`ID` = '{$value['Item_ID']}'", 'listings')) {
                    $value['Type'] = '<a target="_blank" alt="' . $lang['view_details'] . '" title="' . $lang['view_details'] . '" href="' . RL_URL_HOME . ADMIN . '/index.php?controller=listings&action=view&id=' . $value['Item_ID'] . '">' . ($lang['aff_type_' . $value['Type']] ? $lang['aff_type_' . $value['Type']] : $value['Type']) . '</a>';
                }
            }

            if ($value['Type'] != 'visit') {
                $value['Commission'] = $config['system_currency_position'] == 'before'
                ? ($config['system_currency'] . ' ' . $value['Commission'])
                : ($value['Commission'] . ' ' . $config['system_currency']);
            } else {
                $value['Commission'] = $lang['not_available'];
            }

            $value['Type'] = $lang['aff_type_' . $value['Type']]
            ? $lang['aff_type_' . $value['Type']]
            : $value['Type'];

            $value['Status'] = $lang['aff_status_' . $value['Status']]
            ? $lang['aff_status_' . $value['Status']]
            : $value['Status'];
        }

        return array('data' => $data, 'count' => $count['count']);
    }

    /**
     * Get info about payout (admin side)
     */
    public function getApPayoutDetails()
    {
        global $reefless, $rlSmarty, $rlAccount, $rlPlan, $lang, $config, $rlDb;
        // payout ID
        $id = (int) $_GET['id'];

        if (!is_numeric($id) || $_GET['mode'] != 'payouts' || $_GET['action'] != 'view' || !defined('REALM')) {
            return;
        }

        $reefless->loadClass('Account');
        $reefless->loadClass('Plan');

        // get payout info
        $payout = $rlDb->fetch('*', array('ID' => $id, 'Status' => 'deposited'), null, null, 'aff_payouts', 'row');

        // get affiliate info
        $affiliate               = $rlAccount->getProfile((int) $payout['Affiliate_ID']);
        $payout['Aff_Full_name'] = trim($affiliate['Full_name']);

        // count the number of deals
        $payout['Count_deals'] = count(explode(',', $payout['Deals_IDs']));

        // count amount by payout
        $payout['Amount'] = $rlDb->getRow("
            SELECT SUM(`Commission`) AS `Commission` FROM `{db_prefix}affiliate`
            WHERE `Commission` <> '' AND `Commission` > 0 AND `Affiliate_ID` = '{$payout['Affiliate_ID']}'
            AND FIND_IN_SET(`ID`, '{$payout['Deals_IDs']}')
        ");
        $payout['Amount']['Commission'] = $payout['Amount']['Commission'] > 0 ? $payout['Amount']['Commission'] : '0.00';
        $payout['Amount']               = $config['system_currency_position'] == 'before'
        ? ($config['system_currency'] . ' ' . $payout['Amount']['Commission'])
        : ($payout['Amount']['Commission'] . ' ' . $config['system_currency']);

        // get info about each of deals
        $deals = $rlDb->fetch(
            '*',
            null,
            "WHERE FIND_IN_SET(`ID`, '{$payout['Deals_IDs']}') ORDER BY `Date` DESC",
            null,
            'affiliate'
        );

        foreach ($deals as $deal) {
            if ($deal['Type'] == 'listing') {
                // build listing url
                $payout['Deals'][$deal['ID']]['Item'] = '<a target="_blank" href="' . RL_URL_HOME . ADMIN . '/index.php?controller=listings&action=view&id=' . $deal['Item_ID'] . '">' . $lang['aff_details_item'] . ' #' . $deal['Item_ID'] . "</a>";

                // get plan name
                $plan                                 = $rlPlan->getPlan($deal['Plan_ID']);
                $payout['Deals'][$deal['ID']]['Plan'] = $plan['name'] ? $plan['name'] : $lang['not_available'];
                $payout['Deals'][$deal['ID']]['Plan'] = $plan['ID'] ? ('<a target="_blank" alt="' . $lang['view_details'] . '" title="' . $lang['view_details'] . '" href="' . RL_URL_HOME . ADMIN . '/index.php?controller=listing_plans&action=edit&plan=' . $plan['Key'] . '">' . $payout['Deals'][$deal['ID']]['Plan'] . '</a>') : $payout['Deals'][$deal['ID']]['Plan'];

                // get posted date
                $payout['Deals'][$deal['ID']]['Posted'] = $rlDb->getOne('Date', "`ID` = '{$deal['Item_ID']}'", 'listings');
            } else {
                $type_phrase = $deal['Type'] == 'membership' ? 'aff_type_membership' : 'aff_referral_user';

                $referral                             = $rlAccount->getProfile((int) $deal['Referral_ID']);
                $payout['Deals'][$deal['ID']]['Item'] = $referral['Full_name'] . ' (' . $lang[$type_phrase] . ')';

                // get created date
                $payout['Deals'][$deal['ID']]['Posted'] = $deal['Date'];
            }

            // get commission
            $payout['Deals'][$deal['ID']]['Commission'] = $config['system_currency_position'] == 'before'
            ? $config['system_currency'] . ' ' . $deal['Commission']
            : $deal['Commission'] . ' ' . $config['system_currency'];
        }

        $rlSmarty->assign_by_ref('payout', $payout);
    }

    /**
     * Get info about affiliate banners (to Manager in Admin side)
     */
    public function getAffiliateBanners()
    {
        global $reefless, $lang, $rlDb;

        // data read
        $limit = (int) $_GET['limit'];
        $start = (int) $_GET['start'];

        if (!is_numeric($start) || !is_numeric($limit) || !defined('REALM')) {
            return;
        }

        // get affiliate banners by limit
        $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.* ";
        $sql .= "FROM `{db_prefix}aff_banners` AS `T1` ";
        $sql .= "ORDER BY `T1`.`ID` DESC ";
        $sql .= "LIMIT {$start}, {$limit}";
        $data = $rlDb->getAll($sql);

        // count total banners
        $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

        foreach ($data as &$value) {
            $value['Name'] = $lang['aff_banner_' . $value['Key']]
            ? $lang['aff_banner_' . $value['Key']]
            : $lang['not_available'];
            $value['Size']   = $value['Width'] . ' x ' . $value['Height'] . $lang['aff_banner_size_px'];
            $value['Status'] = $lang[$value['Status']];
        }

        return array('data' => $data, 'count' => $count['count']);
    }

    /**
     * Add a new banner
     */
    public function addBanner()
    {
        global $reefless, $rlCrop, $rlResize, $rlDb, $rlNotice, $rlValid, $controller, $allLangs, $lang, $config, $errors, $error_fields;

        if (!$config['affiliate_module']) {
            return false;
        }

        $name   = $_POST['name'];
        $width  = (int) $_POST['width'];
        $height = (int) $_POST['height'];
        $status = $_POST['status'];
        $image  = array(
            'Name' => $_FILES['image']['name'],
            'Path' => $_FILES['image']['tmp_name'],
            'Type' => $_FILES['image']['type'],
        );

        // check name
        if ($name) {
            foreach ($allLangs as $lang_item) {
                $names[$lang_item['Code']] = $name[$lang_item['Code']];
            }

            // build key of banner
            $key = $rlValid->str2key(reset($name));
        }

        // check banner size
        if (!$width) {
            $errors[]       = str_replace('{field}', "<b>" . $lang['aff_banner_width'] . "</b>", $lang['notice_field_empty']);
            $error_fields[] = 'width';
        }
        if (!$height) {
            $errors[]       = str_replace('{field}', "<b>" . $lang['aff_banner_height'] . "</b>", $lang['notice_field_empty']);
            $error_fields[] = 'height';
        }

        // check if GIF animated
        if ($this->isAnimatedGif($image['Path'])) {
            $gif_size   = getimagesize($image['Path']);
            $gif_width  = $gif_size[0];
            $gif_height = $gif_size[1];

            if ($gif_width !== $width || $gif_height !== $height) {
                $errors[]       = $lang['aff_banner_gif_wrong_size'];
                $error_fields[] = 'image';
            } else {
                $animated_banner = true;
            }
        }

        // check banner image
        if (!$image['Name']) {
            $errors[]       = str_replace('{field}', "<b>" . $lang['type_image'] . "</b>", $lang['notice_field_empty']);
            $error_fields[] = 'image';
        } else {
            $allowed_types = array(
                'image/jpeg',
                'image/pjpeg',
                'image/png',
                'image/x-png',
                'image/gif',
            );

            if (!$errors && $name && $width && $height) {
                if (!in_array($image['Type'], $allowed_types)) {
                    $errors[]       = $lang['aff_banner_image_desc'];
                    $error_fields[] = 'image';
                } else {
                    $img_ext  = end(explode('.', $image['Name']));
                    $img_name = 'aff_' . ($key ? $key . '_' : '') . time() . mt_rand() . '.' . $img_ext;

                    $dir = RL_FILES . 'aff_images' . RL_DS;
                    $reefless->rlMkdir($dir);

                    $img_location = $dir . $img_name;

                    // upload animated GIF image
                    if ($animated_banner) {
                        copy($image['Path'], $img_location);
                    }
                    // upload/crop/resize simple image
                    else {
                        if (move_uploaded_file($image['Path'], $img_location)) {
                            $reefless->loadClass('Crop');
                            $reefless->loadClass('Resize');

                            $rlCrop->loadImage($img_location);
                            $rlCrop->cropBySize($width, $height, ccCENTER);
                            $rlCrop->saveImage($img_location, $config['img_quality']);
                            $rlCrop->flushImages();

                            $rlResize->resize($img_location, $img_location, 'C', array($width, $height), true, false);
                        }
                    }

                    // insert banner info to database
                    $insert = array(
                        'Key'    => $key ? $key : '',
                        'Width'  => $width,
                        'Height' => $height,
                        'Image'  => $img_name,
                        'Status' => $status,
                    );

                    // redirect to banners manager
                    if ($rlDb->insertOne($insert, 'aff_banners')) {
                        // save banner phrases
                        if ($names) {
                            foreach ($allLangs as $lang_item) {
                                $lang_keys[] = array(
                                    'Code'   => $lang_item['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'aff_banner_' . $key,
                                    'Value'  => $names[$lang_item['Code']],
                                    'Plugin' => 'affiliate',
                                );
                            }

                            $rlDb->insert($lang_keys, 'lang_keys');
                        }

                        $reefless->loadClass('Notice');
                        $rlNotice->saveNotice($lang['aff_banner_added']);
                        $reefless->redirect(array("controller" => $controller . '&mode=banners'));
                    }
                }
            }
        }

        return true;
    }

    /**
     * Edit a banner
     */
    public function editBanner()
    {
        global $reefless, $rlCrop, $rlResize, $rlNotice, $rlValid, $controller, $allLangs, $lang, $config, $errors,
        $error_fields, $banner_info, $rlDb;

        if (!$config['affiliate_module']) {
            return false;
        }

        $name           = $_POST['name'];
        $width          = (int) $_POST['width'];
        $height         = (int) $_POST['height'];
        $removed_banner = (bool) $_POST['removed_banner'];
        $status         = $_POST['status'];
        $image          = array(
            'Name' => $_FILES['image']['name'],
            'Path' => $_FILES['image']['tmp_name'],
            'Type' => $_FILES['image']['type'],
        );

        // check name
        if ($name) {
            foreach ($allLangs as $lang_item) {
                $names[$lang_item['Code']] = $name[$lang_item['Code']];
            }
        }

        // check banner size
        if (!$width) {
            $errors[]       = str_replace('{field}', "<b>" . $lang['aff_banner_width'] . "</b>", $lang['notice_field_empty']);
            $error_fields[] = 'width';
        }
        if (!$height) {
            $errors[]       = str_replace('{field}', "<b>" . $lang['aff_banner_height'] . "</b>", $lang['notice_field_empty']);
            $error_fields[] = 'height';
        }

        // check if GIF animated
        if ($this->isAnimatedGif($image['Path'])) {
            $gif_size   = getimagesize($image['Path']);
            $gif_width  = $gif_size[0];
            $gif_height = $gif_size[1];

            if ($gif_width !== $width || $gif_height !== $height) {
                $errors[]       = $lang['aff_banner_gif_wrong_size'];
                $error_fields[] = 'image';
            } else {
                $animated_banner = true;
            }
        }

        // check banner image
        if (!$image['Name'] && $removed_banner) {
            $errors[]       = str_replace('{field}', "<b>" . $lang['type_image'] . "</b>", $lang['notice_field_empty']);
            $error_fields[] = 'image';
        } else {
            if (!$errors && $width && $height) {
                // generate Key or use exist
                if ($banner_info['Key'] || $rlValid->str2key(reset($name))) {
                    $key = $banner_info['Key'] ? $banner_info['Key'] : $key = $rlValid->str2key(reset($name));
                }

                if ($removed_banner) {
                    $allowed_types = array(
                        'image/jpeg',
                        'image/pjpeg',
                        'image/png',
                        'image/x-png',
                        'image/gif',
                    );

                    if (!in_array($image['Type'], $allowed_types)) {
                        $errors[]       = $lang['aff_banner_image_desc'];
                        $error_fields[] = 'image';
                    } else {
                        $img_ext  = end(explode('.', $image['Name']));
                        $img_name = 'aff_' . ($key ? $key . '_' : '') . time() . mt_rand() . '.' . $img_ext;

                        $dir = RL_FILES . 'aff_images' . RL_DS;
                        $reefless->rlMkdir($dir);

                        $img_location = $dir . $img_name;

                        // upload animated GIF image
                        if ($animated_banner) {
                            copy($image['Path'], $img_location);
                        }
                        // upload/crop/resize simple image
                        else {
                            if (move_uploaded_file($image['Path'], $img_location)) {
                                $reefless->loadClass('Crop');
                                $reefless->loadClass('Resize');

                                $rlCrop->loadImage($img_location);
                                $rlCrop->cropBySize($width, $height, ccCENTER);
                                $rlCrop->saveImage($img_location, $config['img_quality']);
                                $rlCrop->flushImages();

                                $rlResize->resize($img_location, $img_location, 'C', array($width, $height), true, false);
                            }
                        }

                        // remove old banner
                        unlink(RL_FILES . 'aff_images/' . $banner_info['Image']);
                    }
                }

                if (!$errors) {
                    // update banner info in database
                    $update = array(
                        'fields' => array(
                            'Key'    => $key && $rlValid->str2key(reset($name)) ? $key : '',
                            'Width'  => $width,
                            'Height' => $height,
                            'Status' => $status,
                        ),
                        'where'  => array(
                            'ID' => $banner_info['ID'],
                        ),
                    );

                    if ($removed_banner) {
                        $update['fields']['Image'] = $img_name;
                    }

                    // redirect to banners manager
                    if ($rlDb->updateOne($update, 'aff_banners')) {
                        // update phrases
                        if ($names) {
                            foreach ($allLangs as $lang_item) {
                                if ($rlDb->getOne(
                                    'ID',
                                    "`Key` = 'aff_banner_{$key}' AND `Code` = '{$lang_item['Code']}' AND `Status` = 'active'",
                                    'lang_keys')
                                ) {
                                    // edit names
                                    $update_names = array(
                                        'fields' => array(
                                            'Value' => $names[$lang_item['Code']],
                                        ),
                                        'where'  => array(
                                            'Code' => $lang_item['Code'],
                                            'Key'  => 'aff_banner_' . $key,
                                        ),
                                    );
                                    $rlDb->updateOne($update_names, 'lang_keys');
                                } else {
                                    // insert names
                                    $insert_names = array(
                                        'Code'   => $lang_item['Code'],
                                        'Module' => 'common',
                                        'Key'    => 'aff_banner_' . $key,
                                        'Plugin' => 'affiliate',
                                        'Value'  => $names[$lang_item['Code']],
                                        'Status' => 'active',
                                    );
                                    $rlDb->insertOne($insert_names, 'lang_keys');
                                }
                            }
                        } elseif ($banner_info['Key'] && !$rlValid->str2key(reset($name))) {
                            $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `ID` = {$banner_info['ID']} LIMIT 1");
                        }

                        $reefless->loadClass('Notice');
                        $rlNotice->saveNotice($lang['aff_banner_edited']);
                        $reefless->redirect(array("controller" => $controller . '&mode=banners'));
                    }
                }
            }
        }

        return true;
    }

    /**
     * Delete affiliate banner
     *
     * @since 1.1.0 - Package changed from xAjax to ajax
     * @package ajax
     *
     * @param int $id
     */
    public function ajaxDeleteBanner($id = 0)
    {
        global $lang, $rlDb;

        $id = (int) $id;

        if (!$id) {
            return array('status' => 'ERROR', 'message' => $lang['aff_remove_banner_notify_fail']);
        }

        // get banner info
        $banner_info = $rlDb->fetch('*', array('ID' => $id), null, null, 'aff_banners', 'row');

        // remove banner entry
        $rlDb->query("DELETE FROM `{db_prefix}aff_banners` WHERE `ID` = '{$id}' LIMIT 1");

        // remove banner image
        if ($banner_info['Image']) {
            unlink(RL_FILES . 'aff_images/' . $banner_info['Image']);
        }

        return array('status' => 'OK', 'message' => $lang['aff_banner_deleted']);
    }

    /**
     * @hook apAjaxRequest
     * @throws Exception
     */
    public function hookApAjaxRequest(&$out, $item)
    {
        global $reefless, $rlAccount, $rlPlan, $rlAdmin, $rlMail, $rlValid, $lang, $config, $pages, $rlDb;

        if (!$item) {
            return false;
        }

        $affiliate_ID = (int) $_REQUEST['affiliate_ID'];
        $deals_ids    = $rlValid->xSql($_REQUEST['deals_ids']);

        switch ($item) {
            case 'deposited':
                // get affiliate info
                if ($affiliate_ID && $deals_ids) {
                    $reefless->loadClass('Account');
                    $affiliate              = $rlAccount->getProfile($affiliate_ID);
                    $affiliate['Full_name'] = trim($affiliate['Full_name']);
                    $affiliate['Mail']      = '<a href="mailto:' . $affiliate['Mail'] . '">' . $affiliate['Mail'] . '</a>';
                    $out                    = $affiliate;

                    // get billing details
                    $aff_billing_details        = $rlDb->fetch('*', array('Affiliate_ID' => $affiliate_ID), null, null, 'aff_billing_details', 'row');
                    $out['aff_billing_details'] = array(
                        'type'              => $aff_billing_details['Billing_type'] ? $aff_billing_details['Billing_type'] : $lang['not_available'],
                        'paypal_email'      => $aff_billing_details['Paypal_email'],
                        'wu_country'        => $aff_billing_details['WU_country'],
                        'wu_city'           => $aff_billing_details['WU_city'],
                        'wu_fullname'       => $aff_billing_details['WU_fullname'],
                        'bank_wire_details' => $aff_billing_details['Bank_wire_details'],
                    );

                    // adapt info
                    if ($aff_billing_details['Billing_type']) {
                        // Paypal
                        if ($out['aff_billing_details']['type'] == 'paypal') {
                            $out['aff_billing_details']['Billing_type'] = $lang['payment_gateways+name+paypal'];

                            $out['aff_billing_details']['paypal_email'] = $out['aff_billing_details']['paypal_email'] ? ('<a href="mailto:' . $out['aff_billing_details']['paypal_email'] . '">' . $out['aff_billing_details']['paypal_email'] . '</a>') : '';
                        }

                        // Western Union
                        if ($out['aff_billing_details']['type'] == 'western_union') {
                            $out['aff_billing_details']['Billing_type'] = $lang['aff_western_union'];
                        }

                        // Bank Wire Transfer
                        if ($out['aff_billing_details']['type'] == 'bank_wire') {
                            $out['aff_billing_details']['Billing_type'] = $lang['aff_bank_wire'];
                        }
                    }

                    // get info about deals (items with Status = ready)
                    $deals = $rlDb->fetch(
                        '*',
                        array('Status' => 'ready'),
                        "AND FIND_IN_SET(`ID`, '{$deals_ids}') ORDER BY `Date` DESC",
                        null,
                        'affiliate'
                    );

                    // count the number of deals
                    $out['Count_deals'] = count($deals);

                    // count amount by payout
                    $out['Amount'] = $rlDb->getRow("
                        SELECT SUM(`Commission`) AS `Commission` FROM `{db_prefix}affiliate`
                        WHERE `Commission` <> '' AND `Commission` > 0 AND `Affiliate_ID` = {$affiliate_ID}
                        AND FIND_IN_SET(`ID`, '{$deals_ids}')
                    ");
                    $out['Amount']['Commission'] = $out['Amount']['Commission'] > 0 ? $out['Amount']['Commission'] : '0.00';

                    $out['Amount'] = $config['system_currency_position'] == 'before'
                    ? ($config['system_currency'] . ' ' . $out['Amount']['Commission'])
                    : ($out['Amount']['Commission'] . ' ' . $config['system_currency']);

                    $reefless->loadClass('Plan');

                    foreach ($deals as $deal_key => $deal) {
                        if ($deal['Type'] == 'listing') {
                            // get plan name
                            $plan                            = $rlPlan->getPlan($deal['Plan_ID']);
                            $out['Deals'][$deal_key]['Plan'] = $plan['name'] ? $plan['name'] : $lang['not_available'];
                            $out['Deals'][$deal_key]['Plan'] = $plan['ID'] ? ('<a target="_blank" alt="' . $lang['view_details'] . '" title="' . $lang['view_details'] . '" href="' . RL_URL_HOME . ADMIN . '/index.php?controller=listing_plans&action=edit&plan=' . $plan['Key'] . '">' . $out['Deals'][$deal_key]['Plan'] . '</a>') : $out['Deals'][$deal_key]['Plan'];

                            // get posted date
                            $posted_date = $rlDb->getOne('Date', "`ID` = {$deal['Item_ID']}", 'listings');
                            if ($posted_date) {
                                $date = new DateTimeImmutable($posted_date, new DateTimeZone($config['timezone']));
                                $posted_date = date(str_replace(['%', 'b'], ['', 'M'], RL_DATE_FORMAT), $date->getTimestamp());

                                // build listing url
                                $out['Deals'][$deal_key]['Item'] = '<a target="_blank" href="' . RL_URL_HOME . ADMIN;
                                $out['Deals'][$deal_key]['Item'] .= '/index.php?controller=listings&action=view&id=';
                                $out['Deals'][$deal_key]['Item'] .= $deal['Item_ID'] . '">' . $lang['aff_details_item'];
                                $out['Deals'][$deal_key]['Item'] .= ' #' . $deal['Item_ID'] . "</a>";
                            } else {
                                $posted_date = $lang['not_available'];

                                $out['Deals'][$deal_key]['Item'] = $lang['aff_details_item'] . ' #' . $deal['Item_ID'];
                            }
                            $out['Deals'][$deal_key]['Posted'] = $posted_date;
                        } else {
                            $referral = $rlAccount->getProfile((int) $deal['Referral_ID']);

                            $out['Deals'][$deal_key]['Plan'] = $lang['not_available'];

                            $type_phrase = $deal['Type'] == 'membership' ? 'aff_type_membership' : 'aff_referral_user';

                            // adapt by Date format
                            $registered_date = $deal['Date'];
                            if ($registered_date) {
                                $date = new DateTimeImmutable($registered_date, new DateTimeZone($config['timezone']));
                                $registered_date = date(str_replace(['%', 'b'], ['', 'M'], RL_DATE_FORMAT), $date->getTimestamp());

                                // build account url
                                if ($referral['Full_name']) {
                                    $out['Deals'][$deal_key]['Item'] = '<a target="_blank" href="' . RL_URL_HOME . ADMIN;
                                    $out['Deals'][$deal_key]['Item'] .= '/index.php?controller=accounts&action=view&userid=';
                                    $out['Deals'][$deal_key]['Item'] .= $deal['Referral_ID'] . '">' . $referral['Full_name'];
                                    $out['Deals'][$deal_key]['Item'] .= "</a>" . " (" . $lang[$type_phrase] . ")";
                                } else {
                                    $out['Deals'][$deal_key]['Item'] = $lang['not_available'];
                                    $out['Deals'][$deal_key]['Item'] .= ' (' . $lang[$type_phrase] . ')';
                                }
                            } else {
                                $registered_date = $lang['not_available'];

                                $out['Deals'][$deal_key]['Item'] = $lang['not_available'];
                                $out['Deals'][$deal_key]['Item'] .= ' (' . $lang[$type_phrase] . ')';
                            }
                            $out['Deals'][$deal_key]['Posted'] = $registered_date;
                        }

                        // get commission
                        $out['Deals'][$deal_key]['Commission'] = $config['system_currency_position'] == 'before'
                        ? $config['system_currency'] . ' ' . $deal['Commission']
                        : $deal['Commission'] . ' ' . $config['system_currency'];
                    }
                }
                break;

            case 'deposited_action':
            case 'refused_action':
                // get affiliate info
                if ($affiliate_ID && $deals_ids) {
                    // get info about deals
                    $deals = $rlDb->fetch('*', array('Status' => 'ready'), "AND FIND_IN_SET(`ID`, '{$deals_ids}') ORDER BY `Date` DESC", null, 'affiliate');

                    foreach ($deals as $deal) {
                        $update[] = array(
                            'fields' => array(
                                'Status' => $item == 'deposited_action' ? 'deposited' : 'refused',
                            ),
                            'where'  => array(
                                'ID' => $deal['ID'],
                            ),
                        );
                    }
                    $rlDb->update($update, 'affiliate');

                    // insert new payout
                    $insert_payout = array(
                        'Affiliate_ID' => $affiliate_ID,
                        'Deals_IDs'    => $deals_ids,
                        'Date'         => 'NOW()',
                        'Status'       => $item == 'deposited_action' ? 'deposited' : 'refused',
                    );
                    $rlDb->insertOne($insert_payout, 'aff_payouts');

                    // send email to affiliate
                    $reefless->loadClass('Mail');
                    $reefless->loadClass('Account');

                    // get affiliate info
                    $affiliate = $rlAccount->getProfile($affiliate_ID);

                    // get all pages keys/paths
                    $pages = $GLOBALS['pages'] = $rlAdmin->getAllPages();

                    if ($item == 'deposited_action') {
                        // build link to Payment History page
                        $payment_history_url = $reefless->getPageUrl('aff_payment_history');
                        $payment_history_url = '<a href="' . $payment_history_url . '">' . $lang['pages+name+aff_payment_history'] . '</a>';

                        $mail_tpl         = $rlMail->getEmailTemplate('affiliate_commissions_deposited', $affiliate['Lang']);
                        $mail_tpl['body'] = str_replace(
                            array('{user}', '{link}'),
                            array(trim($affiliate['Full_name']), $payment_history_url),
                            $mail_tpl['body']
                        );
                    } else {
                        // build link to Contact Us page
                        $contact_us_url = $reefless->getPageUrl('contact_us');
                        $contact_us_url = '<a href="' . $contact_us_url . '">' . $lang['pages+name+contact_us'] . '</a>';

                        $mail_tpl         = $rlMail->getEmailTemplate('affiliate_commissions_refused', $affiliate['Lang']);
                        $mail_tpl['body'] = str_replace(
                            array('{user}', '{contact}', '{reason}'),
                            array(trim($affiliate['Full_name']), $contact_us_url, $_REQUEST['reason']),
                            $mail_tpl['body']
                        );
                    }
                    $rlMail->send($mail_tpl, $affiliate['Mail']);

                    $out = true;
                }
                break;

            case 'ajaxDeleteAffiliateBanner':
                $out = $this->ajaxDeleteBanner($_REQUEST['id']);
                break;
        }
    }

    /**
     * @hook init
     */
    public function hookInit()
    {
        global $reefless, $rlDb, $config;

        // get affiliate ID from GET
        $affiliate_ID = (int) $_GET['aff'];

        // get banner ID (if exist) from GET */
        $banner_ID = (int) $_GET['b'];

        // Affiliate plugin enabled && found Affiliate_ID
        if ($config['affiliate_module'] && $affiliate_ID && $affiliate_ID != $_SESSION['account']['ID']) {
            // check exist affiliate account
            if ($rlDb->getOne('ID', "`ID` = '{$affiliate_ID}' AND `Type` = 'affiliate'", 'accounts')) {
                $expire_date = '+' . (intval($config['aff_cookie_time']) > 0 ? (int) $config['aff_cookie_time'] : 90) . ' days';

                $reefless->createCookie('Affiliate_ID', $affiliate_ID, strtotime($expire_date));

                // write info about new visit
                $this->createEvent($affiliate_ID, null, null, 'visit');
            }

            // increase count of clicks of current banner
            if ($banner_ID) {
                $rlDb->query("UPDATE `{db_prefix}aff_banners` SET `Clicks` = `Clicks` + 1 WHERE `ID` = {$banner_ID}");
            }
        }

        // redirect to home page
        if ($affiliate_ID && $_COOKIE['Affiliate_ID']) {
            $reefless->redirect(null, RL_URL_HOME);
        }
    }

    /**
     * @hook boot
     */
    public function hookBoot(): void
    {
        global $rlLang, $rlSmarty, $config, $account_menu, $main_menu, $account_info, $deny_pages, $blocks, $rlCommon;

        // Remove Affiliate menu for not affiliates
        if (!$config['affiliate_module'] || !$account_info || $account_info['Type'] !== 'affiliate') {
            unset($blocks['aff_menu']);
            $rlCommon->defineBlocksExist($blocks);
            $rlCommon->defineSidebarExists();
            return;
        }

        // Remove account area default box
        if ($blocks['account_area']) {
            unset($blocks['account_area']);
            $rlCommon->defineBlocksExist($blocks);
            $rlCommon->defineSidebarExists();
        }

        // Remove all items from exist account menu
        $account_menu = [];
        $affKeys      = [
            'aff_general_stats',
            'aff_commissions',
            'aff_payment_history',
            'aff_banners',
            'aff_traffic_log',
            'my_profile',
            'my_messages'
        ];

        $affPages = Util::getPages(
            ['ID', 'Page_type', 'Key', 'Path', 'Get_vars', 'Controller', 'No_follow', 'Menus', 'Deny', 'Login'],
            ['Status' => 'active'],
            "AND `Key` IN ('" . implode("', '", $affKeys) . "') ORDER BY `Position` DESC"
        );

        $menus = $rlLang->replaceLangKeys($affPages, 'pages', ['name', 'title']);
        foreach ($menus as $value) {
            // Re-generate account menu
            if (in_array(2, explode(',', $value['Menus']))
                && (!in_array($account_info['Type_ID'], explode(',', $value['Deny'])) || !$account_info['Type_ID'])
                && (!in_array($value['Key'], $deny_pages) || !$deny_pages)
            ) {
                $account_menu[] = $value;
            }
        }

        $rlSmarty->assign_by_ref('account_menu', $account_menu);

        // Remove Add-listing page from main menu
        foreach ($main_menu as $menu_key => $menu_item) {
            if ($menu_item['Controller'] == 'add_listing') {
                unset($main_menu[$menu_key]);
            }
        }

        $main_menu = array_values($main_menu);
        $rlSmarty->assign_by_ref('main_menu', $main_menu);

        // Build url to General Stats page
        $rlSmarty->assign('general_stats_url', $GLOBALS['reefless']->getPageUrl('aff_general_stats'));
    }

    /**
     * @hook pageinfoArea
     */
    public function hookPageinfoArea()
    {
        global $config, $page_info;

        if ($config['affiliate_module']) {
            // replace page data | lifehack :)
            if ($page_info['Key'] == 'aff_program_page') {
                $page_info['Page_type']  = 'system';
                $page_info['Controller'] = 'affiliate_program_page';
                $page_info['Plugin']     = 'affiliate';
            }

            if ($page_info['Key'] == 'login') {
                // detect affiliate login process via Affiliate program page
                if ($_POST['affiliate_log_form']) {
                    // logout user (if he already logged by another account type)
                    if (defined('IS_LOGIN')) {
                        unset($_SESSION['account']);

                        $_SESSION['aff_log_username']   = $_POST['username'];
                        $_SESSION['aff_log_password']   = $_POST['password'];
                        $_SESSION['affiliate_log_form'] = true;

                        $GLOBALS['reefless']->refresh();
                    } else {
                        $_SESSION['affiliate_login'] = true;
                    }
                }

                // re-login with new account data
                if ($_SESSION['affiliate_log_form']) {
                    $_POST['username']           = $_SESSION['aff_log_username'];
                    $_POST['password']           = $_SESSION['aff_log_password'];
                    $_POST['action']             = 'login';
                    $_SESSION['affiliate_login'] = true;

                    unset($_SESSION['affiliate_log_form']);
                }
            }
        }
    }

    /**
     * @hook registerSuccess
     */
    public function hookRegisterSuccess()
    {
        global $reefless, $rlAccount, $rlNotice, $config, $profile_data, $account_types, $lang;

        if (!$config['affiliate_module'] || !$profile_data['type']) {
            return;
        }

        // Register new referral account
        $affiliate_ID = (int) $_COOKIE['Affiliate_ID'];

        if ($affiliate_ID
            && $referral_ID = $GLOBALS['rlDb']->getOne('ID', "`Username` = '{$profile_data['username']}'", 'accounts')
        ) {
            $this->registerNewReferral($affiliate_ID, $referral_ID);
        }

        // Register new affiliate account
        if ($account_types[$profile_data['type']]['Key'] === 'affiliate') {
            $match_field = $config['account_login_mode'] === 'email' ? 'mail' : 'username';
            if (true === $rlAccount->login($profile_data[$match_field], $profile_data['password'])) {
                $reefless->loadClass('Notice');
                $rlNotice->saveNotice($lang['notice_logged_in']);
            }

            $reefless->redirect(null, $reefless->getPageUrl('aff_general_stats'));
        }
    }

    /**
     * @hook addListingFormDataChecking
     */
    public function hookAddListingFormDataChecking()
    {
        global $config, $lang, $rlAccount, $rlMail, $reefless;

        // replace default account type to affiliate type & save register new referral account
        if ($config['affiliate_module']) {
            // found new referral account
            $affiliate_ID = (int) $_COOKIE['Affiliate_ID'];

            if ($_SESSION['Referral_register_email'] && $affiliate_ID) {
                if ($referral_ID = $GLOBALS['rlDb']->getOne('ID', "`Mail` = '{$_SESSION['Referral_register_email']}'", 'accounts')) {
                    // commission of registered users
                    $aff_commission = (float) $config['aff_commission_by_registered'];

                    if ($aff_commission) {
                        $this->createEvent($affiliate_ID, $referral_ID, null, 'register', null, $aff_commission, 'fixed');

                        // get affiliate info
                        $affiliate = $rlAccount->getProfile((int) $affiliate_ID);

                        // get referral info
                        $referral = $rlAccount->getProfile((int) $referral_ID);

                        if ($affiliate && $referral) {
                            // build commission value
                            $commission = $config['system_currency_position'] == 'before'
                            ? ($config['system_currency'] . ' ' . $aff_commission)
                            : ($aff_commission . ' ' . $config['system_currency']);

                            // build link to General Stats page
                            $general_stats_url = $reefless->getPageUrl('aff_general_stats');
                            $general_stats_url = '<a href="' . $general_stats_url . '">' . $lang['pages+name+aff_general_stats'] . '</a>';

                            // send email to affiliate
                            $reefless->loadClass('Mail');
                            $mail_tpl         = $rlMail->getEmailTemplate('referral_user_registered', $affiliate['Lang']);
                            $mail_tpl['body'] = str_replace(
                                array(
                                    '{user}',
                                    '{ref_name}',
                                    '{commission}',
                                    '{commission_period}',
                                    '{link}',
                                ),
                                array(
                                    trim($affiliate['Full_name']),
                                    trim($referral['Full_name']),
                                    $commission,
                                    $config['aff_pending_period'],
                                    $general_stats_url,
                                ),
                                $mail_tpl['body']
                            );
                            $rlMail->send($mail_tpl, $affiliate['Mail']);
                        }
                    } else {
                        $this->createEvent($affiliate_ID, $referral_ID, null, 'register');
                    }

                    unset($_SESSION['Referral_register_email']);
                    setcookie('Affiliate_ID', null, -1, $GLOBALS['domain_info']['path'], $GLOBALS['domain_info']['domain']);
                }
            }
        }
    }

    /**
     * @hook loginSuccess
     */
    public function hookLoginSuccess()
    {
        global $reefless, $config;

        // login affiliate account via "aff program" page
        if ($config['affiliate_module'] && $_SESSION['account']['Type'] == 'affiliate') {
            // redirect to General Stats page
            $reefless->redirect(null, $reefless->getPageUrl('aff_general_stats'));
        }
    }

    /**
     * Create a new event with "listing" type
     *
     * @since 1.1.0 - Added $instanse parameter
     * @hook afterListingDone
     */
    public function hooksListingDone($instanse)
    {
        global $config, $pages, $lang, $reefless, $rlAccount, $rlMail, $rlPlan, $rlDb;

        $plan_info = $instanse->plans[$instanse->planID];

        if (!$config['affiliate_module'] || !$plan_info['ID'] || $config['membership_module']) {
            return;
        }

        $commission = (float) $rlDb->getOne('Aff_commission', "`ID` = {$plan_info['ID']}", 'listing_plans');
        $type       = $rlDb->getOne('Aff_commission_type', "`ID` = {$plan_info['ID']}", 'listing_plans');
        $listingID  = $instanse->listingID;
        $accountID  = (int) $instanse->listingData['Account_ID'];
        $planID     = (int) $plan_info['ID'];
        $plan_price = (int) $plan_info['Price'];

        // count commission by price of listing plan
        if ($plan_price && $listingID) {
            $commission = $type == 'percentage' ? round(($plan_price / 100) * $commission, 2) : $commission;
        }

        $affiliateID = $rlDb->getOne('Affiliate_ID', "`Referral_ID` = {$accountID} AND `Type` = 'register'", 'affiliate');

        if ($affiliateID && $accountID && $planID && $listingID) {
            // write info about new listing added by referral
            $this->createEvent($affiliateID, $accountID, $planID, 'listing', $listingID, $commission, $type);

            $reefless->loadClass('Account');
            $affiliate = $rlAccount->getProfile((int) $affiliateID);
            $referral  = $rlAccount->getProfile((int) $accountID);

            if ($affiliate && $referral) {
                // build listing url
                $listing_url = $reefless->getListingUrl((int) $listingID);
                $listing_url = '<a href="' . $listing_url . '">' . $listing_url . '</a>';

                // build commission value
                $commission = $config['system_currency_position'] == 'before'
                ? ($config['system_currency'] . ' ' . $commission)
                : ($commission . ' ' . $config['system_currency']);

                // build link to General Stats page
                $stats_url = $reefless->getPageUrl('aff_general_stats');
                $stats_url = '<a href="' . $stats_url . '">' . $lang['pages+name+aff_general_stats'] . '</a>';

                // send email to affiliate
                $reefless->loadClass('Mail');
                $mail_tpl         = $rlMail->getEmailTemplate('referral_listing_added', $affiliate['Lang']);
                $mail_tpl['body'] = str_replace(
                    array(
                        '{user}',
                        '{ref_name}',
                        '{listing_url}',
                        '{commission}',
                        '{commission_period}',
                        '{link}',
                    ),
                    array(
                        $affiliate['Full_name'],
                        $referral['Full_name'],
                        $listing_url,
                        $commission,
                        $config['aff_pending_period'],
                        $stats_url,
                    ),
                    $mail_tpl['body']
                );
                $rlMail->send($mail_tpl, $affiliate['Mail']);
            }
        }
    }

    /**
     * @hook profileController
     */
    public function hookProfileController()
    {
        global $config, $account_info;

        // simulate post data
        if ($config['affiliate_module'] && $account_info['Type'] == 'affiliate' && !$_POST['fromPost_profile']) {
            if ($aff_billing_details = $GLOBALS['rlDb']->fetch(
                '*',
                array('Affiliate_ID' => $account_info['ID']),
                null,
                null,
                'aff_billing_details',
                'row')
            ) {
                $_POST['aff_billing_details'] = array(
                    'type'              => $aff_billing_details['Billing_type'],
                    'paypal_email'      => $aff_billing_details['Paypal_email'],
                    'wu_country'        => $aff_billing_details['WU_country'],
                    'wu_city'           => $aff_billing_details['WU_city'],
                    'wu_fullname'       => $aff_billing_details['WU_fullname'],
                    'bank_wire_details' => $aff_billing_details['Bank_wire_details'],
                );
            }
        }
    }

    /**
     * @hook profileEditProfileValidate
     */
    public function hookProfileEditProfileValidate()
    {
        global $config, $account_info, $errors, $error_fields, $lang, $rlValid, $rlDb;

        // added affiliate billing details for commissions
        if ($config['affiliate_module'] && $account_info['Type'] == 'affiliate') {
            if (is_array($_POST['aff_billing_details']) && $_POST['aff_billing_details']['type']) {
                $type = $_POST['aff_billing_details']['type'];

                switch ($type) {
                    case 'paypal':
                        $email = $_POST['aff_billing_details']['paypal_email'];

                        if (!empty($email) && !$rlValid->isEmail($email)) {
                            $errors[] = $lang['notice_bad_email'];
                            $error_fields .= 'aff_billing_details[paypal_email]';
                        } else {
                            if ($rlDb->getOne('ID', "`Affiliate_ID` = '{$account_info['ID']}'", 'aff_billing_details')) {
                                $update = array(
                                    'fields' => array(
                                        'Billing_type'      => $type,
                                        'Paypal_email'      => $email,
                                        'WU_country'        => '',
                                        'WU_city'           => '',
                                        'WU_fullname'       => '',
                                        'Bank_wire_details' => '',
                                    ),
                                    'where'  => array(
                                        'Affiliate_ID' => $account_info['ID'],
                                    ),
                                );
                            } else {
                                $insert = array(
                                    'Affiliate_ID' => $account_info['ID'],
                                    'Billing_type' => $type,
                                    'Paypal_email' => $email,
                                );
                            }
                        }
                        break;

                    case 'western_union':
                        $country  = !empty($_POST['aff_billing_details']['wu_country']) ? $_POST['aff_billing_details']['wu_country'] : '';
                        $city     = !empty($_POST['aff_billing_details']['wu_city']) ? $_POST['aff_billing_details']['wu_city'] : '';
                        $fullname = !empty($_POST['aff_billing_details']['wu_fullname']) ? $_POST['aff_billing_details']['wu_fullname'] : '';

                        // if (!empty($country) || !empty($city) || !empty($fullname)) {
                        if ($rlDb->getOne('ID', "`Affiliate_ID` = '{$account_info['ID']}'", 'aff_billing_details')) {
                            $update = array(
                                'fields' => array(
                                    'Billing_type'      => $type,
                                    'Paypal_email'      => '',
                                    'WU_country'        => $country,
                                    'WU_city'           => $city,
                                    'WU_fullname'       => $fullname,
                                    'Bank_wire_details' => '',
                                ),
                                'where'  => array(
                                    'Affiliate_ID' => $account_info['ID'],
                                ),
                            );
                        } else {
                            $insert = array(
                                'Affiliate_ID' => $account_info['ID'],
                                'Billing_type' => $type,
                                'WU_country'   => $country,
                                'WU_city'      => $city,
                                'WU_fullname'  => $fullname,
                            );
                        }
                        // }
                        break;

                    case 'bank_wire':
                        $bank_wire_details = $_POST['aff_billing_details']['bank_wire_details'] ? $_POST['aff_billing_details']['bank_wire_details'] : '';

                        // if (!empty($bank_wire_details)) {
                        if ($rlDb->getOne('ID', "`Affiliate_ID` = '{$account_info['ID']}'", 'aff_billing_details')) {
                            $update = array(
                                'fields' => array(
                                    'Billing_type'      => $type,
                                    'Paypal_email'      => '',
                                    'WU_country'        => '',
                                    'WU_city'           => '',
                                    'WU_fullname'       => '',
                                    'Bank_wire_details' => $bank_wire_details,
                                ),
                                'where'  => array(
                                    'Affiliate_ID' => $account_info['ID'],
                                ),
                            );
                        } else {
                            $insert = array(
                                'Affiliate_ID'      => $account_info['ID'],
                                'Billing_type'      => $type,
                                'Paypal_email'      => '',
                                'WU_country'        => '',
                                'WU_city'           => '',
                                'WU_fullname'       => '',
                                'Bank_wire_details' => $bank_wire_details,
                            );
                        }
                        // }
                        break;
                }

                $rlDb->rlAllowHTML = $insert || $update ? true : false;

                if ($insert) {
                    $rlDb->insertOne($insert, 'aff_billing_details');
                } elseif ($update) {
                    $rlDb->updateOne($update, 'aff_billing_details');
                }
            }
        }
    }

    /**
     * @hook  phpLoginValidation
     * @since 1.0.1
     */
    public function hookPhpLoginValidation($param1, $param2)
    {
        global $reefless;

        // block login process for not affiliate users via Sing in form in affiliate page
        if ($GLOBALS['config']['affiliate_module']
            && $param2['Type'] != 'affiliate'
            && $_POST['affiliate_log_form']) {
            unset($_SESSION['affiliate_login']);

            $reefless->loadClass('Notice');
            $GLOBALS['rlNotice']->saveNotice($GLOBALS['lang']['aff_account_type_not_affiliate'], 'error');
            $reefless->referer();
        }
    }

    /**
     * @hook  reeflessRedirctTarget
     * @since 1.0.1
     */
    public function hookReeflessRedirctTarget($param1)
    {
        global $config, $reefless;

        if ($config['affiliate_module']
            && $GLOBALS['page_info']['Key'] == 'login'
            && $_SESSION['affiliate_login']
        ) {
            // if user fill login form (wrong username or pass)
            if ($param1 === $reefless->getPageUrl('login')) {
                unset($_SESSION['affiliate_login']);

                // redirect back to Affiliate program page
                header("Location: " . $reefless->getPageUrl('aff_program_page'));
                exit;
            }
        }
    }

    /**
     * @hook  phpQuickRegistrationBeforeInsert
     * @since 1.0.1
     */
    public function hookPhpQuickRegistrationBeforeInsert(&$param1)
    {
        // replace default account type to affiliate type
        if ($GLOBALS['config']['affiliate_module'] && $param1['Mail'] == $_SESSION['affiliate_email']) {
            $param1['Type'] = 'affiliate';
            unset($_SESSION['affiliate_email']);
        }

        // save referral register (via quickRegistration form & add listing page)
        if ($GLOBALS['page_info']['Key'] == 'add_listing') {
            $_SESSION['Referral_register_email'] = $param1['Mail'];
        }
    }

    /**
     * @hook  profileBlock
     * @since 1.0.1
     */
    public function hookProfileBlock()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . RL_DS . 'affiliate' . RL_DS . 'billing_details.tpl');
    }

    /**
     * @hook  staticDataRegister
     * @since 1.0.1
     */
    public function hookStaticDataRegister()
    {
        global $rlStatic;

        $rlStatic->addJS(RL_PLUGINS_URL . 'affiliate/static/lib.js', 'aff_general_stats', true);
        $rlStatic->addFooterCSS(
            RL_PLUGINS_URL . 'affiliate/static/style.css',
            array('aff_program_page', 'aff_general_stats', 'my_profile', 'aff_payment_history', 'aff_commissions', 'aff_banners'),
            true
        );
        $rlStatic->addFooterCSS(RL_TPL_BASE . 'controllers/add_listing/add_listing.css', 'aff_program_page', true);
    }

    /**
     * @hook  afterListingDone
     * @since 1.0.1
     */
    public function hookAfterListingDone($instanse)
    {
        $this->hooksListingDone($instanse);
    }

    /**
     * @hook  postPaymentComplete
     * @since 1.1.0 - Added $data parameter
     * @since 1.0.1
     */
    public function hookPostPaymentComplete($data)
    {
        global $config, $reefless;

        $transaction = $GLOBALS['rlPayment']->getTransaction($data['txn_id']);

        if ($transaction['Service'] == 'membership'
            && $transaction['Gateway'] == 'bankWireTransfer'
            && $config['affiliate_module']
            && $config['membership_module']
        ) {
            $planID     = (int) $transaction['Plan_ID'];
            $referralID = (int) $data['account_id'];

            if ($referralID) {
                $affiliateID = (int) $GLOBALS['rlDb']->getOne(
                    'Affiliate_ID',
                    "`Referral_ID` = {$referralID} AND `Type` = 'register'",
                    'affiliate'
                );
            }

            $GLOBALS['reefless']->loadClass('MembershipPlansAdmin', 'admin');
            $plan       = $GLOBALS['rlMembershipPlansAdmin']->getPlan($planID);
            $plan_price = (float) $plan['Price'];
            $commission = $plan['Aff_commission'];
            $type       = $plan['Aff_commission_type'];

            if ($referralID && $affiliateID && $type) {
                // count commission by price of membership plan
                if ($plan_price) {
                    $commission = $type == 'percentage' ? round(($plan_price / 100) * $commission, 2) : $commission;
                }

                $this->createCommissionPerMembership($affiliateID, $referralID, $planID, $commission, $type);
            }
        }
    }

    /**
     * Create new commission to affiliate when referral purchased a new membership plan
     */
    public function createCommissionPerMembership($affiliateID, $referralID, $planID, $commission, $type)
    {
        global $config, $reefless, $rlAccount, $rlMail, $lang;

        // write info about new membership plan bought by referral
        $this->createEvent($affiliateID, $referralID, $planID, 'membership', null, $commission, $type);

        $reefless->loadClass('Account');
        $affiliate = $rlAccount->getProfile((int) $affiliateID);
        $referral  = $rlAccount->getProfile((int) $referralID);

        if ($affiliate && $referral) {
            // build commission value
            $commission = $config['system_currency_position'] == 'before'
            ? ($config['system_currency'] . ' ' . $commission)
            : ($commission . ' ' . $config['system_currency']);

            // build link to General Stats page
            $stats_url = $reefless->getPageUrl('aff_general_stats');
            $stats_url = '<a href="' . $stats_url . '">' . $lang['pages+name+aff_general_stats'] . '</a>';

            // send email to affiliate
            $reefless->loadClass('Mail');
            $mail_tpl         = $rlMail->getEmailTemplate('referral_membership_plan_added', $affiliate['Lang']);
            $mail_tpl['body'] = StringUtil::replaceAssoc(
                $mail_tpl['body'],
                array(
                    '{user}'              => $affiliate['Full_name'],
                    '{ref_name}'          => $referral['Full_name'],
                    '{plan_name}'         => $lang['membership_plans+name+ms_plan_' . $planID],
                    '{commission}'        => $commission,
                    '{commission_period}' => $config['aff_pending_period'],
                    '{link}'              => $stats_url,
                )
            );
            $rlMail->send($mail_tpl, $affiliate['Mail']);
        }
    }

    /**
     * @hook tplRegistrationCheckbox
     */
    public function hookTplRegistrationCheckbox()
    {
        global $config, $account_types, $lang, $pages, $rlSmarty;

        if (!$config['affiliate_module'] || !$account_types) {
            return;
        }

        foreach ($account_types as $account_type) {
            if ($account_type['Key'] == 'affiliate') {
                $affiliate_ID = $account_type['ID'];
                break;
            }
        }

        if ($affiliate_ID) {
            $aff_terms_of_use_link = SEO_BASE . ($config['mod_rewrite'] ? $pages['aff_terms_of_use_program_page'] . '.html' : 'index.php?page=' . $pages['aff_terms_of_use_program_page']);
            $aff_terms_of_use_link = '<a href="' . $aff_terms_of_use_link . '" target="_blank" title="' . $lang['pages+name+aff_terms_of_use_program_page'] . '">' . $lang['pages+name+aff_terms_of_use_program_page'] . '</a>';

            $rlSmarty->assign('affiliate_ID', $affiliate_ID);
            $rlSmarty->assign('aff_terms_of_use_link', $aff_terms_of_use_link);
            $rlSmarty->display(RL_PLUGINS . 'affiliate' . RL_DS . 'tpl_registration_checkbox.tpl');
        }
    }

    /**
     * @hook apTplContentBottom
     */
    public function hookApTplContentBottom()
    {
        global $config, $controller, $lang, $plan_info;

        // Affiliate plugin enabled
        if ($config['affiliate_module']) {
            if ($_GET['action'] == 'edit') {
                // hide settings for affiliate account type
                if ($controller == 'account_types' && $_GET['type'] == 'affiliate') {
                    $selectors = '#legend_account_abb,#legend_account_settings';
                }

                // hide controller of system (static) Affiliate page
                if ($controller == 'pages' && ($_GET['page'] == 'aff_program_page'
                    || $_GET['page'] == 'aff_terms_of_use_program_page')) {
                    $selectors = '#page_types';
                }

                // hide "Show on pages" and "Show in categories" sections for Affiliate menu block
                if ($controller == 'blocks' && $_GET['block'] == 'aff_menu') {
                    $selectors = '#cats,#pages';
                }

                // hide content by selectors
                if ($selectors) {
                    echo "<script>$('{$selectors}').closest('tr').hide()</script>";
                }
            }

            // add commission and commission type configs to listing plans
            if ($controller == 'listing_plans' || $controller == 'membership_plans') {
                $aff_commission_fixed = $plan_info['Aff_commission_type'] == 'fixed' ||
                $_POST['aff_commission_type'] == 'fixed' ? 'selected="selected"' : '';
                $aff_commission_percentage = $plan_info['Aff_commission_type'] == 'percentage' ||
                $_POST['aff_commission_type'] == 'percentage' ? 'selected="selected"' : '';
                $aff_commission = $plan_info['Aff_commission'] ? $plan_info['Aff_commission'] : $_POST['aff_commission'];

                echo <<<HTML
                    <script>
                    $(document).ready(function(){
                        var aff_configs_content = '<tr><td class="name">{$lang['aff_commission_for_affiliates']}</td>';
                        aff_configs_content += '<td class="field"><input type="text" name="aff_commission" value="{$aff_commission}" class="numeric" style="width: 50px; text-align: center;">';
                        aff_configs_content += '<select name="aff_commission_type" style="width:185px;margin-left:5px;">';
                        aff_configs_content += '<option {$aff_commission_fixed} value="fixed"> {$lang['aff_commission_for_affiliates_option1']} ({$config['system_currency_code']})</option>';
                        aff_configs_content += '<option {$aff_commission_percentage} value="percentage">{$lang['aff_commission_for_affiliates_option2']}</option></select></td></tr>';

                        $(aff_configs_content).insertAfter($('input[name="price"]').closest('tr'));
                    });
                    </script>
HTML;
            }
        }
    }

    /**
     * @hook apPhpListingPlansValidate
     */
    public function hookApPhpListingPlansValidate()
    {
        $this->plansApErrorHandler();
    }

    /**
     * @hook apPhpListingPlansBeforeAdd
     */
    public function hookApPhpListingPlansBeforeAdd()
    {
        global $config, $data;

        if ($config['affiliate_module'] && $data && $_POST['aff_commission'] && $_POST['aff_commission_type']) {
            $data['Aff_commission']      = $_POST['aff_commission'];
            $data['Aff_commission_type'] = $_POST['aff_commission_type'];
        }
    }

    /**
     * @hook apPhpListingPlansBeforeEdit
     */
    public function hookApPhpListingPlansBeforeEdit()
    {
        global $config, $update_date;

        if ($config['affiliate_module'] && $update_date && $_POST['aff_commission_type']) {
            $update_date['fields']['Aff_commission'] = (float) $_POST['aff_commission'] > 0
            ? (float) $_POST['aff_commission']
            : 0;

            $update_date['fields']['Aff_commission_type'] = $_POST['aff_commission_type'];
        }
    }

    /**
     * @hook apPhpMembershipPlansValidate
     * @since 1.1.0
     */
    public function hookApPhpMembershipPlansValidate()
    {
        $this->plansApErrorHandler();
    }

    /**
     * @hook apPhpMembershipPlansBeforeAdd
     * @since 1.1.0
     *
     * @param array $data - Data which will be saving in DB
     * @param array $plan - Data from $_POST
     */
    public function hookApPhpMembershipPlansBeforeAdd(&$data, $plan)
    {
        if ($GLOBALS['config']['affiliate_module'] && $plan['aff_commission'] && $plan['aff_commission_type']) {
            $commission                  = (float) $plan['aff_commission'];
            $data['Aff_commission']      = $commission > 0 ? $commission : 0;
            $data['Aff_commission_type'] = $plan['aff_commission_type'];
        }
    }

    /**
     * @hook apPhpMembershipPlansBeforeEdit
     * @since 1.1.0
     *
     * @param array $update_plan - Data which will be saving in DB
     * @param array $plan        - Data from $_POST
     */
    public function hookApPhpMembershipPlansBeforeEdit(&$update_plan, $plan)
    {
        if ($GLOBALS['config']['affiliate_module'] && $plan['aff_commission_type']) {
            $commission                                   = (float) $plan['aff_commission'];
            $update_plan['fields']['Aff_commission']      = $commission > 0 ? $commission : 0;
            $update_plan['fields']['Aff_commission_type'] = $plan['aff_commission_type'];
        }
    }

    /**
     * @hook apPhpConfigAfterUpdate
     */
    public function hookApPhpConfigAfterUpdate()
    {
        global $update, $lang, $config, $rlNotice, $rlSmarty;

        if ($config['affiliate_module']) {
            foreach ($update as $config_item) {
                if ($config_item['where']['Key'] == 'aff_pending_period' && intval($config_item['fields']['Default']) < 30) {
                    $GLOBALS['reefless']->loadClass('Notice');
                    $rlNotice->saveNotice($lang['aff_warning_commission_period'], 'alerts');
                    break;
                }
            }
        }
    }

    /**
     * @hook cronAdditional
     */
    public function hookCronAdditional()
    {
        global $config, $pages, $lang, $rlAccount, $rlMail, $rlDb, $reefless;

        if ($config['affiliate_module']) {
            $aff_pending_period = $config['aff_pending_period'] > 0 ? $config['aff_pending_period'] : 30;

            // get "pending" affiliates
            $sql = "SELECT `T1`.* ";
            $sql .= "FROM `{db_prefix}affiliate` AS `T1` ";
            $sql .= "WHERE `T1`.`Date` < NOW() - INTERVAL {$aff_pending_period} DAY ";
            $sql .= "AND `T1`.`Cron` = '0' ";
            $sql .= "AND `T1`.`Type` <> 'visit' AND `T1`.`Status` = 'pending' ";
            $sql .= "ORDER BY `T1`.`Date` DESC ";
            $sql .= "LIMIT 50";

            // set status to "ready" and sent email to affiliate's and admin
            if ($ready_affs = $rlDb->getAll($sql)) {
                $reefless->loadClass('Mail');

                foreach ($ready_affs as $aff_key => $aff_event) {
                    // get affiliate info
                    $affiliate = $rlAccount->getProfile((int) $aff_event['Affiliate_ID']);

                    if ($aff_event['Type'] != 'listing') {
                        // get referral info
                        $referral = $rlAccount->getProfile((int) $aff_event['Referral_ID']);
                    }

                    // collect info by affiliate
                    $aff_deals[$affiliate['ID']]['Full_name'] = trim($affiliate['Full_name']);
                    $aff_deals[$affiliate['ID']]['Mail']      = trim($affiliate['Mail']);
                    $aff_deals[$affiliate['ID']]['Lang']      = trim($affiliate['Lang']);
                    $aff_deals[$affiliate['ID']]['Count']++;
                    $aff_deals[$affiliate['ID']]['Amount'] += $aff_event['Commission'];

                    // build commission value
                    $commission = $config['system_currency_position'] == 'before' ? ($config['system_currency'] . ' ' . $aff_event['Commission']) : ($aff_event['Commission'] . ' ' . $config['system_currency']);

                    // build listing url
                    $listing_url = false;
                    if ($rlDb->getOne('ID', "`ID` = '{$aff_event['Item_ID']}'", 'listings')) {
                        $listing_url = $reefless->getListingUrl((int) $aff_event['Item_ID']);
                        $listing_url = '<a href="' . $listing_url . '">' . $lang['aff_details_item'] . ' #' . $aff_event['Item_ID'] . '</a>';
                    }

                    $deal_index  = $aff_deals[$affiliate['ID']]['Deals'] ? count($aff_deals[$affiliate['ID']]['Deals']) + 1 : 1;
                    $type_phrase = $aff_event['Type'] == 'membership' ? 'aff_type_membership' : 'aff_referral_user';

                    if ($aff_event['Type'] == 'listing') {
                        // get plan name
                        $listing_plan_key = $rlDb->getOne('Key', "`ID` = '{$aff_event['Plan_ID']}'", 'listing_plans');
                        $plan_name        = $lang['listing_plans+name+' . $listing_plan_key];

                        // build item with details
                        $item = $deal_index . '. ' . $listing_url ?: $lang['aff_details_item'];
                        $item .= ' #' . $aff_event['Item_ID'] . ' (' . $plan_name . ') - ' . $commission;
                        $aff_deals[$affiliate['ID']]['Deals'][$aff_key]['Aff_details_item'] = $item;
                    } else {
                        $item = $deal_index . '. ' . $referral['Full_name'];
                        $item .= ' (' . $lang[$type_phrase] . ') - ' . $commission;
                        $aff_deals[$affiliate['ID']]['Deals'][$aff_key]['Aff_details_item'] = $item;
                    }

                    // collect info by each deal by each affiliate for admin email
                    $aff_admin_details .= $lang['aff_details_item_separator'] . '<br />';
                    $aff_admin_details .= $lang['aff_details_item_admin_user'] . ': ' . $aff_deals[$affiliate['ID']]['Full_name'] . '<br />';

                    if ($aff_event['Type'] == 'listing') {
                        $aff_admin_details .= $lang['aff_details_item_admin_item'] . ': ';
                        $aff_admin_details .= $listing_url ?: ($lang['aff_details_item'] . ' #' . $aff_event['Item_ID']);
                        $aff_admin_details .= '<br />' . $lang['aff_details_item_admin_plan'] . ': ' . $plan_name . '<br />';
                    } else {
                        $aff_admin_details .= $lang[$type_phrase] . ': ' . $referral['Full_name'] . '<br />';
                    }

                    $aff_admin_details .= $lang['aff_details_item_admin_commission'] . ': ' . $commission . '<br />';

                    // change status of deal to "ready"
                    $aff_update[] = array(
                        'fields' => array(
                            'Cron'   => '1',
                            'Status' => 'ready',
                        ),
                        'where'  => array(
                            'ID' => $aff_event['ID'],
                        ),
                    );
                }

                // build link to General Stats page
                $general_stats_url = $reefless->getPageUrl('aff_general_stats');
                $general_stats_url = '<a href="' . $general_stats_url . '">' . $lang['pages+name+aff_general_stats'] . '</a>';

                // update deals
                $rlDb->update($aff_update, 'affiliate');

                // send email to affiliates
                foreach ($aff_deals as $aff_deal) {
                    $mail_tpl = $rlMail->getEmailTemplate('affiliate_commission_ready', $aff_deal['Lang']);

                    // build deal items
                    foreach ($aff_deal['Deals'] as $aff_item) {
                        $aff_details .= $aff_item['Aff_details_item'] . '<br />';
                    }

                    $mail_tpl['body'] = StringUtil::replaceAssoc(
                        $mail_tpl['body'],
                        array(
                            '{user}'        => $aff_deal['Full_name'],
                            '{amount}'      => $config['system_currency_position'] == 'before'
                            ? $config['system_currency'] . ' ' . $aff_deal['Amount']
                            : $aff_deal['Amount'] . ' ' . $config['system_currency'],
                            '{days}'        => $config['aff_pending_period'],
                            '{count}'       => $aff_deal['Count'],
                            '{aff_details}' => $aff_details,
                            '{link}'        => $general_stats_url,
                        )
                    );

                    $rlMail->send($mail_tpl, $aff_deal['Mail']);
                    unset($aff_details);
                }

                // send email to admin
                $mail_tpl         = $rlMail->getEmailTemplate('affiliate_commission_ready_admin');
                $mail_tpl['body'] = StringUtil::replaceAssoc(
                    $mail_tpl['body'],
                    array(
                        '{days}'        => $config['aff_pending_period'],
                        '{count}'       => count($ready_affs),
                        '{aff_details}' => $aff_admin_details,
                    )
                );

                $rlMail->send($mail_tpl, $config['notifications_email']);
            }
        }
    }

    /**
     * Check image is animated or not
     */
    public function isAnimatedGif($filename): bool
    {
        if (!$filename) {
            return false;
        }

        $raw    = file_get_contents($filename);
        $offset = 0;
        $frames = 0;

        while ($frames < 2) {
            $where1 = strpos($raw, "\x00\x21\xF9\x04", $offset);

            if ($where1 === false) {
                break;
            } else {
                $offset = $where1 + 1;
                $where2 = strpos($raw, "\x00\x2C", $offset);

                if ($where2 === false) {
                    break;
                } else {
                    if ($where1 + 8 == $where2) {
                        $frames++;
                    }

                    $offset = $where2 + 1;
                }
            }
        }

        return $frames > 1;
    }

    /**
     * Checking data in plan info
     */
    public function plansApErrorHandler()
    {
        if ($GLOBALS['config']['affiliate_module'] && $_POST['aff_commission'] && $_POST['aff_commission_type']) {
            // check price of listing plan and affiliate commission type
            if ((float) $_POST['price'] <= 0 && $_POST['aff_commission_type'] == 'percentage') {
                $GLOBALS['errors'][]       = $GLOBALS['lang']['aff_incorrect_commission_type'];
                $GLOBALS['error_fields'][] = 'aff_commission_type';
            }
        }
    }

    /**
     * @hook  registrationDone
     * @since 1.1.0
     */
    public function hookRegistrationDone()
    {
        global $config, $reefless, $rlAccount, $rlMail, $lang;

        $plan = $_SESSION['registration']['plan'];
        $bank = $_SESSION['complete_payment']['gateway'] == 'bankWireTransfer' ? true : false;

        if ($config['affiliate_module'] && $config['membership_module'] && $plan && !$bank) {
            $plan_price = (float) $plan['Price'];
            $commission = $plan['Aff_commission'];
            $type       = $plan['Aff_commission_type'];
            $planID     = (int) $plan['ID'];
            $referralID = (int) $_SESSION['registration']['account_id'];

            if ($referralID) {
                $affiliateID = (int) $GLOBALS['rlDb']->getOne(
                    'Affiliate_ID',
                    "`Referral_ID` = {$referralID} AND `Type` = 'register'",
                    'affiliate'
                );
            }

            if ($referralID && $affiliateID && $type) {
                // count commission by price of membership plan
                if ($plan_price) {
                    $commission = $type == 'percentage' ? round(($plan_price / 100) * $commission, 2) : $commission;
                }

                $this->createCommissionPerMembership($affiliateID, $referralID, $planID, $commission, $type);
            }
        }
    }

    /**
     * @hook  apPhpConfigBeforeUpdate
     * @since 1.2.0
     */
    public function hookApPhpConfigBeforeUpdate()
    {
        global $dConfig, $lang;

        $errors = array();

        // validate incoming data
        if ((int) $dConfig['aff_cookie_time']['value'] < 1) {
            $errors[] = str_replace(
                '{field}',
                "<b>\"{$lang['config+name+aff_cookie_time']}\"</b>",
                $lang['notice_field_incorrect']
            );
        }
        if ((int) $dConfig['aff_pending_period']['value'] < 1) {
            $errors[] = str_replace(
                '{field}',
                "<b>\"{$lang['config+name+aff_pending_period']}\"</b>",
                $lang['notice_field_incorrect']
            );
        }
        if ((int) $dConfig['aff_commission_by_registered']['value'] < 0) {
            $errors[] = str_replace(
                '{field}',
                "<b>\"{$lang['config+name+aff_commission_by_registered']}\"</b>",
                $lang['notice_field_incorrect']
            );
        }
        if ((int) $dConfig['aff_items_per_page']['value'] < 1) {
            $errors[] = str_replace(
                '{field}',
                "<b>\"{$lang['config+name+aff_items_per_page']}\"</b>",
                $lang['notice_field_incorrect']
            );
        }

        if ($errors) {
            $GLOBALS['rlNotice']->saveNotice($errors, 'errors');
            $GLOBALS['reefless']->redirect(array('controller' => $GLOBALS['controller'], 'group' => $_POST['group_id']));
        }
    }

    /**
     * @hook  registrationBegin
     * @since 1.2.0
     */
    public function hookRegistrationBegin()
    {
        global $config, $account_types;

        if ($account_types && (!$config['affiliate_module'] || !$config['aff_show_in_registration'])) {
            foreach ($account_types as $ac_type_id => $ac_type) {
                if ($ac_type['Key'] == 'affiliate') {
                    unset($account_types[$ac_type_id]);
                    break;
                }
            }
        }
    }

    /**
     * Create new event about new registered referral
     *
     * @since 1.3.2
     *
     * @param $affiliateID
     * @param $referralID
     *
     * @return bool
     */
    public function registerNewReferral($affiliateID, $referralID)
    {
        $affiliateID = (int) $affiliateID;
        $referralID  = (int) $referralID;

        if (!$affiliateID || !$referralID) {
            return false;
        }

        global $config, $rlAccount, $reefless, $rlMail, $domain_info;

        $commission = (float) $config['aff_commission_by_registered'];

        if ($commission) {
            $this->createEvent($affiliateID, $referralID, null, 'register', null, $commission, 'fixed');

            $affiliate = $rlAccount->getProfile((int) $affiliateID);
            $referral  = $rlAccount->getProfile((int) $referralID);

            if ($affiliate && $referral) {
                $commission = $config['system_currency_position'] == 'before'
                ? ($config['system_currency'] . ' ' . $commission)
                : ($commission . ' ' . $config['system_currency']);

                // Build link to General Stats page
                $general_stats_url = $reefless->getPageUrl('aff_general_stats');
                $general_stats_url = '<a href="' . $general_stats_url . '">';
                $general_stats_url .= $GLOBALS['lang']['pages+name+aff_general_stats'] . '</a>';

                $reefless->loadClass('Mail');
                $mail_tpl         = $rlMail->getEmailTemplate('referral_user_registered', $affiliate['Lang']);
                $mail_tpl['body'] = str_replace(
                    [
                        '{user}',
                        '{ref_name}',
                        '{commission}',
                        '{commission_period}',
                        '{link}',
                    ],
                    [
                        trim($affiliate['Full_name']),
                        trim($referral['Full_name']),
                        $commission,
                        $config['aff_pending_period'],
                        $general_stats_url,
                    ],
                    $mail_tpl['body']
                );
                $rlMail->send($mail_tpl, $affiliate['Mail']);
            }
        } else {
            $this->createEvent($affiliateID, $referralID, null, 'register');
        }

        // Remove Affiliate_ID cookie
        setcookie('Affiliate_ID', null, -1, $domain_info['path'], $domain_info['domain']);

        return true;
    }

    /*** DEPRECATED METHODS ***/

    /**
     * @hook tplFooter
     * @deprecated 1.1.0
     * @since 1.0.1
     */
    public function hookTplFooter()
    {}

}
