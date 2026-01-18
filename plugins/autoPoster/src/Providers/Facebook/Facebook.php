<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: FACEBOOK.PHP
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

namespace Autoposter\Providers;

use Autoposter\AutoPosterContainer;
use Autoposter\AutoPosterModules;
use Autoposter\Interfaces\ProviderInterface;
use Autoposter\MessageBuilder;
use Autoposter\Notifier;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

class Facebook implements ProviderInterface
{
    /**
     * @var \rlDb
     */
    private $rlDb;

    /**
     * @var \rlListings
     */
    private $rlListings;

    /**
     * @var string - User information
     */
    private $user;

    /**
     * @var string - User token
     */
    private $token;

    /**
     * @var string - Facebook application API key
     */
    private $api_key;

    /**
     * @var string - Facebook application API secret
     */
    private $api_secret;

    /**
     * @var \Facebook\Facebook
     */
    private $facebookObj;

    /**
     * @var array - Flynax configurations
     */
    private $flConfigs;

    /**
     * @var Notifier
     */
    private $notifier;

    /**
     * @var array - Flynax phrases data
     */
    private $lang;

    /**
     * @since 1.7.1
     *
     * @var $GLOBALS['rlConfig']
     */
    private $rlConfig;

    /**
     * Facebook constructor.
     */
    public function __construct()
    {
        $this->lang = AutoPosterContainer::getConfig('lang');
        $configs = AutoPosterContainer::getConfig('flConfigs');
        $this->flConfigs = $configs;
        $this->api_secret = $configs['ap_facebook_app_secret'];
        $this->api_key = $configs['ap_facebook_app_id'];
        if ($this->api_secret && $this->api_key) {
            $facebook = new \Facebook\Facebook([
                'app_id' => $this->api_key,
                'app_secret' => $this->api_secret,
                'default_graph_version' => 'v18.0',
            ]);

            $this->facebookObj = $facebook;
        }

        $this->rlDb = AutoPosterContainer::getObject('rlDb');
        $this->rlListings = AutoPosterContainer::getObject('rlListings');
        $this->rlConfig = AutoPosterContainer::getObject('rlConfig');
        $this->notifier = new Notifier('facebook');
    }

    /**
     * Post to user wall
     *
     * @param $listing_id
     */
    public function post2Wall($listing_id)
    {
        $this->facebookObj->setDefaultAccessToken($this->getToken());
        $this->_post($listing_id, 'wall');
    }

    /**
     * Post to user page
     *
     * @param $listing_id
     */
    public function post2Page($listing_id)
    {
        $this->facebookObj->setDefaultAccessToken($this->getPageToken());
        $this->_post($listing_id, 'page');
    }

    /**
     * Post to user group
     *
     * @param $listing_id
     */
    public function post2Group($listing_id)
    {
        $this->facebookObj->setDefaultAccessToken($this->getToken());
        $this->_post($listing_id, 'group');
    }

    /**
     * Sending posting request to the Facebook API
     *
     * @param int    $listing_id - Listing ID
     * @param string $type       - Type of the post {wall, page, group}
     *
     * @throws FacebookSDKException
     */
    public function _post($listing_id, $type)
    {
        $messageBuilder = new MessageBuilder();
        $listing_info = $this->rlListings->getListing($listing_id, true);
        $configs = AutoPosterContainer::getConfig('flConfigs');
        $module = new AutoPosterModules();
        $GLOBALS['pages'] = $module->getAllPages();
        $link = htmlspecialchars_decode($listing_info['listing_link']);

        $postData = [
            'link' => $link,
            'message' => trim($messageBuilder->decodeMessage($listing_info, RL_LANG_CODE)),
        ];

        if (!$postData['message']) {
            return;
        }

        switch ($type) {
            case 'wall':
                $api = "/me/feed";
                break;
            case 'page':
                $page_id = $configs['ap_facebook_subject_id'];
                $api = "/{$page_id}/feed";
                break;
            case 'group':
                $group_id = $configs['ap_facebook_subject_id'];
                $api = "/{$group_id}/feed";
                break;
        }

        $postingRequest = $this->facebookObj->request('POST', $api, $postData);

        try {
            $messageID = null;
            foreach ($this->facebookObj->sendBatchRequest(['post-to-feed' => $postingRequest]) as $response) {
                if ($response->isError()) {
                    $e = $response->getThrownException();
                    $this->notifier->logMessage('Facebook error in sendBatchRequest method response: ' . $e->getMessage());
                } else {
                    $body = $response->getDecodedBody();
                    if ($body && !empty($body['id'])) {
                        $messageID = $body['id'];
                    }
                }
            }

            if ($messageID) {
                $this->onSuccessfullyPosted($listing_id, $messageID);
            }
        } catch (FacebookResponseException $e) {
            $this->notifier->logMessage('Facebook Response Exception error on the post process: ' . $e->getMessage());
        } catch (FacebookSDKException $e) {
            $this->notifier->logMessage('Facebook SDK Exception error on the post process: ' . $e->getMessage());
        }
    }

    /**
     * Getting Facebook token
     */
    public function getToken()
    {
        if ($token = $this->rlDb->getOne('Token', "User_ID = 1 AND `Module` = 'facebook'", 'autoposter_tokens')) {
            return $token;
        }

        return $this->token;
    }

    /**
     * Getting page token
     *
     * @return string - Page token
     */
    public function getPageToken()
    {
        if (!$this->pageTokenExist(1)) {
            try {
                $this->facebookObj->setDefaultAccessToken($this->getToken());
                $page_id = AutoPosterContainer::getConfig('flConfigs')['ap_facebook_subject_id'];

                $response = $this->facebookObj->get('/me/accounts');
                $data = $response->getDecodedBody();

                foreach ($data['data'] as $page) {
                    if ($page['id'] == $page_id && $page['access_token']) {
                        $this->savePageToken($page['access_token']);
                        return $page['access_token'];
                    }
                }
            } catch (FacebookResponseException $e) {
                $this->notifier->logMessage('Facebook Response error on the getting token process: ' . $e->getMessage());
            } catch (FacebookSDKException $e) {
                $this->notifier->logMessage('Facebook Response error on the getting token process: ' . $e->getMessage());
            } catch (\Exception $e) {
                $this->notifier->logMessage('Autoposter error on the getting token process: ' . $e->getMessage());
            }
        }

        return $this->rlDb->getOne('Page_token', "User_ID = 1 AND `Module` = 'facebook'", 'autoposter_tokens');
    }

    /**
     * Validate page_id configuration
     *
     * @param  string $page_id - Page ID configuration value
     * @return array           - Validation response
     */
    public function pageValidate($page_id)
    {
        $post_to = $_POST['post_config']['ap_facebook_post_to'];
        $response = array();
        if (!$page_id && $post_to == 'to_page') {
            $response['status'] = 'ERROR';
            $response['message'] = AutoPosterContainer::getConfig('lang')['ap_field_empty'];

            return $response;
        }
        $response['status'] = 'OK';

        return $response;
    }

    /**
     * Handle Facebook callback
     *
     * @return bool - Does all handled fine
     */
    public function handleRedirect()
    {
        if ($_REQUEST['error_code']) {
            $this->error($_REQUEST['error_code'], $_REQUEST['error_message'], $_REQUEST['state']);
            return false;
        }

        $this->success();
        return true;
    }

    /**
     * Facebook received successful response
     */
    public function success(): void
    {
        $helper = $this->facebookObj->getRedirectLoginHelper();

        try {
            $this->saveToken($helper->getAccessToken()->getValue(), 'user');

            if ($this->flConfigs['ap_facebook_post_to'] === 'to_page') {
                $this->facebookObj->setDefaultAccessToken($this->getToken());
                $response = $this->facebookObj->get('/me/accounts');
                $data = $response->getDecodedBody();

                /**
                 * System save first allowed page only
                 *
                 * @todo - Give ability save all allowed pages
                 *       - and give ability to admin create posts to several Facebook pages
                 */
                foreach ($data['data'] as $page) {
                    if ($page['id'] && $page['access_token']) {
                        $this->rlConfig->setConfig('ap_facebook_subject_id', $page['id']);
                        $this->savePageToken($page['access_token']);
                        break;
                    }
                }
            }

            $this->notifier->toEditWithMessage($this->lang['ap_facebook_logged']);
        } catch (FacebookResponseException $e) {
            $error = $e->getMessage();
            $this->notifier
                ->logMessage('Facebook Response error on the post process: ' . $error)
                ->toEditWithMessage($error);
        } catch (FacebookSDKException $e) {
            $error = $e->getMessage();
            $this->notifier
                ->logMessage('Facebook SDK error on the post process: ' . $error)
                ->toEditWithMessage($error);
        }
    }

    public function error($code, $message, $state)
    {
        //TODO: handle error redirect
    }

    /**
     * Getting user of the Facebook instance
     *
     * @return array - Flynax user information
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Setting user to the Facebook instance
     *
     * @param arrat $user - Flynax user information
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * Saving token to the database
     *
     * @param $token      - Token value
     * @param $token_type - Token type {user, page}
     * @return bool       - Does saved successfully
     */
    public function saveToken($token, $token_type)
    {
        $user_id = 1;
        if (!$this->isTokenExist($user_id)) {
            $token_type = strtolower($token_type);
            $insert_data['User_ID'] = $user_id;
            $insert_data['Module'] = 'facebook';
            switch ($token_type) {
                case 'user':
                    $insert_data['Token'] = $token;
                    $insert_data['Token_date'] = 'NOW()';
                    break;
                case 'post':
                    break;
                default:
                    return false;
                    break;
            }

            if ($this->rlDb->insertOne($insert_data, 'autoposter_tokens')) {
                $this->token = $token;
            }
            return true;
        }

        $this->updateToken($token, $user_id);
        return true;
    }

    /**
     * Update existing token
     *
     * @param  string $token   - Facebook user token
     * @param  int    $user_id - User ID on which token will save
     * @return bool            - Does saving process was succesfull
     */
    public function updateToken($token, $user_id)
    {
        $update = array(
            'fields' => array(
                'Token' => $token,
                'Token_date' => 'NOW()',
            ),
            'where' => array(
                'User_ID' => $user_id,
                'Module' => 'facebook',
            ),
        );
        $this->rlDb->updateOne($update, 'autoposter_tokens');

        return true;
    }

    /**
     * Is token exist for the provided user
     *
     * @param  int $user_id
     * @return bool         - Is token exist
     */
    public function isTokenExist($user_id)
    {
        return $this->rlDb->getOne('Token', "`User_ID` = {$user_id} AND `Module` = 'facebook'", 'autoposter_tokens') ? true : false;
    }

    /**
     * Is Page token exist for the provided user
     *
     * @param  int $user_id
     * @return bool         - Is token exist
     */
    public function pageTokenExist($user_id)
    {
        return $this->tokenExistingCheck('page', $user_id);
    }

    public function savePageToken($token)
    {
        $updateData = array(
            'fields' => array(
                'Page_token' => $token,
            ),
            'where' => array(
                'User_ID' => 1,
                'Module' => 'facebook',
            ),
        );

        $this->rlDb->updateOne($updateData, 'autoposter_tokens');
    }

    /**
     * Token existin checking method
     *
     * @param  string $type    - Token type: {user, page}
     * @param  int    $user_id - Checking user ID
     * @return bool            - Is token exist
     */
    public function tokenExistingCheck($type, $user_id)
    {
        $type = strtolower($type);
        $find = 'ID';
        switch ($type) {
            case 'user':
                $find = 'Token';
                break;
            case 'page':
                $find = 'Page_token';
                break;
        }

        return $this->rlDb->getOne($find, "`User_ID` = {$user_id} AND `Module` = 'facebook'", 'autoposter_tokens') ? true : false;
    }

    /**
     * Setting token to the Facebook intance
     *
     * @param string $token - Facebook user token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Getting permission asking url to the Facebook
     *
     * @return string - Redirect URL
     */
    public function getRedirectUrl()
    {
        $permissions = ['pages_manage_posts', 'pages_show_list', 'pages_read_engagement'];

        if ($this->flConfigs['ap_facebook_post_to'] === 'to_group') {
            $permissions = ['groups_access_member_info', 'publish_to_groups'];
        }

        $helper = $this->facebookObj->getRedirectLoginHelper();
        $redirect_url = $this->getOAuthLink();

        return $helper->getLoginUrl($redirect_url, $permissions);
    }

    /**
     * Getting correct link to make oAuth request from the site
     *
     * @since 1.1.0
     * @return string
     */
    public function getOAuthLink()
    {
        $siteBase = $this->getCorrectRequestScheme();

        $args['controller'] = 'auto_poster';
        $args['action'] = 'edit';
        $args['module'] = 'facebook';
        $args['method'] = 'handleRedirect';

        return $siteBase . ADMIN . '/index.php?' . http_build_query($args);
    }

    /**
     * Get site base with correct HTTP scheme: {http or https}
     * @since  1.1.0
     * @return string - Correct site base
     */
    public function getCorrectRequestScheme()
    {
        $installedPlugins = $GLOBALS['plugins'] ?: $GLOBALS['aHooks'];

        if (array_key_exists('sslProtection', $installedPlugins) && $this->flConfigs['secure_admin_panel']) {
            return str_replace('http', 'https', RL_URL_HOME);
        }

        return RL_URL_HOME;
    }

    /**
     * Fetch necessary debugging data of the current token
     *
     * @return array - token debugging data
     */
    public function getTokenData()
    {
        $tokenData = [];
        try {
            $OAuth2Client = $this->facebookObj->getOAuth2Client();
            $metaData = $OAuth2Client->debugToken($this->getToken());
            $tokenData['application'] = $metaData->getApplication();
            $now = new \DateTime();
            $expires = $metaData->getExpiresAt();
            $tokenData['expiredAt'] = $this->lang['ap_never'];
            if ($expires) {
                $interval = $now->diff($expires);
                $in_hours = round(($expires->getTimestamp() - $now->getTimestamp()) / 3600);
                $tokenData['expiredAt'] = $interval->format('%m month(s), %d day(s), %h hour(s)');
            }

        } catch (FacebookResponseException $e) {
            $this->notifier->logMessage('Facebook Response error on the post process: ' . $e->getMessage());
        } catch (FacebookSDKException $e) {
            $this->notifier->logMessage('Facebook SDK error on the post process: ' . $e->getMessage());
        }

        return $tokenData;
    }

    /**
     * Sending post to the Facebook wall or page
     *
     * @param  int $listing_id - Sending listing ID
     * @return bool            - Posting status
     */
    public function post($listing_id)
    {
        if (!$this->canIPost() || $this->hasBeenPosted($listing_id) || !$this->rlListings->isActive($listing_id)) {
            return false;
        }

        switch ($this->flConfigs['ap_facebook_post_to']) {
            case 'to_page':
                $this->post2Page($listing_id);
                break;
            case 'to_wall':
                $this->post2Wall($listing_id);
                break;
            case 'to_group':
                $this->post2Group($listing_id);
                break;
            default:
                return false;
                break;
        }

        return true;
    }

    /**
     * After successfully posted to Facebook wall trigger
     *
     * @since 1.8.0 - Renamed parameters $listing_id, $posted_listing_id to $listingID, $postID
     * @since 1.2.0
     *
     * @param int $listingID - ID of the posted Listing
     * @param int $postID    - Unique facebook post ID which are return after successfully posting process
     */
    public function onSuccessfullyPosted($listingID, $postID)
    {
        $GLOBALS['rlAutoPoster']->setSocialNetworkID($postID, $listingID, 'Facebook_message_id');
    }

    /**
     * Getting Facebook post ID
     *
     * @since 1.3.0
     *
     * @param int $listing_id
     * @return mixed
     */
    public function getFacebookPostID($listing_id)
    {
        if (!$listing_id) {
            return '';
        }

        $where = sprintf("`Listing_ID` = %d", $listing_id);
        return $this->rlDb->getOne('Facebook_message_id', $where, 'autoposter_listings');
    }

    /**
     * Checking does listing has been posted to the Facebook wall
     *
     * @param  int  $listing_id - ID of the checking Listing
     * @return bool             - Checking status
     */
    public function hasBeenPosted($listing_id)
    {
        $hasPosted = $this->rlDb->getOne('Facebook_message_id', "`Listing_ID` = {$listing_id}", 'autoposter_listings');
        return !empty($hasPosted);
    }

    /**
     * Can Facebook provider post to the wall or page
     *
     * @return bool - Checking result
     */
    public function canIPost(): bool
    {
        $token = $this->flConfigs['ap_facebook_post_to'] === 'to_page' ? $this->getPageToken() : $this->getToken();
        return ($this->flConfigs['ap_facebook_app_secret'] && $this->flConfigs['ap_facebook_app_id'] && $token);
    }

    /**
     * Removing token of provided administrator
     *
     * @param  int $admin_id
     */
    public function removeToken($admin_id)
    {
        if ($this->isTokenExist($admin_id)) {
            $token = $this->getToken();
            $sql = "DELETE FROM `{db_prefix}autoposter_tokens` WHERE `Token` = '{$token}' AND `Module` = 'facebook'";
            $this->rlDb->query($sql);
        }
    }

    /**
     * @since 1.3.0
     *
     * {@inheritdoc}
     */
    public function deletePost($listingID)
    {
        $listingID = (int) $listingID;
        if (!$listingID || !$postID = $this->getFacebookPostID($listingID)) {
            return false;
        }

        $post_to = $this->flConfigs['ap_facebook_post_to'];
        $token = $post_to == 'to_page' ? $this->getPageToken() : $this->getToken();
        $this->facebookObj->setDefaultAccessToken($token);
        $apiEndpoint = "/{$postID}";

        try {
            $this->facebookObj->delete($apiEndpoint);
        } catch (FacebookResponseException $e) {
            $this->notifier->logMessage('Facebook Response error on the post delete process: ' . $e->getMessage());
        } catch (FacebookSDKException $e) {
            $this->notifier->logMessage('Facebook SDK error on the post delete process: ' . $e->getMessage());
        }
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
