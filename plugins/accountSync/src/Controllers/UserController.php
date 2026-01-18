<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: USERCONTROLLER.PHP
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

namespace Flynax\Plugins\AccountSync\Controllers;

use Flynax\Components\Image\Uploader\Uploader;
use Flynax\Plugins\AccountSync\Adapters\AccountFieldsAdapter;
use Flynax\Plugins\AccountSync\Adapters\AccountTypesAdapter;
use Flynax\Plugins\AccountSync\Adapters\UserAdapter;
use Flynax\Plugins\AccountSync\API;
use Flynax\Plugins\AccountSync\Controller;
use Flynax\Plugins\AccountSync\Http\Response;
use Flynax\Plugins\AccountSync\Models\MetaData;
use Flynax\Plugins\AccountSync\Models\Token;
use Flynax\Utils\Valid;

class UserController extends Controller
{
    /**
     * @var \rlAccount
     */
    private $rlAccount;

    /**
     * @var \Flynax\Plugins\AccountSync\Adapters\UserAdapter
     */
    private $userAdapter;

    /**
     * UserController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->rlAccount = asMake('rlAccount');
        $this->userAdapter = new UserAdapter();
    }

    public function shouldEmailExist($email)
    {
        $email = $this->rlValid->xSql($email);
        if (!$this->userAdapter->isEmailExist($email) || !$this->rlValid->isEmail($email)) {
            Response::error(asLang('as_user_missing'), Response::BAD_REQUEST, 'user_missing');
        }
    }

    /**
     * Register new user
     */
    public function registerNewUser()
    {

        $this->validate(array(
            'email' => 'required',
            'password' => 'required',
            'type_key' => 'required',
        ));

        $accountTypeAdapter = new AccountTypesAdapter();
        $meta = new MetaData();

        $email = $this->requestData['email'];
        if ($this->userAdapter->isEmailExist($email)) {
            $emailExistMsg = str_replace(
                '{email}',
                '<b>"' . $email . '"</b>',
                asLang('notice_account_email_exist')
            );

            Response::error($emailExistMsg, Response::BAD_REQUEST, 'email_already_in_use');
        }

        $accountTypeKey = $this->requestData['type_key'];
        if (!$accountTypeAdapter->isAccountTypeExist($accountTypeKey)) {
            Response::error(asLang('as_account_type_doesnt_exist'), Response::BAD_REQUEST, 'account_type_doesnt_exist');
        }
        $accounTypeInfo = AccountTypesAdapter::getInfoByKey($accountTypeKey);

        $profileData = array(
            'username' => $this->requestData['username'],
            'location' => $this->rlValid->str2path($this->requestData['username']),
            'mail' => $this->requestData['email'],
            'password' => $this->requestData['password'],
            'password_repeat' => $this->requestData['password'],
            'type' => $accounTypeInfo['ID'],
        );

        $accountDataFromRequest = json_decode($this->requestData['profile_data'], true);

        $accountData = array();
        if ($accountDataFromRequest) {
            $syncAccountFields = AccountFieldsAdapter::getSyncFieldsByType($this->requestData['type_key']);

            foreach ($syncAccountFields as $field) {
                if (in_array($field, array_keys($accountDataFromRequest))) {
                    $accountData[$field] = $accountDataFromRequest[$field] ? $accountDataFromRequest[$field] : '';
                }
            }
        }

        /** @var \rlCommon $rlCommon */
        $rlCommon = asMake('rlCommon');
        /** @var \rlLang $rlLan */
        $rlLang = asMake('rlLang');

        $fields = $this->rlAccount->getFields($accounTypeInfo['ID']);
        $fields = $rlLang->replaceLangKeys($fields, 'account_fields', array('name', 'default', 'description'));
        $fields = $rlCommon->fieldValuesAdaptation($fields, 'account_fields');

        $this->rlAccount->registration($accounTypeInfo['ID'], $profileData, $accountData, $fields)
        ? Response::success(['new_user' => $profileData], Response::CREATED, 'user_created')
        : Response::error(
            asLang('as_something_wrong'),
            Response::SERVER_ERROR, 'user_create_error'
        );
    }

    /**
     * Register quick new user
     */
    public function registerQuickNewUser()
    {
        // get columns
        $tmp = $this->rlDb->getAll("SHOW COLUMNS FROM `" . RL_DBPREFIX . "accounts`");

        $colomns = [];
        $insert = $this->requestData;

        foreach ($tmp as $fKey => $value) {
            $colomns[$value['Field']] = $value['Field'];
        }

        foreach ($insert as $key => $val) {
            if (!in_array($key, $colomns)) {
                unset($insert[$key]);
            }
        }

        if ($insert) {
            $this->rlDb->insertOne($insert, 'accounts');
        }
    }

    public function updateUser($email)
    {
        if (!$email) {
            Response::error(asLang('as_user_update_missing_email'), Response::BAD_REQUEST, 'user_update_missing_email');
        }

        $accountInfo = $this->userAdapter->getInfoByEmail($email);
        $accountTypeInfo = AccountTypesAdapter::getInfoByKey($accountInfo['Type']);

        if (!$accountInfo) {
            Response::error(asLang('as_user_update_missing_account'), Response::BAD_REQUEST, 'user_update_missing_account');
        }

        if ($this->requestData['profile_data']) {
            $accountDataFromRequest = json_decode($this->requestData['profile_data'], true);

            $accountData = array();
            if ($accountDataFromRequest) {
                $syncAccountFields = AccountFieldsAdapter::getSyncFieldsByType($accountInfo['Type']);

                foreach ($syncAccountFields as $field) {
                    if (in_array($field, array_keys($accountDataFromRequest))) {
                        $accountData[$field] = $accountDataFromRequest[$field];
                    }
                }
            }

            /** @var \rlCommon $rlCommon */
            $rlCommon = asMake('rlCommon');
            /** @var \rlLang $rlLan */
            $rlLang = asMake('rlLang');

            $fields = $this->rlAccount->getFields($accountTypeInfo['ID']);
            $fields = $rlLang->replaceLangKeys($fields, 'account_fields', array('name', 'default', 'description'));
            $fields = $rlCommon->fieldValuesAdaptation($fields, 'account_fields');
            $this->rlAccount->editAccount($accountData, $fields, $accountInfo['ID']);
        }

        $requiredAccountFields = array('password', 'from_admin', 'type_key', 'status');
        $isUpdateAccount = false;

        foreach ($requiredAccountFields as $field) {
            if ($this->requestData[$field]) {
                $isUpdateAccount = true;
                continue;
            }
        }

        if ($isUpdateAccount) {
            $updateAccount = array();
            $updateAccount['mail'] = $updateAccount['mail'] ?: $email;
            $updateAccount['status'] = $this->requestData['status'] ?: 'active';
            $updateAccount['type'] = $accountTypeInfo['Key'];

            if ($this->requestData['password']) {
                require_once RL_CLASSES . "rlSecurity.class.php";
                $updateAccount['password'] = \FLSecurity::cryptPassword($this->requestData['password']);
            }

            if ($this->requestData['from_admin']) {
                define('REALM', 'admin');
            }

            $this->rlAccount->editProfile($updateAccount, $accountInfo['ID']);
        }

        Response::success(asLang('as_user_update_success'), Response::SUCCESS, 'user_update_success');
    }

    public function changeUserPassword($email)
    {

        if (!$email) {
            Response::error(asLang('as_user_update_missing_email'), Response::BAD_REQUEST, 'user_update_missing_email');
        }

        $this->validate(array(
            'password' => 'required',
        ));

        $email = $this->rlValid->xSql($email);
        $hash = \FLSecurity::cryptPassword($this->requestData['password']);
        $sql = "UPDATE `" . RL_DBPREFIX . "accounts` SET `Password` = '{$hash}' ";
        $sql .= "WHERE `Mail` = '{$email}' LIMIT 1";

        $this->rlDb->query($sql);

        Response::error(asLang('as_user_update_password_success'), Response::SUCCESS, 'user_update_password_success');
    }

    public function changeUserStatus($email, $status)
    {
        $email = $this->rlValid->xSql($email);
        $status = $this->rlValid->xSql($status);

        $this->shouldEmailExist($email);

        if (!$this->userAdapter->isValidStatus($status)) {
            Response::error(asLang('as_user_change_wrong_change_status'), Response::BAD_REQUEST, 'user_change_wrong_change_status');
        }

        $this->userAdapter->changeUserStatus($email, $status)
        ? Response::success(asLang('as_status_changed'), Response::SUCCESS, 'status_changed')
        : Response::error(asLang('as_something_wrong'), Response::SERVER_ERROR);
    }

    public function uploadUserAvatar($email)
    {
        $email = $this->rlValid->xSql($email);

        $this->shouldEmailExist($email);

        //todo: Validate URL, it should be correct image
        $this->validate(array(
            'img_url' => 'required',
        ));

        $url = $this->requestData['img_url'];
        $accountInfo = $this->userAdapter->getInfoByEmail($email);

        $_SESSION['account']['ID'] = $accountInfo['ID'];
        $uploader = new Uploader();
        $uploader->uploadImageToAccount($url, $accountInfo['ID'])
        ? Response::success(asLang('as_upload_avatar_success'), Response::SUCCESS, 'upload_avatar_success')
        : Response::error(asLang('as_something_wrong'), Response::SERVER_ERROR, 'upload_avatar_error');
    }

    public function removeUser($email)
    {

        if (!$email) {
            Response::error(asLang('as_user_update_missing_email'), Response::BAD_REQUEST, 'remove_email_missing');
        }

        if (!$this->userAdapter->isEmailExist($email)) {
            Response::error(asLang('as_remove_email_missing'), Response::BAD_REQUEST, 'remove_email_missing');
        }

        $info = $this->userAdapter->getInfoByEmail($email);

        $GLOBALS['reefless']->loadClass('Admin', 'admin');
        $rlListingTypes = asMake('rlListingTypes');

        $GLOBALS['rlAdmin']->ajaxDeleteAccount($info['ID'], false, true);
        Response::success(array('removed_user' => $info), Response::SUCCESS, 'removed_successfully');
    }

    public function getAccounts()
    {
        /**
         * todo: Encrypt output
         */
        $result = array();

        $currentPage = $this->requestData['page'] ?: 1;
        $limit = $this->requestData['limit'] ?: 2;
        $start = ($currentPage - 1) * $limit;
        $info = $this->userAdapter->getUsers($start, $limit);
        $users = $info['users'];

        if (!$users) {
            Response::error(asLang('as_not_found'), Response::NOT_FOUND, 'users_not_found');
        }

        if ($this->requestData['with_stat']) {
            $accountTypes = AccountTypesAdapter::getAllTypes();
            $dataStat = array();
            $dataStat['total'] = $info['total'];

            foreach ($accountTypes as $key => $type) {
                $sql = "SELECT COUNT(`ID`) as `count` FROM `" . RL_DBPREFIX . "accounts` ";
                $sql .= "WHERE `Type` = '{$key}'";
                $dataStat[$key] = (int) $this->rlDb->getRow($sql, 'count');
            }
            $result['statistic'] = $dataStat;
        }
        $result['users'] = $users;

        if (count($users) < $info['total']) {
            $totalPage = ceil($info['total'] / $limit);
            $endPoint = '/accounts';
            $currentPageUrl = $endPoint . '?page=' . $currentPage;
            $result['_links']['_base'] = RL_URL_HOME;
            $result['_links']['self'] = $currentPageUrl;

            if ($currentPage > 1) {
                $previousPage = $endPoint . '?page=' . ($currentPage - 1);
                $result['_links']['prev'] = $previousPage;
            }

            if ((int) $totalPage >= $currentPage) {
                $nextPage = $endPoint . '?page=' . ($currentPage + 1);
                $result['_links']['next'] = $nextPage;
            }

            $lastPage = $endPoint . '?page=' . $totalPage;
            $result['_links']['last'] = $lastPage;
        }

        Response::success(array('data' => $result));
    }

    public function getUsersByEmail($mails)
    {
        global $config;
        $sql = "SELECT `T1`.* ";
        if ($config['membership_module']) {
            $sql .= ", `T2`.`Key` AS `Plan_key` ";
        }
        $sql .= "FROM `" . RL_DBPREFIX . "accounts` AS `T1` ";
        if ($config['membership_module']) {
            $sql .= "LEFT JOIN `" . RL_DBPREFIX . "membership_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
        }
        $sql .= "WHERE `T1`.`Mail` IN('" . implode("','", $mails) . "') ";
        $users = $this->rlDb->getAll($sql);

        return $users;
    }

    /**
     * Request to create new users
     *
     * @param string $url
     * @param string $type
     * @param int    $start
     * @param int    $limit
     *
     * @return return $out
     */
    public function requestCreateUsers($url, $type, $start, $limit)
    {
        $meta = new MetaData();
        $api = new API();
        $tokenManager = new Token();

        $end = $start + $limit;
        $total = 0;
        $out = [];

        $data = $meta->get(MetaData::META_UNIQUE_USERS, RL_URL_HOME);
        $usersByDomain = [];
        foreach ($data[$url]['unique_users'] as $key => $value) {
            if ($value['account_type'] == $type) {
                foreach ($value['unique_for'] as $fKey => $fVal) {
                    if ($fVal) {
                        $usersByDomain[$fVal][] = Valid::escape($key);
                    }
                }
            }
        }
        $tokens = $tokenManager->getAll(true);

        foreach ($tokens as $token) {
            $users = [];
            if ($usersByDomain[$token['Domain']]) {
                $users = array_slice($usersByDomain[$token['Domain']], $start, $end);

                if ($users) {
                    $usersArray['url_from'] = RL_URL_HOME;
                    $usersArray['type'] = $type;
                    $usersArray['users'] = $this->getUsersByEmail($users);

                    $total = count($usersByDomain[$token['Domain']]) > $total ? count($usersByDomain[$token['Domain']]) : $total;

                    $result = $api->withDomain($token['Domain'])->auth()->post('accounts/create', $usersArray);

                    if ($result->status == 200 && $result->body['code_phrase'] == 'success') {
                        $process = (100 / $total * $end);
                        $out = array(
                            'status' => $total > $end ? 'next' : 'complete',
                            'progress' => $process > 100 ? 1 : $process / 100,
                            'duplicate' => $result->body['duplicate'] ? true : '',
                        );
                    }
                }
            }
        }

        return $out;
    }

    /**
     * Create new users by data
     */
    public function createUsers()
    {
        $type = $this->requestData['type'];
        $users = $this->requestData['users'];
        $url_from = $this->requestData['url_from'];

        $accounTypeInfo = AccountTypesAdapter::getInfoByKey($type);
        $syncAccountFields = AccountFieldsAdapter::getSyncFieldsByType($type);

        $insertUsers = [];
        $thumbnails = [];

        if ($GLOBALS['config']['membership_module']) {
            $mmPlans = $this->rlDb->getAll("SELECT `ID`,`Key` FROM `" . RL_DBPREFIX . "membership_plans` WHERE `Status` = 'active'", 'Key');
        }

        $duplicate = "";

        foreach ($users as $key => $user) {

            if ($this->userAdapter->isEmailExist($user['Mail'])) {
                $duplicate = "1";
                continue;
            }
            if ($this->userAdapter->isUsernameExist($user['Username'])) {
                $duplicate = "1";
                continue;
            }
            if (!$user['Mail'] || !$user['Username']) {
                continue;
            }

            if ($user['Photo']) {
                $thumbnails[$user['Mail']] = $user['Photo'] ? $url_from . 'files/' . $user['Photo'] : "";
            }

            $insertUsers[$key] = array(
                'Type' => $type,
                'Username' => $user['Username'],
                'Own_address' => $user['Own_address'],
                'Password' => $user['Password'],
                'Password_tmp' => $user['Password_tmp'],
                'Lang' => $user['Lang'],
                'Mail' => $user['Mail'],
                'Date' => $user['Date'],
                'Display_email' => $user['Display_email'],
                'Status' => $user['Status'],
                'Loc_latitude' => $user['Loc_latitude'],
                'Loc_longitude' => $user['Loc_longitude'],
                'Loc_address' => $user['Loc_address'],
            );

            if ($user['Plan_key'] && $mmPlans[$user['Plan_key']]) {
                $insertUsers[$key]['Plan_ID'] = $mmPlans[$user['Plan_key']]['ID'];
                $insertUsers[$key]['Pay_date'] = 'NOW()';
            }

            foreach ($syncAccountFields as $field) {
                if ($user[$field]) {
                    $insertUsers[$key][$field] = $user[$field];
                }
            }
        }

        if ($insertUsers) {

            foreach ($insertUsers as $key => $insert) {
                $this->rlDb->insertOne($insert, 'accounts', array('Username', 'Password'));
                $user_id = method_exists($this->rlDb, 'insertID') ? $this->rlDb->insertID() : mysql_insert_id();

                if ($thumbnails[$insert['Mail']]) {
                    $_SESSION['account']['ID'] = $user_id;
                    $uploader = new Uploader();
                    $uploader->uploadImageToAccount($thumbnails[$insert['Mail']], $user_id);
                }
            }
        }

        $out = array(
            'status' => 'done',
            'duplicate' => $duplicate,
        );

        Response::success($out);
    }

    /**
     * Sync users
     */
    public function syncUsers()
    {
        if ($this->requestData['start'] == 0) {
            $accountSync = new \Flynax\Plugins\AccountSync\AccountSync();
            $accountSync->apFetchUsers();
        }

        $out = $this->requestCreateUsers(RL_URL_HOME, $this->requestData['type'], $this->requestData['start'], $this->requestData['limit']);

        Response::success(array('out' => $out));
    }

}
