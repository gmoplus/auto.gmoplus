<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: CREATESAPPLICATIONTRAIT.PHP
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

use Flynax\Api\Adapter\LegacyClass;
use Flynax\Api\Api;

if (!function_exists('api')) {
    /**
     * Get the available container instance.
     *
     * @param  string  $abstract
     * @param  array   $parameters
     * @return mixed|Illuminate\Container\Container
     */
    function api($abstract = null, array $parameters = [])
    {
        if (null === $abstract) {
            return Api::getInstance();
        }
        return Api::getInstance()->makeWith($abstract, $parameters);
    }
}

if (!function_exists('rl')) {
    /**
     * Load legacy classes with prefix "rl"
     *
     * @param string $className
     * @param bool   $isAdminClass
     * @param string $pluginName
     * @param mixed  $classParam
     * @return mixed
     */
    function rl(...$args)
    {
        if (empty($args)) {
            return LegacyClass::load('reefless');
        }
        list($className, $isAdminClass, $pluginName, $classParam) = LegacyClass::resolveArguments(...$args);

        return LegacyClass::load($className, $isAdminClass, $pluginName, $classParam);
    }
}

if (!function_exists('rl_admin')) {
    /**
     * Load legacy classes with prefix "rl".
     * Shortcut to load admin classes
     *
     * @see rl()
     *
     * @param string $className
     * @param string $classParam
     * @return mixed
     */
    function rlAdmin($className, $classParam = null)
    {
        if (false !== strpos($className, '/')) {
            throw new \InvalidArgumentException('rl_admin() do not accepts className with /; use rl() instead.');
        }
        return LegacyClass::load($className, true, null, $classParam);
    }
}

if (!function_exists('rl_plugin')) {
    /**
     * Load legacy classes with prefix "rl".
     * Shortcut to load plugins classes
     *
     * @see rl()
     *
     * @param string $className
     * @param string $classParam
     * @param string $pluginName
     * @return mixed
     */
    function rlPlugin($className, $pluginName = null, $classParam = null)
    {
        if (false !== strpos($className, '/')) {
            throw new \InvalidArgumentException('rl_plugin() do not accepts className with /; use rl() instead.');
        }
        return LegacyClass::load($className, false, $pluginName, $classParam);
    }
}

if (!function_exists('core_path')) {
    /**
     * Get the path to the Flynax core.
     *
     * @param  string  $path
     * @return string
     */
    function corePath($path = '')
    {
        return realpath(api()->basePath() . '/../..') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('plugins_path')) {
    /**
     * Get the path to the Flynax plugins.
     *
     * @param  string  $path
     * @return string
     */
    function pluginsPath($path = '')
    {
        return corePath('/plugins') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('api_path')) {
    /**
     * Get the path to the API folder.
     *
     * @param  string  $path
     * @return string
     */
    function apiPath($path = '')
    {
        return api('path') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the path to the base of the install.
     *
     * @param  string  $path
     * @return string
     */
    function basePath($path = '')
    {
        return api()->basePath() . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('response')) {
    /**
     * Return a new response from the application.
     *
     * @param  mixed   $content
     * @param  int     $status
     * @param  array   $headers
     * @return Illuminate\Http\Response
     */
    function response($content = '', $status = 200, array $headers = [])
    {
        return with(new Illuminate\Http\Response($content, $status, $headers));
    }
}
