<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : ROUTES.PHP
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
/**
 * @since 2.0.0
 */
$route->addGroup('/api/v1', function (\FastRoute\RouteCollector $route) {
    $route->get('/listings', 'ListingsController@index');
    $route->get('/listings/recent', 'ListingsController@getRecent');
    $route->get('/listings/featured', 'ListingsController@getFeatured');
    $route->get('/post/update-cache', 'BlocksController@updateBlocksCache');
    $route->get('/flynax-bridge-uninstall', 'APIController@deleteTokens');
    $route->get('/listing-types', 'APIController@getListingTypes');
    $route->get('/account/register', 'AccountController@register');
    $route->get('/account/delete', 'AccountController@delete');
    $route->get('/account/update', 'AccountController@update');
    $route->get('/account/validate', 'AccountController@validate');
    $route->get('/account/change-password', 'AccountController@changePassword');
});
