<?php

namespace Flynax\Plugins\SellerReviews;

use Flynax\Utils\Util;
use Flynax\Utils\Valid;
use RuntimeException;

/**
 * Seller comment class
 */
class SellerComment
{
    /**
     * Active status of comment
     */
    public const ACTIVE_STATUS = 'active';

    /**
     * Approval status of comment
     */
    public const APPROVAL_STATUS = 'approval';

    /**
     * Pending status of comment
     */
    public const PENDING_STATUS = 'pending';

    /**
     * Default status of new comment
     */
    public const DEFAULT_STATUS = self::ACTIVE_STATUS;

    /**
     * @var
     */
    protected $reefless;

    /**
     * @var
     */
    protected $rlDb;

    /**
     * @var
     */
    protected $rlAccount;

    /**
     * @var
     */
    protected $rlSmarty;

    /**
     * @var
     */
    protected $rlLang;

    /**
     * @var
     */
    protected $config;

    /**
     * @var
     */
    protected $lang;

    /**
     * @var
     */
    public $commentsCount;

    /**
     * @var array
     */
    protected static $statuses = [self::ACTIVE_STATUS, self::APPROVAL_STATUS, self::PENDING_STATUS];

    /**
     * @since 1.1.0
     * @var
     */
    protected $accountInfo;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->reefless     = &$GLOBALS['reefless'];
        $this->rlDb        = &$GLOBALS['rlDb'];
        $this->rlLang      = &$GLOBALS['rlLang'];
        $this->config       = &$GLOBALS['config'];
        $this->lang        = &$GLOBALS['lang'];
        $this->accountInfo = &$GLOBALS['account_info'];

        if (!$GLOBALS['rlAccount']) {
            $this->reefless->loadClass('Account');
        }
        $this->rlAccount = &$GLOBALS['rlAccount'];

        if (!$GLOBALS['rlSmarty']) {
            require_once RL_LIBS . 'smarty/Smarty.class.php';
            $this->reefless->loadClass('Smarty');
        }
        $this->rlSmarty = &$GLOBALS['rlSmarty'];
    }

    /**
     * Get list of comments
     *
     * @since 1.1.0 - Added $sorting parameter
     *
     * @param int   $page
     * @param int   $limit
     * @param array $filters
     * @param array $sorting
     *
     * @return array
     */
    public function getComments(int $page = 0, int $limit = 0, array $filters = [], array $sorting = []): array
    {
        $limit          = (int) ($limit ?? $this->config['srr_per_page']);
        $start          = $page > 1 ? ($page - 1) * $limit : 0;
        $isJoinAccounts = $filters['Account'] || $sorting['field'] === 'Account';
        $isJoinAuthors  = $filters['Author'] || $sorting['field'] === 'Author';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS `Comments`.*';

        if ($sorting['field']) {
            switch ($sorting['field']) {
                case 'Author':
                    $sorting['field'] = '`Author_Full_Name`';

                    $sql .= ", IF (`Comments`.`Author_ID`, ";
                    $sql .= "IF(`Authors`.`Last_name` <> '' AND `Authors`.`First_name` <> '', ";
                    $sql .= "CONCAT(`Authors`.`First_name`, ' ', `Authors`.`Last_name`), `Authors`.`Username`), ";
                    $sql .= "`Comments`.`Author_Name`) AS `Author_Full_Name`";
                    break;
                case 'Account':
                    $sorting['field'] = '`Account_Full_Name`';

                    $sql .= ", IF(`Accounts`.`Last_name` <> '' AND `Accounts`.`First_name` <> '', ";
                    $sql .= "CONCAT(`Accounts`.`First_name`, ' ', `Accounts`.`Last_name`), `Accounts`.`Username`) AS `Account_Full_Name`";
                    break;
                default:
                    $sorting['field'] = "`Comments`.`{$sorting['field']}`";
                    break;
            }
        }

        $sql .= " FROM `" . SellerReviews::TABLE_PRX . "` AS `Comments` ";

        if ($isJoinAccounts) {
            $sql .= "LEFT JOIN `{db_prefix}accounts` AS `Accounts` ON `Comments`.`Account_ID` = `Accounts`.`ID` ";
        }

        if ($isJoinAuthors) {
            $sql .= "LEFT JOIN `{db_prefix}accounts` AS `Authors` ON `Comments`.`Author_ID` = `Authors`.`ID` ";
        }

        if ($filters) {
            $sql .= 'WHERE 1 ';
            foreach ($filters as $filterKey => $filter) {
                if (empty($filter)) {
                    continue;
                }

                switch ($filterKey) {
                    case 'Account_ID':
                        $sql .= "AND `Comments`.`Account_ID` = {$filter} ";
                        break;
                    case 'Account':
                        $sql .= "AND `Accounts`.`Username` = '{$filter}' ";
                        break;
                    case 'Author':
                        $sql .= "AND `Authors`.`Username` = '{$filter}' ";
                        break;
                    case 'Status':
                        if (in_array($filter, self::getCommentStatuses(), true)) {
                            $sql .= "AND `Comments`.`Status` = '{$filter}'";
                        }
                        break;
                    case 'Date_from':
                    case 'Date_to':
                        $condition = $filterKey === 'Date_from' ? '>=' : '<=';
                        $sql .= "AND UNIX_TIMESTAMP(DATE(`Comments`.`Date`)) {$condition} UNIX_TIMESTAMP('{$filter}') ";
                        break;
                    case 'Rating':
                        $filter = (int) $filter;
                        $sql .= "AND `Comments`.`Rating` = {$filter} ";
                }
            }
        }

        $sql .= 'ORDER BY ';
        if ($sorting['field'] && $sorting['direction']) {
            $sql .= "{$sorting['field']} {$sorting['direction']} ";
        } else {
            $sql .= "`Comments`.`ID` DESC ";
        }

        $sql .= "LIMIT {$start}, {$limit}";
        $comments = $this->rlDb->getAll($sql);

        $this->setCommentsCount((int) $this->rlDb->getRow('SELECT FOUND_ROWS()')['FOUND_ROWS()']);

        foreach ($comments as &$comment) {
            $comment['Account'] = $this->rlAccount->getProfile((int) $comment['Account_ID']);

            if ($comment['Author_ID']) {
                $comment['Author'] = $this->rlAccount->getProfile((int) $comment['Author_ID']);
            } else {
                $comment['Author'] = $comment['Author_Name'];
            }
            $comment['Status'] = $this->lang[$comment['Status']];
        }

        return $comments;
    }

    /**
     * Get comments for grid manager in admin panel
     * @return void
     */
    public function apGetExtJsComments(): void
    {
        $start    = (int) $_GET['start'];
        $limit    = (int) $_GET['limit'];
        $page     = ($start / $limit) + 1;
        $filters   = [];
        $sorting  = [];

        if ($_GET['srr_search']) {
            $filters['Account']   = Valid::escape($_GET['Account']);
            $filters['Author']    = Valid::escape($_GET['Author']);
            $filters['Status']    = Valid::escape($_GET['Status']);
            $filters['Date_from'] = Valid::escape($_GET['Date_from']);
            $filters['Date_to']   = Valid::escape($_GET['Date_to']);
        }

        if ($_GET['sort']) {
            $sorting = [
                'field'      => Valid::escape($_GET['sort']),
                'direction' => Valid::escape($_GET['dir'])
            ];
        }

        $comments = $this->getComments($page, $limit, $filters, $sorting);

        echo json_encode(['total' => $this->getCommentsCount(), 'data' => $comments]);
    }

    /**
     * @return mixed
     */
    public function getCommentsCount()
    {
        return $this->commentsCount;
    }

    /**
     * @param mixed $commentsCount
     */
    public function setCommentsCount($commentsCount): void
    {
        $this->commentsCount = $commentsCount;
    }

    /**
     * Update status of comment
     *
     * @param int    $id
     * @param string $status
     *
     * @return void
     */
    public function updateCommentStatus(int $id, string $status): void
    {
        if (!$id || !in_array($status, self::getCommentStatuses(), true)) {
            throw new RuntimeException('Error: Not allowed parameters in comment update.');
        }

        $this->rlDb->updateOne([
            'fields' => ['Status' => $status],
            'where' => ['ID' => $id]
        ], SellerReviews::TABLE);
    }

    /**
     * Delete comment from list
     *
     * @param int $id
     *
     * @return bool
     */
    public function removeComment(int $id): bool
    {
        return $this->rlDb->delete(['ID' => $id], SellerReviews::TABLE);
    }

    /**
     * @return array
     */
    public static function getCommentStatuses(): array
    {
        return self::$statuses;
    }

    /**
     * Get info about comment
     *
     * @param int $commentID
     *
     * @return array
     */
    public function getCommentInfo(int $commentID): array
    {
        $comment = (array) $this->rlDb->fetch('*', ['ID' => $commentID], null, 1, SellerReviews::TABLE, 'row');

        if ($comment) {
            $comment['Account'] = $this->rlAccount->getProfile((int) $comment['Account_ID']);
            $comment['Author']  = $comment['Author_ID']
                ? $this->rlAccount->getProfile((int) $comment['Author_ID'])
                : $comment['Author_Name'];
        }

        return $comment;
    }

    /**
     * Update data of comment
     *
     * @param int   $id
     * @param array $data Required fields: title, description, status
     *
     * @return bool
     */
    public function updateComment(int $id, array $data): bool
    {
        $title       = Valid::escape($data['title']);
        $description = Valid::escape($data['description']);
        $status      = Valid::escape($data['status']);

        if (!$title || !$description || !in_array($status, self::getCommentStatuses(), true)) {
            throw new RuntimeException('Error. Not a valid title or description or status of comment.');
        }

        return $this->rlDb->updateOne([
            'fields' => [
                'Title'       => $title,
                'Description' => $description,
                'Status'      => $status
            ],
            'where' => ['ID' => $id]
        ], SellerReviews::TABLE);
    }

    /**
     * @param array $request
     *
     * @return array
     */
    public function loadCommentsInPage(array $request): array
    {
        if (!($accountID = (int) $request['accountID']) || !($page = (int) $request['page'])) {
            return ['status' => 'ERROR'];
        }

        $filters = ['Account_ID' => $accountID, 'Status' => self::ACTIVE_STATUS];
        if ($_REQUEST['filters']) {
            $filters += $_REQUEST['filters'];
        }

        $comments    = $this->getComments($page, $this->config['srr_per_page'], $filters);
        $jsonFilters = isset($_REQUEST['filters']) ? "JSON.parse('" . htmlentities(json_encode($_REQUEST['filters'])) . "')" : '';
        $pagination  = [
            'calc'     => $this->commentsCount,
            'total'    => count($comments),
            'current'  => $page,
            'per_page' => $this->config['srr_per_page'],
            'pages'    => ceil($this->commentsCount / (int) $this->config['srr_per_page']),
            'first_url' => "javascript: sellerReviews.loadComments(1, {$jsonFilters});",
            'tpl_url'  => "javascript: sellerReviews.loadComments('[pg]', {$jsonFilters});",
        ];
        $paginationTpl = FL_TPL_COMPONENT_DIR . 'pagination/pagination.tpl';
        $oldPagination = false;

        /**
         * @todo - Remove custom pagination when compatibility will >= 4.9.0
         */
        if (false === file_exists($paginationTpl)) {
            $paginationTpl = SellerReviews::VIEW_PATH . 'pagination.tpl';
            $oldPagination = true;
        }

        $countByRatings = $this->rlDb->getAll(
            "SELECT `Rating`, COUNT(`Rating`) AS `Count`
             FROM `" . SellerReviews::TABLE_PRX . "`
             WHERE `Account_ID` = {$accountID} AND `Status` = '" . self::ACTIVE_STATUS . "'
             GROUP BY `Rating`
             ORDER BY `Rating` DESC",
            ['Rating', 'Count']
        );

        $this->rlSmarty->assign('pagination', $pagination);
        $this->rlSmarty->assign('lang', $this->lang);
        $this->rlSmarty->assign('side_bar_exists', true);
        $this->rlSmarty->assign('srrComments', $comments);
        $this->rlSmarty->assign('srrCountByRatings', $countByRatings);
        $this->rlSmarty->assign('account_info', $_SESSION['account'] ?? []);

        if ($this->config['srr_one_comment_only']) {
            $this->rlSmarty->assign(
                'srrIsReviewExists',
                SellerReviews::isReviewExists($accountID, $this->rlDb, $this->accountInfo['ID'] ?? 0, Util::getClientIP())
            );
        }

        SellerReviews::assignAccountInfo($this->rlAccount->getProfile($accountID), $this->rlSmarty);

        return [
            'status'         => 'OK',
            'commentsHtml'   => SellerReviews::view($this->rlSmarty, $jsonFilters ? 'comments-list' : 'comments', true),
            'paginationHTML' => $pagination['pages'] > 1 ? $this->rlSmarty->fetch($paginationTpl) : '',
            'commentsCount'  => $this->commentsCount,
            'oldPagination'  => $oldPagination
        ];
    }

    /**
     * Add new comment to database
     *
     * @param array $request
     *
     * @return array
     */
    public function addComment(array $request): array
    {
        $accountID    = (int) $request['accountID'];
        $author       = $request['author'];
        $authorID     = (int) $request['authorID'];
        $title        = $request['title'];
        $message      = $request['message'];
        $star         = (int) $request['star'];
        $securityCode = $request['securityCode'];
        $ip           = Util::getClientIP();

        $errors       = [];
        $errorsFields = [];

        if (empty($author)) {
            $errors[] = str_replace('{field}', "<b>{$this->lang['comment_author']}</b>", $this->lang['notice_field_empty']);
            $errorsFields[] = '#srr_author';
        }

        if (empty($title)) {
            $errors[] = str_replace('{field}', "<b>{$this->lang['comment_title']}</b>", $this->lang['notice_field_empty']);
            $errorsFields[] = '#srr_title';
        }

        if (empty($message)) {
            $errors[] = str_replace('{field}', "<b>{$this->lang['message']}</b>", $this->lang['notice_field_empty']);
            $errorsFields[] = '#srr_message';
        }

        if ($this->config['srr_captcha'] && ($securityCode != $_SESSION['ses_security_code_srr'] || !$securityCode)) {
            $errors[] = $this->lang['security_code_incorrect'];
            $errorsFields[] = '#srr_security_code';
        }

        if ($this->config['srr_rating_module'] && empty($star)) {
            $errors[] = $this->getSystem('srr_rating_empty');
        }

        if ($this->config['srr_one_comment_only']
            && SellerReviews::isReviewExists($accountID, $this->rlDb, $authorID, $ip)
        ) {
            return [];
        }

        if ($errors) {
            $errorsContent = '<ul>';
            foreach ($errors as $error) {
                $errorsContent .= "<li>{$error}</li>";
            }
            $errorsContent .= '</ul>';

            return ['status' => 'ERROR', 'errors' => $errorsContent, 'errorsFields' => $errorsFields];
        }

        $message = strip_tags($message, '<a>');
        $message = preg_replace('/<a\s+(title="[^"]+"\s+)?href=["\']([^"\']+)["\'][^\>]*>[^<]+<\/a>/mi', '$2', $message);

        $this->rlDb->insertOne([
            'Account_ID'  => $accountID,
            'Author_ID'   => $authorID,
            'Author_Name' => $author,
            'IP'          => $ip,
            'Title'       => $title,
            'Description' => $message,
            'Rating'      => $star,
            'Date'        => 'NOW()',
            'Status'      => $this->config['srr_auto_approval'] ? 'active' : 'pending',
        ], SellerReviews::TABLE);

        if ($this->config['srr_send_email_after_added_comment']) {
            $this->reefless->loadClass('Mail');

            $account = $this->rlAccount->getProfile($accountID);
            $mailTpl = $GLOBALS['rlMail']->getEmailTemplate('srr_comment_email', $account['Lang']);
            $message = nl2br($message);

            $mailTpl['body'] = str_replace(
                ['{seller}', '{author}', '{title}', '{message}'],
                [$account['Full_name'], $author, $title, $message],
                $mailTpl['body']
            );
            $GLOBALS['rlMail']->send($mailTpl, $account['Mail']);
        }

        return [
            'status'  => 'OK',
            'message' => $this->getSystem($this->config['srr_auto_approval']
                ? 'srr_notice_comment_added'
                : 'srr_notice_comment_added_approval'
            )
        ];
    }

    /**
     * @todo - Remove this method when compatibility will be >= 4.8.1
     *
     * @param $key
     *
     * @return mixed
     */
    public function getSystem($key)
    {
        if (method_exists($this->rlLang, 'getSystem')) {
            return $this->rlLang->getSystem($key);
        }

        return $this->rlLang->getPhrase($key, null, null, true);
    }

    /**
     * @param array $request
     *
     * @return array
     */
    public function getAccountRating(array $request): array
    {
        if (!($accountID = (int) $request['accountID'])) {
            return ['status' => 'ERROR'];
        }

        if (!($account = $this->rlAccount->getProfile($accountID))) {
            return ['status' => 'ERROR'];
        }

        $commentsCountText = str_replace(
            '{count}',
            $account[SellerReviews::COMMENTS_COUNT_COLUMN],
            $this->rlLang->getPhrase('srr_comments_count', null, null, true)
        );

        if ($this->config['srr_display_mode'] === 'tab') {
            $commentsCountLink = $account['Personal_address'];
            $commentsCountLink .= '#srr_comments_tab';
        } else {
            $commentsCountLink = 'javascript: sellerReviews.loadCommentsInPopup()';
        }

        return [
            'status'            => 'OK',
            'Rating'            => $account[SellerReviews::ACCOUNT_RATING_COLUMN],
            'commentsCountText' => $commentsCountText,
            'commentsCountLink' => $commentsCountLink,
        ];
    }
}
