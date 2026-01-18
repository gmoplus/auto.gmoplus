<?php


/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : DATABASEHANDLER.PHP
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

namespace Flynax\Plugins\ImportExportCategories;

use Flynax\Utils\Valid;

/**
 * Class DatabaseHandler
 * @since 3.0.0
 */
class DatabaseHandler
{
    /**
     * Insert several rows with one request into the Database
     *
     * @param object $db
     * @param array  $data
     * @param string $table
     *
     * @return bool
     */
    public static function insert(object $db, array $data, string $table): bool
    {
        $sql = "INSERT INTO `{db_prefix}{$table}` (";
        foreach ($data[0] as $field => $value) {
            $sql .= "`{$field}`, ";
        }
        $sql = substr($sql, 0, -2);
        $sql .= ') VALUES ' . PHP_EOL;

        foreach ($data as $insert) {
            $sql .= '(';
            foreach ($insert as $value) {
                Valid::escape($value, true);
                $sql .= "'{$value}', ";
            }
            $sql = substr($sql, 0, -2) . '),' . PHP_EOL;
        }
        $sql = substr($sql, 0, -2) . ';';

        return $db->query($sql);
    }
}
