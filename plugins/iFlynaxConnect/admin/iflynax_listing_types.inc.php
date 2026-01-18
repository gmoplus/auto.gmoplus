<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : IFLYNAX_LISTING_TYPES.INC.PHP
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

if ( $_GET['q'] == 'ext' )
{
	// system config
	require_once('../../../includes/config.inc.php');
	require_once(RL_ADMIN_CONTROL .'ext_header.inc.php');
	require_once(RL_LIBS .'system.lib.php' );
	
	// date update
	if ( $_GET['action'] == 'update' )
	{
		$reefless -> loadClass( 'Actions' );

		$type = $rlValid -> xSql($_GET['type']);
		$field = $rlValid -> xSql($_GET['field']);
		$value = $rlValid -> xSql(nl2br($_GET['value']));
		$key = $rlValid -> xSql($_GET['key'] );
		$id = (int)$_GET['id'];

        if ($field == 'Status') {
            $field = 'iFlynax_status';

            if ($value == 'approval') {
                $rows = $rlDb->getRow("
                    SELECT COUNT(`ID`) as `count` FROM `" . RL_DBPREFIX . "listing_types` 
                    WHERE `iFlynax_status` = 'active'
                ");

                if ($rows['count'] == 1) {
                    exit;
                }
            }
        }

		$updateData = array(
			'fields' => array(
				$field => $value
			),
			'where' => array(
				'ID' => $id
			)
		);

		$rlActions -> updateOne($updateData, 'listing_types');

		exit;
	}

	// data read
	$sortDir = $rlValid -> xSql($_GET['dir']);
	$sort = $rlValid -> xSql($_GET['sort']);
	$limit = (int)$_GET['limit'];
	$start = (int)$_GET['start'];

	$sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, `T2`.`Value` AS `name` ";
	$sql .= "FROM `". RL_DBPREFIX ."listing_types` AS `T1` ";
	$sql .= "LEFT JOIN `". RL_DBPREFIX ."lang_keys` AS `T2` ON CONCAT('listing_types+name+',`T1`.`Key`) = `T2`.`Key` AND `T2`.`Code` = '". RL_LANG_CODE ."' ";
	$sql .= "WHERE `T1`.`Status` <> 'trash' ";

	if ( $sort ) {
		$sortField = $sort == 'name' ? "`T2`.`Value`" : "`T1`.`{$sort}`";
		$sql .= "ORDER BY {$sortField} {$sortDir} ";
	}
	$sql .= "LIMIT {$start}, {$limit}";

	$data = $rlDb -> getAll($sql);
	$count = $rlDb -> getRow("SELECT FOUND_ROWS() AS `count`");

	foreach ( $data as $key => $value ) {
		$data[$key]['iFlynax_status'] = $lang[$value['iFlynax_status']];
	}

	$output['total'] = $count['count'];
	$output['data'] = $data;

	echo json_encode($output);
}
else
{
	$icons = array(
    	'default',
    	'auto',
    	'auto_parts',
    	'job',
    	'services',
    	'listings',
    	'pets',
    	'boat_staff',
    	'med_service',
    	'boats',
    	'property',
	);
	$rlSmarty -> assign_by_ref('icons', $icons);

	if ( $_GET['action'] == 'edit' ) {
		$key = $rlValid -> xSql($_GET['key']);
		$bcAStep = $lang['edit_type'];

		$rlSmarty -> assign('cpTitle', $bcAStep);

		// get current listing type info
		$type_info = $rlDb -> fetch(array('ID', 'Key', 'iFlynax_position', 'iFlynax_icon', 'iFlynax_status'), array('Key' => $key), null, null, 'listing_types', 'row');
		$type_info['name'] = $lang['listing_types+name+'. $key];

		$rlSmarty -> assign_by_ref('type_info', $type_info);

		if ( $_GET['action'] == 'edit' && !$_POST['fromPost'] ) {
			$_POST['status'] = $type_info['iFlynax_status'];
			$_POST['icon'] = $type_info['iFlynax_icon'] ? $type_info['iFlynax_icon'] : 'default';
		}

		$set_icon = $_POST['icon'] == 'default' ? '' : $_POST['icon'];

		if ( isset($_POST['submit']) ) {
			$update = array(
				'fields' => array(
					'iFlynax_icon' => $set_icon,
					'iFlynax_status' => $_POST['status']
				),
				'where' => array(
					'ID' => $type_info['ID']
				)
			);

			$rlActions -> updateOne($update, 'listing_types');

			$message = $lang['listing_type_edited'];
			$aUrl = array('controller' => $controller);

			// redirect
			$reefless -> loadClass('Notice');
			$rlNotice -> saveNotice($message);
			$reefless -> redirect($aUrl);
		}
	}
}
