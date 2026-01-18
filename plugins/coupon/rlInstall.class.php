<?php
/**cpopyright**/

class rlInstall
{
    /**
     * Install plugin
     */
    public function install()
    {
        global $rlDb;

        $rlDb->createTable('coupon_code', "
            `ID` int(3) NOT NULL auto_increment,
            `Plan_ID` tinytext NOT NULL,
            `MPPlan_ID` tinytext NOT NULL,
            `BannersPlan_ID` tinytext NOT NULL,
            `PAYGCPlan_ID` tinytext NOT NULL,
            `BumpupPlan_ID` tinytext NOT NULL,
            `HighlightPlan_ID` tinytext NOT NULL,
            `Services` tinytext NOT NULL,
            `Sticky` enum('1','0') NOT NULL default '0',
            `StickyMP` enum('1','0') NOT NULL default '0',
            `StickyBanners` enum('1','0') NOT NULL default '0',
            `StickyPAYGC` enum('1','0') NOT NULL default '0',
            `StickyBumpup` enum('1','0') NOT NULL default '0',
            `StickyHighlight` enum('1','0') NOT NULL default '0',
            `Account_or_type` enum('type','account') NOT NULL default 'type',
            `Account_type` varchar(255) NOT NULL default '',
            `Username` varchar(50) NOT NULL default '',
            `Code` varchar(50) NOT NULL default '',
            `Used_date` enum('yes','no') NOT NULL default 'yes',
            `Date_release` datetime NOT NULL default '0000-00-00 00:00:00',
            `Date_from` date NOT NULL default '0000-00-00',
            `Date_to` date NOT NULL default '0000-00-00',
            `Discount` double NOT NULL default '0',
            `Type` enum('percent','cost') NOT NULL default 'percent',
            `Using_limit` int(5) NOT NULL default '0',
            `Status` enum('active','approval') NOT NULL default 'active',
            `Shopping` enum('1','0') NOT NULL default '0',
            `Booking` enum('1','0') NOT NULL default '0',
            PRIMARY KEY (`ID`)
        ");

        $rlDb->createTable('coupon_users', "
            `ID` INT(10) NOT NULL AUTO_INCREMENT ,
            `Account_ID` INT(6) NOT NULL ,
            `Coupon_ID` INT(6) NOT NULL ,
            `Plan_ID` INT(10) NOT NULL ,
            `BannersPlan_ID` INT(10) NOT NULL ,
            PRIMARY KEY (`ID`),
            KEY `Coupon_ID` (`Coupon_ID`),
            KEY `Account_ID` (`Account_ID`)
        ");

        // add field to transactions
        $rlDb->addColumnsToTable(
            array(
                'Coupon_ID' => "INT NOT NULL default '0'",
                'Coupon_data' => "VARCHAR(255) NOT NULL default ''",
            ),
            'transactions'
        );
    }

    /**
     * Uninstall plugin
     */
    public function uninstall()
    {
        global $rlDb;
        
        $rlDb->dropTables(['coupon_code', 'coupon_users']);

        $rlDb->dropColumnsFromTable(
            array(
                'Coupon_ID',
                'Coupon_data',
            ),
            'transactions'
        );
    }

    /**
     * Update to 2.5.0
     */
    public function update250()
    {
        global $rlDb;

        // add field to coupon code
        $rlDb->addColumnsToTable(
            array(
                'Shopping' => "enum('1','0') NOT NULL default '0'",
                'Booking' => "enum('1','0') NOT NULL default '0'",
            ),
            'coupon_code'
        );

        // add field to transactions
        $rlDb->addColumnsToTable(
            array(
                'Coupon_ID' => "INT NOT NULL default '0'",
                'Coupon_data' => "VARCHAR(255) NOT NULL default ''",
            ),
            'transactions'
        );

        $rlDb->query("ALTER TABLE `{db_prefix}coupon_code` MODIFY COLUMN `Type` enum('percent','cost') NOT NULL default 'percent';");
    }
}
