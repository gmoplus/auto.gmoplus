<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: PAYMENTCONTROLLER.PHP
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

namespace Flynax\Api\Http\Controllers\V1;

use Illuminate\Http\Request;

class PaymentController extends BaseController
{

    public function __construct()
    {
        require_once RL_CLASSES . 'rlGateway.class.php';
    }

    /**
     * Validated payment
     * @param $_POST data
     *
     * @return array
     **/
    public function validatedPayment()
    {
        $account_id = $_POST['account_id'];
        $password = $_POST['account_password'];

        $accountController = new AccountController();
        if ($accountController->issetAccount($account_id, $password)) {

            switch ($_POST['gateway']) {
                case 'yookassa':
                    $yookassaController = new YookassaController();
                    $yookassaController->init();
                    $response = $yookassaController->validateYookassaTransaction();
                    break;

                default:
                    $GLOBALS['rlDebug']->logger("App flutter: " . __FUNCTION__ . "(), unknown payment geteway request: {$_POST['gateway']}");
                    return false;

                    break;
            }
            if ($response['status'] == 'complete') {
                switch ($_POST['service']) {
                    case 'membership':
                        $response['success'] = $accountController->getAccountPlan($account_id);
                        break;

                    case 'listing':
                        $response['success'] = (new ListingsController)->buildReturnMyListing($_POST['id']);
                        break;
                    
                    case 'upgradePackage':
                    case 'purchasePackage':
                        $response['success'] = true;
                        break;
                }
            }
        }
        else {
            $response['status'] = 'error';
            $response['message'] = $GLOBALS['lang']['error_request_api'];
        }

        return $response;
    }

    /**
     * Confirm payment
     * @param $_POST data
     *
     * @return array
     **/
    public function confirmPayment()
    {
        $account_id = $_POST['account_id'];
        $password = $_POST['account_password'];

        $accountController = new AccountController();
        if ($accountController->issetAccount($account_id, $password)) {
            if ($_POST['gateway']) {
                switch ($_POST['gateway']) {
                    case 'yookassa':
                        $yookassaController = new YookassaController();
                        $yookassaController->init();
                        $response = $yookassaController->confirmYookassaPayment();
                        break;

                    default:
                        $GLOBALS['rlDebug']->logger("App flutter: " . __FUNCTION__ . "(), unknown payment geteway request: {$_POST['gateway']}");
                        return false;

                        break;
                }
            }
            // build return data
            $response['status'] = 'complete';
            switch ($_POST['service']) {
                case 'membership':
                    $response['success'] = $accountController->getAccountPlan($account_id);
                    break;

                case 'listing':
                    $response['success'] = (new ListingsController)->buildReturnMyListing($_POST['item_id']);
                    break;

                case 'upgradePackage':
                case 'purchasePackage':
                    $response['success'] = true;
                    break;

                case 'shopping':
                    $response['success'] = true;
                    break;
            }
        }
        else {
            $response['status'] = 'error';
            $response['message'] = $GLOBALS['lang']['error_request_api'];
        }
        return $response;
    }

    /**
     * Payment history
     * @param $_POST data
     *
     * @return array
     **/
    public function paymentHistory()
    {
        if ((new AccountController)->issetAccount($_REQUEST['account_id'], $_REQUEST['account_password'])) {
            $payment_services_multilang = array(
                'package',
                'membership',
                'invoice',
            );
            $page = $_REQUEST['start'] ? $_REQUEST['start'] - 1 : 0;
            $start = intval($page * $GLOBALS['config']['transactions_per_page']);
            $limit = intval($GLOBALS['config']['transactions_per_page']);

            $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT * ";
            $sql .= "FROM `{db_prefix}transactions` ";
            $sql .= "WHERE `Status` <> 'trash' AND `Account_ID` = '{$_REQUEST['account_id']}' ";

            rl('Hook')->load('paymentHistorySqlWhere', $sql);

            $sql .= "ORDER BY `Date` DESC LIMIT {$start}, {$limit}";
            $transactions = rl('Db')->getAll($sql);
            $calc = rl('Db')->getRow("SELECT FOUND_ROWS() AS `calc`");

            foreach ($transactions as $key => &$item) {
                if (in_array($item['Service'], $payment_services_multilang) && !empty($item['Plan_key'])) {
                    $transactions[$key]['Item_name'] = '';
                }
                if ($item['Plan_key']) {
                    $transactions[$key]['Plan_name'] = $GLOBALS['lang'][$item['Plan_key']];
                }
                if ($GLOBALS['l_plan_types'] && $item['Service'] && array_key_exists($item['Service'], $GLOBALS['l_plan_types'])) {
                    if (in_array($item['Service'], array('listing', 'featured'))) {
                        $item_details = rl('Listings')->getListing($item['Item_ID'], true);

                        if ($item_details) {
                            $transactions[$key]['link'] = $item_details ? $item_details['listing_link'] : false;
                        }
                    } else {
                        rl('Hook')->load('phpPaymentHistoryDefault', $item);
                    }
                } else {
                    rl('Hook')->load('phpPaymentHistoryLoop', $item);
                }

                unset($plan_info, $item_details);
            }
            $response['result'] = $transactions;
            $response['total'] = $calc['calc'];
            $response['status'] = 'ok';
        } else {
            $response = 'error';
        }


        return $response;
    }
}
