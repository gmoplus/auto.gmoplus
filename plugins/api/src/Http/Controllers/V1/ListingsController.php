<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: LISTINGSCONTROLLER.PHP
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

use Flynax\Api\Http\Controllers\V1\GeoLocationController;
use Flynax\Utils\Category;
use Flynax\Utils\ListingMedia;

class ListingsController extends BaseController
{
    /**
     * Get home listings
     *
     * @return array - listings information
     **/
    public function getHomeListings()
    {
        if ($GLOBALS['plugins']['multiField'] && rl('GeoFilter', null, 'multiField')->geo_format) {
            $geoLocationController = new GeoLocationController();
            $geoLocationController->appliedLocation('home');
        }

        $listingType = $GLOBALS['config']['app_manager_main_listing_type'];

        if ($GLOBALS['config']['app_manager_home_page_listings'] == 'featured') {
            $listings = rl('Listings')->getFeatured($listingType, $GLOBALS['config']['app_manager_grid_listings_number'], null, null, null);
        }
        else {
            $listings = rl('Listings')->getRecentlyAdded(0, $GLOBALS['config']['app_manager_grid_listings_number'], $listingType);

        }
        // Build photos url
        $this->buildPhotosUrl($listings);

        return $listings;
    }

    /**
     * Get listings
     *
     * @return array - listings
     **/
    public function getListingsByCategory()
    {

        $listingType = $_REQUEST['type'] ? $_REQUEST['type'] : '';
        if (!$listingType) {
            return;
        }

        if ($GLOBALS['plugins']['multiField'] && rl('GeoFilter', null, 'multiField')->geo_format) {
            $geoLocationController = new GeoLocationController();
            $geoLocationController->appliedLocation('lt_' . $listingType);
        }

        $category_id = $_REQUEST['category_id'] ? $_REQUEST['category_id'] : 0;
        if ($category_id) {
            $GLOBALS['category'] = Category::getCategory($category_id);
        }

        $start = $_REQUEST['start'] ? $_REQUEST['start'] : 1;
        $limit = $GLOBALS['config']['app_manager_grid_listings_number'];
        $order = 'date';
        $order_type = 'DESC';

        if (CategoryFilterController::isActive()) {
            CategoryFilterController::initFor(CategoryFilterController::FILTER_LISTING_TYPE);
            if (false !== $filterBoxId = CategoryFilterController::fetchBoxId($listingType, $category_id)) {
                $filters = json_decode($_POST['filters'], true);

                CategoryFilterController::setBoxId($filterBoxId);
                CategoryFilterController::prepareFilters($filters);
            }
        }

        $out['listings'] = rl('Listings')->getListings($category_id, $order, $order_type, $start, $limit, $listingType);
        $out['count'] = rl('Listings')->calc;

        if ($start == 1) {
            $out['categories'] = rl('Categories')->getCategories($category_id, $listingType, false, false);
        }

        // Get Filters
        if (CategoryFilterController::isActive()) {
            $filterFields = CategoryFilterController::applyFiltersToResponse();
            if ($filterFields) {
                $out['filters'] = $filterFields;
            }
        }

        // Build photos url
        $this->buildPhotosUrl($out['listings']);

        return $out;
    }

    /**
     * Get listings by account
     *
     * @return array - listings
     **/
    public function getListingsByAccount()
    {
        $account_id = $_REQUEST['account_id'];
        $start = $_REQUEST['start'] ? $_REQUEST['start'] : 1;
        $limit = $GLOBALS['config']['app_manager_grid_listings_number'];

        $order = false;
        $orderType = 'ASC';

        // Get listings
        $out['listings'] = rl('Listings')->getListingsByAccount($account_id, $order, $orderType, $start, $limit);

        $out['count'] = rl('Listings')->calc;

        // Build photos url
        $this->buildPhotosUrl($out['listings']);

        return $out;
    }

    /**
     * Get listing details
     *
     * @return array - listings
     **/
    public function getListingDetails()
    {

        $listing_id = $_GET['listing_id'] ? $_GET['listing_id'] : 0;
        $account_id = $_GET['account_id'] ? $_GET['account_id'] : 0;
        if ($account_id && $_REQUEST['account_password']) {
            (new AccountController)->issetAccount($account_id, $_REQUEST['account_password']);
        }

        $listing = [];

        if (!$listing_id) {
            return $listing;
        }

        /* get listing plain data */
        $sql = "SELECT `T1`.*, `T2`.`Path`, `T2`.`Type` AS `Listing_type`, `T2`.`Key` AS `Cat_key`, `T2`.`Type` AS `Cat_type`, ";
        if ($GLOBALS['config']['membership_module']) {
            $sql .= "IF (`T1`.`Plan_type` = 'account', `T7`.`Image`, `T3`.`Image`) AS `Image`, ";
            $sql .= "IF (`T1`.`Plan_type` = 'account', `T7`.`Image_unlim`, `T3`.`Image_unlim`) AS `Image_unlim`, ";
            $sql .= "IF (`T1`.`Plan_type` = 'account', `T7`.`Video`, `T3`.`Video`) AS `Video`, ";
            $sql .= "IF (`T1`.`Plan_type` = 'account', `T7`.`Video_unlim`, `T3`.`Video_unlim`) AS `Video_unlim`, ";
        } else {
            $sql .= "`T3`.`Image`, `T3`.`Image_unlim`, `T3`.`Video`, `T3`.`Video_unlim`, ";
        }
        $sql .= "CONCAT('categories+name+', `T2`.`Key`) AS `Category_pName`, ";
        $sql .= "`T2`.`Path` as `Category_path`,`T2`.`Parent_IDs`, ";
        $sql .= "IF(TIMESTAMPDIFF(HOUR, `T1`.`Featured_date`, NOW()) <= `T4`.`Listing_period` * 24 OR `T4`.`Listing_period` = 0, '1', '0') `Featured`, ";
        $sql .= "IF (UNIX_TIMESTAMP(DATE_ADD(`T1`.`Pay_date`, INTERVAL `T3`.`Listing_period` DAY)) <= UNIX_TIMESTAMP(NOW()) AND `T3`.`Listing_period` > 0, 1, 0) AS `Listing_expired` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T3` ON `T1`.`Plan_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T4` ON `T1`.`Featured_ID` = `T4`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T5` ON `T1`.`Account_ID` = `T5`.`ID` ";
        if ($GLOBALS['config']['membership_module']) {
            $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T7` ON `T1`.`Plan_ID` = `T7`.`ID` ";
        }
        $sql .= "WHERE `T1`.`ID` = {$listing_id} AND ";
        if ($account_id) {
            $sql .= "(`T5`.`Status` = 'active' OR `T1`.`Account_ID` = '{$account_id}') ";
        } else {
            $sql .= "`T1`.`Status` = 'active' ";
        }

        $listingData = rl('Db')->getRow($sql);

        if ($listingData)  {
            rl('MembershipPlan')->isContactsAllow();
            rl('MembershipPlan')->isSendMessage();
            $listingType = rl('ListingTypes')->types[$listingData['Listing_type']];

            $listing_fields = rl('Listings')->getListingDetails($listingData['Category_ID'], $listingData, $listingType);
            
            if ($GLOBALS['config']['count_listing_visits']) {
                rl('Listings')->countVisit($listing_id);
            }

            /* Get listing title */
            $listingData['listing_title'] = rl('Listings')->getListingTitle($listingData['Category_ID'], $listingData, $listingType['Key']);
            $listingData['url'] = rl('reefless')->getListingUrl($listingData);

            $listing['data'] = $listingData;

            /* Get listing media */
            $photos_limit = $listingData['Image_unlim'] ? true : $listingData['Image'];
            $videos_limit = $listingData['Video_unlim'] ? true : $listingData['Video'];

            /* Get listing media */
            $media = ListingMedia::get($listing_id, $photos_limit, $videos_limit, $listingType);

            $listing['media'] = $media;
            $price = '';
            $fields_out = [];

            $eventTypeKey = $this->eventTypeKey();
            if ($eventTypeKey == $listingData['Listing_type']) {
                $eventRates = new \Flynax\Plugins\Events\EventsRates();
                $rates = $eventRates->getDetailsRates($listingData['ID']);
            }

            /* Populate details stack */
            foreach ($listing_fields as $group) {
                if (empty($group['Fields'])) {
                    continue;
                }

                if ($group['Key']) {
                    $groupTmp = array(
                        'Key' => $group['Key'],
                        'name' => strip_tags($group['name']),
                        'Type' => 'group',
                        'Item' => 'group',
                    );
                    $fields_out[] = $groupTmp;
                }
                // Event rates
                if ($group['Key'] == 'event_rates' && $rates) {
                    foreach ($rates as $rate) {
                        $fields_out[] = $rate;
                    }
                }
                else {
                    foreach ($group['Fields'] as $field) {
                        if (!$price && false !== strpos($field['Key'], 'price')) {
                            $price = '';
                            if ($field['Options']['from']) {
                                $price .= $GLOBALS['lang']['price_from'] . ' ';
                            }
                            $price .= $field['value'];
                            $listing['data']['price_field'] = $field;
                        }

                        if ($field['value'] == "" || !$field['Details_page']) {
                            continue;
                        }
                        $field['name'] = strip_tags($field['name']);
                        $field['value'] = AppController::adaptValue($field);
                        $field['item'] = 'field';
                        if ($field['Type'] == 'phone' && $field['value'] && $field['Hidden']) {
                            $field['value_default'] = rl('reefless')->parsePhone($listingData[$field['Key']], $field, false);
                        }

                        $fields_out[] = $field;
                    }
                }
            }

            $listing['fields'] = $fields_out;
            $listing['data']['price_out'] = $price ? $price : "";

            if ($GLOBALS['plugins']['comment']) {
                $commentsController = new CommentsController();
                $listing['comments'] = $commentsController->getComments($listing_id, 1);
            }

            // Report broken
            if ($GLOBALS['plugins']['reportBrokenListing']) {
                $ip = \Flynax\Utils\Util::getClientIP();
                $where = "`Listing_ID` = {$listing_id} AND `IP` = '{$ip}'";
                $report_exist = rl('Db')->getOne('ID', $where, 'report_broken_listing');
                $listing['data']['report_exist'] = $report_exist;
            }

            /* Get seller info */
            $seller_info = (new AccountController)->getProfile((int) $listingData['Account_ID']);
            foreach ($seller_info['Fields'] as &$field) {
                if ($field['value'] == '' || !$field['Details_page']) {
                    continue;
                }
                $field['value'] = AppController::adaptValue($field);
            }
            $listing['seller_info'] = $seller_info;


            /* Build location */
            if ($GLOBALS['config']['address_on_map'] && $listingData['account_address_on_map']) {
                /* get location data from user account */
                $location = rl('Account')->mapLocation;

                if ($seller_info['Loc_latitude'] && $seller_info['Loc_longitude']) {
                    $location['direct'] = $seller_info['Loc_latitude'] . ',' . $seller_info['Loc_longitude'];
                }
            } else {
                /* get location data from listing */
                $fields_list = rl('Listings')->fieldsList;

                $location = false;
                foreach ($fields_list as $key => $value) {
                    if ($fields_list[$key]['Map'] && !empty($listingData[$fields_list[$key]['Key']])) {
                        $mValue = str_replace("'", "\'", $value['value']);
                        $location['search'] .= $mValue . ', ';
                        $location['show'] .= $GLOBALS['lang'][$value['pName']] . ': <b>' . $mValue . '<\/b><br />';
                        unset($mValue);
                    }
                }
                if (!empty($location)) {
                    $location['search'] = substr($location['search'], 0, -2);
                }

                if ($listingData['Loc_latitude'] && $listingData['Loc_longitude']) {
                    $location['direct'] = $listingData['Loc_latitude'] . ',' . $listingData['Loc_longitude'];
                }
            }
            $listing['data']['direct'] = $location['direct'];
            $listing['data']['search'] = $location['search'];

            if ($listingData['shc_mode'] == 'auction' && $GLOBALS['config']['shc_module_auction']) {
                (new ShoppingCartController)->getAuction($listing['data']);
            }

            $listing['membership']['send_message_allowed'] = rl('MembershipPlan')->is_send_message_allowed;
            $listing['membership']['contact_allowed'] = rl('MembershipPlan')->is_contact_allowed;
            $listing['membership']['allow_photos'] = rl('MembershipPlan')->isPhotoAllow($listingData);

        }

        return $listing;
    }

    /**
     * Is even type key
     *
     * @return String - eventTypeKey
     **/
    public function eventTypeKey()
    {
        $eventKey = '';
        if ($GLOBALS['plugins']['events']) {
            $eventKey = $GLOBALS['config']['event_type_key']
                ? $GLOBALS['config']['event_type_key']
                : rl('Db')->getOne('Default', "`Key` = 'event_type_key'", 'config');
        }
        return $eventKey;
    }

    /**
     * Buld photos url
     *
     * @param array $listing - listings
     *
     * @return array - listings information
     **/
    public function buildPhotosUrl(&$listings)
    {
        if ($listings['ID']) {
            ListingMedia::prepare($listings);
        }
        else {
            foreach ($listings as &$listing) {
                ListingMedia::prepare($listing);
            }
        }
    }

    /**
     * Buld listing price
     *
     * @param array $listing - listing
     *
     * @return string - price
     **/
    public function buildPrice($listing)
    {
        $price_tag_key = $GLOBALS['config']['price_tag_field'];
        $price_field_key = $listing['Listing_type'] == 'jobs' ? 'salary' : $price_tag_key;

        $out = '';

        if ($listing['fields'][$price_field_key]) {
            if ($listing['fields'][$price_field_key]['Options']['from']) {
                $out .= $GLOBALS['lang']['price_from'] . ' ';
            }
            $out .= $listing['fields'][$price_field_key]['value'];

        }
        else  if ($listing[$price_field_key]) {
            $field = rl('Db')->fetch(
                '*',
                array('Key' => $price_field_key),
                null,
                null,
                'listing_fields',
                'row'
            );
            $out = rl('Common')->adaptValue(
                $field,
                $listing[$price_field_key],
                'listing',
                $listing['ID'],
                true,
                false,
                false,
                false,
                $listing['Account_ID'],
                'listing_form',
                $listing['Listing_type']
            );
        }
        return $out;
    }


    /**
     * GET: /api/v1/app/myListings
     */
    public function myListings()
    {
        if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
            $listingType = $_REQUEST['listing_type'] ? : 'all_ads';
            $listings = rl('Listings')->getMyListings($listingType, 'Date', 'desc', $_REQUEST['start'], $GLOBALS['config']['listings_per_page']);
            $listingsController = new ListingsController();
            $listingsController->buildPhotosUrl($listings);
            foreach ($listings as &$listing) {
                $listing['plan_name'] = rl('Lang')->getPhrase($listing['Plan_key'], null, null, true);
                $listing['price_out'] = $listingsController->buildPrice($listing);
                $listingsController->contactDetailsRequests($listing);
            }

            $response['listings'] = $listings;
            $response['calc'] = rl('Listings')->calc;
            // $response['search_forms'] = $this->buildMyListingsSearchForms();
        }
        return $response;
    }

    /**
     * Get listing contact details
     * @param array $listing - listing
     *
     * @return array
     **/
    public function contactDetailsRequests(&$listing)
    {
        if ($listing['ID']) {
            $sql = "SELECT count(`ID`) AS `click`
                FROM `{db_prefix}phone_clicks`
                WHERE `Listing_ID` = '{$listing['ID']}'
            ";
           $listing['phone_clicks'] = rl('Db')->getRow($sql, 'click');
        }
    }

    /**
     * Build listing data
     * @param int $listing_id - listing id
     *
     * @return array
     **/
    public function buildReturnMyListing($listing_id)
    {
        global $config;

        $sql = "
            SELECT
            `T1`.*, `T4`.`Path`, `T4`.`Parent_ID`, `T4`.`Parent_IDs`,
            CONCAT('categories+name+', `T4`.`Key`) AS `Cat_key`, `T4`.`Type` AS `Category_type`, `T4`.`Type` AS `Listing_type`,
        ";
        if ($config['membership_module']) {
            $sql .= "IF (`T1`.`Plan_type` = 'account', DATE_ADD(`T1`.`Pay_date`, INTERVAL `T7`.`Plan_period` DAY), DATE_ADD(`T1`.`Pay_date`, INTERVAL `T2`.`Listing_period` DAY)) AS `Plan_expire`, ";
            $sql .= "IF (`T1`.`Plan_type` = 'account', CONCAT('listing_plans+name+', `T7`.`Key`), CONCAT('listing_plans+name+', `T2`.`Key`)) AS `Plan_key`, ";
            $sql .= "IF (`T1`.`Plan_type` = 'account', DATE_ADD(`T1`.`Featured_date`, INTERVAL `T8`.`Plan_period` DAY), DATE_ADD(`T1`.`Featured_date`, INTERVAL `T3`.`Listing_period` DAY)) AS `Featured_expire` ";
        } else {
            $sql .= "DATE_ADD(`T1`.`Pay_date`, INTERVAL `T2`.`Listing_period` DAY) AS `Plan_expire`, ";
            $sql .= "CONCAT('listing_plans+name+', `T2`.`Key`) AS `Plan_key`, ";
            $sql .= "DATE_ADD(`T1`.`Featured_date`, INTERVAL `T3`.`Listing_period` DAY) AS `Featured_expire` ";
        }
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T3` ON `T1`.`Featured_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T4` ON `T1`.`Category_ID` = `T4`.`ID` ";
        if ($config['membership_module']) {
            $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T7` ON `T1`.`Plan_ID` = `T7`.`ID` ";
            $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T8` ON `T1`.`Featured_ID` = `T8`.`ID` ";
        }
        $sql .= "WHERE `T1`.`ID` = '{$listing_id}' ";
        $listing = rl('Db')->getRow($sql);

        $listingsController = new ListingsController();
        // Build photos url
        $listingsController->buildPhotosUrl($listing);
        $listing['listing_title'] = rl('Listings')->getListingTitle(
            $listing['Category_ID'],
            $listing,
            $listing['Listing_type'],
            null,
            $listing['Parent_IDs']
        );
        $listing['plan_name'] = rl('Lang')->getPhrase($listing['Plan_key'], null, null, true);
        $listing['price_out'] = $listingsController->buildPrice($listing);
        $listing['url'] = rl('reefless')->getListingUrl($listing);
        $listingsController->contactDetailsRequests($listing);

        if ($listing['Status'] == 'incomplete') {
            $listing['plan'] = rl('Plan')->getPlan($listing['Plan_ID'], $listing['Account_ID']);
        }

        return $listing;
    }


    /*
     * Build search for my listings
     *
     * return array
     */
    public function buildMyListingsSearchForms()
    {
        // get search forms
        rl('Common')->buildActiveTillPhrases();
        foreach (rl('ListingTypes')->types as $lt_key => $ltype) {
            if ($ltype['Myads_search']) {
                if ($search_form = rl('Search')->buildSearch($lt_key . '_myads')) {
                    $fields = AppController::adaptForm($search_form);
                    $search_forms[$lt_key] = AppController::adaptFields($fields, [], 'listing');
                }

                unset($search_form);
            }
        }
        return $search_forms;
    }

    /**
     * Fetch plans by account type
     *
     */
    public function fetchPlans($plan_type, $id, $account_type, $featured)
    {
        $account_info = $_SESSION['account'];

        // fetch membership plans
        if ($plan_type == 'account') {
            if ($account_info['Plan_ID']) {
                $planInfo = rl('MembershipPlan')->getPlanByProfile($account_info);
                $plans[$planInfo['ID']] = $planInfo;
            } else {
                $plans = (new AccountController)->getMembershipPlans($account_info['Type']);
            }
        }
        // fetch listing plans
        else {
            $plans = $this->getPlans($id, $account_type, $featured);
        }
        return $plans;
    }

    /**
     * Get my packages
     *
     */
    public function getPurchagePackages()
    {
        if ((new AccountController)->issetAccount($_POST['account_id'], $_POST['account_password'])) {
            $account_id = $_POST['account_id'];

            $used_ids_tmp = rl('Db')->fetch(array('Plan_ID'), array('Account_ID' => $account_id), null, null, 'listing_packages');
            $used_ids = '';
            foreach ($used_ids_tmp as $key => $val) {
                $used_ids .= $used_ids ? "," . $val['Plan_ID'] : $val['Plan_ID'];
            }
            unset($used_ids_tmp);

            $sql = "SELECT DISTINCT `T1`.*, `T2`.`Status` AS `Subscription`, `T2`.`Period` ";
            $sql .= "FROM `{db_prefix}listing_plans` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}subscription_plans` AS `T2` ON `T1`.`ID` = `T2`.`Plan_ID` AND `T2`.`Service` = 'listing' AND `T2`.`Status` = 'active' ";
            $sql .= "WHERE `T1`.`Status` = 'active' AND `T1`.`Type` =  'package' ";
            if ($used_ids) {
                $sql .= "AND FIND_IN_SET(`T1`.`ID`, '" . $used_ids . "') = 0 ";
            }
            $sql .= "AND (FIND_IN_SET('{$_SESSION['account']['Type']}', `Allow_for`) > 0 OR `Allow_for` = '') ORDER BY `Position`";
            $available_packages = rl('Db')->getAll($sql);
            $available_packages = rl('Lang')->replaceLangKeys($available_packages, 'listing_plans', array('name', 'des'));

            if ($available_packages) {
                foreach ($available_packages as $key => $value) {

                    if ($value['Period']) {
                        $value['Period'] = $GLOBALS['lang']['subscription_period_' . $value['Period']];
                    }
                    $available_packages_tmp[] = $value;
                }
                $available_packages = $available_packages_tmp;
            }
            $response['plans'] = $available_packages;
        }
        return $response;
    }

    /**
     * Get my packages
     *
     */
    public function getMyPackages()
    {
        if ((new AccountController)->issetAccount($_POST['account_id'], $_POST['account_password'])) {
            $account_id = $_POST['account_id'];
            $account_type = $_SESSION['account']['Type'];

            $sql = "SELECT `T1`.`Listings_remains`, `T1`.`Standard_remains`, `T1`.`Featured_remains`, `T1`.`Date`, `T1`.`IP`, `T1`.`Plan_ID`, ";
            $sql .= "`T2`.*, `T1`.`ID`  , ";
            $sql .= "IF (`T2`.`Plan_period` = 0, 'unlimited', DATE_ADD(`T1`.`Date`, INTERVAL `T2`.`Plan_period` DAY)) AS `Exp_date`, ";
            $sql .= "IF (`T2`.`Plan_period` > 0 AND UNIX_TIMESTAMP(DATE_ADD(`T1`.`Date`, INTERVAL `T2`.`Plan_period` DAY)) < UNIX_TIMESTAMP(NOW()), 'expired', 'active') AS `Exp_status`";
            $sql .= ", `T3`.`Status` AS `Subscription`, `T3`.`ID` AS `Subscription_ID`, `T3`.`Service` AS `Subscription_service` ";

            $sql .= "FROM `{db_prefix}listing_packages` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
            $sql .= "LEFT JOIN `{db_prefix}subscriptions` AS `T3` ON `T1`.`Plan_ID` = `T3`.`Plan_ID` AND `T3`.`Service` = 'listing' AND `T3`.`Status` = 'active' ";
            $sql .= "WHERE `T1`.`Account_ID` = '{$account_id}' AND `T1`.`Type` = 'package' ";

            rl('Hook')->load('myPackagesSql', $sql);

            $sql .= "ORDER BY `T1`.`ID` DESC";

            $packages = rl('Db')->getAll($sql);
            $packages = rl('Lang')->replaceLangKeys($packages, 'listing_plans', array('name', 'des'));

            // Get available packages
            $sql = "SELECT count(*) as `count` FROM `{db_prefix}listing_plans` ";
            $sql .= "WHERE `Status` = 'active' AND `Type` =  'package' AND (FIND_IN_SET('{$account_type}', `Allow_for`) > 0 OR `Allow_for` = '') ";
            if ($packages) {
                $packages_id = "";
                foreach ($packages as $key => $value) {
                    $packages_id .= $packages_id ? "," . $value['Plan_ID'] : $value['Plan_ID'];
                }
                $sql .= "AND FIND_IN_SET(`ID`, '" . $packages_id . "') = 0 ";
            }
            $available_packages = rl('Db')->getRow($sql);

            $response['plans'] = $packages;
            $response['available_plan'] = $available_packages['count'] > 0 ? 1 : 0;

        }
        return $response;
    }

    /**
     * Get listing plans
     *
     */
    public function getListingPlans()
    {
        if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
            $id = $_REQUEST['id'];
            $account_type = $_REQUEST['account_type'];
            $featured = $_REQUEST['featured'] ? true : false;
            $response['plans'] = $this->getPlans($id, $account_type, $featured);
        }
        return $response;
    }

    /**
     * Get plans
     * @param int $category_id   - category
     * @param int $account_type  - account type
     * @param int $featured_only - featured
     *
     * @return array
     **/
    public function getPlans($category_id, $account_type, $featured_only)
    {
        global $config;

        $GLOBALS['account_info']['ID'] = $_REQUEST['account_id'];

        $plans = rl('Plan')->getPlanByCategory($category_id, $account_type, $featured_only);

        $remove_paid_plans = true;

        $paypal = rl('Db')->getOne('ID', "`Key`='paypal' AND `Status` = 'active' ", 'payment_gateways');

        if (($paypal && $config['paypal_account_email'] && $config['paypal_merchant_id'])
            || ($config['app_yookassa_module'] && $config['app_yookassa_store_id'] && $config['app_yookassa_secret_key_msdk'])) {
            $remove_paid_plans = false; //ok
        }

        // Remove paid plans if payment is not configured
        foreach ($plans as $key => &$plan) {
            if ($remove_paid_plans && $plan['Price'] > 0 && !($plan['Package_ID'] && $plan['Listings_remains'] > 0)) {
                unset($plans[$key]);
                continue;
            }
        }

        return $plans;
    }

    /**
     * Upgrade listing plan
     *
     * @return array
     **/
    public function upgradeListing()
    {
        global $config;

        if ((new AccountController)->issetAccount($_POST['account_id'], $_POST['account_password'])) {
            $account_id = $_POST['account_id'];
            $account_info = $GLOBALS['account'];
            $listing_id = $_POST['listing_id'];
            $plan_id = $_POST['plan_id'];
            $listing_mode = $_POST['featured'] ? 'featured' : 'standard';

            // get listing details
            $sql = "SELECT `T1`.*, `T1`.`Category_ID`, `T1`.`Status`, UNIX_TIMESTAMP(`T1`.`Pay_date`) AS `Pay_date`, `T1`.`Crossed`, ";
            if ($config['membership_module']) {
                $sql .= "IF (`T1`.`Plan_type` = 'account', DATE_ADD(`T1`.`Pay_date`, INTERVAL `T7`.`Plan_period` DAY), DATE_ADD(`T1`.`Pay_date`, INTERVAL `T3`.`Listing_period` DAY)) AS `Plan_expire`, ";
                $sql .= "IF (`T1`.`Plan_type` = 'account', CONCAT('listing_plans+name+', `T7`.`Key`), CONCAT('listing_plans+name+', `T2`.`Key`)) AS `Plan_key`, ";
                $sql .= "IF (`T1`.`Plan_type` = 'account', DATE_ADD(`T1`.`Featured_date`, INTERVAL `T8`.`Plan_period` DAY), DATE_ADD(`T1`.`Featured_date`, INTERVAL `T4`.`Listing_period` DAY)) AS `Featured_expire`, ";
            } else {
                $sql .= "DATE_ADD(`T1`.`Pay_date`, INTERVAL `T3`.`Listing_period` DAY) AS `Plan_expire`, ";
                $sql .= "CONCAT('listing_plans+name+', `T3`.`Key`) AS `Plan_key`, ";
                $sql .= "DATE_ADD(`T1`.`Featured_date`, INTERVAL `T4`.`Listing_period` DAY) AS `Featured_expire`, ";
            }
            $sql .= "`T2`.`Type` AS `Listing_type`, `T2`.`Path` AS `Category_path`, `T1`.`Last_type` AS `Listing_mode` ";
            $sql .= "FROM `{db_prefix}listings` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";

            $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T3` ON `T1`.`Plan_ID` = `T2`.`ID` ";
            $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T4` ON `T1`.`Featured_ID` = `T3`.`ID` ";
            if ($config['membership_module']) {
                $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T7` ON `T1`.`Plan_ID` = `T7`.`ID` ";
                $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T8` ON `T1`.`Featured_ID` = `T8`.`ID` ";
            }

            $sql .= "WHERE `T1`.`ID` = {$listing_id} AND `T1`.`Account_ID` = '{$account_id}' ";

            rl('Hook')->load('upgradeListingSql', $sql);
            $listing = rl('Db')->getRow($sql);

            if ($listing) {
                $listing['listing_title'] = rl('Listings')->getListingTitle(
                    $listing['Category_ID'],
                    $listing,
                    $listing['Listing_type'],
                    null,
                    $listing['Parent_IDs']
                );
                $listing['url'] = rl('reefless')->getListingUrl($listing);
                $listing['plan_name'] = rl('Lang')->getPhrase($listing['Plan_key'], null, null, true);

                // get plan info
                $plan_info = rl('Plan')->getPlan($plan_id, $account_id);

                if ($plan_info) {
                    // check limited plans using
                    if ($plan_info['Using'] <= 0 && $plan_info['Limit'] > 0) {
                        return false;
                    }
                    // check rest listings using
                    if ($plan_info['Package_ID'] && $listing_mode && ($plan_info[ucfirst($listing_mode) . '_remains'] <= 0 && $plan_info[ucfirst($listing_mode) . '_listings'] > 0)) {
                        return false;
                    }

                    // Upgrade to featured MODE
                    if ($plan_info['Type'] == 'featured') {
                        $update = array(
                            'fields' => array(
                                'Featured_ID' => $plan_info['ID'],
                                'Featured_date' => 'NOW()',
                            ),
                            'where' => array(
                                'ID' => $listing_id,
                            ),
                        );

                        if (rl('Db')->updateOne($update, 'listings')) {
                            /* limited option handler */
                            if ($plan_info['Limit'] > 0) {
                                if ($plan_info['Using'] == '') {
                                    $plan_using_insert = array(
                                        'Account_ID' => $account_info['ID'],
                                        'Plan_ID' => $plan_info['ID'],
                                        'Listings_remains' => $plan_info['Limit'] - 1,
                                        'Type' => 'limited',
                                        'Date' => 'NOW()',
                                        'IP' => \Flynax\Utils\Util::getClientIP(),
                                    );
                                    rl('Db')->insertOne($plan_using_insert, 'listing_packages');
                                } else {
                                    $plan_using_update = array(
                                        'fields' => array(
                                            'Account_ID' => $account_info['ID'],
                                            'Plan_ID' => $plan_info['ID'],
                                            'Listings_remains' => $plan_info['Using'] - 1,
                                            'Type' => 'limited',
                                            'Date' => 'NOW()',
                                            'IP' => \Flynax\Utils\Util::getClientIP(),
                                        ),
                                        'where' => array(
                                            'ID' => $plan_info['Plan_using_ID'],
                                        ),
                                    );
                                    rl('Db')->updateOne($plan_using_update, 'listing_packages');
                                }
                            }

                            /* send notification to listing owner */
                            $mail_tpl = rl('Mail')->getEmailTemplate('listing_upgraded_to_featured');

                            $lt_page_path = rl('Db')->getOne('Path', "`Key` = 'lt_{$listing['Listing_type']}'", 'pages');
                            $my_page_path = rl('Db')->getOne('Path', "`Key` = 'my_{$listing['Listing_type']}'", 'pages');

                            $find = array('{name}', '{listing}', '{plan_name}', '{plan_price}', '{start_date}', '{expiration_date}');
                            $replace = array(
                                $account_info['Full_name'],
                                '<a href="' . $listing['url'] . '">' . $listing['listing_title'] . '</a>',
                                $plan_info['name'],
                                $GLOBALS['lang']['free'],
                                date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT)),
                                date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT), strtotime('+' . $plan_info['Listing_period'] . ' days')),
                            );

                            $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
                            $mail_tpl['body'] = preg_replace('/\{if.*\{\/if\}(<br\s+\/>)?/', '', $mail_tpl['body']);

                            rl('Mail')->send($mail_tpl, $account_info['Mail']);

                            /* send notification to administrator */
                            $mail_tpl = rl('Mail')->getEmailTemplate('listing_upgraded_to_featured_for_admin');

                            $link = RL_URL_HOME . ADMIN . '/index.php?controller=listings&amp;action=view&amp;id=' . $listing_id;

                            $find = array('{listing}', '{plan_name}', '{listing_id}', '{owner}', '{start_date}', '{expiration_date}');
                            $replace = array(
                                '<a href="' . $link . '">' . $listing['listing_title'] . '</a>',
                                $plan_info['name'],
                                $listing_id,
                                $account_info['Full_name'],
                                date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT)),
                                date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT), strtotime('+' . $plan_info['Listing_period'] . ' days')),
                            );

                            $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
                            rl('Mail')->send($mail_tpl, $config['notifications_email']);
                        }
                    }
                    else {
                        $update_featured_id = ($plan_info['Featured'] && !$plan_info['Advanced_mode']) || $listing_mode == 'featured' ? $plan_info['ID'] : '';
                        $update_featured_date = ($plan_info['Featured'] && !$plan_info['Advanced_mode']) || $listing_mode == 'featured' ? 'IF(UNIX_TIMESTAMP(NOW()) > UNIX_TIMESTAMP(DATE_ADD(`Featured_date`, INTERVAL ' . $plan_info['Listing_period'] . ' DAY)) OR IFNULL(UNIX_TIMESTAMP(`Featured_date`), 0) = 0, NOW(), DATE_ADD(`Featured_date`, INTERVAL ' . $plan_info['Listing_period'] . ' DAY))' : '';
                        $update_date = 'IF(UNIX_TIMESTAMP(NOW()) > UNIX_TIMESTAMP(DATE_ADD(`Pay_date`, INTERVAL ' . $plan_info['Listing_period'] . ' DAY)) OR IFNULL(UNIX_TIMESTAMP(`Pay_date`), 0) = 0, NOW(), DATE_ADD(`Pay_date`, INTERVAL ' . $plan_info['Listing_period'] . ' DAY))';

                        $update = array(
                            'fields' => array(
                                'Plan_ID' => $plan_info['ID'],
                                'Pay_date' => $update_date,
                                'Featured_ID' => $update_featured_id,
                                'Featured_date' => $update_featured_date,
                                'Last_type' => $listing_mode,
                                'Plan_type' => 'listing',
                            ),
                            'where' => array(
                                'ID' => $listing_id,
                            ),
                        );

                        /* update listing posting date */
                        if ($config['posting_date_update']) {
                            $update['fields']['Date'] = 'NOW()';
                        }

                        if ($listing['Status'] == 'expired') {
                            $update['fields']['Status'] = 'active';
                        }

                        if (rl('Db')->updateOne($update, 'listings')) {
                            // available package mode
                            if ($plan_info['Type'] == 'package' && $plan_info['Package_ID']) {
                                if ($plan_info['Listings_remains'] != 0 || $plan_info['Standard_listings'] == 0 && $listing_mode == 'standard' || $plan_info['Featured_listings'] == 0 && $listing_mode == 'featured') {
                                    $update_entry = array(
                                        'fields' => array(
                                            'Listings_remains' => $plan_info[ucfirst($listing_mode) . '_listings'] == 0 ? $plan_info['Listings_remains'] : $plan_info['Listings_remains'] - 1,
                                        ),
                                        'where' => array(
                                            'ID' => $plan_info['Package_ID'],
                                        ),
                                    );

                                    if ($plan_info[ucfirst($listing_mode) . '_listings'] != 0) {
                                        $update_entry['fields'][ucfirst($listing_mode) . '_remains'] = $plan_info[ucfirst($listing_mode) . '_remains'] - 1;
                                    } elseif (!$plan_info['Advanced_mode']) {
                                        $update_entry['fields']['Listings_remains'] = $plan_info['Listing_number'] - 1;
                                    }
                                    rl('Db')->updateOne($update_entry, 'listing_packages');
                                }
                            }
                            // free package mode
                            elseif ($plan_info['Type'] == 'package' && !$plan_info['Package_ID'] && $plan_info['Price'] <= 0) {
                                $insert_entry = array(
                                    'Account_ID' => $account_info['ID'],
                                    'Plan_ID' => $plan_info['ID'],
                                    'Listings_remains' => $plan_info[ucfirst($listing_mode) . '_listings'] == 0 ? $plan_info['Listing_number'] : $plan_info['Listing_number'] - 1,
                                    'Type' => 'package',
                                    'Date' => 'NOW()',
                                    'IP' => \Flynax\Utils\Util::getClientIP(),
                                );

                                if ($plan_info['Featured'] && $plan_info['Advanced_mode'] && $plan_info['Standard_listings']) {
                                    $insert_entry['Standard_remains'] = $plan_info['Standard_listings'];
                                }
                                if ($plan_info['Featured'] && $plan_info['Advanced_mode'] && $plan_info['Featured_listings']) {
                                    $insert_entry['Featured_remains'] = $plan_info['Featured_listings'];
                                }

                                if ($plan_info[ucfirst($listing_mode) . '_listings'] != 0) {
                                    $insert_entry[ucfirst($listing_mode) . '_remains'] = $plan_info[ucfirst($listing_mode) . '_listings'] - 1;
                                } elseif (!$plan_info['Advanced_mode']) {
                                    $insert_entry['Listings_remains'] = $plan_info['Listing_number'] - 1;
                                }

                                rl('Db')->insertOne($insert_entry, 'listing_packages');
                            }
                            // limited listing mode
                            elseif ($plan_info['Type'] == 'listing' && $plan_info['Limit'] > 0) {
                                /* update/insert limited plan using entry */
                                if (empty($plan_info['Using'])) {
                                    $plan_using_insert = array(
                                        'Account_ID' => $account_info['ID'],
                                        'Plan_ID' => $plan_info['ID'],
                                        'Listings_remains' => $plan_info['Limit'] - 1,
                                        'Type' => 'limited',
                                        'Date' => 'NOW()',
                                        'IP' => \Flynax\Utils\Util::getClientIP(),
                                    );

                                    rl('Db')->insertOne($plan_using_insert, 'listing_packages');
                                } else {
                                    $plan_using_update = array(
                                        'fields' => array(
                                            'Account_ID' => $account_info['ID'],
                                            'Plan_ID' => $plan_info['ID'],
                                            'Listings_remains' => $plan_info['Using'] - 1,
                                            'Type' => 'limited',
                                            'Date' => 'NOW()',
                                            'IP' => \Flynax\Utils\Util::getClientIP(),
                                        ),
                                        'where' => array(
                                            'ID' => $plan_info['Plan_using_ID'],
                                        ),
                                    );

                                    rl('Db')->updateOne($plan_using_update, 'listing_packages');
                                }
                            }

                            /* update listing images count if plan allows less photos then previous plan */
                            if (!$plan_info['Image_unlim'] && $plan_info['Image'] && $plan_info['Image'] < $listing['Photos_count'] && $plan_info['Type'] != 'featured') {
                                $photos_count_update = array(
                                    'fields' => array(
                                        'Photos_count' => $plan_info['Image'],
                                    ),
                                    'where' => array(
                                        'ID' => $listing['ID'],
                                    ),
                                );

                                rl('Db')->updateOne($photos_count_update, 'listings');
                            }

                            /* recount category listings count */
                            if ($config['listing_auto_approval'] && $listing != "active") {
                                rl('Categories')->listingsIncrease($listing['Category_ID']);
                                if (method_exists(rl('Categories'), 'accountListingsIncrease')) {
                                    rl('Categories')->accountListingsIncrease($account_id);
                                }
                            }

                            /* send message to listing owner */
                            $mail_tpl = rl('Mail')->getEmailTemplate(
                                ($config['listing_auto_approval'] || $listing['Status'] == 'active')
                                ? 'listing_upgraded_active'
                                : 'listing_upgraded_approval'
                            );

                            $lt_page_path = rl('Db')->getOne('Path', "`Key` = 'lt_{$listing['Listing_type']}'", 'pages');
                            $my_page_path = rl('Db')->getOne('Path', "`Key` = 'my_{$listing['Listing_type']}'", 'pages');

                            $mail_tpl['body'] = str_replace(
                                array('{name}', '{link}', '{plan}'),
                                array(
                                    $account_info['Full_name'],
                                    '<a href="' . $listing['url'] . '">' . $listing['url'] . '</a>', $plan_info['name'],
                                ),
                                $mail_tpl['body']
                            );
                            rl('Mail')->send($mail_tpl, $account_info['Mail']);
                        }
                    }
                }
            }


            $response['success'] = $this->buildReturnMyListing($listing_id);
        }
        return $response;
    }

    /**
     * Upgrade package
     *
     * @return array
     **/
    public function upgradePackage()
    {
        if ((new AccountController)->issetAccount($_POST['account_id'], $_POST['account_password'])) {
            $package_id = $_POST['item_id'];
            $plan_id = $_POST['plan_id'];
            $service = $_POST['service'];

            if ($service == 'purchasePackage') {
                rl('Listings')->purchasePackage($plan_id, $plan_id, $_POST['account_id'], true);
            } else {
                rl('Listings')->upgradePackage($package_id, $plan_id, $_POST['account_id']);
            }
            $response['status'] = 'ok';
        }
        return $response;
    }

    /**
     * Remove listing
     *
     * @return array
     **/
    public function removeListing()
    {
        if ((new AccountController)->issetAccount($_POST['account_id'], $_POST['account_password'])) {
            $listing_id = $_POST['listing_id'];
            rl('Listings')->deleteListing($listing_id, $_POST['account_id'], true, false);
            $response['status'] = 'ok';
        }
        return $response;
    }

    /**
     * Add report broken
     *
     * @return array
     **/
    public function addReportBrokenListing()
    {
        $ip = \Flynax\Utils\Util::getClientIP();
        $listing_id = $_POST['listing_id'];

        $where = "`Listing_ID` = {$listing_id} AND `IP` = '{$ip}'";
        $report_exist = rl('Db')->getOne('ID', $where, 'report_broken_listing');

        if ($report_exist) {
            $response['status'] = 'error';
        } else {
            $insert = array(
                'Listing_ID' => $listing_id,
                'Account_ID' => $_POST['account_id'] ? $_POST['account_id'] : '',
                'Report_key' => $_POST['key'],
                'Message' => $_POST['key'] == 'custom' ? $_POST['message'] : rl('Lang')->getPhrase($_POST['key'], null, null, true),
                'IP' => $ip,
                'Date' => 'NOW()',
                'Status' => 'active',
            );

            // insert a new report
            rl('Db')->insertOne($insert, 'report_broken_listing');
            $response['item'] = rl('Db')->insertID();
            $response['status'] = 'ok';
        }

        return $response;
    }

    /**
     * Remove report broken
     *
     * @return array
     **/
    public function removeReportBrokenListing()
    {
        if ($_POST['report_id']) {
            rl('Db')->query("DELETE FROM `{db_prefix}report_broken_listing` WHERE `ID` = '{$_POST['report_id']}'");
            $response['status'] = 'ok';
        }

        return $response;
    }

    /**
     * My favorites
     *
     * @return array
     **/
    public function myFavorites()
    {
        if ($_REQUEST['ids']) {
            $_COOKIE['favorites'] = $_REQUEST['ids'];
        }

        $page = (int) $_GET['page'];

        if ($page == 1 && (new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
            rl('Account')->synchronizeFavorites();
        }

        $listings = rl('Listings')->getMyFavorite(false, false, $page, $GLOBALS['config']['listings_per_page']);

        if ($listings) {
            $this->buildPhotosUrl($listings);
            $response['listings'] = $listings;
            $response['calc'] = rl('Listings')->calc;

            if ($page == 1) {
                $response['ids'] = $_COOKIE['favorites'];
            }
        }
        $response['status'] = 'ok';
        return $response;
    }

    /**
     * Action favorite
     *
     * @return array
     **/
    public function actionFavorite()
    {
        if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
            rl('Listings')->ajaxFavorite($_REQUEST['listing_id'], $_REQUEST['remove'] ? true : false);
            $response['status'] = 'ok';
        }
        return $response;
    }

    /**
     * Get listings on the search map
     *
     * @return array
     **/
    public function searchOnMap()
    {

        $types = rl('ListingTypes')->types;
        $data = json_decode($_POST['form'], true);
        $listingType = $data['listing_type'];

        $coordinates = [
            'northEastLat' => $_POST['northEastLat'],
            'northEastLng' => $_POST['northEastLng'],
            'southWestLat' => $_POST['southWestLat'],
            'southWestLng' => $_POST['southWestLng'],
        ];
        rl('ApiPlugin')->coordinates = $coordinates;

        if ($types[$listingType]['On_map_search']) {
            rl('Search')->getFields($listingType . '_on_map', $listingType);
        }
        else {
            // fake form
            rl('Search')->getFields($listingType . '_quick', $listingType);
        }

        $listings = rl('Search')->search($data, $listingType, 1, $GLOBALS['config']['app_manager_listings_number_on_map']);

        $response['status'] = 'ok';
        if ($listings) {
            foreach ($listings as &$listing) {
                $listing['price_out'] = $this->buildPrice($listing);
                $this->buildPhotosUrl($listing);
            }
            $response['listings'] = $listings;
        }

        return $response;
    }

    /**
     * Search listings
     *
     * @return array
     **/
    public function search()
    {
        $types = rl('ListingTypes')->types;
        $data = json_decode($_POST['form'], true);
        $listingType = $data['listing_type'];

        rl('Search')->getFields($listingType . '_quick', $listingType);

        $listings = rl('Search')->search($data, $listingType, $_REQUEST['start'], $GLOBALS['config']['app_manager_grid_listings_number']);
        $response['count'] = rl('Search')->calc;

        if ($_REQUEST['start'] == 1) {
            $category_id = $_REQUEST['category_id'] ? $_REQUEST['category_id'] : 0;
            $response['categories'] = rl('Categories')->getCategories($category_id, $listingType, false, false);
        }

        $response['status'] = 'ok';
        if ($listings) {
            foreach ($listings as &$listing) {
                $listing['price_out'] = $this->buildPrice($listing);
            }
            $response['listings'] = $listings;
        }

        return $response;
    }

    /**
     * Keyword search listings for main page
     *
     * @return array
     **/
    public function keywordSearch()
    {
        $data['keyword_search'] = $_REQUEST['query'];
        $fields['keyword_search'] = array(
            'Type' => 'text',
        );

        rl('Search')->fields = $fields;

        $listings = rl('Search')->search($data, false, false, 20);
        if ($listings) {
            // Build photos url
            $this->buildPhotosUrl($listings);
            $response['items'] = $listings;
            unset($listings);
        }
        $response['status'] = $response['items'] ? 'ok' : 'error';

        return $response;
    }

    /**
     * Smart search listings && categories for main page
     *
     * @since 1.0.1
     * @return array
     **/
    public function smartSearch()
    {
        $results_limit = 12 / 2;
        $min_length = 3;
        $query = $_REQUEST['query'];

        if (!$query || strlen($query) < $min_length) {
            $response['status'] = 'error';
            return $response;
        }

        $lang_code = RL_LANG_CODE;
        $query_array = explode(' ', $query);
        $keywords_query = '';
        $items = [];
        $categories = [];
        $keywords = [];
        $min = $min_length;

        // Search keywords
        $text_fields = rl('Db')->getAll("
            SELECT `T1`.`Key` FROM `{db_prefix}listing_fields` AS `T1`
            LEFT JOIN `{db_prefix}listing_titles` AS `T2` ON `T1`.`ID` = `T2`.`Field_ID`
            WHERE `T1`.`Status` = 'active' AND `T1`.`Type` = 'text' AND `T2`.`ID` IS NOT NULL
            GROUP BY `T1`.`Key`
        ", [false, 'Key']);

        if (count($query_array) > 1) {
            $query_items = $query_array;
            foreach ($query_items as $index => $item) {
                if (strlen($item) < $min) {
                    unset($query_items[$index]);
                }
            }
            foreach ($text_fields as $field) {
                $keywords_query .= "(`{$field}` LIKE '%" . implode("%' AND `{$field}` LIKE '%", $query_items) . "%') OR ";
            }
            $keywords_query = substr($keywords_query, 0, -4);
        } else {
            $keywords_query = "`" . implode("` LIKE '%{$query}%' OR `", $text_fields) . "` LIKE '%{$query}%'";
        }

        $sql = "
            SELECT `" . implode('`,`', $text_fields) . "` FROM `{db_prefix}listings`
            WHERE `Status` = 'active' AND ({$keywords_query})
            LIMIT {$results_limit}
        ";
        $listing_keywords = rl('Db')->getAll($sql);

        if ($listing_keywords) {
            foreach ($listing_keywords as $listing) {
                foreach ($listing as $keyword) {
                    if ($keyword) {
                        $keywords[] = substr($keyword, 0, 2) == '{|'
                        ? rl('reefless')->parseMultilingual($keyword, $lang_code)
                        : $keyword;
                    }
                }
            }

            if ($keywords) {
                $keywords = array_values(array_unique($keywords));
                /**
                 * @todo remove this code once applying function to simple array foreach {{:}} issue is resolved
                 */
                $tmp = [];
                foreach($keywords as $keyword) {
                    $tmp[] = [
                        'Mode' => 'search',
                        'name' => $keyword
                    ];
                }
                $items = $tmp;
                unset($tmp);
            }
        }

        // Search in categories
        $keywords_query = '';
        if (count($query_array) > 1) {
            $keywords_query .= "AND (";
            foreach ($query_array as $keyword) {
                if (!$keyword || strlen($keyword) < $min) {
                    continue;
                }

                $keywords_query .= "(`Value` LIKE '%{$keyword}' OR `Value` LIKE '%{$keyword} %') OR ";
                $min--; // Reduce min length filter to search more relevant categories
            }
            $keywords_query = substr($keywords_query, 0, -4);
            $keywords_query .= ")";
        } else {
            $keywords_query = "AND `Value` LIKE '%{$query}%'";
        }

        $sql = "
            SELECT `Value`, REPLACE(`Key`, 'categories+name+', '') AS `Key` FROM `{db_prefix}lang_keys`
            WHERE `Module` = 'category' AND `Key` LIKE 'categories+name+%' AND `Code` = '{$lang_code}'
            {$keywords_query}
            LIMIT {$results_limit}
        ";

        $categories_names = rl('Db')->getAll($sql, ['Key', 'Value']);
        $categories_keys = array_keys($categories_names);
        $categories_data = rl('Db')->getAll("
            SELECT * FROM `{db_prefix}categories`
            WHERE `Key` IN ('" . implode("','", $categories_keys) . "') AND `Status` = 'active'
        ", ['Key', true]);

        foreach ($categories_names as $key => $name) {
            if ($categories_data[$key]) {
                $names = [];
                /**
                 * @todo remove 'name' index once applying function to simple array foreach {{:}} issue is resolved
                 */
                $names[] = rl('ListingTypes')->types[$categories_data[$key]['Type']]['name'];
                if ($categories_data[$key]['Parent_keys']) {
                    $parent_key = array_pop(explode(',', $categories_data[$key]['Parent_keys']));
                    $names[] = rl('Lang')->getPhrase('categories+name+' . $parent_key);
                }
                $names[] = $name;
                $items[] = [
                    'names' => $names,
                    'Mode' => 'category',
                    'Category_ID' => $categories_data[$key]['ID'],
                    'Type' => $categories_data[$key]['Type'],
                    'Type_name' => rl('ListingTypes')->types[$categories_data[$key]['Type']]['name'],
                ];
            }
        }

        $response['items'] = $items;
        $response['status'] = $response['items'] ? 'ok' : 'error';

        return $response;
    }

    /**
     * Save alerts
     * @param $_POST data
     *
     * @return array
     **/
    public function saveSearch()
    {
        if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
            $listingType = $_POST['listing_type'];
            $formKey = $listingType . '_quick';

            $data = json_decode($_POST['form'], true);
            $_SESSION[$listingType . '_post'] = $data;

            $out = rl('Search')->ajaxSaveSearch(
                $listingType,
                $_REQUEST['account_id'],
                $formKey
            );
            if ($out['status'] == 'error') {
                $response = 'error';
            }
            else {
                $response = $out;
            }
        } else {
            $response = 'error';
        }
        return $response;
    }

    /**
     * Get save alerts
     * @param $_POST data
     *
     * @return array
     **/
    public function getSaveAlerts()
    {
        if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {

            $page = $_REQUEST['start'] ? $_REQUEST['start'] : 0;

            $saved_search = rl('Db')->fetch(
                array('ID', 'Form_key', 'Listing_type', 'Content', 'Date', 'Status'),
                array('Account_ID' => $_REQUEST['account_id']),
                'ORDER BY `ID`',
                null,
                'saved_search'
            );

            if ($saved_search) {
                $tmp_fields = rl('Db')->fetch(
                    array('Key', 'Type', 'Condition', 'Default'),
                    array('Status' => 'active'),
                    null,
                    null,
                    'listing_fields'
                );
                $tmp_fields = rl('Lang')->replaceLangKeys($tmp_fields, 'listing_fields', array('name'));
                $fields = array();
                foreach ($tmp_fields as $tmp_key => $tmp_field) {
                    $fields[$tmp_field['Key']] = $tmp_field;
                }
                unset($tmp_fields);

                foreach ($saved_search as $key => $value) {
                    $content     = unserialize($saved_search[$key]['Content']);
                    $saved_search[$key]['type_name'] = $GLOBALS['lang']['listing_types+name+' . $value['Listing_type']];
                    $system_currency = '';
                    $tmpContent = false;
                    $step        = 0;

                    foreach ($content as $cKey => $cVal) {
                        if (isset($fields[$cKey])) {

                            $tmpContent[$step]['Type'] = $fields[$cKey]['Type'];
                            $tmpContent[$step]['Default'] = $fields[$cKey]['Default'];
                            $tmpContent[$step]['Condition'] = $fields[$cKey]['Condition'];
                            $tmpContent[$step]['data'] = $content[$cKey];
                            $tmpContent[$step]['name'] = $fields[$cKey]['name'];

                            if ($fields[$cKey]['Type'] == 'number' || $fields[$cKey]['Condition'] == 'years') {
                                $tmpContent[$step]['value'] = $this->buildSaveAlertsFromTo($content[$cKey], $fields[$cKey]);
                            } elseif ($fields[$cKey]['Type'] == 'mixed') {
                                $tmpContent[$step]['value'] = $this->buildSaveAlertsFromTo($content[$cKey], $fields[$cKey]);

                                if (empty($fields[$cKey]['Condition'])) {
                                    $tmpContent[$step]['value'] = ' ' .  $GLOBALS['lang']['listing_fields+name+' . $content[$cKey]['df']];
                                } else {
                                    $tmpContent[$step]['value'] = ' ' . $GLOBALS['lang']['data_formats+name+' . $content[$cKey]['df']];
                                }
                            } elseif ($fields[$cKey]['Type'] == 'date' ) {
                                if ($fields[$cKey]['Default'] == 'single') {
                                    $tmpContent[$step]['value'] = $this->buildSaveAlertsFromTo($content[$cKey], $fields[$cKey]);
                                }
                                else {
                                    $tmpContent[$step]['value'] = $content[$cKey];
                                }

                            } elseif ($fields[$cKey]['Type'] == 'price') {
                                if (!$system_currency) {
                                    $system_currency = rl('Categories')->getDF('currency');
                                }

                                $tmpContent[$step]['value'] = $this->buildSaveAlertsFromTo($content[$cKey], $fields[$cKey]);

                                if ($content[$cKey]['currency']) {
                                    $tmpContent[$step]['value'] .= !$content[$cKey]['currency'] && count($system_currency) == 1
                                    ? ' ' . $GLOBALS['lang'][$system_currency[0]['pName']]
                                    : ' ' . $GLOBALS['lang']['data_formats+name+' . $content[$cKey]['currency']];
                                }
                            } elseif ($fields[$cKey]['Type'] == 'unit') {
                                $tmpContent[$step]['value'] = $content[$cKey];
                                $tmpContent[$step]['value']['unit'] = $GLOBALS['lang']['data_formats+name+' . $content[$cKey]['unit']];
                            } elseif ($fields[$cKey]['Type'] == 'checkbox') {
                                $tmpContent[$step]['value'] = rl('Common')->adaptValue($fields[$cKey], implode(',', $content[$cKey]));
                            } elseif ($fields[$cKey]['Key'] == 'Category_ID') {
                                $title = $GLOBALS['lang']['listing_types+name+' . $value['Listing_type']];

                                $ids = $content['category_parent_ids'] ? $content['category_parent_ids'] : $content[$cKey];
                                $tmpIDs = explode(',', $ids);
                                foreach($tmpIDs as $keyID) {
                                    $cat_name = rl('Db')->fetch(array('Key'), array('ID' => $keyID), null, 1, 'categories', 'row');
                                    $phraseKey = 'categories+name+' . $cat_name['Key'];
                                    $title .= ' / ' . rl('Lang')->getPhrase($phraseKey, RL_LANG_CODE, false, true);
                                }
                                $saved_search[$key]['type_name'] = $title;
                                unset($tmpContent[$step]);
                            } elseif ($fields[$cKey]['Key'] == 'posted_by') {
                                $account_type = rl('Account')->getTypeDetails($cVal);
                                $tmpContent[$step]['value'] = $account_type['name'] ? $account_type['name'] : strtoupper($cVal);
                            } else {
                                $tmpContent[$step]['value'] = rl('Common')->adaptValue($fields[$cKey], $content[$cKey]);
                            }
                        }
                        $step++;

                    }
                    if ($tmpContent) {
                        $saved_search[$key]['fields'] = $tmpContent;
                        unset($tmpContent);
                    }
                }
                unset($fields, $content);
            }
            $response['result'] = $saved_search;
            $response['total'] = count($saved_search);
            $response['status'] = 'ok';
        } else {
            $response = 'error';
        }
        return $response;
    }

    /**
     * Build save alert from to
     * @param $array data
     *
     * @return string
     **/
    public function buildSaveAlertsFromTo($data, $field)
    {
        $out = '';
        if ($data['from'] && $data['to']) {
            $separator = $field['Type'] == 'date' ? ' / ' : '-';
            $out = $data['from'] . $separator . $data['to'];
        }
        else if ($data['from'] && !$data['to']) {
            $out = $GLOBALS['lang']['from'] . ' ' . $data['from'];
        }
        else if (!$data['from'] && $data['to']) {
            $out = $GLOBALS['lang']['to'] . ' ' . $data['to'];
        }
        return $out;
    }

    /**
     * Get save alerts
     * @param $_POST data
     *
     * @return array
     **/
    public function massSavedSearch()
    {
        if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
            $ids = $_REQUEST['ids'];
            $action = $_REQUEST['action'];
            $response = rl('Search')->ajaxMassSavedSearch(
                $ids,
                $action,
                $_REQUEST['account_id']
            );
            $response['status'] = 'ok';
        } else {
            $response = 'error';
        }
        return $response;
    }

    /**
     * Get search alerts result
     * @param data
     *
     * @return array
     **/
    public function getSearchResult()
    {
        global $formKey;

        $id = $_REQUEST['id'];
        if ($id) {
            $entry = rl('Db')->getRow("
                SELECT *
                FROM `{db_prefix}saved_search`
                WHERE `ID` = {$id}
            ");

            $formKey = $entry['Form_key'];
            $data = unserialize($entry['Content']);
            $listingType = $entry['Listing_type'];

            $findIDs = $entry['find_ids'];

            if ($findIDs && is_string($findIDs) && $findIDs !== '') {
                define('FLUTTER_SAVED_SEARCH_IDS', $findIDs);
            }

            rl('Search')->getFields($formKey, $listingType);
            $response['listings'] = rl('Search')->search($data, $listingType, $_REQUEST['start'], $GLOBALS['config']['app_manager_grid_listings_number']);

            $response['count'] = rl('Search')->calc;
        } else {
            $response = 'error';
        }
        return $response;
    }

    /**
     * Get contact info
     *
     * @return array
     **/
    public function getCallOwnerData()
    {
        if ($_REQUEST['account_id'] && $_REQUEST['account_password']) {
            (new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password']);
        }

        $response = [];
        if ($listingID = (int) $_REQUEST['listingID']) {
            if ($GLOBALS['config']['membership_module']) {
                rl('MembershipPlan')->isContactsAllow();
            }

            if ($GLOBALS['config']['membership_module'] && !rl('MembershipPlan')->is_contact_allowed) {
                $phrase_key = $GLOBALS['account'] ? 'contacts_not_available' : 'call_owner_forbidden_login_hint';
                $out = [
                    'status' => 'FORBIDDEN',
                    'phrases' => [
                        $phrase_key => rl('Lang')->getSystem($phrase_key),
                        'change_plan' => rl('Lang')->getPhrase('change_plan')
                    ]
                ];
            } elseif ($results = rl('Listings')->getContactPopupDetails($listingID)) {
                $response = [
                    'status' => 'OK',
                    'results' => $results
                ];
            } else {
                $response['status'] = 'error';
            }
        } else {
            $response['status'] = 'error';
        }

        return $response;
    }

    /**
     * Make services request
     *
     * @return array
     **/
    public function makeServiceRequest()
    {
        global $config;
        (new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password']);

        $response = [];
        rl('Listings');
        rl('Service')->hookAjaxRequest($response, $_REQUEST['mode'], '', RL_LANG_CODE);

        if ($response['results']['add_listing_url'] && $config['service_package_type_task']) {
            $listingID = (int) $_REQUEST['listingID'];
            $category_id = rl('Db')->getOne('Category_ID', "`ID` = {$listingID}", 'listings');
            $category_key = rl('Db')->getOne('Key', "`ID` = {$category_id}", 'categories');
            if ($category_key) {
                $mirror_category_key = preg_replace('/^services_/', 'tasks_', $category_key);
                $mirror_category_id = rl('Db')->getOne('ID', "`Key` = '{$mirror_category_key}' AND `Type` = '{$config['service_package_type_task']}'", 'categories');

                if (!$mirror_category_id) {
                    $phrase_key = 'categories+name+' . $category_key;
                    $category_name = $GLOBALS['rlLang']->getPhrase($phrase_key, null, null, true);

                    $sql = "
                        SELECT `T2`.`ID` FROM `{db_prefix}lang_keys` AS `T1`
                        LEFT JOIN `{db_prefix}categories` AS `T2` ON CONCAT('categories+name+', `T2`.`Key`) = `T1`.`Key`
                        WHERE `T1`.`Value` = '{$category_name}' AND `T1`.`Module` = 'category'
                        AND `T1`.`Code` = '" . RL_LANG_CODE . "' AND `T2`.`Type` = '{$config['service_package_type_task']}'
                    ";
                    $mirror_category_id = rl('Db')->getRow($sql, 'ID');
                }

                if ($mirror_category_id) {
                    $category = Category::getCategory((int) $mirror_category_id);
                }
            }
            if ($category) {
                $response['results']['post_category'] = $category;
            }

        }
        return $response;
    }
}
