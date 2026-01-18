<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLAPPSMANAGER.CLASS.PHP
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

use Flynax\Abstracts\AbstractPlugin;
use Flynax\Interfaces\PluginInterface;

/**
 * Flynax apps plugin class
 */
class rlAppsManager extends AbstractPlugin implements PluginInterface
{
    /**
     * @var String - Path to file
     */
    private $pathToData = RL_PLUGINS . 'appsManager' . RL_DS . 'static' . RL_DS . 'phrase_keys.php';

    /**
     * @var String - Path to file
     */
    private $pathToConfigKeys = RL_PLUGINS . 'appsManager' . RL_DS . 'static' . RL_DS . 'config_keys.php';

    /**
     * Execute arbitrary changes after installation
     *
     * @return void
     */
    public function install()
    {
        // Nothing todo
    }

    /**
     * Execute arbitrary changes after uninstall
     *
     * @return void
     */
    public function uninstall()
    {
        // Nothing todo
    }

    /**
     * Get prases for app
     *
     * @return array $phrase
     */
    public function getPhrases()
    {
        $phrases = [];
        $keys = [];
        if ($this->pathToData && is_readable($this->pathToData)) {
            $keys = include $this->pathToData;
        }

        $all_phrases = $GLOBALS['rlLang']->getLanguagesList(RL_LANG_CODE);

        foreach($keys as $key) {
            if ($all_phrases[$key]) {
                $phrases[$key] = $all_phrases[$key];
            }
            else {
                $phrases[$key] = $GLOBALS['rlLang']->getPhrase($key, null, null, true);
            }
        }

        // get my listing pages
        if (!$GLOBALS['config']['one_my_listings_page']) {
            foreach(rl('ListingTypes')->types as $type) {
                $myPageKey = 'pages+name+' . $type['My_key'];
                if ($all_phrases[$myPageKey]) {
                    $phrases[$myPageKey] = $all_phrases[$myPageKey];
                }
                else {
                    $phrases[$myPageKey] = $GLOBALS['rlLang']->getPhrase($myPageKey, null, null, true);
                }
            }
        }

        // custom sub
        if ($catLevelsName = $GLOBALS['rlDb']->fetch(
            array('Key', 'Value'),
            array('Status' => 'active', 'Code' => RL_LANG_CODE),
            " AND `Key` LIKE 'multilevel_category+%'",
            null,
            'lang_keys'
        )) {
            foreach($catLevelsName as $item) {
                $phrases[$item['Key']] = $item['Value'];
            }
        }

        return $phrases;
    }

     /**
     * Get config keys
     *
     * @since 1.0.1
     * @return array $keys
     */
    public function getConfigKeys()
    {
        $configKeys = [];
        if ($this->pathToConfigKeys && is_readable($this->pathToConfigKeys)) {
            $configKeys = include $this->pathToConfigKeys;
        }
        return $configKeys;
    }

    /**
     * @hook  apMixConfigItem
     *
     * @param array $config
     * @param array $systemSelects - Required configs with "select" type
     */
    public function hookApMixConfigItem(&$config, &$systemSelects)
    {

        $pluginOptions = ['app_manager_main_listing_type', 'app_manager_home_page_listings', 'app_manager_main_types', 'app_manager_main_types_view', 'app_banners_provider'];

        if (!in_array($config['Key'], $pluginOptions)) {
            return;
        }
        switch ($config['Key']) {
            case 'app_manager_main_listing_type':
                $config['Values'] = array();
                foreach ($GLOBALS['rlListingTypes']->types as $ltype) {
                    $config['Values'][] = array(
                        'ID' => $ltype['Key'],
                        'name' => $GLOBALS['lang']['listing_types+name+' . $ltype['Key']],
                    );
                }
                $config['Values'][0]['required'] = true;

                break;

            case 'app_manager_home_page_listings':
                $systemSelects[] = 'app_manager_home_page_listings';
                break;

            case 'app_manager_main_types':
                $systemSelects[] = 'app_manager_main_types';
                break;

            case 'app_manager_main_types_view':
                $systemSelects[] = 'app_manager_main_types_view';
                break;

            case 'app_banners_provider':
                $systemSelects[] = 'app_banners_provider';
                break;
        }
    }

    /**
     * @hook apPhpConfigBottom
     */
    public function hookApPhpConfigBottom()
    {
        global $config, $configs, $rlSmarty, $rlDb, $lang;

        $group_id = $rlDb->getOne('ID', "`Key` = 'appsManager'", 'config_groups');
        $removeKeys = [
            'app_banners_android',
            'app_banners_android_key',
            'app_banners_ios',
            'app_banners_ios_key',
            'app_banners_in_grid',
            'app_banners_in_grid_interation',
            'app_banners_pages',
        ];

        if (!$GLOBALS['plugins']['yandexKassa']) {
            $removeKeysYooKassa = [
                'app_divider_yookassa',
                'app_yookassa_module',
                'app_yookassa_store_id',
                'app_yookassa_secret_key',
                'app_yookassa_secret_key_msdk',
                'app_yookassa_app_id',
            ];
            $removeKeys = array_merge($removeKeys, $removeKeysYooKassa);
        }
            
        foreach ($configs[$group_id] as $key => &$val) {
            if (in_array($val['Key'], $removeKeys)) {
                unset($configs[$group_id][$key]);
            }
        }

        $rlSmarty->assign('appSelectedAccountTypes', explode(',', $config['app_account_types']));
        
        // App pages for banners
        $appPages = [
            'home' => $lang['pages+name+home'],
            'listing_details' => $lang['pages+name+view_details'],
            'account_details' => $GLOBALS['rlLang']->getPhrase('account_info', null, null, true),
            'listing_types' => $lang['admin_controllers+name+listing_types'],
            'account_types' => $lang['admin_controllers+name+account_types'],
        ];

        $rlSmarty->assign_by_ref('appPages', $appPages);
    }

    /**
     * @hook apPhpConfigBeforeUpdate
     */
    public function hookApPhpConfigBeforeUpdate()
    {
        global $rlConfig;

        if (isset($_POST['app_account_type'])) {
            $keys = array();
            foreach ($_POST['app_account_type'] as $key => $val) {
                if ($val) {
                    $keys[] = $key;
                }
            }
            $rlConfig->setConfig('app_account_types', $keys ? implode(',', $keys) : '');
        }

        $appBannersPages = $_POST['app_banners_pages'] ? implode(',', $_POST['app_banners_pages']) : '';
        if ($GLOBALS['config']['app_banners_pages'] != $appBannersPages) {
            $rlConfig->setConfig('app_banners_pages', $appBannersPages);
        }
    }

    /**
     * @hook apTplFooter
     */
    public function hookApTplFooter()
    {
        global $rlSmarty, $rlAccount, $config;

        if ($_GET['controller'] == 'settings') {
            $exceptTypeKeys = array('visitor', 'affiliate');
            if (!$rlAccount) {
                $GLOBALS['reefless']->loadClass('Account');
            }
            $types = $rlAccount->getAccountTypes($exceptTypeKeys);
            if ($config['membership_module'] 
                && $GLOBALS['rlDb']->getOne('ID', "`Status` = 'active' AND `Key` = 'featured'", 'membership_services')) {
                $featured = [
                    'Key' => 'featured',
                    'name' => $GLOBALS['rlLang']->getPhrase('membership_services+name+featured', null, null, true),
                ];
                array_unshift($types, $featured);
            }
            // $type
            $rlSmarty->assign('appAccountTypes', $types);
            $rlSmarty->display(RL_PLUGINS . 'appsManager' . RL_DS . 'admin' . RL_DS . 'settings.tpl');
        }
    }

    /**
     * @hook apPhpIndexBottom
     */
    public function hookApPhpIndexBottom()
    {
        global $tpl_settings;
        $tpl_settings['category_menu_listing_type'] = true;
        $tpl_settings['category_menu'] = true;
    }

    /**
     * Update to 1.0.1 version
     */
    public function update101()
    {
        // Remove legacy file
        unlink(RL_PLUGINS . 'appsManager/static/phrase_keys.json');
    }
}
