<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : SHIPPING.PHP
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

namespace ShoppingCart\Admin;

/**
 * @since 3.0.0
 */
class Shipping
{
    /**
     * Get all methods
     *
     * @return array
     */
    public function getMethods($start = 0, $limit = 0, $sort_field = 'ID', $sort_type = 'ASC')
    {
        global $rlDb, $lang;

        $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, `T2`.`Value` AS `name` ";
        $sql .= "FROM `{db_prefix}shc_shipping_methods` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ";
        $sql .= "ON CONCAT('shipping_methods+name+',`T1`.`Key`) = `T2`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
        $sql .= "WHERE `T1`.`Status` <> 'trash' ";
        $sql .= "ORDER BY `{$sort_field}` {$sort_type} ";
        $sql .= "LIMIT {$start}, {$limit}";

        $data = $rlDb->getAll($sql);
        $total_rows = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

        return ['data' => $data, 'total' => $total_rows['count']];
    }

    /**
     * Simulate Post
     *
     * @param array $data
     */
    public function simulatePost($data = array())
    {
        if (!$data) {
            return;
        }

        foreach ($data as $key => $val) {
            $_POST[strtolower($key)] = $val;
        }

        // get names
        $names = $GLOBALS['rlDb']->fetch(
            array('Code', 'Value'),
            array('Key' => 'shipping_methods+name+' . $data['Key']),
            "AND `Status` <> 'trash'",
            null,
            'lang_keys'
        );

        foreach ($names as $nKey => $nVal) {
            $_POST['name'][$nVal['Code']] = $nVal['Value'];
        }

        $settings = unserialize($data['Settings']);

        if ($settings) {
            foreach ($settings as $sKey => $sVal) {
                $settings[$sKey]['name'] = $sVal['phrase_key']
                ? $GLOBALS['lang'][$sVal['phrase_key']]
                : $GLOBALS['lang']['shc_' . $data['Key'] . '_' . $sVal['key']];
                if ($sVal['type'] == 'select' || $sVal['type'] == 'radio') {
                    if ($sVal['source']) {
                        require_once RL_PLUGINS . 'shoppingCart/shipping/' . $data['Key'] . '/static.inc.php';
                        $settings[$sKey]['items'] = $GLOBALS[$sVal['source']];
                    } elseif ($sVal['source']) {
                        $settings[$sKey]['items'] = explode(',', $sVal['items']);
                    }
                }
            }
        }

        $GLOBALS['rlSmarty']->assign_by_ref('methodSettings', $settings);
    }

    /**
     * Update settings
     *
     * @param string $methodInfo
     * @param array $data
     * @return bool
     */
    public function update($methodInfo = '', $data = array())
    {
        global $rlDb, $allLangs;

        if (!$data) {
            return false;
        }

        $settings = [];
        $langKeys = [];

        $settingsDB = unserialize($methodInfo['Settings']);

        if ($data['settings']) {
            foreach ($data['settings'] as $key => $val) {
                $settings[$key] = [
                    'type' => $settingsDB[$key]['type'],
                    'source' => $settingsDB[$key]['source'],
                    'key' => $key,
                    'value' => is_array($val) ? implode(',', $val) : $val,
                ];
            }
        }

        // update method
        $update = [
            'fields' => [
                'Settings' => serialize($settings),
                'Status' => $data['status'] ?: 'active',
                'Test_mode' => $data['test_mode'],
            ],
            'where' => ['ID' => $methodInfo['ID']],
        ];

        $rlDb->updateOne($update, 'shc_shipping_methods', ['Settings']);

        // update name
        foreach ($allLangs as $key => $value) {
            $sql = "`Key` = 'shipping_methods+name+{$methodInfo['Key']}' AND `Code` = '{$allLangs[$key]['Code']}'";
            if ($rlDb->getOne('ID', $sql, 'lang_keys')) {
                $langKey = [
                    'fields' => [
                        'Value' => $data['name'][$allLangs[$key]['Code']],
                    ],
                    'where' => [
                        'Code' => $allLangs[$key]['Code'],
                        'Key' => 'shipping_methods+name+' . $methodInfo['Key'],
                    ],
                ];

                $rlDb->updateOne($langKey, 'lang_keys');
            } else {
                $langKey = [
                    'Code' => $allLangs[$key]['Code'],
                    'Module' => 'common',
                    'Status' => 'active',
                    'Key' => 'shipping_methods+name+' . $methodInfo['Key'],
                    'Value' => $data['name'][$allLangs[$key]['Code']],
                ];

                $rlDb->insertOne($langKey, 'lang_keys');
            }
        }

        $this->controlConfigs();

        return true;
    }

    /**
     * Control configs of calculation shipping
     */
    public function controlConfigs()
    {
        global $rlDb;

        $isCalc = false;
        $sql = "SELECT * FROM `{db_prefix}shc_shipping_methods` WHERE `Status` = 'active' ORDER BY `ID` DESC";
        $methods = $rlDb->getAll($sql);

        if ($methods) {
            foreach ($methods as $k => $val) {
                $methodClass = '\ShoppingCart\Shipping\\' . ucfirst($val['Key']);
                $methodClass = new $methodClass();
                $methodClass->init();
                if ($methodClass->isConfigured()) {
                    $isCalc = true;
                    break;
                }
            }
        }

        $update[] = array(
            'fields' => array(
                'Default' => $isCalc ? 1 : 0,
            ),
            'where' => array('Key' => 'shc_shipping_calc'),

        );

        $update[] = array(
            'fields' => array(
                'Default' => $isCalc ? 0 : (int) $GLOBALS['config']['shc_use_multifield'],
            ),
            'where' => array('Key' => 'shc_use_multifield'),

        );
        $rlDb->update($update, 'config');

        if ($isCalc) {
            $shippingFields = new \ShoppingCart\Admin\ShippingFields();
            $shippingFields->controlTypes();
        }
    }
}
