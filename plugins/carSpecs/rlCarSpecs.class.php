<?php
/**copyright**/

use Flynax\Plugins\CarSpecs\ModulesController;

if (file_exists($autoLoad = __DIR__ . '/vendor/autoload.php')) {
    require_once $autoLoad;
}

class rlCarSpecs
{
    /**
     * Check registration number of car by plate and mileage
     *
     * @param string $number  - Car plate number
     * @return array|bool
     */
    public function ajaxCheckRegNumber($number)
    {
        global $steps, $rlListingTypes, $pages, $rlDb;

        $service_info = $rlDb->fetch("*", "`Status` = 'active'", null, null, "car_specs_services", "row");
        $type = $GLOBALS['rlListingTypes']->types[$service_info['Listing_type']];

        if (!$number) {
            $out['status'] = "error";
            $out['message'] = $GLOBALS['lang']['cs_error_number'];
        } else {
            $car_data = $this->getCarDetails($number, $service_info['Key']);
            $_SESSION['cs_post'] = array_filter($car_data['post']);


            if ($car_data['status'] == 'error') {
                $out = $car_data;
            } elseif (!$car_data) {
                $out['status'] = "error";
                $out['message'] = $GLOBALS['lang']['cs_error_no_data'];
            } else {
                $where = array("ID" => $car_data['Category_ID']);
                $category_info = $rlDb->fetch("*", $where, null, null, "categories", "row");

                $url = trim(SEO_BASE, '/') . '/' . $pages['add_listing'] . '/' . $category_info['Path'];
                $url .= '/' . $GLOBALS['steps']['plan']['path'] . '.html';

                if (!$GLOBALS['config']['mod_rewrite']) {
                    $url = trim(SEO_BASE, '/') . '/index.php?page=' . $pages['add_listing'] . '&step=';
                    $url .= $GLOBALS['steps']['plan']['path'] . '&id=' . $category_info['ID'];
                }

                $out = array(
                    'status' => 'OK',
                    'Category' => array(
                        'ID' => $category_info['ID'],
                        'Path' => $category_info['Path'],
                        'url' => $url,
                    ),
                    'Parent_IDs' => "{$car_data['Parent_IDs']},{$category_info['ID']}",
                );

                return $out;
            }

        }

        return $out;
    }

    /**
     * Get car information by registration number and service
     *
     * @param string $reg_number - Car plate number
     * @param string $service    - CarSpecs service key
     *
     * @return array             - Car Info from service API
     */
    public function getCarDetails($reg_number, $service)
    {
        global $rlDb;

        $service_info = $rlDb->fetch("*", array("Key" => $service), null, null, "car_specs_services", "row");

        $module = ModulesController::resolve($service_info['Module']);
        if (!is_object($module)) {
            $call_data['registration'] = $reg_number;

            $module_file = RL_PLUGINS . "carSpecs" . RL_DS . "modules" . RL_DS . $service_info['Module'];

            //make a call to a service to get data
            if (is_file($module_file) && is_readable($module_file)) {
                include $module_file;
            }
        } else {
            $out = $module->afterPostSending($service_info);
        }

        if ($out) {
            return $this->preparePost($out, $service_info, $call_data);
        }

        if (!$out || $out['status'] == 500) {
            return false;
        } elseif ($out['status'] == 'error') {
            return $out;
        }
    }

    /**
     * Simulate post from API info
     *
     * @param array  $out          - API response
     * @param string $service_info - CarSpecs service key
     * @param array  $call_data    - Array with registration number
     *
     * @return mixed
     */
    public function preparePost($out, $service_info, $call_data)
    {
        global $rlValid, $rlActions, $rlDb;

        $car_data = $this->extractSubNodes($out);
        $category = [];

        //define category
        $sql = "SELECT * FROM `" . RL_DBPREFIX . "car_specs_mapping` ";
        $sql .= "WHERE `Service` = '{$service_info['Key']}' AND `Data_local` LIKE 'category_%' ";
        $sql .= "ORDER BY `Data_local`";
        $category_mapping_fields = $rlDb->getAll($sql);
        if ($category_mapping_fields) {
            $parents = array();

            foreach ($category_mapping_fields as $ckey => $cat_field) {
                $sql = "SELECT `T1`.`ID`, `T2`.`Value`, `T1`.`Key`, `T1`.`Parent_IDs` ";
                $sql .= "FROM `" . RL_DBPREFIX . "categories` AS `T1` ";
                $sql .= "LEFT JOIN `" . RL_DBPREFIX . "lang_keys` AS `T2` ";
                $sql .= "ON `T2`.`Key` = CONCAT('categories+name+', `T1`.`Key`) ";
                $sql .= "WHERE `T2`.`Value` = '{$car_data[$cat_field['Data_remote']]}' ";
                $category = $rlDb->getRow($sql);

                if (!$category) {
                    $parent_id = $category_mapping_fields[0]['ID'];

                    foreach ($parents as $pk => $parent) {
                        $sql = "SELECT * FROM `" . RL_DBPREFIX . "car_specs_mapping` WHERE `Data_remote` = '" . $parent['Value'] . "' ";
                        $sql .= "AND `Service` = '{$service_info['Key']}' AND `Parent_ID` = " . $parent_id;
                        $parent_cat_mapping_item = $rlDb->getRow($sql);

                        if (!$parent_cat_mapping_item) {
                            $parent_cat_mapping_item['Parent_ID'] = $parent_id;
                            $parent_cat_mapping_item['Data_remote'] = $parent['Value'];
                            $parent_cat_mapping_item['Data_local'] = $parent['Key'];
                            $parent_cat_mapping_item['Service'] = $service_info['Key'];

                            $rlActions->insertOne($parent_cat_mapping_item, "car_specs_mapping");
                            $parent_cat_mapping_item['ID'] = $rlDb->insertID();
                        }
                        $parent_id = $parent_cat_mapping_item['ID'];
                    }

                    if (strtolower($parent_cat_mapping_item['Data_remote']) != strtolower($car_data[$category_mapping_fields[$ckey - 1]['Data_remote']])) {
                        $sql = "SELECT * FROM `" . RL_DBPREFIX . "car_specs_mapping` WHERE `Data_remote` = '" . $car_data[$category_mapping_fields[$ckey - 1]['Data_remote']] . "' ";
                        $sql .= "AND `Service` = '{$service_info['Key']}' AND `Parent_ID` = " . $parent_id;
                        $parent_cat_mapping_item = $rlDb->getRow($sql);

                        $parent_id = $parent_cat_mapping_item['ID'];
                    }

                    $sql = "SELECT `ID`, `Data_local` FROM `" . RL_DBPREFIX . "car_specs_mapping` WHERE ";
                    $sql .= "`Parent_ID` = '" . $parent_id . "' ";
                    $sql .= "AND `Data_remote` = '" . $rlValid->xSql($car_data[$cat_field['Data_remote']]) . "' ";
                    $sql .= "AND `Service` = '" . $service_info['Key'] . "' ";

                    $ex_mapping_item = $rlDb->getRow($sql);

                    if (!$ex_mapping_item) {

                        $cat_map_insert['Parent_ID'] = $parent_id;
                        $cat_map_insert['Data_remote'] = $car_data[$cat_field['Data_remote']];
                        $cat_map_insert['Service'] = $service_info['Key'];

                        $rlActions->insertOne($cat_map_insert, "car_specs_mapping");
                    } elseif ($ex_mapping_item['Data_local']) {
                        $where = array('Key' => $ex_mapping_item['Data_local']);
                        $category = $rlDb->fetch("*", $where, null, null, "categories", "row");
                        $data['Category_ID'] = $category['ID'];
                    } else {
                        // $p_out = $lang['categories']." > ";
                        // foreach ($parents as $key => $parent) {
                        //     $p_out .= $parent['Value'] . " > ";
                        // }
                        // $p_out = substr($p_out, 0, -2);

                        // $find = array('{xml_field}', '{xml_value}');
                        // $replace = array($p_out, $car_data[$cat_field['Data_remote']]);

                        //$rlXmlImport -> xmlLogger(str_replace($find, $replace, $lang['xf_progress_map_item_not_mapped']), "notice");
                    }
                } else {
                    $data = [
                        'Category_ID' => $category['ID'],
                        'Parent_IDs' => $category['Parent_IDs'],
                    ];
                    $parents[] = $category;
                }
            }
        }

        if (!$data['Category_ID']) {
            return false;
        }

        $where = array("Service" => $service_info['Key']);
        $mapping_source = $rlDb->fetch("*", $where, "AND `Data_local` != ''", null, "car_specs_mapping");
        $mapping = array();

        foreach ($mapping_source as $key => $value) {
            if (!$mapping[$value['Data_local']]) {
                $mapping[$value['Data_local']] = $value;
            } else {
                $mapping[$value['Data_local']]['Data_remote_extra'][] = $value['Data_remote'];
            }
        }

        $chosenListingType = $GLOBALS['rlListingTypes']->types[$service_info['Listing_type']];
        $form = method_exists(\Flynax\Utils\Category::class, 'buildForm')
            ? \Flynax\Utils\Category::buildForm($data['Category_ID'], $chosenListingType)
            : $GLOBALS['rlCategories']->buildListingForm($data['Category_ID'], $chosenListingType);

        foreach ($form as $gKey => $group) {
            foreach ($group['Fields'] as $fKey => $field) {
                $values_array = array();
                if ($mapping[$field['Key']]) {

                    $spec_value = $car_data[$mapping[$field['Key']]['Data_remote']];
                    $website_value = $this->websiteValue($field, $spec_value, $service_info);

                    // if there is more than one remote field mapped to a local field we collect all data into one field
                    if ($mapping[$field['Key']]['Data_remote_extra']) {
                        if ($field['Type'] == 'checkbox') {
                            $values_array[$website_value] = $website_value;
                        }

                        foreach ($mapping[$field['Key']]['Data_remote_extra'] as $emK => $emV) {
                            $spec_value = $car_data[$emV];

                            if ($extra_value = $this->websiteValue($field, $spec_value, $service_info)) {
                                if ($field['Type'] == 'checkbox') {
                                    $values_array[$extra_value] = $extra_value;
                                } else {
                                    $website_value .= " " . $extra_value;
                                }
                            }
                        }
                    }

                    if ($values_array) {
                        $website_value = array_filter($values_array);
                    }

                    $post[$field['Key']] = $website_value;
                }
            }
        }

        $post[] = array(
            'mileage' => array(
                'value' => $call_data['odometer'],
                'df' => 'km',
            ),
            'condition' => $call_data['odometer'] > 10000 ? 2 : 1,
        );

        return array(
            'post' => $post,
            'Category_ID' => $data['Category_ID'],
            'Parent_IDs' => $data['Parent_IDs'],
        );
    }

    /**
     * Adapt API mapped value to the Flynax readable format
     *
     * @package - ajax
     *
     * @param string $field_info   -  Field information
     * @param string $spec_value   - Field value
     * @param string $service_info - CarSpecs service key
     *
     * @return mixed
     */
    public function websiteValue($field_info, $spec_value, $service_info)
    {
        global $rlDb;

        if (!$field_info || !$spec_value) {
            return false;
        }

        $out = '';
        if ($field_info['Condition'] == 'years') {
            $field_info['Type'] = 'text';
        }

        switch ($field_info['Type']) {
            case "select":
            case "radio":
            case "checkbox":
                foreach ($field_info['Values'] as $k => $v) {
                    if (!$v['name'] && $v['pName']) {
                        $v['name'] = $GLOBALS['lang'][$v['pName']]
                            ?: $rlDb->getOne("Value", "`Key` = '{$v['pName']}'", "lang_keys");
                    }

                    if (strtolower($v['name']) == strtolower($spec_value)) {
                        $out = $v['Key'];
                        break;
                    }
                }

                if (!$field_info['Condition']) {
                    $out = str_replace($field_info['Key'] . "_", "", $out);
                }

                break;

            case "mixed":
                $out = $spec_value;
                if ($field_info['Key'] == 'engine_displacement') {
                    $val = $out;

                    $out = array();
                    $out['value'] = $val;
                    $out['df'] = "volume_cc";
                }
                break;

            default:
                $out = $spec_value;
                break;
        }

        if (!$out) {
            $where = "`Data_local` = '" . $field_info['Key'] . "' AND `Service` = '{$service_info['Key']}'";
            $parent_mapping_id = $rlDb->getOne("ID", $where, "car_specs_mapping");

            $mapping_insert['Parent_ID'] = $parent_mapping_id;
            $mapping_insert['Service'] = $service_info['Key'];
            $mapping_insert['Data_local'] = '';
            $mapping_insert['Data_remote'] = $spec_value;
            $mapping_insert['Status'] = 'active';

            $where = array(
                'Service' => $mapping_insert['Service'],
                'Data_remote' => $mapping_insert['Data_remote'],
                'Parent_ID' => $mapping_insert['Parent_ID'],
            );
            $ex = $rlDb->fetch("*", $where, null, null, "car_specs_mapping", "row");

            if (!$ex) {
                $GLOBALS['rlActions']->insertOne($mapping_insert, "car_specs_mapping");
            } elseif ($ex['Data_local']) {
                $out = $ex['Data_local'];
                if (!$field_info['Condition']) {
                    $out = str_replace($field_info['Key'] . "_", '', $out);
                }
            }
        }

        return $out;
    }

    /**
     * Ajax delete service
     *
     * @param string key - service key
     *
     * @return array mixed - Prepared response for AJAX call.
     */
    public function ajaxDeleteService($key)
    {
        global $lang;

        $GLOBALS['rlValid']->sql($key);

        $sql = "DELETE `T1`, `T2` FROM `" . RL_DBPREFIX . "car_specs_services` AS `T1` ";
        $sql .= "LEFT JOIN `" . RL_DBPREFIX . "lang_keys` AS `T2` ";
        $sql .= "ON `T2`.`Key` = CONCAT('car_specs_services+name+', `T1`.`Key`) ";
        $sql .= "WHERE `T1`.`Key` = '{$key}'";
        $GLOBALS['rlDb']->query($sql);

        $out['status'] = 'ok';
        $out['message'] = $lang['item_deleted'];

        return $out;
    }

    /**
     * add format item
     *
     * @package ajax
     *
     * @param mixed $data - data
     *
     **/
    public function ajaxDeleteMappingItem($data_remote = false, $get_data = false)
    {
        global $lang, $key, $rlDb;

        if ($get_data['field']) {
            if (is_numeric(strpos($get_data['field'], 'mf|'))) {
                $where = "`Data_local` = '" . str_replace('mf|', '', $get_data['field']) . "'";
                $mapping_parent = $get_data['parent']
                    ? $get_data['parent']
                    : $rlDb->getOne("ID", $where, "car_specs_mapping");
                $where = array("Data_remote" => $data_remote, "Parent_ID" => $mapping_parent);
                $item = $rlDb->fetch(array("ID"), $where, null, null, "car_specs_mapping", "row");

                $this->deleteMappingItemWithChilds($item['ID']);
            } elseif (is_numeric(strpos($get_data['field'], 'category'))) {
                $mapping_parent = $get_data['parent'] ? $get_data['parent']
                    : $rlDb->getOne("ID", "`Data_local` = 'category_0' AND `Service` = '{$get_data['service']}'",
                        "car_specs_mapping");
                $item = $rlDb->fetch(array("ID"), array("Data_remote" => $data_remote, "Parent_ID" => $mapping_parent),
                    null, null, "car_specs_mapping", "row");

                $this->deleteMappingItemWithChilds($item['ID']);
            } else {
                $where = "`Data_remote` = '{$get_data['field']}' AND `Service` = '{$get_data['service']}'";
                $parent_id = $rlDb->getOne("ID", $where, "car_specs_mapping");

                $sql = "DELETE FROM `" . RL_DBPREFIX . "car_specs_mapping` ";
                $sql .= "WHERE `Data_remote` = '{$data_remote}' AND `Service` = '{$get_data['service']}'";

                $rlDb->query($sql);
            }
        } else {
            $sql = "DELETE `T1`, `T2` FROM `" . RL_DBPREFIX . "car_specs_mapping` AS `T1` ";
            $sql .= "LEFT JOIN `" . RL_DBPREFIX . "car_specs_mapping` AS `T2` ON `T2`.`Parent_ID` = `T1`.`ID` ";
            $sql .= "WHERE `T1`.`Data_remote` = '{$data_remote}' AND `T1`.`Service` = '{$get_data['service']}' ";
            $rlDb->query($sql);
        }

        $response['status'] = 'ok';
        $response['message'] = $lang['item_deleted'];

        return $response;
    }

    /**
     * copy mapping item
     *
     * @package ajax
     *
     * @param mixed $data_remote - data
     *
     **/
    public function ajaxCopyMappingItem($data_remote = false)
    {
        global $_response, $rlActions, $rlValid, $lang, $rlDb;

        $where = "`Service` = '" . $_GET['service'] . "' AND `Data_remote` = '" . $_GET['field'] . "'";
        $parent = $rlDb->getOne("Data_local", $where, "car_specs_mapping");

        preg_match('#category_(\d)#', $parent, $match);

        /* insert category */

        if ($match[0]) {
            //todo: Check usage case of this condition.
            $this->createCategory($data_remote, $data['Category_ID']);
        } else {
            $field_info = $rlDb->fetch("*", array("Key" => $parent), null, null, "listing_fields", "row");

            /* insert value */
            if ($field_info['Condition']) {
                $where = array("Key" => $field_info['Condition']);
                $data_format_info = $rlDb->fetch("*", $where, null, null, "data_formats", "row");

                $item_insert['Parent_ID'] = $data_format_info['ID'];
                $item_insert['Key'] = $data_format_info['Key'] . "_" . $rlValid->str2key($data_remote);
                $where = "`Parent_ID` = " . $data_format_info['ID'] . " ORDER BY `Position` DESC";
                $item_insert['Position'] = $rlDb->getOne("Position", $where, "data_formats") + 1;
                $item_insert['Status'] = 'active';
                $data_remote = ucfirst(strtolower($data_remote));

                if ($rlActions->insertOne($item_insert, "data_formats")) {
                    foreach ($GLOBALS['languages'] as $key => $lang_item) {
                        $lang_keys[] = array(
                            'Code' => $lang_item['Code'],
                            'Module' => 'common',
                            'Key' => 'data_formats+name+' . $item_insert['Key'],
                            'Value' => $data_remote,
                            'Status' => 'active',
                        );
                    }
                    $res = $rlActions->insert($lang_keys, "lang_keys");
                }

                $sql = "DELETE FROM `" . RL_DBPREFIX . "car_specs_mapping` WHERE ";
                $sql .= "`Service` = '{$_GET['service']}' AND `Data_remote` = '{$data_remote}'";
                $rlDb->query($sql);
            } else {
                $last_val = end(explode(',', $field_info['Values']));
                $new_val = $last_val + 1;

                $sql = "SELECT * FROM `" . RL_DBPREFIX . "lang_keys` ";
                $sql .= "WHERE `Key` LIKE 'listing_fields+name+{$field_info['Key']}\_%' ";
                $sql .= "AND `Value` = '{$data_remote}'";
                $check = $rlDb->getRow($sql);

                if (!$check) {
                    $new_values = $field_info['Values'] . ',' . $new_val;

                    $sql = "UPDATE `" . RL_DBPREFIX . "listing_fields` SET `Values` = '{$new_values}' ";
                    $sql .= "WHERE `Key` = '{$field_info['Key']}' ";

                    if ($rlDb->query($sql)) {
                        foreach ($GLOBALS['languages'] as $key => $lang_item) {
                            $lang_keys[] = array(
                                'Code' => $lang_item['Code'],
                                'Module' => 'common',
                                'Key' => 'listing_fields+name+' . $field_info['Key'] . "_" . $new_val,
                                'Value' => $data_remote,
                                'Status' => 'active',
                            );
                        }
                        $rlDb->insert($lang_keys, 'lang_keys');
                    }
                }
            }
        }

        $_response->script("printMessage('notice', '{$lang['cs_item_added']}')");
        $_response->script("specsItemMappingGrid.reload();");

        return $_response;
    }

    /**
     * delete mapping with childs
     *
     * @package ajax
     *
     * @param int $id - id
     *
     **/
    public function deleteMappingItemWithChilds($id = false)
    {
        global $rlDb;

        $sql = "DELETE FROM `" . RL_DBPREFIX . "car_specs_mapping` ";
        $sql .= "WHERE `ID` = '{$id}'";
        $rlDb->query($sql);

        $childs = $rlDb->fetch(array('ID'), array('Parent_ID' => $id), null, null, "car_specs_mapping");
        foreach ($childs as $k => $v) {
            $this->deleteMappingItemWithChilds($v['ID']);
        }
    }

    /**
     * Run test API request to build mapping
     *
     * @param array $service - CarSpecs service info
     *
     * @return array - Prepared service API answer
     */
    public function ajaxTestService($service)
    {
        global $rlDb;

        $GLOBALS['reefless']->loadClass('Actions');
        $service_info = $rlDb->fetch("*", array("Key" => $service), null, null, "car_specs_services", "row");
        $module = ModulesController::resolve($service_info['Module']);
        $regNumber = $service_info['Test_number'];

        if ($module->isAuthRequired) {
            $module->setClientID($service_info['Login']);
            $module->setClientKey($service_info['Api_key']);
        }

        $out = $module->getCarInfo($regNumber);

        if (!$out) {
            $response = array(
                'status' => 'error',
                'message' => $GLOBALS['lang']['cs_something_went_wrong'],
            );

            return $response;
        }

        $out = $this->extractSubNodes($out);

        foreach ($out as $remote_field => $value) {
            $mapping_insert['Data_remote'] = $remote_field;
            $mapping_insert['Example_value'] = $value;
            $mapping_insert['Parent_ID'] = 0;
            $mapping_insert['Service'] = $service_info['Key'];
            $mapping_insert['Status'] = 'active';

            $sql = "SELECT `Key` FROM `" . RL_DBPREFIX . "lang_keys` WHERE LOWER(`Value`) = '" . $remote_field . "' ";
            $sql .= "AND `Key` LIKE '%listing_fields+%'";
            $local_field_lk = $rlDb->getRow($sql, "Key");

            if ($local_field_lk) {
                $mapping_insert['Data_local'] = str_replace('listings_fields+name_', $local_field_lk);
            }

            $where = array(
                'Data_remote' => $mapping_insert['Data_remote'],
                'Service' => $mapping_insert['Service'],
                'Parent_ID' => 0,
            );
            $ex = $rlDb->fetch('*', $where, null, null, 'car_specs_mapping', 'row');

            if ($ex['Data_local']) {
                $field_info = $this->getFieldInfo($ex['Data_local']);
                $website_value = $this->websiteValue($field_info, $value, $service_info);
            }

            if (!$ex) {
                $GLOBALS['rlActions']->insertOne($mapping_insert, "car_specs_mapping");
            }
        }

        return array(
            'status' => 'OK',
            'message' => $GLOBALS['lang']['cs_mapped_successfully'],
        );
    }

    /**
     * Get listing field info by Key
     *
     * @param string $field_key - Listing Field info
     *
     * @return mixed
     */
    public function getFieldInfo($field_key)
    {
        $field = $GLOBALS['rlDb']->fetch("*", array("Key" => $field_key), null, null, "listing_fields", "row");
        $field_info = $GLOBALS['rlCommon']->fieldValuesAdaptation(array(0 => $field), "listing_fields");

        return $field_info;
    }

    /**
     * Extract and prepare all subNodes for mapping purposes
     *
     * @param array listing_array - Answer from service API
     *
     * @return array - Prepared for mapping data
     */
    public function extractSubNodes($listing_array = array())
    {
        $out = array();

        foreach ($listing_array as $aKey => $node) {
            if (is_array($node)) {
                foreach ($node as $nKey => $nVal) {
                    reset($nVal);
                    $first_key = key($nVal);

                    if (is_array($nVal) && !is_numeric($first_key) /*&& !is_array($nVal[$first_key])*/) {
                        foreach ($nVal as $nvKey => $nvVal) {
                            if (is_array($nvVal)) {
                                foreach ($nvVal as $nvvKey => $nvvVal) {
                                    $v[$aKey . "_" . $nKey . "_" . $nvKey . "_" . $nvvKey] = $nvvVal;
                                }
                            } else {
                                $v[$aKey . "_" . $nKey . "_" . $nvKey] = $nvVal;
                            }
                        }
                    } else {
                        $v[$aKey . "_" . $nKey] = $nVal;
                    }
                }
                if ($v) {
                    $out = array_merge($out, $v);
                }
            } else {
                $out[$aKey] = $node;
            }
        }

        return $out;
    }

    /**
     * Create new category
     *
     * @param string $category_name - Category name
     * @param string $parent_id     - Parent_id
     *
     * @return int
     */
    public function createCategory($category_name, $parent_id)
    {
        echo 'todo';
        exit;
        global $rlValid, $rlActions, $languages;

        if ($parent_id) {
            $parent_info = $this->fetch("*", array("ID" => $parent_id), null, null, "categories", "row");
        } else {
            $parent_id = 0;
        }

        $cat_insert['Parent_ID'] = $parent_id;
        $cat_insert['Position'] = $this->getOne("Position", "`Parent_ID` = " . $parent_id . " ORDER BY `Position` DESC", "categories") + 1;
        $cat_insert['Path'] = $parent_info ? $parent_info['Path'] . "/" . $rlValid->str2path($category_name) : $rlValid->str2path($category_name);
        $cat_insert['Level'] = $parent_info['Level'] + 1;

        $cat_insert['Tree'] = $parent_info ? $parent_info['Tree'] . "." . $cat_insert['Position'] : $parent_info['Position'] . "." . $cat_insert['Position'];;
        $cat_insert['Parent_IDs'] = $parent_info['Parent_IDs'] ? $parent_info['Parent_IDs'] . '.' . $parent_info['Parent_ID'] : ($parent_info['Parent_ID'] ?: '');
        $cat_insert['Type'] = $parent_info['Type'] ? $parent_info['Type'] : "listings";

        $cat_key = $rlValid->str2key($category_name);
        if ($cat_key) {
            while ($ex = $this->getOne("ID", "`Key` ='" . $cat_key . "'", "categories")) {
                $cat_key = $parent_info['Key'] . "_" . $cat_key;
            }
        }

        $cat_insert['Key'] = $cat_key;
        $cat_insert['Count'] = 1;
        $cat_insert['Status'] = 'active';

        if ($rlActions->insertOne($cat_insert, "categories")) {
            $category_id = $GLOBALS['rlDb']->insertID();

            foreach ($languages as $lkey => $lang_item) {
                $lang_insert[$lkey]['Key'] = 'categories+name+' . $cat_key;
                $lang_insert[$lkey]['Value'] = $category_name;
                $lang_insert[$lkey]['Code'] = $lang_item['Code'];
                $lang_insert[$lkey]['Module'] = 'common';
                $lang_insert[$lkey]['Status'] = 'active';
            }

            $rlActions->insert($lang_insert, "lang_keys");

            return $category_id;
        }
    }

    /**
     * add format item
     *
     * @package ajax
     *
     * @param mixed $data - data
     *
     **/
    public function ajaxAddMappingItem($local = false, $remote = false, $get_data = false)
    {
        global $lang, $rlDb;

        $this->loadClass('Actions');

        if (trim($get_data['field']) == 'category' && !$get_data['parent']) {
            $where = "`Data_local` = 'category_0' AND `Service` = '" . $get_data['service'] . "'";
            $parent_id = $rlDb->getOne("ID", $where, "car_specs_mapping");
        } elseif (is_numeric(strpos($get_data['field'], 'mf|')) && !$get_data['parent']) {
            $where = "`Data_local` = '" . str_replace('mf|', '', $get_data['field']) . "'";
            $parent_id = $rlDb->getOne("ID", $where, "car_specs_mapping");
        } elseif ($get_data['field'] && !$get_data['parent']) {
            $where = "`Service` = '" . $get_data['service'] . "' AND `Data_remote` = '" . $get_data['field'] . "'";
            $parent_id = $rlDb->getOne("ID", $where, "car_specs_mapping");
        } elseif ($get_data['parent']) {
            $parent_id = $get_data['parent'];
        } else {
            $parent_id = 0;
        }

        $insert['Parent_ID'] = $parent_id;
        $insert['Service'] = $get_data['service'];
        $insert['Data_remote'] = $remote;

        $ex = $rlDb->fetch("*", $insert, null, null, "car_specs_mapping", "row");
        if ($ex) {
            $response['status'] = 'error';
            $response['message'] = str_replace("{key}", $local, $lang['notice_field_exist']);

            return $response;
        }

        $insert['Data_local'] = $local;
        $response['status'] = 'ok';
        $GLOBALS['rlActions']->insertOne($insert, "car_specs_mapping");

        return $response;
    }

    /**
     * Convert XML to array
     *
     * @param object $xmlObject       - Xml data object
     * @param bool   $skip_attributes - Skip attributes
     * @param bool   $json_method     - Enables json method
     *
     * @return mixed
     */
    public function toArray($xmlObject, $skip_attributes = false, $json_method = false)
    {
        if ($json_method) {
            return json_decode(json_encode((array) $xmlObject), true);
        }

        foreach ((array) $xmlObject as $index => $node) {
            if ($index == '@attributes' && $skip_attributes) {
            } else {
                if (is_object($node) && !(string) $node || is_array($node)) //if( is_object ( $node ) )
                {
                    $out[$index] = $this->toArray($node, $skip_attributes);
                } else {
                    $out[$index] = (string) $node;
                }
            }
        }

        return $out;
    }

    /**
     * Change HTML code of the Listing Detail page
     *
     * @param string $content - Html of the page
     *
     * @return string $content - Modified html
     **/
    public function prepareHistoryLink($content)
    {
        //todo: Why CarFax is using there and why it is under HTTP?
        if ($GLOBALS['page_info']['Key'] == 'view_details') {
            $what = "http://www.carfax.com/cfm/check_order.cfm?vin='+vin+'";
            $code = RL_FILES_URL . "vin-audit-reports/' + vin + " . "'.pdf";
            $content = str_replace($what, $code, $content);
        }

        return $content;
    }

    /**
     * @hook  staticDataRegister
     *
     * @since 1.2.0
     * @param \rlStatic $rlStatic
     */
    public function hookStaticDataRegister($rlStatic)
    {
        $showInPages = array('add_listing', 'view_details');
        $rlStatic = !is_null($rlStatic) ? $rlStatic : $GLOBALS['rlStatic'];

        $rlStatic->addJS(RL_PLUGINS_URL . 'carSpecs/static/lib.js', $showInPages, true);
        $rlStatic->addFooterCSS(RL_PLUGINS_URL . 'carSpecs/static/style.css', $showInPages, true);
    }

    /**
     * @hook  tplFooter
     *
     * @since 1.2.0
     **/
    public function hookTplFooter()
    {
        if ($GLOBALS['page_info']['Controller'] == 'listing_details') {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . '/carSpecs/views/listingDetailsBottomJs.tpl');
        }
    }

    /**
     * @hook  addListingBottom
     *
     * @since 1.2.0
     **/
    public function hookAddListingBottom()
    {
        if (!$GLOBALS['get_step']) {
            $GLOBALS['rlXajax']->registerFunction(array('checkRegNumber', $this, 'ajaxCheckRegNumber'));
        }
    }

    /**
     * @since 2.1.0
     *
     * @hook  addListingSteps
     *
     * @param \Flynax\Classes\AddListing $addListing
     */
    public function hookAddListingSteps($addListing)
    {
        $step = $GLOBALS['config']['add_listing_single_step'] ? $_POST['step'] : $addListing->step;
        if (!$GLOBALS['config']['mod_rewrite']) {
            $step = $_GET['step'];
        }

        if (
            ($step && $step != 'category')
            || in_array($_GET['nvar_1'], array('preview', 'done'))
            || in_array($step, array('preview', 'done'))
            || !$this->isMotorSpecsEnabled()
        ) {
            $GLOBALS['hide_cs_block'] = true;
        }

        if ($step == 'form') {
            $allLangs = $GLOBALS['languages'];

            foreach ($_SESSION['cs_post'] as $fieldKey => $fieldValue) {
                $fieldValue = is_array($fieldValue) ? array_filter($fieldValue) : $fieldValue;
                $isMultilanguage = $GLOBALS['rlDb']->getOne('Multilingual', "`Key` = '{$fieldKey}'", 'listing_fields');

                if ($isMultilanguage) {
                    foreach ($allLangs as $lang) {
                        $_POST['f'][$fieldKey][$lang['Code']] = $fieldValue;
                    }
                } else {
                    $_POST['f'][$fieldKey] = $fieldValue;
                }
            }

            unset($_SESSION['cs_post']);
        }
    }

    /**
     * Does MotorSpecs module is enabled
     *
     * @since 2.1.0
     * @return boolean
     */
    public function isMotorSpecsEnabled()
    {
        $sql = "SELECT `ID` FROM `" . RL_DBPREFIX . "car_specs_services` ";
        $sql .= "WHERE `Status` = 'active' AND `Module` = 'carmotorspecs.php'";

        return (bool) $GLOBALS['rlDb']->getRow($sql);
    }

    /**
     * @since 2.1.0
     *
     * @hook afterListingDone
     */
    public function hookAfterListingDone()
    {
        unset($_SESSION['cs_post']);
    }

    public function hookApTplHeader()
    {
        if ($_GET['controller'] != 'car_specs') {
            return false;
        }

        $GLOBALS['rlSmarty']->display(RL_PLUGINS . "carSpecs/admin/js-langs.tpl");
    }


    /**
     * @hook  addListingTopTpl
     *
     * @since 1.2.0
     **/
    public function hookAddListingTopTpl()
    {
        if (isset($GLOBALS['hide_cs_block'])) {
            return false;
        }

        $GLOBALS['rlSmarty']->display(RL_PLUGINS . "carSpecs" . RL_DS . "specs_add_listing.tpl");
    }

    /**
     * @hook  phpGetPlanByCategoryModifyWhere
     *
     * @since 1.2.0
     *
     * @param string $sql
     */
    public function hookPhpGetPlanByCategoryModifyWhere(&$sql)
    {
        if ($_SESSION['cs_post']) {
            $sql .= "AND `T1`.`Specs` = '1' ";
        }
    }

    /**
     * @hook  addListingBeforeSteps
     *
     * @since 1.2.0
     **/
    public function hookAddListingBeforeSteps()
    {
        if ($GLOBALS['cur_step'] == 'form' && $_SESSION['cs_post']) {
            $_POST['f'] = $_SESSION['cs_post'];
            unset($_SESSION['cs_post']);
        }
    }

    /**
     * @hook  addListingPostSimulation
     * @since 2.1.0
     * @param \Flynax\Classes\AddListing $addListing
     */
    public function hookAddListingPostSimulation($addListing)
    {
        foreach ($_SESSION['cs_post'] as $fKey => $fValue) {
            $_POST['f'][$fKey] = $fValue;
        }

        unset($_SESSION['cs_post']);
    }

    /**
     * @hook  apPhpListingPlansBeforeEdit
     *
     * @since 1.2.0
     **/
    public function hookApPhpListingPlansBeforeEdit()
    {
        $GLOBALS['update_date']['fields']['specs'] = $_POST['specs'];
    }

    /**
     * @hook  apPhpListingPlansBeforeAdd
     *
     * @since 1.2.0
     **/
    public function hookApPhpListingPlansBeforeAdd()
    {
        $GLOBALS['data']['specs'] = $_POST['specs'];
    }

    /**
     * @hook  apPhpListingPlansPost
     *
     * @since 1.2.0
     **/
    public function hookApPhpListingPlansPost()
    {
        $_POST['specs'] = $GLOBALS['plan_info']['specs'];
    }

    /**
     * @hook  apTplListingPlansForm
     *
     * @since 1.2.0
     **/
    public function hookApTplListingPlansForm()
    {
        echo '<table class="form"><tr><td class="name">' . $GLOBALS['lang']['cs_plan_specs'] . '</td><td class="field">';
        if ($_POST['specs'] == '1') {
            $specs_yes = 'checked="checked"';
        } elseif ($_POST['specs'] == '0') {
            $specs_no = 'checked="checked"';
        } else {
            $specs_no = 'checked="checked"';
        }

        echo '<input ' . $specs_yes . ' type="radio" id="cs_yes" name="specs" value="1" /><label for="cs_yes"> ' . $GLOBALS['lang']['yes'] . '</label>';
        echo '<input ' . $specs_no . ' type="radio" id="cs_no" name="specs" value="0" /> <label for="cs_no"> ' . $GLOBALS['lang']['no'] . '</label>';
        echo '</td></tr></table>';
    }

    /**
     * @hook  apTplListingPlansForm
     *
     * @since 1.2.0
     **/
    public function hookListingDetailsTop()
    {
        global $rlSmarty;

        $vin = $GLOBALS['listing_data']['vin'];
        $ex_history = $GLOBALS['rlDb']->getOne("Uniq", "`Uniq` = 'rh_{$vin}'", "car_specs_cache");
        if ($ex_history) {
            $rlSmarty->assign('vinpdf', $vin);
        }
    }

    /**
     * @hook  apTplListingPlansForm
     *
     * @since 1.2.0
     **/
    public function hookAjaxRequest(&$out, $request_mode)
    {
        global $rlValid;

        if (!$this->isValidRequest($request_mode)) {
            return false;
        }

        switch ($request_mode) {
            case 'cs_checkRegNumber':
                $reg_number = $rlValid->xSql($_REQUEST['reg_number']);
                $odometr = $rlValid->xSql($_REQUEST['odometr']);
                $out = $this->ajaxCheckRegNumber($reg_number, $odometr);
                break;
            case 'cs_getPdfReport':
                $vin = $rlValid->xSql($_REQUEST['vin']);
                $vinAudit = new \Flynax\Plugins\CarSpecs\Modules\Vinaudit();

                $answer = array('status' => 'ERROR');

                if ($vin && $result = $vinAudit->savePDF($vin)) {
                    $url = str_replace(RL_FILES, RL_FILES_URL, $result);
                    $answer = array(
                        'status' => 'OK',
                        'url' => $url,
                    );
                }

                if (!empty($vinAudit->errors)) {
                    $answer['message'] = $vinAudit->errors;
                }

                $out = $answer;
                break;
        }
    }

    /**
     * Is Ajax request is valid for the plugin
     *
     * @param string $request - Ajax request item
     *
     * @return bool
     */
    public function isValidRequest($request)
    {
        $validRequests = [
            'cs_checkRegNumber',
            'cs_getPdfReport',
        ];

        return in_array($request, $validRequests);
    }

    /**
     * @hook  apTplListingPlansForm
     *
     * @since 1.2.0
     **/
    public function hookApAjaxRequest()
    {
        global $rlValid, $out;

        $item = $_REQUEST['item'];
        switch ($item) {
            case 'cs_deleteService':
                $service_key = $rlValid->xSql($_REQUEST['service_key']);
                $out = $this->ajaxDeleteService($service_key);
                break;
            case 'cs_testService':
                $service_key = $rlValid->xSql($_REQUEST['service_key']);
                $out = $this->ajaxTestService($service_key);
                break;
            case 'cs_deleteMappingItem':
                $service_key = $rlValid->xSql($_REQUEST['service_key']);
                $get = $GLOBALS['rlValid']->xSql($_REQUEST['get']);
                $out = $this->ajaxDeleteMappingItem($service_key, $get);
                break;
            case 'cs_addMappingItem':
                $local = $rlValid->xSql($_REQUEST['add_item']['item_local']);
                $remote = $rlValid->xSql($_REQUEST['add_item']['item_remote']);
                $get = $rlValid->xSql($_REQUEST['get']);
                $out = $this->ajaxAddMappingItem($local, $remote, $get);
                break;
        }
    }

    /**
     * @hook  apTplListingPlansForm
     * Calls for Flynax Version >= 4.5
     *
     * @since 1.1.0
     **/
    public function hookSmartyFetchHook(&$compiled_content)
    {
        $compiled_content = $this->prepareHistoryLink($compiled_content);
    }

    /**
     * @hook  smartyClassFetch
     * Calls only for Flynax Version < 4.5
     *
     * @since 1.1.0
     **/
    public function hookPhpSmartyClassFetch(&$compiled_content)
    {
        $compiled_content = $this->prepareHistoryLink($compiled_content);
    }

    /**
     * Car Specs plugin uninstall
     *
     * @since 2.1.0
     */
    public function uninstall()
    {
        global $rlDb;

        $sql = "DROP TABLE IF EXISTS `" . RL_DBPREFIX . "car_specs_mapping` ";
        $rlDb->query($sql);

        $sql = "DROP TABLE IF EXISTS `" . RL_DBPREFIX . "car_specs_services` ";
        $rlDb->query($sql);

        $sql = "DROP TABLE IF EXISTS `" . RL_DBPREFIX . "car_specs_cache` ";
        $rlDb->query($sql);

        $sql = "ALTER TABLE `" . RL_DBPREFIX . "listing_plans` DROP `Specs`";
        $rlDb->query($sql);

        $sql = "ALTER TABLE `" . RL_DBPREFIX . "listings` DROP `cs_ref`";
        $rlDb->query($sql);

        $sql = "DELETE FROM  `" . RL_DBPREFIX . "config` WHERE `Key` = 'cs_input_type'";
        $rlDb->query($sql);

        if (is_dir(RL_FILES . "vin-reports")) {
            rmdir(RL_FILES . "vin-reports");
        }
    }

    /**
     * Car Specs plugin install
     * @since 2.1.0
     */
    public function install()
    {
        global $rlDb;

        $sql = "CREATE TABLE IF NOT EXISTS `" . RL_DBPREFIX . "car_specs_services` (
            `ID` int(11) NOT NULL auto_increment,
            `Key` varchar(255) NOT NULL default '',
            `Module` varchar(255) NOT NULL default '',
            `Login` varchar(255) NOT NULL default '',
            `Pass` varchar(255) NOT NULL default '',
            `Listing_type` varchar(255) NOT NULL default '',
            `Test_number` varchar(255) NOT NULL default '',
            `Api_key` varchar(50) NOT NULL default '',
            `Status` enum('active','approval') NOT NULL default 'active',
            `Token` varchar(255) NOT NULL default '',
              PRIMARY KEY  (`ID`)
            ) CHARACTER SET utf8 COLLATE utf8_general_ci;";
        $rlDb->query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS `" . RL_DBPREFIX . "car_specs_mapping` (
            `ID` int(11) NOT NULL auto_increment,
            `Parent_ID` int(11) NOT NULL,
            `Service` varchar(255) NOT NULL,
            `Data_local` varchar(255) NOT NULL,
            `Data_remote` varchar(255) NOT NULL,
            `Example_value` varchar(255) NOT NULL,
            `Cdata` enum('0','1') NOT NULL default '0',
            `Default` varchar(255) NOT NULL,
            `Status` enum('active','approval') NOT NULL default 'active',
            PRIMARY KEY (`ID`)
            ) CHARACTER SET utf8 COLLATE utf8_general_ci;";
        $rlDb->query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS `" . RL_DBPREFIX . "car_specs_cache` (
            `ID` int(11) NOT NULL auto_increment,
            `Uniq` varchar(255) NOT NULL default '',
            `Module` varchar(255) NOT NULL default '',
            `Content` longtext NOT NULL default '',
            `Date` varchar(255) NOT NULL default '',
              PRIMARY KEY  (`ID`)
            ) CHARACTER SET utf8 COLLATE utf8_general_ci;";
        $rlDb->query($sql);

        $sql = "ALTER TABLE `" . RL_DBPREFIX . "listing_plans` ADD `Specs` ENUM( '0', '1' ) default '0'";
        $rlDb->query($sql);

        $sql = "UPDATE `" . RL_DBPREFIX . "listing_plans` SET `Specs` = '1' WHERE 1";
        $rlDb->query($sql);

        $sql = "ALTER TABLE `" . RL_DBPREFIX . "listings` ADD `cs_ref` VARCHAR(255) NULL DEFAULT ''";
        $rlDb->query($sql);

        $sql = "INSERT INTO  `" . RL_DBPREFIX . "config` ";
        $sql .= "(`Group_ID`, `Position`, `Values`, `Key`, `Type`, `Data_type`, `Plugin`) VALUES ";
        $sql .= "(0, 0, '', 'cs_input_type','text','varchar','carSpecs')";
        $rlDb->query($sql);
    }

    /**
     * Car Specs installation
     *
     * @since 1.2.0
     * @deprecated 2.1.0
     **/
    public function cs_install()
    {
        $this->install();
    }

    /**
     * Car Specs plugin uninstall
     *
     * @since 1.2.0
     * @deprecated 2.1.0
     **/
    public function cs_uninstall()
    {
        $this->uninstall();
    }

    /**
     * @deprecated 2.1.0
     * @param string $message
     */
    public function logger($message)
    {

    }
}
