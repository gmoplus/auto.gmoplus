<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.7.2
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: RLINSTALL.CLASS.PHP
 *
 *	The software is a commercial product delivered under single, non-exclusive,
 *	non-transferable license for one domain or IP address. Therefore distribution,
 *	sale or transfer of the file in whole or in part without permission of Flynax
 *	respective owners is considered to be illegal and breach of Flynax License End
 *	User Agreement.
 *
 *	You are not allowed to remove this information from the file without permission
 *	of Flynax respective owners.
 *
 *	Flynax Classifieds Software 2022 |  All copyrights reserved.
 *
 *	https://www.flynax.com/
 *
 ******************************************************************************/

class rlInstall
{
    /**
     * Install plugin
     */
    public function install()
    {
        global $rlDb, $languages;

        // add field to transactions
        $rlDb->addColumnsToTable(
            array('Item_data'   => "Text NOT NULL default ''"),
            'transactions'
        );

        $gateway_info = array(
            'Key' => 'authorizeNet',
            'Recurring_editable' => 1,
            'Plugin' => 'authorizeNet',
            'Required_options' => 'authorizeNet_account_id,authorizeNet_transaction_key,authorizeNet_type',
            'Form_type' => 'default',
        );

        if ($rlDb->insertOne($gateway_info, 'payment_gateways')) {
            if ($languages) {
                foreach ((array) $languages as $lKey => $lValue) {
                    if ($rlDb->getOne('ID', "`Key` = 'payment_gateways+name+authorizeNet' AND `Code` = '{$lValue['Code']}'", 'lang_keys')) {
                        $update_names = array(
                            'fields' => array(
                                'Value' => 'AuthorizeNet',
                            ),
                            'where' => array(
                                'Code' => $lValue['Code'],
                                'Key' => 'payment_gateways+name+authorizeNet',
                            ),
                        );
                        $rlDb->updateOne($update_names, 'lang_keys');
                    } else {
                        $insert_names = array(
                            'Code' => $lValue['Code'],
                            'Module' => 'common',
                            'Key' => 'payment_gateways+name+authorizeNet',
                            'Value' => 'AuthorizeNet',
                            'Plugin' => 'authorizeNet',
                        );
                        $rlDb->insertOne($insert_names, 'lang_keys');
                    }
                }
            }
        }

        // add subscription plan fields
        $rlDb->addColumnsToTable(
            array('sop_authorizenet_interval_length' => "int(4) NOT NULL default '0'"),
            'subscription_plans'
        );
        $rlDb->addColumnsToTable(
            array('authorizeNet_item_data' => "varchar(255) NOT NULL default ''"),
            'subscriptions'
        );

        // only for shoppingCart plugin
        $GLOBALS['reefless']->loadClass('AuthorizeNet', null, 'authorizeNet');
        $GLOBALS['rlAuthorizeNet']->addAccountFields();
    }
    
    /**
     * Update to 2.3.0
     */
    public function update230()
    {
        $update = array(
            'fields' => array(
                'Form_type' => 'default',
                'Required_options' => 'authorizeNet_account_id,authorizeNet_transaction_key,authorizeNet_md5_hash,authorizeNet_type',
            ),
            'where' => array(
                'Key' => 'authorizeNet',
            ),
        );
        $GLOBALS['rlActions']->updateOne($update, 'payment_gateways');
    
        /* remove unnecessary folders */
        $removing_folders = array('controllers');
        foreach ($removing_folders as $folder) {
            $folder_path = RL_PLUGINS . 'authorizeNet/' . $folder;
            $scaned_files = glob($folder_path . '/*', GLOB_MARK);
        
            if ($scaned_files) {
                foreach ($scaned_files as $file) {
                    unlink($file);
                }
            }
        
            rmdir($folder_path);
        }
    
        /* remove unnecessary files */
        $removing_files = array(
            'boot.php',
            'gateway.tpl',
            'init.php',
            'myListingsIcon.tpl',
            'subscription.inc.php',
            'subscription.tpl',
            'rlAuthorizeNetNew.class.php',
            'rlAuthorizeNetOld.class.php',
            'admin/apPhpListingPlansBeforeAdd.php',
            'admin/apPhpListingPlansBeforeEdit.php',
            'admin/apPhpListingPlansPost.php'
        );
        
        foreach ($removing_files as $file) {
            unlink(RL_PLUGINS . 'authorizeNet/' . $file);
        }
    }

    /**
     * Uninstall plugin
     */
    public function uninstall()
    {
        global $rlDb;

        // delete row from payment gateways table
        $sql = "DELETE FROM `" . RL_DBPREFIX . "payment_gateways` WHERE `Key` = 'authorizeNet' LIMIT 1";
        $rlDb->query($sql);

        // delete transactions
        $sql = "DELETE FROM `" . RL_DBPREFIX . "transactions` WHERE `Gateway` = 'authorizeNet'";
        $rlDb->query($sql);

        // delete field from subscription plans table
        $rlDb->dropColumnsFromTable(
            array('authorizeNet_item_data'),
            'subscriptions'
        );
        $rlDb->dropColumnsFromTable(
            array('sop_authorizenet_interval_length'),
            'subscription_plans'
        );

        // only for shoppingCart plugin
        $this->removeAccountFields();
    }

    /**
     * Remove account fields for shopping cart & bidding plugin
     *
     * @since 2.4.0
     */
    public function removeAccountFields()
    {
        global $rlDb, $plugins;

        $accountsTable = 'shc_account_settings';

        if (!$rlDb->tableExists('shc_account_settings')) {
            $accountsTable = 'accounts';

            if (version_compare($GLOBALS['config']['rl_version'], '4.8.2') >= 0) {
                return;
            }
        }

        $rlDb->dropColumnsFromTable(
            array(
                'authorizeNet_enable',
                'authorizeNet_transaction_key',
                'authorizeNet_account_id',
            ),
            $accountsTable
        );
    }
    
    /**
     * Update to 2.4.0
     */
    public function update240()
    {
        global $rlDb, $plugins;

        $update = array(
            'fields' => array(
                'Form_type' => 'default',
                'Required_options' => 'authorizeNet_account_id,authorizeNet_transaction_key,authorizeNet_type',
            ),
            'where' => array(
                'Key' => 'authorizeNet',
            ),
        );
        $rlDb->updateOne($update, 'payment_gateways');

        $GLOBALS['reefless']->deleteDirectory(RL_PLUGINS . 'authorizeNet/lib');

        $filesystem = new \Symfony\Component\Filesystem\Filesystem;
        $filesystem->mirror(RL_UPLOAD . 'authorizeNet/vendor', RL_PLUGINS . 'authorizeNet/vendor');

        if (!$plugins['shoppingCart']) {
            return;
        }

        $accountsTable = 'shc_account_settings';

        if ($rlDb->tableExists($accountsTable)) {
            $rlDb->addColumnsToTable(
                array(
                    'authorizeNet_enable' => "ENUM('0','1') NOT NULL DEFAULT '0'",
                    'authorizeNet_transaction_key' => "varchar(150) NOT NULL default ''",
                    'authorizeNet_account_id' => "varchar(150) NOT NULL default ''",
                ),
                $accountsTable
            );
            return;
        }

        $rlDb->query("ALTER TABLE `{db_prefix}accounts` CHANGE `shc_authorizeNet_enable` `authorizeNet_enable` ENUM('0','1');");
        $rlDb->query("ALTER TABLE `{db_prefix}accounts` CHANGE `shc_authorizeNet_transaction_key` `authorizeNet_transaction_key` varchar(150);");
        $rlDb->query("ALTER TABLE `{db_prefix}accounts` CHANGE `shc_authorizeNet_account_id` `authorizeNet_account_id` varchar(150);");

        $deleteFiles = [
            'admin/apTplListingPlansForm.tpl', 
            'admin/apTplListingPlansForm_44.tpl',
            'static/aNetSubscription.png',
            'static/authorizeNetSubscription.png',
            'static/Style.css',
        ];

        foreach ($deleteFiles as $key => $value) {
            if (file_exists(RL_PLUGINS . 'authorizeNet/' . $value)) {
                unlink(RL_PLUGINS . 'authorizeNet/' . $value);
            }
        }

        // delete old lang keys
        $keys = [
            'authorizeNet_payment',
            'authorizeNet_subscription',
            'authorizeNet_payment_information',
            'authorizeNet_billing_information',
            'authorizeNet_shipping_information',
            'authorizeNet_credit_card',
            'authorizeNet_expiration_date',
            'authorizeNet_credit_card_code',
            'authorizeNet_bank_account_name',
            'authorizeNet_bank_account_number',
            'authorizeNet_bank_account_routing',
            'authorizeNet_bank_account_name_on_account',
            'authorizeNet_bank_account_type',
            'authorizeNet_bank_account_type_ck',
            'authorizeNet_bank_account_type_sa',
            'authorizeNet_bank_account_type_bc',
            'authorizeNet_first_name',
            'authorizeNet_last_name',
            'authorizeNet_company',
            'authorizeNet_address',
            'authorizeNet_address',
            'authorizeNet_zip',
            'authorizeNet_state',
            'authorizeNet_counry',
            'authorizeNet_email',
            'authorizeNet_phone',
            'authorizeNet_fax',
            'authorizeNet_copy_billing_information',
            'authorizeNet_pay_by',
            'authorizeNet_pay_by_cc',
            'authorizeNet_pay_by_ba',
            'authorizeNet_unsubscription',
            'authorizeNet_submit',
            'authorizeNet_subscription_success',
            'authorizeNet_unsubscription_success',
            'authorizeNet_item_details',
            'authorizeNet_check_status_subscription',
            'authorizeNet_status_subscription',
            'authorizeNet_continue',
            'authorizeNet_duplicate',
            'authorizeNet_interval_unit',
            'authorizeNet_start_date',
            'authorizeNet_interval_length',
        ];

        $keys = implode(',', $keys);
        $sql = "DELETE FROM `{db_prefix}lang_keys` WHERE  FIND_IN_SET(`Key`, '{$keys}') > 0 AND `Plugin` = 'authorizeNet'";
        $rlDb->query($sql);
    }
}
