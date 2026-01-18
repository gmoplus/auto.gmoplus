<?php


/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: FAQS.INC.PHP
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

if ($config['mod_rewrite']) {
    $path = $rlValid->xSql($_GET['nvar_1']);

    // trailing number mode
    if ($_GET['listing_id']) {
        $path .= '-' . $_GET['listing_id'];
    }
    $faqs_id = $rlDb->getOne('ID', "`Path` = '{$path}'", 'faqs');
} else {
    $faqs_id = (int) $_GET['id'];
}

$pInfo['current'] = (int) $_GET['pg'];

$reefless->loadClass('FAQs', null, 'FAQs');


if (empty($faqs_id)) {
    if ($pInfo['current'] > 1) {
        $bc_page = str_replace('{page}', $pInfo['current'], $lang['title_page_part']);

        /* add bread crumbs item */
        $bread_crumbs[1]['title'] .= $bc_page;
    }

    $all_faqs = $rlFAQs->get(false, true, $pInfo['current'], true);
    $rlSmarty->assign_by_ref('all_faqs', $all_faqs);

    $pInfo['calc'] = $rlFAQs->calc_faqs;
    $rlSmarty->assign_by_ref('pInfo', $pInfo);

    $rlHook->load('faqsList');

} else {
    $faqs = $rlFAQs->get($faqs_id, true);
    $rlSmarty->assign('faqs', $faqs);

    $bread_crumbs[] = array(
        'name' => $faqs['title'],
    );
    $page_info['title']            = $page_info['name'] = $faqs['title'];
    $page_info['h1']               = $faqs['h1'] ? $faqs['h1'] : $faqs['title'];
    $page_info['meta_description'] = $faqs['meta_description'];

    $rlHook->load('faqsItem');
}
