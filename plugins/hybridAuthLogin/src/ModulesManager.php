<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : MODULESMANAGER.PHP
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

namespace Flynax\Plugins\HybridAuth;

use Flynax\Plugins\HybridAuth\Configs as HybridAuthConfigs;
use Flynax\Plugins\HybridAuth\Traits\UrlTrait;

class ModulesManager
{
    use UrlTrait;

    /**
     * @var \rlDb
     */
    protected $rlDb;

    /**
     * @var \rlActions
     */
    private $rlActions;

    /**
     * @var string - Class is working with this module now
     */
    private $activeModule;

    /**
     * ModulesManager constructor.
     */
    public function __construct()
    {
        $this->rlDb = hybridAuthMakeObject('rlDb');
        $this->rlActions = hybridAuthMakeObject('rlActions');
    }

    /**
     * Get all modules
     *
     * @return array
     */
    public function getAllModules()
    {
        return array_keys(Configs::i()->getConfig('providers'));
    }

    /**
     * Get total count of modules
     *
     * @return int
     */
    public function modulesTotalCount()
    {
        return count(Configs::i()->getConfig('providers'));
    }

    /**
     * Checking, does module has been configured successful
     *
     * @param  string $provider - Checking provider name
     * @return bool
     */
    public function isModuleConfigured($provider)
    {
        if (!$provider) {
            return false;
        }

        if ('active' !== $this->rlDb->getOne('Status', "`Provider` = '{$provider}'", 'ha_providers')) {
            return false;
        }

        $requiredSettings = $this->getProvidersRequiredSettings($provider);
        $config = HybridAuthConfigs::i()->getConfig('flynax_configs');
        $isConfigured = true;

        foreach ($requiredSettings as $key) {
            if (!$config[$key]) {
                $isConfigured = false;
                break;
            }
        }

        return $isConfigured;
    }

    /**
     * Get keys of the required settings
     *
     * @param string $provider - Provider
     * @return array
     */
    public function getProvidersRequiredSettings($provider)
    {
        $providerRequiredSettings = array();

        if (!$provider) {
            return $providerRequiredSettings;
        }

        $providersFromBootstrap = HybridAuthConfigs::i()->getConfig('providers');
        $providerRequiredSettings = array_keys($providersFromBootstrap[$provider]['validate']);

        return (array) $providerRequiredSettings;
    }

    /**
     * Get all setting of the module (including validation rule from the bootstrap file)
     *
     * @param  string $provider - Provider key which setting do you want to get
     * @return array  $settings
     */
    public function getModuleSettings($provider)
    {
        $sql = "SELECT `T1`.*, `T2`.`Value` AS `name`, `T3`.`Value` AS `des` ";
        $sql .= "FROM `" . RL_DBPREFIX . "config` AS `T1` ";
        $sql .= "LEFT JOIN `" . RL_DBPREFIX . "lang_keys` AS `T2` ON CONCAT('config+name+',`T1`.`Key`) = `T2`.`Key` ";
        $sql .= "AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
        $sql .= "LEFT JOIN `" . RL_DBPREFIX . "lang_keys` AS `T3` ON CONCAT('config+des+',`T1`.`Key`) = `T3`.`Key` ";
        $sql .= "AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
        $sql .= "WHERE `Type` <> 'divider' AND `T1`.`Key` LIKE 'ha_{$provider}_%'";
        $sql .= "GROUP BY `T1`.`ID` ";
        $settings = (array) $this->rlDb->getAll($sql);

        if (!$settings) {
            return [];
        }

        $providersFromBootstrap = HybridAuthConfigs::i()->getConfig('providers');

        foreach ($settings as $key => $setting) {
            if ($providersFromBootstrap[$provider]['validate'][$setting['Key']]) {
                $settings[$key]['required'] = true;
            }
        }

        return $settings;
    }

    /**
     * Putting module settings into Flynax configurations Database
     *
     * @param  array $configs - Configurations which you want to save
     * @param  array $module  - Module
     * @return bool|array     - Boolean if there is no validation error
     *                          Array with error in the case if setting doesn't pass validation
     */
    public function saveModuleSettings($configs, $module)
    {
        if (!$configs || !$module) {
            return false;
        }

        if ($errors = $this->validateSettings($configs, $module)) {
            return $errors;
        }

        foreach ($configs as $key => $value) {
            $update = array(
                'fields' => array(
                    'Default' => trim($value),
                ),
                'where' => array(
                    'Key' => $key,
                ),
            );

            $this->rlActions->updateOne($update, 'config');
        }

        return true;
    }

    /**
     * Validate providers settings after saving it in the module section
     *
     * @param  array  $savedSettings - All filled by user settings of the module
     * @param  string $provider      - Provider key
     * @return array  $errors        - Validation errors
     */
    public function validateSettings($savedSettings, $provider = '')
    {
        if ($provider) {
            $this->setActiveModule($provider);
        }

        $moduleSettings = $this->getModuleSettings($provider);
        $errors = array();
        $lang = HybridAuthConfigs::i()->getConfig('flynax_phrases');

        foreach ($moduleSettings as $key => $setting) {
            if ($setting['required'] && !$savedSettings[$setting['Key']]) {
                $errors[] = str_replace(
                    '{field}',
                    sprintf('<b>%s</b>', $setting['name']),
                    $lang['notice_field_empty']
                );
            }
        }

        return $errors;
    }

    /**
     * Getter of the activeModule property
     *
     * @return mixed
     */
    public function getActiveModule()
    {
        return $this->activeModule;
    }

    /**
     * Setter of the activeModule property
     *
     * @param mixed $activeModule
     */
    public function setActiveModule($activeModule)
    {
        $this->activeModule = $activeModule;
    }

    /**
     * Return full module url for social network
     *
     * @param  string $module - Provider (aka Module) name
     * @return string         - Link to the requests.php file of the plugin with prefilled provider callback
     */
    public function getModuleUrl($module)
    {
        return $this->getRedirectURLToTheProvider($module);
    }

    /**
     * Set Group ID of all provider configurations to 0
     */
    public function updateGroupID()
    {
        $sql = "UPDATE `" . RL_DBPREFIX . "config` SET `Group_ID` = 0 ";
        $sql .= "WHERE `Key` != 'ha_enable_avatar_uploading' AND `Key` != 'ha_enable_password_synchronization' ";
        $sql .= "AND `Plugin` = 'hybridAuthLogin'";
        $this->rlDb->query($sql);
    }
}
