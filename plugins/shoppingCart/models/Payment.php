<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: PAYMENT.PHP
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2025 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

namespace ShoppingCart;

/**
 * @since 3.0.0
 */
class Payment
{
    /**
     * Not replacement credentials for seller
     * The credentials will be replaced in gateway
     *
     * @var array
     */
    public $notReplacement = ['stripe', 'yandexKassa', 'paypalCheckout', 'coinGate'];

    /**
     * Get available gateways only
     *
     * @since 3.1.3
     * @return array - Available gateways array
     */
    public function getActiveGateways(): array
    {
        global $config;

        if (!$config['shc_payment_gateways']) {
            return [];
        }

        $GLOBALS['reefless']->loadClass('Payment');
        $system_gateways = $GLOBALS['rlPayment']->getGatewaysAll();

        if (!$system_gateways) {
            return [];
        }

        $shc_gateways = explode(',', $config['shc_payment_gateways']);

        foreach ($system_gateways as $key => $value) {
            if (!in_array($key, $shc_gateways)) {
                unset($system_gateways[$key]);
            }
        }

        return $system_gateways;
    }

    /**
     * Get payment gateways
     *
     * @param int $dealer_id
     * @param bool $output
     * @return array
     */
    public function getGateways($dealer_id = 0, $output = false)
    {
        global $config;

        $options = $GLOBALS['rlDb']->fetch(
            '*',
            array('Account_ID' => $dealer_id),
            null,
            1,
            'shc_account_settings',
            'row'
        );

        $gateways = $GLOBALS['rlPayment']->getGatewaysAll();

        if ($gateways) {
            foreach (explode(',', $config['shc_payment_gateways']) as $k => $v) {
                $allowed_gateways[$v] = $v;
            }
            foreach ($gateways as $pgKey => $pgValue) {
                if (!isset($allowed_gateways[$pgValue['Key']])) {
                    unset($gateways[$pgKey]);
                    continue;
                }
                if ($config['shc_method'] == 'multi') {
                    if (!$options[$pgValue['Key'] . '_enable']) {
                        unset($gateways[$pgKey]);
                        continue;
                    }
                }
                $gateways[$pgKey]['name'] = $GLOBALS['lang']['payment_gateways+name+' . $pgValue['Key']];
            }
        }

        if ($output) {
            $GLOBALS['rlSmarty']->assign_by_ref('payment_gateways', $gateways);
            $GLOBALS['rlSmarty']->assign_by_ref('dealer_options', $options);
        }

        return $gateways;
    }

    /**
     * Save shopping cart settings of seller
     */
    public function saveAccountSettings()
    {
        global $reefless, $rlDb, $account_info, $rlShoppingCart;

        $options = $rlShoppingCart->getAccountOptions($account_info['ID'], true);

        $shipping = new Shipping();
        $shipping->getMethods(true);
        $shipping->getShippingFields(true);

        // simulate post data
        if (!$_POST['form'] && $options) {
            foreach ($options as $pKey => $pVal) {
                if (!in_array($pKey, array('ID', 'Account_ID'))) {
                    $_POST['shc'][$pKey] = $pVal;
                }
            }

            $_POST['f'] = unserialize($options['Shipping']);
        }

        // get configs name
        self::getConfigNames();

        if ($_POST['form'] == 'settings') {
            $data = array();

            foreach ($_POST['shc'] as $key => $val) {
                $field = $key;
                if (!isset($options[$field])) {
                    unset($data[$key]);
                }

                if (is_array($val)) {
                    $data[$field] = implode(",", $val);
                } else {
                    $data[$field] = $val;
                }
            }

            $data['Shipping'] = serialize($_POST['f']);

            // create field if not exists
            foreach ($data as $field => $val) {
                if (!$rlDb->columnExists($field, 'shc_account_settings')) {
                    if (substr_count($field, '_enable') > 0) {
                        $f_type = "ENUM('0','1') NOT NULL DEFAULT '0'";
                    } else {
                        $f_type = "varchar(50) NOT NULL default ''";
                    }

                    $rlDb->addColumnToTable(
                        $field,
                        $f_type,
                        'shc_account_settings'
                    );
                }
            }

            $result = $rlShoppingCart->saveAccountSettings($data);

            if ($result) {
                $reefless->loadClass('Notice');
                $GLOBALS['rlNotice']->saveNotice($GLOBALS['lang']['notice_profile_edited']);

                $refresh_url = $reefless->getPageUrl('my_profile', array('info' => 'shoppingCart'));
                $reefless->redirect(false, $refresh_url);
            }
        }
    }

    /**
     * Get config names
     */
    public function getConfigNames()
    {
        global $lang;

        if (!isset($lang['config+name+shc_module'])) {
            $lang = array_merge($lang, $GLOBALS['rlShoppingCart']->getPhrases('settings'));
        }

        // prepare config names
        $shcLang = [];
        foreach ($lang as $lKey => $lVal) {
            $pos = strrpos($lKey, '+') + 1;
            if (substr_count($lKey, 'config+name+shc_') > 0) {
                $shcLang[substr($lKey, $pos)] = $lVal;
            }
            if (substr_count($lKey, 'config+des+shc_') > 0) {
                $shcLang[substr($lKey, $pos) . '_des'] = $lVal;
            }
        }

        $GLOBALS['rlSmarty']->assign_by_ref('shcLang', $shcLang);
    }

    /**
     * Set seller payments credentials
     *
     * @param int $sellerID
     * @param string $gateway
     */
    public function setSellerPaymentsCredentials($gateway = '', $sellerID = 0)
    {
        global $config, $rlPayment;

        $service = $rlPayment->getOption('service');

        if ((!$config['shc_module'] && !$config['shc_module_auction'])
            || $config['shc_method'] != 'multi'
            || in_array($gateway, $this->notReplacement)
            || (!in_array($service, ['shopping', 'auction']))
        ) {
            return true;
        }

        $exceptions = ['paypal'];

        $error = false;
        $options = $GLOBALS['rlShoppingCart']->getAccountOptions($sellerID);

        /**
         * @since 3.0.1
         */
        $GLOBALS['rlHook']->load('phpShoppingCartSetCredentials', $options, $gateway);

        if (in_array($gateway, $exceptions)) {
            $exceptionMethod = 'check' . ucfirst($gateway);
            return $this->$exceptionMethod($options);
        }

        if ($options) {
            foreach ($options as $key => $value) {
                if (substr_count($key, $gateway . '_') > 0) {
                    if (!$value) {
                        $error = true;
                        break;
                    }
                    $config[$key] = str_replace('&amp;', '&', $value);
                }
            }
        }

        if (!$error) {
            return true;
        }

        return false;
    }

    /**
     * Check Paypal email from seller
     *
     * @since 3.1.0
     *
     * @param array $options
     * @return bool
     */
    public function checkPaypal(array $options) : bool
    {
        global $config;

        if (!$options['paypal_email']) {
            return false;
        }

        $config['paypal_account_email'] = $options['paypal_email'];

        return true;
    }

    /**
     * Check if gateway supported escrow option
     *
     * @param string $gateway
     * @return bool
     */
    public static function isEscrow(string $gateway)
    {
        global $plugins;

        $gateways = [
            'yandexKassa' => [
                'version' => '1.2.0'
            ],
            'stripe' => [
                'version' => '2.3.0'
            ],
            'escrow' => [
                'version' => '1.0.0'
            ]
        ];

        if (isset($gateways[$gateway]) && version_compare($plugins[$gateway], $gateways[$gateway]['version']) >= 0) {
            return true;
        }

        return false;
    }
}
