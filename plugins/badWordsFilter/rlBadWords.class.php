<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLBADWORDS.CLASS.PHP
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

use Flynax\Interfaces\PluginInterface;
use Flynax\Abstracts\AbstractPlugin;

class rlBadWords extends AbstractPlugin implements PluginInterface
{
    /**
     * @var string - Plugin table name
     */
    private $table;

    /**
     * @var int - Working language ID
     */
    private $lang_id;

    /**
     * @var array - Bad words lists
     * @since 1.2.0
     */
    private $badWordsList;

    /**
     * rlBadWords constructor.
     */
    public function __construct()
    {
        $this->table = RL_DBPREFIX . 'bad_words';
        $this->lang_id = 0;
    }

    /**
     * Checks system languages in the language packages folder
     */
    public function checkInstalledLanguages()
    {
        global $rlLang;

        $langugeList = $GLOBALS['languages'] ?: $rlLang->getLanguagesList();
        $delimiter = PHP_EOL;

        foreach ($langugeList as $item) {
            $path = RL_PLUGINS . "badWordsFilter/langpack/" . $item['Code'] . ".txt";

            $fp = @fopen($path, 'r');
            if ($fp) {
                $badwordsArray = explode($delimiter, fread($fp, filesize($path)));
                $badwordsArray = array_diff($badwordsArray, array(''));
                $this->importBadWords($badwordsArray, $item['Code']);
            }
        }
    }

    /**
     * Importing bad words
     *
     * @param array  $badwordsArray - Bad words
     * @param string $lang - Language of the bad words
     * @param string $delimiter - Delimiter between each bad word
     */
    public function importBadWords($badwordsArray, $lang, $delimiter = PHP_EOL)
    {
        global $rlValid;

        $badwordsArray = array_filter($badwordsArray);

        $sql = "INSERT IGNORE INTO `" . RL_DBPREFIX . "bad_words` (`Code`,`Value`,`Status`) VALUES ";

        foreach ($badwordsArray as $key => $item) {
            $item = $rlValid->xSql($item);
            $sql .= "('{$lang}','{$item}','active'),";
        }
        $sql = substr($sql, 0, -1);
        $sql .= ";";

        $GLOBALS['rlDb']->query($sql);
    }

    /**
     * Remove bad word from database
     *
     * @param  int $id - Removing bad word ID
     * @return array|bool - Prepared AJAX answer | False if wrong argument has been passed
     */
    public function ajaxDeleteBadWord($id = 0)
    {
        if (!$id) {
            $out['status'] = 'ERROR';

            return $out;
        }

        $out = array();
        if ($GLOBALS['rlDb']->query("DELETE FROM `{$this->table}` WHERE `ID` = {$id} LIMIT 1")) {
            $out['status'] = 'OK';
        } else {
            $out['status'] = 'ERROR';
        }

        return $out;
    }

    /**
     * Insert new Bad Word
     *
     * @param string $bad_word
     * @param string $status
     * @return array $out      - Prepared AJAX answer
     */
    public function ajaxAddBadWord($bad_word, $status)
    {
        global $lang, $rlDb;

        if (!$bad_word) {
            $out['status'] = 'ERROR';
            $out['message'] = $lang['bw_is_empty'];

            return $out;
        }

        $GLOBALS['reefless']->loadClass('Actions');
        $out = array();
        $lang_id = $this->lang_id ?: 0;

        $lang_code = $rlDb->getOne('Code', "`ID`={$lang_id}", "languages");
        $data = array(
            'Code' => $lang_code,
            'Value' => $bad_word,
            'Status' => $status,
        );

        $if_exist = $rlDb->getOne("ID", "`Value`='{$bad_word}'", "bad_words");
        if (!$if_exist) {
            $rlDb->insertOne($data, 'bad_words');
            $out['status'] = 'OK';
            $out['message'] = $lang['bw_sucess'];

            return $out;
        }

        $out['status'] = 'ERROR';
        $out['message'] = sprintf("<b>%s</b> %s", $bad_word, $lang['bw_exist']);
        return $out;
    }

    /**
     * @param \Flynax\Classes\AddListing $addListing - Add listing class instance
     * @param array                      $data - Form input data
     * @param array                      $errors - Form validation error
     * @param array                      $error_fields - Form validation errors field
     */
    public function hookAddListingFormDataChecking($addListing, $data, &$errors, &$error_fields)
    {
        $res = $this->checkForm($data, $errors, 'listing');

        if (null !== $errors) {
            $errors = array_merge($errors, $res['errors']);
            $error_fields .= $res['error_fields'];

            return;
        }

        $GLOBALS['errors'] = array_merge($GLOBALS['errors'], $res['errors']);
        $GLOBALS['error_fields'] .= $res['error_fields'];

        return;
    }

    /**
     * @since 1.1.0
     * @hook phpAjaxValidateProfile
     */
    public function hookPhpAjaxValidateProfile()
    {
        $data = $_POST['profile'];
        $errors = $GLOBALS['errors'];

        $res = $this->checkForm($data, $errors, 'account');

        $GLOBALS['errors'] = array_merge((array) $GLOBALS['errors'], (array) $res['errors']);
        $GLOBALS['error_fields'] .= $res['error_fields'];

        return;
    }

    /**
     * @hook phpCommentAddValidate
     * @since 1.1.0
     * @param string $author - Author of the comment
     * @param string $title - Comment title
     * @param string $message - Comment message
     * @param string $rating - Rating of the message
     * @param array  $errors - Validation errors
     */
    public function hookPhpCommentAddValidate($author, $title, $message, $rating, &$errors)
    {
        $data = array(
            'message' => $message,
            'comment_title' => $title,
        );

        $res = $this->checkForm($data, $errors, 'contact');

        $errors = array_merge((array)$errors, (array)$res['errors']);
        $GLOBALS['error_fields'] .= $res['error_fields'];
    }

    public function hookRlMessagesAjaxContactOwnerValidate($name, $email, $phone, $message, $listing_id)
    {
        $data = array('contact_message' => $message);
        $errors = $GLOBALS['errors'];

        $res = $this->checkForm($data, $errors, 'contact');
        $GLOBALS['errors'] = array_merge((array)$errors, (array)$res['errors']);
    }

    /**
     * @param \Flynax\Classes\EditListing $editListing - Add listing class instance
     * @param array                       $data - Form input data
     * @param array                       $errors - Form validation error
     * @param array                       $error_fields - Form validation errors field
     */
    public function hookEditListingDataChecking($editListing, $data, &$errors, &$error_fields)
    {
        $res = $this->checkForm($data, $errors, 'listing');

        if (null !== $errors) {
            $errors = array_merge($errors, $res['errors']);
            $error_fields .= $res['error_fields'];
            return;
        }

        $GLOBALS['errors'] = array_merge($GLOBALS['errors'], $res['errors']);
        $GLOBALS['error_fields'] .= $res['error_fields'];

        return;
    }

    /**
     * @hook profileController
     * @since 1.1.0
     */
    public function hookProfileController()
    {
        $data = $_POST['account'];
        $errors = $GLOBALS['errors'];

        $res = $this->checkForm($data, $errors, 'account');

        $GLOBALS['errors'] = array_merge((array)$errors, (array)$res['errors']);
        $GLOBALS['error_fields'] .= $res['error_fields'];
    }

    /**
     * @hook contactsCheckData
     * @since 1.1.0
     */
    public function hookContactsCheckData()
    {
        $data = array(
            'message' => $GLOBALS['message'],
            'your_name' => $GLOBALS['your_name'],
        );
        $errors = $GLOBALS['errors'];
        $res = $this->checkForm($data, $errors, 'contact');

        $GLOBALS['errors'] = array_merge((array)$errors, (array)$res['errors']);
        $GLOBALS['error_fields'] .= $res['errors_fields'];
    }

    /**
     * @hook apExtAccountFieldsData
     * @since 1.1.0
     */
    public function hookApExtAccountFieldsData()
    {
        global $data;

        foreach ($data as $key => $value) {
            $sql = "SELECT COUNT(`ID`) AS `count` FROM `{$this->table}`  WHERE `Code` = '$value[Code]'";
            $count = $GLOBALS['rlDb']->getRow($sql);
            $data[$key]['bw_count'] = $count['count'];
        }
    }

    /**
     * @hook apTplFooter
     * @since 1.1.0
     */
    public function hookApTplFooter()
    {
        $admin_js = RL_PLUGINS_URL . 'badWordsFilter/static/lib_admin.js';
        echo sprintf('<script type="text/javascript" src="%s"></script>', $admin_js);

        $script = "<script type='text/javascript'>$('#mPlugin_badWordsFilter').remove()</script>";
        echo $script;
    }

    /**
     * @hook apAjaxRequest
     * @since 1.1.0
     * @param array  $out - Answer of the action, should be formatted to the AJAX response format
     * @param string $item - Ajax Request item(action)
     */
    public function hookApAjaxRequest(&$out, $item)
    {
        $answer = null;
        $item = !is_null($item) ? $item : $GLOBALS['item'];
        if (!$this->isValidRequest($item)) {
            return;
        }

        switch ($item) {
            case 'addBadWord':
                $bad_word = $GLOBALS['rlValid']->xSql($_POST['bad_word']);
                $this->lang_id = (int)$_POST['lang_id'];

                $answer = $this->ajaxAddBadWord($bad_word, 'active');
                break;
            case 'removeBadWord':
                $id = (int)$_POST['id'];;
                $answer = $this->ajaxDeleteBadWord($id);
                break;
        }

        if (is_null($out)) {
            $GLOBALS['out'] = $answer;
            return;
        }

        $out = $answer;
    }

    /**
     * Checking is request is valid
     *
     * @since 1.1.0
     * @param $request - Request type
     * @return bool    - Is request valid
     */
    public function isValidRequest($request)
    {
        $valid = array(
            'addBadWord',
            'removeBadWord',
        );

        return in_array($request, $valid);
    }

    /**
     * @hook apPhpIndexBottom
     * @since 1.1.0
     */
    public function hookApPhpIndexBottom()
    {
        global $breadCrumbs;

        if ($_GET['controller'] == 'bad_words_filter' && isset($_GET['lang'])) {
            $breadCrumbs[0]['name'] = $GLOBALS['lang']['admin_controllers+name+languages'];
            $breadCrumbs[0]['Controller'] = 'languages';
        }
    }

    /**
     * @hook beforeRegister
     * @since 1.1.0
     */
    public function hookBeforeRegister()
    {
        $data = $_POST['account'];
        $errors = $GLOBALS['errors'];

        $res = $this->checkForm($data, $errors, 'account');

        $GLOBALS['errors'] = array_merge((array)$errors, (array)$res['errors']);
        $GLOBALS['error_fields'] .= $res['error_fields'];
    }

    /**
     * Checking submit form for finding bad words matches
     *
     * @param  array  $data - Form inputs data
     * @param  array  $errors - Validation errors
     * @param  string $type - Type of the checking
     * @return array  $res    - Checking bad words result array with errors and errors_fields value
     */
    public function checkForm($data, &$errors, $type)
    {
        global $lang;

        $bw_error_fields = '';
        $bw_errors = array();
        $bw_in = array();

        $sql = "SELECT `Value` FROM `{$this->table}` WHERE `Status` = 'active'";
        $badword_list = $GLOBALS['rlDb']->getAll($sql, array(false, 'Value'));

        $this->badWordsList = $badword_list;
        $badword_list = array_map(function ($item) {
            return str_replace(array("\n", "\r\n", "\r"), '', $item);
        }, $badword_list);
        $this->badWordsList = $badword_list;

        if ($badword_list) {
            foreach ($data as $field_key => $value) {
                if (is_array($value) && isset($value['value'])) {
                    $value_to_check = $value['value'];
                } elseif (is_array($value) && isset($value[0])) {
                    $value_to_check = $value['value'][0];
                } else {
                    $value_to_check = $value;
                }

                switch ($type) {
                    case 'comment':
                        $field_title = $field_key == 'message' ? $lang['message'] : $lang['comment_' . $field_key];
                        break;
                    case 'contact':
                        $field_title = $field_key != 'message' ? $lang[$field_key] : $lang['message'];
                        break;
                    default:
                        if ($field_key == 'location') {
                            $field_title = $lang['personal_address'];
                            break;
                        }

                        $field_title = $lang[$type . "_fields+name+" . $field_key]
                            ? $lang[$type . "_fields+name+" . $field_key]
                            : $lang[$field_key];
                        break;
                }

                if ($this->isFieldMultiLanguage($field_key)) {
                    foreach ($value as $lang_code => $lang_phrase_value) {
                        if ($this->doBadWordCheck($lang_phrase_value)) {
                            $bw_in[] = array(
                                'key' => $field_key,
                                'pName' => $field_title,
                            );
                            break;
                        }
                    }

                    continue;
                }

                if ($this->doBadWordCheck($value_to_check)) {
                    $bw_in[] = array(
                        'key' => $field_key,
                        'pName' => $field_title,
                    );
                }

            }

            foreach ($bw_in as $item) {
                $bw_errors[] = $lang['bw_error'] . " <b>" . $item['pName'] . "</b>";

                if ($GLOBALS['page_info']['Controller'] == 'registration') {
                    $fieldName = $GLOBALS['cur_step'] ? 'account' : 'profile';
                    $bw_error_fields .= "{$fieldName}[" . $item['key'] . "],";
                } else {
                    $bw_error_fields .= "f[" . $item['key'] . "],";
                }
            }

            $res = array();
            $res['errors'] = array();

            /* applying to the existing errors array */
            foreach ($bw_errors as $key => $value) {
                $res['errors'][] = $value;
            }

            $res['error_fields'] = $bw_error_fields;

            return $res;
        }

        return $errors;
    }

    /**
     * Check string for bad word field
     *
     * @since 1.2.0
     *
     * @param  string $string - Checking string
     * @return bool|int       - Is checking successful
     */
    public function doBadWordCheck($string)
    {
        if (!$this->badWordsList || !$string || !is_string($string) || in_array($string, ['0', '1'])) {
            return false;
        }

        $regex = $GLOBALS['config']['bw_scan_mode']
            ? sprintf('/(?<![\w\d])(%s)(?![\w\d])/iu', implode('|', $this->badWordsList))
            : '/(' . implode('|', $this->badWordsList) . ')/iu';

        return preg_match($regex, $string, $matches, PREG_OFFSET_CAPTURE);
    }

    /**
     * Checking is provided Listing Field multi-language field
     *
     * @since 1.2.0
     *
     * @param  string $field_key - Checking Listing Field key
     * @return bool
     */
    public function isFieldMultiLanguage($field_key = '')
    {
        if (!$field_key) {
            return false;
        }

        return $GLOBALS['rlDb']->getOne('Multilingual', "`Key` = '{$field_key}'", 'listing_fields');
    }

    /**
     * @hook apTplLanguageGrid
     * @since 1.1.0
     **/
    public function hookApTplLanguagesGrid()
    {
        global $lang;

        $bw_count = $lang['bw_manage'];
        $bw_column_header = $lang['bw_manage_header'];

        echo <<<JS
        var exjs_bw_column = {
                  header: '$bw_column_header',
                  width: 150,
                  fixed: true,
                renderer: function(val, ext, row) {
                   var lang_array = row.data.Data.split('|');
                   var lang_id = lang_array[0];
                   var out = "<b class='x-grid3-cell-inner x-grid3-col-rlExt_item_bold'>" + row.data.bw_count;
                   out += "</b><a class='green_11_bg' href='"+rlUrlHome;
                   out += "index.php?controller=bad_words_filter&amp;lang="+lang_id+"'>$bw_count</a>";
                   
                   return out;
              }
         };
        languagesGrid.getInstance().columns.splice(2, 0, exjs_bw_column);
        languagesGrid.getInstance().fields.push({name: 'bw_count', mapping: 'bw_count'});
JS;
    }

    /**
     * Plugin uninstall
     *
     * @since 1.1.0
     */
    public function uninstall(): void
    {
        $GLOBALS['rlDb']->dropTable('bad_words');
    }

    /**
     * Plugin installation
     *
     * @since 1.1.0
     */
    public function install(): void
    {
        $raw_sql = "
            `ID` int(11) NOT NULL auto_increment,
            `Code` char(2) character set utf8 NOT NULL,
            `Value` varchar(255) character set utf8 NOT NULL,
            `Status` enum('active','approval','trash') NOT NULL default 'active',
            PRIMARY KEY  (`ID`),
            UNIQUE `bw_uniq` (`Code`,`Value`)";
        $GLOBALS['rlDb']->createTable('bad_words', $raw_sql);

        $this->checkInstalledLanguages();
        $GLOBALS['rlPlugin']->controller = '';
    }

    /**
     * Update to 1.0.1 version
     */
    public function update101(): void
    {
        $GLOBALS['rlDb']->query("ALTER TABLE `{db_prefix}bad_words` CHANGE `Code` `Code` CHAR(2) NOT NULL");
    }

    /**
     * Update to 1.2.2 version
     */
    public function update122(): void
    {
        global $rlDb;

        if (array_key_exists('ru', $GLOBALS['languages'])) {
            $russianTranslation = json_decode(file_get_contents(RL_PLUGINS . 'badWordsFilter/i18n/ru.json'), true);
            foreach ($russianTranslation as $phraseKey => $phrase) {
                if ($rlDb->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $rlDb->updateOne([
                        'fields' => ['Value' => $phrase],
                        'where' => ['Key'   => $phraseKey, 'Code' => 'ru'],
                    ], 'lang_keys');
                } else {
                    $rlDb->insertOne([
                        'Code'   => 'ru',
                        'Module' => 'common',
                        'Key'    => $phraseKey,
                        'Value'  => $phrase,
                        'Plugin' => 'badWordsFilter',
                    ], 'lang_keys');
                }
            }
        }
    }
}
