<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLAUTOREGPREVENT.CLASS.PHP
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

use Flynax\Abstracts\AbstractPlugin;
use Flynax\Interfaces\PluginInterface;
use Flynax\Utils\Util;
use Flynax\Utils\Valid;

/**
 * Class rlAutoRegPrevent
 */
class rlAutoRegPrevent extends AbstractPlugin implements PluginInterface
{
    /**
     * @var array
     */
    protected $parseFields = ['type', 'appears'];

    /**
     * Install the plugin
     *
     * @since 2.1.2
     */
    public function install()
    {
        $GLOBALS['rlDb']->createTable('reg_prevent', "
            `ID` int(11) NOT NULL AUTO_INCREMENT,
            `Username` varchar(32) NOT NULL,
            `Mail` varchar(50) NOT NULL,
            `IP` varchar(15) NOT NULL,
            `Reason` varchar(30) NOT NULL,
            `Date` datetime NOT NULL,
            `Status` enum('block','unblock') NOT NULL DEFAULT 'block',
            PRIMARY KEY (`ID`),
            INDEX (`Username`),
            INDEX (`Mail`),
            INDEX (`IP`)
        ");
    }

    /**
     * Uninstall the plugin
     *
     * @since 2.1.2
     */
    public function uninstall()
    {
        $GLOBALS['rlDb']->dropTable('reg_prevent');
    }

    /*** Updates ***/

    /**
     * Update process of the plugin (copy from core)
     *
     * @since 2.1.2
     *
     * @todo - Remove this method when compatibility will be >= 4.6.2
     *
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
     * @version 2.1.0
     */
    public function update210()
    {
        $GLOBALS['rlDb']->query("
            ALTER TABLE `{db_prefix}reg_prevent` 
            CHANGE `Reason` `Reason` VARCHAR(30) NOT NULL
        ");
    }

    /**
     * @version 2.1.2
     */
    public function update212()
    {
        $GLOBALS['rlDb']->query("
            DELETE FROM `{db_prefix}hooks` 
            WHERE `Name` = 'beforeRegister' AND `Plugin` = 'autoRegPrevent'
        ");
    }

    /**
     * @version 2.1.3
     */
    public function update213()
    {
        $GLOBALS['rlDb']->query(
            "ALTER TABLE `{db_prefix}reg_prevent`
             CHANGE `ID` `ID` INT(11) NOT NULL AUTO_INCREMENT"
        );

        $GLOBALS['rlDb']->query(
            "ALTER TABLE `{db_prefix}reg_prevent`
             ADD INDEX (`Username`),
             ADD INDEX (`Mail`),
             ADD INDEX (`IP`)"
        );

        if (in_array('ru', array_keys($GLOBALS['languages']))) {
            $russianTranslation = json_decode(file_get_contents(RL_PLUGINS . 'autoRegPrevent/i18n/ru.json'), true);
            foreach ($russianTranslation as $phraseKey => $phraseValue) {
                if (!$GLOBALS['rlDb']->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $newPhrase = $GLOBALS['rlDb']->fetch(
                        ['Module', 'Key', 'Plugin'],
                        ['Code' => $GLOBALS['config']['lang'], 'Key' => $phraseKey, 'Plugin' => 'comment'],
                        null, 1, 'lang_keys', 'row'
                    );
                    $newPhrase['Code']  = 'ru';
                    $newPhrase['Value'] = $phraseValue;

                    $GLOBALS['rlDb']->insertOne($newPhrase, 'lang_keys');
                } else {
                    $GLOBALS['rlDb']->updateOne([
                        'fields' => ['Value' => $phraseValue],
                        'where'  => ['Key'   => $phraseKey, 'Code' => 'ru'],
                    ], 'lang_keys');
                }
            }
        }
    }

    /*** Hooks ***/

    /**
     * @hook  phpAjaxValidateProfile
     * @since 2.1.2
     */
    public function hookPhpAjaxValidateProfile()
    {
        $this->checkRegistrationForm($GLOBALS['profile_data'], function ($error) {
            $GLOBALS['error_fields'] .= 'profile[mail],';
            $GLOBALS['errors'][] = $error;
        });
    }

    /**
     * @hook  phpAjaxEmailExist
     * @since 2.1.2
     *
     * @param $email
     * @param $message
     * @param $callback
     */
    public function hookPhpAjaxEmailExist($email, &$message, &$callback)
    {
        $this->checkRegistrationForm(['email' => $email], function ($error) use (&$message, &$callback) {
            $message = $error;
            $callback = true;
        });
    }

    /**
     * @hook  phpAddListingQuickRegistrationValidate
     * @since 2.1.2
     *
     * @param $register_data
     * @param $errors
     * @param $error_fields
     */
    public function hookPhpAddListingQuickRegistrationValidate($register_data, &$errors, &$error_fields)
    {
        $this->checkRegistrationForm($register_data, function ($error) use (&$errors, &$error_fields) {
            $errors[] = $error;
            $error_fields .= 'register[email],';
        });
    }

    /**
     * @hook apAjaxRequest
     * @since 2.1.2
     *
     * @param $out
     * @param $item
     */
    public function hookApAjaxRequest(&$out, $item)
    {
        switch ($item) {
            case 'autoRegPrevent_addToSpamList':
                $out = $this->addToSpamList($_REQUEST['username'], $_REQUEST['email'], $_REQUEST['ip']);
                break;

            case 'autoRegPrevent_deleteEntry':
                $out = $this->deleteEntry((int) $_REQUEST['id']);
                break;
        }
    }

    /*** Helpers ***/

    /**
     * Check registration form data to avoid spam
     *
     * @since 2.1.2
     *
     * @param array    $form
     * @param callable $failureCallback
     */
    public function checkRegistrationForm($form, $failureCallback)
    {
        if (!is_callable($failureCallback)) {
            return;
        }

        if (false === $this->check($form)) {
            $link = $this->buildLink('contact_us');
            $message = preg_replace('/\[(.*)\]/', $link, $GLOBALS['lang']['autoRegPrevent_detected']);

            $failureCallback($message);
        }
    }

    /*** Common ***/

    /**
     * Add a new entry to local spam list
     *
     * @since 2.1.2
     *
     * @param string $username
     * @param string $email
     * @param string $ip
     *
     * @return array
     */
    public function addToSpamList($username = '', $email = '', $ip = '')
    {
        global $lang, $rlDb;

        if ($username === '' && $email === '' && $ip === '') {
            return [
                'status'  => 'ERROR',
                'message' => $lang['autoRegPrevent_fillOutNotice'],
            ];
        }

        $insert = [
            'Username' => $username,
            'Mail'     => $email,
            'IP'       => $ip,
            'Reason'   => $lang['autoRegPrevent_adminAdded'],
            'Date'     => 'NOW()',
            'Status'   => 'block',
        ];
        $rlDb->insertOne($insert, 'reg_prevent');

        return [
            'status'  => 'OK',
            'message' => $lang['autoRegPrevent_adminAdded'],
        ];
    }

    /**
     * Delete an entry from the local spam list
     *
     * @since 2.1.2
     *
     * @param int $id
     *
     * @return array
     */
    public function deleteEntry($id)
    {
        $GLOBALS['rlDb']->delete(['ID' => $id], 'reg_prevent');

        return [
            'status'  => 'OK',
            'message' => $GLOBALS['lang']['item_deleted'],
        ];
    }

    /**
     * Convert XML to assoc Array
     *
     * @param string $xml
     *
     * @return array|bool
     */
    private function xml2array($xml)
    {
        if (!is_string($xml) || $xml == '') {
            return false;
        }

        $parser = xml_parser_create();
        xml_parse_into_struct($parser, $xml, $entries);
        xml_parser_free($parser);

        $iteration = $index = 0;
        $out = [];

        foreach ($entries as $entry) {
            $tag = strtolower($entry['tag']);

            if (in_array($tag, $this->parseFields)) {
                $out[$index][$tag] = $entry['value'];
                $iteration++;

                if ($iteration % count($this->parseFields) === 0) {
                    $index++;
                }
            }
        }

        return $out ?: false;
    }

    /**
     * Save record to local database
     *
     * @param array $result
     * @param array $form
     *
     * @return bool
     */
    public function saveToBase($result, $form)
    {
        global $lang, $rlDb;

        $reason = [];
        foreach ($result as $key => $value) {
            if ($value['appears'] == 'yes') {
                $reason[] = $value['type'];
            }
        }

        if (empty($reason)) {
            return false;
        }

        $insert = [
            'Username' => $form['username'] ?: $lang['not_available'],
            'Mail'     => $form['email'] ?: $lang['not_available'],
            'IP'       => Util::getClientIP(),
            'Reason'   => implode(',', $reason),
            'Date'     => 'NOW()',
            'Status'   => 'block',
        ];
        $result = $rlDb->insertOne($insert, 'reg_prevent');

        return $result;
    }

    /**
     * Check spamming users on local database
     *
     * @param string $where
     *
     * @return string|bool
     */
    public function checkBase($where)
    {
        $result = $GLOBALS['rlDb']->getOne('Status', $where, 'reg_prevent');

        return $result ?: false;
    }

    /**
     * Check input form in local or global SPAM database
     *
     * @param array $formData
     *
     * @return bool
     */
    public function check($formData)
    {
        global $config;

        $base_url = "http://www.stopforumspam.com/api?";
        $query_data = [];
        $sql_where = '';

        if ($config['autoRegPrevent_check_username'] && !empty($formData['username'])) {
            $sql_where .= sprintf("`Username` = '%s' OR ", Valid::escape($formData['username']));
            $query_data['username'] = $formData['username'];
        }

        if ($config['autoRegPrevent_check_email']
            && (!empty($formData['email']) || !empty($formData['mail']))
        ) {
            $email = $formData['email'] ?: $formData['mail'];
            $sql_where .= sprintf("`Mail` = '%s' OR ", Valid::escape($email));
            $query_data['email'] = $email;
        }

        if ($config['autoRegPrevent_check_ip']) {
            $client_ip = Util::getClientIP();
            $sql_where .= sprintf("`IP` = '%s'", $client_ip);
            $query_data['ip'] = $client_ip;
        }

        $query_string = $base_url . http_build_query($query_data);
        $sql_where = rtrim($sql_where, 'OR ');

        if (false === $db_status = $this->checkBase($sql_where)) {
            $xml = Util::getContent($query_string);

            if (false !== $result = $this->xml2array($xml)) {
                if (true === $this->saveToBase($result, $formData)) {
                    return false;
                }
            }
        } else {
            return $db_status === 'unblock';
        }

        return true;
    }

    /**
     * Build HTML link to system page
     *
     * @var string $key - Page key
     *
     * @return string
     */
    public function buildLink($key)
    {
        $url = $GLOBALS['reefless']->getPageUrl($key);
        $link = sprintf('<a class="navigator" href="%s" title="$1">$1</a>', $url);

        return $link;
    }

    /*** Deprecated ***/

    /**
     * Add new spamming user to local database
     *
     * @deprecated 2.1.2
     * @see rlAutoRegPrevent::hookApAjaxRequest()
     *
     * @param string $username
     * @param string $email
     * @param string $ip
     */
    public function ajaxAddSpamers($username = '', $email = '', $ip = '')
    {}
}
