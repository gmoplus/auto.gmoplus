<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLFBCONNECT.CLASS.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2023
 *	https://www.flynax.com
 *
 ******************************************************************************/

class rlFBConnect
{
    /**
     * @var rlDb
     * @since 2.3.0
     */
    protected $rlDb;

    /**
     * Class constructor
     *
     * @since 2.3.0
     */
    public function __construct()
    {
        $this->rlDb = &$GLOBALS['rlDb'];
    }

    /**
     * Install the plugin
     *
     * @since 2.2.5
     **/
    public function install()
    {
        $this->rlDb->addColumnsToTable(array('facebook_ID' => 'VARCHAR(25) NOT NULL'), 'accounts');

        $group_id = (int) $this->rlDb->getOne('ID', "`Key` = 'facebookConnect'", 'config_groups');
        $this->rlDb->query("
            INSERT INTO `" . RL_DBPREFIX . "config` (`Key`, `Default`, `Plugin`)
            VALUES ('facebookConnect_configs_group_id', {$group_id}, 'facebookConnect')
        ");
    }

    /**
     * Uninstall the plugin
     *
     * @since 2.2.5
     **/
    public function uninstall()
    {
        $this->rlDb->dropColumnFromTable('facebook_ID', 'accounts');
    }

    /** Hooks **/

    /**
     * @hook phpBeforeLoginValidation
     *
     * @since 2.2.5
     */
    public function hookPhpBeforeLoginValidation()
    {
        if (false === $this->isConfigured(true)) {
            return;
        }
        $this->facebookConnectHandler();
    }

    /**
     * @hook specialBlock
     *
     * @since 2.2.5
     */
    public function hookSpecialBlock()
    {
        if (false === $this->isConfigured()) {
            return;
        }
        $seo_base = SEO_BASE;

        if ($GLOBALS['geo_filter_data']['geo_url']) {
            $seo_base = str_replace($GLOBALS['geo_filter_data']['geo_url'] . "/", '', $seo_base);
        }

        $urlBase = $seo_base;
        $urlBase = (stristr($_SERVER['HTTPS'], 'on') ? str_replace('http:', 'https:', $urlBase) : $urlBase);

        $_SESSION['facebook_base_url'] = $urlBase;
        $_SESSION['facebook_lang_code'] = RL_LANG_CODE;
        $_SESSION['facebook_page_login'] = $GLOBALS['pages']['login'];
        $_SESSION['facebook_page_registration'] = $GLOBALS['pages']['registration'];
        $_SESSION['facebook_referer'] = $_SERVER['SCRIPT_URI'];
    }

    /**
     * @hook tplUserNavbar
     *
     * @since 2.2.5
     */
    public function hookTplUserNavbar()
    {
        if (false === $this->isConfigured()) {
            return;
        }

        printf('<img onclick="fcLogin();" id="fb-nav-bar" style="cursor:pointer;" src="%s" title="%s" alt="" />',
            RL_PLUGINS_URL . 'facebookConnect/static/fb_ico.png',
            $GLOBALS['lang']['fConnect_login_title']
        );
    }

    /**
     * @hook tplFooter
     *
     * @since 2.2.5
     */
    public function hookTplFooter()
    {
        if (false === $this->isConfigured(true)) {
            return;
        }

        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'facebookConnect/connect.tpl');
    }

    /**
     * @hook tplRegistrationCheckbox
     */
    public function hookTplRegistrationCheckbox()
    {
        global $rlSmarty, $account_types, $config;

        if (!$this->isRegistrationProcess() || $this->isQuickRegistration()) {
            return;
        }

        if (isset($_SESSION['facebook_process']) && $_SESSION['facebook_process'] == 'registration') {
            $def_account_type_id = 0;

            if ($config['facebookConnect_account_type']) {
                foreach($account_types as $type_id => $type) {
                    if ($type['Key'] === $config['facebookConnect_account_type']) {
                        $def_account_type_id = $type_id;
                        break;
                    }
                }
            }

            $rlSmarty->assign('facebook_def_account_type_id', $def_account_type_id);
            $rlSmarty->assign('facebook_info', $_SESSION['facebook_info']);
            $rlSmarty->display(RL_PLUGINS . 'facebookConnect/registration.tpl');
        }
    }

    /**
     * @hook phpRegistrationBeforeInsert
     */
    public function hookPhpRegistrationBeforeInsert(&$new_account)
    {
        global $account_types;

        if ($this->isRegistrationProcess() && !$this->isQuickRegistration()) {
            $new_account['facebook_ID'] = (int) $_SESSION['facebook_info']['id'];

            // turn of system options only if Facebook account is verified!
            if ($this->isFBAccountVerified()) {
                $new_account['Password_tmp'] = '';
                $new_account['Status'] = 'active';
                unset($new_account['Confirm_code']);

                $type_id = (int) $_POST['profile']['type'];
                $account_types[$type_id]['Email_confirmation'] = 0;
                $account_types[$type_id]['Admin_confirmation'] = 0;
            }
        }
    }

    /**
     * @hook registerSuccess
     */
    public function hookRegisterSuccess()
    {
        global $account_types;

        if (false === $this->isRegistrationProcessAndFBAccountVerified() || $this->isQuickRegistration()) {
            return;
        }
        $type_id = (int) $GLOBALS['profile_data']['type'];

        $account_types[$type_id]['Email_confirmation'] = 0;
        $account_types[$type_id]['Admin_confirmation'] = 0;
        $account_types[$type_id]['Auto_login'] = 1;
    }

    /**
     * @hook registrationDone
     */
    public function hookRegistrationDone()
    {
        global $account_types;

        if (false === $this->isRegistrationProcessAndFBAccountVerified() || $this->isQuickRegistration()) {
            return;
        }
        $type_id = (int) $_SESSION['registr_account_type'];
        $account_id = (int) $_SESSION['registration']['account_id'];

        $account_types[$type_id]['Auto_login'] = 1;
        $account_types[$type_id]['Email_confirmation'] = 0;
        $account_types[$type_id]['Admin_confirmation'] = 0;

        if ($account_id && $_SESSION['account']['Status'] != 'active') {
            $sql = "UPDATE `" . RL_DBPREFIX . "accounts` SET `Status` = 'active' ";
            $sql .= "WHERE `ID` = " . $account_id;
            $this->rlDb->query($sql);
        }
        unset($_SESSION['facebook_process']);
    }

    /**
     * @hook phpSendRegistrationNotification
     *
     * @since 2.2.5 (Flynax 4.5.1 required)
     *
     * @param array $type - account type details
     * @param array $data - account details from registation form
     */
    public function hookPhpSendRegistrationNotification(&$type, &$data)
    {
        if (false === $this->isRegistrationProcessAndFBAccountVerified() || $this->isQuickRegistration()) {
            return;
        }

        $type['Email_confirmation'] = 0;
        $type['Admin_confirmation'] = 0;
    }

    /**
     * @hook apPhpConfigBottom
     * @since v2.2.3
     */
    public function hookApPhpConfigBottom()
    {
        global $lang, $configs;

        $where = "`Key` = 'facebookConnect' AND `Plugin` = 'facebookConnect'";
        $group_id = (int) $this->rlDb->getOne('ID', $where, 'config_groups');

        if (!empty($configs[$group_id])) {
            foreach ($configs[$group_id] as $key => $entry) {
                if ($entry['Key'] == 'facebookConnect_account_type') {
                    // get accout types
                    $sql = "SELECT `Key` FROM `" . RL_DBPREFIX . "account_types` ";
                    $sql .= "WHERE `Status` = 'active' AND `Key` NOT IN('visitor', 'affiliate') ORDER BY `Position`";
                    $tmpTypes = $this->rlDb->getAll($sql);

                    $configs[$group_id][$key]['Values'] = array();

                    foreach ($tmpTypes as $tKey => $tEntry) {
                        $configs[$group_id][$key]['Values'][] = array(
                            'ID' => $tEntry['Key'],
                            'name' => $lang["account_types+name+" . $tEntry['Key']],
                        );
                    }
                    unset($tmpTypes);
                }
            }
        }
    }

    /**
     * @hook reeflessRedirctVars
     * @since 2.2.6
     */
    public function hookReeflessRedirctVars()
    {
        global $lang;

        if (defined('REALM')
            && $GLOBALS['controller'] == 'settings'
            && $_POST['group_id'] == $GLOBALS['config']['facebookConnect_configs_group_id']
        ) {
            $_config = $_POST['post_config'];

            if ($_config['facebookConnect_module']['value'] == 1
                && ($_config['facebookConnect_appid']['value'] == ''
                    || $_config['facebookConnect_secret']['value'] == ''
                    || $_config['facebookConnect_account_type']['value'] == '')
            ) {
                $GLOBALS['rlConfig']->setConfig('facebookConnect_module', 0);

                $msg = sprintf(
                    $lang['fConnect_save_settings_error_notice'],
                    $lang['config+name+facebookConnect_appid'],
                    $lang['config+name+facebookConnect_secret'],
                    $lang['config+name+facebookConnect_account_type']
                );
                $GLOBALS['rlNotice']->saveNotice($msg, 'errors');
            }
        }
    }

    /**
     * @hook apTplFooter
     * @since 2.2.6
     */
    public function hookApTplFooter()
    {
        if ($GLOBALS['controller'] !== 'settings') {
            return;
        }

        $instruction = <<<HTML
<div class="settings_desc" style="line-height:20px;padding:0px 0 15px 3px;">
    To give your users the ability to log into your site with their Facebook accounts you have to set up an Application first:<br>
    1. Sign into your <a target="_blank" href="https://developers.facebook.com/apps">https://developers.facebook.com/apps</a> account and select "<b>Add a New App</b>" from the "Apps" menu at the top;<br>
    2. Fill out the pop-up form, and click "<b>Create App</b>";<br>
    3. Click the "<b>Get Started</b>" button next to "Facebook Login";<br>
    4. Set <b style="color:#b01935;">{domain}plugins/facebookConnect/request.php</b> to "Valid OAuth redirect URIs" from "Client OAuth Settings";<br>
    5. Go to the "Settings" and click "<b>Add Platform</b>", then "<b>Website</b>", and enter your "Site URL";<br>
    6. Save a copy or write down the <b>App ID</b> and <b>App Secret</b> (you\'ll need them in the next step) and save changes.<br>
    7. Go to "<b>App Review</b>" and make the App public.<br>
    8. Enter your App ID and App Secret below, which you got above.<br><br>
</div>
HTML;

        $instruction = str_replace('{domain}', RL_URL_HOME, $instruction);
        printf(
            "<script>\$('#facebookConnect_module_0').closest('table.form').before('%s');</script>",
            preg_replace('/(\n|\t|\r)?/', '', $instruction)
        );
    }

    /**
     * @hook phpQuickRegistrationBeforeInsert
     * @since 2.3.1
     */
    public function hookPhpQuickRegistrationBeforeInsert(&$new_account)
    {
        $new_account['facebook_ID'] = (int) $_SESSION['facebook_info']['id'];
    }

    /** Common **/

    /**
     * @since 2.2.6
     */
    private function isConfigured($skipConstant = false)
    {
        global $config;

        if (($skipConstant || !defined('IS_LOGIN'))
            && $config['facebookConnect_module'] == 1
            && $config['facebookConnect_appid'] != ''
            && $config['facebookConnect_secret'] != ''
            && $config['facebookConnect_account_type'] != ''
        ) {
            return true;
        }
        return false;
    }

    /**
     * @since 2.2.6
     */
    private function isRegistrationProcessAndFBAccountVerified()
    {
        if (false === $this->isRegistrationProcess()) {
            return false;
        }
        return $this->isFBAccountVerified();
    }

    /**
     * @since 2.2.6
     */
    private function isRegistrationProcess()
    {
        if (isset($_SESSION['facebook_process']) && $_SESSION['facebook_process'] == 'registration') {
            if (0 !== (int) $_SESSION['facebook_info']['id']) {
                return true;
            }
        }
        return false;
    }

    /**
     * @since 2.2.6
     */
    private function isFBAccountVerified()
    {
        return (bool) $_SESSION['facebook_info']['verified'];
    }

    /**
     * Check if the registration type is Quick
     *
     * @since  2.3.0
     *
     * @return bool
     */
    private function isQuickRegistration()
    {
        return (bool) $GLOBALS['config']['facebookConnect_quick_registration'];
    }

    /**
     * Handler for login,registraion pages
     */
    public function facebookConnectHandler()
    {
        global $config, $reefless, $rlAccount, $rlAccountTypes, $rlMail;

        if (0 !== $facebook_id = (int) $_SESSION['facebook_info']['id']) {
            $reefless->loadClass('Account');
            $reefless->loadClass('Actions');

            if ($_SESSION['facebook_process'] == 'login') {
                $db_prefix = RL_DBPREFIX;
                $user_info = $this->rlDb->getRow("
                    SELECT `Username`, `Mail`, `Password` FROM `{$db_prefix}accounts`
                    WHERE `facebook_ID` = {$facebook_id} AND `Status` <> 'trash' LIMIT 1
                ");

                if (!empty($user_info)) {
                    $this->turnOffLoginAttemptsControl();

                    // detect login method option
                    $login_field = $config['account_login_mode'] == 'email' ? 'Mail' : 'Username';

                    $result = $rlAccount->login($user_info[$login_field], $user_info['Password'], true);
                    $this->resultHandlerAfterLogin($result, $user_info);
                }
            } else if ($_SESSION['facebook_process'] == 'registration') {
                if ($this->isQuickRegistration()) {
                    $name = $_SESSION['facebook_info']['name'];
                    $email = $_SESSION['facebook_info']['email'];

                    if (!isset($rlAccountTypes->types)) {
                        $reefless->loadClass('AccountTypes', null, false, true);
                    }

                    $account_type_key = $config['facebookConnect_account_type'];
                    $account_type_id = (int) $rlAccountTypes->types[$account_type_key]['ID'];
                    $new_account = $rlAccount->quickRegistration($name, $email, 0, $account_type_id);

                    if (false !== $new_account) {
                        list($login, $password, $account_id) = $new_account;

                        $this->turnOffLoginAttemptsControl();
                        $result = $rlAccount->login($login, $password);

                        $user_info = array(
                            'Username' => $name,
                            'Mail' => $email,
                            'Password' => $password,
                        );

                        $reefless->loadClass('Mail');
                        $mail_tpl = $rlMail->getEmailTemplate('quick_account_created');
                        $find = array('{login}', '{password}', '{name}');
                        $replace = array($login, $password, $name);
                        $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
                        $rlMail->send($mail_tpl, $email);

                        $this->resultHandlerAfterLogin($result, $user_info);
                    }
                } else {
                    $config['security_img_registration'] = false;

                    // replace fake password to generated
                    if (!empty($_POST['profile']) && !array_key_exists('xjxfun', $_POST)) {
                        $password = $reefless->generateHash(10, 'password', true);
                        $_POST['profile']['password'] = $_POST['profile']['password_repeat'] = $password;
                    }
                }
            }
        }
    }

    /**
     * Helper function after login process
     *
     * @since 2.3.0
     *
     * @param  bool|array $result
     * @param  array      $user_info
     * @return void
     */
    public function resultHandlerAfterLogin($result, $user_info = null)
    {
        global $reefless;

        if (true === $result) {
            $reefless->loadClass('Notice');
            $GLOBALS['rlNotice']->saveNotice($GLOBALS['lang']['notice_logged_in']);

            $GLOBALS['rlHook']->load('loginSuccess', $user_info);

            $facebook_referer = $_SESSION['facebook_referer'];
            $this->cleanSessionData();

            $reefless->redirect(false, $facebook_referer);
        } else {
            $this->cleanSessionData();
            $GLOBALS['rlSmarty']->assign('errors', $result);
        }
    }

    /**
     * Clean Facebook details stored in session
     * 
     * @since  2.3.1
     * @return void
     */
    private function cleanSessionData()
    {
        unset(
            $_SESSION['facebook_referer'],
            $_SESSION['facebook_process'],
            $_SESSION['facebook_info']
        );
    }

    /**
     * Turn off login attempts control
     * 
     * @since  2.3.2
     * @return void
     */
    private function turnOffLoginAttemptsControl()
    {
        $GLOBALS['config']['security_login_attempt_user_module'] = false;
    }

    /**
     * @deprecated since 2.0.0
     */
    public function createFBConnectButton()
    {}
}
