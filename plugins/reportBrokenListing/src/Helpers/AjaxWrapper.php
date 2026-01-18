<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : AJAXWRAPPER.PHP
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

class AjaxWrapper
{
    /*
     * Ajax success answer code
     */
    const AJAX_SUCCESS = 'OK';
    
    /**
     * Ajax error answer code
     */
    const AJAX_ERROR = 'ERROR';
    
    /**
     * Return succes ajax answer with message (build answer helper)
     *
     * @param  string $message - Ajax success message
     * @param  string $body    - Ajax success body
     * @return mixed           - Prepared data
     */
    public function throwSuccessMessage($message, $body = '')
    {
        return $this->buildAnswer(self::AJAX_SUCCESS, $message, $body);
    }
    
    /**
     * Return success ajax answer only with body (build answer helper)
     *
     * @param  mixed $body    - Ajax success body
     * @return mixed           - Prepared data
     */
    public function throwSuccessBody($body)
    {
        return $this->buildAnswer(self::AJAX_SUCCESS, '', $body);
    }
    
    /**
     * Return error ajax answer with message (build answer helper)
     *
     * @param  string $message - Ajax error message
     * @param  string $body    - Ajax error body
     * @return mixed           - Prepared data
     */
    public function throwErrorMessage($message, $body = '')
    {
        return $this->buildAnswer(self::AJAX_ERROR, $message, $body = '');
    }
    
    /**
     * Return an error ajax answer only with body (build answer helper)
     *
     * @param  string $body    - Ajax success body
     * @return mixed           - Prepared data
     */
    public function throwErrorBody($body = '')
    {
        return $this->buildAnswer(self::AJAX_ERROR, '', $body);
    }
    
    /**
     * Prepare handling ajax answer
     *
     * @param string $type    - Answer type: {ERROR, OK}
     * @param string $message - Answer message
     * @param mixed  $body    - Ajax answer body, that should be handled on the JavaScipt side
     * @return mixed $out     - Prepared ajax answer data
     */
    public function buildAnswer($type, $message, $body)
    {
        $out['status'] = $type;
        if ($message) {
            $out['message'] = $message;
        }
        if (isset($body)) {
            $out['body'] = $body;
        }
        
        return $out;
    }
}
