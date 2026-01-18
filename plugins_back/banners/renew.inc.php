<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: ADD_BANNER.INC.PHP
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
 *  Flynax Classifieds Software 2024 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

$bread_crumbs[1] = ['name' => $lang['pages+name+my_banners'], 'path' => $pages['my_banners']];
$bread_crumbs[2] = ['name' => $lang['pages+name+banners_renew'], 'path' => $pages['banners_renew']];

if (!$bannerId = (int) $_GET['id']) {
    return $sError = true;
}

$sql = "SELECT `T1`.*, `T2`.`Plan_Type`, `T2`.`Period`, `T2`.`Price` ";
$sql .= "FROM `{db_prefix}banners` AS `T1` ";
$sql .= "LEFT JOIN `{db_prefix}banner_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
$sql .= "WHERE `T1`.`ID` = {$bannerId}";
$banner = $rlDb->getRow($sql);

if (empty($banner) || $banner['Account_ID'] != $account_info['ID']) {
    return $sError = true;
}

$rlSmarty->assign('bannerInfo', $banner);
$bannerStatus = !$config['banners_auto_approval'] ? 'pending' : 'active';

$bannerPlan = $rlBanners->getBannerPlans('ID', $banner['Plan_ID'], 'row');
$rlSmarty->assign('planInfo', [$bannerPlan['ID'] => $bannerPlan]);

$_POST['plan'] = $bannerPlan['ID'];

if (!isset($_POST['submit'])) {
    return;
}

if ($banner['Price'] == 0) {
    $now = time();
    $date_to = ($banner['Period'] != 0
        ? ($banner['Plan_Type'] == 'period'
            ? $now + ($banner['Period'] * 86400)
            : $banner['Date_to'] + $banner['Period'])
        : 0
    );
    $rlDb->query("
        UPDATE `{db_prefix}banners`
        SET `Pay_date` = '{$now}', `Status` = '{$bannerStatus}', `Date_to` = '{$date_to}'
        WHERE `ID` = {$bannerId}
    ");

    if (!$config['banners_auto_approval']) {
        $reefless->loadClass('Mail');

        $bannerLink = RL_URL_HOME . ADMIN . '/index.php?controller=banners&amp;filter=' . $bannerId;
        $bannerLink = '<a href="' . $bannerLink . '">' . $lang['banners+name+' . $bannerId] . '</a>';

        $mail = $rlMail->getEmailTemplate('banners_admin_banner_edited');
        $mail['body'] = strtr($mail['body'], [
            '{username}' => $account_info['Username'],
            '{link}' => $bannerLink,
            '{date}' => date(str_replace(['b', '%'], ['M', ''], RL_DATE_FORMAT)),
            '{status}' => $lang['pending'],
        ]);

        $rlMail->send($mail, $config['notifications_email']);
    }

    $reefless->loadClass('Notice');
    $rlNotice->saveNotice($lang['banners_noticeBannerUpgraded']);

    $url = $reefless->getPageUrl('my_banners');
    $reefless->redirect(null, $url);
} else {
    $bannerTitle = $lang['banners+name+' . $bannerId];
    $itemName = ' #' . $banner['ID'] . ' (' . $bannerTitle . ')';

    $cancel_url = SEO_BASE . ($config['mod_rewrite']
        ? $page_info['Path'] . '.html?id=' . $bannerId . '&canceled'
        : '?page=' . $page_info['Path'] . '&id=' . $bannerId . '&canceled'
    );
    $success_url = $reefless->getPageUrl('my_banners');

    if (!$rlPayment->isPrepare()) {
        $rlPayment->clear();

        $rlPayment->setOption('service', 'banners');
        $rlPayment->setOption('total', $bannerPlan['Price']);
        $rlPayment->setOption('plan_id', $bannerPlan['ID']);
        $rlPayment->setOption('item_id', $bannerId);
        $rlPayment->setOption('item_name', $itemName);
        $rlPayment->setOption('plan_key', 'banner_plans+name+' . $bannerPlan['Key']);
        $rlPayment->setOption('account_id', $account_info['ID']);
        $rlPayment->setOption('plugin', 'banners');
        $rlPayment->setOption('callback_class', 'rlBanners');
        $rlPayment->setOption('callback_method', 'upgradeBanner');
        $rlPayment->setOption('cancel_url', $cancel_url);
        $rlPayment->setOption('success_url', $success_url);

        $rlPayment->init($errors);
    } else {
        $rlPayment->checkout($errors);
    }
}
