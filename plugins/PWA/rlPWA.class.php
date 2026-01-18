<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLPWA.CLASS.PHP
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
 *  Flynax Classifieds Software 2024 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

use Flynax\Component\Filesystem;
use Flynax\Plugins\PWA\Config;
use Flynax\Plugins\PWA\Files\Icon;
use Flynax\Plugins\PWA\Files\Manifest;
use Flynax\Plugins\PWA\Push;
use Flynax\Plugins\PWA\PWA;
use Flynax\Plugins\PWA\Statistics;
use Flynax\Plugins\PWA\Subscription;
use Flynax\Plugins\PWA\Options\Account;
use Jenssegers\Agent\Agent;
use Flynax\Utils\Valid;
use Flynax\Abstracts\AbstractPlugin;
use Flynax\Interfaces\PluginInterface;

require_once RL_PLUGINS . 'PWA/bootstrap.php';

class rlPWA extends AbstractPlugin implements PluginInterface
{
    /**
     * @var PWA
     */
    public $plugin;

    /**
     * @var
     */
    public $isPluginConfigured = true;

    /**
     * @var
     */
    private $files = [];

    /**
     * @var
     */
    private $icons = [];

    /**
     * @var
     */
    private $options = [];

    /**
     * rlPWA constructor.
     */
    public function __construct()
    {
        $this->plugin = new PWA();

        global $reefless, $plugins;

        if (!$plugins['PWA']) {
            return;
        }

        $names = Config::i()->getConfigs()['multiple']['name'];

        if (count($names) > 1) {
            $this->files['manifest'] = PWA_FILES_URL . $names[RL_LANG_CODE]['Code'] . '-' . Manifest::MANIFEST_NAME;
        } else {
            $this->files['manifest'] = PWA_FILES_URL . Manifest::MANIFEST_NAME;
        }

        $this->icons = Icon::getImages();
        foreach ($this->icons as $key => $value) {
            $newKey = str_replace('x', '_', $key);
            $this->files['icons'][$newKey] = PWA_FILES_URL . $value;
        }

        $this->options = [
            'bgColor'          => Config::i()->getConfig('pwa_color'),
            'pwa_vapid_public' => Config::i()->getConfig('pwa_vapid_public'),
        ];

        if (!$reefless->isHttps()
            || !$names
            || !$this->icons
            || !$this->options['bgColor']
            || !$this->options['pwa_vapid_public']
            || !is_file(str_replace(RL_URL_HOME, RL_ROOT, $this->files['manifest']))
        ) {
            $this->isPluginConfigured = false;
        }
    }

    /**
     * @hook tplHeader
     */
    public function hookTplHeader()
    {
        global $rlSmarty;

        if (!$this->isPluginConfigured) {
            return;
        }

        $rlSmarty->assign('files', $this->files);
        $rlSmarty->assign('options', $this->options);
        $rlSmarty->display(PWA_ROOT . 'views/meta.tpl');
    }

    /**
     * @param \rlStatic $rlStatic
     */
    public function hookStaticDataRegister($rlStatic)
    {
        $rlStatic = $rlStatic ?: $GLOBALS['rlStatic'];

        if (!$this->isPluginConfigured) {
            return;
        }

        $rlStatic->addJS(PWA_ROOT_URL . 'static/core/utils.js');
        $rlStatic->addJS(PWA_ROOT_URL . 'static/lib.js');
        $rlStatic->addJS(RL_URL_HOME . 'upup.min.js');
        $rlStatic->addFooterCSS(PWA_ROOT_URL . 'static/style.css');
    }

    /**
     * @hook seoBase
     */
    public function hookSeoBase()
    {
        if (isset($_GET['utm_source']) && $_GET['utm_source'] === 'web_app_manifest') {
            define('FROM_PWA', true);
        }
    }

    /**
     * @hook specialBlock
     * @todo - Remove it when all necessary browsers will support "appinstalled" listener
     */
    public function hookSpecialBlock()
    {
        if (!$this->isPluginConfigured) {
            return;
        }

        if (PWA::isFromPWA() && !$_COOKIE['pwa_installed']) {
            $agent = new Agent();

            if ($agent->isMobile()) {
                Statistics::collectAndSave();
                $GLOBALS['reefless']->createCookie('pwa_installed', true, strtotime('+365 days'));
            }
        }
    }

    /**
     * @hook cronSavedSearchNotify
     */
    public function hookCronSavedSearchNotify($search, $listingIDs, $accountInfo)
    {
        $listingIDs = $listingIDs ? explode(',', $listingIDs) : [];

        if (!$this->isPluginConfigured || !$listingIDs || !$accountInfo) {
            return;
        }

        global $lang;

        $countListings = count($listingIDs);

        if ($countListings === 1) {
            $listingID = reset($listingIDs);
            Push::i()->toAccount($accountInfo['ID'])->sendListing($listingID);
        } else {
            $sendingData = [
                'tag'     => 'alert',
                'title'   => str_replace('{count}', $countListings, $lang['pwa_new_listings_added']),
                'message' => $lang['email_site_name'] ?: $lang['pages+title+home'],
                'link'    => $GLOBALS['reefless']->getPageUrl('saved_search', null, $accountInfo['Lang']),
                'icon'    => Icon::getAppUrlIcon(),
            ];

            Push::i()->toAccount($accountInfo['ID'])->alertsOnly()->send($sendingData);
        }
    }

    /**
     * @hook rlMessagesAjaxAfterMessageSent
     */
    public function hookRlMessagesAjaxAfterMessageSent($res_id, $message)
    {
        if (!$this->isPluginConfigured || !$GLOBALS['account_info']['ID'] || !$res_id) {
            return;
        }

        Push::i()->sendMessageToAccount((int) $GLOBALS['account_info']['ID'], $res_id, $message);
    }

    /**
     * @hook rlMessagesAjaxContactOwnerAfterSend
     */
    public function hookRlMessagesAjaxContactOwnerAfterSend(
        $name      = null,
        $email     = null,
        $phone     = null,
        $message   = null,
        $listingID = null
    ) {
        if ($listingID) {
            $toID = (int) $GLOBALS['rlDb']->getOne('Account_ID', "`ID` = {$listingID}", 'listings');
        } else {
            $toID = (int) $_REQUEST['account_id'];
        }

        if (!$this->isPluginConfigured || !$toID) {
            return;
        }

        global $account_info;

        $from = $account_info ? (int) $account_info['ID'] : ['name' => $name, 'email' => $email];

        Push::i()->sendMessageToAccount($from, $toID, $message);
    }

    /**
     * @hook tplFooter
     */
    public function hookTplFooter()
    {
        if (!$this->isPluginConfigured) {
            return;
        }

        $GLOBALS['rlSmarty']->display(PWA_ROOT . 'views/tplFooter.tpl');
    }

    /**
     * @hook profileEditProfileDone
     */
    public function hookProfileEditProfileDone()
    {
        global $account_info;

        if (!$this->isPluginConfigured || !$account_info) {
            return;
        }

        $account          = new Account($account_info['ID']);
        $pushSubscription = $_POST['push'];

        $pushSubscription['alerts'] ? $account->subscribeToNewListings() : $account->unsubscribeFromNewListings();
        $pushSubscription['messages'] ?  $account->subscribeToNewMessages() : $account->unsubscribeFromNewMessages();
    }

    /**
     * @hook   ajaxRequest
     * @param  $out
     * @param  $request_mode
     */
    public function hookAjaxRequest(&$out, $request_mode)
    {
        if (!$this->isPluginConfigured || !$this->plugin->isValidAjax($request_mode)) {
            return;
        }

        global $account_info;

        $account_info  = $account_info ?: $_SESSION['account'];

        if (!$account_info['ID'] && $request_mode !== 'pwa_installed') {
            $out['status'] = 'ERROR';
            return;
        }

        if ($data = $_REQUEST['subscription']) {
            $subscriptionData = json_decode($data);

            $subscription = [
                'Endpoint'   => $subscriptionData->endpoint,
                'P256dh'     => $subscriptionData->keys->p256dh,
                'Auth'       => $subscriptionData->keys->auth,
            ];
        }

        $subscription['Account_ID'] = $account_info['ID'];

        switch ($request_mode) {
            case 'pwa_installed':
                $out['status'] = Statistics::collectAndSave() ? 'OK' : 'ERROR';
                break;
            case 'pwa_subscribe':
                $out['status'] = Subscription::subscribe($subscription) ? 'OK' : 'ERROR';
                break;
            case 'pwa_unsubscribe':
                $out['status'] = Subscription::unsubscribe($subscription) ? 'OK' : 'ERROR';
                break;
            case 'pwa_push_blocked':
                $out['status'] = Subscription::blocked($subscription) ? 'OK' : 'ERROR';
                break;
            case 'pwa_get_subscriptions':
                $out = [
                    'status'        => 'OK',
                    'subscriptions' => (new Account($subscription['Account_ID']))->getAllSavedSubscriptions()
                ];
                break;
        }
    }

    /**
     * @hook apExtPhrasesUpdate
     */
    public function hookApExtPhrasesUpdate()
    {
        global $updateData, $rlDb, $config;

        $id    = (int) $updateData['where']['ID'];
        $value = (string) $updateData['fields']['Value'];

        if (!$id || !$value) {
            return;
        }

        $key = $rlDb->getOne('Key', "`ID` = {$id}", 'lang_keys');

        if (!in_array($key, ['pwa_offline', 'pwa_reload'])) {
            return;
        }

        $sql = "SELECT * FROM `{db_prefix}lang_keys` ";
        $sql .= "WHERE `Key` IN('pwa_offline','pwa_reload') ";
        $phrases = $rlDb->getAll($sql);

        $pwaPhrases = [];
        foreach ($phrases as &$phrase) {
            $pwaPhrases[$phrase['Code']][$phrase['Key']] = $phrase['ID'] == $id ? $value : $phrase['Value'];
        }
        unset($phrases);

        $jsContent = '<script class="lang">' . PHP_EOL;
        $jsContent .= 'var phrases = [];';

        foreach ($pwaPhrases as $langCode => $phrases) {
$jsContent .= "
phrases.{$langCode} = [];
phrases.{$langCode}.offline = '" . Valid::escape($phrases['pwa_offline']) . "';
phrases.{$langCode}.reload  = '" . Valid::escape($phrases['pwa_reload']) . "';";
        }

        $jsContent .= "
var code = '', defaultCode = '{$config['lang']}';

window.location.pathname.split('/').forEach(function (item) {
    if (item.length === 2) {
        code = item;
    }
});

var lang = code && phrases[code] ? phrases[code] : phrases[defaultCode];

document.getElementById('logo').alt                  = lang.offline;
document.getElementById('message').textContent       = lang.offline;
document.getElementById('reload_button').textContent = lang.reload;
</script>";

        $offlineFile = RL_ROOT . 'offline/index.html';
        $newContent  = preg_replace(
            "/\<script class=\"lang\"\>(.*)\<\/script\>/smi",
            $jsContent,
            file_get_contents($offlineFile)
        );

        file_put_contents($offlineFile, $newContent);
    }

    /**
     * Plugin installation process
     */
    public function install()
    {
        $this->plugin->copyOfflineFolder();
        $this->plugin->copyServiceWorkers();
        $this->plugin->addSystemTables();
        $this->plugin->createHiddenConfigs();
        $this->plugin->generateVAPIDKeys();
    }

    /**
     * Plugin uninstalling process
     */
    public function uninstall()
    {
        $fileSystem    = new Filesystem();
        $removingFiles = [
            RL_ROOT . 'offline',
            RL_ROOT . 'upup.min.js',
            RL_ROOT . 'upup.sw.min.js',
        ];

        foreach ($removingFiles as $file) {
            if ($file !== RL_ROOT) {
                $fileSystem->remove($file);
            }
        }

        $this->plugin->clearAllGeneratedFiles();
        $this->plugin->dropSystemTables();
    }

    /**
     * Update process of the plugin (copy from core)
     * @param string $version
     */
    public function update($version)
    {
        $version_method = 'update' . (int) str_replace('.', '', $version);
        if (method_exists($this, $version_method)) {
            $this->$version_method();
        }
    }

    /**
     * Update to 1.1.0 version
     */
    public function update110()
    {
        $GLOBALS['rlDb']->insertOne([
            'Key'      => 'pwa_maskable_icons',
            'Group_ID' => 0,
            'Plugin'   => 'PWA',
            'Type'     => 'text'
        ], 'config');

        @copy(RL_UPLOAD . 'PWA/static/offline/.htaccess', RL_ROOT . 'offline/.htaccess');
    }

    /**
     * Update to 1.1.1 version
     * @return void
     */
    public function update111(): void
    {
        global $rlDb;

        $rlDb->query("ALTER TABLE `{db_prefix}pwa_subscriptions` CHANGE COLUMN `Endpoint` `Endpoint` TEXT DEFAULT NULL");
        $rlDb->query("ALTER TABLE `{db_prefix}pwa_subscriptions` CHANGE COLUMN `P256dh` `P256dh` TEXT DEFAULT NULL");
        $rlDb->query("ALTER TABLE `{db_prefix}pwa_subscriptions` CHANGE COLUMN `Auth` `Auth` TEXT DEFAULT NULL");

        $content = <<<HTML
</style>

    <script>
        /**
         * Fix problem with wrong showing "Offline content" by browser on the search-results page
         * when internet is active
         */
        if (navigator && navigator.onLine && navigator.onLine === true) {
            window.location.reload();
        }
    </script>
HTML;
        $offlineFile = RL_ROOT . 'offline/index.html';
        file_put_contents($offlineFile, str_replace('</style>', $content, file_get_contents($offlineFile)));

        require RL_UPLOAD . 'PWA/vendor/autoload.php';
        $filesystem = new Filesystem();
        $oldVendor = RL_PLUGINS . 'PWA/vendor/';
        $filesystem->remove($oldVendor);
        $copyFunction = method_exists($filesystem, 'copyTo') ? 'copyTo' : 'copy';
        $filesystem->$copyFunction(RL_UPLOAD . 'PWA/vendor/', $oldVendor);
    }

    /**
     * Update to 1.1.2 version
     */
    public function update112(): void
    {
        global $rlDb, $languages, $config;

        $enPhrases = [
            'title_PWA'       => 'Progressive Web App',
            'description_PWA' => 'Mobile web-based App that allows users to enjoy all the features of the script and get push notifications',
        ];
        foreach ($enPhrases as $enPhraseKey => $enPhraseValue) {
            if (!$rlDb->getOne('ID', "`Key` = '{$enPhraseKey}' AND `Code` = '{$config['lang']}'", 'lang_keys')) {
                $rlDb->insertOne([
                    'Code'   => $config['lang'],
                    'Module' => 'common',
                    'Key'    => $enPhraseKey,
                    'Value'  => $enPhraseValue,
                    'Plugin' => 'PWA',
                ], 'lang_keys');
            }
        }

        if (array_key_exists('ru', $languages)) {
            $russianTranslation = json_decode(file_get_contents(RL_UPLOAD . 'PWA/i18n/ru.json'), true);

            foreach ($russianTranslation as $phraseKey => $phraseValue) {
                if (!in_array($phraseKey, ['title_PWA', 'description_PWA', 'notice_PWA_1'])) {
                    continue;
                }

                if (!$rlDb->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $insertPhrase = $rlDb->fetch(
                        ['Module', 'Key', 'Plugin'],
                        ['Code' => $GLOBALS['config']['lang'], 'Key' => $phraseKey],
                        null, 1, 'lang_keys', 'row'
                    );

                    $insertPhrase['Code']  = 'ru';
                    $insertPhrase['Value'] = $phraseValue;

                    $rlDb->insertOne($insertPhrase, 'lang_keys');
                } else {
                    $rlDb->updateOne([
                        'fields' => ['Value' => $phraseValue],
                        'where' => ['Key'   => $phraseKey, 'Code' => 'ru'],
                    ], 'lang_keys');
                }
            }
        }

        require RL_UPLOAD . 'PWA/vendor/autoload.php';
        $filesystem = new Filesystem();
        $oldVendor = RL_PLUGINS . 'PWA/vendor/';
        $filesystem->remove($oldVendor);
        $copyFunction = method_exists($filesystem, 'copyTo') ? 'copyTo' : 'copy';
        $filesystem->$copyFunction(RL_UPLOAD . 'PWA/vendor/', $oldVendor);
    }

    /**
     * Update to 1.2.0 version
     */
    public function update120()
    {
        global $config, $rlDb;

        $pngLogo = RL_ROOT . "templates/{$config['template']}/img/logo.png";
        $svgLogo = RL_ROOT . "templates/{$config['template']}/img/logo.svg";

        if (is_file($svgLogo) && !is_file($pngLogo)) {
            file_put_contents(
                RL_ROOT . 'offline/index.html',
                str_replace(
                    [RL_URL_HOME . "templates/{$config['template']}/img/logo.png", RL_URL_HOME . "templates/{$config['template']}/img/@2x/logo.png"],
                    [RL_URL_HOME . "templates/{$config['template']}/img/logo.svg", RL_URL_HOME . "templates/{$config['template']}/img/logo.svg"],
                    file_get_contents(RL_ROOT . 'offline/index.html')
                )
            );
        }

        unlink(PWA_ROOT . 'views/pushSubscription.tpl');
        $rlDb->delete(['Name' => 'profileBlock', 'Plugin' => 'PWA'], 'hooks');

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
             WHERE `Plugin` = 'PWA' AND `Key` IN (
                 'pwa_user_subscribed',
                 'pwa_user_unsubscribed',
                 'pwa_enable_push',
                 'pwa_disable_push',
                 'pwa_push_blocked',
                 'pwa_push_not_supported',
                 'pwa_push_disabled_notice'
            )"
        );
    }

    /*** DEPRECATED ***/

    /**
     * @deprecated 1.2.0
     * @hook profileBlock
     */
    public function hookProfileBlock()
    {}
}
