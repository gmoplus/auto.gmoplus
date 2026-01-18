<?php

/* ext js action */
if ($_GET['q'] == 'ext') {
    // system config
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    /* date update */
    if ($_GET['action'] == 'update') {
        $reefless->loadClass('Actions');

        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id = (int) $_GET['id'];

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where' => array(
                'ID' => $id,
            ),
        );

        $rlActions->updateOne($updateData, 'coupon_code');
        exit;
    }

    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];

    $sFields = array(
        'code' => '`T1`.`Code`',
        'type' => "`T1`.`Type`",
        'account_or_type' => '`T1`.`Account_or_type`',
        'account_type' => '',
        'username' => '`T1`.`Username`',
        'status' => '`T1`.`Status`',
    );

    $sql = "SELECT SQL_CALC_FOUND_ROWS *, `T1`.`ID` AS `Key`, ";
    $sql .= "(SELECT COUNT(`T2`.`ID`) FROM `{db_prefix}coupon_users` AS `T2` WHERE `T2`.`Coupon_ID` = `T1`.`ID`) AS `count_uses`, ";
    $sql .= "IF(UNIX_TIMESTAMP(`T1`.`Date_to`) < UNIX_TIMESTAMP(NOW()), 0, `T1`.`Date_to`) AS `Date_to`, ";
    $sql .= "IF(UNIX_TIMESTAMP(`T1`.`Date_from`) > UNIX_TIMESTAMP(NOW()), 0, `T1`.`Date_from`) AS `Date_from` ";
    $sql .= "FROM `{db_prefix}coupon_code` AS `T1` ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' ";

    if ($_GET['search']) {
        foreach ($sFields as $sfKey => $sfField) {
            $value = $rlValid->xSql($_GET[$sfKey]);

            if (empty($value)) {
                continue;
            }

            switch ($sfKey) {
                case 'code' :
                case 'type' :
                case 'account_or_type' :
                case 'status' :
                case 'username' :
                    $sql .= "AND {$sfField} = '{$value}' ";
                    break;

                case 'account_type' :
                    $sql .= "AND  FIND_IN_SET('{$value}', `T1`.`Account_type`) > 0 ";
                    break;
            }
        }
    }

    $sql .= "LIMIT {$start}, {$limit}";

    $data = $rlDb->getAll($sql);

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $lang[$data[$key]['Status']];
    }

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
}
/* ext js action end */

else {
    $reefless->loadClass('CouponCode', null, 'coupon');
    $reefless->loadClass('Plan');
    $reefless->loadClass('Account');

    // additional bread crumb step
    if ($_GET['action']) {
        $bcAStep = $_GET['action'] == 'add' ? $lang['add_coupon'] : $lang['edit_coupon'];
    }

    $account_types = $rlAccount->getAccountTypes();
    $rlSmarty->assign_by_ref('account_types', $account_types);

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        if ($GLOBALS['config']['membership_module']) {
            $reefless->loadClass('MembershipPlansAdmin', 'admin');
        }
        if ($rlCouponCode->isPluginInstalled('banners')) {
            $reefless->loadClass('Banners', null, 'banners');
        }
        if ($rlCouponCode->isPluginInstalled('payAsYouGoCredits')) {
            $reefless->loadClass('Credits', null, 'payAsYouGoCredits');
        }
        if ($rlCouponCode->isPluginInstalled('monetize')) {
            $reefless->loadClass('Monetize', null, 'monetize');

            if (!method_exists($rlMonetize, 'getPlans')) {
                $reefless->loadClass('BumpUp', null, 'monetize');
                $reefless->loadClass('Highlight', null, 'monetize');
            }
        }

        // prepare object plans by group
        $listing_plans = $rlPlan->getPlans();
        $services = array(
            'listing' => array(
                'name' => $lang['coupon_listing_plans'],
                'title' => $lang['coupon_packages'],
                'Key' => 'listing',
                'items' => $listing_plans,
            ),
        );

        if ($GLOBALS['config']['membership_module']) {
            $membership_plans = $rlMembershipPlansAdmin->getPlans();
            if ($membership_plans) {
                $services['membership'] = array(
                    'name' => $lang['coupon_membership_plans'],
                    'title' => $lang['coupon_plans'],
                    'Key' => 'membership',
                    'items' => $membership_plans,
                    'service' => 'membership',
                );
            }
        }
        if ($rlCouponCode->isPluginInstalled('banners')) {
            $banner_plans = $rlBanners->getBannerPlans();
            if ($banner_plans) {
                $services['banner'] = array(
                    'name' => $lang['coupon_banner_plans'],
                    'title' => $lang['coupon_plans'],
                    'Key' => 'banner',
                    'items' => $banner_plans,
                    'service' => 'banner',
                );
            }
        }
        if ($rlCouponCode->isPluginInstalled('payAsYouGoCredits')) {
            $credit_packages = $rlCredits->get();
            if ($credit_packages) {
                foreach ($credit_packages as $cpKey => $cpVal) {
                    if (empty($credit_packages[$cpKey]['name'])) {
                        $credit_packages[$cpKey]['name'] = $lang['credits_manager+name+credit_package_' . $cpVal['ID']];
                    }
                }
                $services['credit_packages'] = array(
                    'name' => $lang['coupon_credit_packages'],
                    'title' => $lang['coupon_packages'],
                    'Key' => 'credits',
                    'items' => $credit_packages,
                    'service' => 'credits',
                );
            }
        }

        if ($rlCouponCode->isPluginInstalled('monetize')) {
            if (method_exists($rlMonetize, 'getPlans')) {
                $bumpup_packages = $rlMonetize->getPlans(false, false, 'active', 'bump_up');
                $highlight_packages = $rlMonetize->getPlans(false, false, 'active', 'highlight');
            } else {
                $bumpup_packages = $rlBumpUp->getPlans();
                $highlight_packages = $rlHighlight->getPlans();
            }

            if ($bumpup_packages) {
                foreach ($bumpup_packages as $cpKey => $cpVal) {
                    if (empty($bumpup_packages[$cpKey]['name'])) {
                        $bumpup_packages[$cpKey]['name'] = $lang['bump_up_plan+name+' . $cpVal['ID']];
                    }
                }
                $services['bumpup_packages'] = array(
                    'name' => $lang['coupon_monetize_bumpup_packages'],
                    'title' => $lang['coupon_packages'],
                    'Key' => 'bumpup',
                    'items' => $bumpup_packages,
                    'service' => 'bumpup',
                );
            }

            if ($highlight_packages) {
                foreach ($highlight_packages as $cpKey => $cpVal) {
                    if (empty($highlight_packages[$cpKey]['name'])) {
                        $highlight_packages[$cpKey]['name'] = $lang['highlight_plan+name+' . $cpVal['ID']];
                    }
                }
                $services['highlight_packages'] = array(
                    'name' => $lang['coupon_monetize_highlight_packages'],
                    'title' => $lang['coupon_packages'],
                    'Key' => 'highlight',
                    'items' => $highlight_packages,
                    'service' => 'highlight',
                );
            }
        }

        $rlSmarty->assign_by_ref('services', $services);
        $rlSmarty->assign_by_ref('isShoppingInstalled', $rlCouponCode->isPluginInstalled('shoppingCart'));
        $rlSmarty->assign_by_ref('isBookingInstalled', $rlCouponCode->isPluginInstalled('booking'));

        // get coupon info
        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $id = (int) $_GET['coupon'];

            // get coupon info
            $coupon_info = $rlDb->fetch('*', array('ID' => $id), "AND `Status` <> 'trash'", 1, 'coupon_code', 'row');

            $_POST['account_or_type'] = $coupon_info['Account_or_type'];
            $_POST['account_type'] = explode(',', $coupon_info['Account_type']);
            $_POST['username'] = $coupon_info['Username'];
            $_POST['used_date'] = $coupon_info['Used_date'];
            $_POST['date_from'] = $coupon_info['Date_from'];
            $_POST['date_to'] = $coupon_info['Date_to'];
            $_POST['type'] = $coupon_info['Type'];
            $_POST['using_limit'] = $coupon_info['Using_limit'];
            $_POST['coupon_discount'] = $coupon_info['Discount'];
            $_POST['generate_coupon_code'] = $coupon_info['Code'];
            $_POST['show_on_all']['listing'] = $coupon_info['Sticky'];
            $_POST['show_on_all']['membership'] = $coupon_info['StickyMP'];
            $_POST['show_on_all']['banner'] = $coupon_info['StickyBanners'];
            $_POST['show_on_all']['credits'] = $coupon_info['StickyPAYGC'];
            $_POST['show_on_all']['bumpup'] = $coupon_info['StickyBumpup'];
            $_POST['show_on_all']['highlight'] = $coupon_info['StickyHighlight'];
            $_POST['service']['listing'] = explode(',', $coupon_info['Plan_ID']);
            $_POST['service']['membership'] = explode(',', $coupon_info['MPPlan_ID']);
            $_POST['service']['banner'] = explode(',', $coupon_info['BannersPlan_ID']);
            $_POST['service']['credits'] = explode(',', $coupon_info['PAYGCPlan_ID']);
            $_POST['service']['bumpup'] = explode(',', $coupon_info['BumpupPlan_ID']);
            $_POST['service']['highlight'] = explode(',', $coupon_info['HighlightPlan_ID']);
            $_POST['services'] = explode(',', $coupon_info['Services']);
            $_POST['shopping'] = $coupon_info['Shopping'];
            $_POST['booking'] = $coupon_info['Booking'];
        }
        if (isset($_POST['submit'])) {
            $errors = array();

            // check discount
            $coupon_code = $_POST['generate_coupon_code'];

            if (empty($coupon_code)) {
                $errors[] = str_replace('{field}', "<b>{$lang['coupon_code']}</b>", $lang['notice_field_empty']);
                $error_fields[] = 'generate_coupon_code';
            } elseif (strlen($coupon_code) < 2) {
                $errors[] = $lang['coupon_code_limit'];
                $error_fields[] = 'generate_coupon_code';
            }

            // check date
            $used_date = $_POST['used_date'];
            if ($used_date == 'yes') {
                $date_from = $_POST['date_from'];
                $date_to = $_POST['date_to'];
                if (empty($date_from) || empty($date_to)) {
                    $errors[] = str_replace('{field}', "<b>{$lang['available_coupone']}</b>", $lang['notice_field_empty']);
                    $error_fields[] = 'date_from';
                    $error_fields[] = 'date_to';
                }
            }

            if ($_POST['using_limit'] == '') {
                $errors[] = str_replace('{field}', "<b>{$lang['using_limit']}</b>", $lang['notice_field_empty']);
                $error_fields[] = 'using_limit';
            }

            /* check discount */
            $discount = $_POST['coupon_discount'];
            if (empty($discount)) {
                $errors[] = str_replace('{field}', "<b>{$lang['coupon_discount']}</b>", $lang['notice_field_empty']);
                $error_fields[] = 'coupon_discount';
            }

            /* check account or account type */
            $account_or_type = $_POST['account_or_type'];
            if ($account_or_type == 'type') {
                $account_type = $_POST['account_type'];
                if (empty($account_type)) {
                    $errors[] = str_replace('{field}', "<b>{$lang['account_type']}</b>", $lang['notice_field_empty']);
                    $error_fields[] = 'account_type';
                }
            } elseif ($account_or_type == 'account') {
                $username = $rlValid->xSql($_POST['username']);

                if ($username) {
                    $isset_username = $rlDb->getOne('Username', "`Username` = '{$username}'", 'accounts');

                    if (!$isset_username) {
                        $errors[] = $lang['coupon_not_found'];
                        $error_fields[] = 'username';
                    }
                } else {
                    $errors[] = str_replace('{field}', "<b>{$lang['username']}</b>", $lang['notice_field_empty']);
                    $error_fields[] = 'username';
                }
            }

            // check services
            if (!$_POST['services'] || !$_POST['service']) {
                $errors[] = $lang['coupon_service_empty'];
            }

            if ($_POST['service']) {
                foreach ($services as $service) {
                    if (!$_POST['service'][$service['Key']]
                        && !$_POST['show_on_all'][$service['Key']]
                        && in_array($service['Key'], (array) $_POST['services'], true)
                    ) {
                        $errors[] = str_replace('{service}', "<b>{$service['name']}</b>", $lang['service_not_selected']);
                    }
                }
            }
            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                if ($_GET['action'] == 'add') {
                    //insert data
                    $insert_data = array(
                        'Code' => $coupon_code,
                        'Plan_ID' => implode(',', (array) $_POST['service']['listing']),
                        'MPPlan_ID' => implode(',', (array) $_POST['service']['membership']),
                        'BannersPlan_ID' => implode(',', (array) $_POST['service']['banner']),
                        'PAYGCPlan_ID' => implode(',', (array) $_POST['service']['credits']),
                        'BumpupPlan_ID' => implode(',', (array) $_POST['service']['bumpup']),
                        'HighlightPlan_ID' => implode(',', (array) $_POST['service']['highlight']),
                        'Sticky' => $_POST['show_on_all']['listing'] ? 1 : 0,
                        'StickyMP' => $_POST['show_on_all']['membership'] ? 1 : 0,
                        'StickyBanners' => $_POST['show_on_all']['banner'] ? 1 : 0,
                        'StickyPAYGC' => $_POST['show_on_all']['credits'] ? 1 : 0,
                        'StickyBumpup' => $_POST['show_on_all']['bumpup'] ? 1 : 0,
                        'StickyHighlight' => $_POST['show_on_all']['highlight'] ? 1 : 0,
                        'Services' => implode(',', (array) $_POST['services']),
                        'Used_date' => $used_date,
                        'Date_from' => $date_from ? $date_from : '0000-00-00',
                        'Date_to' => $date_to ? $date_to : '0000-00-00',
                        'Using_limit' => $_POST['using_limit'],
                        'Discount' => $discount,
                        'Type' => $_POST['type'],
                        'Account_or_type' => $account_or_type,
                        'Account_type' => !empty($account_type) ? implode(',', $account_type) : '',
                        'Username' => $username,
                        'Status' => $_POST['status'],
                        'Date_release' => 'NOW()',
                        'Shopping' => $_POST['shopping'] ? 1 : 0,
                        'Booking' => $_POST['booking'] ? 1 : 0,
                    );

                    if ($action = $rlActions->insertOne($insert_data, 'coupon_code')) {
                        $message = $lang['coupon_added'];
                        $aUrl = array("controller" => $controller);
                    }
                } elseif ($_GET['action'] == 'edit') {
                    $id = (int) $_GET['coupon'];
                    $update_data = array(
                        'fields' => array(
                            'Code' => $coupon_code,
                            'Plan_ID' => implode(',', (array) $_POST['service']['listing']),
                            'MPPlan_ID' => implode(',', (array) $_POST['service']['membership']),
                            'BannersPlan_ID' => implode(',', (array) $_POST['service']['banner']),
                            'PAYGCPlan_ID' => implode(',', (array) $_POST['service']['credits']),
                            'BumpupPlan_ID' => implode(',', (array) $_POST['service']['bumpup']),
                            'HighlightPlan_ID' => implode(',', (array) $_POST['service']['highlight']),
                            'Sticky' => $_POST['show_on_all']['listing'] ? 1 : 0,
                            'StickyMP' => $_POST['show_on_all']['membership'] ? 1 : 0,
                            'StickyBanners' => $_POST['show_on_all']['banner'] ? 1 : 0,
                            'StickyPAYGC' => $_POST['show_on_all']['credits'] ? 1 : 0,
                            'StickyBumpup' => $_POST['show_on_all']['bumpup'] ? 1 : 0,
                            'StickyHighlight' => $_POST['show_on_all']['highlight'] ? 1 : 0,
                            'Services' => implode(',', (array) $_POST['services']),
                            'Used_date' => $used_date,
                            'Date_from' => $date_from ? $date_from : '0000-00-00',
                            'Date_to' => $date_to ? $date_to : '0000-00-00',
                            'Using_limit' => $_POST['using_limit'],
                            'Discount' => $discount,
                            'Type' => $_POST['type'],
                            'Account_or_type' => $_POST['account_or_type'],
                            'Account_type' => !empty($account_type) ? implode(',', $account_type) : '',
                            'Username' => $username ? $username : '',
                            'Status' => $_POST['status'],
                            'Date_release' => 'NOW()',
                            'Shopping' => $_POST['shopping'] ? 1 : 0,
                            'Booking' => $_POST['booking'] ? 1 : 0,
                        ),
                        'where' => array('ID' => $id),
                    );
                    $action = $GLOBALS['rlActions']->updateOne($update_data, 'coupon_code');
                    $message = $lang['coupon_edited'];
                    $aUrl = array("controller" => $controller);
                }
                if ($action) {
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                }
            }
        }
    }

    /* register ajax methods */
    $rlXajax->registerFunction(array('deleteCoupon', $rlCouponCode, 'ajaxDeleteCoupon'));
}
