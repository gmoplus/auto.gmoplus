<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : ANDROID_LANGUAGES.INC.PHP
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

/* ext js action */
if ($_GET['q'] == 'ext_list') {
    /* system config */
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    /* date update */
    if ($_POST['action'] == 'update') {
        $type = $rlValid->xSql($_POST['type']);
        $field = $rlValid->xSql($_POST['field']);
        $value = $rlValid->xSql(nl2br($_POST['value']));
        $id = $rlValid->xSql($_POST['id']);
        $key = $rlValid->xSql($_POST['key']);

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where' => array(
                'ID' => $id,
            ),
        );

        $rlDb->updateOne($updateData, 'android_languages');
        exit;
    }

    /* data read */
    $limit = $rlValid->xSql($_GET['limit']);
    $start = $rlValid->xSql($_GET['start']);

    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT COUNT(`T2`.`ID`) AS `Number`, `T1`.* FROM `" . RL_DBPREFIX . "android_languages` AS `T1` ";
    $sql .= "LEFT JOIN `" . RL_DBPREFIX . "android_phrases` AS `T2` ON `T1`.`Code` = `T2`.`Code` ";
    $sql .= "GROUP BY `T2`.`Code` ORDER BY `ID` ";
    $sql .= "LIMIT {$start}, {$limit}";

    $data = $rlDb->getAll($sql);

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $GLOBALS['lang'][$value['Status']];
        $is_current = $config['android_lang'] == $value['Code'] ? 'true' : 'false';
        $data[$key]['Data'] = $value['ID'] . '|' . $is_current;
        $data[$key]['Direction'] = $GLOBALS['lang'][$value['Direction'] . '_direction_title'];
        $data[$key]['name'] = $rlDb->getOne('Value', "`Key` = 'android_{$value['Key']}' AND `Code` = '{$value['Code']}'", 'android_phrases');
        if ($value['Code'] == $config['android_lang']) {
            $data[$key]['name'] .= ' <b>(' . $lang['default'] . ')</b>';
        } else {
            $data[$key]['name'] .= ' | <a class="green_11_bg" href="javascript:void(0)" onclick="xajax_setDefault( \'langs_container\', \'' . $value['Code'] . '\' );"><b>' . $lang['set_default'] . '</b></a>';
        }
    }

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
} elseif ($_GET['q'] == 'ext') {
    /* system config */
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';

    /* date update */
    if ($_POST['action'] == 'update') {
        $type = $rlValid->xSql($_POST['type']);
        $field = $rlValid->xSql($_POST['field']);

        /* trim NL */
        $value = $_POST['value'];
        $value = trim($value, PHP_EOL);

        $id = (int) $_POST['id'];
        $lang_code = $rlValid->xSql($_POST['lang_code']);

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where' => array(
                'ID' => $id,
            ),
        );

        $rlDb->updateOne($updateData, 'android_phrases');
        exit;
    }

    /* data read */
    $limit = $rlValid->xSql($_GET['limit']);
    $start = $rlValid->xSql($_GET['start']);
    $sort = $rlValid->xSql($_GET['sort']);
    $sort = $sort ? $sort : 'Value';
    $sortDir = $rlValid->xSql($_GET['dir']);
    $sortDir = $sortDir ? $sortDir : 'ASC';

    $langCode = $_GET['lang_id'] ? $rlDb->getOne('Code', "`ID` = '{$_GET['lang_id']}'", 'android_languages') : $rlValid->xSql($_GET['lang_code']);
    $phrase = str_replace(' ', '%', $rlValid->xSql($_GET['phrase']));

    if (isset($_GET['action']) && $_GET['action'] == 'search') {
        $criteria = $_GET['criteria'];

        $where = '1';

        if ($langCode != 'all') {
            $where = "`Code` = '{$langCode}'";
        }

        $search_by = $criteria == 'in_value' ? 'Value' : 'Key';
        $sql = "SELECT SQL_CALC_FOUND_ROWS `ID`, `Code`, CONCAT('<span style=\"color: #596C27;\"><b>',`Code`,'</b></span> | ', `Key`) AS `Key`, `Value` ";
        $sql .= "FROM `" . RL_DBPREFIX . "android_phrases` ";
        $sql .= "WHERE {$where} AND `{$search_by}` LIKE '%{$phrase}%' ORDER BY `{$sort}` {$sortDir} LIMIT {$start}, {$limit}";

        $lang_data = $rlDb->getAll($sql);
        $count_rows = $rlDb->getRow("SELECT FOUND_ROWS() AS `calc`");
        $lang_count['count'] = $count_rows['calc'];
    } else {
        $rlDb->setTable('android_phrases');
        $lang_data = $rlDb->fetch(array('ID', 'Key', 'Value'), array('Code' => $langCode), "ORDER BY `{$sort}` {$sortDir}", array($start, $limit));
        $rlDb->resetTable();

        $lang_count = $rlDb->getRow("SELECT COUNT(`ID`) AS `count` FROM `" . RL_DBPREFIX . "android_phrases` WHERE `Code` = '{$langCode}'");
    }

    $output['total'] = $lang_count['count'];
    $output['data'] = $lang_data;

    echo json_encode($output);
}
/* ext js action end */
elseif ($_GET['q'] == 'compare') {
    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];

    /* system config */
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';

    $lang_1 = $_SESSION['lang_1'];
    $lang_2 = $_SESSION['lang_2'];

    if ($_GET['action'] == 'update') {

        $type = $rlValid->xSql($_GET['type']);
        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id = $rlValid->xSql($_GET['id']);
        $lang_code = $rlValid->xSql($_GET['lang_code']);

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where' => array(
                'ID' => $id,
            ),
        );

        $rlDb->updateOne($updateData, 'android_phrases');

        if ($_GET['compare_mode'] == "phrases") {
            set_time_limit(0);

            $rlDb->setTable('android_phrases');
            $phrases_1_tmp = $rlDb->fetch('*', array('Code' => $lang_1), "ORDER BY `Key`");
            foreach ($phrases_1_tmp as $pK => $pV) {
                $phrases_1[$phrases_1_tmp[$pK]['Key']] = $phrases_1_tmp[$pK];
            }
            unset($phrases_1_tmp);

            $phrases_2_tmp = $rlDb->fetch('*', array('Code' => $lang_2), "ORDER BY `Key`");
            foreach ($phrases_2_tmp as $pK => $pV) {
                $phrases_2[$phrases_2_tmp[$pK]['Key']] = $phrases_2_tmp[$pK];
            }
            unset($phrases_2_tmp);

            $compare_1 = array_diff_key($phrases_1, $phrases_2);
            foreach ($compare_1 as $cK => $cV) {
                $adapted_compare_1[] = $compare_1[$cK];
            }
            unset($compare_1);

            $_SESSION['compare_1'] = $_SESSION['source_1'] = $adapted_compare_1;

            $compare_2 = array_diff_key($phrases_2, $phrases_1);
            foreach ($compare_2 as $cK => $cV) {
                $adapted_compare_2[] = $compare_2[$cK];
            }
            unset($compare_2);

            $_SESSION['compare_2'] = $_SESSION['source_2'] = $adapted_compare_2;
        } else {
            $phrases_1_tmp = $rlDb->fetch('*', array('Code' => $lang_1), "ORDER BY `Key`");
            foreach ($phrases_1_tmp as $pK => $pV) {
                $phrases_1[$phrases_1_tmp[$pK]['Key']] = $phrases_1_tmp[$pK]['Value'];
                $phrases_1_orig[$phrases_1_tmp[$pK]['Key']] = $phrases_1_tmp[$pK];
            }
            unset($phrases_1_tmp);

            $phrases_2_tmp = $rlDb->fetch('*', array('Code' => $lang_2), "ORDER BY `Key`");

            foreach ($phrases_2_tmp as $pK => $pV) {
                $phrases_2[$phrases_2_tmp[$pK]['Key']] = $phrases_2_tmp[$pK]['Value'];
                $phrases_2_orig[$phrases_2_tmp[$pK]['Key']] = $phrases_2_tmp[$pK];
            }
            unset($phrases_2_tmp);

            $compare_1 = array_intersect_assoc($phrases_1, $phrases_2);
            foreach ($compare_1 as $cK => $cV) {
                $adapted_compare_1[] = $phrases_1_orig[$cK];
            }
            unset($compare_1);

            $_SESSION['compare_1'] = $adapted_compare_1;

            $compare_2 = array_intersect_assoc($phrases_2, $phrases_1);
            foreach ($compare_2 as $cK => $cV) {
                $adapted_compare_2[] = $phrases_2_orig[$cK];
            }
            unset($compare_2);

            $_SESSION['compare_2'] = $adapted_compare_2;
        }
    }

    $grid = (int) $_GET['grid'];
    $data = $_SESSION['compare_' . $grid];

    $output['total'] = (string) count($data);
    $output['data'] = array_slice($data, $start, $limit);

    echo json_encode($output);
} elseif ($_GET['action'] == 'export') {
    $reefless->loadClass('AndroidLang', null, 'androidConnect');
    $rlAndroidLang->exportLanguage((int) $_GET['lang']);
} else {
    /* clear cache */
    if (!$_REQUEST['compare'] && !$_POST['xjxfun']) {
        unset($_SESSION['compare_mode']);

        unset($_SESSION['compare_1']);
        unset($_SESSION['compare_2']);

        unset($_SESSION['source_1']);
        unset($_SESSION['source_2']);

        unset($_SESSION['lang_1']);
        unset($_SESSION['lang_2']);
    }

    /* get all system languages */
    $rlDb->setTable('android_languages');
    $allLangs = $rlDb->fetch(array('ID', 'Key', 'Code'));
    foreach ($allLangs as &$lang_item) {
        $lang_item['name'] = $rlDb->getOne('Value', "`Key` = 'android_{$lang_item['Key']}'", 'android_phrases');
    }
    $rlSmarty->assign_by_ref('allLangs', $allLangs);
    $rlSmarty->assign('langCount', count($allLangs));

    /* get lang for edit */
    if ($_GET['action'] == 'edit') {
        $bcAStep[] = array(
            'name' => $lang['edit'],
        );

        $edit_id = (int) $_GET['lang'];

        // get current language info
        $language = $rlDb->fetch('*', array('ID' => $edit_id), null, 1, 'android_languages', 'row');

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $_POST['code'] = $language['Code'];
            $_POST['direction'] = $language['Direction'];
            $_POST['date_format'] = $language['Date_format'];
            $_POST['status'] = $language['Status'];

            // get names
            $l_name = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'android_' . $language['Key']), null, 1, 'android_phrases', 'row');
            $_POST['name'] = $l_name['Value'];
        }
    }

    if ($_POST['submit']) {
        /* check data */
        if (empty($_POST['name'])) {
            $errors[] = str_replace('{field}', "<b>\"{$lang['name']}\"</b>", $lang['notice_field_empty']);
        }

        if (empty($_POST['date_format'])) {
            $errors[] = str_replace('{field}', "<b>\"{$lang['date_format']}\"</b>", $lang['notice_field_empty']);
        }

        if (!empty($errors)) {
            $rlSmarty->assign_by_ref('errors', $errors);
        } else {
            $result = false;

            /* update general information */
            $updateLang = array(
                'fields' => array(
                    'Date_format' => $_POST['date_format'],
                    'Status' => $_POST['status'],
                    'Direction' => $_POST['direction'],
                ),
                'where' => array(
                    'Code' => $_POST['code'],
                ),
            );

            $result = $rlDb->updateOne($updateLang, 'android_languages');

            if ($rlDb->getOne('ID', "`Key` = 'android_{$language['Key']}'", 'android_phrases')) {
                /* update phrase */
                $updatePhrase = array(
                    'fields' => array(
                        'Value' => $_POST['name'],
                    ),
                    'where' => array(
                        'Key' => 'android_' . $language['Key'],
                    ),
                );

                $result = $rlDb->updateOne($updatePhrase, 'android_phrases');
            } else {
                /* insert phrase */
                $insertPhrase = array(
                    'Key' => 'android_' . $language['Key'],
                    'Value' => $_POST['name'],
                    'Code' => $language['Code'],
                );

                $result = $rlDb->insertOne($insertPhrase, 'android_phrases');
            }

            if ($result) {
                $message = $lang['language_edited'];
                $aUrl = array("controller" => $controller);

                $reefless->loadClass('Notice');
                $rlNotice->saveNotice($message);
                $reefless->redirect($aUrl);
            } else {
                trigger_error("Android: Can't edit language (MYSQL problems)", E_WARNING);
                $rlDebug->logger("Android: Can't edit language (MYSQL problems)");
            }
        }
    }

    if ($_POST['import']) {
        $dump_sours = $_FILES['dump']['tmp_name'];
        $dump_file = $_FILES['dump']['name'];

        preg_match("/([\w]+)\(([\w]{2})\)(\.xml)$/", $dump_file, $matches);
        $new_lang_code = strtolower($matches[2]);

        if (!empty($new_lang_code) && strtolower($matches[3]) == '.xml') {
            if (is_readable($dump_sours)) {
                $rlDb->query("SET NAMES `utf8`");

                /* check exist language */
                $exist_lang_key = $rlDb->getOne('Key', "LOWER(`Code`) = '{$new_lang_code}'", 'android_languages');

                /* read language file */
                $doc = new DOMDocument();
                $doc->load($dump_sours);
                $phrases = $doc->getElementsByTagName('phrase');

                if ($phrases) {
                    /* create new language entry if we haven't it */
                    if (!$exist_lang_key) {
                        loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');
                        $lang_info = $doc->getElementsByTagName('phrases')->item(0);

                        $new_lang_key = $matches[1];
                        if (!utf8_is_ascii($new_lang_key)) {
                            $new_lang_key = utf8_to_ascii($new_lang_key);
                        }
                        $new_lang_key = $rlValid->str2key($new_lang_key);

                        $insert_lang = array(
                            'Code' => $new_lang_code,
                            'Key' => $new_lang_key,
                            'Direction' => $lang_info->getAttribute('direction'),
                            'Date_format' => $lang_info->getAttribute('date_format'),
                        );
                        $rlDb->insertOne($insert_lang, 'android_languages');
                    }

                    /* add missing phrases */
                    foreach ($phrases as $phrase) {
                        if (!$rlDb->getOne('ID', "`Code` = '{$new_lang_code}' AND `Key` = '{$phrase->getAttribute("key")}'", 'android_phrases')) {
                            $insert[] = array(
                                'Code' => $new_lang_code,
                                'Key' => $phrase->getAttribute("key"),
                                'Value' => $phrase->textContent,
                            );
                        }
                    }
                    $rlDb->insert($insert, 'android_phrases');

                    $rlNotice->saveNotice($lang['new_language_imported']);
                    $aUrl = array("controller" => $controller);

                    $reefless->redirect($aUrl);
                } else {
                    $errors[] = $lang['android_import_lang_file_empty'];
                }
            } else {
                $errors[] = $lang['can_not_read_file'];
                trigger_error("Android: Can not to read uploaded file | Language Import", E_WARNING);
                $rlDebug->logger("Android: Can not to read uploaded file | Language Import");
            }
        } else {
            $errors[] = $lang['android_import_lang_file_name_error'];
        }

        if (!empty($errors)) {
            $rlSmarty->assign_by_ref('errors', $errors);
        }
    } elseif (isset($_POST['compare'])) {
        /* additional bread crumb step */
        $bcAStep = $lang['languages_compare'];

        $lang_1 = $_POST['lang_1'];
        $lang_2 = $_POST['lang_2'];

        foreach ($allLangs as $lK => $lV) {
            $langs_info[$allLangs[$lK]['Code']] = $allLangs[$lK];
        }

        /* checking errors */
        if (empty($lang_1) || empty($lang_2)) {
            $errors[] = $lang['compare_empty_langs'];
        }

        if ($lang_1 == $lang_2 && !$errors) {
            $errors[] = $lang['compare_languages_same'];
        }

        if ((!array_key_exists($lang_1, $langs_info) || !array_key_exists($lang_2, $langs_info)) && !$errors) {
            $errors[] = $lang['system_error'];
            //trigger_error("Can not compare the languages, gets undefine language code", E_USER_NOTICE);
            $rlDebug->logger("Android: Can not compare the languages, gets undefine language code");
        }

        if (!empty($errors)) {
            $rlSmarty->assign_by_ref('errors', $errors);
        } else {
            set_time_limit(0);

            $rlDb->setTable('android_phrases');

            $_SESSION['compare_mode'] = $_POST['compare_mode'];
            if ($_POST['compare_mode'] == 'phrases') {
                $phrases_1_tmp = $rlDb->fetch('*', array('Code' => $lang_1), "ORDER BY `Key`");
                foreach ($phrases_1_tmp as $pK => $pV) {
                    $phrases_1[$phrases_1_tmp[$pK]['Key']] = $phrases_1_tmp[$pK];
                }
                unset($phrases_1_tmp);

                $phrases_2_tmp = $rlDb->fetch('*', array('Code' => $lang_2), "ORDER BY `Key`");
                foreach ($phrases_2_tmp as $pK => $pV) {
                    $phrases_2[$phrases_2_tmp[$pK]['Key']] = $phrases_2_tmp[$pK];
                }
                unset($phrases_2_tmp);

                $compare_1 = array_diff_key($phrases_1, $phrases_2);
                foreach ($compare_1 as $cK => $cV) {
                    $adapted_compare_1[] = $compare_1[$cK];
                }
                unset($compare_1);

                $compare_2 = array_diff_key($phrases_2, $phrases_1);
                foreach ($compare_2 as $cK => $cV) {
                    $adapted_compare_2[] = $compare_2[$cK];
                }
                unset($compare_2);

                if (empty($adapted_compare_1) && empty($adapted_compare_2)) {
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($lang['compare_no_diff_found']);

                    $aUrl = array("controller" => $controller);
                    $reefless->redirect($aUrl);
                } else {
                    $_SESSION['compare_1'] = $_SESSION['source_1'] = $adapted_compare_1;
                    $_SESSION['lang_1'] = $lang_1;

                    $_SESSION['compare_2'] = $_SESSION['source_2'] = $adapted_compare_2;
                    $_SESSION['lang_2'] = $lang_2;

                    $compare_lang1 = array('diff' => count($adapted_compare_1), 'Code' => $lang_1);
                    $compare_lang2 = array('diff' => count($adapted_compare_2), 'Code' => $lang_2);

                    $rlSmarty->assign_by_ref('compare_lang1', $compare_lang1);
                    $rlSmarty->assign_by_ref('compare_lang2', $compare_lang2);
                    $rlSmarty->assign_by_ref('langs_info', $langs_info);
                }
            } else {
                $phrases_1_tmp = $rlDb->fetch('*', array('Code' => $lang_1), "ORDER BY `Key`");
                foreach ($phrases_1_tmp as $pK => $pV) {
                    $phrases_1[$phrases_1_tmp[$pK]['Key']] = $phrases_1_tmp[$pK]['Value'];
                    $phrases_1_orig[$phrases_1_tmp[$pK]['Key']] = $phrases_1_tmp[$pK];
                }
                unset($phrases_1_tmp);

                $phrases_2_tmp = $rlDb->fetch('*', array('Code' => $lang_2), "ORDER BY `Key`");

                foreach ($phrases_2_tmp as $pK => $pV) {
                    $phrases_2[$phrases_2_tmp[$pK]['Key']] = $phrases_2_tmp[$pK]['Value'];
                    $phrases_2_orig[$phrases_2_tmp[$pK]['Key']] = $phrases_2_tmp[$pK];
                }
                unset($phrases_2_tmp);

                $compare_1 = array_intersect_assoc($phrases_1, $phrases_2);
                foreach ($compare_1 as $cK => $cV) {
                    $adapted_compare_1[] = $phrases_1_orig[$cK];
                }
                unset($compare_1);

                $compare_2 = array_intersect_assoc($phrases_2, $phrases_1);
                foreach ($compare_2 as $cK => $cV) {
                    $adapted_compare_2[] = $phrases_2_orig[$cK];
                }
                unset($compare_2);

                if (empty($adapted_compare_1) && empty($adapted_compare_2)) {
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($lang['compare_no_diff_found']);

                    $aUrl = array("controller" => $controller);
                    $reefless->redirect($aUrl);
                } else {
                    $_SESSION['compare_1'] = $adapted_compare_1;
                    $_SESSION['lang_1'] = $lang_1;

                    $_SESSION['compare_2'] = $adapted_compare_2;
                    $_SESSION['lang_2'] = $lang_2;

                    $compare_lang1 = array('diff' => count($adapted_compare_1), 'Code' => $lang_1);
                    $compare_lang2 = array('diff' => count($adapted_compare_2), 'Code' => $lang_2);

                    $rlSmarty->assign_by_ref('compare_lang1', $compare_lang1);
                    $rlSmarty->assign_by_ref('compare_lang2', $compare_lang2);
                    $rlSmarty->assign_by_ref('langs_info', $langs_info);
                }
            }
        }
    }

    /* load admin class */
    $reefless->loadClass('AndroidLang', null, 'androidConnect');

    /* register ajax methods */
    $rlXajax->registerFunction(array('setDefault', $rlAndroidLang, 'ajaxSetDefault'));
    $rlXajax->registerFunction(array('deleteLang', $rlAndroidLang, 'ajaxDeleteLang'));
    $rlXajax->registerFunction(array('addLanguage', $rlAndroidLang, 'ajax_addLanguage'));
    $rlXajax->registerFunction(array('addPhrase', $rlAndroidLang, 'ajax_addPhrase'));
    $rlXajax->registerFunction(array('copyPhrases', $rlAndroidLang, 'ajaxCopyPhrases'));
}
