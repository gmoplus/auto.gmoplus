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

if (!defined('IS_LOGIN') || in_array('my_banners', $deny_pages)) {
    return $sError = true;
}

$reefless->loadClass('Banners', null, 'banners');

// redirect to add banner process
if (isset($_GET['incomplete'])) {
    $id = (int) $_GET['incomplete'];
    $step = $_GET['step'];

    $bSteps = $rlBanners->getSteps();
    $bannerInfo = $rlDb->getRow("SELECT `Plan_ID`, `Status` FROM `{db_prefix}banners` WHERE `ID` = {$id}");

    if ($bannerInfo['Status'] === 'incomplete') {
        $_SESSION['add_banner']['plan_id'] = (int) $bannerInfo['Plan_ID'];
        $_SESSION['add_banner']['banner_id'] = $id;

        $url = SEO_BASE . ($config['mod_rewrite']
            ? $pages['add_banner'] . '/' . $bSteps[$step]['path'] . '.html'
            : '?page=' . $pages['add_banner'] . '&step=' . $bSteps[$step]['path']
        );
        $reefless->redirect(null, $url);
    } else {
        return $sError = true;
    }
}

unset($_SESSION['mb_deleted']);

$add_banner_href = $reefless->getPageUrl('add_banner', [rlBanners::getSteps()['plan']['path']]);
$rlSmarty->assign('add_banner_href', $add_banner_href);

// paging info
$pInfo['current'] = (int) $_GET['pg'];

// fields for sorting
$sorting = [
    'shows' => [
        'name' => $lang['banners_bannerShows'],
        'field' => 'Shows',
    ],
    'clicks' => [
        'name' => $lang['banners_bannerClicks'],
        'field' => 'Clicks',
    ],
    'status' => [
        'name' => $lang['status'],
        'field' => 'Status',
    ],
    'expire_date' => [
        'name' => $lang['expire_date'],
        'field' => 'Date_to',
    ],
];
$rlSmarty->assign_by_ref('sorting', $sorting);

// define sort field
$sort_by = empty($_GET['sort_by']) ? $_SESSION['mb_sort_by'] : $_GET['sort_by'];
$order_field = !empty($sorting[$sort_by]) ? $sorting[$sort_by]['field'] : 'Date_to';
$_SESSION['mb_sort_by'] = $sort_by;
$rlSmarty->assign_by_ref('sort_by', $sort_by);

// define sort type
$sort_type = empty($_GET['sort_type']) ? $_SESSION['mb_sort_type'] : $_GET['sort_type'];
$sort_type = in_array($sort_type, ['ASC', 'DESC']) ? $sort_type : 'ASC';
$_SESSION['mb_sort_type'] = $sort_type;
$rlSmarty->assign_by_ref('sort_type', $sort_type);

if ($pInfo['current'] > 1) {
    $bread_crumbs[1]['title'] .= str_replace('{page}', $pInfo['current'], $lang['title_page_part']);
}

$myBanners = $rlBanners->getMyBanners(
    $account_info['ID'],
    $order_field,
    $sort_type,
    $pInfo['current'],
    $config['listings_per_page']
);
$rlSmarty->assign_by_ref('myBanners', $myBanners);

$plans = $rlBanners->getBannerPlans();
$available_plans = count($plans) ? true : false;
$rlSmarty->assign('available_plans', $available_plans);

if (!empty($myBanners) && $available_plans) {
    $rlSmarty->assign('navIcons', [
        '<a class="button low" title="" href="' . $add_banner_href . '">' . $lang['banners_addBanner'] . '</a>',
    ]);
}

$pInfo['calc'] = $rlBanners->calc;
$rlSmarty->assign_by_ref('pInfo', $pInfo);


