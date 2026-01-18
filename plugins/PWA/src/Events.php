<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : EVENTS.PHP
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

namespace Flynax\Plugins\PWA;

class Events
{
    /**
     * @var \rlDb
     */
    private $rlDb;

    /**
     * Events constructor.
     */
    public function __construct()
    {
        $this->rlDb = $GLOBALS['rlDb'];
    }

    /**
     * After image crop event
     *
     * @param array  $newCroppedImages
     * @param string $type
     */
    public static function afterImageCrop($newCroppedImages = [], $type = 'icon')
    {
        $newCroppedImages = (array) $newCroppedImages;
        if (!$newCroppedImages) {
            return;
        }

        $self = new self();
        foreach ($newCroppedImages as $key => $image) {
            $newEntry = [
                'Image' => $image,
                'Size' => $key,
                'Type' => $type,
            ];

            $self->rlDb->insertOne($newEntry, 'pwa_images');
        }
    }
}
