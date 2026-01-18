<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.0
 *  LICENSE: FL0255RKH690 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmoplus.com
 *  FILE: MY_LISTINGS.INC.PHP
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

if (defined('IS_LOGIN')) {
    $reefless->loadClass('Listings');
    $reefless->loadClass('Actions');
    $reefless->loadClass('Search');

    // GMO Plus TASK 8 & 9: Listing Refresh System - Load refresh class
    if (file_exists(RL_CLASSES . 'rlListingRefresh.class.php')) {
        require_once RL_CLASSES . 'rlListingRefresh.class.php';
    }

    /* register ajax methods */
    $rlXajax->registerFunction(array('deleteListing', $rlListings, 'ajaxDeleteListing'));
    
    // GMO Plus TASK 8 & 9: Register refresh AJAX methods (disabled due to xajax version conflict)
    // $rlXajax->registerFunction('ajaxRefreshListing');
    // $rlXajax->registerFunction('ajaxCheckRefreshAvailability');

    /* define listings type */
    $l_type_key = substr($page_info['Key'], 3);
    $listings_type = $rlListingTypes->types[$l_type_key];

    if ($listings_type) {
        $rlSmarty->assign_by_ref('listings_type', $listings_type);
        $rlSmarty->assign('page_key', 'lt_' . $listings_type['Key']);
    }

    if ($config['one_my_listings_page']) {
        $search_forms = array();

        // get search forms
        foreach ($rlListingTypes->types as $lt_key => $ltype) {
            if ($ltype['Myads_search']) {
                if ($search_form = $rlSearch->buildSearch($lt_key . '_myads')) {
                    $search_forms[$lt_key] = $search_form;
                }

                unset($search_form);
            }
        }

        // define all available listing types & search forms
        $rlSmarty->assign_by_ref('listing_types', $rlListingTypes->types);
        $rlSmarty->assign_by_ref('search_forms', $search_forms);

        // save selected listing type in search
        if ($_POST['search_type'] || $_SESSION['search_type']) {
            if ($_POST['search_type']) {
                $_SESSION['search_type'] = $search_type = $_POST['search_type'];
            } else if ($_SESSION['search_type']
                && (isset($_GET[$search_results_url]) || $_GET['nvar_1'] == $search_results_url)
            ) {
                $_POST['search_type'] = $search_type = $_SESSION['search_type'];
            } else if ($_SESSION['search_type']) {
                // Clear previous search criteria data
                unset(
                    $_SESSION[$_SESSION['search_type'] . '_post'],
                    $_SESSION[$_SESSION['search_type'] . '_pageNum'],
                    $_SESSION['search_type'],
                    $_SESSION['post_form_key']
                );
            }

            if ($_POST['post_form_key']) {
                $_SESSION['post_form_key'] = $_POST['post_form_key'];
            }

            $rlSmarty->assign_by_ref('selected_search_type', $search_type);
            $rlSmarty->assign('refine_search_form', true);
        }
    }

    $add_listing_href = $config['mod_rewrite'] ? SEO_BASE . $pages['add_listing'] . '.html' : RL_URL_HOME . 'index.php?page=' . $pages['add_listing'];
    $rlSmarty->assign_by_ref('add_listing_href', $add_listing_href);

    /* paging info */
    $pInfo['current'] = (int) $_GET['pg'];

    /* fields for sorting */
    $sorting = array(
        'date'        => array(
            'name'  => $lang['date'],
            'field' => "date",
            'Type'  => 'date',
        ),
        'category'    => array(
            'name'  => $lang['category'],
            'field' => 'Category_ID',
        ),
        'status'      => array(
            'name'  => $lang['status'],
            'field' => 'Status',
        ),
        'expire_date' => array(
            'name'  => $lang['expire_date'],
            'field' => 'Plan_expire',
        ),
    );
    $rlSmarty->assign_by_ref('sorting', $sorting);

    /* define sort field */
    $sort_by = empty($_GET['sort_by']) ? $_SESSION['ml_sort_by'] : $_GET['sort_by'];
    $sort_by = $sort_by ? $sort_by : 'date';
    if (!empty($sorting[$sort_by])) {
        $order_field = $sorting[$sort_by]['field'];
    }
    $_SESSION['ml_sort_by'] = $sort_by;
    $rlSmarty->assign_by_ref('sort_by', $sort_by);

    /* define sort type */
    $sort_type = empty($_GET['sort_type']) ? $_SESSION['ml_sort_type'] : $_GET['sort_type'];
    $sort_type = !$sort_type && $sort_by == 'date' ? 'desc' : $sort_type;
    $sort_type = in_array($sort_type, array('asc', 'desc')) ? $sort_type : false;
    $_SESSION['ml_sort_type'] = $sort_type;
    $rlSmarty->assign_by_ref('sort_type', $sort_type);

    $rlHook->load('myListingsPreSelect');

    if ($pInfo['current'] > 1) {
        $bc_page = str_replace('{page}', $pInfo['current'], $lang['title_page_part']);

        // add bread crumbs item
        $bread_crumbs[1]['title'] .= $bc_page;
    }

    $reefless->loadClass('Plan');
    $available_plans = $rlPlan->getPlanByCategory(0, $account_info['Type'], true);
    $rlSmarty->assign_by_ref('available_plans', $available_plans);

    if ($listings_type) {
        $listing_type_key = $listings_type['Key'];
    } else if ($l_type_key == 'all_ads') {
        $listing_type_key = 'all_ads';
    }

    // build search form
    if ($config['one_my_listings_page']
        && ($_POST['search_type']
            || ($_SESSION['search_type']
                && (isset($_GET[$search_results_url]) || $_GET['nvar_1'] == $search_results_url)))
    ) {
        $listing_type_key = $_POST['search_type'] ?: $_SESSION['search_type'];

        if ($_POST['post_form_key'] || $_SESSION['post_form_key']) {
            $form_key = $_POST['post_form_key'] ?: $_SESSION['post_form_key'];
        }
    } else {
        $form_key = $listing_type_key . '_myads';
    }

    $form = false;
    if (($block_keys && array_key_exists('ltma_' . $listing_type_key, $block_keys))
        || $config['one_my_listings_page']
    ) {
        if ($form = $rlSearch->buildSearch($form_key)) {
            if ($listings_type) {
                $rlSmarty->assign('listing_type', $listings_type);
            }

            $rlSmarty->assign('refine_search_form', $form);
        }

        $rlCommon->buildActiveTillPhrases();
    }

    /* search results mode */
    if ($_GET['nvar_1'] == $search_results_url ||
        $_GET['nvar_2'] == $search_results_url ||
        isset($_GET[$search_results_url])
    ) {
        if ($_SESSION[$listing_type_key . '_post'] && $_REQUEST['action'] != 'search') {
            $_POST = $_SESSION[$listing_type_key . '_post'];
        }

        // redirect to My ads page to reset search criteria when type wasn't selected
        if ($config['one_my_listings_page'] && $_POST['action'] == 'search' && !$_POST['search_type']) {
            $reefless->redirect(null, $reefless->getPageUrl('my_all_ads'));
        }

        $rlSmarty->assign('search_results_mode', true);

        $data = $_SESSION[$listing_type_key . '_post'] = $_REQUEST['f']
        ? $_REQUEST['f']
        : $_SESSION[$listing_type_key . '_post'];

        // re-assign POST for refine search block
        if ($_POST['f']) {
            $_POST = $_POST['f'];
        }

        $pInfo['current'] = (int) $_GET['pg'];
        $data['myads_controller'] = true;

        // get current search form
        $rlSearch->getFields($form_key, $listing_type_key);

        // load fields from "quick_" form if "my_" form is empty
        if (!$rlSearch->fields && $config['one_my_listings_page'] && $search_type) {
            $rlSearch->fields = true;
        }

        // get listings
        $listings = $rlSearch->search($data, $listing_type_key, $pInfo['current'], $config['listings_per_page']);
        $rlSmarty->assign_by_ref('listings', $listings);

        $pInfo['calc'] = $rlSearch->calc;
        $rlSmarty->assign('pInfo', $pInfo);

        if ($listings) {
            $page_info['name'] = str_replace('{number}', $pInfo['calc'], $lang['listings_found']);
        } elseif ($_GET['pg']) {
            Flynax\Utils\Util::redirect($reefless->getPageUrl($page_info['Key']));
        }

        $rlHook->load('phpMyAdsSearchMiddle');

        // add bread crumbs item
        $page_info['title'] = $sort_by
        ? str_replace('{field}', $sorting[$sort_by]['name'], $lang['search_results_sorting_mode'])
        : $lang['search_results'];

        if ($pInfo['current']) {
            $page_info['title'] .= str_replace('{page}', $pInfo['current'], $lang['title_page_part']);
        }

        $bread_crumbs[] = array(
            'title' => $page_info['title'],
            'name'  => $lang['search_results'],
        );
    }
    /* browse mode */
    else {
        unset($_SESSION[$listing_type_key . '_post']);

        // get my listings
        $listings = $rlListings->getMyListings($listing_type_key, $order_field, $sort_type, $pInfo['current'], $config['listings_per_page']);
        $rlSmarty->assign('listings', $listings);

        /* redirect to the first page if no listings found */
        if (!$listings && $_GET['pg']) {
            if ($config['mod_rewrite']) {
                $url = SEO_BASE . $page_info['Path'] . ".html";
            } else {
                $url = SEO_BASE . "?page=" . $page_info['Path'];
            }

            header('Location: ' . $url, true, 301);
            exit;
        }
        /* redirect to the first page end */

        $pInfo['calc'] = $rlListings->calc;
        $rlSmarty->assign('pInfo', $pInfo);

        // remove box if necessary
        if (!$form || empty($listings)) {
            $rlCommon->removeSearchInMyAdsBox($listing_type_key);

            // remove all search boxes if access is denied for this user
            if ($listing_type_key == 'all_ads'
                && (isset($account_info['Type'])
                    && in_array($account_info['Type_ID'], explode(',', $page_info['Deny']))
                )
            ) {
                $rlCommon->removeAllSearchInMyAdsBoxes();
            }
        }
    }

    // Save current page number
    if ($_GET['pg']) {
        $_SESSION[$listing_type_key . '_pageNum'] = (int) $_GET['pg'];
    } else {
        unset($_SESSION[$listing_type_key . '_pageNum']);
    }
} else {
    $rlCommon->removeAllSearchInMyAdsBoxes();
}

// GMO Plus TASK 8 & 9: Listing Refresh AJAX Functions

/**
 * AJAX function to refresh a listing
 * TASK 8: Vasıta ilanları - 3 günde 1 yenileme
 * TASK 9: Kariyer ilanları - 7 günde 2 yenileme
 */
function ajaxRefreshListing($listing_id)
{
    global $rlDb, $account_info, $lang, $config, $_response;
    
    $out = array('status' => 'error', 'message' => '');
    
    try {
        // Login kontrolü
        if (!$account_info['ID']) {
            $out['message'] = isset($lang['refresh_not_allowed']) ? $lang['refresh_not_allowed'] : 'Access denied';
            if (isset($_response)) {
                $_response->script("handleRefreshResponse(" . json_encode($out) . ", {$listing_id});");
            }
            return $out;
        }
        
        // Listing ID validation
        $listing_id = (int) $listing_id;
        if (!$listing_id) {
            $out['message'] = isset($lang['refresh_not_allowed']) ? $lang['refresh_not_allowed'] : 'Invalid ID';
            if (isset($_response)) {
                $_response->script("handleRefreshResponse(" . json_encode($out) . ", {$listing_id});");
            }
            return $out;
        }
        
        // Get listing details and verify ownership
        $listing = $rlDb->getRow("
            SELECT l.*, c.Type as Category_Type 
            FROM " . RL_DBPREFIX . "listings l
            LEFT JOIN " . RL_DBPREFIX . "categories c ON l.Category_ID = c.ID  
            WHERE l.ID = '{$listing_id}' AND l.Account_ID = '{$account_info['ID']}'
            AND l.Status = 'active'
        ");
        
        if (!$listing) {
            $out['message'] = $lang['refresh_not_allowed'];
            $_response->script("handleRefreshResponse(" . json_encode($out) . ", {$listing_id});");
            return $out;
        }
        
        // Load refresh system
        if (!class_exists('rlListingRefresh')) {
            if (file_exists(RL_CLASSES . 'rlListingRefresh.class.php')) {
                require_once RL_CLASSES . 'rlListingRefresh.class.php';
                $refreshSystem = new rlListingRefresh();
            } else {
                $out['message'] = $lang['refresh_failed'];
                $_response->script("handleRefreshResponse(" . json_encode($out) . ", {$listing_id});");
                return $out;
            }
        } else {
            $refreshSystem = new rlListingRefresh();
        }
        
        // Check if refresh is allowed
        $listingType = $listing['Category_Type'] ?: 'general';
        $canRefresh = $refreshSystem->canRefresh($listing_id, $listingType);
        
        if (!$canRefresh['allowed']) {
            $out['message'] = $canRefresh['message'];
            $_response->script("handleRefreshResponse(" . json_encode($out) . ", {$listing_id});");
            return $out;
        }
        
        // Perform refresh
        $refreshResult = $refreshSystem->refreshListing($listing_id, $listingType);
        
        if ($refreshResult['success']) {
            $out['status'] = 'ok';
            $out['message'] = $lang['refresh_success'];
            $out['remaining_refreshes'] = $refreshResult['remaining_refreshes'];
            $out['next_refresh_date'] = $refreshResult['next_refresh_date'];
        } else {
            $out['message'] = $refreshResult['message'] ?: $lang['refresh_failed'];
        }
        
        // Send response to JavaScript
        $_response->script("handleRefreshResponse(" . json_encode($out) . ", {$listing_id});");
        
    } catch (Exception $e) {
        ob_clean();
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'System error: ' . $e->getMessage()
        ]);
        ob_end_flush();
        exit;
    }
    
    return $out;
}

/**
 * AJAX function to check refresh availability for a listing
 */
function ajaxCheckRefreshAvailability($listing_id, $listing_type = 'general')
{
    global $rlDb, $account_info, $lang, $config, $_response;
    
    $out = array('status' => 'error', 'remaining_refreshes' => 0, 'can_refresh' => false);
    
    try {
        // Login kontrolü
        if (!$account_info['ID']) {
            $_response->script("updateRefreshUI({$listing_id}, " . json_encode($out) . ");");
            return $out;
        }
        
        // Listing ID validation
        $listing_id = (int) $listing_id;
        if (!$listing_id) {
            $_response->script("updateRefreshUI({$listing_id}, " . json_encode($out) . ");");
            return $out;
        }
        
        // Verify listing ownership and get category type
        $listing = $rlDb->getRow("
            SELECT l.ID, l.Account_ID, l.Status, c.Type as Category_Type 
            FROM " . RL_DBPREFIX . "listings l
            LEFT JOIN " . RL_DBPREFIX . "categories c ON l.Category_ID = c.ID
            WHERE l.ID = '{$listing_id}' AND l.Account_ID = '{$account_info['ID']}'
            AND l.Status = 'active'
        ");
        
        if (!$listing) {
            $_response->script("updateRefreshUI({$listing_id}, " . json_encode($out) . ");");
            return $out;
        }
        
        // Override listing type with category type if available
        if (!empty($listing['Category_Type'])) {
            $listing_type = $listing['Category_Type'];
        }
        
        // Load refresh system
        if (!class_exists('rlListingRefresh')) {
            if (file_exists(RL_CLASSES . 'rlListingRefresh.class.php')) {
                require_once RL_CLASSES . 'rlListingRefresh.class.php';
                $refreshSystem = new rlListingRefresh();
            } else {
                $_response->script("updateRefreshUI({$listing_id}, " . json_encode($out) . ");");
                return $out;
            }
        } else {
            $refreshSystem = new rlListingRefresh();
        }
        
        // Check refresh availability
        $canRefresh = $refreshSystem->canRefresh($listing_id, $listing_type);
        
        $out['status'] = 'ok';
        $out['can_refresh'] = $canRefresh['allowed'];
        $out['remaining_refreshes'] = $canRefresh['remaining'] ?? 0;
        $out['message'] = $canRefresh['message'];
        $out['next_refresh_date'] = $canRefresh['next_refresh_date'] ?? null;
        
        // Send response to JavaScript
        $_response->script("updateRefreshUI({$listing_id}, " . json_encode($out) . ");");
        
    } catch (Exception $e) {
        ob_clean();
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'System error: ' . $e->getMessage()
        ]);
        ob_end_flush();
        exit;
    }
    
    return $out;
}

// GMO Plus TASK 8 & 9: Fallback AJAX Handlers for when Xajax fails

// Handle non-xajax refresh requests
if ($_POST['mode'] == 'refreshListing' && $_POST['listing_id']) {
    // Clean output buffer and set headers
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    // Disable xajax response to prevent conflicts
    $_response = null;
    
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    
    try {
        $listing_id = (int) $_POST['listing_id'];
        $listing_type = $_POST['listing_type'] ?: 'general';
        
        // Load refresh system
        if (!class_exists('rlListingRefresh')) {
            if (file_exists(RL_CLASSES . 'rlListingRefresh.class.php')) {
                require_once RL_CLASSES . 'rlListingRefresh.class.php';
            }
        }
        
        if (!$account_info['ID']) {
            echo json_encode([
                'status' => 'ERROR',
                'message' => 'Access denied'
            ]);
            exit;
        }
        
        if (!$listing_id) {
            echo json_encode([
                'status' => 'ERROR', 
                'message' => 'Invalid listing ID'
            ]);
            exit;
        }
        
        // Get listing and verify ownership
        $listing = $rlDb->getRow("
            SELECT l.*, c.Type as Category_Type 
            FROM " . RL_DBPREFIX . "listings l
            LEFT JOIN " . RL_DBPREFIX . "categories c ON l.Category_ID = c.ID  
            WHERE l.ID = '{$listing_id}' AND l.Account_ID = '{$account_info['ID']}'
            AND l.Status = 'active'
        ");
        
        if (!$listing) {
            echo json_encode([
                'status' => 'ERROR',
                'message' => 'Listing not found or access denied'
            ]);
            exit;
        }
        
        $refreshSystem = new rlListingRefresh();
        $listingType = $listing['Category_Type'] ?: 'general';
        
        // Check if refresh is allowed
        $canRefresh = $refreshSystem->canRefresh($listing_id, $listingType);
        if (!$canRefresh['allowed']) {
            echo json_encode([
                'status' => 'ERROR',
                'message' => $canRefresh['message']
            ]);
            exit;
        }
        
        // Perform refresh
        $refreshResult = $refreshSystem->refreshListing($listing_id, $listingType);
        
        if ($refreshResult['success']) {
            echo json_encode([
                'status' => 'OK',
                'message' => 'İlanınız başarıyla yenilendi',
                'data' => $refreshResult
            ]);
        } else {
            echo json_encode([
                'status' => 'ERROR',
                'message' => $refreshResult['message'] ?: 'Refresh failed'
            ]);
        }
        
    } catch (Exception $e) {
        ob_clean();
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'System error: ' . $e->getMessage()
        ]);
    }
    ob_end_flush();
    exit;
}

// Handle non-xajax refresh availability check
if ($_POST['mode'] == 'checkRefreshAvailability' && $_POST['listing_id']) {
    // Clean output buffer and set headers
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    // Disable xajax response to prevent conflicts
    $_response = null;
    
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    
    try {
        $listing_id = (int) $_POST['listing_id'];
        $listing_type = $_POST['listing_type'] ?: 'general';
        
        // Load refresh system
        if (!class_exists('rlListingRefresh')) {
            if (file_exists(RL_CLASSES . 'rlListingRefresh.class.php')) {
                require_once RL_CLASSES . 'rlListingRefresh.class.php';
            }
        }
        
        if (!$account_info['ID']) {
            echo json_encode([
                'status' => 'ERROR',
                'message' => 'Access denied',
                'data' => ['can_refresh' => false, 'remaining_refreshes' => 0]
            ]);
            exit;
        }
        
        if (!$listing_id) {
            echo json_encode([
                'status' => 'ERROR',
                'message' => 'Invalid listing ID',
                'data' => ['can_refresh' => false, 'remaining_refreshes' => 0]
            ]);
            exit;
        }
        
        // Verify listing ownership and get category type
        $listing = $rlDb->getRow("
            SELECT l.ID, l.Account_ID, l.Status, c.Type as Category_Type 
            FROM " . RL_DBPREFIX . "listings l
            LEFT JOIN " . RL_DBPREFIX . "categories c ON l.Category_ID = c.ID
            WHERE l.ID = '{$listing_id}' AND l.Account_ID = '{$account_info['ID']}'
            AND l.Status = 'active'
        ");
        
        if (!$listing) {
            echo json_encode([
                'status' => 'ERROR',
                'message' => 'Listing not found',
                'data' => ['can_refresh' => false, 'remaining_refreshes' => 0]
            ]);
            exit;
        }
        
        // Override listing type with category type if available
        if (!empty($listing['Category_Type'])) {
            $listing_type = $listing['Category_Type'];
        }
        
        $refreshSystem = new rlListingRefresh();
        
        // Check refresh availability
        $canRefresh = $refreshSystem->canRefresh($listing_id, $listing_type);
        
        $result = [
            'can_refresh' => $canRefresh['allowed'],
            'remaining_refreshes' => $canRefresh['remaining'] ?? 0,
            'message' => $canRefresh['message'],
            'next_refresh_date' => $canRefresh['next_refresh_date'] ?? null
        ];
        
        echo json_encode([
            'status' => 'OK',
            'data' => $result
        ]);
        
    } catch (Exception $e) {
        ob_clean();
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'System error: ' . $e->getMessage()
        ]);
    }
    ob_end_flush();
    exit;
}

// GMO Plus DEBUG: Test endpoint
if ($_POST['mode'] == 'test') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'OK',
        'message' => 'Test endpoint working',
        'post_data' => $_POST,
        'get_data' => $_GET
    ]);
    exit;
}

// GMO Plus DEBUG: Log all POST requests for debugging
if ($_POST) {
    error_log('GMO Plus DEBUG - POST data: ' . print_r($_POST, true));
    
    if ($_POST['mode']) {
        error_log('GMO Plus DEBUG - Mode detected: ' . $_POST['mode']);
    }
    
    if ($_POST['mode'] == 'checkRefreshAvailability') {
        error_log('GMO Plus DEBUG - checkRefreshAvailability condition matched!');
    }
    
    if ($_POST['mode'] == 'refreshListing') {
        error_log('GMO Plus DEBUG - refreshListing condition matched!');
    }
}
