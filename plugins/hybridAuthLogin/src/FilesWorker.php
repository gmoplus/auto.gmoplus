<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : FILESWORKER.PHP
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

class FilesWorker
{
    /**
     * @var array - Plugin folders stucture
     */
    private $paths;

    /**
     * @var string - Folder structure will build depending on this plugin name
     */
    private $pluginName;

    /**
     * @var \rlSmarty
     */
    private $rlSmarty;

    public function __construct($pluginName)
    {
        $this->setPluginName($pluginName);
        if ($GLOBALS['rlSmarty']) {
            $this->rlSmarty = $GLOBALS['rlSmarty'];
        }

        if ($this->getPluginName()) {
            $this->buildPaths();
        }
    }

    /**
     * Getter of the pluginName property
     *
     * @return string
     */
    public function getPluginName()
    {
        return $this->pluginName;
    }

    /**
     * Setter of the pluginName property
     *
     * @param string $pluginName
     */
    public function setPluginName($pluginName)
    {
        $this->pluginName = (string) $pluginName;
    }

    /**
     * Build path and URL to the plugin: view, static folders
     */
    private function buildPaths()
    {

        $pathBase = RL_PLUGINS . $this->getPluginName();
        $urlBase = RL_PLUGINS_URL . $this->getPluginName();

        $configs = array(
            'url' => array(
                'view' => "{$urlBase}/view/",
                'static' => "{$urlBase}/static/",
            ),
            'path' => array(
                'view' => "{$pathBase}/view/",
                'static' => "{$pathBase}/static/",
            ),
        );

        $this->paths = $configs;
    }

    /**
     * Getter of the paths property
     *
     * @return array
     */
    public function getIncludingFilesStructure()
    {
        return $this->paths;
    }

    /**
     * Return full path of the view
     *
     * @param  string $viewName - Tpl file of which you want to get full path
     * @return string
     */
    public function getViewPath($viewName)
    {
        $pluginStructure = $this->getIncludingFilesStructure();
        return sprintf('%s%s.tpl', $pluginStructure['path']['view'], $viewName);
    }

    /**
     * Load view file from the 'view' folder of the plugin
     *
     * @param string $viewName - View name including extension
     */
    public function loadView($viewName)
    {
        $viewPath = $this->getViewPath($viewName);
        if (file_exists($viewPath) && is_readable($viewPath)) {
            $this->rlSmarty->display($viewPath);
        }
    }
}
