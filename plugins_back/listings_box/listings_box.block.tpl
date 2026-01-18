<!-- listings boxes -->
{rlHook name='featuredTop'}

{if !empty($listings_box)}
    <ul id="listing_box_{$block.ID}" class="row featured{if $box_option.display_mode == 'grid'} lb-box-grid{/if} with-pictures">
    {foreach from=$listings_box item='featured_listing' key='key' name='listingsF'}{strip}
        {assign var='type' value=$featured_listing.Listing_type}
        {assign var='page_key' value=$listing_types.$type.Page_key}
        {if $box_option.display_mode == 'default'}
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'featured_item.tpl'}
        {elseif $box_option.display_mode == 'grid'}
            {include file=$smarty.const.RL_PLUGINS|cat:'listings_box'|cat:$smarty.const.RL_DS|cat:'listings_box.grid.tpl'}
        {/if}
    {/strip}{/foreach}
    </ul>
{else}
    {if $pages.add_listing}
        {pageUrl key='add_listing' assign='add_listing_href'}
        {assign var='link' value='<a href="'|cat:$add_listing_href|cat:'">$1</a>'}
        {$lang.no_listings_here|regex_replace:'/\[(.+)\]/':$link}
    {else}
        {phrase key='no_listings_found_deny_posting' db_check='true'}
    {/if}
{/if}
<!-- listings boxes end -->
