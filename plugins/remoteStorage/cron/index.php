<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: INDEX.PHP
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

use Flynax\Plugins\RemoteStorage\Server;
use Flynax\Plugins\RemoteStorage\Migration;

const CRON_FILE = true;
set_time_limit(0);

try {
    require_once dirname(__DIR__, 3) . '/includes/config.inc.php';
    require_once RL_INC . '/control.inc.php';
    require_once dirname(__DIR__) . '/vendor/autoload.php';

    $GLOBALS['config'] = $GLOBALS['rlConfig']->allConfig();

    $bucket = (new Server())->getServerInfo((int) $GLOBALS['config']['rs_main_server']);

    if (!$bucket || $bucket['Status'] !== Server::ACTIVE_STATUS) {
        exit;
    }

    (new Migration())->moveFiles();
} catch (Exception $e) {
    $GLOBALS['rlDebug']->logger('Remote Storage (cron): ' . $e->getMessage());
} finally {
    $rlDb->connectionClose();
}
