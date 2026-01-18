{assign var='sReplace' value=`$smarty.ldelim`field`$smarty.rdelim`}
{assign var='missing_booking_field' value=$lang.notice_field_empty|replace:$sReplace:'error_field'}
{assign var='incorrect_booking_field' value=$lang.notice_field_incorrect|replace:$sReplace:'error_field'}
{assign var="phrase_hours" value='booking_escort_duration_'|cat:$request.To}

<script class="fl-js-dynamic">
    /* booking special configs */
    var bookingConfigs                       = [];
    bookingConfigs.booking_debug             = false;
    bookingConfigs.listing_id                = {if $listing_data.ID}{$listing_data.ID}{elseif $bookingData.listing_id}{$bookingData.listing_id}{else}0{/if};
    bookingConfigs.defPrice                  = '{$defPrice.value|escape:"javascript"}';
    bookingConfigs.defCurrency               = '{if $defPrice && $defPrice.currency}{$defPrice.currency}{else}{$config.system_currency}{/if}';
    bookingConfigs.min_bl                    = {if $bookingConfigs.Min_book_day}{$bookingConfigs.Min_book_day}{else}0{/if};
    bookingConfigs.max_bl                    = {if $bookingConfigs.Max_book_day}{$bookingConfigs.Max_book_day}{else}0{/if};
    bookingConfigs.fixed_range               = {if $bookingConfigs.Fixed_rate_range}{$bookingConfigs.Fixed_rate_range}{else}0{/if};
    bookingConfigs.Booking_type              = '{$bookingConfigs.Booking_type}';
    bookingConfigs.price_delimiter           = '{$config.price_delimiter|escape:"javascript"}';
    bookingConfigs.deny                      = {if $bookingConfigs.Deny_guest && !$isLogin}0{else}1{/if};
    bookingConfigs.day_prefix                = '#day_';
    bookingConfigs.bookingDateFormat         = '{if $smarty.const.RL_DATE_FORMAT}{$smarty.const.RL_DATE_FORMAT}{else}%b %d, %Y{/if}';
    bookingConfigs.bookingTimeZone           = '{if $config.timezone}{$config.timezone}{else}America/New_York{/if}';
    bookingConfigs.selected                  = [];
    bookingConfigs.usRange                   = [];
    bookingConfigs.closeRange                = [];
    bookingConfigs.usBook                    = [];
    bookingConfigs.total_cost                = '{if $bookingData.amount}{$bookingData.amount|regex_replace:'/[^0-9.,]+/':''}{elseif $bookingData.total_cost}{$bookingData.total_cost|regex_replace:'/[^0-9.,]+/':''}{else}0{/if}';
    bookingConfigs.total_days                = {if $bookingData.total_days}{$bookingData.total_days}{else}0{/if};
    bookingConfigs.cur_id                    = 0;
    bookingConfigs.s_id                      = 0;
    bookingConfigs.db_start                  = {if $bookingData && $bookingData.start_date}{$bookingData.start_date}{else}0{/if};
    bookingConfigs.start_date_value          = '{if $bookingData}{$bookingData.start_date_value}{/if}';
    bookingConfigs.db_end                    = {if $bookingData && $bookingData.end_date}{$bookingData.end_date}{else}0{/if};
    bookingConfigs.end_date_value            = '{if $bookingData}{$bookingData.end_date_value}{/if}';
    bookingConfigs.first                     = 0;
    bookingConfigs.index                     = 0;
    bookingConfigs.period_updated            = false;
    bookingConfigs.bind_checkin              = '';
    bookingConfigs.bind_checkout             = '';
    bookingConfigs.check_in                  = '{if $listing_data}{$listing_data.booking_check_in}{else}{$bookingData.checkin}{/if}';
    bookingConfigs.check_out                 = '{if $listing_data}{$listing_data.booking_check_out}{else}{$bookingData.checkout}{/if}';
    bookingConfigs.listing_check_in          = '{if $listing}{$listing.booking_check_in}{/if}';
    bookingConfigs.listing_check_out         = '{if $listing}{$listing.booking_check_out}{/if}';
    bookingConfigs.system_currency_position  = '{$config.system_currency_position}';
    bookingConfigs.system_currency_code      = '{$config.system_currency_code}';
    bookingConfigs.booking_calendar_position = '{$config.booking_calendar_position}';
    bookingConfigs.booking_calendar_box_side = '{$blocks.booking_calendar_box.Side}';
    bookingConfigs.booking_regular_per_hour  = {if $bookingConfigs.price_per_hour}{$bookingConfigs.price_per_hour}{else}0{/if};
    bookingConfigs.prepayment                = {if $bookingConfigs.Prepayment}{$bookingConfigs.Prepayment}{else}0{/if};
    bookingConfigs.edit_action               = {if $edit_action}true{else}false{/if};
    bookingConfigs.is_escort                 = {if $smarty.const.IS_ESCORT === true}true{else}false{/if};
    bookingConfigs.in_popup                  = false;
    bookingConfigs.escort_type_service       = '{if $bookingConfigs.Booking_type == 'time_range'}{$bookingData.checkin}{/if}';
    bookingConfigs.escort_type_service_title = '{if $listing_escort}{$listing_escort.escort_rates.Fields.escort_rates.value[$request.Checkin].title}{else}{$bookingRates[$request.Checkin].title}{/if}';
    bookingConfigs.escort_time_service       = '{if $bookingConfigs.Booking_type == 'time_range'}{$bookingData.checkout}{/if}';
    bookingConfigs.escortTimeServiceValue    = '{if $bookingConfigs.Booking_type == 'time_range'}{$bookingData.checkout}{/if}';
    bookingConfigs.escort_duration           = '{if $bookingConfigs.Booking_type == 'time_range'}{$bookingData.end_date}{/if}';
    bookingConfigs.escort_duration_title     = '{$lang.$phrase_hours}';
    bookingConfigs.site_template             = '{$config.template}';
    bookingConfigs.clock_system              = {$config.booking_clock_system|intval};
    bookingConfigs.first_day                 = '{$config.booking_first_day}';
    bookingConfigs.Booking_repeatedly        = {if $bookingConfigs.Booking_repeatedly}true{else}false{/if};

    /* additional checking of booking box calendar */
    {literal}
    if (bookingConfigs.booking_calendar_position === 'box' && $('section.booking > div').length === 0) {
        bookingConfigs.booking_calendar_position = 'listing';
    }
    {/literal}

    bookingConfigs.booking_order_page = "{pageUrl key='booking_order'}";

    lang.deny_text                     = '{$lang.booking_deny_guests}';
    lang.min_bl_text                   = '{$lang.booking_min_booking}';
    lang.max_bl_text                   = '{$lang.booking_max_booking}';
    lang.closed_day_text               = '{$lang.booking_day_closed}';
    lang.booked_day_text               = '{$lang.booking_day_booked}';
    lang.check_in_only_text            = "{if $bookingConfigs.Booking_type == 'time_range' && $smarty.const.IS_ESCORT === true}{$lang.booking_escort_check_in_only}{else}{$lang.booking_check_in_only}{/if}";
    lang.check_out_only_text           = '{$lang.booking_check_out_only}';
    lang.already_booked_text           = '{$lang.booking_already_booked}';
    lang.booking_checkin               = '{if $bookingConfigs.Booking_type == "date_time_range"}{$lang.booking_checkin_auto}{elseif $bookingConfigs.Booking_type == "time_range"}{$lang.booking_checkin_escort}{else}{$lang.booking_checkin}{/if}';
    lang.booking_checkout              = '{if $bookingConfigs.Booking_type == "date_time_range"}{$lang.booking_checkout_auto}{elseif $bookingConfigs.Booking_type == "time_range"}{$lang.booking_checkout_escort}{else}{$lang.booking_checkout}{/if}';
    lang.booking_amount                = '{$lang.total}';
    lang.booking_nights                = '{$lang.booking_nights}';
    lang.booking_prev                  = '{$lang.booking_prev}';
    lang.booking_next                  = '{$lang.booking_next}';
    lang.booking_month                 = '{$lang.booking_month}';
    lang.booking_monday                = '{$bookingWeekdays.short.1}';
    lang.booking_tuesday               = '{$bookingWeekdays.short.2}';
    lang.booking_wednesday             = '{$bookingWeekdays.short.3}';
    lang.booking_thursday              = '{$bookingWeekdays.short.4}';
    lang.booking_friday                = '{$bookingWeekdays.short.5}';
    lang.booking_saturday              = '{$bookingWeekdays.short.6}';
    lang.booking_sunday                = '{$bookingWeekdays.short.7}';
    lang.booking_start_booking_title   = '{$lang.booking_start_booking_title}';
    lang.booking_message_step_button   = '{$lang.booking_message_step_button}';
    lang.booking_field_error           = '{$missing_booking_field}';
    lang.booking_inccorect_field_error = '{$incorrect_booking_field}';
    lang.not_available                 = '{$lang.not_available}';
    lang.next_step                     = '{$lang.next_step}';
    lang.booking_escort_date           = '{$lang.booking_escort_date}';
    lang.booking_escort_time           = '{$lang.booking_escort_time}';
    lang.booking_escort_type           = '{$lang.booking_escort_type}';
    lang.booking_escort_duration       = '{$lang.booking_escort_duration}';
    lang.booking_button_select_date    = '{$lang.booking_button_select_date}';
    lang.time_midnight                 = '{$lang.time_midnight}';
    lang.time_noon                     = '{$lang.time_noon}';
    lang.booking_prepayment_step       = '{$lang.booking_prepayment_step}';
    lang.booking_time_next_day         = '{$lang.booking_time_next_day}';
    lang.booking_short_price_k         = '{$lang.booking_short_price_k}';
    lang.booking_short_price_m         = '{$lang.booking_short_price_m}';
    lang.booking_short_price_b         = '{$lang.booking_short_price_b}';
    lang.booking_rate_selector         = '{$lang.booking_rate_selector}';

    $('.booking_rates .table-cell').addClass('hide').css('display', 'none');

    {if $bookingConfigs.Booking_type == 'time_range' && $bookingAvailability}
        bookingConfigs.availability = [];
        {foreach from=$bookingAvailability item='av_day'}
            bookingConfigs.availability.day_{$av_day.day} = [
                '{if $av_day.originalFrom}{$av_day.originalFrom}{else}{$av_day.from}{/if}',
                '{if $av_day.originalTo}{$av_day.originalTo}{else}{$av_day.to}{/if}',
            ];
        {/foreach}
    {/if}

    {literal}
    $(function() {
        if (typeof booking === 'undefined') {
            return;
        }

        {/literal}{if $qtipEnable}{literal}
        booking.qtipInit();
        {/literal}{/if}{literal}

        if (bookingConfigs.booking_calendar_position === 'box') {
            $('.booking_rates').addClass('hide');
            $('#area_booking').fadeIn('slow');
        } else {
            $('#area_booking').insertAfter($('.booking_rates .table-cell:last')).fadeIn('slow');
        }

        {/literal}
        {if !$step}
            booking.getDates(bookingConfigs.listing_id);
        {elseif $step && $step == 'fields'}
            booking.bookingFieldsValidator();
        {/if}
        {literal}

        $('#step_2 .cancel').click(function() {
            booking.stepsHandler('all', 'hide');
        });

        /* move user to booking details */
        setTimeout(function(){
            if (window.location.hash === '#booking') {
                var $booking_container = bookingConfigs.booking_calendar_position === 'box'
                    ? $('aside .side_block.booking')
                    : $('.booking_rates');

                $('html,body').animate({scrollTop: $booking_container.offset().top - 100}, 'slow');
            }
        }, 800);

    });
{/literal}</script>
