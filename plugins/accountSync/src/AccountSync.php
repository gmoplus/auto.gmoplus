<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: ACCOUNTSYNC.PHP
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

namespace Flynax\Plugins\AccountSync;

use Flynax\Plugins\AccountSync\Adapters\AccountTypesAdapter;
use Flynax\Plugins\AccountSync\Adapters\UserAdapter;
use Flynax\Plugins\AccountSync\Controllers\UserController;
use Flynax\Plugins\AccountSync\Files\FilesWorker;
use Flynax\Plugins\AccountSync\Http\Response;
use Flynax\Plugins\AccountSync\Models\MetaData;
use Flynax\Plugins\AccountSync\Models\Token;

class AccountSync
{
    /**
     * Plugin key
     */
    const PLUGIN_KEY = 'accountSync';

    /**
     * @var \Flynax\Plugins\AccountSync\FilesWorker
     */
    public $filesWorker;

    /**
     * @var \Flynax\Plugins\AccountSync\API
     */
    public $api;

    /**
     * @var \Flynax\Plugins\AccountSync\Models\MetaData
     */
    public $meta;

    /**
     * @var \Flynax\Plugins\AccountSync\Models\Token
     */
    public $tokenManager;

    /**
     * AccountSync constructor.
     */
    public function __construct()
    {
        $this->filesWorker = new FilesWorker(self::PLUGIN_KEY);
        $this->api = new API();
        $this->meta = new MetaData();
        $this->tokenManager = new Token();
    }

    /**
     * Include JS file to the admin panel part of the plugin
     *
     * @param string $fileName - JS file name (located in the static folder)
     */
    public function addAdminJs($fileName)
    {
        echo $this->filesWorker->renderJsFileInclude($fileName);
    }

    /**
     * Add Css file to the admin part of the plugin
     *
     * @param string $fileName - CSS file name which you want to include to the page
     */
    public function addAdminCss($fileName)
    {
        echo $this->filesWorker->renderCssFileInclude($fileName);
    }

    /**
     * Handle AP AJAX Request
     *
     * @param string $item - Request item
     * @return array|bool
     *
     * @throws \Exception
     */
    public function handleApAjax($item)
    {
        if (!$this->isValidAjaxRequest($item)) {
            return false;
        }
        /** @var \rlValid $rlValid */
        $rlValid = asMake('rlValid');

        // handleApAjax
        switch ($item) {
            case 'ac_synchronize':
                $url = $_REQUEST['url'];

                $pulse = $this->api->withDomain($url)->get('status');

                if ($pulse->status != 200) {
                    return array(
                        'status' => 'ERROR',
                        'message' => asLang('as_does_plugin_installed'),
                    );
                }

                if ($url == RL_URL_HOME) {
                    return array(
                        'status' => 'ERROR',
                        'message' => asLang('as_something_wrong'),
                    );
                } else if ($GLOBALS['rlDb']->getOne('ID', "`Domain` = '{$url}'", 'as_tokens')) {
                    return array(
                        'status' => 'ERROR',
                        'message' => str_replace('{key}', asLang('url'), asLang('notice_item_key_exist')),
                    );
                }

                $requestData = $_REQUEST['admin'];
                $requestData['token'] = Token::generateToken();
                $requestData['domain'] = RL_URL_HOME;

                $response = $this->api->withDomain($url)->post('check-admin', $requestData);

                if ($response->status != 200 && $response->body['code_phrase'] != 'correct_credentials') {
                    return array(
                        'status' => 'ERROR',
                        'message' => asLang('as_incorrect_data'),
                    );
                }

                $theirToken = $response->body['token'];
                $theirDomain = $response->body['domain'];

                if (!$theirDomain && !$theirToken) {
                    return array(
                        'status' => 'ERROR',
                        'message' => asLang('as_something_wrong'),
                    );
                }

                $this->tokenManager->add($theirToken, $theirDomain);
                Events::afterSuccessfulSync(RL_URL_HOME, $theirDomain);

                return array(
                    'status' => 'OK',
                    'message' => asLang('as_remote_source_added'),
                );
                break;
            case 'ac_disconnect':
                $domain = $_REQUEST['domain'];

                $response = $this->api
                    ->withDomain($domain)
                    ->auth()
                    ->get('disconnect', array('domain' => RL_URL_HOME));

                if ($response->status != Response::SUCCESS) {
                    return array(
                        'status' => 'ERROR',
                        'message' => $response->body['message'],
                    );
                }

                if ($response->body['code_phrase'] == 'disconnected') {
                    $theirTokenInfo = $this->tokenManager->getInfoByDomain($domain);
                    $this->tokenManager->delete($theirTokenInfo['ID']);

                    $this->meta->delete($theirTokenInfo['Domain'], MetaData::META_ACCOUNT_TYPES);

                    return array(
                        'status' => 'OK',
                        'message' => asLang('as_disconnected'),
                    );
                }
                break;
            case 'ac_exchangeAccountTypes':
                $theirDomain = $_REQUEST['domain'];
                $ourAccountTypes = AccountTypesAdapter::getAllTypes();
                $data = array(
                    'account_types' => $ourAccountTypes,
                    'domain' => RL_URL_HOME,
                );

                $result = $this->api->auth()->withDomain($theirDomain)->post('cache/account-types', $data);
                if ($result->status == 200 && $result->body['code_phrase'] == 'exchange_success') {
                    $theirAccountTypes = $result->body['account_types'];
                    $this->meta->set($result->body['domain'], 'account_types', $theirAccountTypes);
                    $out = array('status' => 'OK');
                } else {
                    $out = array('status' => 'ERROR');
                }
                break;
            case 'as_updateCache':

                $this->updateAllAccountTypesCache();
                $this->updateAllAccountFieldsCache();

                $out = array(
                    'status' => 'OK',
                    'message' => asLang('cache_updated'),
                );
                break;
            case 'as_syncAccountField':
                if ($_POST['from']['domain'] && $_POST['from']['fieldKey']) {
                    $endpoint = sprintf("account/fields/%s", $_POST['from']['fieldKey']);
                    $response = $this->api->auth()->withDomain($_POST['from']['domain'])->get($endpoint);

                    if ($response->status == 200 && $response->body['code_phrase'] == 'account_field_info_success') {
                        $fieldInfo = $response->body['info'];
                        $this->api->auth()->withDomain($_POST['to']['domain'])->post('account/fields', $fieldInfo);

                        $out = array(
                            'status' => 'OK',
                            'message' => asLang('as_af_synchronized'),
                        );
                    }
                }

                $out = array(
                    'status' => 'ERROR',
                    'message' => asLang('as_something_wrong'),
                );
                break;

            case 'as_syncUsers':
                $url = $_REQUEST['url'];
                $type = $_REQUEST['type'];
                $start = $_REQUEST['start'];
                $limit = $_REQUEST['limit'];

                $out = $this->syncUsers($url, $type, $start, $limit);

                break;

            case 'as_apFetchUsers':

                $out = $this->apFetchUsers();

                $out = array('status' => 'OK');
                break;
        }

        return $out;
    }

    /**
     * Update account types related cache
     *
     * @return bool
     */
    public function updateAllAccountTypesCache()
    {

        $allTokens = $this->tokenManager->getAll(true);
        foreach ($allTokens as $token) {
            $response = $this->api->withDomain($token['Domain'])->auth()->get('account/types');
            if ($response->status == 200 && $response->body['code_phrase'] == 'success_account_types') {
                $theirAccountTypes = $response->body['account_types'];
                $this->meta->set($token['Domain'], MetaData::META_ACCOUNT_TYPES, $theirAccountTypes);
            }
        }

        $this->meta->set(RL_URL_HOME, MetaData::META_ACCOUNT_TYPES, AccountTypesAdapter::fetchAllTypes());

        return true;
    }

    /**
     * Update all account fields related cache
     */
    public function updateAllAccountFieldsCache()
    {

        $allTokens = $this->tokenManager->getAll();
        foreach ($allTokens as $token) {
            $response = $this->api->withDomain($token['Domain'])->auth()->get('account/fields');

            if ($response->status == 200 && $response->body['code_phrase'] == 'account_fields_success') {
                $accountFields = $response->body['account_fields'];
                $this->meta->set($token['Domain'], MetaData::META_ACCOUNT_FIELDS, $accountFields);
            }
        }
    }

    /**
     * Get users
     */
    public function apFetchUsers()
    {
        $allUsersInfos = $domainStatsOnly = array();
        $allTokens = $this->tokenManager->getAll();
        $data = array(
            'with_stat' => '1',
            'limit' => $GLOBALS['config']['as_user_limit'],
        );

        // collect all users from all sync domains
        foreach ($allTokens as $token) {
            $users = $userStat = array();

            do {
                $response = $this->api->getAllUsersOf($token['Domain'], 'accounts', $data);

                if ($response->status != 200) {
                    break;
                }

                $userStat = $response->body['data']['statistic'];
                $nextPageEndpoint = $response->body['data']['_links']['next'];
                $usersFromResponse = $response->body['data']['users'];
                foreach ($usersFromResponse as $responseUser) {
                    $users[] = $responseUser;
                }

                if ($pageNumber = (int) filter_var($nextPageEndpoint, FILTER_SANITIZE_NUMBER_INT)) {
                    $data['page'] = $pageNumber;
                }
            } while ($nextPageEndpoint);

            if ($users) {
                $stat = array(
                    'domain' => $token['Domain'],
                    'statistic' => $userStat,
                );

                $allUsersInfos[$token['Domain']] = $users;
                $domainStatsOnly[$token['Domain']] = $stat;
            }

            unset($data['page']);
        }

        $uniqueUsers = $uniqueByTypes = array();
        $totalDomains = count($allUsersInfos);

        // get unique users by account types and domain
        foreach ($allUsersInfos as $domain => $users) {
            $main_domain = RL_URL_HOME == $domain ? true : false;
            foreach ($users as $userKey => $user) {
                $info = UserAdapter::getUserInfoByAllDomain($user['Mail'], $allUsersInfos, $main_domain);
                if ($info['unique_for']) {
                    $uniqueUsers[$domain][$user['Mail']] = array(
                        'account_type' => $user['Type'],
                        'unique_for' => $info['unique_for'],
                    );
                }

                if ($info['found_times'] < $totalDomains && $main_domain) {
                    $uniqueByTypes[$domain][$user['Type']]++;
                } else if (!$info['found_times'] && !$main_domain) {
                    $uniqueByTypes[$domain][$user['Type']]++;
                }
            }
        }
        // combine unique data statistic with total
        foreach ($domainStatsOnly as $domain => $stat) {
            foreach ($stat['statistic'] as $accountType => $usersCount) {
                if ($accountType == 'total') {
                    continue;
                }

                $domainStatsOnly[$domain]['statistic'][$accountType] = array(
                    'total' => $usersCount,
                    'unique' => (int) $uniqueByTypes[$domain][$accountType],
                );
            }
            $domainStatsOnly[$domain]['unique_users'] = $uniqueUsers[$domain];
        }

        $this->meta->set(RL_URL_HOME, MetaData::META_UNIQUE_USERS, $domainStatsOnly);
    }

    /**
     * Sync user by type and domain
     *
     * string $url
     * string $type
     * string $start
     * string $limit
     *
     * return $out
     */
    public function syncUsers($url, $type, $start, $limit)
    {
        $out = [];

        if ($url == RL_URL_HOME) {
            $userController = new UserController();

            $out = $userController->requestCreateUsers($url, $type, $start, $limit);
        } else {

            $token = $this->tokenManager->getInfoByDomain($url);

            $data = array(
                'type' => $type,
                'start' => $start,
                'limit' => $limit,
            );

            $result = $this->api->withDomain($token['Domain'])->auth()->get('accounts/syncUsers', $data);
            if ($result->status == 200 && $result->body['code_phrase'] == 'success') {
                $out = $result->body['out'];
            }
        }

        if (!$out) {
            $out = array('status' => 'ERROR');
        }

        return $out;
    }

    /**
     * Is request is valid
     *
     * @param  string $request - Request item
     * @return bool
     */
    public function isValidAjaxRequest($request)
    {
        $validRequests = array(
            'ac_synchronize',
            'ac_disconnect',
            'ac_exchangeAccountTypes',
            'as_updateCache',
            'as_syncAccountField',
            'as_apFetchUsers',
            'as_syncUsers',
        );

        return in_array($request, $validRequests);
    }
}
