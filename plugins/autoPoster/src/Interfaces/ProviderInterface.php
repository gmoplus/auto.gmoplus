<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : PROVIDERINTERFACE.PHP
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

namespace Autoposter\Interfaces;

interface ProviderInterface
{
    /**
     * Post listing to the provider
     *
     * @param int $listing_id
     *
     * @return mixed
     */
    public function post($listing_id);

    /**
     * Delete post from the provider's timeline
     *
     * @since 1.3.0
     *
     * @param int $listing_id
     * @return mixed
     */
    public function deletePost($listing_id);

    /**
     * Get provider token
     * @return mixed
     */
    public function getToken();
}
