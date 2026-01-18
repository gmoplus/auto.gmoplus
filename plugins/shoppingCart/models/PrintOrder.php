<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : PRINTORDER.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2022
 *	https://www.flynax.com
 *
 ******************************************************************************/

namespace ShoppingCart;

/**
 * @since 3.0.0
 */
class PrintOrder
{
    /**
     * Print page
     */
    public function print()
    {
        global $rlSmarty;

        $rlSmarty->display('controllers/print.tpl');

        exit;
    }

    /**
     * Print order details
     *
     * @param array $data
     */
    public function printShopping($data)
    {
        global $rlSmarty;

        $rlSmarty->assign_by_ref('orderInfo', $data);
        $rlSmarty->display(RL_PLUGINS . 'shoppingCart/view/order_details_print.tpl');
    }

    /**
     * Print auction details
     *
     * @param array $data
     */
    public function printAuction($data)
    {
        global $rlSmarty;

        $rlSmarty->assign_by_ref('auction_info', $data);
        $rlSmarty->display(RL_PLUGINS . 'shoppingCart/view/auction_details.tpl');
    }
}
