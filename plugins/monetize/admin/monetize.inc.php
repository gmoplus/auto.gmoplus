<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: MONETIZE.INC.PHP
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

if ($_GET['q'] == 'ext') {
    require_once '../../../includes/config.inc.php';
}

//Include modules
$module = isset($_GET['module']) ? $_GET['module'] : 'bump_up_plans';

if (is_file(RL_PLUGINS . 'monetize' . RL_DS . 'admin' . RL_DS . $module . '.inc.php')) {
    require_once(RL_PLUGINS . 'monetize' . RL_DS . 'admin' . RL_DS . $module . '.inc.php');
} else {
    $sError = true;
}

if ($result) {
    $reefless->loadClass('Notice');
    $rlNotice->saveNotice($message);
    $reefless->redirect($redirect_url);
}
