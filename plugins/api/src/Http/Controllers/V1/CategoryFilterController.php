<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: CATEGORYFILTERCONTROLLER.PHP
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

use Flynax\Utils\Category;

class CategoryFilterController
{
    private static $filterBoxInfo = array();
    private static $isActive = null;

    private static $ltypeKey = '';
    private static $categoryId = 0;
    private static $boxId = 0;
    private static $mode = '';

    public const FILTER_SEARCH_RESULTS = 'search_results';
    public const FILTER_LISTING_TYPE = 'type';
    public const FILTER_CATEGORY = 'category';

    public static function initFor($mode)
    {
        self::$mode = $mode;
    }

    public static function isActive()
    {
        if (self::$isActive == null) {
            self::$isActive = (bool) $GLOBALS['plugins']['categoryFilter'];

            if (self::$isActive) {
                rl('reefless')->loadClass('CategoryFilter', null, 'categoryFilter');
            }
        }
        return self::$isActive;
    }

    /**
     * Set box id
     *
     * @param int $id - box id
     */
    public static function setBoxId($id)
    {
        self::$boxId = (int) $id;
    }

    /**
     * Set category id
     *
     * @param int $id - category id
     */
    public static function setCategoryId($id)
    {
        self::$categoryId = (int) $id;
    }

    /**
     * Set listing type
     *
     * @param string $type - listing type
     */
    public static function setListingType($type)
    {
        self::$ltypeKey = $type;
    }

    public function getBoxId()
    {
        return self::$boxId;
    }

    /**
     * Determinate filter box id from database
     *
     * @param string $listing_type - Listing type key
     * @param int    $category_id  - Category ID
     * @param string $box_type     - Filter box for (const FILTER_*)
     *
     * @return int|bool
     */
    public static function fetchBoxId($listing_type, $category_id = 0, $box_type = null)
    {
        global $category;
        $listing_type = $listing_type ?: self::$ltypeKey;
        $category_id = $category_id ?: self::$categoryId;

        if (!$listing_type && !$category_id) {
            return false;
        }
        $where = '';

        if (!$box_type && self::$mode) {
            $box_type = self::$mode;
        }

        if (!$box_type) {
            return false;
        }

        if ($category_id && $box_type == self::FILTER_LISTING_TYPE) {
            $category = $GLOBALS['category'] = Category::getCategory($category_id);
            $where .= "AND FIND_IN_SET('{$category_id}', `Category_IDs`) ";

            $boxId = (int) rl('Db')->getOne('ID', "`Mode` = '" . self::FILTER_CATEGORY . "' {$where}", 'category_filter');
            if ($boxId) {
                return $boxId;
            }
        }

        $boxId = (int) rl('Db')->getOne('ID', "`Mode` = '{$box_type}' AND `Type` = '{$listing_type}'", 'category_filter');
        $status = rl('Db')->getOne('Status', "`Key` = 'categoryFilter_{$boxId}'", 'blocks');

        return $status == 'active' ? $boxId : false;
    }

    /**
     * Prepare filters
     *
     * @param  array $appFilters
     * @param  array $formData
     */
    public static function prepareFilters($appFilters, $formData = [])
    {
        if (!self::$boxId) {
            return;
        }

        // Tric
        $GLOBALS['page_info']['Controller'] = 'listing_type';
        $GLOBALS['categoryFilter_activeBoxID'] = self::$boxId;

        $filterData = [];
        $fields = rl('CategoryFilter')->getFilterFields(self::$boxId);

        // collect possible filters from search form
        if (empty($appFilters) && !empty($formData)) {
            foreach ($fields as $field) {
                $key = $field['Key'];
                if (!empty($formData[$key]) && $fields[$key]) {
                    $filterData[$key] = $formData[$key];
                }
            }
        }
        // Set active filter in from the app
        elseif (!empty($appFilters)) {
            foreach ($appFilters as $key => $value) {
                if ($value && $fields[$key]) {
                    $filterData[$key] = $value;
                }
            }
        }

        // Set currency
        if ($appFilters['currency']) {
            $filterData['currency'] = $appFilters['currency'];
            rl('CategoryFilter')->currency = $appFilters['currency'];
        }

        if ($filterData) {
            rl('CategoryFilter')->filters = $filterData;
        }

        $sql = "SELECT `Content` FROM `{db_prefix}blocks` ";
        $sql .= "WHERE `Key` = 'categoryFilter_" . self::$boxId . "'";
        self::$filterBoxInfo = rl('Db')->getRow($sql);

        // Save some smarty assigned values
        rl('Smarty')->collectValuesForKeys(['cfInfo', 'cfFields', 'categories']);
    }

    /**
     * Check geo filter page
     *
     * @param bool
     * @since 1.0.1
     */
    public static function isGeoFilterPage($filterInfo)
    {
        global $config;

        if ($filterInfo) {
            $listingTypeKey = '';
            switch ($filterInfo['Mode']) {
                case 'type':
                    $listingTypeKey = $filterInfo['Type'];
                    break;
                case 'category':
                    if ($GLOBALS['category'] && rl('ListingTypes') && rl('ListingTypes')->types) {
                        $listingTypeKey = rl('ListingTypes')->types[$GLOBALS['category']['Type']]['Key'];
                    }
                    break;
            }

            $filteringPages = $config['mf_filtering_pages'] ? explode(',', $config['mf_filtering_pages']) : [];

            if ($listingTypeKey && $filteringPages) {
                return in_array('lt_' . $listingTypeKey, $filteringPages, true);
            }
        }
        return false;
    }

    /**
     * Apply filters to response
     *
     * @param array $out - response array
     */
    public static function applyFiltersToResponse()
    {
        if (!self::$boxId) {
            return;
        }
        global $rlSmarty, $lang, $rlLang, $config;

        /**
         * Filter box content example:
         *
         * $filter_info = '<base64 data>';
         * $filter_fields = '<base64 data>';
         * rl('CategoryFilter')->request($filter_info, $filter_fields);
         */
        eval(self::$filterBoxInfo['Content']);

        if (empty($fields = $rlSmarty->valueByKey('cfFields'))) {
            return;
        }

        $out = [
            'filter_info'      => $rlSmarty->valueByKey('cfInfo'),
            'selected_filters' => $filters = rl('CategoryFilter')->filters,
            'filter_fields'    => [],
        ];

        $geoFilterBox = false;
        if ($GLOBALS['plugins']['multiField']) {
            $multi_field_table =   'listing_fields';
            $sql = "SELECT * FROM `{db_prefix}multi_formats` AS `T1`";
            $sql .= "JOIN `{db_prefix}" . $multi_field_table . "` AS `T2` ON `T2`.`Condition` = `T1`.`Key` ";
            $sql .= "WHERE `T1`.`Status` = 'active'";
            $sql .= " AND `T1`.`Parent_ID` = 0";
            $mf_tmp = rl('Db')->getAll($sql);

            foreach ($mf_tmp as $key => $item) {
                $multi_fields[$item['Key']] = true;
            }

            if (rl('GeoFilter', null, 'multiField')->geo_format) {
               $geoFilterBox = self::isGeoFilterPage($out['filter_info']);
               foreach ($fields as $filterKey => &$filterField) {
                    if ($multi_fields[$filterField['Key']]) {
                        $exp_key = explode('_level', $filterField['Key']);
                        if ($exp_key) {
                            $parent_level = (int)$exp_key[1] - 1;
                            $parent_key = $parent_level == 0 ? $exp_key[0] : $exp_key[0] . '_level' . $parent_level;
                        }
                        else {
                            $parent_level = 0;
                        }
                        $filterField['multiField_level'] = $exp_key[1];
                    }

                    if ($geoFilterBox && $filterField['Condition'] && $filterField['Condition'] === rl('GeoFilter')->geo_format['Key']) {
                        if (preg_match('/[a-zA-Z|0-9]+\_level([0-9])/', $filterField['Key'])) {
                            unset($fields[$filterKey]);
                        } else {
                            $filterField['Geo_filter'] = true;
                            $geoFilter = true;
                        }
                    }
                }
            }
        }


        $categories = $rlSmarty->valueByKey('categories');
        if ($categories) {
            $categories = array_column($categories, 'name', 'ID');
        }

        foreach ($fields as $field) {
            $fieldKey      = (string) $field['Key'];
            $fieldItemsKey = 'Items';
            $items         = [];

            if (!in_array($field['Type'], ['number', 'mixed']) || $field['Mode'] != 'slider' || $field['Mode'] != 'text') {
                if (($field['Type'] == 'checkbox' || $field['Mode'] == 'checkboxes') && $field['Values']) {
                    foreach (explode(',', $field['Values']) as $key => $item) {
                        $itemName = '';
                        $item_key = 'category_filter+name+' . $field['ID'] . '_' . $field['Field_ID'] . '_' . $item;

                        if (!rl('Lang')->getPhrase($item_key, null, null, true)) {
                            if ($field['Condition']) {
                                $item_key = 'data_formats+name+' . $item;
                            } else {
                                $item_key = 'listing_fields+name+' . $field['Key'] . '_' . $item;
                            }
                        }

                        $itemName = $lang[$item_key];

                        $items[] = [
                            'count'    => 0,
                            'selected' => '',
                            'Key'      => $item,
                            'name'     => $itemName,
                        ];
                    }
                } else {
                    foreach ($field[$fieldItemsKey] as $key => $item) {
                        $itemKey      = (string) $item[$fieldKey];
                        $itemSelected = (isset($filters[$fieldKey]) && $filters[$fieldKey] == $itemKey);
                        $itemName     = '';

                        if ($field['Condition'] && $field['Mode'] != 'text') {
                            if ($field['Condition'] == 'years') {
                                $itemName = $itemKey;
                            } else {
                                // $itemName = rl('Lang')->getPhrase('data_formats+name+' . $itemKey, null, null, true);
                                $itemName = $lang['data_formats+name+' . $itemKey];
                            }
                        } elseif (in_array($field['Type'], ['text', 'mixed', 'price']) || $field['Mode'] == 'text') {
                            $itemName = $itemKey;
                        } elseif ($field['Type'] == 'checkbox') {
                            $itemName = $lang['listing_fields+name+' . $itemKey];
                        } elseif ($field['Type'] == 'bool') {
                            $itemName = $lang[$itemKey == 1 ? 'yes' : 'no'];
                        } elseif ($field['Values'] != '') {

                            $item_lang_key = 'category_filter+name+' . $out['filter_info']['ID'] . '_' . $field['Field_ID'] . '_' . $itemKey;
                            $itemName = $lang[$item_lang_key] ?: $lang[sprintf('%s_%s', $field['pName'], $itemKey)];

                        } elseif ($fieldKey == 'posted_by') {
                            $itemName = $lang['account_types+name+' . $itemKey];
                        } elseif ($fieldKey == 'Category_ID') {
                            $itemName = !empty($categories[$itemKey]) ? (string) $categories[$itemKey] : 'category';
                        } else {
                            $item_lang_key = 'category_filter+name+' . $out['filter_info']['ID'] . '_' . $field['Field_ID'] . '_' . $key;
                            $itemName      = $lang[$item_lang_key] ?: $key;
                            $itemKey       = $key;
                        }

                        $count = isset($item['Number']) ? (int) $item['Number'] : (isset($item['-1']) ? (int) $item['-1'] : (int) $item);

                        $items[] = [
                            'count'    => $count,
                            'selected' => $itemSelected,
                            'Key'      => $itemKey,
                            'name'     => $itemName,
                        ];
                    }
                }
            }
            if ($field['Type'] == 'price' && $field['Key'] == $config['price_tag_field']) {
                foreach (rl('Categories')->getDF('currency') as $currency_item) {
                    $tmpVal['name'] = $currency_item['name'];
                    $tmpVal['Key']  = $currency_item['Key'];
                    $field['df'][]  = $tmpVal;
                }
            }

            $field['Items'] = $items;
            $field['name'] = rl('Lang')->getPhrase($field['pName']);
            $out['filter_fields'][$fieldKey] = $field;

        }
        return $out;
    }

    /**
     * Get filter fields
     */
    public static function getFilterFields()
    {
        $listingType = $_REQUEST['type'] ? $_REQUEST['type'] : '';
        $categoryID = $_REQUEST['category_id'] ? $_REQUEST['category_id'] : 0;

        if ($GLOBALS['plugins']['multiField'] && rl('GeoFilter', null, 'multiField')->geo_format) {
            $geoLocationController = new GeoLocationController();
            $geoLocationController->appliedLocation('lt_' . $listingType);
        }

        $filterFields = [];
        if (CategoryFilterController::isActive()) {
            CategoryFilterController::initFor(CategoryFilterController::FILTER_LISTING_TYPE);
            if (false !== $filterBoxId = CategoryFilterController::fetchBoxId($listingType, $categoryID)) {
                $filters = json_decode($_POST['filters'], true);

                CategoryFilterController::setBoxId($filterBoxId);
                CategoryFilterController::prepareFilters($filters);
                $filterFields = CategoryFilterController::applyFiltersToResponse();
            }
        }

        $response['filter_fields'] = $filterFields;
        return $response;
    }

    /*** DEPRECATED METHODS ***/

    /**
     * Get Phrase
     *
     * @deprecated 1.0.1
     *
     * @param string $key - key
     */
    private static function phraseByKey($key)
    {}
}
