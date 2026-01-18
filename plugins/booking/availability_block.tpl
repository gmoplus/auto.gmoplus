<!-- availability block tpl -->

<section class="side_block_search">{strip}
    <form id="booking-form" class="booking-availability" method="post" action="#">
        <div class="search-item single-field">
            <div class="field"></div>

            <select id="booking_listing_type" name="booking_listing_type">
                <option value="" disabled selected>{$lang.listing_type}</option>

                {if $listing_types}
                    {foreach from=$listing_types item='booking_ltype'}
                        {if $booking_ltype.Key && $booking_ltype.Booking_type != 'none'}
                            <option value="{$booking_ltype.Key}"
                                data-type="{$booking_ltype.Booking_type}"
                                data-url="{pageUrl key=$booking_ltype.Page_key add_url=$search_results_url}"
                            >
                                {$booking_ltype.name}
                            </option>
                        {/if}
                    {/foreach}
                {/if}
            </select>
        </div>

        <div class="search-item two-fields">
            <input placeholder="{$lang.booking_checkin}"
                type="text"
                id="booking_check_in"
                name="f[check_availability][from]"
                maxlength="10"
                value="{$smarty.post.check_availability.from}"
                autocomplete="off"
            />
            <input placeholder="{$lang.booking_checkout}"
                type="text"
                id="booking_check_out"
                name="f[check_availability][to]"
                maxlength="10"
                value="{$smarty.post.check_availability.to}"
                autocomplete="off"
            />
        </div>

        <div class="search-button">
            <input class="button" type="submit" value="{$lang.search}" />
        </div>
    </form>
</section>{/strip}

<script class="fl-js-dynamic">
lang.search                  = '{$lang.search}';
lang.notice_field_empty      = '{$lang.notice_field_empty}';
lang.listing_type            = '{$lang.listing_type}';
lang.booking_checkin         = '{$lang.booking_checkin}';
lang.booking_checkin_auto    = '{$lang.booking_checkin_auto}';
lang.booking_checkin_escort  = '{$lang.booking_checkin_escort}';
lang.booking_checkout        = '{$lang.booking_checkout}';
lang.booking_checkout_auto   = '{$lang.booking_checkout_auto}';
lang.booking_checkout_escort = '{$lang.booking_checkout_escort}';

{literal}
var bookingAvailabilityBoxClass = function() {
    let self           = this,
        $checkInField  = $('#booking_check_in'),
        $checkOutField = $('#booking_check_out'),
        $listingType   = $('#booking_listing_type'),
        $searchForm    = $('#booking-form');

    this.submitFormHandler = function() {
        $searchForm.on('submit', function(event){
            var $submitButton = $(this).find('[type="submit"]');
            $submitButton.val(lang['loading']).addClass('disabled').attr('disabled', 'disabled');

            // check values in form
            if (!$checkInField.val() || !$checkOutField.val() || !$listingType.val()) {
                event.preventDefault();
                var error = '';

                if (!$checkInField.val() || !$checkOutField.val()) {
                    error = '{/literal}{$lang.booking_availability_error}{literal}';
                }

                if (!$listingType.val()) {
                    if (error) {
                        var errors = '<ul><li>' + lang['notice_field_empty'].replace('{field}', lang['listing_type']);
                        errors += '</li><li>' + error + '</li></ul>';

                        error = errors;
                    } else {
                        error = lang['notice_field_empty'].replace('{field}', lang['listing_type']);
                    }
                }

                printMessage('error', error);

                self.highlightFields();
                $submitButton.val(lang['search']).removeClass('disabled').removeAttr('disabled');
                return false;
            } else {
                $(this).attr('action', $listingType.find('option:selected').data('url'));
            }
        });

        return true;
    }

    // highlight empty fields
    this.highlightFields = function() {
        if (!$checkInField.val()) {
            $checkInField.addClass('error');
            $checkInField.unbind('click').click(function() { $(this).removeClass('error'); });
        }

        if (!$checkOutField.val()) {
            $checkOutField.addClass('error');
            $checkOutField.unbind('click').click(function() { $(this).removeClass('error'); });
        }

        if (!$listingType.val()) {
            $listingType.addClass('error');
            $listingType.unbind('click').click(function() { $(this).removeClass('error'); });
        }
    }

    /**
     * Replace phrases in placeholders by booking type
     */
    this.placeholderHandler = function() {
        $listingType.change(function(){
            var type     = $(this).find('option:selected').data('type');
            var checkin  = lang['booking_checkin'];
            var checkout = lang['booking_checkout'];

            if ($(this).val() && type) {
                switch (type) {
                  case 'date_time_range':
                    checkin  = lang['booking_checkin_auto'];
                    checkout = lang['booking_checkout_auto'];
                    break;

                  case 'time_range':
                    checkin  = lang['booking_checkin_escort'];
                    checkout = lang['booking_checkout_escort'];
                    break;
                }
            }

            $checkInField.attr('placeholder', checkin);
            $checkOutField.attr('placeholder', checkout);
        });
    }

    this.init = function() {
        flUtil.loadStyle(rlConfig.tpl_base + 'css/jquery.ui.css');
        flUtil.loadScript(rlConfig.libs_url + 'jquery/jquery.ui.js', function() {
            flUtil.loadScript(
                rlConfig.libs_url + 'jquery/datePicker/i18n/ui.datepicker-' + (rlLang ? rlLang : 'en') + '.js',
                function() {
                    var dp_regional = rlLang === 'en' ? 'en-GB' : rlLang;
                    var dates = $("#booking_check_in, #booking_check_out").datepicker({
                        showOn     : 'focus',
                        dateFormat : 'dd-mm-yy',
                        minDate    : new Date(),
                        changeMonth: true,
                        changeYear : true,
                        yearRange  : '-100:+30',
                        onSelect   : function(selectedDate) {
                            if (this.id === 'booking_check_in') {
                                var instance = $(this).data('datepicker'),
                                date = $.datepicker.parseDate(instance.settings.dateFormat
                                    || $.datepicker._defaults.dateFormat, selectedDate, instance.settings);
                                date.setDate(date.getDate() + 1);
                                dates.not(this).datepicker('option', 'minDate', date);

                                var mMonth = date.getMonth() + 1;
                                var mDay = date.getDate();

                                mMonth = mMonth < 10 ? '0' + mMonth : mMonth;
                                mDay = mDay < 10 ? '0' + mDay : mDay;

                                $checkOutField.val(mDay + '-' + mMonth + '-' + date.getFullYear());
                            }
                        }
                    }).datepicker($.datepicker.regional[dp_regional]);
                }
            );
        });

        self.submitFormHandler();
        self.placeholderHandler();
    }
}

var bookingAvailabilityBox = new bookingAvailabilityBoxClass();

$(function() {
    bookingAvailabilityBox.init();
});
{/literal}</script>

<!-- availability block tpl end -->
