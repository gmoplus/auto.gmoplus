<!-- listing details sidebar -->

{rlHook name='listing_details_sidebar'}

<!-- seller info -->
{if !$pageInfo.Listing_details_inactive}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'listing_details_seller.tpl' sidebar=true}
{/if}
<!-- seller info end -->

<!-- listing details sidebar end -->
