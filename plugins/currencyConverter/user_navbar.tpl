<!-- header user navigation bar -->

<span class="circle currency-selector selector" id="currency_selector">
	<span class="default"><span class="{if $sign_length == 3}code{else}symbol{/if}">{$curConv_sign}</span></span>
	<span class="content hide">
		<div>
			<ul>
			{foreach from=$curConv_rates item='curConv_rate' key='currencyKey'}
                {if $curConv_rate.Status != 'active'}{continue}{/if}

				<li{if $curConv_rate.Sticky || $currencyKey == $curConv_code} class="{if $curConv_rate.Sticky}sticky-rate{/if}{if $currencyKey == $curConv_code} active{/if}"{/if} data-code="{$currencyKey}">
                    <a accesskey="{$currencyKey}" title="{$curConv_rate.Country}" class="font1{if $currencyKey == $curConv_code} active{/if}" href="javascript://">{$curConv_rate.Code}</a>
                </li>
			{/foreach}
			</ul>
		</div>
	</span>
</span>

<!-- header user navigation bar end -->