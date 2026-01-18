<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : GOOGLE.GATEWAY.PHP
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

class google
{
    public $payment_info;
    public $account_id;
    public $errors;

    public $results;
    public $approved = false;

    public function __construct($payment, $accountId)
    {
        $this->payment_info = $payment;
        $this->account_id = $accountId;
        $this->results = new stdClass();

        $this->getTransaction();
    }

    public function getTransaction()
    {
        //$this -> payment_info['tracking_id'] = '{"orderId":"12999763169054705758.1322467832085080","packageName":"com.flynax.flydroid","productId":"supreme","purchaseTime":1415855585618,"purchaseState":0,"developerPayload":"bGoa+V7g\/yqDXvKRqq+JTFn4uQZbPiQJo4pf9RzJ","purchaseToken":"adjbelfmfdbkcbdegmhhekoh.AO-J1OzKtiUWcaXyv9L07qYdCQy8zJspOtsYtJfVZY8yYgY79g90gOq5_OlAt1EAnuZt2PyXFtW52aq_d5U_L8HZl1WclK08DMM2c-agzMpXu5260DVjJok"}|||UbhbsT+VSCYIOOkrL3zE1TPOUzKTDPrJDEjjocFNdQB7cUPYRbqQZuSuri8uDzaCDH0tX+GksSDnx6mtTwCwC+r4U5cmr+qecr9oToMDT4qT9bg7BFrFv0VYA8DXBkWNMxQzHUFtUhUZ6pQUHckeZEWS1Acg3M1CGfoZxCAu5Ggsqy3lSzzvkPnQ/qWWhI44lH7/9Kg2jIz7D0z7SgNZfCc6BUTP1O0iwXuhqjDZtO5FXUTrwhfHeaP1jox4TeU+4Q94lN03oWQdTR8wnNezsZ7IGPVdc8sjZXj72I7rHOkjT/iVHKc2Ialn9FZLEuBlD9mlCkv/nQNfTfdTAgnQCw==';

        $data = explode('|||', $this->payment_info['tracking_id']);
        $json = json_decode($data[0]);

        require_once RL_PLUGINS . 'androidConnect/libs/androidMarket/ResponseData.php';
        require_once RL_PLUGINS . 'androidConnect/libs/androidMarket/ResponseValidator.php';

        // your goole play key
        define('PUBLIC_KEY', $GLOBALS['rlDb']->getOne('Values', "`Key` = 'android_inapp_key'", 'config')); //the key is too long to store it in 255 varchar config Default field
        define('PACKAGE_NAME', $json->packageName);

        //The | delimited response data from the licensing server
        $responseData = $data[0];
        //The signature provided with the response data (Base64)
        $signature = $data[1];

        //if you wish to inspect or use the response data, you can create
        //a response object and pass it as the first argument to the Validator's verify method
        //$response = new AndroidMarket_Licensing_ResponseData($responseData);
        //$valid = $validator->verify($response, $signature);

        $validator = new AndroidMarket_Licensing_ResponseValidator(PUBLIC_KEY, PACKAGE_NAME);
        $valid = $validator->verify($responseData, $signature);

        if ($valid) {
            $this->results->transaction_id = $json->orderId;
            $this->approved = true;
        } else {
            $this->errors[] = "google_transaction_is_not_approved";
            $GLOBALS['rlDebug']->logger("ANDROID BILLING (Google): " . __FUNCTION__ . "(), The payment with ID: " . $this->payment_info['tracking_id'] . " isn't approved or amount is wrong");
        }
    }
}
