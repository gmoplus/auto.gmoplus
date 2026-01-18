<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLBANKWIRETRANSFERGATEWAY.CLASS.PHP
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

class rlBankWireTransferGateway extends rlGateway
{
    /**
     * Payment details
     *
     * @var array
     */
    protected $payment_details;

    /**
     * Transaction status
     *
     * @var bool
     */
    protected $is_completed;

    /**
     * Class constructor
     */
    public function __construct()
    {
        if (isset($_SESSION['bwt_completed'])) {
            $this->is_completed = true;
        }
    }

    /**
     * Start payment process
     */
    public function call()
    {
        global $rlPayment, $rlSmarty, $reefless, $lang, $rlDb, $config, $addListing;

        // if enabled Shopping Cart plugin
        if ($config['shc_method'] == 'multi') {
            $reefless->loadClass('ShoppingCart', false, 'shoppingCart');
        }

        $txn_info = $rlDb->fetch('*', array('ID' => $rlPayment->getTransactionID()), null, 1, 'transactions', 'row');

        if ($txn_info['Txn_ID'] == 0) {
            $Txn_ID = $this->generateTransactionID();
            $this->setTransactionID($Txn_ID);
        } else {
            $this->setTransactionID($txn_info['Txn_ID']);
        }

        if ($txn_info['Txn_ID'] != $Txn_ID) {
            $this->sendEmailNotification();
            $this->setComplete();
        } else {
            $rlSmarty->assign('is_ready', true);
            $this->errors[] = $lang['bwt_txn_exists'];
        }

        $rlSmarty->assign_by_ref('txn_info', $txn_info);

        if ($this->isCompleted()) {
            $payment_details = $this->getPaymentDetails($rlPayment->getOption('dealer_id'));
            $rlSmarty->assign_by_ref('payment_details', $payment_details);

            $rlPayment->setForm(RL_PLUGINS . 'bankWireTransfer' . RL_DS . 'form.tpl');
            $rlPayment->enableForm();

            $rlSmarty->assign('completed', true);
            $rlSmarty->assign('txn_id', $this->getTransactionID());
            $reefless->loadClass('Notice');

            $pageUrl = $reefless->getPageUrl('payment_history');
            $phrasePleaseWait = preg_replace(
                '/(\[(\pL.*)\])/u',
                "<a href=\"{$pageUrl}\" target=\"_blank\">$2</a>",
                $lang['bwt_complete_please_wait']
            );
            $GLOBALS['rlNotice']->saveNotice($phrasePleaseWait);

            // add item data to transaction;
            $this->updateTransaction(
                array(
                    'Item_data' => $rlPayment->buildItemData(false),
                    'Txn_ID' => $this->getTransactionID(),
                    'Dealer_ID' => $config['shc_method'] == 'multi' ? (int) $rlPayment->getOption('dealer_id') : 0,
                )
            );

            if ($rlPayment->getOption('callback_method') === 'upgradeListing'
                || $rlPayment->getOption('service') === 'membership'
            ) {
                if ($rlPayment->getOption('callback_method') === 'upgradeListing') {
                    $listingID = (int) $rlPayment->getOption('item_id');
                } else {
                    $listingID = $addListing && $addListing->listingID ? $addListing->listingID : 0;
                }

                if ($listingID) {
                    $_SESSION['bwtAddListing'] = $listingID;

                    $GLOBALS['reefless']->loadClass('BankWireTransfer', null, 'bankWireTransfer');
                    $GLOBALS['rlBankWireTransfer']::deactivateListing($listingID, $rlDb);
                }
            }

            $GLOBALS['rlHook']->load('phpBankWireTransferAfterComplete', $rlPayment);
        }
    }

    /**
     * Complete payment
     *
     * @param  int $txn_id
     * @return bool
     */
    public function callBack($txn_id = 0)
    {
        global $rlCategories, $reefless;

        $txn_info = $GLOBALS['rlDb']->fetch('*', array('ID' => $txn_id), null, 1, 'transactions', 'row');

        if ($txn_info['Status'] == 'unpaid') {
            if ($txn_info) {
                $items = explode('|', base64_decode(urldecode($txn_info['Item_data'])));

                if ($items) {
                    $response = array(
                        'plan_id' => $items[0],
                        'item_id' => $items[1],
                        'account_id' => $items[2],
                        'total' => $txn_info['Total'],
                        'txn_id' => $txn_id,
                        'txn_gateway' => $txn_info['Txn_ID'],
                        'params' => $items[12],
                    );

                    $GLOBALS['rlPayment']->complete($response, $items[4], $items[5], $items[9] ? $items[9] : false);

                    if ($GLOBALS['config']['listing_auto_approval'] && in_array($txn_info['Service'], ['listing','package'])) {
                        $reefless->loadClass('Categories');
                        $reefless->loadClass('Account');
                        $reefless->loadClass('Listings');

                        $accountInfo = $GLOBALS['rlAccount']->getProfile((int) $items[2]);
                        $listing = $GLOBALS['rlListings']->getListing((int) $items[1], true);
                        if ($listing) {
                            $planInfo = $GLOBALS['rlDb']->fetch('*', array('ID' => $listing['Plan_ID']), null, 1, 'listing_plans', 'row');

                            $rlCategories->listingsIncrease($listing['Category_ID']);
                            $rlCategories->accountListingsIncrease($accountInfo['ID']);

                            // Crossed categories mode
                            if ($planInfo['Cross'] > 0 && $listing['Crossed']) {
                                foreach (explode(',', $listing['Crossed']) as $crossed_category) {
                                    $rlCategories->listingsIncrease($crossed_category);
                                }
                            }
                        }
                    }
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Send email notifications
     */
    public function sendEmailNotification()
    {
        global $config, $reefless, $rlPayment, $lang, $rlMail, $account_info, $rlAccount;

        $reefless->loadClass('Mail');

        $total = $config['system_currency_position'] == 'before'
        ? $config['system_currency'] . $rlPayment->getOption('total')
        : $rlPayment->getOption('total') . ' ' . $config['system_currency'];

        $date = date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT));

        if (!$account_info) {
            $account_info = $rlAccount->getProfile((int) $rlPayment->getOption('account_id'));
        }

        // get payment details
        $payment_details_mail = $this->getPaymentDetails($rlPayment->getOption('dealer_id'))['content'];

        $order_details = "
{$lang['bwt_item_id']}: {$rlPayment->getOption('item_id')}<br />
{$lang['item']}: {$rlPayment->getOption('item_name')}<br />
" . ($rlPayment->getOption('plan_key') ? $lang['plan'] . ": " . $lang[$rlPayment->getOption('plan_key')] : "") . "<br />
{$lang['txn_id']}: {$this->getTransactionID()}<br />
{$lang['total']}: {$total}<br />
{$lang['date']}: {$date}<br />
";

        // send notification to user
        $mail_tpl = $rlMail->getEmailTemplate('bwt_create_new_transaction', $account_info['Lang']);
        $link = $reefless->getPageUrl('payment_history');
        $pageName = $lang['pages+name+payment_history'];
        $paymentHistoryLink = '<a href="' . $link . '" target="_blank">' . $pageName . '</a>';

        $m_find = array('{name}', '{payment_details}', '{details}', '{payment_history}');
        $m_replace = array($account_info['Full_name'], $payment_details_mail, $order_details, $paymentHistoryLink);

        $mail_tpl['body'] = str_replace($m_find, $m_replace, $mail_tpl['body']);
        $rlMail->send($mail_tpl, $account_info['Mail']);

        $mail_tpl = $rlMail->getEmailTemplate('bwt_create_new_transaction_admin');

        $m_find = array('{details}', '{buyer}');
        $m_replace = array($order_details, $account_info['Full_name']);

        $mail_tpl['body'] = str_replace($m_find, $m_replace, $mail_tpl['body']);
        $rlMail->send($mail_tpl, $config['notifications_email']);

        // send notification to dealer
        if ($rlPayment->getOption('dealer_id')) {
            $dealer_info = $rlAccount->getProfile((int) $rlPayment->getOption('dealer_id'), true);
            if ($dealer_info) {
                $mail_tpl = $rlMail->getEmailTemplate('bwt_create_new_transaction_dealer', $dealer_info['Lang']);

                $m_find = array('{name}', '{buyer}', '{details}');
                $m_replace = array($dealer_info['Full_name'], $account_info['Full_name'], $order_details);

                $mail_tpl['body'] = str_replace($m_find, $m_replace, $mail_tpl['body']);
                $rlMail->send($mail_tpl, $dealer_info['Mail']);
            }
        }
    }

    /**
     * Generate transaction ID
     *
     * @param  mixed $txn_tpl
     * @return string
     */
    public function generateTransactionID($txn_tpl = 'BWT-**********')
    {
        global $config;

        $txn_length = $config['bankWireTransfer_lenght_txn_id']
        ? $config['bankWireTransfer_lenght_txn_id']
        : substr_count($txn_tpl, '*');

        $number = $this->getLastNumberTransaction();

        $number++;
        $number_length = strlen($number);
        $txn_length = $txn_length - $number_length;

        if ($txn_length > 0) {
            $txn_stars = str_repeat('0', $txn_length);
        }

        $mask = str_replace("*", "", $txn_tpl);
        $txn = $mask . $txn_stars . $number;

        return $txn;
    }

    /**
     * Get last number transaction
     */
    protected function getLastNumberTransaction()
    {
        $sql = "SELECT `Txn_ID` FROM `{db_prefix}transactions` ";
        $sql .= "WHERE `Gateway` = 'bankWireTransfer' AND `Txn_ID` <> '0' ORDER BY `Date` DESC";
        $transaction = $GLOBALS['rlDb']->getRow($sql);
        $number = 0;

        if ($transaction) {
            $number = explode("-", $transaction['Txn_ID']);
            $number = preg_replace('/\D/', '', $number[1]);
            $number = (int) $number;
        }

        return $number;
    }

    /**
     * Get payment details
     *
     * @param  int $dealer_id
     * @param  bool $source
     * @return array
     */
    public function getPaymentDetails($dealerID = 0, $source = false)
    {
        global $rlDb, $config, $rlLang;

        if (!$this->payment_details || $source) {
            $dealerID = (int) $dealerID;
            if ($config['shc_method'] == 'multi' && $dealerID) {
                if ($rlDb->tableExists('shc_account_settings')) {
                    $GLOBALS['reefless']->loadClass('ShoppingCart', null, 'shoppingCart');
                    $options = $GLOBALS['rlShoppingCart']->getAccountOptions($dealerID);
                } else {
                    $fields = [
                        'bankWireTransfer_enable',
                        'bankWireTransfer_details',
                    ];
                    $options = $rlDb->fetch($fields, array('ID' => $dealerID), null, 1, 'accounts', 'row');
                }

                $this->payment_details = array(
                    'content' => $options['bankWireTransfer_details'],
                );
            } else {
                $this->payment_details = [
                    'content' => $rlLang->getPhrase('bwt_payment_details_content', null, null, true)
                ];
            }
        }

        return $this->payment_details;
    }

    /**
     * Check if payment is completed
     */
    public function isCompleted()
    {
        return $this->is_completed;
    }

    /**
     * Set completed status to payment
     *
     * @param bool $mode
     */
    public function setComplete($mode = true)
    {
        $this->is_completed = $_SESSION['bwt_completed'] = $mode;
    }

    /**
     * Clear transaction options
     */
    public function clear()
    {
        unset($_SESSION['bwt_completed'], $_SESSION['Txn_ID']);
    }

    /**
     * check if plugin configured
     */
    public function isConfigured()
    {
        if ($GLOBALS['rlPayment']->gateways['bankWireTransfer']['Status'] == 'active') {
            return true;
        }

        return false;
    }

    /**
     * @deprecated 3.0.0
     *
     * Transaction type
     *
     * @var string
     */
    protected $type;

    /**
     * @deprecated 3.0.0
     *
     * BWT Transaction ID
     *
     * @var string
     */
    protected $bwt_transaction_id;

    /**
     * @deprecated 3.0.0
     *
     * Set transaction type
     *
     * @param string $type
     */
    public function setType($type = '')
    {}

    /**
     * @deprecated 3.0.0
     *
     * Get transaction type
     */
    public function getType()
    {}

    /**
     * @deprecated 3.0.0
     *
     * Set BWT transaction ID
     *
     * @param mixed $txn_id
     */
    public function setBWTTransactionID($txn_id = false)
    {}

    /**
     * @deprecated 3.0.0
     *
     * Get BWT transaction ID
     *
     * @return string
     */
    public function getBWTTransactionID()
    {}

    /**
     * @deprecated 3.0.0
     *
     * Create BWT transaction
     *
     * @return bool
     */
    public function createTransaction()
    {}

    /**
     * @deprecated 3.0.0
     *
     * validate credit card details
     */
    public function validate()
    {}

    /**
     * @deprecated 3.0.0
     *
     * Check and get transaction details
     *
     * @return array
     */
    public function isReady()
    {}

    /**
     * @deprecated 3.0.0
     *
     * Check if define transaction ID
     *
     * @param  string $transaction_id
     * @return bool
     */
    public function isTransactionExists($txn_id = '')
    {}

    /**
     * @deprecated 3.0.0
     *
     * Get BWT transaction details
     *
     * @param  int $transaction_id
     * @return array
     */
    public function getTransaction($txn_id = 0)
    {}
}
