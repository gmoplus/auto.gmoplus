<!-- Shopping Cart Plugin -->

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='shc_item_details' name=$lang.shc_order_details}

<div class="auction-item-details row">
    <div class="col-md-4">
        {if $auction_info.item.Main_photo}
            <div class="preview" style="padding-bottom: 20px;">
                <a href="{$auction_info.item.listing_link}"><img alt="" src="{$smarty.const.RL_FILES_URL}{$auction_info.item.Main_photo}" /></a>
            </div>
        {/if}
    </div>
    <div class="col-md-8">
        <div class="table-cell">
            <div class="name"><div><span>{$lang.shc_order_key}</span></div></div>
            <div class="value">{$auction_info.Order_key}</div>
        </div>
        <div class="table-cell">
            <div class="name"><div><span>{$lang.shc_auction_date_close}</span></div></div>
            <div class="value">{$auction_info.item.End_time|date_format:$smarty.const.RL_DATE_FORMAT}</div>
        </div>
        <div class="table-cell">
            <div class="name"><div><span>{$lang.item}</span></div></div>
            <div class="value"><a href="{$auction_info.item.listing_link}">{$auction_info.item.listing_title}</a></div>
        </div>
        <div class="table-cell">
            {if $atype == 'buyer'}
                <div class="name"><div><span>{$lang.shc_buyer}</span></div></div>
                <div class="value">
                    {if $auction_info.bOwn_address}
                        <a target="_blank" href="{$rlBase}{$auction_info.bUsername}/">{$auction_info.bUsername}</a>
                    {else}
                        {$auction_info.bUsername}
                    {/if}
                </div>
            {else}
                <div class="name"><div><span>{$lang.shc_dealer}</span></div></div>
                <div class="value">
                    {if $auction_info.dOwn_address}
                        <a target="_blank" href="{$rlBase}{$auction_info.dOwn_address}/">{$auction_info.dUsername}</a>
                    {else}
                        {$auction_info.dUsername}
                    {/if}
                </div>
            {/if}
        </div>
        <div class="table-cell">
            <div class="name"><div><span>{$lang.total}</span></div></div>
            <div class="value shc_price">{$auction_info.Total}</div>
        </div>
        <div class="table-cell">
            <div class="name">
                <span>{$lang.status}</span>
            </div>
            <div class="value">
                <span class="item_{$auction_info.Status}">{$lang[$auction_info.Status]}</span>
                {if $auction_info.Cash}<small class="cash">&nbsp;({$lang.shc_payment_cash})</small>{/if}
                {if $auction_info.Status == 'unpaid'}
                    <a href="{pageUrl page='shc_auction_payment' vars='item='|cat:$auction_info.ID}">{$lang.checkout}</a>
                {/if}
            </div>
        </div>
    </div>
</div>

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

{if $auction_info.Status == 'paid'}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='payment_details' name=$lang.billing_details}
        <div class="table-cell">
            <div class="name"><div><span>{$lang.payment_gateway}</span></div></div>
            <div class="value">{$auction_info.Gateway}</div>
        </div>
        <div class="table-cell">
            <div class="name"><div><span>{$lang.txn_id}</span></div></div>
            <div class="value">{$auction_info.Txn_ID}</div>
        </div>
        <div class="table-cell">
            <div class="name"><div><span>{$lang.pay_date}</span></div></div>
            <div class="value">{$auction_info.Pay_date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
        </div>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

    {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/shipping_info.tpl' order_info=$auction_info}

    {if $auction_info.Escrow_status == 'pending' && $auction_info.Buyer_ID == $account_info.ID}
        {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/order_confirm.tpl' orderInfo=$auction_info}
    {else}
        {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/order_escrow.tpl' orderInfo=$auction_info}
    {/if}
{/if}

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='shc_my_bids' name=$lang.shc_bids}
    {if $auction_info.bids}
        <div class="list-table">
            <div class="header">
                <div class="center" style="width: 40px;">#</div>
                <div>{$lang.shc_bid_amount}</div>
                <div style="width: 150px;">{$lang.date}</div>
            </div>

            {foreach from=$auction_info.bids item='item' name='bidF'}
            <div class="row">
                <div class="center iteration no-flex">{$smarty.foreach.bidF.iteration}</div>
                <div data-caption="{$lang.shc_bid_amount}" class="shc_price">{$item.Total}</div>
                <div data-caption="{$lang.shc_bid_time}">{$item.Date}</div>
            </div>
            {/foreach}
        </div>
    {else}
        <div class="text-notice">{$lang.shc_no_bids}</div>
    {/if}
{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

<!-- end Shopping Cart Plugin -->
