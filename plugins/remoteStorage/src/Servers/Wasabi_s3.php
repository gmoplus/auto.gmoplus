<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : WASABI_S3.PHP
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
 * Wasabi Hot Cloud Storage
 */
class Wasabi_s3 extends S3
{
    /**
     * List of available regions in DigitalOcean Spaces
     */
    public const REGIONS = [
        'us-east-1'      => 'US East 1 (N. Virginia)',
        'us-east-2'      => 'US East 2 (N. Virginia)',
        'us-central-1'   => 'US Central 1 (Texas)',
        'us-west-1'      => 'US West 1 (Oregon)',
        'ca-central-1'   => 'CA Central 1 (Toronto)',
        'eu-central-1'   => 'EU Central 1 (Amsterdam)',
        'eu-central-2'   => 'EU Central 2 (Frankfurt)',
        'eu-west-1'      => 'EU West 1 (London)',
        'eu-west-2'      => 'EU West 2 (Paris)',
        'ap-northeast-1' => 'AP Northeast 1 (Tokyo)',
        'ap-northeast-2' => 'AP Northeast 2 (Osaka)',
        'ap-southeast-1' => 'AP Southeast 1 (Singapore)',
        'ap-southeast-2' => 'AP Southeast 2 (Sydney)',
    ];

    /**
     * List of credentials which must be hidden and not saved in database
     */
    public const HIDDEN_CREDENTIALS = [self::ENDPOINT];

    /**
     * @var string
     */
    protected const REGION_PATTERN = '{REGION}';

    /**
     * Links of guides to get credentials
     */
    public const GUIDES = [
        'en' => 'https://wasabi-support.zendesk.com/hc/en-us/articles/360019677192-Creating-a-Wasabi-API-Access-Key-Set',
    ];

    /**
     * @var string
     */
    protected static $endpointPattern = 'https://s3.' . self::REGION_PATTERN . '.wasabisys.com';

    /**
     * @var string
     */
    private static $type = 'wasabi_s3';

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
     * @return void
     */
    public static function adaptServerData(array &$serverData): void
    {
        $serverData[self::$type][self::ENDPOINT] = str_replace(
            self::REGION_PATTERN,
            $serverData[self::$type][self::REGION],
            self::$endpointPattern
        );
    }
}
