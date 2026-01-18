<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: AMAZON_S3.PHP
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
 * Amazon S3 Storage
 */
class Amazon_s3 extends S3
{
    /**
     * List of available regions in Amazon S3 storage
     */
    public const REGIONS = [
        'us-east-2'      => 'US East (Ohio)',
        'us-east-1'      => 'US East (N. Virginia)',
        'us-west-1'      => 'US West (N. California)',
        'us-west-2'      => 'US West (Oregon)',
        'af-south-1'     => 'Africa (Cape Town)',
        'ap-east-1'      => 'Asia Pacific (Hong Kong)',
        'ap-south-1'     => 'Asia Pacific (Mumbai)',
        'ap-northeast-3' => 'Asia Pacific (Osaka)',
        'ap-northeast-2' => 'Asia Pacific (Seoul)',
        'ap-southeast-1' => 'Asia Pacific (Singapore)',
        'ap-southeast-2' => 'Asia Pacific (Sydney)',
        'ap-northeast-1' => 'Asia Pacific (Tokyo)',
        'ca-central-1'   => 'Canada (Central)',
        'cn-northwest-1' => 'China (Ningxia)',
        'eu-central-1'   => 'Europe (Frankfurt)',
        'eu-west-1'      => 'Europe (Ireland)',
        'eu-west-2'      => 'Europe (London)',
        'eu-south-1'     => 'Europe (Milan)',
        'eu-west-3'      => 'Europe (Paris)',
        'eu-north-1'     => 'Europe (Stockholm)',
        'ap-southeast-3' => 'Asia Pacific (Jakarta)',
        'me-south-1'     => 'Middle East(Bahrain)',
        'sa-east-1'      => 'South America (São Paulo)',
        'us-gov-east-1'  => 'AWS GovCloud (US-East)',
        'us-gov-west-1'  => 'AWS GovCloud (US-West)',
    ];

    /**
     * List of credentials which must be hidden and not saved in database
     */
    public const HIDDEN_CREDENTIALS = [self::ENDPOINT];

    /**
     * Links of guides to get credentials
     */
    public const GUIDES = [
        'en' => 'https://docs.aws.amazon.com/accounts/latest/reference/root-user-access-key.html',
    ];

    /**
     * Pattern for links of remote files
     */
    public const FILE_URL_PATTERN = 'https://s3.{region}.amazonaws.com/{bucket}/';

    /**
     * @param array $serverData
     *
     * @return string
     */
    public static function getFileURLPattern(array &$serverData): string
    {
        return $serverData['Credentials'][self::REGION] && $serverData['Bucket']
            ? str_replace(
                ['{region}', '{bucket}'],
                [$serverData['Credentials'][self::REGION], $serverData['Bucket']],
                self::FILE_URL_PATTERN
            )
            : '';
    }

    /**
     * Create a bucket in Amazon S3 storage and remove the public access block
     *
     * @since 1.0.1
     *
     * @param string $bucketName
     * @param array  $errors
     *
     * @return bool
     */
    public function createBucket(string $bucketName, ?array &$errors = []): bool
    {
        if (!parent::createBucket($bucketName, $errors)) {
            return false;
        }

        $this->client->deletePublicAccessBlock(['Bucket' => $bucketName]);

        return true;
    }
}
