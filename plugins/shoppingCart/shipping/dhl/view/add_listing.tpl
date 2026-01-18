<!-- DHL setting -->

<div class="submit-cell clearfix">
    <div class="name">{$lang.shc_dhl_transport_mode}</div>
    <div class="field single-field">
        <select id="shc_dhl_transport_mode" name="shipping[dhl][transport_mode]">
            {foreach from=$shc_transport_modes item='transport_mode' key="key"}
            <option value="{$key}" {if $smarty.post.shipping.dhl.transport_mode == $key}selected="selected"{/if}>{$transport_mode}</option>
            {/foreach}
        </select>
    </div>
</div>
<div class="submit-cell clearfix">
    <div class="name">{$lang.shc_dhl_hs_code}</div>
    <div class="field single-field">
        <input id="shc_dhl_hsCode" type="text" name="shipping[dhl][hsCode]" value="{$smarty.post.shipping.dhl.hsCode}" />
    </div>
</div>

<!-- end DHL setting -->
