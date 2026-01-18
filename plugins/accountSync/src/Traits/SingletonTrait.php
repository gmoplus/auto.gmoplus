<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.7.0
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: SINGLETONTRAIT.PHP
 *
 *	The software is a commercial product delivered under single, non-exclusive,
 *	non-transferable license for one domain or IP address. Therefore distribution,
 *	sale or transfer of the file in whole or in part without permission of Flynax
 *	respective owners is considered to be illegal and breach of Flynax License End
 *	User Agreement.
 *
 *	You are not allowed to remove this information from the file without permission
 *	of Flynax respective owners.
 *
 *	Flynax Classifieds Software 2022 |  All copyrights reserved.
 *
 *	https://www.flynax.com/
 *
 ******************************************************************************/

namespace Flynax\Plugins\AccountSync\Traits;

/**
 * Trait SingletonTrait
 *
 * @package Flynax\Plugin\AccountSinc\Traits
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
        return isset(static::$instance)
            ? static::$instance
            : static::$instance = new static;
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
    final private function __construct()
    {
    }

    /**
     * Prevent class serializing
     */
    final private function __wakeup()
    {
    }

    /**
     * Prevent cloning object
     */
    final private function __clone()
    {
    }
}
