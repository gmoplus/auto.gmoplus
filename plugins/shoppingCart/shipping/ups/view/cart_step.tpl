<!-- UPS settings -->

<div class="submit-cell">
	<div class="name">{$lang.shc_shipping_services} <span class="red">*</span></div>
	{assign var='shcUPSAllowedServices' value=$item_data.ups.services}
	<div class="field single-field">
        <input type="hidden" name="items[{$item_id}][ups][service_single]" class="service-single-ups item-fixed-single" data-item="{$item_id}" value="{if $item_data.services.total}{$item_data.services.total}{else}0{/if}">
		<select name="items[{$item_id}][ups][service]">
			<option value="">{$lang.select}</option>
			{foreach from=$shc_ups_services item='service'}
				{if $shcUPSAllowedServices && $service.code|in_array:$shcUPSAllowedServices}
					<option value="{$service.code}" {if $smarty.post.shipping.ups.service == $service.code}selected="selected"{/if}>{$service.name}</option>
				{/if}
			{/foreach}
		</select>
	</div>
</div>
<div class="submit-cell">
    <div class="name"></div>
    <div class="field single-field">
        <a href="javascript://" item="{$item_id}" method="ups" class="calculate-rate button low" >{$lang.shc_get_quote}</a>
    </div>
</div>

<!-- end UPS settings -->
