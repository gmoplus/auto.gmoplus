<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: MEDIA.PHP
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

namespace Flynax\Plugins\RemoteStorage;

use Flynax\Utils\ListingMedia;
use Flynax\Utils\Profile;
use InvalidArgumentException;
use RuntimeException;

class Media
{
    /**
     * Entity type for listing images
     */
    public const LISTINGS_DIR = 'listings';

    /**
     * Entity type for account images
     */
    public const ACCOUNTS_DIR = 'accounts';

    /**
     * Types of listing photos which must be uploaded to remote server
     *
     * @since 1.0.1 - Added 'Main_photo', 'Main_photo_x2' values
     */
    public const LISTING_MEDIA_TYPES = ['Original', 'Photo', 'Thumbnail', 'Thumbnail_x2', 'Main_photo', 'Main_photo_x2'];

    /**
     * Types of account photos which must be uploaded to remote server
     */
    public const ACCOUNT_MEDIA_TYPES = ['Photo_original', 'Photo', 'Photo_x2'];

    /**
     * Listing entity type
     */
    public const LISTING_ENTITY_TYPE = 'listing';

    /**
     * Account entity type
     */
    public const ACCOUNT_ENTITY_TYPE = 'account';

    /**
     * @var array
     */
    protected static $entityTypes = [self::LISTING_ENTITY_TYPE, self::ACCOUNT_ENTITY_TYPE];

    /**
     * Cache file key
     */
    public const CACHE_KEY = 'cache_rs_media_files';

    /**
     * @var array
     */
    private $remoteFiles = [];

    /**
     * @var Server
     */
    private $server;

    /**
     * @var mixed
     */
    private $rlDb;

    /**
     * @var mixed
     */
    private $reefless;

    /**
     * @var mixed
     */
    private $config;

    /**
     * @param Server $server
     */
    public function __construct(Server $server)
    {
        $this->server   = $server;
        $this->rlDb     = &$GLOBALS['rlDb'];
        $this->reefless = &$GLOBALS['reefless'];
        $this->config   = &$GLOBALS['config'];
    }

    /**
     * @param array|null $media      - List with photos of listing/account
     * @param int|null   $entityID   - ID of account/listing
     * @param string     $entityType - Possible values self::LISTING_ENTITY_TYPE, self::ACCOUNT_ENTITY_TYPE
     *
     * @return void
     */
    private function uploadMedia(?array &$media, ?int $entityID, string $entityType): void
    {
        if (!$media
            || !$entityID
            || !in_array($entityType, self::$entityTypes, true)
            || !($server = $this->server->getServerInstance())
        ) {
            return;
        }

        $mediaTypes = $entityType === self::LISTING_ENTITY_TYPE ? self::LISTING_MEDIA_TYPES : self::ACCOUNT_MEDIA_TYPES;
        $dir        = '';

        foreach ($media as &$file) {
            switch ($file['Type']) {
                case 'video':
                case 'picture':
                    try {
                        foreach ($mediaTypes as $photoType) {
                            if ($mediaURL = $file[$photoType]) {
                                $mediaPath = str_replace(RL_FILES_URL, RL_FILES, $mediaURL);
                                if (false === strpos($mediaPath, RL_FILES)) {
                                    $mediaPath = RL_FILES . $mediaPath;
                                }

                                // Miss files which not found on server
                                if (!is_file($mediaPath)) {
                                    continue;
                                }

                                $dir       = dirname($mediaPath);
                                $fileKey   = $entityType === self::LISTING_ENTITY_TYPE ? self::LISTINGS_DIR : self::ACCOUNTS_DIR;
                                $fileKey   .= '/' . str_replace(RL_FILES_URL, '', $mediaURL);
                                $remoteURL = $server->sendFile($fileKey, $mediaPath);

                                if ($remoteURL) {
                                    $file[$photoType] = $remoteURL;

                                    $this->rlDb->insertOne([
                                        'Entity_ID'  => $entityID,
                                        'Server_ID'  => $this->server->serverInfo['ID'],
                                        'Key'        => $fileKey,
                                        'Remote_URL' => $remoteURL,
                                    ], RemoteStorage::FILES_TABLE);

                                    unlink($mediaPath);
                                }
                            }
                        }
                    } catch (RuntimeException $e) {
                        $this->server->mainServerDownHandler($e->getMessage());
                        break 2;
                    }
                    break;
            }
        }

        if ($dir) {
            ListingMedia::removeEmptyDir($dir);
            $this->updateMediaCache();
        }
    }

    /**
     * @param array|null $media
     *
     * @return void
     */
    public function loadListingMedia(?array &$media): void
    {
        foreach ($media as &$file) {
            switch ($file['Type']) {
                case 'picture':
                case 'video':
                    foreach (self::LISTING_MEDIA_TYPES as $photoType) {
                        if ($mediaURL = $file[$photoType]) {
                            if ($this->config['rs_main_server_url'] && 0 === strpos($mediaURL, $this->config['rs_main_server_url'])) {
                                continue;
                            }

                            $remoteMediaKey = self::LISTINGS_DIR . '/' . str_replace([RL_FILES, RL_FILES_URL], '', $mediaURL);

                            if ($this->config['rs_main_server_url']) {
                                $remoteMediaURL = $this->config['rs_main_server_url'] . $remoteMediaKey;
                            } else {
                                $remoteMediaURL = $this->getRemoteFilesData()[$remoteMediaKey];
                            }

                            $remoteFileData = $this->getFileDataByRemoteURL($remoteMediaURL);

                            if ($remoteMediaURL && $remoteFileData) {
                                $file[$photoType] = $remoteMediaURL;
                            }
                        }
                    }
                    break;
            }
        }
    }

    /**
     * @param array|null $picture
     * @param string     $entityType
     *
     * @return void
     */
    private function createLocalMedia(?array $picture, string $entityType): void
    {
        if (!$picture || !in_array($entityType, self::$entityTypes, true)) {
            return;
        }

        $mediaTypes = $entityType === self::LISTING_ENTITY_TYPE ? self::LISTING_MEDIA_TYPES : self::ACCOUNT_MEDIA_TYPES;

        foreach ($mediaTypes as $photoType) {
            if ($mediaPath = $picture[$photoType]) {
                $filePrefix     = $entityType === self::LISTING_ENTITY_TYPE ? self::LISTINGS_DIR . '/' : self::ACCOUNTS_DIR . '/';
                $mediaPath      = str_replace([RL_FILES_URL, RL_FILES], '', $mediaPath);
                $remoteMediaKey = $filePrefix . $mediaPath;

                if ($this->config['rs_main_server_url']) {
                    $remoteMediaURL = $this->config['rs_main_server_url'] . $remoteMediaKey;
                } else {
                    $remoteMediaURL = $this->getRemoteFilesData()[$remoteMediaKey];
                }

                $remoteFileData = $this->getFileDataByRemoteURL($remoteMediaURL);
                $serverID       = (int) ($remoteFileData && $remoteFileData['Server_ID'] ? $remoteFileData['Server_ID'] : 0);

                if (!$serverID || !($server = $this->server->getServerInstance($serverID))) {
                    continue;
                }

                $mediaPath = RL_FILES . $mediaPath;
                $this->reefless->rlMkdir(dirname($mediaPath));
                $server->getFile($remoteMediaKey, $mediaPath);
            }
        }
    }

    /**
     * @param array|null $mediaInfo  - List with photos of listing/account
     * @param string     $entityType - Possible values self::LISTING_ENTITY_TYPE, self::ACCOUNT_ENTITY_TYPE
     *
     * @return void
     */
    private function removeMedia(?array $mediaInfo, string $entityType): void
    {
        if (!$mediaInfo || !in_array($entityType, self::$entityTypes, true)) {
            return;
        }

        $mediaTypes = $entityType === self::LISTING_ENTITY_TYPE ? self::LISTING_MEDIA_TYPES : self::ACCOUNT_MEDIA_TYPES;

        foreach ($mediaTypes as $photoType) {
            if ($mediaKey       = $mediaInfo[$photoType]) {
                $filePrefix     = $entityType === self::LISTING_ENTITY_TYPE ? self::LISTINGS_DIR : self::ACCOUNTS_DIR;
                $remoteMediaKey = $filePrefix . '/' . str_replace([RL_FILES, RL_FILES_URL], '', $mediaKey);

                if ($this->config['rs_main_server_url']) {
                    $remoteMediaURL = $this->config['rs_main_server_url'] . $remoteMediaKey;
                } else {
                    $remoteMediaURL = $this->getRemoteFilesData()[$remoteMediaKey];
                }

                $remoteFileData = $this->getFileDataByRemoteURL($remoteMediaURL);
                $serverID       = (int) ($remoteFileData && $remoteFileData['Server_ID'] ? $remoteFileData['Server_ID'] : 0);

                if (!$remoteFileData || !$serverID || !($server = $this->server->getServerInstance($serverID))) {
                    continue;
                }

                $server->removeFile($remoteMediaKey);
                $this->rlDb->delete(['Key' => $remoteMediaKey], RemoteStorage::FILES_TABLE);
            }
        }

        $this->updateMediaCache();
    }

    /**
     * @return void
     */
    public function updateMediaCache(): void
    {
        $this->resetRemoteFilesList();
        $this->getRemoteFilesData(true);

        if (!$this->config['cache'] || $this->config['rs_main_server_url']) {
            return;
        }

        if (!$GLOBALS['rlCache']) {
            $this->reefless->loadClass('Cache');
        }

        $this->rlDb->outputRowsMap = ['Key', 'Remote_URL'];
        $files = (array) $this->rlDb->fetch('*', null, null, null, RemoteStorage::FILES_TABLE);

        $GLOBALS['rlCache']->set(self::CACHE_KEY, $files);
    }

    /**
     * @param bool|null $fromDB
     *
     * @return array
     */
    public function getRemoteFilesData(?bool $fromDB = false): array
    {
        if ($this->config['rs_main_server_url']) {
            return [];
        }

        if (!$fromDB) {
            if ($this->remoteFiles) {
                return $this->remoteFiles;
            }

            if ($this->config['cache']) {
                if (!$GLOBALS['rlCache']) {
                    $this->reefless->loadClass('Cache');
                }

                if ($dataInCache = $GLOBALS['rlCache']->get(self::CACHE_KEY)) {
                    return $this->remoteFiles = $dataInCache;
                }
            }
        }

        $this->rlDb->outputRowsMap = ['Key', 'Remote_URL'];
        return $this->remoteFiles = $this->rlDb->fetch('*', null, null, null, RemoteStorage::FILES_TABLE);
    }

    /**
     * @return void
     */
    public function resetRemoteFilesList(): void
    {
        $this->remoteFiles = [];
    }

    /**
     * @param array|null $media    - List with media of listing
     * @param int|null   $entityID - ID of listing
     *
     * @return void
     */
    public function uploadListingMedia(?array &$media, ?int $entityID): void
    {
        $this->uploadMedia($media, $entityID, self::LISTING_ENTITY_TYPE);
    }

    /**
     * @param array|null $media    - List with photos of account
     * @param int|null   $entityID - ID of account
     *
     * @return void
     */
    public function uploadAccountPhoto(?array &$media, ?int $entityID): void
    {
        $images = [['Type' => 'picture'] + $media];
        $this->uploadMedia($images, $entityID, self::ACCOUNT_ENTITY_TYPE);
        $media = $images[0];
        unset($media['Type']);
    }

    /**
     * @param array|null $picture
     *
     * @return void
     */
    public function createLocalListingMedia(?array $picture): void
    {
        $this->createLocalMedia($picture, self::LISTING_ENTITY_TYPE);
    }

    /**
     * @param array|null $picture
     *
     * @return void
     */
    public function createLocalAccountMedia(?array $picture): void
    {
        $this->createLocalMedia($picture, self::ACCOUNT_ENTITY_TYPE);
    }

    /**
     * @param array|null $mediaInfo
     *
     * @return void
     */
    public function removeListingMedia(?array $mediaInfo): void
    {
        $this->removeMedia($mediaInfo, self::LISTING_ENTITY_TYPE);
    }

    /**
     * @param int|null $listingID
     *
     * @return void
     */
    public function removeListingMediaByID(?int $listingID): void
    {
        if (!($mediaList = (array) ListingMedia::get($listingID))) {
            // Emulation data of the media info, if not available
            foreach ($this->getRemoteFilesDataByID($listingID, self::LISTING_ENTITY_TYPE) as $remoteFile) {
                $mediaList[] = ['Original' => str_replace(self::LISTINGS_DIR . '/', '', $remoteFile['Key'])];
            }
        }

        foreach ($mediaList as $media) {
            $this->removeListingMedia($media);
        }
    }

    /**
     * @param array|null $mediaInfo
     *
     * @return void
     */
    public function removeAccountPhoto(?array $mediaInfo): void
    {
        $this->removeMedia($mediaInfo, self::ACCOUNT_ENTITY_TYPE);
    }

    /**
     * @param int|null $accountID
     *
     * @return void
     */
    public function removeAccountPhotoByID(?int $accountID): void
    {
        $this->removeAccountPhoto(Profile::getProfilePhotoData($accountID));
    }

    /**
     * @param int    $entityID
     * @param string $entityType
     *
     * @return array
     */
    public function getRemoteFilesDataByID(int $entityID, string $entityType): array
    {
        if (!in_array($entityType, self::$entityTypes, true)) {
            throw new InvalidArgumentException('Provided entity type is invalid');
        }

        $media       = [];
        $prefix      = ($entityType === self::LISTING_ENTITY_TYPE ? self::LISTINGS_DIR : self::ACCOUNTS_DIR) . '/';
        $remoteFiles = $this->rlDb->fetch('*', ['Entity_ID' => $entityID], null, null, RemoteStorage::FILES_TABLE);

        foreach ($remoteFiles as $remoteFile) {
            if (false !== strpos($remoteFile['Key'], $prefix)) {
                $media[] = $remoteFile;
            }
        }

        return $media;
    }

    /**
     * @param string $url
     *
     * @return array
     */
    public function getFileDataByRemoteURL(string $url = ''): array
    {
        if (!$url) {
            return [];
        }

        return (array) $this->rlDb->fetch('*', ['Remote_URL' => $url], null, null, RemoteStorage::FILES_TABLE, 'row');
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public static function getEntityType(string $key): string
    {
        if (false !== strpos($key, self::LISTINGS_DIR . '/')) {
            return self::LISTING_ENTITY_TYPE;
        } else if (false !== strpos($key, self::ACCOUNTS_DIR . '/')) {
            return self::ACCOUNT_ENTITY_TYPE;
        } else {
            return '';
        }
    }

    /**
     * @param string $locallyURL
     *
     * @return string
     */
    public function getFileCloudURL(string $locallyURL = ''): string
    {
        $remoteURL = $locallyURL;
        $this->updateURLsInString($remoteURL);

        if ($remoteURL && $remoteURL !== $locallyURL) {
            $filePath = str_replace(RL_FILES_URL, '', $locallyURL);
            return str_replace($filePath, '', $remoteURL);
        }

        return '';
    }

    /**
     * Replaces the local paths of media files to remote
     *
     * @param string|null $string
     *
     * @return void
     */
    public function updateURLsInString(?string &$string = ''): void
    {
        if (!$string) {
            return;
        }

        $this->_updateURLsInString($string, self::LISTING_ENTITY_TYPE);
        $this->_updateURLsInString($string, self::ACCOUNT_ENTITY_TYPE);
    }

    /**
     * @param string|null $string
     * @param string|null $entityType
     *
     * @return void
     */
    private function _updateURLsInString(?string &$string = '', ?string $entityType = ''): void
    {
        if ($entityType === self::LISTING_ENTITY_TYPE) {
            $pattern = '/' . preg_quote(RL_FILES_URL, '/') . '\d{2}-\d{4}\/ad\d+\/[^.]+\.[a-z|0-9]{3,4}/smi';
        } elseif ($entityType === self::ACCOUNT_ENTITY_TYPE) {
            // New format of account thumbnail URLs
            $pattern = '/' . preg_quote(RL_FILES_URL, '/') . 'account-media\/[^\/]+\/[^.]+\.[a-z]{3,4}';
            // Old format of account thumbnail URLs
            $pattern .= '|' . preg_quote(RL_FILES_URL, '/') . 'account-thumbnail\-[^.]+\.[a-z]{3,4}';
            $pattern .= '/smi';
        } else {
            throw new InvalidArgumentException('Provided entity type is wrong.');
        }

        $remoteFiles = !$this->config['rs_main_server_url'] ? $this->getRemoteFilesData() : [];

        preg_match_all($pattern, $string, $mediaURLs);
        $mediaURLs = $mediaURLs && $mediaURLs[0] ? $mediaURLs[0] : [];

        foreach ($mediaURLs as $mediaURL) {
            $prefix   = $entityType === self::LISTING_ENTITY_TYPE ? self::LISTINGS_DIR : self::ACCOUNTS_DIR;
            $mediaKey = $prefix . '/' . str_replace(RL_FILES_URL, '', $mediaURL);

            if ($this->config['rs_main_server_url']) {
                // Prevent the updating of URLs of media which exists locally (they were not uploaded to storage yet)
                if (file_exists(str_replace(RL_FILES_URL, RL_FILES, $mediaURL))) {
                    continue;
                }

                $string = str_replace($mediaURL, $this->config['rs_main_server_url'] . $mediaKey, $string);
            } else {
                if ($remoteMediaURL = $remoteFiles[$mediaKey]) {
                    $string = str_replace($mediaURL, $remoteMediaURL, $string);
                }
            }
        }
    }
}
