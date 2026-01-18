<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLPAYASYOUGOCREDITS.CLASS.PHP
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

class rlPayAsYouGoCredits extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * @hook phpGetPaymentGatewaysAfter
     * @since 2.0.1
     */
    public function hookPhpGetPaymentGatewaysAfter(&$content)
    {
        global $rlSmarty, $rlPayment, $config;

        if ($rlPayment->getOption('service') != 'credits'
            && $rlPayment->gateways['payAsYouGoCredits']['Status'] == 'active'
            && !$rlPayment->isRecurring()
        ) {
            if (in_array($rlPayment->getOption('service'), array('shopping', 'auction'))
                && $config['shc_method'] == 'multi'
            ) {
                return;
            }
            if (in_array($rlPayment->getOption('service'), array('shopping', 'auction'))
                && !in_array('payAsYouGoCredits', explode(",", $config['shc_payment_gateways']))
            ) {
                return;
            }
            $account_total_credits = $GLOBALS['rlDb']->getOne(
                'Total_credits',
                "`ID` = '{$GLOBALS['account_info']['ID']}'",
                'accounts'
            );
            $rlSmarty->assign('account_total_credits', (float) $account_total_credits);
            $rlSmarty->assign('price_item', $rlPayment->getOption('total'));
            $content .= $rlSmarty->fetch(RL_PLUGINS . 'payAsYouGoCredits/gateway.tpl');
        }
    }

    /**
     * @hook apTplAccountsForm
     * @since 1.0.0
     */
    public function hookApTplAccountsForm()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'payAsYouGoCredits/admin/apTplAccountsForm.tpl');
    }

    /**
     * @hook apPhpAccountsPost
     * @since 1.1.0
     */
    public function hookApPhpAccountsPost()
    {
        $_POST['Total_credits'] = $GLOBALS['account_info']['Total_credits'];
    }

    /**
     * @hook apPhpAccountsAfterAdd
     * @since 2.0.0
     */
    public function hookApPhpAccountsAfterAdd()
    {
        $GLOBALS['reefless']->loadClass('Credits', false, 'payAsYouGoCredits');
        $GLOBALS['rlCredits']->updateCreditsForAccount();
    }

    /**
     * @hook apPhpAccountsAfterEdit
     * @since 2.0.0
     */
    public function hookApPhpAccountsAfterEdit()
    {
        $GLOBALS['reefless']->loadClass('Credits', false, 'payAsYouGoCredits');
        $GLOBALS['rlCredits']->updateCreditsForAccount();
    }

    /**
     * @hook tplHeader
     * @since 1.1.1
     */
    public function hookTplHeader()
    {
        $pages = [
            'my_credits',
            'add_listing',
            'my_packages',
            'payment',
            'my_profile',
            'shc_my_shopping_cart',
            'shc_auction_payment',
            'bumpup_page',
            'highlight_page',
            'invoices',
            'upgrade_listing',
        ];

        $GLOBALS['rlStatic']->addFooterCSS(RL_PLUGINS_URL . 'payAsYouGoCredits/static/style.css', $pages);
    }

    /**
     * @hook paymentHistorySqlWhere
     * @since 1.1.0
     */
    public function hookPaymentHistorySqlWhere(&$sql)
    {
        if (isset($_GET['credits'])) {
            $sql .= "AND `Service` = 'credits' ";
        }
    }

    /**
     * @hook apPhpPaymetGatewaysSettings
     * @since 2.0.0
     */
    public function hookApPhpPaymetGatewaysSettings(&$settings)
    {
        if ($settings) {
            foreach ($settings as $sKey => $sValue) {
                if ($sValue['Key'] == 'payAsYouGoCredits_rate_type') {
                    $tmp = explode(',', $sValue['Values']);

                    foreach ($tmp as $k => $v) {
                        $values[$v] = array(
                            'ID' => $v,
                            'name' => $GLOBALS['lang']['rate_type_' . $v],
                        );
                    }
                    $settings[$sKey]['Values'] = $values;
                }
            }
        }
    }

    /**
     * @hook cronAdditional
     * @since 1.0.0
     */
    public function hookCronAdditional()
    {
        global $rlDb, $config;

        if ($config['payAsYouGoCredits_period'] > 0) {
            $rlDb->query(
                "UPDATE `{db_prefix}accounts` SET `Total_credits` = 0, `paygc_pay_date` = '0000-00-00 00:00:00'
                 WHERE `paygc_pay_date` != '0000-00-00 00:00:00'
                    AND `paygc_pay_date` < DATE_SUB(NOW(), INTERVAL {$config['payAsYouGoCredits_period']} MONTH)"
            );
        }
    }

    /**
     * @hook phpGetPaymentGatewaysItem
     * @since 2.1.0
     */
    public function hookPhpGetPaymentGatewaysItem(&$gateway)
    {
        if ($gateway['Key'] == 'payAsYouGoCredits') {
            $gateway['ready'] = true;
        }
    }

    /**
     * @hook apAjaxRequest
     * @since 2.1.2
     */
    public function hookApAjaxRequest(&$out = null, $item = null): void
    {
        if ($item !== 'deleteCreditPackage') {
            return;
        }

        $out = ['status' => 'ERROR'];

        if (!$id = (int) $_REQUEST['id']) {
            return;
        }

        global $rlDb;

        $rlDb->delete(['ID' => $id], 'credits_manager');

        $sql = "UPDATE `{db_prefix}config` SET `Default` = ROUND((SELECT MAX(@Price_one:=`Price`/`Credits`) AS `MaxPriceCredit` ";
        $sql .= "FROM `{db_prefix}credits_manager` LIMIT 1), 2) WHERE `Key` = 'paygc_rate_hide' LIMIT 1";
        $rlDb->query($sql);

        $out = ['status' => 'OK'];
    }

    /**
     * @hook  apExtTransactionItem
     * @since 2.1.2
     */
    public function hookApExtTransactionItem(&$data, $key, $value)
    {
        if (empty($data['Service']) && $value['Service'] === 'credits') {
            $data['Service'] = $GLOBALS['rlLang']->getPhrase('ext_credits', null, null, true);
        }
    }

    /**
     * Install plugin
     * @since 2.1.2 - Moved from rlInstall.class.php
     */
    public function install()
    {
        global $rlDb;

        $rlDb->addColumnsToTable([
            'Total_credits'  => "DOUBLE NOT NULL DEFAULT '0'",
            'paygc_pay_date' => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
        ], 'accounts');

        $rlDb->createTable('credits_manager', "
            `ID` int(11) NOT NULL auto_increment,
            `Price`  double NOT NULL default '0',
            `Credits`  int(11) NOT NULL default '0',
            `Position` int(4) NOT NULL default 0,
            `Status` enum('active','approval') NOT NULL default 'active',
            PRIMARY KEY (`ID`)
        ");

        $rlDb->insertOne([
            'Key'                => 'payAsYouGoCredits',
            'Recurring_editable' => 0,
            'Plugin'             => 'payAsYouGoCredits',
            'Type'               => 'offline',
            'Required_options'   => '',
        ], 'payment_gateways');
    }

    /**
     * Uninstall plugin
     * @since 2.1.2 - Moved from rlInstall.class.php
     */
    public function uninstall()
    {
        global $rlDb;

        $rlDb->dropTable('credits_manager');

        $rlDb->dropColumnsFromTable([
            'Total_credits',
            'paygc_pay_date',
        ], 'accounts');

        $rlDb->query("DELETE FROM `{db_prefix}payment_gateways` WHERE `Key` = 'payAsYouGoCredits' LIMIT 1");
        $rlDb->query("DELETE FROM `{db_prefix}transactions` WHERE `Gateway` = 'payAsYouGoCredits' OR `Service` = 'credits'");
    }
    /**
     * Update to 2.0.0
     * @since 2.1.2
     */
    public function update200()
    {
        global $rlDb;

        $sql = "SELECT `ID` FROM `{db_prefix}payment_gateways` WHERE `Key` = 'payAsYouGoCredits' LIMIT 1";
        $payment_gateway = $rlDb->getRow($sql);

        if (empty($payment_gateway['ID'])) {
            $gateway_info = array(
                    'Key' => 'payAsYouGoCredits',
                    'Recurring_editable' => 0,
                    'Plugin' => 'payAsYouGoCredits',
                    'Type' => 'offline'
                );

            if ($rlDb->insertOne($gateway_info, 'payment_gateways')) {
                if ($GLOBALS['languages']) {
                    foreach ((array)$GLOBALS['languages'] as $lValue) {
                        if ($rlDb->getOne('ID', "`Key` = 'payment_gateways+name+payAsYouGoCredits' AND `Code` = '{$lValue['Code']}'", 'lang_keys')) {
                            $update_names = array(
                                'fields' => array(
                                    'Value' => 'Pay-as-you-go Credits'
                                ),
                                'where' => array(
                                    'Code' => $lValue['Code'],
                                    'Key' => 'listing_groups+name+payAsYouGoCredits'
                                )
                            );
                            $rlDb->updateOne($update_names, 'lang_keys');
                        } else {
                            $insert_names = array(
                                'Code' => $lValue['Code'],
                                'Module' => 'common',
                                'Key' => 'payment_gateways+name+payAsYouGoCredits',
                                'Value' => 'Pay-as-you-go Credits',
                                'Plugin' => 'payAsYouGoCredits'
                            );
                            $rlDb->insertOne($insert_names, 'lang_keys');
                        }
                    }
                }
            }
        }
        // delete old configs
        $sql = "DELETE FROM `" . RL_DBPREFIX . "config` WHERE `Key` = 'paygc_period' OR `Key` = 'paygc_rate_common' ";
        $rlDb->query($sql);

        // delete old config group
        $sql = "DELETE FROM `" . RL_DBPREFIX . "config_groups` WHERE `Key` = 'payAsYouGoCredits' ";
        $rlDb->query($sql);
    }

    /**
     * Update to 2.1.0
     * @since 2.1.2 - Moved from rlInstall.class.php
     */
    public function update210()
    {
        $update = array(
            'fields' => array(
                'Form_type' => 'offline',
                'Required_options' => '',
            ),
            'where' => array(
                'Key' => 'payAsYouGoCredits',
            ),
        );
        $GLOBALS['rlDb']->updateOne($update, 'payment_gateways');

        // delete old hooks
        $sql = "DELETE FROM `" . RL_DBPREFIX . "hooks` WHERE `Plugin` = 'payAsYouGoCredits' ";
        $sql .= "AND (`Name` = 'paymentGateway' OR `Name` = 'phpPaymentHistoryLoop' OR `Name` = 'apExtTransactionsData' ";
        $sql .= "OR `Name` = 'bankWireTransferReturnPageTpl' OR `Name` = 'phpGetPaymentGatewaysWhere')";
        $GLOBALS['rlDb']->query($sql);

        /* remove unnecessary folders */
        $removing_folders = array('controllers');
        foreach ($removing_folders as $folder) {
            $folder_path = RL_PLUGINS . 'payAsYouGoCredits/' . $folder;
            $scaned_files = glob($folder_path . '/*', GLOB_MARK);

            if ($scaned_files) {
                foreach ($scaned_files as $file) {
                    unlink($file);
                }
            }

            rmdir($folder_path);
        }

        /* remove unnecessary files */
        $removing_files = array('cronAdditional.php', 'return_page.tpl');
        foreach ($removing_files as $file) {
            unlink(RL_PLUGINS . 'payAsYouGoCredits/' . $file);
        }
    }

    /**
     * Update to 2.1.1
     * @since 2.1.2 - Moved from rlInstall.class.php
     */
    public function update211()
    {
        $path = RL_PLUGINS . 'payAsYouGoCredits/';
        $files = [
            'admin/apPhpAccountsAfterAdd.php',
            'admin/apPhpAccountsAfterEdit.php',
            'admin/apPhpAccountsPost.php',
            'static/style_responsive_42.css',
            'packages_responsive_42.tpl',
        ];

        foreach ($files as $file) {
            if (file_exists($path . $file)) {
                unlink($path . $file);
            }
        }
    }

    /**
     * Update to 2.1.2 version
     */
    public function update212()
    {
        global $rlDb;

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
             WHERE `Plugin` = 'payAsYouGoCredits'
             AND `Key` IN (
                'ext_credits_manager',
                'paygc_my_credits',
                'ext_total_credits',
                'paygc_total',
                'paygc_payment_completed',
                'paygc_payment_canceled',
                'paygc_credits',
                'paygc_paid_date',
                'paygc_payment_info',
                'paygc_buy',
                'paygc_sufficient',
                'paygc_expiration_date',
                'paygc_back_to_overview',
                'credits',
                'payAsYouGoCredits_divider',
                'paygc_months'
             )"
        );
        $rlDb->query(
            "DELETE FROM `{db_prefix}config` WHERE `Plugin` = 'payAsYouGoCredits' AND `Key` IN ('payAsYouGoCredits_divider')"
        );

        $rlDb->query(
            "UPDATE `{db_prefix}lang_keys` SET `Value` = 'Allows users to buy pay-as-you-go credits for purchasing packages, plans, and other services'
             WHERE `Key` = 'description_payAsYouGoCredits' AND `Value` = 'Pay-as-you-go Credits'"
        );

        unlink(RL_PLUGINS . 'payAsYouGoCredits/rlInstall.class.php');

        if (in_array('ru', array_keys($GLOBALS['languages']))) {
            $russianTranslation = json_decode(file_get_contents(RL_PLUGINS . 'payAsYouGoCredits/i18n/ru.json'), true);

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
