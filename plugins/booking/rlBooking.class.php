<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLBOOKING.CLASS.PHP
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

class rlBooking extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * Time frame field
     *
     * @var boolean
     */
    public $use_time_frame = false;

    /**
     * Detect availability booking functional
     *
     * @var boolean
     */
    public $availableBooking = false;

    /**
     * Prevent modify search query
     *
     * @var boolean
     */
    public $preventModifySearchQuery = false;

    /**
     * List of controllers of pages where plugin must include system javascript libs and style file
     *
     * @var   array
     * @since 3.0.0
     */
    public $listAllowedPages = array(
        // plugin pages
        'booking_requests',
        'booking_details',
        'booking_order',
        'booking_reservations',

        // software pages
        'add_listing',
        'edit_listing',
        'listing_type',
        'listing_details',
        'my_listings',
    );

    /**
     * List of allowed listing plans for booking
     *
     * @var array
     */
    private $allowed_plans = array();

    /**
     * List of allowed membership plans for booking
     *
     * @var array
     */
    private $allowed_membership_plans = array();

    /**
     * Local alternative of IS_ESCORT constant
     */
    public $isEscort = false;

    /**
     * Path of folder with system smarty blocks of plugin
     *
     * @since 3.2.0
     *
     * @var string
     */
    public $blocksFolder = '';

    /**
     * List of weekdays
     *
     * @since 3.2.0
     *
     * @var array
     */
    public $weekdays = [];

    /**
     * Class Constructor
     */
    public function __construct()
    {
        global $config, $rlSmarty;

        // @todo - Remove 2nd part of condition when compatibility will be >= 4.6.2
        $this->isEscort = (isset($config['package_name'])
            ? $config['package_name'] === 'escort'
            : file_exists(RL_CLASSES . 'rlEscort.class.php')
        );

        for ($i = 0; $i <= 6; $i++) {
            $this->weekdays['short'][$i + 1] = ucfirst(
                mb_convert_case(
                    strftime('%a', strtotime("last monday + $i day")),
                    MB_CASE_TITLE,
                    'UTF-8'
                )
            );
            $this->weekdays['full'][$i + 1]  = ucfirst(
                mb_convert_case(
                    strftime('%A', strtotime("last monday + $i day")),
                    MB_CASE_TITLE,
                    'UTF-8'
                )
            );
            $this->weekdays['en'][$i + 1] = date('D', strtotime("last monday + $i day"));
        }

        if ($rlSmarty) {
            $this->blocksFolder = RL_PLUGINS . 'booking/smarty_blocks/';
            $rlSmarty->assign('bookingBlocksFolder', $this->blocksFolder);
            $rlSmarty->assign('bookingWeekdays', $this->weekdays);
        }
    }

    /**
     * Get available days for booking calendar
     *
     * @param  int    $listing_id - ID of listing
     * @param  string $mode       - Select next/previous month
     * @return array              - All necessary data for days in calendar
     */
    public function ajaxGetDates($listing_id, $mode = '')
    {
        global $config, $rlAccount, $rlDb, $rlEscort;

        $listing_id = (int) $listing_id;

        if (!$listing_id) {
            return [];
        }

        $configs    = $this->getConfigs($listing_id);
        $request_id = (int) $_REQUEST['item']['request_id'];
        $page_key   = $_SESSION['page_info']['current'];

        if ($configs['Calendar_restricted']) {
            $sql = "SELECT DATE_ADD(`T1`.`Pay_date`, INTERVAL `T2`.`Listing_period` DAY) AS `Plan_expire`, ";
            $sql .= "`T2`.`Listing_period` FROM `{db_prefix}listings` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID`=`T2`.`ID` ";
            $sql .= "WHERE `T1`.`ID` = {$listing_id}";
            $plan_info = $rlDb->getRow($sql);

            if ($plan_info['Listing_period'] == 0) {
                $configs['Calendar_restricted'] = 0;
            } else {
                $restriction_plan = date('Y-m-d', strtotime("{$plan_info['Plan_expire']}"));
                $restriction_plan = mktime(0, 0, 0,
                    substr($restriction_plan, 5, 2),
                    substr($restriction_plan, 8, 2),
                    substr($restriction_plan, 0, 4)
                );
            }
        }

        $countCalendars = 2;

        $curRange = mktime(0, 0, 0,
            substr(date('Y-m-d'), 5, 2),
            substr(date('Y-m-d'), 8, 2),
            substr(date('Y-m-d'), 0, 4)
        );
        $bookingDays = array();

        if ($mode) {
            $cnYear  = (int) substr($_SESSION['booking_start_date'], 0, 4);
            $cnMonth = (int) substr($_SESSION['booking_start_date'], 5, 2);
            $zn      = substr($mode, 0, 1);
            $wh      = (substr($mode, 1, 1)) == 'Y' ? 0 : 1;

            if ($wh == 0) {
                eval("\$cnYear = {$cnYear} {$zn} 1;");
            } else {
                eval("\$cnMonth = {$cnMonth} {$zn} 1;");
            }

            $startDate  = date('Y-m-d', mktime(0, 0, 0, $cnMonth, 1, $cnYear));
            $checkRange = mktime(0, 0, 0,
                substr($startDate, 5, 2),
                substr($startDate, 8, 2),
                substr($startDate, 0, 4)
            );
            $endDate = date('Y-m-d', mktime(0, 0, 0, $cnMonth + $countCalendars, 0, $cnYear));

            // update year to next
            $cnYear = (int) substr($endDate, 0, 4);

            if ($checkRange <= $curRange) {
                $startDate = date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y')));
                $endDate   = date('Y-m-d', mktime(0, 0, 0, date('m') + $countCalendars, 0, date('Y')));
            }

            $_SESSION['booking_start_date'] = $startDate;
        } else {
            // start calendar with first month from selected booking period
            if ($_SESSION['bookingData']['start_date'] && $page_key == 'booking_order') {
                $startDate = date(
                    'Y-m-d',
                    mktime(0, 0, 0,
                        date('m', $_SESSION['bookingData']['start_date']),
                        1,
                        date('Y', $_SESSION['bookingData']['start_date'])
                    )
                );

                $endDate = date(
                    'Y-m-d',
                    mktime(0, 0, 0,
                        date('m', $_SESSION['bookingData']['start_date']) + $countCalendars,
                        0,
                        date('Y', $_SESSION['bookingData']['start_date'])
                    )
                );
            } else if ($page_key == 'booking_requests' && $request_id) {
                $start_date = $rlDb->getOne('From', "`ID` = {$request_id}", 'listings_book');

                $startDate = date('Y-m-d', mktime(0, 0, 0, date('m', $start_date), 1, date('Y', $start_date)));
                $endDate   = date(
                    'Y-m-d',
                    mktime(0, 0, 0,
                        date('m', $start_date) + $countCalendars,
                        0,
                        date('Y', $start_date)
                    )
                );
            } else {
                $startDate = date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y')));
                $endDate   = date('Y-m-d', mktime(0, 0, 0, date('m') + $countCalendars, 0, date('Y')));
            }

            $_SESSION['booking_start_date'] = $startDate;
        }

        // get booked days (if option "Hide booked days in calendar" disabled or it's listing details page)
        if ((!$configs['Hide_booked_days'] || $configs['Booking_type'] === 'time_range')
            || $page_key == 'booking_requests'
        ) {
            // get all booked/prebooked requests
            $sql = "SELECT `T1`.*, `T2`.`Renter_ID` FROM `{db_prefix}listings_book` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}booking_requests` AS `T2` ON `T1`.`ID` = `T2`.`Book_ID` ";
            $sql .= "WHERE `T1`.`Status` <> 'refused'";

            if ($configs['Hide_booked_days'] && $page_key == 'booking_requests' && $request_id) {
                $sql .= "AND `T1`.`ID` = {$request_id}";
            } elseif ($listing_id) {
                $sql .= "AND `T1`.`Listing_ID` = {$listing_id}";
            }

            $userBook = $rlDb->getAll($sql);

            foreach ($userBook as &$booked_data) {
                if ($configs['Booking_type'] == 'time_range') {
                    // Rewrite "Checkout" day for escort booking (it have duration value and used 1 day)
                    if ($this->isEscort) {
                        $booked_data['Duration'] = $booked_data['To'];
                        $booked_data['To'] = '1';
                    } else {
                        $rates = $this->getRates($listing_id);

                        /* Remapping data
                         * To - it's a ID of selected rate
                         */
                        $currentRate = $rates[$booked_data['Checkin']];
                        $booked_data['RateIndex'] = $booked_data['Checkin'];
                        $booked_data['Checkin']   = $currentRate['time'];
                        $booked_data['Duration']  = $booked_data['To'];
                        $booked_data['Type']      = $currentRate['type'];
                    }
                }

                // highlight own pending requests in calendar
                $booked_data['Own_request'] = $booked_data['Renter_ID'] == $_SESSION['account']['ID']
                && $booked_data['Status'] == 'process'
                ? true
                : false;
            }
        }

        // get available rate ranges
        if ($configs['Booking_type'] !== 'time_range'
            && $rate_ranges = $rlDb->fetch('*', ['Listing_ID' => $listing_id], "ORDER BY `From`", null, 'booking_rate_range')
        ) {
            // get listing price
            $price_tag = $config['booking_price_field_' . $configs['Listing_type']];

            if ($rlDb->getRow("SHOW COLUMNS FROM `{db_prefix}listings` LIKE '{$price_tag}'")) {
                $price                       = $rlDb->getOne($price_tag, "`ID` = {$listing_id}", 'listings');
                list($price, $currency_code) = explode('|', $price);

                foreach ($rate_ranges as $range) {
                    if ($range != 0 && (float) $range['Price'] > 0) {
                        $usRange[] = array(
                            'From'  => $range['From'],
                            'To'    => $range['To'],
                            'Price' => $range['Price'] . '|' . ($currency_code ?: false),
                        );
                    } else {
                        $closeRange[] = array(
                            'From' => $range['From'],
                            'To'   => $range['To'],
                        );
                    }
                }
            }
        }

        // get escort availability data && it will be used as binding days
        if ($configs['Booking_type'] == 'time_range') {
            if ($this->isEscort) {
                global $listing_data;
                $listing_data = $GLOBALS['rlListings']->getListing($listing_id);

                // Get escort system data
                $GLOBALS['reefless']->loadClass('Escort');
                if ($this->isEscort
                    && class_exists('rlEscort')
                    && method_exists($rlEscort, 'formFieldsMergeWithListingData')
                ) {
                    $rlEscort->formFieldsMergeWithListingData();
                }
            } else {
                $availability = $this->getAvailability($listing_id);

                if ($config['booking_'] === 'sunday') {
                    foreach ($availability as $day => $availabilityInDay) {
                        $listing_data['availability'][$day === 1 ? 7 : $day - 1] = $availabilityInDay;
                    }
                } else {
                    $listing_data['availability'] = $availability;
                }
            }

            $binding_days['Checkin'] = '';

            if ($listing_data['availability_1'] || $listing_data['availability'][1]['from']) {
                $binding_days['Checkin'] .= 'Mon';
            }

            if ($listing_data['availability_2'] || $listing_data['availability'][2]['from']) {
                $binding_days['Checkin'] .= ($binding_days['Checkin'] ? ',' : '') . 'Tue';
            }

            if ($listing_data['availability_3'] || $listing_data['availability'][3]['from']) {
                $binding_days['Checkin'] .= ($binding_days['Checkin'] ? ',' : '') . 'Wed';
            }

            if ($listing_data['availability_4'] || $listing_data['availability'][4]['from']) {
                $binding_days['Checkin'] .= ($binding_days['Checkin'] ? ',' : '') . 'Thu';
            }

            if ($listing_data['availability_5'] || $listing_data['availability'][5]['from']) {
                $binding_days['Checkin'] .= ($binding_days['Checkin'] ? ',' : '') . 'Fri';
            }

            if ($listing_data['availability_6'] || $listing_data['availability'][6]['from']) {
                $binding_days['Checkin'] .= ($binding_days['Checkin'] ? ',' : '') . 'Sat';
            }

            if ($listing_data['availability_0'] || $listing_data['availability'][7]['from']) {
                $binding_days['Checkin'] .= ($binding_days['Checkin'] ? ',' : '') . 'Sun';
            }
        } else {
            $where = ['Listing_ID' => $listing_id, 'Status' => 'active'];
            $binding_days = $rlDb->fetch('*', $where, null, null, 'booking_bindings', 'row');
        }

        $sesRange = mktime(0, 0, 0,
            substr($_SESSION['booking_start_date'], 5, 2),
            substr($_SESSION['booking_start_date'], 8, 2),
            substr($_SESSION['booking_start_date'], 0, 4)
        );

        $nulled = true;
        $hack  = ($sesRange >= $curRange) ? true : false;

        $iDateFrom = mktime(0, 0, 0, substr($startDate, 5, 2), substr($startDate, 8, 2), substr($startDate, 0, 4));
        $iDateTo   = mktime(0, 0, 0, substr($endDate, 5, 2), substr($endDate, 8, 2), substr($endDate, 0, 4));

        // first day
        $mYear  = date('Y', $iDateFrom);
        $mMonth = $cnYear - (int) date('Y') > 0 && (int) date('m', $iDateFrom) < 10
            ? (int) date('m', $iDateFrom) + 12
            : (int) date('m', $iDateFrom);
        $mDay      = date('d', $iDateFrom);
        $missDay   = (int) date('N', $iDateFrom) ;
        $mktime    = $iDateFrom;

        if ($config['booking_first_day'] === 'sunday') {
            $missDay++;
        }

        $bookingDays[$mMonth]['Year'] = $mYear;
        $bookingDays[$mMonth]['Name'] = ucfirst(mb_convert_case(strftime('%b', $iDateFrom), MB_CASE_TITLE, 'UTF-8'));

        for ($i = 0; $i < $missDay - 1; $i++) {
            $bookingDays[$mMonth]['Days'][$i]['Miss'] = 1;
        }

        $day_index = $bookingDays[$mMonth]['Days'] ? count($bookingDays[$mMonth]['Days']) : 1;

        if (date('Y-m-d') == date('Y-m-d', $mktime)) {
            if ($rlAccount->isLogin() && $page_key == 'booking_requests') {
                $bookingDays[$mMonth]['Days'][$day_index] = array('Color' => 'A', 'Day' => (int) $mDay);
            } else {
                $bookingDays[$mMonth]['Days'][$day_index] = array('Color' => 'T', 'Day' => (int) $mDay);
            }

            $nulled = false;
        } elseif ($nulled === true && $hack === false) {
            $bookingDays[$mMonth]['Days'][$day_index] = array('Color' => 'U', 'Day' => (int) $mDay);
        } elseif ($mktime > $restriction_plan && $configs['Calendar_restricted']) {
            $bookingDays[$mMonth]['Days'][$day_index] = array('Color' => 'R', 'Day' => (int) $mDay);
        } else {
            $bookingDays[$mMonth]['Days'][$day_index] = array('Color' => 'A', 'Day' => (int) $mDay);
        }

        if ($bookingDays[$mMonth]['Days'][$day_index]['Color'] != 'U'
            && $bookingDays[$mMonth]['Days'][$day_index]['Color'] != 'R'
        ) {
            $bookingDays[$mMonth]['Days'][$day_index]['mktime'] = $mktime;
        }

        $month_flag  = $mMonth;
        $miss_added  = false;
        $first_month = $mMonth;
        $next_year   = false;

        $this->checkAvailabilityRegularPriceMode($listing_id);

        // next days
        while ($iDateFrom <= $iDateTo) {
            $mYear  = date('Y', $iDateFrom);
            $mMonth = (int) $cnYear - (int) date('Y') > 0 && (int) date('m', $iDateFrom) < 10
            ? (int) date('m', $iDateFrom) + 12
            : (int) date('m', $iDateFrom);

            // to fix the problem with sorting months in javascript
            // when current month "December" and next month "January"
            if ($first_month == 12 && $mMonth == 1) {
                $mMonth    = 13;
                $next_year = true;
            }

            $mDay                         = date('d', $iDateFrom);
            $mktime                       = $iDateFrom;
            $bookingDays[$mMonth]['Year'] = $mYear;
            $bookingDays[$mMonth]['Name'] = ucfirst(mb_convert_case(strftime('%b', $iDateFrom), MB_CASE_TITLE, 'UTF-8'));

            if ($month_flag != $mMonth) {
                $miss_added = false;
                $month_flag = $mMonth;
            }

            // add missed days for next months
            if ($mMonth == $month_flag && $miss_added === false) {
                $missDay = date('N', $iDateFrom);

                if ($config['booking_first_day'] === 'sunday') {
                    $missDay++;
                }

                for ($day_index2 = 0; $day_index2 < $missDay - 1; $day_index2++) {
                    $bookingDays[$mMonth]['Days'][$day_index2]['Miss'] = 'missed';
                }

                $miss_added = true;
            }

            if (date('Y-m-d') == date('Y-m-d', $mktime)) {
                $bookingDays[$mMonth]['Days'][$day_index]['Color'] = $rlAccount->isLogin()
                && $page_key == 'booking_requests'
                ? "A"
                : "T";
                $bookingDays[$mMonth]['Days'][$day_index]['Day'] = $mDay;

                $nulled = false;
            } elseif ($nulled === true && $hack === false) {
                $bookingDays[$mMonth]['Days'][$day_index]['Color'] = "U";
                $bookingDays[$mMonth]['Days'][$day_index]['Day']   = $mDay;
            } elseif ($mktime > $restriction_plan && $configs['Calendar_restricted']) {
                $bookingDays[$mMonth]['Days'][$day_index]['Color'] = "R";
                $bookingDays[$mMonth]['Days'][$day_index]['Day']   = $mDay;
            } else {
                $bookingDays[$mMonth]['Days'][$day_index]['Color'] = "A";
                $bookingDays[$mMonth]['Days'][$day_index]['Day']   = $mDay;
            }

            if ($configs['Booking_type'] === 'time_range' && $userBook && $listing_data['availability']) {
                $currentWeekday = date('N', $mktime); // 1 => Mon, 2 => Tue and etc.
                $workTime  = $listing_data['availability'][$currentWeekday];
                $workHours = $workTime['from'] && $workTime['to'] ? ($workTime['to'] - $workTime['from']) / 2 : 0;

                if ($configs['Booking_repeatedly'] === false && !$this->isEscort) {
                    $workHours *= count($rates);
                }

                $bookedTime = 0;
                foreach ($userBook as $bookData) {
                    if ((int) $bookData['From'] === $mktime) {
                        $currentBookedTime = floatval($bookData['Duration']);

                        // If user select rate with 0.5 hour we'll count direction in hours only
//                        $currentBookedTime = $currentBookedTime === 0.5
//                            ? (int) $bookData['Duration']
//                            : $currentBookedTime;

                        $bookedTime += $currentBookedTime;
                    }
                }

                if ($bookedTime && $workHours && $workHours - $bookedTime <= 0) {
                    $bookingDays[$mMonth]['Days'][$day_index]['fullyBooked'] = true;
                }
            }

            // close calendar none ranges
            if (!$this->use_time_frame) {
                $rateInRange = false;

                foreach ($usRange as $rangeValue) {
                    if ($rangeValue['From'] || $rangeValue['To']) {
                        if ($mktime >= $rangeValue['From'] && $mktime <= $rangeValue['To']) {
                            $rateInRange = true;
                            break;
                        } else {
                            $rateInRange = false;
                        }

                        if ($rangeValue['From']) {
                            $rangeValue['From'] = $rangeValue['From'] . ' | ' . date('Y-m-d H:i:s', $rangeValue['From']);
                        }

                        if ($rangeValue['To']) {
                            $rangeValue['To'] = $rangeValue['To'] . ' | ' . date('Y-m-d H:i:s', $rangeValue['To']);
                        }
                    }
                }

                if (!$rateInRange) {
                    $bookingDays[$mMonth]['Days'][$day_index]['Day'] = $mDay;

                    if ($configs['Booking_type'] != 'time_range') {
                        $bookingDays[$mMonth]['Days'][$day_index]['Color'] = "U";
                        unset($bookingDays[$mMonth]['Days'][$day_index]['mktime']);
                    }
                }
            }

            if ($bookingDays[$mMonth]['Days'][$day_index]['Color'] != 'U'
                && $bookingDays[$mMonth]['Days'][$day_index]['Color'] != 'R'
            ) {
                $bookingDays[$mMonth]['Days'][$day_index]['mktime'] = $mktime;
                $bookingDays[$mMonth]['Days'][$day_index]['Day']    = $mDay;
            }

            $iDateFrom = strtotime('+1 day', $iDateFrom); // get timestamp of next day
            $day_index++;
        }

        return array(
            'bookingDays' => $bookingDays ?: false,
            'bindingDays' => $binding_days
                ? ['checkin' => $binding_days['Checkin'], 'checkout' => $binding_days['Checkout']]
                : false,
            'userBook'    => $userBook ?: false,
            'showPrev'    => ($sesRange >= $curRange && !$next_year ? 1 : 0),
            'usRange'     => $usRange ?: false,
            'closeRange'  => $closeRange ?: false,
        );
    }

    /**
     * Accepting or Rejecting booking request by owner of listing
     *
     * @param  int    $id      - ID of request
     * @param  string $action  - Accept/Reject
     * @param  string $message - Message to renter from owner
     * @param  array $data     - Data of updated period by owner
     * @return mixed
     */
    public function ajaxOwnerResult($id, $action, $message, $data = array())
    {
        global $reefless, $rlDb, $lang, $config, $rlValid, $rlMail, $rlEscort, $rlLang;

        $reefless->loadClass('Listings');
        $reefless->loadClass('Mail');

        $rlDb->query("UPDATE `{db_prefix}booking_requests` SET `Status` = 'readed' WHERE `Book_ID` = {$id}");

        // update booking period
        if ($data) {
            $data['db_start']       = (int) $data['db_start'];
            $data['db_end']         = (int) $data['db_end'];
            $data['period_updated'] = $rlValid->xSql($data['period_updated']) == 'true' ? true : false;

            if ($data['period_updated'] && $data['db_start'] && $data['total_cost']) {
                $update = array(
                    'fields' => array(
                        'From'     => $data['db_start'],
                        'Checkin'  => $data['checkin_time'],
                        'To'       => $data['db_end'],
                        'Checkout' => $data['checkout_time'],
                        'Amount'   => $data['total_cost'],
                    ),
                    'where'  => array(
                        'ID' => $id,
                    ),
                );

                $rlDb->updateOne($update, 'listings_book');
            }
        }

        // get request info
        $sql = 'SELECT `T2`.`first_name` AS `Renter_fname`, `T2`.`last_name` AS `Renter_lname`, ';
        $sql .= '`T2`.`email` AS `Renter_mail`, ';
        $sql .= '`T3`.`First_name` AS `Owner_fname`, `T3`.`Last_name` AS `Owner_lname`, ';
        $sql .= '`T3`.`Username` AS `Owner_uname`, `T3`.`Mail` AS `Owner_mail`,  `T1`.* ';
        $sql .= 'FROM `{db_prefix}listings_book` AS `T1` ';
        $sql .= 'LEFT JOIN `{db_prefix}booking_requests` AS `T2` ON `T1`.`ID` = `T2`.`Book_ID` ';
        $sql .= 'LEFT JOIN `{db_prefix}accounts` AS `T3` ON `T2`.`Owner_ID` = `T3`.`ID` ';
        $sql .= "WHERE `T1`.`ID` = {$id}";
        $request_info = $rlDb->getRow($sql);

        // get listing info
        $listing_info = $GLOBALS['rlListings']->getShortDetails($request_info['Listing_ID']);

        // get configs of listing
        $configs = $this->getConfigs($listing_info['ID']);

        // Build link to listing
        $ad_link = $listing_info['url'];
        $ad_link = '<a href="' . $ad_link . '">' . $listing_info['listing_title'] . '</a>';

        // build renter name
        if ($request_info['Renter_fname'] && $request_info['Renter_lname']) {
            $renter = $request_info['Renter_fname'] . ' ' . $request_info['Renter_lname'];
        } else {
            $renter = $request_info['Renter_fname'] ?: $request_info['Renter_lname'];
        }

        // build owner name
        if ($request_info['Owner_fname'] || $request_info['Owner_lname']) {
            $owner = $request_info['Owner_fname'] . ' ' . $request_info['Owner_lname'];
        } else {
            $owner = $request_info['Owner_uname'];
        }

        if ($action == 'accept') {
            $status   = 'booked';
            $mess     = $rlLang->getPhrase('booking_req_accepted', null, null, true);
            $mail_tpl = $rlMail->getEmailTemplate(
                $configs['Booking_type'] == 'time_range'
                ? 'booking_accepted_request_escort'
                : 'booking_accepted_request'
            );
        } else {
            unset($_SESSION['booking_start_date']);

            $status   = 'refused';
            $mess     = $rlLang->getPhrase('booking_req_refused', null, null, true);
            $mail_tpl = $rlMail->getEmailTemplate(
                $configs['Booking_type'] == 'time_range'
                ? 'booking_refused_request_escort'
                : 'booking_refused_request'
            );
        }

        $rlDb->query("UPDATE `{db_prefix}listings_book` SET `Status` = '{$status}' WHERE `ID` = {$id}");

        $mail_tpl['body'] = preg_replace(
            "/\{if period_updated\}(.*?)\{\/if\}/smi",
            $data['period_updated'] ? '$1' : '',
            $mail_tpl['body']
        );

        if ($message) {
            foreach ($GLOBALS['languages'] as $lang_item) {
                $rlDb->rlAllowHTML = true;
                $phrase = [
                    'Code'   => $lang_item['Code'],
                    'Module' => 'common',
                    'Status' => 'active',
                    'Key'    => "booking_request+comment+{$id}",
                    'Value'  => $message,
                    'Plugin' => 'booking',
                ];

                if (method_exists($rlLang, 'createPhrase')) {
                    $rlLang->createPhrase($phrase);
                } else {
                    $rlDb->insert($phrase, 'lang_keys');
                }
            }
        }

        // send email to renter
        if ($mail_tpl) {
            $timePeriods   = $this->buildCheckInOutData($configs['Booking_type']);
            $checkInTime   = $timePeriods[$request_info['Checkin']] ?: $request_info['Checkin'];
            $checkOutTime  = $timePeriods[$request_info['Checkout']] ?: $request_info['Checkout'];
            $date_format   = str_replace('%', '', RL_DATE_FORMAT);
            $from          = date(str_replace('b', 'M', $date_format), $request_info['From']);
            $from          .= $checkInTime ? ' (' . $checkInTime  . ')' : '';
            $to            = date(str_replace('b', 'M', $date_format), $request_info['To']);
            $to            .= $checkOutTime ? ' (' . $checkOutTime . ')' : '';
            $regular_price = $this->getRegularPrice($listing_info['ID']);
            $currency      = $regular_price['currency'] ?: $config['system_currency'];

            $price = $config['system_currency_position'] == 'before'
            ? ($currency . ' ' . $request_info['Amount'])
            : ($request_info['Amount'] . ' ' . $currency);

            if ($data['period_updated']) {
                // added new escort data to email
                if ($configs['Booking_type'] == 'time_range') {
                    // get escort system data
                    if ($this->isEscort
                        && class_exists('rlEscort')
                        && method_exists($rlEscort, 'formFieldsMergeWithListingData')
                    ) {
                        global $listing_data;

                        $listing_data = $listing_info;
                        $rlEscort->formFieldsMergeWithListingData();
                    }

                    $date = date(str_replace('b', 'M', $date_format), $request_info['From']);

                    $timePeriods = $this->buildCheckInOutData($configs['Booking_type']);
                    $request_info['Checkout'] = $timePeriods[$request_info['Checkout']];

                    $date .= $request_info['Checkout'] ? ' - ' . $request_info['Checkout'] : '';
                    $date .= '<br />' . $lang['booking_escort_type'] . ': ';

                    $rate = $request_info['Checkin'];

                    if ($this->isEscort) {
                        $date .= $lang['data_formats+name+' . $listing_data['escort_rates'][$rate]['key']];
                    } else {
                        $rates = $this->getRates($request_info['Listing_ID']);
                        $date .= $rates[$rate]['title'];
                    }

                    if ($request_info['To']) {
                        $date .= '<br />' . $lang['booking_escort_duration'] . ': ';
                        $date .= $lang['booking_escort_duration_' . $request_info['To']];
                    }
                } else {
                    $date = $from . ' - ' . $to;

                    if ($configs['Booking_type'] === 'date_range') {
                        $start  = (new DateTime())->setTimeStamp($request_info['From']);
                        $finish  = (new DateTime())->setTimestamp($request_info['To']);
                        $nights = (int) $start->diff($finish)->format('%r%a');

                        $date .= "<br />{$lang['booking_nights']}: {$nights}";
                    }
                }
            }

            $mail_tpl['body'] = str_replace(
                array('{link}', '{renter}', '{BODY}', '{owner}', '{date}', '{amount}'),
                array(
                    $ad_link,
                    $renter,
                    $message ?: $lang['not_available'],
                    $owner,
                    $data['period_updated'] && $date ? $date : '',
                    $data['period_updated'] ? $price : '',
                ),
                $mail_tpl['body']
            );

            $rlMail->send($mail_tpl, $request_info['Renter_mail'], false, $request_info['Owner_mail']);
        }

        return $mess;
    }

    /**
     * Get booking fields
     *
     * @return array
     */
    public function getBookingFields()
    {
        global $configs, $rlDb, $rlLang, $lang;

        $where = $configs['Booking_type'] ? "AND FIND_IN_SET('{$configs['Booking_type']}', `Booking_type`) " : '';
        $where .= 'ORDER BY `Position`';

        $rlDb->outputRowsMap = 'Key';
        if ($fields = $rlDb->fetch('*', ['Status' => 'active'], $where, null, 'booking_fields')) {
            $fields = $rlLang->replaceLangKeys($fields, 'booking_fields', ['name', 'default', 'description']);

            foreach ($fields as &$field) {
                if (!$field['name'] && in_array($field['Key'], ['first_name', 'last_name', 'email'] )) {
                    $field['name'] = $field['Key'] === 'email' ? $lang['mail'] : $lang[$field['Key']];
                }
            }
        }

        return $fields;
    }

    /**
     * Get rate ranges
     *
     * @param  int   $listingID
     * @return bool
     */
    public function getRateRange($listingID)
    {
        global $lang, $config, $rlSmarty, $rlValid, $rlDb;

        $listingID = (int) $listingID;

        if (!$listingID) {
            return false;
        }

        $configs     = $this->getConfigs($listingID);
        $rate_ranges = $rlDb->fetch('*', ['Listing_ID' => $listingID], "ORDER BY `From`", null, 'booking_rate_range');

        if ($rate_ranges) {
            // get currency from listing details
            $price_tag = $config['booking_price_field_' . $configs['Listing_type']];
            $currency  = false;

            if ($rlDb->getRow("SHOW COLUMNS FROM `{db_prefix}listings` LIKE '{$price_tag}'")) {
                if ($currency = $rlDb->getOne($price_tag, "`ID` = {$listingID}", 'listings')) {
                    $currency = explode('|', $currency);
                    $currency = $currency[1];
                    $currency = $lang['data_formats+name+' . $currency] ?: false;
                }
            }

            foreach ($rate_ranges as &$range) {
                $description_key = $range['From'] . '_' . $range['To'] . '_' . $listingID;

                $range['From']  = date('d.m.Y', (int) $range['From']);
                $range['To']    = date('d.m.Y', (int) $range['To']);
                $range['Price'] = $range['Price'] ? $rlValid->str2money($range['Price']) : '';

                $currency = $currency ?: $config['system_currency'];

                if ($range['Price'] && $currency) {
                    $range['Price'] = $config['system_currency_position'] == 'before'
                    ? ($currency . ' ' . $range['Price'])
                    : ($range['Price'] . ' ' . $currency);
                }

                $range['Desc'] = $rlDb->getOne(
                    'Value',
                    "`Key`= 'booking_range+desc+{$description_key}' AND `Code` = '" . RL_LANG_CODE . "'",
                    'lang_keys'
                );
            }
        }

        // get description of regular price
        $phraseKey = "booking_range+regular+desc+{$listingID}";
        $range_regular_desc = $rlDb->getOne('Value', "`Key` = '{$phraseKey}'", 'lang_keys');

        $rlSmarty->assign_by_ref('range_regular_desc', $range_regular_desc);
        $rlSmarty->assign_by_ref('defPrice', $this->getRegularPrice($listingID));
        $rlSmarty->assign_by_ref('rate_ranges', $rate_ranges);
        $rlSmarty->assign_by_ref('use_time_frame', $this->use_time_frame);

        $_POST['f']['booking_availability'] = $this->getAvailability($listingID);
        $_POST['f']['booking_rates']        = $this->getRates($listingID);

        return true;
    }

    /**
     * Get availability for hourly booking mode (not escort)
     *
     * @since 3.2.0
     *
     * @param int  $listingID
     * @param bool $adaptData
     *
     * @return array
     */
    public function getAvailability($listingID, $adaptData = false)
    {
        $availability = [];
        $listingID    = (int) $listingID;

        if (!$listingID) {
            return $availability;
        }

        static $availabilityData = [];
        $typeInfo = $adaptData ? 'adapt' : 'base';

        if ($availabilityData[$typeInfo][$listingID]) {
            return $availabilityData[$typeInfo][$listingID];
        }

        global $rlDb, $config;

        if ($this->isEscort) {
            global $listing_data;

            if ($listing_data && isset($listing_data['availability_0'])) {
                foreach ($listing_data as $columnName => $listingAvailiability) {
                    if (false === strpos($columnName, 'availability_')) {
                        continue;
                    }

                    $day = (int) explode('_', $columnName)[1];
                    $day = $day === 0 ? 6 : $day - 1;
                    $data['Availability_' . $day] = $listingAvailiability;
                }
            }
        } else {
            $data = $rlDb->fetch('*', ['Listing_ID' => $listingID], null, null, 'booking_availability', 'row');
        }

        $configs    = $this->getConfigs($listingID);
        $timeRanges = $this->buildCheckInOutData($configs['Booking_type']);

        foreach ($data as $columnName => $value) {
            if (false === strpos($columnName, 'Availability')) {
                continue;
            }

            $day = (int) explode('_', $columnName)[1];

            if ($this->isEscort) {
                $day = $day + 1;
            }

            $originalFrom = $value ? intval(substr($value, 0, 2)) : 0;
            $originalTo   = $value ? intval(substr($value, 2, 4)) : 0;

            if ($adaptData) {
                $from = $originalFrom ? $timeRanges[$originalFrom] : 0;
                $to   = $originalTo ? $timeRanges[$originalTo] : 0;

                $day = $day === 0 ? 7 : $day;

                $availability[$day] = [
                    'day'          => $day,
                    'title'        => $this->weekdays['full'][$day === 0 ? 7 : $day],
                    'from'         => $from,
                    'to'           => $to,
                    'originalFrom' => $originalFrom,
                    'originalTo'   => $originalTo,
                ];
            } else {
                $availability[$day === 0 ? 7 : $day] = [
                    'from' => $originalFrom,
                    'to'   => $originalTo,
                ];
            }
        }

        if ($adaptData) {
            if ($this->isEscort && $config['first_day'] === '1'
                || !$this->isEscort && $config['booking_first_day'] === 'monday'
            ) {
                ksort($availability);
            }
        }

        $availabilityData[$typeInfo][$listingID] = $availability;

        return $availability;
    }

    /**
     * Get rates for hourly booking mode (not escort)
     *
     * @since 3.2.0
     *
     * @param int $listingID
     *
     * @return array
     */
    public function getRates($listingID)
    {
        $rates = [];

        if (!$listingID) {
            return $rates;
        }

        static $ratesData = [];

        if ($ratesData[$listingID]) {
            return $ratesData[$listingID];
        }

        global $rlDb;

        $data = $rlDb->fetch('*', ['Listing_ID' => $listingID], 'ORDER BY `ID`', null, 'booking_rate_range');
        foreach ($data as $rate) {
            $price = explode('|', $rate['Price']);

            $rates[] = [
                'title'    => $rate['Title'],
                'time'     => $rate['Time'],
                'type'     => $rate['Type'],
                'price'    => $price[0],
                'currency' => $price[1],
            ];
        }

        $ratesData[$listingID] = $rates;

        return $rates;
    }

    /**
     * Recount regular price of listing by selected time range
     *
     * @param  int   $listing_id - ID of listing
     * @return mixed             - Price, currency and total row
     */
    public function getRegularPrice($listing_id)
    {
        global $config, $rlValid, $rlDb;

        $listing_id        = (int) $listing_id;
        $configs           = $this->getConfigs($listing_id);
        $price_field       = $config['booking_price_field_' . $configs['Listing_type']];
        $time_frame_config = unserialize($config['booking_time_frame_field_' . $configs['Listing_type']]);
        $time_frame_field  = $time_frame_config[0] ?: false;

        $time_frame_mapping = false;
        if ($time_frame_config[1]) {
            foreach ($time_frame_config[1] as $time_frame_key => $time_frame_value) {
                if ($time_frame_value) {
                    $time_frame_mapping[$time_frame_key] = $time_frame_value;
                }
            }
        }

        $show_columns_sql = "SHOW COLUMNS FROM `{db_prefix}listings` LIKE ";

        if (!$time_frame_field
            || !$price_field
            || !$rlDb->getRow($show_columns_sql . "'{$time_frame_field}'")
            || !$rlDb->getRow($show_columns_sql . "'{$price_field}'")
        ) {
            return false;
        }

        // get listing price & time frame value
        $def_price = $rlDb->fetch(
            array($price_field, $time_frame_field),
            array('ID' => $listing_id),
            '',
            null,
            'listings'
        );

        $def_price              = $def_price[0] ?: false;
        list($price, $currency) = explode('|', $def_price[$price_field]);
        $currency               = $currency ? $GLOBALS['lang']['data_formats+name+' . $currency] : false;
        $price_cel              = 0;
        $adaptPrice             = [];

        // recount regular price by time range
        if ($this->checkAvailabilityRegularPriceMode($listing_id)) {
            if ($def_price[$time_frame_field] && $price && $currency && $time_frame_mapping) {
                $this->use_time_frame = true;

                switch ($def_price[$time_frame_field]) {
                    case $time_frame_mapping['hour']:
                        if ($configs['Booking_type'] == 'date_time_range') {
                            $price_cel = round($price * 24, 2);
                        } else {
                            $this->use_time_frame = false;
                        }
                        break;

                    case $time_frame_mapping['day']:
                        $price_cel = $price;
                        break;

                    case $time_frame_mapping['week']:
                        $price_cel = round($price / 7, 2);
                        break;

                    case $time_frame_mapping['month']:
                        $price_cel = round($price / date('t'), 2);
                        break;

                    case $time_frame_mapping['year']:
                        $price_cel = round($price / 365, 2);
                        break;

                    default:
                        $this->use_time_frame = false;
                        break;
                }
            }
        }

        if ($price_cel) {
            $adaptPrice['name'] = $config['system_currency_position'] == 'before'
            ? ($currency . ' ' . $rlValid->str2money($price_cel))
            : ($rlValid->str2money($price_cel) . ' ' . $currency);
        } else {
            $adaptPrice['name'] = false;
        }
        $adaptPrice['currency'] = $currency;
        $adaptPrice['value']    = $price_cel;

        if ($configs['Prepayment']) {
            self::convertPrice($adaptPrice);
        }

        return $adaptPrice;
    }

    /**
     * Checking the time frame and price field, all needed values
     *
     * @param  int  $listing_id - ID of listing
     * @return bool
     */
    public function checkAvailabilityRegularPriceMode($listing_id)
    {
        global $config, $rlDb;

        $listing_id        = (int) $listing_id;

        if (!$listing_id) {
            return false;
        }

        $configs           = $this->getConfigs($listing_id);
        $price_field       = $config['booking_price_field_' . $configs['Listing_type']];
        $time_frame_config = unserialize($config['booking_time_frame_field_' . $configs['Listing_type']]);
        $time_frame_field  = $time_frame_config[0] ?: false;

        $time_frame_mapping = [];
        if ($time_frame_config[1]) {
            foreach ($time_frame_config[1] as $time_frame_key => $time_frame_value) {
                if ($time_frame_value) {
                    $time_frame_mapping[$time_frame_key] = $time_frame_value;
                }
            }
        }

        $check_column_sql           = "SHOW COLUMNS FROM `{db_prefix}listings` LIKE ";
        $price_field_exist_sql      = $check_column_sql . "'" . $price_field . "'";
        $time_frame_field_exist_sql = $check_column_sql . "'" . $time_frame_field . "'";

        if (!$price_field || !$time_frame_field) {
            if ($configs['Booking_type'] == 'time_range') {
                if ($this->isEscort) {
                    $listing_data = $GLOBALS['rlListings']->getListing($listing_id);

                    // check availability data
                    $availability_exist = false;
                    for ($i = 0; $i < 7; $i++) {
                        if ($listing_data['availability_' . $i]) {
                            $availability_exist = true;
                            break;
                        }
                    }

                    if ($listing_data['booking_module']
                        && $availability_exist
                        && $rlDb->getOne('ID', "`Listing_ID` = {$listing_id} AND `Price` <> ''", 'escort_rates')
                    ) {
                        return true;
                    }
                } else {
                    $availability = $rlDb->getOne('ID', "`Listing_ID` = {$listing_id}", 'booking_availability');
                    $rates        = $rlDb->getOne('ID', "`Listing_ID` = {$listing_id}", 'booking_rate_range');

                    return $availability && $rates;
                }
            }

            return false;
        } else {
            if (!$rlDb->getRow($price_field_exist_sql) || !$rlDb->getRow($time_frame_field_exist_sql)) {
                return false;
            }
        }

        // get listing price & time frame value
        $def_price = $rlDb->fetch([$price_field, $time_frame_field], ['ID' => $listing_id], '', null, 'listings');
        $def_price              = $def_price[0] ?: false;
        list($price, $currency) = explode('|', $def_price[$price_field]);

        if ($def_price[$time_frame_field]
            && $price
            && $time_frame_mapping
            && in_array($def_price[$time_frame_field], $time_frame_mapping)
        ) {
            $this->use_time_frame = true;
        } else {
            $this->use_time_frame = false;

            return false;
        }

        return true;
    }

    /**
     * Ajax save rate range
     *
     * @param  int   $listing_id - ID of listing
     * @param  array $rate_range - Data from rate range form
     * @return mixed             - Message if rate range added or false
     */
    public function ajaxSaveRateRange($listing_id, $rate_range)
    {
        global $rlDb, $languages, $rlLang;

        $listing_id = (int) $listing_id;
        $key        = 0;

        foreach ($rate_range as $range) {
            $adapt_range[$key] = $range['value'];
            $key++;
        }

        // add new rate range to booking rate ranges table
        $from = $adapt_range[0]
        ? mktime(0, 0, 0,
            substr($adapt_range[0], 3, 2),
            substr($adapt_range[0], 0, 2),
            substr($adapt_range[0], 6, 4)
        )
        : false;

        $to = $adapt_range[1]
        ? mktime(0, 0, 0,
            substr($adapt_range[1], 3, 2),
            substr($adapt_range[1], 0, 2),
            substr($adapt_range[1], 6, 4)
        )
        : false;

        $insert = array(
            'Listing_ID' => $listing_id,
            'From'       => $from,
            'To'         => $to,
            'Price'      => (float) $adapt_range[2],
        );

        if ($rlDb->insertOne($insert, 'booking_rate_range')) {
            // add description
            if ($adapt_range[3]) {
                $description     = $adapt_range[3];
                $description_key = "booking_range+desc+{$from}_{$to}_{$listing_id}";
                $createPhrases  = [];
                foreach ($languages as $lang_item) {
                    $createPhrases[] = array(
                        'Code'   => $lang_item['Code'],
                        'Module' => 'common',
                        'Status' => 'active',
                        'Key'    => $description_key,
                        'Value'  => $description,
                        'Plugin' => 'booking',
                    );
                }

                if ($createPhrases) {
                    if (method_exists($rlLang, 'createPhrases')) {
                        $rlLang->createPhrases($createPhrases);
                    } else {
                        $rlDb->insert($createPhrases, 'lang_keys');
                    }
                }
            }

            return $GLOBALS['lang']['booking_rate_range_added'];
        }

        return false;
    }

    /**
     * @see removeRequestData
     */
    public function ajaxDeleteRequestAP($id)
    {
        return $this->removeRequestData($id);
    }

    /**
     * @see removeRequestData
     */
    public function ajaxDeleteRequest($id)
    {
        return $this->removeRequestData($id);
    }

    /**
     * Remove request data
     *
     * @since 3.0.0
     *
     * @param  int   $id - ID of request
     * @return mixed
     */
    public function removeRequestData($id)
    {
        global $rlDb;

        $id      = (int) $id;
        $book_id = $rlDb->getOne('Book_ID', "`ID` = {$id}", 'booking_requests');

        if (!$book_id) {
            return false;
        }

        $rlDb->query('DELETE FROM `' . RL_DBPREFIX . "booking_requests` WHERE `ID` = {$id} LIMIT 1");
        $rlDb->query('DELETE FROM `' . RL_DBPREFIX . "listings_book` WHERE `ID` = {$book_id} LIMIT 1");

        return $GLOBALS['rlLang']->getPhrase('ext_booking_request_removed', null, null, true);
    }

    /**
     * Ajax save binding days
     *
     * @param  int    $listing_id - ID of listing
     * @param  array  $formData   - Data of binding days
     * @return string
     */
    public function ajaxSaveBindingDays($listing_id, $formData = array())
    {
        global $rlDb;

        if (!$listing_id = (int) $listing_id) {
            return '';
        }

        foreach ($formData as $value) {
            if ($value['name'] == 'in') {
                $checkin = $checkin ? $checkin . ',' . $value['value'] : $value['value'];
            } else {
                $checkout = $checkout ? $checkout . ',' . $value['value'] : $value['value'];
            }
        }

        if ($rlDb->getOne('ID', "`Listing_ID` = {$listing_id}", 'booking_bindings')) {
            $rlDb->updateOne([
                'fields' => ['Checkin'  => $checkin ?: '', 'Checkout' => $checkout ?: ''],
                'where'  => ['Listing_ID' => $listing_id],
            ], 'booking_bindings');
        } else {
            $rlDb->insertOne([
                'Listing_ID' => $listing_id,
                'Checkin'    => $checkin ?: '',
                'Checkout'   => $checkout ?: '',
            ], 'booking_bindings');
        }

        return $GLOBALS['rlLang']->getPhrase('booking_bindings_saved', null, null, true);
    }

    /**
     * Save rate ranges from listing
     *
     * @param  object $instance - Instance of class (add/edit listing)
     * @return bool
     */
    public function saveRateRangesFromListing($instance = null)
    {
        global $languages, $rlDb, $rlLang;

        if ($instance && $instance->listingID) {
            $listingID = (int) $instance->listingID;
        } elseif ($GLOBALS['listing_id']) {
            $listingID = (int) $GLOBALS['listing_id'];
        }

        if (!$listingID) {
            return false;
        }

        $rate_ranges  = $_POST['b'];
        $availability = $_POST['f']['booking_availability'];
        $rates        = $_POST['f']['booking_rates'];

        if ($_POST['f']['booking_module']) {
            if ($rate_ranges) {
                $createPhrases = [];

                foreach ($rate_ranges as $range) {
                    $from = $to = 0;

                    if ($range['from'] && $range['to']) {
                        $from = mktime(0, 0, 0,
                            substr($range['from'], 3, 2),
                            substr($range['from'], 0, 2),
                            substr($range['from'], 6, 4)
                        );

                        $to = mktime(0, 0, 0,
                            substr($range['to'], 3, 2),
                            substr($range['to'], 0, 2),
                            substr($range['to'], 6, 4)
                        );

                        $rlDb->insertOne([
                            'Listing_ID' => $listingID,
                            'From'       => $from,
                            'To'         => $to,
                            'Price'      => (double) $range['price'],
                        ], 'booking_rate_range');
                    }

                    $description = strip_tags($range['desc']);

                    if ($description && $from && $to) {
                        $descriptionKey = "booking_range+desc+{$from}_{$to}_{$listingID}";

                        foreach ($languages as $lang_item) {
                            if (!$rlDb->getOne(
                                'ID',
                                "`Key` = '{$descriptionKey}' AND `Code` = '{$lang_item['Code']}'",
                                'lang_keys')
                            ) {
                                $createPhrases[] = [
                                    'Code'   => $lang_item['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => $descriptionKey,
                                    'Value'  => $description,
                                    'Plugin' => 'booking',
                                ];
                            }
                        }
                    }
                }

                // Add description of regular price
                if ($rate_ranges['regular'] && $rate_ranges['regular']['desc']) {
                    $description    = strip_tags($rate_ranges['regular']['desc']);
                    $descriptionKey = "booking_range+regular+desc+{$listingID}";

                    foreach ($languages as $lang_item) {
                        if (!$rlDb->getOne(
                            'ID',
                            "`Key` = '{$descriptionKey}' AND `Code` = '{$lang_item['Code']}'",
                            'lang_keys')
                        ) {
                            $createPhrases[] = [
                                'Code'   => $lang_item['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => $descriptionKey,
                                'Value'  => $description,
                                'Plugin' => 'booking',
                            ];
                        }
                    }
                }

                if ($createPhrases) {
                    if (method_exists($rlLang, 'createPhrases')) {
                        $rlLang->createPhrases($createPhrases);
                    } else {
                        $rlDb->insert($createPhrases, 'lang_keys');
                    }
                }
            }

            $availabilityData = ['Listing_ID' => $listingID];
            foreach ($availability as $index => $time) {
                $time = $time['from'] && $time['to'] ? $time['from'] . $time['to'] : 0;
                $availabilityData['Availability_' . ($index === 7 ? 0 : $index)] = $time;
            }

            if (count($availabilityData) > 1) {
                if ($rlDb->getOne('ID', "`Listing_ID` = '{$listingID}'", 'booking_availability')) {
                    $rlDb->updateOne([
                        'fields' => $availabilityData,
                        'where'  => ['Listing_ID' => $listingID],
                    ], 'booking_availability');
                } else {
                    $rlDb->insertOne($availabilityData, 'booking_availability');
                }
            }

            foreach ($rates as $rate) {
                if ($rate['title'] && $rate['time'] && $rate['price']) {
                    $insertRates[] = [
                        'Listing_ID' => $listingID,
                        'Title'      => $rate['title'],
                        'Type'       => $rate['type'] ?: 'multi',
                        'Time'       => $rate['time'],
                        'Price'      => $rate['price'] . '|' . $rate['currency']
                    ];
                }
            }

            if ($insertRates) {
                $rlDb->query("DELETE FROM `{db_prefix}booking_rate_range` WHERE `Listing_ID` = {$listingID}");
                $rlDb->insert($insertRates, 'booking_rate_range');
            }

            // Edit check-in/check-out/regular_mode fields
            $rlDb->updateOne([
                'fields' => [
                    'booking_check_in'     => $_POST['f']['booking_check_in'] ?: '',
                    'booking_check_out'    => $_POST['f']['booking_check_out'] ?: '',
                    'booking_regular_mode' => $this->checkAvailabilityRegularPriceMode($listingID) ? 1 : 0,
                ],
                'where'  => ['ID' => $listingID],
            ], 'listings');
        } else {
            $this->_deleteListingData($listingID);

            // Remove info in check-in/check-out/regular_mode fields
            $rlDb->updateOne([
                'fields' => [
                    'booking_check_in'     => '',
                    'booking_check_out'    => '',
                    'booking_regular_mode' => 0,
                ],
                'where'  => ['ID' => $listingID],
            ], 'listings');
        }

        return true;
    }

    /**
     * Get info about booking requests
     *
     * @param  int   $listing_id - ID of listing
     * @return mixed
     */
    public function getRequests($listing_id = 0)
    {
        global $account_info, $bread_crumbs, $lang;

        if (!defined('IS_LOGIN') || !$account_info['ID']) {
            return false;
        }

        if ($listing_id) {
            // get listing info
            $listing_details = $GLOBALS['rlListings']->getListing($listing_id, true, false);

            // modify bread crumbs
            if (!$GLOBALS['config']['one_my_listings_page']) {
                $myPage = $GLOBALS['rlListingTypes']->types[$listing_details['Listing_type']]['My_key'];
            } else {
                $myPage = 'my_all_ads';
            }

            $bread_crumbs[1] = array(
                'title' => $lang['pages+title+' . $myPage],
                'name'  => $lang['pages+name+' . $myPage],
                'path'  => $GLOBALS['pages'][$myPage],
            );
            $bread_crumbs[2] = array(
                'title' => $lang['pages+title+booking_details'],
                'name'  => $lang['pages+name+booking_details'],
            );

            $GLOBALS['page_info']['title'] = $lang['pages+title+' . $myPage];

            if (!$listing_details['ID'] || $listing_details['Account_ID'] != $account_info['ID']) {
                $GLOBALS['sError'] = true;
                return false;
            }
        }

        $sql = "SELECT `T2`.`ID` AS `Request_ID`, `T2`.`Status` AS `Book_status`, `T2`.`Renter_ID`, ";
        $sql .= "`T1`.`From` AS `Request_from`,`T1`.`To` AS `Request_to`, `T6`.`Booking_type`, ";
        $sql .= "IF(`Renter_ID` > 0, CONCAT(`T4`.`First_name`, ' ', `T4`.`Last_name`), ";
        $sql .= "CONCAT(`T2`.`first_name`, ' ', `T2`.`last_name`)) AS `Author`, `T4`.`Username`, ";
        $sql .= "`T1`.`ID` AS `Req_ID`,`T1`.`Status` AS `Req_status`,`T3`.*, `T5`.`Path`, `T5`.`Type`, ";
        $sql .= "`T1`.`Checkout` ";
        $sql .= "FROM `{db_prefix}listings_book` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}booking_requests` AS `T2` ON `T1`.`ID` = `T2`.`Book_ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listings` AS `T3` ON `T1`.`Listing_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T4` ON `T4`.`ID` = `T2`.`Renter_ID` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T5` ON `T3`.`Category_ID` = `T5`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_types` AS `T6` ON `T5`.`Type` = `T6`.`Key` ";
        $sql .= "WHERE `T2`.`Owner_ID` = {$account_info['ID']} AND `T6`.`Booking_type` <> 'none' ";

        if ($listing_id) {
            $sql .= "AND `T3`.`ID` = {$listing_id} ";
        }

        $sql .= "ORDER BY `T2`.`Status`, `T2`.`Date` DESC";
        $tmp_requests = $GLOBALS['rlDb']->getAll($sql);

        if ($tmp_requests) {
            foreach ($tmp_requests as $request) {
                $index = $request['Req_ID'];

                $requests[$index]['ltype']      = $request['Type'];
                $requests[$index]['Path']       = $request['Path'];
                $requests[$index]['Listing_ID'] = $request['ID'];

                if (!$listing_details || !$listing_id) {
                    // get listing info
                    $listing_details = $GLOBALS['rlListings']->getListing($request['ID'], true, false);
                }

                $requests[$index]['title']       = $listing_details['listing_title'];
                $requests[$index]['status']      = $request['Req_status'];
                $requests[$index]['Book_status'] = $request['Book_status'];
                $requests[$index]['Author']      = trim($request['Author']) ?: $request['Username'];
                $requests[$index]['ID']          = $request['Request_ID'];
                $requests[$index]['From']        = $request['Request_from'];
                $requests[$index]['To']          = $request['Booking_type'] == 'time_range'
                ? 0
                : $request['Request_to'];
                $requests[$index]['Booking_type'] = $request['Booking_type'];
                $requests[$index]['url']          = $this->getRequestUrl($request['title'], $request['Request_ID']);

                if ($GLOBALS['plugins']['ref'] && $request['ref_number']) {
                    $requests[$index]['ref_number'] = $request['ref_number'];
                }

                // add link for renter or label with "website visitor"
                if ($request['Renter_ID']) {
                    $renter                     = $GLOBALS['rlAccount']->getProfile((int) $request['Renter_ID']);
                    $requests[$index]['Author'] = $renter['Personal_address']
                    ? '<a href="' . $renter['Personal_address'] . '">' . $requests[$index]['Author'] . '</a>'
                    : $requests[$index]['Author'];
                } else {
                    $requests[$index]['Author'] .= ' (' . $lang['website_visitor'] . ')';
                }

                if ($request['Booking_type'] === 'time_range' && $request['Checkout']) {
                    $timePeriods = $this->buildCheckInOutData($request['Booking_type']);
                    $requests[$index]['Checkout'] = $timePeriods[$request['Checkout']];
                }
            }

            return $requests;
        } else {
            return false;
        }
    }

    /**
     * Generate a URL for a booking request based on the title and ID.
     *
     * This function constructs a URL for a booking request page. If URL rewriting
     * is enabled, it appends a path-friendly version of the title and ID to the URL.
     * Otherwise, it uses query parameters to include the ID.
     *
     * @since 3.4.1
     *
     * @param  string $title The title of the booking request.
     * @param  int    $id    The ID of the booking request.
     * @return string        The constructed URL for the booking request.
     */
    public function getRequestUrl($title, $id)
    {
        global $reefless, $config, $rlSmarty;

        $addUrl = [];
        $addVars = '';
        if ($config['mod_rewrite']) {
            $addUrl[] = $rlSmarty->str2path("{$title}-r{$id}");
        } else {
            $addVars = "id={$id}";
        }

        return $reefless->getPageUrl('booking_requests', $addUrl, null, $addVars);
    }

    /**
     * Get info about current booking request
     *
     * @param  int   $id - ID of booking request
     * @return mixed
     */
    public function getRequestDetails($id)
    {
        global $account_info, $bread_crumbs, $lang, $rlSmarty, $rlEscort, $rlListings, $rlDb;

        $id = (int) $id;
        static $request = null;

        if (!defined('IS_LOGIN') || !$account_info['ID'] || !is_null($request)) {
            return $request;
        }

        $fields = $this->getBookingFields();

        $sql = "SELECT `T1`.`ID` AS `Req_ID`, `T3`.`ID` AS `Listing_ID`, `T1`.`Amount`, `T1`.`Status` AS `Stat`, ";
        $sql .= "`T2`.`Status` AS `Req_status`,`T3`.*,`T1`.`From`,`T1`.`To`,`T2`.*, `T4`.`Type`, `T4`.`Path`, ";
        $sql .= "`T1`.`Checkin`, `T1`.`Checkout`, `T5`.`Booking_type` ";
        $sql .= "FROM `{db_prefix}listings_book` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}booking_requests` AS `T2` ON `T1`.`ID` = `T2`.`Book_ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listings` AS `T3` ON `T1`.`Listing_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T4` ON `T3`.`Category_ID` = `T4`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_types` AS `T5` ON `T4`.`Type` = `T5`.`Key` ";
        $sql .= "WHERE `T2`.`Owner_ID` = {$account_info['ID']} AND `T1`.`ID` = {$id}";
        $request = $rlDb->getRow($sql);

        if ($request) {
            $request['Status_request'] = $request['Stat'];
            $request['Stat']           = str_replace(
                array('process', 'booked', 'refused'),
                array($lang['pending'], $lang['booking_accepted'], $lang['booking_refused']),
                $request['Stat']
            );

            // get listing data
            $listingID             = $request['Listing_ID'];
            $listing_details        = $rlListings->getListing($listingID, true, true);
            $listing_details['url'] = $listing_details['listing_link'];

            // get escort system data
            if ($this->isEscort
                && class_exists('rlEscort')
                && method_exists($rlEscort, 'formFieldsMergeWithListingData')
            ) {
                global $listing_data;

                $listing_data = $listing_details;
                $rlEscort->formFieldsMergeWithListingData();

                $listingTypeKey = $GLOBALS['rlListingTypes']->types[$listing_data['Listing_type']];

                // build listing structure
                $listing_data_escort = $rlListings->getListingDetails(
                    $listing_data['Category_ID'],
                    $listing_data,
                    $listingTypeKey
                );

                $this->convertPricesInRates(
                    $listing_data_escort['escort_rates']['Fields']['escort_rates']['value'],
                    true
                );

                $rlSmarty->assign_by_ref('listing_escort', $listing_data_escort);
            }

            // add fields from booking form
            foreach ($fields as $key => $field) {
                if (in_array($request['Booking_type'], explode(',', $field['Booking_type']))) {
                    $request['fields'][$key] = $field;

                    switch ($field['Type']) {
                        case 'text':
                        case 'textarea':
                        case 'number':
                            if ($request[$field['Key']]) {
                                if ($field['Condition'] == 'isEmail') {
                                    $request['fields'][$key]['value'] = '<a href="mailto:' . $request[$field['Key']];
                                    $request['fields'][$key]['value'] .= '">' . $request[$field['Key']] . '</a>';
                                } else if ($field['Condition'] == 'isUrl') {
                                    $request['fields'][$key]['value'] = '<a title="" href="' . $request[$field['Key']];
                                    $request['fields'][$key]['value'] .= '">' . $request[$field['Key']] . '</a>';
                                } else {
                                    $request['fields'][$key]['value'] = $request[$field['Key']];
                                }
                            } else {
                                $request['fields'][$key]['value'] = $field['Type'] != 'textarea'
                                ? $lang['not_available']
                                : '';
                            }

                            break;
                        case 'bool':
                            $request['fields'][$key]['value'] = $request[$field['Key']] ? $lang['yes'] : $lang['no'];
                            break;
                    }
                }
            }

            // update data in breadcrumbs and name of page
            $request_url = $GLOBALS['pages']['booking_requests'] . '/';
            $request_url .= $rlSmarty->str2path($listing_details['listing_title']) . '-r' . $id;

            $bread_crumbs[2] = array(
                'name' => $lang['booking_page_details'],
                'path' => $request_url,
            );

            $GLOBALS['page_info']['name'] = $lang['booking_page_details'];

            // get rate ranges
            $this->getRateRange($listingID);

            // build checkin-checkout dates
            $date_format = str_replace('%', '', RL_DATE_FORMAT);
            $checkIn     = date(str_replace('b', 'M', $date_format), $request['From']);
            $checkOut    = date(str_replace('b', 'M', $date_format), $request['To']);

            $configs      = $this->getConfigs($listingID, true);
            $timePeriods = $this->buildCheckInOutData($configs['Booking_type']);
            $nights      = 0;

            if ($configs['Booking_type'] === 'time_range') {
                $request['Checkout'] = $timePeriods[$request['Checkout']];

                $rlSmarty->assign('bookingAvailability', $this->getAvailability($listingID, true));

                if (!$this->isEscort) {
                    $rates = $this->getRates($listingID);
                    $this->convertPricesInRates($rates);
                    $rlSmarty->assign('bookingRates', $rates);
                }
            } else if ($configs['Booking_type'] === 'date_time_range') {
                $request['Checkin']  = $timePeriods[$request['Checkin']] ?: $request['Checkin'];
                $request['Checkout'] = $timePeriods[$request['Checkout']] ?: $request['Checkout'];
            } else {
                $request['booking_check_in']  = $timePeriods[$request['booking_check_in']];
                $request['booking_check_out'] = $timePeriods[$request['booking_check_out']];

                $start  = (new DateTime())->setTimeStamp($request['From']);
                $finish  = (new DateTime())->setTimestamp($request['To']);
                $nights = (int) $start->diff($finish)->format('%r%a');
            }

            // collect booking data
            $bookingData = array(
                'listing_id'       => $listingID,
                'start_date'       => $request['From'],
                'end_date'         => $request['To'],
                'checkin'          => $request['Checkin'],
                'checkout'         => $request['Checkout'],
                'start_date_value' => $checkIn,
                'end_date_value'   => $checkOut,
                'amount'           => $request['Amount'],
                'nights'           => $nights,
            );

            $rlSmarty->assign_by_ref('listing', $listing_details);
            $rlSmarty->assign_by_ref('defPrice', $this->getRegularPrice($listingID));
            $rlSmarty->assign_by_ref('bookingConfigs', $configs);
            $rlSmarty->assign_by_ref('bookingData', $bookingData);
            $rlSmarty->assign_by_ref('booking_time_range', $this->buildCheckInOutData($configs['Booking_type']));

            return $request;
        } else {
            $GLOBALS['sError'] = true;

            return false;
        }
    }

    /**
     * Build data for time range fields (check-in, check-out)
     *
     * @since 3.2.0 - Added support of 24-hour clock format
     *
     * @param string $bookingType
     */
    public function buildCheckInOutData($bookingType = 'date_range')
    {
        global $lang, $config;

        $clockSystem = intval($config['clock_system'] ?: ($config['booking_clock_system'] ?: 12));
        $hour        = $clockSystem == 12 ? 12 : 0;
        $timeRange   = [];
        $amPhrase    = $lang['time_f12_am'] ?: $lang['booking_time_am'];
        $pmPhrase    = $lang['time_f12_pm'] ?: $lang['booking_time_pm'];

        static $checkInCheckOutData;

        if ($checkInCheckOutData[$bookingType]) {
            return $checkInCheckOutData[$bookingType];
        }

        for ($index = 10; $index <= 57; $index++) {
            $hourSt    = $hour < 10 ? "0{$hour}" : $hour;
            $minute    = $index % 2 == 0 ? '00' : '30';
            $timeValue = "{$hourSt}:{$minute}";

            if ($clockSystem == 12) {
                $timeValue .= " ";
                $timeValue .= ($index < 34) ? $amPhrase : $pmPhrase;
            }

            $timeRange[$index] = $timeValue;

            if (($index == 11 || $index == 35) && $clockSystem == 12) {
                $hour = 1;
            } else if ($index == 34) {
                $hour = 12;
            } else if ($index % 2 != 0) {
                $hour++;
            }
        }

        // Add additional 12 hours of next day
        if ($bookingType == 'time_range') {
            $hour          = $clockSystem == 12 ? 12 : 0;
            $nextDayPrefix = ' ' . ($hour == 12 ? $amPhrase . ' ' : '') . $lang['booking_time_next_day'];

            for ($index = 10; $index <= 30; $index++) {
                $hourSt      = $hour < 10 ? "0{$hour}" : $hour;
                $minute      = $index % 2 == 0 ? '00' : '30';
                $timeRange[] = "{$hourSt}:{$minute}" . $nextDayPrefix;

                if (($index == 11 || $index == 35) && $clockSystem == 12) {
                    $hour = 1;
                } else if ($index == 34) {
                    $hour = 12;
                } else if ($index % 2 != 0) {
                    $hour++;
                }
            }
        }

        $checkInCheckOutData[$bookingType] = $timeRange;

        return $timeRange;
    }

    /**
     * Prepare data for booking process.
     * Get and assign necessary variables in smarty
     */
    public function prepareBookingOrder()
    {
        global $reefless, $rlSmarty, $rlListingTypes, $bread_crumbs, $config, $configs, $step,
               $blocks, $rlEscort, $listing_data, $rlListings, $lang;

        // clear data in session of payment has been canceled
        if (isset($_GET['canceled'])) {
            $reefless->loadClass('Notice');
            $GLOBALS['rlNotice']->saveNotice($lang['payment_canceled'], 'error');
        }

        // saving client details to session
        if ($_SESSION['bookingData'] && isset($_POST['booking_order'])) {
            foreach ($_POST as $key => $value) {
                if ($key != 'booking_order' && $value) {
                    $_SESSION['bookingData']['fields'][$key] = $value;
                }
            }
        }

        // get data from session || post
        if ($_SESSION['bookingData']) {
            // update the booking data if user has changed booking dates
            if ($_POST['listing_id']) {
                foreach ($_POST as $key => $value) {
                    $_SESSION['bookingData'][$key] = $bookingData[$key] = $key == 'listing_id' ? (int) $value : $value;
                }
            } else {
                foreach ($_SESSION['bookingData'] as $key => $value) {
                    $bookingData[$key] = $key == 'listing_id' ? (int) $value : $value;
                }
            }
        } else if ($_POST['listing_id']) {
            foreach ($_POST as $key => $value) {
                $_SESSION['bookingData'][$key] = $bookingData[$key] = $key == 'listing_id' ? (int) $value : $value;
            }
        }

        // detect and assign step
        if (($config['mod_rewrite'] && $_GET['nvar_1'] == 'contact-details')
            || (!$config['mod_rewrite'] && $_GET['step'] == 'contact-details')
        ) {
            $step = 'fields';
        } else if (($config['mod_rewrite'] && $_GET['nvar_1'] == 'prepayment')
            || (!$config['mod_rewrite'] && $_GET['step'] == 'prepayment')
        ) {
            $step = 'prepayment';
        }
        $rlSmarty->assign_by_ref('step', $step);

        // detect "edit" action
        if (isset($_GET['edit'])) {
            $rlSmarty->assign('edit_action', true);
        }

        if ($bookingData['listing_id']) {
            $configs = $this->getConfigs($bookingData['listing_id'], true);
        }

        if ($bookingData['listing_id']
            && $bookingData['start_date']
            && $bookingData['start_date_value']
            && ($bookingData['end_date'] || $configs['Booking_type'] == 'time_range')
            && ($bookingData['end_date_value'] || $configs['Booking_type'] == 'time_range')
            && ($bookingData['total_cost'] || $configs['Booking_type'] == 'time_range')
            && $bookingData['total_days']
        ) {
            // Get the listing data (with title and URL)
            $reefless->loadClass('Listings');
            $listing_data = $rlListings->getListing($bookingData['listing_id'], true, true);

            // get escort system data
            if ($this->isEscort
                && class_exists('rlEscort')
                && method_exists($rlEscort, 'formFieldsMergeWithListingData')
            ) {
                $rlEscort->formFieldsMergeWithListingData();

                $listingTypeKey = $GLOBALS['rlListingTypes']->types[$listing_data['Listing_type']];

                // build listing structure
                $listing_data_escort = $rlListings->getListingDetails(
                    $listing_data['Category_ID'],
                    $listing_data,
                    $listingTypeKey
                );

                $this->convertPricesInRates(
                    $listing_data_escort['escort_rates']['Fields']['escort_rates']['value'],
                    true
                );

                $rlSmarty->assign_by_ref('listing_escort', $listing_data_escort);
            }

            $bread_crumbs[1] = ['name' => $listing_data['listing_title'], 'url'  => $listing_data['listing_link']];
            $bread_crumbs[]  = ['name' => $lang['pages+name+booking_order']];

            if ($configs['Booking_type'] === 'time_range') {
                $rlSmarty->assign('bookingAvailability', $this->getAvailability($listing_data['ID'], true));

                if (!$this->isEscort) {
                    $rates = $this->getRates($listing_data['ID']);
                    $this->convertPricesInRates($rates);
                    $rlSmarty->assign('bookingRates', $rates);
                }
            } else {
                $checkInCheckOutData = $this->buildCheckInOutData($configs['Booking_type']);

                if ($bookingData['checkin']) {
                    $bookingData['checkin']  = $checkInCheckOutData[$bookingData['checkin']];
                }

                if ($bookingData['checkout']) {
                    $bookingData['checkout'] = $checkInCheckOutData[$bookingData['checkout']];
                }
            }

            $rlSmarty->assign_by_ref('bookingData', $bookingData);
            $rlSmarty->assign_by_ref('listing', $listing_data);
            $rlSmarty->assign_by_ref('listing_types', $rlListingTypes->types);
            $rlSmarty->assign_by_ref('bookingConfigs', $configs);
            $rlSmarty->assign_by_ref('fields', $this->getBookingFields());
            $rlSmarty->assign_by_ref('booking_time_range', $this->buildCheckInOutData($configs['Booking_type']));

            // get rate ranges and other booking data
            $this->getRateRange($bookingData['listing_id']);
        } else {
            unset($blocks['booking_order_details_1'], $blocks['booking_order_details_2']);

            foreach ($blocks as $value) {
                if ($value['Side'] == 'left') {
                    $blocks['left'] = 1;
                    break;
                } else {
                    $blocks['left'] = 0;
                }
            }
        }
    }

    /**
     * Submit new booking request
     *
     * @since 3.0.0
     *
     * @param array $bookingData
     */
    public function bookingOrder($bookingData = array())
    {
        global $config, $lang, $rlValid, $rlMail, $reefless, $rlDb, $rlEscort, $rlListings;

        $listingID    = (int) ($_SESSION['bookingData']['listing_id'] ?: $bookingData['listing_id']);
        $configs       = $this->getConfigs($listingID);
        $from         = (int) ($_SESSION['bookingData']['start_date'] ?: $bookingData['start_date']);
        $to           = (float) ($_SESSION['bookingData']['end_date'] ?: $bookingData['end_date']);
        $to           = $to == -1 && $configs['Booking_type'] === 'time_range' && $this->isEscort ? 0 : $to;
        $amount       = $_SESSION['bookingData']['total_cost'] ?: $bookingData['total_cost'];
        $checkInTime  = $_SESSION['bookingData']['checkin'] ?? $bookingData['checkin'];
        $checkOutTime = $_SESSION['bookingData']['checkout'] ?? $bookingData['checkout'];
        $formData     = [];

        // unset temporary data
        unset($_POST['f']);

        if (isset($_POST['booking_order'])) {
            foreach ($_POST as $key => $tmp_value) {
                if ($key != 'booking_order') {
                    $value = $rlValid->xSql(strip_tags($tmp_value));
                    $formData[] = array('name' => $key, 'value' => $value);
                }
            }
        } else {
            foreach ($bookingData['fields'] as $key => $tmp_value) {
                if ($tmp_value) {
                    $value = $rlValid->xSql(strip_tags($tmp_value));
                    $formData[] = array('name' => $key, 'value' => $value);
                }
            }
        }

        if (!$listingID || !$from || (!$to && $configs['Booking_type'] != 'time_range')) {
            return false;
        }

        $sql = "INSERT INTO `{db_prefix}listings_book` ";
        $sql .= "(`Listing_ID`, `From`, `Checkin`, `To`, `Checkout`, `Amount`) ";
        $sql .= "VALUES ({$listingID}, '{$from}', '{$checkInTime}', '{$to}', '{$checkOutTime}', '{$amount}')";
        $rlDb->query($sql);

        $id = $rlDb->insertID();

        $renter_id = intval($bookingData['account_id'] ?: $_SESSION['account']['ID']);

        $fields = $values = '';

        if ($formData) {
            foreach ($formData as $data) {
                $field_keys[] = $data['name'];
                $fields       = $fields ? $fields . ', `' . $data['name'] . '`' : '`' . $data['name'] . '`';
                $values       = $values ? $values . ', \'' . $data['value'] . '\'' : '\'' . $data['value'] . '\'';
            }
        }

        $reefless->loadClass('Listings');
        $listing_info = $rlListings->getListing($listingID, true);

        $sql = "INSERT INTO `{db_prefix}booking_requests` ";
        $sql .= "(`Book_ID`, `Owner_ID`, `Renter_ID`, `Date`" . ($fields ? ", {$fields}) " : ") ");
        $sql .= "VALUES ({$id}, {$listing_info['Account_ID']}, {$renter_id}, NOW()" . ($values ? ", {$values})" : ")");
        $rlDb->query($sql);

        // get booking configs of current listing
        $configs = $this->getConfigs($listingID);

        if ($config['booking_notify_admin_by_email'] || $config['booking_notify_email']) {
            // adapt price
            $price_field            = $config['booking_price_field_' . $configs['Listing_type']];
            list($price, $currency) = explode('|', $listing_info[$price_field], 2);
            $currency               = $lang['data_formats+name+' . $currency] ?: $config['system_currency'];

            $prepayment = '';
            if ($configs['Prepayment']) {
                $prepayment = $lang['booking_prepayment_step'] . ' (' . $configs['Prepayment'] . ' %): ';
                $prepayment .= $config['system_currency_position'] == 'before'
                ? $config['system_currency'] . ' ' . $bookingData['prepayment']
                : $bookingData['prepayment'] . ' ' . $config['system_currency'];

                $prepayment .= '<br />' . $lang['booking_rest_phrase_mail'] . ': ';
                $prepayment .= $config['system_currency_position'] == 'before'
                ? $config['system_currency'] . ' ' . $bookingData['total_cost']
                : $bookingData['total_cost'] . ' ' . $config['system_currency'];
                $prepayment .= ' - ' . ($config['system_currency_position'] == 'before'
                    ? $config['system_currency'] . ' ' . $bookingData['prepayment']
                    : $bookingData['prepayment'] . ' ' . $config['system_currency']);

                $rest = $rlValid->str2money(
                    round(
                        (floatval(preg_replace('/[^0-9.]/', '', $bookingData['total_cost'])))
                        - floatval(preg_replace('/[^0-9.]/', '', $bookingData['prepayment'])),
                        2
                    )
                );

                $prepayment .= ' = ' . ($config['system_currency_position'] == 'before'
                    ? $config['system_currency'] . ' ' . $rest
                    : $rest . ' ' . $config['system_currency']);

                $currency = $config['system_currency'];
            }

            $price = $config['system_currency_position'] == 'before' ? "{$currency} {$amount}" : "{$amount} {$currency}";

            // build checkin-checkout dates
            $date_format = str_replace('%', '', RL_DATE_FORMAT);
            $requestDate = date(str_replace('b', 'M', $date_format));
            $checkIn     = date(str_replace('b', 'M', $date_format), $from);

            // added new escort data to email
            if ($configs['Booking_type'] == 'time_range') {
                // get escort system data
                if ($this->isEscort
                    && class_exists('rlEscort')
                    && method_exists($rlEscort, 'formFieldsMergeWithListingData')
                ) {
                    global $listing_data;

                    $listing_data = $listing_info;
                    $rlEscort->formFieldsMergeWithListingData();
                }

                $timePeriods = $this->buildCheckInOutData($configs['Booking_type']);

                $checkIn .= $checkOutTime ? ' - ' . $timePeriods[$checkOutTime] : '';
                $checkIn .= '<br />' . $lang['booking_escort_type'] . ': ';

                if ($this->isEscort) {
                    $checkIn .= $lang['data_formats+name+' . $listing_data['escort_rates'][$checkInTime]['key']];
                } elseif (isset($checkInTime)) {
                    $rates = $this->getRates($listingID);
                    $checkIn .= $rates[$checkInTime]['title'];
                }

                $checkIn .= '<br />' . $lang['booking_escort_duration'] . ': ';
                $checkIn .= $lang['booking_escort_duration_' . $to];
            } else {
                if ($configs['Booking_type'] == 'date_time_range') {
                    $timePeriods = $this->buildCheckInOutData($configs['Booking_type']);

                    $checkInTime  = $checkInTime && $timePeriods[$checkInTime]
                        ? $timePeriods[$checkInTime]
                        : $checkInTime;
                    $checkOutTime = $checkOutTime && $timePeriods[$checkOutTime]
                        ? $timePeriods[$checkOutTime]
                        : $checkOutTime;
                }

                $checkIn .= $checkInTime ? ' - ' . $checkInTime : '';
            }

            $checkOut = date(str_replace('b', 'M', $date_format), $to);
            $checkOut .= $checkOutTime ? ' - ' . $checkOutTime : '';

            if ($configs['Booking_type'] === 'date_range') {
                $start  = (new DateTime())->setTimeStamp($from);
                $finish  = (new DateTime())->setTimestamp($to);
                $nights = (int) $start->diff($finish)->format('%r%a');

                $checkOut .= "<br />{$GLOBALS['rlLang']->getPhrase('booking_nights', null, false, true)}: {$nights}";
            }

            // build listing url and link
            $listing_url  = $listing_info['listing_link'];
            $listing_link = '<a href="' . $listing_url . '">' . $listing_info['listing_title'] . '</a>';

            $bookingFields = $this->getBookingFields();

            // Collect client data from booking fields
            foreach ($formData as $data) {
                $clientData .= $bookingFields[$data['name']]['name'] . ': ';

                $data['value'] = stripcslashes($data['value']);
                switch ($bookingFields[$data['name']]['Type']) {
                    case 'text':
                    case 'textarea':
                    case 'number':
                        if ($data['value']) {
                            if ($bookingFields[$data['name']]['Condition'] == 'isEmail') {
                                $clientData .= '<a href="mailto:' . $data['value'] . '">';
                                $clientData .= $data['value'] . '</a>';
                            } else if ($bookingFields[$data['name']]['Condition'] == 'isUrl') {
                                $clientData .= '<a title="" href="' . $data['value'] . '">' . $data['value'] . '</a>';
                            } else {
                                $clientData .= $data['value'];
                            }
                        } else {
                            $clientData .= $lang['not_available'];
                        }

                        break;
                    case 'bool':
                        $clientData .= $data['value'] ? $lang['yes'] : $lang['no'];
                        break;
                }
                $clientData .= '<br>';
            }

            // collect info to email
            $reefless->loadClass('Mail');
            $mail_tpl = $rlMail->getEmailTemplate(
                $configs['Booking_type'] == 'time_range'
                ? 'booking_new_request_notify_escort'
                : 'booking_new_request_notify'
            );

            if ($configs['Booking_type'] == 'time_range') {
                $mail_tpl['body'] = preg_replace("/\{checkin\}(.*\{checkout\}?)/smi", '{checkin}', $mail_tpl['body']);
            }

            $mail_replace = array(
                '{date}'       => $requestDate,
                '{link}'       => $listing_link,
                '{checkin}'    => $checkIn,
                '{checkout}'   => $checkOut,
                '{amount}'     => $price,
                '{prepayment}' => $configs['Prepayment'] ? $prepayment : '',
                '{details}'    => $clientData,
            );

            $mail_tpl['body'] = str_replace(array_keys($mail_replace), array_values($mail_replace), $mail_tpl['body']);

            if ($config['booking_notify_admin_by_email'] && $config['notifications_email']) {
                $rlMail->send($mail_tpl, $config['notifications_email']);
            }

            if ($config['booking_notify_email']) {
                if ($owner_email = $rlDb->getOne('Mail', "`ID` = {$listing_info['Account_ID']}", 'accounts')) {
                    $rlMail->send($mail_tpl, $owner_email);
                }
            }
        }

        if ($_SESSION['bookingData'] && !defined('REALM')) {
            unset($_SESSION['bookingData']);

            // show message and return to listing page
            $reefless->loadClass('Notice');
            $GLOBALS['rlNotice']->saveNotice($GLOBALS['rlLang']->getPhrase('booking_request_send', null, null, true));
            $reefless->redirect(null, $listing_url);
        } else {
            return true;
        }
    }

    /**
     * Show rate ranges table & checkin-checkout fields
     *
     * @since 3.0.0
     */
    public function displayRateRangesTable()
    {
        global $rlSmarty, $listing_type, $category, $account_info;

        $booking_available = true;
        if ($GLOBALS['config']['membership_module']) {
            $this->getAllowedPlans();

            if (($account_info['plan']['ID'] && !$this->allowed_membership_plans[$account_info['plan']['ID']])
                && !$GLOBALS['rlAccount']->isAdmin()
            ) {
                $booking_available = false;
            }
        }

        $rlSmarty->assign_by_ref('booking_available', $booking_available);

        // hotfix for booking mapping in edit listing page
        if (!$listing_type && $category) {
            // get posting type of listing
            $listing_type = $GLOBALS['rlListingTypes']->types[$category['Type']];
            $rlSmarty->assign_by_ref('listing_type', $listing_type);
        }

        if (!$rlSmarty->_tpl_vars['bookingWeekdays']) {
            $rlSmarty->assign('bookingWeekdays', $this->weekdays);
        }

        $rlSmarty->assign_by_ref('booking_time_range', $this->buildCheckInOutData());
        $rlSmarty->display(RL_PLUGINS . 'booking' . RL_DS . 'rate_ranges.tpl');
    }

    /**
     * Get rate ranges, regular price and etc. and add to $_POST
     *
     * @since 3.3.1 - Added $manageListing parameter
     * @since 3.0.0
     */
    public function postSimulation($manageListing = null): void
    {
        $listing    = $manageListing ? $manageListing->listingData : $GLOBALS['listing'];
        $listing_id = (int) $listing['ID'];

        if (!$listing_id) {
            return;
        }

        $_POST['f']['booking_check_in']  = $listing['booking_check_in'];
        $_POST['f']['booking_check_out'] = $listing['booking_check_out'];

        // get booking configs
        $GLOBALS['rlSmarty']->assign_by_ref('bookingConfigs', $this->getConfigs($listing_id));

        // get rate ranges
        $this->getRateRange($listing_id);
    }

    /**
     * Get booking/system configs for current listing
     *
     * @since  3.0.0
     *
     * @param  int   $id        - ID of listing
     * @param  bool  $full_info - Get listing data and system configs
     * @return array            - Settings for current listing
     */
    public function getConfigs($id, $full_info = false)
    {
        global $config, $rlDb;

        $id = (int) $id;

        if (!$id) {
            return [];
        }

        static $configsData;

        $form = $full_info ? 'full' : 'base';

        if ($configsData[$form][$id]) {
            return $configsData[$form][$id];
        }

        // Get booking type & key of listing type
        $sql = "SELECT `T3`.`Booking_type`, `T3`.`Key`, `T3`.`Booking_repeatedly`, ";
        $sql .= "`T3`.`Booking_prepayment_type`, `T3`.`Booking_prepayment_percent` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_types` AS `T3` ON `T2`.`Type` = `T3`.`Key` ";
        $sql .= "WHERE `T1`.`ID` = {$id} ";
        $type = $rlDb->getRow($sql);

        // get booking configs of current listing
        $tmp_configs = $rlDb->fetch('*', array('Listing_ID' => $id), null, null, 'booking_configs', 'row');

        // prepare default values
        $configs['Deny_guest']          = (int) $tmp_configs['Deny_guest'] ?: 0;
        $configs['Min_book_day']        = $tmp_configs['Min_book_day'] ?: 1;
        $configs['Max_book_day']        = $tmp_configs['Max_book_day'] ?: 0;
        $configs['Fixed_rate_range']    = (int) $tmp_configs['Fixed_rate_range'] ?: 0;
        $configs['Calendar_restricted'] = (int) $tmp_configs['Calendar_restricted'] ?: 0;
        $configs['Hide_booked_days']    = (int) $tmp_configs['Hide_booked_days'] ?: 0;
        $configs['Booking_type']        = $type['Booking_type'] ?: 'date_range';
        $configs['Booking_repeatedly']  = isset($type['Booking_repeatedly']) ? (bool) $type['Booking_repeatedly'] : 1;
        $configs['Listing_type']        = $type['Key'];
        $configs['price_per_hour']      = 0;
        $configs['Prepayment']          = $type['Booking_prepayment_type'] == 'for_admin'
        && (float) $type['Booking_prepayment_percent']
            ? (float) $type['Booking_prepayment_percent']
            : 0;

        // deny booking for visitors if prepayment enabled for type
        if (!$configs['Deny_guest'] && $configs['Prepayment']) {
            $configs['Deny_guest'] = 1;
        }

        if ($full_info) {
            $GLOBALS['reefless']->loadClass('Listings');
            $configs['listing_data'] = $GLOBALS['rlListings']->getListing($id);

            if ($configs['Booking_type'] == 'date_time_range') {
                $time_frame_data     = unserialize($config['booking_time_frame_field_' . $configs['Listing_type']]);
                $time_frame_field    = $time_frame_data[0] ?: false;
                $time_frame_per_hour = $time_frame_data[1] && $time_frame_data[1]['hour']
                ? $time_frame_data[1]['hour']
                : false;

                $price_field = $config['booking_price_field_' . $configs['Listing_type']];

                if ($time_frame_field
                    && $time_frame_per_hour
                    && $configs['listing_data'][$time_frame_field] == $time_frame_per_hour
                    && $price_field
                    && $configs['listing_data'][$price_field]
                ) {
                    $price_data = $configs['listing_data'][$price_field];

                    if (strpos($price_data, '|')) {
                        $price_per_hour = explode('|', $price_data);
                        $price_per_hour = $price_per_hour[0];
                    } else {
                        $price_per_hour = $price_data;
                    }

                    $configs['price_per_hour'] = $price_per_hour;
                }
            }
        }

        $configsData[$form][$id] = $configs;

        return $configs;
    }

    /**
     * Get allowed plans
     *
     * @since 3.0.0
     */
    public function getAllowedPlans()
    {
        global $rlDb, $rlSmarty;

        if (!$this->allowed_plans) {
            $sql = "SELECT `ID`, `Key` FROM `{db_prefix}listing_plans` ";
            $sql .= "WHERE `Booking` = '1' AND `Status` = 'active'";
            $this->allowed_plans = $rlDb->getAll($sql, 'ID');
        }

        if ($GLOBALS['config']['membership_module'] && !$this->allowed_membership_plans) {
            $sql = "SELECT `ID`, `Key` FROM `{db_prefix}membership_plans` ";
            $sql .= "WHERE `Booking` = '1' AND `Status` = 'active'";
            $this->allowed_membership_plans = $rlDb->getAll($sql, 'ID');
        }

        $rlSmarty->assign_by_ref('booking_allowed_plans', $this->allowed_plans);
        $rlSmarty->assign_by_ref('booking_allowed_membership_plans', $this->allowed_membership_plans);
    }

    /**
     * Get info about booking reservations
     *
     * @since  3.0.0
     *
     * @return array
     */
    public function getReservations()
    {
        global $account_info, $lang, $config, $rlEscort, $rlDb;

        if (!defined('IS_LOGIN') || !$account_info['ID']) {
            return [];
        }

        // get current page
        $pInfo['current'] = (int) $_GET['pg'];

        // define start position
        $limit = (int) $config['booking_reservations_per_page'] > 0 ? $config['booking_reservations_per_page'] : 10;
        $start = $pInfo['current'] > 1 ? ($pInfo['current'] - 1) * $limit : 0;

        $sql = "SELECT SQL_CALC_FOUND_ROWS `T2`.`ID` AS `Request_ID`, `T2`.`Status` AS `Book_status`,  ";
        $sql .= "`T4`.`Username`, `T2`.`Date` AS `Booking_date`, `T1`.`From`, `T1`.`To`, ";
        $sql .= "`T1`.`ID` AS `Req_ID`,`T1`.`Status` AS `Req_status`,`T3`.*, `T5`.`Path`, `T5`.`Type`, ";
        $sql .= "`T1`.`Checkin`, `T1`.`Checkout`, `T1`.`To` ";
        $sql .= "FROM `{db_prefix}listings_book` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}booking_requests` AS `T2` ON `T1`.`ID` = `T2`.`Book_ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listings` AS `T3` ON `T1`.`Listing_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T4` ON `T4`.`ID` = `T2`.`Owner_ID` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T5` ON `T3`.`Category_ID` = `T5`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_types` AS `T6` ON `T5`.`Type` = `T6`.`Key` ";
        $sql .= "WHERE `T2`.`Renter_ID` = {$account_info['ID']} AND `T6`.`Booking_type` <> 'none' ";
        $sql .= "ORDER BY `T2`.`Status`, `T2`.`Date` DESC ";
        $sql .= "LIMIT {$start}, {$limit}";
        $tmp_reservations = $rlDb->getAll($sql);

        if ($tmp_reservations) {
            // count total reservations
            $calc          = $rlDb->getRow("SELECT FOUND_ROWS() AS `calc`");
            $pInfo['calc'] = $calc['calc'];
            $GLOBALS['rlSmarty']->assign_by_ref('pInfo', $pInfo);

            foreach ($tmp_reservations as $reservation) {
                $index      = $reservation['Request_ID'];
                $listing_id = $reservation['ID'];

                $reservations[$index]['ltype']         = $reservation['Type'];
                $reservations[$index]['Path']          = $reservation['Path'];
                $reservations[$index]['Listing_ID']    = $listing_id;
                $reservations[$index]['Main_photo']    = $reservation['Main_photo'];
                $reservations[$index]['Main_photo_x2'] = $reservation['Main_photo_x2'];
                $reservations[$index]['Booking_from']  = $reservation['From'];
                $reservations[$index]['Booking_to']    = $reservation['To'];

                $configs     = $this->getConfigs($listing_id);
                $timePeriods = $this->buildCheckInOutData($configs['Booking_type']);

                if ($configs['Booking_type'] == 'date_time_range' || $configs['Booking_type'] == 'time_range') {
                    $reservations[$index]['Checkin'] = $timePeriods[$reservation['Checkin']] ?: $reservation['Checkin'];
                    $reservations[$index]['Checkout'] = $timePeriods[$reservation['Checkout']] ?: $reservation['Checkout'];
                } else {
                    $reservations[$index]['Checkin']  = $timePeriods[$reservation['booking_check_in']];
                    $reservations[$index]['Checkout'] = $timePeriods[$reservation['booking_check_out']];
                }

                $where = "`Key` = 'booking_request+comment+{$reservation['Request_ID']}' ";
                $where .= "AND `Code` = '" . RL_LANG_CODE . "'";
                $comment = $rlDb->getOne('Value', $where, 'lang_keys');
                $reservations[$index]['Comment'] = strip_tags(str_replace(array("\'", '\"'), array(' '), $comment));

                if ($reservations[$index]['Comment'] === $lang['not_available']) {
                    $reservations[$index]['Comment'] = '';
                }

                $listing_details = $GLOBALS['rlListings']->getListing($listing_id, true, false);

                // get escort system data
                if ($this->isEscort
                    && $configs['Booking_type'] == 'time_range'
                    && class_exists('rlEscort')
                    && method_exists($rlEscort, 'formFieldsMergeWithListingData')
                ) {
                    global $listing_data;

                    $listing_data = $listing_details;
                    $rlEscort->formFieldsMergeWithListingData();

                    $type = $reservation['Checkin'];
                    $type = $lang['data_formats+name+' . $listing_data['escort_rates'][$type]['key']];

                    $reservations[$index]['Type']     = $type;
                    $reservations[$index]['Duration'] = $lang['booking_escort_duration_' . $reservation['To']];
                }

                // add title and url of listing
                $reservations[$index]['title'] = $listing_details['listing_title'];
                $reservations[$index]['link']  = $listing_details['listing_link'];

                $reservations[$index]['Booking_type'] = $configs['Booking_type'];
                $reservations[$index]['status']       = $reservation['Req_status'];
                $reservations[$index]['Book_status']  = $reservation['Book_status'];
                $reservations[$index]['ID']           = $reservation['Request_ID'];

                if ($GLOBALS['plugins']['ref'] && $reservation['ref_number']) {
                    $reservations[$index]['ref_number'] = $reservation['ref_number'];
                }
            }

            return $reservations;
        } else {
            return [];
        }
    }

    /**
     * Check availability booking functional for listing.
     * Set true/false for local variable "availableBooking"
     *
     * @since 3.0.0
     *
     * @param array $listing_data
     */
    public function checkBookingAvailable($listing_data)
    {
        global $rlDb, $config;

        if ($listing_data['booking_module']) {
            $id = (int) $listing_data['ID'];

            // get booking type of listing type
            if ($listing_data['Listing_type']) {
                $booking_type = $rlDb->getOne(
                    'Booking_type',
                    "`Key` = '{$listing_data['Listing_type']}'",
                    'listing_types'
                );
            }

            if ($id
                && ($rlDb->getOne('ID', "`Listing_ID` = {$id}", 'booking_rate_range')
                    || $this->checkAvailabilityRegularPriceMode($id)
                )
                && ($listing_data['Listing_type'] && $booking_type != 'none')
            ) {
                $this->getAllowedPlans();

                if ($this->allowed_plans[$listing_data['Plan_ID']]
                    || ($config['membership_module'] && $this->allowed_membership_plans[$listing_data['Plan_ID']])
                ) {
                    $this->availableBooking = true;
                } else {
                    $this->availableBooking = false;
                }
            } else {
                $this->availableBooking = false;
            }
        } else {
            $this->availableBooking = false;
        }
    }

    /**
     * Adding/Removing booking listing group to listing forms of general categories
     *
     * @since 3.0.0
     *
     * @param string $action - "add" or "remove"
     * @param int    $cat_id
     */
    public function handlerBookingGroupToListingForms($action, $cat_id = 0)
    {
        global $rlDb, $rlActions, $rlListingTypes;

        $booking_field_id  = $rlDb->getOne('ID', "`Key` = 'booking_module'", 'listing_fields');
        $booking_group_id  = $rlDb->getOne('ID', "`Key` = 'booking_rates'", 'listing_groups');
        $b_availability_id = $rlDb->getOne('ID', "`Key` = 'check_availability'", 'listing_fields');
        $listing_type      = $rlDb->getOne('Type', "`ID` = {$cat_id}", 'categories');
        $cache_update      = false;

        if ($listing_type) {
            $quick_search_form_id = $rlDb->getOne(
                'ID',
                "`Key` = '{$listing_type}_quick'",
                'search_forms'
            );

            $advanced_search_form_id = $rlDb->getOne(
                'ID',
                "`Key` = '{$listing_type}_advanced'",
                'search_forms'
            );
        }

        switch ($action) {
            case 'remove':
                // remove group from form
                if ($booking_group_id) {
                    $where = "WHERE `Group_ID` = {$booking_group_id} ";
                    if ($cat_id) {
                        $where .= "AND `Category_ID` = {$cat_id}";
                    }
                    $rlDb->query("DELETE FROM `{db_prefix}listing_relations` {$where}");
                    $cache_update = true;
                }

                // remove check-availability field from quick/advanced search forms
                if ($b_availability_id && ($quick_search_form_id || $advanced_search_form_id)) {
                    if ($quick_search_form_id) {
                        $rlDb->query("
                            DELETE FROM `{db_prefix}search_forms_relations`
                            WHERE `Category_ID` = {$quick_search_form_id} AND `Fields` = {$b_availability_id}
                        ");
                    }

                    if ($advanced_search_form_id) {
                        $rlDb->query("
                            DELETE FROM `{db_prefix}search_forms_relations`
                            WHERE `Category_ID` = {$advanced_search_form_id} AND `Fields` = {$b_availability_id}
                        ");
                    }

                    $cache_update = true;
                }
                break;

            case 'add':
                // add group to forms if not exist
                if ($booking_field_id && $booking_group_id) {
                    $position = $rlDb->getRow(
                        "SELECT MAX(`Position`) FROM `{db_prefix}listing_relations`",
                        'MAX(`Position`)'
                    );
                    $position++;

                    if ($cat_id) {
                        $insert[] = array(
                            'Position'    => $position,
                            'Category_ID' => $cat_id,
                            'Group_ID'    => $booking_group_id,
                            'Fields'      => $booking_field_id,
                        );
                    } else {
                        foreach ($rlListingTypes->types as $type) {
                            if ($type['Cat_general_cat']) {
                                $insert[] = array(
                                    'Position'    => $position,
                                    'Category_ID' => $type['Cat_general_cat'],
                                    'Group_ID'    => $booking_group_id,
                                    'Fields'      => $booking_field_id,
                                );
                            }
                        }
                    }

                    if ($insert) {
                        $rlActions->insert($insert, 'listing_relations');
                    }

                    $cache_update = true;
                }

                // add check-availability field to quick/advanced search forms
                if ($quick_search_form_id || $advanced_search_form_id) {
                    if ($quick_search_form_id) {
                        $position = $rlDb->getRow(
                            "SELECT MAX(`Position`) FROM `{db_prefix}search_forms_relations`
                            WHERE `Category_ID` = {$quick_search_form_id}",
                            'MAX(`Position`)'
                        );
                        $position++;

                        $insert_search[] = array(
                            'Position'    => $position,
                            'Category_ID' => $quick_search_form_id,
                            'Fields'      => $b_availability_id,
                        );
                    }

                    if ($advanced_search_form_id) {
                        $position = $rlDb->getRow(
                            "SELECT MAX(`Position`) FROM `{db_prefix}search_forms_relations`
                            WHERE `Category_ID` = {$advanced_search_form_id}",
                            'MAX(`Position`)'
                        );
                        $position++;

                        $insert_search[] = array(
                            'Position'    => $position,
                            'Category_ID' => $advanced_search_form_id,
                            'Fields'      => $b_availability_id,
                        );
                    }
                } else {
                    foreach ($rlListingTypes->types as $type) {
                        if ($type['Cat_general_cat']) {
                            $quick_search_form_id = $rlDb->getOne(
                                'ID',
                                "`Key` = '{$type['Key']}_quick' AND `Status` = 'active'",
                                'search_forms'
                            );

                            $advanced_search_form_id = $rlDb->getOne(
                                'ID',
                                "`Key` = '{$type['Key']}_advanced' AND `Status` = 'active'",
                                'search_forms'
                            );

                            if ($quick_search_form_id || $advanced_search_form_id) {
                                if ($quick_search_form_id) {
                                    $position = $rlDb->getRow(
                                        "SELECT MAX(`Position`) FROM `{db_prefix}search_forms_relations`
                                        WHERE `Category_ID` = {$quick_search_form_id}",
                                        'MAX(`Position`)'
                                    );
                                    $position++;

                                    $insert_search[] = array(
                                        'Position'    => $position,
                                        'Category_ID' => $quick_search_form_id,
                                        'Fields'      => $b_availability_id,
                                    );
                                }

                                if ($advanced_search_form_id) {
                                    $position = $rlDb->getRow(
                                        "SELECT MAX(`Position`) FROM `{db_prefix}search_forms_relations`
                                        WHERE `Category_ID` = {$advanced_search_form_id}",
                                        'MAX(`Position`)'
                                    );
                                    $position++;

                                    $insert_search[] = array(
                                        'Position'    => $position,
                                        'Category_ID' => $advanced_search_form_id,
                                        'Fields'      => $b_availability_id,
                                    );
                                }
                            }
                        }
                    }
                }

                if ($insert_search) {
                    $rlActions->insert($insert_search, 'search_forms_relations');
                    $cache_update = true;
                }
                break;
        }

        if ($cache_update) {
            $GLOBALS['rlCache']->updateForms();
        }
    }

    /**
     * Initialization prepayment process
     *
     * @since 3.0.0
     */
    public function initPrepayment()
    {
        global $reefless, $rlPayment, $config, $pages, $configs, $rlActions, $errors;

        $account_id                            = $GLOBALS['account_info']['ID'];
        $_SESSION['bookingData']['account_id'] = $account_id;
        $booking_data                          = $_SESSION['bookingData'];
        $listing_id                            = (int) $booking_data['listing_id'];

        if (!$listing_id || !$booking_data || !$account_id) {
            return false;
        }

        // build cancel url
        $cancel_url = $reefless->getPageUrl('booking_order');
        $cancel_url .= $config['mod_rewrite'] ? '?canceled' : '&canceled';

        // build success url
        $success_url = $reefless->getListingUrl($listing_id);
        $success_url .= ($config['mod_rewrite'] ? '?' : '&') . 'completed#booking';

        if (!$rlPayment->isPrepare()) {
            // clear payment options
            $rlPayment->clear();

            // build booking prepayment for admin
            $_SESSION['bookingData']['prepayment'] = $configs['Prepayment']
            ? round((floatval(preg_replace('/[^0-9.]/', '', $booking_data['total_cost'])) / 100) * $configs['Prepayment'], 2)
            : 0;

            $booking_data['prepayment'] = $_SESSION['bookingData']['prepayment'];

            // set payment options
            $rlPayment->setOption('service', 'booking');
            $rlPayment->setOption('total', $booking_data['prepayment']);
            $rlPayment->setOption('item_id', $listing_id);
            $rlPayment->setOption('item_name', $GLOBALS['lang']['booking_page_details'] . ' (#' . $listing_id . ')');
            $rlPayment->setOption('account_id', $account_id);
            $rlPayment->setOption('callback_class', 'rlBooking');
            $rlPayment->setOption('callback_method', 'completePrepayment');
            $rlPayment->setOption('cancel_url', $cancel_url);
            $rlPayment->setOption('success_url', $success_url);
            $rlPayment->setOption('plugin', 'booking');

            $rlPayment->init($errors);
        } else {
            $_SESSION['bookingData']['transaction_id'] = $rlPayment->getTransactionID();
            $rlPayment->setOption('plan_id', $rlPayment->getTransactionID());

            // save booking data to transaction table
            $reefless->loadClass('Actions');
            $rlActions->rlAllowHTML = true;
            $rlActions->updateOne(
                array(
                    'fields' => array(
                        'Booking_data' => serialize($booking_data),
                    ),
                    'where'  => array(
                        'ID' => $rlPayment->getTransactionID(),
                    ),
                ),
                'transactions'
            );
            $rlActions->rlAllowHTML = false;

            $rlPayment->checkout($errors);
        }
    }

    /**
     * Complete prepayment of booking process
     *
     * @since 3.0.0
     */
    public function completePrepayment($listing_id, $transaction_id)
    {
        $booking_data = array();

        if ($transaction_id) {
            $booking_data = $GLOBALS['rlDb']->getOne('Booking_data', "`ID` = {$transaction_id}", 'transactions');
            $booking_data = $booking_data ? unserialize($booking_data) : array();
        }

        $this->bookingOrder($booking_data);
    }

    /**
     * Get info about available listings for booking (to Manager in Admin side)
     *
     * @since 3.0.0
     */
    public function getApAvailableListingsList()
    {
        global $reefless, $lang, $start, $limit, $rlDb;

        if (!defined('REALM')) {
            return;
        }

        $reefless->loadClass('Listings');
        $reefless->loadClass('ListingTypes');

        $sql .= "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, `T1`.`ID` AS `Listing_ID`, `T3`.`Type` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
        $sql .= "WHERE `T1`.`booking_module` = '1' AND `T1`.`Status`='active' LIMIT {$start},{$limit}";
        $listings = $rlDb->getAll($sql);

        $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

        $data = array();
        foreach ($listings as $key => $value) {
            $data[$key]['ID']    = $listings[$key]['Listing_ID'];
            $data[$key]['ref']   = $listings[$key]['ref_number'] ?: $lang['not_available'];
            $data[$key]['title'] = $GLOBALS['rlListings']->getListingTitle(
                $listings[$key]['Category_ID'],
                $listings[$key],
                $listings[$key]['Type']
            );

            // build listing url
            if ($data[$key]['title'] && $rlDb->getOne('ID', "`ID` = {$data[$key]['ID']}", 'listings')) {
                $data[$key]['link'] = '<a target="_blank" alt="' . $lang['view_details'];
                $data[$key]['link'] .= '" title="' . $lang['view_details'] . '" href="' . RL_URL_HOME . ADMIN;
                $data[$key]['link'] .= '/index.php?controller=listings&action=view&id=' . $data[$key]['ID'] . '">';
                $data[$key]['link'] .= $data[$key]['title'] . '</a>';
            }
        }

        return array('data' => $data, 'count' => $count['count']);
    }

    /**
     * Get booking requests (to Manager in Admin side)
     *
     * @since 3.0.0
     */
    public function getApRequestsList()
    {
        global $reefless, $lang, $start, $limit, $rlDb;

        if (!defined('REALM')) {
            return;
        }

        $reefless->loadClass('Listings');

        $sql = "SELECT SQL_CALC_FOUND_ROWS `T3`.`ID` AS `Listing_ID`,`T1`.`ID` AS `Request_ID`, `T4`.`Username`, ";
        $sql .= "IF(`T1`.`Renter_ID` > 0, CONCAT(`T4`.`First_name`, ' ', `T4`.`Last_name`), ";
        $sql .= "CONCAT(`T1`.`first_name`, ' ', `T1`.`last_name`)) AS `Booking_client`, `T2`.`From` AS `Booking_from`, ";
        $sql .= "`T2`.`To` AS `Booking_to`, `T2`.`Status` AS `Booking_status`, `T5`.`Type`, `T1`.`Renter_ID`, ";
        $sql .= "`T2`.`ID` AS `Booking_ID`, `T1`.`Status` AS `Book_status`, `T3`.*, `T6`.`Booking_type` ";
        $sql .= "FROM `{db_prefix}booking_requests` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listings_book` AS `T2` ON `T1`.`Book_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listings` AS `T3` ON `T2`.`Listing_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T4` ON `T4`.`ID` = `T1`.`Renter_ID` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T5` ON `T3`.`Category_ID` = `T5`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_types` AS `T6` ON `T5`.`Type` = `T6`.`Key` ";
        $sql .= "GROUP BY `T1`.`ID` ORDER BY `T1`.`Date` DESC LIMIT {$start},{$limit}";
        $data = $rlDb->getAll($sql);

        $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

        foreach ($data as $key => $value) {
            switch ($value['Booking_status']) {
                case 'process':
                    $data[$key]['Booking_status'] = $lang['new'];
                    break;
                case 'booked':
                    $data[$key]['Booking_status'] = $lang['booking_accepted'];
                    break;
                case 'refused':
                    $data[$key]['Booking_status'] = $lang['booking_refused'];
                    break;
            }

            $data[$key]['Booking_client'] = trim($data[$key]['Booking_client']);
            $data[$key]['Booking_client'] = !empty($data[$key]['Booking_client'])
            ? $data[$key]['Booking_client']
            : $data[$key]['Username'];

            // build listing title
            $data[$key]['title'] = $GLOBALS['rlListings']->getListingTitle(
                $data[$key]['Category_ID'],
                $data[$key],
                $data[$key]['Type']
            );

            // build listing url
            if ($data[$key]['title'] && $rlDb->getOne('ID', "`ID` = {$data[$key]['Listing_ID']}", 'listings')) {
                $data[$key]['link'] = '<a target="_blank" alt="' . $lang['view_details'];
                $data[$key]['link'] .= '" title="' . $lang['view_details'] . '" href="' . RL_URL_HOME . ADMIN;
                $data[$key]['link'] .= '/index.php?controller=listings&action=view&id=' . $data[$key]['Listing_ID'];
                $data[$key]['link'] .= '">' . $data[$key]['title'] . '</a>';
            }

            // adapt checkout date for escort
            if ($data[$key]['Booking_type'] == 'time_range') {
                $data[$key]['Booking_to'] = $lang['not_available'];
            } else {
                $data[$key]['Booking_to'] = date(
                    str_replace('b', 'M', str_replace('%', '', RL_DATE_FORMAT)),
                    $data[$key]['Booking_to']
                );
            }

            // add link for renter or label with "website visitor"
            if ($value['Renter_ID']) {
                $renter_page                  = RL_URL_HOME . ADMIN . '/index.php?controller=accounts&action=view&userid=' . $value['Renter_ID'];
                $renter_page                  = '<a target="_blank" title="' . $lang['view_account'] . '" href="' . $renter_page . '">';
                $data[$key]['Booking_client'] = $renter_page . $data[$key]['Booking_client'] . '</a>';
            } else {
                $data[$key]['Booking_client'] .= ' (' . $lang['website_visitor'] . ')';
            }
        }

        return array('data' => $data, 'count' => $count['count']);
    }

    /**
     * Get booking fields (to Manager in Admin side)
     *
     * @since 3.0.0
     *
     * @return array
     */
    public function getApFieldsList()
    {
        global $lang, $start, $limit, $rlDb;

        if (!defined('REALM')) {
            return [];
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.`ID`, `T1`.`Key`, ";
        $sql .= "`T1`.`Type`, `T1`.`Booking_type`, `T1`.`Required`, `T1`.`Position`,`T1`.`Status` ";
        $sql .= "FROM `{db_prefix}booking_fields` AS `T1` ";
        $sql .= "WHERE `T1`.`Status` <> 'trash' ";
        $sql .= "LIMIT {$start}, {$limit}";
        $fields = $rlDb->getAll($sql);

        $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

        foreach ($fields as &$field) {
            $field['name'] = $rlDb->getOne(
                'Value',
                "`Key`='booking_fields+name+{$field['Key']}' AND `Code` = '" . RL_LANG_CODE . "'",
                'lang_keys'
            );

            if (!$field['name'] && in_array($field['Key'], ['first_name', 'last_name', 'email'] )) {
                $field['name'] = $field['Key'] === 'email' ? $lang['mail'] : $lang[$field['Key']];
            }

            switch ($field['Booking_type']) {
                case 'date_range,date_time_range,time_range':
                    $field['Booking_type'] = $lang['any'];
                    break;
                case 'date_range':
                    $field['Booking_type'] = $lang['booking_date_range'];
                    break;
                case 'date_time_range':
                    $field['Booking_type'] = $lang['booking_date_time_range'];
                    break;
                case 'time_range':
                    $field['Booking_type'] = $lang['booking_time_range'];
                    break;
            }

            $field['Type']     = $GLOBALS['l_types'][$field['Type']];
            $field['Required'] = $field['Required'] ? $lang['yes'] : $lang['no'];
            $field['Status']   = $lang[$field['Status']];
        }

        return array('data' => $fields, 'count' => $count['count']);
    }

    /**
     * Ap booking colors
     *
     * @since 3.0.0
     */
    public function apBookingColors()
    {
        global $lang, $reefless;

        if (!defined('REALM')) {
            return;
        }

        $GLOBALS['bcAStep'][0]['name'] = $lang['booking_colors'];

        if (!isset($_POST['submit'])) {
            $colors_info = explode('|', $GLOBALS['config']['booking_colors']);

            $_POST['b']['available']     = $colors_info[3];
            $_POST['b']['available_rgb'] = $colors_info[0];
            $_POST['b']['closed']        = $colors_info[4];
            $_POST['b']['closed_rgb']    = $colors_info[1];
            $_POST['b']['own']           = $colors_info[5];
            $_POST['b']['own_rgb']       = $colors_info[2];
        } else {
            $colors_form = $_POST['b'];

            $booking_colors = $_POST['b']['available_rgb'] . '|' . $_POST['b']['closed_rgb'];
            $booking_colors .= '|' . $_POST['b']['own_rgb'] . '|' . $colors_form['available'];
            $booking_colors .= '|' . $colors_form['closed'] . '|' . $colors_form['own'];

            $GLOBALS['rlConfig']->setConfig('booking_colors', $booking_colors);

            $reefless->loadClass('Notice');
            $GLOBALS['rlNotice']->saveNotice($lang['booking_colors_saved']);
            $reefless->redirect(array('controller' => $GLOBALS['controller'], 'mode' => 'booking_colors'));
        }
    }

    /**
     * Ap request details
     *
     * @since 3.0.0
     */
    public function apRequestDetails()
    {
        global $lang, $rlDb, $rlEscort, $reefless, $rlListings, $rlSmarty;

        $request_id = (int) $_GET['id'];

        if (!$request_id || !defined('REALM')) {
            return;
        }

        $reefless->loadClass('Listings');

        $GLOBALS['bcAStep'][0]['name'] = $lang['booking_page_details'];

        $fields = $this->getBookingFields();

        $sql = "SELECT `T1`.`ID` AS `Req_ID`, `T3`.`ID` AS `Listing_ID`, `T1`.`Amount`, `T1`.`Status` AS `Stat`, ";
        $sql .= "`T2`.`Status` AS `Req_status`, `T3`.*, `T1`.`From`, `T1`.`To`, `T2`.*, ";
        $sql .= "`T5`.`Booking_type`, `T1`.`Checkin`, `T1`.`Checkout` ";
        $sql .= "FROM `{db_prefix}listings_book` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}booking_requests` AS `T2` ON `T1`.`ID` = `T2`.`Book_ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listings` AS `T3` ON `T1`.`Listing_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T4` ON `T3`.`Category_ID` = `T4`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_types` AS `T5` ON `T4`.`Type` = `T5`.`Key` ";
        $sql .= "WHERE `T1`.`ID` = {$request_id}";
        $request = $rlDb->getRow($sql);

        if ($request) {
            $request['Stat'] = str_replace(
                array('process', 'booked', 'refused'),
                array($lang['pending'], $lang['booking_accepted'], $lang['booking_refused']),
                $request['Stat']
            );

            if ($request['Booking_type'] == 'time_range' && $this->isEscort) {
                $reefless->loadClass('Escort');

                // get escort system data
                if (class_exists('rlEscort') && method_exists($rlEscort, 'formFieldsMergeWithListingData')) {
                    global $listing_data;

                    $listing_data = $rlListings->getListing($request['Listing_ID']);
                    $rlEscort->formFieldsMergeWithListingData();

                    $listingTypeKey = $GLOBALS['rlListingTypes']->types[$listing_data['Listing_type']];

                    // build listing structure
                    $listing_data_escort = $rlListings->getListingDetails(
                        $listing_data['Category_ID'],
                        $listing_data,
                        $listingTypeKey
                    );

                    if ($listing_data_escort['escort_rates']['Fields']['escort_rates']
                        && $listing_data_escort['escort_rates']['Fields']['escort_rates']['value'] == $lang['loading']
                        && is_array($listing_data['escort_rates'])
                    ) {
                        $out = array();

                        foreach ($listing_data['escort_rates'] as $entry) {
                            if (intval($entry['custom'])) {
                                $title = $entry['key'];
                            } else {
                                $title = $lang['data_formats+name+' . $entry['key']];
                            }
                            $price    = $rlSmarty->str2money($entry['price']);
                            $currency = $lang['data_formats+name+' . $entry['currency']];

                            $subtitle = $GLOBALS['config']['system_currency_position'] == 'before'
                            ? $currency . ' ' . $price
                            : $price . ' ' . $currency;

                            $out[] = array(
                                'title'    => $title,
                                'subtitle' => $subtitle,
                            );
                        }

                        $listing_data_escort['escort_rates']['Fields']['escort_rates']['value'] = $out;
                    }

                    $rlSmarty->assign_by_ref('listing_escort', $listing_data_escort);
                }
            }

            $request['title'] = $GLOBALS['rlListings']->getListingTitle($request['Category_ID'], $request);

            foreach ($fields as $key => $field) {
                if (!empty($request[$field['Key']])) {
                    $request['fields'][$key]          = $field;
                    $request['fields'][$key]['value'] = $request[$field['Key']];
                }
            }

            $GLOBALS['bread_crumbs'][] = array(
                'title' => $request['title'],
                'name'  => $request['title'],
            );

            $configs     = $this->getConfigs($request['Listing_ID'], true);
            $timePeriods = $this->buildCheckInOutData($configs['Booking_type']);

            $request['Checkout'] = $timePeriods[$request['Checkout']] ?: $request['Checkout'];

            if ($configs['Booking_type'] !== 'time_range') {
                if ($request['booking_check_in']) {
                    $request['booking_check_in']  = $timePeriods[$request['booking_check_in']];
                }

                if ($request['booking_check_out']) {
                    $request['booking_check_out'] = $timePeriods[$request['booking_check_out']];
                }

                if ($configs['Booking_type'] === 'date_time_range') {
                    $request['Checkin'] = $timePeriods[$request['Checkin']] ?: $request['Checkin'];
                }

                if ($configs['Booking_type'] === 'date_range') {
                    $start  = (new DateTime())->setTimeStamp($request['From']);
                    $finish  = (new DateTime())->setTimestamp($request['To']);
                    $request['Booking_nights'] = (int) $start->diff($finish)->format('%r%a');

                    $lang['booking_nights'] = $GLOBALS['rlLang']->getPhrase('booking_nights', null, null, true);
                }
            }

            $rlSmarty->assign_by_ref('request', $request);
            $this->getRateRange($request['Listing_ID'], true);
            $rlSmarty->assign('bookingRates', $this->getRates($request['Listing_ID']));
        }
    }

    /**
     * Create/edit booking fields
     *
     * @since 3.0.0
     */
    public function apBookingFields()
    {
        global $lang, $bcAStep, $errors, $controller, $reefless, $rlSmarty, $rlValid, $rlBoookingFields, $rlDb;

        if (!defined('REALM')) {
            return;
        }

        $reefless->loadClass('BoookingFields', null, 'booking');

        // additional bread crumb step
        $bcAStep[0] = array(
            'name'       => $lang['booking_fields_list'],
            'Controller' => 'booking',
            'Vars'       => 'mode=booking_fields',
        );

        if ($_GET['action']) {
            $bcAStep[1] = array('name' => $_GET['action'] == 'add' ? $lang['add_field'] : $lang['edit_field']);
        }

        $b_types = array(
            'text'     => $lang['type_text'],
            'textarea' => $lang['type_textarea'],
            'number'   => $lang['type_number'],
            'bool'     => $lang['type_bool'],
        );
        $rlSmarty->assign_by_ref('b_types', $b_types);

        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
            if ($_GET['action'] == 'edit') {
                $e_key = $rlValid->xSql($_GET['field']);

                // get current field information
                $field_info = $rlDb->fetch(
                    '*',
                    array('Key' => $e_key),
                    "AND `Status` <> 'trash'",
                    null,
                    'booking_fields',
                    'row'
                );

                if (empty($field_info)) {
                    $errors[] = $lang['notice_field_not_found'];
                } else {
                    if (!$_POST['fromPost']) {
                        // common fields
                        $_POST['key']          = $e_key;
                        $_POST['required']     = $field_info['Required'];
                        $_POST['status']       = $field_info['Status'];
                        $_POST['type']         = $field_info['Type'];
                        $_POST['booking_type'] = $field_info['Booking_type'];

                        // Get names
                        $e_names = $rlDb->fetch(
                            array('Code', 'Value'),
                            array('Key' => 'booking_fields+name+' . $e_key),
                            "AND `Status` = 'active'",
                            null,
                            'lang_keys'
                        );
                        foreach ($e_names as $nKey => $nVal) {
                            $_POST['name'][$e_names[$nKey]['Code']] = $e_names[$nKey]['Value'];
                        }

                        foreach ($allLangs as $allLang) {
                            if (!$_POST['name'][$allLang['Code']]
                                && in_array($e_key, ['first_name', 'last_name', 'email'])
                            ) {
                                $_POST['name'][$allLang['Code']] = $e_key === 'email' ? $lang['mail'] : $lang[$e_key];
                            }
                        }

                        // get description
                        $descriptions = $rlDb->fetch(
                            array('Code', 'Value'),
                            array('Key' => 'booking_fields+description+' . $e_key),
                            "AND `Status` = 'active'",
                            null,
                            'lang_keys'
                        );
                        foreach ($descriptions as $pKey => $pVal) {
                            $_POST['description'][$descriptions[$pKey]['Code']] = $descriptions[$pKey]['Value'];
                        }

                        // additional fields
                        switch ($field_info['Type']) {
                            case 'text':
                                $e_default = $rlDb->fetch(
                                    array('Code', 'Value'),
                                    array('Key' => 'booking_fields+default+' . $e_key),
                                    "AND `Status` = 'active'",
                                    null,
                                    'lang_keys'
                                );

                                foreach ($e_default as $nKey => $nVal) {
                                    $_POST['text']['default'][$e_default[$nKey]['Code']] = $e_default[$nKey]['Value'];
                                }

                                $_POST['text']['condition'] = $field_info['Condition'];
                                $_POST['text']['maxlength'] = $field_info['Values'];
                                break;

                            case 'textarea':
                                $_POST['textarea']['maxlength'] = $field_info['Values'];
                                break;

                            case 'number':
                                $_POST['number']['maxlength'] = $field_info['Values'];
                                break;

                            case 'bool':
                                $_POST['bool']['default'] = $field_info['Default'];
                                break;
                        };
                    }
                }
            }

            if (isset($_POST['submit'])) {
                $errors = array();

                // load the utf8 lib
                loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

                $f_type = $_POST['type'];

                // check key
                $f_key = $rlValid->xSql($_POST['key']);

                if (!utf8_is_ascii($f_key)) {
                    $f_key = utf8_to_ascii($f_key);
                }

                if (strlen($f_key) < 2) {
                    $errors[] = $lang['incorrect_phrase_key'];
                }

                // check key exist (in add mode only)
                if ($_GET['action'] == 'add') {
                    $exist_keys = $rlDb->getAll("SHOW FIELDS FROM `{db_prefix}booking_requests`");

                    foreach ($exist_keys as $eKey => $eValue) {
                        if (strtolower($exist_keys[$eKey]['Field']) == strtolower($f_key)) {
                            $errors[] = str_replace('{key}', "<b>\"" . $f_key . "\"</b>", $lang['notice_field_exist']);
                        }
                    }
                }

                $f_key = $_GET['action'] == 'add' ? $rlValid->str2key($f_key) : $rlValid->xSql($f_key);

                // check name
                $f_name = $_POST['name'];

                foreach ($allLangs as $lkey => $lval) {
                    if (empty($f_name[$allLangs[$lkey]['Code']])) {
                        $errors[] = str_replace(
                            '{field}',
                            "<b>" . $lang['name'] . "({$allLangs[$lkey]['name']})</b>",
                            $lang['notice_field_empty']
                        );
                    }

                    $f_names[$allLangs[$lkey]['Code']] = $f_name[$allLangs[$lkey]['Code']];
                }

                // check field type
                if (empty($f_type)) {
                    $errors[] = $lang['notice_type_empty'];
                }

                // check text type
                $f_text_maxlength = $_POST['text']['maxlength'];

                if (empty($f_text_maxlength) || ($f_text_maxlength < 1 && $f_text_maxlength > 255)) {
                    $f_text_maxlength = 50;
                }

                if (!empty($errors)) {
                    $rlSmarty->assign_by_ref('errors', $errors);
                } else {
                    // configuring data
                    $f_data['key']          = $f_key;
                    $f_data['names']        = $f_names;
                    $f_data['description']  = $_POST['description'];
                    $f_data['required']     = (int) $_POST['required'] ?: $field_info['Required'];
                    $f_data['status']       = $_POST['status'];
                    $f_data['booking_type'] = $_POST['booking_type'] ?: $field_info['Booking_type'];

                    switch ($f_type) {
                        case 'text':
                            foreach ($allLangs as $lkey => $lval) {
                                if (!empty($_POST['text']['default'][$allLangs[$lkey]['Code']])) {
                                    $data_value                                  = $_POST['text']['default'][$allLangs[$lkey]['Code']];
                                    $f_data['default'][$allLangs[$lkey]['Code']] = $rlValid->xSql($data_value);
                                }
                            }
                            $f_data['maxlength'] = (int) $f_text_maxlength;
                            $f_data['condition'] = $_POST['text']['condition'];
                            break;

                        case 'textarea':
                            $f_data['maxlength'] = (int) $_POST['textarea']['maxlength'];
                            break;

                        case 'number':
                            $f_data['max'] = (int) $_POST['number']['maxlength'];
                            break;

                        case 'bool':
                            $f_data['default'] = (int) $_POST['bool']['default'];
                            break;
                    };

                    // add/edit action
                    if ($_GET['action'] == 'add') {
                        $action  = $rlBoookingFields->createBookingField($f_type, $f_data, $allLangs);
                        $message = $lang['field_added'];
                        $aUrl    = array('controller' => $controller, 'mode' => 'booking_fields');
                    } elseif ($_GET['action'] == 'edit') {
                        $action  = $rlBoookingFields->editBookingField($f_type, $f_data, $allLangs);
                        $message = $lang['field_edited'];
                        $aUrl    = array('controller' => $controller, 'mode' => 'booking_fields');
                    }

                    if ($action) {
                        $reefless->loadClass('Notice');
                        $GLOBALS['rlNotice']->saveNotice($message);
                        $reefless->redirect($aUrl);
                    }
                }
            }
        }
    }

    /**
     * @hook  listingsModifyFieldSearch
     * @since 3.2.0 - Added $sql, $data parameters
     * @since 3.0.0
     */
    public function hookListingsModifyFieldSearch(&$sql, $data)
    {
        global $rlSmarty;

        unset($GLOBALS['sorting']['check_availability']);
        $fields_list = $rlSmarty->get_template_vars('fields_list');
        unset($fields_list['check_availability']);
        $rlSmarty->assign_by_ref('fields_list', $fields_list);

        if (!$_POST['booking_submit']
            && !$data['check_availability']
            && !isset($data['booking_module'])
            && false === defined('CRON_FILE')
        ) {
            $this->preventModifySearchQuery = true;
            return false;
        }

        // add DISTINCT to sql request
        if (!is_numeric(strpos($sql, 'DISTINCT'))) {
            $sql = str_replace('SELECT ', 'SELECT DISTINCT ', $sql);
        }
    }

    /**
     * @hook  listingsModifyJoinSearch
     * @since 3.2.0 - Added $sql, $data parameters
     * @since 3.0.0
     */
    public function hookListingsModifyJoinSearch(&$sql, $data)
    {
        $from = $data['check_availability']['from'] ? date_parse($data['check_availability']['from']) : false;
        $to   = $data['check_availability']['to'] ? date_parse($data['check_availability']['to']) : false;

        if ($this->preventModifySearchQuery || (!is_array($from) && !is_array($to))) {
            return false;
        }

        $from = $from ? mktime(0, 0, 0, $from['month'], $from['day'], $from['year']) : false;
        $to   = $to ? mktime(0, 0, 0, $to['month'], $to['day'], $to['year']) : false;

        // join table with exist requests
        $sql .= "LEFT JOIN `{db_prefix}listings_book` AS `LB` ";
        $sql .= "ON `T1`.`ID` = `LB`.`Listing_ID` AND `LB`.`Status` <> 'refused' ";

        if ($from || $to) {
            $sql .= "AND (";

            if ($from) {
                $sql .= "({$from} BETWEEN `LB`.`From` AND `LB`.`To`)" . ($to ? ' OR ' : '');
            }

            if ($to) {
                $sql .= "({$to} BETWEEN `LB`.`From` AND `LB`.`To`)";
            }

            $sql .= ") ";
        }

        // join table with available rate ranges
        $sql .= "LEFT JOIN `{db_prefix}booking_rate_range` AS `BRR` ";
        $sql .= "ON `T1`.`ID` = `BRR`.`Listing_ID` AND `BRR`.`Price` > 0 ";

        if ($from || $to) {
            $sql .= "AND (";

            if ($from) {
                $sql .= "({$from} BETWEEN `BRR`.`From` AND `BRR`.`To`)" . ($to ? ' AND ' : '');
            }

            if ($to) {
                $sql .= "({$to} BETWEEN `BRR`.`From` AND `BRR`.`To`)";
            }

            $sql .= ") ";
        }

        if (!$from || !$to) {
            $GLOBALS['rlDebug']->logger('Booking Availability search failed: "from" = ' . $from . ', "to" = ' . $to);
        }
    }

    /**
     * @hook  listingsModifyWhereSearch
     * @since 3.2.0 - Added $sql, $data parameters
     * @since 3.0.0
     */
    public function hookListingsModifyWhereSearch(&$sql, $data)
    {
        if ($this->preventModifySearchQuery) {
            return false;
        }

        if (!empty($data['check_availability']['from'])) {
            $replace = "AND UNIX_TIMESTAMP(`T1`.`check_availability`) >= ";
            $replace .= "UNIX_TIMESTAMP('{$data['check_availability']['from']}') ";
            $from = date_parse($data['check_availability']['from']);
            $from = mktime(0, 0, 0, $from['month'], $from['day'], $from['year']);
        }

        if (!empty($data['check_availability']['to'])) {
            $replace .= "AND UNIX_TIMESTAMP(`T1`.`check_availability`) <= ";
            $replace .= "UNIX_TIMESTAMP('{$data['check_availability']['to']}') ";
            $to = date_parse($data['check_availability']['to']);
            $to = mktime(0, 0, 0, $to['month'], $to['day'], $to['year']);
        }

        if (isset($data['booking_module'])) {
            $sql = str_replace("`T1`.`booking_module` = 1", "`T1`.`booking_module` = '1'", $sql);
            $sql = str_replace("`T1`.`booking_module` = 0", "`T1`.`booking_module` = '0'", $sql);
        }

        $sql = str_replace($replace, '', $sql);

        if ($from || $to) {
            $sql .= "AND `T1`.`booking_module` = '1' ";
            $sql .= "AND ((`LB`.`Listing_ID` > 0 AND (";

            if ($from) {
                $sql .= "({$from} NOT BETWEEN `LB`.`From` AND `LB`.`To`)";
                $sql .= $to ? ' OR ' : '';
            }

            if ($to) {
                $sql .= "({$to} NOT BETWEEN `LB`.`From` AND `LB`.`To`)";
            }

            $sql .= ")) ";

            $sql .= "OR `LB`.`Listing_ID` IS NULL) ";

            $sql .= "AND (`T1`.`booking_regular_mode` = '1' ";
            $sql .= "OR (`T1`.`booking_regular_mode` = '0' AND `BRR`.`Listing_ID` > 0)) ";
        }

        // Find listings by availability days (for escort type)
        if ($GLOBALS['listing_type']['Booking_type'] == 'time_range'
            && ($data['check_availability']['from'] || $data['check_availability']['to'])
            && $this->isEscort
        ) {
            if (!$data['check_availability']['to']) {
                $from = date_parse($data['check_availability']['from']);
                $from = $from ? mktime(0, 0, 0, $from['month'], $from['day'], $from['year']) : false;

                $to = strtotime('+1 month', $from);
            } else if (!$data['check_availability']['from']) {
                $to = date_parse($data['check_availability']['to']);
                $to = $to ? mktime(0, 0, 0, $to['month'], $to['day'], $to['year']) : false;

                $from = strtotime('-1 month', $to);
            } else {
                $from = date_parse($data['check_availability']['from']);
                $from = $from ? mktime(0, 0, 0, $from['month'], $from['day'], $from['year']) : false;

                $to = date_parse($data['check_availability']['to']);
                $to = $to ? mktime(0, 0, 0, $to['month'], $to['day'], $to['year']) : false;
            }

            if ($from && $to) {
                $dates = array();
                while ($from <= $to && count($dates) < 7) {
                    $dates[] = 'availability_' . date('w', $from);
                    $from    = strtotime('+1 day', $from);
                }

                if ($dates) {
                    $sql .= "AND (`T1`.`" . implode('` > 0 OR `T1`.`', $dates) . "` > 0) ";
                }
            }
        }
    }

    /**
     * @hook  listingDetailsTop
     * @since 3.0.0
     */
    public function hookListingDetailsTop()
    {
        global $listing_data, $config, $blocks, $rlSmarty, $rlNotice;

        $listing_id = (int) $listing_data['ID'];

        if (!$listing_data || !$listing_id) {
            return;
        }

        global $rlLang;

        $this->checkBookingAvailable($listing_data);

        if ($this->availableBooking) {
            $this->getRateRange($listing_id);
            $rlSmarty->assign_by_ref('fields', $this->getBookingFields());
            $rlSmarty->assign_by_ref('bookingConfigs', $this->getConfigs($listing_id));

            if (isset($_GET['completed'])
                && $_SESSION['bookingData']['transaction_id'] == $_SESSION['transaction_id']
                && $_SESSION['bookingData']['listing_id'] == $listing_data['ID']
                && $_SESSION['bookingData']['account_id'] == $GLOBALS['account_info']['ID']
            ) {
                $transaction = $GLOBALS['rlPayment']->getTransaction($_SESSION['transaction_id']);

                $GLOBALS['reefless']->loadClass('Notice');

                // show message to user about status of transaction (paid || unpaid)
                if ($transaction['Status'] == 'paid') {
                    $rlNotice->saveNotice($rlLang->getPhrase('booking_request_send', null, null, true));
                } else if ($transaction['Status'] == 'unpaid') {
                    $rlNotice->saveNotice(
                        $rlLang->getPhrase('booking_transaction_unpaid_notice', null, null, true),
                        'alert'
                    );
                }

                unset($_SESSION['bookingData']);
            }
        }

        // unset booking calendar box
        if (!$this->availableBooking || $config['booking_calendar_position'] == 'listing') {
            unset($blocks['booking_calendar_box']);
            foreach ($blocks as $key => $value) {
                if ($value['Side'] == 'left') {
                    $blocks['left'] = 1;
                    break;
                } else {
                    $blocks['left'] = 0;
                }
            }
        }
    }

    /**
     * @hook myListingsIcon
     */
    public function hookMyListingsIcon()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'booking' . RL_DS . 'nav_bar.tpl');
    }

    /**
     * @hook  listingDetailsBottom
     * @since 3.0.0
     */
    public function hookListingDetailsBottom()
    {
        if (!$this->availableBooking) {
            return;
        }

        global $listing, $listing_data, $lang, $rlSmarty;

        if ($listing['booking_rates']
            && $listing['booking_rates']['Fields']
            && $listing['booking_rates']['Fields']['booking_module']
        ) {
            $listing['booking_rates']['Fields']['check_in'] = array(
                'Key'          => 'booking_check_in',
                'value'        => $listing_data['booking_check_in'] ?: $lang['not_available'],
                'Details_page' => 1,
                'name'         => $lang['booking_checkin'],
            );

            $listing['booking_rates']['Fields']['check_out'] = array(
                'Key'          => 'booking_check_out',
                'value'        => $listing_data['booking_check_out'] ?: $lang['not_available'],
                'Details_page' => 1,
                'name'         => $lang['booking_checkout'],
            );

            $configs = $this->getConfigs($listing_data['ID']);

            if ($configs['Booking_type'] === 'time_range' && !$this->isEscort) {
                $listing['availability']['Fields']['availability']['value'] = $this->getAvailability(
                    $listing_data['ID']
                );
            }

            if ($configs['Booking_type'] === 'time_range') {
                $rlSmarty->assign('bookingAvailability', $this->getAvailability($listing_data['ID'], true));

                if (!$this->isEscort) {
                    $rlSmarty->assign('bookingRates', $this->getRates($listing_data['ID']));
                }
            }
        }
    }

    /**
     * @hook  listingDetailsBottomTpl
     * @since 3.0.0
     */
    public function hookListingDetailsBottomTpl()
    {
        global $rlSmarty;

        if ($this->availableBooking) {
            if ($GLOBALS['config']['booking_calendar_position'] == 'listing') {
                $rlSmarty->assign('displayCalendar', true);
                $rlSmarty->display(RL_PLUGINS . 'booking' . RL_DS . 'booking_group.tpl');
            }
        }
    }

    /**
     * @hook  addListingPreFields
     * @since 3.0.0
     */
    public function hookAddListingPreFields()
    {
        $this->displayRateRangesTable();
    }

    /**
     * @hook  editListingPreFields
     * @since 3.0.0
     */
    public function hookEditListingPreFields()
    {
        $this->displayRateRangesTable();
    }

    /**
     * @hook  afterListingCreate
     * @since 3.0.0
     *
     * @param object $instance - Instance of class (add/edit listing)
     */
    public function hookAfterListingCreate($instance = null)
    {
        $this->saveRateRangesFromListing($instance);
    }

    /**
     * @hook  afterListingUpdate
     * @since 3.1.1
     *
     * @todo - Remove usage of hook "afterListingCreate" when compatible will be >= 4.7.2
     *
     * @param object $instance - Instance of class (add/edit listing)
     */
    public function hookAfterListingUpdate($instance = null)
    {
        $this->saveRateRangesFromListing($instance);
    }

    /**
     * @hook  apPhpListingsAfterAdd
     * @since 3.0.0
     */
    public function hookApPhpListingsAfterAdd()
    {
        $this->saveRateRangesFromListing();
    }

    /**
     * @hook  afterListingEdit
     * @since 3.0.0
     */
    public function hookAfterListingEdit()
    {
        $this->saveRateRangesFromListing();
    }

    /**
     * @hook  apPhpListingsAfterEdit
     * @since 3.0.0
     */
    public function hookApPhpListingsAfterEdit()
    {
        $this->saveRateRangesFromListing();
    }

    /**
     * @hook  apTplHeader
     * @since 3.0.0
     */
    public function hookApTplHeader()
    {
        if (in_array($_GET['controller'], array('booking', 'booking_colors'))) {
            echo '<script src="' . RL_LIBS_URL . 'jquery/colorpicker/js/colorpicker.js"></script>';
        }
    }

    /**
     * @hook  tplHeader
     * @since 3.0.0
     */
    public function hookTplHeader()
    {
        global $account_info, $config, $rlSmarty, $reefless, $rlDb, $account_menu;

        // check/show new requests for owners && check statuses of reservations for renters
        if ($account_info['ID']) {
            // new requests
            $sql = "SELECT COUNT(*) AS `Count` FROM `{db_prefix}booking_requests` ";
            $sql .= "WHERE `Owner_ID` = {$account_info['ID']} AND `Status` = 'new'";
            if ($requests = $rlDb->getRow($sql, 'Count')) {
                $rlSmarty->assign_by_ref('booking_requests_url', $reefless->getPageUrl('booking_requests'));
                $rlSmarty->assign_by_ref('new_booking_requests', $requests);
            }

            // changed reservations
            $sql = "SELECT `T2`.`Status`, `T1`.`Renter_notified` FROM `{db_prefix}booking_requests` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}listings_book` AS `T2` ON `T1`.`Book_ID` = `T2`.`ID` ";
            $sql .= "WHERE `T1`.`Renter_ID` = {$account_info['ID']}";
            $reservations = $rlDb->getAll($sql);

            $count_bookings = 0;
            foreach ($reservations as $reservation) {
                if ($reservation['Status'] != 'process' && !$reservation['Renter_notified']) {
                    $count_bookings++;
                }
            }

            if ($reservations) {
                if ($count_bookings) {
                    $rlSmarty->assign_by_ref('booking_reservations_url', $reefless->getPageUrl('booking_reservations'));
                    $rlSmarty->assign_by_ref('changed_booking_reservations', $count_bookings);
                }
            } else {
                // remove "My bookings" page from account menu if it's empty
                foreach ($account_menu as $item_key => $menu_item) {
                    if ($menu_item['Key'] == 'booking_reservations') {
                        unset($account_menu[$item_key]);
                    }
                }
            }

            if ($requests || $reservations) {
                $rlSmarty->display(RL_PLUGINS . 'booking' . RL_DS . 'user-navbar.tpl');
            }

            // remove "Booking Requests" page from menu if account doesn't have ability to create listings for book
            if ($config['membership_module'] && $account_info['plan'] && !(bool) $account_info['plan']['Booking']) {
                foreach ($account_menu as $item_key => $menu_item) {
                    if ($menu_item['Key'] == 'booking_requests') {
                        unset($account_menu[$item_key]);
                    }
                }
            }
        }
    }

    /**
     * @hook  tplFooter
     * @since 3.0.0
     */
    public function hookTplFooter()
    {
        global $rlSmarty;

        // Get fields from Quick search forms
        if (!$searchForms = $rlSmarty->_tpl_vars['search_forms']) {
            // Get fields from Refine search form
            if (!$searchForms = $rlSmarty->_tpl_vars['refine_search_form']) {
                // Get fields from Advanced search form
                if (!$searchForms = $rlSmarty->_tpl_vars['search_form']) {
                    return;
                }
            }
        }

        foreach (new RecursiveIteratorIterator(new RecursiveArrayIterator($searchForms)) as $fieldKey) {
            if ('check_availability' === $fieldKey) {
                $rlSmarty->display(RL_PLUGINS . 'booking/ca_js_handler.tpl');
                break;
            }
        }
    }

    /**
     * @hook  myListingsPreSelect
     * @since 3.0.0
     */
    public function hookMyListingsPreSelect()
    {
        $this->getAllowedPlans();
    }

    /**
     * @hook  staticDataRegister
     * @since 3.3.0 - Added $rlStatic parameter
     * @since 3.0.0
     *
     * @param object|null $rlStatic
     */
    public function hookStaticDataRegister(?object $rlStatic = null): void
    {
        $rlStatic = $rlStatic ?: $GLOBALS['rlStatic'];

        $rlStatic->addJS(RL_PLUGINS_URL . 'booking/static/lib.js', $this->listAllowedPages);
        $rlStatic->addJS(RL_LIBS_URL . 'jquery/jquery.textareaCounter.js', 'booking_requests');
        $rlStatic->addJS(RL_LIBS_URL . 'ckeditor/ckeditor.js', 'booking_requests');

        $rlStatic->addFooterCSS(RL_PLUGINS_URL . 'booking/static/style.css', $this->listAllowedPages);
        $rlStatic->addBoxFooterCSS(RL_PLUGINS_URL . 'booking/static/style.css', 'booking_availability_block');
    }

    /**
     * @hook  apTplContentBottom
     * @since 3.0.0
     */
    public function hookApTplContentBottom()
    {
        global $controller, $lang, $info;

        if (!$controller) {
            return;
        }

        switch ($controller) {
            case 'membership_plans':
            case 'listing_plans':
                // Add booking config to listing plans
                $booking_yes = $_POST['Booking'] == 1 || (!$_POST['Booking'] && $_GET['action'] == 'add')
                ? 'checked="checked"'
                : '';
                $booking_no = isset($_POST['Booking']) && $_POST['Booking'] == 0 ? 'checked="checked"' : '';

                echo <<<HTML
                    <script>
                    $(document).ready(function(){
                        var booking_config_content = '<table class="form"><tbody><tr><td class="name">';
                        booking_config_content += '{$lang['booking_module_plans']}</td>';
                        booking_config_content += '<td class="field">';
                        booking_config_content += '<input {$booking_yes} type="radio" id="booking_yes" ';
                        booking_config_content += 'name="booking" value="1"> ';
                        booking_config_content += '<label for="booking_yes">{$lang['yes']}</label>';
                        booking_config_content += '<input $booking_no type="radio" id="booking_no" ';
                        booking_config_content += 'name="booking" value="0"> ';
                        booking_config_content += '<label for="booking_no">{$lang['no']}</label>';
                        booking_config_content += '</td></tr></tbody></table>';

                        // put new config before Status field
                        $('[name=status]').closest('table').before(booking_config_content);
                    });
                    </script>
HTML;
                break;

            case 'blocks':
                // Hide settings for booking boxes
                if ($_GET['action'] == 'edit'
                    && in_array(
                        $_GET['block'],
                        array(
                            'booking_calendar_box',
                            'booking_availability_block',
                            'booking_order_details_1',
                            'booking_order_details_2',
                        )
                    )
                ) {
                    echo "<script>$('#btypes').hide()</script>";

                    if ($_GET['block'] != 'booking_availability_block') {
                        echo "<script>$('#pages_obj,#cat_checkboxed').closest('tr').hide()</script>";
                    }
                }

                break;
            case 'categories':
                // Hide description text from content of Booking group fields in category builder
                if ($booking_group_id = $GLOBALS['rlDb']->getOne(
                    'ID',
                    "`Key` = 'booking_rates' AND `Status` = 'active'",
                    'listing_groups')
                ) {
                    echo <<<HTML
                        <script>
                        var booking_group_id = 'group_{$booking_group_id}';
                        var booking_group = $('#' + booking_group_id + '.group_obj');

                        if (booking_group.length) {
                            booking_group.find('.group_fields_container').addClass('hide');

                            $(document).ready(function(){
                                $('#form_section').sortable('option','stop')(null, {
                                        item: booking_group
                                            .find('.group_fields_container')
                                            .text('')
                                            .css('min-height', '0')
                                            .css('padding', '0')
                                    }
                                );
                            });
                        }
                        </script>
HTML;
                }
                break;

            case 'pages':
                // Hide settings for booking system pages
                if ($info && $info['Plugin'] == 'booking') {
                    $selectors = '#page_types,[name=controller]';
                    $selectors .= in_array($info['Key'], array('booking_order', 'booking_details', 'booking_requests'))
                    ? ',[name=login]'
                    : '';
                    echo "<script>$('{$selectors}').closest('tr').hide()</script>";
                }
                break;
            case 'listings':
                if (in_array($_GET['action'], array('add', 'edit'))) {
                    $static_directory = RL_PLUGINS_URL . 'booking/static/';
                    echo '<link href="' . $static_directory . 'admin_style.css" type="text/css" rel="stylesheet" />';
                    echo '<script src="' . $static_directory . 'lib.js"></script>';

                    $this->displayRateRangesTable();
                }

                break;
            case 'listing_fields':
                if ($_GET['action'] !== 'edit') {
                    break;
                }

                switch ($_GET['field']) {
                    case 'check_availability':
                        echo "<script>$('[name=\"date[mode]\"]').closest('tr').hide();</script>";
                        break;
                    case 'booking_module':
                        echo "<script>$('[name=\"add_page\"]').closest('tr').hide();</script>";
                        break;
                }
        }
    }

    /**
     * @hook  apTplListingTypesForm
     * @since 3.0.0
     */
    public function hookApTplListingTypesForm()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'booking' . RL_DS . 'admin' . RL_DS . 'booking_type_properties.tpl');
    }

    /**
     * @hook  apPhpListingTypesBeforeAdd
     * @since 3.0.0
     */
    public function hookApPhpListingTypesBeforeAdd()
    {
        global $data;

        $data['Booking_type']               = $_POST['Booking_type'];
        $data['Booking_prepayment_type']    = $_POST['Booking_prepayment_type'];
        $data['Booking_prepayment_percent'] = $_POST['Booking_prepayment_percent'];
        $data['Booking_repeatedly']         = $_POST['Booking_repeatedly'];
    }

    /**
     * @hook  apPhpListingTypesBeforeEdit
     * @since 3.0.0
     */
    public function hookApPhpListingTypesBeforeEdit()
    {
        global $update_date;

        $update_date['fields']['Booking_type']               = $_POST['Booking_type'];
        $update_date['fields']['Booking_prepayment_type']    = $_POST['Booking_prepayment_type'];
        $update_date['fields']['Booking_prepayment_percent'] = $_POST['Booking_prepayment_percent'];
        $update_date['fields']['Booking_repeatedly']         = $_POST['Booking_repeatedly'];
    }

    /**
     * @hook  apPhpListingTypesPost
     * @since 3.0.0
     */
    public function hookApPhpListingTypesPost()
    {
        global $p_key, $config, $rlSmarty, $rlLang, $rlDb;

        if (!empty($p_key)) {
            if (!$this->tmpBookingType) {
                $properties = $GLOBALS['rlDb']->fetch(
                    ['Booking_type', 'Booking_prepayment_type', 'Booking_prepayment_percent', 'Booking_repeatedly'],
                    ['Key' => $p_key, 'Status' => 'active'],
                    null,
                    null,
                    'listing_types',
                    'row'
                );

                $_POST['Booking_type']               = $properties['Booking_type'] ?: 'date_range';
                $_POST['Booking_prepayment_type']    = $properties['Booking_prepayment_type'] ?: 'none';
                $_POST['Booking_prepayment_percent'] = isset($properties['Booking_prepayment_percent'])
                    ? $properties['Booking_prepayment_percent']
                    : 0;
                $_POST['Booking_repeatedly']         = isset($properties['Booking_repeatedly'])
                    ? $properties['Booking_repeatedly']
                    : 1;
            } else {
                $_POST['Booking_type']               = $this->tmpBookingType ?: 'date_range';
                $_POST['Booking_prepayment_type']    = $this->tmpPrepaymentType ?: 'none';
                $_POST['Booking_prepayment_percent'] = $this->tmpPrepaymentPercent ?: 0;
                $_POST['Booking_repeatedly']         = isset($this->tmpRepeatedlyService)
                    ? $this->tmpRepeatedlyService
                    : 1;

                unset(
                    $this->tmpBookingType,
                    $this->tmpPrepaymentType,
                    $this->tmpPrepaymentPercent,
                    $this->tmpRepeatedlyService
                );
            }

            // rent fields
            $rent_fields = $rlDb->fetch(
                array('Key', 'Values', 'Condition'),
                array('Status' => 'active'),
                "AND (`Type` = 'radio' OR `Type` = 'select')
                AND `ID` > 0
                AND (`Key` <> 'Category_ID' AND `Key` <> 'posted_by')
                AND `Condition` <> 'years'",
                null,
                'listing_fields'
            );
            $rent_fields = $rlLang->replaceLangKeys($rent_fields, 'listing_fields', array('name'));

            // get values of rent fields using condition
            foreach ($rent_fields as &$rent_field) {
                if (empty($rent_field['Values']) && $rent_field['Condition']) {
                    $rent_field['Values'] = $GLOBALS['rlCache']->get('cache_data_formats', $rent_field['Condition']);
                } else {
                    $values       = explode(',', $rent_field['Values']);
                    $field_values = array();

                    foreach ($values as $key => $value) {
                        $field_values[] = array(
                            'Key'   => $value,
                            'pName' => 'listing_fields+name+' . $rent_field['Key'] . '_' . $value,
                        );
                    }

                    $rent_field['Values'] = $field_values;
                }
            }
            $rlSmarty->assign('rent_fields', $rent_fields);

            // assign system variables
            $rlSmarty->assign('booking_price_field', $config['booking_price_field_' . $p_key]);

            $rent_field_data = $config['booking_rent_field_' . $p_key]
            ? unserialize($config['booking_rent_field_' . $p_key])
            : array();

            $rlSmarty->assign('booking_rent_field', $rent_field_data[0] ?: false);
            $rlSmarty->assign('booking_rent_field_value', $rent_field_data[1] ?: false);

            $time_frame_field_data = $config['booking_time_frame_field_' . $p_key]
            ? unserialize($config['booking_time_frame_field_' . $p_key])
            : array();

            $rlSmarty->assign('booking_time_frame_field', $time_frame_field_data[0] ?: false);
            $rlSmarty->assign('booking_time_frame_field_value', $time_frame_field_data[1] ?: false);

            // price fields
            $price_fields = $rlDb->fetch(
                array('Key'),
                array('Status' => 'active', 'Type' => 'price'),
                null,
                null,
                'listing_fields'
            );
            $price_fields = $rlLang->replaceLangKeys($price_fields, 'listing_fields', array('name'));
            $rlSmarty->assign_by_ref('price_fields', $price_fields);
        }
    }

    /**
     * @hook  apPhpListingPlansBeforeAdd
     * @since 3.0.0
     */
    public function hookApPhpListingPlansBeforeAdd()
    {
        $GLOBALS['data']['Booking'] = $_POST['booking'];
    }

    /**
     * @hook  apPhpListingPlansBeforeEdit
     * @since 3.0.0
     */
    public function hookApPhpListingPlansBeforeEdit()
    {
        $GLOBALS['update_date']['fields']['Booking'] = $_POST['booking'];
    }

    /**
     * @hook  apPhpListingPlansPost
     * @since 3.0.0
     */
    public function hookApPhpListingPlansPost()
    {
        if (!empty($GLOBALS['p_key'])) {
            $_POST['Booking'] = $GLOBALS['rlDb']->getOne('Booking', "`Key` = '{$GLOBALS['p_key']}'", 'listing_plans');
        }
    }

    /**
     * @hook  apPhpMembershipPlansAfterAdd
     * @since 3.0.0
     */
    public function hookApPhpMembershipPlansAfterAdd()
    {
        global $rlDb;

        $plan_id = $rlDb->insertID();

        if ($rlDb->getOne('ID', "`ID` = {$plan_id}", 'membership_plans')) {
            $rlDb->query("
                UPDATE `{db_prefix}membership_plans` SET `Booking` = '{$_POST['booking']}'
                WHERE `ID` = '{$plan_id}' LIMIT 1
            ");
        }
    }

    /**
     * @hook  apPhpMembershipPlansAfterEdit
     * @since 3.0.0
     *
     * @param string $plan_key
     */
    public function hookApPhpMembershipPlansAfterEdit($plan_key = '')
    {
        if ($plan_key) {
            $GLOBALS['rlDb']->query("
                UPDATE `{db_prefix}membership_plans` SET `Booking` = '{$_POST['booking']}'
                WHERE `Key` = '{$plan_key}' LIMIT 1
            ");
        }
    }

    /**
     * @hook  apPhpMembershipPlansPost
     * @since 3.0.0
     */
    public function hookApPhpMembershipPlansPost()
    {
        if (!empty($GLOBALS['p_key'])) {
            $_POST['Booking'] = $GLOBALS['rlDb']->getOne('Booking', "`Key` = '{$GLOBALS['p_key']}'", 'membership_plans');
        }
    }

    /**
     * @hook  apMixConfigItem
     * @since 3.2.0 - Added $systemSelects parameter
     * @since 3.0.0
     */
    public function hookApMixConfigItem(&$value, &$systemSelects = null)
    {
        $requiredConfigs = ['booking_first_day', 'booking_calendar_position', 'booking_clock_system'];

        if (in_array($value['Key'], $requiredConfigs)) {
            return;
        }

        $systemSelects = array_merge((array) $systemSelects, $requiredConfigs);
    }

    /**
     * @hook  apExtListingFieldsData
     * @since 3.0.0
     */
    public function hookApExtListingFieldsData()
    {
        global $data;

        if (!$data) {
            return;
        }

        // remove booking system field from grid with fields
        // foreach ($data as $field_key => $field_value) {
        //     if (!in_array($field_value['Key'], array('booking_module', 'check_availability'))) {
        //         $tmp_data[] = $field_value;
        //     }
        // }

        // if ($tmp_data) {
        //     $data = $tmp_data;
        //     unset($tmp_data);
        // }
    }

    /**
     * @hook  apPhpCategoriesBuild
     * @since 3.0.0
     */
    public function hookApPhpCategoriesBuild()
    {
        global $fields, $relations;

        // remove booking system fields from category builders
        $tmp_fields = [];
        foreach ($fields as $field_value) {
            if (is_array($field_value) && !in_array($field_value['Key'], ['check_availability', 'booking_module'])) {
                $tmp_fields[] = $field_value;
            }
        }

        if ($tmp_fields) {
            $fields = $tmp_fields;
            unset($tmp_fields);
        }

        // remove booking system fields from already configured listing groups
        foreach ($relations as $group_key => $group) {
            foreach ($group['Fields'] as $field_key => $field_value) {
                if (is_array($field_value) && in_array($field_value['Key'], ['check_availability', 'booking_module'])) {
                    unset($relations[$group_key]['Fields'][$field_key]);
                }
            }
        }
    }

    /**
     * @hook  apAjaxBuildFormPostSaving
     * @since 3.0.0
     */
    public function hookApAjaxBuildFormPostSaving()
    {
        global $transfer, $config, $rlDb;

        $booking_group_id = $rlDb->getOne('ID', "`Key` = 'booking_rates' AND `Status` = 'active'", 'listing_groups');
        $booking_field_id = $rlDb->getOne('ID', "`Key` = 'booking_module' AND `Status` = 'active'", 'listing_fields');
        $booking          = false;

        if (!$booking_group_id || !$transfer['data'] || !$transfer['category_id'] || !$booking_field_id) {
            return false;
        }

        // find booking group in category form
        foreach ($transfer['data'] as $group_key => $group) {
            if ($group_key == 'group_' . $booking_group_id) {
                $booking = true;
                break;
            }
        }

        if ($booking) {
            $booking_group_id_in_relations = $rlDb->getOne(
                'ID',
                "`Category_ID` = '{$transfer['category_id']}' AND `Group_ID` = '{$booking_group_id}'",
                'listing_relations'
            );

            // add booking module to listing form in booking group
            if ($booking_group_id_in_relations) {
                $update['fields']['Fields'] = $booking_field_id;
                $update['where']['ID']      = $booking_group_id_in_relations;
                $GLOBALS['rlActions']->updateOne($update, 'listing_relations');
            }
        }
    }

    /**
     * @hook  editListingPostSimulation
     * @see   postSimulation()
     * @since 3.3.1 - Added $manageListing parameter
     * @since 3.0.0
     */
    public function hookEditListingPostSimulation($manageListing): void
    {
        $this->postSimulation($manageListing);
    }

    /**
     * @hook  apPhpListingsPost
     * @see   postSimulation()
     * @since 3.0.0
     */
    public function hookApPhpListingsPost()
    {
        $this->postSimulation();
    }

    /**
     * @hook  apPhpListingTypesAfterAdd
     * @see   _saveListingTypeProperties()
     * @since 3.0.0
     */
    public function hookApPhpListingTypesAfterAdd()
    {
        $this->_saveListingTypeProperties();
    }

    /**
     * @hook  apPhpListingTypesAfterEdit
     * @see   _saveListingTypeProperties()
     * @since 3.0.0
     */
    public function hookApPhpListingTypesAfterEdit()
    {
        $this->_saveListingTypeProperties();
    }

    /**
     * @hook  addListingPostSimulation
     * @see   postSimulation()
     * @since 3.0.0
     */
    public function hookAddListingPostSimulation()
    {
        $this->postSimulation();
    }

    /**
     * @hook  phpListingsAjaxDeleteListing
     * @see   _deleteListingData()
     * @since 3.0.0
     *
     * @param array $info - Info about deleted listing
     */
    public function hookPhpListingsAjaxDeleteListing($info)
    {
        $this->_deleteListingData($info['ID']);
    }

    /**
     * @hook  phpDeleteListingData
     * @see   _deleteListingData()
     * @since 3.0.0
     *
     * @param int $id
     */
    public function hookPhpDeleteListingData($id)
    {
        $this->_deleteListingData($id);
    }

    /**
     * Add horizontal-arrow.svg on the page
     *
     * @since 3.4.0
     * @hook tplBodyTop
     */
    public function hookTplBodyTop()
    {
        global $listing_data, $page_info;

        $include = false;

        if ($page_info['Controller'] == 'listing_details' && $listing_data) {
            $this->checkBookingAvailable($listing_data);

            $include = $this->availableBooking;
        } elseif (in_array($page_info['Controller'], ['booking_order', 'booking_requests'])) {
            $include = true;
        }

        if ($include) {
            $GLOBALS['rlSmarty']->display('../img/svg/horizontal-arrow.svg');
        }
    }

    /**
     * Removing listing-booking info (rate ranges, phrases)
     *
     * @since 3.0.0
     *
     * @param  int  $id
     * @return bool
     */
    public function _deleteListingData($id)
    {
        global $rlDb, $rlLang;

        $id = (int) $id;

        if (!$id) {
            return false;
        }

        if ($ratesInfo = $rlDb->fetch(
            ['ID', 'From', 'To', 'Listing_ID'],
            ['Listing_ID' => $id],
            null,
            null,
            'booking_rate_range')
        ) {
            $deletePhrases = [];
            foreach ($ratesInfo as $rate) {
                if ($rate['From'] && $rate['To'] && $rate['ID']) {
                    $rlDb->query("DELETE FROM `{db_prefix}booking_rate_range` WHERE `ID` = {$rate['ID']}");

                    $descriptionKey = "booking_range+desc+{$rate['From']}_{$rate['To']}_{$rate['Listing_ID']}";

                    if (method_exists($rlLang, 'deletePhrases')) {
                        $deletePhrases[] = ['Key' => $descriptionKey];
                    } else {
                        $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = '{$descriptionKey}'");
                    }
                }
            }

            if ($deletePhrases && method_exists($rlLang, 'deletePhrases')) {
                $rlLang->deletePhrases($deletePhrases);
            }
        }

        $rlDb->delete(['Listing_ID' => $id], 'booking_rate_range', null, null);
        $rlDb->delete(['Listing_ID' => $id], 'booking_availability', null, null);

        // Remove phrases of regular price
        if (method_exists($rlLang, 'deletePhrase')) {
            $rlLang->deletePhrase(['Key' => "booking_range+regular+desc+{$id}"]);
        } else {
            $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = '{$descriptionKey}'");
        }

        // Remove all requests of this listing
        $rlDb->query(
            "DELETE `T1`, `T2` FROM `{db_prefix}listings_book` AS `T1`
             LEFT JOIN  `{db_prefix}booking_requests` AS `T2` ON `T1`.`ID` = `T2`.`Book_ID`
             WHERE `T1`.`Listing_ID` = {$id}"
        );
    }

    /**
     * Saving booking properties by listing type (adding/removing)
     *
     * @since 3.0.0
     */
    public function _saveListingTypeProperties()
    {
        global $rlConfig, $rlActions, $config, $type_info;

        $lt_key = $_POST['key'];

        if ($lt_key) {
            // save price field
            if (isset($config['booking_price_field_' . $lt_key])) {
                $rlConfig->setConfig('booking_price_field_' . $lt_key, $_POST['Booking_price_field']);
            } else {
                $data = array(
                    'Key'     => 'booking_price_field_' . $lt_key,
                    'Default' => $_POST['Booking_price_field'],
                    'Type'    => 'text',
                    'Plugin'  => 'booking',
                );

                $rlActions->insertOne($data, 'config');
            }

            // save rent field and value
            if (isset($config['booking_rent_field_' . $lt_key])) {
                $rlConfig->setConfig(
                    'booking_rent_field_' . $_POST['key'],
                    $_POST['Booking_rent_field'] && $_POST['Booking_rent_field_value_' . $_POST['Booking_rent_field']]
                    ? serialize(
                        array(
                            $_POST['Booking_rent_field'],
                            $_POST['Booking_rent_field_value_' . $_POST['Booking_rent_field']],
                        )
                    )
                    : ''
                );
            } else {
                $data = array(
                    'Key'     => 'booking_rent_field_' . $lt_key,
                    'Default' =>
                    $_POST['Booking_rent_field'] && $_POST['Booking_rent_field_value_' . $_POST['Booking_rent_field']]
                    ? serialize(
                        array(
                            $_POST['Booking_rent_field'],
                            $_POST['Booking_rent_field_value_' . $_POST['Booking_rent_field']],
                        )
                    )
                    : '',
                    'Type'    => 'text',
                    'Plugin'  => 'booking',
                );

                $rlActions->insertOne($data, 'config');
            }

            // save time frame field and value
            if (isset($config['booking_time_frame_field_' . $lt_key])) {
                $rlConfig->setConfig(
                    'booking_time_frame_field_' . $_POST['key'],
                    $_POST['Booking_time_frame_field'] && $_POST['Booking_time_frame_' . $_POST['Booking_time_frame_field']]
                    ? serialize(
                        array(
                            $_POST['Booking_time_frame_field'],
                            $_POST['Booking_time_frame_' . $_POST['Booking_time_frame_field']],
                        )
                    )
                    : ''
                );
            } else {
                $data = array(
                    'Key'     => 'booking_time_frame_field_' . $lt_key,
                    'Default' =>
                    $_POST['Booking_time_frame_field'] && $_POST['Booking_time_frame_' . $_POST['Booking_time_frame_field']]
                    ? serialize(
                        array(
                            $_POST['Booking_time_frame_field'],
                            $_POST['Booking_time_frame_' . $_POST['Booking_time_frame_field']],
                        )
                    )
                    : '',
                    'Type'    => 'text',
                    'Plugin'  => 'booking',
                );

                $rlActions->insertOne($data, 'config');
            }
        }

        // general category of type
        $general_cat = (int) $_POST['general_cat'];

        // remove booking group from general cat
        if ($general_cat && $_POST['Booking_type'] == 'none' && $type_info['Booking_type'] != 'none') {
            $this->handlerBookingGroupToListingForms('remove', $general_cat);
        }
        // remove booking group from general cat
        else if ($general_cat && $_POST['Booking_type'] != 'none' && $type_info['Booking_type'] == 'none') {
            $this->handlerBookingGroupToListingForms('add', $general_cat);
        }
    }

    /**
     * @hook  specialBlock
     * @since 3.0.0
     */
    public function hookSpecialBlock()
    {
        global $blocks, $page_info, $rlCommon, $rlAccount, $account_menu, $account_info;

        $requestID = $this->getRequestIdFromUrl();
        $_GET['request_id'] = $requestID;

        if ($page_info['Key'] == 'booking_requests') {
            if ((!$requestID || !$rlAccount->isLogin() || $this->isEscort)
                && ($blocks['booking_order_details_1'] || $blocks['booking_order_details_2'])
            ) {
                if ($this->isEscort && $requestID) {
                    if ($rlAccount->isLogin()) {
                        unset($blocks['booking_order_details_2']);

                        // remove box for other users
                        if ($GLOBALS['account_info']['ID'] != $GLOBALS['rlDb']->getOne(
                            'Owner_ID',
                            "`Book_ID` = {$requestID}",
                            'booking_requests')
                        ) {
                            unset($blocks['booking_order_details_1']);
                        }
                    } else {
                        unset($blocks['booking_order_details_1'], $blocks['booking_order_details_2']);
                    }
                } else {
                    unset($blocks['booking_order_details_1'], $blocks['booking_order_details_2']);
                }
            } else {
                $request = $this->getRequestDetails($requestID);

                if ($request['Booking_type'] === 'time_range') {
                    unset($blocks['booking_order_details_2']);
                }
            }

            $rlCommon->defineBlocksExist($blocks);
        } else if ($page_info['Key'] == 'booking_order') {
            $listingID = intval($_POST['listing_id'] ?: $_SESSION['bookingData']['listing_id']);

            if (!$listingID) {
                return;
            }

            $configs = $this->getConfigs($listingID);

            if ($configs['Booking_type'] === 'time_range' && $blocks['booking_order_details_2']) {
                unset($blocks['booking_order_details_2']);
                $rlCommon->defineBlocksExist($blocks);
            }
        }

        // remove "Booking Requests" page from account menu when account doesn't have ability to post listings
        if (!$account_info['Abilities']
            || (count($account_info['Abilities']) == 1 && $account_info['Abilities'][0] == 'export_import')
        ) {
            foreach ($account_menu as $item_key => $menu_item) {
                if ($menu_item['Key'] == 'booking_requests') {
                    unset($account_menu[$item_key]);
                }
            }
        }
    }

    /**
     * @hook  listingNavIcons
     * @since 3.0.0
     */
    public function hookListingNavIcons()
    {
        global $rlSmarty, $reefless;

        $listing_info = $rlSmarty->get_template_vars('listing');

        if ($listing_info) {
            $this->checkBookingAvailable($listing_info);

            if ($this->availableBooking) {
                $rlSmarty->assign('booking_ad_link', $reefless->getListingUrl($listing_info) . '#booking');
                $rlSmarty->display(RL_PLUGINS . 'booking' . RL_DS . 'icon.tpl');
            }
        }
    }

    /**
     * @hook  addListingFormDataChecking
     * @see   _listingDataChecking()
     * @since 3.0.0
     */
    public function hookAddListingFormDataChecking()
    {
        $this->_listingDataChecking();
    }

    /**
     * @hook  editListingDataChecking
     * @see   _listingDataChecking()
     * @since 3.0.0
     */
    public function hookEditListingDataChecking()
    {
        $this->_listingDataChecking();
    }

    /**
     * @hook  apPhpListingsValidate
     * @see   _listingDataChecking()
     * @since 3.0.0
     */
    public function hookApPhpListingsValidate()
    {
        $this->_listingDataChecking();
    }

    /**
     * @hook  apExtPluginsUpdate
     * @since 3.0.0
     */
    public function hookApExtPluginsUpdate()
    {
        global $updateData, $rlDb;

        $status            = $updateData['fields']['Status'];
        $booking_plugin_id = (int) $rlDb->getOne('ID', "`Key` = 'booking'", 'plugins');

        if ($status && $booking_plugin_id == $updateData['where']['ID']) {
            if ($status == 'approval') {
                // remove check-availability field from all search forms
                $b_availability_id = $rlDb->getOne('ID', "`Key` = 'check_availability'", 'listing_fields');
                $rlDb->query("DELETE FROM `{db_prefix}search_forms_relations` WHERE `Fields` = {$b_availability_id}");
            }

            $this->handlerBookingGroupToListingForms($status == 'approval' ? 'remove' : 'add');
        }
    }

    /**
     * @hook  apPhpListingTypesValidate
     * @since 3.0.0
     */
    public function hookApPhpListingTypesValidate()
    {
        global $errors, $error_fields;

        $percent = (int) $_POST['Booking_prepayment_percent'];

        if ($_POST['Booking_type'] != 'none'
            && $_POST['Booking_prepayment_type'] != 'none'
            && ($percent < 1 || $percent > 100)
        ) {
            $errors[] = $GLOBALS['rlLang']->getPhrase('booking_wrong_percent', null, null, true);;
            $error_fields[] = 'Booking_prepayment_percent';

            // save selected values
            if ($_POST['Booking_type'] != 'none') {
                $this->tmpBookingType       = $_POST['Booking_type'];
                $this->tmpPrepaymentType    = $_POST['Booking_prepayment_type'];
                $this->tmpPrepaymentPercent = $_POST['Booking_prepayment_percent'];
                $this->tmpRepeatedlyService = $_POST['Booking_repeatedly'];
            }

            $this->hookApPhpListingTypesPost();
        }
    }

    /**
     * Checking listing data in creation/editing process
     *
     * @since 3.0.0
     */
    public function _listingDataChecking()
    {
        global $error_fields;

        $data = $_POST['f'];

        if ($data['booking_module'] && $this->isEscort) {
            // check availability data
            $availability_exist = false;
            for ($i = 0; $i < 7; $i++) {
                if (intval($data['availability_' . $i]['from']) > 0 && intval($data['availability_' . $i]['to']) > 0) {
                    $availability_exist = true;
                    break;
                }
            }

            // check rates data
            $rate_exist = false;
            if ($data['escort_rates']) {
                foreach ($data['escort_rates'] as $rate) {
                    if (intval($rate['price']) > 0) {
                        $rate_exist = true;
                    }
                }
            }

            // show error if escort data wasn't found
            if (!$rate_exist || !$availability_exist) {
                $GLOBALS['errors'][] = $GLOBALS['rlLang']->getPhrase('booking_escort_data_not_found', null, null, true);

                $error_fields .= !$data['escort_rates'] ? 'f[escort_rates],' : '';
                $error_fields .= !$availability_exist ? 'f[availability],' : '';
            }
        }
    }

    /**
     * @hook  ajaxRequest
     * @since 3.1.0 - Added $request_lang parameter
     * @since 3.0.0
     */
    public function hookAjaxRequest(&$out, $mode, $item, $request_lang = RL_LANG_CODE)
    {
        global $lang, $reefless, $l_timezone;

        if (!$this->isValidAjaxRequest($mode) || !$item) {
            return false;
        }

        $lang = $GLOBALS['rlLang']->getLangBySide('frontEnd', $request_lang);

        // Set timezone for PHP & MySQL
        if (!$l_timezone) {
            // Load system libs
            require_once RL_LIBS . 'system.lib.php';

            if ($l_timezone[$GLOBALS['config']['timezone']][0]) {
                // Set timezone
                $reefless->setTimeZone();
                $reefless->setLocalization();
            }
        }

        switch ($mode) {
            case 'bookingUpdateRateDesc':
                $out = $this->ajaxCaseBookingUpdateRateDesc($item);
                break;
            case 'bookingRemoveRateRange':
                $out = $this->ajaxCaseBookingRemoveRateRange($item);
                break;
            case 'bookingGetRateRanges':
                $out = $this->ajaxCaseBookingGetRateRanges($item);
                break;
            case 'bookingGetDates':
                $out = $this->ajaxCaseBookingGetDates($item);
                break;
            case 'bookingSaveBindingDays':
                $out = $this->ajaxCaseBookingSaveBindingDays($item);
                break;
            case 'bookingRemoveRequest':
                $out = $this->ajaxCaseBookingRemoveRequest($item);
                break;
            case 'bookingRequestHandler':
                $out = $this->ajaxCaseBookingRequestHandler($item);
                break;
            case 'bookingSaveConfigs':
                $out = $this->ajaxCaseBookingSaveConfigs($item);
                break;
        }
    }

    /**
     * Check correct key of ajax requests in front-end side
     *
     * @since 3.1.1
     *
     * @param string $mode
     * @return bool
     */
    public function isValidAjaxRequest($mode = '')
    {
        $validRequests = [
            'bookingUpdateRateDesc',
            'bookingRemoveRateRange',
            'bookingGetRateRanges',
            'bookingGetDates',
            'bookingSaveBindingDays',
            'bookingRemoveRequest',
            'bookingRequestHandler',
            'bookingSaveConfigs',
        ];

        return ($mode && in_array($mode, $validRequests));
    }

    /**
     * Removing rate range.
     * One of several methods for ajax requests, "case" in hookAjaxRequest()
     *
     * @param  array $item - ID of rate & ID of listing
     * @return array
     */
    public function ajaxCaseBookingRemoveRateRange($item)
    {
        global $rlDb, $rlLang;

        $rate_id    = (int) $item['rate_id'];
        $listing_id = (int) $item['listing_id'];

        if ($listing_id) {
            $rate_info = $rlDb->fetch(
                array('From', 'To', 'Listing_ID'),
                array('ID' => $rate_id, 'Listing_ID' => $listing_id),
                null,
                null,
                'booking_rate_range'
            );

            $rate_info = $rate_info[0] ?: false;
        }

        if ($rate_info) {
            if ($rate_info['From'] && $rate_info['To']) {
                $description_key = "booking_range+desc+{$rate_info['From']}_{$rate_info['To']}_{$rate_info['Listing_ID']}";

                if (method_exists($rlLang, 'deletePhrase')) {
                    $rlLang->deletePhrase(['Key' => $description_key]);
                } else {
                    $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = '{$description_key}'");
                }
            }

            $rlDb->query("DELETE FROM `{db_prefix}booking_rate_range` WHERE `ID` = {$rate_id}");

            $out = array(
                'status' => 'OK',
                'data'   => $GLOBALS['rlLang']->getPhrase('booking_rate_range_removed', null, null, true),
            );
        } else {
            $out = array(
                'status'  => 'ERROR',
                'message' => $GLOBALS['rlLang']->getPhrase('booking_rate_remove_notify_fail', null, null, true),
            );
        }

        return $out;
    }

    /**
     * Get data of rate ranges.
     * One of several methods for ajax requests, "case" in hookAjaxRequest()
     *
     * @param  int   $item - ID of listing
     * @return array
     */
    public function ajaxCaseBookingGetRateRanges($item)
    {
        global $lang, $rlDb;

        $listing_id  = (int) $item ? (int) $item : false;
        $rate_ranges = array();

        if ($listing_id) {
            $rate_ranges = $rlDb->fetch(
                array('ID', 'From', 'To', 'Price', 'Listing_ID'),
                array('Listing_ID' => $listing_id),
                "ORDER BY `From`",
                null,
                'booking_rate_range'
            );

            // get listing price
            $price = $this->getRegularPrice($listing_id);
            $price = $price['value'] ?: false;

            // get description of the regular price
            $regular_price_desc = $rlDb->getOne(
                'Value',
                "`Key` = 'booking_range+regular+desc+{$listing_id}'",
                'lang_keys'
            );

            // collect info for out
            $regular_price = array(
                'value' => $price ?: $lang['not_available'],
                'desc'  => $regular_price_desc,
            );
        }

        if ($listing_id) {
            if ($rate_ranges) {
                foreach ($rate_ranges as &$rate) {
                    // get description of rate range
                    $description_key = "booking_range+desc+{$rate['From']}_{$rate['To']}_{$rate['Listing_ID']}";

                    $rate['Desc'] = $rlDb->getOne(
                        'Value',
                        "`Code` = '" . RL_LANG_CODE . "' AND `Key` = '{$description_key}'",
                        'lang_keys'
                    );

                    $rate['From']  = date('M d, Y', $rate['From']);
                    $rate['To']    = date('M d, Y', $rate['To']);
                    $rate['Price'] = $GLOBALS['rlValid']->str2money($rate['Price']);
                }
            }

            $out = array(
                'status' => 'OK',
                'data'   => array('rate_ranges' => ($rate_ranges ?: ''), 'regular_price' => $regular_price),
            );
        } else {
            $out = array(
                'status'  => 'ERROR',
                'message' => $GLOBALS['rlLang']->getPhrase('booking_get_ranges_notify_fail', null, null, true),
            );
        }

        return $out;
    }

    /**
     * Update description of rate range.
     * One of several methods for ajax requests, "case" in hookAjaxRequest()
     *
     * @param  array $item - ID of rate & ID of listing
     * @return array
     */
    public function ajaxCaseBookingUpdateRateDesc($item)
    {
        global $lang, $languages, $rlDb, $rlLang;

        $description = json_decode($_REQUEST['desc']);
        $rate_id     = $item['rate_id'];
        $listing_id  = (int) $item['listing_id'];

        if ($listing_id) {
            if ($rate_id == 'regular') {
                $rate_info       = true;
                $description_key = "booking_range+regular+desc+{$listing_id}";
            } else {
                $rate_info = $rlDb->fetch(
                    array('From', 'To', 'Listing_ID'),
                    array('ID' => $rate_id, 'Listing_ID' => $listing_id),
                    null,
                    null,
                    'booking_rate_range'
                );

                $rate_info       = $rate_info[0] ?: false;
                $description_key = 'booking_range+desc+' . $rate_info['From'] . '_';
                $description_key .= $rate_info['To'] . '_' . $rate_info['Listing_ID'];
            }
        }

        if ($listing_id && $rate_info) {
            $createPhrases = [];
            $updatePhrases = [];
            foreach ($languages as $lang_item) {
                if ($rlDb->getOne(
                    'ID',
                    "`Key`= '{$description_key}' AND `Code` = '{$lang_item['Code']}'",
                    'lang_keys')
                ) {
                    $updatePhrases[] = array(
                        'fields' => array(
                            'Value' => $description,
                        ),
                        'where'  => array(
                            'Key'  => $description_key,
                            'Code' => $lang_item['Code'],
                        ),
                    );
                } else {
                    $createPhrases[] = array(
                        'Code'   => $lang_item['Code'],
                        'Module' => 'common',
                        'Status' => 'active',
                        'Key'    => $description_key,
                        'Value'  => $description,
                        'Plugin' => 'booking',
                    );
                }
            }

            if ($createPhrases) {
                if (method_exists($rlLang, 'createPhrases')) {
                    $rlLang->createPhrases($createPhrases);
                } else {
                    $rlDb->insert($createPhrases, 'lang_keys');
                }
            }

            if ($updatePhrases) {
                if (method_exists($rlLang, 'updatePhrases')) {
                    $rlLang->updatePhrases($updatePhrases);
                } else {
                    $rlDb->update($updatePhrases, 'lang_keys');
                }
            }

            $out = array(
                'status' => 'OK',
                'data'   => $lang['booking_edit_desc_notify'],
            );
        } else {
            $out = array(
                'status'  => 'ERROR',
                'message' => $GLOBALS['rlLang']->getPhrase('booking_edit_desc_notify_fail', null, null, true),
            );
        }

        return $out;
    }

    /**
     * Get dates for calendar.
     * One of several methods for ajax requests, "case" in hookAjaxRequest()
     *
     * @param  array $item - ID of listing & Mode
     * @return array
     */
    public function ajaxCaseBookingGetDates($item)
    {
        $listing_id = (int) $item['listing_id'];
        $mode       = $item['mode'];

        if ($listing_id && $result = $this->ajaxGetDates($listing_id, $mode)) {
            $out = array(
                'status' => 'OK',
                'data'   => $result,
            );
        } else {
            $out = array(
                'status'  => 'ERROR',
                'message' => $GLOBALS['rlLang']->getPhrase('booking_get_dates_notify_fail', null, null, true),
            );
        }

        return $out;
    }

    /**
     * Saving binding days.
     * One of several methods for ajax requests, "case" in hookAjaxRequest()
     *
     * @param  array $item   - ID of listing & Array of binding days
     * @return array
     */
    public function ajaxCaseBookingSaveBindingDays($item)
    {
        $listing_id = (int) $item['listing_id'];
        $formData   = $item['formData'];

        if ($listing_id && $result = $this->ajaxSaveBindingDays($listing_id, $formData)) {
            $out = array(
                'status' => 'OK',
                'data'   => $result,
            );
        } else {
            $out = array(
                'status'  => 'ERROR',
                'message' => $GLOBALS['rlLang']->getPhrase('booking_save_binding_days_notify_fail', null, null, true),
            );
        }

        return $out;
    }

    /**
     * Removing booking request.
     * One of several methods for ajax requests, "case" in hookAjaxRequest()
     *
     * @param  int   $item - ID of request
     * @return array
     */
    public function ajaxCaseBookingRemoveRequest($item)
    {
        $request_id = (int) $item;

        if ($request_id && $result = $this->ajaxDeleteRequest($request_id)) {
            $out = array(
                'status' => 'OK',
                'data'   => $result,
            );
        } else {
            $out = array(
                'status'  => 'ERROR',
                'message' => $GLOBALS['rlLang']->getPhrase('booking_remove_request_notify_fail', null, null, true),
            );
        }

        return $out;
    }

    /**
     * Accepting or Rejecting booking request by owner of listing.
     * One of several methods for ajax requests, "case" in hookAjaxRequest()
     *
     * @param  array $item - ID of request, action and message
     * @return array
     */
    public function ajaxCaseBookingRequestHandler($item)
    {
        global $rlValid;

        $request_id = (int) $item['ID'];
        $action     = $rlValid->xSql($item['action']);
        $data       = $rlValid->xSql($item['data']);

        // adapt message
        $message = preg_replace('#<script(.*?)>(.*?)</script>#is', '', str_replace('\\n', '<br />', $item['message']));

        if ($request_id && $result = $this->ajaxOwnerResult($request_id, $action, $message, $data)) {
            $out = array(
                'status' => 'OK',
                'data'   => $result,
            );
        } else {
            $out = array(
                'status'  => 'ERROR',
                'message' => $GLOBALS['rlLang']->getPhrase('booking_handler_request_notify_fail', null, null, true),
            );
        }

        return $out;
    }

    /**
     * Saving configs for current listing.
     * One of several methods for ajax requests, "case" in hookAjaxRequest()
     *
     * @param  array $item - ID of listing & Array of configs
     * @return array
     */
    public function ajaxCaseBookingSaveConfigs($item)
    {
        global $lang, $rlDb;

        $listing_id = (int) $item['listing_id'];
        $formData   = $item['formData'];

        if ($listing_id && $formData) {
            // adapt info from form
            foreach ($formData as $value) {
                $data[$value['name']] = $value['value'];
            }

            if ($rlDb->getOne('ID', "`Listing_ID` = {$listing_id}", 'booking_configs')) {
                $update = array(
                    'fields' => array(
                        'Deny_guest'          => $data['deny_guest'],
                        'Min_book_day'        => $data['min_book_day'],
                        'Max_book_day'        => $data['max_book_day'],
                        'Fixed_rate_range'    => $data['fixed_rate_range'],
                        'Calendar_restricted' => $data['calendar_restricted'],
                        'Hide_booked_days'    => $data['hide_booked_days'],
                    ),
                    'where'  => array(
                        'Listing_ID' => $listing_id,
                    ),
                );

                $rlDb->updateOne($update, 'booking_configs');
            } else {
                $insert = array(
                    'Listing_ID'          => $listing_id,
                    'Deny_guest'          => $data['deny_guest'],
                    'Min_book_day'        => $data['min_book_day'],
                    'Max_book_day'        => $data['max_book_day'],
                    'Fixed_rate_range'    => $data['fixed_rate_range'],
                    'Calendar_restricted' => $data['calendar_restricted'],
                    'Hide_booked_days'    => $data['hide_booked_days'],
                );

                $rlDb->insertOne($insert, 'booking_configs');
            }

            $out = array(
                'status' => 'OK',
                'data'   => $lang['booking_configs_saved'],
            );
        } else {
            $out = array(
                'status'  => 'ERROR',
                'message' => $GLOBALS['rlLang']->getPhrase('booking_save_configs_notify_fail', null, null, true),
            );
        }

        return $out;
    }

    /**
     * @hook  apAjaxRequest
     *
     * @since 3.1.1 - Added parameters: $out, $item
     * @since 3.0.0
     *
     * @param $out  string
     * @param $item string
     */
    public function hookApAjaxRequest(&$out, $item)
    {
        global $languages, $rlValid, $rlLang, $rlActions;

        $mode = @$_REQUEST['mode'];

        if (!$this->isValidApAjaxRequest($mode) || !$item) {
            return false;
        }

        $GLOBALS['reefless']->loadClass('Actions');

        if (!$languages) {
            // set language
            $request_lang = @$_REQUEST['lang'] ?: $GLOBALS['config']['lang'];
            $rlValid->sql($request_lang);

            // get all languages
            $languages = $rlLang->getLanguagesList();
            $rlLang->defineLanguage($request_lang);
        }

        switch ($mode) {
            case 'bookingDeleteField':
                $out = $this->ajaxCaseApBookingDeleteField($item);
                break;
            case 'bookingDeleteRequest':
                $out = $this->ajaxCaseApBookingDeleteRequest($item);
                break;
            case 'bookingRemoveRateRange':
                $out = $this->ajaxCaseApBookingRemoveRateRange($item);
                break;
            case 'bookingGetRateRanges':
                $out = $this->ajaxCaseApBookingGetRateRanges($item);
                break;
            case 'bookingUpdateRateDesc':
                $out = $this->ajaxCaseApBookingUpdateRateDesc($item);
                break;
        }
    }

    /**
     * @hook  sitemapExcludedPages
     * @since 3.1.0
     */
    public function hookSitemapExcludedPages(&$urls)
    {
        $urls = array_merge($urls, ['booking_order']);
    }

    /**
     * @hook  apExtPluginsData
     * @since 3.2.0
     */
    public function hookApExtPluginsData()
    {
        global $config;

        /** Update cache of system forms
         *  In install process updating of cache works without any plugin integrations in core > 4.8.0
         * @todo - Remove it when problem will be fixed in core
         */
        if ($config['booking_update_cache_tmp']) {
            $GLOBALS['rlCache']->updateForms();
            $GLOBALS['rlDb']->query(
                "DELETE FROM `{db_prefix}config`
                WHERE `Key` = 'booking_update_cache_tmp'"
            );
            unset($config['booking_update_cache_tmp']);
        }
    }

    /**
     * @hook  apExtTransactionItem
     * @since 3.2.0
     */
    public function hookApExtTransactionItem(&$data, $key, $value)
    {
        if (empty($data['Service']) && $value['Service'] === 'booking') {
            $data['Service'] = $GLOBALS['lang']['booking_requests'];
        }
    }

    /**
     * @hook phpUrlBottom
     * @since 3.2.0
     */
    public function hookPhpUrlBottom(&$url, $mode, $data, $custom_lang)
    {
        $backtrace = debug_backtrace(2);
        $backtrace = $backtrace[4] && $backtrace[4]['function'] ? $backtrace[4]['function'] : '';

        if ($data
            && $data['key'] === 'booking_requests'
            && $requestID = $this->getRequestIdFromUrl()
            && !$GLOBALS['bookingOwnGetPageUrlRequest']
            && ($backtrace && $backtrace === 'hreflangTags')
        ) {
            $request = $this->getRequestDetails($requestID);
            $requestTitle = $GLOBALS['rlValid']->str2path($request['title']);

            if ($GLOBALS['config']['mod_rewrite']) {
                $url = $requestTitle . '-r' . $request['ID'];
            } else {
                $url = '&id=' . $request['ID'];
            }

            $GLOBALS['bookingOwnGetPageUrlRequest'] = true;
            $url = $GLOBALS['reefless']->getPageUrl('booking_requests', [$url], $custom_lang);
            $GLOBALS['bookingOwnGetPageUrlRequest'] = false;
        }
    }

    /**
     * @hook phpMetaRelPrevNext
     * @since 3.4.1
     */
    public function hookPhpMetaRelPrevNext($add_url, $custom, &$count, &$per_page)
    {
        global $page_info, $config, $rlSmarty;

        if ($page_info['Key'] == 'booking_reservations') {
            $per_page = (int) $config['booking_reservations_per_page'];
            $count = (int) $rlSmarty->_tpl_vars['pInfo']['calc'];
        }
    }

    /**
     * Get ID of current request from URL
     * @since 3.2.0
     * @return int
     */
    public function getRequestIdFromUrl()
    {
        if ($GLOBALS['config']['mod_rewrite']) {
            preg_match('/(.*)-r([0-9]+)/', $_GET['nvar_1'], $id);
            $requestID = (int) $id[2];
        } else {
            $requestID = (int) $_GET['id'];
        }

        return $requestID;
    }

    /**
     * Convert prices in rates if they created with different currencies
     *
     * @since 3.2.0
     *
     * @param  array $bookingRates
     * @param  bool  $isEscort
     *
     * @return void
     */
    public function convertPricesInRates(&$bookingRates = [], $isEscort = false)
    {
        if (!$GLOBALS['plugins']['currencyConverter'] || !$bookingRates || !is_array($bookingRates)) {
            return;
        }

        global $rlCurrencyConverter, $config;

        if ($isEscort) {
            if (!$rlCurrencyConverter) {
                $GLOBALS['reefless']->loadClass('CurrencyConverter', null, 'currencyConverter');
            }

            $systemCurrency = $rlCurrencyConverter->systemCurrency[$config['system_currency_code']] ?? $config['system_currency_code'];
            $rates = $rlCurrencyConverter->rates;

            if ($rlCurrencyConverter && !$rates) {
                if (method_exists($rlCurrencyConverter, 'getRates')) {
                    $rates = $rlCurrencyConverter->getRates();
                } else {
                    // Add missing rates, because hook "specialBlock" did not run yet
                    eval($GLOBALS['rlDb']->getOne('Code', "`Name` = 'specialBlock' AND `Plugin` = 'currencyConverter'", 'hooks'));
                    $rates = $rlCurrencyConverter->rates;
                }
            }

            if (!$rates || !$systemCurrency) {
                return;
            }
        }

        foreach ($bookingRates as &$rate) {
            if ($isEscort) {
                $subtitle = explode(' ', $rate['subtitle']);

                if (isset($subtitle[0]) && isset($subtitle[1])) {
                    if ($config['system_currency_position'] === 'before') {
                        $rateCurrency = $subtitle[0];
                        $ratePrice    = $subtitle[1];
                    } else {
                        $rateCurrency = $subtitle[1];
                        $ratePrice    = $subtitle[0];
                    }

                    foreach ($rates as $converterRateKey => $converterRate) {
                        $currencyVariantList = [$converterRate['Code'], $converterRate['Symbol']];

                        if (in_array($rateCurrency, $currencyVariantList)
                            || in_array(htmlentities($rateCurrency), $currencyVariantList)
                        ) {
                            $rateCurrency = $converterRateKey;
                            break;
                        }
                    }

                    $adaptedRate = ['price' => $ratePrice, 'currency' => $rateCurrency];

                    self::convertPrice($adaptedRate);

                    if ($config['system_currency_position'] === 'before') {
                        $rate['subtitle'] = $rates[$systemCurrency]['Symbol'] . ' ' . $adaptedRate['price'];
                    } else {
                        $rate['subtitle'] = $adaptedRate['price'] . ' ' . $rates[$systemCurrency]['Symbol'];
                    }
                }
            } else {
                self::convertPrice($rate);
            }
        }
    }

    /**
     * Convert price in system currency for payment gateways
     *
     * @since 3.4.0
     *
     * @param  array $priceData - Value of price with currency (Required params: price|value, currency)
     * @return void
     */
    public static function convertPrice(array &$priceData): void
    {
        global $rlCurrencyConverter, $plugins, $config, $rlValid;

        if (empty($plugins['currencyConverter'])) {
            return;
        }

        if (!$rlCurrencyConverter) {
            $GLOBALS['reefless']->loadClass('CurrencyConverter', null, 'currencyConverter');
        }

        $systemCurrency = $rlCurrencyConverter->systemCurrency[$config['system_currency_code']] ?? $config['system_currency_code'];
        $rates = $rlCurrencyConverter->rates;

        if ($rlCurrencyConverter && !$rates) {
            if (method_exists($rlCurrencyConverter, 'getRates')) {
                $rates = $rlCurrencyConverter->getRates();
            } else {
                // Add missing rates, because hook "specialBlock" did not run yet
                eval($GLOBALS['rlDb']->getOne('Code', "`Name` = 'specialBlock' AND `Plugin` = 'currencyConverter'", 'hooks'));
                $rates = $rlCurrencyConverter->rates;
            }
        }

        if (!$rates || !$systemCurrency || !$priceData['currency']) {
            return;
        }

        $priceIndex    = isset($priceData['price']) ? 'price' : 'value';
        $price         = &$priceData[$priceIndex];
        $currency      = &$priceData['currency'];
        $adaptCurrencyCode = false !== strpos($currency, 'currency_')
            ? strtoupper(str_replace('currency_', '', $currency))
            : $currency;

        // Try detects properly currency code if provided the symbol or other
        if ($adaptCurrencyCode && !$rates[$adaptCurrencyCode]) {
            foreach ($rates as $currencyIndex => $rate) {
                if ($rate['Code'] == $adaptCurrencyCode || $rate['Symbol'] == $adaptCurrencyCode) {
                    $adaptCurrencyCode = $currencyIndex;
                    break;
                }
            }
        }

        if ($adaptCurrencyCode && $adaptCurrencyCode != $systemCurrency && $rates[$adaptCurrencyCode] && $rates[$systemCurrency]) {
            $price = (float) preg_replace('/[^0-9.]/', '', $price);

            // Convert price to dollars
            $price /= $rates[$adaptCurrencyCode]['Rate'];

            // Convert price to system currency
            $price *= $rates[$systemCurrency]['Rate'];
            $price = round($price, 2);

            $currency = $systemCurrency;

            if (isset($priceData['name'])) {
                $currency = $config['system_currency_code'];

                $priceData['name'] = $config['system_currency_position'] == 'before'
                ? ($currency . ' ' . $rlValid->str2money($price))
                : ($rlValid->str2money($price) . ' ' . $currency);
            }
        }
    }

    /**
     * Check correct key of ajax requests in admin side
     *
     * @since 3.1.1
     *
     * @param string $mode
     * @return bool
     */
    public function isValidApAjaxRequest($mode = '')
    {
        $validRequests = [
            'bookingDeleteField',
            'bookingDeleteRequest',
            'bookingRemoveRateRange',
            'bookingGetRateRanges',
            'bookingUpdateRateDesc',
        ];

        return ($mode && in_array($mode, $validRequests));
    }

    /**
     * Removing booking field.
     * One of several methods for admin ajax requests, "case" in hookApAjaxRequest()
     *
     * @param  array $item - Key of field
     * @return array
     */
    public function ajaxCaseApBookingDeleteField($item)
    {
        $GLOBALS['reefless']->loadClass('BoookingFields', null, 'booking');

        $item = $GLOBALS['rlValid']->xSql($item);

        if ($result = $GLOBALS['rlBoookingFields']->ajaxDeleteLField($item)) {
            $out = array(
                'status' => 'OK',
                'data'   => $result,
            );
        } else {
            $out = array(
                'status'  => 'ERROR',
                'message' => $GLOBALS['rlLang']->getPhrase('booking_remove_field_notify_fail', null, null, true),
            );
        }

        return $out;
    }

    /**
     * Removing booking request.
     * One of several methods for admin ajax requests, "case" in hookApAjaxRequest()
     *
     * @param  array $item - ID of request
     * @return array
     */
    public function ajaxCaseApBookingDeleteRequest($item)
    {
        $item = (int) $item;

        if ($result = $this->ajaxDeleteRequestAP($item)) {
            $out = array(
                'status' => 'OK',
                'data'   => $result,
            );
        } else {
            $out = array(
                'status'  => 'ERROR',
                'message' => $GLOBALS['rlLang']->getPhrase('booking_remove_request_notify_fail', null, null, true),
            );
        }

        return $out;
    }

    /**
     * Delete rate range.
     * One of several methods for admin ajax requests, "case" in hookApAjaxRequest()
     *
     * @param array $item - ID of rate range
     */
    public function ajaxCaseApBookingRemoveRateRange($item)
    {
        return $this->ajaxCaseBookingRemoveRateRange($item);
    }

    /**
     * Get data of rate ranges.
     * One of several methods for ajax requests, "case" in hookAjaxRequest()
     *
     * @param int $item - ID of listing
     */
    public function ajaxCaseApBookingGetRateRanges($item)
    {
        return $this->ajaxCaseBookingGetRateRanges($item);
    }

    /**
     * Update description of rate range.
     * One of several methods for ajax requests, "case" in hookAjaxRequest()
     *
     * @param array $item - ID of rate & ID of listing
     */
    public function ajaxCaseApBookingUpdateRateDesc($item)
    {
        return $this->ajaxCaseBookingUpdateRateDesc($item);
    }

    /**
     * Install
     */
    public function install(): void
    {
        global $rlDb, $config, $rlListingTypes;

        $rlDb->createTable(
            'booking_fields',
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
             `Key` varchar(255) NOT NULL,
             `Type` enum('bool','text','textarea','number') NOT NULL DEFAULT 'text',
             `Booking_type` varchar(255) NOT NULL DEFAULT 'date_range,date_time_range,time_range',
             `Default` varchar(255) NOT NULL,
             `Values` mediumtext NOT NULL,
             `Condition` varchar(50) NOT NULL,
             `Required` enum('0','1') NOT NULL DEFAULT '0',
             `Position` int(3),
             `Status` enum('active','approval','trash') NOT NULL DEFAULT 'active',
             PRIMARY KEY (`ID`),
             KEY `Key` (`Key`),
             KEY `Status` (`Status`)"
        );

        $rlDb->createTable(
            'booking_rate_range',
            "`ID` INT(11) NOT NULL AUTO_INCREMENT,
             `Listing_ID` INT(11),
             `Title` VARCHAR(255),
             `From` VARCHAR(10) NOT NULL,
             `To` VARCHAR(10) NOT NULL,
             `Time` VARCHAR(50) NOT NULL,
             `Type` ENUM('single', 'multi') DEFAULT 'multi',
             `Price` VARCHAR(50) NOT NULL,
             `Status` ENUM('active', 'approval') DEFAULT 'active',
             PRIMARY KEY (`ID`),
             KEY `Listing_ID` (`Listing_ID`)"
        );

        $rlDb->createTable(
            'booking_requests',
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
             `Book_ID` int(11),
             `Owner_ID` int(11),
             `Renter_ID` int(11) DEFAULT '0',
             `Renter_notified` enum('0','1') DEFAULT '0',
             `Date` datetime,
             `Status` enum('new','readed') DEFAULT 'new',
             `first_name` varchar(55) NOT NULL,
             `last_name` varchar(55) NOT NULL,
             `email` varchar(255) NOT NULL,
             `phone` varchar(255) NOT NULL,
             `guests` int(11) NOT NULL DEFAULT '0',
             `comment` mediumtext NOT NULL,
             `age` double NOT NULL,
             PRIMARY KEY (`ID`)"
        );

        $rlDb->createTable(
            'listings_book',
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
             `Listing_ID` int(11),
             `From` varchar(10) NOT NULL,
             `To` varchar(10) NOT NULL,
             `Checkin` varchar(255) NOT NULL DEFAULT '',
             `Checkout` varchar(255) NOT NULL DEFAULT '',
             `Amount` varchar(55) NOT NULL DEFAULT '',
             `Status` enum('process','booked','refused') DEFAULT 'process',
             PRIMARY KEY (`ID`)"
        );

        $rlDb->createTable(
            'booking_bindings',
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
             `Listing_ID` int(11),
             `Checkin` varchar(255),
             `Checkout` varchar(255),
             `Status` enum('active','approval') DEFAULT 'active',
             PRIMARY KEY (`ID`)"
        );

        $rlDb->createTable(
            'booking_configs',
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
             `Listing_ID` int(11),
             `Deny_guest` enum('0','1') NOT NULL DEFAULT '0',
             `Min_book_day` int(11),
             `Max_book_day` int(11),
             `Fixed_rate_range` enum('0','1') NOT NULL DEFAULT '0',
             `Calendar_restricted` enum('0','1') NOT NULL DEFAULT '0',
             `Hide_booked_days` enum('0','1') NOT NULL DEFAULT '0',
             PRIMARY KEY (`ID`)"
        );

        $rlDb->createTable(
            'booking_availability',
            "`ID` INT(11) NOT NULL AUTO_INCREMENT,
             `Listing_ID` INT(11) NOT NULL,
             `Availability_0` INT(4) NOT NULL,
             `Availability_1` INT(4) NOT NULL,
             `Availability_2` INT(4) NOT NULL,
             `Availability_3` INT(4) NOT NULL,
             `Availability_4` INT(4) NOT NULL,
             `Availability_5` INT(4) NOT NULL,
             `Availability_6` INT(4) NOT NULL,
             PRIMARY KEY (`ID`),
             KEY `Listing_ID` (`Listing_ID`)"
        );

        // Add plugin fields to listing fields
        $rlDb->query(
            "INSERT INTO `{db_prefix}listing_fields`
            (`Key`, `Type`, `Default`, `Values`, `Condition`, `Details_page`, `Add_page`, `Required`, `Map`, `Status`)
            VALUES
            ('check_availability', 'date', 'single', '', '', '0', '0', '0', '0', 'active'),
            ('booking_module', 'bool', '0', '', '', '0', '1', '0', '0', 'active')"
        );

        // Add booking to listing groups table
        $rlDb->query(
            "INSERT INTO `{db_prefix}listing_groups` (`Key`, `Display`, `Status`)
            VALUES ('booking_rates', '1', 'active')"
        );

        $this->handlerBookingGroupToListingForms('add');

        $rlDb->addColumnsToTable([
            'booking_module'       => "ENUM('0', '1') NOT NULL DEFAULT '0' AFTER `Status`",
            'booking_regular_mode' => "ENUM('0', '1') NOT NULL DEFAULT '0' AFTER `booking_module`",
            'booking_check_in'     => "VARCHAR(255) NOT NULL DEFAULT '' AFTER `booking_module`",
            'booking_check_out'    => "VARCHAR(255) NOT NULL DEFAULT '' AFTER `booking_check_in`",
        ], 'listings');

        $rlDb->addColumnsToTable([
            'Booking_type'               => "ENUM('none', 'date_range', 'date_time_range', 'time_range')
                NOT NULL DEFAULT 'date_range' AFTER `Status`",
            'Booking_repeatedly'         => "ENUM('0', '1') NOT NULL DEFAULT '1' AFTER `Booking_type`",
            'Booking_prepayment_type'    => "ENUM('none', 'for_admin') NOT NULL DEFAULT 'none' AFTER `Booking_repeatedly`",
            'Booking_prepayment_percent' => "VARCHAR(50) NOT NULL DEFAULT '' AFTER `Booking_prepayment_type`",
        ],'listing_types');

        $rlDb->addColumnToTable('Booking', "ENUM('0', '1') NOT NULL DEFAULT '1' AFTER `Status`", 'listing_plans');
        $rlDb->addColumnToTable('Booking', "ENUM('0', '1') NOT NULL DEFAULT '1' AFTER `Status`", 'membership_plans');
        $rlDb->addColumnToTable('Booking_data', "MEDIUMTEXT NOT NULL DEFAULT '' AFTER `Status`", 'transactions');

        // Add additional fields to booking fields table
        $rlDb->query(
            "INSERT INTO `{db_prefix}booking_fields`
            (`Key`, `Type`, `Booking_type`, `Default`, `Values`, `Condition`, `Required`, `Position`, `Status`)
            VALUES
            ('first_name', 'text', 'date_range,date_time_range,time_range', '', '55', '', '1', '1', 'active'),
            ('last_name', 'text', 'date_range,date_time_range,time_range', '', '55', '', '1', '2', 'active'),
            ('email', 'text', 'date_range,date_time_range,time_range', '', '255', 'isEmail', '1', '3', 'active'),
            ('age', 'number', 'date_time_range', '', '2', '', '1', '4', 'active'),
            ('phone', 'text', 'date_range,date_time_range,time_range', '', '50', '', '', '5', 'active'),
            ('guests', 'number', 'date_range', '', '2', '', '', '6', 'active'),
            ('comment', 'textarea', 'date_range,date_time_range,time_range', '', '500', '', '', '7', 'active')"
        );

        // Add booking config to config table
        $rlDb->query(
            "INSERT INTO `{db_prefix}config` (`Key`,`Group_ID`,`Default`,`Plugin`) VALUES
            ('booking_colors', '0', '0,166,153,.8|119,119,119,.3|156,75,155,.8|#00a698|#cccccc|#9c4b9b', 'booking'),
            ('booking_price_field_property', '0', 'price', 'booking'),
            ('booking_rent_field_property', '0', 'a:2:{i:0;s:9:\"sale_rent\";i:1;s:1:\"2\";}', 'booking'),
            ('booking_time_frame_field_property', '0', 'a:2:{i:0;s:10:\"time_frame\";i:1;a:4:{s:3:\"day\";s:1:\"2\";s:4:\"week\";s:1:\"3\";s:5:\"month\";s:1:\"4\";s:4:\"year\";s:1:\"5\";}}', 'booking'),
            ('booking_update_cache_tmp', '0', '1', 'booking')"
        );

        // Update "Page" and "Sticky" options for Availability box
        $rlDb->query(
            "UPDATE `{db_prefix}blocks`
            SET
                `Sticky` = '0',
                `Cat_sticky` = '1',
                `Page_ID` = (SELECT `ID` FROM `{db_prefix}pages` WHERE `Key` = 'home')
            WHERE `Key` = 'booking_availability_block'"
        );

        // Update "Page", "Sticky", "Cat_sticky" options for calendar box
        $rlDb->query(
            "UPDATE `{db_prefix}blocks`
            SET
                `Sticky` = '0',
                `Cat_sticky` = '" . (version_compare($config['rl_version'], '4.8.1', '>=') ? 0 : 1) . "',
                `Page_ID` = (SELECT `ID` FROM `{db_prefix}pages` WHERE `Key` = 'view_details')
            WHERE `Key` = 'booking_calendar_box'"
        );

        // Update "Page", "Sticky" options for Booking Details boxes in order page
        $rlDb->query(
            "UPDATE `{db_prefix}blocks`
            SET
                `Sticky` = '0',
                `Page_ID` = (
                    SELECT GROUP_CONCAT(`ID`) FROM `{db_prefix}pages`
                    WHERE `Key` = 'booking_order' OR `Key` = 'booking_requests'
                )
            WHERE `Key` = 'booking_order_details_1' OR `Key`  = 'booking_order_details_2'"
        );

        if ($this->isEscort) {
            $rlDb->query("UPDATE `{db_prefix}listing_types` SET `Booking_type` = 'time_range'");
        }

        if ($config['package_name'] === 'service') {
            if ($config['service_package_type_service']) {
                $rlDb->updateOne([
                    'fields' => ['Booking_type' => 'time_range'],
                    'where' => ['Key' => $config['service_package_type_service']],
                ], 'listing_types');
            }

            if ($config['service_package_type_task']) {
                $rlDb->updateOne([
                    'fields' => ['Booking_type' => 'none'],
                    'where' => ['Key' => $config['service_package_type_task']],
                ], 'listing_types');

                if ($rlListingTypes->types[$config['service_package_type_task']]
                    && $generalCategory = $rlListingTypes->types[$config['service_package_type_task']]['Cat_general_cat']
                ) {
                    $this->handlerBookingGroupToListingForms('remove', $generalCategory);
                }
            }
        }

        $rlDb->query(
            "UPDATE `{db_prefix}pages` SET `Plugin` = ''
             WHERE `Key` = 'booking_terms_and_conditions'"
        );
    }

    /**
     * Uninstall
     */
    public function uninstall()
    {
        global $rlDb;

        $rlDb->dropTables([
            'booking_rate_range',
            'booking_fields',
            'booking_requests',
            'listings_book',
            'booking_bindings',
            'booking_configs',
            'booking_availability',
        ]);

        // Remove check-availability field from all search forms
        $b_availability_id = $rlDb->getOne('ID', "`Key` = 'check_availability'", 'listing_fields');
        $rlDb->query("DELETE FROM `{db_prefix}search_forms_relations` WHERE `Fields` = {$b_availability_id}");

        // Remove booking-module field from all search forms
        $bookingModule = $rlDb->getOne('ID', "`Key` = 'booking_module'", 'listing_fields');
        $rlDb->query("DELETE FROM `{db_prefix}search_forms_relations` WHERE `Fields` = {$bookingModule}");

        $rlDb->query("DELETE FROM `{db_prefix}listing_groups` WHERE `Key` = 'booking_rates'");
        $rlDb->query("
            DELETE FROM `{db_prefix}listing_fields`
            WHERE `Key` = 'check_availability' OR `Key` = 'booking_module'
        ");

        $rlDb->dropColumnsFromTable(
            ['booking_module', 'booking_regular_mode', 'booking_check_in', 'booking_check_out'],
            'listings'
        );

        $rlDb->dropColumnsFromTable(
            ['Booking_type', 'Booking_repeatedly', 'Booking_prepayment_type', 'Booking_prepayment_percent'],
            'listing_types'
        );

        $rlDb->dropColumnFromTable('Booking', 'listing_plans');
        $rlDb->dropColumnFromTable('Booking', 'membership_plans');
        $rlDb->dropColumnFromTable('Booking_data', 'transactions');

        $booking_group_id = $rlDb->getOne('ID', "`Key` = 'booking_rates' AND `Status` = 'active'", 'listing_groups');
        if ($booking_group_id) {
            $rlDb->query('DELETE FROM `' . RL_DBPREFIX . "listing_relations` WHERE `Group_ID` = {$booking_group_id}");
        }

        $GLOBALS['rlCache']->updateForms();

        $rlDb->query("DELETE FROM `{db_prefix}pages` WHERE `Key` = 'booking_terms_and_conditions'");
        $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` LIKE '%booking_terms_and_conditions'");
    }

    /**
     * Update process of the plugin (copy from core)
     * @since 3.2.0
     * @param string $version
     */
    public function update($version)
    {
        $version_method = 'update' . (int) str_replace('.', '', $version);
        if (method_exists($this, $version_method)) {
            $this->$version_method();
        }
    }

    /**
     * Update to 3.0.0 version
     */
    public function update300()
    {
        global $rlActions, $reefless, $rlDb;

        // remove old files && images
        @unlink(RL_PLUGINS . 'booking/availability_listings.inc.php');
        @unlink(RL_PLUGINS . 'booking/availability_listings.tpl');
        @unlink(RL_PLUGINS . 'booking/booking.css');
        @unlink(RL_PLUGINS . 'booking/static/style_responsive.css');
        @unlink(RL_PLUGINS . 'booking/static/responsive_all_booking.js');
        @unlink(RL_PLUGINS . 'booking/booking_calendar.tpl');
        @unlink(RL_PLUGINS . 'booking/booking_tab.tpl');
        @unlink(RL_PLUGINS . 'booking/booking_fields.tpl');
        @unlink(RL_PLUGINS . 'booking/binding_days.tpl');
        @unlink(RL_PLUGINS . 'booking/listing_rate_ranges.tpl');
        @unlink(RL_PLUGINS . 'booking/listing.tpl');
        @unlink(RL_PLUGINS . 'booking/rate_range.tpl');
        @unlink(RL_PLUGINS . 'booking/ranges.inc.php');

        // removing booking old images with gradient
        if ($images = glob(RL_PLUGINS . 'booking/img/*', GLOB_MARK)) {
            foreach ($images as $image) {
                unlink($image);
            }
        }

        // remove old folders
        $reefless->deleteDirectory(RL_PLUGINS . 'booking/responsive/');
        $reefless->deleteDirectory(RL_PLUGINS . 'booking/js/');
        $reefless->deleteDirectory(RL_PLUGINS . 'booking/img/');

        // remove unnecessary hooks
        $delete_hooks = "DELETE FROM `{db_prefix}hooks` WHERE (";
        $delete_hooks .= "`Name` = 'apPhpConfigBottom' ";
        $delete_hooks .= "OR `Name` = 'listingsModifyGroupSearch'";
        $delete_hooks .= ") AND `Plugin` = 'booking'";
        $rlDb->query($delete_hooks);

        // remove unnecessary configs
        $delete_configs = "DELETE FROM `{db_prefix}config` WHERE (";
        $delete_configs .= "`Key` = 'booking_bind_checkin_checkout' ";
        $delete_configs .= "OR `Key` = 'booking_deny_guest' ";
        $delete_configs .= "OR `Key` = 'booking_min_book_day' ";
        $delete_configs .= "OR `Key` = 'booking_max_book_day' ";
        $delete_configs .= "OR `Key` = 'booking_binding_plans' ";
        $delete_configs .= "OR `Key` = 'booking_plans' ";
        $delete_configs .= "OR `Key` = 'booking_calendar_restricted'";
        $delete_configs .= "OR `Key` = 'booking_calendar_divider'";
        $delete_configs .= "OR `Key` = 'booking_calendar_horizontal'";
        $delete_configs .= "OR `Key` = 'booking_calendar_vertical'";
        $delete_configs .= "OR `Key` = 'booking_additionals'";
        $delete_configs .= "OR `Key` = 'booking_fixed_range'";
        $delete_configs .= ") AND `Plugin` = 'booking'";
        $rlDb->query($delete_configs);

        $rlDb->addColumnsToTable(
            array(
                'booking_check_in'     => "VARCHAR(255) NOT NULL DEFAULT '' AFTER `booking_module`",
                'booking_check_out'    => "VARCHAR(255) NOT NULL DEFAULT '' AFTER `booking_check_in`",
                'booking_regular_mode' => "ENUM('0','1') NOT NULL DEFAULT '0' AFTER `booking_module`",
            ),
            'listings'
        );

        $rlDb->addColumnsToTable(
            array(
                'Booking_type'               => "ENUM('none', 'date_range', 'date_time_range', 'time_range')
                    NOT NULL DEFAULT 'date_range' AFTER `Status`",
                'Booking_prepayment_type'    => "ENUM('none', 'for_admin') NOT NULL DEFAULT 'none' AFTER `Booking_type`",
                'Booking_prepayment_percent' => "VARCHAR(50) NOT NULL DEFAULT '' AFTER `Booking_prepayment_type`",
            ),
            'listing_types'
        );

        $rlDb->addColumnsToTable(
            array('Booking' => "ENUM('0', '1') NOT NULL DEFAULT '1' AFTER `Status`"),
            'listing_plans'
        );

        $rlDb->addColumnsToTable(
            array('Booking' => "ENUM('0', '1') NOT NULL DEFAULT '1' AFTER `Status`"),
            'membership_plans'
        );

        $rlDb->addColumnsToTable(
            array('Booking_data' => "MEDIUMTEXT NOT NULL DEFAULT '' AFTER `Status`"),
            'transactions'
        );

        // create booking configs table
        $rlDb->query("
            CREATE TABLE IF NOT EXISTS `{db_prefix}booking_configs` (
                `ID` int(11) NOT NULL AUTO_INCREMENT,
                `Listing_ID` int(11),
                `Deny_guest` enum('0','1') NOT NULL DEFAULT '0',
                `Min_book_day` int(11),
                `Max_book_day` int(11),
                `Fixed_rate_range` enum('0','1') NOT NULL DEFAULT '0',
                `Calendar_restricted` enum('0','1') NOT NULL DEFAULT '0',
                `Hide_booked_days` enum('0','1') NOT NULL DEFAULT '0',
                PRIMARY KEY (`ID`)
            ) DEFAULT CHARSET=utf8
        ");

        $rlDb->addColumnsToTable(
            array(
                'Renter_notified' => "ENUM('0', '1') NOT NULL DEFAULT '0' AFTER `Renter_ID`",
                'phone'           => "VARCHAR(255) NOT NULL AFTER `email`",
                'guests'          => "INT(11) NOT NULL DEFAULT '0' AFTER `email`",
                'age'             => "DOUBLE NOT NULL AFTER `email`",
                'comment'         => "MEDIUMTEXT NOT NULL"
            ),
            'booking_requests'
        );

        $rlDb->addColumnsToTable(
            array(
                'Checkin'  => "VARCHAR(255) NOT NULL DEFAULT '' AFTER `From`",
                'Checkout' => "VARCHAR(255) NOT NULL DEFAULT '' AFTER `To`",
            ),
            'listings_book'
        );

        $rlDb->addColumnsToTable(
            array('Booking_type' => "VARCHAR(255) NOT NULL DEFAULT 'date_range,date_time_range,time_range'"),
            'booking_fields'
        );

        // add "Age", "Guests", "Phone", "Comment" fields to booking fields
        $new_fields = array(
            array(
                'Key'          => 'age',
                'Type'         => 'number',
                'Booking_type' => 'date_time_range',
                'Values'       => '',
                'Position'     => '4',
            ),
            array(
                'Key'          => 'phone',
                'Type'         => 'text',
                'Booking_type' => 'date_range,date_time_range,time_range',
                'Values'       => '50',
                'Position'     => '5',
            ),
            array(
                'Key'          => 'guests',
                'Type'         => 'number',
                'Booking_type' => 'date_range',
                'Values'       => '2',
                'Position'     => '6',
            ),
            array(
                'Key'          => 'comment',
                'Type'         => 'textarea',
                'Booking_type' => 'date_range,date_time_range,time_range',
                'Values'       => '500',
                'Position'     => '7',
            ),
        );

        $rlActions->insert($new_fields, 'booking_fields');

        // update colors for calendar
        $rlDb->query("
            UPDATE `{db_prefix}config`
            SET `Default` = '0,166,153,.8|119,119,119,.3|156,75,155,.8|#00a698|#cccccc|#9c4b9b'
            WHERE `Key` = 'booking_colors'
        ");

        // add booking config to config table
        $rlDb->query("
            INSERT INTO `{db_prefix}config` (`Key`,`Group_ID`,`Default`,`Plugin`) VALUES
            ('booking_price_field_property', '0', 'price', 'booking'),
            ('booking_rent_field_property', '0', 'a:2:{i:0;s:9:\"sale_rent\";i:1;s:1:\"2\";}', 'booking'),
            ('booking_time_frame_field_property', '0', 'a:2:{i:0;s:10:\"time_frame\";i:1;a:4:{s:3:\"day\";s:1:\"2\";s:4:\"week\";s:1:\"3\";s:5:\"month\";s:1:\"4\";s:4:\"year\";s:1:\"5\";}}', 'booking')
        ");
        $rlDb->query("
            INSERT INTO `{db_prefix}config` (`Key`,`Group_ID`,`Default`,`Plugin`) VALUES
            ('booking_price_field_listings', '0', 'price', 'booking'),
            ('booking_rent_field_listings', '0', 'a:2:{i:0;s:9:\"sale_rent\";i:1;s:1:\"2\";}', 'booking'),
            ('booking_time_frame_field_listings', '0', 'a:2:{i:0;s:10:\"time_frame\";i:1;a:4:{s:3:\"day\";s:1:\"2\";s:4:\"week\";s:1:\"3\";s:5:\"month\";s:1:\"4\";s:4:\"year\";s:1:\"5\";}}', 'booking')
        ");

        // update "Page" and "Sticky" options for Availability box
        $rlDb->query("
            UPDATE `{db_prefix}blocks`
            SET
                `Sticky` = '0',
                `Cat_sticky` = '1',
                `Page_ID` = (SELECT `ID` FROM `{db_prefix}pages` WHERE `Key` = 'home')
            WHERE `Key` = 'booking_availability_block'
        ");

        // update "Page", "Sticky", "Cat_sticky" options for calendar box
        $rlDb->query("
            UPDATE `{db_prefix}blocks`
            SET
                `Sticky` = '0',
                `Cat_sticky` = '1',
                `Page_ID` = (SELECT `ID` FROM `{db_prefix}pages` WHERE `Key` = 'view_details')
            WHERE `Key` = 'booking_calendar_box'
        ");

        // update "Page", "Sticky" options for Booking Details boxes in order page
        $rlDb->query("
            UPDATE `{db_prefix}blocks`
            SET
                `Sticky` = '0',
                `Page_ID` = (
                    SELECT GROUP_CONCAT(`ID`) FROM `{db_prefix}pages`
                    WHERE `Key` = 'booking_order' OR `Key` = 'booking_requests'
                )
            WHERE `Key` = 'booking_order_details_1' OR `Key`  = 'booking_order_details_2'
        ");

        // update "Type" to html for all email templates
        $rlDb->query("UPDATE `{db_prefix}email_templates` SET `Type` = 'html' WHERE `Plugin` = 'booking'");

        $rlDb->query("UPDATE `{db_prefix}pages` SET `Plugin` = '' WHERE `Key` = 'booking_terms_and_conditions'");
    }

    /**
     * Update to 3.2.0 version
     */
    public function update320()
    {
        global $rlDb;

        $rlDb->query(
            "DELETE FROM `{db_prefix}hooks`
             WHERE `Name` = 'apExtListingFieldsData' AND `Plugin` = 'booking'"
        );

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
             WHERE `Plugin` = 'booking' AND `Key` IN (
                'booking_escort_type_notice',
                'booking_calendar_position_listing',
                'booking_calendar_position_box',
                'booking_req_status',
                'booking_processed',
                'booking_amount',
                'ext_booking_listing_id',
                'ext_booking_listing_title',
                'ext_booking_process',
                'booking_desc',
                'booking_fields+name+first_name',
                'booking_fields+name+last_name',
                'booking_fields+name+email',
                'booking_prepayment_for_admin',
                'booking_add_rate_desc',
                'booking_website_visitor',
                'booking_request_applying',
                'booking_step2',
                'booking_legend',
                'booking_month_january',
                'booking_month_february',
                'booking_month_march',
                'booking_month_april',
                'booking_month_may',
                'booking_month_june',
                'booking_month_july',
                'booking_month_august',
                'booking_month_september',
                'booking_month_october',
                'booking_month_november',
                'booking_month_december',
                'booking_monday',
                'booking_tuesday',
                'booking_wednesday',
                'booking_thursday',
                'booking_friday',
                'booking_saturday',
                'booking_sunday',
                'booking_error_fields_empty',
                'booking_rent_field_devide_price',
                'booking_listing_plans_config',
                'ext_booking_checkin',
                'ext_booking_checkout',
                'booking_checkin_box',
                'booking_checkout_box',
                'booking_checkin_auto_box',
                'booking_checkin_escort_box',
                'booking_checkout_auto_box',
                'booking_checkout_escort_box',
                'ext_booking_booked',
                'ext_booking_refused',
                'booking_frontend_page_details',
                'booking_configs_tab',
                'booking_is_url_example',
                'booking_is_domain_example',
                'booking_is_email_example',
                'booking_prepayment_phrase_mail'
            )"
        );

        $rlDb->query(
            "INSERT INTO `{db_prefix}config` (`Key`,`Group_ID`,`Default`,`Plugin`)
             VALUES ('booking_update_cache_tmp', '0', '1', 'booking')"
        );

        @unlink(RL_PLUGINS . 'booking/request.ajax.php');

        $rlDb->createTable(
            'booking_availability',
            "`ID` INT(11) NOT NULL AUTO_INCREMENT,
             `Listing_ID` INT(11) NOT NULL,
             `Availability_0` INT(4) NOT NULL,
             `Availability_1` INT(4) NOT NULL,
             `Availability_2` INT(4) NOT NULL,
             `Availability_3` INT(4) NOT NULL,
             `Availability_4` INT(4) NOT NULL,
             `Availability_5` INT(4) NOT NULL,
             `Availability_6` INT(4) NOT NULL,
             PRIMARY KEY (`ID`),
             KEY `Listing_ID` (`Listing_ID`)"
        );

        $rlDb->addColumnsToTable([
            'Title' => "VARCHAR(255) NOT NULL AFTER `Listing_ID`",
            'Time'  => "VARCHAR(255) NOT NULL AFTER `To`",
            'Type'  => "ENUM('single', 'multi') DEFAULT 'multi' AFTER `Time`",
        ], 'booking_rate_range');

        $rlDb->query("ALTER TABLE `{db_prefix}booking_rate_range` ADD INDEX (`Listing_ID`)");

        $rlDb->addColumnToTable(
            'Booking_repeatedly',
            "ENUM('0', '1') NOT NULL DEFAULT '1' AFTER `Booking_type`",
            'listing_types'
        );

        if (version_compare($GLOBALS['config']['rl_version'], '4.8.1', '>=')) {
            $rlDb->updateOne([
                'fields' => ['Cat_sticky' => '0'],
                'where'  => ['Key' => 'booking_calendar_box', 'Plugin' => 'booking'],
            ], 'blocks');
        }

        if (in_array('ru', array_keys($GLOBALS['languages']))) {
            $russianTranslation = json_decode(file_get_contents(RL_PLUGINS . 'booking/i18n/ru.json'), true);
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
                        'Plugin' => 'booking',
                    ], 'lang_keys');
                }
            }
        }
    }

    /**
     * Update to 3.2.1 version
     */
    public function update321()
    {
        // TMP Fix: Update the scope of updated phrases for non default languages
        $GLOBALS['rlDb']->query(
            "UPDATE `{db_prefix}lang_keys`
             SET `Module` = 'common'
             WHERE `Key` IN (
                 'booking_availability',
                 'booking_rates',
                 'booking_rate_title',
                 'booking_emptyHourlyRanges',
                 'booking_addNewHourlyRate',
                 'booking_rate_selector',
                 'booking_single_rate',
                 'booking_multi_rate'
             )"
        );
    }

    /*** DEPRECATED METHODS ***/
    /**
     * Update process to 3.0.0 version
     * @deprecated 3.2.0
     */
    public function updateTo3Version()
    {}
}
