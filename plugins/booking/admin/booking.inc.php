<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.6.0
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: BOOKING.INC.PHP
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

if (isset($_GET['q'])) {
    require '../../../includes/config.inc.php';
    require RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require RL_LIBS . 'system.lib.php';

    $start = (int) $_GET['start'];
    $limit = (int) $_GET['limit'];

    $GLOBALS['reefless']->loadClass('Booking', null, 'booking');

    /* get booking fields */
    if ($_GET['q'] == 'ext') {
        if ($_GET['action'] == 'update') {
            $field = $GLOBALS['rlValid']->xSql($_GET['field']);
            $value = $GLOBALS['rlValid']->xSql(nl2br($_GET['value']));
            $id = (int) $_GET['id'];

            $updateData = array(
                'fields' => array(
                    $field => $value,
                ),
                'where' => array(
                    'ID' => $id,
                ),
            );

            $GLOBALS['rlDb']->updateOne($updateData, 'booking_fields');
            exit;
        }

        $result = $GLOBALS['rlBooking']->getApFieldsList();
    }
    /* get booking requests list */
    elseif ($_GET['q'] == 'ext_stat') {
        if ($_GET['action'] == 'update') {
            $type  = $GLOBALS['rlValid']->xSql($_GET['type']);
            $field = $GLOBALS['rlValid']->xSql($_GET['field']);
            $value = $GLOBALS['rlValid']->xSql(nl2br($_GET['value']));
            $key   = $GLOBALS['rlValid']->xSql($_GET['key']);
            $id    = (int) $_GET['id'];

            $table = 'booking_requests';

            if ($field == 'Booking_status') {
                $field = 'Status';
                $table = 'listings_book';

                if ($value == 'process') {
                    $rlDb->updateOne([
                        'fields' => ['Status' => 'new'],
                        'where'  => ['Book_ID' => $id],
                    ], 'booking_requests');
                } else {
                    $action = $value == 'booked' ? 'accept' : 'refuse';
                    $GLOBALS['rlBooking']->ajaxOwnerResult($id, $action, $lang['not_available']);
                }
            }

            $rlDb->updateOne([
                'fields' => [$field => $value],
                'where'  => ['ID' => $id],
            ], $table);
            exit;
        }

        $result = $GLOBALS['rlBooking']->getApRequestsList();
    }
    /* get available listings list */
    elseif ($_GET['q'] == 'ext_listings') {
        $result = $GLOBALS['rlBooking']->getApAvailableListingsList();
    }

    echo json_encode(['total' => $result['count'], 'data' => $result['data']]);
}
/* ext js action end */
else {
    $GLOBALS['reefless']->loadClass('Booking', null, 'booking');

    // check existing of the Reference number plugin
    $GLOBALS['rlSmarty']->assign(
        'refExists',
        (bool) $rlDb->getOne('Key', "`Key` = 'ref' AND `Status` = 'active'", 'plugins')
    );

    if ($_GET['mode'] === 'listings') {
        $bcAStep[0] = [
            'name'       => $lang['ext_listing_rate_range_manager'],
            'Controller' => 'booking',
            'Vars'       => 'mode=listings'
        ];
    } elseif ($_GET['mode'] == 'booking_colors') {
        $GLOBALS['rlBooking']->apBookingColors();
    } elseif ($_GET['id']) {
        $GLOBALS['rlBooking']->apRequestDetails();
    } elseif ($_GET['mode'] == 'booking_fields') {
        $GLOBALS['rlBooking']->apBookingFields();
    } else {
        $bcAStep[0]['name'] = $lang['booking_requests'];
    }
}
