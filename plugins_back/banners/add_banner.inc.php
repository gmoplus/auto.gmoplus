<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: ADD_BANNER.INC.PHP
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

if (in_array('add_banner', $deny_pages)) {
    $sError = true;
    $rlSmarty->assign('no_access', true);

    return;
}

$addBannerSteps = rlBanners::getSteps();
$rlSmarty->assign('show_step_caption', false);
$rlSmarty->assign_by_ref('bSteps', $addBannerSteps);

// optimize category request
$request = explode('/', $_GET['rlVareables']);
$requestStep = array_pop($request);

// detect step from GET
$currentStep = $requestStep ?: $_GET['step'];

// clear saved data
if (!isset($_GET['edit']) && !$currentStep) {
    unset($_SESSION['add_banner']);
    unset($_SESSION['complete_payment']);
    unset($_SESSION['done']);
}

$reefless->loadClass('Plan');
$reefless->loadClass('Actions');

// set bread_crumbs
$bread_crumbs[1] = ['name' => $lang['pages+name+my_banners'], 'path' => $pages['my_banners']];
$bread_crumbs[2] = ['name' => $lang['pages+name+add_banner'], 'path' => $pages['add_banner']];

if (!$currentStep) {
    $url = SEO_BASE . ($config['mod_rewrite']
        ? $page_info['Path'] . '/' . $addBannerSteps['plan']['path'] . '.html'
        : '?page=' . $page_info['Path'] . '&step=' . $addBannerSteps['plan']['path']
    );
    $reefless->redirect(null, $url);
}

$currentStep = $rlPlan->stepByPath($currentStep, $addBannerSteps);
$rlSmarty->assign_by_ref('curStep', $currentStep);

// return user to the first step
if ($_SESSION['done'] && $currentStep && $currentStep != 'done') {
    $url = SEO_BASE;
    $url .= $config['mod_rewrite'] ? $page_info['Path'] . '.html' : '?page=' . $page_info['Path'];
    $reefless->redirect(null, $url);
}

$planId = $_POST['plan'] ? (int) $_POST['plan'] : $_SESSION['add_banner']['plan_id'];
$bannerId = $_SESSION['add_banner']['banner_id'] ? (int) $_SESSION['add_banner']['banner_id'] : false;

if ($bannerId) {
    $bannerData = $rlDb->fetch('*', ['ID' => $bannerId], null, 1, 'banners', 'row');
    $rlSmarty->assign_by_ref('bannerData', $bannerData);
}

$returnLink = SEO_BASE . ($config['mod_rewrite']
    ? $page_info['Path'] . '/' . $addBannerSteps['plan']['path'] . '.html'
    : '?page=' . $page_info['Path'] . '&step=' . $addBannerSteps['plan']['path']
);
$rlSmarty->assign('returnLink', $returnLink);

if (!$planId && $currentStep != 'plan') {
    $reefless->redirect(null, $returnLink);
}

$rlDb->outputRowsMap = 'ID';
$plans = $rlBanners->getBannerPlans();
$rlSmarty->assign_by_ref('plans', $plans);

$planInfo = $plans[$planId];
$rlSmarty->assign_by_ref('planInfo', $planInfo);

if (!$planInfo['Price']) {
    unset($addBannerSteps['checkout']);
}

if ($currentStep) {
    $rlSmarty->assign('no_h1', true);

    $bread_crumbs[] = [
        'name' => $addBannerSteps[$currentStep]['name'],
    ];

    // save step for current banner
    if ($bannerId && !in_array($currentStep, ['plan', 'done'])) {
        $updateStep = [
            'fields' => [
                'Last_step' => $currentStep,
            ],
            'where' => [
                'ID' => $bannerId,
            ],
        ];
        $rlDb->updateOne($updateStep, 'banners');
    }
}

// skip media step if the banner type html
if ($_POST['banner_type'] == 'html'
    || ($currentStep == 'checkout' && $bannerData['Type'] == 'html')
) {
    unset($addBannerSteps['media']);
}

// get prev/next step
$tmp_steps = $addBannerSteps;
foreach ($tmp_steps as $t_key => $t_step) {
    if ($t_key != $currentStep) {
        next($addBannerSteps);
    } else {
        break;
    }
}
unset($tmp_steps);

$nextStep = next($addBannerSteps);
prev($addBannerSteps);
$prevStep = prev($addBannerSteps);

$rlSmarty->assign('next_step', $nextStep);
$rlSmarty->assign('prev_step', $prevStep);

$errors = $error_fields = [];

// steps handler
switch ($currentStep) {
    case 'plan':
        // simulate selected plan in POST
        if (!$_POST['plan'] && $_SESSION['add_banner']['plan_id']) {
            $_POST['plan'] = $_SESSION['add_banner']['plan_id'];
        }

        if (empty($plans)) {
            array_push($errors, $lang['banners_bannerPlansEmpty']);
            $rlSmarty->assign('no_access', true);
        }

        // check plan
        if ($_POST['step'] == 'plan') {
            if (!$planId) {
                array_push($errors, $lang['notice_listing_plan_does_not_chose']);
            }

            if (empty($errors)) {
                $_SESSION['add_banner']['plan_id'] = $planId;

                $url = SEO_BASE . ($config['mod_rewrite']
                    ? $page_info['Path'] . '/' . $addBannerSteps['form']['path'] . '.html'
                    : '?page=' . $page_info['Path'] . '&step=' . $addBannerSteps['form']['path']
                );
                $reefless->redirect(null, $url);
            }
        }
        break;

    case 'form':
        $boxes = explode(',', $planInfo['Boxes']);

        foreach ($boxes as $box) {
            $boxInfo = $rlDb->getRow("
                SELECT `Side`, `Banners` FROM `{db_prefix}blocks`
                WHERE `Key` = '{$box}' AND `Plugin` = 'banners'
            ");
            $boxSide = $boxInfo['Side'];
            $boxInfo = unserialize($boxInfo['Banners']);

            $planInfo['boxes'][] = [
                'Key' => $box,
                'side' => $lang[$boxSide],
                'name' => $lang['blocks+name+' . $box],
                'width' => $boxInfo['width'],
                'height' => $boxInfo['height'],
            ];
        }
        unset($boxes);

        $types = explode(',', $planInfo['Types']);

        foreach ($types as $type) {
            $planInfo['types'][] = [
                'Key' => $type,
                'name' => $lang['banners_bannerType_' . $type],
            ];
        }
        unset($types);

        $allLangs = $GLOBALS['languages'];

        if ($bannerId && !$_POST['step']) {
            if (count($allLangs) > 1) {
                $where = ['Key' => "banners+name+{$bannerId}", 'Plugin' => 'banners'];
                $names = $rlDb->fetch(['Value', 'Code'], $where, null, null, 'lang_keys');

                foreach ($names as $lKey => $entry) {
                    $_POST['name'][$entry['Code']] = $entry['Value'];
                }
            } else {
                $_POST['name'] = $lang["banners+name+{$bannerId}"];
            }

            $_POST['banner_box'] = $bannerData['Box'];
            $_POST['banner_type'] = $bannerData['Type'];
            $_POST['link'] = $bannerData['Link'];

            if ($bannerData['Type'] == 'html') {
                $_POST['responsive'] = (int) $bannerData['Responsive'];
                $_POST['html'] = $bannerData['Html'];
            }
        }

        if ($_POST['step'] == 'form') {
            $postData = $rlValid->xSql($_POST);

            // check form fields
            if (count($allLangs) > 1) {
                if (empty($postData['name'][$config['lang']])) {
                    array_push($errors, str_replace('{field}', "<b>{$lang['name']}({$allLangs[$config['lang']]['name']})</b>", $lang['notice_field_empty']));
                    array_push($error_fields, "name[{$config['lang']}]");
                }
            } else {
                if (empty($postData['name'])) {
                    array_push($errors, str_replace('{field}', "<b>{$lang['name']}</b>", $lang['notice_field_empty']));
                    array_push($error_fields, "name");
                }
            }

            if (empty($postData['banner_box'])) {
                array_push($errors, str_replace('{field}', "<b>\"{$lang['banners_bannerBox']}\"</b>", $lang['notice_select_empty']));
                array_push($error_fields, 'banner_box');
            }

            if (empty($postData['banner_type'])) {
                array_push($errors, str_replace('{field}', "<b>\"{$lang['banners_bannerType']}\"</b>", $lang['notice_select_empty']));
                array_push($error_fields, 'banner_type');
            }

            if ($postData['banner_type'] == 'html' && empty($postData['html'])) {
                array_push($errors, str_replace('{field}', "<b>\"{$lang['banners_bannerType_html']}\"</b>", $lang['notice_field_empty']));
                array_push($error_fields, 'html');
            }

            if (!empty($postData['link']) && !$rlValid->isUrl($postData['link'])) {
                array_push($errors, str_replace('{field}', "<b>\"{$lang['banners_bannerLink']}\"</b>", $lang['notice_field_incorrect']));
                array_push($error_fields, 'link');
            }
            $error_fields = implode(',', $error_fields);

            if (empty($errors)) {
                // fix for non-responsive templates
                if (!is_numeric($postData['responsive'])) {
                    $postData['responsive'] = ($postData['responsive'] == 'on') ? 1 : 0;
                }

                if ($bannerId) {
                    $rlBanners->edit($bannerId, $planInfo, $postData);
                } // create a new banner
                else {
                    $postData['account_id'] = (int) $account_info['ID'];
                    if (false !== $bannerId = $rlBanners->create($planInfo, $postData)) {
                        $_SESSION['add_banner']['banner_id'] = $bannerId;
                    }
                }

                // redirect to related controller
                $redirect = SEO_BASE;
                $redirect .= $config['mod_rewrite'] ? $page_info['Path'] . '/' . $nextStep['path'] . '.html' : '?page=' . $page_info['Path'] . '&step=' . $nextStep['path'];
                $reefless->redirect(null, $redirect);
            }
        }
        break;

    case 'media':

        // TODO: Refactor me as soon as compatible will be ≧ 4.6.2
        $templateCss = RL_TPL_BASE . (in_array($config['rl_version'], ['4.6.0', '4.6.1'])
                ? 'controllers/add_listing/add_listing.css'
                : 'components/file-upload/file-upload.css'
            );
        $rlStatic->addHeaderCSS($templateCss);

        if ($_POST['step'] == 'media') {
            if (empty($errors)) {
                $redirect = SEO_BASE;
                $redirect .= $config['mod_rewrite'] ? $page_info['Path'] . '/' . $nextStep['path'] . '.html' : '?page=' . $page_info['Path'] . '&step=' . $nextStep['path'];
                $reefless->redirect(null, $redirect);
            }
        } else {
            $boxInfo = $rlDb->getOne('Banners', "`Key` = '{$bannerData['Box']}'", 'blocks');
            $boxInfo = unserialize($boxInfo);
            $boxInfo['type'] = $bannerData['Type'];
            $rlSmarty->assign('boxInfo', $boxInfo);

            rlBanners::assignMaxFileUploadSize();
        }
        break;

    case 'checkout':
        $bannerTitle = $lang['banners+name+' . $bannerData['ID']];
        $itemName = ' #' . $bannerData['ID'] . ' (' . $bannerTitle . ')';

        $cancel_url = SEO_BASE . ($config['mod_rewrite']
            ? $page_info['Path'] . '/' . $currentStep . '.html?canceled'
            : '?page=' . $page_info['Path'] . '&step=' . $currentStep . '&canceled'
        );

        $success_url = SEO_BASE . ($config['mod_rewrite']
            ? $page_info['Path'] . '/' . $nextStep['path'] . '.html'
            : '?page=' . $page_info['Path'] . '&step=' . $nextStep['path']
        );

        if (!$rlPayment->isPrepare()) {
            $rlPayment->clear();

            $rlPayment->setOption('service', 'banners');
            $rlPayment->setOption('total', $planInfo['Price']);
            $rlPayment->setOption('plan_id', $planInfo['ID']);
            $rlPayment->setOption('item_id', $bannerData['ID']);
            $rlPayment->setOption('item_name', $itemName);
            $rlPayment->setOption('plan_key', 'banner_plans+name+' . $planInfo['Key']);
            $rlPayment->setOption('account_id', $account_info['ID']);
            $rlPayment->setOption('plugin', 'banners');
            $rlPayment->setOption('callback_class', 'rlBanners');
            $rlPayment->setOption('callback_method', 'upgradeBanner');
            $rlPayment->setOption('cancel_url', $cancel_url);
            $rlPayment->setOption('success_url', $success_url);

            $rlPayment->init($errors);
        } else {
            $rlPayment->checkout($errors);
        }
        break;

    case 'done':
        if ($_SESSION['done']) {
            break;
        }

        $reefless->loadClass('Mail');
        $mail_tpl = $rlMail->getEmailTemplate('banners_admin_banner_added');

        $link = RL_URL_HOME . ADMIN . '/index.php?controller=banners&filter=' . $bannerData['ID'];
        $link = "<a href='{$link}'>{$lang['banners+name+' . $bannerData['ID']]}</a>";

        $mail_tpl['body'] = strtr($mail_tpl['body'], [
            '{username}' => $account_info['Username'],
            '{link}' => $link,
            '{date}' => date(str_replace(['b', '%'], ['M', ''], RL_DATE_FORMAT)),
            '{status}' => $lang[$config['banners_auto_approval'] ? 'active' : 'pending'],
        ]);
        $rlMail->send($mail_tpl, $config['notifications_email']);

        if ($bannerData['Type'] == 'html') {
            $reefless->deleteDirectory(
                RL_FILES . 'banners/' . date('m-Y', $bannerData['Date_release']) . "/b{$bannerData['ID']}"
            );
        }

        $_SESSION['done'] = true;

        if ($plans[$planId]['Price'] > 0) {
            break;
        }

        $updateStatus = [
            'fields' => [
                'Status' => $config['banners_auto_approval'] ? 'active' : 'pending',
                'Pay_date' => time(),
                'Last_step' => '',
            ],
            'where' => [
                'ID' => (int) $bannerData['ID'],
            ],
        ];
        $rlDb->updateOne($updateStatus, 'banners');

        break;
}
