<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.7.0
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: DBHELPER.PHP
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

namespace  Flynax\Plugins\AccountSync\Helpers;

class DBHelper
{
    /**
     * @var \rlDb
     */
    protected $rlDb;

    /**
     * @var \rlActions
     */
    protected $rlActions;

    /**
     * @var string - Db helper will work with this table
     */
    protected $table;

    /**
     * @var string - Table with Flynax
     */
    protected $tableWithPrefix;

    /**
     * DB constructor.
     */
    public function __construct()
    {
        $this->rlActions = asMake('rlActions');
        $this->rlDb = asMake('rlDb');
    }

    /**
     * Search something by key
     *
     * @param string $key   - Will search by this column
     * @param string $value - Will search by this value
     *
     * @return array - Result of the searching process
     */
    public function searchBy($key, $value)
    {
        $sql = sprintf("SELECT * FROM `%s` WHERE `%s` = '%s'", $this->tableWithPrefix, $key, $value);
        $result = $this->rlDb->getAll($sql);

        return count($result) == 1 ? $result[0] : $result;
    }
}
