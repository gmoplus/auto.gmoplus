<!-- rate range -->

{include file='blocks/fieldset_header.tpl' id='rate_range' name=$lang.booking_rate_range}

<ul class="ranges" id="booking_rate_range">
    {foreach from=$rate_ranges item='rateRange'}
        <li class="two-inline clearfix">
            <div class="price">{if $rateRange.Price}{$rateRange.Price}{else}{$lang.booking_close_days}{/if}</div>
            <div class="date">
                {$rateRange.From} - {$rateRange.To}

                {if !empty($rateRange.Desc)}
                    <img class="booking_qtip" alt="" title="{$rateRange.Desc}" src="{$rlTplBase}img/blank.gif" />
                {/if}
            </div>
        </li>
    {/foreach}

    {if $use_time_frame}
        <li class="two-inline clearfix">
            <div class="price">{$defPrice.name}</div>
            <div class="date">
                {$lang.booking_rate_price_per_day}

                {if $range_regular_desc}
                    <img class="booking_qtip" alt="" title="{$range_regular_desc}" src="{$rlTplBase}img/blank.gif" />
                {/if}
            </div>
        </li>
    {/if}

    {if $bookingConfigs.price_per_hour}
        <li class="two-inline clearfix">
            <div class="price">
                {if $defPrice.currency}
                    {assign var="booking_currency" value=$defPrice.currency}
                {else}
                    {assign var="booking_currency" value=$config.system_currency}
                {/if}

                {if $config.system_currency_position == 'before'}
                    {$booking_currency} {$bookingConfigs.price_per_hour}
                {else}
                    {$bookingConfigs.price_per_hour} {$booking_currency}
                {/if}
            </div>
            <div class="date">
                {$lang.booking_rate_price_per_hour}
            </div>
        </li>
    {/if}
</ul>

{include file='blocks/fieldset_footer.tpl'}

<script class="fl-js-static">{literal}
$(function() {
    if (typeof $.convertPrice == 'function') {
        $('#booking_rate_range .price').convertPrice({showCents: true, shortView: true});
    }
});
{/literal}</script>

<!-- rate range end -->
