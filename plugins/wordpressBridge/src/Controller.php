<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : CONTROLLER.PHP
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

namespace Flynax\Plugin\WordPressBridge;

use Flynax\Plugin\WordPressBridge\WordPressAPI\Token;

/**
 * Class Controller
 *
 * @since 2.0.0
 *
 * @package Flynax\Plugin\WordPressBridge
 */
class Controller
{
    /**
     * Checking does provided token from FlynaxBridge plugin is valid
     *
     * @param string $token
     *
     * @return bool
     */
    public function isTokenValid($token)
    {
        if (!$token) {
            return false;
        }

        $tokenManager = new Token();
        return $tokenManager->getFlToken() === $token;
    }
}
