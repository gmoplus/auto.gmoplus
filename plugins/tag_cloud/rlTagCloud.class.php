<?php


/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLTAGCLOUD.CLASS.PHP
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

class rlTagCloud
{
    /**
     * Box template
     */
    var $box_tpl = '
        $tagCloud = [{tag_cloud}];

        if ($GLOBALS[\'config\'][\'tc_order\'] === \'randomly\') {
            shuffle($tagCloud);
        }

        $GLOBALS[\'rlSmarty\']->assign_by_ref(\'tag_cloud\', $tagCloud);
        $GLOBALS[\'rlSmarty\']->display(RL_PLUGINS . \'tag_cloud/tag_cloud.tpl\');';

    /**
     * Current Tag
     *
     * @since 2.1.0
     * @var array
     */
    public $tagPages = [];

    /**
     * Flag Request
     *
     * @since 2.1.0
     * @var boolean
     */
    public $ownRequest = false;

    /**
     * Url info by tag
     *
     * @since 2.1.0
     * @var boolean
     */
    public $tagInfo = [];

    /**
     * Update box
     *
     * @return bool
     */
    public function updateBox()
    {
        global $rlDb;

        $tag_cloud = $this->getTagCloud();

        $code = '';
        foreach ($tag_cloud as $key => $tag) {
            $code .= "'{$key}' => ['Tag' => '" . addslashes($tag['Tag']) . "', ";
            $code .= "'Path' => '{$tag['Path']}', 'Count' => '{$tag['Count']}', ";
            $code .= "'Size' => '{$tag['Size']}' ], ";
        }
        $code = rtrim($code, ',');

        $update = [
            'fields' => [
                'Content' => str_replace('{tag_cloud}', $code, $this->box_tpl)
            ],
            'where' => [
                'Key' => 'tag_cloud'
            ]
        ];

        $rlDb->rlAllowHTML = true;
        $rlDb->updateOne($update, 'blocks');

        return true;
    }

    /**
     * Adding new tag
     *
     * @param $search_query
     */
    public function searchAddTag($search_query)
    {
        global $config, $rlDb;

        if ($_SESSION['keyword_search'] == $search_query) {
            return;
        }

        if ($config['tc_query_explode']) {
            $tags = explode(' ', trim($search_query));
        } else {
            $tags[0] = $search_query;
        }

        /* load the utf8 lib */
        loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

        foreach ($tags as $tag) {
            // Prevent saving tags with SQL injections in query
            if (preg_match('/([A-Z_]+)\s?\(.+/', $tag) || preg_match('/([A-Z_]+)[^=]+=/', $tag)) {
                continue;
            }

            if (strlen($tag) >= $config['tc_tag_min_symbols'] && (stripos($config['tc_exwords'] . ",", $tag . ",") === false)) {
                if ($rlDb->getOne('Tag', "`Tag` = '{$tag}'", 'tag_cloud')) {
                    $rlDb->query("UPDATE `{db_prefix}tag_cloud` SET `Count`=`Count`+1 WHERE `Tag` ='{$tag}'");
                } else {
                    $f_key = $tag;

                    if (!utf8_is_ascii($f_key)) {
                        $f_key = utf8_to_ascii($f_key);
                    }

                    $tag = stripcslashes($tag);
                    $path = $GLOBALS['rlValid']->str2path($tag);

                    // Prevent using "Listing ID" format of path
                    if (preg_match('/-\d+$/', $path)) {
                        $path = preg_replace('/(-)(\d+)$/', '$2', $path);
                    }

                    $data = array(
                        'Key' => $GLOBALS['rlValid']->str2key($f_key),
                        'Path' => $path,
                        'Tag' => $tag,
                        'Status' => 'active',
                        'Type' => 0,
                        'Count' => 0,
                        'Modified' => 'NOW()',
                        'Date' => 'NOW()'
                    );
                    $this->saveTag($data);
                }
            }
        }
        if ($_SESSION['keyword_search_data']['keyword_search']) {
            $_SESSION['keyword_search_data']['keyword_search'] = stripcslashes($search_query);
        }
        $this->updateBox();
    }

    /**
     * Save Tag to the database
     *
     * @since 2.1.0
     *
     * @param array $insertData - data the tag
     *
     * @return bool
     */
    public function saveTag($insertData)
    {
        global $rlDb;

        if (!$insertData) {
            return false;
        }

        $rlDb->rlAllowHTML = true;
        return $rlDb->insertOne($insertData, 'tag_cloud');
    }

    /**
     * Removing tag from list
     *
     * @param bool $key
     * @return xajaxResponse
     */
    public function ajaxDeleteTag($key = false)
    {
        global $_response, $rlDb, $rlLang;

        // check admin session expire
        if ($GLOBALS['reefless']->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $rlDb->delete(['Key' => $key], 'tag_cloud');

        if (method_exists($rlLang, 'deletePhrases')) {
            $rlLang->deletePhrases([
                ['Key' => "tag_cloud+title+{$key}", 'Plugin' => 'tag_cloud'],
                ['Key' => "tag_cloud+h1+{$key}", 'Plugin' => 'tag_cloud'],
                ['Key' => "tag_cloud+des+{$key}", 'Plugin' => 'tag_cloud'],
                ['Key' => "tag_cloud+meta_description+{$key}", 'Plugin' => 'tag_cloud']
            ]);
        } else {
            $rlDb->query("
                DELETE FROM `{db_prefix}lang_keys`
                WHERE `Key` IN ('tag_cloud+title+{$key}','tag_cloud+h1+{$key}','tag_cloud+des+{$key}','tag_cloud+meta_description+{$key}')
            ");
        }

        $_response->script("printMessage('notice', '{$GLOBALS['lang']['item_deleted']}');");
        $_response->script("tagsGrid.reload()");

        $this->updateBox();
        return $_response;
    }

    /**
     * Get list of tags
     * @return array
     */
    public function getTagCloud()
    {
        global $config, $rlDb;

        $limit = $config['tc_limit'];
        $order = '';

        switch ($config['tc_order']) {
            case 'randomly':
                $order = 'ORDER BY RAND()';
                break;
            case 'clicks':
                $order = 'ORDER BY `Count` DESC';
                break;
            case 'date':
                $order = 'ORDER BY `Date` DESC';
                break;
        }

        $tags = (array) $rlDb->fetch('*', ['Status' => 'active'], $order, $limit, 'tag_cloud');

        if ($config['tc_tags_display_type'] != 'as_is') {
            foreach ($tags as $key => $value) {
                switch ($config['tc_tags_display_type']) {
                    case 'ucwords':
                        if (function_exists('mb_convert_case')) {
                            $tags[$key]['Tag'] = mb_convert_case($value['Tag'], MB_CASE_TITLE, 'UTF-8');
                        } else {
                            $tags[$key]['Tag'] = ucwords($value['Tag']);
                        }
                        break;
                    case 'ucfirst':
                        if (function_exists('mb_substr')) {
                            $first_letter = mb_strtoupper(mb_substr($value['Tag'], 0, 1));
                            $tags[$key]['Tag'] = $first_letter . mb_substr($value['Tag'], 1);
                        } else {
                            $tags[$key]['Tag'] = ucfirst($value['Tag']);
                        }
                        break;
                    case 'uppercase':
                        $strtoupper = function_exists('mb_strtoupper') ? 'mb_strtoupper' : 'strtoupper';
                        $tags[$key]['Tag'] = $strtoupper($value['Tag']);
                        break;
                    case 'lowercase':
                        $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
                        $tags[$key]['Tag'] = $strtolower($value['Tag']);
                        break;
                }
            }
        }

        if ($config['tc_box_type'] != 'gradient') {
            $minmax = [];
            foreach ($tags as $tag) {
                $minmax['max'] = $tag['Count'] > $minmax['max'] ? $tag['Count'] : $minmax['max'];
                $minmax['min'] = $tag['Count'] < $minmax['min'] ? $tag['Count'] : $minmax['min'];
            }

            $spread = isset($minmax['min']) && ($minmax['max']) ? $minmax['max'] - $minmax['min'] : 1;
            $step = ($config['tc_maxsize'] - $config['tc_minsize']) / $spread;

            foreach ($tags as $key => $tag) {
                $size = round($config['tc_minsize'] + (($tag['Count'] - $minmax['min']) * $step));
                $tags[$key]['Size'] = $size > $config['tc_maxsize'] ? $config['tc_maxsize'] : $size;
            }
        }

        if ($config['tc_order'] == 'clicks') {
            if ($tags) {
                $tmp = $tags;
                unset($tags);

                foreach ($tmp as $k => $v) {
                    $tags[$v['ID']] = $v;
                }
                ksort($tags);
            }
        } elseif ($config['tc_order'] == 'randomly') {
            shuffle($tags);
        }

        return $tags;
    }

    /**
     * Import tags from string (separated by comma)
     *
     * @param $tags
     * @return xajaxResponse
     */
    public function ajaxImportTags($tags)
    {
        global $_response, $rlDb;

        $tags   = explode(',', $tags);
        $insert = [];

        /* load the utf8 lib */
        loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

        foreach ($tags as $key => $tag) {
            $tag_key = $tag;
            if (!utf8_is_ascii($tag_key)) {
                $tag_key = utf8_to_ascii($tag_key);
            }

            $tag_key = $GLOBALS['rlValid']->str2key($tag_key);
            $tag_path = $GLOBALS['rlValid']->str2path($tag);

            if (!$rlDb->getOne("Key", "`Key` = '{$tag_key}'", "tag_cloud") && !$rlDb->getOne("Key", "`Path` = '{$tag_path}'", "tag_cloud")) {
                $insert['Tag'] = trim($tag);
                $insert['Date'] = 'NOW()';
                $insert['Modified'] = 'NOW()';
                $insert['Key'] = $tag_key;
                $insert['Path'] = $tag_path;
                $this->saveTag($insert);
            }
        }

        if ($insert) {
            $_response->script("printMessage('notice', '{$GLOBALS['lang']['tc_tags_imported']}');");
            $_response->script("tagsGrid.reload()");
            $_response->script("$('#action_blocks div#import').slideUp();");
        }

        $this->updateBox();

        return $_response;
    }

    /**
     * @since 2.0.6
     * @hook  searchSelectArea
     */
    public function hookSearchSelectArea($sql, $f, $fVal)
    {
        if ($fVal['Key'] == 'keyword_search' && $f) {
            $this->searchAddTag($f);
        }
    }

    /**
     * @since 2.0.6
     * @hook  listingsModifyGroupSearch
     */
    public function hookListingsModifyGroupSearch(&$sql)
    {
        if ('tags' === $GLOBALS['page_info']['Key'] && false === strpos($sql, 'GROUP BY')) {
            $sql .= ' GROUP BY `T1`.`ID` ';
        }
    }

    /**
     * @hook  sitemapAddPluginUrls
     * @since 2.0.6
     */
    public function hookSitemapAddPluginUrls(&$urls = [])
    {
        if (!$tags = $GLOBALS['rlDb']->fetch(['Path'], ['Status' => 'active'], null, null, 'tag_cloud')) {
            return;
        }

        $tagsUrls = [];
        foreach ($tags as $tag) {
            $tagUrl = $GLOBALS['reefless']->getPageUrl('tags', ['tag' => $tag['Path']]);

            if ($GLOBALS['config']['mod_rewrite'] && !$GLOBALS['config']['tc_urls_postfix']) {
                $tagUrl = str_replace('.html', '/', $tagUrl);
            }

            $tagsUrls[] = $tagUrl;
        }

        if ($tagsUrls) {
            $urls = array_merge($urls, $tagsUrls);
        }
    }

    /**
     * Update to 2.0.6 version
     */
    public function update206()
    {
        global $rlDb;

        $rlDb->query(
            "DELETE FROM `{db_prefix}hooks`
            WHERE `Name` IN (
                'tplHeader',
                'sitemapAddUrlsInFile',
                'sitemapTotalUrls',
                'sitemapAddNewFile',
                'sitemapAddUrlsCommon'
            )
            AND `Plugin` = 'tag_cloud'"
        );

        $rlDb->query('ALTER TABLE `{db_prefix}tag_cloud` ADD INDEX (`Tag`)');
        $rlDb->query('ALTER TABLE `{db_prefix}tag_cloud` ADD INDEX (`Status`)');
    }

    /**
     * Update to 2.1.0 version
     */
    public function update210()
    {
        global $languages;

        $GLOBALS['rlDb']->query(
            "UPDATE `{db_prefix}blocks` SET `Sticky` = 0,
            `Page_ID` = '11,42,2'
            WHERE `Key` = 'tag_cloud' LIMIT 1"
        );

        if (in_array('ru', array_keys($languages))) {
            $russianTranslation = json_decode(file_get_contents(RL_UPLOAD . 'tag_cloud/i18n/ru.json'), true);
            foreach ([
                'config+name+tc_limit', 'pages+title+tags', 'onfig+name+tc_tag_min_length', 'config+name+tc_order',
                'config+name+tc_urls_postfix', 'config+name+tc_tag_new_page', 'config+name+tc_exwords', 'config+name+tc_highlight_tag',
                'config+name+tc_query_explode', 'config+name+tc_cloud_divider', 'config+name+tc_box_type' ,'config+name+tc_minsize',
                'config+name+tc_maxsize', 'config+name+tc_jquery_circle_height', 'config+name+tc_tags_display_type',
                'config_groups+name+tag_cloud', 'config+des+tc_exwords', 'config+des+tc_jquery_gradient_end',
                'config+des+tc_tags_display_type', 'config+option+tc_order_randomly', 'config+option+tc_order_clicks',
                'config+option+tc_order_date', 'config+option+tc_box_type_simple', 'config+option+tc_box_type_gradient', 'config+option+tc_box_type_circle',
                'config+option+tc_tags_display_type_ucwords', 'config+option+tc_tags_display_type_ucfirst', 'config+option+tc_tags_display_type_uppercase',
                'config+option+tc_tags_display_type_lowercase', 'pages+name+tags',
                'title_tag_cloud', 'config+option+tc_tags_display_type_as_is', 'config+name+tc_tag_min_length', 'config+name+tc_jquery_gradient_end', 'config+name+tc_jquery_gradient_start', 'blocks+name+tag_cloud'] as $phraseKey) {
                $GLOBALS['rlDb']->updateOne([
                    'fields' => ['Value' => $russianTranslation[$phraseKey]],
                    'where'  => ['Key'   => $phraseKey, 'Code' => 'ru'],
                ], 'lang_keys');
            }
        }

    }
    /**
     * Plugin installation method
     *
     * @since 2.0.6
     */
    public function install()
    {
        $GLOBALS['rlDb']->createTable(
            'tag_cloud',
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
             `Tag` VARCHAR(255) CHARACTER SET utf8 NOT NULL,
             `Date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
             `Path` VARCHAR(255) CHARACTER SET utf8 NOT NULL,
             `Type` VARCHAR(50) NOT NULL DEFAULT '',
             `Key` VARCHAR(255) NOT NULL DEFAULT '',
             `Count` INT(9) NOT NULL DEFAULT '0',
             `Modified` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
             `Status` ENUM('active','approval','trash') NOT NULL DEFAULT 'active',
             PRIMARY KEY  (`ID`),
             KEY `Type` (`Type`),
             KEY `Tag` (`Tag`),
             KEY `Status` (`Status`)"
        );
        $GLOBALS['rlDb']->query(
            "UPDATE `{db_prefix}blocks` SET `Sticky` = 0,
            `Page_ID` = '11,42,2'
            WHERE `Key` = 'tag_cloud' LIMIT 1"
        );
    }

    /**
     * @hook phpUrlBottom
     * @since 2.1.0
     */
    public function hookPhpUrlBottom(&$url, $mode, $data, $custom_lang)
    {
        global $config, $reefless;

        if ($this->ownRequest || !$this->tagInfo || !in_array($url, $this->tagPages)) {
            return;
        }

        if ($config['mod_rewrite']) {
            $url = str_replace('.html', '/', $url) . $this->tagInfo['Path'] . '.html';
        } else {
            $url = $url . '&tag=' . $this->tagInfo['Path'];
        }
    }

    /**
     * @deprecated 2.1.4
     */
    public function hookApExtTrashData()
    {}

    /**
     * Plugin uninstalling method
     *
     * @since 2.0.6
     */
    public function uninstall()
    {
        $GLOBALS['rlDb']->dropTable('tag_cloud');
    }

    /*** DEPRECATED DATA ***/

    /**
     * @deprecated 2.0.6
     */
    function getTagsForSitemap($start = false, $limit = false, $languages_count = false)
    {
    }

    /**
     * Update to 2.1.4 version
     */
    public function update214()
    {
        global $rlDb;

        $rlDb->delete(['Key' => 'tag_cloud+meta_keywords+tags_defaults'], 'lang_keys', null, null);
        $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` LIKE 'tag_cloud+meta_keywords+%'");
        $rlDb->delete(['Name' => 'apExtTrashData', 'Plugin' => 'tag_cloud'], 'hooks');

        // Remove all trashed tags
        $rlDb->delete(['Zones' => 'tag_cloud'], 'trash_box', null, null);
        $rlDb->delete(['Status' => 'trash'], 'tag_cloud', null, null);
    }
}
