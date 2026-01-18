<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : BAD_WORDS_FILTER.INC.PHP
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

if ($_GET['q'] == 'ext') {

    /* system config */
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    $reefless->loadClass('BadWords', null, 'badWordsFilter');
    $reefless->loadClass('Actions');

    /* update badword value form grid */
    if ($_GET['action'] == "update") {
        $update_data = array(
            'fields' => array(
                'Value' => $_GET['value'],
            ),
            'where' => array(
                'ID' => intval($_GET['id']),
            ),
        );
        $rlDb->updateOne($update_data, 'bad_words');
    } else {
        /* data read */
        $limit = intval($_GET['limit']);
        $start = intval($_GET['start']);
        $lang_id = intval($_GET['lang']);
        $lang_code = $rlDb->getOne('Code', "`ID`=" . $lang_id, "languages");

        /* search badwords */
        if ($_GET['action'] == "search") {

            $lang_code = $rlValid->xSql($_GET['Code']);
            $badword_value = $rlValid->xSql($_GET['name']);
            $exact_match = $rlValid->xSql($_GET['exact_match']);

            $sql = "SELECT * FROM `" . RL_DBPREFIX . "bad_words` WHERE ";
            if ($_GET['exact_match']) {
                $sql .= "`Value` LIKE '%{$badword_value}%' ";
            } else {
                $sql .= "`Value` = '{$badword_value}' ";
            }
            $sql .= "AND `Code` = '" . $lang_code . "'LIMIT {$start}, {$limit}";
        } else {
            $sql = "SELECT * FROM `" . RL_DBPREFIX . "bad_words` WHERE `Status` <> 'trash' ";
            $sql .= "AND `Code`='" . $lang_code . "' LIMIT {$start}, {$limit}";
        }
        $data = $rlDb->getAll($sql);

        foreach ($data as $key => $value) {
            $data[$key]['name'] = $data[$key]['Value'];
            $data[$key]['Status'] = $GLOBALS['lang'][$data[$key]['Status']];
        }

        $sql = "SELECT COUNT(`ID`) AS `count` FROM `" . RL_DBPREFIX . "bad_words` ";
        $sql .= "WHERE `Status` <> 'trash' AND `Code` = '" . $lang_code . "'";
        $count = $rlDb->getRow($sql);

        $output['total'] = $count['count'];
        $output['data'] = $data;

        echo json_encode($output);
    }

} else {

    $bcAStep[0]['name'] = $lang['bw_breadcumb_title'];

    $allLangs = $GLOBALS['languages'];
    $rlSmarty->assign_by_ref('allLangs', $allLangs);
    $reefless->loadClass('BadWords', null, 'badWordsFilter');

    if (!isset($_GET['lang'])) {
        $aUrl = array('controller' => 'languages');
        $reefless->redirect($aUrl);
    }

    $lang_id = intval($_GET['lang']);
    $lang_code = $rlDb->getOne('Code', "`ID`=" . $lang_id, "languages");

    if (!empty($_POST['import'])) {

        if (empty($_FILES['badword_file'])) {
            return false;
        }

        if ($_POST['delimiter'] == 'another' && $_POST['delimiter_another']) {
            $delimiter = $_POST['delimiter_another'];
        } else {
            $delimiter = $_POST['delimiter'];
        }

        switch ($delimiter) {
            case 'new_line':
                $file_delimiter = PHP_EOL;
                break;
            case 'comma':
                $file_delimiter = ',';
                break;
            default:
                $file_delimiter = $delimiter;
                break;
        }

        $file_path = $_FILES['badword_file']['tmp_name'];

        $fp = @fopen($file_path, 'r');
        if ($fp) {
            $badwordsArray = explode($file_delimiter, fread($fp, filesize($file_path)));
            $badwordsArray = array_diff($badwordsArray, array(''));
            $rlBadWords->importBadWords($badwordsArray, $lang_code, $file_delimiter);
        }
    }
    
    $rlXajax->registerFunction(array('importBadWords', $rlBadWords, 'ajaxImportBadWords'));
}
