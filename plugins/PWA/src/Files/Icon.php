<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : ICON.PHP
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

namespace Flynax\Plugins\PWA\Files;

use Flynax\Plugins\PWA\Config;
use Flynax\Utils\Valid;
use Imagecow\Image;

/**
 * Class Icon
 * @package Flynax\Plugins\PWA\Files
 */
class Icon
{
    private $errors = [];
    private $imagePath;

    public function getErrors()
    {
        return $this->errors;
    }

    public function setImage($path)
    {
        $this->imagePath = $path;

        return $this;
    }

    public function save($fileName = false)
    {
        $iconExist = $GLOBALS['rlConfig']->getConfig('pwa_icon');
        $filePath = PWA_ROOT . 'files/' . $iconExist;
        if ($iconExist && pathinfo($filePath, PATHINFO_EXTENSION)) {
            unlink($filePath);
        }

        $res = move_uploaded_file($this->imagePath, PWA_FILES_PATH . $fileName);
        return $res ? $fileName : false;
    }

    public function validate($rules = [])
    {
        $width = $height = 512;
        if (isset($rules['resolution'])) {
            $checkingResolution = $rules['resolution'];
            $width = $checkingResolution[0];
            $height = $checkingResolution[1];
        }

        $this->isImage('png');
        $this->isNeededSize($width, $height);

        return $this->errors;
    }

    /**
     * Checking does provided file is image
     * @param bool $onlyPng
     */
    private function isImage($onlyPng = false)
    {
        $imageInfo = exif_imagetype($this->imagePath);
        if (!$imageInfo) {
            $this->errors[] = $GLOBALS['lang']['pwa_missing_image'];
            return;
        }

        $isPng = $imageInfo === IMAGETYPE_PNG;
        if ($onlyPng && !$isPng) {
            $this->errors[] = $GLOBALS['lang']['pwa_image_png_notice'];
        }
    }

    private function isNeededSize($width, $height = 0)
    {
        $width = (int) $width;
        $height = $height ?: $width;

        if (!$width || !$height) {
            return false;
        }

        list($imWidth, $imHeight) = getimagesize($this->imagePath);

        if ($imWidth < $width || $imHeight < $height) {
            $this->errors[] = sprintf($GLOBALS['lang']['pwa_image_size_notice'], $width, $height);
        }

        return true;
    }

    public static function getIconsFromDb()
    {
        $sql = "SELECT * FROM `{db_prefix}pwa_images`";
        return $GLOBALS['rlDb']->getAll($sql, 'Size');
    }

    public static function getImages($type = '')
    {
        $sql = "SELECT * FROM `" . RL_DBPREFIX . "pwa_images` ";
        if ($type) {
            $sql .= "WHERE `Type` = '{$type}'";
        }
        $result = $GLOBALS['rlDb']->getAll($sql);

        $images = [];
        foreach ($result as $image) {
            $images[$image['Size']] = $image['Image'];
        }

        return $images;
    }

    /**
     * Resize image by provided resolutions
     *
     * @since 1.1.0 - Added $compress parameter
     *
     * @param array     $resolutions - List of necessary resolutions
     * @param $filePath
     * @param $compress              - Set true if you want decrease the size of PNG image. Alpha channel will be lost
     *
     * @return bool|array
     */
    public function resizeTo($resolutions, $filePath = '', $compress = false)
    {
        $filePath    = $filePath ?: $this->imagePath;
        $resolutions = (array) $resolutions;

        if (empty($resolutions)) {
            return false;
        }

        $pathInfo = pathinfo($filePath);
        $fileName = $pathInfo['filename'];
        $newFiles = [];
        $newFiles['original'] = $pathInfo['basename'];

        foreach ($resolutions as $resolution) {
            $width                          = $resolution[0];
            $height                         = $resolution[1];
            $newFileName                    = sprintf('%s-%s-%s.png', $fileName, $width, $height);
            $newFiles["{$width}x{$height}"] = $newFileName;
            $newFilePath                    = PWA_FILES_PATH . $newFileName;
            $image                          = Image::fromFile($filePath);

            if ($compress) {
                $image->format('jpg')->resizeCrop($width, $height)->save($newFilePath);
                Image::fromFile($newFilePath)->format('png')->save();
            } else {
                $image->resize($width, $height)->save($newFilePath);
            }
        }

        return $newFiles;
    }

    /**
     * Get url of icon
     *
     * @param string $size - Necessary size of image
     *                     - Value can be: 512x512 | 192x192 | 180x180 | 96x96 | 32x32 | 16x16
     *
     * @return bool|string
     */
    public static function getAppUrlIcon($size = '96x96')
    {
        $size = Valid::escape($size);
        $icon = false;

        if (!$size || !in_array($size, ['512x512', '192x192', '180x180', '96x96', '32x32', '16x16'])) {
            return $icon;
        }

        if (is_file($icon = PWA_FILES_PATH . Icon::getIconsFromDb()[$size]['Image'])) {
            $icon = str_replace(RL_ROOT, RL_URL_HOME, $icon);
        }

        return $icon;
    }
}
