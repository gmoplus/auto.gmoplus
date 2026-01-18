<!-- stripe plugin -->

<table class="form">
	<tr>
		<td class="name"><span class="red">*</span>{$lang.stripe_subscription}</td>
		<td class="field">                                                                                                                           
			{assign var='checkbox_field' value='stripe_subscription'}
			
			{if $sPost.$checkbox_field == '1'}
				{assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
			{elseif $sPost.$checkbox_field == '0'}
				{assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
			{else}
				{assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
			{/if}
			
			<input {$stripe_subscription_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
			<input {$stripe_subscription_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
		</td>
	</tr>
</table>

<div id="stripe_subscription_details" class="{if !$sPost.stripe_subscription}hide{/if}">
	<table class="form">
		<tr>
			<td class="name"><span class="red">*</span>{$lang.stripe_subscription_interval}</td>
			<td class="field">                                                                                          
				<select name="stripe_subscription_interval">
					<option value="">{$lang.select}</option>
					{foreach from=$subscription_intervals item='interval' key='pKey'}
						<option value="{$pKey}" {if $pKey == $sPost.stripe_subscription_interval}selected="selected"{/if}>{$interval}</option>
					{/foreach}
				</select>
			</td>
		</tr>
		<tr>
			<td class="name"><span class="red">*</span>{$lang.stripe_subscription_count}</td>
			<td class="field">
				<input type="text" name="stripe_subscription_count" value="{$sPost.stripe_subscription_count}" class="numeric" style="width: 50px; text-align: center;" />
			</td>
		</tr>
	</table>
</div>

<script type="text/javascript">
	{literal}

	$(document).ready(function(){
		$('input[name="stripe_subscription"]').change(function()
		{
			if($(this).is(':checked'))
			{
			 	if($(this).val() == 1)
				{
			   		$('#stripe_subscription_details').show();
				}
				else
				{
					$('#stripe_subscription_details').hide();
				}
			}
		});
	});

	{/literal}
</script>

<!-- end stripe plugin -->