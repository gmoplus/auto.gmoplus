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

/* ext js action */
if ($_GET['q'] == 'ext') {
    /* system config */
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    /* date update */
    if ($_GET['action'] == 'update') {
        $type = $rlValid->xSql($_GET['type']);
        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id = $rlValid->xSql($_GET['id']);
        $key = $rlValid->xSql($_GET['key']);

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where' => array(
                'ID' => $id,
            ),
        );

        $rlDb->updateOne($updateData, 'faqs');
        exit;
    }

    /* data read */
    $limit = $rlValid->xSql($_GET['limit']);
    $start = $rlValid->xSql($_GET['start']);
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);

    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, `T1`.`ID` AS `Key`, `T2`.`Value` AS `title` ";
    $sql .= "FROM `{db_prefix}faqs` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('faqs+title+',`T1`.`ID`) = `T2`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' ";
    if ($sort) {
        $sortField = $sort == 'title' ? "`T2`.`Value`" : "`T1`.`{$sort}`";
        $sql .= "ORDER BY {$sortField} {$sortDir} ";
    }
    $sql .= "LIMIT {$start}, {$limit}";

    $data = $rlDb->getAll($sql);

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $GLOBALS['lang'][$data[$key]['Status']];
    }

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
    exit;
}
/* ext js action end */

else {
    $reefless->loadClass('FAQs', null, 'FAQs');

    /* additional bread crumb step */
    if ($_GET['action']) {
        $bcAStep = $_GET['action'] == 'add' ? $lang['faq_add_faqs'] : $lang['faq_edit_faqs'];
    }

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        /* get all languages */
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        if ($_GET['action'] == 'edit') {
            $id = (int) $_GET['faqs'];
        }

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            // get faqs info
            $faqs_info = $rlDb->fetch('*', array('ID' => $id), "AND `Status` <> 'trash'", 1, 'faqs', 'row');

            $_POST['status'] = $faqs_info['Status'];
            $_POST['path'] = $faqs_info['Path'];
            $_POST['date'] = $faqs_info['Date'];

            // get titles
            $e_titles = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'faqs+title+' . $id), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($e_titles as $nKey => $nVal) {
                $_POST['name'][$e_titles[$nKey]['Code']] = $e_titles[$nKey]['Value'];
            }

            // get content
            $e_content = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'faqs+content+' . $id), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($e_content as $nKey => $nVal) {
                $_POST['content_' . $e_content[$nKey]['Code']] = $e_content[$nKey]['Value'];
            }

            // get h1
            $e_h1 = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'faqs+h1+' . $id), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($e_content as $nKey => $nVal) {
                $_POST['h1_heading'][$e_h1[$nKey]['Code']] = $e_h1[$nKey]['Value'];
            }

            // get h1
            $e_meta = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'faqs+meta_description+' . $id), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($e_content as $nKey => $nVal) {
                $_POST['meta_description'][$e_meta[$nKey]['Code']] = $e_meta[$nKey]['Value'];
            }
        }

        if (isset($_POST['submit'])) {
            $errors = array();

            /* load the utf8 lib */
            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

            /* check titles */
            $f_title = $_POST['name'];

            if (empty($f_title[$config['lang']])) {
                $errors[] = str_replace('{field}', "<b>" . $lang['title'] . "</b>", $lang['notice_field_empty']);
                $error_fields[] = "name[{$config['lang']}]";
            }

            /* check content */
            foreach ($allLangs as $lkey => $lval) {
                $f_content[$allLangs[$lkey]['Code']] = $_POST['content_' . $allLangs[$lkey]['Code']];
            }
            if (empty($_POST['content_' . $config['lang']])) {
                $errors[] = str_replace('{field}', "<b>" . $lang['content'] . "</b>", $lang['notice_field_empty']);
            }

            /* check path */
            $f_path = $_POST['path'];

            if (!utf8_is_ascii($f_path)) {
                $f_path = utf8_to_ascii($f_path);
            }

            $f_path = $rlValid->str2path($f_path);

            if (strlen($f_path) < 3) {
                $errors[] = $lang['incorrect_page_address'];
                $error_fields[] = 'path';
            }

            $exist_path = $rlDb->getOne('ID', "`Path` = '{$f_path}' AND `ID` <> '{$id}'", 'faqs');

            if ($exist_path) {
                $errors[] = str_replace('{path}', "<b>{$f_path}</b>", $lang['notice_page_path_exist']);
            }

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                /* add/edit action */
                if ($_GET['action'] == 'add') {
                    $position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}faqs`");

                    // write main section information
                    $data = array(
                        'Status' => $_POST['status'],
                        'Path' => $f_path,
                        'Date' => 'NOW()',
                        'Position' => $position['max'] + 1,
                    );

                    if ($action = $rlDb->insertOne($data, 'faqs')) {
                        $faqs_id = $rlDb->insertID();

                        // save faqs content
                        $createPhrases = [];
                        foreach ($allLangs as $key => $value) {
                            $createPhrases[] = array(
                                'Code' => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Plugin' => 'FAQs',
                                'Key' => 'faqs+title+' . $faqs_id,
                                'Value' => !empty($f_title[$allLangs[$key]['Code']]) ? $f_title[$allLangs[$key]['Code']] : $f_title[$config['lang']],
                            );

                            $createPhrases[] = array(
                                'Code' => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Plugin' => 'FAQs',
                                'Key' => 'faqs+content+' . $faqs_id,
                                'Value' => !empty($f_content[$allLangs[$key]['Code']]) ? $f_content[$allLangs[$key]['Code']] : $f_content[$config['lang']],
                            );

                            // insert h1
                            $h1_heading = $_POST['h1_heading'];
                            if (!empty($h1_heading[$allLangs[$key]['Code']])) {
                                $createPhrases[] = array(
                                    'Code' => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Plugin' => 'FAQs',
                                    'Key' => 'faqs+h1+' . $faqs_id,
                                    'Value' => $h1_heading[$allLangs[$key]['Code']],
                                );
                            }

                            // insert meta description
                            $metaDesc = $_POST['meta_description'];
                            if (!empty($metaDesc[$allLangs[$key]['Code']])) {
                                $createPhrases[] = array(
                                    'Code' => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Plugin' => 'FAQs',
                                    'Key' => 'faqs+meta_description+' . $faqs_id,
                                    'Value' => $metaDesc[$allLangs[$key]['Code']],
                                );
                            }
                        }

                        if (method_exists($rlLang, 'createPhrases')) {
                            $rlLang->createPhrases($createPhrases);
                        } else {
                            $rlDb->insert($createPhrases, 'lang_keys');
                        }

                        $message = $lang['faq_faqs_added'];
                        $aUrl = array("controller" => $controller);
                    } else {
                        trigger_error("Can't add faq article (MYSQL problems)", E_WARNING);
                        $rlDebug->logger("Can't add faq article (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    $update_date = array(
                        'fields' => array(
                            'Status' => $_POST['status'],
                            'Date' => $_POST['date'],
                            'Path' => $f_path,
                        ),
                        'where' => array('ID' => $id),
                    );
                    $action = $rlDb->updateOne($update_date, 'faqs');

                    $createPhrases = [];
                    $updatePhrases = [];
                    foreach ($allLangs as $key => $value) {
                        // edit titles
                        if ($rlDb->getOne('ID', "`Key` = 'faqs+title+{$id}' AND `Code` = '{$allLangs[$key]['Code']}'", 'lang_keys')) {
                            $updatePhrases[] = array(
                                'where' => array(
                                    'Code' => $allLangs[$key]['Code'],
                                    'Key' => 'faqs+title+' . $id,
                                ),
                                'fields' => array(
                                    'Value' => !empty($f_title[$allLangs[$key]['Code']]) ? $f_title[$allLangs[$key]['Code']] : $f_title[$config['lang']],
                                ),
                            );
                        } else {
                            // insert titles
                            $createPhrases[] = array(
                                'Code' => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Plugin' => 'FAQs',
                                'Key' => 'faqs+title+' . $id,
                                'Value' => !empty($f_title[$allLangs[$key]['Code']]) ? $f_title[$allLangs[$key]['Code']] : $f_title[$config['lang']],
                            );
                        }

                        if ($rlDb->getOne('ID', "`Key` = 'faqs+content+{$id}' AND `Code` = '{$allLangs[$key]['Code']}'", 'lang_keys')) {
                            // edit content
                            $updatePhrases[] = array(
                                'where' => array(
                                    'Code' => $allLangs[$key]['Code'],
                                    'Key' => 'faqs+content+' . $id,
                                ),
                                'fields' => array(
                                    'Value' => !empty($f_content[$allLangs[$key]['Code']]) ? $f_content[$allLangs[$key]['Code']] : $f_content[$config['lang']],
                                ),
                            );
                        } else {
                            // insert contents
                            $createPhrases[] = array(
                                'Code' => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Plugin' => 'FAQs',
                                'Key' => 'faqs+content+' . $id,
                                'Value' => !empty($f_content[$allLangs[$key]['Code']]) ? $f_content[$allLangs[$key]['Code']] : $f_content[$config['lang']],
                            );
                        }

                        // h1
                        $h1_heading = $_POST['h1_heading'][$allLangs[$key]['Code']];
                        if ($h1_heading && $rlDb->getOne('ID', "`Key` = 'faqs+h1+{$id}' AND `Code` = '{$allLangs[$key]['Code']}'", 'lang_keys')) {
                            // edit h1
                            $updatePhrases[] = array(
                                'where' => array(
                                    'Code' => $allLangs[$key]['Code'],
                                    'Key' => 'faqs+h1+' . $id,
                                ),
                                'fields' => array(
                                    'Value' => $h1_heading,
                                ),
                            );
                        } 
                        else if (!empty($h1_heading)) {
                            // insert h1
                            $createPhrases[] = array(
                                'Code' => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Plugin' => 'FAQs',
                                'Key' => 'faqs+h1+' . $id,
                                'Value' => $h1_heading,
                            );
                        }
                        else {
                            if (method_exists($rlLang, 'deletePhrase')) {
                                $rlLang->deletePhrase(
                                    [
                                        'Key' => "faqs+h1+{$id}",
                                        'Code' => $allLangs[$key]['Code'],
                                        'Plugin' => "FAQs",
                                    ]
                                );
                            }
                            else {
                                $rlDb->query(
                                    "DELETE FROM `{db_prefix}lang_keys`
                                    WHERE `Plugin` = 'FAQs' AND `Code` = '{$allLangs[$key]['Code']}' AND `Key` = 'faqs+h1+{$id}'"
                                );
                            }
                        }

                        // meta description
                        $metaDesc = $_POST['meta_description'][$allLangs[$key]['Code']];
                        if ($metaDesc && $rlDb->getOne('ID', "`Key` = 'faqs+meta_description+{$id}' AND `Code` = '{$allLangs[$key]['Code']}'", 'lang_keys')) {
                            // edit meta_description
                            $updatePhrases[] = array(
                                'where' => array(
                                    'Code' => $allLangs[$key]['Code'],
                                    'Key' => 'faqs+meta_description+' . $id,
                                ),
                                'fields' => array(
                                    'Value' => $metaDesc,
                                ),
                            );
                        } else if (!empty($metaDesc)) {
                            // insert meta description
                            $createPhrases[] = array(
                                'Code' => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Plugin' => 'FAQs',
                                'Key' => 'faqs+meta_description+' . $id,
                                'Value' => $metaDesc,
                            );
                        }
                        else {
                            if (method_exists($rlLang, 'deletePhrase')) {
                                $rlLang->deletePhrase(
                                    [
                                        'Key' => "faqs+meta_description+{$id}",
                                        'Code' => $allLangs[$key]['Code'],
                                        'Plugin' => "FAQs",
                                    ]
                                );
                            }
                            else {
                                $rlDb->query(
                                    "DELETE FROM `{db_prefix}lang_keys`
                                    WHERE `Plugin` = 'FAQs' AND `Code` = '{$allLangs[$key]['Code']}' AND `Key` = 'faqs+meta_description+{$id}'"
                                );
                            }
                        }
                    }

                    if ($createPhrases) {
                        if (method_exists($rlLang, 'createPhrases')) {
                            $rlLang->createPhrases($createPhrases);
                        } else {
                            $rlDb->insert($createPhrases, 'lang_keys');
                        }
                    }

                    if ($updatePhrases) {
                        if (method_exists($rlLang, 'updatePhrases')) {
                            $rlLang->updatePhrases($updatePhrases);
                        } else {
                            $rlDb->update($updatePhrases, 'lang_keys');
                        }
                    }

                    $message = $lang['faq_faqs_edited'];
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

    $reefless->loadClass('Categories');
}
