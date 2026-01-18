<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: GEOLOCATIONCONTROLLER.PHP
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

namespace Flynax\Api\Http\Controllers\V1;

class GeoLocationController extends BaseController
{
    public function __construct()
    {
        rl('reefless')->loadClass('GeoFilter', null, 'multiField');
        rl('reefless')->loadClass('MultiField', null, 'multiField');
    }

    /**
     * Apply locations
     *
     * @return array - data
     **/ 
    public function appliedLocation($pageKey = '')
    {
        $filtering_pages = $GLOBALS['config']['mf_filtering_pages']
        ? explode(',', $GLOBALS['config']['mf_filtering_pages'])
        : [];


        if (is_array($filtering_pages) && in_array($pageKey, $filtering_pages)) {
            $geoKey = rl('GeoFilter')->geo_format['Key'];
            $mfData = [];
            foreach($_REQUEST as $key => $val) {
                if (is_numeric(strpos($key, $geoKey))) {
                    if (is_numeric(strpos($key, $geoKey. '_level'))) {
                        $mfData[str_replace($geoKey. '_level', '', $key)] = $val;
                    }
                    else {
                        $mfData[] = $val;
                    }
                }
            }
            if ($mfData) {
                rl('GeoFilter')->geo_filter_data['applied_location'] = $mfData;
                rl('GeoFilter')->geo_filter_data['location_keys'] = $mfData;
                rl('GeoFilter')->geo_filter_data['is_filtering'] = true;
                rl('GeoFilter')->geo_filter_data['filtering_pages'] = $filtering_pages;
                rl('GeoFilter')->prepareLocationFields();
            }
        }


        // print_r(rl('GeoFilter'));
        // exit;
    }

    /**
     * Get geo location data
     *
     * @return array - data
     **/
    public function getGeoData()
    {
        if ($_REQUEST['parent_ids']) {
            $ids = explode(',', $_REQUEST['parent_ids']);

            $data['0'] = rl('MultiField')->getData((int) rl('GeoFilter')->geo_format['ID'], false, rl('GeoFilter')->geo_format['Order_type']);
            foreach($ids as $parent_id) {
                if ($parent_id != '0') {
                    $items = rl('MultiField')->getData((int) $parent_id, false, rl('GeoFilter')->geo_format['Order_type']);
                    if ($items) {
                        $data[$parent_id] = $items;
                    }
                }
            }
        }
        else {
            if ($_REQUEST['parent_id']) {
                $format_id = (int) $_REQUEST['parent_id'];
            } else {
                $format_id = (int) rl('GeoFilter')->geo_format['ID'];
            }

            $data = rl('MultiField')->getData($format_id, false, rl('GeoFilter')->geo_format['Order_type']);
        }
        $response['results'] = $data;

        return $response;
    }

    /**
     * Get geo autocomplete multi field location
     *
     **/
    public function geoAutocomplete()
    {
        $query = $_REQUEST['query'];
        $lang = $_REQUEST['lang'];

        $data = rl('GeoFilter')->geoAutocomplete($query, $lang, null, null);
        $responce = array(
            'status' => $data ? 'ok' : 'error',
            'results' => $data
        );
        return $responce;
    }


    /**
     * Get geo autocomplete multi field location by key
     *
     **/
    public function getAutocompleteDataByKey()
    {
        $data = [];
        $mfKey = $_REQUEST['key'];
        $data['name'] = rl('Db')->getOne('Value', "`Key` = '{$mfKey}'", 'multi_formats_lang_'.RL_LANG_CODE);
        $itemData = rl('Db')->fetch(array('ID', 'Parent_IDs'), array('Key' => $mfKey), null, null, 'multi_formats', 'row');
        if ($itemData) {
            $data['parent_ids'] = $itemData['ID'];
            if ($itemData['Parent_IDs']) {

                $ids = array_reverse(explode(',', $itemData['ID'] .','. $itemData['Parent_IDs']));
                unset($ids[0]);
                $data['parent_ids'] = implode(',', $ids);

                $sql = "
                    SELECT `Key` 
                    FROM `{db_prefix}multi_formats`
                    WHERE `Status` = 'active'
                    AND `ID` = '{$itemData['Parent_IDs']}' OR FIND_IN_SET(`ID`, '{$itemData['Parent_IDs']}') > 0
                    GROUP BY `ID`
                    ORDER BY `ID`  
                ";
                $keys = rl('Db')->getAll($sql);
                unset($keys[0]);
                if ($keys) {
                    $mfKeys = [];
                    foreach($keys as $val) {
                        $mfKeys[] = $val['Key'];
                    }
                    $data['parent_keys'] = implode(',', $mfKeys);
                }
            }
            $data['parent_keys'] .= $data['parent_keys'] ? ',' . $mfKey : $mfKey;
        }
        $responce = array(
            'status' => $data ? 'ok' : 'error',
            'results' => $data
        );
        return $responce;
    }
}
