<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: PAYPALREST.GATEWAY.PHP
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

/*
*
* Run update420 manually
*
* TODO: remove code when compatibility 4.8.1
*/
if (version_compare($config['rl_version'], '4.8.1') < 0
    && file_exists(RL_PLUGINS . 'androidConnect' . RL_DS . 'static' . RL_DS . 'jquery.smartbanner.js')) {
    $reefless->loadClass('AndroidConnect', false, 'androidConnect');
    $rlAndroidConnect->update420();
}

if ($_POST['submit']) {
    $post_config = isset($_POST['post_config']) ? $_POST['post_config'] : $_POST['config'];

    $update = array();

    foreach ($post_config as $key => $value) {
        if ($value['d_type'] == 'int') {
            $value['value'] = (int) $value['value'];
        }

        $rlValid->sql($value['value']);

        $row['where']['Key'] = $key;
        $row['fields']['Default'] = $value['value'];
        if ($key == 'android_inapp_key') {
            $row['fields']['Values'] = $value['value'];
        }
        array_push($update, $row);
        unset($row);
    }

    if ($rlDb->update($update, 'config')) {
        $reefless->loadClass('Notice');

        $aUrl = array('controller' => $controller);

        $rlNotice->saveNotice($lang['config_saved']);
        $reefless->redirect($aUrl);
    }
}

$group_id = $rlDb->getOne('ID', "`Key` = 'androidConnect'", 'config_groups');

// Get missing config phrases
if (method_exists($rlLang, 'preparePhrases')) {
    $config_phrases = (array) $rlLang->preparePhrases(
        "WHERE `Plugin` = 'androidConnect' AND `Target_key` = 'settings' AND `Code` = '" . RL_LANG_CODE . "'"
    );
    $lang = array_merge($lang, $config_phrases);
}

// Get all configs
$configsLsit = $rlDb->fetch('*', array('Group_ID' => $group_id), "ORDER BY `Position`", null, 'config');
$configsLsit = $rlLang->replaceLangKeys($configsLsit, 'config', array('name', 'des'), RL_LANG_CODE, 'admin');
$rlAdmin->mixSpecialConfigs($configsLsit);

// Unset YooKassa settings if the plugin is disabled
if (!$GLOBALS['plugins']['yandexKassa']) {
    $yooKassaKeys = [
        'android_divider_yookassa',
        'android_yookassa_module',
        'android_yookassa_key_site',
        'android_yookassa_key',
        'android_yookassa_store_id',
        'android_yookassa_app_id',
    ];
    foreach ($configsLsit as $key => $val) {
        if (in_array($val['Key'], $yooKassaKeys)) {
            unset($configsLsit[$key]);
        }
    }
}

$rlSmarty->assign_by_ref('configs', $configsLsit);
