<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLREPORTBROKENLISTING.CLASS.PHP
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

namespace ReportListings;

/**
 * Class ReportDetail
 *
 * @package ReportListings
 */
class ReportDetail
{
    /**
     * @var int - Listing ID
     */
    private $listing_id;

    /**
     * @var string - Class table
     */
    private $db_table;

    /**
     * @var array - Flynax configurations
     */
    private $flConfigs;

    /**
     * @var \rlDb
     */
    protected $rlDb;

    /**
     * @var \rlActions
     */
    protected $rlActions;

    /**
     * @var array - Reports array
     */
    private $reports;

    /**
     * @var int - Reports count
     */
    private $reports_count;

    /**
     * @var ReportFilter
     */
    private $reportFilters;

    /**
     * @return mixed
     */
    public function getListingId()
    {
        return $this->listing_id;
    }

    /**
     * @param mixed $listing_id
     */
    public function setListingId($listing_id)
    {
        $this->listing_id = (int)$listing_id;
    }

    /**
     * ReportDetail constructor.
     */
    public function __construct()
    {
        $this->rlDb = FlynaxObjectsContainer::getObject('rlDb');
        $this->rlActions = FlynaxObjectsContainer::getObject('rlActions');
        $this->db_table = RL_DBPREFIX . 'report_broken_listing';
        $this->flConfigs = FlynaxObjectsContainer::getConfig('flConfigs');
        $this->reportFilters = new ReportsFilter();
    }

    /**
     * Filter reports by provided condition
     *
     * @param array $args  - Filter condition
     * @param int   $start - Start filtering results from
     * @param int   $limit - Limit of the filtering result
     */
    public function filter($args, $start, $limit)
    {
        foreach ($args as $filter=>$value)
        {
            $this->reportFilters->filterBy($filter, $value, true)->get($start, $limit);
        }
    }

    /**
     * Getting reports (getReportDetailByListingID helper)
     *
     * @param  int $start
     * @param  int $limit
     * @return ReportDetail
     */
    public function getReportDetails($start = 0, $limit = 20)
    {
        return $this->reportFilters
            ->filterBy('Listing_ID', $this->listing_id)
            ->orderBy('Report_key')
            ->get($start, $limit);
    }

    /**
     * @return int - Get reports count
     */
    public function count()
    {
        return $this->reports_count;
    }


    /**
     * Getting all reports by Listing ID
     *
     * @param  int $id - Listing ID
     * @param  int $start
     * @param  int $limit
     * @return array|bool  - Reports array | false if nothig has been founds
     */
    public function getReportDetailByListingID($id, $start, $limit)
    {
        if (!$id && !$this->listing_id) {
            return false;
        }
        $id = $id ?: $this->listing_id;
        $sql = "SELECT * FROM `{$this->db_table}` WHERE `Listing_ID` = {$id} AND `Status` <> 'trash' ";
        $sql .= "LIMIT {$start},{$limit}";

        return $this->rlActions->getAll($sql);
    }

    /**
     * Get total reports of the Listing
     *
     * @return int - Reports count
     */
    public function total()
    {
        return $this->reportFilters->filterBy('Listing_ID', $this->listing_id)->total();
    }

    /**
     * On report Deleting event
     *
     * @param  int    $report_id - Deleting report ID
     * @param  string $reason    - Reason of the deleting report
     * @return bool              - Is event has been proccesses succesfull
     */
    public function onReportDelete($report_id, $reason = '')
    {
        $point = new Report();
        $point->beforeReportRemove($report_id, $reason);
        $result = $this->flConfigs['trash'] ? $this->move2Trash($report_id) : $this->delete($report_id);

        return $result;
    }

    /**
     * Move report to the trash box
     *
     * @param $report_id - Moving to the trash report ID
     * @return bool      - Does process is succesfull
     */
    public function move2Trash($report_id)
    {
        $updateData = array(
            'fields' => array(
                'Status' => 'trash',
            ),
            'where' => array(
                'ID' => $report_id,
            ),
        );

        return $this->rlActions->updateOne($updateData, 'report_broken_listing');
    }

    /**
     * Delete report
     *
     * @param  int $report_id - Removing report ID
     * @return bool            - Is deleting process has been success
     */
    public function delete($report_id)
    {
        $reportsPoint = new Report();
        $sql = "DELETE FROM `{$this->db_table}` WHERE `ID` = {$report_id}";
        return $this->rlDb->query($sql);
    }

    /**
     * Count single report percentage
     *
     * @param  array $report  - Report info
     * @return float $percent - Report percents
     */
    public function getSingleReportPercent($report)
    {
        $filter = new ReportsFilter();
        $point = new ReportPoints(RL_LANG_CODE);

        $point_info = $point->getPointInfoByKey($report['Report_key']);

        $total = $filter->massFilter(array(
                'Listing_ID' => $report['Listing_ID'],
                'Report_key' => $report['Report_key'],
            )
        )->total();

        $max_reports = $report['Report_key'] != 'custom'
            ? $point_info['Reports_to_critical']
            : $this->flConfigs['reportBroken_default_point_weight'];

        $coeff = $total / $max_reports;
        $percent = round(($coeff * 100) / 0.9, 2);

        return $percent > 100 ? 100 : $percent;
    }
}
