<div id="booking_checkin_field" class="hide">
    <select name="f[booking_check_in]">
        <option value="0">{$lang.any}</option>
        {foreach from=$booking_time_range item='timeValue' key='timeKey'}
            <option {if $timeKey == $smarty.post.f.booking_check_in}selected{/if} value="{$timeKey}">
                {$timeValue}
            </option>
        {/foreach}
    </select>
</div>

<div id="booking_checkout_field" class="hide">
    <select name="f[booking_check_out]"
        {if ($bookingConfigs.Booking_type == 'time_range' || $smarty.const.IS_ESCORT === true) && !$edit_action}
            class="disabled" disabled="disabled"
        {/if}
    >
        <option value="0">{$lang.any}</option>
        {foreach from=$booking_time_range item='timeValue' key='timeKey'}
            <option {if $timeKey == $smarty.post.f.booking_check_out
                        || ($edit_action && $bookingConfigs.Booking_type == 'time_range'
                            && $timeValue == $bookingData.checkout)}selected{/if}
                value="{$timeKey}"
            >
                {$timeValue}
            </option>
        {/foreach}
    </select>
</div>
