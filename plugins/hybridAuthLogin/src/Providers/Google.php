<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : GOOGLE.PHP
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

use Flynax\Plugins\HybridAuth\Configs as HybridAuthConfigs;

class Google extends AbstractProviderAdapter
{
    private $app;

    /**
     * Exception error text
     *
     * @since 2.1.4
     * @var string
     */
    public $appError = '';

    /**
     * Google constructor.
     */
    public function __construct()
    {
        $appID = HybridAuthConfigs::i()->getConfig('flynax_configs')['ha_google_app_key'];
        $appSecret = HybridAuthConfigs::i()->getConfig('flynax_configs')['ha_google_app_id'];

        $configs = array(
            'callback' => $this->getRedirectURLToTheProvider('google'),
            'keys' => array(
                'id' => $appID,
                'secret' => $appSecret,
            ),
            'scope' => 'https://www.googleapis.com/auth/userinfo.profile ' .
                        'https://www.googleapis.com/auth/userinfo.email',
            'access_type' => 'offline',
            'approval_prompt' => 'force',
            'photo_size' => 2000,
        );
        $this->app = new \Hybridauth\Provider\Google($configs);
    }

    /**
     * Authenticate using Google account
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
