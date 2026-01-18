<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLEVENTS.CLASS.PHP
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

use Flynax\Plugins\Events\Event;
use Flynax\Plugins\Events\EventsListingTypesController;
use Flynax\Plugins\Events\EventsRates;
use Flynax\Utils\ListingMedia;

require_once RL_PLUGINS . 'events/bootstrap.php';

/**
 * Class rlEvents
 */
class rlEvents
{
    /**
     * Plugin key
     */
    const PLUGIN_NAME = 'events';
    const PLUGIN_PREFIX = 'ev_';

    /**
     * @var array - Plugin configurations
     */
    protected $configs;

    /**
     * @var \Flynax\Plugins\Events\EventsListingTypesController
     */
    private $eventsListingTypesController;

    /**
     * @var \Flynax\Plugins\Events\EventsCategory
     */
    private $eventTypeCategory;

    /**
     * @var \Flynax\Plugins\Events\EventsRates
     */
    private $eventRates;

    /**
     * @var importData
     */
    public $importData;

    /**
     * @var eventData
     */
    public $eventData;

    /**
     * @var url string
     */
    private $urlToData = RL_PLUGINS . 'events' . RL_DS . 'static' . RL_DS . 'info.json';

    /**
     * @var array non support box position
     */
    public $rejectedBoxSides = array('header_banner', 'integrated_banner');

    /**
     * rlEvents constructor.
     */
    public function __construct()
    {
        $this->eventsListingTypesController = new EventsListingTypesController();
        $this->eventTypeCategory = new \Flynax\Plugins\Events\EventsCategory();
        $this->eventRates = new \Flynax\Plugins\Events\EventsRates();

        $this->initializePluginConfigurations();
    }

    /**
     * Initialize basic plugin configuration
     */
    public function initializePluginConfigurations()
    {
        $pathBase = RL_PLUGINS . self::PLUGIN_NAME;
        $urlBase = RL_PLUGINS_URL . self::PLUGIN_NAME;

        $configs = array(
            'url' => array(
                'view' => "{$urlBase}/view/",
                'static' => "{$urlBase}/static/",
            ),
            'path' => array(
                'view' => "{$pathBase}/view/",
                'static' => "{$pathBase}/static/",
            ),
        );
        $this->configs = $configs;

        if ($GLOBALS['rlSmarty']) {
            $GLOBALS['rlSmarty']->assign('events_configs', $configs);
            $this->rlSmarty = $GLOBALS['rlSmarty'];
        }
    }

    /**
     * @param \rlStatic $rlStatic
     */
    public function hookStaticDataRegister($rlStatic = null)
    {
        $calendarCss = $this->configs['url']['static'] . 'events-calendar.css';
        $libJs = $this->configs['url']['static'] . 'lib.js';
        $rlStatic->addBoxFooterCSS($calendarCss, 'events_calendar');
        $rlStatic->addBoxJS($libJs, 'events_calendar');

        // load JS files
        $rlStatic->addJs(
            RL_PLUGINS_URL . 'events/static/add_edit_listing.js',
            array('add_listing', 'edit_listing')
        );
    }

    /**
     * @hook  init
     */
    public function hookInit()
    {
        if ($GLOBALS['config']['mod_rewrite']) {
            $pagePath = strlen($_GET['page']) < 3 ? $items[0] : $_GET['page'];
            $eventPath = explode(',', $GLOBALS['config']['ev_path']);

            if (in_array($pagePath, $eventPath)) {
                $items = str_replace('.html', '', $_SERVER['REQUEST_URI']);
                $items = explode('/', trim($items, '/'));
                foreach ($items as $key => $value) {
                    if ($this->validateDate($value)) {
                        $this->eventData['date'] = $value;
                        unset($items[$key]);
                        break;
                    }
                    else {
                        unset($items[$key]);
                    }
                }
                if ($this->eventData['date']) {
                    $_GET['rlVareables'] = implode('/', $items);
                    if ($_GET['listing_id']) {
                        unset($_GET['listing_id']);
                    }
                }
            }
        } else if ($_GET['event-date']) {
            $this->eventData['date'] = $_GET['event-date'];
        }
    }

    /**
     * @hook  pageinfoArea
     */
    public function hookPageinfoArea()
    {
        global $page_info, $reefless;

        // update cache after install plugin
        if ($_SESSION['rebuildEventCache']) {
            $GLOBALS['rlCache']->update();
            unset($_SESSION['rebuildEventCache']);
        }

        $eventKey = $this->eventsListingTypesController->getKey();
        $eventPageKey = 'lt_' . $eventKey;

        $metaTmp = ['h1', 'title'];
        foreach ($metaTmp as $meta) {
            $langKey = 'pages+' . $meta . '+' . $eventPageKey;
            $this->prepareDatePhrase($GLOBALS['lang'][$langKey]);
        }

        // replace page
        if ($page_info['Key'] == $eventPageKey) {

            $passed_event = false;

            if ($this->eventData['date'] && strtotime($this->eventData['date']) < strtotime(date('Y-m-d'))) {
                $passed_event = true;
            }

            foreach ($metaTmp as $meta) {
                $this->prepareDatePhrase($page_info[$meta]);

                if ($passed_event) {
                    $page_info[$meta] = $GLOBALS['lang']['past'] . ' - ' . $page_info[$meta];
                }
            }

            if (!$GLOBALS['config']['ev_show_passed_events'] && $this->eventData['date']
                && strtotime($this->eventData['date']) < strtotime(date('Y-m-d'))) {

                $eventPage = $reefless->getPageUrl($page_info['Key']);
                $reefless->redirect(null, $eventPage);
            }

        } else if ($page_info['Key'] == 'view_details') {
            $GLOBALS['config']['display_posted_date'] = false;
        }
    }

    /**
     * @hook  specialBlock
     */
    public function hookSpecialBlock()
    {
        global $page_info, $bread_crumbs;
        $eventKey = $this->eventsListingTypesController->getKey();
        if ($page_info['Key'] == 'lt_' . $eventKey && $this->eventData['date']) {
            $last = end(array_keys($bread_crumbs));
            $bread_crumbs[$last]['title'] = $bread_crumbs[$last]['name'];
        }
    }

    /**
     * @hook  smartyCompileFileBottom
     */
    public function hookSmartyCompileFileBottom(&$compiled_content, &$source_content, &$resource_name)
    {
        global $categories, $bread_crumbs;

        if (!$this->eventData['date']) {
            return;
        }

        $event_key = $this->eventsListingTypesController->getKey();
        $page_key = 'lt_' . $event_key;

        if (false !== strpos($source_content, 'categories.tpl')
            && $GLOBALS['page_info']['Key'] == $page_key) {

            if ($categories) {
                $catCount = $this->getCatCounts($this->eventData['date']);
                foreach ($categories as $key => &$value) {
                    $value['Path'] = $this->eventData['date'] . '/' . $value['Path'];
                    $value['Count'] = $catCount[$value['ID']] ? $catCount[$value['ID']]['Count'] : 0;
                }
            } else {
                foreach ($bread_crumbs as $key => $value) {
                    if ($value['name'] == $this->eventData['date']) {
                        unset($bread_crumbs[$key]);
                    }
                }
            }
        } else if (false !== strpos($source_content, 'categories_block.tpl')
            && $GLOBALS['page_info']['Key'] == $page_key) {

            $catCount = $this->getCatCounts($this->eventData['date']);
            foreach ($GLOBALS['rlSmarty']->_tpl_vars['box_categories'][$event_key] as $key => &$value) {
                $value['Path'] = $this->eventData['date'] . '/' . $value['Path'];
                $value['Count'] = $catCount[$value['ID']] ? $catCount[$value['ID']]['Count'] : 0;
            }
        }
    }

    /**
     * @hook  phpUrlBottom
     * @since 1.1.5
     */
    public function hookPhpUrlBottom(&$url, $mode, $data, $customLang)
    {
        global $page_info;

        if (!$this->eventData['date'] || !$GLOBALS['config']['mod_rewrite']) {
            return;
        }

        $event_key = $this->eventsListingTypesController->getKey();
        $page_key = 'lt_' . $event_key;

        if ($data['Type'] == $event_key && $page_info['Key'] == $page_key) {

            $find = array('/(' . $page_info['Path'] . ')/');
            $replace = array($page_info['Path'] . '/' . $this->eventData['date']);

            $url = preg_replace($find, $replace, $url);
        }
    }

    /**
     * @hook  phpAfterDeleteListing
     */
    public function hookPhpAfterDeleteListing($info)
    {
        global $page_info, $pages;

        $event_key = $this->eventsListingTypesController->getKey();
        $page_key = 'my_' . $event_key;

        if ($page_info['Key'] == $page_key) {
            $addKey = $GLOBALS['rlListingTypes']->types[$event_key]['Add_page'] ? 'al_' . $event_key : 'add_listing';
            $pages['add_listing'] = $pages[$addKey];
        }
    }

    /**
     * @hook  listingsModifyField
     */
    public function hookListingsModifyField()
    {
        global $sql;

        $event_key = $this->eventsListingTypesController->getKey();
        if ($GLOBALS['listing_type']['Key'] == $event_key) {
            $sql .= "IF (`T1`.`event_type` = '1',  ";
            $sql .= "IF (UNIX_TIMESTAMP( `T1`.`event_date` ) < UNIX_TIMESTAMP(DATE(NOW())), 1, 0),  ";
            $sql .= "IF (UNIX_TIMESTAMP( `T1`.`event_date_multi` ) < UNIX_TIMESTAMP(DATE(NOW())), 1, 0)  ";
            $sql .= ") AS `Event_passed`, ";
        }
    }

    /**
     * @hook  myListingsSqlFields
     */
    public function hookMyListingsSqlFields(&$sql)
    {
        $event_key = $this->eventsListingTypesController->getKey();
        $sql .= ", IF (`T4`.`Type` = '{$event_key}',  ";
        $sql .= "IF (`T1`.`event_type` = '1',  ";
        $sql .= "IF (UNIX_TIMESTAMP( `T1`.`event_date` ) < UNIX_TIMESTAMP(DATE(NOW())), 1, 0),  ";
        $sql .= "IF (UNIX_TIMESTAMP( `T1`.`event_date_multi` ) < UNIX_TIMESTAMP(DATE(NOW())), 1, 0)  ";
        $sql .= "), '') AS `Event_passed` ";
    }

    /**
     * @hook  listingsModifyFieldSearch
     *
     * @param  array $sql  - sql
     * @param  array $data - search data
     * @param  array $type - listing type
     * @param  array $form - form
     */
    public function hookListingsModifyFieldSearch(&$sql, &$data, $type, $form)
    {
        $event_key = $this->eventsListingTypesController->getKey();
        if ($type == $event_key) {
            if ($data['event_date']) {
                $this->eventData['date_search'] = $data['event_date'];
                unset($data['event_date']);
            }

            $sql .= "IF (`T1`.`event_type` = '1',  ";
            $sql .= "IF (UNIX_TIMESTAMP( `T1`.`event_date` ) < UNIX_TIMESTAMP(DATE(NOW())), 1, 0),  ";
            $sql .= "IF (UNIX_TIMESTAMP( `T1`.`event_date_multi` ) < UNIX_TIMESTAMP(DATE(NOW())), 1, 0)  ";
            $sql .= ") AS `Event_passed`, ";
        }
    }

    /**
     * @hook  listingsModifyWhere
     */
    public function hookListingsModifyWhere($sql, $plugin_name)
    {
        global $sql, $custom_order;

        $event_key = $this->eventsListingTypesController->getKey();
        if ($GLOBALS['listing_type']['Key'] == $event_key) {
            if ($this->eventData['date']) {
                $date = $this->eventData['date'];
                $sql .= "AND IF (`T1`.`event_type` = '1', `T1`.`event_date` = '{$date}', '{$date}' BETWEEN `T1`.`event_date` AND `T1`.`event_date_multi`) ";
            } else {
                $dateToday = strtotime(date('Y-m-d'));
                $sql .= "AND IF (`T1`.`event_type` = '1',  ";
                $sql .= "UNIX_TIMESTAMP( `T1`.`event_date` ) >= {$dateToday}, ";
                $sql .= "UNIX_TIMESTAMP( `T1`.`event_date_multi` ) >= {$dateToday})  ";
            }

            if ($_SESSION['browse_sort_by'] == 'event_date') {
                $custom_order = 'event_date';
            }
        }
        else if ($event_key && $plugin_name == 'listings_box' && !$GLOBALS['config']['ev_show_passed_events']) {
            $dateToday = strtotime(date('Y-m-d'));
            
            $sql .= "AND IF ( `T3`.`Type` = '{$event_key}',";
            $sql .= "IF (`T1`.`event_type` = '1', ";
            $sql .= "UNIX_TIMESTAMP( `T1`.`event_date` ) >= {$dateToday}, ";
            $sql .= "UNIX_TIMESTAMP( `T1`.`event_date_multi` ) >= {$dateToday}) ";
            $sql .=  ", `T1`.`Status` = 'active') ";
        }
    }

    /**
     * @hook  listingsModifyWhereSearch
     *
     * @param  array $sql  - sql
     * @param  array $data - search data
     * @param  array $type - listing type
     * @param  array $form - form
     */
    public function hookListingsModifyWhereSearch(&$sql, $data, $type, $form)
    {
        $event_key = $this->eventsListingTypesController->getKey();
        if ($type == $event_key) {
            if ($this->eventData['date_search']['from'] || $this->eventData['date_search']['to']) {
                $from = strtotime($this->eventData['date_search']['from']);
                $to = strtotime($this->eventData['date_search']['to']);

                $sql .= "AND IF (`T1`.`event_type` = '1',  ";

                // single type
                if ($from) {
                    $sql .= "UNIX_TIMESTAMP( `T1`.`event_date` ) >= {$from} ";
                }
                if ($from && $to) {
                    $sql .= "AND ";
                }
                if ($to) {
                    $sql .= "UNIX_TIMESTAMP( `T1`.`event_date` ) <= {$to} ";

                }
                $sql .= ", ";

                // multi type
                if ($from && $to) {
                    $sql .= "({$from} BETWEEN UNIX_TIMESTAMP( `T1`.`event_date` ) AND UNIX_TIMESTAMP( `T1`.`event_date_multi` ) ";
                    $sql .= "OR UNIX_TIMESTAMP( `T1`.`event_date` ) BETWEEN {$from} AND {$to}) ";
                    $sql .= "AND ";
                    $sql .= "({$to} BETWEEN UNIX_TIMESTAMP( `T1`.`event_date` ) AND UNIX_TIMESTAMP( `T1`.`event_date_multi` ) ";
                    $sql .= "OR UNIX_TIMESTAMP( `T1`.`event_date_multi` ) BETWEEN {$from} AND {$to}) ";
                } else if ($from) {
                    $sql .= "UNIX_TIMESTAMP( `T1`.`event_date` ) >= {$from} ";
                } else if ($to) {
                    $sql .= "UNIX_TIMESTAMP( `T1`.`event_date` ) <= {$to} ";
                }
                $sql .= ")  ";
            } else {
                $dateToday = strtotime(date('Y-m-d'));
                $sql .= "AND IF (`T1`.`event_type` = '1',  ";
                $sql .= "UNIX_TIMESTAMP( `T1`.`event_date` ) >= {$dateToday}, ";
                $sql .= "UNIX_TIMESTAMP( `T1`.`event_date_multi` ) >= {$dateToday})  ";
            }
        }
    }

    /**
     * @hook  myListingsSqlWhere
     *
     * @param  array $sql  - sql
     * @param  array $type - listing type
     */
    public function hookMyListingsSqlWhere(&$sql, $type)
    {
        if ($type == 'all_ads') {
            $event_key = $this->eventsListingTypesController->getKey();
            $sql .= "AND `T4`.`Type` <> '{$event_key}' ";
        }
    }

    /**
     * @hook  phpListingTypeTop
     */
    public function hookPhpListingTypeTop()
    {
        global $lang, $page_info, $bread_crumbs;

        $eventKey = $this->eventsListingTypesController->getKey();
        if ($page_info['Key'] == 'lt_' . $eventKey) {
            if (!$_SESSION['browse_sort_by']) {
                $_SESSION['browse_sort_by'] = 'event_date';
            }
            if (!$_SESSION['browse_sort_type']) {
                $_SESSION['browse_sort_type'] = 'asc';
            }

            if ($this->eventData['date']) {
                $GLOBALS['listing_type']['Cat_postfix'] = 0;
                $bread_crumbs[] = array(
                    'path' => $page_info['Path'] . '/' . $this->eventData['date'],
                    'title' => $this->eventData['date'],
                    'name' => $this->eventData['date'],
                    'category' => true,
                );

                $GLOBALS['rlSmarty']->assign_by_ref('eventDate', $this->eventData['date']);
            }
        }
    }

    /**
     * @hook  tplAbovePageContent
     * @since 1.1.5
     */
    public function hookTplAbovePageContent()
    {
        if ($this->eventData['date']) {
            $bread_crumbs = $GLOBALS['rlSmarty']->_tpl_vars['bread_crumbs'];
            foreach($bread_crumbs as $key => $val) {
                if ($val['title'] == $this->eventData['date']) {
                    unset($bread_crumbs[$key]);
                    break;
                }
            }
            $GLOBALS['rlSmarty']->assign('bread_crumbs', $bread_crumbs);
        }
    }

    /**
     * @hook  tplListingItemClass
     */
    public function hookTplListingItemClass()
    {
        if ($GLOBALS['rlSmarty']->_tpl_vars['listing']['Event_passed']) {
            echo ' event-passed';
        }
    }

    /**
     * @hook  tplMyListingItemClass
     */
    public function hookTplMyListingItemClass()
    {
        if ($GLOBALS['rlSmarty']->_tpl_vars['listing']['Event_passed']) {
            echo ' event-passed';
        }
    }

    /**
     * @hook  ajaxRequest
     */
    public function hookAjaxRequest(&$out, $request_mode, $request_item, $request_lang)
    {
        if (!$this->isValidAjaxRequest($request_item)) {
            return false;
        }
        $events = new Event();

        switch ($request_item) {
            case 'ev_getEvent':
                $firstDate = $_REQUEST['firstDate'];
                $lastDate = $_REQUEST['lastDate'];
                $month = (int) $_REQUEST['month'];
                $category_id = (int) $_REQUEST['category_id'];

                if (!empty($foundEvents = $events->getByDate($firstDate, $lastDate, $month, $category_id))) {
                    $out['status'] = 'OK';
                    $out['events'] = $foundEvents;
                } else {
                    $out['status'] = 'ERROR';
                    $out['message'] = $GLOBALS['lang']['no_listings_found_deny_posting'];
                }
                break;

            case 'ev_deleteRate':
                $id = $_REQUEST['id'];
                $this->eventRates->deleteRate($id);

                break;
        }
    }

    /**
     * @hook  apAjaxRequest
     * @since 1.1.0
     */
    public function hookApAjaxRequest()
    {
        if (!$this->isValidAjaxRequest($_REQUEST['item'])) {
            return false;
        }

        switch ($_REQUEST['item']) {
            case 'ev_deleteRate':
                $id = $_REQUEST['id'];
                $this->eventRates->deleteRate($id);

                break;
        }
    }

    /**
     * @hook  phpAddListingInitBeforeTypes
     *
     * @param  array $data            - add listing instance
     * @param  array $allow_type_keys - listig type keys
     * @param  array $page_info       - page info
     * @param  array $account_info    - account info
     * @param  array $errors          - errors
     */
    public function hookPhpAddListingInitBeforeTypes(&$data, &$allow_type_keys, $page_info, $account_info, $errors)
    {
        $event_key = $this->eventsListingTypesController->getKey();

        if ($page_info['Key'] == 'add_listing') {
            foreach ($allow_type_keys as $key => $val) {
                if ($val == $event_key && $GLOBALS['rlListingTypes']->types[$event_key]['Add_page']) {
                    unset($allow_type_keys[$key]);
                }
            }
        } else if ($page_info['Key'] == 'al_' . $event_key) {
            $GLOBALS['lang']['add_listing'] = $GLOBALS['lang']['post_an_event'];
            $allow_type_keys = [];
            $allow_type_keys[] = $event_key;
        }
    }

    /**
     * @hook  apPhpConfigBeforeUpdate
     */
    public function hookApPhpConfigBeforeUpdate()
    {
        global $config, $update, $rlDb;

        foreach ($update as $key => $dConfig) {
            if ($dConfig['one_my_listings_page']['value'] != $config['one_my_listings_page']) {
                $eventKey = $this->eventsListingTypesController->getKey();

                $sql = "UPDATE `{db_prefix}pages` SET `Status` = 'active' ";
                $sql .= "WHERE `Controller` = 'my_listings' AND `Key` = 'my_{$eventKey}'";
                $rlDb->query($sql);

                $sql = "UPDATE `{db_prefix}blocks` SET `Status` = 'active' ";
                $sql .= "WHERE `Key` = 'ltma_{$eventKey}'";
                $rlDb->query($sql);
            }
        }
    }

    /**
     * @hook  listingTypesGetAdaptValue
     *
     * @param  array $types         - types
     * @param  array $listing_types - listig types
     */
    public function hookListingTypesGetAdaptValue($types, &$listing_types)
    {
        global $page_info;

        $eventKey = $this->eventsListingTypesController->getKey();
        if ($page_info['Key'] == 'my_all_ads') {
            unset($listing_types[$eventKey]);
        } elseif ($listing_types[$eventKey]) {
            $listing_types[$eventKey]['My_key'] = 'my_' . $eventKey;
        }
    }

    /**
     * @hook  browseTop
     */
    public function hookBrowseTop()
    {
        global $lang, $add_listing_link, $reefless, $page_info, $category, $steps;

        $eventKey = $this->eventsListingTypesController->getKey();
        if ($page_info['Key'] == 'lt_' . $eventKey && !$GLOBALS['listings']) {
            $addKey = $GLOBALS['rlListingTypes']->types[$eventKey]['Add_page'] ? 'al_' . $eventKey : 'add_listing';

            if ($category['ID'] > 0) {
                $add_listing_link = $reefless->getPageUrl(
                    $addKey,
                    ['step' => $steps['plan']['path']],
                    null,
                    "id={$category['ID']}"
                );
            } else {
                $add_listing_link = $reefless->getPageUrl($addKey);
            }

            $GLOBALS['rlSmarty']->assign_by_ref('add_listing_link', $add_listing_link);
            $lang['no_listings_here'] = $lang['no_events_here'];
            $lang['no_listings_found'] = $lang['no_events_found'];
        }
    }

    /**
     * @hook featuredItemTop
     */
    public function hookFeaturedItemTop()
    {
        $this->adapDateField('featured');
    }

    /**
     * @hook listingTop
     */
    public function hookListingTop()
    {
        $this->adapDateField('listing');
    }

    /**
     * @hook myListingTop
     */
    public function hookMyListingTop()
    {
        $this->adapDateField('myListing');
    }

    /**
     * @hook phpPrepareListingsData
     * @param  array $listing     - listing
     * @param  array $out_listing - out listing
     */
    public function hookPhpPrepareListingsData($listing, &$out_listing)
    {
        $eventKey = $this->eventsListingTypesController->getKey();
        if ($listing['Listing_type'] == $eventKey) {
            if ($listing['fields']['event_date']) {
                $d_timestamp = strtotime($listing['event_date']);
                $out = strftime($GLOBALS['config']['ev_date_format'], $d_timestamp);
                if ($listing['event_type'] == '2') {
                    $d_timestamp_multi = strtotime($listing['event_date_multi']);
                    $out .= ' - ' . strftime($GLOBALS['config']['ev_date_format'], $d_timestamp_multi);

                }
                $old_data = $listing['fields']['event_date']['value'];
                $out_listing['info'] = str_replace($old_data, $out, $out_listing['info']);
                foreach ($out_listing['fields_data'] as $key => $value) {
                    if ($value == $old_data) {
                        $out_listing['fields_data'][$key] = $out;
                    }
                }
            }
        }
    }

    /**
     *  Define plugin related boxes and remove not supported box positions
     *  in edit box mode
     *
     *  @hook apPhpBlocksPost
     */
    public function hookApPhpBlocksPost()
    {
        global $block_info;

        if (false === strpos($block_info['Key'], 'event')) {
            return;
        }

        $this->rejectBoxSides();
    }

    /**
     * Remove "integrated_banner" and "header_banner" box positions from the
     * grid cell for plugin rows
     *
     * @hook apTplBlocksBottom
     */
    public function hookApTplBlocksBottom()
    {
        $out = <<< JAVASCRIPT
        $(function(){
            blocksGrid.grid.addListener('beforeedit', function(editEvent){
                if (editEvent.field == 'Side') {
                    var column = editEvent.grid.colModel.columns[2];
                    var removed = false;

                    if (editEvent.record.data.Key.indexOf('event') === 0) {
                        var items = column.editor.getStore().data.items;
                        var items_ids = [];
                        for (var i = 0; i < items.length; i++) {
                            if (['integrated_banner', 'header_banner'].indexOf(items[i].data.field1) >= 0) {
                                items_ids.push(i);
                            }
                        }

                        if (items_ids.length) {
                            for (var i in items_ids.reverse()) {
                                column.editor.getStore().removeAt(items_ids[i])
                            }

                            removed = true;
                        }
                    } else {
                        if (removed) {
                            column.editor = new Ext.form.ComboBox({
                                store: block_sides,
                                displayField: 'value',
                                valueField: 'key',
                                typeAhead: true,
                                mode: 'local',
                                triggerAction: 'all',
                                selectOnFocus: true
                            });
                            removed = false;
                        }
                    }
                }
            });
        });
JAVASCRIPT;

        echo "<script>{$out}</script>";
    }

    /**
     * @hook apTplContentBottom
     * @since 1.1.0
     */
    public function hookApTplContentBottom()
    {
        global $config, $lang, $controller, $rlSmarty, $listing_type;

        $eventKey = $this->eventsListingTypesController->getKey();
        if ($controller == 'listings' && in_array($_GET['action'], array('add', 'edit')) && $listing_type['Key'] == $eventKey) {
            $listing_id = $_GET['id'];

            if ($listing_id) {
                $this->eventRates->postSimulationRates($listing_id);
            }

            // display rates
            $rlSmarty->display(RL_PLUGINS . 'events' . RL_DS . 'view' . RL_DS . 'event_rates_field.tpl');

            echo <<<HTML
            <script type="text/javascript">
                if ($('#group_event_rates').length > 0) {
                    $('input[name="f[{$this->eventData['price_key']}][value]"]').closest('tr').remove();
                    $('#event-rates-master-container').css({'margin-left': '185px'});
                    $('#event-rates-master-container').appendTo($('#group_event_rates'));
                    $('#event-rates-add-field-container').appendTo($('#group_event_rates'));
                    $('#event-rates-master-container,#event-rates-add-field-container').show();
                    eventRates.displayRates();
                }
            </script>
HTML;
        }
    }

    /**
     * @hook  editListingSteps
     *
     * @param  array $instance - edit listing instance
     */
    public function hookEditListingSteps(&$instance)
    {
        global $config, $bread_crumbs, $rlLang;

        $eventKey = $this->eventsListingTypesController->getKey();
        if ($instance->listingType['Key'] == $eventKey) {
            if ($config['one_my_listings_page']) {
                $config['one_my_listings_page'] = 0;
            }

            if ($instance->listingType['Add_page']) {
                $bread_crumbs = array_reverse($bread_crumbs);
                $bread_crumbs[0]['title'] = $bread_crumbs[0]['name'] = $rlLang->getPhrase('edit_event', null, null, true);

                $bread_crumbs[1] = array(
                    'title' => $rlLang->getPhrase('pages+title+my_' . $instance->listingType['Key'], null, null, true),
                    'name' => $rlLang->getPhrase('pages+name+my_' . $instance->listingType['Key'], null, null, true),
                    'path' => $GLOBALS['pages'][$instance->listingType['My_key']],
                );
                $bread_crumbs = array_reverse($bread_crumbs);
            }
        }

    }

    /**
     * @hook  phpCacheGetAfterFetch
     *
     * @param  array $out        - fields
     * @param  array $content    - table
     * @param  array $key        - key
     * @param  array $id         - id
     * @param  array $type       - listing type
     * @param  array $parent_ids - parent_ids
     */
    public function hookPhpCacheGetAfterFetch(&$out, $content, $key, $id, $type, $parent_ids)
    {

        $eventKey = $this->eventsListingTypesController->getKey();
        if ($eventKey == $type['Type'] && $key == 'cache_submit_forms') {
            foreach ($out as $gkey => $group) {
                foreach ($group['Fields'] as $key => $field) {
                    if ($field['Type'] == 'price') {
                        $out[$gkey]['Fields'][$key]['pName'] = 'ev_ticket_price';
                        break;
                    }
                }
            }
        } else if ($key == 'cache_search_forms' && strpos($id, $eventKey) !== false) {
            foreach ($out as $gkey => $group) {
                foreach ($group['Fields'] as $key => $field) {
                    if ($field['Key'] == 'event_date') {
                        $out[$gkey]['Fields'][$key]['Default'] = 'single';
                        break;
                    }
                }
            }
        }
    }

    /**
     * @hook  phpSearchBuildSearchBottom
     *
     * @param  array $out - fields
     * @param  array $key - $key
     */
    public function hookPhpSearchBuildSearchBottom(&$out, $key)
    {
        $eventKey = $this->eventsListingTypesController->getKey();
        if (strpos($key, $eventKey) !== false) {
            foreach ($out as $gkey => $group) {
                foreach ($group['Fields'] as $key => $field) {
                    if ($field['Key'] == 'event_date') {
                        $out[$gkey]['Fields'][$key]['Default'] = 'single';
                        break;
                    }
                }
            }
        }
    }

    /**
     * @hook  phpCommonFieldValuesAdaptationBottom
     *
     * @param  array $fields       - fields
     * @param  array $table        - table
     * @param  array $listing_type - listing type
     */
    public function hookPhpCommonFieldValuesAdaptationBottom(&$fields, $table, $listing_type)
    {
        $eventKey = $this->eventsListingTypesController->getKey();

        if ($eventKey == $listing_type) {
            foreach ($fields as $key => &$field) {
                if ($field['Type'] == 'price') {
                    $field['pName'] = 'ev_ticket_price';
                    break;
                }
            }
        }
    }

    /**
     * @hook  listingDetailsBottom
     */
    public function hookListingDetailsBottom()
    {
        global $config, $listing_data, $listing, $bread_crumbs, $page_info;
        // Preview listing plugin
        if ($GLOBALS['addListing'] && $GLOBALS['addListing']->listingData) {
            $listing_data = $GLOBALS['addListing']->listingData;
            $listing_data['Listing_type'] = $GLOBALS['addListing']->listingType['Key'];
            $listing = $GLOBALS['rlSmarty']->_tpl_vars['listing'];
        }

        $eventKey = $this->eventsListingTypesController->getKey();

        if ($eventKey == $listing_data['Listing_type']) {
            $evDate = $listing_data[$listing_data['event_type'] == '1' ? 'event_date' : 'event_date_multi'];

            if (strtotime($evDate) < strtotime(date("Y-m-d"))) {
                $past_phrase = $GLOBALS['lang']['past'] . ' - ';
                $page_info['name'] = $page_info['name'] ? $past_phrase . $page_info['name']: '';
                $page_info['title'] = $page_info['title'] ? $past_phrase . $page_info['title'] : '';
            }

            $d_timestamp = strtotime($listing_data['event_date']);
            $out = strftime($config['ev_date_format'], $d_timestamp);
            if ($listing_data['event_type'] == '2') {
                $d_timestamp_multi = strtotime($listing_data['event_date_multi']);
                $out .= ' - ' . strftime($config['ev_date_format'], $d_timestamp_multi);

            }

            // Add date to title in the breadcrumbs
            $last_key = end(array_keys($bread_crumbs));
            foreach ($bread_crumbs as $key => $item) {
                if (strpos($item['title'], 'if date') !== false) {
                    $bread_crumbs[$key]['title'] = preg_replace("/\{if date\}(.*?)\{\/if\}/smi", '', $item['title']);
                }

                if ($key == $last_key) {
                    $bread_crumbs[$key]['title'] .= ' | ' . $out;
                }
            }

            $priceKey = '';
            // Convert event date
            foreach ($listing as $gKey => &$group) {
                foreach ($group['Fields'] as $fKey => &$field) {
                    if ($fKey == 'event_date') {
                        $field['value'] = $out;
                    } else if ($group['Key'] == 'event_rates' && $field['Type'] == 'price') {
                        $priceKey = $field['Key'];
                    } else if ($group['Key'] == 'event_rates'
                        && $field['Key'] == 'event_price_type'
                        && !$listing_data['event_price_type']) {
                        $field['Details_page'] = 0;
                    }
                }
            }

            $rates = $this->eventRates->getDetailsRates($listing_data['ID']);
            if ($rates && !$listing_data['event_price_type']) {
                $listing['event_rates']['Fields'] = array_merge($rates, $listing['event_rates']['Fields']);
            }

            if ($listing_data['event_price_type'] || !$listing_data[$config['price_tag_field']]) {
                $GLOBALS['rlSmarty']->assign('price_tag_value', $GLOBALS['lang']['free']);
            }
            $GLOBALS['rlSmarty']->assign('listing', $listing);
        }
    }

    /**
     * @hook apPhpListingsView
     *
     * @since 1.1.0
     */
    public function hookApPhpListingsView()
    {
        global $listing_data, $listing, $config;

        $eventKey = $this->eventsListingTypesController->getKey();
        if ($eventKey == $listing_data['Listing_type']) {
            $this->eventRates->manageRates($instance->listingID, $data, $instance->formFields);

            $evDate = $listing_data[$listing_data['event_type'] == '1' ? 'event_date' : 'event_date_multi'];

            $d_timestamp = strtotime($listing_data['event_date']);
            $out = strftime($config['ev_date_format'], $d_timestamp);
            if ($listing_data['event_type'] == '2') {
                $d_timestamp_multi = strtotime($listing_data['event_date_multi']);
                $out .= ' - ' . strftime($config['ev_date_format'], $d_timestamp_multi);
            }

            $priceKey = '';
            // Convert event date
            foreach ($listing as $gKey => &$group) {
                foreach ($group['Fields'] as $fKey => &$field) {
                    if ($fKey == 'event_date') {
                        $field['value'] = $out;
                    } else if ($group['Key'] == 'event_rates' && $field['Type'] == 'price') {
                        $priceKey = $field['Key'];
                    } else if ($group['Key'] == 'event_rates'
                        && $field['Key'] == 'event_price_type'
                        && !$listing_data['event_price_type']) {
                        $field['Details_page'] = 0;
                    }
                }
            }

            $rates = $this->eventRates->getDetailsRates($listing_data['ID']);
            if ($rates && !$listing_data['event_price_type']) {
                $listing['event_rates']['Fields'] = array_merge($rates, $listing['event_rates']['Fields']);
            }
            $GLOBALS['rlSmarty']->assign('listing', $listing);
        }
    }

    /**
     * @hook  myListingsPreSelect
     */
    public function hookMyListingsPreSelect()
    {
        global $lang, $add_listing_href, $reefless, $config, $listings_type, $listing_types;

        $eventKey = $this->eventsListingTypesController->getKey();

        if ($config['one_my_listings_page']) {
            if ($listings_type['Key'] == $eventKey) {
                $config['one_my_listings_page'] = 0;
            }
        }

        if ($GLOBALS['page_info']['Key'] == 'my_' . $eventKey && !$GLOBALS['listings']) {
            $addKey = $listings_type['Add_page'] ? 'al_' . $eventKey : 'add_listing';
            $add_listing_href = $reefless->getPageUrl($addKey);
            $GLOBALS['rlSmarty']->assign_by_ref('add_listing_href', $add_listing_href);
            $lang['no_listings_here'] = $lang['no_events_here'];
            $lang['no_listings_found'] = $lang['no_events_found'];
        }
    }

    /**
     * @hook  addListingPreFields
     */
    public function hookAddListingPreFields()
    {
        $this->methodListingPreFields();
    }

    /**
     * @hook  addListingFormDataChecking
     *
     * @param  array $instance     - instance
     * @param  array $data         - data
     * @param  array $errors       - errors
     * @param  array $error_fields - error_fields
     */
    public function hookAddListingFormDataChecking($instance, $data, &$errors, &$error_fields)
    {
        $this->checkFormPriceField($instance->listingType, $instance->formFields, $data, $errors, $error_fields);
    }

    /**
     * @hook  editListingDataChecking
     *
     * @param  array $instance     - instance
     * @param  array $data         - data
     * @param  array $errors       - errors
     * @param  array $error_fields - error_fields
     */
    public function hookEditListingDataChecking($instance, $data, &$errors, &$error_fields)
    {
        $this->checkFormPriceField($instance->listingType, $instance->formFields, $data, $errors, $error_fields);
    }

    /**
     * @hook  apPhpListingsValidate
     */
    public function hookApPhpListingsValidate($instance, $data, &$errors, &$error_fields)
    {
        global $listing_type, $data, $errors, $error_fields;
        if (!$listing_type) {
            $listing_type = $GLOBALS['rlListingTypes']->types[$GLOBALS['listing']['Listing_type']];
        }
        $this->checkFormPriceField($listing_type, $GLOBALS['rlCategories']->fields, $data, $errors, $error_fields);
    }

    /**
     * @hook smartyCustomFieldHandler
     *
     * @since 1.1.0
     *
     * @param array $field      - field data
     * @param bool  $use_custom - did we replace the default field tempate with custom or not
     */
    public function hookSmartyCustomFieldHandler(&$field, &$use_custom)
    {
        $group = $GLOBALS['rlSmarty']->_tpl_vars['group'];

        if ($group['Key'] == 'event_rates' && $field['Key'] == $this->eventData['price_key']) {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'events' . RL_DS . 'view' . RL_DS . 'event_rates_field.tpl');
            $use_custom = true;
        }
    }

    /**
     * @hook afterListingCreate
     *
     * @since 1.1.0
     *
     * @param array $instance - add listing instance
     * @param array $info     - info
     * @param array $data     - data
     */
    public function hookAfterListingCreate($instance, $info, $data)
    {
        $eventKey = $this->eventsListingTypesController->getKey();
        if ($eventKey == $instance->listingType['Key']) {
            $this->eventRates->manageRates($instance->listingID, $data, $instance->formFields);
        }
    }

    /**
     * @hook afterListingUpdate
     *
     * @since 1.1.0
     *
     * @param array $instance - add listing instance
     * @param array $info     - info
     * @param array $data     - data
     */
    public function hookAfterListingUpdate($instance, $info, $data)
    {
        $eventKey = $this->eventsListingTypesController->getKey();
        if ($eventKey == $instance->listingType['Key']) {
            $this->eventRates->manageRates($instance->listingID, $data, $instance->formFields);
        }
    }

    /**
     * @hook afterListingEdit
     *
     * @since 1.1.0
     *
     * @param array $instance - add listing instance
     * @param array $info     - info
     * @param array $data     - data
     */
    public function hookAfterListingEdit($instance, $info, $data)
    {
        $eventKey = $this->eventsListingTypesController->getKey();
        if ($eventKey == $instance->listingType['Key']) {
            $this->eventRates->manageRates($instance->listingID, $data, $instance->formFields);
        }
    }

    /**
     * @hook addListingAdditionalInfo
     *
     * @since 1.1.4
     *
     * @param array $instance
     * @param array $info
     * @param array $data
     * @param array $plan_info
     */
    public function hookAddListingAdditionalInfo($instance, $info, &$data, $plan_info)
    {
        $eventKey = $this->eventsListingTypesController->getKey();
        if ($eventKey == $instance->listingType['Key']) {
            if ($data['event_price_type']) {
                $priceKey = $GLOBALS['config']['price_tag_field'];
                $data[$priceKey]['option'] = 'price_options_free';
            }
        }
    }

    /**
     * @hook editListingAdditionalInfo
     *
     * @since 1.1.4
     *
     * @param array $instance
     * @param array $data
     * @param array $info
     */
    public function hookEditListingAdditionalInfo($instance, &$data, $info)
    {
        $eventKey = $this->eventsListingTypesController->getKey();
        if ($eventKey == $instance->listingType['Key']) {
            if ($data['event_price_type']) {
                $priceKey = $GLOBALS['config']['price_tag_field'];
                $data[$priceKey]['option'] = 'price_options_free';
            }
        }
    }

    /**
     * @hook apPhpListingsAfterAdd
     *
     * @since 1.1.0
     */
    public function hookApPhpListingsAfterAdd()
    {
        global $listing_id, $data, $category_fields, $listing_type;

        $eventKey = $this->eventsListingTypesController->getKey();
        if ($eventKey == $listing_type['Key']) {
            $this->eventRates->manageRates($listing_id, $data, $category_fields);
        }
    }

    /**
     * @hook apPhpListingsAfterEdit
     *
     * @since 1.1.0
     */
    public function hookApPhpListingsAfterEdit()
    {
        global $listing_id, $data, $listing_fields, $listing_type;

        $eventKey = $this->eventsListingTypesController->getKey();
        if ($eventKey == $listing_type['Key']) {
            $this->eventRates->manageRates($listing_id, $data, $listing_fields);
        }
    }

    /**
     * @hook addListingPostSimulation
     *
     * @since 1.1.0
     */
    public function hookAddListingPostSimulation($instance)
    {
        $eventKey = $this->eventsListingTypesController->getKey();
        if ($eventKey == $instance->listingType['Key']) {
            $this->eventRates->postSimulationRates($instance->listingID);
        }
    }

    /**
     * @hook editListingPostSimulation
     *
     * @since 1.1.0
     */
    public function hookEditListingPostSimulation($instance)
    {
        $eventKey = $this->eventsListingTypesController->getKey();
        if ($eventKey == $instance->listingType['Key']) {
            $this->eventRates->postSimulationRates($instance->listingID);
        }
    }

    /**
     * @hook  apTplListingTypesBottom
     */
    public function hookApTplListingTypesBottom()
    {
        $event_key = $this->eventsListingTypesController->getKey();
        echo <<< FL
<script type="text/javascript">
    $(document).ready(function(){
        var store = listingTypesGrid.grid.getStore();
        store.on('load', function(){
            $("#grid").find("img.remove").each(function(){
                if ($(this).attr('onclick')=='xajax_prepareDeleting("{$event_key}")') {
                    $(this).hide();
                }
            });
        });
    });
</script>
FL;
    }

    /**
     * @hook apTplFieldsDate
     */
    public function hookApTplFieldsDate()
    {
        if ($_GET['field'] == 'event_date') {
            echo <<< FL
<script type="text/javascript">
    $(document).ready(function(){
       $('#field_date').hide();
    });
</script>
FL;
        }
    }

    /**
     * @hook  apTplListingsFormAdd
     */
    public function hookApTplListingsFormAdd()
    {
        echo '<script type="text/javascript" src="' . RL_PLUGINS_URL . 'events/static/add_edit_listing.js"></script>';
        $this->methodListingPreFields(true);
    }

    /**
     * @hook  apTplListingsFormEdit
     */
    public function hookApTplListingsFormEdit()
    {
        if (!$GLOBALS['listing_type']) {
            $GLOBALS['listing_type'] = $GLOBALS['rlListingTypes']->types[$GLOBALS['listing']['Listing_type']];
        }

        echo '<script type="text/javascript" src="' . RL_PLUGINS_URL . 'events/static/add_edit_listing.js"></script>';
        $this->methodListingPreFields(true);
    }

    /**
     * @hook  apPhpIndexBottom
     */
    public function hookApPhpIndexBottom()
    {
        if ($_SESSION['rebuildEventCache']) {
            $GLOBALS['rlCache']->update();
            unset($_SESSION['rebuildEventCache']);
        }
    }

    /**
     * @hook  apPhpPagesAfterEdit
     */
    public function hookApPhpPagesAfterEdit()
    {
        global $update_data, $allLangs;

        $eventKey = $this->eventsListingTypesController->getKey();
        if ($update_data['where']['Key'] == 'lt_' . $eventKey) {
            $paths[] = $update_data['fields']['Path'];
            if ($GLOBALS['config']['multilingual_paths']) {
                foreach ($allLangs as $langKey => $langData) {
                    if ($update_data['fields']['Path_' . $langKey]) {
                        $paths[] = $update_data['fields']['Path_' . $langKey];
                    }
                }
            }

            if ($paths) {
                $GLOBALS['rlConfig']->setConfig('ev_path', implode(',', $paths));
            }
        }
    }

    /**
     * Add event inline styles
     *
     * @hook tplHeader
     */
    public function hookTplHeader()
    {
        $eventKey = $this->eventsListingTypesController->getKey();
        if (in_array($GLOBALS['page_info']['Key'], ['lt_' . $eventKey, 'my_' . $eventKey])
            || in_array($GLOBALS['page_info']['Controller'], ['edit_listing', 'add_listing'])
            && version_compare($GLOBALS['config']['rl_version'], '4.8.1') <= 0) {
            $GLOBALS['rlSmarty']->display($this->configs['path']['view'] . 'header.tpl');
        }
    }

    /**
     * Add event inline styles
     *
     * @hook apTplHeader
     * @since 1.1.0
     */
    public function hookApTplHeader()
    {
        $eventKey = $this->eventsListingTypesController->getKey();
        if ($_GET['controller'] == 'listings' && in_array($_GET['action'], array('add', 'edit'))
            && ($GLOBALS['listing_type']['Key'] == $eventKey || $GLOBALS['listing']['Listing_type'] == $eventKey)) {
            $GLOBALS['rlSmarty']->display($this->configs['path']['view'] . 'header.tpl');
        }
    }

    /**
     * Add date in url
     *
     * @hook phpBuildPagingTemplate
     *
     * @param $add_url         - add url - e.g. category path
     *
     */
    public function hookPhpBuildPagingTemplate(&$add_url)
    {
        $eventKey = $this->eventsListingTypesController->getKey();

        if ($GLOBALS['page_info']['Key'] == 'lt_' . $eventKey && $this->eventData['date']) {
            $add_url = $this->eventData['date'];
        }
    }

    /**
     * @hook  listingsModifyPreSelect
     */
    public function hookListingsModifyPreSelect(&$dbcount)
    {
        $eventKey = $this->eventsListingTypesController->getKey();
        if ($GLOBALS['page_info']['Key'] == 'lt_' . $eventKey) {
            $dbcount = false;
        }
    }

    /**
     * @hook  cronAdditional
     * @since 1.1.4
     */
    public function hookCronAdditional()
    {
        global $rlDb, $rlCategories;

        $event_key = $this->eventsListingTypesController->getKey();
        if ($event_key && !$GLOBALS['config']['ev_show_passed_events']) {

            $dateToday = strtotime(date('Y-m-d'));

            $sql = "SELECT `T1`.*, `T2`.`Lang`, `T3`.`Type` AS `Listing_type`, ";
            $sql .= "`T2`.`Mail`, `T2`.`First_name`, `T2`.`Last_name`, `T2`.`Username`, `T3`.`Path` ";
            $sql .= "FROM `{db_prefix}listings` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Account_ID` = `T2`.`ID` ";
            $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";

            $sql .= "WHERE `T3`.`Type` = '{$event_key}' AND ";
            $sql .= "IF (`T1`.`event_type` = '1', ";
            $sql .= "UNIX_TIMESTAMP( `T1`.`event_date` ) <= {$dateToday}, ";
            $sql .= "UNIX_TIMESTAMP( `T1`.`event_date_multi` ) <= {$dateToday}) ";

            $sql .= "LIMIT {$GLOBALS['config']['listings_number']} ";

            $listings = $rlDb->getAll($sql);

            if ($listings) {
                foreach ($listings as $key => $listing) {

                    // Update listing
                    $updateOne = [
                        'fields' => [
                            'Pay_date' => '',
                            'Status' => 'expired',
                        ],
                        'where'  => [
                            'ID' => $listing['ID']
                        ],
                    ];
                    $rlDb->updateOne($updateOne, 'listings');

                    // Recount categories
                    if (!empty($listing['Crossed'])) {
                        $crossed_cats = explode(',', trim($listing['Crossed'], ','));
                        foreach ($crossed_cats as $crossed_cat_id) {
                            $rlCategories->listingsDecrease($crossed_cat_id, null, false);
                        }
                    }
                    $rlCategories->listingsDecrease($listing['Category_ID'], $listing['Type']);
                    $rlCategories->accountListingsDecrease($listing['Account_ID']);

                    // Send email
                    $GLOBALS['reefless']->loadClass('Mail');
                    $mailTpl = $GLOBALS['rlMail']->getEmailTemplate('events_expired_past_events');

                    $username = trim(
                        $listing['First_name'] || $listing['Last_name']
                        ? $listing['First_name'] . ' ' . $listing['Last_name']
                        : $listing['Username']
                    );
                    $details_link = $GLOBALS['reefless']->getListingUrl($listing, $listing['Lang']);

                    $mFind = array('{name}', '{details_link}');
                    $mReplace = array(
                        $username,
                        $details_link
                    );

                    $mailTpl['body'] = str_replace($mFind, $mReplace, $mailTpl['body']);
                    $GLOBALS['rlMail']->send($mailTpl, $listing['Mail']);
                }
            }
        }
    }

    /**
     * @hook  methodListingPreFields
     */
    public function methodListingPreFields($admin = false)
    {
        global $rlSmarty;

        $eventKey = $this->eventsListingTypesController->getKey();
        $listing_type = $rlSmarty->_tpl_vars['listing_type'] ? $rlSmarty->_tpl_vars['listing_type'] : $GLOBALS['listing_type'];

        if ($listing_type['Key'] == $eventKey) {
            $form = $rlSmarty->_tpl_vars['form'];

            foreach ($form as $gkey => $group) {
                foreach ($group['Fields'] as $key => $field) {
                    if ($field['Type'] == 'price') {
                        $form[$gkey]['Fields'][$key]['Required'] = $GLOBALS['config']['ev_price_required'];
                        $this->eventData['price_key'] = $field['Key'];
                        break;
                    }
                }
            }
            $GLOBALS['rlSmarty']->assign_by_ref('form', $form);
        }

        echo <<< FL
            <script class="fl-js-dynamic">
                $(function(){
                   rlConfig['admin'] = '$admin';
                   setDatePeriodEvents($('input[name="f[event_type]"]:checked').val());
                });
            </script>
FL;
    }

    /**
     * Check form price field
     *
     * @param  array $listing_type - Listing type
     * @param  array $formFields   - Form fields
     * @param  array $data         - Form data
     * @param  array $errors       - Errors
     * @param  array $error_fields - Error fields
     */
    public function checkFormPriceField($listing_type, $formFields, $data, &$errors, &$error_fields)
    {
        $eventKey = $this->eventsListingTypesController->getKey();
        if ($listing_type['Key'] == $eventKey) {

            if ($data['event_price_type'] === '0') {

                $errors_trigger = false;
                $fieldError = [];

                if (!$data['event_rates'] && $data['event_price_type'] == '0') {
                    $errors_trigger = true;
                } else {
                    foreach ($data['event_rates'] as $index => $entry) {
                        if ($entry['rate'] == "-1") {
                            $fieldError[] = "f[event_rates][{$index}][rate]";
                            $errors_trigger = true;
                        }

                        if (empty($entry['price']) && $entry['price'] != '0') {
                            $fieldError[] = "f[event_rates][{$index}][price]";
                            $errors_trigger = true;
                        }
                        if ($entry['rate'] == '*cust0m*' && empty($entry['custom_rate'])) {
                            $fieldError[] = "f[event_rates][{$index}][custom_rate]";
                            $errors_trigger = true;
                        }
                    }
                }

                if ($errors_trigger) {
                    $errors[] = str_replace(
                        '{field}',
                        '<span class="field_error">"' . $GLOBALS['rlLang']->getPhrase('listing_groups+name+event_rates') . '"</span>',
                        $GLOBALS['rlLang']->getPhrase('notice_field_empty')
                    );
                    if (defined('REALM')) {
                        $error_fields = $error_fields && $fieldError ? array_merge($error_fields, $fieldError) : $fieldError;
                    } else {
                        $error_fields .= implode(",", $fieldError);
                    }
                }
            } else {
                foreach ($formFields as $field) {
                    if ($field['Type'] == 'price' && $GLOBALS['config']['ev_price_required']) {

                        $fieldError = ["f[{$field['Key']}][value]", "f[{$field['Key']}]"];
                        $msgError = str_replace('{field}', '<span class="field_error">"' . $field['name'] . '"</span>', $GLOBALS['lang']['notice_field_empty']);

                        $value = trim($data[$field['Key']]['value']);

                        if ($GLOBALS['config']['ev_price_required'] && empty($value)) {
                            $errors[] = $msgError;

                            if (defined('REALM')) {
                                $error_fields = $error_fields && $fieldError ? array_merge($error_fields, $fieldError) : $fieldError;
                            } else {
                                $error_fields .= implode(",", $fieldError);
                            }

                        } else if (!$GLOBALS['config']['ev_price_required'] && in_array($msgError, $errors)) {
                            $key = array_search($msgError, $errors);
                            unset($errors[$key]);

                            if (defined('REALM')) {
                                $key = array_search($fieldError[0], $error_fields);
                                $key1 = array_search($fieldError[1], $error_fields);
                                unset($error_fields[$key], $error_fields[$key1]);

                            } else {
                                $error_fields = str_replace($fieldError, '', $error_fields);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     *  Get counts of event categories
     *
     * @param String $date - date
     *
     * return $count
     */
    public function getCatCounts($date)
    {
        $where = "";
        if ($GLOBALS['plugins']['listing_status']) {
            $where = "AND `Sub_status` <> 'invisible' ";
        }

        $sql = "SELECT `Category_ID`, COUNT(*) as `Count`
            FROM `{db_prefix}listings`
            WHERE `Status` = 'active'
            AND IF (`event_type` = '1',
            `event_date` = '{$date}',
            '{$date}' BETWEEN `event_date` AND `event_date_multi`)
            {$where}
            GROUP BY `Category_ID` ";
        $counts = $GLOBALS['rlDb']->getAll($sql, 'Category_ID');

        return $counts;
    }

    /**
     *  Replace date in phrase
     *
     * @param String $phrase - phrase
     */
    public function prepareDatePhrase(&$phrase)
    {
        $replace_data = $this->eventData['date'] ? strftime($GLOBALS['config']['ev_date_format'], strtotime($this->eventData['date'])) : '';
        $phrase = str_replace('{date}', $replace_data, $phrase);
        $phrase = preg_replace(
            "/\{if date\}(.*?)\{\/if\}/smi",
            $replace_data ? '$1' : '',
            $phrase
        );
    }

    /**
     *  Adapt single event date field
     *
     * @param String $mode - mode
     */
    public function adapDateField($mode)
    {
        global $config;

        $eventKey = $this->eventsListingTypesController->getKey();

        $varKey = $mode == 'featured' ? 'featured_listing' : 'listing';

        $listing = $GLOBALS['rlSmarty']->_tpl_vars[$varKey];

        if ($listing['Listing_type'] == $eventKey) {
            // Add to title Past on my event page
            if ($mode == 'myListing' && $listing['Event_passed']) {
                $listing['listing_title'] = $GLOBALS['lang']['past'] . ' - ' . $listing['listing_title'];
            }

            if ($listing['fields']['event_date']) {
                $d_timestamp = strtotime($listing['event_date']);
                $out = strftime($GLOBALS['config']['ev_date_format'], $d_timestamp);
                if ($listing['event_type'] == '2') {
                    $d_timestamp_multi = strtotime($listing['event_date_multi']);
                    $out .= ' - ' . strftime($GLOBALS['config']['ev_date_format'], $d_timestamp_multi);
                }
                $listing['fields']['event_date']['value'] = $out;
            }

            //  set free if price is empty
            if ($listing['event_price_type'] || !$listing[$config['price_tag_field']]) {
                $listing['fields'][$config['price_tag_field']]['value'] = $GLOBALS['lang']['free'];
            }

            if ($mode == 'featured') {
                $GLOBALS['rlSmarty']->assign_by_ref('featured_listing', $listing);
            } else {
                $GLOBALS['rlSmarty']->assign_by_ref('listing', $listing);
            }
        }
    }

    /**
     *  Remove not supported box positions for plugin related boxes
     */
    public function rejectBoxSides()
    {
        global $l_block_sides;

        foreach ($this->rejectedBoxSides as $side) {
            unset($l_block_sides[$side]);
        }
    }

    /**
     *  ValidateDate
     */
    public function validateDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    public function isValidAjaxRequest($ajaxRequestMode)
    {
        $validRequests = array(
            'ev_getEvent',
            'ev_deleteRate',
        );

        return in_array($ajaxRequestMode, $validRequests);
    }

    /**
     * Display calendar block
     */
    public function showEventsCalendar()
    {
        $GLOBALS['rlSmarty']->display($this->configs['path']['view'] . 'calendar.tpl');
    }

    /**
     * Remove events
     */
    public function removeListingsByType($listing_type)
    {
        global $rlDb;

        if (!$listing_type) {
            return;
        }

        $sql = "SELECT `T3`.* ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_photos` AS `T3` ON `T1`.`ID` = `T3`.`Listing_ID` ";
        $sql .= "WHERE `T2`.`Type` = '{$listing_type}' AND `T3`.`Original` != 'youtube' GROUP BY `T1`.`ID` ";
        $media = $rlDb->getAll($sql);

        if ($media) {
            foreach ($media as $info) {
                if ($info['Original']) {
                    ListingMedia::removeEmptyDir(RL_FILES . dirname($info['Original']), true);
                }
            }

            $sql = "DELETE `T3` ";
            $sql .= "FROM `{db_prefix}listings` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
            $sql .= "LEFT JOIN `{db_prefix}listing_photos` AS `T3` ON `T1`.`ID` = `T3`.`Listing_ID` ";
            $sql .= "WHERE `T2`.`Type` = '{$listing_type}'";
            $rlDb->query($sql);
        }

        // Remove listings_shows
        $sql = "DELETE `T3` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listings_shows` AS `T3` ON `T3`.`Listing_ID` = `T1`.`ID` ";
        $sql .= "WHERE `T2`.`Type` = '{$listing_type}' ";
        $rlDb->query($sql);

        // Remove favorites
        $sql = "DELETE `T3` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}favorites` AS `T3` ON `T3`.`Listing_ID` = `T1`.`ID` ";
        $sql .= "WHERE `T2`.`Type` = '{$listing_type}' ";
        $rlDb->query($sql);

        // Remove tmp_categories
        $sql = "DELETE `T3` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}tmp_categories` AS `T3` ON `T3`.`Listing_ID` = `T1`.`ID` ";
        $sql .= "WHERE `T2`.`Type` = '{$listing_type}' ";
        $rlDb->query($sql);

        // Remove listings
        $sql = "DELETE `T1` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T2`.`Type` = '{$listing_type}' ";
        $rlDb->query($sql);
    }

    /**
     * Plugin install
     */
    public function install()
    {
        $this->importData = json_decode(file_get_contents($this->urlToData), true);

        $this->eventsListingTypesController->add();
        $this->eventTypeCategory->addCategories($this->importData['categories']);
        $this->eventTypeCategory->buildCategory($this->importData);

        $this->eventRates->createRates($this->importData);

        $this->eventsListingTypesController->updateBoxes();

        $_SESSION['rebuildEventCache'] = true;
    }

    /**
     * Plugin uninstall
     */
    public function uninstall()
    {
        $this->importData = json_decode(file_get_contents($this->urlToData), true);

        $eventKey = $this->eventsListingTypesController->getKey();
        if ($eventKey) {
            // Remove listings by type
            $this->removeListingsByType($eventKey);

            $this->eventTypeCategory->removeCategories($eventKey);

            // remove event type
            $this->eventsListingTypesController->remove($eventKey);

            // remove listing fields
            $this->eventTypeCategory->removeLFields($this->importData);
        }
    }

    /**
     * Update to 1.1.0
     */
    public function update110()
    {
        global $rlDb, $config;

        $filesystem = new \Flynax\Component\Filesystem();
        $filesystem->remove(RL_PLUGINS . 'events/vendor/');
        $symfonyFilesystem = new \Symfony\Component\Filesystem\Filesystem();
        $symfonyFilesystem->mirror(RL_UPLOAD . 'events/vendor/', RL_PLUGINS . 'events/vendor/');

        @unlink(RL_PLUGINS . '/events/src/functions.php');

        $this->importData = json_decode(file_get_contents($this->urlToData), true);
        $this->eventRates->createRates($this->importData);

        $rlDb->query("
            INSERT INTO `{db_prefix}listing_groups`
            (`Key`, `Display`, `Status`)
            VALUES
            ('event_rates', '1', 'active')
        ");
        $groupID = $rlDb->insertID();

        $insertField = array(
            'Key' => 'event_price_type',
            'Type' => 'bool',
            'Details_page' => '1',
            'Required' => 0,
        );
        $rlDb->insertOne($insertField, 'listing_fields');
        $fieldID = $rlDb->insertID();
        $rlDb->addColumnToTable('event_price_type', "ENUM('0','1') NULL DEFAULT '0'", 'listings');

        // get price ID
        if ($config['price_tag_field']) {
            $priceID = $rlDb->getOne('ID', "`Key` = '{$config['price_tag_field']}' AND `Status` = 'active'", 'listing_fields');
        }
        if (!$priceID) {
            $priceID = $rlDb->getOne('ID', "`Type` = 'price' AND `Status` = 'active'", 'listing_fields');
        }

        $eventKey = $this->eventsListingTypesController->getKey();
        $generalCatID = $GLOBALS['rlListingTypes']->types[$eventKey]['Cat_general_cat'];
        $updateData = array(
            'fields' => array(
                'Group_ID' => $groupID,
                'Fields' => $fieldID . ',' . $priceID,
            ),
            'where' => array(
                'Category_ID' => $generalCatID,
                'Fields' => $priceID,
            ),
        );
        $rlDb->updateOne($updateData, 'listing_relations');

        $_SESSION['rebuildEventCache'] = true;
    }

    /**
     * Update to 1.1.2
     */
    public function update112()
    {
        global $rlDb;
        $GLOBALS['reefless']->deleteDirectory(RL_PLUGINS . 'events/vendor/');
        $symfonyFilesystem = new \Symfony\Component\Filesystem\Filesystem();
        $symfonyFilesystem->mirror(RL_UPLOAD . 'events/vendor/', RL_PLUGINS . 'events/vendor/');

        if (!$rlDb->columnExists('price_event', 'listings')) {
            $rlDb->addColumnToTable('price_event', "VARCHAR(80) NOT NULL ", 'listings');
        }
    }

    /**
     * Update to 1.1.4
     */
    public function update114()
    {
        if (!$this->eventsListingTypesController->getKey()) {
            $this->install();
        }
    }

    /**
     *  Update to 1.1.5
     */
    public function update115()
    {
        $GLOBALS['rlDb']->query(
            "DELETE FROM `{db_prefix}hooks`
            WHERE `Plugin` = 'events' AND `Name` = 'smartyFetchHook'"
        );
    }

    /**
     * @hook smartyFetchHook
     *
     * @deprecated 1.1.5
     */
    public function hookSmartyFetchHook(&$compiled_content, &$resource_name)
    {
    }
}
