<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLIFLYNAXCONNECT.CLASS.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2024
 *	https://www.flynax.com
 *
 ******************************************************************************/

use Flynax\Plugins\HybridAuth\API as SocialNetworkLoginAPI;
use Hybridauth\Exception\Exception as SocialNetworkException;
use Flynax\Utils\ListingMedia;
use Flynax\Utils\Util;

/**
 * Bridge between iOS device and Flynax website.
 */
class rlIFlynaxConnect
{
    /**
     * listing types available for app
     **/
    public $listing_types;

    /**
     * account types available for app
     **/
    public $account_types;

    /**
     * price field key
     **/
    public $price_key = 'price';

    /**
     * comments stars number
     */
    public $comments_stars_number = 5;

    /**
     * Table field `Token`
     */
    public $aTokenField = 'ios_token';

    /**
     * skip this keys
     **/
    public $skip_app_configs = array(
        'iflynax_synch_code',
        'iflynax_promote_app',
        'iflynax_app_store_id',
        'iflynax_admin_section_id',
        'iflynax_plugin_status',
        'iflynax_plugin_id',
        'iflynax_affiliate_data',
    );

    /**
     * Skip this listing fields keys on "Ad Details" screen
     *
     * @var array
     **/
    public $skip_lfield_keys = array(
        'account_address_on_map',
        'booking_module',
        'price',
        'title',
    );

    /**
     * Skip this listing fields keys on "Create Ad" screen
     *
     * @var array
     */
    public $skip_lfield_keys_add = array(
        'account_address_on_map'
    );

    /**
     * "year build" field key
     **/
    public $year_build_key = 'built';

    /**
     * @var array Storage of installed and active plugins
     */
    public static $active_plugins = array();

    /**
     * @var string - Postfix for search form key's
     *
     * @since 3.4.0
     *
     * @todo Remove it and use both type's
     */
    protected $search_form_type = '_quick';

    /**
     * @var string
     *
     * @since 3.4.0
     */
    protected $search_form_key = null;

    /**
     * @var array
     *
     * @since 3.4.0
     */
    protected $pending_push_notifications = array();

    /**
     * visitor marker
     */
    const WEBSITE_VISITOR = -1;

    /**
     * Search form key fo iOS Nearby Ads screen
     */
    const NEARBY_ADS_SEARCH_FORM = 'ios_nearby_ads';

    /**
     * Save Search sheet action's
     *
     * @since 3.4.0
     */
    const SAVE_SEARCH_ACTION_ACTIVATE   = 1;
    const SAVE_SEARCH_ACTION_DEACTIVATE = 2;
    const SAVE_SEARCH_ACTION_REMOVE     = 3;

    /*** Hooks ***/

    /**
     * @hook tplHeader
     *
     * @since 3.1.0
     **/
    public function hookTplHeader()
    {
        global $config;

        if ($config['iflynax_promote_app']
            && $config['iflynax_app_store_id']
            && preg_match('/(Safari|AppleWebKit)/i', $_SERVER['HTTP_USER_AGENT'])
        ) {
            printf('<meta name="apple-itunes-app" content="%s, %s" />',
                'app-id=' . $config['iflynax_app_store_id'],
                'affiliate-data=' . $config['iflynax_affiliate_data']
            );
        }
    }

    /**
     * @hook listingsModifyWhereFeatured
     *
     * @since 3.1.0
     **/
    public function hookListingsModifyWhereFeatured(&$sql, &$block_key, &$limit)
    {
        if (!defined('IOS_APP')) {
            return;
        }
        $sql = is_null($sql) ? $GLOBALS['sql'] : $sql;

        if (defined('WITH_PICTURES_ONLY') && WITH_PICTURES_ONLY) {
            $sql .= " AND `T1`.`Main_photo` <> '' ";
        }

        if (version_compare($GLOBALS['config']['rl_version'], '4.6.1', '<')) {
            if (is_numeric($block_key) && is_numeric($limit)) {
                $limit = $block_key . ',' . $limit;
                $block_key = false;
            }
        }
    }

    /**
     * @hook listingsModifyWhereByPeriod
     *
     * @since 3.1.0
     **/
    public function hookListingsModifyWhereByPeriod(&$sql)
    {
        if (!defined('IOS_APP')) {
            return;
        }
        $sql = is_null($sql) ? $GLOBALS['sql'] : $sql;

        if (defined('WITH_PICTURES_ONLY') && WITH_PICTURES_ONLY) {
            $sql .= " AND `T1`.`Main_photo` <> '' ";
        }

        $ltype_keys = array_keys($GLOBALS['rlListingTypes']->types);
        $sql .= " AND `T4`.`Type` IN ('" . implode("','", $ltype_keys) . "') ";
    }

    /**
     * @hook apAjaxRequest
     *
     * @since 3.1.0
     **/
    public function hookApAjaxRequest()
    {
        global $item, $out, $rlDb;

        switch ($item) {
            case 'iflynax_admob_remove':
                $admob_id = (int) $_REQUEST['id'];
                $rlDb->query("DELETE FROM `{db_prefix}iflynax_admob` WHERE `ID` = " . $admob_id);

                $out['status'] = 'ok';
                break;
        }
    }

    /**
     * @hook apNotifications
     *
     * @since 3.1.0
     **/
    public function hookApNotifications(&$notifications)
    {
        // $prod_cert_file = dirname(__FILE__) . '/cert/apns-prod-cert.pem';

        // if (false === file_exists($prod_cert_file)) {
        //     $notifications[] = 'The <b>Apple Push Service</b> will not work; required <b>apns-prod-cert.pem</b> file.';
        // }
    }

    /**
     * @hook apMixConfigItem
     *
     * @since 3.2.0
     */
    public function hookApMixConfigItem(&$config)
    {
        if ($config['Key'] == 'iflynax_home_featured_ltype') {
            $config['Values'] = array();

            foreach ($GLOBALS['rlListingTypes']->types as $ltype ) {
                if ($ltype['iFlynax_status'] != 'active') {
                    continue;
                }

                $config['Values'][] = array(
                    'ID' => $ltype['Key'],
                    'name' => $GLOBALS['lang']['listing_types+name+' . $ltype['Key']]
                );
            }
        }
    }

    /**
     * @hook getCatTreeFields
     *
     * @since 3.1.0
     **/
    public function hookGetCatTreeFields(&$sql)
    {
        if (!defined('IOS_APP')) {
            return;
        }
        $sql = is_null($sql) ? $GLOBALS['sql'] : $sql;

        if (!is_numeric(strpos($sql, '`T1`.`Count`'))) {
            $sql .= '`T1`.`Count`, ';
        }
    }

    /**
     * @hook phpGetPlanSql
     *
     * @since 3.1.0
     **/
    public function hookPhpGetPlanSql(&$sql)
    {
        if (defined('IOS_APP') && IOS_APP && !is_numeric(strpos($sql, "`Featured`"))) {
            $sql = str_replace(
                "`T1`.`ID`,",
                "`T1`.`ID`, `T1`.`Featured`, `T1`.`Advanced_mode`, `T1`.`Standard_listings`, `T1`.`Featured_listings`,",
                $sql);

            $sql = str_replace(
                "`T3`.`Listings_remains`",
                "`T3`.`ID` AS `Package_ID`, `T3`.`Listings_remains`, `T3`.`Standard_remains`,
                 `T3`.`Featured_remains`, `T2`.`Listings_remains` AS `Using`, `T2`.`ID` AS `Plan_using_ID` ",
                $sql);
        }
    }

    /**
     * @hook cronSavedSearchNotify
     *
     * @since 3.4.0
     *
     * @param array  $entry    - The entry for which was found listings
     * @param string $listings - Found listings for the entry (1,2,3,etc)
     * @param array  $account  - Account details which save the search
     */
    public function hookCronSavedSearchNotify($entry, $listings, $account)
    {
        $saved_matches = $entry['Matches'] ? explode(',', $entry['Matches']): array();
        $new_matches = explode(',', $listings);
        $recipient = (int) $account['ID'];

        if (!isset($this->pending_push_notifications[$recipient])) {
            $this->pending_push_notifications[$recipient] = array(
                'matches_total' => 0,
                'matches' => array(),
            );
        }
        $notification = &$this->pending_push_notifications[$recipient];

        $saved_matches_count = count($saved_matches);
        $new_matches_count = count($new_matches) - $saved_matches_count;

        // Eclude already saved ads from response to iOS
        $new_matches_uniq = array_diff($new_matches, $saved_matches);

        $notification['matches_total'] += $new_matches_count;
        $notification['matches'][] = array(
            'entry' => intval($entry['ID']),
            'ads' => array_values($new_matches_uniq),
        );
    }

    /**
     * @hook cronAdditional
     *
     * @since 3.4.0
     */
    public function hookCronAdditional()
    {
        if (empty($this->pending_push_notifications)) {
            return;
        }

        global $rlApplePush, $reefless;

        $reefless->loadClass('ApplePush', false, 'iFlynaxConnect');

        $rlApplePush->shouldCloseConnectionManually();
        // $rlApplePush->enableDevMode();

        foreach ($this->pending_push_notifications as $recipient => $data) {
            if (false !== $tokens = $this->fetchAllActiveTokensByAccountId($recipient)) {
                // prepare alert for the push
                $alert_source = $rlApplePush->generateAlertSource(
                    'notification_new_saved_search_results',
                    array('{count}' => $data['matches_total'])
                );

                $push_type = rlApplePush::PUSH_TYPE_SAVED_SEARCH;
                $matches = $data['matches'];
                $badge = count($matches);
                $user_data = $data;
                $user_data['recipientID'] = $recipient;

                if ($data['matches_total'] == 1
                    && $listing_id = (int) array_values($matches[0]['ads'])[0]
                ) {
                    $reefless->loadClass('Listings');

                    $listing = $GLOBALS['rlListings']->getShortDetails($listing_id);
                    $user_data['listing'] = $this->adaptShortFormWithData($listing);
                    $user_data['entry'] = (int) $matches[0]['entry'];
                }

                if (1 === $user_data['matched_entries_count'] = count($matches)) {
                    $user_data['search_filter_ids'] = implode(',', $matches[0]['ads']);
                    $user_data['entry'] = (int) $matches[0]['entry'];
                }

                try {
                    $alert = $rlApplePush->generateLocalizedAlert($alert_source);
                    $payload = $rlApplePush->generatePayloadBody($push_type, $user_data, $badge);
                    $rlApplePush->pushNotifications($tokens, $payload, $alert);
                } catch (Exception $e) {
                    $log_message = "iOS: " . __FUNCTION__ . "(), the exception is the following: " . $e->getMessage();
                    $GLOBALS['rlDebug']->logger($log_message);
                }
            }
        }
        $GLOBALS['rlApplePush']->closeConnection();
    }

    /**
     * @hook listingsModifyWhereSearch
     *
     * @since 3.4.0
     */
    public function hookListingsModifyWhereSearch(&$sql)
    {
        if (!defined('IOS_SAVED_SEARCH_FILTER_IDS')) {
            return;
        }
        $sql .= sprintf(" AND `T1`.`ID` IN(%s) ", IOS_SAVED_SEARCH_FILTER_IDS);
    }

    /**
     * @hook rlAccountGetAccountTypesFields
     *
     * @since 3.4.0
     */
    public function hookRlAccountGetAccountTypesFields(&$fields)
    {
        if (!defined('IOS_APP') || version_compare($GLOBALS['config']['rl_version'], '4.6.2', '<')) {
            return;
        }
        $fields[] = 'Thumb_width';
        $fields[] = 'Thumb_height';
    }

    /**
     * @hook  phpSelectAgreementFields
     * @since 3.5.0
     */
    public function hookPhpSelectAgreementFields(&$sql)
    {
        $sql .= ", `T1`.`Type`, `T1`.`Add_page` ";
    }

    /**
     * @hook apTplHeader
     *
     * @since 3.1.0
     **/
    public function hookApTplHeader()
    {
        global $config;

        $_plugin_static_url = RL_PLUGINS_URL . 'iFlynaxConnect/static/';

        echo <<<HTML
            <style type="text/css">
                div#msection_{$config['iflynax_admin_section_id']} > div.caption > div.icon {
                    background: url("{$_plugin_static_url}gallery.png") 3px 0 no-repeat!important;
                }
                div#msection_{$config['iflynax_admin_section_id']} > div.caption_active > div.icon {
                    background: url("{$_plugin_static_url}gallery.png") 3px -26px no-repeat!important;
                }
            </style>
HTML;
    }

    /**
     * @hook apTplFooter
     *
     * @since 3.1.0
     **/
    public function hookApTplFooter()
    {
        if ($GLOBALS['cInfo']['Key'] == 'search_forms' && $_GET['form'] == self::NEARBY_ADS_SEARCH_FORM) {
            echo <<<HTML
                <script type="text/javascript">
                    $('form > table:first tr:eq(2)').addClass('hide_field');
                    $('form > table:first tr:eq(3)').addClass('hide_field');
                    $('form > table:first tr:eq(4)').addClass('hide_field');
                    $('.hide_field').hide();
                </script>
HTML;
        }
    }

    /**
     * @hook apPhpIndexBeforeController
     *
     * @since 3.1.0
     **/
    public function hookApPhpIndexBeforeController()
    {
        global $cInfo, $config, $mMenuItems;

        if ($config['iflynax_plugin_status'] !== 'active') {
            foreach ($mMenuItems as $index => $item) {
                if ($item['Key'] == 'iflynax') {
                    unset($mMenuItems[$index]);
                    break;
                }
            }
            return;
        }

        if ($cInfo['Parent_ID'] == $config['iflynax_admin_section_id']) {
            $cInfo['Plugin'] = 'iFlynaxConnect';
        }
    }

    /**
     * @hook apPhpIndexBottom
     *
     * @since 3.1.0
     **/
    public function hookApPhpIndexBottom()
    {
        $GLOBALS['extended_sections'][] = 'iflynax_languages';
        $this->breadCrumbs();
    }

    /**
     * @hook apPhpConfigBottom
     *
     * @since 3.1.0
     **/
    public function hookApPhpConfigBottom()
    {
        $config_groups = $GLOBALS['rlSmarty']->get_template_vars('configGroups');

        foreach ($config_groups as $key => $group) {
            if ($group['Plugin'] == 'iFlynaxConnect') {
                unset($config_groups[$key]);
                $GLOBALS['rlSmarty']->assign('configGroups', $config_groups);
                break;
            }
        }
    }

    /**
     * @hook apPhpEmailTemplatesTop
     *
     * @since 3.1.0
     **/
    public function hookApPhpEmailTemplatesTop()
    {
        unset($_SESSION['ios_app']['email_templates']);

        if (isset($_GET['module']) && $_GET['module'] == 'ios_app') {
            $_SESSION['ios_app']['email_templates'] = true;
        }
    }

    /**
     * @hook apExtEmailTemplatesSql
     *
     * @since 3.1.0
     **/
    public function hookApExtEmailTemplatesSql()
    {
        global $sql;

        if ($_SESSION['ios_app']['email_templates']) {
            $sql = str_replace('WHERE', "WHERE `T1`.`Plugin` = 'iFlynaxConnect' AND ", $sql);
        }
    }

    /**
     * @hook apExtSearchFormsData
     *
     * @since 3.1.0
     **/
    public function hookApExtSearchFormsData()
    {
        foreach ($GLOBALS['data'] as $key => &$form) {
            if ($form['Key'] == self::NEARBY_ADS_SEARCH_FORM) {
                $form['Type'] = sprintf('<b>%s</b>', $GLOBALS['lang']['all']);
                $form['With_picture'] = $GLOBALS['lang']['no'];
                $form['Mode'] = '<b>iOS</b>';
                break;
            }
        }
    }

    /**
     * @hook apExtPluginsUpdate
     *
     * @since 3.1.0
     **/
    public function hookApExtPluginsUpdate()
    {
        global $field, $id, $value, $rlDb;

        if ($field == 'Status' && $id === (int) $GLOBALS['config']['iflynax_plugin_id']) {
            if ($value == 'approval') {
                $sql = "UPDATE `{db_prefix}hooks` SET `Status` = 'active' ";
                $sql .= "WHERE `Plugin` = 'iFlynaxConnect'";
                $sql .= "AND `Name` IN ('apPhpIndexBeforeController', 'apExtPluginsUpdate')";
                $rlDb->query($sql);
            }
            $GLOBALS['rlConfig']->setConfig('iflynax_plugin_status', $value);
        }
    }

    /**
     * @since 3.1.0
     */
    public function hookRlMessagesAjaxAfterMessageSent($recipient = 0, $message = null)
    {
        global $account_info, $rlApplePush, $rlSmarty, $reefless;

        if (false !== $tokens = $this->fetchAllActiveTokensByAccountId($recipient)) {
            $reefless->loadClass('ApplePush', false, 'iFlynaxConnect');

            // hat trick to fetch the last message info [website only]. (probably required to implement by another way)
            if (!defined('IOS_APP') && !defined('ANDROID_APP')) {
                $message = end($rlSmarty->_tpl_vars['messages']); // replace function arg
            }

            $message_from = (int) $message['From'];

            if (defined('ANDROID_APP') && $account_info == null) {
                $reefless->loadClass('Account');
                $account_info = $GLOBALS['rlAccount']->getProfile($message_from);
            }

            $alert_source = $rlApplePush->generateAlertSource(
                'notification_new_message_by',
                array('{name}' => $account_info['Full_name'])
            );

            $badge = 1;
            $user_data = array(
                'from' => $message_from,
                'recipientID' => $recipient,
                'admin' => (int) $message['Admin'],
                'sender' => (string) $account_info['Full_name'],
                'message' => (string) $message['Message'],
            );

            // append user photo if available
            if (!empty($account_info['Photo']) && file_exists(RL_FILES . $account_info['Photo'])) {
                $user_data['thumb'] = RL_FILES_URL . $account_info['Photo'];
            }
            unset($message, $account_info, $alert_values);

            // Turn on APNS Sandbox
            if (Util::getClientIP() === '<dev_IP>') {
                $rlApplePush->enableDevMode();
            }

            try {
                $alert = $rlApplePush->generateLocalizedAlert($alert_source);
                $payload = $rlApplePush->generatePayloadBody('messages', $user_data, $badge);
                $rlApplePush->pushNotifications($tokens, $payload, $alert);
            } catch (Exception $e) {
                $log_message = "iOS: " . __FUNCTION__ . "(), the exception is the following: " . $e->getMessage();
                $GLOBALS['rlDebug']->logger($log_message);
            }
        }
    }

    /**
     * @hook rlMessagesAjaxContactOwnerSend
     */
    public function hookRlMessagesAjaxContactOwnerSend($name = null, $email = null, $phone = null, $message = null, $listing_id = 0)
    {
        global $account_info, $lang, $config, $reefless;

        if (!$account_info || !$listing_id || !$config['messages_save_visitor_message']) {
            return;
        }

        $recipient = $GLOBALS['rlDb']->getOne('Account_ID', "`ID` = {$listing_id}", 'listings');

        if (false !== $tokens = $this->fetchAllActiveTokensByAccountId($recipient)) {
            $reefless->loadClass('ApplePush', false, 'iFlynaxConnect');

            $alert_source = $GLOBALS['rlApplePush']->generateAlertSource(
                'notification_new_message_by',
                array('{name}' => $account_info['Full_name'])
            );

            $badge = 1;
            $user_data = array(
                'from' => $account_info['ID'],
                'recipientID' => $recipient,
                'sender' => (string) $account_info['Full_name'],
                'message' => (string) $message,
            );

            // Turn on APNS Sandbox
            if (Util::getClientIP() === '<dev_IP>') {
                $GLOBALS['rlApplePush']->enableDevMode();
            }

            try {
                $alert = $GLOBALS['rlApplePush']->generateLocalizedAlert($alert_source);
                $payload = $GLOBALS['rlApplePush']->generatePayloadBody('messages', $user_data, $badge);
                $GLOBALS['rlApplePush']->pushNotifications($tokens, $payload, $alert);
            } catch (Exception $e) {
                $log_message = "iOS: " . __FUNCTION__ . "(), the exception is the following: " . $e->getMessage();
                $GLOBALS['rlDebug']->logger($log_message);
            }
        }
    }

    /*** Common API functions ***/

    /**
     * Send response to iOS device
     *
     * @param array $response - response array
     **/
    public function send($response)
    {
        // send headers
        header("Content-Type: application/json; charset=UTF-8");
        header("Cache-Control: max-age=10, public");
        header("X-Powered-By: Flynax API");
        header("Pragma: cache");

        // send json data
        echo json_encode($response);
        exit;
    }

    /**
     * DEPRECATED
     * @see rlIFlynaxConnect::cleanString
     **/
    public function pValid($string, $trim = false)
    {
        return $this->cleanString($string, $trim);
    }

    /**
     * cleanString - make a valid string for iPhone parser
     *
     * @param string $string - string for validate
     * @param string $trim - custom char for trim
     */
    public function cleanString($string, $trim = false)
    {
        $_pattern = "~<a[^>]+href\s*=\s*[\x27\x22]?[^\x20\x27\x22\x3E]+[\x27\x22]?[^>]*>(.+?)</a>~is";
        $string = preg_replace($_pattern, '$1', $string);
        $string = preg_replace('~<img.*src="(.*?)".*\/?>~is', '$1', $string);
        $string = preg_replace('~<br\s?style=".*"\s?\/?>~is', '<br />', $string);
        $string = strip_tags($string, '<br>');
        $string = str_replace(array('&quot;', '&rsquo;', '&nbsp;'), array('"', "'", ' '), $string);

        if ($trim !== false) {
            $string = str_replace($trim, '', $string);
        }
        $string = trim($string);

        return $string;
    }

    /**
     * convert date to unix timestamp
     * @param string $data - data in default format
     * @return int - unix timestamp date
     **/
    public function convertDate($data = false)
    {
        return date(str_replace(array('%', 'b'), array('', 'M'), RL_DATE_FORMAT), strtotime($data) < 0
            ? 0
            : strtotime($data)
        );
    }

    /**
     * Load installed and active plugins on website
     *
     * @since 3.2.0
     */
    public function loadActivePluginsList()
    {
        global $rlDb;

        $rlDb->setTable('plugins');
        $entries = $rlDb->fetch(array('Key', 'Version'), array('Status' => 'active'));

        $plugins = array();
        foreach ($entries as $row) {
            $plugins[$row['Key']] = $row['Version'];
        }
        unset($entries);

        // Support for old logic and some plugins which use it
        $GLOBALS['plugins'] = $GLOBALS['aHooks'] = $GLOBALS['rlHook']->aHooks = $plugins;

        self::$active_plugins = $plugins;
    }

    /**
     * Description
     * @param array &$entry
     * @param bool $my_ads_form
     * @return array
     */
    public function adaptShortFormWithData(&$entry, $my_ads_form = false)
    {
        // build section rows
        $listing = array(
            'id'           => (int) $entry['ID'],
            'sellerId'     => (int) $entry['Account_ID'],
            'title'        => (string) $entry['listing_title'],
            'featured'     => (int) $entry['Featured'],
            'photos_count' => (string) $entry['Photos_count'],
            'middle_field'  => '',
            'price'        => '',
        );

        $ltype_allow_photos = (bool) $this->listing_types[$entry['Listing_type']]['Photo'];
        $listing['thumbnail'] = $this->validPhotoForListing($entry, $ltype_allow_photos);

        // set map short info
        if (!empty($entry['Loc_latitude']) && !empty($entry['Loc_longitude'])) {
            $lat = (float) $entry['Loc_latitude'];
            $lng = (float) $entry['Loc_longitude'];

            if ($lat && $lng) {
                $listing += array(
                    'map' => array(
                        'lat' => $lat,
                        'lng' => $lng,
                        'title' => (string) $entry['Loc_address'],
                    ),
                );
            }
        }

        if ($my_ads_form) {
            global $lang;

            $listing += array(
                'category_name' => (string) $lang[$entry['Cat_key']],
                'category_id'   => (int) $entry['Category_ID'],
                'plan_name'     => (string) $lang[$entry['Plan_key']],
                'plan_id'       => (int) $entry['Plan_ID'],
                'status'        => (string) $entry['Status'],
                'views'         => (int) $entry['Shows'],
            );
            $listing['last_step'] = (string) $entry['Last_step'];

            if ($entry['Featured_expire']) {
                $listing['featured'] = 1;
            }

            $listing['sub_status'] = (string) $lang[in_array($entry['Sub_status'], array('visible', 'invisible'))
                ? 'ls_' . $entry['Sub_status']
                : 'lsl_' . $entry['Sub_status']];

            $_system_fields = array('Date', 'Pay_date', 'Featured_date', 'Plan_expire', 'Featured_expire');
            foreach ($_system_fields as $sf_key) {
                if (array_key_exists($sf_key, $entry) && !empty($entry[$sf_key])) {
                    $listing[$sf_key] = $this->convertDate($entry[$sf_key]);
                }
            }
        }

        if (!empty($entry['fields'])) {
            /* set price if exists */
            if (array_key_exists($this->price_key, $entry['fields'])) {
                $price_key = $this->price_key;

                if (!empty($entry['fields'][$price_key]['value'])) {
                    $listing['price'] = $entry['fields'][$price_key]['value'];
                }
                unset($entry['fields'][$price_key]);
            }
            /* set price if exists END */

            $iteration = 1;
            foreach ($entry['fields'] as $field_value) {
                // set title
                if ($iteration == 1 && empty($entry['listing_title'])) {
                    $listing['title'] = $field_value['value'];
                }
                // set middle field
                elseif ($iteration == 2 && !$my_ads_form) {
                    if ('number' == $field_value['Type']) {
                        $listing['middle_field'] = "{$this->cleanString($field_value['name'])}: ";
                    }
                    $listing['middle_field'] .= $this->cleanString($field_value['value']);
                    break;
                }
                $iteration++;
            }
        }
        return $listing;
    }

    /**
     * @deprecated 3.7.1
     */
    public function getListingTypes()
    {}

    public function isRemovePaidPlans()
    {
        return (bool) !$GLOBALS['config']['iflynax_inapp_module'];
    }

    /**
     * Get plans
     **/
    public function getPlans($account_id = false, $category_id = false, $account_type = false, $featured_only = false, $plan_id = false)
    {
        global $config, $reefless;

        $response = array();

        if (!$account_id) {
            $GLOBALS['rlDebug']->logger("iOS: " . __FUNCTION__ . "(), no account_id received, abort");
            return $response;
        }

        if (!$category_id || !$account_type) {
            $GLOBALS['rlDebug']->logger("iOS: " . __FUNCTION__ . "(), no category_id or account_type received, abort");
            return $response;
        }

        $reefless->loadClass('Plan');
        $plans = $GLOBALS['rlPlan']->getPlanByCategory($category_id, $account_type, $featured_only);

        foreach ($plans as $key => &$plan) {
            if ($this->isRemovePaidPlans()
                && $plan['Price'] > 0
                && !($plan['Package_ID'] && $plan['Listings_remains'] > 0)) {
                unset($plans[$key]);
                continue;
            }

            $this->setValidInt($plan['Listings_remains']);
            $this->setValidInt($plan['Standard_remains']);
            $this->setValidInt($plan['Featured_remains']);
            $this->setValidInt($plan['Plan_using_ID']);
            $this->setValidInt($plan['Package_ID']);
            $this->setValidInt($plan['Period']);
            $this->setValidInt($plan['Limit']);

            if ($plan['Limit'] && is_null($plan['Using'])) {
                $plan['Using'] = (int) $plan['Limit'];
            } else {
                $this->setValidInt($plan['Using']);
            }

            $plan['currencyCode'] = (string) $config['iflynax_billing_currency'];
            $plan['typeShortName'] = (string) $GLOBALS['lang'][$plan['Type'] . '_plan_short'];
            $plan['inAppKey'] = 'listing.plan.' . $plan['Key'];

            // unused in the app yet
            unset($plan['des'], $plan['Subscription']);

            // for "Make Payment" option only
            if ($plan_id && $plan['ID'] == $plan_id) {
                return $plan;
            }
            $response[] = $plan;
        }
        unset($plans);

        return $response;
    }

    public function validateTransaction($account_id = false, $payment = array())
    {
        $response = array('success' => false);

        if (!$account_id) {
            $GLOBALS['rlDebug']->logger("IOS: " . __FUNCTION__ . "(), no account_id received, abort");
            return $response;
        }

        if (!$payment['item'] || !$payment['plan'] || !$payment['id'] || !$payment['amount']) {
            $_log = "IOS: " . __FUNCTION__ . "(), no enough payment details for payment: " . print_r($payment, true);
            $GLOBALS['rlDebug']->logger($_log);
            return $response;
        }

        switch ($payment['gateway']) {
            case 'apple':
                require_once RL_IPHONE_GATEWAYS . 'apple.gateway.php';
                $gateway = new appleInAppPurchase($payment, $account_id);
                break;

            default:
                $_log = "IOS: " . __FUNCTION__ . "(), unknown payment geteway request: {$payment['gateway']}";
                $GLOBALS['rlDebug']->logger($_log);
                return $response;
        }

        if ($gateway->approved) {
            switch ($payment['item']) {
                case 'listing':
                case 'package':
                case 'featured':
                    $success = $this->upgradeListing($account_id, $gateway->transactionId, $payment);
                    $response['success'] = $success;
                    break;

                default:
                    $GLOBALS['rlDebug']->logger("IOS: " . __FUNCTION__ . "(), unknown payment item request: {$payment['item']}");
                    break;
            }
        } else {
            $response['errors'] = $gateway->errors;
        }
        return $response;
    }

    /**
     * upgrade free or existing package plan
     *
     * @param int $account_id - requested account id
     * @param int $listing_id - requested listing id
     * @param int $plan_id - requested plan id
     * @param string $listing_mode - listing appearance: standard or featured
     *
     * @return bool
     **/
    public function upgradePlan($account_id = false, $listing_id = false, $plan_id = false, $listing_mode = 'standard')
    {
        global $rlHook, $config, $account_info, $reefless, $rlDb;

        $reefless->loadClass('Plan');
        $reefless->loadClass('Mail');
        $reefless->loadClass('Common');
        $reefless->loadClass('Listings');
        $reefless->loadClass('Categories');

        if (!$account_id) {
            $GLOBALS['rlDebug']->logger("IOS: " . __FUNCTION__ . "(), no account_id received, abort");
            return false;
        }

        if (!$listing_id || !$plan_id) {
            $GLOBALS['rlDebug']->logger("IOS: " . __FUNCTION__ . "(), no listing_id or plan_id received");
            return false;
        }

        // get listing details
        $sql = "SELECT `T1`.*, `T1`.`Category_ID`, `T1`.`Status`, UNIX_TIMESTAMP(`T1`.`Pay_date`) AS `Pay_date`, `T1`.`Crossed`, ";
        $sql .= "`T2`.`Type` AS `Listing_type`, `T2`.`Path` AS `Category_path`, `T1`.`Last_type` AS `Listing_mode` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = {$listing_id} AND `T1`.`Account_ID` = '{$account_id}' ";

        $rlHook->load('upgradeListingSql', $sql);

        $sql .= "LIMIT 1";
        $listing = $rlDb->getRow($sql);

        if (!$listing) {
            $GLOBALS['rlDebug']->logger("IOS: " . __FUNCTION__ . "(), no listing found with id = {$listing_id}, account_id = {$account_id}");
            return false;
        }

        // get plan info
        $plan_info = $GLOBALS['rlPlan']->getPlan($plan_id, $account_id);
        // $this->send($plan_info);

        if (!$plan_info) {
            $GLOBALS['rlDebug']->logger("IOS: " . __FUNCTION__ . "(), no plan found with id = {$plan_id}");
            return false;
        }

        // check limited plans using
        if (!is_null($plan_info['Using']) && $plan_info['Using'] <= 0 && $plan_info['Limit'] > 0) {
            $GLOBALS['rlDebug']->logger("IOS: " . __FUNCTION__ . "(), the limited plan #{$plan_info['ID']} isn't avaiable for account # {$account_id}");
            return false;
        }

        // check rest listings using
        if (!is_null($plan_info['Package_ID']) && $listing_mode && ($plan_info[ucfirst($listing_mode) . '_remains'] <= 0 && $plan_info[ucfirst($listing_mode) . '_listings'] > 0)) {
            $GLOBALS['rlDebug']->logger("IOS: " . __FUNCTION__ . "(), the package plan #{$plan_info['ID']} isn't avaiable for account # {$account_id}");
            return false;
        }

        $listing_title = $GLOBALS['rlListings']->getListingTitle($listing['Category_ID'], $listing, $listing['Listing_type']);

        /* Upgrade to featured MODE */
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
            // $this->send($update);

            if ($rlDb->updateOne($update, 'listings')) {
                /* limited option handler */
                if ($plan_info['Limit'] > 0) {
                    if (is_null($plan_info['Using']) || $plan_info['Using'] == '') {
                        $plan_using_insert = array(
                            'Account_ID' => $account_info['ID'],
                            'Plan_ID' => $plan_info['ID'],
                            'Listings_remains' => $plan_info['Limit'] - 1,
                            'Type' => 'limited',
                            'Date' => 'NOW()',
                            'IP' => Util::getClientIP(),
                        );
                        $GLOBALS['rlActions']->insertOne($plan_using_insert, 'listing_packages');
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
                        $rlDb->updateOne($plan_using_update, 'listing_packages');
                    }
                }

                /* send notification to listing owner */
                $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('listing_upgraded_to_featured');

                $lt_page_path = $rlDb->getOne('Path', "`Key` = 'lt_{$listing['Listing_type']}'", 'pages');

                $link = SEO_BASE;
                $link .= $config['mod_rewrite']
                ? $lt_page_path . '/' . $listing['Category_path'] . '/' . $GLOBALS['rlValid']->str2path($listing_title) . '-' . $listing_id . '.html'
                : '?page=' . $lt_page_path . '&amp;id=' . $listing_id;

                $find = array('{name}', '{listing}', '{plan_name}', '{plan_price}', '{start_date}', '{expiration_date}');
                $replace = array(
                    $account_info['Full_name'],
                    '<a href="' . $link . '">' . $listing_title . '</a>',
                    $plan_info['name'],
                    $GLOBALS['lang']['free'],
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
        }
        /* Upgrade to featured MODE END */
        else {
            $update_featured_id = ($plan_info['Featured'] && !$plan_info['Advanced_mode']) || $listing_mode == 'featured' ? $plan_info['ID'] : '';
            $update_featured_date = ($plan_info['Featured'] && !$plan_info['Advanced_mode']) || $listing_mode == 'featured' ? 'IF(UNIX_TIMESTAMP(NOW()) > UNIX_TIMESTAMP(DATE_ADD(`Featured_date`, INTERVAL ' . $plan_info['Listing_period'] . ' DAY)) OR IFNULL(UNIX_TIMESTAMP(`Featured_date`), 0) = 0, NOW(), DATE_ADD(`Featured_date`, INTERVAL ' . $plan_info['Listing_period'] . ' DAY))' : '';
            $update_date = 'IF(UNIX_TIMESTAMP() > UNIX_TIMESTAMP(DATE_ADD(`Pay_date`, INTERVAL ' . $plan_info['Listing_period'] . ' DAY)) OR IFNULL(UNIX_TIMESTAMP(`Pay_date`), 0) = 0, NOW(), DATE_ADD(`Pay_date`, INTERVAL ' . $plan_info['Listing_period'] . ' DAY))';

            $update = array(
                'fields' => array(
                    'Plan_ID' => $plan_info['ID'],
                    'Pay_date' => $update_date,
                    'Featured_ID' => $update_featured_id,
                    'Featured_date' => $update_featured_date,
                    'Last_type' => $listing_mode,
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

            if ($rlDb->updateOne($update, 'listings')) {
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
                        $rlDb->updateOne($update_entry, 'listing_packages');
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

                    $rlDb->insertOne($insert_entry, 'listing_packages');
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

                        $rlDb->insertOne($plan_using_insert, 'listing_packages');
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

                        $rlDb->updateOne($plan_using_update, 'listing_packages');
                    }
                }

                /* update listing images count if plan allows less photos then previous plan */
                if (!$plan_info['Image_unlim'] && $plan_info['Image'] < $listing['Photos_count'] && $plan_info['Type'] != 'featured') {
                    $photos_count_update = array(
                        'fields' => array(
                            'Photos_count' => $plan_info['Image'],
                        ),
                        'where' => array(
                            'ID' => $listing['ID'],
                        ),
                    );

                    $rlDb->updateOne($photos_count_update, 'listings');
                }

                /* recount category listings count */
                if ($config['listing_auto_approval']) {
                    $GLOBALS['rlCategories']->listingsIncrease($listing['Category_ID']);
                    $GLOBALS['rlCategories']->accountListingsIncrease($account_id);
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
        return true;
    }

    public function upgradeListing($account_id, $transaction_id, $payment)
    {
        global $rlListings, $reefless, $rlDb;

        $reefless->loadClass('Listings');

        /**
         * @since Flynax v4.4.x
         **/
        if (file_exists(RL_CLASSES . 'rlPayment.class.php')) {
            $reefless->loadClass('Payment');
            global $rlPayment;

            // plan_id to plan_key
            $plan_key = $rlDb->getOne('Key', "`ID` = " . $payment['plan'], 'listing_plans');

            $rlPayment->clear();
            $rlPayment->setOption('service', $payment['item']);
            $rlPayment->setOption('total', $payment['amount']);
            $rlPayment->setOption('plan_id', $payment['plan']);
            $rlPayment->setOption('item_id', $payment['id']);
            $rlPayment->setOption('item_name', $payment['title']);
            $rlPayment->setOption('plan_key', 'listing_plans+name+' . $plan_key);
            $rlPayment->setOption('account_id', $account_id);
            $rlPayment->setGateway($payment['gateway']);
            $rlPayment->createTransaction();

            $data = array(
                'plan_id' => $payment['plan'],
                'item_id' => $payment['id'],
                'account_id' => $account_id,
                'total' => $payment['amount'],
                'txn_id' => $rlPayment->getTransactionID(),
                'txn_gateway' => $transaction_id,
            );
            $rlPayment->complete($data, 'rlListings', 'upgradeListing', $payment['featured']);
        } else {
            $rlListings->upgradeListing(
                $payment['id'],
                $payment['plan'],
                $account_id,
                $transaction_id,
                $payment['gateway'],
                $payment['amount']
            );
        }
        return true;
    }

    //
    public function setValidInt(&$number)
    {
        $number = is_numeric($number) ? (int) $number : 0;
    }

    /**
     * Check for unique value and add postfix after if need | recursive method
     *
     * @param string $field - table field to check
     * @param string $table - table name to check
     * @param string $value - value to check
     *
     * @return string - new or given unique value
     **/
    public function uniqueValue($field = false, $table = false, $value = null)
    {
        global $rlDb, $reefless;

        if (!$field || !$table || !$value) {
            return $value;
        }

        if ($rlDb->getOne($field, "`{$field}` = '{$value}'", $table)) {
            $value .= $reefless->generateHash(1, 'numbers', true);
            return $this->uniqueValue($field, $table, $value);
        }
        return $value;
    }

    /**
     * Get valid categories by listing type
     *
     * @param string $type
     * @param int $parent
     * @param bool $mf_value
     * @param bool $all_categories
     *
     * @return array
     */
    public function getCategories($type = null, $parent = 0, $mf_value = false, $all_categories = false)
    {
        global $reefless;

        $categories = array();

        if (!$type || !is_numeric($parent)) {
            return $categories;
        }

        $reefless->loadClass('Categories');

        $fetch_func = $all_categories === true ? 'getCatTree' : 'getCategories';
        $entries = $GLOBALS['rlCategories']->$fetch_func($parent, $type);

        foreach ($entries as $category) {
            if ($mf_value) {
                $categories[] = array(
                    'key' => $category['ID'],
                    'name' => $this->trueNameOrKeyInstead($category['name'], $category['Key']),
                );
            } else {
                $categories[] = array(
                    'id' => (int) $category['ID'],
                    'key' => $category['Key'],
                    'path' => $category['Path'],
                    'name' => $this->trueNameOrKeyInstead($category['name'], $category['Key']),
                    'level' => (int) $category['Level'],
                    'count' => (int) ($category['Count'] ?: 0),
                    'lock' => (int) $category['Lock'],
                    'childrens' => !empty($category[$all_categories ? 'Sub_cat' : 'sub_categories']),
                    'icon' => $category['Icon'] ? RL_FILES_URL . $category['Icon'] : '',
                    'subCategories' => array(), // DEPRECATED,
                );
            }
        }
        unset($entries);

        return $categories;
    }

    /**
     * Return key if name is empty
     */
    public function trueNameOrKeyInstead($name, $key)
    {
        if (is_string($name) && $GLOBALS['rlCommon']->strLen($name, '>', 0)) {
            return $name;
        }
        return (string) $key;
    }

    /**
     * get listings by category id
     *
     * @param int $id - category ID
     * @param int $stack
     * @param string $listing_type - listing type key
     **/
    public function getListingsByCategory($id = false, $stack = 1, $listing_type = false, $sort = false)
    {
        global $sorting, $rlListings, $order_field, $reefless;

        $response = array(
            'listings' => array(),
            'sorting' => array(),
            'calc' => 0,
        );

        if (!$id || !$listing_type) {
            return $response;
        }

        $sort_field = false;
        $sort_type = 'ASC';
        $limit = (int) $GLOBALS['config']['iflynax_grid_listings_number'];

        $reefless->loadClass('Listings');

        // get sorting fields
        $sorting = $rlListings->getFormFields($id, 'short_forms', $listing_type);

        // apply sorting filter
        if ($sort && array_key_exists($sort['by'], $sorting)) {
            $sort_field = $sort['by'];
            $order_field = $sort_field;
            $sort_type = strtoupper($sort['type']);
        }

        // fetch available listings
        $listings = $rlListings->getListings($id, $sort_field, $sort_type, $stack, $limit);

        if (!empty($listings)) {
            // adapt listings array
            foreach ($listings as $listing) {
                $response['listings'][] = $this->adaptShortFormWithData($listing);
            }
            $response['calc'] = (int) $rlListings->calc;

            // adapt sorting array
            if ($sorting && !$sort_field) {
                $arrange_types = array('mixed', 'price', 'number');

                foreach ($sorting as &$field) {
                    if (!$field['Details_page']) {
                        unset($field);
                        continue;
                    }

                    $_arrange = in_array($field['Type'], $arrange_types);
                    $sname = $_arrange
                    ? $field['name'] . sprintf(' (%s)', $GLOBALS['lang']['ascending'])
                    : $field['name'];

                    $response['sorting'][] = array(
                        'title' => (string) $sname,
                        'stype' => 'asc',
                        'skey' => $field['Key'],
                    );

                    if ($_arrange) {
                        $response['sorting'][] = array(
                            'title' => sprintf('%s (%s)', $field['name'], $GLOBALS['lang']['descending']),
                            'stype' => 'desc',
                            'skey' => $field['Key'],
                        );
                    }
                }
            }

            // clear memory
            unset($listings, $sorting);
        }

        // load sub-categories only for first stack
        if ($stack == 1) {
            $response['categories'] = $this->getCategories($listing_type, $id);
        }

        return $response;
    }

    /**
     * Description
     * @param type $id
     * @param type $start
     */
    public function getListingsByAccount($id = false, $start = 1)
    {
        global $rlListings, $reefless;

        $response = array(
            'listings' => array(),
            'calc' => 0,
        );

        $reefless->loadClass('Listings');
        $limit = (int) $GLOBALS['config']['iflynax_grid_listings_number'];
        $tmp_listings = $rlListings->getListingsByAccount($id, false, false, $start, $limit);

        if (!empty($tmp_listings)) {
            foreach ($tmp_listings as $entry) {
                $response['listings'][] = $this->adaptShortFormWithData($entry);
            }
            $response['calc'] = $rlListings->calc;
            unset($tmp_listings);
        }
        return $response;
    }

    /**
     * Get account types
     *
     * @param array $response - response with account types
     */
    public function getAccountTypes(&$response)
    {
        global $rlAccount, $rlLang, $rlCommon, $config, $reefless;

        $reefless->loadClass('Account');

        $except_type_keys = array('visitor', 'affiliate');
        $account_types = $this->account_types = $rlAccount->getAccountTypes($except_type_keys);
        $position = 1;

        foreach ($account_types as $type) {
            $fields = array();

            if ($config['iflynax_registration_2step']) {
                $account_fields = $rlAccount->getFields($type['ID']);
                $account_fields = $rlLang->replaceLangKeys($account_fields, 'account_fields', array('name'));
                $account_fields = $rlCommon->fieldValuesAdaptation($account_fields, 'account_fields');

                $fields += $account_fields;
                unset($account_fields);
            }

            if (method_exists($rlAccount, 'getAgreementFields')) {
                $fields += $rlAccount->getAgreementFields($type['Key']);
            }

            $info = array();
            $fields = $this->adaptForm($fields, $info, false, 'account_fields');

            $account_type = array(
                /* @since 3.6.0 - 'id' added */
                'id' => (int) $type['ID'],
                'key' => (string) $type['Key'],
                'name' => (string) $type['name'],
                'page' => (bool) $type['Page'],
                'ownLocation' => (string) $type['Own_location'],
                'emailConfirmation' => (bool) $type['Email_confirmation'],
                'quickRegistration' => (bool) $type['Quick_registration'],
                'autoLogin' => (bool) $type['Auto_login'],
                'position' => $position++,
                'fields' => $fields,
            );

            if (version_compare($config['rl_version'], '4.6.2', '>=')) {
                $account_type['thumbnail'] = array(
                    'width'  => (int) $type['Thumb_width'],
                    'height' => (int) $type['Thumb_height'],
                );
            }

            $response['account_types'][$type['Key']] = $account_type;
        }
    }

    /**
     * Get listings by LatLng
     *
     * @param string $type - listing type
     * @param array $coordinates - location coordinates
     * @param int $limit - limit of listings
     **/
    public function getListingsByLatLng($type = false, $coordinates = array(), $limit = 500)
    {
        global $sql, $rlListings, $reefless, $rlDb;

        $response = array(
            'calc' => 0,
            'listings' => array(),
        );

        // convert array keys to variables
        if (!empty($coordinates) && is_array($coordinates)) {
            $centerLat    = (double) $coordinates['centerLat'];
            $centerLng    = (double) $coordinates['centerLng'];
            $southWestLat = (double) $coordinates['southWestLat'];
            $southWestLng = (double) $coordinates['southWestLng'];
            $northEastLat = (double) $coordinates['northEastLat'];
            $northEastLng = (double) $coordinates['northEastLng'];
        } else {
            return $response;
        }

        $reefless->loadClass('Listings');

        $sql = "SELECT `T1`.*, `T3`.`Key` AS `Key`, `T3`.`Type` AS `Listing_type`, ";

        $GLOBALS['rlHook']->load('listingsModifyField');

        $sql .= "ROUND(3956 * 2 * ASIN(SQRT(
            POWER(SIN((" . $centerLat . " - `T1`.`Loc_latitude`) * 0.0174532925 / 2), 2) +
            COS(" . $centerLat . " * 0.0174532925) *
            COS(`T1`.`Loc_latitude` * 0.0174532925) *
            POWER(SIN((" . $centerLng . " - `T1`.`Loc_longitude`) * 0.0174532925 / 2), 2)
        )), 3) AS `ios_distance` ";

        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T7` ON `T1`.`Account_ID` = `T7`.`ID` ";

        $GLOBALS['rlHook']->load('listingsModifyJoin');

        $sql .= "WHERE `T1`.`Status` = 'active' ";
        $sql .= "AND (`T1`.`Loc_latitude`  BETWEEN " . $southWestLat . " AND " . $northEastLat . ") ";
        $sql .= "AND (`T1`.`Loc_longitude` BETWEEN " . $southWestLng . " AND " . $northEastLng . ") ";

        if ($type) {
            $sql .= "AND `T3`.`Type` = '" . $type . "' ";
        }

        $sql .= "AND `T1`.`Status` = 'active' AND `T7`.`Status` = 'active' ";

        $GLOBALS['rlHook']->load('listingsModifyWhere');
        $GLOBALS['rlHook']->load('listingsModifyGroup');

        if (false === strpos($sql, 'GROUP BY')) {
            $sql .= " GROUP BY `T1`.`ID` ";
        }

        $sql .= "ORDER BY `ID` DESC ";
        $sql .= "LIMIT " . $limit;

        $listings = $rlDb->getAll($sql);

        if (empty($listings)) {
            return $response;
        }

        // adapt listings for response
        foreach ($listings as $key => $value) {
            $fields = $rlListings->getFormFields($value['Category_ID'], 'short_forms', $value['Listing_type']);

            foreach ($fields as $fKey => $field) {
                if ($field['Condition'] == 'isUrl' || $field['Condition'] == 'isEmail') {
                    $fields[$fKey]['value'] = (string) $listings[$key][$fKey];
                } else {
                    $fields[$fKey]['value'] = $GLOBALS['rlCommon']->adaptValue($field, $value[$fKey], 'listing', $value['ID']);
                }
            }

            $listings[$key]['fields'] = $fields;
            $listings[$key]['listing_title'] = $rlListings->getListingTitle($value['Category_ID'], $value, $value['Listing_type']);

            $listings[$key] = $this->adaptShortFormWithData($listings[$key]);
            $listings[$key]['middle_field'] = sprintf('%.1f Mi', (float) $value['ios_distance']);
        }

        $response = array(
            'calc' => count($listings),
            'listings' => (array) $listings,
        );
        unset($listings);

        return $response;
    }

    /**
     * Description
     * @param type $type
     * @param type $stack
     * @param type $char
     */
    public function getAccountsByType($type = false, $stack = 1, $char = false)
    {
        global $reefless, $rlDb;

        if (!$type) {
            return array();
        }

        $reefless->loadClass('Account');

        $type_info = $rlDb->getRow("
            SELECT `ID`, `Key`, `Alphabetic_field` FROM `{db_prefix}account_types`
            WHERE `Key` = '{$type}' LIMIT 1
        ");

        $sorting = array();
        $limit = (int) $GLOBALS['config']['iflynax_grid_listings_number'];
        $accounts = $GLOBALS['rlAccount']->getDealersByChar($char, $limit, $stack, $type_info, $sorting);

        return $this->prepareAccounts($accounts, $GLOBALS['rlAccount']->calc_alphabet);
    }

    public function keywordSearch($query, $stack)
    {
        global $rlSearch, $reefless;

        $reefless->loadClass('Common');
        $reefless->loadClass('Listings');
        $reefless->loadClass('Search');

        $response = array(
            'listings' => array(),
            'calc' => 0,
        );

        $rlSearch->fields = array(
            'keyword_search' => array(
                'Type' => 'text',
            ),
        );

        $search_data = array(
            'keyword_search' => $query,
            'keyword_search_type' => 1,
        );

        $limit = (int) $GLOBALS['config']['iflynax_grid_listings_number'];
        $listings = $rlSearch->search($search_data, false, $stack, $limit);

        if ($listings) {
            foreach ($listings as $listing) {
                $response['listings'][] = $this->adaptShortFormWithData($listing);
            }
            $response['calc'] = $rlSearch->calc;

            // clear memory
            unset($listings);
        }
        return $response;
    }

    /**
     * Get Saved Seach entries for account
     *
     * @since 3.4.0
     *
     * @param  int $account_id
     * @return array
     */
    public function getMySavedSearch($account_id)
    {
        global $lang, $rlCommon, $rlLang, $rlDb;

        $response = array();

        if (!$account_id) {
            return $response;
        }

        $rlDb->setTable('saved_search');
        $entries = $rlDb->fetch(
            array('ID', 'Content', 'Date', 'Listing_type', 'Status'),
            array('Account_ID' => $account_id),
            'ORDER BY `ID` DESC'
        );

        if (empty($entries)) {
            return $response;
        }

        if (!is_array($this->account_types)) {
            $except_type_keys = array('visitor', 'affiliate');
            $this->account_types = $GLOBALS['rlAccount']->getAccountTypes($except_type_keys);
        }

        /* fetch listing fields */
        $rlDb->setTable('listing_fields');
        $listing_fields_tmp = $rlDb->fetch(array('Key', 'Type', 'Condition', 'Default'), array('Status' => 'active'));

        // TODO: Move code below to a class/method; to make life easier...
        $listing_fields = array();
        foreach ($listing_fields_tmp as $index => $row) {
            $listing_fields[$row['Key']] = $row;
            unset($listing_fields_tmp[$index]);
        }
        $listing_fields = $rlLang->replaceLangKeys($listing_fields, 'listing_fields', array('name'));
        /* fetch listing fields end */

        foreach ($entries as $index => $entry) {
            $content = unserialize($entry['Content']);

            $response[$index] = array(
                'id' => (int) $entry['ID'],
                'status' => (string) $entry['Status'],
                'date' => $this->convertDate($entry['Date']),
                'title' => $this->cleanString($lang['listing_types+name+' . $entry['Listing_type']]),
                'subtitle' => '',
            );
            $response_subtitle = array();

            foreach ($content as $key => $value) {
                if (!isset($listing_fields[$key])) {
                    continue;
                }

                $field = array(
                    'type' => $listing_fields[$key]['Type'],
                    'key' => $listing_fields[$key]['Key'],
                    'name' => $this->cleanString($listing_fields[$key]['name']),
                );

                switch ($field['type']) {
                    case 'mixed':
                        if (empty($listing_fields[$key]['Condition'])) {
                            $df = $lang['listing_fields+name+' . $value['df']];
                        } else {
                            $df = $lang['data_formats+name+' . $value['df']];
                        }
                        $field['value'] = sprintf('%s%s', $value['value'], $df);
                        break;

                    case 'date':
                        $field['value'] = $value['value'];
                        break;

                    case 'number':
                        $field['value'] = sprintf('%s - %s', $value['from'], $value['to']);
                        break;

                    case 'price':
                        if ($value['from'] && $value['to']) {
                            $field['value'] = sprintf(
                                '%s %s - %s',
                                $field['name'],
                                $this->priceWithCurrency($value['from'], $value['currency']),
                                $this->priceWithCurrency($value['to'], $value['currency'])
                            );
                        } elseif ($value['from'] && !$value['to']) {
                            $field['value'] = sprintf(
                                '%s from %s',
                                $field['name'],
                                $this->priceWithCurrency($value['from'], $value['currency'])
                            );
                        } elseif (!$value['from'] && $value['to']) {
                            $field['value'] = sprintf(
                                '%s to %s',
                                $field['name'],
                                $this->priceWithCurrency($value['to'], $value['currency'])
                            );
                        }
                        break;

                    case 'unit':
                        $field['value'] = sprintf(
                            '%s%s',
                            $value['value'],
                            $lang['data_formats+name+' . $value['unit']]
                        );
                        break;

                    case 'checkbox':
                        $fieldValue = implode(',', $value);
                        $field['value'] = $rlCommon->adaptValue($listing_fields[$key], $fieldValue);
                        break;

                    default:
                        if ($field['key'] == 'Category_ID') {
                            $cat_name = $rlDb->fetch(array('Key'), array('ID' => $value), null, 1, 'categories', 'row');
                            $response[$index]['title'] .= ' / ' . $lang['categories+name+' . $cat_name['Key']];
                            continue 2;
                        } elseif ($field['key'] == 'posted_by') {
                            $account_type = $GLOBALS['rlAccount']->getTypeDetails($value);
                            $field['value'] = $account_type != '' ? $account_type['name'] : ucfirst($value);
                        } elseif ($listing_fields[$key]['Condition'] == 'years') {
                            $field['value'] = sprintf('%s - %s', $value['from'], $value['to']);
                        } else {
                            $field['value'] = $rlCommon->adaptValue($listing_fields[$key], $value);
                        }
                        break;
                }

                if ('' !== $field['value'] = $this->cleanString($field['value'])) {
                    $response_subtitle[] = $field['value'];
                }
            }

            $response[$index]['subtitle'] = implode(', ', $response_subtitle);
            unset($entries[$index]);
        }
        return $response;
    }

    /**
     * Sheet actions handler
     *
     * @since 3.4.0
     *
     * @param  int $action
     * @param  int $action_id
     * @param  int $account_id
     * @return array
     */
    public function actionSavedSearch($action, $action_id, $account_id)
    {
        global $rlDb;

        $response = array(
            'success' => false,
        );

        if (!$account_id) {
            return $response;
        }

        switch ($action) {
            case self::SAVE_SEARCH_ACTION_ACTIVATE:
            case self::SAVE_SEARCH_ACTION_DEACTIVATE:
                $status = ($action == self::SAVE_SEARCH_ACTION_ACTIVATE ? 'active' : 'approval');
                $rlDb->query(
                    "UPDATE `{db_prefix}saved_search` SET `Status` = '{$status}' WHERE `ID` = {$action_id}"
                );
                $response['success'] = true;
                break;

            case self::SAVE_SEARCH_ACTION_REMOVE:
                $rlDb->query("DELETE FROM `{db_prefix}saved_search` WHERE `ID` = {$action_id}");
                $response['success'] = true;
                break;
        }
        return $response;
    }

    /**
     * Save search
     *
     * @since 3.4.0
     *
     * @param  int    $account_id
     * @param  string $type       - Listing type key
     * @param  array  $form_data
     * @return array
     */
    public function saveSearch($account_id, $type, $form_data)
    {
        global $lang, $rlDb;

        if (!$account_id || !$type) {
            return array('failure' => $lang['dialog_unable_save_data_on_server']);
        }
        $content = array();

        foreach ($form_data as $key => $item) {
            if ($form_data[$key]['distance'] && !$form_data[$key]['zip']) {
                continue;
            }
            $content[$key] = $item;
        }

        if (!empty($content)) {
            $content = serialize($content);

            $exist = $rlDb->getOne('ID', "`Account_ID` = {$account_id} AND `Content` = '{$content}'", 'saved_search');

            if (empty($exist)) {
                $form_key = $type . $this->search_form_type;
                $insert = array(
                    'Account_ID' => $account_id,
                    'Form_key' => $form_key,
                    'Listing_type' => $type,
                    'Content' => $content,
                    'Date' => 'NOW()',
                );

                $rlDb->rlAllowHTML = true;
                $rlDb->insertOne($insert, 'saved_search');

                return array('success' => $lang['search_saved']);
            } else {
                return array('failure' => $lang['search_already_saved']);
            }
        }
        return array('failure' => $lang['empty_search_disallow']);
    }

    /**
     * Search listings from "Saved Search"
     *
     * @since 3.4.0
     *
     * @param  int    $entry_id - Saved Search ID
     * @param  int    $start    - Start DB position
     * @param  string $ids      - List of ID's that needs to search
     * @return array
     */
    public function runSavedSearch($entry_id, $start = 1, $ids = null)
    {
        global $rlDb;

        if (!$entry_id) {
            return array('listings' => array(), 'calc' => 0);
        }

        $entry = $rlDb->getRow("
            SELECT `Content`, `Form_key`, `Listing_type`
            FROM `{db_prefix}saved_search`
            WHERE `ID` = {$entry_id}
        ");

        $this->search_form_key = $entry['Form_key'];
        $data = unserialize($entry['Content']);
        $ltype = $entry['Listing_type'];

        if ($ids && is_string($ids) && $ids !== '') {
            define('IOS_SAVED_SEARCH_FILTER_IDS', $ids);
        }
        return $this->searchListings($data, $ltype, $start);
    }

    /**
     * Search listings
     *
     * @param  array  $data  - Search form data [key => value]
     * @param  string $type  - Listing type key
     * @param  int    $start - Start DB position
     * @return array
     */
    public function searchListings($data, $type, $start = 1)
    {
        global $rlSearch, $config, $reefless;

        $response = array(
            'listings' => array(),
            'calc' => 0,
        );

        if (!$type || !is_array($data)) {
            return $response;
        }

        $reefless->loadClass('Search');

        /* prepare fields */
        $form_key = $this->search_form_key ?: $type . $this->search_form_type;
        $rlSearch->getFields($form_key, $type);
        /* get fields END */

        if (isset($data['zip'])
            && !isset($_SESSION['GEOLocationData']->Country_code)
            && isset(self::$active_plugins['ipgeo'])
        ) {
            $reefless->loadClass('IPGeo', null, 'ipgeo');
            $GLOBALS['rlIPGeo']->hookInit();
        }

        if (!empty($data['zip']['place_id'])
            && (empty($data['zip']['lat']) || empty($data['zip']['lng']))
        ) {
            if (false !== $location = $this->getPlaceLocation($data['zip']['place_id'])) {
                $data['zip']['lat'] = (double) $location->lat;
                $data['zip']['lng'] = (double) $location->lng;
                unset($data['zip']['place_id']);
            }
        }

        $limit = (int) $config['iflynax_grid_listings_number'];
        $listings = $rlSearch->search($data, $type, $start, $limit);
        $response['calc'] = (int) $rlSearch->calc;

        // adapt listings
        foreach ($listings as $listing) {
            $response['listings'][] = $this->adaptShortFormWithData($listing);
        }
        unset($listings);

        return $response;
    }

    /**
     * Advanced Agents search
     **/
    public function searchAccounts($form_data = false, $type = false, $start = 1)
    {
        global $reefless, $rlDb;

        $response = array(
            'accounts' => array(),
            'calc' => 0,
        );

        if (!$type) {
            return $response;
        }

        $reefless->loadClass('Account');
        $reefless->loadClass('Listings');

        $account_type_id = $rlDb->getOne('ID', "`Key` = '{$type}'", 'account_types');
        $account_type = $GLOBALS['rlAccount']->getTypeDetails($type);
        $fields = $GLOBALS['rlAccount']->buildSearch($account_type_id);

        if (!$fields) {
            $GLOBALS['rlDebug']->logger("iOS: " . __FUNCTION__ . "(), no fields by form found");
            return $response;
        }

        $form_data['sort_by'] = false; // Set default sorting field: `Date`
        $form_data['sort_type'] = 'DESC';

        $limit = (int) $GLOBALS['config']['iflynax_grid_listings_number'];
        $accounts = $GLOBALS['rlAccount']->searchDealers($form_data, $fields, $limit, $start, $account_type);

        return $this->prepareAccounts($accounts, $GLOBALS['rlAccount']->calc);
    }

    /**
     * Description
     * @param type $account_id - Account ID
     * @return array
     */
    public function getConversations($account_id = 0)
    {
        global $reefless;

        if (!$account_id) {
            return array('error' => 'you must be logged!');
        }

        $response = array(
            'conversations' => array(),
            'new_messages' => 0,
        );

        $reefless->loadClass('Message');
        $conversations = $GLOBALS['rlMessage']->getContacts();

        if (!empty($conversations)) {
            foreach ($conversations as $entry) {
                $account_photo = (!empty($entry['Photo']) && file_exists(RL_FILES . $entry['Photo']))
                ? RL_FILES_URL . $entry['Photo']
                : '';

                $authorName = $entry['Full_name'];
                if ($entry['Admin']) {
                    $authorName .= " ({$GLOBALS['lang']['website_admin']})";
                } elseif ($entry['Visitor_mail']) {
                    $authorName .= " ({$GLOBALS['lang']['website_visitor']})";
                }
                $_count = (int) $entry['Count'];

                $response['conversations'][] = array(
                    'authorName' => $authorName,
                    'authorId' => (int) $entry['From'],
                    'message' => (string) $entry['Message'],
                    'datetime' => $this->convertDate($entry['Date']),
                    'count' => $_count,
                    'photo' => $account_photo,
                    'admin' => (bool) (int) $entry['Admin'],
                    'email' => (string) $entry['Visitor_mail'],
                );
                $response['new_messages'] += $_count;
            }
        }
        return $response;
    }

    // TODO: LOOK AT ME!
    public function removeConversation($author_id = 0, $account_id = 0)
    {
        global $rlDb;

        $update = false;

        if ($account_id == $author_id) {
            $sql = "DELETE FROM `{db_prefix}messages` WHERE `To` = {$account_id} AND `From` =  {$author_id}";
        } else {
            $update = true;
            $sql = "UPDATE `{db_prefix}messages` SET `Remove` = IF(`Remove` = '', IF(`From` = '{$account_id}', 'from', 'to'), CONCAT(`Remove`, ',', IF(`From` = '{$account_id}', 'from', 'to'))) ";
            $sql .= "WHERE (`To` = {$account_id} AND `From` = {$author_id}) ";
            $sql .= "OR (`To` = {$author_id} AND `From` = {$account_id}) ";
        }
        $rlDb->query($sql);

        if ($update) {
            $rlDb->query("DELETE FROM `{db_prefix}messages` WHERE
                ((`To` = {$account_id} AND `From` = {$author_id})
                OR (`To` = {$author_id} AND `From` = {$account_id}))
                AND (`Remove` = 'from,to' OR `Remove` = 'to,from') "
            );
        }
    }

    /**
     * Description
     * @param int $account_id
     * @param int $sender
     * @return array
     */
    public function fetchMessages($account_id = 0, $sender = 0)
    {
        global $rlDb;

        if (!$account_id) {
            return array('error' => 'you must be logged!');
        }

        $response['messages'] = array();
        $visitor_email = false;

        if ($GLOBALS['rlValid']->isEmail($sender)) {
            $visitor_email = $sender;
            $sender = self::WEBSITE_VISITOR;
        }
        $sender = (int) $sender;

        $sql = "SELECT `From`, `Message`, UNIX_TIMESTAMP(`Date`) AS `Date` ";

        if ($sender == self::WEBSITE_VISITOR) {
            $sql .= ",`Visitor_mail`, `Visitor_phone`, `Listing_ID` ";
        }

        $sql .= "FROM `{db_prefix}messages` ";
        $sql .= "WHERE ((`To` = " . $account_id . " AND `From` = " . $sender;

        if ($sender == self::WEBSITE_VISITOR) {
            $sql .= " AND `Visitor_mail` = '{$visitor_email}' ";
        }

        $sql .= ") OR (`To` = " . $sender . " AND `From` = " . $account_id . ")) ";
        $sql .= "AND FIND_IN_SET(IF (`From` = {$account_id}, 'from', 'to'), `Remove`) = 0 ";
        $sql .= "ORDER BY `ID` ASC";
        $messages = $rlDb->getAll($sql);

        // set status as readed
        $this->updatedMessagesStatus($account_id, $sender, $visitor_email);

        $photo_url = $rlDb->getOne('Photo', "`ID` = " . $sender, 'accounts');
        if (!empty($photo_url) && file_exists(RL_FILES . $photo_url)) {
            $response['recipientPhoto'] = RL_FILES_URL . $photo_url;
        }

        if (!empty($messages)) {
            foreach ($messages as $entry) {
                $_message = $this->cleanString($entry['Message']);

                if ($sender == self::WEBSITE_VISITOR) {
                    $this->_appendVisitorDetailsToMessage($_message, $entry);
                }

                $response['messages'][] = array(
                    'message' => $_message,
                    'date' => (int) $entry['Date'],
                    'me' => ($entry['From'] == $account_id),
                );
            }
            unset($messages);
        }
        return $response;
    }

    private function _appendVisitorDetailsToMessage(&$message, &$details)
    {
        global $lang, $reefless, $rlDb;

        $message .= PHP_EOL . PHP_EOL;
        $message .= $lang['mail'] . ': ' . $details['Visitor_mail'] . PHP_EOL;
        $message .= $lang['contact_phone'] . ': ' . $details['Visitor_phone'] . PHP_EOL;

        $listing_id = (int) $details['Listing_ID'];

        if ($listing_id) {
            $reefless->loadClass('Listings');

            $sql = "SELECT `T1`.*, `T2`.`Type` AS `Listing_type_key`";
            $sql .= "FROM `{db_prefix}listings` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
            $sql .= "WHERE `T1`.`ID` = {$listing_id} AND `T1`.`Status` = 'active' AND `T2`.`Status` = 'active' ";
            $info = $rlDb->getRow($sql);

            $title = $GLOBALS['rlListings']->getListingTitle($info['Category_ID'], $info, $info['Listing_type_key']);
            $message .= 'Related to: ' . $title;
        }
    }

    /**
     * Update messages status for conversation
     * @param int $account_id
     * @param int $sender
     */
    public function updatedMessagesStatus($account_id = 0, $sender = 0, $visitor_email = false)
    {
        global $rlDb;

        $update = array(
            'fields' => array('Status' => 'readed'),
            'where' => array('To' => $account_id),
        );

        if ($visitor_email) {
            $update['where']['Visitor_mail'] = $visitor_email;
        } else {
            $update['where']['From'] = $sender;
        }

        $rlDb->updateOne($update, 'messages');
    }

    /**
     * Description
     * @param int $account_id
     * @param int $recipient
     * @param string $message
     * @return array
     */
    public function sendMessageTo($account_id = 0, $recipient = 0, $message = null)
    {
        global $rlDb;

        if (!$account_id) {
            return array('error' => 'you must be logged!');
        }

        $response = array();
        $admin = 0;

        $new_message = array(
            'From' => $account_id,
            'To' => $recipient,
            'Message' => $message,
            'Admin' => $admin,
            'Date' => 'NOW()',
        );

        $response['sent'] = $rlDb->insertOne($new_message, 'messages');

        /**
         * @since v4.1.0
         **/
        $GLOBALS['rlHook']->load('rlMessagesAjaxAfterMessageSent', $recipient, $new_message, $admin);

        return $response;
    }

    private function fetchAllActiveTokensByAccountId($account_id)
    {
        global $rlDb;

        $account_id = (int) $account_id;

        if (!$account_id) {
            return false;
        }

        $sql = "SELECT `Token`, `Language` FROM `{db_prefix}iflynax_push_tokens` ";
        $sql .= "WHERE `Status` = 'active' AND `Account_ID` = " . $account_id;
        $entries = $rlDb->getAll($sql);

        if (empty($entries)) {
            return false;
        }

        $sql = "SELECT `Code` FROM `{db_prefix}iflynax_languages` WHERE `Status` = 'active'";
        $app_languages = $rlDb->getAll($sql);

        foreach ($entries as &$entry) {
            if ($entry['Language'] == '' || !in_array($entry['Language'], $app_languages)) {
                $entry['Language'] = $GLOBALS['config']['iflynax_lang'];
            }
        }
        return $entries;
    }

    /**
     * Get app language for app
     */
    public function getAppLanguage()
    {
        global $rlDb;

        if (isset($_REQUEST['language']) && !empty($_REQUEST['language'])) {
            $language = $GLOBALS['rlValid']->xSql($_REQUEST['language']);
            $code = $rlDb->getOne('Code', "`Code` = '{$language}' AND `Status` = 'active'", 'iflynax_languages');

            if (!empty($code)) {
                return $code;
            }
        }

        return $GLOBALS['config']['iflynax_lang'];
    }

    /**
     * Get app language for app
     */
    public function getSiteLanguage($app_language = false)
    {
        global $config, $rlDb;

        if (!$app_language) {
            return $config['lang'];
        }

        $_where = "`Code` = '" . $app_language . "' AND `Status` = 'active'";
        $_code = $rlDb->getOne('Code', $_where, 'languages');

        if (!empty($_code)) {
            return $_code;
        }
        return $config['lang'];
    }

    /**
     * Get language phrases by language iso code
     *
     * @param string $code - Requested language code
     *
     * @return array
     */
    public function getLangPhrases($code)
    {
        global $lang, $rlDb;

        if (!$code) {
            $GLOBALS['rlDebug']->logger('iOS: Unable to fetch lang phrases, no language code specified');

            return array();
        }

        $rlDb->setTable('iflynax_phrases');
        $rlDb->outputRowsMap = $columns = array('Key', 'Value');
        $app_phrases = (array) $rlDb->fetch($columns, array('Code' => $code));

        $system_phrases = array(
            'alphabet_characters'         => (string) $lang['alphabet_characters'],
            'section_add_pictures'        => (string) $lang['iflynax_section_pictures'],
            'section_add_youtube'         => (string) $lang['iflynax_section_youtube'],
            'button_deleteAccount'        => (string) $lang['delete_account'],
            'alert_title_deleteAccount'   => (string) $lang['warning'],
            'alert_message_deleteAccount' => (string) $lang['account_remove_notice_pass'],
            'alert_passwordLengthFail'    => (string) $lang['password_lenght_fail'],
        );

        if (isset(self::$active_plugins['reportBrokenListing'])) {
            $system_phrases += array(
                'reportAbuse_successfully_sent' => (string) $lang['reportbroken_listing_has_been_added'],
                'reportbroken_add_comment'      => (string) $lang['reportbroken_add_comment'],
                'reportbroken_other'            => (string) $lang['rbl_other'],
            );
        }

        if (isset(self::$active_plugins['hybridAuthLogin'])) {
            $system_phrases += array(
                'ha_user_isnt_synchonized' => (string) $lang['ha_user_isnt_synchonized'],
                'ha_email_doesnt_exist'    => (string) $lang['ha_email_doesnt_exist'],
                'ha_social_login'          => (string) $lang['ha_social_login'],
            );
        }

        if (isset(self::$active_plugins['search_by_distance'])) {
            $system_phrases += [
                'placeholder_sbd_search' => $this->getSBDFieldPlaceholder(),
            ];
        }

        return array_merge($app_phrases, $system_phrases);
    }

    /**
     * Description
     * @param array &$accounts
     * @param int $calc
     * @return array
     */
    public function prepareAccounts(&$accounts, $calc = 0)
    {
        $response = array(
            'accounts' => array(),
            'calc' => (int) $calc,
        );

        if (!$accounts) {
            return $response;
        }

        foreach ($accounts as $index => $account) {
            $response['accounts'][$index] = array(
                'id' => (int) $account['ID'],
                'photo' => !empty($account['Photo']) ? RL_FILES_URL . $account['Photo'] : '',
                'date' => $this->convertDate($account['Date']),
                'lcount' => (int) $account['Listings_count'],
                'fullName' => $account['Full_name'],
            );

            // set map short info
            if (!empty($account['Loc_latitude']) && !empty($account['Loc_longitude'])) {
                $lat = (float) $account['Loc_latitude'];
                $lng = (float) $account['Loc_longitude'];

                if ($lat && $lng) {
                    $response['accounts'][$index] += array(
                        'map' => array(
                            'lat' => $lat,
                            'lng' => $lng,
                            'title' => (string) $account['Loc_address'],
                        ),
                    );
                }
            }

            if (!empty($account['fields'])) {
                $iteration = 1;
                $middle_field = '';

                foreach ($account['fields'] as $field) {
                    if ($iteration > 2) {
                        break;
                    }

                    if ($field['value'] != '') {
                        $middle_field .= $this->cleanString($field['value'] . ', ');
                        $iteration++;
                    }
                }

                $middle_field = rtrim($middle_field, ', ');
                $response['accounts'][$index]['middleField'] = $middle_field;
            }
        }
        unset($accounts);

        return $response;
    }

    /**
     * Get configs related to iOS app
     *
     * @return array - configs list
     **/
    public function getConfigs()
    {
        global $config, $rlDb;

        // get system configs
        $from_system = array(
            'rl_version' => 'site_version',
            'iflynax_lang' => 'system_lang',
            'system_currency_position' => 'currency_position',
            'site_main_email' => 'feedback_email',
            'grid_photos_count' => 'display_photos_count',
            'img_quality' => 'img_quality',
            'img_auto_upload' => 'img_auto_upload',
            'account_login_mode' => 'account_login_mode',
            'account_wildcard' => 'account_wildcard',
            'listing_auto_approval' => 'listing_auto_approval',
            'edit_listing_auto_approval' => 'edit_listing_auto_approval',
            'account_edit_email_confirmation' => 'account_edit_email_confirmation',
        );

        // Set hardcoded dimensions for legacy app support
        $response = array(
            'account_thumb_width'  => 110,
            'account_thumb_height' => 100,
        );

        foreach ($from_system as $key => $sys_config) {
            switch ($key) {
                case 'account_login_mode':
                    $login_mode = $config['account_login_mode'] == 'email' ? 'email' : 'username';
                    $response['account_login_mode'] = $login_mode;
                    break;

                default:
                    $response[$sys_config] = $config[$key];
                    break;
            }
        }

        // get iflynax configs
        $rlDb->setTable('config');
        $app_configs = $rlDb->fetch(array('Key', 'Default'), array('Plugin' => 'iFlynaxConnect'), "AND `Type` <> 'divider'");

        // prepare configs for iOS device's
        foreach ($app_configs as $app_config) {
            if (!in_array($app_config['Key'], $this->skip_app_configs)) {
                $app_config_key = str_replace('iflynax_', '', $app_config['Key']);
                $response[$app_config_key] = $app_config['Default'];
            }
        }
        unset($app_configs);

        // get custom configs
        $response['site_name'] = $rlDb->getOne('Value', "`Key` = 'pages+title+home' AND `Code` = '{$config['lang']}'", 'lang_keys');
        $response['site_url'] = '<a href="' . RL_URL_HOME . '">' . RL_URL_HOME . '</a>';
        $response['site_email'] = '<a href="mailto:' . $config['site_main_email'] . '">' . $config['site_main_email'] . '</a>';
        $response['year_build_key'] = $this->year_build_key;
        $response['forms_date_format'] = 'yyyy-MM-dd';

        // static pages keys | TODO: make it dynamic
        $privacy_policy_page = (string) $rlDb->getOne('Key', "`Key` LIKE 'privacy_polic%'", 'pages');
        $response['static_page:privacy_police'] = $privacy_policy_page;
        $response['static_page:terms_of_use'] = 'terms_of_use';

        /* build personal_address_domain */
        $personal_address_domain = $GLOBALS['rlValid']->getDomain(RL_URL_HOME, true);
        if ($config['account_wildcard']) {
            $personal_address_domain = ltrim($personal_address_domain, 'www.');
        }
        if (RL_DIR != '') {
            $personal_address_domain .= '/' . RL_DIR . '/';
        }
        $response['account_personal_address_domain'] = $personal_address_domain;
        /* build personal_address_domain end */

        // comments plugin
        $plugin_id = (int) $rlDb->getOne('ID', "`Key` = 'comment' AND `Status` = 'active'", 'plugins');
        $response['comment_plugin'] = $plugin_id ? true : false;

        if ($plugin_id) {
            $response['comments_stars_number'] = $this->comments_stars_number;
            $response['comments_rating_module'] = (bool) $config['comments_rating_module'];
            $response['comments_auto_approval'] = (bool) $config['comment_auto_approval'];
            $response['comments_message_symbols_number'] = (int) $config['comment_message_symbols_number'];
            $response['comments_login_access'] = (bool) $config['comments_login_access'];
            $response['comments_login_post'] = (bool) $config['comments_login_post'];
            $response['comments_per_page'] = (int) $config['comments_per_page'];
        }

        if (isset(self::$active_plugins['search_by_distance'])) {
            $response['sbd_active'] = true;
            $response['sbd_search_mode'] = (string) $config['sbd_search_mode'];
        }

        // facebook connect
        $response['facebook_login'] = false;
        if (isset(self::$active_plugins['facebookConnect'])
            && (bool) $config['facebookConnect_module']
            && !empty($config['facebookConnect_appid'])
            && !empty($config['facebookConnect_secret'])
            && !empty($config['facebookConnect_account_type'])
        ) {
            $response['facebook_login'] = true;
            $response['facebook_app_id'] = (string) $config['facebookConnect_appid'];
        }

        if (isset(self::$active_plugins['hybridAuthLogin'])
            && version_compare(self::$active_plugins['hybridAuthLogin'], '2.0.0', '>=')
        ) {
            $GLOBALS['reefless']->loadClass('HybridAuthLogin', null, 'hybridAuthLogin');
            $socialNetwork = new SocialNetworkLoginAPI();
            $socialNetwork->setGettingCredentials([]);

            if ($providers = $socialNetwork->getActiveProviders()) {
                $response['socialNetworkProviders'] = array_column($providers, 'Provider');
            }
        }

        // reportBrokenListing
        if (false !== $response['reportBrokenListing_plugin'] = isset(self::$active_plugins['reportBrokenListing'])
            && version_compare(self::$active_plugins['reportBrokenListing'], '3.0.0', '>=')
        ) {
            require_once __DIR__ . '/Adapter/ReportPointsAdapter.php';
            $response['reportBrokenListing_points'] = ReportPointsAdapter::getAllActivePoints();
        }

        // Tell Apps that the features need to activate only when necessary.
        $response['feature_removeAccount'] = version_compare($config['rl_version'], '4.7.0', '>=')
            ? (bool) $config['account_removing']
            : false;
        $response['feature_APNConfigured'] = file_exists(__DIR__ . '/cert/apns-prod-cert.pem');

        // Tell Apps that the feature already deprecated and no need to use it.
        $response['legacyFeature_makePrimary'] = false;

        return $response;
    }

    /**
     * Get ads for home page
     *
     * @param int $stack - start from
     * @param int $tablet - is tablet device from other side
     *
     * @return array $response - (featured | recently added) listings
     **/
    public function getAdsForMainScreen($stack = 0, $tablet = false)
    {
        global $config, $rlListings, $reefless;

        $limit = (int) $config['iflynax_grid_listings_number'];
        if ($tablet) {
            $limit *= 2;
        }

        $reefless->loadClass('Listings');
        $reefless->loadClass('Resize');
        $reefless->loadClass('Crop');

        $start = $stack > 1 ? ($stack - 1) * $limit : 0;
        $response = array(
            'listings' => array(),
            'calc' => 0,
        );

        switch ($config['iflynax_ads_type_home_screen']) {
            case 'featured':
                $ltype = $config['iflynax_home_featured_ltype'];

                $listings = $rlListings->getFeatured($ltype, $limit, false, false, $start);
                break;

            case 'recently':
                define('IOS_MAIN_SCREEN', true);
                $listings = $rlListings->getRecentlyAdded($stack, $limit);
                break;
        }

        // deep checking
        if (empty($listings)) {
            return $response;
        }

        // adapt ads for response to device
        foreach ($listings as $listing) {
            $response['listings'][] = $this->adaptShortFormWithData($listing);
        }
        unset($listings);

        $response['calc'] = (int) $rlListings->calc;

        return $response;
    }

    /**
     *
     */
    public function buildSectionTitleWithDateDiff($diff = 0, $date_parse = '')
    {
        global $lang;

        if ($diff == 1) {
            return $lang['today'];
        } elseif ($diff == 2) {
            return $lang['yesterday'];
        } elseif ($diff > 2 && $diff < 8) {
            return str_replace('{day}', $diff - 1, $lang['days_ago_pattern']);
        }

        $post_date = new DateTime($date_parse);
        $date_format = str_replace(array('%', 'b'), array('', 'M'), RL_DATE_FORMAT);

        return $post_date->format($date_format);
    }

    /**
     * Get listing photos
     *
     * @param int $id - listing id
     * @param int $limit = photos limit
     * @return array $data - listing photos
     **/
    public function getListingPhotos($id = false, $limit = false)
    {
        global $rlDb;

        $rows = array('ID', 'Photo', 'Thumbnail', 'Description');
        $where = array('Listing_ID' => $id, 'Type' => 'picture', 'Status' => 'active');
        $additional_where = "AND `Thumbnail` <> '' AND `Photo` <> '' ORDER BY `Position`";
        $listing_photos = $rlDb->fetch($rows, $where, $additional_where, $limit, 'listing_photos');

        if (!empty($listing_photos)) {
            foreach ($listing_photos as $listing) {
                $response[] = array(
                    'id' => $listing['ID'],
                    'photo' => RL_FILES_URL . $listing['Photo'],
                    'thumbnail' => RL_FILES_URL . $listing['Thumbnail'],
                    'desc' => $listing['Description'],
                );
            }
            unset($listing_photos);

            return $response;
        }
        return false;
    }

    public function getListingVideos($id = false, $limit = false)
    {
        global $rlDb;

        $_fmap = array(
            'fields' => array('ID', 'Photo', 'Thumbnail', 'Original'),
            'where' => array('Listing_ID' => $id, 'Type' => 'video', 'Original' => 'youtube'),
            'options' => "ORDER BY `Position`",
        );
        $videos = $rlDb->fetch($_fmap['fields'], $_fmap['where'], $_fmap['options'], $limit, 'listing_photos');
        $response = array();

        if (!empty($videos)) {
            foreach ($videos as $video) {
                $index = array_push($response, array(
                    'id' => (int) $video['ID'],
                    'type' => $video['Original'],
                ));
                $index--;

                // YouTube video's
                if ($video['Original'] === 'youtube') {
                    $response[$index]['video'] = $video['Photo'];
                }
                // Local video's
                else {
                    $response[$index]['preview'] = RL_FILES_URL . $video['Thumbnail'];
                    $response[$index]['video'] = RL_FILES_URL . $video['Original'];
                }
            }
            unset($videos);
        }
        return $response ?: false;
    }

    /**
     * Adapt listing details form
     *
     * @param array $form - listing details form
     */
    public function adaptListingDetailSections(&$form)
    {
        foreach ($form as $sKey => $entry) {
            $countFields = count($entry['Fields']);
            $emptyFields = 0;

            foreach ($entry['Fields'] as $fKey => $field) {
                // remove field
                if (empty($field['value']) || in_array($field['Key'], $this->skip_lfield_keys)) {
                    unset($form[$sKey]['Fields'][$fKey]);
                    $emptyFields++;
                }
            }

            // remove section if all fields empty
            if ($countFields == $emptyFields) {
                unset($form[$sKey]);
            }
        }
    }

    public function validPhotoForListing(&$listing, $ltype_allow_photos = true)
    {
        if (!$ltype_allow_photos) {
            return 'ltype_denied_photos';
        }

        // Set system x2 thumbnail if exists
        if (!empty($listing['Main_photo_x2'])) {
            return RL_FILES_URL . $listing['Main_photo_x2'];
        }
        // Set system thumbnail if exists
        elseif (!empty($listing['Main_photo'])) {
            return RL_FILES_URL . $listing['Main_photo'];
        }
        // Set ios thumbnail if exists
        elseif (!empty($listing['iFlynax_photo'])) {
            return RL_FILES_URL . $listing['iFlynax_photo'];
        }
        // Set android thumbnail if exists
        elseif (!empty($listing['Android_photo'])) {
            return RL_FILES_URL . $listing['Android_photo'];
        }

        return 'listing_photo_not_exists';
    }

    /**
     * Description
     * @param type &$listings
     */
    public function adaptSimilarListings(&$listings)
    {
        foreach ($listings as &$listing) {
            $ltype_allow_photos = (bool) $this->listing_types[$listing['Listing_type']]['Photo'];

            $listing = array(
                'lid' => (int) $listing['ID'],
                'thumbnail' => $this->validPhotoForListing($listing, $ltype_allow_photos),
                'title' => (string) $listing['listing_title'],
            );
        }
    }

    public function getComments($listing_id, $account_id, $start, $mode = false)
    {
        global $config, $rlDb;

        // define start position
        $limit = (int) $config['comments_per_page'];
        $start = $start > 1 ? ($start - 1) * $limit : 0;

        $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.`Author`, `T1`.`Title`, `T1`.`Description`, `T1`.`Rating`, `T1`.`Status`, `T1`.`Date` ";
        $sql .= "FROM `{db_prefix}comments` AS `T1` ";
        $sql .= "WHERE `T1`.`Listing_ID` = " . $listing_id . " AND `T1`.`Status` = 'active' ";

        if ($account_id) {
            $sql .= "OR (`T1`.`Listing_ID` = " . $listing_id . " AND `T1`.`User_ID` = " . $account_id . " AND `T1`.`Status` = 'pending') ";
        }

        $sql .= "ORDER BY `T1`.`ID` DESC ";
        $sql .= "LIMIT {$start}, {$limit}";

        $data = $rlDb->getAll($sql);
        $calc = $rlDb->getRow("SELECT FOUND_ROWS() AS `calc`");

        $response = array(
            'comments' => array(),
            'calc' => (int) $calc['calc'],
        );

        // adapt the comments for app response
        foreach ($data as $comment) {
            $response['comments'][] = array(
                'title' => $this->cleanString($comment['Title']),
                'description' => htmlspecialchars_decode(nl2br($comment['Description'])),
                'date' => $this->convertDate($comment['Date']),
                'author' => (string) $comment['Author'],
                'status' => $comment['Status'],

                // 5 because we use 5 stars policy in the app
                'rating' => round(($this->comments_stars_number * $comment['Rating']) / $config['comments_stars_number']),
            );
        }
        unset($data);

        return $response;
    }

    /**
     * Add comment
     *
     * @param array $comment - comment data
     **/
    public function addComment($data)
    {
        global $config, $rlDb, $reefless;

        $listing_id = (int) $data['lid'];
        $account_id = (int) ($data['aid'] ?: 0);
        $status = $config['comment_auto_approval'] ? 'active' : 'pending';
        $rating = round(($config['comments_stars_number'] * (int) $data['rating']) / $this->comments_stars_number);
        $response = array();

        $insert_comment = array(
            'User_ID' => $account_id,
            'Listing_ID' => $listing_id,
            'Author' => $data['author'],
            'Title' => $data['title'],
            'Description' => urldecode($data['body']),
            'Rating' => $rating,
            'Status' => $status,
            'Date' => 'NOW()',
        );

        if ($rlDb->insertOne($insert_comment, 'comments')) {
            $message = $GLOBALS['rlLang']->getPhrase('notice_comment_added_approval', null, null, true);
            $message = $message ?: 'Comment posted'; // Fallback message to avoid app crash

            // increase count of comments
            if ($config['comment_auto_approval']) {
                $sql = "UPDATE `{db_prefix}listings` SET `comments_count` = `comments_count` + 1 ";
                $sql .= "WHERE `ID` = " . $listing_id;
                $rlDb->query($sql);

                $message = $GLOBALS['rlLang']->getSystem('notice_comment_added');
            }

            $response = array(
                'success' => true,
                'message' => $message,
            );

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
                    $listing_info['Listing_type']);

                $message = nl2br(urldecode($data['body']));
                $page = $this->getPagePath($listing_type['Page_key']);

                $link = SEO_BASE;
                $link .= $config['mod_rewrite'] ?
                $page . '/' . $listing_info['Category_path'] . '/' . $GLOBALS['rlValid']->str2path($listing_title) . '-' . $listing_id . '.html#comments'
                : '?page=' . $page . '&amp;id=' . $listing_id . '#comments';

                $link = '<a href="' . $link . '">' . $listing_title . '</a>';

                $mail_tpl['body'] = str_replace(
                    array('{name}', '{author}', '{title}', '{message}', '{listing_title}'),
                    array($account_info['Full_name'], $data['author'], $data['title'], $message, $link),
                    $mail_tpl['body']);

                $GLOBALS['rlMail']->send($mail_tpl, $account_info['Mail']);
            }
        } else {
            $GLOBALS['rlDebug']->logger("iOS: addComment() error when add a new comment");
            $response['success'] = false;
        }
        return $response;
    }

    public function getPagePath($key)
    {
        return $GLOBALS['rlDb']->getOne('Path', "`Key` = '{$key}'", 'pages');
    }

    /**
     * Fetch user shortInfo
     *
     * @since 3.6.0 - Param $profile added
     *
     * @param int   $account_id
     * @param array $profile
     *
     * @return array
     */
    public function fetchUserShortInfo($account_id, $profile = null)
    {
        $account_id = (int) $account_id;

        if (empty($profile)) {
            $profile = $GLOBALS['rlAccount']->getProfile($account_id);
        }

        // build profile short info
        $info['profile'] = array(
            'id' => $account_id,
            'username' => (string) $profile['Username'],
            'type' => array(
                'key' => (string) $profile['Type'],
                'name' => $this->trueNameOrKeyInstead(
                    $GLOBALS['lang']['account_types+name+' . $profile['Type']],
                    $profile['Type']
                ),
            ),
            'full_name' => (string) $profile['Full_name'],
            'listings_count' => (int) $profile['Listings_count'],
            'own_location' => (bool) $profile['Own_location'],
            'own_page' => (bool) $profile['Own_page'],
            'display_email' => (bool) $profile['Display_email'],
            'mail' => (string) $profile['Mail'],
            'own_address' => (string) $profile['Own_address'],
            'location' => array(
                'address' => (string) $profile['Loc_address'],
                'lat' => (float) $profile['Loc_latitude'],
                'lng' => (float) $profile['Loc_longitude']),
        );

        if (!$_SESSION['abilities']) {
            $abilities = $GLOBALS['rlDb']->getOne('Abilities', "`Key` = '{$profile['Type']}'", 'account_types');
            $_SESSION['abilities'] = $abilities ? explode(',', $abilities) : null;
        }

        // account abilities
        $_abilities = is_array($_SESSION['abilities']) ? $_SESSION['abilities'] : implode(',', $_SESSION['abilities']);
        $info['profile']['abilities'] = (array) $_abilities;

        // append thumbnail if exists
        if (!empty($profile['Photo']) && file_exists(RL_FILES . $profile['Photo'])) {
            $info['profile']['thumbnail'] = RL_FILES_URL . $profile['Photo'];
        }

        // collect statistics
        $info['statistics'] = $this->fetchAccountStat($account_id);

        // check a new messages
        $info['new_messages'] = $this->newMessageCount();

        return $info;
    }

    /**
     * Get messages count by account ID from current session
     * @since 3.1.2
     */
    private function newMessageCount()
    {
        global $rlDb;

        $account_id = (int) $GLOBALS['account_info']['ID'];

        if (!$account_id) {
            return 0;
        }

        $sql = "SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}messages` ";
        $sql .= "WHERE `To` = " . $account_id . " AND `Status` = 'new' AND `Remove` = ''";
        $messages = $rlDb->getRow($sql);

        return (int) $messages['Count'];
    }

    /**
     * Build phone data to properly display in App
     * @since 3.1.2
     */
    private function phoneFieldData($field)
    {
        return sprintf(
            '%d|%d|%d|%d',
            $field['Opt1'], $field['Default'], $field['Values'], $field['Opt2']
        );
    }

    /**
     * Get localized price with currency
     *
     * @since 3.4.0
     *
     * @param  float  $price
     * @param  string $currency
     * @return string
     */
    private function priceWithCurrency($price, $currency = null)
    {
        global $config;

        if (!$currency) {
            $currency = $config['system_currency'];
        } else {
            $currency = $GLOBALS['lang']['data_formats+name+' . $currency];
        }
        $price = array($GLOBALS['rlValid']->str2money($price), $currency);

        if ($config['system_currency_position'] == 'before') {
            $price = array_reverse($price);
        }
        return implode('', $price);
    }

    /**
     * Reset password by email
     *
     * @param string $email - requested e-mail address
     **/
    public function resetPassword($email = false)
    {
        global $config, $lang, $rlDb, $reefless;

        if (!$email) {
            return false;
        }

        $GLOBALS['rlValid']->sql($email);

        // check email
        $account_id = (int) $rlDb->getOne('ID', "`Mail` = '{$email}'", 'accounts');

        if (!$account_id) {
            return array(
                'error' => $lang['email_account_not_found'],
            );
        }

        // send "reset password" link
        $reefless->loadClass('Account');
        $profile_info = $GLOBALS['rlAccount']->getProfile($account_id);

        $reefless->loadClass('Mail');
        $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('remind_password_request');

        $hash_key = $reefless->generateHash();
        $hash = md5($hash_key) . md5($config['security_key']);

        $sql = "UPDATE `{db_prefix}accounts` SET `Password_hash` = '{$hash_key}' WHERE `ID` = " . $account_id;
        $rlDb->query($sql);

        $remind_path = $rlDb->getOne('Path', "`Key` = 'remind'", 'pages');
        $GLOBALS['pages']['remind'] = $remind_path;

        $link = $reefless->getPageUrl('remind') . '?hash=' . $hash;
        $link = sprintf('<a href="%s">%s</a>', $link, $link);

        $mail_tpl['body'] = str_replace(
            array('{link}', '{name}'),
            array($link, $profile_info['Full_name']),
            $mail_tpl['body']
        );

        $response = array(
            'success' => $GLOBALS['rlMail']->send($mail_tpl, $email),
            'message' => $lang['remind_password_request_sent'],
        );
        return $response;
    }

    /**
     * get profile form data
     *
     * @param string $type - account type key
     * @param int $id - account ID
     * @return array
     **/
    public function getProfileForm($type = false, $id = false)
    {
        global $reefless, $rlDb;

        if (!$type || !$id) {
            $GLOBALS['rlDebug']->logger("iOS: " . __FUNCTION__ . "() no account type or account ID received, abort");
            return false;
        }

        $account_type_id = $rlDb->getOne('ID', "`Key` = '{$type}'", 'account_types');

        $reefless->loadClass('Account');
        $reefless->loadClass('Categories');

        $fields = $GLOBALS['rlAccount']->getFields($account_type_id);
        $fields = $GLOBALS['rlLang']->replaceLangKeys($fields, 'account_fields', array('name'));
        $fields = $GLOBALS['rlCommon']->fieldValuesAdaptation($fields, 'account_fields');

        $account_data = $rlDb->fetch('*', array('ID' => $id), null, 1, 'accounts', 'row');

        // adapt data
        $result = $this->adaptForm($fields, $account_data, false, 'account_fields');

        return $result;
    }

    /**
     * get search forms for available listing types
     **/
    public function getListingTypeSearchForms()
    {
        $forms = array();

        foreach ($this->listing_types as $type_key => $listing_type) {
            if ($listing_type['Search_page']) {
                $form_key = $this->search_form_key ?: $type_key . $this->search_form_type;
                $search_form = $GLOBALS['rlSearch']->buildSearch($form_key, $type_key);

                if (is_array($search_form) && !empty($search_form)) {
                    $forms[$type_key] = $this->_adaptListingTypeSearchForm($search_form, $type_key);
                }
            }
        }
        return $forms;
    }

    public function getNearbyAdsSearchForm()
    {
        // if (false !== $search_form = $GLOBALS['rlSearch']->buildSearch(self::NEARBY_ADS_SEARCH_FORM)) {
        //     return $this->_adaptListingTypeSearchForm($search_form);
        // }
        return array();
    }

    private function _adaptListingTypeSearchForm(&$form, $type_key)
    {
        global $lang, $config, $reefless, $rlDb;

        $reefless->loadClass('Categories');

        if (defined('MULTI_FIELD_PLUGIN_INSTALLED') && MULTI_FIELD_PLUGIN_INSTALLED) {
            $sql = "SELECT * FROM `{db_prefix}multi_formats` AS `T1`";
            $sql .= "JOIN `{db_prefix}listing_fields` AS `T2` ON `T2`.`Condition` = `T1`.`Key`";
            $sql .= "WHERE `T1`.`Status` = 'active'";

            if ($this->isMultiFieldNew()) {
                $sql .= " AND `T1`.`Parent_ID` = 0";
            }

            $mf_tmp = $rlDb->getAll($sql);

            foreach ($mf_tmp as $item) {
                $multi_fields[$item['Key']] = true;
            }
        }
        $out = array();

        foreach ($form as $group) {
            foreach ($group['Fields'] as $field) {
                $search_multicat_levels = 0;
                $data = '';

                if ($field == null || empty($field['Key'])) {
                    continue;
                }

                switch ($field['Type']) {
                    case 'select':
                        $listing_type = $this->listing_types[$type_key];

                        if ($multi_fields[$field['Key']]) {
                            $data = 'multiField';

                            if (is_numeric(strpos($field['Key'], '_level'))) {
                                unset($field['Values']);
                            }
                        } elseif ($field['Key'] == 'Category_ID' && $listing_type['Search_multi_categories']) {
                            $search_multicat_levels = (int) $listing_type['Search_multicat_levels'];
                            $field['pName'] = 'category';
                            $data = 'multiField';

                            // since Flynax v4.5.1
                            if ($listing_type['Search_multicat_phrases']) {
                                $field['pName'] = sprintf('multilevel_category+%s+%s+1', $type_key, RL_LANG_CODE);
                            }
                        }
                        break;

                    case 'phone':
                        $data = $this->phoneFieldData($field);
                        $field['Values'] = array();
                        break;
                }

                $index = count($out);
                $out[$index] = array(
                    'key' => $field['Key'],
                    'type' => $field['Type'],
                    'name' => strip_tags($lang[$field['pName']]),
                    'data' => $data,
                );

                /* category multi selection - append fields */
                if ($search_multicat_levels > 1) {
                    $level_name = empty($lang['subcategory']) ? 'iflynax_subcategory' : 'subcategory';

                    for ($level = 1; $level < $search_multicat_levels; $level++) {
                        // since Flynax v4.5.1
                        if ($listing_type['Search_multicat_phrases']) {
                            $level_name = sprintf('multilevel_category+%s+%s+%d', $type_key, RL_LANG_CODE, $level + 1);
                        }

                        $out[$index + $level] = array(
                            'key' => $field['Key'] . '_level' . ($level + 1),
                            'type' => 'select',
                            'name' => (string) $lang[$level_name],
                            'data' => 'multiField',
                            'values' => array(),
                        );
                    }
                }
                /* category multi selection END */

                /* collect possible field values */
                $_values = array();

                if (is_array($field['Values'])) {
                    foreach ($field['Values'] as $item) {
                        if (is_numeric(strpos($field['Key'], 'Category_ID'))
                            || $field['Key'] == 'posted_by') {
                            $_values[] = array(
                                'key' => (string) $item['ID'],
                                'name' => (string) $lang[$item['pName']],
                            );
                        } elseif ($field['Condition'] == 'years') {
                            $_values[] = array(
                                'key' => (string) $item['Key'],
                                'name' => (string) $item['Key'],
                            );
                        } else {
                            switch ($field['Type']) {
                                case 'checkbox':
                                case 'select':
                                case 'radio':
                                case 'mixed':
                                    $set_key = $field['Condition'] ? $item['Key'] : $item['ID'];
                                    break;

                                default:
                                    $set_key = $item['Key'];
                                    break;
                            }

                            $_value = array(
                                'key' => (string) $set_key,
                                'name' => (string) $lang[$item['pName']],
                            );
                            $_values[] = $_value;

                            if ((int) $item['Default'] || $item['Default'] == $set_key) {
                                $out[$index]['default'] = $_value;
                            }
                        }
                    }
                } elseif ($field['Type'] == 'price') {
                    foreach ($GLOBALS['rlCategories']->getDF('currency') as $currency_item) {
                        $_values[] = array(
                            'key' => (string) $currency_item['Key'],
                            'name' => (string) $currency_item['name'],
                        );
                    }
                } elseif ($field['Key'] == 'zip'
                    && isset(self::$active_plugins['search_by_distance'])
                ) {
                    $out[$index]['name'] = $this->getSBDFieldPlaceholder();
                    $out[$index]['type'] = 'mixed';

                    if ($config['sbd_units'] == 'auto') {
                        $config['sbd_units'] = in_array(
                            $_SESSION['GEOLocationData']->Country_code,
                            ['US', 'UK', 'GB', 'LR', 'MM']
                        ) ? 'miles' : 'kilometres';
                    }

                    $units = (string) ($config['sbd_default_units'] == 'kilometres'
                        ? $lang['sbd_km']
                        : $lang['sbd_mi']);

                    foreach (explode(',', $config['sbd_distance_items']) as $distance) {
                        $_values[] = $value = [
                            'key' => $distance,
                            'name' => "$distance $units",
                        ];

                        if ($distance == $config['sbd_default_distance']) {
                            $out[$index]['default'] = $value;
                        }
                    };
                } else {
                    if ($field['Values']) {
                        $_values = $field['Values'];
                    }
                }
                $out[$index]['values'] = $_values;

                // clear memory
                unset($data, $_values);
            }
        }

        // Search with photos only checkbox
        if ((int) $form[0]['With_picture']) {
            $out[] = array(
                'key' => 'ios_search_with_pictures',
                'type' => 'radio',
                'name' => '',
                'data' => 'isCheckBox',
                'values' => array(
                    array(
                        'key' => 'with_photo',
                        'name' => (string) $lang['with_photos_only'],
                    ),
                ),
            );
        }

        return $out;
    }

    /**
     * Get search forms for available account types
     **/
    public function getAccountSearchForms()
    {
        global $lang, $config, $rlDb, $reefless;

        $forms = array();

        if (defined('MULTI_FIELD_PLUGIN_INSTALLED') && MULTI_FIELD_PLUGIN_INSTALLED) {
            $sql = "SELECT * FROM `{db_prefix}multi_formats` ";
            $sql .= "WHERE `Status` = 'active'";

            if ($this->isMultiFieldNew()) {
                $sql .= " AND `Parent_ID` = 0";
            }

            global $multi_formats;
            $mf_tmp = $rlDb->getAll($sql);
            foreach ($mf_tmp as $item) {
                $multi_formats[$item['Key']] = $item;
            }
        }

        $reefless->loadClass('Account');

        foreach ($this->account_types as $type) {
            if (!$type['Page']) {
                continue;
            }

            if ($fields = $GLOBALS['rlAccount']->buildSearch($type['ID'])) {
                foreach ($fields as $field) {
                    $data = '';

                    switch ($field['Type']) {
                        case 'price':
                            $sql = "SELECT MAX(ROUND(`{$field['Key']}`)) AS `max` ";
                            $sql .= "FROM `{db_prefix}accounts` ";
                            $sql .= "WHERE `Status` = 'active'";
                            $max = $rlDb->getRow($sql);

                            $data = $max['max'] > 1000000 ? 1000000 : round($max['max']);
                            break;

                        case 'number':
                            if (is_numeric(strpos($field['Key'], 'zip'))) {
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

                                if (is_numeric(strpos($field['Key'], '_level'))) {
                                    unset($field['Values']);
                                }
                            }
                            break;

                        case 'phone':
                            $data = $this->phoneFieldData($field);
                            $field['Values'] = array();
                            break;
                    }

                    $_index = $forms[$type['Key']] ? count($forms[$type['Key']]) : 0;
                    $forms[$type['Key']][$_index] = array(
                        'key' => $field['Key'],
                        'type' => $field['Type'],
                        'name' => $this->cleanString($lang[$field['pName']]),
                        'data' => $data,
                    );

                    /* collect possible field values */
                    $_values = array();

                    if (is_array($field['Values'])) {
                        foreach ($field['Values'] as $item) {
                            switch ($field['Type']) {
                                case 'checkbox':
                                case 'radio':
                                    $set_key = str_replace($field['Key'] . '_', '', $item['Key']);
                                    break;

                                default:
                                    $set_key = $item['Key'];
                                    break;
                            }

                            $_values[] = array(
                                'key' => $set_key,
                                'name' => (string) $lang[$item['pName']],
                            );
                        }
                    } elseif ($field['Type'] == 'price') {
                        foreach ($GLOBALS['rlCategories']->getDF('currency') as $currency_item) {
                            $_values[] = array(
                                'key' => $currency_item['Key'],
                                'name' => (string) $currency_item['name'],
                            );
                        }
                    } elseif ($field['Key'] == 'zip') {
                        $units = (string) ($config['sbd_default_units'] == 'kilometres'
                            ? $lang['sbd_km']
                            : $lang['sbd_mi']);

                        foreach (explode(',', $config['sbd_distance_items']) as $mile) {
                            $_values[] = array(
                                'key' => $mile,
                                'name' => $mile . ' ' . $units,
                            );
                        }
                    } else {
                        if ($field['Values']) {
                            $_values = $field['Values'];
                        }
                    }
                    $forms[$type['Key']][$_index]['values'] = $_values;

                    // clear memory
                    unset($data);
                }
            }
        }
        return $forms;
    }

    /**
     * get listing information for "Edit Listing" process
     *
     * @param int $id - requested listing id
     * @param int $account_id - requested account id
     * @param string $account_type - requested account type key
     **/
    public function getEditListingData($listing_id = false, $account_id = false, $account_type = false)
    {
        global $rlDb, $reefless;

        if (!$account_id || !$listing_id || !$account_type) {
            $log_message = "iOS: " . __FUNCTION__ . "() no listing_id, account_id or account_type received, abort";
            $GLOBALS['rlDebug']->logger($log_message);
            return array('error_key' => 'account_access_denied');
        }

        // get listing info
        $sql = "SELECT `T1`.*, `T2`.`Cross` AS `Plan_crossed`, `T2`.`Key` AS `Plan_key`, `T3`.`Type` AS `Listing_type` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = {$listing_id} AND `T1`.`Account_ID` = {$account_id}";
        $listing = $rlDb->getRow($sql);

        /* Listing_type */
        $listing_type_key = $listing['Listing_type'];
        $listing_type = $this->listing_types[$listing_type_key];
        $response['listing_type_key'] = $listing_type_key;
        /* Listing_type END */

        /* listing category */
        $_category_fields = array('Key', 'Level', 'Parent_ID', 'Parent_IDs', 'Lock');
        $_category_where = array('ID' => $listing['Category_ID']);
        $category = $rlDb->fetch($_category_fields, $_category_where, null, 1, 'categories', 'row');

        // adapt category for app
        $category += array(
            'id' => $listing['Category_ID'],
            'key' => $category['Key'],
            'childrens' => !empty($category['Parent_IDs']),
            'lock' => (int) $category['Lock'],
            'name' => $this->trueNameOrKeyInstead(
                $GLOBALS['lang']['categories+name+' . $category['Key']],
                $category['Key']
            ),
        );
        unset($category['Key'], $category['Lock']);

        $reefless->loadClass('Categories');

        $response['category'] = $category;
        $category_id = (int) $category['id'];
        $parent_category_ids = $GLOBALS['rlCategories']->getParentIDs($category_id);
        /* listing category END */

        // get category parents
        $category_parents[] = 0;
        if ($parent_category_ids) {
            $category_parents = array_merge($category_parents, $parent_category_ids);
        }
        $category_parents[] = $category_id;

        $response['categories_ids'] = $category_parents;

        // add parent categories data
        $response['categories'] = array();
        foreach ($category_parents as $parent) {
            if (false != $categories = $this->getCategories($listing_type_key, $parent)) {
                $section_key = 'section_' . count($response['categories']);
                $response['categories'][$section_key] = $categories;
            }
        }
        /* ADD CATEGORIES DATA END */

        // form data
        $response['form'] = $this->getFormFields($listing['Category_ID'], $listing_type_key, $listing);

        // plans
        $response['plans'] = $this->getPlans($account_id, $listing['Category_ID'], $account_type);

        // plan info
        $_plan_fields = array('ID', 'Key', 'Type', 'Featured', 'Image', 'Image_unlim', 'Video', 'Video_unlim');
        $_plan_where = array('ID' => $listing['Plan_ID']);
        $plan_info = $rlDb->fetch($_plan_fields, $_plan_where, null, 1, 'listing_plans', 'row');
        $plan_info = $GLOBALS['rlLang']->replaceLangKeys($plan_info, 'listing_plans', array('name'));
        $response['plan'] = $plan_info;

        /* MEDIA DATA */
        if ($listing_type['Photo'] || $listing_type['Video']) {
            $rlDb->setTable('listing_photos');
            $_photo_fields = array('ID', 'Position', 'Photo', 'Original', 'Description', 'Type');
            $entries = $rlDb->fetch($_photo_fields, array('Listing_ID' => $listing_id), "ORDER BY `Position`");

            foreach ($entries as $index => $entry) {
                if ($entry['Type'] === 'picture') {
                    $response['photos'][] = array(
                        'id' => (int) $entry['ID'],
                        'type' => $entry['Type'],
                        'thumbnail' => RL_FILES_URL . $entry['Photo'],
                        'description' => (string) $entry['Description'],
                    );
                } else {
                    $video_preview_row = $entry['Original'] === 'youtube' ? 'Photo' : 'Thumbnail';
                    $response['videos'][] = array('Preview' => $entry[$video_preview_row]);
                }
                unset($entries[$index]);
            }
        }
        /* MEDIA DATA END */

        // clear memory
        unset($listing, $category, $plan_info);

        return $response;
    }

    /**
     * print value
     *
     * @param array $data - array of items to print
     * @param string $custom_tag - custom tag name if no tag name specified
     *
     * @return array
     **/
    public function printValue($data = false, $custom_tag = false)
    {
        $out = [];
        foreach ($data as $key => &$value) {
            $empty_tag = $custom_tag ?: 'item';
            $tag = is_numeric($key) ? $empty_tag : strtolower($key);

            if (is_array($value)) {
                $out[$tag] = $this->printValue($value);
            } else {
                $out[$tag] = $value;
            }
        }
        return $out;
    }

    /**
     * Define if the it's new version (2.2.0 and above) of the MultiField plugin
     *
     * @since 3.6.1
     *
     * @return boolean
     */
    public function isMultiFieldNew() {
        return isset($GLOBALS['config']['mf_format_keys']);
    }

    /**
     * Get child locations by parent
     *
     * @param string $parent
     * @param array  $locations
     *
     * @return array
     */
    public function getMDF($parent, $locations = array())
    {
        global $lang, $rlValid, $rlMultiField, $reefless, $rlDb;

        $response = array();

        $rlValid->sql($parent);

        if ($parent && file_exists(RL_PLUGINS . 'multiField/rlMultiField.class.php')) {
            $reefless->loadClass('MultiField', null, 'multiField');

            if ($locations) {
                if ($this->isMultiFieldNew()) {
                    $parent_id = $rlDb->getOne('Parent_ID', "`Key` = '{$parent}'", 'multi_formats');
                    $parent = $rlDb->getOne('Key', "`ID` = {$parent_id}", 'multi_formats');
                } else {
                    $parents = $rlMultiField->getParents($parent);

                    if ($parents[0]) {
                        $parent = $parents[0];
                    }
                }
            }

            if (method_exists($rlMultiField, 'getData')) {
                $locations = $rlMultiField->getData($parent, true, 'alphabetic');
            } else {
                $locations = $rlMultiField->getMDF($parent, 'alphabetic', true);
            }

            foreach ($locations as $location) {
                $response[] = array(
                    'key' => (string) $location['Key'],
                    'name' => $this->cleanString(!empty($location['name'])
                        ? $location['name']
                        : $lang[$location['pName']]
                    ),
                );
            }
        }

        return $response;
    }

    /**
     * Get listing form fields by specific category
     *
     * @param int $id - category id
     * @param string $type - listing type key
     * @param array &$listing_data - original listing data array
     *
     * @return array
     **/
    public function getFormFields($id, $type, &$listing_data = array())
    {
        global $lang, $reefless;

        $listing_type = $this->listing_types[$type];

        $reefless->loadClass('Categories');
        $tmp_form = $GLOBALS['rlCategories']->buildListingForm($id, $listing_type);
        $form_fields = array();

        foreach ($tmp_form as $group) {
            if (!empty($group['Fields']) && $group['Key'] != 'booking_rates') {
                if ($group['Group_ID']) {
                    $_fields = $this->adaptForm($group['Fields'], $listing_data, false, 'listing_fields');

                    if (empty($_fields)) {
                        continue;
                    }

                    $form_fields[] = array(
                        'name' => $this->cleanString($lang[$group['pName']]),
                        'fields' => $_fields,
                    );
                } else {
                    $_sfield = current($group['Fields']);

                    if (!$_sfield['Add_page']) {
                        continue;
                    }

                    $gIndex = count($form_fields);
                    $gIndex = $gIndex != 0 ? $gIndex - 1 : 0;

                    if (!is_array($form_fields[$gIndex]['fields'])) {
                        $form_fields[$gIndex]['fields'] = array();
                    }
                    $single_field = $this->adaptForm($group['Fields'], $listing_data, false, 'listing_fields');

                    if (empty($single_field)) {
                        continue;
                    }
                    $form_fields[$gIndex]['fields'][] = $single_field[0];
                }
            }
        }
        return $form_fields;
    }

    public function getGoogleAdmob()
    {
        global $rlDb;

        $rlDb->setTable('iflynax_admob');
        $admobs = $rlDb->fetch(array('Code', 'Side', 'Pages'), array('Status' => 'active'));
        $response = array();

        foreach ($admobs as $item) {
            $response[] = array(
                'code' => (string) $item['Code'],
                'side' => (string) $item['Side'],
                'pages' => array_map('intval', explode(',', $item['Pages'])),
            );
        }
        unset($admobs);

        return $response;
    }

    public function activeFields($mfield = false, $table = 'listing_fields')
    {
        global $rlLang, $rlDb;

        $info = array();
        $fields = $rlDb->fetch('*', array('Status' => 'active'), null, null, $table);
        $fields = $rlLang->replaceLangKeys($fields, $table, array('name'));
        return $this->adaptForm($fields, $info, $mfield, $table);
    }

    /**
     * Description
     * @param array &$fields
     * @param array &$info
     * @return array
     */
    public function adaptForm(&$fields, &$info, $multi_field = false, $multi_table = false)
    {
        global $lang, $rlDb, $reefless;

        $result = array();
        $multi_fields = false;

        if ($multi_field || $rlDb->getOne('Key', "`Status` = 'active' AND `Key` = 'multiField'", 'plugins')) {
            if (!$multi_table) {
                $d_trace = debug_backtrace();
                $multi_field_table = in_array($d_trace[1]['function'], array('getProfileForm'))
                ? 'account_fields'
                : 'listing_fields';
            } else {
                $multi_field_table = $multi_table;
            }

            $sql = "SELECT * FROM `{db_prefix}multi_formats` AS `T1`";
            $sql .= "JOIN `{db_prefix}{$multi_field_table}` AS `T2` ON `T2`.`Condition` = `T1`.`Key` ";
            $sql .= "WHERE `T1`.`Status` = 'active'";

            if ($this->isMultiFieldNew()) {
                $sql .= " AND `T1`.`Parent_ID` = 0";
            }

            $mf_tmp = $rlDb->getAll($sql);

            $multi_fields = array();
            foreach ($mf_tmp as $item) {
                $multi_fields[$item['Key']] = true;
            }
            unset($mf_tmp);
        }

        foreach ($fields as &$field) {
            $default_value = false;
            $rebuild = false;
            $data = '';

            if ($field['Type'] == 'file') {
                continue;
            }

            if (!$field['Add_page']) {
                continue;
            }

            if (in_array($field['Key'], $this->skip_lfield_keys_add)) {
                continue;
            }

            // tmp HOT fix
            if ($field['Multilingual']) {
                $info[$field['Key']] = $reefless->parseMultilingual($info[$field['Key']], RL_LANG_CODE);
            }

            switch ($field['Type']) {
                case 'text':
                case 'textarea':
                    if ($field['Condition']) {
                        $data = $field['Condition'];
                    }
                    $info[$field['Key']] = $this->cleanString($info[$field['Key']]);
                    break;

                case 'price':
                    $reefless->loadClass('Categories');
                    $field['Values'] = $GLOBALS['rlCategories']->getDF('currency');
                    $rebuild = true;
                    break;

                case 'number':
                    if ($field['Values']) {
                        $data = $field['Values'];
                    }
                    break;

                case 'phone':
                    $data = $this->phoneFieldData($field);

                    if (!empty($info[$field['Key']])) {
                        $field['Values'] = $reefless->parsePhone($info[$field['Key']]);
                        $info[$field['Key']] = $reefless->parsePhone($info[$field['Key']], $field); //current
                    }
                    break;

                case 'date':
                    if ($info[$field['Key']] == '0000-00-00') {
                        $info[$field['Key']] = '';
                    }

                    if ($field['Default'] == 'multi') {
                        $data = 'period';
                        $multi_key = $field['Key'] . '_multi';

                        $info[$field['Key']] = array(
                            'from' => (string) $info[$field['Key']],
                            'to' => (string) ($info[$multi_key] != '0000-00-00' ? $info[$multi_key] : ''),
                        );
                    } else {
                        $data = 'single';
                    }
                    break;

                case 'radio':
                case 'select':
                case 'checkbox':
                case 'mixed':
                    if ($field['Condition']) {
                        $data = $field['Condition'];
                    }
                    $rebuild = true;

                    if ($multi_fields && $multi_fields[$field['Key']]) {
                        $data = 'multiField';

                        if (is_numeric(strpos($field['Key'], '_level'))) {
                            // edit process
                            if (!empty($info[$field['Key']])) {
                                $_mf_current = $info[$field['Key']];
                                $rebuild = false;

                                $field['Values'] = $this->getMDF($_mf_current, true);
                            }
                            // add process
                            else {
                                unset($field['Values']);
                            }
                        } else {
                            $reefless->loadClass('MultiField', null, 'multiField');
                            if (method_exists($GLOBALS['rlMultiField'], 'getPhrases')) {
                                $GLOBALS['rlMultiField']->getPhrases($field['Condition']);
                            }
                        }
                    }
                    break;

                case 'accept':
                    if (version_compare($GLOBALS['config']['rl_version'], '4.7.0', '>=')) {
                        $accept_page_key = $field['Default'];
                        $field['pName'] = 'pages+name+' . $accept_page_key;
                        $content = (string) $lang["pages+content+{$accept_page_key}"];

                        if (!empty($content)) {
                            $data = $content;
                        } else {
                            $GLOBALS['pages'][$accept_page_key] = $this->getPagePath($accept_page_key);
                            $data = (string) $GLOBALS['reefless']->getPageUrl($accept_page_key);
                        }
                    } else {
                        $data = (string) $lang["{$multi_table}+default+{$field['Key']}"];
                        $field['pName'] = "{$multi_table}+name+{$field['Key']}";
                    }
                    break;
            }

            if ($rebuild) {
                if (is_array($field['Values'])) {
                    $values = $field['Values'];
                    $field['Values'] = array();

                    foreach ($values as $key => $value) {
                        if (trim($key) === '') {
                            continue;
                        }

                        $send_key = ($field['Condition'] || in_array($field['Type'], array('price', 'mixed')))
                        ? $value['Key']
                        : $value['ID'];

                        $_value = array(
                            'key' => (string) $send_key,
                            'name' => (string) ($lang[$value['pName']] ?: $send_key),
                        );
                        $field['Values'][] = $_value;

                        if ((int) $value['Default'] || $field['Default'] == $send_key) {
                            $default_value = $_value;
                        }
                    }
                    unset($values);
                }
            }

            // set fields to send
            $_field = array(
                'key' => $field['Key'],
                'type' => $field['Type'],
                'required' => (bool) $field['Required'],
                'multilingual' => (bool) $field['Multilingual'],
                'name' => $this->cleanString($field['name'] ?: $lang[$field['pName']]),
                'current' => $info[$field['Key']] ?: '',
                'values' => $field['Values'] ?: array(),
                'data' => $data,
            );

            if ($default_value) {
                $_field['default'] = $default_value;
            }
            $result[] = $_field;
        }
        return $result;
    }

    /**
     * Manage youtube videos
     *
     * @param  int   $listing_id
     * @param  array $youtube_videos
     * @return void
     */
    public function manageYouTubeVideo($listing_id, $youtube_videos = array())
    {
        global $rlDb;

        if (!$listing_id || empty($youtube_videos)) {
            return;
        }

        $sql = "
            SELECT `Photo` FROM `{db_prefix}listing_photos`
            WHERE `Listing_ID` = {$listing_id} AND `Type` = 'video'
        ";
        $current_videos = $rlDb->getAll($sql, array(false, 'Photo'));

        $youtube_ids = $new_videos = array();
        $video_position = 1;

        foreach ($youtube_videos as $youtube_video) {
            $youtube_ids[] = $youtube_video['ytid'];

            if (in_array($youtube_video['ytid'], $current_videos)) {
                continue;
            }

            $new_videos[] = array(
                'Listing_ID' => $listing_id,
                'Position' => $video_position,
                'Photo' => $youtube_video['ytid'],
                'Original' => 'youtube',
                'Type' => 'video',
            );

            $video_position++;
        }

        $rlDb->query("
            DELETE FROM `{db_prefix}listing_photos` WHERE `Listing_ID` = {$listing_id}
            AND `Photo` NOT IN ('" . implode("','", $youtube_ids) . "')
        ");

        if (!empty($new_videos)) {
            $rlDb->insert($new_videos, 'listing_photos');
        }
    }

    protected function rotateImageIfNecessary($file_path = false, $orientation = 0)
    {
        if (!file_exists($file_path) || $orientation == 0) {
            return false;
        }

        $image = @imagecreatefromjpeg($file_path);
        switch ($orientation) {
            case 1: // Down->180 deg rotation
                $image = @imagerotate($image, 180, 0);
                break;

            case 2: // Left->90 deg CCW
                $image = @imagerotate($image, 90, 0);
                break;

            case 3: // Right->90 deg CW
                $image = @imagerotate($image, 270, 0);
                break;

            default:
                return false;
        }
        $success = imagejpeg($image, $file_path);

        // Free up memory (imagedestroy doesn't delete files):
        @imagedestroy($image);

        return $success;
    }

    /**
     * Save the listing photo uploaded from app
     *
     * @since 3.6.0
     *
     * @param int   $listingId
     * @param array $photoDetails
     *
     * @return array
     */
    public function saveListingPhoto($listingId, $photoDetails)
    {
        if ($photoId = (int) $photoDetails['photo_id']) {
            $this->updateListingPhotoDescription($photoId, $photoDetails['desc']);

            return array('success' => true);
        }

        if ($_FILES['image']['error'] === UPLOAD_ERR_INI_SIZE) {
            return [
                'error_message_key' => 'account_unable_upload_image_exceeds_max_filesize'
            ];
        }

        require __DIR__ . '/Adapter/ListingPictureUploadAdapter.php';
        $GLOBALS['reefless']->loadClass('Listings');

        $uploader = (new ListingPictureUploadAdapter())
            ->setListingId($listingId)
            ->setImageOrientation($photoDetails['orientation']);
        $response = $uploader->uploadFromGlobals();

        $this->updateListingPhotoDescription($response['id'], $photoDetails['desc']);

        return $response;
    }

    /**
     * Update the listing photo description
     *
     * @since 3.6.0
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
                'Description' => $description
            ),
            'where' => array(
                'ID' => $photoId
            ),
        );
        $GLOBALS['rlDb']->updateOne($update, 'listing_photos');
    }

    /**
     * edit listing text data
     * TODO: params description
     **/
    public function editListing($account_id, $ltype_key, $category_id, $plan_id, $data)
    {
        global $config, $reefless, $rlDb;

        $listing_id = (int) $_REQUEST['lid'];

        if (!$account_id || !$listing_id) {
            $GLOBALS['rlDebug']->logger("IOS: " . __FUNCTION__ . "(), no listing_id or account_id received, abort");
            return array('error_message_key' => 'account_access_denied');
        }

        if ($rlDb->getOne('ID', "`ID` = {$listing_id} AND `Account_ID` = {$account_id}", 'listings')) {
            $reefless->loadClass('Plan');
            $reefless->loadClass('Actions');
            $reefless->loadClass('Account');
            $reefless->loadClass('Categories');
            $reefless->loadClass('Common');
            $reefless->loadClass('Listings');

            // get listing data
            $sql = "SELECT `T1`.*, `T1`.`Plan_ID`, `T1`.`Category_ID`, `T3`.`Type` AS `Listing_type` ";
            $sql .= "FROM `{db_prefix}listings` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
            $sql .= "WHERE `T1`.`ID` = '{$listing_id}' LIMIT 1";
            $listing = $rlDb->getRow($sql);

            // get plan data
            $plan_info = $rlDb->fetch(
                array('ID', 'Key', 'Type', 'Cross', 'Price', 'Listing_number', 'Cross', 'Image', 'Image_unlim', 'Video', 'Video_unlim'),
                array('ID' => $plan_id, 'Status' => 'active'),
                null, 1, 'listing_plans', 'row');

            // get related form fields
            $GLOBALS['rlCategories']->buildListingForm($category_id, $this->listing_types[$ltype_key]);
            foreach ($GLOBALS['rlCategories']->fields as $field) {
                $fields[$field['Key']] = $field;
            }

            if (!$config['edit_listing_auto_approval']) {
                $info['Status'] = 'pending';
            } else {
                $info['Status'] = $listing['Status'];
            }

            $GLOBALS['data'] = $data;
            $GLOBALS['listing'] = $listing;
            $GLOBALS['listing_id'] = $listing_id;

            $manageListing = new stdClass();
            $manageListing->listingID = $listing_id;
            $manageListing->listingData = $listing;
            $manageListing->formFields = $fields;
            $manageListing->listingType = $this->listing_types[$ltype_key];

            $GLOBALS['rlHook']->load('editListingAdditionalInfo', $manageListing, $data, $info);

            try {
                $edited = false;

                if ($GLOBALS['rlListings']->edit($listing_id, $info, $data, $fields, $plan_info)) {
                    $edited = true;
                }

                if ($edited) {
                    // manage youtube video
                    $this->manageYouTubeVideo($listing_id, $_REQUEST['youtube_videos']);

                    // remove pictures if necessary
                    $this->removePictures($_REQUEST['removed_picture_ids'], $listing_id);

                    // complete listing
                    $GLOBALS['rlHook']->load('afterListingEdit', $manageListing, $info, $data);

                    $this->completeEditListing($listing_id, $listing, $category_id, $ltype_key);

                    return array('success' => true);
                } else {
                    $GLOBALS['rlDebug']->logger("iOS: " . __FUNCTION__ . "(), edit() method returned false");
                    return array('error_message_key' => 'edit_listing_save_data_fail');
                }
            } catch (Exception $e) {
                $GLOBALS['rlDebug']->logger("iOS: " . __FUNCTION__ . "(), the exception is the following: " . $e->getMessage());
                return array('error_message_key' => 'edit_listing_save_data_fail');
            }
        } else {
            $GLOBALS['rlDebug']->logger("iOS: " . __FUNCTION__ . "(), access denied for {$account_id} (id) account");
            return array('error_message_key' => 'account_access_denied');
        }
    }

    /**
     * complete edit listing listing, send notification messages
     *
     * @param int $listing_id - requested listing id
     * @param array $listing - listing data before edit
     **/
    public function completeEditListing($listing_id = false, $listing = null, $category_id = false, $listing_type_key = false)
    {
        global $config, $lang, $rlValid, $account_info, $rlDb, $reefless;

        /* get updated listing info */
        $sql = "SELECT `T1`.*, `T1`.`Plan_ID`, `T1`.`Category_ID`, `T3`.`Type` AS `Listing_type` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = '{$listing_id}' LIMIT 1";
        $updated_listing = $rlDb->getRow($sql);

        /* send notification to admin and owner */
        if (!$config['edit_listing_auto_approval'] && serialize($updated_listing) != serialize($listing)) {
            $reefless->loadClass('Mail');

            $listing_title = $GLOBALS['rlListings']->getListingTitle($category_id, $updated_listing, $listing_type_key);

            /* send to admin */
            $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('admin_listing_edited');

            $lt_page_path = $rlDb->getOne('Path', "`Key` = 'lt_{$listing_type_key}'", 'pages');
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

            // Decrease related category counter
            $GLOBALS['rlCategories']->listingsDecrease($category_id);
        }
    }

    public function addListing($account_id, $ltype_key, $category_id, $plan_id, $form_data)
    {
        global $rlDebug, $rlListings, $rlCategories, $category, $listing_id, $reefless;

        // load system classes
        $reefless->loadClass('Categories');
        $reefless->loadClass('Listings');
        $reefless->loadClass('Actions');
        $reefless->loadClass('Plan');

        // fetch plan info by id
        $plan_info = $GLOBALS['rlPlan']->getPlan($plan_id, $account_id);
        $category['ID'] = $category_id;

        /* get & adapt form fields */
        $rlCategories->buildListingForm($category_id, $this->listing_types[$ltype_key]);
        foreach ($rlCategories->fields as $field) {
            $fields[$field['Key']] = $field;
        }
        /* get & adapt form fields END */

        // let's try to add the listing with data from app
        try {
            $info = array(
                'Category_ID' => $category_id,
                'Account_ID' => $account_id,
                'Status' => 'incomplete',
                'Last_type' => $ltype_key,
                'Last_step' => $plan_info['Price'] > 0 ? 'checkout' : 'form',
                'Date' => 'NOW()',
            );

            if ($rlListings->create($info, $form_data, $fields, $plan_info)) {
                $listing_id = $rlListings->id;
            }

            if ($listing_id) {
                $GLOBALS['rlHook']->load('afterListingCreate', $listing_id);

                // add youtube video
                $this->manageYouTubeVideo($listing_id, $_REQUEST['youtube_videos']);

                // complete saving & update status
                $this->completeAddListing(
                    $listing_id,
                    $plan_info,
                    $GLOBALS['rlValid']->xSql($_REQUEST['plan_mode']),
                    $account_id,
                    $ltype_key,
                    $category_id);

                // send success
                return array('success' => true, 'listing_id' => $listing_id);
            } else {
                $rlDebug->logger("iOS: " . __FUNCTION__ . "(), add() method returned false");
                return array('error_message_key' => 'add_listing_save_data_fail');
            }
        } catch (Exception $e) {
            $rlDebug->logger("iOS: " . __FUNCTION__ . "(), the exception is the following: " . $e->getMessage());
            return array('error_message_key' => 'add_listing_save_data_fail');
        }
    }

    /**
     * complete add listing, handle plan options and category counters
     *
     * @param int $listing_id - requested listing id
     * @param array $plan_info - plan information
     * @param string $appearence - listing plan type: standard or featured
     * @param int $account_id - requested account id
     * @param int $listing_type_key - listing type key
     * @param int $category_id - listing category id
     **/
    public function completeAddListing($listing_id = false, $plan_info = null, $appearence = false, $account_id = false, $listing_type_key = false, $category_id = false)
    {
        global $config, $lang, $rlValid, $reefless, $rlDb;

        $reefless->loadClass('Mail');
        $reefless->loadClass('Account');

        $set_status = $config['listing_auto_approval'] ? 'active' : 'pending';
        $free = true;
        $last_step = '';
        $featured = false;
        $account_info = $GLOBALS['rlAccount']->getProfile((int) $account_id);
        $paid_status = $plan_info['Price'] ? $lang['not_paid'] : $lang['free'];

        $listing_data = $rlDb->fetch('*', array('ID' => $listing_id), null, 1, 'listings', 'row');
        $listing_title = $GLOBALS['rlListings']->getListingTitle($category_id, $listing_data, $listing_type_key);

        // paid listing
        if (($plan_info['Type'] == 'package' && $plan_info['Price'] > 0 && !$plan_info['Package_ID']) ||
            ($plan_info['Type'] == 'listing' && $plan_info['Price'] > 0)
        ) {
            $set_status = 'incomplete';
            $last_step = 'checkout';
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
                'Featured_date' => ($free && $featured) ? 'NOW()' : '',
                'Last_step' => $last_step,
            ),
            'where' => array(
                'ID' => $listing_id,
            ),
        );
        $rlDb->updateOne($update_status, 'listings');

        // free listing or exist/free package mode
        if (($plan_info['Type'] == 'package' && ($plan_info['Package_ID'] || $plan_info['Price'] <= 0)) ||
            ($plan_info['Type'] == 'listing' && $plan_info['Price'] <= 0)
        ) {
            // available package mode
            if ($plan_info['Type'] == 'package' && $plan_info['Package_ID']) {
                if ($plan_info['Listings_remains'] != 0) {
                    $update_entry = array(
                        'fields' => array(
                            'Listings_remains' => $plan_info['Listings_remains'] - 1,
                        ),
                        'where' => array(
                            'ID' => $plan_info['Package_ID'],
                        ),
                    );

                    if ($plan_info[ucfirst($appearence) . '_listings'] != 0) {
                        $update_entry['fields'][ucfirst($appearence) . '_remains'] = $plan_info[ucfirst($appearence) . '_remains'] - 1;
                    }

                    $rlDb->updateOne($update_entry, 'listing_packages');
                }

                // set paid status
                $paid_status = $lang['purchased_packages'];
            }
            // free package mode
            elseif ($plan_info['Type'] == 'package' && !$plan_info['Package_ID'] && $plan_info['Price'] <= 0) {
                $insert_entry = array(
                    'Account_ID' => $account_id,
                    'Plan_ID' => $plan_info['ID'],
                    'Listings_remains' => ($plan_info[ucfirst($appearence) . '_listings'] == 0)
                    ? $plan_info['Listing_number']
                    : $plan_info['Listing_number'] - 1,
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

                $rlDb->insertOne($insert_entry, 'listing_packages');

                // set paid status
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

                    $GLOBALS['rlActions']->insertOne($plan_using_insert, 'listing_packages');
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

                    $rlDb->updateOne($plan_using_update, 'listing_packages');
                }
            }

            /* recount category listings count */
            if ($config['listing_auto_approval']) {
                $GLOBALS['rlCategories']->listingsIncrease($category_id);
            }

            /* send message to listing owner */
            $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate($config['listing_auto_approval']
                ? 'free_active_listing_created'
                : 'free_approval_listing_created');

            $lt_page_path = $rlDb->getOne('Path', "`Key` = 'lt_{$listing_type_key}'", 'pages');
            $my_page_path = $rlDb->getOne('Path', "`Key` = 'my_{$listing_type_key}'", 'pages');
            $category_path = $rlDb->getOne('Path', "`ID` = {$category_id}", 'categories');

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
            $activation_link = RL_URL_HOME . ADMIN . '/index.php?controller=listings&amp;action=remote_activation&amp;id=' . $listing_id . '&amp;hash=' . md5($rlDb->getOne('Date', "`ID` = '{$listing_id}'", 'listings'));
            $activation_link = '<a href="' . $activation_link . '">' . $activation_link . '</a>';
            $mail_tpl['body'] = preg_replace('/(\{if activation is enabled\})(.*)(\{activation_link\})(.*)(\{\/if\})/', '$2 ' . $activation_link . ' $4', $mail_tpl['body']);
        }

        $GLOBALS['rlMail']->send($mail_tpl, $config['notifications_email']);
    }

    /**
     * delete listing and related data
     *
     * @param int $listing_id - requested listing id
     * @param int $account_id - requested account id
     **/
    public function removeListing($listing_id = false, $account_id = false)
    {
        global $config, $rlListings, $rlCategories, $rlActions, $rlDb, $reefless;

        if (!$listing_id || !$account_id) {
            $GLOBALS['rlDebug']->logger("iOS: " . __FUNCTION__ . "(), no listing_id or account_id received, abort");
            return array('error_key' => 'remove_listing_fail');
        }

        $sql = "SELECT `T1`.`ID`, `T1`.`Category_ID`, `T2`.`Type`, `T1`.`Crossed`, `T1`.`Status`, `T2`.`Type` AS `Listing_type` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = {$listing_id} AND `T1`.`Account_ID` = {$account_id} AND `T1`.`Status` <> 'trash'";
        $listing = $rlDb->getRow($sql);

        if ($listing) {
            $reefless->loadClass('Listings');
            $reefless->loadClass('Actions');
            $reefless->loadClass('Categories');

            try {
                $GLOBALS['rlHook']->load('phpListingsAjaxDeleteListing', $listing);

                if ($config['trash']) {
                    $rlActions->delete(array('ID' => $listing['ID']), 'listings', $listing['ID'], 1, $listing['ID']);

                    /* decrease category listing */
                    if ($listing['Category_ID'] && $rlListings->isActive($listing['ID'])) {
                        $rlCategories->listingsDecrease($listing['Category_ID'], $listing['Listing_type']);

                        /* crossed listings count control */
                        if ($listing['Crossed']) {
                            $crossed = explode(',', $listing['Crossed']);
                            foreach ($crossed as $crossed_id) {
                                $rlCategories->listingsDecrease($crossed_id);
                            }
                        }
                    }
                } else {
                    $rlListings->deleteListingData($listing['ID'], $listing['Category_ID'], $listing['Crossed'], $listing['Listing_type']);
                    $rlActions->delete(array('ID' => $listing['ID']), 'listings', $listing['ID'], 1);
                }
                return array('success' => true);
            } catch (Exception $e) {
                $GLOBALS['rlDebug']->logger("iOS: " . __FUNCTION__ . "(), the exception is the following: " . $e->getMessage());
                return array('error_key' => 'remove_listing_fail');
            }
        }
        return array('error_key' => 'remove_listing_fail');
    }

    /**
     * Report Broken Listing
     *
     * @since 3.4.0
     *
     * @param  int    $account_id
     * @param  int    $listing_id
     * @param  string $message
     * @param  string $point_key
     * @return array
     */
    public function reportBrokenListing($account_id, $listing_id, $message, $point_key = null)
    {
        $response = array('success' => false);

        if (!isset(self::$active_plugins['reportBrokenListing']) || !$listing_id) {
            return $response;
        }

        if (version_compare(self::$active_plugins['reportBrokenListing'], '3.0.0', '>=')) {
            $GLOBALS['reefless']->loadClass('ReportBrokenListing', null, 'reportBrokenListing');

            $_REQUEST['listing_id'] = (int) $listing_id;
            $_REQUEST['key'] = $point_key ?: 'custom';
            $_POST['custom_message'] = $message;
            $out = array();

            $GLOBALS['rlReportBrokenListing']->hookAjaxRequest($out, 'RBLAddReport', null, RL_LANG_CODE);

            return array(
                'success' => $out['status'] === 'OK',
                'message' => $this->cleanString($out['message']),
            );
        }
        return $this->legacyReportBrokenListing($account_id, $listing_id, $message);
    }

    /**
     * Legacy Report Broken Listing
     *
     * @since 3.4.0
     *
     * @param  int    $account_id
     * @param  int    $listing_id
     * @param  string $message - Message of the report
     * @return array
     */
    public function legacyReportBrokenListing($account_id = 0, $listing_id = null, $message = null)
    {
        $max_message_length = (int) $GLOBALS['config']['reportBroken_message_length'];
        $message = substr($message, 0, $max_message_length);

        $insert = array(
            'Listing_ID' => $listing_id,
            'Account_ID' => $account_id,
            'Message' => $message,
            'Date' => 'NOW()',
        );
        $success = $GLOBALS['rlDb']->insertOne($insert, 'report_broken_listing');

        return array('success' => $success);
    }

    /**
     * @since v3.1.0
     */
    public function staticPageContent($page_key = false)
    {
        global $rlDb;

        $lang_code = strtolower(RL_LANG_CODE);
        $lang_direction = $rlDb->getOne('Direction', "`Code` = '{$lang_code}'", 'languages');
        $page_content = (string) $GLOBALS['rlLang']->getPhrase('pages+content+' . $page_key, null, null, true);

        $html = <<<HTML
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$lang_code}">
    <body dir="{$lang_direction}">
        {$page_content}
    </body>
</html>
HTML;

        $response['html'] = $html;

        return $response;
    }

    /**
     * upload profile image
     *
     * @param int $account_id - requested account id
     * @param string $password_hash - requested account password hash
     *
     * @return array
     **/
    public function uploadProfileImage($account_id = false)
    {
        global $config, $rlActions, $reefless, $rlDb;

        if (!$account_id) {
            $GLOBALS['rlDebug']->logger("iOS: " . __FUNCTION__ . "() no account_id received, abort");
            $response['error'] = 'No account_id received';
            return $response;
        }

        if (defined('IS_LOGIN') && IS_LOGIN) {
            $reefless->loadClass('Actions');

            if (version_compare($config['rl_version'], '4.6.2', '>=')) {
                require_once __DIR__ . '/Adapter/ProfileThumbnailUploadAdapter.php';
                return (new ProfileThumbnailUploadAdapter())->uploadFromGlobals();
            }

            $thumb_width = (int) $config['account_thumb_width'];
            $thumb_width = $thumb_width > 0 ? $thumb_width : 100;

            $thumb_height = (int) $config['account_thumb_height'];
            $thumb_height = $thumb_height > 0 ? $thumb_height : 100;

            $thumbnail_name = 'account-thumbnail-' . $account_id . '-' . mt_rand();
            $rlActions->photoSaveOriginal = true;

            if ($thumbnail_name = $rlActions->upload('profile-image', $thumbnail_name, 'C', array($thumb_width, $thumb_height), false, false)) {
                // remove old file if exists
                if ($current_thumbnail = $rlDb->getOne('Photo', "`ID` = " . $account_id, 'accounts')) {
                    unlink(RL_FILES . $current_thumbnail);
                }

                /**
                 * Remove Photo_original if exists
                 * @since Flynax v4.4.1
                 **/
                $photo_original = array_key_exists('Photo_original', $GLOBALS['account_info'])
                ? ', `Photo_original`'
                : '';

                /* determinate old thumbnails if necessary */
                $sql = "SELECT `Photo` {$photo_original} FROM `{db_prefix}accounts` ";
                $sql .= "WHERE `ID` = " . $account_id;
                $current_thumb_data = $rlDb->getRow($sql);

                if (!empty($current_thumb_data)) {
                    unlink(RL_FILES . $current_thumb_data['Photo']);

                    if (array_key_exists('Photo_original', $current_thumb_data)) {
                        unlink(RL_FILES . $current_thumb_data['Photo_original']);
                    }
                }
                /* determinate old thumbnails if necessary END */

                $update = array(
                    'fields' => array(
                        'Photo' => $thumbnail_name,
                    ),
                    'where' => array(
                        'ID' => $account_id,
                    ),
                );

                $rlDb->updateOne($update, 'accounts');
                $response['image'] = RL_FILES_URL . $thumbnail_name;
            } else {
                $GLOBALS['rlDebug']->logger("iOS: " . __FUNCTION__ . "() unable to upload image for {$account_id} (id) account, dipper debug required");
                $response['error'] = 'Unable to upload image';
            }
        } else {
            $GLOBALS['rlDebug']->logger("iOS: " . __FUNCTION__ . "() access denied for {$account_id} (id) account");
            $response['error'] = 'Access denied';
        }
        return $response;
    }

    /**
     * Update profile email address in confirmation mode
     *
     * @param int $account_id - requested account id
     * @param string $new_email - requested account password hash
     **/
    public function updateProfileEmail($account_id = false, $new_email = false)
    {
        global $rlDb;

        if (!$account_id || !$new_email) {
            $GLOBALS['rlDebug']->logger("IOS: " . __FUNCTION__ . "(), no username or new email address received, abort");
            return array('success' => false);
        }

        // Check duplicate e-mail
        $email_exist = $rlDb->getOne('Mail', "`Mail` = '{$new_email}' AND `ID` <> {$account_id}", 'accounts');

        if ($email_exist) {
            return array('error_key' => 'account_email_exist');
        }

        if ($GLOBALS['config']['account_edit_email_confirmation']) {
            /**
             * Simulate pages
             *
             * @see rlAccount::sendEditEmailNotification
             **/
            $GLOBALS['pages']['my_profile'] = $this->getPagePath('my_profile');

            $this->updateAccountEmailField($account_id, $new_email, 'Mail_tmp');

            $GLOBALS['rlAccount']->sendEditEmailNotification($account_id, $new_email);

            return array('success' => true, 'success_key' => 'dialog_email_saved_as_tmp');
        }

        $this->updateAccountEmailField($account_id, $new_email, 'Mail');

        return array('success' => true);
    }

    private function updateAccountEmailField($account_id, $email, $field)
    {
        $sql = "UPDATE `{db_prefix}accounts` SET `{$field}` = '{$email}' ";
        $sql .= "WHERE `ID` = {$account_id} LIMIT 1";
        $GLOBALS['rlDb']->query($sql);
    }

    /**
     * update profile/account data
     *
     * @param array $account_data - data from app
     **/
    public function updateMyProfile($account_data = false)
    {
        global $account_info, $reefless, $rlDb, $rlAccount;

        if (!$account_data) {
            $GLOBALS['rlDebug']->logger("iOS: " . __FUNCTION__ . "(), no account_data received, abort");
            return array('error_message_key' => 'edit_profile_save_data_fail');
        }

        if (IS_LOGIN) {
            $reefless->loadClass('Actions');
            $reefless->loadClass('Account');

            $account_id = (int) $account_info['ID'];
            $atype_id = (int) $rlDb->getOne('ID', "`Key` = '{$account_info['Type']}'", 'account_types');
            $fields = $GLOBALS['rlAccount']->getFields($atype_id);

            if ($rlAccount->editAccount($account_data, $fields, $account_id)) {
                // simulate hook data
                $GLOBALS['account_data'] = $account_data;
                $GLOBALS['rlHook']->load('profileEditAccountValidate');

                /* change account type */
                // if ($_REQUEST['current_type'] != $_REQUEST['account_type']) {
                //     $update_account_type = array(
                //         'fields' => array('Type' => $_REQUEST['account_type']),
                //         'where' => array('ID' => $account_id),
                //     );
                //     $GLOBALS['rlActions']->updateOne($update_account_type, 'accounts');
                // }

                return array('success' => true);
            } else {
                $GLOBALS['rlDebug']->logger("iOS: " . __FUNCTION__ . "(), unable to edit account ({$account_data['account_id']}) profile, $rlAccount->editAccount() returned false");
                return array('error_message_key' => 'edit_profile_save_data_fail');
            }
        } else {
            $GLOBALS['rlDebug']->logger("iOS: " . __FUNCTION__ . "(), access denied for {$account_id} (id) account");
            return array('error_message_key' => 'account_access_denied');
        }
    }

    /**
     * Change password
     *
     * @param int $account_id - account ID
     * @param string $old_pass - md5 crypted
     * @param string $new_pass - md5 crypted
     */
    public function changeAccountPassword($account_id = false, $old_pass = null, $new_pass = null)
    {
        global $account_info, $lang, $rlDb;

        $GLOBALS['rlValid']->sql($old_pass);
        $GLOBALS['rlValid']->sql($new_pass);
        $account_id = (int) $account_id;

        // check current password
        $_where = array('ID' => $account_id, 'Status' => 'active');
        $_current = $rlDb->fetch(array('Password'), $_where, null, null, 'accounts', 'row');

        /*
         * @since Flynax 4.4.0
         **/
        if (file_exists(RL_CLASSES . 'rlSecurity.class.php')) {
            require_once RL_CLASSES . 'rlSecurity.class.php';

            if (!FLSecurity::verifyPassword($old_pass, $_current['Password'])) {
                return array('error' => $lang['notice_incorrect_current_pass']);
            }
            $new_pass_hash = FLSecurity::cryptPassword($new_pass);
        }
        // old style passwords
        else {
            if (md5($old_pass) != $_current['Password']) {
                return array('error' => $lang['notice_incorrect_current_pass']);
            }
            $new_pass_hash = md5($new_pass);
        }

        $sql = "UPDATE `{db_prefix}accounts` SET `Password` = '{$new_pass_hash}' ";
        $sql .= "WHERE `ID` = " . $account_id;
        $rlDb->query($sql);

        $account_info['Password'] = $_SESSION['password'] = $_SESSION['account']['Password'] = md5($new_pass_hash);

        return array('success' => $lang['changes_saved']);
    }

    /**
     * @since 3.5.0
     *
     * @param $accountId
     * @param $password
     *
     * @return array
     */
    public function deleteAccount($accountId, $password)
    {
        global $reefless, $rlDb, $rlAdmin, $lang;

        $reefless->loadClass('Admin', 'admin');
        $reefless->loadClass('Categories');
        $reefless->loadClass('Listings');

        unset($GLOBALS['rlListingTypes']);
        $reefless->loadClass('ListingTypes', null, false, true);

        $db_pass = $rlDb->fetch(array('Password'), array('ID' => $accountId), null, null, 'accounts', 'row');

        if (FLSecurity::verifyPassword($password, $db_pass['Password'])
            && $rlAdmin->deleteAccountDetails($accountId, null, true)
        ) {
            $rlDb->delete(array('Account_ID' => $accountId), 'iflynax_push_tokens', null, 0);

            return array('success' => true, 'message' => (string) $lang['remote_delete_account_removed']);
        }

        return array('success' => false, 'error' => (string) $lang['notice_pass_bad']);
    }

    /**
     * Description
     * @param int $id - account ID
     * @return array
     */
    public function fetchSellerInfo($id, &$mobile_numbers = null)
    {
        global $reefless;

        $response = array();

        $reefless->loadClass('Account');
        $seller_info = $GLOBALS['rlAccount']->getProfile($id);

        if (empty($seller_info)) {
            return $response;
        }

        $response = array(
            'id' => (int) $seller_info['ID'],
            'type' => $seller_info['Type'],
            'username' => $seller_info['Username'],
            'fullname' => $seller_info['Full_name'],
            'lcount' => (int) $seller_info['Listings_count'],
        );

        $lat = (float) $seller_info['Loc_latitude'];
        $lng = (float) $seller_info['Loc_longitude'];

        if ($lat && $lng) {
            $response['location'] = array(
                'lat' => $lat,
                'lng' => $lng,
            );
        }

        if (!empty($seller_info['Photo']) && file_exists(RL_FILES . $seller_info['Photo'])) {
            $response['photo'] = RL_FILES_URL . $seller_info['Photo'];
        }

        if ($seller_info['Display_email']) {
            $response['email'] = $seller_info['Mail'];
        }

        if (!empty($seller_info['Fields'])) {
            $response['fields'] = array();

            foreach ($seller_info['Fields'] as $field) {
                if ($field['Details_page'] && $field['value'] != '') {
                    $seller_field = array(
                        'title' => $field['name'],
                        'value' => $this->cleanString($field['value']),
                    );

                    if (!empty($field['Condition']) && in_array($field['Condition'], array('isUrl', 'isEmail'))) {
                        $seller_field['condition'] = $field['Condition'];
                    } elseif ($field['Type'] == 'phone' || $field['Key'] == 'phone') {
                        $_phone_number = (string) preg_replace('/\W+/i', '', $field['value']);

                        $seller_field['condition'] = 'isPhone';
                        $seller_field['phoneNumber'] = $_phone_number;

                        if (is_array($mobile_numbers)) {
                            $mobile_numbers[] = array(
                                'title' => $this->cleanString($field['value']),
                                'value' => $_phone_number,
                            );
                        }
                    } elseif ($field['Type'] == 'image') {
                        $seller_field['value'] = $this->cleanString($field['value']);
                        $seller_field['condition'] = 'isImage';
                        list($_width, $_height) = explode('|', $field['Values'], 2);

                        $seller_field['size'] = array(
                            'width' => (int) $_width,
                            'height' => (int) $_height,
                        );
                    }

                    // collect it
                    $response['fields'][] = $seller_field;
                }
            }
        }

        return $response;
    }

    /**
     * fetch account statistics
     *
     * @param int $id - account ID
     * @return array $stats - account statistics data
     **/
    public function fetchAccountStat($id = false)
    {
        global $rlDb;

        $id = (int) $id;

        if (!$id) {
            $GLOBALS['rlDebug']->logger("iOS: " . __FUNCTION__ . "(), no account id provided, abort");
            return false;
        }

        /*
        $stats[] = array(
        'caption' => 'stat_blank',
        'items' => array(
        array('name' => 'Balance', 'number' => 878),
        array('name' => 'stat_messages', 'number' => 14, 'new' => 5),
        )
        );
         */

        $sql = "SELECT
            SUM(IF(`Status` = 'active', 1, 0)) `active`,
            SUM(IF(`Status` != 'active' AND `Status` != 'expired', 1, 0)) `inactive`,
            SUM(IF(`Status` = 'expired', 1, 0)) `expired`
            FROM `{db_prefix}listings`
            WHERE `Account_ID` = {$id}";

        $stat_listings = $rlDb->getRow($sql);

        // listings
        $listings = array(
            array('name' => 'status_active', 'number' => (int) $stat_listings['active']),
            array('name' => 'status_approval', 'number' => (int) $stat_listings['inactive']),
            array('name' => 'status_expired', 'number' => (int) $stat_listings['expired']),
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
            $_where = "`Key` = 'stat_rest_of_total' AND `Code` = '" . RL_LANG_CODE . "'";
            $of_phrase = $rlDb->getOne('Value', $_where, 'iflynax_phrases');

            $_standard_total = (int) $stat_plan['standard_total'];
            $_featured_total = (int) $stat_plan['featured_total'];

            $appearance_standard = $_standard_total > 0
            ? str_replace(array('{rest}', '{total}'), array($stat_plan['standard'], $_standard_total), $of_phrase)
            : $GLOBALS['lang']['unlimited'];

            $appearance_featured = $_featured_total > 0
            ? str_replace(array('{rest}', '{total}'), array($stat_plan['featured'], $_featured_total), $of_phrase)
            : $GLOBALS['lang']['unlimited'];

            $plans = array(
                array('name' => 'listing_appearance_standard', 'number' => $appearance_standard),
                array('name' => 'listing_appearance_featured', 'number' => $appearance_featured),
            );

            $stats[] = array(
                'caption' => 'stat_plan_packages',
                'items' => $plans,
            );
        }

        return $stats;
    }

    public function registerForRemoteNotification($account_id, $device_token)
    {
        global $rlDb;

        $_success = false;

        if ($device_token) {
            $GLOBALS['rlValid']->sql($device_token);
            $lang_code = $this->getAppLanguage();

            $exists_token_id = (int) $rlDb->getOne('ID', "`Token` = '{$device_token}'", 'iflynax_push_tokens');
            $_table = '{db_prefix}iflynax_push_tokens';

            // update status to active
            if ($exists_token_id) {
                $sql = "UPDATE `{$_table}` SET `Account_ID` = {$account_id}, `Status` = 'active', `Language` = '{$lang_code}' ";
                $sql .= "WHERE `ID` = " . $exists_token_id;
                $rlDb->query($sql);
            }
            // create a new token row
            else {
                $sql = "INSERT INTO `{$_table}` (`Account_ID`, `Token`, `Status`, `Language`) VALUES ";
                $sql .= "({$account_id}, '{$device_token}', 'active', '{$lang_code}')";
                $rlDb->query($sql);
            }
            $_success = true;
        }
        return array('success' => $_success);
    }

    public function unregisterForRemoteNotifications($device_token)
    {
        global $rlDb;

        $GLOBALS['rlValid']->sql($device_token);

        $sql = "UPDATE `{db_prefix}iflynax_push_tokens` SET `Status` = 'inactive' ";
        $sql .= "WHERE `Token` = '{$device_token}'";
        $rlDb->query($sql);
    }

    /**
     * @since v3.1.0
     */
    public function addToFavorites($listing_id, $account_id)
    {
        $new_row = array(
            'Account_ID' => $account_id,
            'Listing_ID' => $listing_id,
            'Date' => 'NOW()',
            'IP' => Util::getClientIP(),
        );
        $GLOBALS['rlDb']->insertOne($new_row, 'favorites');
    }

    /**
     * @since v3.1.0
     */
    public function removeFromFavorites($listing_id, $account_id)
    {
        $sql = "DELETE FROM `{db_prefix}favorites` ";
        $sql .= "WHERE `Account_ID` = {$account_id} AND `Listing_ID` = {$listing_id}";
        $GLOBALS['rlDb']->query($sql);
    }

    /**
     * Login as default
     *
     * @param string $username - account username
     * @param string $password - account password
     * @param bool $direct - allow login by MD5 password
     * @param string $device_token - token for remote notifications
     */
    public function login($username = false, $password = false, $direct = false, $device_token = false)
    {
        global $rlAccount, $rlValid, $config, $reefless, $rlDb;

        if (!$username || !$password) {
            return array('error' => 'username or password is empty');
        }

        // prevent SQL injection
        $rlValid->sql($username);
        $rlValid->sql($password);

        $response = array();
        if (true === $result = $rlAccount->login($username, $password, $direct)) {
            $response['logged'] = true;
            $response['token'] = $_SESSION['account'][$this->aTokenField];
            $response['account_id'] = $account_id = (int) $_SESSION['id'];

            if (!empty($_COOKIE['favorites'])) {
                $response['favorites'] = $_COOKIE['favorites'];
            }

            if (empty($response['token'])) {
                $response['token'] = md5($reefless->generateHash() . md5($config['security_key']));

                // save token for this account
                $sql = "UPDATE `{db_prefix}accounts` SET `" . $this->aTokenField . "` = '{$response['token']}' ";
                $sql .= "WHERE `ID` = " . $account_id . " LIMIT 1";
                $rlDb->query($sql);
            }

            // change status for device token if necessary
            $this->registerForRemoteNotification($account_id, $device_token);

            // get user short info
            $response += $this->fetchUserShortInfo($account_id);
        }
        // error
        else {
            $response['logged'] = false;
            // TODO: double check message with login attention functionality.
            $response['error'] = $this->cleanString(implode("\n", $result));
        }

        return $response;
    }

    /**
     * Login with token
     *
     * @param string $token - account token
     * @return bool true/false
     */
    public function loginWithToken($token = false)
    {
        global $rlDb;

        if (!$token) {
            return false;
        }
        $GLOBALS['rlValid']->sql($token);

        $sql = "SELECT `Username`, `Mail`, `Password` FROM `{db_prefix}accounts` ";
        $sql .= "WHERE `Status` = 'active' AND `" . $this->aTokenField . "` = '{$token}' LIMIT 1";
        $account = $rlDb->getRow($sql);

        if (!empty($account)) {
            $match_field = $GLOBALS['config']['account_login_mode'] == 'email' ? 'Mail' : 'Username';
            $user = $this->login($account[$match_field], $account['Password'], true);

            return $user['logged'];
        }
        return false;
    }

    /**
     * Allows users to log in via social networks
     * using SocialNetworkLogin plugin
     *
     * @since 3.6.0
     *
     * @param array $request
     *
     * @return array
     */
    public function socialNetworkLogin(array $request)
    {
        global $rlDb, $reefless;

        if (!isset(self::$active_plugins['hybridAuthLogin'])
            || version_compare(self::$active_plugins['hybridAuthLogin'], '2.0.0', '<')
        ) {
            return [];
        }

        $GLOBALS['reefless']->loadClass('HybridAuthLogin', null, 'hybridAuthLogin');
        $socialNetwork = new SocialNetworkLoginAPI();

        if (!empty($request['type_key'])) {
            $accountTypeId = $rlDb->getOne('ID', "`Key` = '{$request['type_key']}'", 'account_types');
            $request['account_type'] = (int) $accountTypeId;
        }

        try {
            $result = $socialNetwork->processUser($request);
        } catch (SocialNetworkException $e) {
            $result = [
                'action' => 'exception',
                'status' => 'error',
                'errors' => [$e->getMessage()],
            ];
        }

        if ($result['status'] == 'success'
            && ($result['action'] == 'login' || $result['action'] == 'registered')
        ) {
            $userData = $result['user_data'];
            unset($result['user_data']);

            $result['token'] = $_SESSION['account'][$this->aTokenField];
            $result['account_id'] = $accountId = (int) $userData['ID'];

            if (!empty($_COOKIE['favorites'])) {
                $result['favorites'] = $_COOKIE['favorites'];
            }

            if (empty($result['token'])) {
                $result['token'] = md5($reefless->generateHash() . md5($GLOBALS['config']['security_key']));

                $rlDb->query("
                    UPDATE `{db_prefix}accounts` SET `{$this->aTokenField}` = '{$result['token']}'
                    WHERE `ID` = {$accountId}
                ");
            }

            $result += $this->fetchUserShortInfo($userData['ID'], $userData);
        }

        return $result;
    }

    /**
     * @since 3.6.0
     *
     * @param string $email
     * @param string $password
     *
     * @return array
     */
    public function socialNetworkLoginVerifyUserPasswordByEmail($email, $passord)
    {
        global $reefless, $rlHybridAuthLogin;

        $reefless->loadClass('HybridAuthLogin', null, 'hybridAuthLogin');

        if ($rlHybridAuthLogin->verifyUserPasswordByEmail($email, $passord)) {
            return ['success' => true];
        }

        return ['error' => 'alert_password_does_not_match'];
    }

    /**
     * Quick user registration
     *
     * @param array $data - account data
     *
     * @return int - created account id
     **/
    public function registration($data)
    {
        global $config, $lang, $rlValid, $reefless, $rlDb;

        $rlValid->sql($data);

        $reefless->loadClass('Account');

        /* check email */
        if (!$rlValid->isEmail($data['email'])) {
            return array('error' => $lang['notice_bad_email']);
        } elseif ($rlDb->getOne('ID', "`Mail` = '{$data['email']}'", 'accounts')) {
            return array('error' => str_replace('{email}', '', $lang['notice_account_email_exist']));
        }
        /* check email end */

        // generate data in case of facebook connect login
        if ($data['fid']) {
            $data['type'] = $config['facebookConnect_account_type'];
            if (!$data['type'] || $data['type'] == 'any') {
                $data['type'] = $rlDb->getOne('Key', "`Key` <> 'visitor' ORDER BY `ID` DESC", 'account_types');
            }
        }

        // get requested account type info
        $fields = array('ID', 'Key', 'Abilities', 'Page', 'Own_location', 'Email_confirmation', 'Auto_login');
        $type_info = $rlDb->fetch($fields, array('Key' => $data['type']), null, 1, 'account_types', 'row');

        loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

        // e-mail login mode
        if ($config['account_login_mode'] == 'email') {
            $exp_email = explode('@', $data['email']);
            $username = $this->uniqueValue('Username', 'accounts', $exp_email[0]);
            $data['username'] = $username;
        }
        // username login mode
        else {
            $username = $data['username'];
            if (!$GLOBALS['rlAccount']->validateUsername($username)) {
                return array('error' => str_replace('{field}', $lang['username'], strip_tags($lang['notice_field_not_valid'])));
            } elseif ($rlDb->getOne('ID', "`Username` = '{$username}'", 'accounts')) {
                return array('error' => str_replace('{username}', $username, strip_tags($lang['notice_account_exist'])));
            }
        }

        $password = $data['password'];

        // personal address
        $own_address = $this->uniqueValue('Own_address', 'accounts', $rlValid->str2path($username));

        // disable verifications for FB users
        if ($data['fid']) {
            $type_info['Email_confirmation'] = false;
            $type_info['Auto_login'] = true;
        }

        /*
         * @since Flynax 4.4.0
         **/
        if (file_exists(RL_CLASSES . 'rlSecurity.class.php')) {
            require_once RL_CLASSES . 'rlSecurity.class.php';

            $password_hash = FLSecurity::cryptPassword($password);
        }
        // old style passwords
        else {
            $password_hash = md5($password);
        }

        // insert a new entry to db
        $insert = array(
            'Quick' => 1,
            'Type' => $data['type'],
            'Username' => $username,
            'First_name' => $username,
            'Own_address' => $own_address,
            'Password' => $password_hash,
            'Lang' => strtolower(RL_LANG_CODE),
            'Mail' => $data['email'],
            'Date' => 'NOW()',
            // we set 'active' status to all for better usability of APP, admin always can deactivate the account
            'Status' => $type_info['Email_confirmation'] ? 'incomplete' : 'active',
        );

        // add missed fb data
        if ($data['fid']) {
            $insert['facebook_ID'] = $data['fid'];
            $insert['First_name'] = $data['first_name'];
            $insert['Last_name'] = $data['last_name'];
            $insert['Quick'] = 0;
        }

        // save password to send it to the user
        if ($type_info['Email_confirmation']) {
            $insert['Password_tmp'] = $password;
        }

        $GLOBALS['rlHook']->load('phpQuickRegistrationBeforeInsert', $insert, $data);

        $reefless->loadClass('Actions');
        $insert_success = $rlDb->insertOne($insert, 'accounts');
        $account_id = $rlDb->insertID();
        unset($insert);

        if ($insert_success && $account_id) {
            /* hotfix for legacy app's (< v3.3.0) */
            if (!$config['iflynax_registration_2step']) {
                $data['account'] = array();
            }
            /* hotfix END */

            if (!empty($data['account'])) {
                $account_type_id = (int) $rlDb->getOne('ID', "`Key` = '{$data['type']}'", 'account_types');
                $account_fields = $GLOBALS['rlAccount']->getFields($account_type_id);

                if (!empty($account_fields)) {
                    $GLOBALS['account_info']['ID'] = $account_id;
                    $GLOBALS['rlAccount']->editAccount($data['account'], $account_fields, $account_id);
                }
            }
        } else {
            return array(
                'success' => false,
                // TODO: remove the hardcode phrase
                'error' => 'The system failed to create your account; please contact the Administrator.',
            );
        }

        // send notification email to the user
        $reefless->loadClass('Mail');

        // email confirmation case
        if ($type_info['Email_confirmation']) {
            $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('iflynax_pending_account_created');

            $confirm_path = $this->getPagePath('confirm');
            $confirm_code = md5(mt_rand());

            // create activation link
            $activation_link = SEO_BASE;
            $activation_link .= $config['mod_rewrite']
            ? $confirm_path . '.html?key='
            : '?page=' . $confirm_path . '&amp;key=';

            $activation_link .= $confirm_code;
            $activation_link = '<a href="' . $activation_link . '">' . $activation_link . '</a>';

            $replace = array(
                '{username}' => $data['username'],
                '{link}' => $activation_link,
            );
            $mail_tpl['body'] = str_replace(array_keys($replace), array_values($replace), $mail_tpl['body']);

            // save confirmation code
            $sql = "UPDATE `{db_prefix}accounts` SET `Confirm_code` = '{$confirm_code}' ";
            $sql .= "WHERE `ID` = {$account_id} LIMIT 1";
            $rlDb->query($sql);
        }
        // activated account case
        else {
            $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('account_created_active');

            $login_path = $this->getPagePath('login');

            $account_area_link = SEO_BASE;
            $account_area_link .= $config['mod_rewrite'] ? $login_path . '.html' : '?page=' . $login_path;
            $account_area_link = '<a href="' . $account_area_link . '">' . $lang['blocks+name+account_area'] . '</a>';

            $replace = array(
                '{username}' => $data['username'],
                '{password}' => $password,
                '{name}' => $data['username'], // no full name available on this step
                '{account_area}' => $account_area_link,
                '{plan_info}' => '',
                '{login}' => (string) $data[$config['account_login_mode']],
            );
            $mail_tpl['body'] = str_replace(array_keys($replace), array_values($replace), $mail_tpl['body']);
        }

        $GLOBALS['rlMail']->send($mail_tpl, $data['email']);

        // send notification e-mail to administrator
        $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('iflynax_account_created_admin');

        $details_link = RL_URL_HOME . ADMIN . '/index.php?controller=accounts&amp;action=view&amp;userid=' . $account_id;
        $details_link = '<a href="' . $details_link . '">' . $details_link . '</a>';

        $replace = array(
            '{username}' => $data['username'],
            '{link}' => $details_link,
            '{email}' => $data['email'],
            '{date}' => date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT)),
            '{account_type}' => $lang['account_types+name+' . $type_info['Key']],
        );
        $mail_tpl['body'] = str_replace(array_keys($replace), array_values($replace), $mail_tpl['body']);
        $mail_tpl['subject'] = str_replace('{username}', $data['username'], $mail_tpl['subject']);

        $GLOBALS['rlMail']->send($mail_tpl, $config['site_main_email']);

        $response = array(
            'success' => true,
            'message_key' => ($type_info['Email_confirmation']
                ? 'alert_registration_complete_email_confirmation'
                : ($type_info['Auto_login']
                    ? 'alert_registration_complete_auto_login'
                    : 'alert_registration_complete_active'
                )
            ),
        );

        // simulate auto login
        if ($type_info['Auto_login'] && !$type_info['Email_confirmation']) {
            $match_field = $config['account_login_mode'] == 'email' ? 'email' : 'username';
            $response += $this->login($data[$match_field], $password);
        }

        return $response;
    }

    /**
     * Set current timezone to PHP and MySQL
     *
     * @param string $timeZone - timezone of the application user
     **/
    public function setTimeZone($timeZone = false)
    {
        global $rlDb;

        $GLOBALS['rlValid']->Sql($timeZone);

        if (!$timeZone) {
            return false;
        }

        // set PHP timezone
        @date_default_timezone_set($timeZone);

        $tz = new DateTimeZone($timeZone);
        $date = new DateTime(false, $tz);
        $gmt = $date->format('P');

        if (!$gmt) {
            return false;
        }

        // set MySQL timezone
        $rlDb->query("SET time_zone = '{$gmt}'");

        return true;
    }

    /**
     * Admin Panel bread crumbs handler
     **/
    public function breadCrumbs()
    {
        global $cInfo, $breadCrumbs, $rlSmarty;

        if (preg_match('/^iflynax_/i', $cInfo['Controller'])) {
            $breadCrumbs[0]['name'] = 'iOS ' . $cInfo['name'];
            $breadCrumbs[0]['Controller'] = $cInfo['Controller'];

            if (!$_GET['action']) {
                $rlSmarty->assign('cpTitle', $cInfo['name']);
            }
        } elseif ($cInfo['Controller'] == 'email_templates' && $_GET['module'] == 'ios_app') {
            $breadCrumbs[0]['name'] = 'iOS';

            if (!$_GET['action']) {
                $rlSmarty->assign('cpTitle', 'iOS ' . $cInfo['name']);
            }
        }
    }

    /**
     * Remove photos which were removed during edit listing process
     *
     * @param  array $ids        - Photo IDs
     * @param  int   $listing_id - Requested listing ID
     *
     * @since 3.6.0 - Used ListingMedia::delete method
     */
    public function removePictures($ids, $listing_id)
    {
        global $account_info;

        if (!$listing_id || !$account_info || empty($ids)) {
            return;
        }

        foreach ($ids as $photoId) {
            ListingMedia::delete($listing_id, $photoId, $account_info);
        }
    }

    /**
     * @since 3.7.2
     */
    private function getSBDFieldPlaceholder()
    {
        global $config, $lang;

        $searchHintKey = ('mixed' == $config['sbd_search_mode']
            ? 'sbd_location_search_hint'
            : 'sbd_zipcode'
        );

        return (string) str_replace('{type}', $lang[$searchHintKey], $lang['sbd_within']);
    }

    /**
     * @param string $location
     *
     * @since 3.7.2
     */
    public function placesAutocomplete($location)
    {
        global $config;

        $provider = $config['geocoding_provider'] == 'google'
        ? 'googlePlaces' // switch to googlePlaces for better results
        : $config['geocoding_provider'];

        if (false === $locations = Util::geocoding($location, false, null, $provider)) {
            return ['success' => false];
        }

        return [
            'success'   => true,
            'locations' => (array) $locations,
        ];
    }

    /**
     * @param string $placeId
     *
     * @since 3.7.2
     *
     * @return false|stdClass
     */
    private function getPlaceLocation($placeId)
    {
        global $config;

        if (empty($placeId) || empty($config['google_server_map_key'])) {
            return false;
        }

        $host = 'https://maps.googleapis.com/maps/api/place/details/json';
        $apiRequest  = sprintf('%s?placeid=%s&key=%s', $host, $placeId, $config['google_server_map_key']);
        $data = json_decode(Util::getContent($apiRequest));

        if ('OK' != $data->status || empty($data->result)) {
            return false;
        }

        return $data->result->geometry->location;
    }

    /**
     * @deprecated 3.6.0
     */
    public static function erasePictures()
    {}

    /**
     * @deprecated 3.6.0
     */
    public static function rmPics()
    {}

    /**
     * @deprecated 3.1.0
     */
    public function setupLanguages()
    {}

    /**
     * @deprecated 3.1.1
     */
    public function defineMainListingType()
    {}

    /**
     * @hook phpUpdatePhotoDataSetFields
     *
     * @deprecated 3.6.0
     *
     * @since 3.1.0
     */
    public function hookPhpUpdatePhotoDataSetFields()
    {
        trigger_error(
            sprintf('Method %s is deprecated since 3.6.0', __METHOD__),
            E_USER_DEPRECATED
        );
    }

    /**
     * @hook apTplControlsForm
     *
     * @deprecated 3.6.0
     *
     * @since 3.1.0
     */
    public function hookApTplControlsForm()
    {
        trigger_error(
            sprintf('Method %s is deprecated since 3.6.0', __METHOD__),
            E_USER_DEPRECATED
        );
    }

    /**
     * Add condition and change order of listing types
     *
     * @hook listingTypesGetModifySql
     *
     * @since 3.7.1
     */
    public function hookListingTypesGetModifySql(&$sql)
    {
        if (!defined('IOS_APP')) {
            return;
        }

        $sql = str_replace('WHERE ', "WHERE `T1`.`iFlynax_status` = 'active' AND ", $sql);
        $sql = str_replace('ORDER BY `Order`', 'ORDER BY `iFlynax_position`', $sql);
    }

    /**
     * Save listing picture
     *
     * @deprecated 3.6.0 - Use saveListingPhoto instead
     *
     * @param int   $listing_id
     * @param array $info
     *
     * @return array
     */
    public function savePicture($listing_id, $info)
    {
        trigger_error(
            sprintf('Method "%s" is deprecated since 3.6.0', __METHOD__),
            E_USER_DEPRECATED
        );

        return $this->saveListingPhoto($listing_id, $info);
    }

    /**
     * Resize stack of photos
     *
     * @deprecated 3.6.0
     */
    public function resizePhotos()
    {
        trigger_error(
            sprintf('Method %s is deprecated since 3.6.0', __METHOD__),
            E_USER_DEPRECATED
        );
    }

    /**
     * Resize photo
     *
     * @deprecated 3.6.0
     *
     * @param string $sql
     * @param array  $listing_data
     * @param string $id
     */
    public function resizePhoto(&$sql, &$listing_data, &$id)
    {
        trigger_error(
            sprintf('Method %s is deprecated since 3.6.0', __METHOD__),
            E_USER_DEPRECATED
        );
    }

    /**
     * Resize
     *
     * @deprecated 3.6.0
     *
     * @param string $photo
     * @param int    $listing_id
     */
    public function resize(&$photo, &$listing_id)
    {
        trigger_error(
            sprintf('Method %s is deprecated since 3.6.0', __METHOD__),
            E_USER_DEPRECATED
        );
    }
}
