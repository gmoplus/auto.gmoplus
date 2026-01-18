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

namespace Flynax\Api\Adapter;

class LegacyClass
{
    /**
     * Description
     *
     * @param  string $className
     * @param  array  $args
     * @return array
     */
    public static function resolveArguments($className, ...$args)
    {
        $isAdminClass = false;
        $pluginName = null;

        if (false !== strpos($className, '/')) {
            if (func_num_args() > 2) {
                throw new \LogicException('rl() accepts only className and classParam if /'); //TODO: message
            }

            if (preg_match('~^(admin|plugins?)/([a-zA-Z_]+)/?([a-zA-Z]+)?$~', $className, $matches)) {
                $package = $matches[1];
                $className = array_pop($matches);
                $classParam = isset($args[0]) ? $args[0] : null;

                if ($package === 'admin') {
                    $isAdminClass = true;
                } else {
                    $pluginName = $matches[2];
                }
            } else {
                throw new \InvalidArgumentException('Wrong className double check it.');
            }
        } else {
            $isAdminClass = isset($args[0]) ? (bool) $args[0] : false;
            $pluginName = isset($args[1]) ? $args[1] : null;
            $classParam = isset($args[2]) ? $args[2] : null;
        }
        return [$className, $isAdminClass, $pluginName, $classParam];
    }

    /**
     * Description
     *
     * @param  string $className
     * @param  bool   $isAdminClass
     * @param  string $pluginName
     * @param  mixed  $classParam
     * @return mixed
     */
    public static function load($className, $isAdminClass = false, $pluginName = null, $classParam = null)
    {
        $class_prefix = 'rl';
        $className = ucfirst($className);
        $real_class_name = ($className === 'Reefless') ? 'reefless' : "$class_prefix{$className}";
        $use_load_class = ($real_class_name !== 'reefless' && $real_class_name !== 'rlDb');

        if (isset($GLOBALS[$real_class_name]) && is_object($object = $GLOBALS[$real_class_name])) {
            return $object;
        }

        // No need to create reefless instance if $className is reefless or rlDb
        if ($use_load_class) {
            global $reefless;

            if (!isset($reefless)) {
                $reefless = self::load('reefless');
            }

            if (!is_object($reefless)) {
                throw new \LogicException('The reefless class not found');
            }
        }
        $filename = $pluginName ? RL_PLUGINS . $pluginName . RL_DS : RL_CLASSES;

        if ($isAdminClass) {
            $filename .= 'admin/' . $real_class_name . '.class.php';
        } else {
            $filename .= $real_class_name . '.class.php';
        }

        if (env('API_ENV') === 'testing' && defined('TEST_LEGACY_LOAD') && $real_class_name !== 'reefless') {
            return $filename;
        }

        if (!is_file($filename)) {
            throw new \LogicException(sprintf('The %s class not found', $real_class_name));
        }

        if (!$use_load_class) {
            require_once $filename;

            return $GLOBALS[$real_class_name] = new $real_class_name($classParam);
        }

        $class_package = $isAdminClass ? 'admin' : null;
        $reefless->loadClass($className, $class_package, $pluginName, $classParam);

        return $GLOBALS[$real_class_name];
    }
}
