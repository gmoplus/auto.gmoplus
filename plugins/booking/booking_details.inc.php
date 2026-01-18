<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.6.0
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: BOOKING_DETAILS.INC.PHP
 *
 *	The software is a commercial product delivered under single, non-exclusive,
 *	non-transferable license for one domain or IP address. Therefore distribution,
 *	sale or transfer of the file in whole or in part without permission of Flynax
 *	respective owners is considered to be illegal and breach of Flynax License End
 *	User Agreement.
 *
 *	You are not allowed to remove this information from the file without permission
 *	of Flynax respective owners.
 *
 *	Flynax Classifieds Software 2022 |  All copyrights reserved.
 *
 *	https://www.flynax.com/
 *
 ******************************************************************************/

$reefless->loadClass('Booking', null, 'booking');
$listing_id = (int) $_GET['id'];

if ($listing_id) {
    $requests = $rlBooking->getRequests($listing_id);
    $rlSmarty->assign_by_ref('requests', $requests);

    $rlSmarty->assign('binding_days', $rlDb->fetch(
            '*',
            array('Listing_ID' => $listing_id, 'Status' => 'active'),
            null,
            null,
            'booking_bindings',
            'row'
        )
    );

    $rlSmarty->assign('booking_configs', $rlBooking->getConfigs($listing_id));

    // get listing info
    $listing_data = $rlListings->getListing($listing_id);

    // get expire date of listing
    $sql = "SELECT DATE_ADD(`T1`.`Pay_date`, INTERVAL `T2`.`Listing_period` DAY) AS `Plan_expire` ";
    $sql .= "FROM `{db_prefix}listings` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
    $sql .= "WHERE `T1`.`ID` = {$listing_id}";
    $listing_data['Plan_expire'] = $rlDb->getRow($sql, 'Plan_expire');

    $rlSmarty->assign('listing_data', $listing_data);
} else {
    $sError = true;
}
