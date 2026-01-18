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

namespace Flynax\Plugins\HybridAuth;

use Hybridauth\User\Profile;

/**
 * Class API
 *
 * @since   2.0.0
 * @package Flynax\Plugins\HybridAuth
 */
class API
{
    /**
     * @var ProviderResolver
     */
    private $providers;

    /**
     * @var ModulesManager
     */
    private $modules;

    /**
     * @var string - Facebook Graph API url
     */
    private $fbApiBaseUrl = 'https://graph.facebook.com/v2.8/';

    /**
     * @var array - Providers configurations for API
     */
    private $configs = array(
        'photo_size' => 2000,
    );

    /**
     * @var array - Validation errors
     */
    private $errors = array();

    /**
     * @var array - Which credentials will be fetched by each provider
     */
    private $gettingCredentials = array(
        'facebook' => array('ha_facebook_app_id'),
    );

    /**
     * API constructor.
     */
    public function __construct()
    {
        $this->providers = new ProviderResolver();
        $this->modules = new ModulesManager();
    }

    /**
     * Add credential keys to the which will be fetching be provider
     *
     * @param string $provider    - Provider key
     * @param array  $credentials - Credentials key, which you want to fetch by provider
     *
     * @return API
     */
    public function withProviderCredentials($provider, $credentials)
    {
        $currentSettings = $this->getGettingCredentials();

        if ($providerKeys = $currentSettings[$provider]) {
            $providerKeys = array_merge($providerKeys, $credentials);
            $credentials = $providerKeys;
        }

        $currentSettings[$provider] = $credentials;
        $this->setGettingCredentials($currentSettings);

        return $this;
    }

    /**
     * Get all active and configured providers
     *
     * @return array
     */
    public function getActiveProviders()
    {
        $providers = $this->providers->getProviders('active', array('Order', 'Provider'));

        foreach ($providers as $key => $provider) {
            if (!$this->modules->isModuleConfigured($provider['Provider'])) {
                unset($providers[$key]);
                continue;
            }

            if ($credentials = $this->getProviderConfigurations($provider['Provider'])) {
                $providers[$key]['Credentials'] = $credentials;
            }
        }

        return $providers;
    }

    /**
     * Get provider credentials
     *
     * @param string $provider - Provider key
     * @param array  $include  - Which credentials do you want to fetch
     *
     * @return array
     */
    public function getProviderConfigurations($provider, $include = array())
    {
        $resultSettings = array();
        $gettingSettingsByProvider = $include ?: $this->gettingCredentials[$provider];

        if (!$gettingSettingsByProvider) {
            return $resultSettings;
        }

        foreach ($this->modules->getModuleSettings($provider) as $moduleSetting) {
            if (in_array($moduleSetting['Key'], $gettingSettingsByProvider)) {
                $resultSettings[$moduleSetting['Key']] = $moduleSetting['Default'];
            }
        }

        return $resultSettings;
    }

    /**
     * Return all required provider settings key and name
     *
     * @return array
     */
    public function getAllProvidersSettingNames()
    {
        $settings = array();
        $providers = $this->providers->getProviders('active', array('Provider'));

        foreach ($providers as $provider) {
            $providerSettings = $this->modules->getModuleSettings($provider['Provider']);

            foreach ($providerSettings as $providerSetting) {
                $settings[$provider['Provider']][] = array(
                    'name' => $providerSetting['name'],
                    'key' => $providerSetting['Key'],
                );
            }
        }

        return $settings;
    }

    /**
     * Handler user data. Method will register new one or login if user exist
     *
     * @param array $userData
     *
     * @return array
     */
    public function processUser($userData)
    {
        $userData = is_array($userData) ? $userData : array();

        // Generate fake email if the social network doesn't provide it
        if (empty($userData['email']) && !empty($userData['fid'])) {
            $userData['email'] = $this->providers->getUserFakeEmail($userData['fid']);
        }

        if (!$this->isValidData($userData)) {
            return array(
                'action' => 'validation',
                'status' => 'error',
                'errors' => $this->errors,
            );
        }

        $user = $this->buildUserObject($userData);
        $this->providers->addUIDRowIfNecessaryForUser($user, $userData['provider']);

        if (!$this->isUserAlreadyExist($user)) {
            if (!$userData['account_type']) {
                return array(
                    'action' => 'need_register',
                    'status' => 'success',
                );
            }

            $action = 'registered';
            $userData = $this->providers->registerNewAccount($user, $userData['account_type'], $userData['escort_lt_key']);
        } else {
            $userData = $this->providers->login($user);
            $action = $userData ? 'login' : 'need_verify';
        }

        return array(
            'action' => $action,
            'status' => 'success',
            'user_data' => $userData,
        );
    }

    /**
     * Checking, is user exist by two parameters: 1 UID and 2 Email
     * Helper of the $ProviderResolver->isUserAlreadyExist() method
     *
     * @param Profile $userData - User info in HybridAuth format
     *
     * @return bool
     */
    public function isUserAlreadyExist($userData)
    {
        if ($this->providers->isUserAlreadyExist($userData)) {
            return true;
        }

        $where = "`Mail` = '{$userData->email}'";

        return (bool) $GLOBALS['rlDb']->getOne('ID', $where, 'accounts');
    }

    /**
     * Checking does provided user data from App is valid
     *
     * @param array $dataFromApp
     *
     * @return bool
     */
    private function isValidData($dataFromApp)
    {
        $errors = array();
        $requiredFields = array(
            'fid',
            'email',
            'provider',
            'first_name',
        );

        if (!$this->modules->isModuleConfigured($dataFromApp['provider'])) {
            $this->errors[] = 'The provider is not properly configured';

            return false;
        }

        foreach ($requiredFields as $field) {
            if (!key_exists($field, $dataFromApp)) {
                $errors[] = sprintf('"%s" is not exist in array', $field);
            }
        }

        foreach ($dataFromApp as $key => $item) {
            if (in_array($key, $requiredFields) && !$item) {
                $errors[] = sprintf('"%s" is required', $key);
            }
        }

        if ($dataFromApp['account_type']) {
            if (is_numeric($dataFromApp['account_type'])) {
                $where = "`ID` = {$dataFromApp['account_type']}";
                if (!$GLOBALS['rlDb']->getOne('ID', $where, 'account_types')) {
                    $errors[] = sprintf("Account type with {%d} ID doesn't exist", $dataFromApp['account_type']);
                }
            } else {
                $errors[] = "'account_type' field should be numeric";
            }
        }

        $this->errors = $errors;

        return empty($errors);
    }

    /**
     * Build Hybrid Auth user object from provided data
     *
     * @param array $dataFromApp
     *
     * @return Profile
     */
    private function buildUserObject($dataFromApp)
    {
        $user = new Profile();
        $activeProvider = $this->providers->getProvider($dataFromApp['provider']);

        $user->identifier = $dataFromApp['fid'];
        $user->email = $dataFromApp['email'];

        $photoUrl = $this->fbApiBaseUrl . $user->identifier . '/picture?width=';
        $photoUrl .= $this->configs['photo_size'] . '&height=' . $this->configs['photo_size'];
        $user->photoURL = $photoUrl;

        $user->firstName = $dataFromApp['first_name'];
        $user->lastName = $dataFromApp['last_name'];
        $user->emailVerified = $dataFromApp['verified'];
        $user->displayName = trim("{$user->firstName} {$user->lastName}");

        $user->data['provider'] = $dataFromApp['provider'];
        $user->data['is_real_image'] = method_exists($activeProvider, 'isNotEmptyImage') ? !$activeProvider->isNotEmptyImage($user->photoURL) : true;

        return $user;
    }

    /**
     * @return array
     */
    public function getGettingCredentials()
    {
        return $this->gettingCredentials;
    }

    /**
     * @param array $gettingCredentials
     */
    public function setGettingCredentials($gettingCredentials)
    {
        $this->gettingCredentials = $gettingCredentials;
    }
}
