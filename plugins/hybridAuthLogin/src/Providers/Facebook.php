<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : FACEBOOK.PHP
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

use Flynax\Plugins\HybridAuth\Providers\AbstractProviderAdapter;
use Flynax\Plugins\HybridAuth\Configs as HybridAuthConfigs;

class Facebook extends AbstractProviderAdapter
{
    /**
     * @var \Hybridauth\Provider\Facebook
     */
    private $app;

    /**
     * @var string - Facebook application key
     */
    private $key;

    /**
     * @var string - Facebook application secret
     */
    private $secret;

    /**
     * Exception error text
     *
     * @since 2.1.4
     * @var string
     */
    public $appError = '';

    /**
     * Facebook constructor.
     */
    public function __construct()
    {
        $appID = HybridAuthConfigs::i()->getConfig('flynax_configs')['ha_facebook_app_id'];
        $appSecret = HybridAuthConfigs::i()->getConfig('flynax_configs')['ha_facebook_app_secret'];

        $configs = array(
            'callback' => $this->getRedirectURLToTheProvider('facebook'),
            'keys' => array(
                'id' => $appID,
                'secret' => $appSecret,
            ),
            'scope' => 'email',
            'photo_size' => 400,
        );

        $this->app = new \Hybridauth\Provider\Facebook($configs);
    }

    /**
     * Fix the picture url by adding the access token
     *
     * @since 2.1.4
     *
     * @param  string $url - URL of profile image
     * @return bool        - True if image is not available, false in otherwise cases
     */
    public function isNotEmptyImage(&$url)
    {
        if (!$url) {
            return true;
        }

        $token_data = json_decode($this->app->exchangeAccessToken(), true);

        if (!is_array($token_data) || !$token_data['access_token']) {
            return true;
        }

        $url .= '&access_token=' . $token_data['access_token'];

        return false;
    }

    /**
     * Authentificate user through facebook
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
}
