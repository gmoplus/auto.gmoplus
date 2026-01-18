<?php

namespace Flynax\Components\Image\Uploader;

use Flynax\Classes\ProfileThumbnailUpload;
use Flynax\Utils\Profile;

class Uploader
{
    const TYPE_BEFORE_461 = 1;
    const TYPE_BEFORE_470 = 2;
    const TYPE_FROM_470 = 3;

    /**
     * @var \rlAccount
     */
    private $rlAccount;

    /**
     * @var array
     */
    private $accountInfo;

    /**
     * @var string
     */
    private $currentUploadingType;

    /**
     * @var array - options of the class
     */
    private $options;

    /**
     * @var \rlActions
     */
    private $rlActions;

    /**
     * ProfileThumbnailUploadAdapter constructor.
     */
    public function __construct()
    {
        /** @var \reefless $reefless */
        $reefless = $GLOBALS['reefless'];

        if (!$GLOBALS['rlAccount']) {
            $reefless->loadClass('Account');
        }
        $this->rlAccount = $GLOBALS['rlAccount'];

        if (!isset($GLOBALS['rlActions'])) {
            $reefless->loadClass('Actions');
        }
        $this->rlActions = $GLOBALS['rlActions'];
    }

    /**
     * @return mixed
     */
    public function getCurrentUploadingType()
    {
        return $this->currentUploadingType;
    }

    /**
     * @param mixed $currentUploadingType
     */
    public function setCurrentUploadingType($currentUploadingType)
    {
        $availableValues = array(self::TYPE_BEFORE_461, self::TYPE_BEFORE_470, self::TYPE_FROM_470,);

        if (in_array($currentUploadingType, $availableValues)) {
            $this->currentUploadingType = $currentUploadingType;
        }
    }

    /**
     * Upload image to the provided account
     *
     * @param  string $image       - Image that you want to upload
     * @param  int    $accountID   - To what account should
     * @param  int    $toListingID - To what listing will be applied this image as main
     * @return bool
     */
    public function uploadImageToAccount($image, $accountID, $toListingID = 0)
    {
        global $config;

        if (!$this->currentUploadingType) {
            $this->determinateUploadingType();
        }

        $this->accountInfo = $this->rlAccount->getProfile((int) $accountID);
        $this->options['thumb_width'] = $this->accountInfo['Thumb_width']
            ?: ($config['pg_upload_thumbnail_width'] ?: 110);

        $this->options['thumb_height'] = $this->accountInfo['Thumb_height']
            ?: ($config['pg_upload_thumbnail_height'] ?: 100);

        switch ($this->currentUploadingType) {
            case self::TYPE_BEFORE_461:
                return (bool) $this->_uploadWithoutAccountMediaFolder($image, $accountID, $toListingID);
                break;
            case self::TYPE_BEFORE_470:
                return (bool) $this->_uploadWithAccountMediaFolder($image, $accountID, $toListingID);
                break;
            case self::TYPE_FROM_470:
                return (bool) $this->_uploadWithSeoAccountMediaFolder($image, $accountID);
                break;
        }

        return false;
    }

    public function determinateUploadingType()
    {
        $this->setCurrentUploadingType(self::TYPE_BEFORE_461);

        if (method_exists(ProfileThumbnailUpload::class, 'buildName')) {
            $this->setCurrentUploadingType(self::TYPE_BEFORE_470);

            if (method_exists(Profile::class, 'updateData')) {
                $this->setCurrentUploadingType(self::TYPE_FROM_470);
            }
        }
    }

    /**
     * Upload Account thumbnail to account media folder
     * TODO: Remove this method when plugin compatible will be >= 4.6.1
     *
     * @param  string $image     - Uploading Image
     * @param  string $accountID - To what account first image will be uploaded
     * @return bool              - Does uploading process successful
     */
    public function _uploadWithoutAccountMediaFolder($image, $accountID, $toListingID)
    {
        if ($this->accountInfo['Photo'] && $toListingID) {
            $this->setMainListingImage($toListingID, $this->accountInfo['Photo']);
            return true;
        }

        if ($newFiles = $this->_uploadProfileImage($image, $this->accountInfo)) {
            $this->setMainListingImage($toListingID, $newFiles['thumbnail']);
            $this->setProfileImage($accountID, $newFiles['original'], $newFiles['thumbnail']);
            return true;
        }

        return false;
    }

    /**
     * Upload Account thumbnail to account media folder
     * TODO: Remove this method when plugin compatible will be >= 4.7.0
     *
     * @param  string $image     - Uploading Image
     * @param  string $accountID - To what account first image will be uploaded
     * @return bool              - Does uploading process successful
     */
    public function _uploadWithAccountMediaFolder($image, $accountID, $toListingID)
    {
        return $this->_uploadWithoutAccountMediaFolder($image, $accountID, $toListingID);
    }

    /**
     * Upload Account thumbnail to the Seo-friendly path
     *
     * @param  string $image     - Uploading Image
     * @param  string $accountID - To what account first image will be uploaded
     * @return bool              - Does uploading process successful
     */
    public function _uploadWithSeoAccountMediaFolder($image, $accountID)
    {
        $accountInfo = $GLOBALS['rlAccount']->getProfile((int)$accountID);
        $uploadManager = new ProfileThumbnailUpload($accountInfo);
        $dirName = $uploadManager->buildName($accountID);
        $fullDir = RL_FILES . $dirName;

        if (!is_dir($fullDir)) {
            $GLOBALS['reefless']->rlMkdir($fullDir);
        }

        $fileInfo = getimagesize($image);
        $extenstion = end(explode('/', $fileInfo['mime']));

        $rand = $uploadManager->options['rand'];
        $newFileName = sprintf('original-%s.%s', $rand, $extenstion);
        $photoFileName = sprintf('photo-%s.%s', $rand, $extenstion);
        $streamContext = stream_context_create(array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
            ),
        ));

        if (copy($image, $fullDir . $newFileName, $streamContext)) {
            $newAccountData['Photo_original'] = $dirName . $newFileName;

            copy($image, $fullDir . $photoFileName, $streamContext);

            $newAccountData['Photo'] = $dirName . $photoFileName;

            if ((bool) $GLOBALS['config']['thumbnails_x2']) {
                $newAccountData['Photo_x2'] = $dirName . $photoFileName;
            }

            Profile::updateData($accountID, $newAccountData);

            return (bool) Profile::cropThumbnail(
                array('width' => $this->options['thumb_width'], 'height' => $this->options['thumb_height']),
                $this->accountInfo
            );
        }

        return false;
    }

    /**
     * Upload provided image to the Flynax (uploading profile image imitation)
     *
     * @param  string $file          - Uploading image URL or Path
     * @param  array  $toProfileInfo - Account Info to which will be photo upload
     * @return array                 - Uploaded images info | Empty array if something went wrong
     */
    private function _uploadProfileImage($file = '', $toProfileInfo = array())
    {
        global $config;

        if (!$file || !$toProfileInfo) {
            return array();
        }

        $fileInfo = getimagesize($file);
        $extension = end(explode('/', $fileInfo['mime']));

        $profileImages = array();

        switch ($this->currentUploadingType) {
            case self::TYPE_BEFORE_461:
                $dirName = '';
                $fullDirPath = RL_FILES;
                $uniqFileName = $this->_buildDirName((int) $toProfileInfo['ID'], $toProfileInfo['Full_name']);
                $original = sprintf('account-original-%s.%s', $uniqFileName, $extension);
                $thumbail = sprintf('account-thumbnail-%s.%s', $uniqFileName, $extension);
                break;
            case self::TYPE_BEFORE_470:
                $mediaDirName = 'account-media';
                $dirName = sprintf(
                    '%s/%s/',
                    $mediaDirName,
                    $this->_buildDirName((int) $toProfileInfo['ID'], $toProfileInfo['Full_name'])
                );
                $fileName = $this->_buildDirName((int) $toProfileInfo['ID'], $toProfileInfo['Full_name']);

                $fullDirPath = RL_FILES . $dirName;
                if (!is_dir($dirName)) {
                    $GLOBALS['reefless']->rlMkdir($fullDirPath);
                    $GLOBALS['reefless']->rlChmod($fullDirPath);
                }
                $original = sprintf('%s_original.%s', $fileName, $extension);
                $thumbail = sprintf('%s_thumbnail.%s', $fileName, $extension);
                break;
        }

        $streamContext = stream_context_create(array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
            ),
        ));

        $originalPath = $fullDirPath . $original;
        $thumbailPath = $fullDirPath . $thumbail;

        if (copy($file, $originalPath, $streamContext)) {
            if (!$GLOBALS['rlCrop']) {
                $GLOBALS['reefless']->loadClass('Crop');
                $GLOBALS['reefless']->loadClass('Resize');
            }

            $GLOBALS['rlCrop']->loadImage($originalPath);
            $GLOBALS['rlCrop']->cropBySize(
                $this->options['thumb_width'],
                $this->options['thumb_height'],
                ccCENTRE
            );
            $GLOBALS['rlCrop']->saveImage($thumbailPath, $config['img_quality']);
            $GLOBALS['rlCrop']->flushImages();

            $profileImages = array(
                'thumbnail' => $dirName . $thumbail,
                'original' => $dirName . $original,
            );
        }

        $GLOBALS['rlResize']->resize(
            $thumbailPath,
            $thumbailPath,
            'C',
            array(
                $this->options['thumb_width'],
                $this->options['thumb_height'],
            ),
            true,
            false
        );

        return $profileImages;
    }

    /**
     * Build directory name of the account profile image
     *
     * @param int    $id       - Account ID
     * @param string $fullName - Account Full name
     * @return string
     */
    public function _buildDirName($id = 0, $fullName = '')
    {
        if (!$id && !$fullName) {
            return '';
        }

        if (!$fullName) {
            $fullName = $this->accountInfo['Full_name'];
        }

        return $GLOBALS['rlValid']->str2path($fullName) . '-' . mt_rand();
    }


    /**
     * Setting main image of the listing
     *
     * @param int    $listingID
     * @param string $photo - Photo which you want attach to the listing
     * @return bool             - Does setting main image successful
     */
    public function setMainListingImage($listingID = 0, $photo = '')
    {
        if (!$photo || !$listingID) {
            return false;
        }

        $update = array(
            'fields' => array(
                'Main_photo' => $photo,
            ),
            'where' => array(
                'ID' => $listingID,
            ),
        );

        return (bool) $this->rlActions->updateOne($update, 'listings');
    }

    /**
     * Setting profile image
     *
     * @param int    $profileID - Profile to which you want to upload image
     * @param string $original  - Original image path
     * @param string $thumbnail - Thumbnail image path
     * @return bool              - Does setting profile image successful
     */
    private function setProfileImage($profileID = 0, $original = '', $thumbnail = '')
    {
        if (!$profileID) {
            return false;
        }

        $update = array(
            'fields' => array(
                'Photo' => $thumbnail,
                'Photo_original' => $original,
            ),
            'where' => array(
                'ID' => $profileID,
            ),
        );

        return (bool) $this->rlActions->updateOne($update, 'accounts');
    }
}
