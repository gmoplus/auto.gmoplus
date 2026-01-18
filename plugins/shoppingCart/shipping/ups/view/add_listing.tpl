<!-- UPS setting -->

<div class="submit-cell clearfix">
    <div class="name">{$lang.shc_ups_origin}</div>
    <div class="field single-field">
        <select name="shipping[ups][origin]" id="shc_ups_origin">
        {foreach from=$ups_origins item='origin' key='key'}
            <option value="{$key}" {if $smarty.post.shipping.ups.origin == $key}selected="selected"{/if}>{$origin}</option>
        {/foreach}
        </select>
    </div>
</div>
<div class="submit-cell clearfix">
    <div class="name">{$lang.shc_shipping_services}</div>
    <div class="field checkbox-field">
        <div id="shc_ups_services" class="row">
            {foreach from=$shc_ups_services item='service'}
                {assign var='shcOriginsItem' value=","|explode:$service.origin}
                {assign var='shcItemKey' value=$service.code}
                <span class="custom-input col-12 {if $smarty.post.shc.shc_ups_origin|in_array:$shcOriginsItem || (!$smarty.post.shc.shc_ups_origin && 'US'|in_array:$shcOriginsItem)}{else}hide{/if}">
                    <label title="{$service.name}">
                        <input class="checkbox ups-origin" {if $smarty.post.shipping.ups.services && $shcItemKey|in_array:$smarty.post.shipping.ups.services}checked="checked"{/if} id="shc_pickup_item{$service.code}" accesskey="{$service.origin}" type="checkbox" name="shipping[ups][services][]" value="{$service.code}" />
                        {$service.name}
                    </label>
                </span>
            {/foreach}
        </div>
    </div>
</div>
<div class="submit-cell clearfix">
    <div class="name">{$lang.shc_ups_pickup_methods}</div>
    <div class="field single-field">
        <select id="shc_ups_pickup_method" name="shipping[ups][pickup_method]">
            {foreach from=$shc_ups_pickup_methods key='code' item='method'}
                <option value="{$code}" {if $smarty.post.shipping.ups.pickup_method == $code}selected="selected"{/if}>{$method}</option>
            {/foreach}
        </select>
    </div>
</div>

<!-- end UPS setting -->
