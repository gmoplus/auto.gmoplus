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
 *	Copyrights Flynax Classifieds Software | 2023
 *	https://www.flynax.com
 *
 ******************************************************************************/

namespace Autoposter;

use Autoposter\ProviderController;

class AjaxWrapper
{
    /**
     * @var array - Flynax language array
     */
    private $lang;
    
    /**
     * AjaxWrapper constructor.
     */
    public function __construct()
    {
        $this->lang = AutoPosterContainer::getConfig('lang');
    }
    
    /**
     * Sending post to the wall of the provider
     *
     * @param  string $provider   - Provider name
     * @param  int    $listing_id - Postings listing ID
     * @return array  $out        - Ajax response
     */
    public function sendPost2Wall($provider, $listing_id)
    {
        $provider_str = $provider;
        $providerController = new ProviderController($provider);
        $provider = $providerController->getProvider();
        
        if (!method_exists($provider, 'post')) {
            $out['status'] = 'ERROR';
            $out['message'] = $this->lang['ap_sending_method_dosnt_exist'];
            $out['provider'] = $provider_str;
            
            return $out;
        }
    
        if ($provider->post($listing_id)) {
            $out['status'] = 'OK';
            $out['message'] = $this->lang['ap_message_sent'];
            $out['provider'] = $provider_str;
            
            return $out;
        }
        
        $out['status'] = 'ERROR';
        $out['message'] = $this->lang['massage_doesnt_sent'];
        $out['provider'] = $provider_str;
        
        return $out;
    }
}
