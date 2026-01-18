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
        global $rlDb, $languages;

        // add field to transactions
        $rlDb->addColumnsToTable(
            array(
                'Item_data' => "Text NOT NULL default ''",
                'Payment_ID' => "varchar(50) NOT NULL default ''"
            ), 
            'transactions'
        );

        // temporary solution till the compatible will be increased to 4.8.1
        $rlDb->addColumnToTable('Parallel', "ENUM('0','1') NOT NULL DEFAULT '0'", 'payment_gateways');

        $gateway_info = array(
            'Key' => 'yandexKassa',
            'Recurring_editable' => 0,
            'Plugin' => 'yandexKassa',
            'Required_options' => 'yandexKassa_api_id,yandexKassa_secret_key',
            'Form_type' => 'offsite',
            'Parallel' => 1,
        );

        if ($rlDb->insertOne($gateway_info, 'payment_gateways')) {
            if ($languages) {
                foreach ((array) $languages as $lKey => $lValue) {
                    if ($rlDb->getOne('ID', "`Key` = 'payment_gateways+name+yandexKassa' AND `Code` = '{$lValue['Code']}'", 'lang_keys')) {
                        $update_names = array(
                            'fields' => array(
                                'Value' => 'Yandex Kassa',
                            ),
                            'where' => array(
                                'Code' => $lValue['Code'],
                                'Key' => 'listing_groups+name+yandexKassa',
                            ),
                        );
                        $rlDb->updateOne($update_names, 'lang_keys');
                    } else {
                        $insert_names = array(
                            'Code' => $lValue['Code'],
                            'Module' => 'common',
                            'Key' => 'payment_gateways+name+yandexKassa',
                            'Value' => 'Yandex Kassa',
                            'Plugin' => 'yandexKassa',
                        );
                        $rlDb->insertOne($insert_names, 'lang_keys');
                    }
                }
            }
        }

        // only for shoppingCart plugin
        $GLOBALS['reefless']->loadClass('YandexKassa', null, 'yandexKassa');
        $GLOBALS['rlYandexKassa']->addAccountFields();
    }

    /**
     * Uninstall plugin
     */
    public function uninstall()
    {
        global $rlDb;

        // delete row from payment gateways table
        $rlDb->delete(array('Key' => 'yandexKassa'), 'payment_gateways');

        // delete transactions
        $rlDb->delete(array('Gateway' => 'yandexKassa'), 'transactions', null, 0);

        $rlDb->dropColumnFromTable('Payment_ID', 'transactions');

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
                'yandexKassa_enable',
                'yandexKassa_api_id',
                'yandexKassa_secret_key',
                'yandexKassa_account_id',
                'yandexKassa_payment_method',
                'yandexKassa_saved_card',
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
        $GLOBALS['reefless']->deleteDirectory(RL_PLUGINS . 'yandexKassa/vendor');

        $filesystem = new \Symfony\Component\Filesystem\Filesystem;
        $filesystem->mirror(RL_UPLOAD . 'yandexKassa/vendor', RL_PLUGINS . 'yandexKassa/vendor');

        // only for shoppingCart plugin
        $GLOBALS['reefless']->loadClass('YandexKassa', null, 'yandexKassa');
        $GLOBALS['rlYandexKassa']->addAccountFields();

        if ($this->synchronizeAccountFields()) {
            $columns = array(
                'shc_yandexKassa_enable',
                'shc_yandexKassa_api_id',
                'shc_yandexKassa_secret_key',
                'shc_yandexKassa_account_id',
                'shc_yandexKassa_payment_method',
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
            || !$rlDb->columnExists('yandexKassa_enable', 'accounts')
        ) {
            return true;
        }

        $accounts = [];
        $oldFields = array(
            'shc_yandexKassa_enable',
            'shc_yandexKassa_api_id',
            'shc_yandexKassa_secret_key',
            'shc_yandexKassa_account_id',
            'shc_yandexKassa_payment_method',
        );

        $oldFields = implode('`,`', $oldFields);

        do {
            $sql = "SELECT `ID`, `{$oldFields}` FROM `{db_prefix}accounts` ";
            $sql .= "WHERE `shc_yandexKassa_enable` = '1' AND `Status` <> 'trash' LIMIT 100";
            $accounts = $rlDb->getAll($sql);

            if ($accounts) {
                foreach ($accounts as $key => $value) {
                    $item = $rlDb->fetch('*', array('Account_ID' => $value['ID']), null, 1, 'shc_account_settings', 'row');

                    $fields = [
                        'yandexKassa_enable' => 1,
                        'yandexKassa_api_id' => $value['shc_yandexKassa_api_id'],
                        'yandexKassa_secret_key' => $value['shc_yandexKassa_secret_key'],
                        'yandexKassa_account_id' => $value['shc_yandexKassa_account_id'],
                        'yandexKassa_payment_method' => $value['shc_yandexKassa_payment_method'],
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
                            'shc_yandexKassa_enable' => '0',
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

    /**
     * Update to 1.2.0
     */
    public function update120()
    {
        global $rlDb;

        // temporary solution till the compatible will be increased to 4.8.1
        $rlDb->addColumnToTable('Parallel', "ENUM('0','1') NOT NULL DEFAULT '0'", 'payment_gateways');

        if ($rlDb->tableExists('shc_account_settings')) {
            $rlDb->addColumnToTable('yandexKassa_saved_card', "varchar(150) NOT NULL default ''", 'shc_account_settings');
        }

        $update = array(
            'fields' => array(
                'Parallel' => '1',
            ),
            'where' => array(
                'Key' => 'yandexKassa',
            ),
        );
        $rlDb->updateOne($update, 'payment_gateways');

        // Remove legacy phrases
        $phrases = array(
            'yandexKassa_confirmed',
            'yandexKassa_payment_waiting',
            'yandexKassa_key',
            'yandexKassa_secret',
            'yandexKassa_account_id',
            'yandexKassa_api_id',
            'yandexKassa_secret_key',
            'yandexKassa_payment_method',
            'yandexKassa_qiwi_phone',
        );

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
            WHERE `Plugin` = 'yandexKassa' AND `Key` IN ('" . implode("','", $phrases) . "')"
        );
    }
}
