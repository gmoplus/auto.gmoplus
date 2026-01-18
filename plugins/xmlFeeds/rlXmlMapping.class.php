<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLXMLMAPPING.CLASS.PHP
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

class rlXmlMapping {

    public $data_formats_mapping = array();
    public $listing_fields_mapping = array();
    public $fields_info = array();
    public $format = '';
    public $mapping = array();

    /**
     * Is installed multifield plugin has new data structure (>= 2.2.0)
     *
     * @since 3.4.0
     * @var boolean
     */
    public $isNewMultiField = false;

    /**
     * Listing field keys related to multifield formats
     *
     * @since 3.4.0
     * @var array
     */
    public $multiFormatKeys = [];

    function __construct()
    {
        $this->analyseMultiField();
    }

    /**
     * Analise is MultiField plugin installed and does it have new data structure
     *
     * @since 3.4.0
     */
    public function analyseMultiField()
    {
        global $plugins;

        if (!$plugins['multiField']) {
            return;
        }

        $GLOBALS['reefless']->loadClass('MultiField', null, 'multiField');
        $this->isNewMultiField = version_compare($plugins['multiField'], '2.2.0', '>=');
    }

    /**
     * Get multifield related listing fields
     *
     * @since 3.4.0
     */
    public function getMultifieldRelatedFileds()
    {
        if (!$GLOBALS['plugins']['multiField']) {
            return;
        }

        $sql = "
            SELECT `T1`.`Key`
            FROM `{db_prefix}listing_fields` AS `T1`
            JOIN `{db_prefix}multi_formats` AS `T2` ON `T1`.`Condition` = `T2`.`Key`
            WHERE `T1`.`Status` = 'active' AND `T2`.`Status` = 'active'
        ";
        $keys = $GLOBALS['rlDb']->getAll($sql, [false, 'Key']);

        if ($keys) {
            $this->multiFormatKeys = array_unique($keys);
        }
    }

    /**
    * Get Mapping Item - function to get local value if mapping exists and filled, create a mapping entry otherwise
    *
    * @param string $xml_field   - xml field
    * @param string $xml_value   - xml value
    * @param string $type        - type - field, field_item
    * @param string return_field - return field - what to return from array, (!) only working with field_item type
    */
    public function getMappingItem($xml_field, $xml_value, $flynax_value, $type = 'field', $return_field = false, $parent_id = false)
    {
        global $lang, $rlDb;

        $insert = array();

        switch($type) {
            case 'field':
                if ($this->mapping[$xml_field]) {
                    return $this->mapping[$xml_field];
                } else {
                    $insert_mapping = true;
                    $mapping_parent_id = 0;
                }

                $log_message = str_replace('{xml_field}', '<b>'.$xml_field.'</b>', $lang['xf_progress_map_field_added']);
                break;

            case 'field_item':
                $sql = "SELECT `ID`, `Data_local`, `Data_remote` FROM `{db_prefix}xml_mapping` ";
                if (substr($this->mapping[$xml_field], -5) == '_unit') {
                    $sql .="WHERE `Data_local` = '" . substr($this->mapping[$xml_field], 0, -5) . "' ";
                } elseif ($parent_id) {
                    $sql .="WHERE `ID` = {$parent_id} ";
                } else {
                    $sql .="WHERE `Data_remote` = '{$xml_field}' ";
                }          
                $sql .="AND `Format` = '" . $this->format . "' ";
                $sql .="AND `Status` = 'active' ";

                $mapping_parent = $rlDb->getRow($sql);
                $mapping_parent_id = $mapping_parent['ID'];

                $insert['Data_remote'] = $xml_value;
                $insert['Example_value'] = '';

                $insert_mapping = true;
                $find = array('{xml_field}', '{xml_value}');

                $added_to = $mapping_parent['Data_local'] ?: $mapping_parent['Data_remote'];
                $replace = array('<b>' . $added_to . '</b>', '<b>' . $xml_value . '</b>');
                $log_message = str_replace($find, $replace, $lang['xf_progress_map_item_added']);
                break;
        }

        $insert['Data_remote'] = isset($insert['Data_remote']) ? $insert['Data_remote'] : $xml_field;
        $insert['Example_value'] = is_array($xml_value) ? implode(', ', array_slice(array_unique($xml_value), 0, 5)) : htmlspecialchars($xml_value);
        $insert['Data_remote'] = $insert['Data_remote'];

        $sql = "SELECT `Data_remote`, `ID`, `Data_local`, `Parent_ID` FROM `{db_prefix}xml_mapping` ";
        $sql .="WHERE `Data_remote` = '" . $GLOBALS['rlValid']->xSql($insert['Data_remote']) . "' AND `Parent_ID` = {$mapping_parent_id} ";
        $sql .="AND `Format` = '" . $this->format . "'";

        $check = $rlDb->getRow($sql);

        if ($check) {
            $ret = $check;
        } elseif ($insert_mapping) {
            $insert['Parent_ID'] = $mapping_parent_id ?: 0;
            $insert['Format'] = $this->format;
            if ($flynax_value) {
                $insert['Data_local'] = $flynax_value;
            }
            $insert['Status'] = 'active';

            if ($rlDb->insertOne($insert, 'xml_mapping')) {
                $insert['ID'] = $rlDb->insertID();
                $this->xmlLogger($log_message, 'notice');
            }
    
            $ret = $insert;
        }

        if ($type == 'field') {
            if (is_array($ret)) {
                return $ret['Data_local'];
            }
        } elseif ($return_field) {
            return $ret[$return_field];
        } else {
            return $ret;
        }
    }

    /**
    * Get Field Info - return field info by field key
    *
    * @param string $field_key - field key     
    * @return array $field_info
    */
    public function getFieldInfo($field_key) 
    {
        if ($this->fields_info[$field_key]) {
            return $this->fields_info[$field_key];
        }

        $sql ="SELECT * FROM `{db_prefix}listing_fields` WHERE ";
        $sql .="`Key` = '" . $field_key . "'";

        $this->fields_info[$field_key] = $field_info = $GLOBALS['rlDb']->getRow($sql);
        
        return $field_info;
    }

    /**
    * Load Basic Mapping - loads configured mapping
    *     
    * @param type - import or export
    */
    public function loadBasicMapping($type = 'import')
    {
        global $rlDb;

        $data = $rlDb->fetch('*', array('Format'=>$this->format, "Parent_ID"=>0), null, null, 'xml_mapping');

        foreach ($data as $key => $row) {
            if ($row['Data_local']) {
                $fields[] = $row['Data_local'];
            }

            if (!$row['Data_local'] || !$row['Data_remote']) {
                continue;//experemental
            }

            if ($type == 'export') {
                $mapping[$row['Data_local']] = $row['Data_remote'];
            } else {
                $mapping[$row['Data_remote']] = $row['Data_local'];
            }
        }

        if ($fields) {
            $sql ="SELECT * FROM `{db_prefix}listing_fields` WHERE ";
            foreach ($fields as $fk => $field) {
                $sql .="`Key` = '" . $field . "' OR ";
            }
            $sql = substr($sql, 0, -3);
            $this->fields_info = $rlDb->getAll($sql, 'Key');

            foreach ($this->fields_info as $field_key => $field) {
                if (!in_array($field['Key'], array('Category_ID', 'posted_by'))) {
                    if (($field['Type'] == 'select' || $field['Type'] == 'mixed' || $field['Type'] == 'checkbox' )
                        && $field['Condition']) {
                        $dfs[] = $field['Condition'];
                    } elseif (in_array($field['Type'], array('select', 'radio', 'checkbox'))) {
                        $lfs[] = $field['Key'];
                    }
                }
            }
            $dfs[] = 'currency';

            $this->data_formats_mapping = $this->getDfMap($dfs, $type);
            $this->listing_fields_mapping = $this->getFieldsMap($lfs, $type);
            $this->loadMappedValues($type);
        }

        $this->mapping = $mapping;
    }

    /**
    * Load Mapped Values - loads mapping
    *
    * @return bool - result
    */
    public function loadMappedValues()
    {
        global $rlDb;

        $sql ="SELECT IF(`T2`.`Type` = 'price', 'currency', `T2`.`Condition`) AS `Condition`, ";
        $sql .="`T2`.`Key`, `T2`.`Type`, `T1`.`ID` AS `Mapping_ID` ";
        $sql .="FROM `{db_prefix}xml_mapping` AS `T1` ";
        $sql .="JOIN `{db_prefix}listing_fields` AS `T2` ON `T2`.`Key` = `T1`.`Data_local` ";
        $sql .="WHERE `T1`.`Format` = '" . $this->format . "' AND `T1`.`Parent_ID` = 0 ";
        $sql .="AND FIND_IN_SET(`T2`.`Type`, 'select,radio,checkbox,mixed,price') ";
        $sql .="AND `T1`.`Data_local` != ''";
        
        $mapping_fields = $rlDb->getAll($sql);

        if (!$mapping_fields) {
            return false;
        }
        
        foreach($mapping_fields as $key => $mapping_field) {
            $sql ="SELECT * FROM `{db_prefix}xml_mapping` ";
            $sql .="WHERE `Data_local` != '' AND `Parent_ID` = '{$mapping_field['Mapping_ID']}'";
            $mapping_values = $rlDb->getAll($sql);

            if ($mapping_values) {
                foreach ($mapping_values as $mapping_value) {
                    $rval = strtolower($mapping_value['Data_remote']);
                    $lval = $mapping_value['Data_local'];
                    if ($mapping_field['Condition']) {
                        $this->data_formats_mapping[$mapping_field['Condition']][$rval] = $lval;
                    } else {
                        $this->listing_fields_mapping[$mapping_field['Key']][$rval] = $lval;
                    }
                }
            }
        }

        return true;
    }

    /**
    * Get DataFormats Mapping - get data formats mapping
    *
    * @param array $format_keys - data entries keys to get mapping
    * @param string $type       - import or export - to define output
    * @return array $out     - mapping array
    */
    private function getDfMap($format_keys = false, $type = 'import')
    {
        global $config, $rlLang, $rlDb;

        if (!$format_keys) {
            return;
        }

        if (!is_array($format_keys)) {
            $format_keys[] = $format_keys;
        }

        $out = array();
        foreach($format_keys as $index => $key) {
            $format_id = $rlDb->getOne('ID', "`Key` = '{$key}'", 'data_formats');
            if ($format_id) {
                $sql ="SELECT `T1`.`ID`, `T1`.`Parent_ID`, `T1`.`Key` ";
                if ($type == 'export') {
                    $sql .=", `T2`.`Value` as `name` ";
                } else {
                    $sql .=", LOWER(`T2`.`Value`) as `name` ";
                }
                $sql .="FROM `{db_prefix}data_formats` AS `T1` ";
                $sql .="LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON `T2`.`Key` = CONCAT('data_formats+name+', `T1`.`Key`) ";
                    $sql .="AND `T2`.`Code` = '{$config['lang']}' ";
                $sql .="WHERE `T1`.`Status` = 'active' AND `T1`.`Parent_ID` = {$format_id} ";
                $sql .="GROUP BY `T1`.`Key` ";
                $sql .="ORDER BY `T1`.`Default` DESC, `T1`.`ID`, `T1`.`Key` ";
                
                if ($type == 'export') {
                    $df = $rlDb->getAll($sql, array('Key', 'name'));
                } else {
                    $df = $rlDb->getAll($sql, array('name', 'Key'));
                }

                $out[$key] = $df;
            }
        }

        return $out;
    }

    /**
    * Get Fields Map - get fields mapping
    *
    * @param array $field_keys - field keys to get mapping for
    * @param string $type      - import or export    
    * @return array $out       - fields mapping array
    */
    private function getFieldsMap($field_keys, $type = 'import')
    {
        global $rlDb;

        foreach ($field_keys as $index => $field_key) {
            $sql = "SELECT `Key`, `Value` as `name` FROM `{db_prefix}lang_keys` ";
            $sql .="WHERE `Key` LIKE 'listing_fields+name+{$field_key}_%'";
            $sql .="AND `Code` = '{$GLOBALS['config']['lang']}' ";

            $data = $rlDb->getAll($sql);

            foreach($data as $key => $value) {
                preg_match('/.*_([0-9]+)$/', $value['Key'], $match);
                if ($match[1]) {
                    if ($type == 'import') {
                        $out[$field_key][strtolower($value['name'])] = $match[1];
                    } else {
                        $out[$field_key][$match[1]] = $value['name'];
                    }
                }
            }
        }

        return $out;
    }

    /**
     * @deprecated 3.4.0
     */
    function isFieldMulti($field_key) {}

    /**
    * Xml Logger function wrapper
    */
    private function xmlLogger($log_message, $type)
    {
        if ($GLOBALS['rlXmlImport']) {
            $GLOBALS['rlXmlImport']->xmlLogger($log_message, $type);
        }
    }
}
