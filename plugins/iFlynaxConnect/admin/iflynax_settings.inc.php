<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : IFLYNAX_SETTINGS.INC.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2023
 *	https://www.flynax.com
 *
 ******************************************************************************/

if ($_POST['submit']) {
    $post_config = isset($_POST['post_config']) ? $_POST['post_config'] : $_POST['config'];
    $update = array();

    foreach ($post_config as $key => $entry) {
        if ($entry['d_type'] == 'int') {
            $entry['value'] = (int) $entry['value'];
        }
        $rlValid->sql($entry['value']);

        $row['where']['Key'] = $key;
        $row['fields']['Default'] = $entry['value'];
        $update[] = $row;
    }

    $reefless->loadClass('Actions');

    if ($rlActions->update($update, 'config')) {
        $reefless->loadClass('Notice');

        $aUrl = array('controller' => $controller);
        $rlNotice->saveNotice($lang['config_saved']);
        $reefless->redirect($aUrl);
    }
}

$group_id = (int) $rlDb->getOne('ID', "`Key` = 'iFlynaxConnect'", 'config_groups');

// Get missing config phrases
if (method_exists($rlLang, 'preparePhrases')) {
    $config_phrases = (array) $rlLang->preparePhrases(
        "WHERE `Plugin` = 'iFlynaxConnect' AND `Target_key` = 'settings' AND `Code` = '" . RL_LANG_CODE . "'"
    );
    $lang = array_merge($lang, $config_phrases);
}

// Get all configs
$configsLsit = $rlDb->fetch('*', array('Group_ID' => $group_id), "ORDER BY `Position`", null, 'config');
$configsLsit = $rlLang->replaceLangKeys($configsLsit, 'config', array('name', 'des'), RL_LANG_CODE, 'admin');
$rlAdmin->mixSpecialConfigs($configsLsit);

$rlSmarty->assign_by_ref('configs', $configsLsit);
