<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLEXPORTIMPORT.CLASS.PHP
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
 *  Flynax Classifieds Software 2024 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

use Flynax\Component\Filesystem;
use Flynax\Plugins\ExportImport\Handlers\Escort;
use Flynax\Plugins\ExportImport\Handlers\File;
use Flynax\Plugins\ExportImport\Handlers\Jobs;
use Flynax\Plugins\ExportImport\Handlers\ListingPackages;
use Flynax\Plugins\ExportImport\Helpers;
use Flynax\Component\ListingImageUploader;
use Flynax\Utils\Util;
use Flynax\Utils\Valid;
use Flynax\Utils\ListingMedia;
use Flynax\Abstracts\AbstractPlugin;
use Flynax\Interfaces\PluginInterface;

require_once __DIR__ . '/vendor/autoload.php';

class rlExportImport extends AbstractPlugin implements PluginInterface
{
    /**
     * @var loop category id
     **/
    var $loop_category_id = false;

    /**
    * @var loop account id
    **/
    var $loop_account_id = false;

    /**
     * Listing field keys related to multifield formats
     *
     * @since 3.9.0
     * @var array
     */
    public $multiFormatKeys = [];

    /**
     * @since 3.6.0
     *
     * @var array - Pages of the Export Import plugin
     */
    protected $pluginPages = array('xls_export_import');

    /**
    * Fetch categories (as select option) by listing type key.
    *
    * @package xajax
    * @param   string $type      - Listing type key.
    * @param   string $el        - DOM element ID
    * @param   bool   $user_mode - Is request from front-end
    * @return  array  $out       - Array with built DOM
    **/
    function ajaxFetchOptions( $type = "", $el = 'import_category_id', $user_mode = false )
    {
        global $rlSmarty, $lang;

        if (!$lang) {
            $lang = $GLOBALS['rlLang']->getLangBySide('frontEnd', RL_LANG_CODE);
        }

        $categories = $GLOBALS['rlCategories']->getCategories(0, $type);

        /* populate categories dropdown */
        $options = sprintf("<option value=''>%s</option>", $lang['eil_no_categories_available']);
        if ($categories) {
            $options = sprintf("<option value=''>%s</option>", $lang['select']);
            foreach ($categories as $category) {
                $margin = $category['margin'] ? 'margin-left: ' . $category['margin'] . 'px;' : '';
                $highlight = $category['Level'] == 0 ? 'class="highlight_opt highlight_option"' : '';
                if (defined('REALM') && REALM == 'admin') {
                    $count = $el == 'export_category_id' ? " ({$category['Count']})" : '';
                }

                $options .= sprintf(
                    "<option %s style='%s' value='%s'>%s %s</option>",
                    $highlight,
                    $margin,
                    $category['ID'],
                    $lang[$category['pName']],
                    $count
                );
            }
        }

        $out['status'] = 'ok';
        $out['html']['category'] = $options;

        /* build search form */
        if ($el == 'export_category_id') {
            $GLOBALS['reefless']->loadClass('Search');
            $fields = $GLOBALS['rlSearch']->buildSearch($type . '_quick', $type);
            $rlSmarty->assign_by_ref('fields', $fields);
            $rlSmarty->assign_by_ref('lang', $lang);
            $rlSmarty->assign('multi_format_keys', []);
            $tpl = RL_PLUGINS . 'export_import/' . ($user_mode ? '' : 'admin/') . 'search.tpl';
            $form_html = $rlSmarty->fetch($tpl, null, null, false);
            $out['html']['form'] = $form_html;
        }
        $out['js'] = !$user_mode ? '' : "flynaxTpl.customInput();";

        if (Helpers::isMultiFieldInstalled()) {
            $multi_formats = Helpers::getMultiFormats();

            if ($multi_formats) {
                $js = '';
                foreach ($multi_formats as $multi_format) {
                    $sql = "SELECT * FROM `" . RL_DBPREFIX . "listing_fields` ";
                    $sql .= "WHERE `Condition` = '{$multi_format['Key']}'";
                    $related_fields = $GLOBALS['rlDb']->getAll($sql);
                    foreach ($related_fields as $k => $field) {
                        $js .= <<< JAVASCRIPT
                        if (mfFields.indexOf('{$field['Key']}') < 0) {
                            mfFields.push('{$field['Key']}');
                        }
JAVASCRIPT;
                        $m_fields[] = $field;
                    }
                }

                $js .= <<< JAVASCRIPT
                var mfHandler = new mfHandlerClass();
                mfHandler.init('f', mfFields, []);
JAVASCRIPT;
                // if plugin version is less than 2.0.0
                if (!method_exists($GLOBALS['rlMultiField'], 'getPostPrefixByPage')) {
                    $rlSmarty->assign('fields', $m_fields);
                    $tpl2 = RL_PLUGINS . 'multiField' . RL_DS . 'mf_reg_js.tpl';
                    $js = $rlSmarty->fetch($tpl2, null, null, false);
                }

                $out['js'] .= $js;
            }

            $rlSmarty->assign('multi_formats', $multi_formats);
        }

        return $out;
    }

    /**
     * import listings
     *
     * @param array  $data - listings data from file
     * @param array  $available_rows - available listing rows indexes in $data array
     * @param array  $columns - field columns availability
     * @param array  $fields - fields keys assigned by columns
     * @param int    $start - start index in $available_rows array
     * @param int    $limit - limit of listings per single import
     * @param string $listing_type - listing type key
     * @param int    $category_id - category id
     * @param int    $account_id - owner account id
     * @param int    $plan_id - plan id
     * @param bool   $paid - is imported listings should be paid by default
     * @param string $status - default listing status
     * @param bool   $user_mode - request from front-end
     * @return bool
     */
    function import($data = null, $available_rows = null, $columns = null, $fields = null, $start = null, $limit = 5, $listing_type =null, $category_id =null, $account_id =null, $plan_id =null, $paid =null, $status =null, $user_mode = false)
    {
        global $config, $rlCategories, $plan_info, $rlListings, $rlDb, $reefless, $rlCache, $plugins;

        if (!$data || !$available_rows || !$limit || !$listing_type || !$category_id || !$account_id) {
            return false;
        }

        if (Helpers::isMultiFieldInstalled()) {
            $multi_formats = Helpers::getMultiFormats();
            $this->multiFormatKeys = Helpers::getMultifieldRelatedFields();
        }

        /* check for "reference number" plugin */
        if ($reference_plugin = $rlDb->getOne('ID', "`Key` = 'ref' AND `Status` = 'active'", 'plugins')) {
            $reefless->loadClass('Ref', null, 'ref');
        }

        /* get requested fields info */
        $add_sql_fields = 'AND (';
        foreach ($fields as $field_index => $field)
        {
            if ( $field && $columns[$field_index] )
            {
                $add_sql_fields .= "`Key` = '{$field}' OR ";
                $available_fields[$field] = $field_index;
            }
        }
        $add_sql_fields = rtrim($add_sql_fields, ' OR ');
        $add_sql_fields .= ')';
        $fields_info_tmp = $rlDb->fetch(
            array('Key', 'Type', 'Default', 'Values', 'Condition', 'Map'),
            null,
            "WHERE `Status` <> 'trash' " . $add_sql_fields,
            null,
            'listing_fields'
        );

        foreach ($fields_info_tmp as $field)
        {
            $fields_info[$field['Key']] = $field;
        }
        unset($fields_info_tmp);

        $account_info = $rlDb->fetch('*', array('ID' => $account_id), null, 1, 'accounts', 'row');

        if ($config['membership_module']) {
            $paid = $account_info['Pay_date'];
            $plan_info = $rlDb->fetch('*', array('ID' => $plan_id), null, 1, 'membership_plans', 'row');
        } else {
            $plan_info = $rlDb->fetch(
                array('Featured', 'Image', 'Image_unlim', 'Price', 'Limit', 'Type', 'Video', 'Video_unlim'),
                array('ID' => $plan_id),
                null, 1, 'listing_plans', 'row'
            );
        }

        $isAdmin = defined('REALM') && REALM === 'admin';

        $listing_number = 1;
        $updateCategoriesCache = false; // @since 3.10.0

        // Reset ids
        if (!$start) {
            $_SESSION['iel_data_ids'] = [];
        }

        /* collect import listings array */
        for ($index = $start; $index < $start + $limit; $index++)
        {
            $row_data = $data[$available_rows[$index]];
            $coming_plan = $plan_id;
            $coming_status = $status;

            if ( !$row_data )
                continue;

            $listing_mode = 'standard';

            if ($plan_info['Featured_listing'] && $config['membership_module']) {
                $listing_mode = 'featured';
            }

            /* collect data by form fields */
            $category_levels = $this->getAllCategories($fields);
            $html_fields = array();
            $insert_fields = array();

            foreach ($fields as $field_index => $field)
            {
                $field_info = $this->getListingFieldInfo($field);

                if ($GLOBALS['plugins']['multiField'] && $multi_formats[$field_info['Condition']]) {
                    continue;
                }

                if ($field_info && $field_info['Type'] == 'textarea' && $field_info['Condition'] == 'html') {
                    $html_fields[] = $field;
                }

                if ($field && $columns[$field_index]) {
                    if ($field == 'Category_level_1') {
                        $this->findCategory($category_levels, $row_data, $listing_type);
                    } else {
                        $value = $this->adaptValue($row_data[$field_index], $field, $fields_info);

                        if ($field_info && $field_info['Type'] == 'textarea') {
                            $find = array('\\n', '\\n\\r');
                            $value = str_replace($find, '<br/>', $value);
                        }

                        if (in_array($field_info['Type'], array('textarea', 'text'))) {
                            $value = str_replace(array('\"', "\\'"), array('"', '\''), $value);

                            if ($field_info['Type'] == 'textarea') {
                                $fieldMaxChars = $field_info['Values'];

                                if (strlen($value) > $fieldMaxChars) {
                                    $value = substr($value, 0, $fieldMaxChars);
                                }
                            }
                        }

                        if ($field_info['Type'] == 'file') {
                            $value = File::importToListing($row_data[$field_index]);
                        }

                        if (Escort::isEscortInstallation() && $field == 'availability') {
                            Escort::importAvailability($row_data[$field_index], $insert_fields);
                        }

                        if (!empty($value)) {
                            $insert_fields[$field] = preg_replace('/[\r]+/', '<br />', $value);
                        }
                    }
                }
            }

            $set_category_id = $this->loop_category_id ?: $category_id;
            $this->loop_category_id = false;
            $set_account_id = $this->loop_account_id ?: $account_id;
            $this->loop_account_id = false;

            //membership
            $restricted_account = false;
            $should_make_featured = false;

            if ($config['membership_module']) {
                $mp_account_info = $rlDb->fetch('*', array('ID' => $set_account_id), null, 1, 'accounts', 'row');
                $restricted_account = true;

                // change plan_id and plan_type
                $plan_id = $mp_account_info['Plan_ID'];
                $plan_info = $mp_plan_info = $GLOBALS['rlMembershipPlan']->getPlan($plan_id);

                $plan_type = 'account';
                $is_exceeded = $this->isLimitExceeded($account_id, $mp_plan_info);

                //check does user selected some membership plan
                if ($user_mode) {
                    if (!$config['allow_listing_plans']) {
                        if (!$mp_account_info['Plan_ID']) {
                            continue;
                        }
                    } else {
                        if ($is_exceeded) {
                            $plan_id = $coming_plan;
                            $fetch = array('Featured', 'Image', 'Image_unlim', 'Price', 'Limit');
                            $plan_info = $rlDb->fetch($fetch, array('ID' => $plan_id), null, 1, 'listing_plans', 'row');
                            $restricted_account = false;
                            $plan_type = 'listing';
                        }
                    }
                }

                if ($restricted_account) {
                    $package_sql = "SELECT * FROM `" . RL_DBPREFIX . "listing_packages` ";
                    $package_sql .= "WHERE `Account_ID` = {$mp_account_info['ID']} AND `Plan_ID` = {$plan_id}";
                    $package_exist = $rlDb->getRow($package_sql);

                    if (!$package_exist) {
                        $this->addMembershipPlanUsing($mp_plan_info, $mp_account_info['ID']);
                    }

                    // skip importing if limit of the membership plan is exceeded
                    if (!$config['allow_listing_plans'] && $is_exceeded) {
                        continue;
                    }
                }
            }

            $user_status = $config['listing_auto_approval'] ? $status : 'pending';

            // listing package integration
            if (!$config['membership_module']) {
                if ($plan_info['Type'] == 'package' && $user_mode) {
                    try {
                        $plan_info['Featured'] = false;
                        $listingPackageManager = new ListingPackages($account_id, $plan_id);

                        $listingPackageManager->checkUsageRowExisting();

                        if ($listingPackageManager->isLimitExceeded()) {
                            continue;
                        }

                        $paid = true;
                        $reducedType = $listingPackageManager->reduceListingUsage($account_id, $plan_id);

                        if ($reducedType['featured']) {
                            $should_make_featured = true;
                            $featuredPlanID = $plan_id;
                        }

                    } catch (\Exception $e) {
                        $GLOBALS['rlDebug']->logger($e->getMessage());
                    }
                }

                if ($plan_info['Type'] == 'listing') {
                    $statusDependPackage = $plan_info['Price'] > 0 ? 'expired' : 'active';
                    $paid = $plan_info['Price'] ? $paid : true;

                    $status = $statusDependPackage;
                    if ($user_mode) {
                        $user_status = $statusDependPackage;
                    }
                }
            }

            /* collect data by system fields */
            $import = array(
                'Category_ID' => $set_category_id,
                'Account_ID' => $set_account_id,
                'Plan_ID' => $plan_id,
                'Pay_date' => $paid ? 'NOW()' : '',
                'Date' => 'NOW()',
                'Status' => $user_mode ? $user_status : $status,
            );

            /* Custom modification by John / YUE-846035 */
            if ($_SESSION['iel_data']['post']['assign_cart']) {
                $import['shc_mode'] = 'fixed';
                $import['shc_quantity'] = '1000';
                $import['shc_start_time'] = 'NOW()';
            }

            if ($restricted_account && $mp_plan_info['Advanced_mode']) {
                $should_make_featured = $this->makeFeatured($account_id, $mp_plan_info['Featured_listings']);
                $featuredPlanID = $mp_plan_info['ID'];
            }

            if ($should_make_featured) {
                $import['Featured_date'] = "NOW()";
                $import['Featured_ID'] = $featuredPlanID;
                $import['Plan_ID'] = $featuredPlanID;
            }

            if ($rlDb->getRow("SHOW FIELDS FROM `{db_prefix}listings` WHERE `Field` = 'cl_direct'")) {
                $import['cl_direct'] = '1';
            }

            if ($plan_info['Featured']) {
                $import['Featured_ID'] = $plan_id;
                $import['Featured_date'] = $paid ? 'NOW()' : '';
            }

            if ($user_mode && !$config['membership_module']) {
                $this->planData($available_rows[$index], $import, $plan_id, $plan_info);
            }

            $import = array_merge($import, $insert_fields);

            $this->defineMultiFieldValues($row_data, $fields, $import);

            if (!empty($row_data[$available_fields['Loc_latitude']]) && !empty($row_data[$available_fields['Loc_longitude']])) {
                $import['Loc_latitude'] = $row_data[$available_fields['Loc_latitude']];
                $import['Loc_longitude'] = $row_data[$available_fields['Loc_longitude']];
            } else {
                foreach ($fields as $field) {
                    if ($fields_info[$field]['Map'] && $import[$field]) {
                        $location[] = $GLOBALS['rlCommon']->adaptValue($fields_info[$field], $import[$field]);
                    }
                }

                if ($location) {
                    $reefless->time_limit = 30;
                    $reefless->geocodeLocation(implode(', ', $location), $import);

                    unset($location);
                }
            }

            /* beforeImport hook */
            $plugin = 'export_import';
            $action = 'insert';
            if (method_exists($rlListings, 'beforeImport')) {
                $rlListings->beforeImport($import, $plugin, $action);
            } else {
                // if method not exists (ver. < 4.6.1) try to load hook directly
                $GLOBALS['rlHook']->load('beforeImport', $import, $plugin, $action);
            }
            /* beforeImport hook end */

            /* insert new listing */
            if ($restricted_account) {
                $import['Plan_type'] = $plan_type;
            }

            $rlDb->insertOne($import, 'listings', $html_fields);
            $imported_id = $rlDb->insertID();

            /* Custom modification by John / YUE-846035 */
            if ($_SESSION['iel_data']['post']['assign_cart']) {
                $options = array(
                    'Listing_ID' => (int) $imported_id,
                    'Start_price' => '',
                    'Reserved_price' => '',
                    'Bid_step' => '',
                    'Weight' => '',
                    'Shipping_options' => '',
                    'Package_type' => '-1',
                    'Dimensions' => 'a:3:{s:6:"length";s:0:"";s:5:"width";s:0:"";s:6:"height";s:0:"";}',
                    'Handling_time' => '',
                    'Shipping_price_type' => 'free',
                    'Shipping_price' => '',
                    'Shipping_fixed_prices' => '',
                    'Use_system_shipping_config' => '0',
                    'Commission' => '0',
                    'Shipping_discount' => '0',
                    'Shipping_discount_at' => '0',
                    'Digital' => '0',
                    'Quantity_unlim' => '',
                    'Digital_product' => '',
                    'Quantity_real' => 1000,
                    'Shipping_method_fixed' => ''
                );
                $rlDb->insertOne($options, 'shc_listing_options');
            }

            /* after end hook */
            if (method_exists($rlListings, 'afterImport')) {
                $rlListings->afterImport($import, $imported_id, $plugin, $action);
            } else {
                // if method not exists (ver. < 4.6.1) try to load hook directly
                $GLOBALS['rlHook']->load('afterImport', $import, $imported_id, $plugin, $action);
            }
            /* after end hook end */

            /* update membership plans of the user */
            if ($restricted_account) {
                $this->updateMembershipUsing($account_info);
            }

            /* set reference ID for the listing */
            if ($reference_plugin) {
                $ref = $GLOBALS['rlRef']->generate($imported_id, $config['ref_tpl']);
                $ref_update_sql = "UPDATE `{db_prefix}listings` SET `ref_number` = '{$ref}' WHERE `ID` = '{$imported_id}'";
                $rlDb->query($ref_update_sql);
            }

            // Queue a listing for later posting
            if ($plugins['autoPoster']
                && (($config['ap_xls_frontend'] && !$isAdmin) || ($config['ap_xls_backend'] && $isAdmin))
            ) {
                $reefless->loadClass('AutoPoster', null, 'autoPoster');

                if ($GLOBALS['rlAutoPoster'] && method_exists($GLOBALS['rlAutoPoster'], 'addListingInQueue')) {
                    $GLOBALS['rlAutoPoster']->addListingInQueue($imported_id);
                }
            }

            /* update categories count information */
            if ($status == 'active' && $paid) {
                // Prevent cache update of categories during import process
                if ($config['cache']) {
                    $updateCategoriesCache = true;
                    $config['cache'] = false;
                }

                $rlCategories->listingsIncrease($set_category_id, $listing_type);
                $rlCategories->accountListingsIncrease($import['Account_ID']);

                if ($updateCategoriesCache) {
                    $config['cache'] = '1';
                }
            }

            /* pictures handler */
            if (array_key_exists('Main_photo_url', $available_fields)) {
                if (Jobs::isBelongsToJobListingType($import['Category_ID'])) {
                    if (array_key_exists('Main_photo_url', $available_fields)) {
                        $mainPhotoUrl = $row_data[$available_fields['Main_photo_url']];

                        $jobManager = new Jobs();
                        $jobManager->import($imported_id, $set_account_id, $mainPhotoUrl);
                    }
                } else {
                    $this->uploadPictures(
                        'url',
                        $imported_id,
                        $row_data[$available_fields['Main_photo_url']],
                        $plan_info
                    );
                }
            }
            if (array_key_exists('Main_photo_zip', $available_fields)) {
                $this->uploadPictures('zip', $imported_id, $row_data[$available_fields['Main_photo_zip']], $plan_info);
            }
            if (array_key_exists('Youtube_video', $available_fields)) {
                $videos = explode(',', $row_data[$available_fields['Youtube_video']]);

                foreach ($videos as $video) {
                    ListingMedia::addYouTube($imported_id, $video, $account_info, $plan_info);
                }
            }

            /* escort handlers */
            if (Escort::isEscortInstallation()) {
                if (array_key_exists('escort_rates', $available_fields)) {
                    Escort::importEscortRates($imported_id, $row_data[$available_fields['escort_rates']]);
                }

                if (array_key_exists('escort_tours', $available_fields)) {
                    Escort::importEscortTours($imported_id, $row_data[$available_fields['escort_tours']]);
                }
            }

            if ($config['membership_module']) {
                $this->updatePlanUsing($account_info, $listing_mode);
            }

            $_SESSION['iel_data_ids'][] = $imported_id;

            unset($insert_fields, $row_data, $imported_id, $import, $update);
            unset($_SESSION['iel_data']['post']['rows'][$available_rows[$index]]);
            $plan_id = $coming_plan;
            $status = $coming_status;
        }

        if ($user_mode && !$config['membership_module']) {
            $this->updatePlans($account_id);
        }

        // Refresh category cache when last listing is imported
        if ($updateCategoriesCache && (($start + $limit) >= count($available_rows))) {
            $rlCache->updateCategories();

            if (version_compare($config['rl_version'], '4.8.2', '>=')) {
                $rlCache->updateStatistics($listing_type);
            } else {
                $rlCache->updateListingStatistics($listing_type);
            }
        }

        return true;
    }

    /**
    * update listing data related selected plan
    *
    * @param int $index - current row index
    * @param array $import - referent to import array to modify necessary data in
    * @param int $plan_id - default plan ID
    * @param array $plan_info - default plan info
    *
    **/
    function planData( $index = false, &$import = null, $plan_id = false, $plan_info = false )
    {
        global $config;

        $status = $import['Status'];
        $plan_id = $import['Plan_ID'];
        $pay_date = $import['Pay_date'];
        $featured_id = $import['Featured_ID'];
        $featured_date = $import['Featured_date'];

        if ( $_SESSION['iel_data']['post']['plan'][$index] || $_SESSION['iel_data']['user_plans'][$plan_id] ) {
            $user_plan_id = $_SESSION['iel_data']['post']['plan'][$index] ? $_SESSION['iel_data']['post']['plan'][$index] : $plan_id;
            $user_plan = $_SESSION['iel_data']['user_plans'][$user_plan_id];

            if ( $_SESSION['iel_data']['user_plans'][$user_plan_id]['Listings_remains'] > 0 ) {
                $plan_id = $user_plan['ID'];
                $pay_date = 'NOW()';

                $type = ucfirst($_SESSION['iel_data']['post']['type'][$index]);

                if ( ($user_plan['Featured'] && !$user_plan['Advanced_mode']) || ($user_plan['Advanced_mode'] && $type == 'Featured' && $user_plan[$type.'_remains'] > 0) ) {
                    $featured_id = $user_plan['ID'];
                    $featured_date = 'NOW()';
                }

                /* decrease counters */
                $_SESSION['iel_data']['user_plans'][$user_plan_id]['Listings_remains'] -= 1;

                if ( $user_plan['Advanced_mode'] && $_SESSION['iel_data']['user_plans'][$user_plan_id][$type.'_remains'] > 0 ) {
                    $_SESSION['iel_data']['user_plans'][$user_plan_id][$type.'_remains'] -= 1;
                }
            }
        }
        else {
            if ( !$plan_info['Price'] && !$plan_info['Limit'] ) {
                $pay_date = 'NOW()';

                if ( $plan_info['Featured'] ) {
                    $featured_id = $plan_id;
                    $featured_date = 'NOW()';
                }
            }
        }

        $import['Status'] = $config['listing_auto_approval'] ? $status : 'pending';
        $import['Plan_ID'] = $plan_id;
        $import['Pay_date'] = $pay_date;
        $import['Featured_ID'] = $featured_id;
        $import['Featured_date'] = $featured_date;
    }

    /**
    * update used plans options
    *
    * @param int $account_id - account ID
    *
    **/
    function updatePlans( $account_id = false )
    {
        global $rlDb;

        if (!$account_id || empty($_SESSION['iel_data']['user_plans'])) {
            return;
        }

        foreach ($_SESSION['iel_data']['user_plans'] as $plan) {
            if ( $plan['Package_ID'] || $plan['Limit'] > 0 ) {
                /* update */
                if ( $plan['Package_ID'] ) {
                    $update = array(
                        'fields' => array(
                            'Listings_remains' => $plan['Listings_remains'],
                            'Standard_remains' => $plan['Standard_remains'],
                            'Featured_remains' => $plan['Featured_remains'],
                        ),
                        'where' => array(
                            'ID' => $plan['Package_ID']
                        )
                    );

                    $rlDb->updateOne($update, 'listing_packages');
                }
                /* insert */
                else {
                    $insert = array(
                        'Account_ID' => $account_id,
                        'Plan_ID' => $plan['ID'],
                        'Listings_remains' => $plan['Listings_remains'],
                        'Standard_remains' => $plan['Standard_remains'],
                        'Featured_remains' => $plan['Featured_remains'],
                        'Type' => $plan['Package'] ?: 'limited',
                        'Date' => 'NOW()',
                        'IP' => Util::getClientIP(),
                    );

                    $rlDb->insertOne($insert, 'listing_packages');

                    $_SESSION['iel_data']['user_plans'][$plan['ID']]['Package_ID'] = $rlDb->insertID();
                }
            }
        }
    }

    /**
    * adapt category/subcategory value
    *
    * @param array $row - row data (all fields)
    * @param int $category_index - category field index
    * @param int $subcategory_index - subcategory field index (if exists)
    *
    **/
    function handleCategory( &$row, $category_index = false, $subcategory_index = false )
    {
        global $rlDb;

        $value = $row[$category_index];

        if (is_numeric($value)) {
            if ($category_id = $rlDb->getOne('ID', "`ID` = '{$value}' AND `Status` = 'active'", 'categories')) {
                $this->loop_category_id = $category_id;
            }
        } elseif ($value) {
            /* category + subcategory way */
            if ($row[$subcategory_index] && $subcategory_index) {
                $subcategory_value = $row[$subcategory_index];

                $compare_sign = strlen($value) > 5 ? '?' : '';
                $compare_sign_sub = strlen($subcategory_value) > 5 ? '?' : '';
                $sql = "SELECT `T1`.`ID` FROM `". RL_DBPREFIX ."categories` AS `T1` ";
                $sql .= "LEFT JOIN `". RL_DBPREFIX ."lang_keys` AS `T2` ON CONCAT('categories+name+', `T1`.`Key`) = `T2`.`Key` ";
                $sql .= "LEFT JOIN `". RL_DBPREFIX ."categories` AS `T3` ON `T3`.`ID` = `T1`.`Parent_ID` ";
                $sql .= "LEFT JOIN `". RL_DBPREFIX ."lang_keys` AS `T4` ON CONCAT('categories+name+', `T3`.`Key`) = `T4`.`Key` ";
                $sql .= "WHERE ";
                $sql .= "`T4`.`Value` RLIKE '^{$value}{$compare_sign}' AND `T4`.`Key` LIKE 'categories+name+%' AND ";
                $sql .= "`T2`.`Value` RLIKE '^{$subcategory_value}{$compare_sign_sub}' AND `T2`.`Key` LIKE 'categories+name+%' ";
                $sql .= "AND `T1`.`Status` <> 'trash' ";
                $sql .= "LIMIT 1";

                if ($category = $rlDb->getRow($sql)) {
                    $this->loop_category_id = $category['ID'];
                    return;
                }
            }

            /* category way */
            $compare_sign = strlen($value) > 5 ? '?' : '';
            $sql = "SELECT `Key` FROM `". RL_DBPREFIX ."lang_keys` ";
            $sql .= "WHERE `Value` RLIKE '^{$value}{$compare_sign}' AND `Key` LIKE 'categories+name+%' AND `Status` <> 'trash' LIMIT 1";

            if ($phrase_key = $rlDb->getRow($sql)) {
                $phrase_key_exp = array_reverse(explode('+', $phrase_key['Key']));

                if ($category_id = $rlDb->getOne('ID', "`Key` = '{$phrase_key_exp[0]}' AND `Status` = 'active'", 'categories')) {
                    $this->loop_category_id = $category_id;
                }
            }
        }
    }

    /**
     * Adapt Listing value
     *
     * @param string $value - field value
     * @param string $key   - field key
     * @param array  $info  - field information
     * @return bool|mixed
     */
    function adaptValue($value = '', $key = '', $info = [])
    {
        global $rlValid, $rlDb;

        if (!$key) {
            return $value;
        }

        $value = $rlValid -> xSql(trim($value));

        switch ($key){
            case 'Account_ID':
                if (is_numeric($value)) {
                    if ($account_id = $rlDb->getOne('ID', "`ID` = '{$value}' AND `Status` = 'active'", 'accounts')) {
                        $this->loop_account_id = $account_id;
                    }
                } elseif ($value) {
                    if ($account_id = $rlDb->getOne('ID', "`Username` = '{$value}' AND `Status` = 'active'", 'accounts')) {
                        $this->loop_account_id = $account_id;
                    }
                }
                break;

            default:
                switch ($info[$key]['Type']){
                    case 'text':
                    case 'textarea':
                    case 'number':
                    case 'date':

                        if ( $value != '' )
                        {
                            $out = $value;

                        }
                        elseif ( $info[$key]['Default'] )
                        {
                            $out = $info[$key]['Default'];
                        }
                        break;

                    case 'bool':
                        if ( is_numeric($value) )
                        {
                            $out = $value ? 1 : 0;
                        }
                        elseif ( $value == '' && $info[$key]['Default'] )
                        {
                            $out = $info[$key]['Default'];
                        }
                        else
                        {
                            $out = in_array(strtolower($value), array('no', 'n')) ? 0 : 1;
                        }
                        break;

                    case 'phone':
                        $area_length = $info[$key]['Default'] ?: 0;
                        $number_length = $info[$key]['Values'] ?: 0;

                        preg_match('/(\+\s?[0-9]{1,6})?\s*(\(?[0-9]{0,'. $area_length .'}\)?)?\s*([\s\-0-9]{0,'. $number_length .'})?\s*(\/.+)?/', $value, $matches);

                        if ($matches) {
                            /* code */
                            if ($matches[1]) {
                                $out = 'c:'. (int) preg_replace('/\D/', '', $matches[1]) .'|';
                            }

                            /* area */
                            $out .= 'a:'. preg_replace('/\D/', '', $matches[2]) . '|';

                            /* number */
                            $out .= 'n:'. preg_replace('/[\s\-]/', '', $matches[3]);

                            /* extension */
                            if ($matches[4]) {
                                $out .= '|e:'. preg_replace('/\D/', '', $matches[4]);
                            }
                        }

                        break;

                    case 'mixed':
                        preg_match('/([^\s]+)?\s*([\/,%,\w\d\,\.\s]+)?\s*([^\s]+)?/', $value, $matches);

                        if ( $matches[1] )
                        {
                            $out = (float) str_replace(',', '', $matches[1]);
                            if ( $unit = $matches[2] )
                            {
                                $unit = trim($unit);
                                $compare_sign = strlen($unit) > 5 ? '?' : '';

                                if ($info[$key]['Condition']) {
                                    $sql = "SELECT `Key` FROM `". RL_DBPREFIX ."lang_keys` ";
                                    //$sql .= "WHERE `Value` RLIKE '^{$unit}{$compare_sign}' AND `Key` LIKE 'data_formats+name+{$info[$key]['Condition']}_%' AND `Status` <> 'trash' LIMIT 1";
                                    $sql .= "WHERE `Value` = '{$unit}' AND `Key` REGEXP 'data_formats\\\+name\\\+({$info[$key]['Condition']}\_)?' AND `Status` <> 'trash' LIMIT 1";
                                    if ($phrase_key = $rlDb->getRow($sql)) {
                                        $phrase_key_exp = str_replace('data_formats+name+', '', $phrase_key['Key']);
                                        //$phrase_key_exp = str_replace($info[$key]['Condition'] .'_', '', $phrase_key_exp);
                                        $out .= '|'. $phrase_key_exp;
                                    }
                                } else {
                                    $sql = "SELECT `Key` FROM `". RL_DBPREFIX ."lang_keys` ";
                                    //$sql .= "WHERE `Value` RLIKE '^{$unit}{$compare_sign}' AND `Key` REGEXP 'listing_fields\\+name\\+{$key}_[0-9]+' AND `Status` <> 'trash' LIMIT 1";
                                    $sql .= "WHERE `Value` = '{$unit}' AND `Key` REGEXP 'listing_fields\\\+name\\\+{$key}_[0-9]+' AND `Status` <> 'trash' LIMIT 1";
                                    if ($phrase_key = $rlDb->getRow($sql)) {
//                                        $phrase_key_exp = array_reverse(explode('_', $phrase_key['Key']));
//                                        $out .= '|'. $phrase_key_exp[0];
                                        $phrase_key_exp = str_replace('listing_fields+name+', '', $phrase_key['Key']);
                                        $out .= '|'. $phrase_key_exp;
                                    }
                                }
                            }
                        }
                        break;

                    case 'price':
                        if ($price = $this->parsePrice($value)) {
                            $out = $price['price'];

                            if ($price['currency']) {
                                $sql = "
                                    SELECT `Key` FROM `{db_prefix}lang_keys`
                                    WHERE `Value` = '{$price['currency']}' AND `Key` LIKE 'data_formats+name+%' AND `Status` <> 'trash'
                                    LIMIT 1
                                ";

                                if ($phrase_key = $rlDb->getRow($sql)) {
                                    $phrase_key_exp = array_reverse(explode('+', $phrase_key['Key']));
                                    $out .= '|'. $phrase_key_exp[0];
                                }
                            }
                        }
                        break;

                    case 'select':
                    case 'radio':
                    case 'checkbox':
                        if ($value != '') {
                            if ($info[$key]['Condition'] == 'years') {
                                if (Escort::isEscortInstallation() && $key == 'age') {
                                    $out = date('Y') - $value;
                                } else {
                                    $out = $value;
                                }
                            } elseif ( $info[$key]['Condition'] )
                            {
                                if ( $value_exp = explode(',', $value) )
                                {
                                    foreach ($value_exp as $sub_value)
                                    {
                                        $sub_value = preg_replace('/([\"\\\'\{\}\(\)\[\]\*\.\-\^\$\+\?\\\|]+)/', '$1', trim($sub_value));
                                        $compare_sign = strlen($sub_value) > 5 ? '?' : '';

                                        $sql = "SELECT `Key` FROM `". RL_DBPREFIX ."lang_keys` ";
                                        //$sql .= "WHERE `Value` RLIKE '^{$sub_value}{$compare_sign}' AND `Key` RLIKE 'data\_formats\\\+name\\\+(". str_replace('_', '_\\', $info[$key]['Condition']) ."\_)?' AND `Status` <> 'trash' LIMIT 1";
                                        $sql .= "WHERE `Value` = '{$sub_value}' AND `Key` RLIKE 'data_formats\\\+name\\\+({$info[$key]['Condition']}_)?' AND `Status` <> 'trash' LIMIT 1";
                                        if ($phrase_key = $rlDb->getRow($sql)) {
                                            $phrase_key_exp = str_replace('data_formats+name+', '', $phrase_key['Key']);
                                            //$phrase_key_exp = str_replace($info[$key]['Condition'] .'_', '', $phrase_key_exp);
                                            $out .= $phrase_key_exp .',';
                                        }
                                    }
                                }
                            }
                            else
                            {
                                if ( $value_exp = explode(',', $value) )
                                {
                                    foreach ($value_exp as $sub_value)
                                    {
                                        $sub_value = preg_replace('/([\"\\\'\{\}\(\)\[\]\*\.\-\^\$\+\?\\\|]+)/', '$1', trim($sub_value));
                                        $compare_sign = strlen($sub_value) > 5 ? '?' : '';

                                        $sql = "SELECT `Key` FROM `". RL_DBPREFIX ."lang_keys` ";
                                        $sql .= "WHERE `Value` RLIKE '^{$sub_value}{$compare_sign}' AND `Key` REGEXP 'listing_fields\\\+name\\\+{$key}_[0-9]+' AND `Status` <> 'trash' LIMIT 1";
                                        if ($phrase_key = $rlDb->getRow($sql)) {
                                            $phrase_key_exp = array_reverse(explode('_', $phrase_key['Key']));
                                            $out .= $phrase_key_exp[0] .',';
//                                            $phrase_key_exp = str_replace('listing_fields+name+', '', $phrase_key['Key']);
//                                            $out .= $phrase_key_exp .',';
                                        }
                                    }
                                }
                            }
                        }
                        elseif ( $info[$key]['Default'] )
                        {
                            $out = $info[$key]['Default'];
                        }

                        $out = rtrim($out, ',');

                        break;
                }

                break;
        }

        return $out ?: false;
    }

    /**
     * Add multifield values to the data array
     *
     * @since 3.9.0
     *
     * @param  array $listing - Listing data array from the feed
     * @param  array $fields  - Feed field key to Flynax field key mapping
     * @param  array &$data   - Listing data array to be imported
     */
    public function defineMultiFieldValues($listing, $fields, &$data)
    {
        asort($fields);

        $skip_condition = null;
        $prev_key = null;

        foreach ($fields as $field_index => $system_key) {
            if ($system_key && in_array($system_key, $this->multiFormatKeys) && $listing[$field_index]) {
                $head_level = !strpos($system_key, '_level');

                $field_cond = $GLOBALS['rlDb']->getOne('Condition', "`Key` = '{$system_key}'", 'listing_fields');
                $parent_key = $head_level ? $field_cond : $prev_key;

                // Skip next fields with the condition we didn't find value previously for
                if ($skip_condition && $skip_condition == $field_cond) {
                    continue;
                }

                $method_name = Helpers::isNewMultifield() ? 'getMultifieldKeyNew' : 'getMultifieldKeyOld';
                $prev_key = $data[$system_key] = $this->$method_name($listing[$field_index], $parent_key);
            }
        }
    }

    /**
     * Define multifield format key by value and parent key in new multifield data structure
     *
     * @since 3.9.0
     *
     * @param  string $value   - Value from the feed
     * @param  string $prevKey - Parent multifield format key of the related field
     * @return string          - Format key
     */
    private function getMultifieldKeyNew($value, $prevKey)
    {
        global $rlDb;

        $languages = $GLOBALS['languages'] ?: $GLOBALS['rlLang']->getLanguagesList('all');
        $parent_id = $rlDb->getOne('ID', "`Key` = '{$prevKey}'", 'multi_formats');
        $item = null;

        if (!$parent_id) {
            return null;
        }

        Valid::escape($value);

        foreach ($languages as $language) {
            $lang_table = 'multi_formats_lang_' . $language['Code'];
            $sql = "
                SELECT `T1`.`Key` FROM `{db_prefix}multi_formats` AS `T1`
                LEFT JOIN `{db_prefix}{$lang_table}` AS `T2` ON `T2`.`Key` = `T1`.`Key`
                WHERE `T2`.`Value` = '{$value}'
                AND `T1`.`Parent_ID` = {$parent_id}
            ";

            if ($item = $rlDb->getRow($sql, 'Key')) {
                break;
            }
        }

        return $item;
    }

    /**
     * Define multifield format key by value and parent key in old multifield data structure
     *
     * @since 3.9.0
     *
     * @param  string $value   - Value from the feed
     * @param  string $prevKey - Parent multifield format key of the related field
     * @return string          - Format key
     */
    private function getMultifieldKeyOld($value, $prevKey)
    {
        global $rlDb;

        $parent_id = $rlDb->getOne('ID', "`Key` = '{$prevKey}'", 'data_formats');

        if (!$parent_id) {
            return null;
        }

        Valid::escape($value);

        $sql = "
            SELECT `T1`.`Key` FROM `{db_prefix}data_formats` AS `T1`
            LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON `T2`.`Key` = CONCAT('data_formats+name+', `T1`.`Key`)
            WHERE `T2`.`Value` = '{$value}'
            AND `T1`.`Parent_ID` = {$parent_id}
        ";

        return $rlDb->getRow($sql, 'Key');
    }

    /**
     * @deprecated 3.9.0 - Use defineMultiFieldValues() instead
     */
    function getMultifieldValue($value = false, $field_key = false, $row_data = [], $fields = [], $fields_info = []) {}

    /**
     * @deprecated 3.9.0 - Use getMultifieldKeyNew() instead
     */
    public function getMultifieldValueNew($related, $condition) {}

    /**
     * @deprecated 3.9.0 - Use getMultifieldKeyOld() instead
     */
    public function getMultifieldValueOld($related, $condition) {}

    /**
     * Upload listing pictures
     *
     * @param string $mode      - Pictures upload mode, url or zip
     * @param int    $listingID
     * @param string $value     - Value from import form
     * @param array  $planInfo
     *
     * @return bool
     */
    public function uploadPictures($mode = 'url', $listingID = 0, &$value = '', $planInfo = [])
    {
        $listingID = (int) $listingID;

        if (!$listingID || !$value || (!$planInfo['Image'] && !$planInfo['Image_unlim'])) {
            return false;
        }

        $picturesSource = explode(',', $value);
        $pictures       = [];

        switch ($mode) {
            case 'url':
                $pictures = $picturesSource;
                break;
            case 'zip':
                if (!$_SESSION['iel_data']['archive_dir'] || !is_dir($_SESSION['iel_data']['archive_dir'])) {
                    return false;
                }

                foreach ($picturesSource as $pictureName) {
                    $picturePath = $_SESSION['iel_data']['archive_dir'] . RL_DS . trim($pictureName);

                    if (!file_exists($picturePath) || !is_readable($picturePath)) {
                        continue;
                    }

                    $pictures[] = $picturePath;
                }
                break;
        }

        // Erase part of images if plan have limit
        if (is_array($pictures) && $planInfo['Image'] && !$planInfo['Image_unlim']) {
            $pictures = array_slice($pictures, 0, (int) $planInfo['Image']);
        }

        return (bool) (new ListingImageUploader())->load($listingID, $pictures);
    }

    /**
     * @deprecated 3.8.1
     */
    function parseYouTube($id = false, $value = '') {}

    /**
    * get available plans for the given user + free plans
    *
    * @param array $account_id - account info
    *
    * @return array - plans
    *
    **/
    function getUserPlans($account_info = false)
    {
        if (!$account_info) {
            return false;
        }

        $sql = "SELECT `T1`.`ID`, `T1`.`Key`, `T1`.`Type`, `T1`.`Featured`, `T1`.`Advanced_mode`, `T1`.`Limit`, `T1`.`Standard_listings`, ";
        $sql .= "`T1`.`Featured_listings`, `T1`.`Listing_number`, ";
        $sql .= "`T2`.`Type` AS `Package`, `T2`.`Listings_remains`, `T2`.`Standard_remains`, `T2`.`Featured_remains`, `T2`.`ID` AS `Package_ID` ";
        $sql .= "FROM `". RL_DBPREFIX ."listing_plans` AS `T1` ";
        $sql .= "LEFT JOIN `". RL_DBPREFIX ."listing_packages` AS `T2` ON `T1`.`ID` = `T2`.`Plan_ID` AND `T2`.`Account_ID` = {$account_info['ID']} ";
        $sql .= "WHERE `T1`.`Status` = 'active' AND ";
        $sql .= "(FIND_IN_SET('{$account_info['Type']}', `T1`.`Allow_for`) > 0 OR `T1`.`Allow_for` = '') AND";
        $sql .= "(";
        $sql .= " (`T2`.`Type` = 'package' AND ";
        $sql .= "  (`T1`.`Listing_number` = 0 OR ";
        $sql .= "   ( ";
        $sql .="      `T1`.`Listing_number` > 0 AND `T2`.`Listings_remains` > 0 AND `T1`.`Plan_period` > 0 AND ";
        $sql .= "     DATE_ADD(`T2`.`Date`, INTERVAL `T1`.`Plan_period` DAY) <= NOW() ";
        $sql .= "   )";
        $sql .= "  ) ";
        $sql .= " ) OR ";
        $sql .= " ( ";
        $sql .= "  (`T1`.`Type` = 'listing' AND `T1`.`Price` = 0) OR ";
        $sql .= "  (`T2`.`Type` = 'limited' AND `T2`.`Listings_remains` > 0) ";
        $sql .= " ) ";
        $sql .= ") ";
        $sql .= "GROUP BY `T1`.`ID` ";
        $sql .= "ORDER BY `T1`.`Position` ";

        $plans_tmp = $GLOBALS['rlLang']->replaceLangKeys($GLOBALS['rlDb']->getAll($sql), 'listing_plans', array('name'));

        foreach ( $plans_tmp as $plan ) {
            if ( $plan['Limit'] > 0 && !$plan['Package'] ) {
                $plan['Listings_remains'] = $plan['Limit'];
            }

            $plans[$plan['ID']] = $plan;
        }
        unset($plans_tmp);

        return $plans;
    }

    /**
    * update plan using details
    *
    * @param mixed $data - account ID or account details
    * @param string $listing_type - listing type option (standard or featured)
    * @return boolean
    */
    public function updatePlanUsing($data, $listing_type = 'standard')
    {
        global $rlDb, $reefless;

        if (!$data) {
            return false;
        }

        if (!is_array($data)) {
            $account_info = $rlDb->fetch('*', array('ID' => (int)$data), null, null, 'accounts', 'row');
        } else {
            $account_info = $data;
        }

        $reefless->loadClass('Account');
        $reefless->loadClass('MembershipPlan');

        $plan_id = $account_info['Plan_ID'];
        $account_info['plan'] = $GLOBALS['rlMembershipPlan']->getPlan($plan_id, true, $account_info);

        $sql = "SELECT * FROM `".RL_DBPREFIX."listing_packages` WHERE `Plan_ID` = '{$plan_id}' AND `Type` = 'account' AND `Account_ID` = '{$account_info['ID']}' LIMIT 1";
        $plan_using = $rlDb->getRow($sql);

        if ($plan_using['ID']) {
            if ($account_info['plan']['Listing_number'] == 0 || $plan_using['Listings_remains'] > 0)  {
                $plan_using_update = array(
                    'fields' => array(
                        'Account_ID' => $account_info['ID'],
                        'Listings_remains' => $plan_using['Listings_remains'] > 0 ? $plan_using['Listings_remains'] - 1 : 0,
                        'Date' => 'NOW()',
                        'IP' => Util::getClientIP(),
                    ),
                    'where' => array(
                        'ID' => $plan_using['ID']
                    )
                );

                if ($account_info['plan']['Advanced_mode']) {
                    if ($listing_type == 'standard') {
                        $plan_using_update['fields']['Standard_remains'] = $plan_using['Standard_remains'] > 0 ? ($plan_using['Standard_remains'] - 1) : 0;
                    }
                    if ($listing_type == 'featured') {
                        $plan_using_update['fields']['Featured_remains'] = $plan_using['Featured_remains'] > 0 ?  ($plan_using['Featured_remains'] - 1) : 0;
                    }
                }

                $result = $GLOBALS['rlActions']->updateOne($plan_using_update, 'listing_packages');
            } else {
                $result = false;
            }
        } else {
            $plan_using_insert = array(
                'Account_ID' => (int)$account_info['ID'],
                'Plan_ID' => (int)$account_info['Plan_ID'],
                'Listings_remains' => $account_info['plan']['Listing_number'] > 0 ? $account_info['plan']['Listing_number'] - 1 : 0,
                'Standard_remains' => (int)$account_info['plan']['Standard_listings'],
                'Featured_remains' => (int)$account_info['plan']['Featured_listings'],
                'Type' => 'account',
                'Date' => 'NOW()',
                'IP' => Util::getClientIP(),
            );

            if ($account_info['plan']['Advanced_mode'])    {
                if ($listing_type == 'standard') {
                    $plan_using_insert['Standard_remains'] = $plan_using_insert['Standard_remains'] > 0 ? ($plan_using_insert['Standard_remains'] - 1) : 0;
                }
                if ($listing_type == 'featured') {
                    $plan_using_insert['Featured_remains'] = $plan_using_insert['Featured_remains'] > 0 ? ($plan_using_insert['Featured_remains'] - 1) : 0;
                }
            }

            $result = $GLOBALS['rlActions']->insertOne($plan_using_insert, 'listing_packages');
        }
        return $result;
    }

    /**
     * @hook  ajaxRequest
     * @since 3.5.0
     * @throws \Exception
     */
    function hookAjaxRequest(&$out, $request_mode, $request_item, $request_lang)
    {
        global $rlValid, $config, $lang;

        if (!$this->isValidRequest($request_mode)) {
            return false;
        }

        $GLOBALS['reefless']->loadClass('Actions');

        switch ($request_mode) {
            case 'eil_fetchOptions':
                $ts_path = RL_ROOT . 'templates' . RL_DS . $config['template'] . RL_DS . 'settings.tpl.php';
                if (is_readable($ts_path)) {
                    require_once($ts_path);
                }
                $GLOBALS['tpl_settings'] = $tpl_settings;
                $element = $rlValid->xSql($_REQUEST['element']);
                $key = $rlValid->xSql($_REQUEST['key']);
                $out = $this->ajaxFetchOptions($key, $element, 1);
                break;
            case 'eil_ajaxGetPaginationInfo':
                $type = $rlValid->xSql($_REQUEST['type']);
                $out = $this->getPaginationData($type);
                break;
            case 'eil_ajaxImportExportPagination':
                $page = $rlValid->xSql($_REQUEST['page']);
                $type = $rlValid->xSql($_REQUEST['type']);
                $out = $this->ajaxPagination($page, $type);
                break;
            case 'eil_checkListingPackage':
                $userID = (int) $_SESSION['account']['ID'];
                $packageID = (int) $_REQUEST['package_id'];
                unset($_SESSION['eil_import_grid_message']);

                try {
                    $GLOBALS['reefless']->loadClass('Plan');
                    $planInfo = $GLOBALS['rlPlan']->getPlan($packageID);
                    $isFreePlan = !$planInfo['Price'];
                    $eiListingPackages = new ListingPackages($userID, $packageID);
                    $messageToShow = !$isFreePlan ? $lang['eil_you_didnt_bought_listing_package'] : '';
                    $isBought = false;

                    $out = array(
                        'status' => 'OK',
                        'type' => $planInfo['Type'],
                        'plan_id' => $packageID,
                        'is_bought' => $isBought,
                        'can_import' => $planInfo['Type'] == 'listing',
                        'is_free' => $isFreePlan,
                        'message' => $messageToShow,
                    );

                    $packageUsingInfo = $planInfo;
                    $listingRemains = $planInfo['Listing_number'];

                    if ($eiListingPackages->isUsageRowExistInDb()) {
                        $messageToShow = $lang['eil_cant_import_more_listings_to_package'];
                        $packageUsingInfo = $eiListingPackages->getPackageUsageInfo();
                        $listingRemains = (int) $packageUsingInfo['Listings_remains'];
                        $isBought = true;
                    }

                    if ($listingRemains && $planInfo['Type'] == 'package') {
                        $messageToShow = str_replace(
                            '{number}',
                            sprintf('<b>%s</b>', $listingRemains),
                            $lang['eil_you_can_import_only_for_package']
                        );
                    }

                    if ($planInfo['Type'] == 'listing' && $isFreePlan) {
                        $messageToShow = '';
                    }

                    $out['is_bought'] = $isBought;
                    $out['can_import'] = $listingRemains ?: false;
                    $out['message'] = $messageToShow;

                    if ($out['message']) {
                        $_SESSION['eil_import_grid_message'] = $out['message'];
                    }
                } catch (Exception $e) {
                    $out['status'] = 'ERROR';
                }
                break;
        }
    }

    /**
    * @hook apAjaxRequest
    * @since 3.5.0
    */
    function hookApAjaxRequest()
    {
        global $item, $out, $rlValid, $config, $rlSmarty, $reefless;

        if (!$this->isValidRequest($item)) {
            return false;
        }

        /* load smarty library */
        require_once(RL_LIBS . 'smarty/Smarty.class.php');
        $reefless->loadClass('Smarty');

        switch ($item) {
            case 'eil_fetchOptions':
                $rlSmarty->register_function('rlHook', [$GLOBALS['rlHook'], 'load']);
                $rlSmarty->assign('config', $config);
                $rlSmarty->assign('plugins', $GLOBALS['plugins']);
                $element = $rlValid->xSql($_REQUEST['element']);
                $key = $rlValid->xSql($_REQUEST['key']);
                $out = $this->ajaxFetchOptions($key, $element);
                break;
            case 'eil_ajaxImportExportPagination':
                $reefless->loadClass('Actions');
                $page = $rlValid->xSql($_REQUEST['page']);
                $type = $rlValid->xSql($_REQUEST['type']);

                $out = $this->ajaxPagination($page, $type);
                break;
            case 'eil_ajaxGetPaginationInfo':
                $reefless->loadClass('Actions');
                $type = $rlValid->xSql($_REQUEST['type']);
                $out = $this->getPaginationData($type);
                break;
        }
    }

    /**
     * @hook apTplAccountTypesForm
     * @since 3.5.0
     */
    public function hookApTplAccountTypesForm()
    {
        $script = <<<HTML
        <script>
            $(function(){
                var \$option = \$('.option_padding input[value=export_import]');
                var \$label = \$option.parent();
                \$label.contents().last()[0].textContent = ' {$GLOBALS['lang']['eil_option_name']}';
                \$option.removeClass('disabled').removeAttr('disabled');
                \$('input[type=hidden][value=export_import]').remove();
            });
        </script>
HTML;
        echo $script;
    }
    /**
    * recount listings number for each membership plan
    *
    */
    public function ajaxUpdateMembershipPlans($start = 0, $account_id  = false,  $direct = false)
    {
        global $rlDb;

        $start = (int)$start;
        $limit = 100;

        $sql = "SELECT `T1`.`ID`, `T1`.`Featured`, `T1`.`Pay_date`, ";
        $sql .= "`T2`.`Advanced_mode`, `T2`.`Listing_number`, `T2`.`Standard_listings`, `T2`.`Featured_listings`, `T2`.`Plan_period`, ";
        $sql .= "`T3`.`Listings_remains`, `T3`.`Standard_remains`, `T3`.`Featured_remains`, `T3`.`ID` AS `lpID`, ";
        $sql .= "(SELECT COUNT(`TL`.`ID`) FROM `". RL_DBPREFIX ."listings` AS `TL`
                WHERE `TL`.`Account_ID` = `T1`.`ID` AND `TL`.`Status` <> 'pending' AND `TL`.`Status` <> 'trash' AND `TL`.`Plan_type` = 'account' LIMIT 1) AS `ltotal`, ";
        $sql .= "(SELECT COUNT(`TLS`.`ID`) FROM `". RL_DBPREFIX ."listings` AS `TLS`
                WHERE `TLS`.`Account_ID` = `T1`.`ID` AND `TLS`.`Status` <> 'pending' AND `TLS`.`Status` <> 'trash' AND `TLS`.`Plan_type` = 'account'
                AND (`TLS`.`Featured_ID` <= 0 OR `TLS`.`Featured_ID` = '') AND `TLS`.`Featured_date` IS NULL LIMIT 1) AS `standard_total`, ";
        $sql .= "(SELECT COUNT(`TLF`.`ID`) FROM `". RL_DBPREFIX ."listings` AS `TLF`
                WHERE `TLF`.`Plan_ID` = `T1`.`Plan_ID` AND `TLF`.`Status` <> 'pending' AND `TLF`.`Status` <> 'trash' AND `TLF`.`Plan_type` = 'account'
                AND `TLF`.`Featured_ID` > 0 AND `TLF`.`Featured_date` IS NOT NULL LIMIT 1) AS `featured_total` ";
        $sql .= "FROM `". RL_DBPREFIX ."accounts` AS `T1` ";
        $sql .= "LEFT JOIN `". RL_DBPREFIX ."membership_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `". RL_DBPREFIX ."listing_packages` AS `T3` ON `T1`.`Plan_ID` = `T3`.`Plan_ID` AND `T3`.`Account_ID` = `T1`.`ID` AND `T3`.`Type` = 'account' ";
        $sql .= "WHERE `T1`.`Status` <> 'pending' AND `T1`.`Status` <> 'trash' ";
        $sql .= $account_id ? "AND `T1`.`ID` = {$account_id} " : '';
        $sql .= "GROUP BY `T1`.`ID` ";
        $sql .= !$direct ? "LIMIT {$start},{$limit}"  : '';
        $accounts = $rlDb->getAll($sql);

        if ($accounts) {
            foreach ($accounts as $account) {
                $listings_by_plan = $account['Listing_number'];
                $used_listings = $account['ltotal'];
                if (!$account['Advanced_mode']) {
                    if ($used_listings > $listings_by_plan) {
                        $update['fields']['Listings_remains'] = 0;

                        $sql  = "UPDATE `". RL_DBPREFIX."listings` SET `Status` = 'approval' ";
                        $sql .= "WHERE `Account_ID` = {$account['ID']}";
                        $rlDb->query($sql);

                        //activate listings depending on the membership plan restriction
                        $sql  = "UPDATE `". RL_DBPREFIX."listings` SET `Status` = 'active' ";
                        $sql .= "WHERE `Account_ID` = {$account['ID']} LIMIT {$listings_by_plan}";
                        $rlDb->query($sql);
                    } else {
                        $update['fields']['Listings_remains'] = $listings_by_plan - $used_listings;
                    }
                }

                if ($update) {
                    $update['where'] = array('ID' => $account['lpID']);
                    $rlDb->updateOne($update, 'listing_packages');
                }
            }
        }

        if (!$direct) {
            if (count($accounts) == $limit) {
                $start += $limit;
                $out['status'] = 'updating';
                $out['start'] = $start;
            } else {
                $out['status'] = 'ok';
            }
        }

        return $out;
    }

    /**
     * Prepare data for import preview grid and pagination
     * @param array $data      - importing data
     * @param bool  $user_mode
     */
    public function prepareImportPreviewGrid($data, $user_mode = false)
    {
        $mode = $user_mode ? 'pg' : 'page';
        $page = $_GET[$mode] ? intval($_GET[$mode]) : 1 ;
        $limit = (int) $GLOBALS['config']['listings_per_page'];
        $total_listing = count($data);
        $total_pages = ceil($total_listing / $limit);

        // data for mapping grid on the Export/Import page. Including Pagination
        $grid['start'] = ($page - 1 ) * $limit;
        $grid['end'] = $grid['start'] + $limit;
        // first column is not import data
        $grid['total'] = $total_listing - 1;
        $grid['current'] = $page;
        $grid['limit'] = $limit;
        $grid['total_pages'] = $total_pages != 1 ? $total_pages : null ;

        if($user_mode) {
            //TODO: Check on non mod_rewrite domains
            $grid['paging_url'] = $GLOBALS['pages']['xls_export_import'] . '/import-table';
        }
        $GLOBALS['rlSmarty'] -> assign_by_ref('grid', $grid);
    }

    public function prepareExportPreviewGrid($data, $user_mode = false)
    {
        $pagination = $this->getPaginationData('export');
        $listings = array_slice($data, 0, $pagination['limit']);
        $GLOBALS['rlSmarty']->assign('export_listings', $listings);
        if (count($data) > $pagination['limit']) {
            $GLOBALS['rlSmarty']->assign('grid', $pagination);
        }
    }

    /**
     * Ajax pagination handler
     *
     * @param  string $page - Current page
     * @return array  $out  - Current pagination data
     */
    public function ajaxPagination($page, $type)
    {
        $data = ($type == 'export') ? $_SESSION['export_data'] : $_SESSION['import_data'];
        $pagination_data = $this->getPaginationData();
        $start = ($page - 1) * $pagination_data['limit'];
        $listings = array_slice($data, $start, $pagination_data['limit']);
        $tpl_var = ($type == 'export') ? 'export_listings' : 'import_data';
        $GLOBALS['rlSmarty']->assign($tpl_var, $listings);

        if ($type == 'export') {
            $GLOBALS['rlSmarty']->assign('fields', $_SESSION['fields']);
        }

        $out['status'] = 'OK';
        $file = ($type == 'export')
            ? RL_PLUGINS . 'export_import/admin/export_grid.tpl'
            : RL_PLUGINS . 'export_import/admin/grid.tpl';
        $out['html'] = $GLOBALS['rlSmarty']->fetch($file, null, null, false);

        return $out;
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function getPaginationData($type = 'import')
    {
        $data = ($type == 'import') ? $_SESSION['import_data'] : $_SESSION['export_data'];
        $out['limit'] = $GLOBALS['config']['listings_per_page'];
        $out['total_listing'] = is_array($data) ? count($data) : 0;
        $out['total_pages'] = ceil($out['total_listing'] / $out['limit']);
        $out['status'] = 'OK';

        return $out;
    }

    /**
     * Find ID of the category to which listing will be assign
     *
     * @since 3.9.0 - The third $listingType parameter added
     *
     * @param  array  $levels      - Array of the categories, mapped to the importing row.
     * @param  array  $mapping     - Importing row.
     * @param  string $listingType - Listing type key
     * @return bool|int            - ID of the system category, to which will be listing assigned.
     */
    public function findCategory($levels, $mapping, $listingType)
    {
        asort($levels);
        if (!$levels) {
            return false;
        }
        //Final category ID
        $category_id = 0;
        foreach ($levels as $key => $level) {
            $cat_value = $GLOBALS['rlValid']->xSql($mapping[$key]);
            $sql  = "SELECT `T1`.`ID`, `T2`.`Value`, `T1`.`Key` FROM `" . RL_DBPREFIX . "categories` AS `T1` ";
            $sql .= "LEFT JOIN `" . RL_DBPREFIX . "lang_keys` AS `T2` ";
            $sql .= "ON `T2`.`Key` = CONCAT('categories+name+', `T1`.`Key`) ";
            $sql .= "WHERE (`T2`.`Value` = '{$cat_value}' OR `T1`.`Key` = '{$cat_value}') ";
            $sql .= "AND `T1`.`Type` = '{$listingType}' AND `T1`.`Status` = 'active' ";
            if ($category_id) {
                $sql .= "AND `T1`.`Parent_ID` = {$category_id}";
            }
            $category = $GLOBALS['rlDb']->getRow($sql);

            //Move forward if category is exist
            if (!$category) {
                break;
            }
            $category_id = $category['ID'];
        }
        $this->loop_category_id = $category_id;

        return $category_id;
    }

    /**
     * Filtering Row data. Leave only values of the Multilevel categories.
     *
     * @param array $rows - Importing row data
     * @return array $categories - Array of the categories
     */
    public function getAllCategories($rows)
    {
        $categories = array();
        foreach ($rows as $key => $row) {
            //Check Category_level_ fields
            if (strpos($row, 'Category_level_') !== false) {
                $categories[$key] = $row;
            }
        }

        return $categories;
    }

    /**
     * Adding all categories assigned to the ticket.
     *
     * @param array $listing  - Listing information.
     * @param array $levels   - Categories.
     * @return array $listing - Modified listing info.
     */
    public function prepareMultiCategory($listing, $levels)
    {
        foreach ($levels as $level) {
            $depth = str_replace('Category_level_', '', $level['Key']);
            $listing[$level['Key']] = $this->getCatNameByLevel($listing, $depth);
        }

        return $listing;
    }

    /**
     * Return a category name by level.
     *
     * @param array   $listing - Importing listing Information.
     * @param array   $level   - Level array.
     * @return string $name    - Category name.
     */
    public function getCatNameByLevel($listing, $level)
    {
        global $rlCategories;

        $name = '';
        $parentIds = $rlCategories->getParentIDs($listing['Category_ID']);

        if(is_array($parentIds)) {
            array_unshift($parentIds, $listing['Category_ID']);
            $catIds = array_reverse($parentIds);
        } else {
            $catIds[] = $listing['Category_ID'];
        }
        $catInfo = $rlCategories->getCategory($catIds[$level-1]);

        if($catInfo) {
            $name = $catInfo['name'];
        }

        return $name;
    }

    /**
     * Return an array of the Maximum category level.
     *
     * @param  bool  $is_export - Should method prepare array to use array in the export or import process (import is default)
     * @return array $levels    - Array of the categories
     */
    public function getMaxCategoryLevelArray($is_export = true)
    {
        $max_level = $GLOBALS['rlDb']->getOne("Level", "`Status` = 'active' ORDER BY `Level` DESC", 'categories');
        $levels = array();
        for ($i = 0; $i <= $max_level; $i++) {
            $cur_level = $i + 1;
            $key = 'Category_level_' . $cur_level;
            $phrase_key = 'eil_category_level_' . $cur_level;

            if($is_export) {
                $levels[$key] = array(
                    'Key' => $key,
                    'pName' => $phrase_key,
                );
            } else {
                $levels[]= array(
                    'Key' => $key,
                    'name' => $GLOBALS['lang'][$phrase_key],
                );
            }
        }

        return $levels;
    }

    /**
     * Is user exceeded Membership limit
     *
     * @param  int   $account_id - Account ID
     * @param  array $plan_info  - Membership plan info
     * @return bool              - Is limit is exceeded
     */
    public function isLimitExceeded($account_id, $plan_info)
    {
        $sql = "SELECT COUNT('ID') as `count` FROM `" . RL_DBPREFIX . "listings` WHERE `Account_ID` = {$account_id} ";
        $sql .= "AND `Plan_type` = 'account'";
        $count = $GLOBALS['rlDb']->getRow($sql);

        if ($plan_info['Listing_number'] > 0 && $count['count'] >= $plan_info['Listing_number']) {
            return true;
        }

        return false;
    }

    /*
     * @hook apTplHeader
     * @since 3.5.0
     */
    public function hookApTplHeader()
    {
        if ($GLOBALS['controller'] == 'export_import') {
            $link = "<link href='" . RL_PLUGINS_URL . "";
            $link .= "export_import/static/style.css' type='text/css' rel='stylesheet' />";
            echo $link;
        }
    }

    /**
     * @hook apPhpIndexBottom
     * @since 3.5.0
     */
    public function hookApPhpIndexBottom()
    {
        if ($GLOBALS['controller'] == 'export_import' && $GLOBALS['breadCrumbs'][0]['Controller']) {
            $action = '';
            switch ($_GET['action']) {
                case 'import':
                    $action = 'reset';
                    break;

                case 'importing':
                    $action = 'action=import';
                    break;

                case 'export_table':
                    $action = 'action=export';
                    break;
            }
            $GLOBALS['breadCrumbs'][0]['Controller'] .= '&' . $action;
        }
    }

    /**
     * @deprecated 3.10.0
     */
    public function hookApExtListingsFilters()
    {}

    /**
     * @hook listingsModifyFieldSearch
     * @since 3.5.0
     */
    public function hookListingsModifyFieldSearch (&$sql)
    {
        if (defined('EIL_EXPORT_TABLE') || $GLOBALS['page_info']['Key'] == 'xls_export_import') {
            $sql .= "`T7`.`Username` AS `Account_username`, `T7`.`Mail` AS `Account_email`, `T1`.`Main_photo` AS `Picture_URLs`, ";
        }

        if (defined('EIL_EXPORT_TABLE')) {
            $sql .= "`PC`.`Key` AS `Parent_cat_key`, ";
        }
    }

    /**
     * @hook listingsModifyWhereSearch
     * @since 3.5.0
     */
    public function hookListingsModifyWhereSearch(&$sql)
    {
        global $page_info, $account_info;

        if ((defined('REALM') && REALM == 'admin' && $_GET['controller'] == 'export_import')
            || ($page_info['Key'] == 'xls_export_import')
        ) {
            // remove active status of the listings
            $sql = str_replace("`T1`.`Status` = 'active' AND", ' ', $sql);

            $category_id = $GLOBALS['category_id'] ?: $_SESSION['eil_data']['post']['export_category_id'];
            if ($category_id) {
                $sql .= "AND ((`T1`.`Category_ID` = {$category_id} OR FIND_IN_SET({$category_id}, `T3`.`Parent_IDs`) > 0) OR (FIND_IN_SET({$category_id}, `T1`.`Crossed`) > 0)) ";
            }

            $from = $_SESSION['eil_data']['post']['export_date_from'];
            if ($from) {
                $sql .= "AND UNIX_TIMESTAMP(DATE(`T1`.`Pay_date`)) >= UNIX_TIMESTAMP('{$from}') ";
            }

            $to = $_SESSION['eil_data']['post']['export_date_to'];
            if ($to) {
                $sql .= "AND UNIX_TIMESTAMP(DATE(`T1`.`Pay_date`)) <= UNIX_TIMESTAMP('{$to}') ";
            }

            if ($page_info['Key'] == 'xls_export_import' && $account_info['ID']) {
                $sql .= "AND `T1`.`Account_ID` = {$account_info['ID']} ";
            }
        }
    }

    /**
     * @hook tplHeader
     *
     * @since 3.7.0 added new MultiField support
     * @since 3.5.0
     */
    public function hookTplHeader()
    {
        global $rlSmarty, $lang;

        if ($GLOBALS['page_info']['Key'] != 'xls_export_import') {
            return;
        }

        $frontCss = '<link href="' . RL_PLUGINS_URL . 'export_import/static/front-end.css" ';
        $frontCss .= 'type="text/css" rel="stylesheet" />';
        echo $frontCss;

        if (Helpers::isMultiFieldInstalled()) {
            printf(
                "<script>
                    lang['any'] = '%s';
                    var mfFields = new Array();
                    var mfFieldVals = new Array();
                    lang['select'] = '%s';
                    lang['not_available'] = '%s';
                    </script>",
                $lang['any'],
                $lang['select'],
                $lang['not_available']
            );

            $rlSmarty->assign('mf_old_style', true);
            $rlSmarty->display(RL_PLUGINS . "multiField" . RL_DS . "tplHeader.tpl");
        }
    }

    /**
     * @hook apPhpAccountTypesTop
     * @since 3.5.0
     */
    public function hookApPhpAccountTypesTop()
    {
        $GLOBALS['rlListingTypes']->types['export_import'] = array(
            'Key' => 'export_import',
            'name' => '',
        );
    }

    /**
     * @hook specialBlock
     * @since 3.5.0
     */
    public function hookSpecialBlock()
    {
        global $rlSmarty, $account_menu, $account_info;

        if ($account_info
            && is_array($account_info['Abilities'])
            && !in_array('export_import', $account_info['Abilities'])
        ) {
            $account_menu = $rlSmarty->get_template_vars('account_menu');
            foreach ($account_menu as $key => $item) {
                if ($item['Key'] == 'xls_export_import') {
                    unset($account_menu[$key]);
                }
            }

            $rlSmarty->assign_by_ref('account_menu', $account_menu);
        }
    }

    /**
     * @hook listingsModifyJoinSearch
     * @since 3.5.0
     */
    public function hookListingsModifyJoinSearch(&$sql)
    {
        if (defined('EIL_EXPORT_TABLE')) {
            $sql .= "LEFT JOIN `" . RL_DBPREFIX . "categories` AS `PC` ON `T3`.`Parent_ID` = `PC`.`ID` ";
        }
    }

    /**
     * @hook apExtListingsSql
     * @since 3.10.0
     */
    public function hookApExtListingsSql()
    {
        if ($_GET['export_import_plugin'] && $_SESSION['iel_data_ids'] && $GLOBALS['sql']) {
            $GLOBALS['sql'] = str_replace(
                'WHERE ',
                "WHERE `T1`.`ID` IN (" . implode(",", $_SESSION['iel_data_ids']) . ") AND ",
                $GLOBALS['sql']
            );
        }

        if ($_SESSION['iel_data']['completed']) {
            // Remove last import files and clear session
            unlink($_SESSION['iel_data']['file']);

            if ($_SESSION['iel_data']['archive_dir'] && is_dir($_SESSION['iel_data']['archive_dir'])) {
                $GLOBALS['reefless']->deleteDirectory($_SESSION['iel_data']['archive_dir']);
            }

            unset($_SESSION['iel_data']);
        }
    }

    /**
     * @hook apTplListingsGrid
     * @since 3.10.0
     */
    public function hookApTplListingsGrid()
    {
        if ($GLOBALS['cInfo']['Key'] == 'export_import') {
            echo <<< JAVASCRIPT
            var gridInstance = listingsGrid.getInstance();
            var removeActions = ['move', 'featured', 'annul_featured'];

            gridInstance.ajaxUrl += '&export_import_plugin=1';
            gridInstance.actions = gridInstance.actions.filter((item) => removeActions.indexOf(item[1]) < 0);
JAVASCRIPT;
        }
    }

    /**
     * Installation method
     * @since 3.5.0
     */
    public function install()
    {
        global $rlDb;

        $current_version = $rlDb->getOne('Version', "`Key` = 'export_import'", 'plugins');

        if (version_compare($current_version, "3.0.0") < 0) {
            $sql = "UPDATE `" . RL_DBPREFIX . "lang_keys` SET `Module` = 'common' ";
            $sql .= "WHERE `Plugin` = 'export_import' AND `Module` = 'admin'";
            $rlDb->query($sql);
        }

        $GLOBALS['rlActions']->enumAdd('account_types', 'Abilities', 'export_import');

        $sql = "UPDATE `" . RL_DBPREFIX . "account_types` SET `Abilities` = CONCAT(`Abilities`, ',export_import') ";
        $sql .= "WHERE `Key` <> 'visitor' AND `Abilities` <> ''";
        $rlDb->query($sql);

        $sql = "UPDATE `" . RL_DBPREFIX . "account_types` SET `Abilities` = 'export_import' ";
        $sql .= "WHERE `Key` <> 'visitor' AND `Abilities` = ''";
        $rlDb->query($sql);

        $this->makePagesNoFollow();
    }

    /**
     * Plugin uninstall
     * @since 3.5.0
     */
    public function uninstall()
    {
        $GLOBALS['rlActions']->enumRemove('account_types', 'Abilities', 'export_import');
    }

    /**
     * @hook staticDataRegister
     *
     * @since 3.7.0 - Added $rlStatic
     * @since 3.5.0
     *
     * @param rlStatic $rlStatic
     */
    public function hookStaticDataRegister($rlStatic = null)
    {
        $rlStatic = $rlStatic !== null ? $rlStatic : $GLOBALS['rlStatic'];

        $rlStatic->addJS(RL_LIBS_URL . 'jquery/jquery.categoryDropdown.js', 'controller');
        $rlStatic->addJS(RL_PLUGINS_URL . 'export_import/static/pagination.js', 'controller');
        $rlStatic->addJS(RL_PLUGINS_URL . 'export_import/static/lib.js', 'controller');

        if (Helpers::isMultiFieldInstalled()) {
            $rlStatic->addJS(RL_PLUGINS_URL . 'multiField/static/lib.js');
        }
    }

    /**
     * @hook apTplFooter
     * @since 3.5.0
     */
    public function hookApTplFooter()
    {
        if($_GET['controller'] == 'export_import') {
            $this->addJs(RL_LIBS_URL . 'jquery/jquery.categoryDropdown.js');
            $this->addJs(RL_PLUGINS_URL . 'export_import/static/lib_admin.js');
            $this->addJs(RL_PLUGINS_URL . 'export_import/static/pagination.js');
        }
    }

    /**
     * @hook sitemapExcludedPages
     *
     * @since 3.6.0
     */
    public function hookSitemapExcludedPages(&$pages)
    {
        $pages = array_merge($pages, $this->pluginPages);
    }

    /**
     * Echo out script tag
     *
     * @param string $url - Url of the including js script
     */
    public function addJs($url) {
        echo sprintf("<script type='text/javascript' src='%s'></script>", $url);
    }

    public function addMembershipPlanUsing($membership_plan, $account_id)
    {
        $insert_data = array();
        $insert_data['Account_ID'] = $account_id;
        $insert_data['Plan_ID'] = $membership_plan['ID'];

        if (!$membership_plan['Featured_listing']) {
            $insert_data['Listings_remains'] = $membership_plan['Listing_number'];
            $insert_data['Standard_remains'] = $membership_plan['Listing_number'];
        } else {
            $insert_data['Listings_remains'] = $membership_plan['Listing_number'];
            $insert_data['Standard_remains'] = $membership_plan['Standard_listings'];
            $insert_data['Featured_remains'] = $membership_plan['Featured_listings'];
        }
        $insert_data['Type'] = 'account';
        $insert_data['Date'] = "NOW()";
        $insert_data['IP'] = Util::getClientIP();
        $GLOBALS['rlDb']->insertOne($insert_data, 'listing_packages');
    }

    /**
     * Updating membership plan using of the provided account
     *
     * @param array $account_info - Account info
     * @return bool|void          - Return false, if provided wrong argument
     */
    public function updateMembershipUsing($account_info)
    {
        if (!is_array($account_info)) {
            return false;
        }
        $GLOBALS['reefless']->loadClass('MembershipPlan');

        $plan_ID = $account_info['Plan_ID'];
        $fields = array();
        $under_mp = 0;

        $mp_info = $GLOBALS['rlMembershipPlan']->getPlan($plan_ID);
        $sql = "SELECT * FROM `" . RL_DBPREFIX . "listings` WHERE `Status` != 'trash' ";
        $sql .= "AND `Account_ID` = {$account_info['ID']}";
        $all_user_listings = $GLOBALS['rlDb']->getAll($sql);
        foreach ($all_user_listings as $listing) {
            if ($listing['Plan_type'] == 'account') {
                $under_mp++;
            }
        }

        $fields['Listings_remains'] = $under_mp > $mp_info['Listing_number']
            ? 0
            : $mp_info['Listing_number'] - $under_mp;

        if ($mp_info['Advanced_mode']) {
            $featured_remains = $mp_info['Featured_listings'];
            $listings_remains = $mp_info['Standard_listings'];
            if ($under_mp >= $listings_remains) {
                $under_mp -= $listings_remains;
                $listings_remains = 0;
            } else {
                $listings_remains = $listings_remains - $under_mp;
                $under_mp = 0;
            }

            if ($under_mp >= $featured_remains) {
                $under_mp -= $featured_remains;
                $featured_remains = 0;
            } else {
                $featured_remains = $featured_remains - $under_mp;
                $under_mp = 0;
            }
            $fields['Standard_remains'] = $listings_remains;
            $fields['Featured_remains'] = $featured_remains;
            unset($under_mp);
        } else {
            $listings_remains = $under_mp > $mp_info['Listing_number']
                ? 0
                : $mp_info['Listing_number'] - $under_mp;
            $fields['Standard_remains'] = $listings_remains;
        }

        if (!empty($fields)) {
            $update = array(
                'fields' => $fields,
                'where' => array('Account_ID' => $account_info['ID']),
            );

            $GLOBALS['rlDb']->updateOne($update, 'listing_packages');
        }
    }

    /**
     * Should I make listing featured depending on the membership plan
     *
     * @param  int  $account_id
     * @param  int  $featured_count - Featured listings count in the advanced Membership plan
     * @return bool                 - Should script make this listing featured
     */
    public function makeFeatured($account_id, $featured_count)
    {
        $sql = "SELECT COUNT(`ID`) as `count` FROM `" . RL_DBPREFIX . "listings` ";
        $sql .= "WHERE `Account_ID` = {$account_id} AND `Featured_date` != '0000-00-00 00:00:00'";
        $my_featured_count = $GLOBALS['rlDb']->getRow($sql, 'count');

        if ($featured_count > 0 && $my_featured_count >= $featured_count) {
            return false;
        }

        return true;
    }

    /**
     * Does plan is bought by provided user
     *
     * @param  array $plan       - Checking plan information
     * @param  int   $account_id - Account ID
     *
     * @throws \Exception
     *
     * @return bool              - Does transaction is exist for this plan
     */
    public function isBoughtPlan($plan, $account_id)
    {
        if (!$plan || !$account_id) {
            return false;
        }

        $listingPackages = new ListingPackages($account_id, $plan['ID']);
        return $listingPackages->isUsageRowExistInDb();
    }

    /**
     * Getting information regarding listing field
     *
     * @since 3.6.0
     *
     * @param  string $field_key - Listing field key
     * @return array|bool        - Listing field info | False if passed wrong argument
     */
    public function getListingFieldInfo($field_key = '')
    {
        if (!$field_key) {
            return false;
        }

        $sql = "SELECT * FROM `" . RL_DBPREFIX . "listing_fields` WHERE `Key` = '{$field_key}'";
        $res = $GLOBALS['rlDb']->getRow($sql);

        return $res;
    }

    /**
     * Make all pages of the plugin with nofollow tag
     *
     * @since 3.6.0
     */
    public function makePagesNoFollow()
    {
        $sql = sprintf(
            "UPDATE `%spages` SET `No_follow` = '1' WHERE `Key` IN('%s') AND `Plugin` = 'export_import'",
            RL_DBPREFIX,
            join("','", $this->pluginPages)
        );

        return $GLOBALS['rlDb']->query($sql);
    }

    /**
     * Checking does provided ajax item is belongs to the plugin
     *
     * @since 3.7.0
     * @param string $item
     *
     * @return bool
     */
    public function isValidRequest($item)
    {
        $apRequests = array(
            'eil_fetchOptions',
            'eil_ajaxImportExportPagination',
            'eil_ajaxGetPaginationInfo',
            'eil_checkListingPackage',
        );

        return in_array($item, $apRequests);
    }

    /**
     * Parse price string, get price and currency code
     *
     * @since 3.7.1
     *
     * @param  string $str - Price string
     * @return array       - Parsed data [price => float(), currency => string()]
     */
    public function parsePrice($str)
    {
        preg_match('/([\D]+)?\s*([\d\,\.\s]+)?\s*([\D]+)?/', $str, $matches);

        $out = [];

        if ($matches[2]) {
            $price = trim($matches[2]);
            $currency = '';

            preg_match('/([\.\,]+([0-9]{2}))$/', $price, $new_price);

            if ($new_price[2]) {
                $cents = '.' . $new_price[2];
                $price = substr($price, 0, -3);
                $price = str_replace([' ', ',', '.'], '', $price) . $cents;
            }

            if ($matches[1] || $matches[3]) {
                $currency = $matches[1] ?: $matches[3];
                $currency = str_replace(array('\\'), '', $currency);
                $currency = trim($currency);
            }

            $out = array(
                'price' => $price,
                'currency' => $currency
            );
        }

        return $out;
    }

    /**
     * Update to 3.0.0 version
     */
    public function update300(): void
    {
        $GLOBALS['rlDb']->query(
            "UPDATE `{db_prefix}lang_keys` SET `Module` = 'common'
             WHERE `Plugin` = 'export_import' AND `Module` = 'admin'"
        );
    }

    /**
     * Update to 3.1.0 version
     */
    public function update310(): void
    {
        $GLOBALS['reefless']->loadClass('Actions');
        $GLOBALS['rlActions']->enumAdd('account_types', 'Abilities', 'export_import');

        $GLOBALS['rlDb']->query(
            "UPDATE `{db_prefix}account_types` SET `Abilities` = CONCAT(`Abilities`, ',export_import')
             WHERE `Key` <> 'visitor' AND `Abilities` <> ''"
        );

        $GLOBALS['rlDb']->query(
            "UPDATE `{db_prefix}account_types` SET `Abilities` = 'export_import'
             WHERE `Key` <> 'visitor' AND `Abilities` = ''"
        );
    }

    /**
     * Update to 3.3.5 version
     */
    public function update335(): void
    {
    }

    /**
     * Update to 3.6.0 version
     */
    public function update360(): void
    {
        require_once RL_UPLOAD . 'export_import/vendor/autoload.php';
        $filesystem = new Filesystem();
        $filesystem->remove(RL_PLUGINS . 'export_import/vendor');
        $filesystem->copy(RL_UPLOAD . 'export_import/vendor', RL_PLUGINS . 'export_import/vendor');
    }

    /**
     * Update to 3.6.1 version
     */
    public function update361(): void
    {
        global $rlDb;

        require_once RL_UPLOAD . 'export_import/vendor/autoload.php';
        $filesystem = new Filesystem();
        $filesystem->remove(RL_PLUGINS . 'export_import/vendor');
        $filesystem->copy(RL_UPLOAD . 'export_import/vendor', RL_PLUGINS . 'export_import/vendor');

        $rlDb->query(
            "UPDATE `{db_prefix}lang_keys` SET `Value` = 'Import/Export Listings'
             WHERE `Key` = 'pages+title+xls_export_import' AND `Code` = 'en'"
        );

        $rlDb->query(
            "UPDATE `{db_prefix}lang_keys` SET `Value` = 'Import/Export Listings'
             WHERE `Key` = 'pages+name+xls_export_import' AND `Code` = 'en'"
        );

        $removingPhrases = [
            'eil_sub_category_note',
            'eil_pictures_from_zip_note',
            'eil_type_for_import_desc',
            'eil_subcategory_using_fail',
        ];

        $keys = implode("','", $removingPhrases);
        $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` IN ('{$keys}') AND `Plugin` = 'export_import'");
    }

    /**
     * Update to 3.7.2 version
     */
    public function update372(): void
    {
        if (array_search('Flynax\Component\Filesystem', get_declared_classes()) === false) {
            require_once RL_UPLOAD . 'export_import/vendor/autoload.php';
        }

        $filesystem = new Filesystem();
        $filesystem->remove(RL_PLUGINS . 'export_import/phpExcel/');
        $filesystem->remove(RL_PLUGINS . 'export_import/vendor/');
        $symfonyFilesystem = new \Symfony\Component\Filesystem\Filesystem();
        $symfonyFilesystem->mirror(RL_UPLOAD . 'export_import/vendor/', RL_PLUGINS . 'export_import/vendor/');
    }

    /**
     * Update to 3.8.0 version
     */
    public function update380(): void
    {
        global $rlDb;

        // remove phrases
        $phrases = ['eil_no_form', 'eil_listingeil_start_fields'];
        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
             WHERE `Plugin` = 'export_import' AND `Key` IN ('" . implode("','", $phrases) . "')"
        );

        require RL_UPLOAD . 'export_import/vendor/autoload.php';
        $filesystem = new Filesystem();
        $oldVendor = RL_PLUGINS . 'export_import/vendor/';
        $filesystem->remove($oldVendor);
        $copyFunction = method_exists($filesystem, 'copyTo') ? 'copyTo' : 'copy';
        $filesystem->$copyFunction(RL_UPLOAD . 'export_import/vendor/', $oldVendor);

        if (array_key_exists('ru', $GLOBALS['languages'])) {
            $russianTranslation = json_decode(file_get_contents(RL_UPLOAD . 'export_import/i18n/ru.json'), true);

            foreach ($russianTranslation as $phraseKey => $phraseValue) {
                if (!$rlDb->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $insertPhrase = $rlDb->fetch(
                        ['Module', 'Key', 'Plugin'],
                        ['Code' => $GLOBALS['config']['lang'], 'Key' => $phraseKey],
                        null, 1, 'lang_keys', 'row'
                    );

                    $insertPhrase['Code']  = 'ru';
                    $insertPhrase['Value'] = $phraseValue;

                    $rlDb->insertOne($insertPhrase, 'lang_keys');
                } else {
                    $rlDb->updateOne([
                        'fields' => ['Value' => $phraseValue],
                        'where' => ['Key'   => $phraseKey, 'Code' => 'ru'],
                    ], 'lang_keys');
                }
            }
        }
    }

    /**
     * Update to 3.8.1 version
     */
    public function update381(): void
    {
        require RL_UPLOAD . 'export_import/vendor/autoload.php';
        $filesystem = new Filesystem();
        $oldVendor = RL_PLUGINS . 'export_import/vendor/';
        $filesystem->remove($oldVendor);
        $copyFunction = method_exists($filesystem, 'copyTo') ? 'copyTo' : 'copy';
        $filesystem->$copyFunction(RL_UPLOAD . 'export_import/vendor/', $oldVendor);
    }

    /**
     * Update to 3.10.0 version
     */
    public function update3100(): void
    {
        global $rlDb;

        $rlDb->dropColumnFromTable('Import_file', 'listings');
        $rlDb->delete(['Name' => 'apExtListingsFilters', 'Plugin' => 'export_import'], 'hooks');
        $rlDb->delete(['Key' => 'ext_imported_listings_manager', 'Plugin' => 'export_import'], 'lang_keys', null, 0);
    }
}
