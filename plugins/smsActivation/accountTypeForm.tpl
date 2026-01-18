<!-- smsActivation option -->

<tr>
    <td class="name">{$lang.smsActivation_status}</td>
    <td class="field">
        {assign var='smsActivation_checkbox_field' value='smsActivation_module'}
        
        {if $sPost.$smsActivation_checkbox_field == '1'}
            {assign var=$smsActivation_checkbox_field|cat:'_yes' value='checked="checked"'}
        {elseif $sPost.$smsActivation_checkbox_field == '0'}
            {assign var=$smsActivation_checkbox_field|cat:'_no' value='checked="checked"'}
        {else}
            {assign var=$smsActivation_checkbox_field|cat:'_no' value='checked="checked"'}
        {/if}
        
        <table>
        <tr>
            <td>
                <input {$smsActivation_module_yes} type="radio" id="{$smsActivation_checkbox_field}_yes" name="{$smsActivation_checkbox_field}" value="1" /> <label for="{$smsActivation_checkbox_field}_yes">{$lang.yes}</label>
                <input {$smsActivation_module_no} type="radio" id="{$smsActivation_checkbox_field}_no" name="{$smsActivation_checkbox_field}" value="0" /> <label for="{$smsActivation_checkbox_field}_no">{$lang.no}</label>
            </td>
        </tr>
        </table>
    </td>
</tr>

<!-- smsActivation option end -->
