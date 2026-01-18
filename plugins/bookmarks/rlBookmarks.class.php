<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLBOOKMARKS.CLASS.PHP
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

class rlBookmarks
{
    /**
     * Floating bar html content
     *
     * @since 5.0.0
     * @var string
     */
    public $floatingBoxContent = '';

    /**
     * Bar html content for the listing details page
     *
     * @since 5.0.0
     * @var string
     */
    public $detailsBoxContent = '';

    /**
     * Bar html content for the "done" step on the add listing page
     *
     * @since 5.0.0
     * @var string
     */
    public $doneBoxContent = '';

    /**
     * Force styles and js code initialization
     *
     * @since 5.0.0
     * @var boolean
     */
    public $init = false;
    
    /**
     * Plugin installer
     */
    public function install()
    {
        global $rlDb;

        $sql = "
            CREATE TABLE IF NOT EXISTS `{db_prefix}bookmarks` (
            `ID` INT NOT NULL AUTO_INCREMENT,
            `Key` VARCHAR(30) NOT NULL,
            `Type` enum('floating_bar','inline') NOT NULL DEFAULT 'inline',
            `Services` MEDIUMTEXT NOT NULL,
            `Align` ENUM('left', 'center', 'right') DEFAULT 'center' NOT NULL,
            `Theme` ENUM('light','dark','transparent') DEFAULT 'transparent' NOT NULL,
            `View_mode` ENUM('large','medium','small') NOT NULL DEFAULT 'medium',
            `Counter` ENUM('0','1') NOT NULL DEFAULT '0',
            PRIMARY KEY (`ID`)
        ) CHARSET=utf8;
        ";
        $rlDb->query($sql);

        $sql = "
            INSERT INTO `{db_prefix}bookmarks` (`Key`, `Type`, `Services`, `Align`, `Theme`, `View_mode`, `Counter`) VALUES
            ('bookmark_floating_bar_1', 'floating_bar', 'facebook,twitter,whatsapp,viber,dd', '', 'transparent', 'medium', '0'),
            ('bookmark_details', 'inline', 'facebook,twitter,whatsapp,viber,dd', 'left', 'transparent', 'medium', '0'),
            ('bookmark_done_step', 'inline', 'facebook,twitter,whatsapp,viber,dd', 'left', 'transparent', 'medium', '0')
        ";
        $rlDb->query($sql);

        $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'blocks+name+bookmark_floating_bar_1'");

        $rlDb->query("UPDATE `{db_prefix}blocks` SET `Page_ID` = '25', `Sticky` = '0' WHERE `Key` = 'bookmark_details'");
        $rlDb->query("UPDATE `{db_prefix}blocks` SET `Page_ID` = '16', `Sticky` = '0' WHERE `Key` = 'bookmark_done_step'");
    }

    /**
     * Plugin un-installer
     */
    public function uninstall()
    {
        $GLOBALS['rlDb']->query("DROP TABLE IF EXISTS `{db_prefix}bookmarks`");
    }

    /**
     * Modify plugin boxes
     * 
     * @since 4.0.0
     * @hook specialBlock
     */
    public function hookSpecialBlock()
    {
        global $blocks, $config, $rlSmarty;

        foreach ($blocks as $key => $block) {
            // Floating bar handler
            if (strpos($block['Key'], 'bookmark_floating_bar_') === 0) {
                $_var_compiled = '';
                $rlSmarty->_compile_source('evaluated template', $block['Content'], $_var_compiled);

                ob_start();
                $rlSmarty->_eval('?>' . $_var_compiled);
                $this->floatingBoxContent = ob_get_clean();

                unset($blocks[$key]);

                $this->init = true;
            }

            // Details page bar
            if ($block['Key'] == 'bookmark_details') {
                $_var_compiled = '';
                $rlSmarty->_compile_source('evaluated template', $block['Content'], $_var_compiled);

                ob_start();
                $rlSmarty->_eval('?>' . $_var_compiled);
                $this->detailsBoxContent = ob_get_clean();

                unset($blocks[$key]);

                $this->init = true;
            }

            // Details page bar
            if ($block['Key'] == 'bookmark_done_step') {
                $_var_compiled = '';
                $rlSmarty->_compile_source('evaluated template', $block['Content'], $_var_compiled);

                ob_start();
                $rlSmarty->_eval('?>' . $_var_compiled);
                $this->doneBoxContent = ob_get_clean();

                unset($blocks[$key]);

                $this->init = true;
            }

            if ($block['Plugin'] == 'bookmarks') {
                $this->init = true;
            }
        }

        if ($this->init) {
            $GLOBALS['rlCommon']->defineBlocksExist($blocks);

            $url = 'https://static.addtoany.com/menu/page.js';
            $GLOBALS['rlStatic']->addJS($url);
        }
    }

    /**
     * Display styles
     * 
     * @since 4.0.0
     * @hook tplHeader
     */
    public function hookTplHeader()
    {
        global $page_info;

        // Disable floating bar for pages required authorization
        if (($page_info['Controller'] != 'add_listing' && $page_info['Login'])
            || ($page_info['Controller'] == 'add_listing' && $GLOBALS['rlSmarty']->_tpl_vars['manageListing']->step != 'category')
        ) {
            $this->floatingBoxContent = false;
        }

        // Add custom floating bar styles
        if ($this->init) {
            echo <<< HTML
            <style>
            .a2a_kit {
                padding: 4px;
                border-radius: 6px;
            }
            .a2a_kit.a2a_barsize_large,
            .a2a_kit.a2a_barsize_large:not(.a2a_flex_style) a {
                padding: 3px;
            }
            .a2a_kit.a2a_barsize_medium,
            .a2a_kit.a2a_barsize_medium:not(.a2a_flex_style) a {
                padding: 2px;
            }
            .a2a_kit.a2a_barsize_small,
            .a2a_kit.a2a_barsize_small:not(.a2a_flex_style) a {
                padding: 2px;
                border-radius: 3px;
            }
            .a2a_kit.a2a_bartheme_transparent,
            .a2a_kit.a2a_bartheme_transparent:not(.a2a_flex_style) a {
                padding: 0;
                border-radius: 0;
            }
            .a2a_kit.a2a_bartheme_transparent,
            .a2a_kit.a2a_bartheme_transparent .a2a_svg {
                border-radius: 0 !important;
            }
            .a2a_kit.a2a_bartheme_light {
                background-color: white;
            }
            .a2a_kit.a2a_bartheme_dark {
                background-color: #2b2b2b;
            }
            </style>
HTML;

            $js = <<< HTML
            <script>
            var a2a_config = a2a_config || {};
            a2a_config.locale = "%s";
            </script>
HTML;
            echo sprintf($js, RL_LANG_CODE);
        }

        // Add custom floating bar styles
        if ($this->floatingBoxContent) {
            echo <<< HTML
            <style>
            .a2a_floating_style {
                top: 200px;
                left: 0;
                z-index: 299 !important;
            }
            body[dir=rtl] .a2a_floating_style {
                left: auto;
                right: 0;
            }
            @media screen and (max-width: 625px) {
                .a2a_floating_style {
                    top: auto;
                    left: auto;
                    right: auto;
                    bottom: 0;
                    display: flex;
                    width: 100%;
                    min-width: 320px;
                    border-radius: 0 !important;
                    padding: 0 !important;
                }
                .a2a_floating_style a {
                    padding: 0 !important;
                    border-radius: 0 !important;
                    flex: 1;
                }
                .a2a_floating_style a .a2a_svg {
                    border-radius: 0 !important;
                    display: flex;
                    width: auto !important;
                    height: 36px;
                    padding: 2px 0;
                }
                .a2a_floating_style.a2a_barsize_small a .a2a_svg {
                    height: 28px !important;
                }
                .a2a_floating_style .a2a_count {
                    display: none !important;
                }
            }
            </style>
HTML;
        }
    }

    /**
     * Show floating bar
     *
     * @since 5.0.0
     * @hook tplFooter
     */
    public function hookTplFooter()
    {
        if ($this->floatingBoxContent) {
            echo $this->floatingBoxContent;
        }
    }

    /**
     * Show floating bar
     *
     * @since 5.0.0
     * @hook listingDetailsAfterStats
     */
    public function hookListingDetailsAfterStats()
    {
        if ($this->detailsBoxContent) {
            echo $this->detailsBoxContent;
        }
    }

    /**
     * Show bar on the done step
     *
     * @since 5.0.0
     * @hook addListingStepActionsTpl
     */
    public function hookAddListingStepActionsTpl()
    {
        global $reefless, $addListing;

        if ($this->doneBoxContent
            && $addListing->step == 'done'
            && $GLOBALS['config']['listing_auto_approval']
        ) {
            $url = $reefless->getListingUrl($addListing->listingData);
            $title = $GLOBALS['rlListings']->getListingTitle(
                $addListing->category['ID'],
                $addListing->listingData,
                $addListing->listingType['Key']
            );

            echo str_replace(['[listing_url]', '[listing_title]'], [$url, $title], $this->doneBoxContent);
        }
    }
}
