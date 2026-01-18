<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLXMLEXPORT.CLASS.PHP
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2025 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

use Flynax\Utils\ListingMedia;

/**
 * XML export class
 */
class rlXmlExport
{
    /**
     * Format info
     *
     * @var array
     */
    private $format_info = [];

    /**
     * Constructor
     *
     * @param array $format
     */
    public function __construct(array $format = [])
    {
        global $reefless, $rlXmlMapping;

        $this->format_info = $format;

        $reefless->loadClass('Listings');
        $reefless->loadClass('ListingTypes');
        $reefless->loadClass('Cache');
        $reefless->loadClass('Valid');
        $reefless->loadClass('XmlMapping', null, 'xmlFeeds');

        $GLOBALS['rlCommon']->getInstalledPluginsList();

        $rlXmlMapping->format = $format['Key'];
        $rlXmlMapping->loadBasicMapping('export');
        $rlXmlMapping->getMultifieldRelatedFileds();
    }

    /**
     * Exports listings data to an XML file.
     *
     * @since 3.6.1 Removed $order, $type parameters
     *
     * @param resource $fp          The file pointer resource where the XML data will be written.
     * @param array    $where       The SQL WHERE clause conditions for fetching listings.
     * @param int      $total_limit The maximum number of listings to export.
     */
    public function export($fp, ?array $where = [], ?int $total_limit = 100): void
    {
        if (!$this->format_info) {
            return;
        }

        $xml = '<?xml version="1.0" encoding="utf-8" ?>';

        $xpath = $this->format_info['Xpath'] ? explode('/', $this->format_info['Xpath']) : [];
        if (count($xpath) == 1) {
            $xpath[1] = $xpath[0];
            $xpath[0] = 'data';
        }
        $last = current(array_splice($xpath, -1, 1));

        foreach ($xpath as $xp) {
            $xml .= '<' . $xp . '>';
        }
        fwrite($fp, $xml);
        unset($xml);

        $start = 0;
        $limit = 100;

        if ($total_limit < $limit) {
            $limit = $total_limit;
        }

        while ($listings = $this->getListings($where, $start, $limit)) {
            foreach ($listings as $listing) {
                $xml = $this->exportListing($listing, $last);
                fwrite($fp, $xml);
            }

            $start = $start + $limit;
            if ($start >= $total_limit) {
                break;
            }
        }

        $xml = '';
        foreach (array_reverse($xpath) as $xp) {
            $xml .= '</' . $xp . '>';
        }
        fwrite($fp, $xml);
    }

    /**
     * Exports a single listing to XML format.
     *
     * @param  array  $listing The listing data to be exported.
     * @param  string $last    The last segment of the XPath for the listing node.
     * @return string          The XML representation of the listing.
     */
    private function exportListing($listing, $last)
    {
        global $rlXmlMapping, $rlDb;

        $advert = [];
        static $cats_out = [];

        foreach ($rlXmlMapping->mapping as $flynax_field => $xml_field) {
            if (strpos($xml_field, '@') > 0) {
                continue;
            }

            if (strpos($xml_field . '_', $last)) {
                $xml_field = substr($xml_field, strlen($last) + 1);
            }

            if ($flynax_field == 'xml_ref') {
                $advert[$xml_field] = $listing[$flynax_field] ?: $listing['ID'];
            } elseif (preg_match('#sys_category_[0-9]#', $flynax_field)) {
                if (!$cats_out[$flynax_field]) {
                    $sql = "SELECT `Key`, `Level` FROM `{db_prefix}categories` ";
                    $sql .= "WHERE `ID` = " . $listing['Category_ID'];
                    $sql .= " OR FIND_IN_SET(`ID`, ";
                    $sql .= "(SELECT `Parent_IDs` FROM `{db_prefix}categories` WHERE `ID` = {$listing['Category_ID']}))";

                    $keys = $rlDb->getAll($sql);
                    $sql = "SELECT `Key`, `Value` FROM `{db_prefix}lang_keys` WHERE (";
                    foreach ($keys as $v) {
                        $levels[$v['Key']] = $v['Level'];
                        $sql .= "`Key` = 'categories+name+" . $v['Key'] . "' OR ";
                    }
                    $sql = substr($sql, 0, -3);
                    $sql .= ") AND `Code` = '{$GLOBALS['config']['lang']}'";
                    $names = $rlDb->getAll($sql);

                    foreach ($names as $value) {
                        $cat_key = str_replace('categories+name+', '', $value['Key']);
                        $cats_out['sys_category_' . $levels[$cat_key]] = $value['Value'];
                    }
                }

                $advert[$xml_field] = $cats_out[$flynax_field];
            } elseif ($flynax_field == 'sys_photos') {
                foreach ((array) ListingMedia::get($listing['ID']) as $photo) {
                    if ($photo['Type'] == 'picture') {
                        $advert['photos'][]['photo'] = $photo['Photo'];
                    }
                }
            } elseif ($flynax_field == 'sys_loc_latitude' || $flynax_field == 'sys_loc_longitude') {
                $flynax_field = $flynax_field == 'sys_loc_latitude' ? 'Loc_latitude' : 'Loc_longitude';
                $advert[$xml_field] = $listing[$flynax_field];
            } elseif ($flynax_field == 'sys_dealer_id') {
                $advert[$xml_field] = $listing['Username'];
            } elseif ($flynax_field == 'xml_back_url') {
                $advert[$xml_field] = $GLOBALS['reefless']->getListingUrl($listing);
            } elseif ($listing[$flynax_field]) {
                $field_info = $rlXmlMapping->fields_info[$flynax_field];

                switch ($field_info['Type']) {
                    case 'price':
                        $advert[$xml_field] = preg_replace("/\|.*$/", "", $listing[$flynax_field]);
                        break;
                    case 'select':
                    case 'radio':
                        if ($field_info['Condition'] == 'years') {
                            $advert[$xml_field] = $listing[$flynax_field];
                        } else if ($field_info['Condition']) {
                            if (in_array($flynax_field, $rlXmlMapping->multiFormatKeys)) {
                                if ($rlXmlMapping->isNewMultiField) {
                                    $GLOBALS['rlMultiField']->getNames($listing[$flynax_field]);
                                    $advert[$xml_field] = $GLOBALS['lang']['data_formats+name+' . $listing[$flynax_field]];
                                } else {
                                    $advert[$xml_field] = $rlDb->getOne("Value", "`Key` ='data_formats+name+" . $listing[$flynax_field] . "'", "lang_keys");
                                }
                            } else {
                                $advert[$xml_field] = $rlXmlMapping->data_formats_mapping[$field_info['Condition']][$listing[$flynax_field]];
                            }
                        } else {
                            $advert[$xml_field] = $rlXmlMapping->listing_fields_mapping[$flynax_field][$listing[$flynax_field]];
                        }
                        break;
                    case 'checkbox':
                        $advert[$xml_field] = $this->adaptFeatures($field_info, $listing[$flynax_field], ',', $rlXmlMapping->listing_fields_mapping[$flynax_field]);
                        break;
                    case 'mixed':
                        $tval = explode("|", $listing[$flynax_field]);
                        $advert[$xml_field] = $tval[0];
                        break;
                    default:
                        $advert[$xml_field] = $listing[$flynax_field];
                        break;
                }
            }
        }

        $data[$last] = $advert;

        $xml = $this->toXML($data);
        $xml = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $xml); //replace & with &amp;

        return $xml;
    }

    /**
     * Get listings
     *
     * @since 3.6.1 Removed $order, $type parameters
     *
     * @param array $where Conditions array
     * @param int   $start Start
     * @param int   $limit Limit
     *
     * @return array
     */
    public function getListings(?array $where = [], ?int $start = 0, ?int $limit = 100): array
    {
        global $rlDb, $rlListings, $reefless, $rlLang, $rlHook;

        $start = $start ?: 0;
        $limit = $limit ?: 100;

        $sql = "SELECT `T1`.*, `T3`.`Path` AS `Path`, `T3`.`Type` AS `Listing_type`, `T3`.`Path` as `Category_path`, `T7`.`Username`, ";
        $sql .= "IF(`T1`.`Featured_date`, '1', '0') `Featured`, `T3`.`Parent_IDs` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T7` ON `T1`.`Account_ID` = `T7`.`ID` ";
        $sql .= "WHERE `T1`.`Status` = 'active' ";

        /**
         * @since 3.6.1 - Added $sql parameter
         */
        $rlHook->load('listingsModifyWhere', $sql);

        if ($where['account_id']) {
            $sql .= "AND `T1`.`Account_ID` = {$where['account_id']} ";
        }

        if ($where['listing_type']) {
            $sql .= "AND `T3`.`Type` = '{$where['listing_type']}' ";
        }

        $sql .= "ORDER BY `T1`.`Date` DESC ";
        $sql .= "LIMIT {$start}, {$limit} ";

        $listings = $rlDb->getAll($sql);
        $listings = $rlLang->replaceLangKeys($listings, 'categories', 'name');

        foreach ($listings as &$listing) {
            $listing['listing_title'] = $rlListings->getListingTitle($listing['Category_ID'], $listing, $listing['Listing_type'], null, $listing['Parent_IDs']);
            $listing['url'] = $reefless->getListingUrl($listing);
        }

        return $listings;
    }

    /**
     * Adapt features
     *
     * @param  array  $field_key  Field info
     * @param  string $features   String containing set of items
     * @param  string $delimiter  Delimiter sign
     *
     * @return string             String containing set of adapted to flynax field values
     */
    public function adaptFeatures($field_info, $features, $delimiter = ',')
    {
        global $rlXmlMapping;

        $data = explode($delimiter, $features);

        $source_mapping = $field_info['Condition']
            ? $rlXmlMapping->data_formats_mapping[$field_info['Condition']]
            : $rlXmlMapping->listing_fields_mapping[$field_info['Key']];

        $out = '';
        foreach ($data as $item) {
            if ($map_item = $source_mapping[$item]) {
                $out .= $map_item . $delimiter;
            }
        }

        $out = trim($out, $delimiter);

        return $out;
    }

    /**
     * Convert array to XML
     *
     * @param array $array - Data array
     */
    public function toXML($array)
    {
        $out = '';
        foreach ($array as $key => $value) {
            if (is_int($key)) {
            } else {
                $out .= '<' . $key;
                if (is_array($value) && is_array($value['@attributes'])) {
                    foreach ($value['@attributes'] as $ak => $av) {
                        $out .= ' ' . $ak . '="' . $av . '"';
                    }
                    unset($value['@attributes']);
                }
                $out .= '>';
            }

            if (is_array($value)) {
                $out .= $this->toXml($value);
            } else {
                $out .= '<![CDATA[' . $value . ']]>';
            }

            if (is_int($key)) {
            } else {
                $out .= '</' . $key . '>';
            }
        }

        return $out;
    }
}
