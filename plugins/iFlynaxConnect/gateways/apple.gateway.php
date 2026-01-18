<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : APPLE.GATEWAY.PHP
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

class appleInAppPurchase
{
    private $payment_info;
    private $account_id;
    private $result;

    public $errors;
    public $approved = false;
    public $transactionId;

    private $error_codes = array(
        21000 => 'The App Store could not read the JSON object you provided.',
        21002 => 'The data in the receipt-data property was malformed or missing.',
        21003 => 'The receipt could not be authenticated.',
        21004 => 'The shared secret you provided does not match the shared secret on file for your account.',
        21005 => 'The receipt server is not currently available.',
        21006 => 'This receipt is valid but the subscription has expired. When this status code is returned to your server, the receipt data is also decoded and returned as part of the response.',
        21007 => 'This receipt is from the test environment, but it was sent to the production environment for verification. Send it to the test environment instead.',
        21008 => 'This receipt is from the production environment, but it was sent to the test environment for verification. Send it to the production environment instead.',
    );

    public function __construct($payment, $accountId)
    {
        $this->payment_info = $payment;
        $this->account_id = $accountId;
        $this->result = new stdClass();

        $this->api = $GLOBALS['config']['iflynax_inapp_sandbox']
            ? 'https://sandbox.itunes.apple.com'
            : 'https://buy.itunes.apple.com';

        $this->getTransaction();
    }

    private function getTransaction()
    {
        $receipt = json_encode(['receipt-data' => $this->payment_info['receipt']]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->api . '/verifyReceipt');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $receipt);
        $response = curl_exec($ch);
        curl_close($ch);

        if (empty($response)) {
            $this->errors[] = "apple_no_get_payment_response";
            $GLOBALS['rlDebug']->logger("IOS BILLING (In-App Purchase): no received any response. Account_ID: " . $this->account_id);
        }
        else {
            $this->result = json_decode($response);

            if ($this->result->status == 0) { //receipt is valid
                $product = $this->result->receipt->in_app[0];

                $this->transactionId = $product->transaction_id;
                $this->approved = true;
            }
            else {
                $apple_error_message = $this->error_codes[$this->result->status];
                $this->errors[] = $apple_error_message;

                $GLOBALS['rlDebug']->logger("IOS BILLING (In-App Purchase): Apple error: " . $apple_error_message . ' Account_ID: ' . $this->account_id);
            }
        }
    }
}
