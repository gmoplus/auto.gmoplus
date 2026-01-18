<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLLISTINGSCAROUSEL.CLASS.PHP
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

class rlListingsCarousel extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * Plugin installer
     * @since 3.3.0
     */
    public function install()
    {

        // create table
        $sql = "
            CREATE TABLE `{db_prefix}listings_carousel` (
              `ID` int(11) NOT NULL AUTO_INCREMENT,
              `Direction` enum('vertical','horizontal') NOT NULL DEFAULT 'horizontal',
              `Block_IDs` varchar(255) NOT NULL,
              `Number` int(10) NOT NULL,
              `Delay` int(10) NOT NULL,
              `Per_slide` int(10) NOT NULL,
              `Visible` int(10) NOT NULL,
              `Round` enum('0','1') NOT NULL DEFAULT '1',
              `Status` varchar(10) NOT NULL,
              PRIMARY KEY (`ID`)
            ) DEFAULT CHARSET=utf8;";

        $GLOBALS['rlDb']->query($sql);

        $GLOBALS['rlDb']->query("UPDATE `{db_prefix}hooks` SET `Class` = '' WHERE `Plugin` = 'listings_carousel' AND `Name` = 'init';");
    }

    /**
     * Plugin un-installer
     * @since 3.3.0
     */
    public function uninstall()
    {
        // DROP TABLE
        $GLOBALS['rlDb']->query("DROP TABLE IF EXISTS `{db_prefix}listings_carousel`");
    }

    /**
     * @hook staticDataRegister
     * set css and js files for new version(4.5 and more)
     */
    public function hookStaticDataRegister()
    {
        // Add js files
        $GLOBALS['rlStatic']->addBoxJS(RL_LIBS_URL . 'fancyapps/carousel.umd.js', 'listings_carousel', true);        
        $GLOBALS['rlStatic']->addBoxJS(RL_LIBS_URL . 'fancyapps/carousel.autoplay.umd.js', 'listings_carousel', true);
        $GLOBALS['rlStatic']->addBoxJS(RL_PLUGINS_URL . 'listings_carousel/static/carousel.js', 'listings_carousel', true);

        // Add css files
        $GLOBALS['rlStatic']->addBoxFooterCSS(RL_LIBS_URL . 'fancyapps/carousel.css', 'listings_carousel', true);
    }

    /**
     * @hook ajaxRequest
     */
    public function hookAjaxRequest(&$out, $request_mode, $request_item, $request_lang)
    {
        global $lang;
        if ($request_item == 'listing_carousel') {
            if ($GLOBALS['rlGeoFilter']->geo_format && !$GLOBALS['rlGeoFilter']->geo_filter_data['location_url_pages']) {
                $GLOBALS['rlGeoFilter']->init();
                global $page_info;
                $page_info['Key'] = $_REQUEST['page_key'];
                $GLOBALS['rlGeoFilter']->hookPageinfoArea();
            }

            if (!$lang) {
                $lang = $GLOBALS['rlLang']->getLangBySide('frontEnd', $request_lang);
            }

            $out = $this->loadListings(
                $_REQUEST['limit'],
                $_REQUEST['options'],
                $_REQUEST['number'],
                $_REQUEST['price_tag'],
                $_REQUEST['side_bar_exists'],
                $_REQUEST['page_key']
            );
            $out['status'] = $out['count'] >= 1 && $out['results'] ? 'ok' : 'no';
        }
    }

    /**
     * @hook Init
     * @since 3.3.0
     */
    public function hookInit()
    {}

    /**
     * @hook specialBlock
     * @since 3.3.0
     */
    public function hookSpecialBlock()
    {
        global $carousel_options;
        if ($carousel_options) {
            $this->changeContentBlock($carousel_options);
        }
    }

    /**
     * @hook listingsAfterSelectFeatured
     * @since 3.3.0
     */
    public function hookListingsAfterSelectFeatured($sql, $block_key, $listings)
    {
        global $blocks, $carousel_options;
        $id = $blocks[$block_key]['ID'];

        foreach ($listings as $key => $val) {
            if ($carousel_options[$id]) {
                $_SESSION['carousel'][$block_key][] = $val['ID'];
            }
            $_SESSION['carousel']['all_ids'][] = $val['ID'];
        }
    }

    /**
     * @hook listings Modify Where Featured
     * @since 3.3.0
     */
    public function hookListingsModifyWhereFeatured($sql, $block_key, &$limit, $start)
    {
        global $blocks, $carousel_options;
        $id = $blocks[$block_key]['ID'];
        if ($carousel_options[$id]) {
            $limit = $carousel_options[$id]['Visible'] == 0 ? 5 : $carousel_options[$id]['Visible'];
        }
    }

    /**
     * Update configuration for carousel
     */
    public function updateCarouselBlock()
    {
        global $rlDb;

        $box = $rlDb->getAll("SELECT * FROM `{db_prefix}listings_carousel` WHERE `Status` = 'active' ");

        $content = 'global $rlSmarty, $carousel_options;';
        $content .= ' if ( !$_REQUEST["xjxfun"] && !defined("AJAX_FILE")) {unset($_SESSION["carousel"]);}';

        if ($box) {
            $content .= '$carousel_options = array(';

            foreach ($box as $key => $item) {

                $block_ids = explode(',', $item['Block_IDs']);
                foreach ($block_ids as $keyId => $itemId) {
                    if ($itemId) {
                        $content .= (int) $itemId . '=> array( ';
                        $content .= '"Direction" => "' . $item['Direction'] . '",';
                        $content .= '"Number" => "' . $item['Number'] . '",';
                        $content .= '"Delay" => "' . $item['Delay'] . '",';
                        $content .= '"Per_slide" => "' . $item['Per_slide'] . '",';
                        $content .= '"Visible" => "' . $item['Visible'] . '",';
                        $content .= '"Round" => "' . $item['Round'] . '"';
                        $content .= '),';
                    }
                }
            }
            $content = substr($content, 0, -1);
            $content .= ');';
        }

        $content .= '$rlSmarty->assign("carousel_options", $carousel_options);';

        if ($rlDb->query("UPDATE  `{db_prefix}hooks` SET `Class` = '', `Code` = '{$content}' WHERE `Name` = 'init' AND `Plugin` = 'listings_carousel'  LIMIT 1;")) {
            return true;
        }
    }

    /**
     * Change content block for carousel
     *
     * @param  array $contents - options of carousel
     *
     * @return array           - adapted listings data
     */
    public function changeContentBlock($contents = false)
    {
        global $carousel_options, $blocks, $tpl_settings, $page_info, $config;

        if ($_REQUEST["item"] != "listing_carousel") {
            $_SESSION['carousel']['all_ids'] = $GLOBALS['rlListings']->selectedIDs;
        }

        if ($tpl_settings['home_page_gallery'] && $config['home_gallery_box'] && $page_info['Key'] == 'home') {
            $home_gallery_key = $config['home_gallery_box'];
        }
        foreach ($blocks as $sKey => $sVal) {
            if ($contents[$sVal['ID']]) {
                if ($blocks[$sKey]['Type'] != 'smarty') {
                    $listings_name = "listings_carousel";
                    $box_name = "carousel.block.tpl";

                    $lbID = (int) str_replace('listing_box_', '', $sVal['Key']);
                    $lbOptions = $GLOBALS['rlDb']->fetch("*", array('ID' => $lbID), null, 1, 'listing_box', 'row');
                    $lbOptions['Limit'] = 5;
                    $callListingBox = $this->buildListingBoxMethod($lbOptions);

                    $contentBlock = 'global $rlSmarty, $reefless;
                        if($_REQUEST["page"] == "404") {return;}
                        $reefless->loadClass("ListingsBox", null, "listings_box");
                        ' . $callListingBox . '
                        foreach($listings_box as $key => $val)
                        {
                            $ids[] = $val["ID"];
                            $_SESSION["carousel"]["all_ids"][] = $val["ID"];
                        }
                        $_SESSION["carousel"]["' . $blocks[$sKey]['Key'] . '"] = $ids;
                        $rlSmarty->assign_by_ref("' . $listings_name . '", $listings_box);
                        $rlSmarty->display(RL_PLUGINS . "listings_carousel" . RL_DS . "' . $box_name . '" );';


                    $blocks[$sKey]['Content'] = $contentBlock;
                    $blocks[$sKey]['Plugin'] = "listings_carousel";
                    $GLOBALS['lang']['blocks+header_link+' . $blocks[$sKey]['Key']] = $GLOBALS['lang']['lb_view_all'];
                } else {
                    if ($home_gallery_key != $sVal['Key']) {
                        /* get field/value */
                        preg_match("/listings=(.*)\s+type='(\w+)'(\s+field='(\w+)')?(\s+value='(\w+)')?/", $sVal['Content'], $matches);
                        $arange_fields = $matches['3'] || $matches['5'] ? $matches['3'] . " " . $matches['5'] : "";

                        $blocks[$sKey]['Content'] = '{include file=$smarty.const.RL_PLUGINS|cat:"listings_carousel"|cat:$smarty.const.RL_DS|cat:"carousel.block.tpl" listings_carousel=' . $matches[1] . ' type="' . $matches[2] . '" ' . $arange_fields . '}';

                        $blocks[$sKey]['Plugin'] = "listings_carousel";
                        $blocks[$sKey]['options'] = "featured|" . $blocks[$sKey]['Key'] . "|" . $matches[2] . "|" . $matches[4] . "|" . $matches[6];
                    }
                }
            }
        }
        $GLOBALS['rlCommon']->defineBlocksExist($blocks);
    }

    /**
     * Build params for listing box method 
     *
     * @since 4.1.0
     *
     * @param  array $options
     *
     * @return string
     */
    public function buildListingBoxMethod($options)
    {
        $params = "'{$options['Type']}', '{$options['Box_type']}', '{$options['Limit']}', '1', '{$options['Box_type']}'";

        if (version_compare($GLOBALS['plugins']['listings_box'], '3.0.7') >= 0) {
            $params .= ", '{$options['Category_IDs']}', '{$options['Use_subcats']}'";
        }
        if (version_compare($GLOBALS['plugins']['listings_box'], '3.2.0') >= 0) {
            $params .= ", '{$options['Filters']}'";
        }

        $callListingBox = '$listings_box = $GLOBALS["rlListingsBox"]->getListings(' .$params. ');';
        return $callListingBox;
    }

    /**
     * Load  listings
     *
     * @param int     $limit           -  limit
     * @param string  $options         -  options
     * @param int     $number          -  max listings
     * @param bool    $priceTag        -  price tag
     * @param bool    $side_bar_exists -  price tag
     * @param string  $page_info_key   -  price tag
     *
     */
    public function loadListings($limit = 1, $options = false, $number = false, $priceTag = false, $side_bar_exists = false, $page_info_key = false)
    {
        global $rlListings, $rlSmarty,$rlHook, $reefless, $config, $page_info, $tpl_settings, $lang;

        // define tpl settings
        if (!$tpl_settings) {
            require_once RL_ROOT . 'templates' . RL_DS . $config['template'] . RL_DS . 'settings.tpl.php';
        }

        $GLOBALS['rlValid']->sql($options);
        $options = explode('|', $options);

        $GLOBALS['rlHook']->load('featuredTop');

        // if limit more number
        if ($number - $limit < 0) {
            $limit = $number;
        }

        //get listing types
        $listing_types = $GLOBALS['rlListingTypes']->types;
        $boxKey = $_REQUEST['box_key'];

        // get listings by type
        if ($options[0] == 'featured') {
            $rlListings->selectedIDs = $_SESSION['carousel']['all_ids'];
            $listings = $rlListings->getFeatured($options[2], $limit, $options[3], $options[4]);
        } else {
            // get listing box
            $reefless->loadClass('ListingsBox', null, 'listings_box');
            $lbID = (int) str_replace('listing_box_', '', $boxKey);
            $options = $GLOBALS['rlDb']->fetch("*", array('ID' => $lbID), null, 1, 'listing_box', 'row');

            if ($options['Unique']) {
                $rlListings->selectedIDs = $_SESSION['carousel']['all_ids'];
            } else {
                $rlListings->selectedIDs = $_SESSION['carousel'][$boxKey] ? : $_SESSION['carousel']['all_ids'];
            }

            if (version_compare($GLOBALS['plugins']['listings_box'], '3.2.0') >= 0) {
                $listings = $GLOBALS["rlListingsBox"]->getListings(
                    $options['Type'],
                    $options['Box_type'],
                    $limit,
                    1,
                    $options['By_category'],
                    $options['Category_IDs'],
                    $options['Use_subcats'],
                    $options['Filters']
                );
            }
            else if (version_compare($GLOBALS['plugins']['listings_box'], '3.0.7') >= 0) {
                $listings = $GLOBALS["rlListingsBox"]->getListings(
                    $options['Type'], 
                    $options['Box_type'], 
                    $limit, 
                    1, 
                    $options['By_category'], 
                    $options['Category_IDs'], 
                    $options['Use_subcats']
                );
            }
            else {
                $listings = $GLOBALS["rlListingsBox"]->getListings(
                    $options['Type'], 
                    $options['Box_type'], 
                    $limit, 
                    1, 
                    $options['By_category']
                );
            }
        }

        if ($listings) {
            // define page info
            if ($page_info_key && !$page_info) {
                $page_info['Key'] = $page_info_key;
            }

            $rlSmarty->assign('side_bar_exists', $side_bar_exists);
            $rlSmarty->assign('lang', $lang);
            $rlSmarty->assign('block', $GLOBALS['rlDb']->fetch("*", array('Status' => 'active', 'Key' => $boxKey), null, 1, 'blocks', 'row'));

            $rlSmarty->assign('rlTplBase', RL_URL_HOME . 'templates/' . $config['template'] . '/');
            $html_content = [];
            // add new listings
            foreach ($listings as $key => $listing) {
                if ($options[0] != 'featured') {
                    $_SESSION['carousel'][$boxKey][] = $listing['ID'];
                }
                if (!in_array($listing['ID'], $_SESSION['carousel']['all_ids'])) {
                    $_SESSION['carousel']['all_ids'][] = $listing['ID'];
                }

                // assign listing, type and page key
                $listing_type = $listing['Listing_type'];
                $page_key = $listing_types[$listing_type]['Page_key'];
                $rlSmarty->assign('type', $listing_type);
                $rlSmarty->assign('page_key', $page_key);
                $rlSmarty->assign('featured_listing', $listing);

                $tpl = 'blocks' . RL_DS . 'featured_item.tpl';
                $html_content[] = $rlSmarty->fetch($tpl, null, null, false);
            }
        }

        $content_listings['block_key'] = $boxKey;

        if ($GLOBALS['plugins']['currencyConverter']) {
            $content_listings['block_key'] = $boxKey;
        }
        $content_listings['count'] = $listings ? count($listings) : 0;
        $content_listings['results'] = $html_content;

        return $content_listings;
    }

    /**
     * Remove listing carousel
     *
     * @hook apAjaxRequest
     */
    public function hookApAjaxRequest()
    {
        $item = $GLOBALS['rlValid']->xSql($_REQUEST['item']);
        switch ($item) {
            case 'deleteListingsCarousel':
                $id = (int) $_REQUEST['id'];
                // delete box
                $GLOBALS['rlDb']->query("DELETE FROM `{db_prefix}listings_carousel` WHERE `ID` = '{$id}' LIMIT 1");

                // update carousel boxs
                $this->updateCarouselBlock();
                $GLOBALS['out']['status'] = "ok";
                $GLOBALS['out']['message'] = $GLOBALS['lang']['block_deleted'];
                break;
        }
    }

    /**
     * Display carousel box styles
     *
     * @since 4.0.0
     * @hook tplHeader
     */
    public function hookTplHeader()
    {
        if (!$GLOBALS['carousel_options']) {
            return;
        }

        echo <<< HTML
<style>
:root {
    --carousel-opacity: .3;
    .listings_carousel {
        --carousel-button-border-radius: 3px;
    }
}
body.home-page .listings_carousel .f-carousel + div > input,
body.home-page .listings_carousel .carousel + div > input {
    display: none;
}
.listings_carousel .f-carousel__button,
.listings_carousel .carousel__button {
    color: initial;
    background: rgba(255,255,255,var(--carousel-opacity));
    transition: background ease 0.3s;
}
.listings_carousel .carousel__button:hover {
    --carousel-opacity: .7;
}
body[dir=rtl] .listings_carousel .f-carousel__viewport,
body[dir=rtl] .listings_carousel .carousel__viewport {
    direction: ltr;
}
body[dir=rtl] .listings_carousel .f-carousel__viewport .f-carousel__slide_item,
body[dir=rtl] .listings_carousel .carousel__viewport .carousel__slide_item {
    direction: rtl;
    text-align: right;
}
@media (max-width: 575px) {
    .listings_carousel .f-carousel__track,
    .listings_carousel .carousel__track {
        overflow-x: unset;
        scroll-snap-type: unset;
    }
    .listings_carousel .f-carousel__slide_item,
    .listings_carousel .carousel__slide_item {
        flex: 0 0 100%;
        width: 100%;
        max-width:100%;
    }
}

/**
 * Fix flex-shrink issue
 * @todo - Remove once middle boxes issues will be fixed in all templates
 */
aside.two-middle > div > div {
    min-width: 0;
}
</style>
HTML;
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
     * Update to 3.3.0 version
     */
    public function update330()
    {
        global $rlDb;

        // Set class to plugin
        $rlDb->query("UPDATE  `{db_prefix}plugins` SET `Class` = 'ListingsCarousel' WHERE `Key` = 'listings_carousel';");

        // Update Carousel options
        $this->updateCarouselBlock();

        // Remove hook
        $rlDb->query("
            DELETE FROM `{db_prefix}hooks`
            WHERE `Plugin` = 'listings_carousel'
            AND `Name` = 'tplHeader'
        ");

        // Remove legacy files
        $files_to_be_removed = array(
            'static/carousel_44.css',
            'static/carousel_44.js',
            'static/carousel_45.css',
            'static/carousel_45.js',
            'carousel.listing.tpl',
            'carousel.block_44.tpl',
        );
        foreach ($files_to_be_removed as $file) {
            unlink(RL_PLUGINS . 'listings_carousel/' . $file);
        }
    }

    /**
     * Update to 4.0.0 version
     */
    public function update400()
    {
        global $rlDb;

        unlink(RL_PLUGINS . 'listings_carousel/static/carousel.css');

        // remove unnecessary phrases
        $phrases = array(
            'listings_carousel_ext_assiged_boxes',
            'listings_carousel_ext_direction',
            'listings_carousel_ext_number_of_listings',
            'listings_carousel_ext_delay',
            'listings_carousel_ext_per_slide',
            'listings_carousel_ext_visible',
        );

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
            WHERE `Plugin` = 'listings_carousel' AND `Key` IN ('" . implode("','", $phrases) . "')"
        );

        $enDesc = [
            'Key' => 'description_listings_carousel',
            'Value' => 'Scrolls premium, popular, recently added and random ads in a separate content box',
        ];
        foreach ($GLOBALS['languages'] as $key => $language) {

            if ($language['Code'] != 'ru') {
                if (!$rlDb->getOne('ID', "`Key` = '{$enDesc['Key']}' AND `Code` = '{$language['Code']}'", 'lang_keys')) {
                    $rlDb->insertOne([
                        'Code'   => $language['Code'],
                        'Module' => 'common',
                        'Key'    => $enDesc['Key'],
                        'Value'  => $enDesc['Value'],
                        'Plugin' => 'listings_carousel',
                    ], 'lang_keys');
                }
                else {
                    $rlDb->updateOne([
                        'fields' => ['Value' => $enDesc['Value']],
                        'where' => ['Key' => $enDesc['Key'], 'Code' => $language['Code'], 'Modified' => '0'],
                    ], 'lang_keys');
                }
            }
        }

        if (in_array('ru', array_keys($GLOBALS['languages']))) {
            $russianTranslation = json_decode(file_get_contents(RL_PLUGINS . 'listings_carousel/i18n/ru.json'), true);

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
}
