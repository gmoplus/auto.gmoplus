<!-- USPS settings -->

<div class="submit-cell shc-usps-domestic-services">
    <div class="name">
        {$lang.shc_usps_domestic_services}
    </div>
    {assign var='shcUSPSDomesticAllowedServices' value=$item_data.usps.domestic_services}
    <div class="field single-field">  
        <select name="items[{$item_id}][usps][domestic_services]">
            <option value="">{$lang.select}</option>
            {foreach from=$shc_usps_domestic_services item='service' key='key'}
                {if $shcUSPSDomesticAllowedServices && $service.key|in_array:$shcUSPSDomesticAllowedServices}
                    <option value="{$service.key}" {if $smarty.post.shipping.usps.domestic_services == $service.key}selected="selected"{/if}>{$service.name}</option>
                {/if}
            {/foreach}
        </select>
    </div>
</div>
<div class="submit-cell shc-usps-international-services">
    <div class="name">
        {$lang.shc_usps_international_services}
    </div>
    {assign var='shcUSPSInternationalAllowedServices' value=$item_data.usps.international_services}
    <div class="field single-field">  
        <select name="items[{$item_id}][usps][international_services]">
            <option value="">{$lang.select}</option>
            {foreach from=$shc_usps_international_services item='service' key='key'}
                {if $shcUSPSInternationalAllowedServices && $service|in_array:$shcUSPSInternationalAllowedServices}
                    <option value="{$service}" {if $smarty.post.shipping.usps.international_services == $service.key}selected="selected"{/if}>{$service}</option>
                {/if}
            {/foreach}
        </select>
    </div>
</div>
<div class="submit-cell response-service{if !$services} hide{/if}">
    <div class="name">
        {$lang.service}
    </div>
    <div class="field single-field">
        <input type="hidden" name="items[{$item_id}][usps][service_single]" class="service-single-usps item-fixed-single" data-item="{$item_id}" value="{$item_data.services.total}">
        <select class="service-usps item-fixed-price" name="items[{$item_id}][usps][service]" data-item="{$item_id}">
            {if $services.0}
                {foreach from=$services item='service' key='key'}
                <option value="{$service.service}" {if $service.selected}selected="selected"{/if} data-fixed-price="{$service.total}">{$service.title} - {$service.total}</option>
                {/foreach}
            {/if}
        </select>
    </div>
</div>
<div class="submit-cell">
    <div class="name"></div>
    <div class="field single-field">
        <a href="javascript://" item="{$item_id}" method="usps" class="calculate-rate button low" >{$lang.shc_get_quote}</a>
    </div>
</div>
<script class="fl-js-dynamic">
    var uspsItemID = {$item_id};
    {literal}
    $(document).ready(function(){
        if ($('select.service-usps option:selected').val()) {
            shoppingCart.calculateShippingPrice();
        }
        $('select.service-usps').change(function() {
            shoppingCart.calculateShippingPrice();
        });
    });
    {/literal}
</script>

<!-- end USPS settings -->
