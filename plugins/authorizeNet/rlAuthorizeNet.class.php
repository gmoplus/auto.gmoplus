<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.7.2
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: RLAUTHORIZENET.CLASS.PHP
 *
 *	The software is a commercial product delivered under single, non-exclusive,
 *	non-transferable license for one domain or IP address. Therefore distribution,
 *	sale or transfer of the file in whole or in part without permission of Flynax
 *	respective owners is considered to be illegal and breach of Flynax License End
 *	User Agreement.
 *
 *	You are not allowed to remove this information from the file without permission
 *	of Flynax respective owners.
 *
 *	Flynax Classifieds Software 2022 |  All copyrights reserved.
 *
 *	https://www.flynax.com/
 *
 ******************************************************************************/

class rlAuthorizeNet
{
    /**
     * @hook apTplListingPlansForm
     */
    public function hookApTplListingPlansForm()
    {
        $this->displayPlanForm();
    }

    /**
     * @hook shoppingCartAccountSettings
     */
    public function hookShoppingCartAccountSettings()
    {
        global $config;

        $gateways = explode(',', $config['shc_payment_gateways']);

        if ((!$config['shc_module'] && !$config['shc_module_auction'])
            || $GLOBALS['rlPayment']->gateways['authorizeNet']['Status'] != 'active'
            || !in_array('authorizeNet', $gateways)
        ) {
            return;
        }

        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'authorizeNet/account_settings.tpl');
    }

    /**
     * @hook apPhpPaymetGatewaysSettings
     */
    public function hookApPhpPaymetGatewaysSettings(&$param1)
    {
        if ($param1) {
            foreach ($param1 as $sKey => $sValue) {
                if ($sValue['Key'] == 'authorizeNet_type') {
                    $tmp = explode(',', $sValue['Values']);

                    foreach ($tmp as $k => $v) {
                        $values[$v] = array(
                            'ID' => $v,
                            'name' => $GLOBALS['lang']['authorizeNet_' . strtolower($v)],
                        );
                    }
                    $param1[$sKey]['Values'] = $values;
                }
            }
        }
    }

    /**
     * @hook apTplPaymentGatewaysBottom
     */
    public function hookApTplPaymentGatewaysBottom()
    {
        if ($_GET['item'] == 'authorizeNet') {
            $url = RL_PLUGINS_URL . 'authorizeNet/static/authorizenet-speed-configuration-guide.pdf';
            echo <<< FL
<script type="text/javascript">
    $(document).ready(function(){
        $('label[for="recurring_no"]').after('<span style="line-height: 14px;margin: 0 10px;" class="field_description"><a class="static" href="{$url}" target="_blank">{$GLOBALS['lang']['authorizeNet_speed_configuration_guide']}</a></span>');
    });
</script>
FL;
        }
    }

    /**
     * @hook apPhpGatewayUpdateSettings
     *
     * @since 2.4.0
     */
    public function hookApPhpGatewayUpdateSettings(&$update, $key, $value)
    {
        if ($key != 'authorizeNet_type') {
            return;
        }

        $gateway = array(
            'fields' => array(
                'Form_type' => $value == 'AIM' ? 'default' : 'offsite',
            ),
            'where' => array(
                'Key' => 'authorizeNet',
            ),
        );
        $GLOBALS['rlDb']->updateOne($gateway, 'payment_gateways');
    }

    /**
     * Add account fields for shopping cart & bidding plugin
     *
     * @since 2.4.0
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
                'authorizeNet_enable' => "ENUM('0','1') NOT NULL DEFAULT '0'",
                'authorizeNet_transaction_key' => "varchar(150) NOT NULL default ''",
                'authorizeNet_account_id' => "varchar(150) NOT NULL default ''",
            ),
            $accountsTable
        );
    }

    /**
     * @hook topPaymentPage
     *
     * @since 2.4.0
     */
    public function hookTopPaymentPage()
    {
        if ($GLOBALS['config']['authorizeNet_type'] != 'SIM') {
            return;
        }

        if (strtolower($_GET['rlVareables']) == 'authorizenet') {
            $_GET['gateway'] = 'authorizeNet';
            $_GET['rlVareables'] = rlPayment::POST_URL;
        }
    }

    /**
     * @hook apTplMembershipPlansForm
     *
     * @since 2.4.0
     */
    public function hookApTplMembershipPlansForm()
    {
        $this->displayPlanForm();
    }

    /**
     * Display plan form
     *
     * @since 2.4.0
     */
    public function displayPlanForm()
    {
        global $rlPayment;

        if ($rlPayment->gateways['authorizeNet']['Status'] == 'active'
            && $rlPayment->gateways['authorizeNet']['Recurring']
        ) {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'authorizeNet/admin/plan_form.tpl');
        } else {
            echo <<< FL
<script type="text/javascript">
$(document).ready(function(){
$('input[name="sop[sop_authorizenet_interval_length]"]').parent().parent().remove();
});
</script>
FL;
        }
    }

    /**
     * @deprecated 2.4.0
     *
     * @hook apPhpPaymentGatewaysAfterEdit
     */
    public function hookApPhpPaymentGatewaysAfterEdit()
    {}
}
