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

class rlSmarty
{
    private $data = array();

    // Overloading
    public function __call($name, $arguments)
    {
        if ($name == 'assign' || $name == 'assign_by_ref') {
            $key = $arguments[0];

            if (array_key_exists($key, $this->data)) {
                $this->data[$key] = &$arguments[1];
            }
        }
    }

    public function collectValuesForKeys($keys)
    {
        if (!is_array($keys)) {
            return;
        }

        foreach ($keys as $key) {
            $this->data[$key] = null;
        }
    }

    public function valueByKey($key)
    {
        return $this->data[$key];
    }

    /**
     * Convert string to url path
     *
     * @param string $string - string
     **/
    public function str2path($string = false)
    {
        return $GLOBALS['rlValid']->str2path($string);
    }

    /**
     * Convert to money format
     *
     * @param string $string
     **/
    public function str2money($string = false)
    {
        $len = strlen($string);
        $string = strrev($string);

        if (false !== strpos($string, '.')) {
            $rest = substr($string, 0, strpos($string, '.'));
            $string = substr($string, strpos($string, '.') + 1, $len);
            $len -= strlen($rest) + 1;
            $rest = strrev(substr(strrev($rest), 0, 2)) . ".";
        } elseif ($GLOBALS['config']['show_cents']) {
            $rest = '00.';
        }

        for ($i = 0; $i <= $len; $i++) {
            $val .= $string[$i];
            if ((($i + 1) % 3 == 0) && ($i + 1 < $len)) {
                $val .= $GLOBALS['config']['price_delimiter'];
            }
        }
        return strrev($rest . $val);
    }

    /**
     * blank flange
     **/
    // public function assign_by_ref()
    // {}
    // public function assign()
    // {}
    public function display()
    {}
    public function fetch()
    {}
    public function register_function()
    {}
    public function get_template_vars()
    {}
    public function listingUrl()
    {}
    public function script()
    {}
    public function call()
    {}
    public function redirect()
    {}
}
