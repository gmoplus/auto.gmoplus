
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
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
 *  Flynax Classifieds Software 2025 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

var bookingClass = function(){
    var self = this;

    /**
     * Showing errors
     * @param {string} error - Message of error
    */
    this.errorShow = function(error) {
        printMessage('error', error);
        self.book_color(true);
    };

    /**
     * Selection days on calendar
     * @param {string} o_id - Mktime of date
     */
    this.xSelect = function(o_id) {
        if (bookingConfigs['deny'] === 0) {
            flUtil.loadStyle(rlConfig.tpl_base + 'components/popup/popup.css');
            flUtil.loadScript(rlConfig.tpl_base + 'components/popup/_popup.js', function() {
                let $loginForm = $('#login_modal_source > *').clone();
                $loginForm.find('.caption_padding').hide();

                $('body').popup({
                    click  : false,
                    content: $loginForm,
                    caption: lang.booking_login_popup_title,
                    width  : 300,
                    onShow : function ($popup) {
                        // Prevent closing the popup by click on label with checkbox
                        if ($popup.find('.remember-me')) {
                            $popup.find('input#css_INPUT_1').attr('id', 'css_INPUT_99999');
                            $popup.find('label[for="css_INPUT_1"]').attr('for', 'css_INPUT_99999');
                        }
                    }
                });
            });

            return false;
        }

        var result, start_book, end_book, mass_bind_checkin;

        if (bookingConfigs.Booking_type === 'time_range') {
            // first selection
            if (!bookingConfigs['s_id']) {
                if ($(bookingConfigs['day_prefix'] + o_id).hasClass('booked')) {
                    self.errorShow(lang['booked_day_text']);
                } else if ($(bookingConfigs['day_prefix'] + o_id).hasClass('closed')) {
                    self.errorShow(lang['closed_day_text']);
                } else {
                    mass_bind_checkin = bookingConfigs.bind_checkin !== ''
                    ? bookingConfigs['bind_checkin'].split(',')
                    : false;

                    if (mass_bind_checkin && !in_array(self.getDateByTimeZone(o_id, 'ddd'), mass_bind_checkin)) {
                        self.errorShow(lang.check_in_only_text.replace('{days}', bookingConfigs.bindCheckinLocale));
                    } else {
                        bookingConfigs.s_id     = o_id;
                        start_book              = self.getDateByTimeZone(o_id);
                        bookingConfigs.db_start = Number(o_id);
                        self.bUpdate(o_id);

                        if (rlPageInfo.controller === 'booking_requests' && bookingConfigs.request_id) {
                            bookingConfigs.start_date_value = start_book;
                            self.stepsHandler('update_period', 'update');
                        } else {
                            self.stepsHandler(
                                'details',
                                'show',
                                {
                                    start_book: start_book,
                                    total_days: 1,
                                }
                            );
                        }

                        self.updateEscortAvailabilityData();

                        // Reset fields in booking details
                        var $type_obj = $('[name="escort_rates"]');
                        $type_obj.find('option:selected').removeAttr('selected');
                        $type_obj.find('option[value="-1"]').attr('selected', 'selected');
                        $type_obj.trigger('change');

                        var $time_obj = $('[name="f[booking_check_out]"]');
                        self.selectFirstActiveOption($time_obj);
                        $time_obj.addClass('disabled').attr('disabled', 'disabled');

                        var $duration_obj = $('[name="escort_duration"]');
                        self.selectFirstActiveOption($duration_obj);
                        $duration_obj.addClass('disabled').attr('disabled', 'disabled');
                        // Reset escort fields in booking details end

                        // Update date in booking details
                        var new_date = self.getDateByTimeZone(bookingConfigs['s_id']);

                        var html = '<a id="change_date_button" href="javascript:void(0)">';
                        html += lang['booking_button_select_date'] + '</a>';

                        $('div.field.start_date').html('<b>' + new_date + '</b>' + html);

                        $('#change_date_button').click(function(){
                            self.escortChangeDate();
                        });

                        $('#modal_block div.inner > div.close').trigger('click');
                    }
                }
            } else {
                bookingConfigs['s_id'] = 0;

                if (rlPageInfo['controller'] !== 'booking_order') {
                    $('#booking_message_obj').fadeOut('fast');
                }

                self.book_color(true);
            }
        } else {
            // period selected
            if (bookingConfigs['first'] === 1) {
                if (o_id === bookingConfigs['s_id']) {
                    self.book_color(true);
                    bookingConfigs['first'] = 0;
                    return;
                }

                var mass_bind_checkout = bookingConfigs['bind_checkout'] !== ''
                ? bookingConfigs['bind_checkout'].split(',')
                : false;

                if (mass_bind_checkout
                    && ((!in_array(self.getDateByTimeZone(o_id, 'ddd'), mass_bind_checkout) && o_id > bookingConfigs['s_id'])
                        || (!in_array(self.getDateByTimeZone(bookingConfigs['s_id'], 'ddd'), mass_bind_checkout) && o_id < bookingConfigs['s_id']))
                ) {
                    self.errorShow(lang.check_out_only_text.replace('{days}', bookingConfigs.bindCheckoutLocale));
                } else {
                    let daysInPeriod = self.getCountDaysBetween2Timestamps(o_id, bookingConfigs.s_id);

                    if (daysInPeriod < bookingConfigs.min_bl && bookingConfigs.min_bl > 0) {
                        self.errorShow(
                            lang.min_bl_text.replace(
                                '{nights}',
                                '<span class="daySize">' + parseInt(bookingConfigs.min_bl) + '</span>'
                            )
                        )
                    } else if (daysInPeriod > bookingConfigs['max_bl'] && bookingConfigs['max_bl'] > 0) {
                        self.errorShow(
                            lang.max_bl_text.replace(
                                '{nights}',
                                '<span class="daySize">' + parseInt(bookingConfigs.max_bl) + '</span>'
                            )
                        )
                    } else {
                        if (bookingConfigs['s_id'] > o_id) {
                            result     = self.bUpdate(o_id, bookingConfigs['s_id'], '-');
                            start_book = self.getDateByTimeZone(o_id);
                            end_book   = self.getDateByTimeZone(bookingConfigs['s_id']);

                            bookingConfigs['db_start'] = o_id;
                            bookingConfigs['db_end'] = bookingConfigs['s_id'];
                        } else {
                            result     = self.bUpdate(o_id, bookingConfigs['s_id'], '+');
                            start_book = self.getDateByTimeZone(bookingConfigs['s_id']);
                            end_book   = self.getDateByTimeZone(o_id);

                            bookingConfigs['db_start'] = bookingConfigs['s_id'];
                            bookingConfigs['db_end'] = o_id;
                        }

                        if (result === true) {
                            if (rlPageInfo.controller === 'booking_requests' && bookingConfigs['request_id']) {
                                bookingConfigs['start_date_value'] = start_book;
                                bookingConfigs['end_date_value']   = end_book;

                                self.stepsHandler('update_period', 'update');
                            } else {
                                self.stepsHandler(
                                    'details',
                                    'show',
                                    {
                                        start_book : start_book,
                                        end_book   : end_book,
                                        total_days : daysInPeriod,
                                    }
                                );
                            }
                        } else {
                            self.errorShow(lang['already_booked_text']);
                        }
                    }
                }

                bookingConfigs['first'] = 0;
            } else {
                if ($(bookingConfigs['day_prefix'] + o_id).hasClass('booked')) {
                    self.errorShow(lang['booked_day_text']);
                } else if ($(bookingConfigs['day_prefix'] + o_id).hasClass('closed')) {
                    self.errorShow(lang['closed_day_text']);
                } else {
                    mass_bind_checkin = bookingConfigs['bind_checkin'] !== ''
                    ? bookingConfigs['bind_checkin'].split(',')
                    : false;

                    if (mass_bind_checkin && !in_array(self.getDateByTimeZone(o_id, 'ddd'), mass_bind_checkin)) {
                        self.errorShow(lang.check_in_only_text.replace('{days}', bookingConfigs.bindCheckinLocale));
                    } else {
                        if (bookingConfigs['s_id'] > 0) {
                            bookingConfigs['s_id'] = bookingConfigs['cur_id'];
                        }

                        self.book_color(true);
                        self.bUpdate(o_id);
                        $('#booking_message_obj').fadeOut('fast');

                        if (rlPageInfo.controller === 'booking_order') {
                            $('.booking_message_clone').hide();
                        }

                        bookingConfigs['first'] = 1;
                        bookingConfigs['s_id'] = o_id;
                        bookingConfigs['cur_id'] = o_id;
                    }
                }
            }
        }
    };

    /**
     * Update colors of days on calendar
     * @param {int}     o_id
     * @param {int}     s_id
     * @param {string}  mod             - Maybe "+" or "-"
     * @param {boolean} disable_recount - Paint calendar without recount of amount
     */
    this.bUpdate = function(o_id, s_id, mod, disable_recount) {
        if (bookingConfigs.first === 1) {
            // Prevent booking the period which have 1 already booked day
            if (bookingConfigs.Booking_type !== 'time_range' && bookingConfigs.usBook) {
                for (let i in bookingConfigs.usBook) {
                    if (!bookingConfigs.usBook.hasOwnProperty(i)) {
                        continue;
                    }

                    let start = Number(mod === '+' ? s_id : o_id);
                    let end = Number(mod === '+' ? o_id : s_id);

                    if (Number(bookingConfigs.usBook[i][1]) >= start && Number(bookingConfigs.usBook[i][2]) <= end) {
                        return false;
                    }
                }
            }

            let daysInPeriod = bookingConfigs.Booking_type === 'time_range'
                ? 1
                : self.getCountDaysBetween2Timestamps(o_id, s_id);

            let currentDate = s_id;
            for (let i = 0; i < daysInPeriod; i++) {
                currentDate = mod === '+'
                    ? self.addDaysToTimestamp(currentDate)
                    : self.subtractDaysFromTimestamp(currentDate);

                let $day = $(bookingConfigs.day_prefix + currentDate);
                if ($day.hasClass('booked') || $day.hasClass('closed') || $day.hasClass('unavailable')) {
                    return false;
                }

                bookingConfigs.selected[bookingConfigs.index] = currentDate;
                bookingConfigs.index++;
            }

            self.book_color(false, false, mod, disable_recount);
        } else {
            if (!disable_recount && !bookingConfigs.in_popup) {
                $('#booking_message').html('');
                self.stepsHandler('fields', 'hide');
            }

            $(bookingConfigs.day_prefix + o_id).addClass(
                bookingConfigs.Booking_type !== 'time_range' ? 'daySelect start' : 'daySelect'
            );
            bookingConfigs.selected[bookingConfigs.index] = o_id;
            bookingConfigs.index++;
        }

        return true;
    };

    /**
     * Change color of selected day in calendar and count total price of selected range
     * @param {boolean} erase - Reset of selection
     * @param {boolean} st
     * @param {string}  mod
     * @param {boolean} disable_recount - Paint calendar without recount of amount
     */
    this.book_color = function(erase, st, mod, disable_recount) {
        var iteration = 1;
        var calc = 0;
        var bDebug = '';
        var count_selected_days = bookingConfigs['selected'].length;
        var count_selected_days_without_last = count_selected_days - 1;
        var price = 0;

        if (bookingConfigs['selected']) {
            for (var id = 0; id < count_selected_days; id++) {
                var $dayContainer = $(bookingConfigs['day_prefix'] + bookingConfigs['selected'][id]);
                $dayContainer[erase === true ? 'removeClass' : 'addClass']('daySelect');

                // add special classes for first and last days of selection
                if (erase) {
                    $dayContainer.removeClass('start end');
                } else {
                    if (id === 0) {
                        $dayContainer.addClass(
                            bookingConfigs['selected'][id + 1] > bookingConfigs['selected'][id] ? 'start' : 'end'
                        );
                    } else if (id === count_selected_days - 1) {
                        if (bookingConfigs['selected'][id + 1]) {
                            $dayContainer.addClass(
                                bookingConfigs['selected'][id + 1] > bookingConfigs['selected'][id] ? 'end' : 'start'
                            );
                        } else {
                            $dayContainer.addClass(
                                bookingConfigs['selected'][id - 1] < bookingConfigs['selected'][id] ? 'end' : 'start'
                            );
                        }
                    }
                }

                if (bookingConfigs['first'] === 1 && !disable_recount) {
                    // listing rate range
                    for (var idR in bookingConfigs['usRange']) {
                        price = parseFloat(bookingConfigs['usRange'][idR][2].split('|')[0]);

                        if (bookingConfigs['fixed_range'] === 1) {
                            if (mod === '+') {
                                if (iteration !== count_selected_days) {
                                    if (bookingConfigs['selected'][0] >= bookingConfigs['usRange'][idR][0]
                                        && bookingConfigs['selected'][0] <= bookingConfigs['usRange'][idR][1]
                                    ) {
                                        bDebug += parseFloat(bookingConfigs['usRange'][idR][2].split('|')[0] ) + ' + ';
                                        bookingConfigs['total_cost'] += price;
                                    }
                                }
                            } else {
                                if (iteration !== 1) {
                                    if (bookingConfigs['selected'][count_selected_days_without_last] >= bookingConfigs['usRange'][idR][0]
                                        && bookingConfigs['selected'][count_selected_days_without_last] <= bookingConfigs['usRange'][idR][1]
                                    ) {
                                        bDebug += price + ' + ';
                                        bookingConfigs['total_cost'] += price;
                                    }
                                }
                            }
                        } else {
                            if (bookingConfigs['selected'][id] >= bookingConfigs['usRange'][idR][0]
                                && bookingConfigs['selected'][id] <= bookingConfigs['usRange'][idR][1]
                            ) {
                                if (mod === '+') {
                                    if (iteration !== count_selected_days) {
                                        bDebug += price + ' + ';
                                        bookingConfigs['total_cost'] += price;
                                        calc++;
                                    }
                                } else {
                                    if (iteration !== 1) {
                                        bDebug += price + ' + ';
                                        bookingConfigs['total_cost'] += price;
                                        calc++;
                                    }
                                }
                            }
                        }
                    }
                }
                iteration++;
            }

            if (bookingConfigs['first'] === 1 && !disable_recount) {
                if (bookingConfigs['fixed_range'] === 0) {
                    bDebug += bookingConfigs['booking_debug']
                    ? '( ' + count_selected_days_without_last + ' - ' + calc + ' ) * ' + bookingConfigs['defPrice']
                    : '';
                    bookingConfigs['total_cost'] += (count_selected_days_without_last - calc) * bookingConfigs['defPrice'];
                } else {
                    if (bookingConfigs['total_cost'] === 0) {
                        bDebug += bookingConfigs['booking_debug']
                        ? count_selected_days_without_last + ' * ' + bookingConfigs['defPrice']
                        : '';
                        bookingConfigs['total_cost'] += count_selected_days_without_last * bookingConfigs['defPrice'];
                    }
                }

                if (bookingConfigs['booking_debug']) {
                    console.log(bDebug);
                }
                bookingConfigs['total_cost'] = self.str2money(bookingConfigs['total_cost']);
            }

            if (erase === true) {
                bookingConfigs['selected'] = [];
                bookingConfigs['index'] = 0;
                bookingConfigs['total_cost'] = 0;
            }

            if (st === true) {
                if (bookingConfigs['selected'][0] !== '') {
                    $(bookingConfigs['day_prefix'] + bookingConfigs['selected'][0]).addClass('daySelect');
                }
            }
        }
    };

    /**
     * Change price by money format
     * @param  {string} price - Price
     * @return {string}       - Formatted price
     */
    this.str2money = function(price) {
        price = parseFloat(price);

        if (!price) {
            return false;
        }

        var price_split = price.toFixed(2).split('.');
        var price_int = self.strrev(price_split[0]);
        var price_cents = (price_split[1] ? price_split[1] : '') === '00' ? '' : '.' + price_split[1];
        var len = price_int.length;
        var val = '';

        for (var i = 0; i <= len; i++) {
            val += price_int.charAt(i);
            if (((i + 1) % 3 === 0) && (i + 1 < len)) {
                val += bookingConfigs['price_delimiter'];
            }
        }

        val = self.strrev(val) + price_cents;

        return val;
    };

    this.strrev = function(string) {
        string = string + '';

        var grapheme_extend = /(.)([\uDC00-\uDFFF\u0300-\u036F\u0483-\u0489\u0591-\u05BD\u05BF\u05C1\u05C2\u05C4\u05C5\u05C7\u0610-\u061A\u064B-\u065E\u0670\u06D6-\u06DC\u06DE-\u06E4\u06E7\u06E8\u06EA-\u06ED\u0711\u0730-\u074A\u07A6-\u07B0\u07EB-\u07F3\u0901-\u0903\u093C\u093E-\u094D\u0951-\u0954\u0962\u0963\u0981-\u0983\u09BC\u09BE-\u09C4\u09C7\u09C8\u09CB-\u09CD\u09D7\u09E2\u09E3\u0A01-\u0A03\u0A3C\u0A3E-\u0A42\u0A47\u0A48\u0A4B-\u0A4D\u0A51\u0A70\u0A71\u0A75\u0A81-\u0A83\u0ABC\u0ABE-\u0AC5\u0AC7-\u0AC9\u0ACB-\u0ACD\u0AE2\u0AE3\u0B01-\u0B03\u0B3C\u0B3E-\u0B44\u0B47\u0B48\u0B4B-\u0B4D\u0B56\u0B57\u0B62\u0B63\u0B82\u0BBE-\u0BC2\u0BC6-\u0BC8\u0BCA-\u0BCD\u0BD7\u0C01-\u0C03\u0C3E-\u0C44\u0C46-\u0C48\u0C4A-\u0C4D\u0C55\u0C56\u0C62\u0C63\u0C82\u0C83\u0CBC\u0CBE-\u0CC4\u0CC6-\u0CC8\u0CCA-\u0CCD\u0CD5\u0CD6\u0CE2\u0CE3\u0D02\u0D03\u0D3E-\u0D44\u0D46-\u0D48\u0D4A-\u0D4D\u0D57\u0D62\u0D63\u0D82\u0D83\u0DCA\u0DCF-\u0DD4\u0DD6\u0DD8-\u0DDF\u0DF2\u0DF3\u0E31\u0E34-\u0E3A\u0E47-\u0E4E\u0EB1\u0EB4-\u0EB9\u0EBB\u0EBC\u0EC8-\u0ECD\u0F18\u0F19\u0F35\u0F37\u0F39\u0F3E\u0F3F\u0F71-\u0F84\u0F86\u0F87\u0F90-\u0F97\u0F99-\u0FBC\u0FC6\u102B-\u103E\u1056-\u1059\u105E-\u1060\u1062-\u1064\u1067-\u106D\u1071-\u1074\u1082-\u108D\u108F\u135F\u1712-\u1714\u1732-\u1734\u1752\u1753\u1772\u1773\u17B6-\u17D3\u17DD\u180B-\u180D\u18A9\u1920-\u192B\u1930-\u193B\u19B0-\u19C0\u19C8\u19C9\u1A17-\u1A1B\u1B00-\u1B04\u1B34-\u1B44\u1B6B-\u1B73\u1B80-\u1B82\u1BA1-\u1BAA\u1C24-\u1C37\u1DC0-\u1DE6\u1DFE\u1DFF\u20D0-\u20F0\u2DE0-\u2DFF\u302A-\u302F\u3099\u309A\uA66F-\uA672\uA67C\uA67D\uA802\uA806\uA80B\uA823-\uA827\uA880\uA881\uA8B4-\uA8C4\uA926-\uA92D\uA947-\uA953\uAA29-\uAA36\uAA43\uAA4C\uAA4D\uFB1E\uFE00-\uFE0F\uFE20-\uFE26])/g;
        string = string.replace(grapheme_extend, '$2$1'); // Temporarily reverse

        return string.split('').reverse().join('');
    };

    /**
     * Paint booked and closed days on the calendar
     */
    this.paintUserBook = function() {
        for (id in bookingConfigs.usBook) {
            var pr             = '';
            var status_b       = bookingConfigs.usBook[id][0];
            var st_b           = bookingConfigs.usBook[id][1];
            var en_b           = bookingConfigs.usBook[id][2];
            let daysInPeriod   = bookingConfigs.Booking_type === 'time_range'
                ? 1
                : self.getCountDaysBetween2Timestamps(en_b, st_b);

            var currentDate   = st_b;
            var req_id         = Number(bookingConfigs.usBook[id]['3']);
            var current_req_id = Number(bookingConfigs.request_id);
            var $dayContainer  = null;

            // don't draw days of current request
            if (!current_req_id || (current_req_id !== req_id)) {
                for (let i = 0; i <= daysInPeriod; i++) {
                    if (i !== 0) {
                        currentDate = self.addDaysToTimestamp(currentDate);
                    }

                    pr = status_b === 'process' ? 'pr_' : '';
                    $dayContainer = $(bookingConfigs.day_prefix + currentDate);

                    if (i === 0) {
                        if (bookingConfigs.Booking_type === 'time_range') {
                            if ($dayContainer.hasClass('fully-booked')) {
                                $dayContainer.addClass('booked').removeAttr('title');
                            } else if (!$dayContainer.hasClass('partially-booked')) {
                                $dayContainer.addClass('partially-booked');
                                $dayContainer.append(
                                    $('<img>').attr({
                                        src: rlConfig.plugins_url + 'booking/static/hour.png',
                                        alt: ''
                                    })
                                );
                            }

                            if (bookingConfigs['usBook'][id]['4'] && rlPageInfo.controller !== 'booking_requests') {
                                $dayContainer.addClass('own-request');
                            }
                        } else {
                            $dayContainer.addClass(pr + 'checkin');

                            if (bookingConfigs['usBook'][id]['4'] && rlPageInfo.controller !== 'booking_requests') {
                                $dayContainer.addClass(pr + 'checkin_own');
                            }

                            if ($dayContainer.hasClass('pr_checkout')) {
                                $dayContainer.addClass('booked').removeAttr('title');
                            }

                            if ($dayContainer.hasClass(pr + 'checkout') || $dayContainer.hasClass('checkout')) {
                                $dayContainer.addClass('booked').removeAttr('title');
                            }
                        }
                    } else if (i === daysInPeriod) {
                        if (bookingConfigs.Booking_type !== 'time_range') {
                            $dayContainer.addClass(pr + 'checkout');

                            if (bookingConfigs['usBook'][id]['4'] && rlPageInfo.controller !== 'booking_requests') {
                                $dayContainer.addClass(pr + 'checkout_own');
                            }

                            if ($dayContainer.hasClass(pr + 'checkin') || $dayContainer.hasClass('checkin')) {
                                $dayContainer.addClass('booked').removeAttr('title');
                            }
                        }
                    } else {
                        $dayContainer.addClass('booked').removeAttr('title');
                    }

                    if (bookingConfigs['usBook'][id]['4']
                        && rlPageInfo.controller !== 'booking_requests'
                        && bookingConfigs.Booking_type !== 'time_range'
                    ) {
                        $dayContainer.addClass('own-request');
                    }
                }
            } else {
                // highlight start date of current request
                var $startDay = $(bookingConfigs['day_prefix'] + st_b);
                if (st_b && rlPageInfo.controller === 'booking_requests' && $startDay.hasClass('current-request')) {
                    $startDay.addClass('start');
                }

                // highlight end date of current request
                var $endDay = $(bookingConfigs['day_prefix'] + en_b);
                if (en_b && rlPageInfo.controller === 'booking_requests' && $endDay.hasClass('current-request')) {
                    $(bookingConfigs['day_prefix'] + en_b).addClass('end');
                }
            }
        }

        // Fill closed days
        for (let cd in bookingConfigs.closeRange) {
            let st_close     = bookingConfigs.closeRange[cd][0];
            let end_close    = bookingConfigs.closeRange[cd][1];
            let daysInPeriod = self.getCountDaysBetween2Timestamps(end_close, st_close);
            let currentDate  = st_close;

            for (let j = 0; j <= daysInPeriod; j++) {
                if (j !== 0) {
                    currentDate = self.addDaysToTimestamp(currentDate);
                }

                $(bookingConfigs.day_prefix + currentDate).removeClass().addClass('closed').removeAttr('title');
            }
        }
    };

    /**
     * Change dates in calendar | First step
     * @param {string} mode - Next or previous month
     */
    this.changeDates = function(mode) {
        self.booking_mask('set');
        self.getDates(bookingConfigs['listing_id'], mode);
    };

    /**
     * Adding/Removing mask with "loading" to calendar
     * @param {string} mode - Detect adding or removing
     */
    this.booking_mask = function(mode) {
        if (mode === 'set') {
            $('#booking_calendar').addClass('loading');
        } else if (mode === 'reset') {
            $('#booking_calendar').removeClass('loading');
        }
    };

    /**
     * Accepting or Rejecting booking request by owner of listing
     * @param  {string} action     - Some of action
     * @param  {int}    require_id - ID of request
     */
    this.bookingRequestHandler = function(action, require_id) {
        if (action === 'accept') {
            $('#accept_btn').val(lang['loading']);
        } else if (action === 'refuse') {
            $('#refuse_btn').val(lang['loading']);
        }

        var message = CKEDITOR.instances.textarea_answer.getData();

        $('#accept_btn,#refuse_btn').addClass('disabled').attr('disabled', true);
        $('.cancel').fadeOut('fast');
        $('#refuse_btn').after('<span>' + lang['booking_refuse'] + '</span>');

        // get updated checkIn/checkOut time values if owner changed it
        var checkin_time = bookingConfigs.Booking_type === 'time_range'
        ? bookingConfigs.escort_type_service
        : bookingConfigs.check_in;

        var checkout_time = bookingConfigs.Booking_type === 'time_range'
        ? bookingConfigs.escort_time_service
        : bookingConfigs.check_out;

        var request = {
            mode: 'bookingRequestHandler',
            item: {
                ID      : require_id,
                action  : action,
                message : message ? message : '',
                data    : {
                            db_start       : bookingConfigs.db_start,
                            db_end         : bookingConfigs.Booking_type === 'time_range'
                                ? bookingConfigs.escort_duration
                                : bookingConfigs.db_end,
                            total_cost     : bookingConfigs.total_cost,
                            checkin_time   : checkin_time,
                            checkout_time  : checkout_time,
                            period_updated : bookingConfigs.period_updated
                        }
            },
            lang: rlLang
        };

        $.post(
            rlConfig.ajax_url,
            request,
            function(response){
                if (response.status === 'OK') {
                    $('#owner_actions').fadeOut('fast');
                    $('#change_period').fadeOut('slow', function(){ $(this).remove() });
                    $('.current_dates').removeClass('current_dates');

                    if (action === 'accept') {
                        $('#owRes').html(lang.booking_accepted);
                    } else if (action === 'refuse') {
                        $('#owRes').html(lang.booking_refused);
                    }

                    if (bookingConfigs.Booking_type === 'time_range') {
                        $('#change_date_button').addClass('hide');

                        if (bookingConfigs['period_updated']) {
                            $('div.field.type').html($('[name="escort_rates"] option:selected').text());
                            $('div.field.time').html($('[name="f[booking_check_out]"] option:selected').text());

                            var duration    = '';
                            var $duraionObj = $('[name="escort_duration"] option:selected');

                            if (parseInt($duraionObj.val()) > 0) {
                                duration = $duraionObj.text();
                                $('div.field.duration').html(duration);
                            } else {
                                $('div.field.duration').closest('div.table-cell').addClass('hide');
                            }
                        }
                    }

                    // update calendar
                    bookingConfigs['request_id'] = null;
                    self.getDates(bookingConfigs.listing_id);

                    printMessage('notice', response.data);
                } else {
                    printMessage('error', response.message);
                }
            },
            'json'
        );
    };

    /**
     * Remove request from page
     * @param {int}    id       - ID of request
     * @param {string} redirect - Custom redirect to prev page if page have pagination
     */
    this.removeRequest = function(id, redirect) {
        if (!id) {
            return false;
        }

        $(this).val(lang['loading']);

        var request = {mode: 'bookingRemoveRequest', item: id, lang: rlLang};

        $.post(rlConfig.ajax_url, request, function(response){
            $('#modal_block div.close').trigger('click');
            if (response.status === 'OK') {
                $('#item_request_' + id).fadeOut('fast', function() {
                    $('#item_request_' + id).remove();

                    // hide table if table is empty
                    var parent = false;
                    if ($('#area_requests').length || $('#area_booking').length) {
                        if ($('#area_requests').length && $('#area_requests .list-table > .row').length <= 0) {
                            parent = $('#area_requests');
                        }
                        if ($('#area_booking').length && $('#area_booking .list-table > .row').length <= 0) {
                            parent = $('#area_booking');
                        }
                    }

                    if ($('#listings').length) {
                        if ($('#listings article.item').length <= 0) {
                            parent = $('#listings');
                        }
                    }

                    if (parent) {
                        if ($(parent).find('.list-table').length > 0) {
                            $(parent).find('.list-table').fadeOut('fast');
                        }

                        // reservation page
                        if ($('#listings').length > 0) {
                            $(parent).parent().html('<div class="text-notice">' + lang['booking_no_reservations'] + '</div>');

                            // redirect to previous page
                            if (redirect) {
                                window.location.href = redirect;
                            }
                        }
                        // other pages
                        else {
                            $(parent).html('<div class="text-notice">' + lang['booking_no_requests'] + '</div>');
                        }
                    }
                });

                setTimeout(function(){ printMessage('notice', response.data) }, 500);
            } else {
                setTimeout(function(){ printMessage('error', response.message) }, 500);
            }
        }, 'json');
    };

    /**
     * Window with confirmation of removing request
     * @param {string} redirect - Custom redirect if page have pagination
     */
    this.requestRemoveHandler = function(redirect) {
        $('.remove_request').each(function(){
            $(this).flModal({
                caption: lang['warning'],
                content: lang['booking_remove_notice'],
                prompt : "booking.removeRequest(" + $(this).attr('id') + ", '" + redirect + "')",
                width  : 'auto',
                height : 'auto'
            });
        });
    };

    /**
     * Adding new rate range to list
     */
    this.addRateRange = function() {
        var lCurrency = $('select[name="f[price][currency]"] option:selected').text();
        var field = '<div class="row tmp" id="add_rate_' + bookingConfigs['current_field'] + '">';
        field += '<div data-caption="' + lang['from'] + '">';
        field += '<input class="w120" type="text" name="b[' + bookingConfigs['current_field'] + '][from]" ';
        field += 'id="brr_from_' + bookingConfigs['current_field'] + '" value="" autocomplete="off" /></div>';
        field += '<div data-caption="' + lang['to'] + '">';
        field += '<input class="w120" type="text" name="b[' + bookingConfigs['current_field'] + '][to]" ';
        field += 'id="brr_to_' + bookingConfigs['current_field'] + '" value="" autocomplete="off" /></div>';
        field += '<div data-caption="' + lang['price'] + '">';
        field += bookingConfigs.system_currency_position === 'before' ? lCurrency + ' ' : '';
        field += '<input type="text" class="numeric w120 price" name="b[' + bookingConfigs['current_field'] + '][price]" ';
        field += 'id="price_' + bookingConfigs['current_field'] + '" value="" />';
        field += bookingConfigs.system_currency_position === 'after' ? ' ' + lCurrency : '';
        field += '<img class="qtip booking_qtip" alt="" title="' + lang['booking_close_rate_range_notice'];
        field += '" id="rate_desc_' + bookingConfigs['current_field'] + '" src="';
        field += bookingConfigs['src_delete_img'] + '" />' + '</div>';

        /* description */
        field += '<div data-caption="' + lang.description + '" class="rate-ranges-description">';
        field += '<a href="javascript:void(0)" onclick="booking.addDesc(' + bookingConfigs['current_field'] + ')">';
        field += lang.description + '</a>';
        field += '<textarea class="hide" id="save_desc_' + bookingConfigs['current_field'] + '" ';
        field += 'name="b[' + bookingConfigs['current_field'] +'][desc]" cols="30" rows="2"></textarea>';
        field += '</div>';
        /* description end */

        field += '<div data-caption="' + lang['actions'] + '" class="rate-ranges-actions">';
        field += '<img class="remove" onclick="booking.removeRate(' + bookingConfigs['current_field'] + ')" ';
        field += 'title="' + lang['delete'] + '" alt="' + lang['delete'] + '" src="';
        field += bookingConfigs['src_delete_img'] + '" /></div>';
        field += '</div>';

        $('#booking_rate_ranges_list > div.list-table #rrange_regular').before(field);

        self.rateRangeFieldsHandler(bookingConfigs['current_field']);
        setTimeout(function(){ bookingConfigs['current_field']++ }, 200);
    };

    /**
     * Apply handlers to new rate range fields
     * @param {int} id
     */
    this.rateRangeFieldsHandler = function(id) {
        $('.numeric').numeric();

        self.qtipInit(true);

        var rate_ranges_selector = '';

        if (id) {
            rate_ranges_selector = '#brr_from_' + id + ',#brr_to_' + id;
        } else {
            for (var i = 1; i < parseInt(bookingConfigs['current_field']); i++) {
                rate_ranges_selector = rate_ranges_selector
                ? rate_ranges_selector + ', #brr_from_' + i + ', #brr_to_' + i
                : '#brr_from_' + i + ', #brr_to_' + i;
            }
        }

        if (rate_ranges_selector) {
            setTimeout(function(){
                var dp_regional = bookingConfigs['rlLang'] === 'en' ? 'en-GB' : bookingConfigs['rlLang'];
                var dates = $(rate_ranges_selector)
                    .datepicker('destroy')
                    .datepicker({
                        showOn: 'focus',
                        dateFormat: 'dd-mm-yy',
                        minDate: self.getMinRateDate(),
                        onSelect: function(selectedDate) {
                            if (this.id.indexOf('from') !== -1) {
                                var instance = $(this).data("datepicker"),
                                date = $.datepicker.parseDate(instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings);
                                var to_date = date.getDate() + parseInt(bookingConfigs['booking_min_book_day']);
                                date.setDate(to_date);
                                dates.not(this).datepicker("option", "minDate", date);

                                var mMonth = date.getMonth() + 1;
                                var mDay = date.getDate();
                                mMonth = mMonth < 10 ? '0'+ mMonth : mMonth;
                                mDay = mDay < 10 ? '0'+ mDay : mDay;

                                dates.not(this).val(mDay + '-' + mMonth + '-' + date.getFullYear());
                            }
                        }
                    })
                    .datepicker($.datepicker.regional[dp_regional]);
            }, 100);
        }
    };

    /**
     * Get minimum date for new rate range (by exist ranges)
     */
    this.getMinRateDate = function() {
        var default_date = new Date();
        var targetTimeOffset = bookingConfigs['bookingTimeOffset'] * 60; // desired time zone
        default_date.setMinutes(default_date.getMinutes() + default_date.getTimezoneOffset() + targetTimeOffset);

        var exist_min_date = '';
        var previous_field = bookingConfigs['current_field'] - 1;
        var min_date = bookingConfigs['current_field'] > 1 && !bookingConfigs['post_rate_ranges']
        ? ($('[name="b[' + previous_field + '][to]"]').length
            ? $('[name="b[' + previous_field + '][to]"]').val()
            : $('#rrange_' + previous_field + ' div[data-caption="to"]').text())
        : default_date;

        /* find value from exist rate ranges (if new created rate is empty) */
        if (!min_date) {
            for (var i = previous_field; i > 0; i--) {
                if ($('#rrange_' + i + ' div[data-caption="to"]').length
                    && $('#rrange_' + i + ' div[data-caption="to"]').text()
                ) {
                    exist_min_date = $('#rrange_' + i + ' div[data-caption="to"]').text();
                    break;
                }
            }

            /* find last filled value in tmp rate ranges */
            if (!min_date) {
                for (var i = previous_field; i > 0; i--) {
                    if ($('[name="b[' + i + '][to]"]').length && $('[name="b[' + i + '][to]"]').val()) {
                        min_date = $('[name="b[' + i + '][to]"]').val();
                        break;
                    }
                }
            }
        }

        if (exist_min_date || min_date) {
            min_date = bookingConfigs['current_field'] > 1 && !bookingConfigs['post_rate_ranges']
            ? new Date(exist_min_date
                ? exist_min_date.replace(/(\d{2})\-(\d{2})\-(\d{4})/,'$3-$2-$1')
                : min_date.replace(/(\d{2})\-(\d{2})\-(\d{4})/,'$3-$2-$1'))
            : min_date;

            /* allow to create new rate from next day only */
            if (bookingConfigs['current_field'] > 1 ) {
                min_date.setDate(min_date.getDate() + 1);
            }
        }

        return min_date ? min_date : default_date;
    };

    /**
     * Deleting rate range row from list
     * @param {int}  id           - ID of row with rate
     * @param {bool} rate_details - Detect exist rate or temp
     */
    this.removeRate = function(id, rate_details) {
        if (!id) {
            return false;
        }

        if (rate_details) {
            $(this).val(lang['loading']);

            var request = {
                mode: 'bookingRemoveRateRange',
                item: {
                    rate_id: id,
                    listing_id: bookingConfigs['listing_id']
                },
                lang: typeof rlLang !== 'undefined' ? rlLang : bookingConfigs.rlLang
            };

            $.post(rlConfig.ajax_url, request, function(response){
                if (bookingConfigs['isAdmin']) {
                    self.closeApModal();
                } else {
                    $('#modal_block div.close').trigger('click');
                }

                if (response.status === 'OK') {
                    $('#rrange_' + id).fadeOut('fast', function(){ $(this).remove() });
                    bookingConfigs['current_field']--;

                    if (!bookingConfigs['isAdmin']) {
                        setTimeout(function(){ printMessage('notice', response.data) }, 500);
                    }
                } else {
                    setTimeout(function(){ printMessage('error', response.message) }, 500);
                }
            }, 'json');
        } else {
            $('#add_rate_' + id).fadeOut('fast', function(){ $(this).remove() });
            bookingConfigs['current_field']--;
        }
    };

    /**
     * Window with confirmation of removing rate
     */
    this.confirmRemoveRateHandler = function() {
        var caption = lang['warning'];
        var confirmation_phrase = lang['booking_remove_confirm'];

        $('.remove_rate').each(function(){
            if (bookingConfigs['isAdmin']) {
                $(this).flModal({
                    width: 450,
                    height: 'auto',
                    caption: caption,
                    content: confirmation_phrase + '<div class="prompt admin"><input name="remove_confirmation" onclick="booking.removeRate(' + $(this).attr('id') + ', true)" type="button" value="' + lang['booking_ok'] + '"><a class="close" href="javascript:void(0)" onclick="self.closeApModal()">' + lang['cancel'] + '</a></div>'
                });
            } else {
                $(this).flModal({
                    caption: caption,
                    content: confirmation_phrase,
                    prompt : "booking.removeRate(" + $(this).attr('id') + ", true)",
                    width  : 'auto',
                    height : 'auto'
                });
            }
        });
    };

    /**
     * Close modal window
     */
    this.closeApModal = function() {
        $('.modal-window > div:first > span:last').trigger('click');
    };

    /**
     * Show/Hide section field group
     */
    this.bookingSectionHandler = function() {
        if (bookingConfigs.booking_available) {
            if (self.$bookingRadioButton.length > 0) {
                /* show for all booking type except Escort (time_ranges) */
                if (bookingConfigs.booking_type && bookingConfigs.booking_type !== 'time_range') {
                    /* check in field */
                    var checkin = '<' + self.rateParentTag + ' id="booking_checkin" class="submit-cell clearfix hide">';
                    checkin += '<' + self.rateChildTag + ' class="name">' + lang['booking_checkin'] + '</' + self.rateChildTag + '>';
                    checkin += '<'+ self.rateChildTag + ' class="field" id="booking_checkin_obj"></' + self.rateChildTag + '>';
                    checkin += '</' + self.rateParentTag + '>';

                    self.$bookingSection.after(checkin);
                    self.$checkInSection.clone().appendTo('#booking_checkin_obj').removeClass('hide');

                    /* check out field */
                    var checkout = '<' + self.rateParentTag + ' id="booking_checkout" class="submit-cell clearfix hide">';
                    checkout += '<' + self.rateChildTag + ' class="name">' + lang['booking_checkout'] + '</' + self.rateChildTag + '>';
                    checkout += '<' + self.rateChildTag + ' class="field" id="booking_checkout_obj"></' + self.rateChildTag + '>';
                    checkout += '</' + self.rateParentTag + '>';

                    $('#booking_checkin').after(checkout);
                    self.$checkOutSection.clone().appendTo('#booking_checkout_obj').removeClass('hide');

                    /* rate ranges fields */
                    var rateRanges = '<' + self.rateParentTag + ' id="booking_rate_ranges" class="submit-cell clearfix hide">';
                    rateRanges += '<' + self.rateChildTag + ' class="name">' + lang['booking_rate_range'] + '</' + self.rateChildTag + '>';
                    rateRanges += '<' + self.rateChildTag + ' class="field" id="booking_rate_ranges_obj"></' + self.rateChildTag + '>';
                    rateRanges += '</' + self.rateParentTag + '>';

                    if (bookingConfigs.booking_type && bookingConfigs.booking_type !== 'time_range') {
                        $('#booking_checkout').after(rateRanges);
                    } else {
                        self.$bookingSection.after(rateRanges);
                    }

                    $('#booking_rate_ranges_list').appendTo('#booking_rate_ranges_obj').removeClass('hide');

                    if ($('[name="f[booking_module]"][value="1"]').is(':checked')) {
                        $('#booking_rate_ranges, #booking_checkin, #booking_checkout').removeClass('hide');
                    }
                } else if (bookingConfigs.booking_type === 'time_range' && !bookingConfigs.is_escort)  {
                    var $availability = $('#availability'), $rates = $('#rates');

                    $availability.appendTo(self.$bookingSection.parent());
                    $rates.appendTo(self.$bookingSection.parent());

                    if (self.$bookingRadioButton.filter(':checked').val() === '1') {
                        $availability.removeClass('hide');
                        $rates.removeClass('hide');
                    }
                }

                self.$bookingRadioButton.off('click').click(function() {
                    if ($(this).is('[value="1"]:checked')) {
                        $('#booking_checkin').removeClass('hide');
                        $('#booking_checkout').removeClass('hide');
                        $('#booking_rate_ranges').fadeIn('normal', self.loadingRangesHandler);

                        if (bookingConfigs.booking_type === 'time_range' && !bookingConfigs.is_escort)  {
                            $availability.removeClass('hide');
                            $rates.removeClass('hide');
                        }
                    } else {
                        $('#booking_checkin').addClass('hide');
                        $('#booking_checkout').addClass('hide');
                        $('#booking_rate_ranges').fadeOut('normal',
                            function(){
                                $('#booking_rate_ranges_list > div.list-table div.row').remove();
                            }
                        );

                        if (bookingConfigs.booking_type === 'time_range' && !bookingConfigs.is_escort)  {
                            $availability.addClass('hide');
                            $rates.addClass('hide');
                        }
                    }
                });

                self.$bookingRadioButton.closest('form').on('submit', function(event) {
                    if (!self.$bookingRadioButton.is('[value="1"]:checked')) {
                        return true;
                    }

                    var submit       = true,
                        emptyFields  = [],
                        errorMessage = lang.booking_addEditListingErrorEmptyRanges;

                    if (bookingConfigs.booking_type === 'time_range' && !bookingConfigs.is_escort)  {
                        var emptyAvailabilityFields = 0, emptyAvailabilityIDs = [];
                        $availability.find('select').each(function () {
                            if ($(this).val() === '0') {
                                emptyAvailabilityFields++;
                                emptyAvailabilityIDs.push('#' + $(this).attr('id'))
                            }
                        });

                        if (emptyAvailabilityFields === 14) {
                            submit      = false;
                            emptyFields = emptyAvailabilityIDs;
                            errorMessage = lang.booking_emptyHourlyRanges;
                        } else if (emptyAvailabilityFields > 0) {
                            let fromAvailability, toAvailability, i;
                            emptyAvailabilityIDs = [];

                            for (i = 1; i <= 7; i++) {
                                fromAvailability = $('#hourly-availability-from-' + i).val();
                                toAvailability   = $('#hourly-availability-to-' + i).val();

                                if (fromAvailability !== '0' && toAvailability === '0') {
                                    emptyAvailabilityIDs.push('#hourly-availability-to-' + i);
                                } else if (fromAvailability === '0' && toAvailability !== '0') {
                                    emptyAvailabilityIDs.push('#hourly-availability-from-' + i);
                                }
                            }

                            if (emptyAvailabilityIDs.length > 0) {
                                submit       = false;
                                emptyFields  = emptyAvailabilityIDs;
                                errorMessage = lang.booking_emptyHourlyRanges;
                            }
                        }

                        let emptyRatesIDs = [];
                        $('.rates-field-container .rates').each(function () {
                            $(this).find('input').each(function () {
                                if ($(this).val() === '') {
                                    emptyRatesIDs.push('#' + $(this).attr('id'));
                                }
                            });

                            if (!$(this).find('select.hourly-rates-duration').val()) {
                                emptyRatesIDs.push('#' + $(this).find('select.hourly-rates-duration').attr('id'));
                            }
                        });

                        if (emptyRatesIDs.length > 0) {
                            submit       = false;
                            emptyFields  = emptyFields.concat(emptyRatesIDs);
                            errorMessage = lang.booking_emptyHourlyRanges;
                        }
                    } else {
                        $('[id^=add_rate_] input').each(function() {
                            if ($(this).val() === '') {
                                emptyFields.push('#' + $(this).attr('id'));
                                submit = false;
                            }
                        });
                    }

                    if (!submit) {
                        event.preventDefault();
                        let $submitButton = self.$bookingRadioButton.closest('form').find('[type="submit"]');
                        if ($submitButton.prop('disabled')) {
                            $submitButton.val(lang.add_listing).prop('disabled', false).removeClass('disabled');
                        }

                        printMessage('error', errorMessage, emptyFields);
                        flynax.slideTo(bookingConfigs.isAdmin ? '#system_message' : '#fs_booking_rates');
                        self.rateRangeFieldsHandler();

                        // Restore phrase in button and removing "disabled" class
                        if (typeof manageListing === 'object' && typeof manageListing.enableButton === 'function') {
                            manageListing.enableButton();
                        }
                    }

                    return submit;
                });
            }

            self.recountRegularPrice(true);
            self.confirmRemoveRateHandler();
            self.qtipInit();

            $('.add_rate_range').click(function(){
                self.addRateRange();
            });

            if (bookingConfigs['post_rate_ranges']) {
                self.rateRangeFieldsHandler();
            }
        } else {
            $('div#fs_booking_rates').remove();
        }
    };

    /**
     * Add new row with data of hourly rate
     * @since 3.2.0
     */
    this.addNewHourlyRate = function () {
        var $timeSelector = $('<select>', {
                name : 'f[booking_rates][' + self.rateIndex + '][time]',
                id   : 'hourly-rates-duration-' + self.rateIndex,
                class: 'hourly-rates-duration',
            }),
            timeValue     = '',
            hoursValue    = '';

        $timeSelector.append($('<option>').val('').text(lang.booking_rate_selector));

        for (i = 1; i <= 24; i++) {
            if (i > 1) {
                hoursValue = i % 2 ? i / 2 : i / 2;
                timeValue  = i % 2 ? parseInt(hoursValue) + ':30' : hoursValue + ':00';

                if (hoursValue <= 9.5) {
                    timeValue = '0' + timeValue;
                }
            } else {
                hoursValue = 0.5;
                timeValue = '00:30';
            }

            $timeSelector.append(
                $('<option>').val(hoursValue).text(timeValue)
            )
        }

        var $currency = $('<select>').attr({
            name: 'f[booking_rates][' + self.rateIndex + '][currency]',
            id  : 'hourly-rate-currency-' + self.rateIndex,
        });

        bookingConfigs.currencies.forEach(function (currency) {
            $currency.append(
                $('<option>').val(currency.key).text(currency.value)
            )
        });

        $('.rates-field-container .rates:last').after(
            $('<div>', {class: 'rates'}).append(
                $('<input>').attr({
                    type       : 'text',
                    name       : 'f[booking_rates][' + self.rateIndex + '][title]',
                    id         : 'hourly-rates-name-' + self.rateIndex,
                    placeholder: lang.booking_rate_title
                }),
                $timeSelector,
                $('<input>').attr({
                    type       : 'text',
                    name       : 'f[booking_rates][' + self.rateIndex + '][price]',
                    id         : 'hourly-rates-price-' + self.rateIndex,
                    placeholder: lang.price,
                    class      : 'numeric w120',
                }),
                $currency,
                (!bookingConfigs.bookingRepeatedly
                    ? $('<select>').attr({
                        name: 'f[booking_rates][' + self.rateIndex + '][type]',
                        id  : 'hourly-rate-type-' + self.rateIndex,
                       }).append(
                           $('<option>').attr({value: 'single'}).text(lang.booking_single_rate),
                            $('<option>').attr({value: 'multi', selected: true}).text(lang.booking_multi_rate),
                        )
                    : null
                ),
                $('<img>').addClass('remove remove-hourly-rate').attr({
                    'title': lang.delete,
                    'alt'  : lang.delete,
                    'src'  : bookingConfigs.src_delete_img,
                    'id'   : 'hourly-rate-' + self.rateIndex
                }).click(function () {
                    $(this).closest('.rates').fadeOut('fast', function(){
                        $(this).remove();
                        self.rateIndex--;
                    });
                })
            )
        );

        self.rateIndex++;
    };

    /**
     * Load list with rate ranges to page (for ex. after desc editing)
     */
    this.loadingRangesHandler = function() {
        $('#booking_rate_ranges_list > div.list-table div.row').remove();
        $('#booking_rate_ranges_list > div.list-table').append('<div id="loading_ranges">' + lang['loading'] + '</div>');

        if (bookingConfigs.rlController === 'edit_listing') {
            var request = {mode: 'bookingGetRateRanges', item: bookingConfigs['listing_id'], lang: rlLang};

            $.post(rlConfig.ajax_url, request, function(response){
                if (response.status === 'OK') {
                    $('#loading_ranges').remove();

                    var rate_ranges = response.data.rate_ranges;
                    var regular_price = response.data.regular_price;

                    if (rate_ranges.length > 0)  {
                        for (var i = 0; i < rate_ranges.length; i++) {
                            var item = rate_ranges[i];
                            var rate_id = item.ID ? parseInt(item.ID) : 0;

                            bookingConfigs['current_field'] = rate_id > parseInt(bookingConfigs['current_field'])
                            ? rate_id
                            : bookingConfigs['current_field'];

                            $('#booking_rate_ranges_list > div.list-table').append(
                                self.buildRateFieldHtml(rate_id, item.From, item.To, item.Price, item.Desc)
                            );
                        }

                        self.confirmRemoveRateHandler();
                    }

                    if (regular_price) {
                        var regular_price_desc_html = self.buildRateFieldHtml(
                            'regular',
                            lang['booking_rate_price_per_day'],
                            '',
                            regular_price.value,
                            regular_price.desc
                        );

                        $('#booking_rate_ranges_list > div.list-table').append(regular_price_desc_html);
                    }

                    self.qtipInit();
                    self.recountRegularPrice();
                    $('#loading_ranges').remove();
                    $('#add_ranges_table').fadeIn('slow');
                } else {
                    printMessage('error', response.message);
                }
            }, 'json');
        } else {
            var regular_price_desc_html = self.buildRateFieldHtml(
                'regular',
                lang['booking_rate_price_per_day'],
                '',
                '',
                '',
                true
            );
            $('#booking_rate_ranges_list > div.list-table').append(regular_price_desc_html);

            self.qtipInit();
            self.recountRegularPrice();

            $('#loading_ranges').remove();
            $('#add_ranges_table').fadeIn('normal');
        }
    };

    /**
     * Generate html code for row with data of rate range
     * @param  {int}    id          - ID of rate range
     * @param  {string} from        - From date
     * @param  {string} to          - To date
     * @param  {int}    price       - Price of rate range
     * @param  {string} desc        - Description of rate range
     * @param  {bool}   add_listing - Detect add listing page
     * @return {string}             - Html code of rate range
     */
    this.buildRateFieldHtml = function(id, from, to, price, desc, add_listing) {
        if (!id) {
            return false;
        }

        var lCurrency = $('select[name="f[price][currency]"] option:selected').text();

        var result = '<div class="row" id="rrange_' + id + '">';
        result += '<div data-caption="' + lang.from + '">' + from +'</div>';
        result += '<div data-caption="' + lang.to + '">' + to + '</div>';
        result += '<div data-caption="' + lang.price + '">';

        /* price */
        result += price && parseFloat(price) !== 0 && price !== lang.not_available
        ? (bookingConfigs.system_currency_position === 'before'
            ? lCurrency + ' ' + price
            : price + ' ' + lCurrency)
        : (parseFloat(price) === 0 ? lang['booking_close_days'] : lang['not_available']);
        /* price end */

        // add qtip with description of missing standard rate
        if (id === 'regular' && price === lang.not_available) {
            result += ' <img class="qtip booking_qtip" alt="" title="' + lang['booking_empty_regular'] + '" ';
            result += 'src="' + bookingConfigs['src_delete_img'] + '" />';
        }

        result += '</div>';

        /* description */
        desc = desc ? desc : '';
        result += '<div data-caption="' + lang.description + '">';

        if (add_listing) {
            result += '<a href="javascript:void(0)" onclick="booking.addDesc()">' + lang.description + '</a>';
        } else {
            var qtip_e = '<br><hr><a class=\'booking_edit_desc\' href=\'javascript:void(0)\' ';
            qtip_e += 'onclick=\'booking.editDesc(' + (id !== 'regular' ? id : '') + ')\'>' + lang.edit + '</a>';

            result += '<img class="qtip booking_qtip" alt="" title="' + (desc ? desc : lang['not_available']);
            result += ' ' + qtip_e + '" id="desc_ico_' + id + '" src="';
            result += bookingConfigs['src_delete_img'] + '" />';
        }

        result += '<textarea class="hide" id="save_desc_' + id + '" name="b[' + id +'][desc] cols="30" rows="2">';
        result += desc + '</textarea>';
        result += '</div>';
        /* description end */

        result += '<div data-caption="' + lang['actions'] + '">';

        if (id === 'regular') {
            result += lang['not_available'];
        } else {
            result += '<img class="remove remove_rate" id=' + id;
            result += ' title="' + lang['delete'] + '" alt="' + lang['delete'] + '"';
            result += ' src="' + bookingConfigs['src_delete_img'] + '" />';
        }

        result += '</div>';
        result += '</div>';

        return result;
    };

    /**
     * Custom qTip initialization with delay
     * @param {bool} second_call - Add qtip's for new fields only
     */
    this.qtipInit = function(second_call) {
        var qtip_style_admin =  {
            width: 'auto',
            background: '#858585',
            color: 'white',
            border: {
                width: 7,
                radius: 5,
                color: '#858585'
            },
            tip: 'bottomLeft'
        };

        // first call in document ready
        if (!second_call) {
            setTimeout(
                function(){
                    $('.booking_qtip').addClass('qtip');
                    $('.qtip.booking_qtip').each(function(){
                        if ($(this).attr('title')) {
                            $(this).qtip({
                                content: $(this).attr('title'),
                                show: 'mouseover',
                                hide: {
                                    fixed: true,
                                    delay: 500
                                },
                                position: {
                                    corner: {
                                        target: 'topRight',
                                        tooltip: 'bottomLeft'
                                    }
                                },
                                style: bookingConfigs['isAdmin'] ? qtip_style_admin : qtip_style
                            }).attr('title', '');
                        }
                    });
                },
                100
            );
        } else {
            if (bookingConfigs['isAdmin']) {
                // second call for new images with qtip (in admin)
                $('.qtip.booking_qtip').each(function(){
                    if ($(this).attr('title')) {
                        $(this).qtip({
                            width: 'auto',
                            content: $(this).attr('title'),
                            show: 'mouseover',
                            hide: 'mouseout',
                            position: {
                                corner: {
                                    target: 'topRight',
                                    tooltip: 'bottomLeft'
                                }
                            },
                            style: {
                                width: 'auto',
                                background: '#858585',
                                color: 'white',
                                border: {
                                    width: 7,
                                    radius: 5,
                                    color: '#858585'
                                },
                                tip: 'bottomLeft'
                            }
                        }).attr('title', '');
                    }
                });
            } else {
                // second call for new images with qtip (in frontend)
                $('.qtip.booking_qtip').each(function(){
                    var target = 'topRight';
                    var tooltip = 'bottomLeft';

                    if ($(this).hasClass('middle-bottom')) {
                        target = 'topMiddle';
                        tooltip = 'bottomMiddle';

                        var tmp_style = jQuery.extend({}, qtip_style);
                        tmp_style.tip = 'bottomMiddle';
                    }

                    if ($(this).attr('title')) {
                        $(this).qtip({
                            content: $(this).attr('title'),
                            show: 'mouseover',
                            hide: 'mouseout',
                            position: {
                                corner: {
                                    target: target,
                                    tooltip: tooltip
                                }
                            },
                            style: $(this).hasClass('middle-bottom') ? tmp_style : qtip_style
                        }).attr('title', '');
                    }
                });
            }
        }
    };

    /**
     * Adding description for new rate range
     * @param {int} id - ID of rate range
     */
    this.addDesc = function(id) {
        if (!id) {
            id = 'regular';
        }

        var caption = lang['manage'];
        var html_content = $('#manage_item_dom').html();

        if (bookingConfigs['isAdmin']) {
            $(document).flModal({
                width: 450,
                height: 'auto',
                caption: caption,
                onReady: function(){
                    $('#manage_item_dom').html('');
                    self.copyDescToTable(id)
                },
                content: html_content,
                onClose: function(){
                    $('#manage_item_dom').html(html_content)
                }
            });
        } else {
            $(document).flModal({
                click: false,
                source: '#manage_item_dom',
                caption: lang['manage'],
                width: 450,
                height: 'auto',
                ready: function(){
                    self.copyDescToTable(id)
                }
            });
        }
    };

    /**
     * Copy description from temp container to ranges table
     * @param {int} id - ID of rate range
     */
    this.copyDescToTable = function(id) {
        if (!id) {
            return false;
        }

        var value = $('#save_desc_' + id).val();

        if (value != lang['not_available']) {
            $('input[name=item-desc]').val(value);
        }

        $('input[name=item-desc-save]').click(function(){
            $('#save_desc_' + id).val($('input[name=item-desc]').val());

            if (bookingConfigs['isAdmin']) {
                self.closeApModal();
            } else {
                $('#modal_block div.close').trigger('click');
            }
        });
    };

    /**
     * Edit description of Rate Range
     * @param {string} id - ID of rate range or Regular
     */
    this.editDesc = function(id) {
        var caption = lang['manage'];
        var html_content = $('#manage_item_dom').html();

        if (bookingConfigs['isAdmin']) {
            $(document).flModal({
                width: 450,
                height: 'auto',
                caption: caption,
                onReady: function(){
                    $('#manage_item_dom').html('');
                    self.sendDescContentFromModal(id)
                },
                content: html_content,
                onClose: function(){
                    $('#manage_item_dom').html(html_content)
                }
            });
        } else {
            $(document).flModal({
                click: false,
                source: '#manage_item_dom',
                caption: caption,
                width: 450,
                height: 'auto',
                ready: function(){
                    self.sendDescContentFromModal(id)
                }
            });
        }
    };

    /**
     * Send description of rate via ajax request
     * @param {string} id - ID of rate range or Regular
     */
    this.sendDescContentFromModal = function(id) {
        if (!id) {
            id = 'regular';
        }

        var old_desc = $('#save_desc_' + id).val();

        $('[name="item-desc"]').val(old_desc !== lang.not_available ? old_desc : '');
        $('[name="item-desc-save"]').click(function(){
            var new_desc = $('[name=item-desc]').val();

            if (new_desc !== lang.not_available && old_desc !== new_desc) {
                if (bookingConfigs.rlController === 'edit_listing') {
                    $(this).val(lang['loading']).addClass('disabled').attr('disabled', true);

                    var request = {
                        mode: 'bookingUpdateRateDesc',
                        item: {rate_id: id, listing_id: bookingConfigs['listing_id']},
                        desc: JSON.stringify(new_desc),
                        lang: rlLang
                    };

                    $.post(rlConfig.ajax_url, request, function(response){
                        if (bookingConfigs['isAdmin']) {
                            self.closeApModal();
                        } else {
                            $('#modal_block div.close').trigger('click');
                        }

                        if (response.status === 'OK') {
                            $('#save_desc_' + id).val(new_desc);

                            // update rows with rate ranges
                            self.loadingRangesHandler();

                            if (!bookingConfigs['isAdmin']) {
                                setTimeout(function(){ printMessage('notice', response.data) }, 500);
                            }
                        } else {
                            setTimeout(function(){ printMessage('error', response.message) }, 500);
                        }
                    }, 'json');
                } else {
                    if (bookingConfigs['isAdmin']) {
                        self.closeApModal();
                    } else {
                        $('#modal_block div.close').trigger('click');
                    }
                }
            } else {
                if (bookingConfigs['isAdmin']) {
                    self.closeApModal();
                } else {
                    $('#modal_block div.close').trigger('click');
                }
            }
        });
    };

    /**
     * Get dates for calendar
     *
     * @param {int}    listing_id - ID of listing
     * @param {string} param_mode - Button for next/previous month
     * @param {bool}   update     - Update calendar without current request
     */
    this.getDates = function(listing_id, param_mode, update) {
        if (!listing_id) {
            return false;
        }

        self.loadExternalScripts(function() {
            flUtil.ajax({
                mode: 'bookingGetDates',
                item: {
                    listing_id: listing_id,
                    mode      : param_mode,
                    request_id: bookingConfigs.request_id ? bookingConfigs.request_id : null
                }
            },
            function(response) {
                bookingConfigs.usBook     = [];
                bookingConfigs.usRange    = [];
                bookingConfigs.closeRange = [];

                if (response.status === 'OK') {
                    let weekDays = {
                        1: lang.booking_monday,
                        2: lang.booking_tuesday,
                        3: lang.booking_wednesday,
                        4: lang.booking_thursday,
                        5: lang.booking_friday,
                        6: lang.booking_saturday,
                        7: lang.booking_sunday,
                    };

                    // assign already booked days
                    if (response.data.userBook) {
                        for (var i = response.data.userBook.length - 1; i >= 0; i--) {
                            bookingConfigs.usBook[i] = [];
                            bookingConfigs.usBook[i][0] = response.data.userBook[i].Status;
                            bookingConfigs.usBook[i][1] = response.data.userBook[i].From;
                            bookingConfigs.usBook[i][2] = response.data.userBook[i].To;
                            bookingConfigs.usBook[i][3] = response.data.userBook[i].ID;
                            bookingConfigs.usBook[i][4] = response.data.userBook[i].Own_request;
                            bookingConfigs.usBook[i][5] = response.data.userBook[i].Checkout;

                            if (bookingConfigs.Booking_type === 'time_range') {
                                bookingConfigs.usBook[i][6] = response.data.userBook[i].Duration;
                                bookingConfigs.usBook[i][7] = response.data.userBook[i].Type;
                                bookingConfigs.usBook[i][8] = response.data.userBook[i].RateIndex;
                            }
                        }
                    }

                    // assign rate ranges
                    if (response.data.usRange) {
                        for (var i = response.data.usRange.length - 1; i >= 0; i--) {
                            bookingConfigs.usRange[i] = [];
                            bookingConfigs.usRange[i][0] = response.data.usRange[i]['From'];
                            bookingConfigs.usRange[i][1] = response.data.usRange[i]['To'];
                            bookingConfigs.usRange[i][2] = response.data.usRange[i]['Price'];
                        }
                    }

                    // assign closed days
                    if (response.data.closeRange) {
                        for (var i = response.data.closeRange.length - 1; i >= 0; i--) {
                            bookingConfigs.closeRange[i] = [];
                            bookingConfigs.closeRange[i][0] = response.data.closeRange[i]['From'];
                            bookingConfigs.closeRange[i][1] = response.data.closeRange[i]['To'];
                        }
                    }

                    // assign binding days
                    if (response.data.bindingDays
                        && (response.data.bindingDays.checkin || response.data.bindingDays.checkout)
                    ) {
                        bookingConfigs['bind_checkin']  = response.data.bindingDays.checkin
                        ? response.data.bindingDays.checkin
                        : '';
                        bookingConfigs['bind_checkout'] = response.data.bindingDays.checkout
                        ? response.data.bindingDays.checkout
                        : '';

                        let additionalNotices = '';
                        if (bookingConfigs.Booking_type !== 'time_range'
                            && (bookingConfigs.bind_checkin || bookingConfigs.bind_checkout)
                        ) {
                            if (bookingConfigs.bind_checkin && bookingConfigs.bind_checkin.split(',').length < 7) {
                                let checkinLocale = self.getBindDaysByLocale(bookingConfigs.bind_checkin, weekDays);
                                bookingConfigs.bindCheckinLocale = checkinLocale;

                                additionalNotices += '<li class="active">';
                                additionalNotices += lang.check_in_only_text.replace('{days}', checkinLocale);
                                additionalNotices += '</li>';
                            }

                            if (bookingConfigs.bind_checkout && bookingConfigs.bind_checkout.split(',').length < 7) {
                                let checkoutLocale = self.getBindDaysByLocale(bookingConfigs.bind_checkout, weekDays);
                                bookingConfigs.bindCheckoutLocale = checkoutLocale;

                                additionalNotices += '<li class="active">';
                                additionalNotices += lang.check_out_only_text.replace('{days}', checkoutLocale);
                                additionalNotices += '</li>';
                            }

                            let $notices = $('.booking_notices ul');
                            $notices.find('li:not(.static)').remove();
                            $notices.append(additionalNotices);
                        }
                    }

                    // draw calendar
                    if (response.data.bookingDays) {
                        var month = [];
                        var first = false;
                        var mass_bind_checkin = bookingConfigs.bind_checkin
                        ? bookingConfigs.bind_checkin.split(',')
                        : false;
                        var mass_bind_checkout = bookingConfigs.bind_checkout
                        ? bookingConfigs.bind_checkout.split(',')
                        : false;

                        var calendar_html = '<div id="calendar_map" class="clearfix row';
                        calendar_html += bookingConfigs['is_escort'] ? ' escort' : '';
                        calendar_html += '">';

                        var narrow_templates = {
                            'escort_flatty': 'col-sm-12',
                            'escort_velvet_flatty': 'col-sm-12',
                            'general_modern': 'col-sm-12',
                            'auto_modern': 'col-sm-12',
                            'general_simple': 'col-sm-12',
                            'escort_sun_cocktail': 'col-sm-12 col-md-12 col-lg-6',
                        };

                        var col_class = (
                            typeof narrow_templates[bookingConfigs['site_template']] == 'undefined'
                            || rlPageInfo['controller'] !== 'listing_details'
                        )
                        ? 'col-sm-6'
                        : narrow_templates[bookingConfigs['site_template']];

                        for (var item in response.data.bookingDays) {
                            month = response.data.bookingDays[item];
                            first = !first;

                            calendar_html += '<div class="' + col_class + '"><div>';
                            calendar_html += '<div class="month-name"><b>' + month.Name + ' ' + month.Year + '</b></div>';
                            calendar_html += '<table class="month-table" ';
                            calendar_html += '>';
                            calendar_html += '<tr class="dayName">';

                            let dayTitle;
                            for (let i = 1; i <= 7; i++) {
                                if (bookingConfigs.first_day === 'sunday') {
                                    dayTitle = i === 1 ? weekDays[7] : weekDays[i - 1];
                                } else {
                                    dayTitle = weekDays[i];
                                }

                                calendar_html += '<td><div>' + dayTitle + '</div></td>';
                            }

                            calendar_html += '</tr>';
                            calendar_html += '<tr>';

                            var index = 0;

                            // show other days
                            for (var item_day in month.Days) {
                                var day = month.Days[item_day];
                                var book_color = '';
                                index++;

                                calendar_html += '<td class="calendar_td">';

                                if (!day.Miss) {
                                    // disable binding days
                                    if (day.Color !== 'U' && (mass_bind_checkin || mass_bind_checkout)) {
                                        if (bookingConfigs.Booking_type === 'time_range') {
                                            if (bookingConfigs.bind_checkin
                                                && !in_array(
                                                    self.getDateByTimeZone(parseInt(day.mktime), 'ddd'),
                                                    mass_bind_checkin
                                                )
                                            ) {
                                                day.Color = 'U';
                                            }
                                        }
                                    }

                                    if (day.Color === 'U') {
                                        book_color = 'unavailable';
                                    } else if (day.Color === 'R') {
                                        book_color = 'restriction';
                                    } else if (day.Color === 'T') {
                                        book_color = 'today';
                                    } else if (day.Color === 'A') {
                                        var current = false;

                                        if (!update
                                            && !param_mode
                                            && bookingConfigs['usBook']
                                            && bookingConfigs['request_id']
                                        ) {
                                            for (let id in bookingConfigs['usBook']) {
                                                let st_b           = bookingConfigs['usBook'][id][1];
                                                let en_b           = bookingConfigs['usBook'][id][2];
                                                let daysInPeriod   = self.getCountDaysBetween2Timestamps(en_b, st_b);
                                                let currentDate    = st_b;
                                                let req_id         = Number(bookingConfigs['usBook'][id]['3']);
                                                let current_req_id = Number(bookingConfigs['request_id']);

                                                // Highlight current request in calendar
                                                if (current_req_id && current_req_id === req_id) {
                                                    for (i = 0; i <= daysInPeriod; i++) {
                                                        if (i !== 0) {
                                                            currentDate = self.addDaysToTimestamp(currentDate);
                                                        }

                                                        current = day.mktime >= st_b && day.mktime <= en_b;
                                                    }
                                                }
                                            }
                                        }

                                        book_color = current ? 'current-request' : 'available';
                                    }

                                    if (bookingConfigs.Booking_type === 'time_range' && day.fullyBooked) {
                                        book_color += ' fully-booked';
                                    }

                                    calendar_html += '<div class="date ' + book_color + '"';

                                    if (day.mktime) {
                                        calendar_html += 'id="day_' + day.mktime + '" ';
                                    }

                                    if (day.Color !== 'U' && day.Color !== 'R') {
                                        calendar_html += ' title="' + lang.booking_start_booking_title + '" ';
                                        calendar_html += 'onclick="booking.xSelect(\'' + day.mktime + '\')"';
                                    }

                                    calendar_html += '>';
                                    calendar_html += '<span>';
                                    calendar_html += parseInt(day.Day);
                                    calendar_html += '</span>';

                                    // add price of day
                                    if (book_color !== 'unavailable' && book_color !== 'restriction') {
                                        var price = 0;

                                        if (bookingConfigs['usRange']) {
                                            for (var idR in bookingConfigs['usRange']) {
                                                price = 0;

                                                if (parseInt(bookingConfigs['usRange'][idR][0]) <= day.mktime
                                                    && day.mktime <= parseInt(bookingConfigs['usRange'][idR][1])
                                                ) {
                                                    price = parseFloat(bookingConfigs['usRange'][idR][2].split('|')[0]);
                                                    break;
                                                }
                                            }
                                        }

                                        price = price ? price : bookingConfigs['defPrice'];

                                        if (price) {
                                            price = self.adaptNumbersInPrice(price);
                                            price = bookingConfigs['system_currency_position'] === 'before'
                                            ? bookingConfigs['defCurrency'] + ' ' + price
                                            : price + ' ' + bookingConfigs['defCurrency'];

                                            calendar_html += '<span class="day-price">' + price + '</span>';
                                        }
                                    }

                                    calendar_html += '</div>';
                                    calendar_html += '</td>';
                                }

                                if (index  % 7 === 0 && index !== month.Days.length) {
                                    calendar_html += '</tr><tr>';
                                }
                            }

                            calendar_html += '</tr>';
                            calendar_html += '</table>';
                            calendar_html += '</div></div>';
                        }

                        /* Prev/next month navigation */
                        calendar_html += '<div class="booking-horizontal">';
                        calendar_html += '<div ';
                        calendar_html += 'onclick="booking.changeDates(\'-M\')" id="prevRange" ';
                        calendar_html += 'class="prev hide" title="' + lang['booking_prev'] + ' ';
                        calendar_html += lang['booking_month'] + '"';
                        calendar_html += '><svg viewBox="0 0 8 14" class="details-icon-fill"><use xlink:href="#icon-horizontal-arrow"></use></svg></div>';
                        calendar_html += '<div ';
                        calendar_html += 'onclick="booking.changeDates(\'+M\')" class="next" ';
                        calendar_html += 'title="' + lang['booking_next'] + ' ' + lang['booking_month'] + '"';
                        calendar_html += '><svg viewBox="0 0 8 14" class="details-icon-fill"><use xlink:href="#icon-horizontal-arrow"></use></svg></div>';
                        calendar_html += '</div>';
                        /* Prev/next month navigation end */

                        calendar_html += '</div>';

                        $('#booking_calendar').html(calendar_html).fadeIn('slow');

                        self.booking_mask("set");
                        self.paintUserBook();
                        self.booking_mask('reset');
                    }

                    // show/hide arrow for previous month
                    response.data.showPrev ? $('#prevRange').fadeIn('slow') : $('#prevRange').fadeOut('fast');

                    // set prebooked days in calendar to continue order (in booking order page only)
                    // simulate selection
                    if (bookingConfigs['db_start']
                        && (bookingConfigs['db_end'] || bookingConfigs.Booking_type === 'time_range')
                        && bookingConfigs['start_date_value']
                        && (bookingConfigs['end_date_value'] || bookingConfigs.Booking_type === 'time_range')
                        && bookingConfigs['total_days']
                    ) {
                        // update calendar and count total amount
                        self.bUpdate(
                            bookingConfigs['db_start'],
                            false,
                            false,
                            param_mode || bookingConfigs.in_popup ? true : false
                        );

                        if (!bookingConfigs['edit_action']) {
                            bookingConfigs['cur_id'] = bookingConfigs['s_id'] = bookingConfigs['db_start'];
                        }

                        bookingConfigs['total_cost'] = 0;
                        bookingConfigs['first'] = 1;

                        if (bookingConfigs.in_popup) {
                            // remove selected day from calendar
                            bookingConfigs['s_id'] = 0;
                            self.book_color(true);
                            bookingConfigs['first'] = 0;
                        } else {
                            var result = self.bUpdate(
                                bookingConfigs['db_end'],
                                bookingConfigs['db_start'],
                                '+',
                                !!param_mode
                            );

                            if (!param_mode) {
                                self.stepsHandler(
                                    'details',
                                    'show',
                                    {
                                        start_book : bookingConfigs.start_date_value,
                                        end_book   : bookingConfigs.end_date_value,
                                        total_days : bookingConfigs.total_days,
                                        total_cost : bookingConfigs.total_cost,
                                        mode       : !!param_mode,
                                    }
                                );

                                // set default condition for second selection
                                bookingConfigs['first'] = 0;
                            }
                        }
                    }

                    if (param_mode) {
                        self.book_color(false, true, param_mode, true);
                    }

                    if (typeof $.convertPrice == 'function') {
                        $('#area_booking .day-price').convertPrice({showCents: false, shortView: true});
                    }

                    // disable calendar in request details page in first loading
                    if (rlPageInfo.controller === 'booking_requests') {
                        if (update) {
                            $('#booking_calendar').removeClass('disabled');
                            self.booking_mask('reset');
                            $('#area_booking .table-cell.current_dates').removeClass('current_dates');

                            if (bookingConfigs.Booking_type === 'date_time_range'
                                && bookingConfigs.listing_check_in
                                && bookingConfigs.listing_check_out
                            ) {
                                /* build html for check in field */
                                checkin_html = '<div class="name"><div><span>' + lang['booking_checkin'];
                                checkin_html += '</span></div></div>';
                                checkin_html += '<div class="field start_date">';

                                // block unavailable time
                                $('[name="f[booking_check_in]"] option').each(function(){
                                    $(this).attr('disabled', 'disabled');

                                    if ($(this).attr('value') === bookingConfigs.listing_check_in) {
                                        $(this).removeAttr('disabled').attr('selected', 'selected');

                                        return false;
                                    }
                                });

                                checkin_html += '<b>' + bookingConfigs['start_date_value'] + '</b> - ';
                                checkin_html += $('#booking_checkin_field').html();
                                checkin_html += '</div>';

                                $('.table-cell.check-in').html(checkin_html);
                                /* end html for check-in field */

                                /* build html for check-out field */
                                checkout_html = '<div class="name"><div><span>' + lang['booking_checkout'];
                                checkout_html += '</span></div></div>';
                                checkout_html += '<div class="field end_date">';

                                $('[name="f[booking_check_out]"] option[value="0"]').attr('disabled', 'disabled');

                                // block unavailable time
                                $($('[name="f[booking_check_out]"] option').get().reverse()).each(function(){
                                    $(this).attr('disabled', 'disabled');

                                    if ($(this).attr('value') === bookingConfigs.listing_check_out) {
                                        $(this).removeAttr('disabled').attr('selected', 'selected');

                                        return false;
                                    }
                                });

                                checkout_html += '<b>' + bookingConfigs['end_date_value'] + '</b> - ';
                                checkout_html += $('#booking_checkout_field').html();
                                checkout_html += '</div>';

                                $('.table-cell.check-out').html(checkout_html);
                                /* end html for check-out field */

                                self.dateTimeRangeHandler();
                            }
                        } else if (!update && !param_mode && bookingConfigs.Booking_type !== 'time_range') {
                            $('#booking_calendar').addClass('disabled');
                            self.booking_mask('set');
                        }
                    }
                } else {
                    printMessage('error', response.message);
                }
            })
        });
    };

    /**
     * Saving selected binding days
     */
    this.saveBindingDaysHandler = function() {
        if (!bookingConfigs['listing_id']) {
            return false;
        }

        $('.save_binding_days').click(function(){
            $(this).val(lang['loading']).addClass('disabled').attr('disabled', 'disabled');

            var formData = $('#binding_days_form').serializeArray();
            var request  = {
                mode: 'bookingSaveBindingDays',
                item: {
                    listing_id: bookingConfigs['listing_id'],
                    formData: formData
                },
                lang: rlLang
            };

            $.post(rlConfig.ajax_url, request, function(response){
                $('.save_binding_days').val(lang['save']).removeClass('disabled').removeAttr('disabled');

                if (response.status == 'OK') {
                    printMessage('notice', response.data)
                } else {
                    printMessage('error', response.message);
                }
            }, 'json');
        });
    };

    /**
     * Saving selected configs
     */
    this.saveConfigsHandler = function() {
        if (!bookingConfigs['listing_id']) {
            return false;
        }

        $('.save_configs').click(function(){
            $(this).val(lang['loading']).addClass('disabled').attr('disabled', 'disabled');
            var min_day_value = $('[name="min_book_day"]').val();
            var max_day_value = $('[name="max_book_day"]').val();

            if ((min_day_value == '' || parseInt(min_day_value) <= 0) && bookingConfigs['Booking_type'] != 'time_range') {
                $('.save_configs').val(lang['save']).removeClass('disabled').removeAttr('disabled');
                printMessage('error', lang['booking_min_config_desc'], 'min_book_day');
            } else if (max_day_value == ''
                || parseInt(max_day_value) < 0
                || (parseInt(max_day_value) > 0 && parseInt(max_day_value) <= parseInt(min_day_value))
            ) {
                $('.save_configs').val(lang['save']).removeClass('disabled').removeAttr('disabled');
                printMessage('error', lang['booking_max_config_error'], 'max_book_day');
            } else {
                var formData = $('#configs_form').serializeArray();
                var request  = {
                    mode: 'bookingSaveConfigs',
                    item: {
                        listing_id: bookingConfigs['listing_id'],
                        formData: formData
                    },
                    lang: rlLang
                };

                $.post(rlConfig.ajax_url, request, function(response){
                    $('.save_configs').val(lang['save']).removeClass('disabled').removeAttr('disabled');

                    if (response.status == 'OK') {
                        printMessage('notice', response.data)
                    } else {
                        printMessage('error', response.message);
                    }
                }, 'json');
            }
        });
    };

    /**
     * Recount of regular price by changed value of "Rent field"
     */
    this.rentFieldHandler = function() {
        if (!bookingConfigs.booking_rent_field || !bookingConfigs.booking_rent_value) {
            return false;
        }

        var $rent = $('[name="f[' + bookingConfigs.booking_rent_field +']"]');

        if ($rent.length) {
            $rent.change(function() {
                if ($('[name="f[' + bookingConfigs['booking_rent_field'] +']"]:checked').val() !== bookingConfigs.booking_rent_value) {
                    if (bookingConfigs['booking_time_frame_field']) {
                        $('[name="f[' + bookingConfigs['booking_time_frame_field'] + ']"]').removeAttr('checked');
                    }

                    self.recountRegularPrice();
                }
            });
        }
    };

    /**
     * Recount of regular price by changed price
     */
    this.priceHandler = function() {
        if (!bookingConfigs.booking_price_field) {
            return false;
        }

        var $price = $('[name="f[' + bookingConfigs['booking_price_field'] + '][value]"]');

        if ($price.length > 0) {
            $price.change(function(){
                self.recountRegularPrice();
            });
        }
    };

    /**
     * Recount of regular price by changed time frame
     */
    this.rentTimeFrameHandler = function() {
        if (!bookingConfigs.booking_time_frame_field) {
            return false;
        }

        var $timeFrame = $('[name="f[' + bookingConfigs['booking_time_frame_field'] + ']"]');

        if ($timeFrame.length) {
            $timeFrame.change(function(){
                self.recountRegularPrice();
            });
        }
    };

    /**
     * Recount of regular price by changed currency
     */
    this.currencyHandler = function() {
        if (!bookingConfigs.booking_price_field) {
            return false;
        }

        var $currency = $('[name="f[' + bookingConfigs.booking_price_field + '][currency]"]');

        if ($currency.length) {
            $currency.change(function(){
                self.recountRegularPrice();
            });
        }
    };

    /**
     * Recount and put the regular price to rate ranges table
     * @param {bool} enable_handlers - Enable handlers for recount
     */
    this.recountRegularPrice = function(enable_handlers) {
        if (enable_handlers) {
            self.priceHandler();
            self.rentTimeFrameHandler();
            self.currencyHandler();
            self.rentFieldHandler();

            return;
        }

        var $time_frame_field_val = $('[name="f[' + bookingConfigs.booking_time_frame_field + ']"]').is('select')
            ? $('[name="f[' + bookingConfigs.booking_time_frame_field + ']"] option:selected').val()
            : $('[name="f[' + bookingConfigs.booking_time_frame_field + ']"]:checked').val();

        if (!bookingConfigs['booking_price_field']
            || !bookingConfigs['booking_time_frame']
            || $('[name="f[booking_module]"][value="1"]').is(':checked') === false
            || ((!bookingConfigs['booking_time_frame_field']
                || !$time_frame_field_val)
                && (bookingConfigs['booking_rent_field']
                    && bookingConfigs['booking_rent_value']
                    && $('[name="f[' + bookingConfigs['booking_rent_field'] + ']"]:checked').val() == bookingConfigs['booking_rent_value'])
                )
        ) {
            return;
        }

        let price, currency, time_frame, price_cel, price_field = bookingConfigs.booking_price_field;

        price = parseFloat($('[name="f[' + price_field + '][value]"]').val())
        ? parseFloat($('[name="f[' + price_field + '][value]"]').val())
        : lang['not_available'];
        currency = $('[name="f[' + price_field + '][currency]"] option:selected').text();
        time_frame = $time_frame_field_val;

        /* count regular price */
        if (price > 0 && time_frame) {
            for (var time_frame_key in bookingConfigs.booking_time_frame) {
                if (!bookingConfigs.booking_time_frame.hasOwnProperty(time_frame_key)) {
                    continue;
                }

                var time_frame_value = bookingConfigs.booking_time_frame[time_frame_key];

                if (time_frame === time_frame_value) {
                    if (time_frame_key === 'day') {
                        price_cel = price;
                    } else if (time_frame_key === 'week') {
                        price_cel = Math.round((price / 7) * 100) / 100;
                    } else if (time_frame_key === 'month') {
                        // get count days of current month
                        Date.prototype.daysInMonth = function(mth, yr){
                            yr = yr || this.getFullYear();
                            mth = mth || this.getMonth();

                            return new Date(yr, mth + 1, 0).getDate();
                        };

                        price_cel = Math.round((price / new Date().daysInMonth()) * 100) / 100;
                    } else if (time_frame_key === 'year') {
                        price_cel = Math.round((price / 365) * 100) / 100;
                    } else if (time_frame_key === 'hour' && bookingConfigs.booking_type === 'date_time_range') {
                        price_cel = Math.round((price * 24) * 100) / 100;
                    } else {
                        price_cel = lang.not_available;
                    }

                    break;
                } else {
                    price_cel = lang.not_available;
                }
            }

            /* build regular price */
            if (price_cel !== lang.not_available) {
                price = bookingConfigs.system_currency_position === 'before'
                ? currency + ' ' + price_cel
                : price_cel + ' ' + currency;
            } else {
                price = lang.not_available;
            }
        } else {
            price = lang.not_available;
        }

        /* add value to table with rate ranges */
        if ($('#rrange_regular').length > 0) {
            var price_val = price;

            // add qtip with description of missing standard rate
            if (price_val === lang.not_available) {
                price += ' <img class="qtip booking_qtip" alt="" title="' + lang['booking_empty_regular'] + '" ';
                price += 'src="' + bookingConfigs['src_delete_img'] + '" />';
            }

            $('#rrange_regular').find('div[data-caption="' + lang['price'] + '"]').html(price);

            if (price_val === lang.not_available) {
                self.qtipInit(true);
            }
        }

        return price;
    };

    /**
     * Handler for booking steps
     *
     * @param {string} steps      - Key of step or "all"
     * @param {string} action     - Show/hide
     * @param {object} parameters - Additional parameters (optional)
     */
    this.stepsHandler = function(steps, action, parameters) {
        if (!steps || !action) {
            return;
        }

        // show/hide booking details section
        if (steps === 'details') {
            if (action === 'show' && !parameters.mode) {
                var html = '';

                if (!bookingConfigs.in_popup) {
                    if (rlPageInfo.controller === 'booking_order') {
                        html = '<div class="table-cell escort-date">';
                        html += '<div class="name"><div><span>';

                        html += bookingConfigs.Booking_type === 'time_range'
                        ? lang.booking_escort_date
                        : lang.booking_checkin;

                        html += '</span></div></div>';
                        html += '<div class="value">';

                        // adding check-in (date & time)
                        if (parameters && parameters.start_book) {
                            if (bookingConfigs.Booking_type === 'date_time_range') {
                                if (bookingConfigs.listing_check_in) {
                                    $('[name="f[booking_check_in]"] option').each(function(){
                                        $(this).attr('disabled', 'disabled');

                                        if ($(this).attr('value') === bookingConfigs.listing_check_in) {
                                            $(this).removeAttr('disabled').attr('selected', 'selected');

                                            return false;
                                        }
                                    });
                                }

                                html += '<div class="date-value"><b>' + parameters.start_book + '</b> - </div>' + $('#booking_checkin_field').html();
                            } else {
                                html += bookingConfigs.check_in && bookingConfigs.Booking_type !== 'time_range'
                                ? parameters.start_book + ' - ' + bookingConfigs.check_in
                                : parameters.start_book;

                                if (bookingConfigs.Booking_type === 'time_range') {
                                    html += '<span style="margin: 0 5px;">/</span><a href="javascript:void(0)" ';
                                    html += 'onclick="booking.escortChangeDate()">';
                                    html += lang.booking_button_select_date + '</a>';
                                }
                            }
                        }

                        html += '</div></div>';

                        if (bookingConfigs.Booking_type === 'time_range') {
                            html += '<div class="table-cell">';
                            html += '<div class="name"><div><span>' + lang.booking_escort_type + '</span></div></div>';
                            html += '<div class="value">' + $('#escort_rates_obj').html() + '</div>';

                            $('#escort_rates_obj').remove();

                            html += '</div>';
                        }

                        html += '<div class="table-cell">';
                        html += '<div class="name"><div><span>';

                        if (bookingConfigs.Booking_type === 'time_range') {
                            let $timeContainer = $('#booking_checkout_field'),
                                $durationContainer = $('#escort_duration_obj');

                            if (bookingConfigs.Booking_repeatedly) {
                                html += lang.booking_escort_time;
                                html += '</span></div></div>';
                                html += '<div class="value checkout_value_field">';

                                self.updateEscortAvailabilityData();

                                html += $timeContainer.html();
                                html += '</div></div>';
                                html += '<div class="table-cell">';
                                html += '<div class="name"><div><span>' + lang.booking_escort_duration;
                                html += '</span></div></div>';
                                html += '<div class="value">' + $durationContainer.html();
                            } else {
                                html += lang.booking_escort_duration;
                                html += '</span></div></div>';
                                html += '<div class="value hourly-time"><div class="view">' + lang.not_available + '</div>';
                                html += '<div class="origin hide">' + $durationContainer.html() + '</div>';
                                html += '</div></div>';
                                html += '<div class="table-cell">';
                                html += '<div class="name"><div><span>';
                                html += lang.booking_escort_time;
                                html += '</span></div></div>';
                                html += '<div class="value checkout_value_field">';

                                self.updateEscortAvailabilityData();

                                html += $timeContainer.html();
                                html += '</div></div>';
                            }

                            $timeContainer.remove();
                            $durationContainer.remove();
                        } else {
                            html += lang.booking_checkout;
                            html += '</span></div></div>';
                            html += '<div class="value checkout_value_field">';

                            // adding check-out (date & time)
                            if (parameters && parameters.end_book) {
                                if (bookingConfigs.Booking_type === 'date_time_range') {
                                    if (bookingConfigs.listing_check_out) {
                                        $('[name="f[booking_check_out]"] option[value="0"]').attr('disabled', 'disabled');

                                        $($('[name="f[booking_check_out]"] option').get().reverse()).each(function(){
                                            $(this).attr('disabled', 'disabled');

                                            if ($(this).attr('value') === bookingConfigs.listing_check_out) {
                                                $(this).removeAttr('disabled').attr('selected', 'selected');

                                                return false;
                                            }
                                        });
                                    }

                                    html += '<div class="date-value"><b>' + parameters.end_book + '</b> - </div>' + $('#booking_checkout_field').html();
                                } else {
                                    html += bookingConfigs.check_out
                                    ? parameters.end_book + ' - ' + bookingConfigs.check_out
                                    : parameters.end_book
                                }
                            }
                        }

                        html += '</div></div>';

                        if (bookingConfigs.Booking_type !== 'time_range') {
                            html += '<div class="table-cell">';
                            html += '<div class="name"><div><span>' + lang.booking_nights + '</span></div></div>';
                            html += '<div class="value">';
                            html += parameters && parameters.total_days ? parameters.total_days : '';
                            html += '</div></div>';
                        }

                        if (bookingConfigs.total_cost || bookingConfigs.Booking_type === 'time_range') {
                            html += '<div class="table-cell">';
                            html += '<div class="name"><div><span>' + lang.booking_amount + '</span></div></div>';
                            html += '<div class="value amount">';

                            // adding total price
                            var total_cost = bookingConfigs.total_cost ? bookingConfigs.total_cost : lang.not_available;

                            if (bookingConfigs.total_cost) {
                                html += bookingConfigs.system_currency_position === 'before'
                                ? bookingConfigs.defCurrency + ' ' + total_cost
                                : total_cost + ' ' + bookingConfigs.defCurrency;
                            } else {
                                html += total_cost;
                            }

                            html += '</div></div>';

                            // added downpayment data
                            if (bookingConfigs.prepayment) {
                                var downpayment = self.countPrepayment(bookingConfigs.total_cost);

                                html += '<div class="table-cell">';
                                html += '<div class="name"><div><span>' + lang.booking_prepayment_step + '</span></div></div>';
                                html += '<div class="value downpayment">' + downpayment + '</div></div>';
                            }
                        }

                        html += '</div>';
                    }

                    // next step button
                    html += '<div class="form-buttons">';

                    // hidden fields with system values
                    html += '<form method="post" action="' + bookingConfigs.booking_order_page + '">';
                    html += '<input type="hidden" name="listing_id" value="' + bookingConfigs.listing_id + '">';
                    html += '<input type="hidden" name="start_date" value="' + bookingConfigs.db_start + '">';
                    html += '<input type="hidden" name="start_date_value" value="';
                    html += parameters && parameters.start_book ? parameters.start_book : '';
                    html += '">';
                    html += '<input type="hidden" name="checkin" value="' + bookingConfigs.check_in + '">';
                    html += '<input type="hidden" name="end_date" value="' + bookingConfigs.db_end + '">';
                    html += '<input type="hidden" name="end_date_value" value="';
                    html += parameters && parameters.end_book ? parameters.end_book : '';
                    html += '">';
                    html += '<input type="hidden" name="checkout" value="' + bookingConfigs.check_out + '">';
                    html += '<input type="hidden" name="total_cost" value="' + bookingConfigs.total_cost + '">';
                    html += '<input type="hidden" name="total_days" value="';
                    html += parameters && parameters.total_days ? parameters.total_days : '';
                    html += '">';
                    html += '<input ';
                    html += rlPageInfo.controller === 'listing_details'
                    ? 'type="submit" value="' + lang.booking_message_step_button + '"'
                    : 'type="button" onclick="booking.stepsHandler(\'fields\', \'show\')" ' + 'value="' + lang.next_step + '"';

                    if (bookingConfigs.Booking_type === 'time_range'
                        && rlPageInfo.controller === 'booking_order'
                        && !bookingConfigs.edit_action
                    ) {
                        html += ' class="disabled" disabled="disabled"';
                    }

                    html += ' /></form>';
                    html += '</div>';

                    $('#booking_message').html(html);

                    self.conversionAmountHandler();

                    // add copy of button which will be added after all fields
                    if (rlPageInfo.controller === 'booking_order') {
                        $('.booking_message_clone').closest('.table-cell').remove();

                        let $buttonForm = $('#booking_message').find('input[type="button"]');
                        let $button = $buttonForm.clone();
                        $buttonForm.hide();
                        $button.addClass('booking_message_clone');
                        $('#booking_message_obj').append(
                            $('<div>', {class: 'table-cell'}).append(
                                $('<div>', {class: 'name'}),
                                $('<div>', {class: 'value'}).append(
                                    $button
                                )
                            )
                        );
                        $button.show();
                    }

                    $('#booking_message_obj').fadeIn('slow');

                    if (rlPageInfo.controller === 'listing_details') {
                        flynax.slideTo('#area_booking');
                    }

                    if (rlPageInfo.controller === 'booking_order') {
                        self.dateTimeRangeHandler();

                        if (bookingConfigs.Booking_type === 'time_range') {
                            self.escortFieldsValidateHandler();
                            self.escortAmountHandler();

                            if (bookingConfigs.edit_action) {
                                $('[name="escort_duration"]').change();
                            }
                        }
                    }
                } else {
                    html += '<div class="name"><div><span>';

                    html += bookingConfigs.Booking_type === 'time_range'
                    ? lang.booking_escort_date
                    : lang.booking_checkin;

                    html += '</span></div></div>';
                    html += '<div class="value">';

                    html += bookingConfigs.check_in && bookingConfigs.Booking_type !== 'time_range'
                    ? parameters.start_book + ' - ' + bookingConfigs.check_in
                    : parameters.start_book;

                    html += '<span style="margin: 0 5px;">/</span><a href="javascript:void(0)" ';
                    html += 'onclick="booking.escortChangeDate()">';
                    html += lang['booking_button_select_date'] + '</a>';

                    html += '</div>';

                    // update start date
                    $('#booking_message').find('form input[name="start_date"]').val(bookingConfigs.db_start);

                    // update start date value
                    $('#booking_message').find('form input[name="start_date_value"]').val(parameters.start_book);

                    // update date for user
                    $('.table-cell.escort-date').html(html);
                }
            }
        } else if (steps === 'fields') {
            if (action === 'show') {
                if (rlPageInfo.controller === 'booking_order') {
                    // Saving escort data to form
                    if (bookingConfigs.Booking_type === 'time_range') {
                        var $bookingForm = $('#booking_message');

                        $bookingForm.find('[name="checkin"]').val(bookingConfigs.escort_type_service);
                        $bookingForm.find('[name="checkout"]').val(bookingConfigs.escort_time_service);
                        $bookingForm.find('[name="end_date"]').val($('[name="escort_duration"] option:selected').val());
                        $bookingForm.find('[name="total_cost"]').val(bookingConfigs.total_cost);
                    }

                    var $booking_form = $('#booking_message form');

                    $booking_form.find('[type="button"]')
                        .addClass('disabled')
                        .attr('disabled', true)
                        .val(lang.loading);

                    $booking_form.attr('action', bookingConfigs.booking_order_page_fields_step);
                    $booking_form.submit();
                } else {
                    $('#step_2').fadeIn('slow');
                    self.bookingFieldsValidator();
                    $('#booking_message [type="button"]').closest('.table-cell').hide();
                }
            } else if (action === 'hide') {
                $('#step_2').fadeOut('fast');
            }
        } else if (steps === 'all') {
            if (action === 'hide') {
                $('#step_2, #booking_message_obj').fadeOut('fast');
                bookingConfigs.first = 0;
                self.book_color(true);
            }
        } else if (steps === 'update_period') {
            if (action === 'update') {
                if (bookingConfigs.Booking_type === 'date_time_range'
                    && bookingConfigs.listing_check_in
                    && bookingConfigs.listing_check_out
                ) {
                    /* build html for check in field */
                    checkin_html = '<div class="name"><div><span>' + lang.booking_checkin;
                    checkin_html += '</span></div></div>';
                    checkin_html += '<div class="field start_date">';

                    // block unavailable time
                    $('[name="f[booking_check_in]"] option').each(function(){
                        $(this).attr('disabled', 'disabled');

                        if ($(this).attr('value') === bookingConfigs.listing_check_in) {
                            $(this).removeAttr('disabled').attr('selected', 'selected');

                            return false;
                        }
                    });

                    checkin_html += bookingConfigs.start_date_value + ' - ';
                    checkin_html += $('#booking_checkin_field').html();
                    checkin_html += '</div>';

                    $('.table-cell.check-in').html(checkin_html);
                    /* end html for check-in field */

                    /* build html for check-out field */
                    checkout_html = '<div class="name"><div><span>' + lang.booking_checkout;
                    checkout_html += '</span></div></div>';
                    checkout_html += '<div class="field end_date">';

                    $('[name="f[booking_check_out]"] option[value="0"]').attr('disabled', 'disabled');

                    // block unavailable time
                    $($('[name="f[booking_check_out]"] option').get().reverse()).each(function(){
                        $(this).attr('disabled', 'disabled');

                        if ($(this).attr('value') === bookingConfigs.listing_check_out) {
                            $(this).removeAttr('disabled').attr('selected', 'selected');

                            return false;
                        }
                    });

                    checkout_html += '<b>' + bookingConfigs.end_date_value + '</b> - ';
                    checkout_html += $('#booking_checkout_field').html();
                    checkout_html += '</div>';

                    $('.table-cell.check-out').html(checkout_html);
                    /* end html for check-out field */

                    self.dateTimeRangeHandler();
                } else {
                    if (bookingConfigs.start_date_value) {
                        let start_date = bookingConfigs.check_in
                            ? '<b>' + bookingConfigs.start_date_value + '</b> - ' + bookingConfigs.check_in
                            : '<b>' + bookingConfigs.start_date_value + '</b>';

                        $('#area_booking .field.start_date').html(start_date);
                    }

                    if (bookingConfigs.end_date_value) {
                        let end_date = bookingConfigs.check_out
                            ? '<b>' + bookingConfigs.end_date_value + '</b> - ' + bookingConfigs.check_out
                            : '<b>' + bookingConfigs.end_date_value + '</b>';

                        $('#area_booking .field.end_date').html(end_date);
                    }

                    if (bookingConfigs.Booking_type === 'date_range' && bookingConfigs.db_start && bookingConfigs.db_end) {
                        let nightsCount = self.getCountDaysBetween2Timestamps(
                            bookingConfigs.db_start,
                            bookingConfigs.db_end
                        );

                        $('#area_booking .field.nights-count').html(nightsCount);
                    }
                }

                if (bookingConfigs.total_cost) {
                    var price = bookingConfigs.system_currency_position === 'before'
                    ? bookingConfigs.defCurrency + ' ' + bookingConfigs.total_cost
                    : bookingConfigs.total_cost + ' ' + bookingConfigs.defCurrency;

                    $('#area_booking .field.total_cost').text(price);

                    if (typeof $.convertPrice == 'function') {
                        $('#area_booking .field.total_cost').convertPrice({showCents: true});
                    }
                }

                bookingConfigs.period_updated = true;
            }
        }
    };

    /**
     * Show calendar in modal window for escort package
     */
    this.escortChangeDate = function() {
        $(document).flModal({
            caption : lang['booking_button_select_date'],
            content : '<div id="booking_calendar" class="in_popup"' + '>' + lang['loading'] + '</div>',
            ready   : function() {
                bookingConfigs['in_popup'] = true;
                bookingConfigs['s_id']     = 0;

                self.getDates(bookingConfigs.listing_id, null, true);
            },
            width   : '400',
            height  : 'auto',
            click   : false
        });
    };

    /**
     * Control filling escort fields (type, time, duration)
     */
    this.escortFieldsValidateHandler = function() {
        var $typeSelector     = $('[name="escort_rates"]'),
            $timeSelector     = $('[name="f[booking_check_out]"]'),
            $durationSelector = $('[name="escort_duration"]');

        $typeSelector.change(function(){
            // reset time/duration fields
            var type_service  = Number($typeSelector.find('option:selected').val());
            var hours_in_type = Number($typeSelector.find('option:selected').data('hours'));

            self.selectFirstActiveOption($timeSelector);
            self.selectFirstActiveOption($durationSelector);

            if (type_service < 0) {
                $timeSelector.addClass('disabled').attr('disabled', 'disabled');
                $durationSelector.addClass('disabled').attr('disabled', 'disabled');
            } else {
                bookingConfigs.escort_type_service       = type_service;
                bookingConfigs.escort_type_service_title = trim($typeSelector.find('option:selected').html());

                // enable all options
                $durationSelector.find('option').removeClass('hide').removeAttr('disabled');

                if (hours_in_type) {
                    let activeWorkOptions = $timeSelector.find('option:not(:disabled)').length;
                    let activeWorkHours = (0.5 * activeWorkOptions) - 0.5; // Exclude last option with end of workday

                    // enable only options which can be used with selected type of service
                    $durationSelector.find('option').each(function(){
                        var option_value = parseFloat($(this).attr('value'));

                        if ((option_value > 0 && option_value % hours_in_type !== 0)
                            || (activeWorkHours > 0 && option_value > activeWorkHours)
                        ) {
                            $(this).attr('disabled', true).addClass('hide');
                        }
                    });

                    $durationSelector.removeClass('disabled').removeAttr('disabled');
                } else {
                    bookingConfigs['escort_duration'] = false;
                    $durationSelector.addClass('disabled').attr('disabled', 'disabled');
                }

                if ($timeSelector.hasClass('disabled')) {
                    $timeSelector.removeClass('disabled').removeAttr('disabled');

                    if (Number($timeSelector.find('option:selected').val()) > 0 && hours_in_type) {
                        $durationSelector.removeClass('disabled').removeAttr('disabled');
                    }
                }

                self.selectFirstActiveOption($durationSelector);
            }

            if (!bookingConfigs.Booking_repeatedly) {
                let $hourlyTime = $('.hourly-time .view');

                if (type_service >= 0) {
                    $hourlyTime.text(trim($durationSelector.find('option:selected').html()));
                } else {
                    $hourlyTime.text(lang.not_available);
                }
            }

            self.updateEscortAvailabilityData();
        });

        $timeSelector.change(function(){
            var time = parseInt($timeSelector.find('option:selected').val());

            if (time > 0) {
                bookingConfigs.escort_time_service    = $timeSelector.find('option:selected').val();
                bookingConfigs.escortTimeServiceValue = trim($timeSelector.find('option:selected').html());

                if (parseInt($typeSelector.find('option:selected').data('hours')) > 1) {
                    $durationSelector.removeClass('disabled').removeAttr('disabled');
                }

                // Enable all options
                $durationSelector.find('option').removeClass('hide').removeAttr('disabled');

                var hours_in_type = Number($typeSelector.find('option:selected').data('hours'));

                if (hours_in_type) {
                    let activeWorkHours = 0, currentTime = false;
                    $timeSelector.find('option').each(function() {
                        if (currentTime && !$(this).is(':disabled')) {
                            activeWorkHours += 0.5;
                        }

                        if ($(this).is(':selected')) {
                            currentTime = true;
                        }
                    });

                    // Enable only options which can be used with selected time and rest of time
                    if (activeWorkHours === 0) {
                        $durationSelector.find('option:not(:first)').attr('disabled', true).addClass('hide');
                    } else {
                        $($durationSelector.find('option').get().reverse()).each(function(){
                            var optionValue = parseFloat($(this).attr('value'));

                            if ((optionValue > 0 && optionValue % hours_in_type !== 0)
                                || (activeWorkHours > 0 && optionValue > activeWorkHours)
                            ) {
                                $(this).attr('disabled', true).addClass('hide');
                            }
                        });
                    }
                }

                self.selectFirstActiveOption($durationSelector);
            } else {
                $durationSelector.addClass('disabled').attr('disabled', 'disabled');
            }
        });

        $durationSelector.change(function () {
            if (rlPageInfo.controller !== 'booking_requests') {
                return;
            }

            bookingConfigs.escort_duration       = $durationSelector.find('option:selected').val();
            bookingConfigs.escort_duration_title = trim($durationSelector.find('option:selected').html());
        });
    };

    /**
     * Count total booking amount for escort
     */
    this.escortAmountHandler = function() {
        let $nextButton           = $('#booking_message input[type="button"]'),
            $amountContainer      = $('#booking_message,#booking_details').find('.value.amount,.field.amount'),
            $answerContainer      = $('#owner_actions'),
            $downpaymentContainer = $('.table-cell .value.downpayment');

        $('[name="escort_rates"],[name="f[booking_check_out]"],[name="escort_duration"]').change(function(){
            let $selectedType   = $('[name="escort_rates"] option:selected'),
                amount          = 0,
                activeWorkHours = 0,
                $timeSelector   = $('[name="f[booking_check_out]"]'),
                type            = parseInt($selectedType.val()),
                time            = parseInt($('[name="f[booking_check_out]"] option:selected').val()),
                duration        = parseFloat($('[name="escort_duration"] option:selected').val()),
                price           = parseFloat($selectedType.data('price')),
                hoursInType     = parseFloat($selectedType.data('hours'));

            $timeSelector.find('option').each(function() {
                if (!$(this).is(':disabled')) {
                    activeWorkHours += 0.5;
                }
            });

            if (type !== -1
                && time !== 0
                && (duration !== -1 || (duration === -1 && !hoursInType))
                && bookingConfigs.db_start
                && price
                && (activeWorkHours && activeWorkHours >= hoursInType)
            ) {
                $nextButton.removeClass('disabled').removeAttr('disabled');
                $answerContainer.removeClass('hide');

                if (rlPageInfo.controller === 'booking_order') {
                    $('.booking_message_clone').removeClass('disabled').removeAttr('disabled');
                }

                // show price without calculation
                if (duration === -1 && !hoursInType) {
                    amount = price;
                } else {
                    bookingConfigs.escort_duration = duration;

                    if (hoursInType === duration) {
                        amount = price;
                    } else {
                        duration = hoursInType > 0 ? duration / hoursInType : duration;
                        amount   = Math.round((price * duration) * 100) / 100;
                    }
                }

                bookingConfigs.total_cost = amount;

                amount = bookingConfigs.system_currency_position === 'before'
                ? bookingConfigs.defCurrency + ' ' + amount
                : amount + ' ' + bookingConfigs.defCurrency;

                $amountContainer.text(amount);

                // update downpayment if it exist
                var downpayment = self.countPrepayment(bookingConfigs.total_cost);
                if (bookingConfigs.prepayment) {
                    $downpaymentContainer.text(downpayment);
                }

                self.conversionAmountHandler();
            } else {
                $nextButton.addClass('disabled').attr('disabled', 'disabled');
                $answerContainer.addClass('hide');
                $amountContainer.text(lang.not_available);

                if (bookingConfigs.prepayment) {
                    $downpaymentContainer.text(lang.not_available);
                }

                if (rlPageInfo.controller === 'booking_order') {
                    $('.booking_message_clone').addClass('disabled').attr('disabled', 'disabled');
                }
            }
        });
    };

    /**
     * Count prepayment by price
     * @param  {string} price
     * @return {string}
     */
    this.countPrepayment = function(price) {
        var downpayment = lang.not_available;

        if (price && bookingConfigs.prepayment) {
            downpayment = (parseFloat(price.toString().replace(/[^0-9.]+/g, "")) / 100) * bookingConfigs.prepayment;
            downpayment = Math.round((downpayment) * 100) / 100;
            downpayment = bookingConfigs.system_currency_position === 'before'
            ? bookingConfigs.defCurrency + ' ' + downpayment
            : downpayment + ' ' + bookingConfigs.defCurrency;
        }

        return downpayment;
    };

    /**
     * Get availability of selected day
     *
     * @since 3.2.0
     *
     * @param day - Date in timestamp
     * @returns {string|boolean}
     */
    this.getAvailabilityOfDay = function(day) {
        day = Number(day);

        if (!day) {
            return false;
        }

        var av_mon = bookingConfigs.availability.day_1 ? bookingConfigs.availability.day_1 : '';
        var av_tue = bookingConfigs.availability.day_2 ? bookingConfigs.availability.day_2 : '';
        var av_wed = bookingConfigs.availability.day_3 ? bookingConfigs.availability.day_3 : '';
        var av_thu = bookingConfigs.availability.day_4 ? bookingConfigs.availability.day_4 : '';
        var av_fri = bookingConfigs.availability.day_5 ? bookingConfigs.availability.day_5 : '';
        var av_sat = bookingConfigs.availability.day_6 ? bookingConfigs.availability.day_6 : '';
        var av_sun = bookingConfigs.availability.day_7 ? bookingConfigs.availability.day_7 : '';

        var selected_day = self.getDateByTimeZone(day, 'ddd').toLowerCase();
        var current_av = '';

        // get availability data of selected day
        if (selected_day === 'mon' && av_mon) {
            current_av = av_mon;
        } else if (selected_day === 'tue' && av_tue) {
            current_av = av_tue;
        } else if (selected_day === 'wed' && av_wed) {
            current_av = av_wed;
        } else if (selected_day === 'thu' && av_thu) {
            current_av = av_thu;
        } else if (selected_day === 'fri' && av_fri) {
            current_av = av_fri;
        } else if (selected_day === 'sat' && av_sat) {
            current_av = av_sat;
        } else if (selected_day === 'sun' && av_sun) {
            current_av = av_sun;
        }

        return current_av;
    };

    /**
     * Update data in "Time of Service" field
     */
    this.updateEscortAvailabilityData = function() {
        let $timeSelector = $('[name="f[booking_check_out]"]');
        let $timeOptions  = $timeSelector.find('option');
        let $selectedRate = $('select[name="escort_rates"] option[value="' + bookingConfigs.escort_type_service + '"]');
        let rateType      = $selectedRate.length ? $selectedRate.data('type') : null;
        let rateIndex     = $selectedRate.length ? $selectedRate.val() : null;

        // reset options
        $($timeOptions.get()).each(function(){
            $(this).removeAttr('disabled');

            if (!bookingConfigs.edit_action) {
                $(this).removeAttr('selected');
            }
        });

        var current_av = self.getAvailabilityOfDay(bookingConfigs.db_start);

        // update by availability data
        if (current_av) {
            var av_from = '';
            if (current_av[0] !== lang.time_midnight && current_av[0] !== lang.time_noon) {
                av_from = current_av[0];
            } else {
                if (current_av[0] === lang.time_midnight) {
                    av_from = '12:00 am';
                } else if (current_av[0] === lang.time_noon) {
                    av_from = '12:00 pm';
                }
            }

            var av_to = '';
            if (current_av[1] !== lang.time_midnight && current_av[1] !== lang.time_noon) {
                av_to = current_av[1];
            } else {
                if (current_av[1] === lang.time_midnight) {
                    av_to = '12:00 am';
                } else if (current_av[1] === lang.time_noon) {
                    av_to = '12:00 pm';
                }
            }

            // set "from"
            $($timeOptions.get()).each(function(){
                $(this).attr('disabled', 'disabled');
                var option_value = $(this).attr('value');

                if (option_value === av_from) {
                    $(this).removeAttr('disabled');

                    if (!bookingConfigs.edit_action) {
                        $(this).attr('selected', 'selected');
                        bookingConfigs.escort_time_service = option_value;
                    } else {
                        bookingConfigs.escort_time_service = bookingConfigs.check_out;
                    }

                    return false;
                }
            });

            // set "to"
            $($timeOptions.get().reverse()).each(function(){
                $(this).attr('disabled', 'disabled');

                if ($(this).attr('value').replace(' ' + lang.booking_time_next_day, '') === av_to) {
                    // Disable the end of work-day
                    $(this).removeAttr('disabled');
                    return false;
                }
            });

            // Disable already booked time by other bookings
            if (bookingConfigs.usBook) {
                for (var i in bookingConfigs.usBook) {
                    if (!bookingConfigs.usBook.hasOwnProperty(i)) {
                        continue;
                    }

                    var userBook = bookingConfigs.usBook[i];

                    // Skip booking from other days
                    if (userBook[1] != bookingConfigs.db_start) {
                        continue;
                    }

                    // Skip own request in requests page
                    if (rlPageInfo.controller === 'booking_requests' && userBook[3] == bookingConfigs.request_id) {
                        continue;
                    }

                    // Miss all booked time which using other type of rate
                    if (((rateType && rateIndex !== userBook[8]) || !rateType)
                        && bookingConfigs.Booking_repeatedly === false
                        && bookingConfigs.is_escort === false
                    ) {
                        continue;
                    }

                    var checkInTime          = userBook[5];
                    var duration             = userBook[6];
                    var countDisabledOptions = duration * 2; // 1h => 30m * 2, 2h => 30m * 4 and etc.

                    $($timeOptions.get()).each(function(){
                        if ($(this).val() === '0' || $(this).val().indexOf(lang.booking_time_next_day) > 0) {
                            return true;
                        }

                        if ($(this).val() === checkInTime) {
                            $(this).attr('disabled', 'disabled');
                            var $option = $(this);

                            for (var i = 1; i <= countDisabledOptions; i++) {
                                $option.attr('disabled', 'disabled');
                                $option = $option.next();
                            }
                        }
                    });
                }
            }

            self.selectFirstActiveOption($timeSelector);
            bookingConfigs.escort_time_service = $timeSelector.find('option:selected').val();
        }
    };

    /**
     * Check booking fields before submit form
     */
    this.bookingFieldsValidator = function() {
        $('#checkValid').unbind('click').click(function(){
            var error_fields = field_names = cond_fields_names = '';
            $(this).closest('form').submit(function(){ return false });

            // get all fields from form
            $(this).closest('form').find('.submit-cell:not(.buttons)').each(function(){
                var field = $(this).find('input,textarea');
                var field_name = field.closest('.submit-cell').find('.name').text().replace('* ', '').trim();
                var field_key = field.attr('name');
                var field_val = field.val();

                // check "text", "number" and "textarea" fields
                if (field.hasClass('required') && !field.hasClass('bool') && !field_val) {
                    // collect field names
                    field_names += (field_names ? ',' : '') + '"<b>' + field_name + '</b>"';

                    // collect field keys
                    if (error_fields.indexOf(field_key) < 0) {
                        error_fields = error_fields ? error_fields + ',' + field_key : field_key;
                    }
                }

                // check conditions (isUrl, isDomain, isEmail)
                if (field.hasClass('required')
                    && ((field.hasClass('isUrl') && !self.checkCondition(field_val, 'url'))
                        || (field.hasClass('isDomain') && !self.checkCondition(field_val, 'domain'))
                        || (field.hasClass('isEmail') && !self.checkCondition(field_val, 'mail'))
                    )
                ) {
                    // collect field names
                    cond_fields_names += (cond_fields_names ? ',' : '') + '"<b>' + field_name + '</b>"';

                    // collect field keys & if key not exist in list already
                    if (error_fields.indexOf(field_key) < 0) {
                        error_fields = error_fields ? error_fields + ',' + field_key : field_key;
                    }
                }
            });

            if (error_fields && (field_names || cond_fields_names)) {
                // some fields are empty && values incorrect
                if (error_fields && field_names && cond_fields_names) {
                    var errors_content = '<ul>';
                    errors_content += '<li>';
                    errors_content += lang['booking_field_error'].replace('error_field', field_names);
                    errors_content += '</li>';
                    errors_content += '<li>';
                    errors_content += lang['booking_inccorect_field_error'].replace('error_field', cond_fields_names);
                    errors_content += '</li>';

                    printMessage('error', errors_content, error_fields);
                }
                // some fields are empty
                else if (error_fields && field_names) {
                    printMessage(
                        'error',
                        lang['booking_field_error'].replace('error_field', field_names),
                        error_fields
                    );
                }
                // values incorrect
                else if (error_fields && cond_fields_names) {
                    printMessage(
                        'error',
                        lang['booking_inccorect_field_error'].replace('error_field', cond_fields_names),
                        error_fields
                    );
                }
            } else {
                $(this).closest('form').unbind('submit').submit(function(){
                    $('#checkValid').addClass('disabled').attr('disabled', true).val(lang['loading']);

                    if (bookingConfigs['booking_order_page']) {
                        var $booking_form = $(this);
                        var action_url = bookingConfigs.booking_order_page;

                        if (action_url) {
                            if (bookingConfigs.prepayment) {
                                action_url = bookingConfigs.booking_order_page_prepayment_step;
                            }

                            $booking_form.attr('action', action_url);
                        }
                    }

                    return true;
                });
            }
        });
    };

    /**
     * Validate string by condition
     * @param  {string} string - Incoming string
     * @param  {string} mode   - Type of condition
     * @return {bool}
     */
    this.checkCondition = function(string, mode) {
        var pattern;

        if (mode === 'domain') {
            pattern = /\S+\.\S+/;
        } else if (mode === 'url') {
            pattern = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
        } else if (mode === 'mail') {
            pattern = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        }

        return string && pattern ? pattern.test(string) : false;
    };

    /**
     * Copying data from selectors to output form
     */
    this.dateTimeRangeHandler = function() {
        // check-in time field
        $('[name="f[booking_check_in]"]').change(function(){
            var checkin_time = $(this).val();
            $('[name="checkin"]').val(checkin_time);

            // decrease booking amount if user reduce time of check in
            if (checkin_time && bookingConfigs['booking_regular_per_hour']) {
                var new_checkin_time = checkin_time.split(':');
                var old_checkin_time = rlPageInfo.controller === 'booking_requests'
                ? bookingConfigs['listing_check_in'].split(':')
                : bookingConfigs['check_in'].split(':');

                var new_hour = new_checkin_time[0] ? parseInt(new_checkin_time[0]) : 0;
                var time_period = new_checkin_time[1] ? new_checkin_time[1].split(' ') : false;
                new_hour = time_period['1'] && time_period['1'] === 'pm' && new_hour < 12 ? new_hour + 12 : new_hour;

                var old_hour = old_checkin_time[0] ? parseInt(old_checkin_time[0]) : 0;
                time_period = old_checkin_time[1] ? old_checkin_time[1].split(' ') : false;
                old_hour = time_period['1'] && time_period['1'] === 'pm' && old_hour < 12 ? old_hour + 12 : old_hour;

                var new_minutes = new_checkin_time[1] ? parseInt(new_checkin_time[1]) : 0;
                var old_minutes = old_checkin_time[1] ? parseInt(old_checkin_time[1]) : 0;

                if (new_hour > old_hour || (new_hour === old_hour && new_minutes > old_minutes)) {
                    var difference_time = new_hour - old_hour;
                    difference_time = new_minutes > old_minutes ? difference_time + 0.5 : difference_time;

                    var price_per_hour = bookingConfigs.booking_regular_per_hour;
                    var difference_price = difference_time && price_per_hour
                    ? difference_time * price_per_hour
                    : price_per_hour;

                    var current_booking_amount = parseFloat(bookingConfigs.total_cost);

                    if (current_booking_amount && difference_price) {
                        var new_booking_amount = Math.round((current_booking_amount - difference_price) * 100) / 100;

                        $('[name="total_cost"]').val(new_booking_amount);

                        $('.amount').text(
                            bookingConfigs.system_currency_position === 'before'
                            ? bookingConfigs.defCurrency + ' ' + new_booking_amount
                            : new_booking_amount + ' ' + bookingConfigs.defCurrency
                        );
                    }
                } else {
                    $('[name="total_cost"]').val(bookingConfigs.total_cost);
                    $('.amount').text(
                        bookingConfigs.system_currency_position === 'before'
                        ? bookingConfigs.defCurrency + ' ' + bookingConfigs.total_cost
                        : bookingConfigs.total_cost + ' ' + bookingConfigs.defCurrency
                    );
                }
            }
        });

        // check-out time field
        $('[name="f[booking_check_out]"]').change(function(){
            var checkout_time = $(this).val();
            $('[name="checkout"]').val(checkout_time);

            // Decrease booking amount if user reduce time of check out
            if (checkout_time && bookingConfigs['booking_regular_per_hour']) {
                var new_checkout_time = checkout_time.split(':');
                var old_checkout_time = rlPageInfo.controller == 'booking_requests'
                ? bookingConfigs['listing_check_out'].split(':')
                : bookingConfigs['check_out'].split(':');

                var new_hour = new_checkout_time[0] ? parseInt(new_checkout_time[0]) : 0;
                var time_period = new_checkout_time[1] ? new_checkout_time[1].split(' ') : false;

                if (time_period['1'] && time_period['1'] == 'pm' && new_hour < 12) {
                    new_hour +=  12;
                } else if (time_period['1'] && time_period['1'] == 'am' && new_hour == 12) {
                    new_hour =  0;
                }

                var old_hour = old_checkout_time[0] ? parseInt(old_checkout_time[0]) : 0;
                time_period = old_checkout_time[1] ? old_checkout_time[1].split(' ') : false;
                old_hour = time_period['1'] && time_period['1'] == 'pm' && old_hour < 12 ? old_hour + 12 : old_hour;

                var new_minutes = new_checkout_time[1] ? parseInt(new_checkout_time[1]) : 0;
                var old_minutes = old_checkout_time[1] ? parseInt(old_checkout_time[1]) : 0;

                if (new_hour < old_hour || (new_hour == old_hour && new_minutes != old_minutes)) {
                    var difference_time = old_hour - new_hour;
                    difference_time = new_minutes > old_minutes ? difference_time - 0.5 : difference_time;

                    var price_per_hour = bookingConfigs['booking_regular_per_hour'];
                    var difference_price = difference_time && price_per_hour
                    ? (Math.round((difference_time * price_per_hour) * 100) / 100)
                    : price_per_hour;

                    var current_booking_amount = parseFloat(bookingConfigs['total_cost']);

                    if (current_booking_amount && difference_price) {
                        var new_booking_amount = Math.round((current_booking_amount - difference_price) * 100) / 100;

                        $('[name="total_cost"]').val(new_booking_amount);

                        $('.amount').text(
                            bookingConfigs['system_currency_position'] == 'before'
                            ? bookingConfigs['defCurrency'] + ' ' + new_booking_amount
                            : new_booking_amount + ' ' + bookingConfigs['defCurrency']
                        );
                    }
                } else {
                    $('[name="total_cost"]').val(bookingConfigs['total_cost']);
                    $('.amount').text(
                        bookingConfigs['system_currency_position'] == 'before'
                        ? bookingConfigs['defCurrency'] + ' ' + bookingConfigs['total_cost']
                        : bookingConfigs['total_cost'] + ' ' + bookingConfigs['defCurrency']
                    );
                }
            }
        });
    };

    /**
     * Handler for "Change Period" button
     */
    this.changePeriodHandler = function() {
        // Save request data
        tmpBookingConfigs.db_start                  = bookingConfigs.db_start;
        tmpBookingConfigs.start_date_value          = bookingConfigs.start_date_value;
        tmpBookingConfigs.db_end                    = bookingConfigs.db_end;
        tmpBookingConfigs.end_date_value            = bookingConfigs.end_date_value;
        tmpBookingConfigs.total_cost                = self.str2money(bookingConfigs.total_cost);
        tmpBookingConfigs.check_in                  = bookingConfigs.check_in;
        tmpBookingConfigs.check_out                 = bookingConfigs.check_out;
        tmpBookingConfigs.escort_duration           = bookingConfigs.escort_duration;
        tmpBookingConfigs.escort_duration_title     = bookingConfigs.escort_duration_title;
        tmpBookingConfigs.escort_type_service       = bookingConfigs.escort_type_service;
        tmpBookingConfigs.escort_type_service_title = bookingConfigs.escort_type_service_title;
        tmpBookingConfigs.escort_time_service       = bookingConfigs.escort_time_service;

        $('#change_period').click(function() {
            if ($(this).hasClass('process')) {
                $('#owner_actions').removeClass('hide');
                $(this).removeClass('process').text(lang.booking_button_change_period);
                $('#cancel_changes').fadeOut();
                $('#booking_calendar').addClass('disabled loading');

                if (bookingConfigs.Booking_type === 'date_time_range') {
                    var $checkInCont  = $('.check-in [name="f[booking_check_in]"]'),
                        $checkOutCont = $('.check-out [name="f[booking_check_out]"]');

                    let checkin_time = $checkInCont.length && $checkInCont.val() !== '0'
                    && $checkInCont.val() !== bookingConfigs.check_in
                        ? $checkInCont.val()
                        : bookingConfigs.check_in;

                    let checkInTimeHuman = $checkInCont.length && $checkInCont.val() !== '0'
                        ? $checkInCont.find('option:selected').html()
                        : bookingConfigs.check_in

                    bookingConfigs.check_in = checkin_time;

                    if (bookingConfigs.start_date_value) {
                        let start_date = checkInTimeHuman
                            ? '<b>' + bookingConfigs.start_date_value + '</b> - ' + checkInTimeHuman
                            : '<b>' + bookingConfigs.start_date_value + '</b>';

                        $('#area_booking .field.start_date').html(start_date);
                    }

                    let checkout_time = $checkOutCont.length && $checkOutCont.val() !== '0'
                        && $checkOutCont.val() !== bookingConfigs.check_out
                            ? $checkOutCont.val()
                            : bookingConfigs.check_out;

                    let checkOutTimeHuman = $checkOutCont.length && $checkOutCont.val() !== '0'
                        ? $checkOutCont.find('option:selected').html()
                        : bookingConfigs.check_out

                    bookingConfigs.check_out = checkout_time;

                    if (bookingConfigs.end_date_value) {
                        let end_date = checkOutTimeHuman
                            ? '<b>' + bookingConfigs.end_date_value + '</b> - ' + checkOutTimeHuman
                            : '<b>' + bookingConfigs.end_date_value + '</b>';

                        $('#area_booking .field.end_date').html(end_date);
                    }

                    if (bookingConfigs.check_in !== bookingConfigs.listing_check_in
                        || bookingConfigs.check_out !== bookingConfigs.listing_check_out
                    ) {
                        bookingConfigs.period_updated = true;
                    }
                } else if (bookingConfigs.Booking_type === 'time_range') {
                    // Move selectors to initial place
                    $('[name="escort_rates"]').detach().appendTo($('#escort_rates_obj'));
                    $('[name="f[booking_check_out]"]').detach().appendTo($('#booking_checkout_field'));
                    $('[name="escort_duration"]').detach().appendTo($('#escort_duration_obj'));

                    if (bookingConfigs.start_date_value) {
                        $('#area_booking .field.start_date').html('<b>' + bookingConfigs.start_date_value + '</b>');
                    }

                    if (bookingConfigs.escort_type_service_title) {
                        $('#area_booking .field.type').html(bookingConfigs.escort_type_service_title);
                    }

                    if (bookingConfigs.escortTimeServiceValue) {
                        $('#area_booking .field.time').html(bookingConfigs.escortTimeServiceValue);
                    }

                    if (bookingConfigs.escort_duration_title) {
                        $('#area_booking .field.duration').html(bookingConfigs.escort_duration_title);
                    }

                    $('#area_booking').removeClass('request-details');

                    bookingConfigs.period_updated = true;
                }
            } else {
                $('#owner_actions').addClass('hide');
                $(this).text(lang.save).addClass('process');
                $(this).next().fadeIn('fast');

                if (bookingConfigs.Booking_type === 'time_range') {
                    var $bookingDetailsContainer = $('#booking_details'),
                        $typeContainer           = $('[name="escort_rates"]'),
                        $timeContainer           = $('[name="f[booking_check_out]"]'),
                        $durationContainer       = $('[name="escort_duration"]');

                    // Add padding in details
                    $('#area_booking').addClass('request-details');

                    // Add "Change date" button to "Date Of Service" field
                    var html = '<a id="change_date_button" href="javascript:void(0)">';
                    html += lang.booking_button_select_date + '</a>';

                    $bookingDetailsContainer.find('div.field.start_date')
                        .html(trim($bookingDetailsContainer.find('div.field.start_date').html()) + html);

                    $('#change_date_button').click(function() {
                        self.escortChangeDate();
                    });

                    // Add escort rates to "Type Of Service" field
                    if (bookingConfigs.check_in) {
                        $typeContainer.find('option[value="' + bookingConfigs.check_in + '"]').attr('selected', true);
                        $typeContainer = $typeContainer.detach();
                        $typeContainer.appendTo($bookingDetailsContainer.find('div.field.type').html(''));
                    }

                    // Add checkout-time selector to "Time Of Service" field
                    if (bookingConfigs.check_out) {
                        self.updateEscortAvailabilityData();

                        $timeContainer.find('option[value="' + bookingConfigs.check_out + '"]').attr('selected', true);
                        $timeContainer.removeClass('disabled').removeAttr('disabled');
                        $timeContainer = $timeContainer.detach();
                        $timeContainer.appendTo($bookingDetailsContainer.find('div.field.time').html(''));
                    }

                    // Add hours selector to "Duration (Hours)" field
                    if (bookingConfigs.db_end) {
                        $durationContainer.find('option[value="' + bookingConfigs.db_end + '"]').attr('selected', true);
                        $durationContainer.removeClass('disabled').removeAttr('disabled');
                    }
                    $durationContainer = $durationContainer.detach();

                    if (!bookingConfigs.Booking_repeatedly) {
                        $durationContainer = $('<div>', {class: 'hourly-time'}).append(
                            $('<div>', {class: 'view'}).text(lang.not_available),
                            $('<div>', {class: 'origin hide'}).append($durationContainer)
                        )
                    }

                    $durationContainer.appendTo($bookingDetailsContainer.find('div.field.duration').html(''));

                    self.escortFieldsValidateHandler();
                    self.escortAmountHandler();

                    // Hide unavailable values in "Duration" field
                    var hours_in_type = parseInt($typeContainer.find('option:selected').data('hours'));

                    $durationContainer.find('option').each(function(){
                        var option_value = parseInt($(this).attr('value'));

                        if (option_value > 0 && option_value % hours_in_type !== 0) {
                            $(this).attr('disabled', true).addClass('hide');
                        }
                    });

                    $('div.field.duration').closest('div.table-cell').removeClass('hide');
                    $typeContainer.change();
                } else {
                    self.getDates(bookingConfigs.listing_id, null, true);
                }
            }
        });
    };

    /**
     * Revert data of request to initial
     */
    this.revertRequestChanges = function() {
        if (tmpBookingConfigs['db_start'] && tmpBookingConfigs['db_end']) {
            $('#cancel_changes').click(function(){
                bookingConfigs['db_start']                  = tmpBookingConfigs['db_start'];
                bookingConfigs['start_date_value']          = tmpBookingConfigs['start_date_value'];
                bookingConfigs['db_end']                    = tmpBookingConfigs['db_end'];
                bookingConfigs['end_date_value']            = tmpBookingConfigs['end_date_value'];
                bookingConfigs['total_cost']                = tmpBookingConfigs['total_cost'];
                bookingConfigs['check_in']                  = tmpBookingConfigs['check_in'];
                bookingConfigs['check_out']                 = tmpBookingConfigs['check_out']
                bookingConfigs['escort_duration']           = tmpBookingConfigs['escort_duration'];
                bookingConfigs['escort_duration_title']     = tmpBookingConfigs['escort_duration_title'];
                bookingConfigs['escort_type_service']       = tmpBookingConfigs['escort_type_service'];
                bookingConfigs['escort_type_service_title'] = tmpBookingConfigs['escort_type_service_title'];
                bookingConfigs['escort_time_service']       = tmpBookingConfigs['escort_time_service'];

                self.getDates(bookingConfigs['listing_id']);
                self.stepsHandler('update_period', 'update');

                if (bookingConfigs['Booking_type'] == 'date_time_range') {
                    if (bookingConfigs['start_date_value']) {
                        var start_date = bookingConfigs['check_in']
                        ? '<b>' + bookingConfigs['start_date_value'] + '</b> - ' + bookingConfigs['check_in']
                        : '<b>' + bookingConfigs['start_date_value'] + '</b>';

                        $('#area_booking .field.start_date').html(start_date);
                    }

                    if (bookingConfigs['end_date_value']) {
                        var end_date = bookingConfigs['check_out']
                        ? '<b>' + bookingConfigs['end_date_value'] + '</b> - ' + bookingConfigs['check_out']
                        : '<b>' + bookingConfigs['end_date_value'] + '</b>';

                        $('#area_booking .field.end_date').html(end_date);
                    }
                } else if (bookingConfigs['Booking_type'] == 'time_range') {
                    // move selectors to inital place
                    $('[name="escort_rates"]').detach().appendTo($('#escort_rates_obj'));
                    $('[name="f[booking_check_out]"]').detach().appendTo($('#booking_checkout_field'));
                    $('[name="escort_duration"]').detach().appendTo($('#escort_duration_obj'));

                    if (bookingConfigs['start_date_value']) {
                        $('#area_booking .field.start_date').html('<b>' + bookingConfigs['start_date_value'] + '</b>');
                    }

                    if (bookingConfigs['escort_type_service_title']) {
                        $('#area_booking .field.type').html(bookingConfigs['escort_type_service_title']);
                    }

                    if (bookingConfigs['escort_time_service']) {
                        $('#area_booking .field.time').html(bookingConfigs['escort_time_service']);
                    }

                    if (bookingConfigs['escort_duration_title']) {
                        $('#area_booking .field.duration').html(bookingConfigs['escort_duration_title']);
                    }

                    $('#area_booking').removeClass('request-details');
                }

                $('#change_period').text(lang['booking_button_change_period']).removeClass('process');
                $('#cancel_changes').fadeOut();
                $('#owner_actions').removeClass('hide');
            });
        }
    };

    /**
     * Find first option without "disabled" attribute and select it
     * @param {object} $container
     */
    this.selectFirstActiveOption = function($container) {
        if ($container.length < 0) {
            return false;
        }

        // remove selection from active option
        $container.find('option:selected').removeAttr('selected').removeProp('selected');

        // select first active option
        $container.find('option').each(function(){
            if (!$(this).prop('disabled') && $(this).val() != '-1' && !$(this).hasClass('hide')) {
                $(this).prop('selected', true);
                return false;
            }
        });

        $container.trigger('change');
    };

    /**
     * Convert date by selected timezone
     * @param  {int}    timestamp
     * @param  {string} custom_format
     * @return {string}               - Converted date
     */
    this.getDateByTimeZone = function(timestamp, custom_format) {
        if (!timestamp) {
            return '';
        }

        var format = custom_format
        ? custom_format
        : bookingConfigs['bookingDateFormat'].toUpperCase().replace(/%/g, '').replace('B', 'MMM');

        if (timestamp && format) {
            return moment.tz(new Date(timestamp * 1000), bookingConfigs.bookingTimeZone).format(format);
        }
    };

    /**
     * Remove decimals and convert price to short forms (1k, 1m, 1b)
     *
     * @since 3.2.0 Rename method from "adaptNumbersinPrice" to "adaptNumbersInPrice"
     * @since 3.1.0
     *
     * @param  {float} price
     * @return {int}         - Adapted price
     */
    this.adaptNumbersInPrice = function(price) {
        if (!price) {
            return 0;
        }

        if (typeof $.convertPrice == 'function') {
            return price;
        }

        price         = parseInt(price);
        var price_val = price.toString();
        var separator = Math.floor(price_val.length/3);

        separator -= price_val.length % 3 == 0 ? 1 : 0;

        if (separator > 0) {
            var sign, short;

            if (separator == 1) {
                sign = lang['booking_short_price_k'];
                short = price / 1000;
            } else if (separator == 2) {
                sign = lang['booking_short_price_m'];
                short = price / 1000000;
            } else if (separator > 2) {
                sign = lang['booking_short_price_b'];
                short = price / 1000000000;
            }

            if (sign) {
                short = Math.round(short * 10)/10;
                price = short + sign;
            }
        }

        return price;
    };

    /**
     * Get title of day by current localization
     *
     * @since 3.2.0
     *
     * @param bindCheckin
     * @param weekDays
     * @returns {string}
     */
    this.getBindDaysByLocale = function (bindCheckin, weekDays) {
        bindCheckin = bindCheckin.split(',');

        if (!bindCheckin) {
            return '';
        }

        let bindLocale = '';

        for (let dayIndex in bindCheckin) {
            if (!bindCheckin.hasOwnProperty(dayIndex)) {
                continue;
            }

            switch (bindCheckin[dayIndex]) {
                case 'Mon':
                    bindLocale += (bindLocale ? ', ' : '') + weekDays[1];
                    break;
                case 'Tue':
                    bindLocale += (bindLocale ? ', ' : '') + weekDays[2];
                    break;
                case 'Wed':
                    bindLocale += (bindLocale ? ', ' : '') + weekDays[3];
                    break;
                case 'Thu':
                    bindLocale += (bindLocale ? ', ' : '') + weekDays[4];
                    break;
                case 'Fri':
                    bindLocale += (bindLocale ? ', ' : '') + weekDays[5];
                    break;
                case 'Sat':
                    bindLocale += (bindLocale ? ', ' : '') + weekDays[6];
                    break;
                case 'Sun':
                    bindLocale += (bindLocale ? ', ' : '') + weekDays[7];
                    break;
            }
        }

        return bindLocale;
    }

    /**
     * Load all external JS libraries (momentJS etc.) asynchronously
     * @since 3.3.0
     * @param {function} callback
     */
    this.loadExternalScripts = function (callback) {
        flUtil.loadScript(rlConfig.plugins_url + 'booking/static/moment.min.js', function () {
            flUtil.loadScript(rlConfig.plugins_url + 'booking/static/moment-timezone-with-data.min.js', function () {
                if (callback && typeof callback === 'function') {
                    callback.call(this);
                }
            });
        });
    }

    /**
     * Get count of days between 2 timestamps in days
     * @param {timestamp} date1
     * @param {timestamp} date2
     * @return {number}
     */
    this.getCountDaysBetween2Timestamps = function(date1, date2) {
        if (!date1 || !date2) {
            return 0;
        }

        date1 = moment.tz(new Date(date1 * 1000), bookingConfigs.bookingTimeZone);
        date2 = moment.tz(new Date(date2 * 1000), bookingConfigs.bookingTimeZone);
        return Math.abs(date1.diff(date2, 'days'));
    }

    /**
     * Add count of days to date in timestamp format
     * @since 3.3.0
     * @param {timestamp} timestamp
     * @param {number} days
     * @return {timestamp}
     */
    this.addDaysToTimestamp = function(timestamp, days = 1) {
        return self.modifyTimestamp(timestamp, days, '+');
    }

    /**
     * Subtract count of days to date in timestamp format
     * @since 3.3.0
     * @param {timestamp} timestamp
     * @param {number} days
     * @return {timestamp}
     */
    this.subtractDaysFromTimestamp = function(timestamp, days = 1) {
        return self.modifyTimestamp(timestamp, days, '-');
    }

    /**
     * Add/subtract count of days to date in timestamp format
     * @since 3.3.0
     * @param {timestamp} timestamp
     * @param {number} days
     * @param {string} action
     * @return {timestamp}
     */
    this.modifyTimestamp = function(timestamp, days, action = '+') {
        if (!timestamp) {
            return timestamp;
        }

        if (action === '+') {
            return moment.tz(new Date(timestamp * 1000), bookingConfigs.bookingTimeZone).add(days, 'days').unix();
        } else {
            return moment.tz(new Date(timestamp * 1000), bookingConfigs.bookingTimeZone).subtract(days, 'days').unix();
        }
    }

    /**
     * Convert amount & prepayment in user currency via converter
     * @since 3.4.0
     */
    this.conversionAmountHandler = function() {
        if (typeof $.convertPrice != 'function') {
            return;
        }

        // Add a timeout to prevent amount conversion before other handlers have finished
        let $amountContainer      = $('#booking_message,#booking_details').find('.value.amount,.field.amount'),
            $downpaymentContainer = $('.table-cell .value.downpayment'),
            $convertedPrice;

        if (!$amountContainer.length) {
            return;
        }

        /**
         * Create a new temporary container for the price conversion.
         * Because the converter cannot convert price multiple times in the same static container
         */
        if ($amountContainer.text() != lang.not_available) {
            $convertedPrice = $('<div>').text($amountContainer.text());
            $convertedPrice.convertPrice({showCents: true});

            if ($convertedPrice.find('.converted-price').text()) {
                $amountContainer.text($convertedPrice.find('.converted-price').text());
            } else {
                return;
            }
        }

        if (bookingConfigs.prepayment && $downpaymentContainer.text() != lang.not_available) {
            $convertedPrice = $('<div>').text($downpaymentContainer.text());
            $convertedPrice.convertPrice({showCents: true});
            $downpaymentContainer.text($convertedPrice.find('.converted-price').text());

            let systemCurrency    = bookingConfigs.system_currency_code;
            let defaultCurrencies = {'USD': 'dollar', 'EUR': 'euro', 'GBP': 'pound'};
            systemCurrency        = defaultCurrencies[systemCurrency] ? defaultCurrencies[systemCurrency] : systemCurrency;

            // Return if the converter doesn't have rate of the system currency
            if (!currencyConverter || !currencyConverter.rates || !currencyConverter.rates[systemCurrency]) {
                return;
            }

            // Remove the old amount in system currency, if it's there
            if ($('.value.amount-system-currency').length) {
                $('.value.amount-system-currency').remove();
            }

            // Add total amount in the system currency
            let $amountInSystemCurrency = $('<div>', {class: 'd-inline-block ml-2 value amount-system-currency'});
            $amountInSystemCurrency.text($amountContainer.text());
            $amountInSystemCurrency.convertPrice({currencyKey: systemCurrency});

            if ($amountInSystemCurrency.find('.converted-price').length && $amountInSystemCurrency.find('.converted-price').text()) {
                $amountInSystemCurrency.find('.converted-price').text('(' + $amountInSystemCurrency.find('.converted-price').text() + ')')
                $amountContainer.addClass('d-inline-block');
                $amountContainer.after($amountInSystemCurrency);
            }

            // Remove the old prepayment in system currency, if it's there
            if ($('.value.prepayment-system-currency').length) {
                $('.value.prepayment-system-currency').remove();
            }

            // Add prepayment amount in system currency
            let $prepaymentInSystemCurrency = $('<div>', {class: 'd-inline-block ml-2 value prepayment-system-currency'});
            $prepaymentInSystemCurrency.text($downpaymentContainer.text());
            $prepaymentInSystemCurrency.convertPrice({currencyKey: systemCurrency});

            if ($prepaymentInSystemCurrency.find('.converted-price').length && $prepaymentInSystemCurrency.find('.converted-price').text()) {
                $prepaymentInSystemCurrency.find('.converted-price').text('(' + $prepaymentInSystemCurrency.find('.converted-price').text() + ')')
                $downpaymentContainer.addClass('d-inline-block');
                $downpaymentContainer.after($prepaymentInSystemCurrency);
            }
        }
    }

    /**
     * Initialization process of booking section (add/edit listing)
     */
    this.init = function() {
        self.rateParentTag       = bookingConfigs && bookingConfigs.isAdmin ? 'tr' : 'div';
        self.rateChildTag        = bookingConfigs && bookingConfigs.isAdmin ? 'td' : 'div';
        self.$checkInSection     = $('#booking_checkin_field');
        self.$checkOutSection    = $('#booking_checkout_field');
        self.$bookingRadioButton = $('[name="f[booking_module]"]');
        self.$bookingSection     = self.$bookingRadioButton.parent().parent().parent();

        var $rates     = $('.rates-field-container .rates');
        self.rateIndex = $rates.length ? $rates.length : 1;

        self.bookingSectionHandler();
    }
};

var booking = new bookingClass();

function in_array(needle,haystack,argStrict){var key='',strict=!!argStrict;if(strict){for(key in haystack){if(haystack[key]===needle){return true;}}}else{for(key in haystack){if(haystack[key]==needle){return true;}}}return false;}
