<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLAUTOPOSTER.CLASS.PHP
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
 *  Flynax Classifieds Software 2024 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

const CRON_FILE = true;
set_time_limit(0);

try {
    require_once dirname(__DIR__, 3) . '/includes/config.inc.php';
    require_once RL_INC . '/control.inc.php';
    require_once dirname(__DIR__) . '/vendor/autoload.php';

    $config = $rlConfig->allConfig();
    require_once RL_LIBS . 'system.lib.php';
    define('RL_LANG_CODE', $config['lang']);

    if ($config['ap_own_cron']) {
        $reefless->loadClass('AutoPoster', null, 'autoPoster');
        $rlAutoPoster->postListings($config['ap_cron_ads_limit']);
    }
} catch (Exception $e) {
    $GLOBALS['rlDebug']->logger('AutoPoster (cron): ' . $e->getMessage());
} finally {
    $rlDb->connectionClose();
}
