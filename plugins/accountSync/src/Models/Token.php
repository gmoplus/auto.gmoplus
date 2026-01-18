<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.7.0
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: TOKEN.PHP
 *
 *	The software is a commercial product delivered under single, non-exclusive,
 *	non-transferable license for one domain or IP address. Therefore distribution,
 *	sale or transfer of the file in whole or in part without permission of Flynax
 *	respective owners is considered to be illegal and breach of Flynax License End
 *	User Agreement.
 *
 *	You are not allowed to remove this information from the file without permission
 *	of Flynax respective owners.
 *
 *	Flynax Classifieds Software 2022 |  All copyrights reserved.
 *
 *	https://www.flynax.com/
 *
 ******************************************************************************/

namespace  Flynax\Plugins\AccountSync\Models;

class Token
{
    /**
     * Model is working only with this table
     */
    const WORKING_TABLE = 'as_tokens';

    /**
     * Working table with Flynax prefix
     */
    const WORKING_TABLE_WITH_PREFIX = RL_DBPREFIX . 'as_tokens';


    /**
     * @var \rlDb
     */
    private $rlDb;

    /**
     * Token constructor.
     */
    public function __construct()
    {
        $this->rlDb = $GLOBALS['rlDb'];
    }


    /**
     * Create working table in the database
     */
    public function addTable()
    {
        $sql = sprintf("CREATE TABLE IF NOT EXISTS `%s` (
              `ID` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `Domain` VARCHAR(100) NOT NULL,
              `Token` varchar(100) DEFAULT ''
            ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;
        ", self::WORKING_TABLE_WITH_PREFIX);
        $this->rlDb->query($sql);
    }

    /**
     * Delete working table from database
     */
    public function deleteTable()
    {
        $this->rlDb->dropTable(self::WORKING_TABLE);
    }

    /**
     * Add and attach token to the specific domain
     *
     * @param string $token  - Account sync plugin token
     * @param string $domain - Domain which with will be associated this token
     *
     * @return bool
     */
    public function add($token, $domain)
    {
        if (!$token || !$domain) {
            return false;
        }

        $new = array(
            'Token' => $token,
            'Domain' => $domain,
        );

        return (bool) $this->rlDb->insertOne($new, self::WORKING_TABLE);
    }

    /**
     * Delete token info by it's row in the working table
     *
     * @param int $id
     *
     * @return bool
     */
    public function delete($id)
    {
        $id = (int) $id;
        if (!$id) {
            return false;
        }

        $sql = sprintf("DELETE FROM `%s` WHERE `ID` = %d", self::WORKING_TABLE_WITH_PREFIX, $id);
        return (bool) $this->rlDb->query($sql);
    }

    /**
     * Get token by domain
     *
     * @param string $domain - Domain
     * @return string
     */
    public function findByDomain($domain)
    {
        if (!$domain) {
            return '';
        }

        $where = "`Domain` = '{$domain}'";
        return (string) $this->rlDb->getOne('Token', $where, self::WORKING_TABLE);
    }

    /**
     * Check does token is exist in the working table
     *
     * @param string $token
     * @return string
     */
    public function findByToken($token)
    {
        return $this->findBy('Token', $token);
    }

    /**
     * Find field by value in the working table
     *
     * // todo: Check, where I'm using it and does this kind of searching is relevant
     *
     * @param string $field - Looking table column name from the working table
     * @param string $value - Looking value of the provided column
     *
     * @return string
     */
    private function findBy($field, $value)
    {
        $field = ucfirst($field);
        if (!in_array($field, array('Token', 'Domain'))) {
            return '';
        }

        $where = "`{$field}` = '{$value}'";

        return (string) $this->rlDb->getOne($field, $where, self::WORKING_TABLE);
    }

    /**
     * Get current Flynax installation token
     *
     * @return string
     */
    public function getCurrentSiteToken()
    {
        $where = sprintf("`Domain` = '%s'", RL_URL_HOME);
        return $this->rlDb->getOne('Token', $where, self::WORKING_TABLE) ?: '';
    }

    /**
     * Generate token
     *
     * @param int $length - Generated token length
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function generateToken($length = 32)
    {
        $self = new self();
        if ($token = $self->findByDomain(RL_URL_HOME)) {
            return $token;
        }

        $token = function_exists('random_bytes')
            ? bin2hex(random_bytes($length))
            : bin2hex(openssl_random_pseudo_bytes($length));
        $self->add($token, RL_URL_HOME);

        return $token;
    }

    /**
     * Get all tokens info
     *
     * @param bool $excludeOurDomain - Should method exclude current installation token info
     *
     * @return array
     */
    public function getAll($excludeOurDomain = false)
    {
        $sql = sprintf("SELECT * FROM `%s` ", self::WORKING_TABLE_WITH_PREFIX );
        $sql .= $excludeOurDomain ? sprintf("WHERE `Domain` != '%s'", RL_URL_HOME) : '';

        return $this->rlDb->getAll($sql);
    }

    /**
     * Does provided token is exist in the working table
     *
     * @param string $token
     *
     * @return bool
     */
    public function isTokenExist($token)
    {
        return (bool) $this->findByToken($token);
    }

    /**
     * Get token info by its value
     *
     * @param string $token
     * @return array
     */
    public function getInfoByToken($token)
    {
        if (!$token) {
            return array();
        }

        $sql = sprintf("SELECT * FROM `%s` WHERE `Token` = '%s'", self::WORKING_TABLE_WITH_PREFIX, $token);
        return (array) $this->rlDb->getRow($sql);
    }

    /**
     * Get token info by domain
     *
     * @param string $domain - Associated domain
     * @return array
     */
    public function getInfoByDomain($domain)
    {
        if (!$domain) {
            return array();
        }

        $sql = sprintf("SELECT * FROM `%s` WHERE `Domain` = '%s'", self::WORKING_TABLE_WITH_PREFIX, $domain);
        return (array) $this->rlDb->getRow($sql);
    }

    /**
     * Get token info by it's row id in working tables
     *
     * @param int $id
     *
     * @return array
     */
    public function getInfoByID($id)
    {
        $id = (int) $id;
        if (!$id) {
            return array();
        }

        $sql = sprintf("SELECT * FROM `%s` WHERE `ID` = '%d'", self::WORKING_TABLE_WITH_PREFIX, $id);
        return (array) $this->rlDb->getRow($sql);
    }
}
