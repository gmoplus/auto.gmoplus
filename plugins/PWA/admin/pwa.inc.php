<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLPWA.CLASS.PHP
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

use Flynax\Plugins\PWA\PWA;
use Flynax\Plugins\PWA\Config;
use Flynax\Plugins\PWA\Events;
use Flynax\Plugins\PWA\Files\Icon;
use Flynax\Plugins\PWA\Statistics;
use Flynax\Utils\Util;

if ($_GET['mode'] && $_GET['mode'] === 'usage') {
    if ($_GET['q'] === 'ext') {
        require '../../../includes/config.inc.php';
        require RL_ADMIN_CONTROL . 'ext_header.inc.php';
        require RL_LIBS . 'system.lib.php';

        $reefless->loadClass('PWA', null, 'PWA');

        $pwaStat   = new Statistics();
        $usageStat = $pwaStat->getStat();

        echo json_encode($usageStat);
    } else {
        $bcAStep = $lang['pwa_usage_stat'];
    }
} else {
    $bcAStep = $lang['settings'];

    $reefless->loadClass('PWA', null, 'PWA');
    $reefless->loadClass('Account');

    $allLangs = $GLOBALS['languages'];
    $rlSmarty->assign_by_ref('allLangs', $allLangs);

    $pwa             = new PWA();
    $multiLangFields = Config::i()->multiLangConfigs;
    $iconExist       = Config::i()->getConfig('pwa_icon');
    $action          = $iconExist ? 'update' : 'add';
    $screenExist     = Icon::getImages()['2048x2732'];

    if (!$pwa->isPhpCompatible()) {
        $reefless->loadClass('Notice');
        $GLOBALS['rlNotice']->saveNotice($lang['pwa_php_wrong'], 'errors');
    }

    if ($iconExist) {
        $icons = Icon::getImages('icon');
        $rlSmarty->assign('icon_exist', PWA_ROOT_URL . 'files/' . $icons['512x512']);
    }

    if ($screenExist) {
        $rlSmarty->assign('screen_exist', PWA_ROOT_URL . 'files/' . $screenExist);
    }

    if (!$_POST['submit']) {
        if ($iconExist) {
            foreach ($multiLangFields as $field) {
                $keyPattern = sprintf('%s+%s', PWA_APP_KEY, $field);

                $where = ['Plugin' => 'PWA', 'Key' => $keyPattern];
                $fieldLangKeys = $rlDb->fetch('*', $where, "AND `Status` <> 'trash'", false, 'lang_keys');

                foreach ($fieldLangKeys as $langKey) {
                    $_POST[$field][$langKey['Code']] = $langKey['Value'];
                }
            }

            $_POST['color']          = $GLOBALS['rlConfig']->getConfig('pwa_color');
            $_POST['maskable_icons'] = $GLOBALS['rlConfig']->getConfig('pwa_maskable_icons');
        }
    } else {
        $errors = $langKeys = $updateKeys = [];

        foreach ($multiLangFields as $field) {
            foreach ($allLangs as $key => $value) {
                if (!$_POST[$field][$key]) {
                    $find = '{field}';
                    $replace = "<b>" . $lang['pwa_' . $field] . "({$key})</b>";
                    $errors[] = str_replace('{field}', $replace, $lang['notice_field_empty']);
                    $error_fields[] = sprintf('%s[%s]', $field, $key);

                    continue;
                }
                $langKey = [
                    'Code' => $allLangs[$key]['Code'],
                    'Module' => 'common',
                    'Status' => 'active',
                    'Key' => sprintf('%s+%s', PWA_APP_KEY, $field),
                    'Plugin' => 'PWA',
                    'Value' => $_POST[$field][$key],
                ];

                if ($action == 'add') {
                    $langKeys[] = $langKey;
                } else {
                    $updateKeys[] = [
                        'fields' => $langKey,
                        'where' => [
                            'Key' => $langKey['Key'],
                            'Plugin' => 'PWA',
                            'Code' => $langKey['Code'],
                        ],
                    ];
                }
            }
        }

        if (empty($_FILES['pwa-icon']['tmp_name']) && !$iconExist) {
            $errors[] = $lang['pwa_missing_image'];
            $error_fields[] = 'pwa-icon';
        }

        if (!empty($_FILES['pwa-icon']['tmp_name'])) {
            $icon = new Icon();
            $icon->setImage($_FILES['pwa-icon']['tmp_name']);
            $errors += $icon->validate();
        }

        if (!empty($_FILES['portrait-launch-screen']['tmp_name'])) {
            $icon = new Icon();
            $icon->setImage($_FILES['portrait-launch-screen']['tmp_name']);

            if ($error = $icon->validate(['resolution' => [2048, 2732]])) {
                $errors = array_merge($errors, $icon->getErrors());
            }
        }

        if ($_FILES['portrait-launch-screen']['name'] && $_FILES['portrait-launch-screen']['error'] === 1) {
            $errors[] = str_replace('{limit}', Util::getMaxFileUploadSize() / (1024 * 1024), $lang['error_maxFileSize']);
        }

        if (!$errors) {
            if ($_FILES['pwa-icon']['tmp_name']
                && $icon->setImage($_FILES['pwa-icon']['tmp_name'])
                && $savedFileName = $icon->save($_FILES['pwa-icon']['name'])
            ) {
                $resizeFiles = $icon->resizeTo(
                    [
                        [512, 512],
                        [192, 192],
                        [180, 180],
                        [96, 96],
                        [32, 32],
                        [16, 16],
                    ],
                    PWA_FILES_PATH . $savedFileName
                );

                Events::afterImageCrop($resizeFiles, 'icon');
                $GLOBALS['rlConfig']->setConfig('pwa_icon', $resizeFiles['original']);
            }

            if ($_FILES['portrait-launch-screen']['name']
                && $_FILES['portrait-launch-screen']['tmp_name']
                && $icon->setImage($_FILES['portrait-launch-screen']['tmp_name'])
                && $savedFileName = $icon->save($_FILES['portrait-launch-screen']['name'])
            ) {
                // Close DB connection to prevent error with timeout in MySQL
                $rlDb->connectionClose(true);

                $resizeFiles = $icon->resizeTo(
                    [
                        [1125, 2436],
                        [750, 1334],
                        [828, 1792],
                        [1242, 2688],
                        [1242, 2208],
                        [640, 1136],
                        [1536, 2048],
                        [1668, 2224],
                        [1668, 2388],
                        [2048, 2732],
                    ],
                    PWA_FILES_PATH . $savedFileName,
                    true
                );

                // Start DB connection again to save changes after resizing of launch screen
                $rlDb = new rlDb();
                $rlDb->connect(RL_DBHOST, RL_DBPORT, RL_DBUSER, RL_DBPASS, RL_DBNAME);

                Events::afterImageCrop($resizeFiles, 'launch_portrait');
            }

            $GLOBALS['rlConfig']->setConfig('pwa_color', $_POST['color'] ?: '383737');
            $GLOBALS['rlConfig']->setConfig('pwa_maskable_icons', $_POST['maskable_icons']);

            if ($action == 'add') {
                $rlDb->insert($langKeys, 'lang_keys');
            } elseif ($updateKeys) {
                foreach ($updateKeys as $langKey) {
                    $rlDb->updateOne($langKey, 'lang_keys');
                }
            }

            $pwa->afterConfigSaving();

            $reefless->loadClass('Notice');
            $GLOBALS['rlNotice']->saveNotice($lang['pwa_config_saved']);
            $reefless->redirect(['controller' => $_GET['controller']]);
        }
    }
}
