<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RSSFEED.INC.PHP
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

$content_tpl = '{include file=$smarty.const.RL_PLUGINS|cat:$smarty.const.RL_DS|cat:"rssfeed"|cat:$smarty.const.RL_DS|cat:"block.tpl" number={number} url="{url}" update="{update}"}';

/* ext js action */
if ($_GET['q'] == 'ext') {
    /* system config */
    require_once('../../../includes/config.inc.php');
    require_once(RL_ADMIN_CONTROL . 'ext_header.inc.php');
    require_once(RL_LIBS . 'system.lib.php');

    $GLOBALS['reefless']->loadClass('RssFeed', null, 'rssfeed');

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
        $rlDb->updateOne($updateData, 'rss_feed');

        if (in_array($field, array('Side', 'Tpl'))) {
            $updateData_blocks = array(
                'fields' => array(
                    $field => $value
                ),
                'where' => array(
                    'Key' => 'rssfeed_' . $id
                )
            );

            $rlDb->updateOne($updateData_blocks, 'blocks');
        } elseif ($field == 'Article_num') {
            $feed_info = $rlDb->fetch(array('Url', 'Article_num'), array('ID' => $id), null, 1, 'rss_feed', 'row');

            $new_content = str_replace(
                array('{data}'),
                array(serialize($rlRssFeed->get($feed_info['Url'], $feed_info['Article_num']))),
                $rlRssFeed->box_content
            );

            $updateData_blocks = array(
                'fields' => array('Content' => $new_content),
                'where' => array('Key' => 'rssfeed_' . $id)
            );

            $rlDb->updateOne($updateData_blocks, 'blocks');
        } elseif ($field == 'Status') {
            $updateData_blocks = array(
                'fields' => array('Status' => $value),
                'where' => array('Key' => 'rssfeed_' . $id)
            );

            $rlDb->updateOne($updateData_blocks, 'blocks');
        }
        exit;
    }

    /* data read */
    $limit = $rlValid->xSql($_GET['limit']);
    $start = $rlValid->xSql($_GET['start']);

    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT *, CONCAT('rssfeed_', `ID`) AS `Key` FROM `" . RL_DBPREFIX . "rss_feed` WHERE `Status` <> 'trash' LIMIT {$start}, {$limit}";
    $data = $rlDb->getAll($sql);
    $data = $rlLang->replaceLangKeys($data, 'blocks', array('name'), RL_LANG_CODE, 'admin');
    $rlDb->resetTable();

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $GLOBALS['lang'][$data[$key]['Status']];
        $data[$key]['Tpl'] = $data[$key]['Tpl'] ? $lang['yes'] : $lang['no'];
        $data[$key]['Side'] = $GLOBALS['lang'][$data[$key]['Side']];
    }

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
} /* ext js action end */

else {
    /* register ajax methods */
    $GLOBALS['reefless']->loadClass('RssFeed', null, 'rssfeed');

    $rlXajax->registerFunction(array('deleteRssFeed', $rlRssFeed, 'ajaxDeleteRss'));
    $rlXajax->registerFunction(array('rssfeedValidate', $rlRssFeed, 'ajaxValidate'));

    if (!$_REQUEST['xjxfun']) {
        /* additional bread crumb step */
        if ($_GET['action']) {
            switch ($_GET['action']) {
                case 'add':
                    $bcAStep = $lang['add_new_rss'];
                    break;

                case 'edit':
                    $bcAStep = $lang['edit_rss'];
                    break;
            }
        }

        if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
            /* get all languages */
            $allLangs = $GLOBALS['languages'];
            $rlSmarty->assign_by_ref('allLangs', $allLangs);

            if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
                $id = (int)$_GET['id'];

                // get rss feed info
                $item_info = $rlDb->fetch(array('Status', 'Url', 'Side', 'Tpl', 'Article_num'), array('ID' => $id), "AND `Status` <> 'trash'", 1, 'rss_feed', 'row');
                $_POST['status'] = $item_info['Status'];
                $_POST['url'] = $item_info['Url'];
                $_POST['side'] = $item_info['Side'];
                $_POST['tpl'] = $item_info['Tpl'];
                $_POST['article_num'] = $item_info['Article_num'];
                $_POST['update_delay'] = $item_info['Update_delay'];

                // get name
                $names = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'blocks+name+rssfeed_' . $id), "AND `Status` <> 'trash'", null, 'lang_keys');

                foreach ($names as $nKey => $nVal) {
                    $_POST['name'][$nVal['Code']] = $names[$nKey]['Value'];
                }
            }

            if (isset($_POST['submit'])) {
                /* check names */
                $post_names = $_POST['name'];
                foreach ($allLangs as $lkey => $lval) {
                    if (empty($post_names[$lval['Code']])) {
                        $errors[] = str_replace('{field}', "<b>" . $lang['name'] . "({$lval['name']})</b>", $lang['notice_field_empty']);
                        $error_fields[] = "name[{$lval['Code']}]";
                    }
                }

                /* check side */
                $f_side = $_POST['side'];
                if (empty($f_side)) {
                    $errors[] = str_replace('{field}', "<b>\"" . $lang['block_side'] . "\"</b>", $lang['notice_select_empty']);
                    $error_fields[] = 'side';
                }

                /* validate URL */
                $url = $_POST['url'];
                if (empty($url) || $url == 'http://' || !$rlValid->isUrl($url)) {
                    $errors[] = str_replace('{field}', "<b>\"" . $lang['link'] . "\"</b>", $lang['notice_field_incorrect']);
                    $error_fields[] = "url";
                } elseif (!$_POST['validated']) {
                    $errors[] = $lang['rssfeed_empty_feed'];
                    $error_fields[] = "url";
                }

                /* check number of articles */
                $number = (int)$_POST['article_num'];
                if (!$number) {
                    $errors[] = str_replace('{field}', "<b>\"" . $lang['article_num'] . "\"</b>", $lang['notice_field_incorrect']);
                    $error_fields[] = "article_num";
                }

                $update_delay = $_POST['update_delay'] ? (int)$_POST['update_delay'] : 12;

                if (empty($errors)) {
                    $new_content = str_replace(
                        array('{data}'),
                        array(serialize($rlRssFeed->get($url, $number))),
                        $rlRssFeed->box_content
                    );

                    /* add/edit action */
                    if ($_GET['action'] == 'add') {
                        // feed data
                        $data = array(
                            'Side' => $f_side,
                            'Tpl' => $_POST['tpl'],
                            'Url' => $url,
                            'Article_num' => $number,
                            'Update_delay' => $update_delay,
                            'Last_update' => 'NOW()',
                            'Status' => $_POST['status'],
                            'Date' => 'NOW()'
                        );

                        if ($action = $rlDb->insertOne($data, 'rss_feed')) {
                            $insert_id = method_exists($rlDb, 'insertID') ? $rlDb->insertID() : mysql_insert_id();
                            $rss_feed_key = 'rssfeed_' . $insert_id;

                            foreach ($allLangs as $key => $value) {
                                // save rss feed name
                                $lang_keys[] = array(
                                    'Code' => $value['Code'],
                                    'Module' => 'common',
                                    'Plugin' => 'rss_feed',
                                    'Status' => 'active',
                                    'Key' => 'blocks+name+' . $rss_feed_key,
                                    'Value' => $post_names[$value['Code']]
                                );
                            }

                            $rlDb->insert($lang_keys, 'lang_keys');

                            $position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `" . RL_DBPREFIX . "blocks`");

                            $data_block = array(
                                'Sticky' => '1',
                                'Key' => $rss_feed_key,
                                'Side' => $f_side,
                                'Type' => 'php',
                                'Position' => $last_position['max'] + 1,
                                'Content' => $new_content,
                                'Tpl' => $_POST['tpl'],
                                'Plugin' => 'rssfeed',
                                'Status' => $_POST['status'],
                                'Readonly' => '0'
                            );
                            $rlDb->insertOne($data_block, 'blocks');

                            $message = $lang['item_added'];
                            $aUrl = array('controller' => $controller);
                        } else {
                            $rlDebug->logger("Can't add new rssfeed (MYSQL problems)");
                        }
                    } elseif ($_GET['action'] == 'edit') {
                        $id = (int)$_GET['id'];
                        $rss_feed_key = 'rssfeed_' . $id;

                        /* edit feed */
                        $update_feed = array(
                            'fields' => array(
                                'Url' => $_POST['url'],
                                'Update_delay' => $update_delay,
                                'Last_update' => 'NOW()',
                                'Side' => $f_side,
                                'Tpl' => $_POST['tpl'],
                                'Status' => $_POST['status'],
                                'Article_num' => $number,
                                'Date' => 'NOW()'
                            ),
                            'where' => array('ID' => $id)
                        );

                        $action = $rlDb->updateOne($update_feed, 'rss_feed');

                        /* edit box */
                        $update_block = array(
                            'fields' => array(
                                'Side' => $f_side,
                                'Tpl' => $_POST['tpl'],
                                'Content' => $new_content,
                                'Type' => 'php',
                                'Status' => $_POST['status']
                            ),
                            'where' => array('Key' => $rss_feed_key)
                        );

                        $action = $rlDb->updateOne($update_block, 'blocks');

                        /* update/insert lang keys */
                        foreach ($allLangs as $key => $value) {
                            if ($rlDb->getOne('ID', "`Key` = 'blocks+name+{$rss_feed_key}' AND `Code` = '{$allLangs[$key]['Code']}'", 'lang_keys')) {
                                // edit name's values
                                $update_names = array(
                                    'fields' => array(
                                        'Value' => $_POST['name'][$allLangs[$key]['Code']]
                                    ),
                                    'where' => array(
                                        'Code' => $allLangs[$key]['Code'],
                                        'Key' => 'blocks+name+' . $rss_feed_key
                                    )
                                );

                                // update
                                $rlDb->updateOne($update_names, 'lang_keys');
                            } else {
                                // insert names
                                $insert_names = array(
                                    'Code' => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Key' => 'blocks+name+' . $rss_feed_key,
                                    'Value' => $_POST['name'][$allLangs[$key]['Code']]
                                );

                                // insert
                                $rlDb->insertOne($insert_names, 'lang_keys');
                            }
                        }

                        $message = $lang['item_edited'];
                        $aUrl = array("controller" => $controller);
                    }

                    if ($action) {
                        $GLOBALS['reefless']->loadClass('Notice');
                        $rlNotice->saveNotice($message);
                        $GLOBALS['reefless']->redirect($aUrl);
                    }
                }
            }
        }
    }
}
