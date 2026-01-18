<!-- my auctions tpl -->

<div class="list-table row-align-middle">
    <div class="header">
        <div style="width: 40px;">#</div>
        <div>{$lang.item}</div>
        <div style="width: 60px;">{$lang.shc_bids}</div>
        <div class="text-wrap" style="width: 160px;">{$lang.shc_your_bid_total}</div>
        <div class="text-wrap" style="width: 150px;">{if $auction_mod == 'live'}{$lang.shc_time_left}{else}{$lang.shc_auction_date_close}{/if}</div>
        <div class="text-wrap" style="width: 120px;">{$lang.shc_current_bid}</div>
        {if $auction_mod == 'live'}
            <div class="text-wrap" style="width: 100px;">{$lang.actions}</div>
        {/if}
    </div>

    {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/my_auctions_items.tpl'}
</div>

{if $auctions|@count >= $config.shc_orders_per_page}
    <div class="text-center mt-3 shc-load-more-button-cont">
        <input type="button" class="button" name="load_more_auctions" value="{$lang.load_more}" data-phrase="{$lang.load_more}" />
    </div>
{/if}

<!-- my auctions tpl end -->
