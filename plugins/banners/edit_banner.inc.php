<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: EDIT_BANNER.INC.PHP
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

use Flynax\Utils\Util;

// set bread_crumbs
$bread_crumbs[1] = ['name' => $lang['pages+name+my_banners'], 'path' => $pages['my_banners']];
$bread_crumbs[2] = ['name' => $lang['pages+name+banners_edit_banner'], 'path' => $pages['banners_edit_banner']];

$bannerId = (int) $_GET['id'];
if ($bannerId) {

    // TODO: refactor me as soon as compatible will be ≧ 4.6.2
    $templateCss = RL_TPL_BASE . (in_array($config['rl_version'], ['4.6.0', '4.6.1'])
        ? 'controllers/add_listing/add_listing.css'
        : 'components/file-upload/file-upload.css'
    );
    $rlStatic->addHeaderCSS($templateCss);

    // get banner data
    $bannerData = $rlDb->fetch('*', ['ID' => $bannerId], null, 1, 'banners', 'row');

    if (!empty($bannerData) && $account_info['ID'] == $bannerData['Account_ID']) {
        $reefless->loadClass('Banners', false, 'banners');
        $rlSmarty->assign_by_ref('bannerData', $bannerData);

        // get plan info
        $plan_info = $rlBanners->getBannerPlans('ID', $bannerData['Plan_ID'], 'row');

        // build available banner types
        $types = explode(',', $plan_info['Types']);
        foreach ($types as $type) {
            $plan_info['types'][] = [
                'key' => $type,
                'name' => $lang['banners_bannerType_' . $type],
            ];
        }
        $rlSmarty->assign('plan_info', $plan_info);
        unset($types);

        // get box info
        $b_box = $bannerData['Box'];
        $boxInfo = $rlDb->getRow("SELECT `Side`, `Banners` FROM `{db_prefix}blocks` WHERE `Key` = '{$b_box}' AND `Plugin` = 'banners'");
        $boxSide = $boxInfo['Side'];
        $boxInfo = unserialize($boxInfo['Banners']);

        $bannerData['Box'] = [
            'side' => $lang[$boxSide],
            'name' => $lang['blocks+name+' . $b_box],
            'width' => $boxInfo['width'],
            'height' => $boxInfo['height'],
        ];

        // type info
        $b_type = $bannerData['Type'];
        $bannerData['Type'] = [
            'key' => $b_type,
            'name' => $lang['banners_bannerType_' . $b_type],
        ];

        if ($bannerData['Type']['key'] != 'html') {
            $reefless->loadClass('Json');
            rlBanners::assignMaxFileUploadSize();
        }

        $_SESSION['edit_banner']['banner_id'] = $bannerId;

        $allLangs = $GLOBALS['languages'];
        if (!$_POST['submit_form']) {
            if (count($allLangs) > 1) {
                $names = $rlDb->fetch(['Value', 'Code'], ['Key' => "banners+name+{$bannerId}", 'Plugin' => 'banners'], null, null, 'lang_keys');
                foreach ($names as $lKey => $entry) {
                    $_POST['name'][$entry['Code']] = $entry['Value'];
                }
            } else {
                $_POST['name'] = $lang["banners+name+{$bannerId}"];
            }

            $_POST['banner_type'] = $bannerData['Type']['key'];
            $_POST['link'] = $bannerData['Link'];

            if ($bannerData['Type']['key'] == 'html') {
                $_POST['html'] = $bannerData['Html'];
                $_POST['responsive'] = (int) $bannerData['Responsive'];
            }
        } else {
            $errors = $error_fields = [];
            $postData = $rlValid->xSql($_POST);

            // check form fields
            if (count($allLangs) > 1) {
                foreach ($allLangs as $lkey => $lval) {
                    if (empty($postData['name'][$allLangs[$lkey]['Code']])) {
                        array_push($errors, str_replace('{field}', "<b>{$lang['name']}({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']));
                        array_push($error_fields, "name[{$lval['Code']}]");
                    }
                }
            } else {
                if (empty($postData['name'])) {
                    array_push($errors, str_replace('{field}', "<b>{$lang['name']}</b>", $lang['notice_field_empty']));
                    array_push($error_fields, "name");
                }
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
                $reefless->loadClass('Actions');
                $rlBanners->updateBanner($bannerId, $postData, $bannerData);

                $reefless->loadClass('Notice');
                $rlNotice->saveNotice($lang['banners_notice_banner_edited']);
                Util::redirect($reefless->getPageUrl('my_banners'));
            }
        }
    } else {
        $sError = true;
    }
} else {
    $sError = true;
}
