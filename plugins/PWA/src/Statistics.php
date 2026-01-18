<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : STATISTICS.PHP
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

namespace Flynax\Plugins\PWA;

use Flynax\Utils\Util;
use Flynax\Utils\Valid;
use Jenssegers\Agent\Agent;

class Statistics
{
    const DB_TABLE = 'pwa_usage_info';
    const DB_TABLE_WITH_PREFIX = RL_DBPREFIX . Statistics::DB_TABLE;

    /**
     * @var \Jenssegers\Agent\Agent
     */
    private $mobileDetector;

    /**
     * @var \rlDb
     */
    private $rlDb;

    /**
     * Statistics constructor.
     */
    public function __construct()
    {
        $this->rlDb           = $GLOBALS['rlDb'];
        $this->mobileDetector = new Agent();
    }

    /**
     * @return array
     */
    public function getMainStatistic()
    {
        $self = new self();

        return [
            'IP'             => Util::getClientIP(),
            'Browser'        => $self->getBrowser(),
            'OS'             => $self->getOs(),
            'Plugin_version' => PWA_PLUGIN_VERSION,
        ];
    }

    /**
     *
     */
    public function getBrowser()
    {
        return $this->mobileDetector->browser();
    }

    /**
     *
     */
    public function getOs()
    {
        return $this->mobileDetector->platform();
    }

    public static function collectAndSave()
    {
        $self = new Statistics();

        $info = $self->getMainStatistic();
        $where = "`IP` = '{$info['IP']}' AND `Browser` = '{$info['Browser']}' AND `OS` = '{$info['OS']}'";

        if ($GLOBALS['plugins']['ipgeo'] && $location = $_SESSION['GEOLocationData']) {
            Valid::escape($location->Country_name);
            Valid::escape($location->Region);
            Valid::escape($location->City);

            $where .= " AND `Country` = '{$location->Country_name}'";
            $where .= " AND `State` = '{$location->Region}'";
            $where .= " AND `City` = '{$location->City}'";

            $info['Country'] = stripslashes($location->Country_name);
            $info['State']   = stripslashes($location->Region);
            $info['City']    = stripslashes($location->City);
        }

        $rowExist = $self->rlDb->getOne('ID', $where, self::DB_TABLE);

        if (!$rowExist) {
            $info['Date'] = 'NOW()';
            return $info ? $self->rlDb->insertOne($info, self::DB_TABLE) : false;
        }

        return true;
    }

    public function getStat()
    {
        $limit     = (int) $_GET['limit'];
        $start     = (int) $_GET['start'];
        $sortField = $_GET['sort'];
        $sortType  = $_GET['dir'];

        if (!is_numeric($start) || !is_numeric($limit) || !defined('REALM')) {
            return [];
        }

        $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM `' . Statistics::DB_TABLE_WITH_PREFIX . '` ';
        $sql .= "ORDER BY ";
        if ($sortField && $sortType) {
            $sql .= "`{$sortField}` {$sortType} ";
        } else {
            $sql .= "`Date` DESC ";
        }
        $sql .= "LIMIT {$start}, {$limit}";

        $data  = $this->rlDb->getAll($sql);
        $count = $this->rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

        return ['data' => $data, 'total' => $count['count']];
    }
}
