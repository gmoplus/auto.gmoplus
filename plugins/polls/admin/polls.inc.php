<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: POLLS.INC.PHP
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

/* ext js action */
if ($_GET['q'] == 'ext') {
    /* system config */
    require_once('../../../includes/config.inc.php');
    require_once(RL_ADMIN_CONTROL . 'ext_header.inc.php');
    require_once(RL_LIBS . 'system.lib.php');

    $reefless->loadClass('Polls', null, 'polls');

    /* date update */
    if ($_GET['action'] == 'update') {
        $type = $rlValid->xSql($_GET['type']);
        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id = $rlValid->xSql($_GET['id']);
        $key = $rlValid->xSql($_GET['key']);

        $updateData = array(
            'fields' => array(
                $field => $value
            ),
            'where' => array(
                'ID' => $id
            )
        );
        $rlDb->updateOne($updateData, 'polls');

        $languages = $rlLang->getLanguagesList();

        if ($field == 'Status') {
            $current_poll = $rlDb->getRow("SELECT `Random` FROM `" . RL_DBPREFIX . "polls` WHERE `ID` = {$id} LIMIT 1");
            $id = $current_poll['Random'] == 1 ? 'all' : $id;
            $insert_content = $rlPolls->preparePollsContent($rlPolls->get($id));
            $polls_key = $current_poll['Random'] == 1 ? 'polls' : 'polls_' . $id;

            $update = [
                'fields' => [
                    'Content' => $insert_content,
                    'Status'  => $value ?: 'active',
                ],
                'where' => ['Key' => $polls_key]
            ];
            $rlDb->rlAllowHTML = true;
            $rlDb->updateOne($update, 'blocks');
        }

        exit;
    }

    /* data read */
    $limit = $rlValid->xSql($_GET['limit']);
    $start = $rlValid->xSql($_GET['start']);

    $sql = "SELECT `T1`.*, `T1`.`ID` AS `Key`, `T2`.`Value` AS `name` FROM `" . RL_DBPREFIX . "polls` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('polls+name+', `T1`.`ID`) = `T2`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' LIMIT {$start}, {$limit}";
    $data = $rlDb->getAll($sql);
    $rlDb->resetTable();

    foreach ($data as $key => $value) {
        $data[$key]['Tpl'] = $data[$key]['Tpl'] ? $lang['yes'] : $lang['no'];
        $data[$key]['Random'] = $data[$key]['Random'] ? $lang['no'] : $lang['yes'];
        $data[$key]['Status'] = $GLOBALS['lang'][$data[$key]['Status']];
    }

    $count = $rlDb->getRow("SELECT COUNT(`ID`) AS `count` FROM `" . RL_DBPREFIX . "polls` WHERE `Status` <> 'trash'");

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
} /* ext js action end */

else {
    /* additional bread crumb step */
    if ($_GET['action']) {
        switch ($_GET['action']) {
            case 'add':
                $bcAStep = $lang['add'];
                break;

            case 'edit':
                $bcAStep = $lang['edit'];
                break;

            case 'results':
                $bcAStep = $lang['polls_view_results'];
                break;
        }
    }

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        $reefless->loadClass('Polls', null, 'polls');

        // Remove useless box positions
        unset($l_block_sides['header_banner'], $l_block_sides['integrated_banner']);

        /* get all languages */
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);
        if ($_GET['action'] == 'edit') {
            $id = (int)$_GET['poll'];

            // get poll info
            $poll_info = $rlDb->fetch(array('Status', 'Side', 'Tpl', 'Random'), array('ID' => $id), "AND `Status` <> 'trash'", 1, 'polls', 'row');
        }

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $_POST['status'] = $poll_info['Status'];
            $_POST['side'] = $poll_info['Side'];
            $_POST['tpl'] = $poll_info['Tpl'];
            $_POST['random'] = $poll_info['Random'];

            // get titles
            $e_titles = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'polls+name+' . $id), "AND `Status` <> 'trash'", null, 'lang_keys');

            foreach ($e_titles as $nKey => $nVal) {
                $_POST['name'][$e_titles[$nKey]['Code']] = $e_titles[$nKey]['Value'];
            }

            // get polls items
            $rlDb->setTable('polls_items');
            $e_items = $rlDb->fetch(array('ID', 'Color'), array('Poll_ID' => $id), "ORDER BY `ID`");
            $rlDb->resetTable();
            foreach ($e_items as $nKey => $nVal) {
                foreach ($allLangs as $lkey => $lval) {
                    $phrase = $rlDb->fetch(array('Value'), array('Key' => 'polls_items+name+' . $e_items[$nKey]['ID'], 'Code' => $allLangs[$lkey]['Code']), "AND `Status` <> 'trash'", null, 'lang_keys', 'row');
                    $_POST['items'][$e_items[$nKey]['ID']][$allLangs[$lkey]['Code']] = $phrase['Value'];
                }
                $_POST['color'][] = $e_items[$nKey]['Color'];
            }
        }
        if (isset($_POST['submit'])) {
            $errors = array();

            /* check titles */
            $f_title = $_POST['name'];

            foreach ($allLangs as $lkey => $lval) {
                if (empty($f_title[$allLangs[$lkey]['Code']])) {
                    $errors[] = str_replace('{field}', "<b>" . $lang['name'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                }
            }

            /* check items */
            $f_items = $_POST['items'];
            $f_colors = $_POST['color'];
            if (!empty($f_items)) {
                foreach ($f_items as $key => $value) {
                    foreach ($allLangs as $lkey => $lval) {
                        if (empty($f_items[$key][$allLangs[$lkey]['Code']])) {
                            $errors[] = str_replace('{field}', "<b>" . $lang['vote_items'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                        }
                    }
                    break;
                }
            }
            /* check side */
            $f_side = $_POST['side'];
            if (empty($f_side) && !$_POST['random']) {
                $errors[] = str_replace('{field}', "<b>\"" . $lang['block_side'] . "\"</b>", $lang['notice_select_empty']);
                $error_fields[] = 'side';
            }

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                /* add/edit action */
                if ($_GET['action'] == 'add') {
                    // write main section information
                    $data = array(
                        'Side' => $f_side,
                        'Tpl' => $_POST['tpl'],
                        'Random' => $_POST['random'],
                        'Status' => $_POST['status'],
                        'Date' => 'NOW()'
                    );

                    if ($action = $rlDb->insertOne($data, 'polls')) {
                        $poll_id = $rlDb->insertID();
                        $polls_key = 'polls_' . $poll_id;

                        foreach ($f_items as $key => $val) {
                            $poll_item = array('Poll_ID' => $poll_id, 'Color' => $f_colors[$key - 1]);
                            $rlDb->insertOne($poll_item, 'polls_items');
                            $items_id[$key] = $rlDb->insertID();
                        }

                        foreach ($allLangs as $key => $value) {
                            // save poll title
                            $lang_keys[] = array(
                                'Code' => $allLangs[$key]['Code'],
                                'Module' => 'box',
                                'Target_key' => $_POST['random'] ? 'polls' : $polls_key,
                                'Plugin' => 'polls',
                                'Status' => 'active',
                                'Key' => 'polls+name+' . $poll_id,
                                'Value' => $f_title[$allLangs[$key]['Code']]
                            );

                            // save items
                            foreach ($f_items as $ikey => $ival) {
                                if (!empty($f_items[$ikey][$allLangs[$key]['Code']])) {
                                    $lang_keys[] = array(
                                        'Code' => $allLangs[$key]['Code'],
                                        'Module' => 'box',
                                        'Target_key' => $_POST['random'] ? 'polls' : $polls_key,
                                        'Plugin' => 'polls',
                                        'Status' => 'active',
                                        'Key' => 'polls_items+name+' . $items_id[$ikey],
                                        'Value' => $f_items[$ikey][$allLangs[$key]['Code']]
                                    );
                                }
                            }
                        }

                        $rlDb->insert($lang_keys, 'lang_keys');

                        if (!$_POST['random']) {
                            $position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `" . RL_DBPREFIX . "blocks`");
                            /* create poll block */
                            $data_block = array(
                                'Sticky' => '1',
                                'Key' => $polls_key,
                                'Side' => $f_side,
                                'Type' => 'php',
                                'Position' => $position['max'] + 1,
                                'Content' => $rlPolls->preparePollsContent($rlPolls->get($poll_id)),
                                'Tpl' => $_POST['tpl'],
                                'Plugin' => 'polls',
                                'Status' => $_POST['status'],
                                'Readonly' => '0'
                            );
                            $rlDb->rlAllowHTML = true;
                            $rlDb->insertOne($data_block, 'blocks');
                            $rlDb->rlAllowHTML = false;

                            foreach ($allLangs as $key => $value) {
                                // save polls block name
                                $lang_keys[] = array(
                                    'Code' => $allLangs[$key]['Code'],
                                    'Module' => 'box',
                                    'Target_key' => $polls_key,
                                    'Plugin' => 'polls',
                                    'Status' => 'active',
                                    'Key' => 'blocks+name+' . $polls_key,
                                    'Value' => $f_title[$allLangs[$key]['Code']]
                                );
                            }
                            $rlDb->insert($lang_keys, 'lang_keys');
                        } else {
                            /* edit poll block */
                            $update_block = array(
                                'fields' => array(
                                    'Content' => $rlPolls->preparePollsContent($rlPolls->get('all')),
                                    'Status' => $_POST['status']
                                ),
                                'where' => array('Key' => 'polls')
                            );
                            $rlDb->rlAllowHTML = true;
                            $action = $rlDb->updateOne($update_block, 'blocks');
                            $rlDb->rlAllowHTML = false;
                        }

                        $message = $lang['item_added'];
                        $aUrl = array("controller" => $controller);
                    } else {
                        trigger_error("Can not add new poll (MySQL problems)", E_WARNING);
                        $rlDebug->logger("Can not add new poll (MySQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    $id = (int)$_GET['poll'];
                    $polls_key = 'polls_' . $id;
                    $update_date = array(
                        'fields' => array(
                            'Status' => $_POST['status'],
                            'Side' => $f_side,
                            'Random' => $_POST['random'],
                            'Tpl' => $_POST['tpl']),
                        'where' => array('ID' => $id)
                    );
                    $action = $rlDb->updateOne($update_date, 'polls');

                    $rlDb->setTable('polls_items');
                    $items_id_tmp = $rlDb->fetch(array('ID'), array('Poll_ID' => $id), "ORDER BY `ID`");
                    $rlDb->resetTable();

                    foreach ($items_id_tmp as $key => $val) {
                        $items_id[$items_id_tmp[$key]['ID']] = $items_id_tmp[$key];

                        if (!isset($f_items[$items_id_tmp[$key]['ID']])) {
                            // remove item row
                            $rlDb->query("DELETE FROM `" . RL_DBPREFIX . "polls_items` WHERE `ID` = '{$items_id_tmp[$key]['ID']}'");

                            // remove phrases
                            $rlDb->query("DELETE FROM `" . RL_DBPREFIX . "lang_keys` WHERE `Key` = 'polls_items+name+{$items_id_tmp[$key]['ID']}' AND `Plugin` = 'polls' ");
                        } else {
                            $rlDb->query("UPDATE `" . RL_DBPREFIX . "polls_items` SET `Color` = '{$f_colors[$key]}' WHERE `ID` = {$val['ID']}");
                        }
                    }

                    $i = 0;
                    foreach ($f_items as $key => $value) {
                        if (isset($items_id[$key])) {
                            foreach ($allLangs as $lkey => $lval) {
                                $lang_phrase[] = array(
                                    'where' => array(
                                        'Code' => $allLangs[$lkey]['Code'],
                                        'Key' => 'polls_items+name+' . $key
                                    ),
                                    'fields' => array(
                                        'Value' => $f_items[$key][$allLangs[$lkey]['Code']]
                                    )
                                );
                            }
                        } else {
                            if (!empty($f_items[$key][$allLangs[$lkey]['Code']])) {
                                unset($insert);
                                $insert = array(
                                    'Poll_ID' => $id,
                                    'Color' => $f_colors[$i]
                                );
                                $rlDb->insertOne($insert, 'polls_items');
                                $new_item = $rlDb->insertID();

                                foreach ($allLangs as $lkey => $lval) {
                                    $lang_phrase_insert[] = array(
                                        'Code' => $allLangs[$lkey]['Code'],
                                        'Module' => 'box',
                                        'Target_key' => $_POST['random'] ? 'polls' : $polls_key,
                                        'Plugin' => 'polls',
                                        'Status' => 'active',
                                        'Key' => 'polls_items+name+' . $new_item,
                                        'Value' => $f_items[$key][$allLangs[$lkey]['Code']]
                                    );
                                }
                            }
                        }
                        $i++;
                    }

                    // insert new items' phrases
                    if (!empty($lang_phrase_insert)) {
                        $rlDb->insert($lang_phrase_insert, 'lang_keys');
                    }

                    // edit title's values
                    foreach ($allLangs as $key => $value) {
                        $lang_phrase[] = array(
                            'where' => array(
                                'Code' => $allLangs[$key]['Code'],
                                'Key' => 'polls+name+' . $id
                            ),
                            'fields' => array(
                                'Value' => $f_title[$allLangs[$key]['Code']]
                            )
                        );
                    }

                    $rlDb->update($lang_phrase, 'lang_keys');

                    if (!$_POST['random'] && !$poll_info['Random']) {
                        foreach ($allLangs as $key => $value) {
                            $lang_phrases[] = array(
                                'where' => array(
                                    'Code' => $allLangs[$key]['Code'],
                                    'Key' => 'blocks+name+' . $polls_key,
                                    'Plugin' => 'polls'
                                ),
                                'fields' => array(
                                    'Value' => $f_title[$allLangs[$key]['Code']]
                                )
                            );
                        }
                        $rlDb->update($lang_phrases, 'lang_keys');

                        /* edit poll block */
                        $update_block = array(
                            'fields' => array(
                                'Side' => $f_side,
                                'Tpl' => $_POST['tpl'],
                                'Content' => $rlPolls->preparePollsContent($rlPolls->get($id)),
                                'Status' => $_POST['status'],
                                'Plugin' => 'polls'
                            ),
                            'where' => array('Key' => $polls_key)
                        );

                        $rlDb->rlAllowHTML = true;
                        $action = $rlDb->updateOne($update_block, 'blocks');
                        $rlDb->rlAllowHTML = false;
                    } elseif ($_POST['random'] && !$poll_info['Random']) {
                        $rlDb->query("DELETE FROM `" . RL_DBPREFIX . "blocks` WHERE `Key` = '{$polls_key}' LIMIT 1");

                        // delete polls block names
                        $sql = "DELETE FROM `" . RL_DBPREFIX . "lang_keys` WHERE `Key` = 'blocks+name+{$polls_key}' AND `Plugin` = 'polls'";
                        $rlDb->query($sql);

                        /* edit poll block */
                        $update_block = array(
                            'fields' => array(
                                'Content' => $rlPolls->preparePollsContent($rlPolls->get('all')),
                                'Status' => $_POST['status'] ?: 'active',
                                'Plugin' => 'polls'
                            ),
                            'where' => array('Key' => 'polls')
                        );

                        $rlDb->rlAllowHTML = true;
                        $action = $rlDb->updateOne($update_block, 'blocks');
                        $rlDb->rlAllowHTML = false;
                    } elseif (!$_POST['random'] && $poll_info['Random']) {
                        $position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `" . RL_DBPREFIX . "blocks`");
                        /* create poll block */
                        $data_block = array(
                            'Sticky' => '1',
                            'Key' => $polls_key,
                            'Side' => $f_side,
                            'Type' => 'php',
                            'Position' => $position['max'] + 1,
                            'Content' => $rlPolls->preparePollsContent($rlPolls->get($id)),
                            'Tpl' => $_POST['tpl'],
                            'Plugin' => 'polls',
                            'Status' => $_POST['status'],
                            'Readonly' => '0'
                        );
                        $rlDb->rlAllowHTML = true;
                        $rlDb->insertOne($data_block, 'blocks');
                        $rlDb->rlAllowHTML = false;

                        foreach ($allLangs as $key => $value) {
                            // save polls block name
                            $lang_keys[] = array(
                                'Code' => $allLangs[$key]['Code'],
                                'Module' => 'box',
                                'Target_key' => $polls_key,
                                'Plugin' => 'polls',
                                'Status' => 'active',
                                'Key' => 'blocks+name+' . $polls_key,
                                'Value' => $f_title[$allLangs[$key]['Code']]
                            );
                        }
                        $rlDb->insert($lang_keys, 'lang_keys');

                        /* edit poll block */
                        $update_block = array(
                            'fields' => array(
                                'Content' => $rlPolls->preparePollsContent($rlPolls->get('all')),
                                'Plugin' => 'polls'
                            ),
                            'where' => array('Key' => 'polls')
                        );
                        $rlDb->rlAllowHTML = true;
                        $action = $rlDb->updateOne($update_block, 'blocks');
                        $rlDb->rlAllowHTML = false;
                    } elseif ($_POST['random'] && $poll_info['Random']) {
                        /* edit poll block */
                        $update_block = array(
                            'fields' => array(
                                'Content' => $rlPolls->preparePollsContent($rlPolls->get('all')),
                                'Status' => $_POST['status'],
                                'Plugin' => 'polls'
                            ),
                            'where' => array('Key' => 'polls')
                        );

                        $rlDb->rlAllowHTML = true;
                        $action = $rlDb->updateOne($update_block, 'blocks');
                        $rlDb->rlAllowHTML = false;
                    }

                    $message = $lang['item_edited'];
                    $aUrl = array("controller" => $controller);
                }

                if ($action) {
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                }
            }
        }
    } elseif ($_GET['action'] == 'results') {
        $poll_id = (int)$_GET['poll'];

        /* get poll info */
        $poll = $rlDb->fetch(array('ID`, `ID` AS `Key'), array('ID' => $poll_id), null, null, 'polls', 'row');
        $poll['name'] = $rlLang->getPhrase("polls+name+{$poll_id}", null, null, true);
        $rlSmarty->assign_by_ref('poll_info', $poll);

        /* get poll items */
        $total_votes = $rlDb->getRow("SELECT SUM(`Votes`) AS `sum` FROM `" . RL_DBPREFIX . "polls_items` WHERE `Poll_ID` = '{$poll_id}' LIMIT 1");
        $total_votes = $total_votes['sum'];
        $rlSmarty->assign_by_ref('total_votes', $total_votes);

        $poll_items = $rlDb->fetch(array('ID` AS `Key`, `Votes', 'Color'), array('Poll_ID' => $poll_id), null, null, 'polls_items');
        foreach ($poll_items as &$pollItem) {
            $pollItem['percent'] = $pollItem['Votes'] && $total_votes
                ? floor(((int) $pollItem['Votes'] * 100) / $total_votes)
                : 0;
            $pollItem['width'] = $pollItem['Votes'] && $total_votes
                ? floor(((int) $pollItem['Votes'] * 100) / $total_votes) * 3
                : 0;
            $pollItem['name'] = $rlLang->getPhrase("polls_items+name+{$pollItem['Key']}", null, null, true);
        }
        $rlSmarty->assign_by_ref('poll_items', $poll_items);
    }
}
