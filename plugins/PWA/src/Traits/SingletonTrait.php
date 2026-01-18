<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : SINGLETONTRAIT.PHP
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

namespace Flynax\Plugins\PWA\Traits;

/**
 * Trait SingletonTrait
 *
 * @package Flynax\Plugins\PWA\Traits
 */
trait SingletonTrait
{
    /**
     * @var self
     */
    protected static $instance;

    /**
     * Getting current instance of the class
     * @return self
     */
    final public static function getInstance()
    {
        return static::$instance ?? static::$instance = new static;
    }

    /**
     * Get current instance of the class (helper of the getInstance() method)
     *
     * @return static
     */
    final public static function i()
    {
        return self::getInstance();
    }

    /**
     * SingletonTrait constructor.
     */
    final public function __construct()
    {
    }

    /**
     * Prevent class serializing
     */
    final public function __wakeup()
    {
    }

    /**
     * Prevent cloning object
     */
    final public function __clone()
    {
    }
}
