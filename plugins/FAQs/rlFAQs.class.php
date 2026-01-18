<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLFAQS.CLASS.PHP
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

class rlFAQs {

    /**
     * @var calculate faqs
     **/
    public $calc_faqs;

    /**
     * Install process
     *
     * @since 1.1.2
     */
    public function install()
    {
        $raw_sql = "`ID` int(11) NOT NULL auto_increment,
          `Date` datetime NOT NULL default '0000-00-00 00:00:00',
          `Path` varchar(255) NOT NULL default '0',
          `Position` INT NOT NULL,
          `Status` enum('active','approval','trash') NOT NULL default 'active',
           PRIMARY KEY (`ID`),
           KEY `Path` (`Path`)";

        $GLOBALS['rlDb']->createTable('faqs', $raw_sql, RL_DBPREFIX, "ENGINE=InnoDB CHARSET=utf8 COLLATE=utf8_general_ci");
    }

    /**
     * Plugin un-installer
     * @since 1.1.2
     **/
    public function uninstall() {
        // DROP TABLE
        $GLOBALS['rlDb']->dropTable('faqs');
    }

    /**
     * Update to 1.1.2
     **/
    public function update112() {
        global $rlDb;

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
            WHERE `Plugin` = 'FAQs' AND `Key` = 'faq_dropped'"
        );

        // remove unnecessary hooks
        $hooks = array(
            'sitemapAddUrlsInFile',
            'sitemapTotalUrls',
            'sitemapAddUrlsCommon',
            'sitemapAddNewFile',
        );

        $rlDb->query(
            "DELETE FROM `{db_prefix}hooks`
            WHERE `Plugin` = 'FAQs' AND `Name` IN ('" . implode("','", $hooks) . "')"
        );
    }

    /**
     * Update to 1.2.0
     */
    public function update120()
    {
        global $rlDb;

        $rlDb->addColumnToTable('Position', "INT NOT NULL AFTER `Path`", 'faqs');
        $rlDb->query('ALTER TABLE `{db_prefix}faqs` ENGINE = InnoDB');
    }

    /**
     * @hook  specialBlock
     * @since 1.1.2
     */
    public function hookSpecialBlock() {
        global $blocks;

        if (array_key_exists('faqs_box', $blocks)) {
            $faqs = $this->get(false, 'block');

            $GLOBALS['rlSmarty']->assign('all_faqs_block', $faqs);
        }
    }

    /**
     * @hook  sitemapAddPluginUrls
     * @since 1.1.2
     */
    public function hookSitemapAddPluginUrls(&$pluginsUrls)
    {
        global $config, $rlDb, $reefless;

        $sql = "SELECT `ID`,  `Path` ";
        $sql .= "FROM `{db_prefix}faqs` WHERE `Status` = 'active' ORDER BY `Date` DESC";
        $items = $rlDb->getAll($sql);

        if ($items) {
            foreach ($items as $value) {
                $addUrl  = $config['mod_rewrite'] ? [$value['Path']] : [];
                $addVars = $config['mod_rewrite'] ? '' : "id={$value['ID']}";
                $url     = $reefless->getPageUrl('faqs', $addUrl, null, $addVars);

                $pluginsUrls[] = $url;
            }
            unset($items);
        }
    }

    /**
     * @hook  apAjaxRequest
     * @since 1.1.2
     */
    public function hookApAjaxRequest(&$out, $request_mode)
    {
        switch ($request_mode) {
            case 'deleteFAQs':
                $id = (int) $_REQUEST['id'];

                $out = $this->deleteFAQs($id);
                break;
        }
    }

    /**
     * Get faqs
     *
     * @param int $id - faqs id
     * @param bool $page - page mode
     * @param int $pg - start position
     * @param bool $calc_fr - append SQL_CALC_FOUND_ROWS
     *
     * @return array - faqs array
     **/
    public function get($id = false, $page = false, $pg = 1, $calc_fr = false) {
        $id = (int) $id;
        $sql = "SELECT ";

        if ($calc_fr === true) {
            $sql .= "SQL_CALC_FOUND_ROWS ";
        }

        $sql .= "`ID`, `ID` AS `Key`, `Date`, `Path` FROM `{db_prefix}faqs` ";
        $sql .= "WHERE `Status` = 'active' ";

        if ($id) {
            $sql .= "AND `ID` = '{$id}'";
        }

        $GLOBALS['rlHook']->load('rlFAQsGetSql', $sql); // from v4.1.0

        $sql .= "ORDER BY `Position` ";

        if ($page === 'block') {
            $sql .= "LIMIT " . $GLOBALS['config']['faqs_block_in_block'];
        } else {
            $start = 0;
            if ($pg > 1) {
                $start = ($pg - 1) * $GLOBALS['config']['faqs_at_page'];
            }

            $sql .= "LIMIT {$start}," . $GLOBALS['config']['faqs_at_page'];
        }

        if ($id) {
            $faqs = $GLOBALS['rlDb']->getRow($sql);
        } else {
            $faqs = $GLOBALS['rlDb']->getAll($sql);
        }

        if ($calc_fr === true) {
            $faqs_number = $GLOBALS['rlDb']->getRow("SELECT FOUND_ROWS() AS `calc`");
            $this->calc_faqs = $faqs_number['calc'];
        }

        $faqs = $GLOBALS['rlLang']->replaceLangKeys($faqs, 'faqs', array('title', 'content', 'meta_description', 'h1'));

        return $faqs;
    }

    /**
     * Delete FAQs
     *
     * @param string $id - faq ID
     *
     **/
    public function deleteFAQs($id) {
        global $rlDb;

        if (!$id) {
            return false;
        }

        $id = (int) $id;

        $rlDb->delete(array('ID' => $id), 'faqs');
        $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE (`Key` = 'faqs+title+{$id}' OR `Key` = 'faqs+content+{$id}' OR `Key` = 'faqs+h1+{$id}' OR `Key` = 'faqs+meta_description+{$id}') AND `Plugin` = 'FAQs'");

        $out['status']  = 'ok';
        $out['message'] = $GLOBALS['lang']['faq_deleted'];

        return $out;
    }

    /**
     * @deprecated 1.1.2
     **/
    public function ajaxDeleteFAQs($id) {
    }
}
