<!-- booking configs tpl -->

<form id="configs_form" method="post">
    <div class="submit-cell clearfix">
        <div class="name">
            {$lang.booking_deny_guest}
            <img class="qtip" alt="" title="{$lang.booking_deny_guests_option_disabled}" src="{$rlTplBase}img/blank.gif" />
        </div>
        <div class="field checkbox-field inline-fields">
            {if $booking_configs.Deny_guest && $booking_configs.Prepayment}
                <input type="hidden" value="1" name="deny_guest" />
            {/if}

            <span class="custom-input">
                <label title="{$lang.yes}">
                    <input {if $booking_configs.Deny_guest}checked="checked"{/if} type="radio" value="1" name="deny_guest" {if $booking_configs.Deny_guest && $booking_configs.Prepayment}class="disabled" disabled="disabled"{/if} />
                    {$lang.yes}
                </label>
            </span>

            <span class="custom-input">
                <label title="{$lang.no}">
                    <input {if !$booking_configs.Deny_guest}checked="checked"{/if} type="radio" value="0" name="deny_guest" {if $booking_configs.Deny_guest && $booking_configs.Prepayment}class="disabled" disabled="disabled"{/if} />
                    {$lang.no}
                </label>
            </span>
        </div>
    </div>

    <div class="submit-cell clearfix {if $booking_configs.Booking_type == 'time_range'}hide{/if}">
        <div class="name">
            {$lang.booking_min_book_day}
            <img class="qtip" alt="" title="{$lang.booking_min_config_desc}" src="{$rlTplBase}img/blank.gif" />
        </div>
        <div class="field single-field">
            <input type="text" name="min_book_day" size="2" class="wauto numeric" maxlength="2" value="{if $booking_configs.Min_book_day}{$booking_configs.Min_book_day}{else}2{/if}" />
        </div>
    </div>

    <div class="submit-cell clearfix {if $booking_configs.Booking_type == 'time_range'}hide{/if}">
        <div class="name">
            {$lang.booking_max_book_day}
            <img class="qtip" alt="" title="{$lang.booking_max_config_desc}" src="{$rlTplBase}img/blank.gif" />
        </div>
        <div class="field single-field">
            <input type="text" name="max_book_day" size="2" class="wauto numeric" maxlength="2" value="{if $booking_configs.Max_book_day}{$booking_configs.Max_book_day}{else}0{/if}" />
        </div>
    </div>

    <div class="submit-cell clearfix {if $booking_configs.Booking_type == 'time_range'}hide{/if}">
        <div class="name">
            {$lang.booking_fixed_range}
            <img class="qtip" alt="" title="{$lang.booking_fixed_range_config_desc}" src="{$rlTplBase}img/blank.gif" />
        </div>
        <div class="field checkbox-field inline-fields">
            <span class="custom-input">
                <label title="{$lang.yes}">
                    <input {if $booking_configs.Fixed_rate_range}checked="checked"{/if} type="radio" value="1" name="fixed_rate_range" />
                    {$lang.yes}
                </label>
            </span>

            <span class="custom-input">
                <label title="{$lang.no}">
                    <input {if !$booking_configs.Fixed_rate_range}checked="checked"{/if} type="radio" value="0" name="fixed_rate_range" />
                    {$lang.no}
                </label>
            </span>
        </div>
    </div>

    <div class="submit-cell clearfix">
        {assign var='replace_from' value=`$smarty.ldelim`from`$smarty.rdelim`}
        {assign var='replace_to' value=`$smarty.ldelim`to`$smarty.rdelim`}
        {assign var='active_from' value=$listing_data.Pay_date|date_format:$smarty.const.RL_DATE_FORMAT}
        {assign var='active_to' value=$listing_data.Plan_expire|date_format:$smarty.const.RL_DATE_FORMAT}

        <div class="name">
            {$lang.booking_calendar_restricted}
            <img class="qtip" alt="" title="{$lang.booking_calendar_restricted_config_desc|replace:$replace_from:$active_from|replace:$replace_to:$active_to}" src="{$rlTplBase}img/blank.gif" />
        </div>

        <div class="field checkbox-field inline-fields">
            <span class="custom-input">
                <label title="{$lang.yes}">
                    <input {if $booking_configs.Calendar_restricted}checked="checked"{/if} type="radio" value="1" name="calendar_restricted" />
                    {$lang.yes}
                </label>
            </span>

            <span class="custom-input">
                <label title="{$lang.no}">
                    <input {if !$booking_configs.Calendar_restricted}checked="checked"{/if} type="radio" value="0" name="calendar_restricted" />
                    {$lang.no}
                </label>
            </span>
        </div>
    </div>

    <div class="submit-cell clearfix{if $booking_configs.Booking_type === 'time_range'} hide{/if}">
        <div class="name">
            {$lang.booking_hide_booked_days}
            <img class="qtip" alt="" title="{$lang.booking_hide_booked_days_desc}" src="{$rlTplBase}img/blank.gif" />
        </div>
        <div class="field checkbox-field inline-fields">
            <span class="custom-input">
                <label title="{$lang.yes}">
                    <input {if $booking_configs.Hide_booked_days}checked="checked"{/if} type="radio" value="1" name="hide_booked_days" />
                    {$lang.yes}
                </label>
            </span>

            <span class="custom-input">
                <label title="{$lang.no}">
                    <input {if !$booking_configs.Hide_booked_days}checked="checked"{/if} type="radio" value="0" name="hide_booked_days" />
                    {$lang.no}
                </label>
            </span>
        </div>
    </div>

    <div class="submit-cell clearfix">
        <div class="name"></div>
        <div class="field single-field">
            <input type="button" class="save_configs" value="{$lang.save}">
        </div>
    </div>
</form>

<script class="fl-js-static">flynax.qtip();</script>

<!-- booking configs tpl end -->
