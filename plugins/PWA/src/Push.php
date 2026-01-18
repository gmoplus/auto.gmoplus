<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLPWA.CLASS.PHP
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
 *  Flynax Classifieds Software 2024 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

namespace Flynax\Plugins\PWA;

use Flynax\Plugins\PWA\Traits\SingletonTrait;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription as ManishlikSubscription;
use Flynax\Plugins\PWA\Files\Icon;

/**
 * Class Push
 * @package Flynax\Plugins\PWA
 */
class Push
{
    use SingletonTrait;

    /**
     * @var int
     */
    private $toAccount = 0;

    /**
     * @var bool
     */
    private $alertsOnly = false;

    /**
     * @var bool
     */
    private $messagesOnly = false;

    /**
     * Set ID of recipient
     *
     * @param $accountID
     * @return $this
     */
    public function toAccount($accountID)
    {
        $this->toAccount = (int) $accountID ?: 0;

        return $this;
    }

    /**
     * Leave push about new added listings only
     *
     * @return $this
     */
    public function alertsOnly()
    {
        $this->alertsOnly = true;
        return $this;
    }

    /**
     * Leave push about received messages only
     *
     * @return $this
     */
    public function messagesOnly()
    {
        $this->messagesOnly = true;
        return $this;
    }

    /**
     * Send push notification with message
     *
     * @param $message
     */
    public function send($message)
    {
        $message['message'] = stripslashes($message['message']);
        $message['message'] = str_replace(['<br />', '\n'], PHP_EOL, $message['message']);

        $message       = json_encode($message);
        $subscriptions = $this->getSubscriptionsCollection();

        foreach ($subscriptions as $key => $subscription) {
            if (!$subscription['Endpoint']
                || !$subscription['P256dh']
                || $subscription['Subscription'] !== 'active'
            ) {
                continue;
            }

            try {
                $subject = false !== strpos($subscription['Endpoint'], 'web.push.apple.com')
                    ? RL_URL_HOME
                    : $GLOBALS['rlConfig']->getConfig('site_main_email');

                $auth = [
                    'VAPID' => [
                        'subject'    => $subject,
                        'publicKey'  => Config::i()->getConfig('pwa_vapid_public'),
                        'privateKey' => Config::i()->getConfig('pwa_vapid_private'),
                    ],
                ];
                $webPush = new WebPush($auth);

                $manishlikSubscription = ManishlikSubscription::create([
                    'endpoint' => $subscription['Endpoint'],
                    'keys'     => [
                        'p256dh' => $subscription['P256dh'],
                        'auth'   => $subscription['Auth'],
                    ],
                ]);
                $webPush->queueNotification($manishlikSubscription, $message);

                foreach ($webPush->flush() as $report) {
                    $endpoint = $report->getRequest()->getUri()->__toString();
                    if (!$report->isSuccess()) {
                        $GLOBALS['rlDebug']->logger(
                            "PWA: Push failed to sent for subscription {$endpoint}: {$report->getReason()}"
                        );

                        $GLOBALS['rlDb']->delete(['Endpoint' => $endpoint], 'pwa_subscriptions');
                    }
                }
            } catch (\Exception $exception) {
                $GLOBALS['rlDebug']->logger(
                    "PWA: Push failed to sent {$exception->getMessage()}"
                );
            }
        }
    }

    /**
     * Send push about new added listing
     *
     * @param $listing
     * @return bool
     */
    public function sendListing($listing)
    {
        if (!$listing) {
            return false;
        }

        $GLOBALS['reefless']->loadClass('Listings');
        $GLOBALS['reefless']->loadClass('Account');

        if (is_string($listing) || is_numeric($listing)) {
            $listingInfo = $GLOBALS['rlListings']->getShortDetails($listing);
        } elseif (is_array($listing)) {
            $listingInfo = $listing;
        }

        $sendingData = [
            'tag'     => 'alert',
            'title'   => $GLOBALS['lang']['pwa_new_listing_added'],
            'message' => $listingInfo['listing_title'],
            'link'    => $listingInfo['url'],
        ];

        if ($listingInfo['Main_photo']) {
            $sendingData['image'] = RL_FILES_URL . $listingInfo['Main_photo'];
        } else {
            $sendingData['icon'] = Icon::getAppUrlIcon();
        }

        Push::i()->alertsOnly()->send($sendingData);
    }

    /**
     * Get all subscriptions by user
     *
     * @return array
     */
    public function getSubscriptionsCollection()
    {
        $subscriptionManager = new Subscription();
        $where = [];

        if ($this->toAccount) {
            $where['Account_ID'] = $this->toAccount;
        }

        if ($this->alertsOnly) {
            $where['Alerts'] = '1';
        }

        if ($this->messagesOnly) {
            $where['Messages'] = '1';
        }

        $subscriptions = $where
            ? $subscriptionManager->where($where)->getAll()
            : $subscriptionManager->getAll();

        return $subscriptions;
    }

    /**
     * Send push from one account to another
     *
     * @param $from
     * @param $toID
     * @param $message
     * @return bool
     */
    public function sendMessageToAccount($from, $toID, $message)
    {
        $toID    = (int) $toID;
        $message = (string) $message;

        if (!$from || !$toID || !$message) {
            return false;
        }

        $this->toAccount = $toID;

        if (is_int($from)) {
            $senderInfo = $GLOBALS['rlAccount']->getProfile($from);
            $name       = $senderInfo['Full_name'] ?: $senderInfo['Username'];
            $fromData   = $senderInfo['ID'];
            $icon       = $senderInfo['Photo']
                ? RL_FILES_URL . $senderInfo['Photo']
                : $sendingData['icon'] = Icon::getAppUrlIcon();
        } elseif (is_array($from)) {
            $name = $from['name'];
            $fromData = '-1&visitor_mail=' . $from['email'];
            $icon     = $sendingData['icon'] = Icon::getAppUrlIcon();
        } else {
            return false;
        }

        $sendingData = [
            'tag'     => 'message',
            'title'   => sprintf($GLOBALS['lang']['pwa_message_from'], $name),
            'message' => $message,
            'link'    => $GLOBALS['reefless']->getPageUrl('my_messages')
                . ($GLOBALS['config']['mod_rewrite'] ? '?' : '&') . 'id=' . $fromData,
            'icon'    => $icon,
        ];

        Push::i()->messagesOnly()->send($sendingData);
    }
}
