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
 *	Copyrights Flynax Classifieds Software | 2024
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

        // TODO - remove this code after the plugin compatible became 4.8.2 or above
        $rlDb->addColumnToTable('Parallel', "ENUM('0','1') NOT NULL DEFAULT '0'", 'payment_gateways');

        // insert item to payment gateways table
        $gateway_info = array(
            'Key' => 'stripe',
            'Recurring_editable' => 1,
            'Plugin' => 'stripe',
            'Required_options' => 'stripe_publishable_key,stripe_secret_key',
            'Form_type' => 'custom',
            'Parallel' => 1,
        );

        if ($rlDb->insertOne($gateway_info, 'payment_gateways')) {
            if ($languages) {
                foreach ((array) $languages as $lKey => $lValue) {
                    if ($rlDb->getOne('ID', "`Key` = 'payment_gateways+name+stripe' AND `Code` = '{$lValue['Code']}'", 'lang_keys')) {
                        $update_names = array(
                            'fields' => array(
                                'Value' => 'Stripe',
                            ),
                            'where' => array(
                                'Code' => $lValue['Code'],
                                'Key' => 'payment_gateways+name+stripe',
                            ),
                        );
                        $rlDb->updateOne($update_names, 'lang_keys');
                    } else {
                        $insert_names = array(
                            'Code' => $lValue['Code'],
                            'Module' => 'common',
                            'Key' => 'payment_gateways+name+stripe',
                            'Value' => 'Stripe',
                            'Plugin' => 'stripe',
                        );
                        $rlDb->insertOne($insert_names, 'lang_keys');
                    }
                }
            }
        }

        // TODO - remove this code after the plugin compatible became 4.8.2 or above
        $rlDb->addColumnToTable('Item_data', "Text NOT NULL default ''", 'transactions');

        $rlDb->addColumnToTable('Stripe_item_data', "varchar(255) NOT NULL default ''", 'subscriptions');
        $rlDb->addColumnToTable('stripe_key', "varchar(100) NOT NULL default ''", 'listing_plans');
        $rlDb->addColumnToTable('stripe_key', "varchar(100) NOT NULL default ''", 'membership_plans');

        // only for shoppingCart plugin
        $GLOBALS['reefless']->loadClass('Stripe', null, 'stripe');
        $GLOBALS['rlStripe']->addAccountFields();
    }

    /**
     * Update to 1.2.0
     */
    public function update120()
    {
        global $rlDb;

        $update = array(
            'fields' => array(
                'Form_type' => 'custom',
                'Required_options' => 'stripe_publishable_key,stripe_secret_key',
            ),
            'where' => array(
                'Key' => 'stripe',
            ),
        );
        $GLOBALS['rlActions']->updateOne($update, 'payment_gateways');

        // delete old configs
        $sql = "DELETE FROM `" . RL_DBPREFIX . "config` WHERE `Key` = 'stripe_divider' OR `Key` = 'stripe_module' ";
        $rlDb->query($sql);

        // delete page
        $sql = "DELETE FROM `" . RL_DBPREFIX . "pages` WHERE `Key` = 'stripe' ";
        $rlDb->query($sql);

        $old_hooks = array(
            'tplHeader',
            'paymentGateway',
            'specialBlock',
            'shoppingCartCheckPaymentDetails',
            'myListingsBottom',
            'myListingsIcon',
            'apPhpListingPlansPost',
            'apPhpListingPlansTop',
            'apPhpListingPlansValidate',
            'apPhpListingPlansBeforeAdd',
            'apPhpListingPlansBeforeEdit',
            'apTplListingPlansForm',
            'creditCardPaymentBottom',
        );

        $sql = "DELETE FROM `" . RL_DBPREFIX . "hooks` WHERE `Plugin` = 'stripe' ";
        $sql .= "AND `Name` IN ('" . implode("','", $old_hooks) . "')";
        $rlDb->query($sql);
    }

    /**
     * Update to 2.0.0
     */
    public function update200()
    {
        require_once RL_UPLOAD . 'stripe/vendor/autoload.php';
        $filesystem = new \Symfony\Component\Filesystem\Filesystem;
        $filesystem->mirror(RL_UPLOAD . 'stripe/vendor', RL_PLUGINS . 'stripe/vendor');

        $GLOBALS['reefless']->deleteDirectory(RL_PLUGINS . 'stripe/lib');
    }

    /**
     * Update to 2.1.0
     */
    public function update210()
    {
        global $rlDb;

        // delete current vendor directory
        $GLOBALS['reefless']->deleteDirectory(RL_PLUGINS . 'stripe/vendor');

        require_once RL_UPLOAD . 'stripe/vendor/autoload.php';
        $filesystem = new \Symfony\Component\Filesystem\Filesystem;
        $filesystem->mirror(RL_UPLOAD . 'stripe/vendor', RL_PLUGINS . 'stripe/vendor');

        $sql = "DELETE FROM `{db_prefix}hooks` WHERE `Plugin` = 'stripe' ";
        $sql .= "AND `Name` = 'apTplPaymentGatewaysBottom'";
        $rlDb->query($sql);

        $rlDb->addColumnToTable('stripe_key', "varchar(50) NOT NULL default ''", 'listing_plans');
        $rlDb->addColumnToTable('stripe_key', "varchar(50) NOT NULL default ''", 'membership_plans');
        $rlDb->addColumnsToTable(
            array(
                'stripe_customer_id' => "varchar(50) NOT NULL default ''",
                'stripe_payment_method' => "varchar(50) NOT NULL default ''",
            ),
            'accounts'
        );

        $rlDb->addColumnToTable('Stripe_item_data', "varchar(255) NOT NULL default ''", 'subscriptions');

        $rlDb->query("UPDATE `{db_prefix}subscriptions` SET `Stripe_item_data` = `Stirpe_item_data`");

        // delete old field
        $rlDb->dropColumnFromTable('Stirpe_item_data', 'subscriptions');

        if (file_exists(RL_PLUGINS . 'stripe/my_listings.tpl')) {
            unlink(RL_PLUGINS . 'stripe/my_listings.tpl');
        }
    }

    /**
     * Uninstall plugin
     */
    public function uninstall()
    {
        global $rlDb;

        // get gateway info
        $sql = "SELECT * FROM `" . RL_DBPREFIX . "payment_gateways` WHERE `Key` = 'stripe' LIMIT 1";
        $gateway_info = $rlDb->getRow($sql);

        // delete row from payment gateways table
        $sql = "DELETE FROM `" . RL_DBPREFIX . "payment_gateways` WHERE `Key` = 'stripe' LIMIT 1";
        $rlDb->query($sql);

        // delete transactions
        $sql = "DELETE FROM `" . RL_DBPREFIX . "transactions` WHERE `Gateway` = 'stripe'";
        $rlDb->query($sql);

        // delete transactions
        $sql = "DELETE FROM `" . RL_DBPREFIX . "subscriptions` WHERE `Gateway_ID` = '{$gateway_info['ID']}'";
        $rlDb->query($sql);

        // only for shoppingCart plugin
        $this->removeAccountFields();

        // delete field from subscription plans table
        $rlDb->dropColumnFromTable('Stripe_item_data', 'subscriptions');

        $rlDb->dropColumnFromTable('stripe_key', 'listing_plans');
        $rlDb->dropColumnFromTable('stripe_key', 'membership_plans');
    }

    /**
     * Remove account fields for shopping cart & bidding plugin
     *
     * @since 2.2.0
     */
    public function removeAccountFields()
    {
        global $rlDb, $plugins;

        $rlDb->dropColumnsFromTable(
            array(
                'stripe_customer_id',
            ),
            'accounts'
        );

        if (!$plugins['shoppingCart']) {
            return;
        }

        $accountsTable = 'shc_account_settings';

        if (!$rlDb->tableExists('shc_account_settings')) {
            $accountsTable = 'accounts';

            if (version_compare($GLOBALS['config']['rl_version'], '4.8.2') >= 0) {
                return;
            }
        }

        $rlDb->dropColumnsFromTable(
            array(
                'stripe_enable',
                'stripe_publishable_key',
                'stripe_secret_key',
                'stripe_account_id',
                'stripe_payment_method',
                'stripe_account_confirmed',
            ),
            $accountsTable
        );
    }

    /**
     * Update to 2.2.0
     */
    public function update220()
    {
        global $rlDb, $plugins;

        if (!$plugins['shoppingCart']) {
            return;
        }

        $accountsTable = 'shc_account_settings';

        if ($rlDb->tableExists($accountsTable)) {
            $rlDb->addColumnsToTable(
                array(
                    'stripe_enable' => "ENUM('0','1') NOT NULL DEFAULT '0'",
                    'stripe_publishable_key' => "varchar(150) NOT NULL default ''",
                    'stripe_secret_key' => "varchar(150) NOT NULL default ''",
                    'stripe_account_id' => "varchar(50) NOT NULL default ''",
                    'stripe_account_confirmed' => "ENUM('0','1') NOT NULL DEFAULT '0'",
                    'stripe_payment_method' => "varchar(50) NOT NULL default ''",
                ),
                $accountsTable
            );
            return;
        }

        $rlDb->query("ALTER TABLE `{db_prefix}accounts` CHANGE `shc_stripe_enable` `stripe_enable` ENUM('0','1');");
        $rlDb->query("ALTER TABLE `{db_prefix}accounts` CHANGE `shc_stripe_publishable_key` `stripe_publishable_key` varchar(150);");
        $rlDb->query("ALTER TABLE `{db_prefix}accounts` CHANGE `shc_stripe_secret_key` `stripe_secret_key` varchar(150);");

        // add new fields
        $rlDb->addColumnsToTable(
            array(
                'stripe_account_id' => "varchar(50) NOT NULL default ''",
                'stripe_account_confirmed' => "ENUM('0','1') NOT NULL DEFAULT '0'",
                'stripe_payment_method' => "varchar(50) NOT NULL default ''",
            ),
            'accounts'
        );

        $hooks = [
            'apPhpListingPlansAfterAdd',
            'apPhpListingPlansAfterEdit',
            'apPhpMembershipPlansAfterAdd',
            'apPhpMembershipPlansAfterEdit'
        ];

        $hooks = implode(',', $hooks);
        $sql = "DELETE FROM `{db_prefix}hooks` WHERE `Plugin` = 'stripe' ";
        $sql .= "AND FIND_IN_SET(`Name`, '{$hooks}') > 0 ";
        $rlDb->query($sql);
    }

    /**
     * Update to 2.2.1
     */
    public function update221()
    {
        // delete current vendor directory
        $GLOBALS['reefless']->deleteDirectory(RL_PLUGINS . 'stripe/vendor');

        $filesystem = new \Symfony\Component\Filesystem\Filesystem;
        $filesystem->mirror(RL_UPLOAD . 'stripe/vendor', RL_PLUGINS . 'stripe/vendor');

        if (file_exists(RL_PLUGINS . 'stripe/static/style.css')) {
            unlink(RL_PLUGINS . 'stripe/static/style.css');
        }

        // TODO - remove this code after compatible 4.8.2 or more
        $GLOBALS['rlDb']->addColumnToTable('Item_data', "Text NOT NULL default ''", 'transactions');
    }

    /**
     * Update to 2.2.2
     */
    public function update222()
    {
        global $rlDb;

        // delete current vendor directory
        $GLOBALS['reefless']->deleteDirectory(RL_PLUGINS . 'stripe/vendor');

        $filesystem = new \Symfony\Component\Filesystem\Filesystem;
        $filesystem->mirror(RL_UPLOAD . 'stripe/vendor', RL_PLUGINS . 'stripe/vendor');

        $configs = [
            'stripe_cvc',
            'stripe_avs'
        ];
        $configs = implode(',', $configs);

        $sql = "DELETE FROM `{db_prefix}config` WHERE `Plugin` = 'stripe' ";
        $sql .= "AND FIND_IN_SET(`Key`, '{$configs}') > 0 ";
        $rlDb->query($sql);

        $keys = [
            'config+name+stripe_cvc',
            'config+name+stripe_avs'
        ];
        $keys = implode(',', $keys);

        $sql = "DELETE FROM `{db_prefix}lang_keys` WHERE `Plugin` = 'stripe' ";
        $sql .= "AND FIND_IN_SET(`Key`, '{$keys}') > 0 ";
        $rlDb->query($sql);

        if (file_exists(RL_PLUGINS . 'stripe/static/stripe-speed-configuration-guide.pdf')) {
            unlink(RL_PLUGINS . 'stripe/static/stripe-speed-configuration-guide.pdf');
        }
    }

    /**
     * Update to 2.3.0
     */
    public function update230(): void
    {
        global $rlDb;

        $rlDb->addColumnToTable('Parallel', "ENUM('0','1') NOT NULL DEFAULT '0'", 'payment_gateways');

        $rlDb->updateOne([
            'fields' => ['Parallel' => '1'],
            'where'  => ['Key' => 'stripe'],
        ], 'payment_gateways');
    }

    /**
     * Update to 2.3.1
     */
    public function update231(): void
    {
        global $rlDb, $languages, $config;

        $enPhrases = [
            'title_stripe'       => 'Stripe',
            'description_stripe' => 'Stripe payment gateway',
        ];
        foreach ($enPhrases as $enPhraseKey => $enPhraseValue) {
            if (!$rlDb->getOne('ID', "`Key` = '{$enPhraseKey}' AND `Code` = '{$config['lang']}'", 'lang_keys')) {
                $rlDb->insertOne([
                    'Code'   => $config['lang'],
                    'Module' => 'common',
                    'Key'    => $enPhraseKey,
                    'Value'  => $enPhraseValue,
                    'Plugin' => 'stripe',
                ], 'lang_keys');
            }
        }

        if (array_key_exists('ru', $languages)) {
            $russianTranslation = json_decode(file_get_contents(RL_UPLOAD . 'stripe/i18n/ru.json'), true);

            foreach ($russianTranslation as $phraseKey => $phraseValue) {
                if (!$rlDb->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $insertPhrase = $rlDb->fetch(
                        ['Module', 'Key', 'Plugin'],
                        ['Code' => $GLOBALS['config']['lang'], 'Key' => $phraseKey],
                        null, 1, 'lang_keys', 'row'
                    );

                    $insertPhrase['Code']  = 'ru';
                    $insertPhrase['Value'] = $phraseValue;

                    $rlDb->insertOne($insertPhrase, 'lang_keys');
                } else {
                    $rlDb->updateOne([
                        'fields' => ['Value' => $phraseValue],
                        'where'  => ['Key'   => $phraseKey, 'Code' => 'ru'],
                    ], 'lang_keys');
                }
            }
        }
    }
}
