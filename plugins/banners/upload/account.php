<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: ACCOUNT.PHP
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

require __DIR__ . '/../../../includes/config.inc.php';
require RL_INC . 'control.inc.php';

$config = $rlConfig->allConfig();

$reefless->loadClass('Account');

$banner_id = (int) $_SESSION['edit_banner']['banner_id']
    ?: (int) $_SESSION['add_banner']['banner_id'];

$account_info = $_SESSION['account'];

if (!$banner_id) {
    exit;
} elseif (!$rlAccount->isLogin()) {
    exit;
} elseif ($account_info['ID'] != $rlDb->getOne('Account_ID', "`ID` = {$banner_id}", 'banners')) {
    exit;
}

require RL_PLUGINS . 'banners/upload/upload.php';

$rlDb->connectionClose();
