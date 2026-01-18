<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: AUTOPOSTERMODULES.PHP
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

namespace Autoposter;

use Autoposter\AutoPosterContainer;

class AutoPosterModules
{
    /**
     * @var object - rlDb class instance
     */
    protected $rlDb;

    /**
     * @var object - rlAutoPoster class instance
     */
    protected $rlAutoPoster;

    /**
     * @var string - Module
     */
    private $module;

    /**
     * AutoPosterModules constructor
     */
    public function __construct()
    {
        $this->rlAutoPoster = AutoPosterContainer::getObject('rlAutoPoster');
        $this->rlDb = AutoPosterContainer::getObject('rlDb');
    }

    /**
     * Getting all modules existing in the plugin
     *
     * @return array - Modules
     */
    public function getAll()
    {
        $sql = "SELECT * FROM `" . RL_DBPREFIX . "autoposting_modules`";
        return $this->rlDb->getAll($sql);
    }

    /**
     * Adding auto posting module
     *
     * @param  array $data - Module information
     * @return bool        - Adding status
     */
    public function add($data)
    {
        return $this->rlActions->insertOne($data, 'autoposting_modules');
    }

    /**
     * Getting all settings by module
     *
     * @param  string $module   - Module key
     * @return array  $settings - Settings array
     */
    public function getSettingsByKey($module)
    {
        $settings = array();
        $find_by = $this->rlAutoPoster->getConfig('plugin_prefix') . $module;
        $sql = "SELECT `T1`.*, `T2`.`Value` AS `name`, `T3`.`Value` AS `des` ";
        $sql .= "FROM `" . RL_DBPREFIX . "config` AS `T1` ";
        $sql .= "LEFT JOIN `" . RL_DBPREFIX . "lang_keys` AS `T2` ON CONCAT('config+name+',`T1`.`Key`) = `T2`.`Key` ";
        $sql .= "AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
        $sql .= "LEFT JOIN `" . RL_DBPREFIX . "lang_keys` AS `T3` ON CONCAT('config+des+',`T1`.`Key`) = `T3`.`Key` ";
        $sql .= "AND `T3`.`Code` = '" . RL_LANG_CODE . "' ";
        $sql .= "WHERE `Type` <> 'divider' AND `T1`.`Key` LIKE '{$find_by}_%'";
        $sql .= "GROUP BY `T1`.`ID` ORDER BY `T1`.`Position` ASC";
        $settings = $this->rlDb->getAll($sql);

        // modify setting array
        $modules = $this->rlAutoPoster->getConfig('modules');
        $module_custom_settings = $modules[$module]['custom_settings'];

        if ($module_custom_settings) {
            foreach ($settings as $key => $setting) {
                if (in_array($setting['Key'], array_keys($module_custom_settings))) {
                    // change value
                    $settings[$key]['Values'] = $module_custom_settings[$setting['Key']]['values'];

                    // run onSubmit methods
                    if ($method = $module_custom_settings[$setting['Key']]['onSubmit']) {
                        $provider = (new \Autoposter\ProviderController($module))->getProvider();
                        if (method_exists($provider, $method) && isset($_POST['post_config'][$setting['Key']])) {
                            $field_value = $_POST['post_config'][$setting['Key']];
                            $response = call_user_func(array($provider, $method), $field_value);
                            if ($response['status'] == 'ERROR') {
                                $settings[$key]['error'] = $response['message'];
                                $settings[$key]['validate'] = 'required';
                            }
                        }
                    }
                }
            }
        }

        // change setting array - add required field
        $this->makeRequiredConfigs($module, $settings);
        return $settings;
    }

    public function getSettingValue($key, $settings_array)
    {
        foreach ($settings_array as $setting) {
            if ($setting['Key'] == $key) {
                return $setting['Default'];
            }
        }

        return false;
    }

    /**
     * Return module status
     *
     * @param  string      $module - Module key
     * @return string|bool         - Module status or false if row doesn't exist
     */
    public function getStatus($module)
    {
        return $this->rlDb->getOne('Status', "`Key` = '{$module}'", 'autoposting_modules');
    }

    /**
     * Change settings array by adding required mark to the field
     *
     * @param  string  $module   - Module key
     * @param  string  $settings - Setting array
     */
    public function makeRequiredConfigs($module, &$settings)
    {
        $all_modules = $this->rlAutoPoster->getConfig('modules');
        $rules = $all_modules[$module]['validate'];

        foreach ($settings as $key => $setting) {
            if (key_exists($setting['Key'], $rules)) {
                $rule = $rules[$setting['Key']];
                $settings[$key]['validate'] = $rule;
            }
        }
    }

    /**
     * Getting active module
     *
     * @return mixed
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Setting active module
     *
     * @param string $module
     */
    public function setModule($module)
    {
        $this->module = $module;
    }

    /**
     * Allow management of the module
     *
     * @param  string $module - Module key
     * @return bool           - Module is configured fine and I can show management block
     */
    public function allowManagement($module)
    {
        $settings = $this->getSettingsByKey($module);
        $modules = $this->rlAutoPoster->getConfig('modules');
        $module_custom_settings = $modules[$module]['custom_settings'];

        $allow = true;
        foreach ($settings as $setting) {
            if ($module_custom_settings && in_array($setting['Key'], array_keys($module_custom_settings))) {
                $custom_setting = $module_custom_settings[$setting['Key']];
                if ($custom_setting['boundTo']) {
                    $boundRule = explode('|', $custom_setting['boundTo']);
                    $checking_field = $boundRule[0];
                    $value_should = $boundRule[1];
                    $current_value = $this->getSettingValue($checking_field, $settings);

                    if ($current_value == $value_should && !$setting['Default']) {
                        return false;
                    }
                }
            }

            if ($setting['validate'] && !$setting['Default']) {
                $allow = false;
                break;
            }
        }

        return $allow;
    }

    /**
     * Get message body of the module
     *
     * @param string       $module      - Module key
     * @return string|bool $messageBody - Body of the message, that user is saved as pattern
     *                                    false, if message did not found
     */
    public function getMessageBody($module)
    {
        $messageBody = $this->rlDb->getOne('Message_body', "`Key` = '{$module}'", 'autoposting_modules');
        if (!$messageBody) {
            return false;
        }

        return $messageBody;
    }

    /**
     * Save general settings of the module like: {status}
     *
     * @param  array  $data   - Saving data
     * @param  string $module - Module key
     * @return bool           - Saving answer
     */
    public function saveGeneralSettings($data, $module)
    {
        $rlActions = AutoPosterContainer::getObject('rlActions');
        $updateData = array(
            'fields' => $data,
            'where' => array(
                'Key' => $module,
            ),
        );

        return $rlActions->updateOne($updateData, 'autoposting_modules');
    }

    /**
     * Is configuration has been changed
     *
     * @param  array  $new_configs - Array of the new configurations
     * @param  string $module      - Active module
     * @return bool                - Does configurations has been changed
     */
    public function isConfigurationChanged($new_configs, $module)
    {
        $old_configs = $this->getSettingsByKey($module);
        $has_changed = false;

        foreach ($old_configs as $old) {
            if (in_array($old['Key'], array_keys($new_configs))) {
                if ($old['Default'] != $new_configs[$old['Key']]) {
                    $has_changed = true;
                    break;
                }
            }
        }

        return $has_changed;
    }

    /**
     * Getting all pages
     * @since 1.1.0
     *
     * @return mixed $pages - Pages array
     */
    public function getAllPages()
    {
        $this->rlDb->setTable('pages');
        $this->rlDb->outputRowsMap = array('Key', 'Path');
        $pages = $this->rlDb->fetch($this->rlDb->outputRowsMap, array('Status' => 'active'));
        $this->rlDb->resetTable();

        return $pages;
    }
}
