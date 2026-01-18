<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : REQUESTS.PHP
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
namespace ReportListings\Helpers;

use ReportListings\FlynaxObjectsContainer;

class Requests
{
    /**
     * @var \rlValid
     */
    private $rlValid;
    
    /**
     * Type of the method request
     * @var string
     */
    private $type;
    
    /**
     * Requests constructor.
     */
    public function __construct()
    {
        $this->type = $_REQUEST;
        $this->rlValid = FlynaxObjectsContainer::getObject('rlValid');
    }
    
    /**
     * Main getting request method
     *
     * @param string $name    - key of the needed variable in request
     * @return mixed|Requests -
     */
    public static function request($name = '')
    {
        $self = new self();
        if (!$name) {
            return $self;
        }
        
        return $self->sanitize($_REQUEST[$name]);
    }
    
    /**
     * Getting all requests elements
     *
     * @param  string $type     - type of the request: {post, get or '' - to get all}
     * @return array  $requests - sanitized requests array
     */
    public function all($type = '')
    {
        $requests = array();
        $method  = $_REQUEST;
        
        switch (strtolower($type)) {
            case 'post':
                $method = $_POST;
                break;
            case 'get':
                $method = $_GET;
                break;
        }
        
        foreach ($method as $key => $value) {
            $requests[$key] = $this->sanitize($value);
        }
        
        return $requests;
    }
    
    /**
     * Return satinized $_POST data (all() method helper)
     *
     * @param  string $name - Looking element
     * @return mixed        - Sanitized element of the request
     */
    public function post($name)
    {
        $this->type = $_POST;
        return $this->sanitize($_POST[$name]);
    }
    
    /**
     * Return satinized $_GET data (all() method helper)
     *
     * @param  string $name - Looking element
     * @return mixed        - Sanitized element of the request
     */
    public function get($name)
    {
        $this->type = $_GET;
        return $this->sanitize($_GET[$name]);
    }
    
    /**
     * Sanitize provided data
     *
     * @param  mixed      $data - Unsinitized data
     * @return int|mixed  $data - Sanitized data
     */
    public function sanitize($data)
    {
        if (is_array($data) || !is_numeric($data)) {
            if (is_object($this->rlValid)) {
                return $this->rlValid->xSql($data);
            }
        
            return $data;
        }
    
        return (int)$data;
    }
}
