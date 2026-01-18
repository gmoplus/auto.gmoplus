<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : ESCROWTEST.PHP
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

namespace ShoppingCart;

use ShoppingCart\Payment;

/**
 * @since 3.1.0
 */
class EscrowTest
{
    public function initSafeDeal($orderID)
    {
        $DealID = $GLOBALS['reefless']->generateHash();
        $update = array(
            'fields' => array(
                'Escrow' => '1',
                'Escrow_date' => date('Y-m-d H:i:s', strtotime("+3 months", time())),
                'Deal_ID' => $DealID,
            ),
            'where' => array('ID' => $orderID),

        );
        $GLOBALS['rlDb']->updateOne($update, 'shc_orders');
    }

    /**
     * Confirm order by buyer and make payout to seller
     *
     * @since 3.1.0
     *
     * @param array $txnInfo
     * @return bool
     */
    public function confirmEscrow(array $txnInfo) : bool
    {
        $PayoutID = $GLOBALS['reefless']->generateHash();
        $update = array(
            'fields' => array(
                'Payout_ID' => $PayoutID,
                'Escrow_status' => 'confirmed',
            ),
            'where' => array('ID' => $txnInfo['Item_ID']),

        );

        return $GLOBALS['rlDb']->updateOne($update, 'shc_orders');
    }

    /**
     * Cancel order by buyer and refund payment
     *
     * @since 3.1.0
     *
     * @param array $txnInfo
     * @return bool
     */
    public function cancelEscrow(array $txnInfo) : bool
    {
        $RefundID = $GLOBALS['reefless']->generateHash();
        $update = array(
            'fields' => array(
                'Refund_ID' => $RefundID,
                'Escrow_status' => 'canceled',
                'Status' => 'canceled',
            ),
            'where' => array('ID' => $txnInfo['Item_ID']),
        );

        return $GLOBALS['rlDb']->updateOne($update, 'shc_orders');
    }
}
