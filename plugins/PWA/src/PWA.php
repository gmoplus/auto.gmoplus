<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLPWA.CLASS.PHP
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

namespace Flynax\Plugins\PWA;

use Flynax\Component\Filesystem;
use Flynax\Plugins\PWA\Files\Icon;
use Flynax\Plugins\PWA\Files\Manifest;
use Minishlink\WebPush\VAPID;

class PWA
{
    /**
     * @var \rlDb
     */
    private $rlDb;

    /**
     * @var \Flynax\Plugins\PWA\Files\Manifest
     */
    public $manifest;

    /**
     * PWA constructor.
     */
    public function __construct()
    {
        $this->rlDb = $GLOBALS['rlDb'];
        $this->manifest = new Manifest();

        if (!is_dir(PWA_FILES_PATH)) {
            $GLOBALS['reefless']->rlMkdir(PWA_FILES_PATH);
        }
    }

    /**
     * Save JSON data to file
     *
     * @param array  $array
     * @param string $fileName
     * @param string $saveIn
     * @return bool
     */
    public static function arrayToJsonFile($array, $fileName, $saveIn = PWA_FILES_PATH)
    {
        if (!$array || !$fileName) {
            return false;
        }

        $filePath = $saveIn . $fileName;
        $fp = fopen($filePath, 'w');
        $res = fwrite($fp, json_encode($array, JSON_PRETTY_PRINT));
        fclose($fp);

        return (bool) $res;
    }

    /**
     * Handler for saving and applying data into manifest
     */
    public function afterConfigSaving()
    {
        Config::i()->fetchAllConfigs();
        $this->manifest->generate();
    }

    /**
     * Copy system files of offline mode to root directory
     */
    public function copyOfflineFolder()
    {
        $fileSystem = new Filesystem();
        $fileSystem->copyTo(PWA_ROOT . 'static/offline', RL_ROOT . 'offline');

        $template  = RL_URL_HOME . 'templates/' . $GLOBALS['config']['template'] . '/';
        $logoSrc   = $template . 'img/logo.png';
        $logo2xSrc = $template . 'img/@2x/logo.png';

        // SVG logo in rainbow templates
        $svgLogo = is_file(RL_ROOT . 'templates/' . $GLOBALS['config']['template'] . '/img/logo.svg')
            ? $template . 'img/logo.svg'
            : '';

        file_put_contents(
            RL_ROOT . 'offline/index.html',
            str_replace(
                ['{logo_src}', '{logo_src_2x}'],
                [$svgLogo ?: $logoSrc, $svgLogo ?: $logo2xSrc],
                file_get_contents(RL_ROOT . 'offline/index.html')
            )
        );
    }

    /**
     * Copy system files of service-workers to root directory
     */
    public function copyServiceWorkers()
    {
        $fileSystem = new Filesystem();

        $workers = [
            'upup.min.js',
            'upup.sw.min.js',
        ];

        foreach ($workers as $worker) {
            $fileSystem->copyTo(PWA_ROOT . 'static/' . $worker, RL_ROOT . $worker);
        }
    }

    /**
     * Create all necessary plugin tables
     */
    public function addSystemTables()
    {
        $this->rlDb->createTable('pwa_usage_info',
            "`ID` INT(11) PRIMARY KEY NOT NULL auto_increment,
            `IP` VARCHAR(15) NOT NULL DEFAULT '',
            `OS` VARCHAR(20) NOT NULL DEFAULT '',
            `Browser` VARCHAR(20) NOT NULL DEFAULT '',
            `Plugin_version` VARCHAR(5) NOT NULL DEFAULT '',
            `Date` DATETIME,
            `Country` VARCHAR(44) NOT NULL DEFAULT '',
            `State` VARCHAR(52) NOT NULL DEFAULT '',
            `City` VARCHAR(61) NOT NULL DEFAULT '',
            KEY IP (IP),
            KEY Browser (Browser)"
        );

        $this->rlDb->createTable('pwa_images',
            "`ID` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            `Image` VARCHAR(255) DEFAULT NULL,
            `Type` ENUM('icon','launch_portrait','launch_landscape') DEFAULT 'icon',
            `Size` VARCHAR(10) DEFAULT NULL"
        );

        $this->rlDb->createTable('pwa_subscriptions',
            "`ID` INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            `Account_ID` INT(11) NOT NULL,
            `Subscription` ENUM('active', 'inactive', 'blocked') DEFAULT 'inactive',
            `Alerts` ENUM('1', '0') DEFAULT '0',
            `Messages` ENUM('1', '0') DEFAULT '0',
            `Endpoint` TEXT DEFAULT NULL,
            `P256dh` TEXT DEFAULT NULL,
            `Auth` TEXT DEFAULT NULL,
            KEY Account_ID (Account_ID),
            KEY Subscription (Subscription)"
        );
    }

    /**
     * Detect PWA requests
     *
     * @return bool
     */
    public static function isFromPWA()
    {
        return defined('FROM_PWA');
    }

    /**
     * Remove plugin tables
     */
    public function dropSystemTables()
    {
        $this->rlDb->dropTables(['pwa_usage_info', 'pwa_images', 'pwa_subscriptions']);
    }

    /**
     * Checking correct ajax request
     * @param $mode
     * @return bool
     */
    public function isValidAjax($mode)
    {
        return in_array($mode, array_values([
            'pwa_installed',
            'pwa_subscribe',
            'pwa_unsubscribe',
            'pwa_push_blocked',
            'pwa_get_subscriptions',
        ]));
    }

    /**
     * Removing all generated files from plugin
     */
    public function clearAllGeneratedFiles()
    {
        global $languages;

        $countLanguages = count($GLOBALS['languages']);
        $files          = [];

        if ($countLanguages > 1) {
            foreach ($languages as $languageCode => $languageData) {
                $files[] = $languageCode . '-manifest.json';
            }
        } else {
            $files[] = 'manifest.json';
        }

        $files = array_merge(Icon::getImages(), $files);
        foreach ($files as $image) {
            $fullPath = PWA_ROOT . 'files/' . $image;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    /**
     * Generate VAPID keys for service-workers
     * @return array|bool
     * @throws \ErrorException
     */
    public function generateVAPIDKeys()
    {
        if (!$this->isPhpCompatible()) {
            return [];
        }

        $keys = VAPID::createVapidKeys();
        $alreadyExists = $GLOBALS['rlConfig']->getConfig('pwa_vapid_public');
        if (!$keys || $alreadyExists) {
            return false;
        }

        // Prevent the creation new config in install process
        $GLOBALS['config']['pwa_vapid_public']  = '';
        $GLOBALS['config']['pwa_vapid_private'] = '';

        $GLOBALS['rlConfig']->setConfig('pwa_vapid_public', $keys['publicKey']);
        $GLOBALS['rlConfig']->setConfig('pwa_vapid_private', $keys['privateKey']);

        return $keys;
    }

    /**
     * Create all necessary hidden plugin configs
     */
    public function createHiddenConfigs()
    {
        $configs = [
            'pwa_color',
            'pwa_icon',
            'pwa_vapid_public',
            'pwa_vapid_private',
            'pwa_maskable_icons',
        ];

        foreach ($configs as $config) {
            $this->rlDb->insertOne([
                'Key'      => $config,
                'Group_ID' => 0,
                'Plugin'   => 'PWA',
                'Type'     => 'text'
            ], 'config');
        }
    }

    /**
     * Check configuration of PHP in web-server
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function isPhpCompatible()
    {
        return version_compare(phpversion(), '7.2.0') >= 0 && extension_loaded('gmp');
    }
}
