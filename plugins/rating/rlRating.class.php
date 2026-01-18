<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLRATING.CLASS.PHP
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

class rlRating
{
    /**
    * set rating
    *
    * @package xAjax
    *
    * @param int $id - listing id
    * @param int $stars - stars rating
    *
    **/
    function ajaxRate( $id = false, $stars = false )
    {
        global $_response, $lang, $config, $rlSmarty, $rlDb, $reefless;

        $id = (int)$id;
        $stars = (int)$stars;

        if ( empty($id) || empty($stars) || ($config['rating_prevent_visitor'] && !defined('IS_LOGIN') ) )
        {
            return $_response;
        }

        $hours = date("G");
        $minutes = date("i");
        $seconds = date("s");
        $today_period = ($hours * 3600) + ($minutes * 60) + $seconds;

        $voted = explode(',', $_COOKIE['rating']);

        if ( !in_array( $id, $voted ) )
        {
            $rlDb->query("UPDATE `" .RL_DBPREFIX . "listings` SET `lr_rating_votes` = `lr_rating_votes` + 1, `lr_rating` = `lr_rating` + {$stars}  WHERE `ID` = '{$id}' LIMIT 1");

            /* save vote in cookie */
            $voted[] = $id;
            $value = implode(',', $voted);
            $expire_time = time()+(86400 - $today_period);

            if (method_exists($reefless, 'createCookie')) {
                $reefless->createCookie('rating', $value, $expire_time);
            } else {
                setcookie('rating', $value, $expire_time, $GLOBALS['domain_info']['path'], $GLOBALS['domain_info']['domain']);
            }

            $_response -> script("printMessage('notice', '{$lang['rating_vote_accepted']}');");

            $listing_info = $rlDb->fetch(array('lr_rating_votes', 'lr_rating'), array('ID' => $id), null, 1, 'listings', 'row');

            $rlSmarty -> assign_by_ref('listing_data', $listing_info);
            $rlSmarty -> assign('rating_denied', 'true');

            $tpl = RL_PLUGINS . 'rating' . RL_DS . 'dom.tpl';
            $_response -> assign('listing_rating_dom', 'innerHTML', $rlSmarty -> fetch($tpl, null, null, false));
        }

        return $_response;
    }
}
