<?php
/**cpopyright**/

/**
 * @since 2.0.0
 */
class rlInstall
{
    /**
     * Install plugin
     */
    public function install()
    {
        global $rlDb;

        $rlDb->createTable('monetize_plans', "
            `ID` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `Key` VARCHAR(200) NOT NULL,
            `Bump_ups` INT(4) NOT NULL,
            `Days` INT(3) NOT NULL,
            `Highlights` INT(4) NOT NULL,
            `Price` VARCHAR(50) NOT NULL,
            `Color` VARCHAR(6) NOT NULL,
            `Status` ENUM('active', 'approval') DEFAULT 'active',
            `Type` ENUM('highlight', 'bumpup')
        ");

        $rlDb->createTable('monetize_using', "
            `ID` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `Account_ID` INT(11) NOT NULL,
            `Plan_ID` INT(4) NOT NULL,
            `Plan_type` ENUM('bumpup', 'highlight') DEFAULT 'bumpup',
            `Date` DATETIME NOT NULL,
            `Bumpups_available` INT(4) NOT NULL,
            `Highlights_available` INT(4) NOT NULL,
            `Days_highlight` INT(3) NOT NULL,
            `Is_unlim` ENUM('0', '1') DEFAULT '0'
        ");

        $rlDb->addColumnsToTable(array(
            'Bumped' => "ENUM('0', '1') DEFAULT '0'",
            'HighlightDate' => "DATETIME NOT NULL",
            'Highlight_Plan' => "INT(11) NOT NULL",
            'Monetize_using_id' => "INT(11) NOT NULL",
        ), 'listings');

        $rlDb->addColumnsToTable(array(
            'Bumpup_ID' => "INT(4) NOT NULL",
            'Highlight_ID' => "INT(4) NOT NULL",
        ), 'listing_plans');

        // set default pages to the Monetize block
        $sql = "SELECT GROUP_CONCAT(`ID`) AS `ids` FROM `{db_prefix}pages` WHERE `Key`='bumpup_page' OR  `Key`='highlight_page'";
        $result = $rlDb->getRow($sql);

        $updatePage = array(
            'fields' => array(
                'Page_ID' => $result['ids'],
                'Header' => 0,
                'Sticky' => 0,
            ),
            'where' => array(
                'Key' => 'monetize_listing_detail',
            ),
        );

        $rlDb->updateOne($updatePage, 'blocks');
    }

    /**
     * Uninstall plugin
     */
    public function uninstall()
    {
        global $rlDb;

        $rlDb->dropColumnsFromTable(
            array(
                'Bumped',
                'HighlightDate',
                'Highlight_Plan',
                'Monetize_using_id',
            ),
            'listings'
        );

        $rlDb->dropColumnsFromTable(
            array(
                'Bumpup_ID',
                'Highlight_ID',
            ),
            'listing_plans'
        );

        $rlDb->dropTables(['monetize_plans', 'monetize_using']);
    }

    /**
     * @since 1.1.0
     */
    public function update_110()
    {}

    /**
     * Update to 1.4.1
     */
    public function update141()
    {
        global $rlDb;

        $rlDb->dropColumnFromTable('BumpDate', 'listings');
        $rlDb->addColumnToTable('Bumped', "ENUM('0', '1') DEFAULT '0'", 'listings');

        // delete hooks
        $sql = "DELETE FROM `" . RL_DBPREFIX . "hooks` ";
        $sql .= "WHERE `Plugin` = 'monetize' AND (`Name` = 'listingsModifyGroup' ";
        $sql .= "OR `Name` = 'listingsModifyGroupSearch' OR `Name` = 'phpListingTypeTop' OR `Name` = 'beforeImport')";
        $rlDb->query($sql);
    }

    /**
     * Update to 1.3.0
     */
    public function update130()
    {
        global $rlDb;

        // change structure of database
        $rlDb->addColumnsToTable(array(
            'Credit_from' => "ENUM('flynax', 'plugin') DEFAULT 'plugin'",
        ), 'monetize_using');

        $rlDb->addColumnsToTable(array(
            'Monetize_using_id' => "INT(11) NOT NULL",
        ), 'listings');
    }

    /**
     * Update to 2.0.0
     */
    public function update200()
    {
        global $rlDb;

        $rlDb->dropColumnsFromTable([
            'Bumpups',
            'Highlight',
            'Days_highlight',
            'Credit_from'
        ], 'listing_plans');

        $hooks = 'addListingBeforeSteps,apPhpListingPlansValidate';

        // remove old hook
        $sql = "DELETE FROM `{db_prefix}hooks` WHERE FIND_IN_SET(`Name`, '{$hooks}') > 0 AND `Plugin` = 'monetize'";
        $rlDb->query($sql);

        $rlDb->addColumnsToTable(array(
            'Bumpup_ID' => "INT(4) NOT NULL",
            'Highlight_ID' => "INT(4) NOT NULL",
        ), 'listing_plans');

        // update/add ru phrases
        if (in_array('ru', array_keys($GLOBALS['languages']))) {
            $russianTranslation = json_decode(file_get_contents(RL_PLUGINS . 'monetize/i18n/ru.json'), true);
            foreach ($russianTranslation as $phraseKey => $phrase) {
                if ($rlDb->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $rlDb->updateOne([
                        'fields' => ['Value' => $phrase],
                        'where'  => ['Key'   => $phraseKey, 'Code' => 'ru'],
                    ], 'lang_keys');
                } else {
                    $rlDb->insertOne([
                        'Code'   => 'ru',
                        'Module' => 'common',
                        'Key'    => $phraseKey,
                        'Value'  => $russianTranslation[$phraseKey],
                        'Plugin' => 'monetize',
                    ], 'lang_keys');
                }
            }
        }
    }

    /**
     * Update to 2.0.1
     */
    public function update201()
    {
        global $rlDb;

        $hooks_to_be_removed = [
            'listingNavIcons',
        ];
        $rlDb->query(
            "DELETE FROM `{db_prefix}hooks`
             WHERE `Plugin` = 'monetize'
             AND `Name` IN ('" . implode("','", $hooks_to_be_removed) . "')"
        );
    }

    /**
     * Update to 2.1.0
     */
    public function update210()
    {
        global $rlDb;

        $hooks_to_be_removed = [
            'tplFooter',
        ];
        $rlDb->query(
            "DELETE FROM `{db_prefix}hooks`
             WHERE `Plugin` = 'monetize'
             AND `Name` IN ('" . implode("','", $hooks_to_be_removed) . "')"
        );

        unlink(RL_PLUGINS . 'monetize/static/listings.css');
    }
}
