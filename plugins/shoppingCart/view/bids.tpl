{if $bids}
    {assign var='date_format_value' value=$smarty.const.RL_DATE_FORMAT|cat:"<br />"|cat:$config.shc_time_format}

    <div class="content-padding">
        <div class="list-table">
            <div class="header">
                <div class="center" style="width: 40px;">#</div>
                <div>{$lang.shc_bidder}</div>
                <div style="width: 120px;">{$lang.shc_bid_amount}</div>
                <div style="width: 150px;">{$lang.shc_bid_time}</div>
                {if $isLogin}<div style="width: 30px;"></div>{/if}
            </div>

            {foreach from=$bids item='item' name='bidF'}
                <div class="row" id="bid-{$item.ID}">
                    <div class="center iteration no-flex">{$smarty.foreach.bidF.iteration}</div>
                    <div data-caption="{$lang.shc_bidder}" class="nr">{if $item.Buyer_ID == 0}{$lang.shc_auto_bid}{else}{$item.bidder}{/if}</div>
                    <div data-caption="{$lang.shc_bid_amount}" class="shc_price">{strip}
                        {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
                        {$item.Total|number_format:2:'.':','}
                        {if $config.system_currency_position == 'after'}&nbsp;{$config.system_currency}{/if}
                    {/strip}</div>
                    <div data-caption="{$lang.shc_bid_time}">{$item.Date|date_format:$date_format_value}</div>
                    {if $isLogin}
                        <div class="center">
                            {if ($account_info.ID == $item.Buyer_ID && $config.shc_auction_cancel_bid_buyer) || ($account_info.ID == $item.Dealer_ID && $config.shc_auction_cancel_bid_seller)}
                                <a class="close-red cancel-bid" data-item="{$item.ID}" data-auction-id="{$listing_data.ID}"  href="javascript://;">
                                    <svg viewBox="0 0 18 18" class="icon grid-icon-fill">
                                        <use xlink:href="#close_icon"></use>
                                    </svg>
                                </a>
                            {/if}
                        </div>
                    {/if}
                </div>
            {/foreach}
        </div>
    </div>
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
{else}
    <div class="text-notice">{$lang.shc_no_bids}</div>
{/if}
