<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : YANDEX_S3.PHP
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

namespace Flynax\Plugins\RemoteStorage\Servers;

use Flynax\Plugins\RemoteStorage\Servers\Handlers\S3;

/**
 * Yandex Object Storage
 */
class Yandex_s3 extends S3
{
    /**
     * List of available regions in Yandex Object Storage
     */
    public const REGIONS = [
        'ru-central1' => 'Central 1',
    ];

    /**
     * List of credentials which must be hidden and not saved in database
     */
    public const HIDDEN_CREDENTIALS = [self::ENDPOINT];

    /**
     * Links of guides to get credentials
     */
    public const GUIDES = [
        'en' => 'https://cloud.yandex.com/en/docs/iam/operations/sa/create-access-key',
        'ru' => 'https://cloud.yandex.ru/docs/iam/operations/sa/create-access-key',
    ];

    /**
     * @var string
     */
    protected static $endpoint = 'https://storage.yandexcloud.net';

    /**
     * @var string
     */
    private static $type = 'yandex_s3';

    /**
     * @return string
     */
    public static function getType(): string
    {
        return self::$type;
    }

    /**
     * @param array $serverData
     *
     * @return string
     */
    public static function getFileURLPattern(array &$serverData): string
    {
        return $serverData['Bucket'] ? self::$endpoint . '/' . $serverData['Bucket'] . '/' : '';
    }
}
