{if $fieldsetEnable && !$step}
    {include file='blocks/fieldset_header.tpl' id='calendar_fieldset' name=$lang.booking_calendar}
{/if}

<div class="booking_notices {if $step}hide{/if}">
    <ul>
        <li class="{if $bookingConfigs.Deny_guest && !$isLogin}active{else}hide{/if} static">
            {$lang.booking_deny_guests}
        </li>

        {if $bookingConfigs.Booking_type != 'time_range'}
            <li class="static{if $bookingConfigs.Min_book_day && $bookingConfigs.Min_book_day > 1} active{else} hide{/if}">
                {assign var='minNights' value=`$smarty.ldelim`nights`$smarty.rdelim`}
                {$lang.booking_min_booking|replace:$minNights:$bookingConfigs.Min_book_day}
            </li>
            <li class="static{if $bookingConfigs.Max_book_day} active{else} hide{/if}">
                {assign var='maxNights' value=`$smarty.ldelim`nights`$smarty.rdelim`}
                {$lang.booking_max_booking|replace:$maxNights:$bookingConfigs.Max_book_day}
            </li>
        {/if}
    </ul>
</div>

<div id="booking_calendar" class="{if $fieldsetEnable}in-fieldset{/if}{if $step} hide{/if}">{$lang.loading}</div>

{if $fieldsetEnable && !$step}
    {include file='blocks/fieldset_footer.tpl'}
{/if}
