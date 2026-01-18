<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: EVENT.PHP
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

class Event
{
    /**
     * @var \Flynax\Plugins\Events\EventsListingTypesController
     */
    private $eventsListingType;
    private $category;

    /**
     * EventsCategory constructor.
     */
    public function __construct()
    {
        $this->rlDb = eventsContainerMake('rlDb');
        $this->eventsListingType = new EventsListingTypesController();
    }

    /**
     * Get by date.
     *
     * @param  string $firstDate   - First date
     * @param  string $lastDate    - Last date
     * @param  string $month       - Month
     * @param  string $category_id - Category id
     * @return array               - Events
     */
    public function getByDate($firstDate, $lastDate, $month, $category_id)
    {
        $eventKey = $this->eventsListingType->getKey();

        if ($category_id) {
            $this->category = $GLOBALS['rlCategories']->getCategory($category_id);
        }

        if (!$GLOBALS['config']['ev_show_passed_events'] && $month + 1 == date('m')) {
            $firstDate = date('Y-m-d');
        }

        $sql = "SELECT `T1`.*, `T2`.`Type` AS `Listing_type` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";

        $sql .= "WHERE `T1`.`Status` = 'active' AND `T2`.`Type` = '{$eventKey}' AND ";
        $sql .= "IF (`T1`.`event_type` = '1', ";
        $sql .= "`T1`.`event_date` BETWEEN '{$firstDate}' AND '{$lastDate}', ";
        $sql .= "(`T1`.`event_date` BETWEEN '{$firstDate}' AND '{$lastDate}' OR `T1`.`event_date_multi` BETWEEN '{$firstDate}' AND '{$lastDate}')) ";

        $data = $this->rlDb->getAll($sql);

        $dateFormatJS = 'Y-n-j';
        $dateFormat = 'Y-m-d';
        $events = [];
        if ($data) {
            foreach ($data as $key => $value) {
                $currentDay = date($dateFormatJS);

                $dateTimeStart = new \DateTime($value['event_date']);

                if ($value['event_type'] == '1') {
                    if (!$GLOBALS['config']['ev_show_passed_events']
                        && strtotime($currentDay) >= strtotime($dateTimeStart->format($dateFormat))) {
                        continue;
                    }

                    $day = $dateTimeStart->format($dateFormatJS);
                    $events[$day] = array(
                        'name' => $value['event_title'],
                        'link' => $this->getEventUrl($value, $dateTimeStart->format($dateFormat)),
                        'finished' => strtotime($day) < strtotime($currentDay) ? 1 : 0,
                    );
                } else {
                    $dateTimeEnd = new \DateTime($value['event_date_multi']);
                    $interval = new \DateInterval('P1D');
                    $period = new \DatePeriod($dateTimeStart, $interval, $dateTimeEnd);

                    foreach ($period as $dt) {
                        $day = $dt->format($dateFormatJS);
                        if (!$GLOBALS['config']['ev_show_passed_events']
                            && strtotime($currentDay) > strtotime($dt->format($dateFormatJS))) {
                            continue;
                        }
                        if (!$events[$day]) {
                            $events[$day] = array(
                                'name' => $value['event_title'],
                                'link' => $this->getEventUrl($value, $dt->format($dateFormat)),
                                'finished' => strtotime($day) < strtotime($currentDay) ? 1 : 0,
                            );
                        }
                    }

                    $endDay = $dateTimeEnd->format($dateFormatJS);
                    if (!$events[$endDay]) {
                        $events[$endDay] = array(
                            'name' => $value['event_title'],
                            'link' => $this->getEventUrl($value, $dateTimeEnd->format($dateFormat)),
                            'finished' => strtotime($endDay) < strtotime($currentDay) ? 1 : 0,
                        );
                    }
                }
            }
        }

        return $events;
    }

    /**
     * Get by month.
     *
     * @param  string $year  - Year
     * @param  string $month - Month
     * @return array         - Events
     */
    public function getByMonth($year, $month)
    {
        $month = $month + 1;
        $firstDate = $this->getFirstDateInMonth($year, $month);
        $lastDate = $this->getLastDateInMonth($year, $month);

        $sql = "SELECT `T1`.*, `T2`.`Type` AS `Listing_type` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";

        $sql .= "WHERE `T1`.`Status` = 'active' AND ";
        $sql .= "IF (`T1`.`event_type` = '1', `T1`.`event_date` BETWEEN '{$firstDate}' AND '{$lastDate}',`T1`.`event_date` BETWEEN '{$firstDate}' AND '{$lastDate}' and `T1`.`event_date_multi` BETWEEN '{$firstDate}' AND '{$lastDate}') ";
        $data = $this->rlDb->getAll($sql);

        $events = [];
        if ($data) {
            foreach ($data as $key => $value) {
                $currentDay = date('j');
                $dateTimeStart = new \DateTime($value['event_date']);

                if ($value['event_type'] == '1') {
                    $day = $dateTimeStart->format('j');
                    $events[$day] = array(
                        'name' => $value['event_title'],
                        'link' => $this->getEventUrl($value, $dateTimeStart->format('Y-m-d')),
                        'finished' => $day < $currentDay ? 1 : 0,
                    );
                } else {
                    $dateTimeEnd = new \DateTime($value['event_date_multi']);
                    $interval = new \DateInterval('P1D');
                    $period = new \DatePeriod($dateTimeStart, $interval, $dateTimeEnd);

                    foreach ($period as $dt) {
                        $day = $dt->format('j');
                        if (!$events[$day]) {
                            $events[$day] = array(
                                'name' => $value['event_title'],
                                'link' => $this->getEventUrl($value, $dt->format('Y-m-d')),
                                'finished' => $day < $currentDay ? 1 : 0,
                            );
                        }
                    }

                    $endDay = $dateTimeEnd->format('j');
                    if (!$events[$endDay]) {
                        $events[$endDay] = array(
                            'name' => $value['event_title'],
                            'link' => $this->getEventUrl($value, $dateTimeEnd->format('Y-m-d')),
                            'finished' => $endDay < $currentDay ? 1 : 0,
                        );
                    }
                }
            }
        }

        return $events;
    }

    /**
     *  Get last date in this month
     *
     * @param  string $year  - Year
     * @param  string $month - Month
     * @return string        - Date
     */
    public function getLastDateInMonth($year, $month)
    {
        $result = strtotime("{$year}-{$month}-01");
        $result = strtotime('-1 second', strtotime('+1 month', $result));

        return date('Y-m-d', $result);
    }

    /**
     *  Get first date in this month
     *
     * @param  string $year  - Year
     * @param  string $month - Month
     * @return string        - Date
     */
    public function getFirstDateInMonth($year, $month)
    {
        if (!$GLOBALS['config']['ev_show_passed_events'] && $month == date('m')) {
            $date = date('Y-m-d');
        } else {
            $result = strtotime("{$year}-{$month}-01");
            $date = date('Y-m-d', $result);
        }

        return $date;
    }

    /**
     *  Get event url
     *
     * @param  string  $item - Item
     * @param  string  $date - Date
     * @param  boolean $mode - Mode
     * @return string        - Page url
     */
    public function getEventUrl($item, $date, $mode = false)
    {
        global $reefless, $rlListingTypes, $config;

        $listing_type = $rlListingTypes->types[$item['Listing_type']];
        $add_url['event-date'] = $date;
        if ($this->category) {
            $add_url['category'] = $GLOBALS['config']['mod_rewrite'] ? $this->category['Path'] : $this->category['ID'];
        }
        // Hot fix - apply html or /
        $tmpPageConf = $config['html_in_pages'];
        $config['html_in_pages'] = $config['html_in_categories'];
        $page_url = $reefless->getPageUrl($listing_type['Page_key'], $add_url);
        $config['html_in_pages'] = $tmpPageConf;

        return $page_url;
    }
}
