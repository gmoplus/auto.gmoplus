<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLCATEGORYFILTER.CLASS.PHP
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

use Flynax\Utils\Category;
use Flynax\Utils\Valid;

class rlCategoryFilter extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * Template of system box for filter
     * @since 2.11.0 - Changed type from protected to public
     */
    public $boxTemplate = '
$filter_info = \'{filter_info}\';
$filter_fields = \'{filter_fields}\';

$GLOBALS[\'rlCategoryFilter\']->request($filter_info, $filter_fields);';

    /**
     * Search criteria from search results
     * @since 2.11.0 - Changed type from protected to public
     */
    public $criteriaSQL = '';

    /**
     * Search criteria from selected filters
     * @since 2.11.0 - Changed type from protected to public
     */
    public $filtersSQL = '';

    /**
     * Array of selected filters
     * @since 2.7.0 - Changed type from protected to public
     */
    public $filters = [];

    /**
     * Listing type in Field-Bound Boxes
     * @since 2.11.0 - Changed type from protected to public
     */
    public $fbbListingType = '';

    /**
     * Path in Field-Bound Boxes
     * @since 2.11.0 - Changed type from protected to public
     */
    public $fbbPath = '';

    /**
     * Value in Field-Bound Boxes
     * @since 2.11.0 - Changed type from protected to public
     */
    public $fbbValue = '';

    /**
     * Local alternative of IS_ESCORT constant
     * @todo - Remove it when compatible when compatibility will be >= 4.6.2
     *       - Use instead ($config['package_name'] === 'escort')
     */
    public $isEscort = false;

    /**
     * Show the need of tag "noindex, nofollow" in page
     * @since 2.11.0 - Changed type from protected to public
     */
    public $pageNoindex = false;

    /**
     * Shows the existence of the Currency Converter plugin
     * @since 2.11.0 - Changed type from protected to public
     * @var boolean
     */
    public $converterExist = false;

    /**
     * Shows the existence of the base price listing field
     * @var boolean
     */
    public $priceFieldExist = false;

    /**
     * Show the need to build HTML in box with filters
     * @since 2.11.0 - Changed type from protected to public
     * @var boolean
     */
    public $buildFilterBox = true;

    /**
     * Specials chars which must be replaced in filters
     * @since 2.11.0 - Changed type from protected to public
     * @since 2.6.0
     * @var   array
     */
    public $specialChars = [
        "'" => '-cfa-',
        '+' => '-cfp-',
        ':' => '-cfc-',
        '/' => '-cfs-',
        '&' => '-cfamp-',
    ];

    /**
     * Basic info about all filters in box
     *
     * @since 2.10.0 - Changed type from protected to public
     * @since 2.6.0
     * @var   array
     */
    public $filtersInfo = [];

    /**
     * Info about current used filter
     *
     * @since 2.10.0 - Changed type from protected to public
     * @since 2.7.0
     * @var   array
     */
    public $filterInfo = [];

    /**
     * @since 2.11.0 - Changed type from protected to public
     * @since 2.7.2
     * @var array
     */
    public $geoFilterData = [];

    /**
     * @since 2.11.0 - Changed type from protected to public
     * @since 2.7.3
     * @var array
     */
    public $multifieldFormatKeys = [];

    /**
     * Save the code of the selected currency in the price filter
     * @since 2.11.0
     * @var string
     */
    public $currency = '';

    /**
     * Class Constructor
     */
    public function __construct()
    {
        global $rlSmarty, $config, $rlGeoFilter;

        $this->isEscort = (isset($config['package_name'])
            ? $config['package_name'] === 'escort'
            : file_exists(RL_CLASSES . 'rlEscort.class.php')
        );

        $this->converterExist  = (bool) $GLOBALS['plugins']['currencyConverter'];
        $this->priceFieldExist = $GLOBALS['rlDb']->columnExists($config['price_tag_field'], 'listings');

        if (is_object($rlSmarty)) {
            $rlSmarty->register_function('encodeFilter', [$this, 'encodeFilter']);
            $rlSmarty->register_function('buildActiveFiltersURL', [$this, 'buildActiveFiltersURL']);
        }

        // Get location filter data from the Multifield/Geo-filter plugin
        if (is_object($rlGeoFilter) && $rlGeoFilter->geo_filter_data) {
            $this->geoFilterData = &$rlGeoFilter->geo_filter_data;
        } elseif (is_object($rlSmarty) && $rlSmarty->_tpl_vars['geo_filter_data']) {
            $this->geoFilterData = &$rlSmarty->_tpl_vars['geo_filter_data'];
        } elseif ($GLOBALS['geo_filter_data']) {
            $this->geoFilterData = &$GLOBALS['geo_filter_data'];
        }

        $formatKeys = $config['mf_format_keys'];
        $this->multifieldFormatKeys = $formatKeys ? explode('|', $formatKeys) : [];
    }

    /**
     * Creating new filter box
     *
     * @param array $filterData
     */
    public function createFilter($filterData)
    {
        global $rlDb, $config, $rlLang;

        if (!$filterData || false == is_array($filterData)) {
            return false;
        }

        $insertData = [
            'Mode'          => $filterData['Mode'],
            'Type'          => $filterData['Mode'] !== 'category' ? $filterData['Listing_type'] : '',
            'Category_IDs'  => $filterData['Mode'] === 'category' ? implode(',', $filterData['Categories']) : '',
            'Subcategories' => $filterData['Mode'] === 'category' && $filterData['Subcategories'] ? '1' : '0',
        ];

        if ($action['action'] = $rlDb->insertOne($insertData, 'category_filter')) {
            $filterID           = $rlDb->insertID();
            $action['filterID'] = $filterID;
            $filterKey          = "categoryFilter_{$filterID}";
            $boxNames           = [];

            foreach ($GLOBALS['allLangs'] as $langItem) {
                $namePhrase = [
                    'Code'   => $langItem['Code'],
                    'Module' => version_compare($config['rl_version'], '4.8.1', '>=') ? 'box' : 'common',
                    'Status' => 'active',
                    'Key'    => "blocks+name+{$filterKey}",
                    'Value'  => $GLOBALS['names'][$langItem['Code']],
                    'Plugin' => 'categoryFilter',
                ];

                if (version_compare($config['rl_version'], '4.8.1', '>=')) {
                    $namePhrase['Target_key'] = $filterKey;
                }

                $boxNames[] = $namePhrase;
            }

            if (method_exists($rlLang, 'createPhrases')) {
                $rlLang->createPhrases($boxNames);
            } else {
                $rlDb->insert($boxNames, 'lang_keys');
            }

            $rlDb->insertOne([
                'Key'           => $filterKey,
                'Status'        => $_POST['status'],
                'Position'      => 1,
                'Side'          => $filterData['Side'],
                'Type'          => 'php',
                'Tpl'           => $_POST['tpl'],
                'Page_ID'       => $filterData['Page_ids'] ? implode(',', $filterData['Page_ids']) : '',
                'Category_ID'   => $filterData['Mode'] === 'category'
                    ? implode(',', $filterData['Categories'])
                    : $this->getCategoryIDs($filterData['Listing_type']),
                'Subcategories' => $filterData['Subcategories'],
                'Sticky'        => 0,
                'Cat_sticky'    => $filterData['Mode'] === 'category'
                    ? 0
                    : (version_compare($config['rl_version'], '4.8.1', '<') ? 1 : 0),
                'Plugin'        => 'categoryFilter',
                'Content'       => $this->boxTemplate,
            ], 'blocks');
        } else {
            $GLOBALS['rlDebug']->logger('Cannot add a new filter box.');
        }

        return $action;
    }

    /**
     * Editing exist filter box
     *
     * @param array $filterData
     */
    public function editFilter($filterData)
    {
        global $names, $rlDb, $config, $rlLang;

        $updateData = [
            'fields' => [
                'Mode'          => $filterData['Mode'],
                'Type'          => $filterData['Mode'] !== 'category' ? $filterData['Listing_type'] : '',
                'Category_IDs'  => $filterData['Mode'] === 'category' ? implode(',', $filterData['Categories']) : '',
                'Subcategories' => $filterData['Mode'] === 'category' && $filterData['Subcategories'] ? '1' : '0',
            ],
            'where'  => ['ID' => $filterData['ID']],
        ];

        if ($action['action'] = $rlDb->updateOne($updateData, 'category_filter')) {
            $createPhrases = [];
            $updatePhrases = [];
            foreach ($GLOBALS['allLangs'] as $langItem) {
                $where = "`Key` = 'blocks+name+categoryFilter_{$filterData['ID']}' ";
                $where .= "AND `Code` = '{$langItem['Code']}'";

                if ($rlDb->getOne('ID', $where, 'lang_keys')) {
                    $updatePhrases[] = [
                        'fields' => ['Value' => $names[$langItem['Code']]],
                        'where'  => [
                            'Code' => $langItem['Code'],
                            'Key'  => "blocks+name+categoryFilter_{$filterData['ID']}",
                        ],
                    ];
                } else {
                    $filterName = [
                        'Code'   => $langItem['Code'],
                        'Module' => version_compare($config['rl_version'], '4.8.1', '>=') ? 'box' : 'common',
                        'Key'    => "blocks+name+categoryFilter_{$filterData['ID']}",
                        'Plugin' => 'categoryFilter',
                        'Value'  => $names[$langItem['Code']],
                        'Status' => 'active',
                    ];

                    if (version_compare($config['rl_version'], '4.8.1', '>=')) {
                        $filterName['Target_key'] = 'categoryFilter_' . $filterData['ID'];
                    }

                    $createPhrases[] = $filterName;
                }
            }

            if ($createPhrases) {
                if (method_exists($rlLang, 'createPhrases')) {
                    $rlLang->createPhrases($createPhrases);
                } else {
                    $rlDb->insert($createPhrases, 'lang_keys');
                }
            }

            if ($updatePhrases) {
                if (method_exists($rlLang, 'updatePhrases')) {
                    $rlLang->updatePhrases($updatePhrases);
                } else {
                    $rlDb->update($updatePhrases, 'lang_keys');
                }
            }

            $rlDb->updateOne([
                    'fields' => [
                        'Status'        => $_POST['status'],
                        'Side'          => $filterData['Side'],
                        'Tpl'           => $_POST['tpl'],
                        'Page_ID'       => $filterData['Page_ids'] ? implode(',', $filterData['Page_ids']) : '',
                        'Subcategories' => $filterData['Subcategories'],
                        'Category_ID'   => $filterData['Categories']
                            ? implode(',', $filterData['Categories'])
                            : $this->getCategoryIDs($filterData['Listing_type']),
                    ],
                    'where' => ['Key' => "categoryFilter_{$filterData['ID']}"],
            ], 'blocks');

            /*
             * Fix old blocks configuration
             * @todo - Remove when compatibility will be >= 4.8.1
             */
            if (version_compare($config['rl_version'], '4.8.1', '>=')
                && $filterData['Mode'] !== 'category'
                && '1' === $rlDb->getOne('Cat_sticky', "`Key` = 'categoryFilter_{$filterData['ID']}'", 'blocks')
            ) {
                $rlDb->updateOne([
                    'fields' => ['Cat_sticky' => '0'],
                    'where'  => ['Key' => "categoryFilter_{$filterData['ID']}"],
                ], 'blocks');
            }

            $this->recountFilters($filterData['ID']);
        } else {
            $GLOBALS['rlDebug']->logger("Cannot edit a filter box, ID: {$filterData['ID']}");
        }

        return $action;
    }

    /**
     * Save filter form
     *
     * @hook apAjaxBuildFormPostSaving
     *
     * @since 2.7.3 - Added $errors parameter
     *
     * @param array $data   - ID of category data of fields (format: ['category_id', 'data'])
     * @param array $errors
     */
    public function saveForm($data, &$errors = [])
    {
        global $rlDb;

        $boxID = (int) $data['category_id'];
        unset($data['data']['ordering']);

        if (!$data['category_id'] || !$boxID) {
            return false;
        }

        $boxInfo = $rlDb->fetch('*', ['ID' => $boxID], null, 1, 'category_filter', 'row');
        $fieldIDs = [];

        foreach ($data['data'] as $fieldID => $field) {
            if (!$fieldID) {
                continue;
            }

            $fieldIDs[] = explode('_', $fieldID)[1];
        }

        $sql = "SELECT `T1`.`ID`, `T1`.`Key`, `T1`.`Type`, `T1`.`Condition`, `T1`.`Values`, `T2`.`Items`, ";
        $sql .= "CONCAT('listing_fields+name+', `T1`.`Key`) AS `pName`, ";
        $sql .= "`T2`.`Items_display_limit`, `T2`.`Mode`, `T2`.`Item_names`, `T2`.`No_index`, ";
        $sql .= "`T2`.`Data_in_title`, `T2`.`Data_in_description`, `T2`.`Data_in_H1` ";
        $sql .= "FROM `{db_prefix}listing_fields` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}category_filter_field` AS `T2` ON `T2`.`Field_ID` = `T1`.`ID` ";
        $sql .= "AND `T2`.`Box_ID` = {$boxID} WHERE `T1`.`Status` = 'active' ";
        $sql .= "AND (`T1`.`ID` = '" . implode("' OR `T1`.`ID` = '", $fieldIDs) . "') ";
        $sql .= "ORDER BY FIND_IN_SET(`T1`.`ID`, '" . implode(',', $fieldIDs) . "')";
        $fieldsTmp = $rlDb->getAll($sql);

        foreach ($fieldsTmp as $fieldTmp) {
            // Save all values in cache (except database with locations)
            if ($fieldTmp['Condition'] && $fieldTmp['Condition'] !== 'countries') {
                $values = $rlDb->fetch(
                    ['Key'],
                    ['Status' => 'active', 'Plugin' => ''],
                    "AND `Key` LIKE '{$fieldTmp['Condition']}\_%'",
                    null,
                    'data_formats'
                );

                foreach ($values as $value) {
                    $fieldTmp['Values'] = $fieldTmp['Values']
                    ? $fieldTmp['Values'] . ',' . $value['Key']
                    : $value['Key'];
                }
            }

            $fields[$fieldTmp['Key']] = $fieldTmp;
        }
        unset($fieldsTmp);

        if ($fields) {
            if ($GLOBALS['plugins']['multiField']) {
                foreach ($fields as $field) {
                    if (false !== strpos($field['Key'], '_level')) {
                        $parentKey = preg_replace("/(_level[0-9])/", '', $field['Key']);

                        if (!in_array($parentKey, array_keys($fields))) {
                            $errors[] = $GLOBALS['rlLang']->getPhrase('cf_missing_parent_multifield', null, null, true);
                            break;
                        }
                    }
                }
            }

            // Clear form
            $rlDb->query("DELETE FROM `{db_prefix}category_filter_field` WHERE `Box_ID` = {$boxID}");

            $index  = 0;
            $insert = [];
            foreach ($fields as $field) {
                if ($field['ID']) {
                    $insert[$index] = [
                        'Box_ID'              => $boxID,
                        'Field_ID'            => $field['ID'],
                        'Items'               => $field['Items'],
                        'Item_names'          => $field['Item_names'],
                        'Items_display_limit' => $field['Items_display_limit'] ?: 8,
                        'Mode'                => $field['Mode'] ?: 'auto',
                        'Data_in_title'       => $field['Data_in_title'] ?: 1,
                        'Data_in_description' => $field['Data_in_description'] ?: 1,
                        'Data_in_H1'          => $field['Data_in_H1'],
                        'No_index'            => $field['No_index'],
                        'Status'              => $field['Status'] ?: 'active',
                    ];

                    if (in_array($field['Type'], ['number', 'price', 'mixed'])
                        || $field['Condition'] == 'years'
                    ) {
                        $insert[$index]['Mode'] = $field['Mode'] ?: 'text';
                    }

                    if ($field['No_index'] == '') {
                        $insert[$index]['No_index'] = $insert[$index]['Mode'] == 'slider'
                        || $insert[$index]['Mode'] == 'text'
                        || $field['Type'] == 'text'
                        ? '1'
                        : '0';
                    }

                    if ($field['Data_in_H1'] === null) {
                        $insert[$index]['Data_in_H1'] = $field['Key'] === 'Category_ID' ? '1' : '0';
                    }

                    $index++;
                }
            }

            $rlDb->insert($insert, 'category_filter_field');

            // Recount values of fields
            if ($boxInfo['Mode'] == 'category' || $boxInfo['Mode'] == 'type') {
                $update = [];

                foreach ($this->updateFields($boxID) as $field) {
                    if ($field['Key'] == 'Category_ID') {
                        $update[] = [
                            'fields' => ['Items' => $field['Items'] ? '1' : '0'],
                            'where'  => ['ID' => $field['ID']],
                        ];
                    } else {
                        $update[] = [
                            'fields' => ['Items' => $field['Items'] ? base64_encode(serialize($field['Items'])) : ''],
                            'where'  => ['ID' => $field['ID']],
                        ];
                    }
                }

                $rlDb->update($update, 'category_filter_field');
            }

            $this->enableHandlerToUpdateCounts($boxID);
            $this->updateSystemBox($boxID);
            $this->saveNoIndexFields();
        }
    }

    /**
     * Recount values of fields
     *
     * @param int $boxID - ID of filter box
     */
    public function updateFields($boxID)
    {
        global $config, $plugins, $category, $rlDb, $conversion_rates, $rlSearch, $rlSmarty, $page_info;

        $boxID      = (int) $boxID;
        $categoryID = (int) (is_array($category) ? $category['ID'] : $category);

        if (!$boxID) {
            return false;
        }

        $boxInfo = $this->filterInfo && $this->filterInfo['ID'] == $boxID
            ? $this->filterInfo
            : $rlDb->fetch('*', ['ID' => $boxID], null, 1, 'category_filter', 'row');

        // Get saved data about counts from Database
        if ($this->filters || in_array($boxInfo['Mode'], ['field_bound_boxes', 'search_results'])) {
            $selectedFilters = $this->filters ?: [];

            if ($plugins['multiField']
                && $this->geoFilterData
                && true === in_array($page_info['Key'], $this->geoFilterData['filtering_pages'])
            ) {
                $selectedFilters['geoFilterData'] = $this->geoFilterData['location_listing_fields'];
            }

            if ($boxInfo['Mode'] === 'field_bound_boxes' && $this->fbbValue) {
                $selectedFilters['fbbValue'] = $this->fbbValue;
            }

            if ($boxInfo['Mode'] === 'search_results') {
                $selectedFilters['searchCriteria'] = $_REQUEST && $_REQUEST['f'] ? $_REQUEST['f'] : [];
            }

            $selectedFilters = json_encode($selectedFilters);

            $savedCounts = $rlDb->fetch(
                ['Data_counts', 'Update_handler'],
                ['Filter_ID' => $boxID, 'Category_ID' => $categoryID, 'Selected_filters' => $selectedFilters],
                null, 1, 'category_filter_counts', 'row'
            );

            if (!$savedCounts || ($savedCounts && $savedCounts['Update_handler'] === '1')) {
                $updateSavedCounts = true;
            } else {
                $decodedSavedCounts = json_decode($savedCounts['Data_counts'], true);

                // Update counts for categories by selected filters
                foreach ($decodedSavedCounts as $decodedSavedCount) {
                    if ($decodedSavedCount['categoryCounts']) {
                        $rlSmarty->assign('cfCategoryCounts', $decodedSavedCount['categoryCounts']);
                        break;
                    }
                }

                return $decodedSavedCounts;
            }
        }

        if ($boxInfo['Mode'] == 'category') {
            $selectedCategories = explode(',', $boxInfo['Category_IDs']);

            if ($boxInfo['Subcategories']
                && is_array($category)
                && $category['ID']
                && false === array_search($category['ID'], $selectedCategories)
            ) {
                $selectedCategories[] = $category['ID'];
            }
        }

        if ($category && defined('CRON_FILE')) {
            unset($category);
        }

        $fields = $this->getFilterFields($boxID);

        if ($fields) {
            foreach ($fields as &$field) {
                // Categories already have count in first condition
                if ($field['Key'] == 'Category_ID' && is_array($category) && !$category['ID'] && !$this->filters) {
                    if (in_array($boxInfo['Mode'], ['type', 'category'])) {
                        continue;
                    }
                }

                $sql = "SELECT ";

                switch ($field['Key']) {
                    case 'posted_by':
                        $sql .= "COUNT(`T7`.`Type`) AS `Number`, `T7`.`Type` AS '{$field['Key']}' ";
                        break;

                    case 'Category_ID':
                        $sql .= "COUNT(`T1`.`{$field['Key']}`) AS `Number`, `T1`.`{$field['Key']}`, `T3`.`Parent_IDs` ";
                        break;

                    default:
                        $sql .= "COUNT(`T1`.`{$field['Key']}`) AS `Number` ";

                        // Currency converter condition
                        if (in_array($field['Type'], ['price', 'mixed'])) {
                            $sql .= ", SUBSTRING_INDEX(`T1`.`{$field['Key']}`, '|', 1) AS `{$field['Key']}` ";
                        } else {
                            $sql .= ", `T1`.`{$field['Key']}` ";
                        }
                        break;
                }

                // Search by categories
                if ($selectedCategories) {
                    foreach ($selectedCategories as $sCategoryID) {
                        $sql .= ', SUM(IF(';
                        $sql .= "`T1`.`Category_ID` = {$sCategoryID} ";
                        $sql .= "OR (FIND_IN_SET({$sCategoryID}, `T1`.`Crossed`) > 0 AND `T2`.`Cross` > 0) ";

                        if ($config['lisitng_get_children']) {
                            $sql .= "OR (FIND_IN_SET({$sCategoryID}, `T3`.`Parent_IDs`) > 0) ";
                        }

                        $sql .= ", 1, 0)) AS `Category_count_{$sCategoryID}` ";
                    }
                }

                $sql .= 'FROM `{db_prefix}listings` AS `T1` ';
                $sql .= 'LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ';

                if ($field['Key'] === 'posted_by' || !empty($this->filters['posted_by'])) {
                    $sql .= 'LEFT JOIN `{db_prefix}accounts` AS `T7` ON `T1`.`Account_ID` = `T7`.`ID` ';
                }

                if ($selectedCategories) {
                    $sql .= 'LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ';
                }

                // Join lang_keys table if search have keywordSearch request
                if ($boxInfo['Mode'] == 'search_results' && $this->criteriaSQL) {
                    // join names of categories for keyword search
                    if (false !== strpos($this->criteriaSQL, '`TL`.`Value`')) {
                        $sql .= 'LEFT JOIN `{db_prefix}lang_keys` AS `TL` ON `TL`.`Key` ';
                        $sql .= "= CONCAT('categories+name+',`T3`.`Key`) AND `TL`.`Code` = '" . RL_LANG_CODE . "' ";
                    }

                    // Join rates of fields with enabled conversion
                    if ($rlSearch->fields && $conversion_rates && false !== strpos($this->criteriaSQL, '(`TDF_')) {
                        foreach ($rlSearch->fields as $fVal) {
                            if ($conversion_rates[$fVal['Condition']] && $fVal['Condition']) {
                                $sql .= "LEFT JOIN `{db_prefix}data_formats` AS `TDF_{$fVal['Condition']}` "
                                    . "ON `TDF_{$fVal['Condition']}`.`Key` = "
                                    . "SUBSTRING_INDEX(`T1`.`{$fVal['Key']}`, '|', -1) ";
                            }
                        }
                    }
                }

                if ($this->converterExist) {
                    $sql .= 'LEFT JOIN `{db_prefix}currency_rate` AS `CURCONV` ';
                    $sql .= "ON SUBSTRING_INDEX(REPLACE(`T1`.`{$field['Key']}`, ";
                    $sql .= "'currency_', ''), '|', -1) = `CURCONV`.`Key` ";
                    $sql .= "AND `CURCONV`.`Status` = 'active' ";
                }

                $GLOBALS['rlHook']->load('listingsModifyJoin', $sql);

                if ($this->converterExist
                    && false === strpos($sql, 'AS `CURCONV`')
                    && $this->priceFieldExist
                    && ($_SESSION['curConv_code'] || $_COOKIE['curConv_code'])
                ) {
                    $sql .= 'LEFT JOIN `{db_prefix}currency_rate` AS `CURCONV` ';
                    $sql .= "ON SUBSTRING_INDEX(REPLACE(`T1`.`{$config['price_tag_field']}`, ";
                    $sql .= "'currency_', ''), '|', -1) = ";
                    $sql .= "`CURCONV`.`Key` AND `CURCONV`.`Status` = 'active' ";
                }

                if ($plugins['booking'] && $boxInfo['Mode'] === 'search_results' && $this->criteriaSQL) {
                    $GLOBALS['rlBooking']->hookListingsModifyJoinSearch($sql, $GLOBALS['data']);
                }

                $sql .= "WHERE `T1`.`Status` = 'active' ";

                if ($plugins['listing_status']) {
                    $sql .= "AND `T1`.`Sub_status` <> 'invisible' ";
                }

                if ($boxInfo['Mode'] == 'type' || $boxInfo['Mode'] == 'search_results') {
                    $sql .= "AND `T3`.`Type` = '{$boxInfo['Type']}' ";

                    if ($category['ID']) {
                        $sql .= "AND ((`T1`.`Category_ID` = {$category['ID']} OR FIND_IN_SET({$category['ID']}, ";
                        $sql .= "`T3`.`Parent_IDs`) > 0) OR (FIND_IN_SET({$category['ID']}, `T1`.`Crossed`) > 0)) ";
                    }
                } elseif ($boxInfo['Mode'] == 'category') {
                    $sql .= 'AND (';

                    foreach ($selectedCategories as $tmp_category) {
                        $sql .= $config['lisitng_get_children'] ? '(' : '';
                        $sql .= "(`T1`.`Category_ID` = {$tmp_category} OR (FIND_IN_SET({$tmp_category}, ";
                        $sql .= "`T1`.`Crossed`) > 0 AND `T2`.`Cross` > 0)) ";

                        if ($config['lisitng_get_children']) {
                            $sql .= "OR FIND_IN_SET({$tmp_category}, `T3`.`Parent_IDs`) > 0)";
                        }

                        $sql .= 'OR ';
                    }

                    $sql = rtrim($sql, 'OR ');
                    $sql .= ') ';
                } elseif ($boxInfo['Mode'] === 'field_bound_boxes') {
                    $fbbField = $GLOBALS['fbb_info'] ? $GLOBALS['fbb_info']['Field_key'] : $GLOBALS['field'];

                    if ($plugins['fieldBoundBoxes'] && $fbbField && $this->fbbValue) {
                        global $rlFieldBoundBoxes, $field_info, $item_info;

                        $fbbFieldCondition = $field_info && $field_info['Condition']
                            ? $field_info['Condition']
                            : $rlDb->getOne('Condition', "`Key` = '{$fbbField}'", 'listing_fields');

                        if (defined('FBB_MULTIFIELD_MODE') && method_exists($rlFieldBoundBoxes, 'getRelatedFields')) {
                            if ($multifields = $rlFieldBoundBoxes->getRelatedFields($fbbFieldCondition, $fbbField)) {
                                $fbbValue = $item_info && $item_info['Key'] ? $item_info['Key'] : $this->fbbValue;

                                $sql .= 'AND (';
                                foreach ($multifields as $multifield) {
                                    $sql .= "`T1`.`{$multifield['Key']}` = '{$fbbValue}' OR ";
                                }
                                $sql = substr($sql, 0, -4) . ') ';
                            }
                        } else {
                            if ($fbbField == 'posted_by') {
                                $sql .= "AND (`T7`.`Type` = '{$this->fbbValue}'";
                            } else {
                                $sql .= "AND (`T1`.`{$fbbField}` = '{$this->fbbValue}'";

                                if ($item_info && $item_info['Key']) {
                                    $itemKey = explode('_', $item_info['Key']);
                                    $itemKey = is_array($itemKey) ? end($itemKey) : null;

                                    if ($itemKey) {
                                        $sql .= " OR `T1`.`{$fbbField}` = '{$itemKey}'";
                                    }
                                }
                            }

                            if ($fbbFieldCondition) {
                                if ($item_info && $item_info['Key']) {
                                    $sql .= " OR `T1`.`{$fbbField}` = '{$item_info['Key']}'";
                                } else {
                                    $fbbValue = $GLOBALS['rlValid']->str2key($this->fbbValue);
                                    $sql .= " OR `T1`.`{$fbbField}` = '{$fbbFieldCondition}_{$this->fbbValue}'";
                                    $sql .= " OR `T1`.`{$fbbField}` = '{$fbbValue}'";
                                }
                            }

                            $sql .= ') ';

                            if ($this->fbbListingType) {
                                $sql .= "AND `T3`.`Type` = '{$this->fbbListingType}' ";
                            }
                        }

                        $this->getSqlByFilters($GLOBALS['categoryFilter_activeBoxID']);
                    }
                }

                if ($field['Key'] != 'posted_by' && $field['Key'] != 'Category_ID') {
                    $sql .= "AND `T1`.`{$field['Key']}` <> '' ";

                    if ($field['Type'] != 'bool') {
                        $sql .= "AND `T1`.`{$field['Key']}` <> '0' ";
                    }
                }

                if ($boxInfo['Mode'] == 'search_results' && $this->criteriaSQL) {
                    $sql .= $this->criteriaSQL . ' ';
                }

                // Add criteria of exist filters
                if ($this->filtersSQL && !defined('CRON_FILE')) {
                    // Don't update counter for fields in slider mode if another filters do not exist
                    if ($field['Mode'] == 'slider') {
                        if ((isset($this->filters[$field['Key']]) && count($this->filters) > 1)
                            || !isset($this->filters[$field['Key']])
                        ) {
                            $sql .= $this->filtersSQL;
                        }
                    }
                    // Update count of listings by filters (except checkboxes)
                    else {
                        $sql .= $field['Mode'] != 'checkboxes' && $field['Type'] != 'checkbox'
                        ? $this->filtersSQL
                        : '';
                    }
                }

                if ($plugins['multiField']
                    && $this->geoFilterData
                    && $this->geoFilterData['is_filtering'] === true
                    && $this->geoFilterData['location_listing_fields']
                    && !$GLOBALS['rlGeoFilter']->searchResultsPage
                ) {
                    foreach ($this->geoFilterData['location_listing_fields'] as $geoField => $geoValue) {
                        if (empty($geoValue)) {
                            continue;
                        }

                        $sql .= "AND `T1`.`{$geoField}` = '{$geoValue}' ";
                    }
                }

                if (in_array($field['Type'], ['price', 'mixed'])) {
                    $sql .= "GROUP BY SUBSTRING_INDEX(`T1`.`{$field['Key']}`, '|', 1) ";
                } else {
                    $sql .= "GROUP BY " . ($field['Key'] == 'posted_by' ? "`T7`.`Type` " : "`T1`.`{$field['Key']}` ");
                }

                $sql .= 'HAVING COUNT(' . ($field['Key'] == 'posted_by'
                ? "`T7`.`Type` "
                : "`T1`.`{$field['Key']}`") . ") > 0 ";
                $sql .= 'ORDER BY `Number` DESC ';

                $items = $rlDb->getAll($sql);

                // Recount values of parent categories
                if ($field['Key'] == 'Category_ID') {
                    $cfCategoryCounts = $items;

                    foreach ($cfCategoryCounts as $item) {
                        $categoryCount = $item['Number'];

                        if ($item['Parent_IDs']) {
                            foreach (explode(',', $item['Parent_IDs']) as $parentID) {
                                foreach ($cfCategoryCounts as $categoryKey => $categoryData) {
                                    if ($categoryData['Category_ID'] == $parentID) {
                                        $cfCategoryCounts[$categoryKey]['Number'] += $categoryCount;
                                    }
                                }
                            }
                        }
                    }

                    // Update count of not exist parent categories
                    if ($cfCategoryCounts) {
                        // Replacing array keys to category ids
                        foreach ($cfCategoryCounts as $categoryCount) {
                            $tmpCategoryCounts[$categoryCount['Category_ID']] = $categoryCount;
                        }

                        foreach ($tmpCategoryCounts as $tmpCategoryKey => $tmpCategoryData) {
                            foreach (explode(',', $tmpCategoryData['Parent_IDs']) as $pCategoryID) {
                                if ((int) $pCategoryID) {
                                    $number = $tmpCategoryCounts[$tmpCategoryKey]['Number'];

                                    if (!$tmpCategoryCounts[$pCategoryID]) {
                                        $tmpCategoryCounts[$pCategoryID]['Number']      = $number;
                                        $tmpCategoryCounts[$pCategoryID]['Category_ID'] = $pCategoryID;
                                        $tmpCategoryCounts[$pCategoryID]['Recounted']   = true;
                                    } elseif ($tmpCategoryCounts[$pCategoryID]
                                        && $tmpCategoryCounts[$pCategoryID]['Recounted'] === true
                                    ) {
                                        $tmpCategoryCounts[$pCategoryID]['Number'] += $number;
                                    }
                                }
                            }
                        }

                        $field['categoryCounts'] = $cfCategoryCounts = $tmpCategoryCounts;
                    }

                    if (!defined('REALM')) {
                        $rlSmarty->assign('cfCategoryCounts', $cfCategoryCounts);
                    }
                } else {
                    $field['Items'] = $items;

                    $field = $this->prepareValues($field, $boxInfo);
                }
            }
        }

        if (($this->filters
                || (in_array($boxInfo['Mode'], ['field_bound_boxes', 'search_results']) && $selectedFilters)
            )
            && $updateSavedCounts
            && !IS_BOT
        ) {
            $rlDb->rlAllowHTML = true;

            if (!$savedCounts) {
                self::internalCacheSizeHandler();

                $rlDb->insertOne([
                    'Filter_ID'        => $boxID,
                    'Category_ID'      => $categoryID,
                    'Selected_filters' => $selectedFilters,
                    'Data_counts'      => json_encode($fields),
                ], 'category_filter_counts');
            } else {
                $rlDb->updateOne([
                    'fields' => [
                        'Data_counts'    => json_encode($fields),
                        'Update_handler' => '0',
                    ],
                    'where'  => [
                        'Filter_ID'        => $boxID,
                        'Category_ID'      => $categoryID,
                        'Selected_filters' => $selectedFilters,
                    ]
                ], 'category_filter_counts');
            }
        }

        return $fields;
    }

    /**
     * Prepare values of fields with multi mode
     *
     * @param  array      $field
     * @param  array      $boxInfo
     * @return bool|array
     */
    public function prepareValues($field, $boxInfo)
    {
        if (!$field || !is_array($field) || !$boxInfo) {
            return false;
        }

        if ($field['Condition'] == 'years' && $field['Mode'] == 'slider' && $field['Type'] == 'select') {
            $field['Type'] = 'number';
        }

        // Optimize results
        switch ($field['Type']) {
            case 'number':
            case 'mixed':
            case 'price':
                if (($field['Mode'] == 'auto' || $field['Mode'] == 'group') && $field['Type'] == 'price') {
                    $field['Mode'] = $field['Mode'] == 'auto' ? 'slider' : $field['Mode'];

                    if ($field['Mode'] == 'slider') {
                        break;
                    }
                } elseif ($field['Mode'] == 'slider') {
                    // Create array with counter and values
                    foreach ($field['Items'] as $value) {
                        if ($boxInfo['Mode'] != 'category') {
                            $items[$value[$field['Key']]] = ['-1' => $value['Number']];
                        } else {
                            foreach ($value as $keyValue => $val) {
                                if (false !== strpos($keyValue, 'Category_count_')) {
                                    $valuesByCategory[$keyValue] = $val;
                                }
                            }

                            $items[$value[$field['Key']]] = $valuesByCategory;
                        }
                    }

                    $field['Items'] = $items;

                    if ($field['Condition'] == 'years') {
                        $field['Type'] = 'select';
                    }
                    break;
                }

                if ($field['Items'] || $field['Mode'] != 'slider') {
                    // Create array with count and values
                    foreach ($field['Items'] as $value) {
                        if ($boxInfo['Mode'] != 'category') {
                            $items[$value[$field['Key']]] = ['-1' => $value['Number']];
                        } else {
                            foreach ($value as $keyValue => $val) {
                                if (false !== strpos($keyValue, 'Category_count_')) {
                                    $valuesByCategory[$keyValue] = $val;
                                }
                            }

                            $items[$value[$field['Key']]] = $valuesByCategory;
                        }
                    }

                    if ($field['Item_names']) {
                        $itemNames = unserialize(base64_decode($field['Item_names']));
                        $itemsNew  = [];

                        foreach ($itemNames as $key => $value) {
                            preg_match('/([0-9|min]+)?([\<\-\>]+)?([0-9|max]+)?/', $key, $matches);

                            $from = $matches[1];
                            $to   = $matches[3];

                            if ($matches[1] == 'min') {
                                $sign = '<';
                            } elseif ($matches[3] == 'max') {
                                $sign = '>';
                            } else {
                                $sign = $matches[2];
                            }

                            foreach ($items as $itemKey => $item) {
                                if (($sign == '-' && $itemKey >= $from && $itemKey <= $to)
                                    || ($sign == '>' && $itemKey > $from)
                                    || ($sign == '<' && $itemKey < $to)
                                ) {
                                    $itemsNew[$key] = $this->flArraySum($item, $itemsNew[$key]);
                                }
                            }
                        }

                        // Re-assign items if there is at least one item after rendering
                        $field['Items'] = $itemsNew ?: '';
                    }

                    unset($itemsNew);
                }
                break;

            case 'select':
                if ($field['Condition'] == 'years' && !$field['Mode']) {
                    $field['Mode'] = 'slider';
                }

                if ($field['Mode'] == 'checkboxes' && $field['Items']) {
                    $values = '';
                    foreach ($field['Items'] as $key => $value) {
                        $values = $values ? ($values . ',' . $value[$field['Key']]) : $value[$field['Key']];
                    }

                    $field['Values'] = $values;
                }
                break;
            case 'checkbox':
                // Removing values without count of listings
                if (is_array($field['Items']) && $field['Values']) {
                    $existValues = [];

                    foreach (explode(',', $field['Values']) as $value) {
                        foreach ($field['Items'] as $itemValue) {
                            $itemValues = explode(',', $itemValue[$field['Key']]);

                            if (false !== array_search($value, $itemValues)
                                && false === array_search($value, $existValues)
                            ) {
                                $existValues[] = $value;
                            }
                        }
                    }

                    $field['Values'] = $existValues ? implode(',', $existValues) : '';
                }
                break;
        }

        return $field;
    }

    /**
     * Update content of box with filter
     *
     * @param  int  $boxID
     * @return bool
     */
    public function updateSystemBox($boxID)
    {
        global $rlDb;

        $boxID = (int) $boxID;

        if (!$boxID) {
            return false;
        }

        $boxInfo  = $rlDb->fetch('*', ['ID' => $boxID], null, 1, 'category_filter', 'row');
        $sBoxInfo = $rlDb->fetch('*', ['Key' => "categoryFilter_{$boxID}"], null, 1, 'blocks', 'row');
        $fields   = $this->getFilterFields($boxID, true);

        if (!$boxInfo || !$sBoxInfo) {
            $GLOBALS['rlDebug']->logger("Category Filter: box don't exist with ID: {$boxID}");
            return false;
        }

        // Add content to new filter box
        if (false !== strpos($sBoxInfo['Content'], '{filter_info}')) {
            $origin = $sBoxInfo['Content'];
        }
        // Update content in exist filter box
        else {
            $origin = $this->boxTemplate;
        }

        $fInfo = base64_encode(serialize($boxInfo));
        $fFields = $boxInfo['Mode'] == 'category' || $boxInfo['Mode'] == 'type'
        ? base64_encode(serialize($fields))
        : '';

        $sBoxInfo['Content'] = str_replace(['{filter_info}', '{filter_fields}'], [$fInfo, $fFields], $origin);
        $update = ['fields' => ['Content' => $sBoxInfo['Content']], 'where'  => ['ID' => $sBoxInfo['ID']]];

        $rlDb->rlAllowHTML = true;
        $rlDb->updateOne($update, 'blocks');
        $rlDb->rlAllowHTML = false;

        return true;
    }

    /**
     * Build a filter box
     *
     * @param  string      $filterInfo
     * @param  string      $filterFields
     * @return bool|array                - Filters for sitemap | True
     */
    public function request($filterInfo, $filterFields)
    {
        global $rlSmarty, $category, $categories, $listing_type, $rlCategories, $reefless, $page_info, $config, $rlDb;

        if (!$filterInfo) {
            return false;
        }

        $this->filterInfo = unserialize(base64_decode($filterInfo));

        // Prepare workspace for getting filter urls
        if (!$this->buildFilterBox) {
            require_once RL_LIBS . 'smarty/Smarty.class.php';
            $reefless->loadClass('Smarty');
            $reefless->loadClass('Hook');

            $page_info['Path'] = $GLOBALS['pages']['lt_' . $listing_type['Key']];

            $rlSmarty->assign_by_ref('config', $GLOBALS['config']);
            $rlSmarty->assign_by_ref('pageInfo', $page_info);
            $rlSmarty->assign_by_ref('category', $category);
            $rlSmarty->assign_by_ref('listing_type', $listing_type);
            $rlSmarty->register_function('encodeFilter', [$this, 'encodeFilter']);
            $rlSmarty->register_function('buildActiveFiltersURL', [$this, 'buildActiveFiltersURL']);
            $rlSmarty->register_function('phrase', [$GLOBALS['rlLang'], 'getPhrase']);
        }

        $cCondition = false;
        if ($this->filterInfo['Mode'] === 'category' || $this->filterInfo['Mode'] === 'type') {
            // Check category condition
            if ($this->filterInfo['Mode'] === 'category') {
                $selectedCategories = explode(',', $this->filterInfo['Category_IDs']);

                foreach ($selectedCategories as $sCategoryID) {
                    if ($category['ID'] == $sCategoryID) {
                        $cCondition = true;
                        break;
                    }
                }
            }

            // Decoding of filter fields from cache
            if (!$this->filters
                && !$this->geoFilterData['location_keys']
                && (($this->filterInfo['Mode'] === 'type' && !$category['ID'])
                    || ($this->filterInfo['Mode'] === 'category' && $cCondition)
                )
            ) {
                $filterFields = $filterFields ? unserialize(base64_decode($filterFields)) : '';

                foreach ($filterFields as &$field) {
                    if ($field['Key'] != 'Category_ID' && $field['Items']) {
                        $field['Items'] = unserialize(base64_decode($field['Items']));
                    }
                }
            } else {
                $this->getSqlByFilters($this->filterInfo['ID']);
                $filterFields = $this->updateFields($this->filterInfo['ID']);
            }

            // Get categories if they're missing (Listing Type->Cat_position = hide)
            if (!$categories && isset($filterFields['Category_ID'])) {
                $categories = $rlCategories->getCategories(
                    $category['ID'],
                    $this->filterInfo['Type'] ?: $listing_type['Key'],
                    null,
                    true
                );
            }
        } else {
            $filterFields = $this->updateFields($this->filterInfo['ID']);
            $categoryID   = $this->filters['category_id'] ? (int) $this->filters['category_id'] : false;

            $category = $rlCategories->getCategory($categoryID);
            $rlSmarty->assign_by_ref('category', $category);

            if ($this->filterInfo['Mode'] == 'field_bound_boxes' && $this->fbbListingType) {
                $rlSmarty->assign_by_ref('listing_type', $GLOBALS['rlListingTypes']->types[$this->fbbListingType]);
            }

            if (isset($filterFields['Category_ID'])) {
                $categories = $rlCategories->getCategories(
                    $categoryID,
                    $this->filterInfo['Mode'] == 'field_bound_boxes' ? $this->fbbListingType : $this->filterInfo['Type'],
                    null,
                    true
                );
            }
        }

        if ($filterFields) {
            // Build category count prefix ("-1" for listing type)
            if (!$category['ID']) {
                $categoryPrefix = '-1';
            } else {
                $categoryPrefix = $this->filterInfo['Mode'] === 'category' ? "Category_count_{$category['ID']}" : '-1';
            }

            $filterFields = $this->updatePriceByRate($filterFields, $categoryPrefix);
            $filterFields = $this->updateYears($filterFields);
            $filterFields = $this->updateStepMinMax($filterFields, $categoryPrefix, $this->filterInfo, $cCondition);
            $filterFields = $this->updateMultiFields($filterFields, $this->filterInfo);
        }

        if ($GLOBALS['pInfo'] && isset($GLOBALS['pInfo']['calc'])) {
            $this->filterInfo['Total_listings_count'] = (int) $GLOBALS['pInfo']['calc'];
        }

        $rlSmarty->assign('cfInfo', $this->filterInfo);
        $rlSmarty->assign('cfFields', $filterFields);

        $countActiveFilters = $this->filters ? count($this->filters) : 0;
        $rlSmarty->assign('cfCountActiveFilters', $countActiveFilters);

        if ($this->filters) {
            $rlSmarty->assign('cfActiveFilters', $this->filters);
        }

        $clearFiltersLink = false;

        if ($this->filterInfo['Mode'] === 'type') {
            if ($countActiveFilters > 1) {
                $clearFiltersLink = true;
            } else {
                if ($countActiveFilters == 1 && $category['ID']) {
                    $clearFiltersLink = true;
                }
            }
        } else {
            if ($countActiveFilters > 1) {
                $clearFiltersLink = true;
            }
        }

        // Configure the data for the Category Box component
        if ($filterFields['Category_ID'] && version_compare($config['rl_version'], '4.9.3', '>=')) {
            $block       = &$rlSmarty->_tpl_vars['block'];
            $lTypeKey    = $this->filterInfo['Type'] ?: '';
            $catBlockKey = $lTypeKey ? "ltcategories_{$lTypeKey}" : '';
            $catBlock    = $catBlockKey ? $rlDb->fetch('*', ['Key' => $catBlockKey], null, null, 'blocks', 'row') : [];

            if ($catBlock && $catBlock['Options']) {
                $block['Options'] = json_decode($catBlock['Options'], true);
            } else {
                // Emulating the default data for the Category Box component
                $block['Options'] = [
                    'categories_style' => [
                        'type'    => 'select',
                        'default' => 'column_fill',
                        'values'  => ['column_fill', 'grid']
                    ],
                    'visible_categories' => [
                        'type'    => 'number',
                        'default' => 0,
                        'values'  => 0,
                    ],
                    'display_subcategories' => [
                        'type'    => 'boolean',
                        'default' => true,
                    ],
                    'subcategories_number' => [
                        'type'    => 'number',
                        'default' => 0,
                        'values'  => 0,
                    ],
                    'more_subcategories_style' => [
                        'type'    => 'select',
                        'default' => 'more_subcategories_popup',
                        'values'  => ['more_subcategories_popup', 'more_subcategories_link'],
                    ],
                    'display_counter' => [
                        'type'    => 'boolean',
                        'default' => true,
                    ]
                ];
            }
        }

        if ($categories) {
            if (isset($filterFields['Category_ID']['categoryCounts'])
                || isset($filterFields['Category_ID']['Items'])
            ) {
                self::updateCountsInCategories(
                    $categories,
                    $filterFields['Category_ID']['categoryCounts'],
                    (bool) $filterFields['Category_ID']['Items']
                );
            }

            $rlSmarty->assign_by_ref('categories', $categories);
        }

        $rlSmarty->assign('cfClearFiltersLink', $clearFiltersLink);
        $rlSmarty->assign_by_ref('cfBaseUrl', $this->buildCfBaseUrl($this->filterInfo));
        $rlSmarty->assign_by_ref('cfCancelUrl', $this->buildCfBaseUrl($this->filterInfo, true));

        $boxTplFile = RL_PLUGINS . 'categoryFilter/box.tpl';
        if ($this->buildFilterBox) {
            $rlSmarty->display($boxTplFile);
            return true;
        } else {
            $urls       = [];
            $boxContent = $rlSmarty->fetch($boxTplFile, null, null, false);

            preg_match_all("/<a href\=\"(https?:\/\/[^\"]*)\"[^\>]*/smi", $boxContent, $matches);

            if ($matches[0] && $matches[1]) {
                foreach ($matches[1] as $key => $url) {
                    if (false === strpos($matches[0][$key], 'rel="nofollow"')) {
                        $urls[] = $url;
                    }
                }
            }

            return $urls;
        }
    }

    /**
     * Build base url of filters
     *
     * @param array $filterInfo
     * @param bool  $cancelFilters - Cancel active filters
     */
    public function buildCfBaseUrl($filterInfo, $cancelFilters = false)
    {
        global $config, $category, $listing_type, $search_results_url, $reefless, $page_info;

        $mode = $filterInfo['Mode'];

        if ($mode == 'field_bound_boxes') {
            $cfBaseUrl = SEO_BASE;

            if ($this->fbbPath && $this->fbbValue) {
                if ($config['mod_rewrite']) {
                    $cfBaseUrl = $reefless->getPageUrl($page_info['Key'], [$this->fbbValue]);
                } else {
                    $cfBaseUrl = $reefless->getPageUrl($page_info['Key'], null, null, "{$this->fbbPath}={$this->fbbValue}");
                }
            }
        } else {
            if ($mode == 'search_results') {
                $cfBaseUrl = $reefless->getPageUrl("lt_{$listing_type['Key']}", [$search_results_url]);
            } else {
                if ($category['ID'] && (!$cancelFilters || $filterInfo['Mode'] == 'category')) {
                    $cfBaseUrl = $reefless->getCategoryUrl($category);
                } else {
                    $cfBaseUrl = $reefless->getPageUrl('lt_' . $listing_type['Key']);
                }
            }

            if (!$cancelFilters && substr($cfBaseUrl, -5) == '.html') {
                $cfBaseUrl = substr($cfBaseUrl, 0, strlen($cfBaseUrl) - 5) . '/';
            }
        }

        return $cfBaseUrl;
    }

    /**
     * Generate step for slider field; update MIN and MAX values if it necessary
     *
     * @param array $filter - Field info (in slider mode only)
     * @return bool|array
     */
    public function getStepMinMax($filter)
    {
        $min = (int) floor($filter['Minimum']);
        $max = (int) ceil($filter['Maximum']);

        if (!is_numeric($min) || !is_numeric($max)) {
            return false;
        } else {
            if ($max < $min) {
                $filter['Minimum'] = 0;
                $filter['Maximum'] = 0;
                $filter['Step']    = 1;

                return $filter;
            }
        }

        // Separate difference by ~100 parts
        $stepParts = $max > 100 ? 250 : 100;
        $step      = ceil(($max - $min) / $stepParts);

        // Max step must be 50000
        $step = $step > 50000 ? 50000 : $step;

        // Rounding of step by length
        $stepLength = strlen($step);

        if ($stepLength >= 4) {
            $step = round($step, -3);
        } elseif ($stepLength == 3 || ($stepLength == 2 && $step > 50)) {
            $step = round($step, -2);
        }

        // Updating of step, if min and max have big values
        if ($step > 1) {
            if ($step % 5 != 0) {
                for ($i = 1; $i <= 5; $i++) {
                    if (($step + $i) % 5 == 0) {
                        $step += $i;
                        break;
                    }
                }
            }

            // Decrease MIN, if MIN isn't multiple of step
            if ($min % $step != 0) {
                for ($i = 1; $i <= $min; $i++) {
                    if (($min - $i) % $step == 0) {
                        $min -= $i;
                        break;
                    }
                }
            }

            // Increase MAX, if MAX isn't multiple of step
            if ($max % $step != 0) {
                for ($i = 1; $i <= $max; $i++) {
                    if (($max + $i) % $step == 0) {
                        $max += $i;
                        break;
                    }
                }
            }
        }

        $filter['Minimum'] = is_numeric($min) ? $min : $filter['Minimum'];
        $filter['Maximum'] = is_numeric($max) ? $max : $filter['Maximum'];
        $filter['Step']    = $step ?: 1;

        return $filter;
    }

    /**
     * Update price values by currency converter
     *
     * @param array  $filterFields
     * @param string $categoryPrefix
     */
    public function updatePriceByRate($filterFields, $categoryPrefix)
    {
        global $config, $rlCurrencyConverter;

        if (!$this->converterExist
            || !$this->priceFieldExist
            || (!$_SESSION['curConv_code'] && !$_COOKIE['curConv_code'])
        ) {
            return $filterFields;
        }

        $GLOBALS['reefless']->loadClass('CurrencyConverter', null, 'currencyConverter');
        $currencyRate = $_COOKIE['curConv_code'] ?: $_SESSION['curConv_code'];
        $currencyCode = $this->adaptCurRate($config['system_currency_code']);

        // Update values for price field (currency converter condition)
        foreach ($filterFields as &$filter) {
            if ($filter['Type'] == 'price'
                && is_array($filter['Items'])
                && $filter['Mode'] == 'slider'
            ) {
                if ($filter['Key'] == $config['price_tag_field']) {
                    if ($currencyRate != $currencyCode
                        && $rlCurrencyConverter->rates[$currencyCode]['Rate']
                        && $rlCurrencyConverter->rates[$currencyRate]['Rate']
                    ) {
                        foreach ($filter['Items'] as $itemKey => $itemValue) {
                            if ($itemValue[$categoryPrefix]) {
                                $newValue = $itemKey / $rlCurrencyConverter->rates[$currencyCode]['Rate'];
                                $newValue = $newValue * $rlCurrencyConverter->rates[$currencyRate]['Rate'];
                                $newValue = round($newValue, 2);

                                $newItems[(string) $newValue] = $itemValue;
                                unset($newValue);
                            }
                        }

                        $filter['Items'] = $newItems;
                    }

                    break;
                }
            }
        }

        return $filterFields;
    }

    /**
     * Adapt custom keys of currencies to ISO standard
     *
     * @param  string $currency - Custom currency
     * @return string           - ISO key of currency
     */
    public function adaptCurRate($currency)
    {
        switch ($currency) {
            case 'GBP':
                $currency = 'pound';
                break;
            case 'EUR':
                $currency = 'euro';
                break;
            case 'USD':
                $currency = 'dollar';
                break;
        }

        return $currency;
    }

    /**
     * Update step, min and max values for fields with slider mode
     *
     * @param array     $filterFields
     * @param string    $categoryPrefix
     * @param array     $filterInfo
     * @param bool|null $cCondition
     *
     * @return array|false
     */
    public function updateStepMinMax(array $filterFields, string $categoryPrefix, array $filterInfo, ?bool $cCondition = null)
    {
        global $category;

        if (!$filterFields) {
            return false;
        }

        foreach ($filterFields as &$filter) {
            if ($filter['Mode'] === 'slider' && is_array($filter['Items'])) {
                foreach ($filter['Items'] as $itemKey => $itemValue) {
                    if ($itemValue[$categoryPrefix]) {
                        if ((float) $itemKey < $filter['Minimum'] || !isset($filter['Minimum'])) {
                            $filter['Minimum'] = (float) $itemKey;
                        }

                        if ((float) $itemKey > $filter['Maximum'] || !$filter['Maximum']) {
                            $filter['Maximum'] = (float) $itemKey;
                        }
                    }
                }

                $minIndex = "cfID_{$filterInfo['ID']}_{$filter['Key']}";
                $maxIndex = "cfID_{$filterInfo['ID']}_{$filter['Key']}";

                // First initial of listing type and category filter mode
                if (!$this->filters
                    && (($filterInfo['Mode'] === 'type' && !$category['ID'])
                        || ($filterInfo['Mode'] === 'category' && $cCondition)
                        || $filterInfo['Mode'] === 'search_results'
                        || $filterInfo['Mode'] === 'field_bound_boxes'
                    )
                ) {
                    // Save values of initial condition
                    if (is_numeric($filter['Minimum']) && is_numeric($filter['Maximum'])) {
                        $_SESSION[$minIndex]['Minimum'] = $filter['Minimum'];
                        $_SESSION[$maxIndex]['Maximum'] = $filter['Maximum'];
                    }

                    $filter = $this->getStepMinMax($filter);
                } else {
                    if (in_array($filterInfo['Mode'], ['category', 'type'])) {
                        // Update MIN value by first initial
                        if ($_SESSION[$minIndex]['Minimum']) {
                            $filter['Minimum'] = $_SESSION[$minIndex]['Minimum'];
                        }

                        // Update MAX value by first initial
                        if ($_SESSION[$maxIndex]['Maximum']) {
                            $filter['Maximum'] = $_SESSION[$maxIndex]['Maximum'];
                        }

                        $filter = $this->getStepMinMax($filter);
                        $filter['Second_condition'] = true;
                    }
                    // Search results mode
                    else {
                        if (!isset($this->filters[$filter['Key']])) {
                            // Save values of initial condition
                            if (is_numeric($filter['Minimum']) && is_numeric($filter['Maximum'])) {
                                $_SESSION[$minIndex]['Minimum'] = $filter['Minimum'];
                                $_SESSION[$maxIndex]['Maximum'] = $filter['Maximum'];
                            }

                            $filter = $this->getStepMinMax($filter);
                        } else {
                            // Update MIN value by first initial
                            if ($_SESSION[$minIndex]['Minimum']) {
                                $filter['Minimum'] = $_SESSION[$minIndex]['Minimum'];
                            }

                            // Update MAX value by first initial
                            if ($_SESSION[$maxIndex]['Maximum']) {
                                $filter['Maximum'] = $_SESSION[$maxIndex]['Maximum'];
                            }

                            $filter = $this->getStepMinMax($filter);
                            $filter['Second_condition'] = true;
                        }
                    }
                }

                // Assign already selected values in slider
                if (isset($this->filters[$filter['Key']])) {
                    $sliderValues             = explode('-', $this->filters[$filter['Key']]);
                    $filter['Slider_minimum'] = $sliderValues[0];
                    $filter['Slider_maximum'] = $sliderValues[1];
                } else {
                    $filter['Slider_minimum'] = $filter['Minimum'];
                    $filter['Slider_maximum'] = $filter['Maximum'];
                }
            }
        }

        return $filterFields;
    }

    /**
     * Update filters fields by Multi-field/Location Filter plugin
     *
     * @param array $filterFields
     * @param array $filterInfo
     */
    public function updateMultiFields($filterFields, $filterInfo)
    {
        global $lang, $rlDb, $reefless, $rlMultiField, $rlGeoFilter;

        if (!$GLOBALS['plugins']['multiField']) {
            return $filterFields;
        }

        // Detects using geo-filter in page and prevent using filters by location filter fields
        if ($this->isGeoFilterPage($filterInfo)) {
            $geoFilter = false;
            foreach ($filterFields as $filterKey => &$filterField) {
                if ($filterField['Condition'] && $filterField['Condition'] === $rlGeoFilter->geo_format['Key']) {
                    if (preg_match('/[a-zA-Z|0-9]+\_level([0-9])/', $filterField['Key'])) {
                        unset($filterFields[$filterKey]);
                    } else {
                        $filterField['Geo_filter'] = true;
                        $geoFilter = true;
                    }
                }
            }

            if ($geoFilter && method_exists($rlGeoFilter, 'boxData') && method_exists($rlGeoFilter, 'ignoreFileCompilation')) {
                $rlGeoFilter->boxData();
                $rlGeoFilter->ignoreFileCompilation('plugins/categoryFilter/box.tpl');
            }

            return $filterFields;
        }

        // Find multi-fields & detect parent field in Field-Bound Box
        if ($filterInfo['Mode'] == 'field_bound_boxes' && $this->fbbPath) {
            if ($this->isNewFieldBoundBoxesPlugin()) {
                $fbbFieldKey = $rlDb->getOne(
                    'Field_key',
                    "`Key` = '{$filterInfo['Type']}' AND `Status` = 'active'",
                    'field_bound_boxes'
                );
            } else {
                $fbbFieldKey = $rlDb->getOne(
                    'Field_key',
                    "`Path` = '{$this->fbbPath}' AND `Status` = 'active'",
                    'field_bound_boxes'
                );
            }

            $fbbFieldCondition = $rlDb->getOne(
                'Condition',
                "`Key` = '{$fbbFieldKey}' AND `Status` = 'active'",
                'listing_fields'
            );
            $fbbFieldInfo = $rlDb->fetch(
                '*',
                ['Key' => $fbbFieldCondition, 'Status' => 'active'],
                null,
                null,
                'multi_formats',
                'row'
            );

            // Get last selected level of filters
            if ($fbbFieldKey && $fbbFieldCondition && $fbbFieldInfo['Levels']) {
                $lastLevel = 0;
                for ($i = 1; $i <= (int) $fbbFieldInfo['Levels']; $i++) {
                    if ($this->filters[str_replace('_', '-', $fbbFieldKey) . '_level' . $i] && $lastLevel < $i) {
                        $lastLevel = $i;
                    }
                }

                // Find and remove child multi-fields
                foreach ($filterFields as $childKey => $childFilter) {
                    $pattern = '/' . $fbbFieldKey . '\_level([0-9])/';
                    preg_match($pattern, $childFilter['Key'], $matches);

                    if ($childFilter['Condition'] == $fbbFieldCondition
                        && $matches[1]
                        && (int) $matches[1] > $lastLevel + 1
                    ) {
                        $removeChilds[] = $childKey;
                    }
                }

                // Remove unnecessary (child) fields
                if (is_array($removeChilds)) {
                    foreach ($removeChilds as $childKey) {
                        unset($filterFields[$childKey]);
                    }
                }

                // Get missed phrases for multi-fields
                if ($lastLevel >= 0 && $this->fbbValue) {
                    $missedKey = $fbbFieldCondition . '_' . $this->fbbValue . '_';
                    $sql = 'SELECT `Key`, `Value` FROM `{db_prefix}lang_keys` ';
                    $sql .= "WHERE `Status` = 'active' AND `Key` LIKE CONCAT('data_formats+name+', '{$missedKey}%') ";
                    $sql .= "AND `Code` = '" . RL_LANG_CODE . "'";

                    if ($missedPhrases = $rlDb->getAll($sql)) {
                        foreach ($missedPhrases as $phrase) {
                            $lang[$phrase['Key']] = $phrase['Value'];
                        }
                    }
                }

                return $filterFields;
            }
        }

        // Find multi-fields
        foreach ($filterFields as &$filter) {
            $pattern = '/[a-zA-Z|0-9]+\_level([0-9])/';
            preg_match($pattern, $filter['Key'], $matches);

            if ($filter['Condition'] && !$matches[0]) {
                // Get parent of multi-fields
                $sql = "SELECT * FROM `{db_prefix}multi_formats` WHERE `Key` = '{$filter['Condition']}' ";
                $sql .= "AND `Status` = 'active'";

                if ($parentFieldInfo = $rlDb->getRow($sql)) {
                    // Set parent of multi-field
                    $filter['Multifield_level'] = 1;

                    // Get last selected level of filters
                    $lastLevel = 0;
                    for ($i = 1; $i <= (int) $parentFieldInfo['Levels']; $i++) {
                        if (($this->filters[str_replace('_', '-', $filter['Key']) . '_level' . $i]
                                || $this->filters[$filter['Key'] . '_level' . $i])
                            && $lastLevel < $i
                        ) {
                            $lastLevel = $i;
                        }
                    }

                    // Child levels not selected
                    if ($lastLevel == 0) {
                        if (!$this->filters[$filter['Key']]) {
                            foreach ($filterFields as $childKey => $childFilter) {
                                $pattern = '/' . $filter['Key'] . '\_level([0-9])/';
                                preg_match($pattern, $childFilter['Key'], $matches);

                                if ($childFilter['Condition'] == $filter['Condition']
                                    && $childFilter['ID'] != $filter['ID']
                                    && (false !== strpos($childFilter['Key'], '_level') && (int) $matches[1] >= 1)
                                ) {
                                    $removeChilds[] = $childKey;
                                }
                            }
                        } else {
                            foreach ($filterFields as $childKey => $childFilter) {
                                if ($childFilter['Condition'] == $filter['Condition']
                                    && $childFilter['ID'] != $filter['ID']
                                    && ($childFilter['Key'] != $filter['Key'] . '_level1'
                                        && false !== strpos($childFilter['Key'], '_level')
                                        && false !== strpos($childFilter['Key'], $filter['Key'])
                                    )
                                ) {
                                    $removeChilds[] = $childKey;
                                }
                            }
                        }
                    }
                    // Some of child levels is selected
                    elseif ($lastLevel > 0) {
                        foreach ($filterFields as $childKey => $childFilter) {
                            $pattern = '/' . $filter['Key'] . '\_level([0-9])/';
                            preg_match($pattern, $childFilter['Key'], $matches);

                            if ($childFilter['Condition'] == $filter['Condition']
                                && $childFilter['ID'] != $filter['ID']
                                && (false !== strpos($childFilter['Key'], '_level') && $matches[1])
                                && (int) $matches[1] > $lastLevel + 1
                            ) {
                                $removeChilds[] = $childKey;
                            }
                        }
                    }

                    // Get missed phrases for multi-fields
                    if ($this->filters[$filter['Key']]) {
                        $sql = 'SELECT `Key`, `Value` FROM `{db_prefix}lang_keys` ';
                        $sql .= "WHERE `Status` = 'active' AND `Key` LIKE CONCAT";
                        $sql .= "('data_formats+name+', '{$this->filters[str_replace('_', '-', $filter['Key'])]}_%') ";
                        $sql .= "AND `Code` = '" . RL_LANG_CODE . "'";

                        if ($missedPhrases = $rlDb->getAll($sql)) {
                            foreach ($missedPhrases as $phrase) {
                                $lang[$phrase['Key']] = $phrase['Value'];
                            }
                        }
                    } elseif (in_array($filter['Condition'], $this->multifieldFormatKeys)) {
                        $reefless->loadClass('MultiField', null, 'multiField');

                        if (method_exists($rlMultiField, 'getPhrases')) {
                            $rlMultiField->getPhrases($filter['Condition']);
                        }
                    }
                }
            } elseif ($filter['Condition'] && $matches[0]) {
                // Found child level of multifield (state/city without country)
                $parentKey     = preg_replace("/(_level[0-9])/", '', $filter['Key']);
                $parentMissing = !in_array($parentKey, array_keys($filterFields));

                if ($parentMissing) {
                    $removeChilds[] = $filter['Key'];
                } else {
                    if ($this->filters[$parentKey]
                        && in_array($filter['Condition'], $this->multifieldFormatKeys)
                    ) {
                        $reefless->loadClass('MultiField', null, 'multiField');

                        if (method_exists($rlMultiField, 'getPhrases')) {
                            $rlMultiField->getPhrases($this->filters[$parentKey]);
                        }
                    }
                }
            }
        }

        // Remove unnecessary (child) fields
        if (is_array($removeChilds)) {
            foreach ($removeChilds as $childKey) {
                unset($filterFields[$childKey]);
            }
        }

        return $filterFields;
    }

    /**
     * Update filter field with condition Years (for escort version only)
     *
     * @param  array $filterFields
     * @return array
     */
    public function updateYears($filterFields)
    {
        if (!$this->isEscort) {
            return $filterFields;
        }

        // Find years field
        foreach ($filterFields as &$filter) {
            if ($filter['Condition'] == 'years' && $filter['Items'] && $filter['Mode'] == 'slider') {
                $tmpItems = [];

                foreach ($filter['Items'] as $itemKey => $item) {
                    if ((int) $itemKey <= date('Y')) {
                        $tmpItems[date('Y') - (int) $itemKey] = $item;
                    }
                }

                $filter['Items'] = $tmpItems;
            }
        }

        return $filterFields;
    }

    /**
     * Get available fields of filter
     *
     * @since 2.7.4 - Added $force parameter
     *
     * @param  int        $boxID - ID of filter box
     * @param  bool       $force - Prevent return data from internal cache
     * @return array|bool
     */
    public function getFilterFields($boxID, $force = false)
    {
        global $rlDb;

        $boxID = (int) $boxID;

        if (!$boxID) {
            return false;
        }

        static $filterFields = [];

        if ($filterFields[$boxID] && !$force) {
            return $filterFields[$boxID];
        }

        $sql = 'SELECT `T1`.`ID`, `T2`.`ID` AS `Field_ID`, `T1`.`Items`, `T1`.`Item_names`, ';
        $sql .= '`T1`.`Items_display_limit`, `T1`.`Mode`, `T2`.`Key`, `T2`.`Type`, `T2`.`Condition`, ';
        $sql .= "`T2`.`Values`, CONCAT('listing_fields+name+', `T2`.`Key`) AS `pName`, `T1`.`No_index`, ";
        $sql .= '`T1`.`Data_in_title`, `T1`.`Data_in_description`, `T1`.`Data_in_H1` ';
        $sql .= 'FROM `{db_prefix}category_filter_field` AS `T1` ';
        $sql .= 'LEFT JOIN `{db_prefix}listing_fields` AS `T2` ON `T2`.`ID` = `T1`.`Field_ID` ';
        $sql .= "WHERE `T1`.`Box_ID` = {$boxID} AND `T1`.`Status` = 'active' AND `T2`.`Status` = 'active' ";
        $sql .= 'ORDER BY `T1`.`ID`';
        $tmpFields = $rlDb->getAll($sql);

        $GLOBALS['reefless']->loadClass('Categories');

        // Update values for field with checkbox type and have condition
        foreach ($tmpFields as &$tmpField) {
            if (($tmpField['Type'] === 'checkbox' && $tmpField['Condition'])
                || ($tmpField['Type'] === 'select' && $tmpField['Mode'] === 'checkboxes')
            ) {
                $dataFormats    = [];
                foreach ($GLOBALS['rlCategories']->getDF($tmpField['Condition']) as $dataFormatItem) {
                    $dataFormats[$dataFormatItem['Key']] = $dataFormatItem;
                }

                if ($tmpField['Type'] == 'select' && $tmpField['Mode'] == 'checkboxes') {
                    $items = unserialize(base64_decode($tmpField['Items']));

                    foreach ($items as $key => $value) {
                        if ($dataFormats[$value[$tmpField['Key']]]) {
                            $tmpField['Values'] = $tmpField['Values']
                            ? ($tmpField['Values'] . ',' . $value[$tmpField['Key']])
                            : $value[$tmpField['Key']];
                        }
                    }
                } else {
                    foreach ($dataFormats as $key => $value) {
                        $tmpField['Values'] = $tmpField['Values'] ? ($tmpField['Values'] . ',' . $key) : $key;
                    }
                }
            }
        }

        // Optimize keys in list of filters
        foreach ($tmpFields as $field) {
            $fields[$field['Key']] = $field;
        }

        $filterFields[$boxID] = $fields;

        return $fields;
    }

    /**
     * Get search criteria from search request
     *
     * @return  bool
     */
    public function getSearchCriteria()
    {
        global $sql;

        if ($GLOBALS['page_info']['Controller'] !== 'listing_type' || !$sql) {
            return false;
        }

        if (preg_match('/^SELECT.*WHERE.(.*)$/s', $sql, $matches)) {
            if (!$matches[1]) {
                return false;
            }

            $criteriaSQL = $matches[1];

            // Removing default SQL code
            $pattern           = "/^`T1`\\.`Status`.=.'[^']*'(.*)AND.`T3`\\.`Type`.=.'[^']*'/s";
            $criteriaSQL       = preg_replace($pattern, '$1', $criteriaSQL);
            $this->criteriaSQL = $criteriaSQL;
        }
    }

    /**
     * Generate SQL by selected filters
     *
     * @param  array $filterID
     * @return bool
     */
    public function getSqlByFilters($filterID)
    {
        global $rlCurrencyConverter, $lang, $reefless, $rlMultiField;

        $filterID  = (int) $filterID;
        $filtersSQL = '';

        if (!$filterID || $GLOBALS["filter_sql_{$filterID}"]) {
            $this->filtersSQL = $GLOBALS["filter_sql_{$filterID}"];
            return false;
        }

        $filterFields = $this->getFilterFields($filterID);

        if ($this->filters) {
            foreach ($this->filters as $filterKey => $filter) {
                $filter     = stripslashes(Valid::escape($filter));
                $filterInfo = $filterFields[$filterKey];
                $filterType = $filterInfo['Type'];
                $filterMode = $filterInfo['Mode'];

                if (in_array($filterInfo['Condition'], $this->multifieldFormatKeys)) {
                    $reefless->loadClass('MultiField', null, 'multiField');

                    if (method_exists($rlMultiField, 'getPhrases')) {
                        $rlMultiField->getPhrases($filterInfo['Condition']);
                    }
                }

                if (in_array($filterMode, ['slider', 'text', 'group'])) {
                    preg_match('/^([0-9|min]+)?([\-\<\>]+)([0-9|max]+)?$/', $filter, $ranges);

                    if ($ranges[1] == 'min') {
                        $ranges[2] = '<';
                    } elseif ($ranges[3] == 'max') {
                        $ranges[2] = '>';
                    }

                    if ($filterType == 'price' && $this->converterExist) {
                        $currencyRate = $_COOKIE['curConv_code'] ?: $_SESSION['curConv_code'];
                        $currencyCode = $this->adaptCurRate($GLOBALS['config']['system_currency_code']);
                    }

                    if ($currencyRate
                        && $currencyRate != $currencyCode
                        && $rlCurrencyConverter->rates[$currencyRate]['Rate']
                        && $rlCurrencyConverter->rates[$currencyCode]['Rate']
                    ) {
                        $from = $ranges[1];
                        $from /= $rlCurrencyConverter->rates[$currencyRate]['Rate'];

                        if ($filterMode != 'text') {
                            $from       = $from * $rlCurrencyConverter->rates[$currencyCode]['Rate'];
                            $filtersSQL .= "AND SUBSTRING_INDEX(`T1`.`{$filterKey}`, '|', 1) >= {$from} ";
                        } else {
                            $filtersSQL .= "AND SUBSTRING_INDEX(`T1`.`{$filterKey}`, '|', 1)";
                            $filtersSQL .= "/IF(`CURCONV`.`Rate` IS NULL, 1, `CURCONV`.`Rate`) >= {$from} ";
                        }

                        $to = $ranges[3];
                        $to /= $rlCurrencyConverter->rates[$currencyRate]['Rate'];

                        if ($filterMode != 'text') {
                            $to         = $to * $rlCurrencyConverter->rates[$currencyCode]['Rate'];
                            $filtersSQL .= "AND SUBSTRING_INDEX(`T1`.`{$filterKey}`, '|', 1) <= {$to} ";
                        } else {
                            $filtersSQL .= "AND SUBSTRING_INDEX(`T1`.`{$filterKey}`, '|', 1)";
                            $filtersSQL .= "/IF(`CURCONV`.`Rate` IS NULL, 1, `CURCONV`.`Rate`) <= {$to} ";
                        }
                    } else {
                        // Optimize condition for years filter field (for escort)
                        if ($this->isEscort
                            && $filterInfo['Condition'] == 'years'
                            && in_array($filterMode, ['slider', 'text'])
                        ) {
                            $to        = $ranges[1] ? date('Y') - (int) $ranges[1] : '';
                            $from      = $ranges[3] ? date('Y') - (int) $ranges[3] : '';
                            $ranges[1] = $from;
                            $ranges[3] = $to;
                        }

                        switch ($ranges[2]) {
                            case '-':
                                $filtersSQL .= "AND (ROUND(`T1`.`{$filterKey}`, 2) >= {$ranges[1]} ";
                                $filtersSQL .= "AND ROUND(`T1`.`{$filterKey}`, 2) <= {$ranges[3]}) ";

                                if ($this->currency) {
                                    $filtersSQL .= "AND LOCATE('{$this->currency}', `T1`.`{$filterKey}`) > 0 ";
                                }
                                break;

                            case '<':
                                $filtersSQL .= "AND (ROUND(`T1`.`{$filterKey}`, 2) <= {$ranges[3]} ";
                                $filtersSQL .= "AND ROUND(`T1`.`{$filterKey}`, 2) >= 0) ";
                                break;

                            case '>':
                                $filtersSQL .= "AND (ROUND(`T1`.`{$filterKey}`, 2) >= {$ranges[1]}) ";
                                break;
                        }
                    }
                } else {
                    $ids = [];

                    // Revert keys of values instead of phrases
                    if (in_array($filterType, ['checkbox', 'select', 'radio'])
                        && $filterKey != 'posted_by'
                        && $filterInfo['Condition'] != 'years'
                    ) {
                        foreach (explode(',', $filter) as $item) {
                            $id           = '';
                            $parentValue = '';

                            // Find necessary phrase key by value
                            if ($filterInfo['Condition']) {
                                if ($GLOBALS['plugins']['multiField']
                                    && (
                                        $GLOBALS['multi_formats'][$filterInfo['Condition']]
                                        || in_array($filterInfo['Condition'], $this->multifieldFormatKeys)
                                    )
                                ) {
                                    // Move phrases with names of "Countries" forward.
                                    array_multisort(array_map('strlen', array_keys($lang)), SORT_ASC, $lang);
                                    $phrasePattern = "data_formats+name+{$filterInfo['Condition']}";
                                } else {
                                    if ($filterInfo['Values']) {
                                        $phrasePattern = 'data_formats+name+';
                                    } else {
                                        $phrasePattern = "data_formats+name+{$filterInfo['Condition']}";
                                    }
                                }

                                if (strpos($filterKey, '_level')) {
                                    $level           = (int) substr($filterKey, -1);
                                    $parentFilterKey = $level > 1
                                    ? str_replace($level, $level - 1, $filterKey)
                                    : str_replace('_level' . $level, '', $filterKey);
                                    $parentValue = $this->filters[$parentFilterKey] . '_';

                                    if ($parentValue) {
                                        $phrasePattern = "data_formats+name+{$parentValue}";
                                    }
                                }

                                foreach ($lang as $phraseKey => $phrase) {
                                    if (false !== strpos($phraseKey, $phrasePattern) && $phrase === $item) {
                                        $id = $phraseKey;
                                        break;
                                    }
                                }

                                // Search the value which doesn't have key of format in key of the phrase
                                if (!$id && !$parentValue) {
                                    $phrasePattern = 'data_formats+name+';

                                    foreach ($lang as $phraseKey => $phrase) {
                                        if (false !== strpos($phraseKey, $phrasePattern) && $phrase === $item) {
                                            $id = $phraseKey;
                                            break;
                                        }
                                    }
                                }
                            } else {
                                $id = array_search(htmlspecialchars_decode($item), $lang);

                                // Found wrong value (in another phrase)
                                if ($id && false === strpos($id, $filterKey)) {
                                    foreach ($lang as $pKey => $phrase) {
                                        if (false !== strpos($pKey, $filterKey)
                                            && false !== strpos($phrase, $filter)
                                            && false === strpos($pKey, 'category_filter+name+')
                                        ) {
                                            $id = $pKey;
                                        }
                                    }
                                }
                            }

                            // Phrase not found in loaded phrases
                            if (!$id && $filterInfo['Condition']) {
                                $addWhere = '';

                                // Detect child multi-fields created via Multifield/GeoFilter plugin
                                if (false !== strpos($filterKey, '_level')) {
                                    $parentFilter = preg_replace("/(_level[0-9])/", "", $filterKey);
                                    $parentValue  = $this->filters[$parentFilter];
                                    $level        = (int) substr($filterKey, -1);

                                    if ($parentFilter && $parentValue) {
                                        $itemKey = '';

                                        if ($level == 1) {
                                            $itemKey = $parentValue . '_' . $GLOBALS['rlValid']->str2key($item);
                                            $addWhere = "AND `Key` LIKE '%{$itemKey}%'";
                                        } else if ($level == 2) {
                                            $itemKey = $this->filters["{$parentFilter}_level1"];
                                            $itemKey .= '_' . $GLOBALS['rlValid']->str2key($item);
                                        }

                                        if ($itemKey) {
                                            $addWhere = "AND `Key` LIKE '%{$itemKey}%'";
                                        }
                                    }
                                }

                                $sql = 'SELECT `Key` FROM `{db_prefix}lang_keys` ';
                                $sql .= "WHERE `Value` = '{$item}' AND `Status` = 'active' ";
                                $sql .= "AND `Key` LIKE '%{$filterInfo['Condition']}%' ";
                                $sql .= "AND `Code` = '" . RL_LANG_CODE . "' {$addWhere}";
                                $id = $GLOBALS['rlDb']->getRow($sql, 'Key');
                            }

                            if ($id && false !== strpos($id, '+')) {
                                if (false !== strpos($id, 'category_filter+name+')) {
                                    $ids[] = preg_replace("/(category\_filter\+name\+[0-9]+\_[0-9]+\_)/", '', $id);
                                } else {
                                    $id    = explode('+', $id)[2];
                                    $ids[] = $filterInfo['Condition'] ? $id : strtr($id, [$filterKey => '', '_' => '']);
                                }
                            }
                        }

                        // Search phrase completely in loaded phrases (it cannot be found if have the comma, for example)
                        if (!$ids) {
                            $id = array_search(htmlspecialchars_decode($filter), $lang);

                            if ($id && false !== strpos($id, '+')) {
                                if (false !== strpos($id, 'category_filter+name+')) {
                                    $ids[] = preg_replace("/(category\_filter\+name\+[0-9]+\_[0-9]+\_)/", '', $id);
                                } else {
                                    $id    = explode('+', $id)[2];
                                    $ids[] = $filterInfo['Condition'] ? $id : strtr($id, [$filterKey => '', '_' => '']);
                                }
                            }
                        }

                        $this->filters[$filterKey] = $ids ? implode(',', $ids) : '';
                        $filter = $this->filters[$filterKey];
                    }

                    if ($filterType == 'checkbox' && $ids) {
                        $filtersSQL .= "AND (FIND_IN_SET('";
                        $filtersSQL .= implode("', `T1`.`{$filterKey}`) > 0 AND FIND_IN_SET('", $ids);
                        $filtersSQL .= "', `T1`.`{$filterKey}`) > 0) ";
                    } else {
                        if ($filterKey == 'posted_by') {
                            $filtersSQL .= "AND `T7`.`Type` = '{$filter}' ";
                        } elseif ($filterKey == 'category_id') {
                            $filtersSQL .= "AND ((`T1`.`Category_ID` = {$filter} OR FIND_IN_SET({$filter}, ";
                            $filtersSQL .= "`T3`.`Parent_IDs`) > 0) OR (FIND_IN_SET({$filter}, `T1`.`Crossed`) > 0)) ";
                        } else {
                            if ($filterMode == 'checkboxes') {
                                $sql = 'AND (';
                                foreach (explode(',', $filter) as $value) {
                                    $sql .= "`T1`.`{$filterKey}` = '{$value}' OR";
                                }
                                $sql = rtrim($sql, 'OR');
                                $sql .= ') ';

                                $filtersSQL .= $sql;
                            } else {
                                if ($filterKey != 'currency') {
                                    $filtersSQL .= "AND `T1`.`{$filterKey}` = '{$filter}' ";
                                }
                            }
                        }
                    }
                }

                if (in_array($filterInfo['Condition'], $this->multifieldFormatKeys)) {
                    $reefless->loadClass('MultiField', null, 'multiField');

                    if (method_exists($rlMultiField, 'getPhrases')) {
                        $rlMultiField->getPhrases($this->filters[$filterInfo['Key']]);
                    }
                }
            }
        }

        $GLOBALS["filter_sql_{$filterID}"] = $this->filtersSQL = $filtersSQL;
    }

    /**
     * @hook listingsModifyWhere
     */
    public function hookListingsModifyWhere()
    {
        global $sql, $categoryFilter_activeBoxID;

        if (!$categoryFilter_activeBoxID || !$sql) {
            return;
        }

        $this->getSqlByFilters($categoryFilter_activeBoxID);
        $sql .= $this->filtersSQL;
        $this->filtersSQL = '';
    }

    /**
     * @hook listingsModifyWhereSearch
     */
    public function hookListingsModifyWhereSearch(&$sql)
    {
        global $categoryFilter_activeBoxID;

        if (!$categoryFilter_activeBoxID || !$sql) {
            return;
        }

        $this->getSqlByFilters($categoryFilter_activeBoxID);
        $sql .= $this->filtersSQL;
        $this->filtersSQL = '';
    }

    /**
     * Update filter forms and recount filters after removing field
     *
     * @hook apPhpFieldsAjaxDeleteField
     */
    public function hookApPhpFieldsAjaxDeleteField()
    {
        global $field, $rlDb;

        $fieldID = (int) $field['ID'];

        if (!$fieldID) {
            return;
        }

        $rlDb->query("DELETE FROM `{db_prefix}category_filter_relation` WHERE `Fields` = $fieldID");
        $rlDb->query("DELETE FROM `{db_prefix}category_filter_field` WHERE `Field_ID` = $fieldID");
        $this->recountFilters();
    }

    /**
     * @hook apTplContentBottom
     */
    public function hookApTplContentBottom()
    {
        if ($GLOBALS['controller'] !== 'blocks') {
            return;
        }

        // Hide some of options from page
        if ($_GET['action'] == 'edit' && isset($_GET['block']) && false !== strpos($_GET['block'], 'categoryFilter_')) {
            echo "<script type='text/javascript'>$('#btypes').hide()</script>";
            echo "<script type='text/javascript'>$('#pages_obj,#cats').closest('tr').hide()</script>";
        }
    }

    /**
     * @hook  apTplHeader
     * @since 2.2.0
     */
    public function hookApTplHeader()
    {
        if ($_GET['controller'] !== 'categoryFilter') {
            return;
        }

        echo '<link href="' . RL_PLUGINS_URL . 'categoryFilter/static/aStyle.css" type="text/css" rel="stylesheet" />';
    }

    /**
     * @hook  apAjaxBuildFormPostSaving
     * @since 2.2.0
     */
    public function hookApAjaxBuildFormPostSaving()
    {
        global $transfer, $_response;

        if (!$transfer['category_id'] || $_GET['controller'] !== 'categoryFilter') {
            return;
        }

        $errors = [];
        $this->saveForm($transfer, $errors);

        $_response->script("$('div#form_section div.field_obj a.suspended').removeClass('suspended')");

        if ($errors) {
            $_response->script("$('div#form_section div.field_obj a.suspended').removeClass('suspended')");

            $errorsList = '<ul>';
            foreach ($errors as $error) {
                $errorsList .= '<li>' . $error . '</li>';
            }
            $errorsList .= '</ul>';

            $_response->script("printMessage('alert', '{$errorsList}');");
        }
    }

    /**
     * Parse URL and collect data about selected filters
     * @hook  init
     * @since 2.7.4
     */
    public function hookInit()
    {
        // Special chars (like: "'","+" and etc.)
        $find    = ['-cfa-', '-cfp-', '-cfc-', '-cfs-', '-cfamp-'];
        $replace = ['%27', '%2B', '%3A', '%2F', '&#38;'];

        if ($GLOBALS['config']['mod_rewrite']) {
            $vars  = explode('/', $_GET['rlVareables']);
            $allow = [];
            foreach ($vars as $var) {
                if (false === strpos($var, ':')) {
                    $allow[] = $var;
                } elseif (false !== strpos($var, ':')) {
                    $item = explode(':', $var);
                    $filters[str_replace('-', '_', $item[0])] = urldecode(
                        str_replace($find, $replace, $item[1])
                    );
                }
            }

            if ($allow) {
                $_GET['rlVareables'] = implode('/', $allow);
            } else {
                $_GET['rlVareables'] = '';
            }
        } else {
            foreach ($_GET as $key => $nvar) {
                if (0 === strpos($key, 'cf-')) {
                    $item = explode('cf-', $key);

                    // Fix wrong filter key (temp solution)
                    if (!$nvar && false !== strpos($key, ':')) {
                        $nvar    = $nvar ?: explode(':', $key)[1];
                        $key     = explode(':', $key)[0];
                        $item[1] = str_replace('cf-', '', $key);
                    }

                    $filters[str_replace('-', '_', $item[1])] = urldecode(str_replace($find, $replace, $nvar));
                    unset($_GET[$key]);
                }
            }
        }

        if ($filters) {
            if ($filters['currency']) {
                $this->currency = $filters['currency'];
            }

            // Ignore missing fields
            if ($_SESSION['cfExistingFields']) {
                $existingFields = $_SESSION['cfExistingFields'];
            } else {
                $existingFields = $GLOBALS['rlDb']->getAll('SELECT `Key` FROM `{db_prefix}listing_fields`', 'Key');
                $_SESSION['cfExistingFields'] = $existingFields;
            }

            foreach ($filters as $filterKey => $filter_value) {
                if (!$existingFields[$filterKey] && $filterKey !== 'category_id' && $filterKey !== 'currency') {
                    unset($filters[$filterKey]);
                }
            }

            $this->filters = $filters;
        }
    }

    /**
     * @hook  listingsModifyJoin
     * @since 2.2.0
     */
    public function hookListingsModifyJoin()
    {
        $this->join();
    }

    /**
     * @hook  listingsModifyJoinSearch
     * @since 2.2.0
     */
    public function hookListingsModifyJoinSearch()
    {
        $this->join();
    }

    /**
     * @hook  cronAdditional
     * @since 2.2.0
     */
    public function hookCronAdditional()
    {
        $this->recountFilters();
    }

    /**
     * @hook  apTplControlsForm
     * @since 2.2.0
     */
    public function hookApTplControlsForm()
    {
        global $lang;

        $html = '<tr class="body"><td class="list_td">' . $lang['category_filter_refreshes_recount'] . '</td>';
        $html .= '<td class="list_td" align="center"><input id="rebuild_filters" type="button" ';
        $html .= 'onclick="cfAjaxRecountFilters();"';
        $html .= 'value="' . $lang['recount'] . '" style="margin: 0; width: 100px;" /></td></tr>';
        $html .= '<td style="height: 5px;" colspan="3"></td></tr>';
        echo $html;

        $errorPhrase = $GLOBALS['rlLang']->getPhrase('cf_recount_notify_fail', null, null, true);

        echo <<<HTML
            <script>
            var \$button = $('#rebuild_filters');

            var cfAjaxRecountFilters = function() {
                \$button.val('{$lang['loading']}').addClass('disabled').attr('disabled', 'disabled');

                $.post(
                    rlConfig['ajax_url'],
                    {item: 'cfAjaxRecountFilters'},
                    function(response){
                        if (response && response.status && response.message) {
                            if (response.status === 'OK') {
                                printMessage('notice', response.message);
                            } else {
                                printMessage('error', '{$errorPhrase}');
                            }

                            \$button.val('{$lang['recount']}').removeClass('disabled').removeAttr('disabled');
                        }
                    },
                    'json'
                );
            }
            </script>
HTML;
    }

    /**
     * @hook  listingsModifyGroupSearch
     * @since 2.2.0
     */
    public function hookListingsModifyGroupSearch()
    {
        $this->getSearchCriteria();
    }

    /**
     * @hook  specialBlock
     * @since 2.2.0
     */
    public function hookSpecialBlock()
    {
        global $blocks, $config, $search_results_url, $advanced_search_url, $rlCategories, $rlDb;

        // Add a header "X-Robots-Tag: noindex, nofollow" to necessary pages
        if ($this->filters && $noIndexFields = unserialize(base64_decode($GLOBALS['lang']['cf_no_index']))) {
            foreach ($this->filters as $filterKey => $filter) {
                if ($noIndexFields[$filterKey]) {
                    $this->pageNoindex = true;
                    header('X-Robots-Tag: noindex, nofollow', true);
                    break;
                }
            }
        }

        $activeFilterKey = '';
        $categoryMode    = false;
        foreach ($blocks as $blockKey => $block) {
            if (0 === strpos($blockKey, 'categoryFilter_')) {
                $filterInfo = $this->decodeFilterContent($block['Content'], false, false)['filterInfo'] ?? [];

                // Search or advanced search results page
                if (($_GET['nvar_1'] == $search_results_url || $_GET['nvar_2'] == $search_results_url
                        || isset($_GET[$search_results_url]))
                    || (($_GET['nvar_1'] == $advanced_search_url || isset($_GET[$advanced_search_url]))
                        && $GLOBALS['listing_type']['Advanced_search'])
                ) {
                    if ($filterInfo['Mode'] != 'search_results') {
                        unset($blocks[$blockKey]);
                    } else {
                        $activeFilterKey = $blockKey;
                    }
                }
                // General advanced page
                elseif (($_GET['nvar_1'] == $advanced_search_url || isset($_GET[$advanced_search_url]))
                    && !isset($_GET['nvar_2'])
                ) {
                    unset($blocks[$blockKey]);
                }
                // Listing type page
                else {
                    if ($filterInfo['Mode'] == 'search_results') {
                        unset($blocks[$blockKey]);
                    } else {
                        // Type priority
                        if (!$categoryMode) {
                            if ($filterInfo['Mode'] == 'type') {
                                $activeFilterKey = $blockKey;
                            }
                        }

                        // Category priority
                        if ($filterInfo['Mode'] == 'category') {
                            if ($config['mod_rewrite']) {
                                $category = $rlCategories->getCategory(false, $_GET['rlVareables']);
                            } else {
                                $category = $rlCategories->getCategory($_GET['category']);
                            }

                            if (!$category['ID']) {
                                $category['ID'] = 0;
                            }

                            if ($category['ID'] != 0) {
                                $categoryIDs = explode(',', $filterInfo['Category_IDs']);

                                if (false !== array_search($category['ID'], $categoryIDs)) {
                                    $activeFilterKey = $blockKey;
                                    $categoryMode    = true;
                                } elseif ($filterInfo['Subcategories'] && $category['Parent_IDs']) {
                                    if (array_intersect($categoryIDs, explode(',', $category['Parent_IDs']))) {
                                        $activeFilterKey = $blockKey;
                                        $categoryMode    = true;
                                    }
                                }
                            } else {
                                unset($blocks[$blockKey]);
                            }
                        }
                    }

                    if ($filterInfo['Mode'] == 'field_bound_boxes') {
                        // Get path of field-bound box
                        if ($config['mod_rewrite']) {
                            if ($this->isNewFieldBoundBoxesPlugin()) {
                                $this->fbbPath  = $_GET['page'];
                                $this->fbbValue = $_GET['nvar_1'];
                            } else {
                                $this->fbbPath  = $_GET['nvar_1'];
                                $this->fbbValue = $_GET['nvar_2'];
                            }
                        } else {
                            foreach ($_GET as $getKey => $getValue) {
                                if (!in_array($getKey, ['page', 'sort_by', 'sort_type', 'pg'])) {
                                    $this->fbbPath  = $getKey;
                                    $this->fbbValue = $getValue;
                                    break;
                                }
                            }
                        }

                        if ($this->fbbPath === $this->getFbbPath($filterInfo['Type']) && $this->fbbValue) {
                            $activeFilterKey = $blockKey;

                            // Find listing type
                            $this->fbbListingType = $rlDb->getOne(
                                'Listing_type',
                                "`Key` = '{$filterInfo['Type']}' AND `Status` = 'active'",
                                'field_bound_boxes'
                            );
                        }
                    }
                }
            }
        }

        // Remove all filter boxes from page except one with major priority
        foreach ($blocks as $blockKey => $block) {
            if (0 === strpos($blockKey, 'categoryFilter_')) {
                if ($activeFilterKey && $blockKey === $activeFilterKey) {
                    // Save ID of active filter box
                    $GLOBALS['categoryFilter_activeBoxID'] = explode('_', $blockKey)[1];

                    $this->decodeFilterContent($block['Content']);
                    $this->filtersInfo = $this->updateMultiFields($this->filtersInfo, $this->filterInfo);
                } else {
                    unset($blocks[$blockKey]);
                }
            }
        }

        /**
         * If filter have location fields they will be replaced by "Location filter"
         * We need emulate existence of location filter box to include necessary CSS styles
         */
        if ($this->filtersInfo) {
            foreach ($this->filtersInfo as $filter) {
                if ($filter['Geo_filter']) {
                    // Remove origin geo-filter box from page if it will be shown in filters
                    if ($blocks['geo_filter_box']) {
                        unset($blocks['geo_filter_box']);
                    }

                    /**
                     * Forcibly change position of the Location Filter to box instead of header bar
                     * To prevent problems in 2 different geo-filter interfaces
                     */
                    if ($config['mf_select_interface'] === 'usernavbar') {
                        $config['mf_select_interface'] = 'box';
                    }

                    break;
                }
            }
        }

        if (version_compare($config['rl_version'], '4.9.3', '>=') && !empty($this->filtersInfo['Category_ID'])) {
            $GLOBALS['rlStatic']->addHeaderCss(RL_TPL_BASE . 'components/category-box/category-box.css');
        }

        $GLOBALS['rlCommon']->defineBlocksExist($blocks);

        unset($category);
    }

    /**
     * @hook  boot
     * @since 2.2.0
     */
    public function hookBoot()
    {
        // Set noindex for page if listings has not been found
        if ($this->filters && !$this->pageNoindex && !$GLOBALS['listings']) {
            header('X-Robots-Tag: noindex, nofollow', true);
            $this->pageNoindex = true;
        }

        $GLOBALS['rlSmarty']->assign_by_ref('cfPageNoindex', $this->pageNoindex);
    }

    /**
     * @hook  staticDataRegister
     * @since 2.2.0
     */
    public function hookStaticDataRegister()
    {
        global $rlStatic;

        $staticFolder = RL_PLUGINS_URL . 'categoryFilter/static/';

        $rlStatic->addBoxJs("{$staticFolder}lib.js", 'categoryFilter', true);
        $rlStatic->addBoxFooterCSS("{$staticFolder}style.css", 'categoryFilter', true);
    }

    /**
     * @hook  phpListingsAjaxDeleteListing
     * @since 2.2.0
     */
    public function hookPhpListingsAjaxDeleteListing($listingInfo)
    {
        // Make recount of filters when listing will be removed from DB or moved to trash
        // @todo - Remove this temp solution when hook will be moved after removing
        register_shutdown_function([$this, 'recountFilters'], false, $listingInfo);
    }

    /**
     * @hook  afterListingDone
     * @since 2.7.0 - Added $addListing, $update parameters
     * @since 2.2.0
     *
     * @return void
     */
    public function hookAfterListingDone($addListing, $update)
    {
        if ($addListing->listingData) {
            $listingData = $addListing->listingData;
            $listingData['CategoryInfo'] = $addListing->category;
        } else {
            $listingData = $addListing->listingID;
        }

        if (!$listingData) {
            return;
        }

        // Make recount of filters when listing will be created in DB with active status
        // @todo - Remove it when compatible will be more then 4.7.2
        if (version_compare($GLOBALS['config']['rl_version'], '4.7.2') <= 0) {
            register_shutdown_function([$this, 'recountFilters'], false, $listingData);
        } else {
            $this->recountFilters(false, $listingData);
        }
    }

    /**
     * @hook  afterListingEdit
     * @since 2.7.0 - Added $editListing parameter
     * @since 2.2.0
     *
     * @return void
     */
    public function hookAfterListingEdit($editListing)
    {
        if ($GLOBALS['page_info']['Controller'] === 'add_listing') {
            return;
        }

        if ($editListing->listingData) {
            $listingData = $editListing->listingData;
            $listingData['CategoryInfo'] = $editListing->category;
        } else {
            $listingData = $editListing->listingID;
        }

        $this->recountFilters(false, $listingData);
    }

    /**
     * @hook  phpListingsUpgradeListing
     * @since 2.4.0 - Added $planInfo, $planID, $listingID parameters
     * @since 2.2.0
     */
    public function hookPhpListingsUpgradeListing($planInfo = [], $planID = 0, $listingID = 0)
    {
        $this->recountFilters(false, $listingID);
    }

    /**
     * @hook  apPhpListingsAfterAdd
     * @since 2.2.0
     */
    public function hookApPhpListingsAfterAdd()
    {
        $listingData = ['ID' => $GLOBALS['listing_id'], 'CategoryInfo' => $GLOBALS['category']];
        $listingData = array_merge($listingData, $GLOBALS['info']);

        $this->recountFilters(false, $listingData);
    }

    /**
     * @hook  apPhpListingsAfterEdit
     * @since 2.2.0
     */
    public function hookApPhpListingsAfterEdit()
    {
        $listingData = ['ID' => $GLOBALS['listing_id'], 'CategoryInfo' => $GLOBALS['category']];
        $listingData = array_merge($listingData, $GLOBALS['info']);

        $this->recountFilters(false, $listingData);
    }

    /**
     * @hook  apExtListingsAfterUpdate
     * @since 2.2.0
     *
     * @return void
     */
    public function hookApExtListingsAfterUpdate()
    {
        if (!isset($GLOBALS['updateData']['fields']['Status'])) {
            return;
        }

        $listingData = $GLOBALS['listing_info'];
        $listingData['CategoryInfo'] = $GLOBALS['category'];

        $this->recountFilters(false, $listingData);
    }

    /**
     * @hook  categoriesListingsIncrease
     * @since 2.7.0 - Added $type parameter
     * @since 2.2.1
     *
     * @param  int  $categoryID - ID of category
     * @param  type $type       - Key of listing type
     * @return void
     */
    public function hookCategoriesListingsIncrease($categoryID, $type = '')
    {
        if ($GLOBALS['page_info']['Controller'] === 'add_listing'
            || $GLOBALS['cInfo']['Controller'] === 'listings'
            || $_REQUEST['q'] === 'ext'
            || !$categoryID
        ) {
            return;
        }

        $this->recountFilters(null, null, ['ID' => $categoryID, 'Type' => $type]);
    }

    /**
     * @hook  categoriesListingsDecrease
     * @since 2.7.0 - Added $type parameter
     * @since 2.2.1
     *
     * @param  int  $categoryID - ID of category
     * @param  type $type       - Key of listing type
     * @return void
     */
    public function hookCategoriesListingsDecrease($categoryID, $type = '')
    {
        if ($GLOBALS['page_info']['Controller'] === 'my_listings' || !$categoryID) {
            return;
        }

        $this->recountFilters(null, null, ['ID' => $categoryID, 'Type' => $type]);
    }

    /**
     * @hook  tplHeader
     * @since 2.3.0
     */
    public function hookTplHeader()
    {
        if ($this->pageNoindex) {
            echo PHP_EOL . '<meta name="robots" content="noindex, nofollow">';
        }

        /**
         * @todo - Remove code when the Auto Brand template wouldn't add custom CSS to the Filter plugin
         */
        if ($GLOBALS['tpl_settings']['name'] === 'auto_brand_wide') {
            echo <<<HTML
            <style>
            div.filter-area .gf-box ul.list-unstyled.row > li {
                padding: 0 0 0px 15px;
            }
            </style>
HTML;
        }
    }

    /**
     * @hook  phpBuildPagingTemplate
     * @since 2.5.0
     */
    public function hookPhpBuildPagingTemplate(&$addUrl = '')
    {
        global $config, $categoryFilter_activeBoxID;

        if (!$this->filters) {
            return false;
        }

        $index = 0;
        $add   = '';

        // Get filters info
        if ($categoryFilter_activeBoxID && !$this->filtersInfo) {
            $this->filtersInfo = $this->getFilterFields($categoryFilter_activeBoxID);
        }

        // Add filters to paging urls
        foreach ($this->filters as $filterKey => $filterVal) {
            $filterVal = $this->encodeFilter(
                [
                    'filter'  => $filterVal,
                    'key'     => $filterKey,
                    'filters' => $this->filtersInfo
                ]
            );
            $filterKey = str_replace('_', '-', $filterKey);

            if ($config['mod_rewrite']) {
                $add = ($add && $index != count($this->filters) ? $add . '/' : '') . "{$filterKey}:{$filterVal}";
            } else {
                $add = ($add && $index != count($this->filters) ? $add . '&' : '') . "cf-{$filterKey}={$filterVal}";
            }

            $index++;
        }

        $addUrl .= $addUrl ? ($config['mod_rewrite'] ? '/' : '&') . $add : $add;
    }

    /**
     * @hook  listingsModifyPreSelect
     * @since 2.5.0
     */
    public function hookListingsModifyPreSelect(&$dbCount)
    {
        if ($this->filters) {
            $dbCount = false;
        }
    }

    /**
     * @hook  sitemapAddPluginUrls
     * @since 2.6.0
     */
    public function hookSitemapAddPluginUrls(&$urls = [])
    {
        global $rlDb, $category, $rlCategories, $listing_type, $rlListingTypes;

        define('SITEMAP_BUILD', true);

        // Get all active exist filter boxes
        $sql = 'SELECT `T1`.`Mode`, `T1`.`Type`, `T1`.`Category_IDs`, `T2`.`Content` ';
        $sql .= 'FROM `{db_prefix}category_filter` AS `T1` ';
        $sql .= "LEFT JOIN `{db_prefix}blocks` AS `T2` ON CONCAT('categoryFilter_', `T1`.`ID`) = `T2`.`Key` ";
        $sql .= "WHERE `T2`.`Status` = 'active'";
        $filters = $rlDb->getAll($sql);

        if (!$filters) {
            return;
        }

        // Build internal urls of filters
        $filterUrls = [];

        foreach ($filters as $filter) {
            switch ($filter['Mode']) {
                case 'type':
                case 'category':
                    preg_match('/filter_info\s=\s\'([^\']*)\';/', trim($filter['Content']), $matches);
                    $filterInfo = $matches[1] ? $matches[1] : '';

                    preg_match('/filter_fields\s=\s\'([^\']*)\';/', trim($filter['Content']), $matches);
                    $filterFields = $matches[1] ? $matches[1] : '';

                    if ($filterInfo && $filterFields) {
                        $this->buildFilterBox = false;
                        $decodedFilterInfo    = unserialize(base64_decode($filterInfo));
                        $deniedFields         = unserialize(base64_decode($GLOBALS['lang']['cf_no_index']));

                        // Exclude blocked fields from content
                        if ($deniedFields) {
                            $tmpFilterFields = unserialize(base64_decode($filterFields));

                            foreach ($tmpFilterFields as $filterKey => $filter) {
                                if (isset($deniedFields[$filter['Key']])) {
                                    unset($tmpFilterFields[$filterKey]);
                                }
                            }

                            if (!$tmpFilterFields) {
                                break;
                            } else {
                                $filterFields = base64_encode(serialize($tmpFilterFields));
                            }
                        }

                        if ($decodedFilterInfo['Type']) {
                            $typeKey = $decodedFilterInfo['Type'];
                            $listing_type = $rlListingTypes->types[$typeKey];
                        }

                        if ($decodedFilterInfo['Mode'] === 'category') {
                            foreach (explode(',', $decodedFilterInfo['Category_IDs']) as $categoryID) {
                                $category     = $rlCategories->getCategory($categoryID);
                                $listing_type = $rlListingTypes->types[$category['Type']];

                                $filterUrls = array_merge(
                                    $filterUrls,
                                    $this->request($filterInfo, $filterFields)
                                );
                            }
                        } else {
                            $filterUrls = array_merge($filterUrls, $this->request($filterInfo, $filterFields));
                        }
                    }
                    break;
            }
        }

        if ($filterUrls) {
            $urls = array_merge($urls, $filterUrls);
        }
    }

    /**
     * @hook  sitemapGetRobotsRules
     * @since 2.6.0
     */
    public function hookSitemapGetRobotsRules($rlSitemap)
    {
        // Get filters which will be excluded from bots crawling
        if ($deniedFields = unserialize(base64_decode($GLOBALS['lang']['cf_no_index']))) {
            foreach ($deniedFields as $filterKey => $filter) {
                $rlSitemap->addRuleInRobots("Disallow: /*{$filterKey}:*");
            }
        }
    }

    /**
     * @hook  pageTitle
     * @since 2.6.0
     *
     * @param  string $title
     * @return bool
     */
    public function hookPageTitle(&$title)
    {
        global $categoryFilter_activeBoxID, $page_info, $category;

        if ($categoryFilter_activeBoxID && !$this->filtersInfo) {
            $this->filtersInfo = $this->getFilterFields($categoryFilter_activeBoxID);
        }

        if (!$this->filtersInfo || !$this->filters) {
            return false;
        }

        $dataForTitle       = $this->getFilteredData('title');
        $dataForDescription = $this->getFilteredData('description');
        $dataForH1          = $this->getFilteredData('H1');

        // Software use this logic before 4.7.0 version
        // @todo - Remove this code when compatible will be more then 4.7.0
        if (is_array($title)) {
            $title[0] .= $dataForTitle;
        } else {
            $title .= $dataForTitle;
        }

        if ($page_info['meta_title'] == '') {
            $page_info['meta_title'] = is_array($title) ? $title[0] : $title;
        } else {
            $page_info['meta_title'] .= $dataForTitle;
        }

        // Add filtered data for name in Social Meta Data plugin
        if (isset($category)) {
            if ($category['title'] == '') {
                $category['title'] = $title;
            } else {
                $category['title'] .= $dataForTitle;
            }
        }

        $page_info['meta_description'] .= $page_info['meta_description'] == ''
        ? str_replace(' / ', '', $dataForDescription)
        : $dataForDescription;

        $page_info['h1'] .= $page_info['h1'] == '' ? str_replace(' / ', '', $dataForH1) : $dataForH1;

        return true;
    }

    /**
     * Get SEO data of selected filters for adding to title/description/H1 tags in page
     *
     * @since 2.7.0
     *
     * @param  string $type - Type can be: title|description|H1
     * @return string       - Phrase with data of selected filters
     */
    public function getFilteredData($type = 'title')
    {
        $filteredData = '';

        if (empty($type)
            || !in_array($type, ['title', 'description', 'H1'])
            || !$this->filters
            || !$this->filtersInfo
        ) {
            return $filteredData;
        }

        global $lang, $rlDb;

        foreach ($this->filters as $filterKey => $filterValue) {
            $filterKey  = $filterKey == 'category_id' ? 'Category_ID' : $filterKey;
            $filterInfo = $this->filtersInfo[$filterKey];

            if ($filterKey === 'currency' || $filterInfo["Data_in_{$type}"] === '0') {
                continue;
            }

            $itemNames  = $filterInfo['Item_names'] ? unserialize(base64_decode($filterInfo['Item_names'])) : '';
            $filterName = $lang[$filterInfo['pName']];

            switch ($filterInfo['Type']) {
                case 'bool':
                    if ($itemNames) {
                        $bool = $lang[$itemNames[$filterValue]];
                    } else {
                        $bool = $filterValue ? $lang['yes'] : $lang['no'];
                    }

                    $filteredData .= "{$filterName}: {$bool}, ";
                    break;
                case 'radio':
                case 'select':
                    if ($filterInfo['Mode'] === 'checkboxes') {
                        $filterValues = explode(',', $filterValue);
                    } else {
                        $filterValues = [$filterValue];
                    }

                    foreach ($filterValues as $expValue) {
                        if ($filterInfo['Condition'] == 'years') {
                            $filteredData .= "{$filterName}: {$expValue}, ";
                        } else {
                            if ($itemNames && $itemNames[$expValue]) {
                                $phrase = $itemNames[$expValue];
                            } else {
                                if ($filterInfo['Condition']) {
                                    $phrase = "data_formats+name+{$filterInfo['Condition']}_{$expValue}";

                                    if (!$lang[$phrase]) {
                                        $phrase = "data_formats+name+{$expValue}";
                                    }
                                } else {
                                    if ($filterKey == 'posted_by') {
                                        $phrase = "account_types+name+{$expValue}";
                                    } else {
                                        if ($filterKey == 'Category_ID') {
                                            $categoryKey = $rlDb->getOne('Key', "`ID` = {$expValue}", 'categories');
                                            $phrase      = "categories+name+{$categoryKey}";
                                        } else {
                                            $phrase = "listing_fields+name+{$filterKey}_{$expValue}";
                                        }
                                    }
                                }
                            }

                            if (!$lang[$phrase]
                                && ($GLOBALS['multi_formats'][$filterInfo['Condition']]
                                    || 0 === strpos($phrase, 'categories+name+')
                                )
                            ) {
                                $lang[$phrase] = $GLOBALS['rlLang']->getPhrase(['key' => $phrase, 'db_check' => true]);
                            }

                            $filteredData .= "{$filterName}: ";
                            $filteredData .= ($lang[$phrase] ?: $lang['not_available']) . ', ';
                        }
                    }
                    break;
                case 'checkbox':
                    $out = '';
                    foreach (explode(',', $filterValue) as $expValue) {
                        if ($itemNames) {
                            $phrase = $itemNames[$expValue];
                        } else {
                            if ($filterInfo['Condition']) {
                                $phrase = "data_formats+name+{$filterInfo['Condition']}_{$expValue}";

                                if (!$lang[$phrase]) {
                                    $phrase = "data_formats+name+{$expValue}";
                                }
                            } else {
                                $phrase = "listing_fields+name+{$filterKey}_{$expValue}";
                            }
                        }

                        $out .= $lang[$phrase] . ', ';
                    }

                    $filteredData .= $filterName . ': ' . rtrim($out, ', ') . '; ';
                    break;
                default:
                    $phrase = $itemNames ? $lang[$itemNames[$filterValue]] : $filterValue;

                    if ($filterInfo['Type'] === 'price' && $this->filters['currency']) {
                        $currencyName = $lang["data_formats+name+{$this->filters['currency']}"];
                        $filteredData .= "{$filterName}: {$phrase} {$currencyName}, ";
                    } else {
                        $filteredData .= "{$filterName}: {$phrase}, ";
                    }
                    break;
            }

            unset($phrase);
        }

        if ($filteredData) {
            $filteredData = rtrim($filteredData, ', ');
            $filteredData = str_replace('{filtered_data}', $filteredData, $lang['category_filter_filtered']);
        }

        return $filteredData;
    }

    /**
     * Sum array elements, arrays should have the same keys
     *
     * @param array $array1
     * @param array $array2
     */
    public function flArraySum($array1, $array2)
    {
        if (!$array1 || !$array2) {
            return $array1;
        }

        foreach ($array1 as $key => $value) {
            $out[$key] = $value + $array2[$key];
        }

        return $out;
    }

    /**
     * Recount counters of all active filter boxes
     *
     * @since 2.7.0 - Updated $listingData, $categoryData parameters
     *              - Added ability to send data as arrays
     *
     * @param int       $filterID     - ID of filter box
     * @param int|array $listingData  - ID/data of added/edited listing
     * @param array     $categoryData - ID & Type of category
     */
    public function recountFilters($filterID = 0, $listingData = null, $categoryData = [])
    {
        global $rlDb, $config, $rlListingTypes;

        self::internalCacheSizeHandler();
        $this->saveNoIndexFields();

        $filterID    = (int) $filterID;
        $categoryID = (int) $categoryData['ID'];
        $listingID  = (int) (is_array($listingData) ? $listingData['ID'] : $listingData);

        // Get info of current filter
        if ($filterID) {
            $sql = 'SELECT `T1`.`ID`, `T1`.`Mode` ';
            $sql .= 'FROM `{db_prefix}category_filter` AS `T1` ';
            $sql .= 'LEFT JOIN `{db_prefix}blocks` AS `T2` ';
            $sql .= "ON CONCAT('categoryFilter_', `T1`.`ID`) = `T2`.`Key` ";
            $sql .= "WHERE `T2`.`Status` = 'active' AND `T1`.`ID` = {$filterID}";
            $filters = $rlDb->getAll($sql);
        }
        // Get listing info && get filters by listing
        else if ($listingID) {
            if (is_int($listingData)) {
                $GLOBALS['reefless']->loadClass('Listings');
                $listingData = $GLOBALS['rlListings']->getListing($listingData);
            }

            if (!$listingData['Category_ID'] || !$listingData['Listing_type']) {
                $sql = 'SELECT `T1`.`Category_ID`, `T3`.`Type` AS `Listing_type`, `T3`.`Parent_IDs` ';
                $sql .= 'FROM `{db_prefix}listings` AS `T1` ';
                $sql .= 'LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ';
                $sql .= "WHERE `T1`.`ID` = {$listingID} LIMIT 1";
                $listingData = $rlDb->getRow($sql);
            }

            $listingData['Category_ID']  = $listingData['Category_ID'] ?: $listingData['CategoryInfo']['ID'];
            $listingData['Listing_type'] = $listingData['Listing_type'] ?: $listingData['CategoryInfo']['Type'];
            $listingData['Parent_IDs']   = $listingData['CategoryInfo']['Parent_IDs'];

            // Get category info
            if ($listingData['CategoryInfo']) {
                $categoryInfo = $listingData['CategoryInfo'];
            } else {
                $sql = "SELECT `ID`, `Parent_IDs`, `Level` FROM `{db_prefix}categories` WHERE `Status` = 'active' ";
                $sql .= "AND `ID` = {$listingData['Category_ID']}";
                $categoryInfo = $rlDb->getRow($sql);
            }

            $listingFieldsIDs = [];

            if ($rlDb->getOne('ID', "`Mode` = 'category'", 'category_filter')) {
                $categoryData = version_compare($config['rl_version'], '4.7.0') <= 0
                ? $listingData['Category_ID']
                : [
                    'ID'         => $listingData['Category_ID'],
                    'Parent_IDs' => $listingData['Parent_IDs'],
                ];

                $listingType = $rlListingTypes->types[$listingData['Listing_type']] ?? $listingData['Listing_type'];
                $listingForm = Category::buildForm($categoryData, $listingType);

                foreach ($listingForm as $group) {
                    if ($group['Fields']) {
                        foreach ($group['Fields'] as $fieldData) {
                            if ($fieldData['ID']) {
                                $listingFieldsIDs[] = $fieldData['ID'];
                            }
                        }
                    }
                }

                $listingFieldsIDs = implode(',', $listingFieldsIDs);
            }

            $parentCategoryIDs = [];

            if ($categoryInfo['Level'] > 0) {
                $parentCategoryIDs = array_reverse(explode(',', $categoryInfo['Parent_IDs']));
            }

            if ($parentCategoryID = $parentCategoryIDs[0] ?: $categoryInfo['ID']) {
                $sql = 'SELECT `ID` FROM `{db_prefix}categories` ';
                $sql .= "WHERE `Status` = 'active' AND (`Parent_IDs` = {$parentCategoryID} ";
                $sql .= "OR `Parent_IDs` LIKE '%,{$parentCategoryID}') ORDER BY `Level`";
                $childCategories = $rlDb->getAll($sql);

                // Add parent category id to array with child categories
                $childCategories[]['ID'] = $parentCategoryID;

                // Get filters associated with listing
                $sql = 'SELECT `T1`.`ID`, `T1`.`Mode` FROM `{db_prefix}category_filter` AS `T1` ';
                $sql .= 'LEFT JOIN `{db_prefix}category_filter_field` AS `T2` ON `T1`.`ID` = `T2`.`Box_ID` ';
                $sql .= 'LEFT JOIN `{db_prefix}blocks` AS `T3` ';
                $sql .= "ON CONCAT('categoryFilter_', `T1`.`ID`) = `T3`.`Key` ";
                $sql .= 'WHERE ';
                $sql .= $listingFieldsIDs ? "FIND_IN_SET(`T2`.`Field_ID`, '{$listingFieldsIDs}') > 0 AND " : "";
                $sql .= "`T2`.`Status` = 'active' AND `T3`.`Status` = 'active' ";
                $sql .= 'AND (';

                foreach ($childCategories as $childCategory) {
                    $sql .= "FIND_IN_SET({$childCategory['ID']}, `T1`.`Category_IDs`) > 0 OR ";
                }

                $sql = rtrim($sql, ' OR');
                $sql .= " OR `T1`.`Type` = '{$listingData['Listing_type']}') ";

                if (is_array($listingData) && $rlDb->getOne('ID', "`Mode` = 'field_bound_boxes'", 'category_filter')) {
                    $sql .= "OR (`T1`.`Mode` = 'field_bound_boxes'
                                AND REPLACE(`T1`.`Type`, 'fbb_', '')
                                IN ('" . implode("', '", array_keys($listingData)) . "')
                            ) ";
                }

                $sql .= 'GROUP BY `T1`.`ID` ';
                $filters = $rlDb->getAll($sql);
            }
        }
        // Get filters by related category
        else if ($categoryID) {
            $type = $categoryData['Type'] ?: $rlDb->getOne('Type', "`ID` = {$categoryID}", 'categories');

            $sql = 'SELECT `T1`.`ID`, `T1`.`Mode` ';
            $sql .= 'FROM `{db_prefix}category_filter` AS `T1` ';
            $sql .= 'LEFT JOIN `{db_prefix}blocks` AS `T2` ';
            $sql .= "ON CONCAT('categoryFilter_', `T1`.`ID`) = `T2`.`Key` ";
            $sql .= "WHERE `T2`.`Status` = 'active' ";
            $sql .= "AND (FIND_IN_SET({$categoryID}, `T1`.`Category_IDs`) OR `T1`.`Type` = '{$type}') ";
            $sql .= "AND (`T1`.`Mode` = 'category' OR `T1`.`Mode` = 'type')";
            $filters = $rlDb->getAll($sql);
        }
        // Get all exist filters
        else {
            $sql = 'SELECT `T1`.`ID`, `T1`.`Mode` ';
            $sql .= 'FROM `{db_prefix}category_filter` AS `T1` ';
            $sql .= 'LEFT JOIN `{db_prefix}blocks` AS `T2` ';
            $sql .= "ON CONCAT('categoryFilter_', `T1`.`ID`) = `T2`.`Key` ";
            $sql .= "WHERE `T2`.`Status` = 'active' ";
            $filters = $rlDb->getAll($sql);
        }

        foreach ($filters as $filter) {
            $this->enableHandlerToUpdateCounts($filter['ID']);

            if (!in_array($filter['Mode'], ['category', 'type'])) {
                continue;
            }

            $update = [];
            foreach ($this->updateFields($filter['ID']) as $field) {
                if ($field['Key'] == 'Category_ID') {
                    $update[] = [
                        'fields' => ['Items' => $field['Items'] ? '1' : '0'],
                        'where'  => ['ID' => $field['ID']],
                    ];
                } else {
                    $update[] = [
                        'fields' => ['Items' => $field['Items']
                            ? base64_encode(serialize($field['Items']))
                            : ''],
                        'where'  => ['ID' => $field['ID']],
                    ];
                }
            }

            $rlDb->update($update, 'category_filter_field');
            $this->updateSystemBox($filter['ID']);
        }
    }

    /**
     * Join all necessary tables
     *
     * @hook listingsModifyJoin
     */
    public function join()
    {
        global $sql;

        if (!$this->filters) {
            return;
        }

        if (false === strpos($sql, 'AS `CURCONV`')
            && $this->converterExist
            && $this->priceFieldExist
            && ($_SESSION['curConv_code'] || $_COOKIE['curConv_code'])
        ) {
            $sql .= 'LEFT JOIN `{db_prefix}currency_rate` AS `CURCONV` ON ';
            $sql .= "SUBSTRING_INDEX(REPLACE(`T1`.`{$GLOBALS['config']['price_tag_field']}`, ";
            $sql .= "'currency_', ''), '|', -1) = ";
            $sql .= "`CURCONV`.`Key` AND `CURCONV`.`Status` = 'active' ";
        }

        if (isset($this->filters['posted_by']) && false === strpos($sql, 'accounts` AS `T7` ON')) {
            $sql .= 'LEFT JOIN `{db_prefix}accounts` AS `T7` ON `T1`.`Account_ID` = `T7`.`ID` ';
        }
    }

    /**
     * Save all fields with enabled "No-index" option to cache
     *
     * @since 2.4.0
     */
    public function saveNoIndexFields()
    {
        $sql = 'SELECT `T2`.`Key`, `T1`.`No_index` FROM `{db_prefix}category_filter_field` AS `T1` ';
        $sql .= 'LEFT JOIN `{db_prefix}listing_fields` AS `T2` ON `T1`.`Field_ID` = `T2`.`ID` ';
        $sql .= "WHERE `T1`.`No_index` = '1' ";
        $this->updateNoIndexPhrase($GLOBALS['rlDb']->getAll($sql, 'Key'));
    }

    /**
     * Update value in "cf_no_index" phrase (it available in all languages)
     * The plugin use it as internal cache (it must changed in future)
     *
     * @since 2.6.0
     *
     * @param array $fields - Array with data about no-index fields
     */
    protected function updateNoIndexPhrase($fields = [])
    {
        global $rlDb, $rlLang;

        $phrase = [
            'fields' => ['Value' => $fields ? base64_encode(serialize($fields)) : ''],
            'where'  => ['Key'   => 'cf_no_index'],
        ];

        if (method_exists($rlLang, 'updatePhrase')) {
            $rlLang->updatePhrase($phrase);
        } else {
            $rlDb->updateOne($phrase, 'lang_keys');
        }
    }

    /**
     * Encode value of filter for using in url
     *
     * @since 2.6.0 - Added $key, $filters parameters into params list
     * @since 2.4.0
     *
     * @param  array  $params - Value of filter
     *                        - Name of variable to assign in smarty
     *                        - Key of filter
     *                        - Data of filters
     * @return string
     */
    public function encodeFilter($params = [])
    {
        if (!$params || ($params && $params['filter'] == '')) {
            return '';
        }

        $filterInfo = [];
        $filterKey  = str_replace('-', '_', $params['key']);
        $filters    = $params['filters'];
        $filter     = $params['filter'];
        $assign     = $params['assign'];

        if ($filterKey) {
            foreach ($filters as $filterItem) {
                if ($filterItem['Key'] == $filterKey) {
                    $filterInfo = $filterItem;
                    break;
                }
            }

            if ($filterInfo) {
                switch ($filterInfo['Type']) {
                    case 'checkbox':
                    case 'select':
                    case 'radio':
                        if ($filterKey == 'posted_by' || $filterInfo['Condition'] == 'years') {
                            break;
                        }

                        // Revert commas in filter
                        if (in_array($filterInfo['Type'], ['checkbox', 'select'])) {
                            $filter = str_replace('%2C', ',', $filter);
                        }

                        $ids     = explode(',', $filter);
                        $phrases = [];

                        foreach ($ids as $id) {
                            if ($filterInfo['Condition']) {
                                $phraseKey = "data_formats+name+{$id}";
                            } else {
                                $phraseKey = "listing_fields+name+{$filterKey}_{$id}";
                            }

                            $phrases[] = $GLOBALS['lang'][$phraseKey];
                        }

                        // Replace keys to SEO values of filter
                        $filter = implode(',', $phrases);
                        break;
                }
            }
        }

        $filter = urlencode(strtr($filter, $this->specialChars));

        // Revert commas in filters
        if (in_array($filterInfo['Type'], ['checkbox', 'select'])) {
            $filter = str_replace('%2C', ',', $filter);
        }

        if ($assign) {
            $GLOBALS['rlSmarty']->assign($assign, $filter);
        } else {
            return $filter;
        }
    }

    /**
     * Delete category filter box
     *
     * @since 2.5.0 - Package changed from xAjax to ajax
     *
     * @param int $id
     */
    public function ajaxDeleteBox($id)
    {
        global $rlDb, $rlLang;

        $id = (int) $id;

        if (!$id) {
            return [
                'status'  => 'ERROR',
                'message' => $GLOBALS['rlLang']->getPhrase('cf_remove_box_notify_fail', null, null, true),
            ];
        }

        $rlDb->delete(['ID' => $id], 'category_filter');
        $rlDb->delete(['Key' => "categoryFilter_{$id}"], 'blocks');
        $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` LIKE 'category_filter+name+{$id}_%'");

        if (method_exists($rlLang, 'deletePhrase')) {
            $rlLang->deletePhrase(['Key' => "blocks+name+categoryFilter_{$id}"]);
        } else {
            $rlDb->delete(['Key' => "blocks+name+categoryFilter_{$id}"], 'lang_keys', null, null);
        }

        $rlDb->delete(['Box_ID' => $id], 'category_filter_field', null, null);
        $rlDb->delete(['Category_ID' => $id], 'category_filter_relation', null, null);
        $rlDb->delete(['Filter_ID' => $id], 'category_filter_counts', null, null);

        return [
            'status'  => 'OK',
            'message' => $GLOBALS['rlLang']->getPhrase('category_filter_filter_box_deleted', null, null, true),
        ];
    }

    /**
     * Remove range row
     *
     * @since 2.5.0 - Package changed from xAjax to ajax
     */
    public function ajaxRemoveRow($item)
    {
        global $rlDb, $rlLang;

        $boxID   = (int) $_REQUEST['box_id'];
        $fieldID = (int) $_REQUEST['field_id'];

        if (!$boxID || !$fieldID || !$item) {
            return [
                'status'  => 'ERROR',
                'message' => $GLOBALS['rlLang']->getPhrase('cf_remove_row_notify_fail', null, null, true),
            ];
        }

        $where = ['Box_ID' => $boxID, 'Field_ID' => $fieldID];
        $field = $rlDb->fetch(['ID', 'Item_names'], $where, null, 1, 'category_filter_field', 'row');
        $field = unserialize(base64_decode($field['Item_names']));

        unset($field[$item]);

        $rlDb->updateOne(
            [
                'fields' => ['Item_names' => base64_encode(serialize($field))],
                'where'  => $where,
            ],
            'category_filter_field'
        );

        if (method_exists($rlLang, 'deletePhrase')) {
            $rlLang->deletePhrase(['Key' => "category_filter+name+{$boxID}_{$fieldID}_{$item}"]);
        } else {
            $rlDb->query(
                "DELETE FROM `{db_prefix}lang_keys`
                 WHERE `Key` = 'category_filter+name+{$boxID}_{$fieldID}_{$item}'"
            );
        }

        $this->recountFilters($boxID);

        return ['status' => 'OK'];
    }

    /**
     * Enable handler for updating counts in database
     *
     * @since 2.7.0
     *
     * @param  int
     * @return bool
     */
    protected function enableHandlerToUpdateCounts($filterID)
    {
        if (0 === $filterID = (int) $filterID) {
            return false;
        }

        $GLOBALS['rlDb']->updateOne(
            [
                'fields' => ['Update_handler' => '1'],
                'where'  => ['Filter_ID' => $filterID],
            ],
            'category_filter_counts'
        );

        return true;
    }

    /**
     * Control the size of internal cache table
     *
     * @since 2.11.0
     *
     * @return void
     */
    public static function internalCacheSizeHandler(): void
    {
        global $rlDb;

        // Count all existing rows in internal cache table
        $counts = (int) $rlDb->getRow('SELECT COUNT(*) FROM `{db_prefix}category_filter_counts`', 'COUNT(*)');

        // Clean up the internal cache table
        if ($counts && $counts > 5000) {
            $rlDb->query('TRUNCATE TABLE `{db_prefix}category_filter_counts`');
        }
    }

    /**
     * Get all IDs of related categories by key of listing type
     *
     * @since 2.7.5
     *
     * @param $key - Key of listing type
     * @return string
     */
    public function getCategoryIDs($key)
    {
        $key = (string) $key;

        if (!$key || version_compare($GLOBALS['config']['rl_version'], '4.8.1', '<')) {
            return '';
        }

        $GLOBALS['rlDb']->query('SET SESSION group_concat_max_len = 1000000;');

        $sql = "SELECT GROUP_CONCAT(`ID`) FROM `{db_prefix}categories` ";
        $sql .= "WHERE `Status` = 'active' AND `Type` = '{$key}' ";

        return (string) rtrim($GLOBALS['rlDb']->getRow($sql, 'GROUP_CONCAT(`ID`)'), ',');
    }

    /**
     * Update list of selected categories in filter box
     *
     * @since 2.7.6
     *
     * @param  $category
     * @return bool
     */
    public function updateCategoryIDsInFilter($listingType)
    {
        if (!$listingType || version_compare($GLOBALS['config']['rl_version'], '4.8.1', '<')) {
            return false;
        }

        global $rlDb;

        if ($filterID = $rlDb->getOne('ID', "`Mode` = 'type' AND `Type` = '{$listingType}'", 'category_filter')) {
            $rlDb->updateOne([
                'fields' => ['Category_ID' => $this->getCategoryIDs($listingType)],
                'where'  => ['Key' => 'categoryFilter_' . $filterID],
            ], 'blocks');

            return true;
        }

        return false;
    }

    /**
     * Update list of selected categories in all filter boxes created for listing types
     *
     * @since 2.7.6
     */
    public function updateCategoryIDsInAllFilters()
    {
        global $rlDb;

        $filterBoxes = $rlDb->fetch(['Type'], ['Mode' => 'type'], null, null, 'category_filter');

        foreach ($filterBoxes as $filterBox) {
            $this->updateCategoryIDsInFilter($filterBox['Type']);
        }
    }

    /**
     * Get URL with active filters
     * @since 2.11.0
     * @return string
     */
    public function getActiveFiltersURL(): string
    {
        $url = '';
        foreach ($this->filters as $filterKey => $filterValue) {
            $filterKey = str_replace('_', '-', $filterKey);

            if ($filterKey !== 'category-id') {
                $params      = ['filter' => $filterValue, 'key' => $filterKey, 'filters' => $this->filtersInfo];
                $filterValue = $this->encodeFilter($params);

                if ($GLOBALS['config']['mod_rewrite']) {
                    $url .= "{$filterKey}:{$filterValue}/";
                } else {
                    $url .= "&cf-{$filterKey}={$filterValue}";
                }
            }
        }

        return $url;
    }

    /**
     * Get URL with active filters and optionally assign in the smarty
     *
     * @since 2.11.0
     *
     * @param  array $params - [assign] Name of variable to assign in smarty
     *                       - [baseURL] URL which will be used at first
     *
     * @return string|void
     */
    public function buildActiveFiltersURL($params = [])
    {
        $url = !empty($params['baseURL']) ? (string) $params['baseURL'] : '';
        $url .= $this->getActiveFiltersURL();

        if (!empty($params['assign'])) {
            $GLOBALS['rlSmarty']->assign($params['assign'], $url);
        } else {
            return $url;
        }
    }

    /**
     * @hook  apAjaxRequest
     * @since 2.5.0
     */
    public function hookApAjaxRequest(&$out, $item)
    {
        switch ($item) {
            case 'cfAjaxDeleteBox':
                $out = $this->ajaxDeleteBox($_REQUEST['id']);
                break;
            case 'cfAjaxRemoveRow':
                $out = $this->ajaxRemoveRow($_REQUEST['id']);
                break;
            case 'cfAjaxRecountFilters':
                $this->recountFilters();
                $out = [
                    'status'  => 'OK',
                    'message' => $GLOBALS['rlLang']->getPhrase('category_filter_box_recounted', null, null, true),
                ];
                break;
        }
    }

    /**
     * @hook  apPhpCategoriesAfterAdd
     * @since 2.7.6
     */
    public function hookApPhpCategoriesAfterAdd()
    {
        $this->updateCategoryIDsInFilter($GLOBALS['data']['Type']);
    }

    /**
     * @hook  apPhpCategoriesAfterEdit
     * @since 2.7.6
     */
    public function hookApPhpCategoriesAfterEdit()
    {
        $this->updateCategoryIDsInAllFilters();
    }

    /**
     * @hook  phpUrlBottom
     * @since 2.11.0
     */
    public function hookPhpUrlBottom(&$url, $mode, $category): void
    {
        global $rlSmarty, $config;

        if (version_compare($config['rl_version'], '4.9.3', '<') || $mode !== 'category' || !$category || !$url) {
            return;
        }

        // Detection of the building URL of the category via the category component in the filter field
        $backtrace = debug_backtrace(2);

        $isFilterRequest = array_filter($backtrace, function($item) {
            return $item['function'] === 'request' && $item['class'] === 'rlCategoryFilter';
        });

        if (!$isFilterRequest) {
            return;
        }

        $isRequestFromComponent = array_filter($backtrace, function($item) {
            return $item['function'] === 'categoryUrl'
                && $item['class'] === 'rlSmarty'
                && strpos($item['file'], '_category-box.tpl');
        });

        if (!$isRequestFromComponent) {
            return;
        }

        $listingTypes     = $rlSmarty->_tpl_vars['listing_types'];
        $activeFiltersURL = $this->getActiveFiltersURL();
        $baseURL          = $rlSmarty->_tpl_vars['cfBaseUrl'];

        if (in_array($this->filterInfo['Mode'], ['category', 'type'])) {
            if ($activeFiltersURL) {
                // Add already selected filters to the category link
                if ($config['mod_rewrite']) {
                    if ($config['html_in_categories'] || $listingTypes[$category['Type']]['Cat_postfix']) {
                        $url = str_replace('.html', "/{$activeFiltersURL}", $url);
                    } else {
                        $url = $url . $activeFiltersURL;
                    }
                } else {
                    $url = $url . $activeFiltersURL;
                }
            }
        } elseif (in_array($this->filterInfo['Mode'], ['search_results', 'field_bound_boxes'])) {
            if ($config['mod_rewrite']) {
                $url = "{$baseURL}category-id:{$category['ID']}/{$activeFiltersURL}";
            } else {
                $url = "{$baseURL}&cf-category-id={$category['ID']}{$activeFiltersURL}";
            }
        }

        // Update count with listings in category
        $cfCategoryCounts = $rlSmarty->_tpl_vars['cfCategoryCounts'];
        $cat = &$rlSmarty->_tpl_vars['cat'];

        foreach ($cfCategoryCounts as $cfCategoryCount) {
            if ($cfCategoryCount['Category_ID'] == $cat['ID']) {
                $cat['Count'] = $cfCategoryCount['Number'];
            }
        }
    }

    /**
     * Install process
     */
    public function install()
    {
        global $rlDb;

        // Create general table with filters
        $rlDb->createTable(
            'category_filter',
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
            `Mode` enum('type','category','search_results','field_bound_boxes') NOT NULL DEFAULT 'type',
            `Type` varchar(80) NOT NULL,
            `Category_IDs` mediumtext NOT NULL,
            `Subcategories` ENUM('0','1') NOT NULL DEFAULT '0',
            PRIMARY KEY (`ID`),
            KEY `Mode` (`Mode`)"
        );

        // Create table for filter fields
        $rlDb->createTable(
            'category_filter_field',
            "`ID` int(8) NOT NULL AUTO_INCREMENT,
            `Box_ID` int(8) NOT NULL,
            `Field_ID` int(8) NOT NULL,
            `Items` mediumtext CHARACTER SET utf8 NOT NULL,
            `Item_names` mediumtext NOT NULL,
            `Items_display_limit` varchar(3) NOT NULL,
            `Mode` enum('auto','group','slider','checkboxes', 'text') NOT NULL DEFAULT 'auto',
            `No_index` enum('1','0') NOT NULL DEFAULT '0',
            `Data_in_title` ENUM('1','0') NOT NULL DEFAULT '1',
            `Data_in_description` ENUM('1','0') NOT NULL DEFAULT '1',
            `Data_in_H1` ENUM('1','0') NOT NULL DEFAULT '0',
            `Status` enum('active','approval') NOT NULL DEFAULT 'active',
            PRIMARY KEY (`ID`),
            KEY `Box_ID` (`Box_ID`,`Field_ID`),
            KEY `Field_ID` (`Field_ID`)"
        );

        // Create table with filter fields relation
        $rlDb->createTable(
            'category_filter_relation',
            "`ID` int(8) NOT NULL AUTO_INCREMENT,
            `Position` int(8) NOT NULL,
            `Category_ID` int(6) NOT NULL,
            `Group_ID` int(1) NOT NULL,
            `Fields` varchar(8) NOT NULL,
            PRIMARY KEY (`ID`),
            KEY `Category_ID` (`Category_ID`)"
        );

        /**
         * Create table with counts of filters
         *
         * @since 2.7.0
         */
        $rlDb->createTable(
            'category_filter_counts',
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
            `Filter_ID` int(11) NOT NULL,
            `Category_ID` int(11) NOT NULL,
            `Selected_filters` VARCHAR(512) NOT NULL,
            `Data_counts` MEDIUMTEXT NOT NULL,
            `Update_handler` ENUM('1','0') NOT NULL DEFAULT '0',
            PRIMARY KEY (`ID`),
            KEY `Filter_ID` (`Filter_ID`),
            KEY `Category_ID` (`Category_ID`),
            KEY `Selected_filters` (`Selected_filters`)"
        );

        $this->fixScopes();
    }

    /**
     * Uninstall process
     */
    public function uninstall()
    {
        $GLOBALS['rlDb']->dropTables(
            [
                'category_filter',
                'category_filter_field',
                'category_filter_relation',
                'category_filter_counts',
            ]
        );
    }

    /**
     * Fix phrase scopes for software version <= 4.8.0
     *
     * @todo Remove the method when compatibility will be >= 4.8.0
     *
     * @since 2.7.6
     */
    public function fixScopes()
    {
        if (version_compare($GLOBALS['config']['rl_version'], '4.8.0', '>')) {
            return;
        }

        foreach (['category_filter_ext_caption', 'category_filter_related_categories'] as $phraseKey) {
            $GLOBALS['rlDb']->updateOne(
                ['fields' => ['Module' => 'ext'], 'where'  => ['Key' => $phraseKey]],
                'lang_keys'
            );
        }

        $GLOBALS['rlDb']->query(
            "UPDATE `{db_prefix}lang_keys`
             SET `Module` = 'common'
             WHERE `Module` = '' AND `Plugin` = 'categoryFilter'"
        );
    }

    /**
     * Detect new or old version of the Field Bound Boxes plugin (old it's >= 1.3.0 version)
     *
     * @since 2.7.6
     *
     * @return bool
     */
    public function isNewFieldBoundBoxesPlugin()
    {
        if (!isset($GLOBALS['plugins']['fieldBoundBoxes'])) {
            return false;
        }

        $GLOBALS['reefless']->loadClass('FieldBoundBoxes', null, 'fieldBoundBoxes');

        return isset($GLOBALS['rlFieldBoundBoxes']->lang_elements);
    }

    /**
     * Get path of page related with box created by Field Bound Boxes plugin
     *
     * @since 2.7.6
     *
     * @param $key string
     *
     * @return string
     */
    public function getFbbPath($key = '')
    {
        static $paths = [];

        if (!$key) {
            return '';
        }

        global $rlDb, $config, $page_info;

        if ($paths[$key] === null) {
            if ($page_info && $page_info['Key'] === $key) {
                if ($config['multilingual_paths'] && $page_info['Path_' . RL_LANG_CODE]) {
                    $paths[$key] = $page_info['Path_' . RL_LANG_CODE];
                } else {
                    $paths[$key] = $page_info['Path'];
                }
            }
            // @todo - Remove it when compatibility will be >= 4.8.1
            else {
                if ($this->isNewFieldBoundBoxesPlugin()) {
                    $table  = 'pages';
                    $select = $config['multilingual_paths'] && RL_LANG_CODE !== $config['lang']
                        ? ['Path', 'Path_' . RL_LANG_CODE]
                        : ['Path'];
                } else {
                    $table = 'field_bound_boxes';
                    $select = ['Path'];
                }

                $where       = ['Key' => $key, 'Status' => 'active'];
                $path        = $rlDb->fetch($select, $where, null, null, $table, 'row');
                $paths[$key] = isset($path['Path_' . RL_LANG_CODE]) && $path['Path_' . RL_LANG_CODE]
                    ? $path['Path_' . RL_LANG_CODE]
                    : $path['Path'];
            }
        }

        return $paths[$key];
    }

    /**
     * Decode cached content from box and assign variables
     *
     * @since 2.11.0  - Added $saveFilterInfo, $saveFilterFields parameters
     *               - Changed response from "void" to "array"
     * @since 2.10.0
     *
     * @param string $content
     * @param bool   $saveFilterInfo
     * @param bool   $saveFilterFields
     *
     * @return array
     */
    public function decodeFilterContent(string $content, bool $saveFilterInfo = true, bool $saveFilterFields = true): array
    {
        $result  = [];
        $content = trim($content);

        preg_match('/filter_info\s=\s\'([^\']*)\';/', $content, $matches);
        if ($matches[1]) {
            $result['filterInfo'] = unserialize(base64_decode($matches[1]));

            if ($saveFilterInfo) {
                $this->filterInfo = $result['filterInfo'];
            }
        }

        preg_match('/filter_fields\s=\s\'([^\']*)\';/', $content, $matches);
        if ($matches[1]) {
            $result['filterFields'] = unserialize(base64_decode($matches[1]));

            if ($saveFilterFields) {
                $this->filtersInfo = $result['filterFields'];
            }
        } elseif (!$this->filtersInfo && $this->filterInfo['ID']) {
            $result['filterFields'] = $this->getFilterFields($this->filterInfo['ID']);

            if ($saveFilterFields) {
                $this->filtersInfo = $result['filterFields'];
            }
        }

        return $result;
    }

    /**
     * Detects that on current page uses the geo-filter
     *
     * @since 2.10.0
     *
     * @param array|null $filterInfo
     *
     * @return bool
     */
    public function isGeoFilterPage(?array $filterInfo = null): bool
    {
        global $rlGeoFilter, $config, $category, $rlListingTypes;

        if ($this->geoFilterData
            && $this->geoFilterData['is_filtering'] === true
            && $rlGeoFilter
            && !$rlGeoFilter->searchResultsPage
        ) {
            return true;
        }

        if ($filterInfo) {
            $listingTypeKey = '';
            switch ($filterInfo['Mode']) {
                case 'type':
                    $listingTypeKey = $filterInfo['Type'];
                    break;
                case 'category':
                    if ($category && $rlListingTypes && $rlListingTypes->types) {
                        $listingTypeKey = $rlListingTypes->types[$category['Type']]['Key'];
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
     * Updates the counts of categories in the given array and removes empty categories
     * if the third parameter is true.
     *
     * @since 2.11.2
     *
     * @param array $categories  The array of categories to update.
     * @param array $counts      The array of counts data.
     * @param bool  $removeEmpty Whether to remove empty categories from the array.
     */
    public static function updateCountsInCategories(?array &$categories = [], ?array $counts = [], ?bool $removeEmpty = false): void
    {
        if (!$categories) {
            return;
        }

        foreach ($categories as $categoryKey => &$categoryInfo) {
            if ($counts) {
                if ($counts[$categoryInfo['ID']]) {
                    $categoryInfo['Count'] = (int) $counts[$categoryInfo['ID']]['Number'];
                } else {
                    $categoryInfo['Count'] = 0;
                }
            }

            if ($removeEmpty && (int) $categoryInfo['Count'] === 0) {
                unset($categories[$categoryKey]);
                continue;
            }

            foreach ($categoryInfo['sub_categories'] as $subCatKey => $subCatInfo) {
                if ($counts) {
                    if ($counts[$subCatInfo['ID']]) {
                        $subCatInfo['Count'] = (int) $counts[$subCatInfo['ID']]['Number'];
                    } else {
                        $subCatInfo['Count'] = 0;
                    }
                }

                if ($removeEmpty && (int) $subCatInfo['Count'] === 0) {
                    unset($categories[$categoryKey]['sub_categories'][$subCatKey]);
                }
            }
        }
    }

    /**
     * Update process of the plugin (copy from core)
     * @param string $version
     */
    public function update($version)
    {
        $version_method = 'update' . (int) str_replace('.', '', $version);
        if (method_exists($this, $version_method)) {
            $this->$version_method();
        }
    }

    /**
     * Update to 1.0.2 version
     */
    public function update102()
    {
        global $rlDb;

        $rlDb->setTable('hooks');
        $hooks = (array) $rlDb->fetch(['ID'], ['Name' => 'boot', 'Plugin' => 'categoryFilter']);
        $rlDb->resetTable();

        if (count($hooks) > 1) {
            $rlDb->query(
                "DELETE FROM `{db_prefix}hooks`
                WHERE `Name` = 'boot' AND `Plugin` = 'categoryFilter' LIMIT 1"
            );
        }
    }

    /**
     * Update to 2.0.0 version
     */
    public function update200()
    {
        global $rlDb;

        $rlDb->query(
            "DELETE FROM `{db_prefix}hooks`
            WHERE (`Name` = 'apAjaxBuildFormPreSaving'
                    OR `Name` = 'listingsModifyField'
                    OR `Name` = 'listingDetailsTop'
                    OR `Name` = 'searchMiddle'
                    OR `Name` = 'boot')
                AND `Plugin` = 'categoryFilter'"
        );

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
            WHERE `Key` LIKE 'categoryFilter_%' AND `Plugin` = 'categoryFilter'"
        );

        // Add new type of filter mode (search results)
        $rlDb->query(
            "ALTER TABLE `{db_prefix}category_filter`
            CHANGE `Mode` `Mode` ENUM('type','category','search_results')"
        );

        $this->recountFilters();

        unlink(RL_PLUGINS . 'categoryFilter/categories.tpl');
        unlink(RL_PLUGINS . 'categoryFilter/categories_responsive_42.tpl');
    }

    /**
     * Update to 2.1.0 version
     */
    public function update210()
    {
        global $rlDb;

        // Add new type of filter mode (field_bound_boxes)
        $rlDb->query(
            "ALTER TABLE `{db_prefix}category_filter`
            CHANGE `Mode` `Mode` ENUM('type','category','search_results','field_bound_boxes')"
        );

        // Add new view mode for select fields (checkboxes)
        $rlDb->query(
            "ALTER TABLE `{db_prefix}category_filter_field`
            CHANGE `Mode` `Mode` ENUM('auto','group','slider','checkboxes')"
        );

        // Static files have been moved to footer (remove old hook)
        $rlDb->query(
            "DELETE FROM `{db_prefix}hooks`
            WHERE (`Name` = 'tplHeader' OR `Name` = 'browseBCArea') AND `Plugin` = 'categoryFilter'"
        );
    }

    /**
     * Update to 2.3.0 version
     */
    public function update230()
    {
        global $rlDb;

        $rlDb->query(
            "ALTER TABLE `{db_prefix}category_filter_field`
            ADD COLUMN `No_index` enum('1','0') NOT NULL DEFAULT '0' AFTER `Mode`"
        );

        $rlDb->query(
            "UPDATE `{db_prefix}category_filter_field` SET `No_index` = '1'
            WHERE `Mode` = 'slider'"
        );

        $rlDb->query(
            "UPDATE `{db_prefix}category_filter_field` AS `T1`
            JOIN `{db_prefix}listing_fields` AS `T2` ON `T2`.`ID` = `T1`.`Field_ID`
            SET `T1`.`No_index` = '1' WHERE `T2`.`Type` = 'text'"
        );

        $this->recountFilters();
    }

    /**
     * Update to 2.4.0 version
     */
    public function update240()
    {
        global $rlDb;

        // Create hidden config for storing data about filters with "no-index" option
        $rlDb->query(
            "INSERT INTO `{db_prefix}config` (`Key`, `Group_ID`, `Plugin`)
            VALUES ('cf_no_index', '0', 'categoryFilter')"
        );

        $rlDb->query(
            "DELETE FROM `{db_prefix}hooks`
            WHERE (`Name` = 'apPhpListingsAjaxDeleteListing' OR `Name` = 'phpListingTypeBrowseQuickSearchMode')
            AND `Plugin` = 'categoryFilter'"
        );

        $this->recountFilters();
    }

    /**
     * Update to 2.5.0 version
     */
    public function update250()
    {
        global $rlDb;

        $rlDb->query(
            "DELETE FROM `{db_prefix}hooks`
            WHERE `Name` = 'apPhpControlsBottom' AND `Plugin` = 'categoryFilter'"
        );

        $rlDb->query(
            "ALTER TABLE `{db_prefix}category_filter_field`
            CHANGE `Mode` `Mode` ENUM('auto', 'group', 'slider', 'checkboxes', 'text')"
        );
    }

    /**
     * Update to 2.6.0 version
     */
    public function update260()
    {
        $this->updateNoIndexPhrase(unserialize($GLOBALS['config']['cf_no_index']));

        $GLOBALS['rlDb']->query(
            "DELETE FROM `{db_prefix}config`
            WHERE `Key` = 'cf_no_index' AND `Plugin` = 'categoryFilter'"
        );
    }

    /**
     * Update to 2.7.0 version
     */
    public function update270()
    {
        global $rlDb;

        // Remove old hooks from database
        // Hook "browseTop" must be removed in 2.4.0 version yet
        $rlDb->query(
            "DELETE FROM `{db_prefix}hooks`
            WHERE `Name` IN ('tplFooter', 'browseTop') AND `Plugin` = 'categoryFilter'"
        );

        $rlDb->createTable(
            'category_filter_counts',
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
            `Filter_ID` int(11) NOT NULL,
            `Category_ID` int(11) NOT NULL,
            `Selected_filters` VARCHAR(255) NOT NULL,
            `Data_counts` MEDIUMTEXT NOT NULL,
            `Update_handler` ENUM('1','0') NOT NULL DEFAULT '0',
            PRIMARY KEY (`ID`),
            KEY `Filter_ID` (`Filter_ID`, `Category_ID`, `Selected_filters`)"
        );

        $rlDb->addColumnsToTable(
            [
                'Data_in_title'       => "ENUM('1','0') NOT NULL DEFAULT '1'",
                'Data_in_description' => "ENUM('1','0') NOT NULL DEFAULT '1'",
                'Data_in_H1'          => "ENUM('1','0') NOT NULL DEFAULT '0'",
            ],
            'category_filter_field'
        );
    }

    /**
     * Update to 2.7.2 version
     */
    public function update272()
    {
        global $rlDb;

        $index = $rlDb->getAll(
            "SHOW INDEX FROM `{db_prefix}category_filter_counts`
             WHERE `Key_name` = 'Filter_ID'"
        );

        if ($index) {
            $rlDb->query("DROP INDEX `Filter_ID` ON `{db_prefix}category_filter_counts`;");
        }

        $rlDb->query(
            "ALTER TABLE `{db_prefix}category_filter_counts`
            CHANGE `Selected_filters` `Selected_filters` VARCHAR(512) NOT NULL"
        );
    }

    /**
     * Update to 2.7.3 version
     */
    public function update273()
    {
        global $rlDb;

        $index = $rlDb->getAll(
            "SHOW INDEX FROM `{db_prefix}category_filter_counts`
             WHERE `Key_name` = 'Filter_ID'"
        );

        if ($index) {
            $rlDb->query("DROP INDEX `Filter_ID` ON `{db_prefix}category_filter_counts`;");
        }

        $rlDb->query("CREATE INDEX `Filter_ID` ON `{db_prefix}category_filter_counts` (`Filter_ID`);");
        $rlDb->query("CREATE INDEX `Category_ID` ON `{db_prefix}category_filter_counts` (`Category_ID`);");
        $rlDb->query("CREATE INDEX `Selected_filters` ON `{db_prefix}category_filter_counts` (`Selected_filters`);");
    }

    /**
     * Update to 2.7.4 version
     */
    public function update274()
    {
        $GLOBALS['rlDb']->query(
            "DELETE FROM `{db_prefix}hooks`
            WHERE `Name` = 'pageinfoArea' AND `Plugin` = 'categoryFilter'"
        );
    }

    /**
     * Update to 2.7.5 version
     */
    public function update275()
    {
        if (version_compare($GLOBALS['config']['rl_version'], '4.8.1', '<')) {
            return;
        }

        global $rlDb;

        $sql = 'SELECT `T2`.`ID`, `T2`.`Key`, `T1`.`Type` ';
        $sql .= 'FROM `{db_prefix}category_filter` AS `T1` ';
        $sql .= "LEFT JOIN `{db_prefix}blocks` AS `T2` ON CONCAT('categoryFilter_', `T1`.`ID`) = `T2`.`Key` ";
        $sql .= "WHERE `T1`.`Mode` = 'type' ";
        $filters = $rlDb->getAll($sql);

        foreach ($filters as $filter) {
            $rlDb->updateOne([
                'fields' => [
                    'Cat_sticky' => '0',
                    'Category_ID' => $this->getCategoryIDs($filter['Type']),
                ],
                'where'  => ['Key' => $filter['Key']],
            ], 'blocks');
        }
    }

    /**
     * Update to 2.7.6 version
     */
    public function update276()
    {
        global $rlDb;

        $rlDb->query(
            "UPDATE `{db_prefix}blocks` SET `Category_ID` = TRIM(TRAILING ',' FROM `Category_ID`)
             WHERE `Plugin` = 'categoryFilter'"
        );

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
             WHERE `Key` = 'category_filter_items_auto' AND `Plugin` = 'categoryFilter'"
        );

        register_shutdown_function(function () {
            $this->fixScopes();
        });
    }

    /**
     * Update to 2.8.0 version
     */
    public function update280()
    {
        register_shutdown_function(function () {
            $this->fixScopes();
        });
    }

    /**
     * Update to 2.9.0 version
     */
    public function update290()
    {
        $GLOBALS['reefless']->deleteDirectory(RL_PLUGINS . 'categoryFilter/static/jslider');
        @unlink(RL_PLUGINS . 'categoryFilter/static/jslider.blue.png');
        @unlink(RL_PLUGINS . 'categoryFilter/static/jslider.png');
        @unlink(RL_PLUGINS . 'categoryFilter/static/jslider.round.plastic.png');
    }

    /**
     * Update to 2.11.0 version
     */
    public function update2110()
    {
        $GLOBALS['rlDb']->addColumnToTable(
            'Subcategories',
            "ENUM('0','1') NOT NULL DEFAULT '0' AFTER `Category_IDs`",
            'category_filter'
        );
    }
}
