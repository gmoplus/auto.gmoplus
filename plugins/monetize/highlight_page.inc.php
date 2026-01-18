<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: HIGHLIGHT_PAGE.INC.PHP
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

$id = (int) $_GET['id'];

$reefless->loadClass('Listings');
$listing_info = $rlListings->getListing($id);

if (!$id || $listing_info['Account_ID'] != $account_info['ID']) {
    unset($blocks['monetize_listing_detail']);
    $rlCommon->defineBlocksExist($blocks);

    $sError = true;
    return;
}

$reefless->loadClass('Monetize', null, 'monetize');
$reefless->loadClass('Highlight', null, 'monetize');

$rlSmarty->assign('back_url', $_SESSION['m_back_to']);
$rlSmarty->assign('listing_info', $listing_info);

$rlMonetize->breadCrumbs();

$plans = $rlMonetize->getPlans(false, false, 'active', 'highlight', 'ID');
$rlSmarty->assign('plans', $plans);
$rlSmarty->assign('firstIndex', key($plans));

$lang['next_service_will_apply'] = str_replace(
    '{package_name}', 
    '<b>' . $plans[key($plans)]['name'] . '</b>', 
    $lang['next_service_will_apply']
);

if ($_POST && $_POST['buy_highlight']) {
    //redirect pages
    $success_url = $reefless->getPageUrl('highlight_page', false, false, 'id=' . $id . '&completed');
    $cancel_url = $reefless->getPageUrl('highlight_page', false, false, 'id=' . $id . '&canceled');

    $planID = (int) $_POST['plan'];
    $plan_info = $plans[$planID];

    if (!$plan_info) {
        $errors[] = $lang['m_plan_not_selected'];
        return;
    }

    if ($plan_info['Price'] <= 0 
        || ($plan_info['Using_ID'] && ($plan_info['Highlights_available'] > 0 || $plan_info['Is_unlim']))
    ) {
        $redirect_url = $rlHighlight->highlight($id, $planID, $account_info['ID']) ? $success_url : $cancel_url;
        $reefless->redirect(null, $redirect_url);
        exit;
    } else {
        $rlPayment->clear();
        $rlPayment->setRedirect();
        $rlPayment->setOption('service', 'highlight');
        $rlPayment->setOption('total', $plan_info['Price']);
        $rlPayment->setOption('plan_id', $planID);
        $rlPayment->setOption('item_id', $listing_info['ID']);
        $rlPayment->setOption('item_name', $plan_info['name'] . ' (#' . $planID . ')');
        $rlPayment->setOption('account_id', $account_info['ID']);
        $rlPayment->setOption('plugin', 'monetize');
        $rlPayment->setOption('params', 'monetize');
        $rlPayment->setOption('callback_class', 'rlHighlight');
        $rlPayment->setOption('callback_method', 'highlight');
        $rlPayment->setOption('cancel_url', $cancel_url);
        $rlPayment->setOption('success_url', $success_url);
        
        $rlPayment->init($errors);
    }
}
