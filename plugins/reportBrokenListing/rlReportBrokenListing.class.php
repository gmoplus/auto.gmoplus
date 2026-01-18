<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLREPORTBROKENLISTING.CLASS.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2024
 *	https://www.flynax.com
 *
 ******************************************************************************/

use ReportListings\FlynaxObjectsContainer;
use ReportListings\Report;
use ReportListings\ReportPoints;
use ReportListings\Helpers\AjaxWrapper;

require_once 'bootstrap.php';

class rlReportBrokenListing
{
    /**
     * @var - Report Broken Listing configurations
     */
    protected $configs;
    
    /**
     * @var rlDb
     */
    protected $rlDb;
    
    /**
     * @var rlLang
     */
    protected $rlLang;
    
    /**
     * @var rlSmarty
     */
    protected $rlSmarty;
    
    /**
     * @var mixed
     */
    protected $flLangs;
    
    /**
     * rlReportBrokenListing constructor.
     */
    public function __construct()
    {
        if (!$this->isAjaxValid()) {
            return;
        }
        
        $GLOBALS['reefless']->loadClass('Actions');
        $GLOBALS['reefless']->loadClass('Listings');
        $GLOBALS['reefless']->loadClass('Lang');
        
        /* collect neccessary objects */
        FlynaxObjectsContainer::addObject('reefless', $GLOBALS['reefless']);
        FlynaxObjectsContainer::addObject('rlDb', $GLOBALS['rlDb']);
        FlynaxObjectsContainer::addObject('rlValid', $GLOBALS['rlValid']);
        FlynaxObjectsContainer::addObject('rlActions', $GLOBALS['rlActions']);
        FlynaxObjectsContainer::addObject('rlListings', $GLOBALS['rlListings']);
    
        if (!$GLOBALS['rlAccount']) {
            $GLOBALS['reefless']->loadClass('Account');
        }
        FlynaxObjectsContainer::addObject('rlAccount', $GLOBALS['rlAccount']);
        FlynaxObjectsContainer::addObject('rlLang', $GLOBALS['rlLang']);
        $this->rlLang = $GLOBALS['rlLang'];
        
        FlynaxObjectsContainer::addObject('rlReportBrokenListing', $this);
        $this->rlDb = $GLOBALS['rlDb'];
        
        FlynaxObjectsContainer::setConfig('flConfigs', $GLOBALS['config']);
        $this->flLangs = $GLOBALS['lang'];
        
        FlynaxObjectsContainer::setConfig('flLang', $this->flLangs);
        
        /* build configurations */
        $configs['a_pages'] = RL_PLUGINS . 'reportBrokenListing' . RL_DS . 'admin' . RL_DS . 'pages' . RL_DS;
        
        $configs['report_table'] = RL_DBPREFIX . 'report_broken_listing';
        $configs['report_points_table'] = RL_DBPREFIX . 'report_broken_listing_points';
        
        $path['view']['front'] = RL_ROOT . 'plugins/reportBrokenListing/view/';
        $path['view']['admin'] = RL_ROOT . 'plugins/reportBrokenListing/admin/view/';
        $configs['path'] = $path;
        
        $url['static']['front'] = RL_URL_HOME . 'plugins/reportBrokenListing/static/';
        $url['static']['admin'] = RL_URL_HOME . 'plugins/reportBrokenListing/static/';
        $configs['url'] = $url;
        
        if ($GLOBALS['rlSmarty']) {
            $this->rlSmarty = $GLOBALS['rlSmarty'];
            $GLOBALS['rlSmarty']->assign('rblConfigs', $configs);
        }
        
        $this->configs = $configs;
    
        // admin specified objects
        if (defined('REALM')) {
            if (!$GLOBALS['rlNotice']) {
                $GLOBALS['reefless']->loadClass('Notice');
            }
            FlynaxObjectsContainer::addObject('rlNotice', $GLOBALS['rlNotice']);
        }
    }
    
    /**
     * @param $name
     * @return mixed
     */
    public function getConfig($name)
    {
        return $this->configs[$name];
    }
    
    /**
     * @param mixed $configs
     */
    public function setConfigs($configs)
    {
        $this->configs = $configs;
    }
    
    /**
     * Plugin installation method
     * @since 3.0.0
     */
    public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . RL_DBPREFIX . "report_broken_listing` (
            `ID` int(5) NOT NULL AUTO_INCREMENT,
            `Listing_ID` int(8) NOT NULL,
            `Account_ID` int(8) NOT NULL,
            `Report_key` varchar(50) NOT NULL DEFAULT 'custom',
            `Date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `Message` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
            `Status` enum('active','approval','trash') NOT NULL default 'active',
            `IP` varchar(15),
            PRIMARY KEY (`ID`),
            KEY `Listing_ID` (`Listing_ID`)
        ) DEFAULT CHARSET=utf8";
        $this->rlDb->query($sql);
        
        $this->addReportPointsTable();
    }
    
    /**
     * Adding report_broken_listing_points table
     * @since 3.0.0
     */
    public function addReportPointsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . RL_DBPREFIX . "report_broken_listing_points` (
          `ID` int(2) NOT NULL AUTO_INCREMENT,
          `Key` varchar(50) DEFAULT NULL,
          `Reports_to_critical` int(2) DEFAULT NULL,
          `Position` int(2) DEFAULT NULL,
          `Status` enum('active','approval','critical','trash') NOT NULL default 'active',
          PRIMARY KEY (`ID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $this->rlDb->query($sql);

        $sql = "
            INSERT INTO `" . RL_DBPREFIX . "report_broken_listing_points`
            (`ID`, `Key`, `Reports_to_critical`, `Position`, `Status`) VALUES
            (1, 'report_broken_point_1517465827', 5, 1, 'active'),
            (2, 'report_broken_point_1517465865', 10, 2, 'active'),
            (3, 'report_broken_point_1517465877', 10, 3, 'active'),
            (4, 'report_broken_point_1517465899', 15, 4, 'active'),
            (5, 'report_broken_point_1517465911', 20, 5, 'active'),
            (6, 'report_broken_point_1517465925', 15, 6, 'active');
        ";
        $this->rlDb->query($sql);
    }
    
    /**
     * @hook apTplFooter
     * @since 3.0.0
     */
    public function hookApTplFooter()
    {
        if (request('controller') == 'reportBrokenListing') {
            $static_url = RL_PLUGINS_URL . 'reportBrokenListing/static/';
            $js = "<script src='{$static_url}admin_lib.js'></script>";
        
            echo $js;
        }
    }
    
    /**
     * @hook listingDetailsAfterStats
     * @since 3.0.0
     */
    public function hookListingDetailsAfterStats()
    {
        $this->rlSmarty->display($this->configs['path']['view']['front'] . 'details_icon.tpl');
    
        define('RBL_FOOTER', true);
        // TODO: Check what is RBL_FOOTER
    }
    
    /**
     * @hook staticDataRegister
     * @since 3.0.0
     */
    public function hookStaticDataRegister()
    {
        global $rlStatic;
    
        $in_pages = array('view_details');
        $rlStatic->addJS($this->configs['url']['static']['front'] . 'lib.js', $in_pages, true);
    }
    
    /**
     * @hook tplFooter
     * @since 3.0.0
     */
    public function hookTplFooter()
    {
        if ($GLOBALS['page_info']['Controller'] == 'listing_details') {
            $this->rlSmarty->display($this->configs['path']['view']['front'] . 'footer.tpl');
        }
    }
    
    /**
     * Display inline styles
     *
     * @since 3.2.0
     * @hook tplHeader
     */
    public function hookTplHeader()
    {
        if ($GLOBALS['page_info']['Controller'] == 'listing_details') {
            $this->rlSmarty->display($this->configs['path']['view']['front'] . 'header.tpl');
        }
    }

    /**
     * @hook apAjaxRequest
     * @since 3.0.0
     */
    public function hookApAjaxRequest(&$out, $item)
    {
        if (null === $out) {
            $out = &$GLOBALS['out'];
        }

        $item = $item !== null ? $item : $_REQUEST['item'];
        $item = $GLOBALS['rlValid']->xSql($item);
    
        if (!$this->isAjaxValid()) {
            return;
        }

        $reportPoint = new ReportPoints(RL_LANG_CODE);
        $ajaxWrapper = new AjaxWrapper();

        switch ($item) {
            case 'RBLAddReportItem':
                $reports = $_POST['formData'];
                if ($reportPoint->onSubmit($reports)) {
                    $out = $ajaxWrapper->throwSuccessMessage($this->rlLang->getSystem('item_added'));
                }
                break;
            case 'RBLDeleteCompletely':
                
                $key = request('key');
    
                $result = !$_REQUEST['point']
                    ? $reportPoint->removeWithAssociatedReports($key)
                    : $reportPoint->removeWithAssigningToAnotherLabel($key, $_REQUEST['point']);
                if ($result) {
                    $out = $ajaxWrapper->throwSuccessBody(array('removed_listings' => $result));
                }
                break;
            case 'RBLGetPointsByKey':
                $key = request('key');
                if ($reportPoints = $reportPoint->getPointByKey($key)) {
                    $out = $ajaxWrapper->throwSuccessBody($reportPoints);
                } else {
                    $out = $ajaxWrapper->throwErrorMessage($this->flLangs['system_error']);
                }
                break;
            case 'RBLEditReportItem':
                $key = request('key');
                $new_data = request('formData');
                if ($reportPoint->edit($new_data, $key)) {
                    $out = $ajaxWrapper->throwSuccessMessage($this->flLangs['item_edited']);
                } else {
                    $out = $ajaxWrapper->throwErrorMessage($this->flLangs['system_error']);
                }
                break;
            case 'RBLDeleteReportPoint':
                $key = request('key');
                $reports_exists = $reportPoint->doesReportsExist($key);
                
                // if reports doesn't exist, just remove point
                if (!$reports_exists) {
                    if ($reportPoint->deleteByKey($key)) {
                        $out = $ajaxWrapper->throwSuccessMessage($this->flLangs['item_deleted']);
                    } else {
                        $out = $ajaxWrapper->throwErrorMessage($this->flLangs['system_error']);
                    }
                    return;
                }
                
                $out = $ajaxWrapper->throwErrorBody(array('reports' => $reports_exists));
                break;
            case 'RBLDeleteReport':
                $reportDetail = new \ReportListings\ReportDetail();
                if ($reportDetail->onReportDelete(request('id'), request('reason'))) {
                    $out = $ajaxWrapper->throwSuccessMessage($this->flLangs['item_deleted']);
                } else {
                    $out = $ajaxWrapper->throwErrorMessage($this->flLangs['system_error']);
                }
                break;
            case 'RBLRemoveAllReportsOfTheListing':
                $out = $ajaxWrapper->throwErrorMessage($this->flLangs['system_error']);
                if($this->removeAllReportsByListing(request('id'))) {
                    $out = $ajaxWrapper->throwSuccessMessage($this->rlLang->getSystem('rbl_reports_removed'));
                }
                break;
            case 'RBLRemoveListing':
                if ($this->removeListing(request('id'))) {
                    FlynaxObjectsContainer::getObject('rlNotice')
                        ->saveNotice($this->flLangs['notice_listing_deleted']);
        
                    $out = $ajaxWrapper->throwSuccessBody(array(
                        'redirectTo' => RL_URL_HOME . ADMIN . "/index.php?controller=reportBrokenListing",
                    ));
                }
                break;
        }
    }
    
    /**
     * Remove all associated reports of the Listing.
     *
     * @since  3.1.0
     *
     * @param  int $listing_id
     * @return bool            - Is removing process is succeed
     */
    public function removeAllReportsByListing($listing_id = 0)
    {
        if (!$listing_id) {
            return false;
        }
        
        $reportObj = new Report();
        $reports = $reportObj->reportFilters->filterBy('Listing_ID', $listing_id)->get();
    
        foreach ($reports as $report) {
            $reportObj->delete($report['ID']);
        }
    
        return true;
    }
    
    /**
     * Remove listing by ID
     *
     * @since 3.1.0
     * @param  int $listingID
     * @return bool            - Is listing removing has been successful
     */
    public function removeListing($listingID = 0)
    {
        if (!$listingID) {
            return false;
        }
        
        $this->removeAllReportsByListing($listingID);
        FlynaxObjectsContainer::getObject('rlListings')->deleteListing((int)$listingID);
        
        return true;
    }
    
    /**
     * @hook  ajaxRequest
     * @since 3.0.0
     *
     * @param array  $out
     * @param string $request_mode
     * @param string $request_item
     * @param string $request_lang
     * @return bool                 - False if validation has been failed
     */
    public function hookAjaxRequest(&$out, $request_mode, $request_item, $request_lang)
    {
        if (!$this->isAjaxValid()) {
            return;
        }
        
        $report = new Report();
        $ajaxWrapper = new AjaxWrapper();
        
        switch ($request_mode) {
            case 'RBLAddReport':
                $ip = FlynaxObjectsContainer::getObject('reefless')->getClientIpAddress();
                $custom_message = $_POST['custom_message'];

                $data['Account'] = $GLOBALS['account_info'] ? $GLOBALS['account_info']['ID'] : 0;
                $data['key'] = request('key');
                $data['Listing_ID'] = request('listing_id');
                $data['IP'] = $ip;
                
                if ($custom_message) {
                    $data['custom_message'] = $custom_message;
                }
    
                if (!$this->validateIP($ip, $data['Listing_ID'])) {
                    $out = $ajaxWrapper->throwErrorMessage($this->rlLang->getSystem('rbl_ip_wrong'));
                    return false;
                }
                
                if ($id = $report->add($data)) {
                    $out = $ajaxWrapper->throwSuccessMessage($this->rlLang->getSystem('rbl_report_added'), $id);
                } else {
                    $out = $ajaxWrapper->throwErrorMessage($this->flLangs['system_error']);
                }
                break;
            case 'RBLRemoveReport':
                $report_id = request('id');
                if ($report->delete($report_id)) {
                    $out = $ajaxWrapper->throwSuccessMessage($this->rlLang->getSystem('rbl_report_removed'));
                } else {
                    $out = $ajaxWrapper->throwErrorMessage($this->flLangs['system_error']);
                }
                break;
            case 'RBLGetAllPoints':
                $pointsClass = new ReportPoints($request_lang);
                $points = $pointsClass->getAllActivePoints();
                $out = $ajaxWrapper->throwSuccessBody($points);
                break;
        }
    }
    
    /**
     * Update to 3.0.0
     */
    public function update300()
    {
        $this->addReportPointsTable();
        
        /* remove unnecessary files */
        $removing_files = array('footer.tpl', 'details_icon.tpl', 'grid_icon.tpl');
        foreach ($removing_files as $file) {
            unlink(RL_PLUGINS . 'reportBrokenListing/' . $file);
        }
    
        /* alter table old table structure to fit new features */
        $sql = "ALTER TABLE `" . RL_DBPREFIX . "report_broken_listing` ";
        $sql .= "ADD `Report_key` VARCHAR(50) NOT NULL";
        $this->rlDb->query($sql);

        $sql = "ALTER TABLE `" . RL_DBPREFIX . "report_broken_listing` ";
        $sql .= "ADD `Status` ENUM('active','approval','trash') NOT NULL DEFAULT 'active'";
        $this->rlDb->query($sql);
    
        $sql = "ALTER TABLE `" . RL_DBPREFIX . "report_broken_listing` ";
        $sql .= "ADD `IP` VARCHAR(15) NOT NULL";
        $this->rlDb->query($sql);
        
        /* remove old hooks */
        $sql = "DELETE FROM `" . RL_DBPREFIX . "hooks` WHERE `Plugin` = 'reportBrokenListing' ";
        $sql .= "AND `Name` IN('listingNavIcons','specialBlock', 'ajaxRecentlyAddedLoadPost', 'tplHeader')";
        $this->rlDb->query($sql);
        
        /* switch all old report to the custom */
        $sql = "UPDATE `" . RL_DBPREFIX . "report_broken_listing` SET `Report_key` = 'custom'";
        $this->rlDb->query($sql);
    }
    
    /**
     * Validate IP of the adding report person
     *
     * @param string $ip         - IP of the client
     * @param int    $listing_id - ID of the validating Listing
     * @return bool              - Is this IP is unique for this listing
     */
    public function validateIP($ip, $listing_id)
    {
        if (!$ip || !$listing_id) {
            return false;
        }

        $where = "`Listing_ID` = {$listing_id} AND `IP` = '{$ip}'";
        $report_exist = $this->rlDb->getOne('ID', $where , 'report_broken_listing');

        if ($report_exist) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check ajaxRequest request
     * @since 3.0.0
     *
     * @return bool
     */
    public function isAjaxValid()
    {
        $available_ajax_requests = array(
            'RBLAddReportItem',
            'RBLGetPointsByKey',
            'RBLEditReportItem',
            'RBLDeleteReportPoint',
            'RBLDeleteReport',
            'RBLAddReport',
            'RBLRemoveReport',
            'RBLGetAllPoints',
            'RBLRemoveAllReportsOfTheListing',
            'RBLDeleteCompletely',
            'RBLRemoveListing',
            'install',
            'update'
        );
    
        if (($_REQUEST['item'] && !in_array($_REQUEST['item'], $available_ajax_requests))
            || ($_REQUEST['mode'] && !in_array($_REQUEST['mode'], $available_ajax_requests))
        ) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Uninstall plugin
     * @since 3.0.0
     */
    public function uninstall()
    {
        $sql = "DROP TABLE IF EXISTS `" . RL_DBPREFIX . "report_broken_listing`";
        $this->rlDb->query($sql);

        $sql = "DROP TABLE IF EXISTS `" . RL_DBPREFIX . "report_broken_listing_points`";
        $this->rlDb->query($sql);
    }

    /**
     * @since 3.1.0
     */
    public function update310()
    {
        $sql = "ALTER TABLE `" . RL_DBPREFIX . "report_broken_listing` CHANGE `Report_key` ";
        $sql .= "`Report_key` VARCHAR(50) NOT NULL DEFAULT 'custom'";
        $this->rlDb->query($sql);

        // delete config row
        $sql = "DELETE FROM `" . RL_DBPREFIX . "config` ";
        $sql .= "WHERE `Key` = 'reportBroken_grid_icon' AND `Plugin` = 'reportBrokenListing'";
        $this->rlDb->query($sql);
    }

    /**
     * @since 3.2.0
     */
    public function update320()
    {
        // Remove unnecessary phrases
        $phrases = array(
            'reportbroken_listing_has_been_added',
            'reportbroken_listing_has_been_removed',
            'ext_reportbroken_delete',
            'ext_reportbroken_delete_listing',
            'ext_reportbroken_message',
            'ext_reportBroken_notice_delete',
            'ext_reportBroken_notice_trash',
            'rbl_listing_detail',
            'rbl_report_broken_points',
            'rbl_point_message',
            'rbl_add_points',
            'rbl_edit',
            'rbl_add',
            'rbl_ajax_error',
            'rbl_points_added',
            'rbl_point_changed',
            'rbl_something_wrong',
            'rbl_fill_all',
            'rbl_label',
            'rbl_remove_bellow',
            'rbl_ip',
            'rbl_listing_has_been_removed',
            'ext_reportBroken_guest',
            'rbl_report_date',
            'config+name+reportBroken_common',
        );

        $this->rlDb->query("
            DELETE FROM `{db_prefix}lang_keys`
            WHERE `Plugin` = 'reportBrokenListing' AND `Key` IN ('" . implode("','", $phrases) . "')"
        );

        // delete config
        $sql = "
            DELETE FROM `{db_prefix}config`
            WHERE `Key` = 'reportBroken_common'
        ";
        $this->rlDb->query($sql);

        // Remove files
        unlink(RL_PLUGINS . 'reportBrokenListing/static/style.css');

        if (in_array('ru', array_keys($GLOBALS['languages']))) {
            $russianTranslation = json_decode(file_get_contents(RL_PLUGINS . 'reportBrokenListing/i18n/ru.json'), true);
            foreach ($russianTranslation as $phraseKey => $phraseValue) {
                if (!$GLOBALS['rlDb']->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $newPhrase = $GLOBALS['rlDb']->fetch(
                        ['Module', 'Key', 'Plugin', 'JS', 'Target_key'],
                        ['Code' => $GLOBALS['config']['lang'], 'Key' => $phraseKey, 'Plugin' => 'reportBrokenListing'],
                        null, 1, 'lang_keys', 'row'
                    );
                    $newPhrase['Code']  = 'ru';
                    $newPhrase['Value'] = $phraseValue;

                    $GLOBALS['rlDb']->insertOne($newPhrase, 'lang_keys');
                } else {
                    $GLOBALS['rlDb']->updateOne([
                        'fields' => ['Value' => $phraseValue],
                        'where'  => ['Key'   => $phraseKey, 'Code' => 'ru'],
                    ], 'lang_keys');
                }
            }
        }
    }
}
