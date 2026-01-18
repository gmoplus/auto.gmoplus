<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : AUTO_REG_PREVENT.INC.PHP
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

use Flynax\Utils\Valid;

if (!isset($_GET['q']) || $_GET['q'] !== 'ext') {
    return;
}

require_once __DIR__ . '/../../../includes/config.inc.php';
require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';

if (isset($_GET['action']) && $_GET['action'] === 'update') {
    $field = Valid::escape($_GET['field']);
    $value = Valid::escape(nl2br($_GET['value']));
    $id    = (int) $_GET['id'];

    $updateData = [
        'fields' => [
            $field => $value,
        ],
        'where' => [
            'ID' => $id,
        ],
    ];
    $rlDb->updateOne($updateData, 'reg_prevent');

    exit;
}

$limit = (int) $_GET['limit'];
$start = (int) $_GET['start'];

$query = <<<SQL
    SELECT SQL_CALC_FOUND_ROWS DISTINCT `ID`, `Username`, `Mail`, `IP`, `Reason`, `Date`, `Status` 
    FROM `{db_prefix}reg_prevent` ORDER BY `Date` DESC 
    LIMIT {$start}, {$limit}
SQL;
$data = $rlDb->getAll($query);

foreach ($data as &$value) {
    $value['Status']   = $lang['autoRegPrevent_status_' . $value['Status']];
    $value['Username'] = $value['Username'] ?: $lang['not_available'];
    $value['Mail']     = $value['Mail'] ?: $lang['not_available'];
    $value['IP']       = $value['IP'] ?: $lang['not_available'];
}

$output = [
    'total' => (int) $rlDb->getRow("SELECT FOUND_ROWS() AS `count`", 'count'),
    'data'  => $data,
];
echo json_encode($output);
