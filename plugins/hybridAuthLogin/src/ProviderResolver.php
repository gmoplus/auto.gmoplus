<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: PROVIDERRESOLVER.PHP
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

use Flynax\Components\Image\Uploader\Uploader;
use Hybridauth\User\Profile;
use rlNotice;

class ProviderResolver
{
    /**
     * @var
     */
    private $activeProvider;

    /**
     * @var string
     */
    private $provider;

    /**
     * @var Uid
     */
    public $uid;

    /**
     * @var \rlAccount
     */
    protected $rlAccount;

    /**
     * @var \rlDb
     */
    protected $rlDb;

    /**
     * @var bool - Does it Escort installation
     */
    protected $isEscortInstallation = false;

    /**
     * @var array - Flynax languages array
     */
    protected $lang = array();

    /**
     * ProviderResolver constructor.
     * @param string $provider
     * @return  Interfaces\ProviderInterface|bool
     */
    public function __construct($provider = '')
    {
        if ($provider) {
            $this->getProvider($provider);
            $this->provider = $provider;
        }

        $this->uid = new Uid();
        $this->rlAccount = hybridAuthMakeObject('rlAccount');
        $this->rlDb = hybridAuthMakeObject('rlDb');
        $this->lang = $GLOBALS['lang'];
    }

    /**
     * Setter of the activeProvider property
     *
     * @param $provider
     */
    public function setActiveProvider($provider)
    {
        $this->activeProvider = $provider;
    }

    /**
     * Getter of the activeProvider property
     *
     * @return Interfaces\ProviderInterface|bool
     */
    public function getActiveProvider()
    {
        return $this->activeProvider;
    }

    /**
     * Get provider object by available provider name
     *
     * @param $providerName - Provider Name
     * @return Interfaces\ProviderInterface|bool
     */
    public function getProvider($providerName)
    {
        $class = '\\Flynax\\Plugins\\HybridAuth\\Providers\\' . ucfirst($providerName);

        if (class_exists($class)) {
            $providerObject = new $class();
            $this->setActiveProvider($providerObject);
            $this->provider = $providerName;

            return $providerObject;
        }

        return false;
    }

    /**
     * Does provided user is already exist in the system
     *
     * @param Profile $user - Hybrid Auth profile object
     *
     * @return bool
     */
    public function isUserAlreadyExist($user)
    {
        $uid = $user->identifier;
        $user = $this->uid->get($uid);

        return (bool) $user;
    }


    /**
     * Login user
     *
     * @param Profile $user - Hybrid Auth profile object
     *
     * @return bool
     */
    public function login($user)
    {
        $configs = Configs::i()->getConfig('flynax_configs');
        $uid = $user->identifier;
        $uidInfo = $this->uid->get($uid);
        $accountID = (int) $uidInfo['Account_ID'];

        $sql = "SELECT `ID`, `Password`, `Username`, `Mail` FROM `" . RL_DBPREFIX . "accounts` WHERE `ID` = {$accountID}";
        $accountData = $this->rlDb->getRow($sql);
        $userOrEMail = $configs['account_login_mode'] == 'username'
            ? $accountData['Username']
            : $accountData['Mail'];

        if (!$this->isUserVerified($user)) {
            $_SESSION['ha_non_verified'] = array(
                'email' => $userOrEMail,
                'provider' => $user->data['provider'],
            );
            return false;
        };


        $loginResult = $this->rlAccount->login($userOrEMail, $accountData['Password'], true, true);
        $loginNotice = array(
            'message' => $this->lang['notice_logged_in'],
            'type' => 'notice',
        );

        if (!$this->rlAccount->isLogin()) {
            $loginNotice = array(
                'message' => reset($loginResult),
                'type' => 'error',
            );
            $GLOBALS['reefless']->loginAttempt();
        } else {
            $userData = $this->rlAccount->getProfile((int) $accountData['ID']);

            unset(
                $userData['Password'],
                $userData['Password_hash'],
                $userData['Password_tmp']
            );
        }

        /** @var rlNotice $rlNotice */
        $rlNotice = hybridAuthMakeObject('rlNotice');
        $rlNotice->saveNotice($loginNotice['message'], $loginNotice['type']);

        return $userData ?? false;
    }

    /**
     * Is provided user is verified in the system
     *
     * @param Profile $user - Hybrid Auth profile object
     *
     * @return bool
     */
    public function isUserVerified($user)
    {
        if (!$user) {
            return false;
        }

        $uid = $user->identifier;
        $uidInfo = $this->uid->get($uid);

        return (bool) $uidInfo['Verified'];
    }

    /**
     * Register new user in the system by provided hybrid auth user object
     *
     * @param  Profile $user           - Hybrid Auth user object
     * @param  string  $accountType    - Account type key
     * @param  string  $listingTypeKey - Listing type key
     *
     * @return bool - Does registration process successful
     */
    public function registerNewAccount($user, $accountType = '', $listingTypeKey = '')
    {
        if (empty($user)) {
            return false;
        }

        $configs = Configs::i()->getConfig('flynax_configs');
        $isAvatarUploadingEnabled = (bool)$configs['ha_enable_avatar_uploading'];
        hybridAuthMakeObject('rlListingTypes');

        $accountName = $user->firstName || $user->lastName
        ? trim($user->firstName . ' ' . $user->lastName)
        : $user->displayName;
        $accountTypes = hybridAuthMakeObject('rlAccountTypes');
        $choosenAccountTypeKey = $accountType ?: $_SESSION['ha_choosen_account_type'];
        $userAccountType = (int) $accountTypes->types[$choosenAccountTypeKey]['ID'];

        $newUser = $this->rlAccount->quickRegistration(
            $accountName,
            $user->email,
            $planId = 0,
            $userAccountType,
            $listingTypeKey
        );

        if ($newUser) {
            $newAccountId = $newUser[2];
            $newUIDRow = array(
                'Account_ID' => $newAccountId,
                'Provider' => $this->provider,
                'UID' => $user->identifier,
                'Verified' => '1',
            );
            $this->uid->add($newUIDRow);
            $_SESSION['account']['ID'] = $newAccountId;

            if ($isAvatarUploadingEnabled && $user->photoURL && $user->data['is_real_image']) {
                $uploader = new Uploader();
                $uploader->uploadImageToAccount($user->photoURL, $newAccountId);
            }

            HybridAuth::afterSuccessRegistration($newUser, $user->email);
            $newUser = $this->rlAccount->getProfile($newAccountId);

            unset(
                $newUser['Password'],
                $newUser['Password_hash'],
                $newUser['Password_tmp']
            );

            return $newUser;
        }

        return false;
    }

    /**
     * Does user with this email is already exist on the DB
     *
     * @param  string $email - Email of the checking user
     * @return int
     */
    public function isUserWithSameEmailExist($email)
    {
        if (!$email) {
            return 0;
        }

        $where = "`Mail` = '{$email}' AND `Status` != 'trash'";

        return (int) $this->rlDb->getOne('ID', $where, 'accounts');
    }

    /**
     * @since 2.0.0
     *
     * @param Profile $user
     * @param string  $incomingProvider
     */
    public function addUIDRowIfNecessaryForUser(Profile $user, $incomingProvider)
    {
        if ($accountID = $this->isUserWithSameEmailExist($user->email)) {
            $shouldUserVerify = $GLOBALS['config']['ha_enable_password_synchronization']
                && !$this->uid->isAlreadyVerified($accountID);

            $newUIDRow = array(
                'Account_ID' => $accountID,
                'Provider' => $incomingProvider,
                'UID' => $user->identifier,
                'Verified' => (int) !$shouldUserVerify,
            );

            $this->uid->add($newUIDRow);
        }
    }

    /**
     * Checking is user with provided email active
     *
     * @param  string $email - Account email
     * @return bool
     */
    public function isUserActive($email)
    {
        if (!$email) {
            return false;
        }

        $where = "`Mail` = '{$email}'";

        return $this->rlDb->getOne('Status', $where, 'accounts') == 'active';
    }

    /**
     * Add provider info to the Database
     *
     * @param  string $provider - Provider name
     * @param  string $status   - Provider status
     * @return bool             - Does adding process is successful
     */
    public function addToDb($provider = '', $status = 'active')
    {
        if (!$provider) {
            return false;
        }

        $availableStatus = array('active', 'approval');
        $status = in_array($status, $availableStatus) ? $status : 'active';
        $sql = "SELECT MAX(`Order`) as `max` FROM `" . RL_DBPREFIX . "ha_providers` ";
        $maxOrderPosition = (int) $this->rlDb->getRow($sql, 'max');

        $newProvider = array(
            'Provider' => $provider,
            'Order' => $maxOrderPosition + 1,
            'Status' => $status,
        );
        return (bool) $this->rlDb->insertOne($newProvider, 'ha_providers');
    }

    /**
     * Get providers by status.
     *      If status didn't provided, method is return all providers
     * @since 2.0.0  - Parameter added: $fields
     *
     * @param  string $status  - Provider status
     * @param  array  $fields  - Fields what you want to get from the database
     * @return array           - Providers
     */
    public function getProviders($status = '', $fields = array())
    {
        $where = !empty($fields) ? sprintf("`%s`", implode("`,`", $fields)) : '*';

        $sql = "SELECT {$where} FROM `" . RL_DBPREFIX . "ha_providers` ";
        if ($status) {
            $sql .= "WHERE `Status` = '{$status}' ";
        }
        $sql .= ' ORDER BY `Order`';

        return $this->rlDb->getAll($sql);
    }

    /**
     * Return total count of providers
     *
     * @return int
     */
    public function getTotalProvidersCount()
    {
        $sql = "SELECT COUNT(`ID`) as `count` FROM `" . RL_DBPREFIX . "ha_providers`";
        $sqlResult = $this->rlDb->getRow($sql);

        return (int) $sqlResult['count'];
    }

    /**
     * Activate specific provider
     *
     * @param  string $provider - Provider, which you want to activate
     * @return bool             - Provider changing status result
     */
    public function makeProviderActive($provider)
    {
        if (!$provider) {
            return false;
        }

        $provider = (string) $provider;
        $sql = "UPDATE `" . RL_DBPREFIX . "ha_providers` SET `Status` = 'active' ";
        $sql .= "WHERE `Provider` = '{$provider}'";

        return $this->rlDb->query($sql);
    }

    /**
     * Getter of the isEscortInstallation property
     *
     * @return bool
     */
    public function isEscortInstallation()
    {
        return $this->isEscortInstallation;
    }

    /**
     * Setter of the isEscortInstallation property
     * @param bool $isEscortInstallation - Is this Flynax is Escort
     */
    public function setIsEscortInstallation($isEscortInstallation)
    {
        $this->isEscortInstallation = (bool) $isEscortInstallation;
    }

    /**
     * Generate fake email for user
     *
     * @since 2.2.0
     *
     * @param  string      $identifier - User identifier from the provider
     * @return string|null
     */
    public function getUserFakeEmail($identifier)
    {
        if (!$identifier) {
            return;
        }

        if (!empty($GLOBALS['domain_info']['domain'])) {
            $domain = ltrim($GLOBALS['domain_info']['domain'], '.');
        } else {
            $domainInfo = parse_url(RL_URL_HOME);
            $domain     = preg_replace('/^(www.)?/', '', $domainInfo['host']);
        }

        return "user{$identifier}@{$domain}";
    }

    /*** DEPRECATED ***/
    /**
     * @deprecated 2.1.4
     * @var \rlActions
     */
    protected $rlActions;
}
