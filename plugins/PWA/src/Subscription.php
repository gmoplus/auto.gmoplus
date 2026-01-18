<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : SUBSCRIPTION.PHP
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

namespace Flynax\Plugins\PWA;

class Subscription
{
    const DB_TABLE = 'pwa_subscriptions';
    const DB_TABLE_WITH_PREFIX = RL_DBPREFIX . Subscription::DB_TABLE;

    private $whereSql = '';

    /**
     * @var \rlDb
     */
    private $rlDb;

    /**
     * Subscription constructor.
     */
    public function __construct()
    {
        $this->rlDb = $GLOBALS['rlDb'];
    }

    public static function subscribe($subscription)
    {
        $result = false;

        if (empty($subscription = (array) $subscription)) {
            return $result;
        }

        $self = new self();

        if ($self->where($subscription)->getAll()) {
            $result = $self->rlDb->updateOne([
                'fields' => ['Subscription' => 'active'],
                'where'  => $subscription
            ], self::DB_TABLE);
        } else {
            $result = $self->rlDb->insertOne([
                    'Account_ID'   => $subscription['Account_ID'],
                    'Alerts'       => '1',
                    'Messages'     => '1',
                    'Subscription' => 'active',
                    'Endpoint'     => $subscription['Endpoint'],
                    'P256dh'       => $subscription['P256dh'],
                    'Auth'         => $subscription['Auth'],
                ], self::DB_TABLE
            );

            $self->rlDb->delete(
                ['Account_ID' => $subscription['Account_ID'], 'Subscription' => 'blocked'],
                self::DB_TABLE
            );
        }

        return $result;
    }

    /**
     * Prevent show push-notification for user
     * @param  $subscription
     * @return bool
     */
    public static function unsubscribe($subscription)
    {
        $result = false;

        if (empty($subscription = (array) $subscription)) {
            return $result;
        }

        $self = new self();

        if ($self->where($subscription)->getAll()) {
            $result = $self->rlDb->update([
                'fields' => ['Subscription' => 'inactive'],
                'where'  => ['Account_ID'   => $subscription['Account_ID']]
            ], self::DB_TABLE);
        } else {
            $subscription['Subscription'] = 'inactive';
            $result = $self->rlDb->insertOne($subscription, self::DB_TABLE);
        }

        return $result;
    }

    /**
     * User denied sending push notifications for him
     * @param  $subscription
     * @return bool
     */
    public static function blocked($subscription)
    {
        $result = false;

        if (empty($subscription = (array) $subscription)) {
            return $result;
        }

        $self = new self();

        if ($self->where($subscription)->getAll()) {
            $result = $self->rlDb->update([
                'fields' => ['Subscription' => 'blocked'],
                'where'  => ['Account_ID'   => $subscription['Account_ID']]
            ], self::DB_TABLE);
        } else {
            $subscription['Subscription'] = 'blocked';
            $result = $self->rlDb->insertOne($subscription, self::DB_TABLE);
        }

        return $result;
    }

    public function getAll()
    {
        $sql = sprintf("SELECT * FROM `%s` ", self::DB_TABLE_WITH_PREFIX);

        if ($this->whereSql) {
            $sql .= "WHERE {$this->whereSql}";
        }

        return (array) $this->rlDb->getAll($sql);
    }

    public function where($condition)
    {
        if (!is_array($condition)) {
            return false;
        }

        $whereSql = '';
        foreach ($condition as $key => $item) {
            $whereSql .= sprintf("AND `%s` = '%s' ", $key, $item);
        }
        $this->whereSql = ltrim($whereSql, 'AND ');

        return $this;
    }
}
