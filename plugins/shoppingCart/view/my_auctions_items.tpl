<!-- my auctions items tpl -->

{assign var='date_format_value' value=$smarty.const.RL_DATE_FORMAT|cat:"<br />"|cat:$config.shc_time_format}

{foreach from=$auctions item='item' name='auctionF'}
    {math assign='iteration' equation='(((current?current:1)-1)*per_page)+iter' iter=$smarty.foreach.auctionF.iteration current=$pInfo.current per_page=$config.shc_orders_per_page}

    <div class="row">
        <div class="center iteration no-flex">{$iteration}</div>
        <div data-caption="{$lang.item}">
            <a class="d-flex" href="{$item.listing_link}" target="_blank">
                {if $item.Main_photo}<img  alt="{$item.listing_title}" src="{$smarty.const.RL_FILES_URL}{$item.Main_photo}" class="shc-item-picture mr-2" />{/if}{$item.listing_title}
            </a>
        </div>
        <div class="center" data-caption="{$lang.shc_bids}">
            <span>{$item.shc_total_bids}</span>
        </div>
        <div class="center" data-caption="{$lang.shc_your_bid_total}">
            <span class="shc_price {if $auction_mod && $auction_mod == 'live'}{if $item.my_total_price < $item.total}behind{else}ahead{/if}{/if}">{$item.my_total_price}</span>
        </div>
        <div class="center" data-caption="{if $auction_mod == 'live'}{$lang.shc_time_left}{else}{$lang.shc_auction_date_close}{/if}">
            {if $auction_mod == 'live'}
                {$item.time_left}
            {else}
                {if !$item.End_time || $item.End_time == '0000-00-00 00:00:00'}
                    {$lang.shc_not_processed}
                {else}
                    {$item.End_time|date_format:$date_format_value} 
                {/if}
            {/if}
        </div>
        <div class="center" data-caption="{$lang.shc_current_bid}">
            <span class="price-cell shc_price">{$item.total}</span>
        </div>
        {if $auction_mod == 'live'}
            <div data-caption="{$lang.actions}">
                <a title="{$lang.view_details}" href="{pageUrl page='shc_auctions' add_url='mode='|cat:$auction_mod vars='item='|cat:$item.ID}">
                    {$lang.view_details}
                </a>
            </div>
        {/if}
    </div>
{/foreach}

<!-- my auctions items end -->
