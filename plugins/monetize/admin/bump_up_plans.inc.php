<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: BUMP_UP_PLANS.INC.PHP
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

    $reefless->loadClass('Actions');
    $reefless->loadClass('Lang');
    $reefless->loadClass('Monetize', null, 'monetize');

    // data read
    $limit = intval($_GET['limit']);
    if ($_GET['action'] == 'update') {
        $update_data = array(
            'fields' => array(
                $_GET['field'] => $_GET['value'],
            ),
            'where' => array(
                'ID' => intval($_GET['id']),
            ),
        );

        $rlActions->updateOne($update_data, 'monetize_plans');
    } else {
        $start = intval($_GET['start']);
        $sql = "SELECT COUNT(`ID`) AS `count` FROM `" . RL_DBPREFIX . "monetize_plans` WHERE `Type` = 'bumpup'";
        $count = $rlDb->getRow($sql);

        $output['total'] = $count['count'];
        $data = $rlMonetize->getPlans($start, $limit, null, 'bump_up');
        foreach ($data as $key => $plan) {
             $data[$key]['Bump_ups'] = $plan['Bump_ups'] ?: $lang['unlimited'];
             $data[$key]['Price'] = $plan['Price'] ?: $lang['free'];
        }
        $output['data'] = $data;

        echo json_encode($output);
        exit;
    }
} else {
    $reefless->loadClass('Valid');
    $allLangs = $GLOBALS['languages'];
    $rlSmarty->assign_by_ref('allLangs', $allLangs);
    $reefless->loadClass('Monetize', null, 'monetize');

    //breadcrumbs
    if ($_GET['action']) {
        $bcAStep = $_GET['action'] == 'add' ? $lang['add_bump_up'] : $lang['edit_bump_up'];
    }

    if ($_GET['action'] == 'edit' && !$_POST['edit']) {
        $id = (int) $_GET['id'];

        $sql = "SELECT * FROM `{db_prefix}monetize_plans` WHERE `ID` = {$id}";
        $plan_info = $rlDb->getRow($sql);

        $_POST['id'] = $plan_info['ID'];
        $_POST['key'] = $plan_info['Key'];
        $_POST['bump_up_count'] = $plan_info['Bump_ups'];
        $_POST['bump_up_count_unlimited'] = !(bool) $plan_info['Bump_ups'];
        $_POST['price'] = $plan_info['Price'];
        $_POST['color'] = $plan_info['Color'];
        $_POST['status'] = $plan_info['Status'];

        // get name
        $where = array('Key' => 'bump_up_plan+name+' . $plan_info['Key']);
        $names = $rlDb->fetch(array('Code', 'Value'), $where, null, null, 'lang_keys');

        foreach ($names as $pKey => $pVal) {
            $_POST['name'][$names[$pKey]['Code']] = $names[$pKey]['Value'];
        }

        // get description
        $where = array('Key' => 'bump_up_plan+description+' . $plan_info['Key']);
        $descriptions = $rlDb->fetch(array('Code', 'Value'), $where, "AND `Status` <> 'trash'", null, 'lang_keys');
        foreach ($descriptions as $pKey => $pVal) {
            $_POST['description'][$descriptions[$pKey]['Code']] = $descriptions[$pKey]['Value'];
        }
    }

    // post request handler
    if ($_POST) {
        $description = $rlValid->xSql($_POST['description']);
        $plan_name = $rlValid->xSql($_POST['name']);

        if ($_POST['add']) {
            foreach ($allLangs as $lkey => $lval) {
                if (empty($plan_name[$lkey])) {
                    $find = "<b>" . $lang['name'] . "({$allLangs[$lkey]['name']})</b>";
                    $errors[] = str_replace('{field}', $find, $lang['notice_field_empty']);
                    $error_fields[] = "name[{$lval['name']}]";
                }
            }

            if ($_POST['bump_up_count'] && (int) $_POST['bump_up_count'] < 0) {
                $replace = "<b>" . $lang['bumpups_available'] . "</b>";
                $errors[] = str_replace('{h_field}', $replace, $lang['m_field_negative']);
                $error_fields[] = "bump_up_count";
            }

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                $data = array(
                    'Type' => 'bump_up',
                    'Bump_ups' => $_POST['bump_up_count_unlimited'] ? 0 : $_POST['bump_up_count'],
                    'Price' => (float)$_POST['price'],
                    'Color' => $_POST['color'],
                    'Status' => $_POST['status'],
                );
                $result = $rlMonetize->addPlan($data, $plan_name, $description);

                if ($result) {
                    $message = $lang['bumpup_plan_added'];
                } else {
                    $message = $lang['bump_up_adding_error'];
                }
            }
        }

        if ($_POST['edit']) {
            $planID = (int) $_POST['plan_id'];

            $data = array(
                'Type' => 'bump_up',
                'Bump_ups' => $_POST['bump_up_count'],
                'Price' => $_POST['price'],
                'Color' => $_POST['color'],
                'Status' => $_POST['status'],
            );
            $result = $rlMonetize->editPlan($planID, $data, $plan_name, $description);

            $message = $lang['bumpup_plan_edited'];
        }

        $redirect_url = array("controller" => $controller);
    }
}
