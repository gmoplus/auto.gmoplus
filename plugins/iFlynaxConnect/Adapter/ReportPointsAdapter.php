<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : REPORTPOINTSADAPTER.PHP
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

/**
 * Class adapter to get Report Listing points
 *
 * @since 3.4.0
 */
class ReportPointsAdapter
{
    /**
     * @var \rlDb
     */
    protected $rlDb;

    /**
     * @var string
     */
    protected $lang;

    /**
     * @var string
     */
    protected $db_table;

    /**
     * @override
     */
    public function __construct($lang = RL_LANG_CODE)
    {
        $this->lang = $lang;
        $this->rlDb = &$GLOBALS['rlDb'];
        $this->db_table = RL_DBPREFIX . 'report_broken_listing_points';
    }

    /**
     * Return all active ordered points
     *
     * @return array|false
     */
    public static function getAllActivePoints()
    {
        $self = new self;
        $point_prefix = 'report_broken_point_';

        $entries = $self->rlDb->getAll("
            SELECT REPLACE(`Key`, '{$point_prefix}', '') AS `Key` FROM `{$self->db_table}` 
            WHERE `Status` = 'active' ORDER BY `Position`
        ");

        $response = array(
            'point_prefix' => $point_prefix,
            'points' => array(),
        );

        $not_available = $GLOBALS['lang']['not_available'] ?: 'N/A';

        foreach ($entries as $entry) {
            $key  = $point_prefix . $entry['Key'];
            $name = $GLOBALS['lang'][$key] ?: $GLOBALS['rlLang']->getPhrase($key, $GLOBALS['config']['lang'], null, true);
            $name = $name ?: $not_available;

            $response['points'][] = array($entry['Key'] => $name);
        }

        return $response;
    }
}
