<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: BOOTSTRAP.PHP
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

// entry point of the plugin
use Autoposter\AutoPosterContainer;

require_once __DIR__ . '/vendor/autoload.php';

$guide_host = 'https://blog.flynax.' . (RL_LANG_CODE == 'ru' ? 'ru' : 'com');

// plugin modules
$apConfig['modules'] = array(
    'facebook' => array(
        'validate' => array(
            'ap_facebook_app_id' => 'required',
            'ap_facebook_app_secret' => 'required',
            'ap_facebook_post_to' => 'required',
        ),
        'custom_settings' => array(
            'ap_facebook_post_to' => array(
                'key' => 'ap_facebook_post_to',
                'type' => 'select',
                'values' => array(
                    array(
                        'name' => $GLOBALS['lang']['ap_to_personal_page'],
                        'Key' => 'to_wall',
                        'ID' => 'to_wall',
                        'Disabled' => true,
                    ),
                    array(
                        'name' => $GLOBALS['lang']['ap_to_business_page'],
                        'Key' => 'to_page',
                        'ID' => 'to_page',
                    ),
                    array(
                        'name' => $GLOBALS['lang']['ap_to_group'],
                        'Key' => 'to_group',
                        'ID' => 'to_group',
                    ),
                ),
            ),
        ),
        'guide_link' => 'https://blog.flynax.com/manuals/setting-up-facebook/'
    ),
    'twitter' => array(
        'validate' => array(
            'ap_twitter_consumer_secret' => 'required',
            'ap_twitter_consumer_key' => 'required',
            'ap_twitter_token_key' => 'required',
            'ap_twitter_token_secret' => 'required',
        ),
        'onSubmit' => 'isValidSettings',
        'guide_link' => 'https://blog.flynax.com/manuals/setting-up-twitter/'
    ),
    'vk' => array(
        'validate' => array(
            'ap_vk_client_id' => 'required',
            'ap_vk_post_to'   => 'required',
            'ap_vk_owner_id'  => 'required',
        ),
        'custom_settings' => array(
            'ap_vk_post_to' => array(
                'key' => 'ap_vk_post_to',
                'type' => 'select',
                'values' => array(
                    array(
                        'name' => $GLOBALS['lang']['ap_to_personal_page'],
                        'Key' => 'to_wall',
                        'ID' => 'to_wall',
                    ),
                    array(
                        'name' => $GLOBALS['lang']['ap_to_group_vk'],
                        'Key' => 'to_group',
                        'ID' => 'to_group',
                    ),
                ),
            ),
        ),
        'guide_link' => 'https://blog.flynax.ru/manuals/avtopublikaciya-obyavlenij-v-vk/'
    ),
    'telegram' => array(
        'validate' => array(
            'ap_telegram_bot_token' => 'required',
            'ap_telegram_chat_id' => 'required',
        ),
        'guide_link' => $guide_host . '/manuals/setting-up-telegram-autoposter/'
    )
);

// system fields in the "Message builder" tool
$apConfig['message_system_field'] = array(
    'ID' => 'customMessage',
    'Key' => 'customMessage',
    'Type' => 'system',
    'Status' => 'active',
    'name' => $GLOBALS['lang']['ap_custom_message_name'],
);

$apConfig['php_min'] = '5.4';
$apConfig['plugin_prefix'] = 'ap_';
$apConfig['admin']['view'] = RL_PLUGINS . 'autoPoster/admin/view/';
$apConfig['front']['view'] = RL_PLUGINS . 'autoPoster/view/';

AutoPosterContainer::setConfig('configs', $apConfig);
