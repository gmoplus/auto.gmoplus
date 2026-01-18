<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : TWITTER.PHP
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

namespace Flynax\Plugins\HybridAuth\Providers;

use Flynax\Plugins\HybridAuth\Interfaces\ProviderInterface;
use Flynax\Plugins\HybridAuth\Configs as HybridAuthConfigs;
use Flynax\Plugins\HybridAuth\Traits\UrlTrait;

class Twitter implements ProviderInterface
{
    use UrlTrait;

    /**
     * Application instance
     * @var \Hybridauth\Provider\Twitter
     */
    private $app;

    /**
     * Exception error text
     *
     * @since 2.1.4
     * @var string
     */
    public $appError = '';

    /**
     * Twitter constructor.
     */
    public function __construct()
    {
        $appKey = HybridAuthConfigs::i()->getConfig('flynax_configs')['ha_twitter_app_id'];
        $appSecret = HybridAuthConfigs::i()->getConfig('flynax_configs')['ha_twitter_app_secret'];

        $configs = array(
            'callback' => $this->getRedirectURLToTheProvider('twitter'),
            'keys' => array(
                'key' => $appKey,
                'secret' => $appSecret,
            ),
            'photo_size' => '400x400',
        );

        $this->app = new \Hybridauth\Provider\Twitter($configs);
    }

    /**
     * Authenticate using Twitter account
     *
     * @return array|\Hybridauth\User\Profile|void
     */
    public function authenticate()
    {
        $userInfo = array();

        try {
            $this->app->authenticate();
            $userInfo = $this->app->getUserProfile();
        } catch (\Exception $e) {
            $this->appError = $e->getMessage();
        }

        return $userInfo;
    }

    /**
     * Checking does provided URL is a not a dummy image
     *
     * @param  string $url - URL of profile image
     * @return bool        - True if image is not dummy, false in otherwise cases
     */
    public function isNotEmptyImage($url)
    {
        if (!$url) {
            false;
        }
        $disabledPictureName = 'default_profile.png';

        return (bool) strpos($url, $disabledPictureName);
    }
}
