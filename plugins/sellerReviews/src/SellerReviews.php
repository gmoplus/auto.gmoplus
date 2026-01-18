<?php

namespace Flynax\Plugins\SellerReviews;

use InvalidArgumentException;

/**
 * Seller Reviews/Rating class
 */
class SellerReviews
{
    /**
     * Plugin comments table
     */
    public const TABLE = 'srr_comments';

    /**
     * Plugin comments table with prefix
     */
    public const TABLE_PRX = '{db_prefix}' . self::TABLE;

    /**
     * Column in accounts table which have total count of comments per account
     */
    public const COMMENTS_COUNT_COLUMN = 'Comments_Count';

    /**
     * Column in accounts table which have total rating by comments
     */
    public const ACCOUNT_RATING_COLUMN = 'Account_Rating';

    /**
     * Path of view directory
     */
    public const VIEW_PATH = RL_PLUGINS . 'sellerReviews/view/';

    /**
     * @param $rlDb
     *
     * @return void
     */
    public function createSystemTable($rlDb): void
    {
        $statuses = "'" . implode("','", SellerComment::getCommentStatuses()) . "'";

        $rlDb->createTable(
            self::TABLE,
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
             `Account_ID` INT(11) NOT NULL,
             `Author_ID` INT(11) NOT NULL DEFAULT 0,
             `Author_Name` VARCHAR(100) CHARACTER SET utf8 NOT NULL DEFAULT '',
             `IP` VARCHAR (39) NOT NULL DEFAULT '',
             `Title` TINYTEXT CHARACTER SET utf8 NOT NULL DEFAULT '',
             `Description` MEDIUMTEXT CHARACTER SET utf8 NOT NULL DEFAULT '',
             `Rating` INT(3) NOT NULL DEFAULT 0,
             `Date` DATETIME,
             `Status` ENUM({$statuses}) NOT NULL DEFAULT '" . SellerComment::DEFAULT_STATUS . "',
             PRIMARY KEY (`ID`),
             INDEX (`Account_ID`),
             INDEX (`Author_ID`),
             INDEX (`Date`),
             INDEX (`Status`)",
            RL_DBPREFIX,
            'ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;'
        );
    }

    /**
     * @param $rlDb
     *
     * @return void
     */
    public function removeSystemTable($rlDb): void
    {
        $rlDb->dropTable(self::TABLE);
    }

    /**
     * Create triggers which will increase/decrease counts of comments in accounts table
     *
     * @param $rlDb
     *
     * @return void
     */
    public function createCountTriggers($rlDb): void
    {
        $rlDb->query('DROP TRIGGER IF EXISTS `srrInsertSellerComment`');
        $rlDb->query('DROP TRIGGER IF EXISTS `srrDeleteSellerComment`');
        $rlDb->query('DROP TRIGGER IF EXISTS `srrUpdateSellerComment`');

        $rlDb->query("
            CREATE TRIGGER `srrInsertSellerComment` AFTER INSERT ON `" . self::TABLE_PRX . "`
            FOR EACH ROW
            BEGIN
              UPDATE `{db_prefix}accounts` SET `" . self::COMMENTS_COUNT_COLUMN . "` = `" . self::COMMENTS_COUNT_COLUMN . "` + 1
              WHERE `ID` = NEW.`Account_ID` AND NEW.`Status` = '" . SellerComment::ACTIVE_STATUS . "';
              
              UPDATE `{db_prefix}accounts` AS `Accounts`
              SET `" . self::ACCOUNT_RATING_COLUMN . "` = ROUND(
                (SELECT SUM(`Rating`) FROM `" . self::TABLE_PRX . "`
                 WHERE `Account_ID` = `Accounts`.`ID`
                 AND `Status` = '" . SellerComment::ACTIVE_STATUS . "') / `Accounts`.`" . self::COMMENTS_COUNT_COLUMN . "`, 1)
              WHERE `ID` = NEW.`Account_ID` AND NEW.`Status` = '" . SellerComment::ACTIVE_STATUS . "';
            END
        ");

        $rlDb->query("
            CREATE TRIGGER `srrDeleteSellerComment` AFTER DELETE ON `" . self::TABLE_PRX . "`
            FOR EACH ROW
            BEGIN
              IF OLD.`Status` = '" . SellerComment::ACTIVE_STATUS . "' THEN
                UPDATE `{db_prefix}accounts` SET `" . self::COMMENTS_COUNT_COLUMN . "` = `" . self::COMMENTS_COUNT_COLUMN . "` - 1
                WHERE `ID` = OLD.`Account_ID`;
              END IF;

              UPDATE `{db_prefix}accounts` AS `Accounts`
              SET `" . self::ACCOUNT_RATING_COLUMN . "` = ROUND(
                (SELECT SUM(`Rating`) FROM `" . self::TABLE_PRX . "`
                 WHERE `Account_ID` = `Accounts`.`ID`
                 AND `Status` = '" . SellerComment::ACTIVE_STATUS . "') / `Accounts`.`" . self::COMMENTS_COUNT_COLUMN . "`, 1)
              WHERE `ID` = OLD.`Account_ID`;
            END
        ");

        $rlDb->query("
            CREATE TRIGGER `srrUpdateSellerComment` AFTER UPDATE ON `" . self::TABLE_PRX . "`
            FOR EACH ROW
            BEGIN
              IF OLD.`Status` <> NEW.`Status` AND NEW.`Status` = '" . SellerComment::ACTIVE_STATUS . "' THEN  
                UPDATE `{db_prefix}accounts` SET `" . self::COMMENTS_COUNT_COLUMN . "` = `" . self::COMMENTS_COUNT_COLUMN . "` + 1
                WHERE `ID` = NEW.`Account_ID`;
              END IF;

              IF OLD.`Status` = '" . SellerComment::ACTIVE_STATUS . "' AND NEW.`Status` = '" . SellerComment::APPROVAL_STATUS . "' THEN
                UPDATE `{db_prefix}accounts` SET `" . self::COMMENTS_COUNT_COLUMN . "` = `" . self::COMMENTS_COUNT_COLUMN . "` - 1
                WHERE `ID` = NEW.`Account_ID`;
              END IF;
              
              UPDATE `{db_prefix}accounts` AS `Accounts`
              SET `" . self::ACCOUNT_RATING_COLUMN . "` = IF (`Accounts`.`" . self::COMMENTS_COUNT_COLUMN . "`, ROUND(
                (SELECT SUM(`Rating`) FROM `" . self::TABLE_PRX . "`
                 WHERE `Account_ID` = `Accounts`.`ID`
                 AND `Status` = '" . SellerComment::ACTIVE_STATUS . "') / `Accounts`.`" . self::COMMENTS_COUNT_COLUMN . "`, 1), 0)
              WHERE `ID` = NEW.`Account_ID`;
            END
        ");
    }

    /**
     * @param $rlDb
     *
     * @return void
     */
    public function addCommentsCountColumn($rlDb): void
    {
        $rlDb->addColumnToTable(self::COMMENTS_COUNT_COLUMN, "INT(6) NOT NULL AFTER `Status`", 'accounts');
    }

    /**
     * @param $rlDb
     *
     * @return void
     */
    public function dropCommentsCountColumn($rlDb): void
    {
        $rlDb->dropColumnFromTable(self::COMMENTS_COUNT_COLUMN, 'accounts');
    }

    /**
     * @param $rlDb
     *
     * @return void
     */
    public function addAccountRatingColumn($rlDb): void
    {
        $rlDb->addColumnToTable(self::ACCOUNT_RATING_COLUMN, "DECIMAL(3,1) NOT NULL DEFAULT 0 AFTER `Status`", 'accounts');
    }

    /**
     * @param $rlDb
     *
     * @return void
     */
    public function dropAccountRatingColumn($rlDb): void
    {
        $rlDb->dropColumnFromTable(self::ACCOUNT_RATING_COLUMN, 'accounts');
    }

    /**
     * @param object $smarty
     * @param string $fileName
     * @param bool   $isFetch
     *
     * @return void|string
     */
    public static function view(object $smarty, string $fileName, bool $isFetch = false)
    {
        if (!file_exists($file = self::VIEW_PATH . $fileName . '.tpl')) {
            throw new \RuntimeException('Error. View file does not exist: ' . $file);
        }

        if ($isFetch) {
            return $smarty->fetch($file);
        }

        $smarty->display($file);
    }

    /**
     * Checking correct ajax request
     * @param $mode
     * @return bool
     */
    public function isValidAjax($mode): bool
    {
        return in_array($mode, ['srrGetComments', 'srrAddNewComment', 'srrLoadAccountRating']);
    }

    /**
     * @param $account
     * @param $smarty
     *
     * @return void
     */
    public static function assignAccountInfo($account, $smarty): void
    {
        $smarty->assign('srrAccountInfo', [
            'Account_ID'       => $account['ID'],
            'Rating'           => $account[self::ACCOUNT_RATING_COLUMN],
            'Comments_Count'   => $account[self::COMMENTS_COUNT_COLUMN],
            'Personal_address' => $account['Personal_address'],
        ]);
    }

    /**
     * @param $id
     * @param $rlDb
     *
     * @return bool
     */
    public static function removeSellerReviews($id, $rlDb): bool
    {
        if (!($id = (int) $id)) {
            return false;
        }

        // Remove reviews of this deleted seller
        $rlDb->delete(['Account_ID' => $id], self::TABLE, null, 0);

        // Remove reviews which created by this seller
        $rlDb->delete(['Author_ID' => $id], self::TABLE, null, 0);

        return true;
    }

    /**
     * Checks existing reviews of dealer from current user/visitor
     *
     * @since 1.1.0
     *
     * @param int    $accountID
     * @param object $rlDb
     * @param int    $authorID
     * @param string $ip
     *
     * @return bool
     */
    public static function isReviewExists(int $accountID, object $rlDb, int $authorID = 0, string $ip = ''): bool
    {
        if (!$authorID && !$ip) {
            throw new InvalidArgumentException('Error. Author ID or IP parameter must be specified.');
        }

        $sql = "SELECT COUNT(*) FROM `" . self::TABLE_PRX . "` ";
        $sql .= "WHERE `Account_ID` = {$accountID} ";
        $sql .= $authorID ? "AND `Author_ID` = {$authorID}" : "AND `Author_ID` = 0 AND `IP` = '{$ip}' ";
        return (int) $rlDb->getRow($sql, 'COUNT(*)') > 0;
    }
}
