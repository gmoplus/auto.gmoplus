<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : DATAENTRIESIMPORT.INC.PHP
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

$allLangs = $GLOBALS['languages'];
$rlSmarty->assign_by_ref('allLangs', $allLangs);

$reefless->loadClass('DataEntriesImport', false, 'dataEntriesImport');
$reefless->loadClass('Actions');

$isMFInstalled = $rlDataEntriesImport->isMFInstalled();
$rlSmarty->assign('isMFInstalled', $isMFInstalled);

$sql = "SELECT `T1`.`ID`, `T1`.`Key` " . ($isMFInstalled ? ', IF(`T2`.`Key` IS NOT NULL, 1, 0) AS `mf` ' : '')
    . "FROM `{db_prefix}data_formats` AS `T1` "
    . ($isMFInstalled
        ? "LEFT JOIN `{db_prefix}multi_formats` AS `T2` 
            ON (`T1`.`Key` = `T2`.`Key`) "
        : ''
    )
    . "WHERE `T1`.`Status` <> 'trash' AND `T1`.`Key` <> 'years' AND `T1`.`Parent_ID` = 0 ";

$dataFormats = $rlDb->getAll($sql);
$dataFormats = $rlLang->replaceLangKeys($dataFormats, 'data_formats', array('name'));

$rlSmarty->assign_by_ref('data_formats', $dataFormats);

if (!$_POST['upload'] || $_REQUEST['xjxfun']) {
    return;
}

$sourceFile = $_FILES['source'];

$errors = $error_fields = array();
$allowedTypes = array(
    'text/csv',
    'text/plain',
    'application/vnd.ms-excel',
    'application/octet-stream',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
);

if ($errno = $sourceFile['error']) {
    $errors[] = $lang['dataEntriesImport_error_upload'];
} elseif (!in_array($sourceFile['type'], $allowedTypes, true)) {
    $errors[] = $lang['dataEntriesImport_unsupportedFileType'];
} else {
    $importTo = $_POST['import_to'];
    $importToSuffix = $importTo == 'new' ? '_new' : '';

    $dfID = (int) $_POST['import_to_parent' . $importToSuffix];
    $dfKey = (string) $rlDb->getOne('Key', "`ID` = {$dfID}", 'data_formats');

    if ($importTo === 'new') {
        $entryName = $_POST['name'];
        $systemLang = $config['lang'];
        $defName = $entryName[$systemLang];
        $dfKey = $rlDataEntriesImport->keyByName($defName);

        if (empty($dfKey) || strlen($dfKey) < 3) {
            $errors[] = $lang['dataEntriesImport_newEntryNameIsTooShort'];
            $error_fields[] = "name[{$systemLang}]";
        } elseif ((bool) $rlDb->getOne('ID', "`Key` = '{$dfKey}'", 'data_formats')) {
            $errors[] = str_replace('{entry}', $dfKey, $lang['dataEntriesImport_entryAlreadyExist']);
            $error_fields[] = "name[{$systemLang}]";
        } else {
            $langKeys = array();

            foreach ($allLangs as $language) {
                $localizedName = (!empty($entryName[$language['Code']])
                    ? $entryName[$language['Code']]
                    : $entryName[$systemLang]
                );
                $langKeys[] = array(
                    'Key'    => "data_formats+name+{$dfKey}",
                    'Value'  => $localizedName,
                    'Code'   => $language['Code'],
                    'Module' => 'common',
                );
            }

            $data = array(
                'Key'        => $dfKey,
                'Order_type' => (in_array($_POST['order_type'], array('alphabetic', 'position'))
                    ? $_POST['order_type']
                    : 'position'
                ),
            );

            $rlActions->insertOne($data, 'data_formats');
            $dfID = $rlDb->insertID();

            $rlActions->insert($langKeys, 'lang_keys');
        }
    }
}

$rlDataEntriesImport->parentID  = $dfID;
$rlDataEntriesImport->parentKey = $dfKey;

if (!move_uploaded_file($sourceFile['tmp_name'], $rlDataEntriesImport->tmpFile)) {
    $errors[] = $lang['dataEntriesImport_error_upload'];
} else {
    chmod($rlDataEntriesImport->tmpFile, 0644);
}

if (!empty($errors)) {
    $rlSmarty->assign_by_ref('errors', $errors);

    return;
}

$delimiter = $_POST['delimiter'];
$delimiter = (string) $rlDataEntriesImport->delimiters[$delimiter];
$parsedFileName = explode('.', $sourceFile['name']);
$sourceExt = end($parsedFileName);

try {
    $rlDataEntriesImport->import($sourceExt, $delimiter);

    if (is_file($rlDataEntriesImport->tmpFile)) {
        unlink($rlDataEntriesImport->tmpFile);
    }
} catch (Exception $e) {
    $errors[] = $e->getMessage();
    $rlSmarty->assign('errors', $errors);

    return;
}

$rlCache->updateDataFormats();
$rlCache->updateForms();

$reefless->loadClass('Notice');

if ($importedEntriesCount = $rlDataEntriesImport->getImportedEntries()) {
    $notice = str_replace('{count}', $importedEntriesCount, $lang['dataEntriesImport_notice']);
} else {
    $notice = $lang['dataEntriesImport_noticeNotingToImport'];
}
$rlNotice->saveNotice($notice);

$reefless->redirect(array('controller' => 'dataEntriesImport'));
