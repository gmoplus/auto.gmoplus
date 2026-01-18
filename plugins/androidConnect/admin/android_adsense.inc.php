<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : ANDROID_ADSENSE.INC.PHP
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
if ($_GET['q'] == 'ext') {
    // system config
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    if ($_GET['action'] == 'update') {
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
            $rlDb->updateOne($updateData, 'android_adsense');
            exit;
        }
    }

    // data read
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];

    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT * FROM `" . RL_DBPREFIX . "android_adsense`";
    $sql .= "ORDER BY `ID` DESC ";
    $sql .= "LIMIT {$start}, {$limit}";
    $data = $rlDb->getAll($sql);

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");
    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
    unset($output);

} else {
    $reefless->loadClass('AndroidConnect', null, 'androidConnect');

    $android_phrases = $rlAndroidConnect->getLangPhrases(RL_LANG_CODE);
    $rlSmarty->assign_by_ref('android_phrases', $android_phrases);

    /* additional bread crumb step */
    if ($_GET['action'] == 'add') {
        $bcAStep = $lang['add'];
    } else if ($_GET['action'] == 'edit') {
        $bcAStep = $lang['edit'];
    }
    /* get adsense position */
    $side = array(
        'top' => $lang['top'],
        'bottom' => $lang['bottom'],
    );
    $rlSmarty->assign_by_ref('side', $side);

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        $used = $rlDb->fetch(array('Pages'), null, null, null, 'android_adsense');

        foreach ($used as $item) {
            $n_item = explode(',', $item['Pages']);
            foreach ($n_item as $ui) {
                $used_screens[] = $ui;
            }
            unset($n_item);
        }
        $rlSmarty->assign_by_ref('used_screens', $used_screens);

        /* get pages */
        $id = $rlValid->xSql($_GET['id']);

        // get current adsense info
        $adsense = $rlDb->fetch('*', array('ID' => $id), null, 1, 'android_adsense', 'row');
        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $_POST['id'] = $adsense['ID'];
            $_POST['status'] = $adsense['Status'];
            $_POST['side'] = $adsense['Side'];
            $_POST['name'] = $adsense['Name'];
            $_POST['code'] = $adsense['Code'];

            $m_pages = explode(',', $adsense['Pages']);
            foreach ($m_pages as $page_key) {
                $_POST['pages'][$page_key] = $page_key;
            }
            unset($m_pages);
        }

        if (isset($_POST['submit'])) {
            $errors = array();

            /* check name */
            $f_name = $_POST['name'];
            if (empty($f_name)) {
                $errors[] = str_replace('{field}', "<b>" . $lang['name'] . "</b>", $lang['notice_field_empty']);
                $error_fields[] = "name[{$lval['Code']}]";
            }

            /* check Side */
            $f_side = $_POST['side'];
            if (empty($f_side)) {
                $errors[] = str_replace('{field}', "<b>\"" . $lang['position'] . "\"</b>", $lang['notice_select_empty']);
                $error_fields[] = 'position';
            }

            /* check code */
            $f_code = $_POST['code'];
            if (empty($f_code)) {
                $errors[] = str_replace('{field}', "<b>" . $lang['adsense_code'] . "</b>", $lang['notice_field_empty']);
                $error_fields[] = 'code';
            }

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                /* add/edit action */
                if ($_GET['action'] == 'add') {
                    // write main, block information
                    $data = array(
                        'Name' => $f_name,
                        'Code' => $f_code,
                        'Status' => $_POST['status'],
                        'Side' => $f_side,
                        'Pages' => implode(',', $_POST['pages']),
                    );
                    if ($action = $rlDb->insertOne($data, 'android_adsense')) {
                        $message = $lang['block_added'];
                        $aUrl = array("controller" => $controller);
                    } else {
                        trigger_error("Can't add new block (MYSQL problems)", E_WARNING);
                        $rlDebug->logger("Can't add new block (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    $data_update = array(
                        'fields' => array(
                            'Name' => $f_name,
                            'Code' => $f_code,
                            'Status' => $_POST['status'],
                            'Side' => $f_side,
                            'Pages' => implode(',', $_POST['pages']),
                        ),
                        'where' => array('ID' => $id),
                    );
                    $action = $rlDb->updateOne($data_update, 'android_adsense');

                    $message = $lang['block_edited'];
                    $aUrl = array("controller" => $controller);
                }

                if ($action) {
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                }
            }
        }
    }
    $rlXajax->registerFunction(array('deleteAdsenseBox', $rlAndroidConnect, 'ajaxDeleteAdsenseBox'));

    $android_pages = array(
        'home' => 'android_title_activity_home',
        'recently_added' => 'android_title_activity_recently_added',
        'category' => 'listing_category',
        'favorites' => 'android_title_activity_favorites',
        'search' => 'android_title_activity_search',
        'listing_details' => 'android_title_activity_listing_details',
        'account_details' => 'android_title_activity_account_details',
        'account_type' => 'account_type',
        'search_results' => 'android_title_activity_search_results',
        'search_accounts' => 'account_search_results',
        'comments' => 'android_title_activity_comments',
    );

    $rlSmarty->assign_by_ref('android_pages', $android_pages);
}
