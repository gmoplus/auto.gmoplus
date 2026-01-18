<!-- shopping cart option -->

{assign var='shcModule' value='config+name+shc_module'}
{assign var='shcAuction' value='config+name+shc_module_auction'}
<tr>
	<td class="name">{phrase key=$shcModule db_check=true}</td>
	<td class="field">
		{assign var='checkbox_field' value='shc_module'}
		
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
				<input {$shc_module_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
				<input {$shc_module_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
			</td>
		</tr>
		</table>
	</td>
</tr>
<tr>
	<td class="name">{phrase key=$shcAuction db_check=true}</td>
	<td class="field">
		{assign var='checkbox_field' value='shc_auction'}
		
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
				<input {$shc_auction_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
				<input {$shc_auction_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
			</td>
		</tr>
		</table>
	</td>
</tr>

<!-- shopping cart option end -->
