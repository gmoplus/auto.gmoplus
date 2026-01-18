<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLNEARBYSCHOOLS.CLASS.PHP
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

class rlNearbySchools
{

    /**
     * Getting all UK school by postcode
     *
     * @since 1.1.0
     *
     * @param array $parametrs - Request parametrs for APIrequest
     * @param int   $listingID - ID
     *
     * @return array           - Schools array
     *
     */
    public function getUKSchools($parametrs, $listingID)
    {
        global $config, $rlDb;

        $result = array();
        $request_url = "https://api.propertydata.co.uk/schools?"
            . http_build_query($parametrs);
        $xml_response = $GLOBALS['reefless']->getPageContent($request_url);

        if ($xml_response) {
            $xml = json_decode($xml_response, true);
            if ($xml['status'] === 'success') {
                $schools = $xml['data']['state']['nearest'];
                foreach ($schools as $key => $school) {
                    foreach ($school as $key => $value) {
                        $school['badge'] = 'badge-text';
                        $school['isNumeric'] = false;
                        
                        if ($key == 'rating') {
                            $school['background'] = $this->setBackg('uk', $value);
                        }
                        if ($key == 'phase') {
                            unset($school[$key]);
                            $school['gradeRange'] = $value;
                        }
                        if ($key == 'url') {
                            unset($school[$key]);
                            $school['overviewLink'] = $value;
                        }
                    }
                    $result[] = $school;
                }

                $insert_data = array(
                    'Listing_ID' => $listingID,
                    'Data' => base64_encode(serialize($result))
                );
                $rlDb->insertOne($insert_data, 'nearby_schools');
            } else {
                return [];
            }
        } else {
            return [];
        }
        return $result;
    }

    /**
     * Getting all USA school by State and Locale
     *
     * @since 1.1.0
     *
     * @param  array $parametrs - Request parametrs for APIrequest
     * @param  int   $listingID - ID
     *
     * @return array            - Schools array
     *
     */
    public function getUSAchools($parametrs, $listingID)
    {
        global $config, $rlDb;

        if (!extension_loaded('curl') || !$config['nbs_api_key']) {
            return [];
        }

        $result = array();
        $parametrs = array_filter($parametrs);
        $request_url = 'https://gs-api.greatschools.org/nearby-schools?' . http_build_query($parametrs);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: ' . $config['nbs_api_key']));
        $content = curl_exec($ch);
        curl_close($ch);

        if ($content) {
            $schools = json_decode($content, true);

            if (is_array($schools['schools'])) {
                $schools = $schools['schools'];

                foreach ($schools as $key => $school) {
                    $result[] = array(
                        'rating' => $school['rating'],
                        'overviewLink' => $school['overview-url'],
                        'name' => $school['name'],
                        'gradeRange' => $school['level-codes'],
                        'distance' => round($school['distance'], 1),
                        'badge' => 'badge',
                        'isNumeric' => true,
                        'background' => $this->setBackg('usa', $school['rating']),
                    );
                }

                $insert_data = array(
                    'Listing_ID' => $listingID,
                    'Data' => base64_encode(serialize($result))
                );
                $rlDb->insertOne($insert_data, 'nearby_schools');
            }
        } else {
            return [];
        }
        return $result;
    }

    /**
     * Checks for a record in the database ,if exists then returns the list of schools
     *
     * @since 1.1.0
     *
     * @param int $listingID - ID
     *
     * @return  array  - Schools array
     *
     */
    public function hasCache($listingID)
    {
        global $rlDb;
        
        $result = array();
        $is_cached = $rlDb->getOne('Data', "`Listing_ID` = {$listingID}", 'nearby_schools');
        if (!empty($is_cached)) {
            $result = unserialize(base64_decode($is_cached));

            if (isset($result[0]['gsRating'])) {
                foreach ($result as $key => $school) {
                    $result[$key]['rating'] = $school['gsRating'];
                    unset($result[$key]['gsRating']);
                }
            }
        }
        return $result;
    }
    
    /**
     * Set background  color by rating
     *
     * @since 1.1.0
     *
     * @param string $typeApi - country api
     * @param string $raiting - raiting thr school
     *
     * @return  string  - background
     *
     */
    public function setBackg($typeApi, $raiting)
    {
        $background = 'warning';
        
        if ($raiting !=null) {
            if ($typeApi === 'uk') {
                switch ($raiting) {
                    case 'Inadequate':
                    case 'Unknown':
                    $background = 'warning';
                    break;

                    case 'Requires improvement':
                    $background = 'alert';
                    break;

                    case 'Outstanding':
                    case 'Good':
                    $background = 'success';
                    break;

                    default:
                    $background = 'warning';
                    break;
                }
            }

            if ($typeApi === 'usa') {
                switch ($raiting) {
                    case ($raiting < 2):
                    $background = 'warning';
                    break;

                    case ($raiting < 7):
                    $background = 'alert';
                    break;

                    case ($raiting >= 7):
                    $background = 'success';
                    break;

                    default:
                    $background = 'warning';
                    break;
                }
            }
        }
         
        return $background;
    }

    /**
     * Returns a list of schools according to the country
     *
     * @since 1.1.0
     *
     * @return  array  - Schools array
     *
     */
    public function getSchools()
    {
        global $listing_data, $page_info, $config, $rlCommon, $rlSmarty, $rlDebug;

        $data = array();
        $need_unset = false;
        $listingID = $listing_data['ID'];
        if ($page_info['Key'] != 'view_details') {
            $need_unset = true;
        }
        if ($listing_data['Status'] == 'active' && !$need_unset) {
            $listing_id = $listing_data['ID'];

            if ($data = $this->hasCache($listing_id)) {
                return $data;
            } else {
                $country = $listing_data[$config['nbs_country_key']];
                
                if ($country) {
                    switch (true) {
                        case stristr($country, 'united_states'):
                            $parametrs = array(
                                'lat' => $listing_data['Loc_latitude'],
                                'lon' => $listing_data['Loc_longitude'],
                                'distance' => $config['nbs_radius'],
                                'limit' => $config['nbs_limit'],
                            );
                            $data = $this->getUSAchools($parametrs, $listingID);
                            break;

                        case (stristr($country, 'united_kingdom') || stristr($country, 'great_britain') || stristr($country, 'england')):
                            $parametrs = array(
                                'key' => $config['nbs_uk_api_key'],
                                'postcode' => $listing_data[$config['nbs_post_key']],
                            );
                            $data = $this->getUKSchools($parametrs, $listingID);
                            break;

                        default:
                            $need_unset = true;
                            break;
                    }
                } else {
                    $need_unset = true;
                }
            }
        }
        if (empty($data)) {
            $need_unset = true;
        }
        if ($need_unset) {
            unset($GLOBALS['blocks']['nearbyschools']);
            unset($GLOBALS['block_keys']['nearbyschools']);

            $rlCommon->defineBlocksExist($GLOBALS['blocks']);
            $rlSmarty->assign('blocks', $GLOBALS['blocks']);
        } else {
            return $data;
        }
    }

    /**
     * @deprecated 1.2.0
     */
    public function getState($state_key) {}

    /**
     * Display plugin option in listing type form
     * 
     * @since 1.1.0
     *
     * @hook apTplListingTypesForm
     */
    public function hookApTplListingTypesForm()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'nearbySchools/row.tpl');
    }

    /**
     * Assign data to POST
     * 
     * @since 1.1.0
     *
     * @hook apPhpListingTypesPost
     */
    public function hookApPhpListingTypesPost()
    {
        $_POST['neabSchool'] = $GLOBALS['type_info']['isNearScl'];
    }

    /**
     * Assign to data array
     * 
     * @since 1.1.0
     *
     * @hook apPhpListingTypesBeforeAdd
     */
    public function hookApPhpListingTypesBeforeAdd()
    {
        $GLOBALS['data']['isNearScl'] = (int) $_POST['neabSchool'];
    }

    /**
     * Assign to data array
     * 
     * @since 1.1.0
     *
     * @hook apPhpListingTypesBeforeEdit
     */
    public function hookApPhpListingTypesBeforeEdit()
    {
        $GLOBALS['update_date']['fields']['isNearScl'] = (int) $_POST['neabSchool'];
    }

    /**
     * Box/Tab view handler
     * 
     * @since 1.1.0
     *
     * @hook listingDetailsBottom
     */
    public function hookListingDetailsBottom()
    {
        global $config, $tabs, $blocks, $page_info, $listing_type, $rlCommon, $listing_data;
       
        if (!$listing_type['isNearScl'] || $page_info['Key'] !== 'view_details' ) {
            unset($blocks['nearbyschools']);
            $rlCommon->defineBlocksExist($blocks);

            return;
        }

        $nearby_schools = $this->getSchools();
        if ($nearby_schools){
            $GLOBALS['rlSmarty']->assign('schools', $nearby_schools);

            if ($config['nbs_mode'] == 'tab') {
                $tabs['nearScl'] = array(
                    'key' => 'nearScl',
                    'name' => 'Schools'
                );
            }
        }
    }

    /**
     * Box/Tab view handler
     * 
     * @since 1.1.0
     *
     * @hook listingDetailsBottomTpl
     */
    public function hookListingDetailsBottomTpl()
    {
        global $rlSmarty, $config, $listing_type;

        if ($config['nbs_mode'] == 'tab' && $listing_type['isNearScl']) {
            $rlSmarty->display(RL_PLUGINS . 'nearbySchools/nbs_tab.tpl');
        }
    }

    /**
     * Plugin box status handler
     * 
     * @since 1.1.0
     *
     * @hook apPhpConfigAfterUpdate
     */
    public function hookApPhpConfigAfterUpdate()
    {
        global $config;

        if ($GLOBALS['config']['nbs_mode'] != $GLOBALS['dConfig']['nbs_mode']['value']) {

            $update = array(
                'fields' => array(
                    'Status' => ($GLOBALS['dConfig']['nbs_mode']['value'] == 'tab') ? 'trash' : 'active'
                ),
                'where' => array(
                    'Key' => 'nearbyschools'
                ),
            );
    
            $GLOBALS['rlDb']->update($update, 'blocks');
        }
    }

    /**
     * Remove cache of edited listing
     *
     * @since 1.0.1 Added params: $listingID
     *
     * @param  int $listingID
     * @return bool
     */
    public function listingEdit($listingID)
    {
        global $rlDb;

        if (!$listingID) {
            return false;
        }

        return $rlDb->query("DELETE FROM `" . RL_DBPREFIX . "nearby_schools` WHERE `Listing_ID` = {$listingID}");
    }

    /**
     * @hook staticDataRegister
     *
     * @since 1.0.0
     **/
    public function hookStaticDataRegister()
    {
        $GLOBALS['rlStatic']->addBoxFooterCSS(RL_PLUGINS_URL . 'nearbySchools/static/style.css', 'nearbySchools', true);
    }

    /**
     * @hook apPhpConfigBeforeUpdate
     *
     * @since 1.0.0
     **/
    public function hookApPhpConfigBeforeUpdate()
    {
        global $rlDb;

        if ($GLOBALS['dConfig']['nbs_limit']['value'] != $GLOBALS['config']['nbs_limit']) {
            $rlDb->query("TRUNCATE TABLE `" . RL_DBPREFIX . "nearby_schools`");
        }
    }

    /**
     * @hook tplFooter
     *
     * @since 1.0.0
     **/
    public function hookTplFooter()
    {
        global $rlStatic;

        if (!is_object($rlStatic)) {
            echo "<link rel='stylesheet' href='" . RL_PLUGINS_URL . "nearbySchools/static/style.css' />";
        }
    }

    /**
     * @deprecated 1.2.0
     **/
    public function hookListingDetailsTop() {}

    /**
     * @hook apMixConfigItem
     *
     * @since 1.0.0
     **/
    public function hookApMixConfigItem(&$value)
    {
        global $lang, $rlDb;

        static $l_fields = [];

        if ($value['Key'] == 'nbs_state_key') {
            $rlDb->setTable('listing_fields');
            $value['Values'] = array();
            foreach ($l_fields AS $item) {
                $value['Values'][] = array('ID' => $item['Key'], 'name' => $lang['listing_fields+name+' . $item['Key']]);
            }
        }
        if ($value['Key'] == 'nbs_post_key') {
            $rlDb->setTable('listing_fields');
            $value['Values'] = array();
            $fields = $rlDb->fetch(
                array('Key'),
                array('Status' => 'active'),
                "AND `Type` IN ('text','number')"
            );
            foreach ($fields AS $item) {
                $value['Values'][] = array('ID' => $item['Key'], 'name' => $lang['listing_fields+name+' . $item['Key']]);
            }
        }
        if ($value['Key'] == 'nbs_country_key') {
            $rlDb->setTable('listing_fields');
            $value['Values'] = array();
            $l_fields = $rlDb->fetch(
                array('Key'),
                array('Status' => 'active', 'Map' => '1'),
                "AND `Type` IN ('text','select')"
            );
            
            foreach ($l_fields AS $item) {
                $value['Values'][] = array('ID' => $item['Key'], 'name' => $lang['listing_fields+name+' . $item['Key']]);
            }
        }
    }

    /**
     * @hook  afterListingEdit
     *
     * @since 1.0.1 Added param: $addListing
     * @since 1.0.0
     * @param \Flynax\Classes\AddListing $addListing
     */
    public function hookAfterListingEdit($addListing = null)
    {
        $listingID = !is_null($addListing) ? $addListing->listingID : $GLOBALS['listing_id'];
        $this->listingEdit($listingID);
    }

    /**
     * @hook  apPhpListingsAfterEdit
     *
     * @since 1.0.0
     **/
    public function hookApPhpListingsAfterEdit()
    {
        $this->listingEdit($GLOBALS['listing_id']);
    }

    /**
     * Plugin install method
     */
    public function nbsInstall()
    {
        global $rlDb, $config;
        
        $rlDb->query(
            "UPDATE `{db_prefix}blocks`
            SET `Sticky` = '0',
                `Page_ID` = '25',
                `Cat_sticky` = '" . (version_compare($config['rl_version'], '4.8.1', '>=') ? 0 : 1) . "'
            WHERE `Key` = 'nearbyschools'"
        );
        
        $rlDb->addColumnToTable(
            'isNearScl',
            "ENUM('0', '1') NOT NULL DEFAULT '1' AFTER `Status`",
            'listing_types'
        );

        $rlDb->query("
           CREATE TABLE IF NOT EXISTS `" . RL_DBPREFIX . "nearby_schools` (
              `ID` int(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `Listing_ID` int(10) NOT NULL,
              `Data` TEXT NOT NULL
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ");
    }

    /**
     * Plugin uninstall method
     */
    public function nbsUninstall()
    {
        global $rlDb;

        $rlDb->dropColumnFromTable('isNearScl', 'listing_types');
        $rlDb->query("DROP TABLE IF EXISTS`" . RL_DBPREFIX . "nearby_schools`");
    }
}
