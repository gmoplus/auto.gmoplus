<div class="container auction-popup-info">
    <div class="table-cell small d-flex">
        <div class="name">
            {$lang.shc_bids}
        </div>
        <div class="value">
            {$auctionInfo.shc.total_bids}
        </div>
    </div>
    <div class="table-cell small d-flex">
        <div class="name">
            {$lang.shc_current_bid}
        </div>
        <div class="value">
            {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
            {$auctionInfo.shc.current_bid|number_format:2:'.':','}
            {if $config.system_currency_position == 'after'}&nbsp;{$config.system_currency}{/if}
        </div>
    </div>
    <div class="table-cell small d-flex">
        <div class="name">
            {$lang.shc_time_left}
        </div>
        <div class="value">
            {$auctionInfo.shc.time_left}
        </div>
    </div>
    <div class="table-cell small">
        <a target="_blank" href="{$auctionInfo.listing_link}">{$lang.shc_go_to_auction}</a>
     </div>
</div>
