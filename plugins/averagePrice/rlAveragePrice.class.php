<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLAVERAGEPRICE.CLASS.PHP
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

class rlAveragePrice extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * Table with form relations
     * @var string
     */
    protected $formTable = 'short';

    /**
     * List of denied types of fields for adding in form
     * @var array
     */
    public $deniedFieldTypes = ['price', 'textarea', 'image', 'file', 'mixed', 'phone'];

    /**
     * List of denied keys of fields for adding in form
     * @var array
     */
    public $deniedFieldKeys = ['title'];

    /**
     * Class Constructor
     */
    public function __construct()
    {
        $this->formTable = $GLOBALS['config']['ap_fields_form'];
    }

    /**
     * @hook listingDetailsBottom
     *
     * @return void
     */
    public function hookListingDetailsBottom()
    {
        $listingID = $GLOBALS['listing_id'];

        if (!$listingID) {
            return;
        }

        if (!$priceData = $this->getListingData($listingID)) {
            $_SESSION['apUpdateListingData'] = $listingID;
            $this->removeBox();
            return;
        }

        if (($GLOBALS['config']['ap_hide_box'] && $priceData['Graph_percent'] > 50)
            || !$priceData['Average_price']
            || $priceData['Count_listings'] < 2
        ) {
            $this->removeBox();
            return;
        }

        $this->prepareDataOfBox($listingID);
    }

    /**
     * @hook ajaxRequest
     *
     * @param  array  &$out
     * @param  string $mode
     * @param  string $item
     * @param  array  $lang
     * @return void
     */
    public function hookAjaxRequest(&$out, $mode, $item, $lang)
    {
        $listingID = (int) $item;

        if ($mode !== 'apUpdateListingData' || !$listingID) {
            return;
        }

        $GLOBALS['lang'] = $GLOBALS['rlLang']->getLangBySide('frontEnd', $lang);

        $out = [
            'status' => 'OK',
            'html'   => $this->updateListingData($listingID)
        ];
    }

    /**
     * Update data of listing for graph
     *
     * @param  int  $listingID
     * @return string - HTML of box with updated content
     */
    protected function updateListingData($listingID)
    {
        global $rlSearch, $rlDb, $config;

        $GLOBALS['reefless']->loadClass('Search');

        $listingData      = $GLOBALS['rlListings']->getListing($listingID);
        $listingCriteria  = [];

        $rlDb->delete(['Listing_ID' => $listingID], 'ap_listing_data');

        $rlSearch->fields = $fields = $this->getFields($listingData);

        // Prepare all selected data for search criteria
        foreach ($fields as $field) {
            $listingValue = $listingData[$field['Key']];

            if ($listingValue
                && !in_array($field['Type'], $this->deniedFieldTypes)
                && !in_array($field['Key'], $this->deniedFieldKeys)
            ) {
                switch ($field['Type']) {
                    case 'checkbox':
                        $values = explode(',', $listingValue);
                        // Emulate first empty value
                        array_unshift($values, '0');

                        // Set values as keys to array
                        $listingCriteria[$field['Key']] = array_combine($values, $values);
                        break;
                    case 'number':
                        $listingCriteria[$field['Key']] = [
                            'from' => $listingValue,
                            'to'   => $listingValue,
                        ];
                        break;
                    case 'date':
                        if ($field['Default'] === 'single') {
                            $listingCriteria[$field['Key']] = [
                                'from' => $listingValue,
                                'to'   => $listingValue,
                            ];
                        }
                        break;

                    case 'select':
                        if ($field['Condition'] === 'years') {
                            $listingCriteria[$field['Key']] = [
                                'from' => $listingValue,
                                'to'   => $listingValue,
                            ];
                            break;
                        }
                    default:
                        if ($field['Key'] === 'Category_ID') {
                            $categoryParentIDs = Category::getCategory($listingValue)['Parent_IDs'];
                            $categoryParentIDs = $categoryParentIDs
                            ? $categoryParentIDs . ',' . $listingData['Category_ID']
                            : $listingData['Category_ID'];

                            $listingCriteria['category_parent_ids'] = $categoryParentIDs;
                        }

                        $listingCriteria[$field['Key']] = $listingValue;
                        break;
                }
            }
        }

        // Add custom sorting to get converted prices after search
        if ($GLOBALS['plugins']['currencyConverter']) {
            // Add "sort_field" to support old logic of sorting
            // @todo - Remove it in future versions when compatible will be >= 4.7.0
            $listingCriteria['sort_by']   = $listingCriteria['sort_field'] = 'price';
            $listingCriteria['sort_type'] = 'asc';
        }

        $parentCategoryID = $listingData['Parent_IDs']
        ? explode(',', $listingData['Parent_IDs'])[0]
        : $listingData['Category_ID'];

        $limit         = (int) $rlDb->getOne('Count', "`ID` = {$parentCategoryID}", 'categories');
        $totalPrice    = 0;
        $listings      = $rlSearch->search($listingCriteria, $listingData['Listing_Type'], 0, $limit);
        $countListings = count($listings);
        $listingPrice  = 0;

        foreach ($listings as $listing) {
            $currentPrice = 0;

            // Count total of converted prices
            if ($listing['cc_price_tmp']) {
                $currentPrice += round((float) preg_replace("/[^0-9,\.]/", '', explode('|', $listing['cc_price_tmp'])[0]));
            }

            // Count total of simple prices
            else if ($listing[$config['price_tag_field']]) {
                $currentPrice += round((float) preg_replace("/[^0-9,\.]/", '', explode('|', $listing[$config['price_tag_field']])[0]));
            }

            $totalPrice += $currentPrice;

            // Save price of current listing
            if ($listing['ID'] === $listingData['ID']) {
                $listingPrice = $currentPrice;
            }
        }

        $averagePrice = $totalPrice && $countListings ? round($totalPrice / $countListings) : 0;

        if (!$listingPrice || !$averagePrice) {
            return '';
        }

        $graphPercent = round($listingPrice * 100 / ($averagePrice * 2));

        $rlDb->insertOne(
            [
                'Listing_ID'       => $listingData['ID'],
                'Listing_Type_Key' => $listingData['Listing_type'],
                'Saved_date'       => 'NOW()',
                'Average_price'    => $averagePrice,
                'Listing_price'    => $listingPrice,
                'Compared_data'    => json_encode($listingCriteria),
                'Count_listings'   => $countListings,
                'Graph_percent'    => $graphPercent > 100 ? 100 : $graphPercent,
            ],
            'ap_listing_data',
            ['Compared_data']
        );

        if (($config['ap_hide_box'] && $graphPercent > 50) || !$averagePrice || $countListings < 2) {
            return '';
        } else {
            return $this->updateContentOfBox($listingID);
        }
    }

    /**
     * Collect data of listing and update graph in box
     *
     * @param  int   $listingID
     * @return string - HTML of box with updated content
     */
    protected function updateContentOfBox($listingID)
    {
        global $rlSmarty, $config, $lang, $plugins;

        if (version_compare($config['rl_version'], '4.8.1', '>=')) {
            $phraseKey = 'blocks+name+averagePrice';
            $boxTitle = $GLOBALS['rlLang']->getPhrase($phraseKey, null, false, true);
            $lang[$phraseKey] = $boxTitle;
        }

        // Collect data for box and replace content of them
        $sql = "SELECT `ID`, `Key`, `Side`, `Type`, `Content`, `Tpl`, `Header`, ";
        $sql .= "`Position`, `Plugin` FROM `{db_prefix}blocks`";
        $sql .= "WHERE `Key` = 'averagePrice' AND `Status` = 'active'";
        $blockData = $GLOBALS['rlDb']->getAll($sql);
        $blockData = $GLOBALS['rlLang']->replaceLangKeys($blockData, 'blocks', ['name'])[0];
        $rlSmarty->assign_by_ref('block', $blockData);

        $rlSmarty->assign_by_ref('config', $config);
        $rlSmarty->assign_by_ref('lang', $lang);
        $rlSmarty->assign('pages', $GLOBALS['rlNavigator']->getAllPages());

        $seoBase = RL_URL_HOME;
        if ($config['lang'] != RL_LANG_CODE && $config['mod_rewrite']) {
            $seoBase .= RL_LANG_CODE . '/';
        }
        if (!$config['mod_rewrite']) {
            $seoBase .= 'index.php';
        }

        define('SEO_BASE', $seoBase);
        $rlSmarty->assign('rlBase', $seoBase);

        function smartyEval($param, $content)
        {
            return $content;
        }

        function insert_eval($params, &$smarty)
        {
            require_once RL_LIBS . 'smarty' . RL_DS . 'plugins' . RL_DS . 'function.eval.php';
            return smarty_function_eval(['var' => $params['content']], $smarty);
        }

        $rlSmarty->register_block('eval', 'smartyEval', false);

        // Add support old versions of the software
        // @todo - Remove it when compatible of the plugin will be >= 4.6.1
        $search_results_url = $GLOBALS['search_results_url'];

        if (!$search_results_url) {
            require RL_LIBS . 'system.lib.php';
            $GLOBALS['search_results_url'] = $search_results_url;
        }

        if ($config['mf_format_keys']) {
            $rlSmarty->assign_by_ref('multi_format_keys', explode('|', $config['mf_format_keys']));
        }

        $this->prepareDataOfBox($listingID);

        $html = '';

        if ($plugins['multiField']) {
            $html .= '<script>mfFields = []; mfFieldVals = []</script>';
        }

        $html .= $rlSmarty->fetch(RL_ROOT . "templates/{$config['template']}/tpl/blocks/blocks_manager.tpl");

        if ($plugins['multiField']) {
            $html .= "<script>
                        for (var i in mfFields) {
                            (function(fields, values, index){
                                var \$form = null;

                                if (index.indexOf('|') >= 0) {
                                    var form_key = index.split('|')[1];
                                    \$form = $('#area_' + form_key).find('form');
                                    \$form = \$form.length ? \$form : null;
                                }

                                var mfHandler = new mfHandlerClass();
                                mfHandler.init(mf_prefix, fields, values, \$form);
                            })(mfFields[i], mfFieldVals[i], i);
                        }
                    </script>";
        }

        return $html;
    }

    /**
     * Collect all necessary data for box with graph
     *
     * @param  int  $listingID
     * @return void
     */
    protected function prepareDataOfBox($listingID)
    {
        global $config, $rlSmarty, $rlValid, $rlListings, $rlCommon;

        $listingID   = (int) $listingID;
        $listingData = $GLOBALS['listing_data'] ?: $rlListings->getListing($listingID);

        if (!$listingID) {
            return;
        }

        if (!$priceData = $this->getListingData($listingID)) {
            return;
        }

        // Get data of average & listing prices
        $averagePrice = $priceData['Average_price'];
        $listingPrice = $priceData['Listing_price'];

        if (($config['ap_hide_box'] && $priceData['Graph_percent'] > 50)
            || !$averagePrice
            || $priceData['Count_listings'] < 2
        ) {
            return;
        }

        $listingType = $GLOBALS['rlListingTypes']->types[$priceData['Listing_Type_Key']];
        $showCents   = isset($listingType['Show_cents']) ? (bool) $listingType['Show_cents'] : $config['show_cents'];

        $averagePrice = $rlValid->str2money($averagePrice, $showCents);
        $listingPrice = $rlValid->str2money($listingPrice, $showCents);

        // Add system currency for prices
        $cBefore      = $config['system_currency_position'] == 'before';
        $currency     = $GLOBALS['plugins']['currencyConverter'] ? 'USD' : $config['system_currency'];
        $averagePrice = $cBefore ? $currency . ' ' . $averagePrice : $averagePrice . ' ' . $currency;
        $listingPrice = $cBefore ? $currency . ' ' . $listingPrice : $listingPrice . ' ' . $currency;

        // Collect compared data for header of box
        $headerBox    = '';
        $comparedData = json_decode($priceData['Compared_data'], true);

        if (defined('AJAX_FILE')) {
            $rlSmarty->assign('listing_type', $listingType);
        }

        if ($rlListings->fieldsList) {
            $fields = $rlListings->fieldsList;
        } else {
            $rlListings->getListingDetails(
                $listingData['Category_ID'],
                $listingData,
                $listingType
            );

            $fields = $rlListings->fieldsList;
        }

        array_unshift($fields, ['Key' => 'Category_ID', 'Type' => 'select']);

        foreach ($fields as $fieldData) {
            if (!isset($comparedData[$fieldData['Key']])) {
                continue;
            }

            $value = '';
            if ($fieldData['Type'] === 'checkbox') {
                $checkboxValues = $comparedData[$fieldData['Key']];

                foreach ($checkboxValues as $checkboxValue) {
                    $adaptedValue = $rlCommon->adaptValue(
                        $fieldData,
                        $checkboxValue,
                        'listing',
                        $listingData['ID'],
                        true,
                        false,
                        false,
                        false,
                        $listingData['Account_ID'],
                        null,
                        $listingData['Listing_type']
                    );

                    if ($adaptedValue) {
                        $value = $value ? $value . ', ' . $adaptedValue : $adaptedValue;
                    }
                }
            } else {
                $value = $rlCommon->adaptValue(
                    $fieldData,
                    $comparedData[$fieldData['Key']],
                    'listing',
                    $listingData['ID'],
                    true,
                    false,
                    false,
                    false,
                    $listingData['Account_ID'],
                    null,
                    $listingData['Listing_type']
                );
            }

            $value = is_array($value) && isset($value['from']) ? $value['from'] : $value;

            if ($value && $fieldData['Key'] === 'Category_ID') {
                $value = explode(',', $value);
                $value = end($value);
            }

            $headerBox = $headerBox ? $headerBox . ', ' . $value : $value;
        }

        // Prepare fields for hidden search form
        $searchForm = [];

        if (!$config['ap_hide_footer']) {
            $GLOBALS['reefless']->loadClass('Search');

            $_SESSION['apListingIdForHiddenSearch'] = $listingID;

            // Emulate building a base quick search
            $searchFormKey              = $listingData['Listing_type'] . '_quick';
            $searchForm                 = $GLOBALS['rlSearch']->buildSearch($searchFormKey);
            $searchForm['listing_type'] = $listingData['Listing_type'];

            // Emulate selected values in search
            if ($listingType['Submit_method'] === 'post') {
                $_POST = $comparedData;
            } else {
                $_GET  = $comparedData;
            }

            $rlSmarty->assign('form', $searchForm);
            $rlSmarty->assign('form_key', $searchFormKey);
            $rlSmarty->assign_by_ref('form_type_key', $listingData['Listing_type']);
        }

        $rlSmarty->assign(
            'apData',
            [
                'averagePrice'  => $averagePrice,
                'listingPrice'  => $listingPrice,
                'headerBox'     => $headerBox,
                'searchForm'    => $searchForm,
                'countListings' => $priceData['Count_listings'],
                'graphPercent'  => $priceData['Graph_percent']
            ]
        );
    }

    /**
     * Get listing data for graph
     *
     * @param  int   $listingID
     * @return array
     */
    protected function getListingData($listingID)
    {
        if (!$listingID = (int) $listingID) {
            return [];
        }

        static $graphData = [];
        if ($graphData[$listingID]) {
            return $graphData[$listingID];
        }

        $data = $GLOBALS['rlDb']->fetch('*', ['Listing_ID' => $listingID], null, null, 'ap_listing_data', 'row');

        if (!$data) {
            return [];
        }

        // Update data if cache has been expired
        $savedDate   = new DateTime($data['Saved_date']);
        $currentDate = new DateTime();
        $dateDiff    = $currentDate->diff($savedDate);

        if ($dateDiff->d >= (int) $GLOBALS['config']['ap_update_period']) {
            $_SESSION['apUpdateListingData'] = $listingID;
        }

        $graphData[$listingID] = $data;

        return $data;
    }

    /**
     * Get fields of form selected for plugin
     *
     * @param  array $listingData
     * @return array
     */
    protected function getFields($listingData)
    {
        if (!$listingData) {
            return [];
        }

        global $config, $rlListings;

        $fields = [];

        switch ($this->formTable) {
            case 'short':
                $fields = $rlListings->getFormFields(
                    $listingData['Category_ID'],
                    'short_forms',
                    $listingData['Listing_type']
                );
                break;
            case 'similar':
            case 'own':
                $formTable           = $this->formTable === 'own' ? 'ap_form_relations' : 'similar_listings_form';
                $config['tmp_cache'] = $config['cache'];
                $config['cache']     = 0;

                $fields = $rlListings->getFormFields(
                    $listingData['Category_ID'],
                    $formTable,
                    $listingData['Listing_type']
                );

                $config['cache'] = $config['tmp_cache'];
                unset($config['tmp_cache']);
                break;
        }

        if (!isset($fields['Category_ID'])) {
            $fields['Category_ID'] = ['Key' => 'Category_ID', 'Type' => 'select'];
        }

        return $fields;
    }

    /**
     * Remove box from page
     *
     * @return void
     */
    protected function removeBox()
    {
        global $blocks;

        unset($blocks['averagePrice']);
        $GLOBALS['rlCommon']->defineBlocksExist($blocks);
    }

    /**
     * @hook tplFooter
     *
     * @return void
     */
    public function hookTplFooter()
    {
        global $rlSmarty;

        if ($GLOBALS['page_info']['Controller'] !== 'listing_details' || !$_SESSION['apUpdateListingData']) {
            return;
        }

        $rlSmarty->assign('apListingID', $_SESSION['apUpdateListingData']);
        unset($_SESSION['apUpdateListingData']);
        $rlSmarty->display(RL_PLUGINS . 'averagePrice/footer.tpl');
    }

    /**
     * @hook phpSearchBuildSearchTop
     *
     * @return void
     */
    public function hookPhpSearchBuildSearchTop()
    {
        if (!$_REQUEST['ap-search'] && !$_SESSION['apListingIdForHiddenSearch']) {
            return;
        }

        // Disable system cache for adding a missing fields to search form
        $GLOBALS['config']['tmp_cache'] = $GLOBALS['config']['cache'];
        $GLOBALS['config']['cache']     = 0;
    }

    /**
     * @hook phpSearchBuildSearchBottom
     *
     * @param  array &$relations
     * @return void
     */
    public function hookPhpSearchBuildSearchBottom(&$relations)
    {
        if (!$_REQUEST['ap-search'] && !$_SESSION['apListingIdForHiddenSearch']) {
            return;
        }

        global $config, $rlListings;

        $listingID         = intval($_REQUEST['ap-listing-id'] ?: $_SESSION['apListingIdForHiddenSearch']);
        $listingData       = $rlListings->getListing($listingID);
        $fields            = $this->getFields($listingData);
        $baseInfo          = [];
        $relationFieldKeys = [];

        unset($_SESSION['apListingIdForHiddenSearch']);

        // Collect keys of fields in search form
        foreach ($relations as $relation) {
            $relationFieldKeys[] = $relation['Fields'][0]['Key'];

            if (!$baseInfo) {
                unset($relation['Fields']);
                $baseInfo = $relation;
            }
        }

        // Add missing fields to search form
        foreach ($fields as $field) {
            if (!in_array($field['Key'], $relationFieldKeys)) {
                $newField = $baseInfo;

                $sql = "SELECT `ID`, `Key`, `Type`, `Default`, `Values`, `Condition`, ";
                $sql .= "CONCAT('listing_fields+name+', `Key`) AS `pName`, ";
                $sql .= "`Key` = '{$field['Key']}' AS `Order` ";
                $sql .= "FROM `{db_prefix}listing_fields` ";
                $sql .= "WHERE `Key` = '{$field['Key']}' AND `Status` = 'active' ";
                $sql .= "ORDER BY `Order`";
                $fieldData = $GLOBALS['rlDb']->getAll($sql);

                $newField['pName'] = "listing_groups+name+{$newField['Group_key']}";
                $newField['Fields'] = empty($fieldData)
                ? false
                : $GLOBALS['rlCommon']->fieldValuesAdaptation($fieldData, 'listing_fields', $newField['Listing_type']);

                $relations[] = $newField;
            }
        }

        // Revert value of system cache
        $config['cache'] = $config['tmp_cache'];
        unset($config['tmp_cache']);
    }

    /**
     * @hook listingsModifyFieldSearch
     *
     * @param  string $sql
     * @param  array  $data
     * @param  string $type
     * @param  array  &$form
     * @return void
     */
    public function hookListingsModifyFieldSearch($sql, $data, $type, &$form)
    {
        // Add missing fields to search criteria
        if (!$_REQUEST['ap-search']) {
            return;
        }

        $searchFieldKeys = array_keys($form);

        foreach ($GLOBALS['rlSearch']->buildSearch($type . '_quick') as $searchField) {
            $searchField = $searchField['Fields'][0];
            unset($searchField['Values']);

            if (!in_array($searchField['Key'], $searchFieldKeys)) {
                $form[$searchField['Key']] = $searchField;
            }
        }
    }

    /**
     * @hook staticDataRegister
     *
     * @return void
     */
    public function hookStaticDataRegister()
    {
        $GLOBALS['rlStatic']->addBoxFooterCSS(RL_PLUGINS_URL . 'averagePrice/static/style.css', 'averagePrice');
    }

    /**
     * @hook apTplContentBottom
     *
     * @return void
     */
    public function hookApTplContentBottom()
    {
        global $controller;

        // Hide plugin controller from menu
        echo '<script>$(\'#mPlugin_averagePrice\').remove();</script>';

        if (!in_array($controller, ['blocks', 'settings'])) {
            return;
        }

        if ($_GET['action'] == 'edit' && false !== strpos($_GET['block'], 'averagePrice')) {
            echo "<!-- Hide unnecessary options from page for Average Price box -->";
            echo "<script type='text/javascript'>$('#btypes').hide()</script>";
            echo "<script type='text/javascript'>$('#pages_obj,#cats').closest('tr').hide()</script>";
        }

        /**
         * @todo - Remove it when compatible will be 4.7.1 or higher
         *       - Use $systemSelects parameter in hook "apMixConfigItem" only
         */
        if ($controller == 'settings' && version_compare($GLOBALS['config']['rl_version'], '4.7.1') < 0) {
            echo '<script>
            $(function(){
                $(\'[name="post_config[ap_fields_form][value]"] option[value=""]\').remove();
            });</script>';
        }

        // Offer copy fields from Browse form to plugin table
        if ($_SESSION['apCopyFormRelations']) {
            unset($_SESSION['apCopyFormRelations']);

            echo <<<HTML
            <script>
            $(function() {
                rlConfirm("{$GLOBALS['lang']['ap_copy_form_relations']}", 'apCopyFormRelations');
            });

            var apCopyFormRelations = function() {
                $.getJSON(rlConfig['ajax_url'], {item: 'apCopyFormRelations'}, function(response) {
                    if (response.status === 'OK' && response.message) {
                        printMessage('notice', response.message);
                    }
                });
            };
            </script>
HTML;
        }
    }

    /**
     * @hook apAjaxRequest
     *
     * @param  array &$out
     * @param  string $item
     * @return void
     */
    public function hookApAjaxRequest(&$out, $item)
    {
        if ($item !== 'apCopyFormRelations') {
            return;
        }

        $GLOBALS['rlDb']->query('INSERT INTO `{db_prefix}ap_form_relations` SELECT * FROM `{db_prefix}short_forms`');
        $out = ['status' => 'OK', 'message' => $GLOBALS['lang']['ap_fields_copied']];
    }

    /**
     * @hook  apMixConfigItem
     *
     * @param  array $value
     * @param  array $systemSelects - Required configs with "select" type
     * @return void
     */
    public function hookApMixConfigItem(&$value, &$systemSelects = null)
    {
        if ($value['Key'] !== 'ap_fields_form') {
            return;
        }

        // Remove option "Similar Ads form" if plugin doesn't exist
        if (!$GLOBALS['plugins']['similarListings']) {
            foreach ($value['Values'] as $index => $data) {
                if ($data['ID'] === 'similar') {
                    unset($value['Values'][$index]);
                    break;
                }
            }
        }

        // Mark field as "required" to remove "- Select -" option
        $systemSelects[] = 'ap_fields_form';
    }

    /**
     * @hook apTplCategoriesNavBar
     *
     * @return void
     */
    public function hookApTplCategoriesNavBar()
    {
        global $lang, $config;

        if ($config['ap_fields_form'] !== 'own' || $_GET['form'] === 'ap_fields_form' || !isset($_GET['form'])) {
            return;
        }

        $title = str_replace('[category]', $GLOBALS['category_info']['name'], $lang['ap_form_title']);

        echo "<a title=\"{$title}\" href=\"";
        echo RL_URL_HOME . ADMIN;
        echo '/index.php?controller=ap_fields_form&action=build&form=ap_fields_form'
            . '&key=' . $GLOBALS['category_info']['Key'];
        echo '" class="button_bar"><span class="left"></span><span class="center_build">';
        echo $lang['ap_form'] . '</span><span class="right"></span></a>';
    }

    /**
    * @hook apPhpConfigAfterUpdate
    *
    * @return void
    */
    public function hookApPhpConfigAfterUpdate()
    {
        global $dConfig, $config;

        if ($dConfig['ap_fields_form']['value'] === 'own'
            && $dConfig['ap_fields_form']['value'] !== $config['ap_fields_form']
            && !$GLOBALS['rlDb']->getRow('SELECT `ID` FROM `{db_prefix}ap_form_relations`', 'ID')
        ) {
            $_SESSION['apCopyFormRelations'] = true;
        }
    }

    /**
     * @hook  phpListingsAjaxDeleteListing
     *
     * @param  array $listingInfo
     * @return void
     */
    public function hookPhpListingsAjaxDeleteListing($listingInfo)
    {
        if (!$listingInfo['ID']) {
            return;
        }

        $GLOBALS['rlDb']->delete(['Listing_ID' => $listingInfo['ID']], 'ap_listing_data');
    }

    /**
     * Installation process
     *
     * @return void
     */
    public function install()
    {
        global $rlDb, $config;

        $rlDb->query(
            "UPDATE `{db_prefix}blocks`
             SET `Sticky` = '0',
                 `Cat_sticky` = '" . (version_compare($config['rl_version'], '4.8.1', '>=') ? 0 : 1) . "',
                 `Page_ID` = (SELECT `ID` FROM `{db_prefix}pages` WHERE `Key` = 'view_details'),
                 `Position` = 1
             WHERE `Key` = 'averagePrice'"
        );

        $rlDb->createTable(
            'ap_form_relations',
            "`ID` INT(11) NOT NULL AUTO_INCREMENT,
            `Position` INT(3) NOT NULL DEFAULT 0,
            `Category_ID` INT(11) NOT NULL DEFAULT 0,
            `Field_ID` INT(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`ID`)"
        );

        $rlDb->createTable(
            'ap_listing_data',
            "`ID` INT(11) NOT NULL AUTO_INCREMENT,
            `Listing_ID` INT(11) NOT NULL DEFAULT 0,
            `Listing_Type_Key` VARCHAR(25) NOT NULL DEFAULT '',
            `Saved_date` DATE NOT NULL,
            `Average_price` VARCHAR(100) NOT NULL DEFAULT '',
            `Listing_price` VARCHAR(100) NOT NULL DEFAULT '',
            `Compared_data` MEDIUMTEXT NOT NULL,
            `Count_listings` INT(11) NOT NULL DEFAULT '0',
            `Graph_percent` INT(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`ID`),
            Key `Listing_ID` (`Listing_ID`)"
        );

        // Hide plugin controller from menu
        $GLOBALS['rlPlugin']->controller = '';
    }

    /**
     * Uninstallation process
     *
     * @return void
     */
    public function uninstall()
    {
        $GLOBALS['rlDb']->dropTables(['ap_form_relations', 'ap_listing_data']);
    }

    /**
     * Update process of the plugin (copy from core)
     * @since 1.0.1
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
     * Update to 1.0.1 version
     */
    public function update101()
    {
        global $rlDb;

        if (version_compare($GLOBALS['config']['rl_version'], '4.8.1', '>=')) {
            $rlDb->query("UPDATE `{db_prefix}blocks` SET `Cat_sticky` = '0' WHERE `Key` = 'averagePrice'");
        }

        $rlDb->query("CREATE INDEX `Listing_ID` ON `{db_prefix}ap_listing_data` (`Listing_ID`);");
    }

    /**
     * Update to 1.0.2 version
     */
    public function update102()
    {
        $GLOBALS['rlDb']->query(
            "ALTER TABLE `{db_prefix}ap_listing_data`
             CHANGE `Compared_data` `Compared_data` MEDIUMTEXT NOT NULL"
        );
    }
}
