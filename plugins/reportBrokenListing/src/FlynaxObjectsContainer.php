<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : FLYNAXOBJECTSCONTAINER.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2022
 *	https://www.flynax.com
 *
 ******************************************************************************/

namespace ReportListings;

class FlynaxObjectsContainer
{
    /*
     * Container class instance
     */
    private static $instance = null;
    
    /**
     * @var array - plugin global options
     */
    public  static $options;
    
    /**
     * @var array - Array of the classes objects
     */
    private static $objects = array();
    
    /**
     * Return Container class instance.
     *
     * @return object - Container class instance
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Return AutPosterContainer class instance. Short way function
     *
     * @return object - Container class instance
     */
    public static function i()
    {
        return self::getInstance();
    }
    
    /**
     * Getting plugin configurations
     *
     * @param  string $name - Configuration name
     * @return array        - Plugin configurations array
     */
    public static function getConfig($name)
    {
        return self::$options[$name];
    }
    
    /**
     * Setting plugin configuration
     *
     * @param string $name  - Configuration name
     * @param mixed  $value - Configuration value
     */
    public static function setConfig($name, $value)
    {
        self::$options[$name] = $value;
    }
    
    /**
     * Adding classes instances like rlAccount, rlDb and etc
     *
     * @param  string $key   - Object key
     * @param  object $value - Class instance
     * @return bool          - Is successfully added
     */
    public static function addObject($key, $value)
    {
        if (is_object($value)) {
            self::$objects[$key] = $value;
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Return instance of the class
     *
     * @param  string $key - Object name
     * @return object      - Class instance
     */
    public static function getObject($key)
    {
        return self::$objects[$key];
    }
    
    /**
     * Does object is exist in the container
     *
     * @param string $key - Looking object key
     * @return bool       - Does this object is exist
     */
    public static function hasObject($key)
    {
        return isset(self::$objects[$key]);
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
