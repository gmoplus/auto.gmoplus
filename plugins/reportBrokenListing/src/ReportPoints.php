<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : REPORTPOINTS.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2023
 *	https://www.flynax.com
 *
 ******************************************************************************/

namespace ReportListings;

/**
 * Class ReportPoints
 * @package ReportListings
 */
class ReportPoints
{
    /**
     * Should getting report points method ignore language filtering
     */
    const LANG_INGORE = true;

    /**
     * @var array - Array of the Flynax languages
     */
    protected $lang;

    /**
     * @var \rlDb
     */
    protected $rlDb;

    /**
     * @var string - DB table of this class
     */
    protected $db_table;

    /**
     * @var array - Flynax configurations
     */
    private $flConfigs;

    /**
     * @var Report - Report class instance
     */
    protected $reportObj;

    /**
     * ReportPoints constructor.
     * @param string $lang - All reports will be filtered depending on this language. Basicly it is current site language
     */
    public function __construct($lang)
    {
        $rlReportListings = FlynaxObjectsContainer::getObject('rlReportBrokenListing');
        $this->lang = $lang;
        $this->reportObj = new Report();
        $this->rlDb = FlynaxObjectsContainer::getObject('rlDb');
        $this->db_table = $rlReportListings->getConfig('report_points_table');
        $this->flConfigs = FlynaxObjectsContainer::getConfig('flConfigs');
    }

    /**
     * Triggering after submit button of the report point panel has been clicked
     *
     * @param  array $formData - Value of the all inputs of the form
     * @return bool            - Is all data has been handled
     */
    public function onSubmit($formData)
    {
        $allLangs = FlynaxObjectsContainer::getObject('rlLang')->getLanguagesList('all');
        $result = true;

        $phrases = array();
        foreach ($allLangs as $key => $lang) {
            if ($formData[$key]) {
                $phrases[$key] = $formData[$key];
            }
        }

        $new_point = array(
            'Status' => $formData['status'],
            'Reports_to_critical' => $formData['reports_count_to_critical'],
            'Phrases' => $phrases,
        );
        $result = $this->add($new_point);

        return $result;
    }

    /**
     * Add report point phrase
     *
     * @param string $pointKey - Report point will be added with this key
     * @param string $langCode - Report point will be added under this language
     * @param string $phrase    - Report point phrase
     * @param $status           - Report point status
     * @return bool             - Is report point has been added succesfully
     */
    public function addPhrase($pointKey, $langCode, $phrase, $status)
    {
        $newPhrase = array(
            'Code' => $langCode,
            'Module' => 'common',
            'Key' => $pointKey,
            'Value' => $this->excerpt($phrase, $this->flConfigs['reportBroken_message_length']),
            'Plugin' => 'reportBrokenListing',
            'Status' => $status,
        );

        return $this->rlDb->insertOne($newPhrase, 'lang_keys');
    }

    /**
     * Edit single report point phrase by key
     *
     * @param  string $newValue - New pharse
     * @param  string $langKey  - Phrase key
     * @param  string $langCode - Editable phrase language code
     * @return bool              - Is editing process has been passed succesfully
     */
    public function editSinglePhrase($newValue, $langKey, $langCode = '')
    {
        if (!$langKey) {
            return false;
        }

        $langCode = $langCode ?: $this->lang;
        $updateData = array(
            'fields' => array(
                'Value' => $newValue,
            ),
            'where' => array(
                'Key' => $langKey,
                'Plugin' => 'reportBrokenListing',
                'Code' => $langCode
            )
        );

        return $this->rlDb->updateOne($updateData, 'lang_keys');
    }

    /**
     * Add new report to the database
     *
     * @param  array $report - Report data
     * @return bool          - Adding action result
     */
    public function add($report)
    {
        $pointKey = 'report_broken_point_' . time();
        $phrases = $report['Phrases'];

        unset($report['Phrases']);

        $report['Key'] = $pointKey;
        $report['Position'] = $this->getMaxPosition() + 1;

        if ($this->rlDb->insertOne($report, 'report_broken_listing_points')) {
            foreach ($phrases as $code => $phrase) {
                $this->addPhrase($pointKey, $code, $phrase, $report['Status']);
            }

            return true;
        }

        return false;
    }

    /**
     * Getting max position of the all added report points before
     *
     * @return int - Max position
     */
    public function getMaxPosition()
    {
        $sql = "SELECT MAX(`Position`) as `Max` FROM `" . RL_DBPREFIX . "report_broken_listing_points`";
        $response = $this->rlDb->getRow($sql);
        if (!$response['Max']) {
            return 0;
        }

        return $response['Max'];
    }

    /**
     * Edit report point by key
     *
     * @param  array  $newValues - New report point data
     * @param  string $key        - Key of the looking report point
     * @return bool               - Edit action result
     */
    public function edit($newValues, $key)
    {
        if (empty($newValues)) {
            return false;
        }

        $allLangs = FlynaxObjectsContainer::getObject('rlLang')->getLanguagesList('all');
        $phrases = array();
        foreach ($allLangs as $langKey => $lang) {
            if ($newValues[$langKey]) {
                $phrases[$langKey] = $newValues[$langKey];
            }
        }

        $update = array(
            'fields' => array(
                'Status' => $newValues['status'],
                'Reports_to_critical' => $newValues['reports_count_to_critical'],
            ),
            'where' => array(
                'Key' => $key,
            )
        );

        if ($this->rlDb->updateOne($update, 'report_broken_listing_points')) {
            foreach ($phrases as $code => $phrase) {
                $this->editSinglePhrase($phrase, $key, $code);
            }

            return true;
        }

        return false;
    }

    /**
     * Getting total number of the report points
     * @return \data
     */
    public function total()
    {
        $sql = "SELECT COUNT(`ID`) as `count` FROM `" . RL_DBPREFIX . "report_broken_listing_points`";
        return $this->rlDb->getRow($sql);
    }

    /**
     * Delete report point by key (deleteBy method helper)
     *
     * @param  string $key - Removing report key
     * @return bool       - Is removing has been successfully passed
     */
    public function deleteByKey($key)
    {
        return $this->deleteBy('key', $key);
    }

    /**
     * Delete report point by ID (deleteBy method helper)
     *
     * @param  int $id    - Removing report ID
     * @return bool       - Is removing has been successfully passed
     */
    public function deleteById($id)
    {
        return $this->deleteBy('id', $id);
    }

    /**
     * Deleting report point from db by key or ID
     *
     * @param  string $type  - Report point type removing method: {id, key}
     * @param  string $param - Removing method parameter
     * @return bool          - Is removing has been succesfully passed
     */
    public function deleteBy($type, $param)
    {
        switch ($type) {
            case 'key':
                $key = $param;
                break;
            case 'id':
                $point_info = $this->getPointInfoById($param);
                $key = $point_info['Key'];
                break;
            default:
                return false;
                break;
        }

        $sql = "DELETE FROM `" . RL_DBPREFIX . "report_broken_listing_points` WHERE `Key` = '{$key}'";
        if ($this->rlDb->query($sql)) {
            $this->deletePhrase($key);
            return true;
        }
    }

    /**
     * Does reports exist by the point key
     *
     * @param  string $pointKey - Point key
     * @return array  $reports  - Found reports
     */
    public function doesReportsExist($pointKey = '')
    {
        if (!$pointKey) {
            return false;
        }

        $reports = array();
        $reportsFilter = new ReportsFilter();
        $reports = $reportsFilter->filterBy('Report_key', $pointKey)->get();

        return $reports;
    }

    /**
     * Delete phrase of the Report from Lang_keys database
     *
     * @param  string $key - Point key
     * @return bool        - Is removing phrases has been passed successfully
     */
    public function deletePhrase($key)
    {
        $sql = "DELETE FROM `" . RL_DBPREFIX . "lang_keys` WHERE `Key` = '{$key}' ";
        $sql .= "AND `Plugin` = 'reportBrokenListing'";

        return $this->rlDb->query($sql);
    }

    /**
     * Get all report points by lang
     *
     * @param  int $start  - Start point of the getting reports
     * @param  int $end    - End point of the getting reports
     * @return array|false - Report points array | false if nothig found
     */
    public function get($start = 0, $end = 10, $status = '')
    {
        $sql = "SELECT * FROM `" . RL_DBPREFIX . "report_broken_listing_points` ";
        $sql .= $status ? "WHERE `Status` = '{$status}' " : '';
        $sql .= "ORDER BY `Position`";
        $sql .= $end ? "LIMIT {$start}, {$end}" : '';

        $points = $this->rlDb->getAll($sql);

        foreach ($points as $key => $point) {
            $sql = "SELECT * FROM `" . RL_DBPREFIX . "lang_keys` WHERE `Key` = '{$point['Key']}' ";
            $sql .= "AND `Plugin` = 'reportBrokenListing' ";

            if ($this->lang) {
                $sql .= "AND `Code` = '{$this->lang}'";
                $phrase = $this->rlDb->getRow($sql);
                $points[$key]['Value'] = $phrase['Value'];

                continue;
            }
        }

        return $points;
    }

    /**
     * Return all active ordered points
     *
     * @return array|false - Reports array
     */
    public function getAllActivePoints()
    {
        return $this->get(0,0,'active');
    }

    /**
     * Getting all report points by lang key
     *
     * @param  string $key - Searching point by this key
     * @return mixed|false - Report points keys | false if nothing found
     */
    public function getPointByKey($key)
    {
        if (!$key) {
            return false;
        }
        $sql = "SELECT * FROM `" . RL_DBPREFIX . "report_broken_listing_points` ";
        $sql .= "WHERE `Key` = '{$key}'";
        $point = $this->rlDb->getRow($sql);
        $point['Phrases'] = $this->getPointPhrases($key, self::LANG_INGORE);

        return $point;
    }

    /**
     * Getting Report points phrases
     *
     * @param string $point_key       - Report
     * @param bool   $ignore_language - Ignore language and get all associated with point phrases
     * @return array|bool             - Report languages | False if nothig has been found or wrong arguments
     */
    public function getPointPhrases($point_key, $ignore_language = false)
    {
        if (!$point_key) {
            return false;
        }

        $phrases = array();
        $sql = "SELECT * FROM `" . RL_DBPREFIX . "lang_keys` WHERE `Key` = '{$point_key}' ";
        $sql .= "AND `Plugin` = 'reportBrokenListing' ";

        if ($this->lang && !$ignore_language) {
            $sql .= "AND `Code` = '{$this->lang}'";
            $result = $this->rlDb->getRow($sql);
            $phrases[$this->lang] = $result['Value'];

            return $result['Value'];
        }

        $result = $this->rlDb->getAll($sql);
        foreach ($result as $phrase ) {
          $phrases[$phrase['Code']] = $phrase['Value'];
        }

        return $phrases;
    }

    /**
     * Geeting report point info by ID (reportPointBy helper)
     *
     * @param  int $id - Searching report point ID
     * @return array|bool  - Report point info | False, if report didn't found
     */
    public function getPointInfoById($id)
    {
        return $this->getPointInfoBy('id', $id);
    }

    /**
     * Geeting report point info by key (reportPointBy helper)
     *
     * @param  string $key - Searching report point key
     * @return array|bool  - Report point info | False, if report didn't found
     */
    public function getPointInfoByKey($key)
    {
       return $this->getPointInfoBy('key', $key);
    }

    /**
     * Remove point by provided condition
     *
     * @param string $type  - Removing condition type: {id, key}
     * @param string $value - Removing condition value
     * @return array|bool   - Report point information | False if nothing found or method has called incorrectlys
     */
    public function getPointInfoBy($type, $value)
    {
        switch ($type) {
            case 'id':
                $column = '`ID`';
                break;
            case 'key':
                $column = '`Key`';
                $value = "'{$value}'";
                break;
            default:
                return false;
                break;
        }

        $sql = "SELECT * FROM `" . RL_DBPREFIX . "report_broken_listing_points` WHERE {$column} = {$value}";
        $report = $this->rlDb->getRow($sql);
        $where = "`Key` = '{$report['Key']}' AND `Code` = '{$this->lang}'";
        $report['Phrase'] = $this->rlDb->getOne('Value', $where, 'lang_keys');

        return $report;
    }

    /**
     * Remove label with all associated reports
     *
     * @param  string $pointKey - Key of the label that you want to remove
     * @return bool             - Removing process status
     */
    public function removeWithAssociatedReports($pointKey = '')
    {
        if (!$pointKey) {
            return false;
        }

        $reports = $this->reportObj->reportFilters->filterBy('Report_key', $pointKey)->get();
        foreach ($reports as $report) {
            $this->reportObj->delete($report['ID']);

        }
        $this->deleteByKey($pointKey);

        return true;
    }

    /**
     * Remove report label with assigning all associated reports to another label
     *
     * @param string $removingLabelKey - Label that you want to remove.
     * @param string $assignTo         - Label to what report will assign after removing.
     * @return bool                    - Does assign procedure was successful
     */
    public function removeWithAssigningToAnotherLabel($removingLabelKey = '', $assignTo = '')
    {
        $reports = $this->reportObj->reportFilters->filterBy('Report_key', $removingLabelKey)->get();
        foreach ($reports as $report) {
            $this->reportObj->assign($report, $assignTo);
        }

        return $this->deleteByKey($removingLabelKey);
    }

    /**
     * Excerpt string to the provided length
     *
     * @param  string $string - Working string
     * @param  int    $limit  - String length
     * @return string         - Handled string
     */
    public function excerpt($string = '', $limit = 0)
    {
        return strlen($string) > $limit ? substr($string, 0, $limit) : $string;
    }

    /*** DEPRECATED ***/
    /**
     * @deprecated 3.1.3
     * @var \rlActions
     */
    protected $rlActions;
}
