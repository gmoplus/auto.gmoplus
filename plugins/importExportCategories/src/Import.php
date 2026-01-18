<?php


/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: IMPORT.PHP
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

/**
 * Class Import
 * @package Flynax\Plugins\ImportExportCategories
 * @since 3.0.0
 */
class Import
{
    /**
     * List of allowed file formats for import
     */
    public const ALLOWED_FORMATS = ['xls', 'xlsx'];

    /**
     * Max length of category key
     * @since 3.0.1
     */
    public const KEY_MAX_LENGTH = 32;

    /**
     * Path file
     *
     * @string
     */
    public static $tempFile = RL_TMP . 'upload/import_export_categories.tmp';

    /**
     * Count import category
     *
     * @int
     */
    public static $lastImportCount = 0;

    /**
     * @var Provider
     */
    private $provider;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->provider = new Provider();
    }

    /**
     * Move file the list categories
     *
     * @param array $file - File data
     *
     * @return bool
     */
    public static function moveUploadedFile(array $file): bool
    {
        if ('' === $file['tmp_name'] || !move_uploaded_file($file['tmp_name'], self::$tempFile)) {
            return false;
        }

        chmod(self::$tempFile, 0644);

        return true;
    }

    /**
     * Import categories
     *
     * @param array|null  $categories  - List categories data
     * @param string|null $listingType - Is listing type
     *
     * @return bool
     */
    public function import(?array $categories = [], ?string $listingType = ''): bool
    {
        global $rlValid, $rlDb, $languages, $config, $rlLang;

        $categoryLangNames       = [];
        $categoryLangTitles      = [];
        $categoryLangH1          = [];
        $categoryLangDescription = [];
        $categoryLangKeywords    = [];

        if (!$categories || !$listingType) {
            return false;
        }

        $addedCategoryCount = 0;
        $module             = $rlDb->columnExists('Target_key', 'lang_keys') ? 'category' : 'common';

        // Load the utf8 lib functions
        loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

        foreach ($categories as $iCat) {
            $path       = trim(str_replace('//', '/', $iCat['Path']), '/');
            $parentInfo = Provider::getParentInfo($path, $listingType);
            $parentID   = (int) $parentInfo['ID'];
            $key        = utf8_is_ascii($iCat['Name']) ? $iCat['Name'] : utf8_to_ascii($iCat['Name']);
            $key        = $rlValid->str2key($key);

            if (isset($_SESSION['imex_plugin']['maxParentPosition'][$parentID])) {
                $maxPos = $_SESSION['imex_plugin']['maxParentPosition'][$parentID];
            } else {
                $maxPos = (int) $rlDb->getRow(
                    "SELECT MAX(`Position`) AS `max` FROM `{db_prefix}categories` WHERE `Parent_ID` = {$parentID}"
                )['max'];

                $_SESSION['imex_plugin']['maxParentPosition'][$parentID] = $maxPos;
            }
            $maxPos++;

            $iCat['Name']            = str_replace('&amp;', '&', $iCat['Name']);
            $categoryName            = stripcslashes($rlValid->xSql($iCat['Name']));
            $categoryTree            = $parentID ? "{$parentInfo['Tree']}.{$maxPos}" : $maxPos;
            $categoryKey             = substr($key, 0, self::KEY_MAX_LENGTH) . '_' . uniqid();
            $categoryH1              = $iCat['H1'];
            $categoryTitle           = $iCat['Title'];
            $categoryMetaDescription = $iCat['Meta_description'];
            $categoryMetaKeywords    = $iCat['Meta_keywords'];

            // Prepare data
            $categoryInfo = [
                'Key'       => $categoryKey,
                'Parent_ID' => $parentID,
                'Position'  => $maxPos,
                'Path'      => $path,
                'Level'     => $this->getCatLevel($path),
                'Type'      => $listingType,
                'Lock'      => (int) $iCat['Lock'],
                'Modified'  => date('Y-m-d h:i:s'),
                'Status'    => 'active',
                'Tree'      => $categoryTree,
            ];

            if ($config['multilingual_paths']) {
                foreach ($languages as $language) {
                    if ($language['Code'] === $config['lang']) {
                        continue;
                    }

                    if ($iCat['Path:' . $language['Code']]) {
                        $categoryInfo['Path_' . $language['Code']] = $iCat['Path:' . $language['Code']];
                    }

                    $categoryInfo['Path_' . $language['Code']] = $rlValid->str2multiPath(
                        $categoryInfo['Path_' . $language['Code']],
                        true
                    );

                    /**
                     * @todo - Remove it when compatibility will be > 4.9.1
                     */
                    if (version_compare($config['rl_version'], '4.9.1', '<=')) {
                        $categoryInfo['Path_' . $language['Code']] = mb_strtolower(
                            $categoryInfo['Path_' . $language['Code']],
                            'UTF-8'
                        );
                    }
                }
            }

            // Get category parent IDs
            if ($parentID) {
                $parentIDs  = [$parentID];
                if ($parentInfo['Parent_IDs']) {
                    foreach (explode(',', $parentInfo['Parent_IDs']) as $parentCategoryID) {
                        $parentIDs[] = $parentCategoryID;
                    }
                }
                sort($parentIDs);
                $categoryInfo['Parent_IDs'] = ltrim(implode(',', $parentIDs), ',');

                $parentKeys = $parentInfo['Parent_keys']
                    ? $parentInfo['Parent_keys'] . ',' . $parentInfo['Key']
                    : $parentInfo['Key'];
                $categoryInfo['Parent_keys'] = $parentKeys;
            }

            $multilingualData = [];
            if ($languages) {
                foreach ($iCat as $categoryIndex => $categoryValue) {
                    if (false === strpos($categoryIndex, ':')) {
                        continue;
                    }

                    [$value, $languageCode] = explode(':', $categoryIndex);
                    $multilingualData[$value][$languageCode] = $categoryValue;
                }
            }

            if ($rlDb->insertOne($categoryInfo, 'categories')) {
                $addedCategoryCount++;

                $this->addToArray($this->getLangArray(
                    'categories+name+' . $categoryKey,
                    $categoryName,
                    $module,
                    $multilingualData['Name']
                ), $categoryLangNames);

                $this->addToArray($this->getLangArray(
                    'categories+title+' . $categoryKey,
                    $categoryTitle,
                    $module,
                    $multilingualData['Title']
                ), $categoryLangTitles);

                $this->addToArray($this->getLangArray(
                    'categories+h1+' . $categoryKey,
                    $categoryH1,
                    $module,
                    $multilingualData['H1']
                ), $categoryLangH1);

                $this->addToArray($this->getLangArray(
                    'categories+meta_description+' . $categoryKey,
                    $categoryMetaDescription,
                    $module,
                    $multilingualData['Meta_description']
                ), $categoryLangDescription);

                $this->addToArray($this->getLangArray(
                    'categories+meta_keywords+' . $categoryKey,
                    $categoryMetaKeywords,
                    $module,
                    $multilingualData['Meta_keywords']
                ), $categoryLangKeywords);

                $_SESSION['imex_plugin']['maxParentPosition'][$parentID] = $maxPos;
            }
        }

        // Save phrases
        if (!empty($categoryLangNames)) {
            $categoryPhrases = $categoryLangNames;
            unset($categoryLangNames);

            if (!empty($categoryLangTitles)) {
                $categoryPhrases = array_merge($categoryPhrases, $categoryLangTitles);
                unset($categoryLangTitles);
            }
            if (!empty($categoryLangH1)) {
                $categoryPhrases = array_merge($categoryPhrases, $categoryLangH1);
                unset($categoryLangH1);
            }
            if (!empty($categoryLangDescription)) {
                $categoryPhrases = array_merge($categoryPhrases, $categoryLangDescription);
                unset($categoryLangDescription);
            }
            if (!empty($categoryLangKeywords)) {
                $categoryPhrases = array_merge($categoryPhrases, $categoryLangKeywords);
                unset($categoryLangKeywords);
            }

            if (method_exists($rlLang, 'createPhrases')) {
                $rlLang->createPhrases($categoryPhrases);
            } else {
                DatabaseHandler::insert($rlDb, $categoryPhrases, 'lang_keys');
            }
        }

        self::$lastImportCount = $addedCategoryCount;
        $_SESSION['imex_plugin']['ic_count'] += $addedCategoryCount;

        return true;
    }

    /**
     * Getting array lang, all lang in system
     *
     * @param string $key    - Phrases key
     * @param string $value  - Phrases value
     * @param string $module - Phrases module
     *
     * @return array - Array of phrases in the languages used
     */
    public function getLangArray(string $key, string $value, string $module, ?array $multilingualData): array
    {
        $phrases = [];
        foreach ($GLOBALS['languages'] as $language) {
            if (!$multilingualData[$language['Code']] && !$value) {
                continue;
            }

            $phrases[] = [
                'Code'   => $language['Code'],
                'Module' => $module,
                'Status' => 'active',
                'Key'    => $key,
                'Value'  => $multilingualData[$language['Code']] ?: $value,
            ];
        }

        return $phrases;
    }

    /**
     * Adds elements from the array
     *
     * @param array $from  - Array the phrases
     * @param array $array - Array to add phrases
     *
     * @return  void
     */
    public function addToArray(array $from, array &$array): void
    {
        foreach ($from as $value) {
            $array[] = $value;
        }
    }

    /**
     * Counts the number of levels along the path
     *
     * @param  $path - Category path
     *
     * @return  int
     */
    public function getCatLevel($path): int
    {
        return substr_count($path, '/');
    }

    /**
     * Preparing data for importing categories
     *
     * @param int $stack - Start position
     *
     * @return array
     */
    public function fromStack(int $stack): array
    {
        global $rlDb;

        $response = [];

        if (file_exists(self::$tempFile)) {
            $start = $stack;
            $limit = 1000;
            $stop = $start + $limit;
            $next_iteration = false;

            $languages = $rlDb->fetch(['Code'], ['Status' => 'active'], null, null, 'languages');
            $GLOBALS['languages'] = &$languages;
            $listing_type = $_SESSION['imex_plugin']['listing_type'];
            $categories = $this->provider->getCategories($start, $stop, true);
            $c_count = count($categories);
            if ($c_count === $limit) {
                $next_iteration = true;
            }

            $status = $this->import($categories, $listing_type);

            $response = [
                'ic_count' => self::$lastImportCount,
                'next'     => $next_iteration,
                'start'    => $stop,
                'status'   => $status,
            ];
        }

        return $response;
    }

    /**
     * Checks that file must have first row with column names
     *
     * @return bool
     */
    public function isExistHeaderRow(): bool
    {
        $sources = $this->provider::getFromFile();

        if (!($firstRow = $sources[0] ?? null)) {
            return false;
        }

        $filledColumns = [];
        foreach ($firstRow as $column) {
            if (!empty($column)) {
                $filledColumns[] = $column;
            }
        }

        // Add checking of multilingual fields if they found in file
        if (count($filledColumns) > count($this->provider->columnNames)) {
            $this->provider->collectMultilingualColumns();
        }

        foreach ($this->provider->columnNames as $columnIndex => $columnName) {
            if (!$firstRow[$columnIndex] || $firstRow[$columnIndex] !== $columnName) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return void
     */
    public static function removeImportFile(): void
    {
        if (file_exists(self::$tempFile)) {
            unlink(self::$tempFile);
        }
    }
}
