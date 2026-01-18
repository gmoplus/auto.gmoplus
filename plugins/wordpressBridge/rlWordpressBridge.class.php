<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLWORDPRESSBRIDGE.CLASS.PHP
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

use Flynax\Plugin\WordPressBridge\Controllers\BlocksController;
use Flynax\Plugin\WordPressBridge\PluginPathBuilder;
use Flynax\Plugin\WordPressBridge\WordPressAPI\API;
use Flynax\Plugin\WordPressBridge\WordPressAPI\Token;
use Flynax\Utils\Valid;

require_once RL_PLUGINS . 'wordpressBridge/bootstrap.php';

class rlWordpressBridge
{
    /**
     * @var string - Path to the WordPress root folder
     */
    public $wp_root = "";

    /**
     * @var string  - Path to the functions.php file in the WordPress installation
     */
    public $wp_functions = "";

    /**
     * @var \reefless
     */
    private $reefless;

    /**
     * @var \rlDb
     */
    private $rlDb;

    /**
     * @var \rlActions
     */
    private $rlActions;

    public $cachedPosts = false;

    /**
     * rlWordpressBridge constructor.
     */
    public function __construct()
    {
        $this->reefless = $GLOBALS['reefless'];

        if (!$GLOBALS['rlActions']) {
            $this->reefless->loadClass('Actions');
        }

        $this->rlActions = $GLOBALS['rlActions'];
        $this->rlDb = $GLOBALS['rlDb'];

        if (class_exists(PluginPathBuilder::class)) {
            PluginPathBuilder::i()->setPluginName('wordpressBridge');
        }
    }

    /**
     * Check wp-config.php location in the WordPress plugin
     *
     * @since 2.0.0 - Removed $which_folder parameter
     *
     * @param string $folder_array - Folders array to the wp-config.php
     *
     * @return bool
     */
    public function determinateWpConfigs($folder_array)
    {
        global $rlConfig;

        $this->reefless->loadClass('Config');

        $path = RL_ROOT . implode("/", $folder_array) . RL_DS;

        $wp_config_path = $path . "wp-config.php";

        if (file_exists($wp_config_path)) {
            require_once $wp_config_path;

            $this->wp_root = ABSPATH;
            $this->wp_functions = ABSPATH . "wp-load.php";

            $rlConfig->setConfig('fl_wp_root', ABSPATH);
            $rlConfig->setConfig('wp_config_path', ABSPATH . "wp-load.php");
            return true;
        } else {

            return false;
        }
    }

    /**
     * Disconnect from Flynax Bridge plugin
     *
     * @return bool
     */
    public function wpDisconnect()
    {
        global $rlConfig;
        $wp_functions = $rlConfig->getConfig('wp_config_path');

        if (!file_exists($wp_functions)) {
            return false;
        }

        if ($wp_functions) {
            require_once $wp_functions;
            delete_option("FL_ROOT_DIR");
            delete_option("RL_ADMIN_CONTROL");
            delete_option("RL_LIBS");
        }
    }

    /**
     * @hook postPaymentComplete
     */
    public function hookPostPaymentComplete($data)
    {
        if ($data['service'] == 'listing' && $data['item_id']) {
            API::updateWpWidgetsCache();
        }
    }

    /**
     * Plugin install
     *
     * @since 2.0.0
     */
    public function install()
    {
        global $rlDb;

        /* create hidden configs */
        $sql = "INSERT INTO `" . RL_DBPREFIX . "config` (`Group_ID`, `Key`, `Default`, `Type`, `Plugin`) VALUES
        (0, 'wp_success', '0', 'text', 'wordpressBridge'),
        (0, 'wp_flynax_root', '0', 'text', 'wordpressBridge'),
        (0, 'wp_config_path', '0', 'text', 'wordpressBridge'),
        (0, 'flb_fl_token', '0', 'text', 'wordpressBridge'),
        (0, 'flb_wp_token', '0', 'text', 'wordpressBridgeCustom'),
        (0, 'fl_wp_root', '0', 'text', 'wordpressBridgeCustom')
        ";
        $rlDb->query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS `" . RL_DBPREFIX . "wb_usermeta` (
              `ID` INT(11) NOT NULL AUTO_INCREMENT,
              `Username` VARCHAR(100) NOT NULL,
              `Meta_name` VARCHAR(255) NOT NULL,
              `Meta_value` VARCHAR(255) NOT NULL,
              PRIMARY KEY  (`ID`)
        ) DEFAULT CHARSET=utf8;";
        $rlDb->query($sql);

        $GLOBALS['rlPlugin']->controller = '';

        $rlDb->addColumnsToTable(
            array(
                'wp_user_id' => "int(4) NOT NULL DEFAULT '0'",
            ),
            'accounts'
        );
    }

    /**
     * @since 2.0.0
     *
     * @hook  apPhpIndexBeforeController
     */
    public function hookApPhpIndexBeforeController()
    {
        if ($_SESSION['wp_error']) {
            unset($_SESSION['wp_error']);
            $GLOBALS['rlSmarty']->assign_by_ref('errors', $_SESSION['admin_notice']);
        }
    }

    /**
     * @since 2.0.0
     *
     * @hook  apTplFooter
     */
    public function hookApTplFooter()
    {
        global $cInfo;

        if (in_array($cInfo['Controller'], array('settings', 'controls'))) {
            PluginPathBuilder::i()->addJsToPage('lib.js');

            if ($cInfo['Controller'] === 'settings') {
                echo "<script>let wpBridge = new WordPressBridgeClass(); wpBridge.wpConnectChecking();</script>";
            }
        }
    }

    /**
     * @hook  deleteAccountSetItems
     */
    public function hookDeleteAccountSetItems($id = 0)
    {
        global $config, $rlDb;

        $wordPressAPI = new API();

        if (!$id) {
            return false;
        }

        $sql = "SELECT * FROM `{db_prefix}accounts` WHERE `ID` = {$id}";
        $account = $rlDb->getRow($sql);

        if ($account['wp_user_id']) {
            $data = [
                'wp_user_id' => $account['wp_user_id'],
            ];

            $wordPressAPI->callAPI('delete-user', 'get', $data, true);
        }
    }

    /**
     * @hook  profileEditAccountValidate
     */
    public function hookProfileEditAccountValidate()
    {
        global $config, $account_data, $account_info;

        $wordPressAPI = new API();

        $data = [
            'username' => $account_info['Username'],
            'first_name' => $account_data['First_name'],
            'last_name' => $account_data['Last_name'],
            'user_url' => $account_data['website'],
            'ID' => $account_info['wp_user_id'],
        ];

        $wordPressAPI->callAPI('update-user', 'get', $data, true);
    }

    /**
     * @hook  apPhpAccountsBeforeEdit
     */
    public function hookApPhpAccountsBeforeEdit()
    {
        global $config, $profile_data, $update_data, $account_data, $account_info;

        $wordPressAPI = new API();

        $typeKey = $this->rlDb->getOne('Key', "`ID`='" . $profile_data['type'] . "'", 'account_types');
        $ownPage = false;
        if ($update_data['location']) {
            $ownPage = $update_data['location'];
        }

        $data = [
            'username' => $account_info['Username'],
            'password' => $profile_data['password'] ? $profile_data['password'] : '',
            'user_email' => $update_data['mail'],
            'first_name' => $account_data['First_name'],
            'last_name' => $account_data['Last_name'],
            'own_address' => $ownPage,
            'user_url' => $account_data['website'],
            'ID' => $account_info['wp_user_id'],
        ];

        $wordPressAPI->callAPI('update-user', 'get', $data, true);
    }

    /**
     * @hook  profileEditProfileDone
     *
     * @return bool
     */
    public function hookProfileEditProfileDone()
    {
        global $config, $account_info, $profile_data;

        $wordPressAPI = new API();

        $data = [
            'username' => $account_info['Username'],
            'user_email' => $profile_data['mail'],
            'ID' => $account_info['wp_user_id'],
        ];

        $wordPressAPI->callAPI('update-user', 'get', $data, true);
    }

    /**
     * @since 2.0.0
     *
     * @hook  apPhpConfigBottom
     */
    public function hookApPhpConfigBottom()
    {
        global $configs;

        $hidden_settings = array(
            'wp_main_settings',
            'wp_reserve_login',
            'wp_reserve_email',
            'wp_box_image_width',
            'wp_box_image_height',
            'wp_last_box_setting',
            'wp_box_excerpt_size',
            'wp_box_short_excerpt_size',
            'wp_box_medium_excerpt_size',
        );

        if (!$GLOBALS['config']['wp_success']) {
            foreach ($configs as $group_key => $group_configs) {
                foreach ($group_configs as $key => $value) {
                    if (in_array($value['Key'], $hidden_settings)) {
                        unset($configs[$group_key][$key]);
                    }
                    if ($value['Key'] == 'wp_url') {
                        unset($configs[$group_key][$key]);
                    }
                }
            }
        }
    }

    /**
     * Content of the 'WP Last posts' block
     *
     * @since 2.0.0
     *
     */
    public function blockWPBridgeLastPost()
    {
        global $rlSmarty;

        $wordPressApi = new API();
        if (!$this->cachedPosts) {
            BlocksController::updateCache();
        }

        $posts = $this->cachedPosts
        ? json_decode($this->cachedPosts, true)
        : $wordPressApi->getRecentPosts((int) $GLOBALS['config']['wp_post_count']);

        foreach ($posts as $key => $value) {
            $posts[$key]['excerpt'] = stripslashes($value['excerpt']);
            $posts[$key]['title'] = stripslashes($value['title']);
        }

        $rlSmarty->assign("recent_posts", $posts);
        $rlSmarty->display(RL_PLUGINS . "wordpressBridge" . RL_DS . "recent_posts.tpl");
    }

    /**
     * @since 2.0.0
     *
     * @hook  apPhpConfigBeforeUpdate
     */
    public function hookApPhpConfigBeforeUpdate()
    {
        global $dConfig, $config, $lang;

        $oldConfig = $config['wp_path'];
        $newConfig = preg_replace(
            "#\[\[http(s)?_pref\]\]#",
            "http$1",
            $dConfig['wp_path']['value']
        );

        if ($newConfig == $oldConfig) {
            return;
        }

        if (!$this->isValidUrl($newConfig)) {
            foreach ($GLOBALS['update'] as $key => $update) {
                $settingKey = $update['where']['Key'];
                if ($settingKey == 'wp_path') {
                    unset($GLOBALS['update'][$key]);

                    /** @var \rlNotice $rlNotice */
                    $rlNotice = wbMake('rlNotice');
                    $rlNotice->saveNotice($lang['wpb_wrong_url'], 'errors');
                    break;
                }
            }
            return;
        }

        $wordPressApi = new API();
        $wordPressApi->saveWpUrl($newConfig);
    }

    /**
     * @since 2.0.0
     *
     * @hook  apPhpConfigAfterUpdate
     */
    public function hookApPhpConfigAfterUpdate()
    {
        global $dConfig, $config, $rlConfig;

        if (!$dConfig || !$config || !isset($dConfig['wp_path'])) {
            return;
        }

        $hasChanges = false;
        $urlHasChanged = false;
        foreach ($dConfig as $key => $val) {
            if (substr_count($key, 'wp_') <= 0) {
                continue;
            }

            $oldConfig = $config[$key];
            $newConfig = $val['value'];

            if ($key == 'wp_path' || $key == 'fl_wp_root') {
                $newConfig = preg_replace(
                    "#\[\[http(s)?_pref\]\]#",
                    "http$1",
                    $val['value']
                );
            }

            if ($newConfig != $oldConfig) {
                $hasChanges = true;
                $config[$key] = $newConfig;

                if ($key == 'wp_path' || $key == 'fl_wp_root') {
                    $urlHasChanged = true;
                }
            }
        }

        if (!$hasChanges) {
            return;
        }

        // Reset the tokens if the WordPress URL has changed
        if ($urlHasChanged) {
            $rlConfig->setConfig('flb_wp_token', '');
            $rlConfig->setConfig('flb_fl_token', '');
        } else {
            BlocksController::updateCache();
        }
    }

    /**
     * Add box related inline styles
     *
     * @since 2.1.1
     *
     * @hook tplHeader
     */
    public function hookTplHeader()
    {
        if ($GLOBALS['blocks']['wpbridge_last_post']) {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'wordpressBridge/header.tpl');
        }
    }

    /**
     * Checking does provided string which is suppose to be URL is valid one.
     *
     * @since 2.0.0
     *
     * @param string $url
     *
     * @return bool
     */
    public function isValidUrl($url)
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * @hook  profileEditProfileValidate
     */
    public function hookProfileEditProfileValidate()
    {
        global $profile_data;

        $wordPressAPI = new API();

        $data = [
            'username' => $GLOBALS['account_info']['Username'],
            'email' => $profile_data['mail'],
        ];

        $result = $wordPressAPI->callAPI('validate-user', 'get', $data, true);

        if ($GLOBALS['account_info']['Mail'] != $profile_data['mail'] && $result) {
            $GLOBALS['errors'][] = $GLOBALS['lang']['wpb_email_exist'];
            $GLOBALS['$error_fields'] .= 'profile[mail]';
        }
    }

    /**
     * @hook  phpAjaxChangePassBeforeUpdate
     *
     * @param string $password
     */
    public function hookPhpAjaxChangePassBeforeUpdate($password = '')
    {
        global $account_info;

        if (!$account_info['wp_user_id'] || !$password) {
            return;
        }

        $wordPressAPI = new API();

        $data = [
            'wp_user_id' => $account_info['wp_user_id'],
            'password'   => $password,
        ];

        $wordPressAPI->callAPI('update-password', 'get', $data, true);
    }

    /**
     * @hook  beforeRegister
     */
    public function hookBeforeRegister()
    {
        global $profile_data, $account_data;

        $wordPressAPI = new API();

        $typeKey = $this->rlDb->getOne('Key', "`ID`='" . $profile_data['type'] . "'", 'account_types');
        $ownPage = false;
        if ($profile_data['location']) {
            $ownPage = $profile_data['location'];
        }

        $data = [
            'username' => $profile_data['username'],
            'password' => $profile_data['password'],
            'email' => $profile_data['mail'],
            'type' => $typeKey,
            'first_name' => $account_data['First_name'],
            'last_name' => $account_data['Last_name'],
            'own_page' => $ownPage,
        ];

        $response = $wordPressAPI->callAPI('register-user', 'get', $data, true);

        if ($response->status == 200) {
            $update = [
                'fields' => [
                    'wp_user_id' => (int) $response->response['wp_user_id'],
                ],
                'where' => [
                    'Username' => $profile_data['username'],
                ],
            ];

            $GLOBALS['rlDb']->updateOne($update, 'accounts');
        }
    }

    /**
     * @hook  apPhpAccountsValidate
     *
     * @return bool
     */
    public function hookApPhpAccountsValidate()
    {
        global $profile_data, $account_info;

        if ($_GET['action'] == 'edit' && $profile_data['mail'] == $account_info['Mail']) {
            return;
        }

        $wordPressAPI = new API();

        $result = $wordPressAPI->callAPI('validate-user', 'get', ['user_email' => $profile_data['mail']], true);

        if ($result->response['exists']) {
            $GLOBALS['errors'][] = $GLOBALS['lang']['wpb_email_exist'];
            $GLOBALS['error_fields'][] = 'profile[mail]';
        }
    }

    /**
     * @since 2.1.0
     *
     * @hook  apPhpAccountsAfterAdd
     *
     * @return bool
     */
    public function hookApPhpAccountsAfterAdd()
    {
        global $profile_data, $account_data;

        $wordPressAPI = new API();

        $typeKey = $this->rlDb->getOne('Key', "`ID`='" . $profile_data['type'] . "'", 'account_types');
        $ownPage = false;
        if ($profile_data['location']) {
            $ownPage = $profile_data['location'];
        }

        $data = [
            'username' => $profile_data['username'],
            'password' => $profile_data['password'],
            'email' => $profile_data['mail'],
            'type' => $typeKey,
            'first_name' => $account_data['First_name'],
            'last_name' => $account_data['Last_name'],
            'own_page' => $ownPage,
        ];

        $response = $wordPressAPI->callAPI('register-user', 'get', $data, true);

        if ($response->status == 200) {
            $update = [
                'fields' => [
                    'wp_user_id' => (int) $response->response['wp_user_id'],
                ],
                'where' => [
                    'Username' => $profile_data['username'],
                ],
            ];

            $GLOBALS['rlDb']->updateOne($update, 'accounts');
        }
    }

    /**
     * @since 2.0.0
     * @hook  afterListingDone
     *
     * @param null|\Flynax\Classes\AddListing $AddListing
     * @param null|array $update
     */
    public function hookAfterListingDone($AddListing = null, $update = null)
    {
        if (!$AddListing) {
            // if Flynax version less  < 4.6.0
            $status = $GLOBALS['update_status']['fields']['Status'];
            $listingID = $GLOBALS['listing_id'];
        } else {
            // if Flynax version version >= 4.6.0
            $listingID = $AddListing->listingID;
            $status = isset($update['fields']['Status'])
            ? $update['fields']['Status']
            : $AddListing->listingData['Status'];
        }

        if ($status == 'active' && $listingID) {
            $changeStatus = array(
                'fields' => array(
                    'Status' => $status,
                ),
                'where' => array(
                    'ID' => (int) $listingID,
                ),
            );
            $this->rlActions->updateOne($changeStatus, 'listings');

            API::updateWpWidgetsCache();
        }
    }

    /**
     * @hook   afterListingEdit
     * @since  2.0.0
     */
    public function hookAfterListingEdit()
    {
        API::updateWpWidgetsCache();
    }

    /**
     * @since 2.0.0
     * @hook  apExtListingsAfterUpdate
     */
    public function hookApExtListingsAfterUpdate()
    {
        if ($GLOBALS['field'] != 'Status') {
            return false;
        }

        $oldValue = $GLOBALS['listing_info']['Status'];
        $newValue = $GLOBALS['value'];

        if ($oldValue != $newValue && in_array($newValue, array('active', 'approval'))) {
            API::updateWpWidgetsCache();
        }
    }

    /**
     * @since 2.1.0
     *
     * @hook  phpAfterDeleteListing
     */
    public function hookPhpAfterDeleteListing()
    {
        API::updateWpWidgetsCache();
    }

    /**
     * @since 2.0.0
     * @hook  apTplControlsForm
     */
    public function hookApTplControlsForm()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . "wordpressBridge/view/admin/apTplControlsForm.tpl");
    }

    /**
     * @since 2.0.0
     *
     * @param array       $out -  Response
     * @param string|null $item
     *
     * @return bool - False in the case if something went wrong
     */
    public function hookApAjaxRequest(&$out, $item = null)
    {
        global $config, $rlLang;

        $item = $item == null ? $GLOBALS['item'] : $item;
        if (!$this->isValidAjax($item)) {
            return false;
        }

        $out['status'] = 'ERROR';
        switch ($item) {
            case 'wpb_update_cache':
                if (BlocksController::updateCache()) {
                    $out['status'] = 'OK';
                    $out['message'] = $GLOBALS['lang']['wpb_refresh_cache_success'];
                }
                break;

            case 'wp_check_connection':
                if ($config['wp_path']) {
                    $wordPressApi = new API();

                    if ($config[Token::FLB_WP_TOKEN_KEY]
                        && $wordPressApi->isValidUrl($config['wp_path']) === true
                    ) {
                        $out = [
                            'status'  => 'OK',
                            'message' => $rlLang->getSystem('wpb_check_connection_success')
                        ];
                    } else {
                        $out = ['status' => 'ERROR'];
                    }

                } else {
                    $out = ['status' => 'ERROR'];
                }
                break;

            case 'wp_login':
                $username     = Valid::escape($_REQUEST['username']);
                $password     = Valid::escape($_REQUEST['password']);
                $wordPressApi = new API();

                if (($response = $wordPressApi->handShake($username, $password)) === true) {
                    $out = [
                        'status'  => 'OK',
                        'message' => $rlLang->getSystem('wpb_check_connection_success')
                    ];
                } else {
                    $out = ['status' => 'ERROR', 'message' => $response];
                }
                break;
        }
    }

    /**
     * @since 2.1.2 Added 'wp_check_connection' and 'wp_login' to the list
     * @since 2.0.0
     *
     * @param string $item - Request item
     *
     * @return bool
     */
    public function isValidAjax($item)
    {
        $validRequests = array(
            'wpb_update_cache',
            'wp_check_connection',
            'wp_login',
        );

        return in_array($item, $validRequests);
    }

    /**
     * @since 2.0.0
     *
     * Plugin uninstall
     */
    public function uninstall()
    {
        $this->wpDisconnect();

        unset($_SESSION['wp_bridge_error']);
        unset($_SESSION['attempt_count']);

        $wordPressAPI = new API();
        $wordPressAPI->callAPI('bridge-uninstalled', 'get', array(), true);

        $sql = "DELETE FROM `" . RL_DBPREFIX . "config` ";
        $sql .= "WHERE (`Key` = 'flb_wp_token' OR `Key` = 'fl_wp_root') AND `Plugin` = 'wordpressBridgeCustom'";
        $this->rlDb->query($sql);

        $this->rlDb->dropTable('wb_usermeta');

        $this->rlDb->dropColumnFromTable('wp_user_id', 'accounts');
    }

    /**
     * @hook  apMixConfigItem
     *
     * @since 2.1.0
     */
    public function hookApMixConfigItem(&$value)
    {
        if ($value['Key'] == 'wp_account_type') {
            $this->reefless->loadClass('AccountTypes');
            $types = $GLOBALS['rlAccountTypes']->types;
            $value['Values'] = array();
            foreach ($types as $key => $item) {
                $value['Values'][$key] = array(
                    'ID' => $item['Key'],
                    'name' => $GLOBALS['lang']['account_types+name+' . $item['Key']],
                );
            }
        }
    }
}
