<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: PAYPALREST.GATEWAY.PHP
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

require_once __DIR__ . '/AdsFilter.php';

use Flynax\Utils\Category;
use Flynax\Utils\Util;
use Flynax\Utils\Valid;

class rlAndroidConnect extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * custom output flag
     **/
    public $custom_output = false;

    /**
     * listing types available for app
     **/
    public $types;

    /**
     * deflate response (using gzip)
     **/
    public $response_deflate = false;

    /**
     * price field key
     **/
    public $price_key = 'price';

    /**
     * system account email field key
     **/
    public $system_email_key = 'account_email';

    /**
     * listings grid limit
     **/
    public $grid_listings_limit = 10;

    /**
     * featured listings grid limit
     **/
    public $grid_featured_limit = 20;

    /**
     * featured listings grid limit | tablet mode
     **/
    public $grid_featured_limit_tablet = 40;

    /**
     * map capture zoom on home page
     **/
    public $home_map_host_zoom = 16; //0-21

    /**
     * zip code field numeric input type - if true then use numbers only else use numbers and letters
     **/
    public $zip_numeric_input_type = true;

    /**
     * listing transfer fields
     **/
    public $transfer_listings_grid_fields = array('ID', 'Main_photo', 'Listing_type', 'Photos_count', 'Featured');

    /**
     * my listing transfer fields
     **/
    public $transfer_my_listings_grid_fields = array('ID', 'photo', 'Listing_type', 'Photos_count', 'Status', 'Last_step', 'Last_type', 'title', 'Sub_status',
        'Plan_expire', 'Date', 'Pay_date', 'Featured_expire', 'Featured_date', 'category_name', 'Category_ID', 'plan_name',
        'Plan_real_key', 'Shows', 'Plan_type', 'Plan_ID', 'Plan_price');

    /**
     * account transfer fields
     **/
    public $transfer_account_grid_fields = array('ID', 'Photo', 'Full_name', 'Date', 'Listings_count');

    /**
     * sorting transfer fields
     **/
    public $transfer_sorting_fields = array('Key', 'Type', 'name');

    /**
     * "year build" field key
     **/
    public $year_build_key = 'built';

    /**
     * "year build" field key
     **/
    public $age_key = 'age';

    /**
     * main listing type key
     **/
    public $main_listing_type;

    /**
     * account types
     **/
    public $account_types;

    /**
     * youtube thumbnail url
     **/
    public $youtube_thumbnail_url = 'https://img.youtube.com/vi/{key}/mqdefault.jpg';

    /**
     * youtube preview url (add/edit listing mode)
     **/
    public $youtube_preview_url = 'https://i.ytimg.com/vi/{id}/0.jpg';

    /**
     * class constructor
     **/
    public function __construct()
    {
        global $response_deflate, $item, $config;

        if ($GLOBALS['plugins']['androidConnect'] && $item != 'isPluginAvailable') {
            $this->getListingTypes();

            $this->grid_listings_limit = $config['android_grid_listings_number'];
            if ($config['android_main_listing_type']) {
                $this->main_listing_type = $config['android_main_listing_type'];
            } else {
                reset($this->types);
                $type = current($this->types);
                $this->main_listing_type = $type['Key'];
            }
        }

        if ($response_deflate) {
            $this->response_deflate = true;
        }
    }

    /**
     * Install process
     *
     * @since 4.0.0
     */
    public function install()
    {
        global $reefless, $config, $rlDb;

        $sql = "ALTER TABLE `" . RL_DBPREFIX . "listing_types`
                ADD `Android_icon` VARCHAR(20) NOT NULL AFTER `Status`,
                ADD `Android_status` ENUM( 'active', 'approval' ) NOT NULL DEFAULT 'active' AFTER `Android_icon`,
                ADD `Android_position` INT( 3 ) NOT NULL AFTER `Android_status`";
        $rlDb->query($sql);

        $sql = "UPDATE `" . RL_DBPREFIX . "listing_types` SET `Android_status` = `Status`, `Android_position` = `Order`";
        $rlDb->query($sql);

        // create android language table
        $sql_create = "`ID` INT( 3 ) NOT NULL AUTO_INCREMENT ,
            `Code` VARCHAR( 2 ) CHARACTER SET utf8 NOT NULL ,
            `Direction` ENUM( 'ltr', 'rtl' ) NOT NULL DEFAULT 'ltr',
            `Key` VARCHAR( 15 ) CHARACTER SET utf8 NOT NULL ,
            `Status` ENUM( 'active', 'approval' ) NOT NULL DEFAULT 'active',
            `Date_format` VARCHAR( 25 ) CHARACTER SET utf8 NOT NULL ,
            PRIMARY KEY ( `ID` ) ,
            INDEX ( `Status` )";
        $rlDb->createTable("android_languages", $sql_create, RL_DBPREFIX, "DEFAULT CHARSET=utf8");

        $sql = "INSERT INTO `" . RL_DBPREFIX . "android_languages` (`Code`, `Direction`, `Key`, `Status`, `Date_format`)
                VALUES ('en', 'ltr', 'english', 'active', '%b %d,%Y')";
        $rlDb->query($sql);

        // create android phrases table
        $sql_create = "`ID` INT( 9 ) NOT NULL AUTO_INCREMENT ,
            `Code` VARCHAR( 2 ) CHARACTER SET utf8 NOT NULL ,
            `Key` VARCHAR( 128 ) CHARACTER SET utf8 NOT NULL ,
            `Value` MEDIUMTEXT CHARACTER SET utf8 NOT NULL ,
            PRIMARY KEY ( `ID` ) ,
            INDEX ( `Code` )";
        $rlDb->createTable("android_phrases", $sql_create, RL_DBPREFIX, "DEFAULT CHARSET=utf8");

        /* add menu group */
        $pos = $rlDb->getRow("SELECT MAX(`Position`) AS `Max` FROM `" . RL_DBPREFIX . "admin_controllers` WHERE `Parent_ID` = 0");
        $pos = $pos['Max'] + 1;
        $sql = "INSERT INTO `" . RL_DBPREFIX . "admin_controllers` (`Parent_ID`, `Position`, `Key`) VALUES ('0', '{$pos}', 'android')";
        $rlDb->query($sql);

        $id = $config['android_admin_section_id'] = $rlDb->insertID();

        $sql = "INSERT INTO `" . RL_DBPREFIX . "config` (`Group_ID`, `Key`, `Default`, `Plugin`) VALUES
                ('0', 'android_admin_section_id', '{$id}', 'androidConnect'),
                ('0', 'android_lang', 'en', 'androidConnect')";
        $rlDb->query($sql);

        // create android adsense table
        $sql_create = "`ID` int(50) NOT NULL AUTO_INCREMENT,
            `Name` varchar(50) NOT NULL,
            `Pages` text NOT NULL,
            `Side` varchar(50) NOT NULL,
            `Code` varchar(100) NOT NULL,
            `Status` enum('active','approval') NOT NULL,
            PRIMARY KEY (`ID`)";
        $rlDb->createTable("android_adsense", $sql_create, RL_DBPREFIX, "DEFAULT CHARSET=utf8");

        // create android push_token table
        $sql_create = "`ID` int(50) NOT NULL AUTO_INCREMENT,
            `Token` varchar(255) NOT NULL,
            `Phone_ID` varchar(255) NOT NULL,
            `Account_ID` int(6) NOT NULL DEFAULT '0',
            `Language` varchar(2) NOT NULL,
            `Status` enum('active','inactive') NOT NULL DEFAULT 'active',
            PRIMARY KEY (`ID`)";
        $rlDb->createTable("android_push_tokens", $sql_create, RL_DBPREFIX, "DEFAULT CHARSET=utf8");

        $sql = "INSERT INTO `" . RL_DBPREFIX . "admin_controllers` (`Parent_ID`, `Position`, `Key`, `Controller`) VALUES ('{$id}', '1', 'android_languages', 'android_languages')";
        $rlDb->query($sql);

        $sql = "INSERT INTO `" . RL_DBPREFIX . "admin_controllers` (`Parent_ID`, `Position`, `Key`, `Controller`) VALUES ('{$id}', '2', 'android_settings', 'android_settings')";
        $rlDb->query($sql);

        $sql = "INSERT INTO `" . RL_DBPREFIX . "admin_controllers` (`Parent_ID`, `Position`, `Key`, `Controller`) VALUES ('{$id}', '3', 'android_listing_types', 'android_listing_types')";
        $rlDb->query($sql);

        $sql = "INSERT INTO `" . RL_DBPREFIX . "admin_controllers` (`Parent_ID`, `Position`, `Key`, `Controller`) VALUES ('{$id}', '4', 'android_adsense', 'android_adsense')";
        $rlDb->query($sql);

        $main_type_key = $rlDb->getOne('Key', "`Status` = 'active' ORDER BY `Order`", 'listing_types');
        $rlDb->query("UPDATE `" . RL_DBPREFIX . "config` SET `Default` = '{$main_type_key}' WHERE `Key` = 'android_main_listing_type' LIMIT 1");

        $sql = "UPDATE `" . RL_DBPREFIX . "config` SET `Group_ID` = 0 WHERE `Key` = 'android_version' LIMIT 1";
        $rlDb->query($sql);

        $this->setupLanguages();

        $this->addAdminSection();
    }

    /**
     * Uninstall process
     *
     * @since 4.0.0
     */
    public function uninstall()
    {
        global $rlDb;

        $sql = "ALTER TABLE `" . RL_DBPREFIX . "listing_types` DROP `Android_icon`, DROP `Android_status`, DROP `Android_position`";
        $rlDb->query($sql);

        $rlDb->dropTables(
            [
                'android_languages',
                'android_phrases',
                'android_adsense',
                'android_push_tokens',
            ]
        );

        $sql = "DELETE FROM `" . RL_DBPREFIX . "admin_controllers` WHERE `Key` LIKE 'android%'";
        $rlDb->query($sql);

        $this->removeAdminSection();
    }

    /**
     * Update process of the plugin (copy from core)
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
     * Update process
     *
     * @since 4.2.0
     */
    public function update102()
    {
        // update phrases
        $this->addUpdatePhrases();
    }

    /**
     * Update process
     *
     * @since 4.2.0
     */
    public function update104()
    {
        // update phrases
        $this->addUpdatePhrases();
    }

    /**
     * Update process
     *
     * @since 4.2.0
     */
    public function update105()
    {
        // update phrases
        $this->addUpdatePhrases();
    }

    /**
     * Update process
     *
     * @since 4.2.0
     */
    public function update200()
    {
        global $rlDb;
        // update phrases
        $this->addUpdatePhrases();
        $main_type_key = $rlDb->getOne('Key', "`Status` = 'active' ORDER BY `Order`", 'listing_types');
        $rlDb->query("UPDATE `" . RL_DBPREFIX . "config` SET `Default` = '{$main_type_key}' WHERE `Key` = 'android_main_listing_type' LIMIT 1");
    }

    /**
     * Update process
     *
     * @since 4.2.0
     */
    public function update210()
    {
        global $rlDb;
        // update phrases
        $this->addUpdatePhrases();

        $sql = "
        CREATE TABLE `" . RL_DBPREFIX . "android_adsense` (
            `ID` int(50) NOT NULL AUTO_INCREMENT,
            `Name` varchar(50) NOT NULL,
            `Pages` text NOT NULL,
            `Side` varchar(50) NOT NULL,
            `Code` varchar(100) NOT NULL,
            `Status` enum('active','approval') NOT NULL,
            PRIMARY KEY (`ID`)
        ) CHARSET=utf8 ";
        $rlDb->query($sql);

        $id = $rlDb->getOne('ID', "`Key` = 'android' AND `Parent_ID` = 0", 'admin_controllers');

        $sql = "INSERT INTO `" . RL_DBPREFIX . "admin_controllers` (`Parent_ID`, `Position`, `Key`, `Controller`) VALUES ('{$id}', '4', 'android_adsense', 'android_adsense')";
        $rlDb->query($sql);
    }

    /**
     * Update process
     *
     * @since 4.2.0
     */
    public function update211()
    {
        if (!$GLOBALS['rlDb']->getRow("SHOW TABLES LIKE '" . RL_DBPREFIX . "android_adsense'")) {
            $sql = "
            CREATE TABLE `" . RL_DBPREFIX . "android_adsense` (
                `ID` int(50) NOT NULL AUTO_INCREMENT,
                `Name` varchar(50) NOT NULL,
                `Pages` text NOT NULL,
                `Side` varchar(50) NOT NULL,
                `Code` varchar(100) NOT NULL,
                `Status` enum('active','approval') NOT NULL,
                PRIMARY KEY (`ID`)
            ) CHARSET=utf8 ";
            $GLOBALS['rlDb']->query($sql);
        }
        if (!$GLOBALS['rlDb']->getOne('ID', "`Key` = 'android_adsense'", 'admin_controllers')) {
            $id = $GLOBALS['rlDb']->getOne('ID', "`Key` = 'android' AND `Parent_ID` = 0", 'admin_controllers');
            $sql = "INSERT INTO `" . RL_DBPREFIX . "admin_controllers` (`Parent_ID`, `Position`, `Key`, `Controller`) VALUES ('{$id}', '4', 'android_adsense', 'android_adsense')";
            $GLOBALS['rlDb']->query($sql);
        }
    }

    /**
     * Update process
     *
     * @since 4.2.0
     */
    public function update220()
    {
        $sql = "UPDATE `" . RL_DBPREFIX . "config` SET `Group_ID` = 0 WHERE `Key` = 'android_version' LIMIT 1";
        $GLOBALS['rlDb']->query($sql);

        $this->addUpdatePhrases();
    }

    /**
     * Update process
     *
     * @since 4.2.0
     */
    public function update230()
    {
        if (!$GLOBALS['rlDb']->getRow("SHOW TABLES LIKE '" . RL_DBPREFIX . "android_push_tokens'")) {
            $sql = "
                CREATE TABLE `" . RL_DBPREFIX . "android_push_tokens` (
                 `ID` int(50) NOT NULL AUTO_INCREMENT,
                  `Token` varchar(255) NOT NULL,
                  `Phone_ID` varchar(255) NOT NULL,
                  `Account_ID` int(6) NOT NULL DEFAULT '0',
                  `Status` enum('active','inactive') NOT NULL DEFAULT 'active',
                  PRIMARY KEY (`ID`)
                ) CHARSET=utf8";
            $GLOBALS['rlDb']->query($sql);
        }

        $this->addUpdatePhrases();
    }

    /**
     * Update process
     *
     * @since 4.2.0
     */
    public function update300()
    {
        $this->addUpdatePhrases();
    }

    /**
     * Update process
     *
     * @since 4.2.0
     */
    public function update310()
    {
        $this->addUpdatePhrases();
    }

    /**
     * Update process
     *
     * @since 4.2.0
     */
    public function update320()
    {
        $this->addUpdatePhrases();
    }

    /**
     * Update process
     *
     * @since 4.2.0
     */
    public function update330()
    {
        $this->addUpdatePhrases();
    }

    /**
     * Update process
     *
     * @since 4.0.0
     */
    public function update400()
    {
        global $rlDb;

        // remove android photo from table listings
        $rlDb->dropColumnFromTable('Android_photo', 'listings');

        // remove paypal mpl configurations
        $rlDb->query(
            "DELETE FROM `{db_prefix}config`
            WHERE (`Key` = 'android_divider_paypal_mpl'
                OR `Key` = 'android_paypal_mpl_module'
                OR `Key` = 'android_paypal_mpl_sandbox'
                OR `Key` = 'android_paypal_mpl_account_email'
                OR `Key` = 'android_paypal_mpl_app_id'
                OR `Key` = 'android_paypal_mpl_api_username'
                OR `Key` = 'android_paypal_mpl_api_password'
                OR `Key` = 'android_paypal_mpl_api_signature')"
        );

        // remove paypal mpl phrases
        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
            WHERE (`Key` = 'config+name+android_divider_paypal_mpl'
                OR `Key` = 'config+name+android_paypal_mpl_module'
                OR `Key` = 'config+name+android_paypal_mpl_sandbox'
                OR `Key` = 'config+name+android_paypal_mpl_account_email'
                OR `Key` = 'config+name+android_paypal_mpl_app_id'
                OR `Key` = 'config+name+android_paypal_mpl_api_username'
                OR `Key` = 'config+name+android_paypal_mpl_api_password'
                OR `Key` = 'config+name+android_paypal_mpl_api_signature')"
        );

        // remove old hooks
        $rlDb->query(
            "DELETE FROM `{db_prefix}hooks`
            WHERE (`Name` = 'apTplControlsForm'
                OR `Name` = 'phpUpdatePhotoDataSetFields'
                OR `Name` = 'apAjaxRequest'
                OR `Name` = 'apTplHeader') AND `Plugin` = 'androidConnect'"
        );

        $rlDb->addColumnToTable('Language', "VARCHAR(2) NOT NULL AFTER `Account_ID`", 'android_push_tokens');

        // remove android photos
        $this->erasePictures();

        // update phrases
        $this->addUpdatePhrases();

        @unlink(RL_PLUGINS . 'androidConnect' . RL_DS . 'admin' . RL_DS . 'resizeEntry.tpl');
        @unlink(RL_PLUGINS . 'androidConnect' . RL_DS . 'paypalMPL.gateway.php');
    }

    /**
     * Update process
     *
     * @since 4.2.0
     */
    public function update410()
    {
        $this->addUpdatePhrases();
    }

    /**
     * Update process
     *
     * @since 4.2.0
     */
    public function update420()
    {
        $GLOBALS['rlDb']->query(
            "DELETE FROM `{db_prefix}lang_keys`
            WHERE (`Key` = 'android_controls_resize_pictures'
                OR `Key` = 'android_controls_button'
                OR `Key` = 'android_controls_notice'
                OR `Key` = 'android_controls_resizing'
                OR `Key` = 'android_controls_resize_completed')"
        );

        // update phrases
        $this->addUpdatePhrases();

        @unlink(RL_PLUGINS . 'androidConnect' . RL_DS . 'static' . RL_DS . 'jquery.smartbanner.css');
        @unlink(RL_PLUGINS . 'androidConnect' . RL_DS . 'static' . RL_DS . 'jquery.smartbanner.js');
    }

    public function update422()
    {
        $GLOBALS['rlDb']->query("
            DELETE FROM `{db_prefix}lang_keys`
            WHERE `Plugin` = 'androidConnect' AND `Key` = 'android_application'
        ");
    }

    /**
     * Get listing types
     *
     * @return array
     **/
    public function getListingTypes()
    {

        $sql = "SELECT `T1`.*, IF(`T2`.`Status` = 'active', 1, 0) AS `Advanced_search_availability` ";
        $sql .= "FROM `{db_prefix}listing_types` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}search_forms` AS `T2` ON `T1`.`Key` = `T2`.`Type` AND `T2`.`Mode` = 'advanced' ";
        $sql .= "WHERE `T1`.`Android_status` = 'active' AND `T1`.`Status` = 'active' ";
        $sql .= "ORDER BY `Android_position` ";
        $types = $GLOBALS['rlDb']->getAll($sql);

        $types = $GLOBALS['rlLang']->replaceLangKeys($types, 'listing_types', array('name'));

        foreach ($types as $type) {
            $type['Type'] = $type['Key'];
            $type['Page_key'] = 'lt_' . $type['Type'];
            $type['My_key'] = 'my_' . $type['Type'];
            $type_out[$type['Key']] = $type;
        }
        unset($types);

        $this->types = $type_out;
        unset($type_out);
    }

    /**
     * Send response
     *
     * @param array  $response - response array
     * @param string $type     - response type: xml or json
     * @param string $item     - requested resource item
     *
     **/
    public function send($response, $type = 'xml', $item = '')
    {
        global $response_dely;

        if (!$type) {
            $GLOBALS['rlDebug']->logger("ANDROID: No response type defined in request by {$item} request");
            exit;
        }

        if (!$this->custom_output) {

            switch ($type) {
                case 'xml':
                    $print = '<?xml version="1.0" encoding="UTF-8"?>'
                        . '<items>';
                    foreach ($response as $item) {
                        $node_name = $item['node_name'] ? $item['node_name'] : 'item';
                        unset($item['node_name']);

                        $print .= '<' . $node_name . '>';
                        if ($item['child_nodes']) {
                            foreach ($item['child_nodes'] as $child_node) {
                                foreach ($child_node as $child_node_key => $child_node_value) {
                                    if (strtolower($child_node_key) == 'name') {
                                        $text_content = $child_node_value;
                                    } else {
                                        $attrs .= ' ' . strtolower($child_node_key) . '="' . $child_node_value . '"';
                                    }
                                }
                                $print .= '<item' . $attrs;
                                $print .= $text_content ? '><![CDATA[' . $text_content . ']]></item>' : ' />';

                                unset($text_content, $attrs);
                            }
                        } else {
                            $print .= $this->printValue($item);
                        }
                        $print .= '</' . $node_name . '>';
                    }

                    $print .= '</items>';
                    break;

                case 'json':
                    // go ahead with this case programming (the first who need it)

                    $print = json_encode($response);

                    break;

                default:
                    die('Unsupported response type received');
                    break;
            }
        } else {
            $print = $response;
        }

        if ($type == 'xml') {
            $print = str_replace("&amp;", "AMPREPLACE", $print);
            $print = str_replace("&", "&amp;", $print);
            $print = str_replace("AMPREPLACE", "&amp;", $print);
            header('Content-Type: text/xml; charset=utf-8');
        } else {
            // send headers
            header("Content-Type: application/json; charset=UTF-8");
            header("Cache-Control: max-age=10, public");
            header("X-Powered-By: Flynax API");
            header("Pragma: cache");
        }

        if (function_exists('gzencode') && $this->response_deflate) {
            header("Content-Type: application/x-gzip");
            header("Content-Encoding: gzip");

            $print = gzencode($print);
        }

        if ($response_dely) {
            usleep(700000);
        }
        //sleep 0.7 second

        echo $print;
        exit;
    }

    /**
     * Print value
     *
     * @param array  $data       - array of items to print
     * @param string $custom_tag - custom tag name if no tag name specified
     *
     * @return string - output xml
     *
     **/
    public function printValue($data, $custom_tag)
    {
        global $lang;

        foreach ($data as $key => &$value) {
            $empty_tag = $custom_tag ? $custom_tag : 'item';
            $tag = is_numeric($key) ? $empty_tag : strtolower($key);

            $out .= '<' . $tag . '>';
            if (is_array($value)) {
                $out .= $this->printValue($value);
            } else {
                $out .= '<![CDATA[' . $value . ']]>';
            }
            $out .= '</' . $tag . '>';
        }

        return $out;
    }

    /**
     * Convert date to unix timestamp
     *
     * @param string $data - data in default format
     *
     * @return int - unix timestamp date
     *
     **/
    public function convertDate($data)
    {
        return date(str_replace(array('%', 'b'), array('', 'M'), RL_DATE_FORMAT), strtotime($data) < 0 ? 0 : strtotime($data));
    }

    /** Hooks **/
    /**
     * @hook  listingsModifyWhereFeatured
     * @since 4.0.0
     */
    public function hookListingsModifyWhereFeatured(&$param1)
    {
        if (defined('ANDROID_APP') and ANDROID_APP) {
            $param1 .= " AND `T1`.`Main_photo` <> ''";
        }
    }

    /**
     * @hook  apTplHeader
     * @since 4.0.0
     */
    public function hookApTplHeader()
    {
        global $config;
        echo '<style type="text/css">
        div#msection_' . $config['android_admin_section_id'] . ' > div.caption > div.icon {
            background: url(' . RL_PLUGINS_URL . 'androidConnect/static/gallery.png) 3px 0 no-repeat!important;
        }
        div#msection_' . $config['android_admin_section_id'] . ' > div.caption_active > div.icon {
            background: url(' . RL_PLUGINS_URL . 'androidConnect/static/gallery.png) 3px -26px no-repeat!important;
        }
        </style>';
    }

    /**
     * @hook  apPhpIndexBeforeController
     * @since 4.0.0
     */
    public function hookApPhpIndexBeforeController()
    {
        global $cInfo, $config;
        if ($cInfo['Parent_ID'] == $config['android_admin_section_id'] && $config['android_admin_section_id']) {
            $cInfo['Plugin'] = 'androidConnect';
        }
    }

    /**
     * @hook  apPhpIndexBottom
     * @since 4.0.0
     */
    public function hookApPhpIndexBottom()
    {
        global $reefless, $extended_sections;
        $extended_sections[] = 'android_languages';
        $this->breadCrumbs();
    }

    /**
     * @hook  confirmPreConfirm
     * @since 4.0.0
     */
    public function hookConfirmPreConfirm()
    {
        global $rlDb, $account, $key;

        if ($GLOBALS['plugins']['facebookConnect']) {
            if ($rlDb->getOne('facebook_ID', "`Confirm_code` = '{$key}'", 'accounts')) {
                $account['Admin_confirmation'] = 0;
            }
        }
    }

    /**
     * @hook  phpGetPlanSql
     * @since 4.0.0
     */
    public function hookPhpGetPlanSql()
    {
        global $sql;

        if (!defined('ANDROID_APP')) {
            return;
        }

        if (!is_numeric(strpos($sql, "`Featured`"))) {
            $sql = str_replace("`T1`.`ID`,", "`T1`.`ID`, `T1`.`Featured`, `T1`.`Advanced_mode`, `T1`.`Standard_listings`, `T1`.`Featured_listings`,", $sql);
            $sql = str_replace("`T3`.`Listings_remains`", "`T3`.`ID` AS `Package_ID`, `T3`.`Listings_remains`, `T3`.`Standard_remains`, `T3`.`Featured_remains`, `T2`.`Listings_remains` AS `Using`, `T2`.`ID` AS `Plan_using_ID` ", $sql);
        }
    }

    /**
     * @hook  myListingsSqlFields
     * @since 4.0.0
     */
    public function hookMyListingsSqlFields(&$param1)
    {
        global $sql;

        if (!defined('ANDROID_APP')) {
            return;
        }

        $ref_sql = $param1 ? $param1 : $sql;
        if (!is_numeric(strpos($ref_sql, '`T2`.`Type`'))) {
            if ($param1) {
                $param1 .= ', `T2`.`Type` AS `Plan_type`, `T2`.`Price` AS `Plan_price`, `T2`.`Key` AS `Plan_real_key` ';
            } else {
                $sql .= ', `T2`.`Type` AS `Plan_type`, `T2`.`Price` AS `Plan_price`, `T2`.`Key` AS `Plan_real_key` ';
            }
        }
    }

    /**
     * @hook  apPhpConfigBeforeUpdate
     * @since 4.0.0
     */
    public function hookApPhpConfigBeforeUpdate()
    {
        global $update;
        foreach ($update as $key => &$value) {
            if ($value['where']['Key'] == 'android_inapp_key') {
                $value['fields']['Values'] = $value['fields']['Default'];
            }
        }
    }

    /**
     * @hook  getCatTreeFields
     * @since 4.0.0
     */
    public function hookGetCatTreeFields()
    {
        if (!defined('ANDROID_APP')) {
            return;
        }

        global $sql;

        if (!is_numeric(strpos($sql, '`T1`.`Count`'))) {
            $sql .= '`T1`.`Count`, ';
        }
    }

    /**
     * @hook  phpGetProfileModifyField
     * @since 4.0.0
     */
    public function hookPhpGetProfileModifyField(&$param1)
    {
        if (!defined('ANDROID_APP')) {
            return;
        }

        if (!is_numeric(strpos($param1, '`T2`.`Abilities`'))) {
            $param1 .= ', `T2`.`Abilities` ';
        }
    }

    /**
     * @hook  rlMessagesAjaxContactOwnerAfterSend
     * @since 4.0.0
     */
    public function hookRlMessagesAjaxContactOwnerAfterSend()
    {
        global $account_info, $lang, $config;
        if (defined('IS_LOGIN') || $config['messages_save_visitor_message']) {
            $message = $this->fetchMessage($account_info['ID'], 'From');
            $array_message = array('key' => 'message', 'title' => $lang['new_message']);
            foreach ($message as $key => $val) {
                if ($key == "From" || $key == "To") {
                    $array_message[strtolower($key . "_id")] = $val == '-1' ? $message['Visitor_mail'] : $val;
                } else {
                    $array_message[strtolower($key)] = $val;
                }
            }
            $this->sendPushNotification($array_message);
        }
    }

    /**
     * @hook tplHeader
     *
     * @since 3.2.0
     */
    public function hookTplHeader()
    {
        if ($GLOBALS['config']['android_smart_banner']) {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'androidConnect' . RL_DS . 'view' . RL_DS . 'tplHeader.tpl');
        }
    }

    /**
     * Display smarty banner in software version < 4.9.1
     *
     * @hook smartyFetchHook
     */
    public function hookSmartyFetchHook(&$compiled_content, &$resource_name)
    {
        global $config, $rlSmarty;

        if ($_COOKIE['smart_banner']
            || !$this->isMobile()
            || !$config['android_smart_banner']
            || version_compare($config['rl_version'], '4.9.1', '>=')
        ) {
            return;
        }

        $template_name = $config['template'];
        $header_name = $template_name . RL_DS . 'tpl' . RL_DS . 'header.tpl';

        if (false !== strpos($resource_name, $header_name) || $resource_name == 'header.tpl') {
            if (preg_match("/<body[^>]*?>/", $compiled_content, $body)) {
                $this->prepareBannerData();

                $tpl = RL_PLUGINS . 'androidConnect' . RL_DS . 'view' . RL_DS . 'smart_banner.tpl';
                $html_content = $rlSmarty->fetch($tpl, null, null, false);

                $compiled_content = str_replace($body[0], $body[0] . $html_content, $compiled_content);
            }
        }
    }

    /**
     * Display smarty banner in software version >= 4.9.1
     *
     * @hook tplBodyTop
     * @since 4.2.2
     */
    public function hookTplBodyTop()
    {
        global $config, $rlSmarty;

        if ($_COOKIE['smart_banner']
            || !$this->isMobile()
            || !$config['android_smart_banner']
            || version_compare($config['rl_version'], '4.9.1', '<')
        ) {
            return;
        }

        $this->prepareBannerData();

        $rlSmarty->display(RL_PLUGINS . 'androidConnect/view/smart_banner.tpl');
    }

    /**
     * Prepare smart banner data and assign it to SMARTY
     *
     * @since 4.2.2
     */
    public function prepareBannerData()
    {
        global $config;

        $bannerLogoUrl = '';
        if ($config['android_smart_banner_image']) {
            $bannerLogoUrl = $config['android_smart_banner_image'];
        }
        else {
            if (file_exists(RL_ROOT . 'templates' . RL_DS . $config['template'] . RL_DS . 'img'  . RL_DS .  'logo.svg')) {
                $bannerLogoUrl = RL_URL_HOME . 'templates/' . $config['template'] . '/img/logo.svg';
            }
            else {
                $bannerLogoUrl = RL_URL_HOME . 'templates/' . $config['template'] . '/img/logo.png';
            }
        }

        $banner_info = [
            'name' => $GLOBALS['lang']['pages+title+home'],
            'domain' => $_SERVER['HTTP_HOST'],
            'url' => $bannerLogoUrl
        ];

        $GLOBALS['rlSmarty']->assign('banner_info', $banner_info);
    }

    /**
     *  Detect mobile or not
     *
     * return bool
     */
    public function isMobile()
    {
        $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
        return false !== strpos($userAgent, 'android');
    }

    public function hookRlAccountGetAccountTypesFields(&$fields)
    {
        if (version_compare($GLOBALS['config']['rl_version'], '4.6.2') >= 0) {
            $fields[] = 'Thumb_width';
            $fields[] = 'Thumb_height';
        }
    }

    /**
     * @hook apMixConfigItem
     *
     * @since 3.2.0
     */
    public function hookApMixConfigItem(&$config)
    {
        if ($config['Key'] == 'android_main_listing_type') {
            $config['Values'] = array();

            foreach ($GLOBALS['rlListingTypes']->types as $ltype) {
                if ($ltype['Android_status'] != 'active') {
                    continue;
                }
                $config['Values'][] = array(
                    'ID' => $ltype['Key'],
                    'name' => $GLOBALS['lang']['listing_types+name+' . $ltype['Key']],
                );
            }
            $config['Values'][0]['required'] = true;
        }
    }

    /**
     * @hook phpRecentlyAddedModifyPreSelect
     *
     * @since 4.0.0
     */
    public function hookPhpRecentlyAddedModifyPreSelect(&$param1)
    {
        $param1 = false;
    }

    /**
     * @hook listingsModifyWhereSearch
     *
     * @since 3.2.0
     */
    public function hookListingsModifyWhereSearch(&$sql, $data, $type, $form)
    {
        if (!defined('ANDROID_SAVED_SEARCH_IDS')) {
            return;
        }
        $sql .= sprintf(" AND `T1`.`ID` IN(%s) ", ANDROID_SAVED_SEARCH_IDS);
    }

    /**
     * add new hook in cron file
     *
     * @since 3.1.0
     *
     * @param array $info         - save search content info
     * @param array $listings     - find listings
     * @param array $account_info - account info
     *
     **/
    public function hookCronSavedSearchNotify($info, $listings, $account_info)
    {
        global $config, $rlDb;

        if (false !== $tokens = $this->fetchAllActiveTokensByAccountId($account_info['ID'])) {
            $oldCount = $info['Matches'] ? count(explode(",", $info['Matches'])) : 0;
            $newCount = count(explode(",", $listings)) - $oldCount;

            $send_info['data'] = unserialize($info['Content']);
            $send_info['id'] = $info['ID'];
            $send_info['matches'] = $listings;
            $send_info['key'] = 'save_search';
            $send_info['type'] = $info['Listing_type'];
            $send_info['title'] = $rlDb->getOne('Value', "`Key` = 'android_ad_alert' AND `Code` = '{$account_info['Lang']}'", 'android_phrases');
            $send_info['message'] = str_replace('{count}', $newCount, $rlDb->getOne('Value', "`Key` = 'android_new_saved_search' AND `Code` = '{$account_info['Lang']}'", 'android_phrases'));

            $GLOBALS['reefless']->loadClass('Pusher', false, 'androidConnect');
            $GLOBALS['rlPusher']->apiKey = $config['android_google_key'];

            foreach ($tokens as $key => $val) {
                $title = $rlDb->getOne('Value', "`Key` = 'android_ad_alert' AND `Code` = '{$val['Language']}'", 'android_phrases');
                $send_info['title'] = $title ? $title : $send_info['title'];

                $GLOBALS['rlPusher']->notify($val['Token'], $send_info);
            }
        }
    }

    /**
     * Description
     * @param int $user_id
     * @param int $message_new don't used
     * @param string $admin false
     * @return array
     */
    public function hookRlMessagesAjaxAfterMessageSent($user_id, $message_new, $admin)
    {
        global $lang;
        $message = $this->fetchMessage($user_id);

        $array_message = array('key' => 'message', 'title' => $lang['new_message']);
        foreach ($message as $key => $val) {
            if ($key == "From" || $key == "To") {
                $array_message[strtolower($key . "_id")] = $val;
            } else {
                $array_message[strtolower($key)] = $val;
            }
        }

        $this->sendPushNotification($array_message);
    }

    /**
     * @hook apPhpConfigBottom
     *
     * @since 4.2.0
     */
    public function hookApPhpConfigBottom()
    {
        global $rlSmarty;

        $configGroups = $rlSmarty->_tpl_vars['configGroups'];
        foreach ($configGroups as $key => $value) {
            if ($value['Key'] == 'androidConnect') {
                unset($configGroups[$key]);
                break;
            }
        }
        $rlSmarty->assign_by_ref('configGroups', $configGroups);
    }

    /**
     * Get language phrases by language iso code
     *
     * @param string $code - requested language code
     *
     * @return array - requested phrases list
     *
     **/
    public function getLangPhrases($code)
    {
        global $languages, $config, $rlDb;

        if (!$code) {
            $GLOBALS['rlDebug']->logger("ANDROID: Unable to fetch lang phrases, no language code specified");
            return false;
        }

        $system_code = $code;
        if (!$languages[$code]) {
            $system_code = $config['lang'];
        }

        // get system languages
        $where = "WHERE `Status` = 'active' AND `Code` = '{$system_code}' AND ";
        $where .= "(`Key` LIKE 'listing_types+name+%' OR `Key` LIKE 'account_types+name+%') ";

        $rlDb->setTable('lang_keys');
        $system_phrases = $rlDb->fetch(array('Key', 'Value'), null, $where);

        // get app phrases
        $rlDb->setTable('android_phrases');
        $app_phrases = $rlDb->fetch(array('Key', 'Value'), array('Code' => $code));

        foreach (array_merge($system_phrases, $app_phrases) as $phrase) {
            $phrases[$phrase['Key']] = $phrase['Value'];
        }
        if ($GLOBALS['plugins']['search_by_distance']) {
            $phrases['sbd_location_search_hint'] = $GLOBALS['lang']['sbd_location_search_hint'];

        }
        unset($system_phrases, $app_phrases);

        return $phrases;
    }

    /**
     * Get configs related to android app
     *
     * @return array - configs list
     *
     **/
    public function getConfigs($countDate)
    {
        global $config, $rlDb;
        /* get system configs, the value is adapted config name */
        $from_system = array(
            'android_lang' => 'system_lang',
            'system_currency_position' => 'currency_position',
            'rl_version',
            'system_currency',
            'site_main_email' => 'feedback_email',
            'account_edit_email_confirmation',
            'account_thumb_width',
            'account_thumb_height',
            'pg_upload_large_width' => 'picture_large_width',
            'pg_upload_large_height' => 'picture_large_height',
            'listing_auto_approval',
            'edit_listing_auto_approval',
            'account_login_mode',
            'android_billing_currency',
            'android_paypal_client_id',
            'android_paypal_sandbox',
            'android_google_id',
            'account_removing',
        );

        /* the following configs will not be sent */
        $deny_config = array(
            'android_inapp_key',
            'android_paypal_secret',
        );

        foreach ($from_system as $key => $sys_config) {
            if (is_numeric($key)) {
                $response[$sys_config] = $config[$sys_config];
            } else {
                $response[$sys_config] = $config[$key];
            }
        }

        // set hard core value
        if (!$response['account_thumb_width'] && !$response['account_thumb_height']) {
            $response['account_thumb_width'] = 110;
            $response['account_thumb_height'] = 100;
        }

        /* get android configs */
        $rlDb->setTable('config');
        $app_configs = $rlDb->fetch(array('Key', 'Default'), array('Plugin' => 'androidConnect'), "AND `Type` <> 'divider' AND `Key` NOT IN ('" . implode("','", $deny_config) . "')");

        foreach ($app_configs as $app_config) {
            $response[$app_config['Key']] = str_replace('android_', '', $app_config['Default']);
        }
        unset($app_configs);

        /* add custom configs */
        $response['site_name'] = $rlDb->getOne('Value', "`Key` = 'pages+title+home' AND `Code` = '{$config['lang']}'", 'lang_keys');
        $response['site_url'] = '<a href="' . RL_URL_HOME . '">' . RL_URL_HOME . '</a>';
        $response['site_email'] = '<a href="mailto:' . $config['site_main_email'] . '">' . $config['site_main_email'] . '</a>';
        $response['home_map_host_zoom'] = $this->home_map_host_zoom;
        $response['year_build_key'] = $this->year_build_key;
        $response['price_field_key'] = $this->price_key;

        // comments plugin
        $response['comment_plugin'] = $GLOBALS['plugins']['comment'] ? 1 : 0;
        $response['comments_stars_number'] = $config['comments_stars_number'];
        $response['comments_rating_module'] = $config['comments_rating_module'];
        $response['comment_auto_approval'] = $config['comment_auto_approval'];
        $response['comment_message_symbols_number'] = $config['comment_message_symbols_number'];
        $response['comments_login_access'] = $config['comments_login_access'];
        $response['comments_login_post'] = $config['comments_login_post'];

        // zip field input type, numeric or mixed
        $response['zip_numeric_input'] = $this->zip_numeric_input_type;

        // count new listings handler
        $response['countNewListingsData'] = 'empty';
        if ($config['android_count_recently_added']) {
            if ($countDate) {
                $response['countNewListingsData'] = $this->countNewListings($countDate);
            }
        }

        if ($GLOBALS['plugins']['search_by_distance']) {
            $response['sbd_search_mode'] = $config['sbd_search_mode'];
        }

        // set main listing type
        $response['mainListingType'] = $this->main_listing_type;

        if ($GLOBALS['plugins']['hybridAuthLogin'] && version_compare($GLOBALS['plugins']['hybridAuthLogin'], '2.0.0') >= 0) {
            $response['hybridAuthLogin'] = 1;
            $response['hybridAuthLogin_password_syn'] = $config['ha_enable_password_synchronization'];
            $GLOBALS['reefless']->loadClass('HybridAuthLogin', null, 'hybridAuthLogin');
            $hybridAuthApi = new \Flynax\Plugins\HybridAuth\API();

            $providers = $hybridAuthApi
                ->withProviderCredentials('google', array('ha_google_app_key', 'ha_google_app_id'))
                ->withProviderCredentials('twitter', array('ha_twitter_app_secret', 'ha_twitter_app_id'))
                ->getActiveProviders();

            foreach ($providers as $data) {
                if ($data['Provider']) {
                    $response[$data['Provider'] . '_login'] = 1;
                    if ($data['Credentials']) {
                        foreach ($data['Credentials'] as $cKey => $cVal) {
                            $response[$cKey] = $cVal;
                        }
                    }
                }
            }
        }
        // facebook connect
        else {
            $response['facebookConnect_plugin'] = $GLOBALS['plugins']['facebookConnect'] && $config['facebookConnect_module'] ? 1 : 0;
        }

        // report Broken Listing
        $response['reportBroken_plugin'] = $GLOBALS['plugins']['reportBrokenListing']
        && version_compare($GLOBALS['plugins']['reportBrokenListing'], '3.0.0') >= 0
        ? 1 : 0;

        // cURL support flag
        $response['curl_available'] = extension_loaded('curl');

        // payment methods
        $response['android_inapp_module'] = $config['android_inapp_module'] && $config['android_inapp_key'] ? 1 : 0;
        $response['android_paypal_module'] = $config['android_paypal_module'] && $config['android_paypal_client_id'] && $config['android_paypal_secret'] ? 1 : 0;

        $response['android_yookassa_module'] = $GLOBALS['plugins']['yandexKassa'] && $config['android_yookassa_module'] && $config['android_yookassa_key'] && $config['android_yookassa_store_id'] ? 1 : 0;

        return $response;
    }

    /**
     * Build main cache
     *
     * @param int    $countDate    - timestamp date, the latest date the use run application
     * @param int    $tablet       - is tablet devide from other side
     * @param string $username     - username to check and return data for
     * @param string $passwordHash - x2 md5 of the user password to approve access
     *
     **/
    public function getCache($countDate, $tablet, $username, $passwordHash)
    {
        global $config, $rlLang, $languages, $rlDb;

        if ($tablet) {
            $this->grid_featured_limit = $this->grid_featured_limit_tablet;
        }

        $this->custom_output = true;

        // cache start
        $response = '<?xml version="1.0" encoding="UTF-8"?><cache>';

        // add configs
        $aConfigs = $this->getConfigs($countDate);
        $response .= '<configs>';

        foreach ($aConfigs as $config_key => $config_value) {
            $response .= '<config key="' . $config_key . '"><![CDATA[' . $config_value . ']]></config>';
        }

        $response .= '</configs>';
        // add configs END

        $rlDb->setTable('android_languages');
        $app_languages = $rlDb->fetch(array('Code', 'Direction', 'Key', 'Date_format'), array('Status' => 'active'));

        // add wabsite languages
        $response .= '<langsweb>';
        foreach ($languages as &$wLanguage) {
            $def_lang = $config['lang'] == $wLanguage['Code'] ? 1 : 0;

            $response .= '<lang code="' . $wLanguage['Code'] . '" name="' . $wLanguage['name'] . '" default="' . $def_lang . '" />';
        }
        $response .= '</langsweb>';

        // add languages and language phrases
        $response .= '<langs>';
        foreach ($app_languages as &$language) {
            $phrases = $this->getLangPhrases($language['Code']);
            $response .= '<lang code="' . $language['Code'] . '" name="' . $phrases['android_' . $language['Key']] . '" dir="' . $language['Direction'] . '">';

            foreach ($phrases as $phrase_key => $phrase_value) {
                $response .= '<phrase key="' . $phrase_key . '"><![CDATA[' . $phrase_value . ']]></phrase>';
            }

            $response .= '</lang>';
        }
        $response .= '</langs>';
        // add languages and language phrases END

        // add listing types
        if ($this->types) {
            $response .= '<listing_types>';

            foreach ($this->types as $listing_type) {
                $page_option = version_compare($config['rl_version'], '4.7.0') >= 0 ? 1 : $listing_type['Page'];
                $response .= '<type key="' . $listing_type['Key'] . '"
									photo="' . $listing_type['Photo'] . '"
									video="' . $listing_type['Video'] . '"
									page="' . $page_option . '"
									search="' . $listing_type['Search'] . '"
									admin="' . $listing_type['Admin_only'] . '"
									icon="' . $listing_type['Android_icon'] . '"></type>';
            }
            $response .= '</listing_types>';
        }
        // add listing types END

        // add adsense
        $this->getAdsense($response);

        // add account types
        $this->getAccountTypes($response);

        // add featured listings steck for home page
        $this->getFeatured($response, 0);

        // add listing search forms
        $this->getSearchForms($response);

        // add account search forms
        $this->getAccountSearchForms($response);

        // add user data
        $this->getUserData($response, $username, $passwordHash);

        // add report broken
        if ($GLOBALS['plugins']['reportBrokenListing']) {
            $this->getReportBrokenInfo($response);
        }

        // cache end
        $response .= '</cache>';

        return $response;
    }

    /**
     * Build main cache
     *
     * @since 4.0.0
     *
     * @param int    $countDate    - timestamp date, the latest date the use run application
     * @param int    $tablet       - is tablet devide from other side
     * @param string $username     - username to check and return data for
     * @param string $passwordHash - x2 md5 of the user password to approve access
     *
     **/
    public function getCacheJson($countDate, $tablet, $username, $passwordHash)
    {
        global $config, $rlLang, $languages, $rlDb;

        if ($tablet) {
            $this->grid_featured_limit = $this->grid_featured_limit_tablet;
        }

        // add configs
        $response['config'] = $this->getConfigs($countDate);

        // add wabsite languages
        $response['langsweb'] = $languages;

        $rlDb->setTable('android_languages');
        $app_languages = $rlDb->fetch(array('Code', 'Direction', 'Key', 'Date_format'), array('Status' => 'active'));

        foreach ($app_languages as &$language) {

            $phrases = $this->getLangPhrases($language['Code']);
            $language['name'] = $phrases['android_' . $language['Key']];
            $response['langs'][$language['Code']] = $language;
            $response['phrases'][$language['Code']] = $phrases;
        }

        // add listing types
        if ($this->types) {
            // add listing types END
            $response['listing_types'] = $this->types;
        }

        // add adsense
        $this->getAdsense($response, true);

        // add account types
        $this->getAccountTypes($response, true);

        // add featured listings steck for home page
        $this->getFeatured($response, 0, true);

        // add listing search forms
        $this->getSearchForms($response, true);

        // add account search forms
        $this->getAccountSearchForms($response, true);

        // add user data
        $this->getUserData($response, $username, $passwordHash, true);

        // add report broken
        if ($GLOBALS['plugins']['reportBrokenListing']) {
            $this->getReportBrokenInfo($response, true);
        }

        return $response;
    }

    /**
     * Get recently added listings by listing type
     *
     * @param string $type  - listing type key
     * @param int    $start - stack position
     *
     * @return array - recently added listings
     *
     **/
    public function getRecentlyAdded($type, $start = 0)
    {
        $GLOBALS['reefless']->loadClass('Listings');
        $listings = $GLOBALS['rlListings']->getRecentlyAdded($start, $this->grid_listings_limit, $type);

        if ($listings) {
            $listings_total = $GLOBALS['rlListings']->calc;

            array_push($this->transfer_listings_grid_fields, 'Date_diff');

            foreach ($listings as $index => $listing) {
                $date_diff = $listings[$index]['Date_diff'];
                unset($listings[$index]['Date_diff']); //unset date_diff to move it to the end of the item array

                if ($GLOBALS['config']['thumbnails_x2'] && $listing['Main_photo_x2']) {
                    $listing['Main_photo'] = $listing['Main_photo_x2'];
                }
                $listings[$index]['Main_photo'] = $listing['Main_photo'] ? RL_FILES_URL . $listing['Main_photo'] : '';
                $fields = $listing['fields'];

                foreach ($listing as $key => $value) {
                    if (!in_array($key, $this->transfer_listings_grid_fields)) {
                        unset($listings[$index][$key]);
                    }
                }

                $listings[$index]['price'] = '';
                $listings[$index]['title'] = $listing['listing_title'];
                $listings[$index]['middle_field'] = '';
                $listings[$index]['Date_diff'] = $date_diff; // set saved data_diff

                if (!$fields) {
                    continue;
                }

                // set price
                if ($fields && array_key_exists($this->price_key, $fields)) {
                    $listings[$index]['price'] = $fields[$this->price_key]['value'];
                    unset($fields[$this->price_key]);
                }

                foreach ($fields as $field_key => $field_value) {
                    if ($field_value['value']) {
                        $listings[$index]['middle_field'] .= $listings[$index]['middle_field'] ? ', ' . $field_value['value'] : $field_value['value'];
                    }
                }
            }
            if ($GLOBALS['json_support']) {
                $GLOBALS['response_type'] = "json";

                $out['listings'] = $listings;
                $out['statistic'] = $listings_total;

            } else {
                $listings[$index + 1] = array(
                    'total' => $listings_total,
                    'node_name' => 'statistic',
                );
                $out = $listings;
            }
        } else {
            if ($GLOBALS['json_support']) {
                $GLOBALS['response_type'] = "json";
                $out['listings'] = "";
            } else {
                $out = false;
            }
        }

        return $out;
    }

    /**
     * Get home listings
     *
     * @since 3.2.0
     *
     * @param  int $start - page
     *
     * @return string - return xml
     **/
    public function getHomeListings($start = 0)
    {
        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $this->getFeatured($response, $start, true);
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?><items>';
            $this->getFeatured($response, $start, false);
            $response .= '</items>';
        }

        return $response;
    }

    /**
     * Get featured listings for home page
     *
     * @since 3.2.0 Added $start parameter
     *
     * @param string $response - xml response
     * @param int    $start    - start page
     * @param bool   $json     - true or false
     *
     * @return array - recently added listings
     *
     **/
    public function getFeatured(&$response, $start = 1, $json = false)
    {
        global $config, $reefless;

        $reefless->loadClass('Listings');

        unset($this->transfer_listings_grid_fields[array_search('Photos_count', $this->transfer_listings_grid_fields)]);

        if ($config['android_home_page_listings'] == 'featured') {
            $listings = $GLOBALS['rlListings']->getFeatured(
                $config['android_main_listing_type'],
                $config['android_grid_listings_number'],
                false,
                false,
                false
            );
        } else {
            $listings = $GLOBALS['rlListings']->getRecentlyAdded($start, $config['android_grid_listings_number'], $config['android_main_listing_type']);
        }

        if ($json) {
            if ($start < 1) {
                $calc = $GLOBALS['rlListings']->calc;
                $response['featured_count'] = $calc;
            }

            foreach ($listings as $index => $listing) {
                $tmpListing = array();
                $tmpListing['id'] = $listing['ID'];

                // set main photo
                if ($config['thumbnails_x2'] && $listing['Main_photo_x2']) {
                    $listing['Main_photo'] = $listing['Main_photo_x2'];
                }
                $tmpListing['photo'] = $listing['Main_photo'] ? RL_FILES_URL . $listing['Main_photo'] : '';

                $fields = $listing['fields'];
                if ($fields) {
                    // set price
                    if ($fields && array_key_exists($this->price_key, $fields)) {
                        $tmpListing['price'] = $fields[$this->price_key]['value'];
                        unset($fields[$this->price_key]);
                    } else {
                        $tmpListing['price'] = '';
                    }
                    $iteration = 1;

                    if ($listing['listing_title']) {
                        $listing_title = $listing['listing_title'];
                    } else {
                        foreach ($fields as $field_key => $field_value) {
                            // set title
                            if ($iteration == 1) {
                                $listing_title = $field_value['value'];
                            }

                            $iteration++;
                        }
                    }

                    $tmpListing['title'] = $listing_title;
                } else {
                    $tmpListing['price'] = '';
                    $tmpListing['title'] = '';
                }
                $response['featured'][] = $tmpListing;
            }
        } else {
            if ($start < 1) {
                $calc = $GLOBALS['rlListings']->calc;
                $response .= '<featured_count>' . $calc . '</featured_count>';
            }
            $response .= '<featured>';

            foreach ($listings as $index => $listing) {
                $response .= '<listing>';

                // set id
                $response .= '<id><![CDATA[';
                $response .= (int) $listing['ID'];
                $response .= ']]></id>';

                // set main photo
                if ($config['thumbnails_x2'] && $listing['Main_photo_x2']) {
                    $listing['Main_photo'] = $listing['Main_photo_x2'];
                }
                $response .= '<main_photo><![CDATA[';
                $response .= $listing['Main_photo'] ? RL_FILES_URL . $listing['Main_photo'] : '';
                $response .= ']]></main_photo>';

                $fields = $listing['fields'];

                if ($fields) {
                    // set price
                    $response .= '<price><![CDATA[';
                    if ($fields && array_key_exists($this->price_key, $fields)) {
                        $response .= $fields[$this->price_key]['value'];
                        unset($fields[$this->price_key]);
                    }
                    $response .= ']]></price>';

                    $iteration = 1;

                    if ($listing['listing_title']) {
                        $listing_title = $listing['listing_title'];
                    } else {
                        foreach ($fields as $field_key => $field_value) {
                            // set title
                            if ($iteration == 1) {
                                $listing_title = $field_value['value'];
                            }

                            $iteration++;
                        }
                    }

                    $response .= '<title><![CDATA[' . $listing_title . ']]></title>';
                } else {
                    $response .= '<price><![CDATA[]]></price>';
                    $response .= '<title><![CDATA[]]></title>';
                }

                $response .= '</listing>';
            }

            $response .= '</featured>';
        }
    }

    /**
     * Get listings by requsted IDs
     *
     * @param string $IDs        - string or listing ids separated by comma
     * @param string $start      - stack position
     * @param string $account_id - account id
     *
     * @return array - listings
     *
     **/
    public function getListingByIDs($IDs, $start = 1, $account_id = 0)
    {
        $exp_IDs = is_array($IDs) ? $IDs : explode(",", $IDs);

        if (!count($exp_IDs)) {
            if ($GLOBALS['json_support']) {
                $GLOBALS['response_type'] = "json";
                return array('error' => true);
            }
            return;
        }

        $GLOBALS['reefless']->loadClass('Listings');

        /* define start position */
        $limit = $this->grid_listings_limit;
        $start = $start > 1 ? ($start - 1) * $limit : 0;

        $sql = "SELECT SQL_CALC_FOUND_ROWS ";
        $sql .= "`T1`.*, `T4`.`Path`, `T4`.`Type` AS `Listing_type`, `T4`.`Key` AS `Key`, `T4`.`Parent_ID`, ";
        $sql .= "`T4`.`Key` AS `Cat_key`, `T4`.`Parent_IDs`, ";

        $GLOBALS['rlHook']->load('listingsModifyFieldByPeriod');

        $sql .= "IF(`T1`.`Featured_date`, '1', '0') `Featured` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T4` ON `T1`.`Category_ID` = `T4`.`ID` ";

        $GLOBALS['rlHook']->load('listingsModifyJoinByPeriod');

        $sql .= "WHERE `T1`.`Status` = 'active' ";
        $sql .= "AND (`T1`.`ID` = '" . implode("' OR `T1`.`ID` ='", $exp_IDs) . "') ";

        $GLOBALS['rlHook']->load('listingsModifyWhereByPeriod');
        $GLOBALS['rlHook']->load('listingsModifyGroupByPeriod');

        $sql .= "ORDER BY `Featured` DESC ";
        $sql .= "LIMIT {$start}, {$limit}";

        $listings = $GLOBALS['rlDb']->getAll($sql);

        $calc = $GLOBALS['rlDb']->getRow("SELECT FOUND_ROWS() AS `calc`");

        $listings = $GLOBALS['rlLang']->replaceLangKeys($listings, 'categories', 'name');

        foreach ($listings as $key => $value) {
            /* populate fields */
            $fields = $GLOBALS['rlListings']->getFormFields($value['Category_ID'], 'short_forms', $value['Listing_type']);

            foreach ($fields as $fKey => $fValue) {
                if ($field['Condition'] == 'isUrl' || $field['Condition'] == 'isEmail') {
                    $fields[$fKey]['value'] = $listings[$key][$item];
                } else {
                    $fields[$fKey]['value'] = $GLOBALS['rlCommon']->adaptValue(
                        $fValue,
                        $value[$fKey],
                        'listing',
                        $value['ID'],
                        true,
                        false,
                        false,
                        false,
                        $value['Account_ID'],
                        'short_form',
                        $value['Listing_type']
                    );
                }
            }

            $listings[$key]['fields'] = $fields;

            $listings[$key]['listing_title'] = $GLOBALS['rlListings']->getListingTitle($value['Category_ID'], $value, $value['Listing_type']);
        }

        $out = $this->prepareListings($listings, $calc['calc']);

        // return synch ids
        if ($start <= 1 && $account_id) {
            $out['fav_ids'] = implode(',', $exp_IDs);
        }

        return $out;
    }

    /**
     * Get certain listing details
     *
     * @param int $id - listing id
     * @param int $account_id - account id
     *
     * @return array - listing details
     *
     **/
    public function getListingDetails($id, $account_id)
    {
        global $config, $listing_data, $rlDb, $reefless;

        $price = false;

        /* get listing plain data */
        $sql = "SELECT `T1`.*, `T2`.`Path`, `T2`.`Type` AS `Listing_type`, `T2`.`Key` AS `Cat_key`, `T2`.`Type` AS `Cat_type`, ";
        $sql .= "`T3`.`Image`, `T3`.`Image_unlim`, `T3`.`Video`, `T3`.`Video_unlim`, CONCAT('categories+name+', `T2`.`Key`) AS `Category_pName`, ";
        $sql .= "`T2`.`Path` as `Category_path`,`T2`.`Parent_IDs`, ";
        $sql .= "IF(TIMESTAMPDIFF(HOUR, `T1`.`Featured_date`, NOW()) <= `T4`.`Listing_period` * 24 OR `T4`.`Listing_period` = 0, '1', '0') `Featured`, ";
        $sql .= "IF (UNIX_TIMESTAMP(DATE_ADD(`T1`.`Pay_date`, INTERVAL `T3`.`Listing_period` DAY)) <= UNIX_TIMESTAMP(NOW()) AND `T3`.`Listing_period` > 0, 1, 0) AS `Listing_expired` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T3` ON `T1`.`Plan_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T4` ON `T1`.`Featured_ID` = `T4`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T5` ON `T1`.`Account_ID` = `T5`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = {$id} AND ";
        if ($account_id) {
            $sql .= "(`T5`.`Status` = 'active' OR `T1`.`Account_ID` = '{$account_id}') ";
        } else {
            $sql .= "`T1`.`Status` = 'active' ";
        }

        $listing_data = $rlDb->getRow($sql);
        $listing_type = $this->types[$listing_data['Listing_type']];

        $GLOBALS['rlHook']->load('listingDetailsTop');

        $reefless->loadClass('Listings');
        $listings = $GLOBALS['rlListings']->getListingDetails($listing_data['Category_ID'], $listing_data, $listing_type);

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response = array();
            if (!$listings) {
                $response['error'] = true;
                return $response;
            }

            /* count visit */
            if ($config['count_listing_visits']) {
                $GLOBALS['rlListings']->countVisit($id);
            }

            /* get listing title */
            $listing_title = $GLOBALS['rlListings']->getListingTitle($listing_data['Category_ID'], $listing_data, $listing_type['Key']);
            $response['data']['title'] = $listing_title;
            $response['data']['Listing_type'] = $listing_type['Key'];
            $response['data']['Featured'] = $listing_data['Featured'];
            $response['data']['Photos_count'] = $listing_data['Photos_count'];
            if ($config['thumbnails_x2'] && $listing_data['Main_photo_x2']) {
                $listing_data['Main_photo'] = $listing_data['Main_photo_x2'];
            }
            $response['data']['photo_allowed'] = $listing_data['Photos_count'] ? 1 : 0;
            $main_photo = $listing_data['Main_photo'] ? RL_FILES_URL . $listing_data['Main_photo'] : "";
            $response['data']['photo'] = $response['data']['Main_photo'] = $main_photo;

            /* get listing url */
            $response['data']['url'] = $reefless->getListingUrl($listing_data);
            /* get photos */
            $photos_limit = $listing_data['Image_unlim'] ? null : $listing_data['Image'];
            $photos = $rlDb->fetch('*', array('Listing_ID' => $id, 'Status' => 'active', 'Type' => 'picture'), "AND `Thumbnail` <> '' AND `Photo` <> '' ORDER BY `Position`", $photos_limit, 'listing_photos');

            /* populate photos stack */
            foreach ($photos as &$photo) {
                $photo['Large'] = RL_FILES_URL . $photo['Photo'];
                $photo['Thumbnail'] = RL_FILES_URL . $photo['Thumbnail'];
            }
            $response['photos'] = $photos;

            /* remove empty fields */
            foreach ($listings as $key => $group) {
                foreach ($group['Fields'] as $field) {
                    if (!$price && false !== strpos($field['Key'], 'price')) {
                        $price = $field['value'];
                    }
                    if ($field['value'] == "" || !$field['Details_page']) {
                        unset($listings[$key]['Fields'][$field['Key']]);
                        continue;
                    }
                }
            }

            /* populate details stack */
            foreach ($listings as $group) {
                if (empty($group['Fields'])) {
                    continue;
                }

                $groupTmp = array(
                    'key' => $group['Key'],
                    'name' => strip_tags($group['name']),
                    'type' => 'group',
                    'item' => 'group',
                );
                $response['details'][] = $groupTmp;

                foreach ($group['Fields'] as $field) {
                    if (!$price && false !== strpos($field['Key'], 'price')) {
                        $price = $field['value'];
                    }

                    if ($field['value'] == "" || !$field['Details_page']) {
                        continue;
                    }
                    $dataTmp = array(
                        'key' => $field['Key'],
                        'name' => strip_tags($field['name']),
                        'type' => $field['Type'],
                        'value' => $this->adaptValue($field),
                        'item' => 'field',
                    );
                    $response['details'][] = $dataTmp;
                }
            }

            /* get listing video */
            if ($listing_type['Video']) {
                $videos = $rlDb->fetch(array('Photo', 'Thumbnail', 'Original'), array('Listing_ID' => $id, 'Type' => "video"), "ORDER BY `Position`", null, 'listing_photos');

                if ($listing_type['Video'] && $videos) {
                    foreach ($videos as $video) {
                        if ($video['Original'] == 'youtube') {
                            $video['Video'] = $video['Photo'];
                            $video['Preview'] = str_replace('{key}', $video['Photo'], $this->youtube_thumbnail_url);
                            $video['Type'] = "youtube";

                        } else {
                            $video['Preview'] = RL_FILES_URL . $video['Thumbnail'];
                            $video['Video'] = RL_FILES_URL . $video['Original'];
                            $video['Type'] = "local";
                        }

                        $response['videos'][] = $video;
                    }
                }
            }

            /* get comments */
            if ($GLOBALS['plugins']['comment']) {
                $response['comments'] = $this->getComments($id, $account_id, 0, false);
            }

            /* get seller information */
            $GLOBALS['reefless']->loadClass('Account');
            $seller_info = $GLOBALS['rlAccount']->getProfile((int) $listing_data['Account_ID']);

            $response['seller']['ID'] = $seller_info['ID'];
            $response['seller']['name'] = $seller_info['Full_name'];
            $response['seller']['email'] = $seller_info['Mail'];
            $response['seller']['listings_count'] = $seller_info['Listings_count'];
            $response['seller']['thumbnail'] = $seller_info['Photo'] ? RL_FILES_URL . $seller_info['Photo'] : '';

            foreach ($seller_info['Fields'] as $field) {
                if ($field['value'] == '' || !$field['Details_page']) {
                    continue;
                }

                $dataTmp = array(
                    'key' => $field['Key'],
                    'name' => strip_tags($field['name']),
                    'type' => $field['Type'],
                    'value' => $this->adaptValue($field),
                );
                $response['seller_fields'][] = $dataTmp;
            }

            $response['data']['price'] = $price ? $price : "";

            /* build location fields */
            if ($config['address_on_map'] && $listing_data['account_address_on_map']) {
                /* get location data from user account */
                $location = $GLOBALS['rlAccount']->mapLocation;

                if ($seller_info['Loc_latitude'] && $seller_info['Loc_longitude']) {
                    $location['direct'] = $seller_info['Loc_latitude'] . ',' . $seller_info['Loc_longitude'];
                }
            } else {
                /* get location data from listing */
                $fields_list = $GLOBALS['rlListings']->fieldsList;

                $location = false;
                foreach ($fields_list as $key => $value) {
                    if ($fields_list[$key]['Map'] && !empty($listing_data[$fields_list[$key]['Key']])) {
                        $mValue = str_replace("'", "\'", $value['value']);
                        $location['search'] .= $mValue . ', ';
                        $location['show'] .= $lang[$value['pName']] . ': <b>' . $mValue . '<\/b><br />';
                        unset($mValue);
                    }
                }
                if (!empty($location)) {
                    $location['search'] = substr($location['search'], 0, -2);
                }

                if ($listing_data['Loc_latitude'] && $listing_data['Loc_longitude']) {
                    $location['direct'] = $listing_data['Loc_latitude'] . ',' . $listing_data['Loc_longitude'];
                }
            }
            $response['data']['direct'] = $location['direct'];
            $response['data']['search'] = $location['search'];

        } else {
            $this->custom_output = true;

            if (!$listings) {
                $response = '<?xml version="1.0" encoding="UTF-8"?><error>true</error>';
                return $response;
            }

            /* count visit */
            if ($config['count_listing_visits']) {
                $GLOBALS['rlListings']->countVisit($id);
            }

            $response = '<?xml version="1.0" encoding="UTF-8"?><listing>';

            /* get listing title */
            $listing_title = $GLOBALS['rlListings']->getListingTitle($listing_data['Category_ID'], $listing_data, $listing_type['Key']);
            $response .= '<title><![CDATA[' . $listing_title . ']]></title>';

            /* get listing type  */
            $response .= '<listing_type><![CDATA[' . $listing_type['Key'] . ']]></listing_type>';
            $response .= '<featured><![CDATA[' . $listing_data['Featured'] . ']]></featured>';
            $main_photo = $listing_data['Main_photo'] ? RL_FILES_URL . $listing_data['Main_photo'] : "";
            $response .= '<photos_count><![CDATA[' . $listing_data['Photos_count'] . ']]></photos_count>';
            $response .= '<photo><![CDATA[' . $main_photo . ']]></photo>';

            /* get listing url */
            $listing_url = $reefless->getListingUrl($listing_data);
            $response .= '<url><![CDATA[' . $listing_url . ']]></url>';

            /* get photos */
            $photos_limit = $listing_data['Image_unlim'] ? null : $listing_data['Image'];
            $photos = $rlDb->fetch('*', array('Listing_ID' => $id, 'Status' => 'active', 'Type' => 'picture'), "AND `Thumbnail` <> '' AND `Photo` <> '' ORDER BY `Position`", $photos_limit, 'listing_photos');

            /* populate photos stack */
            $response .= '<photos>';
            foreach ($photos as $photo) {
                $response .= '<photo large="' . RL_FILES_URL . $photo['Photo'] . '" thumbnail="' . RL_FILES_URL . $photo['Thumbnail'] . '"><![CDATA[' . $photo['Description'] . ']]></photo>';
            }
            $response .= '</photos>';

            /* remove empty fields */
            foreach ($listings as $key => $group) {
                foreach ($group['Fields'] as $field) {
                    if (!$price && false !== strpos($field['Key'], 'price')) {
                        $price = $field['value'];
                    }
                    if ($field['value'] == "" || !$field['Details_page']) {
                        unset($listings[$key]['Fields'][$field['Key']]);
                        continue;
                    }
                }
            }

            /* populate details stack */
            $response .= '<details>';
            foreach ($listings as $group) {
                if (empty($group['Fields'])) {
                    continue;
                }

                $response .= '<group key="' . $group['Key'] . '" name="' . strip_tags($group['name']) . '">';

                foreach ($group['Fields'] as $field) {
                    if (!$price && false !== strpos($field['Key'], 'price')) {
                        $price = $field['value'];
                    }

                    if ($field['value'] == "" || !$field['Details_page']) {
                        continue;
                    }

                    $response .= '<field key="' . $field['Key'] . '" name="' . strip_tags($field['name']) . '" type="' . $field['Type'] . '"><![CDATA[' . $this->adaptValue($field) . ']]></field>';
                }

                $response .= '</group>';
            }
            $response .= '</details>';

            /* get listing video */
            if ($listing_type['Video']) {
                $videos = $rlDb->fetch(array('Photo', 'Thumbnail', 'Original'), array('Listing_ID' => $id, 'Type' => "video"), "ORDER BY `Position`", null, 'listing_photos');

                $response .= '<videos>';
                if ($listing_type['Video'] && $videos) {
                    foreach ($videos as $video) {
                        if ($video['Original'] == 'youtube') {
                            $video['Video'] = $video['Photo'];
                            $video['Preview'] = str_replace('{key}', $video['Photo'], $this->youtube_thumbnail_url);
                            $video['Type'] = "youtube";

                        } else {
                            $video['Preview'] = RL_FILES_URL . $video['Thumbnail'];
                            $video['Video'] = RL_FILES_URL . $video['Original'];
                            $video['Type'] = "local";
                        }

                        $response .= '<video type="' . $video['Type'] . '" video="' . $video['Video'] . '" preview="' . $video['Preview'] . '" />';
                    }
                }
                $response .= '</videos>';
            }

            /* get comments */
            if ($GLOBALS['plugins']['comment']) {
                $response .= $this->getComments($id, $account_id, 0, false);
            }

            /* get seller information */
            $GLOBALS['reefless']->loadClass('Account');
            $seller_info = $GLOBALS['rlAccount']->getProfile((int) $listing_data['Account_ID']);

            /* populate seller stack */
            $response .= '<seller>';
            $response .= '<id><![CDATA[' . $seller_info['ID'] . ']]></id>';
            $response .= '<name><![CDATA[' . $seller_info['Full_name'] . ']]></name>';
            $response .= '<email><![CDATA[' . $seller_info['Mail'] . ']]></email>';
            $response .= '<listings_count><![CDATA[' . $seller_info['Listings_count'] . ']]></listings_count>';
            if ($seller_info['Photo']) {
                $response .= '<thumbnail><![CDATA[' . RL_FILES_URL . $seller_info['Photo'] . ']]></thumbnail>';
            }
            $response .= '<fields>';

            foreach ($seller_info['Fields'] as $field) {
                if ($field['value'] == '' || !$field['Details_page']) {
                    continue;
                }

                $response .= '<field key="' . $field['Key'] . '" name="' . $field['name'] . '" type="' . $field['Type'] . '"><![CDATA[' . $this->adaptValue($field) . ']]></field>';
            }

            $response .= '</fields>';
            $response .= '</seller>';

            /* set price */
            $response .= '<price><![CDATA[' . $price . ']]></price>';

            /* build location fields */
            if ($config['address_on_map'] && $listing_data['account_address_on_map']) {
                /* get location data from user account */
                $location = $GLOBALS['rlAccount']->mapLocation;

                if ($seller_info['Loc_latitude'] && $seller_info['Loc_longitude']) {
                    $location['direct'] = $seller_info['Loc_latitude'] . ',' . $seller_info['Loc_longitude'];
                }
            } else {
                /* get location data from listing */
                $fields_list = $GLOBALS['rlListings']->fieldsList;

                $location = false;
                foreach ($fields_list as $key => $value) {
                    if ($fields_list[$key]['Map'] && !empty($listing_data[$fields_list[$key]['Key']])) {
                        $mValue = str_replace("'", "\'", $value['value']);
                        $location['search'] .= $mValue . ', ';
                        $location['show'] .= $lang[$value['pName']] . ': <b>' . $mValue . '<\/b><br />';
                        unset($mValue);
                    }
                }
                if (!empty($location)) {
                    $location['search'] = substr($location['search'], 0, -2);
                }

                if ($listing_data['Loc_latitude'] && $listing_data['Loc_longitude']) {
                    $location['direct'] = $listing_data['Loc_latitude'] . ',' . $listing_data['Loc_longitude'];
                }
            }

            /* set location */
            $response .= '<location direct="' . $location['direct'] . '"><![CDATA[' . $location['search'] . ']]></location>';

            $response .= '</listing>';

        }

        return $response;
    }

    /**
     * Get certain account details
     *
     * @param int $id - account id
     *
     * @return array - account details
     *
     **/
    public function getAccountDetails($id)
    {
        $GLOBALS['reefless']->loadClass("Account");
        $GLOBALS['reefless']->loadClass('Listings');

        $account = $GLOBALS['rlAccount']->getProfile($id);

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            if (!$account) {
                return;
            } else {
                foreach ($account['Fields'] as $key => $field) {
                    $account['Fields'][$key]['value'] = $this->adaptValue($field);
                }
                $account['Photo'] = $account['Photo'] ? RL_FILES_URL . $account['Photo'] : '';
                $account['show_adress'] = $GLOBALS['rlAccount']->mapLocation['search'];

                $response['account'] = $account;

                /* get listings */
                $listings = $GLOBALS['rlListings']->getListingsByAccount($id, false, false, 1, $this->grid_listings_limit);
                if ($listings) {
                    $response['listings'] = $this->prepareListings($listings, $GLOBALS['rlListings']->calc);
                }
            }

        } else {

            if (!$account) {
                return;
            }
            $this->custom_output = true;

            $response = '<?xml version="1.0" encoding="UTF-8"?><account>';
            $response .= '<email><![CDATA[' . $account['Mail'] . ']]></email>';

            /* set account fields */
            $response .= '<fields>';

            foreach ($account['Fields'] as $field) {
                if ($field['value'] == '' || !$field['Details_page']) {
                    continue;
                }

                $response .= '<field key="' . $field['Key'] . '" name="' . $field['name'] . '" type="' . $field['Type'] . '"><![CDATA[' . $this->adaptValue($field) . ']]></field>';
            }

            $response .= '</fields>';

            /* set location */
            $location = $GLOBALS['rlAccount']->mapLocation;
            $response .= '<location latitude="' . $account['Loc_latitude'] . '" longitude="' . $account['Loc_longitude'] . '"><![CDATA[' . $location['search'] . ']]></location>';

            /* get listings */
            $listings = $GLOBALS['rlListings']->getListingsByAccount($id, false, false, 1, $this->grid_listings_limit);
            if ($listings) {
                $calc = $GLOBALS['rlListings']->calc;

                $response .= '<listings>';
                foreach ($this->prepareListings($listings, $calc) as $listing) {
                    $nod_name = $listing['node_name'] ? $listing['node_name'] : 'item';
                    unset($listing['node_name']);

                    $response .= '<' . $nod_name . '>';
                    foreach ($listing as $key => $value) {
                        $response .= '<' . strtolower($key) . '><![CDATA[' . $value . ']]></' . strtolower($key) . '>';
                    }
                    $response .= '</' . $nod_name . '>';
                }
                $response .= '</listings>';
            }

            $response .= '</account>';
        }

        return $response;
    }

    /**
     * Get search forms for available listing types
     *
     * @param string  $response - xml response
     * @param boolean $json     - response type
     *
     **/
    public function getSearchForms(&$response, $json = false)
    {
        global $lang, $config, $rlDb, $rlLang;

        $GLOBALS['reefless']->loadClass('Search');
        $GLOBALS['reefless']->loadClass('Categories');

        if ($GLOBALS['plugins']['multiField']) {
            $sql = "SELECT * FROM `{db_prefix}multi_formats` AS `T1`";
            $sql .= "JOIN `{db_prefix}listing_fields` AS `T2` ON `T2`.`Condition` = `T1`.`Key` ";
            $sql .= "WHERE `T1`.`Status` = 'active'";

            if ($this->isMultiFieldNew()) {
                $sql .= " AND `T1`.`Parent_ID` = 0";
            }

            $mf_tmp = $rlDb->getAll($sql);

            foreach ($mf_tmp as $key => $item) {
                $multi_fields[$item['Key']] = true;
            }
        }

        if ($json) {
            foreach ($this->types as $type_key => $listing_type) {
                if ($listing_type['Search_page']) {
                    if ($search_form = $GLOBALS['rlSearch']->buildSearch($type_key . '_quick', $type_key)) {
                        foreach ($search_form as $field) {
                            $tmpField = array();
                            $data = '';
                            switch ($field['Fields'][0]['Type']) {
                                case 'select':
                                    if ($multi_fields[$field['Fields'][0]['Key']]) {
                                        $data = 'multiField';
                                        if (false !== strpos($field['Fields'][0]['Key'], '_level')) {
                                            unset($field['Fields'][0]['Values']);
                                        } else {
                                            $GLOBALS['reefless']->loadClass('MultiField', null, 'multiField');
                                            if (method_exists($GLOBALS['rlMultiField'], 'getPhrases')) {
                                                $GLOBALS['rlMultiField']->getPhrases($field['Fields'][0]['Condition']);
                                            }
                                        }
                                    } elseif ($field['Fields'][0]['Key'] == 'Category_ID' && $listing_type['Search_multi_categories']) {
                                        $data = $listing_type['Search_multicat_levels'];
                                    } elseif ($field['Fields'][0]['Condition'] == 'years') {
                                        $data = "years";
                                    }
                                    break;
                            }

                            $tmpField['Key'] = $field['Fields'][0]['Key'] == 'Category_ID' ? $field['Fields'][0]['Key'] . '|' . $type_key : $field['Fields'][0]['Key'];
                            $tmpField['type'] = $field['Fields'][0]['Type'];
                            $tmpField['data'] = $data;
                            $tmpField['name'] = $lang[$field['Fields'][0]['pName']];

                            if (is_array($field['Fields'][0]['Values'])) {
                                foreach ($field['Fields'][0]['Values'] as $item) {
                                    $tmpVal = array();
                                    if ((bool) preg_match('/^Category_ID/', $field['Fields'][0]['Key'])) {
                                        $item['margin'] = (int) $item['margin'] >= 5 ? ceil(($item['margin'] - 5) * 2) : $item['margin'];
                                        $item['margin'] = $item['margin'] ? $item['margin'] : 0;
                                        $set_name = strip_tags($rlLang->getPhrase($item['pName'], null, null, true));
                                        if ($listing_type['Cat_listing_counter'] && $item['Count'] > 0) {
                                            $set_name .= " ({$item['Count']})";
                                        }
                                        $tmpVal['name'] = $set_name;
                                        $tmpVal['Key'] = $item['ID'];
                                        $tmpVal['margin'] = $item['margin'];

                                    } elseif ($field['Fields'][0]['Key'] == 'posted_by') {
                                        $tmpVal['name'] = $lang[$item['pName']];
                                        $tmpVal['Key'] = $item['ID'];
                                    } else {
                                        switch ($field['Fields'][0]['Type']) {
                                            case 'checkbox':
                                            case 'radio':
                                                if ($field['Fields'][0]['Condition']) {
                                                    $item['Key'] = $item['Key'];
                                                } else {
                                                    $item['Key'] = $item['ID'];
                                                }
                                                $tmpVal['name'] = $lang[$item['pName']];
                                                $tmpVal['Key'] = $item['Key'];
                                                break;

                                            case 'select':
                                                if ($field['Fields'][0]['Condition']) {
                                                    $item['Key'] = $item['Key'];
                                                } else {
                                                    $item['Key'] = $item['ID'];
                                                }
                                                $field_name = $lang[$item['pName']] ? $lang[$item['pName']] : $item['name'];
                                                $tmpVal['name'] = $field_name;
                                                $tmpVal['Key'] = $item['Key'];
                                                break;

                                            default:
                                                $tmpVal['name'] = $lang[$item['pName']];
                                                $tmpVal['Key'] = $item['Key'];
                                                break;
                                        }
                                    }

                                    $tmpField['value'][] = $tmpVal;
                                }
                            } elseif ($field['Fields'][0]['Type'] == 'price') {
                                foreach ($GLOBALS['rlCategories']->getDF('currency') as $currency_item) {
                                    $tmpVal['name'] = $currency_item['name'];
                                    $tmpVal['Key'] = $currency_item['Key'];
                                    $tmpField['value'][] = $tmpVal;
                                }
                            } elseif (false !== strpos($field['Fields'][0]['Key'], 'zip')) {
                                if (!$config['sbd_distance_items']) {
                                    $tmpVal['name'] = $lang['sbd_distance'];
                                    $tmpVal['Key'] = "";
                                    $tmpField['value'][] = $tmpVal;
                                }

                                $units = $config['sbd_units'] == 'kilometres' ? strip_tags($lang['sbd_km']) : strip_tags($lang['sbd_mi']);
                                foreach (explode(',', $config['sbd_distance_items']) as $mile) {
                                    $tmpVal['name'] = $mile . ' ' . $units;
                                    $tmpVal['Key'] = $mile;
                                    $tmpField['value'][] = $tmpVal;
                                }
                            }

                            $response['search_forms'][$type_key][] = $tmpField;
                        }
                    }
                }
            }

        } else {
            $response .= '<search_forms>';
            /* get search forms */
            foreach ($this->types as $type_key => $listing_type) {
                if ($listing_type['Search_page']) {
                    if ($search_form = $GLOBALS['rlSearch']->buildSearch($type_key . '_quick', $type_key)) {
                        $response .= '<form type="' . $type_key . '">';

                        foreach ($search_form as $field) {

                            switch ($field['Fields'][0]['Type']) {

                                case 'number':
                                    if (false !== strpos($field['Fields'][0]['Key'], 'zip')) {
                                        // TODO in case of zip code
                                    } else {
                                        $sql = "SELECT MIN(ROUND(`{$field['Fields'][0]['Key']}`)) AS `min`, MAX(ROUND(`{$field['Fields'][0]['Key']}`)) AS `max` ";
                                        $sql .= "FROM `{db_prefix}listings` ";
                                        $sql .= "WHERE `Status` = 'active'";
                                        $max = $rlDb->getRow($sql);
                                        $max['min'] = !$max['min'] || $max['min'] < 0 ? 0 : (int) $max['min'];

                                        $data = $max['min'] . '-' . $max['max'];
                                    }
                                    break;
                                case 'select':
                                    if ($multi_fields[$field['Fields'][0]['Key']]) {
                                        $data = 'multiField';
                                        if (false !== strpos($field['Fields'][0]['Key'], '_level')) {
                                            unset($field['Fields'][0]['Values']);
                                        }
                                    } elseif ($field['Fields'][0]['Key'] == 'Category_ID' && $listing_type['Search_multi_categories']) {
                                        $data = $listing_type['Search_multicat_levels'];
                                    } elseif ($field['Fields'][0]['Condition'] == 'years') {
                                        $data = "years";
                                    }
                                    break;
                            }

                            /* re-define the field key because fields will be stored in single array and keys maybe be overwrited */
                            if ($field['Fields'][0]['Key'] == 'Category_ID') {
                                $field['Fields'][0]['Key'] .= '|' . $type_key;
                            }

                            $response .= '<field name="' . strip_tags($lang[$field['Fields'][0]['pName']]) . '" type="' . $field['Fields'][0]['Type'] . '" key="' . $field['Fields'][0]['Key'] . '" data="' . $data . '">';

                            /* collect possible field items */
                            if (is_array($field['Fields'][0]['Values'])) {
                                foreach ($field['Fields'][0]['Values'] as $item) {
                                    if ((bool) preg_match('/^Category_ID/', $field['Fields'][0]['Key'])) {
                                        $item['margin'] = (int) $item['margin'] >= 5 ? ceil(($item['margin'] - 5) * 2) : $item['margin'];
                                        $item['margin'] = $item['margin'] ? $item['margin'] : 0;
                                        $set_name = strip_tags($lang[$item['pName']]);
                                        if ($listing_type['Cat_listing_counter'] && $item['Count'] > 0) {
                                            $set_name .= " ({$item['Count']})";
                                        }
                                        $response .= '<item name="' . $set_name . '" key="' . $item['ID'] . '" margin="' . $item['margin'] . '" />';
                                    } elseif ($field['Fields'][0]['Key'] == 'posted_by') {
                                        $response .= '<item name="' . strip_tags($lang[$item['pName']]) . '" key="' . $item['ID'] . '" />';
                                    } else {
                                        switch ($field['Fields'][0]['Type']) {
                                            case 'checkbox':
                                            case 'radio':
                                                if ($field['Fields'][0]['Condition']) {
                                                    $item['Key'] = $item['Key'];
                                                } else {
                                                    $item['Key'] = $item['ID'];
                                                }
                                                $response .= '<item name="' . strip_tags($lang[$item['pName']]) . '" key="' . $item['Key'] . '" />';
                                                break;

                                            case 'select':
                                                if ($field['Fields'][0]['Condition']) {
                                                    $item['Key'] = $item['Key'];
                                                } else {
                                                    $item['Key'] = $item['ID'];
                                                }
                                                $field_name = $item['name'] ? $item['name'] : $lang[$item['pName']];
                                                $response .= '<item name="' . strip_tags($field_name) . '" key="' . $item['Key'] . '" />';
                                                break;

                                            default:
                                                $response .= '<item name="' . strip_tags($lang[$item['pName']]) . '" key="' . $item['Key'] . '" />';
                                                break;
                                        }
                                    }
                                }
                            } elseif ($field['Fields'][0]['Type'] == 'price') {
                                foreach ($GLOBALS['rlCategories']->getDF('currency') as $currency_item) {
                                    $response .= '<item name="' . $currency_item['name'] . '" key="' . $currency_item['Key'] . '" />';
                                }
                            } elseif (false !== strpos($field['Fields'][0]['Key'], 'zip')) {
                                $response .= '<item name="' . strip_tags($lang['sbd_distance']) . '" key="" />';

                                $units = $config['sbd_units'] == 'kilometres' ? strip_tags($lang['sbd_km']) : strip_tags($lang['sbd_mi']);
                                foreach (explode(',', $config['sbd_distance_items']) as $mile) {
                                    $response .= '<item name="' . $mile . ' ' . $units . '" key="' . $mile . '" />';
                                }
                            }
                            $response .= '</field>';

                            unset($data);
                        }

                        $response .= '</form>';
                    }
                }
            }

            $response .= '</search_forms>';
        }
    }

    /**
     * Get search forms for available account types
     *
     * @param string $response - xml response
     * @param boolean $json     - true or false
     *
     **/
    public function getAccountSearchForms(&$response, $json = false)
    {
        global $lang, $config, $rlDb;

        if ($GLOBALS['plugins']['multiField']) {
            $sql = "SELECT * FROM `{db_prefix}multi_formats` ";
            $sql .= "WHERE `Status` = 'active'";

            if ($this->isMultiFieldNew()) {
                $sql .= " AND `Parent_ID` = 0";
            }

            global $multi_formats;
            $mf_tmp = $rlDb->getAll($sql);
            foreach ($mf_tmp as $key => $item) {
                $multi_formats[$item['Key']] = $item;
            }
        }
        $GLOBALS['reefless']->loadClass('Account');

        if ($json) {
            foreach ($this->account_types as $type) {
                if (!$type['Page']) {
                    continue;
                }
                if ($fields = $GLOBALS['rlAccount']->buildSearch($type['ID'])) {
                    foreach ($fields as $field) {
                        $tmpField = array();

                        $tmpField['Key'] = $field['Key'];
                        $tmpField['type'] = $field['Type'];
                        $tmpField['name'] = $lang[$field['pName']];
                        $tmpField['data'] = "";

                        switch ($field['Type']) {
                            case 'select':
                                if ($multi_formats[$field['Condition']]) {
                                    $tmpField['data'] = 'multiField';

                                    if (false !== strpos($field['Key'], '_level')) {
                                        unset($field['Values']);
                                    }
                                } elseif ($field['Condition'] == 'years') {
                                    $tmpField['data'] = "years";
                                }

                                break;
                        }

                        /* collect possible field items */
                        if (is_array($field['Values'])) {
                            foreach ($field['Values'] as $item) {
                                switch ($field['Type']) {
                                    case 'checkbox':
                                    case 'radio':
                                        if ($field['Fields'][0]['Condition']) {
                                            $item['Key'] = $item['Key'];
                                        } else {
                                            $item['Key'] = $item['ID'];
                                        }
                                        $tmpVal['Key'] = $item['Key'];
                                        $tmpVal['name'] = $lang[$item['pName']];
                                        break;

                                    default:
                                        $tmpVal['name'] = $lang[$item['pName']];
                                        $tmpVal['Key'] = $item['Key'];
                                        break;
                                }
                                $tmpField['value'][] = $tmpVal;
                            }
                        } elseif ($field['Type'] == 'price') {
                            foreach ($GLOBALS['rlCategories']->getDF('currency') as $currency_item) {
                                $tmpVal['name'] = $currency_item['name'];
                                $tmpVal['Key'] = $currency_item['Key'];

                                $tmpField['value'][] = $tmpVal;
                            }
                        } elseif (false !== strpos($field['Key'], 'zip')) {
                            $tmpVal['name'] = $lang['sbd_distance'];
                            $tmpVal['Key'] = "";
                            $tmpField['value'][] = $tmpVal;

                            $units = $config['sbd_units'] == 'kilometres' ? strip_tags($lang['sbd_km']) : strip_tags($lang['sbd_mi']);
                            foreach (explode(',', $config['sbd_distance_items']) as $mile) {
                                $tmpVal['name'] = $mile . ' ' . $units;
                                $tmpVal['Key'] = $mile;
                                $tmpField['value'][] = $tmpVal;
                            }
                        }
                        $response['account_search_forms'][$type['Key']][] = $tmpField;
                    }
                }
            }

        } else {
            $response .= '<account_search_forms>';
            foreach ($this->account_types as $type) {
                if (!$type['Page']) {
                    continue;
                }

                if ($fields = $GLOBALS['rlAccount']->buildSearch($type['ID'])) {
                    $response .= '<form type="' . $type['Key'] . '">';

                    foreach ($fields as $field) {
                        switch ($field['Type']) {
                            case 'price':
                                $sql = "SELECT MAX(ROUND(`{$field['Key']}`)) AS `max` ";
                                $sql .= "FROM `{db_prefix}accounts` ";
                                $sql .= "WHERE `Status` = 'active'";
                                $max = $rlDb->getRow($sql);

                                $data = $max['max'] > 1000000 ? 1000000 : round($max['max']);
                                break;

                            case 'number':
                                if (false !== strpos($field['Key'], 'zip')) {
                                    // TODO in case of zip code
                                } else {
                                    $sql = "SELECT MIN(ROUND(`{$field['Key']}`)) AS `min`, MAX(ROUND(`{$field['Key']}`)) AS `max` ";
                                    $sql .= "FROM `{db_prefix}accounts` ";
                                    $sql .= "WHERE `Status` = 'active'";
                                    $max = $rlDb->getRow($sql);

                                    $data = $max['min'] . '-' . $max['max'];
                                }
                                break;
                            case 'select':
                                if ($multi_formats[$field['Condition']]) {
                                    $data = 'multiField';

                                    if (false !== strpos($field['Key'], '_level')) {
                                        unset($field['Values']);
                                    }
                                }
                                break;
                        }

                        $response .= '<field name="' . strip_tags($lang[$field['pName']]) . '" type="' . $field['Type'] . '" key="' . $field['Key'] . '" data="' . $data . '">';

                        /* collect possible field items */
                        if (is_array($field['Values'])) {
                            foreach ($field['Values'] as $item) {
                                switch ($field['Type']) {
                                    case 'checkbox':
                                    case 'radio':
                                        if ($field['Fields'][0]['Condition']) {
                                            $item['Key'] = $item['Key'];
                                        } else {
                                            $item['Key'] = $item['ID'];
                                        }
                                        $response .= '<item name="' . strip_tags($lang[$item['pName']]) . '" key="' . $item['Key'] . '" />';
                                        break;

                                    default:
                                        $response .= '<item name="' . strip_tags($lang[$item['pName']]) . '" key="' . $item['Key'] . '" />';
                                        break;
                                }
                            }
                        } elseif ($field['Type'] == 'price') {
                            foreach ($GLOBALS['rlCategories']->getDF('currency') as $currency_item) {
                                $response .= '<item name="' . $currency_item['name'] . '" key="' . $currency_item['Key'] . '" />';
                            }
                        } elseif (false !== strpos($field['Key'], 'zip')) {
                            $response .= '<item name="' . strip_tags($lang['sbd_distance']) . '" key="" />';

                            $units = $config['sbd_units'] == 'kilometres' ? strip_tags($lang['sbd_km']) : strip_tags($lang['sbd_mi']);
                            foreach (explode(',', $config['sbd_distance_items']) as $mile) {
                                $response .= '<item name="' . $mile . ' ' . $units . '" key="' . $mile . '" />';
                            }
                        }
                        $response .= '</field>';

                        unset($data);
                    }

                    $response .= '</form>';
                }
            }
            $response .= '</account_search_forms>';
        }
    }

    /**
     * Prepare listings array for xml responce
     *
     * @param array $listings - referent to original listings array
     * @param int   $count    - total listings count from CALC
     * @param array $sorting  - sorting fields array
     *
     * @return array $listings - response type: xml or json
     *
     **/
    public function getUserData(&$response, $username, $passwordHash, $json = false)
    {
        if (empty($username) || empty($passwordHash)) {
            return;
        }

        /* check for user data */
        $access_id = $GLOBALS['rlDb']->getOne('ID', "`Username` = '{$username}' AND `Password` = '{$passwordHash}' AND `Status` IN ('active', 'pending', 'expired', 'incomplete')", 'accounts');
        if (!$access_id) {
            return;
        }

        if ($json) {
            $response['account'] = $_SESSION['account'] = $this->fetchAccountData($access_id);
        } else {
            /* add account data to the response */
            $response .= '<account>';
            $response .= $this->toXmlNode($this->fetchAccountData($access_id), array('Password_tmp'));
            $response .= '</account>';
        }
    }

    /**
     * Search result
     *
     * @param string $data  - post data
     * @param string $type  - listing type
     * @param int    $start - start search
     * @param string $sort  - sort field
     *
     * @return mixed $listings - response type: xml or json
     *
     **/
    public function searchResults($data, $type, $start = 1, $sort = null)
    {
        global $sorting, $form_key, $lang;

        if (!$type || !$data) {
            return false;
        }

        $form_key = $form_key ? $form_key : $type . '_quick';

        $GLOBALS['reefless']->loadClass('Search');

        /* get sorting fields */
        $GLOBALS['rlSearch']->getFields($form_key, $type);
        $date_field['date'] = array('Key' => 'date', 'Type' => 'date', 'name' => $lang['date']);
        $sorting = is_array($GLOBALS['rlSearch']->fields) ? array_merge($date_field, $GLOBALS['rlSearch']->fields) : $date_field;

        /* adapt sorting array */
        if ($sorting) {
            foreach ($sorting as &$field) {
                if ($field['Key'] == 'keyword_search') {
                    unset($sorting['keyword_search']);
                    continue;
                }

                if (!$field['Details_page']) {
                    unset($field);
                    continue;
                }

                foreach ($field as $item_key => $value) {
                    if (!in_array($item_key, $this->transfer_sorting_fields)) {
                        unset($field[$item_key]);
                    }
                }
            }
        }

        $GLOBALS['reefless']->loadClass('Search');

        $GLOBALS['rlSearch']->getFields($form_key, $type);
        $fields = $GLOBALS['rlSearch']->fields;

        if (!$fields) {
            $GLOBALS['rlDebug']->logger("ANDROID: searchResults, no fields by form found");
        }

        $form_data = is_array($data) ? $data : $this->preparePostFields($fields, $data);

        if ($sort) {
            $sort = explode('|/|', $sort);

            if ($sorting[$sort[0]]) {
                $form_data['sort_by'] = $sort[0];
                $form_data['sort_type'] = $sort[1];
            }
        }
        if ($GLOBALS['plugins']['currencyConverter']) {
            $this->enableCurrencyConverter();
        }

        if (AdsFilter::isActive()) {
            AdsFilter::initFor(AdsFilter::FILTER_SEARCH_RESULTS);

            if (false !== $filterBoxId = AdsFilter::fetchBoxId($type)) {
                $filters = json_decode($_REQUEST['filters'], true);

                AdsFilter::setBoxId($filterBoxId);
                AdsFilter::prepareFilters($filters, $form_data);
            }
        }

        $listings = $GLOBALS['rlSearch']->search($form_data, $type, $start, $this->grid_listings_limit);

        $filterFields = [];
        if (AdsFilter::isActive()) {
            AdsFilter::applyFiltersToResponse($filterFields);
        }

        return $this->prepareListings($listings, $GLOBALS['rlSearch']->calc, $sorting, $filterFields);
    }

    /**
     * Enable currency hook
     **/
    public function enableCurrencyConverter()
    {
        $GLOBALS['reefless']->loadClass('CurrencyConverter', false, 'currencyConverter');

        if (!method_exists($GLOBALS['rlCurrencyConverter'], 'getRates')) {
            eval($GLOBALS['rlDb']->getOne('Code', "`Name` = 'specialBlock' AND `Plugin` = 'currencyConverter'", 'hooks'));
        }
    }

    /**
     * Save Search
     *
     * @param int     $account_id - account id
     * @param string  $password   - account password
     *
     * @return mixed  $response   - response result in xml
     *
     **/
    public function saveSearch($account_id, $password_hash)
    {
        global $lang, $rlDb;

        if (!$account_id || !$password_hash) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no username or passwordHash received, abort");
            return false;
        }

        if (!$this->issetAccount($account_id, $password_hash)) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), wrong login details provided, abort");
            return false;
        }

        $type = $GLOBALS['rlValid']->xSql($_REQUEST['type']);
        $form_key = $type . '_quick';

        $GLOBALS['reefless']->loadClass('Search');

        /* get sorting fields */
        $GLOBALS['rlSearch']->getFields($form_key, $type);
        $fields = $GLOBALS['rlSearch']->fields;

        $data = trim(urldecode($_REQUEST['data']), '{}');
        $form_data = $this->preparePostFields($fields, $data);

        unset($form_data['sort_type']);
        unset($form_data['sort_by']);

        foreach ($form_data as $key => $value) {
            if ($form_data[$key]['from'] == $lang['from']) {
                $form_data[$key]['from'] = "";
            }
            if ($form_data[$key]['to'] == $lang['to']) {
                $form_data[$key]['to'] = "";
            }

            // escort package && availability field
            if (($form_data[$key]['day'] && intval($form_data[$key]['day']) < 0)
                && ($form_data[$key]['time'] && intval($form_data[$key]['time']) < 0)) {
                unset($form_data[$key]);
            }

            if (empty($form_data[$key])) {
                unset($form_data[$key]);
            }
            if (isset($form_data[$key]['from']) && (empty($form_data[$key]['from']) && empty($form_data[$key]['to']))) {
                unset($form_data[$key]);
            }
            if (isset($form_data[$key][0]) && is_array($form_data[$key])) {
                unset($form_data[$key][0]);

                if (empty($content[$key])) {
                    unset($content[$key]);
                }
            }
            if ($form_data[$key]['distance'] && !$form_data[$key]['zip']) {
                unset($form_data[$key]);
            }
        }

        if (!empty($form_data)) {

            $content = serialize($GLOBALS['rlValid']->xSql($form_data));

            $exist = $GLOBALS['rlDb']->fetch(array('ID'), array('Content' => $content, 'Account_ID' => $account_id), null, 1, 'saved_search', 'row');

            if (empty($exist)) {
                $insert = array(
                    'Account_ID' => $account_id,
                    'Form_key' => $form_key,
                    'Listing_type' => $type,
                    'Content' => $content,
                    'Date' => 'NOW()',
                );

                $rlDb->rlAllowHTML = true;
                $rlDb->insertOne($insert, 'saved_search');
                $item_id = $rlDb->insertID();

                $out['success']['ID'] = $item_id;
                $out['success']['item'] = $lang['search_saved'];
            } else {
                $out['error'] = $lang['search_already_saved'];
            }
        } else {
            $out['error'] = $lang['empty_search_disallow'];
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response = $out;
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';
            if ($out['error']) {
                $response .= '<error><![CDATA[' . $out['error'] . ']]></error>';
            } else {
                $response .= '<success>';
                $response .= '<id><![CDATA[' . $out['success']['ID'] . ']]></item>';
                $response .= '<item><![CDATA[' . $out['success']['item'] . ']]></item>';
                $response .= '</success>';
            }
        }

        return $response;
    }

    /**
     * Run Save Search
     *
     * @param int    $id        - save search id
     * @param int    $start     - start
     * @param string $find_ids  - find_ids
     * @param string $sort      - sort
     *
     * @return mixed $response - response result in xml
     **/
    public function runSaveSearch($id, $start, $find_ids, $sort)
    {
        global $form_key;
        $entry = $GLOBALS['rlDb']->getRow("
            SELECT `Content`, `Form_key`, `Listing_type`
            FROM `{db_prefix}saved_search`
            WHERE `ID` = {$id}
        ");

        $form_key = $entry['Form_key'];
        $data = unserialize($entry['Content']);
        $type = $entry['Listing_type'];

        if ($find_ids && is_string($find_ids) && $find_ids !== '') {
            define('ANDROID_SAVED_SEARCH_IDS', $find_ids);
        }

        return $this->searchResults($data, $type, $start, $sort);

    }

    /**
     * Get my Save Search
     *
     * @param int     $account_id - account id
     * @param string  $password   - account password
     *
     * @return mixed $response    - response result in xml
     *
     **/
    public function getMySaveSearch($account_id, $password_hash)
    {
        global $lang, $rlHook, $rlCommon, $rlLang, $rlDb;

        if (!$account_id || !$password_hash) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no username or passwordHash received, abort");
            return false;
        }

        if (!$this->issetAccount($account_id, $password_hash)) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), wrong login details provided, abort");
            return false;
        }

        // get save search
        $saved_search = $rlDb->fetch(
            array('ID', 'Content', 'Date', 'Listing_type', 'Matches', 'Status'),
            array('Account_ID' => $account_id),
            "ORDER BY `ID`",
            null,
            'saved_search'
        );

        $rlHook->load('savedSearchTop');

        if (!empty($saved_search)) {

            $tmp_fields = $rlDb->fetch(array('Key', 'Type', 'Condition', 'Default'), array('Status' => 'active'), null, null, 'listing_fields');
            $tmp_fields = $rlLang->replaceLangKeys($tmp_fields, 'listing_fields', array('name'));

            $fields = array();
            foreach ($tmp_fields as $tmp_key => $tmp_field) {
                $fields[$tmp_field['Key']] = $tmp_field;
            }
            unset($tmp_fields);

            foreach ($saved_search as $key => $value) {
                $content = unserialize($saved_search[$key]['Content']);

                $saved_search[$key]['Date'] = $this->convertDate($value['Date']);
                $saved_search[$key]['title'] = $lang['listing_types+name+' . $value['Listing_type']];
                $tmp_content = false;
                $step = 0;
                $fields_value = array();
                foreach ($content as $cKey => $cVal) {
                    if (isset($fields[$cKey])) {

                        switch ($fields[$cKey]['Type']) {
                            case 'mixed':
                                $tmp_content[$step]['value'] = $content[$cKey];
                                if (empty($fields[$cKey]['Condition'])) {
                                    $fields_value[] = $lang['listing_fields+name+' . $content[$cKey]['df']];
                                } else {
                                    $fields_value[] = $lang['data_formats+name+' . $content[$cKey]['df']];
                                }
                                $saved_search[$key]['fields'][$cKey] = $content[$cKey];
                                break;

                            case 'date':
                                $fields_value[] = $content[$cKey];
                                $saved_search[$key]['fields'][$cKey] = $content[$cKey];
                                break;

                            case 'number':
                                $fields_value[] = $content[$cKey]['from'] . '-' . $content[$cKey]['to'];
                                $saved_search[$key]['fields'][$cKey] = $content[$cKey]['from'] . '-' . $content[$cKey]['to'];
                                break;

                            case 'price':
                                if (!$system_currency) {
                                    $system_currency = $GLOBALS['rlCategories']->getDF('currency');
                                }
                                $tmp_content_val = $content[$cKey]['from'] . '-' . $content[$cKey]['to'] . ' ';
                                $tmp_content_val .= !$content[$cKey]['currency'] && count($system_currency) == 1
                                ? $lang[$system_currency[0]['pName']]
                                : $lang['data_formats+name+' . $content[$cKey]['currency']];

                                $fields_value[] = $tmp_content_val;
                                $saved_search[$key]['fields'][$cKey] = $content[$cKey]['from'] . '-' . $content[$cKey]['to'] . '|' . $content[$cKey]['currency'];
                                break;

                            case 'unit':
                                $fields_value[] = $content[$cKey] . " " . $lang['data_formats+name+' . $content[$cKey]['unit']];
                                break;

                            case 'checkbox':
                                $fields_value[] = $rlCommon->adaptValue($fields[$cKey], implode(',', $content[$cKey]));
                                $saved_search[$key]['fields'][$cKey] = implode(',', $content[$cKey]);
                                break;

                            default:

                                if ($fields[$cKey]['Key'] == 'Category_ID') {
                                    $cat_key = $rlDb->getOne('Key', "`ID` = {$content[$cKey]}", 'categories');
                                    $cat_name = $GLOBALS['rlLang']->getPhrase('categories+name+' . $cat_key, null, null, true);

                                    $tmp_content[$step]['value'] = $cat_name;
                                    $saved_search[$key]['title'] .= " / " . $cat_name;
                                    $saved_search[$key]['fields'][$cKey] = $content[$cKey];

                                } elseif ($fields[$cKey]['Condition'] == 'years') {
                                    $fields_value[] = $content[$cKey]['from'] . '-' . $content[$cKey]['to'];
                                    $saved_search[$key]['fields'][$cKey] = $content[$cKey]['from'] . '-' . $content[$cKey]['to'];
                                } elseif ($fields[$cKey]['Key'] == 'posted_by') {
                                    $account_type = $GLOBALS['rlAccount']->getTypeDetails($cVal);
                                    $fields_value[] = $account_type['name'] ? $account_type['name'] : strtoupper($cVal);
                                    $saved_search[$key]['fields'][$cKey] = $cVal;
                                } else {
                                    $fields_value[] = $rlCommon->adaptValue($fields[$cKey], $content[$cKey]);
                                    $saved_search[$key]['fields'][$cKey] = $content[$cKey];
                                }

                                break;
                        }
                    }
                    $step++;
                }

                $saved_search[$key]['fields_value'] = implode(", ", $fields_value);
                unset($tmp_content, $saved_search[$key]['Content']);
            }
            unset($fields, $content);

            $rlHook->load('savedSearchBottom');
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response = $saved_search;
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';
            $response .= '<items>';
            $response .= $this->printValue($saved_search);
            $response .= '</items>';
        }

        return $response;
    }

    /**
     * @since 4.0.0 in the plugin
     *
     * @param array $field   - field info
     * @param array $listing - listing data
     * @param array $data    - data
     *
     */
    public function androidAdaptFormFieldEscort(&$field, &$listing_data, &$data)
    {
        global $lang;

        if ($GLOBALS['rlEscort']) {
            $key_upper = $GLOBALS['json_support'] ? 'Key' : 'key';
            switch ($field['Key']) {
                case 'availability':
                    $availabilityTmp = $GLOBALS['rlEscort']->availabilityFormData();
                    $availability = array();
                    foreach ($availabilityTmp as $key => $availabilityItem) {
                        foreach ($availabilityItem as $itemKey => $item) {
                            if ($key == "days") {
                                $availability[$key][] = array($key_upper => $item['day'], 'name' => $item['title']);
                            } else {
                                $availability[$key][] = array($key_upper => $itemKey, 'name' => $item);
                            }
                        }
                    }
                    $field['Values'] = $availability;

                    break;

                case 'escort_rates':
                    $arrayName = array();
                    foreach ($field['Values'] as $curV) {
                        $arrayName['values'][] = array($key_upper => $curV[$key_upper], 'name' => $lang[$curV['pName']] ? $lang[$curV['pName']] : $curV['name']);
                    }
                    // added custom tag
                    $arrayName['values'][] = array($key_upper => '*cust0m*', 'name' => $lang['escort_custom_rate']);

                    foreach ($GLOBALS['rlCategories']->getDF('currency') as $curItem) {
                        $arrayName['currency'][] = array($key_upper => $curItem['Key'], 'name' => $lang[$curItem['pName']] ? $lang[$curItem['pName']] : $curItem['name']);
                    }
                    $field['Values'] = $arrayName;

                    $listing_data[$field['Key']] = $this->escortRatesPostSimulation($listing_data['ID']);

                    break;

                case 'escort_tours':
                    $listing_data[$field['Key']] = $GLOBALS['rlDb']->fetch('*', array('Listing_ID' => $listing_data['ID']), 'ORDER BY `ID`', null, 'escort_tours');

                    break;
            }
        }
    }

    /**
     * @since 4.0.0 in the plugin
     *
     * @param array  $out    - out data
     * @param array  $fields - field info
     * @param string $key    - key
     * @param string $value  - value
     *
     **/
    public function androidAdaptDataItemEscort(&$out, $fields, $key, $value)
    {
        global $lang;

        if ($GLOBALS['rlEscort']) {
            switch ($key) {
                case 'availability':
                    $availabilityTmp = array();
                    for ($i = 0; $i < 7; $i++) {
                        $new_key = $key . '_' . $i;
                        $ad = explode('-', $_POST[$new_key]);
                        $array = array('from' => $ad[0], 'to' => $ad[1]);
                        $out[$new_key] = $array;
                        $_POST['f'][$new_key] = $array;
                    }

                    break;
                case 'escort_rates':
                    $escort_rates = array();
                    foreach ($_POST as $eKey => $eVal) {
                        if (strpos($eKey, $key) !== false && $eKey != $key) {
                            $index = array_reverse(explode("_", $eKey));
                            $rate = explode('|', $eVal);
                            $escort_rates[$index[0]] = array(
                                'rate' => $rate[0],
                                'custom_rate' => $rate[1],
                                'price' => $rate[2],
                                'currency' => $rate[3],
                            );
                        }
                    }
                    ksort($escort_rates);
                    $_POST['f'][$key] = $escort_rates;
                    unset($out[$key], $_POST[$key]);

                    break;
                case 'escort_tours':
                    $escort_tours = array();
                    foreach ($_POST as $eKey => $eVal) {
                        if (strpos($eKey, $key) !== false && $eKey != $key) {
                            $index = array_reverse(explode("_", $eKey));

                            $tmpTours = json_decode($eVal);
                            $escort_tours[$index[0]] = array(
                                'location' => $tmpTours->location,
                                'latitude' => $tmpTours->latitude,
                                'longitude' => $tmpTours->longitude,
                                'place_id' => $tmpTours->place_id,
                                'from' => $tmpTours->from,
                                'to' => $tmpTours->to,
                            );
                        }
                    }
                    ksort($escort_tours);
                    $_POST['f'][$key] = $escort_tours;
                    unset($out[$key], $_POST[$key]);

                    break;
            }
        }
    }

    /**
     * @since 4.0.0 in the plugin
     *
     * @param int $listing_id - listing id
     */
    public function escortRatesPostSimulation($listing_id)
    {
        if (!$listing_id) {
            return;
        }

        $sql = "SELECT `Rate`, `Custom`, `Price` FROM `{db_prefix}escort_rates` ";
        $sql .= "WHERE `Listing_ID` = " . $listing_id;
        $sql .= " ORDER BY `ID`";
        $escort_rates = $GLOBALS['rlDb']->getAll($sql);

        $escort_tmp = array();
        if (!empty($escort_rates)) {
            foreach ($escort_rates as $index => $entry) {
                list($price, $currency) = explode('|', $entry['Price'], 2);
                $custom = intval($entry['Custom']);

                $escort_tmp[$index] = array(
                    'rate' => $custom ? '*cust0m*' : $entry['Rate'],
                    'custom_rate' => $custom ? $entry['Rate'] : '',
                    'currency' => $currency,
                    'price' => $price,
                );
            }
        }
        return $escort_tmp;
    }

    /**
     * Action save search
     *
     * @param int     $account_id - account id
     * @param string  $password   - account password
     *
     * @return mixed $response    - response result in xml
     *
     **/
    public function actionSavedSearch($account_id, $password_hash)
    {
        global $rlValid;

        if (!$account_id || !$password_hash) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no username or passwordHash received, abort");
            return false;
        }

        if (!$this->issetAccount($account_id, $password_hash)) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), wrong login details provided, abort");
            return false;
        }

        $id = (int) $_REQUEST['id'];
        $mode = $rlValid->xSql($_REQUEST['mode']);

        if ($mode && $id && $GLOBALS['rlDb']->getOne('ID', "`ID` = {$id}", 'saved_search')) {
            switch ($mode) {
                case 'active':
                case 'approval':
                    $GLOBALS['rlDb']->query("UPDATE `{db_prefix}saved_search` SET `Status` = '{$mode}' WHERE `ID` = {$id}");

                    break;

                case 'delete':
                    $GLOBALS['rlDb']->query("DELETE FROM `{db_prefix}saved_search` WHERE `ID` = {$id} LIMIT 1");

                    break;
            }

            $out['success'] = true;
        } else {
            $out['error'] = 'dialog_unable_save_data_on_server';
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response = $out;
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';
            if ($out['error']) {
                $response .= '<error>' . $out['error'] . '</error>';
            } else {
                $response .= '<success>true</success>';
            }
        }

        return $response;
    }

    /**
     * Prepere post form
     *
     * @param array  $fields - fields form search
     * @param string $data   - fields from app post
     *
     * @return array $fields - response result
     *
     **/
    public function preparePostFields($fields, $data)
    {
        $new_data = [];
        foreach (explode(',', $data) as $form_item) {
            $params = explode('=', trim($form_item));
            /* remove |listing_type_key from the field key */
            if (preg_match('/^Category_ID/', $params[0])) {
                $category_id = explode('|', $params[0]);
                $params[0] = $category_id[0];
            } else if (preg_match('/zip/', $params[0])) {

                if (false !== strpos($params[0], '_distance')) {
                    $new_data[str_replace('_distance', '', $params[0])]['distance'] = $params[1];
                } elseif (false !== strpos($params[0], '_zip')) {
                    $new_data[str_replace('_zip', '', $params[0])]['zip'] = $params[1];
                } elseif (false !== strpos($params[0], '_lat')) {
                    $new_data[str_replace('_lat', '', $params[0])]['lat'] = $params[1];
                } elseif (false !== strpos($params[0], '_lng')) {
                    $new_data[str_replace('_lng', '', $params[0])]['lng'] = $params[1];
                }
            }
            // removed space
            $params[0] = trim($params[0]);
            $params[1] = trim($params[1]);
            $new_data[$params[0]] = $params[1];
        }

        foreach ($new_data as $key => $val) {
            switch ($fields[$key]['Type']) {
                case 'checkbox':
                    if ($val) {
                        $exp_items = explode(';', $val);
                        array_unshift($exp_items, 0);
                        if ($exp_items) {
                            $form_fields[$key] = $exp_items;
                        }
                    }

                    break;

                case 'text':
                    $form_fields[$key] = $val;

                    break;

                case 'number':
                    $value = explode('-', $val);
                    if (false !== strpos($key, 'zip')) {
                        $form_fields[$key] = $val;
                    } else {
                        $form_fields[$key]['from'] = $value[0];
                        $form_fields[$key]['to'] = $value[1];
                    }

                    break;

                case 'mixed':
                    $value = explode('|', $val);
                    $value_number = explode('-', $value[0]);

                    $form_fields[$key]['from'] = $value_number[0];
                    $form_fields[$key]['to'] = $value_number[1];
                    $form_fields[$key]['df'] = $value[1];

                    break;

                case 'price':

                    $price_explode = explode('|', $val);
                    $value = explode('-', $price_explode[0]);
                    $form_fields[$key]['from'] = $value[0];
                    $form_fields[$key]['to'] = $value[1];
                    $form_fields[$key]['currency'] = $price_explode[1];

                    break;

                default:
                    if ($fields[$key]['Key'] == $this->year_build_key) {
                        $value = explode('-', $val);
                        $form_fields[$key]['from'] = $value[0];
                        $form_fields[$key]['to'] = $value[1];
                    } elseif ($fields[$key]['Key'] == $this->age_key) {
                        $value = explode('-', $val);
                        $form_fields[$key]['from'] = $value[1];
                        $form_fields[$key]['to'] = $value[0];
                    } else {
                        if ($val) {
                            $form_fields[$key] = $val;
                        }
                    }
                    break;
            }
        }

        return $form_fields;
    }

    /**
     * Get listing by account
     *
     * @param int $id    - account id
     * @param int $start - number of the page
     *
     * @return array $listings - response type: xml or json
     *
     **/
    public function getListingsByAccount($id, $start = 1)
    {
        $GLOBALS['reefless']->loadClass('Listings');
        return $this->prepareListings($GLOBALS['rlListings']->getListingsByAccount($id, false, false, $start, $this->grid_listings_limit), $GLOBALS['rlListings']->calc);
    }

    /**
     * Prepare listings array for xml responce
     *
     * @param array $listings      - referent to original listings array
     * @param int   $count         - total listings count from CALC
     * @param array $sorting       - sorting fields array
     * @param array $filterFields  - filter fields array
     *
     * @return array $listings - response type: xml or json
     *
     **/
    public function prepareListings(&$listings, $count = 0, $sorting = [], $filterFields = [])
    {
        $listings = $GLOBALS['rlLang']->replaceLangKeys($listings, 'categories', 'name');

        if (empty($listings)) {
            if ($GLOBALS['json_support']) {
                $GLOBALS['response_type'] = "json";
                $out['listings'] = null;
            } else {
                $out = false;
            }
            return $out;
        }

        foreach ($listings as $index => $listing) {
            if ($GLOBALS['config']['thumbnails_x2'] && $listing['Main_photo_x2']) {
                $listing['Main_photo'] = $listing['Main_photo_x2'];
            }
            $listings[$index]['Main_photo'] = $listing['Main_photo'] ? RL_FILES_URL . $listing['Main_photo'] : '';
            $fields = $listing['fields'];

            foreach ($listing as $key => $value) {
                if (!in_array($key, $this->transfer_listings_grid_fields)) {
                    unset($listings[$index][$key]);
                }
            }

            $listings[$index]['price'] = '';
            $listings[$index]['title'] = $listing['listing_title'];
            $listings[$index]['middle_field'] = '';

            if (!$fields) {
                continue;
            }

            // set price
            if ($fields && array_key_exists($this->price_key, $fields)) {
                $listings[$index]['price'] = $fields[$this->price_key]['value'];
                unset($fields[$this->price_key]);
            }

            foreach ($fields as $field_key => $field_value) {
                if ($field_value['value']) {
                    $listings[$index]['middle_field'] .= $listings[$index]['middle_field'] ? ', ' . $field_value['value'] : $field_value['value'];
                }
            }
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";

            $listingsOut['listings'] = $listings;
            $listingsOut['statistic'] = $count;
            $listingsOut['sorting'] = $sorting;
            $listingsOut['filters'] = $filterFields ? $filterFields : '';

        } else {
            $listings[$index + 1] = array(
                'total' => $count,
                'node_name' => 'statistic',
            );

            if ($sorting) {
                $listings[$index + 2] = array(
                    'child_nodes' => $sorting,
                    'node_name' => 'sorting',
                );
            }
            $listingsOut = $listings;
        }

        return $listingsOut;
    }

    /**
     * prepare accounts array for xml responce
     *
     * @param array $account - referent to original accounts array
     * @param int   $count   - total account count from CALC
     *
     * @return array $accounts - response type: xml or json
     *
     **/
    public function prepareAccounts(&$accounts, $count)
    {
        if (!$accounts) {
            if ($GLOBALS['json_support']) {
                $GLOBALS['response_type'] = "json";
                $out['accounts'] = "";
            } else {
                $out = false;
            }
            return $out;
        }

        foreach ($accounts as $index => $account) {
            $accounts[$index]['Photo'] = $account['Photo'] ? RL_FILES_URL . $account['Photo'] : '';
            $fields = $account['fields'];

            $accounts[$index]['Date'] = $this->convertDate($account['Date']);

            foreach ($account as $key => $value) {
                if (!in_array($key, $this->transfer_account_grid_fields)) {
                    unset($accounts[$index][$key]);
                }
            }

            $iteration = 1;
            $middle_field = '';
            foreach ($fields as $field) {
                if ($iteration > 2) {
                    break;
                }

                if ($field['value'] != '') {
                    $middle_field .= $field['value'] . ', ';
                    $iteration++;
                }
            }

            $middle_field = substr($middle_field, 0, -2);
            $accounts[$index]['middle_field'] = $middle_field;
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $out['accounts'] = $accounts;
            $out['statistic'] = $count;
        } else {
            $accounts[$index + 1] = array(
                'total' => $count,
                'node_name' => 'statistic',
            );
            $out = $accounts;
        }

        return $out;
    }

    /**
     * Count new listings
     *
     * @param int $date  - date
     *
     * @return array $array - response type: xml or json
     *
     **/
    public function countNewListings($date = 0)
    {
        if (!$date) {
            return "empty";
        }

        $sql = "SELECT COUNT(`T1`.`ID`) AS `Count` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
        $sql .= "WHERE (";
        $sql .= " TIMESTAMPDIFF(HOUR, `T1`.`Pay_date`, NOW()) <= `T2`.`Listing_period` * 24 "; //round to hour
        $sql .= " OR `T2`.`Listing_period` = 0 ";
        $sql .= ") ";
        $sql .= "AND `T1`.`Status` = 'active' ";
        $sql .= "AND UNIX_TIMESTAMP(`T1`.`Date`) >= {$date} ";
        $sql .= "LIMIT 100";

        $total = $GLOBALS['rlDb']->getRow($sql);

        return $total['Count'];
    }

    /**
     * Get categories
     *
     * @param string $type   - listing type
     * @param id     $parent - parent id
     *
     * @return array $array - response type: xml or json
     *
     **/
    public function getCatTree($type, $parent)
    {
        if (!$type || !is_numeric($parent)) {
            return;
        }

        $GLOBALS['reefless']->loadClass('Categories');
        $categories = [];
        foreach ($GLOBALS['rlCategories']->getCatTree($parent, $type) as $category) {
            $categories[] = array(
                'id' => $category['ID'],
                'name' => $category['name'],
                'count' => $category['Count'] ? $category['Count'] : 0,
                'sub_categories' => $category['Sub_cat'] ? 1 : 0,
                'level' => $category['Level'] ?: 0,
                'lock' => $category['Lock'] ?: 0,
            );
        }
        if ($GLOBALS['rlListingTypes']->types[$type]['Cat_order_type'] == 'alphabetic') {
            $GLOBALS['reefless']->rlArraySort($categories, 'name');
        }

        return $categories;
    }

    /**
     * Get categories
     *
     * @param string $type   - listing type
     * @param id     $parent - parent id
     *
     * @return array $array - response type: xml or json
     *
     **/
    public function getCategories($type, $parent)
    {
        if (!$type || !is_numeric($parent)) {
            return;
        }

        $GLOBALS['reefless']->loadClass('Categories');
        $categories = [];
        foreach ($GLOBALS['rlCategories']->getCategories($parent, $type, false, true) as $category) {
            $categories[] = array(
                'id' => $category['ID'],
                'name' => $category['name'],
                'count' => $category['Count'] ? $category['Count'] : 0,
                'sub_categories' => $category['sub_categories'] ? 1 : 0,
                'level' => $category['Level'] ?: 0,
                'lock' => $category['Lock'] ?: 0,
            );
        }
        if ($GLOBALS['rlListingTypes']->types[$type]['Cat_order_type'] == 'alphabetic') {
            $GLOBALS['reefless']->rlArraySort($categories, 'name');
        }

        return $categories;
    }

    /**
     * Get listings by category id
     *
     * @param int    $id           - category ID
     * @param int    $start        - start stack
     * @param string $listing_type - listing type key
     **/
    public function getListingsByCategory($id, $start = 1, $listing_type = null, $sort = null)
    {
        global $sorting, $category;

        $sort_field = false;
        $sort_type = 'ASC';

        $GLOBALS['reefless']->loadClass('Listings');

        if ($id) {
            $category = $GLOBALS['rlCategories']->getCategory($id);
        }

        // date field for sorting array, used two times
        $date_field['date'] = array('Key' => 'date', 'Type' => 'date', 'name' => $GLOBALS['lang']['date']);

        /* get sorting fields */
        $sorting = $GLOBALS['rlListings']->getFormFields($id, 'sorting_forms', $listing_type);
        if (!$sorting) {
            $sorting = $GLOBALS['rlListings']->getFormFields($id, 'short_forms', $listing_type);
        }
        $sorting = is_array($sorting) ? array_merge($date_field, $sorting) : $date_field;

        if ($sort) {
            $sort = explode('|/|', $sort);

            if ($sorting[$sort[0]]) {
                $sort_field = $sort[0];
                $sort_type = strtoupper($sort[1]);
            }
        }

        if (AdsFilter::isActive()) {
            AdsFilter::initFor(AdsFilter::FILTER_LISTING_TYPE);

            if (false !== $filterBoxId = AdsFilter::fetchBoxId($listing_type, $id)) {
                $filters = json_decode($_REQUEST['filters'], true);

                AdsFilter::setBoxId($filterBoxId);
                AdsFilter::prepareFilters($filters);
            }
        }

        $listings = $GLOBALS['rlListings']->getListings($id, $sort_field, $sort_type, $start, $GLOBALS['config']['android_grid_listings_number'], $listing_type);

        $filterFields = [];
        if (AdsFilter::isActive()) {

            AdsFilter::applyFiltersToResponse($filterFields);
        }

        /* adapt sorting array */
        if ($sorting) {
            foreach ($sorting as &$field) {
                if (!$field['Details_page']) {
                    unset($field);
                    continue;
                }

                foreach ($field as $item_key => $value) {
                    if (!in_array($item_key, $this->transfer_sorting_fields)) {
                        unset($field[$item_key]);
                    }
                }
            }
        }

        $out = $this->prepareListings($listings, $GLOBALS['rlListings']->calc, $sorting, $filterFields);
        if ($start == 1) {
            $out['categories'] = $this->getCategories($listing_type, $id);
        }

        return $out;
    }

    /**
     * Get listings by LatLng
     *
     * @param string $type        - listing type
     * @param int    $start       - start
     * @param array  $coordinates - coordinates
     **/
    public function getListingsByLatLng($type, $start = 1, $coordinates = [])
    {
        global $sql;

        $GLOBALS['reefless']->loadClass('Listings');

        $sql = "SELECT `T1`.*, `T3`.`Key` AS `Key`, `T3`.`Type` AS `Listing_type`, ";

        $GLOBALS['rlHook']->load('listingsModifyField');

        $sql .= "ROUND(3956 * 2 * ASIN(SQRT(
			POWER(SIN(({$coordinates['centerLat']} - `T1`.`Loc_latitude`) * 0.0174532925 / 2), 2) +
			COS({$coordinates['centerLat']} * 0.0174532925) *
			COS(`T1`.`Loc_latitude` * 0.0174532925) *
			POWER(SIN(({$coordinates['centerLng']} - `T1`.`Loc_longitude`) * 0.0174532925 / 2), 2)
		)), 3) AS `Android_distance`, ";

        $sql .= "IF(`T1`.`Featured_date`, '1', '0') `Featured` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_types` AS `T4` ON  `T3`.`Type` = `T4`.`Key` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T7` ON `T1`.`Account_ID` = `T7`.`ID` ";

        $GLOBALS['rlHook']->load('listingsModifyJoin');

        $sql .= "WHERE `T1`.`Status` = 'active' ";

        $sql .= "AND `T1`.`Loc_latitude` != '0' AND `T1`.`Loc_longitude` != '0'";

        $sql .= "AND (`T1`.`Loc_latitude` BETWEEN {$coordinates['southWestLat']} AND {$coordinates['northEastLat']}) ";
        if ($coordinates['southWestLng'] > $coordinates['northEastLng']) {
            $sql .= "AND ((`T1`.`Loc_longitude` BETWEEN {$coordinates['southWestLng']} AND 180) ";
            $sql .= "OR (`T1`.`Loc_longitude` BETWEEN -180 AND {$coordinates['northEastLng']})) ";
        } else {
            $sql .= "AND (`T1`.`Loc_longitude` BETWEEN {$coordinates['southWestLng']} AND {$coordinates['northEastLng']}) ";
        }

        if ($type) {
            $sql .= "AND `T3`.`Type` = '{$type}' ";
        }

        $sql .= "AND `T4`.`Android_status` = 'active' ";
        $sql .= "AND `T7`.`Status` = 'active' ";

        $GLOBALS['rlHook']->load('listingsModifyWhere');
        $GLOBALS['rlHook']->load('listingsModifyGroup');

        if (false === strpos($sql, 'GROUP BY')) {
            $sql .= " GROUP BY `T1`.`ID` ";
        }

        $sql .= "ORDER BY `ID` DESC ";
        $sql .= "LIMIT 500";

        $listings = $GLOBALS['rlDb']->getAll($sql);

        $calc = $GLOBALS['rlDb']->getRow("SELECT FOUND_ROWS() AS `calc`");

        $this->transfer_listings_grid_fields[] = 'Loc_latitude';
        $this->transfer_listings_grid_fields[] = 'Loc_longitude';
        $this->transfer_listings_grid_fields[] = 'Android_distance';

        foreach ($listings as $key => $value) {
            /* populate fields */
            $fields = $GLOBALS['rlListings']->getFormFields($value['Category_ID'], 'short_forms', $value['Listing_type']);

            foreach ($fields as $fKey => $fValue) {
                if ($field['Condition'] == 'isUrl' || $field['Condition'] == 'isEmail') {
                    $fields[$fKey]['value'] = $listings[$key][$item];
                } else {
                    $fields[$fKey]['value'] = $GLOBALS['rlCommon']->adaptValue(
                        $fValue,
                        $value[$fKey],
                        'listing',
                        $value['ID'],
                        true,
                        false,
                        false,
                        false,
                        $value['Account_ID'],
                        'short_form',
                        $value['Listing_type']
                    );
                }
            }

            $listings[$key]['fields'] = $fields;

            $listings[$key]['listing_title'] = $GLOBALS['rlListings']->getListingTitle($value['Category_ID'], $value, $value['Listing_type']);
        }

        return $this->prepareListings($listings, $calc['calc']);
    }

    /**
     * Get dealers by char
     *
     * @param array  $type_info - account type info
     * @param int    $page      - current page
     * @param string $char      - start character
     *
     * @return array of dealers accounts
     **/
    public function getAccountsByType($type, $start = 1, $char = '')
    {
        global $alphabet;

        if (!$type) {
            if ($GLOBALS['json_support']) {
                $GLOBALS['response_type'] = "json";
                $out['accounts'] = "";
            } else {
                $out = false;
            }
            return $out;
        }

        $alphabet = explode(',', $GLOBALS['lang']['alphabet_characters']);

        $GLOBALS['reefless']->loadClass('Account');

        $type_info = $GLOBALS['rlDb']->fetch('*', array('Key' => $type), null, 1, 'account_types', 'row');

        $sorting = array();
        $accounts = $GLOBALS['rlAccount']->getDealersByChar($char, $this->grid_listings_limit, $start, $type_info, $sorting, false, false);

        return $this->prepareAccounts($accounts, $GLOBALS['rlAccount']->calc_alphabet);
    }

    /**
     * Search dealers
     *
     * @param array  $data - search data
     * @param string $type - account type
     * @param int    $page - current page
     *
     * @return array of dealers accounts
     **/
    public function searchAccount($data, $type, $start = 1)
    {
        if (!$type) {
            if ($GLOBALS['json_support']) {
                $GLOBALS['response_type'] = "json";
                $out['accounts'] = "";
            } else {
                $out = false;
            }
            return $out;
        }

        $GLOBALS['reefless']->loadClass('Account');
        $GLOBALS['reefless']->loadClass('Listings');

        $account_type_id = $GLOBALS['rlDb']->getOne('ID', "`Key` = '{$type}'", 'account_types');
        $account_type = $GLOBALS['rlAccount']->getTypeDetails($type);
        $fields = $GLOBALS['rlAccount']->buildSearch($account_type_id);

        if (!$fields) {
            $GLOBALS['rlDebug']->logger("ANDROID: searchAccounts, no fields by form found");
        }

        foreach (explode(',', $data) as $form_item) {
            $params = explode('=', $form_item);

            switch ($fields[$params[0]]['Type']) {
                case 'checkbox':
                    if ($params[1]) {
                        $exp_items = explode(';', $params[1]);
                        array_unshift($exp_items, 0);
                        $form_data[$params[0]] = $exp_items;
                    }
                    break;

                case 'number':
                    $value = explode('-', $params[1]);
                    if (false !== strpos($params[0], 'zip')) {
                        $form_data[$params[0]]['distance'] = $value[0];
                        $form_data[$params[0]]['zip'] = $value[1];
                    } else {
                        $form_data[$params[0]]['from'] = $value[0];
                        $form_data[$params[0]]['to'] = $value[1];
                    }
                    break;

                default:
                    $form_data[$params[0]] = $params[1];
                    break;
            }
        }

        $accounts = $GLOBALS['rlAccount']->searchDealers($form_data, $fields, $this->grid_listings_limit, $start, $account_type);

        return $this->prepareAccounts($accounts, $GLOBALS['rlAccount']->calc);
    }

    /**
     * Get account types
     *
     * @param string $response - xml response
     * @param boolean $json     - true or false
     *
     **/
    public function getAccountTypes(&$response, $json = false)
    {
        $GLOBALS['reefless']->loadClass('Account');
        $except_type_keys = array('visitor', 'affiliate');
        $account_types = $this->account_types = $GLOBALS['rlAccount']->getAccountTypes($except_type_keys);

        if ($json) {
            $tmp = array();
            foreach ($account_types as $type) {
                $tmp[$type['Key']] = $type;
            }
            $response['account_types'] = $tmp;
        } else {
            $response .= '<account_types>';
            foreach ($account_types as $type) {
                if (version_compare($GLOBALS['config']['rl_version'], '4.6.2') >= 0) {
                    $thum_size = "width='" . $type['Thumb_width'] . "' height='" . $type['Thumb_height'] . "' ";
                }
                $response .= '<type key="' . $type['Key'] . '" own_location="' . $type['Own_location'] . '" page="' . $type['Page'] . '" ' . $thum_size . ' />';
            }
            $response .= '</account_types>';
        }
    }

    /**
     * Get account forms
     *
     * @since 3.3.0
     **/
    public function getAccountForms()
    {
        $type_fields = array();

        $GLOBALS['reefless']->loadClass('Account');
        $account_types = $this->account_types = $GLOBALS['rlAccount']->getAccountTypes(array('affiliate', 'visitor'));
        if ($GLOBALS['config']['android_second_step']) {
            foreach ($account_types as $type) {
                $fields = $GLOBALS['rlAccount']->getFields($type['ID']);
                $fields = $GLOBALS['rlLang']->replaceLangKeys($fields, 'account_fields', array('name', 'default', 'description'));
                $fields = $GLOBALS['rlCommon']->fieldValuesAdaptation($fields, 'account_fields');

                $type_fields['fields'][$type['Key']] = $this->adaptForm($fields, $type, false, true);
            }
        }

        // get a list with agreement fields
        if (method_exists($GLOBALS['rlAccount'], 'getAgreementFields')) {
            $agreement = array();
            $agreement_tmp = $GLOBALS['rlAccount']->getAgreementFields();
            foreach ($agreement_tmp as $key => $value) {

                $tmp_item['Key'] = $value['Key'];
                $pageInfo = $GLOBALS['rlDb']->fetch(
                    array('Page_type', 'Path', 'Controller'),
                    array('Status' => 'active', 'Key' => $value['Default']),
                    null,
                    null,
                    'pages',
                    'row'
                );

                $tmp_item['type'] = 'accept';
                $tmp_item['page_type'] = $pageInfo['Page_type'];
                $tmp_item['required'] = '1';
                $tmp_item['mode'] = $pageInfo['Page_type'] == 'static' ? 'view' : 'webview';
                $tmp_item['name'] = $GLOBALS['lang']['pages+name+' . $value['Default']];
                switch ($pageInfo['Page_type']) {
                    case 'system':
                        $link = SEO_BASE;
                        if ($pageInfo['Path']) {
                            $link .= $GLOBALS['config']['mod_rewrite'] ? $pageInfo['Path'] . '.html' : '?page=' . $pageInfo['Path'];
                        }
                        $tmp_item['content'] = $link;
                        break;

                    case 'static':
                        $tmp_item['content'] = $GLOBALS['lang']['pages+content+' . $value['Default']];
                        break;

                    case 'external':
                        $tmp_item['content'] = $value['Controller'];
                        break;
                }
                if ($value['Values']) {
                    foreach (explode(',', $value['Values']) as $val) {
                        $agreement[$val][$tmp_item['Key']] = $tmp_item;
                    }
                } else {
                    foreach ($account_types as $type) {
                        $agreement[$type['Key']][$tmp_item['Key']] = $tmp_item;
                    }
                }
            }
            $type_fields['agreement'] = $agreement;
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response = $type_fields;
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?><items>';
            $response .= $this->printValue($type_fields);
            $response .= '</items>';
        }

        return $response;
    }

    /**
     * Get ad sense
     *
     * @param string $response - xml response
     * @param Boolean $json     - true or false
     *
     **/
    public function getAdsense(&$response, $json = false)
    {
        $info = $GLOBALS['rlDb']->fetch(array('ID', 'Side', 'Code', 'Pages'), array('Status' => 'active'), null, null, 'android_adsense');

        if ($json) {
            foreach ($info as $key => $item) {
                $pages = explode(',', $item['Pages']);
                foreach ($pages as $page) {
                    $item['Page'] = $page;
                    $response['adsenses'][] = $item;
                }
            }
        } else {
            $response .= '<adsenses>';
            foreach ($info as $key => $item) {
                $pages = explode(',', $item['Pages']);
                foreach ($pages as $page) {
                    $response .= '<adsense page="' . $page . '" side="' . $item['Side'] . '" id="' . $item['ID'] . '">';
                    $response .= $item['Code'];
                    $response .= '</adsense>';
                }
            }
            $response .= '</adsenses>';
        }
    }

    /**
     * Get Report BrokenInfo
     *
     * @param string $response - xml response
     *
     **/
    public function getReportBrokenInfo(&$response, $json = false)
    {
        global $lang, $rlDb, $rlLang;

        if (version_compare($GLOBALS['plugins']['reportBrokenListing'], '3.0.0') < 0) {
            return;

        } else {

            $points = $rlDb->fetch('*', array('Status' => 'active'), "ORDER BY `Position`", null, 'report_broken_listing_points');

            foreach ($points as $key => $point) {
                if ($lang[$point['Key']]) {
                    $points[$key]['name'] = $lang[$point['Key']];
                } else {
                    $tmp_param = array('Key' => $point['Key'], 'Code'=> RL_LANG_CODE);
                    $points[$key]['name'] = $rlLang->getPhrase($tmp_param);
                }
            }

            if ($points) {
                $other = array('Key' => 'custom', 'name' => $lang['rbl_other']);
                $points[] = $other;

                if ($json) {
                    $response['report_broken'] = $points;
                } else {
                    $response .= '<report_broken>';
                    foreach ($points as $key => $point) {
                        $response .= "<item key='" . $point['Key'] . "'>";
                        $response .= $point['name'];
                        $response .= "</item>";
                    }
                    $response .= '</report_broken>';
                }
            }
        }
    }

    /**
     * Send Report BrokenInfo
     *
     * @param string $response - xml response
     *
     **/
    public function sendReportBroken()
    {
        global $rlDb;

        if (!$_POST['listing_id']) {
            return false;
        }

        $ip = Util::getClientIP();
        $where = "`Listing_ID` = {$_POST['listing_id']} AND `IP` = '{$ip}'";
        $report_exist = $rlDb->getOne('ID', $where, 'report_broken_listing');

        if ($report_exist) {
            $out['errors'] = 'android_report_ip_wrong';
        } else {
            $insert = array(
                'Listing_ID' => $_POST['listing_id'],
                'Account_ID' => $_POST['account_id'],
                'Report_key' => $_POST['key'],
                'Message' => $_POST['key'] == 'custom' ? $_POST['message'] : $GLOBALS['lang'][$_POST['key']],
                'IP' => $ip,
                'Date' => 'NOW()',
                'Status' => 'active',
            );

            // insert a new report
            $success = $GLOBALS['rlDb']->insertOne($insert, 'report_broken_listing');

            $out['success'] = 'android_report_added';
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response = $out;
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';
            if ($out['errors']) {
                $response .= '<errors>' . $out['errors'] . '</errors>';
            } else {
                $response .= '<success>' . $out['success'] . '</success>';
            }
        }

        return $out;
    }

    /**
     * Remove ad sense
     *
     * @param string $id - id
     *
     **/
    public function ajaxDeleteAdsenseBox($id)
    {

        global $_response;

        // check admin session expire
        if ($GLOBALS['reefless']->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $id = (int) $id;
        if (!$id) {
            return $_response;
        }

        $GLOBALS['rlDb']->delete(array('ID' => $id), 'android_adsense');

        $_response->script("
			adsense.reload();
			printMessage('notice', '{$GLOBALS['lang']['adsense_removed']}')
		");

        return $_response;
    }

    /**
     * Search listings by keyword
     *
     * @param string $query - search query
     *
     **/
    public function keywordSearch(&$query)
    {
        global $reefless;
        $reefless->loadClass('Common');
        $reefless->loadClass('Listings');
        $reefless->loadClass('Search');

        $data['keyword_search'] = $query;
        $fields['keyword_search'] = array(
            'Type' => 'text',
        );

        $GLOBALS['rlSearch']->fields = $fields;

        $listings = $GLOBALS['rlSearch']->search($data, false, false, 20);

        foreach ($listings as $listing) {
            if ($GLOBALS['config']['thumbnails_x2'] && $listing['Main_photo_x2']) {
                $listing['Main_photo'] = $listing['Main_photo_x2'];
            }
            $out[] = array(
                'listing_title' => $listing['listing_title'],
                'id' => $listing['ID'],
                'Main_photo' => $listing['Main_photo'],
            );
        }
        unset($listings);

        return $out;
    }

    /**
     * Login attempt, checks if login details are correct
     *
     * @param string $username - username
     * @param string $password - password
     *
     **/
    public function loginAttempt($username, $password)
    {
        global $config;

        if (!$username || !$password) {
            return false;
        }

        $password = urldecode($password);

        $match_field = $config['account_login_mode'] == 'email' ? 'Mail' : 'Username';

        $account = $GLOBALS['rlDb']->fetch(array('ID', 'Status', 'Password'), array($match_field => $username), "AND `Status` <> 'incomplete'", 1, 'accounts', 'row');

        $verified = FLSecurity::verifyPassword($password, $account['Password']);
        if ($verified) {
            $info = $account;
        }

        if (!$info) {
            $info['Status'] = 'error';
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            switch ($info['Status']) {
                case 'active':
                case 'expired':
                    $acount_data = $this->fetchAccountData($info['ID']);
                    $acount_data['password'] = $account['Password'];
                    $response = $_SESSION['account'] = $acount_data;
                    break;

                default:
                    $response['Status'] = $info['Status'];
                    break;
            }
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?><account>';

            switch ($info['Status']) {
                case 'active':
                case 'expired':
                    $acount_data = $this->fetchAccountData($info['ID']);
                    $acount_data['password'] = $account['Password'];
                    $_SESSION['account'] = $acount_data;
                    $response .= $this->toXmlNode($acount_data);
                    break;

                default:
                    $response .= '<status><![CDATA[' . $info['Status'] . ']]></status>';
                    break;
            }

            $response .= '</account>';
        }

        return $response;
    }

    /**
     * Login attempt, checks if login details are correct
     *
     * @param string $account_id - $account_id
     * @param string $password   - password
     *
     **/
    public function issetAccount($account_id, $password)
    {
        $password = urldecode($password);
        if (!$GLOBALS['rlDb']->getOne('ID', "`ID` = {$account_id} AND `Password` = '{$password}'", 'accounts')) {
            return false;
        }
        if (!$_SESSION['account']) {
            $account = $GLOBALS['rlDb']->fetch('*', array('ID' => $account_id), null, null, 'accounts', 'row');
            $account['Password'] = md5($account['Password']);
            $_SESSION['account'] = $account;
        }

        return true;
    }

    /**
     * Fetch account data
     *
     * @param int $id - account ID
     *
     **/
    public function fetchAccountData($id)
    {
        $id = intval($id);

        if (!$id) {
            return;
        }

        $GLOBALS['reefless']->loadClass('Account');

        // get account data
        $profile = $GLOBALS['rlAccount']->getProfile((int) $id);

        if ($profile['Photo']) {
            $profile['Photo'] = RL_FILES_URL . $profile['Photo'];
        }

        // get account statistics
        $profile['statistics'] = $this->fetchAccountStat($id);

        //add new messages count
        $new_messages = $GLOBALS['rlDb']->getRow("SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}messages` WHERE `To` = '{$id}' AND `Status` = 'new' AND FIND_IN_SET( 'to', `Remove`) = 0");
        $profile['new_messages'] = $new_messages['Count'];

        // get account saved search
        $profile['saved_search'] = $this->fetchAccountSavedSearch($id);

        return $profile;
    }

    /**
     * Fetch account Saved Search
     *
     * @param int $id - account ID
     *
     * @return array $array - account Saved Search data
     *
     **/
    public function fetchAccountSavedSearch($id)
    {
        global $lang;
        if (!$id) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no account id provided, abort");
            return false;
        }

        $array = $GLOBALS['rlDb']->fetch(array('ID', 'Matches'), array('Account_ID' => $id), null, null, 'saved_search');
        return $array;
    }

    /**
     * Fetch account statistics
     *
     * @param int $id - account ID
     *
     * @return array $stats - account statistics data
     *
     **/
    public function fetchAccountStat($id)
    {
        global $lang, $rlDb;
        if (!$id) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no account id provided, abort");
            return false;
        }

        $sql = "SELECT
			SUM(IF(`Status` = 'active', 1, 0)) `active`,
			SUM(IF(`Status` != 'active' AND `Status` != 'expired', 1, 0)) `inactive`,
			SUM(IF(`Status` = 'expired', 1, 0)) `expired`
			FROM `{db_prefix}listings`
			WHERE `Account_ID` = {$id}";

        $stat_listings = $rlDb->getRow($sql);

        // listings
        $listings = array(
            array('name' => 'status_active', 'number' => $stat_listings['active']),
            array('name' => 'status_approval', 'number' => $stat_listings['inactive']),
            array('name' => 'status_expired', 'number' => $stat_listings['expired']),
        );

        $stats[] = array(
            'caption' => 'stat_listings',
            'items' => $listings,
        );

        // plan packages
        $sql = "SELECT
			SUM(`T1`.`Standard_remains`) `standard`,
			SUM(`T1`.`Featured_remains`) `featured`,
			SUM(`T2`.`Standard_listings`) `standard_total`,
			SUM(`T2`.`Featured_listings`) `featured_total`
			FROM `{db_prefix}listing_packages` AS `T1`
			LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID`
			WHERE `T1`.`Account_ID` = {$id} AND `T1`.`Type` = 'package'";

        $stat_plan = $rlDb->getRow($sql);

        if ($stat_plan['standard'] || $stat_plan['featured']) {
            $of_phrase = $rlDb->getOne('Value', "`Key` = 'stat_rest_of_total' AND `Code` = '" . RL_LANG_CODE . "'", 'android_phrases');
            $plans = array(
                array('name' => 'listing_appearance_standard', 'number' => $stat_plan['standard_total'] ? str_replace(array('{rest}', '{total}'), array($stat_plan['standard'], $stat_plan['standard_total']), $of_phrase) : $lang['unlimited']),
                array('name' => 'listing_appearance_featured', 'number' => $stat_plan['featured_total'] ? str_replace(array('{rest}', '{total}'), array($stat_plan['featured'], $stat_plan['featured_total']), $of_phrase) : $lang['unlimited']),
            );

            $stats[] = array(
                'caption' => 'stat_plan_packages',
                'items' => $plans,
            );
        }

        return $stats;
    }

    /**
     * Get account statistics
     *
     * @param int    $account_id - account id
     * @param string $password_hash - md5 password
     *
     * @return string $response - response xml
     *
     **/
    public function getAccountStat($account_id, $password_hash)
    {
        if (!$account_id || !$password_hash) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no username or passwordHash received, abort");
            return false;
        }

        if (!$this->issetAccount($account_id, $password_hash)) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), wrong login details provided, abort");
            return false;
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response = $this->fetchAccountStat($account_id);
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';

            $data = array('statistics' => $this->fetchAccountStat($account_id));
            $response .= $this->toXmlNode($data);
        }

        return $response;
    }

    /**
     * Login or regist by plugin hybrid auth
     *
     * @param array  $data - user data
     *
     * @return array of the user
     **/
    public function hybridAuthLogin($data)
    {
        if ($GLOBALS['plugins']['hybridAuthLogin'] && $data['provider']) {
            $GLOBALS['reefless']->loadClass('HybridAuthLogin', null, 'hybridAuthLogin');
            $hybridAuthApi = new \Flynax\Plugins\HybridAuth\API();

            $userData = [
                'fid' => $data['fid'],
                'email' => $data['email'],
                'provider' => $data['provider'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'verified' => 1,
            ];

            if ($data['account_type_id'] && $data['account_type_id'] != 'will-be-set') {
                $userData['account_type'] = $data['account_type_id'];
            }

            $result = $hybridAuthApi->processUser($userData);

            switch ($result['action']) {
                case 'login':
                case 'registered':
                    $result['user_data'] = $this->fetchAccountData($result['user_data']['ID']);
                    $result['user_data']['password'] = $GLOBALS['rlDb']->getOne('Password', "`ID` = '{$result['user_data']['ID']}'", 'accounts');
                    break;

                case 'need_register':
                    $result['data'] = $this->getAccountForms();
                    break;

                case 'validation':
                    $errors = [];
                    foreach ($result['errors'] as $key => $value) {
                        $errors['error_' . $key] = str_replace('"', "'", $value);
                    }
                    $result['errors'] = $errors;
                    break;
            }
        }

        return $result;
    }

    /**
     * Verify user password
     *
     * @param array  $data - search data
     *
     * @return array $result - of the user
     **/
    public function hybridAuthLoginVerifyPassword($data)
    {
        $GLOBALS['reefless']->loadClass('HybridAuthLogin', null, 'hybridAuthLogin');
        if ($data['password'] && $GLOBALS['rlHybridAuthLogin']->verifyUserPasswordByEmail($data['email'], $data['password'])) {
            $user_id = $GLOBALS['rlDb']->getOne('ID', "`Mail` = '{$data['email']}'", 'accounts');
            $result['user_data'] = $this->fetchAccountData($user_id);
            $result['user_data']['password'] = $GLOBALS['rlDb']->getOne('Password', "`ID` = '{$user_id}'", 'accounts');
        } else {
            $result['error'] = 'dialog_password_incorrect';
        }

        return $result;
    }

    /**
     * Create user account
     *
     * @param array $form_data - account data
     *
     **/
    public function createAccount(&$data)
    {
        global $rlAccount, $rlDb;

        if (!count(array_filter($data))) {
            return;
        }

        $error = false;

        /* facebook connect login */
        if ($data['fb_id']) {

            $password = $rlDb->getOne('Password', "`Mail` = '{$data['email']}'", 'accounts');
            if ($data['fb_password']) {

                $verified = FLSecurity::verifyPassword($data['fb_password'], $password);
                if ($verified) {
                    $confirmFBpasss = 1;
                }
                if ($confirmFBpasss) {
                    $rlDb->query("UPDATE `{db_prefix}accounts` SET `facebook_ID` = '{$data['fb_id']}' WHERE `Mail` = '{$data['email']}' LIMIT 1");
                }
            }

            $fb_account = $rlDb->fetch(array('ID', 'Password', 'Username', 'Mail', 'Status'), array('facebook_ID' => $data['fb_id']), null, 1, 'accounts', 'row');

            /* fb account exists */
            if ($fb_account) {
                if (in_array($fb_account['Status'], array('trash', 'approval'))) {
                    $error = true;
                    if ($GLOBALS['json_support']) {
                        $out = 'fb_account_deactivated';
                    } else {
                        $out = '<error field="field">fb_account_deactivated</error>';
                    }
                }

                if (!$error) {
                    $passwordHash = $fb_account['Password'];
                    $account_id = $fb_account['ID'];
                }
            }
            /* create new account */
            else {
                /* check for duplicate e-mail */
                $exist_email = $rlDb->getOne('ID', "`Mail` = '{$data['email']}'", 'accounts');
                if ($exist_email) {
                    $error = true;
                    if ($GLOBALS['json_support']) {
                        $out = 'fb_email_exists';
                    } else {
                        $out = '<error field="field">fb_email_exists</error>';
                    }
                }

                if (!$error) {
                    $new_password = $GLOBALS['reefless']->generateHash(10, 'password', true);
                    $passwordHash = FLSecurity::cryptPassword($new_password);
                    $data['password'] = $new_password;
                    $account_id = $this->registration($data);
                }
            }
        }
        /* default logic */
        else {
            /* validate user */
            if ($GLOBALS['config']['account_login_mode'] == 'email') {
                $exp_email = explode('@', $GLOBALS['rlValid']->xSql($data['email']));
                $username = $data['username'] = $GLOBALS['rlAccount']->makeUsernameUnique($exp_email[0]);
            } else {
                $username_val = $GLOBALS['rlValid']->xSql($data['username']);
                $status = $rlDb->getOne('Status', "`Username` = '{$username_val}'", 'accounts');
                if ($status == 'trash') {
                    $error = true;
                    if ($GLOBALS['json_support']) {
                        $out = 'dialog_account_exists_trash';
                    } else {
                        $out = '<error field="username">dialog_account_exists_trash</error>';
                    }
                } elseif ($status) {
                    $error = true;
                    if ($GLOBALS['json_support']) {
                        $out = 'dialog_account_exists';
                    } else {
                        $out = '<error field="username">dialog_account_exists</error>';
                    }
                }
            }

            /* validate e-mail */
            $email_val = $GLOBALS['rlValid']->xSql($data['email']);
            $email = $rlDb->getOne('ID', "`Mail` = '{$email_val}'", 'accounts');
            if ($email) {
                $error = true;
                if ($GLOBALS['json_support']) {
                    $out = 'dialog_email_exists';
                } else {
                    $out = '<error field="email">dialog_email_exists</error>';
                }
            }

            /* create new account */
            if (!$error) {
                $passwordHash = FLSecurity::cryptPassword($data['password']);

                $account_id = $this->registration($data);
            }
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            if ($error) {
                $response['errors'] = $out;
            } else {
                $profile = $this->fetchAccountData($account_id);
                /* send back the password hash in case of fb login */
                $profile['password'] = $rlDb->getOne('Password', "`ID` = '{$account_id}'", 'accounts');
                if ($fb_account && !$error) {
                    $profile['fb_logged_in'] = "1"; //just login using fb
                }
                $response = $profile;
            }
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';

            if ($error) {
                $response .= '<errors>';
                $response .= $out;
                $response .= '</errors>';
            } else {
                $response .= '<account>';

                $profile = $this->fetchAccountData($account_id);

                /* send back the password hash in case of fb login */
                $profile['password'] = $rlDb->getOne('Password', "`ID` = '{$account_id}'", 'accounts');
                if ($fb_account && !$error) {
                    $profile['fb_logged_in'] = "1"; //just login using fb
                }

                $response .= $this->toXmlNode($profile, array('Password_tmp'));

                $response .= '</account>';
            }
        }

        return $response;
    }

    /**
     * Quick user registration
     *
     * @param array $data - account data
     *
     * @return int - created account id
     **/
    public function registration(&$data)
    {
        global $config, $rlValid, $reefless, $rlDb, $rlHook, $lang, $account_info;

        /* generate data in case of facebook connect login */
        if ($data['fb_id']) {
            $data['account_type'] = $config['facebookConnect_account_type'];
            if (!$data['account_type'] || $data['account_type'] == 'any') {
                $data['account_type'] = $rlDb->getOne('Key', "`Key` <> 'visitor' ORDER BY `ID` DESC", 'account_types');
            }
        }

        /* validate username */
        if ($config['account_login_mode'] == 'username') {
            $data['username'] = $this->uniqueValue('Username', 'accounts', $data['username']);
        } else {
            $exp_email = explode('@', $data['email']);
            $data['username'] = $this->uniqueValue('Username', 'accounts', $exp_email[0]);
        }

        /* prepare own account address */
        $own_address = $this->uniqueValue('Own_address', 'accounts', $rlValid->str2path($data['username']));

        /* get requested account type info */
        $fields = array('ID', 'Key', 'Abilities', 'Page', 'Own_location', 'Email_confirmation', 'Admin_confirmation', 'Auto_login');
        $type_info = $rlDb->fetch($fields, array('Key' => $data['account_type']), null, 1, 'account_types', 'row');

        // disable verifications for FB users, TODO
        if ($data['fb_id']) {
            $type_info['Email_confirmation'] = false; // disabled uet, we will take into account the varified status from the FB soon
        }

        $passwordHash = FLSecurity::cryptPassword($data['password']);

        /* insert entry to db */
        $insert = array(
            'Quick' => 1,
            'Type' => $data['account_type'],
            'Username' => $data['username'],
            'Own_address' => $own_address,
            'Password' => $passwordHash,
            'Password_tmp' => $data['password'],
            'Lang' => strtolower(RL_LANG_CODE),
            'Mail' => $data['email'],
            'Date' => 'NOW()',
            'Status' => $type_info['Email_confirmation'] || $type_info['Admin_confirmation'] ? 'incomplete' : 'active', // we set 'active' status to all for better usability of APP, admin always can deactivate the account
        );

        /* add missed fb data */
        if ($data['fb_id']) {
            $_SESSION['facebook_info']['id'] = $data['fb_id'];
            $insert['facebook_ID'] = $data['fb_id'];
            $insert['First_name'] = $data['first_name'];
            $insert['Last_name'] = $data['last_name'];
            $insert['Quick'] = 0;
        }

        /* save password to send it tp the user */
        if ($type_info['Email_confirmation']) {
            $insert['Password_tmp'] = $data['password'];
        }

        $rlHook->load('phpQuickRegistrationBeforeInsert', $insert, $data);

        $rlDb->insertOne($insert, 'accounts');
        $account_id = $rlDb->insertID();

        if ($account_id && $config['android_second_step'] && !$data['fb_id']) {
            $fields = $GLOBALS['rlAccount']->getFields($rlDb->getOne('ID', "`Key` = '{$data['account_type']}'", 'account_types'));
            if ($fields) {
                $GLOBALS['profile_info'] = $insert;
                $account_data = $GLOBALS['account_data'] = $this->adaptData($fields, $_POST);

                $account_info['ID'] = $account_id;
                $GLOBALS['rlAccount']->editAccount($account_data, $fields, $account_id);
                $rlHook->load('profileEditAccountValidate'); // @since 4.0.0
            }
        }

        /*
         * @since 4.0.0
         */
        $rlHook->load('phpRegistrationSuccessApp', $data, $account_data);

        unset($insert);

        /* send notification email to the user */
        $reefless->loadClass('Mail');

        // email confirmation case
        if ($type_info['Email_confirmation']) {
            $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('android_pending_account_created');

            $confirm_path = $this->getPagePath('confirm');
            $confirm_code = md5(mt_rand());

            // create activation link
            $activation_link = SEO_BASE;
            $activation_link .= $config['mod_rewrite'] ? "{$confirm_path}.html?key=" : "?page={$confirm_path}&amp;key=";
            $activation_link .= $confirm_code;
            $activation_link = '<a href="' . $activation_link . '">' . $activation_link . '</a>';

            $find = array(
                '{username}',
                '{link}',
                '{code}',
            );
            $replace = array(
                $data['username'], // no full name available on this step
                $activation_link,
                $confirm_code,
            );
            $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);

            /* save confirmation code */
            $rlDb->query("UPDATE `{db_prefix}accounts` SET `Confirm_code` = '{$confirm_code}' WHERE `ID` = {$account_id} LIMIT 1");
        }
        // activated account case
        else {
            $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('account_created_active');

            $login_path = $this->getPagePath('login');

            $account_area_link = SEO_BASE;
            $account_area_link .= $config['mod_rewrite'] ? $login_path . '.html' : '?page=' . $login_path;
            $account_area_link = '<a href="' . $account_area_link . '">' . $lang['blocks+name+account_area'] . '</a>';

            $find = array(
                '{login}',
                '{password}',
                '{name}',
                '{account_area}',
            );
            $replace = array(
                $data['username'],
                $data['password'],
                $data['username'], // no full name available on this step
                $account_area_link,
            );
            $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
        }

        $GLOBALS['rlMail']->send($mail_tpl, $data['email']);

        /* send notification e-mail to administrator */
        $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('android_account_created_admin');

        $details_link = RL_URL_HOME . ADMIN . '/index.php?controller=accounts&amp;action=view&amp;userid=' . $account_id;
        $details_link = '<a href="' . $details_link . '">' . $details_link . '</a>';

        $find = array(
            '{username}',
            '{link}',
            '{email}',
            '{date}',
            '{account_type}',
        );
        $replace = array(
            $data['username'],
            $details_link,
            $data['email'],
            date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT)),
            $lang['account_types+name+' . $type_info['Key']],
        );
        $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
        $mail_tpl['subject'] = str_replace('{username}', $data['username'], $mail_tpl['subject']);

        $GLOBALS['rlMail']->send($mail_tpl, $config['site_main_email']);

        return $account_id;
    }

    /**
     * Delete account
     *
     * @param int    $account_id       - account id
     * @param string $password         - password
     * @param string $password_confirm - password confirm
     *
     * @since 3.3.0
     **/
    public function deleteAccount($account_id, $password, $password_confirm)
    {

        if (!$account_id || !$password) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "() no username or passwordHash received, abort");
            return false;
        }

        $GLOBALS['reefless']->loadClass('Admin', 'admin');

        $password = urldecode($password);
        $password_confirm = urldecode($password_confirm);

        if (FLSecurity::verifyPassword($password_confirm, $password)
            && $GLOBALS['rlAdmin']->deleteAccountDetails($account_id, null, true)) {
            $out['success'] = 'android_remove_account_notice';
        } else {
            $out['error'] = 'password_does_not_match';
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response = $out;
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';
            if ($out['error']) {
                $response .= '<error>' . $out['error'] . '</error>';
            } else {
                $response .= '<success>' . $out['success'] . '</success>';
            }
        }

        return $response;
    }

    /**
     * check for unique value and add postfix after if need | recursive method
     *
     * @param string $field - table field to check
     * @param string $table - table name to check
     * @param string $value - value to check
     *
     * @return string - new or given unique value
     **/
    public function uniqueValue($field, $table, $value)
    {
        if (!$field || !$table || !$value) {
            return $value;
        }

        if ($GLOBALS['rlDb']->getOne($field, "`{$field}` = '{$value}'", $table)) {
            $value .= $GLOBALS['reefless']->generateHash(1, 'numbers', true);
            return $this->uniqueValue($field, $table, $value);
        } else {
            return $value;
        }
    }

    /**
     * Reset password by email
     *
     * @param string $email - requested e-mail address
     *
     * @return mixed - custom xml
     **/
    public function resetPassword($email)
    {
        global $config, $rlDb, $reefless;

        if (!$email) {
            $out['errors'] = 'email';
        } else if (!$out['errors']) {
            /* check email */
            $account_id = $rlDb->getOne('ID', "`Mail` = '{$email}'", 'accounts');

            if (!$account_id) {
                $out['errors'] = 'email';
            }
        }

        /* send "reset password" link */
        if (!$out['errors']) {
            $reefless->loadClass('Account');
            $profile_info = $GLOBALS['rlAccount']->getProfile((int) $account_id);

            $reefless->loadClass('Mail');
            $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('remind_password_request');

            $hash_key = $reefless->generateHash();
            $hash = md5($hash_key) . md5($config['security_key']);

            $sql = "UPDATE `{db_prefix}accounts` SET `Password_hash` = '{$hash_key}' WHERE `ID` = '{$account_id}' LIMIT 1";
            $rlDb->query($sql);

            $page_path = $this->getPagePath('remind');

            $link = SEO_BASE;
            $link .= $config['mod_rewrite'] ? $page_path . '.html?hash=' . $hash : '?page=' . $page_path . '&amp;hash=' . $hash;
            $link = '<a href="' . $link . '">' . $link . '</a>';

            $mail_tpl['body'] = str_replace(
                array('{link}', '{name}'),
                array($link, $profile_info['Full_name']),
                $mail_tpl['body']
            );
            $GLOBALS['rlMail']->send($mail_tpl, $email);

            $out['success'] = true;
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response = $out;
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';
            if ($out['errors']) {
                $response .= '<errors>' . $out['errors'] . '</errors>';
            } else {
                $response .= '<success>' . $out['success'] . '</success>';
            }
        }

        return $response;
    }

    /**
     * Get profile form data
     *
     * @param string $type - account type key
     * @param int    $id   - account ID
     *
     * @return string - xml
     **/
    public function getProfileForm($type, $id)
    {
        global $lang, $config;

        if (!$type || !$id) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "() no account type or account ID received, abort");
            return false;
        }

        $account_type_id = $GLOBALS['rlDb']->getOne('ID', "`Key` = '{$type}'", 'account_types');

        $GLOBALS['reefless']->loadClass('Account');

        $fields = $GLOBALS['rlAccount']->getFields($account_type_id);
        $fields = $GLOBALS['rlLang']->replaceLangKeys($fields, 'account_fields', array('name'));
        $fields = $GLOBALS['rlCommon']->fieldValuesAdaptation($fields, 'account_fields');

        $account_data = $GLOBALS['rlDb']->fetch('*', array('ID' => $id), null, 1, 'accounts', 'row');

        /* adapt data */
        $out = $this->adaptForm($fields, $account_data, true, true);

        return $out;
    }

    /**
     * Adapt form
     *
     * @param array  $fields       - fields
     * @param array  $info         - data
     * @param string $edit         - edit mode
     * @param bool   $account_mode - mode
     *
     * @return array - out
     **/
    public function adaptForm(&$fields, &$info, $edit_mode, $account_mode = false)
    {
        global $lang, $rlDb;

        $out = array();

        if ($GLOBALS['plugins']['multiField']) {
            $multi_field_table = $account_mode ? 'account_fields' : 'listing_fields';

            $sql = "SELECT * FROM `{db_prefix}multi_formats` AS `T1`";
            $sql .= "JOIN `{db_prefix}" . $multi_field_table . "` AS `T2` ON `T2`.`Condition` = `T1`.`Key` ";
            $sql .= "WHERE `T1`.`Status` = 'active'";

            if ($this->isMultiFieldNew()) {
                $sql .= " AND `T1`.`Parent_ID` = 0";
            }

            $mf_tmp = $rlDb->getAll($sql);

            foreach ($mf_tmp as $key => $item) {
                $multi_fields[$item['Key']] = true;
            }
        }

        $key_upper = $GLOBALS['json_support'] ? 'Key' : 'key';

        foreach ($fields as &$field) {
            $rebuild = false;
            $data = '';

            if (!$field['Add_page'] && $field['Type'] != 'divider' || ($field['Type'] == 'accept' && $edit_mode)) {
                continue;
            }

            switch ($field['Type']) {
                case 'text':
                    if ($field['Condition']) {
                        $data = $field['Condition'];
                    }
                    $field['Default'] = $field['Default'] ? $lang[$field['pDefault']] : "";
                    break;

                case 'textarea':
                    if ($field['Condition']) {
                        $data = $field['Condition'];
                    }
                    $field['Default'] = $field['Default'] ? $lang[$field['pDefault']] : "";

                    $info[$field['Key']] = nl2br($info[$field['Key']]);
                    break;

                case 'price':
                    $GLOBALS['reefless']->loadClass('Categories');
                    $field['Values'] = $GLOBALS['rlCategories']->getDF('currency');
                    $rebuild = true;
                    break;

                case 'accept':
                    if (version_compare($GLOBALS['config']['rl_version'], '4.7.0') >= 0) {
                        $pageInfo = $rlDb->fetch(
                            array('Page_type', 'Path', 'Controller'),
                            array('Status' => 'active', 'Key' => $field['Default']),
                            null,
                            null,
                            'pages',
                            'row'
                        );

                        switch ($pageInfo['Page_type']) {
                            case 'system':
                                $link = SEO_BASE;
                                if ($pageInfo['Path']) {
                                    $link .= $GLOBALS['config']['mod_rewrite'] ? $pageInfo['Path'] . '.html' : '?page=' . $pageInfo['Path'];
                                }
                                $data = $link;
                                break;

                            case 'static':
                                $data = $lang['pages+content+' . $field['Default']];
                                break;

                            case 'external':
                                $data = $pageInfo['Controller'];
                                break;
                        }
                        $field['name'] = $lang['pages+name+' . $field['Default']];
                        $field['Values'] = $pageInfo['Page_type'];
                    } else {
                        $data = $lang[$field['pDefault']];
                    }
                    break;

                case 'number':
                    if ($field['Values']) {
                        $data = $field['Values'];
                    }
                    $field['Default'] = $field['Default'] ? $lang[$field['pDefault']] : "";
                    break;

                case 'date':
                    if ($field['Default'] == 'multi') {
                        $data = $info[$field['Key'] . '_multi'];
                    }
                    break;

                case 'phone':
                    $data = "{$field['Opt1']}|{$field['Default']}|{$field['Values']}|{$field['Opt2']}";
                    break;

                case 'radio':
                case 'select':
                case 'checkbox':
                case 'mixed':
                    if ($field['Condition']) {
                        $data = $field['Condition'];
                    }

                    if (is_array($field['Default'])) {
                        $field['Default'] = implode(',', $field['Default']);
                    }

                    if ($multi_fields[$field['Key']]) {
                        $data = 'multiField';
                        if (false !== strpos($field['Key'], '_level')) {
                            unset($field['Values']);
                        } else {

                            $GLOBALS['reefless']->loadClass('MultiField', null, 'multiField');
                            if (method_exists($GLOBALS['rlMultiField'], 'getPhrases')) {
                                $GLOBALS['rlMultiField']->getPhrases($field['Condition']);
                            }
                        }
                    }
                    $rebuild = true;
                    break;
            }

            if ($rebuild) {
                if (is_array($field['Values'])) {
                    $values = $field['Values'];
                    $field['Values'] = array();
                    foreach ($values as $key => &$value) {
                        if (trim($key) === '') {
                            continue;
                        }

                        $new_key = $field['Condition'] || in_array($field['Type'], array('price', 'mixed')) ? $value['Key'] : $value['ID'];
                        if ($value['Default']) {
                            $field['Default'] = $new_key;
                        }
                        $field['Values'][$key] = array(
                            $key_upper => $new_key,
                            'name' => $value['name'] ? $value['name'] : $lang[$value['pName']],
                        );
                    }
                    unset($values);
                }
            }

            $this->androidAdaptFormFieldEscort($field, $info, $data);

            /* set fields to send */
            $out[$field['Key']] = array(
                $key_upper => $field['Key'],
                'type' => $field['Type'],
                'required' => $field['Required'],
                'multilingual' => $field['Multilingual'],
                'name' => $field['name'] ? $field['name'] : $lang[$field['pName']],
                'map' => $field['Map'],
                'current' => $info[$field['Key']],
                'default' => $field['Default'],
                'values' => $field['Values'],
                'data' => $data,
            );
        }
        return $out;
    }

    /**
     * Upload profile image
     *
     * @param int    $account_id    - requested account id
     * @param string $password_hash - requested account password hash
     *
     * @return string - custom xml
     **/
    public function uploadProfileImage($account_id, $password_hash)
    {
        global $config, $rlDb;

        if (!$account_id || !$password_hash) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "() no username or passwordHash received, abort");
            return false;
        }

        if ($this->issetAccount($account_id, $password_hash)) {
            $GLOBALS['reefless']->loadClass('Actions');

            if (version_compare($config['rl_version'], '4.6.2', '>=')) {
                require_once __DIR__ . '/adapter/ProfileThumbnailUploadAdapter.php';
                return (new ProfileThumbnailUploadAdapter())->uploadFromGlobals();
            }

            if (version_compare($GLOBALS['config']['rl_version'], '4.6.2') >= 0) {
                $sql = "SELECT `Thumb_width`, `Thumb_height` ";
                $sql .= "FROM `{db_prefix}accounts` AS `T1` ";
                $sql .= "LEFT JOIN `{db_prefix}account_types` AS `T2` ON `T1`.`Type` = `T2`.`Key` ";
                $sql .= "WHERE `T1`.`ID` = {$account_id}";
                $thum_size = $rlDb->getRow($sql);

                $thumb_width = (int) $thum_size['Thumb_width'];
                $thumb_height = (int) $thum_size['Thumb_height'];
            } else {
                $thumb_width = (int) $config['account_thumb_width'];
                $thumb_height = (int) $config['account_thumb_height'];
            }

            $thumb_width = $thumb_width > 0 ? $thumb_width : 110;

            $thumb_height = (int) $config['account_thumb_height'];
            $thumb_height = $thumb_height > 0 ? $thumb_height : 100;

            $thumbnail_name = 'account-thumbnail-' . $account_id . '-' . mt_rand();
            if ($thumbnail_name = $GLOBALS['rlActions']->upload('image', $thumbnail_name, 'C', array($thumb_width, $thumb_height), false, false)) {
                /* remove old file if exists */
                if ($current_thumbnail = $rlDb->getOne('Photo', "`ID` = '{$account_id}'", 'accounts')) {
                    unlink(RL_FILES . $current_thumbnail);
                }

                $update = array(
                    'fields' => array(
                        'Photo' => $thumbnail_name,
                    ),
                    'where' => array(
                        'ID' => $account_id,
                    ),
                );

                $rlDb->updateOne($update, 'accounts');

                $out['account'] = RL_FILES_URL . $thumbnail_name;
            } else {
                $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "() unable to upload image for {$account_id} (id) account, dipper debug required");
                $out['error'] = 'account_unable_upload_image';
            }
        } else {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "() access denied for {$account_id} (id) account");
            $out['error'] = 'account_access_denied';
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";

            $response = $out;
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';
            if ($out['error']) {
                $response .= '<error>' . $out['error'] . '</error>';
            } else {
                $response .= '<account>' . $out['account'] . '</account>';
            }
        }

        return $response;
    }

    /**
     * Update profile/account data
     *
     * @param int    $account_id    - requested account id
     * @param string $password_hash - requested account password hash
     *
     * @return string - custom xml
     **/
    public function updateProfile($account_id, $password_hash)
    {
        global $config, $account_info, $reefless, $rlDb;

        if (!$account_id || !$password_hash) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no username or passwordHash received, abort");
            return false;
        }
        $account_info['ID'] = $account_id;

        if ($this->issetAccount($account_id, $password_hash)) {
            $reefless->loadClass('Account');
            $GLOBALS['profile_info']['Mail'] = $rlDb->getOne('Mail', "`ID` = {$account_id}", 'accounts');

            $fields = $GLOBALS['rlAccount']->getFields($rlDb->getOne('ID', "`Key` = '{$_POST['account_type']}'", 'account_types'));
            $account_data = $GLOBALS['account_data'] = $this->adaptData($fields, $_POST);

            if ($GLOBALS['rlAccount']->editAccount($account_data, $fields, $account_id)) {
                $GLOBALS['rlHook']->load('profileEditAccountValidate');

                /* change account type */
                if ($_REQUEST['current_type'] != $_REQUEST['account_type']) {
                    $update_account_type = array(
                        'fields' => array('Type' => $_REQUEST['account_type']),
                        'where' => array('ID' => $account_id),
                    );
                    $rlDb->updateOne($update_account_type, 'accounts');
                }

                /* change account email in passive mode */
                if (!$config['account_edit_email_confirmation'] && $account_data[$this->system_email_key]) {
                    $update_account_email = array(
                        'fields' => array('Mail' => $account_data[$this->system_email_key]),
                        'where' => array('ID' => $account_id),
                    );
                    $rlDb->updateOne($update_account_email, 'accounts');
                }

            } else {
                $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), unable to edit account ({$account_data['account_id']}) profile, $rlAccount->editAccount() returned false");
                $out['error'] = 'account_access_denied';
            }
        } else {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), access denied for {$account_id} (id) account");
            $out['error'] = 'account_access_denied';
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";

            if ($out['error']) {
                $response = $out;
            } else {
                $response['success'] = true;
            }
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';
            if ($out['error']) {
                $response .= '<error>' . $out['error'] . '</error>';
            } else {
                $response .= '<success>true</success>';
            }
        }

        return $response;
    }

    /**
     * Update profile email address in confirmation mode
     *
     * @param int    $account_id    - requested account id
     * @param string $password_hash - requested account password hash
     * @param string $new_email     - requested account password hash
     *
     * @return string - custom xml
     **/
    public function updateProfileEmail($account_id, $password_hash, $new_email)
    {
        global $config, $rlDb;

        if (!$account_id || !$password_hash || !$new_email) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no username, passwordHash or new email address received, abort");
            return false;
        }

        if ($this->issetAccount($account_id, $password_hash)) {
            $GLOBALS['reefless']->loadClass('Account');

            // check dublicate e-mail
            $email_exist = $rlDb->getOne('Mail', "`Mail` = '{$new_email}' AND `ID` <> '{$account_id}'", 'accounts');

            if ($email_exist) {
                $out['error'] = 'account_email_exist';
            } else {
                $this->getAccountInfo($account_id);
                $this->getPagesInfo();

                // save new e-mail as temporary
                $rlDb->query("UPDATE `{db_prefix}accounts` SET `Mail_tmp` = '{$new_email}' WHERE `ID` = '{$account_id}' LIMIT 1");
                $GLOBALS['rlAccount']->sendEditEmailNotification($account_id, $new_email);
            }
        } else {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), access denied for {$account_id} (id) account");
            $out['error'] = 'account_access_denied';
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";

            if ($out['error']) {
                $response = $out;
            } else {
                $response['success'] = true;
            }
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';
            if ($out['error']) {
                $response .= '<error>' . $out['error'] . '</error>';
            } else {
                $response .= '<success>true</success>';
            }
        }

        return $response;
    }

    /**
     * Update account password
     *
     * @param int    $account_id        - requested account id
     * @param string $password_hash     - requested account password hash
     * @param string $new_password_hash - new password hash
     *
     * @return string - custom xml
     **/
    public function changePassword($account_id, $password, $new_password)
    {
        global $reefless, $rlDb;

        if (!$account_id || !$password) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no username or passwordHash received, abort");
            return false;
        }

        $account = $rlDb->fetch(array('ID', 'Password'), array("ID" => $account_id), "AND `Status` <> 'incomplete'", 1, 'accounts', 'row');

        $verified = FLSecurity::verifyPassword($password, $account['Password']);
        if ($verified) {
            $password_hash = $account['Password'];
        }
        $error = false;

        if ($this->issetAccount($account_id, $password_hash)) {
            $reefless->loadClass('Account');

            $new_password_hash = FLSecurity::cryptPassword($new_password);

            $rlDb->query("UPDATE `{db_prefix}accounts` SET `Password` = '{$new_password_hash}' WHERE `ID` = '{$account_id}' LIMIT 1");
            //$GLOBALS['rlHook']->load('accountChangePassword');
            $out['success'] = $new_password_hash;
        } elseif ($account) {
            $error = true;
            $out['error'] = 'dialog_password_incorrect';
        } else {
            $error = true;
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), access denied for {$account_id} (id) account");
            $out['error'] = 'account_access_denied';
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";

            $response = $out;
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';
            if ($error) {
                $response .= '<error>' . $out['error'] . '</error>';
            } else {
                $response .= '<success><password>' . $out['success'] . '</password></success>';
            }
        }

        return $response;
    }

    /**
     * Get account info
     *
     * @param id $id - account ID
     *
     **/
    public function getAccountInfo($id)
    {
        $id = (int) $id;

        if (!$id) {
            return false;
        }

        $GLOBALS['account_info'] = $GLOBALS['rlDb']->fetch('*', array('ID' => $id), null, 1, 'accounts', 'row');
        $GLOBALS['account_info']['Full_name'] = $GLOBALS['account_info']['First_name'] || $GLOBALS['account_info']['Last_name'] ? $GLOBALS['account_info']['First_name'] . ' ' . $GLOBALS['account_info']['Last_name'] : $GLOBALS['account_info']['Username'];
    }

    /**
     * Get pages info
     *
     **/
    public function getPagesInfo()
    {
        global $rlDb;

        $rlDb->setTable('pages');
        $this->outputRowsMap = array('Key', 'Path');
        $GLOBALS['pages'] = $rlDb->fetch($this->outputRowsMap);
        $rlDb->resetTable();
    }

    /**
     * Adapt data received from the app
     *
     * @param array $fields - fields information
     * @param array $data   - data array received from the app
     *
     * @return array - adapted array of data
     **/
    public function adaptData(&$fields, &$data)
    {
        global $languages;

        if (!$data) {
            return false;
        }

        $out = $data;

        foreach ($languages as $language) {
            $lang_codes[] = $language['Code'];
        }
        $pattern = '/^(.*)(\_(' . implode('|', $lang_codes) . '))$/';
        foreach ($data as $key => &$value) {
            preg_match($pattern, $key, $matches);
            if ($matches[1] && $matches[3]) {
                $out[$matches[1]][$matches[3]] = $value;
                unset($out[$key]);
            }

            switch ($fields[$key]['Type']) {
                case 'checkbox':
                    $out[$key] = array_combine(explode(';', $value), explode(';', $value));

                    break;

                case 'text':
                case 'textarea':
                    $out[$key] = $value;

                    break;

                case 'mixed':
                    $exp = explode('|', $value);
                    $out[$key] = array();
                    $out[$key]['value'] = $exp[0];
                    $out[$key]['df'] = $exp[1];

                    break;

                case 'price':
                    $exp = explode('|', $value);
                    $out[$key] = array();
                    $out[$key]['value'] = $exp[0];
                    $out[$key]['currency'] = $exp[1];

                    break;

                case 'date':
                    if ($fields[$key]['Default'] == 'multi') {
                        $out[$key] = array();
                        $out[$key]['from'] = $value;
                        $out[$key]['to'] = $data[$key . "_multi"];
                    }

                    break;

                case 'phone':
                    // preg_match('/(c:([0-9]+))?\|?(a:([0-9]+))?\|(n:([0-9]+))?\|?(e:([0-9]+))?/', $value, $matches);
                    preg_match('/(c:([0-9]+))?\|?(a:\+?([0-9]+\-?[0-9]?+))?\|(n:([0-9]+))?\|?(e:([0-9]+))?/', $value, $matches);

                    if ($matches[2]) {
                        $phone['code'] = $matches[2];
                    }

                    $phone['area'] = $matches[4];
                    $phone['number'] = $matches[6];

                    if ($matches[8]) {
                        $phone['ext'] = $matches[8];
                    }

                    $out[$key] = $phone;
                    unset($phone);

                    break;
            }

            $this->androidAdaptDataItemEscort($out, $fields, $key, $value);
        }

        return $out;
    }

    /**
     * Addapt value
     *
     * @param array $field - field data
     *
     * @return string $set_value - value
     **/
    public function adaptValue(&$field)
    {
        switch ($field['Type']) {
            case 'phone':
                $set_value = '<a href="tel:' . $field['value'] . '">' . $field['value'] . '</a>';
                break;

            case 'image':
                preg_match('/src\="([^"]+)"/', $field['value'], $matches);
                $set_value = $matches[1];
                break;

            default:
                $set_value = $field['value'];
                break;
        }

        return $set_value;
    }

    /**
     * Remove android photos
     **/
    public function erasePictures()
    {
        set_time_limit(0);

        try {
            $this->rmPics(RL_FILES);
        } catch (Exception $e) {
            system("cd " . RL_FILES . ";rm -rf android*");
        }
    }

    /**
     * Remove android photos forlder
     **/
    public function rmPics($path)
    {
        $i = new DirectoryIterator($path);

        foreach ($i as $f) {
            if ($f->isFile() && preg_match('/^android/i', $f->getFilename())) {
                unlink($f->getPathname());
            } elseif (!$f->isDot() && $f->isDir()) {
                $this->rmPics($f->getPathname());
            }
        }
    }

    /**
     * Install the language
     **/
    public function setupLanguages()
    {

        /* read language file */
        $doc = new DOMDocument();
        $doc->load(RL_PLUGINS . 'androidConnect' . RL_DS . 'languages' . RL_DS . 'English(EN).xml');
        $phrases = $doc->getElementsByTagName('phrase');

        foreach ($phrases as $phrase) {
            $insert[] = array(
                'Code' => 'en',
                'Key' => $phrase->getAttribute("key"),
                'Value' => $phrase->textContent,
            );
        }

        $GLOBALS['rlDb']->insert($insert, 'android_phrases');
    }

    /**
     * Set current timezone to PHP and MySQL
     *
     * @param string $timeZone - timezone of the application user
     **/
    public function setTimeZone($timeZone)
    {

        $GLOBALS['rlValid']->Sql($timeZone);

        if (!$timeZone) {
            return;
        }

        /* set PHP timezone */
        @date_default_timezone_set($timeZone);

        /* set MySQL timezone */
        $period = date("P");
        $GLOBALS['rlDb']->query("SET time_zone = '{$period}'");
    }

    /**
     * Admin Panel bread crumbs handler
     *
     **/
    public function breadCrumbs()
    {
        global $cInfo, $breadCrumbs, $rlSmarty;

        if (preg_match('/^android_/', $cInfo['Controller'])) {
            $breadCrumbs[0]['name'] = 'Android ' . $cInfo['name'];
            $breadCrumbs[0]['Controller'] = $cInfo['Controller'];

            if (!$_GET['action']) {
                $rlSmarty->assign('cpTitle', $cInfo['name']);
            }
        }
    }

    /**
     * Convert fields to xml nodes
     *
     * @param $fields - fields to convert
     * @param $expect - expect keys
     *
     **/
    public function toXmlNode(&$fields, $except)
    {
        foreach ($fields as $key => $field) {
            if (in_array($key, $except)) {
                continue;
            }

            $key = strtolower($key);
            $response .= '<' . $key . '>';
            if (is_array($field)) {
                switch ($key) {
                    case 'fields':
                        foreach ($field as $inner_field) {
                            $response .= '<field key="' . $inner_field['Key'] . '" type="' . $inner_field['Type'] . '"><![CDATA[' . $inner_field['value'] . ']]></field>';
                        }
                        break;

                    case 'statistics':
                        foreach ($field as $section) {
                            $response .= '<section name="' . $section['caption'] . '">';
                            foreach ($section['items'] as $item) {
                                $count = isset($item['count']) ? ' count="' . $item['count'] . '"' : '';
                                $response .= '<item number="' . $item['number'] . '"' . $count . '><![CDATA[' . $item['name'] . ']]></item>';
                            }
                            $response .= '</section>';
                        }
                        break;

                    default:
                        $response .= $this->printValue($field);
                        break;
                }
            } else {
                $response .= '<![CDATA[' . $field . ']]>';
            }
            $response .= '</' . $key . '>';
        }

        return $response;
    }

    /**
     * Add Android menu section in main admin menu
     */
    public function addAdminSection()
    {
        global $_response, $lang, $config;

        // Get all new plugin phrases
        if (!$lang['admin_controllers+name+android']) {
            $sql = "SELECT `Key`, `Value` FROM `{db_prefix}lang_keys` ";
            $sql .= "WHERE `Plugin` = 'androidConnect' AND `Code` = '" . RL_LANG_CODE . "' ";
            $pluginPhrases = (array) $GLOBALS['rlDb']->getAll($sql, ['Key', 'Value']);

            $lang = array_merge($lang, $pluginPhrases);
        }

        $url = RL_URL_HOME . ADMIN . '/';

        $controllers = [
            ['controller' => 'android_languages', 'name' => $lang['admin_controllers+name+android_languages']],
            ['controller' => 'android_settings', 'name' => $lang['admin_controllers+name+android_settings']],
            ['controller' => 'android_listing_types', 'name' => $lang['admin_controllers+name+android_listing_types']],
            ['controller' => 'android_adsense', 'name' => $lang['admin_controllers+name+android_adsense']],
        ];

        $_response->script("
            apMenu['android'] = new Array();
            apMenu['android']['section_name'] = '{$lang['admin_controllers+name+android']}';
        ");

        $plugins_url = RL_PLUGINS_URL;

        $menu_full = <<<VS
            <div id="msection_{$config['android_admin_section_id']}">\
                <div class="caption" id="lb_status_{$config['android_admin_section_id']}">\
                    <div class="icon" style="background: url({$plugins_url}androidConnect/static/gallery.png) 3px 0 no-repeat!important;"></div>\
                    <div class="name">{$lang['admin_controllers+name+android']}</div>\
                </div>\
                \
                <div class="ms_container clear" id="lblock_{$config['android_admin_section_id']}">\
                    <div id="android_section" class="section">
VS;
        foreach ($controllers as $controller) {
            $menu_full .= <<<VS
                <div class="mitem">\
                    <a href="{$url}index.php?controller={$controller['controller']}">{$controller['name']}</a>\
                </div>
VS;
            $_response->script("
                apMenu['android'][{$controller['controller']}] = new Array();
                apMenu['android'][{$controller['controller']}]['Name'] = '{$controller['name']}';
                apMenu['android'][{$controller['controller']}]['Controller'] = '{$controller['controller']}';
                apMenu['android'][{$controller['controller']}]['Vars'] = '';
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

    /**
     * remove Adnroid menu section in main admin menu
     *
     **/
    public function removeAdminSection()
    {
        global $_response, $config;

        $_response->script("
			$('#msection_{$config['android_admin_section_id']}').remove();
		");
    }

    /**
     * Convert to valid int which will be accpeted by java
     *
     **/
    public function setValidInt(&$number)
    {
        $number = is_numeric($number) ? (int) $number : 0;
    }

    /**
     * Get path of the page
     *
     * @param $key - page key
     **/
    public function getPagePath($key)
    {
        if (!$key) {
            return;
        }

        return $GLOBALS['rlDb']->getOne('Path', "`Key` = '{$key}'", 'pages');
    }

    /**
     * Add new phrase when update plugin
     *
     **/
    public function addUpdatePhrases()
    {
        global $rlDb;

        $sql = "SELECT GROUP_CONCAT(`Code`) AS `row` FROM `" . RL_DBPREFIX . "android_languages`";
        $entry = $rlDb->getRow($sql);
        $android_languages = explode(',', $entry['row']);

        $language_xml_files = array();
        foreach (glob(__DIR__ . '/languages/*.xml') as $filename) {
            if (preg_match('/[A-Z]{2}/', $filename, $matches)) {
                $file_code = strtolower($matches[0]);

                if (in_array($file_code, $android_languages)) {
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
                $exists = (bool) $rlDb->getOne('ID', $_where, 'android_phrases');

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
            $rlDb->insert($insert, 'android_phrases');
        }
    }

    /**
     * Get listing form fields by category
     *
     * @param id           - category id
     * @param type         - listing type key
     * @param listing_data - original listing data array
     *
     * @return array $accounts - response type: xml or json
     *
     **/
    public function getFormFields($id, $type, &$listing_data)
    {
        global $lang;

        $GLOBALS['reefless']->loadClass('Categories');
        $category = $GLOBALS['rlCategories']->getCategory($id);
        $form = Category::buildForm(
            $category,
            $this->types[$type],
            $GLOBALS['rlCategories']->fields
        );

        $fields = array();

        foreach ($form as &$group) {
            if ($group['Group_ID'] > 0) {
                $fields[] = array(
                    'Key' => 'group_' . $group['Key'],
                    'Type' => 'divider',
                    'Required' => 0,
                    'Multilingual' => 0,
                    'name' => $lang[$group['pName']],
                    'Condition' => '',
                    'Current' => '',
                );
            }

            if (is_array($group['Fields'])) {
                foreach ($group['Fields'] as $key => $value) {
                    $fields[$value['Key']] = $value;
                }
            }
        }

        $editMode = $listing_data ? true : false;

        unset($form);

        return $this->adaptForm($fields, $listing_data, $editMode);
    }

    /**
     * Add comment
     *
     * @param int  $listing_id - listing id
     * @param int  $account_id - account id
     * @param int  $start      - start
     * @param bool $mode       - mode
     **/
    public function getComments($listing_id, $account_id = 0, $start = 0, $mode = false)
    {
        global $config, $rlValid, $rlDb, $lang;

        /* define start position */
        $limit = $config['android_grid_listings_number'];
        $start = $start > 1 ? ($start - 1) * $limit : 0;

        $listing_id = (int) $listing_id;
        $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.* ";
        $sql .= "FROM `{db_prefix}comments` AS `T1` ";
        $sql .= "WHERE `T1`.`Listing_ID` = {$listing_id} AND `T1`.`Status` = 'active'";
        if ($account_id) {
            $sql .= " OR (`T1`.`Listing_ID` = {$listing_id} AND `T1`.`User_ID` = '{$account_id}' AND `T1`.`Status` = 'pending') ";
        }

        if ($mode) {
            $sql .= "ORDER BY `T1`.`ID` DESC ";
            $sql .= "LIMIT {$start}, {$limit}";
        } else {
            $sql .= "ORDER BY `T1`.`ID` DESC LIMIT 5";
        }

        $data = $rlDb->getAll($sql);

        $calc = $rlDb->getRow("SELECT FOUND_ROWS() AS `calc`");

        if ($GLOBALS['json_support']) {
            if ($mode) {
                $GLOBALS['response_type'] = "json";
            }
            $comments['items'] = $data;
            $comments['count'] = $calc['calc'];
        } else {
            if ($mode) {
                $this->custom_output = true;
                $comments = '<?xml version="1.0" encoding="UTF-8"?><comments><count>' . $calc['calc'] . '</count>';
            } else {
                $comments = '<comments>';
            }

            if ($data) {
                foreach ($data as $comment) {
                    $comment['Description'] = htmlspecialchars_decode(nl2br($comment['Description']));
                    $comment['Date'] = $this->convertDate($comment['Date']);
                    $comment['Rating'] = round((5 * $comment['Rating']) / $config['comments_stars_number']); // 5 because we use 5 stars policy in the app
                    $comments .= '<comment title="' . $comment['Title'] . '" author="' . $comment['Author'] . '" rating="' . $comment['Rating'] . '" status="' . $comment['Status'] . '" date="' . $comment['Date'] . '"><![CDATA[' . $comment['Description'] . ']]></comment>';
                }
            }
            $comments .= '</comments>';

        }
        return $comments;
    }

    /**
     * Add comment
     *
     * @param array $comment - comment data
     *
     **/
    public function addComment(&$data)
    {
        global $config, $reefless;

        $listing_id = (int) $data['listing_id'];
        $account_id = !empty($data['account_id']) ? $data['account_id'] : 0;
        $status = $config['comment_auto_approval'] ? 'active' : 'pending';
        $rating = $data['rating'] ? $data['rating'] : $data['Rating'];
        $rating = round(($config['comments_stars_number'] * (int) $rating) / 5);
        $insert_comment = array(
            'User_ID' => $account_id,
            'Listing_ID' => $listing_id,
            'Author' => $data['author'] ? $data['author'] : $data['Author'],
            'Title' => $data['title'] ? $data['title'] : $data['Title'],
            'Description' => $data['description'] ? $data['description'] : $data['Description'],
            'Rating' => $rating,
            'Status' => $status,
            'Date' => 'NOW()',
        );
        if ($GLOBALS['rlDb']->insertOne($insert_comment, 'comments')) {
            // /* increase count */
            if ($config['comment_auto_approval']) {
                $GLOBALS['rlDb']->query("UPDATE `{db_prefix}listings` SET `comments_count` = `comments_count` + 1 WHERE `ID` = {$listing_id} LIMIT 1");
                $out['comment'] = 'comment_added';
            } else {
                $out['comment'] = 'comment_added_approval';
            }

            if ($config['comments_send_email_after_added_comment']) {
                $reefless->loadClass('Mail');
                $reefless->loadClass('Listings');
                $reefless->loadClass('Account');

                $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('comment_email');

                $listing_info = $GLOBALS['rlListings']->getListing($listing_id);
                $listing_type = $this->types[$listing_info['Listing_type']];
                $account_info = $GLOBALS['rlAccount']->getProfile((int) $listing_info['Account_ID']);
                $listing_title = $GLOBALS['rlListings']->getListingTitle(
                    $listing_info['Category_ID'],
                    $listing_info,
                    $listing_info['Listing_type']
                );

                $message = nl2br($data['description']);

                $page = $this->getPagePath($listing_type['Page_key']);

                $link = SEO_BASE;
                $link .= $config['mod_rewrite'] ? $page . '/' . $listing_info['Category_path'] . '/' . $GLOBALS['rlValid']->str2path($listing_title) . '-' . $listing_id . '.html#comments' : '?page=' . $page . '&amp;id=' . $listing_id . '#comments';
                $link = '<a href="' . $link . '">' . $listing_title . '</a>';

                $mail_tpl['body'] = str_replace(
                    array('{name}', '{author}', '{title}', '{message}', '{listing_title}'),
                    array($account_info['Full_name'], $data['author'], $data['title'], $message, $link),
                    $mail_tpl['body']
                );
                $GLOBALS['rlMail']->send($mail_tpl, $account_info['Mail']);
            }
        } else {
            $GLOBALS['rlDebug']->logger("ANDROID: addComment() error when add new comment");
            $out['error'] = 'comment_not_added';
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response = $out;
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';
            if ($out['error']) {
                $response .= '<error>' . $out['error'] . '</error>';
            } else {
                $response .= '<comment>' . $out['comment'] . '</comment>';
            }
        }

        return $response;
    }

    /**
     * Add listing text data
     *
     * @param int    $account_id - requested account id
     * @param string $password_hash - requested account password hash
     *
     * @return string - custom xml
     **/
    public function addListing($account_id, $password_hash)
    {
        global $config, $reefless, $rlValid, $rlHook, $category, $account_info, $listing_id;

        if (!$account_id || !$password_hash) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no username or passwordHash received, abort");
            return false;
        }

        if ($this->issetAccount($account_id, $password_hash)) {
            $reefless->loadClass('Plan');
            $reefless->loadClass('Account');
            $reefless->loadClass('Categories');
            $reefless->loadClass('Common');
            $reefless->loadClass('Listings');

            $listing_type_key = $rlValid->xSql($_REQUEST['listing_type_key']);
            $category_id = (int) $_REQUEST['listing_category_id'];
            $plan_id = (int) $_REQUEST['listing_plan_id'];

            $plan_info = $GLOBALS['rlPlan']->getPlan($plan_id, $account_id);

            $form = Category::buildForm(
                $category_id,
                $this->types[$listing_type_key],
                $GLOBALS['rlCategories']->fields
            );

            foreach ($GLOBALS['rlCategories']->fields as $field) {
                $fields[$field['Key']] = $field;
            }

            $data = $this->adaptData($fields, $_POST);

            /* simulate data using in the create method */
            $category['ID'] = $category_id;
            $this->getAccountInfo($account_id);

            try {

                $info['Category_ID'] = $category['ID'];
                $info['Account_ID'] = $account_info['ID'];
                $info['Status'] = 'incomplete';
                $info['Last_type'] = $listing_type_key;
                $info['Last_step'] = 'form';
                $info['Date'] = 'NOW()';
                if ($plan_info['Crossed']) {
                    $info['Crossed'] = implode(',', $_POST['crossed_categories']);
                }

                if ($GLOBALS['rlListings']->create($info, $data, $fields, $plan_info)) {
                    $listing_id = $GLOBALS['rlListings']->id;
                }

                if ($listing_id) {

                    // add youtube video
                    $this->manageYouTubeVideo($_REQUEST['youtube_video_ids'], $listing_id, '');

                    // simulate instance
                    $this->listingID = $listing_id;
                    // complete saving
                    $rlHook->load('afterListingCreate', $this, $info, $data, $plan_info);

                    // update status
                    $this->completeAddListing($listing_id, $plan_info, $rlValid->xSql($_REQUEST['plan_listing_type']), $account_id, $listing_type_key, $category_id);

                    // send success
                    $out['success'] = $this->buildReturnListing($listing_id);
                } else {
                    $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), add() method returned false");
                    $out['error'] = 'add_listing_save_data_fail';
                }
            } catch (Exception $e) {
                $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), the exception is the following: " . $e->getMessage());
                $out['error'] = 'add_listing_save_data_fail';
            }
        } else {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), access denied for {$account_id} (id) account");
            $out['error'] = 'account_access_denied';
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";

            $response = $out;
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';
            if ($out['error']) {
                $response .= '<error>' . $out['error'] . '</error>';
            } else {
                $response .= '<success>' . $out['success'] . '</success>';
            }
        }

        return $response;
    }

    /**
     * Edit listing text data
     *
     * @param int    $listing_id    - requested listing id
     * @param int    $account_id    - requested account id
     * @param string $password_hash - requested account password hash
     *
     * @return string - custom xml
     **/
    public function editListing($listing_id, $account_id, $password_hash)
    {
        global $config, $reefless, $rlValid, $rlHook, $rlDb, $account_info;

        if (!$account_id || !$password_hash || !$listing_id) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no listing_id, username or passwordHash received, abort");
            return false;
        }

        if (!$this->issetAccount($account_id, $password_hash)) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no listing_id, username or passwordHash received, abort");
            return false;
        }

        if ($rlDb->getOne('ID', "`ID` = {$listing_id} AND `Account_ID` = {$account_id}", 'listings')) {
            $reefless->loadClass('Plan');
            $reefless->loadClass('Account');
            $reefless->loadClass('Categories');
            $reefless->loadClass('Common');
            $reefless->loadClass('Listings');

            // get listing data
            $sql = "SELECT `T1`.*, `T1`.`Plan_ID`, `T1`.`Category_ID`, `T3`.`Type` AS `Listing_type` ";
            $sql .= "FROM `{db_prefix}listings` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
            $sql .= "WHERE `T1`.`ID` = {$listing_id}";
            $listing = $rlDb->getRow($sql);

            // get account info
            $this->getAccountInfo($account_id);

            // get plan data
            $plan_info = $rlDb->fetch(
                array('ID', 'Key', 'Type', 'Cross', 'Price', 'Listing_number', 'Cross', 'Image', 'Image_unlim', 'Video', 'Video_unlim'),
                array('ID' => $listing['Plan_ID'], 'Status' => 'active'),
                null, 1, 'listing_plans', 'row');

            $category_id = $_REQUEST['listing_category_id'] ? (int) $_REQUEST['listing_category_id'] : $listing['Category_ID'];

            // get related form fields
            Category::buildForm(
                $category_id,
                $this->types[$listing['Listing_type']],
                $GLOBALS['rlCategories']->fields
            );

            foreach ($GLOBALS['rlCategories']->fields as $field) {
                $fields[$field['Key']] = $field;
            }

            $data = $this->adaptData($fields, $_POST);

            // Required for priceHistory plugin
            $GLOBALS['data'] = $data;
            $GLOBALS['listing'] = $listing;
            $GLOBALS['listing_id'] = $listing_id;
            $GLOBALS['rlHook']->load('editListingAdditionalInfo');

            try {
                $edited = false;

                if (!$config['edit_listing_auto_approval']) {
                    $info['Status'] = 'pending';
                } else {
                    $info['Status'] = $listing['Status'];
                }
                $info['Category_ID'] = $category_id;
                if ($GLOBALS['rlListings']->edit($listing_id, $info, $data, $fields, $plan_info)) {
                    $edited = true;
                }

                if ($edited) {

                    // add youtube video
                    $this->manageYouTubeVideo($_REQUEST['youtube_video_ids'], $listing_id, $_REQUEST['removed_video_ids']);

                    // remove pictures
                    $this->removePictures($_REQUEST['removed_picture_ids'], $listing_id);

                    // change status if plan free
                    if ($account_info['Status'] == 'active' && $listing['Status'] == 'incomplete' && $plan_info['Price'] == 0) {
                        $status = $config['listing_auto_approval'] ? 'active' : 'pending';
                        $GLOBALS['rlDb']->query("UPDATE `{db_prefix}listings` SET `Status` = '{$status}' WHERE `ID` = {$listing_id}");
                    }

                    // complete listing
                    $rlHook->load('afterListingEdit');

                    $out['success'] = $this->buildReturnListing($listing_id);

                    $this->completeEditListing($listing_id, $listing, $listing['Category_ID'], $listing['Listing_type']);
                } else {
                    $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), edit() method returned false");
                    $out['error'] = 'edit_listing_save_data_fail';
                }
            } catch (Exception $e) {
                $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), the exception is the following: " . $e->getMessage());
                $out['error'] = 'edit_listing_save_data_fail';
            }
        } else {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), access denied for {$account_id} (id) account");
            $out['error'] = 'account_access_denied';
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response = $out;
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';
            if ($out['error']) {
                $response .= '<error>' . $out['error'] . '</error>';
            } else {
                $response .= '<success>' . $out['success'] . '</success>';
            }
        }

        return $response;
    }

    /**
     * Remove pictures which were removed during edit listing pricess
     *
     * @param string $ids        - video IDs separated by ','
     * @param int    $listing_id - requested listing id
     *
     **/
    public function removePictures($ids, $listing_id)
    {
        global $rlDb;
        $picture_ids = explode(',', $ids);

        if (!$listing_id || empty($picture_ids[0])) {
            return;
        }

        foreach ($picture_ids as $id) {
            $picture = $rlDb->fetch(array('Photo', 'Thumbnail', 'Original'), array('ID' => $id, 'Listing_ID' => $listing_id), null, 1, 'listing_photos', 'row');

            unlink(RL_FILES . $picture['Photo']);
            unlink(RL_FILES . $picture['Thumbnail']);
            unlink(RL_FILES . $picture['Original']);

            $rlDb->query("DELETE FROM `{db_prefix}listing_photos` WHERE `ID` = {$id} AND `Listing_ID` = {$listing_id} LIMIT 1");

            $GLOBALS['rlListings']->updatePhotoData($listing_id);
        }
    }

    /**
     * Manage youtube videos, delete removed and add new
     *
     * @param string $videos     - video IDs separated by ||
     * @param int    $listing_id - requested listing id
     * @param string $remove_ids - remove ids
     *
     **/
    public function manageYouTubeVideo($videos, $listing_id, $remove_ids = '')
    {
        $youtube_video_ids = explode('||', $videos);

        if ($remove_ids) {
            $tmp_ids = explode('||', $remove_ids);
            if ($tmp_ids) {
                foreach ($tmp_ids as $id) {
                    $GLOBALS['rlDb']->query("DELETE FROM `{db_prefix}listing_photos` WHERE `ID` = {$id} AND `Listing_ID` = {$listing_id} LIMIT 1");
                    $GLOBALS['rlListings']->updatePhotoData($listing_id);
                }
            }
        }

        if (!$listing_id || empty($youtube_video_ids[0])) {
            return;
        }

        // remove videos
        $sql = "DELETE FROM `{db_prefix}listing_photos` WHERE `Listing_ID` = {$listing_id} AND `Type` = 'video' AND `Photo` NOT IN ('" . implode("','", $youtube_video_ids) . "')";
        $GLOBALS['rlDb']->query($sql);

        // add videos
        $video_position = 1;
        foreach ($youtube_video_ids as $youtube_id) {
            if ($youtube_id && !$GLOBALS['rlDb']->getOne('ID', "`Listing_ID` = {$listing_id} AND `Photo` = '{$youtube_id}'", 'listing_photos')) {
                $ut_video = json_decode($GLOBALS['reefless']->getPageContent("https://www.youtube.com/oembed?format=json&url=http://www.youtube.com/watch?v={$youtube_id}"), true);

                $youtube_videos[] = array(
                    'Type' => 'video',
                    'Listing_ID' => $listing_id,
                    'Position' => $video_position,
                    'Original' => 'youtube',
                    'Photo' => $youtube_id,
                    'Description' => $ut_video['title'],
                );

                $video_position++;
            }
        }

        if ($youtube_videos) {
            $GLOBALS['rlDb']->insert($youtube_videos, 'listing_photos');
        }
    }

    /**
     * Complete add listing listing, handle plan options and category counters
     *
     * @param int    $listing_id       - requested listing id
     * @param array  $plan_info        - plan information
     * @param string $appearence       - listing plan type: standard or featured
     * @param int    $account_id       - requested account id
     * @param int    $listing_type_key - listing type key
     * @param int    $category_id      - listing category id
     **/
    public function completeAddListing($listing_id, &$plan_info, $appearence, $account_id, $listing_type_key, $category_id)
    {
        global $config, $lang, $reefless, $rlValid, $rlDb;

        $reefless->loadClass('Mail');
        $reefless->loadClass('Account');

        $free = true;
        $last_stap = '';
        $featured = false;
        $account_info = $GLOBALS['rlAccount']->getProfile((int) $account_id);

        $set_status = $config['listing_auto_approval'] && $account_info['Status'] == 'active' ? 'active' : 'pending';

        $paid_status = $plan_info['Price'] ? $lang['not_paid'] : $lang['free'];

        $listing_data = $rlDb->fetch('*', array('ID' => $listing_id), null, 1, 'listings', 'row');
        $listing_title = $GLOBALS['rlListings']->getListingTitle($category_id, $listing_data, $listing_type_key);

        // paid listing
        if (($plan_info['Type'] == 'package' && $plan_info['Price'] > 0 && !$plan_info['Package_ID']) ||
            ($plan_info['Type'] == 'listing' && $plan_info['Price'] > 0)
        ) {
            $set_status = 'incomplete';
            $last_stap = 'checkout';
            $free = false;
        }

        // featured
        if ($plan_info['Featured'] && (!$plan_info['Advanced_mode'] || ($plan_info['Advanced_mode'] && $appearence == 'featured'))) {
            $featured = true;
        }

        $update_status = array(
            'fields' => array(
                'Status' => $set_status,
                'Pay_date' => $free ? 'NOW()' : '',
                'Featured_ID' => $featured ? $plan_info['ID'] : 0,
                'Featured_date' => $featured && $free ? 'NOW()' : '',
                'Last_type' => $appearence == 'featured' ? 'featured' : 'standard',
                'Last_step' => $last_stap,
            ),
            'where' => array(
                'ID' => $listing_id,
            ),
        );
        $GLOBALS['rlDb']->updateOne($update_status, 'listings');

        // free listing or exist/free package mode
        if (($plan_info['Type'] == 'package' && ($plan_info['Package_ID'] || $plan_info['Price'] <= 0)) ||
            ($plan_info['Type'] == 'listing' && $plan_info['Price'] <= 0)
        ) {
            // available package mode
            if ($plan_info['Type'] == 'package' && $plan_info['Package_ID']) {
                if ($plan_info['Listings_remains'] != 0) {
                    $update_entry = array(
                        'fields' => array(
                            'Listings_remains' => (int) $plan_info['Listings_remains'] ? $plan_info['Listings_remains'] - 1 : 0,
                        ),
                        'where' => array(
                            'ID' => $plan_info['Package_ID'],
                        ),
                    );

                    if ($plan_info[ucfirst($appearence) . '_listings'] != 0) {
                        $update_plan_key = ucfirst($appearence) . '_remains';
                        $update_entry['fields'][$update_plan_key] = (int) $plan_info[$update_plan_key] ? $plan_info[$update_plan_key] - 1 : 0;
                    }

                    $GLOBALS['rlDb']->updateOne($update_entry, 'listing_packages');
                }

                // set paid status
                $paid_status = $lang['purchased_packages'];
            }
            // free package mode
            elseif ($plan_info['Type'] == 'package' && !$plan_info['Package_ID'] && $plan_info['Price'] <= 0) {

                $insert_entry = array(
                    'Account_ID' => $account_id,
                    'Plan_ID' => $plan_info['ID'],
                    'Listings_remains' => $plan_info[ucfirst($appearence) . '_listings'] == 0 ? $plan_info['Listing_number'] : $plan_info['Listing_number'] - 1,
                    'Type' => 'package',
                    'Date' => 'NOW()',
                    'IP' => Util::getClientIP(),
                );

                if ($plan_info['Featured'] && $plan_info['Advanced_mode'] && $plan_info['Standard_listings']) {
                    $insert_entry['Standard_remains'] = $plan_info['Standard_listings'];
                }
                if ($plan_info['Featured'] && $plan_info['Advanced_mode'] && $plan_info['Featured_listings']) {
                    $insert_entry['Featured_remains'] = $plan_info['Featured_listings'];
                }

                if ($plan_info[ucfirst($appearence) . '_listings'] != 0) {
                    $insert_entry[ucfirst($appearence) . '_remains'] = $plan_info[ucfirst($appearence) . '_listings'] - 1;
                } elseif (!$plan_info['Advanced_mode']) {
                    $insert_entry['Listings_remains'] = $plan_info['Listing_number'] - 1;
                }

                $GLOBALS['rlDb']->insertOne($insert_entry, 'listing_packages');

                /* set paid status */
                $paid_status = $lang['package_plan'] . '(' . $lang['free'] . ')';
            }
            // limited listing mode
            elseif ($plan_info['Type'] == 'listing' && $plan_info['Limit'] > 0) {
                /* update/insert limited plan using entry */
                if (empty($plan_info['Using'])) {
                    $plan_using_insert = array(
                        'Account_ID' => $account_id,
                        'Plan_ID' => $plan_info['ID'],
                        'Listings_remains' => $plan_info['Limit'] - 1,
                        'Type' => 'limited',
                        'Date' => 'NOW()',
                        'IP' => Util::getClientIP(),
                    );

                    $GLOBALS['rlDb']->insertOne($plan_using_insert, 'listing_packages');
                } else {
                    $plan_using_update = array(
                        'fields' => array(
                            'Account_ID' => $account_id,
                            'Plan_ID' => $plan_info['ID'],
                            'Listings_remains' => $plan_info['Using'] - 1,
                            'Type' => 'limited',
                            'Date' => 'NOW()',
                            'IP' => Util::getClientIP(),
                        ),
                        'where' => array(
                            'ID' => $plan_info['Plan_using_ID'],
                        ),
                    );

                    $GLOBALS['rlDb']->updateOne($plan_using_update, 'listing_packages');
                }
            }

            /* recount category listings count */
            if ($config['listing_auto_approval']) {
                $GLOBALS['rlCategories']->listingsIncrease($category_id);
                if (method_exists($GLOBALS['rlCategories'], 'accountListingsIncrease')) {
                    $GLOBALS['rlCategories']->accountListingsIncrease($account_id);
                }
            }

            /* send message to listing owner */
            $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate(
                $config['listing_auto_approval'] ? 'free_active_listing_created' : 'free_approval_listing_created'
            );

            $lt_page_path = $rlDb->getOne('Path', "`Key` = 'lt_{$listing_type_key}'", 'pages');
            $my_page_path = $rlDb->getOne('Path', "`Key` = 'my_{$listing_type_key}'", 'pages');
            $category_path = $rlDb->getOne('Path', "`ID` = '{$category_id}'", 'categories');

            $link = SEO_BASE;
            if ($config['listing_auto_approval']) {
                $link .= $config['mod_rewrite'] ? $lt_page_path . '/' . $category_path . '/' . $rlValid->str2path($listing_title) . '-' . $listing_id . '.html' : '?page=' . $lt_page_path . '&id=' . $listing_id;
            } else {
                $link .= $config['mod_rewrite'] ? $my_page_path . '.html' : '?page=' . $my_page_path;
            }

            $mail_tpl['body'] = str_replace(
                array('{name}', '{link}'),
                array($account_info['Full_name'], '<a href="' . $link . '">' . $link . '</a>'),
                $mail_tpl['body']
            );
            $GLOBALS['rlMail']->send($mail_tpl, $account_info['Mail']);
        }

        /* send admin notification */
        $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('admin_listing_added');

        $m_find = array('{name}', '{link}', '{date}', '{status}', '{paid}');
        $m_replace = array(
            $account_info['Full_name'],
            '<a href="' . RL_URL_HOME . ADMIN . '/index.php?controller=listings&amp;action=view&amp;id=' . $listing_id . '">' . $listing_title . '</a>',
            date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT)),
            $lang[$config['listing_auto_approval'] ? 'active' : 'pending'],
            $paid_status,
        );
        $mail_tpl['body'] = str_replace($m_find, $m_replace, $mail_tpl['body']);

        if ($config['listing_auto_approval']) {
            $mail_tpl['body'] = preg_replace('/\{if activation is enabled\}(.*)\{\/if\}/', '', $mail_tpl['body']);
        } else {
            $activation_link = RL_URL_HOME . ADMIN . '/index.php?controller=listings&amp;action=remote_activation&amp;id=' . $listing_id . '&amp;hash=' . md5($rlDb->getOne('Date', "`ID` = {$listing_id}", 'listings'));
            $activation_link = '<a href="' . $activation_link . '">' . $activation_link . '</a>';
            $mail_tpl['body'] = preg_replace('/(\{if activation is enabled\})(.*)(\{activation_link\})(.*)(\{\/if\})/', '$2 ' . $activation_link . ' $4', $mail_tpl['body']);
        }

        $GLOBALS['rlMail']->send($mail_tpl, $config['notifications_email']);
    }

    /**
     * Complete edit listing listing, send notification messages
     *
     * @param int    $listing_id       - requested listing id
     * @param array  $listing          - listing data before edit
     * @param int    $category_id      - category id
     * @param string $listing_type_key - listing type key
     *
     **/
    public function completeEditListing($listing_id, &$listing, $category_id, $listing_type_key)
    {
        global $config, $lang, $rlValid, $account_info, $rlDb;

        /* get updated listing info */
        $sql = "SELECT `T1`.*, `T1`.`Plan_ID`, `T1`.`Category_ID`, `T3`.`Type` AS `Listing_type` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = {$listing_id}";
        $updated_listing = $rlDb->getRow($sql);

        /* send notification to admin and owner */
        if (!$config['edit_listing_auto_approval'] && serialize($updated_listing) != serialize($listing)) {
            $GLOBALS['reefless']->loadClass('Mail');

            $listing_title = $GLOBALS['rlListings']->getListingTitle($category_id, $updated_listing, $listing_type_key);

            /* send to admin */
            $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('admin_listing_edited');

            $lt_page_path = $rlDb->getOne('Path', "`Key` = 'lt_{$listing_type_key}'", 'pages');
            $my_page_path = $rlDb->getOne('Path', "`Key` = 'my_{$listing_type_key}'", 'pages');
            $category_path = $rlDb->getOne('Path', "`ID` = '{$category_id}'", 'categories');

            $link = SEO_BASE;
            $link .= $config['mod_rewrite'] ? $lt_page_path . '/' . $category_path . '/' . $rlValid->str2path($listing_title) . '-' . $listing_id . '.html' : '?page=' . $lt_page_path . '&amp;id=' . $listing_id;
            $activation_link = RL_URL_HOME . ADMIN . '/index.php?controller=listings&amp;action=remote_activation&amp;id=' . $listing_id . '&amp;hash=' . md5($updated_listing['Date']);
            $activation_link = '<a href="' . $activation_link . '">' . $activation_link . '</a>';

            $m_find = array('{name}', '{link}', '{date}', '{status}', '{activation_link}');
            $m_replace = array(
                $account_info['Full_name'],
                '<a href="' . RL_URL_HOME . ADMIN . '/index.php?controller=listings&amp;action=view&amp;id=' . $listing_id . '">' . $listing_title . '</a>',
                date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT)),
                $lang['suspended'],
                $activation_link,
            );
            $mail_tpl['body'] = str_replace($m_find, $m_replace, $mail_tpl['body']);
            $GLOBALS['rlMail']->send($mail_tpl, $config['notifications_email']);

            /* send to owner */
            $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('edit_listing_pending');
            $mail_tpl['body'] = preg_replace('/\[(.+)\]/', '<a href="' . $link . '">$1</a>', $mail_tpl['body']);
            $mail_tpl['body'] = str_replace('{name}', $account_info['Full_name'], $mail_tpl['body']);

            $GLOBALS['rlMail']->send($mail_tpl, $account_info['Mail']);

            /* dicrease related category counter */
            $GLOBALS['rlCategories']->listingsDecrease($category_id);
            $GLOBALS['rlCategories']->accountListingsDecrease($account_id);
        }
    }

    /**
     * Save listing picture
     *
     * @param int    $account_id    - requested account id
     * @param string $password_hash - requested account password hash
     *
     * @return string - custom xml
     **/
    public function savePicture($account_id, $password_hash)
    {

        if (!$account_id || !$password_hash) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "() no username or passwordHash received, abort");
            return false;
        }

        if (!$this->issetAccount($account_id, $password_hash)) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), wrong login details provided, abort");
            return false;
        }

        $listing_id = (int) $_REQUEST['listing_id'];

        $photoDetails = array(
            'photo_id' => (int) $_REQUEST['exist_id'],
            'orientation' => (int) $_REQUEST['orientation'],
            'desc' => $_REQUEST['description'],
        );

        if ($photoDetails['photo_id']) {
            $this->updateListingPhotoDescription($photoDetails['photo_id'], $photoDetails['desc']);
        } else {

            require __DIR__ . '/adapter/ListingPictureUploadAdapter.php';
            $GLOBALS['reefless']->loadClass('Listings');

            $uploader = (new ListingPictureUploadAdapter())
                ->setListingId($listing_id)
                ->setImageOrientation($photoDetails['orientation']);
            $response = $uploader->uploadFromGlobals();

            $this->updateListingPhotoDescription($response['id'], $photoDetails['desc']);

        }

        if ($_REQUEST['last']) {
            $out['success'] = $this->buildReturnListing($listing_id, true);
        } else {
            $out['success'] = true;
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response = $out;
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';
            if ($out['error']) {
                $response .= '<error>' . $out['error'] . '</error>';
            } else {
                $response .= '<success>' . $out['success'] . '</success>';
            }
        }

        return $response;
    }

    /**
     * Update the listing photo description
     *
     * @since 4.0.0
     *
     * @param int    $photoId
     * @param string $description
     *
     * @return bool
     */
    public function updateListingPhotoDescription($photoId, $description)
    {
        $update = array(
            'fields' => array(
                'Description' => $description,
            ),
            'where' => array(
                'ID' => $photoId,
            ),
        );
        $GLOBALS['rlDb']->updateOne($update, 'listing_photos');
    }

    /**
     * Build the listing short details to return to the server
     *
     * @param int     $listing_id   - listing ID
     * @param boolean $picture_mode - get picture details only
     *
     * @return string - custom xml
     **/
    public function buildReturnListing($listing_id, $picture_mode = false)
    {
        global $lang, $reefless, $rlDb;

        // picture mode
        if ($picture_mode) {
            $reefless->loadClass('Listings');
            $GLOBALS['rlListings']->updatePhotoData($listing_id, true);

            $listing = $rlDb->fetch(array('Main_photo', 'Photos_count'), array('ID' => $listing_id), null, 1, 'listings', 'row');
            if ($GLOBALS['config']['thumbnails_x2'] && $listing['Main_photo_x2']) {
                $listing['Main_photo'] = $listing['Main_photo_x2'];
            }
            $listing['photo'] = $listing['Main_photo'] ? RL_FILES_URL . $listing['Main_photo'] : '';

            unset($listing['Main_photo']);

            if ($GLOBALS['json_support']) {
                return $listing;
            } else {
                return $this->printValue($listing);
            }
        }

        // default (full) mode
        global $sql;

        $reefless->loadClass('Hook');
        $reefless->loadClass('Listings');
        $reefless->loadClass('Common');

        $sql = "SELECT `T1`.*, ";
        $sql .= "IF((TIMESTAMPDIFF(HOUR, `T1`.`Pay_date`, NOW()) >= `T2`.`Listing_period` * 24) AND `T2`.`Listing_period` != 0, 'expired', `T1`.`Status`) as `Status`, ";
        $sql .= "DATE_ADD(`T1`.`Pay_date`, INTERVAL `T2`.`Listing_period` DAY) AS `Plan_expire`, DATE_ADD(`T1`.`Featured_date`, INTERVAL `T3`.`Listing_period` DAY) AS `Featured_expire`, ";
        $sql .= "`T2`.`Type` AS `Plan_type`, `T2`.`Price` AS `Plan_price`, CONCAT('categories+name+', `T4`.`Key`) AS `Cat_key`, CONCAT('listing_plans+name+', `T2`.`Key`) AS `Plan_key`, ";
        $sql .= "`T2`.`Key` AS `Plan_real_key` ";

        $GLOBALS['rlHook']->load('myListingsSqlFields', $sql); // > 4.1.0

        $sql .= ", `T4`.`Type` AS `Listing_type` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T3` ON `T1`.`Featured_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T4` ON `T1`.`Category_ID` = `T4`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = {$listing_id}";
        $tmp = $rlDb->getRow($sql);

        // adapt values
        $tmp['category_name'] = $lang[$tmp['Cat_key']];
        $tmp['plan_name'] = $lang[$tmp['Plan_key']];
        $tmp['title'] = $GLOBALS['rlListings']->getListingTitle($tmp['Category_ID'], $tmp, $tmp['Listing_type']);
        $tmp['photo'] = $tmp['Main_photo'] ? RL_FILES_URL . $tmp['Main_photo'] : '';
        $tmp['Sub_status'] = $lang[in_array($tmp['Sub_status'], array('visible', 'invisible')) ? 'ls_' . $tmp['Sub_status'] : 'lsl_'+$tmp['Sub_status']];

        foreach ($tmp as $key => $value) {
            // convert date
            if (in_array($key, array('Pay_date', 'Featured_date', 'Plan_expire'))) {
                $value = $this->convertDate($value);
            }

            if (in_array($key, $this->transfer_my_listings_grid_fields)) {
                $listing[$key] = $value;
            }
        }

        // set price
        if ($tmp && array_key_exists($this->price_key, $tmp)) {
            $fields = $GLOBALS['rlListings']->getFormFields($tmp['Category_ID'], 'short_forms', $tmp['Listing_type']);
            $listing['price'] = $GLOBALS['rlCommon']->adaptValue(
                $fields[$this->price_key],
                $tmp[$this->price_key],
                'listing',
                $tmp['ID'],
                true,
                false,
                false,
                false,
                $tmp['Account_ID'],
                'short_form',
                $tmp['Listing_type']
            );
        }

        unset($tmp, $listing['Main_photo']);
        if ($GLOBALS['json_support']) {
            return $listing;
        } else {
            return $this->printValue($listing);
        }
    }

    /**
     * Get my listing by listing type
     *
     * @param string $listing_type  - requested listing type
     * @param int    $account_id    - requested account id
     * @param string $password_hash - requested account password hash
     * @param int    $start         - stack position
     *
     * @return string - custom xml
     **/
    public function getMyListings($listing_type, $account_id, $password_hash, $start = 0)
    {
        global $account_info, $lang;

        if (!$account_id || !$password_hash) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no username or passwordHash received, abort");
            return false;
        }

        if (!$listing_type) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no listing type specified, abort");
            return false;
        }

        $GLOBALS['reefless']->loadClass('Listings');

        $account_info['ID'] = $account_id; // simulate account info array which is using inside getMyListing method

        if ($this->issetAccount($account_id, $password_hash)) {
            $listings = $GLOBALS['rlListings']->getMyListings($listing_type, 'ID', 'desc', $start, $this->grid_listings_limit);

            foreach ($listings as $index => &$listing) {
                $fields = &$listing['fields'];

                // adapt values
                $listing['category_name'] = $lang[$listing['Cat_key']];
                $listing['plan_name'] = $lang[$listing['Plan_key']];
                if ($GLOBALS['config']['thumbnails_x2'] && $listing['Main_photo_x2']) {
                    $listing['Main_photo'] = $listing['Main_photo_x2'];
                }
                $listing['photo'] = $listing['Main_photo'] ? RL_FILES_URL . $listing['Main_photo'] : '';
                $listing['title'] = $listing['listing_title'];
                $listing['Sub_status'] = $lang[in_array($listing['Sub_status'], array('visible', 'invisible')) ? 'ls_' . $listing['Sub_status'] : 'lsl_' . $listing['Sub_status']];

                foreach ($listing as $key => &$field) {
                    if (!in_array($key, $this->transfer_my_listings_grid_fields)) {
                        unset($listings[$index][$key]);
                    }

                    // convert date
                    if (in_array($key, array('Date', 'Pay_date', 'Featured_date', 'Plan_expire')) || ($key == 'Featured_expire' && !empty($field))) {
                        $field = $this->convertDate($field);
                    }
                }

                // set price
                if ($fields && array_key_exists($this->price_key, $fields)) {
                    $listing['price'] = $fields[$this->price_key]['value'];
                    unset($fields[$this->price_key]);
                }
            }

            if ($GLOBALS['json_support']) {
                $out['listings'] = $listings;
                $out['total'] = $GLOBALS['rlListings']->calc;
            } else {
                $out = $listings;
                $out[] = array(
                    'total' => $GLOBALS['rlListings']->calc,
                    'node_name' => 'statistic',
                );
            }
        } else {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), access denied for {$account_id} (id) account");
            $out = '<error>account_access_denied</error>';
        }

        return $out;
    }

    /**
     * Get listing information for "Edit Listing" process
     *
     * @param int    $id            - requested listing id
     * @param string $listing_type  - requested listing type
     * @param int    $account_id    - requested account id
     * @param string $password_hash - requested account password hash
     *
     * @return string - custom xml
     **/
    public function getEditListingInfo($listing_id, $listing_type, $account_id, $password_hash, $category_id)
    {
        global $config, $rlDb;

        if (!$account_id || !$password_hash || !$listing_id) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "() no listing ID, username or passwordHash received, abort");
            return false;
        }
        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            if ($this->issetAccount($account_id, $password_hash)) {

                // get listing info
                $sql = "SELECT `T1`.*, `T2`.`Cross` AS `Plan_crossed`, `T2`.`Key` AS `Plan_key`, `T3`.`Type` AS `Listing_type` ";
                $sql .= "FROM `{db_prefix}listings` AS `T1` ";
                $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
                $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
                $sql .= "WHERE `T1`.`ID` = {$listing_id}";

                $listing = $rlDb->getRow($sql);

                /* change category */
                $listing['Category_ID'] = $category_id ? $category_id : $listing['Category_ID'];

                /* ADD LISTING DATA */
                $response['data'] = $listing;

                /* ADD CATEGORIES DATA */
                // get category info
                $category = $rlDb->fetch(array('Level', 'Parent_ID', 'Parent_IDs'), array('ID' => $listing['Category_ID']), null, 1, 'categories', 'row');

                // get category parents
                $category_parents[] = 0;
                if ($category['Parent_IDs']) {
                    $category_parents = array_merge($category_parents, explode(',', $category['Parent_IDs']));
                }

                // add parent categories data
                foreach ($category_parents as $parent) {
                    $response['category'][] = array('id' => $parent, 'items' => $this->getCatTree($listing_type, $parent));
                }

                /* ADD FORM DATA */
                $response['form'] = $this->getFormFields($listing['Category_ID'], $listing_type, $listing);

                /* ADD PLAN DATA */
                $plan = $rlDb->fetch(array('ID', 'Key', 'Type', 'Featured', 'Price', 'Image', 'Image_unlim', 'Video', 'Video_unlim'), array('ID' => $listing['Plan_ID']), null, 1, 'listing_plans', 'row');
                $response['plan'] = $plan;

                /* ADD PICTURES DATA */
                $rlDb->setTable('listing_photos');
                $media = $rlDb->fetch('*', array('Listing_ID' => $listing_id), "ORDER BY `Position`");

                $photos = array();
                foreach ($media as $photo) {
                    if ($photo['Type'] == 'picture') {
                        $photo['Photo'] = RL_FILES_URL . $photo['Photo'];
                        $photo['uri'] = RL_FILES_URL . $photo['Thumbnail'];
                        $photos[] = $photo;
                    }
                }
                $response['pictures'] = $photos;

                /* ADD VIDEO DATA */
                $videos = array();
                foreach ($media as $video) {
                    if ($video['Type'] == 'video') {
                        if ($video['Original'] == 'youtube') {
                            $video['Preview'] = $video['Photo'];
                            $video['uri'] = str_replace('{id}', $video['Photo'], $this->youtube_preview_url);
                        }
                        $video['title'] = $video['Video'];
                        $videos[] = $video;
                    }
                }

                $response['videos'] = $videos;

                unset($listing, $category, $plan, $photos, $videos);
            } else {
                $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), access denied for {$account_id} (id) account");

                $response['error'] = 'account_access_denied';
            }
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';

            if ($this->issetAccount($account_id, $password_hash)) {
                $response .= '<listing>';

                // get listing info
                $sql = "SELECT `T1`.*, `T2`.`Cross` AS `Plan_crossed`, `T2`.`Key` AS `Plan_key`, `T3`.`Type` AS `Listing_type` ";
                $sql .= "FROM `{db_prefix}listings` AS `T1` ";
                $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
                $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
                $sql .= "WHERE `T1`.`ID` = {$listing_id}";

                $listing = $rlDb->getRow($sql);

                /* change category */
                $listing['Category_ID'] = $category_id ? $category_id : $listing['Category_ID'];

                /* ADD LISTING DATA */
                $response .= '<data>';
                $response .= $this->printValue($listing);
                $response .= '</data>';

                /* ADD CATEGORIES DATA */
                // get category info
                $category = $rlDb->fetch(array('Level', 'Parent_ID', 'Parent_IDs'), array('ID' => $listing['Category_ID']), null, 1, 'categories', 'row');

                // get category parents
                $category_parents[] = 0;
                if ($category['Parent_IDs']) {
                    $category_parents = array_merge($category_parents, explode(',', $category['Parent_IDs']));
                }

                // add parent categories data
                foreach (array_reverse($category_parents) as $parent) {
                    $response .= '<category id="' . $parent . '">';
                    $response .= $this->printValue($this->getCatTree($listing_type, $parent), 'subcategory');
                    $response .= '</category>';
                }

                /* ADD FORM DATA */
                $response .= '<form>';
                $response .= $this->printValue($this->getFormFields($listing['Category_ID'], $listing_type, $listing));
                $response .= '</form>';

                /* ADD PLAN DATA */
                $plan = $rlDb->fetch(array('ID', 'Key', 'Type', 'Featured', 'Price', 'Image', 'Image_unlim', 'Video', 'Video_unlim'), array('ID' => $listing['Plan_ID']), null, 1, 'listing_plans', 'row');
                $response .= '<plan>';
                $response .= $this->printValue($plan);
                $response .= '</plan>';

                /* ADD PICTURES DATA */
                $rlDb->setTable('listing_photos');
                $media = $rlDb->fetch('*', array('Listing_ID' => $listing_id), "ORDER BY `Position`");

                $photos = array();
                foreach ($media as $photo) {
                    if ($photo['Type'] == 'picture') {
                        $photo['Photo'] = RL_FILES_URL . $photo['Photo'];
                        $photo['uri'] = RL_FILES_URL . $photo['Thumbnail'];
                        $photos[] = $photo;
                    }
                }

                $response .= '<pictures>';
                $response .= $this->printValue($photos);
                $response .= '</pictures>';

                /* ADD VIDEO DATA */
                $videos = array();
                foreach ($media as $video) {
                    if ($video['Type'] == 'video') {
                        if ($video['Original'] == 'youtube') {
                            $video['Preview'] = $video['Photo'];
                            $video['uri'] = str_replace('{id}', $video['Photo'], $this->youtube_preview_url);
                        }
                        $video['title'] = $video['Video'];
                        $videos[] = $video;
                    }
                }

                $response .= '<videos>';
                $response .= $this->printValue($videos);
                $response .= '</videos>';

                $response .= '</listing>';

                unset($listing, $category, $plan, $photos, $videos);
            } else {
                $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), access denied for {$account_id} (id) account");

                $response .= '<error>account_access_denied</error>';

            }
        }

        return $response;
    }

    /**
     * Delete listing and related data
     *
     * @param int    $listing_id    - requested listing id
     * @param int    $account_id    - requested account id
     * @param string $password_hash - requested account password hash
     *
     * @return string - custom xml
     **/
    public function removeListing($listing_id, $account_id, $password_hash)
    {
        global $config, $rlValid, $reefless;

        if (!$account_id || !$password_hash || !$listing_id) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no listing_id, username or passwordHash received, abort");
            return false;
        }

        if (!$this->issetAccount($account_id, $password_hash)) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), wrong login details provided, abort");
            return false;
        }

        $sql = "SELECT `T1`.`ID`, `T1`.`Category_ID`, `T1`.`Account_ID`, `T2`.`Type`, `T1`.`Crossed`, `T1`.`Status`, `T2`.`Type` AS `Listing_type` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = {$listing_id} AND `T1`.`Account_ID` = '{$account_id}' AND `T1`.`Status` <> 'trash'";
        $listing = $GLOBALS['rlDb']->getRow($sql);

        if ($listing) {
            $reefless->loadClass('Listings');
            $reefless->loadClass('Categories');

            try {
                $GLOBALS['rlHook']->load('phpListingsAjaxDeleteListing', $listing);

                if ($config['trash']) {
                    $GLOBALS['rlDb']->delete(array('ID' => $listing['ID']), 'listings', null, 1);

                    /* decrease category listing */
                    if ($listing['Category_ID'] && $GLOBALS['rlListings']->isActive($listing['ID'])) {

                        $GLOBALS['rlCategories']->listingsDecrease($listing['Category_ID'], $listing['Listing_type']);
                        $GLOBALS['rlCategories']->accountListingsDecrease($listing['Account_ID']);

                        /* crossed listings count control */
                        if ($listing['Crossed']) {
                            $crossed = explode(',', $listing['Crossed']);
                            foreach ($crossed as $crossed_id) {
                                $GLOBALS['rlCategories']->listingsDecrease($crossed_id);
                            }
                        }
                    }
                } else {
                    $GLOBALS['rlListings']->deleteListingData($listing['ID'], $listing['Category_ID'], $listing['Crossed'], $listing['Listing_type']);
                    $GLOBALS['rlDb']->delete(array('ID' => $listing['ID']), 'listings', null, 1);
                }

                $out['success'] = true;
            } catch (Exception $e) {
                $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), the exception is the following: " . $e->getMessage());
                $out['error'] = 'remove_listing_fail';
            }
        } else {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), access denied for {$account_id} (id) account");
            $out['error'] = 'account_access_denied';
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response = $out;
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';
            if ($out['error']) {
                $response .= '<error>' . $out['error'] . '</error>';
            } else {
                $response .= '<success>' . $out['success'] . '</success>';
            }
        }

        return $response;
    }

    /**
     * confirm app transaction Yookassa
     *
     * @since 4.2.0
     *
     * @param int    $account_id    - requested account id
     * @param string $password_hash - requested account password hash
     *
     * @return array - return data
     **/
    public function confirmYookassaPayment($account_id, $password_hash)
    {
        global $config, $reefless, $rlValid, $rlHook, $rlDb;

        if (!$account_id || !$password_hash) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no username or passwordHash received, abort");
            return false;
        }

        if (!$this->issetAccount($account_id, $password_hash)) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), wrong login details provided, abort");
            return false;
        }

        if (!$GLOBALS['plugins']['yandexKassa']) {
            return;
        }

        $GLOBALS['response_type'] = "json";

        if (!is_object('rlGateway')) {
            require_once RL_CLASSES . 'rlGateway.class.php';
        }
        $reefless->loadClass('YooKassaGateway', null, 'androidConnect');
        $GLOBALS['rlYooKassaGateway']->init();
        $item_info = $GLOBALS['rlYooKassaGateway']->confirmYookassaPayment();

        if ($item_info) {
            if ($item_info['method'] == 'upgradeListing') {
                $out['success'] = $this->buildReturnListing($item_info['id']);
            } else {
                $out['success'] = $this->getPackage($account_id, $item_info['id']);
            }

        } else {
            $out['error'] = true;
        }

        return $out;
    }

    /**
     * Validate app transaction Yookassa
     *
     * @since 4.2.0
     *
     * @param int    $account_id    - requested account id
     * @param string $password_hash - requested account password hash
     *
     * @return array - return data
     **/
    public function validateYookassaTransaction($account_id, $password_hash)
    {
        global $reefless;

        if (!$account_id || !$password_hash) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no username or passwordHash received, abort");
            return false;
        }

        if (!$this->issetAccount($account_id, $password_hash)) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), wrong login details provided, abort");
            return false;
        }

        if (!$GLOBALS['plugins']['yandexKassa']) {
            return;
        }

        if (!is_object('rlGateway')) {
            require_once RL_CLASSES . 'rlGateway.class.php';
        }

        $GLOBALS['response_type'] = "json";
        $reefless->loadClass('YooKassaGateway', null, 'androidConnect');
        $GLOBALS['rlYooKassaGateway']->init();
        $out = $GLOBALS['rlYooKassaGateway']->validateYookassaTransaction();

        if ($out['status'] == 'complete') {
            // Simulate request data
            $_REQUEST['id'] = $out['success']['id'];
            $_REQUEST['txn_id'] = $out['success']['txn_id'] ;

            $out = $this->confirmYookassaPayment($account_id, $password_hash);
            $out['success']['payment_status'] = 'complete';
        }

        return $out;
    }

    /**
     * Validate app transaction
     *
     * @param int    $account_id    - requested account id
     * @param string $password_hash - requested account password hash
     * @param array  $payment       - payment details
     *
     * @return string - custom xml
     **/
    public function validateTransaction($account_id, $password_hash, $payment)
    {
        global $config, $reefless, $rlValid, $rlHook, $rlDb;

        if (!$account_id || !$password_hash) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no username or passwordHash received, abort");
            return false;
        }

        if (!$this->issetAccount($account_id, $password_hash)) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), wrong login details provided, abort");
            return false;
        }

        if (!$payment['item'] || !$payment['plan'] || !$payment['id'] || !$payment['amount']) {
            ob_start();
            print_r($payment);
            $log = ob_get_clean();
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no enough payment details for payment: {$log}");
            return false;
        }

        if (!extension_loaded('curl')) {
            return;
        }

        switch ($payment['gateway']) {
            case 'paypal_rest':
            case 'paypal':
                require_once RL_PLUGIN_ANDROID . 'paypalREST.gateway.php';
                $gateway = new paypalREST($payment, $account_id);

                break;

            case 'google':
                require_once RL_PLUGIN_ANDROID . 'google.gateway.php';
                $gateway = new google($payment, $account_id);

                break;

            default:
                $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), unknown payment geteway request: {$payment['gateway']}");
                return false;

                break;
        }

        if ($gateway->approved) {
            switch ($payment['service']) {
                case 'listings':
                case 'listing':
                    $reefless->loadClass('Listings');

                    $reefless->loadClass('Payment');
                    $GLOBALS['rlPayment']->clear();
                    $payment_info = $gateway->payment_info;

                    $GLOBALS['rlPayment']->setOption('service', $payment_info['item']);
                    $GLOBALS['rlPayment']->setOption('total', $payment_info['amount']);
                    $GLOBALS['rlPayment']->setOption('plan_id', $payment_info['plan']);
                    $GLOBALS['rlPayment']->setOption('item_id', $payment_info['id']);
                    $GLOBALS['rlPayment']->setOption('item_name', $payment_info['title']);
                    $GLOBALS['rlPayment']->setOption('plan_key', 'listing_plans+name+' . $rlDb->getOne('Key', "`ID` = '{$payment_info['plan']}'", 'listing_plans'));
                    $GLOBALS['rlPayment']->setOption('account_id', $gateway->account_id);
                    $GLOBALS['rlPayment']->setGateway($payment['gateway']);
                    $GLOBALS['rlPayment']->createTransaction();

                    $data = array(
                        'service' => 'listing',
                        'plan_id' => $payment_info['plan'],
                        'item_id' => $payment_info['id'],
                        'params' => $payment_info['featured'],
                        'account_id' => $gateway->account_id,
                        'total' => $payment_info['amount'],
                        'txn_id' => $GLOBALS['rlPayment']->getTransactionID(),
                        'txn_gateway' => $gateway->results->transaction_id,
                    );
                    $GLOBALS['rlPayment']->complete($data, 'rlListings', 'upgradeListing', $payment_info['featured']);

                    $out['success'] = $this->buildReturnListing($payment['id']);

                    break;

                case 'upgradePackage':
                case 'purchasePackage':
                    $reefless->loadClass('Listings');
                    $reefless->loadClass('Payment');
                    $GLOBALS['rlPayment']->clear();
                    $payment_info = $gateway->payment_info;

                    // set payment options
                    $GLOBALS['rlPayment']->setOption('service', $payment_info['item']);
                    $GLOBALS['rlPayment']->setOption('total', $payment_info['amount']);
                    $GLOBALS['rlPayment']->setOption('plan_id', $payment_info['plan']);
                    $GLOBALS['rlPayment']->setOption('item_id', $payment_info['id']);
                    $GLOBALS['rlPayment']->setOption('item_name', $payment_info['title']);
                    $GLOBALS['rlPayment']->setOption('plan_key', 'listing_plans+name+' . $rlDb->getOne('Key', "`ID` = '{$payment_info['plan']}'", 'listing_plans'));
                    $GLOBALS['rlPayment']->setOption('account_id', $gateway->account_id);
                    $GLOBALS['rlPayment']->setGateway($payment['gateway']);
                    $GLOBALS['rlPayment']->createTransaction();

                    $data = array(
                        'service' => $payment_info['item'],
                        'plan_id' => $payment_info['plan'],
                        'item_id' => $payment_info['id'],
                        'account_id' => $gateway->account_id,
                        'total' => $payment_info['amount'],
                        'txn_id' => $GLOBALS['rlPayment']->getTransactionID(),
                        'txn_gateway' => $gateway->results->transaction_id,
                    );
                    if ($payment['service'] == "purchasePackage") {
                        $GLOBALS['rlPayment']->complete($data, 'rlListings', 'purchasePackage', false);
                    } elseif ($payment['service'] == "upgradePackage") {
                        $GLOBALS['rlPayment']->complete($data, 'rlListings', 'upgradePackage', false);
                    }

                    if ($GLOBALS['json_support']) {
                        $out['success'] = $this->getPackage($account_id, $payment['plan']);
                    } else {
                        $out['success'] = $this->printValue($this->getPackage($account_id, $payment['plan']));
                    }

                    break;

                default:
                    $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), unknown payment item request: {$payment['item']}");
                    $out['error'] = '';

                    break;
            }
        } else {
            $out['error'] = '';
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response = $out;
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';

            if ($out['error']) {
                $response .= '<error><![CDATA[' . $out['error'] . ']]></error>';
            } else {
                $response .= '<success><![CDATA[' . $out['success'] . ']]></success>';
            }
        }

        return $response;
    }

    /**
     * get plans
     *
     * @param int    $account_id    - requested account id
     * @param string $password_hash - requested account password hash
     * @param int    $category_id   - category
     * @param int    $account_type  - account type
     * @param int    $featured_only - featured
     *
     * @return string - custom xml
     **/
    public function getPlans($account_id, $password_hash, $category_id, $account_type, $featured_only)
    {
        global $account_info, $config;

        if (!$account_id || !$password_hash) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no username or passwordHash received, abort");
            return false;
        }

        if (!$this->issetAccount($account_id, $password_hash)) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), wrong login details provided, abort");
            return false;
        }

        if (!$category_id || !$account_type) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no category_id or account_type received");
            return false;
        }

        $account_info['ID'] = $account_id;

        $GLOBALS['reefless']->loadClass('Plan');
        $plans = $GLOBALS['rlPlan']->getPlanByCategory($category_id, $account_type, $featured_only);
        $remove_paid_plans = true;

        // remove paid plans if there are not payment gateways available
        if (($config['android_inapp_module'] && $config['android_inapp_key']) // no iap
             || ($config['android_paypal_module'] && $config['android_paypal_client_id'] && $config['android_paypal_secret']) // no paypal REST
             || ($config['android_yookassa_module'] && $config['android_yookassa_key'] && $config['android_yookassa_store_id']) // no yookassa
        ) {
            $remove_paid_plans = false; //ok
        }

        foreach ($plans as $key => &$plan) {
            if (count($plans) == 1 && $plan['Using'] == '0') {
                unset($plans[$key]);
                continue;
            }
            if ($remove_paid_plans && $plan['Price'] > 0 && !($plan['Package_ID'] && $plan['Listings_remains'] > 0)) {
                unset($plans[$key]);
                continue;
            }

            $this->setValidInt($plan['Listings_remains']);
            $this->setValidInt($plan['Standard_remains']);
            $this->setValidInt($plan['Featured_remains']);
        }

        return $plans;
    }

    /**
     * Upgrade free or existing package plan
     *
     * @param int    $account_id    - requested account id
     * @param string $password_hash - requested account password hash
     * @param int    $listing_id    - requested listing id
     * @param int    $plan_id       - requested plan id
     * @param string $listing_mode  - listing appearance: standard or featured
     *
     * @return string - custom xml
     **/
    public function upgradePlan($account_id, $password_hash, $listing_id, $plan_id, $listing_mode = "standard")
    {
        global $reefless, $rlDb, $rlHook, $config, $lang;

        $reefless->loadClass('Plan');
        $reefless->loadClass('Mail');
        $reefless->loadClass('Common');
        $reefless->loadClass('Listings');
        $reefless->loadClass('Categories');
        $reefless->loadClass('Account');

        if (!$account_id || !$password_hash) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no username or passwordHash received, abort");
            return false;
        }

        if (!$this->issetAccount($account_id, $password_hash)) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), wrong login details provided, abort");
            return false;
        }

        if (!$listing_id || !$plan_id) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no listing_id or plan_id received");
            return false;
        }

        // get listing details
        $sql = "SELECT `T1`.*, `T1`.`Category_ID`, `T1`.`Status`, UNIX_TIMESTAMP(`T1`.`Pay_date`) AS `Pay_date`, `T1`.`Crossed`, ";
        $sql .= "`T2`.`Type` AS `Listing_type`, `T2`.`Path` AS `Category_path`, `T1`.`Last_type` AS `Listing_mode` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = {$listing_id} AND `T1`.`Account_ID` = '{$account_id}' ";

        $rlHook->load('upgradeListingSql', $sql);

        $listing = $rlDb->getRow($sql);

        if (!$listing) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no listing found with id = {$listing_id}, account_id = {$account_id}");
            return false;
        }

        // get plan info
        $plan_info = $GLOBALS['rlPlan']->getPlan($plan_id, $account_id);

        if (!$plan_info) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no plan found with id = {$plan_id}");
            return false;
        }

        // check limited plans using
        if ($plan_info['Using'] <= 0 && $plan_info['Limit'] > 0) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), the limited plan #{$plan_info['ID']} isn't avaiable for account # {$account_id}");
            return false;
        }

        // check rest listings using
        if ($plan_info['Package_ID'] && $listing_mode && ($plan_info[ucfirst($listing_mode) . '_remains'] <= 0 && $plan_info[ucfirst($listing_mode) . '_listings'] > 0)) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), the package plan #{$plan_info['ID']} isn't avaiable for account # {$account_id}");
            return false;
        }

        $account_info = $GLOBALS['rlAccount']->getProfile((int) $account_id);

        $listing_title = $GLOBALS['rlListings']->getListingTitle($listing['Category_ID'], $listing, $listing['Listing_type']);

        // Upgrade to featured MODE
        if ($plan_info['Type'] == 'featured') {
            $update = array(
                'fields' => array(
                    'Featured_ID' => $plan_info['ID'],
                    'Featured_date' => 'NOW()',
                ),
                'where' => array(
                    'ID' => $listing_id,
                ),
            );

            if ($GLOBALS['rlDb']->updateOne($update, 'listings')) {
                /* limited option handler */
                if ($plan_info['Limit'] > 0) {
                    if ($plan_info['Using'] == '') {
                        $plan_using_insert = array(
                            'Account_ID' => $account_info['ID'],
                            'Plan_ID' => $plan_info['ID'],
                            'Listings_remains' => $plan_info['Limit'] - 1,
                            'Type' => 'limited',
                            'Date' => 'NOW()',
                            'IP' => Util::getClientIP(),
                        );
                        $GLOBALS['rlDb']->insertOne($plan_using_insert, 'listing_packages');
                    } else {
                        $plan_using_update = array(
                            'fields' => array(
                                'Account_ID' => $account_info['ID'],
                                'Plan_ID' => $plan_info['ID'],
                                'Listings_remains' => $plan_info['Using'] - 1,
                                'Type' => 'limited',
                                'Date' => 'NOW()',
                                'IP' => Util::getClientIP(),
                            ),
                            'where' => array(
                                'ID' => $plan_info['Plan_using_ID'],
                            ),
                        );
                        $GLOBALS['rlDb']->updateOne($plan_using_update, 'listing_packages');
                    }
                }

                /* send notification to listing owner */
                $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('listing_upgraded_to_featured');

                $lt_page_path = $rlDb->getOne('Path', "`Key` = 'lt_{$listing['Listing_type']}'", 'pages');
                $my_page_path = $rlDb->getOne('Path', "`Key` = 'my_{$listing['Listing_type']}'", 'pages');

                $link = SEO_BASE;
                $link .= $config['mod_rewrite']
                ? $lt_page_path . '/' . $listing['Category_path'] . '/' . $GLOBALS['rlValid']->str2path($listing_title) . '-' . $listing_id . '.html'
                : '?page=' . $lt_page_path . '&amp;id=' . $listing_id;

                $find = array('{name}', '{listing}', '{plan_name}', '{plan_price}', '{start_date}', '{expiration_date}');
                $replace = array(
                    $account_info['Full_name'],
                    '<a href="' . $link . '">' . $listing_title . '</a>',
                    $plan_info['name'],
                    $lang['free'],
                    date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT)),
                    date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT), strtotime('+' . $plan_info['Listing_period'] . ' days')),
                );

                $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
                $mail_tpl['body'] = preg_replace('/\{if.*\{\/if\}(<br\s+\/>)?/', '', $mail_tpl['body']);

                $GLOBALS['rlMail']->send($mail_tpl, $account_info['Mail']);

                /* send notification to administrator */
                $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('listing_upgraded_to_featured_for_admin');

                $link = RL_URL_HOME . ADMIN . '/index.php?controller=listings&amp;action=view&amp;id=' . $listing_id;

                $find = array('{listing}', '{plan_name}', '{listing_id}', '{owner}', '{start_date}', '{expiration_date}');
                $replace = array(
                    '<a href="' . $link . '">' . $listing_title . '</a>',
                    $plan_info['name'],
                    $listing_id,
                    $account_info['Full_name'],
                    date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT)),
                    date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT), strtotime('+' . $plan_info['Listing_period'] . ' days')),
                );

                $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
                $GLOBALS['rlMail']->send($mail_tpl, $config['notifications_email']);
            }
        } else {
            $update_featured_id = ($plan_info['Featured'] && !$plan_info['Advanced_mode']) || $listing_mode == 'featured' ? $plan_info['ID'] : '';
            $update_featured_date = ($plan_info['Featured'] && !$plan_info['Advanced_mode']) || $listing_mode == 'featured' ? 'IF(UNIX_TIMESTAMP(NOW()) > UNIX_TIMESTAMP(DATE_ADD(`Featured_date`, INTERVAL ' . $plan_info['Listing_period'] . ' DAY)) OR IFNULL(UNIX_TIMESTAMP(`Featured_date`), 0) = 0, NOW(), DATE_ADD(`Featured_date`, INTERVAL ' . $plan_info['Listing_period'] . ' DAY))' : '';
            $update_date = 'IF(UNIX_TIMESTAMP(NOW()) > UNIX_TIMESTAMP(DATE_ADD(`Pay_date`, INTERVAL ' . $plan_info['Listing_period'] . ' DAY)) OR IFNULL(UNIX_TIMESTAMP(`Pay_date`), 0) = 0, NOW(), DATE_ADD(`Pay_date`, INTERVAL ' . $plan_info['Listing_period'] . ' DAY))';

            $update = array(
                'fields' => array(
                    'Plan_ID' => $plan_info['ID'],
                    'Pay_date' => $update_date,
                    'Featured_ID' => $update_featured_id,
                    'Featured_date' => $update_featured_date,
                    'Last_type' => $listing_mode,
                    'Plan_type' => 'listing',
                ),
                'where' => array(
                    'ID' => $listing_id,
                ),
            );

            /* update listing posting date */
            if ($config['posting_date_update']) {
                $update['fields']['Date'] = 'NOW()';
            }

            if ($listing['Status'] == 'expired') {
                $update['fields']['Status'] = 'active';
            }

            if ($GLOBALS['rlDb']->updateOne($update, 'listings')) {
                // available package mode
                if ($plan_info['Type'] == 'package' && $plan_info['Package_ID']) {
                    if ($plan_info['Listings_remains'] != 0 || $plan_info['Standard_listings'] == 0 && $listing_mode == 'standard' || $plan_info['Featured_listings'] == 0 && $listing_mode == 'featured') {
                        $update_entry = array(
                            'fields' => array(
                                'Listings_remains' => $plan_info[ucfirst($listing_mode) . '_listings'] == 0 ? $plan_info['Listings_remains'] : $plan_info['Listings_remains'] - 1,
                            ),
                            'where' => array(
                                'ID' => $plan_info['Package_ID'],
                            ),
                        );

                        if ($plan_info[ucfirst($listing_mode) . '_listings'] != 0) {
                            $update_entry['fields'][ucfirst($listing_mode) . '_remains'] = $plan_info[ucfirst($listing_mode) . '_remains'] - 1;
                        } elseif (!$plan_info['Advanced_mode']) {
                            $update_entry['fields']['Listings_remains'] = $plan_info['Listing_number'] - 1;
                        }
                        $GLOBALS['rlDb']->updateOne($update_entry, 'listing_packages');
                    }
                }
                // free package mode
                elseif ($plan_info['Type'] == 'package' && !$plan_info['Package_ID'] && $plan_info['Price'] <= 0) {
                    $insert_entry = array(
                        'Account_ID' => $account_info['ID'],
                        'Plan_ID' => $plan_info['ID'],
                        'Listings_remains' => $plan_info[ucfirst($listing_mode) . '_listings'] == 0 ? $plan_info['Listing_number'] : $plan_info['Listing_number'] - 1,
                        'Type' => 'package',
                        'Date' => 'NOW()',
                        'IP' => Util::getClientIP(),
                    );

                    if ($plan_info['Featured'] && $plan_info['Advanced_mode'] && $plan_info['Standard_listings']) {
                        $insert_entry['Standard_remains'] = $plan_info['Standard_listings'];
                    }
                    if ($plan_info['Featured'] && $plan_info['Advanced_mode'] && $plan_info['Featured_listings']) {
                        $insert_entry['Featured_remains'] = $plan_info['Featured_listings'];
                    }

                    if ($plan_info[ucfirst($listing_mode) . '_listings'] != 0) {
                        $insert_entry[ucfirst($listing_mode) . '_remains'] = $plan_info[ucfirst($listing_mode) . '_listings'] - 1;
                    } elseif (!$plan_info['Advanced_mode']) {
                        $insert_entry['Listings_remains'] = $plan_info['Listing_number'] - 1;
                    }

                    $GLOBALS['rlDb']->insertOne($insert_entry, 'listing_packages');
                }
                // limited listing mode
                elseif ($plan_info['Type'] == 'listing' && $plan_info['Limit'] > 0) {
                    /* update/insert limited plan using entry */
                    if (empty($plan_info['Using'])) {
                        $plan_using_insert = array(
                            'Account_ID' => $account_info['ID'],
                            'Plan_ID' => $plan_info['ID'],
                            'Listings_remains' => $plan_info['Limit'] - 1,
                            'Type' => 'limited',
                            'Date' => 'NOW()',
                            'IP' => Util::getClientIP(),
                        );

                        $GLOBALS['rlDb']->insertOne($plan_using_insert, 'listing_packages');
                    } else {
                        $plan_using_update = array(
                            'fields' => array(
                                'Account_ID' => $account_info['ID'],
                                'Plan_ID' => $plan_info['ID'],
                                'Listings_remains' => $plan_info['Using'] - 1,
                                'Type' => 'limited',
                                'Date' => 'NOW()',
                                'IP' => Util::getClientIP(),
                            ),
                            'where' => array(
                                'ID' => $plan_info['Plan_using_ID'],
                            ),
                        );

                        $GLOBALS['rlDb']->updateOne($plan_using_update, 'listing_packages');
                    }
                }

                /* update listing images count if plan allows less photos then previous plan */
                if (!$plan_info['Image_unlim'] && $plan_info['Image'] && $plan_info['Image'] < $listing['Photos_count'] && $plan_info['Type'] != 'featured') {
                    $photos_count_update = array(
                        'fields' => array(
                            'Photos_count' => $plan_info['Image'],
                        ),
                        'where' => array(
                            'ID' => $listing['ID'],
                        ),
                    );

                    $GLOBALS['rlDb']->updateOne($photos_count_update, 'listings');
                }

                /* recount category listings count */
                if ($config['listing_auto_approval'] && $listing != "active") {
                    $GLOBALS['rlCategories']->listingsIncrease($listing['Category_ID']);
                    if (method_exists($GLOBALS['rlCategories'], 'accountListingsIncrease')) {
                        $GLOBALS['rlCategories']->accountListingsIncrease($account_id);
                    }
                }

                /* send message to listing owner */
                $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate(
                    ($config['listing_auto_approval'] || $listing['Status'] == 'active')
                    ? 'listing_upgraded_active'
                    : 'listing_upgraded_approval'
                );

                $lt_page_path = $rlDb->getOne('Path', "`Key` = 'lt_{$listing['Listing_type']}'", 'pages');
                $my_page_path = $rlDb->getOne('Path', "`Key` = 'my_{$listing['Listing_type']}'", 'pages');

                $link = SEO_BASE;
                if ($config['listing_auto_approval']) {
                    $link .= $config['mod_rewrite']
                    ? $lt_page_path . '/' . $listing['Category_path'] . '/' . $GLOBALS['rlValid']->str2path($listing_title) . '-' . $listing_id . '.html'
                    : '?page=' . $lt_page_path . '&amp;id=' . $listing_id;
                } else {
                    $link .= $config['mod_rewrite'] ? $my_page_path . '.html' : '?page=' . $my_page_path;
                }

                $mail_tpl['body'] = str_replace(
                    array('{name}', '{link}', '{plan}'),
                    array(
                        $account_info['Full_name'],
                        '<a href="' . $link . '">' . $link . '</a>', $plan_info['name'],
                    ),
                    $mail_tpl['body']
                );
                $GLOBALS['rlMail']->send($mail_tpl, $account_info['Mail']);
            }
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response['success'] = $this->buildReturnListing($listing_id);
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';

            // send success
            $response = '<success>';
            $response .= $this->buildReturnListing($listing_id);
            $response .= '</success>';
        }

        return $response;
    }

    /**
     * Description
     *
     * @param int    $account_id    - Account ID
     * @param string $password_hash - Password
     * @param string $account_type  - Account type
     *
     * @return array
     */
    public function getMyPackages($account_id = 0, $password_hash = null, $account_type = null)
    {
        global $account_info, $lang, $rlDb;

        if (!$account_id || !$password_hash) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no username or passwordHash received, abort");
            return false;
        }

        $sql = "SELECT `T1`.`Listings_remains`, `T1`.`Standard_remains`, `T1`.`Featured_remains`, `T1`.`Date`, `T1`.`IP`, `T1`.`Plan_ID`, ";
        $sql .= "`T2`.*, `T1`.`ID`  , ";
        $sql .= "IF (`T2`.`Plan_period` = 0, 'unlimited', UNIX_TIMESTAMP(DATE_ADD(`T1`.`Date`, INTERVAL `T2`.`Plan_period` DAY))) AS `Exp_date`, ";
        $sql .= "IF (`T2`.`Plan_period` > 0 AND UNIX_TIMESTAMP(DATE_ADD(`T1`.`Date`, INTERVAL `T2`.`Plan_period` DAY)) < UNIX_TIMESTAMP(NOW()), 'expired', 'active') AS `Exp_status`";
        $sql .= ", `T3`.`Status` AS `Subscription`, `T3`.`ID` AS `Subscription_ID`, `T3`.`Service` AS `Subscription_service` ";

        $sql .= "FROM `{db_prefix}listing_packages` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}subscriptions` AS `T3` ON `T1`.`Plan_ID` = `T3`.`Plan_ID` AND `T3`.`Service` = 'listing' AND `T3`.`Status` = 'active' ";
        $sql .= "WHERE `T1`.`Account_ID` = '{$account_id}' AND `T1`.`Type` = 'package' ";

        $GLOBALS['rlHook']->load('myPackagesSql', $sql);

        $sql .= "ORDER BY `T1`.`ID` DESC";

        $packages = $rlDb->getAll($sql);
        $packages = $GLOBALS['rlLang']->replaceLangKeys($packages, 'listing_plans', array('name', 'des'));

        $sql = "SELECT count(*) as `count` FROM `{db_prefix}listing_plans` ";
        $sql .= "WHERE `Status` = 'active' AND `Type` =  'package' AND (FIND_IN_SET('{$account_type}', `Allow_for`) > 0 OR `Allow_for` = '') ";
        if ($packages) {
            $packages_ids = "";
            foreach ($packages as $key => $value) {
                $packages_id .= $packages_id ? "," . $value['Plan_ID'] : $value['Plan_ID'];
            }
            $sql .= "AND FIND_IN_SET(`ID`, '" . $packages_id . "') = 0 ";
        }
        $available_packages = $rlDb->getRow($sql);

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response['plans'] = $packages;
            $response['available_plan'] = $available_packages['count'] > 0 ? 1 : 0;
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';
            $response .= '<items>';
            $response .= $this->printValue($packages);

            $response .= '<item><available_plan>';
            $response .= $available_packages['count'] > 0 ? 1 : 0;
            $response .= '</available_plan></item>';
            $response .= '</items>';
        }

        return $response;
    }

    /**
     * Description
     *
     * @param int    $account_id    - Account ID
     * @param string $password_hash - Password
     * @param string $account_type  - Account type
     *
     * @return array
     */
    public function getPackages($account_id = 0, $password_hash = null, $account_type = null)
    {
        global $account_info, $lang, $rlDb;

        if (!$account_id || !$password_hash) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no username or passwordHash received, abort");
            return false;
        }
        $account_info['Type'] = $account_type;

        /* get available plans */
        $GLOBALS['reefless']->loadClass('Plan');
        $used_ids_tmp = $rlDb->fetch(array('Plan_ID'), array('Account_ID' => $account_id), null, null, 'listing_packages');
        foreach ($used_ids_tmp as $key => $val) {
            $used_ids .= $used_ids ? "," . $val['Plan_ID'] : $val['Plan_ID'];
        }
        unset($used_ids_tmp);

        $sql = "SELECT DISTINCT `T1`.*, `T2`.`Status` AS `Subscription`, `T2`.`Period` ";
        $sql .= "FROM `{db_prefix}listing_plans` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}subscription_plans` AS `T2` ON `T1`.`ID` = `T2`.`Plan_ID` AND `T2`.`Service` = 'listing' AND `T2`.`Status` = 'active' ";
        $sql .= "WHERE `T1`.`Status` = 'active' AND `T1`.`Type` =  'package' ";
        if ($used_ids) {
            $sql .= "AND FIND_IN_SET(`T1`.`ID`, '" . $used_ids . "') = 0 ";
        }
        $sql .= "AND (FIND_IN_SET('{$account_info['Type']}', `Allow_for`) > 0 OR `Allow_for` = '') ORDER BY `Position`";
        $available_packages = $rlDb->getAll($sql);
        $available_packages = $GLOBALS['rlLang']->replaceLangKeys($available_packages, 'listing_plans', array('name', 'des'));

        if ($available_packages) {
            foreach ($available_packages as $key => $value) {

                if ($value['Period']) {
                    $value['Period'] = $lang['subscription_period_' . $value['Period']];
                }
                $available_packages_tmp[$value['ID']] = $value;
            }
            $available_packages = $available_packages_tmp;
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response['plans'] = $available_packages;
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';

            $response .= '<items>';
            if ($available_packages) {
                $response .= $this->printValue($available_packages);
            }
            $response .= '</items>';
        }

        return $response;
    }

    /**
     * Description
     *
     * @param int $account_id - Account ID
     * @param int $plan_id    - $plan_id
     *
     * @return array
     */
    public function getPackage($account_id = 0, $plan_id = 0)
    {
        global $lang;

        $sql = "SELECT `T1`.`Listings_remains`, `T1`.`Standard_remains`, `T1`.`Featured_remains`, `T1`.`Date`, `T1`.`IP`, `T1`.`Plan_ID`, ";
        $sql .= "`T2`.*, `T1`.`ID`  , ";
        $sql .= "IF (`T2`.`Plan_period` = 0, 'unlimited', UNIX_TIMESTAMP(DATE_ADD(`T1`.`Date`, INTERVAL `T2`.`Plan_period` DAY))) AS `Exp_date`, ";
        $sql .= "IF (`T2`.`Plan_period` > 0 AND UNIX_TIMESTAMP(DATE_ADD(`T1`.`Date`, INTERVAL `T2`.`Plan_period` DAY)) < UNIX_TIMESTAMP(NOW()), 'expired', 'active') AS `Exp_status`";
        $sql .= ", `T3`.`Status` AS `Subscription`, `T3`.`ID` AS `Subscription_ID`, `T3`.`Service` AS `Subscription_service` ";

        $sql .= "FROM `{db_prefix}listing_packages` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}subscriptions` AS `T3` ON `T1`.`Plan_ID` = `T3`.`Plan_ID` AND `T3`.`Service` = 'listing' AND `T3`.`Status` = 'active' ";

        $sql .= "WHERE `T1`.`Account_ID` = '{$account_id}' AND `T1`.`Plan_ID` = '{$plan_id}'";

        $sql .= "ORDER BY `T1`.`ID` DESC";

        $package = $GLOBALS['rlDb']->getRow($sql);
        $package = $GLOBALS['rlLang']->replaceLangKeys($package, 'listing_plans', array('name', 'des'));

        return $package;
    }

    /**
     * Description
     *
     * @param int    $account_id    - Account ID
     * @param string $password_hash - Password
     * @param int    $package_id    - package id
     * @param int    $plan_id       - plan id
     * @param string $service       - service type
     *
     * @return array
     */
    public function upgradePackages($account_id, $password_hash, $package_id, $plan_id, $service)
    {

        if (!$account_id || !$password_hash) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no username or passwordHash received, abort");
            return false;
        }

        /* get available plans */
        $GLOBALS['reefless']->loadClass('Listings');
        if ($service == 'purchase') {
            $GLOBALS['rlListings']->purchasePackage($plan_id, $plan_id, $account_id, true);
        } else {
            $GLOBALS['rlListings']->upgradePackage($package_id, $plan_id, $account_id);
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response['success'] = $this->getPackage($account_id, $plan_id);
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';

            $response = '<success>';
            $response .= $this->printValue($this->getPackage($account_id, $plan_id));
            $response .= '</success>';
        }

        return $response;
    }

    /**
     * Description
     * @param type $account_id    - Account ID
     * @param type $password_hash - Password
     * @return array
     */
    public function getConversations($account_id = 0, $password_hash = null)
    {
        global $account_info, $lang, $reefless;

        if (!$account_id || !$password_hash) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no username or passwordHash received, abort");
            return false;
        }

        $reefless->loadClass('Account');
        $reefless->loadClass('Message');
        $account_info['ID'] = $account_id;
        $conversations = $GLOBALS['rlMessage']->getContacts();

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";

            foreach ($conversations as $key => $entry) {
                $conversations[$key]['Photo'] = (!empty($entry['Photo']) && file_exists(RL_FILES . $entry['Photo'])) ? RL_FILES_URL . $entry['Photo'] : '';
                $conversations[$key]['Full_name'] = $entry['From'] == -1 ? strval($entry['Full_name']) . " (" . $lang['website_visitor'] . ")" : strval($entry['Full_name']);

                if ($entry['From'] == -1) {
                    $from = strval($entry['Visitor_mail']);
                } else if ($entry['Admin']) {
                    $from = 0;
                } else {
                    $from = intval($entry['From']);
                }
                $conversations[$key]['From'] = $from;
                $conversations[$key]['divider'] = $this->convertDate($entry['Date']);
            }
            $response['contacts'] = $conversations;
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';

            // send success
            $response .= '<contacts>';
            if (!empty($conversations)) {
                foreach ($conversations as $key => $entry) {
                    $account_photo = (!empty($entry['Photo']) && file_exists(RL_FILES . $entry['Photo']))
                    ? RL_FILES_URL . $entry['Photo']
                    : '';

                    $from = 0;
                    if ($entry['From'] == -1) {
                        $from = strval($entry['Visitor_mail']);
                    } else if ($entry['Admin']) {
                        $from = 0;
                    } else {
                        $from = intval($entry['From']);
                    }

                    $contact = array(
                        'full_name' => $entry['From'] == -1 ? strval($entry['Full_name']) . " (" . $lang['website_visitor'] . ")" : strval($entry['Full_name']),
                        'from' => $from,
                        'to' => intval($account_id),
                        'visitor_mail' => strval($entry['Visitor_mail']),
                        'message' => strval($entry['Message']),
                        'date' => $entry['Date'],
                        'divider' => $this->convertDate($entry['Date']),
                        'count' => intval($entry['Count']),
                        'photo' => $account_photo,
                        'admin' => intval($entry['Admin']) ? 1 : 0,
                    );
                    $response .= '<contact>';
                    $response .= $this->printValue($contact);
                    $response .= '</contact>';
                }
            }
            $response .= '</contacts>';
        }

        return $response;
    }

    /*
     * Description
     * @param int $account_id
     * @param int $user_id
     * @param int $start
     * @param int $admin
     * @return array
     */
    public function fetchMessages($account_id, $user_id, $start = 0, $admin = 0)
    {
        global $reefless, $rlDb;

        if (!$account_id || $user_id == '') {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no account id or user id received, abort");
            return false;
        }

        $reefless->loadClass('Listings');

        $visitor_email = '';
        if (Valid::isEmail($user_id)) {
            $visitor_email = $user_id;
            $user_id = -1;
        }

        $limit = 15;
        $start = $start > 1 ? ($start - 1) * $limit : 0;

        $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.*, UNIX_TIMESTAMP(`T1`.`Date`) AS `Date`, `T2`.`Username`, `T2`.`First_name`, `T2`.`Last_name`, `T2`.`Photo` ";

        $sql .= "FROM `{db_prefix}messages` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`From` = `T2`.`ID` ";

        $sql .= "WHERE ((`T1`.`To` = " . $account_id . " AND `T1`.`From` = " . $user_id;
        if ($visitor_email) {
            $sql .= " AND `T1`.`Visitor_mail` = '{$visitor_email}' ";
        }
        $sql .= ") OR (`T1`.`To` = " . $user_id . " AND `T1`.`From` = " . $account_id . ")) ";
        $sql .= "AND FIND_IN_SET(IF (`T1`.`From` = '{$account_id}', 'from', 'to'), `T1`.`Remove`) = 0 ";
        $sql .= "ORDER BY `T1`.`ID` Desc ";
        $sql .= "LIMIT {$start}, {$limit}";
        $messages = $rlDb->getAll($sql);

        $calc = $rlDb->getRow("SELECT FOUND_ROWS() AS `calc`");

        // set status readed
        $this->updatedMessagesStatus($account_id, $user_id, $visitor_email);

        //add new messages
        $new_messages = $rlDb->getRow("SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}messages` WHERE `To` = '{$account_id}' AND `Status` = 'new' AND FIND_IN_SET( 'to', `Remove`) = 0");

        if (!empty($messages)) {
            foreach ($messages as $key => $entry) {
                $messages[$key]['Full_name'] = $entry['First_name'] || $entry['First_name'] ? $entry['Last_name'] . " " . $entry['Last_name'] : $entry['Username'];
                if (!empty($entry['Photo']) && file_exists(RL_FILES . $entry['Photo'])) {
                    $messages[$key]['Photo'] = RL_FILES_URL . $entry['Photo'];
                }
                if ($entry['Listing_ID']) {
                    $sql = "SELECT `T1`.*, `T2`.`Type` AS `Listing_type_key`";
                    $sql .= "FROM `{db_prefix}listings` AS `T1` ";
                    $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
                    $sql .= "WHERE `T1`.`ID` = {$entry['Listing_ID']} AND `T1`.`Status` = 'active' AND `T2`.`Status` = 'active' ";
                    $info = $rlDb->getRow($sql);
                    $messages[$key]['listing_title'] = $GLOBALS['rlListings']->getListingTitle($info['Category_ID'], $info, $info['Listing_type_key']);
                } else {
                    $messages[$key]['listing_title'] = "";
                }
                unset($messages[$key]['First_name'], $messages[$key]['Last_name'], $messages[$key]['Username']);
            }
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response['messages'] = $messages;
            if ($messages) {
                $response['total'] = $calc['calc'];
                $response['new_messages'] = $new_messages['Count'];
            }
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';
            $response .= '<messages>';

            if (!empty($messages)) {
                // send success
                $response .= $this->printValue($messages);

                $response .= '<total>' . $calc['calc'] . '</total>';
                $response .= '<new_messages>' . $new_messages['Count'] . '</new_messages>';
            }
            $response .= '</messages>';
        }

        return $response;
    }

    /**
     * Description
     * @param int $message id
     */
    public function fetchMessage($user_id, $send_field = "To")
    {
        global $lang, $rlDb;

        $sql = "SELECT `T1`.*, UNIX_TIMESTAMP(`T1`.`Date`) AS `Date`, `T2`.`Username`, `T2`.`First_name`, `T2`.`Last_name`, `T2`.`Photo` ";
        $sql .= "FROM `{db_prefix}messages` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`From` = `T2`.`ID` ";
        if (!$user_id) {
            $user_id = "-1";
            $sql .= "WHERE `From` = '{$user_id}' ORDER BY `T1`.`ID` Desc";
        } else {
            $sql .= "WHERE `{$send_field}` = '{$user_id}' ORDER BY `T1`.`ID` Desc";
        }
        $message = $rlDb->getRow($sql);

        if ($message['From'] == "-1") {
            $message['Full_name'] = $message['Visitor_name'] . " (" . $lang['website_visitor'] . ")";
        } else if ($message['From'] == "0") {
            $message['Full_name'] = $lang['administrator'];
        } else {
            $message['Full_name'] = $message['First_name'] || $message['Last_name'] ? $message['First_name'] . " " . $message['Last_name'] : $message['Username'];
        }

        if (!empty($message['Photo']) && file_exists(RL_FILES . $message['Photo'])) {
            $message['Photo'] = RL_FILES_URL . $message['Photo'];
        }
        unset($message['First_name'], $message['Last_name'], $message['Username']);

        if ($message['Listing_ID']) {
            $GLOBALS['reefless']->loadClass('Listings');
            $sql = "SELECT `T1`.*, `T2`.`Type` AS `Listing_type_key`";
            $sql .= "FROM `{db_prefix}listings` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
            $sql .= "WHERE `T1`.`ID` = {$message['Listing_ID']} AND `T1`.`Status` = 'active' AND `T2`.`Status` = 'active' ";
            $info = $rlDb->getRow($sql);
            $message['listing_title'] = $GLOBALS['rlListings']->getListingTitle($info['Category_ID'], $info, $info['Listing_type_key']);
        } else {
            $message['listing_title'] = "";
        }

        //get all new message
        $sql = "SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}messages` WHERE `Status` = 'new'";
        if ($message['From'] == "-1") {
            $sql .= " AND  `Visitor_mail` = '{$message['Visitor_mail']}'";
        } else {
            $sql .= " AND  `To` = '{$message['To']}' AND `From` = '{$message['From']}' AND FIND_IN_SET( 'to', `Remove`) = 0";
        }
        $new_messages = $rlDb->getRow($sql);
        $message['count'] = $new_messages['Count'];

        // get count new messages
        $count = $rlDb->getRow("SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}messages` WHERE `To` = '{$message['To']}' AND `Status` = 'new' AND FIND_IN_SET( 'to', `Remove`) = 0");
        $message['new_messages'] = $count['Count'];

        return $message;
    }

    /**
     * Description
     * @param int $account_id
     * @param int $user_id
     * @param string $message
     * @return array
     */
    public function sendMessage($account_id, $user_id, $listing_id = 0, $notification = 0)
    {
        global $lang, $config, $rlListingTypes;
        if (!$account_id || !$user_id) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no account id or user id received, abort");
            return false;
        }

        $message_text = $GLOBALS['json_support'] ? $_POST['Message'] : $_POST['message'];
        $message_text = $GLOBALS['rlValid']->xSql($message_text);

        $new_message = array(
            'From' => $account_id,
            'To' => $user_id,
            'Message' => $message_text,
            'Listing_ID' => $listing_id,
            'Admin' => 0,
            'Date' => 'NOW()',
        );

        if ($GLOBALS['rlDb']->insertOne($new_message, 'messages')) {
            $GLOBALS['rlHook']->load('rlMessagesAjaxAfterMessageSent', $user_id, $new_message);

            if ($GLOBALS['json_support']) {
                $GLOBALS['response_type'] = "json";
                $response['messages'] = true;
            } else {
                $this->custom_output = true;
                $response = '<?xml version="1.0" encoding="UTF-8"?>';
                $response .= '<messages>true</messages>';
            }
        }
        return $response;
    }

    /**
     * Description contact owner
     **/
    public function contactOwner()
    {
        global $lang, $config, $reefless, $rlValid, $rlListingTypes;

        $user_id = (int) $_REQUEST['id'];
        $listing_id = (int) $_REQUEST['listing_id'];
        $message_new = $rlValid->xSql($_REQUEST['message']);
        $phone = $rlValid->xSql($_REQUEST['phone']);
        $name = $rlValid->xSql($_REQUEST['name']);
        $email = $rlValid->xSql($_REQUEST['mail']);

        if ($config['messages_save_visitor_message']) {
            $insert = array(
                'From' => -1,
                'To' => $user_id,
                'Message' => $message_new,
                'Date' => 'NOW()',
                'Visitor_mail' => $email,
                'Visitor_phone' => $phone,
                'Visitor_name' => $name,
                'Listing_ID' => $listing_id,
            );
            $GLOBALS['rlDb']->insertOne($insert, 'messages');

            if ($config['messages_save_visitor_message']) {
                $message = $this->fetchMessage("-1");

                $array_message = array('key' => 'message', 'title' => $lang['new_message'], 'photo' => '');
                foreach ($message as $key => $val) {
                    if ($key == "From" || $key == "To") {
                        $array_message[strtolower($key . "_id")] = $val == '-1' ? $message['Visitor_mail'] : $val;
                    } else {
                        $array_message[strtolower($key)] = $val;
                    }
                }

                $this->sendPushNotification($array_message);
            }
        }

        if ($listing_id) {
            /* get listing/owner details */
            $sql = "SELECT `T1`.*, `T2`.`Type` AS `Listing_type_key`, `T2`.`Path` AS `Category_path`, `T3`.`Mail` AS `Owner_email`, ";
            $sql .= "`T3`.`Username` AS `Owner_username`, `T3`.`First_name` AS `Owner_first_name`, `T3`.`Last_name` AS `Owner_last_name` ";
            $sql .= "FROM `{db_prefix}listings` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
            $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T3` ON `T1`.`Account_ID` = `T3`.`ID` ";
            $sql .= "WHERE `T1`.`ID` = {$listing_id} AND `T1`.`Status` = 'active' AND `T2`.`Status` = 'active' AND `T3`.`Status` = 'active'";

            $info = $GLOBALS['rlDb']->getRow($sql);
            $owner_name = $info['Owner_first_name'] || $info['Owner_last_name'] ? $info['Owner_first_name'] . ' ' . $info['Owner_last_name'] : $info['Owner_username'];
            $reefless->loadClass('Listings');
            $listing_type = $rlListingTypes->types[$info['Listing_type_key']];
            $listing_title = $GLOBALS['rlListings']->getListingTitle($info['Category_ID'], $info, $info['Listing_type_key']);

            $link = $reefless->getListingUrl($info);
            $link = '<a href="' . $link . '">' . $listing_title . '</a>';
        } else {
            $account = $this->fetchAccountData($user_id);
            $info['Account_ID'] = $account['ID'];
            $info['Owner_email'] = $account['Mail'];
            $info['Owner_username'] = $account['Username'];
            $info['Owner_first_name'] = $account['First_name'];
            $info['Owner_last_name'] = $account['Last_name'];
            $owner_name = $info['Owner_first_name'] || $info['Owner_last_name'] ? trim($info['Owner_first_name'] . ' ' . $info['Owner_last_name']) : $info['Owner_username'];
            $link = $lang['not_available'];
        }

        $message_new = preg_replace('/(\\n|\\t|\\r)/', '<br />', $message_new);

        $phone_line = $lang['contact_phone'] . ': ';
        $phone_line = $phone ? $phone : $lang['not_available'];

        $reefless->loadClass('Mail');
        $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('contact_owner');

        $find = array('{owner_name}', '{visitor_name}', '{message}', '{listing_link}', '{contact_phone}');
        $replace = array($owner_name, $name, $message_new, $link, $phone_line);
        $mail_tpl['subject'] = str_replace('{visitor_name}', $name, $mail_tpl['subject']);
        $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);

        // display the link of listing in email what has been sent from listing details page only
        $mail_tpl['body'] = preg_replace(
            "/\{if listing_page\}(.*?)\{\/if\}/smi",
            $listing_id && $link ? '$1' : '',
            $mail_tpl['body']
        );

        /* send e-mail for friend */
        $GLOBALS['rlMail']->send($mail_tpl, $info['Owner_email'], null, $email, $name);

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response['messages'] = true;
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';
            $response .= '<messages>true</messages>';
        }

        return $response;
    }

    /**
     * send push notitfication
     * @param int $contact_id
     * @param array $data
     */
    public function sendPushNotification($data)
    {
        global $config;

        if (false !== $tokens = $this->fetchAllActiveTokensByAccountId($data['to_id'])) {

            $GLOBALS['reefless']->loadClass('Pusher', false, 'androidConnect');
            $GLOBALS['rlPusher']->apiKey = $config['android_google_key'];

            foreach ($tokens as $key => $val) {
                $title = $GLOBALS['rlDb']->getOne('Value', "`Key` = 'new_message' AND `Code` = '{$val['Language']}'", 'lang_keys');
                $data['title'] = $title ? $title : $data['title'];

                $GLOBALS['rlPusher']->notify($val['Token'], $data);
            }
        }
    }

    /**
     * set status read for current chat
     * @param int $account_id
     * @param int $user_id
     * @param string $user_email
     */
    public function updatedMessagesStatus($account_id, $user_id, $user_email = '')
    {
        // set status readed
        $update = array(
            'fields' => array(
                'Status' => 'readed',
            ),
            'where' => array(
                'From' => $user_id,
                'To' => $account_id,
                'Visitor_mail' => $user_email ? $user_email : "",
            ),
        );

        $GLOBALS['rlDb']->updateOne($update, 'messages');

        return true;
    }

    /**
     * Description get count messages of account
     * @param int $account_id
     * @param int $user_id
     */
    public function getCountMessages($account_id, $user_id)
    {

        // set status readed
        $this->updatedMessagesStatus($account_id, $user_id);

        $new_messages = $GLOBALS['rlDb']->getRow("SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}messages` WHERE `To` = '{$account_id}' AND `Status` = 'new' AND FIND_IN_SET( 'to', `Remove`) = 0 ");

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response['new_messages'] = $new_messages['Count'];
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';
            $response .= '<new_messages>' . $new_messages['Count'] . '</new_messages>';
        }
        return $response;
    }

    /**
     * Description remove messages
     * @param int    - $account_id
     * @param string - $password
     * @param string - $user_id
     *
     * return sring  - out
     */
    public function removeMessages($account_id, $password, $user_id)
    {
        global $rlDb;

        if (!$account_id || !$password) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no username or passwordHash received, abort");
            return false;
        }

        if (Valid::isEmail($user_id)) {
            $visitor_email = $user_id;
            $user_id = -1;
        }

        if ($account_id == $user_id) {
            $sql = "DELETE FROM `{db_prefix}messages` WHERE `To` = {$account_id} AND `From` = '{$user_id}'";
        } else if ($visitor_email) {
            $sql = "DELETE FROM `{db_prefix}messages` WHERE `To` = {$account_id} AND `From` = '{$user_id}' AND `Visitor_mail` = '{$visitor_email}'";
        } else {
            $update = true;
            $sql = "UPDATE `{db_prefix}messages` SET `Remove` = IF(`Remove` = '', IF(`From` = {$account_id}, 'from', 'to'), CONCAT(`Remove`, ',', IF(`From` = {$account_id}, 'from', 'to'))) ";
            $sql .= "WHERE (`To` = {$account_id} AND `From` = '{$user_id}') ";
            $sql .= "OR (`To` = '{$user_id}' AND `From` = {$account_id}) ";
        }

        if ($rlDb->query($sql)) {

            // delete message after update
            if ($update) {
                $rlDb->query("DELETE FROM `{db_prefix}messages`  WHERE ((`To` = {$account_id} AND `From` = '{$user_id}') OR (`To` = '{$user_id}' AND `From` = {$account_id})) AND (`Remove` = 'from,to' OR `Remove` = 'to,from') ");
            }

            //get new messages for account
            $count = $rlDb->getRow("SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}messages` WHERE `To` = {$account_id} AND `Status` = 'new' AND FIND_IN_SET( 'to', `Remove`) = 0");
            $message['new_messages'] = $count['Count'];
            $out['success'] = $count['Count'];
        } else {
            $out['error'] = 'error';
        }

        if ($GLOBALS['json_support']) {
            $GLOBALS['response_type'] = "json";
            $response = $out;
        } else {
            $this->custom_output = true;
            $response = '<?xml version="1.0" encoding="UTF-8"?>';
            if ($out['error']) {
                $response .= '<error><![CDATA[' . $out['error'] . ']]></error>';
            } else {
                $response .= '<success><![CDATA[' . $out['success'] . ']]></success>';
            }
        }
        return $response;
    }

    /**
     * Description
     * @param int $account_id
     * @return array
     */
    public function fetchAllActiveTokensByAccountId($account_id)
    {
        $account_id = (int) $account_id;

        if (!$account_id) {
            $GLOBALS['rlDebug']->logger("ANDROID: " . __FUNCTION__ . "(), no account id received, abort");
            return false;
        }

        $sql = "SELECT `Token`, `Language` FROM `{db_prefix}android_push_tokens` ";
        $sql .= "WHERE `Status` = 'active' AND `Account_ID` = " . $account_id;
        $entries = $GLOBALS['rlDb']->getAll($sql);

        if (empty($entries)) {
            return false;
        }

        return $entries;
    }

    /**
     * registerForRemoteNotification
     * @param  int    $account_id
     * @param  int    $phone_id
     * @param  string $device_token
     * @param  string $status
     * @param  string $language
     * @return array
     */
    public function registerForRemoteNotification($account_id, $phone_id, $device_token, $status, $language)
    {
        global $rlDb;
        $_success = false;

        if ($device_token) {
            $GLOBALS['rlValid']->sql($device_token);
            $update_status = $status ? 'active' : 'inactive';

            //disable other tokes for this devices.
            if ($rlDb->getOne('Phone_ID', "`Phone_ID` = '{$phone_id}'", 'android_push_tokens')) {
                $rlDb->query("UPDATE `{db_prefix}android_push_tokens` SET `Status` = 'inactive' WHERE `Phone_ID` = '{$phone_id}' ");
            }

            if ($rlDb->getOne('Phone_ID', "`Phone_ID` = '{$phone_id}' ", 'android_push_tokens')) {
                $sql = "UPDATE `{db_prefix}android_push_tokens` SET `Status` = '{$update_status}', `Token` = '{$device_token}',  `Phone_ID` = '{$phone_id}', `Account_ID` = '{$account_id}', `Language` = '{$language}' ";
                $sql .= "WHERE `Phone_ID` = '{$phone_id}' ";
                $rlDb->query($sql);
            } else {
                $sql = "INSERT INTO `{db_prefix}android_push_tokens` (`Token`, `Phone_ID`, `Account_ID`, `Status`, `Language`) VALUES ('{$device_token}', '{$phone_id}', '{$account_id}', 'active', '{$language}') ";
                $rlDb->query($sql);
            }
            $_success = true;
        }
        return array('success' => $_success);
    }

    /**
     * Save lang for notification
     *
     * @since 4.0.0
     *
     * @param  int    $account_id
     * @param  int    $phone_id
     * @param  string $language
     */
    public function saveLangForNotification($account_id, $phone_id, $language)
    {
        $update = array(
            'fields' => array(
                'Language' => $language,
            ),
            'where' => array(
                'Account_ID' => $account_id,
                'Phone_ID' => $phone_id,
            ),
        );

        $GLOBALS['rlDb']->updateOne($update, 'android_push_tokens');
    }

    /**
     * Add or remove listing from favorite
     *
     * @since 4.0.0
     *
     * @param  int    $account_id
     * @param  int    $listing_id
     * @param  string $mode
     */
    public function actionFavorite($account_id, $listing_id, $mode)
    {
        global $rlDb;

        if ($account_id) {
            if ($mode == 'remove') {
                $rlDb->query("
                    DELETE FROM `{db_prefix}favorites`
                    WHERE `Account_ID` = {$account_id} AND `Listing_ID` = {$listing_id}"
                );
            } else {

                if (!$rlDb->getOne('ID', "`Account_ID` = {$account_id} AND `Listing_ID` = {$listing_id} ", 'favorites')) {
                    $insert = array(
                        'Account_ID' => $account_id,
                        'Listing_ID' => $listing_id,
                        'IP' => Util::getClientIP(),
                        'Date' => 'NOW()',
                    );
                    $rlDb->insertOne($insert, 'favorites');
                }
            }
        }
    }

    /**
     * Sync favorites with account
     *
     * @since 4.0.0
     *
     * @param  array $favorites_ids
     * @param  int   $account_id
     */
    public function synchronizeFavorites($favorites_ids, $account_id)
    {
        global $rlDb;

        if ($account_id) {
            $sql = "SELECT GROUP_CONCAT(`Listing_ID`) as `ids` FROM `{db_prefix}favorites` WHERE `Account_ID` = " . $account_id;
            $db_favorites = $rlDb->getRow($sql, 'ids');
            $db_favorites_arr = explode(",", $db_favorites);

            if ($favorites_ids && $_REQUEST['favorites_sync'] == true) {
                $insert = [];
                $k = 0;
                foreach ($favorites_ids as $key => $id) {
                    if (!in_array($id, $db_favorites_arr) && $id) {
                        $insert[$k]['Account_ID'] = $account_id;
                        $insert[$k]['Listing_ID'] = $id;
                        $insert[$k]['Date'] = 'NOW()';
                        $insert[$k]['IP'] = Util::getClientIP();
                        $k++;
                    }
                }

                if ($insert) {
                    $rlDb->insert($insert, "favorites");
                }
            }

            $favorites_ids = array_unique(array_merge($favorites_ids, $db_favorites_arr));
        }

        return $favorites_ids;
    }

    /**
     * Define if the it's new version (2.2.0 and above) of the MultiField plugin
     *
     * @since 4.0.1
     *
     * @return boolean
     */
    public function isMultiFieldNew()
    {
        return isset($GLOBALS['config']['mf_format_keys']);
    }

    /**
     * Get locations
     *
     * @since 4.2.0
     *
     * @param string $query - search query
     * @return array
     **/
    public function zipLocation($query)
    {
        global $config;
        $provider = $config['geocoding_provider'] == 'google'
        ? 'googlePlaces' // switch to googlePlaces for better results
         : $config['geocoding_provider'];

        if (false === $locations = Util::geocoding($query, false, null, $provider)) {
            return false;
        }

        return $locations;
    }

    /**
     * Places coordinates
     *
     * @since 4.2.0
     *
     * @param string $place_id - Place ID
     * @return array
     *
     **/
    public function placesСoordinates($place_id)
    {
        global $config;

        if (!$place_id || !$config['google_server_map_key']) {
            return array(
                'status' => 'ERROR',
                'message' => !$config['google_server_map_key']
                ? 'No google api key specified'
                : 'No place_id param passed',
            );
        }

        $host = 'https://maps.googleapis.com/maps/api/place/details/json';
        $params = array(
            'placeid' => $place_id,
            'key' => $config['google_server_map_key'],
        );

        $request = $host . '?' . http_build_query($params);
        $response = Util::getContent($request);
        $data = json_decode($response);

        $out = $data->status ? $data->result->geometry->location : null;

        return $out;
    }

    /**
     * resizePhotos
     *
     * @deprecated 4.0.0
     */
    public function resizePhotos($stack = 1)
    {}
    /**
     * @hook staticDataRegister
     *
     * @since 3.2.0
     * @deprecated 4.2.0
     */
    public function hookStaticDataRegister()
    {}
}
