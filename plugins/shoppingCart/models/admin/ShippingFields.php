<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : SHIPPINGFIELDS.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2023
 *	https://www.flynax.com
 *
 ******************************************************************************/

namespace ShoppingCart\Admin;

/**
 * @since 3.0.0
 */
class ShippingFields
{
    /**
     * Get details
     *
     * @param int $start
     * @param int $limit
     * @return array
     */
    public function getFields($start = 0, $limit = 0)
    {
        global $rlDb, $lang, $l_types;

        $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, `T2`.`Value` AS `name` ";
        $sql .= "FROM `{db_prefix}shc_shipping_fields` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ";
        $sql .= "ON CONCAT('shc_shipping_fields+name+',`T1`.`Key`) = `T2`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
        $sql .= "WHERE `T1`.`Status` <> 'trash' ";
        $sql .= "LIMIT {$start}, {$limit}";

        $data = $rlDb->getAll($sql);
        $total_rows = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

        foreach ($data as $key => $value) {
            $data[$key]['Type'] = $l_types[$data[$key]['Type']];
            $data[$key]['Required'] = $data[$key]['Required'] ? $lang['yes'] : $lang['no'];
            $data[$key]['Map'] = $data[$key]['Map'] ? $lang['yes'] : $lang['no'];
            $data[$key]['Status'] = $lang[$data[$key]['Status']];
        }

        return array('data' => $data, 'total' => $total_rows['count']);
    }

    /**
     * Validate form
     */
    public function validate()
    {
        global $errors, $error_fields, $rlValid, $lang, $f_type, $f_key;

        loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

        $f_type = $_POST['type'];

        // check field type
        if (empty($f_type)) {
            $errors[] = $lang['notice_type_empty'];
            $error_fields[] = 'type';
        }

        // check key
        $f_key = $rlValid->xSql($_POST['key']);

        if (!utf8_is_ascii($f_key)) {
            $f_key = utf8_to_ascii($f_key);
        }

        if (strlen($f_key) < 3) {
            $errors[] = $lang['incorrect_phrase_key'];
            $error_fields[] = 'key';
        }

        // check key exist (in add mode only)
        if ($_GET['action'] == 'add' && $f_key) {
            if ($GLOBALS['rlDb']->columnExists($f_key, $GLOBALS['rlFields']->source_table)) {
                $errors[] = str_replace('{key}', "<b>\"" . $f_key . "\"</b>", $lang['notice_field_exist']);
                $error_fields[] = 'key';
            }
        }

        $f_key = $_GET['action'] == 'add' ? $rlValid->str2key($f_key) : $rlValid->xSql($f_key);

        // check name
        foreach ($allLangs as $lang_item) {
            if (empty($_POST['name'][$lang_item['Code']])) {
                $errors[] = str_replace('{field}', "<b>" . $lang['name'] . "({$lang_item['name']})</b>", $lang['notice_field_empty']);
                $error_fields[] = 'name[' . $lang_item['Code'] . ']';
            }
        }

        // check mixed type
        if ($f_type == 'mixed') {
            if (!$_POST['mixed'][1][$GLOBALS['config']['lang']] && !$_POST['data_format'] && !$_POST['mixed_data_format']) {
                $errors[] = $lang['notice_mixed_df_empty'];
            }
        }

        // check date type
        if ($f_type == 'date' && empty($_POST['date']['mode'])) {
            $errors[] = $lang['notice_mode_not_chose'];
        }

        // check file type
        if ($f_type == 'file' && empty($_POST['file']['type'])) {
            $errors[] = $lang['notice_type_empty'];
        }

        // check agreement field
        foreach ($allLangs as $lang_item) {
            if ($f_type == 'accept' && empty($_POST['accept'][$lang_item['Code']])) {
                $errors[] = str_replace('{field}', "<b>" . $lang['agreement_text'] . "({$lang_item['name']})</b>", $lang['notice_field_empty']);
            }
        }
    }

    /**
     * Assign google autocomplete field
     *
     * @param int $field_id
     * @param string $key
     */
    public function assignAutocomplete($field_id = 0, $key = '', $edit = false)
    {
        if (!$field_id) {
            return;
        }

        $update = array(
            'fields' => array('Google_autocomplete' => $key),
            'where' => $edit ? array('Key' => $field_id) : array('ID' => (int) $field_id),
        );

        $GLOBALS['rlDb']->updateOne(
            $update,
            'shc_shipping_fields'
        );
    }

    /**
     * Delete shipping field
     *
     * @param array $field
     * @return bool
     */
    public function delete($field)
    {
        global $rlDb;

        if ($rlDb->dropColumnFromTable($field['Key'], 'shc_orders')) {
            $rlDb->query("DELETE FROM `{db_prefix}shc_shipping_fields` WHERE `ID` = '{$field['ID']}' LIMIT 1");

            $rlDb->query(
                "DELETE FROM `{db_prefix}lang_keys`
                 WHERE `Key` LIKE 'shc_shipping_fields+name+{$field['Key']}%'
                    OR `Key` LIKE 'shc_shipping_fields+default+{$field['Key']}'
                    OR `Key` LIKE 'shc_shipping_fields+description+{$field['Key']}'"
            );
            $rlDb->query("DELETE FROM `{db_prefix}shc_shipping_form` WHERE `Field_ID` = {$field['ID']}");

            return true;
        }

        return false;
    }

    /**
     * Control type fields
     *
     * @param bool $useMultifield
     */
    public function controlTypes($useMultifield = false)
    {
        global $config, $rlDb;

        $fields = ['location_level3', 'location_level2', 'location_level1'];

        if ($useMultifield && !$GLOBALS['config']['shc_shipping_calc']) {
            $geoFormat = json_decode($config['mf_geo_data_format'], true);

            if ($geoFormat['Key']) {
                $condition = $geoFormat['Key'];
                $levels = $geoFormat['Levels'];

                foreach ($fields as $i => $field) {
                    if ($i < $levels) {
                        $update[] = array(
                            'fields' => array(
                                'Type' => 'select',
                                'Condition' => $condition
                            ),
                            'where' => array('Key' => $field)
                        );
                    } else {
                        $update[] = array(
                            'fields' => array(
                                'Status' => 'approval'
                            ),
                            'where' => array('Key' => $field)
                        );
                    }

                    $update_phrases[] = array(
                        'fields' => array(
                            'Status' => 'trash',
                            'Key' => 'back+shc_shipping_fields+description+' . $field
                        ),
                        'where' => array(
                            'Key' => 'shc_shipping_fields+description+' . $field,
                            'Modified' => '0'
                        )
                    );
                }
            }
        } else {
            foreach ($fields as $i => $field) {
                $update[] = array(
                    'fields' => array(
                        'Type' => $field == 'location_level1' ? 'select' : 'text',
                        'Condition' => '',
                        'Status' => 'active',
                    ),
                    'where' => array('Key' => $field)
                );

                $qtip_phrase = 'shc_shipping_fields+description+' . $field;

                if (!$rlDb->getOne('Value', "`Key` = '{$qtip_phrase}' AND `Status` = 'active'", 'lang_keys')) {
                    $update_phrases[] = array(
                        'fields' => array(
                            'Status' => 'active',
                            'Key' => $qtip_phrase
                        ),
                        'where' => array('Key' => 'back+' . $qtip_phrase)
                    );
                }
            }
        }

        $rlDb->update($update, 'shc_shipping_fields');

        if ($update_phrases) {
            $rlDb->update($update_phrases, 'lang_keys');
        }
    }
}
