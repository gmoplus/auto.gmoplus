<!-- list of rate ranges tpl -->

<div id="manage_item_dom" class="hide">
    <div class="manage-photo light-inputs {if $smarty.const.REALM == 'admin'}admin{/if}">
        <div class="two-inline">
            <div><input name="item-desc-save" type="button" value="{$lang.save}"></div>
            <div style="padding-{if $text_dir_rev}{$text_dir_rev}{else}right{/if}: 15px;">
                <input style="width: 100%;" placeholder="{$lang.description}" name="item-desc" type="text">
            </div>
        </div>
    </div>
</div>

{include file=$smarty.const.RL_PLUGINS|cat:'booking/smarty_blocks/checkin_checkout.tpl'}

<div class="hide" id="booking_rate_ranges_list">
    <div class="list-table content-padding">
        <div class="header">
            <div class="from">{$lang.from}</div>
            <div class="to">{$lang.to}</div>
            <div class="price">{$lang.price} {$lang.booking_price_notice}</div>
            <div class="desc">{$lang.description}</div>
            <div class="actions">{$lang.actions}</div>
        </div>

        {if $rate_ranges}
            {foreach from=$rate_ranges item='rate_range' name='postRateRanges'}
                {assign var="rate_index" value=$rate_range.ID}

                <div class="row" id="rrange_{$rate_index}">
                    <div data-caption="{$lang.from}">{$rate_range.From}</div>
                    <div data-caption="{$lang.to}">{$rate_range.To}</div>
                    <div data-caption="{$lang.price}">{if $rate_range.Price}{$rate_range.Price}{else}{$lang.booking_close_days}{/if}</div>
                    <div data-caption="{$lang.description}">
                        {assign var='qtip_e' value=" <br><hr><a class='booking_edit_desc' href='javascript:void(0)' onclick='booking.editDesc("|cat:$rate_index|cat:")'>"|cat:$lang.edit|cat:"</a>"}

                        <img class="booking_qtip" alt="" title="{if $rate_range.Desc}{$rate_range.Desc}{else}{$lang.not_available}{/if} {$qtip_e}" id="desc_ico_{$rate_index}" src="{$rlTplBase}img/blank.gif" />

                        <textarea class="hide" id="save_desc_{$rate_index}" cols="30" rows="2">{$rate_range.Desc}</textarea>
                    </div>
                    <div data-caption="{$lang.actions}">
                        <img class="remove remove_rate" id="{$rate_index}" title="{$lang.delete}" alt="{$lang.delete}" src="{$rlTplBase}img/blank.gif">
                    </div>
                </div>

                {if $smarty.foreach.postRateRanges.last}
                    {assign var="current_rate_index" value=$rate_range.ID}
                    {math equation="x + y" x=$current_rate_index y=1 assign="current_rate_index"}
                {/if}
            {/foreach}
        {else}
            {if $errors && $smarty.post.b}
                {assign var="post_rate_ranges" value=$smarty.post.b}

                {if $smarty.post.f[$config.price_tag_field].currency}
                    {assign var="currency_name" value='data_formats+name+'|cat:$smarty.post.f[$config.price_tag_field].currency}

                    {if $lang.$currency_name}
                        {assign var="lCurrency" value=$lang.$currency_name}
                    {else}
                        {assign var="lCurrency" value=$config.system_currency}
                    {/if}
                {/if}

                {foreach from=$post_rate_ranges item='rate_range' name='postRateRanges'}
                    {if $rate_range.from || $rate_range.to}
                        {assign var="rate_index" value=$smarty.foreach.postRateRanges.iteration}

                        <div class="row tmp" id="add_rate_{$rate_index}">
                            <div data-caption="{$lang.from}">
                                <input class="w120" type="text" name="b[{$rate_index}][from]" id="brr_from_{$rate_index}" value="{$rate_range.from|replace:'/':'-'}" />
                            </div>
                            <div data-caption="{$lang.to}">
                                <input class="w120" type="text" name="b[{$rate_index}][to]" id="brr_to_{$rate_index}" value="{$rate_range.to|replace:'/':'-'}" />
                            </div>
                            <div data-caption="{$lang.price}">
                                {if $config.system_currency_position == 'before'}{$lCurrency} {/if}<input type="text" class="numeric w120 price" name="b[{$rate_index}][price]" id="price_{$rate_index}" value="{$rate_range.price}" />{if $config.system_currency_position != 'before'} {$lCurrency}{/if}<img class="booking_qtip" alt="" title="{$lang.booking_close_rate_range_notice}" id="rate_desc_{$rate_index}" src="{$rlTplBase}img/blank.gif">
                            </div>
                            <div data-caption="{$lang.description}" class="rate-ranges-description">
                                <a href="javascript:void(0)" onclick="booking.addDesc({$rate_index})">{$lang.description}</a>
                                <textarea class="hide" id="save_desc_{$rate_index}" name="b[{$rate_index}][desc]" cols="30" rows="2">{$rate_range.desc}</textarea>
                            </div>
                            <div data-caption="{$lang.actions}" class="rate-ranges-actions">
                                <img class="remove" onclick="booking.removeRate({$rate_index})" title="{$lang.delete}" alt="{$lang.delete}" src="{$rlTplBase}img/blank.gif" />
                            </div>
                        </div>

                        {assign var="current_rate_index" value=$smarty.foreach.postRateRanges.iteration}
                        {math equation="x + y" x=$current_rate_index y=1 assign="current_rate_index"}
                    {/if}
                {/foreach}
            {/if}
        {/if}

        <div class="row" id="rrange_regular">
            <div data-caption="{$lang.from}">{$lang.booking_rate_price_per_day}</div>
            <div data-caption="{$lang.to}"></div>
            <div data-caption="{$lang.price}">
                {if $defPrice.name}
                    {$defPrice.name}
                {else}
                    {$lang.not_available}
                    <img class="booking_qtip" alt="" title="{$lang.booking_empty_regular}" src="{$rlTplBase}img/blank.gif">
                {/if}
            </div>

            <div data-caption="{$lang.description}">
                {if !$errors}
                    {assign var='qtip_e_regular' value=" <br><hr><a class='booking_edit_desc' href='javascript:void(0)' onclick='booking.editDesc()'>"|cat:$lang.edit|cat:"</a>"}

                    <img class="booking_qtip" alt="" title="{if !empty($range_regular_desc)}{$range_regular_desc}{else}{$lang.not_available}{/if} {$qtip_e_regular}" id="desc_ico_regular" src="{$rlTplBase}img/blank.gif" />

                    <textarea class="hide" id="save_desc_regular" cols="30" rows="2">{$range_regular_desc}</textarea>
                {else}
                    <a href="javascript:void(0)" onclick="booking.addDesc()">{$lang.description}</a>
                    <textarea class="hide" id="save_desc_regular" name="b[regular][desc] cols=" 30"="" rows="2">{$smarty.post.b.regular.desc}</textarea>
                {/if}
            </div>
            <div data-caption="{$lang.actions}">{$lang.not_available}</div>
        </div>
    </div>

    <div id="add_ranges_table">
        <a class="add_rate_range" href="javascript:void(0)">{$lang.booking_rate_add}</a>
    </div>
</div>

<div id="availability" class="submit-cell clearfix hide">
    <div class="name">{$lang.booking_availability}</div>
    <div class="field">
        <div class="availability-field-container">
            {section name=bookingAvailability start=1 loop=8}
                {assign var='availabilityPost' value=$smarty.post.f.booking_availability}
                {assign var='i' value=$smarty.section.bookingAvailability.index}

                {if $config.booking_first_day === 'sunday'}
                    {if $i === 1}
                        {assign var='i' value=7}
                    {else}
                        {assign var='i' value=$i-1}
                    {/if}
                {/if}

                <div>
                    <div class="day">{$bookingWeekdays.full.$i}</div>
                    <div class="av_from">
                        <select name="f[booking_availability][{$i}][from]" id="hourly-availability-from-{$i}">
                            <option value="0" {if !$smarty.post}selected{/if}>{$lang.not_available}</option>

                            {foreach from=$booking_time_range item='timeValue' key='timeKey'}
                                <option {if $timeKey == $availabilityPost.$i.from}selected{/if} value="{$timeKey}">
                                    {$timeValue}
                                </option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="av_to">
                        <select name="f[booking_availability][{$i}][to]" id="hourly-availability-to-{$i}">
                            <option value="0" {if !$smarty.post}selected{/if}>{$lang.not_available}</option>

                            {foreach from=$booking_time_range item='timeValue' key='timeKey'}
                                <option {if $timeKey == $availabilityPost.$i.to}selected{/if} value="{$timeKey}">
                                    {$timeValue}
                                </option>
                            {/foreach}
                        </select>
                    </div>
                </div>
            {/section}
        </div>
    </div>
</div>

<div id="rates" class="submit-cell clearfix hide">
    <div class="name">{$lang.booking_rates}</div>
    <div class="field">
        <div class="rates-field-container">
            {if $smarty.post.f.booking_rates && $smarty.post.f.booking_rates|@count >= 2}
                {assign var='ratesCount' value=$smarty.post.f.booking_rates|@count}
            {else}
                {assign var='ratesCount' value=1}
            {/if}

            {section name=bookingRates start=0 loop=$ratesCount}
                {assign var='i' value=$smarty.section.bookingRates.index}

                <div class="rates">{strip}
                    <input type="text"
                           name="f[booking_rates][{$i}][title]"
                           id="hourly-rates-name-{$i}"
                           placeholder="{$lang.booking_rate_title}"
                           value="{$smarty.post.f.booking_rates.$i.title}"
                    >
                    <select name="f[booking_rates][{$i}][time]"
                            id="hourly-rates-duration-{$i}"
                            class="hourly-rates-duration"
                    >
                        <option value="">{$lang.booking_rate_selector}</option>

                        {section name=bookingTime start=1 loop=25}
                            {assign var='hoursValue' value=''}
                            {assign var='timeValue' value=''}
                            {assign var='j' value=$smarty.section.bookingTime.index}

                            {if $j > 1}
                                {assign var='hoursValue' value=$j/2}

                                {if  $j % 2}
                                    {assign var='timeValue' value=$hoursValue|intval}
                                    {assign var='timeValue' value=$timeValue|cat:':30'}
                                {else}
                                    {assign var='timeValue' value=$hoursValue|cat:':00'}
                                {/if}

                                {if $hoursValue <= 9.5}
                                    {assign var='timeValue' value='0'|cat:$timeValue}
                                {/if}
                            {else}
                                {assign var='hoursValue' value='0.5'}
                                {assign var='timeValue' value='00:30'}
                            {/if}

                            <option value="{$hoursValue}"
                                {if $smarty.post.f.booking_rates.$i.time == $hoursValue}selected{/if}
                            >
                                {$timeValue}
                            </option>
                        {/section}
                    </select>
                    <input type="text"
                           name="f[booking_rates][{$i}][price]"
                           id="hourly-rates-price-{$i}"
                           placeholder="{$lang.price}"
                           class="numeric w120"
                           value="{$smarty.post.f.booking_rates.$i.price}"
                    >
                    <select name="f[booking_rates][{$i}][currency]" id="hourly-rate-currency-{$i}">
                        {foreach from='currency'|df item='currency'}
                            <option value="{$currency.Key}"
                                {if $smarty.post.f.booking_rates.$i.currency == $currency.Key}selected{/if}
                            >
                                {$currency.name}
                            </option>
                        {/foreach}
                    </select>

                    {if ($bookingConfigs && $bookingConfigs.Booking_repeatedly == '0')
                        || ($listing_type && $listing_type.Booking_repeatedly == '0')
                    }
                        <select name="f[booking_rates][{$i}][type]" id="hourly-rate-type-{$i}">
                            <option value="single" {if $smarty.post.f.booking_rates.$i.type == 'single'}selected{/if}>
                                {$lang.booking_single_rate}
                            </option>
                            <option value="multi"
                                    {if $smarty.post.f.booking_rates.$i.type == 'multi'
                                        || !$smarty.post.f.booking_rates.$i}selected{/if}
                            >
                                {$lang.booking_multi_rate}
                            </option>
                        </select>
                    {/if}

                    {if $i > 0}
                        <img src="{$rlTplBase}img/blank.gif"
                             alt="{$lang.delete}"
                             class="remove remove-hourly-rate"
                             title="{$lang.delete}"
                             onclick="
                                $(this).closest('.rates').fadeOut('fast', function(){ldelim}
                                    $(this).remove();
                                    booking.rateIndex--;
                                {rdelim});"
                        >
                    {/if}

                    <script class="fl-js-dynamic">booking.rateIndex = {$i};</script>
                {/strip}</div>
            {/section}

            <div class="new-rate-button">
                <a href="javascript://" onclick="booking.addNewHourlyRate();">{$lang.booking_addNewHourlyRate}</a>
            </div>
        </div>
    </div>
</div>

{if $listing_type.Key}
    {assign var="price_field_key" value='booking_price_field_'|cat:$listing_type.Key}
    {assign var="booking_price_field" value=$config.$price_field_key}

    {assign var="rent_field_config" value='booking_rent_field_'|cat:$listing_type.Key}
    {assign var="booking_rent_field_data" value=$config.$rent_field_config|unserialize}

    {if $booking_rent_field_data.0 && $booking_rent_field_data.1}
        {assign var="booking_rent_field" value=$booking_rent_field_data.0}
        {assign var="booking_rent_value" value=$booking_rent_field_data.1}
    {/if}

    {assign var="time_frame_field_config" value='booking_time_frame_field_'|cat:$listing_type.Key}
    {assign var="booking_time_frame_field_data" value=$config.$time_frame_field_config|unserialize}

    {if $booking_time_frame_field_data.0 && $booking_time_frame_field_data.1}
        {assign var="booking_time_frame_field" value=$booking_time_frame_field_data.0}
        {assign var="booking_time_frame_value" value=$booking_time_frame_field_data.1}
    {/if}
{/if}

{php} $GLOBALS['rlSmarty']->assign_by_ref('l_timezone', $GLOBALS['l_timezone']); {/php}
{if $l_timezone[$config.timezone] && $l_timezone[$config.timezone].0}
    {assign var="time_offset" value=$l_timezone[$config.timezone].0|replace:':00':''|replace:':30':'.5'}
{else}
    {assign var="time_offset" value='-5'}
{/if}

<script class="fl-js-dynamic">
lang.to                                     = '{$lang.to}';
lang.description                            = '{$lang.description}';
lang.from                                   = '{$lang.from}';
lang.edit                                   = '{$lang.edit}';
lang.price                                  = '{$lang.price}';
lang.cancel                                 = '{$lang.cancel}';
lang.actions                                = '{$lang.actions}';
lang.warning                                = '{$lang.warning}';
lang.booking_ok                             = '{$lang.booking_ok}';
lang.description                            = '{$lang.description}';
lang.not_available                          = '{$lang.not_available}';
lang.booking_checkin                        = '{if $bookingConfigs.Booking_type == "date_time_range"}{$lang.booking_checkin_auto}{elseif $bookingConfigs.Booking_type == "time_range"}{$lang.booking_checkin_escort}{else}{$lang.booking_checkin}{/if}';
lang.booking_checkout                       = '{if $bookingConfigs.Booking_type == "date_time_range"}{$lang.booking_checkout_auto}{elseif $bookingConfigs.Booking_type == "time_range"}{$lang.booking_checkout_escort}{else}{$lang.booking_checkout}{/if}';
lang.booking_rate_range                     = '{$lang.booking_rate_range}';
lang.booking_remove_confirm                 = '{$lang.booking_remove_confirm}';
lang.booking_rate_price_per_day             = '{$lang.booking_rate_price_per_day}';
lang.booking_close_rate_range_notice        = '{$lang.booking_close_rate_range_notice}';
lang.booking_addEditListingErrorEmptyRanges = '{$lang.booking_addEditListingErrorEmptyRanges}';
lang.booking_empty_regular                  = '{$lang.booking_empty_regular}';
lang.booking_close_days                     = '{$lang.booking_close_days}';
lang.booking_rate_title                     = '{$lang.booking_rate_title}';
lang.booking_emptyHourlyRanges              = '{$lang.booking_emptyHourlyRanges}';
lang.booking_rate_selector                  = '{$lang.booking_rate_selector}';
lang.booking_single_rate                    = '{$lang.booking_single_rate}';
lang.booking_multi_rate                     = '{$lang.booking_multi_rate}';
lang.add_listing                            = '{$lang.add_listing}';

var bookingConfigs                      = [];
bookingConfigs.system_currency_position = '{$config.system_currency_position}';
bookingConfigs.booking_price_field      = '{$booking_price_field}';
bookingConfigs.booking_rent_field       = '{$booking_rent_field}';
bookingConfigs.booking_rent_value       = '{$booking_rent_value}';
bookingConfigs.booking_time_frame_field = '{$booking_time_frame_field}';
bookingConfigs.booking_min_book_day     = {if $bookingConfigs.Min_book_day}{$bookingConfigs.Min_book_day}{else}2{/if};
bookingConfigs.booking_type             = '{if $bookingConfigs && $bookingConfigs.Booking_type}{$bookingConfigs.Booking_type}{elseif $listing_type && $listing_type.Booking_type}{$listing_type.Booking_type}{/if}';
bookingConfigs.booking_time_frame       = [];
bookingConfigs.bookingTimeZone          = '{if $config.timezone}{$config.timezone}{else}America/New_York{/if}';
bookingConfigs.bookingTimeOffset        = '{$time_offset}';
bookingConfigs.is_escort                = {if $smarty.const.IS_ESCORT === true}true{else}false{/if};
bookingConfigs.clock_system             = {$config.booking_clock_system|intval};
bookingConfigs.first_day                = '{$config.booking_first_day}';

{if $booking_time_frame_value}
    {foreach from=$booking_time_frame_value item='time_frame_item' key='time_frame_key'}
        {if $time_frame_item}
            bookingConfigs.booking_time_frame.{$time_frame_key} = '{$time_frame_item}';
        {/if}
    {/foreach}
{/if}

bookingConfigs.current_field     = {if $current_rate_index}{$current_rate_index}{else}1{/if};
bookingConfigs.listing_id        = '{if $smarty.get.id}{$smarty.get.id}{elseif $smarty.session.add_listing.listing_id}{$smarty.session.add_listing.listing_id}{/if}';
bookingConfigs.src_delete_img    = '{$rlTplBase}img/blank.gif';
bookingConfigs.isAdmin           = {if $smarty.const.REALM == 'admin'}true{else}false{/if};
bookingConfigs.rlLang            = '{$smarty.const.RL_LANG_CODE}';
bookingConfigs.rlController      = bookingConfigs.isAdmin
? "{if $smarty.get.action == 'add'}add_listing{else}edit_listing{/if}"
: rlPageInfo.controller;
bookingConfigs.post_rate_ranges  = {if $post_rate_ranges}true{else}false{/if};
bookingConfigs.booking_available = {if $booking_available}true{else}false{/if};
bookingConfigs.bookingRepeatedly = {if ($bookingConfigs && $bookingConfigs.Booking_repeatedly == '1') || ($listing_type && $listing_type.Booking_repeatedly == '1')}true{else}false{/if};

bookingConfigs.currencies = [];
{foreach from='currency'|df item='currency'}
    bookingConfigs.currencies.push({ldelim}key: '{$currency.Key}', value: '{$currency.name}'{rdelim});
{/foreach}

{literal}
$(function(){
    booking.init();
});
{/literal}
</script>

{if $smarty.const.REALM == 'admin'}
    <script src="{$smarty.const.RL_LIBS_URL}jquery/datePicker/i18n/ui.datepicker-{$smarty.const.RL_LANG_CODE|lower}.js"></script>
{/if}

<!-- list of rate ranges tpl end -->
