<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: S3.PHP
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

namespace Flynax\Plugins\RemoteStorage\Servers\Handlers;

use Aws\Credentials\Credentials;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Exception;
use Flynax\Plugins\RemoteStorage\Interfaces\ServerInterface;
use RuntimeException;

/**
 * Base S3 server class
 */
class S3 implements ServerInterface
{
    protected const KEY         = 'ACCESS_KEY_ID';
    protected const SECRET      = 'SECRET_ACCESS_KEY';
    protected const REGION      = 'REGION';
    protected const BASE_REGION = 'BASE_REGION';
    protected const ENDPOINT    = 'ENDPOINT';

    /**
     * Connection timeout
     */
    public const TIMEOUT = 30;

    /**
     * List of available regions in storage
     */
    public const REGIONS = [];

    /**
     * List of credentials which must be hidden and not saved in database
     */
    public const HIDDEN_CREDENTIALS = [];

    /**
     * Links of guides to get credentials
     */
    public const GUIDES = [];

    /**
     * Pattern for links of remote files
     */
    public const FILE_URL_PATTERN = '';

    /**
     * @var string[]
     */
    protected static $credentials = [self::REGION, self::ENDPOINT, self::KEY, self::SECRET];

    /**
     * @var string
     */
    protected static $endpoint = '';

    /**
     * @var string
     */
    private static $type = 'amazon_s3';

    /**
     * @var S3Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $bucket;

    /**
     * @var string
     */
    protected $region;

    /**
     * @since 1.0.1
     * @var object
     */
    protected $debug;

    /**
     * @param array $serverData
     *
     * @return self
     */
    public function initialize(array $serverData): object
    {
        $credentials = is_array($serverData['Credentials']) ? $serverData['Credentials'] : $serverData;

        $this->bucket = $serverData['Bucket'] ?? null;
        $this->region = $credentials[self::BASE_REGION] ?? ($credentials[self::REGION] ?? 'us-east-2');
        $this->debug  = $GLOBALS['rlDebug'];

        $config = [
            'version'     => 'latest',
            'region'      => $this->region,
            'credentials' => new Credentials($credentials[self::KEY], $credentials[self::SECRET]),
            'http'        => ['connect_timeout' => self::TIMEOUT, 'timeout' => self::TIMEOUT],
        ];

        if ($this::$endpoint) {
            $config['endpoint'] = $this::$endpoint;
        } else if ($credentials[self::ENDPOINT]) {
            $config['endpoint'] = $credentials[self::ENDPOINT];
        }

        $this->client = new S3Client($config);

        return $this;
    }

    /**
     * @param string $key
     * @param string $path
     *
     * @return string
     */
    public function sendFile(string $key, string $path): string
    {
        try {
            $result = $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'Body'   => fopen($path, 'rb'),
                'ACL'    => 'public-read',
            ]);

            return $result->get('ObjectURL');
        } catch (S3Exception $exception) {
            throw new RuntimeException("Failed to upload $key with error: " . $exception->getMessage());
        }
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function removeFile(string $key): void
    {
        try {
            $this->client->deleteObject(['Bucket' => $this->bucket, 'Key' => $key]);
        } catch (S3Exception $exception) {
            $this->debug->logger("Failed to remove $key with error: " . $exception->getMessage());
        }
    }

    /**
     * @param string $key
     * @param string $path
     *
     * @return bool
     */
    public function getFile(string $key, string $path): bool
    {
        try {
            return (bool) $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'SaveAs' => $path
            ]);
        } catch (S3Exception $exception) {
            $this->debug->logger("Failed to get $key with error: " . $exception->getMessage());
            return true;
        }
    }

    /**
     * @param string     $bucketName
     * @param array|null $errors
     *
     * @return bool
     */
    public function createBucket(string $bucketName, ?array &$errors = []): bool
    {
        try {
            return (bool) $this->client->createBucket([
                'Bucket'                    => $bucketName,
                'CreateBucketConfiguration' => ['LocationConstraint' => $this->region],
                'ObjectOwnership'           => 'BucketOwnerPreferred', // BucketOwnerPreferred|ObjectWriter|BucketOwnerEnforced
            ]);
        } catch (Exception $exception) {
            $errors[] = "Failed to create bucket $bucketName in server with error: " . $exception->getMessage();
            return false;
        }
    }

    /**
     * @param string      $name
     * @param string|null $error
     *
     * @return bool
     */
    public function removeBucket(string $name, ?string &$error = ''): bool
    {
        try {
            return (bool) $this->client->deleteBucket(['Bucket' => $name]);
        } catch (Exception $exception) {
            $error = "Failed to delete bucket $name with error: " . $exception->getMessage();
            return false;
        }
    }

    /**
     * @return string[]
     */
    public static function getCredentials(): array
    {
        return self::$credentials;
    }

    /**
     * @return string
     */
    public static function getType(): string
    {
        return self::$type;
    }

    /**
     * Experimental method for getting bucket size (in MB)
     * @return int
     */
    public function getSize(): int
    {
        $totalSize = 0;
        try {
            foreach ($this->client->getPaginator('ListObjects', ['Bucket' => $this->bucket]) as $result) {
                foreach ($result['Contents'] as $object) {
                    $totalSize += $object['Size'];
                }
            }

            return number_format($totalSize / 1024 / 1024, 2);
        } catch (S3Exception $e) {
            // echo $e->getMessage() . PHP_EOL;
            return 0;
        }
    }

    /**
     * @param array $serverData
     *
     * @return void
     */
    public static function adaptServerData(array &$serverData): void
    {}

    /**
     * @param array $serverData
     *
     * @return string
     */
    public static function getFileURLPattern(array &$serverData): string
    {
        return $serverData['Credentials'][self::ENDPOINT] && $serverData['Bucket']
            ? $serverData['Credentials'][self::ENDPOINT] . '/' . $serverData['Bucket'] . '/'
            : '';
    }
}
