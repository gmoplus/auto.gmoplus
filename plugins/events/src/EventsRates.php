<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: EVENTSRATES.PHP
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

/**
 * @since 1.1.0
 */
class EventsRates
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
     * Create rates
     *
     * @param array $data
     */
    public function createRates($data)
    {
        if ($data['event_rates'] && !$this->rlDb->tableExists('listing_event_rates')) {
            $allLangs = $GLOBALS['languages'];
            $langKeys = array();

            $raw_sql = "`ID` int(11) NOT NULL AUTO_INCREMENT,
                  `Listing_ID` int(10) NOT NULL,
                  `Rate` varchar(255) NOT NULL,
                  `Custom` enum('0','1') NOT NULL DEFAULT '0',
                  `Price` varchar(255) NOT NULL,
                   PRIMARY KEY (`ID`)";

            $this->rlDb->createTable("listing_event_rates", $raw_sql, RL_DBPREFIX);

            $dfKey = 'event_rates';
            $dataInsert = array(
                'Key' => $dfKey,
                'Status' => 'active',
                'Order_type' => 'position',
                'Conversion' => 0,
                'Parent_ID' => 0,
            );

            if ($this->rlDb->insertOne($dataInsert, 'data_formats')) {
                $groupID = $this->rlDb->insertID();
                $pos = 1;

                foreach ($data['event_rates'] as $names) {
                    $itemKey = $this->buildKey($dfKey . '_' . $names['en']);
                    $insert = array(
                        'Parent_ID' => $groupID,
                        'Key' => $itemKey,
                        'Status' => 'active',
                        'Default' => 0,
                        'Position' => $pos,
                    );
                    $pos++;

                    if ($this->rlDb->insertOne($insert, 'data_formats')) {
                        foreach ($allLangs as $key => $value) {
                            $name = $names[$allLangs[$key]['Code']] ? $names[$allLangs[$key]['Code']] : $names['en'];

                            $langKeys[] = array(
                                'Value' => $name,
                                'Key' => 'data_formats+name+' . $itemKey,
                                'Code' => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Plugin' => 'events',
                            );
                        }
                    }
                }
                if ($langKeys) {
                    $this->rlDb->insert($langKeys, 'lang_keys');
                }
            }
        }
    }

    /**
     * Build rate key
     *
     * @param  string $name - Category name
     * @return array        - Results data
     */
    public function buildKey($name)
    {
        $key = $name;
        $key = $this->rlValid->str2key($key);
        if ($this->rlDb->getOne('ID', "`Key` = '{$key}'", 'data_formats')) {
            $key = $key . rand(0, 1);
            return $this->buildKey($key);
        } else {
            return $key;
        }
    }

    /**
     * Manage rate data
     *
     * @param int   $listing_id - Listing ID
     * @param array $data       - Listing data
     * @param array $fields     - Listing fields
     */
    public function manageRates($listing_id, $data, $fields = false)
    {
        if ($data['event_rates']) {

            $insert = $update = $updatePrice = array();

            $priceKey = $price = '';
            if ($fields) {
                foreach ($fields as $field) {
                    if ($field['Type'] == 'price') {
                        $priceKey = $field['Key'];
                    }
                }
            }

            foreach ($data['event_rates'] as $key => $entry) {
                $custom_rate = ($entry['rate'] == '*cust0m*');
                $rate_value = $custom_rate
                ? $entry['custom_rate']
                : $entry['rate'];

                // Get price field for listing
                if (!$updatePrice && $entry['price']) {
                    $updatePrice = $entry;
                }

                if ($entry['id']) {
                    $update[] = array(
                        'fields' => array(
                            'Rate' => $rate_value,
                            'Price' => $entry['price'] . '|' . $entry['currency'],
                            'Custom' => $custom_rate ? 1 : 0,
                        ),
                        'where' => array(
                            'ID' => $entry['id'],
                        ),
                    );

                } else {
                    $insert[] = array(
                        'Listing_ID' => $listing_id,
                        'Rate' => $rate_value,
                        'Price' => $entry['price'] . '|' . $entry['currency'],
                        'Custom' => $custom_rate ? 1 : 0,
                    );
                }
            }

            if ($insert) {
                $this->rlDb->insert($insert, 'listing_event_rates');
            }

            if ($update) {
                $this->rlDb->update($update, 'listing_event_rates');
            }

            // update price field for listing
            $updateListingPrice = array(
                'fields' => array(
                    $priceKey => $updatePrice ? $updatePrice['price'] . '|' . $updatePrice['currency'] : 0,
                ),
                'where' => array(
                    'ID' => $listing_id,
                ),
            );

            $this->rlDb->updateOne($updateListingPrice, 'listings');
        }
    }

    /**
     * Simulate post of rates
     *
     * @param int $listing_id - Listing ID
     */
    public function postSimulationRates($listing_id)
    {
        if (!$listing_id || $_POST['f']['event_rates']) {
            return;
        }

        $_POST['f']['event_rates'] = $this->getRates($listing_id);
    }

    /**
     * Get rates
     *
     * @param  int $listing_id - Listing ID
     * @return array           - Return data
     */
    public function getRates($listing_id)
    {
        $event_rates = $this->rlDb->fetch('*', array('Listing_ID' => $listing_id), null, null, 'listing_event_rates');
        $rates = [];

        if (!empty($event_rates)) {
            foreach ($event_rates as $index => $entry) {
                list($price, $currency) = explode('|', $entry['Price'], 2);
                $custom = intval($entry['Custom']);

                $rates[$index] = array(
                    'id' => $entry['ID'],
                    'rate' => $custom ? '*cust0m*' : $entry['Rate'],
                    'custom_rate' => $custom ? $entry['Rate'] : '',
                    'currency' => $currency,
                    'price' => $price,
                );
            }
        }
        return $rates;
    }

    /**
     * Get rates to listing details
     *
     * @param  int $listing_id - Listing ID
     * @return array           - Return data
     */
    public function getDetailsRates($listing_id)
    {
        $event_rates = $this->rlDb->fetch('*', array('Listing_ID' => $listing_id), null, null, 'listing_event_rates');

        $rates = [];
        foreach ($event_rates as $key => $value) {
            $fKey = 'rate' . $key;
            $field = [
                'Key' => $fKey,
                'Type' => 'price',
                'Details_page' => '1',
                'name' => intval($value['Custom']) ? $value['Rate'] : $GLOBALS['lang']['data_formats+name+' . $value['Rate']],
            ];

            $parsePrice = explode('|', $value['Price']);
            if ($parsePrice[0]) {
                $field['value'] = $GLOBALS['rlCommon']->adaptValue(
                    $field,
                    $value['Price'],
                    'listing',
                    $listing_id,
                    true,
                    false,
                    false,
                    false,
                    false,
                    null,
                    $this->eventsListingType->getKey()
                );
            } else {
                $field['value'] = $GLOBALS['lang']['free'];
            }

            $rates[$fKey] = $field;
        }
        return $rates;
    }

    /**
     * Delete Rate
     *
     * @param int $id - ID
     */
    public function deleteRate($id)
    {
        if (!$id) {
            return;
        }
        $this->rlDb->delete(array('ID' => $id), 'listing_event_rates');
    }
}
