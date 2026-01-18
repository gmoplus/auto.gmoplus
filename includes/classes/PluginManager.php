<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: INDEX.PHP
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

namespace Flynax\Classes;

/**
 * @since 4.7.0
 */
class PluginManager
{
    /**
     * Get plugin instance with key and name
     *
     * @param string $plugin_key
     * @param string $plugin_class
     *
     * @throws \LogicException
     *
     * @return bool|object
     */
    public static function getPluginInstance($plugin_key, $plugin_class)
    {
        if (!$plugin_key || !$plugin_class) {
            return false;
        }
        $plugin_class = 'rl' . $plugin_class;

        if (!file_exists($filename = sprintf('%s%s/%s.class.php', RL_PLUGINS, $plugin_key, $plugin_class))) {
            throw new \LogicException(sprintf('The %s class not found', $plugin_class));
        }
        require_once $filename;

        $instance = new $plugin_class;

        return $instance;
    }
}
