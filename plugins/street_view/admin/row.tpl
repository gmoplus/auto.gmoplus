<!-- street view option -->

<tr>
	<td class="name">{$lang.street_view_tab}</td>
	<td class="field">
		{assign var='checkbox_field' value='street_view'}
		
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
				<input {$street_view_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
				<input {$street_view_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
			</td>
		</tr>
		</table>
	</td>
</tr>

<!-- street view option end -->
