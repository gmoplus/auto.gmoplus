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

use Flynax\Utils\ListingMedia;
use Abraham\TwitterOAuth\TwitterOAuth;
use Autoposter\AutoPosterContainer;
use Autoposter\AutoPosterModules;
use Autoposter\Interfaces\ProviderInterface;
use Autoposter\MessageBuilder;
use Autoposter\Notifier;

class Twitter implements ProviderInterface
{
    /**
     * @var \rlDb
     */
    private $rlDb;

    /**
     * @var array - Flynax configuration array
     */
    private $flConfigs;

    /**
     * @var array - Configuration of the Twitter provider
     */
    private $providerConfig;

    /**
     * @var TwitterOAuth
     */
    private $twitter;

    /**
     * @var \rlListings
     */
    private $rlListings;

    /**
     * @var \reefless
     */
    private $reefless;

    /**
     * @var object
     */
    private $notifier;

    /**
     * Flag for the TMP photo file usage
     *
     * @since 1.7.0
     * @var boolean
     */
    private $tmpPhotoPath = false;

    /**
     * Twitter constructor.
     */
    public function __construct()
    {
        $this->flConfigs = AutoPosterContainer::getConfig('flConfigs');
        $this->rlDb = AutoPosterContainer::getObject('rlDb');
        $this->rlListings = AutoPosterContainer::getObject('rlListings');
        $this->reefless = AutoPosterContainer::getObject('reefless');
        $this->notifier = new Notifier('twitter');

        $this->providerConfig = [
            'ap_twitter_consumer_key',
            'ap_twitter_consumer_secret',
            'ap_twitter_token_key',
            'ap_twitter_token_secret',
        ];

        if ($this->isConfigured()) {
            $this->twitter = new TwitterOAuth(
                $this->flConfigs['ap_twitter_consumer_key'],
                $this->flConfigs['ap_twitter_consumer_secret'],
                $this->flConfigs['ap_twitter_token_key'],
                $this->flConfigs['ap_twitter_token_secret']
            );
            $this->twitter->setApiVersion('2');
        }
    }

    /**
     * Send post to the Twitter timeline
     * @param  int $listing_id - Posting listing ID
     * @return bool            - Does posting is processed successfully
     */
    public function post($listing_id)
    {
        if (!$this->isConfigured() || $this->hasBeenPosted($listing_id) || !$this->rlListings->isActive($listing_id)) {
            return false;
        }

        $rlLang = AutoPosterContainer::getObject('rlLang');
        $GLOBALS['lang'] = $rlLang->getLangBySide('frontEnd', RL_LANG_CODE);
        $listing_data = $this->rlListings->getListing($listing_id);
        $main_photo = $listing_data['Main_photo']
        ? $this->rlDb->fetch('Photo', ['Thumbnail' => $listing_data['Main_photo']], null, 1, 'listing_photos', 'row')
        : '';
        $messageBuilder = new MessageBuilder();
        $message = trim($messageBuilder->decodeMessage($listing_data, RL_LANG_CODE));

        if (!$message) {
            return false;
        }

        $link_size = 23;
        if (($summ = strlen($message) + $link_size) > 140) {
            $cutting_length = 135 - (int) $link_size;
            $message = substr($message, 0, $cutting_length) . ' ...';
        }
        $module = new AutoPosterModules();
        $GLOBALS['pages'] = $module->getAllPages();

        try {
            $data = [];

            if ($main_photo) {
                $original_photo_path = RL_FILES . $main_photo['Photo'];
                ListingMedia::prepareURL($main_photo);
                $photo_path = $this->getPhotoFilePath($main_photo['Photo'], $original_photo_path);
                $media = $this->twitter->upload('media/upload', ['media' => $photo_path]);

                if ($this->tmpPhotoPath) {
                    unlink($photo_path);
                }

                if ($media->media_id_string) {
                    $data['media'][] = $media->media_id_string;
                }
            }

            $link = $this->reefless->getListingUrl((int) $listing_id);
            $data['text'] = "{$message}\n{$link}";

            $result = $this->twitter->post('tweets', $data, true);

            if ($result->data && $result->data->id) {
                $this->afterSuccessPosting($result->data->id, $listing_id);
                return true;
            } elseif ($result->errors && $result->errors[0]->message) {
                $this->notifier->logMessage('Twitter SDK error: ' . $result->errors[0]->message);
                return false;
            }
        } catch (\Exception $e) {
            $this->notifier->logMessage('Twitter SDK error: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Get real photo path, copy remote photo (in case of active remote storage plugin)
     * as temporary local file and remove it after uploading to the VK server
     *
     * @since 1.7.0
     *
     * @param  string $photoURL  - Photo URL
     * @param  string $photoPath - Local photo path
     * @return string            - Final existing photo path
     */
    public function getPhotoFilePath(string $photoURL, string $photoPath): string
    {
        global $domain_info;

        $photo_path_data = pathinfo($photoPath);
        $photo_url_data = parse_url($photoURL);

        if (false === strpos($photo_url_data['host'], $domain_info['host'])) {
            $tmp_path = sprintf('%sautoPosterVK-%s%s.%s', RL_TMP, mt_rand(), time(), $photo_path_data['extension']);
            if ($GLOBALS['reefless']->copyRemoteFile($photoURL, $tmp_path)) {
                $this->tmpPhotoPath = true;
                return $tmp_path;
            } else {
                return $photoPath;
            }
        } else {
            return $photoPath;
        }
    }

    /**
     * After posting to the Twitter timeline trigger
     *
     * @param int $message_id - Posted message ID (value come from Twitter API request)
     * @param int $listing_id - Posted listing ID
     */
    public function afterSuccessPosting($message_id, $listing_id)
    {
        $GLOBALS['rlAutoPoster']->setSocialNetworkID($message_id, $listing_id, 'Twitter_message_id');
    }

    /**
     * Is Twitter provider has been configured successfully
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
     * Checking does listing has been posted to the Facebook wall
     *
     * @param  int  $listing_id - ID of the checking Listing
     * @return bool             - Checking status
     */
    public function hasBeenPosted($listing_id)
    {
        return !empty($this->rlDb->getOne('Twitter_message_id', "`Listing_ID` = {$listing_id}", 'autoposter_listings'));
    }

    /**
     * Is credentials are valid for Twitter API
     *
     * @param  array $settings - New settings
     * @return array           - Erros
     */
    public function isValidSettings($settings)
    {
        $errors = [];

        $connection = new TwitterOAuth(
            $settings['ap_twitter_consumer_key'],
            $settings['ap_twitter_consumer_secret'],
            $settings['ap_twitter_token_key'],
            $settings['ap_twitter_token_secret']
        );

        try {
            $api_repsonse = $connection->get('account/verify_credentials');
            if ($api_repsonse->errors) {
                foreach ($api_repsonse->errors as $error) {
                    $errors[] = $error->message;
                }
            }
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
            $this->notifier->logMessage('Twitter SDK error: ' . $e->getMessage());
        }

        return $errors;
    }

    /**
     * Returning basic configuration of all Twitter applications.
     *
     * @return object
     */
    public function getTwitterConfiguration()
    {
        $api_response = false;
        try {
            $api_response = $this->twitter->get('help/configuration');
        } catch (\Exception $e) {
            $this->notifier->logMessage('Twitter SDK error: ' . $e->getMessage());
        }

        return $api_response;
    }
    /**
     * Getting Twitter token
     */
    public function getToken()
    {}

    /**
     * @since 1.3.0
     *
     * {@inheritdoc}
     */
    public function deletePost($listingID)
    {
        $listingID = (int) $listingID;

        if (!$listingID || !($postID = $this->getTwitterPostID($listingID))) {
            return false;
        }

        try {
            $this->twitter->delete('tweets', ['id' => $postID]);
        } catch (\Exception $e) {
            $this->notifier->logMessage('Twitter SDK error on post removing: ' . $e->getMessage());
        }
    }

    /**
     * Getting Twitter post ID
     *
     * @since 1.3.0
     *
     * @param int $listingID
     *
     * @return string
     */
    public function getTwitterPostID($listingID)
    {
        $listingID = (int) $listingID;
        if (!$listingID) {
            return '';
        }

        $where = sprintf("`Listing_ID` = %d", $listingID);
        return $this->rlDb->getOne('Twitter_message_id', $where, 'autoposter_listings');
    }

    /*** DEPRECATED METHODS ***/

    /**
     * Checking is listings status is non Active
     *
     * @deprecated 1.8.0
     *
     * @param  int  $listing_id
     * @return bool             - Is listings posted
     */
    public function isListingsActive($listing_id)
    {}
}
