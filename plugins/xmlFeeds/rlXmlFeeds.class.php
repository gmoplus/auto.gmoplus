<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLXMLFEEDS.CLASS.PHP
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

class rlXmlFeeds extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
    * install
    * @since 3.0.0
    */
    public function install()
    {
        global $rlDb;

        $rlDb->createTable('xml_formats',
            "`ID` int(11) NOT NULL auto_increment,
            `Key` varchar(255) NOT NULL default '',
            `Name` varchar(100) NOT NULL default '',
            `Xpath` varchar(255) NOT NULL default '',
            `Status` enum('active','approval') NOT NULL default 'active',
            `Format_for` set('export','import') NOT NULL,
            `New_parser` enum('0','1') NOT NULL DEFAULT '1',
            PRIMARY KEY  (`ID`)"
        );

        $rlDb->createTable('xml_feeds',
            "`ID` int(11) NOT NULL auto_increment,
            `Key` varchar(255) NOT NULL default '',
            `Name` varchar(100) NOT NULL default '',
            `Type` enum('xml','json') NOT NULL DEFAULT 'xml',
            `Url` tinytext DEFAULT '',
            `Format` varchar(255) NOT NULL DEFAULT '',
            `Plan_ID` int(5) NOT NULL DEFAULT 0,
            `Account_ID` int(7) NOT NULL DEFAULT 0,
            `Default_category` int(6) NOT NULL DEFAULT 0,
            `Listings_status` enum('active','approval') NOT NULL default 'active',
            `Removed_ads_action` enum('remove','expire','') NOT NULL default '',
            `Listing_type` varchar(255) NOT NULL DEFAULT '',
            `Lastrun` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `Access_method` enum('direct', 'copy', 'stream') NOT NULL DEFAULT 'copy',
            `Http_auth` enum('0', '1') NOT NULL DEFAULT '0',
            `Http_auth_login` varchar(255) NOT NULL DEFAULT '',
            `Http_auth_pass` varchar(255) NOT NULL DEFAULT '',
            `Skip_imported` enum('0', '1') NOT NULL DEFAULT '0',
            `Update_photos` enum('0', '1') NOT NULL DEFAULT '0',
            `Run` int(11) NOT NULL DEFAULT 0,
            `Import_limit` int(11) NOT NULL DEFAULT 0,
            `Delayed_photos` enum('0', '1') NOT NULL DEFAULT '0',
            `Not_delayed_photos` int(11) NOT NULL DEFAULT 0,
            `Status` enum('active','approval', 'pending') NOT NULL default 'active',
            PRIMARY KEY (`ID`)"
        );

        $rlDb->createTable('xml_statistics',
            "`ID` int(11) NOT NULL auto_increment,
            `Account_ID` int(11) NOT NULL,
            `Feed` varchar(255) NOT NULL default '',
            `Date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `Listings_inserted` int(11) NOT NULL,
            `Listings_updated` int(11) NOT NULL,
            `Listings_deleted` int(11) NOT NULL default '0',
            `Error` varchar(255) NOT NULL default '',
            `Status` enum('completed','error') NOT NULL default 'completed',
            PRIMARY KEY (`ID`),
            KEY `Account_ID` (`Account_ID`),
            KEY `Feed` (`Feed`)"
        );

        $rlDb->createTable('xml_mapping',
            "`ID` int(11) NOT NULL auto_increment,
            `Parent_ID` int(11) NOT NULL,
            `Format` varchar(255) NOT NULL,
            `Data_local` varchar(255) NOT NULL,
            `Data_remote` varchar(255) NOT NULL,
            `Example_value` varchar(255) NOT NULL,
            `Cdata` enum('0','1') NOT NULL default '0',
            `Default` varchar(255) NOT NULL,
            `Status` enum('active','approval') NOT NULL default 'active',
            PRIMARY KEY (`ID`),
            KEY `Parent_ID` (`Parent_ID`),
            KEY `Format` (`Format`),
            KEY `Data_remote` (`Data_remote`)"
        );

        $rlDb->createTable('xml_photos',
            "`ID` int(11) NOT NULL auto_increment,
            `Listing_ID` int(11) NOT NULL,
            `Source` varchar(255) NOT NULL,
            `Status` ENUM('in_progress', 'new') NOT NULL DEFAULT 'new',
            PRIMARY KEY (`ID`),
            KEY `Listing_ID` (`Listing_ID`)"
        );

        $this->validateInnoDB();

        $rlDb->createTable('xml_listings',
            "`Listing_ID` int(11) NOT NULL,
            `xml_ref` VARCHAR(255) NULL DEFAULT '',
            `xml_back_url` VARCHAR(255) NULL DEFAULT '',
            `xml_feed_key` VARCHAR(255) NULL DEFAULT '',
            `xml_last_updated` DATETIME NOT NULL,
            KEY `Listing_ID` (`Listing_ID`),
            CONSTRAINT `xml_listings_fk` FOREIGN KEY (`Listing_ID`)
            REFERENCES `{db_prefix}listings`(`ID`) ON DELETE CASCADE"
        );
    }

    /**
     * Validate `fl_listings` table scheme, change to InnoDB if needs
     *
     * @since 3.6.1
     */
    private function validateInnoDB()
    {
        global $rlDb;

        $data = $rlDb->getRow("SHOW CREATE TABLE `{db_prefix}listings`");
        if (false === strpos($data['Create Table'], 'ENGINE=InnoDB')) {
            $rlDb->query("ALTER TABLE `{db_prefix}listings` ENGINE=InnoDB");
        }
    }

    /**
    * unInstall
    * @since 3.0.0
    */
    public function unInstall()
    {
        global $rlDb;

        $rlDb->dropTables(array('xml_formats', 'xml_feeds', 'xml_statistics', 'xml_mapping', 'xml_photos', 'xml_listings'));
        
        foreach (scandir(RL_CACHE) as $key => $file) {
            preg_match("/^xml_[a-z0-9]{32}$/", $file, $match);
            if ($match[0]) {
                unlink(RL_CACHE.$file);
            }
        }
    }

    /**
    * @hook apTplHeader
    * @since 3.0.0
    */
    public function hookApTplHeader()
    {        
        if ($GLOBALS['controller'] == 'xml_feeds') {
            echo '<link href="' . RL_PLUGINS_URL . 'xmlFeeds/static/style.css" type="text/css" rel="stylesheet" />';
        }    
    }

    /**
    * @hook apTplListingsSearch2
    * @since 3.0.0
    */
    public function hookApTplListingsSearch2()
    {
        echo '<tr><td class="name w130">' . $GLOBALS['lang']['xf_filter_by_feed'] . '</td><td class="field">';
        echo '<select class="filters w200" id="xml_feed_key"><option value="">' . $GLOBALS['lang']['select'] . '</option>';

        foreach ($GLOBALS['xmlfeeds'] as $xk => $xml_feed) {
            echo '<option value="' . $xml_feed['Key'] . '"';
            if ($_GET['feed'] == $xml_feed['Key']) {
                echo 'selected="selected"';
            }
            echo '>' . $xml_feed['Name'] . '</option>';
        }
        echo '</select></td></tr>';
    }

    /**
    * @hook apExtListingsFilters
    * @since 3.0.0
    */
    public function hookApExtListingsFilters()
    {
        $GLOBALS['filters']['f_xml_feed_key'] = true;
    }

    /**
     * @hook apExtListingsSql
     * @since 3.6.1
     */
    public function hookApExtListingsSql()
    {
        global $sql;

        if ($_GET['f_xml_feed_key']) {
            $from = 'FROM `{db_prefix}listings` AS `T1`';
            $join = "LEFT JOIN `{db_prefix}xml_listings` AS `TXML` ON `T1`.`ID` = `TXML`.`Listing_ID`";
            $sql = str_replace($from, sprintf('%s %s', $from, $join), $sql);
            $sql = str_replace('`T1`.`xml_feed_key`', '`TXML`.`xml_feed_key`', $sql);
        }
    }

    /**
    * @hook apTplListingsSearch2
    * @since 3.0.0
    */
    public function hookApTplListingsRemoteFilter()
    {        
        if ($_GET['feed']) {
            echo 'cookies_filters = new Array();';
            echo "cookies_filters[0] = new Array('xml_feed_key', '" . $_GET['feed'] . "');";
            if ($_GET['username']) {
                echo "cookies_filters[1] = new Array('Account', '" . $_GET['username'] . "');";
            }
        }
    }

    /**
    * @hook apPhpListingsBottom
    * @since 3.0.0
    */
    public function hookApPhpListingsBottom()
    {
        global $xmlfeeds;
        $xmlfeeds = $GLOBALS['rlDb']->fetch('*', ['Status' => 'active'], null, null, 'xml_feeds');
    }

    /**
    * @hook cronAdditional
    * @since 3.0.0 
    */
    public function hookCronAdditional()
    {
        foreach (scandir(RL_CACHE) as $key => $file) {
            preg_match("/^xml_[a-z0-9]{32}$/", $file, $match);
            if ($match[0] && filemtime(RL_CACHE . $file) + 6000 > time()) {
                unlink(RL_CACHE . $file);
            }
        }
    }

    /**
    * AjaxDeleteFormat
    *     
    * @param string key - format key    
    */
    function ajaxDeleteFormat($key)
    {
        global $_response, $lang;
        
        if ($GLOBALS['reefless']->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . '/index.php';
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if (!$key) {
            return $_response;
        }

        $GLOBALS['rlValid']->sql($key);

        $sql = "DELETE `T1`,`T2` FROM `{db_prefix}xml_formats` AS `T1` ";
        $sql .="LEFT JOIN `{db_prefix}xml_feeds` AS `T2` ON `T2`.`Format` = `T1`.`Key` ";
        $sql .="WHERE `T1`.`Key` = '{$key}'";
        $GLOBALS['rlDb']->query($sql);

        $_response->script("printMessage('notice', '{$lang['item_deleted']}')");
        $_response->script("xmlFormatsGrid.reload()");

        return $_response;
    }

    /**
    * AjaxDeleteFeed
    *
    * @param string key - feed key
    */
    function ajaxDeleteFeed($key)
    {
        global $_response, $lang;
        
        if ($GLOBALS['reefless']->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if (!$key) {
            return $_response;
        }

        $GLOBALS['rlDb']->delete(['Key' => $key], 'xml_feeds');

        $_response->script("printMessage('notice', '{$lang['item_deleted']}')");
        $_response->script("xmlFeedsGrid.reload()");

        return $_response;
    }

    /**
    * AjaxDeleteUser
    * 
    * @param string id - user id (not account_id)
    */
    function ajaxDeleteUser($id)
    {
        global $_response, $lang;
        
        if ($GLOBALS['reefless']->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $id = (int)$id;
        if (!$id) {
            return $_response;
        }

        $_response->script("printMessage('notice', '{$lang['item_deleted']}')");
        $_response->script("xmlUsersGrid.reload()");

        return $_response;
    }
    
    /**
    * AjaxClearStatistics
    *
    * @param string feed_key - feed key
    */
    function ajaxClearStatistics($feed_key)
    {
        global $_response;

        if (!$feed_key) {
            return $_response;
        }
        
        $GLOBALS['rlValid']->sql($feed_key);

        $sql = "DELETE FROM `{db_prefix}xml_statistics` WHERE `Feed` = '{$feed_key}' ";
        $GLOBALS['rlDb']->query($sql);

        $_response->script("printMessage('notice', '{$GLOBALS['lang']['xf_stats_cleared']}')");
        $_response->script("$('#stats_table').fadeOut()");

        return $_response;
    }

    /**
    * ajax run feed
    *    
    * @param string feed_key - feed key
    * @param string account_id - account_id   
    */
    function ajaxRunFeed($feed_key = false, $account_id = false, $debug_local_field = false, $debug_listing_id = false, $debug_xml_ref = false)
    {
        global $_response;

        $account_id = (int)$account_id;
        $GLOBALS['rlValid']->sql($feed_key);
        $params['feed'] = $feed_key;
        
        if ($debug_local_field) {
            $params['debug_local_field'] = $debug_local_field;
        }

        if ($debug_listing_id) {
            $params['debug_listing_id'] = $debug_listing_id;
        }

        if ($debug_xml_ref) {
            $params['debug_xml_ref'] = $debug_xml_ref;
        }

        if ($account_id) {
            $params['account_id'] = $account_id;
        }

        $GLOBALS['rlSmarty']->assign('params', $params);

        $tpl = RL_PLUGINS . "xmlFeeds" . RL_DS . "admin" . RL_DS . "import_frame.tpl";

        $_response->assign('manual_import_dom', 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));
        $_response->script("$('#manual_import_cont').slideDown()");

        return $_response;
    }

    /**
    * AjaxApplyRule
    *
    * @param string rule - rewrite rule
    */
    function ajaxApplyRule($rule = '')
    {
        global $_response, $lang;

        if (!is_numeric(strpos($rule, '[params]')) || !is_numeric(strpos($rule, '[format]'))) {
            $_response->script("printMessage('error', '" . $lang['xf_format_rule_incorrect'] . "');");
            return $_response;
        }

        $htaccess = RL_ROOT . ".htaccess";
        $htaccess_cont = file_get_contents($htaccess);

        preg_match('/RewriteRule \^([^$]*)\$ plugins\/xmlFeeds\/export.php\?format=\$1\&\$2 \[QSA\,L\]/smi', $htaccess_cont, $match);

        if ($match[0]) {
            $rewrite_cond = $match[1];
            $old_rule = $match[0];

            $new_cond = str_replace(array('[format]','[params]'), array('([^-]*)', '(.*)'), $rule);

            $htaccess_cont = str_replace($rewrite_cond, $new_cond, $htaccess_cont);
            file_put_contents($htaccess, $htaccess_cont);
        } else {
            $pattern = PHP_EOL . "# define paging";

            $new_cond = str_replace(array('[format]', '[params]'), array('([^-]*)', '(.*)'), $rule);
            $rewrite_rule = "RewriteRule ^" . $new_cond . "$ plugins/xmlFeeds/export.php?format=$1&$2 [QSA,L]";

            $replacement = PHP_EOL . $rewrite_rule . PHP_EOL . $pattern;

            $htaccess_cont = str_replace($pattern, $replacement, $htaccess_cont);
            file_put_contents($htaccess, $htaccess_cont);
        }

        $_response->script("$('#apply_rule').val('" . $GLOBALS['lang']['xf_htrule_edit'] . "');");
        $_response->script("$('#rewrited').val(1);");
        
        $_response->script("printMessage('notice', '{$lang['xf_rewrite_success']}' );$('#actual_rewrite').val( '" . RL_URL_HOME . $rule . "' );");
        $_response->script("buildUrl();");

        return $_response;
    }

    /**
    * AjaxAddMappingItem
    *
    * @param $local   - local value
    * @param $remote  - remote value
    * @param $default - default
    **/
    function ajaxAddMappingItem($local = false, $remote = false, $default = false)
    {
        global $_response, $lang, $rlDb;

        if (trim($_GET['field']) == 'category') {
            $parent_id = $rlDb->getOne("ID", "`Data_local` = 'category_0' AND `Format` = '" . $_GET['format'] . "'", "xml_mapping");
        } elseif (is_numeric(strpos($_GET['field'], 'mf|')) && !$_GET['parent']) {
            $parent_id = $rlDb->getOne("ID", "`Data_local` = '" . str_replace('mf|','', $_GET['field']) . "'", "xml_mapping");
        } elseif ($_GET['field'] && !$_GET['parent']) {
            $parent_id = $rlDb->getOne("ID", "`Format` = '" . $_GET['format'] . "' AND `Data_local` = '" . $_GET['field'] . "'", "xml_mapping");
        } elseif ($_GET['parent']) {
            $parent_id = $_GET['parent'];
        } else {
            $parent_id = 0;
        }
        
        $remote = preg_replace('/\s+/', '_', $remote);
        
        if (!$local) {
            $mess = str_replace("{field}", $lang['xf_local_field'], $lang['notice_field_empty']);
            $_response->script("printMessage('error', '{$mess}')");
            $_response->script("$('input[name=item_submit]').val('{$lang['add']}');");

            return $_response;
        } elseif (!$remote && $remote !== '0') {
            $mess = str_replace("{field}", $lang['xf_remote_field'], $lang['notice_field_empty']);
            $_response->script("printMessage('error', '{$mess}')");
            $_response->script("$('input[name=item_submit]').val('{$lang['add']}');");

            return $_response;
        }

        $insert['Parent_ID'] = $parent_id;
        $insert['Format'] = $_GET['format'];
        $insert['Data_remote'] = $remote;
        $insert['Data_local'] = $local;
        $insert['Default'] = $default;

        $ex = $rlDb->fetch('*', $insert, null, null, 'xml_mapping', 'row');
        if ($ex) {
            $_response->script("printMessage('error', '".str_replace("{key}", $local, $lang['notice_field_exist'])."')");
            $_response->script("$('input[name=item_submit]').val('{$lang['add']}');");
            
            return $_response;
        }

        $rlDb->insertOne($insert, 'xml_mapping');

        $_response->script("$('#mapping_item_local').val();$('#mapping_item_remote').val()");
        $_response->script("$('#add_mapping_item').slideUp('normal')");

        $_response->script($_GET['field'] ? 'xmlItemMappingGrid.reload();' : 'xmlMappingGrid.reload();');
        $_response->script("$('input[name=item_submit]').val('{$lang['add']}');");

        return $_response;
    }

    /**
    * Ajax Delete Mapping Item
    *
    * @param mixed $data_remote
    **/
    function ajaxDeleteMappingItem($data_remote = false)
    {
        global $_response, $lang, $key, $rlDb;
        
        if ($GLOBALS['reefless']->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . '/index.php';
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }
        
        if (!$data_remote) {
            return $_response;
        }

        if ($_GET['field']) {
            if (is_numeric(strpos($_GET['field'], 'mf|'))) {
                $mapping_parent = $_GET['parent'] ? $_GET['parent'] : $rlDb->getOne("ID", "`Data_local` = '".str_replace('mf|', '', $_GET['field'])."'", "xml_mapping");
                $item = $rlDb->fetch(array("ID"), array("Data_remote"=>$data_remote, "Parent_ID"=>$mapping_parent), null, null, "xml_mapping", "row");
                $this->deleteMappingItemWithChilds($item['ID']);
            } elseif (is_numeric(strpos($_GET['field'], 'category'))) {
                $mapping_parent = $_GET['parent'] ? $_GET['parent'] : $rlDb->getOne("ID", "`Data_local` = 'sys_category_0' AND `Format` = '{$_GET['format']}'", "xml_mapping");

                $item = $rlDb->fetch(array("ID"), array("Data_remote"=>$data_remote, "Parent_ID"=>$mapping_parent), null, null, "xml_mapping", "row");                
                $this->deleteMappingItemWithChilds($item['ID']);
            } else {
                $parent_id = $rlDb->getOne("ID", "`Data_remote` = '{$_GET['field']}' AND `Format` = '{$_GET['format']}'", "xml_mapping");
                $sql = "DELETE FROM `{db_prefix}xml_mapping` ";
                $sql .="WHERE `Data_remote` = '{$data_remote}' AND `Format` = '{$_GET['format']}'";
                
                $rlDb->query($sql);
            }
        } else {
            $sql = "DELETE `T1`, `T2` FROM `{db_prefix}xml_mapping` AS `T1` ";
            $sql .="LEFT JOIN `{db_prefix}xml_mapping` AS `T2` ON `T2`.`Parent_ID` = `T1`.`ID` ";
            $sql .="WHERE `T1`.`Data_remote` = '{$data_remote}' AND `T1`.`Format` = '{$_GET['format']}' ";

            $rlDb->query($sql);
        }

        $_response->script("printMessage('notice', '{$lang['item_deleted']}')");

        if ($_GET['field']) {
            $_response->script("xmlItemMappingGrid.reload()");
        } else {
            $_response->script("xmlMappingGrid.reload()");
        }

        return $_response;
    }

    /**
    * Adapt Request Field - adapts request field, if system field detects the right parent field
    *
    * @param string $request_field
    **/
    function adaptRequestField($request_field) {
        $system_field = substr($request_field, 0, 4) == 'sys_' ? substr($request_field,4) : false;
        if ($system_field) {
            if ($system_field == 'category') {
                $local_field = 'sys_category_0';
                $category_field = true;
            } else {
                $local_field = $request_field;
            }
        } else {
            $local_field = $request_field;
        }

        return $local_field;
    }

    /**
    * CreateCategory
    *
    * @param string $category_name - category name
    * @param string $parent_id     - parent_id
    **/
    function createCategory($category_name, $parent_id)
    {
        global $rlValid, $languages, $rlDb;

        $category_name = ucfirst(strtolower($category_name));
        if ($parent_id) {
            $parent_info = $rlDb->fetch('*', array('ID' => $parent_id), null, null, 'categories', 'row');
        } else {

            $sql = "SELECT `Listing_type` FROM `{db_prefix}xml_feeds` ";
            $sql .="WHERE `Format` = '{$_GET['format']}'";
            $format_feed_type = $rlDb->getRow($sql, 'Listing_type');

            $parent_id = 0;
        }
        
        $cat_insert['Parent_ID'] = $parent_id;
        $cat_insert['Position'] = $rlDb->getOne("Position", "`Parent_ID` = " . $parent_id . " ORDER BY `Position` DESC", "categories") + 1;
        $cat_insert['Path'] = $parent_info ? $parent_info['Path'] . "/" . $rlValid->str2path($category_name) : $rlValid->str2path($category_name);
        $cat_insert['Level'] = $parent_info['Level']+1;        
        $cat_insert['Parent_IDs'] = $parent_info['Parent_IDs'] ? $parent_info['Parent_IDs'] . "," . $parent_info['Parent_ID'] : $parent_info['Parent_ID'];
        $cat_insert['Type'] = $parent_info['Type'] ? : $format_feed_type;

        $cat_key = $rlValid->str2key($category_name);
        if ($cat_key) {
            while ($ex = $rlDb->getOne("ID", "`Key` ='{$cat_key}'", "categories")) {
                $cat_key = $parent_info['Key'] . "_" . $cat_key;
            }
        }

        $cat_insert['Key'] = $cat_key;
        $cat_insert['Count'] = 1;
        $cat_insert['Status'] = 'active';

        if ($rlDb->insertOne($cat_insert, 'categories')) {
            $category_id = $rlDb->insertID();

            foreach ($languages as $lkey => $lang_item) {
                $lang_insert[$lkey]['Key'] = 'categories+name+' . $cat_key;
                $lang_insert[$lkey]['Value'] = $category_name;
                $lang_insert[$lkey]['Code'] = $lang_item['Code'];
                $lang_insert[$lkey]['Module'] = 'common';
                $lang_insert[$lkey]['Status'] = 'active';
            }

            $rlDb->insert($lang_insert, 'lang_keys');

            return $category_id;
        }
    }

    /**
    * Ajax Copy Mapping Item - copies item to flynax, e.g. category or data format item.
    *
    * @param string $data_remote - data remote
    **/
    function ajaxCopyMappingItem($data_remote = false)
    {
        global $_response, $rlValid, $lang, $rlDb;

        $local_field = $this->adaptRequestField($_GET['field']);

        /* insert category */
        if ($local_field == 'sys_category_0') {
            
            if ($_GET['parent']) {
                $sql = "SELECT `T2`.`ID` FROM `{db_prefix}xml_mapping` AS `T1` ";
                $sql .="LEFT JOIN `{db_prefix}categories` AS `T2` ON `T2`.`Key` = `T1`.`Data_local` ";
                $sql .="WHERE `T1`.`Format` = '" . $_GET['format'] . "' AND `T1`.`ID` = '" . $_GET['parent'] . "' ";
                $parent_cat = $rlDb->getRow($sql, "ID");

                $this->createCategory($data_remote, $parent_cat);

                $sql = "DELETE FROM `{db_prefix}xml_mapping` ";
                $sql .="WHERE `Format` = '" . $_GET['format'] . "' AND `Data_remote` = '" . $data_remote . "' AND `Parent_ID` = " . $_GET['parent'] . " ";
                $rlDb->query($sql);                
            } else {
                $this->createCategory($data_remote, 0);

                $sql = "DELETE FROM `{db_prefix}xml_mapping` ";
                $sql .="WHERE `Format` = '" . $_GET['format'] . "' AND `Data_remote` = '" . $data_remote . "' ";
                $rlDb->query($sql);
            }

        } else {
            $field_info = $rlDb -> fetch("*", array("Key"=>$local_field), null, null, "listing_fields", "row");
            if (!$_GET['parent']) {
                $mapping_parent_id = $rlDb->getOne("ID", "`Format` = '{$_GET['format']}' AND `Data_local` = '{$local_field}'", "xml_mapping");
                $parent_df_key = $field_info['Condition'];
            } else {
                $sql ="SELECT `ID`, `Data_local` FROM `{db_prefix}xml_mapping` WHERE `ID` = '{$_GET['parent']}' ";
                $mapping_parent_info = $rlDb->getRow($sql);
                $mapping_parent_id = $mapping_parent_info['ID'];
                $parent_df_key = $mapping_parent_info['Data_local'];
            }

            if ($field_info['Condition']) {
                $data_format_info = $rlDb->fetch('*', array('Key'=>$parent_df_key), null, null, 'data_formats', 'row');

                $item_insert['Parent_ID'] = $data_format_info['ID'];
                $item_insert['Key'] = $data_format_info['Key'] . '_' . $rlValid->str2key($data_remote);
                $item_insert['Position'] = $rlDb->getOne("Position", "`Parent_ID` = " . $data_format_info['ID'] . " ORDER BY `Position` DESC", "data_formats") + 1;
                $item_insert['Status'] = 'active';

                if ($rlDb->insertOne($item_insert, 'data_formats')) {
                    foreach ($GLOBALS['languages'] as $key => $lang_item) {
                        $lang_keys[] = array(
                            'Code' => $lang_item['Code'],
                            'Module' => 'common',
                            'Key' => 'data_formats+name+'.$item_insert['Key'],
                            'Value' => $data_remote,
                            'Status' => 'active'
                        );
                    }

                    $rlDb->insert($lang_keys, 'lang_keys');
                }
            } else {
                $last_val = end(explode(',', $field_info['Values']));
                $new_val = $last_val+1;

                $sql = "SELECT * FROM `{db_prefix}lang_keys` ";
                $sql .="WHERE `Key` LIKE 'listing_fields+name+{$field_info['Key']}\_%' ";
                $sql .="AND `Value` = '{$data_remote}'";
                $check = $rlDb->getRow($sql);

                if (!$check) {
                    $new_values = $field_info['Values'] . ',' . $new_val;

                    $sql = "UPDATE `{db_prefix}listing_fields` SET `Values` = '{$new_values}' ";
                    $sql .="WHERE `Key` = '{$field_info['Key']}' ";

                    if ($rlDb->query($sql)) {
                        foreach ($GLOBALS['languages'] as $key => $lang_item) {
                            $lang_keys[] = array(
                                'Code' => $lang_item['Code'],
                                'Module' => 'common',
                                'Key' => 'listing_fields+name+' . $field_info['Key'] . "_" . $new_val,
                                'Value' => $data_remote,
                                'Status' => 'active'
                            );
                        }
                        $rlDb->insert($lang_keys, 'lang_keys');
                    }
                }
            }
            
            $sql = "DELETE FROM `{db_prefix}xml_mapping` ";
            $sql .="WHERE `Format` = '{$_GET['format']}' ";
            $sql .="AND `Data_remote` = '{$data_remote}' ";
            $sql .="AND `Parent_ID` = {$mapping_parent_id} ";
            
            $rlDb->query($sql);
        }

        $GLOBALS['rlCache']->update();

        $_response->script("printMessage('notice', '{$lang['item_added']}')");
        $_response->script("xmlItemMappingGrid.reload();");

        return $_response;
    }

    /**
    * Add Format Item
    *
    * @package ajax
    * @param mixed $data - data    
    **/
    function ajaxDeleteXmlFeed($feed_id = false)
    {
        global $_response, $account_info, $rlDb;

        if (defined('IS_LOGIN') && $feed_id) {
            $id = (int)$id;
            $info = $rlDb->fetch(array('ID', 'Account_ID'), array('ID' => $feed_id), null, 1, 'xml_feeds', 'row');

            if ($info['Account_ID'] == $account_info['ID']) {
                $GLOBALS['rlDb']->delete(['ID' => $feed_id], 'xml_feeds');

                $feeds = $rlDb->fetch(array('ID'), array('Account_ID'=>$account_info['ID']), null, 1, 'xml_feeds', 'row');
                if (empty($feeds)) {
                    $_response->script("$('#user_feeds').slideUp();$('#add_feed_cont').slideDown();");
                    
                    $empty_mess = '<div class="info">' . $GLOBALS['lang']['no_saved_search'] . '</div>';//tocheck
                    $_response->assign('saved_search_obj', 'innerHTML', $empty_mess);
                }
                
                $_response->script("$('#item_{$feed_id}').fadeOut('slow');");
                $_response->script("printMessage('notice', '{$lang['notice_item_deleted']}');");
            }
        }

        return $_response;
    }

    /**
    * Clear Mapping
    *
    * @package ajax
    * @param mixed $format - format    
    **/
    function ajaxClearMapping($format = false)
    {
        global $_response, $lang;

        if (!$format) {
            return $_response;
        }

        $sql = "DELETE `T1`, `T2` FROM `{db_prefix}xml_mapping` AS `T1` ";
        $sql .="LEFT JOIN `{db_prefix}xml_mapping` AS `T2` ON `T2`.`Parent_ID` = `T1`.`ID` ";
        $sql .="WHERE `T1`.`Format` = '{$format}' ";

        $GLOBALS['rlDb']->query($sql);

        $_response->script("printMessage('notice', '{$lang['notice_items_deleted']}')");
        $_response->script("xmlMappingGrid.reload()");

        return $_response;
    }

    /**
    * delete mapping with childs
    *
    * @package ajax    
    * @param int $id - id
    **/
    function deleteMappingItemWithChilds($id = false)
    {
        global $rlDb;

        if (!$id) {
            return false;
        }
        
        $sql = "DELETE FROM `{db_prefix}xml_mapping` ";
        $sql .="WHERE `ID` = '{$id}'";
        
        $rlDb->query($sql);

        $childs = $rlDb->fetch(array('ID'), array('Parent_ID' => $id), null, null, 'xml_mapping');
        foreach ($childs as $k => $v) {
            $this->deleteMappingItemWithChilds($v['ID']);
        }
    }

    /**
     * Update to 2.1.0
     */
    public function update210()
    {
        $sql ="ALTER TABLE `" . RL_DBPREFIX . "xml_mapping` ADD `Default` VARCHAR(255) NOT NULL";
        $GLOBALS['rlDb']->query($sql);

        $sql ="ALTER TABLE `" . RL_DBPREFIX . "xml_feeds` ADD `Listing_type` varchar(255) NOT NULL";
        $GLOBALS['rlDb']->query($sql);
    }

    /**
     * Update to 2.1.1
     */
    public function update211()
    {
        $sql ="ALTER TABLE `" . RL_DBPREFIX . "xml_formats` ADD `Format_for` set('export','import') NOT NULL";
        $GLOBALS['rlDb']->query($sql);
    }

    /**
     * Update to 2.2.0
     */
    public function update220()
    {
        global $rlDb;

        $sql ="ALTER TABLE `" . RL_DBPREFIX . "xml_statistics` CHANGE `Listings_inserted` `Listings_inserted` int(11) NOT NULL;";
        $rlDb->query($sql);

        $sql ="ALTER TABLE `" . RL_DBPREFIX . "xml_statistics` CHANGE `Listings_updated` `Listings_updated` int(11) NOT NULL;";
        $rlDb->query($sql);

        if (!$rlDb->getRow("SHOW FIELDS FROM  `" . RL_DBPREFIX . "xml_feeds` WHERE `Field` = 'Lastrun'")) {
            $sql ="ALTER TABLE `" . RL_DBPREFIX . "xml_feeds` ADD `Lastrun` DATETIME NOT NULL ;";
            $rlDb->query($sql);
        }
        if (!$rlDb->getRow("SHOW FIELDS FROM  `" . RL_DBPREFIX . "xml_feeds` WHERE `Field` = 'Access_method'")) {
            $sql ="ALTER TABLE `" . RL_DBPREFIX . "xml_feeds` ADD `Access_method` enum('direct', 'copy', 'stream') NOT NULL DEFAULT 'copy';";
            $rlDb->query($sql);
        }
        if (!$rlDb->getRow("SHOW FIELDS FROM  `" . RL_DBPREFIX . "xml_feeds` WHERE `Field` = 'Http_auth'")) {
            $sql ="ALTER TABLE `".RL_DBPREFIX."xml_feeds` ADD `Http_auth` enum('0', '1') NOT NULL DEFAULT '0';";
            $rlDb->query($sql);
        }
        if (!$rlDb->getRow("SHOW FIELDS FROM  `" . RL_DBPREFIX . "xml_feeds` WHERE `Field` = 'Http_auth_login'")) {
            $sql ="ALTER TABLE `".RL_DBPREFIX."xml_feeds` ADD `Http_auth_login` varchar(255) NOT NULL DEFAULT '';";
            $rlDb->query($sql);
        }
        if (!$rlDb->getRow("SHOW FIELDS FROM  `" . RL_DBPREFIX . "xml_feeds` WHERE `Field` = 'Http_auth_pass'")) {
            $sql ="ALTER TABLE `".RL_DBPREFIX."xml_feeds` ADD `Http_auth_pass` varchar(255) NOT NULL DEFAULT '';";
            $rlDb->query($sql);
        }
        if ($rlDb->getRow("SHOW FIELDS FROM  `" . RL_DBPREFIX . "accounts` WHERE `Field` = 'xml_ref'")) {
            $sql = "ALTER TABLE `" . RL_DBPREFIX . "accounts` DROP `xml_ref`";
            $rlDb->query($sql);
        }
        if ($rlDb->getRow("SHOW FIELDS FROM  `" . RL_DBPREFIX . "accounts` WHERE `Field` = 'xml_feed_key'")) {
            $sql = "ALTER TABLE `".RL_DBPREFIX."accounts` DROP `xml_feed_key`";
            $rlDb->query($sql);
        }
    }

    /**
     * Update to 3.0.0
     */
    public function update300()
    {
        global $rlDb;
        $sql ="DELETE FROM `" . RL_DBPREFIX . "lang_keys` WHERE ";
        $sql .="FIND_IN_SET(`Key`, 'xf_pictures,xf_pictures_ftype,xf_ref_field,xf_ref_ftype,xf_back_url,xf_latitude,xf_longitude,xf_unit,xf_type_system,xf_type_mapping,ext_xf_insert_category')";
        $rlDb->query($sql);

        $sql = "DELETE FROM `" . RL_DBPREFIX . "config` WHERE `Key` = 'xml_show_info' OR `Key` = 'xml_import_categories_automatically' OR `Key` = 'xml_import_df_automatically'";
        $rlDb->query($sql);

        $sql = "TRUNCATE TABLE `" . RL_DBPREFIX . "xml_mapping`";
        $rlDb->query($sql);

        $sql = "ALTER TABLE `" . RL_DBPREFIX . "xml_feeds` DROP `Feed_type`";
        $rlDb->query($sql);

        $sql = "ALTER TABLE `" . RL_DBPREFIX . "xml_feeds` DROP `Feed_account_type`";
        $rlDb->query($sql);

        $sql = "ALTER TABLE `" . RL_DBPREFIX . "xml_feeds` ADD `Skip_imported` enum('0', '1') NOT NULL DEFAULT '0'";
        $rlDb->query($sql);

        $sql = "ALTER TABLE `" . RL_DBPREFIX . "xml_feeds` ADD `Update_photos` enum('0', '1') NOT NULL DEFAULT '0'";
        $rlDb->query($sql);

        $sql = "ALTER TABLE `" . RL_DBPREFIX . "xml_feeds` ADD `Import_limit` int(11) NOT NULL DEFAULT 0";
        $rlDb->query($sql);

        $sql = "ALTER TABLE `" . RL_DBPREFIX . "xml_feeds` ADD `Delayed_photos` enum('0', '1') NOT NULL DEFAULT '0'";
        $rlDb->query($sql);

        $sql = "ALTER TABLE `" . RL_DBPREFIX . "xml_feeds` ADD `Not_delayed_photos` int(11) NOT NULL DEFAULT 0";
        $rlDb->query($sql);

        $sql = "ALTER TABLE `" . RL_DBPREFIX . "xml_feeds` ADD `Run` int(11) NOT NULL DEFAULT 0";
        $rlDb->query($sql);

        $sql ="CREATE TABLE `" . RL_DBPREFIX . "xml_photos` (
            `ID` int(11) NOT NULL auto_increment,
            `Listing_ID` int(11) NOT NULL,
            `Source` varchar(255) NOT NULL,
            PRIMARY KEY (`ID`)
            ) CHARACTER SET utf8 COLLATE utf8_general_ci;";
        $rlDb->query($sql);
    }

    /**
     * Update to 3.0.4
     */
    public function update304()
    {
        global $rlDb;

        $sql = "ALTER TABLE `" . RL_DBPREFIX . "xml_feeds` ADD `Removed_ads_action` enum('remove','expire','') NOT NULL default ''";
        $rlDb->query($sql);

        $sql ="DELETE FROM `" . RL_DBPREFIX . "lang_keys` ";
        $sql .="WHERE `Key` = 'xf_sys_back_url'";
        $rlDb->query($sql);
    }

    /**
     * Update to 3.1.0
     */
    public function update310()
    {
        global $rlDb;

        $sql = "UPDATE `" . RL_DBPREFIX . "lang_keys` SET `Module` = 'ext' WHERE `Key` = 'xf_copy_mapping_item' ";
        $rlDb->query($sql);
    }

    /**
     * Update to 3.2.0
     */
    public function update320()
    {
        $GLOBALS['rlDb']->addColumnToTable("Status", "ENUM('in_progress', 'new') NOT NULL DEFAULT 'new'", "xml_photos");
    }

    /**
     * Update to 3.3.0
     */
    public function update330()
    {
        global $rlDb;

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
            WHERE FIND_IN_SET(`Key`, 'xf_handler,xf_handler_hint,xf_error_missing_handler')"
        );

        $rlDb->updateOne(
            array(
                'fields' => array('Module' => 'common'),
                'where'  => array('Key'    => 'xf_notice_url_exist')
            ),
            'lang_keys'
        );

        $rlDb->dropColumnFromTable('Handler', 'xml_feeds');

        $rlDb->query(
            "ALTER TABLE `{db_prefix}xml_feeds`
            CHANGE `Status` `Status` ENUM('active','approval', 'pending') NOT NULL default 'active'"
        );
    }

    /**
     * Update to 3.4.0
     */
    public function update340()
    {
        global $rlDb;

        $rlDb->query("ALTER TABLE `{db_prefix}xml_mapping` ADD INDEX(`Parent_ID`)");
        $rlDb->query("ALTER TABLE `{db_prefix}xml_mapping` ADD INDEX(`Format`)");
        $rlDb->query("ALTER TABLE `{db_prefix}xml_mapping` ADD INDEX(`Data_remote`)");

        $rlDb->query("ALTER TABLE `{db_prefix}xml_statistics` ADD INDEX(`Account_ID`)");
        $rlDb->query("ALTER TABLE `{db_prefix}xml_statistics` ADD INDEX(`Feed`)");

        $rlDb->query("ALTER TABLE `{db_prefix}xml_photos` ADD INDEX(`Listing_ID`)");

        $rlDb->dropColumnFromTable('Parse_method', 'xml_feeds');

        $phrases = [
            'xf_parse_method',
            'xf_parse_method_1',
            'xf_parse_method_2',
            'xf_progress_map_item_added_mf',
            'xf_progress_map_item_not_mapped_mf'
        ];
        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
            WHERE `Plugin` = 'xmlFeeds'
            AND `Key` IN ('" . implode("','", $phrases) . "')
        ");

        $rlDb->addColumnToTable('New_parser', "ENUM('0','1') NOT NULL DEFAULT '1'", "xml_formats");
        $rlDb->query("UPDATE `{db_prefix}xml_formats` SET `New_parser` = '0'");

        $rlDb->addColumnToTable('Type', "ENUM('xml','json') NOT NULL DEFAULT 'xml' AFTER `Key`", 'xml_feeds');
    }

    /**
     * Update to 3.5.0
     */
    public function update350()
    {
        global $rlDb, $config;

        $rlDb->addColumnToTable('Name', "VARCHAR(100) NOT NULL AFTER `Key`", "xml_feeds");
        $rlDb->addColumnToTable('Name', "VARCHAR(100) NOT NULL AFTER `Key`", "xml_formats");

        $rlDb->setTable('xml_feeds');
        foreach ($rlDb->fetch('Key') as $feed) {
            $name = $rlDb->getOne('Value', "`Code` = '{$config['lang']}' AND `Key` = 'xml_feeds+name+{$feed['Key']}'", 'lang_keys');
            $rlDb->query("UPDATE `{db_prefix}xml_feeds` SET `Name` = '{$name}' WHERE `Key` = '{$feed['Key']}'");
        }

        $rlDb->setTable('xml_formats');
        foreach ($rlDb->fetch('Key') as $format) {
            $name = $rlDb->getOne('Value', "`Code` = '{$config['lang']}' AND `Key` = 'xml_formats+name+{$format['Key']}'", 'lang_keys');
            $rlDb->query("UPDATE `{db_prefix}xml_formats` SET `Name` = '{$name}' WHERE `Key` = '{$format['Key']}'");
        }

        $sql = "
            DELETE FROM `{db_prefix}lang_keys`
            WHERE `Key` LIKE 'xml_formats+name%' OR `Key` LIKE 'xml_feeds+name%'
        ";
        $rlDb->query($sql);

        $phrases = [
            'xf_feed_name',
            'xf_listing_types',
            'xf_format_name',
            'xf_mapping_of_format',
            'xf_mapping_of_field',
            'xf_stats_bc',
            'xf_submit_feed',
            'xf_add_user',
            'xf_edit_user',
            'xf_run',
            'xf_stats_account',
            'xf_export_url',
            'xf_plan',
            'xf_username',
            'xf_notice_remove_feed',
            'xf_notice_remove_item',
            'xf_default',
            'xf_auth_login',
            'xf_auth_password',
        ];
        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
            WHERE `Plugin` = 'xmlFeeds'
            AND `Key` IN ('" . implode("','", $phrases) . "')
        ");
    }

    /**
     * Update to 3.6.1
     */
    public function update361()
    {
        global $rlDb;

        $this->validateInnoDB();

        $rlDb->createTable('xml_listings',
            "`Listing_ID` int(11) NOT NULL,
            `xml_ref` VARCHAR(255) NULL DEFAULT '',
            `xml_back_url` VARCHAR(255) NULL DEFAULT '',
            `xml_feed_key` VARCHAR(255) NULL DEFAULT '',
            `xml_last_updated` DATETIME NOT NULL,
            KEY `Listing_ID` (`Listing_ID`),
            CONSTRAINT `xml_listings_fk` FOREIGN KEY (`Listing_ID`)
            REFERENCES `{db_prefix}listings`(`ID`) ON DELETE CASCADE"
        );

        $sql = "
            INSERT INTO `{db_prefix}xml_listings`
            (SELECT `ID` AS `Listing_ID`, `xml_ref`, `xml_back_url`, `xml_feed_key`, `xml_last_updated`
            FROM `{db_prefix}listings` WHERE `xml_feed_key` != '')
        ";
        $rlDb->query($sql);

        $rlDb->dropColumnsFromTable(array('xml_ref', 'xml_feed_key', 'xml_back_url', 'xml_last_updated'), 'listings');

        $sql ="DELETE FROM `{db_prefix}listing_fields` WHERE `Key` = 'xml_ref'";
        $rlDb->query($sql);

        $sql ="DELETE FROM `{db_prefix}listing_fields` WHERE `Key` = 'xml_back_url'";
        $rlDb->query($sql);

        $sql ="DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'listing_fields+name+xml_ref' OR `Key` = 'listing_fields+name+xml_back_url'";
        $rlDb->query($sql);

        $sql ="UPDATE `{db_prefix}xml_mapping` SET `Data_local` = 'sys_xml_ref' WHERE `Data_local` = 'xml_ref'";
        $rlDb->query($sql);

        $sql ="UPDATE `{db_prefix}xml_mapping` SET `Data_local` = 'sys_xml_back_url' WHERE `Data_local` = 'xml_back_url'";
        $rlDb->query($sql);

        if (in_array('ru', array_keys($GLOBALS['languages']))) {
            $russianTranslation = json_decode(file_get_contents(RL_PLUGINS . 'xmlFeeds/i18n/ru.json'), true);

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
                    $where = ['Key' => $phraseKey, 'Code' => 'ru'];
                    if (version_compare($GLOBALS['config']['rl_version'], '4.8.1', '>=')) {
                        $where['Modified'] = '0';
                    }
                    $rlDb->updateOne([
                        'fields' => ['Value' => $phraseValue],
                        'where' => $where,
                    ], 'lang_keys');
                }
            }
        }

    }
}
