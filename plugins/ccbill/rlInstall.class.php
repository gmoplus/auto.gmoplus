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
 *	Copyrights Flynax Classifieds Software | 2022
 *	https://www.flynax.com
 *
 ******************************************************************************/

class rlInstall extends reefless
{
    /**
     * Install plugin
     */
    public function install()
    {
        global $rlDb, $rlActions, $languages;

        $rlDb->query("CREATE TABLE IF NOT EXISTS `" . RL_DBPREFIX . "ccbill_settings` (
            `ID` int(11) NOT NULL auto_increment,
            `Service` varchar(50) NOT NULL,
            `Item_ID`  int(11) NOT NULL default '0',
            `Form` varchar(100) NOT NULL,
            `Allowed_types` varchar(255) NOT NULL,
            PRIMARY KEY (`ID`)
        ) DEFAULT CHARSET=utf8");

        // add field to transactions
        $rlDb->addColumnsToTable(
            array('Item_data' => "Text NOT NULL default ''"),
            'transactions'
        );

        $gateway_info = array(
            'Key' => 'ccbill',
            'Recurring_editable' => 0,
            'Plugin' => 'ccbill',
            'Required_options' => 'ccbill_clientAccnum,ccbill_clientSubacc',
            'Form_type' => 'offsite',
        );

        if ($rlActions->insertOne($gateway_info, 'payment_gateways')) {
            if ($languages) {
                foreach ((array) $languages as $lKey => $lValue) {
                    if ($rlDb->getOne('ID', "`Key` = 'payment_gateways+name+ccbill' AND `Code` = '{$lValue['Code']}'", 'lang_keys')) {
                        $update_names = array(
                            'fields' => array(
                                'Value' => 'CCBill',
                            ),
                            'where' => array(
                                'Code' => $lValue['Code'],
                                'Key' => 'payment_gateways+name+ccbill',
                            ),
                        );
                        $rlActions->updateOne($update_names, 'lang_keys');
                    } else {
                        $insert_names = array(
                            'Code' => $lValue['Code'],
                            'Module' => 'common',
                            'Key' => 'payment_gateways+name+ccbill',
                            'Value' => 'CCBill',
                            'Plugin' => 'ccbill',
                        );
                        $rlActions->insertOne($insert_names, 'lang_keys');
                    }
                }
            }
        }

        $rlDb->addColumnsToTable(
            array('sop_ccbill_type_id' => "varchar(100) NOT NULL default ''"),
            'subscription_plans'
        );
    }

    /**
     * Uninstall plugin
     */
    public function uninstall()
    {
        global $rlDb;

        // delete row from payment gateways table
        $sql = "DELETE FROM `" . RL_DBPREFIX . "payment_gateways` WHERE `Key` = 'ccbill' LIMIT 1";
        $rlDb->query($sql);

        // delete transactions
        $sql = "DELETE FROM `" . RL_DBPREFIX . "transactions` WHERE `Gateway` = 'ccbill'";
        $rlDb->query($sql);

        // remove ccbill settings table
        $rlDb->query("DROP TABLE IF EXISTS `" . RL_DBPREFIX . "ccbill_settings`");

        // delete field from subscription plans table
        $rlDb->dropColumnFromTable('sop_ccbill_type_id', 'subscription_plans');
    }
}
