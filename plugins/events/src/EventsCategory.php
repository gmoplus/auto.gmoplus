<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: EVENTSCATEGORY.PHP
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

namespace Flynax\Plugins\Events;

class EventsCategory
{
    /**
     * @var \rlDb
     */
    protected $rlDb;

    /**
     * @var \Flynax\Plugins\Events\EventsListingTypesController
     */
    private $eventsListingType;

    /**
     * EventsCategory constructor.
     */
    public function __construct()
    {
        $this->rlDb = eventsContainerMake('rlDb');
        $this->rlValid = eventsContainerMake('rlValid');
        $this->eventsListingType = new EventsListingTypesController();
    }

    /**
     * Add categories by name
     *
     * @param array $category_names
     */
    public function addCategories($category_names = array())
    {

        $eventTypeKey = $this->eventsListingType->getKey();
        if ($eventTypeKey && $category_names) {
            if ($this->rlDb->getOne('ID', "`Type` = '{$eventTypeKey}'", 'categories')) {
                return;
            }

            $categories = [];

            $allLangs = $GLOBALS['languages'];
            $langKeys = array();

            $parentID = 0;

            // build tree
            $sql = "SELECT MAX(`Position`) AS `max` FROM `{db_prefix}categories` WHERE `Parent_ID` = {$parentID}";
            $position = $this->rlDb->getRow($sql);
            $position = $position['max'] + 1;

            foreach ($category_names as $names) {
                $name = $names[RL_LANG_CODE] ? $names[RL_LANG_CODE] : $names['en'];
                $catKey = $this->buildCategoryKey($name);
                $path = $this->buildCategoryPath($catKey);

                $categories[] = array(
                    'Key' => $catKey,
                    'Path' => $path,
                    'Status' => 'active',
                    'Lock' => '0',
                    'Type' => $eventTypeKey,
                    'Parent_ID' => $parentID,
                    'Parent_IDs' => null,
                    'Parent_keys' => null,
                    'Position' => $position,
                    'Level' => 0,
                    'Modified' => 'NOW()',
                    'Tree' => $position,
                );

                $position++;

                foreach ($allLangs as $key => $value) {
                    $langCode = $value['Code'];
                    $catName = $names[$langCode] ? $names[$langCode] : $names['en'];

                    $langKeys[] = $this->eventsListingType->prepareLangArray(
                        $langCode,
                        "categories+name+{$catKey}",
                        $catName,
                        'category'
                    );
                }
            }

            if ($categories && $langKeys) {
                $this->rlDb->insert($categories, 'categories');
                $this->rlDb->insert($langKeys, 'lang_keys');
            }
        }
    }

    /**
     * Build category key
     *
     * @param string $name
     */
    public function buildCategoryKey($name)
    {

        $key = $name;
        if (!utf8_is_ascii($key)) {
            $key = utf8_to_ascii($key);
        }

        $key = $this->rlValid->str2key($key);

        if ($this->rlDb->getOne('ID', "`Key` = '{$key}'", 'categories')) {
            $key = $key . rand(0, 9);
            return $this->buildCategoryKey($key);
        } else {
            return $key;
        }
    }

    /**
     * Build category path
     *
     * @param string $name
     */
    public function buildCategoryPath($name)
    {

        $path = $this->rlValid->str2path($name);

        if ($this->rlDb->getOne('ID', "`Path` = '{$path}'", 'categories')) {
            $path = $path . rand(0, 9);
            return $this->buildCategoryPath($path);
        } else {
            return $path;
        }
    }

    /**
     * Build category
     *
     * @param array $data
     */
    public function buildCategory($data)
    {
        global $config;

        $typeKey = $this->eventsListingType->getKey();
        if (!$typeKey) {
            return;
        }

        // set first cat and make general
        $firtCatID = $this->rlDb->getOne('ID', "`Type` = '{$typeKey}'", 'categories');

        if ($firtCatID) {
            // Cat_general_cat
            $updateData = array(
                'fields' => array(
                    'Cat_general_cat' => $firtCatID,
                    'Cat_general_only' => '1',
                ),
                'where' => array(
                    'Key' => $typeKey,
                ),
            );
            $this->rlDb->updateOne($updateData, 'listing_types');
        }

        if ($data['listing_fields']) {
            $this->rlDb->query("
                INSERT INTO `{db_prefix}listing_groups`
                (`Key`, `Display`, `Status`)
                VALUES
                ('event_rates', '1', 'active')
            ");
            $groupID = $this->rlDb->insertID();

            $fields = [];
            $relationCatIDs = $shortCatIDs = $titleCatIDs = $searchForm = [];
            $position = $dateID = 0;

            foreach ($data['listing_fields'] as $key => $value) {
                if ($this->rlDb->getOne('ID', "`Key` = '{$value['Key']}'", 'listing_fields')) {
                    continue;
                }

                if ($value['Field_type']) {
                    $fields[$value['Key']] = $value['Field_type'];

                    if ($value['Key'] == 'event_date') {
                        $fields['event_date_multi'] = $value['Field_type'];
                    }
                }

                $insertField = array(
                    'Key' => $value['Key'],
                    'Type' => $value['Type'],
                    'Default' => $value['Default'],
                    'Values' => $value['Values'],
                    'Details_page' => $value['Details_page'],
                    'Required' => 1,
                );
                $this->rlDb->insertOne($insertField, 'listing_fields');
                $fieldID = $this->rlDb->insertID();

                // Make relation array for event general category
                if ($value['Key'] == 'event_price_type') {
                    $relationCatIDs[$groupID] = array(
                        'Category_ID' => $firtCatID,
                        'Position' => $position,
                        'Group_ID' => $groupID,
                        'Fields' => $fieldID,
                    );
                } else {
                    $relationCatIDs[] = array(
                        'Category_ID' => $firtCatID,
                        'Position' => $position,
                        'Group_ID' => '0',
                        'Fields' => $fieldID,
                    );
                }

                if ($value['Key'] == 'event_title') {
                    $titleCatIDs[] = array(
                        'Category_ID' => $firtCatID,
                        'Position' => $position,
                        'Field_ID' => $fieldID,
                    );
                }
                if ($value['Key'] == 'event_date') {
                    $dateID = $fieldID;
                    $shortCatIDs[] = array(
                        'Category_ID' => $firtCatID,
                        'Position' => $position,
                        'Field_ID' => $fieldID,
                    );
                }
                $position++;
            }

            // add to form the price field
            $priceID = 0;

            // add price fields by price_tag_field config
            if ($config['price_tag_field']) {
                $priceID = $this->rlDb->getOne('ID', "`Key` = '{$config['price_tag_field']}' AND `Status` = 'active'", 'listing_fields');
            }

            if (!$priceID) {
                if ($tmpID = $this->rlDb->getOne('ID', "`Type` = 'price' AND `Status` = 'active'", 'listing_fields')) {
                    $priceID = $tmpID;
                } else {
                    $this->rlDb->addColumnToTable('price_event', "VARCHAR(80) NOT NULL ", 'listings');

                    $fields['price_event'] = "VARCHAR(100) NOT NULL";

                    $insertField = array(
                        'Key' => 'price_event',
                        'Type' => 'price',
                        'Default' => '',
                        'Values' => '',
                        'Required' => 1,
                    );
                    $this->rlDb->insertOne($insertField, 'listing_fields');
                    $priceID = $this->rlDb->insertID();
                }
            }

            if ($priceID) {
                $shortCatIDs[] = array(
                    'Category_ID' => $firtCatID,
                    'Position' => $position,
                    'Field_ID' => $priceID,
                );

                $relationCatIDs[$groupID] = array(
                    'Category_ID' => $firtCatID,
                    'Position' => $position,
                    'Group_ID' => $groupID,
                    'Fields' => $relationCatIDs[$groupID]['Fields'] . ',' . $priceID,
                );
            }

            // build quick search
            $quickKey = $typeKey . '_quick';
            if ($quickFormID = $this->rlDb->getOne('ID', "`Key` = '{$quickKey}'", 'search_forms')) {
                $searchForm[] = array(
                    'Position' => 2,
                    'Group_ID' => 0,
                    'Category_ID' => $quickFormID,
                    'Fields' => $dateID,
                );
            }

            // Search and add group location
            if ($locationsGroupID = $this->rlDb->getOne('ID', "`Key` = 'location'", 'listing_groups')) {
                if ($locIDs = $this->rlDb->getOne('Fields', "`Group_ID` = '{$locationsGroupID}'", 'listing_relations')) {
                    $position++;
                    $relationCatIDs[] = array(
                        'Category_ID' => $firtCatID,
                        'Position' => $position,
                        'Group_ID' => $locationsGroupID,
                        'Fields' => $locIDs,
                    );
                }

                if ($quickFormID) {
                    $ids = explode(',', $locIDs);
                    $poss = 3;
                    foreach ($ids as $lID) {
                        $lID = (int) $lID;
                        if ($this->rlDb->getOne('ID', "`ID` = {$lID} AND `Type` = 'select'", 'listing_fields')) {
                            $searchForm[] = array(
                                'Position' => $poss,
                                'Group_ID' => 0,
                                'Category_ID' => $quickFormID,
                                'Fields' => $lID,
                            );
                            $poss++;
                        }
                    }
                }
            }

            if ($fields) {
                $this->rlDb->addColumnsToTable($fields, 'listings');
            }

            if ($relationCatIDs) {
                $this->rlDb->insert($shortCatIDs, 'short_forms');
                $this->rlDb->insert($shortCatIDs, 'featured_form');
                $this->rlDb->insert($titleCatIDs, 'listing_titles');
                $this->rlDb->insert($relationCatIDs, 'listing_relations');
                $this->rlDb->insert($searchForm, 'search_forms_relations');
            }

            $this->rlDb->query("ALTER TABLE `{db_prefix}listings` ADD INDEX(`event_type`);");
            $this->rlDb->query("ALTER TABLE `{db_prefix}listings` ADD INDEX(`event_date`);");
            $this->rlDb->query("ALTER TABLE `{db_prefix}listings` ADD INDEX(`event_date_multi`);");
        }
    }

    /**
     * Remove fields
     *
     * @param string - $file
     */
    public function removeLFields($data = false)
    {
        if (!$data) {
            return;
        }
        // Remove multi date
        $data['listing_fields'][]['Key'] = 'event_date_multi';

        if ($this->rlDb->getOne('ID', "`Key` = 'price_event'", 'listing_fields')) {
            $data['listing_fields'][]['Key'] = 'price_event';
        }

        foreach ($data['listing_fields'] as $key => $value) {

            if ($value['Key']) {
                $field = $this->rlDb->fetch(
                    array('ID', 'Readonly', 'Values', 'Type', 'Condition'),
                    array('Key' => $value['Key']),
                    null,
                    1,
                    'listing_fields',
                    'row'
                );

                // DROP field from the lsetings table
                $sql = "ALTER TABLE `{db_prefix}listings` DROP `{$value['Key']}` ";

                if ($this->rlDb->query($sql)) {

                    // delete information from listing_fields table
                    $this->rlDb->delete(array('Key' => $value['Key']), 'listing_fields', null, 0);

                    // delete languages phrases by current field
                    $sql = "DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'listing_fields+name+{$value['Key']}' ";
                    $sql .= "OR `Key` = 'listing_fields+default+{$value['Key']}' OR `Key` = 'listing_fields+description+{$value['Key']}'";

                    // Delete field value names
                    if (!$field['Condition'] && $field['Values'] && ($field['Type'] == 'checkbox' || $field['Type'] == 'radio')) {
                        foreach (explode(',', $field['Values']) as $fValue) {
                            $sql .= " OR `Key` = 'listing_fields+name+{$value['Key']}_{$fValue}'";
                        }
                    }
                    $this->rlDb->query($sql);

                    // delete field relations from submit forms
                    $field_rel = $this->rlDb->fetch(
                        array('ID', 'Fields'),
                        null,
                        "WHERE FIND_IN_SET('{$field['ID']}', `Fields`) > 0",
                        null,
                        'listing_relations'
                    );

                    foreach ($field_rel as $field_item) {
                        $c_fields = explode(',', trim($field_item['Fields'], ','));
                        $poss = array_search($field['ID'], $c_fields);

                        unset($c_fields[$poss]);

                        if (!empty($c_fields)) {
                            $sql = "UPDATE `{db_prefix}listing_relations` SET `Fields` = '" . implode(',', $c_fields) . ",' WHERE `ID` = '{$field_item['ID']}'";
                        } else {
                            $sql = "DELETE FROM `{db_prefix}listing_relations` WHERE `ID` = '{$field_item['ID']}'";
                        }
                        $this->rlDb->query($sql);
                    }

                    // delete field relations from search forms
                    $search_rel = $this->rlDb->fetch(array('ID', 'Fields'), null, "WHERE FIND_IN_SET('{$field['ID']}', `Fields`) > 0", null, 'search_forms_relations');
                    foreach ($search_rel as $search_item) {
                        $c_fields = explode(',', trim($search_item['Fields'], ','));
                        $poss = array_search($field['ID'], $c_fields);

                        unset($c_fields[$poss]);

                        if (!empty($c_fields)) {
                            $sql = "UPDATE `{db_prefix}search_forms_relations` SET `Fields` = '" . implode(',', $c_fields) . ",' WHERE `ID` = '{$search_item['ID']}'";
                        } else {
                            $sql = "DELETE FROM `{db_prefix}search_forms_relations` WHERE `ID` = '{$search_item['ID']}'";
                        }
                        $this->rlDb->query($sql);
                    }

                    // delete field relations from short form
                    $this->rlDb->delete(array('Field_ID' => $field['ID']), 'short_forms', null, 0);

                    // delete field relations from listing title form
                    $this->rlDb->delete(array('Field_ID' => $field['ID']), 'listing_titles', null, 0);

                    // delete field relations from featured form
                    $this->rlDb->delete(array('Field_ID' => $field['ID']), 'featured_form', null, 0);
                }
            }
        }
    }

    /**
     * Remove categories
     *
     * @param string - $typeKey
     */
    public function removeCategories($typeKey)
    {
        if (!$typeKey) {
            return;
        }

        return (bool) $this->rlDb->delete(array('Type' => $typeKey), 'categories', null, 0);
    }
}
