<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLCOINGATE.CLASS.PHP
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

class rlCoinGate
{
    /**
     * @hook shoppingCartAccountSettings
     */
    public function hookShoppingCartAccountSettings()
    {
        global $config;

        if (!$config['shc_module'] || $GLOBALS['rlPayment']->gateways['coinGate']['Status'] != 'active') {
            return;
        }

        if ($config['shc_method'] == 'single') {
            $gateways = explode(',', $config['shc_payment_gateways']);

            if (!in_array('coinGate', $gateways)) {
                return;
            }
        }
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'coinGate/account_settings.tpl');
    }

    /**
     * @hook apPaymentGatewaysValidate
     */
    public function hookApPaymentGatewaysValidate(&$errors, $i_key)
    {
        if ($i_key == 'coinGate') {
            if (!is_object('rlGateway')) {
                require_once RL_CLASSES . 'rlGateway.class.php';
            }
            $GLOBALS['reefless']->loadClass('CoinGateGateway', null, 'coinGate');
            $GLOBALS['rlCoinGateGateway']->init();

            // check auth token
            $result = \CoinGate\CoinGate::testConnection();
            if ($result !== true) {
                $errors[] = $GLOBALS['lang']['coinGate_invalid_auth_token'];
            }
        }
    }

    /**
     * Add account fields for shopping cart & bidding plugin
     *
     * @since 1.1.0
     */
    public function addAccountFields()
    {
        global $rlDb, $plugins;

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

        $rlDb->addColumnsToTable(
            array(
                'coinGate_enable' => "ENUM('0','1') NOT NULL DEFAULT '0'",
                'coinGate_auth_token' => "varchar(100) NOT NULL default ''",
                'coinGate_receive_currency' => "varchar(10) NOT NULL default ''",
            ),
            $accountsTable
        );
    }
}
