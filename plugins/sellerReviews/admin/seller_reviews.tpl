<!-- SellerReviews system controller tpl -->

{include file=$smarty.const.RL_PLUGINS|cat:'sellerReviews/admin/view/navbar.tpl'}

{if $smarty.get.action && $smarty.get.action === 'edit'}
    {include file=$smarty.const.RL_PLUGINS|cat:'sellerReviews/admin/view/edit_form.tpl'}
{else}
    {include file=$smarty.const.RL_PLUGINS|cat:'sellerReviews/admin/view/search.tpl'}
    {include file=$smarty.const.RL_PLUGINS|cat:'sellerReviews/admin/view/comments.tpl'}
{/if}

<!-- SellerReviews system controller tpl -->
