<!-- Fedex setting -->

<div class="submit-cell clearfix">
    <div class="name">{$lang.shc_shipping_services}</div>
    <div class="field checkbox-field">
        {assign var='shcFedexServices' value=$smarty.post.shipping.fedex.services}
        <div class="row">
            {foreach from=$shc_fedex_services item='service' key="key"}
                <span class="custom-input col-xs-12 col-lg-6 col-md-6 col-sm-4">
                    <label><input class="checkbox" {if $shcFedexServices && $service.key|in_array:$shcFedexServices}checked="checked"{/if} type="checkbox" name="shipping[fedex][services][]" value="{$service.key}" />{$service.name}</label>
                </span>
            {/foreach}
        </div>
    </div>
</div>
<div class="submit-cell clearfix">
    <div class="name">{$lang.shc_fedex_dropoff_type}</div>
    <div class="field single-field">
        <select id="shc_fedex_dropoff_type" name="shipping[fedex][dropoff_type]">
        {foreach from=$shc_fedex_dropoff_types item='dropoff_type' key='key'}
            <option value="{$key}" {if $smarty.post.shipping.fedex.dropoff_type == $key}selected="selected"{/if}>{$dropoff_type}</option>
        {/foreach}
        </select>
    </div>
</div>
<div class="submit-cell clearfix">
    <div class="name">{$lang.shc_fedex_packaging_type}</div>
    <div class="field single-field">
        <select id="shc_fedex_packaging_type" name="shipping[fedex][packaging_type]">
        {foreach from=$shc_fedex_packaging_types item='packaging_type' key='key'}
            <option value="{$key}" {if $smarty.post.shipping.fedex.packaging_type == $key}selected="selected"{/if}>{$packaging_type}</option>
        {/foreach}
        </select>
    </div>
</div>

<!-- end Fedex setting -->
