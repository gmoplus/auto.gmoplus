<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLXMLIMPORT.CLASS.PHP
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

use Flynax\Utils\ListingMedia;
use Flynax\Utils\Valid;
use Flynax\Component\ListingImageUploader;

class rlXmlImport
{
    public $feed;
    public $print_progress = ''; //html, plain - set plain to print in shell mode
    public $xml_file;

    public $debug = true;
    public $time_stats = array();
    public $mapping_use_db = false;//todo

    public $modulesToDebug = array('import',
                                //'importListing',
                                'copyFile',
                                'getBasicData',
                                'getMapping',
                                'copyPhotos',
                                //'copyASinglePhoto',
                                'afterImportCompleted'
                                );

    public $debug_by_data = array();

    protected $listing_base_data = array();
    protected $listing_default_data = array();
    protected $listing_reference_data = array();
    protected $cat_fields = array();
    protected $loc_fields = array();

    public $statistics = array(); //inserted, updated, deleted, error

    private $start_time = '';

    function __construct()
    {
        global $reefless, $rlXmlMapping;

        if (defined('REALM') && REALM == 'admin') {
            $this->print_progress = 'html';
        }

        $this->beforeImportStarted();

        if ($GLOBALS['config']['membership_module']) {
            $reefless->loadClass('Account');
            $reefless->loadClass('MembershipPlan');
        }

        $reefless->loadClass('Resize');
        $reefless->loadClass('Listings');
        $reefless->loadClass('Crop');
        $reefless->loadClass('Cache');

        $GLOBALS['rlCommon']->getInstalledPluginsList();
    }

    /**
    * Import - main function to run import
    */
    public function import()
    {
        global $lang, $rlXmlMapping, $config;

        $this->start_time = $GLOBALS['rlDb']->getRow("SELECT UNIX_TIMESTAMP(NOW()) as `start`", 'start');

        $this->xmlDebug('import', 'start');
        $this->xmlLogger($lang['xf_progress_start'], 'notice');

        $GLOBALS['reefless']->loadClass('XmlMapping', null, 'xmlFeeds');
        $rlXmlMapping->format = $this->feed['Format'];
        $rlXmlMapping->loadBasicMapping('import');
        $rlXmlMapping->getMultifieldRelatedFileds();

        if (!$this->copyFile()) {
            return;
        }

        $this->getBasicData();
        $this->loadDefaultData();

        if ($config['membership_module']) {
            $this->xmlLogger($lang['xf_membership_intro_notice'], 'warning');
            if ($config['allow_listing_plans']) {
                $this->xmlLogger($lang['xf_membership_intro_notice2'], 'warning');
            }
        }

        switch ($this->feed['Type']) {
            case 'xml':
                $this->xmlParser();
                break;

            case 'json':
                $this->jsonParser();
                break;
        }

        if ($this->statistics['error']) {
            $this->xmlLogger($lang['xf_progress_fail'] . ';' . $this->statistics['error'], 'error');
        } elseif (!$GLOBALS['rlXmlMapping']->mapping) {
            $this->xmlLogger($lang['xf_progress_mapping'], 'notice');
        } else {
            $this->xmlLogger($lang['xf_progress_imported'], 'notice');
        }

        $this->handleRemovedAds();
        $this->afterImportCompleted();
        $this->saveStatistics();
        $this->xmlDebug('import', 'end');
    }

    /**
    * GetBasicData - function to prepare initial listing data
    */
    protected function getBasicData()
    {
        $this->xmlDebug('getBasicData', 'start');

        $this->listing_base_data = [
            'Date'             => 'NOW()',
            'Pay_date'         => 'NOW()',
            'Plan_ID'          => $this->feed['Plan_ID'],
            'Category_ID'      => $this->feed['Default_category'],
            'Account_ID'       => $this->feed['Account_ID'],
            'Status'           => $this->feed['Listings_status'] ? $this->feed['Listings_status'] : 'active'
        ];

        $this->listing_reference_data = [
            'xml_feed_key'     => $this->feed['Feed'],
            'xml_last_updated' => 'NOW()'
        ];

        $this->xmlDebug('getBasicData', 'end');
    }

    /**
    * LoadDefaultData - function to load mapping defaults
    */
    protected function loadDefaultData()
    {
        $sql = "SELECT * FROM `{db_prefix}xml_mapping` WHERE `Default` != '' AND `Format` = '" . $this->feed['Format'] . "'";
        $defaults = $GLOBALS['rlDb']->getAll($sql);
        foreach ($defaults as $k => $v) {
            $this->listing_default_data[$v['Data_remote']] = $v['Default'];
        }
    }

    /**
     * Converts an JSON file to array and executes importListing function for each iteration.
     *
     * @since 3.4.0
     */
    protected function jsonParser()
    {
        if ($this->feed['Xpath'] == '/') {
            $file = fopen($this->xml_file, 'r');

            if (!$file) {
                $this->statistics['error'] = $GLOBALS['lang']['xf_progress_file_fail'];
                return false;
            }

            while (($line = fgets($file)) !== false) {
                $listing = json_decode($line, true);

                if (is_array($listing)) {
                    $this->importListing($listing);
                } else {
                    $this->statistics['error'] = $GLOBALS['lang']['xf_progress_xpath_fail'];
                    break;
                }
            }

            fclose($file);
        } else {
            $data = file_get_contents($this->xml_file);
            $xpath = explode('/', $this->feed['Xpath']);
            $index = array_shift($xpath);
            $index = $index === 'array' ? null : $index;
            $listings = [];

            if (null !== $listings = json_decode($data, true)) {
                while ($index && is_array($listings[$index])) {
                    $listings = $listings[$index];
                    $index = array_shift($xpath);
                }

                if ($listings) {
                    foreach ($listings as $listing) {
                        $this->adaptJsonArray($listing);
                        $this->importListing($listing);
                    }
                } else {
                    $this->statistics['error'] = $GLOBALS['lang']['xf_progress_xpath_fail'];
                }
            } else {
                $this->statistics['error'] = $GLOBALS['lang']['xf_progress_file_fail'];
            }
        }
    }

    /**
     * Convert array to string indexes
     *
     * @since 3.4.0
     *
     * @param array &$listing - Listing data
     */
    private function adaptJsonArray(&$listing)
    {
        $tmp = [];

        foreach ($listing as $key => &$value) {
            if (is_array($value)) {
                // Multiple items mode
                if (isset($value[0]) && is_array($value[0])) {
                    foreach ($value as $items) {
                        foreach ($items as $item_key => $item_value) {
                            $tmp[$key . '_' . $item_key][] = $item_value;
                        }
                    }

                    unset($listing[$key]);
                }
                // Simple array mode
                elseif (key($value) !== 0) {
                    foreach ($value as $item_key => $item_value) {
                        $tmp[$key . '_' . $item_key] = $item_value;
                    }
                    unset($listing[$key]);
                }
            }
        }

        if ($tmp) {
            $this->adaptJsonArray($tmp);
        }

        $listing = array_merge($listing, $tmp);
    }

    /**
    * XmlParser - converts an XML file to array and executes importListing function for each iteration.
    */
    protected function xmlParser()
    {
        $reader = new XMLReader();

        if (!$reader->open($this->xml_file)) {
            $this->statistics['error'] = $GLOBALS['lang']['xf_progress_file_fail'];
            return false;
        }
        $xpath = explode('/', strtolower($this->feed['Xpath']));

        $last_path = array_slice($xpath, -1, 1);
        $last_path = $last_path[0];

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT && strtolower($reader->localName) == $last_path) {
                $listing = array();
                $xpath_correct = true;
                $parse = true;
            }

            if ($parse) {
                $method = $this->feed['New_parser'] === '1' ? 'nodeParserNew' : 'nodeParser';
                $listing = $this->$method($reader, $last_path);
            }

            if ($reader->nodeType == XMLReader::END_ELEMENT && strtolower($reader->localName) == $last_path) {
                $parse = false;

                $this->importListing($listing);

                if ($GLOBALS['import_completed']) {
                    break;
                }
            }
        }

        if (!$xpath_correct) {
            $this->statistics['error'] = $GLOBALS['lang']['xf_progress_xpath_fail'];
        }
    }

    /**
     * Read XML node attributes
     *
     * @since 3.4.0
     *
     * @param  object &$node      - XMLReader node
     * @param  array  &$data      - Current listing parsed data
     * @param  string $parentName - Parent element tag name
     * @return string             - Possible language code
     */
    private function readNodeAttrs(&$node, &$data, $parentName)
    {
        $lang_code = '';

        if ($node->hasAttributes) {
            while ($node->moveToNextAttribute()) {
                if ($node->value != '') {
                    $index = $parentName . '@' . $node->name;
                    $node_name = strtolower($node->name);

                    if (in_array(strtolower($node_name), ['lang', 'language', 'xml:lang'])) {
                        $lang_code = $node->value;
                    }

                    if ($data[$index]) {
                        if (!is_array($data[$index])) {
                            $data[$index] = [$data[$index]];
                        }
                        $data[$index][] = $node->value;
                    } else {
                        $data[$index] = $node->value;
                    }
                }
            }

            $node->moveToElement();
        }

        return $lang_code;
    }

    /**
     * New xml mode parser method
     *
     * @since 3.4.0
     *
     * @param  object &$node       - XMLReader node
     * @param  string $parent_name - Parent element tag name
     * @return array               - Listing data
     * @throws Exception
     */
    private function nodeParserNew(&$node, $parent_name)
    {
        if (!$node || $node->nodeType != XMLReader::ELEMENT) {
            throw new Exception('No XML node object passed or given node is not XML Element');
        }

        $parent_names = [$parent_name];
        $data = [];
        $lang_code = false;

        // Read target element attributes
        $this->readNodeAttrs($node, $data, $parent_name);

        while ($node->read()) {
            if ($node->nodeType == XMLReader::END_ELEMENT) {
                array_pop($parent_names);
            }
            // Read current element attributes
            elseif ($node->nodeType == XMLReader::ELEMENT) {
                $parent_names[] = $node->name;
                $lang_code = $this->readNodeAttrs($node, $data, implode('@', $parent_names));
            }
            // Read current element text or cdata value
            elseif (in_array($node->nodeType, [XMLReader::TEXT, XMLReader::CDATA])) {
                if ('' != $value = trim($node->value)) {
                    $set_parent = implode('@', $parent_names);

                    if ($lang_code) {
                        $set_parent = $set_parent . '@' . $lang_code;
                    }

                    if ($data[$set_parent]) {
                        if (!is_array($data[$set_parent])) {
                            $data[$set_parent] = [$data[$set_parent]];
                        }
                        $data[$set_parent][] = $value;
                    } else {
                        $data[$set_parent] = $value;
                    }
                }
            }

            if ($node->isEmptyElement) {
                array_pop($parent_names);
            }

            if ($parent_name == strtolower($node->localName)) {
                break;
            }
        }

        return $data;
    }

    /**
    * nodeParser - function to parse xml
    */
    private function nodeParser(&$node, $parent_name)
    {
        $data = $names = array();
        $names[] = strtolower($parent_name);

        /* read attributes of a parent node */
        if ($node->hasAttributes) {
            while ($node->moveToNextAttribute()) {
                if ($node->value) {
                    $data[$parent_name . '@' . $node->name] = $node->value;
                }
            }
            $node->moveToElement();
        }
        /* read attributes of a parent node end */

        while ($node->read()) {
            if ($node->nodeType == XMLReader::ELEMENT && !$node->isEmptyElement) {
                if (!in_array($node->localName, $names)) {
                    /* build node name depending on the current cursor position */
                    $names[] = $node->localName;
                    $node_name = strtolower(implode("_", $names));
                }
            }

            if ($node->hasAttributes && $node->nodeType == XMLReader::ELEMENT) {
                $attrs = array();
                while ($node->moveToNextAttribute()) {
                    if ($node->value) {
                        $attrs[$node->name] = $node->value;
                    }
                }
                $node->moveToElement();
            } else {
                $attrs = array();
            }

            if ($attrs['name']) {
                $node_name = $node_name . '@' . $attrs['name'];
            }

            if ($node->hasValue && trim($node->value)) {
                /* add value */
                $value = array();
                $value['value'] = trim($node->value);
                $value['attrs'] = $attrs;
                unset($attrs);

                /* transform item to first element of array if there is more items to collect */
                if ($data[$node_name] && is_string($data[$node_name])) {
                    $tmp = $data[$node_name];
                    $data[$node_name] = array();
                    $data[$node_name][] = $tmp;

                    foreach ($prev_attrs as $k => $v) {
                        unset($data[$node_name . '@' . $k]);
                    }
                }
                /* transform item to array end */

                if (is_array($data[$node_name])) {
                    $data[$node_name][] = $value['value'];
                } else {
                    foreach ($value['attrs'] as $ak => $av) {
                        $data[$node_name . '@' . $ak] = $av;
                    }
                    $data[$node_name] = $value['value'];
                }
                $prev_attrs = $value['attrs'];
            } elseif ($attrs) {
                /* add attributes if attributes without value */
                foreach ($attrs as $atk => $atv) {
                    if ($data[$node_name . '@' . $atk] && is_string($data[$node_name . '@' . $atk])) {
                        $tmp = $data[$node_name . '@' . $atk];
                        $data[$node_name . '@' . $atk] = array();
                        $data[$node_name . '@' . $atk][] = $tmp;
                    }

                    if (is_array($data[$node_name . '@' . $atk])) {
                        $data[$node_name . '@' . $atk][] = $atv;
                    } else {
                        $data[$node_name . '@' . $atk] = $atv;
                    }
                }
            }

            if ($node->nodeType == XMLReader::END_ELEMENT) {
                $names = array_slice($names, 0, -1);
            }

            if ($node->nodeType == XMLReader::END_ELEMENT && $parent_name == strtolower($node->localName)) {
                return $data;
            }
        }

        return $data;
    }

    /**
    * ImportListing - adapts data parsed from xml to how flynax stores it and insert or update in db
    *
    * @param array $listing - listing data array
    */
    protected function importListing($listing)
    {
        global $config, $lang, $rlDb, $rlXmlMapping, $rlListings;

        $this->xmlDebug('importListing', 'start');
        $this->xmlLogger(null, 'divider');

        if ($this->feed['Skip_imported']) {
            $xml_ref_field = array_search('xml_ref', $rlXmlMapping->mapping);

            if ($xml_ref_field) {
                $where = "`xml_feed_key` = '" . $this->feed['Key'] . "' AND `xml_ref` = '" . $listing[$xml_ref_field] . "'";

                $ex_listing_id = $rlDb->getOne('Listing_ID', $where, 'xml_listings');
                $mess = str_replace(array('{xml_ref}', '{listing_id}'), array($listing[$xml_ref_field], $ex_listing_id), $lang['xf_progress_skipped']);

                if ($ex_listing_id) {
                    if ($this->feed['Removed_ads_action']) {
                        $sql = "
                            UPDATE `{db_prefix}xml_listings` SET `xml_last_updated` = NOW()
                            WHERE `Listing_ID` = {$ex_listing_id}
                        ";
                        $rlDb->query($sql);
                    }

                    $this->xmlLogger($mess);

                    return false;
                }

                if ($this->statistics['imported'] == $this->feed['Import_limit']) {
                    $mess = str_replace('{limit}', $this->feed['Import_limit'], $lang['xf_progress_limit_reached']);
                    $this->xmlLogger($mess);

                    if (!$this->feed['Removed_ads_action']) {
                    $GLOBALS['import_completed'] = true;
                    }

                    return false;
                }

                $this->statistics['imported']++;
            }
        }

        $explode_fields = $rlXmlMapping->mapping ? array_keys($rlXmlMapping->mapping, 'sys_explode_comma') : [];
        foreach ($explode_fields as $fk => $explode_field) {
            $tmp = explode(',', $listing[$explode_field]);
            foreach($tmp as $k => $v) {
                $listing[$explode_field . '_' . $k] = trim($v);
            }
            unset($listing[$explode_field]);
        }

        $duplicate_fields = $rlXmlMapping->mapping ? array_keys($rlXmlMapping->mapping, 'sys_duplicate') : [];
        foreach ($duplicate_fields as $fk => $dup_field) {
            if ($listing[$dup_field]) {
                $listing[$dup_field . '_copy1'] = $listing[$dup_field];
                $listing[$dup_field . '_copy2'] = $listing[$dup_field];
                unset($listing[$dup_field]);
            }
        }

        if ($this->listing_default_data) {
            $listing = array_merge($listing, $this->listing_default_data);
        }

        $data = $this->listing_base_data;

        if (!$listing) {
            $this->statistics['error'] = $lang['xf_progress_no_data'];
            $this->xmlLogger($lang['xf_progress_no_data'], 'error');

            //return false; Commented by John
        }

        ksort($listing);

        $fields_mapping = array();
        $mixed_fields = array();
        foreach ($listing as $xml_field => $xml_value) {
            $flynax_field = $fields_mapping[$xml_field] = $rlXmlMapping->getMappingItem($xml_field, $xml_value, null, 'field');

            if ($flynax_field) {
                $system_field = substr($flynax_field, 0,4) == 'sys_' ? substr($flynax_field, 4) : false;
                $unit_field = false;
                if (substr($flynax_field, -5) == '_unit') {
                    $unit_field = $flynax_field;
                    $flynax_field = substr($flynax_field, 0, -5);
                }
                // move this upper
                if (substr($flynax_field, 0, 4) == 'sys_') {
                    $flynax_field = substr($flynax_field, 4);
                }

                if ($system_field) {
                    $flynax_value = $this->adaptSystemField($flynax_field, $xml_field, $xml_value, $listing);
                } else {
                    $flynax_value = $this->adaptListingField($flynax_field, $xml_field, $xml_value, $listing, $unit_field, $data);
                }

                if ($flynax_value != '') {
                    if (preg_match('#mld_(.*)_([a-z]{2})#', $flynax_field, $match)) {
                        $data[$match[1]] .= $flynax_value;
                    } elseif ($flynax_field == 'dealer_id') {
                        $data['Account_ID'] = $flynax_value;
                    } elseif ($flynax_field == 'status') {
                        $data['Status'] = $flynax_value;
                    } elseif ($flynax_field == 'date') {
                        $data['Date'] = $flynax_value;
                    } elseif (in_array($rlXmlMapping->fields_info[$flynax_field]['Type'], array('price', 'mixed'))) {
                        if (!in_array($flynax_field, $mixed_fields)) {
                            array_push($mixed_fields, $flynax_field);
                        }

                        if ($unit_field) {
                            $data[$flynax_field]['unit'] = $flynax_value;
                        } else {
                            $data[$flynax_field]['amount'] = $flynax_value;
                        }
                    } elseif ($data[$flynax_field] && $system_field) {
                        switch($system_field) {
                            case 'photos':
                                if (!is_array($data[$flynax_field])) {
                                    $tmp = $data[$flynax_field];
                                    $data[$flynax_field] = array();
                                    $data[$flynax_field][] = $tmp;
                                }

                                if (is_array($flynax_value)) {
                                    $data[$flynax_field] = array_merge($data[$flynax_field], $flynax_value);
                                } else {
                                    $data[$flynax_field][] = $flynax_value;
                                }
                                break;
                            case 'video':
                            break;
                        }
                    } elseif ($data[$flynax_field]) {
                        switch ($rlXmlMapping->fields_info[$flynax_field]['Type']) {
                            case 'text':
                                $data[$flynax_field] .= ' ' . $data[$flynax_field];
                                break;
                            case 'textarea':
                                // if($multilingual)
                                // {}todo
                                // break;
                            case 'checkbox':
                                $data[$flynax_field] .=  ',' . $data[$flynax_field];
                            default:
                                $data[$flynax_field] .= $data[$flynax_field];
                            break;
                        }
                    } else {
                        $data[$flynax_field] = $flynax_value;
                    }
                }
            }
        }

        foreach ($mixed_fields as $mixed_field) {
            if (!$data[$mixed_field]['unit']) {
                $finfo = $rlXmlMapping->getFieldInfo($mixed_field);
                $finfo['Condition'] = !$finfo['Condition'] && $finfo['Type'] == 'price' ? 'currency' : $finfo['Condition'];

                if ($finfo['Condition']) {
                    reset($rlXmlMapping->data_formats_mapping[$finfo['Condition']]);
                    $default_unit = current($rlXmlMapping->data_formats_mapping[$finfo['Condition']]);
                } else {
                    $default_unit = current($rlXmlMapping->listing_fields_mapping[$finfo['Key']]);//tocheck
                }

                if ($default_unit) {
                    $data[$mixed_field]['unit'] = $default_unit;
                }
            }

            if (is_array($data[$mixed_field]) && $data[$mixed_field]['unit'] && $data[$mixed_field]['amount']) {
                $data[$mixed_field] = $data[$mixed_field]['amount'] . "|" . $data[$mixed_field]['unit'];
            } else {
                unset($data[$mixed_field]);
            }
        }

        $this->defineMultiFieldValues($listing, $fields_mapping, $data);

        if ($category_id = $this->defineCategory($listing)) {
            $data['Category_ID'] = (int)$category_id;
        }
        $data['Account_ID'] = (int)$data['Account_ID'];

        $photos = $data['photos'] ? array_filter(array_unique($data['photos'])) : [];
        unset($data['photos']);

        $videos = $data['video'] ? array_filter(array_unique($data['video'])) : [];
        unset($data['video']);

        $data = array_filter($data, 'strlen');

        if (!$data['Account_ID']) {
            $this->statistics['error'] = $lang['xf_progress_no_account'];
            $this->xmlLogger($lang['xf_progress_no_account'], 'error');

            return false;
        } elseif (!$data['xml_ref']) {
            $this->statistics['error'] = $lang['xf_progress_no_ref'];
            $this->xmlLogger($lang['xf_progress_no_ref'], 'error');

            return false;
        }

        $listing_id = $rlDb->getOne('Listing_ID', "`xml_ref` = '{$data['xml_ref']}' AND `xml_feed_key` = '{$this->feed['Feed']}'", 'xml_listings');

        $this->defineLocation($data, $listing);

        /* debugging module - beta */
        if ($_GET['debug_local_field']) {
            $this->debug_by_data['flynax_field'] = $_GET['debug_local_field'];
        }
        if ($_GET['debug_listing_id']) {
            $this->debug_by_data['listing_id'] = $_GET['debug_listing_id'];
        }
        if ($_GET['debug_xml_ref']) {
            $this->debug_by_data['xml_ref'] = $_GET['debug_xml_ref'];
        }

        if ($this->debug_by_data) {
            if ($this->debug_by_data['xml_ref'] && $this->debug_by_data['xml_ref'] == $data['xml_ref']
                || $listing_id && $this->debug_by_data['listing_id'] && $this->debug_by_data['listing_id'] == $listing_id)
            {
                $this->xmlLogger("XML REF: " . $data['xml_ref']."; LISTING ID: ".$listing_id, "data_debug");
                $this->xmlLogger("XML LISTING: <pre>" . print_r($listing, true) . "</pre>", "data_debug");
                $this->xmlLogger("ADAPTED LISTING: <pre>" . print_r($data, true) . "</pre>", "data_debug");
                $this->xmlLogger("PHOTOS: <pre>" . print_r($photos, true)."</pre>", "data_debug");

            } elseif ($this->debug_by_data['flynax_field']) {
                $this->xmlLogger($this->debug_by_data['flynax_field'] . "<pre>" . var_dump($data[$this->debug_by_data['flynax_field']])."</pre>", "data_debug");
            } else {
                $this->xmlLogger('skipped for debug purposes');
                return false;
            }
        }
        /* end of debugging module */

        $action = $data['xml_ref'] && $listing_id ? 'update' : 'insert';

        $this->listing_reference_data['xml_ref'] = $data['xml_ref'];
        $this->listing_reference_data['xml_back_url'] = $data['xml_back_url'];

        unset($data['xml_ref'], $data['xml_back_url']);

        if (!$this->planUsingControl($data, $action)) {
            return false; //terminate import in case planControl does not allow to continue
        }

        $this->beforeItemImported($data, $action);

        $rlDb->rlAllowHTML = true;

        if ($action == 'update') {
            $update['fields'] = $data;
            $update['where']['ID'] = $listing_id;

            $rlDb->updateOne($update, 'listings');
            $this->statistics['updated']++;

            $update_reference = [
                'fields' => $this->listing_reference_data,
                'where' => ['Listing_ID' => $listing_id]
            ];
            $rlDb->updateOne($update_reference, 'xml_listings');
        } else {
            $rlDb->insertOne($data, 'listings');

            $listing_id = $rlDb->insertID();
            $this->statistics['inserted']++;

            $this->listing_reference_data['Listing_ID'] = $listing_id;

            $rlDb->insertOne($this->listing_reference_data, 'xml_listings');
        }

        $this->afterItemImported($data, $listing_id, $action);

        if ($photos || $action == 'update') {
            $this->copyListingPhotos($photos, $listing_id, $action);
        }

        if ($videos && $action == 'insert') {
            $this->copyVideos($videos, $listing_id);
        }

        $mess = $action == 'update' ? $lang['xf_progress_ad_updated'] : $lang['xf_progress_ad_inserted'];
        $mess = str_replace('[id]', "<b>".$listing_id."</b>", $mess);

        $this->xmlLogger($mess, 'notice');
        $this->xmlDebug('importListing', 'end');
    }

    /**
    * Plan using counts control
    *
    * Function designed to control memberships and listing packages counting
    * It decrease number of related to account membership or listing package
    * And indecates if a limit is reached.
    *
    * @param array  $data   - listing data for database insert/update
    * @param string $action - current action, insert or update
    *
    * @return bool          - true if import allowed
    **/
    protected function planUsingControl(&$data, $action = 'insert') {
        global $config, $rlDb, $lang;

        if ($action == 'update') {
            //unset plan variables for not updating already imported listing plan data
            unset($data['Plan_ID']);
            unset($data['Pay_date']);
            unset($data['Plan_type']);
            unset($data['Date']);
            unset($data['Status']);

            return true;
        } else {

            if ($config['membership_module']) {
                $account_info = $rlDb->fetch('*', array('ID' => $data['Account_ID']), null, 1, 'accounts', 'row');

                if (!$account_info['Plan_ID'] && !$config['allow_listing_plans']) {
                    $this->xmlLogger($lang['xf_mbs_plan_not_assigned'], 'error');
                    $GLOBALS['import_completed'] = true;

                    return false;
                }

                $mp_plan_info = $GLOBALS['rlMembershipPlan']->getPlan($account_info['Plan_ID']);

                $data['Plan_ID']   = $mp_plan_info['ID'];
                $data['Pay_date']  = $account_info['Pay_date'];
                $data['Plan_type'] = 'account';

                if (!$mp_plan_info['Services']['add_listing']) {
                    $this->xmlLogger($lang['xf_mbs_plan_no_add_service'], 'error');
                    $GLOBALS['import_completed'] = true;

                    return false;
                }

                $allow_import = $this->packageControl($data['Account_ID'], $mp_plan_info, $data);

                if (!$allow_import && $config['allow_listing_plans']) {
                    $this->xmlLogger($lang['xf_mbs_plan_exceded_assigning_default'], 'warning');
                } elseif (!$allow_import) {
                    $this->xmlLogger($lang['xf_mbs_plan_exceded'], 'warning');
                    $GLOBALS['import_completed'] = true;

                    return false;
                }
            } else {
                //listing packages counting
                $plan_info = $rlDb->fetch('*', array('ID'=>$this->listing_base_data['Plan_ID']), null, null, 'listing_plans', 'row');
                if ($plan_info['Type'] == 'package') {
                    $allow_import = $this->packageControl($data['Account_ID'], $plan_info, $data);

                    if (!$allow_import) {
                        $this->xmlLogger($lang['xf_listing_package_exceded'], 'warning');
                        $GLOBALS['import_completed'] = true;

                        return false;
                    }
                }
            }

            //allow import with default params, when there is nothing to adjust
            return true;
        }
    }

    /**
    * Package controlling
    *
    * Function designed to check and update listing_packages table (Plans Using manager)
    *
    * @param int   $account_id   - account id
    * @param array $plan_info    - listing data array (from xml)
    * @param array $data         - listing data for database insert/update
    *
    * @return bool                - true if import allowed
    */
    protected function packageControl($account_id, $plan_info, &$data)
    {
        global $rlDb;

        $existing_package = $rlDb->fetch(
            '*',
            array('Account_ID' => $account_id, 'Plan_ID' => $plan_info['ID']),
            null,
            null,
            'listing_packages',
            'row'
        );

        if (!$existing_package) {
            $existing_package = array();

            $existing_package['Account_ID'] = $account_id;
            $existing_package['Plan_ID'] = $plan_info['ID'];
            $existing_package['Listings_remains'] = $plan_info['Listing_number'];
            $existing_package['Standard_remains'] = $plan_info['Standard_listings'];
            $existing_package['Featured_remains'] = $plan_info['Featured_listings'];

            $existing_package['Account_ID'] = $account_id;
            $existing_package['Plan_ID'] = $plan_info['ID'];
            $existing_package['Type'] = 'account';
            $existing_package['Date'] = 'NOW()';

            $existing_package['IP'] = $GLOBALS['reefless']->getClientIpAddress();

            $rlDb->insertOne($existing_package, 'listing_packages');
        }

        $account_info = $rlDb->fetch('*', array('ID'=>$account_id), null, null, 'accounts', 'row');

        // unlimited plans
        if ($plan_info['Listing_number'] == 0) {
            return true;
        }

        if ($plan_info['Advanced_mode']) {
            $mess = str_replace(
                array('{remains}', '{featured}', '{standart}', '{username}'),
                array(
                    $existing_package['Listings_remains'],
                    $existing_package['Featured_remains'],
                    $existing_package['Standard_remains'],
                    $account_info['Username']
                ),
                $GLOBALS['lang']['xf_package_stats_advanced']
            );
        } else {
            $mess = str_replace(
                array('{remains}', '{username}'),
                array(
                    $existing_package['Listings_remains'],
                    $account_info['Username']
                ),
                $GLOBALS['lang']['xf_package_stats']
            );
        }
        $this->xmlLogger($mess, 'data_debug');

        if ($existing_package['Listings_remains'] > 0) {
            $sql = "UPDATE `{db_prefix}listing_packages` SET `Listings_remains` = `Listings_remains` - 1 ";

            if ($plan_info['Featured_listing'] || $plan_info['Featured']) {
                if ($existing_package['Featured_remains'] > 0) {
                    $sql .= ", `Featured_remains` = `Featured_remains` - 1 ";

                    $data['Featured_date'] = 'NOW()';
                    $data['Featured_ID'] = $plan_info['ID'];
                } elseif ($existing_package['Standard_remains'] > 0) {
                    $sql .= ", `Standard_remains` = `Standard_remains` - 1 ";
                }
            }

            $sql .="WHERE `Account_ID` = {$account_id} AND `Plan_ID` = {$plan_info['ID']}";

            $rlDb->query($sql);

            return true;
        } elseif (!$existing_package['Listings_remains']) {
            return false;
        }
    }

    /**
    * DefineLocation
    *
    * Fetches latitude and longitude from google by location information
    * Do geocode call only if listing is new or listing exists but loc data is empty
    *
    * @param array $data       - adapted listing data array (flynax db)
    * @param array $listing    - listing data array (from xml)
    */
    protected function defineLocation(&$data, $listing)
    {
        if (!$this->loc_fields) {
            return false;
        }

        if ($this->loc_fields && (!$data['loc_latitude'] || !$data['loc_longitude'])) {
            $fields_list = implode(',', $this->loc_fields);

            $sql = "SELECT * FROM `{db_prefix}listing_fields` ";
            $sql .="WHERE FIND_IN_SET(`Key`, '{$fields_list}')";
            $fields_info = $GLOBALS['rlDb']->getAll($sql, "Key");

            foreach ($this->loc_fields as $flynax_field) {
                if (isset($data[$flynax_field])) {
                    $location[] = $GLOBALS['rlCommon']->adaptValue($fields_info[$flynax_field], $data[$flynax_field]);
                }
            }
        }

        if ($location) {
            $GLOBALS['reefless']->geocodeLocation($location, $data);
        }
    }

    /**
    * DefineCategory - defines target category based on the source data
    *
    * @param array $listing - listing data array
    */
    protected function defineCategory($listing)
    {
        global $lang, $rlValid, $rlDb;

        $cat_fields = $this->cat_fields;
        ksort($cat_fields);

        if (!$cat_fields) {
            return false;
        }

        $sql = "SELECT * FROM `{db_prefix}xml_mapping` ";
        $sql .="WHERE `Data_local` = 'sys_category_0' AND `Format` = '" . $this->feed['Format'] . "' ";
        $cat_mapping_field = $rlDb->getRow($sql);

        if (!$cat_mapping_field) {
            $this->xmlLogger($lang['xf_no_categories_mapping'], 'error');
            return false;
        }

        $parents = array();
        $category = array();

        foreach ($cat_fields as $ckey => $cat_field) {
            if (!$listing[$cat_field]) {
                continue;
            }

            $sql = "SELECT `T1`.`ID`, `T2`.`Value`, `T1`.`Key` FROM `{db_prefix}categories` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON `T2`.`Key` = CONCAT('categories+name+', `T1`.`Key`) ";
            $sql .= "WHERE `T2`.`Value` = '" . $rlValid->xSql($listing[$cat_field]) . "' ";
            if ($this->feed['Listing_type']) {
                $sql .="AND `T1`.`Type` = '" . $this->feed['Listing_type'] . "' ";
            }
            if ($category) {
                $sql .= "AND `T1`.`Parent_ID` = {$category['ID']}";
            }
            $category = $rlDb->getRow($sql);

            if ($category) {
                $category_id = $category['ID'];
                $parents[] = $category;
            } else {
                $parent_id = $cat_mapping_field['ID'];

                foreach ($parents as $pk => $parent) {
                    $sql = "SELECT * FROM `{db_prefix}xml_mapping` ";
                    $sql .="WHERE `Data_remote` = '{$parent['Value']}' ";
                    $sql .="AND `Format` = '" . $this->feed['Format'] . "' ";
                    $sql .="AND `Parent_ID` = {$parent_id} ";

                    $parent_cat_mapping_item = $rlDb->getRow($sql);

                    if (!$parent_cat_mapping_item) {
                        $parent_cat_mapping_item['Parent_ID'] = $parent_id;
                        $parent_cat_mapping_item['Data_remote'] = $parent['Value'];
                        $parent_cat_mapping_item['Data_local'] = $parent['Key'];
                        $parent_cat_mapping_item['Format'] = $this->feed['Format'];

                        $rlDb->insertOne($parent_cat_mapping_item, 'xml_mapping');
                        $parent_cat_mapping_item['ID'] = $rlDb->insertID();
                    }
                    $parent_id = $parent_cat_mapping_item['ID'];
                }

                $sql = "SELECT `ID`, `Data_local` FROM `{db_prefix}xml_mapping` WHERE ";
                $sql .= "`Parent_ID` = '{$parent_id}' ";
                $sql .= "AND `Data_remote` = '" . $rlValid->xSql($listing[$cat_field]) . "' ";
                $sql .= "AND `Format` = '" . $this->feed['Format'] . "' ";

                $ex_mapping_item = $rlDb->getRow($sql);

                $tmp_parent = array();
                $tmp_parent['Value'] = $listing[$cat_field];
                $tmp_parent['Key'] = $ex_mapping_item['Data_local'];
                $parents[] = $tmp_parent;

                if (!$ex_mapping_item) {
                    $find = array('{listing_cat_data}', '{ckey}');
                    $replace = array($listing[$cat_field], $ckey);
                    $this->xmlLogger(str_replace($find, $replace, $lang['xf_progress_category_not_found']), 'notice');

                    $cat_map_insert['Parent_ID'] = $parent_id;
                    $cat_map_insert['Data_remote'] = $listing[$cat_field];
                    $cat_map_insert['Format'] = $this->feed['Format'];

                    if ($rlDb->insertOne($cat_map_insert, "xml_mapping")) {
                        $this->xmlLogger(str_replace($find, $replace, $lang['xf_progress_category_item_added']), 'notice');
                    }
                } elseif ($ex_mapping_item['Data_local']) {
                    $category = $rlDb->fetch('*', array('Key' => $ex_mapping_item['Data_local']), null, null, 'categories', 'row');
                    $category_id = $category['ID'];
                } else {
                    $p_out = $lang['categories'] . ' > ';
                    foreach ($parents as $key=>$parent) {
                        $p_out .= $parent['Value'] . ' > ';
                    }
                    $p_out = substr($p_out, 0, -2);

                    $find = array('{xml_field}', '{xml_value}');
                    $replace = array($p_out, $listing[$cat_field]);

                    $this->xmlLogger(str_replace($find, $replace, $lang['xf_progress_map_item_not_mapped']), 'notice');
                }
            }
        }

        return $category_id;
    }

    /**
    * AdaptSystemField - adapts a system field data to store locally
    *
    * @param array $listing - listing data array
    *
    * @param string $flynax_field - local field
    * @param string $xml_field    - source field
    * @param string $xml_value    - source data
    * @param array  $listing      - listing data
    * @return mixed               - adapted value
    */
    protected function adaptSystemField($flynax_field, $xml_field, $xml_value, &$listing)
    {
        global $rlXmlMapping;

        if (preg_match('/category_(\d)/', $flynax_field, $match)) {
            $this->cat_fields[$match[1]] = $xml_field;
        } elseif (preg_match('#mld_(.*)_([a-z]{2})#', $flynax_field, $match)) {

            $field_key = $match[1];
            $lang_code = $match[2];

            $xml_value = is_array($xml_value) ? $xml_value[0] : $xml_value;
            $flynax_value = "{|{$lang_code}|}". $xml_value ."{|/{$lang_code}|}";
        } elseif ($flynax_field == "photos") {
            return $this->extractPhotos($listing[$xml_field]);
        } elseif ($flynax_field == "video") {
            return $this->getVideos($listing[$xml_field]);
        } elseif ($flynax_field == "date") {
            $flynax_value = date('Y-m-d', strtotime($listing[$xml_field]));
        } elseif ($flynax_field == 'dealer_id') {
            /* TODO:create accounts automatically */
            $this->feed['Create_accounts'] = false;
            if ($this->feed['Create_accounts']) {
                $dealer_name_field = array_search('sys_dealer_name', $rlXmlMapping->mapping);
                $dealer_email_field = array_search('sys_dealer_email', $rlXmlMapping->mapping);

                $seller['name'] = $listing[$dealer_name_field];
                $seller['email'] = $listing[$dealer_email_field];
                unset($listing[$dealer_name_field]);
                unset($listing[$dealer_email_field]);

                $account_id = $this->createAccount($seller);
            } else {
                $account_id = (int) $rlXmlMapping->getMappingItem($xml_field, $xml_value, null, 'field_item', 'Data_local');
            }

            if ($account_id) {
                $username = $GLOBALS['rlDb']->getOne('Username', "`ID` = {$account_id}", 'accounts');
                $find = array('{field}', '{account}');
                $replace = array($xml_field, '<b>' . $username . '</b>');
                $mess = str_replace($find, $replace, $GLOBALS['lang']['xf_progress_account_assigned']);

                $this->xmlLogger($mess, 'notice');
                $flynax_value = $account_id;
            }
        } elseif ($flynax_field == 'status') {
            $flynax_value = $rlXmlMapping->getMappingItem($xml_field, $xml_value, null, 'field_item', 'Data_local');
        } elseif ($flynax_field == 'lang_codes') {
            //faceplate
        } else {
            $flynax_value = $xml_value;
        }

        return $flynax_value;
    }

    /**
     * Define multifield format key by value and parent key in new multifield data structure
     *
     * @since 3.4.0
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
     * @since 3.4.0
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
     * Add multifield values to the data array
     *
     * @since 3.4.0
     *
     * @param  array $listing - Listing data array from the feed
     * @param  array $mapping - Feed field key to Flynax field key mapping
     * @param  array &$data   - Listing data array to be imported
     */
    public function defineMultiFieldValues($listing, $mapping, &$data)
    {
        global $rlXmlMapping;

        asort($mapping);

        $skip_condition = null;
        $prev_key = null;

        foreach ($mapping as $feed_key => $system_key) {
            if ($system_key && in_array($system_key, $rlXmlMapping->multiFormatKeys) && $listing[$feed_key]) {
                $head_level = !strpos($system_key, '_level');
                $field_info = $rlXmlMapping->getFieldInfo($system_key);
                $parent_key = $head_level ? $field_info['Condition'] : $prev_key;

                // Skip next fields with the condition we didn't find value previously for
                if ($skip_condition && $skip_condition == $field_info['Condition']) {
                    continue;
                }

                $method_name = $rlXmlMapping->isNewMultiField ? 'getMultifieldKeyNew' : 'getMultifieldKeyOld';
                $prev_key = $data[$system_key] = $this->$method_name($listing[$feed_key], $parent_key);

                if (!$prev_key) {
                    // @todo - Add missing item to the mapping once the multifield plugin is offer the full support
                    // Add missing item to the mapping
                    // $data[$system_key] = $rlXmlMapping->getMappingItem($feed_key, $listing[$feed_key], null, 'field_item', 'Data_local');

                    if (!$data[$system_key]) {
                        $log_message = str_replace('{xml_value}', $listing[$feed_key], $GLOBALS['lang']['xf_progress_map_item_skipped']);
                        $this->xmlLogger($log_message, 'warning');

                        $skip_condition = $field_info['Condition'];
                    }
                }
            }
        }
    }

    /**
    * AdaptListingField - adapts a listing field data to store locally
    *
    * @param array $listing - listing data array
    *
    * @param string $flynax_field - local field
    * @param string $xml_field    - source field
    * @param string $xml_value    - source data
    * @param array  $listing      - listing data
    * @param string $unit_field   - a trigger defines is that is measurement field
    * @return mixed               - adapted value
    */
    protected function adaptListingField($flynax_field, $xml_field, $xml_value, &$listing, $unit_field, &$data)
    {
        global $rlDb, $rlXmlMapping;

        $xml_value_escaped = $GLOBALS['rlValid']->xSql($xml_value);

        $field_info = $rlXmlMapping->fields_info[$flynax_field];

        $mf_level = 0;

        if (in_array($field_info['Key'], $rlXmlMapping->multiFormatKeys)) {
            return false;
        }

        if (!$field_info || $xml_value == '') {
            return false;
        }

        if ($field_info['Type'] == 'price') {
            $field_info['Condition'] = 'currency';
        }
        if ($unit_field && in_array($field_info['Type'], array('price', 'mixed'))) {
            $field_info['Type'] = 'select';
        }

        if ($field_info['Map']) {
            $this->loc_fields[$xml_field] = $flynax_field;
        }

        switch ($field_info['Type']) {
            case "select":
            case "radio":
                if ($field_info['Condition'] == 'years') {
                    $out = $xml_value;
                } elseif ($field_info['Condition']) {
                    if ($this->mapping_use_db) {//todo
                        $sql = "SELECT `T1`.`Key` FROM `{db_prefix}data_formats` AS `T1` ";
                        $sql .="JOIN `{db_prefix}lang_keys` AS `T2` ON `T2`.`Key` = CONCAT('data_formats+name+', `T1`.`Key`)";
                        $sql .="WHERE `T2`.`Value` = '{$xml_value_escaped}'";

                        $out = $rlDb->getRow($sql, 'Key');
                    } else {
                        $out = $rlXmlMapping->data_formats_mapping[$field_info['Condition']][strtolower($xml_value)];
                    }
                } else {
                    if ($this->mapping_use_db) {//todo
                        $sql = "SELECT `T1`.`Key` FROM `{db_prefix}data_formats` AS `T1` ";
                        $sql .="JOIN `{db_prefix}lang_keys` AS `T2` ON `T2`.`Key` = CONCAT('data_formats+name+', `T1`.`Key`)";
                        $sql .="WHERE `T2`.`Value` = '{$xml_value_escaped}'";
                        $out = $this->getRow($sql, "Key");
                    } else {
                        $out = $rlXmlMapping->listing_fields_mapping[$field_info['Key']][strtolower($xml_value)];
                    }

                    if (!$field_info['Condition'] && strlen($out)>2) {
                        $out = substr($out, strlen($field_info['Key'])+1);
                    }
                }

                if (!$out && $mf_level == 0) {
                    $out = $rlXmlMapping->getMappingItem($xml_field, $xml_value, null, 'field_item', 'Data_local');
                }
            break;
            case 'checkbox':
                $out = '';
                if (!is_array($xml_value) && strpos($listing[$xml_field], ',')) {
                    $check_vals = explode(',', $listing[$xml_field]);
                } elseif (!is_array($xml_value) && strpos($listing[$xml_field], ';')) {
                    $check_vals = explode(';', $listing[$xml_field]);
                } elseif(is_array($xml_value)) {
                    $check_vals = $xml_value;
                } else {
                    $check_vals[] = $xml_value;
                }

                foreach($check_vals as $chK => $check_val) {
                    if ($field_info['Condition']) {
                        $local_val = $rlXmlMapping->data_formats_mapping[$field_info['Condition']][strtolower($check_val)];
                    } else {
                        $local_val = $rlXmlMapping->listing_fields_mapping[$field_info['Key']][strtolower($check_val)];
                    }

                    if (!$local_val) {
                        $local_val = $rlXmlMapping->getMappingItem($xml_field, $check_val, null, 'field_item', 'Data_local');
                    }

                    if ($local_val) {
                        $out .=$local_val . ',';
                    }
                }
                $out = trim($out, ',');
            break;
            case 'number':
                $xml_value = preg_replace("/[\D\s]*/i","", $xml_value);
                $out = (int)$xml_value;
            break;
            case 'mixed':
            case 'price':
                $out = $xml_value;
                break;
            case 'textarea':
                if ($field_info['Multilingual'] && is_array($xml_value)) {
                    $ml_codes_field = array_search('sys_lang_codes', $rlXmlMapping->mapping);

                    $html_field = $field_info['Condition'] == 'html' ? true : false;
                    $out = '';
                    foreach ($xml_value as $k => $val) {
                        //if lang codes field is not mapped use first description
                        if (!$ml_codes_field || !$listing[$ml_codes_field]) {
                            $out = $this->prepareTextForDb($xml_value[0], $html_field);
                            break;
                        } else {
                            $lang_code = $listing[$ml_codes_field];
                            $lcode = $rlXmlMapping->getMappingItem(
                                $ml_codes_field,
                                is_array($listing[$ml_codes_field]) ? $listing[$ml_codes_field][$k] : $listing[$ml_codes_field],
                                null,
                                'field_item',
                                'Data_local'
                            );

                            if ($lcode) {
                                $val = $this->prepareTextForDb($val, $html_field);
                                $out .= "{|{$lcode}|}" . $val . "{|/{$lcode}|}";
                            }
                        }
                    }
                    unset($listing[$ml_codes_field]);
                } elseif ($field_info['Condition'] == 'html') {
                    $out = $this->prepareTextForDb($xml_value, true);
                } else {
                    $out = $this->prepareTextForDb($xml_value);
                }
                break;
            case "text":
                if (is_array($xml_value)) {
                    $out = $xml_value[0];
                } else {
                    $out = $xml_value;
                }
            break;
            default:
                $out = $xml_value;
            break;
        }

        return $out;
    }

    /**
    * PrepareTextForDb - prepare text data for insertion to database,
    *                    set correct new lines according to field type
    *
    * @param string $text - text to prepare
    * @param bool   $html - true if field is html, false if plain text
    */
    function prepareTextForDb($text, $html = false)
    {
        $text = is_array($text) ? $text[0] : $text;

        if ($html) {
            $text = nl2br($text);
            $text = str_replace(PHP_EOL, '<br />', $text);
            $text = str_replace('\r\n', '<br />', $text);
            $text = str_replace('\\n', '<br />', $text);
            $text = str_replace('\n', '<br />', $text);
            $text = htmlspecialchars_decode($text);
        } else {
            $text = str_replace('\r\n', PHP_EOL, $text);
            $text = str_replace('\n', PHP_EOL, $text);
            $text = strip_tags($text);
        }

        return $text;
    }

    /**
    * IsFileExistsAndReadable - checks if a file is readable
    *
    * @param string $file_url - file url
    * @param string $method - curl
    */
    public function isFileExistsAndReadable($file_url, $method = 'normal')
    {
        if ($method == 'curl') {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $res = $httpCode = 200;
        } else {
            $headers = get_headers($file_url);
            $res = $headers[0] == 'HTTP/1.1 200 OK';
        }

        return $res;
    }

    /**
    * CopyFile - copied a remote file to local filesystem to proceed with import
    *
    * @return bool
    */
    protected function copyFile()
    {
        global $lang;

        $local_file_expiration_time = 10;//seconds
        $local_file_expiration_time = 10000;//seconds

        $this->xmlDebug('copyFile', 'start');

        if ($this->feed['Access_method']  == 'copy' || $this->feed['Access_method']  == 'stream') {
            $filename = RL_UPLOAD . $this->feed['Key'] . '.' . $this->feed['Type'];
            $fmtime = filemtime($filename);
            $fmtime = false;

            if (!$fmtime || $fmtime + ($local_file_expiration_time*60) < time() || !filesize($filename)) {
                if (is_file($filename)) {
                    unlink($filename);
                }

                if (!$fmtime) {
                    $this->xmlLogger($lang['xf_progress_no_local_file'], "notice");
                } else {
                    $this->xmlLogger($lang['xf_progress_file_expired'], "notice");
                }

                if ($this->feed['Access_method']  == 'stream' && !$this->feed['Http_auth']) {
                    $stream = fopen($this->feed['Url'], 'r');

                    $t = stream_copy_to_stream(
                        $stream,
                        fopen($filename, 'w+')
                    );

                    $this->xml_file = $filename;
                    $filesize = filesize($this->feed['Url']);

                    if ($filesize) {
                        $this->xmlLogger(str_replace("{filesize}", $filesize, $lang['xf_progress_file_copied_stream']), "notice");

                        return true;
                    } else {
                        $this->xmlLogger($lang['xf_progress_file_copy_failed'].";#stream", "notice");

                        return false;
                    }
                } else {
                    $ctx = null;
                    if ($this->feed['Http_auth'] && $this->feed['Http_auth_login'] && $this->feed['Http_auth_pass']) {
                        $auth = base64_encode($this->feed['Http_auth_login'] . ':' . $this->feed['Http_auth_pass']);
                        $header = array("Authorization: Basic $auth");
                        $opts = array('http'=>array('method'=>'GET',
                                                    'header'=>$header));
                        $ctx = stream_context_create($opts);
                    }

                    if ($ctx) {
                        $res = copy($this->feed['Url'], $filename, $ctx);
                    } else {
                        $res = $GLOBALS['reefless']->copyRemoteFile($this->feed['Url'], $filename);
                    }

                    if ($res) {
                        $this->xml_file = $filename;

                        $filesize = filesize($this->xml_file);
                        $this->xmlLogger(str_replace("{filesize}", $filesize, $lang['xf_progress_file_copied']), "notice");

                        return true;
                    } else {
                        $this->xmlLogger($lang['xf_progress_file_copy_failed'].";#copy", "notice");

                        $this->feed['Access_method'] = 'direct';

                        return false;
                    }
                }
            } else {
                $this->xml_file = $filename;
                $GLOBALS['reefless']->rlChmod($this->xml_file);

                $this->xmlLogger($lang['xf_progress_local_file_alive'], "notice");

                return true;
            }
        }

        if ($this->feed['Access_method'] == 'direct') {
            if ($this->isFileExistsAndReadable($this->feed['Url'])) {
                $this->xml_file = $this->feed['Url'];

                return true;
            } else {
                $this->xmlLogger($lang['xf_progress_file_fail'], "error");
                $this->statistics['error'] = $lang['xf_progress_file_fail'];

                return false;
            }
        }

        $this->xmlDebug('copyFile', 'end');
    }

    /**
    * Function extract photos
    *
    * @param array photos - input data
    * @return array out   - result
    */
    protected function extractPhotos($photos)
    {
        if (is_array($photos)) {
            foreach ($photos as $key => $value) {
                if ($this->isUrl($value)) {
                    $out[] = $value;
                } else {
                    $tmp = $this->extractPhotos($value);
                    foreach ($tmp as $k => $v) {
                        $out[] = $v;
                    }
                }
            }
        } elseif(preg_match('/(jpg|gif|png)(;|,|:|\|)/smi', $photos, $match)) {
            $out = explode($match[2], $photos);
        } elseif($this->isUrl($photos) && !strpos($photos, ';')) {
            $out[] = $photos;
        }

        return $out;
    }

    /**
     * Fetch videos from the feed data
     *
     * @since 3.4.0
     *
     * @param  mixed $data - Videos data, string or array
     * @return array       - Videos array
     */
    protected function getVideos($data = '') {
        if (!$data) {
            return false;
        }

        $out = [];
        if (is_array($data)) {
            foreach ($data as $video) {
               $out = array_merge($out, $this->getVideos($video));
            }
        } else {
            if ($this->isUrl($data)) {
                $out[] = array(
                    'type' => strpos($data, 'youtu.be') || strpos($data, 'youtube.com') ? 'youtube' : 'local',
                    'uri' => $data
                );
            } elseif (strpos($data, 'youtu.be') || strpos($data, 'youtube.com')) {
                $out[] = array(
                    'type' => 'youtube',
                    'uri' => $data
                );
            }
        }

        return $out;
    }

    /**
     * Copy videos
     *
     * @since 3.4.0
     *
     * @param  array $videos    - Videos array to upload
     * @param  int   $listingID - Listing ID
     */
    protected function copyVideos($videos, $listingID)
    {
        global $rlDb, $rlListings, $l_player_file_types, $reefless;

        $this->xmlDebug('copyVideos', 'start');

        foreach ($videos as $index => $video) {
            $this->xmlDebug('copyVideoItem', 'start');

            if ($video['type'] == 'youtube') {
                // Remove all url variables and slashes at the end of the embed URL
                if (strpos($video['uri'], 'embed/')) {
                    $video['uri'] = preg_replace('/embed\/([^\/\?]+)([^\"]+)/', 'embed/$1', $video['uri']);
                }

                /**
                 * Fallback class load to avoid fatal error in rlListings::uploadVideo() method
                 * @todo - Remove once the plugin compatibility is >= 4.9.2
                 */
                $reefless->loadClass('Actions');

                $rlListings->uploadVideo('youtube', $video['uri'], $listingID);
            } else {
                $video_info = pathinfo($video['uri']);
                $extension = $video_info['extension'];

                if (!array_key_exists($extension, $l_player_file_types)) {
                    $this->xmlLogger('copyVideo: Video extension ' . $extension . ' is not acceptable - ' . $video['uri'], 'error');
                    continue;
                }

                $dir_name = date('m-Y') . '/ad' . $listingID . '/';
                $dir = RL_FILES . $dir_name;
                $reefless->rlMkdir($dir);

                $file_name = ListingMedia::buildName($listingID, null, $index, $extension, $dir_name);
                $file_name .= '.' . $extension;

                $video_name = str_replace('{postfix}', 'video', $file_name);
                $video_dir  = $dir . $video_name;

                $reefless->copyRemoteFile($video['uri'], $video_dir);

                if (is_readable($video_dir)) {
                    $possition = $rlDb->getRow("
                        SELECT MAX(`Position`) AS `Position`
                        FROM `{db_prefix}listing_photos`
                        WHERE `Listing_ID` = {$listingID}
                    ", 'Position');

                    $insert = array(
                        'Listing_ID' => $listingID,
                        'Position'   => $possition + 1,
                        'Original'   => $dir_name . $video_name,
                        'Thumbnail'  => '',
                        'Type'       => 'Video',
                    );

                    $reefless->insertOne($insert, 'listing_photos');
                } else {
                    $this->xmlLogger('copyVideo: Video file is not readable - ' . $video_dir, 'error');
                }
            }
        }

        $this->xmlDebug('copyVideos', 'end');
    }

    /**
    * IS URL - checking if input string correctly formatted URL
    *
    * @param array photos - input data
    * @return array out   - result
    */
    protected function isUrl($url)
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL);

        if ($GLOBALS['rlValid']->isUrl($url)) {
            return true;
        }
    }

    /**
    * CollectPhotosForDelayedImport - collect photos for delayed import
    *
    * @param array photos - input data
    * @param array listing_id - input data
    */
    private function collectPhotosForDelayedImport($photos, $listing_id)
    {
        if (!$photos || !$listing_id) {
            return false;
        }

        foreach($photos as $key => $photo) {
            $insert['Source'] = $photo;
            $insert['Listing_ID'] = $listing_id;
            $insert['Status'] = 'new';

            if (!$GLOBALS['rlDb']->fetch("*", $insert, null, null, "xml_photos", "row")) {
                $GLOBALS['rlDb']->insertOne($insert, "xml_photos");
            }
        }
    }

    /**
    * CollectPhotosForDelayedImport - collect photos for delayed import
    *
    * @param array photos - input data
    * @param array listing_id - input data
    */
    public function copyListingPhotos($photos, $listing_id, $mode = 'insert')
    {
        global $rlListingTypes;

        if (!$this->feed['Update_photos'] && $mode == 'update') {
            return false;
        }

        //workaround to aboid conflicts in update mode with not-delayed photos, make not-delayed photos only work when inserting
        if ($mode == 'update' && $this->feed['Update_photos'] && $this->feed['Delayed_photos'] && $this->feed['Not_delayed_photos']) {
            $this->feed['Not_delayed_photos'] = 0;
        }

        /* re-copy photos only if update photos enabled and action is update */
        $delete_photos = false;
        if ($mode == 'update') {
            $listingPhotos = array_filter((array) ListingMedia::get($listing_id), function ($photo) {
                return $photo['Type'] == 'picture';
            });

            if ($this->checkPhotosDifference($listingPhotos, $photos)) {
                $delete_photos = true; // delete and re-copy
            } else {
                $this->xmlLogger($GLOBALS['lang']['xf_photos_same_skip']);

                return false;
            }

            if ($delete_photos) {
                $accountInfo = ['ID' => $this->feed['Account_ID']];

                // Force removal of all images from the listing
                $rlListingTypes->types[$this->feed['Listing_type']]['Photo_required'] = 0;

                foreach ($listingPhotos as $listingPhoto) {
                    ListingMedia::delete($listing_id, $listingPhoto['ID'], $accountInfo);
                }
            }
        }

        if ($this->feed['Delayed_photos']) {
            if ($this->feed['Not_delayed_photos'] > 0) {
                $photos_to_delay = array_slice($photos, $this->feed['Not_delayed_photos']);
                $photos_to_import = array_slice($photos, 0, $this->feed['Not_delayed_photos']);
            } else {
                $photos_to_delay = $photos;
                $photos_to_import = array();
            }
            $photos = $photos_to_import;

            $this->collectPhotosForDelayedImport($photos_to_delay, $listing_id);
        }

        if ($photos) {
            $this->copyPhotos($photos, $listing_id);
        }
    }

    /**
     * Copy and resize pictures
     * @param  array   $photos     - Picture URLs
     * @param  integer $listing_id - Listing ID
     * @return boolean             - Success/Failure status
     */
    public function copyPhotos($photos, $listing_id)
    {
        if (!$listing_id || !$photos) {
            return false;
        }

        $this->xmlDebug('copyPhotos', 'start');

        require_once RL_PLUGINS . 'xmlFeeds/vendor/autoload.php';
        $result = (bool) (new ListingImageUploader())->load($listing_id, $photos);

        $this->xmlDebug('copyPhotos', 'end');

        return $result;
    }

    /**
    * Check if photo arrays are different
    *
    * @param $local_photos - local photos array
    * @param $local_photos - remote photos array
    * @return - bool -
    */
    private function checkPhotosDifference($local_photos, $remote_photos)
    {
        if (count($local_photos) != count($remote_photos)) {
            return true;
        }

        if ($local_photos[0]['Thumbnail'] && !@getimagesize($local_photos[0]['Thumbnail'])) {
            return true;
        }

        return false;
    }

    /**
    * Create Account
    *
    * @param $seller - account data
    */
    protected function createAccount($seller) {
        global $rlDb, $reefless;

        $this->feed['Default_atype'] = 'dealer';

        $type_id = $rlDb->getOne('ID', "`Key` = '" . $this->feed['Default_atype'] . "'", 'account_types');

        $reefless->loadClass('Account');

        $profile['username'] = $GLOBALS['rlValid']->str2key($seller['name']);
        $profile['username'] = $GLOBALS['rlAccount']->makeUsernameUnique($profile['username']);

        $profile['mail'] = $seller['email'];

        //$profile['location'] = $GLOBALS['rlValid']->str2path($seller['name']);
        //$profile['password']
        //$profile['lang']
        //$profile['display_email']
        //$profile['plan']

        $account['First_name'] = $seller['name'];
        $account['Last_name'] = '';

        $ex_account = $rlDb->fetch(array('ID'), array('Mail' => $profile['mail']), null, null, 'accounts', 'row');
        $account_id = $ex_account['ID'];

        if (!$account_id) {
            $GLOBALS['rlAccount']->registration($type_id, $profile, $account);
            $account_id = $_SESSION['registration']['account_id'] ?: $rlDb->insertID();
        }

        return $account_id;
    }

    /**
    * Handle Removed Ads
    */
    private function handleRemovedAds() {
        global $rlDb, $lang;

        if ($this->statistics['error'] || !$this->start_time) {
            return false;
        }

        if ($this->feed['Removed_ads_action'] == 'expire') {
            $sql = "
                UPDATE `{db_prefix}listings` AS `T1`
                LEFT JOIN `{db_prefix}xml_listings` AS `T2` ON `T1`.`ID` = `T2`.`Listing_ID`
                SET `T1`.`Status` = 'expired' WHERE `T2`.`xml_feed_key` = '{$this->feed['Key']}'
                AND UNIX_TIMESTAMP(`T2`.`xml_last_updated`) < {$this->start_time}
                AND `T1`.`Status` <> 'trash'
            ";
            $rlDb->query($sql);

            $affected_listings = $rlDb->affectedRows();

            $this->xmlLogger($lang['xf_progress_start'], 'notice');

        } elseif ($this->feed['Removed_ads_action'] == 'remove') {
            $sql = "
                SELECT `Listing_ID` FROM `{db_prefix}xml_listings`
                WHERE `xml_feed_key` = '{$this->feed['Key']}'
                AND UNIX_TIMESTAMP(`xml_last_updated`) < {$this->start_time}
            ";
            $listings_to_delete = $rlDb->getAll($sql, [false, 'Listing_ID']);

            foreach ($listings_to_delete as $listing_id) {
                $GLOBALS['rlListings']->deleteListingData($listing_id);
                $rlDb->query("DELETE FROM `{db_prefix}listings` WHERE `ID` = '{$listing_id}' LIMIT 1");
            }

            $affected_listings = count($listings_to_delete);
            $this->xmlLogger(str_replace('{count}', $affected_listings, $lang['xf_progress_deleted']));
        }

        $this->statistics['deleted'] = $affected_listings;
    }


    /**
    * XmlLogger - log any progress event
    *
    * @param string $message - message string
    * @param string $type    - type of message; notice, warning, error, debug, data_debug
    */
    public function xmlLogger($message = false, $type = 'notice')
    {
        $out = '';
        if ($type == 'divider') {
            if ($this->print_progress == 'html') {
                $out .='<hr />';
            } else {
                $out .='------------------';
            }
        }

        if ($type && $this->print_progress == 'html') {
            $out .='<div class="progress_'.$type.'">';
        }

        $out .= $message;

        if ($this->print_progress == 'plain') {
            $out .="\r\n";
        } else if ($type && $this->print_progress == 'html') {
            $out .='</div>';
        }

        if ($this->print_progress) {
            echo $out;
        } elseif ($type == 'error') {
            $GLOBALS['rlDebug']->logger($message);
        }
    }

    /**
    * XmlDebug - measure execution time of modules
    *
    * @param string $module - module to debug
    * @param string $phase  - start or end event
    * @param string $force  - force debugging even not in modulesToDebug
    */
    protected function xmlDebug($module, $phase, $force = false)
    {
        if (!$force && (!$this->debug || !$module || !in_array($module, $this->modulesToDebug))) {
            return false;
        }

        if ($phase) {
            $time = microtime();
            $time = explode(" ", $time);
            $time = $time[1] + $time[0];

            if ($phase == 'start') {
                $this->time_stats[$module]['start'] = $time;

                $msg = sprintf("The %s action started.", $module, $this->time_stats[$module]['time']);
            } else {
                $this->time_stats[$module]['finish'] = $time;
                $this->time_stats[$module]['time'] = $this->time_stats[$module]['finish']-$this->time_stats[$module]['start'];

                $msg = sprintf("The %s action took %f seconds to load.", $module, $this->time_stats[$module]['time']);
            }

            $this->xmlLogger($msg, 'debug');
        }
    }

    /**
    * Get Exif Type - save import stats
    *
    * @param string $file  - image file name or url
    * @return string       - image extension
    */
    private function getExifType($file)
    {
        if (!function_exists('exif_imagetype')){
            return strtolower(pathinfo($file, PATHINFO_EXTENSION));
        }

        $exif_type = exif_imagetype($file);
        switch ($exif_type) {
            case IMAGETYPE_GIF:
                return 'gif';
            break;
            case IMAGETYPE_JPEG:
                return 'jpg';
            break;
            case IMAGETYPE_PNG:
                return 'png';
            break;
        }

        return false;
    }

    /**
    * SaveStatistics - save import stats
    */
    protected function saveStatistics()
    {
        $insert['Account_ID'] = $this->feed['Account_ID'];
        $insert['Feed'] = $this->feed['Feed'];
        $insert['Date'] = 'NOW()';
        $insert['Listings_inserted'] = $this->statistics['inserted'];
        $insert['Listings_updated'] = $this->statistics['updated'];
        $insert['Listings_deleted'] = $this->statistics['deleted'];

        $this->statistics['inserted'] = 0;
        $this->statistics['updated'] = 0;
        $this->statistics['deleted'] = 0;

        $GLOBALS['rlDb']->insertOne($insert, 'xml_statistics');
    }

    /**
    * BeforeImportStarted - load necessary plugin classes etc.
    */
    public function beforeImportStarted()
    {
        global $rlListings;

        $this->xmlDebug('beforeImportStarted', 'start');

        if ($rlListings && method_exists($rlListings, 'beforeImportStarted')) {
            $rlListings->beforeImportStarted();
        } else {
            if ($GLOBALS['plugins']['ref']) {
                $GLOBALS['reefless']->loadClass('Ref', null, 'ref');
            }
        }

        $this->xmlDebug('beforeImportStarted', 'end');
    }

    /**
    * BeforeItemImported - add necessary fields to the data array if necessary.
    *
    * @param array $data      - imported listing data
    * @param string $action   - update or insert
    */
    public function beforeItemImported(&$data, $action) {
        global $rlListings;

        if (method_exists($rlListings, 'beforeItemImported')) {
            $rlListings->beforeItemImported($data, 'xmlFeeds', $action);
        } else {
            /* claim listing plugin */
            if ($GLOBALS['rlDb']->columnExists('cl_direct', 'listings')) {
                $data['cl_direct'] = '1';
            }
            /* claim listing plugin end */
        }
    }

    /**
    * AfterItemImported    - add necessary fields to the data array if necessary.
    *
    * @param array $data      - imported listing data
    * @param int $listing_id  - type of message; notice, warning, error, debug, data_debug
    * @param string $action   - update or insert
    */
    public function afterItemImported($data, $listing_id, $action)
    {
        global $rlListings, $plugins, $config;

        $this->xmlDebug('afterItemImported', 'start');

        if (method_exists($rlListings, 'afterItemImported')) {
            $rlListings->afterItemImported($data, $listing_id, 'xmlFeeds', $action);
        } else {
            /* ref number plugin */
            if ($plugins['ref'] && $GLOBALS['rlRef']) {
                if ($action == 'insert' || ($action == 'update' && !$data['ref_number'])) {
                    if (method_exists($GLOBALS['rlRef'], 'updateRefOfTheListing')) {
                        $GLOBALS['rlRef']->updateRefOfTheListing($listing_id);
                    } else {
                        $ref_number = $GLOBALS['rlRef']->generate($listing_id, $GLOBALS['config']['ref_tpl']);

                        $sql = "UPDATE `{db_prefix}listings` SET `ref_number` = '{$ref_number}' ";
                        $sql .="WHERE `ID` = {$listing_id} LIMIT 1";
                        $GLOBALS['rlDb']->query($sql);
                    }
                }
            }
            /* ref number plugin end */

            // Queue a listing for later posting
            if ($plugins['autoPoster'] && $config['ap_xml_backend'] && defined('REALM') && REALM === 'admin') {
                $GLOBALS['reefless']->loadClass('AutoPoster', null, 'autoPoster');

                if ($GLOBALS['rlAutoPoster'] && method_exists($GLOBALS['rlAutoPoster'], 'addListingInQueue')) {
                    $GLOBALS['rlAutoPoster']->addListingInQueue($listing_id);
                }
            }
        }

        $this->xmlDebug('afterItemImported', 'end');
    }

    /**
    * AfterImportCompleted - finalize import, recounts etc.
    */
    public function afterImportCompleted()
    {
        global $rlListings;

        $this->xmlDebug('afterImportCompleted', 'start');

        if (method_exists($rlListings, 'afterImportCompleted')) {
            $rlListings->afterImportCompleted();
        } else {
            global $rlControls;

            $GLOBALS['reefless']->loadClass('Controls', 'admin');
            $rlControls->ajaxRecountListings(false, true);

            $this->xmlDebug('afterImportCompleted', 'end');
        }
    }

    /**
    * ClearVars - kind of destructor, clearing variables to prepare for next feed initialization
    */
    public function clearVars()
    {
        unset($this->feed);
        unset($this->xml_file);
        unset($this->time_stats);
    }
}
