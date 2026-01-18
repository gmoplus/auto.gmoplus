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
     */
    public function install()
    {
        global $rlDb;

        $rlDb->createTable('sms_activation_details', "
            `ID` int(11) NOT NULL auto_increment,
            `Account_ID` int(11) NOT NULL default 0,
            `smsActivation` enum('0','1') NOT NULL default '0',
            `smsActivation_code` varchar(10) NOT NULL default '',
            `smsActivation_exists` enum('0','1') NOT NULL default '0',
            `smsActivation_count_attempts` int(4) NOT NULL default 0,
            `smsActivation_listing_id` int(4) NOT NULL default 0,
            PRIMARY KEY (`ID`),
            KEY `Account_ID` (`Account_ID`)
        ");

        $update = array(
            'fields' => array(
                'Group_ID' => '0',
            ),
            'where' => array('Key' => 'sms_activation_activate_exists'),
        );
        $rlDb->update($update, 'config');

        // save accounts as active
        $sql = "SELECT `ID` FROM `{db_prefix}accounts` WHERE `Status` <> 'trash'";
        $accounts = $rlDb->getAll($sql);

        if ($accounts) {
            foreach ($accounts as $key => $val) {
                $data = array(
                    'Account_ID' => $val['ID'],
                    'smsActivation' => '1',
                    'smsActivation_code' => 'done',
                    'smsActivation_exists' => '1',
                );
                $rlDb->insertOne($data, 'sms_activation_details');
            }
        }

        $rlDb->addColumnToTable(
            'smsActivation_module',
            "ENUM( '0', '1' ) NOT NULL DEFAULT '1' AFTER `Status`",
            'account_types'
        );
    }

    /**
     * Uninstall plugin
     */
    public function uninstall()
    {
        global $rlDb;

        $rlDb->dropTable('sms_activation_details');
        $rlDb->dropColumnFromTable('smsActivation_module', 'account_types');
    }

    /**
     * Update to 2.2.0
     */
    public function update220()
    {
        global $rlDb;

        $GLOBALS['rlDb']->dropColumnsFromTable(
            array(
                'smsActivation',
                'smsActivation_code',
                'smsActivation_exists',
                'smsActivation_count_attempts',
            ),
            'accounts'
        );

        $rlDb->createTable('sms_activation_details', "
            `ID` int(11) NOT NULL auto_increment,
            `Account_ID` int(11) NOT NULL default 0,
            `smsActivation` enum('0','1') NOT NULL default '0',
            `smsActivation_code` varchar(4) NOT NULL default '',
            `smsActivation_exists` enum('0','1') NOT NULL default '0',
            `smsActivation_count_attempts` int(4) NOT NULL default 0,
            `smsActivation_listing_id` int(4) NOT NULL default 0,
            PRIMARY KEY (`ID`)
        ");

        // update configs
        $update = array(
            array(
                'fields' => array(
                    'Default' => $GLOBALS['config']['sms_activation_api_id'],
                ),
                'where' => array('Key' => 'sms_activation_api_key'),
            ),
            array(
                'fields' => array(
                    'Group_ID' => 0,
                ),
                'where' => array('Key' => 'sms_activation_activate_exists'),
            ),
        );

        $rlDb->update($update, 'config');

        // save accounts as active
        $sql = "SELECT `ID` FROM `{db_prefix}accounts` WHERE `Status` <> 'trash'";
        $accounts = $rlDb->getAll($sql);

        if ($accounts) {
            foreach ($accounts as $key => $val) {
                $data = array(
                    'Account_ID' => $val['ID'],
                    'smsActivation' => '1',
                    'smsActivation_code' => 'done',
                    'smsActivation_exists' => '1',
                );
                $rlDb->insertOne($data, 'sms_activation_details');
            }
        }

        // remove old configs
        $sql = "DELETE FROM `{db_prefix}config` WHERE `Plugin` = 'smsActivation' ";
        $sql .= "AND (`Key` = 'sms_activation_username' OR `Key` = 'sms_activation_password' OR `Key` = 'sms_activation_api_id')";
        $rlDb->query($sql);

        // remove old files
        @unlink(RL_PLUGINS . 'smsActivation/account_activation.inc.php');
        @unlink(RL_PLUGINS . 'smsActivation/account_activation.tpl');
        @unlink(RL_PLUGINS . 'smsActivation/completed.tpl');
        @unlink(RL_PLUGINS . 'smsActivation/sesExpired.tpl');
        @unlink(RL_PLUGINS . 'smsActivation/request.php');
    }

    /**
     * Update to 2.2.1
     */
    public function update221()
    {
        require_once RL_UPLOAD . 'smsActivation/vendor/autoload.php';
        $filesystem = new \Symfony\Component\Filesystem\Filesystem;
        $filesystem->mirror(RL_UPLOAD . 'smsActivation/vendor', RL_PLUGINS . 'smsActivation/vendor');
    }

    /**
     * Update to 2.3.0
     */
    public function update230()
    {
        $GLOBALS['reefless']->deleteDirectory(RL_PLUGINS . 'smsActivation/vendor');

        require_once RL_UPLOAD . 'smsActivation/vendor/autoload.php';
        $filesystem = new \Symfony\Component\Filesystem\Filesystem;
        $filesystem->mirror(RL_UPLOAD . 'smsActivation/vendor', RL_PLUGINS . 'smsActivation/vendor');

        $GLOBALS['rlDb']->addColumnToTable(
            'smsActivation_module',
            "ENUM( '0', '1' ) NOT NULL DEFAULT '1' AFTER `Status`",
            'account_types'
        );
    }

    /**
     * Update to 2.4.0
     */
    public function update240(): void
    {
        global $rlDb;

        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 8 WHERE `Key` = 'sms_activation_method'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 9 WHERE `Key` = 'sms_activation_api_key'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 10 WHERE `Key` = 'sms_activation_smsru_api_key'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 11 WHERE `Key` = 'sms_activation_late_confirm'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 12 WHERE `Key` = 'sms_activation_count_attempts'");
    }
}
