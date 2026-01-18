<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLAUTOPOSTER.CLASS.PHP
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

namespace Autoposter\Providers;

use Flynax\Utils\Category;
use Flynax\Utils\ListingMedia;
use TelegramBot\Api;
use Autoposter\AutoPosterContainer;
use Autoposter\AutoPosterModules;
use Autoposter\Interfaces\ProviderInterface;
use Autoposter\MessageBuilder;
use Autoposter\Notifier;

/**
 * @since 1.7.0
 */
class Telegram implements ProviderInterface
{
    /**
     * @var rlDb
     */
    private $rlDb;

    /**
     * @var array - Flynax configuration array
     */
    private $flConfigs;

    /**
     * @var array - Configuration of the provider
     */
    private $providerConfig;

    /**
     * @var rlListings
     */
    private $rlListings;

    /**
     * @var reefless
     */
    private $reefless;

    /**
     * @var Notifier
     */
    private $notifier;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->flConfigs = AutoPosterContainer::getConfig('flConfigs');
        $this->rlDb = AutoPosterContainer::getObject('rlDb');
        $this->rlListings = AutoPosterContainer::getObject('rlListings');
        $this->reefless = AutoPosterContainer::getObject('reefless');
        $this->notifier = new Notifier('telegram');

        $this->providerConfig = [
            'ap_telegram_bot_token',
            'ap_telegram_chat_id',
        ];
    }

    /**
     * Send post to the provider
     *
     * @param  int $listingID - Posting listing ID
     * @return bool           - Does posting is processed successfully
     */
    public function post($listingID)
    {
        if (!$this->isConfigured() || $this->hasBeenPosted($listingID) || !$this->rlListings->isActive($listingID)) {
            return false;
        }

        $rlLang = AutoPosterContainer::getObject('rlLang');
        if (!$GLOBALS['lang']) {
            $GLOBALS['lang'] = $rlLang->getLangBySide('frontEnd', RL_LANG_CODE);
        }
        $listing_data = $this->rlListings->getListing($listingID, true);
        $main_photo = $listing_data['Main_photo']
        ? $this->rlDb->fetch('Photo', ['Thumbnail' => $listing_data['Main_photo']], null, 1, 'listing_photos', 'row')
        : '';

        $this->reefless->loadClass('Account');
        $messageBuilder = new MessageBuilder();
        $message = trim($messageBuilder->decodeMessage($listing_data, RL_LANG_CODE));

        if (!$message) {
            $category = Category::getCategory($listing_data['Category_ID']);
            $this->notifier->logMessage("Telegram API Exception: no message pattern build for listing category ({$category['Path']}: {$category['name']}) or it's parent.");

            return false;
        }

        $module = new AutoPosterModules();
        $GLOBALS['pages'] = $module->getAllPages();

        $link = sprintf('<a href="%s">%s</a>', $listing_data['listing_link'], $GLOBALS['lang']['view_details']);

        try {
            $bot = new Api\BotApi($this->flConfigs['ap_telegram_bot_token']);

            if ($main_photo) {
                $strlen_func = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
                $substr_func = function_exists('mb_substr') ? 'mb_substr' : 'substr';

                $link_length = $strlen_func($GLOBALS['lang']['view_details']) + 1;
                $truncate = 200;

                if (($strlen_func($message) + $link_length) > $truncate) {
                    $truncate -= 3;
                    $truncate -= $link_length;
                    $message = $substr_func($message, 0, $truncate) . '...';
                }

                $text = sprintf('%s %s', $message, $link);

                ListingMedia::prepareURL($main_photo);
                $response = $bot->sendPhoto(
                    sprintf('-%s', $this->flConfigs['ap_telegram_chat_id']),
                    $main_photo['Photo'],
                    $text,
                    null, null, false, 'html'
                );
            } else {
                $text = sprintf('%s %s', $message, $link);

                $response = $bot->sendMessage(
                    sprintf('-%s', $this->flConfigs['ap_telegram_chat_id']),
                    $text,
                    'html'
                );
            }

            $message_id = $response->getMessageId();

            if ($message_id) {
                $this->afterSuccessPosting($message_id, $listingID);
                return true;
            }
        } catch (Api\Exception $e) {
            $this->notifier->logMessage('Telegram API Exception: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * After posting to the Telegram timeline trigger
     *
     * @param int $messageID - Posted message ID (value come from Telegram API response)
     * @param int $listingID - Posted listing ID
     */
    public function afterSuccessPosting($messageID, $listingID)
    {
        $GLOBALS['rlAutoPoster']->setSocialNetworkID($messageID, $listingID, 'Telegram_message_id');
    }

    /**
     * Is provider has been configured successfully
     *
     * @return bool
     */
    public function isConfigured()
    {
        foreach ($this->providerConfig as $config) {
            if (!$this->flConfigs[$config]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checking does listing has been posted
     *
     * @param  int  $listingID - ID of the checking Listing
     * @return bool            - Checking status
     */
    public function hasBeenPosted($listingID)
    {
        return !empty($this->rlDb->getOne('Telegram_message_id', "`Listing_ID` = {$listingID}", 'autoposter_listings'));
    }

    /**
     * Getting token
     */
    public function getToken()
    {}

    /**
     * Delete message by Listing ID
     *
     * @param int $listingID - Listing ID
     */
    public function deletePost($listingID)
    {
        $listingID = (int) $listingID;
        if (!$listingID || !$postID = $this->getMessageID($listingID)) {
            return false;
        }

        try {
            $bot = new Api\BotApi($this->flConfigs['ap_telegram_bot_token']);
            $bot->deleteMessage(
                sprintf('-%s', $this->flConfigs['ap_telegram_chat_id']),
                $postID
            );
        } catch (Api\Exception $e) {
            $this->notifier->logMessage('Telegram API Exception: Unable to remove message, ' . $e->getMessage());
        }
    }

    /**
     * Get Telegram message ID by Listing ID
     *
     * @param int $listingID
     *
     * @return string
     */
    public function getMessageID($listingID)
    {
        $listingID = (int) $listingID;

        if (!$listingID) {
            return '';
        }

        $where = sprintf("`Listing_ID` = %d", $listingID);
        return $this->rlDb->getOne('Telegram_message_id', $where, 'autoposter_listings');
    }

    /*** DEPRECATED METHODS ***/

    /**
     * Checking is listings status is non Active
     *
     * @deprecated 1.8.0
     *
     * @param  int  $listingID - ID of the checking Listing
     * @return bool            - Is listings posted
     */
    public function isListingsActive($listingID)
    {}
}
