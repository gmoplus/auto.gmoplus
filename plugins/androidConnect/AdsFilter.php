<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : ADSFILTER.PHP
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

/**
 * @since 4.2.0
 */
class AdsFilter
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
                $GLOBALS['reefless']->loadClass('CategoryFilter', null, 'categoryFilter');
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
            $where .= "AND FIND_IN_SET('{$category_id}', `Category_IDs`) ";

            $boxId = (int) $GLOBALS['rlDb']->getOne('ID', "`Mode` = '" . self::FILTER_CATEGORY . "' {$where}", 'category_filter');
            if ($boxId) {
                return $boxId;
            }
        }

        $boxId = (int) $GLOBALS['rlDb']->getOne('ID', "`Mode` = '{$box_type}' AND `Type` = '{$listing_type}'", 'category_filter');

        return $boxId ?: false;
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
        $fields = $GLOBALS['rlCategoryFilter']->getFilterFields(self::$boxId);

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
            $GLOBALS['rlCategoryFilter']->currency = $appFilters['currency'];
        }

        if ($filterData) {
            $GLOBALS['rlCategoryFilter']->filters = $filterData;
        }

        $sql = "SELECT `Content` FROM `{db_prefix}blocks` ";
        $sql .= "WHERE `Key` = 'categoryFilter_" . self::$boxId . "'";
        self::$filterBoxInfo = $GLOBALS['rlDb']->getRow($sql);

        // Save some smarty assigned values
        $GLOBALS['rlSmarty']->collectValuesForKeys(['cfInfo', 'cfFields', 'categories']);
    }

    /**
     * Apply filters to response
     *
     * @param array  $response - response array
     *
     **/
    public static function applyFiltersToResponse(&$response)
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
         * $GLOBALS['rlCategoryFilter']->request($filter_info, $filter_fields);
         */
        eval(self::$filterBoxInfo['Content']);

        if (empty($fields = $rlSmarty->valueByKey('cfFields'))) {
            return;
        }

        $response = [
            'filter_info' => $rlSmarty->valueByKey('cfInfo'),
            'selected_filters' => $filters = $GLOBALS['rlCategoryFilter']->filters,
            'filter_fields' => [],
        ];

        $categories = $rlSmarty->valueByKey('categories');
        $categories = array_column($categories, 'name', 'ID');

        foreach ($fields as $field) {
            $fieldKey = (string) $field['Key'];
            $fieldName = self::phraseByKey($field['pName']);
            $fieldItemsKey = 'Items';

            $items = [];

            if (!in_array($field['Type'], ['number', 'mixed']) || $field['Mode'] != 'slider' || $field['Mode'] != 'text') {

                if (($field['Type'] == 'checkbox' || $field['Mode'] == 'checkboxes') && $field['Values']) {

                    foreach (explode(',', $field['Values']) as $key => $item) {
                        $itemName = '';
                        $item_key = 'category_filter+name+' . $field['ID'] . '_' . $field['Field_ID'] . '_' . $item;

                        if (!$rlLang->getPhrase($item_key, null, null, true)) {
                            if ($field['Condition']) {
                                $item_key = 'data_formats+name+' . $item;
                            } else {
                                $item_key = 'listing_fields+name+' . $field['Key'] . '_' . $item;
                            }
                        }

                        $itemName = $lang[$item_key];

                        $items[] = [
                            'count' => 0,
                            'selected' => '',
                            'Key' => $item,
                            'name' => $itemName,
                        ];
                    }
                } else {
                    foreach ($field[$fieldItemsKey] as $key => $item) {
                        $itemKey = (string) $item[$fieldKey];
                        $itemSelected = (isset($filters[$fieldKey]) && $filters[$fieldKey] == $itemKey);
                        $itemName = '';

                        if ($field['Condition'] != '') {
                            if ($field['Condition'] == 'years') {
                                $itemName = $itemKey;
                            } else {
                                $itemName = self::phraseByKey('data_formats+name+' . $itemKey);
                            }
                        } elseif ($field['Type'] == 'checkbox') {

                            $itemName = self::phraseByKey('listing_fields+name+' . $itemKey);
                        } elseif ($field['Type'] == 'bool') {
                            $itemName = self::phraseByKey($itemKey == 1 ? 'yes' : 'no');
                        } elseif ($field['Values'] != '') {
                            $itemName = self::phraseByKey(sprintf('%s_%s', $field['pName'], $itemKey));
                        } elseif ($fieldKey == 'posted_by') {
                            $itemName = self::phraseByKey('account_types+name+' . $itemKey);
                        } elseif ($fieldKey == 'Category_ID') {
                            $itemName = !empty($categories[$itemKey]) ? (string) $categories[$itemKey] : 'category';
                        } else {

                            $item_lang_key = 'category_filter+name+' . $response['filter_info']['ID'] . '_' . $field['Field_ID'] . '_' . $key;
                            $itemName = self::phraseByKey($item_lang_key) ?: $key;
                            $itemKey = $key;
                        }

                        $items[] = [
                            'count' => (int) $item['Number'],
                            'selected' => $itemSelected,
                            'Key' => $itemKey,
                            'name' => $itemName,
                        ];
                    }
                }
            }

            if ($field['Type'] == 'price' && $field['Key'] == $config['price_tag_field']) {
                foreach ($GLOBALS['rlCategories']->getDF('currency') as $currency_item) {
                    $tmpVal['name'] = $currency_item['name'];
                    $tmpVal['Key'] = $currency_item['Key'];
                    $field['df'][] = $tmpVal;
                }
            }

            $field['Items'] = $items;
            $field['name'] = $GLOBALS['rlLang']->getPhrase(['key' => $field['pName'], 'db_check' => true]);
            $response['filter_fields'][] = $field;

        }
    }

    /**
     * Get Phare
     *
     * @param string $key - key
     *
     **/
    private static function phraseByKey($key)
    {
        $value = $GLOBALS['rlLang']->getPhrase(['key' => $key, 'db_check' => true]);
        return (is_string($value) && $value !== '') ? $value : $key;
    }
}
