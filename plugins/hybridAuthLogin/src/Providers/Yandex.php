<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : YANDEX.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2024
 *	https://www.flynax.com
 *
 ******************************************************************************/

namespace Flynax\Plugins\HybridAuth\Providers;

use Flynax\Plugins\HybridAuth\Configs as HybridAuthConfigs;

/**
 * Yandex provider
 * @since 2.1.5
 */
class Yandex extends AbstractProviderAdapter
{
    /**
     * Application instance
     * @var \Hybridauth\Provider\Yandex
     */
    private $app;

    /**
     * Exception error text
     *
     * @var string
     */
    public $appError = '';

    /**
     * Yandex constructor.
     */
    public function __construct()
    {
        $appID = HybridAuthConfigs::i()->getConfig('flynax_configs')['ha_yandex_app_id'];
        $appSecret = HybridAuthConfigs::i()->getConfig('flynax_configs')['ha_yandex_app_secret'];

        $configs = array(
            'callback' => $this->getRedirectURLToTheProvider('yandex'),
            'keys' => array(
                'id' => $appID,
                'secret' => $appSecret
            ),
            'photo_size' => 400,
        );

        $this->app = new \Hybridauth\Provider\Yandex($configs);
    }

    /**
     * Authenticate using yandex account
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
