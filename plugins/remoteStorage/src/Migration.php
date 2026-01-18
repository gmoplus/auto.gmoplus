<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: MIGRATION.PHP
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

class Migration
{
    /**
     * @var mixed
     */
    private $rlDb;

    /**
     * @var mixed
     */
    private $config;

    /**
     * @var Media
     */
    private $media;

    /**
     * Limit number of files to migrate per run
     * @since 1.0.1
     * @var int
     */
    public const LIMIT = 10;

    /**
     * Migration constructor
     */
    public function __construct()
    {
        $this->rlDb   = &$GLOBALS['rlDb'];
        $this->config = &$GLOBALS['config'];
        $this->media  = new Media(new Server());
    }

    /**
     * @return int
     */
    public function getCountNotMigratedMedia(): int
    {
        $listingLargeMedia       = $this->getNotMigratedCount('Photo', 'listing_photos');
        $listingThumbnailMedia   = $this->getNotMigratedCount('Thumbnail', 'listing_photos');
        $listingOriginalMedia    = $this->getNotMigratedCount('Original', 'listing_photos');
        $listingThumbnailX2Media = $this->config['thumbnails_x2']
            ? $this->getNotMigratedCount('Thumbnail_x2', 'listing_photos')
            : 0;

        $accountThumbnailPhotos   = $this->getNotMigratedCount('Photo', 'accounts');
        $accountOriginalPhotos    = $this->getNotMigratedCount('Photo_original', 'accounts');
        $accountThumbnailX2Photos = $this->config['thumbnails_x2']
            ? $this->getNotMigratedCount('Photo_x2', 'accounts')
            : 0;

        return $listingLargeMedia
            + $listingThumbnailMedia
            + $listingOriginalMedia
            + $listingThumbnailX2Media
            + $accountThumbnailPhotos
            + $accountOriginalPhotos
            + $accountThumbnailX2Photos;
    }

    /**
     * @param string $column
     * @param string $table
     *
     * @return int
     */
    private function getNotMigratedCount(string $column, string $table): int
    {
        if (!in_array($table, ['accounts', 'listing_photos'])) {
            throw new \InvalidArgumentException('Provided table name is invalid');
        }

        $prefix = ($table === 'accounts' ? Media::ACCOUNTS_DIR : Media::LISTINGS_DIR) . '/';
        $where = "`{$column}` <> ''";

        if ($table === 'listing_photos') {
            $where .= " AND `Original` <> 'youtube'";
        }

        return (int) $this->rlDb->getRow(
            "SELECT COUNT(*) FROM `{db_prefix}{$table}`
             WHERE {$where} AND CONCAT('{$prefix}', `{$column}`) NOT IN (
                 SELECT `Key` FROM `" . RemoteStorage::FILES_TABLE_PRX . "`
             )",
            'COUNT(*)'
        );
    }

    /**
     * @param int|null $limit
     *
     * @return array
     */
    public function getNotMigratedMedia(?int $limit = 0): array
    {
        $media = $this->_getNotMigratedMedia('Photo', 'listing_photos', $limit);
        if ($limit && count($media) === $limit) {
            return $media;
        }

        $media = array_merge($media, $this->_getNotMigratedMedia('Thumbnail', 'listing_photos', $limit));
        if ($limit && count($media) === $limit) {
            return $media;
        }

        if ($this->config['thumbnails_x2']) {
            $media = array_merge($media, $this->_getNotMigratedMedia('Thumbnail_x2', 'listing_photos', $limit));
            if ($limit && count($media) === $limit) {
                return $media;
            }
        }

        $media = array_merge($media, $this->_getNotMigratedMedia('Original', 'listing_photos', $limit));
        if ($limit && count($media) === $limit) {
            return $media;
        }

        $media = array_merge($media, $this->_getNotMigratedMedia('Photo', 'accounts', $limit));
        if ($limit && count($media) === $limit) {
            return $media;
        }

        if ($this->config['thumbnails_x2']) {
            $media = array_merge($media, $this->_getNotMigratedMedia('Photo_x2', 'accounts', $limit));
            if ($limit && count($media) === $limit) {
                return $media;
            }
        }

        return array_merge($media, $this->_getNotMigratedMedia('Photo_original', 'accounts', $limit));
    }

    /**
     * @param string   $column
     * @param string   $table
     * @param int|null $limit
     *
     * @return array
     */
    private function _getNotMigratedMedia(string $column, string $table, ?int $limit = 0): array
    {
        if (!in_array($table, ['accounts', 'listing_photos'])) {
            throw new \InvalidArgumentException('Provided table name is invalid');
        }

        $prefix = ($table === 'accounts' ? Media::ACCOUNTS_DIR : Media::LISTINGS_DIR) . '/';
        $where = "`{$column}` <> ''";

        if ($table === 'listing_photos') {
            $where .= " AND `Original` <> 'youtube'";
        }

        $files = (array) $this->rlDb->getAll(
            "SELECT `{$column}` FROM `{db_prefix}{$table}` WHERE {$where}"
             . " AND CONCAT('{$prefix}', `{$column}`) NOT IN (SELECT `Key` FROM `" . RemoteStorage::FILES_TABLE_PRX . "`)"
             . ($limit ? " LIMIT {$limit}" : '')
        );

        foreach ($files as &$file) {
            $file += ['Entity_type' => $table === 'accounts' ? Media::ACCOUNT_ENTITY_TYPE : Media::LISTING_ENTITY_TYPE];
            $file += ['Column' => $column];
        }

        return $files;
    }

    /**
     * Move local media files to remote storage
     *
     * @param int $limit
     *
     * @return bool
     */
    public function moveFiles(int $limit = self::LIMIT): bool
    {
        if (!($files = $this->getNotMigratedMedia($limit))) {
            return  false;
        }

        foreach ($files as $file) {
            $select   = $file['Entity_type'] === Media::ACCOUNT_ENTITY_TYPE ? 'ID' : 'Listing_ID';
            $table    = $file['Entity_type'] === Media::ACCOUNT_ENTITY_TYPE ? 'accounts' : 'listing_photos';
            $where    = [$file['Column'] => $file[$file['Column']]];
            $entity   = $this->rlDb->fetch([$select], $where, null, 1, $table, 'row');
            $entityID = $entity && $entity[$select] ? (int) $entity[$select] : 0;

            if (!$entityID) {
                continue;
            }

            $media = ['Type' => 'picture', $file['Column'] => $file[$file['Column']]];

            // Miss files which not found on server
            if (!is_file(RL_FILES . $file[$file['Column']])) {
                // Removing data about missing file from DB
                $this->rlDb->updateOne([
                    'fields' => [$file['Column'] => ''],
                    'where'  => [$file['Column'] => $file[$file['Column']]]
                ], $table);

                continue;
            }

            if ($file['Entity_type'] === Media::ACCOUNT_ENTITY_TYPE) {
                $this->media->uploadAccountPhoto($media, $entityID);
            } else {
                $media = [$media];
                $this->media->uploadListingMedia($media, $entityID);
            }
        }

        return true;
    }

    /**
     * Revert files from remote storage to local
     *
     * @since 1.0.1
     *
     * @param int $bucketID ID of the remote storage bucket
     * @param int $limit    Limit of files to revert

     * @return bool Result of the operation
     */
    public function revertFiles(int $bucketID, int $limit = self::LIMIT): bool
    {
        $files = (array) $this->rlDb->getAll(
            "SELECT * FROM `" .  RemoteStorage::FILES_TABLE_PRX . "`
             WHERE `Server_ID` = {$bucketID}
             LIMIT {$limit}"
        );

        if (!$files) {
            return false;
        }

        foreach ($files as $file) {
            switch ($this->media->getEntityType($file['Key'])) {
                case $this->media::LISTING_ENTITY_TYPE:
                    $picture['Thumbnail'] = str_replace($this->media::LISTINGS_DIR . '/', '', $file['Key']);

                    $this->media->createLocalListingMedia($picture);
                    $this->media->removeListingMedia($picture);
                    break;
                case $this->media::ACCOUNT_ENTITY_TYPE:
                    $picture = [
                        'Photo' => str_replace($this->media::ACCOUNTS_DIR . '/', '', $file['Key']),
                        'Type'  => 'picture',
                    ];

                    $this->media->createLocalAccountMedia($picture);
                    $this->media->removeAccountPhoto($picture);
                    break;
            }
        }

        return true;
    }
}
