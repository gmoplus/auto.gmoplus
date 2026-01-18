<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: METADATA.PHP
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

namespace Flynax\Plugins\AccountSync\Models;

use Flynax\Plugins\AccountSync\Helpers\DBHelper;

class MetaData extends DBHelper
{
    /**
     * Key of the account types cached data
     */
    const META_ACCOUNT_TYPES = 'account_types';

    /**
     * Key of the account fields cached data
     */
    const META_ACCOUNT_FIELDS = 'account_fields';

    const META_UNIQUE_USERS = 'unique_users';

    /**
     * Model is working with as_metadata table only
     */
    const WORKING_TABLE = 'as_metadata';

    /**
     * Working table + flynax prefix
     */
    const WORKING_TABLE_WITH_PREFIX = RL_DBPREFIX . 'as_metadata';

    /**
     * MetaData constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTable(self::WORKING_TABLE);
    }

    /**
     * Create table ot the plugin metadata
     */
    public function addTable()
    {
        $sql = sprintf("CREATE TABLE IF NOT EXISTS `%s` (
              `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `Domain` varchar(70) DEFAULT NULL,
              `Key` varchar(50) DEFAULT NULL,
              `Value` mediumtext DEFAULT NULL,
              PRIMARY KEY (`ID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
            self::WORKING_TABLE_WITH_PREFIX
        );
        $this->rlDb->query($sql);
    }

    /**
     * Drop working table from the database
     */
    public function dropTable()
    {
        $this->rlDb->dropTable(self::WORKING_TABLE);
    }

    /**
     * Set working table of the Model
     *
     * @param string $dbTable - With which table will work this model
     */
    private function setTable($dbTable)
    {
        $this->table = $dbTable;
        $this->tableWithPrefix = RL_DBPREFIX . $dbTable;
    }

    /**
     * Get metadata info by key and domain
     *
     * @param string $key
     * @param string $domain
     *
     * @return array
     */
    public function get($key, $domain = '')
    {
        $info = $this->getInfo($key, $domain);

        $values = array();

        if ($info['Value']) {
            return $info['Value'];
        }

        foreach ($info as $key => $item) {
            if (is_array($item) && is_array($item['Value'])) {
                $values[] = $item['Value'];
            }
        }

        return $values;
    }

    /**
     * Get metadata info by key and domain
     *
     * @param  string $key    - Meta key of which info do you want to get
     * @param string  $domain - Domain name if you want to get info by some domain and key
     *
     * @return mixed
     */
    public function getInfo($key, $domain = '')
    {
        $sql = sprintf("SELECT * FROM `%s` WHERE `Key` = '%s' ", self::WORKING_TABLE_WITH_PREFIX, $key);
        if ($domain) {
            $sql .= sprintf("AND `Domain` = '%s'", $domain);
        }

        $info = (array) $this->rlDb->getAll($sql);

        foreach ($info as $key => $item) {
            if ($item['Value']) {
                $info[$key]['Value'] = json_decode($item['Value'], true);
            }
        }

        return count($info) === 1 ? $info[0] : $info;
    }

    /**
     * Set metadata to the domain
     *
     * @param string $domain - URL of the domain, to which you want to add metadata
     * @param string $key    - Metadata key
     * @param mixed  $value  - Metadata value
     *
     * @return bool
     */
    public function set($domain, $key, $value)
    {
        if (!$domain || !$key) {
            return false;
        }

        $existingRow = $this->getInfo($key, $domain);

        $data = array(
            'Domain' => $domain,
            'Key' => $key,
            'Value' => json_encode($value),
        );

        return $existingRow['ID'] ? $this->updateMeta($existingRow['ID'], $data) : $this->newMeta($data);
    }

    /**
     * Update metadata of the plugin
     *
     * @param int    $byID
     * @param  array $newData
     *
     * @return bool
     */
    public function updateMeta($byID, $newData)
    {
        $byID = (int) $byID;
        if (!$byID || !is_array($newData)) {
            return false;
        }

        $fields = array(
            'Domain' => $newData['Domain'],
            'Value' => $newData['Value'],
        );
        if ($newData['Key']) {
            $fields['Key'] = $newData['Key'];
        }

        $update = array(
            'fields' => $fields,
            'where' => array(
                'ID' => $byID,
            ),
        );

        return $this->rlActions->updateOne($update, self::WORKING_TABLE, array('Value'));
    }

    /**
     * Add new metadata to the plugin
     *
     * @param array $newData - Required fields: Domain, Value, Key
     *
     * @return bool
     */
    private function newMeta($newData)
    {
        $requiredFields = array('Domain', 'Value', 'Key');
        $insert = [];
        $isValid = true;

        foreach ($requiredFields as $field) {
            if (!$newData[$field]) {
                $isValid = false;
                break;
            }

            $insert[$field] = $newData[$field];
        }

        if (!$isValid) {
            return false;
        }

        return $this->rlActions->insertOne($insert, self::WORKING_TABLE, array('Value'));
    }

    /**
     * Remove MetaData by domain and key
     *
     * @param string $domain
     * @param string $key
     */
    public function delete($domain, $key)
    {
        $sql = sprintf("DELETE FROM `%s` WHERE `Key` = '%s' AND `Domain` = '%s'", self::WORKING_TABLE_WITH_PREFIX, $key, $domain);
        $this->rlDb->query($sql);
    }
}
