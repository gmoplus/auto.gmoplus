<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: REMOTESTORAGE.PHP
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

/**
 * Remote Storage class
 */
class RemoteStorage
{
    /**
     * Plugin servers table
     */
    public const TABLE = 'rs_servers';

    /**
     * Plugin servers table with prefix
     */
    public const TABLE_PRX = '{db_prefix}' . self::TABLE;

    /**
     * Plugin files table
     */
    public const FILES_TABLE = 'rs_files';

    /**
     * Plugin servers table with prefix
     */
    public const FILES_TABLE_PRX = '{db_prefix}' . self::FILES_TABLE;

    /**
     * @param $rlDb
     *
     * @return void
     */
    public static function createSystemTables($rlDb): void
    {
        $types    = "'" . implode("','", Server::getServerTypes()) . "'";
        $statuses = "'" . implode("','", Server::getServerStatuses()) . "'";

        $rlDb->createTable(
            self::TABLE,
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
             `Title` TINYTEXT CHARACTER SET utf8 NOT NULL DEFAULT '',
             `Type` ENUM({$types}) NOT NULL DEFAULT '" . Server::getDefaultServerType() . "',
             `Bucket` VARCHAR(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
             `Credentials` MEDIUMTEXT CHARACTER SET utf8 NOT NULL DEFAULT '',
             `Status` ENUM({$statuses}) NOT NULL DEFAULT '" . Server::DEFAULT_STATUS . "',
             PRIMARY KEY (`ID`),
             INDEX (`Status`)",
            RL_DBPREFIX,
            'ENGINE=InnoDB CHARSET=utf8 COLLATE=utf8_general_ci;'
        );

        $rlDb->createTable(
            self::FILES_TABLE,
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
             `Entity_ID` BIGINT NOT NULL DEFAULT '0',
             `Key` VARCHAR(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
             `Server_ID` int(11) NOT NULL,
             `Remote_URL` VARCHAR(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
             PRIMARY KEY (`ID`),
             INDEX (`Key`)",
            RL_DBPREFIX,
            'ENGINE=InnoDB CHARSET=utf8 COLLATE=utf8_general_ci;'
        );
    }

    /**
     * @param $rlDb
     *
     * @return void
     */
    public static function addSystemConfigs($rlDb): void
    {
        foreach (['rs_main_server', 'rs_main_server_down', 'rs_main_server_url'] as $config) {
            $rlDb->insertOne([
                'Key'      => $config,
                'Group_ID' => 0,
                'Plugin'   => 'remoteStorage',
                'Type'     => 'text'
            ], 'config');
        }
    }

    /**
     * @param $rlDb
     *
     * @return void
     */
    public static function removeSystemTables($rlDb): void
    {
        $rlDb->dropTables([self::TABLE, self::FILES_TABLE]);
    }

    /**
     * @param null $storage
     *
     * @return void
     */
    public function removeAllServers($storage = null): void
    {
        if (!$storage) {
            return;
        }

        foreach ($storage->getServers() as $server) {
            $storage->removeServer((int) $server['ID']);
        }
    }

    /**
     * @param $reefless
     *
     * @return void
     */
    public static function clearCompile($reefless): void
    {
        foreach ($reefless->scanDir(RL_TMP . 'compile') as $file) {
            if (in_array($file, ['index.html', '.htaccess'])) {
                continue;
            }

            @unlink(RL_TMP . 'compile' . RL_DS . $file);
        }
    }
}
