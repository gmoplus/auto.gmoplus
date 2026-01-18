<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLREMOTESTORAGE.CLASS.PHP
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

use Flynax\Abstracts\AbstractPlugin;
use Flynax\Interfaces\PluginInterface;
use Flynax\Plugins\RemoteStorage\Media;
use Flynax\Plugins\RemoteStorage\Migration;
use Flynax\Plugins\RemoteStorage\RemoteStorage;
use Flynax\Plugins\RemoteStorage\Server;
use Flynax\Utils\ListingMedia;
use Flynax\Utils\Profile;
use Flynax\Utils\Valid;

require __DIR__ . '/vendor/autoload.php';

/**
 * General RemoteStorage class
 */
class rlRemoteStorage extends AbstractPlugin implements PluginInterface
{
    /**
     * @var RemoteStorage
     */
    protected $plugin;

    /**
     * @var \$rlDb
     */
    protected $rlDb;

    /**
     * @var \$reefless
     */
    protected $reefless;

    /**
     * @var \$rlLang
     */
    protected $rlLang;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var array
     */
    protected $lang = [];

    /**
     * @var array
     */
    protected $pageInfo = [];

    /**
     * @var array
     */
    protected $accountInfo = [];

    /**
     * @var Server
     */
    protected $server;

    /**
     * @var Media
     */
    public $media;

    /**
     * @var string
     */
    private $tempOriginImagePath = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->plugin      = new RemoteStorage();
        $this->rlDb        = &$GLOBALS['rlDb'];
        $this->reefless    = &$GLOBALS['reefless'];
        $this->rlLang      = &$GLOBALS['rlLang'];
        $this->config      = &$GLOBALS['config'];
        $this->lang        = &$GLOBALS['lang'];
        $this->pageInfo    = &$GLOBALS['page_info'];
        $this->accountInfo = &$GLOBALS['account_info'];
        $this->server      = new Server();
        $this->media       = new Media($this->server);
    }

    /**
     * @hook ajaxRequest
     */
    public function hookAjaxRequest(&$out = null, ?string $mode = null): void
    {
        if (!$this->isPluginConfigured()) {
            return;
        }

        switch ($mode) {
            case 'pictureUpload':
                if (isset($out['files'])) {
                    $this->media->uploadListingMedia($out['files'], (int) $_REQUEST['listing_id']);
                } else if (isset($out['results'])) {
                    $this->media->loadListingMedia($out['results']);
                }
                break;
            case 'pictureRotate':
            case 'pictureCrop':
                if (isset($out['results'])) {
                    $images = [['Type' => 'picture'] + $out['results']];
                    $this->media->loadListingMedia($images);
                    $out['results'] = reset($images);
                }
                break;
            case 'profilePictureUpload':
                if (isset($out['thumbnail'][0])) {
                    $this->media->uploadAccountPhoto($out['thumbnail'][0], (int) $_SESSION['account']['ID']);
                }
                break;

            case 'profileThumbnailCrop':
                if (isset($out['results'])) {
                    $this->media->uploadAccountPhoto($out['results'], (int) $_SESSION['account']['ID']);

                    // Remove unnecessary original photo after cropping process
                    if ($this->tempOriginImagePath) {
                        unlink(RL_FILES . $this->tempOriginImagePath);
                        ListingMedia::removeEmptyDir(dirname(RL_FILES . $this->tempOriginImagePath));
                        $this->tempOriginImagePath = '';
                    }
                }
                break;
            case 'rsUpdateRemoteUrlsInString':
                $string = (string) $_REQUEST['string'] ?: '';
                $this->media->updateURLsInString($string);
                $out = ['status' => 'OK', 'string' => $string];
                break;
            case 'photo':
                // Fix problem with wrong URLs of images in templates with featured gallery on the home page
                if ($out && !file_exists($out)) {
                    $this->media->updateURLsInString($out);
                }
                break;
            case 'getListingPhotos':
                /**
                 * Update URLs of images in rainbow templates (in thumbnail slider preview)
                 * Rewrite the output latest, because template maybe selected after plugin installation
                 *
                 * @todo - Remove it when compatibility will be > 4.9.1
                 *       - Because this case will have already full and correct URLs from ListingMedia::get()
                 */
                $pluginClass = $GLOBALS['rlRemoteStorage'];
                register_shutdown_function(static function() use ($pluginClass) {
                    if ($GLOBALS['out']['data']) {
                        foreach ($GLOBALS['out']['data'] as &$image) {
                            foreach (Media::LISTING_MEDIA_TYPES as $mediaType) {
                                if ($image[$mediaType]) {
                                    if (false === strpos($image[$mediaType], RL_FILES_URL)
                                        && !Valid::isURL($image[$mediaType])
                                    ) {
                                        $image[$mediaType] = RL_FILES_URL . $image[$mediaType];
                                    }

                                    $pluginClass->media->updateURLsInString($image[$mediaType]);
                                }
                            }
                        }

                        ob_end_clean();
                        echo json_encode($GLOBALS['out']);
                    }
                });
                break;
        }
    }

    /**
     * @hook requestAjaxBeforeSwitchCase
     */
    public function hookRequestAjaxBeforeSwitchCase($mode, &$item): void
    {
        if (!$this->isPluginConfigured()) {
            return;
        }

        switch ($mode) {
            case 'profileThumbnailCrop':
                if ($this->accountInfo) {
                    $picture = [
                        'Photo_original' => $this->accountInfo['Photo_original'] ?: $this->accountInfo['Photo'],
                        'Type'           => 'picture',
                    ];

                    $this->media->createLocalAccountMedia($picture);
                    $this->tempOriginImagePath = $picture['Photo_original'];

                    $images = Profile::getProfilePhotoData($this->accountInfo['ID']);
                    unset($images['Photo_original']);
                    $this->media->removeAccountPhoto($images);
                }
                break;

            case 'photo':
                // Fix problem with wrong URLs of images in templates with featured gallery on the home page
                if (Valid::isURL($item)) {
                    preg_match('/\/' . $this->media::LISTINGS_DIR . '\/([^.]+.[a-z|0-9]{3,4})/smi', $item, $matches);

                    if ($matches && $matches[1]) {
                        $item = $matches[1];
                    }
                }
                break;
            case 'getListingPhotos':
                /**
                 * Fix problem with URLs of images in rainbow templates (in thumbnail slider preview)
                 * The output will be rewritten later in "ajaxRequest" hook
                 */
                ob_start();
                break;
        }
    }

    /**
     * @hook listingMediaDeletePicture
     */
    public function hookListingMediaDeletePicture($mediaInfo): void
    {
        if (!$this->isPluginConfigured()) {
            return;
        }

        $this->media->removeListingMedia($mediaInfo);
    }

    /**
     * @hook phpAjaxDelProfileThumbnailBeforeUpdate
     */
    public function hookPhpAjaxDelProfileThumbnailBeforeUpdate($update): void
    {
        if (!($accountID = (int) $update['where']['ID']) || !$this->isPluginConfigured()) {
            return;
        }

        $this->media->removeAccountPhotoByID($accountID);
    }

    /**
     * @hook smartyFetchHook
     */
    public function hookSmartyFetchHook(&$fileContent, $fileName): void
    {
        if (!$this->isPluginConfigured()) {
            return;
        }

        // Fix problem with the updating account thumbnail in uploading process
        if (false !== strpos($fileContent, ".attr('src', rlConfig['files_url'] + response.Photo)")) {
            // Fix problems with replacing local URLs of files after loading
            $fileContent = str_replace(
                [
                    ".attr('src', rlConfig['files_url'] + response.Photo)",
                    ".attr('data-source', rlConfig['files_url'] + response.Photo_original);",
                    "\$img.attr('srcset', rlConfig['files_url'] + response.Photo_x2 + ' 2x');",
                    "\$img.attr('src', rlConfig['files_url'] + response.results.Photo);",
                    "rlConfig['files_url'] + response.results.Photo_x2 + ' 2x'",
                ],
                [
                    ".attr('src', response.Photo)",
                    ".attr('data-source', response.Photo_original);",
                    "\$img.attr('srcset', response.Photo_x2 + ' 2x');",
                    "\$img.attr('src', response.results.Photo);",
                    "response.results.Photo_x2 + ' 2x'",
                ],
                $fileContent
            );
        }

        // Fix problems with CORS blocking request to image from the crop module
        if (false !== strpos($fileContent, "aspectRatio: aspectRatio,")) {
            $fileContent = str_replace(
                "aspectRatio: aspectRatio,",
                "aspectRatio: aspectRatio, checkCrossOrigin: false, checkOrientation: false,",
                $fileContent
            );
        }

        // Fix problem with updating URLs in thumbnail listing preview slider (rainbow templates)
        if (false !== strpos($fileContent, "var src = rlConfig['files_url'] + response.data[i].Thumbnail;")) {
            $fileContent = str_replace(
                "var src = rlConfig['files_url'] + response.data[i].Thumbnail;",
                "var src = response.data[i].Thumbnail;",
                $fileContent
            );
        }

        // Miss compiled files which cannot have listing/account images
        $templatePath  = RL_ROOT . 'templates/' . $this->config['template'] . '/';
        $excludedFiles = [
            $templatePath . 'tpl/footer.tpl',
        ];
        if (in_array($fileName, $excludedFiles, true)) {
            return;
        }

        // Miss compiled files which doesn't have URLs to local files directory
        if (false === strpos($fileContent, RL_FILES_URL)) {
            return;
        }

        // Replace local URLs of media files to remote in content
        $this->media->updateURLsInString($fileContent);
    }

    /**
     * @hook listingMediaTmpRotate
     *
     * @param $mediaID
     * @param $picture
     * @param $sourcePicture
     *
     * @return void
     */
    public function hookListingMediaTmpRotate($mediaID, $picture, &$sourcePicture): void
    {
        if (!$this->isPluginConfigured()) {
            return;
        }

        $originPicture = $picture;
        $originPicture['Original'] = $originPicture['Original'] ? RL_FILES_URL . $originPicture['Original'] : '';
        $originPicture['Type'] = 'picture';
        $images = [$originPicture];

        $this->media->loadListingMedia($images);

        $remotePicture = reset($images);

        if ($originPicture['Original'] !== $remotePicture['Original']) {
            $sourcePicture = $remotePicture['Original'];
        }
    }

    /**
     * @param      $picture
     * @param bool $saveOriginal
     *
     * @return void
     */
    private function downloadListingImages($picture, bool $saveOriginal = true): void
    {
        if (!$this->isPluginConfigured()) {
            return;
        }

        // Prevent downloading same file if it's already exists
        if ($saveOriginal && $picture['Original'] && is_file(RL_FILES . $picture['Original'])) {
            return;
        }

        $this->media->createLocalListingMedia($picture);

        if ($saveOriginal) {
            // Prevent the removing original image from remote server, it wouldn't be changed after cropping
            $this->tempOriginImagePath = $picture['Original'];
            unset($picture['Original']);
        }

        $this->media->removeListingMedia($picture);
    }

    /**
     * Copy remote image locally to rotate it
     *
     * @hook listingMediaUpdateCropData
     *
     * @param $picture
     *
     * @return void
     */
    public function hookListingMediaUpdateCropData($picture): void
    {
        $this->downloadListingImages($picture);
    }

    /**
     * Copy remote image locally to rotate it
     *
     * @hook phpListingMediaUpdatePictureTop
     *
     * @param $picture
     *
     * @return void
     */
    public function hookPhpListingMediaUpdatePictureTop($picture): void
    {
        $this->downloadListingImages($picture);
    }

    /**
     * Download listing images locally to update names in done step of add listing process
     *
     * @hook addListingBeforeInit
     *
     * @param $addListing
     *
     * @return void
     */
    public function hookAddListingBeforeInit($addListing): void
    {
        if (!$this->isPluginConfigured()) {
            return;
        }

        if (!$this->config['add_listing_single_step'] || $GLOBALS['get_step'] !== 'done' || !$addListing->listingID) {
            return;
        }


        if ($mediaList = (array) ListingMedia::get($addListing->listingID)) {
            foreach ($mediaList as $media) {
                if ($media['Type'] === 'picture') {
                    $this->downloadListingImages($media, false);
                }
            }
        }
    }

    /**
     * Upload renamed listing images to remote storage
     *
     * @hook addListingBottom
     *
     * @param $addListing
     *
     * @return void
     */
    public function hookAddListingBottom($addListing): void
    {
        if (!$this->isPluginConfigured()) {
            return;
        }

        if (!$this->config['add_listing_single_step'] || $GLOBALS['get_step'] !== 'done' || !$addListing->listingID) {
            return;
        }

        if ($mediaList = (array) ListingMedia::get($addListing->listingID)) {
            foreach ($mediaList as $media) {
                if ($media['Type'] === 'picture') {
                    $images = [$media];
                    $this->media->uploadListingMedia($images, (int) $addListing->listingID);
                }
            }
        }
    }

    /**
     * @hook phpListingMediaUpdatePictureAfterResize
     *
     * @param $update
     *
     * @return void
     */
    public function hookPhpListingMediaUpdatePictureAfterResize($update): void
    {
        if (!is_array($update['fields']) || !$this->isPluginConfigured()) {
            return;
        }

        $images = [$update['fields'] + ['Type' => 'picture']];
        $this->media->uploadListingMedia(
            $images,
            (int) ($_REQUEST['listing_id'] ?: $this->rlDb->getOne('Listing_ID', "`ID` = {$update['where']['ID']}", 'listing_photos'))
        );

        // Remove original photo which leaves after cropping/rotation
        if ($this->tempOriginImagePath) {
            unlink(RL_FILES . $this->tempOriginImagePath);
            ListingMedia::removeEmptyDir(dirname(RL_FILES . $this->tempOriginImagePath));
            $this->tempOriginImagePath = '';
        }
    }

    /**
     * Download account thumbnails before refreshing of them
     * @hook phpGetProfileData
     */
    public function hookPhpGetProfileData($id, $columns): void
    {
        if (!($_REQUEST['item'] === 'rebuildAccountImages') || !$this->isPluginConfigured()) {
            return;
        }

        $account = $this->rlDb->fetch($columns, ['ID' => $id], null, 1, 'accounts', 'row');

        if (!$account['Photo_original'] && !$account['Photo']) {
            return;
        }

        $picture = [
            'Photo_original' => $account['Photo_original'] ?: $account['Photo'],
            'Type'           => 'picture',
        ];

        $this->media->createLocalAccountMedia($picture);
        $this->tempOriginImagePath = $picture['Photo_original'];

        unset($account['Photo_original']);
        $this->media->removeAccountPhoto($account);
    }

    /**
     * @hook phpListingsAjaxDeleteListing
     */
    public function hookPhpListingsAjaxDeleteListing($info): void
    {
        if ($this->config['trash'] || !($listingID = (int) $info['ID']) || !$this->isPluginConfigured()) {
            return;
        }

        $this->media->removeListingMediaByID($listingID);
    }

    /**
     * @hook phpDeleteAccountDetails
     */
    public function hookPhpDeleteAccountDetails($accountID): void
    {
        if (!$this->isPluginConfigured()) {
            return;
        }

        $this->media->removeAccountPhotoByID((int) $accountID);
    }

    /**
     * Delete account thumbnail if trash box is disabled only
     *
     * @hook deleteAccountSetItems
     */
    public function hookDeleteAccountSetItems($accountID): void
    {
        if ($this->config['trash'] || !$this->isPluginConfigured()) {
            return;
        }

        $this->media->removeAccountPhotoByID((int) $accountID);
    }

    /**
     * @hook phpDeleteListingData
     */
    public function hookPhpDeleteListingData($listingID): void
    {
        if (!$this->isPluginConfigured()) {
            return;
        }

        $this->media->removeListingMediaByID((int) $listingID);
    }

    /**
     * @hook phpUploadBeforeSaveData
     */
    public function hookPhpUploadBeforeSaveData($picture, $dirName, &$file): void
    {
        /*
         * Upload listing images from the old uploader in ADMIN side only
         * @todo - Remove it when admin side will use new frontend uploader
         */
        if (!defined('REALM') || !is_array($picture) || !$this->isPluginConfigured()) {
            return;
        }

        $images = [$picture];
        $this->media->uploadListingMedia($images, (int) $picture['Listing_ID']);

        // Add data about remote URLs to image object to reassign local URLs to remote via javascript
        $file->remoteImage = reset($images);
    }

    /**
     * @hook apTplFooter
     */
    public function hookApTplFooter(): void
    {
        if ($GLOBALS['controller'] === 'listings' && $_GET['action'] === 'photos') {
            $listingID     = (int) $_GET['id'];
            $prefix         = Media::LISTINGS_DIR . '/';
            $listingImages = json_encode($this->media->getRemoteFilesDataByID($listingID, Media::LISTING_ENTITY_TYPE));

            echo <<<HTML
            <script>
                $(function () {
                    let \$fileUploader = $('#fileupload'), listingImages = JSON.parse('{$listingImages}'), prefix = '{$prefix}';

                    // Reassign local URLs of uploading image to remote
                    \$fileUploader.on('fileuploaddone', function (e, data) {
                        if (data.result) {
                            data.result.forEach(function(image) {
                                if (image.remoteImage) {
                                    image.url           = image.remoteImage.Original;
                                    image.original      = image.remoteImage.Original;
                                    image.thumbnail_url = image.remoteImage.Thumbnail;
                                }
                            })
                        }
                    })

                    // Reassign local URLs of already uploaded images to remote
                    \$fileUploader.on('fileuploadcompleted', function (e, data) {
                        if (data.result && listingImages) {
                            data.result.forEach(function (image) {
                                $.each(listingImages, function (index, remoteImage) {
                                    if (image.thumbnail_url === remoteImage.Key.replace(prefix, rlConfig.files_url)) {
                                        \$fileUploader.find('img.thumbnail[src="' + image.thumbnail_url + '"]')
                                            .attr('src', remoteImage.Remote_URL);
                                        return false;
                                    }
                                })
                            });
                        }
                    })
                })
            </script>
HTML;
        }

        // Prevent the plugin deletion if buckets have uploaded files
        if ($GLOBALS['controller'] === 'plugins') {
            echo <<<HTML
            <script>
            const rsPluginControlHandler = function () {
                let \$uninstallIcons = $('div.x-grid3-row img.uninstall');

                if (\$uninstallIcons.length > 0) {
                    \$uninstallIcons.each(function() {
                        if ($(this).attr('onclick').indexOf('"xajax_unInstall", "remoteStorage"') > 0) {
                            $(this).attr('onclick', 'rsPluginDeletion()');
                        }
                    })
               }
            }

            /**
             * Checks files in exists buckets before plugin removal
             */
            const rsPluginDeletion = function() {
                flynax.sendAjaxRequest('rsAllowPluginUninstalling', {}, function(response) {
                    if (response.status === 'OK') {
                        if (response.allowPluginUninstalling === true) {
                            rlConfirm(lang.rs_plugin_uninstall, 'xajax_unInstall', 'remoteStorage');
                        } else {
                            printMessage('alert', lang.rs_plugin_uninstall_blocked);
                        }
                    } else {
                        printMessage('error', response.message ? response.message : lang.system_error);
                    }
                }, function () {
                    printMessage('error', lang.system_error);
                });
            }

            $(function() {
                /**
                 * Rework the uninstallation process of plugin
                 * System need check already exists buckets and block the uninstallation if they have files
                 */
                pluginsGrid.store.addListener('load', function() {
                    rsPluginControlHandler();
                });
            });
            </script>
HTML;
        }
    }

    /**
     * @hook phpUploadDelete
     */
    public function hookPhpUploadDelete($media): void
    {
        if (!$this->isPluginConfigured()) {
            return;
        }

        $this->media->removeListingMedia($media);
    }

    /**
     * Upload local video from admin side here, because "listings" controller doesn't have necessary hook
     *
     * @hook reeflessRedirctVars
     */
    public function hookReeflessRedirctVars(): void
    {
        if (!$this->isPluginConfigured()) {
            return;
        }

        if (!(defined('REALM')
            && $GLOBALS['controller'] === 'listings'
            && $_GET['action'] === 'video'
            && $_POST['type'] === 'local'
            && ($listingID = (int) $_GET['id']))
        ) {
            return;
        }

        $condition = ['Listing_ID' => $listingID, 'Type' => 'video'];
        $video = $this->rlDb->fetch('*', $condition, "ORDER BY `ID` DESC", 1, 'listing_photos', 'row');

        if ($video && file_exists(RL_FILES . $video['Original'])) {
            $media = [$video];
            $this->media->uploadListingMedia($media, $listingID);
        }
    }

    /**
     * Remove remote video here, because $rlListingsAdmin->ajaxDelVideoFileAP() function doesn't have any hook
     *
     * @hook apPhpIndexBottom
     */
    public function hookApPhpIndexBottom(): void
    {
        if (!$this->isPluginConfigured()) {
            return;
        }

        if (!($GLOBALS['controller'] === 'listings'
            && $_POST['xjxfun'] === 'ajaxDelVideoFileAP'
            && ($videoID = (int) $_POST['xjxargs'][0]))
        ) {
            return;
        }

        $condition = ['ID' => $videoID, 'Type' => 'video'];
        if ($video = $this->rlDb->fetch('*', $condition, "ORDER BY `ID` DESC", 1, 'listing_photos', 'row')) {
            $this->media->removeListingMedia($video);
        }
    }

    /**
     * @hook ajaxRequestAccountThumbnailBeforeInsert
     */
    public function hookAjaxRequestAccountThumbnailBeforeInsert($accountID, $media): void
    {
        if (!$this->isPluginConfigured()) {
            return;
        }

        // Upload refreshed thumbnails to remote storage (admin side)
        if ($_REQUEST['item'] === 'rebuildAccountImages') {
            $this->media->uploadAccountPhoto($media, $accountID);

            if ($this->tempOriginImagePath) {
                unlink(RL_FILES . $this->tempOriginImagePath);
                ListingMedia::removeEmptyDir(dirname(RL_FILES . $this->tempOriginImagePath));
                $this->tempOriginImagePath = '';
            }
        }
        // Remove old account thumbnail before uploading new thumbnail (frontend/backend)
        elseif ($_REQUEST['mode'] === 'profilePictureUpload'
            || (defined('REALM') && defined('REALM') == 'admin' && $GLOBALS['controller'] === 'accounts')
        ) {
            $this->media->removeAccountPhotoByID((int) $accountID);
        }
    }

    /**
     * Upload account thumbnail from admin side
     *
     * @hook ajaxRequestProfileThumbnailAfterUpdate
     */
    public function hookAjaxRequestProfileThumbnailAfterUpdate($dirName, $file): void
    {
        if (!defined('REALM') || !$this->isPluginConfigured()) {
            return;
        }

        $this->media->uploadAccountPhoto($file, (int) $_GET['account']);
    }

    /**
     * @hook tplFooter
     */
    public function hookTplFooter(): void
    {
        if (!$this->isPluginConfigured()) {
            return;
        }

        global $plugins, $page_info, $account_info;

        /**
         * @todo - Rework this integration with Recently Viewed plugin
         *       - Plugin must have ability replace image URLs before loading them on page
         */
        if ($plugins['recentlyViewed']) {
            // Load remote URLs of images in grid on the "Recently Viewed Listings" page for not logged users
            if ($page_info['Controller'] === 'rv_listings') {
                if (!$account_info) {
                    echo <<<HTML
                        <script>
                        $(function() {
                            let waitRecentlyViewedListings = setInterval(function() {
                                let \$viewedListings = $('#controller_area #listings');

                                if (\$viewedListings.length > 0) {
                                    flUtil.ajax(
                                        {mode: 'rsUpdateRemoteUrlsInString', string: \$viewedListings.html()},
                                        function(response) {
                                            if (response && response.status && response.status === 'OK' && response.string) {
                                                \$viewedListings.html(response.string);

                                                if (typeof addTriggerToIcons === 'function') {
                                                    addTriggerToIcons();
                                                }
                                            }
                                        }
                                    );

                                    clearInterval(waitRecentlyViewedListings);
                               }
                            }, 100);
                        });
                        </script>
HTML;
                }
            } else {
                // Load remote URLs of images in the "Recently Viewed Listings" box
                echo <<<HTML
                    <script>
                    $(function() {
                        let waitRecentlyViewedListings = setInterval(function() {
                            let \$viewedListings = $('#rv_listings .rv_items');

                            if (\$viewedListings.length > 0) {
                                flUtil.ajax(
                                    {mode: 'rsUpdateRemoteUrlsInString', string: \$viewedListings.html()},
                                    function(response) {
                                        if (response && response.status && response.status === 'OK' && response.string) {
                                            \$viewedListings.html(response.string);
                                            \$viewedListings.find('img').each(function() {
                                                let imageUrl = $(this).attr('accesskey');

                                                if (imageUrl) {
                                                    $(this).attr('style', 'background-image: url(' + imageUrl + ')').removeAttr('accesskey');
                                                }
                                            });

                                            updateQtips();
                                        }
                                    }
                                );

                                clearInterval(waitRecentlyViewedListings);
                           }
                        }, 100);

                        let updateQtips = function() {
                            let tmpStyle = jQuery.extend({}, qtip_style);
                            tmpStyle.tip = 'bottomMiddle';

                            $('#rv_listings .hint').each(function(){
                                $(this).qtip({
                                    content: $(this).attr('title') ? $(this).attr('title') : $(this).prev('div.qtip_cont').html(),
                                    show: 'mouseover',
                                    hide: 'mouseout',
                                    position: {
                                        corner: {
                                            target: 'topMiddle',
                                            tooltip: 'bottomMiddle'
                                        }
                                    },
                                    style: tmpStyle
                                }).attr('title', '');
                            });
                        }
                    });
                    </script>
HTML;
            }
        }
    }

    /**
     * @hook phpPdfExportHtml
     *
     * @param  array  $listingData
     * @param  array  $seller
     * @param  string $html
     * @return void
     */
    public function hookPhpPdfExportHtml(array $listingData, array $seller, string &$html): void
    {
        if (!$listingData['Main_photo'] || !$this->isPluginConfigured()) {
            return;
        }

        $html = str_replace(RL_FILES . $listingData['Main_photo'], RL_FILES_URL . $listingData['Main_photo'], $html);
        $this->media->updateURLsInString($html);
    }

    /**
     * @hook listingMediaPrepare
     *
     * @param $fileData
     * @param $fields
     * @param $filesDirectoryURL
     *
     * @return void
     */
    public function hookListingMediaPrepare($fileData, $fields, &$filesDirectoryURL): void
    {
        $this->updateFilesURL($fileData, $filesDirectoryURL, Media::LISTING_ENTITY_TYPE);
    }

    /**
     * @hook accountMediaPrepare
     *
     * @since 1.0.1
     *
     * @param $fileData
     * @param $fields
     * @param $filesDirectoryURL
     *
     * @return void
     */
    public function hookAccountMediaPrepare($fileData, $fields, &$filesDirectoryURL): void
    {
        $this->updateFilesURL($fileData, $filesDirectoryURL, Media::ACCOUNT_ENTITY_TYPE);
    }

    /**
     * @since 1.0.1
     *
     * @param $fileData
     * @param $filesDirectoryURL
     * @param $entityType
     *
     * @return void
     */
    private function updateFilesURL($fileData, &$filesDirectoryURL, $entityType): void
    {
        if (!$this->isPluginConfigured()) {
            return;
        }

        $mediaTypes = $entityType === Media::ACCOUNT_ENTITY_TYPE ? Media::ACCOUNT_MEDIA_TYPES : Media::LISTING_MEDIA_TYPES;

        foreach ($mediaTypes as $mediaType) {
            if (!$filePath = $fileData[$mediaType]) {
                continue;
            }

            $fileURL = $filesDirectoryURL . $filePath;

            if (RL_FILES_URL === $filesDirectoryURL && file_exists($fileURL)) {
                return;
            }

            if ($fileCloudURL = $this->media->getFileCloudURL($fileURL)) {
                $filesDirectoryURL = $fileCloudURL;
                break;
            }
        }
    }

    /**
     * @since  1.0.1
     * @hook   phpAddListingRemoveBlankListing
     * @return void
     */
    public function hookPhpAddListingRemoveBlankListing($addListing, $hash): void
    {
        // Update fake listing IDs in files table
        if ($addListing->listingID && $hash) {
            $this->rlDb->update([
                'fields' => ['Entity_ID' => $addListing->listingID],
                'where'  => ['Entity_ID' => $hash]
            ], $this->plugin::FILES_TABLE);
        }
    }

    /**
     * @hook apExtListingsData
     */
    public function hookApExtListingsData(): void
    {
        if (!$GLOBALS['key'] || !$GLOBALS['data'] || !$this->isPluginConfigured()) {
            return;
        }

        $listing = &$GLOBALS['data'][$GLOBALS['key']];

        if (!$listing['Allow_photo'] || false === strpos($listing['thumbnail'], RL_FILES_URL)) {
            return;
        }

        $this->media->updateURLsInString($listing['thumbnail']);
    }

    /**
     * @hook apExtAccountsData
     */
    public function hookApExtAccountsData(): void
    {
        if (!$GLOBALS['data'] || !$this->isPluginConfigured()) {
            return;
        }

        foreach ($GLOBALS['data'] as &$account) {
            if (!$account['Photo']) {
                continue;
            }

            $this->media->updateURLsInString($account['thumbnail']);
        }
    }

    /**
     * @hook apAjaxRequest
     */
    public function hookApAjaxRequest(&$out = null, $item = null): void
    {
        switch ($item) {
            case 'rsDeleteServer':
                $error = '';
                if ($this->server->removeServer((int) $_REQUEST['id'], $error)) {
                    $out = ['status' => 'OK'];
                } else {
                    $out = ['status' => 'ERROR', 'message' => $error];
                }
                break;
            case 'rsSetMainServer':
                $error = '';
                if ($this->server->setMainServer((int) $_REQUEST['id'], $error)) {
                    $out = ['status' => 'OK'];
                } else {
                    $out = ['status' => 'ERROR', 'message' => $error];
                }
                break;
            case 'pictureRotate':
            case 'cropListingPicture':
                if (isset($out['results'])) {
                    $images = [['Type' => 'picture'] + $out['results']];
                    $this->media->loadListingMedia($images);
                    $out['results'] = reset($images);
                }
                break;
            case 'rsMigrateFile':
                try {
                    $migration = new Migration();

                    // Reverse migration (from storage -> to local)
                    if ($downloadBucketID = (int) $_REQUEST['downloadBucketID']) {
                        if ((int) $_REQUEST['file'] === 0) {
                            $this->server->updateServerStatus($downloadBucketID, $this->server::APPROVAL_STATUS);
                        }

                        $migration->revertFiles($downloadBucketID);

                        if ((int) $_REQUEST['file'] === (int) $_REQUEST['total']) {
                            if ((int) $this->config['rs_main_server'] === $downloadBucketID) {
                                $this->server->setMainServer();
                            }
                        }
                    }
                    // Default migration (from local -> to storage)
                    else {
                        $migration->moveFiles();
                    }

                    $out = ['status' => 'OK', 'limit' => $migration::LIMIT];
                } catch (Exception $e) {
                    $out = ['status' => 'ERROR', 'message' => $e->getMessage()];
                }
                break;
            case 'rsPrepareReverseMigration':
                if (($bucketID = (int) $_REQUEST['bucketID']) && ($total = (int) $_REQUEST['total'])) {
                    if (!is_object($GLOBALS['rlSmarty'])) {
                        require_once RL_LIBS . '/smarty/Smarty.class.php';
                        $this->reefless->loadClass('Smarty');
                    }

                    $GLOBALS['rlSmarty']->assign('rsCountNotMigratedMedia', $total);
                    $GLOBALS['rlSmarty']->assign('rsDownloadBucketID', $bucketID);

                    $this->lang += $this->rlDb->getAll(
                        "SELECT `Key`, `Value` FROM `{db_prefix}lang_keys`
                         WHERE `Target_key` = 'remote_storage' AND `Code` = '" . RL_LANG_CODE . "'",
                        ['Key', 'Value']
                    );
                    $GLOBALS['rlSmarty']->assign('lang', $this->lang);

                    $out = [
                        'status' => 'OK',
                        'html'   => $GLOBALS['rlSmarty']->fetch(RL_PLUGINS . 'remoteStorage/admin/view/migration.tpl', null, null, false)
                    ];
                } else {
                    $out = ['status' => 'ERROR'];
                }
                break;
            case 'rsAllowPluginUninstalling':
                $out = ['status' => 'OK', 'allowPluginUninstalling' => true];

                foreach ((new Server())->getServers() as $bucket) {
                    if ($bucket['Number_of_files_origin'] > 0) {
                        $out['allowPluginUninstalling'] = false;
                        break;
                    }
                }
                break;
        }
    }

    /**
     * @hook apNotifications
     *
     * @param array $notifications
     *
     * @return void
     */
    public function hookApNotifications(array &$notifications): void
    {
        if ($this->config['rs_main_server_down']) {
            $notifications[] = $this->server->getWarningAboutDownServer();
        }
    }

    /**
     * @return bool
     */
    public function isPluginConfigured(): bool
    {
        return (bool) $this->config['rs_main_server'];
    }

    /**
     * @return void
     */
    public function install(): void
    {
        $this->plugin::createSystemTables($this->rlDb);
        $this->plugin::addSystemConfigs($this->rlDb);
        $this->plugin::clearCompile($this->reefless);
    }

    /**
     * @return void
     */
    public function uninstall(): void
    {
        $this->plugin->removeAllServers($this->server);
        $this->plugin::removeSystemTables($this->rlDb);
        $this->plugin::clearCompile($this->reefless);
    }

    /**
     * Update to 1.0.1 version
     */
    public function update101(): void
    {
        $this->rlDb->query("ALTER TABLE `" . $this->plugin::FILES_TABLE_PRX . "` CHANGE `Entity_ID` `Entity_ID` BIGINT NOT NULL DEFAULT '0'");
        $this->rlDb->query("ALTER TABLE `" . $this->plugin::FILES_TABLE_PRX . "` ADD INDEX `Key` (`Key`)");
        rename(RL_PLUGINS . 'remoteStorage/src/Servers/VK_s3.php', RL_PLUGINS . 'remoteStorage/src/Servers/Vk_s3.php');
        $this->media->updateMediaCache();
    }
}
