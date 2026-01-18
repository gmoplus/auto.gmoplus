<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: API.PHP
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

namespace Flynax\Plugin\WordPressBridge\WordPressAPI;

use Flynax\Plugin\WordPressBridge\Controllers\BlocksController;
use Flynax\Plugin\WordPressBridge\Request;

class API
{
    /**
     * @var string
     */
    private $group = 'wp-content/plugins/flynax-bridge/request.php?route=';

    /**
     * @var string
     */
    private $wpUrl = '';

    /**
     * @var \Flynax\Plugin\WordPressBridge\WordPressAPI\Token
     */
    private $tokenManager;

    /**
     * @var \rlConfig
     */
    private $rlConfig;

    /**
     * @var \rlDb
     */
    private $rlDb;

    /**
     * API constructor.
     */
    public function __construct()
    {
        $this->wpUrl = $GLOBALS['config']['wp_path'];
        $this->tokenManager = new Token();

        $this->rlConfig = wbMake('rlConfig');
        $this->rlDb = wbMake('rlDb');
    }

    /**
     * Save URL to the WordPress installation where FlynaxBridge plugin is located
     *
     * @param string $url
     *
     * @return bool
     */
    public function saveWpUrl($url)
    {
        if (!$url) {
            return false;
        }

        return $this->rlConfig->setConfig('fl_wp_root', $url)
        ? $this->wpUrl = $url
        : false;
    }

    /**
     * Setter of the wpUrl property
     *
     * @param string $url - URL to the WordPress site
     */
    public function setWPUrl($url)
    {
        $this->wpUrl = $url;
    }

    /**
     * Get 'WordPress site url' configuration from Flynax DB
     *
     * @return string - Configuration value
     */
    public function getWPUrl()
    {
        return $this->rlDb->getOne('Default', "`Key` = 'fl_wp_root'", 'config');
    }

    /**
     * Checking does provided URL is valid URL and FlynaxBridge plugin successfully installed there
     *
     * @param string $url - URL to the WordPress installation where FlynaxBridge plugin is suppose to be installed
     *
     * @return array|bool - Array with errors if something went wrong or true if everything is OK
     */
    public function isValidUrl($url)
    {
        global $lang;

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return array(
                'message' => $lang['wpb_wrong_url'],
            );
        }

        $endpoint = 'status';
        $url      = rtrim($url, '/');
        $apiUrl   = sprintf('%s/%s%s', $url, $this->group, $endpoint);

        $result = Request::get($apiUrl);

        return $result->status == 200 && $result->response['data']['message'] && !$result->response['errors']
        ? true
        : ['message' => $lang['wpb_not_found']];
    }

    /**
     * Send 'handshake' request and make sure that FlynaxBridge plugin is installed and awaiting to accept requests
     *
     * @throws \Exception
     */
    public function handShake($username, $password)
    {
        if (!$username || !$password) {
            return false;
        }

        $result = $this->callAPI(
            'handshake',
            'get',
            ['username' => $username, 'password' => $password]
        );

        if ($result->status == 200) {
            if ($result->response['data'] && isset($result->response['data']['token'])) {
                $token = $result->response['data']['token'];
                $tokenManager = new Token();

                $tokenManager->saveWpToken($token);
                $this->sendFlToken($tokenManager->generateFlToken());

                return true;
            } elseif ($result->response['errors'] && is_array($result->response['errors']['handshake-error'])) {
                return $result->response['errors']['handshake-error'][0];
            }
        } else {
            return false;
        }
    }

    /**
     * Send WordPress bridge token to the FlynaxBridge plugin
     *
     * @param string $flToken
     *
     * @return bool
     */
    public function sendFlToken($flToken)
    {
        if (!$flToken) {
            return false;
        }

        $data = array(
            'wp_token' => $this->tokenManager->getWpToken(),
            'fl_token' => $flToken,
            'fl_path' => RL_URL_HOME,
        );

        $res = $this->callAPI('fl-token', 'post', $data);

        if ($res->status == 200) {
            $this->tokenManager->saveFlToken($flToken);
            BlocksController::updateCache();
        }
    }

    /**
     * Call FlynaxBridge plugin API endpoint
     *
     * @param  string $endpoint - FlynaxBridge REST API endpoint
     * @param  string $type     - Request type: get, post
     * @param  array  $data     - Data, which you want to send with request
     * @param  bool   $isAuth   - Should request send auth data
     *
     * @return \stdClass
     */
    public function callAPI($endpoint, $type, $data = array(), $isAuth = false)
    {
        $wpUrl  = $this->getWPUrl() ?: $this->wpUrl;
        $url    = trim($wpUrl, '/');
        $apiUrl = sprintf('%s/%s%s', $url, $this->group, trim($endpoint, '/'));
        $type   = strtolower($type);

        switch ($type) {
            case 'get':
                return Request::get($apiUrl, $data, $isAuth);
            case 'post':
                return Request::post($apiUrl, $data, $isAuth);
        }
    }

    /**
     * Get Recent posts of the WordPress
     *
     * @param int $number - Posts limit
     *
     * @return array - array with WordPress posts
     */
    public function getRecentPosts($number = 4)
    {
        $res = $this->callAPI('recent-posts', 'get', array('limit' => $number));
        if ($res->status == 200 && !empty($res->response['data']['data'])) {
            $posts = $res->response['data']['data'];
            foreach ($posts as $key => $post) {
                $posts[$key]['excerpt'] = $this->trimWords($post['excerpt'], 20);
            }

            return $posts;
        }

        return array();
    }

    /**
     * Cut specified number of words from text
     *
     * @param string $string - Text from which you want to trim words
     * @param int    $length - Number of words
     *
     * @return string - Modified text
     */
    public function trimWords($string, $length = 5)
    {
        if (!$string) {
            return '';
        }

        return count(explode(' ', $string)) > $length
        ? implode(' ', array_slice(explode(' ', $string), 0, $length)) . '...'
        : $string;
    }

    public static function updateWpWidgetsCache()
    {
        $self = new self();
        $self->callAPI('update-listings-cache', 'get');
    }
}
