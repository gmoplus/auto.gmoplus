<?php

use Flynax\Plugins\HybridAuth\Configs;
use Flynax\Plugins\HybridAuth\HybridAuth;
use Flynax\Plugins\HybridAuth\ModulesManager;
use Flynax\Plugins\HybridAuth\ProviderResolver;
use Flynax\Plugins\HybridAuth\Configs as HybridAuthConfigs;

if ($_GET['q'] == 'ext') {
    /* system files */
    require_once('../../../includes/config.inc.php');
    require_once(RL_ADMIN_CONTROL . 'ext_header.inc.php');
    require_once(RL_LIBS . 'system.lib.php');

    $reefless->loadClass('HybridAuthLogin', null, 'hybridAuthLogin');

    if ($_GET['action'] == "update") {
        $reefless->loadClass('Actions');
        $reefless->loadClass('Valid');

        $updateData = array(
            'fields' => array(
                $_GET['field'] => $rlValid->xSql($_GET['value']),
            ),
            'where' => array(
                'ID' => (int) $_GET['id'],
            ),
        );

        $rlDb->updateOne($updateData, 'ha_providers');
    }

    $modulesManager = new ModulesManager();
    $providerManager = new ProviderResolver();
    $modules = $providerManager->getProviders();

    $data = array();
    $output = array();

    foreach ($modules as $key => $provider) {
        $data[$key]['ID'] = $provider['ID'];
        $data[$key]['Name'] =  ucfirst($provider['Provider'])
            /* @todo: Remove as soon as the provider will available for WEB too */
            . ($provider['Provider'] == 'apple' ? ' (iOS)' : '');
        $data[$key]['Key'] = $provider['Provider'];
        $data[$key]['Status'] = $lang[$provider['Status']];
        $data[$key]['Order'] = $provider['Order'];
    }

    $count = $providerManager->getTotalProvidersCount();

    $output['total'] = $count;
    $output['data'] = $data;

    echo json_encode($output);
} else {
    $reefless->loadClass('HybridAuthLogin', null, 'hybridAuthLogin');
    $providersManager = new ProviderResolver();
    $modulesManager = new ModulesManager();

    $modulesManager->updateGroupID();

    $availableModules = $modulesManager->getAllModules();

    $notice = array();
    $isFacebookConnectEnabled = $rlHybridAuthLogin->isFacebookConnectEnabled();
    if ($isFacebookConnectEnabled) {
        $notice = array(
            'message' => $lang['ha_fb_connect_plugin_conflict'],
            'type' => 'alerts',
        );
    }

    $rlSmarty->assign('ha_is_facebook_connect_enabled', (int) $isFacebookConnectEnabled);

    if ($_GET['module']) {
        $action = $_GET['action'];
        $module = $_GET['module'];

        if (in_array($module, $availableModules)) {
            $bcAStep[] = array(
                'name' => str_replace('{provider_name}', ucfirst($module), $lang['ha_ap_edit_module_page']),
                'Controller' => $_GET['controller'],
                'Vars' => 'action=edit&module=' . $module,
            );

            $allModulesInfo = HybridAuthConfigs::i()->getConfig('providers');
            $selectedModuleInfo = $allModulesInfo[$module];
            $modulesManager->setActiveModule($module);
            Configs::i()->setConfig('active_module', $module);

            if ($selectedModuleInfo['enable_copy_button']) {
                $willRedirectTo = $modulesManager->getModuleUrl($module);
                $rlSmarty->assign('redirect_url', $willRedirectTo);
            }

            if ($guideLink = $selectedModuleInfo['guide_link']) {
                if (is_array($guideLink)) {
                    $guideLink = $guideLink[RL_LANG_CODE] ?? $guideLink['en'];
                }

                $message = preg_replace(
                    '/\[(.*)\]/',
                    '<a target="_blank" href="' . $guideLink . '">$1</a>',
                    $lang['ha_provider_guide_link']
                );
                $guideLink = str_replace('{provider}', ucfirst($module), $message);
                $rlSmarty->assign('guide_link', $guideLink);
            }

            $moduleSettings = $modulesManager->getModuleSettings($module);
            $rlSmarty->assign('module_settings', $moduleSettings);

            if ($_POST['submit']) {
                $configs = $rlValid->xSql($_POST['post_config']);
                $errors = $modulesManager->saveModuleSettings($configs, $module);

                if (!is_array($errors)) {
                    $aUrl = array(
                        'controller' => 'hybrid_auth_login',
                    );
                    $message = $lang['ha_configurations_has_been_saved'];
                    $providersManager->makeProviderActive($module);

                    HybridAuth::redirectWithMessageToAP($aUrl, $message, 'notice');
                }
            }
        }

        if (is_array($errors)) {
            $GLOBALS['errors'] = $errors;
            $rlSmarty->assign_by_ref('errors', $GLOBALS['errors']);
        }
    }

    if (!empty($notice) && $notice['message']) {
        $reefless->loadClass('Notice');

        $type = $notice['type'] ?: 'notice';
        $rlNotice->saveNotice($notice['message'], $type);
    }
}
