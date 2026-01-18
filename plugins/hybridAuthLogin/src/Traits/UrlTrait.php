<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : URLTRAIT.PHP
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

namespace Flynax\Plugins\HybridAuth\Traits;

/**
 * Trait UrlTrait
 * @package Flynax\Plugins\HybridAuth\Traits
 */
trait UrlTrait
{
    /**
     * Getting Redirect handler link of the provider
     *
     * @param  string $provider
     * @return string
     */
    public function getRedirectURLToTheProvider($provider)
    {
        if (!$provider) {
            return '';
        }

        return sprintf('%shybridAuthLogin/%s', RL_PLUGINS_URL, $provider);
    }
}
