<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : IFLYNAX_ADMOB.INC.PHP
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

if ($_GET['q'] == 'ext') {
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    if ($_GET['action'] == 'update') {
        $reefless->loadClass('Actions');

        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $key = $rlValid->xSql($_GET['key']);
        $id = (int) $_GET['id'];

        if ($field == 'Side' || $field == 'Status') {
            $updateData = array(
                'fields' => array(
                    $field => $value,
                ),
                'where' => array(
                    'ID' => $id,
                ),
            );
            $rlActions->updateOne($updateData, 'iflynax_admob');
            exit;
        }
    }

    // data read
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];

    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT * FROM `" . RL_DBPREFIX . "iflynax_admob`";
    $sql .= "ORDER BY `ID` DESC ";
    $sql .= "LIMIT {$start}, {$limit}";
    $data = $rlDb->getAll($sql);

    foreach ($data as $key => &$entry) {
        $entry['Side'] = $lang[$entry['Side']];
        $entry['Status'] = $lang[$entry['Status']];
    }

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");
    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
}
// Add/Edit process
else {
    $reefless->loadClass('IFlynaxConnect', null, 'iFlynaxConnect');

    $iflynax_phrases = $rlIFlynaxConnect->getLangPhrases(RL_LANG_CODE);
    $iflynax_phrases['iflynax_account_search_results'] = $lang['iflynax_account_search_results'];
    $rlSmarty->assign('iflynax_phrases', $iflynax_phrases);

    /* additional bread crumb step */
    if ($_GET['action'] == 'add') {
        $bcAStep = $lang['add'];
    } elseif ($_GET['action'] == 'edit') {
        $bcAStep = $lang['edit'];
    }

    $position = array(
        'top' => $lang['top'],
        'bottom' => $lang['bottom'],
    );
    $rlSmarty->assign('position', $position);

    $iflynax_pages = array(
        1 => 'screen_home',
        2 => 'screen_recently_view',
        3 => 'listing_category',
        4 => 'screen_favorite_ads_view',
        5 => 'screen_search',
        6 => 'screen_listing_details',
        7 => 'screen_account_details',
        8 => 'menu_section_account_types',
        9 => 'screen_search_sellers',
        10 => 'iflynax_account_search_results',
        11 => 'screen_comments',
    );

    $rlSmarty->assign('iflynax_pages', $iflynax_pages);

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        $tmp_used_pages = $rlDb->fetch(array('Pages'), null, null, null, 'iflynax_admob');
        $used_pages = array();

        foreach ($tmp_used_pages as $item) {
            $used_pages = array_merge($used_pages, explode(',', $item['Pages']));
        }
        unset($tmp_used_pages);

        $rlSmarty->assign('used_pages', $used_pages);
        $adMob_id = intval($_GET['id']);

        if ($_GET['action'] == 'edit' && !$_POST['fromPost'] && $adMob_id) {
            $adMob = $rlDb->fetch('*', array('ID' => $adMob_id), null, 1, 'iflynax_admob', 'row');

            $_POST['id'] = $adMob_id;
            $_POST['status'] = $adMob['Status'];
            $_POST['position'] = $adMob['Side'];
            $_POST['name'] = $adMob['Name'];
            $_POST['code'] = $adMob['Code'];
            $_POST['pages'] = explode(',', $adMob['Pages']);
        }

        if ($_GET['action'] == 'add' && count($used_pages) >= count($iflynax_pages)) {
            $rlSmarty->assign('alerts', $lang['iflynax_admob_all_pages_used']);
        }

        if (isset($_POST['submit'])) {
            $errors = array();

            if ('' === $f_name = trim($_POST['name'])) {
                $errors[] = str_replace('{field}', sprintf("<b>%s</b>", $lang['name']), $lang['notice_field_empty']);
                $error_fields[] = 'name';
            }

            if ('' === $f_position = trim($_POST['position'])) {
                $_replace = sprintf("<b>%s</b>", $lang['position']);
                $errors[] = str_replace('{field}', $_replace, $lang['notice_select_empty']);
                $error_fields[] = 'position';
            }

            if ('' === $f_code = trim($_POST['code'])) {
                $_replace = sprintf("<b>%s</b>", $lang['iflynax_admob_code']);
                $errors[] = str_replace('{field}', $_replace, $lang['notice_field_empty']);
                $error_fields[] = 'code';
            }

            if (0 === count($_POST['pages'])) {
                $errors[] = $lang['iflynax_admob_pages_empty'];
            }

            if (0 !== count($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            }
            // save the adMob to database
            else {
                if ($_GET['action'] == 'add') {
                    // write main, block information
                    $data = array(
                        'Name' => $f_name,
                        'Code' => $f_code,
                        'Status' => $_POST['status'],
                        'Side' => $f_position,
                        'Pages' => implode(',', $_POST['pages']),
                    );
                    $action = $rlActions->insertOne($data, 'iflynax_admob');

                    if ($action) {
                        $message = $lang['block_added'];
                        $aUrl = array('controller' => $controller);
                    } else {
                        $rlDebug->logger("Can't add new block (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    $data_update = array(
                        'fields' => array(
                            'Name' => $f_name,
                            'Code' => $f_code,
                            'Status' => $_POST['status'],
                            'Side' => $f_position,
                            'Pages' => implode(',', $_POST['pages']),
                        ),
                        'where' => array('ID' => $adMob_id),
                    );
                    $action = $GLOBALS['rlActions']->updateOne($data_update, 'iflynax_admob');

                    $message = $lang['block_edited'];
                    $aUrl = array('controller' => $controller);
                }

                if ($action) {
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                }
            }
        }
    }
}
