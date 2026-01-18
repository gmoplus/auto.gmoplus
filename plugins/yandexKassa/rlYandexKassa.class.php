<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLYANDEXKASSA.CLASS.PHP
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

class rlYandexKassa
{
    /**
     * @hook shoppingCartAccountSettings
     * @since 1.0.0
     */
    public function hookShoppingCartAccountSettings()
    {
        global $config;

        if (!$config['shc_module'] || $GLOBALS['rlPayment']->gateways['yandexKassa']['Status'] != 'active') {
            return;
        }

        if ($config['shc_method'] == 'single') {
            $gateways = explode(',', $config['shc_payment_gateways']);

            if (!in_array('yandexKassa', $gateways)) {
                return;
            }
        }

        $GLOBALS['rlSmarty']->assign_by_ref('yk_methods', $this->getPaymentMethods());
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'yandexKassa' . RL_DS . 'account_settings.tpl');
    }

    /**
     * @hook bottomPaymentPage
     * @since 1.0.0
     */
    public function hookBottomPaymentPage()
    {
        global $sError;

        if ($_GET['rlVareables'] == 'yandexKassa'
            && isset($_GET['yandex_transaction_tracking_id'])
            && isset($_GET['yandex_merchant_reference'])
        ) {
            $sError = false;
            $this->initGateway();
            $GLOBALS['rlYandexGateway']->handleRedirect(
                $_GET['yandex_transaction_tracking_id'], 
                $_GET['yandex_merchant_reference']
            );
        }
    }

    /**
     * @hook apPhpPaymetGatewaysSettings
     */
    public function hookApPhpPaymetGatewaysSettings(&$settings)
    {
        if ($settings) {
            $values = array();

            foreach ($settings as $sKey => $sValue) {
                if ($sValue['Key'] == 'yandexKassa_payment_method') {
                    $tmp = explode(',', $sValue['Values']);

                    foreach ($tmp as $k => $v) {
                        $values[$v] = array(
                            'ID' => $v,
                            'name' => $GLOBALS['lang']['yandexKassa_' . $v],
                        );
                    }

                    $settings[$sKey]['Values'] = $values;
                }
            }
        }
    }

    /**
     * @hook ajaxRequest
     */
    public function hookAjaxRequest(&$out, $request_mode, $request_item, $request_lang)
    {
        global $rlPayment, $rlDb, $account_info, $rlLang;

        if ($request_mode == 'yandexKassaCheck' && $request_item) {
            $this->initGateway();

            $payment = $GLOBALS['rlYandexKassaGateway']->client->getPaymentInfo($request_item);

            switch ($payment->status) {
                case 'waiting_for_capture' :
                case 'succeeded' :
                    if ($payment->status == 'waiting_for_capture') {
                        $result = $GLOBALS['rlYandexKassaGateway']->confirm($request_item);
                    }
                    if ($result) {
                        $out = array(
                            'status' => 'OK',
                            'url' => $rlPayment->getOption('success_url'),
                        );
                    } else {
                        $out = array(
                            'status' => 'CANCELED',
                            'url' => $rlPayment->getOption('canceled_url'),
                        );
                    }
                    break;
                    
                case 'canceled' :
                    $out = array(
                        'status' => 'CANCELED',
                        'url' => $rlPayment->getOption('canceled_url'),
                    );
                    break;
                    
                case 'pending' :
                    $out = array(
                        'status' => 'PENDING',
                    );
                    break;
                    
                default: 
                    $out = array(
                        'status' => 'ERROR',
                    );
                    break;
            }
        }

        if ($request_mode == 'yandexKassaSaveToken') {
            $bankName = $GLOBALS['rlValid']->xSql($_REQUEST['bankName']);
            $panmask = $GLOBALS['rlValid']->xSql($_REQUEST['panmask']);
            $synonim = $GLOBALS['rlValid']->xSql($_REQUEST['synonim']);
            $type = $GLOBALS['rlValid']->xSql($_REQUEST['type']);

            if (!empty($panmask) && !empty($synonim)) {
                $savedCard = $bankName . '| '. $panmask . '| '. $synonim . '| '. $type;

                $result = $this->saveCardInfo($savedCard);

                $out = array(
                    'status' => $result ? 'OK' : 'ERROR',
                    'message' => $result
                        ? $rlLang->getPhrase('yandexKassa_saved_card_ok', $request_lang, null, true)
                        : 'Fail save card',
                );
            } else {
                $out = array(
                    'status' => 'ERROR',
                    'message' => 'No date for card',
                );
            }

        }

        if ($request_mode == 'yandexKassaRemoveToken') {
            $result = $this->saveCardInfo();

            $out = array(
                'status' => $result ? 'OK' : 'ERROR',
                'message' => $result
                    ? $rlLang->getPhrase('yandexKassa_remove_card_ok', $request_lang, null, true)
                    : 'Fail save card',
            );
        }
    }

    /**
     * @hook apPhpGatewayUpdateSettings
     */
    public function hookApPhpGatewayUpdateSettings(&$update, $key, $value)
    {
        if ($key != 'yandexKassa_payment_method') {
            return;
        }

        if ($value == 'mobile_balance' || $value == 'alfabank') {
            $type = 'custom';
        } else {
            $type = 'offsite';
        }

        $gateway = array(
            'fields' => array(
                'Form_type' => $type,
            ),
            'where' => array(
                'Key' => 'yandexKassa',
            ),
        );
        $GLOBALS['rlDb']->updateOne($gateway, 'payment_gateways');
    }

    /**
     * Get payment methods
     *
     * @return []
     */
    public function getPaymentMethods()
    {
        global $lang;

        $methods = array(
            array('key' => 'bank_card', 'name' => $lang['yandexKassa_bank_card']),
            array('key' => 'yandex_money', 'name' => $lang['yandexKassa_yandex_money']),
            array('key' => 'sberbank', 'name' => $lang['yandexKassa_sberbank']),
            array('key' => 'qiwi', 'name' => $lang['yandexKassa_qiwi']),
            array('key' => 'webmoney', 'name' => $lang['yandexKassa_webmoney']),
            array('key' => 'alfabank', 'name' => $lang['yandexKassa_alfabank']),
            array('key' => 'cash', 'name' => $lang['yandexKassa_cash']),
            array('key' => 'all', 'name' => $lang['yandexKassa_all']),
        );

        return $methods;
    }

    /**
     * @hook apTplPaymentGatewaysBottom
     */
    public function hookApTplPaymentGatewaysBottom()
    {
        if ($_REQUEST['item'] != 'yandexKassa') {
            return;
        }

        echo <<< FL
        <script>
            $(document).ready(function() {
                controlYandexMethods($('select[name="post_config[yandexKassa_payment_method]"] option:selected').val());

                $('select[name="post_config[yandexKassa_payment_method]"]').change(function() {
                    controlYandexMethods($(this).val());
                });
                
                var note = '<tr><td></td><td class="field"><b>{$GLOBALS['lang']['yandexKassa_currency_note']}</b></td></tr>';
                $('select[name="status"]').closest('tr').after(note);
            });
            
            var controlYandexMethods = function(method) {
                if (method == 'qiwi') {
                    $('input[name="post_config[yandexKassa_qiwi_phone]"]').closest('tr').removeClass('hide');
                } else {
                    $('input[name="post_config[yandexKassa_qiwi_phone]"]').closest('tr').addClass('hide');
                }
            }
        </script>
FL;
    }

    /**
     * Initialize gateway class
     */
    public function initGateway()
    {
        if (!is_object('rlGateway')) {
            require_once RL_CLASSES . 'rlGateway.class.php';
        }
        $GLOBALS['reefless']->loadClass('YandexKassaGateway', null, 'yandexKassa');
        $GLOBALS['rlYandexKassaGateway']->init();
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
                'yandexKassa_enable' => "ENUM('0','1') NOT NULL DEFAULT '0'",
                'yandexKassa_api_id' => "varchar(150) NOT NULL default ''",
                'yandexKassa_secret_key' => "varchar(150) NOT NULL default ''",
                'yandexKassa_account_id' => "varchar(50) NOT NULL default ''",
                'yandexKassa_payment_method' => "varchar(50) NOT NULL default ''",
                'yandexKassa_saved_card' => "varchar(150) NOT NULL default ''",
            ),
            $accountsTable
        );
    }

    /**
     * Save credit card info for payout
     *
     * @since 1.2.0
     *
     * @param $savedCard
     * @return mixed
     */
    public function saveCardInfo($savedCard = '')
    {
        return $GLOBALS['rlDb']->updateOne(array(
            'fields' => array(
                'yandexKassa_saved_card' => $savedCard,
            ),
            'where' => array(
                'Account_ID' => $GLOBALS['account_info']['ID'],
            ),
        ), 'shc_account_settings');
    }

    /**
     * @hook staticDataRegister
     *
     * @since 1.2.0
     *
     * @param \rlStatic $rlStatic
     */
    public function hookStaticDataRegister(&$rlStatic)
    {
        $rlStatic->addHeaderCSS(RL_PLUGINS_URL . 'yandexKassa/static/style.css', [
            'profile'
        ]);
    }

    /**
     * Confirm order by buyer and make payout to seller
     *
     * @since 1.2.0
     *
     * @param array $orderInfo
     * @return bool
     */
    public function confirmEscrow(array $orderInfo) : bool
    {
        global $rlDb, $config;

        if (!$orderInfo) {
            return false;
        }

        $txnInfo = $this->getTxnInfo($orderInfo['ID']);

        if (!$txnInfo) {
            return false;
        }

        if ($config['shc_debug_escrow']) {
            $GLOBALS['reefless']->loadClass('ShoppingCart', null, 'shoppingCart');
            return $GLOBALS['rlShoppingCart']->getEscrowTest()->confirmEscrow($txnInfo);
        }

        $this->initGateway();

        $total = $orderInfo['Total'];
        if ($config['shc_commission_enable'] && $config['shc_commission'] > 0 && $config['shc_method'] == 'multi') {
            $total = $orderInfo['Total'] - $orderInfo['Commission_total'];
        }

        return $GLOBALS['rlYandexKassaGateway']->payout($txnInfo, $orderInfo['Deal_ID'], $total);
    }

    /**
     * Cancel order by buyer and refund payment
     *
     * @since 1.2.0
     *
     * @param array $orderInfo
     * @return bool
     */
    public function cancelEscrow(array $orderInfo) : bool
    {
        global $rlDb, $config;

        if (!$orderInfo) {
            return false;
        }

        $txnInfo = $this->getTxnInfo($orderInfo['ID']);

        if (!$txnInfo) {
            return false;
        }

        if ($config['shc_debug_escrow']) {
            $GLOBALS['reefless']->loadClass('ShoppingCart', null, 'shoppingCart');
            return $GLOBALS['rlShoppingCart']->getEscrowTest()->cancelEscrow($txnInfo);
        }

        $this->initGateway();

        return $GLOBALS['rlYandexKassaGateway']->refund($txnInfo);
    }

    /**
     * Get transaction details
     *
     * @since 1.2.0
     *
     * @param int $orderID
     * @return array
     */
    public function getTxnInfo(int $orderID) : array
    {
        global $rlDb;

        $sql = "SELECT * FROM `{db_prefix}transactions` WHERE `Item_ID` = {$orderID} AND `Service` IN ('shopping', 'auction') ";

        return (array) $rlDb->getRow($sql);
    }
}
