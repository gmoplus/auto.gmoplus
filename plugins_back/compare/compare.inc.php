<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLCOMPARE.CLASS.PHP
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

$rlStatic->addHeaderCss(RL_PLUGINS_URL . 'compare/static/page-style.css');

$reefless->loadClass('Compare', false, 'compare');

// Get IDs from saved table
if ($_GET['nvar_1'] || $_GET['sid']) {
    $path = $_GET['sid'] ?: $_GET['nvar_1'];
    \Flynax\Utils\Valid::escape($path);

    $table = $rlDb->fetch(
        '*',
        array('Path' => $path),
        null, 1, 'compare_table', 'row'
    );
    
    if ($table) {
        if ($table['Type'] == 'private' && $table['Account_ID'] != $account_info['ID']) {
            $errors[] = $lang['compare_table_private_only'];
        } else {
            $rlSmarty->assign_by_ref('saved_table', $table);

            $delete_allowed    = $table['Account_ID'] == $account_info['ID'];
            $compare_ids       = $table['IDs'];
            $page_info['name'] = $table['Name'];

            $bread_crumbs[] = array(
                'name' => $table['Name']
            );
        }
    } else {
        $errors[] = $lang['compare_table_not_found'];
    }
}
// Get current table IDs from cookies
else {
    $compare_ids    = $_COOKIE['compare_listings'];
    $delete_allowed = true;
}

$rlSmarty->assign_by_ref('delete_allowed', $delete_allowed);

// Get listings
$rlCompare->get(explode(',', $compare_ids));

// Get saved tables
if (defined('IS_LOGIN')) {
    $saved_tables = $rlDb->fetch(
        array('Name', 'IDs', 'Type', 'Path', 'ID'),
        array('Account_ID' => $account_info['ID']),
        "ORDER BY `Date` DESC", null, 'compare_table'
    );
    $rlSmarty->assign_by_ref('saved_tables', $saved_tables);
}

// Add view mode icon
if (count($rlSmarty->get_template_vars('compare_listings')) > 2) {
    $navIcons[] = <<< HTML
        <a class="button low compare-fullscreen"
           title="{$lang['compare_fullscreen']}"
           href="javascript:void(0)">
            <span>{$lang['compare_fullscreen']}</span>
        </a>
HTML;
    $rlSmarty->assign_by_ref('navIcons', $navIcons);
}
