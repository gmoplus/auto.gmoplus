<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLPAYASYOUGOCREDITSGATEWAY.CLASS.PHP
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

class rlPayAsYouGoCreditsGateway extends rlGateway
{
    /**
     * Start payment process
     */
    public function call()
    {
        global $rlPayment, $rlDb, $reefless, $config;

        $account_info = $rlDb->fetch(
            array('ID', 'Total_credits'),
            array('ID' => $rlPayment->getOption('account_id')),
            null,
            1,
            'accounts',
            'row'
        );

        // generate transaction
        $txn_id = $reefless->generateHash(10, 'upper');

        // calculating the cost of credit
        $total = (float) $rlPayment->getOption('total');

        if (!$total) {
            $this->errors[] = str_replace(
                '{option}',
                $GLOBALS['lang']['payment_option_total'],
                $GLOBALS['lang']['required_payment_option_error']
            );

            return;
        }

        if ($config['payAsYouGoCredits_rate_type'] == 'auto' && $config['payAsYouGoCredits_rate'] > 0) {
            $total = (float) round(($rlPayment->getOption('total') / (float) $config['payAsYouGoCredits_rate']), 2);
            $rlPayment->setOption('total', $total);
        }

        if ($account_info['Total_credits'] >= $total) {
            // update account
            $sql = "UPDATE `{db_prefix}accounts` SET `Total_credits` = `Total_credits` - {$total} ";
            $sql .= "WHERE `ID` = '" . $rlPayment->getOption('account_id') . "' LIMIT 1";
            $rlDb->query($sql);

            $payment_details = array(
                'plan_id' => $rlPayment->getOption('plan_id'),
                'item_id' => $rlPayment->getOption('item_id'),
                'account_id' => $rlPayment->getOption('account_id'),
                'total' => $total,
                'txn_id' => $rlPayment->getTransactionID(),
                'txn_gateway' => $txn_id,
            );

            $rlPayment->complete(
                $payment_details,
                $rlPayment->getOption('callback_class'),
                $rlPayment->getOption('callback_method'),
                $rlPayment->getOption('plugin')
            );
            $redirect_url = $rlPayment->getOption('success_url');
        } else {
            $redirect_url = $rlPayment->getOption('cancel_url');
        }
        $rlPayment->clear();
        $reefless->redirect(false, $redirect_url);
        exit;
    }

    /**
     * Complete payment
     */
    public function callBack()
    {
        // any code
    }

    /**
     * Check settings of the gateway
     */
    public function isConfigured()
    {
        return true;
    }
}
