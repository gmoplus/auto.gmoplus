</table>
{assign var='sPost' value=$sPost}
<!-- booking type properties -->

<table class="form">
    <tr>
        <td class="divider" colspan="3">
            <div class="inner">{$lang.booking_module}</div>
        </td>
    </tr>

    <tr>
        <td class="name">{$lang.booking_type}</td>
        <td class="field">
            <select name="Booking_type">
                <option value="none" {if $sPost.Booking_type == 'none'}selected="selected"{/if}>{$lang.booking_none}</option>
                <option value="date_range" {if $sPost.Booking_type == 'date_range'}selected="selected"{/if}>{$lang.booking_date_range}</option>
                <option value="date_time_range" {if $sPost.Booking_type == 'date_time_range'}selected="selected"{/if}>{$lang.booking_date_time_range}</option>
                <option value="time_range" {if $sPost.Booking_type == 'time_range'}selected="selected"{/if}>{$lang.booking_time_range}</option>
            </select>
        </td>
    </tr>

    <tr {if $sPost.Booking_type !== 'time_range' || $smarty.const.IS_ESCORT === true}class="hide"{/if}>
        <td class="name">{$lang.booking_repeatedly_service}</td>
        <td class="field">
            {if $sPost.Booking_repeatedly == '1'}
                {assign var='repeatedly_yes' value='checked'}
            {elseif $sPost.Booking_repeatedly == '0'}
                {assign var='repeatedly_no' value='checked'}
            {else}
                {assign var='repeatedly_yes' value='checked'}
            {/if}

            <label><input {$repeatedly_yes} type="radio" name="Booking_repeatedly" value="1" />&nbsp;{$lang.yes}</label>
            <label><input {$repeatedly_no} type="radio" name="Booking_repeatedly" value="0" />&nbsp;{$lang.no}</label>
        </td>
    </tr>

    <tr {if $sPost.Booking_type == 'time_range'}class="hide"{/if}>
        <td class="name">{$lang.booking_admin_price_field_devide_price}</td>
        <td class="field">
            <select name="Booking_price_field" {if $sPost.Booking_type == 'none'}class="disabled" disabled="disabled"{/if}>
                <option value="">{$lang.booking_none}</option>

                {foreach from=$price_fields item='field'}
                    <option value="{$field.Key}"{if $field.Key == $booking_price_field || $sPost.Booking_price_field == $field.Key} selected="selected"{/if}>{$field.name}</option>
                {/foreach}
            </select>

            <span class="field_description">{$lang.booking_price_field_hint}</span>
        </td>
    </tr>

    <tr {if $sPost.Booking_type == 'time_range'}class="hide"{/if}>
        <td class="name">{$lang.booking_rent_field}</td>
        <td class="field">
            <select name="Booking_rent_field" {if $sPost.Booking_type == 'none'}class="disabled" disabled="disabled"{/if}>
                <option value="">{$lang.booking_none}</option>

                {foreach from=$rent_fields item='field'}
                    <option value="{$field.Key}"{if $field.Key == $booking_rent_field || $sPost.Booking_rent_field == $field.Key} selected="selected"{/if}>{$field.name}</option>
                {/foreach}
            </select>

            <span class="field_description">{$lang.booking_rent_field_hint}</span>
        </td>
    </tr>

    {foreach from=$rent_fields item='field'}
        {assign var="rent_field_key" value='Booking_rent_field_value_'|cat:$field.Key}

        {if $sPost.$rent_field_key}
            {assign var="post_rent_field_key" value=$field.Key}
            {assign var="post_rent_field_value" value=$sPost.$rent_field_key}
            {break}
        {/if}
    {/foreach}

    <tr class="rent_field_values {if !$booking_rent_field && !$post_rent_field_value}hide{/if}">
        <td class="name">{$lang.booking_rent_field_value}</td>
        <td class="field">
            {foreach from=$rent_fields item='field'}
                <select name="Booking_rent_field_value_{$field.Key}" class="{if ($field.Key != $booking_rent_field || !$booking_rent_field) && ($field.Key != $post_rent_field_key)}hide{/if}">

                {if $field.Values}
                    <option value="">{$lang.booking_none}</option>

                    {foreach from=$field.Values item='field_value'}
                        <option value="{$field_value.Key}"{if $field_value.Key == $booking_rent_field_value || $post_rent_field_value == $field_value.Key} selected="selected"{/if}>{$lang[$field_value.pName]}</option>
                    {/foreach}
                {/if}
                </select>
            {/foreach}
        </td>
    </tr>

    <tr {if $sPost.Booking_type == 'time_range' && !$sPost.Booking_time_frame_field}class="hide"{/if}>
        <td class="name">{$lang.booking_time_frame_field}</td>
        <td class="field">
            <select name="Booking_time_frame_field" {if $sPost.Booking_type == 'none' && !$sPost.Booking_time_frame_field}class="disabled" disabled="disabled"{/if}>
                <option value="">{$lang.booking_none}</option>

                {foreach from=$rent_fields item='field'}
                    <option value="{$field.Key}"{if $field.Key == $booking_time_frame_field || $field.Key == $sPost.Booking_time_frame_field} selected="selected"{/if}>{$field.name}</option>
                {/foreach}
            </select>

            <span class="field_description">{$lang.booking_time_frame_field_hint}</span>
        </td>
    </tr>

    {if $sPost.Booking_time_frame_field}
        {assign var="post_rent_time_field" value=$sPost.Booking_time_frame_field}
        {assign var="post_rent_time_value" value='Booking_rent_field_value_'|cat:$sPost.Booking_time_frame_field}
        {assign var="post_rent_time_value" value=$sPost.$post_rent_time_value}
        {assign var="time_frame_field_values" value='Booking_time_frame_'|cat:$post_rent_time_field}
    {/if}

    <tr class="booking_rent_time_frame {if !$booking_time_frame_field && !$post_rent_time_field}hide{/if}">
        <td class="name">{$lang.booking_admin_rent_time_frame}</td>
        <td class="field">
            <table class="rent_time_frame_mapping">
                <tr>
                    <td>
                        {foreach from=$rent_fields item='time_frame_field'}
                            {if $time_frame_field.Values}
                                <select name="Booking_time_frame_{$time_frame_field.Key}[hour]" class="{if ($booking_time_frame_field != $time_frame_field.Key || !$booking_time_frame_field) && ($post_rent_time_field != $time_frame_field.Key)}hide{/if} {if $sPost.Booking_type != 'date_time_range'}disabled{/if}" {if $sPost.Booking_type != 'date_time_range'}disabled="disabled"{/if}>
                                    <option value="">{$lang.booking_none}</option>

                                    {foreach from=$time_frame_field.Values item='time_frame_value'}
                                        <option value="{$time_frame_value.Key}"{if ($time_frame_field.Key == $booking_time_frame_field && $time_frame_value.Key == $booking_time_frame_field_value.hour) || ($sPost.$time_frame_field_values.hour == $time_frame_value.Key)} selected="selected"{/if}>{$lang[$time_frame_value.pName]}</option>
                                    {/foreach}
                                </select>
                            {/if}
                        {/foreach}

                        <span class="field_description_noicon">{$lang.booking_admin_rent_time_frame_per_hour}</span>
                        <span class="field_description">{$lang.booking_rent_time_frame_per_day_hint}</span>
                    </td>
                </tr>

                <tr>
                    <td>
                        {foreach from=$rent_fields item='time_frame_field'}
                            {if $time_frame_field.Values}
                                <select name="Booking_time_frame_{$time_frame_field.Key}[day]" class="{if ($booking_time_frame_field != $time_frame_field.Key || !$booking_time_frame_field) && ($post_rent_time_field != $time_frame_field.Key)}hide{/if}">
                                    <option value="">{$lang.booking_none}</option>

                                    {foreach from=$time_frame_field.Values item='time_frame_value'}
                                        <option value="{$time_frame_value.Key}"{if ($time_frame_field.Key == $booking_time_frame_field && $time_frame_value.Key == $booking_time_frame_field_value.day) || ($sPost.$time_frame_field_values.day == $time_frame_value.Key)} selected="selected"{/if}>{$lang[$time_frame_value.pName]}</option>
                                    {/foreach}
                                </select>
                            {/if}
                        {/foreach}

                        <span class="field_description_noicon">{$lang.booking_admin_rent_time_frame_per_day}</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        {foreach from=$rent_fields item='time_frame_field'}
                            {if $time_frame_field.Values}
                                <select name="Booking_time_frame_{$time_frame_field.Key}[week]" class="{if ($booking_time_frame_field != $time_frame_field.Key || !$booking_time_frame_field) && ($post_rent_time_field != $time_frame_field.Key)}hide{/if}">
                                    <option value="">{$lang.booking_none}</option>

                                    {foreach from=$time_frame_field.Values item='time_frame_value'}
                                        <option value="{$time_frame_value.Key}"{if ($time_frame_field.Key == $booking_time_frame_field && $time_frame_value.Key == $booking_time_frame_field_value.week) || ($sPost.$time_frame_field_values.week == $time_frame_value.Key)} selected="seelcted"{/if}>{$lang[$time_frame_value.pName]}</option>
                                    {/foreach}
                                </select>
                            {/if}
                        {/foreach}

                        <span class="field_description_noicon">{$lang.booking_admin_rent_time_frame_per_week}</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        {foreach from=$rent_fields item='time_frame_field'}
                            {if $time_frame_field.Values}
                                <select name="Booking_time_frame_{$time_frame_field.Key}[month]" class="{if ($booking_time_frame_field != $time_frame_field.Key || !$booking_time_frame_field) && ($post_rent_time_field != $time_frame_field.Key)}hide{/if}">
                                    <option value="">{$lang.booking_none}</option>

                                    {foreach from=$time_frame_field.Values item='time_frame_value'}
                                        <option value="{$time_frame_value.Key}"{if ($time_frame_field.Key == $booking_time_frame_field && $time_frame_value.Key == $booking_time_frame_field_value.month) || ($sPost.$time_frame_field_values.month == $time_frame_value.Key)} selected="seelcted"{/if}>{$lang[$time_frame_value.pName]}</option>
                                    {/foreach}
                                </select>
                            {/if}
                        {/foreach}

                        <span class="field_description_noicon">{$lang.booking_admin_rent_time_frame_per_month}</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        {foreach from=$rent_fields item='time_frame_field'}
                            {if $time_frame_field.Values}
                                <select name="Booking_time_frame_{$time_frame_field.Key}[year]" class="{if ($booking_time_frame_field != $time_frame_field.Key || !$booking_time_frame_field) && ($post_rent_time_field != $time_frame_field.Key)}hide{/if}">
                                    <option value="">{$lang.booking_none}</option>

                                    {foreach from=$time_frame_field.Values item='time_frame_value'}
                                        <option value="{$time_frame_value.Key}"{if ($time_frame_field.Key == $booking_time_frame_field && $time_frame_value.Key == $booking_time_frame_field_value.year) || ($sPost.$time_frame_field_values.year == $time_frame_value.Key)} selected="seelcted"{/if}>{$lang[$time_frame_value.pName]}</option>
                                    {/foreach}
                                </select>
                            {/if}
                        {/foreach}

                        <span class="field_description_noicon">{$lang.booking_admin_rent_time_frame_per_year}</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td class="name">{$lang.booking_prepayment_type}</td>
        <td class="field">
            <select name="Booking_prepayment_type" {if $sPost.Booking_type == 'none'}class="disabled" disabled="disabled"{/if}>
                <option value="none" {if $sPost.Booking_prepayment_type == 'none'}selected="selected"{/if}>{$lang.booking_none}</option>
                <option value="for_admin" {if $sPost.Booking_prepayment_type == 'for_admin'}selected="selected"{/if}>{$lang.administrator}</option>
            </select>
        </td>
    </tr>

    <tr>
        <td class="name">{$lang.booking_prepayment_percent}</td>
        <td class="field">
            {if ($sPost.Booking_prepayment_type == 'none' || $sPost.Booking_type == 'none')
                || !$sPost}
                {assign var="booking_hide_percent" value=true}
            {/if}

            <input type="text" name="Booking_prepayment_percent" value="{if !$booking_hide_percent}{$sPost.Booking_prepayment_percent}{/if}" class="w60 numeric {if $booking_hide_percent}disabled{/if}" {if $booking_hide_percent}disabled="disabled"{/if} maxlength="5">
        </td>
    </tr>

<script>{literal}
$(function() {
    fieldBookingTypeHandler();
    fieldBookingPrepaymentHandler();
    fieldBookingRentHandler();
    fieldBookingTimeFrameHandler();
});

function fieldBookingTypeHandler() {
    let $bookingTypeField        = $('[name="Booking_type"]'),
        $rentFieldGroup          = $('tr.rent_field_values'),
        $rentTimeFrameFieldGroup = $('tr.booking_rent_time_frame'),
        $priceField              = $('[name="Booking_price_field"]'),
        $rentField               = $('[name="Booking_rent_field"]'),
        $timeFrameField          = $('[name="Booking_time_frame_field"]'),
        $prepaymentTypeField     = $('[name="Booking_prepayment_type"]'),
        $repeatedlyServiceField  = $('[name="Booking_repeatedly"]'),
        isEscort                 = {/literal}{if $smarty.const.IS_ESCORT === true}true{else}false{/if}{literal};

    $bookingTypeField.change(function(){
        if ($(this).val() === 'none') {
            $bookingTypeField.closest('table.form').find('input').val('');
            $bookingTypeField.closest('table.form').find('select option[value=""]').attr('selected', 'selected');
            $prepaymentTypeField.find('option[value="none"]').attr('selected', 'selected');
            $bookingTypeField.closest('table.form').find('input,select:not([name="Booking_type"])').addClass('disabled').attr('disabled', 'disabled');
            $bookingTypeField.next('span.field_description').fadeOut('fast');
            $rentFieldGroup.fadeOut('fast');
            $rentTimeFrameFieldGroup.fadeOut('fast');
        } else {
            if ($(this).val() === 'time_range') {
                $priceField.find('option[value=""]').attr('selected', 'selected');
                $priceField.closest('tr').fadeOut('fast');
                $rentField.find('option[value=""]').attr('selected', 'selected');
                $rentField.closest('tr').fadeOut('fast');
                $timeFrameField.find('option[value=""]').attr('selected', 'selected');
                $timeFrameField.closest('tr').fadeOut('fast');

                $prepaymentTypeField.find('option[value="none"]').attr('selected', 'selected');
                $prepaymentTypeField.removeClass('disabled').removeAttr('disabled');

                $bookingTypeField.next('span.field_description').fadeIn('slow');

                $rentFieldGroup.fadeOut('fast');
                $rentTimeFrameFieldGroup.fadeOut('fast');

                if (false === isEscort) {
                    $repeatedlyServiceField.closest('tr').fadeIn('slow');
                }
            } else {
                $timeFrameField.closest('tr').show();
                $priceField.closest('tr').show();
                $rentField.closest('tr').show();
                $bookingTypeField.closest('table.form').find('input:not([name="Booking_prepayment_percent"]),select:not([name="Booking_type"])').removeClass('disabled').removeAttr('disabled');
                $bookingTypeField.next('span.field_description').fadeOut('fast');
                $repeatedlyServiceField.closest('tr').fadeOut('fast');
            }

            // Disable "Per hour" option for all booking types except Auto
            if ($bookingTypeField.val() !== 'date_time_range') {
                $('.booking_rent_time_frame').find('[name*="[hour]"]').addClass('disabled').attr('disabled', 'disabled');
            }
        }
    });
}

function fieldBookingPrepaymentHandler() {
    var $prepayment_field = $('[name="Booking_prepayment_type"]');
    var $percent_field    = $('[name="Booking_prepayment_percent"]');

    $prepayment_field.change(function(){
        if ($(this).val() === 'none') {
            $percent_field.val('').addClass('disabled').attr('disabled', 'disabled');
        } else {
            $percent_field.removeClass('disabled').removeAttr('disabled');
        }
    });
}

function fieldBookingRentHandler() {
    var $rent_field = $('[name="Booking_rent_field"]');
    var $rent_field_values_tr = $('.rent_field_values');

    $rent_field.change(function(){
        var rent_field_val = $(this).val();

        if (rent_field_val) {
            $rent_field_values_tr.find('select').addClass('hide');
            $rent_field_values_tr.find('[name="Booking_rent_field_value_' + rent_field_val + '"]').removeClass('hide');
            $rent_field_values_tr.fadeIn('slow');
        } else {
            $rent_field_values_tr.fadeOut();
            $rent_field_values_tr.find('select option[value=""]').attr('selected', 'selected');
        }
    });
}

function fieldBookingTimeFrameHandler() {
    var $time_frame_field = $('[name="Booking_time_frame_field"]');
    var $time_frame_values_tr = $('.booking_rent_time_frame');

    $time_frame_field.change(function(){
        var time_frame_field_val = $(this).val();

        if (time_frame_field_val) {
            $time_frame_values_tr.find('select').addClass('hide');
            $time_frame_values_tr.find('[name*="Booking_time_frame_' + time_frame_field_val + '"]').removeClass('hide');
            $time_frame_values_tr.fadeIn('slow');

            // disable "Per hour" option for all booking types except Auto
            if ($('[name="Booking_type"]').val() !== 'date_time_range') {
                $time_frame_values_tr.find('[name="Booking_time_frame_' + time_frame_field_val + '[hour]"]').addClass('disabled').attr('disabled', 'disabled');
            }
        } else {
            $time_frame_values_tr.fadeOut();
            $time_frame_values_tr.find('select option[value=""]').attr('selected', 'selected');
        }
    });
}
{/literal}</script>

<style>{literal}
table.rent_time_frame_mapping {
    margin-top: 0 !important;
}
table.rent_time_frame_mapping tr:not(:nth-of-type(1)) td {
    padding-top: 10px !important;
}
{/literal}</style>

<!-- booking type properties end -->
