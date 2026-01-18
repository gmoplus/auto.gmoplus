<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: PRODUCT_DOWNLOAD.PHP
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

if (!$_GET['r']) {
    exit;
}

require_once '../../includes/config.inc.php';
require_once RL_INC . 'control.inc.php';

$request = explode('|', base64_decode($_GET['r']));

if ($request[0] != md5($_SESSION['account']['ID']) && $request[0] != md5($_SESSION['sessAdmin']['user_id'])) {
    exit;
}

$file = base64_decode($_SESSION['shcDownloadFile']);

if (file_exists($file)) {
    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . basename($file));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));

    if ($fd = fopen($file, 'rb')) {
        while (!feof($fd)) {
            print fread($fd, 1024);
        }
        fclose($fd);
    }
}

unset($_SESSION['shcDownloadFile']);
$rlDb->connectionClose();
exit;
