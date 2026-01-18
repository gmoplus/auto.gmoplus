<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : CLAIMLISTING.INC.PHP
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
if ($_GET['q'] == 'ext') {
    /* system config */
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';
    require_once RL_LIBS . 'smarty' . RL_DS . 'Smarty.class.php';

    $reefless->loadClass('Listings');
    $reefless->loadClass('Smarty');
    $reefless->loadClass('ClaimListing', null, 'claimListing');

    /* data read */
    $start = (int) $_GET['start'];
    $limit = (int) $_GET['limit'];

    // get claim requests
    if (!$_REQUEST['cl_ids']) {
        $data = $rlClaimListing->getClaimRequests($start, $limit);
    } else {
        $data = $rlClaimListing->getClAPListings($_REQUEST['cl_ids'], $start, $limit);
    }

    $output['total'] = $data['count'];
    $output['data']  = $data['data'];

    echo json_encode($output);
}
/* ext js action end */
else {
    $reefless->loadClass('ClaimListing', null, 'claimListing');

    /* get claim info */
    $claim_id = (int) $_GET['item'] ? (int) $_GET['item'] : (int) $_POST['ID'];

    $sql = "SELECT * FROM `" . RL_DBPREFIX . "claim_requests` ";
    $sql .= "WHERE `ID` = {$claim_id} LIMIT 1";
    $claim_info = $rlDb->getRow($sql);

    // adapt phone number
    if ($claim_info['Claim_method'] == 'phone'
        && 'phone' === $rlDb->getOne('Type', "`Key` = '{$config['cl_phone_field']}'", 'listing_fields')
    ) {
        $sql = "SELECT * FROM `" . RL_DBPREFIX . "listing_fields` ";
        $sql .= "WHERE `Key` = '{$config['cl_phone_field']}' LIMIT 1";
        $phone_field = $rlDb->getRow($sql);

        $claim_info['Data'] = $reefless->parsePhone($claim_info['Data'], $phone_field);
    }

    $rlSmarty->assign_by_ref('claim_info', $claim_info);

    if ($_GET['action'] == 'view' && $_GET['item']) {
        $reefless->loadClass('Listings');
        $reefless->loadClass('Account');
        $reefless->loadClass('Message');

        /* register ajax methods */
        $rlXajax->registerFunction(array('contactOwner', $rlMessage, 'ajaxContactOwnerAP'));

        /* add breadcrumb */
        $bcAStep[] = array('name' => $lang['cl_request']);

        /* get claim request info */
        $rlClaimListing->prepareRequestDetails();
    } else {
        /* confirmation of claim request */
        if (isset($_POST['confirm']) && is_array($claim_info)) {
            // change owner of listing
            $rlDb->updateOne(
                array(
                    'fields' => array(
                        'Account_ID' => $claim_info['Account_ID'],
                        'cl_direct'  => '0',
                    ),
                    'where'  => array(
                        'ID' => $claim_info['Listing_ID'],
                    ),
                ),
                'listings'
            );

            // confirm claim request
            $rlDb->updateOne(
                array(
                    'fields' => array(
                        'Status' => 'active',
                    ),
                    'where'  => array(
                        'ID' => $claim_info['ID'],
                    ),
                ),
                'claim_requests'
            );

            // get listing info
            $listing_info = $rlClaimListing->getListingInfo($claim_info['Listing_ID']);

            // sending email with confirmation to new owner
            if ($listing_info['Owner_info']['Mail']) {
                $reefless->loadClass('Mail');
                $mail_tpl         = $rlMail->getEmailTemplate('claim_request_confirmed');
                $mail_tpl['body'] = str_replace(
                    '{username}',
                    $listing_info['Owner_info']['Username'],
                    $mail_tpl['body']
                );

                // get listing title and build url
                $reefless->loadClass('Listings');
                $listing_details = $rlListings->getListing($listing_info['ID'], true);

                $mail_tpl['body'] = str_replace(
                    '{listing_details}',
                    "<a href=\"{$listing_details['listing_link']}\">{$listing_details['listing_title']}</a>",
                    $mail_tpl['body']
                );
                $rlMail->send($mail_tpl, $listing_info['Owner_info']['Mail']);
            }

            /* show notice */
            $reefless->loadClass('Notice');
            $rlNotice->saveNotice($lang['cl_success_confirmed']);
        }
    }
}
