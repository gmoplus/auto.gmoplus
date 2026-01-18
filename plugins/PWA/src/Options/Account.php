<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : ACCOUNT.PHP
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

namespace Flynax\Plugins\PWA\Options;

use Flynax\Plugins\PWA\Config;

class Account
{
    /**
     * Subscription is checking new coming listings
     */
    const SUBSCRIPTION_NEW_LISTINGS = 'new_listing';

    /**
     * Subscription is checking new coming listing
     */
    const SUBSCRIPTION_NEW_MESSAGE = 'new_message';

    /**
     * @var int $accountID - Account ID of the users which subscription options will be controller
     *                     - by this class
     */
    protected $accountID;

    /**
     * @var array - Available subscriptions
     */
    protected $subscriptions;

    /**
     * @var $rlDb
     */
    protected $rlDb;

    /**
     * Account constructor.
     *
     * @param $id
     */
    public function __construct($id)
    {
        $this->accountID     = (int) $id;
        $this->subscriptions = Config::i()->getConfig('account_subscription');;
        $this->rlDb          = $GLOBALS['rlDb'];
    }

    /**
     * Subscribe user to get push notification if someone is sending private message to him
     *
     * @return bool
     */
    public function subscribeToNewMessages()
    {
        return $this->subscribeAccountTo($this->subscriptions[Account::SUBSCRIPTION_NEW_MESSAGE]);
    }

    /**
     * Unsubscribe user from getting push notification if someone is sending private messag to him
     *
     * @return bool
     */
    public function unsubscribeFromNewMessages()
    {
        return $this->unsubscribeAccountFrom($this->subscriptions[Account::SUBSCRIPTION_NEW_MESSAGE]);
    }

    /**
     * Subscribe user for getting push notification regarding new listings
     *
     * @return bool
     */
    public function subscribeToNewListings()
    {
        return $this->subscribeAccountTo($this->subscriptions[Account::SUBSCRIPTION_NEW_LISTINGS]);
    }

    /**
     * Subscribe user for getting push notification regarding new listings
     *
     * @return bool
     */
    public function unsubscribeFromNewListings()
    {
        return $this->unsubscribeAccountFrom($this->subscriptions[Account::SUBSCRIPTION_NEW_LISTINGS]);
    }

    /**
     * Subscribe user to all actions
     */
    public function subscribeToAllActions()
    {
        foreach ($this->subscriptions as $subscription) {
            $this->subscribeAccountTo($subscription);
        }
    }

    /**
     * Unsubscribe user from all actions
     */
    public function unsubscribeFromAllActions()
    {
        foreach ($this->subscriptions as $subscription) {
            $this->subscribeAccountTo($subscription);
        }
    }

    /**
     * Subscribe user to subscription
     *
     * @param string $option - Subscription key (could be found in bootstrap.php)
     *
     * @return bool
     */
    public function subscribeAccountTo($option)
    {
        return $this->updateSubscriptionColumn($option, true);
    }

    /**
     * Unsubscribe user from subscription
     *
     * @param string $option - Subscription key (could be found in bootstrap.php)
     *
     * @return bool
     */
    public function unsubscribeAccountFrom($option)
    {
        return $this->updateSubscriptionColumn($option, false);
    }

    /**
     * Update subscription column of the `db_prefix_pwa_subscriptions` table by provided value
     *
     * @param string $column    - Subscription key (could be found in bootstrap.php)
     * @param bool   $subscribe - Boolean value of should method change value to 0 or 1
     *
     * @return bool
     */
    private function updateSubscriptionColumn($column, $subscribe = true)
    {
        if (!$accountID = $this->accountID || !$column) {
            return false;
        }

        return (bool) $this->rlDb->updateOne(
            ['fields' => [$column => (int) $subscribe], 'where' => ['Account_ID' => $this->accountID]],
            'pwa_subscriptions'
        );
    }

    /**
     * Get all subscriptions by all his devices
     */
    public function getAllSavedSubscriptions()
    {
        if (!$this->accountID) {
            return [];
        }

        $sql = 'SELECT * FROM `{db_prefix}pwa_subscriptions`';
        $sql .= "WHERE `Account_ID` = '{$this->accountID}'";

        return (array) $this->rlDb->getAll($sql);
    }
}
