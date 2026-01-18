<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : MAILRU.PHP
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
use Hybridauth\User\Profile;
use Hybridauth\Exception\UnexpectedApiResponseException;

/**
 * Mailru provider
 * @since 2.1.5
 */
class Mailru extends AbstractProviderAdapter
{
    /**
     * Application instance
     * @var \Hybridauth\Provider\Mailru
     */
    private $app;

    /**
     * Exception error text
     *
     * @var string
     */
    public $appError = '';

    /**
     * Mailru constructor.
     */
    public function __construct()
    {
        $appID = HybridAuthConfigs::i()->getConfig('flynax_configs')['ha_mailru_app_id'];
        $appSecret = HybridAuthConfigs::i()->getConfig('flynax_configs')['ha_mailru_app_secret'];

        $configs = array(
            'callback' => $this->getRedirectURLToTheProvider('mailru'),
            'keys' => array(
                'id' => $appID,
                'secret' => $appSecret
            ),
            'scope' => 'userinfo',
            'photo_size' => 400,
            'endpoints' => [
                'api_base_url' => 'https://oauth.mail.ru/userinfo',
                'authorize_url' => 'https://oauth.mail.ru/login',
                'access_token_url' => 'https://oauth.mail.ru/token'
            ]
        );

        $this->app = new \Hybridauth\Provider\Mailru($configs);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $token_data = $this->app->getAccessToken();
        $param = [
            'access_token' => $token_data['access_token'],
        ];

        $data = $this->app->apiRequest('', 'GET', $param);

        if (!$data->id) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $userProfile->identifier = $data->id;
        $userProfile->email = $data->email;
        $userProfile->firstName = $data->first_name;
        $userProfile->lastName = $data->last_name;
        $userProfile->displayName = $data->name;
        $userProfile->photoURL = $data->image;
        $userProfile->profileURL = '';
        $userProfile->gender = $data->gender;
        $userProfile->age = '';

        return $userProfile;
    }

    /**
     * Authenticate using mailru account
     *
     * @return array|\Hybridauth\User\Profile|void
     */
    public function authenticate()
    {
        $userInfo = array();

        try {
            $this->app->authenticate();
            $userInfo = $this->getUserProfile();
        } catch (\Exception $e) {
            $this->appError = $e->getMessage();
        }

        return $userInfo;
    }
}
