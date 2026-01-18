<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: LISTINGPICTUREUPLOADADAPTER.PHP
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
 *  Flynax Classifieds Software 2025 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

namespace Flynax\Api\Http\Controllers\V1;
use Flynax\Classes\ListingPictureUpload;

class ListingPictureUploadAdapter extends ListingPictureUpload
{
    /** @var int */
    private $listingId = 0;

    /** @var int */
    private $imageOrientation = 0;

    /**
     * Get the listing ID
     *
     * @return int
     */
    public function getListingId()
    {
        return $this->listingId;
    }

    /**
     * @return int
     */
    public function getImageOrientation()
    {
        return $this->imageOrientation;
    }

    /**
     * Set value of the listing ID
     *
     * @param int $listingId
     *
     * @return ListingPictureUploadAdapter
     */
    public function setListingId($listingId)
    {
        $this->listingId = $listingId;

        return $this;
    }

    /**
     * @param int $imageOrientation
     *
     * @return ListingPictureUploadAdapter
     */
    public function setImageOrientation($imageOrientation)
    {
        $this->imageOrientation = $imageOrientation;

        return $this;
    }

    /**
     * Upload image from global $_FILES array
     *
     * @return array
     */
    public function uploadFromGlobals()
    {
        if (!$this->listingId) {
            throw new LogicException('Error processing request; "listingId" is wrong');
        }
        if (!$GLOBALS['rlListings']) {
            $GLOBALS['reefless']->loadClass('Listings');
        }

        $GLOBALS['lang']['error_maxFileSize'] = str_replace(
            '{limit}',
            \Flynax\Utils\Util::getMaxFileUploadSize() / (1024 * 1024),
            $GLOBALS['lang']['error_maxFileSize']
        );
                
        $filename = $this->options['param_name'] = 'file';
        $file     = (object) $_FILES[$filename];

        if ($file->type == 'application/octet-stream') {
            $file->type = mime_content_type($_FILES['file']['tmp_name']);
        }
        
        $_FILES[$filename] = [
            'name'     => [$file->name],
            'type'     => [$file->type],
            'tmp_name' => [$file->tmp_name],
            'error'    => [$file->error],
            'size'     => [$file->size],
        ];

        $result = $this->init();
        $result = reset($result[$filename]);

        // $this->rotateImagesIfNecessary($result, $uploader->options);

        if (isset($result['error'])) {
            return [
                'success' => false,
                'error'   => (string) $result['error']
            ];
        }

        return [
            'success' => true,
            'id'      => (int) $result['ID'],
            'image'   => (string) $result['Photo']
        ];
    }


    /**
     * Rotate image
     */
    private function rotateImagesIfNecessary($data, $options)
    {
        if ($this->imageOrientation === 0) {
            return;
        }

        $imageVersions = $options['image_versions'];
        $imageVersions['original'] = array('db_field' => 'Original');

        foreach ($imageVersions as $imageVersion) {
            if (empty($imageURL = $data[$imageVersion['db_field']])) {
                continue;
            }

            $filename = str_replace($options['upload_url'], $options['upload_dir'], $imageURL);

            if (is_file($filename)) {
                $this->rotateImage($filename);
            }
        }
    }

    /**
     * Rotate image
     *
     * return image data
     */
    private function rotateImage($filename)
    {
        $image = @imagecreatefromjpeg($filename);

        switch ($this->imageOrientation) {
            case 3: // Down->180 deg rotation
                $image = @imagerotate($image, 180, 0);
                break;

            case 6: // Left->270 deg CCW
                $image = @imagerotate($image, 270, 0);
                break;

            case 8: // Right->90 deg CW
                $image = @imagerotate($image, 90, 0);
                break;

            default:
                return false;
        }

        $success = imagejpeg($image, $filename);

        // Free up memory (imagedestroy doesn't delete files):
        @imagedestroy($image);

        return $success;
    }
}
