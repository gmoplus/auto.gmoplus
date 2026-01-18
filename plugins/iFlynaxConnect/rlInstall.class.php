<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLINSTALL.CLASS.PHP
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

if (false === class_exists('rlIFlynaxConnect')) {
    require_once dirname(__FILE__) . RL_DS . 'rlIFlynaxConnect.class.php';
}

class rlInstall
{
    /**
     * @deprecated 3.7.4
     */
    private $lastUpdate = 0;

    /**
     * @deprecated 3.7.4
     */
    private $currentUpdate = 0;

    public function install()
    {
        define('INSTALLATION_PROCESS', true);

        $this->update('3.0.0');
        $this->update('3.1.0');
        $this->update('3.1.1');
        $this->update('3.2.0');
        $this->update('3.7.0');

        $this->synchronizeAppLanguages(true);
    }

    public function update($version)
    {
        $version             = intval(str_replace('.', '', $version));
        $versionMethod       = 'update_' . $version;

        try {
            if (false === method_exists($this, $versionMethod)) {
                throw new BadMethodCallException(sprintf('Undefined method rlInstall::%s', $versionMethod));
            }
            $this->$versionMethod();
        }
        catch (Exception $e) {
            $log = sprintf('%s thrown within the exception: "%s"', 'iFlynaxConnect', $e->getMessage());
            $GLOBALS['rlDebug']->logger($log);
        }
    }

    public function uninstall()
    {
        $this->dropColumnFromTable('ios_token', 'accounts');
        $this->dropColumnFromTable('iFlynax_icon', 'listing_types');
        $this->dropColumnFromTable('iFlynax_status', 'listing_types');
        $this->dropColumnFromTable('iFlynax_position', 'listing_types');

        $this->dropTable('iflynax_languages');
        $this->dropTable('iflynax_phrases');
        $this->dropTable('iflynax_push_tokens');
        $this->dropTable('iflynax_admob');

        $this->dbQuery("DELETE FROM `{db_prefix}admin_controllers` WHERE `Key` LIKE 'iflynax%'");

        $this->removeAdminSection();
    }

    /*** Updates ***/

    /**
     * @version 3.0.0
     **/
    private function update_300()
    {
        global $rlDb;

        $this->addColumnsToTable(
            array('ios_token' => 'CHAR(32) NOT NULL'),
            'accounts'
        );

        $this->addColumnsToTable(
            array(
                'iFlynax_icon' => 'VARCHAR(20) NOT NULL AFTER `Status`',
                'iFlynax_status' => "ENUM('active', 'approval') NOT NULL DEFAULT 'active' AFTER `iFlynax_icon`",
                'iFlynax_position' => 'INT(3) NOT NULL AFTER `iFlynax_status`',
            ),
            'listing_types'
        );

        $this->dbQuery("
            UPDATE `{db_prefix}listing_types` SET `iFlynax_status` = `Status`,`iFlynax_position` = `Order`
        ");

        $this->dbQuery("
        CREATE TABLE IF NOT EXISTS `{db_prefix}iflynax_languages` (
          `ID` INT(3) NOT NULL AUTO_INCREMENT,
          `Code` VARCHAR(2) CHARACTER SET utf8 NOT NULL,
          `Direction` ENUM('ltr', 'rtl') NOT NULL DEFAULT 'ltr',
          `Key` VARCHAR(15) CHARACTER SET utf8 NOT NULL,
          `Status` ENUM('active', 'approval') NOT NULL DEFAULT 'active',
          `Date_format` VARCHAR(25) CHARACTER SET utf8 NOT NULL,
          PRIMARY KEY (`ID`),
          INDEX (`Status`)
        ) CHARSET=utf8
        ");

        $this->dbQuery("
            INSERT INTO `{db_prefix}iflynax_languages` (`Code`, `Direction`, `Key`, `Status`, `Date_format`) VALUES 
            ('en', 'ltr', 'english', 'active', '%b %d,%Y')
        ");

        $this->dbQuery("
        CREATE TABLE IF NOT EXISTS `{db_prefix}iflynax_phrases` (
          `ID` INT(9) NOT NULL AUTO_INCREMENT,
          `Code` VARCHAR(2) CHARACTER SET utf8 NOT NULL,
          `Key` VARCHAR(128) CHARACTER SET utf8 NOT NULL,
          `Value` MEDIUMTEXT CHARACTER SET utf8 NOT NULL,
          PRIMARY KEY (`ID`),
          INDEX (`Code`)
        ) CHARSET=utf8 
        ");

        $this->dbQuery("
        CREATE TABLE IF NOT EXISTS `{db_prefix}iflynax_push_tokens` (
          `ID` INT(11) NOT NULL AUTO_INCREMENT,
          `Token` VARCHAR(72) NOT NULL,
          `Account_ID` INT(6) NOT NULL DEFAULT '0',
          `Status` enum('active','inactive') NOT NULL DEFAULT 'active',
          PRIMARY KEY (`ID`),
          INDEX (`Account_ID`, `Status`)
        ) CHARSET=utf8
        ");

        // add menu group
        $sql = "SELECT MAX(`Position`) AS `Max` FROM `" . RL_DBPREFIX . "admin_controllers` WHERE `Parent_ID` = 0";
        $pos = $rlDb->getRow($sql);
        $pos = intval($pos['Max'] + 1);

        $this->dbQuery("
            INSERT INTO `{db_prefix}admin_controllers` (`Parent_ID`, `Position`, `Key`) VALUES 
            (0, {$pos}, 'iflynax')
        ");

        $section_id = method_exists($rlDb, 'insertID') ? $rlDb->insertID() : mysql_insert_id();

        $this->dbQuery("
            INSERT INTO `{db_prefix}config` (`Group_ID`, `Key`, `Default`, `Plugin`) VALUES
            (0, 'iflynax_admin_section_id', '{$section_id}', 'iFlynaxConnect'),
            (0, 'iflynax_lang', 'en', 'iFlynaxConnect'),
            (0, 'app_version', '3.0.0', 'iFlynaxConnect')
        ");

        // required to support legacy app's
        $this->dbQuery("
            UPDATE `{db_prefix}config` SET `Plugin` = 'iFlynaxConnect_old' 
            WHERE `Key` LIKE 'iFlynaxConnect_%'
        ");

        $GLOBALS['config']['iflynax_admin_section_id'] = $section_id;

        $this->dbQuery("
            INSERT INTO `{db_prefix}admin_controllers` (`Parent_ID`, `Position`, `Key`, `Controller`, `Vars`) VALUES 
            ({$section_id}, 1, 'iflynax_languages', 'iflynax_languages', ''),
            ({$section_id}, 2, 'iflynax_settings', 'iflynax_settings', ''),
            ({$section_id}, 3, 'iflynax_email_templates', 'email_templates', 'module=ios_app'),
            ({$section_id}, 4, 'iflynax_listing_types', 'iflynax_listing_types', '')
        ");

        $this->synchronizeAppLanguages();
        $this->addAdminSection();
    }

    /**
     * @version 3.1.0
     **/
    private function update_310()
    {
        $this->dbQuery("
            DELETE FROM `{db_prefix}hooks` WHERE `Name` = 'confirmPreConfirm' AND `Plugin` = 'iFlynaxConnect'
        ");

        $this->dbQuery("
        CREATE TABLE IF NOT EXISTS `{db_prefix}iflynax_admob` (
            `ID` INT(50) NOT NULL AUTO_INCREMENT,
            `Name` VARCHAR(50) NOT NULL,
            `Pages` TEXT NOT NULL,
            `Side` VARCHAR(50) NOT NULL,
            `Code` VARCHAR(100) NOT NULL,
            `Status` enum('active','approval') NOT NULL,
            PRIMARY KEY (`ID`)
        ) CHARSET=utf8
        ");

        if (0 !== $section_id = intval($GLOBALS['config']['iflynax_admin_section_id'])) {
            $this->dbQuery("
                INSERT INTO `{db_prefix}admin_controllers` (`Parent_ID`, `Position`, `Key`, `Controller`) VALUES 
                ({$section_id}, '5', 'iflynax_admob', 'iflynax_admob')
            ");
        }

        $plugin_id = intval($GLOBALS['rlDb']->getOne('ID', "`Key` = 'iFlynaxConnect'", 'plugins'));

        $this->dbQuery("
            INSERT INTO `{db_prefix}config` (`Group_ID`, `Key`, `Default`, `Plugin`) VALUES 
            (0, 'iflynax_plugin_status', 'active', 'iFlynaxConnect'),
            (0, 'iflynax_plugin_id', '{$plugin_id}', 'iFlynaxConnect')
        ");

        $this->synchronizeAppLanguages();
        $this->appendAdMobMenuItemToSection();

        $this->dbQuery("
            ALTER TABLE `{db_prefix}iflynax_push_tokens` CHANGE `Token` `Token` VARCHAR(72) NOT NULL
        ");
    }

    /**
     * @version 3.1.1
     **/
    private function update_311()
    {
        $this->addColumnsToTable(
            array('Language' => 'CHAR(2) CHARACTER SET utf8 NOT NULL AFTER `Token`'),
            'iflynax_push_tokens'
        );
    }

    /**
     * @version 3.2.0
     **/
    private function update_320()
    {
        $ltype_key = $GLOBALS['rlDb']->getOne('Key', "`iFlynax_status` = 'active'", 'listing_types');
        $update_config_position = !defined('INSTALLATION_PROCESS') ? ',`Position` = 3' : '';

        $this->dbQuery("
            UPDATE `{db_prefix}config` SET `Default` = '{$ltype_key}' {$update_config_position} 
            WHERE `Key` = 'iflynax_home_featured_ltype'
        ");

        if (defined('INSTALLATION_PROCESS')) {
            $this->dbQuery("
                INSERT INTO `{db_prefix}iflynax_languages` (`Code`, `Direction`, `Key`, `Status`, `Date_format`) VALUES 
                ('ar', 'rtl', 'arabic', 'approval', '%d.%m.%Y'),
                ('tr', 'ltr', 'turkish', 'approval', '%d.%m.%Y')
            ");
        }

        $this->dbQuery("
            UPDATE `{db_prefix}listing_types` SET `iFlynax_icon` = `Key`
            WHERE `iFlynax_icon` = '' AND `Key` IN (
                'auto_parts','auto','boat_staff','boats','job','listings','med_service','pets','property','services'
            )
        ");

        $this->synchronizeAppLanguages();
    }

    /**
     * @version 3.4.0
     */
    private function update_340()
    {
        foreach (array('config', 'lang_keys') as $table) {
            $GLOBALS['rlDb']->query("
                DELETE FROM `{db_prefix}{$table}` 
                WHERE `Key` LIKE '%_paypal%' AND `Plugin` = 'iFlynaxConnect'
            ");
        }
        @unlink(__DIR__ . '/gateways/paypal.gateway.php');
    }

    /**
     * @version 3.5.0
     */
    private function update_350()
    {
        $this->synchronizeAppLanguages();
    }

    /**
     * @version 3.6.0
     */
    private function update_360()
    {
        global $rlDb;

        $rlDb->query("
            DELETE FROM `{db_prefix}hooks` 
            WHERE `Plugin` = 'iFlynaxConnect' AND `Name` IN(
                'phpUpdatePhotoDataSetFields',
                'apTplControlsForm'
            )
        ");

        $rlDb->query("
            DELETE FROM `{db_prefix}lang_keys` 
            WHERE `Plugin` = 'iFlynaxConnect' AND `Key` LIKE 'iflynax\_controls\_%'
        ");

        @unlink(__DIR__ . '/admin/resizeEntry.tpl');

        $this->synchronizeAppLanguages();
    }

    /**
     * @version 3.7.0
     */
    private function update_370()
    {
        $this->dbQuery("
            INSERT INTO `{db_prefix}iflynax_languages` (`Code`, `Direction`, `Key`, `Status`, `Date_format`) VALUES 
            ('ru', 'ltr', 'russian', 'approval', '%d.%m.%Y');
        ");

        $this->synchronizeAppLanguages();
    }

    private function update_373()
    {
        $this->dbQuery("
            UPDATE `{db_prefix}config` SET `Position` = '1'
            WHERE `Key` = 'iflynax_bundle_identifier' LIMIT 1
        ");
    }

    private function update_374()
    {
        $GLOBALS['rlDb']->setTable('iflynax_phrases');
        if (!$GLOBALS['rlDb']->fetch('*')) {
            $this->synchronizeAppLanguages();
        }
    }
    /** Helpfull methods to manage the plugin **/

    private function dbQuery($sql)
    {
        $sql = str_replace('{db_prefix}', RL_DBPREFIX, $sql);

        $GLOBALS['rlDb']->query($sql);
    }

    private function columnExists($column, $table)
    {
        $column = $GLOBALS['rlDb']->getRow("SHOW COLUMNS FROM  `" . RL_DBPREFIX . "{$table}` LIKE  '{$column}'");
        return !empty($column);
    }

    private function dropColumnFromTable($column, $table)
    {
        if (true === $this->columnExists($column, $table)) {
            $this->dbQuery("ALTER TABLE `{db_prefix}{$table}` DROP `{$column}`");
        }
    }

    private function addColumnsToTable($columns, $table)
    {
        $alter_fields = array();
        foreach ($columns as $field => $field_params_sql) {
            if (false === $this->columnExists($field, $table, $prefix)) {
                $alter_fields[] = "ADD `{$field}` {$field_params_sql}";
            }
        }

        if (count($alter_fields)) {
            $this->dbQuery("ALTER TABLE `{db_prefix}{$table}` " . implode(', ', $alter_fields));
        }
    }

    private function dropTable($table)
    {
        $this->dbQuery("DROP TABLE IF EXISTS `{db_prefix}{$table}`");
    }

    /**
     * Synchronize/install application languages/phrases
     *
     * @since 3.7.4 - $force parameter added
     *
     * @param  boolean $force - Force languages/phrases installation
     */
    public function synchronizeAppLanguages($force = false)
    {
        if (defined('INSTALLATION_PROCESS') && !$force) {
            return;
        }

        $sql = "SELECT GROUP_CONCAT(`Code`) AS `row` FROM `" . RL_DBPREFIX . "iflynax_languages`";
        $entry = $GLOBALS['rlDb']->getRow($sql);
        $iflynax_languages = explode(',', $entry['row']);

        $language_xml_files = array();
        foreach (glob(__DIR__ . '/languages/*.xml') as $filename) {
            if (preg_match('/[A-Z]{2}/', $filename, $matches)) {
                $file_code = strtolower($matches[0]);

                if (in_array($file_code, $iflynax_languages)) {
                    $language_xml_files[$file_code] = $filename;
                }
            }
        }

        if (empty($language_xml_files)) {
            return;
        }
        $insert = array();

        foreach ($language_xml_files as $lang_code => $file) {
            if (!is_readable($file)) {
                continue;
            }

            $doc = new DOMDocument();
            $doc->load($file);
            $phrases = $doc->getElementsByTagName('phrase');

            foreach ($phrases as $phrase) {
                $phrase_key = $phrase->getAttribute('key');
                $_where = sprintf("`Code` = '%s' AND `Key` = '%s'", $lang_code, $phrase_key);
                $exists = (bool) $GLOBALS['rlDb']->getOne('ID', $_where, 'iflynax_phrases');

                if (false === $exists) {
                    $insert[] = array(
                        'Code' => $lang_code,
                        'Key' => $phrase_key,
                        'Value' => strval($phrase->textContent),
                    );
                }
            }
            unset($phrases);
        }

        if (!empty($insert)) {
            $GLOBALS['reefless']->loadClass('Actions');
            $GLOBALS['rlActions']->insert($insert, 'iflynax_phrases');
        }
    }

    public function addAdminSection()
    {
        global $_response, $lang, $config;

        // Get all new plugin phrases
        if (!$lang['admin_controllers+name+iflynax']) {
            $sql = "SELECT `Key`, `Value` FROM `{db_prefix}lang_keys` ";
            $sql .= "WHERE `Plugin` = 'iFlynaxConnect' AND `Code` = '" . RL_LANG_CODE . "' ";
            $pluginPhrases = (array) $GLOBALS['rlDb']->getAll($sql, ['Key', 'Value']);

            $lang = array_merge($lang, $pluginPhrases);
        }

        $url = RL_URL_HOME . ADMIN . '/';

        $controllers = [
            ['controller' => 'iflynax_languages', 'name' => $lang['admin_controllers+name+iflynax_languages']],
            ['controller' => 'iflynax_settings', 'name' => $lang['admin_controllers+name+iflynax_settings']],
            ['controller' => 'email_templates', 'name' => $lang['admin_controllers+name+iflynax_email_templates']],
            ['controller' => 'iflynax_listing_types', 'name' => $lang['admin_controllers+name+iflynax_listing_types']],
        ];

        $_response->script("
            apMenu['iflynax'] = {
                section_name : '{$lang['admin_controllers+name+iflynax']}'
            }
        ");

        $plugins_url = RL_PLUGINS_URL;

        $menu_full = <<<VS
            <div id="msection_{$config['iflynax_admin_section_id']}">\
                <div class="caption" id="lb_status_{$config['iflynax_admin_section_id']}">\
                    <div class="icon" style="background: url({$plugins_url}iFlynaxConnect/static/gallery.png) 3px 0 no-repeat!important;"></div>\
                    <div class="name">{$lang['admin_controllers+name+iflynax']}</div>\
                </div>\
                \
                <div class="ms_container clear" id="lblock_{$config['iflynax_admin_section_id']}">\
                    <div id="iflynax_section" class="section">
VS;
        foreach ($controllers as $controller) {
            $menu_full .= <<<VS
                <div class="mitem">\
                    <a href="{$url}index.php?controller={$controller['controller']}">{$controller['name']}</a>\
                </div>
VS;
            $_response->script("
                apMenu['iflynax'][{$controller['controller']}] = {
                    Controller : '{$controller['controller']}',
                    Name       : '{$controller['name']}',
                    Vars       : ''
                }
            ");
        }

        $menu_full .= <<<VS
                    </div>\
                </div>\
            </div>
VS;

        $_response->script("
            $('#mmenu_full').append('{$menu_full}');
        ");
    }

    public function appendAdMobMenuItemToSection()
    {
        $GLOBALS['_response']->script("
            apMenu['iflynax']['iflynax_admob'] = {
                Controller : 'iflynax_admob',
                Name       : 'Google AdMob',
                Vars       : ''
            }

            $('div#iflynax_section').append(
                $('<div/>', {class:'mitem'}).append($('<a/>', {
                    href: rlConfig['tpl_base'] + 'index.php?controller=iflynax_admob',
                    html: 'Google AdMob'}))
            )
        ");
    }

    public function removeAdminSection()
    {
        $section_id = intval($GLOBALS['config']['iflynax_admin_section_id']);
        $GLOBALS['_response']->script(sprintf("$('#msection_%d').remove();", $section_id));
    }
}
