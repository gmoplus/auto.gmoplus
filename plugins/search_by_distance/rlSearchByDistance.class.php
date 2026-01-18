<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLSEARCHBYDISTANCE.CLASS.PHP
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

class rlSearchByDistance
{
    /**
    * ajax request mode
    **/
    var $ajax = false;

    /**
    * requested latitude
    **/
    var $lat = false;

    /**
    * requested longitude
    **/
    var $lng = false;

    /**
    * requested distance
    **/
    var $distance = false;

    /**
    * iso codes of countries which are using the imperial system
    **/
    var $imperialCountries = array('US', 'UK', 'GB', 'LR', 'MM');

    /**
    * box template
    **/
    var $box_tpl = '';

    /**
     * Format keys related to listing and account country fields
     */
    var $formatKeys = array();

    /**
    * class constructor
    **/
    function __construct()
    {
        $this->box_tpl = <<< BOX
        global \$rlSmarty;

        \$tpl = 'block.tpl';

        \$countries = array({countries});
        \$rlSmarty->assign_by_ref('sbd_countries', \$countries);
        \$rlSmarty->display(RL_PLUGINS . 'search_by_distance' . RL_DS . \$tpl);
BOX;

        // define unit
        if ($GLOBALS['config']['sbd_units'] == 'auto') {
            $GLOBALS['config']['sbd_units'] = in_array($_SESSION['GEOLocationData']->Country_code, $this->imperialCountries) ? 'miles' : 'kilometres';
        }

        if (is_object($GLOBALS['rlSmarty'])) {
            $GLOBALS['rlSmarty']->assign_by_ref('sbd_country_iso', $this->country_iso);
        }
    }

    /**
     * add Distance field to the sorting dropdown
     *
     * @hook phpListingTypeTop
     *
     * @since 4.0.0
     */
    public function hookPhpListingTypeTop()
    {
        global $date_field;

        $sort_by_distance['sbd_distance'] = array(
            'Key' => 'sbd_distance',
            'Type' => 'select',
            'name' => $GLOBALS['lang']['sbd_distance']
        );
        $date_field = array_merge($sort_by_distance, $date_field);
    }

    /**
     * remove distance field if no search form was submitted
     *
     * @hook browseMiddle
     *
     * @since 4.0.4
     */
    public function hookBrowseMiddle()
    {
        unset($GLOBALS['sorting']['sbd_distance']);
        $GLOBALS['sort_by'] = $GLOBALS['sort_by'] == 'sbd_distance' ? 'date' : $GLOBALS['sort_by'];
    }

    /**
    * mobify selection during fields fetching
    *
    * @hook - listingsModifyFieldSearch
    **/
    public function hookListingsModifyFieldSearch(&$sql, &$data)
    {
        global $config, $sorting, $custom_order, $sort_by, $sort_type, $content;

        $data = defined('CRON_FILE') ? $content : $data;

        if (!$this->ajax) {
            // define distance
            $this->distance = $data[$config['sbd_zip_field']]['distance'];
            if ($config['sbd_units'] == 'kilometres') {
                $this->distance /= 1.609344;
            }

            // direct search by coordinates
            if ($data[$config['sbd_zip_field']]['lat'] && $data[$config['sbd_zip_field']]['lng']) {
                $this->lat = $data[$config['sbd_zip_field']]['lat'];
                $this->lng = $data[$config['sbd_zip_field']]['lng'];

                // remove country data from the form to avoid duplication location search condition
                $this->unsetLocationData($data);
            }
            // search by zip code
            elseif ($data[$config['sbd_zip_field']]['zip']) {
                $zip = preg_replace('/[\W]/', '', $data[$config['sbd_zip_field']]['zip']);
                $iso_key = $config['sbd_default_country'];

                // get country code from the form
                if ($config['sbd_country_field'] && $data[$config['sbd_country_field']]) {
                    $iso_key = $this->keyToISO($data[$config['sbd_country_field']]);

                    // remove country data from the form to avoid duplication location search condition
                    $this->unsetLocationData($data);
                }

                // get location by zip and country code
                $this->getCoordinates($iso_key, $zip);
            }
        }

        // modify query
        if ($this->lat && $this->lng) {
            $sql .= "(3956 * 2 * ASIN(SQRT(POWER(SIN((" . $this->lat . " - `T1`.`Loc_latitude`) * 0.0174532925 / 2), 2) + COS(" . $this->lat . " * 0.0174532925) * COS(`T1`.`Loc_latitude` * 0.0174532925) * POWER(SIN((" . $this->lng . " - `T1`.`Loc_longitude`) * 0.0174532925 / 2), 2)))) AS `sbd_distance`, ";
        }
        // remove sorting field
        else {
            unset($sorting['sbd_distance']);
            if ($data['sort_by'] == 'sbd_distance') {
                unset($data['sort_by']);

                $custom_order = $sort_by = 'date';
                $data['sort_type'] = 'desc';
                $sort_type = 'desc';
            }
        }
    }

    /**
    * add location condition to the search query
    *
    * @hook - listingsModifyWhereSearch
    **/
    public function hookListingsModifyWhereSearch(&$sql)
    {
        if ($this->lat && $this->lng && $this->distance) {
            $sql .= "AND (3956 * 2 * ASIN(SQRT( POWER(SIN(({$this->lat} - `T1`.`Loc_latitude`) * 0.0174532925 / 2), 2) + COS({$this->lat} * 0.0174532925) * COS(`T1`.`Loc_latitude` * 0.0174532925) * POWER(SIN(({$this->lng} - `T1`.`Loc_longitude`) * 0.0174532925 / 2), 2))) <= {$this->distance}) ";
        }
    }

    /**
     * replace order clause
     *
     * @hook listingsModifySqlSearch
     *
     * @since 4.0.0
     */
    public function hookListingsModifySqlSearch(&$sql)
    {
        if ($this->lat && $this->lng) {
            $sql = str_replace('`T1`.`sbd_distance`', '`sbd_distance`', $sql);
        }
    }

    /**
    * @hook - accountsSearchDealerSqlSelect
    **/
    public function hookAccountsSearchDealerSqlSelect(&$sql, &$data)
    {
        global $data, $config;

        if (!$config['sbd_account_zip_field']) {
            $GLOBALS['rlDebug'] -> logger("Search by Distance: No account zip code field selected in configurations");
            return false;
        }

        if (!$data[$config['sbd_account_zip_field']] || !is_array($data[$config['sbd_account_zip_field']])) {
            return false;
        }

        // define distance
        $this->distance = $data[$config['sbd_account_zip_field']]['distance'];
        if ($config['sbd_units'] == 'kilometres') {
            $this->distance /= 1.609344;
        }

        // direct search by coordinates
        if ($data[$config['sbd_account_zip_field']]['lat'] && $data[$config['sbd_account_zip_field']]['lng']) {
            $this->lat = $data[$config['sbd_account_zip_field']]['lat'];
            $this->lng = $data[$config['sbd_account_zip_field']]['lng'];

            // remove country data from the form to avoid duplication location search condition
            $this->unsetLocationData($data, true);
        }
        // search by zip code
        else {
            $zip = preg_replace('/[\W]/', '', $data[$config['sbd_account_zip_field']]['zip']);
            $iso_key = $config['sbd_default_country'];

            // get country code from the form
            if ($config['sbd_account_country_field'] && $data[$config['sbd_account_country_field']]) {
                $iso_key = $this->keyToISO($data[$config['sbd_account_country_field']]);

                // remove country data from the form to avoid duplication location search condition
                $this->unsetLocationData($data, true);
            }

            // get location by zip and country code
            $this->getCoordinates($iso_key, $zip);
        }

        // modify query
        if ($this->lat && $this->lng) {
            $sql .= "(3956 * 2 * ASIN(SQRT(POWER(SIN((" . $this->lat . " - `T1`.`Loc_latitude`) * 0.0174532925 / 2), 2) + COS(" . $this->lat . " * 0.0174532925) * COS(`T1`.`Loc_latitude` * 0.0174532925) * POWER(SIN((" . $this->lng . " - `T1`.`Loc_longitude`) * 0.0174532925 / 2), 2)))) AS `sbd_distance`, ";
        }
    }

    /**
    * @hook - accountsSearchDealerSqlWhere
    **/
    public function hookAccountsSearchDealerSqlWhere(&$sql)
    {
        if ($this->lat && $this->lng && $this->distance) {
            $sql .= "AND (3956 * 2 * ASIN(SQRT(POWER(SIN(({$this->lat} - `T1`.`Loc_latitude`) * 0.0174532925 / 2), 2) + COS({$this->lat} * 0.0174532925) * COS(`T1`.`Loc_latitude` * 0.0174532925) * POWER(SIN(({$this->lng} - `T1`.`Loc_longitude`) * 0.0174532925 / 2), 2))) <= {$this->distance}) ";
        }
    }

    /**
     * @hook accountAfterStats
     *
     * @since 4.0.0
     */
    public function hookAccountAfterStats()
    {
        global $rlSmarty;

        if (isset($rlSmarty->_tpl_vars['dealer']['sbd_distance'])) {
            $rlSmarty->display(RL_PLUGINS . 'search_by_distance' . RL_DS . 'distance.tpl');
        }
    }

    /**
     * @hook listingAfterStats
     *
     * @since 4.0.0
     */
    public function hookListingAfterStats()
    {
        global $rlSmarty;

        if (isset($rlSmarty->_tpl_vars['listing']['sbd_distance'])) {
            $rlSmarty->display(RL_PLUGINS . 'search_by_distance' . RL_DS . 'distance.tpl');
        }
    }

    /**
    * update box
    **/
    public function updateBox()
    {
        global $rlDb, $config;

        $code = '';

        // get available countries
        if ($config['sbd_country_field']) {
            $this->defineFormatKeys();

            if ($this->formatKeys['listing']) {
                $countries = $GLOBALS['rlCategories']->getDF($this->formatKeys['listing']);

                // Try to get countries from multifield format
                if (!$countries) {
                    $countries = $GLOBALS['rlCache']->get('cache_multi_formats', $this->formatKeys['listing']);
                }

                if ($countries) {
                    foreach ($countries as $country) {
                        if ($iso_key = $this->keyToISO($country['Key'])) {
                            $adapted_key = str_replace($this->formatKeys['listing'] . '_', '', $country['Key']);
                            $code .= "'{$iso_key}' => array('Code' => '{$iso_key}', 'Key' => '{$adapted_key}', 'pName' => 'data_formats+name+{$country['Key']}'),";
                        }
                    }
                    $code = rtrim($code, ',');
                }
            }
        }

        // update box
        $rlDb->rlAllowHTML = true;

        $update = array(
            'fields' => array(
                'Content' => str_replace('{countries}', $code, $this->box_tpl)
            ),
            'where' => array(
                'Key' => 'search_by_distance'
            )
        );
        $rlDb->updateOne($update, 'blocks');
    }

    /**
     * Convert country key to iso_code
     *
     * @since 4.0.0
     */
    public function keyToISO($key) {
        $this->defineFormatKeys();

        $iso_key = $this->country_iso[$key];

        if (!$iso_key && $this->formatKeys) {
            $match = preg_split('/^(' . implode('|', $this->formatKeys) . ')_/', $key);

            if ($match[1] && $this->country_iso[$match[1]]) {
                $iso_key = $this->country_iso[$match[1]];
            }
        }

        return $iso_key;
    }

    /**
     * unset location related fields from data array
     *
     * @since 4.0.0
     */
    public function unsetLocationData(&$data, $accountMode = false) {
        global $config;

        $config_name = $accountMode ? 'sbd_account_country_field' : 'sbd_country_field';
        if ($config[$config_name] && is_array($data)) {
            unset($data[$config[$config_name]]);
            for ($i = 1; $i <= 3; $i++) {
                if ($data[$config[$config_name] . '_level' . $i]) {
                    unset($data[$config[$config_name] . '_level' . $i]);
                }
            }
        }
    }

    /**
     * @deprecated 4.2.1 - Plugin compatibility doesn't require this method code anymore
     */
    public function hookTplFooter() {}

    /**
     * @hook staticDataRegister
     *
     * @since 3.2.8
     */
    public function hookStaticDataRegister()
    {
        // register css
        $GLOBALS['rlStatic']->addFooterCSS(
            RL_PLUGINS_URL . 'search_by_distance/static/style.css',
            'search_by_distance',
            true
        );
    }

    /**
     * @hook apMixConfigItem
     *
     * @since 4.0.0
     */
    public function hookApMixConfigItem(&$param1)
    {
        global $rlDb, $lang, $config;

        if ($param1['Plugin'] != 'search_by_distance')
            return;

        switch ($param1['Key']) {
            case 'sbd_zip_field':
                $param1['Values'] = array();
                $rlDb->setTable('listing_fields');
                foreach ($rlDb->fetch(array('Key'), array('Status' => 'active'), "AND `Type` IN ('text','number')") as $item) {
                    $param1['Values'][] = array('ID' => $item['Key'], 'name' => $lang['listing_fields+name+'.$item['Key']]);
                }
                break;

            case 'sbd_country_field':
                $param1['Values'] = array();
                $rlDb->setTable('listing_fields');
                foreach ($rlDb->fetch(array('Key'), array('Status' => 'active', 'Type' => 'select')) as $item) {
                    $param1['Values'][] = array('ID' => $item['Key'], 'name' => $lang['listing_fields+name+'.$item['Key']]);
                }
                break;

            case 'sbd_default_country':
                $param1['Values'] = array();
                $param1['Type'] = 'select';

                // define data format
                if ($config['sbd_country_field']) {
                    $df = $rlDb->getOne('Condition', "`Key` = '{$config['sbd_country_field']}'", 'listing_fields');
                    if ($df) {
                        $GLOBALS['reefless']->loadClass('Categories');
                        $countries = $GLOBALS['rlCategories']->getDF($df);
                    }
                }

                // countries from the data entries
                if ($countries) {
                    foreach ($countries as &$item) {
                        if ($id = $this->keyToISO($item['Key'])) {
                            $param1['Values'][] = array('ID' => $id, 'name' => $lang['data_formats+name+'.$item['Key']]);
                        }
                    }
                }
                // countries from the mapping
                else {
                    foreach ($this->country_iso as $country_key => $iso_key) {
                        $param1['Values'][] = array('ID' => $iso_key, 'name' => ucwords(str_replace('_', ' ', $country_key)));
                    }
                }
                break;

            case 'sbd_account_zip_field':
                $param1['Values'] = array();
                $rlDb->setTable('account_fields');
                foreach ($rlDb->fetch(array('Key'), array('Status' => 'active'), "AND `Type` IN ('text','number')") as $item) {
                    $param1['Values'][] = array('ID' => $item['Key'], 'name' => $lang['account_fields+name+'.$item['Key']]);
                }
                break;

            case 'sbd_account_country_field':
                $param1['Values'] = array();
                $rlDb->setTable('account_fields');
                foreach ($rlDb->fetch(array('Key'), array('Status' => 'active', 'Map' => '1', 'Type' => 'select')) as $item) {
                    $param1['Values'][] = array('ID' => $item['Key'], 'name' => $lang['account_fields+name+'.$item['Key']]);
                }
                break;
        }
    }

    /**
     * update box data after config save
     *
     * @since 4.0.3
     */
    public function hookApPhpConfigAfterUpdate()
    {
        if ($_POST['group_id'] == $GLOBALS['rlDb']->getOne('ID', "`Plugin` = 'search_by_distance'", 'config_groups')) {
            $GLOBALS['config']['sbd_country_field'] = $GLOBALS['rlDb']->getOne('Default', "`Key` = 'sbd_country_field'", 'config');
            $this->updateBox();
        }
    }

    /**
     * ajax request handler
     *
     * @since 4.0.0
     */
    public function hookAjaxRequest(&$out, &$request_mode, &$request_item, &$request_lang)
    {
        if ($request_mode != 'sbdSearch') {
            return;
        }

        global $rlSmarty, $page_info, $config, $rlSearch;

        if (!$config['sbd_zip_field']) {
            $msg = "Search by Distance: No listing zip code field selected in configurations";

            $GLOBALS['rlDebug'] -> logger($msg);
            $out = array(
                'status' => 'error',
                'message' => $msg
            );
            return;
        }

        $this->ajax = true;

        $this->lat = (double) $_REQUEST['lat'];
        $this->lng = (double) $_REQUEST['lng'];
        $this->distance = (double) $_REQUEST['distance'] / 1000 / 1.609344; // Distance always came in meters, converting to miles

        $page = (int) $_REQUEST['page'];
        $listing_type = $GLOBALS['rlValid']->xSql($_REQUEST['type']);
        $data = $this->adaptSerializedForm($_REQUEST['form']);
        $data['sort_by'] = $GLOBALS['rlValid']->xSql($_REQUEST['sortingField']);
        $data['sort_type'] = $GLOBALS['rlValid']->xSql($_REQUEST['sortingType']);

        $this->smartySupport();

        $this->bannerSupport();

        // prepare fields
        $GLOBALS['reefless']->loadClass('Search');
        $rlSearch->getFields($listing_type . '_quick', $listing_type);

        // define sorting fields
        $date_field['date'] = array('Key' => 'date', 'Type' => 'date', 'name' => $GLOBALS['lang']['date']);
        $sorting = $rlSearch->fields = is_array($rlSearch->fields) ? array_merge($date_field, $rlSearch->fields) : $date_field;

        // search listings
        $limit    = $page === 0 ? $config['sbd_listings_limit'] : $config['listings_per_page'];
        $listings = $rlSearch->search($data, $listing_type, $page, $limit);
        $markers  = null;

        if ($listings) {
            // prepare markers data
            if ($page === 0) {
                $markers = $this->prepareMarkers($listings);
                $listings = array_slice($listings, 0, $config['listings_per_page']);
            }

            // unset unnecessary sorting fields
            foreach($sorting as $field_key => $field) {
                if (in_array($field['Key'], array($config['sbd_zip_field'], $config['sbd_country_field']))
                    || (bool) preg_match('/^' .$config['sbd_country_field'] . '_level/', $field['Key'])) {
                    unset($sorting[$field_key]);
                }
            }

            // assign sorting fields
            $rlSmarty->assign_by_ref('sorting', $sorting);
            $rlSmarty->assign('sort_by', $data['sort_by']);
            $rlSmarty->assign('sort_type', $data['sort_type']);

            // prepare environment
            $rlSmarty->assign('search_results', true);
            $rlSmarty->assign_by_ref('listings', $listings);

            // prepare page info
            $page_info = array(
                'calc' => $rlSearch->calc,
                'Path' => $GLOBALS['pages']['search_by_distance'],
                'current' => $page
            );
            $rlSmarty->assign_by_ref('pInfo', $page_info);

            // fetch html
            $tpl = 'controllers' . RL_DS . 'listing_type.tpl';
            $html = $GLOBALS['rlSmarty']->fetch($tpl, null, null, false);
            $html = preg_replace('/(\<a\s)/', '<a target="_blank" ', $html);

            // prepare array
            $out = array(
                'status' => 'ok',
                'listings' => $markers,
                'count' => $rlSearch->calc,
                'html' => $html
            );
        } else {
            $out = array(
                'status' => 'error',
                'message' => $GLOBALS['lang']['sbd_no_ads_found']
            );
        }
    }

    /**
     * Prepare listing data
     *
     * @since 4.0.0
     *
     * @param  array $listings - Full listings data
     * @return array           - Optimized listing data
     */
    private function prepareMarkers(&$listings)
    {
        global $config, $pages, $rlListingTypes;

        $exclude_short_form_fields = array('price', 'title');

        $price_field_key = $config['price_tag_field'];

        // transfer fields mapping
        $transfer = array(
            'ID' => 'ID',
            'Loc_latitude' => 'lat',
            'Loc_longitude' => 'lng',
            'listing_title' => 'lt',
            'Main_photo' => 'mp',
            'Photos_count' => 'pct',
            'Featured' => 'fd',
            'fields_data' => 'fields_data'
        );

        foreach ($listings as &$listing) {
            $listing_type = &$rlListingTypes->types[$listing['Listing_type']];

            // set empty values for main fields
            $out_listing['price'] = '0';
            $out_listing['fields_data'] = array();
            $out_listing['gc'] = 1;

            foreach ($listing as $field_key => $field_value) {
                if (isset($transfer[$field_key])) {
                    $out_listing[$transfer[$field_key]] = $field_value;
                }
            }

            // set price
            if ($listing['fields'][$price_field_key]['value']) {
                $out_listing['price'] = $listing['fields'][$price_field_key]['value'];
            }

            // set date field
            $out_listing['dt'] = date(str_replace(array('%', 'b'), array('', 'M'), RL_DATE_FORMAT), strtotime($listing['Date']));

            // set "fields_data"
            foreach ($listing['fields'] as &$field) {
                if (!$field['Details_page'] || $field['value'] == '' || in_array($field['Key'], $exclude_short_form_fields))
                    continue;

                $out_listing['fields_data'][] = array('name' => $field['value']);
            }

            if (method_exists($GLOBALS['reefless'], 'url')) {
                $link = $GLOBALS['reefless']->url('listing', $listing);
            } else {
                $link = SEO_BASE;
                $link .= $config['mod_rewrite']
                ? $pages[$listing_type['Page_key']] .'/'. $listing['Path'] .'/'. $GLOBALS['rlValid']->str2path($listing['listing_title']) .'-'. $listing['ID'] .'.html'
                : 'index.php?page='. $pages[$listing_type['Page_key']] .'&amp;id=' . $listing['ID'];
            }
            $out_listing['lu'] = $link;

            // new listing
            $out_listings[] = $out_listing;

            // clear stack
            unset($out_listing);
        }

        return $out_listings;
    }

    /**
     * Update box cache on countries data format touch
     *
     * @since 4.1.0
     */
    public function hookApPhpFormatsAjaxAddItem()
    {
        $this->updateBox();
    }

    /**
     * Update box cache on countries data format touch
     *
     * @since 4.1.0
     */
    public function hookApPhpFormatsAjaxDeleteItem()
    {
        $this->updateBox();
    }

    /**
     * Update box cache on countries data format touch
     *
     * @since 4.1.0
     */
    public function hookApExtDataFormatsUpdate()
    {
        $this->defineFormatKeys();

        if ($this->formatKeys['listing']
            && $_REQUEST['parent'] == $this->formatKeys['listing']
            && $_REQUEST['field'] == 'Status'
        ) {
            $GLOBALS['rlDb']->updateOne($GLOBALS['updateData'], 'data_formats');
            $GLOBALS['rlCache']->updateDataFormats();

            $this->updateBox();
            exit;
        }
    }

    /**
     * print styles in header
     *
     * @since 4.1.0
     */
    public function hookTplHeader()
    {
        if (version_compare($GLOBALS['config']['rl_version'], '4.9.3', '>')) {
            return;
        }

        $special_block = $GLOBALS['rlSmarty']->_tpl_vars['home_page_special_block'];

        if (array_key_exists('search_by_distance', $GLOBALS['blocks'])
            || $special_block['Key'] == 'search_by_distance'
        ) {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'search_by_distance' . RL_DS . 'header_block.tpl');
        }
    }

    /**
     * Optimize zip code field output on Saved Search page
     *
     * @since 5.0.0
     * @hook savedSearchBottom
     */
    public function hookSavedSearchBottom()
    {
        foreach ($GLOBALS['saved_search'] as &$search) {
            foreach($search['fields'] as &$field) {
                if (isset($field['value']['zip'])) {
                    $field['value'] = str_replace(
                        array('{radius}', '{location}'),
                        array($field['value']['distance'] . ' ' . $GLOBALS['config']['sbd_units'], $field['value']['zip']),
                        $GLOBALS['lang']['sbd_within_full']
                    );
                }
            }
        }
    }

    /**
     * Define format keys for listing and account country field
     *
     * @since 4.2.1
     */
    public function defineFormatKeys()
    {
        global $rlDb, $config;
        static $done = false;

        if ($done) {
            return;
        }

        if ($config['sbd_country_field']) {
            $this->formatKeys['listing'] = $rlDb->getOne(
                'Condition',
                "`Key` = '{$config['sbd_country_field']}'",
                'listing_fields'
            );
        }

        if ($config['sbd_account_country_field']) {
            $this->formatKeys['account'] = $rlDb->getOne(
                'Condition',
                "`Key` = '{$config['sbd_account_country_field']}'",
                'account_fields'
            );
        }

        $done = true;
    }

    /**
     * establish smarty support to supply .tpl files fetching
     *
     * @since 4.0.0
     */
    private function smartySupport()
    {
        global $rlSmarty, $config;

        // assign tpl base settings
        require_once(RL_ROOT . 'templates' . RL_DS . $config['template'] . RL_DS . 'settings.tpl.php');

        $rlSmarty->preAjaxSupport();
    }

    /**
     * backup user countries which were actual for version before 4.0.0
     *
     * @since 4.0.0
     */
    public function backup()
    {
        global $rlDb;

        $backup_dir = RL_ROOT . 'backup' . RL_DS . 'plugins' . RL_DS;

        if (is_writable($backup_dir)) {
            $rlDb->setTable('lang_keys');

            // backup phrases
            if ($phrases = $rlDb->fetch('*', array('Plugin' => 'search_by_distance'), "AND `Key` LIKE 'sbd_countries+name+sbd_country_%'")) {
                $file_content = "INSERT INTO `{db_prefix}lang_keys` (`Code`, `Module`, `Key`, `Value`, `Plugin`) VALUES\r\n";

                foreach ($phrases as &$phrase) {
                    $file_content .= <<< VS
('{$phrase['Code']}', '{$phrase['Module']}', '{$phrase['Key']}', '{$phrase['Value']}', 'search_by_distance'),\r\n
VS;
                }
                $file_content = preg_replace('/(\,\r\n)$/', ';', $file_content);
            }

            // backup countries
            $rlDb->setTable('sbd_countries');
            if ($countries = $rlDb->fetch('*')) {
                $file_content .= "\r\n\r\nINSERT INTO `{db_prefix}sbd_countries` (`Code`, `Status`) VALUES\r\n";

                foreach ($countries as &$country) {
                    $file_content .= <<< VS
('{$country['Code']}', '{$country['Status']}'),\r\n
VS;
                }
                $file_content = preg_replace('/(\,\r\n)$/', ';', $file_content);
            }

            // write to file
            $backup_path = $backup_dir . "Search_by_distance_countries_". date('d.m.Y') .".txt";
            $file = fopen($backup_path, 'w+');

            fwrite($file, $file_content);
            fclose($file);
        }
    }

    private function adaptSerializedForm(&$data) {
        global $tpl_settings;

        foreach($data as $item) {
            if (!$item['value']) continue;

            // remove f[] from the field name
            $item['name'] = preg_replace('/^f\[([^\]]+)\]/', '$1', $item['name']);

            preg_match('/([^\[]+)(\[(.*?)\])?$/', $item['name'], $matches);

            if ($matches[3]) {
                $out[$matches[1]][$matches[3]] = $item['value'];
            } else {
                $out[$matches[1]] = $item['value'];
            }
        }

        return $out;
    }

    /**
     * Banners plugin support
     *
     * @since 4.1.2
     */
    private function bannerSupport()
    {
        global $rlDb, $blocks, $block_keys, $reefless, $rlSmarty, $rlXajax;

        $page_id = $rlDb->getOne('ID', "`Key` = 'search_by_distance'", 'pages');

        // Get related boxes
        $sql = "
            SELECT * FROM `{db_prefix}blocks`
            WHERE (`Sticky` = '1' OR FIND_IN_SET( '{$page_id}', `Page_ID` ) > 0)
            AND `Status` = 'active' AND `Side` = 'integrated_banner' 
            ORDER BY `Position`
        ";
        $block_keys = $blocks = $rlDb->getAll($sql, 'Key');

        // No banners in grid found
        if (!$blocks) {
            return;
        }

        $blocks['integrated_banner'] = true;
        $rlSmarty->assign_by_ref('blocks', $blocks);

        // register system functions
        $rlSmarty->register_function('showIntegratedBanner', [$rlSmarty, 'showIntegratedBanner']);

        // Assign html content
        foreach ($blocks as &$block) {
            if ($block['Type'] == 'html') {
                $block['Content'] = $GLOBALS['lang']['blocks+content+' . $block['Key']];
            }
        }

        // Create smarty eval methods
        if (!function_exists('smartyEval')) {
            function smartyEval($param, $content, &$smarty){
                return $content;
            }
        }

        if (!function_exists('insert_eval')) {
            function insert_eval($params, &$smarty) {
                require_once( RL_LIBS . 'smarty' . RL_DS . 'plugins' . RL_DS . 'function.eval.php');
                return smarty_function_eval(array("var" => $params['content']), $smarty);
            }
        }

        $rlSmarty->register_block('eval', 'smartyEval', false);

        if ($GLOBALS['plugins']['banners']) {
            // Create $rlXajax instance
            require_once( RL_LIBS . 'ajax' . RL_DS . 'xajax_core' . RL_DS . 'xajax.inc.php' );
            $rlXajax = new xajax();

            // Get banners
            $reefless->loadClass('Banners', null, 'banners');
            $GLOBALS['rlBanners']->prepareBannersList();
        }
    }

    /**
     * Gets zip code coordinates from geocoder
     *
     * @since 4.2.1
     *
     * @param string $country_code - ICO country code
     * @param string $zip_code     - postal/zip code
     */
    private function getCoordinates($country_code = null, $zip_code = null)
    {
        if (!$country_code || !$zip_code) {
            return;
        }

        $content = \Flynax\Utils\Util::geocoding([
            'country' => $country_code,
            'postalcode' => $zip_code
        ]);

        if ($content[0] && $content[0]['lat'] && $content[0]['lng']) {
            $this->lat = $content[0]['lat'];
            $this->lng = $content[0]['lng'];
        }
    }

    var $country_iso = array(
        'aland' => 'AX',
        'afghanistan' => 'AF',
        'albania' => 'AL',
        'algeria' => 'DZ',
        'american_samoa' => 'AS',
        'andorra' => 'AD',
        'angola' => 'AO',
        'anguilla' => 'AI',
        'antarctica' => 'AQ',
        'antigua_and_barbuda' => 'AG',
        'argentina' => 'AR',
        'armenia' => 'AM',
        'aruba' => 'AW',
        'australia' => 'AU',
        'austria' => 'AT',
        'azerbaijan' => 'AZ',
        'bahamas' => 'BS',
        'bahrain' => 'BH',
        'bangladesh' => 'BD',
        'barbados' => 'BB',
        'belarus' => 'BY',
        'belgium' => 'BE',
        'belize' => 'BZ',
        'benin' => 'BJ',
        'bermuda' => 'BM',
        'bhutan' => 'BT',
        'bolivia' => 'BO',
        'bosnia_and_herzegovina' => 'BA',
        'botswana' => 'BW',
        'bouvet_island' => 'BV',
        'brazil' => 'BR',
        'british_indian_ocean_territory' => 'IO',
        'british_virgin_islands' => 'VG',
        'brunei' => 'BN',
        'bulgaria' => 'BG',
        'burkina_faso' => 'BF',
        'burundi' => 'BI',
        'cambodia' => 'KH',
        'cameroon' => 'CM',
        'canada' => 'CA',
        'cape_verde' => 'CV',
        'cayman_islands' => 'KY',
        'central_african_republic' => 'CF',
        'chad' => 'TD',
        'chile' => 'CL',
        'china' => 'CN',
        'christmas_island' => 'CX',
        'cocos_keeling_islands' => 'CC',
        'colombia' => 'CO',
        'comoros' => 'KM',
        'republic_of_the_congo' => 'CD',
        'congo' => 'CG',
        'cook_islands' => 'CK',
        'costa_rica' => 'CR',
        'ivory_coast' => 'CI',
        'croatia' => 'HR',
        'cuba' => 'CU',
        'curacao' => 'CW',
        'cyprus' => 'CY',
        'czech_republic' => 'CZ',
        'denmark' => 'DK',
        'djibouti' => 'DJ',
        'dominica' => 'DM',
        'dominican_republic' => 'DO',
        'ecuador' => 'EC',
        'egypt' => 'EG',
        'el_salvador' => 'SV',
        'equatorial_guinea' => 'GQ',
        'eritrea' => 'ER',
        'estonia' => 'EE',
        'ethiopia' => 'ET',
        'falkland_islands' => 'FK',
        'faroe_islands' => 'FO',
        'fiji' => 'FJ',
        'finland' => 'FI',
        'france' => 'FR',
        'french_guiana' => 'GF',
        'french_polynesia' => 'PF',
        'french_southern_territories' => 'TF',
        'gabon' => 'GA',
        'gambia' => 'GM',
        'georgia' => 'GE',
        'germany' => 'DE',
        'ghana' => 'GH',
        'gibraltar' => 'GI',
        'greece' => 'GR',
        'greenland' => 'GL',
        'grenada' => 'GD',
        'guadeloupe' => 'GP',
        'guam' => 'GU',
        'guatemala' => 'GT',
        'guernsey' => 'GG',
        'guinea' => 'GN',
        'guinea_bissau' => 'GW',
        'guyana' => 'GY',
        'haiti' => 'HT',
        'heard_and_mc_donald_islands' => 'HM',
        'honduras' => 'HN',
        'hong_kong' => 'HK',
        'hungary' => 'HU',
        'iceland' => 'IS',
        'india' => 'IN',
        'indonesia' => 'ID',
        'iran' => 'IR',
        'iraq' => 'IQ',
        'ireland' => 'IE',
        'isle_of_man' => 'IM',
        'israel' => 'IL',
        'italy' => 'IT',
        'jamaica' => 'JM',
        'japan' => 'JP',
        'jersey' => 'JE',
        'hashemite_kingdom_of_jordan' => 'JO',
        'kazakhstan' => 'KZ',
        'kenya' => 'KE',
        'kiribati' => 'KI',
        'north_korea' => 'KP',
        'republic_of_korea' => 'KR',
        'kuwait' => 'KW',
        'kyrgyzstan' => 'KG',
        'laos' => 'LA',
        'latvia' => 'LV',
        'lebanon' => 'LB',
        'lesotho' => 'LS',
        'liberia' => 'LR',
        'libya' => 'LY',
        'liechtenstein' => 'LI',
        'republic_of_lithuania' => 'LT',
        'lithuania' => 'LT',
        'luxembourg' => 'LU',
        'macao' => 'MO',
        'macedonia' => 'MK',
        'madagascar' => 'MG',
        'malawi' => 'MW',
        'malaysia' => 'MY',
        'maldives' => 'MV',
        'mali' => 'ML',
        'malta' => 'MT',
        'marshall_islands' => 'MH',
        'saint_martin' => 'MQ',
        'mauritania' => 'MR',
        'mauritius' => 'MU',
        'mayotte' => 'YT',
        'mexico' => 'MX',
        'federated_states_of_micronesia' => 'FM',
        'republic_of_moldova' => 'MD',
        'monaco' => 'MC',
        'mongolia' => 'MN',
        'montenegro' => 'ME',
        'montserrat' => 'MS',
        'morocco' => 'MA',
        'mozambique' => 'MZ',
        'myanmar_burma' => 'MM',
        'namibia' => 'NA',
        'nauru' => 'NR',
        'nepal' => 'NP',
        'netherlands' => 'NL',
        'netherlands_antilles' => 'AN',
        'new_caledonia' => 'NC',
        'new_zealand' => 'NZ',
        'nicaragua' => 'NI',
        'niger' => 'NE',
        'nigeria' => 'NG',
        'niue' => 'NU',
        'norfolk_island' => 'NF',
        'northern_mariana_islands' => 'MP',
        'norway' => 'NO',
        'oman' => 'OM',
        'pakistan' => 'PK',
        'palau' => 'PW',
        'palestine' => 'PS',
        'panama' => 'PA',
        'papua_new_guinea' => 'PG',
        'paraguay' => 'PY',
        'peru' => 'PE',
        'philippines' => 'PH',
        'pitcairn_islands' => 'PN',
        'poland' => 'PL',
        'portugal' => 'PT',
        'puerto_rico' => 'PR',
        'qatar' => 'QA',
        'reunion' => 'RE',
        'romania' => 'RO',
        'russia' => 'RU',
        'rwanda' => 'RW',
        'saint_barthelemy' => 'BL',
        'saint_helena' => 'SH',
        'saint_kitts_and_nevis' => 'KN',
        'saint_lucia' => 'LC',
        'saint_pierre_and_miquelon' => 'PM',
        'saint_vincent_and_the_grenadines' => 'VC',
        'samoa' => 'WS',
        'san_marino' => 'SM',
        'sao_tome_and_principe' => 'ST',
        'saudi_arabia' => 'SA',
        'senegal' => 'SN',
        'serbia' => 'RS',
        'seychelles' => 'SC',
        'sierra_leone' => 'SL',
        'singapore' => 'SG',
        'sint_maarten' => 'SX',
        'slovak_republic' => 'SK',
        'slovenia' => 'SI',
        'solomon_islands' => 'SB',
        'somalia' => 'SO',
        'south_africa' => 'ZA',
        'south_georgia_and_the_south_sandwich_islands' => 'GS',
        'south_sudan' => 'SS',
        'spain' => 'ES',
        'sri_lanka' => 'LK',
        'sudan' => 'SD',
        'suriname' => 'SR',
        'svalbard_and_jan_mayen' => 'SJ',
        'swaziland' => 'SZ',
        'sweden' => 'SE',
        'switzerland' => 'CH',
        'syria' => 'SY',
        'taiwan' => 'TW',
        'tajikistan' => 'TJ',
        'tanzania' => 'TZ',
        'thailand' => 'TH',
        'east_timor' => 'TL',
        'togo' => 'TG',
        'tokelau' => 'TK',
        'tonga' => 'TO',
        'trinidad_and_tobago' => 'TT',
        'tunisia' => 'TN',
        'turkey' => 'TR',
        'turkmenistan' => 'TM',
        'turks_and_caicos_islands' => 'TC',
        'tuvalu' => 'TV',
        'uganda' => 'UG',
        'ukraine' => 'UA',
        'united_arab_emirates' => 'AE',
        'united_kingdom' => 'GB',
        'united_states' => 'US',
        'u_s_minor_outlying_islands' => 'UM',
        'uruguay' => 'UY',
        'uzbekistan' => 'UZ',
        'vanuatu' => 'VU',
        'vatican_city' => 'VA',
        'venezuela' => 'VE',
        'vietnam' => 'VN',
        'u_s_virgin_islands' => 'VI',
        'wallis_and_futuna' => 'WF',
        'western_sahara' => 'EH',
        'yemen' => 'YE',
        'zambia' => 'ZM',
        'zimbabwe' => 'ZW'
    );
}
