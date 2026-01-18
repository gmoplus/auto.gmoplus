<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLINSTALL.CLASS.PHP
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

class rlInstall
{
    /**
     * Intsall plugin
     */
    public function install()
    {
        global $rlDb, $languages;

        // add field to transactions
        $rlDb->addColumnsToTable(
            array(
                'Item_data' => "Text NOT NULL default ''",
                'Dealer_ID' => "INT NOT NULL default '0'",
                'Doc' => "VARCHAR(255) NOT NULL default ''",
            ),
            'transactions'
        );

        // insert item to payment gateways table
        $gateway_info = array(
            'Key' => 'bankWireTransfer',
            'Recurring_editable' => 0,
            'Plugin' => 'bankWireTransfer',
            'Form_type' => 'offsite',
        );

        if ($rlDb->insertOne($gateway_info, 'payment_gateways')) {
            if ($languages) {
                $phrase_key = 'payment_gateways+name+bankWireTransfer';
                $phraseVal = '';

                if (isset($languages['ru'])) {
                    $phrases = json_decode(file_get_contents(RL_PLUGINS . 'bankWireTransfer/i18n/ru.json'), true);

                    foreach ($phrases as $phKey => $phVal) {
                        if ($phKey == $phrase_key) {
                            $phraseVal = $phVal;
                            break;
                        }
                    }
                }

                foreach ((array) $languages as $lKey => $lValue) {
                    $gatewayName = $lValue['Code'] == 'ru' && !empty($phraseVal) ? $phraseVal : 'Offline Payments';
                    if ($rlDb->getOne('ID', "`Key` = '{$phrase_key}' AND `Code` = '{$lValue['Code']}'", 'lang_keys')) {
                        $update_names = array(
                            'fields' => array(
                                'Value' => $gatewayName,
                            ),
                            'where' => array(
                                'Code' => $lValue['Code'],
                                'Key' => $phrase_key,
                            ),
                        );
                        $rlDb->updateOne($update_names, 'lang_keys');
                    } else {
                        $insert_names = array(
                            'Code' => $lValue['Code'],
                            'Module' => 'common',
                            'Key' => $phrase_key,
                            'Value' => $gatewayName,
                            'Plugin' => 'bankWireTransfer',
                        );

                        $rlDb->insertOne($insert_names, 'lang_keys');
                    }
                }
            }
        }

        // only for shoppingCart plugin
        $GLOBALS['reefless']->loadClass('BankWireTransfer', null, 'bankWireTransfer');
        $GLOBALS['rlBankWireTransfer']->addAccountFields();
    }

    /**
     * Uninstall plugin
     */
    public function uninstall()
    {
        global $rlDb;

        // remove item from payment gateways table
        $rlDb->delete(array('Key' => 'bankWireTransfer'), 'payment_gateways');

        // delete transactions
        $rlDb->delete(array('Gateway' => 'bankWireTransfer'), 'transactions');

        $rlDb->dropColumnsFromTable(
            array(
                'Dealer_ID',
                'Doc',
            ),
            'transactions'
        );

        // only for shoppingCart plugin
        $this->removeAccountFields();
    }

    /**
     * Update to 2.1.0
     */
    public function update210()
    {
        global $rlDb;

        // remove old hook
        $sql = "DELETE FROM `{db_prefix}hooks` WHERE `Name` = 'apPhpIndexBottom' AND `Plugin` = 'bankWireTransfer'";
        $rlDb->query($sql);

        // remove old page
        $sql = "DELETE FROM `{db_prefix}pages` WHERE `Key` = 'bank_wire_transfer' LIMIT 1";
        $rlDb->query($sql);

        $old_files = array('bank_wire_transfer.inc.php', 'bank_wire_transfer.tpl');

        foreach ($old_files as $fKey => $fVal) {
            $file = RL_PLUGINS . 'bankWireTransfer' . RL_DS . $fVal;

            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Update to 3.0.0
     */
    public function update300()
    {
        global $rlDb;

        $hooks = 'phpGetPaymentGatewaysItem,apPhpPaymetGatewaysSettings,profileController,specialBlock';

        // remove old hook
        $sql = "DELETE FROM `{db_prefix}hooks` WHERE FIND_IN_SET(`Name`, '{$hooks}') > 0 AND `Plugin` = 'bankWireTransfer'";
        $rlDb->query($sql);

        $sql = "DELETE FROM `{db_prefix}config` WHERE `Plugin` = 'bankWireTransfer' AND `Key` = 'bankWireTransfer_type' ";
        $GLOBALS['rlDb']->query($sql);

        $langKeys = [
            'bwt_account_info',
            'bwt_bank_info',
            'bwt_account_name',
            'bwt_company_name',
            'bwt_country',
            'bwt_city',
            'bwt_address',
            'bwt_state',
            'bwt_zip',
            'bwt_bank_account_number',
            'bwt_bank_name',
            'bwt_bank_address',
            'bwt_bank_phone',
            'config+name+bankWireTransfer_type',
            'ext_payment_details_manager',
            'bwt_activate_error',
            'ext_service',
            'bwt_transactions',
            'bwt_transaction_info',
            'bwt_txn_id',
            'bwt_view',
            'bwt_pay',
            'bwt_description',
            'bwt_transaction_num',
            'bwt_details_tnx',
            'bwt_payment_by_check',
            'bwt_add_item',
            'bwt_edit_item',
            'bwt_item_added',
            'bwt_item_edited',
            'bwt_order_details',
            'by_check',
            'bwt_settings',
            'config+name+bwt_divider',
            'config+name+bwt_module',
        ];
        $langKeys = implode(',', $langKeys);

        $sql = "DELETE FROM `{db_prefix}lang_keys` WHERE `Plugin` = 'bankWireTransfer' ";
        $sql .= "AND FIND_IN_SET(`Key`, '{$langKeys}') > 0 ";
        $GLOBALS['rlDb']->query($sql);

        $files = [
            'form_by_cheque_on_call.tpl',
            'form_by_cheque_on_call_responsive_42.tpl',
            'type_by_cheque_on_call.tpl',
            'static/bwt_write_transfer.png',
            'payment_history.php',
        ];

        foreach ($files as $file) {
            if (file_exists(RL_PLUGINS . 'bankWireTransfer/' . $file)) {
                unlink(RL_PLUGINS . 'bankWireTransfer/' . $file);
            }
        }

        $rlDb->dropTable('bwt_transactions');

        // only for shoppingCart plugin
        $GLOBALS['reefless']->loadClass('BankWireTransfer', null, 'bankWireTransfer');
        $GLOBALS['rlBankWireTransfer']->addAccountFields();

        if ($this->synchronizeAccountFields()) {
            $columns = array(
                'shc_bankWireTransfer_enable',
                'shc_bankWireTransfer_details',
                'shc_bankWireTransfer_type',
            );

            $rlDb->dropColumnsFromTable($columns, 'accounts');
        }

        // add field to transactions
        $rlDb->addColumnsToTable(
            array(
                'Dealer_ID' => "INT NOT NULL default '0'",
                'Doc' => "VARCHAR(255) NOT NULL default ''",
            ),
            'transactions'
        );

        $update = array(
            'fields' => array(
                'Form_type' => 'offsite',
            ),
            'where' => array(
                'Key' => 'bankWireTransfer',
            ),
        );
        $rlDb->updateOne($update, 'payment_gateways');

        // delete old configs
        $sql = "DELETE FROM `{db_prefix}config` WHERE `Plugin` = 'bankWireTransfer' ";
        $sql .= "AND (`Key` = 'bwt_module' OR `Key` = 'bwt_divider')";
        $rlDb->query($sql);
    }

    /**
     * Update to 3.1.0
     */
    public function update310(): void
    {
        global $languages, $rlDb;

        register_shutdown_function(static function () use ($languages, $rlDb) {
            $defaultDescription = '<strong>There are two ways you can make a payment.</strong><br />1. You may send a money transfer using the details below:<br /><br />Western Union<br />John Dow<br /><br />2. Or you can send a payment to the following bank account:<br />- Bank Name: My B';
            $currentDescription = $rlDb->getOne('Values', "`Key` = 'bankWireTransfer_payment_details'", 'config');

            if ($currentDescription && $currentDescription !== $defaultDescription) {
                foreach ($languages as $language) {
                    $rlDb->updateOne([
                        'fields' => ['Value' => $currentDescription],
                        'where' => [
                            'Code' => $language['Code'],
                            'Key' => 'bwt_payment_details_content',
                        ],
                    ], 'lang_keys');
                }
            }
        });
    }

    /**
     * Remove account fields for shopping cart & bidding plugin
     *
     * @since 3.0.0
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
                'bankWireTransfer_enable',
                'bankWireTransfer_details',
            ),
            $accountsTable
        );
    }

    /**
     * Synchronize account fields with new shopping cart & bidding plugin
     *
     * @since 3.0.0
     */
    public function synchronizeAccountFields()
    {
        global $rlDb, $config;

        if (!$rlDb->tableExists('shc_account_settings')
            || $config['shc_method'] != 'multi'
            || !$rlDb->columnExists('shc_bankWireTransfer_enable', 'accounts')
        ) {
            return true;
        }

        $accounts = [];

        do {
            $sql = "SELECT `ID`, `shc_bankWireTransfer_enable`, `shc_bankWireTransfer_details` FROM `{db_prefix}accounts` ";
            $sql .= "WHERE `shc_bankWireTransfer_enable` = '1' AND `Status` <> 'trash' LIMIT 100";
            $accounts = $rlDb->getAll($sql);

            if ($accounts) {
                foreach ($accounts as $key => $value) {
                    $item = $rlDb->fetch('*', array('Account_ID' => $value['ID']), null, 1, 'shc_account_settings', 'row');

                    if ($item) {
                        $update = array(
                            'fields' => array(
                                'bankWireTransfer_enable' => 1,
                                'bankWireTransfer_details' => $value['shc_bankWireTransfer_details'],
                            ),
                            'where' => array(
                                'ID' => $item['ID'],
                            ),
                        );
                        $rlDb->updateOne($update, 'shc_account_settings');
                    } else {
                        $insert = [
                            'Account_ID' => $value['ID'],
                            'bankWireTransfer_enable' => 1,
                            'bankWireTransfer_details' => $value['shc_bankWireTransfer_details'],
                        ];

                        $rlDb->insertOne($insert, 'shc_account_settings');
                    }

                    $update = array(
                        'fields' => array(
                            'shc_bankWireTransfer_enable' => '0',
                            'shc_bankWireTransfer_details' => '',
                        ),
                        'where' => array(
                            'ID' => $value['ID'],
                        ),
                    );
                    $rlDb->updateOne($update, 'accounts');
                }
            }
        } while (count($accounts) > 0);

        return true;
    }
}
