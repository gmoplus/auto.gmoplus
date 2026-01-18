<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLREPORTBROKENLISTING.CLASS.PHP
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
