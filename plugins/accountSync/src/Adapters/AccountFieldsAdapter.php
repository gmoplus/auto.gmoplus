<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: ACCOUNTFIELDSADAPTER.PHP
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
 * All related logic with account fields
 *
 * @package Flynax\Plugins\AccountSync\Adapters
 */
class AccountFieldsAdapter extends DBHelper
{
    /**
     * Get account field info by key
     *
     * @param $key
     * @return mixed
     */
    public static function getByKey($key)
    {
        if (!$key) {
            return array();
        }

        $self = new self();
        /** @var \rlLang $rlLang */
        $rlLang = asMake('rlLang');

        $sql = "SELECT * FROM `" . RL_DBPREFIX . "account_fields` WHERE `Key` = '{$key}' ";
        $fieldInfo = $self->rlDb->getRow($sql);
        $fieldInfo = $rlLang->replaceLangKeys($fieldInfo, 'account_fields', array('name'));

        return $fieldInfo;
    }

    /**
     * Get all synchronized account fields by account type
     *
     * @param $accountTypeKey
     *
     * @return array
     */
    public static function getSyncFieldsByType($accountTypeKey)
    {
        if (!$accountTypeKey) {
            return array();
        }

        $meta = new MetaData();
        $syncAccountFields = $meta->get(MetaData::META_ACCOUNT_FIELDS, RL_URL_HOME);

        return $syncAccountFields[$accountTypeKey] ? array_column($syncAccountFields[$accountTypeKey], 'Key') : '';
    }
}
