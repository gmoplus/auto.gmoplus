<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: REMOTE_STORAGE.INC.PHP
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

use Flynax\Plugins\RemoteStorage\Migration;
use Flynax\Plugins\RemoteStorage\Server;
use Flynax\Plugins\RemoteStorage\ServerResolver;
use Flynax\Utils\Valid;

require __DIR__ . '/../vendor/autoload.php';

if ($_GET['q'] === 'ext') {
    require '../../../includes/config.inc.php';
    require RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require RL_LIBS . 'system.lib.php';

    $server = new Server();
    if ($_GET['action'] === 'update' && (string) $_GET['field'] === 'Status') {
        $server->updateServerStatus((int) $_GET['id'], (string) $_GET['value']);
    } else {
        $server->apGetExtJsServers();
    }
} elseif ($_GET['mode'] === 'migration') {
    $migration = new Migration();
    $GLOBALS['rlSmarty']->assign('rsCountNotMigratedMedia', $migration->getCountNotMigratedMedia());
} else {
    $server      = new Server();
    $statuses    = Server::getServerStatuses();
    $types       = Server::getServerTypes();
    $credentials = Server::getAllServersCredentials();
    $lang        = $GLOBALS['lang'];
    $serverID    = (int) $_GET['id'] ?: 0;
    $serverInfo  = $serverID ? $server->getServerInfo($serverID) : [];

    $rsS3Regions = [];
    foreach ($types as $type) {
        $rsS3Regions[$type] = ServerResolver::getServer($type)::REGIONS;
    }

    $rsS3HiddenCredentials = [];
    foreach ($types as $type) {
        $rsS3HiddenCredentials[$type] = ServerResolver::getServer($type)::HIDDEN_CREDENTIALS;
    }

    $rsS3Guides = [];
    foreach ($types as $type) {
        $rsS3Guides[$type] = ServerResolver::getServer($type)::GUIDES ?? [];
    }

    switch ($_GET['action']) {
        case 'edit':
            $bcAStep =  $lang['edit'] . ' ' . mb_strtolower($lang['rs_bucket']) . ' "' . $serverInfo['Title'] . '"';
            break;
        case 'add':
            $bcAStep =  $lang['add'] . ' ' . mb_strtolower($lang['rs_bucket']);
            break;
    }

    if ($_GET['action'] === 'edit' && !isset($_POST['submit'])) {
        $_POST['title']  = $serverInfo['Title'];
        $_POST['type']   = $serverInfo['Type'];
        $_POST['bucket'] = $serverInfo['Bucket'];
        $_POST['status'] = $serverInfo['Status'];

        foreach ($serverInfo['Credentials'] as $credentialKey => $credential) {
            $_POST[$serverInfo['Type']][$credentialKey] = $credential;
        }
    }

    if (isset($_POST['submit'])) {
        $errors      = [];
        $error_fields = [];
        $type        = Valid::escape($_POST['type']);

        if (empty($title = Valid::escape($_POST['title']))) {
            $errors[]      = str_replace('{field}', "<b>{$lang['title']}</b>", $lang['notice_field_empty']);
            $error_fields[] = 'title';
        }

        foreach ($credentials[$type] as $credential) {
            if (empty($credentialValue = Valid::escape($_POST[$type][$credential]))
                && !in_array($credential, $rsS3HiddenCredentials[$type], true)
            ) {
                $phraseKey = 'rs_' . $type . '_' . $credential;
                if (!isset($lang[$phraseKey]) && false !== strpos($type, '_s3')) {
                    $phraseKey = 'rs_base_s3_' . $credential;
                }

                $errors[]      = str_replace('{field}', "<b>{$lang[$phraseKey]}</b>", $lang['notice_field_empty']);
                $error_fields[] = $type . '[' . $credential . ']';
            }
        }

        if (!$errors && $_GET['action'] === 'add') {
            $_POST['bucket'] = $server->createBucket($_POST, $errors);
        }

        if ($errors) {
            $GLOBALS['rlSmarty']->assign('errors', $errors);
        } elseif ($_GET['action'] === 'add' && $server->createServer($_POST, $credentials[$type])) {
            $GLOBALS['reefless']->loadClass('Notice');
            $GLOBALS['rlNotice']->saveNotice($lang['item_added']);
            $GLOBALS['reefless']->redirect(['controller' => $GLOBALS['controller']]);
        } elseif ($_GET['action'] === 'edit' && $server->updateServer($serverID, $_POST)) {
            $GLOBALS['reefless']->loadClass('Notice');
            $GLOBALS['rlNotice']->saveNotice($lang['item_edited']);
            $GLOBALS['reefless']->redirect(['controller' => $GLOBALS['controller']]);
        }
    }

    $GLOBALS['rlSmarty']->assign('rsStatuses', $statuses);
    $GLOBALS['rlSmarty']->assign('rsTypes', $types);
    $GLOBALS['rlSmarty']->assign('rsTypesCredentials', $credentials);
    $GLOBALS['rlSmarty']->assign('rsS3Regions', $rsS3Regions);
    $GLOBALS['rlSmarty']->assign('rsS3HiddenCredentials', $rsS3HiddenCredentials);
    $GLOBALS['rlSmarty']->assign('rsS3Guides', $rsS3Guides);

    if ($GLOBALS['config']['rs_main_server_down']) {
        $GLOBALS['rlSmarty']->assign('rsWarningAboutDownServer', $server->getWarningAboutDownServer());
    }
}
