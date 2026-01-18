<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: REQUESTS.PHP
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

use Flynax\Plugins\HybridAuth\HybridAuth;
use Flynax\Plugins\HybridAuth\ModulesManager;
use Flynax\Plugins\HybridAuth\ProviderResolver;

// force disable output of PHP notifications
error_reporting(E_ERROR);
ini_set('display_errors', 0);

// Load system config file
require_once __DIR__ . '/../../includes/config.inc.php';
require_once(RL_INC . 'control.inc.php');

// set language
$request_lang = $_COOKIE['rl_lang_front'] ?: $_REQUEST['lang'] ?: $config['lang'];
$rlValid->sql($request_lang);

$languages = $rlLang->getLanguagesList();
$rlLang->defineLanguage($request_lang);
$rlLang->modifyLanguagesList($languages);

$seo_base = RL_URL_HOME;
if ($config['lang'] != RL_LANG_CODE && $config['mod_rewrite']) {
    $seo_base .= RL_LANG_CODE . '/';
}
if (!$config['mod_rewrite']) {
    $seo_base .= 'index.php';
}

$reefless->loadClass('HybridAuthLogin', null, 'hybridAuthLogin');

/** @var \rlLang $rlLang */
$rlLang = hybridAuthMakeObject('rlLang');
$GLOBALS['lang'] = $rlLang->getLangBySide('frontEnd', $request_lang);

$modulesManager = new ModulesManager();
$providersManager = new ProviderResolver();
$incomingProvider = $rlValid->xSql($_GET['provider']);
$availableProviders = $modulesManager->getAllModules();
$errors = array();
$errorFields = '';
$escortListingTypeKey = '';

if (!$rlAccount->isLogin()) {
    $reefless->loginAttempt();
}

if (!isset($_SESSION['ha_from_page'])) {
    $_SESSION['ha_from_page'] = str_replace('?logout', '', $_SERVER['HTTP_REFERER']);
}

if ($_GET['account_type'] && $accountTypeID = (int) $_GET['account_type']) {
    $accountTypeKey = $rlDb->getOne('Key', "`ID` = $accountTypeID", 'account_types');
    $_SESSION['ha-account-type'] = $accountTypeKey;
}

if (file_exists(RL_CLASSES . 'rlEscort.class.php') && $_GET['escort_ltype']) {
    $reefless->loadClass('Escort');
    $providersManager->setIsEscortInstallation(true);
    $escortListingTypeKey = $_GET['escort_ltype'];
}

$rlDb->connectionClose();

if (in_array($incomingProvider, $availableProviders)) {
    $provider = $providersManager->getProvider($incomingProvider);

    /** @var \Hybridauth\User\Profile $user */
    $user = $provider->authenticate();

    if ($user) {
        $user->data['provider'] = $incomingProvider;
        $user->data['is_real_image'] = method_exists($provider, 'isNotEmptyImage')
            ? !$provider->isNotEmptyImage($user->photoURL)
            : true;

        // Generate fake email if the social network doesn't provide it
        if (!$user->email && $user->identifier) {
            $user->email = $providersManager->getUserFakeEmail($user->identifier);
        }

        if (!$user->email) {
            $errors[] = $lang['ha_provider_without_email'];
        }

        if ($user->email) {
            $isAccountExist = (bool) $rlDb->getOne('ID', "`Mail` = '{$user->email}' ", 'accounts');

            if ($isAccountExist
                && $providersManager->isUserAlreadyExist($user)
                && !$providersManager->isUserActive($user->email)
            ) {
                $errors[] = $lang['ha_user_inactive_or_in_trash'];
            }

            $registerData = array(
                'email' => $user->email,
            );
            $GLOBALS['rlHook']->load('phpAddListingQuickRegistrationValidate', $registerData, $errors, $errorFields);
        }
    } elseif ($provider->appError) {
        $errors[] = $provider->appError;
    }

    if (!$errors) {
        $providersManager->addUIDRowIfNecessaryForUser($user,  $incomingProvider);

        if ($existingUser = $providersManager->isUserAlreadyExist($user)) {
            $providersManager->login($user);
            $reefless->redirect(null, $_SESSION['ha_from_page']);
        }

        if (isset($_SESSION['ha-account-type'])) {
            if ($providersManager->registerNewAccount($user, $_SESSION['ha-account-type'], $escortListingTypeKey)) {
                $providersManager->login($user);
                $reefless->redirect(null, $_SESSION['ha_from_page']);
            }
        }

        $_SESSION['ha_login_fail'] = array(
            'provider' => $incomingProvider,
            'show_modal' => true,
        );
        $reefless->redirect(null, $_SESSION['ha_from_page']);
    } else {
        HybridAuth::redirectWithMessageToFront($_SESSION['ha_from_page'], $errors, 'error');
    }
}
