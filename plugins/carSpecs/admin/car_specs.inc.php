<?php
/**copyright**/

if ($_GET['q'] == 'ext_services') {
    /* system config */
    require_once('../../../includes/config.inc.php');
    require_once(RL_ADMIN_CONTROL . 'ext_header.inc.php');
    require_once(RL_LIBS . 'system.lib.php');
    
    /* date update */
    if ($_GET['action'] == 'update') {
        $reefless->loadClass('Actions');

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
        
        $rlActions->updateOne($updateData, 'car_specs_services');
    }
    
    /* data read */
    $limit = (int)$_GET['limit'];
    $start = (int)$_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);
    $key = $rlValid->xSql($_GET['key']);

    
    $sql = "SELECT SQL_CALC_FOUND_ROWS  `T1`.*, `T2`.`Value` as `name` ";
    $sql .="FROM `".RL_DBPREFIX."car_specs_services` AS `T1` ";
    $sql .="LEFT JOIN `".RL_DBPREFIX."lang_keys` AS `T2` ON CONCAT('car_specs_services+name+',`T1`.`Key`) = `T2`.`Key` AND `T2`.`Code` = '". RL_LANG_CODE ."' ";    
    $sql .="WHERE `T1`.`Status` <> 'trash' ";
   
    if ( $sort )
    {
        $sortField = $sort == 'name' ? "`T2`.`Value`" : "`T1`.`{$sort}`";
        $sql .= "ORDER BY {$sortField} {$sortDir} ";
    }

    $sql .= "LIMIT {$start},{$limit}";

    $data = $rlDb -> getAll( $sql );    

    foreach ( $data as $key => $value )
    {
        $data[$key]['Status'] = $lang[$value['Status']];
    }

    $count = $rlDb -> getRow( "SELECT FOUND_ROWS() AS `count`" );
    
    $reefless -> loadClass( 'Json' );
    
    $output['total'] = $count['count'];
    $output['data'] = $data;
    
    echo $rlJson -> encode( $output );

} elseif ($_GET['q'] == 'ext_mapping') {

    /* system config */
    require_once('../../../includes/config.inc.php');
    require_once(RL_ADMIN_CONTROL . 'ext_header.inc.php');
    require_once(RL_LIBS . 'system.lib.php');

    /* date update */
    if ($_GET['action'] == 'update') {
        $reefless->loadClass('Actions');

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

        $rlActions -> updateOne($updateData, 'car_specs_mapping');
    }
    if ($rlDb -> getOne("Key", "`Key`='multiField' AND `Status` = 'active'", "plugins")) {
        $multi_formats_tmp = $rlDb->fetch(array("Key"), null, null, null, "multi_formats");

        foreach ($multi_formats_tmp as $k => $v) {
            $mfs = $v['Key'] . ",";
        }

        if ($mfs) {
            $mfs = substr($mfs, 0, -1);
        }
    }

    /* data read */
    $limit = (int)$_GET['limit'];
    $start = (int)$_GET['start'];
    $sortField = $_GET['sort'] ? $rlValid->xSql( $_GET['sort'] ) : "ID";
    $sortDir = $_GET['dir'] ? $rlValid->xSql( $_GET['dir'] ) : "ASC";

    $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.*, `T2`.`Type` AS `Local_field_type`, `T3`.`Value` AS `Local_field_name`, `T1`.`Service`, ";
    $sql .="IF(FIND_IN_SET(`T2`.`Condition`, '" . $mfs . "'), 1, '') as `Mf` ";
    $sql .= "FROM `". RL_DBPREFIX ."car_specs_mapping` AS `T1` ";
    $sql .= "LEFT JOIN `".RL_DBPREFIX."listing_fields` AS `T2` ON `T1`.`Data_local` = `T2`.`Key` ";
    $sql .= "LEFT JOIN `".RL_DBPREFIX."lang_keys` AS `T3` ON `T3`.`Key` = CONCAT('listing_fields+name+', `T2`.`Key`) AND `T3`.`Code` = '" . RL_LANG_CODE . "' ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' ";

    if ($_GET['service']) {
        $sql .="AND `T1`.`Service` = '" . $_GET['service'] . "' ";
    }

    $sql .="AND `T1`.`Parent_ID` = 0 ";
    $sql .= "ORDER BY `Status` ASC, {$sortField} {$sortDir} ";
    $sql .= "LIMIT {$start},{$limit}";

    $data = $rlDb -> getAll( $sql );

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $lang[ $value['Status'] ];
        if (is_numeric(strpos($value['Data_local'], 'category_')) && strpos($value['Data_local'], 'category_') == 0) {
            $i = substr($value['Data_local'], -1, 1);
            $data[$key]['Local_field_name']  = $GLOBALS['lang']['category']." Level ".$i;
            $data[$key]['Local_field_type'] = "select";         
        } elseif( $value['Data_local'] == 'pictures' ) {
            $data[$key]['Local_field_name']  = $GLOBALS['lang']['cs_pictures'];
        } elseif( $value['Data_local'] == 'cs_base_field' ) {
            $data[$key]['Local_field_name']  = $GLOBALS['lang']['cs_base_field'];            
        } elseif( is_numeric(strpos($value['Data_local'], "_unit")) ) {
            preg_match("/^(.*)_unit$/smi", $value['Data_local'], $matches);
            if( $matches[1] )
            {
                $data[$key]['Local_field_name'] = $GLOBALS['lang']['listing_fields+name+'.$matches[1]]." ".$lang['cs_unit'];
            }
        }

        $data[$key]['Cdata'] = $data[$key]['Cdata'] ? $lang['yes'] : $lang['no'];
    }

    $count = $rlDb -> getRow( "SELECT FOUND_ROWS() AS `count`" );
    
    $reefless -> loadClass( 'Json' );
    
    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo $rlJson -> encode( $output );

} elseif ($_GET['q'] == 'ext_item_mapping') {
    /* system config */
    require_once('../../../includes/config.inc.php');
    require_once(RL_ADMIN_CONTROL . 'ext_header.inc.php');
    require_once(RL_LIBS . 'system.lib.php');

    /* date update */
    if ($_GET['action'] == 'update' ) {
        $reefless->loadClass('Actions');

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

        $rlActions -> updateOne($updateData, 'car_specs_mapping');
    }

    /* data read */
    $limit = (int)$_GET['limit'];
    $start = (int)$_GET['start'];
    $sort = $rlValid -> xSql($_GET['sort']);
    $sortDir = $rlValid -> xSql($_GET['dir']);


    if (trim($_GET['field']) == 'category') {
        $field = $rlDb->getOne("Data_remote", "`Data_local` = 'category_0' AND `Service` = '{$_GET['service']}'", "car_specs_mapping");
    } elseif(trim($_GET['field']) == 'xml_dealer_id') {
        $field = $rlDb -> getOne("Data_remote", "`Data_local` = 'xml_dealer_id' AND `Service` = '{$_GET['service']}'", "car_specs_mapping");
    } elseif(is_numeric(strpos($_GET['field'], 'mf|'))) {
        $local = trim(str_replace('mf|','', $_GET['field'] ));
        $field = $rlDb -> getOne("Data_remote", "`Data_local` = '".$local."' AND `Service` = '{$_GET['service']}'", "car_specs_mapping");
    } else {
        $field = trim($_GET['field']);
    }

    if ($_GET['parent']) {
        $dataLocal = $_GET['parent'];
    } elseif($field) {
        $serviceKey = $rlValid->xSql($_GET['service']);
        $sql = "SELECT `Data_local` FROM `" . RL_DBPREFIX . "car_specs_mapping` ";
        $sql .= "WHERE `Service` = '{$serviceKey}' AND `Data_remote` = '{$field}'";

        $mappingRow = $rlDb->getRow($sql);
        $dataLocal = $mappingRow['Data_local'];
    }

    if ($dataLocal) {
        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM `" . RL_DBPREFIX . "car_specs_mapping` ";
        $sql .= "WHERE `Data_local` = '{$dataLocal}' ";

        if ($sort) {
            $sql .= "ORDER BY {$sortField} {$sortDir} ";
        }
        $sql .= "LIMIT {$start},{$limit}";
        $data = $rlDb->getAll($sql);

        $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");
    }

    foreach ($data as $key => $value) {
        $removeValue = ucfirst(strtolower($value['Example_value']));
        $data[$key]['Example_value'] = $removeValue;
        $data[$key]['Status'] = $lang[$value['Status']];

        $sql = "SELECT `Key`, `ID`, `Condition` FROM `" . RL_DBPREFIX . "listing_fields` ";
        $sql .= "WHERE `Key` = '{$value['Data_local']}'";
        $localFieldInfo = $rlDb->getRow($sql);

        $sql = "SELECT `ID` FROM `" . RL_DBPREFIX . "lang_keys` WHERE `Value` = '{$removeValue}' ";
        if ($localFieldInfo['Condition']) {
            $newFieldKey = $rlValid->str2key(sprintf('%s %s', $localFieldInfo['Condition'], $removeValue));
            $sql .= "AND `Key` LIKE 'data_formats+name+{$newFieldKey}%'";
        } else {
            $sql .= "AND `Key` LIKE 'listing_fields+name+{$value['Data_local']}%'";
        }

        $keyExist = $rlDb->getRow($sql);
        if ($keyExist) {
            unset($data[$key]);
            continue;
        }

        if ($data[$key]['Data_local']) {
            if (trim($_GET['field']) == 'category') {
                $data[$key]['Data_local'] = $lang[ "categories+name+".$value['Data_local'] ];
            } elseif(trim($_GET['field']) == 'xml_dealer_id') {
                $data[$key]['Data_local'] = $rlDb -> getOne("Username", "`ID` = '".$value['Data_local']."'", "accounts");
            } else {
                if ($field) {
                    $local_field = $rlDb -> getOne("Data_local", "`Data_remote` = '".$field."'", "car_specs_mapping");
                }

                if ($lang["data_formats+name+{$value['Data_local']}"]) {
                    $data[$key]['Data_local'] = $lang["data_formats+name+{$value['Data_local']}"];
                } elseif ($lang["listing_fields+name+{$value['Data_local']}"]) {
                    $data[$key]['Data_local'] = $lang["listing_fields+name+{$value['Data_local']}"];
                } elseif ($local_field && $lang["listing_fields+name+{$local_field}_{$value['Data_local']}"]) {
                    $data[$key]['Data_local'] = $lang["listing_fields+name+{$local_field}_{$value['Data_local']}"];
                } else {
                    $where = "`Key` = 'data_formats+name+{$value['Data_local']}'";
                    $data[$key]['Data_local'] = $rlDb->getOne("Value", $where, "lang_keys");
                }
            }
        }
    }

    $reefless -> loadClass( 'Json' );

    $output['total'] = (int) $count['count'];
    $output['data'] = array_values($data);


    echo $rlJson -> encode( $output );
} else {

    $rlSmarty -> assign('allLangs', $languages);

    if ($_GET['service'] && $_GET['action'] == 'mapping' && !$_GET['field']) {
        $sql = "SELECT `T2`.`Data_remote`, `T2`.`Data_local` FROM `".RL_DBPREFIX."car_specs_mapping` AS `T1` ";
        $sql .="LEFT JOIN `".RL_DBPREFIX."car_specs_mapping` AS `T2` ON `T2`.`ID` = `T1`.`Parent_ID` ";
        $sql .="WHERE `T1`.`Service` = '{$_GET['service']}' ";
        $sql .="AND `T1`.`Parent_ID` != 0 AND `T1`.`Data_local` = '' ";
        $sql .="GROUP BY `T2`.`ID`";

        $not_mapping_items = $rlDb->getAll($sql);
       
        $rlBaseC = RL_URL_HOME . ADMIN . "/index.php?controller=" . $_GET['controller'] . "&amp;";

        if ($not_mapping_items) {
            $build_map_out ="<div>" . $lang['cs_build_info'] . "</div>";
            $build_map_out .="<ul>";
            foreach ($not_mapping_items as $key => $item) {
                if (strstr($item['Data_local'],'category')) {
                    $item['Data_remote'] = 'category';
                }

                $build_map_link = $rlBaseC . "action=mapping&amp;service={$_GET['service']}&amp;field=" . $item['Data_remote'];
                $build_map_out .='<li><a href="'.$build_map_link.'">'.$item['Data_remote'].'</a></li>';
            }
            $build_map_out .="</ul>";

            $info[] = $build_map_out;
        }
    }

    /* additional bread crumb step */
    if ($_GET['action']) {
        $service_info = $rlDb->fetch("*", array('Key'=>$_GET['service']), "AND `Status` <> 'trash'", null, 'car_specs_services', 'row');
        $service_name = $lang['car_specs_services+name+' . $service_info['Key']];


        switch ($_GET['action']){
            case 'mapping':
                if ($_GET['field']) {
                    $bcAStep[0]['name'] = str_replace('{service}', $service_name, $lang['cs_mapping_bc']);
                    $bcAStep[0]['Controller'] = 'car_specs';
                    $bcAStep[0]['Vars'] = 'action=mapping&service='.$service_info['Key'];

                    $bcAStep[1]['name'] = str_replace('{field}', $_GET['field'], $lang['cs_bc_field']);
                } else {
                    $bcAStep = str_replace('{service}', $service_name, $lang['cs_mapping_bc']);
                }
                break;
            case 'edit_service':
                $bcAStep = str_replace('{service}', $service_name, $lang['cs_edit_service_bc']);
                break;
        }
    }

    $reefless->loadClass('CarSpecs', null, 'carSpecs');

    if ($_GET['action'] == 'add_service' || $_GET['action'] == 'edit_service') {
        $modules = scandir(RL_PLUGINS . "carSpecs" . RL_DS . "modules");
        $modules = array_filter($modules, function($item){ return strlen($item) > 3; });

        $rlSmarty -> assign('modules', $modules);

        $reefless -> loadClass('ListingTypes');
        $rlSmarty -> assign('listing_types', $rlListingTypes -> types);

        $listing_types = array_filter($modules, function($item){ return strlen($item) > 3; });
        $rlSmarty -> assign('modules', $modules);

        if ($_GET['action'] == 'edit_service' && !$_POST['fromPost']) {
            $_POST['key'] = $service_info['Key'];
            $_POST['module'] = $service_info['Module'];
            $_POST['login'] = $service_info['Login'];
            $_POST['pass'] = $service_info['Pass'];
			$_POST['test_number'] = $service_info['Test_number'];
            $_POST['api_key'] = $service_info['Api_key'];
            $_POST['listing_type'] = $service_info['Listing_type'];
            $_POST['status'] = $service_info['Status'];

            $names = $rlDb -> fetch( array( 'Code', 'Value' ), array( 'Key' => 'car_specs_services+name+'.$service_info['Key'] ), "AND `Status` <> 'trash'", null, 'lang_keys' );            
            foreach ($names as $nKey => $nVal) {
                $_POST['name'][$names[$nKey]['Code']] = $names[$nKey]['Value'];
            }
        }
        
        if (isset($_POST['submit'])) {
            $errors = array();

            /* load the utf8 lib */
            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

            if ($_GET['action'] == 'add_service' || $_GET['action'] == 'edit_service') {
                $f_key = $rlValid->str2key($_POST['name'][$config['lang']]);

                if ($_GET['action'] == 'add_service') {
                    /* check key exist (in add mode only) */
                    if (strlen($f_key) < 3) {
                        $errors[] = $lang['incorrect_phrase_key'];
                        $error_fields[] = 'key';
                    }

                    $exist_key = $rlDb->fetch(array('Key'), array( 'Key' => $f_key ), null, null, 'car_specs_services');
                    if (!empty($exist_key)) {
                        $errors[] = str_replace( '{key}', "<b>\"".$f_key."\"</b>", $lang['notice_key_exist']);
                        $error_fields[] = 'key';
                    }
                }

                $f_name = $_POST['name'];

                foreach ($languages as $lkey => $lval) {
                    if (empty($f_name[$lval['Code']])) {
                        $errors[] = str_replace( '{field}', "<b>".$lang['name']."({$languages[$lkey]['name']})</b>", $lang['notice_field_empty']);
                        $error_fields[] = "name[{$lval['Code']}]";
                    }
                }

                $requiredTextFields = array(
                    array(
                        'key' => 'test_number',
                        'name' => $lang['cs_auth_login'],
                    ),
                    array(
                        'key' => 'api_key',
                        'name' => $lang['cs_api_key'],
                    ),
                    array(
                        'key' => 'pass',
                        'name' => $lang['cs_auth_password'],
                    ),
                    array(
                        'key' => 'login',
                        'name' => $lang['cs_auth_login'],
                    ),
                );

                foreach ($requiredTextFields as $requiredTextField) {
                    if (!$_POST[$requiredTextField['key']]) {
                        $find = '{field}';
                        $replace = sprintf("<b>%s</b>", $requiredTextField['name']);
                        $errors[] = str_replace('{field}', $replace, $lang['notice_field_empty']);
                        $error_fields[] = $requiredTextField['key'];
                    }
                }
            }

            if (!empty($errors)) {
                $rlSmarty -> assign_by_ref( 'errors', $errors );
            } else {
                $data = array(
                    'Key' => $f_key,
                    'Login' => $_POST['login'],
                    'Pass' => $_POST['pass'],
                    'Module' => $_POST['module'],
                    'Listing_type' => $_POST['listing_type'],
                    'Test_number' => $_POST['test_number'],
                    'Api_key' => $_POST['api_key'],
                    'Status' => $_POST['status'],
                );

                /* add/edit action */
                if ($_GET['action'] == 'add_service') {
                    if ($action = $rlActions->insertOne($data, 'car_specs_services')) {
                        foreach ($languages as $key => $value) {
                            $lang_keys[] = array(
                                'Code' => $languages[$key]['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Plugin' => 'carSpecs',
                                'Key' => 'car_specs_services+name+' . $f_key,
                                'Value' => $f_name[$languages[$key]['Code']],
                            );
                        }
        
                        $rlActions->insert($lang_keys, 'lang_keys');
                        $reefless->loadClass('Config');
                        $module = str_replace('.php', '', $_POST['module']);
                        $rlConfig->setConfig('cs_input_type', $module);
                        $message = $lang['cs_item_added'];
                        $aUrl = array("controller" => $controller);
                    } else {
                        trigger_error("Can't add new car specs (MYSQL problems)", E_WARNING);
                        $rlDebug->logger("Can't add new car specs (MYSQL problems)");
                    }
                } elseif ( $_GET['action'] == 'edit_service' ) {
                    $f_key = $_GET['service'];
    
                    $update_data['fields'] = $data;
                    $update_data['where']['Key'] = $f_key;
    
                    if ($action = $rlActions->updateOne($update_data, 'car_specs_services')) {
                        foreach ($languages as $key => $value) {
                            if ($rlDb->getOne('ID',
                                "`Key` = 'car_specs_services+name+{$f_key}' AND `Code` = '{$languages[$key]['Code']}'",
                                'lang_keys')
                            ) {
                                $update_names = array(
                                    'fields' => array(
                                        'Value' => $_POST['name'][$languages[$key]['Code']],
                                    ),
                                    'where' => array(
                                        'Code' => $languages[$key]['Code'],
                                        'Key' => 'car_specs_services+name+' . $f_key,
                                    ),
                                );
                                $rlActions->updateOne($update_names, 'lang_keys');
                            } else {
                                $insert_names = array(
                                    'Code' => $languages[$key]['Code'],
                                    'Module' => 'common',
                                    'Key' => 'car_specs_services+name+' . $f_key,
                                    'Value' => $_POST['name'][$languages[$key]['Code']],
                                );
                
                                $rlActions->insertOne($insert_names, 'lang_keys');
                            }
                        }
        
                        $message = $lang['notice_item_edited'];
                        $aUrl = array("controller" => $controller);
                    }
                }
    
                if ($action) {
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                } else {
                    trigger_error("Can't edit car specs (MYSQL problems)", E_WARNING);
                    $rlDebug->logger("Can't edit car specs (MYSQL problems)");
                }
            }
        }
    } elseif ($_GET['action'] == 'mapping') {

        if ($_GET['field']) {
            $sql ="SELECT `T1`.`Data_local` ";
            
            if (!is_numeric(strpos($_GET['field'], 'category'))) {
                $sql .=", `T2`.* ";
                $join .="JOIN `".RL_DBPREFIX."listing_fields` AS `T2` ON `T2`.`Key` = `T1`.`Data_local` ";
            }
            $sql .=" FROM `".RL_DBPREFIX."car_specs_mapping` AS `T1` ";
            $sql .=$join;

            $sql .="WHERE ";

            if ($_GET['field'] == 'category') {
                $sql .="`T1`.`Data_local` = 'category_0' ";            
            } elseif (is_numeric(strpos($_GET['field'], 'mf|'))) {
                $sql .="`T1`.`Data_local` = '".str_replace('mf|','', $_GET['field'] )."' ";
                $rlSmarty -> assign('mf_field', true);
                $mf_field = true;
            } else {
                $sql .="`T1`.`Data_remote` = '".$_GET['field']."' ";
            }

            $local_field_info =  $rlDb->getRow($sql);
            $rlSmarty->assign('local_field_info', $local_field_info);

            preg_match('#category_(\d)#', $local_field_info['Data_local'], $match);

            if ($match) {
                if ($_GET['parent']) {
                    $sql ="SELECT `T2`.`ID`, `T2`.`Type` FROM `" . RL_DBPREFIX . "car_specs_mapping` AS `T1` ";
                    $sql .="JOIN `" . RL_DBPREFIX . "categories` AS `T2` ON `T2`.`Key` = `T1`.`Data_local` ";
                    $sql .="WHERE `T1`.`ID` = " . $_GET['parent'];

                    $cat_info = $rlDb->getRow($sql);
                }
                else
                {
                    $sql ="SELECT `T3`.`ID`, `T3`.`Type` FROM `".RL_DBPREFIX."car_specs_mapping` AS `T1` ";
                    $sql .="JOIN `".RL_DBPREFIX."lang_keys` AS `T2` ON `T2`.`Value` = `T1`.`Default` ";
                    $sql .="JOIN `".RL_DBPREFIX."categories` AS `T3` ON `T2`.`Key` = CONCAT('categories+name+', `T3`.`Key` ) ";
                    $sql .="WHERE `T1`.`Data_local` = 'category_".$match[1]."' AND `T1`.`Default` != '' ";

                    $cat_info = $rlDb->getRow($sql);
                }

                $parent_id = $cat_info['ID'] ? $cat_info['ID'] : 0;

                $cats_tree = $rlCategories->getCatTree($parent_id);

                foreach ($cats_tree as $key => $value) {
                    $local_values[$key]['Key'] = $value['Key'];
                    $local_values[$key]['name'] = $value['name'];
                    $local_values[$key]['Level'] = $value['Level'];
                }

                $rlSmarty->assign('local_values', $local_values);
            } elseif ($mf_field) {
                $mfield_info = $rlDb->getRow($sql);

                if ($_GET['parent']) {
                    $sql ="SELECT `T2`.`Key` FROM `" . RL_DBPREFIX . "car_specs_mapping` AS `T1` ";
                    $sql .="JOIN `" . RL_DBPREFIX . "data_formats` AS `T2` ON `T2`.`Key` = `T1`.`Data_local` ";
                    $sql .="WHERE `T1`.`ID` = " . $_GET['parent'];

                    $item_info = $rlDb->getRow($sql);
                }
                
                $reefless->loadClass('MultiField', null, 'multiField');
                $data = $rlMultiField->getMDF($item_info ? $item_info['Key'] : $local_field_info['Condition']);

                $k = 0;
                if ($_GET['field'] == 'mf|location') {
                    foreach ($data as $k => $v) {
                        $data2[] = $rlMultiField -> getMDF($v['Key']);
                    }
                    
                    foreach ($data2 as $dk => $data) {
                        foreach ($data as $key => $value) {
                            $k++;
                            $local_values[$k]['Key'] = $value['Key'];
                            $local_values[$k]['name'] = $value['name'];
                        }
                    }
                } else {
                    foreach ($data as $key => $value) {
                        $k++;
                        $local_values[$k]['Key'] = $value['Key'];
                        $local_values[$k]['name'] = $value['name'];
                    }
                }

                $rlSmarty->assign('local_values', $local_values);
            } else {
                if ($local_field_info['Data_local'] == 'currency') {
                    $local_values = $rlCategories->getDF('currency');
                } elseif ($local_field_info['Data_local'] == 'xml_dealer_id') {
                    $account_list = $rlDb->fetch(array("Username", "ID"), array("Status" => "active"), null, null, "accounts");
                    foreach ($account_list as $key => $account) {
                        $local_values[$key]['name'] = $account['Username'];
                        $local_values[$key]['Key'] = $account['ID'];
                    }
                } else {
                    $local_values_tmp = $rlCommon->fieldValuesAdaptation(array(0 => $local_field_info), "listing_fields");

                    foreach ($local_values_tmp[0]['Values'] as $key => $value) {
                        if (!$local_field_info['Condition'] && $local_field_info['Type'] == 'select') {
                            if (!$value['Key']) {
                                $value['Key'] = $key;
                            }
                            
                            $local_values[$key]['Key'] = str_replace($local_field_info['Key']."_", '', $value['Key']);
                        } else {
                            $local_values[$key]['Key'] = $value['Key'];
                        }
                        $local_values[$key]['name'] = $value['name'] ? $value['name'] : $lang[ $value['pName'] ];
                    }
                }
                
                $rlSmarty -> assign('local_values', $local_values);
            }

            //$rlXajax -> registerFunction( array( 'addFieldMappingItem', $rlXmlFeeds, 'ajaxAddFieldMappingItem' ) );
            $rlXajax->registerFunction(array('copyMappingItem', $rlCarSpecs, 'ajaxCopyMappingItem'));
            $rlXajax->registerFunction(array('deleteMappingItem', $rlCarSpecs, 'ajaxDeleteMappingItem'));
        } else {
            $fields = $rlDb->fetch("*", array("Status" => "active"), "AND `Key` != 'Category_ID' AND `Key` != 'text_search' AND `Key` != 'xml_ref' AND `ID` > 0", null, 'listing_fields');
            $fields = $rlLang->replaceLangKeys($fields, 'listing_fields', 'name', RL_LANG_CODE);

            foreach($fields as $key => $field) {
                if ($field['name']) {
                    $out[$key]['Key'] = $field['Key'];
                    $out[$key]['Type_name'] = $lang['type_' . $field['Type']];
                    $out[$key]['name'] = $field['name'];

                    if ($field['Type'] == 'mixed') {
                        $measurement_fields[] = $field;
                    }
                }
            }

            $rlSmarty->assign('listing_fields', $out);
            $max_level = $rlDb->getOne("Level", "`Status` = 'active' ORDER BY `Level` DESC", 'categories');

            $key = 0;
            for ($i = 0; $i <= $max_level; $i++) {
                $system_out[$key]['Key'] = 'category_' . $i;
                $system_out[$key]['name'] = $lang['category'] . " Level " . $i;
                $system_out[$key]['Type_name'] = $lang['category'];
                $key++;
            }

            $system_out[$key]['Key'] = 'currency';
            $system_out[$key]['name'] = $lang['currency'];
            $system_out[$key]['Type_name'] = $lang['currency'];

            foreach ($measurement_fields as $mfk => $mfv) {
                $key++;
                $system_out[$key]['Key'] = $mfv['Key'] . "_unit";
                $system_out[$key]['name'] = $mfv['name'] . " " . $lang['cs_unit'];
                $system_out[$key]['Type_name'] = $lang['data_formats+name+' . $mfv['Condition']];
            }

            $key++;
            $system_out[$key]['Key'] = 'pictures';
            $system_out[$key]['name'] = $lang['cs_pictures'];
            //$system_out[$key]['Type_name'] = $lang['xf_pictures_ftype'];

            $key++;
            $system_out[$key]['Key'] = 'cs_base_field';
            $system_out[$key]['name'] = $lang['cs_base_field'];
            //$system_out[$key]['Type_name'] = $lang['listing_fields+name+xml_ref'];
            
            // $key++;
            // $system_out[$key]['Key'] = 'Loc_latitude';
            // $system_out[$key]['name'] = $lang['xf_latitude'];
            // $system_out[$key]['Type_name'] = '';

            // $key++;
            // $system_out[$key]['Key'] = 'Loc_longitude';
            // $system_out[$key]['name'] = $lang['xf_longitude'];
            // $system_out[$key]['Type_name'] = '';

            $rlSmarty->assign('system_fields', $system_out);

            $data = $rlDb->fetch("*", array('Service'=>$_GET['service']), null, null, 'car_specs_mapping', 'row');

            $fields = unserialize($data['Data']);
            $rlSmarty->assign('map_fields', $fields);
        }

        $rlXajax->registerFunction(array('deleteMappingItem', $rlCarSpecs, 'ajaxDeleteMappingItem'));


        $rlXajax -> registerFunction( array( 'addMappingItem', $rlCarSpecs, 'ajaxAddMappingItem' ) );
        // $rlXajax -> registerFunction( array( 'clearMapping', $rlXmlFeeds, 'ajaxClearMapping' ) );

/*      if (isset($_POST['submit'])) {
            $pdata = $_POST['xf'];

            foreach( $pdata as $key => $value )
            {
                if( !$value['xml'] || !$value['fl'])
                {
                    unset( $pdata[$key] );
                }
            }

            $update['fields'] = array(
                'Format' => $_GET['format'],
                'Xpath' => $_POST['xpath'],
                'Data' => serialize($pdata)
            );
            $update['where'] = array( 'Format' => $_GET['format'] );
            $action = $rlActions -> updateOne($update, 'xml_mapping');

            $message = $lang['notice_item_edited'];
            $aUrl = array( "controller" => $controller, 'mode' => 'formats' );
            
            if ( $action )
            {
                $reefless -> loadClass( 'Notice' );
                $rlNotice -> saveNotice( $message );
                $reefless -> redirect( $aUrl );
            }
        }*/

        $rlXajax->registerFunction(array('testService', $rlCarSpecs, 'ajaxTestService'));
    } else {
        $rlXajax->registerFunction(array('deleteService', $rlCarSpecs, 'ajaxDeleteService'));
    }

    // $rlXajax -> registerFunction( array( 'deleteFormat', $rlXmlFeeds, 'ajaxDeleteFormat' ) );
    // $rlXajax -> registerFunction( array( 'deleteUser', $rlXmlFeeds, 'ajaxDeleteUser' ) );
}
