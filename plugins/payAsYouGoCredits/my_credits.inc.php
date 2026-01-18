<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: MY_CREDITS.INC.PHP
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

$reefless->loadClass('Mail');
$reefless->loadClass('Notice');
$reefless->loadClass('Credits', null, 'payAsYouGoCredits');

// detect purchase page
if ($config['mod_rewrite']) {
    $purchasePage = $_GET['nvar_1'] == 'purchase' ? true : false;
} else {
    $purchasePage = isset($_GET['purchase']) ? true : false;
}
$rlSmarty->assign('purchasePage', $purchasePage);

if ($purchasePage) {
    // add bread crumbs item
    $bread_crumbs[] = ['name' => $lang['paygc_purchase_credits']];
    $page_info['name'] = $lang['paygc_purchase_credits'];

    if ($config['payAsYouGoCredits_period']) {
        $rlSmarty->assign(
            'paygcDescPeriod',
            str_replace('{number}', $config['payAsYouGoCredits_period'], $lang['paygc_desc_period'])
        );
    }

    if ($_POST['submit']) {
        if (!empty($_POST['package_id'])) {
            $package_id = (int) $_POST['package_id'];
        } else {
            $errors[] = $lang['paygc_empty_credit'];
        }

        if (!$errors) {
            $package_info = $rlDb->fetch(
                array('ID', 'Price', 'Credits', 'Position', 'Status'),
                array('ID' => $package_id),
                null,
                1,
                'credits_manager',
                'row'
            );

            $return_url = $reefless->getPageUrl('my_credits') . ($config['mod_rewrite'] ? '?' : '&');
            $cancel_url = $return_url . 'canceled';
            $success_url = $return_url . 'completed';

            // clear payment options
            $rlPayment->clear();
            $rlPayment->setRedirect();

            $rlHook->load('addCreditsCheckoutPreRedirect');

            $phrase_key = 'credits_manager+name+credit_package_' . $package_id;

            // set payment options
            $rlPayment->setOption('service', 'credits');
            $rlPayment->setOption('total', (float) $package_info['Price']);
            $rlPayment->setOption('item_id', $package_id);
            $rlPayment->setOption('item_name', $lang[$phrase_key] . ' (#' . $package_id . ')');
            $rlPayment->setOption('account_id', $account_info['ID']);
            $rlPayment->setOption('callback_class', 'rlCredits');
            $rlPayment->setOption('callback_method', 'addCredits');
            $rlPayment->setOption('cancel_url', $cancel_url);
            $rlPayment->setOption('success_url', $success_url);
            $rlPayment->setOption('plugin', 'payAsYouGoCredits');

            // set bread crumbs
            $rlPayment->setBreadCrumbs(array(
                'name' => $lang['pages+name+my_credits'],
                'title' => $lang['pages+title+my_credits'],
                'path' => $pages['my_credits'],
            ));

            $rlPayment->init($errors);
        }
    }

    // get credits list
    $credits = $rlCredits->get();
    $rlSmarty->assign_by_ref('credits', $credits);
} else {
    $creditsInfo = $rlCredits->getCreditsInfo();
    $rlSmarty->assign_by_ref('creditsInfo', $creditsInfo);

    // set notifications
    if (isset($_GET['completed'])) {
        $rlSmarty->assign_by_ref('pNotice', $lang['payment_completed']);
    }

    if (isset($_GET['canceled'])) {
        $errors[] = $lang['payment_canceled'];
    }
}
