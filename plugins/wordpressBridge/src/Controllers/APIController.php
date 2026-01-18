<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : APICONTROLLER.PHP
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

namespace Flynax\Plugin\WordPressBridge\Controllers;

use Flynax\Plugin\WordPressBridge\Controller;
use Flynax\Plugin\WordPressBridge\Request;
use Flynax\Plugin\WordPressBridge\Response;
use Flynax\Plugin\WordPressBridge\WordPressAPI\API;
use Flynax\Plugin\WordPressBridge\WordPressAPI\Token;

/**
 * Class APIController
 *
 * @since 2.0.0
 *
 * @package Flynax\Plugin\WordPressBridge\Controllers
 */
class APIController extends Controller
{
    private $wordPressApi = null;

    /**
     * @var \rlActions
     */
    private $rlActions;

    /**
     * APIController constructor.
     */
    public function __construct()
    {
        $this->wordPressApi = new API();
        $this->rlActions = wbMake('rlActions');
    }


    /**
     * Delete tokens of the bridges from Flynax
     */
    public function deleteTokens()
    {
        $flToken = (string) $_REQUEST['fl_token'];
        if (!$this->isTokenValid($flToken)) {
            Response::error('Invalid token', 500);
            return;
        }

        $tokenManager = new Token();
        $tokenManager->clearAllTokens();

        /** @var \rlConfig $rlConfig */
        $rlConfig = wbMake('rlConfig');

        $rlConfig->setConfig('fl_wp_root', '');
        $rlConfig->setConfig('wp_path', '');
    }

    /**
     * Get listings types and return it as JSON
     */
    public function getListingTypes()
    {
        /** @var \rlListingTypes $rlListingsTypes */
        $rlListingsTypes = wbMake('rlListingTypes');

        $listingTypes = array();

        foreach ($rlListingsTypes->types as $key => $type) {
            $listingTypes[] = array(
                'name' => $type['name'],
                'key' => $type['Key'],
            );
        }

        Response::success(array(
            'listing_types' => $listingTypes,
        ), 200);
    }
}
