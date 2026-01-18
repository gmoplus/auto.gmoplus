<!-- Shopping Cart Plugin -->

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='shc_item_details' name=$lang.shc_order_details}

<div class="auction-item-details d-flex row">
    <div class="col-md-4">
        {if $auction_info.Main_photo}
            <div class="preview" style="padding-bottom: 20px;">
                <a href="{$auction_info.listing_link}">
                    <img alt="{$auction_info.listing_title}" src="{$smarty.const.RL_FILES_URL}{$auction_info.Main_photo}" />
                </a>
            </div>
        {/if}
    </div>
    <div class="col-md-8">
        <div class="table-cell">
            <div class="name"><div><span>{$lang.item}</span></div></div>
            <div class="value"><a href="{$auction_info.listing_link}" target="_blank">{$auction_info.title}</a></div>
        </div>
        <div class="table-cell">
            <div class="name"><div><span>{$lang.price} {$lang.shc_buy_now}</span></div></div>
            <div class="value">
                <span class="price-cell shc_price">
                    {$auction_info.price}
                </span>
            </div>
        </div>
        <div class="table-cell">
            <div class="name"><div><span>{$lang.shc_time_left}</span></div></div>
            <div class="value">{$auction_info.shc.time_left}</div>
        </div>
        <div class="table-cell">
            <div class="name"><div><span>{$lang.shc_bids}</span></div></div>
            <div class="value">{$auction_info.shc_total_bids}</div>
        </div>
        <div class="table-cell">
            <div class="name"><div><span>{$lang.shc_your_bid_total}</span></div></div>
            <div class="value">
                <span class="price-cell shc_price">{$auction_info.my_total_price}</span>
            </div>
        </div>
        <div class="table-cell">
            <div class="name"><div><span>{$lang.shc_current_bid}</span></div></div>
            <div class="value">
                <span class="shc_price">{$auction_info.bids[0].Total}</span>
                {if $auction_info.Reserved_price > $auction_info.bids[0].Total}
                    <span class="behind"> ({$lang.shc_reserve_not_met})</span>
                {else}
                    <span class="ahead"> ({$lang.shc_reserve_met})</span>
                {/if}
            </div>
        </div>
        <div class="table-cell">
            <div class="name"><div><span>{$lang.shc_dealer}</span></div></div>
            <div class="value">
                {if $auction_info.seller.Own_address}
                    <a target="_blank" href="{$rlBase}{$auction_info.seller.Own_address}/">{$auction_info.seller.Full_name}</a>
                {else}
                    {$auction_info.seller.Full_name}
                {/if}
            </div>
        </div>
    </div>
</div>

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='shc_my_bids' name=$lang.shc_bids}

{if $auction_info.bids}
    <div class="list-table">
        <div class="header">
            <div class="center" style="width: 40px;">#</div>
            <div>{$lang.shc_bid_amount}</div>
            <div style="width: 200px;">{$lang.shc_bid_time}</div>
            <div style="width: 70px;"></div>
        </div>

        {foreach from=$auction_info.bids item='item' name='bidF'}
        <div class="row" id="bid-{$item.ID}">
            <div class="center iteration no-flex">{$smarty.foreach.bidF.iteration}</div>
            <div data-caption="{$lang.shc_bid_amount}" class="shc_price">{$item.Total}</div>
            <div data-caption="{$lang.shc_bid_time}">{$item.Date}</div>
            <div data-caption="{$lang.actions}">
                {if ($account_info.ID == $item.Buyer_ID && $config.shc_auction_cancel_bid_buyer && $smarty.foreach.bidF.first) || ($account_info.ID == $item.Dealer_ID && $config.shc_auction_cancel_bid_seller)}
                    <a class="shc-delete-item cancel-bid" data-item="{$item.ID}" data-auction-id="{$auction_info.ID}"  href="javascript:;">
                        <img src="{$rlTplBase}img/blank.gif" class="remove" />
                    </a>
                {/if}
            </div>
        </div>
        {/foreach}
    </div>
{else}
    <div class="text-notice">{$lang.shc_no_bids}</div>
{/if}

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
<script class="fl-js-dynamic">
    {literal}
    $(document).ready(function() {
        $('a.cancel-bid').each(function() {
            $(this).flModal({
                caption: '',
                content: '{/literal}{$lang.shc_do_you_want_cancel_bid}{literal}',
                prompt: 'shoppingCart.cancelBid('+ $(this).attr('data-item') + ', '+ $(this).attr('data-auction-id') + ')',
                width: 'auto',
                height: 'auto'
            });
        });
    });
    {/literal}
</script>

<!-- end Shopping Cart Plugin -->
