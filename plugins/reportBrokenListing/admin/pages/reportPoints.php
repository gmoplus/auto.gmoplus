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
if ($_GET['q'] == 'ext') {
    require_once('../../../../includes/config.inc.php');
    require_once(RL_ADMIN_CONTROL . 'ext_header.inc.php');
    require_once(RL_LIBS . 'system.lib.php');
    
    $reefless->loadClass('ReportBrokenListing', null, 'reportBrokenListing');
    $reefless->loadClass('Actions');
    $reportPoints = new \ReportListings\ReportPoints(RL_LANG_CODE);
    $data = array();
    
    /* date update */
    if ($_GET['action'] == 'update') {
        $field = request('field');
        $value = request('value');
        $id = request('id');
        
        if ($field == 'Body') {
            $point_info = $reportPoints->getPointInfoById($id);
            $reportPoints->editSinglePhrase($value, $point_info['Key']);
            exit;
        }
        
        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where' => array(
                'ID' => $id,
            ),
        );
        
        $rlActions->updateOne($updateData, 'report_broken_listing_points');
        exit;
    }
    
    $limit = request('limit');
    $start = request('start');
    $allPoints = $reportPoints->get($start, $limit);
    
    foreach ($allPoints as $key => $point) {
        $data[$key]['ID'] = $point['ID'];
        $data[$key]['Body'] = $point['Value'];
        $data[$key]['Key'] = $point['Key'];
        $data[$key]['Status'] = $lang[$point['Status']];
        $data[$key]['Position'] = $point['Position'];
        $data[$key]['Reports_to_critical'] = $point['Reports_to_critical'];
    }
    
    $count = $reportPoints->total();

    $output['total'] = $count['count'];
    $output['data'] = $data;
    
    echo json_encode($output);
} else {
    $bcAStep = $lang['rbl_report_points'];
}
