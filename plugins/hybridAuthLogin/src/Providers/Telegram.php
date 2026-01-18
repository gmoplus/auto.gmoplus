<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: TELEGRAM.PHP
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
use Hybridauth\Provider\Telegram as HybridAuthTelegramProvider;

/**
 * Telegram provider
 * @since 2.2.0
 */
class Telegram extends AbstractProviderAdapter
{
    /**
     * @var HybridAuthTelegramProvider
     */
    private $app;

    /**
     * @var string
     */
    public $appError = '';

    /**
     * @var string
     */
    protected $bot = '';

    /**
     * @var string
     */
    protected $token = '';

    /**
     * Telegram constructor
     */
    public function __construct()
    {
        $this->bot   = HybridAuthConfigs::i()->getConfig('flynax_configs')['ha_telegram_bot'];
        $this->token = HybridAuthConfigs::i()->getConfig('flynax_configs')['ha_telegram_bot_token'];

        $config = [
            'callback' => $this->getRedirectURLToTheProvider('telegram'),
            'keys'     => [
                'id'     => $this->bot,
                'secret' => $this->token,
            ],
        ];

        $this->app = new HybridAuthTelegramProvider($config);
    }

    /**
     * Authenticate using Telegram account
     *
     * @return array|\Hybridauth\User\Profile|void
     */
    public function authenticate()
    {
        $userInfo = [];

        try {
            if ($_SESSION['ha_temp_telegram_storage']) {
                $userInfo = unserialize($_SESSION['ha_temp_telegram_storage']);
            } else {
                $this->app->authenticate();
                $userInfo = $this->app->getUserProfile();
                $_SESSION['ha_temp_telegram_storage'] = serialize($userInfo);
            }
        } catch (\Exception $e) {
            $this->appError = $e->getMessage();
        }

        return $userInfo;
    }

    /**
     * Send message to Telegram user
     *
     * @param string $message Message body
     * @param int    $userID  User ID to send message to
     * @return bool           Return true if message sent successfully, false otherwise
     */
    public function sendMessage($message, $userID): bool
    {
        if (!$message || !$userID) {
            return false;
        }

        $message = str_replace(['<br>', '<br/>', '<br />', '</p>', '<p>'], "\n", $message); // Update new lines to supported format
        $message = strip_tags($message, '<b><i><a><code><pre>'); // Remove all non supported tags
        $message = "✉️ " . $message; // Add mail icon before message

        $url     = "https://api.telegram.org/bot{$this->token}/sendMessage";
        $configs = [
            'chat_id'    => $userID,
            'text'       => $message,
            'parse_mode' => 'HTML',
        ];

        $response = $this->app->httpClient->request($url, 'GET', $configs);
        $data = $response ? json_decode($response, true) : [];

        if ($data['ok'] ?? false) {
            return true;
        }

        $GLOBALS['rlDebug']->logger("HybridAuth: Telegram send message error: " . $data['error_code'] . ' - ' . $data['description']);
        return false;
    }
}
