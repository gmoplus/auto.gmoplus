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
    'version' => 1.1,
    'name' => 'general_modern_wide',
    'inventory_menu' => false,
    'right_block' => false,
    'long_top_block' => false,
    'featured_price_tag' => true,
    'ffb_list' => false, //field bound boxes plugins list
    'fbb_custom_tpl' => true,
    'header_banner' => true,
    'header_banner_size_hint' => '728x90',
    'home_page_gallery' => false,
    'listing_details_anchor_tabs' => false,
    'listing_details_nav_mode' => 'h1_mixed',
    'search_on_map_page' => true,
    'browse_add_listing_icon' => true,
    'listing_grid_except_fields' => array('title', 'bedrooms', 'bathrooms', 'square_feet', 'time_frame', 'phone', 'pay_period'),
    'sidebar_sticky_pages' => array('listing_details'),
    'sidebar_restricted_pages' => array('search_on_map'),
    'svg_icon_fill' => true,
    'default_listing_grid_mode' => 'list',
    'listing_grid_mode_only' => false,
    'qtip' => array(
        'background' => '2a5d86',
        'b_color'    => '2a5d86',
        'b_radius'   => 5,
    ),
    'font_family' => [
        'local' => true,
        'name' => 'Roboto Condensed',
        'format' => 'woff2',
        'weights' => [
            '400' => 'RobotoCondensed-Regular',
            '700' => 'RobotoCondensed-Bold',
        ]
    ]
);

if ( is_object($rlSmarty) ) {
    $rlSmarty -> assign_by_ref('tpl_settings', $tpl_settings);
}
