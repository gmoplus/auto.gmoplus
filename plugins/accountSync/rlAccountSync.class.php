<?php

use Flynax\Plugins\AccountSync\Adapters\AccountTypesAdapter;
use Flynax\Plugins\AccountSync\Events;
use Flynax\Plugins\AccountSync\Models\MetaData;

require_once RL_PLUGINS . 'accountSync/bootstrap.php';

class rlAccountSync
{
    /**
     * @var \Flynax\Plugins\AccountSync\AccountSync
     */
    private $accountSync;

    /**
     * rlAccountSync constructor.
     */
    public function __construct()
    {
        $folderName = 'accountSync';
        $paths = array(
            'url' => array(
                'view' => RL_PLUGINS_URL . $folderName . '/view/',
            ),
            'path' => array(
                'view' => RL_PLUGINS . $folderName . '/view/',
            ),
        );

        if ($GLOBALS['rlSmarty']) {
            $GLOBALS['rlSmarty']->assign('asConfigs', $paths);
        }

        $this->accountSync = new \Flynax\Plugins\AccountSync\AccountSync();
    }

    /**
     * Plugin installation method
     */
    public function install()
    {
        $token = new \Flynax\Plugins\AccountSync\Models\Token();
        $token->addTable();

        $metaData = new MetaData();
        $metaData->addTable();
    }

    /**
     * Plugin uninstalling method
     */
    public function uninstall()
    {
        $token = new \Flynax\Plugins\AccountSync\Models\Token();
        $token->deleteTable();

        $metaData = new MetaData();
        $metaData->dropTable();
    }

    /**
     * @hook apTplFooter
     */
    public function hookApTplFooter()
    {
        if ($_GET['controller'] != 'account_sync') {
            return false;
        }

        $this->accountSync->addAdminJs('admin.js');
        $this->accountSync->addAdminCss('style.css');
        $this->accountSync->filesWorker->loadView('apTplFooter');

        switch ($_GET['action']) {
            case 'build':
                $this->accountSync->addAdminJs('admin/pages/account_types_grid.js');
                break;
            case 'build_fields':
                $this->accountSync->addAdminJs('admin/pages/account_fields.js');
                break;
            case 'manage_users':
                $this->accountSync->addAdminJs('admin/pages/manage_users.js');
                break;
        }
    }

    /**
     * @hook ApAjaxRequest
     *
     * @param mixed $out  - Prepared AJAX answer
     * @param mixed $item - Calling AJAX item
     */
    public function hookApAjaxRequest(&$out = null, $item = null)
    {
        $item = $item ?: $GLOBALS['item'];
        if (!$this->accountSync->isValidAjaxRequest($item)) {
            return;
        }

        $out = &$out ?: $GLOBALS['out'];
        $out = $this->accountSync->handleApAjax($item);
    }

    /**
     * Update cache of all account types of all synchronized domains
     */
    public function updateAllAccountTypesCache()
    {
        $meta = new MetaData();
        $api = new API();
        $tokenManager = new Token();

        $allTokens = $tokenManager->getAll(true);
        foreach ($allTokens as $token) {
            $response = $api->withDomain($token['Domain'])->auth()->get('account/types');
            if ($response->status == 200 && $response->body['code_phrase'] == 'success_account_types') {
                $theirAccountTypes = $response->body['account_types'];
                $meta->set($token['Domain'], MetaData::META_ACCOUNT_TYPES, $theirAccountTypes);
            }
        }

        $meta->set(RL_URL_HOME, MetaData::META_ACCOUNT_TYPES, AccountTypesAdapter::fetchAllTypes());
    }

    /**
     * @hook registerSuccess
     */
    public function hookRegisterSuccess()
    {
        Events::afterRegistration($GLOBALS['profile_data'], $GLOBALS['account_data']);
    }

    /**
     * @hook phpQuickRegistrationBeforeInsert
     *
     * @param array $data - data of quick regist
     * @param array $info - account data - step 2
     *
     */
    public function hookPhpQuickRegistrationBeforeInsert($data, $info)
    {
        Events::beforeQuickRegistration($data, $info);
    }

    /**
     * @hook deleteAccountSetItems
     *
     * @param int $id - Removing item ID
     *
     * @return bool
     */
    public function hookDeleteAccountSetItems($id)
    {
        $id = (int) $id;
        if (!$id) {
            return false;
        }

        /** @var \rlAccount $rlAccount */
        $rlAccount = function_exists('asMake') ? asMake('rlAccount') : $GLOBALS['rlAccount'];
        $accountInfo = $rlAccount->getProfile($id);

        if (!$accountInfo['Mail']) {
            return false;
        }

        $GLOBALS['config']['trash']
        ? Events::afterProfileMovingToTrash($accountInfo['Mail'])
        : Events::afterProfileRemove($accountInfo['Mail']);
    }

    public function hookApPhpTrashBottom()
    {
        $xAjaxFunction = $_REQUEST['xjxfun'];
        if ($xAjaxFunction === 'ajaxRestoreTrashItem' && $trashRowID = (int) reset($_REQUEST['xjxargs'])) {
            $sql = "SELECT * FROM `" . RL_DBPREFIX . "trash_box` WHERE `ID` = {$trashRowID} ";
            $row = $GLOBALS['rlDb']->getRow($sql);

            if (in_array('accounts', explode(',', $row['Zones'])) && $accountID = (int) $row['Key']) {
                /** @var \rlAccount $rlAccount */
                $rlAccount = asMake('rlAccount');
                $accountInfo = $rlAccount->getProfile($accountID);

                if ($accountInfo['Mail']) {
                    $xAjaxFunction === 'ajaxRestoreTrashItem'
                    ? Events::afterProfileRestoreFromTrash($accountInfo['Mail'])
                    : Events::afterProfileRemove($accountInfo['Mail']);
                }
            }
        }
    }

    public function hookProfileEditAccountValidate()
    {
        $this->updateUserProfile($GLOBALS['profile_info']['Mail'], array(), $GLOBALS['account_data']);
    }

    public function hookAccountChangePassword()
    {
        Events::afterPasswordChanged($GLOBALS['account_info']['Mail'], $GLOBALS['new_password']);
    }

    public function hookApExtAccountsUpdate()
    {
        if ($GLOBALS['field'] != 'Status') {
            return false;
        }

        /** @var \rlAccount $rlAccount */
        $rlAccount = asMake('rlAccount');
        $userInfo = $rlAccount->getProfile((int) $GLOBALS['id']);

        Events::afterUserStatusChanged($userInfo['Mail'], $GLOBALS['value']);
    }

    /**
     * From Flynax > 4.7.0
     *
     * @param      $dirName
     * @param      $file
     * @param mixed $profileInfo
     */
    public function hookAjaxRequestProfileThumbnailAfterUpdate($dirName, $file, $profileInfo = null)
    {
        $email = $profileInfo ? $profileInfo['Mail'] : $_SESSION['account']['Mail'];
        $avatarUrl = RL_FILES_URL . $file['Photo_original'];

        Events::afterUserUploadAvatar($email, $avatarUrl);
    }

    private function updateUserProfile($email, $profileData, $accountData)
    {
        if (!$email) {
            return false;
        }

        Events::afterProfileEdit($email, $profileData, $accountData);
    }

    public function hookApPhpAccountsAfterEdit()
    {
        $profileData = $GLOBALS['profile_data'];
        $profileData['from_admin'] = true;

        $this->updateUserProfile($GLOBALS['profile_data']['mail'], $profileData, $GLOBALS['account_data']);
    }

    public function hookApPhpAccountsAfterAdd()
    {
        Events::afterRegistration($GLOBALS['profile_data'], $GLOBALS['account_data']);
    }

    /**
     * Update to 1.0.1
     */
    public function update101()
    {
        $sql = "ALTER TABLE `{db_prefix}as_metadata` CHANGE `Value` `Value` MEDIUMTEXT NOT NULL";
        $GLOBALS['rlDb']->query($sql);
    }
}
