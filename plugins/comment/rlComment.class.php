<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLCOMMENT.CLASS.PHP
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

use Flynax\Utils\Profile;
use Flynax\Utils\Valid;

class rlComment extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * Total comments calc per listing
     *
     * @since 4.0.0
     */
    public $calc = 0;

    /**
     * Comments limit per page
     *
     * @since 4.0.0
     */
    public $limit = 10;

    /**
     * Is box with ads available on the page
     *
     * @since 4.1.0
     * @var boolean
     */
    public $adsBox = false;

    /**
     * Allowed controllers for svg listings grid icon
     *
     * @since 4.1.0
     * @var array
     */
    public $allowed_controllers = [
        'search',
        'listing_type',
        'my_favorite',
        'recently_added',
        'listings_by_field',
        'rv_listings',
        'account_type'
    ];

    /**
     * Box cache code
     *
     * @since 4.0.0
     */
    private $content = "
        global \$rlSmarty, \$config, \$reefless;

        \$code = <<< FL
{data_replace}
FL;

        \$comments = json_decode(\$code, true);
        \$rlSmarty->assign_by_ref('block_comments', \$comments);

        \$reefless->loadClass('Listings');

        foreach (\$comments as &\$comment) {
            \$comment['Listing_title'] = \$GLOBALS['rlListings']->getListingTitle(
                \$comment['Category_ID'],
                \$comment,
                \$comment['Listing_type']
            );

            \$link = \$reefless->url('listing', \$comment);
            \$comment['Listing_link'] = \$link . '#comments';
        }

        if (\$config['comments_select_comments_random'] == 'Random') {
            \$limit = \$config['comments_number_comments'] ?: 5;
            \$comments = array_values(array_intersect_key(\$comments, array_flip(array_rand(\$comments, \$limit))));
        }

        \$rlSmarty->display(RL_PLUGINS . 'comment/comment.sidebar.tpl');
    ";

    /**
     * @since 4.0.0
     */
    public function __construct()
    {
        global $config;

        $config_key = $config['comment_mode'] == 'tab' ? 'comments_per_page' : 'comments_number_comments';
        $this->limit = (int) $config[$config_key];
    }

    /**
     * @since 4.0.0
     */
    public function install()
    {
        global $rlDb, $rlSmarty;

        $rlDb->createTable(
            'comments',
            "`ID` int(4) NOT NULL AUTO_INCREMENT,
            `User_ID` int(5) NOT NULL default '0',
            `Listing_ID` int(7) NOT NULL default '0',
            `Author` varchar(100) CHARACTER SET utf8 NOT NULL default '',
            `Title` tinytext CHARACTER SET utf8 NOT NULL,
            `Description` mediumtext CHARACTER SET utf8 NOT NULL,
            `Rating` int(3) NOT NULL default '0',
            `Date` datetime NOT NULL default '0000-00-00 00:00:00',
            `Status` enum('active','pending','approval') NOT NULL default 'active',
            PRIMARY KEY (`ID`),
            KEY `User_ID` (`User_ID`),
            KEY `Listing_ID` (`Listing_ID`),
            KEY `Status` (`Status`)",
            RL_DBPREFIX,
            'ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;'
        );

        $columns = array(
            'comments_count' => "INT(6) NOT NULL AFTER `Date`",
            'comments_rating' => "DOUBLE NOT NULL AFTER `Date`",
        );
        $rlDb->addColumnsToTable($columns, 'listings');

        $GLOBALS['rlDb']->query("
            UPDATE `{db_prefix}blocks` SET `Sticky` = '0', `Page_ID` = '2,8'
            WHERE `Key` = 'comments_block' LIMIT 1
        ");

        $GLOBALS['rlDb']->query("
            UPDATE `{db_prefix}blocks`
            SET `Status` = 'trash', `Sticky` = '0', `Page_ID` = '25', `Position` = '1', `Plugin` = ''
            WHERE `Key` = 'comments_block_bottom' LIMIT 1
        ");

        $GLOBALS['rlDb']->addColumnToTable('Comments_module', "enum('0', '1') NOT NULL DEFAULT '1' AFTER `Status`", 'listing_types');

        $this->updateBox();
    }

    /**
     * Updates sidebar box cache
     *
     * @since 4.0.0
     */
    public function updateBox()
    {
        global $rlDb, $reefless;

        $comments = $this->selectCommentsInBlock();

        $update = array(
            'fields' => array(
                'Content' => str_replace('{data_replace}', json_encode($comments), $this->content)
            ),
            'where' => array(
                'Key' => 'comments_block'
            )
        );

        $allow_html = $rlDb->rlAllowHTML;
        $rlDb->rlAllowHTML = true;
        $rlDb->updateOne($update, 'blocks');
        $rlDb->rlAllowHTML = $allow_html;
    }

    /**
     * Get comments
     *
     * @param  int  $listingID - Related listing ID
     * @param  int  $page      - Current comments page
     * @return array           - List of the Comments
     */
    public function getComments($listingID, $page = 0)
    {
        global $rlDb, $config;

        $listingID = (int) $listingID;
        $start = $page > 1 ? ($page - 1) * $this->limit : 0;

        $comments = $rlDb->getAll("
            SELECT SQL_CALC_FOUND_ROWS `T1`.*, `T2`.`Own_address`, `T2`.`Type`, `T2`.`ID` AS `Account_ID`
            FROM `{db_prefix}comments` AS `T1`
            LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T2`.`ID` = `T1`.`User_ID`
            WHERE `T1`.`Listing_ID` = {$listingID} AND `T1`.`Status` = 'active'
            ORDER BY `T1`.`Date` DESC
            LIMIT {$start}, {$this->limit}
        ");
  
        $calc = $rlDb->getRow("SELECT FOUND_ROWS() AS `calc`");
        $this->calc = $calc['calc'];
    
        foreach ($comments as &$comment) {
            $comment['Description'] = preg_replace(
                '/(https?\:\/\/[^\s]+)/',
                '<a href="$1">$1</a>',
                $comment['Description']
            );

            if ($comment['Own_address'] && $comment['Account_ID']) {
                $account_data = array(
                    'ID' => $comment['Account_ID'],
                    'Type' => $comment['Type'],
                    'Own_address' => $comment['Own_address'],
                );
                $comment['Personal_address'] = Profile::getPersonalAddress($comment);

                /**
                 * Remove multifield locfix
                 *
                 * @todo - remove once this issue fixed in Multifield
                 */
                if ($GLOBALS['plugins']['multiField']) {
                    $comment['Personal_address'] = str_replace('locfix', '', $comment['Personal_address']);
                }
            }
        }

        return $comments;
    }

    /**
     * Add comment
     *
     * @param  string $author        - Comment author
     * @param  int    $listingID     - Comment title
     * @param  string $title         - Comment title
     * @param  string $message       - Comment message
     * @param  string $securityCode  - Comment security code
     * @param  int    $rating        - Rating number
     * @return array                 - Response object
     */
    public function ajaxCommentAdd($author, $listingID, $title, $message, $securityCode = '', $rating = 0)
    {
        global $config, $lang, $account_info, $rlListingTypes, $rlSmarty, $rlDb, $reefless;

        $errors = array();
        $errors_fields = array();

        if (!$this->isCommentOnOwnAllowed($account_info['ID'], $listingID)) {
            $errors[] = $GLOBALS['rlLang']->getSystem('comment_own_listing_not_allowed');
        }

        if (!$errors) {
            if (empty($author)) {
                $errors[] = str_replace('{field}', '<b>' . $lang['comment_author'] . '</b>', $lang['notice_field_empty']);
                $errors_fields[] = '#comment_author';
            }

            if (empty($title)) {
                $errors[] = str_replace('{field}', '<b>' . $lang['comment_title'] . '</b>', $lang['notice_field_empty']);
                $errors_fields[] = '#comment_title';
            }

            if (empty($message)) {
                $errors[] = str_replace('{field}', '<b>' . $lang['message'] . '</b>', $lang['notice_field_empty']);
                $errors_fields[] = '#comment_message';
            }

            if (!$rating && $config['comments_rating_module']) {
                $errors[] = $GLOBALS['rlLang']->getSystem('comments_no_rating_set');
            }

            if ($config['security_img_comment_captcha']
                && ($securityCode != $_SESSION['ses_security_code_comment'] || !$securityCode)
            ) {
                $errors[] = $lang['security_code_incorrect'];
                $errors_fields[] = '#comment_security_code';
            }
        }

        $GLOBALS['rlHook']->load('phpCommentAddValidate', $author, $title, $message, $rating, $errors);

        if ($errors) {
            $error_content = '<ul>';
            foreach ($errors as $error) {
                $error_content .= '<li>' . $error . '</li>';
            }
            $error_content .= '</ul>';

            return [
                'status' => 'ERROR',
                'data' => $error_content,
                'error_fields' => $errors_fields
            ];
        } else {
            $reefless->setTable('comments');

            $listingID = (int) $listingID;

            $account_id = intval($account_info['ID'] ?: 0);
            $status = $config['comment_auto_approval'] ? 'active' : 'pending';

            $message = strip_tags($message, '<a>');
            $message = preg_replace('/<a\s+(title="[^"]+"\s+)?href=["\']([^"\']+)["\'][^\>]*>[^<]+<\/a>/mi', '$2', $message);

            $comment = array(
                'User_ID' => $account_id,
                'Listing_ID' => $listingID,
                'Author' => $author,
                'Title' => $title,
                'Description' => $message,
                'Rating' => (int) $rating,
                'Status' => $status,
                'Date' => 'NOW()',
            );

            $rlDb->insertOne($comment, 'comments');

            // Update count
            if ($config['comment_auto_approval']) {
                $GLOBALS['rlLang']->getSystem('comments_number');
                $rating = $this->getListingRating($listingID);

                $rlDb->query("
                    UPDATE `{db_prefix}listings`
                    SET `comments_count` = `comments_count` + 1,  `comments_rating` = {$rating}
                    WHERE `ID` = '{$listingID}' LIMIT 1
                ");
            }

            if ($config['comments_send_email_after_added_comment']) {
                $reefless->loadClass('Mail');
                $reefless->loadClass('Listings');
                $reefless->loadClass('Account');

                $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('comment_email');

                $listing_info = $GLOBALS['rlListings']->getListing($listingID);
                $listing_type = $rlListingTypes->types[$listing_info['Listing_type']];
                $account_info = $GLOBALS['rlAccount']->getProfile((int) $listing_info['Account_ID']);
                $listing_title = $GLOBALS['rlListings']->getListingTitle(
                    $listing_info['Category_ID'],
                    $listing_info,
                    $listing_info['Listing_type']
                );

                $message = nl2br($message);

                $link = $reefless->url('listing', $listing_info);
                $link = '<a href="' . $link . '">' . $listing_title . '</a>';

                $mail_tpl['body'] = str_replace(
                    array('{name}', '{author}', '{title}', '{message}', '{listing_title}'),
                    array($account_info['Full_name'], $author, $title, $message, $link),
                    $mail_tpl['body']
                );
                $GLOBALS['rlMail']->send($mail_tpl, $account_info['Mail']);
            }

            $comments = $this->getComments($listingID);

            $pages = ceil($this->calc / $this->limit);

            $rlSmarty->assign_by_ref('comments', $comments);
            $rlSmarty->assign('comment_pages', $pages);
            $rlSmarty->assign('comment_page', 1);
            $rlSmarty->assign('lang', $lang);
            $rlSmarty->assign('total_comments', $this->calc);
            $rlSmarty->assign('isLogin', $_SESSION['account']['Full_name']);

            $html = $rlSmarty->fetch(RL_PLUGINS . 'comment/comment_dom.tpl', null, null, false);
            $phrase_key = $config['comment_auto_approval'] ? 'notice_comment_added' : 'notice_comment_added_approval';

            if ($config['comment_auto_approval']) {
                $this->updateBox();

                $listing_data = [
                    'comments_rating' => $rating,
                    'comments_count' => $this->calc
                ];
                $rlSmarty->assign('listing_data', $listing_data);
                $informer_html = $rlSmarty->fetch(RL_PLUGINS . 'comment/listing_details_dom.tpl', null, null, false);
            }

            return [
                'status' => 'OK',
                'data' => $html,
                'informer' => $informer_html,
                'mess' => $GLOBALS['rlLang']->getSystem($phrase_key)
            ];
        }
    }

    /**
     * Get average listing rating
     *
     * @since 4.1.0
     *
     * @param  int    $listingID - Listing ID
     * @return float             - Average listing rating
     */
    public function getListingRating(int $listingID): float
    {
        if (!$GLOBALS['config']['comments_rating_module']) {
            return 0;
        }

        $sql = "
            SELECT SUM(`Rating`) AS `Sum_rating`, COUNT(*) AS `Count_comments`
            FROM `{db_prefix}comments`
            WHERE `Listing_ID` = {$listingID} AND `Status` = 'active' AND `Rating` > 0
        ";
        $data = $GLOBALS['rlDb']->getRow($sql);

        return $data['Sum_rating'] && $data['Count_comments'] ? round($data['Sum_rating'] / $data['Count_comments'], 1) : 0;
    }

    /**
     * Defines is comment on own listing allowed
     *
     * @since 4.0.0
     *
     * @param  string $accountID - Account ID
     * @param  string $listingID - Account ID
     * @return boolean           - Is allowed or not
     */
    private function isCommentOnOwnAllowed($accountID, $listingID)
    {
        global $rlSmarty;

        $allowed = true;

        if (!$GLOBALS['config']['comments_own_listings']
            && $accountID == $GLOBALS['rlDb']->getOne('Account_ID', "`ID` = {$listingID}", 'listings')
        ) {
            $allowed = false;

            if (is_object($rlSmarty)) {
                $rlSmarty->assign('comment_own_listing_denied', true);
            }
        }

        return $allowed;
    }

    /**
     * Delete comment by ID
     *
     * @param  int $id - ID of the deleting comment
     * @return array   - Response object
     */
    public function deleteComment($id = 0)
    {
        global $lang, $rlDb;
        
        if (!$id) {
            return ['status' => 'ERROR'];
        }
        
        $sql = "SELECT * FROM `{db_prefix}comments` WHERE id={$id}";
        $comment_info = $rlDb->getRow($sql);
        $listing_id = (int) $comment_info['Listing_ID'];

        $out = array();
        if ($GLOBALS['rlDb']->query("DELETE FROM `{db_prefix}comments` WHERE `ID` = {$id} LIMIT 1")) {
            $this->updateBox();

            // Decrease comment count
            if ($comment_info['Status'] == 'active') {
                $rating = $this->getListingRating($listing_id);
                $sql = "
                    UPDATE `{db_prefix}listings`
                    SET `comments_count` = `comments_count` - 1, `comments_rating` = {$rating}
                    WHERE `ID` = {$listing_id} LIMIT 1
                ";
                $rlDb->query($sql);
            }

            return ['status' => 'OK'];
        } else {
            return ['status' => 'ERROR'];
        }
    }

    /**
     * Select comment into block
     *
     * @return array - Latest or random comments
     **/
    public function selectCommentsInBlock()
    {
        global $config;

        $limit = $config['comments_number_comments'] ?: 5;

        $sql = "
            SELECT `T2`.*, `T1`.`Author` AS `Comment_author`, `T1`.`Title` AS `Comment_title`, `T1`.`ID` AS `Comment_ID`,
            `T1`.`Description` AS `Comment_description`, `T1`.`Rating` AS `Comment_rating`, `T3`.`Type` AS `Listing_type`,
            `T3`.`Path` AS `Category_path`
            FROM `{db_prefix}comments` AS `T1`
            LEFT JOIN `{db_prefix}listings` AS `T2` ON `T1`.`Listing_ID` = `T2`.`ID`
            LEFT JOIN `{db_prefix}categories` AS `T3` ON `T2`.`Category_ID` = `T3`.`ID`
            WHERE `T1`.`Status` = 'active' AND `T2`.`Status` = 'active' AND `T3`.`Status` = 'active'
        ";

        if ($config['comments_select_comments_random'] == 'Last') {
            $sql .= "ORDER BY `T1`.`Date` DESC ";
        } else {
            $limit = $limit >= 5 ? $limit * 2 : 10; // Multiply limit by two to allow box select random comments
            $sql .= "ORDER BY RAND() ";
        }

        $sql .= "LIMIT {$limit}";

        $box_fields = ['Comment_author', 'Comment_title', 'Comment_description', 'Comment_rating', 'Listing_type', 'Category_path'];
        $system_fields = [
            'Account_ID', 'Plan_ID', 'Plan_type', 'Pay_date',
            'Featured_ID', 'Featured_date', 'Last_show', 'Shows', 'Main_photo',
            'Main_photo_x2', 'Photos_count', 'Status', 'Date', 'comments_count',
            'Import_file', 'Loc_latitude', 'Loc_longitude', 'Loc_address',
            'additional_information', 'description', 'ad_description', 'services',
            'about_me', 'availability', 'description_add', 'job_description',
            'electronics', 'about_me', 'services_des'
        ];

        $GLOBALS['rlHook']->load('phpCommentsIgnoreFields', $system_fields);

        $comments = $GLOBALS['rlDb']->getAll($sql);

        foreach ($comments as $key => $comment) {
            $description = preg_replace('/[\n\r]/', '<br />', $comment['Comment_description']);
            Valid::revertQuotes($description);

            $comments[$key]['Comment_description'] = $description;
            $comments[$key]['Comment_title'] = str_replace('&quot;', '"', $comment['Comment_title']);

            // Remove empty and unnecessary listing fields
            foreach ($comment as $field_key => $field_value) {
                if (in_array($field_key, $box_fields)) {
                    continue;
                }

                if (empty($field_value)
                    || $field_value === '0'
                    || $field_value == '0000-00-00 00:00:00'
                    || $field_value == '0000-00-00'
                    || in_array($field_key, $system_fields)
                ) {
                    unset($comments[$key][$field_key]);
                }
            }
        }

        return $comments;
    }

    /**
     * Build admin panel statistics section
     **/
    public function apStatistics()
    {
        global $plugin_statistics, $lang, $rlDb;

        $total = $rlDb->getRow("SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}comments`");
        $total = $total['Count'];

        $sql = "SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}comments` WHERE `Status` = 'pending'";
        $pending = $rlDb->getRow($sql);
        $pending = $pending['Count'];

        $link = RL_URL_HOME . ADMIN . '/index.php?controller=comment';

        $plugin_statistics[] = array(
            'name' => $lang['comment_tab'],
            'items' => array(
                array(
                    'name' => $lang['total'],
                    'link' => $link,
                    'count' => $total,
                ),
                array(
                    'name' => $lang['pending'] . ' / ' . $lang['new'],
                    'link' => $link . '&amp;status=pending',
                    'count' => $pending,
                ),
            ),
        );
    }

    /**
     * Unset comments box
     *
     * @since 4.0.0
     */
    public function unsetBlock()
    {
        if ($GLOBALS['blocks']['comments_block_bottom']) {
            unset($GLOBALS['blocks']['comments_block_bottom']);
            $GLOBALS['rlCommon']->defineBlocksExist($GLOBALS['blocks']);
        }
    }

    /* Hooks */

    /**
     * @hook tplHeader
     */
    public function hookTplHeader()
    {
        if (($GLOBALS['page_info']['Key'] == 'view_details' && $GLOBALS['listing_type']['Comments_module'])
            || $GLOBALS['blocks']['comments_block']
        ) {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'comment' . RL_DS . 'header.tpl');
        }

        $this->checkBlocks();

        if (in_array($GLOBALS['page_info']['Controller'], $this->allowed_controllers) || $this->adsBox) {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'comment' . RL_DS . 'gridHeader.tpl');
        }
    }

    /**
     * @hook listingAfterStats
     *
     * @since 4.0.0
     */
    public function hookListingAfterStats()
    {
        if ($GLOBALS['config']['comments_rating_module']) {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'comment' . RL_DS . 'info_navigator.tpl');
        }
    }

    /**
     * @hook apPhpHome
     * 
     *  @since 4.0.0
     */
    public function hookApPhpHome()
    {
        global $reefless;

        $reefless->loadClass('Comment', null, 'comment');
        $GLOBALS['rlComment']->apStatistics();
    }

    /**
     * @hook listingDetailsBottomTpl
     * 
     * @since 4.0.0
     */
    public function hookListingDetailsBottomTpl()
    {
        global $config;

        if (
            ($config['comments_login_access'] && defined('IS_LOGIN') && IS_LOGIN !== true)
            || $config['comment_mode'] == 'box'
            || !$GLOBALS['listing_type']['Comments_module']
        ) {
            return;
        }

        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'comment/comment.block.tpl');
    }

    /**
     * @hook listingDetailsBottom
     * 
     * @since 4.0.0
     */
    public function hookListingDetailsBottom()
    {
        global $account_info, $rlSmarty, $tabs, $lang, $config, $listing_id;

        if ($account_info && $GLOBALS['listing_data']
            && $account_info['ID'] == $GLOBALS['listing_data']['Account_ID']
            && !$config['comments_own_listings']
        ) {
            $rlSmarty->assign('comment_own_listing_denied', true);
        }

        if (($config['comments_login_access'] && defined('IS_LOGIN') && IS_LOGIN !== true)
            || !$GLOBALS['listing_type']['Comments_module']
        ) {
            $this->unsetBlock();

            return;
        }

        if ($config['comment_mode'] === 'tab') {
            $tabs['comments'] = array(
                'key' => 'comments',
                'name' => $lang['comment_tab']
            );

            $this->unsetBlock();
        } else {
            if ($GLOBALS['listing_data']['comments_count']
                && (
                    !$config['comments_login_access']
                    || ($config['comments_login_access'] && defined('IS_LOGIN'))
                )
            ) {
                $comments = $this->getComments($listing_id);
                $pages = ceil($this->calc / $this->limit);
                
                $rlSmarty->assign_by_ref('comments', $comments);
                $rlSmarty->assign('comment_pages', $pages);
                $rlSmarty->assign('comment_page', 1);
                $rlSmarty->assign('total_comments', $this->calc);
            }
        }
    }

    /**
     * @hook  phpDeleteListingData
     *
     * @since 4.0.0
     */
    public function hookPhpDeleteListingData($id)
    {
        $GLOBALS['rlDb']->query("DELETE FROM `{db_prefix}comments` WHERE `Listing_ID` = '{$id}'");
    }

    /**
     * @hook apAjaxRequest
     *
     * @since 4.0.0
     *
     * @param array  $out
     * @param string $item
     */
    public function hookAjaxRequest(&$out, $item)
    {
        global $rlSmarty, $lang, $account_info;

        if (!$item) {
            return false;
        }

        switch ($item) {
            case 'addComment':
                $out = $this->ajaxCommentAdd(
                    $_REQUEST['comment_author'], 
                    $_REQUEST['listing_id'], 
                    $_REQUEST['comment_title'], 
                    $_REQUEST['comment_message'], 
                    $_REQUEST['comment_security_code'], 
                    $_REQUEST['comment_star']
                );
                break;

            case 'getComments':
                $comments = $this->getComments($_REQUEST['listing_id'], $_REQUEST['page']);

                $pages = ceil($this->calc / $this->limit);

                $rlSmarty->assign('lang', $lang);
                $rlSmarty->assign_by_ref('comments', $comments);
                $rlSmarty->assign('comment_pages', $pages);
                $rlSmarty->assign('comment_page', $_REQUEST['page']);
                $rlSmarty->assign('total_comments', $this->calc);
                $rlSmarty->assign('isLogin', $_SESSION['account']['Full_name']);

                $this->isCommentOnOwnAllowed($account_info['ID'], $_REQUEST['listing_id']);

                $html = $rlSmarty->fetch(RL_PLUGINS . 'comment/comment_dom.tpl', null, null, false);

                if ($html) {
                    $out = [
                        'status' => 'OK',
                        'data' => $html
                    ];
                } else {
                    $out = [
                        'status' => 'ERROR'
                    ];
                }
                break;
        }
    }

    /**
     * Control ajax queries
     *
     * @since 4.0.0
     * @hook apAjaxRequest
     *
     * @param array  $out  - response data
     */
    public function hookApAjaxRequest(&$out = null)
    {
        switch ($_REQUEST['mode']) {
            case 'deleteComment':
                $response = $this->deleteComment($_REQUEST['comment_id']);
            
                $out = $response;
                break;
        }
    }
    
    /**
     * Box stars config validation
     *
     * @since 4.0.0
     *
     * @hook apPhpIndexBeforeController
     */
    public function hookApPhpIndexBeforeController()
    {
        global $dConfig, $cInfo;

        if ($cInfo['Controller'] != 'settings' || !$_POST['post_config']) {
            return;
        }

        if ($_POST['post_config']['comments_stars_number']
            && $_POST['post_config']['comments_stars_number']['value'] > 10
        ) {
            $_POST['post_config']['comments_stars_number']['value'] = 10;
        }
    }

    /**
     * Plugin box status handler
     * 
     * @since 4.0.0
     *
     * @hook apPhpConfigAfterUpdate
     */
    public function hookApPhpConfigAfterUpdate()
    {
        global $config, $dConfig;

        if ($config['comment_mode'] != $dConfig['comment_mode']['value']) {
            $update = array(
                'fields' => array(
                    'Status' => $dConfig['comment_mode']['value'] == 'tab' ? 'trash' : 'active'
                ),
                'where' => array(
                    'Key' => 'comments_block_bottom'
                )
            );
            $GLOBALS['rlDb']->update($update, 'blocks');
        }

        if ($config['comments_select_comments_random'] != $dConfig['comments_select_comments_random']['value']
            || $config['comments_number_comments'] != $dConfig['comments_number_comments']['value']
            || $config['comments_number_symbols_comments'] != $dConfig['comments_number_symbols_comments']['value']
        ) {
            $config['comments_select_comments_random'] = $dConfig['comments_select_comments_random']['value'];
            $config['comments_number_comments'] = $dConfig['comments_number_comments']['value'];
            $config['comments_number_symbols_comments'] = $dConfig['comments_number_symbols_comments']['value'];

            $this->updateBox();
        }
    }
    
    /**
     * @hook apMixConfigItem
     *
     * @since 4.0.0
     *
     * @param  array $value
     * @param  array $systemSelects - Required configs with "select" type
     */
    public function hookApMixConfigItem(&$value, &$systemSelects = null)
    {
        switch ($value['Key']) {
            case 'comment_mode':
                $systemSelects[] = 'comment_mode';
                break;

            case 'comments_select_comments_random':
                $systemSelects[] = 'comments_select_comments_random';
                break;
        }
    }

    /**
     * @since 4.0.0
     * 
     * @hook apTplBlocksNavBar
     */
    public function hookApTplBlocksNavBar()
    {
        if ($GLOBALS['b_key'] == 'comments_block_bottom' ) {
            $GLOBALS['rlSmarty']->assign('preventChangeBoxPosition', true);
        }
    }

    /**
     *  Define plugin related boxes and remove not supported box positions
     *  in edit box mode
     *
     *  @hook apPhpBlocksPost
     * 
     * @since 4.0.0
     */
    public function hookApPhpBlocksPost()
    {
        global $block_info, $l_block_sides;

        if ($block_info['Plugin'] != 'comment') {
            return;
        }
        
        $rejectedBoxSides = array('integrated_banner', 'header_banner');
        
        foreach ($rejectedBoxSides as $side) {
            if ($l_block_sides && $l_block_sides[$side]) {
                unset($l_block_sides[$side]);
            }
        }
    }

    /**
     * @since 4.1.0
     * @hook tplHeaderUserNav
     */
    public function hookTplHeaderUserNav()
    {
        $this->checkBlocks();

        /**
         * @todo - Move this code to tplBodyTop hook once it is available
         */
        if (in_array($GLOBALS['page_info']['Controller'], $this->allowed_controllers) || $this->adsBox) {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'comment/static/comment.svg');
        }
    }

    /**
     * Display rating in featured listing card
     *
     * @since 4.1.0
     * @hook tplFeaturedItemAdInfo
     */
    public function hookTplFeaturedItemAdInfo()
    {
        if ($GLOBALS['config']['comments_rating_module']) {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'comment' . RL_DS . 'info_navigator.tpl');
        }
    }

    /**
     * Display overall raring on the listing details page
     *
     * @since 4.1.0
     * @hook tplListingDetailsRating
     */
    public function hookTplListingDetailsRating()
    {
        if ($GLOBALS['config']['comments_rating_module'] && $GLOBALS['listing_type']['Comments_module']) {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'comment' . RL_DS . 'listing_details.tpl');
        }
    }

    /**
     * Check for listings boxes
     *
     * @since 4.1.0
     */
    public function checkBlocks()
    {
        static $initted = false;

        if ($initted) {
            return;
        }

        foreach ($GLOBALS['blocks'] as $key => $block) {
            if ($block['Plugin'] == 'listings_box' || false !== strpos($key, 'ltfb_')) {
                $this->adsBox = true;
                break;
            }
        }

        $initted = true;
    }

    /**
     * Displays the option row on the listing type management page
     *
     * @since 4.1.0
     * @hook apTplListingTypesForm
     */
    public function hookApTplListingTypesForm()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'comment' . RL_DS . 'admin' . RL_DS . 'row.tpl');
    }

    /**
     * Simulate post data
     *
     * @since 4.1.0
     * @hook apPhpListingTypesPost
     */
    public function hookApPhpListingTypesPost()
    {
        $_POST['comments_module'] = $GLOBALS['type_info']['Comments_module'];
    }

    /**
     * Validate post data on "add listing type" page
     *
     * @since 4.1.0
     * @hook apPhpListingTypesBeforeAdd
     */
    public function hookApPhpListingTypesBeforeAdd()
    {
        $GLOBALS['data']['Comments_module'] = (int) $_POST['comments_module'];
    }

    /**
     * Validate post data on "edit listing type" page
     *
     * @since 4.1.0
     * @hook apPhpListingTypesBeforeEdit
     */
    public function hookApPhpListingTypesBeforeEdit()
    {
        $GLOBALS['update_date']['fields']['Comments_module'] = (int) $_POST['comments_module'];
    }

    /**
     * @since 4.0.0
     */
    public function uninstall()
    {
        global $rlDb;

        $rlDb->dropTable('comments');
        $rlDb->dropColumnsFromTable(['comments_count', 'comments_rating'], 'listings');

        $rlDb->delete(array('Key' => 'comments_block_bottom'), 'blocks');
    }

    /** Updates */

    /**
     * @version 305
     */
    public function update305()
    {
        $GLOBALS['rlDb']->query("
            UPDATE `{db_prefix}lang_keys` SET `Value` = REPLACE(`Value`, '{username}', '{name}') 
            WHERE `Key` = 'email_templates+body+comment_email'
        ");
    }

    /**
     * @version 4.0.0
     */
    public function update400()
    {
        global $rlDb;

        $rlDb->query("
            UPDATE `{db_prefix}blocks`
            SET `Status` = 'trash', `Sticky` = '0', `Page_ID` = '25', `Position` = '1'
            WHERE `Key` = 'comments_block_bottom' LIMIT 1
        ");

        $rlDb->query("
            UPDATE `{db_prefix}blocks`
            SET `Type` = 'php'
            WHERE `Key` = 'comments_block' LIMIT 1
        ");

        // Remove legacy phrases
        $phrases = array(
            'comment_comments',
            'comment_absent',
            'config+name+comments_number_symbols_comments',
            'config+name+comments_common'
        );

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
            WHERE `Plugin` = 'comment' AND `Key` IN ('" . implode("','", $phrases) . "')"
        );

        // Fix config positions
        $config_positions = array(
            'comment_message_symbols_number' => 1,
            'comments_nav_target' => 2,
            'comments_comment_block' => 3,
            'comment_mode' => 4,
            'comments_login_access' => 5,
            'comments_per_page' => 6,
            'comment_show_time' => 7,
            'comments_posting_group' => 8,
            'comments_send_email_after_added_comment' => 9,
            'security_img_comment_captcha' => 10,
            'comment_auto_approval' => 11,
            'comments_login_post' => 12,
            'comments_own_listings' => 13,
            'comments_sidebar_block' => 14,
            'comments_select_comments_random' => 15,
            'comments_number_comments' => 16,
            'comments_rating_group' => 17,
            'comments_rating_module' => 18,
            'comments_stars_number' => 19
        );

        foreach ($config_positions as $key => $position) {
            $update_position[] = array(
                'fields' => ['Position' => $position],
                'where' => ['Key' => $key, 'Plugin' => 'comment']
            );
        }
        $rlDb->update($update_position, 'config');

        // Remove useless hooks
        $rlDb->query("DELETE FROM `{db_prefix}hooks` WHERE `Plugin` = 'comment' AND `Name` = 'boot'");

        // Remove legacy config
        $legacy_configs = array(
            'comments_number_symbols_comments',
            'comments_common'
        );
        $rlDb->query("
            DELETE FROM `{db_prefix}config`
            WHERE `Key` IN ('" . implode("','", $legacy_configs) . "')
        ");

        // Remove legacy files
        $files_to_be_removed = array(
            'static/stars.png',
            'static/style.css',
            'admin/rlCommentGrid.js',
            'static/style_responsive_42.css',
            'block_responsive_42.tpl',
            'paging_responsive_42.tpl'
        );

        foreach ($files_to_be_removed as $file) {
            unlink(RL_PLUGINS . 'comment/' . $file);
        }

        // Add indexes
        $rlDb->query("
            ALTER TABLE `{db_prefix}comments`
            ADD KEY `User_ID` (`User_ID`),
            ADD KEY `Listing_ID` (`Listing_ID`),
            ADD KEY `Status` (`Status`);
        ");
        
        // Translate ru phrases
        $languages = $GLOBALS['languages'];
        if (in_array('ru', array_keys($languages))) {
            $russianTranslation = json_decode(file_get_contents(RL_UPLOAD . 'comment/i18n/ru.json'), true);
            foreach ($russianTranslation as $phraseKey => $phraseValue) {
                if (!$rlDb->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $insert_phrase = $rlDb->fetch(
                        ['Module', 'Key', 'JS', 'Target_key', 'Plugin'],
                        [
                            'Code' => $GLOBALS['config']['lang'],
                            'Key' => $phraseKey,
                            'Plugin' => 'comment'
                        ],
                        null, 1, 'lang_keys', 'row'
                    );

                    $insert_phrase['Code'] = 'ru';
                    $insert_phrase['Value'] = $phraseValue;

                    $rlDb->insertOne($insert_phrase, 'lang_keys');
                } else {
                    $rlDb->updateOne(array(
                        'fields' => array('Value' => $phraseValue),
                        'where'  => array('Key'   => $phraseKey, 'Code' => 'ru'),
                    ), 'lang_keys');
                }
            }
        }

        $this->updateBox();
    }

    /**
     * @version 4.1.0
     */
    public function update410()
    {
        $GLOBALS['rlDb']->addColumnToTable('comments_rating', "DOUBLE NOT NULL AFTER `Date`", 'listings');
        $GLOBALS['rlDb']->query("
            UPDATE `{db_prefix}listings` AS `T1` SET `comments_rating` = (
                SELECT ROUND(SUM(`Rating`) / COUNT(*), 1)
                FROM `{db_prefix}comments`
                WHERE `Listing_ID` = `T1`.`ID` AND `Status` = 'active' AND `Rating` > 0
            ) WHERE `comments_count` > 0
        ");
        $GLOBALS['rlDb']->query('ALTER TABLE `{db_prefix}comments` ENGINE = InnoDB');
        $GLOBALS['rlDb']->addColumnToTable('Comments_module', "enum('0', '1') NOT NULL DEFAULT '1' AFTER `Status`", 'listing_types');
    }

    /*** DEPRECATED METHODS ***/

    /**
     * @deprecated 4.0.0
     **/
    public function show_tab()
    {}
}
