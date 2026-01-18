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
