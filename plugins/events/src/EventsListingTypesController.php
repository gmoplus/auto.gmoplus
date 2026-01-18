<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: EVENTSLISTINGTYPESCONTROLLER.PHP
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

namespace Flynax\Plugins\Events;


class EventsListingTypesController
{
    /**
     * @var \rlDb
     */
    protected $rlDb;

    /**
     * @var \rlActions
     */
    protected $rlActions;

    /**
     * @var \rlValid
     */
    protected $rlValid;

    /**
     * @var string
     */
    protected $listingTypeUrl;

    /**
     * Box options support, starts from 4.9.3
     *
     * @since 1.1.3
     * @var boolean
     */
    protected $boxOptionsSupport = false;

    /**
     * EventsListingTypesController constructor.
     */
    public function __construct()
    {
        $this->rlDb = eventsContainerMake('rlDb');
        $this->rlActions = eventsContainerMake('rlActions');
        $this->rlValid = eventsContainerMake('rlValid');

        $this->listingTypeUrl = RL_URL_HOME . ADMIN . '/index.php?controller=listing_types&action=add';

        $this->boxOptionsSupport = version_compare($GLOBALS['config']['rl_version'], '4.9.3', '>=');
    }

    /**
     * Add 'Events'  listing type
     */
    public function add()
    {
        //get rid of the global
        global $lang, $config, $rlEvents, $_categoryBoxOptionKeys;

        $order = $this->rlDb->getRow("SELECT MAX(`Order`) AS `max` FROM `{db_prefix}listing_types`");

        $fKey = $this->getKey();
        if (!$fKey) {
            $fKey = 'events_' . time();
        }
        if (!$this->rlDb->getOne('ID', "`Key` = '{$fKey}'", 'listing_types')) {

            $data = array(
                'Key' => $fKey,
                'Order' => $order['max'] + 1,
                'Add_page' => 1,
                'Photo' => 1,
                'Photo_required' => 0,
                'Video' => 1,
                'Admin_only' => 0,
                'Links_type' => 'full',
                'Cat_general_cat' => 0,
                'Cat_hide_empty' => 0,
                'Cat_order_type' => 'position',
                'Cat_custom_adding' => 0,
                'Cat_show_subcats' => 0,
                'Search' => 1,
                'Search_home' => 0,
                'Search_page' => 1,
                'Search_type' => 1,
                'Advanced_search' => 0,
                'Myads_search' => 1,
                'Submit_method' => 'post',
                'Arrange_field' => null,
                'Arrange_values' => null,
                'Arrange_search' => 0,
                'Search_multi_categories' => 0,
                'Search_multicat_levels' => 2,
                'Search_multicat_phrases' => 0,
                'Status' => 'active',
            );
            $update_cache_key = $fKey;

            if (version_compare($GLOBALS['config']['rl_version'], '4.10.1') < 0) {
                $data['Featured_blocks'] = 0;
                $data['Arrange_featured'] = 0;
            }

            if (!isset($config['html_in_categories'])) {
                $data['Cat_postfix'] = 0;
            }

            if (!$this->boxOptionsSupport) {
                $data['Cat_position'] = 'left';
                $data['Cat_listing_counter'] = 1;
                $data['Ablock_pages'] = 1;
                $data['Ablock_position'] = 'left';
                $data['Ablock_visible_number'] = 0;
                $data['Ablock_show_subcats'] = 0;
                $data['Ablock_subcat_number'] = 0;
                $data['Ablock_scrolling'] = 1;
            }

            if ($action = $this->rlDb->insertOne($data, 'listing_types')) {
                $updateTypeInfo = [];

                $insertEventKey = array(
                    'Key' => 'event_type_key',
                    'Default' => $fKey,
                    'Plugin' => 'events',
                );
                $this->rlDb->insertOne($insertEventKey, 'config');

                $this->rlActions->enumAdd('search_forms', 'Type', $fKey);
                $this->rlActions->enumAdd('categories', 'Type', $fKey);
                $this->rlActions->enumAdd('account_types', 'Abilities', $fKey);
                $this->rlActions->enumAdd('saved_search', 'Listing_type', $fKey);

                $sql = "UPDATE `{db_prefix}account_types` ";
                $sql .= "SET `Abilities` = TRIM(BOTH ',' FROM CONCAT(`Abilities`, ',{$fKey}')) ";
                $sql .= "WHERE `Key` NOT IN ('affiliate')";
                $this->rlDb->query($sql);

                $allLangs = $GLOBALS['languages'];

                foreach ($allLangs as $key => $value) {
                    $langCode = $value['Code'];

                    $fName = $rlEvents->importData[$langCode == 'ru' ? 'listing_type_name_ru' : 'listing_type_name'];

                    $lang_keys[] = $this->prepareLangArray($langCode, 'listing_types+name+' . $fKey, $fName);

                    $lang_keys[] = $this->prepareLangArray($langCode, 'page+name+' . $fKey, $fName);

                    // prepare individual page phrases
                    $lang_keys[] = $this->prepareLangArray($langCode, 'pages+name+lt_' . $fKey, $fName);

                    // individual page titles
                    $dateVar = $langCode == 'en' ? '{if date} at {date}{/if}' : '{if date} {date}{/if}';
                    $lang_keys[] = $this->prepareLangArray($langCode, 'pages+title+lt_' . $fKey, $fName . $dateVar);

                    // individual page h1
                    $lang_keys[] = $this->prepareLangArray($langCode, 'pages+h1+lt_' . $fKey, $fName . $dateVar);

                    // create search block names
                    $lang_keys[] = $this->prepareLangArray(
                        $langCode,
                        'blocks+name+ltsb_' . $fKey,
                        str_replace(['{type}', '(', ')'], '', $GLOBALS['rlLang']->getPhrase('refine_search_pattern', $langCode, null, true))
                    );

                    // page search form handler
                    $lang_keys[] = $this->prepareLangArray(
                        $langCode,
                        'blocks+name+ltpb_' . $fKey,
                        str_replace('{type}', '', $GLOBALS['rlLang']->getPhrase('listing_type_search_box_pattern', $langCode, null, true))
                    );

                    // refube search form handler
                    $lang_keys[] = $this->prepareLangArray(
                        $langCode,
                        'blocks+name+ltma_' . $fKey,
                        str_replace('{type}', '', $GLOBALS['rlLang']->getPhrase('myads_box_pattern', $langCode, null, true))
                    );

                    // cat box
                    $lang_keys[] = $this->prepareLangArray(
                        $langCode,
                        'blocks+name+ltcategories_' . $fKey,
                        str_replace('{type}', '', $GLOBALS['rlLang']->getPhrase('categories_block_pattern', $langCode, null, true))
                    );

                    // search forms titles
                    $lang_keys[] = $this->prepareLangArray($langCode, 'search_forms+name+' . $fKey . '_quick', $fName);
                    $lang_keys[] = $this->prepareLangArray($langCode, 'search_forms+name+' . $fKey . '_myads', $fName);
                    $lang_keys[] = $this->prepareLangArray($langCode, 'search_forms+name+' . $fKey . '_advanced', $fName);

                    // my event page titles
                    $lang_keys[] = $this->prepareLangArray(
                        $langCode,
                        'pages+title+my_' . $fKey,
                        str_replace('{type}', $fName, $GLOBALS['rlLang']->getPhrase('my_listings_pattern', $langCode, null, true))
                    );

                    $lang_keys[] = $this->prepareLangArray(
                        $langCode,
                        'pages+name+my_' . $fKey,
                        str_replace('{type}', $fName, $GLOBALS['rlLang']->getPhrase('my_listings_pattern', $langCode, null, true))
                    );

                    // add event page titles
                    $post_an_event = $GLOBALS['rlLang']->getPhrase('post_an_event', $langCode, null, true);
                    $lang_keys[] = $this->prepareLangArray(
                        $langCode,
                        'pages+name+al_' . $fKey,
                        $post_an_event
                    );

                    $lang_keys[] = $this->prepareLangArray(
                        $langCode,
                        'pages+title+al_' . $fKey,
                        $post_an_event
                    );
                }
                $this->rlDb->insert($lang_keys, 'lang_keys');
            }
        }

        if (!$this->rlDb->getOne('ID', "`Key` = 'ev_path'", 'config')) {
            // create page for events
            $path = $this->buildTypePath('events');

            $addConfigEventPath = array(
                'Key' => 'ev_path',
                'Default' => $path,
                'Type' => 'text',
                'Plugin' => 'events',
            );
            $this->rlDb->insertOne($addConfigEventPath, 'config');
        }

        $tmpPage = array(
            'Key' => 'lt_' . $fKey,
            'Path' => $path,
            'Menu' => '1,3',
            'Controller' => 'listing_type',
        );
        $pageID = $this->addPage($tmpPage);

        $data['search_form'] = true;
        if (!empty($data['search_form'])) {
            $this->addQuickSearchForm($fKey);
        }

        $data['advanced_search'] = true;
        if (!empty($data['advanced_search'])) {
            $this->addAdvancedSearchForm($fKey);
        }

        $myads_search = true;
        if ($myads_search) {
            $this->addMyAdsSearchForm($fKey);
        }

        // create additional categories block
        $catBoxData = array(
            'Key' => 'ltcategories_' . $fKey,
            'Page_ID' => $pageID,
            'Content' => "{include file='blocks'|cat:\$smarty.const.RL_DS|cat:'categories.tpl'}",
        );

        if ($this->boxOptionsSupport) {
            $main_category_box_options = $_categoryBoxOptionKeys;
            unset($main_category_box_options['group_categories']);

            $catBoxData['Options'] = json_encode($main_category_box_options);
            $catBoxData['Content'] = '{include file=$componentDir|cat:"category-box/_category-box.tpl" typePage=true}';
        }

        $this->addBlock($catBoxData);
        $updateTypeInfo['fields']['Ablock_pages'] = $pageID;

        // create search block
        $searchBoxData = array(
            'Page_ID' => $pageID,
            'Key' => 'ltsb_' . $fKey,
            'Content' => '{include file=$refine_block_controller}',
        );
        $this->addBlock($searchBoxData);

        // type page search form handler
        $searchFormData = array(
            'Page_ID' => $pageID,
            'Key' => 'ltpb_' . $fKey,
            'Content' => "{include file='blocks'|cat:\$smarty.const.RL_DS|cat:'side_bar_search.tpl'}",
        );
        $this->addBlock($searchFormData);

        // update event page
        if ($pageID) {
            $updateData = array(
                'fields' => array(
                    'Sticky' => 0,
                    'Page_ID' => '1,2,30,42,' . $pageID,
                ),
                'where' => array(
                    'Key' => 'events_calendar',
                ),
            );
            $this->rlDb->updateOne($updateData, 'blocks');
        }

        /** @var \rlListingTypes */
        $rlListingTypes = eventsContainerMake('rlListingTypes');
        $rlListingTypes->arrange($data['Arrange_field']);

        // create add event page
        $tmpAddPage = array(
            'Key' => 'al_' . $fKey,
            'Path' => 'add-event',
            'Login' => 1,
            'Menu' => 2,
            'Controller' => 'add_listing',
        );
        $addEventID = $this->addPage($tmpAddPage);

        // create my event page
        $tmpMyEventPage = array(
            'Key' => 'my_' . $fKey,
            'Path' => 'my-events',
            'Login' => 1,
            'Menu' => 2,
            'Status' => 'active',
            'Controller' => 'my_listings',
        );
        $myPageID = $this->addPage($tmpMyEventPage);

        // my ads refine box search
        $refineSearchBoxData = array(
            'Page_ID' => $myPageID,
            'Key' => 'ltma_' . $fKey,
            'Content' => "{include file='blocks'|cat:\$smarty.const.RL_DS|cat:'refine_search.tpl'}",
        );
        $this->addBlock($refineSearchBoxData);

        if ($updateTypeInfo && $this->rlDb->getOne('ID', "`Key` = '{$fKey}'", 'listing_types')) {
            $updateTypeInfo['where'] = array('Key' => $fKey);
            $this->rlDb->updateOne($updateTypeInfo, 'listing_types');
        }
    }

    /**
     * Build type path
     *
     * @param  string $name - Type name
     * @return string       - Path
     */
    public function buildTypePath($name)
    {

        $path = $this->rlValid->str2path($name);

        if ($this->rlDb->getOne('ID', "`Path` = '{$path}'", 'pages')) {
            $path = $path . rand(0, 9);
            return $this->buildTypePath($path);
        } else {
            return $path;
        }
    }

    /**
     * Set categories for boxes of event type.
     */
    public function updateBoxes()
    {
        $typeKey = $this->getKey();
        $catIDs = $this->rlDb->fetch(
            array('ID'),
            array('Type' => $typeKey),
            null,
            null,
            'categories'
        );

        if ($catIDs) {
            $tmpIDs = [];
            foreach ($catIDs as $ids) {
                $tmpIDs[] = $ids['ID'];
            }

            $boxKeys = ['events_calendar', 'ltcategories_' . $typeKey, 'ltpb_' . $typeKey];

            // update event boxes
            foreach ($boxKeys as $key) {
                $updateData[] = array(
                    'fields' => array(
                        'Category_ID' => implode(',', $tmpIDs),
                    ),
                    'where' => array(
                        'Key' => $key,
                    ),
                );
            }

            $this->rlDb->update($updateData, 'blocks');
        }
    }

    /**
     * Add Pages for 'Events'
     *
     * @param  array $data - Date
     * @return int         - Insert ID
     */
    public function addPage($data = array())
    {
        $key = $data['Key'];
        if (!$key || $this->rlDb->getOne('ID', "`Key` = '{$key}'", 'pages')) {
            return 0;
        }

        $path = $data['Path'];
        $menu = $data['Menu'] ? $data['Menu'] : 1;
        $login = $data['Login'] ? $data['Login'] : 0;
        $status = $data['Status'] ? $data['Status'] : 'active';
        $controller = $data['Controller'];

        $sql = "SELECT MAX(`Position`) AS `max` FROM `{db_prefix}pages`";
        $page_position = $this->rlDb->getRow($sql);

        $newPage = array(
            'Parent_ID' => 0,
            'Page_type' => 'system',
            'Login' => $login,
            'Key' => $key,
            'Position' => $page_position['max'] + 1,
            'Path' => $path,
            'Controller' => $controller,
            'Tpl' => 1,
            'Menus' => $menu,
            'Modified' => 'NOW()',
            'Status' => $status,
            'Readonly' => 1,
        );
        $this->rlDb->insertOne($newPage, 'pages');
        $pageID = $this->rlDb->insertID();

        return $pageID;
    }

    /**
     * Prepare Phrase for 'Events' listing type
     *
     * @param  string $code  - Phrase language code
     * @param  string $key   - Phrase key
     * @param  string $value - Phrase value
     * @return array         - Prepared to insert to DB data
     */
    public function prepareLangArray($code = '', $key = '', $value = '', $module = 'common')
    {
        if (!$code || !$key || !$value) {
            return array();
        }

        return array(
            'Code' => $code,
            'Module' => $module,
            'Status' => 'active',
            'Key' => $key,
            'Plugin' => 'events',
            'Value' => $value,
        );
    }

    /**
     * Add new block
     *
     * @param array $additionalData - Additional data
     */
    public function addBlock($additionalData = array())
    {
        if ($this->rlDb->getOne('ID', "`Key` = '{$additionalData['Key']}'", 'blocks')) {
            return;
        }
        $sql = "SELECT MAX(`Position`) AS `max` FROM `{db_prefix}blocks`";
        $cat_block_position = $this->rlDb->getRow($sql);

        $blockTmp = array(
            'Page_ID' => $additionalData['Page_ID'],
            'Sticky' => 0,
            'Key' => $additionalData['Key'],
            'Position' => $cat_block_position['max'] + 1,
            'Side' => 'left',
            'Type' => 'smarty',
            'Content' => $additionalData['Content'],
            'Tpl' => 1,
            'Status' => 'active',
            'Readonly' => 1,
        );

        if ($additionalData['Options']) {
            $blockTmp['Options'] = $additionalData['Options'];
        }

        $this->rlDb->insertOne($blockTmp, 'blocks');
    }

    /**
     * Add quick search form
     *
     * @param string $type           - Type
     * @param array  $additionalData - Additional data
     */
    public function addQuickSearchForm($type, $additionalData = array())
    {
        $key = $type . '_quick';
        $additionalData = array(
            'Mode' => 'quick',
            'Type' => $type,
            'Key' => $key,
        );
        if ($this->addSearchForm($key, $additionalData)) {
            $formID = $this->rlDb->insertID();
            // build my search form
            $searchForm[] = array(
                'Position' => 1,
                'Group_ID' => 0,
                'Category_ID' => $formID,
                'Fields' => $this->rlDb->getOne('ID', "`Key` = 'keyword_search'", 'listing_fields'),
            );
            $searchForm[] = array(
                'Position' => 2,
                'Group_ID' => 0,
                'Category_ID' => $formID,
                'Fields' => $this->rlDb->getOne('ID', "`Key` = 'Category_ID'", 'listing_fields'),
            );

            $this->rlDb->insert($searchForm, 'search_forms_relations');
        }

        return true;
    }

    /**
     * Add my ads search form
     *
     * @param string $type           - Type
     * @param array  $additionalData - Additional data
     */
    public function addMyAdsSearchForm($type, $additionalData = array())
    {
        $key = $type . '_myads';
        $additionalData = array(
            'Mode' => 'myads',
            'Type' => $type,
            'Key' => $key,
        );

        if ($this->addSearchForm($key, $additionalData)) {
            $formID = $this->rlDb->insertID();
            // build my search form
            $mySearchForm = array(
                'Position' => 1,
                'Group_ID' => 0,
                'Category_ID' => $formID,
                'Fields' => $this->rlDb->getOne('ID', "`Key` = 'Category_ID'", 'listing_fields'),
            );
            $this->rlDb->insertOne($mySearchForm, 'search_forms_relations');
        }

        return true;
    }

    /**
     * Add advanced search form
     *
     * @param string $type - Type
     */
    public function addAdvancedSearchForm($type)
    {
        $key = $type . '_advanced';
        $additionalData = array(
            'Mode' => 'advanced',
            'Type' => $type,
            'Key' => $key,
        );

        return $this->addSearchForm($key, $additionalData);
    }

    /**
     * Add search form
     *
     * @param string $key            - Key
     * @param array  $additionalData - Additional data
     */
    public function addSearchForm($key, $additionalData)
    {
        if (!$key || $this->rlDb->getOne('ID', "`Key` = '{$key}'", 'search_forms')) {
            return false;
        }

        $newSearchFormData = array(
            'Key' => $key,
            'Type' => $additionalData['Type'],
            'Mode' => $additionalData['Mode'] ?: 'quick',
            'Groups' => $additionalData['Groups'] ?: 0,
            'Status' => $additionalData['Status'] ?: 'active',
            'Readonly' => $additionalData['Readonly'] ?: 1,
        );

        return (bool) $this->rlDb->insertOne($newSearchFormData, 'search_forms');
    }

    /**
     * Get event type key
     *
     * @return string - Event key
     */
    public function getKey()
    {
        $key = $GLOBALS['config']['event_type_key'] ? $GLOBALS['config']['event_type_key'] : $this->rlDb->getOne('Default', "`Key` = 'event_type_key'", 'config');
        return $key;
    }

    /**
     * Remove event type
     *
     * @string $key
     */
    public function remove($key)
    {
        if($key) {
            $rlListingTypes = eventsContainerMake('rlListingTypes');
            $rlListingTypes->deleteListingTypeData($key);

            $this->rlDb->delete(array('Key' => $key), 'listing_types');
            $this->rlDb->delete(array('Key' => 'event_rates'), 'listing_groups', null, 0);

            $this->rlDb->query("DELETE FROM `{db_prefix}pages` WHERE `Key` = 'lt_{$key}' OR `Key` = 'my_{$key}' OR `Key` = 'al_{$key}'");
            $this->rlDb->query("DELETE FROM `{db_prefix}search_forms` WHERE `Key` LIKE '{$key}%'");
            $this->rlDb->query("DELETE FROM `{db_prefix}data_formats` WHERE `Key` LIKE 'event_rates%'");

            $this->rlDb->dropTable('listing_event_rates');
        }
    }
}
