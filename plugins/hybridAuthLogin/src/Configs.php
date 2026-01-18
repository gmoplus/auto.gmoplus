<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : CONFIGS.PHP
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

class Configs
{
    /**
     * @var array - Plugin configs
     */
    protected $configs;

    /**
     * @var Configs class instance
     */
    private static $instance = null;

    /**
     * Get plugin config by name
     *
     * @param string $configName
     * @return mixed              - Configuration plugin value
     */
    public  function getConfig($configName = '')
    {
        return $this->configs[$configName];
    }

    /**
     * Set plugin configuration
     *
     * @param string $configName
     * @param string $value
     * @return bool
     */
    public function setConfig($configName = '', $value = '')
    {
        if (!$configName || !$value) {
            return false;
        }

        $this->configs[$configName] = $value;
        return true;
    }

    /**
     * Get instance of the class
     *
     * @return \Flynax\Plugins\HybridAuth\Configs
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Return an instance of the current class
     * Helper of the getInstance method
     *
     * @return \Flynax\Plugins\HybridAuth\Configs
     */
    public static function i()
    {
        return self::getInstance();
    }

    /*
    * Cloning blocked of the Singleton class
    */
    private function __clone()
    {
    }

    /**
     * Constructor is empty for Singleton
     */
    private function __construct()
    {
    }
}
