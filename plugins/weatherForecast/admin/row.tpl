<!-- location finder option in listing type -->

<tr>
    <td class="name">{$lang.weatherForecast_weather_foreacst}</td>
    <td class="field">
        {assign var='checkbox_field' value='weather_forecast'}
        
        {if $sPost.$checkbox_field == '1'}
            {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
        {elseif $sPost.$checkbox_field == '0'}
            {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
        {else}
            {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
        {/if}
        
        <table>
        <tr>
            <td>
                <input {$weather_forecast_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                <input {$weather_forecast_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
            </td>
        </tr>
        </table>
    </td>
</tr>

<!-- location finder option in listing type end -->
