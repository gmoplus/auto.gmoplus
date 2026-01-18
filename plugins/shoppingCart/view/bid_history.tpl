<!-- shoppingCart plugin -->

<div id="area_shoppingCart" class="tab_area hide">
    {if $listing_data.shc.time_left_value > 0 && $listing_data.shc_auction_status != 'closed'}
        <div class="bid-history-header mb-3">
            <span class="date">{$lang.shc_bidders}:</span> <span id="bh_bidders">{$listing_data.shc.bidders}</span>
            <span class="date ml-2">{$lang.shc_bids}:</span> <span id="bh_total_bids">{$listing_data.shc.total_bids}</span>
        </div>
    {/if}

    <div id="bid-history-list">
        {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/bids.tpl'}
    </div>
</div>

<!-- end shoppingCart plugin -->
