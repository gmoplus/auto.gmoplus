<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : REPORT.PHP
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

namespace ReportListings;

class Report
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
     * @var \rlListings
     */
    protected $rlListings;
    
    /**
     * @var ReportsFilter
     */
    public $reportFilters;
    
    /**
     * @var string - Active Database table
     */
    private $table;
    
    /**
     * @var array - Flynax configurations
     */
    protected $flConfigs;

    /**
     * Last db fetch rows count
     *
     * @since 3.2.0
     * @var integer
     */
    private $count = 0;

    /**
     * Report constructor.
     */
    public function __construct()
    {
        $this->rlDb = FlynaxObjectsContainer::getObject('rlDb');
        $this->rlActions = FlynaxObjectsContainer::getObject('rlActions');
        $this->rlListings = FlynaxObjectsContainer::getObject('rlListings');
        $this->reportFilters = new ReportsFilter();
        $this->table = 'report_broken_listing';
        $this->flConfigs = FlynaxObjectsContainer::getConfig('flConfigs');
    }
    
    /**
     * Add new report
     *
     * @param  array $data - New report data
     * @return int         - New report ID
     */
    public function add($data)
    {
        $point = new ReportPoints(RL_LANG_CODE);
        $point_info = $point->getPointInfoByKey($data['key']);
        $key = isset($data['key']) ? $data['key'] : 'custom';

        $insert = array(
            'Listing_ID' => $data['Listing_ID'],
            'Report_key' => $key,
            'Message' => $data['custom_message'] ?: $point_info['Phrase'],
            'Account_ID' => $data['Account'] ?: 0,
        );
        $insert['Message'] = $data['custom_message'] ?: $point_info['Phrase'];
        $insert['Account_ID'] = $data['Account'] ?: 0;
        $insert['Date'] = 'NOW()';
        $insert['Status'] = 'active';
        $insert['IP'] = $data['IP'];
        
        $this->rlActions->insertOne($insert, 'report_broken_listing');
        
        $new_id = $this->rlDb->insertID();
        $this->afterReportAdd($new_id);
        
        return $new_id;
    }
    
    /**
     * Assign report to another
     *
     * @param array  $report  - Report that you want to re-assign
     * @param string $toPoint - Destination report key
     * @return bool           - Assigning process was successful
     */
    public function assign($report = array(), $toPoint = '')
    {
        $lang = FlynaxObjectsContainer::getConfig('flLang');
        $id = $report['ID'];
        $pointObj = new ReportPoints(RL_LANG_CODE);
        $message = $lang['rbl_other'];
        
        if ($toPoint !== 'custom') {
            $toPointInfo = $pointObj->getPointInfoByKey($toPoint);
            $message = $toPointInfo['Phrase'];
        }
        
        if ($id) {
            $updateData = array(
                'fields' => array(
                    'Report_key' => $toPoint,
                    'Message' => $message,
                ),
                'where' => array(
                    'ID' => $id,
                ),
            );
            
           return $this->rlActions->updateOne($updateData, $this->table);
        }
        
        return false;
    }
    
    /**
     * Event method, which is fired after adding report
     *
     * @param $new_id - Created report ID
     */
    public function afterReportAdd($new_id)
    {
        $reportPoint = new ReportPoints(RL_LANG_CODE);
        $report = $this->reportFilters->filterBy('ID', $new_id)->first();
        $filterBy = array(
            'Listing_ID' => $report['Listing_ID'],
        );
        
        if ($report['Report_key'] != 'custom') {
            $point_info = $reportPoint->getPointInfoByKey($report['Report_key']);
            $max_reports = $point_info['Reports_to_critical'];
            $filterBy['Report_key'] = $point_info['Key'];
            $total = $this->reportFilters->massFilter($filterBy)->total();
        } else {
            $filterBy['Report_key'] = 'custom';
            $total = $this->reportFilters->massFilter($filterBy)->total();
            $max_reports = $this->flConfigs['reportBroken_default_point_weight'];
        }
        
        if ($total >= $max_reports) {
            $this->changeStatus($report['Listing_ID'], 'approval');
        }
    }
    
    /**
     * Delete report
     *
     * @param int $id - ID of the Report
     * @return bool   - Does removing process has been succesfull
     */
    public function delete($id)
    {
        if (!$id) {
            return false;
        }
        
        $sql = "DELETE FROM `". RL_DBPREFIX ."{$this->table}` WHERE `ID` = {$id}";
        return $this->rlDb->query($sql);
    }
    
    /**
     * Get reports
     *
     * @since 3.2.0 - $calcRows parameter added
     *
     * @param int    $start    - Start position
     * @param int    $end      - End position
     * @param bool   $calcRows - Calc found rows
     * @return array           - Reports
     */
    public function get($start, $end, $calcRows = false)
    {
        $filter = new ReportsFilter();
        $reportPoint = new ReportPoints(RL_LANG_CODE);
        
        $listings = $filter
            ->fetchField(array('*', 'COUNT(`ID`) as `Reports_count`'))
            ->groupBy('Listing_ID')
            ->get($start, $end, $calcRows);

        if ($calcRows) {
            $this->count = $filter->getLastCount();
        }

        foreach ($listings as $key => $listing) {
            
            $reports = $filter
                ->fetchField(array('Report_key',"COUNT(`ID`) AS 'count'"))
                ->groupBy('Report_key')
                ->filterBy('Listing_ID', $listing['Listing_ID'])
                ->get();

            foreach ($reports as $report) {
                $reports_count = $report['count'];
                
                if ($report['Report_key'] != 'custom') {
                    $point_info = $reportPoint->getPointInfoByKey($report['Report_key']);
                }
    
                $max_reports = $report['Report_key'] != 'custom'
                    ? $point_info['Reports_to_critical']
                    : $this->flConfigs['reportBroken_default_point_weight'];
                
                $coefficient = round($reports_count / $max_reports, 1);
                $percent = round(($coefficient * 100) / 0.9, 1);
                
                $listings[$key]['points'][$report['Report_key']]['coef'] = $coefficient;
                $listings[$key]['points'][$report['Report_key']]['percent'] = $percent;
            }
        }
        
        return $listings;
    }

    /**
     * Get last query found rows count
     *
     * @since 3.2.0
     * @return int - Rows found count
     */
    public function getLastCount()
    {
        $count = $this->count;
        $this->count = 0;

        return $count;
    }
    
    /**
     * Count percentage of the Listing Point, for showing it in the Criticality field
     *
     * @param  array $points  - Report points info
     * @return float          - Percent of the report
     */
    public function countPercent($points)
    {
        if (!$points || !is_array($points)) {
            return 0;
        }

        $percent = 0;
        $max = 0;
        foreach ($points as $key => $point) {
            if ($point['percent'] > 70 && $point['percent'] > $max) {
                $max = $point['percent'];
                $percent = $point['percent'];
            } else {
                $percent += $point['percent'];
            }
        }
        
        if ($max) {
            return $max > 100 ? 100 : $max;
        }
    
        return round($percent / count($points), 2) > 100 ? 100 : round($percent / count($points), 2);
    }
    
    /**
     * Event method, which are fired before removing report
     *
     * @param int    $report_id
     * @param string $reason    - The reason of removing report
     */
    public function beforeReportRemove($report_id, $reason = '')
    {
        $reportPoint = new ReportPoints(RL_LANG_CODE);
        $sql = "SELECT * FROM `" . RL_DBPREFIX . "report_broken_listing` WHERE `ID` = {$report_id}";
        $report_info = $this->rlDb->getRow($sql);
        $point_info = $reportPoint->getPointByKey($report_info['Report_key']);
        $filterBy = array(
            'Listing_ID' => $report_info['Listing_ID'],
            'Report_key' => $report_info['Report_key']
        );
        $total = $this->reportFilters->massFilter($filterBy)->total();
        
        if ($point_info['Reports_to_critical'] - $total > 1) {
            $this->makeActive($report_info['Listing_ID'], $report_info['Report_key']);
        }
        
        if ($report_info['Account_ID']) {
            $rlAccount = FlynaxObjectsContainer::getObject('rlAccount');
            $acc_info = $rlAccount->getProfile((int)$report_info['Account_ID']);
            
            if ($acc_info['Mail']) {
                FlynaxObjectsContainer::getObject('reefless')->loadClass('Mail');
                $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('rbl_report_removed', $acc_info['Lang']);
                
                $find = ['{name}', '{report_message}', '{reason}'];
                $replace = [$acc_info['Full_name'], $report_info['Message'], $reason];
                $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
                
                $GLOBALS['rlMail']->send($mail_tpl, $acc_info['Mail']);
            }
        }
    }
    
    /**
     * Make provided listing active
     *
     * @param  int    $listing_id
     * @param  string $key        - Report Key
     * @return bool               - Does making acive process has been succesfull
     */
    public function makeActive($listing_id, $key)
    {
        $updateData = array(
            'fields' => array(
                'Status' => 'active',
            ),
            'where' => array(
                'Listing_ID' => $listing_id,
                'Report_key' => $key
            ),
        );
    
        return $this->rlActions->updateOne($updateData, 'report_broken_listing');
    }
    
    
    /**
     * Changins status of the listing
     *
     * @param  int    $listing_id - ID of chaning listing
     * @param  string $new_status - New status of the changing listing
     * @return bool               - Status changing result
     */
    public function changeStatus($listing_id, $new_status)
    {
        if (!$listing_id || !$new_status) {
            return false;
        }
        
        $updateData = array(
            'fields' => array(
                'Status' => $new_status,
            ),
            'where' => array(
                'ID' => $listing_id,
            ),
        );
        
        return $this->rlActions->updateOne($updateData, 'listings');
    }
    
    /**
     * Get all reports
     *
     * @return array - Reports array
     */
    public function getAll()
    {
        $sql = "SELECT `T1`.`ID`, `T1`.`Message`, `T1`.`Listing_ID`, ";
        $sql .= "`T1`.`Account_ID`, `T1`.`Date`,`T2`.`Username`, `T2`.`First_name`, `T2`.`Last_name` ";
        $sql .= "FROM `" . RL_DBPREFIX . "report_broken_listing` AS `T1` ";
        $sql .= "LEFT JOIN `" . RL_DBPREFIX . "accounts` AS `T2` ON `T1`.`Account_ID` = `T2`.`ID` ";
        
        return $this->rlDb->getAll($sql);
    }
}
