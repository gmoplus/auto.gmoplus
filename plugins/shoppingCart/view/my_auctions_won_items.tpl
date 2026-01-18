<!-- my auction won items -->

{assign var='date_format_value' value=$smarty.const.RL_DATE_FORMAT|cat:"<br />"|cat:$config.shc_time_format}

{foreach from=$auctions item='item' name='auctionF'}
    {math assign='iteration' equation='(((current?current:1)-1)*per_page)+iter' iter=$smarty.foreach.auctionF.iteration current=$pInfo.current per_page=$config.shc_orders_per_page}

    <div class="row">
        <div class="center iteration no-flex">{$iteration}</div>
        <div data-caption="{$lang.item}">
            <a class="d-flex" href="{$item.item_details.listing_link}" target="_blank">
                {if $item.item_details.Main_photo}<img class="shc-item-picture mr-2" alt="{$item.item_details.listing_title}" src="{$smarty.const.RL_FILES_URL}{$item.item_details.Main_photo}" />{/if}{$item.item_details.listing_title}
            </a>
        </div>
        <div class="center" data-caption="{$lang.shc_bids}">
            <span>{$item.item_details.shc_total_bids}</span>
        </div>
        <div class="center" data-caption="{$lang.shc_total}">
            <span class="shc_price">{$item.Total}</span>
        </div>
        <div class="center" data-caption="{$lang.date}">
            {$item.Date|date_format:$smarty.const.RL_DATE_FORMAT}
        </div>
        <div class="center" data-caption="{$lang.status}">
            <span class="item_{$item.Status}">{$lang[$item.Status]}</span>
            {if $item.Cash}<small class="cash">{$lang.shc_payment_cash}</small>{/if}
        </div>
        <div class="align-center" data-caption="{$lang.actions}">
            {if $item.Status == 'unpaid'}
                <a href="{pageUrl page='shc_auction_payment' vars='item='|cat:$item.ID}">
                    {$lang.checkout}
                </a>
                <div class="align-center">{$lang.or}</div>
            {/if}
            <a title="{$lang.view_details}" href="{pageUrl page='shc_auctions' add_url='mode='|cat:$auction_mod vars='item='|cat:$item.ID}">
                {$lang.view_details}
            </a>
        </div>
    </div>
{/foreach}

<!-- my auction won items end -->
