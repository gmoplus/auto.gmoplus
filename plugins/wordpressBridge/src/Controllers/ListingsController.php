<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : LISTINGSCONTROLLER.PHP
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

use Flynax\Plugin\WordPressBridge\Response;

/**
 * Class ListingsController
 *
 * @since 2.0.0
 *
 * @package Flynax\Plugin\WordPressBridge\Controllers
 */
class ListingsController
{
    /**
     * Getting all recent listings
     */
    public function getRecent()
    {
        /** @var \rlValid $rlValid */
        $rlValid = wbMake('rlValid');

        $limit = (int) $_REQUEST['limit'];
        $listingType = $rlValid->xSql($_REQUEST['l_type']);

        $result = $GLOBALS['rlListings']->getRecentlyAdded(0, $limit, $listingType);
        $listings = $this->adaptListingsToWordPressFormat($result);

        Response::json($listings);
    }

    /**
     * Getting featured listings
     */
    public function getFeatured()
    {
        /** @var \rlListings $rlListings */
        $rlListings = wbMake('rlListings');
        /** @var \rlValid $rlValid */
        $rlValid = wbMake('rlValid');

        $limit = (int) $_REQUEST['limit'];
        $listingType = $rlValid->xSql($_REQUEST['l_type']);

        $listings = $this->adaptListingsToWordPressFormat(
            $rlListings->getFeatured($listingType, $limit)
        );

        Response::json($listings);
    }

    /**
     * Adapt Flynax generated listings into Flynax-bridge readable format
     *
     * @param array $listings
     *
     * @return array
     */
    private function adaptListingsToWordPressFormat($listings)
    {
        if (!$listings) {
            return array();
        }

        $adaptedListings = array();
        foreach ($listings as $listing) {
            $fields = implode(', ', array_filter(array_column($listing['fields'], 'value')));

            $adaptedListings[$listing['ID']] = array(
                'title' => $listing['listing_title'],
                'url' => $listing['url'],
                'fields' => $fields,
                'img' => $listing['Main_photo'] ? RL_FILES_URL . $listing['Main_photo'] : null,
                'img_x2' => $listing['Main_photo_x2'] ? RL_FILES_URL . $listing['Main_photo_x2'] : null,
            );
        }

        return $adaptedListings;
    }
}
