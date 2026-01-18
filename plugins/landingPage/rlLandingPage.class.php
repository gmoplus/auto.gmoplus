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

use Flynax\utils\Valid;

class rlLandingPage extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * Landing page data
     * @var null
     */
    public $pageData = null;

    /**
     * Landing page mode flag
     * @var boolean
     */
    public $landingMode = true;

    /**
     * Installation
     */
    public function install(): void
    {
        global $rlDb;

        $rlDb->createTable(
            'landing_pages',
            "`ID` int(7) NOT NULL AUTO_INCREMENT,
            `Use_subdomain` enum('0','1') NOT NULL default '0',
            `Box_position` varchar(32) NOT NULL,
            `Box_design` enum('0','1') NOT NULL default '0',
            `Status` enum('active','approval') NOT NULL default 'active',
            PRIMARY KEY (`ID`)",
            RL_DBPREFIX,
            'ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;'
        );
        $rlDb->createTable(
            'landing_pages_lang',
            "`Lang_code` varchar(2) NOT NULL,
            `Page_ID` int(7) NOT NULL,
            `Landing_path` varchar(512) CHARACTER SET utf8 NOT NULL,
            `Landing_subdomain` varchar(128) CHARACTER SET utf8 NOT NULL,
            `Original_path` varchar(512) CHARACTER SET utf8 NOT NULL,
            `Original_subdomain` varchar(128) CHARACTER SET utf8 NOT NULL,
            `Meta_title` varchar(1024) CHARACTER SET utf8 NOT NULL,
            `Meta_h1` varchar(1024) CHARACTER SET utf8 NOT NULL,
            `Meta_description` text CHARACTER SET utf8 NOT NULL,
            `Meta_keywords` text CHARACTER SET utf8 NOT NULL,
            `Seo_text` text CHARACTER SET utf8 NOT NULL,
            KEY `Page_ID` (`Page_ID`),
            KEY `Landing_path` (`Landing_path`),
            KEY `Original_path` (`Original_path`)",
            RL_DBPREFIX,
            'ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;'
        );
    }

    /**
     * Uninstallation
     */
    public function unInstall(): void
    {
        global $rlDb;

        $rlDb->dropTable('landing_pages');
        $rlDb->dropTable('landing_pages_lang');
    }

    /**
     * Validate post field data
     *
     * @param array  $fieldData    - Field data array from the form
     * @param string $fieldKey     - Field form key
     * @param string $fieldNameKey - Field lang phrase key
     * @param array  &$errors      - System errors array
     * @param array  &$errorFields - System error fields array
     * @param bool   $allowEmpty   - Allow certain empty fields, if pass `false` then field should be filled in all languages
     */
    public function validateField(
        array  $fieldData,
        string $fieldKey,
        string $fieldNameKey,
        array &$errors,
        array &$errorFields,
        bool   $allowEmpty = false
    ): void
    {
        global $allLangs, $lang, $langCount;

        $filled_subdomains = 0;
        foreach ($allLangs as $lang_code => $language) {
            if ($fieldData[$lang_code]) {
                $filled_subdomains++;
            }
        }

        if (!$filled_subdomains && $allowEmpty) {
            return;
        }

        if ($filled_subdomains < $langCount) {
            $errors[] = str_replace('{field}', "<b>" . $lang[$fieldNameKey] . "</b>", $lang['lp_error_fill_in_all_languages']);

            foreach ($allLangs as $lang_code => $language) {
                if (!$fieldData[$lang_code]) {
                    $errorFields[] = "{$fieldKey}[{$lang_code}]";
                }
            }
        }
    }

    /**
     * Check for the existing path
     *
     * @param array  $urlData       - Landing page path data
     * @param array  $subdomainData - Landing page subdomain data
     * @param string $dataKey       - Data index
     * @param array  &$errors       - System errors array
     * @param array  &$errorFields  - System error fields array
     */
    public function checkExistingPath(array $urlData, array $subdomainData, string $dataKey, array &$errors, array &$errorFields): void
    {
        global $allLangs, $lang, $rlDb;
        
        $dataDBKey = ucfirst($dataKey);

        foreach ($allLangs as $lang_code => $language) {
            $where = [
                $dataDBKey . '_path' => $this->preparePath($urlData[$lang_code]),
                'Lang_code' => $lang_code
            ];
            if ($subdomainData[$lang_code]) {
                $where[$dataDBKey . '_subdomain'] = $this->preparePath($subdomainData[$lang_code]);
            }
            if ($rlDb->fetch(['Page_ID'], $where, null, null, 'landing_pages_lang', 'row')) {
                $notify_lang = count($allLangs) > 1 ? sprintf('(%s)', $language['name']) : '';
                $errors[] = str_replace('{language}', $notify_lang, $lang["lp_error_{$dataKey}_page_exists"]);
                $errorFields[] = "{$dataKey}_page_url[{$lang_code}]";
            }
        }
    }

    /**
     * Check path in pages and categories
     *
     * @param array  $pathData     - Landing page path data
     * @param array &$errors       - System errors array
     * @param array &$errorFields  - System error fields array
     */
    public function checkSystemPath(array $pathData, array &$errors, array &$errorFields): void
    {
        global $config, $rlDb, $lang;

        foreach ($pathData as $lang_code => $path) {
            if (!$path) {
                continue;
            }

            $path_field = $config['multilingual_paths'] && $lang_code != $config['lang']  ? 'Path_' . $lang_code : 'Path';
            $path = $this->preparePath($path);

            // Check path in pages
            if ($rlDb->getOne('ID', "`{$path_field}` = '{$path}'", 'pages')) {
                $errors[] = str_replace('{path}', '"' . $path . '"', $lang['notice_page_path_exist']);
                $errorFields[] = "landing_page_url[{$lang_code}]";
            }

            // Check path in categories
            if ($rlDb->getOne('ID', "`{$path_field}` = '{$path}'", 'categories')) {
                $errors[] = str_replace('{path}', '"' . $path . '"', $lang['notice_path_exist']);
                $errorFields[] = "landing_page_url[{$lang_code}]";
            }
        }
    }

    /**
     * Check path for host data
     *
     * @param array  $pathData     - Landing page path data
     * @param string $dataKey      - Data index
     * @param array &$errors       - System errors array
     * @param array &$errorFields  - System error fields array
     * @param bool   $denyExtenion - Deny url ending by the file extension
     */
    public function validatePath(array $pathData, string $dataKey, array &$errors, array &$errorFields, bool $denyExtenion = false): void
    {
        global $domain_info, $allLangs, $lang;

        foreach ($pathData as $lang_code => $path) {
            if (!$path) {
                continue;
            }

            if (false !== strpos($path, '://' . $domain_info['host'])
                || false !== strpos($path, '.' . $domain_info['host'])
            ) {
                $notify_lang = count($allLangs) > 1 ? sprintf('(%s)', $allLangs[$lang_code]['name']) : '';
                $errors[] = str_replace('{language}', $notify_lang, $lang['lp_error_url_has_host']);
                $errorFields[] = "{$dataKey}_page_url[{$lang_code}]";
            }

            if ($denyExtenion && preg_match('/\.[^\.\/]+\/?$/', $path)) {
                $errors[] = str_replace('{language}', $notify_lang, $lang['lp_error_url_ending_by_ext']);
                $errorFields[] = "{$dataKey}_page_url[{$lang_code}]";
            }
        }
    }

    /**
     * Prepare/validate the path of the url
     *
     * @param  string $path     - Url path
     * @return string           - Validated url path
     */
    public function preparePath(string $path = ''): string
    {
        Valid::revertQuotes($path);

        $path = str_replace([' ', '"', "'", '\\'], ['+', '', '', ''], $path);
        $path = trim(urldecode($path), ' /');

        if (RL_DIR && strpos($path, RL_DIR) === 0) {
            $path = substr($path, strlen(RL_DIR));
        }

        return $path;
    }

    /**
     * Simulate landing page target url data
     *
     * @hook reeflessWwwRedirect
     *
     * @param string &$redirectTarget - Target URL
     * @param bool   &$admin          - Is admin interface
     */
    public function hookReeflessWwwRedirect(&$redirectTarget, &$admin): void
    {
        global $domain_info, $rlDb, $config;

        if (!$config['mod_rewrite']) {
            return;
        }

        if ($admin || $redirectTarget) {
            return;
        }

        $subdomain = false;
        $sys_host = false === strpos($domain_info['host'], 'www.') ? $domain_info['host'] : ltrim($domain_info['domain'], '.');

        if ($_SERVER['HTTP_HOST'] != $domain_info['host']) {
            $subdomain_data = explode($sys_host, $_SERVER['HTTP_HOST']);

            if ($subdomain_data[0]) {
                $subdomain = trim($subdomain_data[0], '.');
            }
        }

        $request_path = $this->preparePath($_SERVER['REQUEST_URI']);
        $request_path_parts = explode('/', $request_path);
        $get_vars = '';

        if (strlen($request_path_parts[0]) === 2) {
            $lang_code = array_shift($request_path_parts);
            $request_path = implode('/', $request_path_parts);
        } else {
            $lang_code = $config['lang'];
        }

        if ($request_path) {
            $opt = sprintf(
                "AND ((`Landing_path` = '{$request_path}' %s) OR (`Original_path` = '{$request_path}' %s))",
                $subdomain ? "AND `Landing_subdomain` = '{$subdomain}'" : '',
                $subdomain ? "AND `Original_subdomain` = '{$subdomain}'" : ''
            );
            $sql = "
                SELECT `T1`.*, `T2`.`Box_position`, `T2`.`Box_design`
                FROM `{db_prefix}landing_pages_lang` AS `T1`
                LEFT JOIN `{db_prefix}landing_pages` AS `T2` ON `T1`.`Page_ID` = `T2`.`ID`
                WHERE `T1`.`Lang_code` = '{$lang_code}' {$opt} AND `T2`.`Status` = 'active'
            ";

            $page_lang_data = $rlDb->getRow($sql);

            if ($page_lang_data) {
                // Landing page simulation mode
                if ($page_lang_data['Landing_path'] == $request_path) {
                    $original_path = $page_lang_data['Original_path'];

                    if (false !== strpos($page_lang_data['Original_path'], '?')) {
                        $original_path_parts = explode('?', $page_lang_data['Original_path']);
                        $original_path = trim($original_path_parts[0], '/');
                        $get_vars = explode('&', $original_path_parts[1]);
                        foreach ($get_vars as $var_set) {
                            $var_data = explode('=', $var_set);

                            // Simulate nested vars get parameter
                            if (false !== $offset = strpos($var_data[0], '[')) {
                                preg_match_all('/\[([^\]]+)\]/', $var_data[0], $matches);
                                $base_var = substr($var_data[0], 0, $offset);
                                $get_var = &$_GET[$base_var];
                                $request_var = &$_REQUEST[$base_var];

                                foreach ($matches[1] as $match) {
                                    $get_var = &$get_var[$match];
                                    $request_var = &$request_var[$match];
                                }

                                $get_var = $var_data[1];
                                $request_var = $var_data[1];
                            }
                            // Simulate single var get parameter
                            else {
                                $_GET[$var_data[0]] = $var_data[1];
                            }
                        }
                    }
                    // Add trailing slash if there is not extension at the end
                    elseif (!preg_match('/\.[^\.\/]+\/?$/', $original_path)) {
                        $page_lang_data['Original_path'] .= '/';
                    }

                    // Define listing details page
                    if (preg_match('/\-l?([0-9]+)\.html$/', $original_path, $matches)) {
                        $_GET['listing_id'] = $matches[1];
                    }

                    // Remove extension if exists
                    if (preg_match('/\.[^\.\/]+\/?$/', $original_path)) {
                        $original_path = preg_replace('/\.[^\.\/]+\/?$/', '', $original_path);
                    }

                    $path_items = explode('/', urldecode($original_path));

                    if ($page_lang_data['Original_subdomain']) {
                        $domain_host = sprintf('%s.%s', $page_lang_data['Original_subdomain'], $sys_host);
                        $page = $page_lang_data['Original_subdomain'];
                    } else {
                        if ($page_lang_data['Landing_subdomain']) {
                            $domain_host = sprintf('%s.%s', $page_lang_data['Landing_subdomain'], $sys_host);
                        } else {
                            $domain_host = $domain_info['host'];
                        }
                        $page = array_shift($path_items);
                    }

                    $_SERVER['HTTP_HOST'] = $domain_host;
                    $_SERVER['REQUEST_URI'] = sprintf(
                        '%s%s/%s',
                        RL_DIR ? '/' . rtrim(RL_DIR, '/') : '',
                        ($page_lang_data['Lang_code'] != $config['lang'] ? '/' . $page_lang_data['Lang_code'] : ''),
                        $page_lang_data['Original_path']
                    );
                    $_GET['page'] = $page;
                    $_GET['rlVareables'] = implode('/', $path_items);
                    // Add original page GET variables to avoid redirect in rlListings::originalUrlRedirect()
                    $_SERVER['QUERY_STRING'] .= '&landingOriginalPageVars=' . $original_path;

                    // Reset selected GeoFilter location
                    if ($GLOBALS['plugins']['multiField']) {
                        $GLOBALS['config']['mf_geo_autodetect'] = false;
                        unset($_SESSION['geo_filter_location']);
                        $GLOBALS['reefless']->eraseCookie('mf_geo_location');
                        $_COOKIE['mf_geo_location'] = '';
                    }
                }
                // Original page mode
                else {
                    $this->landingMode = false;
                }

                $this->pageData = $page_lang_data;
            }
        }
    }

    /**
     * Set landing page meta data
     *
     * @hook pageTitle
     *
     * @param string &$pageTitle   - Page meta title
     * @param array  &$breadCrumbs - Bread Crumbs
     */
    public function hookPageTitle(string &$pageTitle, array &$breadCrumbs): void
    {
        global $page_info, $blocks, $domain_info, $config, $plugins, $rlDb, $category;

        if (!$config['mod_rewrite']) {
            return;
        }

        // Move pageTitle hook forward if the "Filters" plugin has been installed later than "Landing Page" plugin
        if (!$config['lp_hook_moved'] && $plugins['categoryFilter']) {
            $plugin_keys = array_keys($plugins);
            if (array_search('categoryFilter', $plugin_keys) > array_search('landingPage', $plugin_keys)) {
                $hook = ['Name' => 'pageTitle', 'Class' => 'LandingPage', 'Plugin' => 'landingPage'];
                $rlDb->delete($hook, 'hooks');
                $rlDb->insertOne($hook, 'hooks');

                $insert_config = [
                    'Key' => 'lp_hook_moved',
                    'Default' => 1,
                    'Plugin' => 'landingPage'
                ];
                $rlDb->insertOne($insert_config, 'config');

                $GLOBALS['reefless']->refresh();
            }
        }

        if ($this->pageData) {
            $landing_page_url = sprintf(
                '%s://%s%s/%s%s%s/',
                $domain_info['scheme'],
                ($this->pageData['Landing_subdomain'] ? $this->pageData['Landing_subdomain'] . '.' : ''),
                $domain_info['host'],
                RL_DIR ?  RL_DIR : '',
                ($this->pageData['Lang_code'] != $config['lang'] ? $this->pageData['Lang_code'] . '/' : ''),
                $this->pageData['Landing_path']
            );

            if ($this->landingMode) {
                $pageTitle = $this->pageData['Meta_title'];
                $page_info['meta_title'] = $this->pageData['Meta_title'];
                $page_info['meta_description'] = $this->pageData['Meta_description'];

                if ($this->pageData['Meta_keywords']) {
                    $page_info['meta_keywords'] = $this->pageData['Meta_keywords'];
                }
                if ($this->pageData['Meta_h1']) {
                    $page_info['h1'] = $this->pageData['Meta_h1'];
                }

                // Assign seo text box on the page
                if ($this->pageData['Seo_text']) {
                    $blocks['seo_text_landing'] = [
                        'Side' => $this->pageData['Box_position'],
                        'Content' => $this->pageData['Seo_text'],
                        'Key' => 'seo_text_landing',
                        'Tpl' => $this->pageData['Box_design'],
                        'Type' => 'html',
                        'Header' => 0
                    ];
                    $GLOBALS['rlCommon']->defineBlocksExist($blocks);
                }

                // Modify hreflang
                $hreflang = &$GLOBALS['rlSmarty']->_tpl_vars['hreflang'];
                $multilang_data = $GLOBALS['rlDb']->fetch(
                    '*',
                    ['Page_ID' => $this->pageData['Page_ID']],
                    "AND `Lang_code` != '" . RL_LANG_CODE . "'",
                    null,
                    'landing_pages_lang'
                );

                $hreflang[RL_LANG_CODE] = $landing_page_url;

                foreach ($multilang_data as $multilang) {
                    $subdomain = $multilang['Landing_subdomain'] ? $multilang['Landing_subdomain'] . '.' : '';
                    $hreflang[$multilang['Lang_code']] = sprintf(
                        '%s://%s%s/%s%s%s/',
                        $domain_info['scheme'],
                        $subdomain,
                        ($subdomain ? ltrim($domain_info['domain'], '.') : $domain_info['host']),
                        RL_DIR ?  RL_DIR : '',
                        ($multilang['Lang_code'] != $config['lang'] ? $multilang['Lang_code'] . '/' : ''),
                        $multilang['Landing_path']
                    );
                }

                // Remove category description if the seo text exists
                if ($this->pageData['Seo_text']
                    && $category
                    && $category['des']
                    && $page_info['Controller'] == 'listing_type'
                ) {
                    unset($category['des']);
                }
            }

            // Add canonical path
            $page_info['canonical'] = $landing_page_url;
        }
    }

    /**
     * Include plugin javascript
     *
     * @hook apTplFooter
     */
    public function hookApTplFooter(): void
    {
        echo <<< HTML
        <script>
        $(function(){
            $('.tabs > li').click(function(){
                var lang_code = $(this).attr('lang');
                $('.tabs-content > div').addClass('hide');
                $('.tabs-content > div.' + lang_code).removeClass('hide');
            });
        });
        </script>
HTML;
    }

    /**
     * @hook sitemapAddPluginUrls
     */
    public function hookSitemapAddPluginUrls(array &$pluginsUrls): void
    {
        global $config, $domain_info;

        if (!$config['mod_rewrite']) {
            return;
        }

        $sql = "
            SELECT `T1`.`Lang_code`, `T1`.`Landing_path`, `T1`.`Landing_subdomain`
            FROM `{db_prefix}landing_pages_lang` AS `T1`
            LEFT JOIN `{db_prefix}landing_pages` AS `T2` ON `T1`.`Page_ID` = `T2`.`ID`
            WHERE `T2`.`Status` = 'active' ORDER BY `T1`.`Page_ID`
        ";
        $pages = $GLOBALS['rlDb']->getAll($sql);
        $urls = [];

        if ($pages) {
            foreach($pages as $page) {
                $urls[] = sprintf(
                    '%s://%s%s/%s%s%s/',
                    $domain_info['scheme'],
                    ($page['Landing_subdomain'] ? $page['Landing_subdomain'] . '.' : ''),
                    $domain_info['host'],
                    RL_DIR ?  RL_DIR : '',
                    ($page['Lang_code'] == $config['lang'] ? '' : $page['Lang_code'] . '/'),
                    $page['Landing_path']
                );
            }

            $pluginsUrls[] = $urls;
        }
    }

    /**
     * @hook apAjaxRequest
     */
    public function hookApAjaxRequest(&$out = null, $item = null): void
    {
        global $rlDb;

        if ($item === 'deleteLandingPage') {
            $id = (int) $_REQUEST['id'];

            if (!$id) {
                $out = ['status' => 'ERROR'];
                return;
            }

            $rlDb->delete(['ID' => $id], 'landing_pages');
            $action = $rlDb->delete(['Page_ID' => $id], 'landing_pages_lang', null, null);

            $out = ['status' => $action ? 'OK' : 'ERROR'];
        }
    }

    /**
     * Display base tag if listings sorting dropdown available on the page
     *
     * @since 1.0.1
     * @hook tplHeader
     */
    public function hookTplHeader()
    {
        global $domain_info, $config;

        if (!$this->pageData || !$this->landingMode) {
            return;
        }

        if ($GLOBALS['rlSmarty']->_tpl_vars['sorting']) {
            $base = sprintf(
                '%s://%s%s/%s%s%s',
                $domain_info['scheme'],
                ($this->pageData['Original_subdomain'] ? $this->pageData['Original_subdomain'] . '.' : ''),
                $domain_info['host'],
                RL_DIR ?  RL_DIR : '',
                ($this->pageData['Lang_code'] == $config['lang'] ? '' : $this->pageData['Lang_code'] . '/'),
                $this->pageData['Original_path']
            );

            echo '<base href="' . $base . '" />';
        }
    }
}
