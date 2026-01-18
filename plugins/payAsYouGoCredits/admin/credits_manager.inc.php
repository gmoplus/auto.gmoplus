<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: CREDITS_MANAGER.INC.PHP
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

if ($_GET['q'] == 'ext') {
    // system config
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    // date update
    if ($_GET['action'] == 'update') {
        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id = (int) $_GET['id'];

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where' => array(
                'ID' => $id,
            ),
        );

        $rlDb->updateOne($updateData, 'credits_manager');
        exit;
    }

    // data read
    $start = (int) $_GET['start'];
    $limit = (int) $_GET['limit'];

    $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.* ";
    $sql .= "FROM `{db_prefix}credits_manager` AS `T1` ";
    $sql .= "LIMIT {$start}, {$limit}";
    $data = $rlDb->getAll($sql);
    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    foreach ($data as $key => $entry) {
        $data[$key]['Status'] = $lang[$entry['Status']];
        $data[$key]['name'] = $lang['credits_manager+name+credit_package_' . $entry['ID']];
    }
    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
    exit;
}

$reefless->loadClass('Credits', null, 'payAsYouGoCredits');

if (isset($_GET['action'])) {
    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        // additional bread crumb step
        $bcAStep[0] = array('name' => $_GET['action'] == 'add' ? $lang['paygc_add_item'] : $lang['paygc_edit_item']);

        $id = (int) $_GET['item'];

        // get all languages
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        // get account types
        $reefless->loadClass('Account');

        // get current plan info
        if (isset($_GET['item'])) {
            $credits_manager = $rlDb->fetch(
                array('ID', 'Price', 'Credits', 'Status'),
                array('ID' => $id),
                null,
                null,
                'credits_manager',
                'row'
            );
            $credits_manager['Key'] = 'credit_package_' . $credits_manager['ID'];
            $rlSmarty->assign_by_ref('credits_manager', $credits_manager);
        }

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $_POST['status'] = $credits_manager['Status'];
            $_POST['price'] = $credits_manager['Price'];
            $_POST['credits'] = $credits_manager['Credits'];

            // get names
            $names = $rlDb->fetch(
                array('Code', 'Value'),
                array('Key' => 'credits_manager+name+' . $credits_manager['Key']),
                "AND `Status` <> 'trash'",
                null,
                'lang_keys'
            );
            foreach ($names as $pKey => $pVal) {
                $_POST['name'][$pVal['Code']] = $pVal['Value'];
            }
        }

        if (isset($_POST['submit'])) {
            $errors = $error_fields = array();

            // check name
            $f_names = $_POST['name'];
            if (empty($f_names[$config['lang']])) {
                $langName = count($allLangs) > 1 ? "{$lang['name']}({$allLangs[$config['lang']]['name']})" : $lang['name'];
                array_push($errors, str_replace('{field}', "<b>{$langName}</b>", $lang['notice_field_empty']));
                array_push($error_fields, "name[{$config['lang']}]");
            }
            if (empty($_POST['price'])) {
                array_push($errors, str_replace('{field}', "<b>{$lang['price']}</b>", $lang['notice_field_empty']));
                array_push($error_fields, "price");
            }

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                if ($_GET['action'] == 'add') {
                    // get max position
                    $position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `" . RL_DBPREFIX . "credits_manager`");

                    // write main plan information
                    $data = array(
                        'Price' => $_POST['price'],
                        'Credits' => $_POST['credits'],
                        'Position' => $position['max'] + 1,
                        'Status' => $_POST['status'],
                    );

                    if ($action = $rlDb->insertOne($data, 'credits_manager')) {
                        $id_credit = $rlDb->insertID();
                        $f_key = 'credit_package_' . $id_credit;

                        // write name's phrases
                        $createPhrases = [];
                        foreach ($allLangs as $key => $value) {
                            $createPhrases[] = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'credits_manager+name+' . $f_key,
                                'Value'  => $f_names[$allLangs[$key]['Code']] ?: $f_names[$config['lang']],
                                'Plugin' => 'payAsYouGoCredits',
                            );
                        }
                        if (method_exists($rlLang, 'createPhrases')) {
                            $rlLang->createPhrases($createPhrases);
                        } else {
                            $rlDb->insert($createPhrases, 'lang_keys');
                        }

                        $message = $lang['paygc_item_added'];
                        $aUrl = array("controller" => $controller);
                    } else {
                        trigger_error("Can't add new credit package (MYSQL problems)", E_WARNING);
                        $rlDebug->logger("Can't add new credit package (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    $update_date = array(
                        'fields' => array(
                            'Price' => $_POST['price'],
                            'Credits' => $_POST['credits'],
                            'Status' => $_POST['status'],
                        ),
                        'where' => array('ID' => $id),
                    );

                    if ($action = $rlDb->updateOne($update_date, 'credits_manager')) {
                        $f_key = 'credit_package_' . $id;

                        $createPhrases = [];
                        $updatePhrases = [];
                        foreach ($allLangs as $key => $value) {
                            $sql_key = "`Key` = 'credits_manager+name+{$f_key}' AND `Code` = '{$allLangs[$key]['Code']}'";
                            if ($rlDb->getOne('ID', $sql_key, 'lang_keys')) {
                                // edit names
                                $updatePhrases[] = array(
                                    'fields' => array(
                                        'Value' => $f_names[$allLangs[$key]['Code']] ?: $f_names[$config['lang']],
                                    ),
                                    'where' => array(
                                        'Code' => $allLangs[$key]['Code'],
                                        'Key' => 'credits_manager+name+' . $f_key,
                                    ),
                                );
                            } else {
                                // insert names
                                $createPhrases[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Key'    => 'credits_manager+name+' . $f_key,
                                    'Value'  => $f_names[$allLangs[$key]['Code']] ?: $f_names[$config['lang']],
                                    'Plugin' => 'payAsYouGoCredits',
                                );
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
                    }

                    $message = $lang['paygc_item_edited'];
                    $aUrl = array("controller" => $controller);
                }
                unset($credit_info);

                // update config
                $select = "SELECT MAX(@Price_one:=`Price`/`Credits`) AS `MaxPriceCredit` ";
                $select .= "FROM `{db_prefix}credits_manager` LIMIT 1";
                $sql = "UPDATE `{db_prefix}config` SET `Default` = ROUND(({$select}), 2) ";
                $sql .= "WHERE `Key` = 'paygc_rate_hide' LIMIT 1";
                $rlDb->query($sql);

                if ($action) {
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                }
            }
        }
    }
}
