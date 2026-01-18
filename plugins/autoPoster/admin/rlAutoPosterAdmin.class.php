<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLAUTOPOSTERADMIN.CLASS.PHP
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

namespace Autoposter\Admin;

use Autoposter\AutoPosterModules;
use Autoposter\AutoPosterContainer;

class rlAutoPosterAdmin
{
    /**
     * @var object - rlDb class
     */
    protected $rlDb;

    /**
     * @var object - rlSmarty
     */
    protected $rlSmarty;

    /**
     * @var object - rlActions
     */
    protected $rlActions;

    /**
     * @var array - Admin part options
     */
    protected $options;

    /**
     * @var object - AutoPosterModules class
     */
    protected $autoPostingModules;

    /**
     * @var object - reefless class
     */
    protected $reefless;

    /**
     * @var object - rlBuilder class instance
     */
    protected $builderClass;

    /**
     * @var object - rlLang class instance
     */
    protected $rlLang;

    /**
     * @var \rlCategories
     */
    protected $rlCategories;

    /**
     * rlAutoPosterAdmin constructor
     */
    public function __construct()
    {
        $this->options['path']['view'] = RL_PLUGINS . 'autoPoster' . RL_DS . 'admin' . RL_DS . 'view';
        $this->rlDb = AutoPosterContainer::getObject('rlDb');
        $this->rlActions = AutoPosterContainer::getObject('rlActions');
        $this->reefless = AutoPosterContainer::getObject('reefless');
        $this->rlSmarty = AutoPosterContainer::getObject('rlSmarty');

        $this->rlLang = AutoPosterContainer::getObject('rlLang');
        $this->builderClass = AutoPosterContainer::getObject('rlBuilder');
        $this->autoPostingModules = new AutoPosterModules();

        if ($this->rlSmarty) {
            $this->rlSmarty->assign('admin_options', $this->options);
        }
    }

    /**
     * Loading admin panel view file
     *
     * @param string $name - View name. Use just name of the tpl file.
     */
    public function loadView($name)
    {
        $file = $this->options['path']['view'] . RL_DS . $name . '.tpl';
        $this->rlSmarty->display($file);
    }

    /**
     * Update system config file
     *
     * @param  array      $data - Needed configurations array
     * @return array|bool       - Saving result.
     */
    public function updateSettings($data)
    {
        if ($errors = $this->validate($data)) {
            return $errors;
        }

        foreach ($data as $key => $value) {
            $update = array(
                'fields' => array(
                    'Default' => trim($value),
                ),
                'where' => array(
                    'Key' => $key,
                ),
            );

            $this->rlActions->updateOne($update, 'config');
        }

        return true;
    }

    /**
     * Validate configurations of the module
     *
     * @param  array $data   - Configuration of the Module
     * @return array $errors - Validation errors
     */
    public function validate($data)
    {
        $lang = AutoPosterContainer::getConfig('lang');
        $active_module = AutoPosterContainer::getConfig('active_module');
        $module_settings = $this->autoPostingModules->getSettingsByKey($active_module);

        $errors = array();
        foreach ($module_settings as $cKey => $cValue) {
            $config = $cValue['Key'];
            $rule = $cValue['validate'];
            $value = trim($data[$config]);

            if ($value && $value < 0) {
              $errors[] = str_replace('{field}', sprintf("<b>%s</b>", $cValue['name']), $lang['ap_cant_be_negative']);
            }
            if ($rule == 'required' && !$value) {
                $errors[] = str_replace('{field}', sprintf("<b>%s</b>", $cValue['name']), $lang['notice_field_empty']);
            } elseif(isset($cValue['error'])) {
                $errors[] = $cValue['error'];
            }
        }

        //handle onSumbit of all settings is they are not empty
        if (!$errors) {
            $modules = AutoPosterContainer::getConfig('configs');
            $on_submit = $modules['modules'][$active_module]['onSubmit'];
            $provider = (new \Autoposter\ProviderController($active_module))->getProvider();
            if ($on_submit && method_exists($provider, $on_submit)) {
                $errors = call_user_func(array($provider, $on_submit), $data);
            }
        }

        return $errors;
    }

    /**
     * Redirect user with message
     *
     * @param string $to      - Redirect to URL
     * @param string $message - Message body
     * @param string $type    - Message type: {'notice', 'alerts', 'errors', 'infos'}
     */
    function redirectWithMessage($to, $message, $type = 'notice')
    {
        $rlNotice = AutoPosterContainer::getObject('rlNotice');
        $rlNotice->saveNotice($message, $type);
        $this->reefless->redirect($to);
        exit;
    }

    /**
     * Get available Listing fields for the module
     *
     * @param   string $module - Module name
     * @return  array  $fields - Available fields
     */
    public function getFieldsForMessage($module)
    {
        $fields = $this->getDuplicateFields();

        $fields[] = 88;
        $add_cond = "AND(`ID` = '" . implode("' OR `ID` = '", $fields) . "') ";

        $fields = $this->rlDb->fetch(
            array('ID', 'Key', 'Type', 'Status'),
            null,
            "WHERE `Status` <> 'trash' {$add_cond}",
            null,
            'listing_fields'
        );
        $fields = $this->rlLang->replaceLangKeys($fields, 'listing_fields', array('name'), RL_LANG_CODE, 'admin');

        // add system fields
        $system_fields = AutoPosterContainer::getConfig('configs');
        $system_fields = $system_fields['message_system_field'];
        $fields[] = $system_fields;

        // filter array based on the saved fields
        $saved_fields = $this->filterAvailableAndSavedFields($fields, $module);
        $result['available'] = $fields;
        $result['saved'] = $saved_fields;

        return $result;
    }

    /**
     * Get non-unique listing fields
     *
     * @return array $fields - Fields array
     */
    public function getDuplicateFields()
    {
        $cat_fields = array();
        $fields = array();

        $sql = "SELECT `Category_ID` FROM `" . RL_DBPREFIX . "listing_relations` GROUP BY `Category_ID`";
        $cat_ids = $this->rlDb->getAll($sql);
        foreach ($cat_ids as $cat_id) {
            if ($tmpFields = $this->builderClass->getAvailableFields($cat_id['Category_ID'])) {
                $cat_fields[$cat_id['Category_ID']] = $tmpFields;
            }
        }
        $cat_fields = array_reduce($cat_fields, 'array_merge', array());
        $non_unique = $this->arrayGetNonUnique($cat_fields);
        $non_unique_count = array_count_values($non_unique);

        $cat_ids_count = count($cat_ids);
        foreach ($non_unique_count as $key => $value) {
            if ($value == $cat_ids_count) {
                $fields[] = $key;
            }
        }

        return $fields;
    }

    /**
     * Save message builder form thought Ajax
     *
     * @param  array  $data   - Saving data
     * @param  string $module - Module name. All data will be saved into this module
     * @return array  $out    - AJAX request
     */
    public function  ajaxSaveForm($data, $module)
    {
        $this->ajaxLoadClasses();
        $lang = AutoPosterContainer::getConfig('lang');
        $message_fields = $message_pattern = $message_body = '';
        $answer = $pattern = $fields = array();

        foreach ($data as $field) {

            if ($field['type'] == 'system' && $field['key'] == 'customMessage') {
                $message_body = $field['value'];
            }
            $value = '{' . $field['key'] . '}';
            $pattern[] = $value;
            $value = $field['id'];
            $fields[] = $value;
        }
        $message_pattern = implode(',', $pattern);
        $message_fields = implode(',', $fields);


        $update = array(
            'fields' => array(
                'Message_pattern' => $message_pattern,
                'Message_fields' => $message_fields,
                'Message_body' => $message_body,
            ),
            'where' => array(
                'Key' => $module,
            ),
        );
        $result = $this->rlActions->updateOne($update, 'autoposting_modules');

        if (!$result) {
            $answer['status'] = 'ERROR';
            $answer['message'] = $lang['ap_module_saving_error'];
            return $answer;
        }

        $answer['status'] = 'OK';
        $answer['message'] = $lang['ap_module_saved_fine'];

        return $answer;
    }

    /**
     * Return all non unique elements of the array
     *
     * @param  array $raw_array - Filtering array
     * @return array $dupes     - Duplicates array
     */
    public function arrayGetNonUnique($raw_array)
    {
        $dupes = array();
        natcasesort($raw_array);
        reset($raw_array);

        $old_key = null;
        $old_value = null;
        foreach ($raw_array as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (strcasecmp($old_value, $value) === 0) {
                $dupes[$old_key] = $old_value;
                $dupes[$key] = $value;
            }
            $old_value = $value;
            $old_key = $key;
        }

        return $dupes;
    }

    /**
     * Run this method in all ajax handlers
     */
    public function ajaxLoadClasses()
    {
        $this->rlActions = $GLOBALS['rlActions'];
    }

    /**
     * Filter available fields. Cut all saved fields from the array and return them
     *
     * @param  array  $fields       - Available Listings fields array
     * @param  string $module       - Module key
     * @return array  $saved_fields - Saved fields array
     */
    public function filterAvailableAndSavedFields(&$fields, $module)
    {
        $saved_fields = array();
        $available_fields = $fields;
        $in_db = $this->rlDb->getOne('Message_fields', "`Key` = '{$module}'", 'autoposting_modules');

        if (!$in_db) {
            return $saved_fields;
        }
        $in_db = explode(',', $in_db);

        foreach ($available_fields as $key => $available_field) {
            if (in_array($available_field['ID'], $in_db)) {
                $saved_fields[] = $available_field;
                unset($fields[$key]);
            }
        }

        return $saved_fields;
    }

    /**
     * Get errors related to the plugin from the main errors log file
     *
     * @since 1.6.0 - Moved from "rlAutoPoster" class
     *
     * @return array - Error log content
     */
    public function getErrorLog()
    {
        $log = [];
        if (!file_exists($GLOBALS['rlDebug']->logFilePath)) {
            return $log;
        }
        $data_chunk_length = 16384;
        $current_month = '';
        $now = time();
        $fp = fopen($GLOBALS['rlDebug']->logFilePath, 'r');

        while (!feof($fp)) {
            $line = fgets($fp, $data_chunk_length);

            if (strpos($line, 'autoPoster -')) {
                preg_match('/^([0-9]+\s([a-zA-Z]{3})\s[0-9:]+)\s\| [0-9]+ repeats \| (.*)\son line\#.*/', $line, $matches);

                if ($matches[1] && $matches[3]) {
                    // Ignore previous year logs
                    if ($current_month && $matches[2] == 'Jan') {
                        $log = [];
                    }

                    $date = preg_replace('/^([0-9]+\s[a-zA-Z]{3})/', '$1 ' . date('Y'), $matches[1]);

                    // Ignore viewed or logs in future
                    if ($GLOBALS['config']['ap_log_reset_date']
                        &&
                        (strtotime($date) <= $GLOBALS['config']['ap_log_reset_date']
                            || strtotime($date) >= $now)
                    ) {
                        continue;
                    }

                    $current_month = $matches[2];

                    $log[] = array(
                        'message' => str_replace('DEBUG: autoPoster - ', '', $matches[3]),
                        'date' => $matches[1],
                    );
                }
            }
        }

        fclose($fp);

        return $log;
    }
}
