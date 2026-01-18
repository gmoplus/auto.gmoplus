<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: VKONTAKTEADAPTER.PHP
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

namespace Flynax\Plugins\HybridAuth\Adapters;

use Flynax\Plugins\HybridAuth\Configs as HybridAuthConfigs;

use Hybridauth\Provider\Vkontakte;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data\Collection;
use Hybridauth\User\Profile;

/**
 * Adapter class for the API VK ID to allow for the sending of additional parameters
 * @since 2.1.7
 */
class VkontakteAdapter extends Vkontakte
{
    /**
     * {@inheritdoc}
     */
    protected function exchangeCodeForAccessToken($code)
    {
        $this->tokenExchangeParameters['code'] = $code;

        if ($_SESSION['ha_vk_code_verifier']) {
            $this->tokenExchangeParameters['code_verifier'] = $_SESSION['ha_vk_code_verifier'];
        }

        if ($deviceID = filter_input($_SERVER['REQUEST_METHOD'] === 'POST' ? INPUT_POST : INPUT_GET, 'device_id')) {
            $this->tokenExchangeParameters['device_id'] = $deviceID;
        }

        $response = $this->httpClient->request(
            $this->accessTokenUrl,
            $this->tokenExchangeMethod,
            $this->tokenExchangeParameters,
            $this->tokenExchangeHeaders
        );

        $this->validateApiResponse('Unable to exchange code for API access token');

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest(
            'user_info',
            'POST',
            ['client_id' => HybridAuthConfigs::i()->getConfig('flynax_configs')['ha_vkontakte_app_id']]
        );

        if (property_exists($response, 'error')) {
            throw new UnexpectedApiResponseException($response->error->error_msg);
        }

        $data = new Collection($response->user);

        if (!$data->exists('user_id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $userProfile->identifier  = $data->get('user_id');
        $userProfile->email       = $data->get('email');
        $userProfile->firstName   = $data->get('first_name');
        $userProfile->lastName    = $data->get('last_name');
        $userProfile->displayName = '';
        $userProfile->photoURL    = $data->get('avatar');

        if ($this->config->get('photo_size')) {
            $userProfile->photoURL = str_replace(
                'cs=50x50',
                "cs={$this->config->get('photo_size')}x{$this->config->get('photo_size')}",
                $userProfile->photoURL
            );
        }

        // Handle b-date.
        if ($data->get('birthday')) {
            $bday = explode('.', $data->get('birthday'));
            $userProfile->birthDay = (int)$bday[0];
            $userProfile->birthMonth = (int)$bday[1];
            $userProfile->birthYear = (int)$bday[2];
        }

        $userProfile->data = [
            'education' => $data->get('education'),
        ];

        $screen_name = static::URL . ($data->get('screen_name') ?: 'id' . $data->get('user_id'));
        $userProfile->profileURL = $screen_name;

        switch ($data->get('sex')) {
            case 1:
                $userProfile->gender = 'female';
                break;

            case 2:
                $userProfile->gender = 'male';
                break;
        }

        return $userProfile;
    }
}
