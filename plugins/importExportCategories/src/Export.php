<?php


/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : EXPORT.PHP
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

namespace Flynax\Plugins\ImportExportCategories;

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use RuntimeException;

/**
 * Class Export
 * @package Flynax\ImportExportCategories
 * @since 3.0.0
 */
class Export
{
    /**
     * ID subcategory array
     *
     * @var array
     */
    private static $categoriesIDs = [];

    /**
     * @var Spreadsheet
     */
    private $spreadsheet;

    /**
     * @var int
     */
    private $rowIndex = 1;

    /**
     * @var Provider
     */
    private $provider;

    /**
     * Constructor of class
     */
    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
        $this->provider    = new Provider();
    }

    /**
     * Export categories to Excel file
     *
     * @return void
     *
     * @throws Exception
     */
    public function export(): void
    {
        global $lang, $config, $rlDb;

        // Set document properties
        $this->spreadsheet->getProperties()
            ->setCreator($config['owner_name'])
            ->setLastModifiedBy($config['owner_name'])
            ->setTitle('')
            ->setSubject('')
            ->setDescription('')
            ->setKeywords('')
            ->setCategory('');

        $subIncludeCategory = [];
        if (isset($_POST['cat_sticky'])) {
            $categoriesExport = $rlDb->getAll("SELECT `ID` FROM `{db_prefix}categories`", [false, 'ID']);
        } else {
            $categoriesExport   = explode(',', $_POST['str_category']);
            $subIncludeCategory = explode(',', $_POST['strincludeType']);
        }

        if (!empty($categoriesExport)) {
            $this->provider->collectMultilingualColumns();

            $this->addRow($this->provider->columnNames, true);

            // Collect categories
            foreach ($categoriesExport as $id) {
                $categoryInfo = self::getSubCatInfo($id);

                $this->stepBeforeBuildDoc($categoryInfo);

                if ($categoryInfo && $subIncludeCategory && in_array($categoryInfo['Type'], $subIncludeCategory, true)) {
                    foreach (self::getSubCategoriesIDs($categoryInfo['ID']) as $subCatID) {
                        $subCatInfo = self::getSubCatInfo($subCatID);
                        $this->stepBeforeBuildDoc($subCatInfo);
                    }
                }
            }

            $fileName = 'categories-' . date('M\.j\-Y');

            // Send necessary headers
            // Redirect output to a client’s web browser (Xlsx)
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename={$fileName}.xlsx");
            header('Cache-Control: max-age=0');
            // If you're serving to IE 9, then the following may be needed
            header('Cache-Control: max-age=1');

            // If you're serving to IE over SSL, then the following may be needed
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0

            $writer = IOFactory::createWriter($this->spreadsheet, 'Xlsx');
            $writer->save('php://output');
            exit;
        }

        throw new RuntimeException($lang['importExportCategories_empty']);
    }

    /**
     * Data selection by category id
     *
     *  @since 2.3.0
     *
     * @param int $id - category id
     *
     * @return array
     */
    private static function getSubCatInfo(int $id): array
    {
        global $rlDb, $config;

        $countLang = 0;
        $langPath = '';

        if ($config['multilingual_paths']) {
            foreach ($GLOBALS['languages'] as $lang) {
                if ($countLang > 0) {
                    $langPath .= 'Path_' . $lang['Code'];
                    $langPath .= $countLang < count($GLOBALS['languages']) - 1 ? ' , ' : '';
                }
                $countLang++;
            }
        }

        $sql = "SELECT `ID`,`Key`, `Parent_ID`, `Path`, `Lock`, `Type`";
        $sql .=  $langPath ? ',' . $langPath : '';
        $sql .= " FROM `{db_prefix}categories` ";
        $sql .= "WHERE `ID` = {$id}";

        return (array) $rlDb->getRow($sql);
    }

    /**
     * Getting all subcategory IDs by parent_id
     *
     * @param $parentID
     *
     * @return array
     */
    private static function getSubCategoriesIDs($parentID): array
    {
        global $rlDb;

        if (!(int) $parentID) {
            return [];
        }

        $rlDb->outputRowsMap = [false, 'ID'];
        $where               = ['Status' => 'active'];
        $additionalWhere     = "AND FIND_IN_SET('{$parentID}', `Parent_IDs`) > 0";

        return (array) $rlDb->fetch(['ID'], $where, $additionalWhere, null, 'categories');
    }

    /**
     * Initialization parameter parent_id and calling method buildDocument
     *
     * @param array $category
     *
     * @return void
     */
    private function stepBeforeBuildDoc(array $category): void
    {
        global $rlDb, $lang, $rlLang, $config;

        $row = [];
        foreach ($this->provider->columnNames as $column) {
            switch ($column) {
                case 'Parent':
                    if ($category['Parent_ID'] && (int) $category['Parent_ID'] !== 0) {
                        $parentKey = $rlDb->getOne('Key', "`ID` = " . $category['Parent_ID'], 'categories');
                        $row[] = $lang['categories+name+' . $parentKey];
                    } else {
                        $row[] = '';
                    }
                    break;
                case 'Lock':
                case 'Path':
                    $row[] = Provider::adaptCategoryPath($category[$column]);
                    break;
                default:
                    $languageCode = $config['lang'];
                    if (strpos($column, ':')) {
                        [$column, $languageCode] = explode(':', $column);
                    }

                    // Multilingual path of category
                    if ($column === 'Path') {
                        $row[] = Provider::adaptCategoryPath($category['Path_' . $languageCode]);
                    } else {
                        $phraseKey = 'categories+' . strtolower($column) . '+' . $category['Key'];
                        $rlLang->getPhrase($phraseKey, $languageCode);

                        $row[] = $languageCode === $config['lang'] ? $lang[$phraseKey] : $lang[$languageCode . '_' . $phraseKey];
                    }

                    break;
            }
        }

        $this->addRow($row);
    }

    /**
     * Add new row to Excel file
     *
     * @param array     $row - Array will be placed in row ['Column1', 'Column2', and etc.]
     * @param bool|null $bold
     *
     * @return void
     */
    private function addRow(array $row, ?bool $bold = false): void
    {
        $this->spreadsheet->getActiveSheet()->fromArray([$row], NULL, 'A' . $this->rowIndex);

        if ($bold) {
            $lastFilledColumn = $this->spreadsheet->getActiveSheet()->getHighestDataColumn();
            $this->spreadsheet->getActiveSheet()
                ->getStyle('A' . $this->rowIndex . ':' . $lastFilledColumn . '1')
                ->getFont()
                ->setBold(true);
        }

        $this->rowIndex++;
    }
}
