<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : TESTIMONIALS.INC.PHP
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

/* ext js action */
if ($_GET['q'] == 'ext')
{
    /* system config */
    require_once( '../../../includes/config.inc.php' );
    require_once( RL_ADMIN_CONTROL . 'ext_header.inc.php' );
    require_once( RL_LIBS . 'system.lib.php' );

    /* date update */
    if ($_REQUEST['action'] == 'update') {
        $type  = $rlValid->xSql($_REQUEST['type']);
        $field  = $rlValid->xSql($_REQUEST['field']);
        $value = trim($_REQUEST['value'], PHP_EOL);
        $id    = $rlValid->xSql($_REQUEST['id']);
        $key   = $rlValid->xSql($_REQUEST['key']);

        $updateData = array(
            'fields' => array(
                $field => $value
            ),
            'where' => array(
                'ID' => $id
            )
        );

        $rlDb->updateOne($updateData, 'testimonials');

        $reefless->loadClass('Testimonials', null, 'testimonials');
        $rlTestimonials->updateBox();
        exit;
    }

    /* data read */
    $limit = (int)$_GET['limit'];
    $start = (int)$_GET['start'];

    $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.* ";
    $sql .= "FROM `". RL_DBPREFIX ."testimonials` AS `T1` ";
    $sql .= "ORDER BY `T1`.`Date` DESC ";
    $sql .= "LIMIT {$start}, {$limit}";
    $data = $rlDb -> getAll($sql);

    $count = $rlDb -> getRow("SELECT FOUND_ROWS() AS `testimonials`");

    foreach ($data as $key => $value)
    {
        $data[$key]['Status'] = $lang[$data[$key]['Status']];
    }

    $output['total'] = $count['testimonials'];
    $output['data'] = $data;

    echo json_encode($output);
}
/* ext js action end */
