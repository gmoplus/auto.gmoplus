<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: INDEX.PHP
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

/* template settings */
$tpl_settings = array(
    'type' => 'responsive_42', // DO NOT CHANGE THIS SETTING
    'version' => 1.0,
    'name' => 'general_cragslist_wide', // _flatty_wide - is necessary postfix
    'single_rtl_css' => true,
    'inventory_menu' => false,
    'right_block' => false,
    'long_top_block' => false,
    'featured_price_tag' => true,
    'ffb_list' => false, //field bound boxes plugins list
    'fbb_custom_tpl' => true,
    'header_banner' => true,
    'header_banner_size_hint' => '728x90',
    'home_page_gallery' => false,
    'autocomplete_tags' => true,
    'category_banner' => true,
    'shopping_cart_use_sidebar' => true,
    'listing_details_anchor_tabs' => false,
    'search_on_map_page' => true,
    'home_page_map_search' => false,
    'browse_add_listing_icon' => true,
    'sass_styles' => true,
    'css_hash' => '3e4da40d4027953d886d426da6028b35',
    'css_rtl_hash' => '9eef76b7caccb665ab39861b8000075d',
    'listing_grid_except_fields' => array('title', 'bedrooms', 'bathrooms', 'square_feet', 'time_frame', 'phone', 'pay_period'),
    'category_dropdown_search' => true,
    'sidebar_sticky_pages' => 'all',
    'sidebar_restricted_pages' => array('search_on_map'),
    'svg_icon_fill' => true,
    'default_listing_grid_mode' => 'list',
    'listing_grid_mode_only' => false,
    'bootstrap_grid_no_xl' => true,
    'qtip' => array(
        'background' => '066ce3',
        'b_color'    => '066ce3',
    ),
    'font_family' => [
        'local' => true,
        'name' => 'Lato',
        'format' => 'woff2',
        'weights' => [
            '400' => 'Lato-Regular',
            '700' => 'Lato-Bold',
        ]
    ]
);

if (is_object($rlSmarty)) {
    $rlSmarty->assign_by_ref('tpl_settings', $tpl_settings);
}

/**
 * @todo Remove in version > 4.9.3
 */
if ($config['single_rtl_css']) {
    $rlDb->query("DELETE FROM `{db_prefix}config` WHERE `Key` = 'single_rtl_css'");
    $rlDb->query("DELETE FROM `{db_prefix}hooks` WHERE `Plugin` = 'singleRTL'");
}
