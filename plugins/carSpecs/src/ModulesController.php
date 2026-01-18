<?php
/**copyright**/

namespace Flynax\Plugins\CarSpecs;

/**
 * Class ModulesController
 *
 * @since 2.1.0
 *
 * @package Flynax\Plugins\CarSpecs
 */
class ModulesController
{
    /**
     * Get instance of the module but it key from configurations
     *
     * @param string $moduleName - Module key
     *
     * @return object|bool - Instance of the module | False if something went wrong
     */
    public static function resolve($moduleName = '')
    {
        $moduleName = ucfirst(str_replace('.php', '', $moduleName));
        $moduleClass = sprintf('\\Flynax\\Plugins\\CarSpecs\\Modules\\%s', $moduleName);

        return class_exists($moduleClass) ? new $moduleClass() : false;
    }
}
