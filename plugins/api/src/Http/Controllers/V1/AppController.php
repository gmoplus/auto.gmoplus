<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: APPCONTROLLER.PHP
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

use Flynax\Api\Http\Controllers\V1\AccountController;
use Flynax\Api\Http\Controllers\V1\CommentsController;
use Flynax\Api\Http\Controllers\V1\ShoppingCartController;
use Illuminate\Http\Request;
use Flynax\Utils\File;
use Flynax\Utils\Category;
use Flynax\Classes\ListingData;

class AppController extends BaseController
{

    /**
     * GET: /api/v1/app/init
     */
    public function init()
    {
        if (!$GLOBALS['plugins']['appsManager']) {
            return null;
        }

        $accountController = new AccountController();
        rl('reefless')->loadClass('AppsManager', null, 'appsManager');

        $out = [
            'config' => $this->buildAppConfigs(),
            'languages' => !empty($GLOBALS['languages']) ? $GLOBALS['languages'] : rl('Lang')->getLanguagesList(),
            'phrases' => rl('AppsManager')->getPhrases(),
            'plugins' => $GLOBALS['plugins'],
            'listing_types' => rl('ListingTypes')->types,
            'account_types' => $accountController->getAccountTypes(),
            'cat_type_box' => $this->getHomeCatTypes(),
            'listings' => (new ListingsController)->getHomeListings(),
            'accounts' => $accountController->getMainAccounts(),
            'search_forms' => $this->buildSearch(),
            'search_forms_map' => $this->buildSearchOnMap(),
        ];


        // if user login
        if ($accountController->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
            $out['account_info'] = $accountController->getProfile($_REQUEST['account_id']);
        }

        // Allow send message
        $allowSendMessage = $accountController->isSendMessage();
        $out['config']['allow_send_message'] = $allowSendMessage['allow_send_message'];

        // Get report broken item
        if ($GLOBALS['plugins']['reportBrokenListing']) {
            $this->getReportBrokenInfo($out);
        }

        // Shopping cart items
        if ($GLOBALS['plugins']['shoppingCart']) {
            (new ShoppingCartController)->getItems($out);
        }

        return $out;
    }

    /**
     * Build home types or categories
     */
    public function getHomeCatTypes()
    {
        global $config, $tpl_settings;

        $tpl_settings['category_menu_listing_type'] = true;

        $out = [];
        if ($config['app_manager_main_types'] == 'default') {
            $types = rl('ListingTypes')->types;
            if ($types && count($types) == 1) {
                $typeKey = array_key_first($types);
                $cats = rl('Categories')->getCategories(0, $types[$typeKey]['Key'], false, false);
                $out = $cats;
            }
            else {
                $out = $types;
            }
        }
        else {
            $out = Category::getCategoryIconMenu();
        }
        return $out;
    }

    /**
     * Build app configs
     */
    public function buildAppConfigs()
    {
        $appConfigs = [];
        $config = $GLOBALS['config'];
        
        $configKeys = rl('AppsManager')->getConfigKeys();
        foreach($configKeys as $key) {
            $appConfigs[$key] = $config[$key];
        }

        $appConfigs['app_lang'] = RL_LANG_CODE;
        $appConfigs['api_version'] = $GLOBALS['version'];

        $domain_info = parse_url(RL_URL_HOME);
        $appConfigs['scheme'] = $domain_info['scheme'];
        $appConfigs['host'] = $domain_info['host'];
        $appConfigs['files_url'] = RL_FILES_URL;
        $appConfigs['libs_url'] = RL_LIBS_URL;
        $appConfigs['upload_max_size'] = \Flynax\Utils\Util::getMaxFileUploadSize();
        $appConfigs['mf_geo_filter'] = false;

        $appConfigs['paypal_module'] = $appConfigs['android_paypal_module'] = rl('Db')->getOne('ID', "`Key`='paypal' AND `Status` = 'active' ", 'payment_gateways');

        // Geo filter
        if ($GLOBALS['plugins']['multiField']) {
            rl('reefless')->loadClass('GeoFilter', null, 'multiField');
            $appConfigs['mf_geo_filter'] = rl('GeoFilter')->geo_format  ? true : false;
            $appConfigs['mf_levels'] = rl('GeoFilter')->geo_format['Levels'];
        }

        $appConfigs['hybridAuthLogin'] = 0;
        if ($GLOBALS['plugins']['hybridAuthLogin'] && version_compare($GLOBALS['plugins']['hybridAuthLogin'], '2.0.0') >= 0) {
            $appConfigs['hybridAuthLogin'] = 1;
            $appConfigs['hybridAuthLogin_password_syn'] = $config['ha_enable_password_synchronization'];
            
            rl('reefless')->loadClass('HybridAuthLogin', null, 'hybridAuthLogin');
            $hybridAuthApi = new \Flynax\Plugins\HybridAuth\API();

            $providers = $hybridAuthApi
                ->withProviderCredentials('google', array('ha_google_app_key', 'ha_google_app_id'))
                ->withProviderCredentials('twitter', array('ha_twitter_app_secret', 'ha_twitter_app_id'))
                ->getActiveProviders();

            foreach ($providers as $data) {
                if ($data['Provider']) {
                    $appConfigs[$data['Provider'] . '_login'] = 1;
                    if ($data['Credentials']) {
                        foreach ($data['Credentials'] as $cKey => $cVal) {
                            $appConfigs[$cKey] = $cVal;
                        }
                    }
                }
            }
        }

        if ($GLOBALS['plugins']['events']) {
            $rates = rl('Categories')->getDF('event_rates');
            foreach($rates as $rate) {
                $appConfigs['event_rates'][$rate['Key']] = $rate['name'];
            }
            $appConfigs['event_rates']['*cust0m*'] = $GLOBALS['lang']['event_custom_rate'];
        }

        // Shopping cart options
        if ($GLOBALS['plugins']['shoppingCart']) {
            $appConfigs['shoppingCart'] = true;
        }

        // files extantions
        foreach($GLOBALS['l_file_types'] as $key => $val) {
            $appConfigs[$key] = $val['ext'];
        }

        // Messengers
        $appConfigs['_phoneMessengers'] = $GLOBALS['_phoneMessengers'];

        return $appConfigs;
    }

    /**
     * Get categories
     *
     * @return array - listings
     **/
    public function getCategories()
    {
        $listing_type = $_POST['type'] ? $_POST['type'] : '';
        if (!$listing_type) {
            return;
        }
        $category_id = $_POST['category_id'] ? $_POST['category_id'] : 0;

        $out['categories'] = rl('Categories')->getCategories($category_id, $listing_type, false, false);
        return $out;
    }

    /**
     * Get cat tree
     *
     * @return array - listings
     **/
    public function getCatTree()
    {
        $listing_type = $_POST['type'] ? $_POST['type'] : '';
        if (!$listing_type) {
            return;
        }
        $category_id = $_POST['category_id'] ? $_POST['category_id'] : 0;

        $out['categories'] = rl('Categories')->getCatTree($category_id, $listing_type, false, false, false);

        return $out;
    }

    /**
     * Addapt value
     *
     * @param array $field - field data
     *
     * @return string $set_value - value
     **/
    public static function adaptValue($field)
    {
        switch ($field['Type']) {
            case 'phone':
                if (is_string($field['value']) && false === strpos($field['value'], 'href')) {
                    $set_value = '<a href="tel:' . $field['value'] . '">' . $field['value'] . '</a>';

                }
                else {
                    $set_value = $field['value'];
                }
                break;

            case 'file':
                $set_value = '<a href="' . RL_FILES_URL . $field['value'] . '">' . $GLOBALS['lang']['download'] . '</a>';
                break;

            case 'image':
                preg_match('/src\="([^"]+)"/', $field['value'], $matches);
                $set_value = $matches[1];
                break;

            default:
                $set_value = $field['value'];
                break;
        }

        return $set_value;
    }

    /**
     * Get home listings and accounts data
     *
     * @return array - information
     **/
    public function getHomeData()
    {
        $response = [
            'listings' => (new ListingsController)->getHomeListings(),
            'accounts' => (new AccountController)->getMainAccounts(),
        ];
        return $response;
    }

    /**
     * Get multi fields
     *
     * @return array $values
     **/
    public function getMultiFieldNextFields()
    {
        $parent = rl('Valid')->xSql($_REQUEST['parent']);
        $condition = rl('Valid')->xSql($_REQUEST['condition']);
        rl('reefless')->loadClass('MultiField', null, 'multiField');
        $order_type = rl('Db')->getOne('Order_type', "`Key` = '{$condition}'", 'data_formats');
        $response = rl('MultiField')->getData(is_numeric($parent) ? (int) $parent : $parent, false, $order_type);

        return $response;
    }

    /**
     * Delete file
     *
     * @return array $data
     **/
    public function deleteFile()
    {
        if ($result = File::removeFile(
            $_REQUEST['field'],
            $_REQUEST['value'],
            $_REQUEST['type'],
            (int) $_REQUEST['account_id'],
            (int) $_REQUEST['item_id'],
            $_REQUEST['multipart'])) {
            $response = array('status' => 'OK', 'results' => $result);
        } else {
            $response['status'] = 'ERROR';
        }

        return $response;
    }

    /**
     * Adapt form
     **/
    public static function adaptForm($form)
    {
        $fields = [];
        foreach ($form as &$group) {
            if ($group['Group_ID'] > 0) {
                $fields[] = array(
                    'Key' => 'group_' . $group['Key'],
                    'Type' => 'divider',
                    'Required' => 0,
                    'Multilingual' => 0,
                    'name' => $GLOBALS['lang'][$group['pName']],
                    'Condition' => '',
                    'Current' => '',
                );
            }

            if (is_array($group['Fields'])) {
                foreach ($group['Fields'] as $key => $value) {
                    if (!$value['name']) {
                        $value['name'] = $GLOBALS['lang'][$value['pName']];
                    }
                    $fields[$value['Key']] = $value;
                }
            }
        }

        return $fields;
    }

    /**
     * Adapt fields
     **/
    public static function adaptFields($fields, $data, $mode = 'listing')
    {
        global $lang;

        if ($GLOBALS['plugins']['multiField']) {
            $multi_field_table = $mode == 'listing' ? 'listing_fields' : 'account_fields';
            $sql = "SELECT * FROM `{db_prefix}multi_formats` AS `T1`";
            $sql .= "JOIN `{db_prefix}" . $multi_field_table . "` AS `T2` ON `T2`.`Condition` = `T1`.`Key` ";
            $sql .= "WHERE `T1`.`Status` = 'active'";
            $sql .= " AND `T1`.`Parent_ID` = 0";
            $mf_tmp = rl('Db')->getAll($sql);

            foreach ($mf_tmp as $key => $item) {
                $multi_fields[$item['Key']] = true;
            }
        }
        if (version_compare($GLOBALS['config']['rl_version'], '4.9.3') > 0 && $mode == 'listing') {
            $options = ListingData::getOptions($data['ID']);
        }

        foreach ($fields as $key => &$field) {
            $field['name'] = $field['name'] ? : $lang[$field['pName']];

            switch ($field['Type']) {
                case 'phone':
                    if ($field['Condition']) {
                        $field['df'] = rl('Categories')->getDF($field['Condition']);
                    }
                    $field['value'] = rl('reefless')->parsePhone($data[$field['Key']]);
                    break;

                case 'checkbox':
                    $field['value'] = $data[$field['Key']];
                    $out_values = [];
                    foreach($field['Values'] as $vKey => $vVal) {
                        $vVal['name'] = $lang[$vVal['pName']];
                        $out_values[] = $vVal;
                    }
                    $field['Values'] = $out_values;

                    if (is_array($field['Default'])) {
                        $field['Default'] = '';
                    }

                    break;

                case 'radio':
                    $field['value'] = $data[$field['Key']];

                    $out_values = [];
                    foreach($field['Values'] as $vKey => $vVal) {
                        $vVal['name'] = $lang[$vVal['pName']];
                        $out_values[] = $vVal;
                    }
                    $field['Values'] = $out_values;

                    break;
                case 'date':

                    if ($field['Default'] == 'multi') {
                        $field['value'] = [
                            'from' => $data[$field['Key']],
                            'to' => $data[$field['Key'] . "_multi"],
                        ];
                    }
                    else {
                        $field['value'] = $data[$field['Key']];
                    }
                    break;
                case 'text':
                case 'textarea':
                    if ($field['Multilingual'] && count($GLOBALS['languages']) > 1) {
                        $field['value'] = rl('reefless')->parseMultilingual($data[$field['Key']]);
                    } else {
                        $field['value'] = $data[$field['Key']];
                    }
                    break;

                case 'price':
                    $field['Values'] = rl('Categories')->getDF('currency');
                    $price = false;
                    $price = explode('|', $data[$field['Key']]);
                    $priceOut = [
                        'value' => $price[0],
                        'currency' => $price[1],
                    ];

                    if (version_compare($GLOBALS['config']['rl_version'], '4.9.3') > 0 && $mode == 'listing' && $field['Opt1']) {
                        $field['price_options'] = rl('Categories')->getDF('price_options');
                        if ($options[$field['Key']]) {
                            $priceOut = array_merge($priceOut, $options[$field['Key']]);
                        }
                    }

                    $field['value'] = $priceOut;
                    break;

                case 'mixed':

                    $out_values = [];
                    foreach($field['Values'] as $vKey => $vVal) {
                        $vVal['name'] = $lang[$vVal['pName']];
                        $out_values[] = $vVal;
                    }
                    $field['Values'] = $out_values;

                    $df_item = false;
                    $df_item = explode('|', $data[$field['Key']]);

                    $dfItem = [
                        'value' => $df_item[0],
                        'df' => $df_item[1],
                    ];
                    $field['value'] = $dfItem;

                    break;

                case 'select':
                    $field['multiField'] = $multi_fields[$field['Key']] ? 1 : 0;
                    $field['value'] = $data[$field['Key']];

                    if ($multi_fields[$field['Key']]) {
                        if (false !== strpos($field['Key'], '_level')) {
                            unset($field['Values']);
                            $exp_key = explode('_level', $field['Key']);
                            $parent_level = (int)$exp_key[1] - 1;
                            $parent_key = $parent_level == 0 ? $exp_key[0] : $exp_key[0] . '_level' . $parent_level;

                            // set level
                            $field['multiField_level'] = $exp_key[1];

                            $order_type = rl('Db')->getOne('Order_type', "`Key` = '{$field['Condition']}'", 'data_formats');
                            $field['Values'] = rl('MultiField')->getData($data[$parent_key], false, $order_type);


                        } else if (is_array($field['Values'])) {
                            rl('MultiField')->getPhrases($field['Condition']);
                            $field['multiField_level'] = 0;
                            foreach ($field['Values'] as $key => &$value) {
                                if (trim($key) === '') {
                                    continue;
                                }
                                $new_key = $field['Condition'] || in_array($field['Type'], array('price', 'mixed')) ? $value['Key'] : $value['ID'];
                                if ($value['Default']) {
                                    $field['Default'] = $new_key;
                                }
                                $field['Values'][$key]['name'] = rl('Lang')->getPhrase($value['pName'], null, null, true);
                            }
                        }
                    }
                    else {
                        if ($field['Condition'] != 'years') {
                            foreach ($field['Values'] as $key => &$value) {
                                if (trim($key) === '') {
                                    continue;
                                }
                                $field['Values'][$key]['name'] = rl('Lang')->getPhrase($value['pName'], null, null, true);
                            }
                        }
                    }

                    break;

                case 'accept':
                    $pageInfo = rl('Db')->fetch(
                        array('Page_type', 'Key', 'Controller'),
                        array('Status' => 'active', 'Key' => $field['Default']),
                        null,
                        null,
                        'pages',
                        'row'
                    );

                    $field['Page_type'] = $pageInfo['Page_type'];

                    switch ($pageInfo['Page_type']) {
                        case 'system':
                            $field['value'] = rl('reefless')->getPageUrl($pageInfo['Key']);
                            break;

                        case 'static':
                            $field['value'] = rl('Lang')->getPhrase('pages+content+' . $field['Default'], null, null, true);
                            break;

                        case 'external':
                            $field['value'] = $pageInfo['Controller'];
                            break;
                    }

                    break;

                default:
                    $field['value'] = $data[$field['Key']];

                    break;
            }
        }

        return $fields;
    }

    /**
     * Adapt files
     * @since 1.0.1
     **/
    public static function adaptFilesFields()
    {
        $tmpFiles = [];
        foreach ($_FILES as $key => $value) {
            if (strpos($key, '|')) {
                $exp = explode('|', $key);
                // change view to upload standard in rlAction
                $tmpFiles[$exp[0]]['name'][$exp[1]] = $value['name'];
                $tmpFiles[$exp[0]]['type'][$exp[1]] = $value['type'];
                $tmpFiles[$exp[0]]['tmp_name'][$exp[1]] = $value['tmp_name'];
                $tmpFiles[$exp[0]]['error'][$exp[1]] = $value['error'];
                $tmpFiles[$exp[0]]['size'][$exp[1]] = $value['size'];
            }
            else {
                $tmpFiles[$key] = $value;
            }
        }
        $_FILES = $tmpFiles;
    }

    /**
     * Get svg file by name
     *
     **/
    public function getSvgIcon()
    {
        $key = $_REQUEST['key'];
        $file_path = RL_LIBS . 'icons/svg-line-set/' . $key;
        $file_source = file_get_contents($file_path);

        if (strpos($file_source, '<style>')) {
            $file_source = preg_replace('/(<defs><style>.*<\/style><\/defs>)/', '', $file_source);
            $file_source = str_replace(' class="a"', ' fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5px"', $file_source);
            echo $file_source;
            exit;
        }
    }

    /**
     * Get Report Broken items
     *
     **/
    public function getReportBrokenInfo(&$out)
    {
        $points = rl('Db')->fetch('*', array('Status' => 'active'), "ORDER BY `Position`", null, 'report_broken_listing_points');

        foreach ($points as $key => $point) {
            $points[$key]['name'] = rl('Lang')->getPhrase($point['Key'], null, null, true);
        }

        if ($points) {
            $other = array('Key' => 'custom', 'name' => rl('Lang')->getPhrase('rbl_other'));
            $points[] = $other;

            $out['report_broken'] = $points;
        }
    }

    /**
     * Get places autocomplete
     *
     **/
    public function placesAutocomplete()
    {
        $query    = $_REQUEST['query'];
        $provider = $GLOBALS['config']['geocoding_provider'] == 'google'
        ? 'googlePlaces' // switch to googlePlaces for better results
        : $GLOBALS['config']['geocoding_provider'];

        if (strlen(isset($query) ? $query : $query) < 2) {
            return array(
                'status' => 'ERROR',
                'message' => 'Query string is too short, 2 characters is miniumal length'
            );
        }

        if (in_array($_REQUEST['provider'], ['nominatim', 'googlePlaces'])) {
            $provider = $_REQUEST['provider'];
        }

        $data = \Flynax\Utils\Util::geocoding($query, false, $_REQUEST['lang'], $provider);

        $responce = array(
            'status' => $data ? 'ok' : 'error',
            'results' => $data
        );
        return $responce;
    }

    /**
     * Get places coordinates
     *
     * @since 1.0.1
     **/
    public function placesCoordinates()
    {
        global $config;
        $place_id = $_REQUEST['place_id'];

        if (!$place_id || !$config['google_server_map_key']) {
            return array(
                'status' => 'ERROR',
                'message' => !$config['google_server_map_key']
                ? 'No google api key specified'
                : 'No place_id param passed'
            );
        }

        $host = 'https://maps.googleapis.com/maps/api/place/details/json';
        $params = array(
            'placeid' => $place_id,
            'key' => $config['google_server_map_key']
        );

        $request  = $host . '?' . http_build_query($params);
        $responseData = \Flynax\Utils\Util::getContent($request);
        $data = json_decode($responseData);

        $response = array(
            'status' => $data->status,
            'results' => $data->status ? $data->result->geometry->location : null
        );
        return $response;
    }

    /**
     * Build search forms
     *
     **/
    public function buildSearch()
    {
        // Get search forms
        foreach (rl('ListingTypes')->types as $type_key => $listing_type) {
            if ($listing_type['Search_page'] || $listing_type['Search_type']) {
                if ($search_form = rl('Search')->buildSearch($type_key . '_quick')) {

                    // Remove address fields from the form
                    foreach ($search_form as $key => $field) {
                        if (is_numeric(strpos($field['Fields'][0]['Key'], 'address'))) {
                            unset($search_form[$key]);
                            break;
                        }
                    }
                    $form_key = $type_key . '_on_map';
                    $out_search_forms[$type_key]['name'] = $GLOBALS['lang']['search_forms+name+'.$form_key];
                    $out_search_forms[$type_key]['listing_type'] = $type_key;
                    $out_search_forms[$type_key]['form_key'] = $form_key;

                    $fields = AppController::adaptForm($search_form);
                    $fields = AppController::adaptFields($fields, [], 'listing');
                    $out_search_forms[$type_key]['form'] = $fields;
                }
            }

            unset($search_form);
        }

        return $out_search_forms;
    }

    /**
     * Build search forms on map
     *
     **/
    public function buildSearchOnMap()
    {
        // Get search forms on map
        foreach (rl('ListingTypes')->types as $type_key => $listing_type) {
            if ($listing_type['On_map_search']) {
                if ($search_form = rl('Search')->buildSearch($type_key . '_on_map')) {
                    // Remove address fields from the form
                    foreach ($search_form as $key => $field) {
                        if (is_numeric(strpos($field['Fields'][0]['Key'], 'address'))) {
                            unset($search_form[$key]);
                            break;
                        }
                    }
                    $form_key = $type_key . '_on_map';
                    $out_search_forms[$type_key]['name'] = $GLOBALS['lang']['search_forms+name+'.$form_key];
                    $out_search_forms[$type_key]['listing_type'] = $type_key;
                    $out_search_forms[$type_key]['form_key'] = $form_key;

                    $fields = AppController::adaptForm($search_form);
                    $fields = AppController::adaptFields($fields, [], 'listing');
                    $out_search_forms[$type_key]['form'] = $fields;
                }
            }

            unset($search_form);
        }

        return $out_search_forms;
    }
}
