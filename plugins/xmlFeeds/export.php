<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: EXPORT.PHP
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

require_once dirname(dirname(__DIR__)) . '/includes/config.inc.php';

$filename        = RL_CACHE . 'xml_' . md5(serialize($_GET));
$fmtime          = is_file($filename) ? filemtime($filename) : false;
$expiration_time = 60 * 60 * 6; // 6 hours

if (!$fmtime || ($fmtime + $expiration_time < time())) {
    require_once('control.inc.php');

    define('RL_LANG_CODE', $config['lang']);

    $lang       = $rlLang->getLangBySide('frontEnd', $config['lang']);
    $format     = $rlValid->xSql($_GET['format']);
    $formatInfo = $rlDb->fetch('*', ['Key' => $format], null, null, 'xml_formats', 'row');
    unset($_GET['format']);

    if (!$formatInfo) {
        $rlDb->connectionClose();
        header('HTTP/1.0 404 Not Found');
        echo $lang['error_404'];
        exit;
    } else {
        $limit = (int) $_GET['limit'] ?: 100;
        $where = [];

        if ($_GET['account_id']) {
            $username  = $rlValid->xSql($_GET['account_id']);
            $accountID = (int) $rlDb->getOne('ID', "`Username` = '{$username}'", 'accounts');

            if ($accountID) {
                $where['account_id'] = $accountID;
            }
        }

        if ($_GET['listing_type']) {
            $where['listing_type'] = $rlValid->xSql($_GET['listing_type']);
        }

        $reefless->loadClass('XmlExport', null, 'xmlFeeds', $formatInfo);

        $fp = fopen($filename, 'w+');
        $rlXmlExport->export($fp, $where, $limit);
        fclose($fp);
    }

    $rlDb->connectionClose();
}

$fp = fopen($filename, 'rb');
header('Content-Type: text/xml; charset=utf-8');
header('Content-Length: ' . filesize($filename));
fpassthru($fp);
fclose($fp);
exit;
