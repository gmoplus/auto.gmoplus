<?php


/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : IMPORTEXPORTCATEGORIES.INC.PHP
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

use Flynax\Plugins\ImportExportCategories\Export;
use Flynax\Plugins\ImportExportCategories\ExportException;
use Flynax\Plugins\ImportExportCategories\Import;
use Flynax\Plugins\ImportExportCategories\Provider;

require __DIR__ . '/../vendor/autoload.php';

if (isset($_GET['q']) && $_GET['q'] === 'ext') {
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    $start    = (int) $_GET['start'];
    $limit    = (int) $_GET['limit'];
    $stop     = $start + $limit;
    $provider = new Provider();

    echo json_encode([
        'data'  => $provider->getCategories($start, $stop),
        'total' => $provider::getTotalCategories(),
    ]);
} else {
    if (isset($_GET['done'])) {
        (new Import())::removeImportFile();
        $rlCache->updateCategories();
        $rlNotice->saveNotice(str_replace("{count}", $_SESSION['imex_plugin']['ic_count'], $lang['importExportCategories_count']));
        unset($_SESSION['imex_plugin']);
    }

    if (!$_GET['action']) {
        $bcAStep = $lang['importExportCategories_import'];
    } elseif ($_GET['action'] === 'import') {
        $bcAStep = $lang['importExportCategories_import_preview'];
    } elseif ($_GET['action'] === 'export') {
        $bcAStep = $lang['importExportCategories_export'];
    }

    if (!$_GET['action']) {
        $provider = new Provider();
        $rlSmarty->assign('systemColumns', $provider->columnNames);
        $rlSmarty->assign('multilingualColumns', $provider->getColumnNames());
        $rlSmarty->assign('allowedFormats', Import::ALLOWED_FORMATS);
    } elseif ($_GET['action'] === 'import' && isset($_POST['submit'])) {
        $errors   = [];
        $file      = $_FILES['file_import'];
        $pathInfo = pathinfo($file['name']);
        $import   = new Import();

        if (0 !== $file['error']) {
            $errors[] = $lang['importExportCategories_not_move_file'];
        } elseif ('' === $pathInfo['filename']) {
            $errors[] = strtr($lang['importExportCategories_import_filename_empty'], ['{field}' => "<b>{$lang['file']}</b>"]);
        } elseif (!in_array($pathInfo['extension'], $import::ALLOWED_FORMATS, true)) {
            $errors[] = $lang['importExportCategories_incorrect_file_ext'];
        } elseif (empty($_POST['export_listing_type'])) {
            $errors[] = strtr($lang['importExportCategories_import_filename_empty'], ['{field}' => "<b>{$lang['listing_type']}</b>"]);
        } elseif (!$import::moveUploadedFile($file)) {
            $errors[] = $lang['importExportCategories_not_move_file'];
        } elseif (!$import->isExistHeaderRow()) {
            $errors[] = $lang['importExportCategories_missing_header_row'];
        } elseif (Provider::noValidCategoryPath()) {
            $errors[] = $lang['category_url_listing_logic'];
        } else {
            $_SESSION['imex_plugin']['listing_type'] = $_POST['export_listing_type'];
            if ($_POST['export_category_id']) {
                $_SESSION['imex_plugin']['category_id'] = $_POST['export_category_id'];
            }
            $reefless->redirect(['controller' => 'importExportCategories', 'action' => 'import']);
        }

        if ($errors) {
            $import::removeImportFile();

            $reefless->loadClass('Notice');
            $rlNotice->saveNotice($errors, 'errors');
            $reefless->redirect(['controller' => 'importExportCategories']);

        }
    } elseif ($_GET['action'] === 'export') {
        if (isset($_POST['submit'])) {
            try {
                (new Export)->export();
            } catch (ExportException|\PhpOffice\PhpSpreadsheet\Exception $e) {
                $errors[] = $e->getMessage();
            }
        } else {
            if (!isset($_REQUEST['xjxfun'])) {
                $rlSmarty->assign('sections', $rlCategories->getCatTree(0, false, true));
            }

            $rlXajax->registerFunction(['getCatLevel', $rlCategories, 'ajaxGetCatLevel']);
        }
    }
}
