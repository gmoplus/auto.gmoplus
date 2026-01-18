<!-- "Check Availability" field JS handler tpl -->

<script class="fl-js-dynamic">
lang.booking_checkin_property  = '{$lang.booking_checkin}';
lang.booking_checkin_auto      = '{$lang.booking_checkin_auto}';
lang.booking_checkin_escort    = '{$lang.booking_checkin_escort}';
lang.booking_checkout_property = '{$lang.booking_checkout}';
lang.booking_checkout_auto     = '{$lang.booking_checkout_auto}';
lang.booking_checkout_escort   = '{$lang.booking_checkout_escort}';

var booking_types = [];
{foreach from=$listing_types item='b_listing_type'}
    {if $b_listing_type.Booking_type != 'none'}
        booking_types['{$b_listing_type.Key}'] = '{$b_listing_type.Booking_type}';
    {/if}
{/foreach}

{literal}
$(function() {
    $datesFields = $('[name="f[check_availability][from]"], [name="f[check_availability][to]"]');

    flUtil.loadStyle(rlConfig.tpl_base + 'css/jquery.ui.css');
    flUtil.loadScript(rlConfig.libs_url + 'jquery/jquery.ui.js', function() {
        flUtil.loadScript(
            rlConfig.libs_url + 'jquery/datePicker/i18n/ui.datepicker-' + (rlLang ? rlLang : 'en') + '.js',
            function() {
                var booking_counter = 1;
                $('[name="f[check_availability][from]"]').each(function () {
                    $(this).attr('id', 'date_check_availability_from_' + booking_counter);
                    booking_counter++;
                });

                booking_counter = 1;
                $('[name="f[check_availability][to]"]').each(function () {
                    $(this).attr('id', 'date_check_availability_to_' + booking_counter);
                    booking_counter++;
                });

                // set default values for "from" and "to" fields
                $('[name*="check_availability"]')
                    .datepicker('destroy')
                    .datepicker({
                        showOn     : 'focus',
                        dateFormat : 'dd-mm-yy',
                        minDate    : new Date(),
                        changeMonth: true,
                        changeYear : true,
                        yearRange  : '-100:+30',
                        onSelect   : function (selectedDate) {
                            if (this.id.indexOf('from') > 0) {
                                var id = parseInt(this.id.split('_')[4]);
                                var $toField = $('#date_check_availability_to_' + id);

                                if ($toField.length) {
                                    var instance = $(this).data('datepicker'),
                                        date = $.datepicker.parseDate(instance.settings.dateFormat
                                            || $.datepicker._defaults.dateFormat, selectedDate, instance.settings);
                                    date.setDate(date.getDate() + 1);
                                    $toField.datepicker('option', 'minDate', date);

                                    var mMonth = date.getMonth() + 1;
                                    var mDay = date.getDate();
                                    mMonth = mMonth < 10 ? '0' + mMonth : mMonth;
                                    mDay = mDay < 10 ? '0' + mDay : mDay;
                                    $toField.val(mDay + '-' + mMonth + '-' + date.getFullYear());
                                }
                            }
                        }
                    })
                    .datepicker($.datepicker.regional[rlLang == 'en' ? 'en-GB' : rlLang]);

                // update min date for Refine Search boxes
                $('[name="f[check_availability][to]"]').each(function () {
                    var id = this.id && parseInt(this.id.split('_')[4]) ? parseInt(this.id.split('_')[4]) : 0;
                    var from = $('#date_check_availability_from_' + id).val();

                    if (from) {
                        var instance = $(this).data('datepicker'),
                            date = $.datepicker.parseDate(instance.settings.dateFormat
                                || $.datepicker._defaults.dateFormat, from, instance.settings);
                        date.setDate(date.getDate() + 1);

                        if (date) {
                            $(this).datepicker('option', 'minDate', date);
                        }
                    }
                });
            }
        )
    });

    // replace phrases in placeholders by booking type
    $datesFields.each(function(){
        var form_key = $(this).closest('form').find('[name="post_form_key"]').val();

        if (booking_types && form_key) {
            for (var type in booking_types) {
                if (form_key.indexOf(type) == 0) {
                    var checkin  = lang['booking_checkin_property'];
                    var checkout = lang['booking_checkout_property'];

                    switch (booking_types[type]) {
                      case 'date_time_range':
                        checkin  = lang['booking_checkin_auto'];
                        checkout = lang['booking_checkout_auto'];
                        break;

                      case 'time_range':
                        checkin  = lang['booking_checkin_escort'];
                        checkout = lang['booking_checkout_escort'];
                        break;
                    }

                    $(this).attr('placeholder', this.id.indexOf('from') > 0 ? checkin : checkout);
                }
            }
        }
    });
});
{/literal}</script>

<!-- "Check Availability" field JS handler tpl end -->
