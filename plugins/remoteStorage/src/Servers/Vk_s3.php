<?php
/**copyright**/

namespace Flynax\Plugins\RemoteStorage\Servers;

use Flynax\Plugins\RemoteStorage\Servers\Handlers\S3;

/**
 * VK Cloud Solutions
 */
class VK_s3 extends S3
{
    /**
     * List of available regions in Yandex Object Storage
     */
    public const REGIONS = [
        'ru-msk' => 'RU MSK',
    ];

    /**
     * List of credentials which must be hidden and not saved in database
     */
    public const HIDDEN_CREDENTIALS = [self::ENDPOINT];

    /**
     * Links of guides to get credentials
     */
    public const GUIDES = [
        'ru' => 'https://mcs.mail.ru/docs/base/s3/access-management/s3-account',
    ];

    /**
     * @var string
     */
    protected static $endpoint = 'https://hb.bizmrg.com';

    /**
     * @var string
     */
    private static $type = 'vk_s3';

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
