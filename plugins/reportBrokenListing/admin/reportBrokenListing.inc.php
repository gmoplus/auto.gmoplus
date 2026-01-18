<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : REPORTBROKENLISTING.INC.PHP
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

use \ReportListings\Report;
use \ReportListings\Helpers\Requests;

/* ext js action */
if ($_GET['q'] == 'ext') {
    /* system config */
    require_once('../../../includes/config.inc.php');
    require_once(RL_ADMIN_CONTROL . 'ext_header.inc.php');
    require_once(RL_LIBS . 'system.lib.php');
    $reefless->loadClass('ReportBrokenListing', null, 'reportBrokenListing');
    $reefless->loadClass('Listings');
    $reportObj = new Report();
    
    /* date update */
    if ($_GET['action'] == 'update') {
        $field = request('field');
        if ($field == 'Status') {
            $reportObj->changeStatus(request('id'), request('value'));
        }
    }
    /* data read */
    $limit = $rlValid->xSql($_GET['limit']);
    $start = $rlValid->xSql($_GET['start']);
    
    $data = $reportObj->get($start, $limit, true);
    $count = $reportObj->getLastCount();

    foreach ($data as $key => $report) {
        $listing_info = $rlListings->getListing((int)$report['Listing_ID']);
        $listing_title = $rlListings->getListingTitle(
            $listing_info['Category_ID'],
            $listing_info,
            $listing_info['Listing_type']
        );
        
        $data[$key]['Criticality'] = $reportObj->countPercent($report['points']);
        $data[$key]['Listing_title'] = $listing_title;
        $data[$key]['Status'] = $lang[$listing_info['Status']];
    }

    $output['total'] = $count;
    $output['data'] = $data;
    
    echo json_encode($output);
} else {
    $allLangs = $rlLang->getLanguagesList('all');
    $rlSmarty->assign_by_ref('allLangs', $allLangs);
    $reefless->loadClass('ReportBrokenListing', null, 'reportBrokenListing');
    
    $reportPoints = new \ReportListings\ReportPoints(RL_LANG_CODE);
    $allPoints = $reportPoints->getAllActivePoints();
    
    $rlSmarty->assign_by_ref('rbl_points', $allPoints);
    
    if ($_GET['page']) {
        $page = request('page');
        $pages_folder = $rlReportBrokenListing->getConfig('a_pages');
        if (file_exists($pages_folder . $page . '.php')) {
            require_once $pages_folder . $page . '.php';
        } else {
            $error = true;
        }
    } else {
        /* register ajax methods */
        $rlXajax->registerFunction(array(
            'deletereportBrokenListing',
            $rlReportBrokenListing,
            'ajaxDeletereportBrokenListing',
        ));
        $rlXajax->registerFunction(array('deleteListing', $rlReportBrokenListing, 'ajaxDeleteListing'));
    }
    
    if ($error) {
        //TODO: throw an error
    }
}
