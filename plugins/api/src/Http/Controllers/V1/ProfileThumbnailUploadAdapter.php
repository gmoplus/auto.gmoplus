<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: CREATESAPPLICATIONTRAIT.PHP
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2024 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

namespace Flynax\Api\Http\Controllers\V1;
use Flynax\Classes\ProfileThumbnailUpload;

/**
 * @since 4.0.0
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
        $param_name = $this->options['param_name'] = 'image';
        $response = $this->init();

        $result = $response[$param_name][0];

        if (isset($result['error'])) {
            return ['error' => (string) $result['error']];
        }
        else if (!$result) {
            return ['error' => (string) $GLOBALS['lang']['error_request_api']];
        }
        return ['photo' => strval(RL_FILES_URL . $result['Photo'])];
    }
}
