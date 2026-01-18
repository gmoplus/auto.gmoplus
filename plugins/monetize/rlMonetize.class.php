<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLMONETIZE.CLASS.PHP
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

require_once RL_PLUGINS . 'monetize/bootstrap.php';

class rlMonetize
{
    /**
     * @var string - Path to the front-end view folder
     */
    private $view;

    /**
     * @var string - Path to the admin view folder
     */
    private $a_view;

    /**
     * @var \rlBumpUp - Bump Up class instance
     */
    private $bumpUp;

    /**
     * @var \rlHighlight - Highlight class instance
     */
    private $highlight;

    /**
     * rlMonetize constructor.
     */
    public function __construct()
    {
        if (!defined('RL_DATE_FORMAT') && $GLOBALS['rlLang'] && $GLOBALS['languages']) {
            $GLOBALS['rlLang']->modifyLanguagesList($GLOBALS['languages']);
        }

        $m_time_format = $GLOBALS['config']['is_european_time_format'] ? ' %H:%M:%S' : ' %I:%M %p';
        define('BUMPUP_TIME_FORMAT', RL_DATE_FORMAT . $m_time_format);

        $this->view = RL_PLUGINS . 'monetize' . RL_DS . 'view' . RL_DS;
        $this->a_view = RL_PLUGINS . 'monetize' . RL_DS . 'admin' . RL_DS . 'view' . RL_DS;
        if ($GLOBALS['rlSmarty']) {
            $config['view'] = $this->view;
            $config['a_view'] = $this->a_view;
            $config['a_path'] = RL_PLUGINS . 'monetize' . RL_DS . 'admin' . RL_DS;
            $GLOBALS['rlSmarty']->assign('mConfig', $config);
        }

        //Load BumpUp and High
        $GLOBALS['reefless']->loadClass('BumpUp', null, 'monetize');
        $GLOBALS['reefless']->loadClass('Highlight', null, 'monetize');
        $this->bumpUp = $GLOBALS['rlBumpUp'];
        $this->highlight = $GLOBALS['rlHighlight'];

    }

    /**
     * Getting allowed pages controllers
     *
     * @since 1.3.0
     *
     * @return array - Listing of allowed pages controllers where lib.js and style.css will be included
     */
    public function getAllowPagesControllers($pageType = 'listings')
    {
        $allowedListingsPages = array(
            'listing_type',
            'recently_added',
            'search',
            'account_type',
        );

        $allowedPluginPages = array(
            'my_listings',
            'bumpup_page',
            'profile',
            'highlight_page',
        );

        $pages = 'allowed' . ucfirst($pageType) . 'Pages';
        $allowedPages = $$pages;

        $GLOBALS['rlHook']->load('phpMonetizeAssignAllowedPages', $allowedPages, $pageType);

        return $allowedPages;
    }

    /**
     * @hook myListingsIcon
     */
    public function hookMyListingsIcon()
    {
        $listing = $GLOBALS['rlSmarty']->_tpl_vars['listing'];
        if (!$listing || $listing['Status'] !== 'active') {
            return false;
        }

        $back_to = $GLOBALS['reefless']->getPageUrl($GLOBALS['page_info']['Key']);
        $_SESSION['m_back_to'] = $back_to;
        $GLOBALS['rlSmarty']->display($this->view . 'monetize_icons.tpl');
    }

    /**
     * @hook apAjaxRequest
     */
    public function hookApAjaxRequest()
    {
        $item = $_REQUEST['item'];
        if (!$this->isCorrectApAjaxRequest($item)) {
            return false;
        }

        switch ($item) {
            case 'deleteBumpUpPlan':
                $GLOBALS['out'] = $this->deletePlan((int) $_REQUEST['id'], 'bumpup');
                break;
            case 'deleteHighlightPlan':
                $GLOBALS['out'] = $this->deletePlan((int) $_REQUEST['id'], 'highlight');
                break;
            case 'monetize_getMonetizePlanUsingInfo':
                $username = $GLOBALS['rlValid']->xSql($_REQUEST['username']);
                $creditInfo = $this->getCreditsInfoByUser($username);

                $GLOBALS['out'] = array(
                    'status' => 'OK',
                    'credits_info' => $creditInfo,
                );
                break;
            case 'monetize_ajaxAssignCredits':
                $GLOBALS['reefless']->loadClass('Actions');
                $username = $GLOBALS['rlValid']->xSql($_POST['username']);
                $userID = $GLOBALS['rlDb']->getOne('ID', "`Username` = '{$username}'", 'accounts');
                $status = 'ERROR';
                if ($userID) {
                    if (isset($_POST['bumpup_plan'])) {
                        $bumpupPlan = (int) $_POST['bumpup_plan'];
                        $bumpupCredits = (int) $_POST['bumpup_credits'];
                        $this->addCustomPlanUsing($bumpupPlan, $bumpupCredits, $userID, 'bumpup');
                    }

                    if (isset($_POST['highlight_plan'])) {
                        $highlightPlan = (int) $_POST['highlight_plan'];
                        $highlightCredits = (int) $_POST['highlight_credits'];
                        $this->addCustomPlanUsing($highlightPlan, $highlightCredits, $userID, 'highlight');
                    }
                    $status = 'OK';
                }

                $GLOBALS['out'] = array('status' => $status);
                break;
            case 'monetize_getHighlightCredits':
                $username = $GLOBALS['rlValid']->xSql($_POST['username']);
                $userID = (int) $GLOBALS['rlDb']->getOne('ID', "`Username` = '{$username}'", 'accounts');
                $planID = (int) $_POST['plan'];

                $out = array(
                    'status' => 'ERROR',
                );

                if ($userID && $planID) {
                    $credits = $this->getCredits($userID, $planID, 'highlight');
                    $out = array(
                        'status' => 'OK',
                        'credits' => $credits,
                    );
                }

                $GLOBALS['out'] = $out;
                break;
            case 'monetize_getBumpUpCredits':
                $username = $GLOBALS['rlValid']->xSql($_POST['username']);
                $userID = (int) $GLOBALS['rlDb']->getOne('ID', "`Username` = '{$username}'", 'accounts');
                $planID = (int) $_POST['plan'];

                $out = array(
                    'status' => 'ERROR',
                );

                if ($userID && $planID) {
                    $credits = $this->getCredits($userID, $planID, 'bumpup');
                    $out = array(
                        'status' => 'OK',
                        'credits' => $credits,
                    );
                }

                $GLOBALS['out'] = $out;
                break;
            case 'monetize_checkMonetizePlanUsage':
                $planID = (int) $_POST['plan_id'];
                $users = $this->getPlanUsingUsers($planID);
                $GLOBALS['out'] = array(
                    'status' => 'OK',
                    'users' => $users,
                );
                break;
            case 'monetize_getPlans':
                $type = $GLOBALS['rlValid']->xSql($_POST['type']);
                $exclude = (int) $_POST['exclude'];

                $out = array(
                    'status' => 'ERROR',
                );

                if ($type == 'highlight') {
                    $plans = $this->getPlans(0, 0, null, 'highlight');
                    $pos = array_search($exclude, array_column($plans, 'ID'));
                    if (is_int($pos)) {
                        unset($plans[$pos]);
                    }
                    $plans = array_values(array_filter($plans));

                    $out = array(
                        'status' => 'OK',
                        'plans' => $plans,
                    );
                }

                $GLOBALS['out'] = $out;
                break;
            case 'monetize_reassignPlan':
                $from = (int) $_POST['from'];
                $to = (int) $_POST['to'];
                $out = array(
                    'status' => 'ERROR',
                );

                if ($this->reassignMonetizePlanToAnother($from, $to)) {
                    $out['status'] = 'OK';
                }

                $GLOBALS['out'] = $out;
                break;
        }
    }

    /**
     * Getting credits info by user
     *
     * @since 1.3.0
     * @param string $username
     *
     * @return array
     */
    public function getCreditsInfoByUser($username)
    {
        if (!$username) {
            return array();
        }

        $accountID = $GLOBALS['rlDb']->getOne('ID', "`Username` = '{$username}'", 'accounts');

        $highlightPlans = $this->getPlans(0, 0, null, 'highlight');
        foreach ($highlightPlans as $key => $plan) {
            if (!(int) $plan['Highlights']) {
                unset($highlightPlans[$key]);
                continue;
            }

            $highlightPlans[$key]['credits'] = $this->getCredits($accountID, $plan['ID'], 'highlight');
        }
        $highlightPlans = array_values(array_filter($highlightPlans));

        $bumpupPlans = $this->getPlans(0, 0, null, 'bump_up');
        foreach ($bumpupPlans as $key => $plan) {
            if (!(int) $plan['Bump_ups']) {
                unset($bumpupPlans[$key]);
                continue;
            }
            $bumpupPlans[$key]['credits'] = $this->getCredits($accountID, $plan['ID'], 'bumpup');
        }
        $bumpupPlans = array_values(array_filter($bumpupPlans));

        return array(
            'bump_up' => array(
                'total_credits' => $this->getCredits($accountID, false, 'bumpup'),
                'plans' => count($bumpupPlans) > 0 ? $bumpupPlans : [],
            ),
            'highlight' => array(
                'total_credits' => $this->getCredits($accountID, false, 'highlight'),
                'plans' => count($highlightPlans) > 0 ? $highlightPlans : [],
            ),
        );
    }

    /**
     * Does incoming ajax request item is related to the monetize plugin
     *
     * @since 1.3.0
     * @param  string $item - Request item
     * @return bool
     */
    public function isCorrectApAjaxRequest($item)
    {
        $availableRequests = array(
            'deleteBumpUpPlan',
            'deleteHighlightPlan',
            'monetize_getMonetizePlanUsingInfo',
            'monetize_ajaxAssignCredits',
            'monetize_getHighlightCredits',
            'monetize_getBumpUpCredits',
            'monetize_checkMonetizePlanUsage',
            'monetize_getPlans',
            'monetize_reassignPlan',
        );

        return in_array($item, $availableRequests);
    }

    /**
     * @hook staticDataRegister
     */
    public function hookStaticDataRegister()
    {
        global $rlStatic;

        $chartUrl = '';

        $rlStatic->addHeaderCSS(RL_PLUGINS_URL . 'monetize/static/style.css', $this->getAllowPagesControllers('plugin'));
        $rlStatic->addJS(RL_PLUGINS_URL . 'monetize/static/lib.js', $this->getAllowPagesControllers('plugin'));

        $templateRoot = RL_ROOT . 'templates/' . $GLOBALS['config']['template'] . '/';
        $chartLocationIn = array(
            'css' => 'css/plans-chart.css',
            'components' => 'components/plans-chart/plans-chart.css',
        );

        if (file_exists($templateRoot . $chartLocationIn['components'])) {
            $chartUrl = RL_TPL_BASE . $chartLocationIn['components'];
        }

        if ($chartUrl) {
            $rlStatic->addHeaderCSS($chartUrl, array('bumpup_page', 'highlight_page'));
        }
    }

    /**
     * Display highlight inline styles in header
     *
     * @since 2.1.0
     * @hook tplHeader
     */
    public function hookTplHeader()
    {
        global $blocks, $page_info;

        $in_box = false;

        foreach ($blocks as $block) {
            if ($block['Plugin'] == 'listings_box' || false !== strpos($block['Key'], 'ltfb_')) {
                $in_box = true;
                break;
            }
        }

        if (!in_array($page_info['Controller'], $this->getAllowPagesControllers()) && !$in_box) {
            return;
        }

        echo <<< HTML
        <style>
        article.highlight a.link-large, article.highlight ul.ad-info > li.title > a {
            background: #ffec6c;
            box-shadow: 5px 0 0 #ffec6c, -5px 0 0 #ffec6c;
            color: #555555 !important;
        }
        .highlight .title a {
            background: #ffec6c;
            box-shadow: 5px 0 0 #ffec6c, -5px 0 0 #ffec6c;
            color: #555555 !important;
        }
        </style>
HTML;
    }

    /**
     * @deprecated 2.1.0
     */
    public function hookTplFooter() {}

    /**
     * @hook ListingAfterFields
     */
    public function hookListingAfterFields()
    {
        $GLOBALS['rlSmarty']->display($this->view . 'highlight_hook.tpl');
    }

    /**
     * Display listing statistics on the My Profile page
     *
     * @since 2.0.1
     * @hook listingAfterStats
     */
    public function hookListingAfterStats()
    {
        if ($GLOBALS['page_info']['Controller'] == 'profile') {
            $GLOBALS['rlSmarty']->display($this->view . 'bumped_up_date.tpl');
        }
    }

    /**
     * @hook apTplListingPlansForm
     */
    public function hookApTplListingPlansForm()
    {
        $GLOBALS['rlSmarty']->display($this->a_view . 'listings_plan_form.tpl');
    }

    /**
     * @hook apPhpListingPlansBeforeAdd
     */
    public function hookApPhpListingPlansBeforeAdd()
    {
        global $data;

        $data['Bumpup_ID'] = (int) $_POST['bumpup_id'];
        $data['Highlight_ID'] = (int) $_POST['highlight_id'];
    }

    /**
     * @hook apPhpListingPlansBeforeEdit
     */
    public function hookApPhpListingPlansBeforeEdit()
    {
        global $update_date;

        $update_date['fields']['Bumpup_ID'] = (int) $_POST['bumpup_id'];
        $update_date['fields']['Highlight_ID'] = (int) $_POST['highlight_id'];
    }

    /**
     * @hook apPhpListingPlansPost
     */
    public function hookApPhpListingPlansPost()
    {
        global $plan_info;

        $_POST['bumpup_id'] = $plan_info['Bumpup_ID'];
        $_POST['highlight_id'] = $plan_info['Highlight_ID'];
    }

    /**
     * @hook  afterListingDone
     *
     * @since 1.3.0 $addListing, $updateData, $isFree added
     *
     * @param  \Flynax\Classes\AddListing $addListing
     * @param  array                      $updateData
     * @param  bool                       $isFree
     *
     * @return bool
     */
    public function hookAfterListingDone($addListing, &$updateData, $isFree)
    {
        $listingData = !is_null($addListing) ? $addListing->listingData : $GLOBALS['listing_data'];
        $planInfo = !is_null($addListing) && $addListing->plans[$listingData['Plan_ID']]
        ? $addListing->plans[$listingData['Plan_ID']]
        : $GLOBALS['plan_info'];
        $isFree = !is_null($isFree) ? $isFree : $planInfo['Price'] <= 0;

        if ($isFree) {
            $this->addMonetizeUsingDependingOnPlan($listingData['Plan_ID']);
        }

        return true;
    }

    /**
     * @hook  phpListingsUpgradeListing
     * @since 1.2.0
     *
     * @param array $plan_info - Information regarding plan
     * @param int   $plan_id
     * @param int   $listing_id
     */
    public function hookPhpListingsUpgradeListing($plan_info = array(), $plan_id = 0, $listing_id = 0)
    {
        return false;

        global $rlPayment, $account_info;
        $GLOBALS['reefless']->loadClass('Account');

        if (!$plan_id) {
            $plan_id = $GLOBALS['plan_id'] ?: $rlPayment->getOption('plan_id');
        }

        $account_info = $account_info ?: $GLOBALS['rlAccount']->getProfile((int) $rlPayment->getOption('account_id'));
        $this->addMonetizeUsingDependingOnPlan($plan_id);
    }

    /**
     * Adding Highlight/BumpUp credits to the user depending on the plan.
     *
     * @param  int $plan_id
     * @return bool
     */
    public function addMonetizeUsingDependingOnPlan($plan_id = 0)
    {
        if (!$plan_id) {
            return false;
        }

        $sql = "SELECT * FROM `{db_prefix}listing_plans` WHERE `ID` = {$plan_id}";
        $plan = $GLOBALS['rlDb']->getRow($sql);

        // add highlights
        if ($plan['Highlight_ID']) {
            $highlightPlan = $this->getPlanInfo($plan['Highlight_ID'], 'highlight');
            $this->highlight->addPlanUsing($highlightPlan, $GLOBALS['account_info']['ID']);
        }

        // add bumpups
        if ($plan['Bumpup_ID']) {
            $bumpupPlan = $this->getPlanInfo($plan['Bumpup_ID'], 'bump_up');
            $this->bumpUp->addPlanUsing($bumpupPlan, $GLOBALS['account_info']['ID']);
        }
    }

    /**
     * @hook phpListingsUpgradePlanInfo
     */
    public function hookPhpListingsUpgradePlanInfo()
    {
        if ($GLOBALS['plan_info']['Price'] === 0) {
            $this->addMonetizeUsingDependingOnPlan($GLOBALS['plan_info']['ID']);
        }
    }

    /**
     * Monetize block
     */
    public function blockMonetizeListingDetail()
    {
        global $rlSmarty;

        $listing_id = $GLOBALS['rlValid']->xSql($_GET['id']);
        if ($listing_id) {
            $listing_data = $GLOBALS['rlListings']->getListing($listing_id, true, true);
            $listing_data['url'] = $listing_data['listing_link'];
            $rlSmarty->assign('listings', [$listing_data]);
            $rlSmarty->display($this->view . 'monetize_block.tpl');
        }
    }

    /**
     * @hook apTplBlocksBottom
     */
    public function hookApTplFooter()
    {
        if ($_GET['controller'] == 'blocks' && $_GET['block'] == 'monetize_listing_detail') {
            echo "<script type='text/javascript'>$(\"#pages_obj\").parent().hide();</script>";
        }

        if ($_GET['controller'] == 'listing_plans' || $_GET['controller'] == 'monetize') {
            $adminStyle = "<link href='" . RL_PLUGINS_URL . "monetize/static/admin_style.css' ";
            $adminStyle .= "type='text/css' rel='stylesheet' />";
            echo $adminStyle;

            echo "<script src='" . RL_PLUGINS_URL . "monetize/static/lib.js'></script>";
        }
    }

    /**
     * @since 1.3.0
     */
    public function hookApTplHeader()
    {
        if ($_GET['controller'] == 'monetize') {
            echo sprintf("<script type='text/javascript' src='%smonetize/static/lib.js'></script>", RL_PLUGINS_URL);
        }
    }

    /**
     * @hook profileController
     */
    public function hookProfileController()
    {

        if (!$this->cantAddListing($GLOBALS['account_info']['Type'])) {
            return false;
        }

        // load BumpUp Tab
        $this->bumpUp->prepareTab();

        // load Highlight Tab
        $this->highlight->prepareTab();
    }

    /**
     * @hook profileBlock
     */
    public function hookProfileBlock()
    {
        $GLOBALS['rlSmarty']->display($this->view . 'bump_up_tab.tpl');
        $GLOBALS['rlSmarty']->display($this->view . 'highlight_tab.tpl');
        $GLOBALS['rlSmarty']->display($this->view . 'js-code.tpl');
    }

    /**
     * Return first listing type with listings.
     *
     * @return string|bool $my_key - Listing type key or false if user doesn't added any listing yet
     */
    public function getNotEmptyListingType()
    {
        if ($GLOBALS['config']['one_my_listings_page']) {
            return 'my_all_ads';
        }

        $listing_types = $GLOBALS['rlListingTypes']->types;
        $first_type = current($GLOBALS['rlListingTypes']->types);
        $my_key = $first_type['My_key'];
        $GLOBALS['modify_where'] = false;
        $GLOBALS['modify_highlight_where'] = false;

        foreach ($listing_types as $listing_type) {
            $listing_exist = $GLOBALS['rlListings']->getMyListings($listing_type['Key'], 'ID', 'asc', 0, 10);
            if (!empty($listing_exist)) {
                $my_key = $listing_type['My_key'];
                break;
            }
        }

        $GLOBALS['modify_where'] = true;
        $GLOBALS['modify_highlight_where'] = true;

        return $my_key;
    }

    /**
     * Remove monetize 'plan using' row
     *
     * @param int $id - ID of the row
     *
     * @return bool $result - true if row was removed successfully, false if not.
     */
    public function removePlanUsingRow($id)
    {
        $sql = "DELETE FROM `{db_prefix}monetize_using` WHERE `ID` = {$id}";
        $result = $GLOBALS['rlDb']->query($sql);

        return $result;
    }

    /**
     * @hook smartyFetchHook
     */
    public function hookSmartyFetchHook(&$compiled_content, &$resource_name): void
    {
        /*
         * This method using adding new class via HTML in software < 4.8.0 versions.
         * In a new versions "highlight" class added via hook "tplListingItemClass".
         * @todo - Remote this hook when compatibility will be >= 4.8.0
         */
        if (version_compare($GLOBALS['config']['rl_version'], '4.8.0') >= 0) {
            return;
        }

        global $page_info;

        $allowedPages = $this->getAllowPagesControllers();

        if ($page_info && $allowedPages && in_array($page_info['Controller'], $allowedPages, true)) {
            $file_name = basename($resource_name);

            if ($file_name === 'content.tpl') {
                $html = Pharse::str_get_dom($compiled_content);

                foreach ($html('#listings article.item') as $listing) {
                    if (count($listing('i.highlight'))) {
                        $classList = explode(' ', $listing->getAttribute('class'));
                        $classList[] = 'highlight';
                        $listing->setAttribute('class', implode(' ', $classList));
                    }
                }

                $compiled_content = (string) $html;
            }
        }
    }

    /**
     * @hook myListingsSqlWhere.
     * @param string $sql - SQL query of the getMyListings method.
     */
    public function hookMyListingsSqlWhere(&$sql, $type)
    {
        if ($type == 'monetizeAll') {
            $find = "AND `T4`.`Type` = 'monetizeAll'";
            $sql = str_replace($find, '', $sql);
        }

        if ($GLOBALS['modify_where']) {
            $sql .= "AND `T1`.`Bumped` = '1' ";
        }

        if ($GLOBALS['modify_highlight_where']) {
            $sql .= "AND `T1`.`Date` != `T1`.`HighlightDate` AND `T1`.`HighlightDate` != '0000-00-00 00:00:00' ";
        }
    }

    /**
     * @deprecated 2.0.1
     */
    public function hookListingNavIcons() {}

    /**
     * @hook TplListingPlanService
     */
    public function hookTplListingPlanService()
    {
        $GLOBALS['rlSmarty']->display($this->view . 'listing_plan.tpl');
    }

    /**
     * @hook  tplMyPackagesPlanService
     * @since 1.3.0
     */
    public function hookTplMyPackagesPlanService()
    {
        $GLOBALS['rlSmarty']->display($this->view . 'listing_plan.tpl');
    }

    /**
     * @hook  tplMyPackageItemListingInfo
     *
     * @since 1.4.0
     */
    public function hookTplMyPackageItemListingInfo()
    {
        $GLOBALS['rlSmarty']->display($this->view . 'my_package_item_listing_info.tpl');
    }

    /**
     * @hook  phpMyPackagesTop
     *
     * @since 1.4.0
     */
    public function hookPhpMyPackagesTop()
    {
        foreach ($GLOBALS['packages'] as &$package) {
            $planID = (int) $package['Plan_ID'];
            $bumpupPlanInfo = $package['Bumpup_ID'] ? $this->getPlanInfo($package['Bumpup_ID'], 'bump_up') : ['Bump_ups' => 0];
            $highlightPlanInfo = $package['Highlight_ID']
            ? $this->getPlanInfo($package['Highlight_ID'], 'highlight')
            : ['Highlights' => 0, 'Days' => 0];

            $package += array(
                'Bumpups' => (int) $bumpupPlanInfo['Bump_ups'],
                'Highlight' => (int) $highlightPlanInfo['Highlights'],
                'Days_highlight' => (int) $highlightPlanInfo['Days'],
            );
        }
    }

    /**
     * @hook apPhpListingsMassActions
     *
     * @since 1.4.0
     *
     * @param $ids
     * @param $action
     */
    public function hookApPhpListingsMassActions($ids, $action)
    {
        if ($action !== 'renew') {
            return;
        }

        $ids = explode('|', $ids);

        $sql = "UPDATE `{db_prefix}listings` SET `Date` = NOW(), `Bumped` = '1' ";
        $sql .= "WHERE FIND_IN_SET(`ID`, '" . implode(',', $ids) . "')";
        $GLOBALS['rlDb']->query($sql);
    }

    /**
     * @hook PhpGetPlanByCategoryModifyField
     * @param string $sql - SQL query of the getPlan method
     * @param        $id  - ID of the plan
     */
    public function hookPhpGetPlanByCategoryModifyField(&$sql, $id)
    {
        $sql .= ' `TMH`.`Days` AS `Days_highlight`, `TMB`.`Bump_ups` AS `Bumpups`, `TMH`.`Highlights` AS `Highlight`, ';
    }

    /**
     * @hook ListingsModifyField
     */
    public function hookListingsModifyField()
    {
        $GLOBALS['sql'] .= "IF(`T1`.`Date` != `T1`.`HighlightDate` ";
        $GLOBALS['sql'] .= "AND `T1`.`HighlightDate` != '0000-00-00 00:00:00', '1', '0') as 'is_highlighted', ";
    }

    /**
     * @hook  myListingsSqlFields
     *
     * @since 1.1.0
     */
    public function hookMyListingsSqlFields(&$sql)
    {
        $sql .= ", IF(`T1`.`Date` != `T1`.`HighlightDate` ";
        $sql .= "AND `T1`.`HighlightDate` != '0000-00-00 00:00:00', '1', '0') as 'is_highlighted' ";
    }

    /**
     * @hook  listingsModifyFieldSearch
     *
     * @since 1.1.0
     */
    public function hookListingsModifyFieldSearch(&$sql)
    {
        $sql .= "IF(`T1`.`Date` != `T1`.`HighlightDate` ";
        $sql .= "AND `T1`.`HighlightDate` != '0000-00-00 00:00:00', '1', '0') as 'is_highlighted', ";
    }

    /**
     * @hook  myListingsafterStatFields
     *
     * @since 1.1.0
     */
    public function hookMyListingsafterStatFields()
    {
        $GLOBALS['rlSmarty']->display($this->view . 'highlight_hook.tpl');
    }

    /**
     * @hook listingsModifyFieldByPeriod
     * @param $sql - SQL string of the getRecentlyAdded method
     */
    public function hookListingsModifyFieldByPeriod(&$sql)
    {
        $sql .= "IF(`T1`.`Date` != `T1`.`HighlightDate` ";
        $sql .= "AND `T1`.`HighlightDate` != '0000-00-00 00:00:00', '1', '0') as 'is_highlighted', ";
    }

    /**
     * @since 1.3.0
     *
     * @hook  listingsModifyFieldByAccount
     */
    public function hookListingsModifyFieldByAccount()
    {
        $GLOBALS['sql'] .= "IF(`T1`.`Date` != `T1`.`HighlightDate` ";
        $GLOBALS['sql'] .= "AND `T1`.`HighlightDate` != '0000-00-00 00:00:00', '1', '0') as 'is_highlighted', ";
    }

    /**
     * @hook  cronAdditional
     *
     * @since 1.1.0
     */
    public function hookCronAdditional()
    {
        global $rlDb;

        $sql = "SELECT `ID` FROM `{db_prefix}listings` ";
        $sql .= "WHERE `HighlightDate` != '0000-00-00 00:00:00'  && `HighlightDate` < NOW()";
        $listings = $rlDb->getAll($sql);

        if (!empty($listings)) {
            $ids = array_map(
                function ($element) {
                    return $element['ID'];
                }, $listings
            );
            $ids = implode(',', $ids);

            $sql = "UPDATE  `{db_prefix}listings` SET `HighlightDate` = '0000-00-00 00:00:00', ";
            $sql .= "`Highlight_Plan` = 0 WHERE `ID` IN ({$ids})";

            $rlDb->query($sql);
        }
    }

    /**
     * Working only for users who upgrade only free packages.
     *
     * @hook  phpMyPackagesRenewValidate
     * @since 1.1.0
     */
    public function hookPhpMyPackagesRenewValidate()
    {
        global $pack_info;

        if ($pack_info['Price'] <= 0) {
            $this->addMonetizeUsingDependingOnPlan($pack_info['Plan_ID']);
        }

    }

    /**
     * @hook  postPaymentComplete
     *
     * @since 1.1.0
     * @param array $data - Payment options
     */
    public function hookPostPaymentComplete($data)
    {
        $txn_id = (int) $data['txn_id'];

        if (!$txn_id || !$data['plan_id']) {
            return;
        }

        $sql = "SELECT * FROM `{db_prefix}transactions` WHERE `ID` = {$txn_id}";
        $txnInfo = $GLOBALS['rlDb']->getRow($sql);

        if (in_array($txnInfo['Service'], array('listing', 'package'))) {
            if (!$GLOBALS['rlAccount']) {
                $GLOBALS['reefless']->loadClass('Account');
            }

            $accountID = (int) $data['account_id'];
            $GLOBALS['account_info'] = $GLOBALS['account_info'] ?: $GLOBALS['rlAccount']->getProfile($accountID);
            $this->addMonetizeUsingDependingOnPlan($data['plan_id']);
        }
    }

    /**
     * @hook  phpMyPackagesRenewPreAction
     *
     * @since 1.1.0
     */
    public function hookPhpMyPackagesRenewPreAction()
    {
        $GLOBALS['rlPayment']->setOption('params', 'monetize');
    }

    /**
     * @hook  apPhpListingsAfterAdd
     * @since 1.2.1
     */
    public function hookApPhpListingsAfterAdd()
    {
        global $plan_info, $listing_id;

        if ($plan_info['Bumpup_ID'] || $plan_info['Highlight_ID']) {
            $this->addMonetizeUsingDependingOnPlan($plan_info['ID']);
        }
    }

    /**
     * @hook  apPhpPlansUsingAfterGrant
     * @since 1.2.1
     */
    public function hookApPhpPlansUsingAfterGrant()
    {
        $GLOBALS['account_info'] = $GLOBALS['account_data'];
        $packageInfo = array();

        foreach ($GLOBALS['plans'] as $package) {
            if ($package['ID'] == $GLOBALS['package_id']) {
                $packageInfo = $package;
                break;
            }
        }

        if ($packageInfo['Bumpup_ID'] || $packageInfo['Highlight_ID']) {
            $this->addMonetizeUsingDependingOnPlan($packageInfo['ID']);
        }
    }

    /**
     * @hook  apExtTransactionsService
     * @since 1.3.0
     *
     * @param array $paymentServicesMultilang - Multilanguage services
     */
    public function hookApExtTransactionsService(&$paymentServicesMultilang)
    {
        $paymentServicesMultilang = array_filter($paymentServicesMultilang, function ($item) {
            return ($item !== 'bump_up');
        });
    }

    /**
     * @hook  couponAttachCouponBox
     * @since 1.3.0
     *
     * @param string $service - Coupon code service name
     * @param int    $item_id - Payed service ID
     */
    public function hookCouponAttachCouponBox(&$service, &$item_id)
    {
        global $page_info, $plans;

        if (!in_array($page_info['Controller'], array('bumpup_page', 'highlight_page')) || !$plans) {
            return;
        }

        $item_id = $plans[0]['ID'];
        $service = str_replace('_page', '', $page_info['Controller']);
    }

    /**
     * Can user with provided listing type adding listings
     *
     * @param  string $account_type - Account type key
     * @return bool                 - Is user is available to add listings
     */
    public function cantAddListing($account_type)
    {
        $row = $GLOBALS['rlDb']->getOne('Abilities', "`Key` = '{$account_type}'", 'account_types');
        $abilities = explode(',', $row);

        // if export import plugin is active
        $plugins = $GLOBALS['plugins'] ?: $GLOBALS['aHooks'];
        if (key_exists('export_import', $plugins) && ($key = array_search('export_import', $abilities))) {
            unset($abilities[$key]);
        }

        $abilities = array_filter($abilities);
        if (empty($abilities)) {
            return false;
        }

        return true;
    }

    /**
     * Assign custom number of credits to account
     *
     * @since 1.3.0
     *
     * @param        $planID
     * @param        $value
     * @param        $accountID
     * @param string $type
     *
     * @return bool
     */
    public function addCustomPlanUsing($planID, $value, $accountID, $type = 'highlight')
    {
        $value = (int) $value;

        if (!$planID || $value < 0 || !$accountID || !in_array($type, array('highlight', 'bumpup'))) {
            return false;
        }

        $class = $type == 'highlight' ? 'highlight' : 'bumpUp';
        $planUsing = $this->getPlanUsing($planID, $accountID);
        $planInfo = $this->getPlanInfo($planID, $type);

        if (!$planUsing && $value > 0) {
            $this->{$class}->addPlanUsing($planInfo, $accountID);
            $planUsing = $this->getPlanUsing($planID, $accountID);
        }

        $planUsingRowID = $planUsing['ID'];

        if ($value == 0 && $planUsingRowID) {
            return true;
        }

        $updateField = $type == 'highlight' ? 'Highlights_available' : 'Bumpups_available';

        $fields = array(
            $updateField => (int) $planUsing[$updateField] + $value,
        );

        $update = array(
            'fields' => $fields,
            'where' => array(
                'ID' => $planUsingRowID,
            ),
        );

        $GLOBALS['rlDb']->updateOne($update, 'monetize_using');
    }

    /**
     * @since 1.3.0
     * @hook  ajaxRecentlyAddedLoadPost
     */
    public function hookAjaxRecentlyAddedLoadPost()
    {
        $GLOBALS['_response']->script('monetizer.highlightListings();');
    }

    /**
     * Getting users which are using provided monetize plan
     *
     * @since  1.3.0
     *
     * @param int $planID - Monetize plan ID
     *
     * @return array
     */
    public function getPlanUsingUsers($planID)
    {
        $planID = (int) $planID;

        if (!$planID) {
            return array();
        }

        $sql = "SELECT `Account_ID` FROM `{db_prefix}monetize_using` WHERE `Plan_ID` = {$planID} ";

        return $GLOBALS['rlDb']->getAll($sql);
    }

    /**
     * Reassign highlight monetize plan to another
     *
     * @since  1.3.0
     *
     * @param int $from - Monetize plan id which you want to reassign
     * @param int $to   - Monetize plan id on which you want to assign
     *
     * @return bool
     */
    public function reassignMonetizePlanToAnother($from, $to)
    {
        $assignToPlanInfo = $this->highlight->getPlanInfo($to);
        $update = array(
            'fields' => array(
                'Plan_ID' => $to,
                'Days_highlight' => $assignToPlanInfo['Days'],
            ),
            'where' => array(
                'Plan_ID' => $from,
            ),
        );

        return (bool) $GLOBALS['rlDb']->update($update, 'monetize_using');
    }

    /**
     * @since 1.4.0
     */
    public function hookTplListingItemClass()
    {
        $listing = $GLOBALS['rlSmarty']->_tpl_vars['listing'];
        if ($this->shouldHighlight($listing)) {
            echo rlHighlight::HIGLIGHT_HTML_CLASS . ' ';
        }
    }

    /**
     * @since 2.1.0
     * @hook featuredItemTop
     */
    public function hookFeaturedItemTop()
    {
        $listing = $GLOBALS['rlSmarty']->_tpl_vars['featured_listing'];
        $class = &$GLOBALS['rlSmarty']->_tpl_vars['box_item_class'];
        if ($this->shouldHighlight($listing)) {
            $class .= ' ' . rlHighlight::HIGLIGHT_HTML_CLASS;
        }
    }

    /**
     * @since 1.4.0
     */
    public function hookTplMyListingItemClass()
    {
        $listing = $GLOBALS['rlSmarty']->_tpl_vars['listing'];
        if ($this->shouldHighlight($listing)) {
            echo rlHighlight::HIGLIGHT_HTML_CLASS . ' ';
        }
    }

    /**
     * Check does listing should be highlighted by provided listing information
     *
     * @since 1.4.0
     *
     * @param array $listingInfo
     * @return bool
     * @throws \Exception
     */
    public function shouldHighlight($listingInfo)
    {
        if ($listingInfo['HighlightDate'] == '0000-00-00 00:00:00' || !$listingInfo) {
            return false;
        }

        try {
            $highlightData = new DateTime($listingInfo['HighlightDate']);
            $now = new DateTime();

            return $highlightData > $now;
        } catch (\Exception $e) {
            // todo: Think about debugging of this place. Should I write something to log, or just skip it as it is.
            return false;
        }
    }

    /**
     * @hook apPhpListingPlansTop
     *
     * @since 2.0.0
     */
    public function hookApPhpListingPlansTop()
    {
        global $rlSmarty;

        $bumpupPlans = $this->getPlans(false, false, 'active', 'bump_up');
        $highlightPlans = $this->getPlans(false, false, 'active', 'highlight');

        $rlSmarty->assign('bumpupPlans', $bumpupPlans);
        $rlSmarty->assign('highlightPlans', $highlightPlans);
    }

    /**
     * Add plan
     *
     * @since 2.0.0
     *
     * @param array $data
     * @param array $plan_name
     * @param array $description
     * @return bool
     */
    public function addPlan($data = [], $plan_name = [], $description = [])
    {
        global $allLangs, $rlDb, $rlLang;

        if (!$data || !$plan_name) {
            return false;
        }

        $type = $data['Type'];
        $data['Type'] = str_replace('_', '', $type);

        if ($rlDb->insertOne($data, 'monetize_plans')) {
            $insertID = $rlDb->insertID();
            $planKey = $data['Type'] . '_' . $insertID;

            $update_data = array(
                'fields' => array(
                    'Key' => $planKey,
                ),
                'where' => array(
                    'ID' => $insertID,
                ),
            );
            $rlDb->updateOne($update_data, 'monetize_plans');

            $createPhrases = [];
            foreach ($allLangs as $key => $lang) {
                $createPhrases[] = array(
                    'Code'   => $allLangs[$key]['Code'],
                    'Module' => 'common',
                    'Status' => 'active',
                    'Key'    => $type . '_plan+name+' . $planKey,
                    'Value'  => $plan_name[$allLangs[$key]['Code']],
                    'Plugin' => 'monetize',
                );

                $createPhrases[] = array(
                    'Code'   => $allLangs[$key]['Code'],
                    'Module' => 'common',
                    'Status' => 'active',
                    'Key'    => $type . '_plan+description+' . $planKey,
                    'Value'  => $description[$allLangs[$key]['Code']],
                    'Plugin' => 'monetize',
                );
            }

            if (method_exists($rlLang, 'createPhrases')) {
                $rlLang->createPhrases($createPhrases);
            } else {
                $rlDb->insert($createPhrases, 'lang_keys');
            }

            $result = true;
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * Edit plan
     *
     * @since 2.0.0
     *
     * @param int $planID
     * @param array $data
     * @param array $plan_name
     * @param array $description
     * @return bool
     */
    public function editPlan($planID = 0, $data = [], $plan_name = [], $description = [])
    {
        global $allLangs, $rlDb, $rlLang;

        if (!$planID || !$data || !$plan_name) {
            return false;
        }

        $type = $data['Type'];
        $data['Type'] = str_replace('_', '', $type);

        $update = array(
            'fields' => $data,
            'where' => array(
                'ID' => $planID,
            ),
        );

        $result = false;

        if ($rlDb->updateOne($update, 'monetize_plans')) {
            $langKey       = $type . '_plan';
            $planKey       = $data['Type'] . '_' . $planID;
            $createPhrases = [];
            $updatePhrases = [];

            foreach ($allLangs as $key => $value) {
                if ($rlDb->getOne('ID', "`Key` = '{$langKey}+name+{$planKey}' AND `Code` = '{$value['Code']}'", 'lang_keys')) {
                    $updatePhrases[] = array(
                        'fields' => array(
                            'Value' => $plan_name[$value['Code']],
                        ),
                        'where'  => array(
                            'Code' => $value['Code'],
                            'Key'  => $langKey . '+name+' . $planKey,
                        ),
                    );
                } else {
                    $createPhrases[] = array(
                        'Code'   => $value['Code'],
                        'Module' => 'common',
                        'Key'    => $langKey . '+name+' . $planKey,
                        'Value'  => $plan_name[$value['Code']],
                        'Plugin' => 'monetize',
                    );
                }

                if ($rlDb->getOne('ID', "`Key` = '{$langKey}+description+{$planKey}' AND `Code` = '{$value['Code']}'", 'lang_keys')) {
                    $updatePhrases[] = array(
                        'where'  => array(
                            'Code' => $value['Code'],
                            'Key'  => $langKey . '+description+' . $planKey,
                        ),
                        'fields' => array(
                            'Value' => $description[$value['Code']],
                        ),
                    );
                } else {
                    $createPhrases[] = array(
                        'Code'   => $value['Code'],
                        'Module' => 'common',
                        'Status' => 'active',
                        'Key'    => $langKey . '+description+' . $planKey,
                        'Value'  => $description[$value['Code']],
                        'Plugin' => 'monetize',
                    );
                }
            }

            if ($createPhrases) {
                if (method_exists($rlLang, 'createPhrases')) {
                    $rlLang->createPhrases($createPhrases);
                } else {
                    $rlDb->insert($createPhrases, 'lang_keys');
                }
            }

            if ($updatePhrases) {
                if (method_exists($rlLang, 'updatePhrases')) {
                    $rlLang->updatePhrases($updatePhrases);
                } else {
                    $rlDb->update($updatePhrases, 'lang_keys');
                }
            }

            $result = true;
        }

        return $result;
    }

    /**
     * Return all highlight plans by limit or all
     *
     * @since 2.0.0
     *
     * @param  int    $start  - Start from
     * @param  int    $limit  - Limit plans
     * @param  string $status - Status of the plans
     * @param  string $type -   Type of the plans
     * @param  bool   $mapField
     * @return array  $data   - An array of the highlight plans
     */
    public function getPlans($start = 0, $limit = 0, $status = '', $type = '', $mapField = false)
    {
        global $rlLang, $lang, $account_info;

        $dbType = str_replace('_', '', $type);

        $sql = "SELECT `T1`.*, `T2`.`ID` AS `Using_ID`, `T2`.`Is_unlim`, `T2`.`Bumpups_available`,  `T2`.`Highlights_available` ";
        $sql .= "FROM `{db_prefix}monetize_plans` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}monetize_using` AS `T2` ON `T1`.`ID` = `T2`.`Plan_ID` ";
        $sql .= "AND `T2`.`Account_ID` = '{$account_info['ID']}' AND `T2`.`Plan_type` = '{$dbType}' ";
        $sql .= "WHERE `T1`.`Type` = '{$dbType}' ";
        $sql .= $status ? "AND `T1`.`Status` = '{$status}' " : '';
        $sql .= $limit ? "LIMIT {$start}, {$limit}" : '';
        $data = $GLOBALS['rlDb']->getAll($sql, $mapField);

        foreach ($data as $key => $item) {
            $data[$key] = $rlLang->replaceLangKeys($item, $type . '_plan', array('name', 'description'), RL_LANG_CODE);
            $data[$key]['Status'] = $lang[$data[$key]['Status']];
        }

        return $data;
    }

    /**
     * Delete plan
     *
     * @since 2.0.0
     *
     * @param int $plan_id
     * @param string $type
     * @return array
     */
    public function deletePlan($plan_id = 0, $type = '')
    {
        global $lang, $rlDb, $rlLang;

        if (!$plan_id || !$type || !in_array($type, ['bumpup', 'highlight'])) {
            $out['status'] = 'error';
            $out['message'] = $lang['system_error'];
            return $out;
        }

        $sql = "SELECT * FROM `{db_prefix}monetize_plans` WHERE `ID` = {$plan_id}";
        $plan_info = $rlDb->getRow($sql);

        if (!$plan_info) {
            $out['status'] = 'error';
            $out['message'] = $lang[$type === 'bumpup' ? 'bump_up_delete_error' : 'm_highlight_plan_remove_error'];
            return $out;
        }

        // delete plan
        $sql = "DELETE FROM `{db_prefix}monetize_plans` WHERE  `ID` = {$plan_id}";
        $rlDb->query($sql);

        // delete lang keys
        if (method_exists($rlLang, 'deletePhrases')) {
            $result = $rlLang->deletePhrases([
                ['Key' => "{$type}_plan+name+{$plan_info['Key']}"],
                ['Key' => "{$type}_plan+description+{$plan_info['Key']}"]
            ]);
        } else {
            $sql = "DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = '{$type}_plan+name+{$plan_info['Key']}'";
            $sql .= "OR `Key` = '{$type}_plan+description+{$plan_info['Key']}'";
            $result = $rlDb->query($sql);
        }

        if ($result) {
            $out['status'] = 'ok';
            $out['message'] = $lang[$type === 'bumpup' ? 'bump_up_deleted' : 'm_highlight_plan_removed'];

            $rlDb->query("DELETE FROM `{db_prefix}monetize_using` WHERE  `Plan_ID` = {$plan_id}");
        } else {
            $out['status'] = 'error';
            $out['message'] = $lang[$type === 'bumpup' ? 'bump_up_delete_error' : 'm_highlight_plan_remove_error'];
        }

        return $out;
    }

    /**
     * Get plan info
     *
     * @since 2.0.0
     *
     * @param  int $planID
     * @param  string $type
     * @return array
     */
    public function getPlanInfo($planID = 0, $type = '')
    {
        if (!$planID) {
            return [];
        }

        $type = $type == 'highlight' ? 'highlight' : 'bump_up';
        $sql = "SELECT * FROM `{db_prefix}monetize_plans` WHERE `ID` = {$planID}";
        $planInfo = $GLOBALS['rlDb']->getRow($sql);
        if ($planInfo) {
            $planInfo = $GLOBALS['rlLang']->replaceLangKeys($planInfo, $type . '_plan', array('name', 'description'), RL_LANG_CODE);
        }

        return $planInfo;
    }

    /**
     * Getting plan usin row by plan_id and account
     *
     * @since 2.0.0
     *
     * @param int $plan_id   - Monetize plan ID
     * @param int $accountID
     *
     * @return bool|mixed  - $plan_using
     */
    public function getPlanUsing($plan_id = 0, $accountID = 0)
    {
        if (!$plan_id) {
            return false;
        }

        $account_id = $accountID ?: $GLOBALS['account_info']['ID'];
        $sql = "SELECT * FROM `{db_prefix}monetize_using` ";
        $sql .= "WHERE `Account_ID` = {$account_id} AND `Plan_ID` = {$plan_id}";
        $plan_using = $GLOBALS['rlDb']->getRow($sql);
        $result = $plan_using ?: false;

        return $result;
    }

    /**
     * Return available credits by specified account.
     *
     * @since 2.0.0
     *
     * @param  int  $account_id - ID of needed account
     * @param  int  $planID     - Highlight plan ID
     * @param  string  $type
     *
     * @return int  $row        - Highlight credits.
     */
    public function getCredits($account_id, $planID = 0, $type = '')
    {
        $account_id = (int) $account_id;
        $planID = (int) $planID;

        if (!$account_id) {
            return false;
        }

        $sumField = $type == 'highlight' ? 'Highlights_available' : 'Bumpups_available';
        $sql = "SELECT SUM(`{$sumField}`) AS `sum` FROM `{db_prefix}monetize_using` ";
        $sql .= "WHERE `Account_ID` = {$account_id} ";

        if ($planID) {
            $sql .= "AND `Plan_ID` = {$planID} ";
        }

        $row = $GLOBALS['rlDb']->getRow($sql);

        return $row['sum'] ?: 0;
    }

    /**
     * Modify main bread crumbs, add My Listings page as a parent page.
     *
     * @since 2.0.0
     */
    public function breadCrumbs()
    {
        global $bread_crumbs, $lang, $listing_info;

        $listingType = $GLOBALS['rlListingTypes']->types[$listing_info['Listing_type']];
        $my_page_key = $GLOBALS['config']['one_my_listings_page'] ? 'my_all_ads' : $listingType['My_key'];

        if (!$my_page_key) {
            return;
        }

        $last = array_pop($bread_crumbs);
        $bread_crumbs[] = array(
            'name'  => $lang['pages+name+' . $my_page_key],
            'title' => $lang['pages+title+' . $my_page_key],
            'path'  => $GLOBALS['pages'][$my_page_key],
        );
        $bread_crumbs[] = $last;
    }

    /**
     * @hook myListingsPreSelect
     *
     * @since 2.0.0
     */
    public function hookMyListingsPreSelect()
    {
        global $rlSmarty;

        $bumpupPlans = $this->getPlans(false, false, 'active', 'bump_up');
        $highlightPlans = $this->getPlans(false, false, 'active', 'highlight');

        $rlSmarty->assign('bumpupPlans', $bumpupPlans);
        $rlSmarty->assign('highlightPlans', $highlightPlans);
    }

    /**
     * @hook phpGetPlanByCategoryModifyJoin
     */
    public function hookPhpGetPlanByCategoryModifyJoin(&$sql, $id)
    {
        $sql .= "LEFT JOIN `{db_prefix}monetize_plans` AS `TMH` ON `T1`.`Highlight_ID` = `TMH`.`ID` ";
        $sql .= "AND `TMH`.`Type` = 'highlight' AND `TMH`.`Status` = 'active' ";
        $sql .= "LEFT JOIN `{db_prefix}monetize_plans` AS `TMB` ON `T1`.`Bumpup_ID` = `TMB`.`ID` ";
        $sql .= "AND `TMB`.`Type` = 'bumpup' AND `TMB`.`Status` = 'active' ";
    }

    /**
     * @deprecated 2.0.0
     *
     * @hook apPhpListingPlansValidate
     */
    public function hookApPhpListingPlansValidate()
    {}

    /**
     * @deprecated 2.0.0
     *
     * Plugin install function
     */
    public function install()
    {}

    /**
     * @deprecated 2.0.0
     *
     * Plugin uninstall function
     */
    public function uninstall()
    {}

    /**
     * @deprecated 2.0.0
     *
     * @since 1.1.0
     */
    public function update_110()
    {}

    /**
     * @deprecated 2.0.0
     *
     * @since 1.4.1
     */
    public function update141()
    {}

    /**
     * @deprecated 2.0.0
     *
     * Update to 1.3.0
     */
    public function update130()
    {}

    /**
     * @deprecated 2.0.0
     *
     * Rebuild plans depending on bought unlim plan ID. All remain not bought plans should be removed from the list.
     *
     * @param  array  $plans    - Bump up plans
     * @param  int    $unlim_id - Bought unlim plan ID
     * @param  string $type     - Type of the Monetize Plan
     * @return array            - Modified plans
     */
    public function rebuildPlans($plans, $unlim_id, $type = 'bumpups')
    {}

    /**
     * @deprecated 2.0.0
     *
     * IncludeFilesInPages array getter
     *
     * @since 1.3.0
     *
     * @return array
     */
    public function getIncludeFilesInPages()
    {}

    /**
     * @deprecated 2.0.0
     *
     * IncludeFiltersInPages property setter
     *
     * @since 1.3.0
     *
     * @param array $includeFilesInPages
     */
    public function setIncludeFilesInPages($includeFilesInPages)
    {}

    /**
     * @deprecated 2.0.0
     *
     * Add new page controller to the list
     *
     * @since 1.3.0
     *
     * @param string $controller - Page controller, which you want to add to the IncludingFilesInPages
     */
    public function addIncludePagesController($controller)
    {}

    /**
     * @deprecated 2.0.0
     *
     * Remove page controller from the controllers list
     *
     * @since 1.3.0
     *
     * @param string $controller - Page controller, which you want to remove from the IncludingFilesInPages
     */
    public function removePageController($controller)
    {}

    /**
     * @deprecated 2.0.0
     *
     * @hook addListingBeforeSteps
     */
    public function hookAddListingBeforeSteps()
    {}

    /**
     * @deprecated 2.0.0
     *
     * Getting bump ups count from Listings plan/packages
     *
     * @param  int $plan_id - ID of the listing plan/package
     * @return string           - Bump ups count of the listing package
     */
    public function getBumpUpFromPlan($plan_id)
    {}
}
