<!-- dhl setting -->
<table class="form">
    <tr>
        <td class="name"> {$lang.shc_dhl_transport_mode}</td>
        <td class="field"> 
            <select name="shipping[dhl][transport_mode]">
            {foreach from=$shc_dhl_transport_modes item='transport_mode' key='key'}
                <option value="{$key}" {if $smarty.post.shipping.dhl.transport_mode == $key}selected="selected"{/if}>{$transport_mode}</option>
            {/foreach}
            </select>
        </td>
    </tr>
    <tr>
        <td class="name"> {$lang.shc_dhl_hs_code}</td>
        <td class="field"> 
            <input id="shc_dhl_hsCode" type="text" name="shipping[dhl][hsCode]" value="{$smarty.post.shipping.dhl.hsCode}" />
        </td>
    </tr>
</table>

<!-- end dhl setting -->
