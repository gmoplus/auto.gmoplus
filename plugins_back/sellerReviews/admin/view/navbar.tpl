<!-- Navigation bar the SellerReviews plugin tpl -->

<div id="nav_bar">
    {if !$smarty.get.action}
        <a href="javascript:" onclick="show('search')" class="button_bar">{strip}
            <span class="left"></span>
            <span class="center_search">{$lang.search}</span>
            <span class="right"></span>
        {/strip}</a>
    {else}
        <a href="{$rlBaseC}" class="button_bar">{strip}
            <span class="left"></span>
            <span class="center_list">{$lang.items_list}</span>
            <span class="right"></span>
        {/strip}</a>
    {/if}
</div>

<!-- Navigation bar the SellerReviews plugin tpl -->
