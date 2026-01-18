<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : UID.PHP
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

namespace Flynax\Plugins\HybridAuth;

class Uid
{
    /**
     * Class is working with this table
     */
    const WORKING_TABLE = 'ha_uids';

    /**
     * @var string - Table name with prefix
     */
    protected $tableWithPrefix;

    /**
     * @var \rlDb
     */
    protected $rlDb;

    /**
     * @var \rlActions
     */
    protected $rlActions;

    /**
     * Uids constructor.
     */
    public function __construct()
    {
        $this->rlActions = hybridAuthMakeObject('rlActions');
        $this->rlDb = hybridAuthMakeObject('rlDb');
        $this->tableWithPrefix = RL_DBPREFIX . self::WORKING_TABLE;
    }

    /**
     * Set account type to Verified by provider
     *
     * @param  int    $userID
     * @param  string $provider - Provider name
     * @return bool
     */
    public function verifyUserByProvider($userID, $provider)
    {
        if (!$userID || !$provider) {
            return false;
        }

        return $this->verifyUserWhere(array(
            'Account_ID' => (int) $userID,
            'Provider' => $provider,
        ));
    }

    /**
     * Set account type to Verified by ID
     *
     * @param  int    $accountID
     * @return bool
     */
    public function verifyUserByID($accountID)
    {
        if (!$accountID) {
            return false;
        }

        return $this->verifyUserWhere(array(
            'Account_ID' => (int) $accountID,
        ));
    }

    /**
     * Verify all user providers if one where verified before
     *
     * @param  $accountID
     * @return bool
     */
    public function verifyAllUserProviders($accountID)
    {
        return $this->verifyUserByID($accountID);
    }

    /**
     * Verify user by given fields
     *
     * @param  array $where - fields by which you want to verify user
     * @return bool
     */
    public function verifyUserWhere($where)
    {
        if (empty($where)) {
            return false;
        }

        $update = array(
            'fields' => array(
                'Verified' => '1',
            ),
            'where' => $where,
        );

        return $this->rlActions->updateOne($update, 'ha_uids');
    }

    /**
     * Get UID info by provider and account ID
     *
     * @param  string $provider  - Provider name
     * @param  int    $accountID
     * @return array             - UID information
     */
    public function getByProviderAndAccount($provider, $accountID)
    {
        $sql = "SELECT * FROM `{$this->tableWithPrefix}` WHERE `Account_ID` = {$accountID} ";
        $sql .= "AND `Provider` = '{$provider}'";
        return (array) $this->rlDb->getRow($sql);
    }

    /**
     * Add UID info row to the user
     *
     * @param  array $data - User info
     * @return array       - Added UID row
     */
    public function add($data)
    {
        if (!empty($this->getByProviderAndAccount($data['Provider'], $data['Account_ID']))) {
            return array();
        }

        $this->rlActions->insertOne($data, self::WORKING_TABLE);

        return $this->get($data['UID']);
    }

    /**
     * Remove UID info by Row ID
     *
     * @param  int $rowID
     * @return bool
     */
    public function remove($rowID)
    {
        if (!$rowID) {
            return false;
        }

        return (bool) $this->removeUIDBy('ID', $rowID);
    }

    /**
     * Remove UID info by UID
     *
     * @since 2.1.1
     *
     * @param  int $uid
     * @return bool
     */
    public function removeByUID($uid)
    {
        if (!$uid) {
            return false;
        }

        return (bool) $this->removeUIDBy('UID', $uid);
    }

    /**
     * Remove UID row by provided column and value
     *
     * @param  string $column          - working table column name
     * @param  string $value           - working table column value
     * @param  bool   $isStringColumn  - is provided column is string
     * @return bool                    - Is everything removed correctly
     */
    private function removeUIDBy($column, $value, $isStringColumn = false)
    {
        if (!$column || !$value) {
            return false;
        }

        $preparedValue = $isStringColumn ? "'{$value}'" : $value;
        $sql = sprintf("DELETE FROM `%s` WHERE `%s` = %s", $this->tableWithPrefix, $column, $preparedValue);
        return $this->rlDb->query($sql);
    }

    /**
     * Remove all UID info by Account ID
     * Helper of the removeUIDBy method
     *
     * @param  int $ID
     * @return bool    - Does everyhing removed correctly
     */
    public function removeByAccountID($ID)
    {
        if (!$ID) {
            return false;
        }

        return $this->removeUIDBy('Account_ID', $ID);
    }

    /**
     * Remove all UID info by Provider
     * Helper of the removeUIDBy method
     *
     * @param  string $provider - Provider key
     * @return bool             - Does everyhing removed correctly
     */
    public function removeByProvider($provider)
    {
        if (!$provider) {
            return false;
        }

        return $this->removeUIDBy('Provider', $provider);
    }

    /**
     * Get UID info by name
     *
     * @param  string $uid - Unique ID of the user
     * @return array       - UID info
     */
    public function get($uid = '')
    {
        if (!$uid) {
            return array();
        }

        $sql = "SELECT * FROM `{$this->tableWithPrefix}` WHERE `UID` = '{$uid}'";
        $uidArray = (array) $this->rlDb->getRow($sql);

        if (!$this->isExistsAccount($uidArray['Account_ID'], $uid)) {

          return array();
        }

        return $uidArray;
    }

    /**
      * Checking existence of account records in the table if record not found delete and return false
      *
      * @since 2.1.1
      *
      * @param  int $accountID
      * @param  string $uid  -  user social network identifier
      * @return bool
      */
    public function isExistsAccount($accountID, $uid)
    {
        if (!$accountID) {
            return false;
        }
        $isAccountExist = (bool) $this->rlDb->getOne('ID', "`ID` = '{$accountID}' ", 'accounts');

        if (!$isAccountExist) {
           $this->removeByUID($uid);
           return false;
        }
        return true;
    }


    /**
     * Does provided account was already verified account before using different social networks
     *
     * @param  int $accountID
     * @return bool
     */
    public function isAlreadyVerified($accountID)
    {
        if (!$accountID) {
            return false;
        }
        $sql = "SELECT `ID` FROM `{$this->tableWithPrefix}` WHERE `Verified` = '1' AND `Account_ID` = {$accountID}";

        return (bool) $this->rlDb->getRow($sql);
    }

}
