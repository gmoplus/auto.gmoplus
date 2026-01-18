<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: USERADAPTER.PHP
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

/**
 * All users related logic should be described here
 *
 * @package Flynax\Plugins\AccountSync\Adapters
 */
class UserAdapter extends DBHelper
{
    /**
     * @var \rlValid
     */
    private $rlValid;

    /**
     * @var array
     */
    public $availableStatuses = array('active', 'approval', 'pending');

    /**
     * UserAdapter constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->rlValid = asMake('rlValid');

        if ($GLOBALS['config']['trash']) {
            $this->availableStatuses[] = 'trash';
        }
    }

    public static function getUserInfoByAllDomain($mail, $allUsersInfos, $main_domain = false)
    {
        $uniqueFor = $existInDomains = array();

        if ($main_domain) {
            foreach ($allUsersInfos as $domain => $users) {
                if (!in_array($mail, array_column($users, 'Mail'))) {
                    $uniqueFor[] = $domain;
                }

                if (in_array($mail, array_column($users, 'Mail'))) {
                    $existInDomains[] = $domain;
                }
            }
        } else {
            $domain = RL_URL_HOME;
            $users = $allUsersInfos[$domain];
            if (!in_array($mail, array_column($users, 'Mail'))) {
                $uniqueFor[] = $domain;
            }

            if (in_array($mail, array_column($users, 'Mail'))) {
                $existInDomains[] = $domain;
            }
        }

        $info = array(
            'found_times' => count($existInDomains),
            'exist_in' => $existInDomains,
            'unique_for' => $uniqueFor,
        );

        return $info;
    }

    /**
     * Does user with email exist
     *
     * @param $email
     *
     * @return bool
     */
    public function isEmailExist($email)
    {
        if (!$email) {
            return false;
        }

        $email = $this->rlValid->xSql($email);
        return (bool) $this->rlDb->getOne('ID', "`Mail` = '{$email}'", 'accounts');
    }

    /**
     * Does user with username exist
     *
     * @param $username
     *
     * @return bool
     */
    public function isUsernameExist($username)
    {
        if (!$username) {
            return false;
        }

        $username = $this->rlValid->xSql($username);
        return (bool) $this->rlDb->getOne('ID', "`Username` = '{$username}'", 'accounts');
    }

    /**
     * Get user information by email
     *
     * @param $email
     *
     * @return mixed
     */
    public function getInfoByEmail($email)
    {
        if (!$email) {
            return array();
        }

        $email = $this->rlValid->xSql($email);
        $sql = sprintf("SELECT * FROM `%saccounts` WHERE `Mail` = '%s'", RL_DBPREFIX, $email);

        return $this->rlDb->getRow($sql);
    }

    /**
     * Deactivate user by provided email
     * Helper of the `changeUserStatus()` method
     *
     * @param string $email - Email
     *
     * @return bool
     */
    public function deactivateUser($email)
    {
        return $this->changeUserStatus($email, 'approval');
    }

    /**
     * Activate user by provided email
     * Helper of the `changeUserStatus()` method
     *
     * @param string $email
     *
     * @return bool
     */
    public function activateUser($email)
    {
        return $this->changeUserStatus($email, 'activate');
    }

    /**
     * Change user status of the user by provided email
     *
     * @param string $email
     * @param string $status - Available statuses: active, approval, pending, trash
     *
     * @return bool
     */
    public function changeUserStatus($email, $status)
    {
        $status = strtolower($status);

        if (!$email || !$status || !$this->isValidStatus($status)) {
            return false;
        }

        $email = $this->rlValid->xSql($email);

        $sql = "UPDATE `" . RL_DBPREFIX . "accounts` SET `Status` = '{$status}' ";
        $sql .= "WHERE `Mail` = '{$email}'";
        $out = $this->rlDb->query($sql);

        $account_id = $this->rlDb->getOne('ID', "`Mail` = '{$email}'", 'accounts');
        $GLOBALS['rlListings']->listingStatusControl(array('Account_ID' => $account_id), $status);

        return $out;
    }

    /**
     * Does provided status is valid for using in Flynax
     *
     * @param string $status
     *
     * @return bool
     */
    public function isValidStatus($status)
    {
        return in_array($status, $this->availableStatuses);
    }

    /**
     * Add column to the {db_prefix}accounts table
     *
     * @param string $columnKey
     * @return bool
     */
    public function addColumnToTable($columnKey)
    {
        $columnKey = (string) $columnKey;
        if (!$columnKey) {
            return false;
        }

        $sql = "ALTER TABLE `" . RL_DBPREFIX . "accounts` ADD COLUMN {$columnKey} VARCHAR(100)";
        return $this->rlDb->query($sql);
    }

    /**
     *
     * @param int $start
     * @param int $end
     *
     * @return array
     */
    public function getUsers($start = 0, $end = 0)
    {
        $start = (int) $start;
        $end = (int) $end;

        $limit = $end ? "{$start},{$end}" : ($start ?: '');
        $limit = $limit ? "LIMIT {$limit}" : '';
        $sql = "SELECT SQL_CALC_FOUND_ROWS `Mail`, `Type` FROM `" . RL_DBPREFIX . "accounts` {$limit}";

        return array(
            'users' => (array) $this->rlDb->getAll($sql),
            'total' => (int) $this->rlDb->getRow('SELECT FOUND_ROWS() AS `count`', 'count'),
        );
    }
}
