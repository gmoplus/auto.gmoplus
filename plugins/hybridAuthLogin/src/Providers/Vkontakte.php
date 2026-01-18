<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: VKONTAKTE.PHP
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
 *  Flynax Classifieds Software 2025 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

namespace Flynax\Plugins\HybridAuth\Providers;

use Flynax\Plugins\HybridAuth\Configs as HybridAuthConfigs;
use Flynax\Plugins\HybridAuth\Adapters\VkontakteAdapter;

class Vkontakte extends AbstractProviderAdapter
{
    /**
     * Application instance
     * @var \Hybridauth\Provider\Vkontakte
     */
    protected $vkObject;

    /**
     * Exception error text
     *
     * @since 2.1.4
     * @var string
     */
    public $appError = '';

    /**
     * Vkontakte constructor.
     */
    public function __construct()
    {
        $appID     = HybridAuthConfigs::i()->getConfig('flynax_configs')['ha_vkontakte_app_id'];
        $appSecret = HybridAuthConfigs::i()->getConfig('flynax_configs')['ha_vkontakte_app_key'];
        $appKey    = HybridAuthConfigs::i()->getConfig('flynax_configs')['ha_vkontakte_app_secret'];

        if (!filter_input($_SERVER['REQUEST_METHOD'] === 'POST' ? INPUT_POST : INPUT_GET, 'code')) {
            $_SESSION['ha_vk_code_verifier'] = $GLOBALS['reefless']->generateHash(43);
        }

        $configs = array(
            'callback' => $this->getRedirectURLToTheProvider('vkontakte'),
            'keys' => array(
                'id'     => $appID,
                'secret' => $appSecret,
                'key'    => $appKey,
            ),
            'photo_size' => 480,
            'endpoints' => [
                'api_base_url'     => 'https://id.vk.ru/oauth2/',
                'authorize_url'    => 'https://id.vk.ru/authorize',
                'access_token_url' => 'https://id.vk.ru/oauth2/auth',
            ],
            'authorize_url_parameters' => [
                'code_challenge'        => $this->hashString($_SESSION['ha_vk_code_verifier']),
                'code_challenge_method' => 'S256',
            ],
        );

        $this->vkObject = new VkontakteAdapter($configs);
    }

    /**
     * Authenticate using Vkontakte account
     *
     * @return array|\Hybridauth\User\Profile|void
     */
    public function authenticate()
    {
        $userInfo = array();

        try {
            $this->vkObject->authenticate();
            $userInfo = $this->vkObject->getUserProfile();
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
        $excludeImageRegex = '/camera_[0-9]{2,3}.png/m';

        preg_match($excludeImageRegex, $url, $matches, 0);
        array_filter($matches);

        return !empty($matches);
    }

    /**
     * Creates a hash string using the given code.
     *
     * @since 2.1.7
     *
     * @param  string $code Code to be hashed.
     * @return string       Hashed string.
     */
    private function hashString($code): string {
        return str_replace(
            '=',
            '',
            strtr(base64_encode(hash('sha256', $code, true)), '+/', '-_')
        );
    }
}
