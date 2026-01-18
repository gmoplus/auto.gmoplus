<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.6.0
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: BOOKING_ORDER.INC.PHP
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
$rlBooking->prepareBookingOrder();

if (isset($_POST['booking_order']) && !$configs['Prepayment']) {
    $rlBooking->bookingOrder();
} else {
    if ($_SESSION['bookingData'] && $configs['Prepayment'] && $step == 'prepayment') {
        $rlBooking->initPrepayment();
    }
}      
