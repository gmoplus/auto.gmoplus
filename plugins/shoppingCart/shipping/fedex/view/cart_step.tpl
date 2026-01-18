<!-- Fedex settings -->

<div class="submit-cell">
    <div class="name">{$lang.shc_shipping_services} <span class="red">*</span></div> 
    {assign var='shcFedexAllowedServices' value=$item_data.fedex.services}
    <div class="field single-field"> 
        <input type="hidden" name="items[{$item_id}][fedex][service_single]" class="service-single-fedex item-fixed-single" data-item="{$item_id}" value="{$item_data.services.total}">
        <select name="items[{$item_id}][fedex][service]">
            <option value="">{$lang.select}</option>
            {foreach from=$shc_fedex_services item='service' key='key'}
                {if ($shcFedexAllowedServices && $service.key|in_array:$shcFedexAllowedServices)
                    || ($item.shc_use_system_shipping_config == 'single' && $key|in_array:$shcFedexAllowedServices)
                }
                    <option value="{$service.key}" {if $smarty.post.items[$item_id].service == $service.key}selected="selected"{/if}>{$service.name}</option>
                {/if}
            {/foreach}
        </select>
    </div>
</div>
<div class="submit-cell">
    <div class="name"></div>
    <div class="field single-field">
        <a href="javascript://" item="{$item_id}" method="fedex" class="calculate-rate button low" >{$lang.shc_get_quote}</a>
    </div>
</div>

<!-- end Fedex settings -->
