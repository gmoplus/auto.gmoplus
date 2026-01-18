<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLOPENAI.CLASS.PHP
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

use Flynax\Utils\Valid;
use Flynax\Plugins\OpenAI\ProviderResolver;

class rlOpenAI extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * Install process
     */
    public function install()
    {
        global $rlDb;

        $rlDb->createTable(
            'openai_data',
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
            `Listing_ID` varchar(11) NOT NULL,
            `Field_key` varchar(128) NOT NULL,
            `Lang` varchar(2) NOT NULL,
            `Text` text NOT NULL,
            PRIMARY KEY (`ID`),
            INDEX (`Listing_ID`)"
        );

        $position = $rlDb->getRow("SELECT MAX(`Position`) AS `Max` FROM `{db_prefix}membership_services`");
        $rlDb->insertOne([
            'Key' => 'openai',
            'Status' => 'trash',
            'Position' => $position['Max'] ? $position['Max'] + 1 : 1
        ], 'membership_services');

        $rlDb->insert([
            [
                'Key' => 'openai_yandex_api_key',
                'Type' => 'text',
                'Plugin' => 'openAI'
            ], [
                'Key' => 'openai_yandex_token_time',
                'Type' => 'text',
                'Plugin' => 'openAI'
            ]
        ], 'config');
    }

    /**
     * Uninstall process
     */
    public function uninstall()
    {
        global $rlDb;

        $rlDb->dropTable('openai_data');
        $rlDb->delete(['Key' => 'openai'], 'membership_services');
    }

    public function __construct()
    {
        require_once __DIR__ . '/vendor/autoload.php';
    }

    /**
     * Print notice in admin panel
     *
     * @hook apNotifications
     * @param array $notices - global notifications array
     */
    public function hookApNotifications(&$notices)
    {
        if (!extension_loaded('curl')) {
            $notices[] = $GLOBALS['rlLang']->getSystem('openai_error_curl_required');
        }
    }

    /**
     * Prepare plugin configs values
     *
     * @hook apMixConfigItem
     * @param array $option - config items
     */
    public function hookApMixConfigItem(&$option, &$systemSelects)
    {
        global $rlDb, $lang;

        if ($option['Plugin'] != 'openAI') {
            return;
        }

        if (in_array($option['Key'], ['openai_provider', 'openai_access'])) {
            $systemSelects[] = $option['Key'];
        }

        if ($option['Key'] == 'openai_title_field') {
            $rlDb->setTable('listing_fields');
            $option['Values'] = [];

            foreach ($rlDb->fetch(['Key'], ['Status' => 'active', 'Type' => 'text']) as $item) {
                $option['Values'][] = array('ID' => $item['Key'], 'name' => $lang['listing_fields+name+'.$item['Key'] ]);
            }
        }
    }

    /**
     * Manage membership service
     *
     * @hook apPhpConfigAfterUpdate
     */
    public function hookApPhpConfigAfterUpdate()
    {
        global $config;

        $access = $GLOBALS['dConfig']['openai_access']['value'];
        $status = '';

        // Manage path fields
        if ($config['openai_access'] != 'membership' && $access == 'membership') {
            $status = 'active';
        } elseif ($config['openai_access'] == 'membership' && $access != 'membership') {
            $status = 'trash';
        }

        if ($status) {
            $update = [
                'fields' => ['Status' => $status],
                'where' => ['Key' => 'openai']
            ];
            $GLOBALS['rlDb']->updateOne($update, 'membership_services');
        }
    }

    /**
     * @hook ajaxRequest
     **/
    public function hookAjaxRequest(&$out, $request_mode, $request_item, $request_lang)
    {
        global $config, $rlDb, $lang, $rlLang;

        if (!extension_loaded('curl')) {
            return;
        }

        if ($request_mode != 'openAI') {
            return;
        }

        if (!$this->isPluginAllowed()) {
            return;
        }

        $providerResolver = new ProviderResolver();
        $provider = $providerResolver->getProvider($config['openai_provider']);

        if (!$provider) {
            return;
        }

        $title = $_REQUEST['title'];
        $data = $_REQUEST['data'];
        $user_message = $_REQUEST['user_message'];
        $system_message = $_REQUEST['system_message'];
        $listing_id = Valid::escape($_REQUEST['listing_id']);
        $field_key = Valid::escape($_REQUEST['field_key']);

        if (!$field_key || !$listing_id || !$request_lang) {
            return;
        }

        $field_info = $rlDb->fetch(['ID', 'Values'], ['Key' => $field_key], null, 1, 'listing_fields', 'row');

        if (!$field_info) {
            return;
        }

        // Return saved text
        if ($db_data = $rlDb->fetch(['Text', 'Lang'], ['Listing_ID' => $listing_id, 'Field_key' => $field_key], null, 1, 'openai_data', 'row')) {
            $out = [
                'status' => 'OK',
                'results' => $db_data
            ];
            return;
        }

        if (!$phrases = $this->getPluginPhrases($request_lang)) {
            return;
        }

        if ($title && $data) {
            $options = implode(', ', $data);

            if (!$phrases['openai_message_system_template']) {
                $GLOBALS['rlDebug']->logger('OpenAI error: the "system message" template phrase (openai_message_system_template) is empty');
                $out = [
                    'status' => 'ERROR',
                    'message' => $rlLang->getSystem('openai_error_system_message_template')
                ];
                return;
            } else {
                $system_message_content = $phrases['openai_message_system_template'];
            }

            if (false === strpos($phrases['openai_message_user_template'], '{title}')) {
                $error_message = '';
                $GLOBALS['rlDebug']->logger('OpenAI error: No {title} variable found in openai_message_user_template phrase');
                $out = [
                    'status' => 'ERROR',
                    'message' => $rlLang->getSystem('openai_error_user_message_template_title')
                ];
                return;
            } else {
                $user_message_content = str_replace(['{title}', '{options}'], [$title, $options], $phrases['openai_message_user_template']);
            }
        } elseif ($system_message && $user_message) {
            if (strlen($system_message) < 10) {
                $error_message = str_replace('{field}', sprintf('"%s"', $phrases['openai_system_message']), $lang['notice_field_empty']);
                $out = [
                    'status' => 'ERROR',
                    'message' => $error_message
                ];
                return;
            } else {
                $system_message_content = $system_message;
            }

            if (strlen($user_message) < 10) {
                $error_message = str_replace('{field}', sprintf('"%s"', $phrases['openai_user_message']), $lang['notice_field_empty']);
                $GLOBALS['rlDebug']->logger('OpenAI error: ' . $error_message);
                $out = [
                    'status' => 'ERROR',
                    'message' => $error_message
                ];
                return;
            } else {
                $user_message_content = $user_message;
            }
        } else {
            $out = [
                'status' => 'ERROR'
            ];
            return;
        }

        $out = $provider->request($system_message_content, $user_message_content, intval($field_info['Values']));

        if ($out['status'] == 'OK') {
            $insert = [
                'Listing_ID' => $listing_id,
                'Field_key' => $field_key,
                'Lang' => $request_lang,
                'Text' => $out['results']
            ];
            $rlDb->insertOne($insert, 'openai_data');
        }
    }

    /**
     * Display plugin option in textarea field
     *
     * @hook apTplFieldsFormTextarea
     */
    public function hookApTplFieldsFormTextarea()
    {
        if ($GLOBALS['cInfo']['Controller'] == 'listing_fields') {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'openAI/admin/option.tpl');
        }
    }

    /**
     * Save option value
     *
     * @hook apPhpListingFieldsAfterAdd
     */
    public function hookApPhpListingFieldsAfterAdd()
    {
        $this->updateField();
    }

    /**
     * Save option value
     *
     * @hook apPhpListingFieldsAfterEdit
     */
    public function hookApPhpListingFieldsAfterEdit()
    {
        $this->updateField();
    }

    /**
     * Update field openAI option
     */
    public function updateField(): void
    {
        $update = [
            'fields' => ['Opt2' => $_POST['openai']],
            'where' => ['Key' => $GLOBALS['f_key']],
        ];
        $GLOBALS['rlDb']->updateOne($update, 'listing_fields');
    }

    /**
     * Simulate post
     *
     * @hook apPhpListingFieldsBottom
     */
    public function hookApPhpListingFieldsBottom()
    {
        if ($GLOBALS['field_info']) {
            $_POST['openai'] = $GLOBALS['field_info']['Opt2'];
        }
    }

    /**
     * Add javascript to the form
     *
     * @hook tplStepFormAfterForm
     */
    public function hookTplStepFormAfterForm()
    {
        global $rlDb, $rlSmarty, $config, $rlStatic;

        if (!$this->isPluginAllowed()) {
            return;
        }

        $providerResolver = new ProviderResolver();
        $provider = $providerResolver->getProvider($config['openai_provider']);

        if (!$provider) {
            return;
        }

        $display = false;
        $text_fields = [];

        if ($fields = $rlSmarty->_tpl_vars['manageListing']->formFields) {
            foreach ($fields as $field) {
                if ($field['Type'] == 'textarea' && $field['Opt2']) {
                    $display = true;
                    $text_fields[] = $field['Key'];
                }
            }
        }

        if ($display) {
            if ($config['add_listing_single_step'] || $rlSmarty->_tpl_vars['manageListing']->listingID) {
                $listing_id = $rlSmarty->_tpl_vars['manageListing']->listingID;
            } else {
                $listing_id = $_SESSION['openai_hash_id'] ?: 'r' . mt_rand(100000, 999999);
                $_SESSION['openai_hash_id'] = $listing_id;
            }

            $rlStatic->addFooterCss(RL_TPL_BASE . 'components/popup/popup.css');
            $rlStatic->addFooterCss(RL_TPL_BASE . 'components/popover/popover.css');

            $rlStatic->addJS(RL_TPL_BASE . 'components/popup/_popup.js');
            $rlStatic->addJS(RL_TPL_BASE . 'components/popover/_popover.js');

            $rlDb->outputRowsMap = ['Field_key', true];
            $db_data = $rlDb->fetch(['ID', 'Field_key'], ['Listing_ID' => $listing_id], null, null, 'openai_data');
            $phrases = $this->getPluginPhrases(RL_LANG_CODE);

            $rlSmarty->assign_by_ref('openai_phrases', $phrases);
            $rlSmarty->assign('openai_db_data', $db_data);
            $rlSmarty->assign('openai_fields', $text_fields);
            $rlSmarty->assign('openai_listing_id', $listing_id);
            $rlSmarty->display(RL_PLUGINS . 'openAI/field_handler.tpl');
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
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'openAI/admin/settings_js.tpl');
        }
    }

    /**
     * Update listing ID in plugin table
     *
     * @hook afterListingDone
     *
     * @param  object &$manageListing - Add listing class object
     * @param  array  &$data          - Listing data
     * @param  bool &$isFree          - Is free flag
     */
    public function hookAfterListingDone(&$manageListing, &$data, &$isFree)
    {
        if ($_SESSION['openai_hash_id'] && $manageListing->listingID) {
            $GLOBALS['rlDb']->updateOne([
                'fields' => ['Listing_ID' => $manageListing->listingID],
                'where' => ['Listing_ID' => $_SESSION['openai_hash_id']],
            ], 'openai_data');

            unset($_SESSION['openai_hash_id']);
        }
    }

    /**
     * Get plugin phrases
     *
     * @param  string $langCode - Lang code
     * @return array            - Phrases array
     */
    private function getPluginPhrases(string $langCode): array
    {
        global $rlDb;

        $rlDb->outputRowsMap = ['Key', 'Value'];
        $plugin_lang = $rlDb->fetch(
            ['Key', 'Value'],
            ['Code' => $langCode, 'Plugin' => 'openAI', 'Module' => 'system'],
            "AND `Key` NOT LIKE 'openai_error_'",
            null, 'lang_keys'
        );

        return $plugin_lang ?: [];
    }

    /**
     * Define is plugin available for current user
     *
     * @return boolean - Is plugin available
     */
    private function isPluginAllowed(): bool
    {
        global $config, $account_info;

        $access = false;

        if ($config['openai_access'] == 'all'
            || (
                $config['openai_access'] == 'authorized'
                && is_array($account_info)
                && $account_info['ID']
            )
            || (
                $config['openai_access'] == 'membership'
                && is_array($account_info)
                && $account_info['ID']
                && $account_info['plan']
                && $account_info['plan']['Services']['openai']
            )
        ) {
            $access = true;
        }

        return $access;
    }

    /**
     * Update to 1.1.0
     */
    public function update110()
    {
        global $languages, $rlDb, $rlLang;

        $update = [
            'fields' => ['Position' => 3],
            'where' => ['Key' => 'openai_deepseek_api_key']
        ];
        $GLOBALS['rlDb']->updateOne($update, 'config');

        // Update plugin name
        $updatePhrases = [];
        $names = [
            'en' => 'AI Text Generation',
            'ru' => 'Генерация текста ИИ'
        ];

        foreach ($languages as $language) {
            $phrase = $names[$language['Code']] ?: $names['en'];
            $updatePhrases[] = [
                'fields' => ['Value' => $phrase],
                'where' => [
                    'Code' => $language['Code'],
                    'Key' => 'title_openAI',
                    'Modified' => '0'
                ]
            ];
        }

        if (method_exists($rlLang, 'updatePhrases')) {
            $rlLang->updatePhrases($updatePhrases);
        } else {
            $rlDb->update($updatePhrases, 'lang_keys');
        }
    }
}
