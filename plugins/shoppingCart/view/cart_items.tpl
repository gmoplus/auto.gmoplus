<!-- my cart items list -->

{if !empty($shcItems)}
	{foreach from=$shcItems item='item' name='shcItemsF'}
		<li class="d-flex mb-3">
			{if $item.Main_photo}
				<div class="cart-item-picture mr-2{if !$item.shc_available} shc-item-unavailable{/if}">
					<a href="{$item.listing_link}" target="_blank">
						<img alt="{$item.Item}"
							{if $item.Main_photo}
							src="{$smarty.const.RL_FILES_URL}{$item.Main_photo}" 
							{else}
							src="data:image/png;base64, {$item.photo_tmp}"
							{/if}
						/>
					</a>
				</div>
			{/if}
			<div class="cart-item-info flex-fill position-relative pr-3">
				<a href="{$item.listing_link}" target="_blank">{$item.Item}</a>
				<div class="pt-1{if !$item.shc_available} red unavailable{/if}">
					{if $item.shc_available}
						<span class="shc-rtl-fix">{$item.Quantity} x</span>
						<strong class="shc_price">
						{if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
						{str2money string=$item.Price}
						{if $config.system_currency_position == 'after'} {$config.system_currency}{/if}
						</strong>
					{else}
						{$lang.shc_not_available}
					{/if}
				</div>

				<div title="{$lang.remove}" class="close-red position-absolute delete-item-from-cart" data-id="{$item.ID}" data-item-id="{$item.Item_ID}"></div>
			</div>
		</li>
	{/foreach}
	
	<li class="d-flex align-items-center justify-content-between controls">
		<a href="javascript:void(0);" class="clear-cart red">{$lang.shc_clear_cart}</a>
        <a class="button ml-3" href="{pageUrl page='shc_my_shopping_cart'}">{$lang.checkout}</a>
	</li>
{else}
	<li class="text-notice">{$lang.shc_empty_cart}</li>
{/if}

<!-- my cart items list end -->
