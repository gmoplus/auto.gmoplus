<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLSTREETVIEW.CLASS.PHP
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

/**
 * @since 2.0.0
 */
class rlStreetView extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * Plugin installer
     */
    public function install()
    {
        $update = array(
            'fields' => array(
                'Status'     => 'trash',
                'Page_ID'    => '25',
                'Sticky'     => '0',
                'Cat_sticky' => '1',
                'Position'   => 2,
                'Plugin'     => ''
            ),
            'where' => array(
                'Key' => 'street_view'
            )
        );
        $GLOBALS['rlDb']->updateOne($update, 'blocks');

        $GLOBALS['rlDb']->addColumnToTable(
            'Street_view',
            "ENUM('0', '1') NOT NULL DEFAULT '1' AFTER `Status`",
            'listing_types'
        );

        $this->fixScopes();
    }

    /**
     * Plugin uninstaller
     */
    public function uninstall()
    {
        $GLOBALS['rlDb']->delete(array('Key' => 'street_view'), 'blocks');
        $GLOBALS['rlDb']->dropColumnFromTable('Street_view', 'listing_types');
    }

    /**
     * Is API configured
     *
     * @return boolean
     */
    public function isAPIConfigured()
    {
        global $config;

        return ($config['street_view_provider'] == 'google' && $config['street_view_google_key'])
            || ($config['street_view_provider'] == 'yandex' && $config['street_view_yandex_key']);
    }

    /**
     * Is gallery integration supported by tempalate
     *
     * @return boolean
     */
    public function isGallerySupported()
    {
        global $tpl_settings;

        $no_support = ['general_simple_wide', 'general_modern_wide'];
        return !(bool) preg_match('/(\_flatty|\_modern)$/', $tpl_settings['name'])
               && !in_array($tpl_settings['name'], $no_support)
               && strpos($tpl_settings['name'], 'escort_') !== 0;
    }

    /**
     * Add street view tab
     *
     * @hook listingDetailsBottom
     */
    public function hookListingDetailsBottom()
    {
        global $tabs, $lang, $config, $location, $rlSmarty, $blocks;

        if (!$this->isAPIConfigured()
            || !$location['direct']
            || !$rlSmarty->_tpl_vars['listing_type']['Street_view']
        ) {
            // Unset plugin box
            if ($config['street_view_mode'] == 'box') {
                unset($blocks['street_view']);
                $GLOBALS['rlCommon']->defineBlocksExist($blocks);
            }
            return;
        }

        if ($config['street_view_mode'] == 'tab') {
            $tabs['streetViewTab'] = array(
                'key' => 'streetView',
                'name' => $lang['street_view_tab']
            );
        } elseif ($config['street_view_mode'] == 'gallery' && $this->isGallerySupported()) {
            $GLOBALS['rlStatic']->addFooterCSS(RL_PLUGINS_URL . 'street_view/static/style.css');
        }
    }

    /**
     * Display street view tab content
     *
     * @hook listingDetailsBottom
     */
    public function hookListingDetailsBottomTpl()
    {
        global $config, $rlSmarty, $location;

        if (!$this->isAPIConfigured()
            || !$rlSmarty->_tpl_vars['listing_type']['Street_view']
        ) {
            return;
        }

        if ($config['street_view_mode'] == 'tab' && $location['direct']) {
            $rlSmarty->display(RL_PLUGINS . 'street_view' . RL_DS . 'tab.tpl');
        }
    }

    /**
     * Add street view button to the listing details gallery
     *
     * @hook tplListingDetailsMapButtons
     */
    public function hookTplListingDetailsMapButtons()
    {
        global $lang, $config, $location, $rlSmarty;

        if (!$this->isAPIConfigured()
            || !$this->isGallerySupported()
            || !$rlSmarty->_tpl_vars['listing_type']['Street_view']
        ) {
            return;
        }

        if ($config['street_view_mode'] == 'gallery' && $location['direct']) {
            echo <<< HTML
                <span class="nav-button street-view">{$lang['street_view_tab']}</span>
HTML;
        }
    }

    /**
     * Add street view container to the listing details gallery
     *
     * @hook tplListingDetailsPhotoPreview
     */
    public function hookTplListingDetailsPhotoPreview()
    {
        global $config, $rlSmarty, $location;

        if (!$this->isAPIConfigured()
            || !$this->isGallerySupported()
            || !$rlSmarty->_tpl_vars['listing_type']['Street_view']
        ) {
            return;
        }

        if ($config['street_view_mode'] == 'gallery' && $location['direct']) {
            $rlSmarty->display(RL_PLUGINS . 'street_view' . RL_DS . 'gallery.tpl');
        }
    }

    /**
     * Include configs js handlers
     *
     * @hook apTplContentBottom
     */
    public function hookApTplContentBottom()
    {
        global $cInfo;

        if ($cInfo['Key'] == 'config') {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'street_view' . RL_DS . 'admin' . RL_DS . 'js.tpl');
        }
    }

    /**
     * Plugin box status handler
     *
     * @hook apPhpConfigAfterUpdate
     */
    public function hookApPhpConfigAfterUpdate()
    {
        $mode = $GLOBALS['dConfig']['street_view_mode']['value'];

        if ($GLOBALS['config']['street_view_mode'] != $mode) {
            $update = array(
                'fields' => array(
                    'Status' => $mode == 'box' ? 'active' : 'trash'
                ),
                'where' => array(
                    'Key' => 'street_view'
                ),
            );
            $GLOBALS['rlDb']->updateOne($update, 'blocks');
        }
    }

    /**
     * Print "No API keys" noteice in admin panel
     *
     * @hook apNotifications
     */
    public function hookApNotifications(&$notices)
    {
        global $config;

        if (($config['street_view_provider'] == 'google' && !$config['street_view_google_key'])
            || ($config['street_view_provider'] == 'yandex' && !$config['street_view_yandex_key'])
        ) {
            $notice = $GLOBALS['rlLang']->getPhrase('street_view_no_api_keys', null, false, true);
            $group_id = $GLOBALS['rlDb']->getOne('ID', "`Key` = 'street_view'", 'config_groups');
            $url = RL_URL_HOME . ADMIN . '/index.php?controller=settings&group=' . $group_id;
            $link = '<a href="' . $url . '">$2</a>';
            $notice = preg_replace('/(\[([^\]]+)\])/', $link, $notice);

            $notices[] = $notice;
        }
    }

    /**
     * Switch mode to "tab" if selected template does not support media in the gallery
     *
     * @hook apMixConfigItem
     */
    public function hookApMixConfigItem(&$item, &$systemSelects)
    {
        global $config;

        $systemSelects[] = 'street_view_mode';

        if ($this->isGallerySupported()) {
            return;
        }

        if ($item['Key'] != 'street_view_mode') {
            return;
        }

        if ($config['street_view_mode'] == 'gallery') {
            $GLOBALS['rlConfig']->setConfig('street_view_mode', 'tab');
            $config['street_view_mode'] = 'tab';
            $item['Default'] = 'tab';
        }

        unset($item['Values'][1]);
    }

    /**
     * Display plugin option in listing type form
     *
     * @since 2.1.0
     * @hook apTplListingTypesForm
     */
    public function hookApTplListingTypesForm()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'street_view/admin/row.tpl');
    }

    /**
     * Assign data to POST
     *
     * @since 2.1.0
     * @hook apPhpListingTypesPost
     */
    public function hookApPhpListingTypesPost()
    {
        $_POST['street_view'] = $GLOBALS['type_info']['Street_view'];
    }

    /**
     * Assign to data array
     *
     * @since 2.1.0
     * @hook apPhpListingTypesBeforeAdd
     */
    public function hookApPhpListingTypesBeforeAdd()
    {
        $GLOBALS['data']['Street_view'] = (int) $_POST['street_view'];
    }

    /**
     * Assign to data array
     *
     * @since 2.1.0
     * @hook apPhpListingTypesBeforeEdit
     */
    public function hookApPhpListingTypesBeforeEdit()
    {
        $GLOBALS['update_date']['fields']['Street_view'] = (int) $_POST['street_view'];
    }

    /**
     * Fix phrase scopes for software version <= 4.8.0
     */
    public function fixScopes()
    {
        if (version_compare($GLOBALS['config']['rl_version'], '4.8.0', '>')) {
            return;
        }

        $update = array(
            'fields' => array('Module' => 'admin'),
            'where' => array('Key' => 'street_view_no_api_keys')
        );
        $GLOBALS['rlDb']->updateOne($update, 'lang_keys');
    }

    /**
     * Update to 2.0.0 version
     */
    public function update200()
    {
        global $rlDb;

        // Remove phrases
        $phrases = array(
            'street_view_no_flash'
        );
        $sql = "DELETE FROM `{db_prefix}lang_keys` WHERE `Plugin` = 'street_view' AND `Key` IN ('" . implode("','", $phrases) . "')";
        $rlDb->query($sql);

        // Prepare box
        $update = array(
            'fields' => array(
                'Status'     => 'trash',
                'Page_ID'    => '25',
                'Sticky'     => '0',
                'Cat_sticky' => '1',
                'Position'   => 2,
                'Plugin'     => ''
            ),
            'where' => array(
                'Key' => 'street_view'
            ),
        );
        $rlDb->update($update, 'blocks');

        // Remove legacy files
        $files_to_be_removed = array(
            'static/lib.js',
        );

        foreach ($files_to_be_removed as $file) {
            unlink(RL_PLUGINS . 'street_view/' . $file);
        }

        $this->fixScopes();
    }

    /**
     * Update to 2.1.0 version
     */
    public function update210()
    {
        $GLOBALS['rlDb']->addColumnToTable(
            'Street_view',
            "ENUM('0', '1') NOT NULL DEFAULT '1' AFTER `Status`",
            'listing_types'
        );
    }
}
