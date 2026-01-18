
/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : LIB.JS
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
/**
 * @param {array} options
 * @constructor
 */
var EventsCalendar = function (options) {
    var self = this;

    /**
     * @param {array} options
     */
    this.init = function (options) {
        if (options.firstDayOfWeek) {
            self.firstDayOfWeek = options.firstDayOfWeek;
        }
        self.showPassedEvents = options.showPassedEvents;
        self.categoryID = options.category_id;
        self.locale = options.locale;

        if (options.eventDate) {
            self.date = self.selectedDate = new Date(options.eventDate.replace( /(\d{2})-(\d{2})-(\d{4})/, "$2/$1/$3"))
        }
        else {
            self.date = new Date();
        }
        self._create();
        self.enableHeaderClick();

        if (options.cache) {
            self.cachedEvents = options.cache;
        }
        
        self.year = self.date.getFullYear();
        self.month = self.date.getMonth();

        self.setYearsPage(self.year, 0);
        self.renderCalendar(self.cachedEvents);
    };

    /**
     * Set years range in the Years view
     *
     * @param {number} from - Years grid will start from this year
     * @param {number} to   - Years grid will end on this year
     *
     * @returns {boolean}   - Return false if something went wrong
     */
    self.setYearsPage = function (from, to) {
        if (!from && !to) {
            return false;
        }

        if (from && !to) {
            to = from + 12;
        }

        if (!from && to) {
            from = to - 12;
        }

        self.yearsPage.to = to;
        self.yearsPage.from = from;
    };

    /**
     * Enable all click handlers of the calendar
     */
    this.enableHeaderClick = function () {
        $('.calender-header > .title').off('click').click(function () {
            self.toggleCalendarView();
        });

        $('.calender-header > .left').off('click').click(function () {
            self.handleNavigationBarClick('prev');
        });

        $('.calender-header > .right').off('click').click(function () {
            self.handleNavigationBarClick('next');
        });
    };

    /**
     * Re-render calendar view depending on the current view type
     */
    this.updateCalendar = function () {
        switch (self.selectingType) {
            case views.days:
                self.renderCalendar();
                break;
            case views.month:
                self.renderMonthGrid();
                break;
            case views.year:
                self.renderYearsGrid();
                break;
        }
    };

    /**
     * Switching calendar type
     *
     * @param {string} viewType - Type of calendar view: days, month, year
     */
    this.switchCalendarView = function (viewType) {
        self.selectingType = viewType;
        var newClass = self.viewClasses[viewType];
        var currentClass = self.getViewClassOfTheBodyContainer();
        if (currentClass) {
            $('#events-calendar').find('.calendar-body').removeClass(currentClass).addClass(newClass);
        }

        self.updateCalendar();
    };


    /**
     * Toggle calendar view depending on the view type
     */
    this.toggleCalendarView = function () {

        switch (self.selectingType) {
            case views.days:
                self.switchCalendarView(views.month);
                break;
            case views.month:
                self.switchCalendarView(views.year);
                break;
            case views.year:
                self.switchCalendarView(views.days);
                break;
        }
    };

    /**
     * Render month page of the grid
     */
    this.renderMonthGrid = function () {
        var months = self.getAllMonthsNames();

        $('.calender-header').find('.title').text(self.year);

        $('.calendar-body').find('.month > .item').each(function (index, item) {
            $(this).attr('data-month', index);
            $(this).text(self._firsUpper(months[index]));
        });

        self.enableMonthSelect();
    };

    /**
     * Getting all month names of the Year
     *
     * @returns {Array} - Localized month names
     */
    this.getAllMonthsNames = function () {
        var selectedYear = new Date(self.year.toString());
        var months = [];

        for (var i = 0; i < 12; i++) {
            months.push(selectedYear.toLocaleDateString(self.locale, {month: 'short'}));
            selectedYear.setMonth(selectedYear.getMonth() + 1);
        }

        return months;
    };

    /**
     * Enable key press events of the calendar
     */
    this.enableCalendarKeyPressEvents = function () {
        if (self.boxInFocus) {
            $(document).on('keyup', function (event) {
                if (event.keyCode == 37) {
                    self.handleNavigationBarClick('prev');
                }

                if (event.keyCode == 39) {
                    self.handleNavigationBarClick('next');
                }
            });
        }
    };

    /**
     * Display preloader in the calendar UI
     */
    this.enablePreloader = function () {
        $('.calendar-body').addClass('active-preloader');
    };

    /**
     * Hide preloader in the calendar UI
     */
    this.disablePreloader = function () {
        $('.calendar-body').removeClass('active-preloader');
    };

    /**
     * Setter of the boxInFocus property
     *
     * @param {boolean} value
     */
    self.setBoxInFocus = function (value) {
        self.boxInFocus = value;
        // self.enableCalendarKeyPressEvents();
    };

    /**
     * Getter of the boxInFocus property
     *
     * @returns {boolean}
     */
    self.getBoxInFocus = function () {
        return self.boxInFocus;
    };

    /**
     * Handle click on the navigation panel of the calendar UI
     *
     * @param {string} side - Calendar view type
     */
    self.handleNavigationBarClick = function (side) {
        // self.setBoxInFocus(true);
        switch (self.selectingType) {
            case views.days:
                (side === 'prev') ? self.prevMonth() : self.nextMonth();
                break;
            case views.month:
                (side === 'prev') ? self.prevYear() : self.nextYear();
                break;
            case views.year:
                (side === 'prev') ? self.prevYearsList() : self.nextYearsList();
                self.renderYearsGrid();
                break;
        }
    };

    /**
     * Get next year months
     */
    this.nextYear = function () {
        self.year = self.year + 1;
        self.updateCalendar();
    };

    /**
     * Get previous year months
     */
    this.prevYear = function () {
        self.year = self.year - 1;
        self.updateCalendar();
    };

    /**
     * Get next year calendar
     */
    this.nextYearsList = function () {
        self.setYearsPage(self.yearsPage.to, 0);
    };

    /**
     * Render year view type of the calendar UI
     */
    this.renderYearsGrid = function () {
        var yearsList = self._getRangeArray(self.yearsPage.from, self.yearsPage.to);
        $('.calender-header').find('.title').text(self.yearsPage.from + '-' + self.yearsPage.to);

        $('.calendar-body').find('.years .item').each(function (index, item) {
            $(item).text(yearsList[index]);
        });

        self.enableYearSelect();
    };

    /**
     * Return from and to provided values as an array
     *
     * @param {int} from - Array will start from
     * @param {int} to -  Array will end on
     * @returns {Array}
     *
     * @private
     */
    this._getRangeArray = function (from, to) {
        var rangeArray = [];

        for (var i = from; i < to; i++) {
            rangeArray.push(i);
        }

        return rangeArray;
    };

    /**
     * Get previous year calendar
     */
    this.prevYearsList = function () {
        self.setYearsPage(0, self.yearsPage.from);
    };

    /**
     * Render previous month view
     *
     * @returns {EventsCalendar|boolean}
     */
    this.prevMonth = function () {

        if (self.date.getMonth() - 1 == -1) {
            self.month = 11;
            self.year = self.date.getFullYear() - 1;
        }
        else {
            self.month = self.date.getMonth() - 1;
            self.year = self.date.getFullYear();
        }

        self.date = new Date(self.year, self.month, 1);

        self.renderCalendar();

        return this;
    };

    /**
     * Render next month view
     *
     * @returns {EventsCalendar}
     */
    this.nextMonth = function () {
        self.date = new Date(self.date.getFullYear(), self.date.getMonth() + 1, 1);
        self.month = self.date.getMonth();
        self.year = self.date.getFullYear();

        self.renderCalendar();

        return this;
    };

    /**
     * Render all calendar UI
     *
     * @param {Array} cachedEvents - Cached items
     */
    this.renderCalendar = function (cachedEvents) {
        var date = new Date();
        date.setDate(1);
        date.setMonth(self.month);
        date.setFullYear(self.year);
        self.date = date;
        var $calendarBody = $('.calendar-body');

        $('.calender-header').find('.title').text((parseInt(self.month) + 1) + '/' + self.year);


        var data = self._getVisibleMonth(date);

        var weekNames = self._getWeekDaysName(data.days);
        $calendarBody.find('.weeks > .item').each(function (index, item) {
            $(this).text(self._firsUpper(weekNames[index]));
        });

        if (cachedEvents) {
            var newDates = self.combineEventsIntoDays(data.days, cachedEvents);

            return self._renderDays(newDates);
        }

        if (self.events.beforeMonthViewRender instanceof Function) {
            self.enablePreloader();

            try {
                self.events.beforeMonthViewRender(self.year, self.month, data.days).then(function (newDates) {
                    self.disablePreloader();
                    return self._renderDays(newDates);
                }).catch(function (reason) {
                    self.disablePreloader();
                    return self._renderDays(data.days);
                });
            } catch (e) {
                console.log(e.message);
            }
        }

        return self._renderDays(data.days);
    };

    /**
     * Combine cached events with array of the days
     *
     * @param {Object} daysInMonth - All month days object
     * @param {Array} daysWithEvents - Days with events
     */
    this.combineEventsIntoDays = function (daysInMonth, daysWithEvents) {
        return daysInMonth.map(function (date) {
            var day = date.getDate();
            if (date.getMonth() === self.month && day in daysWithEvents) {
                date.events = daysWithEvents[day];
            }

            return date;
        });
    };

    /**
     * Render days view in the calendar UI
     *
     * @param {Object} days - Whole month days object
     *
     * @private
     */
    this._renderDays = function (days) {
        var $calendarBody = $('.calendar-body');
        $calendarBody.find('.dates > .item').each(function (index, item) {
            var day = days[index].getDate();

            $(item).removeClass('has-event finished-event inactive-month today passed-day selected-day');

            if (self.events.beforeDayRender instanceof Function && typeof days[index].events !== 'undefined') {
                self.events.beforeDayRender(item, days[index]);
            }
            else {
                $(item).find('a').attr('href', 'javascript:void(0);');
            }

            var className = self.getDayClass(days[index]);


            $(item).addClass(className);
            $(item).find('a').text(day);
        });
    };

    /**
     * Making first letter of the word in the uppercase
     *
     * @param   {string} string - Provided string where you want to modify first letter
     * @returns {string} string - Modified string
     *
     * @private
     */
    this._firsUpper = function (string) {
        return string[0].toUpperCase() + string.slice(1);
    };

    /**
     * Get date each day of the calendar class
     *
     * @param   {Object} date - Calendar cell object
     *
     * @returns {string} className - Calendar cell main class: 'inactive-month', 'today' or nothing
     */
    this.getDayClass = function (date) {
        var className = '';
        var today = new Date();
        var month = date.getMonth();

        if (month != self.month) {
            className += 'inactive-month';
        }

        if (self.selectedDate!= null && date.toDateString() === self.selectedDate.toDateString()) {
            className += ' selected-day';
        }
        
        if (date.toDateString() === today.toDateString()) {
            className += ' today';
        }
        else if (date.getTime() < today.getTime()) {
            className += ' passed-day';
        }

        return className;
    };

    /**
     * Get all month view days (including days of inactive and non-current class)
     *
     * @param   {Date} date - First day of the provided month
     * @returns {Object}    - Current month view days
     */
    this._getVisibleMonth = function (date) {

        var tmpDays = this.getCountDays(date);
        if (tmpDays != this.monthCountDays) {
            this.monthCountDays = tmpDays;
            this.buildCalendarDays();
        }

        var firstDay = new Date(date.getFullYear(), date.getMonth());
        var monthName = firstDay.toLocaleString(self.locale, {month: 'long'});
        var days = self._getVisibleDays(date);
        self.days = days;

        return {
            monthName: monthName,
            monthNumber: self.month,
            days: days
        };
    };

    /**
     * Getting week days name
     *
     * @param  {Array} calendarPageDays - List of the days of the whole month view
     * @returns {Array}                 - Localized week name
     *
     * @private
     */
    this._getWeekDaysName = function (calendarPageDays) {
        var weekDays = [];

        for (var i = 0; i < 7; i++) {
            weekDays.push(calendarPageDays[i].toLocaleString(self.locale, {weekday: 'short'}));
        }

        return weekDays;
    };

    /**
     * Get all visible days in the days view type of the Calendar UI
     *
     * @param date
     * @returns {any}
     * @private
     */
    this._getVisibleDays = function (date) {
        if (!(date instanceof Date)) {
            return new Error('Flynax Calendar: Invalid date');
        }

        var dates = [];
        var selectedDate = date;
        var firstDateOfTheMonth = new Date(date.getFullYear(), date.getMonth());
        var firstDayIndex = firstDateOfTheMonth.getDay() - (self.firstDayOfWeek);
        if (firstDateOfTheMonth < 0) {
            firstDayIndex += 7;
        }

        firstDateOfTheMonth = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
        while (firstDayIndex > 0) {
            firstDateOfTheMonth.setDate(firstDateOfTheMonth.getDate() - 1);
            dates.unshift(new Date(firstDateOfTheMonth.getTime()));

            firstDayIndex--;
        }

        firstDateOfTheMonth = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
        while (firstDateOfTheMonth.getMonth() == self.month) {
            dates.push(new Date(firstDateOfTheMonth.getTime()));
            firstDateOfTheMonth.setDate(firstDateOfTheMonth.getDate() + 1);
        }

        var nextMonthDays = this.monthCountDays - dates.length;
        var lastDateOfTheMonth = new Date(selectedDate.getFullYear(), selectedDate.getMonth() + 1, 0);
        if (nextMonthDays > 0) {
            while (nextMonthDays > 0) {
                lastDateOfTheMonth.setDate(lastDateOfTheMonth.getDate() + 1);
                dates.push(new Date(lastDateOfTheMonth.getTime()));

                nextMonthDays--;
            }
        }

        return dates;
    };

    /**
     * Return number of days in month
     *
     * @param {number} month
     * @param {number} year
     *
     * @returns {number}
     */
    this.daysInMonth = function (month, year) {
        return new Date(year, month, 0).getDate();
    };

    /**
     * Get main class of the calendar view
     *
     * @return {string}
     */
    this.getViewClassOfTheBodyContainer = function () {
        var $calendarBodyContainer = $('#events-calendar').find('.calendar-body');
        var allClasses = $calendarBodyContainer.attr('class').split(' ');
        var activeClass = '';

        allClasses.forEach(function (item) {
            if (item.includes('events-')) {
                activeClass = item;
            }
        });

        return activeClass;
    };

    /**
     * Enable selecting month handlers
     */
    this.enableMonthSelect = function () {
        $('.calendar-body').find('.month > .item').off('click').click(function () {
            self.month = $(this).attr('data-month');
            self.switchCalendarView(views.days);
        });
    };

    /**
     * Enable selecting year handlers
     */
    this.enableYearSelect = function () {
        $('.calendar-body').find('.years .item').off('click').click(function () {
            self.year = $(this).text();
            self.switchCalendarView(views.month);
        });
    };

    /**
     * Generate template of the calendar
     * @private
     */    
    this._create = function () {
        var daysInWeek = 7;
        
        this.monthCountDays = this.getCountDays(self.date);

        var daysInCalendarView = this.monthCountDays;
        var monthsInMonthView = 12;
        var yearsInYearsView = 12;
        var $calendarBody = $('.calendar-body');

        var $weeksContainer = $calendarBody.find('.weeks');
        var $spanWithWeekName = null;
        for (var i = 0; i < daysInWeek; i++) {
            $spanWithWeekName = $('<span/>', {
                class: 'item'
            });

            $weeksContainer.append($spanWithWeekName);
        }

        this.$daysContainer = $calendarBody.find('.dates');
        this.buildCalendarDays();

        var $monthsContainer = $calendarBody.find('.month');
        for (var i = 0; i < monthsInMonthView; i++) {
            var $monthItem = $('<div/>', {
                class: 'item',
                'data-month': i
            });
            $monthsContainer.append($monthItem);
        }

        var $yearsContainer = $calendarBody.find('.years');
        for (var i = 0; i < yearsInYearsView; i++) {
            var $yearItem = $('<div />', {
                class: 'item'
            });
            $yearsContainer.append($yearItem);
        }
    };

    /**
     * Get count days
    **/
    this.buildCalendarDays = function () {
        this.$daysContainer.empty();
        var $dayItemContainer = null;
        for (var i = 0; i < this.monthCountDays; i++) {
            $dayItemContainer = $('<div/>', {
                class: 'item'
            }).append($('<a/>', {
                href: '#'
            }));
            this.$daysContainer.append($dayItemContainer);
        }
    }

    /**
     * Get count days
    **/
    this.getCountDays = function (date) {
        var y = date.getFullYear(), m = date.getMonth();
        var firstDay = new Date(y, m, 1);
        var lastDay = new Date(y, m + 1, 0);

        var daysVisbleOfMonth = lastDay.getDate();
        var firstDayWeek = firstDay.getDay();
        var lastDayWeek = lastDay.getDay();

        if (firstDayWeek == 0) {
            daysVisbleOfMonth -= 1;
        }

        if (firstDayWeek > 1) {
            daysVisbleOfMonth += firstDayWeek - 1;
        }
        
        if (lastDayWeek < 7 ) {
            if (self.firstDayOfWeek == 1 && lastDayWeek!=0 || self.firstDayOfWeek == 0) {
                daysVisbleOfMonth += 7 - lastDayWeek;
            }
        }

        return daysVisbleOfMonth;
    }

    /**
     * Short format Y-m-d from date
    **/
    this.getShortDate = function (date) {
        var shortDate = '';
        var y = date.getFullYear(), m = date.getMonth()+1, d = date.getDate();

        shortDate =  y + "-" + m + "-" + d;
        
        return shortDate;
    };


    /**
     * Catch all assigned event callbacks
     *
     * @param {string}   eventName - Event name
     * @param {function} callback  - Callable function
     */
    this.catchEvent = function (eventName, callback) {
        if (eventName in self.events && callback instanceof Function) {
            self.events[eventName] = callback;
        }
    };

    /**
     * Available values of the calendar view
     *
     * @type {{days: number, month: number, year: number}}
     */
    const views = {days: 0, month: 1, year: 2};

    /**
     * Available values of the first day of the week option
     *
     * @type {{sunday: number, monday: number}}
     */
    const leadDaysOfTheWeek = {sunday: 0, monday: 1};

    /**
     * View type of the calendar
     * @type {string[]}
     */
    this.viewClasses = ['events-days-view', 'events-month-view', 'events-years-view'];

    /**
     * Currently selected type
     *
     * @type {number}
     */
    this.selectingType = 0;

    /**
     * Does calendar box is in the focus now
     *
     * @type {boolean}
     */
    this.boxInFocus = false;

    /**
     * Working year
     *
     * @type {null}
     */
    this.year = null;

    /**
     * Working month
     *
     * @type {null}
     */
    this.month = null;

    /**
     * Working date
     * @type {null}
     */
    this.date = null;

    /**
     * What should be a first day of the calendar
     * @type {number}
     */
    this.firstDayOfWeek = leadDaysOfTheWeek.sunday;

    /**
     * Calendar localization
     *
     * @type {string}
     */
    this.locale = 'en-us';

    /**
     * Range of the years in the years view of the calendar
     *
     * @type {{from: number, to: number}}
     */
    this.yearsPage = {
        from: 0,
        to: 0
    };

    /**
     * Calendar callable events
     *
     * beforeMonthViewRender: Running before month view rendering calendar
     * beforeDayRender: Running before each day cell render, so you can manipulate if using this callback
     *
     * @type {Object}
     */
    this.events = {
        beforeMonthViewRender: null,
        beforeDayRender: null
    };

    /**
     * Enable cache mode of the plugin
     * @type {boolean}
     */
    this.cachedEvents = false;
    // this.locale = 'ru-ru';
};

/**
 * Events plugin class
 */
var EventsClass = function () {
    var self = this;

    self.promise = function (data) {
        return new Promise(function (resolve, reject) {

            $.ajax({
                url: rlConfig['ajax_url'],
                data: data,
                method: 'POST',
                dataType: 'json',
                success: function (response) {
                    try {
                        if (response.status == 'OK') {
                            resolve(response);
                        }

                        reject(response.message);
                    } catch (e) {
                        reject(e.message);
                    }
                }
            });
        });
    };
};

var eventsCalendar = new EventsCalendar();

eventsCalendar.catchEvent('beforeMonthViewRender', function (year, month, daysInMonth) {
    return new Promise(function (resolve, reject) {
        var eventsObject = new EventsClass();

        var firstDate = eventsCalendar.getShortDate(eventsCalendar.days[0]); 
        var lastDate = eventsCalendar.getShortDate(eventsCalendar.days[eventsCalendar.days.length-1]); 

        var data = {
            item: 'ev_getEvent',
            lang: rlLang,
            category_id: eventsCalendar.categoryID,
            month: month,
            firstDate: firstDate,
            lastDate: lastDate
        };

        eventsObject.promise(data).then(function (value) {

            var daysWithEvents = daysInMonth.map(function (date, index) {
                // var day = date.getDate();
                var day = eventsCalendar.getShortDate(date);
                if (day in value.events) {
                    date.events = value.events[day];

                }

                return date;
            });
            resolve(daysWithEvents);
        }).catch(function (reason) {
            reject(reason);
        });
    });
});

eventsCalendar.catchEvent('beforeDayRender', function (itemUI, day) {
    if (day.events instanceof Object) {
        $(itemUI).addClass('has-event');
        if (day.events.finished) {
            $(itemUI).addClass('finished-event');
        }

        $(itemUI).find('a').attr("href", day.events.link);
    }
});
