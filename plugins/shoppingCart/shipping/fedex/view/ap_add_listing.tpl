<!-- Fedex setting -->
<table class="form">
    <tr>
        <td class="name"> {$lang.shc_shipping_services}</td>
        <td class="field">
            {assign var='shcFedexServices' value=$smarty.post.shipping.fedex.services}
            <div id="shc_usps_services">
                {assign var='col_num' value=3}
                <table class="fixed">
                    <tr>
                    {foreach from=$shc_fedex_services item='service' key="key" name='fedexServicesF'}
                        <td valign="top" style="padding: 2px 0;">
                            <input class="checkbox" {if $service.key|in_array:$shcFedexServices}checked="checked"{/if} type="checkbox" id="fedex_item_{$service.key}" name="shipping[fedex][services][]" value="{$service.key}" />&nbsp;<label for="fedex_item_{$service.key}">{$service.name}</label>
                        </td>
                        {if $smarty.foreach.fedexServicesF.iteration%$col_num == 0 && !$smarty.foreach.fedexServicesF.last}
                        </tr>
                        <tr>
                        {/if}
                    {/foreach}
                    </tr>
                </table>
            </div>
        </td>
    </tr>
    <tr>
        <td class="name"> {$lang.shc_fedex_dropoff_type}</td>
        <td class="field"> 
            <select name="shipping[fedex][dropoff_type]">
            {foreach from=$shc_fedex_dropoff_types item='dropoff_type' key='key'}
                <option value="{$key}" {if $smarty.post.shipping.fedex.dropoff_type == $key}selected="selected"{/if}>{$dropoff_type}</option>
            {/foreach}
            </select>
        </td>
    </tr>
    <tr>
        <td class="name"> {$lang.shc_fedex_packaging_type}</td>
        <td class="field"> 
            <select name="shipping[fedex][packaging_type]">
            {foreach from=$shc_fedex_packaging_types item='packaging_type' key='key'}
                <option value="{$key}" {if $smarty.post.shipping.fedex.packaging_type == $key}selected="selected"{/if}>{$packaging_type}</option>
            {/foreach}
            </select>
        </td>
    </tr>
</table>

<!-- end Fedex setting -->
