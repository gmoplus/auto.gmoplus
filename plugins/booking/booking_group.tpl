<!-- section of booking -->

{if $displayCalendar}
    <div id="area_booking" class="hide content-padding {if $config.booking_calendar_position == 'box'}box{/if}">
        {if $bookingConfigs.Booking_type == 'time_range' && $smarty.const.IS_ESCORT !== true}
            {include file=$bookingBlocksFolder|cat:'escort_data.tpl'}
        {/if}

        {include file=$bookingBlocksFolder|cat:'calendar.tpl'}
        {include file=$bookingBlocksFolder|cat:'message.tpl'}
        {include file=$bookingBlocksFolder|cat:'fields.tpl'}
    </div>

    {include file=$bookingBlocksFolder|cat:'styles.tpl'}
    {include file=$bookingBlocksFolder|cat:'js_script.tpl'}
{/if}

<!-- section of booking end -->
