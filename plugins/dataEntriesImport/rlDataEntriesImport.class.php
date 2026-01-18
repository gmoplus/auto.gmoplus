<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLDATAENTRIESIMPORT.CLASS.PHP
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

use Flynax\Component\Filesystem;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Data Entries Import plugin class
 */
class rlDataEntriesImport extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * @var string
     */
    public $tmpFile;

    /**
     * @var array
     */
    public $delimiters = array(
        'new_line' => "\n",
        'tab'      => "\t",
    );

    /**
     * @var string
     */
    public $delimiter;

    /**
     * @var int - Data Entry parent ID
     */
    public $parentID;

    /**
     * @var string - Data Entry parent Key
     */
    public $parentKey;

    /**
     * @var array - Collect entries to multiple insert
     */
    private $data = array(
        'entries'   => array(),
        'lang_keys' => array(),
    );

    /**
     * @var string
     *
     * @since 1.2.0
     */
    private $table;

    /**
     * @var int
     */
    private $importedEntries;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->tmpFile = RL_UPLOAD . 'dataEntriesImport.tmp';
        $this->table   = '{db_prefix}data_formats';
    }

    /**
     * Check installation of the Multi-Field plugin
     *
     * @since 1.2.0
     */
    public function isMFInstalled()
    {
        return (isset($GLOBALS['plugins']['multiField']) || isset($GLOBALS['aHooks']['multiField']));
    }

    /**
     * Main import function
     *
     * @since 1.2.3 - PHPExcel library replaced by PHPSpreadsheet
     *
     * @param string      $format
     * @param string|null $delimiter
     *
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function import(string $format, ?string $delimiter): void
    {
        global $lang, $rlDb;

        if (!in_array($format, ['csv', 'txt', 'xls', 'xlsx'])) {
            throw new RuntimeException(str_replace('{ext}', "<b>{$format}</b>", $lang['notice_bad_file_ext']), 1);
        }
        $entries = array();

        if (false === class_exists('ZipArchive') && $format === 'xlsx') {
            throw new RuntimeException($lang['dataEntriesImport_zipArchiveRequired'], 3);
        }

        switch ($format) {
            case 'txt':
            case 'csv':
                $dataFile = file($this->tmpFile);

                if ($format === 'csv') {
                    $csv = array_map(static function($data) {
                        $data = trim($data);
                        return str_getcsv($data);
                    }, $dataFile);
                    $entries = $csv[0];
                } else {
                    $entries = $delimiter === $this->delimiters['tab'] ? preg_split('/\s{3}/', $dataFile[0]) : $dataFile;
                }
                break;
            case 'xls':
            case 'xlsx':
                require __DIR__ . '/vendor/autoload.php';

                $reader   = IOFactory::createReaderForFile($this->tmpFile);
                $document = $reader->load($this->tmpFile);
                $rows     = $document->getActiveSheet()->toArray('');

                foreach ($rows as $cell => $row) {
                    if (!empty($value = reset($row))) {
                        $entries[] = $value;
                    }
                    unset($rows[$cell]);
                }
                break;
        }

        if (empty($entries)) {
            throw new RuntimeException($lang['dataEntriesImport_nothingToSave'], 2);
        }

        $position = $rlDb->getRow("
            SELECT MAX(`position`) AS `max` FROM `{$this->table}` 
            WHERE `Parent_ID` = {$this->parentID}
        ");
        $position = (int) $position['max'] + 1;

        $children = array();
        $tmp = $GLOBALS['rlDb']->getAll("
            SELECT `Key` FROM `{$this->table}` 
            WHERE `Key` LIKE '{$this->parentKey}\_%' AND `Status` = 'active' 
            LIMIT 10000
        ");

        foreach($tmp as $i => $child) {
            $children[] = $child['Key'];
            unset($tmp[$i]);
        }

        foreach ($entries as  $item) {
            if (empty($name = trim($item))) {
                continue;
            }

            $key = $this->keyByName($name);

            if (in_array($key, $children)) {
                if (!empty($_POST['ignore_duplicates'])) {
                    continue;
                } else {
                    $key .= $position;
                }
            }

            $this->addItem($key, $name, $position++);
        }

        $this->save();
    }

    /**
     * Add entry to storage before multiple insert
     *
     * @param string $entryKey
     * @param string $entryName
     * @param int    $entryPosition
     */
    public function addItem($entryKey, $entryName, $entryPosition)
    {
        $this->data['entries'][] = array(
            'Key'       => $entryKey,
            'Parent_ID' => (int) $this->parentID,
            'Position'  => (int) $entryPosition,
        );

        foreach ($GLOBALS['languages'] as $language) {
            $this->data['lang_keys'][] = array(
                'Key'    => sprintf('data_formats+name+%s', $entryKey),
                'Value'  => (string) $entryName,
                'Code'   => $language['Code'],
                'Module' => 'common',
            );
        }
    }

    /**
     * @since 1.2.0
     *
     * @param $name
     *
     * @return string
     */
    public function keyByName($name)
    {
        if (false === function_exists('utf8_is_ascii')) {
            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');
        }

        if (!utf8_is_ascii($name)) {
            $name = utf8_to_ascii($name);
        }
        $key = $GLOBALS['rlValid']->str2key($name);

        if (!empty($this->parentKey)) {
            return $this->parentKey . '_' . $key;
        }

        return $key;
    }

    /**
     * Save entries to database
     */
    public function save()
    {
        global $rlActions;

        $rlActions->insert($this->data['entries'], 'data_formats');
        $rlActions->insert($this->data['lang_keys'], 'lang_keys');

        $this->importedEntries = count($this->data['entries']);
    }

    /**
     * @since  1.2.0
     * @return int
     */
    public function getImportedEntries()
    {
        return $this->importedEntries;
    }

    /**
     * @version 1.2.0
     */
    public function update120(): void
    {
        global $rlDb, $reefless;

        require RL_UPLOAD . 'dataEntriesImport/vendor/autoload.php';

        $filesystem = new Filesystem();
        $filesystem->copyTo(RL_UPLOAD . 'dataEntriesImport/vendor', __DIR__ . '/vendor');

        $rlDb->query("
            DELETE FROM `{db_prefix}lang_keys` WHERE `Key` IN(
                'dataEntriesImport_delimiter_comma',
                'dataEntriesImport_delimiter_another'
            )
        ");

        $reefless->deleteDirectory(__DIR__ . '/phpExcel');
    }

    /**
     * @version 1.2.2
     */
    public function update122(): void
    {
        global $rlDb;

        require RL_UPLOAD . 'dataEntriesImport/vendor/autoload.php';

        $filesystem = new Filesystem();
        $pluginsVendor = RL_PLUGINS . 'dataEntriesImport/vendor';
        $inUploadVendor = RL_UPLOAD . 'dataEntriesImport/vendor';

        $filesystem->remove($pluginsVendor);

        if (method_exists($filesystem, 'copyTo')) {
            $filesystem->copyTo($inUploadVendor, $pluginsVendor);
        } else {
            $filesystem->copy($inUploadVendor, $pluginsVendor);
        }

        $rlDb->query("
                DELETE FROM `{db_prefix}hooks`
                WHERE `Plugin` = 'dataEntriesImport' 
                AND `Name` = 'apAjaxRequest'
            ");

        if (in_array('ru', array_keys($GLOBALS['languages']))) {
            $russianTranslation = json_decode(file_get_contents(RL_PLUGINS . 'dataEntriesImport/i18n/ru.json'), true);
            foreach ($russianTranslation as $phraseKey => $phrase) {
                if ($rlDb->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $rlDb->updateOne([
                        'fields' => ['Value' => $phrase],
                        'where'  => ['Key'   => $phraseKey, 'Code' => 'ru'],
                    ], 'lang_keys');
                } else {
                    $rlDb->insertOne([
                        'Code'   => 'ru',
                        'Module' => 'common',
                        'Key'    => $phraseKey,
                        'Value'  => $russianTranslation[$phraseKey],
                        'Plugin' => 'dataEntriesImport',
                    ], 'lang_keys');
                }
            }
        }
    }

    /**
     * Update to 1.2.3 version
     */
    public function update123(): void
    {
        require RL_UPLOAD . 'dataEntriesImport/vendor/autoload.php';
        $filesystem = new Filesystem();
        $oldVendor = RL_PLUGINS . 'dataEntriesImport/vendor/';
        $filesystem->remove($oldVendor);
        $copyFunction = method_exists($filesystem, 'copyTo') ? 'copyTo' : 'copy';
        $filesystem->$copyFunction(RL_UPLOAD . 'dataEntriesImport/vendor/', $oldVendor);

    }
}
