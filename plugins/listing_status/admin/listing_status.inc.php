<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: LISTING_STATUS.INC.PHP
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

use Flynax\Utils\Valid;

if ($_GET['q'] == 'ext') {
    // system config
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    if ($_GET['action'] == 'update') {

        $field = Valid::escape($_GET['field']);
        $value = Valid::escape(nl2br($_GET['value']));
        $id = (int) $_GET['id'];

        $key = $rlDb->getOne('Key', "`ID` = '{$id}' ", 'listing_status');

        if ($field == 'Status') {
            $updateBl = array(
                'fields' => array(
                    $field => $value,
                ),
                'where' => array(
                    'Key' => 'lb_' . $key,
                ),
            );
            $rlDb->updateOne($updateBl, 'blocks');

            $updateData = array(
                'fields' => array(
                    $field => $value,
                ),
                'where' => array(
                    'ID' => $id,
                ),
            );
            $rlDb->updateOne($updateData, 'listing_status');
            exit;
        } else {
            /* update block */
            $reefless->loadClass('ListingStatus', null, 'listing_status');

            $field_replace = array($field, $value, $id);
            $dataContent = $rlListingStatus->checkContentBlock(false, false, false, false, false, $field_replace);

            $updateData = array(
                'fields' => array(
                    'Content' => $dataContent,
                ),
                'where' => array(
                    'Key' => 'lb_' . $key,
                ),
            );
            $rlDb->updateOne($updateData, 'blocks');

            /* update status */
            $updateData = array(
                'fields' => array(
                    $field => $value,
                ),
                'where' => array(
                    'ID' => $id,
                ),
            );
            $rlDb->updateOne($updateData, 'listing_status');
            exit;
        }
    }

    // data read
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];

    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT * ";
    $sql .= "FROM `{db_prefix}listing_status` ";
    $sql .= "ORDER BY `ID` ASC ";
    $sql .= "LIMIT {$start}, {$limit}";

    $data = $rlDb->getAll($sql);

    foreach ($data as $key => $value) {
        $data[$key]['name']   = $lang['lsl_'.$value['Key']] ?: $rlLang->getPhrase(array('key' => 'lsl_'.$value['Key'], 'lang' => $config['lang']));
        $data[$key]['Status'] = $lang[$value['Status']];
    }

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");
    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
    exit;
} else {

    $reefless->loadClass('ListingStatus', null, 'listing_status');

    if (isset($_GET['module'])) {
        if (is_file(RL_PLUGINS . 'listing_status' . RL_DS . 'admin' . RL_DS . $_GET['module'] . '.inc.php')) {
            require_once RL_PLUGINS . 'listing_status' . RL_DS . 'admin' . RL_DS . $_GET['module'] . '.inc.php';
        } else {
            $sError = true;
        }
    } else {

        // reject non supported box sides
        $rlListingStatus->rejectBoxSides();

        /* get type list*/
        $rlSmarty->assign_by_ref('listing_types', $rlListingTypes->types);

        if ($_GET['action']) {
            $bcAStep = $_GET['action'] == 'add' ? $lang['ls_add_label'] : $lang['ls_edit_label'];
        }

        if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
            /* get categories/section */
            $sections = $rlCategories->getCatTree(0, false, true);
            $rlSmarty->assign_by_ref('sections', $sections);

            /* get all languages */
            $allLangs = $GLOBALS['languages'];
            $rlSmarty->assign_by_ref('allLangs', $allLangs);

            /* get pages list */
            $pages = $rlDb->fetch(array('ID', 'Key'), array('Tpl' => 1), "AND `Status` <> 'trash' ORDER BY `Key`", null, 'pages');
            $pages = $rlLang->replaceLangKeys($pages, 'pages', array('name'), RL_LANG_CODE, 'admin');
            $rlSmarty->assign_by_ref('pages', $pages);

            $id = (int) $_GET['block'];

            // modify ls_watermarks_hint
            $label_width = (int) (($config['pg_upload_thumbnail_width'] / 100) * 20);
            $lang['ls_watermarks_label_hint'] = str_replace('{size}', $label_width, $lang['ls_watermarks_hint']);

            $label_large_width = (int) (($config['pg_upload_large_width'] / 100) * 20);
            $lang['ls_watermarks_large_label_hint'] = str_replace('{size}', $label_large_width, $lang['ls_watermarks_hint']);

            // get current block info
            $status_info = $rlDb->fetch('*', array('ID' => $id), null, null, 'listing_status', 'row');

            if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
                $_POST['id']             = $status_info['ID'];
                $_POST['key']            = $status_info['Key'];
                $_POST['count']          = $status_info['Count'];
                $_POST['days']           = $status_info['Days'];
                $_POST['type']           = explode(',', $status_info['Type']);
                $_POST['used_block']     = $status_info['Used_block'];
                $_POST['delete']         = $status_info['Delete'] == 'simple' ? '' : $status_info['Delete'];
                $_POST['status']         = $status_info['Status'];
                $_POST['order']          = $status_info['Order'];
                $_POST['watermark_type'] = $status_info['Watermark_type'];
                $_POST['position']       = $status_info['Position'];
                $_POST['label_type']     = $status_info['Label_type'];

                $names = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'lsl_' . $status_info['Key']), "AND `Status` <> 'trash'", null, 'lang_keys');
                foreach ($names as $nKey => $nVal) {
                    $_POST['name'][$names[$nKey]['Code']]           = $names[$nKey]['Value'];
                    $_POST['watermark'][$names[$nKey]['Code']]      = $status_info['watermark_' . $names[$nKey]['Code']];
                    $_POST['watermarkLarge'][$names[$nKey]['Code']] = $status_info['watermarkLarge_' . $names[$nKey]['Code']];

                }

                if ($status_info['Used_block'] == '1') {
                    $block_info = $rlDb->fetch(
                        array('Side', 'Tpl', 'Status', 'Sticky', 'Cat_sticky', 'Subcategories', 'Category_ID', 'Page_ID'),
                        array('Key' => 'lb_' . $status_info['Key']),
                        null,
                        null,
                        'blocks',
                        'row'
                    );
                    $bnames = $rlDb->fetch(
                        array('Code', 'Value'),
                        array('Key' => 'blocks+name+lb_' . $status_info['Key']),
                        "AND `Status` <> 'trash'",
                        null,
                        'lang_keys'
                    );
                    foreach ($bnames as $nKey => $nVal) {
                        $_POST['block_name'][$bnames[$nKey]['Code']] = $bnames[$nKey]['Value'];
                    }

                    $_POST['side']          = $block_info['Side'];
                    $_POST['tpl']           = $block_info['Tpl'];
                    $_POST['block_status']  = $block_info['Status'];
                    $_POST['show_on_all']   = $block_info['Sticky'];
                    $_POST['cat_sticky']    = $block_info['Cat_sticky'];
                    $_POST['subcategories'] = $block_info['Subcategories'];
                    $_POST['categories']    = explode(',', $block_info['Category_ID']);

                    $m_pages = explode(',', $block_info['Page_ID']);
                    foreach ($m_pages as $page_id) {
                        $_POST['pages'][$page_id] = $page_id;
                    }
                    unset($m_pages);
                }
            }

            if (isset($_POST['submit'])) {
                $errors = array();

                if ($_GET['action'] == 'add') {
                    $f_key = $rlListingStatus->generateKey(0);
                }
                elseif ($_GET['action'] == 'edit') {
                    $f_key = $status_info['Key'];
                }

                /* check name */
                $f_wtype          = $_POST['watermark_type'];
                $f_name           = $_POST['name'];
                $f_watermark      = $_FILES['watermark']['name'];
                $f_watermarkLarge = $_FILES['watermarkLarge']['name'];


                if (empty($f_name[$config['lang']])) {
                    $errors[]       = str_replace('{field}', "<b>" . $lang['name'] . "({$allLangs[$config['lang']]['name']})</b>", $lang['notice_field_empty']);
                    $error_fields[] = "name[{$config['lang']}]";
                }

                if ($f_wtype == 'image') {
                    if (!$_POST['watermark'][$config['lang']]) {
                        $ext = array_reverse(explode('.', $f_watermark[$config['lang']]));
                        if (empty($f_watermark[$config['lang']]) || !$rlValid->isImage($ext[0]) || !$rlListingStatus->isImage($_FILES['watermark']['tmp_name'][$config['lang']])) {
                            $errors[] = str_replace('{field}', "<b>" . $lang['ls_watermark'] . "({$config['lang']})</b>", $lang['notice_field_empty']);
                            $error_fields[] = "watermark[{$config['lang']}]";
                        }
                    }
                    if (!$_POST['watermarkLarge'][$config['lang']]) {
                        $ext = array_reverse(explode('.', $f_watermarkLarge[$config['lang']]));

                        if (empty($f_watermarkLarge[$config['lang']]) || !$rlValid->isImage($ext[0]) || !$rlListingStatus->isImage($_FILES['watermarkLarge']['tmp_name'][$config['lang']])) {
                            $errors[] = str_replace('{field}', "<b>" . $lang['ls_label_large'] . "({$config['lang']})</b>", $lang['notice_field_empty']);
                            $error_fields[] = "watermarkLarge_[{$config['lang']}]";
                        }
                    }

                    if ($_FILES) {
                        foreach ($allLangs as $lkey => $lval) {
                            foreach ($_FILES as $keys => $tmp_files) {
                                $code = $allLangs[$lkey]['Code'];

                                if (!empty($tmp_files['tmp_name'][$code])) {
                                    $file_ext = explode('.', $tmp_files['name'][$code]);
                                    $file_ext = array_reverse($file_ext);

                                    if (!$rlValid->isImage($file_ext[0]) || !$rlListingStatus->isImage($_FILES[$keys]['tmp_name'][$code])) {
                                        $errors[] = str_replace('{field}', "<b>" . $lang[$keys == 'watermarkLarge' ? 'ls_label_large' : 'ls_watermark'] . "({$config['lang']})</b>", $lang['notice_field_empty']);
                                        $error_fields[] = "watermarkLarge_[{$config['lang']}]";
                                    }
                                    else {
                                        $file_ext = '.' . $file_ext[0];
                                        if ($keys == 'watermarkLarge') {
                                            $photo_name = $f_key . '_large_' . $code . $file_ext;
                                        } else {
                                            $photo_name = $f_key . '_' . $code . $file_ext;
                                        }
                                        $photo_file = RL_FILES . 'watermark' . RL_DS . $photo_name;

                                        if (move_uploaded_file($tmp_files['tmp_name'][$code], $photo_file)) {
                                            if ($keys == 'watermarkLarge') {
                                                $_POST['watermarkLarge'][$code] = $photo_name;
                                            } else {
                                                $_POST['watermark'][$code] = $photo_name;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                /* check Days */
                $f_days = $_POST['days'];

                if ($_POST['delete']) {
                    if (empty($f_days)) {
                        $errors[]       = str_replace('{field}', "<b>\"" . $lang['days'] . "\"</b>", $lang['notice_field_empty']);
                        $error_fields[] = 'days';
                    }
                }

                /* check count */
                $f_count = $_POST['count'];
                if (empty($f_count)) {
                    $errors[]       = str_replace('{field}', "<b>\"" . $lang['listing_number'] . "\"</b>", $lang['notice_field_empty']);
                    $error_fields[] = 'count';
                } elseif ($f_count > 30 && !empty($f_count)) {
                    $errors[]       = $lang['ls_more_listings'];
                    $error_fields[] = 'count';
                }

                /* check count */
                $f_type = $_POST['type'];
                if (empty($f_type)) {
                    $errors[]       = str_replace('{field}', "<b>\"" . $lang['listing_type'] . "\"</b>", $lang['notice_field_empty']);
                    $error_fields[] = 'type';
                }

                if ($_POST['used_block'] == '1') {
                    /* check name */
                    $f_block_name = $_POST['block_name'];

                    foreach ($allLangs as $lkey => $lval) {
                        if (empty($f_block_name[$allLangs[$lkey]['Code']])) {
                            $errors[]       = str_replace('{field}', "<b>" . $lang['ls_block_name'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                            $error_fields[] = "block_name[{$lval['Code']}]";
                        }
                    }

                    /* check side */
                    $f_side = $_POST['side'];

                    if (empty($f_side)) {
                        $errors[]       = str_replace('{field}', "<b>\"" . $lang['block_side'] . "\"</b>", $lang['notice_select_empty']);
                        $error_fields[] = 'side';
                    }
                }

                if (!empty($errors)) {
                    $rlSmarty->assign_by_ref('errors', $errors);
                } else {
                    $action = false;

                    $f_types = is_array($f_type) ? implode(',', $f_type) : $f_type;

                    /* add/edit action */
                    if ($_GET['action'] == 'add') {
                        $fb_key = 'lb_' . $f_key;
                        $createPhrases = [];

                        $data_status = array(
                            'Key'            => $f_key,
                            'Count'          => $f_count,
                            'Days'           => $f_days,
                            'Order'          => $_POST['order'],
                            'Type'           => $f_types,
                            'Delete'         => $_POST['delete'] ? $_POST['delete'] : 'simple',
                            'Used_block'     => $_POST['used_block'],
                            'Status'         => $_POST['status'],
                            'Label_type'     => $_POST['label_type'],
                            'Watermark_type' => $f_wtype,
                            'Position'       => $_POST['position'],
                        );

                        // write name's phrases and add watermark
                        foreach ($allLangs as $key => $value) {
                            $code = $allLangs[$key]['Code'];
                            $createPhrases[] = array(
                                'Code'   => $code,
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'lsl_' . $f_key,
                                'Value'  => $f_name[$code],
                                'Plugin' => 'listing_status',
                            );

                            $createPhrases[] = array(
                                'Code'   => $code,
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'ls_notice_' . $f_key,
                                'Value'  => str_replace('[status]', $f_name[$code], $lang['ls_notice_all']),
                                'Plugin' => 'listing_status',
                            );

                            if ($f_wtype == 'image') {
                                $data_status['watermark_' . $code]      = $_POST['watermark'][$code];
                                $data_status['watermarkLarge_' . $code] = $_POST['watermarkLarge'][$code];
                            }
                        }

                        if ($action = $rlDb->insertOne($data_status, 'listing_status')) {
                            if ($_POST['used_block'] == '1') {
                                // write name's phrases
                                foreach ($allLangs as $key => $value) {
                                    $createPhrases[] = array(
                                        'Code'   => $allLangs[$key]['Code'],
                                        'Module' => 'common',
                                        'Status' => 'active',
                                        'Key'    => 'blocks+name+' . $fb_key,
                                        'Value'  => $f_block_name[$allLangs[$key]['Code']],
                                        'Plugin' => 'listing_status',
                                    );
                                }

                                // get max position
                                $position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}blocks`");

                                $f_pages = is_array($_POST['pages']) ? implode(',', $_POST['pages']) : $_POST['pages'];
                                $f_categories = is_array($_POST['categories']) ? implode(',', $_POST['categories']) : $_POST['categories'];

                                // write main, block information
                                $data = array(
                                    'Key'           => $fb_key,
                                    'Status'        => $_POST['status'],
                                    'Position'      => $position['max'] + 1,
                                    'Side'          => $f_side,
                                    'Type'          => 'php',
                                    'Tpl'           => $_POST['tpl'],
                                    'Readonly'      => '1',
                                    'Page_ID'       => $f_pages,
                                    'Category_ID'   => $f_categories,
                                    'Subcategories' => empty($_POST['subcategories']) ? 0 : 1,
                                    'Sticky'        => empty($_POST['show_on_all']) ? 0 : 1,
                                    'Cat_sticky'    => empty($_POST['cat_sticky']) ? 0 : 1,
                                    'Plugin'        => 'listing_status',
                                );
                                $data['Content'] = $rlListingStatus->checkContentBlock($f_types, $f_days, $f_count, $f_key, $_POST['order'], false);
                                $rlDb->insertOne($data, 'blocks');
                            }
                            if ($action) {
                                if (method_exists($rlLang, 'createPhrases')) {
                                    $rlLang->createPhrases($createPhrases);
                                } else {
                                    $rlDb->insert($createPhrases, 'lang_keys');
                                }

                                $message = $lang['ls_block_add'];
                                $aUrl    = array("controller" => $controller);
                            } else {
                                trigger_error("Can't add new block (MYSQL problems)", E_WARNING);
                                $rlDebug->logger("Can't add new block (MYSQL problems)");
                            }
                        }
                    } elseif ($_GET['action'] == 'edit') {
                        $fb_key = 'lb_' . $f_key;

                        /*update status*/
                        $data_status = array(
                            'fields' => array(
                                'Count'          => $f_count,
                                'Days'           => $f_days,
                                'Order'          => $_POST['order'],
                                'Type'           => $f_types,
                                'Delete'         => $_POST['delete'] ? $_POST['delete'] : 'simple',
                                'Used_block'     => $_POST['used_block'],
                                'Status'         => $_POST['status'],
                                'Label_type'     => $_POST['label_type'],
                                'Watermark_type' => $f_wtype,
                                'Position'       => $_POST['position'],
                            ),
                            'where' => array('ID' => $id),
                        );

                        $createPhrases = [];
                        $updatePhrases = [];

                        /*update langs for status*/
                        foreach ($allLangs as $key => $value) {
                            $code = $allLangs[$key]['Code'];

                            if ($rlDb->getOne('ID', "`Key` = 'lsl_{$f_key}' AND `Code` = '{$code}'", 'lang_keys')) {
                                // edit name's values
                                $updatePhrases[] = array(
                                    'fields' => array(
                                        'Value' => $_POST['name'][$code],
                                    ),
                                    'where' => array(
                                        'Code' => $code,
                                        'Key' => 'lsl_' . $f_key,
                                    ),
                                );
                            } else {
                                // insert names
                                $createPhrases[] = array(
                                    'Code'   => $code,
                                    'Module' => 'common',
                                    'Key'    => 'lsl_' . $f_key,
                                    'Plugin' => 'listing_status',
                                    'Value'  => $_POST['name'][$code],
                                );
                            }

                            /*add watermark for status*/
                            if ($f_wtype == 'image') {
                                $data_status['fields']['watermark_' . $code]      = $_POST['watermark'][$code];
                                $data_status['fields']['watermarkLarge_' . $code] = $_POST['watermarkLarge'][$code];
                            }
                        }

                        if ($action = $rlDb->updateOne($data_status, 'listing_status')) {
                            $block_id = $rlDb->getOne('ID', "`Key` = '{$fb_key}'", 'blocks');

                            if ($_POST['status'] != 'active') {
                                $rlListingStatus->changeSubStatus($_POST['id'], $_POST['status']);
                            }

                            if ($status_info['Label_type'] == 'admin' && $_POST['label_type'] != 'admin') {
                                $sql = "UPDATE `{db_prefix}listings` SET `Sub_status_data` = REPLACE(`Sub_status_data`, '{$f_key}', '') ";
                                $sql .= "WHERE `Sub_status_data` = '{$f_key}' OR (FIND_IN_SET('{$f_key}', `Sub_status_data`) > 0)";
                                $rlDb->query($sql);
                            }

                            $f_pages = is_array($_POST['pages']) ? implode(',', $_POST['pages']) : $_POST['pages'];
                            $f_categories = is_array($_POST['categories']) ? implode(',', $_POST['categories']) : $_POST['categories'];

                            /*if will use the block*/
                            if ($_POST['used_block'] == '1') {
                                /*update block*/
                                if ($block_id) {
                                    $update_data = array(
                                        'fields' => array(
                                            'Status'        => $_POST['block_status'],
                                            'Side'          => $f_side,
                                            'Tpl'           => $_POST['tpl'],
                                            'Page_ID'       => $f_pages,
                                            'Sticky'        => empty($_POST['show_on_all']) ? 0 : 1,
                                            'Category_ID'   => $_POST['cats_sticky'] ? '' : $f_categories,
                                            'Subcategories' => empty($_POST['subcategories']) ? 0 : 1,
                                            'Cat_sticky'    => empty($_POST['cat_sticky']) ? 0 : 1,
                                        ),
                                        'where' => array('Key' => $fb_key),
                                    );
                                    $update_data['fields']['Content'] = $rlListingStatus->checkContentBlock($f_types, $f_days, $f_count, $f_key, $_POST['order'], false);

                                    $rlDb->updateOne($update_data, 'blocks');
                                } else {
                                    /*add block*/

                                    // get max position
                                    $position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}blocks`");

                                    // write main, block information
                                    $data = array(
                                        'Key'           => $fb_key,
                                        'Status'        => $_POST['status'],
                                        'Position'      => $position['max'] + 1,
                                        'Side'          => $f_side,
                                        'Type'          => 'php',
                                        'Tpl'           => $_POST['tpl'],
                                        'Readonly'      => '1',
                                        'Page_ID'       => $f_pages,
                                        'Category_ID'   => $f_categories,
                                        'Subcategories' => empty($_POST['subcategories']) ? 0 : 1,
                                        'Sticky'        => empty($_POST['show_on_all']) ? 0 : 1,
                                        'Cat_sticky'    => empty($_POST['cat_sticky']) ? 0 : 1,
                                        'Plugin'        => 'listing_status',
                                    );
                                    $data['Content'] = $rlListingStatus->checkContentBlock($f_types, $f_days, $f_count, $f_key, $_POST['order'], false);
                                    $rlDb->insertOne($data, 'blocks');
                                }
                                foreach ($allLangs as $key => $value) {
                                    $code = $allLangs[$key]['Code'];
                                    if ($rlDb->getOne('ID', "`Key` = 'blocks+name+{$fb_key}' AND `Code` = '{$code}'", 'lang_keys')) {
                                        // edit name's values
                                        $updatePhrases[] = array(
                                            'fields' => array(
                                                'Value' => $f_block_name[$code],
                                            ),
                                            'where' => array(
                                                'Code' => $code,
                                                'Key' => 'blocks+name+' . $fb_key,
                                            ),
                                        );
                                    } else {
                                        // insert names
                                        $createPhrases[] = array(
                                            'Code' => $code,
                                            'Module' => 'common',
                                            'Key'    => 'blocks+name+' . $fb_key,
                                            'Plugin' => 'listing_status',
                                            'Value'  => $f_block_name[$code],
                                        );
                                    }
                                }
                            } else {
                                // Detele block
                                if ($block_id) {
                                    $rlDb->query("DELETE FROM `{db_prefix}blocks` WHERE `Key` = '{$fb_key}' LIMIT 1");

                                    if (method_exists($rlLang, 'deletePhrase')) {
                                        $rlLang->deletePhrase(['Key' => "blocks+name+{$fb_key}"]);
                                    } else {
                                        $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'blocks+name+{$fb_key}'");
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

                            $message = $lang['ls_block_edit'];
                            $aUrl = array("controller" => $controller);
                        }
                    }

                    if ($action) {
                        $reefless->loadClass('Notice');
                        $rlNotice->saveNotice($message);
                        $reefless->redirect($aUrl);
                    }
                }
            }

            if (method_exists($rlCategories, 'ajaxGetCatLevel')) {
                $rlXajax->registerFunction(array('getCatLevel', $rlCategories, 'ajaxGetCatLevel'));
            }
            if (method_exists($rlCategories, 'ajaxOpenTree')) {
                $rlXajax->registerFunction(array('openTree', $rlCategories, 'ajaxOpenTree'));
            }
        }
    }
}
