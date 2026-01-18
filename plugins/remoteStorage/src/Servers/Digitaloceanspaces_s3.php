<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: DIGITALOCEANSPACES_S3.PHP
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

namespace Flynax\Plugins\RemoteStorage\Servers;

use Flynax\Plugins\RemoteStorage\Servers\Handlers\S3;

/**
 * Digital Ocean Spaces storage
 */
class Digitaloceanspaces_s3 extends S3
{
    /**
     * List of available regions in DigitalOcean Spaces
     */
    public const REGIONS = [
        'nyc1' => 'NYC1, New York City, United States',
        'nyc2' => 'NYC2, New York City, United States',
        'nyc3' => 'NYC3, New York City, United States',
        'ams2' => 'AMS2, Amsterdam, the Netherlands',
        'ams3' => 'AMS3, Amsterdam, the Netherlands',
        'sfo1' => 'SFO1, San Francisco, United States',
        'sfo2' => 'SFO2, San Francisco, United States',
        'sfo3' => 'SFO3, San Francisco, United States',
        'sgp1' => 'SGP1, Singapore',
        'lon1' => 'LON1, London, United Kingdom',
        'fra1' => 'FRA1, Frankfurt, Germany',
        'tor1' => 'TOR1, Toronto, Canada',
        'blr1' => 'BLR1, Bangalore, India',
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
     * @var string
     */
    protected const BASE_REGION = 'us-east-1';

    /**
     * Links of guides to get credentials
     */
    public const GUIDES = [
        'en' => 'https://www.digitalocean.com/community/tutorials/how-to-create-a-digitalocean-space-and-api-key',
    ];

    /**
     * @var string
     */
    protected static $endpointPattern = 'https://' . self::REGION_PATTERN . '.digitaloceanspaces.com';

    /**
     * @var string
     */
    private static $type = 'digitaloceanspaces_s3';

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
        $serverData[self::$type]['BASE_REGION'] = self::BASE_REGION;
    }
}
