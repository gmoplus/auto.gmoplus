<!-- currency converter header styles -->

<style>
{literal}
#currency_selector span.content {
    /* flatty templates fallback */
    min-width: auto;
}
#currency_selector span.content > div {
    max-height: 270px;
    overflow: hidden;

    /* modern templates scrollBar fallback */
    padding-top: 0;
    padding-bottom: 0;
}
#currency_selector > span.default > span.symbol {
    font-size: 1.214em;
}
#currency_selector > span.default > span.code {
    font-size: 0.929em;
}

#currency_selector > span.default > * {
    display: inline-block!important;
}

#currency_selector ul > li.sticky-rate + li:not(.sticky-rate) {
    border-top: 1px rgba(0,0,0,.5) solid;
    height: 35px;
    padding-top: 7px;
    margin-top: 7px;
}

.price_tag span.hide,
.price-tag span.hide {
    display: none!important;
}

/*** MOBILE VIEW ***/
@media screen and (max-width: 767px) {
    #currency_selector {
        position: relative;
    }
}
{/literal}
</style>

<script>
var currencyConverter = new Object();
currencyConverter.config = new Array();
currencyConverter.rates = new Array();

lang['short_price_k'] = '{if $lang.short_price_k}{$lang.short_price_k}{else}k{/if}';
lang['short_price_m'] = '{if $lang.short_price_m}{$lang.short_price_m}{else}m{/if}';
lang['short_price_b'] = '{if $lang.short_price_b}{$lang.short_price_b}{else}b{/if}';

currencyConverter.config['currency'] = {if $curConv_code}'{$curConv_code}'{else}false{/if};
currencyConverter.config['field'] = '{$config.currencyConverter_price_field}';
currencyConverter.config['show_cents'] = {$config.show_cents};
currencyConverter.config['price_delimiter'] = "{$config.price_delimiter}";
currencyConverter.config['cents_separator'] = "{$config.price_separator}";
currencyConverter.config['currency_position'] = '{$config.system_currency_position}';

{foreach from=$curConv_rates item='curConv_rate' key='curConv_key'}
currencyConverter.rates['{$curConv_key}'] = new Array('{$curConv_rate.Rate}', ['{$curConv_rate.Code}'{if $curConv_rate.Symbol},{foreach from=','|explode:$curConv_rate.Symbol item='cc_rItem' name='ccF'}'{$cc_rItem|flHtmlEntitiesDecode}'{if !$smarty.foreach.ccF.last},{/if}{/foreach}{/if}]);
{/foreach}
</script>

<!-- currency converter header styles end -->
