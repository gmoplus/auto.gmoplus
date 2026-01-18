<!-- Booking availability/rates/duration info -->

{if in_array($tpl_settings.name, array('general_simple_wide', 'general_modern_wide', 'auto_modern'))}
    {assign var='bookingDivClass' value='col-md-12'}
{else}
    {assign var='bookingDivClass' value='col-md-6'}
{/if}

<div class="row">
    {assign var='availabilityData' value=$bookingAvailability}

    {if $availabilityData|@count > 0}
        <div class="{$bookingDivClass}">
            <section class="side_block stick seller-short">
                <h3>
                    {if $listing.availability.Fields.availability.name}
                        {$listing.availability.Fields.availability.name}
                    {else}
                        {$lang.booking_availability}
                    {/if}
                </h3>
                <div class="clearfix">
                    <ul class="availability-chart{if $smarty.const.IS_ESCORT !== true} simple-hourly-mode{/if}">
                    {foreach from=$availabilityData item='availability_item'}
                        {if $availability_item.from != '0' && $availability_item.to != '0'}
                            <li class="clearfix">
                                <div>{$availability_item.title}</div>
                                <div>{$availability_item.from} - {$availability_item.to}</div>
                            </li>
                        {/if}
                    {/foreach}
                    </ul>
                </div>
            </section>
        </div>
    {/if}

    {if $bookingRates}
        {assign var='ratesData' value=$bookingRates}
    {else}
        {assign var='ratesData' value=$listing.escort_rates.Fields.escort_rates.value}
    {/if}

    {if $ratesData|@count > 0}
        <div class="{$bookingDivClass}">
            <section class="side_block stick seller-short">
                <h3>
                    {if $listing.escort_rates.Fields.escort_rates.name}
                        {$listing.escort_rates.Fields.escort_rates.name}
                    {else}
                        {$lang.booking_rates}
                    {/if}
                </h3>
                <div class="clearfix">
                    <ul class="availability-chart escort-rates-chart{if $smarty.const.IS_ESCORT !== true} simple-hourly-mode{/if}">
                    {foreach from=$ratesData item='escort_rates_item'}
                        <li class="clearfix">
                            {if $escort_rates_item.subtitle}
                                <div>{$escort_rates_item.title}</div>
                                <div>{$escort_rates_item.subtitle}</div>
                            {else}
                                <div>{$escort_rates_item.title} - {$escort_rates_item.time} {$lang.booking_rate_hour}</div>
                                <div>
                                    {assign var='currencyName' value=$escort_rates_item.currency}
                                    {assign var='currencyPhraseKey' value='data_formats+name+'|cat:$escort_rates_item.currency}

                                    {if $lang.$currencyPhraseKey}
                                        {assign var='currencyName' value=$lang.$currencyPhraseKey}
                                    {/if}

                                    {if $config.system_currency_position == 'before'}
                                        {$currencyName} {$escort_rates_item.price}
                                    {else}
                                        {$escort_rates_item.price} {$currencyName}
                                    {/if}
                                </div>
                            {/if}
                        </li>
                    {/foreach}
                    </ul>
                </div>
            </section>
        </div>
    {/if}
</div>

{if $ratesData|@count > 0}
    <div id="escort_rates_obj" class="hide">
        <select name="escort_rates">
            <option value="-1">{$lang.select}</option>

            {foreach from=$ratesData item='rate' key='rate_key'}
                {if $rate.subtitle}
                    {assign var='ratePrice' value=$rate.subtitle|regex_replace:'/[^0-9.]+/':''}
                    {assign var='rateTime' value=$rate.title|regex_replace:'/[^0-9.,]+/':''}
                {else}
                    {assign var='ratePrice' value=$rate.price}
                    {assign var='rateTime' value=$rate.time}
                {/if}

                <option {if $edit_action && $rate_key == $bookingData.checkin}selected="selected"{/if}
                        value="{$rate_key}"
                        data-price="{$ratePrice}"
                        data-hours="{$rateTime}"
                        data-type="{$rate.type}"
                >
                    {$rate.title}
                </option>
            {/foreach}
        </select>
    </div>
{/if}

<div id="escort_duration_obj" class="hide">
    <select name="escort_duration" {if !$edit_action}class="disabled" disabled="disabled"{/if}>
        <option value="-1">{$lang.select}</option>

        {section name='durations' start=1 loop=13}
            {assign var='durationsIndex' value=$smarty.section.durations.index}

            {section name='subdurations' start=1 loop=3}
                {if $smarty.section.subdurations.index % 2 != 0}
                    {assign var='subDurationsIndex' value=$durationsIndex-0.5}
                {else}
                    {assign var='subDurationsIndex' value=$durationsIndex}
                {/if}

                {assign var='durationPhraseKey' value='booking_escort_duration_'|cat:$subDurationsIndex}

                <option {if $edit_action && $subDurationsIndex == $bookingData.end_date}selected="selected"{/if}
                        value="{$subDurationsIndex}"
                >
                    {$lang.$durationPhraseKey}
                </option>
            {/section}
        {/section}
    </select>
</div>

<!-- Booking availability/rates/duration info -->
