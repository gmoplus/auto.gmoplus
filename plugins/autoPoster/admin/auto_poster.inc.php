<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: AUTO_POSTER.INC.PHP
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

use Autoposter\Admin\rlAutoPosterAdmin;
use Autoposter\AutoPosterContainer;
use Autoposter\AutoPosterModules;

/* ext js action */
if ($_GET['q'] == 'ext') {
    /* system config */
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';
    $reefless->loadClass('AutoPoster', null, 'autoPoster');

    if ($_GET['action'] == "update") {
        $reefless->loadClass('Actions');
        $reefless->loadClass('Valid');

        $update_data = array(
            'fields' => array(
                $_GET['field'] => $rlValid->xSql($_GET['value']),
            ),
            'where' => array(
                'ID' => intval($_GET['id']),
            ),
        );

        $rlActions->updateOne($update_data, 'autoposting_modules');
    }

    $modules = $rlAutoPoster->getModules();
    foreach ($modules as $key => $module) {
        $modules[$key]['name'] = ucfirst($module['Key']);
        $modules[$key]['Status'] = $GLOBALS['lang'][$modules[$key]['Status']];
    }
    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $output['total'] = $count['count'];
    $output['data'] = $modules;

    echo json_encode($output);
} else {
    require RL_PLUGINS . 'autoPoster' . RL_DS . 'bootstrap.php';

    $reefless->loadClass('AutoPoster', null, 'autoPoster');
    $autoPostingModules = new AutoPosterModules();
    $autoPostingAdmin = new rlAutoPosterAdmin();
    $available_modules = $rlAutoPoster->getConfig('modules');

    $rlSmarty->assign('error_log', $autoPostingAdmin->getErrorLog());

    if ($_GET['module']) {
        $action = $_GET['action'];
        $module = $_GET['module'];
        $autoPostingModules->setModule($module);
        $method = $_GET['method'];
        $available_methods = ['success', 'error', 'handleRedirect'];
        $bcAStep[] = array(
            'name' => ucfirst($module),
            'Controller' => $_GET['controller'],
            'Vars' => 'action=edit&module=' . $module,
        );

        $module_options = $rlAutoPoster->getConfig('modules');
        $module_options = $module_options[$module];
        $rlSmarty->assign('module_options', $module_options);

        $errors = array();

        if (key_exists($module, $available_modules)) {
            AutoPosterContainer::setConfig('active_module', $module);

            $module_configs = $autoPostingModules->getSettingsByKey($module);
            $rlSmarty->assign('module_configs', $module_configs);

            $all_modules_configs = AutoPosterContainer::getConfig('configs');
            $selectedModuleConfig = $all_modules_configs['modules'][$module];

            // Prevent showing VK guide link for all languages not RU
            if ($module === 'vk' && RL_LANG_CODE !== 'ru') {
                $selectedModuleConfig['guide_link'] = null;
            }

            if ($selectedModuleConfig['guide_link']) {
                $build_link = '<a target="_blank" href="' . $selectedModuleConfig['guide_link'] . '">$2</a>';
                $link = preg_replace('/(\[(.*?)\])/', $build_link, $lang['ap_provider_guide_link']);
                $link = str_replace(array('{provider}'), array(ucfirst($module)), $link);

                $rlSmarty->assign('guide_link', $link);
            }

            $status = $autoPostingModules->getStatus($module);
            $_POST['status'] = $status;

            // handle post requests
            if ($_POST['submit']) {
                $configs = $rlValid->xSql($_POST['post_config']);
                if ($autoPostingModules->isConfigurationChanged($configs, $module)) {
                    $providerObject = (new \Autoposter\ProviderController($module))->getProvider();

                    if (method_exists($providerObject, 'removeToken')) {
                        $providerObject->removeToken(1);
                    }
                }

                // update settings
                $status = $autoPostingAdmin->updateSettings($configs);
                $aUrl = array(
                    'controller' => 'auto_poster',
                    'action' => 'edit',
                    'module' => $module,
                );

                // update status
                $data['Status'] = $rlValid->xSql($_REQUEST['status']);
                $autoPostingModules->saveGeneralSettings($data, $module);

                if (!is_array($status)) {
                    $message = $lang['ap_config_saved_fine'];
                    $type = 'notice';
                    $autoPostingAdmin->redirectWithMessage($aUrl, $message, $type);
                }

                $GLOBALS['errors'] = $status;
                $rlSmarty->assign_by_ref('errors', $GLOBALS['errors']);
            }

            // module management area
            $providerController = new \Autoposter\ProviderController($module);
            if ($action == 'edit') {

                $provider = $providerController->getProvider();
                if (method_exists($provider, 'getOAuthLink')) {
                    $OAuthLink = $provider->getOAuthLink();
                    $rlSmarty->assign('OAuthLink', $OAuthLink);
                }
            }

            if ($action == 'edit' && $autoPostingModules->allowManagement($module)) {
                $activeProvider = $providerController->getProvider();

                // handle incoming redirects
                if (in_array($method, $available_methods) && method_exists($activeProvider, $method)) {
                    $activeProvider->$method();
                }

                if (method_exists($provider, 'getRedirectUrl')) {
                    $rlSmarty->assign('allow_management', true);
                    $url = $activeProvider->getRedirectUrl();
                    $rlSmarty->assign('redirectUrl', $url);

                    if ($activeProvider->isTokenExist(1)) {
                        $tokenInfo = $activeProvider->getTokenData();
                        $rlSmarty->assign('tokenInfo', $tokenInfo);
                    }
                }
            }

            if ($action == 'build_message') {
                $bcAStep[] = array(
                    'name' => ucfirst($lang['ap_build_message']),
                );
                $fields = $autoPostingAdmin->getFieldsForMessage($module);
                $rlSmarty->assign('fields', $fields['available']);
                $rlSmarty->assign('saved_fields', $fields['saved']);

                $moduleMessage = $autoPostingModules->getMessageBody($module);
                $rlSmarty->assign('moduleMessage', $moduleMessage);
            }
        } else {
            $message = $lang['ap_module_missing'];
            $type = 'errors';
            $aUrl = array("controller" => 'auto_poster');

            $reefless->loadClass('Notice');
            $rlNotice->saveNotice($message);
            $reefless->redirect($aUrl);
        }
    }
}
