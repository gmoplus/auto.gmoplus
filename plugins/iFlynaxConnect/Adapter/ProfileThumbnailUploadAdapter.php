<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : PROFILETHUMBNAILUPLOADADAPTER.PHP
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

use Flynax\Classes\ProfileThumbnailUpload;

/**
 * @since 3.4.0
 */
class ProfileThumbnailUploadAdapter extends ProfileThumbnailUpload
{
    /**
     * Upload image from global $_FILES massive
     * 
     * @return array
     */
    public function uploadFromGlobals()
    {
        $param_name = $this->options['param_name'] = 'profile-image';
        $response = $this->init();

        $result = $response[$param_name][0];

        if (isset($result['error'])) {
            return ['error' => (string) $result['error']];
        }
        return ['image' => strval(RL_FILES_URL . $result['Photo'])];
    }
}
