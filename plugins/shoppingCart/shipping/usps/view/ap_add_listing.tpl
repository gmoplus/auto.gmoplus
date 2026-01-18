<!-- usps setting -->
<table class="form">
    <tr>
        <td class="name">
            {$lang.shc_usps_domestic_services}
        </td>
        <td class="field">   
            {assign var='shcuspsServicesDomestic' value=$smarty.post.shipping.usps.domestic_services}
            <div id="shc_usps_services">
                {assign var='col_num' value=3}
                <table class="fixed">
                    <tr>
                    {foreach from=$shc_usps_domestic_services item='service' key="key" name='uspsServicesF'}
                        <td valign="top" style="padding: 2px 0;">
                            <div style="padding: 2px 8px 2px 0;">
                                <input class="checkbox" {if $service.key|in_array:$shcuspsServicesDomestic}checked="checked"{/if} type="checkbox" id="usps_item_{$service.key}" name="shipping[usps][domestic_services][]" value="{$service.key}" />&nbsp;<label for="usps_item_{$service.key}">{$service.name}</label>
                            </div>
                        </td>
                        {if $smarty.foreach.uspsServicesF.iteration%$col_num == 0 && !$smarty.foreach.uspsServicesF.last}
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
        <td class="name">
            {$lang.shc_usps_international_services}
        </td>
        <td class="field">
            {assign var='shcuspsServicesInternational' value=$smarty.post.shipping.usps.international_services}
            <div id="shc_usps_services">
                {assign var='col_num' value=3}
                <table class="fixed">
                    <tr>
                    {foreach from=$shc_usps_international_services item='service' key="key" name='uspsInternationalServicesF'}
                        <td valign="top" style="padding: 2px 0;">
                            <div style="padding: 2px 8px 2px 0;">
                                <input class="checkbox" {if $service|in_array:$shcuspsServicesInternational}checked="checked"{/if} id="uspsi_item_{$service.key}" type="checkbox" name="shipping[usps][international_services][]" value="{$service}" />&nbsp;<label for="uspsi_item_{$service}">{$service}</label>
                            </div>
                        </td>
                        {if $smarty.foreach.uspsInternationalServicesF.iteration%$col_num == 0 && !$smarty.foreach.uspsInternationalServicesF.last}
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
        <td class="name">
            {$lang.shc_usps_container}</td>
        <td class="field">
            <select name="shipping[usps][container]">
            {foreach from=$shc_usps_containers item='container' key='key'}
                <option value="{$key}" {if $smarty.post.shipping.usps.container == $key}selected="selected"{/if}>{$container.name}</option>
            {/foreach}
            </select>
        </td>
    </tr>
    <tr>
        <td class="name">
            {$lang.shc_usps_size}
        </td>
        <td class="field">
            <select name="shipping[usps][size]">
                <option value="">{$lang.select}</option>
                <option value="REGULAR" {if $smarty.post.shipping.usps.size == 'REGULAR'}selected="selected"{/if}>{$lang.shc_usps_size_regular}</option>
                <option value="LARGE" {if $smarty.post.shipping.usps.size == 'LARGE'}selected="selected"{/if}>{$lang.shc_usps_size_large}</option>
            </select>
        </td>
    </tr>
    <tr>
        <td class="name">
            {$lang.shc_usps_mail_type}</td>
        <td class="field">
            <select name="shipping[usps][mail_type]">
            {foreach from=$shc_usps_mail_types_domestic item='mail_type' key='key'}
                <option value="{$mail_type}" {if $smarty.post.shipping.usps.mail_type == $mail_type}selected="selected"{/if}>{$mail_type}</option>
            {/foreach}
            </select>
        </td>
    </tr>
</table>
<!-- end usps setting -->
