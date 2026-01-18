<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLLISTINGNAVIGATOR.CLASS.PHP
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

use \Flynax\Utils\Category;

class rlListingNavigator extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
	/**
	* session name for listing type search
	**/
	var $lts = 'lnp_listingTypeSearch';

	/**
	* session name for keyword search
	**/
	var $kws = 'lnp_keyword_search';

	/**
	* session name for browse category page
	**/
	var $bc = 'lnp_browse_category';

	/**
	* session name for recently added page
	**/
	var $ra = 'lnp_recently_added';

	/**
	* session name for account details page
	**/
	var $al = 'lnp_account_listings';

    /**
     * New navigation hooks system flag
     *
     * @var boolean
     * @since 2.2.0
     */
    var $hasNewHooks = false;

    /**
     * Class constructor
     *
     * @since 2.2.0
     */
    public function __construct()
    {
        $this->hasNewHooks = version_compare($GLOBALS['config']['rl_version'], '4.9.0', '>');

        // Enabling new option for version <= 4.9.0
        if (!$this->hasNewHooks) {
            $GLOBALS['config']['ln_display_basic'] = true;
        }
    }

	/**
	* get navigation details by requested listing ID
	*
	* @param int $id - requested listing ID
	* @param int $pass_current_stack - passed current stack number
	* @param int $pass_current_index - passed current index
	*
	* @todo assign listing navigation data to the template
	**/
	function get($id = false, &$listing_data = null, $pass_current_stack = false, $pass_current_index = false)
	{
		global $rlSmarty, $page_info, $config, $pages, $listing_type, $listing_data, $listing_title, $rlListingTypes, $reefless;

		/* define item key */
		$get_item = $this->getItemKey($page_info, $id, $listing_data);
		$item = $_GET['request'] ? $_GET['request'] : $get_item;

		if (!$item || !$id || !$_SESSION[$item] || !$_SESSION[$item]['stacks']) {
			return false;
		}

		$current_stack = $pass_current_stack;
		$current_index = $pass_current_index;

		if ($current_stack === false && $current_index === false) {
			foreach ($_SESSION[$item]['stacks'] as $stack_id => &$stacks) {
				foreach ($stacks as $index => $listing) {
					if ($id == $listing['ID']) {
						$current_stack = $stack_id;
						$current_index = $index;
					}
				}
			}
		}

        // Clear stack data if the href index presents, session cache will be rebuild
        if ($data_current = current(current($_SESSION[$item]['stacks']))) {
            if ($data_current['href']) {
                unset($_SESSION[$item]['stacks']);
            }
        }

        $prev_stack = $_SESSION[$item]['stacks'][$current_stack-1];

		/* get previous listing data */
		if ($_SESSION[$item]['stacks'][$current_stack][$current_index-1]) {
			$data_prev = $_SESSION[$item]['stacks'][$current_stack][$current_index-1];
		} elseif ($prev_stack && $prev_stack[count($prev_stack) - 1]) {
			$data_prev = $prev_stack[count($prev_stack) - 1];
		} else {
			if ($pass_current_stack === false && $current_stack > 1) {
				$this->getNextStack($item, $current_stack, 'prev');
				$this->get($id, $listing_data, $current_stack-1, count($_SESSION[$item]['stacks'][$current_stack-1])); // we don't know which is the latest index in the stack :(
			}
		}

		if ($data_prev) {
            $data_prev['href'] = $reefless->url('listing', $data_prev);
			$rlSmarty->assign_by_ref('lnp_data_prev', $data_prev);
		}

		/* get next listing data */
		if ($_SESSION[$item]['stacks'][$current_stack][$current_index+1]) {
			$data_next = $_SESSION[$item]['stacks'][$current_stack][$current_index+1];
		} elseif ($_SESSION[$item]['stacks'][$current_stack+1][0]) {
			$data_next = $_SESSION[$item]['stacks'][$current_stack+1][0];
		} else {
			if ($pass_current_stack === false) {
				if ($this->getNextStack($item, $current_stack, 'next')) {
					$this->get($id, $listing_data, $current_stack + 1, -1);
				}
			}
		}

		if ($data_next) {
            $data_next['href'] = $reefless->url('listing', $data_next);
			$rlSmarty->assign_by_ref('lnp_data_next', $data_next);
		}
	}

	/**
	* get next stack data
	*
	* @param string $item - item key
	* @param int $stack - requested stack
	* @param string $direction - search direction, next or prev
	*
	* @todo populate next step and run get method
	**/
	function getNextStack($item = false, $current_stack = false, $direction = 'next')
	{
		global $config, $rlListingTypes, $sorting, $reefless;

		$stack = $direction == 'next' ? $current_stack + 1 : $current_stack - 1;

		switch ($item) {
			case $this->lts:
				$reefless->loadClass('Search');

				$GLOBALS['rlSearch']->fields = $_SESSION[$this->lts]['data']['fields'];

				$listings = $GLOBALS['rlSearch']->search(
					$_SESSION[$this->lts]['data']['data'],
					$_SESSION[$this->lts]['data']['listing_type_key'],
					$stack,
					$config['listings_per_page']
				);

				if (empty($listings)) {
					return false;
				}

				$this->listingTypeSearch($listings, $stack);
				break;

			case $this->kws;
				$reefless->loadClass('Search');

				$GLOBALS['rlSearch']->fields['keyword_search'] = array(
					'Key' => 'keyword_search',
					'Type' => 'text'
				);

				$sorting = $_SESSION[$this->kws]['data']['sorting'];
				$listings = $GLOBALS['rlSearch']->search($_SESSION[$this->kws]['data']['data'], false, $stack, $config['listings_per_page']);

				if (empty($listings)) {
					return false;
				}

				$this->keywordSearch($listings, $stack);
				break;

			case $this->bc;
				$reefless->loadClass('Listings');

				$sorting = $_SESSION[$this->bc]['data']['sorting'];
				$listings = $GLOBALS['rlListings']->getListings(
					$_SESSION[$this->bc]['data']['category_id'],
					$_SESSION[$this->bc]['data']['order_field'],
					$_SESSION[$this->bc]['data']['sort_type'],
					$stack,
					$config['listings_per_page'],
					$_SESSION[$this->bc]['data']['listing_type']
				);

				if (empty($listings)) {
					return false;
				}

				$this->browseCategory($listings, $stack);
				break;

			case $this->ra;
				$reefless->loadClass('Listings');

				$requested_type = $_SESSION['recently_added_type'];
				$listings = $GLOBALS['rlListings']->getRecentlyAdded($stack, $config['listings_per_page'], $requested_type);

				if (empty($listings)) {
					return false;
				}

				$this->recentlyAdded($listings, $stack);

				break;

			case $this->al;
				$reefless->loadClass('Listings');

				$sorting = $_SESSION[$this->at]['data']['sorting'];
				$listings = $GLOBALS['rlListings']->getListingsByAccount(
					$_SESSION[$this->at]['data']['account_id'],
					$_SESSION[$this->at]['data']['sort_by'],
					$_SESSION[$this->at]['data']['sort_type'],
					$stack,
				$config['listings_per_page']);

				if (empty($listings)) {
					return false;
				}

				$this->accountListings($listings, $stack);

				break;
		}

		return true;
	}
	
	/**
	* get item key by previous visited page key
	*
	* @param array $page_info - current page info
	* @param int $id - requested listing ID
	* @param array $listing_data - referent to listing data array
	*
	* @return item key
	**/
	function getItemKey($page_info = false, $id = false, &$listing_data = null)
	{
		// get item name from cache
		if ($_SESSION['ln_item']) {
			$item = $_SESSION['ln_item'];
		}
		// define item name by previous page key
		else {
			if ((bool) preg_match('/^lt_.*_search/', $page_info['prev'])) {
				$item = $this->lts;
			} elseif ($page_info['prev'] == 'search') {
				$item = $this->kws;
			} elseif ((bool) preg_match('/^lt_/', $page_info['prev'])) {
				$item = $this->bc;
			} elseif ($page_info['prev'] == 'listings') {
				$item = $this->ra;
			} elseif ((bool) preg_match('/^at_/', $page_info['prev'])) {
				$item = $this->al;
			} else {
				$item = $this->bc;
			}

			// save item while use browser listing details
			$_SESSION['ln_item'] = $item;
		}

		if ($item == $this->bc) {
			$this->directListing($id, $listing_data);
		}

		return $item;
	}

	/**
	* simulate the browse category behavior for the direct listing request
	*
	* @param int $id - requested listing ID
	* @param array $listing_data - referent to listing data array
	*
	* @todo prepare the data by listing category
	**/
	function directListing($id = false, &$listing_data = null)
	{
        global $reefless;

		if (!$id || !$listing_data || $listing_data['Status'] != 'active') {
			return;
        }

		if ($this->hasInStack($this->bc, $id)) {
			return;
        }

        define('RL_LN_DIRECT_LISTING', true);

		$reefless->loadClass('Listings');
		$listings = $GLOBALS['rlListings']->getListings($listing_data['Category_ID'], false, null, 1, 30, $listing_data['Listing_type']);

        if (!$listings) {
            return;
        }

		/* get sorting form fields */
		$sorting_fields = $GLOBALS['rlListings']->getFormFields($listing_data['Category_ID'], 'short_forms', $listing_data['Cat_type']);
		foreach ($sorting_fields as &$field) {
			if ($field['Details_page']) {
				$sorting[$field['Key']] = $field;
			}
		}
		unset($sorting_fields);

		$_SESSION[$this->bc]['data'] = array(
			'category_id' => $listing_data['Category_ID'],
			'order_field' => false,
			'sort_type' => 'ASC',
			'sorting' => $sorting
		);

		$this->browseCategory($listings, 1);
	}

	/**
	* populate the stack listings data
	*
	* @param array $listings - passed listings
	* @param int $pass_stack - passed stack
	* @param string $item - item key
	*
	* @return item key
	**/
	function populate(&$listings, $pass_stack = false, $item = false)
	{
		global $config, $rlListingTypes, $pages, $rlValid;

		// clear item name cache
		if ($item != $_SESSION['ln_item']) {
			unset($_SESSION['ln_item']);
		}

		if (empty($listings)) {
			return false;
		}

		$stack = (int)$_GET['pg'] ? (int)$_GET['pg'] : 1;
		$work_stack = $pass_stack ? $pass_stack : $stack;

		/* clear stack array */
		$_SESSION[$item]['stacks'][$work_stack] = array();

		/* add listings to the array */
		foreach ($listings as &$listing) {
			$_SESSION[$item]['stacks'][$work_stack][] = $listing;
		}
	}

	/**
	* detects does the listing in given item stacks
	*
	* @param string $item - item type to search in 
	* @param int $id - listing ID
	*
	* @return bool
	**/
	function hasInStack($item = false, $id = false)
	{
		$has = false;
		foreach ($_SESSION[$item]['stacks'] as $stack) {
			foreach ($stack as &$array) {
				if ($array['ID'] == $id) {
					$has = true;
					break;
				}
			}
		}

		return $has;
	}

	/**
	* Search results on listing type page
	*
	* @access Hook: searchMiddle
	**/
	function listingTypeSearch($pass_listings = false, $pass_stack = false)
	{
		global $listings, $rlListingTypes, $listing_type_key;

		if ($_REQUEST['action'] == 'search' || $_SESSION[$this->lts]['data']['listing_type_key'] != $listing_type_key) {
			unset($_SESSION[$this->lts]['stacks']);
		}

		$work_listings = $pass_listings ? $pass_listings : $listings;
		$this->populate($work_listings, $pass_stack, $this->lts);
	}

	/**
	* Keyword searh results page
	*
	* @access Hook: searchBottom
	**/
	function keywordSearch($pass_listings = false, $pass_stack = false)
	{
		global $listings;

		if ($_POST['form'] == 'keyword_search') {
			unset($_SESSION[$this->kws]['stacks']);
		}

		$work_listings = $pass_listings ? $pass_listings : $listings;
		$this->populate($work_listings, $pass_stack, $this->kws);
	}

	/**
	* browse categories page
	*
	* @access Hook: browseMiddle
	**/
	function browseCategory($pass_listings = false, $pass_stack = false)
	{
		global $listings, $page_info, $category, $listing_type, $sort_by, $sort_type;

		// clear cache
		if ($page_info['Controller'] == 'listing_type' && $page_info['Key'] != 'view_details' && (
			(isset($_SESSION[$this->bc]['last_category']) && $_SESSION[$this->bc]['last_category'] != $category['ID'])
			|| (isset($_SESSION[$this->bc]['last_listing_type']) && $_SESSION[$this->bc]['last_listing_type'] != $listing_type['Key'])
			|| (isset($_SESSION[$this->bc]['last_sort_by']) && $_SESSION[$this->bc]['last_sort_by'] != $sort_by)
			|| (isset($_SESSION[$this->bc]['last_sort_type']) && $_SESSION[$this->bc]['last_sort_type'] != $sort_type)
			|| (isset($_SESSION[$this->bc]['last_request_uri']) && $_SESSION[$this->bc]['last_request_uri'] != $_SERVER['REQUEST_URI'])
		)) {
			unset($_SESSION[$this->bc]['stacks']);
		}

		// save indicators
		$_SESSION[$this->bc]['last_category'] = $category['ID'];
		$_SESSION[$this->bc]['last_listing_type'] = $listing_type['Key'];
		$_SESSION[$this->bc]['last_sort_by'] = $sort_by;
		$_SESSION[$this->bc]['last_sort_type'] = $sort_type;
		$_SESSION[$this->bc]['last_request_uri'] = $_SERVER['REQUEST_URI'];

		$work_listings = $pass_listings ? $pass_listings : $listings;
		$this->populate($work_listings, $pass_stack, $this->bc);
	}

	/**
	* recently added listings page
	*
	* @access Hook: listingsBottom
	**/
	function recentlyAdded($pass_listings = false, $pass_stack = false)
	{
		global $listings;

		$work_listings = $pass_listings ? $pass_listings : $listings;
		$this->populate($work_listings, $pass_stack, $this->ra);
	}

	/**
	* recently added listings page
	*
	* @access Hook: ajaxRecentlyAddedLoadPost
	**/
	function recentlyAddedCache()
	{
		global $page_info, $requested_key;

		// clear cache
		if ($page_info['Controller'] == 'recently_added' && isset($_SESSION[$this->ra]['last_type']) && $_SESSION[$this->ra]['last_type'] != $requested_key) {
			unset($_SESSION[$this->ra]['stacks']);
		}

		// save indicators
		$_SESSION[$this->ra]['last_type'] = $requested_key;
	}

	/**
	* account details page
	*
	* @access Hook: accountTypeAccount
	**/
	function accountListings($pass_listings = false, $pass_stack = false)
	{
		global $listings, $page_info;

		if ($page_info['prev'] != $page_info['Key']) {
			unset($_SESSION[$this->al]['stacks']);
		}

		$work_listings = $pass_listings ? $pass_listings : $listings;
		$this->populate($work_listings, $pass_stack, $this->al);
	}

    /**
     * Display inline styles
     *
     * @since 2.2.0
     * @hook tplHeader
     */
    public function hookTplHeader()
    {
        if ($GLOBALS['page_info']['Controller'] == 'listing_details') {
            echo <<< HTML
            <style>
            .ln-item {
                flex: 1;
            }
            .ln-item a {
                font-size: 0.929em;
                opacity: .7;
            }
            .ln-item a:hover {
                opacity: 1;
            }
            body:not([dir=rtl]) .ln-item-prev .ln-item-icon,
            body[dir=rtl] .ln-item-next .ln-item-icon {
                transform: scaleX(-1);
            }
            .ln-item-icon {
                width: 6px;
                height: 10px;
            }
            .ln-h1_mixed a {
                padding: 9px 6px;
            }
            .ln-h1_mixed .ln-item-icon {
                width: 10px;
                height: 18px;
            }
            .ln-h1_mixed.ln-hidden {
                display: none;
            }
            </style>
HTML;
        }
    }

    /**
     * Include svg icon
     *
     * @since 2.2.0
     * @hook tplBodyTop
     */
    public function hookTplBodyTop()
    {
        $file = $GLOBALS['tpl_settings']['listing_details_nav_mode'] == 'h1_mixed' ? 'horizontal-arrow-tight.svg' : 'horizontal-arrow.svg';
        $GLOBALS['rlSmarty']->display('../img/svg/' . $file);
    }

    /**
     * Display basic navigation view
     *
     * @since 2.2.0
     * @hook listingDetailsTopTpl
     */
    public function hookListingDetailsTopTpl()
    {
        // Listing Preview plugin may call this hook also, the following condition prevents useless nav appearing
        if ($GLOBALS['page_info']['Controller'] != 'listing_details') {
            return;
        }

        if (!$this->hasNewHooks && $GLOBALS['config']['ln_display_basic']) {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'listing_navigator' . RL_DS . 'navigation.tpl');
        }
    }

    /**
     * Generate return link
     *
     * @since 2.2.0
     * @hook listingDetailsTop
     */
    public function hookListingDetailsTop()
    {
        global $ln_keyword_search_data, $ln_keyword_search_post, $listing_type, $reefless, 
               $config, $advanced_search_url, $search_results_url, $page_info, $listing_id, $listing_data;

        // Listing Preview plugin may call this hook also, the following condition prevents useless code run
        if ($page_info['Key'] != 'view_details') {
            return;
        }

        // Get navigation data
        $this->get($listing_id, $listing_data);

        // Re-assign session data related to search results
        if ($_SESSION['keyword_search_data']) {
            $pg = $_SESSION['keyword_search_pageNum'];

            if ($pg > 1) {
                $path = ['pg' => $config['mod_rewrite'] ? 'index' . $pg : $pg];
            }

            $return_link = $reefless->getPageUrl('search', $path) . '#keyword_tab';
        } elseif ($_SESSION[$listing_type['Key'] .'_post']) {
            $path = [];

            if ($category_id = (int) $_SESSION[$this->bc]['data']['category_id']) {
                $category = Category::getCategory($category_id);
                $path[] = $category['Path'];
            }

            if ($_SESSION[$listing_type['Key'] .'_advanced']) {
                $path[] = $advanced_search_url;
            }

            $path[] = $search_results_url;

            $pg = $_SESSION[$listing_type['Key'] .'_pageNum'];

            if ($pg > 1) {
                $path['pg'] = $config['mod_rewrite'] ? 'index' . $pg : $pg;
            }

            $return_link = $reefless->getPageUrl($listing_type['Page_key'], $path);
        }

        if ($return_link) {
            $GLOBALS['rlSmarty']->assign_by_ref('lnp_return_link', $return_link);
        }

        $ln_keyword_search_data = $_SESSION['keyword_search_data'];
        $ln_keyword_search_post = $_SESSION[$listing_type['Key'] .'_post'];
    }

    /**
     * Reassign post data
     *
     * @since 2.2.0
     * @hook listingDetailsBottom
     */
    public function hookListingDetailsBottom()
    {
        global $ln_keyword_search_data, $ln_keyword_search_post, $listing_type;

        $_SESSION['keyword_search_data'] = $ln_keyword_search_data;
        $_SESSION[$listing_type['Key'] .'_post'] = $ln_keyword_search_post;
    }

    /**
     * Collect data
     *
     * @since 2.2.0
     * @hook searchMiddle
     */
    public function hookSearchMiddle()
    {
        global $page_info, $data, $listing_type_key, $rlSearch;

        $_SESSION['page_info']['current'] = $page_info['Key'] . '_search';

        $_SESSION[$this->lts]['data'] = array(
            'data' => $data,
            'listing_type_key' => $listing_type_key,
            'fields' => $rlSearch->fields
        );

        $this->listingTypeSearch();
    }

    /**
     * Collect data
     *
     * @since 2.2.0
     * @hook searchBottom
     */
    public function hookSearchBottom()
    {
        $this->keywordSearch();
    }

    /**
     * Collect data
     *
     * @since 2.2.0
     * @hook keywordSearchData
     */
    public function hookKeywordSearchData()
    {
        global $data, $sorting;

        $_SESSION[$this->kws]['data'] = array(
            'data' => $data,
            'sorting' => $sorting,
        );
    }

    /**
     * Collect data
     *
     * @since 2.2.0
     * @hook browseMiddle
     */
    public function hookBrowseMiddle()
    {
        global $listing_type, $category, $order_field, $sort_type, $sorting;

        $_SESSION[$this->bc]['data'] = array(
            'listing_type' => $listing_type['Key'],
            'category_id' => $category['ID'],
            'order_field' => $order_field,
            'sort_type' => $sort_type,
            'sorting' => $sorting
        );

        $this->browseCategory();
    }

    /**
     * Collect data
     *
     * @since 2.2.0
     * @hook listingsBottom
     */
    public function hookListingsBottom()
    {
        $this->recentlyAdded();
    }

    /**
     * Collect data
     *
     * @since 2.2.0
     * @hook accountTypeAccount
     */
    public function hookAccountTypeAccount()
    {
        global $account, $sort_by, $sort_type, $sorting;

        $_SESSION[$this->at]['data'] = array(
            'account_id' => $account['ID'],
            'sort_by' => $sort_by,
            'sort_type' => $sort_type,
            'sorting' => $sorting
        );
        $this->accountListings();
    }

    /**
     * Collect data
     *
     * @since 2.2.0
     * @hook ajaxRecentlyAddedLoadPost
     */
    public function hookAjaxRecentlyAddedLoadPost()
    {
        $this->recentlyAddedCache();
    }

    /**
     * Display navigation in new hook
     *
     * @since 2.2.0
     * @hook tplListingDetailsNavLeft
     */
    public function hookTplListingDetailsNavLeft()
    {
        // Listing Preview plugin may call this hook also, the following condition prevents useless nav appearing
        if ($GLOBALS['page_info']['Controller'] != 'listing_details') {
            return;
        }

        if (!$GLOBALS['config']['ln_display_basic']) {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'listing_navigator' . RL_DS . 'nav_left.tpl');
        }
    }

    /**
     * Display navigation in new hook
     *
     * @since 2.2.0
     * @hook tplListingDetailsNavRight
     */
    public function hookTplListingDetailsNavRight()
    {
        // Listing Preview plugin may call this hook also, the following condition prevents useless nav appearing
        if ($GLOBALS['page_info']['Controller'] != 'listing_details') {
            return;
        }

        if (!$GLOBALS['config']['ln_display_basic']) {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'listing_navigator' . RL_DS . 'nav_right.tpl');
        }
    }

    /**
     * Display navigation in new hook
     *
     * @since 2.2.0
     * @hook tplAbovePageContent
     */
    public function hookTplAbovePageContent()
    {
        // Listing Preview plugin may call this hook also, the following condition prevents useless nav appearing
        if ($GLOBALS['page_info']['Controller'] != 'listing_details') {
            return;
        }

        if ($GLOBALS['config']['ln_display_basic'] && $this->hasNewHooks) {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'listing_navigator' . RL_DS . 'navigation.tpl');
        }
    }

    /**
     * Include configs js handlers
     *
     * @since 2.2.0
     * @hook apTplContentBottom
     */
    public function hookApTplContentBottom()
    {
        if ($GLOBALS['cInfo']['Key'] == 'config') {
            echo <<< html
            <script>
            var hasNewHooks = '{$this->hasNewHooks}';
            var \$wrapper = \$('input[name="post_config[ln_display_basic][value]"][type=hidden]').parent();

            if (hasNewHooks) {
                \$wrapper.find('.settings_desc').hide();
            } else {
                \$('#ln_display_basic_1').click();
                \$wrapper.find('input[type=radio]').attr('disabled', true);
            }
            </script>
html;
        }
    }

    /**
     * Define $dbcount to avoid SQL_CALC_FOUND_ROWS in getListings() method
     *
     * @since 2.2.0
     * @hook listingsModifyPreSelect
     */
    public function hookListingsModifyPreSelect(&$dbcount)
    {
        if (!defined('RL_LN_DIRECT_LISTING')) {
            return;
        }

        $dbcount = 10;
    }

    /**
     * Update to 2.2.0
     */
    public function update220()
    {
        global $languages, $rlDb;

        // Remove hook
        $hooks = [
            'listingDetailsBottom',
            'init',
        ];
        $GLOBALS['rlDb']->query("
            DELETE FROM `{db_prefix}hooks`
            WHERE `Plugin` = 'listing_navigator' AND `Name` IN ('" . implode("','", $hooks) . "')
        ");

        // Remove static directory
        $GLOBALS['reefless']->deleteDirectory(RL_PLUGINS . 'listing_navigator/static/');

        // Enable basic view mode for version without new hooks
        if (version_compare($GLOBALS['config']['rl_version'], '4.9.0', '<=')) {
            $update = [
                'fields' => ['Default' => 1],
                'where' => ['Key' => 'ln_display_basic'],
            ];
            $GLOBALS['rlDb']->updateOne($update, 'config');
        }

        // Translate phrases
        if (array_key_exists('ru', $languages)) {
            $russianTranslation = json_decode(file_get_contents(RL_UPLOAD . 'listing_navigator/i18n/ru.json'), true);

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
}
