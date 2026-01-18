<?php


/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: PROVIDER.PHP
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

namespace Flynax\Plugins\ImportExportCategories;

use Exception;
use Flynax\Utils\Category;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Class Provider
 * @package Flynax\ImportExportCategories
 * @since 3.0.0
 */
class Provider
{
    /**
     * Count in file
     *
     * @var int
     */
    private static $totalCategories = 0;

    /**
     * Names of default system fields which will be exported
     */
    public $columnNames = ['Name', 'Parent', 'Path', 'Title', 'H1', 'Meta_description', 'Meta_keywords', 'Lock'];

    /**
     * Names of default multilingual system fields which will be exported if on website several languages
     */
    public $multilingualColumnNames = ['Name', 'Title', 'H1', 'Meta_description', 'Meta_keywords'];

    /**
     * @return int
     */
    public static function getTotalCategories(): int
    {
        return self::$totalCategories;
    }

    /**
     * Reads the category from the import file
     *
     * @return array - List of categories
     */
    public static function getFromFile($missHeaderRow = false): array
    {
        try {
            $spreadsheet = IOFactory::load(Import::$tempFile);
            $worksheet = $spreadsheet->getActiveSheet();
            $source  = [];
            foreach ($worksheet->getRowIterator() as $row) {
                if ($missHeaderRow && $row->getRowIndex() === 1) {
                    continue;
                }

                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(FALSE); // This loops through all cells,
                $cells = [];
                foreach ($cellIterator as $cell) {
                    $cells[] = $cell->getCalculatedValue();
                }
                $source[] = $cells;
            }

            return $source;
        } catch (Exception $e) {
            $GLOBALS['rlDebug']->logger('imExpCategories: ' . __FUNCTION__ . '(), Got exception: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Checking the correct path
     *
     * @return bool - flag
     */
    public static function noValidCategoryPath(): bool
    {
        $sources = self::getFromFile();
        foreach($sources as $source) {
            $valuePath = $GLOBALS['rlValid']->str2path($source[2], true);
            if (preg_match('/\-\d+$/',$valuePath)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get categories list from file
     *
     * @param int  $start  - start position
     * @param int  $stop   - limit position
     * @param bool $import - flag
     *
     * @return array - list category
     */
    public function getCategories(int $start, int $stop, ?bool $import = false): array
    {
        global $lang, $languages, $config;

        $source             = self::getFromFile(true);
        $categories         = [];
        $parentCategoryInfo = null;

        if (isset($_SESSION['imex_plugin']['category_id'])) {
            $parentCategoryInfo = Category::getCategory((int) $_SESSION['imex_plugin']['category_id']);
        }

        if (!empty($source)) {
            $this->collectMultilingualColumns();

            self::$totalCategories = count($source);
            $ltype_key = $_SESSION['imex_plugin']['listing_type'];
            $limit = min(self::$totalCategories, $stop);
            $index = 0;
            $nameColumnIndex   = array_search('Name', $this->columnNames, true);
            $pathColumnIndex   = array_search('Path', $this->columnNames, true);
            $parentColumnIndex = array_search('Parent', $this->columnNames, true);

            for ($row = $start; $row < $limit; $row++) {
                /**
                 * Get parent info from XLS file if child category have wrong Path
                 * to add parent Path for child category
                 */
                if ($source[$row][$parentColumnIndex] !== null
                    && false === strpos($source[$row][$pathColumnIndex], '/')
                ) {
                    $parentPath = '';
                    foreach ($source as $category) {
                        if ($category[$nameColumnIndex]
                            && $category[$nameColumnIndex] === $source[$row][$parentColumnIndex]
                        ) {
                            $parentPath = $category[$pathColumnIndex] ?: '';
                            break;
                        }
                    }

                    if ($parentPath) {
                        $source[$row][$pathColumnIndex] = $parentPath . '/' . $source[$row][$pathColumnIndex];
                    }

                    if ($languages && $config['multilingual_paths']) {
                        foreach ($languages as $language) {
                            if ($language['Code'] === $config['lang']) {
                                continue;
                            }

                            $multilingualPathColumnIndex = array_search(
                                'Path:' . $language['Code'],
                                $this->columnNames,
                                true
                            );

                            if ($multilingualPathColumnIndex
                                && $source[$row][$multilingualPathColumnIndex]
                                && false === strpos($source[$row][$multilingualPathColumnIndex], '/')
                            ) {
                                $multilingualParentPath = '';
                                foreach ($source as $category) {
                                    if ($category[$nameColumnIndex]
                                        && $category[$nameColumnIndex] === $source[$row][$parentColumnIndex]
                                    ) {
                                        $multilingualParentPath = $category[$multilingualPathColumnIndex] ?: '';
                                        break;
                                    }
                                }

                                if ($multilingualParentPath) {
                                    $source[$row][$multilingualPathColumnIndex] = $multilingualParentPath
                                        . '/'
                                        . $source[$row][$multilingualPathColumnIndex];
                                }
                            }
                        }
                    }
                }

                // Selected parent in intro page
                if ($parentCategoryInfo !== null
                    && 0 !== strpos($source[$row][$pathColumnIndex], $parentCategoryInfo['Path'] . '/')
                ) {
                    $source[$row][$pathColumnIndex] = $parentCategoryInfo['Path'] . '/' . $source[$row][$pathColumnIndex];
                }

                if ($parentCategoryInfo !== null && $languages && $config['multilingual_paths']) {
                    foreach ($languages as $language) {
                        if ($language['Code'] === $config['lang']) {
                            continue;
                        }

                        $multilingualPathColumnIndex = array_search(
                            'Path:' . $language['Code'],
                            $this->columnNames,
                            true
                        );

                        $multilingualParentPath = $parentCategoryInfo['Path_' . $language['Code']] ?: $parentCategoryInfo['Path'];

                        if ($multilingualPathColumnIndex
                            && $source[$row][$multilingualPathColumnIndex]
                            && $multilingualParentPath
                            && 0 !== strpos($source[$row][$multilingualPathColumnIndex], $multilingualParentPath . '/')
                        ) {
                            $source[$row][$multilingualPathColumnIndex] = $multilingualParentPath
                                . '/'
                                . $source[$row][$multilingualPathColumnIndex];
                        }
                    }
                }

                if ($source[$row][$parentColumnIndex] === null && $parentCategoryInfo != null) {
                    $source[$row][$parentColumnIndex] = $parentCategoryInfo['name'];
                }

                $source[$row][$pathColumnIndex] = self::str2path($source[$row][$pathColumnIndex]);

                if (preg_match('/\-\d+$/', $source[$row][$pathColumnIndex])) {
                    return ['error'=>'true'];
                }

                if (true !== $import) {
                    $categories[$index]['Type'] = $lang['listing_types+name+' . $ltype_key];
                }

                foreach ($this->columnNames as $columnIndex => $columnName) {
                    $categories[$index][$columnName] = trim($source[$row][$columnIndex]);
                }

                $index++;
            }
        }

        return $categories;
    }

    /**
     * Get parent info by children path
     *
     * @param string $path        - Children path
     * @param string $listingType - Key of listing type
     *
     * @return array              - Parent info data
     */
    public static function getParentInfo(string $path = '', string $listingType = ''): array
    {
        global $rlDb;

        if (!$listingType || empty($path) || false === strpos($path, '/')) {
            return [];
        }

        $explodedPath = explode('/', $path);
        array_pop($explodedPath);
        $parentPath = implode('/', $explodedPath);

        static $parentCategory;
        $cacheCategoryKey = $parentPath . '_' . $listingType;

        if ($parentCategory[$cacheCategoryKey]) {
            return $parentCategory[$cacheCategoryKey];
        }

        $sql = "SELECT `ID`, `Key`, `Position`, `Tree`, `Parent_IDs`, `Parent_keys` FROM `{db_prefix}categories` ";
        $sql .= "WHERE `Path` = '{$parentPath}' AND `Type` = '{$listingType}' AND `Status` = 'active' LIMIT 1";
        $parentCategory[$cacheCategoryKey] = (array) $rlDb->getRow($sql);

        return $parentCategory[$cacheCategoryKey];
    }

    /**
     * @return void
     */
    public function collectMultilingualColumns(): void
    {
        global $languages, $config;

        if (!$languages) {
            $languages = $GLOBALS['rlLang']->getLanguagesList();
        }

        if (count($languages) <= 1) {
            return;
        }

        foreach ($languages as $language) {
            if ($config['lang'] === $language['Code']) {
                continue;
            }

            foreach ($this->multilingualColumnNames as $multilingualColumnName) {
                $this->columnNames[] = "{$multilingualColumnName}:{$language['Code']}";

                if ($config['multilingual_paths'] && $multilingualColumnName === 'Name') {
                    $this->columnNames[] = "Path:{$language['Code']}";
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getColumnNames(): array
    {
        $this->collectMultilingualColumns();
        return $this->columnNames;
    }

    /**
     * @param string|null $string
     *
     * @return string|null
     */
    public static function str2path(?string $string): ?string
    {
        if (!$string) {
            return null;
        }

        if (false !== strpos($string, '/')) {
            $stringParts = explode('/', $string);

            foreach ($stringParts as &$stringPart) {
                $stringPart = $GLOBALS['rlValid']->str2path($stringPart, true);
            }

            return implode('/', $stringParts);
        }

        return $GLOBALS['rlValid']->str2path($string, true);
    }

    /**
     * @param string|null $path
     *
     * @return string
     */
    public static function adaptCategoryPath(?string $path): string
    {
        $path = (string) $path;

        if (preg_match('/-\d+$/', $path)) {
            $path = preg_replace('/-(\d+)$/', '$1', $path);
        }

        return $path;
    }
}
