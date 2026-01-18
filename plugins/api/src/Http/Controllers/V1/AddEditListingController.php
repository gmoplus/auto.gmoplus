<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: ADDEDITLISTINGCONTROLLER.PHP
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
use Flynax\Api\Http\Controllers\V1\AppController;
use Flynax\Api\Http\Controllers\V1\AccountController;
use Flynax\Utils\Category;

class AddEditListingController extends BaseController
{

    /**
     * Add listing
     *
     * @return array
     **/
    public function addListing()
    {
        global $config;

        if ($_POST['post']) {
            $_POST = json_decode($_POST['post'], true);
            if ((new AccountController)->issetAccount($_POST['account_id'], $_POST['account_password'])) {
                $category_id = $_POST['category_id'];
                $listing_type_key = $_POST['listing_type'];
                $listing_type = rl('ListingTypes')->types[$listing_type_key];
                $data = $_POST['data'];

                if ($_POST['plan_type'] == 'listing') {
                    $plan_info = rl('Plan')->getPlan($_POST['plan_id'], $_POST['account_id']);
                }
                else {
                    $plan_info = rl('MembershipPlan')->getPlanByProfile($GLOBALS['account_info']);
                }

                $fields = [];
                $category = Category::getCategory($category_id);
                Category::buildForm(
                    $category,
                    $listing_type,
                    $fields
                );
                
                $info = [];
                $info['Category_ID'] = $category_id;
                if (!$config['edit_listing_auto_approval']) {
                    $info['Status'] = 'pending';
                }
                $info['Account_ID'] = $_POST['account_id'];
                $info['Date'] = 'NOW()';
                $info['Status'] = 'incomplete';
                $info['Last_type'] = $_POST['listing_plan_type'];
                $info['Last_step'] = 'form';
                $info['Plan_ID'] = $_POST['plan_id'];
                $info['Plan_type'] = $_POST['plan_type'] ? : 'listing';
                if ($plan_info['Crossed']) {
                    $info['Crossed'] = implode(',', $_POST['crossed_categories']);
                }

                if (rl('Listings')->create($info, $data, $fields, $plan_info)) {
                    $listing_id = rl('Listings')->id;
                    // simulate instance
                    $this->listingID = (int) $listing_id;
                    $this->formFields = $fields;
                    $this->listingType = $listing_type;

                    $this->stepDone($listing_id, $plan_info);

                    // complete saving
                    rl('Hook')->load('afterListingCreate', $this, $info, $data, $plan_info);

                    if (!$this->listingData) {
                        $listingsController = new ListingsController();
                        $this->listingData = $listingsController->buildReturnMyListing($listing_id);
                    }

                    // Adapt to services
                    if ($_POST['service_notify_id'] && $config['package_name'] == 'service' && $category['Type'] == $config['service_package_type_task']) {
                        $_SESSION['add_listing']['notify_id'] = $_POST['service_notify_id'];

                        $manageListing = $this;
                        $update = array(
                            'fields' => array(
                                'Last_step' => '',
                            ),
                            'where'  => array(
                                'ID' => $listing_id,
                            ),
                        );
                        $isFree = $plan_info['Price'] > 0 ? false : true;
                        if (!$isFree) {
                            $update['fields']['Status'] = 'incomplete';
                        }

                        rl('Service')->hookAfterListingDone($manageListing, $update, $isFree);
                    }

                    $response['listing'] = $this->listingData;
                }
                else {
                    $response = 'error';
                    $GLOBALS['rlDebug']->logger("API: " . __FUNCTION__ . "(), addListing() method returned false");
                }
            }
        }
        return $response;
    }

    /**
     * Done step handler
     * 
     * $id - listing id
     * $plan_info - plan info
     */
    public function stepDone($id, $plan_info)
    {
        global $lang, $config;

        $status = 'incomplete';
        $planType = $_POST['plan_type'];

        $info = [];
        $status = $this->updatePlan($id, $info, 'add');

        $listingsController = new ListingsController();
        $this->listingData = $listingsController->buildReturnMyListing($id);

        
        // Send message to listing owner
        if ($status != 'incomplete') {
            $mail_tpl = rl('Mail')->getEmailTemplate(
                $config['listing_auto_approval']
                    ? 'free_active_listing_created'
                    : 'free_approval_listing_created'
            );

            if ($config['listing_auto_approval']) {
                $link = $this->listingData['url'];
            } else {
                $myPageKey = $config['one_my_listings_page'] ? 'my_all_ads' : 'my_' . $this->listingData['Listing_type'];
                $link      = rl('reefless')->getPageUrl($myPageKey);
            }

            $mail_tpl['body'] = str_replace(
                ['{name}', '{link}'],
                [$GLOBALS['account_info']['Full_name'], '<a href="' . $link . '">' . $link . '</a>'], $mail_tpl['body']
            );
            rl('Mail')->send($mail_tpl, $GLOBALS['account_info']['Mail']);
        }

        // Send admin notification 
        $paid_status = $status == 'incomplete' ? $lang['not_paid'] : $lang['free'];
        $mail_tpl = rl('Mail')->getEmailTemplate('admin_listing_added');

        $find = array('{username}', '{link}', '{date}', '{status}', '{paid}');
        $replace = array(
            $GLOBALS['account_info']['Username'],
            '<a href="' . RL_URL_HOME . ADMIN . '/index.php?controller=listings&action=view&id=' . $id . '">' . $this->listingData['listing_title'] . '</a>',
            date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT)),
            $lang[$status],
            $paid_status,
        );
        $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);

        if ($config['listing_auto_approval']) {
            $mail_tpl['body'] = preg_replace('/\{if activation is enabled\}(.*)\{\/if\}/', '', $mail_tpl['body']);
        } else {
            $hash = md5($this->listingData['Date']);
            $activation_link = RL_URL_HOME . ADMIN . '/index.php?controller=listings&action=remote_activation&id=' . $id . '&hash=' . $hash;
            $activation_link = '<a href="' . $activation_link . '">' . $activation_link . '</a>';
            $mail_tpl['body'] = preg_replace(
                '/(\{if activation is enabled\})(.*)(\{activation_link\})(.*)(\{\/if\})/',
                '$2 ' . $activation_link . ' $4',
                $mail_tpl['body']
            );
        }
        rl('Mail')->send($mail_tpl, $config['notifications_email']);

        // Recount listings in related category
        if ($GLOBALS['config']['listing_auto_approval']) {
            rl('Categories')->listingsIncrease($id);
            rl('Categories')->accountListingsIncrease($GLOBALS['account_info']['ID']);
        }
    }

    /**
     * Edit listing
     *
     * @return array
     **/
    public function editListing()
    {
        global $config;

        if ($_POST['post']) {
            $_POST = json_decode($_POST['post'], true);
            if ($_POST['listing_id'] && (new AccountController)->issetAccount($_POST['account_id'], $_POST['account_password'])) {

                if ($_FILES) {
                    AppController::adaptFilesFields();
                }

                $listing_id = $_POST['listing_id'];
                $category_id = $_POST['category_id'];
                $listing_type_key = $_POST['listing_type'];
                $listing_type = rl('ListingTypes')->types[$listing_type_key];
                $data = $_POST['data'];

                $fields = [];
                $category = Category::getCategory($category_id);
                Category::buildForm(
                    $category,
                    $listing_type,
                    $fields
                );
                
                $info = [];
                $info['Category_ID'] = $category_id;

                // check incomplete listings
                $status = rl('Db')->getOne('Status', "`ID` = {$listing_id}", 'listings');
                if ($status == 'incomplete') {
                    $this->updatePlan($listing_id, $info, 'edit');
                }

                if (!$config['edit_listing_auto_approval']) {
                    $info['Status'] = 'pending';
                }

                if (rl('Listings')->edit($listing_id, $info, $data, $fields, [])) {
                    $this->listingID = (int) $listing_id;
                    $this->formFields = $fields;
                    $this->listingType = $listing_type;

                    // remove media
                    $this->removeListingMedia($listing_id, $_POST['media_removed']);

                    if ($_POST['media_reorder']) {
                        \Flynax\Utils\ListingMedia::reorder(
                            $listing_id, 
                            $_POST['media_reorder'], 
                            $_SESSION['account']
                        );
                    }

                    // Complete edit listing
                    rl('Hook')->load('afterListingEdit', $this, $info, $data);

                    $listingsController = new ListingsController();
                    $listing = $listingsController->buildReturnMyListing($listing_id);

                    $response['listing'] = $listing;
                }
                else {
                    $GLOBALS['rlDebug']->logger("API: " . __FUNCTION__ . "(), edit() method returned false");
                }
            }
        }
        return $response;
    }
    
    /**
     * Update incomplete listing plan
     *
     * @return array
     * @since 1.0.1
     **/
    public function updatePlan($listing_id, &$info, $mode)
    {
        global $plan_info;

        $status = 'incomplete';
        $planType = $_POST['plan_type'] ? $_POST['plan_type'] : 'listing';

        // get plan info
        $plan_info = $plan_info ? $plan_info : rl('Plan')->getPlan($_POST['plan_id'], $_POST['account_id']);

        // Define is listing is free
        if (
            $plan_info['Price'] <= 0
            || ($plan_info['Price'] > 0
                && (
                    (
                        $planType == 'listing'
                        && $plan_info['Package_ID']
                        && $plan_info['Listings_remains'] > 0
                    )
                    || (
                        $planType == 'account'
                        && ($plan_info['Listings_remains'] > 0 || $plan_info['Listings_number'] == 0)
                    )
                )
            )
        ) {
            $is_free = true;
        }

        if ($is_free) {
            $listing_mode = $_POST['listing_plan_type'] == 'featured' ? 'featured' : 'standard';
            $status = $GLOBALS['config']['listing_auto_approval'] ? 'active' : 'pending';
            $info['Last_step'] = '';
            $info['Last_type'] = '';
            $info['Cron'] = '0';
            $info['Cron_notified'] = '0';
            $info['Cron_featured'] = '0';
            $info['Status'] = $status;

            if ($planType == 'account' ) {
                if ($GLOBALS['account_info']['Plan_ID']) {
                    $info['Pay_date'] = 'NOW()';
                    $info['Featured_ID'] = $listing_mode == 'featured' ? $plan_info['ID'] : 0;
                    $info['Featured_date'] = $listing_mode == 'featured' ? 'NOW()' : 'NULL';
                    $info['Plan_type'] = 'account';
                    rl('MembershipPlan')->updatePlanUsing($_POST['listing_plan_type']);
                }
            }
            else if ($planType == 'listing' ) {
                $update_featured_id = ($plan_info['Featured'] && !$plan_info['Advanced_mode']) || $listing_mode == 'featured' ? $plan_info['ID'] : '';
                $update_featured_date = ($plan_info['Featured'] && !$plan_info['Advanced_mode']) || $listing_mode == 'featured' ? 'IF(UNIX_TIMESTAMP(NOW()) > UNIX_TIMESTAMP(DATE_ADD(`Featured_date`, INTERVAL ' . $plan_info['Listing_period'] . ' DAY)) OR IFNULL(UNIX_TIMESTAMP(`Featured_date`), 0) = 0, NOW(), DATE_ADD(`Featured_date`, INTERVAL ' . $plan_info['Listing_period'] . ' DAY))' : '';
                $update_date = 'IF(UNIX_TIMESTAMP(NOW()) > UNIX_TIMESTAMP(DATE_ADD(`Pay_date`, INTERVAL ' . $plan_info['Listing_period'] . ' DAY)) OR IFNULL(UNIX_TIMESTAMP(`Pay_date`), 0) = 0, NOW(), DATE_ADD(`Pay_date`, INTERVAL ' . $plan_info['Listing_period'] . ' DAY))';

                $info['Plan_ID'] = $plan_info['ID'];
                $info['Pay_date'] = $update_date;
                $info['Featured_ID'] = $update_featured_id;
                $info['Featured_date'] = $update_featured_date;
                $info['Last_type'] = $listing_mode;
                $info['Plan_type'] = 'listing';

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
                        'Account_ID' => $GLOBALS['account_info']['ID'],
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
                            'Account_ID' => $GLOBALS['account_info']['ID'],
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
                                'Account_ID' => $GLOBALS['account_info']['ID'],
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
            }
        }

        if ($mode == 'add') {
            if ($info) {
                $update = array(
                    'fields' => $info,
                    'where'  => array(
                        'ID' => $listing_id,
                    ),
                );
                rl('Db')->update($update, 'listings');
            }
            return $status;
        }
    }

    /**
     * Build add listing forms
     *
     * @return array
     **/
    public function buildAddListingForm()
    {
        if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
           
            $id = (int) $_REQUEST['id'];
            $account_type = rl('Valid')->xSql($_REQUEST['account_type']);
            $listing_type = rl('Valid')->xSql($_REQUEST['type']);

            $category = Category::getCategory($id);
            $form = Category::buildForm(
                $category,
                rl('ListingTypes')->types[$listing_type],
                rl('Categories')->fields
            );

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
                        if ($eventTypeKey == $listing_data['Listing_type'] && $group['Key'] == 'event_rates' && $value['Type'] == 'price') {
                            $value['custom_event_price'] = true;
                        }
                        $fields[$value['Key']] = $value;
                    }
                }
            }
            if ($GLOBALS['plugins']['shoppingCart'] && rl('ShoppingCart', null, 'shoppingCart')->isConfigured()) {
                $response['shoppingFields'] = (new ShoppingCartController)::buildAddEditShoppingFields(
                    $fields,
                    rl('ListingTypes')->types[$listing_type]
                );
            }

            $fields = AppController::adaptFields($fields, [], 'listing');

            $plan_type = rl('MembershipPlan')->defineAllowedPlanType();

            $plans = (new ListingsController)->fetchPlans($plan_type, $id, $account_type, false);

            $response['fields'] = $fields;
            $response['plans'] = $plans;
            $response['plan_type'] = $plan_type;

            if ($_REQUEST['simulate_category']) {
                $response['category'] = $category;
                $listingTypeKey = $category['Type'];
                // Get category parents
                $category_parents[] = 0;
                if ($category['Parent_IDs']) {
                    $category_parents = array_merge($category_parents, explode(',', $category['Parent_IDs']));
                }
                // Add parent categories data
                foreach ($category_parents as $parent) {
                    $categories[$parent] = rl('Categories')->getCatTree($parent, $listingTypeKey);
                }
                $response['categories'] = $categories;
            }
        }

        return $response;
    }

    /**
     * Get listing info for edit
     *
     * @return array
     **/
    
    public function editListingInfo()
    {
        $response = [];
        $listing_id = (int) $_REQUEST['listing_id'];

        if ($listing_id && (new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {

            // Get listing data
            $sql = "
                SELECT `T1`.*, `T2`.`Cross` AS `Plan_crossed`, `T2`.`Key` AS `Plan_key`,
                `T3`.`Type` AS `Listing_type`
                FROM `{db_prefix}listings` AS `T1`
                LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID`
                LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID`
                WHERE `T1`.`ID` = {$listing_id} LIMIT 1
            ";
            $listing_data = rl('Db')->getRow($sql);

            $response['category'] = Category::getCategory($listing_data['Category_ID']);

            // Get category parents
            $category_parents[] = 0;
            if ($response['category']['Parent_IDs']) {
                $category_parents = array_merge($category_parents, explode(',', $response['category']['Parent_IDs']));
            }
            // Add parent categories data
            foreach ($category_parents as $parent) {
                $response['categories'][$parent] = rl('Categories')->getCatTree($parent, $listing_data['Listing_type']);
            }

            $listingsController = new ListingsController();
            // Build plans
            $plan_type = rl('MembershipPlan')->defineAllowedPlanType();
            $response['plan_type'] = $plan_type;
            $response['plans'] = $listingsController->fetchPlans($plan_type, $listing_data['Category_ID'], $GLOBALS['account_info']['Type'], false);

            // Build form
            $form = Category::buildForm(
                $response['category'],
                rl('ListingTypes')->types[$listing_data['Listing_type']],
                rl('Categories')->fields
            );

            // Event
            $eventTypeKey = $listingsController->eventTypeKey();
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
                        if ($eventTypeKey == $listing_data['Listing_type'] && $group['Key'] == 'event_rates' && $value['Type'] == 'price') {
                            $value['custom_event_price'] = true;
                        }
                        $fields[$value['Key']] = $value;
                    }
                }
            }

            if ($GLOBALS['plugins']['shoppingCart'] && rl('ShoppingCart', null, 'shoppingCart')->isConfigured()) {
                $response['shoppingFields'] = (new ShoppingCartController)::buildAddEditShoppingFields(
                    $fields,
                    rl('ListingTypes')->types[$listing_data['Listing_type']]
                );
            }

            $fields = AppController::adaptFields($fields, $listing_data, 'listing');
            $response['fields'] = $fields;

            if ($eventTypeKey == $listing_data['Listing_type']) {
                $eventRates = new \Flynax\Plugins\Events\EventsRates();
                $response['event_rates'] = $eventRates->getRates($listing_data['ID']);
            }

            $response['listing_data'] = $listing_data;

            /* Get listing media */
            $media = [];
            if ($listing_data['Plan_ID']) {
                $plan = $response['plans'][$listing_data['Plan_ID']];
                $photos_limit = $plan['Image_unlim'] ? true : $plan['Image'];
                $videos_limit = $plan['Video_unlim'] ? true : $plan['Video'];

                /* Get listing media */
                $media = \Flynax\Utils\ListingMedia::get(
                    $listing_id, 
                    $photos_limit, 
                    $videos_limit, 
                    rl('ListingTypes')->types[$listing_data['Listing_type']]
                );
            }
            $response['media'] = $media;
        }

        return $response;
    }

    /**
     * Remove listing media
     * @param int   $listing_id - listing id
     * @param array $media      - listing media ids
     *
     **/
    public function removeListingMedia($listing_id, $media)
    {
        if (!$listing_id || !is_array($media) || count($media) < 1) {
            return;
        }
        foreach ($media as $id) {
            \Flynax\Utils\ListingMedia::delete($listing_id, $id, $_SESSION['account']);
        }
    }

    /**
     * Upload listing media
     *
     **/
    public function uploadMedia()
    {
        
        if ($_REQUEST['account_id'] && $_REQUEST['account_password']) {
            if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
                $listing_id = (int) $_REQUEST['listing_id'];

                $uploader = (new ListingPictureUploadAdapter())
                    ->setListingId($listing_id)
                    ->setImageOrientation(0);
                $res = $uploader->uploadFromGlobals();
                if (isset($res['error'])) {
                    $response['status'] = 'error';
                    $response['message'] = $res['error'];
                }
                else {
                    $response['status'] = 'ok';
                    $response['data'] = (new ListingsController)->buildReturnMyListing($listing_id);
                }
            }
        }
        else {
            $response['status'] = 'error';
            $response['message'] = $GLOBALS['lang']['error_request_api'];
        }

        return $response;
    }

    /**
     * Upload listing youtube media
     *
     **/
    public function uploadYoutubeMedia()
    {
        if ($_REQUEST['account_id'] && $_REQUEST['account_password']) {

            if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
                $listing_id = (int) $_REQUEST['listing_id'];
                $plan_info = rl('Plan')->getPlan($_REQUEST['plan_id'], $_REQUEST['account_id']);

                if ($results = \Flynax\Utils\ListingMedia::addYouTube(
                    $_REQUEST['listing_id'],
                    $_REQUEST['link'],
                    $GLOBALS['account_info'],
                    $plan_info,
                    $_REQUEST['index']
                )) {
                    $response = array(
                        'status'  => 'OK',
                        'results' => $results,
                        'data' => '',
                    );

                    $response['data'] = (new ListingsController)->buildReturnMyListing($listing_id);
                } else {
                    $response['status'] = 'ERROR';
                    $response['message'] = $GLOBALS['lang']['error_request_api'];
                }
            }
        }
        else {
            $response['status'] = 'error';
            $response['message'] = $GLOBALS['lang']['error_request_api'];
        }


        return $response;
    }

    /**
     * Allow post listings
     * @since 1.0.1
     **/
    public function isAddListingAllow()
    {
        if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
            $plan_type = rl('MembershipPlan')->defineAllowedPlanType();
            $allow = 1;
            if ($plan_type == 'account') {
                $allow = rl('MembershipPlan')->isAddListingAllow();
            }
            $response['allow'] = $allow && $plan_type ? 1 : 0;
        }
        $response['status'] = 'ok';
        return $response;
    }

    /**
     * Delete event rate
     *
     **/
    public function deleteEventRate()
    {
        if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
            $id = (int) $_REQUEST['id'];
            $eventRates = new \Flynax\Plugins\Events\EventsRates();
            $eventRates->deleteRate($id);
            $response['status'] = 'ok';
        }

        return $response;
    }

}
