<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLSITEMAP.CLASS.PHP
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

use Flynax\Utils\ListingMedia;
use Flynax\Utils\Valid;
use Flynax\Utils\Profile;

class rlSitemap extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * @var int
     */
    public $limitUrls = 40000;

    /**
     * @var int
     */
    public $totalPages = 0;

    /**
     * @var int
     */
    public $totalCategories = 0;

    /**
     * @var int
     */
    public $totalListings = 0;

    /**
     * Count related urls of listings with/without images in XML
     * @since 3.0.2
     * @var   int
     */
    public $totalListingsUrls = 0;

    /**
     * @var int
     */
    public $totalAccounts = 0;

    /**
     * @var int
     */
    public $totalNews = 0;

    /**
     * @var int
     */
    public $totalPluginUrls = 0;

    /**
     * @var int
     */
    public $start = 0;

    /**
     * @var array
     */
    public $languages;

    /**
     * @var int
     */
    public $languagesCount = 1;

    /**
     * @var string
     */
    public $basePath;

    /**
     * @var array
     */
    public $pages;

    /**
     * @var array
     */
    public $pluginsUrls = [];

    /**
     * @var array
     */
    protected $robotsRules = [];

    /**
     * @var string
     */
    public $xmlFilePath = RL_FILES . 'sitemaps/';

    /**
     * @var string
     */
    public $xmlFilesUrl = RL_FILES_URL . 'sitemaps/';

    /**
     * @var string
     */
    public $robotsFilePath = RL_ROOT . 'robots.txt';

    /**
     * List of limits of count of urls in file (separated by type of pages)
     * @since 3.0.2
     *
     * @var array
     */
    protected $xmlLimits = [];

    /**
     * List of urls of XML files
     * @since 3.0.2
     *
     * @var array
     */
    protected $indexXmlUrls = [];

    /**
     * Current status of plugin
     * @since 3.0.2
     *
     * @var string
     */
    protected $pluginStatus = '';

    /**
     * Allows/blocks the rebuilding of existing XML files
     * @since 3.2.1
     * @var bool
     */
    public $preventRebuild = false;

    /**
     * Path of plugin directory
     * @since 3.3.0
     * @string
     */
    public const PLUGIN_DIR = RL_PLUGINS . 'sitemap/';

    /**
     * Path of view directory for admin side
     * @since 3.3.0
     * @string
     */
    public const ADMIN_VIEW_DIR = self::PLUGIN_DIR . 'admin/view/';

    /**
     * Shows that the plugin created modified SEO_BASE constant
     * @since 3.3.1
     *
     * @var bool
     */
    public $seoBaseModified = false;

    /**
     * Shows that the plugin finished the rebuilding process
     * @since 3.3.1
     *
     * @var bool
     */
    public $buildFinish = false;

    /**
     * Get listings and build URLs
     *
     * @param  bool  $first
     * @return array
     */
    public function getListings($first = false)
    {
        global $rlDb, $reefless, $rlListings, $config, $plugins;

        $reefless->loadClass('Listings');

        if ($first) {
            $sql = 'SELECT SQL_CALC_FOUND_ROWS `T1`.`ID` ';
        } else {
            $sql = 'SELECT `T1`.`ID`, `T1`.`Main_photo`, `T3`.`Path`, `T1`.`Category_ID`, ';
            $sql .= '`T3`.`Type` AS `Listing_type` ';

            if ($plugins['ref']) {
                $refShortUrlsUsage = false;
                foreach ($GLOBALS['rlListingTypes']->types as $type) {
                    if ($type['ref_short_urls']) {
                        $refShortUrlsUsage = true;
                        break;
                    }
                }

                if ($refShortUrlsUsage) {
                    $sql .= ', `T1`.`ref_number` ';
                }
            }
        }

        if ($first && $config['sm_photos']) {
            $sql .= ", (SELECT COUNT(`ID`) FROM `{db_prefix}listing_photos` ";
            $sql .= "WHERE `Listing_ID` = `T1`.`ID` AND `Type` = 'picture') AS `Photos_count` ";
        }

        $sql .= "FROM `{db_prefix}listings` AS `T1` ";

        if (!$first) {
            $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
        }

        $sql .= "WHERE `T1`.`Status` = 'active' AND `T1`.`Account_ID` > 0 ";

        /**
         * @since 3.1.0 - Restore hook in same place, it was removed by mistake in 3.0.2 version
         * @since 3.0.0 - Added $sql parameter
         */
        $GLOBALS['rlHook']->load('sitemapGetListingsWhere', $sql);

        if ($this->languagesCount > 1) {
            $length = ceil($this->xmlLimits['listings'] / $this->languagesCount);

            if ($this->start > 0) {
                $start = ceil($this->start / $this->languagesCount);
            } else {
                $start = $this->start;
            }
        } else {
            $length = $this->xmlLimits['listings'];
            $start  = $this->start;
        }

        if (!$first) {
            $sql .= "LIMIT {$start}, {$length}";
        }

        $listings = $rlDb->getAll($sql);

        if ($first) {
            $listingsCount = (int) $rlDb->getRow("SELECT FOUND_ROWS() AS `count`", 'count');
            $listingsCount = $this->languagesCount * $listingsCount;
            $urlsCount     = $listingsCount;

            // Add count of listing photos
            if ($config['sm_photos']) {
                $urlsCount += array_sum(array_column($listings, 'Photos_count'));
            }

            $this->totalListings     = $listingsCount;
            $this->totalListingsUrls = $urlsCount;

            return [];
        } else {
            foreach ($listings as &$listing) {
                $fields = $rlListings->getFormFields(
                    $listing['Category_ID'],
                    'listing_titles',
                    $listing['Listing_type']
                );

                $fieldKeys   = !empty($fields) ? array_filter(array_keys($fields)) : '*';
                $listingData = $rlDb->fetch($fieldKeys, ['ID' => $listing['ID']], null, null, 'listings', 'row');
                $listing     = array_merge($listing, $listingData);

                if ($this->languagesCount > 1) {
                    foreach ($this->languages as $language) {
                        $listing['urls'][] = $reefless->getListingUrl($listing, $language['Code']);
                    }
                } else {
                    $listing['url'] = $reefless->getListingUrl($listing);
                }

                unset($listing['Path'], $listing['Listing_type'], $listing['Category_ID'], $listingData);

                if ($plugins['ref'] && $refShortUrlsUsage) {
                    unset($listing['ref_number']);
                }

                foreach ($fieldKeys as $fieldKey) {
                    unset($listing[$fieldKey]);
                }
            }

            return $listings;
        }
    }

    /**
     * Get pages and build URLs
     *
     * @param  bool  $first
     * @return array
     */
    public function getPages($first = false)
    {
        global $rlDb, $reefless;

        if ($first) {
            $sql = 'SELECT SQL_CALC_FOUND_ROWS `ID` ';
        } else {
            $sql = 'SELECT `ID`, `Key`, `Path`, `Get_vars`, NOW() AS `NOW`, `Modified`, `No_follow`, ';
            $sql .= '`Status`, `Controller`, `Page_type` ';
        }

        $sql .= "FROM `{db_prefix}pages` ";

        $sql .= "WHERE `Status` = 'active' AND `Sitemap` = '1' ";
        $sql .= "AND `Key` NOT IN ('" . implode("', '", $this->getExcludedPages()) . "') ";
        $pages = $rlDb->getAll($sql);

        if ($first) {
            $pagesCount       = (int) $rlDb->getRow("SELECT FOUND_ROWS() AS `count`", 'count');
            $this->totalPages = $this->languagesCount * $pagesCount;

            return [];
        } else {
            foreach ($pages as &$page) {
                if ($this->languagesCount > 1 && $GLOBALS['config']['multilingual_paths']) {
                    foreach ($this->languages as $language) {
                        $page['urls'][] = $reefless->getPageUrl($page['Key'], false, $language['Code']);
                    }
                } else {
                    $page['url'] = $reefless->getPageUrl($page['Key']);
                }
            }

            return $pages;
        }
    }

    /**
     * Get categories and build URLs
     *
     * @param  bool  $first
     * @return array
     */
    public function getCategories($first = false)
    {
        global $rlDb, $reefless, $config;

        $pageExists = $rlDb->columnExists('Page', 'listing_types');

        if ($first) {
            $sql = 'SELECT SQL_CALC_FOUND_ROWS `T1`.`ID` ';
        } else {
            $sql = 'SELECT `T1`.`ID` , `T1`.`Path`, `T1`.`Parent_ID`, `T1`.`Status`, `T1`.`Modified`, `T1`.`Type` ';
        }

        if ($pageExists) {
            $sql .= ",`T2`.`Page` ";
        }

        $sql .= 'FROM `{db_prefix}categories` AS `T1` ';
        $sql .= 'LEFT JOIN `{db_prefix}listing_types` AS `T2` ON `T1`.`Type` = `T2`.`Key` ';
        $sql .= "WHERE `T1`.`Status` = 'active' ";

        if ($pageExists) {
            $sql .= "AND `T2`.`Page` = '1' ";
        }

        // Exclude empty categories from the sitemap
        if ($config['sm_robots_tag'] && $config['sm_robots_noindex'] === 'noindex, nofollow') {
            $sql .= "AND `T1`.`Count` > 0 ";
        }

        if ($this->languagesCount > 1) {
            $length = ceil($this->limitUrls / $this->languagesCount);

            if ($this->start > 0) {
                $start = ceil($this->start / $this->languagesCount);
            } else {
                $start = $this->start;
            }
        } else {
            $length = $this->limitUrls;
            $start  = $this->start;
        }

        if (!$first) {
            $sql .= "LIMIT {$start}, {$length}";
        }

        $categories = $rlDb->getAll($sql);

        if ($first) {
            $countCategories = $rlDb->getRow('SELECT FOUND_ROWS() AS `calc`', 'calc');
            $this->totalCategories = $this->languagesCount * $countCategories;

            return [];
        } else {
            $reefless->loadClass('Categories');

            foreach ($categories as &$category) {
                if ($this->languagesCount > 1 && $GLOBALS['config']['multilingual_paths']) {
                    foreach ($this->languages as $language) {
                        $category['urls'][] = $reefless->getCategoryUrl($category, $language['Code']);
                    }
                } else {
                    $category['url'] = $reefless->getCategoryUrl($category);
                }
            }

            return $categories;
        }
    }

    /**
     * Get seller accounts and build URLs
     *
     * @param  bool  $first
     * @return array
     */
    public function getAccounts($first = false)
    {
        global $rlDb, $config;

        if ($first) {
            $sql = 'SELECT SQL_CALC_FOUND_ROWS `T1`.`ID` ';
        } else {
            $sql = 'SELECT `T1`.`ID`, `T1`.`Type`, `T1`.`Own_address`, ';
            $sql .= '`T2`.`Own_location`, `T1`.`Photo`, `T2`.`Page` ';
        }

        $sql .= 'FROM `{db_prefix}accounts` AS `T1` ';
        $sql .= 'LEFT JOIN `{db_prefix}account_types` AS `T2` ON `T1`.`Type` = `T2`.`Key` ';
        $sql .= "LEFT JOIN `{db_prefix}pages` AS `T3` ON `T3`.`Key` = CONCAT('at_', `T2`.`Key`) ";
        $sql .= "WHERE `T1`.`Status` = 'active' AND `T2`.`Page` = '1' AND `T3`.`Status` = 'active' ";
        $sql .= "AND `T1`.`Own_address` <> '' ";

        if ($this->languagesCount > 1) {
            $length = floor($this->limitUrls / $this->languagesCount);

            if ($this->start > 0) {
                $start = floor($this->start / $this->languagesCount);
            } else {
                $start = $this->start;
            }
        } else {
            $length = $this->limitUrls;
            $start  = $this->start;
        }

        if (!$first) {
            $sql .= "LIMIT {$start}, {$length}";
        }

        $accounts = $rlDb->getAll($sql);

        if ($first) {
            $calc         = $rlDb->getRow("SELECT FOUND_ROWS() AS `calc`");
            $calc['calc'] = $this->languagesCount * $calc['calc'];
            $this->totalAccounts = (int) $calc['calc'];

            return [];
        } else {
            foreach ($accounts as &$account) {
                if ($account['Own_address']) {
                    if ($this->languagesCount > 1 && $config['multilingual_paths']) {
                        foreach ($this->languages as $language) {
                            $account['urls'][] = $this->getAccountURL($account, $language['Code']);
                        }
                    } else {
                        $account['url'] = $this->getAccountURL($account, $config['lang']);
                    }
                }
            }

            return $accounts;
        }
    }

    /**
     * Get URL of account
     *
     * @since 3.2.1
     *
     * @param $account
     * @param $language
     *
     * @return string
     */
    private function getAccountURL($account, $language)
    {
        global $rlAccountTypes, $rlAccount;

        if (!$rlAccountTypes) {
            $GLOBALS['reefless']->loadClass('AccountTypes');
        }

        $accountURL = Profile::getPersonalAddress($account, $rlAccountTypes->types[$account['Type']], $language);

        /**
         * @todo - Remove when Multifield plugin will erase "locfix" from URLs by yourself
         */
        $accountURL = str_replace('locfix', '', $accountURL);

        if (!$GLOBALS['config']['multilingual_paths']) {
            $lang = $this->languagesCount > 1 ? '[lang]' . '/' : '';

            if ($GLOBALS['config']['account_wildcard']) {
                $accountURL .= $lang;
            } else {
                $accountURL = str_replace(RL_URL_HOME, RL_URL_HOME . $lang, $accountURL);
            }
        }

        return $accountURL;
    }

    /**
     * Get all news
     *
     * @param  bool  $first
     * @return array
     */
    public function getNews($first = false)
    {
        global $rlDb;

        if ($first) {
            $sql = 'SELECT SQL_CALC_FOUND_ROWS `T1`.`ID` ';
        } else {
            $sql = 'SELECT SQL_CALC_FOUND_ROWS `T1`.`ID`, `T1`.`ID` AS `Key`, `T1`.`Date`, `T1`.`Path` ';
        }

        $sql .= "FROM `{db_prefix}news` AS `T1` ";
        $sql .= "WHERE `Status` = 'active'";

        if ($this->languagesCount > 1) {
            $length = ceil($this->limitUrls / $this->languagesCount);

            if ($this->start > 0) {
                $start = ceil($this->start / $this->languagesCount);
            } else {
                $start = $this->start;
            }
        } else {
            $length = $this->limitUrls;
            $start  = $this->start;
        }

        if (!$first) {
            $sql .= "LIMIT {$start}, {$length}";
        }

        $news = $rlDb->getAll($sql);

        if ($first) {
            $newsCount       = (int) $rlDb->getRow("SELECT FOUND_ROWS() AS `count`", 'count');
            $this->totalNews = $this->languagesCount * $newsCount;

            return [];
        } else {
            $this->languagesCount > 1 ? $lang = '[lang]' . '/' : $lang = '';

            foreach ($news as &$item) {
                $item['url'] = $this->basePath . $lang .
                    ($GLOBALS['config']['mod_rewrite']
                    ? $this->pages['news'] . '/' . $item['Path'] . '.html'
                    : '?page=' . $this->pages['news'] . '&id=' . $item['ID']
                );
            }

            return $news;
        }
    }

    /**
     * Get additional URLs from plugins (plugins must use this method to add new URLs to sitemap)
     *
     * @since 3.1.0 - Added ability to use multilingual urls from plugins
     * @since 3.0.0
     *
     * @param  bool       $first
     * @return array|bool
     */
    public function getPluginsUrls($first = false)
    {
        if ($first) {
            /**
             * Use $urls as simple array with urls, you need use the array_merge method to prevent lose previous urls
             *
             * Use this example if you want add non multilingual urls
             * return array_merge($param1, [
             *     'http://domain.com/plugin_name/url1.html',
             *     'http://domain.com/plugin_name/url2.html',
             *     ...
             * ]);
             *
             * -------------------------------
             *
             * Use this example if you use multilingual urls
             * $param1['your_plugin_name'] = [
             *     'http://domain.com/path/url1.html',
             *     'http://domain.com/ru/ru-path/url1.html',
             *     'http://domain.com/path/url2.html',
             *     'http://domain.com/ru/ru-path/url2.html',
             *     ...
             * ];
             */
            $pluginsUrls = [];
            $GLOBALS['rlHook']->load('sitemapAddPluginUrls', $pluginsUrls);

            $nonMultilingualUrls = [];
            foreach ($pluginsUrls as $urlIndex => $urlData) {
                if (is_string($urlData)) {
                    $lang      = $this->languagesCount > 1 ? '[lang]' . '/' : '';
                    $pluginUrl = htmlspecialchars_decode($urlData);

                    if ($GLOBALS['config']['mod_rewrite'] == '0'
                        && !strpos($pluginUrl, 'index.php')
                        && $this->languagesCount > 1
                    ) {
                        $pluginUrl = str_replace(RL_URL_HOME, $this->basePath . '[lang]/', $pluginUrl);
                    }

                    $this->addLangTemplate($pluginUrl, $lang);

                    $nonMultilingualUrls[] = $pluginUrl;
                    unset($pluginsUrls[$urlIndex]);
                } elseif (is_array($urlData)) {
                    foreach ($urlData as $url) {
                        $this->pluginsUrls[] = $url;
                    }
                    unset($pluginsUrls[$urlIndex]);
                }
            }

            if ($nonMultilingualUrls) {
                foreach ($nonMultilingualUrls as $urlIndex => $pluginUrl) {
                    foreach ($this->languages as $lang) {
                        $url = $pluginUrl;
                        $this->addLangCode($url, $lang['Code']);
                        $this->pluginsUrls[] = $url;
                    }

                    unset($nonMultilingualUrls[$urlIndex]);
                }
            }

            $this->totalPluginUrls = count($this->pluginsUrls);

            return true;
        } else {
            if ($this->pluginsUrls) {
                return array_slice($this->pluginsUrls, $this->start, $this->limitUrls);
            } else {
                return false;
            }
        }
    }

    /**
     * Get keys of pages which must be excluded from sitemap
     *
     * @since 3.0.0
     *
     * @return array
     */
    public function getExcludedPages()
    {
        $excludedPages       = ['confirm', 'view_details', 'print', '404', 'listing_remove', 'account_remove'];
        $pluginExcludedPages = [];

        /**
         * Use this hook in plugins when some of pages must be excluded from sitemap, plugin must return array with keys
         * Example: return ('Key1', 'Key2', 'Key3' and etc.)
         */
        $GLOBALS['rlHook']->load('sitemapExcludedPages', $pluginExcludedPages);

        return array_unique(array_merge($excludedPages, (array) $pluginExcludedPages));
    }

    /**
     * Build sitemap
     *
     * @param  bool   $force - Enable when files must be rebuild now
     * @return string
     */
    public function build($force = false)
    {
        $this->init();

        // Force update of files
        if ($force && $this->pluginStatus == 'active') {
            foreach (glob($this->xmlFilePath . '*', GLOB_MARK) as $file) {
                unlink($file);
            }
        }

        $xml = $this->getBasicXml();

        $this->updateRulesInRobotsFile();

        $this->buildFinish = true;

        return $xml;
    }

    /**
     * Initialize basic data
     *
     * @since 3.0.0
     *
     * @return void
     */
    public function init()
    {
        global $rlDb, $reefless, $plugins, $rlListingTypes, $lang, $config, $domain_info;

        if ($config['lang'] && !defined('RL_LANG_CODE')) {
            define('RL_LANG_CODE', $config['lang']);
        }

        if (!defined('RL_DATE_FORMAT')) {
            define('RL_DATE_FORMAT', $rlDb->getOne('Date_format', "`Code` = '{$config['lang']}'", 'languages'));
        }

        $reefless->loadClass('Account');

        $this->languages      = $this->getActiveLanguages();
        $this->languagesCount = count($this->languages);
        $this->basePath       = RL_URL_HOME;

        if (!$config['mod_rewrite']) {
            $this->basePath .= 'index.php';
        }

        if ($config['lang'] && !$lang) {
            $lang = $GLOBALS['rlLang']->getLangBySide('frontEnd', $config['lang']);
        }

        if (!defined('SEO_BASE')) {
            define('SEO_BASE', RL_URL_HOME . ($this->languagesCount > 1 ? '[lang]' . '/' : ''));

            if ($this->languagesCount > 1) {
                $this->seoBaseModified = true;
            }
        }

        if (!$rlListingTypes->types) {
            $reefless->loadClass('ListingTypes', null, false, true);
        }

        if ($GLOBALS['pages']) {
            $this->pages = $GLOBALS['pages'];
        } else {
            $rlDb->setTable('pages');
            $rlDb->outputRowsMap = $columns = ['Key', 'Path'];
            $this->pages = $rlDb->fetch($columns, ['Status' => 'active']);
            $rlDb->resetTable();

            /**
             * @todo - Remove this when compatible will be >= 4.9.0 (problem must be fixed in core)
             */
            if (!$config['multilingual_paths']) {
                $GLOBALS['pages'] = $this->pages;
            }
        }

        if ($plugins && !$plugins['sitemap']) {
            $this->pluginStatus = $rlDb->getOne('Status', "`Key` = 'sitemap'", 'plugins');

            if (!$this->pluginStatus) {
                $reefless->redirect(null, $reefless->getPageUrl('404'));
            }
        } else {
            $this->pluginStatus = 'active';
        }

        if (!is_dir($this->xmlFilePath)) {
            $reefless->rlMkdir($this->xmlFilePath);
        }

        if (!$domain_info) {
            $domain_info = parse_url(RL_URL_HOME);
        }
    }

    /**
     * Build XML of basic sitemap.xml file
     *
     * @since 3.0.0
     *
     * @return string
     */
    public function getBasicXml()
    {
        // Get XML content from saved file if it exists
        $xml = is_file($this->xmlFilePath . 'sitemap.xml') ? file_get_contents($this->xmlFilePath . 'sitemap.xml') : '';

        if ((!$this->isFileRelevance('sitemap.xml') || !$xml) && $this->pluginStatus == 'active') {
            $this->getPages(true);
            $this->getCategories(true);
            $this->getListings(true);
            $this->getAccounts(true);
            $this->getNews(true);
            $this->getPluginsUrls(true);

            $xml = "<?xml version='1.0' encoding='UTF-8'?>" . PHP_EOL;
            $xml .= "<sitemapindex xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>" . PHP_EOL;
            $xml .= $this->buildSitemapFiles($this->totalPages, 'pages');
            $xml .= $this->buildSitemapFiles($this->totalCategories, 'categories');
            $xml .= $this->buildSitemapFiles($this->totalAccounts, 'accounts');
            $xml .= $this->buildSitemapFiles($this->totalNews, 'news');
            $xml .= $this->buildSitemapFiles($this->totalListingsUrls, 'listings');
            $xml .= $this->buildSitemapFiles($this->totalPluginUrls, 'plugins');
            $xml .= "</sitemapindex>";

            $this->updateXmlFile('sitemap.xml', $xml);
        }

        $this->buildInternalXml();

        return $xml;
    }

    /**
     * Build XML of internal XML in sitemap_[postfix][0-9].xml files
     *
     * @since 3.0.0
     *
     * @return bool
     */
    public function buildInternalXml()
    {
        if (!$this->indexXmlUrls) {
            return false;
        }

        foreach ($this->indexXmlUrls as $xmlUrl) {
            preg_match('/sitemap\_([a-z]+)([0-9]+)\.xml/', $xmlUrl, $matches);
            $fileName = $matches[0];
            $type     = $matches[1];
            $number   = (int) $matches[2];

            if (!$fileName || !$type || !$number) {
                continue;
            }

            $limit = $type == 'listings' ? $this->xmlLimits[$type] : $this->limitUrls;
            $this->start = ($number - 1) * $limit;

            // Return counter to back to prevent lose items in files 2,3,4...
            if ($this->start > 0 && $number > 1) {
                $this->start -= $number;
            }

            if (!$this->isFileRelevance($fileName)) {
                $xml = '<?xml version="1.0" encoding="UTF-8"?>';
                $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
                $xml .= 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" ';
                $xml .= 'xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . PHP_EOL;

                switch ($type) {
                    case 'pages':
                        $xml .= $this->buildSitemapItems($this->getPages());
                        break;

                    case 'categories':
                        $xml .= $this->buildSitemapItems($this->getCategories());
                        break;

                    case 'accounts':
                        $xml .= $this->buildSitemapItems($this->getAccounts());
                        break;

                    case 'news':
                        $xml .= $this->buildSitemapItems($this->getNews());
                        break;

                    case 'listings':
                        $xml .= $this->buildSitemapItems($this->getListings());
                        break;

                    case 'plugins':
                        $xml .= $this->buildSitemapItems($this->getPluginsUrls(), false);
                        break;
                }

                $xml .= '</urlset>';

                $this->updateXmlFile($fileName, $xml);
                unset($xml);
            }
        }

        return true;
    }

    /**
     * Build sitemap files
     *
     * @since 3.0.0
     *
     * @param  int    $totalItems
     * @param  string $postfix
     * @return string
     */
    public function buildSitemapFiles($totalItems = 0, $postfix = '')
    {
        if (!$totalItems || !$postfix) {
            return '';
        }

        $xml         = '';
        $countFiles = (int) ceil($totalItems / $this->limitUrls);
        $countFiles = $countFiles > 0 ? $countFiles : 1;
        $url         = $this->xmlFilesUrl;
        $index       = 'total' . ucfirst($postfix);
        $postfix     = '_' . $postfix;

        $this->xmlLimits[str_replace('_', '', $postfix)] = (int) floor($this->$index / $countFiles);

        for ($i = 1; $i <= $countFiles; $i++) {
            $xmlUrl               = $url . 'sitemap' . $postfix . $i . '.xml';
            $this->indexXmlUrls[] = $xmlUrl;

            $xml .= "<sitemap>" . PHP_EOL;
            $xml .= "\t<loc>" . $xmlUrl . "</loc>" . PHP_EOL;
            $xml .= "\t<lastmod>" . str_replace(" ", "T", date("Y-m-d H:i:s")) . "+00:00</lastmod>" . PHP_EOL;
            $xml .= "</sitemap>" . PHP_EOL;
        }

        return $xml;
    }

    /**
     * Build items in file
     *
     * @since 3.1.0 - Added $addLangCodes parameter
     * @since 3.0.0
     *
     * @param  array  $data
     * @param  bool   $addLangCodes - Replace template [lang] to code of lang in urls
     * @return string
     */
    public function buildSitemapItems($data = [], $addLangCodes = true)
    {
        $xml = '';

        if (!$data) {
            return $xml;
        }

        if (isset($data[0]['urls'])) {
            foreach ($data as $listing) {
                foreach ($listing['urls'] as $url) {
                    $listing['url'] = $url;
                    $xml .= $this->buildItem($listing);
                }
            }
        } else {
            if ($addLangCodes) {
                foreach ($this->languages as $lang) {
                    foreach ($data as $item) {
                        $this->addLangCode($item['url'], $lang['Code']);
                        $xml .= $this->buildItem($item);
                    }
                }
            } else {
                foreach ($data as $item) {
                    $xml .= $this->buildItem(['url' => $item]);
                }
            }
        }

        return $xml;
    }

    /**
     * Build item
     *
     * @since 3.0.0
     *
     * @param array $row
     *
     * @return string
     */
    public function buildItem(array $row = [])
    {
        if (!$row['url']) {
            return false;
        }

        if (!function_exists('utf8_is_ascii')) {
            loadUTF8functions('ascii');
        }

        $url = utf8_is_ascii($row['url'])
            ? $row['url']
            : preg_replace_callback('#://([^/]+)/([^?]+)#', static function ($match) {
                return '://' . $match[1] . '/' . implode('/', array_map('rawurlencode', explode('/', $match[2])));
            }, $row['url']);

        $xml = "\t<url>" . PHP_EOL;
        $xml .= "\t\t<loc>{$url}</loc>" . PHP_EOL;

        if ($GLOBALS['config']['sm_photos']) {
            if ($row['Photo'] || $row['Photo_x2']) {
                Profile::prepareURL($row);
                $accountPhoto = $row['Photo_x2'] ?: $row['Photo'];

                $xml .= "\t\t<image:image>" . PHP_EOL;
                $xml .= "\t\t\t<image:loc>{$accountPhoto}</image:loc>" . PHP_EOL;
                $xml .= "\t\t</image:image>" . PHP_EOL;
            }

            if ($row['Main_photo']) {
                /**
                 * Prevent showing error in the ListingMedia::get() method
                 * @todo - Remove it when compatibility will be >= 4.9.2
                 */
                if (version_compare($GLOBALS['config']['rl_version'], '4.9.2') < 0) {
                    $GLOBALS['l_youtube_thumbnail'] = true;
                }

                foreach ((array) ListingMedia::get($row['ID']) as $media) {
                    if ($media['Type'] === 'picture' && $media['Photo']) {
                        $xml .= "\t\t<image:image>" . PHP_EOL;
                        $xml .= "\t\t\t<image:loc>{$media['Photo']}</image:loc>" . PHP_EOL;
                        $xml .= "\t\t</image:image>" . PHP_EOL;
                    }
                }
            }
        }

        if (isset($row['Modified']) && isset($row['NOW'])) {
            $modified = $row['Modified'] && $row['Modified'] !== '0000-00-00 00:00:00' ? $row['Modified'] : $row['NOW'];
            $xml .= "\t\t<lastmod>" . str_replace(' ', 'T', $modified) . "+00:00</lastmod>" . PHP_EOL;
        }

        $xml .= "\t</url>" . PHP_EOL;

        return $xml;
    }

    /**
     * Add language code to URL
     *
     * @since 3.0.0
     *
     * @param  string $url
     * @param  string $lang
     * @return void
     */
    public function addLangCode(&$url, $lang)
    {
        global $config;

        if (!$url || !$lang) {
            return;
        }

        if ($config['mod_rewrite']) {
            $url = $config['lang'] != $lang ? str_replace('[lang]', $lang, $url) : str_replace('[lang]/', '', $url);
        } else {
            $url = str_replace('[lang]/', 'index.php', str_replace('index.php', '', $url));
            $url .= (substr_count($url, '?') > 0 ? '&' : '?') . 'language=' . $lang;
        }
    }

    /**
     * Add language code template to URL
     *
     * @param  string $url
     * @param  string $template
     */
    public function addLangTemplate(&$url, $template = '')
    {
        if ($GLOBALS['config']['mod_rewrite'] && !empty($template) && substr_count($url, $template) <= 0) {
            $url = str_replace($this->basePath, $this->basePath . $template, $url);
        }
    }

    /**
     * Update/create content of XML file in webserver
     *
     * @since 3.0.0
     *
     * @param  string $fileName - Name of XML file
     * @param  string $xml
     * @return void
     */
    public function updateXmlFile($fileName = 'sitemap.xml', $xml = '')
    {
        file_put_contents($this->xmlFilePath . $fileName, $xml);
    }

    /**
     * Check relevance of XML data in saved file
     *
     * @since 3.0.0
     *
     * @param  string $fileName - Name of XML file
     * @return bool
     */
    public function isFileRelevance($fileName = 'sitemap.xml')
    {
        $fileName = $this->xmlFilePath . $fileName;

        if (!file_exists($fileName)) {
            return false;
        } else if (file_exists($fileName) && $this->preventRebuild) {
            return true;
        }

        return filemtime($fileName) && time() - filemtime($fileName) < 86400;
    }

    /**
     * Update content of robots.txt file in webserver
     * Adding rules to prevent search indexing of excluded pages
     *
     * @since 3.0.0
     *
     * @return void
     */
    public function updateRulesInRobotsFile()
    {
        if ($this->pluginStatus != 'active') {
            return;
        }

        if (!$this->pages) {
            $this->init();
        }

        $this->removeRulesInRobotsFile();

        // Add template for plugin rules in robots.txt file
        file_put_contents(
            $this->robotsFilePath,
            "\n### Rules generated by the Sitemap plugin ###\nSitemap: " . RL_URL_HOME . "sitemap.xml",
            FILE_APPEND
        );

        if ($excludedPages = $this->getExcludedPages()) {
            file_put_contents($this->robotsFilePath, "\n\n# Excluded pages:", FILE_APPEND);

            foreach ($excludedPages as $pageKey) {
                if (!$path = $this->pages[$pageKey]) {
                    continue;
                }

                $path = $GLOBALS['config']['mod_rewrite'] ? $path . '.html' : 'index.php?page=' . $path;

                file_put_contents(
                    $this->robotsFilePath,
                    "\nDisallow: /" . ($path == '404.html' ? '' : '*') . "{$path}*",
                    FILE_APPEND
                );
            }

            file_put_contents($this->robotsFilePath, "\n", FILE_APPEND);
        }

        // Add custom rules from plugins
        foreach ($this->getRobotsRules() as $rule) {
            file_put_contents($this->robotsFilePath, $rule . "\n", FILE_APPEND);
        }

        if ($additionalRules = $GLOBALS['config']['sm_robots_rules']) {
            file_put_contents(
                $this->robotsFilePath,
                "\n# Additional rules:\n" . $additionalRules . "\n", FILE_APPEND
            );
        }
    }

    /**
     * Add new custom rule in robots.txt file
     *
     * @since 3.0.0
     *
     * @param  string $rule - Rule for excluding url from crawling, for example: Disallow: /*confirm.html*
     * @return bool
     */
    public function addRuleInRobots($rule = '')
    {
        if (!$rule) {
            return false;
        }

        $this->robotsRules[] = $rule;

        return true;
    }

    /**
     * Get all custom rules from plugins
     *
     * @since 3.0.0
     *
     * @return array
     */
    protected function getRobotsRules()
    {
        $this->robotsRules = $this->robotsRules ? array_unique($this->robotsRules) : [];

        return $this->robotsRules;
    }

    /**
     * Remove plugin content from robots.txt file
     *
     * @since 3.0.0
     *
     * @return void
     */
    public function removeRulesInRobotsFile()
    {
        // Remove plugin rules from robots.txt file
        file_put_contents(
            $this->robotsFilePath,
            preg_replace(
                "/(\n### Rules generated by the Sitemap plugin ###.*)$/smi",
                '',
                file_get_contents($this->robotsFilePath)
            )
        );
    }

    /**
     * Get system active languages
     *
     * @since 3.0.0
     *
     * @return array
     */
    private function getActiveLanguages()
    {
        global $rlDb;

        $rlDb->setTable('languages');
        $rlDb->outputRowsMap = 'Code';

        $languages = $rlDb->fetch(
            ['Code', 'Key', 'Direction', 'Locale', 'Date_format', 'Status'],
            ['Status' => 'active']
        );

        foreach ($languages as &$language) {
            $language['name'] = $GLOBALS['rlLang']->getPhrase([
                'key'  => 'languages+name+' . $language['Key'],
                'lang' => $language['Code'],
            ]);
        }

        return $languages;
    }

    /**
     * Install process
     *
     * @since 3.0.0
     */
    public function install()
    {
        global $rlDb;

        $rlDb->addColumnToTable('Sitemap', "ENUM('0','1') NOT NULL DEFAULT '1'", 'pages');

        $rlDb->query("
            UPDATE `{db_prefix}pages` SET `Sitemap` = '0'
            WHERE  `No_follow` = '1'
                OR `Page_type` = 'external'
                OR FIND_IN_SET('2', `Menus`)
                OR `Login` = '1'
                OR `Key` IN ('" . implode("', '", $this->getExcludedPages()) . "')
        ");

        $GLOBALS['reefless']->rlMkdir($this->xmlFilePath);
    }

    /**
     * Uninstall plugin
     *
     * @since 3.0.0
     */
    public function uninstall()
    {
        $GLOBALS['rlDb']->dropColumnFromTable('Sitemap', 'pages');

        $GLOBALS['reefless']->deleteDirectory($this->xmlFilePath);

        $this->removeRulesInRobotsFile();
    }

    /**
     * Update to 3.0.0 version
     */
    public function update300()
    {
        global $rlDb;

        $rlDb->addColumnToTable('Sitemap', "ENUM('0','1') NOT NULL DEFAULT '1'", 'pages');

        $rlDb->query("
            UPDATE `{db_prefix}pages` SET `Sitemap` = '0'
            WHERE  `No_follow` = '1'
                OR `Page_type` = 'external'
                OR FIND_IN_SET('2', `Menus`)
                OR `Login` = '1'
                OR `Key` IN ('" . implode("', '", $this->getExcludedPages()) . "')
        ");

        $GLOBALS['reefless']->rlMkdir($this->xmlFilePath);

        foreach (['config', 'config_groups'] as $table) {
            $rlDb->query("
                DELETE FROM `{db_prefix}{$table}` WHERE `Key` LIKE 'sitemap%' AND `Plugin` = 'sitemap'
            ");
        }

        $rlDb->query("
            DELETE FROM `{db_prefix}lang_keys` WHERE `Key` LIKE '%sitemap_limit_urls%' AND `Plugin` = 'sitemap'
        ");
    }

    /**
     * Update to 3.2.0 version
     */
    public function update320()
    {
        $GLOBALS['rlDb']->query(
            "DELETE FROM `{db_prefix}lang_keys`
             WHERE `Key` = 'sm_deactivate_language_notce' AND `Plugin` = 'sitemap'"
        );

        if (in_array('ru', array_keys($GLOBALS['languages']))) {
            $russianTranslation = json_decode(file_get_contents(RL_UPLOAD . 'sitemap/i18n/ru.json'), true);
            foreach ($russianTranslation as $phraseKey => $phrase) {
                $GLOBALS['rlDb']->updateOne([
                    'fields' => ['Value' => $phrase],
                    'where'  => ['Key'   => $phraseKey, 'Code' => 'ru'],
                ], 'lang_keys');
            }
        }
    }

    /**
     * @hook  cronAdditional
     * @since 3.0.0
     */
    public function hookCronAdditional()
    {
        $this->build();
    }

    /**
     * @hook  apTplControlsForm
     * @since 3.0.0
     */
    public function hookApTplControlsForm()
    {
        global $lang;

        $html = '<tr class="body"><td class="list_td">' . $lang['sm_rebuild_title'] . '</td>';
        $html .= '<td class="list_td" align="center"><input id="sitemap_rebuild" type="button" ';
        $html .= 'value="' . $lang['rebuild'] . '" style="margin: 0; width: 100px;" /></td></tr>';
        $html .= '<td style="height: 5px;" colspan="3"></td></tr>';
        echo $html;

        echo <<<HTML
            <script>
            var \$smButton = $('#sitemap_rebuild');
            sitemapInProgress = false;

            \$smButton.click(function(){
                \$smButton.val('{$lang['loading']}').addClass('disabled').attr('disabled', 'disabled');
                sitemapInProgress = true;

                $.post(
                    rlConfig['ajax_url'],
                    {item: 'smRebuildFiles'},
                    function(response){
                        if (response && response.status) {
                            sitemapInProgress = false;

                            if (response.status === 'OK') {
                                printMessage('notice', '{$lang['sm_xml_rebuilt']}');
                            } else {
                                printMessage('error', '{$lang['sm_rebuild_notify_fail']}');
                            }

                            \$smButton.val('{$lang['rebuild']}').removeClass('disabled').removeAttr('disabled');
                        }
                    },
                    'json'
                ).fail(function() {
                    sitemapInProgress = false;
                    printMessage('error', '{$lang['sm_dryrun_rebuild_fail']}');
                    $.post(rlConfig['ajax_url'], {item: 'smRestoreXmlFromBackup'}, function(){}, 'json');
                    \$smButton.val('{$lang['rebuild']}').removeClass('disabled').removeAttr('disabled');
                });
            });

            $(window).bind('beforeunload', function(){
                if (sitemapInProgress) {
                    return '{$lang['sm_dryrun_rebuild_in_process']}';
                }
            });
            </script>
HTML;
    }

    /**
     * @hook  apAjaxRequest
     * @since 3.0.0
     */
    public function hookApAjaxRequest(&$out, $item)
    {
        if (!$item || !$this->isValidAjaxRequest($item)) {
            return false;
        }

        global $config, $rlConfig, $reefless, $rlDebug;

        $sitemapFolder       = RL_FILES . 'sitemaps';
        $sitemapBackupFolder = RL_FILES . 'sitemap_backups';

        switch ($item) {
            case 'smRebuildFiles':
                /**
                 * Dry-run with urls from Multifield/Location plugin
                 * @since 3.1.0
                 */
                $mfUrlsInSitemap    = Valid::escape($_REQUEST['mf_urls_in_sitemap']);
                $mfAddHomeToSitemap = Valid::escape($_REQUEST['mf_home_in_sitemap']);
                $mfLocationUrlPages = Valid::escape($_REQUEST['mf_location_url_pages']);
                $mfListingGeoUrls   = Valid::escape($_REQUEST['mf_listing_geo_urls']);

                if (!empty($mfUrlsInSitemap)) {
                    $config['mf_urls_in_sitemap'] = $mfUrlsInSitemap;
                }

                if (!empty($mfAddHomeToSitemap)) {
                    $config['mf_home_in_sitemap'] = $mfAddHomeToSitemap;
                }

                if (!empty($mfLocationUrlPages)) {
                    $config['mf_location_url_pages'] = $mfLocationUrlPages;
                }

                if (!empty($mfListingGeoUrls)) {
                    $config['mf_listing_geo_urls'] = $mfListingGeoUrls;
                }

                if (is_dir($sitemapBackupFolder)) {
                    $reefless->rlMkdir($sitemapFolder);
                } else {
                    rename($sitemapFolder, $sitemapBackupFolder);
                    $reefless->rlMkdir($sitemapFolder);
                }

                if ($this->build(true)) {
                    $reefless->deleteDirectory($sitemapBackupFolder);

                    if (!empty($mfUrlsInSitemap)) {
                        $rlConfig->setConfig('mf_urls_in_sitemap', $mfUrlsInSitemap);
                    }

                    if (!empty($mfAddHomeToSitemap)) {
                        $rlConfig->setConfig('mf_home_in_sitemap', $mfAddHomeToSitemap);
                    }

                    if (!empty($mfLocationUrlPages)) {
                        $rlConfig->setConfig('mf_location_url_pages', $mfLocationUrlPages);
                    }

                    if (!empty($mfListingGeoUrls)) {
                        $rlConfig->setConfig('mf_listing_geo_urls', $mfListingGeoUrls);
                    }

                    $out['status'] = 'OK';
                } else {
                    $rlDebug->logger('Rebuilding of sitemap failed');
                    $out['status'] = 'ERROR';
                }
                break;
            case 'smRestoreXmlFromBackup':
                if (is_dir($sitemapBackupFolder)) {
                    $reefless->deleteDirectory($sitemapFolder);
                    rename($sitemapBackupFolder, $sitemapFolder);
                    $out['status'] = 'OK';
                } else {
                    $out['status'] = 'ERROR';
                }
                break;
        }
    }

    /**
     * Check correct key of ajax requests
     *
     * @since 3.1.0
     *
     * @param  string $mode
     * @return bool
     */
    public function isValidAjaxRequest($mode = '')
    {
        $validRequests = [
            'smRebuildFiles',
            'smRestoreXmlFromBackup',
        ];

        return ($mode && in_array($mode, $validRequests));
    }

    /**
     * @hook  apTplPagesForm
     * @since 3.0.0
     */
    public function hookApTplPagesForm()
    {
        global $lang, $info;

        $action  = $_GET['action'];
        $yes     = $_POST['Sitemap'] == '1' || (!$_POST['Sitemap'] && $action == 'add') ? 'checked="checked"' : '';
        $no      = isset($_POST['Sitemap']) && $_POST['Sitemap'] == '0' ? 'checked="checked"' : '';
        $blocked = $action == 'edit' && in_array($info['Key'], $this->getExcludedPages()) ? true : false;

        // Disabled blocked pages from plugins
        if ($yes && $blocked) {
            $yes = '';
            $no  = 'checked="checked"';
        }

        $lang['sm_page_notice'] = str_replace('{rebuild_title}', $lang['sm_rebuild_title'], $lang['sm_page_notice']);

        if ($blocked) {
            echo '<input type="hidden" name="Sitemap" value="0" />';
            $blocked = 'disabled="disabled"';
            $notice  = "<span class=\"field_description\">{$lang['sm_blocked_page']}</span>";
        } else {
            $notice  = "<span class=\"field_description\">{$lang['sm_page_notice']}</span>";
        }

        echo <<<HTML
        <tr>
            <td class="name">{$lang['sm_page']}</td>
            <td class="field">
                <label>
                    <input {$yes} {$blocked} class="lang_add" type="radio" name="Sitemap" value="1" />
                    &nbsp;{$lang['enabled']}
                </label>
                <label>
                    <input {$no} {$blocked} class="lang_add" type="radio" name="Sitemap" value="0" />
                    &nbsp;{$lang['disabled']}
                </label>

                {$notice}
            </td>
        </tr>
HTML;
    }

    /**
     * @hook  apPhpPagesBeforeAdd
     * @since 3.0.0
     */
    public function hookApPhpPagesBeforeAdd()
    {
        $GLOBALS['data']['Sitemap'] = $_POST['Sitemap'];
    }

    /**
     * @hook  apPhpPagesBeforeEdit
     * @since 3.0.0
     */
    public function hookApPhpPagesBeforeEdit()
    {
        $GLOBALS['update_data']['fields']['Sitemap'] = $_POST['Sitemap'];
    }

    /**
     * @hook  apPhpPagesPost
     * @since 3.0.0
     */
    public function hookApPhpPagesPost()
    {
        $_POST['Sitemap'] = $GLOBALS['info']['Sitemap'];
    }

    /**
     * @hook  apTplPagesGrid
     * @since 3.0.0
     **/
    public function hookApTplPagesGrid()
    {
        global $lang;

        // Add some necessary phrases with EXT prefix
        foreach (['ext_yes', 'ext_no', 'ext_not_available', 'ext_click_to_edit', 'alert'] as $phraseKey) {
            $lang[$phraseKey] = $GLOBALS['rlLang']->getPhrase(['key' => $phraseKey, 'db_check' => true]);
        }

        $excludedPages = implode("', '", $this->getExcludedPages());

        $lang['sm_page_notice'] = str_replace('{rebuild_title}', $lang['sm_rebuild_title'], $lang['sm_page_notice']);

        echo <<<JS
        var smExcludedPages = ['{$excludedPages}'];

        pagesGrid.getInstance().columns.splice(2, 0, {
            header   : '{$lang['sm_page_in_grid']}',
            dataIndex: 'Sitemap',
            width    : 10,
            editor   : new Ext.form.ComboBox({
                store: [
                    ['1', '{$lang['ext_yes']}'],
                    ['0', '{$lang['ext_no']}']
                ],
                displayField : 'value',
                valueField   : 'key',
                emptyText    : '{$lang['ext_not_available']}',
                typeAhead    : true,
                mode         : 'local',
                triggerAction: 'all',
                selectOnFocus: true,
                listeners    : {
                    beforeselect: function(combo, record){
                        var index  = combo.gridEditor.row;
                        var row    = pagesGrid.grid.store.data.items && pagesGrid.grid.store.data.items[index]
                        ? pagesGrid.grid.store.data.items[index]
                        : null;

                        if (row && row.data.Key && smExcludedPages.indexOf(row.data.Key) >= 0) {
                            Ext.MessageBox.alert('{$lang['alert']}', '{$lang['sm_blocked_page']}');
                            pagesGrid.reload();

                            return false;
                        }
                    },
                    change: function(){
                        if (!readCookie('sm_rebuild_notice')) {
                            Ext.MessageBox.alert(lang['alert'], '{$lang['sm_page_notice']}', function(button){
                                if (button == 'ok') {
                                    createCookie('sm_rebuild_notice', true);
                                }
                            });
                        }
                    }
                }
            }),
            renderer: function(val, grid, row){
                var out = '<span ext:qtip="{$lang['ext_click_to_edit']}">';
                out += smExcludedPages.includes(row.data.Key)
                ? '{$lang['ext_not_available']}'
                : (val == 1 || val == '{$lang['ext_yes']}' ? '{$lang['ext_yes']}' : '{$lang['ext_no']}')
                out += '</span>';

                return out;
            }
        });

        pagesGrid.getInstance().fields.push({name: 'Sitemap', mapping: 'Sitemap'});
JS;
    }

    /**
     * @hook  apPhpConfigBottom
     * @since 3.0.0
     */
    public function hookApPhpConfigBottom()
    {
        // Add phrase as description of config which have HTML tags
        foreach ($GLOBALS['configs'] as &$configItem) {
            foreach ($configItem as &$config) {
                switch ($config['Key']) {
                    case 'sm_robots_rules':
                        $config['des'] = $GLOBALS['lang']['sm_robots_rules_desc'];
                        break;
                    case 'sm_robots_tag':
                        Valid::revertQuotes($GLOBALS['lang']['sm_robots_tag_description']);
                        $config['des'] = htmlentities($GLOBALS['lang']['sm_robots_tag_description']);
                        break;
                    case 'sm_robots_noindex':
                        $config['Values'] = ['index, follow', 'index, nofollow', 'noindex, nofollow'];
                        break;
                }
            }
        }
    }

    /**
     * @hook  apPhpConfigBeforeUpdate
     * @since 3.0.0
     */
    public function hookApPhpConfigBeforeUpdate()
    {
        global $config;

        foreach ($GLOBALS['update'] as &$config_item) {
            if ($config_item['where']['Key'] == 'sm_robots_rules') {
                $newRules = &$config_item['fields']['Default'];
                $newRules = trim($newRules);

                if ($newRules != $config['sm_robots_rules']) {
                    $config['sm_robots_rules'] = $newRules;
                    $this->updateRulesInRobotsFile();
                }
            }
        }
    }

    /**
     * @hook  apTplLanguagesGrid
     * @since 3.0.0
     */
    public function hookApTplLanguagesGrid()
    {
        global $lang;

        echo <<<JS
        $(function () {
            // Find column with Status data
            var \$statusColumn = {};
            languagesGrid.getInstance().columns.map(function (column) {
                if (column.dataIndex === 'Status') {
                    \$statusColumn = column;
                }
            });

            if (\$statusColumn === {}) {
                return;
            }

            // Inform to admin that related URL's of inactive language will be removed from sitemap
            \$statusColumn.editor.addListener('beforeselect', function(event, value){
                if (event.value == lang['ext_active'] && value.data.field1 == 'approval') {
                    Ext.MessageBox.confirm('{$lang['warning']}', '{$lang['sm_deactivate_language_notice']}', function(btn) {
                        if (btn == 'yes') {
                            // disable language
                            Ext.Ajax.request({
                                url   : languagesGrid.ajaxUrl,
                                method: languagesGrid.ajaxMethod,
                                params: {
                                    'action': 'update',
                                    'id'    : event.gridEditor.record.id,
                                    'field' : 'Status',
                                    'value' : 'approval'
                                }
                            });

                            languagesGrid.reload();
                        }
                    });

                    // Prevent saving changes
                    return false;
                }
            });
        });
JS;
    }

    /**
     * @hook  apTplFooter
     * @since 3.3.0
     */
    public function hookApTplFooter(): void
    {
        if ($GLOBALS['controller'] !== 'settings') {
            return;
        }

        $GLOBALS['rlSmarty']->display(self::ADMIN_VIEW_DIR . 'settings.tpl');
    }

    /**
     * @hook  tplHeader
     * @since 3.3.0
     */
    public function hookTplHeader(): void
    {
        global $config, $page_info, $category, $pInfo, $rlCategoryFilter;

        if (!$config['sm_robots_tag']
            || $page_info['Controller'] !== 'listing_type'
            || (!$category || $category['ID'] <= 0)
        ) {
            return;
        }

        if ($rlCategoryFilter) {
            // @todo - Rework it when the "pageNoindex" property will be public in the $rlCategoryFilter class
            $reflection  = new ReflectionClass($rlCategoryFilter);
            $pageNoindex = $reflection->getProperty('pageNoindex')->getValue($rlCategoryFilter);

            // Prevent adding duplicate of the robots meta tag to page
            if ($pageNoindex && $pageNoindex === true) {
                return;
            }
        }

        $tagValue = 'index, follow';

        /**
         * Use count from found listings on page
         * To prevent problems with the Multifield plugin which can update count in category
         */
        if ((int) $pInfo['calc'] <= 0) {
            $tagValue = $config['sm_robots_noindex'];
        }

        echo PHP_EOL . "<meta name=\"robots\" content=\"{$tagValue}\">";
    }

    /**
     * @hook  phpUrlBottom
     * @since 3.3.1
     */
    public function hookPhpUrlBottom(&$url = ''): void
    {
        if ($this->seoBaseModified && $this->buildFinish) {
            $url = str_replace('[lang]/', '', $url);
        }
    }

    /*** DEPRECATED DATA ***/
    /**
     * Adapt URL to SSL protection
     *
     * @deprecated 3.1.0
     * @param string|array $data
     */
    public function adaptToSSL(&$data)
    {}

    /**
     * @deprecated 3.0.2
     * @var int
     */
    public $total = 0;
}
