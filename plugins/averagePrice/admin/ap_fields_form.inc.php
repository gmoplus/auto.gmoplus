<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : AP_FIELDS_FORM.INC.PHP
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

use Flynax\Utils\Valid;

$reefless->loadClass('Categories');

if ($_GET['action'] == 'build') {
    $category_info = $rlDb->fetch(
        ['ID', 'Key'],
        ['Key' => Valid::escape($_GET['key'])],
        "AND `Status` <> 'trash'",
        null,
        'categories',
        'row'
    );

    if (!$category_info) {
        $sError = true;
    } else {
        $category_info = $rlLang->replaceLangKeys($category_info, 'categories', ['name'], RL_LANG_CODE, 'admin');

        $reefless->loadClass('Builder', 'admin');
        $reefless->loadClass('AveragePrice', null, 'averagePrice');
        $reefless->loadClass('Notice');

        $rlSmarty->assign_by_ref('category_info', $category_info);
        $rlSmarty->assign('cpTitle', $category_info['name']);
        $rlSmarty->assign('no_groups', true);

        foreach ($rlAveragePrice->deniedFieldTypes as $fieldType) {
            $deniedFieldTypes[] = $lang['type_' . ($fieldType === 'file' ? $fieldType . '_storage' : $fieldType)];
        }

        if (!$_REQUEST['xjxr']) {
            $deniedFieldTypes = '<b>' . implode(', ', $deniedFieldTypes) . '</b>';
            $rlNotice->saveNotice(str_replace('{types}', $deniedFieldTypes, $lang['ap_denied_fields']), 'alerts');
        }

        if ($_GET['form'] === 'ap_fields_form') {
            $rlBuilder->rlBuildTable = 'ap_form_relations';
            $rlBuilder->rlBuildField = 'Field_ID';

            $bcAStep = $lang['ap_form'];
        }

        $aFields   = $rlBuilder->getAvailableFields($category_info['ID']);
        $relations = $rlBuilder->getFormRelations($category_info['ID']);

        // Hide fields with denied type or key (they will be ignored in comparing)
        foreach ($relations as $relationKey => $relationField) {
            if (in_array($relationField['Fields']['Type'], $rlAveragePrice->deniedFieldTypes)
                || in_array($relationField['Fields']['Key'], $rlAveragePrice->deniedFieldKeys)
            ) {
                unset($relations[$relationKey]);
            }
        }

        $rlSmarty->assign_by_ref('relations', $relations);

        foreach ($relations as $rKey => $rValue) {
            $fFields = $relations[$rKey]['Fields'];

            if ($relations[$rKey]['Group_ID']) {
                foreach ($fFields as $fKey => $fValue) {
                    $noFields[] = $fFields[$fKey]['Key'];
                }
            } else {
                $noFields[] = $relations[$rKey]['Fields']['Key'];
            }
        }

        if (!empty($aFields)) {
            $aFields[]    = 88;
            $addCondition = "AND(`ID` = '" . implode("' OR `ID` = '", $aFields) . "') ";
            $addCondition .= "AND `Type` NOT IN('" . implode("', '", $rlAveragePrice->deniedFieldTypes) . "')";
            $addCondition .= "AND `Key` <> 'account_address_on_map'";
            $addCondition .= "AND `Key` NOT IN('" . implode("', '", $rlAveragePrice->deniedFieldKeys) . "') ";

            $fields = $rlDb->fetch(
                ['ID', 'Key', 'Type', 'Status'],
                null,
                "WHERE `Status` <> 'trash' {$addCondition}",
                null,
                'listing_fields'
            );
            $fields = $rlLang->replaceLangKeys($fields, 'listing_fields', ['name'], RL_LANG_CODE, 'admin');

            // Hide already using fields
            if (!empty($noFields)) {
                foreach ($fields as $fKey => $fVal) {
                    if (false !== array_search($fields[$fKey]['Key'], $noFields)) {
                        $fields[$fKey]['hidden'] = true;
                    }
                }
            }

            $rlSmarty->assign_by_ref('fields', $fields);
        }

        $rlXajax->registerFunction(['buildForm', $rlBuilder, 'ajaxBuildForm']);
    }
} else {
    $reefless->redirect(['controller' => 'categories']);
}
