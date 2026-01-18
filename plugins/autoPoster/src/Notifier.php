<?php


/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : NOTIFIER.PHP
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

namespace Autoposter;

class Notifier
{
    /**
     * @var string - current module
     */
    protected $module;
    
    /**
     * @var bool - is debug mode enabled
     */
    protected $is_debug;
    
    /**
     * @var array - url arguments, which are buil into url
     */
    protected $url_args;
    
    /**
     * @var \rlNotice
     */
    protected $rlNotice;
    /**
     * @var \rlDebug
     */
    protected $rlDebug;
    
    /**
     * @var \reefless
     */
    protected $reefless;
    
    
    /**
     * Notifier constructor
     *
     * @param $module
     */
    public function __construct($module)
    {
        $this->rlNotice = AutoPosterContainer::getObject('rlNotice');
        $this->rlDebug = AutoPosterContainer::getObject('rlDebug');
        $this->reefless = AutoPosterContainer::getObject('reefless');
        
        
        $this->is_debug = false;
        $this->module = $module;
        $args['controller'] = 'auto_poster';
        $args['module'] = $module;
        $this->url_args = $args;
        
    }
    
    /**
     * Show notice on the Edit page of the module
     *
     * @param string $message - Notice message
     */
    public function toEditWithMessage($message)
    {
        $this->url_args['action'] = 'edit';
        $this->_showNotice($message, 'notice');
    }
    
    /**
     * Show notice on the Main page of the module
     *
     * @param string $message - Notice message
     */
    public function toMainWithMessage($message)
    {
        $this->_showNotice($message, 'notice');
    }
    
    /**
     * Show error on the Edit page of the module
     *
     * @param string $message - Notice message
     */
    public function toEditWithError($message)
    {
        $this->url_args['action'] = 'edit';
        $this->_showNotice($message, 'errors');
    }
    
    /**
     * Show error on the Main page of the module
     *
     * @param string $message - Notice message
     */
    public function toMainWithError($message)
    {
        $this->_showNotice($message, 'errors');
    }
    
    /**
     * Show notice
     *
     * @param string $message - Message body
     * @param string $type    - Message type: {alert, errros}
     */
    public function _showNotice($message, $type)
    {
        $this->rlNotice->saveNotice($message, $type);
        $this->reefless->redirect(null, $this->buildUrl());
        exit;
    }
    
    /***
     * Saving notice wihout redirecting
     *
     * @param string $message - Notice body
     */
    public function _saveNotice($message)
    {
        $this->rlNotice->saveNotice($message);
    }
    
    /**
     * Save message to the error.log file
     *
     * @param  string $message - Notice body
     * @return object $this    - Current instace of the class
     */
    public function logMessage($message)
    {
        $message = preg_replace('/[\\\]?[\r\n]+/m', '', $message);
        $message = preg_replace('/\s+/', ' ', $message);

        $this->rlDebug->logger('autoPoster - ' . $message);
        return $this;
    }
    
    /**
     * Build redirect URL
     *
     * @return string - Redirect url
     */
    public function buildUrl()
    {
        return  RL_URL_HOME . ADMIN . '/index.php?' . http_build_query($this->url_args);
    }
}
