<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: SERVER.PHP
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

use Flynax\Utils\Valid;
use RuntimeException;

/**
 * Server class
 */
class Server
{
    /**
     * Active status of server
     */
    public const ACTIVE_STATUS = 'active';

    /**
     * Approval status of server
     */
    public const APPROVAL_STATUS = 'approval';

    /**
     * Default status of new server
     */
    public const DEFAULT_STATUS = self::ACTIVE_STATUS;

    /**
     * @var array
     */
    protected static $statuses = [self::ACTIVE_STATUS, self::APPROVAL_STATUS];

    /**
     * @var
     */
    public $serversCount;

    /**
     * @var
     */
    protected $reefless;

    /**
     * @var
     */
    protected $rlDb;

    /**
     * @var
     */
    protected $rlConfig;

    /**
     * @var
     */
    protected $lang;

    /**
     * @var
     */
    protected $config;

    /**
     * @var
     */
    protected $servers;

    /**
     * @var
     */
    public $serverInfo;

    /**
     * Constructor
     */
    public function __construct()
    {
        if (!$GLOBALS['rlConfig']) {
            $this->reefless->loadClass('Config');
        }

        $this->reefless = &$GLOBALS['reefless'];
        $this->rlDb    = &$GLOBALS['rlDb'];
        $this->rlConfig = &$GLOBALS['rlConfig'];
        $this->lang    = &$GLOBALS['lang'];
        $this->config   = &$GLOBALS['config'];
    }

    /**
     * Update status of server
     *
     * @param int    $id
     * @param string $status
     *
     * @return void
     */
    public function updateServerStatus(int $id, string $status): void
    {
        if (!$id || !in_array($status, self::getServerStatuses(), true)) {
            throw new RuntimeException('Error: Not allowed parameters in server update.');
        }

        $this->rlDb->updateOne([
            'fields' => ['Status' => $status],
            'where' => ['ID' => $id]
        ], RemoteStorage::TABLE);
    }

    /**
     * @return array
     */
    public static function getAllServersCredentials(): array
    {
        $credentials = [];
        foreach ($GLOBALS['reefless']->scanDir(RL_PLUGINS . 'remoteStorage/src/Servers/') as $file) {
            if ($file === 'Handlers') {
                continue;
            }

            $class = '\\Flynax\\Plugins\\RemoteStorage\\Servers\\' . explode('.', $file)[0];
            $credentials[$class::getType()] = $class::getCredentials();
        }

        ksort($credentials);
        return $credentials;
    }

    /**
     * Get servers for grid manager in admin panel
     * @return void
     */
    public function apGetExtJsServers(): void
    {
        $start    = (int) $_GET['start'];
        $limit    = (int) $_GET['limit'];
        $page     = ($start / $limit) + 1;
        $filters   = [];
        $sorting  = [];

        if ($_GET['sort']) {
            $sorting = [
                'field'      => Valid::escape($_GET['sort']),
                'direction' => Valid::escape($_GET['dir'])
            ];
        }

        $servers = $this->getServers($page, $limit, $filters, $sorting);

        echo json_encode(['total' => $this->getServersCount(), 'data' => $servers]);
    }

    /**
     * Get list of servers
     *
     * @param int   $page
     * @param int   $limit
     * @param array $filters
     * @param array $sorting
     *
     * @return array
     */
    public function getServers(int $page = 0, int $limit = 0, array $filters = [], array $sorting = []): array
    {
        $start = $page > 1 ? ($page - 1) * $limit : 0;

        $sql = 'SELECT SQL_CALC_FOUND_ROWS `Servers`.*';

        if ($sorting['field']) {
            switch ($sorting['field']) {
                default:
                    $sorting['field'] = "`Servers`.`{$sorting['field']}`";
                    break;
            }
        }

        $sql .= " FROM `" . RemoteStorage::TABLE_PRX . "` AS `Servers` ";

        if ($filters) {
            $sql .= 'WHERE 1 ';
            foreach ($filters as $filterKey => $filter) {
                if (empty($filter)) {
                    continue;
                }

                switch ($filterKey) {
                    case 'Status':
                        if (in_array($filter, self::getServerStatuses(), true)) {
                            $sql .= "AND `Servers`.`Status` = '{$filter}'";
                        }
                        break;
                }
            }
        }

        $sql .= 'ORDER BY ';
        if ($sorting['field'] && $sorting['direction']) {
            $sql .= "{$sorting['field']} {$sorting['direction']} ";
        } else {
            $sql .= "`Servers`.`ID` ASC ";
        }

        if ($start && $limit) {
            $sql .= "LIMIT {$start}, {$limit}";
        }

        $servers = $this->rlDb->getAll($sql);

        $this->setServersCount((int) $this->rlDb->getRow('SELECT FOUND_ROWS()')['FOUND_ROWS()']);

        foreach ($servers as &$server) {
            $server['Status_key']     = $server['Status'];
            $server['Status']         = $this->lang[$server['Status']];
            $server['Main_server']    = (int) $this->config['rs_main_server'] === (int) $server['ID'];
            $server['Number_of_files'] = (int) $this->rlDb->getRow(
                "SELECT COUNT(*) FROM `" . RemoteStorage::FILES_TABLE_PRX . "` WHERE `Server_ID` = {$server['ID']}",
                'COUNT(*)'
            );
            $server['Number_of_files_origin'] = $server['Number_of_files'];

            if ($server['Number_of_files'] >= 0 && $server['Number_of_files'] < 1000) {
                $server['Number_of_files'] = number_format($server['Number_of_files']);
            } else if ($server['Number_of_files'] < 1000000) {
                $server['Number_of_files'] = number_format($server['Number_of_files'] / 1000, 1) . $GLOBALS['lang']['rs_k'];
            } else if ($server['Number_of_files'] < 1000000000) {
                $server['Number_of_files'] = number_format($server['Number_of_files'] / 1000000, 1) . $GLOBALS['lang']['rs_m'];
            } else {
                $server['Number_of_files'] = number_format($server['Number_of_files'] / 1000000000, 1) . $GLOBALS['lang']['rs_b'];
            }
        }

        return $servers;
    }

    /**
     * @param array $serverData
     * @param array $credentialsFields
     *
     * @return bool
     */
    public function createServer(array $serverData, array $credentialsFields): bool
    {
        self::adaptCredentialsInServerData($serverData);

        $credentials = [];
        foreach ($credentialsFields as $credentialsField) {
            $credentials[$credentialsField] = $serverData['Credentials'][$credentialsField];
        }

        $this->rlDb->insertOne([
            'Title'       => $serverData['title'],
            'Type'        => $serverData['type'],
            'Bucket'      => $serverData['bucket'],
            'Credentials' => json_encode($credentials),
            'Status'      => $serverData['status'] ?: self::DEFAULT_STATUS,
        ], RemoteStorage::TABLE, ['Credentials']);

        if (!$this->config['rs_main_server']) {
            $this->setMainServer((int) $this->rlDb->insertID());
        }

        $this->updateMainServerURL();

        return true;
    }

    /**
     * Delete server from list
     *
     * @param int    $id
     * @param string $error
     *
     * @return bool
     */
    public function removeServer(int $id, string &$error = ''): bool
    {
        $serverInfo = $this->getServerInfo($id);

        $server = ServerResolver::getServer($serverInfo['Type']);
        $server->initialize($serverInfo['Credentials'])->removeBucket($serverInfo['Bucket'], $error);

        if (!$error) {
            $this->rlDb->delete(['Server_ID' => $id], RemoteStorage::FILES_TABLE, null, 0);
            $this->rlDb->delete(['ID' => $id], RemoteStorage::TABLE);

            if (!$this->getFirstActiveServer()) {
                $this->reefless->loadClass('Config');
                $GLOBALS['rlConfig']->setConfig('rs_main_server', '');
            }

            if ($id === (int) $this->config['rs_main_server']) {
                $this->setMainServer();
            }

            $this->updateMainServerURL();

            (new Media($this))->updateMediaCache();

            return true;
        }

        return false;
    }

    /**
     * Update data of server
     *
     * @since 1.0.1 - Removed $credentialsFields parameter
     *
     * @param int   $id
     * @param array $serverData - Required fields: title
     *
     * @return bool
     */
    public function updateServer(int $id, array $serverData): bool
    {
        $title  = Valid::escape($serverData['title']);

        if (!$title) {
            throw new RuntimeException('Error. Not a valid title of server.');
        }

        return $this->rlDb->updateOne([
            'fields' => ['Title' => $title],
            'where'  => ['ID' => $id]
        ], RemoteStorage::TABLE);
    }

    /**
     * Get info about server
     *
     * @param int $serverID
     *
     * @return array
     */
    public function getServerInfo(int $serverID): array
    {
        $server = (array) $this->rlDb->fetch('*', ['ID' => $serverID], null, 1, RemoteStorage::TABLE, 'row');

        if ($server && $server['Credentials']) {
            $server['Credentials'] = json_decode($server['Credentials'], true);
        }

        return $server;
    }

    /**
     * @param array      $serverData
     * @param array|null $errors
     *
     * @return string|null
     */
    public function createBucket(array &$serverData, ?array &$errors = []): ?string
    {
        $server     = ServerResolver::getServer($serverData['type']);
        $bucketName = $GLOBALS['domain_info']['host'] . '-' . uniqid();

        $server::adaptServerData($serverData);

        self::adaptCredentialsInServerData($serverData);

        return $server->initialize($serverData)->createBucket($bucketName, $errors) ? $bucketName : null;
    }

    /**
     * @return bool|object
     */
    public function getServerInstance(?int $serverID = 0)
    {
        $serverID = $serverID ?: $this->config['rs_main_server'];

        if (!$serverID) {
            return false;
        }

        if ($this->servers[$serverID]) {
            return $this->servers[$serverID];
        }

        $this->serverInfo = $this->getServerInfo($serverID);
        $this->servers[$serverID] = ServerResolver::getServer($this->serverInfo['Type']);
        $this->servers[$serverID]->initialize($this->serverInfo);

        return $this->servers[$serverID];
    }

    /**
     * @param $serverData
     *
     * @return void
     */
    protected static function adaptCredentialsInServerData(&$serverData): void
    {
        if (!isset($serverData['Credentials'])
            && $serverData['type']
            && isset($serverData[$serverData['type']])
        ) {
            $serverData['Credentials'] = $serverData[$serverData['type']];
        }
    }

    /**
     * @param int|null    $serverID
     * @param string|null $error
     *
     * @return bool
     */
    public function setMainServer(?int $serverID = null, ?string &$error = null): bool
    {
        // Check the server before administrator will set it as main server again
        if ($serverID) {
            $filename = RL_TMP . 'upload/rs-test-server.txt';
            file_put_contents($filename, 'Remote Storage: It\'s a test file for checking server.');

            try {
                $serverInfo = $this->getServerInfo($serverID);
                $server = ServerResolver::getServer($serverInfo['Type']);
                $server->initialize($serverInfo);
                $server->sendFile('rs:test-server.txt', $filename);
                $server->removeFile('rs:test-server.txt');
                unlink($filename);

                $this->reefless->loadClass('Config');
                $GLOBALS['rlConfig']->setConfig('rs_main_server_down', '');
                $this->updateServerStatus($serverID, self::ACTIVE_STATUS);
            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
                unlink($filename);
                $this->updateServerStatus($serverID, self::APPROVAL_STATUS);
                return false;
            }
        }

        $serverID = $serverID ?: $this->getFirstActiveServer();

        return $this->rlConfig->setConfig('rs_main_server', $serverID);
    }

    /**
     * @return int
     */
    public function getFirstActiveServer(): int
    {
        return (int) $this->rlDb->getOne('ID', "`Status` = '" . self::ACTIVE_STATUS . "'", RemoteStorage::TABLE);
    }

    /**
     * @param string|null $error
     *
     * @return void
     */
    public function mainServerDownHandler(?string $error = null): void
    {
        if ($error) {
            $error = trim(preg_replace('/\s\s+/', ' ', strip_tags($error)));
            $GLOBALS['rlDebug']->logger("Remote Storage: {$error}");
        }

        $this->rlConfig->setConfig('rs_main_server_down', $this->config['rs_main_server']);
        $this->updateServerStatus($this->config['rs_main_server'], self::APPROVAL_STATUS);
        $this->setMainServer();
    }

    /**
     * @return string|null
     */
    public function getWarningAboutDownServer(): ?string
    {
        if (!($lastDownServer = $this->getServerInfo((int) $this->config['rs_main_server_down']))) {
            return null;
        }

        return str_replace(
            '{bucket}',
            $lastDownServer['Title'],
            $GLOBALS['rlLang']->getSystem('rs_main_server_down_notice')
        );
    }

    /**
     * Set main server URL for remote files if exists one bucket only
     *
     * @return void
     */
    protected function updateMainServerURL(): void
    {
        if (count($buckets = $this->getServers()) === 1) {
            $bucketInfo = $this->getServerInfo((int) reset($buckets)['ID']);

            if ($fileUrl = ServerResolver::getServer($bucketInfo['Type'])::getFileURLPattern($bucketInfo)) {
                $this->rlConfig->setConfig('rs_main_server_url', $fileUrl);
            } else {
                $this->rlConfig->setConfig('rs_main_server_url', '');
            }
        } else {
            $this->rlConfig->setConfig('rs_main_server_url', '');
        }
    }

    /**
     * @return array
     */
    public static function getServerStatuses(): array
    {
        return self::$statuses;
    }

    /**
     * @return mixed
     */
    public function getServersCount()
    {
        return $this->serversCount;
    }

    /**
     * @param mixed $serversCount
     */
    public function setServersCount($serversCount): void
    {
        $this->serversCount = $serversCount;
    }

    /**
     * @return array
     */
    public static function getServerTypes(): array
    {
        return array_keys(self::getAllServersCredentials());
    }

    /**
     * @return string
     */
    public static function getDefaultServerType(): string
    {
        return self::getServerTypes()[0] ?? '';
    }
}
