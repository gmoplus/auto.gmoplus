<!-- AutoPoster modules configuration area -->
{if $configItem.Type == 'text' || $configItem.Type == 'textarea' || $configItem.Type == 'bool' || $configItem.Type == 'select' || $configItem.Type == 'radio'}
    {assign var="sPost" value=$smarty.post}

    <tr {if $configItem.Key === 'ap_facebook_subject_id' && $config.ap_facebook_post_to === 'to_page'}class="hide"{/if}>
        <td class="name">{$configItem.name}{if $configItem.validate}<span class="red"> *</span>{/if}</td>
        <td class="field">
            <div class="inner_margin">
                {if $configItem.Type == 'text'}
                    <input name="post_config[{$configItem.Key}]" class="{if $configItem.Data_type == 'int'}numeric{/if}" type="text" value="{if isset($sPost.post_config[$configItem.Key])}{$sPost.post_config[$configItem.Key]}{else}{$configItem.Default}{/if}" maxlength="255" />
                {elseif $configItem.Type == 'bool'}
                    <label><input type="radio" {if $configItem.Default == 1}checked="checked"{/if} name="post_config[{$configItem.Key}]" value="1" /> {$lang.enabled}</label>
                    <label><input type="radio" {if $configItem.Default == 0}checked="checked"{/if} name="post_config[{$configItem.Key}]" value="0" /> {$lang.disabled}</label>
                {elseif $configItem.Type == 'textarea'}
                    <textarea cols="5" rows="5" class="{if $configItem.Data_type == 'int'}numeric{/if}" name="post_config[{$configItem.Key}]">{if isset($sPost.post_config[$configItem.Key])}{$sPost.post_config[$configItem.Key]}{else}{$configItem.Default}{/if}</textarea>
                {elseif $configItem.Type == 'select'}
                    <select {if $configItem.Key == 'timezone'}class="w350"{/if} style="width: 204px;" name="post_config[{$configItem.Key}]" {if $configItem.Values|@count < 2} class="disabled" disabled="disabled"{/if}>
                        {if $configItem.Values|@count > 1}
                            <option value="">{$lang.select}</option>
                        {/if}
                        {foreach from=$configItem.Values item='sValue' name='sForeach'}
                            <option  {if $sValue.Disabled}disabled="disabled"{/if} value="{if is_array($sValue)}{$sValue.ID}{else}{$sValue}{/if}" {if is_array($sValue)}{if $configItem.Default == $sValue.ID || $sPost.post_config[$configItem.Key] == $sValue.ID}selected="selected"{/if}{else}{if $sValue == $configItem.Default}selected="selected"{/if}{/if}>{if is_array($sValue)}{$sValue.name}{else}{$sValue}{/if}</option>
                        {/foreach}
                    </select>
                {elseif $configItem.Type == 'radio'}
                    {assign var='displayItem' value=$configItem.Display}
                    {foreach from=$configItem.Values item='rValue' name='rForeach' key='rKey'}
                        <input id="radio_{$configItem.Key}_{$rKey}" {if $rValue == $configItem.Default}checked="checked"{/if} type="radio" value="{$rValue}" name="post_config[{$configItem.Key}][value]" /><label for="radio_{$configItem.Key}_{$rKey}">{$displayItem.$rKey}</label>
                    {/foreach}
                {else}
                    {$configItem.Default}
                {/if}

                {if $configItem.des != ''}
                    <span style="{if $configItem.Type == 'textarea'}line-height: 10px;{elseif $configItem.Type == 'bool'}line-height: 14px;margin: 0 10px;{/if}" class="settings_desc">{$configItem.des}</span>
                {/if}
            </div>
        </td>
    </tr>
{/if}
<!-- AutoPoster modules configuration area end -->
