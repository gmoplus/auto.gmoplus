<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.7.0
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: FILESWORKER.PHP
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

namespace Flynax\Plugins\AccountSync\Files;

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

    /**
     * Get path of the provided JS file
     * Helper of getStaticBy method
     *
     * @param string $file
     *
     * @return string
     */
    public function getJsFilePath($file)
    {
        return $this->getStaticBy('path', $file);
    }

    /**
     * Get url of the provided JS file
     * Helper of the getStaticBy method
     *
     * @param string $file
     * @return string
     */
    public function getJsFileUrl($file)
    {
        return $this->getStaticBy('url', $file);
    }

    /**
     * Get path of the provided CSS file
     * Helper of getStaticBy method
     *
     * @param string $file
     *
     * @return string
     */
    public function getCssFilePath($file)
    {
        return $this->getStaticBy('path', $file);
    }

    /**
     * Get URL of the provided CSS file
     * Helper of getStaticBy method
     *
     * @param string $file
     *
     * @return string
     */
    public function getCssFileURL($file)
    {
        return $this->getStaticBy('url', $file);
    }

    /**
     * Return HTML with included js file in it
     *
     * @param string $file
     *
     * @return string
     */
    public function renderJsFileInclude($file)
    {
        if (file_exists($this->getJsFilePath($file))) {
            return sprintf('<script src="%s"></script>', $this->getJsFileUrl($file));
        }
    }

    /**
     * Return HTML with including css file in it
     *
     * @param string $file
     *
     * @return string
     */
    public function renderCssFileInclude($file)
    {
        if (file_exists($this->getCssFilePath($file))) {
            return sprintf('<link href="%s" type="text/css" rel="stylesheet" />', $this->getCssFileURL($file));
        }
    }

    /**
     * Get file path or URL by it name
     *
     * @param string $type - Type of getting info: path, url
     * @param string $file - File name
     *
     * @return string - Path or URL of the static file of the plugin
     */
    private function getStaticBy($type, $file)
    {
        $type = strtolower($type);
        return in_array($type, array('path', 'url')) ? sprintf('%s%s', $this->paths[$type]['static'], $file) : '';
    }
}
