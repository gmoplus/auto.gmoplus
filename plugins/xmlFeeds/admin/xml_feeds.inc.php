<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: XML_FEEDS.INC.PHP
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

use Flynax\Utils\Util;

/* ext js action */
if ($_GET['q'] == 'ext_feeds') {

    /* system config */
    require_once('../../../includes/config.inc.php');
    require_once(RL_ADMIN_CONTROL . 'ext_header.inc.php');
    require_once(RL_LIBS . 'system.lib.php');
    
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
        
        $rlDb->updateOne($updateData, 'xml_feeds');
    }
    
    /* data read */
    $limit = (int)$_GET['limit'];
    $start = (int)$_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);
    $key = $rlValid->xSql($_GET['key']);

    /* run filters */
    $filters = array(
        'f_Account' => true,
        'f_format' => true
    );

    $rlHook->load('apExtListingsFilters');

    foreach ($_GET as $filter => $val) {
        if (array_key_exists($filter, $filters)) {
            $filter_field = explode('f_', $filter);

            switch ($filter_field[1]){
                case 'Account':
                    $where .= "AND `T3`.`Username` = '" . $_GET[$filter] . "' ";
                    break;
                default:
                    $where .= "AND `T1`.`" . $filter_field[1] . "` = '" . $_GET[$filter] . "' ";
                    break;
            }
        }
    }

    $sql = "SELECT SQL_CALC_FOUND_ROWS  `T1`.*, `T3`.`Username` as `account`, `T2`.`Name` AS `Format` ";
    $sql .="FROM `{db_prefix}xml_feeds` AS `T1` ";
    $sql .="LEFT JOIN `{db_prefix}xml_formats` AS `T2` ON `T1`.`Format` = `T2`.`Key` ";
    $sql .="LEFT JOIN `{db_prefix}accounts` AS `T3` ON `T3`.`ID` = `T1`.`Account_ID` ";
    $sql .="WHERE `T1`.`Status` <> 'trash' ";

    if ($where) {
        $sql .= $where;
    }
    
    if ($sort) {
        $sql .= "ORDER BY `T1`.`{$sort}` {$sortDir} ";
    }

    $sql .= "LIMIT {$start},{$limit}";

    $data = $rlDb->getAll($sql);

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $lang[$value['Status']];
    }

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $output['total'] = $count['count'];
    $output['data'] = $data;
    
    echo json_encode($output);
} elseif ($_GET['q'] == 'ext_formats') {
    /* system config */
    require_once('../../../includes/config.inc.php');
    require_once(RL_ADMIN_CONTROL . 'ext_header.inc.php');
    require_once(RL_LIBS . 'system.lib.php');

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

        $rlDb->updateOne($updateData, 'xml_formats');
    }
    
    /* data read */
    $limit = (int)$_GET['limit'];
    $start = (int)$_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);

    $sql = "SELECT SQL_CALC_FOUND_ROWS * ";
    $sql .= "FROM `{db_prefix}xml_formats` ";
    $sql .= "WHERE `Status` <> 'trash' ";

    if ($sort) {
        $sql .= "ORDER BY `T1`.`{$sort}` {$sortDir} ";
    }

    $sql .= "LIMIT {$start},{$limit}";

    $data = $rlDb->getAll($sql);

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $lang[$value['Status']];
    }
    
    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);

} elseif ($_GET['q'] == 'ext_mapping') {
    /* system config */
    require_once('../../../includes/config.inc.php');
    require_once(RL_ADMIN_CONTROL . 'ext_header.inc.php');
    require_once(RL_LIBS . 'system.lib.php');

    /* date update */
    if ($_GET['action'] == 'update' ) {
        $type = $rlValid->xSql($_GET['type']);
        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id = $rlValid->xSql($_GET['id']);
        $key = $rlValid->xSql($_GET['key']);

        if ($field == 'Local_field_name') {
            $field = 'Data_local';

            if ($value == 'unset') {
                $value = '';
            }
        }

        $updateData = array(
            'fields' => array(
                $field => $value
            ),
            'where' => array(
                'ID' => $id
            )
        );

        $rlDb->updateOne($updateData, 'xml_mapping');
    }

    /* data read */
    $limit = (int)$_GET['limit'];
    $start = (int)$_GET['start'];
    $sortField = $_GET['sort'] ? $rlValid->xSql($_GET['sort']) : "ID";
    $sortDir = $_GET['dir'] ? $rlValid->xSql($_GET['dir']) : "ASC";

    $reefless->loadClass('XmlMapping', null, 'xmlFeeds');
    $rlXmlMapping->getMultifieldRelatedFileds();

    $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.*, `T2`.`Type` AS `Local_field_type`, ";
    $sql .="`T3`.`Value` AS `Local_field_name`, `T1`.`Format` AS `Format`, ";
    $sql .="IF(FIND_IN_SET(`T2`.`Condition`, '" . $mfs . "'), 1, '') as `Mf`, ";
    $sql .="`TC`.`Key` AS `Mixed_field_key`, `TC`.`Condition` AS `Mixed_field_condition`, `TC`.`Type` AS `Mixed_field_type` ";
    $sql .= "FROM `{db_prefix}xml_mapping` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}listing_fields` AS `T2` ON `T1`.`Data_local` = `T2`.`Key` ";
    $sql .= "LEFT JOIN `{db_prefix}listing_fields` AS `TC` ON `T1`.`Data_local` = CONCAT(`TC`.`Key`, '_unit') ";
    $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T3` ON `T3`.`Key` = CONCAT('listing_fields+name+', `T2`.`Key`) ";
    $sql .= "AND `T3`.`Code` = '" . RL_LANG_CODE . "' ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' ";

    if ($_GET['format']) {
        $sql .="AND `T1`.`Format` = '" . $_GET['format'] . "' ";
    }

    $sql .="AND `T1`.`Parent_ID` = 0 ";
    $sql .= "ORDER BY `Status` ASC, {$sortField} {$sortDir} ";
    $sql .= "LIMIT {$start},{$limit}";
    
    $data = $rlDb->getAll($sql);
    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $lang[$value['Status']];

        if ($value['Data_local']) {
            if (substr($value['Data_local'], 0, 4) == 'sys_') {
                if (in_array($value['Data_local'], ['sys_xml_ref', 'sys_xml_back_url', 'sys_dealer_id', 'sys_dealer_email', 'sys_status', 'sys_lang_codes'])) {
                    $data[$key]['Local_field_name'] = $lang['xf_' . $value['Data_local']];
                    $data[$key]['Build_url'] = "field=" . $value['Data_local'];
                } elseif (preg_match('#sys_category_(\d)#', $value['Data_local'], $match)) {
                    $allow_build_url = false;
                    if ($match[1] >= 1) {
                        $sql = "SELECT `ID` FROM `{db_prefix}xml_mapping` ";
                        $sql .="WHERE `Data_local` = 'sys_category_0' AND `Format` = '" . $_GET['format'] . "' ";
                        
                        $cat_toplevel_mapping = $rlDb->getRow($sql);
                    }

                    $allow_build_url = $match[1] >= 1 && !$cat_toplevel_mapping ? false : true;

                    $data[$key]['Local_field_name'] = $lang['category'] . " Level " . $match[1];
                    $data[$key]['Build_url'] = "field=sys_category";
                } elseif (preg_match('#sys_mld_(.*)_([a-z]{2})#', $value['Data_local'], $match)) {
                    $language_key = $rlDb->getOne('Key', "`Code` = '{$match[2]}'", 'languages');
                    $data[$key]['Local_field_name'] = $lang['listing_fields+name+' . $match[1] ] . '(' . ucfirst($language_key) . ')';
                } elseif ($lang['xf_' . $value['Data_local']]) {
                    $data[$key]['Local_field_name'] = $lang['xf_' . $value['Data_local']];
                }
            } elseif (substr($value['Data_local'], -5) == '_unit') {
                $data[$key]['Local_field_name'] = $lang['listing_fields+name+' . $value['Mixed_field_key']] . " " .$lang['xf_type_unit'];
                $data[$key]['Build_url'] = "field=" . substr($value['Data_local'], 0, -5);
                
                if ($value['Mixed_field_type'] == 'price') {
                    $data[$key]['Local_field_name'] .= " (" . $GLOBALS['lang']['data_formats+name+currency'] .")";
                } elseif ($value['Mixed_field_condition']) {
                    $data[$key]['Local_field_name'] .= " (" . $GLOBALS['lang']['data_formats+name+' . $value['Mixed_field_condition']] .")";
                } else {
                    $data[$key]['Local_field_name'] .= " (" . $GLOBALS['lang']['listing_fields+name+' . $value['Mixed_field_key']] .")";
                }
            } else {
                if (in_array($value['Local_field_type'], ['select', 'radio', 'checkbox','price', 'mixed']) && !in_array($value['Data_local'], $rlXmlMapping->multiFormatKeys)) {
                    $data[$key]['Build_url'] = "field=" . $value['Data_local'];
                }
            }
        }

        if ($value['Example_value'] && strlen(strip_tags($value['Example_value'])) !== strlen($value['Example_value'])) {
            $data[$key]['Example_value'] = htmlentities($data[$key]['Example_value']);
        }
    }

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);

} elseif ($_GET['q'] == 'ext_item_mapping') {
    /* system config */
    require_once('../../../includes/config.inc.php');
    require_once(RL_ADMIN_CONTROL . 'ext_header.inc.php');
    require_once(RL_LIBS . 'system.lib.php');

    /* date update */
    if ($_GET['action'] == 'update') {
        $type = $rlValid->xSql($_GET['type']);
        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id = $rlValid->xSql($_GET['id']);
        $key = $rlValid->xSql($_GET['key']);

        if ($field == 'Local_field_name') {
            $field = 'Data_local';
        }

        $updateData = array(
            'fields' => array(
                $field => $value
            ),
            'where' => array(
                'ID' => $id
            )
        );

        $rlDb->updateOne($updateData, 'xml_mapping');
    }

    /* data read */
    $limit = (int)$_GET['limit'];
    $start = (int)$_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);
    
    $request_field = $rlValid->xSql(trim($_GET['field']));
    $format = $rlValid->xSql(trim($_GET['format']));
    $parent_id = (int) $_GET['parent'];
    
    if ($request_field) {
        $reefless->loadClass('XmlFeeds', null, 'xmlFeeds');
        $reefless->loadClass('XmlMapping', null, 'xmlFeeds');
        $local_field = $rlXmlFeeds->adaptRequestField($request_field);
        $field_info = $rlXmlMapping->getFieldInfo($local_field);
        if (!$parent_id) {
            $sql = "SELECT `ID` FROM `{db_prefix}xml_mapping` WHERE `Format` = '{$_GET['format']}' AND `Data_local` = '{$local_field}'";
            $parent_id = $rlDb->getRow($sql, "ID");
        }
    }

    if ($parent_id) {
        $sql ="SELECT SQL_CALC_FOUND_ROWS * FROM `{db_prefix}xml_mapping` WHERE `Parent_ID` = '{$parent_id}' ";
        $sql .="AND `Format` = '{$format}' ";
        if ($sort) {
            $sql .= "ORDER BY {$sortField} {$sortDir} ";
        }
        $sql .= "LIMIT {$start},{$limit}";

        $data = $rlDb->getAll($sql);
        $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");
    }

    if ($field_info['Type'] == 'price') {
        $field_info['Condition'] = 'currency';
    }

    foreach ($data as $key => &$value) {
        $value['Status'] = $lang[$value['Status']];

        if ($data[$key]['Data_local']) {
            if ($request_field == 'sys_category') {
                $value['Data_local'] = $lang["categories+name+" . $value['Data_local']];
                $value['Build_url'] = "field=sys_category&parent=" . $value['ID'];
            } elseif ($request_field == 'sys_dealer_id') {
                $data[$key]['Data_local'] = $rlDb->getOne("Username", "`ID` = '" . $value['Data_local'] . "'", "accounts");
            } elseif ($request_field == 'sys_lang_codes') {
                $lang_pName = 'languages+name+' . $rlDb->getOne('Key', "`Code` = '{$value['Data_local']}'", "languages");
                $value['Data_local'] = $lang[$lang_pName];
                if (!$value['Data_local']) {
                    $value['Data_local'] = $rlDb->getOne("Value", "`Key` = '{$lang_pName}'", "lang_keys");
                }
            }

            if ($field_info) {
                if ($field_info['Condition']) {
                    $value['pName'] = 'data_formats+name+' . $value['Data_local'];
                } else {
                      $value['pName'] = 
                     'listing_fields+name+' . $value['Data_local']
                    && $lang['listing_fields+name+' . $value['Data_local']]
                    ? 'listing_fields+name+' . $value['Data_local']
                    : 'listing_fields+name+' . $field_info['Key'] . "_" . $value['Data_local'];
                }
                
                if ($value['name']) {
                    $value['Data_local'] = $value['name'];
                } elseif ($value['pName'] && $lang[$value['pName']]) {
                    $value['Data_local'] = $lang[$value['pName']];
                } elseif ($value['pName'] && !$lang[$value['pName']]) {
                    $lang[$value['pName']] = $rlDb->getOne("Value", "`Key` = '{$value['pName']}' AND `Code` = '".RL_LANG_CODE."'", "lang_keys");
                    if ($lang[$value['pName']]) {
                        $value['Data_local'] = $lang[$value['pName']];
                    }
                }
            }
        }
    }

    $output['total'] = (int) $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
}
/* ext js action end */

else {
    $rlListingTypes->get(true);

    $file_types = ['xml', 'json'];
    $rlSmarty->assign('file_types', $file_types);

    /* help section */
    if (!$_GET['action']) {
        $format = $rlDb->fetch(array('Key'), array('Status' => 'active'), "ORDER BY `Key`", null, 'xml_formats', "row");

        if (!$format) {
            $info[] = $lang['xf_help_no_format'];
        }

        $rlSmarty->assign('formats_mode', $formats_mode);
    } elseif ($_GET['action'] == 'export') {
        $info[] = $lang['xf_help_export'];
    } elseif ($_GET['action'] == 'mapping') {
        $ref_mapping_field = $rlDb->getOne("ID", "`Format` = '" . $_GET['format'] . "' AND `Data_local` = 'sys_xml_ref'", "xml_mapping");

        if (!$ref_mapping_field) {
            $info[] = $lang['xf_help_xml_ref_mapping'];
        }

        if ($_GET['field']) {
            $info[] = $lang['xf_help_select_field_mapping'];
        }
    }
    $rlSmarty->assign("info", $info);
    /* help section end */

    /* additional bread crumb step */
    switch ($_GET['action']){
        case 'add_feed':
            $bcAStep = $lang['xf_add_feed'];
            break;
        case 'add_format':
            $bcAStep = $lang['xf_add_format'];
            break;
        case 'edit_feed':
            $bcAStep = $lang['xf_edit_feed'];
            break;
        case 'edit_format':
            $bcAStep = $lang['xf_edit_format'];
            break;
        case 'import_file':
            $bcAStep = $lang['xf_import_file'];
            break;
        case 'export':
            $bcAStep = $lang['xf_export'];
            break;
        case 'mapping':
            $bread_crumbs[0]['Controller'] = 'xml_feeds';
            $bread_crumbs[0]['Vars'] = 'mode=formats';
            $bread_crumbs[0]['name'] = $lang['xf_manage_formats'];

            $bread_crumbs[1]['name'] = $rlDb->getOne('Name', "`Key` = '{$_GET['format']}'", 'xml_formats');

            if ($_GET['field']) {
                $bread_crumbs[1]['Controller'] = 'xml_feeds';
                $bread_crumbs[1]['Vars'] = 'mode=formats&amp;action=mapping&amp;format=' . $_GET['format'];

                if ($_GET['parent']) {
                    $bread_crumbs[1]['Vars'] .= "&field=" . $_GET['field'];
                }

                if (is_numeric(strpos($_GET['field'], 'mf|'))) {
                    $field_name = $lang['listing_fields+name+' . str_replace('mf|','', $_GET['field'])];
                } else {
                    $field_name = $lang['listing_fields+name+' . $_GET['field']];
                }

                $bread_crumbs[2]['name'] = $field_name;

                if ($_GET['parent']) {
                    $bread_crumbs[2]['Controller'] = 'xml_feeds';
                    $bread_crumbs[2]['Vars'] = 'mode=formats&amp;action=mapping&amp;format=' . $_GET['format'] . '&field=' . $_GET['field'];

                    $parent_item_info = $rlDb->fetch("*", array('ID'=>$_GET['parent']), null, null, "xml_mapping", "row");
                    $bread_crumbs[3]['name'] = $parent_item_info['Data_remote'];
                }
            }

            $bcAStep = $bread_crumbs;
            break;
    }

    if (!$_GET['action']) {
        $mode = $_GET['mode'] ? $_GET['mode'] : 'feeds';
        
        switch ($mode) {
            case 'feeds':
                $bcAStep = $lang['xf_manage_feeds'];
            break;
            case 'formats':
                $bcAStep = $lang['xf_manage_formats'];
            break;
            case 'users':
                $bcAStep = $lang['xf_manage_users'];
            break;
        }
    }

    $reefless->loadClass('XmlFeeds', null, 'xmlFeeds');

    if ($_GET['action'] == 'add_feed' || $_GET['action'] == 'edit_feed') {
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);
        
        $sql = "SELECT `Key`, `Name` FROM `{db_prefix}xml_formats` ";
        $sql .="WHERE `Status` = 'active' AND FIND_IN_SET('import', `Format_for`) ";
        $sql .="ORDER BY `Key`";
        $formats = $rlDb->getAll($sql);

        $rlSmarty->assign_by_ref('formats', $formats);

        $reefless->loadClass("Account");
        $account_types = $rlAccount->getAccountTypes();
        $rlSmarty->assign("account_types", $account_types);
        
        /* get accounts */
        $accounts_list = $rlDb->fetch(array('ID', 'Username'), array('Status' => 'active'), null, null, 'accounts');
        $rlSmarty->assign_by_ref('accounts', $accounts_list);

        $plans = $rlDb->fetch(array('ID', 'Key'), array( 'Status' => 'active' ) , null, null, 'listing_plans');
        $plans = $rlLang->replaceLangKeys($plans, 'listing_plans', 'name', RL_LANG_CODE, 'admin');
        $rlSmarty->assign_by_ref('plans', $plans);

        if ($_GET['action'] == 'edit_feed' && !$_POST['fromPost']) {
            $f_key = $rlValid->xSql($_GET['feed']);
            $item_info = $rlDb->fetch("*", array('Key'=>$f_key), "AND `Status` <> 'trash'", null, 'xml_feeds', 'row');
        }

        $listing_type = $item_info['Listing_type'] ? $rlListingTypes->types[$item_info['Listing_type']] : current($rlListingTypes -> types);
        $listing_type = $listing_type['Key'];

        $rlSmarty->assign('listing_types', $rlListingTypes->types);
        $rlSmarty->assign('listing_type', $listing_type);

        if ($_GET['action'] == 'edit_feed' && !$_POST['fromPost']) {
            $_POST['key'] = $item_info['Key'];
            $_POST['name'] = $item_info['Name'];
            $_POST['file_type'] = $item_info['Type'];
            $_POST['url'] = $item_info['Url'];
            $_POST['format'] = $item_info['Format'];
            $_POST['plan_id'] = $item_info['Plan_ID'];
            $_POST['listings_status'] = $item_info['Listings_status'];
            $_POST['removed_ads_action'] = $item_info['Removed_ads_action'];
            $_POST['listing_type'] = $item_info['Listing_type'];
            $_POST['account_id'] = $item_info['Account_ID'];
            $_POST['default_category'] = $item_info['Default_category'];
            $_POST['skip_imported'] = $item_info['Skip_imported'];
            $_POST['update_photos'] = $item_info['Update_photos'];
            $_POST['run'] = $item_info['Run'];
            $_POST['delayed_photos'] = $item_info['Delayed_photos'];
            $_POST['not_delayed_photos'] = $item_info['Not_delayed_photos'];
            $_POST['import_limit'] = $item_info['Import_limit'];
            $_POST['access_method'] = $item_info['Access_method'];
            $_POST['http_auth'] = $item_info['Http_auth'];
            $_POST['http_auth_login'] = $item_info['Http_auth_login'];
            $_POST['http_auth_pass'] = $item_info['Http_auth_pass'];
            $_POST['status'] = $item_info['Status'];
        }
        
        if (isset($_POST['submit'])) {
            $errors = array();

            /* load the utf8 lib */
            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

            if ($_GET['action'] == 'add_feed' || $_GET['action'] == 'edit_feed') {
                $f_key = $_POST['name'];

                if (!utf8_is_ascii($f_key)) {
                    $f_key = utf8_to_ascii($f_key);
                }
                $f_key = $rlValid->str2key($f_key);
                $f_xpath = trim($_POST['xpath'], "/");

                $f_url = $_POST['url'];
                $f_format = $_POST['format'];

                /* check names */
                $f_name = $_POST['name'];
                if (!$f_name) {
                    $errors[] = str_replace('{field}', '<b>' . $lang['name'] . '</b>', $lang['notice_field_empty']);
                    $error_fields[] = 'name';
                }

                if (!$rlValid->isUrl($f_url)) {
                    $errors[] = $lang['xf_notice_url_incorrect'];
                    $error_fields[] = 'url';
                }

                if ($_POST['create_format'] && !$_POST['format_xpath']) {
                    $errors[] = str_replace('{field}', '<b>' . $lang['xf_xpath'] . '</b>', $lang['notice_field_empty']);
                    $error_fields[] = 'format_xpath';
                }

                if (empty($f_format) && !$_POST['create_format']){
                    $errors[] = $lang['xf_notice_format_empty'];
                    $error_fields[] = 'format';
                }
 
                if (empty($_POST['plan_id']) && (!$config['membership_module'] || $config['allow_listing_plans'])) {
                    $errors[] = str_replace('{field}', '<b>'.$lang['listing_package'].'</b>', $lang['notice_field_empty']);
                    $error_fields[] = 'plan_id';
                }

                if (empty($_POST['account_id'])) {
                    $errors[] = str_replace('{field}', '<b>'.$lang['account'].'</b>', $lang['notice_field_empty']);
                    $error_fields[] = 'account_id';
                }

                if (empty($_POST['default_category'])) {
                    $errors[] = str_replace('{field}', '<b>'.$lang['xf_default_category'].'</b>', $lang['notice_field_empty']);
                    $error_fields[] = 'default_category';
                }

                if (empty($_POST['listing_type']) && count($rlListingTypes -> types) > 1) {
                    $errors[] = str_replace('{field}', '<b>'.$lang['xf_listing_type'].'</b>', $lang['notice_field_empty']);
                    $error_fields[] = 'listing_type';
                }

                if ($_POST['import_limit'] && $_POST['import_limit'] < 0) {
                    $errors[] = str_replace('{field}', '<b>' . $lang['xf_import_limit'] . '</b>', $lang['notice_field_incorrect']);
                    $error_fields[] = 'import_limit';
                }

                if ($_POST['not_delayed_photos'] && $_POST['not_delayed_photos'] < 0) {
                    $errors[] = str_replace('{field}', '<b>' . $lang['xf_not_delayed_photos_number'] . '</b>', $lang['notice_field_incorrect']);
                    $error_fields[] = 'not_delayed_photos';
                }

                if ($_GET['action'] == 'add_feed') {
                    /* check key exist (in add mode only) */
                    if (strlen($f_key) < 3) {
                        $errors[] = $lang['incorrect_phrase_key'];
                        $error_fields[] = 'key';
                    }

                    $exist_key = $rlDb->fetch(array('Key'), array('Key' => $f_key), null, null, 'xml_feeds');
                    if (!empty($exist_key)) {
                        $errors[] = str_replace('{key}', "<b>\"".$f_key."\"</b>", $lang['notice_key_exist']);
                        $error_fields[] = 'key';
                    }

                    $exist_url = $rlDb->fetch(array('ID'), array('Url' => $f_url), null, null, 'xml_feeds');
                    if (!empty($exist_key)) {
                        $errors[] = $lang['xf_notice_url_exist'];
                        $error_fields[] = 'url';
                    }
                }
            }

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                /* add/edit action */
                if ($_GET['action'] == 'add_feed') {
                    if ($_POST['create_format'] && $_POST['format_xpath']) {
                        $f_xpath = trim($_POST['format_xpath']);

                        if (!($f_xpath == '/' && $_POST['file_type'] == 'json')) {
                            $f_xpath = trim($f_xpath, '/');
                        }

                        $f_format = $f_key . "_format";
                        $data = array(
                            'Key' => $f_format,
                            'Name' => $f_name . ' ' . $lang['xf_format'],
                            'Status' => $_POST['status'],
                            'Format_for' => "import",
                            'Xpath' => $f_xpath
                        );

                        $rlDb->insertOne($data, 'xml_formats');
                    }

                    $data = array(
                        'Key' => $f_key,
                        'Name' => $f_name,
                        'Type' => $_POST['file_type'],
                        'Url' => $f_url,
                        'Plan_ID' => $_POST['plan_id'],
                        'Default_category' => $_POST['default_category'],
                        'Skip_imported' => $_POST['skip_imported'],
                        'Update_photos' => $_POST['update_photos'],
                        'Run' => $_POST['run'],
                        'Import_limit' => $_POST['import_limit'],
                        'Delayed_photos' => $_POST['delayed_photos'],
                        'Not_delayed_photos' => $_POST['not_delayed_photos'],
                        'Format' => $f_format,
                        'Listings_status' => $_POST['listings_status'],
                        'Removed_ads_action' => $_POST['removed_ads_action'],
                        'Listing_type' => $_POST['listing_type'],
                        'Access_method' => $_POST['access_method'],
                        'Http_auth' => $_POST['http_auth'],
                        'Http_auth_login' => $_POST['http_auth_login'],
                        'Http_auth_pass' => $_POST['http_auth_pass'],
                        'Status' => $_POST['status'],
                        'Account_ID' => $_POST['account_id']
                    );

                    if ($action = $rlDb->insertOne($data, 'xml_feeds')) {
                        $message = $lang['item_added'];
                        $aUrl = array("controller" => $controller, 'mode' => 'feeds');
                    }
                } elseif ($_GET['action'] == 'edit_feed') {
                    $f_key = $_GET['feed'];

                    $update_data = array(
                        'fields' => array(
                            'Name' => $f_name,
                            'Type' => $_POST['file_type'],
                            'Status' => $_POST['status'],
                            'Format' => $f_format,
                            'Listings_status' => $_POST['listings_status'],
                            'Removed_ads_action' => $_POST['removed_ads_action'],
                            'Default_category' => $_POST['default_category'],
                            'Skip_imported' => $_POST['skip_imported'],
                            'Update_photos' => $_POST['update_photos'],
                            'Run' => $_POST['run'],
                            'Import_limit' => $_POST['import_limit'],
                            'Delayed_photos' => $_POST['delayed_photos'],
                            'Not_delayed_photos' => $_POST['not_delayed_photos'],
                            'Listing_type' => $_POST['listing_type'],
                            'Url' => $f_url,
                            'Plan_ID' => $_POST['plan_id'],
                            'Default_category' => $_POST['default_category'],
                            'Access_method' => $_POST['access_method'],
                            'Http_auth' => $_POST['http_auth'],
                            'Http_auth_login' => $_POST['http_auth_login'],
                            'Http_auth_pass' => $_POST['http_auth_pass'],
                            'Account_ID' => $_POST['account_id']
                        ),
                        'where' => array('Key' => $f_key)
                    );

                    if ($action = $rlDb->updateOne($update_data, 'xml_feeds')) {
                        $message = $lang['item_edited'];
                        $aUrl = array("controller"=>$controller);
                    }
                }
                
                if ($action) {
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                }
            }
        }
    } elseif ($_GET['action'] == 'add_format' || $_GET['action'] == 'edit_format') {
        $allLangs = $GLOBALS['languages'];
        $rlSmarty -> assign_by_ref('allLangs', $allLangs);
        
		if ($_GET['action'] == 'edit_format' && !$_POST['fromPost']) {
            $f_key = $rlValid->xSql($_GET['format']);

            $item_info = $rlDb->fetch("*", array('Key' => $f_key), "AND `Status` <> 'trash'", null, 'xml_formats', 'row');

            $_POST['key'] = $item_info['Key'];
            $_POST['name'] = $item_info['Name'];
            $_POST['status'] = $item_info['Status'];
            $_POST['xpath'] = $item_info['Xpath'];
            $format_for = explode(",", $item_info['Format_for']);
            $_POST['format_for'] = $format_for;
        }

        if (isset($_POST['submit'])) {
            $errors = array();

            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

            $f_name = $_POST['name'];

            if ($_GET['action'] == 'add_format') {
                $f_key = $f_name;

                if (!utf8_is_ascii($f_key)) {
                    $f_key = utf8_to_ascii($f_key);
                }
                $f_key = $rlValid->str2key($f_key);

                if (strlen($f_key) < 3) {
                    $errors[] = $lang['incorrect_phrase_key'];
                    $error_fields[] = 'key';
                }

                $exist_key = $rlDb->fetch(array('Key'), array('Key'=>$f_key), null, null, 'xml_formats');
                if (!empty($exist_key)) {
                    $errors[] = str_replace('{key}', "<b>\"" . $f_key . "\"</b>", $lang['notice_key_exist']);
                    $error_fields[] = 'key';
                }

                if (!$f_name) {
                    $errors[] = str_replace('{field}', "<b>{$lang['name']}</b>", $lang['notice_field_empty']);
                    $error_fields[] = 'name';
                }
            }

            if (!$_POST['format_for']) {
                $errors[] = str_replace('{field}', '<b>' . $lang['xf_format_for'] . '</b>', $lang['notice_field_empty']);
                $error_fields[] = 'format';
            } else {
                $format_for = implode(",", array_values($_POST['format_for']));
            }

            $f_xpath = trim($_POST['xpath']);

            if (!in_array($f_xpath, ['/', 'array'])) {
                $f_xpath = trim($f_xpath, "/");

                if (empty($f_xpath)) {
                    $errors[] = str_replace('{field}', '<b>' . $lang['xf_xpath'] . '</b>', $lang['notice_field_empty']);
                    $error_fields[] = 'xpath';
                }
            }

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else  {
                /* add/edit action */
                if ($_GET['action'] == 'add_format') {
                    $data = array(
                        'Key' => $f_key,
                        'Name' => $f_name,
                        'Status' => $_POST['status'],
                        'Format_for' => $format_for,
                        'Xpath' => $f_xpath
                    );

                    if ($action = $rlDb->insertOne($data, 'xml_formats')) {
                        $message = $lang['xf_added_need_build'];
                        $aUrl = array("controller" => $controller, 'mode' => 'formats');
                    }
                } elseif ($_GET['action'] == 'edit_format') {
                    $f_key = $_GET['format'];

                    $update_data = array(
                        'fields' => array(
                            'Name' => $f_name,
                            'Status' => $_POST['status'],
                            'Format_for' => $format_for,
                            'Xpath' => $f_xpath
                        ),
                        'where' => array('Key'=>$f_key)
                    );
                    
                    if ($action = $rlDb->updateOne($update_data, 'xml_formats')) {
                        $message = $lang['notice_item_edited'];
                        $aUrl = array("controller" => $controller, 'mode' => 'formats');
                    }
                }

                if ($action) {
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                }
            }
        }
    } elseif ($_GET['action'] == 'statistics') {
        if (!$_POST['xjxfun']) {
            unset($_SESSION['xmlFeedsImport']);
        }
        
        $feed = $_GET['feed'];

        $feed_info = $rlDb->fetch("*", array("Key" => $feed), null, null, "xml_feeds", "row");
        $plan_key = $rlDb->getOne("Key", "`ID` = '".$feed_info['Plan_ID']."'", "listing_plans");
        $feed_info['Plan_name'] = $lang['listing_plans+name+'.$plan_key];
        $feed_info['Username'] = $rlDb->getOne("Username", "`ID` = '".$feed_info['Account_ID']."'", "accounts");
        
        $category_key = $rlDb->getOne("Key", "`ID` = '{$feed_info['Default_category']}'", "categories");
        $feed_info['Category_name'] = $rlLang->getSystem('categories+name+' . $category_key);
        $rlSmarty->assign_by_ref('feed_info', $feed_info);

        $sql = "SELECT *, `T1`.`Key` as `Key` FROM `{db_prefix}xml_formats` AS `T1` ";
        $sql .="JOIN `{db_prefix}xml_feeds` AS `T2` ON `T2`.`Format` = `T1`.`Key` ";
        $sql .="WHERE `T2`.`Key` = '{$feed}'";

        $format_info = $rlDb->getRow($sql);
        $rlSmarty->assign('format_info', $format_info);

        $feed_info['Format_name'] = $format_info['Name'];
        
        $sql = "SELECT `T1`.*, `T2`.`Username` ";
        $sql .="FROM `{db_prefix}xml_statistics` AS `T1` ";
        $sql .="LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T2`.`ID` = `T1`.`Account_ID` ";
        $sql .= "WHERE `T1`.`Feed` = '{$feed}'";

        if ($_GET['account_id']) {
            $sql .= "AND `T1`.`Account_ID` = '{$_GET['account_id']}'";
            
            $account_username = $rlDb->getOne('Username', '`ID` = ' . $_GET['account_id'], 'accounts');
            $rlSmarty->assign('account_username', $account_username);
        }
        $sql .="ORDER BY `Date` DESC ";

        $data = $rlDb->getAll($sql);

        $bcAStep = $feed_info['Name'];
        $rlSmarty->assign('statistics', $data);

        $rlXajax->registerFunction(array('clearStatistics', $rlXmlFeeds, 'ajaxClearStatistics'));
        $rlXajax->registerFunction(array('runFeed', $rlXmlFeeds, 'ajaxRunFeed'));

    } elseif ($_GET['action'] == 'mapping' && $_GET['format']) {
        $request_field = $_GET['field'];

        if ($request_field) {
            $system_field = substr($request_field, 0, 4) == 'sys_' ? substr($request_field,4) : false;
            if ($system_field) {
                if ($system_field == 'category') {
                    $local_field = 'sys_category_0';
                    $category_field = true;
                } else {
                    $local_field = $request_field;
                }
            } else {
                $local_field = $request_field;
            }

            $sql ="SELECT `T1`.`Data_local` ";
            if (!$system_field) {
                if ($GLOBALS['plugins']['multiField']) {
                    $sql .=", `T3`.`Key` AS `Mf` ";
                }
                $sql .=", `T2`.* ";
            }
            $sql .=" FROM `{db_prefix}xml_mapping` AS `T1` ";
            if (!$system_field) {
                $sql .="JOIN `{db_prefix}listing_fields` AS `T2` ON `T2`.`Key` = `T1`.`Data_local` ";
                if ($GLOBALS['plugins']['multiField']) {
                    $sql .="LEFT JOIN `{db_prefix}multi_formats` AS `T3` ON `T2`.`Condition` = `T3`.`Key`";
                }
            }
            $sql .="WHERE ";
            $sql .="`T1`.`Data_local` = '{$local_field}' ";

            $local_field_info = $rlDb->getRow($sql);

            if (!$local_field_info) {
                echo 'something wrong';
                exit;
            }
            $rlSmarty->assign('local_field_info', $local_field_info);

            if ($local_field_info['Type'] == 'price') {
                $local_field_info['Condition'] = 'currency';
                $local_field_info['Type'] = 'mixed';
            }

            if ($system_field) {
                if ($category_field) {
                    $parent_id = $_GET['parent'];

                    //when category added as default (not exist in feed but necessary)
                    if ($parent_id) {
                        $sql ="SELECT `T1`.* FROM `{db_prefix}xml_mapping` AS `T1` ";
                        $sql .="WHERE `T1`.`ID` = ".$_GET['parent'];
                        $parent_cat_info = $rlDb->getRow($sql);
                        
                        if ($parent_cat_info['Default'] && !$parent_cat_info['Data_local']) {
                            // $sql = "SELECT * FROM `{db_prefix}categories` AS `T1` ";
                            // $sql .="LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON `T2`.`Key` = CONCAT('categories+name+', `T1`.`Key`) ";
                            // $sql .="WHERE `T2`.`Value` = '{$parent_cat_info['Default']}'";
                            // $cat_info = $rlDb->getRow($sql);
                        } elseif ($parent_cat_info['Data_local']) {
                            $sql = "SELECT * FROM `{db_prefix}categories` AS `T1` ";
                            $sql .="WHERE `T1`.`Key` = '{$parent_cat_info['Data_local']}'";

                            $cat_info = $rlDb->getRow($sql);
                        }
                    } else {
                        $sql ="SELECT `T3`.`ID` FROM `{db_prefix}xml_mapping` AS `T1` ";                    
                        $sql .="JOIN `{db_prefix}lang_keys` AS `T2` ON `T2`.`Value` = `T1`.`Default` ";                    
                        $sql .="JOIN `{db_prefix}categories` AS `T3` ON `T2`.`Key` = CONCAT('categories+name+', `T3`.`Key`) ";
                        $sql .="WHERE `T1`.`Data_local` = 'sys_category_0' ";

                        $cat_info = $rlDb->getRow($sql);
                    }

                    $local_values = array();

                    if (!$cat_info) {
                        $sql = "SELECT `Listing_type` FROM `{db_prefix}xml_feeds` ";
                        $sql .="WHERE `Format` = '{$_GET['format']}'";

                        $format_feed_type = $rlDb->getRow($sql, 'Listing_type');

                        $cats_tree = $rlCategories->getCatTree(0, $format_feed_type);
                    } else {
                        $cats_tree = $rlCategories->getCatTree($cat_info['ID']);
                    }
                    
                    foreach($cats_tree as $key => $value) {
                        $local_values[$key]['Key'] = $value['Key'];
                        $local_values[$key]['name'] = $value['name'];
                        $local_values[$key]['Level'] = $value['Level'];
                    }
                    $local_values = $rlValid->xSql($local_values);
                } elseif ($local_field_info['Data_local'] == 'sys_dealer_id') {
                    $account_list = $rlDb->fetch(array("Username", "ID"), array("Status" => "active"), "ORDER BY `Username`", null, "accounts");

                    foreach ($account_list as $key => $account) {
                        $local_values[$key]['name'] = $account['Username'];
                        $local_values[$key]['Key'] = $account['ID'];
                    }
                } elseif ($local_field_info['Data_local'] == 'sys_status') {
                    $statuses = array('approval', 'active', 'pending', 'incomplete', 'expired');
                    foreach ($statuses as $key => $status) {
                        $local_values[$key]['name'] = $lang[$status];
                        $local_values[$key]['Key'] = $status;
                    }
                } elseif ($local_field_info['Data_local'] == 'sys_lang_codes') {
                	$langs_list = $rlLang->getLanguagesList('all');
                	$k = 0;
                    foreach ($langs_list as $key => $lang_item) {
                        $local_values[$k]['name'] = $lang_item['name'];
                        $local_values[$k]['name'] .= ' (' . ($lang_item['Locale'] ?: $lang_item['Code']) . ')';
                        
                        $local_values[$k]['Key'] = $lang_item['Code'];
                        $k++;
                    }
                }
                $rlSmarty->assign('local_values', $local_values);

            } else {
                if ($local_field_info['Mf'] && $_GET['parent']) {
                    //preg_match('/(.*)_level([0-9])/', $local_field_info['Key'], $match);
                    $sql ="SELECT `T2`.`Key` FROM `{db_prefix}xml_mapping` AS `T1` ";
                    $sql .="JOIN `{db_prefix}data_formats` AS `T2` ON `T2`.`Key` = `T1`.`Data_local` ";
                    $sql .="WHERE `T1`.`ID` = ".$_GET['parent'];

                    $item_info = $rlDb->getRow($sql);

                    $data = $GLOBALS['rlMultiField']->getMDF($item_info ? $item_info['Key'] : $local_field_info['Condition']);
                    
                    $local_values = array();
                    foreach ($data as $key => $value) {
                        $k++;
                        $local_values[$k]['Key'] = $value['Key'];
                        $local_values[$k]['name'] = $value['name'];
                    }
                } else {
                    $local_values_tmp = $rlCommon->fieldValuesAdaptation(array(0 => $local_field_info), "listing_fields");

                    foreach($local_values_tmp[0]['Values'] as $key => $value) {
                        if (!$local_field_info['Condition']
                         && ($local_field_info['Type'] == 'select' || $local_field_info['Type'] == 'checkbox')) {
                            if(!$value['Key']) {
                                $value['Key'] = $key;
                            }
                            
                            $local_values[$key]['Key'] = str_replace($local_field_info['Key']."_", '', $value['Key']);
                        } else {
                            $local_values[$key]['Key'] = $value['Key'];
                        }
                        $local_values[$key]['name'] = $value['name'] ? $value['name'] : $lang[ $value['pName'] ];
                    }
                }

                $local_values = $rlValid->xSql($local_values);
                $rlSmarty->assign('local_values', $local_values);
            }

            $rlXajax->registerFunction(array('copyMappingItem', $rlXmlFeeds, 'ajaxCopyMappingItem'));
            $rlXajax->registerFunction(array('deleteMappingItem', $rlXmlFeeds, 'ajaxDeleteMappingItem'));
        } else {
            $sql ="
                SELECT * FROM `{db_prefix}listing_fields`
                WHERE `Status` = 'active'
                AND `Key` != 'Category_ID' AND `Key` != 'text_search' AND `ID` > 0
            ";
            $fields = $rlDb->getAll($sql);

            $fields = $rlLang->replaceLangKeys($fields, 'listing_fields', 'name', RL_LANG_CODE);
            
            foreach ($fields as $key => $field) {
                $out[$key]['Key'] = $field['Key'];
                $out[$key]['Type_name'] = $lang['type_'.$field['Type']];
                $out[$key]['name'] = $field['name'];

                if ($field['Type'] == 'mixed' || $field['Type'] == 'price') {
                    $measurement_fields[] = $field;
                }
            }
    
            foreach ($measurement_fields as $mfk => $mfv) {
                $mfkey = $key + $mfk + 1;
                $out[$mfkey]['Key'] = $mfv['Key'] . "_unit";
                $out[$mfkey]['name'] = $mfv['name'] . " " . $lang['xf_type_unit'];

                if ($mfv['Condition']) {
                    $out[$mfkey]['Type_name'] = $lang['data_formats+name+' . $mfv['Condition']];
                } elseif ($mfv['Type'] == 'price') {
                    $out[$mfkey]['Type_name'] = $lang['data_formats+name+currency'];
                } else {
                    $out[$mfkey]['Type_name'] = $lang['listing_fields+name+' . $mfk['Key']];
                }
            }

            Util::arraySort($out, 'name');

            $rlSmarty->assign_by_ref('listing_fields', $out);

            $sys_fields = [
                'sys_xml_ref',
                'sys_xml_back_url',
                'sys_photos',
                'sys_loc_latitude',
                'sys_loc_longitude',
                'sys_dealer_id',
                'sys_video',
                'sys_date',
                'sys_status',
                'sys_explode_comma',
                'sys_duplicate'
            ];

            /*TODO:create accounts automatically
            $config['create_dealers_automatically'] = true;
            if ($config['create_dealers_automatically']) {
                $sys_fields[] = 'sys_dealer_email';
                $sys_fields[] = 'sys_dealer_name';
                $sys_fields[] = 'sys_dealer_last_name';
            }*/

            if (count($languages) > 1) {
				$ml_field = $rlDb->getOne("Key", 
					"`Type` = 'textarea' AND `Multilingual` = '1' AND `Status` = 'active'", 
					"listing_fields"
					);
			
                if ($ml_field) {                    
                    $sys_lang_code_field = $rlDb->getOne("ID", 
                        "`Format` = '{$_GET['format']}' AND `Status` = 'active' AND `Data_local` = 'sys_lang_codes' AND `Data_remote` != ''",
                        "xml_mapping"
                        );
                    
                    if (!$sys_lang_code_field) {
                        $extra = array();
                        foreach ($out as $key => $field) {
                            if ($field['Key'] == $ml_field) {
                                unset($out[$key]);
                                foreach ($languages as $lk => $language) {
                                    $extra_field = $field;
                                    $extra_field['Key']  = 'sys_mld_' . $field['Key'] . '_' . $language['Code'];
                                    $extra_field['name'] = $field['name'] . '(' . $language['name'] . ')';
                                    $extra[] = $extra_field;
                                }
                            }
                        }
                        
                        if ($extra) {
                            $out = array_merge($out, $extra);
                        }

                        foreach ($extra as $key => $extra_desc_field) {
                            $existing_extra_field = $rlDb->getOne("ID", 
                            "`Format` = '{$_GET['format']}' AND `Status` = 'active' AND `Data_local` = '{$extra_desc_field['Key']}' AND `Data_remote` != ''",
                            "xml_mapping"
                            );
                            if ($existing_extra_field) {
                                break;
                            }
                        }

                        if (!$existing_extra_field) {
                            $sys_fields[] = 'sys_lang_codes';
                        }
                    }                    
                }
		    }

            //$sys_fields[] = 'sys_plan_id';
            //$sys_fields[] = 'sys_separate_numeric_and_text';

            foreach($sys_fields as $k => $sf) {
                $sys_out[$k]['Key'] = $sf;
                $sys_out[$k]['name'] = $lang['xf_' . $sf];
            }

            $max_level = $rlDb->getOne("Level", "`Status` = 'active' ORDER BY `Level` DESC", 'categories');
            for ($i = 0; $i <= $max_level; $i++) {
                $k++;
                $sys_out[$k]['Key'] = 'sys_category_' . $i;
                $sys_out[$k]['name'] = $lang['category'] . " Level " . $i;
            }
            
            $rlSmarty->assign('system_fields', $sys_out);
            /* system fields building end */
        }

        $rlXajax->registerFunction(array('addMappingItem', $rlXmlFeeds, 'ajaxAddMappingItem'));
        $rlXajax->registerFunction(array('deleteMappingItem', $rlXmlFeeds, 'ajaxDeleteMappingItem'));
        $rlXajax->registerFunction(array('clearMapping', $rlXmlFeeds, 'ajaxClearMapping'));
    } elseif ($_GET['action'] == 'export') {
        
        $sql ="SELECT `Key`, `Name` FROM `{db_prefix}xml_formats` ";
        $sql .="WHERE `Status` = 'active' AND FIND_IN_SET('export', `Format_for`) ";
        $sql .="ORDER BY `Key` ";
        $formats = $rlDb->getAll($sql);
        $rlSmarty->assign_by_ref('formats', $formats);

        $listing_type = current($rlListingTypes -> types);
        $listing_type = $listing_type['Key'];

        $rlSmarty->assign('listing_types', $rlListingTypes->types);
        $rlSmarty->assign('listing_type', $listing_type);
        
        $htaccess = RL_ROOT . ".htaccess";
        $htaccess_cont = file_get_contents($htaccess);
        
        preg_match('/RewriteRule \^([^$]*)\$ plugins\/xmlFeeds\/export.php\?format=\$1\&\$2 \[QSA\,L\]/smi', $htaccess_cont, $match);
        
        if ($match) {
            $rewrite_cond = $match[1];
            $rewrite_cond = str_replace('([^-]*)', '[format]', $rewrite_cond);
            $rewrite_cond = str_replace('(.*)', '[params]', $rewrite_cond);
            
            $rlSmarty->assign('rewrite', $rewrite_cond);
        } else {
            $default_rewrite = '[format]-feed.xml[params]';
            $rlSmarty -> assign('default_rewrite', $default_rewrite);
        }
        $rlXajax->registerFunction(array('applyRule', $rlXmlFeeds, 'ajaxApplyRule'));

    } elseif ($_GET['action'] == 'manual_import' && ($_GET['feed'] || $_GET['file'])) {
        include(RL_PLUGINS . "xmlFeeds" . RL_DS . "import.php");
        exit;
    }

    $sql ="SELECT `Key`, `Name` FROM `{db_prefix}xml_formats` ";
    $sql .="WHERE `Status` = 'active' AND FIND_IN_SET('import', `Format_for`) ";
    $sql .="ORDER BY `Key` ";
    $filter_formats = $rlDb->getAll($sql);
    $rlSmarty->assign_by_ref('filter_formats', $filter_formats);

    $rlXajax->registerFunction(array('deleteFeed', $rlXmlFeeds, 'ajaxDeleteFeed'));
    $rlXajax->registerFunction(array('deleteFormat', $rlXmlFeeds, 'ajaxDeleteFormat'));
    $rlXajax->registerFunction(array('deleteUser', $rlXmlFeeds, 'ajaxDeleteUser'));
}
