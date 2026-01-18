<!-- compare icon -->

{assign var='compare_in' value=false}
{if is_array($compare_cookie_ids) && $listing.ID|in_array:$compare_cookie_ids}
    {assign var='compare_in' value=true}
{/if}

{assign var='compare_ad_fields' value=''}
{foreach from=$listing.fields item='item'}
    {if empty($item.value) || !$item.Details_page || $item.Key|in_array:$tpl_settings.listing_grid_except_fields}{continue}{/if}
    {assign var='compare_ad_fields' value=$compare_ad_fields|cat:', '|cat:$item.value}
{/foreach}

{if $mode == 'grid'}<li{else}<span{/if} class="compare-grid-icon {if $compare_in} active{/if}{if !$tpl_settings.svg_icon_fill} {if $compare_in}remove remove_from_compare{else}add_to_compare{/if}{/if}"
    title="{if $compare_in}{$lang.compare_remove_from_compare}{else}{$lang.compare_add_to_compare}{/if}"
    data-listing-id="{$listing.ID}"
    data-listing-url="{$listing.url}"
    data-listing-title="{$listing.listing_title|escape}"
    data-listing-fields="{$compare_ad_fields|ltrim:', '}"
    {if $listing.Main_photo}
    data-listing-picture="{$smarty.const.RL_FILES_URL}{$listing.Main_photo}"
    {/if}>
    {if $tpl_settings.svg_icon_fill}
        <svg viewBox="0 0 18 18" class="icon grid-icon-fill">
            <use xlink:href="#compare-ad-icon{if $compare_in}-rev{/if}"></use>
        </svg>
    {else}
        <span class="icon"></span>
    {/if}

    {if $mode == 'grid'}
    <span class="link">{if $compare_in}{$lang.compare_remove_from_compare}{else}{$lang.compare_add_to_compare}{/if}</span>
    {/if}
</{if $mode == 'grid'}li{else}span{/if}>

<!-- compare icon end -->
