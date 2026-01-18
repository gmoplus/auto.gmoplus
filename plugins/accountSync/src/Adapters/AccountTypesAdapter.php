<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: ACCOUNTTYPESADAPTER.PHP
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

namespace Flynax\Plugins\AccountSync\Adapters;

use Flynax\Plugins\AccountSync\Helpers\DBHelper;
use Flynax\Plugins\AccountSync\Models\MetaData;

/**
 * All related with account types logic
 *
 * @package Flynax\Plugins\AccountSync\Adapters
 */
class AccountTypesAdapter extends DBHelper
{
    /**
     * Table with which is working this adapter
     */
    const TABLE = 'account_types';

    /**
     * Table with Flynax prefix at the beginning
     */
    const TABLE_WITH_PREFIX = RL_DBPREFIX . 'account_types';

    /**
     * Create new account type
     *
     * @param array $data - New account type data (check fl_account_types table to check all incoming parameters)
     *
     * @return bool
     */
    public function create($data)
    {
        if (self::getInfoByKey($data['key'])) {
            return false;
        }

        $position = $this->rlDb->getRow(sprintf("SELECT MAX(`Position`) AS `max` FROM `%s`", self::TABLE_WITH_PREFIX));
        $new = array(
            'Key' => $data['key'],
            'Position' => $position['max'] + 1,
            'Abilities' => $data['abilities'] ?: '',
            'Page' => $data['page'] ?: 0,
            'Own_location' => $data['own_location'] ?: 0,
            'Email_confirmation' => $data['email_confirmation'] ?: 0,
            'Admin_confirmation' => $data['admin_confirmation'] ?: 0,
            'Auto_login' => $data['auto_login'] ?: 0,
            'Status' => $data['status'] ?: 'active',
            'Featured_blocks' => $data['featured_blocks'] ?: 0,
        );

        if ($data['thumb_width']) {
            $new['Thumb_width'] = $data['thumb_width'];
        }
        if ($data['thumb_height']) {
            $new['Thumb_height'] = $data['thumb_height'];
        }

        if ($this->rlDb->insertOne($new, self::TABLE)) {

            $newLang = array(
                'Code' => RL_LANG_CODE,
                'Module' => 'common',
                'Key' => sprintf('account_types+name+%s', $data['key']),
                'Value' => $data['name'],
                'Status' => $new['Status'],
            );

            return $this->rlDb->insertOne($newLang, 'lang_keys');
        }

        return false;
    }

    /**
     * Get account type info by its Key
     *
     * @param string $key - Key of the account type which info you want to get
     *
     * @return mixed
     */
    public static function getInfoByKey($key)
    {
        $self = new self();

        return $key ? $self->getInfoBy('key', $key) : array();
    }

    /**
     * Get account type info by its ID
     *
     * @param int $id - ID of the account type which info you want to get
     *
     * @return mixed
     */
    public static function getInfoByID($id)
    {
        $self = new self();
        $id = (int) $id;

        return $id ? $self->getInfoBy('id', $id) : array();
    }

    /**
     * Get all account types directly from DataBase
     *
     * @return array
     */
    public static function fetchAllTypes()
    {
        $self = new self();

        $sql = sprintf("SELECT `Key`, `ID` FROM `%s`", self::TABLE_WITH_PREFIX);
        $accountTypes = $self->rlDb->getAll($sql);
        $result = array();

        foreach ($accountTypes as $key => $type) {
            if ($type['Key'] != 'visitor') {
                $where = sprintf("`Key` = 'account_types+name+%s' AND `Code` = '%s'", $type['Key'], RL_LANG_CODE);
                $type['name'] = (string) $self->rlDb->getOne('Value', $where, 'lang_keys');
                $result[$type['Key']] = $type;
            }
        }

        return $result;
    }

    /**
     * Get all types (rlAccountTypes->types) helper
     *
     * @return array
     */
    public static function getAllTypes()
    {
        $result = array();

        /** @var \rlAccountTypes $rlAccountTypes */
        $rlAccountTypes = asMake('rlAccountTypes');
        $types = $rlAccountTypes->types;

        foreach ($types as $type) {
            if ($type['Key'] != 'visitor') {
                $result[$type['Key']] = array(
                    'ID' => $type['ID'],
                    'Key' => $type['Key'],
                    'name' => $type['name'],
                );
            }
        }

        return $result;
    }

    /**
     * Get info by provided type and its value
     *
     * @param string $type - By what fields should I get info: id, key
     * @param string $value - Searching value
     *
     * @return mixed
     */
    private function getInfoBy($type, $value)
    {
        if (!in_array($type, array('id', 'key'))) {
            return false;
        }
        /** @var \rlLang $rlLang */
        $rlLang = asMake('rlLang');

        $where = $type == 'id' ? "`ID` = {$value}" : "`Key` = '{$value}'";
        $sql = sprintf("SELECT * FROM `%s` WHERE %s", self::TABLE_WITH_PREFIX, $where);
        $info = $this->rlDb->getRow($sql);
        $info = $rlLang->replaceLangKeys($info, 'account_types', array('name'));

        return $info;
    }

    /**
     * Does provided account type is exist
     *
     * @param $key - Account type key
     * @return bool
     */
    public function isAccountTypeExist($key)
    {
        return $key ? (bool) $this->rlDb->getOne('ID', "`Key` = '{$key}'", self::TABLE) : false;
    }

    /**
     * Does provided account type is synchronized with some specific domain
     *
     * @param string $key - Account type key
     * @param string $domain - Domain name from synchronized domains pool
     *
     * @return bool
     */
    public static function isSynchronizedWithDomain($key, $domain)
    {
        $meta = new MetaData();
        $allAccountTypes = $meta->get(MetaData::META_ACCOUNT_TYPES, $domain);
        return in_array($key, array_keys($allAccountTypes));
    }

    public static function getUniqueAccountTypes()
    {
        $meta = new MetaData();
        $accountTypes = array_filter($meta->get('account_types'));

        $uniqueTypes = self::getAllTypes();

        if (isset($accountTypes[0])) {
            foreach ($accountTypes as $type) {
                $uniqueTypes += $type;
            }
        }

        return array_values($uniqueTypes);
    }
}
