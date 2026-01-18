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

if ($_GET['q'] == 'ext') {
    require_once('../../../../includes/config.inc.php');
    require_once(RL_ADMIN_CONTROL . 'ext_header.inc.php');
    require_once(RL_LIBS . 'system.lib.php');
    
    $reefless->loadClass('ReportBrokenListing', null, 'reportBrokenListing');
    $reefless->loadClass('Account');
    $listing_id = request('listing_id');
    $reefless->loadClass('Actions');
    
    $reportDetails = new \ReportListings\ReportDetail();
    $report = new \ReportListings\Report();
    $reportDetails->setListingId($listing_id);
    
    /* date update */
    if ($_GET['action'] == 'update') {
        $id = request('id');
        $field = request('field');
        $value = request('value');
        
        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where' => array(
                'ID' => $id,
            ),
        );
        
        $rlActions->updateOne($updateData, 'report_broken_listing');
    }
    
    $limit = request('limit');
    $start = request('start');
    
    if (!request('filter')) {
        $data = $reportDetails->getReportDetails($start, $limit);
        foreach ($data as $key => $item) {
            
            if ($item['Account_ID'] <= 0) {
                $data[$key]['Account_username'] = $lang['website_visitor'];
                continue;
            }
            
            $user_info = $rlAccount->getProfile((int) $item['Account_ID']);
            $data[$key]['Account_username'] = $user_info['Username'];
        }
        
        $total = $reportDetails->total();
    } else {
        /* filter data */
        $filterBy = array();
        $listing_id = request('listing_id');
        
        if (!empty(request('point'))) {
            $filterBy['Report_key'] = request('point');
        }
        
        if (!empty(request('date_from'))) {
            $filterBy['Date']['from'] = request('date_from');
        }
        
        if(!empty(request('date_to'))) {
            $filterBy['Date']['to'] = request('date_to');
        }
    
        if ($listing_id) {
            $filterBy['Listing_ID'] = $listing_id;
        }
        
        $data = $report->reportFilters->massFilter($filterBy)->get($start, $limit);
        $total = $report->reportFilters->massFilter($filterBy)->total();
    }
    
    /* prepare data for the grids */
    foreach ($data as $key => $report) {
        $data[$key]['Criticality'] = $reportDetails->getSingleReportPercent($report);
        $data[$key]['Status'] = $lang[$report['Status']];
    }

    $output['total'] = $total;
    $output['data'] = $data;
    
    echo json_encode($output);
    exit;
} else {
    /* prepare data for the filter area */
    $reportPoints = new \ReportListings\ReportPoints(RL_LANG_CODE);
    $points = $reportPoints->get();
    $rlSmarty->assign_by_ref('report_points', $points);
    
    /* Prepare listing details data */
    $listing_id = request('id');
    
    $listing_data = $rlListings->getListing($listing_id, true, true);
    $listing_type = $rlListingTypes->types[$listing_data['Listing_type']];
    $category_id = $listing_data['Category_ID'];
    
    $listing = $rlListings->getListingDetails($category_id, $listing_data, $listing_type);
    $rlSmarty->assign_by_ref('listing', $listing);
    
    $photos = $rlDb -> fetch( '*', array( 'Listing_ID' => $listing_id, 'Status' => 'active' ), "AND `Thumbnail` <> '' AND `Photo` <> '' ORDER BY `Position`", $listing_data['Image'], 'listing_photos' );
    $rlSmarty->assign_by_ref('photos', $photos);
    
    $bcAStep = $lang['rbl_detail'];
}
