<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLLISTINGSTATUS.CLASS.PHP
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

use Flynax\Utils\ListingMedia;

class rlListingStatus
{
    /**
     * @var array
     */
    public $lbGlobalBoxes = [];

    /**
     * @var array non support box position
     */
    public $rejectedBoxSides = array('header_banner', 'integrated_banner');

    /**
     * @var array all labels
     *
     * @since 3.0.0
     */
    public $all_labels = [];

    /**
     * @var array simple type
     *
     * @since 3.0.0
     */
    public $noInType = ['wait','visible','invisible'];

    /**
     * Allow access to the listing details page of the invisible listings
     *
     * @var boolean
     * @since 3.1.0
     */
    public $allowInvisibleListingDetailsAccess = false;

    /**
     * Install process
     *
     * @since 2.4.0
     */
    public function install()
    {
        global $rlDb, $rlListingTypes;

        $raw_sql = "`ID` int(11) NOT NULL AUTO_INCREMENT,
                `Key` VARCHAR( 255 ) NOT NULL,
                `Type` varchar( 255 ) NOT NULL,
                `Days` INT( 5 ) NOT NULL,
                `Count` INT( 10 ) NOT NULL,
                `Delete` ENUM( 'simple', 'delete', 'disabled' ) DEFAULT 'simple' NOT NULL,
                `Order` ENUM( 'random', 'latest' ) DEFAULT 'latest' NOT NULL,
                `Used_block` ENUM( '1', '0' ) DEFAULT '1' NOT NULL ,
                `Watermark_type` ENUM( 'image', 'text' ) DEFAULT 'image' NOT NULL,
                `Position` INT( 2 ) DEFAULT '3' NOT NULL,
                `Label_type` ENUM('user', 'admin', 'verify' ) DEFAULT 'user' NOT NULL,
                `Status` ENUM( 'active', 'approval' ) NOT NULL,
                PRIMARY KEY (`ID`)";

        $rlDb->createTable("listing_status", $raw_sql, RL_DBPREFIX, "ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci");

        $raw_sql = "`ID` int(11) NOT NULL AUTO_INCREMENT,
                `Key` VARCHAR( 10 ) NOT NULL,
                `Listing_ID` int( 10 ) NOT NULL,
                `Type` ENUM( 'user', 'admin', 'verify' ) DEFAULT 'user' NOT NULL,
                `Date` DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
                PRIMARY KEY (`ID`)";

        $rlDb->createTable("listing_status_data", $raw_sql, RL_DBPREFIX, "DEFAULT CHARSET=utf8");

        $fields = array();
        $fields['Sub_status']      = "VARCHAR( 50 ) DEFAULT 'visible' NOT NULL AFTER `Date`";
        $fields['Sub_status_data'] = "VARCHAR( 255 ) NOT NULL AFTER `Date`";
        $rlDb->addColumnsToTable($fields, 'listings');

        if ($rlDb->getRow("SHOW INDEXES FROM `{db_prefix}listings` WHERE `Key_name` = 'Category_status'")) {
            $rlDb->query("ALTER TABLE `{db_prefix}listings` DROP INDEX `Category_status`");
        }
        if (!$rlDb->getRow("SHOW INDEXES FROM `{db_prefix}listings` WHERE `Key_name` = 'Category_status_substatus'")) {
            $rlDb->query("ALTER TABLE `{db_prefix}listings` ADD INDEX `Category_status_substatus` (`Category_ID`, `Status`, `Sub_status`)");
        }

        $updatePage = array(
            'fields' => array('Status' => 'trash'),
            'where'  => array('Key' => 'verify_photos'),
        );
        $rlDb->updateOne($updatePage, 'pages');

        // update box pages
        $updateblock = array(
            'fields' => array('Page_ID' => '1,2,42', 'Sticky' => '0'),
            'where'  => array('Key' => 'lb_sold'),
        );
        if ($rlListingTypes->types['girls'] || $rlListingTypes->types['guys']) {
            $_SESSION['lb_sold_approval'] = 'approval';
        }
        $GLOBALS['rlDb']->updateOne($updateblock, 'blocks');

        $this->installWatermark();

        if ($rlListingTypes->types['girls'] || $rlListingTypes->types['guys']) {
            $updateConfig = array(
                'fields' => array('Default' => '1'),
                'where'  => array('Key' => 'ls_verify_module'),
            );
            $rlDb->updateOne($updateConfig, 'config');

            $this->installVerification();
        }
    }

    /**
     *
     * install first status(sold)
     *
     */
    public function installWatermark()
    {
        global $rlListingTypes, $rlDb;

        $allLangs = $GLOBALS['languages'];

        $updateListingTypes = false;

        $types = 'listings';
        if ($rlListingTypes->types['motors'] ) {
            $types .= ',motors';
            $updateListingTypes = true;
        }
        if ($rlListingTypes->types['property']) {
            $types .= ',property';
            $updateListingTypes = true;
        }

        $lable_status = 'active';
        if ($rlListingTypes->types['girls'] || $rlListingTypes->types['guys']) {
            $lable_status = 'approval';
        }

        $data = array(
            'Key'            => 'sold',
            'Type'           => $types,
            'Days'           => '14',
            'Count'          => '6',
            'Delete'         => 'disabled',
            'Used_block'     => '1',
            'Status'         => $lable_status,
            'Label_type'     => 'user',
            'Watermark_type' => 'image',
            'Position'       => '3',
        );

        $folder_name = RL_FILES . "watermark";
        $GLOBALS['reefless']->rlMkdir($folder_name);

        if ($rlDb->insertOne($data, 'listing_status')) {
            $file = RL_PLUGINS . 'listing_status' . RL_DS . 'sold.png';
            $fileLarge = RL_PLUGINS . 'listing_status' . RL_DS . 'sold_large.png';

            $field = '';
            $fieldL = '';
            foreach ($allLangs as $lkey => $lval) {
                $newname = 'sold_' . $lkey . '.png';
                $newnameLarge = 'sold_large_' . $lkey . '.png';
                $newfile = $folder_name . RL_DS . $newname;
                $newfileLarge = $folder_name . RL_DS . $newnameLarge;

                if (copy($file, $newfile) && copy($fileLarge, $newfileLarge)) {
                    $field  = 'watermark_' . $lkey;
                    $fieldL = 'watermarkLarge_' . $lkey;
                    $fields = array();
                    $fields[$field]  = 'VARCHAR( 50 ) NOT NULL';
                    $fields[$fieldL] = 'VARCHAR( 50 ) NOT NULL';
                    $rlDb->addColumnsToTable($fields, 'listing_status');

                    $updat_watermark['fields'][$field] = $newname;
                    $updat_watermark['fields'][$fieldL] = $newnameLarge;
                }
            }

            $updat_watermark['where']['ID'] = '1';

            $rlDb->updateOne($updat_watermark, 'listing_status');

            if ($updateListingTypes) {
                $content = $rlDb->getOne('Content', "`Key` = 'lb_sold'", 'blocks');
                $content = str_replace('"listings",', '"'. $types .'",', $content);

                $updateBox['fields']['Content'] = $content;
                $updateBox['where']['Key'] = 'lb_sold';
                $rlDb->updateOne($updateBox, 'blocks');
            }
        }
    }

    /**
     * install verified
     *
     * @since 3.0.0
     */
    public function installVerification()
    {
        global $rlDb, $rlListingTypes;

        $types = '';
        if ($rlListingTypes->types['girls'] ) {
            $types = 'girls';
        }
        if ($rlListingTypes->types['guys']) {
            $types .=  $types ? ',guys' : 'guys';
        }

        $raw_sql = "`ID` int(11) NOT NULL AUTO_INCREMENT,
            `Listing_ID` int(15) NOT NULL,
            `Image` varchar(100) NOT NULL,
            `Description` mediumtext NOT NULL,
            `Date` datetime NOT NULL,
            PRIMARY KEY (`ID`)";

        $rlDb->createTable("listing_status_verified", $raw_sql, RL_DBPREFIX, "DEFAULT CHARSET=utf8");

        $dataVerified = array(
            'Key'            => 'verified',
            'Type'           => $types,
            'Days'           => '0',
            'Count'          => '6',
            'Delete'         => 'simple',
            'Used_block'     => '0',
            'Status'         => 'active',
            'Label_type'     => 'verify',
            'Watermark_type' => 'text',
            'Position'       => '2',
        );

        $rlDb->insertOne($dataVerified, 'listing_status');

        $updatePage = array(
            'fields' => array('Status' => 'active'),
            'where'  => array('Key' => 'verify_photos'),
        );
        $rlDb->updateOne($updatePage, 'pages');
    }

    /**
     * uninstall verified
     *
     * @since 3.0.0
     */
    public function uninstallVerification()
    {
        global $rlDb;

        $GLOBALS['reefless']->deleteDirectory(RL_FILES . 'verified_photos');

        $rlDb->dropTable('listing_status_verified');

        $sql = "DELETE FROM `{db_prefix}listing_status` WHERE `Key` = 'verified'";
        $rlDb->query($sql);

        $updatePage = array(
            'fields' => array('Status' => 'trash'),
            'where'  => array('Key' => 'verify_photos'),
        );

        $rlDb->updateOne($updatePage, 'pages');
    }

    /**
     * Uninstall process
     *
     * @since 2.4.0
     */
    public function uninstall()
    {
        global $rlDb;

        if ($GLOBALS['config']['ls_verify_module']) {
            $this->uninstallVerification();
        }

        $GLOBALS['reefless']->deleteDirectory(RL_FILES . 'watermark');

        $this->deleteAllListingPhotosWithLabel();

        $rlDb->dropTable('listing_status');

        $rlDb->dropTable('listing_status_data');

        if ($rlDb->getRow("SHOW INDEXES FROM `{db_prefix}listings` WHERE `Key_name` = 'Category_status_substatus'")) {
            $rlDb->query("ALTER TABLE `{db_prefix}listings` DROP INDEX `Category_status_substatus`");
        }
        if (!$rlDb->getRow("SHOW INDEXES FROM `{db_prefix}listings` WHERE `Key_name` = 'Category_status'")) {
            $rlDb->query("ALTER TABLE `{db_prefix}listings` ADD INDEX `Category_status` (`Category_ID`, `Status`)");
        }

        $fields = ['Sub_status', 'Sub_status_data'];
        $rlDb->dropColumnsFromTable($fields, 'listings');
    }

    /**
     * delete listing with label
     *
     */
    public function deleteAllListingPhotosWithLabel()
    {
        $data = $GLOBALS['rlDb']->getAll("SELECT `Key` FROM `{db_prefix}listing_status`  ");
        foreach ($data as $key => $val) {
            $this->foundPhotoLabel(RL_FILES, $val['Key']);
        }
    }

    /**
     * delete All Listing Photos With label
     *
     * @param string $dir   - folder
     * @param bool   $label - label
     *
     */
    public function foundPhotoLabel($dir = RL_ROOT, $label = false)
    {
        $files = $GLOBALS['reefless']->scanDir($dir, false, true);

        if ($files) {
            foreach ($files as $file) {
                if ($file['type'] == 'dir') {
                    $this->foundPhotoLabel($dir . $file['name'] . RL_DS, $label);
                } elseif ($file['type'] == 'file') {
                    if (preg_match('/_' . $label . '_/', $file['name'])) {
                        $file_path = rtrim($dir) . $file['name'];
                        unlink($file_path);
                    }
                }
            }
        }
    }

    /**
     * Delete all watermarks in folder
     *
     * @param string  $key   - key of watermark
     * @param string  $code  - code of langauge
     * @param string  $name  - name status
     * @param boolean $large - checker
     *
     * @return string $_response
     */
    public function deleteWatermark($key, $code, $name, $large = false)
    {
        global $config;

        $field = $large == 1 ? 'watermarkLarge_' . $code : 'watermark_' . $code;

        /*update watermark*/
        $GLOBALS['rlDb']->query("UPDATE `{db_prefix}listing_status` SET `{$field}` = '' WHERE `Key` = '{$key}'");

        /*delete watermark*/
        $file = RL_FILES . "watermark" . RL_DS . $name;
        unlink($file);

        if (!$GLOBALS['rlSmarty']) {
            require_once RL_LIBS . 'smarty' . RL_DS . 'Smarty.class.php';
            $GLOBALS['reefless']->loadClass("Smarty");
        }

        $GLOBALS['rlSmarty']->assign_by_ref('code', $code);

        $lang = $GLOBALS['lang'];
        // modify ls_watermarks_hint
        $label_width = (int) (($config['pg_upload_thumbnail_width'] / 100) * 20);
        $lang['ls_watermarks_label_hint'] = str_replace('{size}', $label_width, $lang['ls_watermarks_hint']);

        $label_large_width = (int) (($config['pg_upload_large_width'] / 100) * 20);
        $lang['ls_watermarks_large_label_hint'] = str_replace('{size}', $label_large_width, $lang['ls_watermarks_hint']);

        $GLOBALS['rlSmarty']->assign_by_ref('lang', $lang);

        if ($large) {
            $GLOBALS['rlSmarty']->assign_by_ref('large', $large);
        }
        $tpl = RL_PLUGINS . 'listing_status' . RL_DS . 'admin' . RL_DS . 'watermark.tpl';
        $out['html'] = $GLOBALS['rlSmarty']->fetch($tpl, null, null, false);
        $out['message'] = $GLOBALS['lang']['lb_watermark_removed'];
        $out['status'] = "ok";

        return $out;
    }

    /**
     * delete
     *
     * @package Ajax
     *
     * @param int $id - id
     *
     * @return string $out
     */
    public function deleteStatusBlock($id)
    {
        global $rlDb, $rlLang;

        $id = (int) $id;
        if (!$id) {
            return false;
        }
        $key = $rlDb->getOne('Key', "`ID` = '{$id}'", 'listing_status');

        if ($key == 'verified') {
            $out['status'] = 'error';
            $out['message'] = $GLOBALS['lang']['ls_cannot_remove_verified'];
            return $out;
        }

        // updated listing if labels removed
        $rlDb->query("UPDATE `{db_prefix}listings` SET `Sub_status` = 'visible' WHERE `Sub_status` = '{$key}' ");
        $this->foundPhotoLabel(RL_FILES, $key);

        // delete
        $rlDb->query("DELETE FROM `{db_prefix}listing_status` WHERE `ID` = '{$id}' LIMIT 1");

        $rlDb->query("DELETE FROM `{db_prefix}blocks` WHERE `Key` = 'lb_{$key}' LIMIT 1");

        if (method_exists($rlLang, 'deletePhrases')) {
            $rlLang->deletePhrases([['Key' => "lsl_{$key}"], ['Key' => "blocks+name+lb_{$key}"]]);
        } else {
            $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'lsl_{$key}' || `Key` = 'blocks+name+lb_{$key}'");
        }

        $out['status'] = 'ok';
        $out['message'] = $GLOBALS['lang']['block_deleted'];

        return $out;
    }

    /**
     * refresh photo
     *
     * @param string $stack - stack
     *
     * @return int - 1 or 0
     */
    public function refreshLabel($stack)
    {
        $this->rebuild();
        $out['stack'] = 0;

        return $out;
    }

    /**
     *
     * rebuld listing status fields
     *
     */
    public function rebuild()
    {
        if ($GLOBALS['languages']) {
            $languages = $GLOBALS['languages'];
        } else {
            $languages = $GLOBALS['rlLang']->getLanguagesList();
        }

        $fields = array();
        foreach ($languages as $key => $val) {
            $fields['watermark_' . $val['Code']]      = 'VARCHAR( 50 ) NOT NULL';
            $fields['watermarkLarge_' . $val['Code']] = 'VARCHAR( 50 ) NOT NULL';
        }

        if ($fields) {
            $GLOBALS['rlDb']->addColumnsToTable($fields, 'listing_status');
        }
    }

    /**
     * Add label columns by language id
     * @since 3.0.0
     * @param string code - lang code
     */
    public function addLabelFields($code)
    {
        $fields = [];
        $fields['watermark_' . $code]      = 'VARCHAR( 50 ) NOT NULL';
        $fields['watermarkLarge_' . $code] = 'VARCHAR( 50 ) NOT NULL';
        $GLOBALS['rlDb']->addColumnsToTable($fields, 'listing_status');
    }

    /**
     * check content box
     *
     * @param string $type  - type list
     * @param string $days  - days keep in active
     * @param int    $limit - listing number per request
     * @param int    $label - key label
     * @param array  $field - fields for update in grid
     *
     * @return array - listings information
     */
    public function checkContentBlock($type, $days, $limit, $label, $order, $field)
    {
        $days = (int) $days;
        $limit = (int) $limit;
        if (is_array($field)) {
            $data = $GLOBALS['rlDb']->fetch(array('Type', 'Days', 'Count', 'Order', 'Key'), array('ID' => $field[2]), null, null, 'listing_status', 'row');
            $type = $data['Type'];
            $label = $data['Key'];
            $order = $data['Order'];
            if ($field[0] == 'Days') {
                $days = $field[1];
                $limit = $data['Count'];
            } elseif ($field[0] == 'Count') {
                $days = $data['Days'];
                $limit = $field[1];
            }
        }

        $content = '
				global $reefless, $rlSmarty;
				$reefless->loadClass("ListingStatus", null, "listing_status");
				global $rlListingStatus;
				$ls_listings = $rlListingStatus->getRecentlySoldListings( "' . $type . '", "' . $days . '", "' . $limit . '", "' . $label . '", "' . $order . '" );
				$key_s = "' . $label . '";
				$rlSmarty->assign_by_ref("ls_key", $key_s);
				$rlSmarty->assign_by_ref("ls_listings", $ls_listings);
				$rlSmarty->display(RL_PLUGINS . "listing_status" . RL_DS . "recently_sold.block.tpl");
			';

        return preg_replace("'(\r|\n|\t)'", "", $content);
    }

    /**
     *  Change sub Status for item in plugin
     *
     * @param int    $id     - id
     * @param string $status - status listing
     *
     * @return mixed - information
     */
    public function changeSubStatus($id, $status)
    {
        global $rlDb;

        $key = $rlDb->getOne('Key', "`ID` = '{$id}'", 'listing_status');

        // updated listing with removed label
        $rlDb->query("UPDATE `{db_prefix}listings` SET `Sub_status` = 'visible' WHERE `Sub_status` = '{$key}' ");
        $this->foundPhotoLabel(RL_FILES, $key);

        if ($status) {
            // update status for item
            $rlDb->query("UPDATE `{db_prefix}listing_status` SET `Status` = '{$status}' WHERE `ID` =" . $id);
            $rlDb->query("UPDATE `{db_prefix}blocks` SET `Status` = '{$status}' WHERE `Key` = 'lb_{$key}' ");
        }

        $out['status'] = 'ok';

        return $out;
    }

    /**
     * ajax Change Status
     *
     * @param string  $listing_id - id listing
     * @param int     $status     - status listing
     *
     * @return mixed - information
     */
    public function changeStatus($listing_id, $status = 'visible')
    {
        global $config, $reefless, $rlDb, $lang, $rlSmarty;
        if (empty($listing_id)) {
            return false;
        }

        $listing_info = $rlDb->fetch(
            array('Sub_status', 'Sub_status_data', 'Category_ID', 'Account_ID'),
            array('ID' => $listing_id),
            null,
            null,
            'listings',
            'row'
        );

        $label_keys = explode(',', $listing_info['Sub_status_data']);
        if (in_array($status, $label_keys)) {
            $out['status'] = 'error';
            $out['default'] = $listing_info['Sub_status'];
            return $out;
        }

        $updated_info = [];
        $reefless->loadClass('Categories');

        if (!$this->all_labels) {
            $sql = "SELECT * FROM `{db_prefix}listing_status` ";
            $sql .= "WHERE `Status` = 'active'";
            $this->all_labels = $rlDb->getAll($sql, 'Key');
        }

        $status_data = $rlDb->fetch(
            '*',
            array('Listing_ID' => $listing_id),
            null,
            null,
            'listing_status_data'
        );

        if ($status_data) {

            $issetItem = false;
            foreach ($status_data as $key => $value) {
                if ($this->all_labels[$value['Key']]['Position'] == $this->all_labels[$status]['Position']
                    && !in_array($status, $this->noInType)
                    && $value['Type'] != 'user' ) {
                    $out['message'] = $lang['ls_notice_duplicate_position'];
                    $out['default'] = $listing_info['Sub_status'];
                    $out['status']  = 'error';
                    return $out;
                }

                if ($value['Type'] == 'user') {
                    $issetItem = true;
                    $ls_status[] = $status;
                } else {
                    $ls_status[] = $value['Key'];
                }
            }
            if (!$issetItem) {
                $ls_status[] = $status;
            }

            $ls_status = implode(',', $ls_status);

            if ($issetItem) {
                $update = array(
                    'fields' => array(
                        'Key'  => $status,
                        'Date' => 'NOW()',
                    ),
                    'where' => array(
                        'Type'       => 'user',
                        'Listing_ID' => $listing_id,
                    ),
                );
                $rlDb->updateOne($update, 'listing_status_data');
            } else {
                $insertData = array(
                    'Listing_ID' => $listing_id,
                    'Type'       => 'user',
                    'Key'        => $status,
                    'Date'       => 'NOW()',
                );
                $rlDb->insertOne($insertData, 'listing_status_data');
            }

            $update = array(
                'fields' => array(
                    'Sub_status'      => $status,
                    'Sub_status_data' => $ls_status,
                ),
                'where' => array(
                    'ID' => $listing_id,
                ),
            );

            $rlDb->updateOne($update, 'listings');
        } else {
            $insertData = array(
                'Listing_ID' => $listing_id,
                'Type' => 'user',
                'Key'  => $status,
                'Date' => 'NOW()',
            );
            $rlDb->insertOne($insertData, 'listing_status_data');

            $update = array(
                'fields' => array(
                    'Sub_status'      => $status,
                    'Sub_status_data' => $status,
                ),
                'where' => array(
                    'ID' => $listing_id,
                ),
            );

            $rlDb->updateOne($update, 'listings');

            $ls_status = $status;
        }

        $updated_info['Sub_status'] = $status;
        $updated_info['Sub_status_data'] = $ls_status;

        if ($status != 'visible' && $status != 'invisible') {
            if ($listing_info['Sub_status'] == 'invisible') {
                //increase category count
                $GLOBALS['rlCategories']->listingsIncrease($listing_info['Category_ID']);
            }

            $out['message'] = str_replace('[status]', $lang['lsl_' . $status], $lang['ls_notice_all']);
        } else {
            if ($status == 'invisible') {
                //decrease category count
                $GLOBALS['rlCategories']->listingsDecrease($listing_info['Category_ID']);
                $GLOBALS['rlCategories']->accountListingsDecrease($listing_info['Account_ID']);
            } elseif ($status == 'visible' && $listing_info['Sub_status'] == 'invisible') {
                //increase category count
                $GLOBALS['rlCategories']->listingsIncrease($listing_info['Category_ID']);
                $GLOBALS['rlCategories']->accountListingsIncrease($listing_info['Account_ID']);
            }
            $out['message'] = $lang['ls_notice_' . $status];
        }

        if ($rlSmarty) {
            $rlSmarty->assign_by_ref('lb_statuses', $this->all_labels);
            $rlSmarty->assign_by_ref('listing_label', $updated_info);
            $tpl = RL_PLUGINS . 'listing_status' . RL_DS . 'label.box.tpl';
            $out['html'] = $rlSmarty->fetch($tpl, null, null, false);
        }

        $out['status'] = 'ok';

        return $out;
    }

    /**
     * ajax Change Admin Labels
     *
     * @param string  $listing_id - id listing
     * @param mixed   $status     - status listing
     *
     * @return mixed - information
     */
    public function changeAdminStatus($listing_id, $status)
    {
        global $config, $reefless, $rlDb, $lang;
        if (empty($listing_id)) {
            return false;
        }

        if (!$this->all_labels) {
            $sql = "SELECT * FROM `{db_prefix}listing_status` ";
            $sql .= "WHERE `Status` = 'active'";
            $this->all_labels = $rlDb->getAll($sql, 'Key');
        }

        $sql = "SELECT * FROM `{db_prefix}listing_status_data` WHERE `Listing_ID` = {$listing_id}";
        $status_data = $rlDb->getAll($sql, 'Key');

        // flip array keys
        if ($status) {
            $status = array_flip($status);
        }

        // create variables
        $usedLabels = [];
        $usedPosition = [];
        $usedAdminLabels = [];
        $insert = [];
        $remove_ids = "";

        // check verify and user labels
        foreach ($status_data as $key => $val) {
            if ($val['Type'] != 'admin') {
                $usedPosition[$this->all_labels[$key]['Position']] = true;
                $usedLabels[] = $key;
            }
        }

        // check admin labels
        foreach ($this->all_labels as $key => $val) {
            if ($val['Label_type'] == 'admin') {
                if (isset($status[$key])) {
                    if ($usedPosition[$this->all_labels[$key]['Position']]) {
                        $out['message'] = $lang['ls_notice_duplicate_position'];
                        $out['status']  = 'error';
                        return $out;
                    } else {
                        $usedPosition[$this->all_labels[$key]['Position']] = true;
                        $usedLabels[] = $key;
                        $usedAdminLabels[] = $lang['lsl_' . $key];

                        if (!$status_data[$key]) {
                            $insert[] = array(
                                'Listing_ID' => $listing_id,
                                'Type'       => 'admin',
                                'Key'        => $key,
                                'Date'       => 'NOW()',
                            );
                        }
                    }
                } else if ($status_data[$key]) {
                    $remove_ids = $remove_ids ? ' AND `ID` = ' . $status_data[$key]['ID'] : '`ID` = ' . $status_data[$key]['ID'];
                }
            }
        }

        // insert new admin labels
        if ($insert) {
            $rlDb->insert($insert, 'listing_status_data');
        }

        // remove admin labels
        if ($remove_ids) {
            $rlDb->query("DELETE FROM `{db_prefix}listing_status_data` WHERE " . $remove_ids);
        }

        // update the labels on the listing
        $update = array(
            'fields' => array(
                'Sub_status_data' => $usedLabels ? implode(',', $usedLabels) : '',
            ),
            'where' => array(
                'ID' => $listing_id,
            ),
        );
        $rlDb->updateOne($update, 'listings');

        $out['message'] = $lang['ls_admin_labels_updated'];
        $out['status']  = 'ok';
        $out['labels']  = $usedAdminLabels ? implode(',', $usedAdminLabels) : $lang['ls_no_labels'];

        return $out;
    }

    /**
     *
     * getMpStatus
     *
     */
    public function getMpStatus($admin = false)
    {
        global $sql, $config, $lang;

        $membership_statuses[] = array('Key' => 'visible', 'Type' => 'all', 'name' => $lang['default']);

        $data = $GLOBALS['rlDb']->fetch(array('Key', 'Type', 'Position'), array('Status' => 'active', 'Label_type' => $admin ? 'admin' : 'user'), null, null, 'listing_status');

        foreach ($data as $key => $val) {
            $membership_statuses[] = array(
                'Key'  => $val['Key'],
                'Type' => explode(',', $val['Type']),
                'name' => $lang['lsl_' . $val['Key']] ? : $GLOBALS['rlLang']->getPhrase(array('key' => 'lsl_'.$val['Key'], 'lang' => $config['lang'])),
            );
        }

        return $membership_statuses;
    }

    /**
     *
     * get status
     *
     * @param boolean $admin
     */
    public function getStatus($admin = false)
    {
        global $sql, $config, $lang;

        if (!$admin) {
            $status['visible'] = array('Key' => 'visible', 'Type' => 'all', 'name' => $lang['ls_visible']);
            $status['invisible'] = array('Key' => 'invisible', 'Type' => 'all', 'name' => $lang['ls_invisible']);
        }

        $data = $GLOBALS['rlDb']->fetch(array('Key', 'Type', 'Position'), array('Status' => 'active', 'Label_type' => $admin ? 'admin' : 'user'), null, null, 'listing_status');

        foreach ($data as $key => $val) {
            $status[$data[$key]['Key']] = array(
                'Key'      => $val['Key'],
                'Type'     => explode(',', $val['Type']),
                'Position' => $val['Position'],
                'name'     => $lang['lsl_' . $val['Key']] ? : $GLOBALS['rlLang']->getPhrase(array('key' => 'lsl_'.$val['Key'], 'lang' => $config['lang'])),
            );
        }

        return $status;
    }

    /**
     * get recently status listings
     *
     * @param string $listings_type - listing types
     * @param int    $days          - listing will be shown
     * @param int    $limit         - limit
     * @param string $s_staus       - sub status
     * @param int    $order         - order type
     *
     */
    public function getRecentlySoldListings($listings_type, $days, $limit, $s_status, $order)
    {
        $lbgKey = md5($listings_type . $days . $limit . $s_status . $order);

        return $this->lbGlobalBoxes[$lbgKey];
    }

    /**
     * Prepare sold boxes
     */
    public function prepareSoldBoxes()
    {
        global $block_keys, $blocks;

        // search labels in boxes
        foreach ($block_keys as $key => $value) {
            if ((bool) preg_match('/^lb_/', $key)) {
                // get box params
                preg_match('/getRecentlySoldListings\(\s?"([a-z0-9_,]+)",\s?"([0-9]+)",\s?"([0-9]+)",\s?"([a-z0-9_]+)",\s?"(latest|random)"/i', $blocks[$key]['Content'], $matches);
                if (count($matches) === 6) {
                    list($subject, $type, $days, $limit, $label, $order) = $matches;
                    $tmp = $this->getGlobalRecentlySoldListings($type, $days, $limit, $label, $order);
                    if (!empty($tmp)) {
                        $lbgKey = md5($type . $days . $limit . $label . $order);
                        $this->lbGlobalBoxes[$lbgKey] = $tmp;
                        unset($tmp);
                    }
                }
            }
        }
    }

    /**
     * get Global Recently Sold Listings
     *
     * @param string $listings_type - listing types
     * @param int    $days          - listing will be shown
     * @param int    $limit         - limit
     * @param string $s_staus       - sub status
     * @param int    $order         - order type
     *
     * @return array $listing
     */
    public function getGlobalRecentlySoldListings($listings_type, $days, $limit, $s_status, $order)
    {
        global $sql, $config;

        $sql = "SELECT {hook}  ";

        $sql .= "`T1`.*, `T1`.`Shows`, `T3`.`Path` AS `Path`, `T3`.`Key` AS `Key`, `T3`.`Type` AS `Listing_type`, ";

        $GLOBALS['rlHook']->load('listingsModifyField');

        $sql .= "IF(`T1`.`Featured_date`, '1', '0') `Featured` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_status_data` AS `T5` ON `T1`.`ID` = `T5`.`Listing_ID`  ";
        $sql .= "LEFT JOIN `{db_prefix}listing_status` AS `T4` ON  `T4`.`Key` = `T5`.`Key` ";


        $GLOBALS['rlHook']->load('listingsModifyJoin');

        $sql .= "WHERE if( `T4`.`Delete` != 'simple' , UNIX_TIMESTAMP(DATE_ADD(`T5`.`Date`, INTERVAL {$days} DAY)) > UNIX_TIMESTAMP(NOW()), 1) AND ";

        $sql .= "(`T3`.`Type` = '{$listings_type}' OR FIND_IN_SET( `T3`.`Type` , '{$listings_type}') > 0 ) AND ";

        $sql .= "`T1`.`Status` = 'active' AND ";
        $sql .= "`T1`.`Sub_status` <> 'invisible' AND ";
        $sql .= "`T5`.`Key` = '{$s_status}' ";

        switch ($order) {
            case 'latest':
                $sql .= "ORDER BY `T5`.`Date` DESC ";
                break;
            case 'random':
                $sql .= "ORDER BY RAND() ";
                break;
            default:
                $sql .= "ORDER BY `T1`.`ID` DESC ";
                break;
        }

        $sql .= "LIMIT {$limit} ";
        /* replace hook */
        $sql = str_replace('{hook}', $hook, $sql);

        $listings = $GLOBALS['rlDb']->getAll($sql);
        $listings = $GLOBALS['rlLang']->replaceLangKeys($listings, 'categories', 'name');

        if (empty($listings)) {
            return false;
        }

        if (!$config['cache']) {
            $fields = $GLOBALS['rlListings']->getFormFields($listings[0]['Category_ID'], 'featured_form', $listings[0]['Listing_type']);
        }

        foreach ($listings as $key => $value) {
            /* populate fields */
            if ($config['cache']) {
                $fields = $GLOBALS['rlListings']->getFormFields($value['Category_ID'], 'featured_form', $value['Listing_type']);
            }

            foreach ($fields as $fKey => $fValue) {
                $fields[$fKey]['value'] = $GLOBALS['rlCommon']->adaptValue($fValue, $value[$fKey], 'listing', $value['ID']);
            }

            $listings[$key]['fields'] = $fields;

            $listings[$key]['listing_title'] = $GLOBALS['rlListings']->getListingTitle($value['Category_ID'], $value, $value['Listing_type']);
            $listings[$key]['url'] = $GLOBALS['reefless']->getListingUrl($listings[$key]);
        }

        return $listings;
    }

    /**
     * delete listing photos
     *
     * array info = listing info
     */
    public function deleteListingPhotos($info = false)
    {
        global $rlDb;

        $allLangs = $GLOBALS['languages'];
        $sub_status = $rlDb->getOne('Sub_status', "`ID` = '{$info['ID']}'", 'listings');
        if ($sub_status != 'visible' && $sub_status != 'invisible') {
            $data_photos = $rlDb->fetch(array('Thumbnail'), array('Listing_ID' => $info['ID']), null, null, 'listing_photos');
            foreach ($data_photos as $key => $val) {
                $thum = explode('.', str_replace('/', RL_DS, $val['Thumbnail']));
                foreach ($allLangs as $lkey => $lval) {
                    $thum_label = RL_FILES . $thum[0] . '_' . $sub_status . '_' . $lval['Code'] . '.' . $thum[1];
                    unlink($thum_label);
                }
            }
        }
    }

    /**
     *  Update status
     */
    public function updateStatus()
    {
        global $rlDb;
        if (!$rlDb->getRow("SHOW FIELDS FROM `{db_prefix}listing_status` WHERE `Field` LIKE 'watermarkLarge_%'")) {
            $data = $rlDb->fetch('*', array('Status' => 'active'), null, null, 'listing_status');
            foreach ($GLOBALS['languages'] as $key => $val) {
                $fields = array();
                $fields['watermark_' . $val['Code']]      = 'VARCHAR( 50 ) NOT NULL';
                $fields['watermarkLarge_' . $val['Code']] = 'VARCHAR( 50 ) NOT NULL';
                $rlDb->addColumnsToTable($fields, 'listing_status');

                foreach ($data as $sKey => $sVal) {
                    $file_ext = array_reverse(explode('.', $sVal[$fields['watermark_' . $val['Code']]]));
                    $photo_name = $sVal['Key'] . '_large_' . $val['Code'] . '.' . $file_ext[0];

                    if (copy(RL_FILES . 'watermark' . RL_DS . $sVal[$fields['watermark_' . $val['Code']]], RL_FILES . 'watermark' . RL_DS . $photo_name)) {
                        $rlDb->query("UPDATE `{db_prefix}listing_status` SET `{$fields['watermarkLarge_' . $val['Code']]}` = '{$photo_name}' WHERE `Key` = '{$sVal['Key']}'");
                    }
                }
            }
        }
        return true;
    }

    /**
     *  Update status to version 3.0.0
     */
    public function update300()
    {
        global $rlDb;

        $this->deleteAllListingPhotosWithLabel();

        @unlink(RL_PLUGINS . 'listing_status' . RL_DS . 'status_admin_selector.tpl');

        // create table listing status data
        $raw_sql = "`ID` int(11) NOT NULL AUTO_INCREMENT,
                `Key` VARCHAR( 10 ) NOT NULL,
                `Listing_ID` int( 10 ) NOT NULL,
                `Type` ENUM( 'user', 'admin', 'verify' ) DEFAULT 'user' NOT NULL,
                `Date` DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
                PRIMARY KEY (`ID`)";
        $rlDb->createTable("listing_status_data", $raw_sql, RL_DBPREFIX, "DEFAULT CHARSET=utf8");

        // add to listings new field
        $rlDb->addColumnToTable('Sub_status_data', 'VARCHAR( 255 ) NOT NULL AFTER `Sub_status`', 'listings');
        $rlDb->query('ALTER TABLE `{db_prefix}listings` CHANGE `Sub_status` `Sub_status` VARCHAR(50) NOT NULL');

        // remove not used field
        $rlDb->dropColumnFromTable('Sold_date', 'listings');

        // add new fields
        $fields = array();
        $fields['Watermark_type'] = "ENUM( 'image', 'text' ) DEFAULT 'image' NOT NULL";
        $fields['Position']       = "INT( 2 ) DEFAULT '3' NOT NULL";
        $fields['Label_type']     = "ENUM('user', 'admin', 'verify' ) DEFAULT 'user' NOT NULL";
        $rlDb->addColumnsToTable($fields, 'listing_status');

        //update label to new system
        $sql = "SELECT `ID`,`Sub_status` FROM `{db_prefix}listings` ";
        $sql .= "WHERE `Status` = 'active' AND `Sub_status` NOT IN('" . implode("','", $this->noInType) . "') ";
        $listings = $rlDb->getAll($sql);

        // remove unnecessary phrases
        $phrases = array(
            'ls_ext_number',
            'ls_add_new_status',
            'ls_delete_listings',
            'ls_ext_add_label',
            'ls_default_status',
            'lb_build_label_photos',
            'lb_loading',
        );

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
            WHERE `Plugin` = 'listing_status' AND `Key` IN ('" . implode("','", $phrases) . "')"
        );

        $insert = [];

        if ($listings) {
            foreach ($listings as $value) {
                $insert[] = array(
                    'Listing_ID' => $value['ID'],
                    'Key'        => $value['Sub_status'],
                    'Type'       => 'user',
                );
            }

            $rlDb->query("UPDATE `{db_prefix}listings` SET `Sub_status_data` = `Sub_status` WHERE `Status` = 'active' AND `Sub_status` NOT IN('" . implode("','", $this->noInType) . "')");

            if ($insert) {
                $rlDb->insert($insert, 'listing_status_data');
            }
        }
    }

    /**
     *  Update status to version 3.0.1
     */
    public function update301()
    {
        global $rlDb;

        $rlDb->query("ALTER TABLE `{db_prefix}listings` CHANGE `Sub_status` `Sub_status` VARCHAR(50) NOT NULL DEFAULT 'visible'");
        $rlDb->query("UPDATE `{db_prefix}listings` SET `Sub_status` = 'visible' WHERE `Sub_status` = ''");
    }

    /**
     *  Update status to version 3.1.0
     */
    public function update310()
    {
        global $rlDb;

        // Remove hooks
        $hook_keys = array(
            'listingsModifyFieldByAccount',
            'listingsModifyFieldSearch',
            'myListingsSqlFields',
            'listingDetailsBottom',
            'phpResizeRefreshImage',
            'phpUploadScaledImage',
            'listingMediaCropPicturePre',
            'requestAjaxBeforeSwitchCase',
            'listingsModifyField',
            'listingsModifyFieldMyFavorite',
            'listingsModifyFieldByPeriod',
            'apAjaxRecountListings',
        );

        $rlDb->query(
            "DELETE FROM `{db_prefix}hooks`
            WHERE `Plugin` = 'listing_status' AND `Name` IN ('" . implode("','", $hook_keys) . "')"
        );

        if (in_array('ru', array_keys($GLOBALS['languages']))) {
            $russianTranslation = json_decode(file_get_contents(RL_PLUGINS . 'listing_status/i18n/ru.json'), true);

            foreach ($russianTranslation as $phraseKey => $phraseValue) {
                if (!$rlDb->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $insertPhrase = $rlDb->fetch(
                        ['Module', 'Key', 'Plugin', 'JS', 'Target_key'],
                        ['Code' => $GLOBALS['config']['lang'], 'Key' => $phraseKey],
                        null, 1, 'lang_keys', 'row'
                    );

                    $insertPhrase['Code']  = 'ru';
                    $insertPhrase['Value'] = $phraseValue;

                    $rlDb->insertOne($insertPhrase, 'lang_keys');
                } else {
                    $rlDb->updateOne([
                        'fields' => ['Value' => $phraseValue],
                        'where' => ['Key' => $phraseKey, 'Code' => 'ru', 'Modified' => '0'],
                    ], 'lang_keys');
                }
            }
        }
    }

    /**
     *  Update to version 3.1.1
     */
    public function update311()
    {
        global $rlDb;

        if ($rlDb->getRow("SHOW INDEXES FROM `{db_prefix}listings` WHERE `Key_name` = 'Category_status'")) {
            $rlDb->query("ALTER TABLE `{db_prefix}listings` DROP INDEX `Category_status`");
        }
        if (!$rlDb->getRow("SHOW INDEXES FROM `{db_prefix}listings` WHERE `Key_name` = 'Category_status_substatus'")) {
            $rlDb->query("ALTER TABLE `{db_prefix}listings` ADD INDEX `Category_status_substatus` (`Category_ID`, `Status`, `Sub_status`)");
        }
    }

    /**
     *
     * checked is image or not
     *
     */
    public function isImage($image = false)
    {
        if (!$image) {
            return false;
        }

        $allowed_types = array('image/gif', 'image/jpeg', 'image/jpg', 'image/png');
        $img_details = getimagesize($image);
        if (in_array($img_details['mime'], $allowed_types)) {
            return true;
        }

        return false;
    }

    /**
     *  Remove not supported box positions for plugin related boxes
     */
    public function rejectBoxSides()
    {
        global $l_block_sides;

        foreach ($this->rejectedBoxSides as $side) {
            unset($l_block_sides[$side]);
        }
    }

    /**
     *  Get Label for listing
     * @since 3.0.0
     */
    public function getLabel($params)
    {
        global $config, $lang, $rlLang;
        $key  = $params['key'];
        $mode = $params['mode'];

        if (!$this->all_labels) {
            $sql = "SELECT * FROM `{db_prefix}listing_status` ";
            $sql .= "WHERE `Status` = 'active'";
            $this->all_labels = $GLOBALS['rlDb']->getAll($sql, 'Key');
        }

        $data = $this->all_labels[$key];

        $label = [];
        $label['name'] = $lang['lsl_'.$key] ?: $rlLang->getPhrase(array('key' => 'lsl_'.$key, 'lang' => $config['lang']));

        if ($data['Watermark_type'] == 'image') {
            $img_name = $mode ? 'watermarkLarge_' . RL_LANG_CODE : 'watermark_' . RL_LANG_CODE;
            if (!$data[$img_name] && $config['lang'] != RL_LANG_CODE) {
                $img_name = $mode ? 'watermarkLarge_' . $config['lang'] : 'watermark_' . $config['lang'];
            }

            $image_path = RL_FILES . 'watermark' . RL_DS . $data[$img_name];

            if (file_exists($image_path)) {
                $label['img'] = $data[$img_name];
            }
        }

        if ($params['assign'] && is_object($GLOBALS['rlSmarty'])) {
            $GLOBALS['rlSmarty']->assign($params['assign'], $label);
        }
    }

    /**
     * Generate label key
     * @since 3.1.0
     */
    public function generateKey($id = 0)
    {
        global $rlDb;
        if (!$id) {
            $id = $rlDb->getRow("SELECT MAX(`ID`) FROM `{db_prefix}listing_status`", 'MAX(`ID`)');
        }
        $id++;

        $f_key = 'label_' . $id;
        if ($rlDb->getOne('ID', "`Label_type` = '{$f_key}'", 'listing_status')) {
            return $this->generateKey($id);
        }
        return $f_key;
    }

    /**
     * Delete listing
     *
     * @since 3.1.0
     *
     * @param int  $id
     * @param int  $accountID
     * @param bool $recountCategories
     */
    public function deleteListing($id, $accountID = 0, $recountCategories = true)
    {
        global $rlDb;

        $id = (int) $id;

        $sql = "SELECT `T1`.`ID`, `T1`.`Category_ID`, `T2`.`Type`, `T1`.`Crossed`, `T1`.`Status`, `T2`.`Type` AS `Listing_type`, `T1`.`Plan_ID`, `T1`.`Account_ID`, `T1`.`Plan_type`, ";
        $sql .= "`T1`.`Featured_ID`, `T1`.`Featured_date` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = '{$id}' AND `T1`.`Status` <> 'trash'";

        if ($accountID) {
            //additional check for listing owner (front-end only)
            $sql .= "AND `T1`.`Account_ID` = '{$accountID}' ";
        }

        $info = $rlDb->getRow($sql);

        if (empty($info)) {
            return false;
        }

        $GLOBALS['rlHook']->load('phpListingsAjaxDeleteListing', $info); // >= 4.4 , 4.5 > name is the same but the function is not ajax now.

        $this->deleteListingData($info['ID'], $info['Category_ID'], $info['Crossed'], $info['Listing_type'], $recountCategories);
        $GLOBALS['rlActions']->delete(array('ID' => $info['ID']), 'listings', $info['ID'], 1, $info['ID']);

        if (version_compare($GLOBALS['config']['rl_version'], '4.8.2') >= 0) {
            $GLOBALS['rlCache']->updateStatistics($info['Listing_type']);
        }

        // handle membership plan
        if ($info['Plan_type'] == 'account') {
            $account_info = $rlDb->fetch('*', array('ID' => $info['Account_ID']), null, null, 'accounts', 'row');
            $membership_plan = $rlDb->fetch('*', array('ID' => $account_info['Plan_ID']), null, null, 'membership_plans', 'row');
            $plan_using = $rlDb->fetch('*', array('Account_ID' => $info['Account_ID'], 'Plan_ID' => $info['Plan_ID'], 'Type' => 'account'), null, null, 'listing_packages', 'row');

            if ($plan_using) {
                $update = array(
                    'fields' => array(
                        'Listings_remains' => $membership_plan['Listing_number'] > $plan_using['Listings_remains'] ? $plan_using['Listings_remains'] + 1 : $membership_plan['Listing_number'],
                    ),
                    'where'  => array('ID' => $plan_using['ID']),
                );

                if ($membership_plan['Advanced_mode']) {
                    if ($info['Featured_ID']) {
                        $update['fields']['Featured_remains'] = $membership_plan['Featured_listings'] > $plan_using['Featured_remains'] ? $plan_using['Featured_remains'] + 1 : $membership_plan['Featured_listings'];
                    } else {
                        $update['fields']['Standard_remains'] = $membership_plan['Standard_listings'] > $plan_using['Standard_remains'] ? $plan_using['Standard_remains'] + 1 : $membership_plan['Standard_listings'];
                    }
                }

                if ($rlDb->updateOne($update, 'listing_packages')) {
                    $_SESSION['account']['plan']['Listings_remains'] = $update['fields']['Listings_remains'];
                    $_SESSION['account']['plan']['Standard_remains'] = $update['fields']['Standard_remains'];
                    $_SESSION['account']['plan']['Featured_remains'] = $update['fields']['Featured_remains'];
                }
            }
        }

        $GLOBALS['rlHook']->load('phpAfterDeleteListing', $info);

        return true;
    }

    /**
     * Delete all listing data
     * @since 3.1.0
     *
     * @param  int    $id
     * @param  int    $category_id
     * @param  array  $crossed      - Crossed category IDs
     * @param  string $type         - Listing type key
     * @param  bool   $recount_cats - Update recount of categories
     * @return bool
     */
    public function deleteListingData($id = 0, $category_id = 0, $crossed = array(), $type = '', $recount_cats = true)
    {
        global $config, $rlAccount, $rlDb, $reefless, $rlCategories, $rlListings;

        $id = (int) $id;

        if (!$id) {
            return false;
        }

        // decrease category listing
        if ($category_id && $rlListings->isActive($id) && $recount_cats) {
            $reefless->loadClass('Categories');

            $rlCategories->listingsDecrease($category_id, $type);
            $rlCategories->accountListingsDecrease($rlDb->getOne('Account_ID', "`ID` = {$id}", 'listings'));

            // crossed listings count control
            if ($crossed) {
                $crossed = explode(',', $crossed);
                foreach ($crossed as $crossed_id) {
                    $rlCategories->listingsDecrease($crossed_id);
                }
            }
        }

        $reefless->loadClass('Account');

        if ($config['trash']) {
            // if trash is enabled return after count changes
            return true;
        }

        $rlDb->delete(array('Listing_ID' => $id), 'listings_shows', null, 0);
        $rlDb->delete(array('Listing_ID' => $id), 'favorites', null, 0);
        $rlDb->delete(array('Listing_ID' => $id), 'tmp_categories', null, 0);

        $mediaPath = $rlDb->getOne('Original', "`Listing_ID` = {$id} AND `Original` != 'youtube'", 'listing_photos');

        if ($mediaPath) {
            ListingMedia::removeEmptyDir(RL_FILES . dirname($mediaPath), true);
            $rlDb->delete(array('Listing_ID' => $id), 'listing_photos', null, 0);
        }

        // delete files of listing fields with "image" or "file" types
        $file_fields = $rlDb->getAll(
            "SELECT `Key` FROM `{db_prefix}listing_fields` WHERE `Type` = 'image' OR `Type` = 'file' ",
            array(false, 'Key')
        );

        if ($file_fields) {
            $listing_info = $rlDb->fetch($file_fields, array('ID' => $id), null, null, 'listings', 'row');

            foreach ($listing_info as $listing_file) {
                unlink(RL_FILES . $listing_file);
            }
        }

        /**
         * @since 4.5.0
         */
        $GLOBALS['rlHook']->load('phpDeleteListingData', $id, $category_id, $crossed, $type);

        return true;
    }

    /** Hooks **/

    /**
     * @hook  init
     * @since 3.0.0
     */
    public function hookInit()
    {
        if ($_SESSION['lb_sold_approval'] == 'approval') {
             $updateblock = array(
                'fields' => array('Status' => 'approval'),
                'where'  => array('Key' => 'lb_sold'),
            );
            $GLOBALS['rlDb']->updateOne($updateblock, 'blocks');
            unset($_SESSION['lb_sold_approval']);
        }

        if (!defined('AJAX_FILE') ||
            (defined('AJAX_FILE') &&
            ($_REQUEST['mode']==='novaLoadMoreListings' || $_REQUEST['item']==='listing_carousel' ))
        ) {

            $sql = "SELECT * FROM `{db_prefix}listing_status` WHERE `Status` = 'active'";
            $this->all_labels = $lb_statuses = $GLOBALS['rlDb']->getAll($sql, 'Key');

            if ($lb_statuses) {
                $GLOBALS['rlSmarty']->assign_by_ref('lb_statuses', $lb_statuses);
            }
        }
    }

    /**
     * @hook  phpRegisterFunctions
     * @since 3.0.0
     */
    public function hookPhpRegisterFunctions($param1)
    {
        $param1->register_function('getLabel', array($this, 'getLabel'));
    }

    /**
     * @hook  specialBlock
     * @since 2.4.0
     */
    public function hookSpecialBlock()
    {
        global $page_info, $blocks;

        if ($page_info['Controller'] == 'my_listings') {
            $status = $this->getStatus();
            $GLOBALS['rlSmarty']->assign_by_ref('status', $status);

            if ($GLOBALS['config']['membership_module']) {
                $membership_statuses = $this->getMpStatus();
                $GLOBALS['rlSmarty']->assign_by_ref('membership_statuses', $membership_statuses);
            }
        }
        $this->prepareSoldBoxes();

        $listing_status = $GLOBALS['rlDb']->fetch('*', array('Status' => 'active'), null, null, 'listing_status');

        //add css style for carousel boxes
        foreach ($blocks as $key => $val) {
            if ($val['Plugin'] == 'listings_carousel') {
                $GLOBALS['rlStatic']->addHeaderCSS(RL_PLUGINS_URL . 'listing_status/static/style.css');
                break;
            }
        }
    }

    /**
     * @hook  tplHeader
     * @since 3.1.0
     */
    public function hookTplHeader()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'listing_status' . RL_DS . 'header.tpl');
    }

    /**
     * @hook  staticDataRegister
     * @since 2.4.0
     */
    public function hookStaticDataRegister()
    {
        global $rlStatic, $tpl_settings, $page_info;


        $pageKeys = $pageKeysCss = [];
        if ($GLOBALS['config']['one_my_listings_page']) {
            $pageKeys[] = $pageKeysCss[] = 'my_all_ads';
        }
        else {
            foreach ($GLOBALS['rlListingTypes']->types as $lt_key => $ltype) {
                $pageKeys[] = $pageKeysCss[] = 'my_' . $ltype['Key'];
            }
        }

        if ($page_info['Controller']=='home' && (strpos($tpl_settings['name'], '_nova') !== false || $tpl_settings['name'] == 'realty_map')) {
            $pageKeysCss[] = 'home';
        }
        $pageKeysCss[] = 'search_on_map';

        $rlStatic->addFooterCSS(RL_PLUGINS_URL . 'listing_status/static/style.css', $pageKeysCss, true);
        $rlStatic->addJS(RL_PLUGINS_URL . 'listing_status/static/lib.js', $pageKeys, true);
    }

    /**
     * @hook  listingsModifyWhere
     * @since 2.4.0
     */
    public function hookListingsModifyWhere()
    {
        global $sql;
        $sql .= "AND `T1`.`Sub_status` <> 'invisible' ";
    }

    /**
     * @hook  listingsModifyWhereMyFavorite
     * @since 2.4.0
     */
    public function hookListingsModifyWhereMyFavorite()
    {
        global $sql;
        $sql .= "AND `T1`.`Sub_status` <> 'invisible' ";
    }

    /**
     * @hook  listingsModifyWhereByPeriod
     * @since 2.4.0
     */
    public function hookListingsModifyWhereByPeriod(&$sql)
    {
        $sql .= "AND `T1`.`Sub_status` <> 'invisible' ";
    }

    /**
     * @hook  listingsModifyWhereByAccount
     * @since 2.4.0
     */
    public function hookListingsModifyWhereByAccount()
    {
        global $sql;
        $sql .= "AND `T1`.`Sub_status` <> 'invisible' ";
    }

    /**
     * @hook  listingsModifyWhereSearch
     * @since 2.4.0
     */
    public function hookListingsModifyWhereSearch(&$sql)
    {
        $sql .= "AND `T1`.`Sub_status` <> 'invisible' ";
    }

    /**
     * @hook  phpListingsAjaxDeleteListing
     * @since 2.4.0
     */
    public function hookPhpListingsAjaxDeleteListing($info)
    {
        $this->deleteListingPhotos($info);
    }

    /**
     * @hook  cronAdditional
     * @since 2.4.0
     */
    public function hookCronAdditional()
    {
        global $rlDb, $rlCategories;

        $this->pluginLabel = true;

        $sql = "SELECT `T3`.`ID`,  `T3`.`Account_ID`, `T3`.`Crossed`, `T3`.`Category_ID`, `T2`.`Delete`";
        $sql .= "FROM `{db_prefix}listing_status_data` AS `T1`";
        $sql .= "LEFT JOIN `{db_prefix}listing_status` AS `T2` ON `T1`.`Key` = `T2`.`Key` ";
        $sql .= "LEFT JOIN `{db_prefix}listings` AS `T3` ON `T1`.`Listing_ID` = `T3`.`ID` ";

        $sql .= "WHERE `T3`.`Status` = 'active' AND `T2`.`Delete` != 'simple' ";
        $sql .= "AND `T1`.`Key` NOT IN('" . implode("','", $this->noInType) . "') ";
        $sql .= "AND UNIX_TIMESTAMP(DATE_ADD(`T1`.`Date`, INTERVAL `T2`.`Days` DAY)) < UNIX_TIMESTAMP(NOW()) AND `T2`.`Days` != 0";

        $listings = $rlDb->getAll($sql);

        if ($listings) {
            foreach ($listings as $key => $listing) {
                if ($listing['Delete'] == 'delete') {
                    if (version_compare($GLOBALS['config']['rl_version'], '4.9.1') > 0) {
                        $GLOBALS['rlListings']->deleteListing($listing['ID'], $listing['Account_ID']);
                    }
                    else {
                        // TO DO - remove when when version will be more 4.9.1
                        $this->deleteListing($listing['ID'], $listing['Account_ID']);
                    }
                } elseif ($listing['Delete'] == 'disabled') {
                    $update = array(
                        'fields' => ['Status' => 'approval'],
                        'where'  => ['ID' => $listing['ID']],
                    );

                    $rlDb->updateOne($update, 'listings');

                    $rlCategories->listingsDecrease($listing['Category_ID']);
                    $rlCategories->accountListingsDecrease($listing['Account_ID']);
                    if (!empty($listing['Crossed'])) {
                        $crossed_cats = explode(',', trim($listing['Crossed'], ','));
                        foreach ($crossed_cats as $crossed_cat_id) {
                            $rlCategories->listingsDecrease($crossed_cat_id);
                        }
                    }
                }
            }
        }
    }

    /**
     * @hook  phpDeleteListingDataBefore
     * @since 3.1.0
     *
     * @param  int    $id
     * @param  int    $category_id
     * @param  array  $crossed         - Crossed category IDs
     * @param  string $type            - Listing type key
     * @param  bool   $saveListingData - Save listing date
     */
    public function hookPhpDeleteListingDataBefore($id, $category_id, $crossed, $type, &$saveListingData)
    {
        if (defined('CRON_FILE') && $GLOBALS['config']['trash'] && $this->pluginLabel) {
            $saveListingData = true;
        }
    }

    /**
     * @hook  apTplListingsFormEdit
     * @since 2.4.0
     */
    public function hookApTplListingsFormEdit()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'listing_status' . RL_DS . 'admin' . RL_DS . 'status_admin_selector.tpl');
    }

    /**
     * @hook  apPhpListingsBottom
     * @since 2.4.0
     */
    public function hookApPhpListingsBottom()
    {
        global $listing, $lang;
        if ($_GET['action'] == 'edit') {
            $status_admin = $this->getStatus(true);
            $GLOBALS['rlSmarty']->assign_by_ref('status_admin', $status_admin);
            if ($listing['Sub_status_data']) {
                foreach (explode(',', $listing['Sub_status_data']) as $item) {
                    if ($status_admin[$item]) {
                        $listing['Sub_status_data_val'][] = $item;
                        $listing['Sub_status_data_name'] .= $listing['Sub_status_data_name'] ? ',' . $lang['lsl_' . $item] : $lang['lsl_' . $item];
                    }
                }
            }

            $status = $this->getStatus(false);
            $GLOBALS['rlSmarty']->assign_by_ref('status', $status);

            if ($GLOBALS['config']['membership_module']) {
                $membership_statuses = $this->getMpStatus(false);
                $GLOBALS['rlSmarty']->assign_by_ref('membership_statuses', $membership_statuses);
            }

            $listing_type = $listing['Listing_type'];
            $listing_type = $GLOBALS['rlListingTypes']->types[$listing_type];
            $GLOBALS['rlSmarty']->assign_by_ref('listing_type', $listing_type);
        }
    }

    /**
     * @hook  listingsModifyWhereFeatured
     * @since 2.4.0
     */
    public function hookListingsModifyWhereFeatured(&$param1)
    {
        $param1 .= "AND `T1`.`Sub_status` <> 'invisible' ";
    }

    /**
     * @hook  listingDetailsSql
     * @since 2.4.0
     */
    public function hookListingDetailsSql(&$sql)
    {
        if (!$this->allowInvisibleListingDetailsAccess) {
            $sql .= "AND `T1`.`Sub_status` <> 'invisible' ";
        }
    }

    /**
     * @hook  sitemapGetListingsWhere
     * @since 2.4.0
     */
    public function hookSitemapGetListingsWhere(&$sql)
    {
        $additionalSQL = "AND `T1`.`Sub_status` <> 'invisible' ";

        if ($sql) {
            $sql .= $additionalSQL;
        } else if ($GLOBALS['sql']) {
            $GLOBALS['sql'] .= $additionalSQL;
        }

    }

    /**
     * @hook  myListingsIconTop
     * @since 2.4.0
     */
    public function hookMyListingsIconTop()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'listing_status' . RL_DS . 'status_selector.tpl');
    }

    /**
     * @hook  apTplControlsForm
     * @since 2.4.0
     */
    public function hookApTplControlsForm()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'listing_status' . RL_DS . 'admin' . RL_DS . 'refresh_labels.tpl');
    }

    /**
     * @hook  ajaxRequest
     * @since 2.4.0
     */
    public function hookAjaxRequest(&$out, $request_mode, $request_item, $request_lang)
    {

        switch ($request_mode) {
            // change status
            case 'listing_status':
                $listing_id = (int) $_REQUEST['id'];
                $sub_status = $GLOBALS['rlValid']->xSql($_REQUEST['status']);
                $out = $this->changeStatus($listing_id, $sub_status);
                break;
        }
    }

    /**
     * @hook  apAjaxRequest
     * @since 2.4.0
     */
    public function hookApAjaxRequest(&$out, $request_mode)
    {
        switch ($request_mode) {
            // change status
            case 'listing_status':
                $listing_id = (int) $_REQUEST['id'];
                $sub_status = $GLOBALS['rlValid']->xSql($_REQUEST['status']);
                $out = $this->changeStatus($listing_id, $sub_status);
                break;

            // change status
            case 'listing_admin_status':
                $listing_id = (int) $_REQUEST['id'];
                $sub_status = $GLOBALS['rlValid']->xSql($_REQUEST['status']);
                $out = $this->changeAdminStatus($listing_id, $sub_status);
                break;

            // change Sub Status
            case 'changeSubStatus':
                $id = (int) $_REQUEST['id'];
                $sub_status = $GLOBALS['rlValid']->xSql($_REQUEST['status']);
                $out = $this->changeSubStatus($id, $sub_status);
                break;

            // remove Sub Status
            case 'deleteListingsLabel':
                $id = (int) $_REQUEST['id'];
                $out = $this->deleteStatusBlock($id);
                break;

            // remove wartermark
            case 'deleteWatermark':
                $key = $GLOBALS['rlValid']->xSql($_REQUEST['key']);
                $code = $GLOBALS['rlValid']->xSql($_REQUEST['code']);
                $watermark = $GLOBALS['rlValid']->xSql($_REQUEST['watermark']);
                $large = $GLOBALS['rlValid']->xSql($_REQUEST['large']);

                $out = $this->deleteWatermark($key, $code, $watermark, $large);
                break;

            // refresh labels and photos with label
            case 'refreshLabel':
                $stack = (int) $_REQUEST['stack'];
                $out = $this->refreshLabel($stack);
                break;
        }
    }

    /**
     * @hook  tplListingItemPhoto
     * @since 3.0.0
     */
    public function hookTplListingItemPhoto()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'listing_status' . RL_DS . 'label.box.tpl');
    }

    /**
     * @hook  tplFeaturedItemPhoto
     * @since 3.0.0
     */
    public function hookTplFeaturedItemPhoto()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'listing_status' . RL_DS . 'label.box.tpl');
    }

    /**
     * @hook  tplListingDetailsPhotoPreview
     * @since 3.0.0
     */
    public function hookTplListingDetailsPhotoPreview()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'listing_status' . RL_DS . 'label.box.tpl');
    }

    /**
     * @hook  tplListingDetailsGalleryPreview
     * @since 3.0.0
     */
    public function hookTplListingDetailsGalleryPreview()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'listing_status' . RL_DS . 'label.box.tpl');
    }

    /**
     * @hook  tplMyListingItemPhoto
     * @since 3.0.0
     */
    public function hookTplMyListingItemPhoto()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'listing_status' . RL_DS . 'label.box.tpl');
    }

    /**
     * @hook  phpPrepareListingsData
     * @since 3.0.0
     * @param array  $param1 - listing data
     * @param array  $param2 - prepare listing data
     * @param string $param3 - data to that parameter should be assigned using string concatenation
     */
    public function hookPhpPrepareListingsData($param1, $param2, &$param3)
    {
        global $rlSmarty;

        if (!$this->all_labels) {
            $sql = "SELECT * FROM `{db_prefix}listing_status` ";
            $sql .= "WHERE `Status` = 'active'";
            $this->all_labels = $GLOBALS['rlDb']->getAll($sql, 'Key');
        }

        $rlSmarty->assign_by_ref('lb_statuses', $this->all_labels);
        $rlSmarty->assign_by_ref('listing', $param1);

        $tpl = RL_PLUGINS . 'listing_status' . RL_DS . 'label.box.tpl';
        $param3 .= $rlSmarty->fetch($tpl, null, null, false);
    }

    /**
     *  Define plugin related boxes and remove not supported box positions
     *  in edit box mode
     *
     *  @hook apPhpBlocksPost
     */
    public function hookApPhpBlocksPost()
    {
        global $block_info;
        if ($block_info['Plugin'] != 'listing_status') {
            return;
        }

        $this->rejectBoxSides();
    }

    /**
     *  Ebable the verification by photo
     *
     *  @hook apPhpConfigAfterUpdate
     */
    public function hookApPhpConfigAfterUpdate()
    {
        global $dConfig, $rlDb;

        if ($dConfig['ls_verify_module']['value'] == 1 && !$rlDb->getOne('ID', "`Label_type` = 'verify'", 'listing_status')) {
            $this->installVerification();
        } else if ($dConfig['ls_verify_module']['value'] != 1 && $rlDb->getOne('ID', "`Key` = 'verified'", 'listing_status')) {
            $this->uninstallVerification();
        }
    }

    /**
     *  Remove verify label and send pending
     *
     * @since 2.4.0
     *
     *  @hook phpUploadBeforeSaveData
     */
    public function hookPhpUploadBeforeSaveData($param1)
    {
        global $rlDb;

        $listing_id = $param1['Listing_ID'];
        if ($listing_id && $rlDb->getOne('Key', "`Listing_ID` = '{$listing_id}' AND `Key` = 'verified' AND `Type` = 'verify'", 'listing_status_data')) {

            // get listing information
            $sql = "SELECT `T1`.*, `T2`.`Type` AS `Listing_type` ";
            $sql .= "FROM `{db_prefix}listings` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
            $sql .= "WHERE `T1`.`ID` = '{$listing_id}' LIMIT 1";
            $listing = $rlDb->getRow($sql);

            // update label info
            $updateVerify = array(
                'fields' => array('Key' => 'wait'),
                'where'  => array('Listing_ID' => $listing_id, 'Type' => 'verify'),
            );
            $rlDb->updateOne($updateVerify, 'listing_status_data');

            // update label info
            $update = array(
                'fields' => array(
                    'Sub_status_data' => str_replace('verified', 'wait', $listing['Sub_status_data']),
                ),
                'where' => array(
                    'ID' => $listing_id,
                ),
            );
            $rlDb->updateOne($update, 'listings');

            $item_id = $rlDb->getOne('ID', "`Listing_ID` = '{$listing_id}}'", 'listing_status_verified');

            // send notify to admin
            $GLOBALS['reefless']->loadClass('Mail');

            // send admin notification
            $mailTpl = $GLOBALS['rlMail']->getEmailTemplate('verified_photos_resend_request_admin');

            $link = RL_URL_HOME . ADMIN . '/index.php?controller=listing_status&amp;module=verify_photos&amp;action=view&amp;id=' . $item_id;

            $mFind = array('{listing_id}', '{title}');
            $mReplace = array(
                $listing_id,
                $GLOBALS['rlListings']->getListingTitle($listing['Category_ID'], $listing, $listing['Listing_type'])
            );
            $mailTpl['body'] = str_replace($mFind, $mReplace, $mailTpl['body']);
            $mailTpl['body'] = preg_replace('/\[(.*)\]/', '<a href="' . $link . '">$1</a>', $mailTpl['body']);
            $GLOBALS['rlMail']->send($mailTpl, $GLOBALS['config']['notifications_email']);
        }
    }

    /**
     * Add link for verification by the photo on step done on the add listing
     *
     * @since 3.0.0
     *
     * @hook addListingStepActionsTpl
     */
    public function hookAddListingStepActionsTpl()
    {
        global $rlSmarty;

        if ($GLOBALS['addListing']->step == 'done' && $GLOBALS['config']['ls_verify_module'] && $GLOBALS['addListing']->listingData['Main_photo']) {
            $listingType = $GLOBALS['addListing']->listingType['Key'];
            if ($GLOBALS['rlDb']->getOne('ID', "`Key` = 'verified' AND (`Type` = '{$listingType}' OR FIND_IN_SET('{$listingType}', `Type`) > 0)", 'listing_status')) {
                $rlSmarty->display(RL_PLUGINS . 'listing_status' . RL_DS . 'step.done.tpl');
            }
        }
    }

    /**
     * Rebuild label after import language
     *
     * @since 3.0.0
     *
     * @hook apPhpLanguageAfterImport
     */
    public function hookApPhpLanguageAfterImport(&$langCode)
    {
        $this->addLabelFields($langCode);
    }

    /**
     * Rebuild label after add language
     *
     * @since 3.0.0
     *
     * @hook apPhpAfterAddLanguage
     */
    public function hookApPhpAfterAddLanguage(&$langKey, &$isoCode, &$direction, &$locale, &$dateFormat)
    {
        $this->addLabelFields($isoCode);
    }

    /**
     * Rebuild label after remove language
     *
     * @since 3.0.0
     *
     * @hook apPhpAfterDeleteLanguage
     */
    public function hookApPhpAfterDeleteLanguage(&$code)
    {
        $fields = ['watermark_'.$code, 'watermarkLarge_'.$code];
        $GLOBALS['rlDb']->dropColumnsFromTable($fields, 'listing_status');
    }

    /**
     * Rebuild label after change default language
     *
     * @since 3.0.0
     *
     * @hook apPhpAfterChangeDefaultLanguage
     */
    public function hookApPhpAfterChangeDefaultLanguage($old_lang, $new_lang)
    {
        global $rlLang, $rlDb, $rlLang;

        if (!$this->all_labels) {
            $sql = "SELECT * FROM `{db_prefix}listing_status` ";
            $sql .= "WHERE `Status` = 'active'";
            $this->all_labels = $rlDb->getAll($sql, 'Key');
        }

        $createPhrases = [];
        $update_labels = [];

        foreach ($this->all_labels as $key => $label) {
            if (!$rlLang->getPhrase(array('key' => 'lsl_'.$label['Key'], 'lang' => $new_lang, 'db_check' => true))) {
                $createPhrases[] = array(
                    'Code'   => $new_lang,
                    'Module' => 'common',
                    'Status' => 'active',
                    'Key'    => 'lsl_'.$label['Key'],
                    'Value'  => $rlLang->getPhrase(array('key' => 'lsl_'.$label['Key'], 'lang' => $old_lang, 'db_check' => true)),
                    'Plugin' => 'listing_status',
                );
            }

            $update_label = [];

            if (!$label['watermark_'.$new_lang]) {
                $old_name = $label['watermark_'.$old_lang];
                $new_name = str_replace($old_lang, $new_lang, $old_name);

                $old_file = RL_FILES . 'watermark' . RL_DS . $old_name;
                $new_file = RL_FILES . 'watermark' . RL_DS . $new_name;
                if (copy($old_file, $new_file)) {
                    $update_label['fields']['watermark_'.$new_lang] = $new_name;
                }
            }

            if (!$label['watermarkLarge_'.$new_lang]) {
                $old_name = $label['watermarkLarge_'.$old_lang];
                $new_name = str_replace($old_lang, $new_lang, $old_name);

                $old_file = RL_FILES . 'watermark' . RL_DS . $old_name;
                $new_file = RL_FILES . 'watermark' . RL_DS . $new_name;
                if (copy($old_file, $new_file)) {
                    $update_label['fields']['watermarkLarge_'.$new_lang] = $new_name;
                }
            }

            if ($update_label) {
                $update_label['where']['Key'] = $label['Key'];
                $update_labels[] = $update_label;
            }

        }

        if ($createPhrases) {
            if (method_exists($rlLang, 'createPhrases')) {
                $rlLang->createPhrases($createPhrases);
            } else {
                $rlDb->insert($createPhrases, 'lang_keys');
            }
        }
        if ($update_labels) {
            $rlDb->update($update_labels, 'listing_status');
        }
    }

    /**
     * Rebuild label when import, add or remove language
     *
     * @since 3.0.0
     *
     * @hook apPhpIndexBottom
     */
    public function hookApPhpIndexBottom()
    {
        if ($GLOBALS['cInfo']['Controller'] == 'languages') {

            // remove language
            if ($_POST['xjxfun'] == 'ajaxDeleteLang' && $_POST['xjxargs'][0]) {
                $id   = (int) $_POST['xjxargs'][0];
                $code = $GLOBALS['rlDb']->getOne('Code', "`ID` = '{$id}'", 'languages');
                $fields = ['watermark_'.$code, 'watermarkLarge_'.$code];
                $GLOBALS['rlDb']->dropColumnsFromTable($fields, 'listing_status');
            }
            // add  language
            elseif ($_POST['xjxfun'] == 'ajax_addLanguage' && $_POST['xjxargs'][0]) {
                foreach ($GLOBALS['rlXajax']->objArgumentManager->aArgs[0] as $param) {
                    if ($param[0] == 'iso_code') {
                        $this->addLabelFields($param[1]);
                        break;
                    }
                }
            }
            //  import language
            else if ($_SESSION['ls_after_add_import_lang_trigger']) {
                $this->rebuild();

                unset($_SESSION['ls_after_add_import_lang_trigger']);
                return;
            }

            if ($_POST['import']) {
                $_SESSION['ls_after_add_import_lang_trigger'] = true;
            }
        }

        if ($_SESSION['lb_sold_approval'] == 'approval') {
             $updateblock = array(
                'fields' => array('Status' => 'approval'),
                'where'  => array('Key' => 'lb_sold'),
            );
            $GLOBALS['rlDb']->updateOne($updateblock, 'blocks');
            unset($_SESSION['lb_sold_approval']);
        }
    }

    /**
     * Remove "integrated_banner" and "header_banner" box positions from the
     * grid cell for plugin rows
     *
     * @hook apTplBlocksBottom
     */
    public function hookApTplBlocksBottom()
    {
        $out = <<< JAVASCRIPT
        $(function(){
            blocksGrid.grid.addListener('beforeedit', function(editEvent){
                if (editEvent.field == 'Side') {
                    var column = editEvent.grid.colModel.columns[2];
                    var removed = false;

                    if (editEvent.record.data.Key.indexOf('lb_') === 0) {
                        var items = column.editor.getStore().data.items;
                        var items_ids = [];
                        for (var i = 0; i < items.length; i++) {
                            if (['integrated_banner', 'header_banner'].indexOf(items[i].data.field1) >= 0) {
                                items_ids.push(i);
                            }
                        }

                        if (items_ids.length) {
                            for (var i in items_ids.reverse()) {
                                column.editor.getStore().removeAt(items_ids[i])
                            }

                            removed = true;
                        }
                    } else {
                        if (removed) {
                            column.editor = new Ext.form.ComboBox({
                                store: block_sides,
                                displayField: 'value',
                                valueField: 'key',
                                typeAhead: true,
                                mode: 'local',
                                triggerAction: 'all',
                                selectOnFocus: true
                            });
                            removed = false;
                        }
                    }
                }
            });
        });
JAVASCRIPT;

        echo "<script>{$out}</script>";
    }

    // Deprecated hooks and methods
    /**
     * @hook  listingsModifyFieldByAccount
     *
     * @since 2.4.0
     *
     * @deprecated 3.0.0
     */
    public function hookListingsModifyFieldByAccount()
    {}

    /**
     * @hook  listingsModifyFieldSearch
     *
     * @since 2.4.0
     *
     * @deprecated 3.0.0
     */
    public function hookListingsModifyFieldSearch(&$sql)
    {}

    /**
     * @hook  myListingsSqlFields
     *
     * @since 2.4.0
     *
     * @deprecated 3.0.0
     */
    public function hookMyListingsSqlFields(&$sql)
    {}

    /**
     * @hook  listingDetailsBottom
     *
     * @since 2.4.0
     *
     * @deprecated 3.0.0
     */
    public function hookListingDetailsBottom()
    {}

    /**
     * @hook  phpResizeRefreshImage
     *
     * @since 2.4.0
     *
     * @deprecated 3.0.0
     */
    public function hookPhpResizeRefreshImage($file_name, $mode, $mt, $original, $listing_id)
    {}

    /**
     * @hook  phpUploadScaledImage
     *
     * @since 2.4.0
     *
     * @deprecated 3.0.0
     */
    public function hookPhpUploadScaledImage($file_name = false, $new_file = false, $dir_name = false, $options = false, $mode = false)
    {}

    /**
     * @hook  listingMediaCropPicturePre
     *
     * @since 2.4.0
     *
     * @deprecated 3.0.0
     */
    public function hookListingMediaCropPicturePre($listing_id, $item, $original, $data, $account_info)
    {}

    /**
     * @hook  requestAjaxBeforeSwitchCase
     *
     * @since 2.4.0
     *
     * @deprecated 3.0.0
     */
    public function hookRequestAjaxBeforeSwitchCase($param1, $param2, $param3)
    {}

    /**
     * @hook  listingsModifyField
     *
     * @since 2.4.0
     *
     * @deprecated 3.0.0
     */
    public function hookListingsModifyField()
    {}

    /**
     * @hook  listingsModifyFieldMyFavorite
     *
     * @since 2.4.0
     *
     * @deprecated 3.0.0
     */
    public function hookListingsModifyFieldMyFavorite()
    {}

    /**
     * @hook  listingsModifyFieldByPeriod
     *
     * @since 2.4.0
     *
     * @deprecated 3.0.0
     */
    public function hookListingsModifyFieldByPeriod(&$sql)
    {}

    /**
     * replace photos
     *
     * @param array $photos       - listing photos
     * @param array $listing_data - listing info
     *
     * @deprecated 3.0.0
     */
    public function replacePhotos($photos, $listing_info)
    {}

    /**
     * @hook  apAjaxRecountListings
     * @since 2.4.0
     * @deprecated 3.1.0
     */
    public function hookApAjaxRecountListings(&$param1)
    {}
}
