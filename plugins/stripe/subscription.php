<?php
/**copyright*/

if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
    require_once '../../includes/config.inc.php';

    // system controller
    require_once RL_INC . 'control.inc.php';

    // load system configurations
    $config = $rlConfig->allConfig();

    require_once RL_CLASSES . 'rlGateway.class.php';
    $reefless->loadClass('Payment');
    $reefless->loadClass('StripeGateway', null, 'stripe');

    $response = $rlStripeGateway->getWebhookResponse();
    $subscription_id = '';

    switch ($response->type) {
        case 'invoice.payment_succeeded':
            $subscription_id = $rlValid->xSql($response->data->object->lines->data[0]->subscription);

            if (empty($subscription_id)) {
                $subscription_id = $rlValid->xSql($response->data->object->lines->data[0]->id);
            }

            $txn_id = $rlValid->xSql($response->data->object->charge);
            $total = round((float) $response->data->object->amount_paid / 100, 2);
            break;

        case 'customer.subscription.updated':
            $subscription_id = $rlValid->xSql($response->data->object->id);
            break;
    }

    if ($subscription_id) {
        $subscription_info = $rlStripeGateway->getSubscriptionByID($subscription_id);

        if ($subscription_info) {
            $sql = "UPDATE `{db_prefix}subscriptions` SET `Count` = `Count` + 1 WHERE `ID` = '{$subscription_info['ID']}' LIMIT 1";
            $rlDb->query($sql);

            $items = $subscription_info['Stripe_item_data'];

            if ($response->type == 'invoice.payment_succeeded') {
                $data = array(
                    'plan_id' => $subscription_info['Plan_ID'],
                    'item_id' => $subscription_info['Item_ID'],
                    'account_id' => $subscription_info['Account_ID'],
                    'total' => $total,
                    'txn_gateway' => $txn_id,
                    'params' => $items[4],
                );

                $insert = array(
                    'Service' => $subscription_info['Service'],
                    'Account_ID' => $subscription_info['Account_ID'],
                    'Item_ID' => $subscription_info['Item_ID'],
                    'Plan_ID' => $subscription_info['Plan_ID'],
                    'Total' => $total,
                    'Txn_ID' => $txn_id,
                    'Date' => 'NOW()',
                    'Item_name' => $subscription_info['Item_name'],
                    'Gateway' => 'stripe',
                    'Status' => 'paid',
                );

                $rlDb->insertOne($insert, 'transactions');
            }

            $rlPayment->complete($data, $items[0], $items[1], $items[2]);
        }
    }

    if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
        http_response_code(200);
    }
}
