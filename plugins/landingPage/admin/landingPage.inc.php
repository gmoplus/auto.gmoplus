<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: LANDINGPAGE.INC.PHP
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

use Flynax\Utils\Valid;

if ($_GET['q'] == 'ext') {
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    if ($_GET['action'] == 'update') {
        $field = Valid::escape($_GET['field']);
        $value = Valid::escape($_GET['value']);
        $id = (int) $_GET['id'];

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where'  => array(
                'ID' => $id,
            ),
        );

        $rlDb->updateOne($updateData, 'landing_pages');
        exit;
    }

    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $sort = Valid::escape($_GET['sort']);
    $sortDir = Valid::escape($_GET['dir']);

    $sql = "
        SELECT SQL_CALC_FOUND_ROWS * 
        FROM `{db_prefix}landing_pages` AS `T1`
        LEFT JOIN `{db_prefix}landing_pages_lang` AS `T2` ON `T1`.`ID` = `T2`.`Page_ID`
        AND `T2`.`Lang_code` = '" . RL_LANG_CODE . "'
    ";
    if ($sort) {
        $sql .= "ORDER BY {$sort} {$sortDir} ";
    }
    $sql .= "LIMIT {$start}, {$limit}";
    $data = $rlDb->getAll($sql);

    $domain_path = ltrim($domain_info['path'], '/');
    $has_www = false !== strpos($domain_info['host'], 'www.');

    foreach ($data as &$value) {
        $value['Status'] = $lang[$value['Status']];
        $value['Landing_path'] = sprintf(
            '%s://%s%s/%s%s%s/',
            $domain_info['scheme'],
            ($value['Landing_subdomain'] ? $value['Landing_subdomain'] . '.' : ''),
            ($has_www && $value['Landing_subdomain'] ? ltrim($domain_info['domain'], '.') : $domain_info['host']),
            RL_DIR ?  RL_DIR : '',
            ($value['Lang_code'] != $config['lang'] ? $value['Lang_code'] . '/' : ''),
            $value['Landing_path']
        );
        $value['Original_path'] = sprintf(
            '%s://%s%s/%s%s%s%s',
            $domain_info['scheme'],
            ($value['Original_subdomain'] ? $value['Original_subdomain'] . '.' : ''),
            ($has_www && $value['Original_subdomain'] ? ltrim($domain_info['domain'], '.') : $domain_info['host']),
            RL_DIR ?  RL_DIR : '',
            ($value['Lang_code'] != $config['lang'] ? $value['Lang_code'] . '/' : ''),
            $value['Original_path'],
            (preg_match('/\.[^\.\/]+\/?$|\?/', $value['Original_path']) ? '' : '/')
        );
    }

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
} else {
    if (!$rlSmarty->_tpl_vars['domain_info']) {
        $rlSmarty->assign_by_ref('domain_info', $domain_info);
    }

    if (!$config['mod_rewrite']) {
        $rlSmarty->assign('alerts', $rlLang->getSystem('lp_error_no_mod_rewrite'));
    }

    unset($l_block_sides['header_banner'], $l_block_sides['integrated_banner']);

    $reefless->loadClass('LandingPage', null, 'langdingPage');

    if ($_GET['action']) {
        $bcAStep = $_GET['action'] == 'add' ? $lang['add_page'] : $lang['edit_page'];
    }

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        $allLangs = $GLOBALS['languages'];
        $langCount = count($GLOBALS['languages']);
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        $has_www = false !== strpos($domain_info['host'], 'www.');
        $rlSmarty->assign('has_www', $has_www);

        if ($_GET['page']) {
            $page_id = (int) $_GET['page'];
            $page_info = $rlDb->fetch('*', array('ID' => $page_id), null, 1, 'landing_pages', 'row');
            $rlDb->outputRowsMap = ['Lang_code', true];
            $page_lang_info = $rlDb->fetch('*', array('Page_ID' => $page_id), null, null, 'landing_pages_lang');
        }

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $_POST['use_subdomain'] = $page_info['Use_subdomain'];
            $_POST['text_position'] = $page_info['Box_position'];
            $_POST['use_design'] = $page_info['Box_design'];
            $_POST['status'] = $page_info['Status'];

            foreach ($allLangs as $lang_code => $language) {
                $pli = $page_lang_info[$lang_code];

                $_POST['landing_page_url'][$lang_code] = $pli['Landing_path'];
                $_POST['landing_page_subdomain'][$lang_code] = $pli['Landing_subdomain'];
                $_POST['original_page_url'][$lang_code] = $pli['Original_path'];
                $_POST['original_page_subdomain'][$lang_code] = $pli['Original_subdomain'];
                $_POST['meta_title'][$lang_code] = $pli['Meta_title'];
                $_POST['meta_h1'][$lang_code] = $pli['Meta_h1'];
                $_POST['meta_description'][$lang_code] = $pli['Meta_description'];
                $_POST['meta_keywords'][$lang_code] = $pli['Meta_keywords'];
                $_POST['seo_text_' . $lang_code] = $pli['Seo_text'];
            }
        }

        if (isset($_POST['submit'])) {
            $errors = array();
            $error_fields = array();

            $use_subdomain = $_POST['use_subdomain'];
            $landing_page_url = $_POST['landing_page_url'];
            $landing_page_subdomain = $_POST['landing_page_subdomain'];
            $original_page_url = $_POST['original_page_url'];
            $original_page_subdomain = $_POST['original_page_subdomain'];

            $meta_title = $_POST['meta_title'];
            $meta_h1 = $_POST['meta_h1'];
            $meta_description = $_POST['meta_description'];
            $meta_keywords = $_POST['meta_keywords'];
            $text_position = $_POST['text_position'];
            $use_design = $_POST['use_design'];
            $status = $_POST['status'];

            if ($use_subdomain) {
                $rlLandingPage->validateField($landing_page_subdomain, 'landing_page_subdomain', 'lp_landing_page_subdomain', $errors, $error_fields, true);
                $rlLandingPage->validateField($original_page_subdomain, 'original_page_subdomain', 'lp_original_page_subdomain', $errors, $error_fields, true);
            }

            $rlLandingPage->validateField($landing_page_url, 'landing_page_url', 'lp_landing_page_url', $errors, $error_fields);
            $rlLandingPage->validateField($original_page_url, 'original_page_url', 'lp_original_page_url', $errors, $error_fields);
            $rlLandingPage->validateField($meta_title, 'meta_title', 'title', $errors, $error_fields);
            $rlLandingPage->validateField($meta_description, 'meta_description', 'meta_description', $errors, $error_fields);

            $rlLandingPage->validatePath($landing_page_url, 'landing', $errors, $error_fields, true);
            $rlLandingPage->validatePath($original_page_url, 'original', $errors, $error_fields);

            if (!$errors && $_GET['action'] == 'add') {
                $rlLandingPage->checkExistingPath($landing_page_url, $landing_page_subdomain, 'landing', $errors, $error_fields);
                $rlLandingPage->checkExistingPath($original_page_url, $original_page_subdomain, 'original', $errors, $error_fields);
            }

            $rlLandingPage->checkSystemPath($landing_page_url, $errors, $error_fields);

            foreach ($allLangs as $lang_code => $language) {
                if ($_POST['seo_text_' . $lang_code] && !$text_position) {
                    $errors[] = str_replace('{field}', "<b>" . $lang['block_side'] . "</b>", $lang['notice_select_empty']);
                    $error_fields[] = "text_position";
                }
            }

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                $data = [
                    'Use_subdomain' => $use_subdomain ? '1' : '0',
                    'Box_position' => $text_position,
                    'Box_design' => $use_design ? '1' : '0',
                    'Status' => $status
                ];

                if ($_GET['action'] == 'add') {
                    $rlDb->insertOne($data, 'landing_pages');
                    $page_id = $rlDb->insertID();
                } elseif ($_GET['action'] == 'edit') {
                    $update_data = [
                        'fields' => $data,
                        'where' => ['ID' => $page_id]
                    ];
                    $rlDb->updateOne($update_data, 'landing_pages');
                }

                foreach ($allLangs as $lang_code => $language) {
                    $lang_data = [
                        'Lang_code' => $lang_code,
                        'Page_ID' => $page_id,
                        'Landing_path' => $rlLandingPage->preparePath($landing_page_url[$lang_code]),
                        'Landing_subdomain' => $rlLandingPage->preparePath($landing_page_subdomain[$lang_code]),
                        'Original_path' => $rlLandingPage->preparePath($original_page_url[$lang_code]),
                        'Original_subdomain' => $rlLandingPage->preparePath($original_page_subdomain[$lang_code]),
                        'Meta_title' => $meta_title[$lang_code],
                        'Meta_h1' => $meta_h1[$lang_code],
                        'Meta_description' => $meta_description[$lang_code],
                        'Meta_keywords' => $meta_keywords[$lang_code],
                        'Seo_text' => $_POST['seo_text_' . $lang_code],
                    ];

                    if ($rlDb->getOne('Lang_code', "`Lang_code` = '{$lang_code}' AND `Page_ID` = {$page_id}", 'landing_pages_lang')) {
                        $update_lang_data = [
                            'fields' => $lang_data,
                            'where' => [
                                'Lang_code' => $lang_code,
                                'Page_ID' => $page_id
                            ]
                        ];
                        $rlDb->updateOne($update_lang_data, 'landing_pages_lang');
                    } else {
                        $rlDb->insertOne($lang_data, 'landing_pages_lang');
                    }
                }

                $message = $rlLang->getSystem($_GET['action'] == 'add' ? 'page_added' : 'page_edited');
                $aUrl = array("controller" => $controller);

                $reefless->loadClass('Notice');
                $rlNotice->saveNotice($message);
                $reefless->redirect($aUrl);
            }
        }
    }
}
