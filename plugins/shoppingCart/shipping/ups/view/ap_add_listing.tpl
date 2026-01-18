<!-- UPS setting -->

<table class="form">
    <tr>
        <td class="name"> {$lang.shc_ups_origin}</td>
        <td class="field">
            <select name="shipping[ups][origin]" id="shc_ups_origin">
            {foreach from=$ups_origins item='origin' key='key'}
                <option value="{$key}" {if $smarty.post.shipping.ups.origin == $key}selected="selected"{/if}>{$origin}</option>
            {/foreach}
            </select>
        </td>
    </tr>
    <tr>
        <td class="name"> {$lang.shc_shipping_services}</td>
        <td class="field">
            {assign var='shcUPSServices' value=$smarty.post.shipping.ups.services}
            <div id="shc_ups_services">
                {foreach from=$shc_ups_services item='service'}
                    {assign var='shcOriginsItem' value=","|explode:$service.origin}
                    {assign var='shcItemKey' value=$service.code}
                    <div class="{if $smarty.post.shipping.ups.origin|in_array:$shcOriginsItem || (!$smarty.post.shipping.ups.origin && 'US'|in_array:$shcOriginsItem)}{else}hide{/if}">
                        <input class="checkbox ups-origin" {if $shcItemKey|in_array:$shcUPSServices}checked="checked"{/if} id="shc_pickup_item{$service.code}" accesskey="{$service.origin}" type="checkbox" name="shipping[ups][services][]" value="{$service.code}" />&nbsp;<label for="shc_pickup_item{$service.code}">{$service.name}</label>
                    </div>
                {/foreach}
            </div>
        </td>
    </tr>
    <tr>
        <td class="name"> {$lang.shc_ups_pickup_methods}</td>
        <td class="field"> 
            <select name="shipping[ups][pickup_method]">
                {foreach from=$shc_ups_pickup_methods key='code' item='method'}
                    <option value="{$code}" {if $config.shc_ups_pickup_methods == $code}selected="selected"{/if}>{$method}</option>
                {/foreach}
            </select>
        </td>
    </tr>
</table>

<script class="fl-js-dynamic">
    {literal}
    $(document).ready(function(){
        handleUPSShippingServices($('#shc_ups_origin option:selected').val());
        $('#shc_ups_origin').change(function() {
            handleUPSShippingServices($(this).val());
        });
    });

    var handleUPSShippingServices = function(origin) {
        $('#shc_ups_services input.ups-origin').each(function() {
            var origins = $(this).attr('accesskey').split(',');
            if (origins.indexOf(origin) >= 0) {
                $(this).parent('div').removeClass('hide');
            } else {
                $(this).prop('checked', false);
                $(this).parent('div').addClass('hide');
            }
        });
    }
    {/literal}
</script>

<!-- end UPS setting -->
