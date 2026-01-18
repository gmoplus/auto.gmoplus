<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLHYBRIDAUTHLOGIN.CLASS.PHP
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

use Flynax\Plugins\HybridAuth\FilesWorker;
use Flynax\Plugins\HybridAuth\HybridAuth;
use Flynax\Plugins\HybridAuth\ModulesManager;
use Flynax\Plugins\HybridAuth\ProviderResolver;
use Flynax\Plugins\HybridAuth\Configs as HybridAuthConfigs;
use Flynax\Plugins\HybridAuth\Uid;
use Flynax\Abstracts\AbstractPlugin;
use Flynax\Interfaces\PluginInterface;

require_once RL_PLUGINS . 'hybridAuthLogin/bootstrap.php';

class rlHybridAuthLogin extends AbstractPlugin implements PluginInterface
{
    /**
     * @var string
     */
    const PLUGIN_NAME = 'hybridAuthLogin';

    /**
     * @var rlSmarty
     */
    protected $rlSmarty;

    /**
     * @var array - Plugin configurations array
     */
    protected $configs;

    /**
     * @var rlDb
     */
    protected $rlDb;

    /**
     * @var rlActions
     */
    protected $rlActions;

    /**
     * @var FilesWorker
     */
    protected $filesWorker;

    /**
     * @var HybridAuth
     */
    protected $hybridAuth;

    /**
     * @var ModulesManager
     */
    protected $modules;

    /**
     * @var ProviderResolver
     */
    protected $providers;

    /**
     * @var rlAccount
     */
    protected $rlAccount;

    /**
     * rlHybridAuth constructor.
     */
    public function __construct()
    {
        if (!function_exists('hybridAuthMakeObject')) {
            return false;
        }

        $this->prepareMainConfigurations();
        $this->rlActions = hybridAuthMakeObject('rlActions');
        $this->rlDb = hybridAuthMakeObject('rlDb');
        $this->rlAccount = hybridAuthMakeObject('rlAccount');

        if ($this->isFacebookConnectEnabled()) {
            $this->deactivate();
        }

        $this->hybridAuth = new HybridAuth();
        $this->providers = new ProviderResolver();
        $this->modules = new ModulesManager();
    }

    /**
     * Prepare Basic configurations of the plugin such as:
     *    - File structure of the plugin to correct including CSS/JS/View's files
     */
    public function prepareMainConfigurations()
    {
        $fileWorker = new FilesWorker(self::PLUGIN_NAME);
        $foldersStructure = $fileWorker->getIncludingFilesStructure();
        $this->configs = $foldersStructure;
        $this->filesWorker = $fileWorker;

        if ($GLOBALS['rlSmarty']) {
            $GLOBALS['rlSmarty']->assign('hybrid_configs', $foldersStructure);
            $this->rlSmarty = $GLOBALS['rlSmarty'];
        }

    }

    /**
     * @hook apTplFooter
     */
    public function hookApTplFooter()
    {
        if ($_GET['controller'] == 'hybrid_auth_login') {
            $this->addJS('lib.js');
        }
    }

    /**
     * @hook apTplHeader
     */
    public function hookApTplHeader()
    {
        if ($_GET['controller'] == 'hybrid_auth_login') {
            $this->addCss('admin_style.css');

            $this->filesWorker->loadView('apTplHeader');
        }
    }

    /**
     * @hook staticDataRegister
     *
     * @param rlStatic $rlStatic
     */
    public function hookStaticDataRegister($rlStatic)
    {
        if ($this->isFacebookConnectEnabled()) {
            return;
        }

        /** @var rlStatic $rlStatic */
        $rlStatic = $rlStatic !== null ? $rlStatic : hybridAuthMakeObject('rlStatic');
        $pageController = $GLOBALS['page_info']['Controller'];
        $icons = $this->hybridAuth->getSocialNetworksIcon();

        if (!empty($icons)) {
            $fileUrl = $this->getStaticFolderFileUrl('lib.js');
            $styleUrl = $this->getStaticFolderFileUrl('style.css');

            $rlStatic->addJS($fileUrl);
            $callingMethod = $pageController == 'registration' ? 'addHeaderCSS' : 'addFooterCSS';
            $rlStatic->{$callingMethod}($styleUrl);
        }
    }

    /**
     * Add JS file to the HTML markup
     *
     * @param string $fileName - Including file name
     */
    public function addJS($fileName)
    {
        $jsFileUrl = $this->getStaticFolderFileUrl($fileName);
        echo sprintf('<script type="text/javascript" src="%s"></script>', $jsFileUrl);
    }

    /**
     * Get url to the file inside 'static' folder or the plugin
     *
     * @param  string $fileName - File name, which url do you want to get
     * @return string
     */
    public function getStaticFolderFileUrl($fileName)
    {
        return sprintf('%s%s', $this->configs['url']['static'], $fileName);
    }

    /**
     * Add Css file to the header part of the page
     *
     * @param string $fileName   - File name, which you want to include.
     *                           Please notice, that all needed css styles should located in the 'static' directory
     */
    public function addCss($fileName)
    {
        $cssFileUrl = $this->getStaticFolderFileUrl($fileName);
        echo sprintf('<link type="text/css" href="%s" rel="stylesheet">', $cssFileUrl);
    }

    /**
     * @hook registrationBottomTpl
     */
    public function hookRegistrationBottomTpl()
    {
        if ($this->isFacebookConnectEnabled()) {
            return;
        }

        // Show the social icons in the first step only
        if ($GLOBALS['rlSmarty']->_tpl_vars['cur_step']) {
            return;
        }

        $isErrorExist = HybridAuthConfigs::i()->getConfig('error_exist');
        $activeProviderPage = isset($_GET['nvar_1']) && in_array($_GET['nvar_1'], $this->modules->getAllModules());

        if ($activeProviderPage && !$isErrorExist) {
            return;
        }

        $icons = $this->hybridAuth->getSocialNetworksIcon();
        $this->rlSmarty->assign('ha_networks_icons', $icons);
        $this->rlSmarty->assign('icon_container_class', 'in-registration');
        $view = $this->filesWorker->getViewPath('registrationBottomTpl');
        $this->rlSmarty->display($view);
    }

    /**
     * Deactivate plugin
     */
    public function deactivate()
    {
        return $this->changePluginStatus('approval');
    }

    /**
     * Activate plugin
     */
    public function activate()
    {
        return $this->changePluginStatus('active');
    }

    /**
     * Change plugin status to provided one: 'active', 'approval'
     *
     * @param string $status - Which status do you want to assign
     * @return bool
     */
    private function changePluginStatus($status = '')
    {
        $availableStatuses = array('active', 'approval');

        if (!in_array($status, $availableStatuses)) {
            return false;
        }

        // deactivate plugin
        $sql = "UPDATE `" . RL_DBPREFIX . "plugins` SET `Status` = '{$status}' ";
        $sql .= "WHERE `Key` = 'hybridAuthLogin' ";
        $this->rlDb->query($sql);

        // deactivate hooks
        $sql = "UPDATE `" . RL_DBPREFIX . "hooks` SET `Status` = '{$status}' ";
        $sql .= "WHERE `Plugin` = 'hybridAuthLogin' AND `Name` != 'apExtPluginsUpdate' ";
        $sql .= "AND `Name` != 'apTplPluginsGrid'";
        $this->rlDb->query($sql);

        // deactivate all langs
        $sql = "UPDATE `" . RL_DBPREFIX . "lang_keys` SET `Status` = '$status' ";
        $sql .= "WHERE `Plugin` = 'hybridAuthLogin' AND `Key` != 'ha_fb_cant_enable_plugin' ";
        $sql .= "AND `Key` != 'ha_fb_connect_plugin_conflict'";
        $this->rlDb->query($sql);

        return true;
    }

    /**
     * @hook ajaxRequest
     *
     * @param array  $out
     * @param string $request_mode
     * @param string $request_item
     * @param string $request_lang
     */
    public function hookAjaxRequest(&$out, $request_mode, $request_item, $request_lang)
    {
        if (!$this->isValidAjax($request_item)) {
            return;
        }

        $hybridAuth = new HybridAuth();

        /** @var rlLang $rlLang */
        $rlLang = hybridAuthMakeObject('rlLang');
        $GLOBALS['lang'] = $rlLang->getLangBySide('frontEnd', $request_lang);

        switch ($request_item) {
            case 'ha_isMultipleAccountType':
                $status = 'ERROR';
                if ($hybridAuth->isMultipleAccountTypeInstallation()) {
                    $status = 'OK';
                }

                $out['status'] = $status;
                break;

            case 'ha_getAccountType':
                $accountTypes = $hybridAuth->getAccountTypes();

                $out = array(
                    'status' => 'OK',
                    'body' => array(
                        'account_types' => $accountTypes,
                        'count' => count($accountTypes),
                    ),
                );

                if (defined('IS_ESCORT')) {
                    $singleListingTypes = $this->getSingleListingTypes();
                    $this->rlSmarty->assign('single_ltypes', $singleListingTypes);
                    $this->rlSmarty->assign('lang', $GLOBALS['lang']);
                    $tpl = $this->filesWorker->getViewPath('escortGenderFields');
                    $smartyOut = $this->rlSmarty->fetch($tpl, null, null, false);
                    $out['body']['gender_fields'] = $smartyOut;
                }

                if (method_exists($this->rlAccount, 'getAgreementFields')) {
                    $agreementFields = $this->rlAccount->getAgreementFields();
                    $this->rlSmarty->assign('agreement_fields', $agreementFields);
                    $this->rlSmarty->assign('lang', HybridAuthConfigs::i()->getConfig('flynax_phrases'));
                    $tpl = 'blocks/agreement_fields.tpl';

                    $out['body']['agreement_fields'] = array(
                        'show' => true,
                        'count' => count($agreementFields),
                        'html' => $this->rlSmarty->fetch($tpl, null, null, false),
                    );
                }

                $this->clearAllPluginSessions();
                break;

            case 'ha_saveAccountType':
                $_SESSION['ha_choosen_account_type'] = hybridAuthMakeObject('rlValid')->xSql($_POST['account_type']);
                break;

            case 'ha_clearSessionStorage':
                $this->clearAllPluginSessions();

                $out = array(
                    'status' => 'OK',
                );
                break;

            case 'ha_verifyPassword':
                $GLOBALS['reefless']->loginAttempt();

                $loginResult = $this->rlAccount->login($_SESSION['ha_non_verified']['email'], $_REQUEST['password'], false, true);

                if (is_bool($loginResult)) {
                    $uid = new Uid();
                    $uid->verifyUserByID($_SESSION['id']);

                    $out = array(
                        'status' => 'OK',
                        'redirect' => $_SESSION['ha_from_page'],
                    );

                    /** @var rlNotice $rlNotice */
                    $rlNotice = hybridAuthMakeObject('rlNotice');
                    $rlNotice->saveNotice($GLOBALS['lang']['notice_logged_in']);

                    $this->clearAllPluginSessions();

                    return;
                }

                $out = array(
                    'status' => 'ERROR',
                    'message' => $loginResult,
                );
                break;

            /** @since 2.0.0 */
            case 'ha_verifySystemUserPassword':
                $isValid = $this->verifyUserPasswordByEmail(
                    $_SESSION['ha_non_verified']['email'],
                    $_REQUEST['password']
                );

                $out = array(
                    'status' => $isValid ? 'OK' : 'ERROR',
                );
                break;
        }
    }

    /**
     * @since 2.0.0
     *
     * @param string $email
     * @param string $password
     *
     * @return bool
     */
    public function verifyUserPasswordByEmail($email, $password)
    {
        if (empty($email) || empty($password)) {
            return false;
        }

        $rlValid = hybridAuthMakeObject('rlValid');
        $rlValid->sql($email);
        $rlValid->sql($password);

        $sql = "SELECT `ID`, `Password` FROM `" . RL_DBPREFIX . "accounts` ";
        $sql .= "WHERE `Mail` = '{$email}'";
        $account = $this->rlDb->getRow($sql);

        if (empty($account)) {
            return false;
        }

        if (FLSecurity::verifyPassword($password, $account['Password'], false)) {
            $uid = new Uid();
            $uid->verifyUserByID($account['ID']);

            return true;
        }

        return false;
    }

    /**
     * Clear all session storage
     */
    public function clearAllPluginSessions()
    {
        unset($_SESSION['ha_login_fail'], $_SESSION['ha_from_page'], $_SESSION['ha_non_verified']);
    }

    /**
     * Clears all session variables related to the HybridAuth plugin and account type choosing.
     *
     * @since 2.2.0
     */
    public function clearAccountTypeSession()
    {
        unset(
            $_SESSION['ha-account-type'],
            $_SESSION['ha_choosen_account_type'],
            $_SESSION['ha_temp_telegram_storage']
        );

        $hybridAuthStorage = new \Hybridauth\Storage\Session();
        $hybridAuthStorage->clear();
    }

    /**
     * Does sending AJAX request to the request.ajax.php is related to the HybridAuth plugin
     *
     * @param  string $item - Request item
     * @return bool
     */
    public function isValidAjax($item)
    {
        $validRequests = array(
            'ha_isMultipleAccountType',
            'ha_getAccountType',
            'ha_saveAccountType',
            'ha_verifyPassword',
            'ha_verifySystemUserPassword',
            'ha_clearSessionStorage',
        );

        return in_array($item, $validRequests);
    }

    /**
     * @hook phpBeforeLoginValidation
     */
    public function hookPhpBeforeLoginValidation()
    {
        $provider = $_GET['with'];
        if (isset($_GET['login'])) {
            $modulesManager = new ModulesManager();
            $availableProviders = $modulesManager->getAllModules();

            if (in_array($provider, $availableProviders)) {
                $vk = (new ProviderResolver($provider))->getActiveProvider();
                $user = $vk->authenticate();

                exit;
            }
        }
    }

    /**
     * @hook apPhpTrashBottom
     */
    public function hookApPhpTrashBottom()
    {
        $xAjaxFunction = $_REQUEST['xjxfun'];
        if ($xAjaxFunction == 'ajaxDeleteTrashItem' && $trashRowID = (int) reset($_REQUEST['xjxargs'])) {
            $sql = "SELECT * FROM `" . RL_DBPREFIX . "trash_box` WHERE `ID` = {$trashRowID} ";
            $row = $this->rlDb->getRow($sql);
            if (in_array('accounts', explode(',', $row['Zones'])) && $accountID = (int) $row['Key']) {
                $uids = new Uid();
                $uids->removeByAccountID($accountID);
            }
        }
    }

    /**
     * @hook deleteAccountSetItems
     *
     * @param  int $id - Deleting account ID
     * @return bool    - Removing account status
     */
    public function hookDeleteAccountSetItems($id)
    {
        $configs = HybridAuthConfigs::i()->getConfig('flynax_configs');
        if ($configs['trash']) {
            return false;
        }

        $uids = new Uid();
        return $id ? $uids->removeByAccountID($id) : false;
    }

    /**
     * @hook apExtPluginsUpdate
     *
     * @return bool
     */
    public function hookApExtPluginsUpdate()
    {
        $pluginKey = $GLOBALS['plugin_info']['Key'];

        if ($pluginKey != 'hybridAuthLogin' && $pluginKey != 'facebookConnect') {
            return false;
        }

        $status = $GLOBALS['updateData']['fields']['Status'];
        if ($this->isFacebookConnectEnabled()) {
            $status == 'active' ? $this->activate() : $this->deactivate();
        }

        if ($pluginKey == 'hybridAuthLogin') {
            $this->forceActivateHook('apTplPluginsGrid');
            $this->forceActivateLang('ha_fb_cant_enable_plugin');
            $this->forceActivateLang('ha_fb_connect_plugin_conflict');
        }
    }

    /**
     * Does facebook connect plugin is installed, active and module were enabled
     *
     * @return bool
     */
    public function isFacebookConnectEnabled()
    {
        return in_array('facebookConnect', array_keys($GLOBALS['plugins']))
            && $GLOBALS['config']['facebookConnect_module'];
    }

    /**
     * @hook tplUserNavbar
     */
    public function hookTplUserNavbar()
    {
        if ($this->isFacebookConnectEnabled()) {
            return false;
        }

        $icons = $this->hybridAuth->getSocialNetworksIcon();
        $this->rlSmarty->assign('ha_networks_icons', $icons);
        $this->rlSmarty->assign('icon_container_class', 'in-navigation');
        $view = $this->filesWorker->getViewPath('iconsContainer');
        $this->rlSmarty->display($view);
    }

    /**
     * @hook tplFooter
     */
    public function hookTplFooter()
    {
        if (!$this->hybridAuth->getSocialNetworksIcon() || $this->isFacebookConnectEnabled()) {
            return;
        }

        $accountTypes = $this->hybridAuth->getAccountTypes();
        $this->rlSmarty->assign('account_types', $accountTypes);
        $view = $this->filesWorker->getViewPath('footer');
        $this->rlSmarty->display($view);
    }

    /**
     * Plugin install
     */
    public function install()
    {
        $this->addTables();

        $availableProviders = $this->modules->getAllModules();
        foreach ($availableProviders as $provider) {
            $this->providers->addToDb($provider, 'approval');
        }

        $this->modules->updateGroupID();
    }

    /**
     * Add provider to the DB
     *
     * @since 2.1.5
     *
     * @param string $provider - Provider name
     * @param string $status   - Provider status
     */
    public function addProvider(string $provider, string $status = 'approval'): void
    {
        $availableProviders = $this->modules->getAllModules();

        if (false !== array_search($provider, $availableProviders)) {
            $this->providers->addToDb($provider, $status);
        }
    }

    /**
     * Add new tables of the plugin to the Database
     */
    public function addTables()
    {
        $this->rlDb->createTable(
            'ha_uids',
            "`ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
             `Account_ID` int(11) DEFAULT NULL,
             `Provider` varchar(50) DEFAULT NULL,
             `UID` varchar(255) DEFAULT NULL,
             `Verified` enum('0','1') DEFAULT '0',
             PRIMARY KEY (`ID`)"
        );

        $this->rlDb->createTable(
            'ha_providers',
            "`ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
             `Provider` varchar(50) DEFAULT NULL,
             `Order` int(2) DEFAULT 0,
             `Status` enum('active','approval') DEFAULT 'active',
             PRIMARY KEY (`ID`)"
        );
    }

    /**
     * Remove table which where created by plugin
     */
    public function removeTables()
    {
        $this->rlDb->dropTables(['ha_uids', 'ha_providers']);
    }

    /**
     * Getting all single listing type for pesonal account type of Escort installation
     *
     * @return array
     */
    public function getSingleListingTypes()
    {
        /** @var rlLang $rlLang */
        $rlLang = hybridAuthMakeObject('rlLang');

        if (!USE_GENDER_FIELD) {
            return array();
        }

        $sql = "SELECT `ID`, `Key` FROM `" . RL_DBPREFIX . "listing_types`";
        $sql .= "WHERE `Escort_Type` = '" . rlEscort::SINGLE . "' AND `Status` = 'active'";
        $singleLtypes = (array) $this->rlDb->getAll($sql, 'Key');
        $singleLtypes = $rlLang->replaceLangKeys($singleLtypes, 'listing_types', 'name');

        return $singleLtypes;
    }

    /**
     * @hook phpDeleteAccountDetails
     * @param  int $id - ID of removing account
     *
     * @return bool
     */
    public function hookPhpDeleteAccountDetails($id)
    {
        if (!$id) {
            return false;
        }

        $uids = new Uid();
        $uids->removeByAccountID($id);

        $this->clearAllPluginSessions();
        $this->clearAccountTypeSession();
    }

    /**
     * @hook apTplPluginsGrid
     */
    public function hookApTplPluginsGrid()
    {
        global $rlLang, $config, $lang;

        if (!$this->isFacebookConnectEnabled()) {
            return;
        }

        if ('' === $message = (string) $lang['ha_fb_cant_enable_plugin']) {
            $message = $rlLang->getPhrase(array(
                'key'      => 'ha_fb_cant_enable_plugin',
                'lang'     => $config['lang'],
                'db_check' => true,
            ));
        }
        $message = addslashes($message);

        echo <<<JS
        pluginsGrid.getInstance().columns[3].editor.addListener('beforeselect', function(event, value){
            if (event.gridEditor.record.data.Key === 'hybridAuthLogin'
                && event.value == '{$lang['approval']}'
                && value.data.field1 == 'active'
            ) {
                Ext.MessageBox.alert(lang['ext_notice'], '{$message}');
                return false;
            }
        });
JS;
    }

    /**
     * @hook apPhpConfigAfterUpdate
     */
    public function hookApPhpConfigAfterUpdate()
    {
        global $lang;

        $oldPhrase = $lang['config_saved'];
        $facebookConnectModule = (bool) $GLOBALS['dConfig']['facebookConnect_module'];

        if (in_array('facebookConnect', array_keys($GLOBALS['plugins'])) && $facebookConnectModule) {
            $lang['config_saved'] = sprintf(
                '<li>%s</li><li>%s</li>',
                $oldPhrase,
                $lang['ha_fb_connect_plugin_conflict']
            );
        }
    }

    /**
     * Plugin uninstall
     */
    public function uninstall()
    {
        $this->removeTables();
    }

    /**
     * Activate hook even if plugin is disabled
     *
     * @param  string $hook - Plugin hook
     * @return bool
     */
    public function forceActivateHook($hook)
    {
        if (!$hook) {
            return false;
        }

        $sql = "UPDATE `" . RL_DBPREFIX . "hooks` SET `Status` = 'active' ";
        $sql .= "WHERE `Plugin` = 'hybridAuthLogin' AND `Name` = '{$hook}'";

        return (bool) $this->rlDb->query($sql);
    }

    /**
     * Activating phrase even if plugin is deactivated
     *
     * @param string $langKey
     */
    public function forceActivateLang($langKey)
    {
        $sql = "UPDATE `" . RL_DBPREFIX . "lang_keys` SET `Status` = 'active' ";
        $sql .= "WHERE `Plugin` = 'hybridAuthLogin' AND `Key` = '{$langKey}'";

        $this->rlDb->query($sql);
    }

    /**
     * @hook phpLogOut
     * @since 2.2.0
     */
    public function hookPhpLogOut()
    {
        $this->clearAllPluginSessions();
        $this->clearAccountTypeSession();
    }

    /**
     * Send text from mail via provider (for example, Telegram)
     * The provider must implement sendMessage() method
     *
     * @hook phpMailSend
     * @since 2.2.0
     */
    public function hookPhpMailSend($subject, $body, $attach_file, $from_mail, $from_name, &$to, $template)
    {
        $domain = ltrim($GLOBALS['domain_info']['domain'], '.');

        if (false === strpos($to, $domain)) {
            return;
        }

        $pattern = "/user([0-9]+)@{$domain}/";
        preg_match($pattern, $to, $matches);

        if (!$userIdentifier = ($matches && isset($matches[1]) ? $matches[1] : null)) {
            return;
        }

        if (!$userUidInfo = (new Uid())->get($userIdentifier)) {
            return;
        }

        $provider = (new ProviderResolver($userUidInfo['Provider']))->getActiveProvider();

        if ($provider && method_exists($provider, 'sendMessage')) {
            $provider->sendMessage($body, $userIdentifier);
            $to = null; // Stop default mail sending
        }
    }

    /**
     * Update to 2.1.0 version
     */
    public function update210()
    {
        global $rlDb;

        $rlDb->query("
            DELETE FROM `{db_prefix}config`
            WHERE `Plugin` = 'hybridAuthLogin' AND `Key` IN(
                'ha_instagram_app_id',
                'ha_instagram_app_secret'
            )
        ");

        $rlDb->query("
            DELETE FROM `{db_prefix}lang_keys`
            WHERE `Plugin` = 'hybridAuthLogin' AND `Key` LIKE 'config+name+ha\_instagram\_%'
        ");

        $rlDb->query("
            DELETE FROM `{db_prefix}ha_providers`
            WHERE `Provider` = 'instagram'
        ");

        $rlDb->query("
            INSERT INTO `{db_prefix}ha_providers` (`Provider`, `Status`)
            VALUES ('apple', 'approval')
        ");

        @unlink(RL_PLUGINS . '/hybridAuthLogin/src/Providers/Instagram.php');
    }

    /**
     * Update to 2.1.2 version
     */
    public function update212()
    {
        global $rlDb;

        @unlink(RL_PLUGINS . 'hybridAuthLogin/src/functions.php');
        @unlink(RL_PLUGINS . 'hybridAuthLogin/static/admin_lib.js');

        $phrases = [
            'ha_something_went_wrong',
            'ha_continue',
            'ha_something_wrong_with_provider',
            'ha_all_fields_are_required'
        ];

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
             WHERE `Plugin` = 'hybridAuthLogin' AND `Key` IN ('" . implode("','", $phrases) . "')"
        );

        // Translate ru phrases
        $languages = $GLOBALS['languages'];
        if (in_array('ru', array_keys($languages))) {
            $russianTranslation = json_decode(file_get_contents(RL_UPLOAD . 'hybridAuthLogin/i18n/ru.json'), true);
            foreach ($russianTranslation as $phraseKey => $phraseValue) {
                $rlDb->updateOne(array(
                    'fields' => array('Value' => $phraseValue),
                    'where'  => array('Key'   => $phraseKey, 'Code' => 'ru'),
                ), 'lang_keys');
            }
        }
    }

    /**
     * Update to 2.1.4 version
     */
    public function update214()
    {
        @unlink(RL_PLUGINS . 'hybridAuthLogin/view/registrationStepActionsTpl.tpl');
    }

    /**
     * Update to 2.1.5 version
     */
    public function update215()
    {
        $this->addProvider('yandex', 'approval');
        $this->addProvider('mailru', 'approval');
        $this->addProvider('odnoklassniki', 'approval');
    }

    /**
     * Update to 2.2.0 version
     */
    public function update220()
    {
        $this->addProvider('telegram', 'approval');
    }
}
