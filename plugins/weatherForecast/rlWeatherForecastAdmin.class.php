<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: LIB.JS
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
 *  Flynax Classifieds Software 2024 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

/**
 * Weather Forecast admin panel class
 * @since 3.1.0
 */
class rlWeatherForecastAdmin
{
    /**
     * @var API base to get forecast by location ID
     */
    private $api_base_id = 'https://api.openweathermap.org/data/2.5/forecast?{data}&appid={appid}&units={units}&cnt=32';

    /**
    * @var content code for polls blocks
    */
    private $content = "
        global \$rlSmarty;

        \$GLOBALS['reefless']->loadClass('WeatherForecast', null, 'weatherForecast');

        \$GLOBALS['rlWeatherForecast']->detectUnit();

        \$rlSmarty->assign('next_update', {update_time});

        \$code = <<< VS
{data_replace}
VS;

        \$forecast = json_decode(\$code, true);

        \$GLOBALS['rlWeatherForecast']->convertUnit(\$forecast);
        \$rlSmarty->assign('forecast', \$forecast);

        \$rlSmarty->display(RL_PLUGINS . 'weatherForecast' . RL_DS . 'weatherForecast.block.tpl');
";

    /**
     * Plugin installer
     */
    public function install()
    {
        global $rlDb;
        
        $sql = "
            INSERT INTO `" . RL_DBPREFIX . "config` 
            (`Group_ID`, `Key`, `Default`, `Type`, `Plugin`) VALUES 
            (0, 'weatherForecast_wb_location_id', '5391959', 'text', 'weatherForecast')
        ";
        $rlDb->query($sql);

        $sql = "
            ALTER TABLE `" . RL_DBPREFIX . "listing_types` 
            ADD `Weather_forecast` ENUM('0', '1') NOT NULL DEFAULT '1' AFTER `Status`
        ";
        $rlDb->query($sql);

        $update_box = array(
            'fields' => array('Sticky' => '0', 'Page_ID' => '1'),
            'where' => array('Key' => 'weatherForecast_block')
        );
        $rlDb->updateOne($update_box, 'blocks');
    }

    /**
     * Gets forecast by location ID or Location
     * 
     * @param  string $query  - API query data
     * @param  string $method - API query type "id" or "q"
     * @return array          - Forecast data
     */
    public function get($mode, $location = 'id') {
        global $reefless, $config;

        $units = 'metric';

        switch ($mode) {
            case 'id':
                $data = 'id=' . intval($location);
                break;

            case 'location':
                $data = 'q=' . urlencode($location);
                break;

            case 'coordinates':
                $coordinates = explode(',', $location);
                $data = 'lat=' . $coordinates[0] . '&lon=' . $coordinates[1];
                break;

            default:
                $this->errorLog('No requested mode "' . $mode . '" available"', __LINE__);
                return false;
                break;
        }

        $url = str_replace(
            array('{data}', '{appid}', '{units}'),
            array($data, $config['weatherForecast_apiid'], $units),
            $this->api_base_id
        );
        $content = $reefless->getPageContent($url);

        if ($content) {
            $data = json_decode($content, true);
            return $this->adaptData($data);
        } else {
            $this->errorLog('No data received by weather API call for "' . $mode . '": "' . $location . '"', __LINE__);
            return false;
        }
    }

    /**
     * Adapt forecast data
     * @param  array &$data - Frecast data from API
     * @return array        - Adapted forecast
     */
    private function adaptData(&$data) {
        if (!$data['list']) {
            $this->errorLog('No forecast data found in response ("list" index is required)', $errors, __LINE__);
            return false;
        }

        $forecast = [
            'location' => $data['city']['name'],
            'city_id' => $data['city']['id'],
            'forecast' => []
        ];

        $prev_date = null;
        $prev_index = 0;

        foreach ($data['list'] as $index => $item) {
            $exp_date = explode(' ', $item['dt_txt']);
            $date = $exp_date[0];

            if ($prev_date && $prev_date != $date) {
                $forecast['forecast'][] = $this->getDayAverage(
                    array_slice($data['list'], $prev_index, $index - $prev_index),
                    $prev_index
                );

                $prev_index = $index;
            }

            $prev_date = $date;

            if (count($forecast['forecast']) == 4) {
                break;
            }
        }

        return $forecast;
    }

    /**
     * Get day average forecase
     * 
     * @param  array   $forecast   - Hourly day forecast
     * @param  integer $prev_index - Previous index
     * @return array               - Average forecase for the day
     */
    private function getDayAverage($forecast, $prev_index)
    {
        $count = count($forecast);

        $average_temp = 0;
        $average_min  = false;
        $average_max  = false;
        $noon         = array();

        // Current conditions
        // if ($prev_index == 0) {
        //     $noon         = $forecast[0];
        //     $average_temp = $noon['main']['temp'];
        //     $average_min  = $noon['main']['temp_min'];
        //     $average_max  = $noon['main']['temp_max'];
        // }
        // // Average condition per day
        // else {
            foreach ($forecast as $item) {
                $average_temp += $item['main']['temp'];
                $average_min = $average_min === false
                ? $item['main']['temp_min']
                : min($item['main']['temp_min'], $average_min);
                $average_max = $average_max === false
                ? $item['main']['temp_max']
                : max($item['main']['temp_max'], $average_max);

                if (array_pop(explode(' ', $item['dt_txt'])) == '12:00:00') {
                    $noon = $item;
                }
            }

            $noon = $noon ?: $item;

            $average_temp = $average_temp / $count;
        //}

        $unix_data = $noon['dt'];

        return array(
            'date' => $noon['dt'],
            'week_day_short' => date('D', $unix_data),
            'week_day' => date('w', $unix_data),
            'temp' => $average_temp,
            'temp_min' => $average_min,
            'temp_max' => $average_max,
            'icon' => $noon['weather'][0]['icon'],
            'icon_id' => $noon['weather'][0]['id'],
            'name' => $noon['weather'][0]['main']
        );
    }

    /**
     * @deprecated 3.3.0
     */
    private function formatTemp($temp) {}

    /**
     * Error hander, adds error to global errors array and logs error to the errorLog file
     *
     * @param string $msd    - Rrror message
     * @param array  $errors - Global errors array
     * @param string $line   - Related code line
     */
    private function errorLog($msg, $line)
    {
        $errors[] = $msg;
        $GLOBALS['rlDebug']->logger('WeatherForecast Plugin Error: ' . $msg . ' On ' . __FILE__ . '(line #' . $line . ')');
    }

    /**
     * Updates weather forecast website box
     *
     * @param array $forecast - Forecast data
     */
    public function updateBox($forecast)
    {
        $update_time = time() + ($GLOBALS['config']['weatherForecast_cache'] * 3600);

        $update = array(
            'fields' => array(
                'Content' => str_replace(
                    array('{data_replace}', '{update_time}'),
                    array(json_encode($forecast), $update_time),
                    $this->content
                )
            ),
            'where' => array(
                'Key' => 'weatherForecast_block'
            )
        );

        $GLOBALS['reefless']->loadClass('Actions');
        $allow_html = $GLOBALS['rlActions']->rlAllowHTML;
        $GLOBALS['rlActions']->rlAllowHTML = true;
        $GLOBALS['rlActions']->updateOne($update, 'blocks');
        $GLOBALS['rlActions']->rlAllowHTML = $allow_html;
    }

    /**
     * Adds the google maps API js loading script
     * 
     * @hook apTplContentBottom
     */
    public function hookApTplContentBottom()
    {
        if ($GLOBALS['controller'] == 'settings') {
            $GLOBALS['rlSmarty']->display(RL_ROOT . 'plugins' . RL_DS . 'weatherForecast' . RL_DS . 'admin' . RL_DS . 'ap.js.tpl');
        }
    }

    /**
     * Update site weather forecast box cache
     * 
     * @hook apPhpConfigBeforeUpdate
     */
    public function hookApPhpConfigBeforeUpdate()
    {
        global $update, $config;

        $set_value = $_POST['post_config']['weatherForecast_wb_location']['value'] 
        ? $_POST['weather_default_location_id']
        : '';
        $row['where']['Key'] = 'weatherForecast_wb_location_id';
        $row['fields']['Default'] = $set_value;
        array_push($update, $row);

        if ($config['weatherForecast_wb_location_id'] != $_POST['weather_default_location_id']
            || $config['weatherForecast_units'] != $_POST['post_config']['weatherForecast_units']['value']
        ) {
            $config['weatherForecast_units'] = $_POST['post_config']['weatherForecast_units']['value'];

            $forecast = $this->get('id', $set_value);
            $this->updateBox($forecast);
        }
    }

    /**
     * Adapts related configuration values
     * 
     * @hook apMixConfigItem
     */
    public function hookApMixConfigItem(&$param1, &$systemSelects)
    {
        global $rlDb, $lang, $rlLang;

        if ($param1['Plugin'] != 'weatherForecast')
            return;

        switch ($param1['Key']) {
            case 'weatherForecast_mapping_country':
            case 'weatherForecast_mapping_region':
            case 'weatherForecast_mapping_city':
                $rlDb->setTable('listing_fields');
                $param1['Values'] = array();

                foreach ($rlDb->fetch(array('Key'), array('Status' => 'active', 'Map' => '1'), "AND `Type` IN ('text','select')") AS $item) {
                    $param1['Values'][] = array('ID' => $item['Key'], 'name' => $lang['listing_fields+name+'.$item['Key'] ]);
                }
                break;
            
            case 'weatherForecast_position':
                $param1['Values'] = array(
                    array(
                        'ID' => 'top',
                        'name' => $lang['weatherForecast_form_top']
                    ),
                    array(
                        'ID' => 'bottom',
                        'name' => $lang['weatherForecast_form_bottom']
                    ),
                    array(
                        'ID' => 'in_group',
                        'name' => $lang['weatherForecast_place_in_form']
                    )
                );
                break;

            case 'weatherForecast_group_possition':
                $param1['Values'] = array('prepend', 'append');
                $param1['Display'] = array($lang['weatherForecast_prepend'], $lang['weatherForecast_append']);
                break;

            case 'weatherForecast_group':
                $rlDb->setTable('listing_groups');
                $groups = $rlDb->fetch(array('Key`, `Key` AS `ID'), array('Status' => 'active'));
                $param1['Values'] = $rlLang->replaceLangKeys($groups, 'listing_groups', array('name'), RL_LANG_CODE, 'admin');
                break;

            case 'weatherForecast_cache':
                $systemSelects[] = 'weatherForecast_cache';

                $param1['Values'] = array();

                for ($i = 1; $i<= 24; $i++) {
                    $param1['Values'][] = array(
                        'ID' => $i,
                        'name' => $i . ' ' . $GLOBALS['lang']['weatherForecast_hours']
                    );
                }
                break;
        }
    }

    /**
     * Displays the option row on the listing type management page
     * 
     * @hook apTplListingTypesForm
     */
    public function hookApTplListingTypesForm()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'weatherForecast' . RL_DS . 'admin' . RL_DS . 'row.tpl');
    }

    /**
     * Simulate post data
     * 
     * @hook apPhpListingTypesPost
     */
    public function hookApPhpListingTypesPost()
    {
        $_POST['weather_forecast'] = $GLOBALS['type_info']['Weather_forecast'];
    }

    /**
     * Validate post data on "add listing type" page
     * 
     * @hook apPhpListingTypesBeforeAdd
     */
    public function hookApPhpListingTypesBeforeAdd()
    {
        $GLOBALS['data']['Weather_forecast'] = (int) $_POST['weather_forecast'];
    }

    /**
     * Validate post data on "edit listing type" page
     * 
     * @hook apPhpListingTypesBeforeEdit
     */
    public function hookApPhpListingTypesBeforeEdit()
    {
        $GLOBALS['update_date']['fields']['Weather_forecast'] = (int) $_POST['weather_forecast'];
    }

    /**
     * Handles frontned ajax requests
     *
     * @hook ajaxRequest
     * 
     * @param array  - Response data
     * @param string - Request mode
     * @param string - Request item
     * @param string - Request language
     */
    public function hookAjaxRequest(&$out, &$request_mode, &$request_item, &$request_lang)
    {
        $this->request($out, $request_mode, $request_item);
    }

    /**
     * Handles frontned ajax requests
     *
     * @hook apAjaxRequest
     */
    public function hookApAjaxRequest()
    {
        $this->request($GLOBALS['out'], $_REQUEST['mode'], $GLOBALS['item']);
    }

    /**
     * Handles ajax requests
     *
     * @hook ajaxRequest
     *
     * @param array  - Response data
     * @param string - Request mode
     * @param string - Request item
     */
    private function request(&$out, &$request_mode, &$request_item)
    {
        if ($request_item != 'weatherForecast') {
            return;
        }

        // Get forecast
        $forecast = $this->get($request_mode, $_REQUEST['location']);

        // Update website box cache
        if ($_REQUEST['cache']) {
            $this->updateBox($forecast);
        }

        $GLOBALS['reefless']->loadClass('WeatherForecast', null, 'weatherForecast');

        $GLOBALS['rlWeatherForecast']->detectUnit();
        $GLOBALS['rlWeatherForecast']->convertUnit($forecast);

        if ($forecast) {
            $out['status'] = 'OK';
            $out['results'] = $forecast;
        } else {
            $out['status'] = 'ERROR';
        }
    }
}
