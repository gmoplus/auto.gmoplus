<?php


/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLMASSMAILERNEWSLETTER.CLASS.PHP
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

use Flynax\Utils\Valid;

class rlMassmailerNewsletter extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    public function install()
    {
        global $rlDb;

        $rlDb->createTable(
            'subscribers',
            "`ID` int(11) NOT NULL auto_increment,
            `Name` varchar(255) CHARACTER SET utf8 NOT NULL default '',
            `Mail` varchar(255) NOT NULL default '',
            `Date` date NOT NULL default '0000-00-00',
            `Status` enum('active','approval', 'incomplete') NOT NULL default 'active',
            `Confirm_code` varchar(55) NOT NULL default '',
            PRIMARY KEY  (`ID`),
            KEY `Status` (`Status`)"
        );

        $rlDb->createTable(
            'massmailer',
            "`ID` int(11) NOT NULL auto_increment,
            `Key` varchar(255) NOT NULL default '',
            `Subject` mediumtext CHARACTER SET utf8 NOT NULL,
            `Body` mediumtext CHARACTER SET utf8 NOT NULL,
            `Lang` varchar(3) NOT NULL,
            `Recipients_newsletter` enum('1','0') NOT NULL default '0',
            `Recipients_accounts` varchar(255) NOT NULL default '',
            `Recipients_contact_us` enum('1','0') NOT NULL default '0',
            `Date` date NOT NULL default '0000-00-00',
            `Status` enum('active','approval') NOT NULL default 'active',
            PRIMARY KEY  (`ID`),
            KEY `Status` (`Status`)"
        );

        $rlDb->addColumnToTable('Subscribe', "ENUM('0', '1') NOT NULL DEFAULT '1' AFTER `Mail_tmp`", 'accounts');
        $rlDb->query("ALTER TABLE `{db_prefix}accounts` ADD INDEX (`Subscribe`)");

        $rlDb->addColumnToTable('Subscribe', "ENUM('0', '1') NOT NULL DEFAULT '1' AFTER `Email`", 'contacts');
        $rlDb->query("ALTER TABLE `{db_prefix}contacts` ADD INDEX (`Subscribe`)");

        $sql = "SELECT GROUP_CONCAT(`ID`) AS `IDs` FROM `{db_prefix}pages` ";
        $sql .= "WHERE `Key` IN('home', 'about_us', 'contact_us', 'news') LIMIT 4";
        $page_ids = $rlDb->getRow($sql, 'IDs');

        $rlDb->query("
            UPDATE `{db_prefix}blocks`
            SET `Sticky` = '0', `Page_ID` = '{$page_ids}'
            WHERE `Key` = 'massmailer_newsletter_block' LIMIT 1
        ");
        $this->fixScopes();
    }

    public function uninstall()
    {
        global $rlDb;

        $rlDb->dropTables(array('massmailer', 'subscribers'));
        $rlDb->dropColumnFromTable('Subscribe', 'accounts');
        $rlDb->dropColumnFromTable('Subscribe', 'contacts');
    }

    /**
     *
     * add aStyle.css in admin header
     *
     **/
    public function hookApTplHeader()
    {
        global $controller;

        if ($controller == 'massmailer_newsletter') {
            echo '<link href="' . RL_PLUGINS_URL
                . 'massmailer_newsletter/static/aStyle.css" type="text/css" rel="stylesheet" />';
        }
    }

    public function hookPhpRegistrationBeforeInsert(&$data, &$profile)
    {
        $data['Subscribe'] = (int) $profile['mn_subscribe'];
    }

    public function hookProfileEditProfileDone()
    {
        global $rlDb, $account_info;

        $subscribe = (int) $_POST['profile']['mn_subscribe'];

        $rlDb->query("
            UPDATE `{db_prefix}accounts` SET `Subscribe` = '{$subscribe}'
            WHERE `ID` = {$account_info['ID']} LIMIT 1
        ");
    }

    public function hookProfileController()
    {
        global $profile_info;

        if (!$_POST['fromPost_profile']) {
            $_POST['profile']['mn_subscribe'] = $profile_info['Subscribe'];
        }
    }

    public function hookTplRegistrationCheckbox()
    {
        global $tpl_settings, $rlSmarty;

        $rlSmarty->display(RL_PLUGINS . 'massmailer_newsletter' . RL_DS . 'checkbox.tpl');
    }

    public function hookTplProfileCheckbox()
    {
        global $tpl_settings, $rlSmarty;

        $rlSmarty->display(RL_PLUGINS . 'massmailer_newsletter' . RL_DS . 'checkbox.tpl');
    }

    /**
     * @deprecated 3.2.2
     */
    public function hookStaticDataRegister()
    {}

    /**
     * save massmailer
     *
     * @package xAjax
     *
     * @param string $id - massmail ID
     * @param string $from - send massmail from email
     * @param string $status - massmail status
     * @param string $subject - massmail subject
     * @param string $body - massmail body
     * @param string $res_newsletter - recipients form newsletter
     * @param string $res_accounts - recipients form accounts
     * @param string $res_contact_us - recipients from contact us
     *
     **/
    public function ajaxMassmailerSave(
        $id = false,
        $status = false,
        $subject = false,
        $body = false,
        $res_newsletter = false,
        $res_accounts = false,
        $res_contact_us = false
    ) {
        global $_response, $rlDb, $lang;

        // check admin session expire
        if ($GLOBALS['reefless']->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING'])
                ? '?session_expired'
                : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);

            return $_response;
        }

        $update_data = array(
            'fields' => array(
                'Status' => $status,
                'Date' => 'NOW()',
                'Subject' => trim($subject),
                'Body' => trim($body),
                'Recipients_newsletter' => $res_newsletter,
                'Recipients_accounts' => $res_accounts,
                'Recipients_contact_us' => $res_contact_us,
            ),
            'where' => array('ID' => $id),
        );

        if ($rlDb->updateOne($update_data, 'massmailer')) {
            $_response->script("massmailer.send({$id}, 0);");
        } else {
            $GLOBALS['rlDebug']->logger("Unable to update massmailer item, updateOne() fail");
            $message = "printMessage('error', 'Unable to save massmailer entry, please contact Flynax Support.');";
            $_response->script($message);
        }

        $_response->script("$('input#confirm').val('{$lang['massmailer_newsletter_send_and_save']}')");

        return $_response;
    }
    /**
     * Getting count and the account type name
     *
     * @since 3.2.0
     *
     * @param array $langSelect - user lanf to massmailer
     * @param array $js - the flag is called with ajax
     *
     * @return  array  - array acounts types
     */
    public function getCountTypeByLang($langSelect, $js = false)
    {
        global $lang, $rlDb, $rlLang;

        /* get account types */
        $rlDb->setTable('account_types');
        $acTypes = $rlDb->fetch(array('Key'), array('Status' => 'active'), "AND `Key` <> 'visitor'");
        $acTypes = $rlLang->replaceLangKeys($acTypes, 'account_types', array('name'), RL_LANG_CODE, 'admin');
        $accounts_count = 0;
        foreach ($acTypes as $key => $val) {
            $sql = "SELECT COUNT(`ID`) AS 'count' FROM `{db_prefix}accounts`
            WHERE `Type` = '{$val['Key']}' AND `Status` = 'active' AND Subscribe = '1' ";
            if ($langSelect !== 'all') {
                $sql .= "AND `Lang` like '{$langSelect}'";
            }
            $ac_type_count = $rlDb->getRow($sql);
            $accounts_count = $accounts_count + $ac_type_count['count'];
            $acTypes[$key]['count'] = $ac_type_count['count'];
        }
        $acTypes['total'] = $accounts_count;
        if ($js) {
            $acTypes['langAcType'] = $lang['account_type'];
        }
        return $acTypes;
    }

    /**
     * @hook apAjaxRequest
     *
     * @since 3.2.0
     *
     * @param array  $out
     * @param string $item
     */
    public function hookApAjaxRequest(&$out, $item)
    {
        if (!$item) {
            return false;
        }

        switch ($item) {
            case 'getCountMassmailer':
                $lang = $_REQUEST['langMassmailer'];
                $countAcountType = $this->getCountTypeByLang($lang, true);
                $out = array(
                    'status'  => $countAcountType ? 'OK' : 'ERROR',
                    'data' => $countAcountType
                );
                break;
        }
    }
    /**
     * Get phase by key
     *
     * @since 3.2.0
     *
     * @param  string $key key phrase
     * @return string  phrase
     */
    public function getPhrase($key)
    {
        global  $rlLang;

        if (version_compare($GLOBALS['config']['rl_version'], '4.8.1', '<')) {
            return $GLOBALS['lang'][$key];
        } else {
            return $rlLang->getSystem($key);
        }
    }

    /**
     * @hook ajaxRequest
     *
     * ajax subscribe recipients for massmail
     * @since 3.0.0
     *
     * @param string $out - return request error or success
     * @param string $request_mode - detect subscribpion request
     * @param string $request_item - item for action
     * @param string $request_lang - user language
     */
    public function hookAjaxRequest(&$out, &$request_mode, &$request_item, &$request_lang)
    {
        global $_response, $pages, $config, $account_info, $tpl_settings, $rlLang, $reefless, $rlDb;

        if ($request_mode != 'newsletterSubscribe') {
            return;
        }

        if (version_compare($GLOBALS['config']['rl_version'], '4.8.1', '<')) {
            $GLOBALS['lang'] = $rlLang->getLangBySide('frontEnd', $request_lang);
        }

        $name = Valid::escape($_REQUEST['name']);
        $email = Valid::escape($_REQUEST['email']);

        if (empty($name)) {
            $errors[] = $this->getPhrase('massmailer_newsletter_empty_name');
        } elseif (strlen($name) < 3) {
            $errors[] = $this->getPhrase('massmailer_newsletter_name_is_to_short');
        }

        if (empty($email)) {
            $errors[] = $this->getPhrase('massmailer_newsletter_empty_email');
        } elseif (!$GLOBALS['rlValid']->isEmail($email)) {
            $errors[] = $this->getPhrase('massmailer_newsletter_incorrect_email');
        }

        if (empty($errors)) {
            $exist = $rlDb->fetch(
                array('ID', 'Mail', 'Name', 'Date', 'Confirm_code'),
                array('Mail' => $email),
                null,
                1,
                'subscribers',
                'row'
            );

            if (!empty($exist)) {
                $msg = $this->getPhrase('massmailer_newsletter_subscribe_email_exist');

                $out = array(
                    'status' => 'ERROR',
                    'data' => array('message' => $msg),
                );
                return;
            } else {
                $subscribe_code = md5(mt_rand());

                if ($config['massmailer_newsletter_confirm_subscription']) {
                    $rlDb->query("
                        INSERT INTO `{db_prefix}subscribers`
                            (`Name`, `Mail`, `Date`, `Status`, `Confirm_code`)
                        VALUES ('{$name}', '{$email}', NOW(), 'incomplete', '{$subscribe_code}')
                    ");
                    $user_id = $rlDb->insertID();

                    $reefless->loadClass('Mail');

                    // create confirmation subscribe link
                    $subscribe_link = $reefless->getPageUrl(
                        'massmailer_newsletter_newsletter',
                        array('nvar_1' => 'subscribe')
                    );
                    $subscribe_link .= ($GLOBALS['config']['mod_rewrite'] ? "?" : "&") . "key=" . $subscribe_code;
                    $subscribe_link = '<a href="' . $subscribe_link . '">' . $subscribe_link . '</a>';

                    $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('massmailer_subscribe');
                    $mail_tpl['body'] = str_replace(
                        array('{name}', '{link}'),
                        array($name, $subscribe_link),
                        $mail_tpl['body']
                    );

                    if ($GLOBALS['rlMail']->send($mail_tpl, $email)) {
                        $msg = $this->getPhrase('massmailer_newsletter_subscription_incompleted');
                        $msg = str_replace('{email}', $email, $msg);

                        $out = array(
                            'status' => 'OK',
                            'data' => array('content' => $msg),
                        );
                        return;
                    } else {
                        $msg = $this->getPhrase('massmailer_newsletter_cannot_sendemail_to_subscriber');
                        $msg = str_replace(
                            array('{email}', '{email_admin}'),
                            array($email, $config['site_main_email']),
                            $msg
                        );

                        $out = array(
                            'status' => 'WARNING',
                            'data' => array('content' => $msg),
                        );
                        return;
                    }
                } else {
                    $rlDb->query("
                        INSERT INTO `{db_prefix}subscribers`
                            (`Name`, `Mail`, `Date`, `Confirm_code`)
                        VALUES ('{$name}', '{$email}', NOW(), '{$subscribe_code}')
                    ");

                    $msg = $this->getPhrase('massmailer_newsletter_subscription_completed');
                    $msg = str_replace('{sitename}', $lang['pages+title+home'], $msg);

                    $out = array(
                        'status' => 'OK',
                        'data' => array('content' => $msg),
                    );

                    return;
                }
            }
        } else {
            $error_content = '<ul>';
            foreach ($errors as $error) {
                $error_content .= "<li>" . $error . "</li>";
            }
            $error_content .= '</ul>';

            $out = array(
                'status' => 'ERROR',
                'data' => array('message' => $error_content),
            );

            return;
        }
    }

    /**
     * @hook  sitemapExcludedPages
     * @since 3.1.0
     */
    public function hookSitemapExcludedPages(&$urls)
    {
        $urls = array_merge($urls, array('massmailer_newsletter_newsletter'));
    }

    /**
     * @hook  phpQuickRegistrationBeforeInsert
     * @since 3.1.0
     *
     * @param array $account - Data of new account before registration
     */
    public function hookPhpQuickRegistrationBeforeInsert(&$account = array())
    {
        if (!$account) {
            return false;
        }

        $account['Subscribe'] = $GLOBALS['config']['mn_default_value'];
    }

    /**
     * delete Massmailer in admin panel grid
     *
     * @package xAjax
     *
     * @param int $mass_id - massmailer id
     *
     **/
    public function ajaxDeleteMassmailerNewsletter($mass_id = false)
    {
        global $_response, $lang, $rlDb;

        $mass_id = (int) $mass_id;

        // check admin session expire
        if ($GLOBALS['reefless']->checkSessionExpire() === false) {
            $_response->redirect(RL_URL_HOME . ADMIN . '/index.php?action=session_expired');
            return $_response;
        }

        $items = $rlDb->getOne('Key', "`ID` = '{$mass_id}'", 'massmailer');

        $rlDb->query("DELETE FROM `{db_prefix}massmailer` WHERE `ID` = {$mass_id} LIMIT 1");
        $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'massmailer+name+{$items['Key']}'");
        $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'massmailer+desc+{$items['Key']}'");

        $_response->script("
            massmailerGrid.reload();
            printMessage('notice', '{$lang['item_deleted']}');
        ");

        return $_response;
    }

    /**
     * delete Subscriber in admin panel grid
     *
     * @package xAjax
     *
     * @param $id - subscriber id and type (5, 'subscriber')
     *
     **/
    public function ajaxDeleteSubscrider($id = false)
    {
        global $_response, $lang, $rlDb;

        $exploaded = explode(",", $id);
        $id = (int) $exploaded[0];
        $type = $exploaded[1];

        if (!$id) {
            return $_response;
        }

        // check admin session expire
        if ($GLOBALS['reefless']->checkSessionExpire() === false) {
            $_response->redirect(RL_URL_HOME . ADMIN . '/index.php?action=session_expired');
            return $_response;
        }
        // delete account from subscribe
        switch ($type) {
            case 'subscribers':
                $rlDb->query("DELETE FROM `{db_prefix}subscribers` WHERE `ID` = {$id} LIMIT 1");
                break;
            case 'accounts':
                $rlDb->query("UPDATE `{db_prefix}accounts` SET `Subscribe` = '0' WHERE `ID` = {$id} LIMIT 1");
                break;
            case 'contacts':
                $rlDb->query("UPDATE `{db_prefix}contacts` SET `Subscribe` = '0' WHERE `ID` = {$id} LIMIT 1");
                break;
        }

        $_response->script("newsletterGrid.reload();
            printMessage('notice', '{$lang['massmailer_newsletter_user_unsubsrcibed']}');");

        return $_response;
    }

    /**
     * add styles for newsletter
     *
     * @hook tplFooter
     **/
    public function hookTplFooter()
    {
        global $page_info;

        if (stristr($GLOBALS['tpl_settings']['name'], '_nova') && $page_info['Key'] !== 'search_on_map') {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'massmailer_newsletter/footer_block_mailler.tpl');
        }
    }

    /**
     * detect duplicate emails and show in admin panel grid
     *
     * @param array $array - array with emails
     *
     **/
    public function returndup($array)
    {
        $duplicates = array();
        foreach (array_count_values($array) as $val => $c) {
            if ($c > 1) {
                $duplicates[] = $val;
            }
        }
        return $duplicates;
    }

    public function arrayValueRecursive($key, array $arr)
    {
        $val = array();
        array_walk_recursive($arr, function ($v, $k) use ($key, &$val) {
            if ($k == $key) {
                array_push($val, $v);
            }
        });
        return count($val) > 1 ? $val : array_pop($val);
    }

    /**
     * Update process of the plugin (copy from core)
     * @todo - Remove this method when compatibility will be >= 4.6.2
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
     * Update to 3.0.0 version
     */
    public function update300()
    {
        global $rlDb;

        $confirm_code_field = $rlDb->getRow("SHOW FIELDS FROM `{db_prefix}subscribers` WHERE `Field` = 'Confirm_code'");

        if (!is_array($confirm_code_field)) {
            $rlDb->query("ALTER TABLE `{db_prefix}subscribers` ADD `Confirm_code` VARCHAR( 55 ) NOT NULL");
        }

        $inc_status = $rlDb->getRow("SHOW FIELDS FROM `{db_prefix}subscribers` WHERE `Field` = 'Status' AND `Type` LIKE '%incomplete%'");

        if (!$inc_status) {
            $rlDb->query("ALTER TABLE `{db_prefix}subscribers` CHANGE `Status` `Status` ENUM('active','approval','incomplete') NOT NULL DEFAULT 'active';");
        }

        //remove old hooks
        $sql = "DELETE FROM `{db_prefix}hooks` WHERE (`Name` = 'registerSuccess' OR `Name` = 'specialBlock' OR `Name` = 'tplHeader') AND `Plugin` = 'massmailer_newsletter' LIMIT 3";
        $rlDb->query($sql);

        //remove old phrases
        $sql = "DELETE FROM `{db_prefix}lang_keys` WHERE (
                `Key` = 'massmailer_newsletter_unsubscribe_text' OR
                `Key` = 'massmailer_newsletter_send_and_save' OR
                `Key` = 'massmailer_newsletter_subscribe_email_not_exist' OR
                `Key` = 'email_templates+subject+massmailer_unsubscribe' OR
                `Key` = 'email_templates+body+massmailer_unsubscribe' OR
                `Key` = 'pages+name+massmailer_unsubscribe' OR
                `Key` = 'pages+title+massmailer_unsubscribe' OR
                `Key` = 'massmailer_newsletter_delete'
            ) AND `Plugin` = 'massmailer_newsletter'";
        $rlDb->query($sql);

        $rlDb->query("UPDATE `{db_prefix}lang_keys` SET `Value` = REPLACE(`Value`, '{sitename}', '[sitename]') WHERE `Key` = 'massmailer_newsletter_person_unsubscibed'");

        //remove old email tempalte
        $rlDb->query("DELETE FROM `{db_prefix}email_templates` WHERE `Key` = 'massmailer_unsubscribe' AND `Plugin` = 'massmailer_newsletter' LIMIT 1");

        $rlDb->query("DELETE FROM `{db_prefix}pages` WHERE `Key` = 'massmailer_newsletter_unsubscribe' AND `Plugin` = 'massmailer_newsletter' LIMIT 1");

        // remove old files
        unlink(RL_PLUGINS . 'massmailer_newsletter' . RL_DS . 'footer.tpl');
        unlink(RL_PLUGINS . 'massmailer_newsletter' . RL_DS . 'massmailer_newsletter.tpl');
        unlink(RL_PLUGINS . 'massmailer_newsletter' . RL_DS . 'unsubscribe.inc.php');

        $sql = "SELECT GROUP_CONCAT(`ID`) AS `IDs` FROM `{db_prefix}pages`
            WHERE `Key` IN('home', 'about_us', 'contact_us', 'news') LIMIT 4";
        $page_ids = $rlDb->getRow($sql, 'IDs');

        $update = "UPDATE `{db_prefix}blocks`
            SET `Sticky` = '0', `Page_ID` = '{$page_ids}'
            WHERE `Key` = 'massmailer_newsletter_block' LIMIT 1";
        $rlDb->query($update);
    }

    /**
     * Update to 3.1.0 version
     */
    public function update310()
    {
        $GLOBALS['rlDb']->query("
            DELETE FROM `{db_prefix}hooks`
            WHERE `Name` = 'tplFooter' AND `Plugin` = 'massmailer_newsletter' LIMIT 1
        ");
    }

    /**
     * Update to 3.2.0 version
     */
    public function update320()
    {
        global $rlDb ,$languages;

        @unlink(RL_PLUGINS . '/massmailer_newsletter/admin/massmailer_newsletter_save.php');
        $rlDb->addColumnToTable('Lang', "varchar(3) NOT NULL AFTER `Body`", 'massmailer');
        register_shutdown_function(function () {
            $this->fixScopes();
        });
        if (in_array('ru', array_keys($languages))) {
            $russianTranslation = json_decode(file_get_contents(RL_UPLOAD . 'massmailer_newsletter/i18n/ru.json'), true);
            foreach (['config+des+mn_default_value', 'blocks+name+massmailer_newsletter_block', 'email_templates+body+massmailer_massmail_example', 'email_templates+body+massmailer_subscribe', 'email_templates+subject+massmailer_subscribe' ] as $phraseKey) {
                $rlDb->updateOne([
                    'fields' => ['Value' => $russianTranslation[$phraseKey]],
                    'where'  => ['Key'   => $phraseKey, 'Code' => 'ru'],
                ], 'lang_keys');
            }
        }
    }

    public function update322()
    {
        $GLOBALS['rlDb']->query("
            DELETE FROM `{db_prefix}hooks`
            WHERE `Name` = 'staticDataRegister' AND `Plugin` = 'massmailer_newsletter' LIMIT 1
        ");

        unlink(RL_PLUGINS . 'massmailer_newsletter/static/style.css');
    }

    /*** DEPRECATED METHODS ***/

    /**
     * Fix phrase scopes for software version <= 4.8.0
     *
     * @since 3.2.0
     */
    public function fixScopes()
    {
        if (version_compare($GLOBALS['config']['rl_version'], '4.8.0', '>')) {
            $update = array(
                [
                    'fields' => array('Module' => 'frontEnd', 'js' => '1'),
                    'where' => array('Key' => 'massmailer_newsletter_no_response')
                ],
                [
                    'fields' => array('Module' => 'frontEnd', 'js' => '1'),
                    'where' => array('Key' => 'massmailer_newsletter_guest')
                ]
            );
            $GLOBALS['rlDb']->update($update, 'lang_keys');
            return;
        }
        $update = array(
            [
                'fields' => array('Module' => 'frontEnd'),
                'where' => array('Key' => 'massmailer_newsletter_cannot_sendemail_to_subscriber')
            ],
            [
                'fields' => array('Module' => 'frontEnd'),
                'where' => array('Key' => 'massmailer_newsletter_subscription_incompleted')
            ],
            [
                'fields' => array('Module' => 'frontEnd'),
                'where' => array('Key' => 'massmailer_newsletter_person_subscibed')
            ],
            [
                'fields' => array('Module' => 'common'),
                'where' => array('Key' => 'massmailer_newsletter_incorrect_request')
            ],
            [
                'fields' => array('Module' => 'frontEnd'),
                'where' => array('Key' => 'massmailer_newsletter_oops')
            ],
            [
                'fields' => array('Module' => 'frontEnd'),
                'where' => array('Key' => 'massmailer_newsletter_congratulations')
            ],
            [
                'fields' => array('Module' => 'frontEnd'),
                'where' => array('Key' => 'massmailer_newsletter_empty_name')
            ],
            [
                'fields' => array('Module' => 'frontEnd'),
                'where' => array('Key' => 'massmailer_newsletter_empty_email')
            ],
            [
                'fields' => array('Module' => 'frontEnd'),
                'where' => array('Key' => 'massmailer_newsletter_incorrect_email')
            ],
            [
                'fields' => array('Module' => 'frontEnd'),
                'where' => array('Key' => 'massmailer_newsletter_name_is_to_short')
            ],
            [
                'fields' => array('Module' => 'frontEnd'),
                'where' => array('Key' => 'massmailer_newsletter_subscribe_email_exist')
            ],
            [
                'fields' => array('Module' => 'frontEnd'),
                'where' => array('Key' => 'massmailer_newsletter_subscription_completed')
            ],
            [
                'fields' => array('Module' => 'frontEnd'),
                'where' => array('Key' => 'massmailer_newsletter_no_response')
            ],
            [
                'fields' => array('Module' => 'frontEnd'),
                'where' => array('Key' => 'massmailer_newsletter_title_for_unsubscribtion_doesnt_exist')
            ],
            [
                'fields' => array('Module' => 'frontEnd'),
                'where' => array('Key' => 'massmailer_newsletter_your_name')
            ],
            [
                'fields' => array('Module' => 'frontEnd'),
                'where' => array('Key' => 'massmailer_newsletter_incorrect_link')
            ],
        );
        $GLOBALS['rlDb']->update($update, 'lang_keys');
    }
}
