<!-- android common settings -->

<form method="post" action="{$rlBase}index.php?controller={$cInfo.Controller}">
	<input name="submit" value="1" type="hidden" />
	
	<table class="form">
	{foreach from=$configs item='configItem' name='configF'}
	<tr class="{if $smarty.foreach.configF.iteration%2 != 0 && $configItem.Type != 'divider'}highlight{/if}">
		{if $configItem.Type == 'divider'}
			<td class="divider_line" colspan="2">
				<div class="inner">{$configItem.name}</div>
			</td>
		{else}
			<td class="name" style="width: 210px;">{$configItem.name}</td>
			<td class="field">
				<div class="inner_margin">
					{if $configItem.Data_type == 'int'}<input class="text" type="hidden" name="config[{$configItem.Key}][d_type]" value="{$configItem.Data_type}" />{/if}
					<input class="text" type="hidden" name="config[{$configItem.Key}][value]" value="{$configItem.Default}" />
					
					{if $configItem.Type == 'text'}
						<input class="text {if $configItem.Data_type == 'int'}numeric{/if}" type="text" name="config[{$configItem.Key}][value]" value="{$configItem.Default}" />
					{elseif $configItem.Type == 'textarea'}
						<textarea cols="5" rows="5" class="{if $configItem.Data_type == 'int'}numeric{/if}" name="config[{$configItem.Key}][value]">{$configItem.Default|replace:'\r\n':$smarty.const.PHP_EOL}</textarea>
					{elseif $configItem.Type == 'bool'}
						<input {if $configItem.Default == 1}checked="checked"{/if} type="radio" id="{$configItem.Key}_1" name="config[{$configItem.Key}][value]" value="1" /> 
						<label for="{$configItem.Key}_1">{$lang.enabled}</label>
						
						<input {if $configItem.Default == 0}checked="checked"{/if} type="radio" id="{$configItem.Key}_0" name="config[{$configItem.Key}][value]" value="0" /> 
						<label for="{$configItem.Key}_0">{$lang.disabled}</label>
					{elseif $configItem.Type == 'select'}
						<select style="width: 204px;" name="config[{$configItem.Key}][value]" 
							{foreach from=$configItem.Values item='sValue' name='sForeach'}
								{if $smarty.foreach.sForeach.first}
									{if $smarty.foreach.sForeach.total <= '1'} class="disabled" disabled="disabled"{/if}
								>
									{if is_array($sValue)}<option value="">{$lang.select}</option>{/if}
								{/if}
								<option value="{if is_array($sValue)}{$sValue.ID}{else}{$sValue}{/if}" {if is_array($sValue)}{if $configItem.Default == $sValue.ID}selected="selected"{/if}{else}{if $sValue == $configItem.Default}selected="selected"{/if}{/if}>{if is_array($sValue)}{$sValue.name}{else}{$sValue}{/if}</option>
							{/foreach}
						</select>
					{elseif $configItem.Type == 'radio'}
						{assign var='displayItem' value=$configItem.Display}
						{foreach from=$configItem.Values item='rValue' name='rForeach' key='rKey'}
							<input id="radio_{$configItem.Key}_{$rKey}" {if $rValue == $configItem.Default}checked="checked"{/if} type="radio" value="{$rValue}" name="config[{$configItem.Key}][value]" /><label for="radio_{$configItem.Key}_{$rKey}">&nbsp;{$displayItem.$rKey}&nbsp;&nbsp;</label>
						{/foreach}
					{else}
						{$configItem.Default}
					{/if}
					{if $configItem.des != ''}
						<span style="{if $configItem.Type == 'textarea'}line-height: 10px;{elseif $configItem.Type == 'bool'}line-height: 14px;margin: 0 10px;{/if}" class="settings_desc">{$configItem.des}</span>
					{/if}
				</div>
			</td>
		{/if}
	</tr>
	{/foreach}
	<tr>
		<td></td>
		<td><input style="margin: 10px 0 0 0;" type="submit" class="button" value="{$lang.save}" /></td>
	</tr>
	</table>
</form>

<!-- android common settings end -->