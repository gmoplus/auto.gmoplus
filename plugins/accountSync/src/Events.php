<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.7.0
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: EVENTS.PHP
 *
 *	The software is a commercial product delivered under single, non-exclusive,
 *	non-transferable license for one domain or IP address. Therefore distribution,
 *	sale or transfer of the file in whole or in part without permission of Flynax
 *	respective owners is considered to be illegal and breach of Flynax License End
 *	User Agreement.
 *
 *	You are not allowed to remove this information from the file without permission
 *	of Flynax respective owners.
 *
 *	Flynax Classifieds Software 2022 |  All copyrights reserved.
 *
 *	https://www.flynax.com/
 *
 ******************************************************************************/

namespace Flynax\Plugins\AccountSync;

use Flynax\Plugins\AccountSync\Adapters\AccountTypesAdapter;
use Flynax\Plugins\AccountSync\Adapters\UserAdapter;
use Flynax\Plugins\AccountSync\Models\MetaData;
use Flynax\Plugins\AccountSync\Models\Token;

class Events
{
    /**
     * Event fire right after succeed connection between two domains
     *
     * @param string $ourDomain
     * @param string $theirDomain
     */
    public static function afterSuccessfulSync($ourDomain, $theirDomain)
    {
        $api = new API();
        $meta = new MetaData();

        $response = $api->withDomain($theirDomain)->auth()->get('account/types');
        if ($response->status == 200 && $response->body['code_phrase'] == 'success_account_types') {
            $theirAccountTypes = $response->body['account_types'];
            $meta->set($theirDomain, MetaData::META_ACCOUNT_TYPES, $theirAccountTypes);
        }

        $ourAccountTypes = AccountTypesAdapter::getAllTypes();
        $meta->set($ourDomain, MetaData::META_ACCOUNT_TYPES, $ourAccountTypes);
    }

    /**
     * Event fire after users password has been changed
     *
     * @param string $email       - Account email of which password is changing
     * @param string $newPassword - New password
     */
    public static function afterPasswordChanged($email, $newPassword)
    {
        if (!$email) {
            return;
        }

        $api = new API();
        $tokenManager = new Token();
        $endpoint = sprintf('accounts/%s/password', $email);
        $allTokens = $tokenManager->getAll(true);
        $data['password'] = $newPassword;

        foreach ($allTokens as $token) {
            $api->withDomain($token['Domain'])->auth()->post($endpoint, $data);
        }
    }

    /**
     * Events fire after user status has been changed
     *
     * @param string $email  - Account Email
     * @param string $status - To which status has been changed account
     *
     * @return bool
     */
    public static function afterUserStatusChanged($email, $status)
    {
        $userAdapter = new UserAdapter();
        if (!$email || !$status || !$userAdapter->isValidStatus($status)) {
            return false;
        }

        $api = new API();
        $tokenManager = new Token();
        $allTokens = $tokenManager->getAll(true);
        
        $endpoint = sprintf('accounts/%s/%s', $email, $status);
       

        foreach ($allTokens as $token) {
            $api->withDomain($token['Domain'])->post($endpoint);
        }
    }

    /**
     * Event fire after user has been changed avatar
     *
     * @param string $email     - Account email
     * @param string $avatarUrl - New avatar URL
     *
     * @return bool
     */
    public static function afterUserUploadAvatar($email, $avatarUrl)
    {
        if (!$email || !$avatarUrl) {
            return false;
        }

        $api = new API();
        $tokenManager = new Token();
        $allTokens = $tokenManager->getAll(true);

        $endpoint = sprintf('accounts/%s/avatar', $email);
        $data['img_url'] = $avatarUrl;

        foreach ($allTokens as $token) {
            $api->withDomain($token['Domain'])->post($endpoint, $data);
        }
        
    }

    /**
     * Fire after moving user to the trash box
     *
     * @param string $email - Email of account which is moving to the trash
     */
    public static function afterProfileMovingToTrash($email)
    {
        self::afterUserStatusChanged($email, 'trash');
    }

    /**
     * Fire after restoring a user from the trash box
     *
     * @param string $email - Email of account which is removing from the trash box
     */
    public static function afterProfileRestoreFromTrash($email)
    {
        self::afterUserStatusChanged($email, 'approval');
    }
    
    /**
     * Event fire after user registration process
     *
     * @param array $profileData
     * @param array $accountData
     */
    public static function afterRegistration($profileData, $accountData)
    {
        $api = new API();
        $tokenManager = new Token();
        $allTokens = $tokenManager->getAll(true);
        $accountTypeInfo = AccountTypesAdapter::getInfoByID($profileData['type']);

        $status = $accountTypeInfo['Admin_confirmation'] && !defined('REALM') ? 'pending' : 'active';
        if ($accountTypeInfo['Email_confirmation'] && !defined('REALM')) {
            $status = 'incomplete';
        }

        $data = array(
            'email'        => $profileData['mail'],
            'password'     => $profileData['password'],
            'type_key'     => $accountTypeInfo['Key'],
            'username'     => $profileData['username'],
            'status'       => $status,
            'profile_data' => json_encode($accountData),
        );
        /*  todo: handle and prepare batch response array. I should show information
                  about what domain successfully created account and what not
        */
        foreach ($allTokens as $token) {
            if (AccountTypesAdapter::isSynchronizedWithDomain($accountTypeInfo['Key'], $token['Domain'])) {
                $response = $api->withDomain($token['Domain'])->auth()->post('accounts', $data);

                if ($response->status != 200) {
                    $errors[$token['Domain']] = $response->body['message'];
                    return $error;
                }
            }
        }
    }

    /**
     * Event before quick user registration process
     *
     * @param array $data
     * @param array $info
     */
    public static function beforeQuickRegistration($data, $info)
    {
        $api = new API();
        $tokenManager = new Token();
        $allTokens = $tokenManager->getAll(true);

        foreach ($allTokens as $token) {
            $response = $api->withDomain($token['Domain'])->auth()->post('accounts/quick', $data);
            if ($response->status != 200) {
                $errors[$token['Domain']] = $response->body['message'];
                return $error;
            }
        }
    }

    /**
     * Fire after removing account
     *
     * @param string $email - Account email which removed
     * @return bool
     */
    public static function afterProfileRemove($email)
    {
        if (!$email) {
            return false;
        }

        $api = new API();
        $tokenManager = new Token();
        $endpoint = sprintf('accounts/%s', $email);
        $allTokens = $tokenManager->getAll(true);

        foreach ($allTokens as $token) {
            $api->withDomain($token['Domain'])->auth()->delete($endpoint);
        }
    }

    /**
     * Event fire after user changed his profile
     *
     * @param string $email
     * @param array  $profileData
     * @param array  $accountData
     */
    public static function afterProfileEdit($email, $profileData = array(), $accountData = array())
    {
        $api = new API();
        $tokenManager = new Token();
        $endpoint = sprintf('accounts/%s', $email);

        if (!empty($profileData)) {
            $data['mail'] = $profileData['mail'];
            $data['type_key'] = $profileData['type'];
            $data['status'] = $profileData['status'];
            $data['password'] = $profileData['password'];
            $data['from_admin'] = isset($profileData['from_admin']);
        }

        $data['profile_data'] = json_encode($accountData);

        $allTokens = $tokenManager->getAll(true);
        foreach ($allTokens as $token) {
            $api->withDomain($token['Domain'])->auth()->post($endpoint, $data);
        }
    }
}
