<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : MESSAGEBUILDER.PHP
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

namespace Autoposter;

class MessageBuilder
{

    /**
     * @var int - id of the working category
     */
    private $category_id;

    /**
     * @var string - key of the working category
     */
    private $category_key;

    /**
     * @var \rlDb
     */
    private $rlDb;

    /**
     * @var \rlCategories
     */
    private $rlCategories;
    /**
     * @var \rlBuilder
     */
    private $builderClass;

    /**
     * @var \rlActions
     */
    private $rlActions;

    /**
     * @var string - key base of the all messages
     */
    private $message_lang_key;

    /**
     * @var string - key of the post's footer
     */
    private $post_footer_lang_key;

    /**
     * @var string - key of the post description
     */
    private $post_description_lang_key;

    /**
     * MessageBuilder constructor.
     */
    public function __construct()
    {
        $this->rlDb = AutoPosterContainer::getObject('rlDb');
        $this->rlCategories = AutoPosterContainer::getObject('rlCategories');
        $this->rlActions = AutoPosterContainer::getObject('rlActions');
        $this->builderClass = AutoPosterContainer::getObject('rlBuilder');
        $this->message_lang_key = 'categories+auto_posting_message+';
        $this->post_footer_lang_key = 'categories+auto_posting_footer+';
        $this->post_description_lang_key = 'categories+auto_posting_description+';
    }

    /**
     * Getting all messages by category key.
     *
     * @param string $type
     * @return array $messages - Messag patterns of the category
     */
    public function getAll($type = '')
    {
        $messages = [];

        $key = $this->getPostPart($type);
        $sql = "SELECT `ID`, `Code`, `Value` FROM `" . RL_DBPREFIX . "lang_keys` ";
        $sql .= "WHERE `Key` = '{$key}'";
        $result = $this->rlDb->getAll($sql);

        foreach ($result as $item) {
            $messages[$item['Code']] = $item;
        }

        return $messages;
    }

    /**
     * Get all post footer part messages
     *
     * @return array - messages
     */
    public function getAllFotterMessage()
    {
        return $this->getAll('footer');
    }

    /**
     * Get all post message part
     *
     * @return array - messages
     */
    public function getAllMessages()
    {
        return $this->getAll('header');
    }

    public function getDescription()
    {
        return array_shift($this->getAll('description'))['Value'];
    }

    /**
     * Adding new message to the lang_keys table
     *
     * @param string $message - Message body
     * @param string $lang    - Editing language code
     * @param string $type    - Message part
     */
    public function add($message, $lang, $type = '')
    {
        $insert_lang = array(
            'Code' => $lang,
            'Module' => 'common',
            'Plugin' => 'autoPoster',
            'Value' => $message,
        );
        $insert_lang['Key'] = $this->getPostPart($type);

        $this->rlActions->insertOne($insert_lang, 'lang_keys');
    }

    /**
     * Getting lang key by part
     *
     * @param  string      $type - type of the post part
     * @return string|bool $part - Lang key or false if type was misstyped
     */
    public function getPostPart($type)
    {
        $part = false;
        switch ($type) {
            case 'header':
                $part = $this->message_lang_key . $this->category_key;
                break;
            case 'description':
                $part = $this->post_description_lang_key . $this->category_key;
                break;
            case 'footer':
                $part = $this->post_footer_lang_key . $this->category_key;
                break;
        }

        return $part;
    }

    /**
     * Edit message
     *
     * @param string $message - Message body
     * @param string $lang - Editing language code
     * @param string $type - Type of the post {header, description, footer}
     */
    public function edit($message, $lang, $type = '')
    {
        $update = array(
            'fields' => array(
                'Value' => $message,
            ),
            'where' => array(
                'Code' => $lang,
            ),
        );
        $update['where']['Key'] = $this->getPostPart($type);

        $this->rlActions->updateOne($update, 'lang_keys');
    }

    /**
     * Delete empty pattern message by pattern type
     *
     * @since 1.4.0
     *
     * @param string $lang - Language code
     * @param string $type - Phrase type
     */
    public function delete($lang, $type)
    {
        if ($phrase_key = $this->getPostPart($type)) {
            $this->rlDb->delete(['Key' => $phrase_key, 'Code' => $lang], 'lang_keys');
        }
    }

    /**
     * Edit description field
     *
     * @param string $field - Field value
     * @param string $lang  - Languge ISO code
     */
    public function editDescription($field, $lang)
    {
        $this->edit($field, $lang, 'description');
    }

    /**
     * Check if this pattern is valid. Checking conditions:
     *     - Pattern shouldn't be empty
     *     - Each field in the pattern should be builded in the category
     *
     * @param $message_array
     * @return bool
     */
    public function isValidPattern($message_array)
    {
        $errors = $error_fields = [];
        $allLangs = AutoPosterContainer::getConfig('languages');
        $lang = AutoPosterContainer::getConfig('lang');
        $cat_id = $this->rlDb->getOne('ID', "`Key` = '{$this->category_key}'", 'categories');
        $notice = [];

        // catch empty patterns
        foreach ($allLangs as $lkey => $lval) {
            $message_pattern = $message_array[$lval['Code']];
            if (empty($message_pattern)) {
                $errors[] = str_replace(
                    '{field}',
                    "<b>" . $lang['ap_message_in_posts'] . "({$lval['name']})</b>",
                    $lang['notice_field_empty']
                );
                $error_fields[] = "facebook_message[{$lval['Code']}]";
            }

            $listing_fields = $this->getFieldsFromPattern($message_pattern);
            if ($non_built_fields = $this->hasNonBuildedField($listing_fields, $cat_id)) {
                foreach ($non_built_fields as $field) {
                    $notice[] = $field['Value'];
                }
            }
        }

        // notice
        $GLOBALS['errors'] = $errors;
        $GLOBALS['error_fields'] = $error_fields;
    }

    /**
     * Validating of the description field
     *
     * @param  string $description - Listing fields description key
     */
    public function isValidDescription($description)
    {
        print_r($description);exit;
    }

    /**
     * Parsing message pattern and return all found fields
     *
     * @param  string $message_pattern - Message pattern
     * @return mixed  $fields          - Listing fields
     */
    public function getFieldsFromPattern($message_pattern)
    {
        $fields = [];
        $regex = '/{\K[^}]*(?=})/m';
        preg_match_all($regex, $message_pattern, $matches);

        if (!empty($matches[0])) {
            foreach ($matches[0] as $key => $field) {
                $fields[$key]['ID'] = $this->rlDb->getOne('ID', "`Key` = '{$field}'", 'listing_fields');
                $fields[$key]['Value'] = $field;
            }
        }

        return $fields;
    }

    /**
     * Is some field is missing in the category builder of this category and all parents
     *
     * @param  array   $fields - Checking fields
     * @param  integer $cat_id - Category ID
     * @return array   $empty_fields - Empty fields
     */
    public function hasNonBuildedField($fields, $cat_id)
    {
        $empty_fields = [];
        $rlCategories = AutoPosterContainer::getObject('rlCategories');
        $forms = $this->builderClass->getAvailableFields($cat_id);

        if (empty($forms)) {
            // TODO: Need to check all parents builder, if current category doesn't set
        }
        foreach ($fields as $field) {
            if (!in_array($field['ID'], $forms)) {
                $empty_fields[] = $field;
            }
        }

        return $empty_fields;
    }

    /**
     * Getting category key
     *
     * @return string - Category key
     */
    public function getCategoryKey()
    {
        return $this->category_key;
    }

    /**
     * Set category key value
     *
     * @param string $category_key - Setting category key
     */
    public function setCategoryKey($category_key)
    {
        $this->category_key = AutoPosterContainer::getObject('rlValid')->xSql($category_key);
    }

    /**
     * Does any message exist for the category key
     *
     * @param  string $message - Checkng message
     * @param  string $lang    - Checking language
     * @return bool            - Does message is exist
     */
    public function isMessageExist($message, $lang)
    {
        return $this->isExist($lang, 'header');
    }

    /**
     * Is footer pattern exist (isExist helper)
     *
     * @param string $footer_text - Footer pattern
     * @param string $lang        - ISO code of the searching footer pattern
     * @return bool               - Does pattern is exist
     */
    public function isFooterExist($footer_text, $lang)
    {
        return $this->isExist($lang, 'footer');
    }

    /**
     * Does field exist in the database
     *
     * @param  string $lang    - Language ISO code of the pattern
     * @param  string $type    - Place where script should find pattern {header, footer, description}
     * @return bool            - Existing of this pattern
     */
    public function isExist($lang, $type = '')
    {
        $key = $this->getPostPart($type);
        $where = "`Key` = '{$key}' AND `Code` = '{$lang}'";

        return boolval($this->rlDb->getOne('ID', $where, 'lang_keys'));
    }

    /**
     * Handle messages array and decide what actions should run for each one
     *
     * @param array $message_patterns - Message patterns
     */
    public function handleMessages($message_patterns)
    {
        foreach ($message_patterns as $lang => $message) {
            if ($this->isMessageExist($message, $lang)) {
                if ($message) {
                    $this->edit($message, $lang, 'header');
                } else {
                    $this->delete($lang, 'header');
                }
            } elseif ($message) {
                $this->add($message, $lang, 'header');
            }
        }
    }

    /**
     * Handle all footer message pattern, which are came from POST request
     *
     * @param array $message_patterns - Footer messages
     */
    public function handleFooterMessages($message_patterns)
    {
        foreach ($message_patterns as $lang => $message) {
            if ($this->isFooterExist($message, $lang)) {
                if ($message) {
                    $this->editFooterText($message, $lang);
                } else {
                    $this->delete($lang, 'footer');
                }
            } elseif ($message) {
                $this->addFooterText($message, $lang);
            }
        }
    }

    /**
     * Handle description field
     *
     * @param sring $field - Selected description field
     */
    public function handleDescription($message_patterns)
    {
        foreach ($message_patterns as $lang => $message) {
            if ($this->isDescriptionExist($message, $lang)) {
                if ($message) {
                    $this->editDescription($message, $lang);
                } else {
                    $this->delete($lang, 'description');
                }
            } elseif ($message) {
                $this->addDescription($message, $lang);
            }
        }
    }

    /**
     * Is description field exist (helper function)
     *
     * @param  string $lang  - Language of the field
     * @return bool          - Does field is exist
     */
    public function isDescriptionExist($lang)
    {
        return $this->isExist($lang, 'description');
    }

    /**
     * Adding post main message pattern
     *
     * @param string $message - Message pattern
     * @param string $lang    - Language ISO code
     */
    public function addMessagText($message, $lang)
    {
        $this->add($message, $lang, 'header');
    }

    /**
     * Adding foooter message pattern
     *
     * @param string $message - Message pattern
     * @param string $lang    - Language ISO code
     */
    public function addFooterText($message, $lang)
    {
        $this->add($message, $lang, 'footer');
    }

    /**
     * Add description
     *
     * @param string $field - Field value
     * @param string $lang  - Languge ISO code
     */
    public function addDescription($field, $lang)
    {
        $this->add($field, $lang, 'description');
    }

    /**
     * Edit foooter message pattern
     *
     * @param string $message - Message pattern
     * @param string $lang    - Language ISO code
     */
    public function editFooterText($message, $lang)
    {
        $this->edit($message, $lang, 'footer');
    }

    /**
     * Prepare message pattern to the posting process
     *
     * @param  array $listing_info - Posting listing information
     * @param  strin $lang         - Language of the message
     * @return bool|string         - Prepared message string or false if message didn't found
     */
    public function decodeMessage($listing_info, $lang)
    {
        $rlCommon = AutoPosterContainer::getObject('rlCommon');
        $rlLang = AutoPosterContainer::getObject('rlLang');
        $GLOBALS['lang'] = $rlLang->getLangBySide('frontEnd', $lang);
        $checking_ids[] = $listing_info['Category_ID'];
        $parent_ids = $this->rlCategories->getParentIDs($listing_info['Category_ID']);
        $general_cat_id[] = $this->getGeneralCatByType($listing_info['Listing_type']);
        $checking_ids = array_filter(array_merge((array) $checking_ids, (array) $parent_ids, (array) $general_cat_id));
        $message_pattern = $decoded_message = '';
        foreach ($checking_ids as $cat_id) {
            $cat_info = $this->rlCategories->getCategory($cat_id);

            $this->setCategoryKey($cat_info['Key']);
            if ($this->isMessageExist('', $lang)) {
                $message_pattern = $this->getAllMessages()[$lang]['Value'];
                if ($message_pattern) {
                    break;
                }
            }
        }

        if ($message_pattern) {
            preg_match_all('/\{([^\{]+)\}+/', $message_pattern, $fields);
            $possible_fields = $GLOBALS['rlValid']->xSql($fields[1]);

            $where = "AND FIND_IN_SET(`Key`, '" . implode(",", $possible_fields) . "')";
            $fields_info = $this->rlDb->fetch("*", array('Status' => 'active'), $where, null, 'listing_fields');

            foreach ($fields_info as $key => $value) {
                $tmpArray[$value['Key']] = $value;
            }
            $fields_info = $tmpArray;
            unset($tmpArray);

            foreach ($possible_fields as $key => $field_key) {
                if ($field_key === 'Category_ID' && version_compare($GLOBALS['config']['rl_version'], '4.8.1', '>=')) {
                    if ($category_phrases = $rlLang->getLangBySide('category')) {
                        $GLOBALS['lang'] = array_merge($GLOBALS['lang'], $category_phrases);
                    }
                }

                $replacement[] = $rlCommon->adaptValue(
                    $fields_info[$field_key],
                    $listing_info[$field_key],
                    'listing',
                    $listing_info['ID'],
                    true,
                    false,
                    false,
                    false,
                    $listing_info['Account_ID'],
                    'short_form',
                    $listing_info['Listing_type']
                );
            }

            $decoded_message = str_replace($fields[0], $replacement, $message_pattern);
        }

        $decoded_message = html_entity_decode($decoded_message);
        return strip_tags($decoded_message);
    }

    /**
     * Getting General Category ID of the provided listing type
     * @param  string $listing_type - Listing type key
     * @return int                  - Found General Category ID
     */
    public function getGeneralCatByType($listing_type)
    {
        $cat_id = $this->rlDb->getOne('Cat_general_cat', "`Key` = '{$listing_type}'", 'listing_types');
        return $cat_id;
    }
}
