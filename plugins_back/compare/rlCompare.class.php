<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLCOMPARE.CLASS.PHP
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

class rlCompare extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * Page controllers list
     *
     * Compare listing tab won't be display on the mentioned pages
     *
     * @since 3.0.0
     * @var array
     */
    private $excludedPages = array(
        'add_listing',
        'edit_listing',
        'compare',
        'search_map',
        'login',
    );

    /**
     * Field keys to be excluded from the comparison table
     *
     * @since 3.0.0
     * @var array
     */
    private $excludedFields = array(
        'account_address_on_map',
        'title',
    );

    /**
     * Exclude page
     *
     * @since 3.0.0
     * @param string $controller - page controller to exclude
     */
    public function excludePage($controller)
    {
        array_push($this->excludedPages, $controller);
    }

    /**
     * Exclude field
     *
     * @since 3.0.0
     * @param string $field - field key
     */
    public function excludeField($field)
    {
        array_push($this->excludedFields, $field);
    }

    /**
     * Display compare tab view
     *
     * @since 3.0.0
     * @hook tplFooter
     */
    public function hookTplFooter()
    {
        if ($this->isPageAllowed()) {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'compare' . RL_DS . 'tab.tpl');
        }
    }

    /**
     * Define is the current page allowed to display the comparison tab on it
     *
     * @since 3.0.1
     *
     * @return boolean - is page allowed
     */
    private function isPageAllowed()
    {
        global $page_info;

        return !in_array($page_info['Controller'], $this->excludedPages)
            && $page_info['Key'] != '404';
    }

    /**
     * Define template svg support
     *
     * @since 3.0.0
     * @hook boot
     */
    public function hookBoot()
    {
        global $blocks;

        $this->defineSVGSupport();

        // Remove block if it assign to any other page
        if ($GLOBALS['page_info']['Key'] != 'compare_listings' && $blocks['compare_results']) {
            unset($blocks['compare_results']);
            $GLOBALS['rlCommon']->defineBlocksExist($blocks);
        }
    }

    /**
     * Define svg support
     *
     * @since 3.0.0
     * @hook ajaxRecentlyAddedLoadPre
     */
    public function hookAjaxRecentlyAddedLoadPre()
    {
        $this->defineSVGSupport();
    }

    /**
     * Static data registration
     *
     * @since 3.0.0
     * @hook staticDataRegister
     */
    public function hookStaticDataRegister()
    {
        if ($this->isPageAllowed()) {
            $GLOBALS['rlStatic']->addJS(RL_PLUGINS_URL . 'compare/static/lib.js');
            $GLOBALS['rlStatic']->addFooterCSS(RL_PLUGINS_URL . 'compare/static/style.css');
        }
    }

    /**
     * Display icon in listing grids
     *
     * @since 3.0.0
     * @hook listingNavIcons
     */
    public function hookListingNavIcons()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'compare' . RL_DS . 'grid_icon.tpl');
    }

    /**
     * Ajax requests handler
     *
     * @since 3.0.0
     * @hook ajaxRequest
     */
    public function hookAjaxRequest(&$out, &$request_mode)
    {
        global $rlDb, $lang;

        if (!in_array($request_mode, array(
            'compareFetch',
            'compareSaveTable',
            'compareRemoveTable',
            'compareRemoveItem',
        ))) {
            return;
        }

        $account_info = $_SESSION['account'];

        switch($request_mode) {
            case 'compareFetch':
                if (!$_COOKIE['compare_listings']) {
                    return;
                }

                if (!$lang) {
                    $request_lang = @$_REQUEST['lang'] ?: $GLOBALS['config']['lang'];
                    $lang = $GLOBALS['rlLang']->getLangBySide('frontEnd', $request_lang);
                }

                // Define flag variable to catch it in 'listingsModifyGroupMyFavorite' hook
                define('COMPARE_LISTINGS', true);

                // Redefine favorites variable in cookies to allow getMyFavorite() fetch
                // necessary listings by necessary IDs
                $_COOKIE['favorites'] = $_COOKIE['compare_listings'];

                $GLOBALS['reefless']->loadClass('Listings');
                $listings = $GLOBALS['rlListings']->getMyFavorite(false, false, 0, 100);

                $out = array(
                    'status'  => $listings ? 'OK' : 'ERROR',
                    'results' => $listings ? $this->filter($listings) : ''
                );
                break;

            case 'compareSaveTable':
                if (!$GLOBALS['rlAccount']->isLogin()) {
                    $GLOBALS['reefless']->loadClass('Notice');
                    $GLOBALS['rlNotice']->saveNotice($lang['notice_logged_out']);

                    $out = array(
                        'status'  => 'ERROR',
                        'errorCode' => 'NOT_LOGGED_IN'
                    );
                    return;
                }

                $name = \Flynax\Utils\Valid::stripJS($_REQUEST['name']);
                $type = $_REQUEST['type'];
                $path = $GLOBALS['rlValid']->str2path($account_info['Full_name'] . '-' . $name);
                $path = $this->checkPath($path);

                $insert = array(
                    'Name'       => $name ?: $lang['not_available'],
                    'Path'       => $path,
                    'Account_ID' => $account_info['ID'],
                    'IDs'        => $_COOKIE['compare_listings'],
                    'Type'       => $type == 'public' ? 'public' : 'private',
                    'Date'       => 'NOW()'
                );
                $rlDb->insert($insert, 'compare_table');

                $GLOBALS['reefless']->loadClass('Notice');
                $GLOBALS['rlNotice']->saveNotice(
                    str_replace('{name}', $name, $lang['compare_save_completed_notice'])
                );

                $out = array(
                    'status'  => 'OK',
                    'results' => $path
                );
                break;

            case 'compareRemoveTable':
                $id      = (int) $_REQUEST['id'];
                $on_page = $_REQUEST['savedTable'];

                if (!$id) {
                    $out = array(
                        'status'  => 'ERROR',
                        'message' => 'No ID parameter specified'
                    );
                    return;
                }

                // Get table details
                $table = $rlDb->fetch(
                    '*',
                    array('ID' => $id),
                    null, 1, 'compare_table', 'row'
                );

                // No table or no table owner error
                if (!$table || $account_info['ID'] != $table['Account_ID']) {
                    $out = array(
                        'status'  => 'ERROR',
                        'message' => 'No table found or table does not belong to this user'
                    );
                    return;
                }

                $rlDb->delete(array('ID' => $id), 'compare_table');

                // Save notice if table removes on it's page because in this case
                // visiter will be redirected to the parent page
                if ($on_page) {
                    $GLOBALS['reefless']->loadClass('Notice');
                    $GLOBALS['rlNotice']->saveNotice($lang['compare_table_removed']);
                }

                $out = array(
                    'status'  => 'OK'
                );
                break;

            case 'compareRemoveItem':
                $item_id  = (int) $_REQUEST['itemID'];
                $table_id = (int) $_REQUEST['tableID'];

                if (!$item_id || !$table_id) {
                    $out = array(
                        'status'  => 'ERROR',
                        'message' => 'No itemID or tableID parameter specified'
                    );
                    return;
                }

                // Get table details
                $table = $rlDb->fetch(
                    '*',
                    array('ID' => $table_id),
                    null, 1, 'compare_table', 'row'
                );

                // No table or no table owner error
                if (!$table || $account_info['ID'] != $table['Account_ID']) {
                    $out = array(
                        'status'  => 'ERROR',
                        'message' => 'No table found or table does not belong to this user'
                    );
                    return;
                }

                $ids = explode(',', $table['IDs']);
                unset($ids[array_search($item_id, $ids)]);

                $update = array(
                    'fields' => array('IDs' => implode(',', $ids)),
                    'where'  => array('ID'  => $table_id),
                );

                $rlDb->update($update, 'compare_table');

                $out = array(
                    'status'  => 'OK'
                );
                break;
        }
    }

    /**
     * Order listings by compare position
     *
     * @since 3.0.0
     * @hook listingsModifyGroupMyFavorite
     */
    public function hookListingsModifyGroupMyFavorite()
    {
        global $sql;

        if (!defined('COMPARE_LISTINGS')) {
            return;
        }

        if (!strpos($sql, 'GROUP BY')) {
            $sql .= "GROUP BY `T1`.`ID` ";
        }

        $sql .= "ORDER BY FIND_IN_SET(`T1`.`ID`, '{$_COOKIE['compare_listings']}') ";
    }

    /**
     * Display icon on listing details page
     *
     * @since 3.0.0
     * @hook listingDetailsNavIcons
     */
    public function hookListingDetailsNavIcons()
    {
        global $listing_data, $rlListings, $rlSmarty;

        $short_form_fields = $rlListings->getFormFields(
            $listing_data['Category_ID'],
            'short_forms',
            $listing_data['Listing_type'],
            $listing_data['Parent_IDs']
        );

        $fields = [];

        if ($short_form_fields && $rlListings->fieldsList) {
            foreach ($rlListings->fieldsList as $field) {
                if ($short_form_fields[$field['Key']]) {
                    $fields[] = $field['value'];
                }
            }
        }

        $rlSmarty->assign('compare_ad_fields', implode(', ', $fields));
        $rlSmarty->display(RL_PLUGINS . 'compare' . RL_DS . 'details_icon.tpl');
    }

    /**
     * @hook  sitemapExcludedPages
     * @since 3.0.0
     */
    public function hookSitemapExcludedPages(&$urls)
    {
        $urls = array_merge($urls, array('compare_listings'));
    }

    /**
     * @since 3.1.0
     * @hook tplFeaturedItemIcon
     */
    public function hookTplFeaturedItemIcon()
    {
        if ($GLOBALS['config']['compare_featured_ad_icon']) {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'compare' . RL_DS . 'featured_icon.tpl');
        }
    }

    /**
     * @since 3.1.0
     * @hook apPhpConfigBottom
     */
    public function hookApPhpConfigBottom()
    {
        global $configs;

        if (version_compare($GLOBALS['config']['rl_version'], '4.8.0', '>')) {
            return;
        }

        foreach ($configs as $group_index => $config_group) {
            foreach ($config_group as $config_index => $group_item) {
                if ($group_item['Key'] == 'compare_featured_ad_icon') {
                    unset($configs[$group_index][$config_index]);
                    break;
                }
            }
        }
    }

    /**
     * @since 3.1.1
     * @hook tplHeader
     */
    public function hookTplHeader()
    {
        /**
         * @todo - Remove this condition and code once the plugin compatibility will be >= 4.8.2
         */
        if ($GLOBALS['tpl_settings']['name'] == 'general_cragslist_wide'
            && $GLOBALS['page_info']['Key'] == 'compare_listings'
            && version_compare($GLOBALS['config']['rl_version'], '4.8.1', '<=')
        ) {
            echo "
                <style>
                .preview .remove {
                    width: 1.125rem;
                    height: 1.125rem;
                    display: inline-block;
                    background: url(" . RL_TPL_BASE . "img/gallery.png) -18px -300px no-repeat;
                    opacity: 0.7;
                    cursor: pointer;
                    -webkit-transition: opacity 0.4s;
                    transition: opacity 0.4s;
                }
                </style>
            ";
        }
    }

    /**
     * @since 3.1.2
     * @hook tplHeaderUserNav
     */
    public function hookTplHeaderUserNav()
    {
        /**
         * @todo - Move this code to tplBodyTop hook once it is available
         */
        if ($this->isPageAllowed()) {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'compare/static/icons.svg');
        }
    }

    /**
     * Define SVG support
     *
     * @since 3.0.0
     */
    private function defineSVGSupport()
    {
        global $config, $rlSmarty;

        $rlSmarty->assign('compare_cookie_ids', explode(',', $_COOKIE['compare_listings']));
    }

    /**
     * Remove useless fields from the listings
     *
     * @param  array $listings - listings data
     * @return array           - adapted listings data
     */
    private function filter($listings)
    {
        global $tpl_settings;

        $adapted = array();

        foreach ($listings as $listing) {
            $fields = [];

            foreach ($listing['fields'] as $field) {
                if (!$field['value'] || !$field['Details_page'] || in_array($field['Key'], $tpl_settings['listing_grid_except_fields'])) {
                    continue;
                }

                $fields[]= $field['value'];
            }

            $adapted[] = array(
                'id'     => $listing['ID'],
                'url'    => $listing['url'],
                'img'    => $listing['Main_photo'] ? RL_FILES_URL . $listing['Main_photo'] : '',
                'title'  => $listing['listing_title'],
                'fields' => implode(', ', $fields),
            );
        }

        return $adapted;
    }

    /**
     * @deprecated 3.0.0 - See self::hookAjaxRequest()
     */
    function load($ids = false) {}

    /**
     * Get listings to compare by IDs
     *
     * @param  array $ids - Listing IDs
     */
    public function get($ids = array())
    {
        global $rlSmarty, $lang;

        if (!$ids) {
            return false;
        }

        $listings = array();
        $fields   = array(
            array(
                'Key' => 'Main_photo'
            ),
            array(
                'Key' => 'listing_title',
                'name' => $lang['compare_title']
            ),
        );

        $GLOBALS['reefless']->loadClass('Listings');

        foreach ($ids as $id) {
            $listing = $GLOBALS['rlListings']->getListing($id, true);

            if ($listing && $listing['Active_status']) {
                // Push listing
                $listing['fields'] = $this->prepareListing($listing, $fields);
                $listings[] = $listing;
            }
        }

        $rlSmarty->assign('compare_listings', $listings);
        $rlSmarty->assign('compare_fields', $fields);
    }

    /**
     * Prepare listing data
     *
     * @since 3.0.0
     *
     * @param  array $data     - listing data
     * @param  array &$fields  - listing fields data
     * @return array           - adapted data array
     */
    private function prepareListing($data, &$fields)
    {
        $GLOBALS['reefless']->loadClass('Listings');

        $out = array();
        $listing = $GLOBALS['rlListings']->getListingDetails(
            $data['Category_ID'],
            $data,
            $GLOBALS['rlListingTypes']->types[$data['Listing_type']]
        );

        foreach ($listing as $group) {
            if (!$group || !$group['Fields']) {
                continue;
            }

            foreach ($group['Fields'] as &$field) {
                if (in_array($field['Key'], $this->excludedFields)) {
                    continue;
                }

                unset($field['Values'], $field['source']);

                // Save field
                $fields[$field['Key']] = $field;
            }

            $out = array_merge($out, $group['Fields']);
        }

        return $out;
    }

    /**
     * @deprecated 3.0.0
     */
    function getParentCatFields($id = false) {}

    /**
     * Validate and unify the path
     *
     * @param  string $path - path string
     * @return string       - validated and unified path
     */
    private function checkPath($path)
    {
        $path = preg_replace('/([0-9\-]+)$/', '', $path);

        if ($GLOBALS['rlDb']->getOne('ID', "`Path` = '{$path}'", 'compare_table')) {
            if ((bool) preg_match('/^([0-9])+\-/', $path[0], $matches)) {
                $prefix = $matches[1]++;
            } else {
                $prefix = '2-';
            }
            return $this->checkPath($prefix . $path);
        } else {
            return $path;
        }
    }

    /**
     * @deprecated 3.0.0 - See self::hookAjaxRequest()
     */
    function ajaxRemoveSavedItem() {}

    /**
     * @deprecated 3.0.0 - See self::hookAjaxRequest()
     */
    function ajaxRemoveTable() {}

    /**
     * Plugin installer
     *
     * @since 3.0.0
     */
    public function install()
    {
        global $rlDb;

        // Create main plugin table
        $rlDb->createTable(
            'compare_table',
            "`ID` int(10) NOT NULL AUTO_INCREMENT,
            `Name` varchar(32) CHARACTER SET utf8 NOT NULL DEFAULT '',
            `Path` varchar(64) CHARACTER SET utf8 NOT NULL DEFAULT '',
            `Account_ID` int(8) NOT NULL DEFAULT '0',
            `IDs` mediumtext NOT NULL,
            `Type` enum('public','private') NOT NULL DEFAULT 'private',
            `Date` datetime NOT NULL,
            PRIMARY KEY (`ID`),
            KEY `Account_ID` (`Account_ID`)"
        );

        // Update related box
        $update = array(
            'fields' => array(
                'Position' => '2',
                'Sticky'   => '0',
                'Page_ID'  => $rlDb->getOne('ID', "`Key` = 'compare_listings'", 'pages')
            ),
            'where'  => array(
                'Key'      => 'compare_results'
            )
        );
        $rlDb->update($update, 'blocks');
    }

    /**
     * Remove plugins db tables
     *
     * @since 3.0.0
     */
    public function uninstall()
    {
        $GLOBALS['rlDb']->dropTable('compare_table');
    }

    /**
     * Update process of the plugin (copy from core)
     *
     * @since 3.0.0
     * @todo Remove this method when compatibility will be >= 4.6.2
     *
     * @param string $version
     */
    public function update($version)
    {
        $version_method = 'update' . (int) str_replace('.', '', $version);
        if (method_exists($this, $version_method)) {
            $this->$version_method();
        }
    }

    /**
     * Update to 2.1.0 version
     */
    public function update210()
    {
        $GLOBALS['rlDb']->query("
            DELETE FROM `{db_prefix}hooks`
            WHERE `Name` = 'listingDetailsPreFields' AND `Plugin` = 'compare'
            LIMIT 1
        ");
    }

    /**
     * Update to 3.0.0 version
     */
    public function update300()
    {
        global $rlDb;

        // Remove hooks
        $hooks_to_be_removed = array(
            'tplHeader',
            'listingDetailsAfterStats',
            'ajaxRecentlyAddedLoadPost',
        );
        $rlDb->query("
            DELETE FROM `{db_prefix}hooks` 
            WHERE `Plugin` = 'compare' 
            AND `Name` IN ('" . implode("','", $hooks_to_be_removed) . "')
        ");

        // Decrease text fields length
        $rlDb->query("
            ALTER TABLE `{db_prefix}compare_table` 
            CHANGE `Name` `Name` VARCHAR(32) CHARACTER SET utf8 NOT NULL DEFAULT '',
            CHANGE `Path` `Path` VARCHAR(64) CHARACTER SET utf8 NOT NULL DEFAULT ''
        ");

        // Remove legacy files
        $files_to_be_removed = array(
            'static/style_responsive42.css',
            'static/rtl.css',
            'static/gallery.png',
            'static/gallery_responsive_42.png',
            'static/gallery_responsive_42_x2.png',
            'icon.tpl',
            'icon_responsive_42.tpl',
        );
        foreach ($files_to_be_removed as $file) {
            unlink(RL_PLUGINS . 'compare/' . $file);
        }

        // Remove useless config
        $rlDb->query("
            DELETE FROM `{db_prefix}config`
            WHERE `Plugin` = 'compare' 
            AND `Key` IN ('compare_module', 'compare_common')
        ");

        // Update block phrases
        $rlDb->query("
            UPDATE `{db_prefix}lang_keys` AS `T1`
            LEFT JOIN `{db_prefix}lang_keys` AS `T2` 
                ON `T1`.`Code` = `T2`.`Code` AND `T2`.`Key` = 'compare_my_tables'
            SET `T1`.`Value` = `T2`.`Value`
            WHERE `T1`.`Key` = 'blocks+name+compare_results';
        ");

        // Remove phrases
        $phrases = array(
            'config+name+compare_module',
            'config+name+compare_common',
            'compare_picture',
            'compare_my_tables',
        );

        $rlDb->query("
            DELETE FROM `{db_prefix}lang_keys` 
            WHERE `Plugin` = 'compare' 
            AND `Key` IN ('" . implode("','", $phrases) . "')
        ");
    }

    /**
     * Update to 3.1.2 version
     */
    public function update312()
    {
        global $languages, $rlDb;

        $rlDb->query("ALTER TABLE `{db_prefix}compare_table` ENGINE=InnoDB;");

        if (array_key_exists('ru', $languages)) {
            $russianTranslation = json_decode(file_get_contents(RL_UPLOAD . 'compare/i18n/ru.json'), true);

            foreach ($russianTranslation as $phraseKey => $phraseValue) {
                if (!$rlDb->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $insertPhrase = $rlDb->fetch(
                        ['Module', 'Key', 'Plugin'],
                        ['Code' => $GLOBALS['config']['lang'], 'Key' => $phraseKey],
                        null, 1, 'lang_keys', 'row'
                    );

                    $insertPhrase['Code']  = 'ru';
                    $insertPhrase['Value'] = $phraseValue;

                    $rlDb->insertOne($insertPhrase, 'lang_keys');
                } else {
                    $rlDb->updateOne([
                        'fields' => ['Value' => $phraseValue],
                        'where' => ['Key'   => $phraseKey, 'Code' => 'ru'],
                    ], 'lang_keys');
                }
            }
        }
    }
}
