<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: APICONTROLLER.PHP
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

use FLSecurity;
use Flynax\Plugins\AccountSync\Adapters\AccountFieldsAdapter;
use Flynax\Plugins\AccountSync\Adapters\AccountTypesAdapter;
use Flynax\Plugins\AccountSync\Adapters\UserAdapter;
use Flynax\Plugins\AccountSync\API;
use Flynax\Plugins\AccountSync\Controller;
use Flynax\Plugins\AccountSync\Http\Response;
use Flynax\Plugins\AccountSync\Models\MetaData;
use Flynax\Plugins\AccountSync\Models\Token;

class ApiController extends Controller
{
    /**
     * @var \Flynax\Plugins\AccountSync\API
     */
    private $api;

    /**
     * ApiController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->api = new API();
    }

    /**
     * Checking status of the plugin
     */
    public function checkStatus()
    {
        // get status info
        Response::success(asLang('as_plugin_successfully'));
    }

    /**
     * Trying to login to the admin panel area with provided in request credentials
     */
    public function checkAdmin()
    {

        $username = $this->requestData['username'];
        $password = $this->requestData['password'];

        $sql = "SELECT * FROM `" . RL_DBPREFIX . "admins` WHERE `User` = '{$username}'";
        $adminInfo = $this->rlDb->getRow($sql);

        if ($adminInfo && FLSecurity::verifyPassword($password, $adminInfo['Pass'])) {
            $this->validate(array(
                'token' => 'required',
                'domain' => 'required',
            ));

            $theirToken = $this->requestData['token'];
            $theirDomain = $this->requestData['domain'];

            $tokenManager = new Token();

            if (!$tokenManager->add($theirToken, $theirDomain)) {
                Response::error(asLang('as_something_wrong'), Response::SERVER_ERROR, 'server_error');
                return;
            }

            $sendingData = array(
                'token' => Token::generateToken(),
                'domain' => RL_URL_HOME,
            );

            $meta = new MetaData();
            $ourAccountTypes = AccountTypesAdapter::getAllTypes();
            $meta->set(RL_URL_HOME, MetaData::META_ACCOUNT_TYPES, $ourAccountTypes);

            Response::success($sendingData, Response::SUCCESS, 'correct_credentials');
            return;
        }

        Response::error(asLang('as_incorrect_data'), 422, 'unprocessable_entity');
    }

    /**
     * Showing all account fields of the Flynax installation as the Response
     */
    public function getAllAccountFields()
    {
        /** @var \rlAccount $rlAccount */
        $rlAccount = asMake('rlAccount');
        /** @var \rlLang $rlLang */
        $rlLang = asMake('rlLang');

        // get phrases if empty
        if (!$GLOBALS['lang']) {
            // Define controller
            $controller = empty($_GET['controller']) ? 'home' : $_GET['controller'];
            $js_keys = [];
            $GLOBALS['lang'] = $lang = $rlLang->getAdminPhrases(RL_LANG_CODE, 'active', $controller, $js_keys);
        }

        $ourAccountTypes = AccountTypesAdapter::getAllTypes();
        $accountTypeFields = array();
        foreach ($ourAccountTypes as $accountType) {
            $fields = $rlAccount->getFields($accountType['ID']);
            $fields = $rlLang->replaceLangKeys($fields, 'account_fields', array('name'));
            foreach ($fields as $field) {
                $accountTypeFields[$accountType['Key']][] = array(
                    'Key' => $field['Key'],
                    'name' => $field['name'],
                );
            }
        }

        $sql = "SELECT `Key` FROM `" . RL_DBPREFIX . "account_fields`";
        $fields = $this->rlDb->getAll($sql);
        $accountTypeFields['all'] = $fields;

        $accountFields = array('account_fields' => $accountTypeFields);
        $accountTypeFields
        ? Response::success($accountFields, Response::SUCCESS, 'account_fields_success')
        : Response::error(asLang('as_something_wrong'), Response::SERVER_ERROR, 'server_error');
    }

    /**
     * Disconnect with domain by provided token in request
     */
    public function disconnect()
    {
        // enable auth
        $this->shouldAuth();

        $tokenManager = new Token();
        $token = $this->requestData['token'];

        $info = $tokenManager->getInfoByToken($token);
        if (empty($info) || !$info['Domain']) {
            Response::error(asLang('as_something_wrong'), Response::SERVER_ERROR, 'server_error');
        }

        if ($tokenManager->delete($info['ID'])) {
            $meta = new MetaData();
            $meta->delete($info['Domain'], MetaData::META_ACCOUNT_TYPES);

            Response::success(asLang('as_disconnected'), Response::SUCCESS, 'disconnected');
        }

        Response::error(asLang('as_something_wrong'), Response::SERVER_ERROR, 'server_error');
    }

    /**
     * Show all account types in HTTP response
     */
    public function getAccountTypes()
    {
        // enable auth
        $this->shouldAuth();

        $types = AccountTypesAdapter::getAllTypes();

        Response::success(array('account_types' => $types), Response::SUCCESS, 'success_account_types');
    }

    /**
     * Show provided account type info in HTTP Response
     *
     * @param string $key - Looking account type key
     */
    public function getAccountType($key)
    {
        if (!$key) {
            Response::error(asLang('as_account_type_missing'), Response::BAD_REQUEST, 'account_type_info_missing');
        }

        $this->shouldAuth();

        $info = AccountTypesAdapter::getInfoByKey($key);

        Response::success(array('account_type' => $info), Response::SUCCESS, 'account_type_info_success');
    }

    /**
     * Create new account type
     */
    public function createAccountType()
    {
        // enable auth
        $this->shouldAuth();

        $this->validate(array(
            'key' => 'required',
            'name' => 'required',
        ));

        /** @var \rlAccountTypes $rlAccountTypes */
        $rlAccountTypes = asMake('rlAccountTypes');
        $existingTypes = array_keys($rlAccountTypes->types);

        if (in_array($this->requestData['key'], $existingTypes)) {
            Response::error(asLang('as_account_type_exist'), Response::SERVER_ERROR, 'account_type_duplicate_key');
        }

        $accountTypes = new AccountTypesAdapter();

        $accountTypes->create($this->requestData)
        ? Response::success(asLang('as_account_type_created'), Response::SUCCESS, 'created_account_type')
        : Response::error(asLang('as_account_type_creation_error'), Response::SERVER_ERROR, 'account_type_creation_error');
    }

    /**
     * Show all fields of provided account type
     *
     * @param  string $accountTypeKey
     */
    public function getAccountTypeFields($accountTypeKey)
    {
        $accountTypes = AccountTypesAdapter::getAllTypes();
        if (!$accountTypeKey || !$accountTypes[$accountTypeKey]) {
            Response::error(asLang('as_account_type_missing'), Response::BAD_REQUEST, 'account_type_missing');
        }

        /** @var \rlAccount $rlAccount */
        $rlAccount = asMake('rlAccount');
        /** @var \rlLang $rlLang */
        $rlLang = asMake('rlLang');
        /** @var \rlCommon $rlCommon */
        $rlCommon = asMake('rlCommon');
        $resultFields = array(
            'domain' => RL_URL_HOME,
        );

        $checkingAccountType = $accountTypes[$accountTypeKey];
        $fields = $rlAccount->getFields($checkingAccountType['ID']);
        $fields = $rlLang->replaceLangKeys($fields, 'account_fields', array('name', 'default', 'description'));
        $fields = $rlCommon->fieldValuesAdaptation($fields, 'account_fields');

        if (!$fields) {
            Response::success(asLang('as_fields_not_found'), Response::SUCCESS, 'fields_not_found');
        }

        foreach ($fields as $key => $field) {
            $resultFields['fields'][$key] = array(
                'key' => $field['Key'],
                'type' => $field['Type'],
                'name' => $field['name'],
            );
        }

        Response::success(array('account_fields' => $resultFields), Response::SUCCESS, 'account_type_fields_success');
    }

    /**
     * Show account field information in HTTP Response by field key
     *
     * @param string $accountField - Looking account field key
     */
    public function getAccountFieldInfo($accountField)
    {

        /** @var \rlValid $rlValid */
        $rlValid = asMake('rlValid');
        $field = $rlValid->xSql($accountField);

        if (!$field) {
            Response::error(asLang('as_bad_field_key'), Response::BAD_REQUEST, 'bad_field_key');
        }

        $fieldInfo = AccountFieldsAdapter::getByKey($field);
        $fieldInfo
        ? Response::success(array('info' => $fieldInfo), Response::SUCCESS, 'account_field_info_success')
        : Response::success(asLang('as_account_field_not_found'), Response::NOT_FOUND, 'account_field_not_found');
    }

    /**
     * Create account field
     */
    public function createAccountField()
    {

        $this->validate(array(
            'Type' => 'required',
            'Key' => 'required',
            'name' => 'required',
        ));

        $where = sprintf("`Key` = '%s'", $this->requestData['Key']);
        $fieldExist = $this->rlDb->getOne('ID', $where, 'account_fields');

        if ($fieldExist) {
            $fieldExistMsg = str_replace("{key}", $this->requestData['name'], asLang('notice_field_exist'));
            Response::error($fieldExistMsg, Response::BAD_REQUEST, 'account_field_exist');
        }

        $columns = $this->rlDb->getAll("SHOW COLUMNS FROM " . RL_DBPREFIX . "account_fields");
        $newField = array();
        foreach ($columns as $column) {
            if ($this->requestData[$column['Field']]) {
                $newField[$column['Field']] = $this->requestData[$column['Field']];
            }
        }

        if ($this->rlDb->insertOne($newField, 'account_fields')) {
            $newLang = array(
                'Key' => 'account_fields+name+' . $this->requestData['Key'],
                'Value' => $this->requestData['name'],
                'Module' => 'common',
                'Code' => RL_LANG_CODE,
            );
            $this->rlDb->insertOne($newLang, 'lang_keys');

            $createdField = AccountFieldsAdapter::getByKey($this->requestData['Key']);
            $userAdapter = new UserAdapter();
            $userAdapter->addColumnToTable($this->requestData['Key']);

            Response::success(array('info' => $createdField), Response::CREATED, 'account_field_created');
        }

        Response::success(asLang('as_something_wrong'), Response::SERVER_ERROR, 'something_went_wrong');
    }
}
