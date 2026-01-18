<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : CONFIG.PHP
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

namespace Flynax\Plugins\PWA;

use Flynax\Plugins\PWA\Traits\SingletonTrait;

class Config
{
    use SingletonTrait;

    const CONFIG_SINGLE = 'single';
    const CONFIG_MULTIPLE = 'multiple';

    private $configs;

    public $multiLangConfigs = [];
    public $singleConfigs = [];

    public function getConfigs()
    {
        return $this->configs;
    }

    public function fetchAllConfigs()
    {
        /* fetch multi-lang configs */
        foreach ($this->multiLangConfigs as $config) {
            $info = $this->getMultiLangConfig($config);
            $this->setConfig($config, $info, self::CONFIG_MULTIPLE);
        }

        $sql = "SELECT `Key`, `Default` FROM `{db_prefix}config` WHERE `Plugin` = 'PWA' ";
        $configs = $GLOBALS['rlDb']->getAll($sql);

        foreach ($configs as $config) {
            $this->singleConfigs[] = $config['Key'];
            $this->setConfig($config['Key'], $config['Default'], self::CONFIG_SINGLE);
        }

    }

    private function getMultiLangConfig($key)
    {
        $key = (string) $key;
        if (!in_array($key, $this->multiLangConfigs)) {
            return '';
        }

        $configKey = sprintf('flynax_pwa+%s', $key);

        $sql = "SELECT `Code`, `Value` FROM `{db_prefix}lang_keys` ";
        $sql .= "WHERE `Key` = '{$configKey}' AND `Plugin` = 'PWA' GROUP BY `Key`, `Code` ";
        $info = $GLOBALS['rlDb']->getAll($sql, 'Code');

        return $info;
    }

    public function setConfig($key, $value, $type = 'single')
    {
        $type = $type ?: 'single';
        $this->configs[$type][$key] = $value;
    }

    public function getConfig($key, $lang = '')
    {
        if ($lang) {
            return isset($this->configs[self::CONFIG_MULTIPLE][$key][$lang]['Value'])
                ? $this->configs[self::CONFIG_MULTIPLE][$key][$lang]['Value']
                : '';
        }

        return isset($this->configs[self::CONFIG_SINGLE][$key]) ? $this->configs[self::CONFIG_SINGLE][$key] : '';
    }
}
