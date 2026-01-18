<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : ODNOKLASSNIKI.PHP
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
 * Odnoklassniki provider
 * @since 2.1.5
 */
class Odnoklassniki extends AbstractProviderAdapter
{
    /**
     * Application instance
     * @var \Hybridauth\Provider\Odnoklassniki
     */
    private $app;

    /**
     * Exception error text
     *
     * @var string
     */
    public $appError = '';

    /**
     * Odnoklassniki constructor.
     */
    public function __construct()
    {
        $appID = HybridAuthConfigs::i()->getConfig('flynax_configs')['ha_odnoklassniki_app_id'];
        $appSecret = HybridAuthConfigs::i()->getConfig('flynax_configs')['ha_odnoklassniki_app_secret'];
        $appKey = HybridAuthConfigs::i()->getConfig('flynax_configs')['ha_odnoklassniki_app_key'];

        $configs = array(
            'callback' => $this->getRedirectURLToTheProvider('odnoklassniki'),
            'keys' => array(
                'id' => $appID,
                'secret' => $appSecret,
                'key' => $appKey
            ),
            'scope' => 'VALUABLE_ACCESS;GET_EMAIL',
            'photo_size' => 400,
        );

        $this->app = new \Hybridauth\Provider\Odnoklassniki($configs);
    }

    /**
     * Authenticate using odnoklassniki account
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
