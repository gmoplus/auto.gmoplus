<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLVERIFICATIONCODE.CLASS.PHP
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

use Flynax\Abstracts\AbstractPlugin;
use Flynax\Interfaces\PluginInterface;
use Flynax\Utils\Valid;

/**
 * Class rlVerificationCode
 */
class rlVerificationCode extends AbstractPlugin implements PluginInterface
{
    /**
     * Path of folder with plugin
     * @since 1.1.0
     */
    public const PLUGIN_DIR = RL_PLUGINS . 'verificationCode/';

    /**
     * Name of plugin table in database
     * @since 1.1.0
     */
    public const TABLE = 'verification_code';

    /**
     * Name of plugin table in database with system prefix
     * @since 1.1.0
     */
    public const TABLE_PRX = '{db_prefix}' . self::TABLE;

    /**
     * updateCodesHook
     */
    public function updateCodesHook(): bool
    {
        global $rlDb;

        $rlDb->rlAllowHTML = true;

        $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.* ";
        $sql .= "FROM `" . self::TABLE_PRX . "` AS `T1` ";
        $sql .= "WHERE `T1`.`Status` = 'active' ";
        $sql .= "ORDER BY `T1`.`Date` DESC";
        $verification_code = $rlDb->getAll($sql);

        $php = str_replace('{codes}', serialize($verification_code), $this->buildTemplateHook());
        $php = str_replace("'", "''", $php);

        $sql = "UPDATE `{db_prefix}hooks` SET `Code` = '{$php}' ";
        $sql .= "WHERE `Name` = 'specialBlock' AND `Plugin` = 'verificationCode' LIMIT 1";

        return (bool) $rlDb->query($sql);
    }

    /**
     * buildTemplateHook
     */
    public function buildTemplateHook(): string
    {
        return <<< FL
        global \$rlDb, \$rlSmarty, \$page_info;

        \$verification_code_header = array();
        \$verification_code_footer = array();

        \$verification_code = <<< VC
        {codes}
VC;

        \$verification_code = unserialize(trim(\$verification_code));

        if ( !empty( \$verification_code ) )
        {
            foreach(\$verification_code as \$key => \$val)
            {
                \$pages_item = !empty(\$val['Pages']) ? explode(",", \$val['Pages']) : array();

                if(\$val['Pages_sticky'] == 1 || in_array(\$page_info['ID'], \$pages_item))
                {
                    if(\$val['Position'] == 'header')
                    {
                        \$verification_code_header[] = \$val;
                    }
                    elseif(\$val['Position'] == 'footer')
                    {
                        \$verification_code_footer[] = \$val;
                    }
                }
            }

            \$GLOBALS['rlSmarty'] -> assign_by_ref( 'verification_code_header', \$verification_code_header );
            \$GLOBALS['rlSmarty'] -> assign_by_ref( 'verification_code_footer', \$verification_code_footer );
        }
FL;
    }

    /**
     * @hook tplHeader
     * @since 1.1.0
     * @return void
     */
    public function hookTplHeader(): void
    {
        $GLOBALS['rlSmarty']->display(self::PLUGIN_DIR . 'tplHeader.tpl');
    }

    /**
     * @hook tplFooter
     * @since 1.1.0
     * @return void
     */
    public function hookTplFooter(): void
    {
        $GLOBALS['rlSmarty']->display(self::PLUGIN_DIR . 'tplFooter.tpl');
    }

    /**
     * @hook apAjaxRequest
     * @since 1.1.0

     * @param $out
     * @param $item
     *
     * @return void
     */
    public function hookApAjaxRequest(&$out = null, $item = null): void
    {
        if ($item === 'deleteVerificationCode') {
            $ids = explode('|', Valid::escape($_REQUEST['ids']));
            $out = ['status' => 'ERROR'];

            if ($ids) {
                foreach ($ids as $id) {
                    $GLOBALS['rlDb']->delete(['ID' => $id], self::TABLE);
                }

                $this->updateCodesHook();

                $out = ['status' => 'OK', 'message' => $GLOBALS['lang']['item_deleted']];
            }
        }
    }

    /**
     * @since 1.1.0
     * @return void
     */
    public function install(): void
    {
        $GLOBALS['rlDb']->createTable(
            self::TABLE,
            "`ID` INT(11) NOT NULL AUTO_INCREMENT,
             `Name` VARCHAR(255) NOT NULL DEFAULT '',
             `Content` MEDIUMTEXT NOT NULL DEFAULT '',
             `Date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
             `Pages` TEXT NOT NULL,
             `Status` ENUM('active','approval','trash') NOT NULL DEFAULT 'active',
             `Position` ENUM('footer','header') NOT NULL DEFAULT 'footer',
             `Pages_sticky` ENUM('0','1') NOT NULL DEFAULT '0',
             PRIMARY KEY (`ID`)",
            RL_DBPREFIX,
            'ENGINE=InnoDB CHARSET=utf8 COLLATE=utf8_general_ci;'
        );

        // Prepare hook for cache
        $GLOBALS['rlDb']->query(
            "UPDATE `{db_prefix}hooks` SET `Class` = ''
             WHERE `Name` = 'specialBlock' AND `Plugin` = 'verificationCode' LIMIT 1;"
        );
    }

    /**
     * @since 1.1.0
     * @return void
     */
    public function uninstall(): void
    {
        $GLOBALS['rlDb']->dropTable(self::TABLE);
    }

    /**
     * @return void
     */
    public function update110(): void
    {
        global $languages, $rlDb;

        if (array_key_exists('en', $languages)) {
            if ($rlDb->getOne('ID', "`Key` = 'description_verificationCode' AND `Code` = 'en'", 'lang_keys')) {
                $rlDb->updateOne([
                    'fields' => ['Value' => 'Adds Meta Tags, JS codes from third-party services to the header or footer of the website'],
                    'where' => ['Key'   => 'description_verificationCode', 'Code' => 'en'],
                ], 'lang_keys');
            }
        }

        if (array_key_exists('ru', $languages)) {
            $russianTranslation = json_decode(file_get_contents(RL_PLUGINS . 'verificationCode/i18n/ru.json'), true);
            foreach ($russianTranslation as $phraseKey => $phrase) {
                if ($rlDb->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $rlDb->updateOne([
                        'fields' => ['Value' => $phrase],
                        'where' => ['Key'   => $phraseKey, 'Code' => 'ru'],
                    ], 'lang_keys');
                } else {
                    $rlDb->insertOne([
                        'Code'   => 'ru',
                        'Module' => 'common',
                        'Key'    => $phraseKey,
                        'Value'  => $phrase,
                        'Plugin' => 'verificationCode',
                    ], 'lang_keys');
                }
            }
        }
    }

    /*** DEPRECATED METHODS ***/

    /**
     * ajaxDeleteItem
     *
     * @deprecated 1.1.0
     *
     * @todo replace with AJAX instead of xAJAX
     *
     * @param int $id - item id
     */
    public function ajaxDeleteItem( $id = false )
    {}
}
