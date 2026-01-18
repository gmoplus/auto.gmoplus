<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLCOUPONCODE.CLASS.PHP
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
 *  Flynax Classifieds Software 2025 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

class rlCouponCode
{
    /**
     * available plugins which using in the plugin
     *
     * @var array
     */
    protected $plugins;

    /**
     * Gateways processed by JS
     *
     * @since 2.5.0
     *
     * @var array
     */
    protected $gatewaysProcessedJS = ['2co', 'stripe', 'pesaPal', 'paypalCheckout'];

    /**
     * edit price
     *
     * @param $coupon - coupon code
     * @param $plan price - plan price
     *
     * @return prise with discount
     **/
    public function editPrice($price = 0, $coupon = '')
    {
        if (!$coupon) {
            return $price;
        }

        $sql = "AND ( `Used_date` = 'no' OR UNIX_TIMESTAMP(`Date_from`) < UNIX_TIMESTAMP(NOW()) ";
        $sql .= "AND UNIX_TIMESTAMP(`Date_to`) > UNIX_TIMESTAMP(NOW()))";

        $where = array('Code' => $coupon, 'Status' => 'active');
        $coupon_info = $GLOBALS['rlDb']->fetch('*', $where, $sql, 1, 'coupon_code', 'row');

        if ($coupon_info) {
            if ($price > 0) {
                if ($coupon_info['Type'] == 'cost') {
                    $price = $price - $coupon_info['Discount'];
                } elseif ($coupon_info['Type'] == 'percent') {
                    $price = $price - (($price * $coupon_info['Discount']) / 100);
                }
            }

            if ($price < 0) {
                $price = 0;
            }

            $price = number_format($price, 2, '.', '');
        }

        return $price;
    }

    /**
     * delete coupon code
     *
     * @package xAjax
     *
     * @param int $coupon_id
     **/
    public function ajaxDeleteCoupon($coupon_id = false)
    {
        global $_response;

        $coupon_id = (int) $coupon_id;

        // check admin session expire
        if ($GLOBALS['reefless']->checkSessionExpire() === false) {
            $_response->redirect(RL_URL_HOME . ADMIN . '/index.php?action=session_expired');
            return $_response;
        }

        if (!$coupon_id) {
            return $_response;
        }

        $sql = "DELETE FROM `" . RL_DBPREFIX . "coupon_code` WHERE `ID` = {$coupon_id} LIMIT 1";
        $GLOBALS['rlDb']->query($sql);

        $_response->script("
            CouponCodeGrid.reload();
            printMessage('notice', '{$GLOBALS['lang']['coupon_deleted']}')
        ");
        return $_response;
    }

    /**
     * attach coupon box
     */
    public function attachCouponBox()
    {
        global $rlSmarty, $rlPayment, $page_info;

        if ($rlPayment->getOption('coupon')
            || ($rlPayment->getGateway() == 'bankWireTransfer' && $_SESSION['bwt_completed'])
        ) {
            return;
        }

        $service = $rlPayment->getOption('service');
        $item_id = $rlPayment->getOption('plan_id') ?: $rlPayment->getOption('item_id');

        /**
         * @since 2.4.0
         */
        $GLOBALS['rlHook']->load('couponAttachCouponBox', $service, $item_id);

        $rlSmarty->assign('item_id', $item_id);
        $rlSmarty->assign('service', $service);

        $content = $rlSmarty->fetch(RL_PLUGINS . 'coupon/coupon.block.tpl');

        return $content;
    }

    /**
     * handle item price if used coupon code
     *
     * @param bool $jsHandler
     */
    public function checkPayment($jsHandler = false)
    {
        global $rlValid, $rlPayment, $rlDb, $config;

        if ($_SESSION['coupon_code'] && (int) $rlPayment->getOption('total') > 0) {
            $couponInfo = $rlDb->fetch('*', array('Code' => $_SESSION['coupon_code']), null, 1, 'coupon_code', 'row');
            $priceOriginal = $rlPayment->getOption('total');
            $price = $this->editPrice($rlPayment->getOption('total'), $_SESSION['coupon_code']);
            $gateway = $rlValid->xSql($_REQUEST['gateway']);

            $insert = array(
                'Coupon_ID' => $couponInfo['ID'],
                'Account_ID' => $rlPayment->getOption('account_id'),
                'Plan_ID' => $rlPayment->getOption('plan_id'),
            );

            $rlDb->insertOne($insert, 'coupon_users');

            $transaction_id = $rlPayment->getTransactionID();

            if ($price == 0) {
                $data = array(
                    'plan_id' => $rlPayment->getOption('plan_id'),
                    'item_id' => $rlPayment->getOption('item_id'),
                    'account_id' => $rlPayment->getOption('account_id'),
                    'total' => $price,
                    'txn_id' => $transaction_id,
                    'txn_gateway' => 'coupon (' . $_SESSION['coupon_code'] . ')',
                    'params' => $rlPayment->getOption('params'),
                );
                $plugin = $rlPayment->getOption('plugin') ? $rlPayment->getOption('plugin') : false;

                $rlPayment->complete(
                    $data,
                    $rlPayment->getOption('callback_class'),
                    $rlPayment->getOption('callback_method'),
                    $plugin
                );

                unset($_SESSION['coupon_code']);
            } else {
                if (!$rlPayment->getOption('coupon')
                    || $rlPayment->getOption('coupon') != $_SESSION['coupon_code']
                ) {
                    $rlPayment->setOption('total', $price);
                    $rlPayment->setOption('coupon', $_SESSION['coupon_code']);
                }
            }

            $shortInfo = array(
                'ID' => $couponInfo['ID'],
                'Code' => $couponInfo['Code'],
                'Discount' => $couponInfo['Type'] == 'percent' ? $couponInfo['Discount'] .'%' :  $couponInfo['Discount'],
                'Price_discount' => $price,
                'Price' => $priceOriginal,
            );

            foreach ($shortInfo as $key => $value) {
                if (in_array($key, ['Price_discount', 'Price', 'Discount'])) {
                    if ($key == 'Discount' && $couponInfo['Type'] == 'percent') {
                        continue;
                    }
                    $shortInfo[$key] = $config['system_currency_position'] == 'before'
                    ? $config['system_currency'] . $value
                    : $value . ' ' . $config['system_currency'];
                }
            }

            $update = array(
                'fields' => array(
                    'Total' => $price,
                    'Gateway' => $gateway,
                    'Coupon_ID' => $couponInfo['ID'],
                    'Coupon_data' => serialize($shortInfo)
                ),
                'where' => array('ID' => $transaction_id),
            );

            $rlDb->updateOne($update, 'transactions', ['Coupon_data']);

            // redirect to related controller
            if ($price == 0 && !$jsHandler) {
                $redirect = $rlPayment->getOption('success_url');
                $GLOBALS['reefless']->redirect(null, $redirect);
                exit;
            }

            unset($_SESSION['coupon_code']);
        }
    }

    /**
     * @hook ajaxRequest
     *
     * @since 2.3.0
     */
    public function hookAjaxRequest(&$out, $request_mode, $request_item, $request_lang)
    {
        global $lang, $config, $account_info, $rlValid, $rlDb, $rlSmarty, $rlPayment;

        $GLOBALS['reefless']->loadClass('Payment');

        if ($request_mode == 'checkCouponCode') {
            if (!$account_info && isset($_SESSION['account'])) {
                $account_info = $_SESSION['account'];
            }

            if (!$account_info && $_SESSION['registration']['account_id']) {
                $account_id = (int) $_SESSION['registration']['account_id'];
                $account_info = $rlDb->fetch('*', array('ID' => $account_id), null, null, 'accounts', 'row');
            }

            $lang = $GLOBALS['rlLang']->getLangBySide('frontEnd', RL_LANG_CODE);
            $coupon = $rlValid->xSql($_REQUEST['code']);
            $item_id = (int) $_REQUEST['item_id'];
            $service = $rlValid->xSql($_REQUEST['service']);
            $service = $service == 'bump_up' ? 'bumpup' : $service;
            $is_cancel = (bool) $_REQUEST['cancel'];
            $gateway = $rlValid->xSql($_REQUEST['gateway']);

            if (!$gateway) {
                $out = array(
                    'status' => 'error',
                    'data' => array('message' => $lang['notice_payment_gateway_does_not_chose']),
                );
                return;
            }

            if ($is_cancel) {
                unset($_SESSION['coupon_code']);

                $out = array(
                    'status' => 'OK',
                    'data' => array('content' => ''),
                );
                return;
            }

            $sql = "SELECT *, UNIX_TIMESTAMP(`Date_from`) AS `Date_from`, ";
            $sql .= "UNIX_TIMESTAMP(`Date_to`) AS `Date_to` ";
            $sql .= "FROM `" . RL_DBPREFIX . "coupon_code` ";
            $sql .= "WHERE `Status` = 'active' AND `Code` = '{$coupon}' LIMIT 1";
            $coupon_info = $rlDb->getRow($sql);

            if ($coupon_info) {
                $checkup = $rlDb->fetch(
                    array('Coupon_ID', 'Account_ID'),
                    array('Coupon_ID' => $coupon_info['ID'], 'Account_ID' => $account_info['ID']),
                    null,
                    null,
                    'coupon_users'
                );

                switch ($service) {
                    case 'featured':
                    case 'package':
                    case 'listing':
                        $table = 'listing_plans';
                        break;

                    case 'banners':
                        $table = 'banner_plans';
                        break;

                    case 'membership':
                        $table = 'membership_plans';
                        break;

                    case 'credits':
                        $table = 'credits_manager';
                        break;

                    case 'bumpup':
                    case 'highlight':
                        $table = 'monetize_plans';
                        $sql_s = "AND `Type` = '{$service}' ";
                        break;

                    case 'shopping':
                    case 'auction':
                    case 'booking':
                        $price = $rlPayment->getOption('total');
                        break;
                }

                if (!in_array($service, ['shopping', 'auction', 'booking'])) {
                    $price = $rlDb->getOne('Price', "`Status` = 'active' AND `ID` = {$item_id} {$sql_s}", $table);
                }

                if (($price > 0 && $coupon_info['Using_limit'] > count($checkup))
                    || $coupon_info['Using_limit'] == '0'
                ) {
                    if (($coupon_info['Account_or_type'] == 'type'
                        && !in_array($account_info['Type'], explode(',', $coupon_info['Account_type']))
                    )
                        || ($coupon_info['Account_or_type'] == 'account'
                            && $account_info['Username'] != $coupon_info['Username']
                        )
                    ) {
                        $error = $lang['coupon_not_account'];
                    }
                    switch ($service) {
                        case 'featured':
                        case 'package':
                        case 'listing':
                            if ($coupon_info['Sticky'] == 0
                                && !in_array($item_id, explode(',', $coupon_info['Plan_ID']))
                            ) {
                                $error = $lang['coupon_not_plan'];
                            }
                            break;

                        case 'banners':
                            if ($coupon_info['StickyBanners'] == 0
                                && !in_array($item_id, explode(',', $coupon_info['BannersPlan_ID']))
                            ) {
                                $error = $lang['coupon_not_plan'];
                            }
                            break;

                        case 'membership':
                            if ($coupon_info['StickyMP'] == 0
                                && !in_array($item_id, explode(',', $coupon_info['MPPlan_ID']))
                            ) {
                                $error = $lang['coupon_not_plan'];
                            }
                            break;

                        case 'credits':
                            if ($coupon_info['StickyPAYGC'] == 0
                                && !in_array($item_id, explode(',', $coupon_info['PAYGCPlan_ID']))
                            ) {
                                $error = $lang['coupon_not_package'];
                            }
                            break;

                        case 'bumpup':
                            if ($coupon_info['StickyBumpup'] == 0
                                && !in_array($item_id, explode(',', $coupon_info['BumpupPlan_ID']))
                            ) {
                                $error = $lang['coupon_not_package'];
                            }
                            break;

                        case 'highlight':
                            if ($coupon_info['StickyHighlight'] == 0
                                && !in_array($item_id, explode(',', $coupon_info['HighlightPlan_ID']))
                            ) {
                                $error = $lang['coupon_not_package'];
                            }
                            break;

                        case 'shopping':
                        case 'auction':
                            if ($coupon_info['Shopping'] == 0) {
                                $error = $lang['coupon_not_package'];
                            }
                            break;

                        case 'booking':
                            if ($coupon_info['Booking'] == 0) {
                                $error = $lang['coupon_not_package'];
                            }
                            break;
                    }

                    if ($coupon_info['Used_date'] == 'yes'
                        && ($coupon_info['Date_from'] >= time() || time() >= $coupon_info['Date_to'])
                    ) {
                        $error = $lang['coupon_expired'];
                    }
                } elseif ($coupon_info['Using_limit'] <= count($checkup)
                    && $coupon_info['Using_limit'] != '0'
                ) {
                    $error = $lang['your_coupon_limit_is_over'];
                } else {
                    $error = $lang['coupon_code_is_incorrect'];
                }
            } else {
                $error = $lang['coupon_not_found'];
            }

            if ($error) {
                $out = array(
                    'status' => 'ERROR',
                    'data' => array('message' => $error),
                );
            } else {
                if ($coupon_info['Type'] == 'cost') {
                    $total = $price - $coupon_info['Discount'];
                    $discount = $coupon_info['Discount'];
                } elseif ($coupon_info['Type'] == 'percent') {
                    $total = $price - (($price / 100) * $coupon_info['Discount']);
                    $discount = $coupon_info['Discount'] . '%';
                }

                $total = number_format($total < 0 ? 0 : $total, 2, '.', '');

                $coupon_price_info = array(
                    'price' => $price,
                    'discount' => $discount,
                    'total' => $total,
                );

                $_SESSION['coupon_code'] = $coupon;

                $rlSmarty->assign_by_ref('coupon_price_info', $coupon_price_info);
                $rlSmarty->assign_by_ref('coupon_code', $coupon);
                $rlSmarty->assign_by_ref('lang', $lang);

                $gatewayInfo = $rlDb->fetch('*', array('Key' => $gateway), null, 1, 'payment_gateways', 'row');

                $html = $rlSmarty->fetch(RL_PLUGINS . 'coupon/coupon_price_info.tpl');
                $out = array(
                    'status' => 'OK',
                    'total' => (float) $total,
                    'needRedirect' => in_array($gateway, $this->gatewaysProcessedJS) && $gatewayInfo['Form_type'] == 'custom' ? true : false,
                    'data' => array('content' => $html),
                );
            }
        }

        if ($request_mode == 'completeCouponCode') {
            if (!$_SESSION['coupon_code'] || (int) $rlPayment->getOption('total') == 0) {
                $out = array(
                    'status' => 'ERROR',
                    'message' => $lang['coupon_not_applied'],
                );
                return;
            }

            $couponInfo = $rlDb->fetch('*', array('Code' => $_SESSION['coupon_code']), null, 1, 'coupon_code', 'row');
            $gateway = $rlValid->xSql($_REQUEST['gateway']);

            $insert = array(
                'Coupon_ID' => $couponInfo['ID'],
                'Account_ID' => $rlPayment->getOption('account_id'),
                'Plan_ID' => $rlPayment->getOption('plan_id'),
            );

            $rlDb->insertOne($insert, 'coupon_users');

            $transaction_id = $rlPayment->getTransactionID();

            $data = array(
                'plan_id' => $rlPayment->getOption('plan_id'),
                'item_id' => $rlPayment->getOption('item_id'),
                'account_id' => $rlPayment->getOption('account_id'),
                'total' => 0,
                'txn_id' => $transaction_id,
                'txn_gateway' => 'coupon (' . $_SESSION['coupon_code'] . ')',
                'params' => $rlPayment->getOption('params'),
            );
            $plugin = $rlPayment->getOption('plugin') ? $rlPayment->getOption('plugin') : false;

            $rlPayment->complete(
                $data,
                $rlPayment->getOption('callback_class'),
                $rlPayment->getOption('callback_method'),
                $plugin
            );

            $shortInfo = array(
                'ID' => $couponInfo['ID'],
                'Code' => $couponInfo['Code'],
                'Discount' => $couponInfo['Type'] == 'percent' ? $couponInfo['Discount'] .'%' :  $couponInfo['Discount'],
                'Price_discount' => 0,
                'Price' => $rlPayment->getOption('total'),
            );

            foreach ($shortInfo as $key => $value) {
                if (in_array($key, ['Price_discount', 'Price', 'Discount'])) {
                    if ($key == 'Discount' && $couponInfo['Type'] == 'percent') {
                        continue;
                    }
                    $shortInfo[$key] = $config['system_currency_position'] == 'before'
                    ? $config['system_currency'] . $value
                    : $value . ' ' . $config['system_currency'];
                }
            }
            $update = array(
                'fields' => array(
                    'Gateway' => $gateway,
                    'Coupon_ID' => $couponInfo['ID'],
                    'Coupon_data' => serialize($shortInfo)
                ),
                'where' => array('ID' => $transaction_id),
            );

            $rlDb->updateOne($update, 'transactions', ['Coupon_data']);

            unset($_SESSION['coupon_code']);

            $out = array(
                'status' => 'OK',
                'url' => $rlPayment->getOption('success_url'),
            );
        }
    }

    /**
     * @hook phpGetPaymentGatewaysAfter
     *
     * @since 2.3.0
     */
    public function hookPhpGetPaymentGatewaysAfter(&$content)
    {
        $services = ['invoice'];

        if ($GLOBALS['config']['shc_method'] == 'multi') {
            array_push($services, 'shopping', 'auction');
        }

        if (!in_array($GLOBALS['rlPayment']->getOption('service'), $services)) {
            $content .= $this->attachCouponBox();
        }
    }

    /**
     * @hook preCheckoutPayment
     *
     * @since 2.3.0
     */
    public function hookPreCheckoutPayment()
    {
        $this->checkPayment();
    }

    /**
     * check if plugin installed
     *
     * @param string $key
     * @return bool
     */
    public function isPluginInstalled($key = '')
    {
        if (!$key) {
            return false;
        }

        if (isset($this->plugins[$key])) {
            if ($this->plugins[$key]) {
                return true;
            } else {
                return false;
            }
        }

        $sql = "SELECT * FROM `" . RL_DBPREFIX . "plugins` ";
        $sql .= "WHERE `Key` = '{$key}' AND `Install` = '1' AND `Status` = 'active' LIMIT 1";
        $plugin = $GLOBALS['rlDb']->getRow($sql);

        $this->plugins[$key] = $plugin['ID'] ? true : false;

        return $this->plugins[$key];
    }

    /**
     * @hook loadPaymentForm
     *
     * @since 2.5.0
     */
    public function hookLoadPaymentForm($gateway_info)
    {
        if (in_array($gateway_info['Key'], $this->gatewaysProcessedJS) && $gateway_info['Form_type'] == 'custom') {
            $this->checkPayment(true);
        }
    }

    /**
     * @hook apExtTransactionItem
     *
     * @since 2.5.0
     */
    public function hookApExtTransactionItem(&$dataItem, $key, $value)
    {
        global $rlSmarty;

        if (!empty($value['Coupon_data'])) {
            $couponInfo = $value['Coupon_data'] ? unserialize($value['Coupon_data']) : [];

            if ($couponInfo) {
                $couponInfo['Price_discount'] = $value['Total'];
            }
            $rlSmarty->assign_by_ref('couponInfo', $couponInfo);

            $tpl = RL_PLUGINS . 'coupon/admin/info.tpl';
            $couponInfoHtml = $rlSmarty->fetch($tpl, null, null, false);

            $dataItem['Total'] .= "<img class=info style='float: right; margin-right: 5px;' ext:qtip='{$couponInfoHtml}' src='" . RL_URL_HOME . ADMIN . "/img/blank.gif' />";
        }
    }

    /**
     * @hook apExtHeader
     *
     * @since 2.5.0
     */
    public function hookApExtHeader()
    {
        global $rlSmarty, $lang;

        if (!is_object($GLOBALS['rlSmarty'])) {
            require_once RL_LIBS . 'smarty' . RL_DS . 'Smarty.class.php';
            $GLOBALS['reefless']->loadClass('Smarty');
        }

        $rlSmarty->assign_by_ref('lang', $lang);
    }

    /**
     * @hook phpPaymentHistoryBottom
     *
     * @since 2.5.0
     */
    public function hookPhpPaymentHistoryBottom()
    {
        global $transactions, $lang;

        if (!$transactions) {
            return;
        }

        foreach ($transactions as $key => $value) {
            if (!empty($value['Coupon_data'])) {
                $couponInfo = $value['Coupon_data'] ? unserialize($value['Coupon_data']) : [];

                if ($couponInfo) {
                    $couponInfo['Price_discount'] = $value['Total'];
                }

                $info = "{$lang['coupon_code']}: {$couponInfo['Code']}" . PHP_EOL;
                $info .= "{$lang['price']}: {$couponInfo['Price']}" . PHP_EOL;
                $info .= "{$lang['coupon_discount']}: {$couponInfo['Discount']}" . PHP_EOL;
                $info .= "{$lang['coupon_price_discount']}: {$couponInfo['Price_discount']}";
                $info = ' <img class="qtip" alt="" title="' . $info . '" src="' . RL_TPL_BASE . 'img/blank.gif" />';

                $transactions[$key]['Total'] = '<span>' . $value['Total'] . $info .'</span>';
            }
        }
    }

    /**
     * @deprecated 2.4.0
     *
     * check coupone code
     *
     * @package XAJAX
     * @param string $coupon
     * @param int $plan_id
     * @param bool $diffuse
     * @param bool $renew
     * @param string $type
     **/
    public function ajaxCheckCouponCode($coupon, $plan_id, $diffuse = false, $renew = false, $type = false)
    {}

    /**
     * @deprecated 2.4.0
     */
    public function hookAddBannerCheckoutPreRedirect()
    {}

    /**
     * @deprecated 2.4.0
     *
     * Banners insert coupon users if necessary
     *
     * @param int $account_id - User account ID
     * @param int $plan_id - Plan ID
     */
    public function bannersInsertCouponUsersIfNecessary($account_id = false, $plan_id = false)
    {}
}
