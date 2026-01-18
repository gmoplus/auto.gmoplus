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
     * Install plugin
     *
     */
    public function install()
    {
        $GLOBALS['rlDb']->createTable('invoices', "
            `ID` int(11) NOT NULL AUTO_INCREMENT,
            `Account_ID` int(11) NOT NULL default '0',
            `Total` double NOT NULL default '0',
            `Txn_ID` varchar(30) NOT NULL default '',
            `Subject` varchar(255) CHARACTER SET utf8 NOT NULL default '',
            `Description` mediumtext CHARACTER SET utf8 NOT NULL,
            `Date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `Pay_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `IP` varchar(100) NOT NULL default '',
            `pStatus` enum('paid','unpaid') NOT NULL DEFAULT 'unpaid',
            PRIMARY KEY (`ID`),
            KEY `Account_ID` (`Account_ID`),
            KEY `pStatus` (`pStatus`)
        ");
    }

    /**
     * Uninstall plugin
     *
     */
    public function uninstall()
    {
        global $rlDb;

        $rlDb->dropTable('invoices');
        $rlDb->query("DELETE FROM `{db_prefix}transactions` WHERE `Service` = 'invoice'");
    }

    /**
     * Update to 2.1.0
     */
    public function update210()
    {
        if (file_exists(RL_PLUGINS . 'invoices' . RL_DS . 'tplFooter.php')) {
            unlink(RL_PLUGINS . 'invoices' . RL_DS . 'tplFooter.php');
        }
        if (file_exists(RL_PLUGINS . 'invoices' . RL_DS . 'gateways.tpl')) {
            unlink(RL_PLUGINS . 'invoices' . RL_DS . 'gateways.tpl');
        }
        if (file_exists(RL_PLUGINS . 'invoices' . RL_DS . 'invoice_details.tpl')) {
            unlink(RL_PLUGINS . 'invoices' . RL_DS . 'invoice_details.tpl');
        }
        if (file_exists(RL_PLUGINS . 'invoices' . RL_DS . 'list.tpl')) {
            unlink(RL_PLUGINS . 'invoices' . RL_DS . 'list.tpl');
        }
    }

    /**
     * Update to 2.1.1
     */
    public function update211()
    {
        global $reefless, $rlDb;

        $dir = RL_PLUGINS . 'invoices/lib';

        if (is_dir($dir)) {
            $reefless->deleteDirectory($dir);
        }

        $rlDb->query("ALTER TABLE `{db_prefix}invoices` ADD INDEX `Account_ID` (`Account_ID`)");
        $rlDb->query("ALTER TABLE `{db_prefix}invoices` ADD INDEX `pStatus` (`pStatus`)");
    }

    /**
     * Update to 2.1.2
     */
    public function update212(): void
    {
        global $languages, $rlDb;

        if (array_key_exists('en', $languages)) {
            if ($rlDb->getOne('ID', "`Key` = 'description_invoices' AND `Code` = 'en'", 'lang_keys')) {
                $rlDb->updateOne([
                    'fields' => ['Value' => 'Allows the Administrator to invoice customers for services that are not monetized on the site'],
                    'where' => ['Key'   => 'description_invoices', 'Code' => 'en'],
                ], 'lang_keys');
            }
        }

        if (array_key_exists('ru', $languages)) {
            if ($rlDb->getOne('ID', "`Key` = 'description_invoices' AND `Code` = 'ru'", 'lang_keys')) {
                $rlDb->updateOne([
                    'fields' => ['Value' => 'Позволяет Администратору выставлять счета за услуги, которые не монетизированы на сайте'],
                    'where' => ['Key'   => 'description_invoices', 'Code' => 'ru'],
                ], 'lang_keys');
            }
        }

        // Remove deprecated files
        unlink(RL_PLUGINS . 'invoices/invoice_details_responsive_42.tpl');
        unlink(RL_PLUGINS . 'invoices/list_responsive_42.tpl');
        unlink(RL_PLUGINS . 'invoices/gateways_440.tpl');
    }

    /**
     * Update to 2.1.3
     */
    public function update213(): void
    {
        $GLOBALS['rlDb']->query(
            "DELETE FROM `{db_prefix}lang_keys`
             WHERE `Plugin` = 'invoices' AND `Key` IN (
                'paid',
                'unpaid',
                'ext_paid',
                'ext_unpaid',
                'invoice_payment'
            )"
        );
    }
}
