<!-- listings label block -->
{if !empty($ls_listings)}
	<ul class="row featured with-pictures">
	{foreach from=$ls_listings item='rsold_listing' key='key'}
		{assign var='type' value=$rsold_listing.Listing_type}
		{assign var='page_key' value=$listing_types.$type.Page_key}
		{include file='blocks'|cat:$smarty.const.RL_DS|cat:'featured_item.tpl' featured_listing=$rsold_listing}
	{/foreach}
	</ul>
{else}
	{assign var='ls_lang' value='lsl_'|cat:$ls_key}
	<div class="grey_middle">{$lang.rsold_no_listings|replace:"[days]":$ls_days|replace:"[label]":$lang.$ls_lang}</div>
{/if}
<!-- listings label block -->
