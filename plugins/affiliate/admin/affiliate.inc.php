<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : AFFILIATE.INC.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2022
 *	https://www.flynax.com
 *
 ******************************************************************************/

/* ext js action */
if ($_GET['q'] == 'ext') {
    /* system config */
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    /* banners grid */
    if ($_GET['mode'] == 'banners') {
        /* data update */
        if ($_GET['action'] == 'update') {
            $field = $rlValid->xSql($_GET['field']);
            $value = $rlValid->xSql(nl2br($_GET['value']));
            $id    = (int) $_GET['id'];

            $rlDb->updateOne(array('fields' => array($field => $value), 'where' => array('ID' => $id)), 'aff_banners');
            exit;
        }
        /* get affiliate banners */
        else {
            $reefless->loadClass('Affiliate', null, 'affiliate');
            $result = $rlAffiliate->getAffiliateBanners();
        }
    }
    /* get affiliate payouts */
    elseif ($_GET['mode'] == 'payouts') {
        $reefless->loadClass('Affiliate', null, 'affiliate');
        $result = $rlAffiliate->getApPaymentHistory();
    }
    /* get affiliate events */
    else {
        $reefless->loadClass('Affiliate', null, 'affiliate');
        $result = $rlAffiliate->getAffiliateEvents();
    }

    echo json_encode(['total' => $result['count'], 'data' => $result['data']]);
}
/* ext js action end */
else {
    $reefless->loadClass('Affiliate', null, 'affiliate');

    /* additional bread crumb step */
    if ($_GET['action']) {
        switch ($_GET['action']) {
            case 'add':
                $bcAStep = $lang['aff_add_banner_button'];
                break;

            case 'edit':
                $bcAStep = $lang['aff_edit_banner'];
                break;

            case 'view':
                $bcAStep = $lang['aff_payout_details'];
                break;
        }
    }

    /* add/edit banners */
    if ($_GET['mode'] == 'banners' && in_array($_GET['action'], array('add', 'edit'))) {
        // get all languages
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        if ($_GET['action'] == 'add') {
            /* adding new banner */
            if ($_POST['submit']) {
                $rlAffiliate->addBanner();
            }
        }

        if ($_GET['action'] == 'edit' && (int) $_GET['id']) {
            $banner_info = $rlDb->fetch('*', array('ID' => $_GET['id']), null, null, 'aff_banners', 'row');

            /* edit banner */
            if ($_POST['fromPost']) {
                if ($_SESSION['admin_notice_type'] && !$_POST['removed_banner']) {
                    $_POST['image'] = $banner_info['Image'];
                }

                $rlAffiliate->editBanner();
            }
            /* simulate POST */
            else {
                if ($banner_info) {
                    $_POST['width'] = $banner_info['Width'];
                    $_POST['height'] = $banner_info['Height'];
                    $_POST['image'] = $banner_info['Image'];

                    // get names
                    $names = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'aff_banner_' . $banner_info['Key']), "AND `Status` = 'active'", null, 'lang_keys');

                    foreach ($names as $name_phrase) {
                        $_POST['name'][$name_phrase['Code']] = $name_phrase['Value'];
                    }
                }
            }
        }
    }

    /* view payout details */
    if ($_GET['mode'] == 'payouts' && $_GET['action'] == 'view') {
        $rlAffiliate->getApPayoutDetails();
    }
}
