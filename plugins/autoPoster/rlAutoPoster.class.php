<?php


/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLAUTOPOSTER.CLASS.PHP
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

use Autoposter\Admin\rlAutoPosterAdmin;
use Autoposter\AjaxWrapper;
use Autoposter\AutoPosterContainer;
use Autoposter\MessageBuilder;
use Autoposter\Notifier;
use Flynax\Component\Filesystem;

require_once RL_PLUGINS . 'autoPoster/bootstrap.php';

class rlAutoPoster extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * @var rlDb
     */
    protected $rlDb;

    /**
     * @var rlActions
     */
    protected $rlActions;

    /**
     * @var rlAutoPosterAdmin
     */
    protected $rlAutoPosterAdmin;

    /**
     * @var rlValid
     */
    protected $rlValid;
    /**
     * @var reefless - reefless class instance
     */
    protected $reefless;

    /**
     * @var array - plugin configuration
     */
    protected $configs;
    /**
     * @vars array - flynax languages
     */
    protected $lang;

    /**
     * @var MessageBuilder
     */
    protected $messageBuilder;

    /**
     * @var rlSmarty
     */
    protected $rlSmarty;

    /**
     * @var rlCommon
     */
    protected $rlCommon;

    /**
     * @var notifier
     */
    protected $notifier;

    /**
     * rlAutoPoster constructor
     */
    public function __construct()
    {
        $this->configs = [
            'path' => [
                'view' => RL_PLUGINS . 'autoPoster/view/',
                'static' => RL_PLUGINS . 'autoPoster/static/',
            ],
            'url' => [
                'view' => RL_PLUGINS_URL . 'autoPoster/view/',
                'static' => RL_PLUGINS_URL . 'autoPoster/static/',
            ],
        ];

        $this->init();
    }

    /**
     *  Plugin install
     */
    public function install(): void
    {
        $this->rlDb->createTable(
            'autoposting_modules',
            "`ID` INT(11) NOT NULL AUTO_INCREMENT,
             `Key` VARCHAR(50) NOT NULL,
             `Message_pattern` VARCHAR(150) DEFAULT '',
             `Message_fields` VARCHAR(150) DEFAULT NULL,
             `Message_body` TEXT,
             `Status` ENUM('active', 'approval') DEFAULT 'approval',
             PRIMARY KEY (`ID`)"
        );

        $this->rlDb->createTable(
            'autoposter_tokens',
            "`id` int(11) NOT NULL AUTO_INCREMENT,
             `User_ID` INT(11) DEFAULT NULL,
             `Token` MEDIUMTEXT,
             `Refresh_token` MEDIUMTEXT,
             `Device_id` MEDIUMTEXT,
             `Token_date` DATETIME DEFAULT NULL,
             `User_type` INT(11) DEFAULT NULL,
             `Page_token` MEDIUMTEXT,
             `Page_token_date` DATETIME DEFAULT NULL,
             `Module` VARCHAR(50) DEFAULT NULL,
             PRIMARY KEY (`id`),
             KEY `User_ID` (`User_ID`)"
        );

        $this->rlDb->createTable(
            'autoposter_listings',
            "`ID` INT(11) NOT NULL AUTO_INCREMENT,
             `Listing_ID` INT(11) DEFAULT NULL,
             `Facebook_message_id` VARCHAR(150) NOT NULL,
             `Twitter_message_id` VARCHAR(150) NOT NULL,
             `Vk_message_id` VARCHAR(150) NOT NULL,
             `Telegram_message_id` INT(11) NOT NULL,
             `Try` INT(1) DEFAULT 0,
             PRIMARY KEY (`ID`),
             KEY `Listing_ID` (`Listing_ID`)"
        );

        $modules = array_keys($this->getConfig('modules'));
        foreach ($modules as $module) {
            $data['Key'] = $module;
            $this->addModule($data);
        }

        $this->rlDb->insertOne([
            'Key'      => 'ap_log_reset_date',
            'Group_ID' => 0,
            'Plugin'   => 'autoPoster',
            'Type'     => 'text',
        ], 'config');

        $this->updateGroupID();
    }

    /**
     * Plugin uninstall
     */
    public function uninstall(): void
    {
        $this->rlDb->dropTables(['autoposting_modules', 'autoposter_tokens', 'autoposter_listings']);
    }

    /**
     *  Initial method of the plugin
     */
    public function init()
    {
        // add Flynax classes
        $this->reefless = $GLOBALS['reefless'];
        $this->reefless->loadClass('Actions');

        AutoPosterContainer::addObject('rlDb', $GLOBALS['rlDb']);
        AutoPosterContainer::addObject('reefless', $GLOBALS['reefless']);
        AutoPosterContainer::addObject('rlNotice', $GLOBALS['rlNotice']);
        AutoPosterContainer::addObject('rlAccount', $GLOBALS['rlAccount']);

        if (!$GLOBALS['rlListings']) {
            $this->reefless->loadClass('Listings');
        }
        AutoPosterContainer::addObject('rlListings', $GLOBALS['rlListings']);
        AutoPosterContainer::addObject('rlDebug', $GLOBALS['rlDebug']);
        AutoPosterContainer::addObject('rlActions', $GLOBALS['rlActions']);
        AutoPosterContainer::addObject('rlAutoPoster', $this);
        AutoPosterContainer::addObject('rlValid', $GLOBALS['rlValid']);
        AutoPosterContainer::addObject('rlCategories', $GLOBALS['rlCategories']);
        AutoPosterContainer::addObject('rlCommon', $GLOBALS['rlCommon']);
        AutoPosterContainer::addObject('rlStatic', $GLOBALS['rlStatic']);
        AutoPosterContainer::addObject('rlLang', $GLOBALS['rlLang']);
        AutoPosterContainer::addObject('rlConfig', $GLOBALS['rlConfig']);

        if ($GLOBALS['rlSmarty']) {
            AutoPosterContainer::addObject('rlSmarty', $GLOBALS['rlSmarty']);
            $this->rlSmarty = $GLOBALS['rlSmarty'];
        }

        $this->rlDb = AutoPosterContainer::getObject('rlDb');
        $this->rlValid = $GLOBALS['rlValid'];
        $this->rlCommon = $GLOBALS['rlCommon'];
        $this->notifier = new Notifier('debug');

        AutoPosterContainer::setConfig('flConfigs', $GLOBALS['config']);
        AutoPosterContainer::setConfig('lang', $GLOBALS['lang']);

        // add Flynax languages
        AutoPosterContainer::setConfig('languages', $GLOBALS['languages']);

        if (defined('REALM') && REALM == 'admin') {
            $this->lang = $GLOBALS['lang'];
            $this->reefless->loadClass('Builder', 'admin');
            AutoPosterContainer::addObject('rlBuilder', $GLOBALS['rlBuilder']);
            $this->rlAutoPosterAdmin = new rlAutoPosterAdmin();
            $this->messageBuilder = new MessageBuilder();
        }
    }

    /**
     * Getting all modules
     *
     * @return array  - Array of the modules
     */
    public function getModules()
    {
        return $this->rlDb->getAll("SELECT * FROM `" . RL_DBPREFIX . "autoposting_modules`");
    }

    /**
     * Add module to the database
     *
     * @param array $data - Array of the Module
     */
    public function addModule($data)
    {
        $this->rlDb->insertOne($data, 'autoposting_modules');
    }

    /**
     * Getting plugin config from AutoPosterContainer
     *
     * @param  string $name - Configuration name
     * @return mixed        - Configuration value
     */
    public function getConfig($name)
    {
        $all_plugin_configs = AutoPosterContainer::getConfig('configs');
        return $all_plugin_configs[$name];
    }

    /**
     * @hook apAjaxRequest
     */
    public function hookApAjaxRequest(&$out, $item)
    {
        switch ($item) {
            case 'apSaveMessageBuildForm':
                $this->reefless->loadClass('Actions');
                $ids = $GLOBALS['rlValid']->xSql($_POST['ids']);
                $module = $_POST['module'];
                $out = $this->rlAutoPosterAdmin->ajaxSaveForm($ids, $module);
                break;

            case 'ap_clear_log':
                $update = [
                    'fields' => ['Default' => time()],
                    'where' => ['Key' => 'ap_log_reset_date'],
                ];
                $this->rlDb->updateOne($update, 'config');

                $out = array(
                    'status' => 'OK',
                );
                break;

            case 'ap_get_telegram_chat_id':
                $bot_token = $GLOBALS['rlValid']->xSql($_REQUEST['token']);

                if ($bot_token) {
                    $url = sprintf('https://api.telegram.org/bot%s/getUpdates', $bot_token);
                    $response = $this->reefless->getPageContent($url);
                    $response = json_decode($response, true);

                    if ($response['ok'] && !$response['result']) {
                        $out = array(
                            'status' => 'ERROR',
                            'message' => $GLOBALS['rlLang']->getPhrase('telegram_get_chat_id_no_data_error', null, null, true)
                        );
                    } elseif (!$response['ok']) {
                        $error_message = $GLOBALS['rlLang']->getPhrase('telegram_get_chat_id_bot_token_error', null, null, true);

                        if ($response['error_code']) {
                            $this->notifier->logMessage("Telegram API Exception: " . $response['description']);
                            $error_message = $response['description'];
                        }

                        $out = array(
                            'status' => 'ERROR',
                            'message' => $error_message
                        );
                    } elseif ($response['ok'] && $response['result']) {
                        if (is_array($response['result'])) {
                            $chat_id = false;

                            foreach ($response['result'] as $stack) {
                                if (isset($stack['channel_post']) && $stack['channel_post']['chat']['id']) {
                                    $chat_id = abs($stack['channel_post']['chat']['id']);
                                    break;
                                }
                                if (isset($stack['my_chat_member']) && $stack['my_chat_member']['chat']['id']) {
                                    $chat_id = abs($stack['my_chat_member']['chat']['id']);
                                    break;
                                }
                                if (isset($stack['message']) && $stack['message']['chat']['id']) {
                                    $chat_id = abs($stack['message']['chat']['id']);
                                    break;
                                }
                            }

                            if ($chat_id) {
                                $out = array(
                                    'status' => 'OK',
                                    'results' => $chat_id
                                );
                            } else {
                                $out = array(
                                    'status' => 'ERROR',
                                    'message' => $GLOBALS['rlLang']->getPhrase('system_error')
                                );
                                $this->notifier->logMessage("Telegram API Exception: no chat id found in getUpdates response");
                            }
                        } else {
                            $this->notifier->logMessage("Telegram API Exception: no chat id found in getUpdates response");
                        }
                    }
                } else {
                    $out = array(
                        'status' => 'ERROR',
                        'message' => str_replace(
                            '{field}',
                            sprintf('"%s"', $GLOBALS['rlLang']->getPhrase('config+name+ap_telegram_bot_token', null, null, true)),
                            $this->lang['notice_field_empty']
                        )
                    );
                }
                break;
        }
    }

    /**
     * @hook apTplCategoriesForm
     */
    public function hookApTplCategoriesForm()
    {
        $tpl = $this->getConfig('admin')['view'] . 'message_in_category_builder.tpl';
        AutoPosterContainer::getObject('rlSmarty')->display($tpl);
    }

    /**
     * @hook apPhpCategoriesAfterAdd
     */
    public function hookApPhpCategoriesAfterAdd()
    {
        $this->onCategoryPageSubmit();
    }

    /**
     * @hook apPhpCategoriesPost
     */
    public function hookApPhpCategoriesPost()
    {

        $allLangs = AutoPosterContainer::getObject('rlLang')->getLanguagesList('all');

        $this->messageBuilder->setCategoryKey($_POST['key']);
        $messages = $this->messageBuilder->getAllMessages();
        $footers = $this->messageBuilder->getAllFotterMessage();
        $description = $this->messageBuilder->getDescription();

        // simulate post
        foreach ($allLangs as $key => $lang_info) {
            $_POST['facebook_message'][$messages[$key]['Code']] = $messages[$key]['Value'];
            $_POST['facebook_post_footer'][$footers[$key]['Code']] = $footers[$key]['Value'];
            $_POST['post_description'] = $description;
        }
    }

    /**
     * @hook apPhpCategoriesBeforeEdit
     */
    public function hookApPhpCategoriesBeforeEdit()
    {
        $this->onCategoryPageSubmit();
    }

    /**
     * @hook postPaymentComplete
     * @param array $data - Payment information
     */
    public function hookPostPaymentComplete($data)
    {
        $valid_services = ['listing', 'package', 'featured'];

        $plugins = $GLOBALS['plugins'] ?: $this->rlCommon->getInstalledPluginsList();

        if (!$data['service'] && $plugins['bankWireTransfer']) {
            $txn_id = $_SESSION['Txn_ID'] ?: $data['txn_id'];

            if ($txn_id) {
                $data['service'] = $this->rlDb->getOne('Service', "`ID` = {$txn_id}", 'transactions');
            }
        }

        if (in_array($data['service'], $valid_services)) {
            $this->providersPoster($data['item_id']);
        }
    }

    /**
     * @hook apPhpListingsAfterAdd
     */
    public function hookApPhpListingsAfterAdd()
    {
        if ($GLOBALS['listing_id']) {
            $this->providersPoster($GLOBALS['listing_id']);
        }
    }

    /**
     * @hook apExtListingsUpdate
     */
    public function hookApExtListingsAfterUpdate()
    {
        if ($_REQUEST['field'] != 'Status' && $_REQUEST['value'] != 'active') {
            return false;
        }

        $this->providersPoster($GLOBALS['listing_info']['ID']);
    }

    /**
     * Post listing on remote admin activation
     *
     * @since 1.3.1
     * @hook apPhpListingsAfterActivate
     */
    public function hookApPhpListingsAfterActivate()
    {
        global $remote_listing_id;

        $this->providersPoster($remote_listing_id);
    }

    /**
     * Handle posting via active providers
     * @param int $listing_id - Posting Listing ID
     */
    public function providersPoster($listing_id)
    {
        $modules = $this->getModules();
        foreach ($modules as $module) {
            if ($module['Status'] != 'active') {
                continue;
            }
            $provider = (new \Autoposter\ProviderController($module['Key']))->getProvider();
            $provider->post($listing_id);
        }
    }

    /**
     * Remove listing from the all providers timeline
     *
     * @param int $listingID
     * @return bool
     */
    public function removeListingFromTimeline($listingID)
    {
        $listingID = (int) $listingID;
        if (!$listingID) {
            return false;
        }

        $modules = $this->getModules();
        foreach ($modules as $module) {
            if ($module['Status'] != 'active') {
                continue;
            }

            $provider = (new \Autoposter\ProviderController($module['Key']))->getProvider();
            if (method_exists($provider, 'deletePost')) {
                $provider->deletePost($listingID);
            }
        }

        $this->rlDb->delete(['Listing_ID' => $listingID], 'autoposter_listings', null, null);
    }

    /**
     * Handler for category add/edit actions
     */
    public function onCategoryPageSubmit()
    {
        $this->messageBuilder->setCategoryKey($_POST['key']);
        $this->messageBuilder->handleMessages($_POST['facebook_message']);
        $this->messageBuilder->handleDescription($_POST['post_description']);
        $this->messageBuilder->handleFooterMessages($_POST['facebook_post_footer']);
    }

    /**
     * Set Group ID of all provider configurations to 0
     * @since 1.8.0
     * @return void
     */
    public function updateGroupID()
    {
        $this->rlDb->query(
            "UPDATE `{db_prefix}config` SET `Group_ID` = 0
             WHERE `Key` NOT IN (
                'ap_own_cron',
                'ap_cron_ads_limit',
                'ap_imported_ads',
                'ap_xls_frontend',
                'ap_xls_backend',
                'ap_xml_backend'
             ) AND `Plugin` = 'autoPoster'"
        );
    }

    /**
     * Post listings from queue
     *
     * @since 1.8.0
     *
     * @param  int  $limit
     * @return void
     */
    public function postListings(int $limit = 1): void
    {
        $listings = $this->rlDb->getAll(
            "SELECT `T1`.`Listing_ID` AS `ID` FROM `{db_prefix}autoposter_listings` AS `T1`
             LEFT JOIN `{db_prefix}listings` AS `T2` ON `T1`.`Listing_ID` = `T2`.`ID`
             WHERE `T1`.`Try` = 0 AND `T2`.`Status` = 'active'
             LIMIT {$limit}"
        );

        if ($listings) {
            foreach ($listings as $listing) {
                $this->providersPoster($listing['ID']);
                $this->rlDb->updateOne([
                    'fields' => ['Try' => 1],
                    'where'  => ['Listing_ID' => $listing['ID']],
                ], 'autoposter_listings');
            }
        }
    }

    /**
     * Queue a listing for later posting via cron
     *
     * @since 1.8.0
     *
     * @param  int  $id
     * @return void
     */
    public function addListingInQueue(int $id)
    {
        if (!$this->rlDb->getOne('ID', "`Listing_ID` = {$id}", 'autoposter_listings')) {
            $this->rlDb->insertOne([
                'Listing_ID' => $id,
                'Try'        => 0,
            ], 'autoposter_listings');
        }
    }

    /**
     * Set the ID of the social network post after posting of the listing
     *
     * @since 1.8.0
     *
     * @param  int|string $postID    - ID of the social network post
     * @param  int        $listingID - ID of the listing
     * @param  string     $column    - Name of the column of the related social network
     * @return void
     */
    public function setSocialNetworkID($postID, $listingID, string $column)
    {
        if ($this->rlDb->getOne('ID', "`Listing_ID` = {$listingID}", 'autoposter_listings')) {
            $this->rlDb->updateOne([
                'fields' => [
                    $column => $postID,
                    'Try'   => 1,
                ],
                'where' => [
                    'Listing_ID' => $listingID,
                ],
            ], 'autoposter_listings');
        } else {
            $this->rlDb->insertOne([
                $column      => $postID,
                'Listing_ID' => $listingID,
                'Try'        => 1,
            ], 'autoposter_listings');
        }
    }

    /**
     * @hook addListingStepActionsTpl
     */
    public function hookAddListingStepActionsTpl()
    {
        $this->rlSmarty->display($this->configs['path']['view'] . 'addListingStepActionsTpl.tpl');
    }

    /**
     * @hook staticDataRegister
     */
    public function hookStaticDataRegister()
    {
        $rlStatic = AutoPosterContainer::getObject('rlStatic');
        $js = $this->configs['url']['static'] . 'lib.js';
        $rlStatic->addJs($js);
    }

    /**
     * Ajax request handler of the current plugin
     *
     * @hook ajaxRequest
     * @param $out
     * @param $request_mode
     * @param $request_item
     * @param $request_lang
     */
    public function hookAjaxRequest(&$out, $request_mode, $request_item, $request_lang)
    {
        $ajaxWrapper = new AjaxWrapper();
        switch ($request_item) {
            case 'getProviders':
                $modules = $this->getModules();
                $modules = array_map(function ($module) {
                    return $module['Status'] == 'active' ? $module['Key'] : false;
                }, $modules);
                $out = array_filter($modules);
                break;
            case "sendListingToProvider":
                $provider = $this->rlValid->xSql($_REQUEST['provider']);
                $listing_id = (int) $_REQUEST['listing_id'];
                $out = $ajaxWrapper->sendPost2Wall($provider, $listing_id);
                break;
        }
    }

    /**
     * @since  1.3.0
     *
     * @param int $id - Removing listing ID
     */
    public function hookPhpDeleteListingData($id)
    {
        if (version_compare($GLOBALS['config']['rl_version'], '4.9.2', '>=')) {
            return;
        }

        $id = (int) $id;
        if (!$id) {
            return;
        }

        $this->removeListingFromTimeline($id);
    }

    /**
     * Delete listing from the wall
     *
     * @since 1.9.0
     * @hook phpDeleteListingDataBefore
     *
     * @param int $id - Removing listing ID
     */
    public function hookPhpDeleteListingDataBefore(&$id)
    {
        if (version_compare($GLOBALS['config']['rl_version'], '4.9.2', '<')) {
            return;
        }

        $id = (int) $id;
        if (!$id) {
            return;
        }

        $this->removeListingFromTimeline($id);
    }

    /**
     * @since 1.3.0
     *
     * @hook apPhpTrashBottom
     */
    public function hookApPhpTrashBottom()
    {
        $xAjaxFunction = $_REQUEST['xjxfun'];
        if ($xAjaxFunction == 'ajaxDeleteTrashItem' && $trashRowID = (int) reset($_REQUEST['xjxargs'])) {
            $sql = "SELECT * FROM `" . RL_DBPREFIX . "trash_box` WHERE `ID` = {$trashRowID} ";
            $row = $this->rlDb->getRow($sql);

            if (in_array('listings', explode(',', $row['Zones'])) && $listingID = (int) $row['Key']) {
                $this->removeListingFromTimeline($listingID);
            }
        }
    }

    /**
     * @since 1.4.0
     *
     * @hook apNotifications
     */
    public function hookApNotifications(&$notifications)
    {
        if (!$_COOKIE['ap_last_pattern_check']) {
            if (!$this->rlDb->getOne('ID', "`Key` LIKE 'categories\+auto\_posting\_message\+%'", 'lang_keys')) {
                $notifications[] = $this->lang['ap_no_category_pattern'];

                $expire = time() + 86400;
            } else {
                $expire = time() + (86400 * 31);
            }

            $GLOBALS['reefless']->createCookie('ap_last_pattern_check', 1, $expire);
        }
    }

    /**
     * @since 1.5.0
     *
     * @hook afterListingCreate
     *
     * @param $instance
     * @param $info
     * @param $data
     * @param $plan_info
     */
    public function hookAfterListingCreate($instance, $info, $data, $plan_info)
    {
        if ($instance->listingID) {
            $this->addListingInQueue($instance->listingID);
        }
    }

    /**
     * @since 1.5.0
     *
     * @hook cronAdditional
     */
    public function hookCronAdditional()
    {
        if ($GLOBALS['config']['ap_own_cron']) {
            return;
        }

        $this->postListings();
    }

    /**
     * @hook  apTplFooter
     * @since 1.8.0
     */
    public function hookApTplFooter(): void
    {
        if ($GLOBALS['controller'] === 'settings') {
            $this->rlAutoPosterAdmin->loadView('settings');
        }
    }

    /**
     * @hook apMixConfigItem
     *
     * @since      1.8.0 - Restore the usage of hook
     * @deprecated 1.3.1
     */
    public function hookApMixConfigItem(&$config)
    {
        if ($config['Key'] === 'ap_own_cron') {
            $phpExecutablePath = PHP_BINDIR . '/php';
            $cronScriptPath    = RL_PLUGINS . 'autoPoster/cron/index.php';
            $config['des']     = str_replace(
                ['{php_path}', '{cron_path}'],
                [$phpExecutablePath, $cronScriptPath],
                $config['des']
            );
        }
    }

    /**
     * @hook apPhpIndexBottom
     * @since 1.9.0
     */
    public function hookApPhpIndexBottom()
    {
        // It's a custom VK handler because they don't support queries in parameters.
        if ($_REQUEST['code']
            && $_REQUEST['state']
            && $_SESSION['ap_vk_code_verifier']
            && $_SESSION['ap_vk_state']
            && $_REQUEST['state'] == $_SESSION['ap_vk_state']
        ) {
            (new \Autoposter\ProviderController('vk'))->getProvider()->handleRedirect();
        }
    }

    /**
     * @since 1.1.0
     */
    public function update110()
    {
        $update = array(
            'fields' => array(
                'Value' => 'Page/Group ID',
            ),
            'where' => array(
                'Key' => 'config+name+ap_facebook_subject_id',
                'Plugin' => 'autoPoster',
            ),
        );
        $this->rlDb->updateOne($update, 'lang_keys');

        $update = array(
            'fields' => array(
                'Value' => "Please fill in the field if you chose to post to 'Personal page' or 'Facebook Group'",
            ),
            'where' => array(
                'Key' => 'config+des+ap_facebook_subject_id',
                'Plugin' => 'autoPoster',
            ),
        );
        $this->rlDb->updateOne($update, 'lang_keys');
    }

    /**
     * @version 1.4.0
     */
    public function update140()
    {
        $this->rlDb->query("
            DELETE FROM `{db_prefix}hooks`
            WHERE `Name` = 'apTplFooter' AND `Plugin` = 'autoPoster'
        ");

        $insert = [
            'Key' => 'ap_log_reset_date',
            'Group_ID' => 0,
            'Plugin' => 'autoPoster',
            'Type' => 'text',
        ];
        $this->rlDb->insertOne($insert, 'config');
    }

    /**
     * @version 1.5.0
     */
    public function update150()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . RL_DBPREFIX . "autoposter_listings` (
                `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `Listing_ID` int(11) DEFAULT NULL,
                `Facebook_message_id` varchar(150) NOT NULL,
                `Twitter_message_id` varchar(150) NOT NULL,
                `Try` int(1) DEFAULT '0',
            PRIMARY KEY (`ID`),
            KEY `Listing_ID` (`Listing_ID`)
         ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;";
        $this->rlDb->query($sql);

        // transfer data
        $sql = "INSERT INTO `" . RL_DBPREFIX . "autoposter_listings` (`Listing_ID`, `Facebook_message_id`, `Twitter_message_id`, `Try`)
            SELECT `ID`, `Facebook_message_id`, `Twitter_message_id`, '1' FROM `" . RL_DBPREFIX . "listings`

            WHERE `Facebook_message_id` != '' OR `Twitter_message_id` != ''
        ";
        $this->rlDb->query($sql);

        $this->rlDb->query("ALTER TABLE `" . RL_DBPREFIX . "listings` DROP `Facebook_message_id`");
        $this->rlDb->query("ALTER TABLE `" . RL_DBPREFIX . "listings` DROP `Twitter_message_id`");
    }

    /**
     * @version 1.6.0
     */
    public function update160()
    {
        global $rlDb;

        $modules = array_keys($this->getConfig('modules'));
        $curentModul = $rlDb->getAll("SELECT `Key` FROM  `{db_prefix}autoposting_modules`");

        $tempMod = [];
        foreach ($curentModul as  $cMod) {
            $tempMod[] = $cMod['Key'];
        }

        foreach ($modules as $module) {
            if ( !in_array($module, $tempMod)) {
                $data['Key'] = $module;
                $this->addModule($data);
            }

        }

        $this->rlDb->addColumnToTable(
            'Vk_message_id',
            "varchar(150) NOT NULL AFTER `Twitter_message_id`",
            'autoposter_listings'
        );

        if (in_array('ru', array_keys($GLOBALS['languages']))) {
            $russianTranslation = json_decode(file_get_contents(RL_PLUGINS . 'autoPoster/i18n/ru.json'), true);
            foreach ($russianTranslation as $phraseKey => $phrase) {
                if ($rlDb->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $rlDb->updateOne([
                        'fields' => ['Value' => $phrase],
                        'where'  => ['Key'   => $phraseKey, 'Code' => 'ru'],
                    ], 'lang_keys');
                } else {
                    $rlDb->insertOne([
                        'Code'   => 'ru',
                        'Module' => 'common',
                        'Key'    => $phraseKey,
                        'Value'  => $russianTranslation[$phraseKey],
                        'Plugin' => 'autoPoster',
                    ], 'lang_keys');
                }
            }
        }

        require RL_UPLOAD . 'autoPoster/vendor/autoload.php';
        $filesystem = new Filesystem();
        $oldVendor = RL_PLUGINS . 'autoPoster/vendor/';
        $filesystem->remove($oldVendor);
        $copyFunction = method_exists($filesystem, 'copyTo') ? 'copyTo' : 'copy';
        $filesystem->$copyFunction(RL_UPLOAD . 'autoPoster/vendor/', $oldVendor);
    }

    /**
     * @version 1.7.0
     */
    public function update170()
    {
        global $rlDb;

        $modules = array_keys($this->getConfig('modules'));
        $rlDb->setTable('autoposting_modules');
        $rlDb->outputRowsMap = [false, 'Key'];
        $currentModules = $rlDb->fetch(['Key']);

        foreach ($modules as $module) {
            if (!in_array($module, $currentModules)) {
                $data['Key'] = $module;
                $this->addModule($data);
            }
        }

        $this->rlDb->addColumnToTable(
            'Telegram_message_id',
            'INT(11) NOT NULL AFTER `Vk_message_id`',
            'autoposter_listings'
        );

        if (in_array('ru', array_keys($GLOBALS['languages']))) {
            $russianTranslation = json_decode(file_get_contents(RL_PLUGINS . 'autoPoster/i18n/ru.json'), true);

            foreach ($russianTranslation as $phraseKey => $phraseValue) {
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
                    $where = ['Key' => $phraseKey, 'Code' => 'ru'];
                    if (version_compare($GLOBALS['config']['rl_version'], '4.8.1', '>=')) {
                        $where['Modified'] = '0';
                    }
                    $rlDb->updateOne([
                        'fields' => ['Value' => $phraseValue],
                        'where' => $where,
                    ], 'lang_keys');
                }
            }
        }

        $filesystem = new Filesystem();
        $oldVendor = RL_PLUGINS . 'autoPoster/vendor/';
        $filesystem->remove($oldVendor);
        $copyFunction = method_exists($filesystem, 'copyTo') ? 'copyTo' : 'copy';
        $filesystem->$copyFunction(RL_UPLOAD . 'autoPoster/vendor/', $oldVendor);
    }

    /**
     * Update to 1.7.1 version
     */
    public function update171()
    {
        global $rlDb;

        $rlDb->query("ALTER TABLE `{db_prefix}autoposter_tokens` CHANGE COLUMN `Token` `Token` MEDIUMTEXT");
        $rlDb->query("ALTER TABLE `{db_prefix}autoposter_tokens` CHANGE COLUMN `Page_token` `Page_token` MEDIUMTEXT");
    }

    /**
     * Update to 1.8.0 version
     */
    public function update180()
    {
        $this->updateGroupID();
    }

    /**
     * Update to 1.9.0 version
     */
    public function update190()
    {
        $this->rlDb->addColumnsToTable([
                'Module'        => 'VARCHAR(50) DEFAULT NULL AFTER `Page_token_date`',
                'Refresh_token' => 'MEDIUMTEXT AFTER `Token`',
                'Device_id'     => 'MEDIUMTEXT AFTER `Refresh_token`',
        ], 'autoposter_tokens');

        // Set module=facebook for all existing tokens
        $this->rlDb->query("UPDATE `{db_prefix}autoposter_tokens` SET `Module` = 'facebook'");

        // Save exists token to new architecture
        if ($GLOBALS['config']['ap_vk_token']) {
            $this->rlDb->insertOne([
                'User_ID'  => 1,
                'Module'   => 'vk',
                'Token'    => $GLOBALS['config']['ap_vk_token'],
            ], 'autoposter_tokens');
        }

        $this->rlDb->query(
            "DELETE FROM `{db_prefix}config`
                WHERE `Key` = 'ap_vk_token' AND `Plugin` = 'autoPoster'"
        );

        $this->rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
                WHERE `Key` = 'config+name+ap_vk_token' AND `Plugin` = 'autoPoster'"
        );

        $this->updateGroupID();
    }

    /**
     * Get errors related to the plugin from the main errors log file
     *
     * @deprecated 1.6.0 - Moved to "rlAutoPosterAdmin"
     * @since 1.4.0
     *
     * @return array - Error log content
     */
    public function getErrorLog()
    {
    }
}
