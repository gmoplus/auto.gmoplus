<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : CONFIGS.PHP
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

namespace ShoppingCart\Admin;

use ShoppingCart\Payment;

/**
 * @since 3.0.0
 */
class Configs
{
    /**
     * Save settings
     */
    public function saveSettings()
    {
        global $reefless, $config;

        $configs = $_POST['config'];

        foreach ($configs as $cKey => $cVal) {
            $update[] = array(
                'fields' => array(
                    'Default' => is_array($cVal) ? implode(",", $cVal) : $cVal,
                    'Values' => $cKey == 'shc_shipper_address' ? serialize($_POST['f']) : '',
                ),
                'where' => array(
                    'Key' => $cKey,
                ),
            );
        }

        $action = $GLOBALS['rlDb']->update($update, 'config');

        if ($action) {
            $aUrl = array('controller' => $GLOBALS['controller'], 'module' => 'configs', 'form' => 'settings');

            if ($config['shc_method_currency_convert'] != 'single'
                && $configs['shc_method_currency_convert'] == 'single'
            ) {
                $aUrl['convertPrices'] = 1;
            }

            if ($config['shc_use_multifield'] != $configs['shc_use_multifield']) {
                $shippingFields = new \ShoppingCart\Admin\ShippingFields();
                $shippingFields->controlTypes($configs['shc_use_multifield']);
            }

            if ($configs['shc_method'] == 'multi') {
                $GLOBALS['rlShoppingCart']->checkFieldsPaymentGateways();
            }

            $reefless->loadClass('Notice');
            $GLOBALS['rlNotice']->saveNotice($GLOBALS['lang']['config_saved']);
            $reefless->redirect($aUrl);
        }
    }

    /**
     * Prepare data to settings
     */
    public function prepareData()
    {
        global $rlDb, $rlLang, $rlSmarty, $config;

        $shipperAddress = $rlDb->getOne('Values', "`Key` = 'shc_shipper_address'", 'config');

        if ($shipperAddress) {
            $_POST['f'] = unserialize($shipperAddress);
        }

        $groups = $rlDb->fetch(
            array('ID', 'Key'),
            array('Status' => 'active'),
            null,
            1,
            'listing_groups'
        );

        $groups = $rlLang->replaceLangKeys($groups, 'listing_groups', array('name'), RL_LANG_CODE, 'admin');

        $fields = $rlDb->fetch(
            array('Key', 'ID', 'Type'),
            array('Type' => 'price', 'Status' => 'active'),
            null,
            1,
            'listing_fields'
        );

        $fields = $rlLang->replaceLangKeys($fields, 'listing_fields', array('name'), RL_LANG_CODE, 'common');

        $account_types = $GLOBALS['rlAccount']->getAccountTypes();

        $payment_gateways = $GLOBALS['rlPayment']->getGatewaysAll();

        if ($payment_gateways) {
            foreach ($payment_gateways as $pgKey => $pgValue) {
                $payment_gateways[$pgKey]['name'] = $GLOBALS['lang']['payment_gateways+name+' . $pgValue['Key']];

                $payment_gateways[$pgKey]['escrow'] = 0;
                if (Payment::isEscrow($pgValue['Key'])) {
                    $payment_gateways[$pgKey]['escrow'] = 1;
                }
            }
        }

        $rlSmarty->assign_by_ref('groups', $groups);
        $rlSmarty->assign_by_ref('listing_fields', $fields);
        $rlSmarty->assign_by_ref('account_types', $account_types);
        $rlSmarty->assign_by_ref('payment_gateways', $payment_gateways);
    }
}
