<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : PLUGINPATHBUILDER.PHP
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

namespace Flynax\Plugin\WordPressBridge;

use Flynax\Plugin\WordPressBridge\Traits\SingletonTrait;

/**
 * Class PluginPathBuilder
 *
 * @since 2.0.0
 *
 * @package Flynax\Plugin\WordPressBridge
 */
class PluginPathBuilder
{
    use SingletonTrait;

    /**
     * @var string - Plugin key
     */
    private $pluginName;

    /**
     * @var array - Plugin folder structure
     */
    private $folderStructure;

    /**
     * Setter of the pluginName property
     *
     * @param string $pluginName
     */
    public function setPluginName($pluginName)
    {
        $pluginName = (string) $pluginName;
        $this->pluginName = $pluginName;
        $this->rebuildPaths();
    }

    /**
     * Rebuild all plugin paths
     */
    private function rebuildPaths()
    {
        $basicPluginStructure = array();

        $pluginName = $this->getPluginName();
        $rootPath = RL_PLUGINS . $pluginName;
        $rootUrl = RL_PLUGINS_URL . $pluginName;
        $mainPluginsFolders = ['view', 'admin', 'static'];

        foreach ($mainPluginsFolders as $folder) {
            $basicPluginStructure['path'][$folder] = "{$rootPath}/$folder/";
            $basicPluginStructure['url'][$folder] = "{$rootUrl}/$folder/";
        }

        $this->folderStructure = $basicPluginStructure;
    }

    /**
     * Get plugin key
     *
     * @return string
     */
    public function getPluginName()
    {
        return $this->pluginName;
    }

    /**
     * Get url to the specified static (JS/CSS) file in the plugin directory
     *
     * @param string $jsFileName
     *
     * @return string
     */
    public function getStaticFileUrl($jsFileName)
    {
        $jsPath = $this->folderStructure['path']['static'] . $jsFileName;
        if (!file_exists($jsPath)) {
            return '';
        }

        return $this->folderStructure['url']['static'] . $jsFileName;
    }

    /**
     * Include provided JS file on the page
     *
     * @param string $fileName - JS file name from the plugin static folder
     */
    public function addJsToPage($fileName)
    {
        if ($jsUrl = $this->getStaticFileUrl($fileName)) {
            echo sprintf("<script src='%s' type='text/javascript'></script>", $jsUrl);
        }
    }

    /**
     * Get provided view path in the plugin directory
     *
     * @param string $viewName - View name (without .tpl extension)
     * @return string
     */
    public function getViewPath($viewName)
    {
        return $this->folderStructure['path']['view'] . $viewName . '.tpl';
    }
}
