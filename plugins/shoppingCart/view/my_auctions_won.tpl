<!-- my auctions won tpl -->

<div class="list-table row-align-middle">
    <div class="header">
        <div style="width: 40px;">#</div>
        <div>{$lang.item}</div>
        <div style="width: 100px;">{$lang.shc_bids}</div>
        <div class="text-wrap" style="width: 100px;">{$lang.total}</div>
        <div class="text-wrap" style="width: 150px;">{$lang.date}</div>
        <div class="text-wrap" style="width: 90px;">{$lang.status}</div>
        <div class="text-wrap align-center" style="width: 120px;">{$lang.actions}</div>
    </div>

    {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/my_auctions_won_items.tpl'}
</div>

{if $auctions|@count >= $config.shc_orders_per_page}
    <div class="text-center mt-3 shc-load-more-button-cont">
        <input type="button" class="button" name="load_more_auctions" value="{$lang.load_more}" data-phrase="{$lang.load_more}" />
    </div>
{/if}

<!-- my auctions won tpl -->
