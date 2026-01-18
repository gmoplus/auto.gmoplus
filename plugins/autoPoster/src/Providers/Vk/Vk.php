<?php


/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: VK.PHP
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

use Flynax\Utils\ListingMedia;
use Autoposter\AutoPosterContainer;
use Autoposter\AutoPosterModules;
use Autoposter\Interfaces\ProviderInterface;
use Autoposter\MessageBuilder;
use Autoposter\Notifier;

/**
 * Class Vk
 * @since 1.6.0
 */
class Vk implements ProviderInterface
{
    /**
     * @since 1.9.0
     */
    const API_VERSION = '5.199';

    /**
     * @var \rlDb
     */
    private $rlDb;

    /**
     * @var array Flynax configuration array
     */
    private $flConfigs;

    /**
     * @var int Token
     */
    private $token;

    /**
     * @var string Post listings to
     */
    private  $post_to;

    /**
     * @var int Owner id page/group
     */
    private $owner_id;

    /**
     * @since 1.9.0
     * @var int App ID
     */
    private $client_id;

    /**
     * @since 1.9.0
     * @var array Flynax phrases data
     */
    private $lang;

    /**
     * @var \rlListings
     */
    private $rlListings;

    /**
     * @var \reefless
     */
    private $reefless;

    /**
     * @var object
     */
    private $notifier;

    /**
     * Flag for the TMP photo file usage
     *
     * @since 1.7.0
     * @var boolean
     */
    private $tmpPhotoPath = false;

    /**
     * @since 1.9.1
     * @var string
     */
    private $apiUrl = 'https://api.vk.ru/method/';

    /**
     * @since 1.9.1
     * @var string
     */
    private $vkIdUrl = 'https://id.vk.ru/';

    /**
     * @since 1.9.0
     * @var bool
     */
    private $tokenIsChecked = false;

    /**
     * Vk constructor.
     */
    public function __construct()
    {
        $this->flConfigs = AutoPosterContainer::getConfig('flConfigs');
        $this->rlDb = AutoPosterContainer::getObject('rlDb');
        $this->rlListings = AutoPosterContainer::getObject('rlListings');
        $this->reefless = AutoPosterContainer::getObject('reefless');
        $this->notifier = new Notifier('vk');
        $this->post_to = $this->flConfigs['ap_vk_post_to'];
        $this->owner_id = $this->flConfigs['ap_vk_owner_id'];
        $this->client_id = $this->flConfigs['ap_vk_client_id'];
        $this->lang = AutoPosterContainer::getConfig('lang');
    }

    /**
     * Send post to the Vk
     * @param  int $listing_id Posting listing ID
     * @return bool            Does posting is processed successfully
     */
    public function post($listing_id)
    {
        if ($this->hasBeenPosted($listing_id) || !$this->rlListings->isActive($listing_id)) {
            return false;
        }

        $rlLang = AutoPosterContainer::getObject('rlLang');
        $GLOBALS['lang'] = $rlLang->getLangBySide('frontEnd', RL_LANG_CODE);
        $listing_data = $this->rlListings->getListing($listing_id, true);
        $main_photo = $listing_data['Main_photo']
        ? $this->rlDb->fetch('Photo', ['Thumbnail' => $listing_data['Main_photo']], null, 1, 'listing_photos', 'row')
        : '';

        $messageBuilder = new MessageBuilder();
        $message = trim($messageBuilder->decodeMessage($listing_data, RL_LANG_CODE));
        $attach_data = [];
        $attacStr = '';

        if (!$message) {
            return false;
        }

        $module = new AutoPosterModules();
        $GLOBALS['pages'] = $module->getAllPages();

        try {
            if ($main_photo) {
                $original_photo_path = RL_FILES . $main_photo['Photo'];
                ListingMedia::prepareURL($main_photo);
                $photo_path = $this->getPhotoFilePath($main_photo['Photo'], $original_photo_path);
                $urlUpload = $this->getUploadImageUrl();
                $resultUpload = $this->uploadImageToServer($urlUpload, $photo_path);
                $mediaData = $this->saveImageToVk($resultUpload);

                if ($mediaData) {
                    if ($mediaData->error) {
                        $this->notifier->logMessage('Vk API Exception: Unable to upload photo, error occurs: ' . $mediaData->error->error_msg);
                    } else {
                        array_push($attach_data, $mediaData->response[0]->id, $mediaData->response[0]->owner_id);
                    }
                }

                $attacStr = 'photo';
                $attacStr .= $attach_data[1] . '_' . $attach_data[0] . ',' . $listing_data['listing_link'];
            } else {
                $attacStr = $listing_data['listing_link'];
            }

            if ($this->post_to === 'to_group') {
                $this->owner_id = '-'.$this->owner_id;
            }

            $params = array(
                'access_token' => $this->getToken(),
                'owner_id'     => $this->owner_id,
                'message'      => $message,
                'attachments'  => $attacStr,
                'v'            => self::API_VERSION
            );

            if ($this->post_to === 'to_group') {
                $params['from_group'] = '1';
            }

            $resultPost =  json_decode(file_get_contents(
                $this->apiUrl . 'wall.post?' . http_build_query($params)
            ));

            if ($resultPost->response->post_id) {
                $this->afterSuccessPosting($resultPost->response->post_id, $listing_id);
                return true;
            } else {
                $this->notifier->logMessage('Vk API: Unable to post the message, error occurs: ' . $resultPost->error->error_msg);
                return false;
            }
        } catch (\Exception $e) {
            $this->notifier->logMessage('Vk error posting');
        }
        return false;
    }

    /**
     * Get real photo path, copy remote photo (in case of active remote storage plugin)
     * as temporary local file and remove it after uploading to the VK server
     *
     * @since 1.7.0
     *
     * @param  string $photoURL  Photo URL
     * @param  string $photoPath Local photo path
     * @return string            Final existing photo path
     */
    public function getPhotoFilePath(string $photoURL, string $photoPath): string
    {
        global $domain_info;

        $photo_path_data = pathinfo($photoPath);
        $photo_url_data = parse_url($photoURL);

        if (false === strpos($photo_url_data['host'], $domain_info['host'])) {
            $tmp_path = sprintf('%sautoPosterVK-%s%s.%s', RL_TMP, mt_rand(), time(), $photo_path_data['extension']);
            if ($this->reefless->copyRemoteFile($photoURL, $tmp_path)) {
                $this->tmpPhotoPath = true;
                return $tmp_path;
            } else {
                return $photoPath;
            }
        } else {
            return $photoPath;
        }
    }

    /**
     * Get server url  vk for upload image
     *
     * @return string|null Url for upload
     */
    public function getUploadImageUrl()
    {
        $url_info = json_decode(file_get_contents(
            $this->apiUrl . 'photos.getWallUploadServer'
                . '?access_token=' . $this->getToken()
                . ($this->post_to === 'to_group' ? '&group_id=' . $this->owner_id : '')
                . '&v=' . self::API_VERSION
        ));

        if ($url_info && isset($url_info->response->upload_url)) {
            return $url_info->response->upload_url;
        } elseif ($url_info->error && isset($url_info->error->error_msg)) {
            $this->notifier->logMessage('VK API Exception: Unable to get uploadImageUrl, error occurs: ' . $url_info->error->error_msg);
        } else {
            $this->notifier->logMessage('VK API Exception: Unable to get uploadImageUrl, empty response received.');
        }

        return null;
    }

    /**
     * Upload image resource to server vk
     * @param  $url       Url get from vk server
     * @param  $pathImage Path image
     * @return array      Media data
     */
    public function uploadImageToServer($url, $pathImage)
    {
        if (!empty($url)) {
            $file_new = new \CURLFile($pathImage);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, array(
                'file1' => $file_new
            ));
            $result = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if ($this->tmpPhotoPath) {
                unlink($pathImage);
            }

            return $result;
        }
        return null;
    }

    /**
     * Save image on Vk
     *
     * @param array $uploadData
     * @return object|null
     */
    public function saveImageToVk($uploadData)
    {
        if (!empty($uploadData['server'])) {
            $result = json_decode(file_get_contents(
                $this->apiUrl .'photos.saveWallPhoto'
                    . '?access_token=' . $this->getToken()
                    . ($this->post_to === 'to_group' ? '&group_id=' :  '&user_id=') . $this->owner_id
                    . '&server=' . $uploadData['server']
                    . '&photo=' . stripslashes($uploadData['photo'])
                    . '&hash=' . $uploadData['hash']
                    . '&v=' . self::API_VERSION
            ));

            return $result;
        }
        return null;
    }

    /**
     * After posting to the Vk timeline trigger
     *
     * @param int $message_id Posted message ID
     * @param int $listing_id Posted listing ID
     */
    public function afterSuccessPosting($message_id, $listing_id)
    {
        $GLOBALS['rlAutoPoster']->setSocialNetworkID($message_id, $listing_id, 'Vk_message_id');
    }

    /**
     * Checking does listing has been posted to the Vk wall
     *
     * @param  int  $listing_id ID of the checking Listing
     * @return bool             Checking status
     */
    public function hasBeenPosted($listing_id)
    {
        return !empty($this->rlDb->getOne('Vk_message_id', "`Listing_ID` = {$listing_id}", 'autoposter_listings'));
    }

    /**
     * Getting vk token
     */
    public function getToken()
    {
        if (!$this->token && $token = $this->rlDb->getOne('Token', "User_ID = 1 AND `Module` = 'vk'", 'autoposter_tokens')) {
            $this->token = $token;

            $this->checkAndRefreshToken();
        }

        return $this->token;
    }

    /**
     * Checking token and refreshing if it is expired
     *
     * @since 1.9.0
     * @return void
     */
    public function checkAndRefreshToken()
    {
        if ($this->token && !$this->tokenIsChecked) {
            $client   = new \GuzzleHttp\Client();
            $response = json_decode($client->request('GET', $this->apiUrl . 'photos.getWallUploadServer', [
                'query' => [
                    'access_token'  => $this->token,
                    'v'             => self::API_VERSION
                ]
            ])->getBody());

            if ($response->error && $response->error->error_code == 5) {
                $tokenData = $this->rlDb->fetch(
                    ['Refresh_token', 'Device_id'],
                    ['User_ID' => 1, 'Module' => 'vk'], null, 1, 'autoposter_tokens', 'row'
                );

                if ($tokenData['Refresh_token'] && $tokenData['Device_id']) {
                    $response = json_decode($client->request('POST', $this->vkIdUrl . 'oauth2/auth', [
                        'form_params' => [
                            'grant_type'    => 'refresh_token',
                            'refresh_token' => $tokenData['Refresh_token'],
                            'client_id'     => $this->client_id,
                            'device_id'     => $tokenData['Device_id'],
                        ]
                    ])->getBody());

                    if ($response->access_token && $response->refresh_token) {
                        $this->token = $response->access_token;

                        $this->updateToken(
                            $response->access_token,
                            1,
                            $response->refresh_token,
                            $tokenData['Device_id']
                        );
                    } else {
                        $this->token = null;
                        $this->removeToken(1);
                    }
                }
            }

            $this->tokenIsChecked = true;
        }
    }

    /**
     * Delete Vk post
     *
     * @param int $listingID
     * @return void
     */
    public function deletePost($listingID)
    {
        $listingID = (int) $listingID;
        if (!$listingID || !$postID = $this->getVkPostID($listingID)) {
            return false;
        }

        $params = [
            'post_id'      => $postID,
            'access_token' => $this->getToken(),
            'owner_id'     => ($this->post_to === 'to_group' ? '-' : '') . $this->owner_id,
            'v'            => self::API_VERSION
        ];

        file_get_contents($this->apiUrl . 'wall.delete?' . http_build_query($params));
    }

    /**
     * Getting Vk post ID
     *
     * @param int $listingID
     * @return string
     */
    public function getVkPostID($listingID)
    {
        $listingID = (int) $listingID;
        if (!$listingID) {
            return '';
        }

        $where = sprintf("`Listing_ID` = %d", $listingID);
        return $this->rlDb->getOne('Vk_message_id', $where, 'autoposter_listings');
    }

    /**
     * Getting permission asking url to the VK
     *
     * @since 1.9.0
     * @return string Redirect URL
     */
    public function getRedirectUrl(): string
    {
        if (!$_SESSION['ap_vk_code_verifier']) {
            $_SESSION['ap_vk_code_verifier'] = $this->reefless->generateHash(43);
            $_SESSION['ap_vk_state']         = $this->reefless->generateHash(32);
        }

        $params = [
            'response_type'         => 'code',
            'client_id'             => $this->client_id,
            'code_challenge'        => $this->hashString($_SESSION['ap_vk_code_verifier']),
            'code_challenge_method' => 'S256',
            'scope'                 => implode(' ', ['photos', 'wall', 'groups']),
            'redirect_uri'          => $this->getOAuthLink(),
            'state'                 => $_SESSION['ap_vk_state'],
        ];

        return $this->vkIdUrl . 'authorize?' . http_build_query($params);
    }

    /**
     * Handle VK callback
     *
     * @since 1.9.0
     *
     * @return bool Does all handled fine
     */
    public function handleRedirect(): bool
    {
        if (!$_REQUEST['code'] || !$_REQUEST['device_id']) {
            $this->notifier
                ->logMessage('VK ID error: Code or Device ID not received')
                ->toEditWithError('VK ID error: Code or Device ID not received');
            return false;
        }

        $this->success();
        return true;
    }

    /**
     * VK ID provided successful response
     *
     * @since 1.9.0
     *
     * @return void
     */
    private function success(): void
    {
        // Exchange authorization code for access and refresh token
        $url = $this->vkIdUrl . 'oauth2/auth';

        $params = [
            'form_params' => [
                'grant_type'    => 'authorization_code',
                'code_verifier' => $_SESSION['ap_vk_code_verifier'],
                'redirect_uri'  => $this->getOAuthLink(),
                'code'          => $_REQUEST['code'],
                'client_id'     => $this->client_id,
                'device_id'     => $_REQUEST['device_id'],
                'state'         => $_REQUEST['state'],
            ],
            'headers' => [
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
        ];

        try {
            $client   = new \GuzzleHttp\Client();
            $response = json_decode($client->request('POST', $url, $params)->getBody());

            if ($response->error && $response->error_description) {
                $this->notifier
                    ->logMessage('VK ID error: ' . $response->error_description)
                    ->toEditWithError('VK ID error: ' . $response->error_description);
            } elseif ($response->access_token
                && $response->scope
                && (false === strpos($response->scope, 'photo')
                    || ($this->post_to === 'to_group' && false === strpos($response->scope, 'groups'))
                    || ($this->post_to === 'to_wall' && false === strpos($response->scope, 'wall'))
                )
            ) {
                $this->notifier
                    ->logMessage('VK ID scope error: The app doesn\'t have the required permissions (photo, wall, groups)')
                    ->toEditWithError('VK ID scope error: The app doesn\'t have the required permissions (photo, wall, groups)');
            } else {
                $this->saveToken($response->access_token, $response->refresh_token, $_REQUEST['device_id']);
                $this->notifier->toEditWithMessage($this->lang['ap_vk_logged']);
            }

            unset($_SESSION['ap_vk_code_verifier'], $_SESSION['ap_vk_state']);
        } catch (\Throwable $th) {
            $error = $th->getMessage();
            $this->notifier
                ->logMessage('VK ID error: ' . $error)
                ->toEditWithError('VK ID error: ' . $error);
        }
    }

    /**
     * Saving token to the database
     *
     * @since 1.9.0
     *
     * @param  string $token        VK API access token
     * @param  string $refreshToken VK API refresh token
     * @param  string $deviceID     VK API device ID
     * @return bool                 Does saved successfully
     */
    public function saveToken($token, $refreshToken, $deviceID): bool
    {
        $userID = 1;
        if (!$this->isTokenExist($userID)) {
            $insert_data = [
                'User_ID'       => $userID,
                'Module'        => 'vk',
                'Token'         => $token,
                'Refresh_token' => $refreshToken,
                'Device_id'     => $deviceID,
                'Token_date'    => 'NOW()',
            ];

            if ($this->rlDb->insertOne($insert_data, 'autoposter_tokens')) {
                $this->token = $token;
            }
            return true;
        }

        return $this->updateToken($token, $userID, $refreshToken, $deviceID);
    }

    /**
     * Update existing token
     *
     * @since 1.9.0
     *
     * @param  string $token        VK API access token
     * @param  int    $user_id      User ID on which token will save
     * @param  string $refreshToken VK API refresh token
     * @param  string $deviceID     VK API device ID
     * @return bool                 Does saving process was successfully
     */
    public function updateToken($token, $user_id, $refreshToken, $deviceID): bool
    {
        return $this->rlDb->updateOne([
            'fields' => [
                'Token'         => $token,
                'Token_date'    => 'NOW()',
                'Refresh_token' => $refreshToken,
                'Device_id'     => $deviceID,
            ],
            'where' => [
                'User_ID' => $user_id,
                'Module'  => 'vk',
            ],
        ], 'autoposter_tokens');
    }

    /**
     * Is token exist for the provided user
     *
     * @since 1.9.0
     *
     * @param  int $userID
     * @return bool         Is token exist
     */
    public function isTokenExist($userID)
    {
        return $this->rlDb->getOne('Token', "`User_ID` = {$userID} AND `Module` = 'vk'", 'autoposter_tokens') ? true : false;
    }

    /**
     * Removing token of provided administrator
     *
     * @since 1.9.0
     *
     * @param int $userID
     */
    public function removeToken($userID)
    {
        if ($this->isTokenExist($userID) && $token = $this->getToken()) {
            $this->rlDb->query(
                "DELETE FROM `{db_prefix}autoposter_tokens`
                 WHERE `Token` = '{$token}' AND `Module` = 'vk'"
            );
        }
    }

    /**
     * Fetch necessary debugging data of the current token
     *
     * @since 1.9.0
     * @return array Token debugging data
     */
    public function getTokenData()
    {
        return ['token' => substr($this->getToken(), 0, 32) . '...'];
    }

    /**
     * Creates a hash string using the given code
     *
     * @since 1.9.0
     *
     * @param  string $code Code to be hashed
     * @return string       Hashed string
     */
    public function hashString($code): string {
        return str_replace(
            '=',
            '',
            strtr(base64_encode(hash('sha256', $code, true)), '+/', '-_')
        );
    }

    /**
     * Getting correct link to make oAuth request from the site
     *
     * @since 1.9.0
     *
     * @return string
     */
    public function getOAuthLink()
    {
        return RL_URL_HOME . ADMIN . '/index.php';
    }

    /*** DEPRECATED METHODS ***/

    /**
     * @deprecated 1.9.1
     * @since 1.9.0
     * @var string
     */
    private $photoServerApiUrl = 'https://api.vk.com/method/photos.getWallUploadServer';

    /**
     * Checking is listings status is non Active
     *
     * @deprecated 1.8.0
     *
     * @param  int  $listing_id
     * @return bool Is listings posted
     */
    public function isListingsActive($listing_id)
    {}
}
