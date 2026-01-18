<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
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
     * Install plugin
     */
    public function install()
    {
        global $rlDb;

        $rlDb->addColumnsToTable(
            array(
                'Token' => "varchar(100) NOT NULL default ''",
                'Item_data' => "Text NOT NULL default ''",
            ),
            'transactions'
        );

        $gateway_info = array(
            'Key' => 'coinGate',
            'Recurring_editable' => 0,
            'Plugin' => 'coinGate',
            'Required_options' => 'coinGate_auth_token,coinGate_receive_currency',
            'Form_type' => 'offsite',
        );

        if ($rlDb->insertOne($gateway_info, 'payment_gateways')) {
            if ($GLOBALS['languages']) {
                foreach ((array) $GLOBALS['languages'] as $lKey => $lValue) {
                    if ($rlDb->getOne('ID', "`Key` = 'payment_gateways+name+coinGate' AND `Code` = '{$lValue['Code']}'", 'lang_keys')) {
                        $update_names = array(
                            'fields' => array(
                                'Value' => 'CoinGate',
                            ),
                            'where' => array(
                                'Code' => $lValue['Code'],
                                'Key' => 'payment_gateways+name+coinGate',
                            ),
                        );
                        $rlDb->updateOne($update_names, 'lang_keys');
                    } else {
                        $insert_names = array(
                            'Code' => $lValue['Code'],
                            'Module' => 'common',
                            'Key' => 'payment_gateways+name+coinGate',
                            'Value' => 'CoinGate',
                            'Plugin' => 'coinGate',
                        );
                        $rlDb->insertOne($insert_names, 'lang_keys');
                    }
                }
            }
        }

        // only for shoppingCart plugin
        $GLOBALS['reefless']->loadClass('CoinGate', null, 'coinGate');
        $GLOBALS['rlCoinGate']->addAccountFields();
    }

    /**
     * Uninstall plugin
     */
    public function uninstall()
    {
        global $rlDb;

        // delete row from payment gateways table
        $sql = "DELETE FROM `{db_prefix}payment_gateways` WHERE `Key` = 'coinGate' LIMIT 1";
        $rlDb->query($sql);

        // delete transactions
        $sql = "DELETE FROM `{db_prefix}transactions` WHERE `Gateway` = 'coinGate'";
        $rlDb->query($sql);

        $rlDb->dropColumnFromTable('Token', 'transactions');

        // only for shoppingCart plugin
        $this->removeAccountFields();
    }

    /**
     * Remove account fields for shopping cart & bidding plugin
     *
     * @since 1.1.0
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
                'coinGate_enable',
                'coinGate_auth_token',
                'coinGate_receive_currency',
            ),
            $accountsTable
        );
    }

    /**
     * Update to 1.1.0
     */
    public function update110()
    {
        global $rlDb;

        // delete current vendor directory
        $GLOBALS['reefless']->deleteDirectory(RL_PLUGINS . 'coinGate/vendor');

        $filesystem = new \Symfony\Component\Filesystem\Filesystem;
        $filesystem->mirror(RL_UPLOAD . 'coinGate/vendor', RL_PLUGINS . 'coinGate/vendor');

        // only for shoppingCart plugin
        $GLOBALS['reefless']->loadClass('CoinGate', null, 'coinGate');
        $GLOBALS['rlCoinGate']->addAccountFields();

        if ($this->synchronizeAccountFields()) {
            $columns = array(
                'shc_coinGate_enable', 
                'shc_coinGate_auth_token', 
                'shc_coinGate_receive_currency',
            );

            $rlDb->dropColumnsFromTable($columns, 'accounts');
        }
    }

    /**
     * Synchronize account fields with new shopping cart & bidding plugin
     *
     * @since 1.1.0
     */
    public function synchronizeAccountFields()
    {
        global $rlDb, $config;

        if (!$rlDb->tableExists('shc_account_settings') 
            || $config['shc_method'] != 'multi' 
            || !$rlDb->columnExists('shc_coinGate_enable', 'accounts')
        ) {
            return true;
        }

        $accounts = [];
        $oldFields = array(
            'shc_coinGate_enable', 
            'shc_coinGate_auth_token', 
            'shc_coinGate_receive_currency',
        );

        $oldFields = implode('`,`', $oldFields);

        do {
            $sql = "SELECT `ID`, `{$oldFields}` FROM `{db_prefix}accounts` ";
            $sql .= "WHERE `shc_coinGate_enable` = '1' AND `Status` <> 'trash' LIMIT 100";
            $accounts = $rlDb->getAll($sql);

            if ($accounts) {
                foreach ($accounts as $key => $value) {
                    $item = $rlDb->fetch('*', array('Account_ID' => $value['ID']), null, 1, 'shc_account_settings', 'row');

                    $fields = [
                        'coinGate_enable' => 1,
                        'coinGate_auth_token' => $value['shc_coinGate_auth_token'],
                        'coinGate_receive_currency' => $value['shc_coinGate_receive_currency'],
                    ];
                    if ($item) {
                        $update = array(
                            'fields' => $fields,
                            'where' => array(
                                'ID' => $item['ID'],
                            ),
                        );
                        $rlDb->updateOne($update, 'shc_account_settings');
                    } else {
                        $fields['Account_ID'] = $value['ID'];

                        $rlDb->insertOne($fields, 'shc_account_settings');
                    }

                    $update = array(
                        'fields' => array(
                            'shc_coinGate_enable' => '0',
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
