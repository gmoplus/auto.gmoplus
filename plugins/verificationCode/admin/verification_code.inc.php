<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : VERIFICATION_CODE.INC.PHP
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

/* ext js action */
if ($_GET['q'] == 'ext') {
    // system config
    require_once('../../../includes/config.inc.php');
    require_once(RL_ADMIN_CONTROL . 'ext_header.inc.php');
    require_once(RL_LIBS . 'system.lib.php');

    $reefless->loadClass('VerificationCode', null, 'verificationCode');

    /* date update */
    if ($_GET['action'] == 'update') {
        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id = $rlValid->xSql($_GET['id']);

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where' => array(
                'ID' => $id,
            ),
        );

        if ($rlDb->updateOne($updateData, $rlVerificationCode::TABLE)) {
            $rlVerificationCode->updateCodesHook();
        }
        exit;
    }

    /* data read */
    $limit = $rlValid->xSql($_GET['limit']);
    $start = $rlValid->xSql($_GET['start']);

    $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.* ";
    $sql .= "FROM `" . $rlVerificationCode::TABLE_PRX . "` AS `T1` ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' ";
    $sql .= "ORDER BY `T1`.`Date` DESC LIMIT {$start}, {$limit}";

    $data = $rlDb->getAll($sql);
    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    foreach ($data as $key => $val) {
        $data[$key]['Status'] = $GLOBALS['lang'][$data[$key]['Status']];
        $data[$key]['Position'] = $GLOBALS['lang']['vc_position_' . $data[$key]['Position']];
    }

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
    exit;
}

$reefless->loadClass('VerificationCode', null, 'verificationCode');

if (isset($_GET['action'])) {
    $reefless->loadClass('Valid');

    // get all languages
    $allLangs = $GLOBALS['languages'];
    $rlSmarty->assign_by_ref('allLangs', $allLangs);

    $bcAStep[] = array('name' => $_GET['action'] == 'add' ? $lang['vc_add_item'] : $lang['vc_edit_item']);

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        // get account types
        $reefless->loadClass('Account');

        /* get pages list */
        $where = "AND `Status` = 'active' ORDER BY `Key`";
        $pages = $rlDb->fetch(array('ID', 'Key'), array('Tpl' => 1), $where, null, 'pages');
        $pages = $rlLang->replaceLangKeys($pages, 'pages', array('name'), RL_LANG_CODE, 'admin');
        $rlSmarty->assign_by_ref('pages', $pages);

        $id = (int) $_GET['item'];

        // get current plan info
        if (isset($_GET['item']) && !$_POST['fromPost']) {
            $verification_code = $rlDb->fetch('*', array('ID' => $id), null, null, $rlVerificationCode::TABLE, 'row');

            foreach ($verification_code as $key => $val) {
                if ($key == 'Pages') {
                    $_POST[strtolower($key)] = explode(",", $val);
                } elseif ($key == 'Pages_sticky') {
                    $_POST['show_on_all'] = $val;
                } else {
                    $_POST[strtolower($key)] = $val;
                }
            }
        }

        if (isset($_POST['submit'])) {
            $errors = $error_fields = array();

            if (empty($_POST['name'])) {
                array_push($errors, str_replace('{field}', "<b>{$lang['vc_name']}</b>", $lang['notice_field_empty']));
                array_push($error_fields, "name");
            }

            if (empty($_POST['content'])) {
                array_push(
                    $errors,
                    str_replace('{field}', "<b>{$lang['vc_content']}</b>", $lang['notice_field_empty'])
                );
                array_push($error_fields, "content");
            }

            if (empty($_POST['position'])) {
                array_push(
                    $errors,
                    str_replace('{field}', "<b>{$lang['vc_position']}</b>", $lang['notice_field_empty'])
                );
                array_push($error_fields, "position");
            }

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                if ($_GET['action'] == 'add') {
                    // write main plan information
                    $data = array(
                        'Name' => $_POST['name'],
                        'Pages' => !empty($_POST['pages']) ? implode(",", $_POST['pages']) : '',
                        'Pages_sticky' => !empty($_POST['show_on_all']) ? 1 : 0,
                        'Date' => 'NOW()',
                        'Position' => $_POST['position'],
                    );

                    if ($action = $rlDb->insertOne($data, $rlVerificationCode::TABLE, array('Content'))) {
                        $verification_code_id = $rlDb->insertID();

                        $update = array(
                            'fields' => array(
                                'Content' => $_POST['content'],
                            ),
                            'where' => array(
                                'ID' => $verification_code_id,
                            ),
                        );

                        if ($rlDb->updateOne($update, $rlVerificationCode::TABLE, array('content'))) {
                            $rlVerificationCode->updateCodesHook();
                        }

                        $message = $lang['vc_item_added'];
                        $aUrl = array("controller" => $controller);
                    } else {
                        trigger_error("Can't add new banner plan (MYSQL problems)", E_WARNING);
                        $rlDebug->logger("Can't add new banner plan (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    $update = array(
                        'fields' => array(
                            'Name' => $_POST['name'],
                            'Content' => $_POST['content'],
                            'Pages' => (!empty($_POST['pages']) ? implode(",", $_POST['pages']) : ''),
                            'Position' => $_POST['position'],
                            'Pages_sticky' => (!empty($_POST['show_on_all']) ? 1 : 0),
                        ),
                        'where' => array(
                            'ID' => $id,
                        ),
                    );

                    $action = $rlDb->updateOne($update, $rlVerificationCode::TABLE, array('content'));
                    if ($action) {
                        $rlVerificationCode->updateCodesHook();
                    }

                    $message = $lang['vc_item_edited'];
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
}
