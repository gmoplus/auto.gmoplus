{if $listing.shc_mode == 'auction'}
    {if ($listing.shc_auction_status == 'closed' || $listing.shc.time_left_value <= 0) && $listing.shc.Auction_won != ''}
        <li class="nav-icon text-nowrap qtip" title="{$lang.shc_winner} {$listing.shc.winner.Full_name}">
            <svg viewBox="0 0 18 18" class="icon shc-my-listings-icon">
                <use xlink:href="#auction_bids"></use>
            </svg>
            <a class="shc-my-listings-link" href="javascript://">
                <span>{$lang.shc_closed}</span>
            </a>
        </li>
    {/if}
    {if (($listing.shc_auction_status == 'closed' || $listing.shc.time_left_value <= 0) && $listing.shc.Auction_won == '') || $listing.shc_days <= 0}
        <li class="nav-icon text-nowrap">
            <svg viewBox="0 0 18 18" class="icon shc-my-listings-icon">
                <use xlink:href="#renew_auction"></use>
            </svg>
            <a class="shc-my-listings-link renew-auction" {$lang.shc_renew_auction} href="javascript://" id="renew_auction-{$listing.ID}">
                <span>{$lang.shc_renew_auction}</span>
            </a>
        </li>
    {/if}
    {if $listing.shc_auction_status == 'active' && $listing.shc.Auction_won <= 0 && $listing.shc.time_left > 0}
        <li class="nav-icon text-nowrap" title="{$listing.shc_total_bids} {$lang.shc_bids}">
            <svg viewBox="0 0 18 18" class="icon shc-my-listings-icon">
                <use xlink:href="#auction_bids"></use>
            </svg>
            <a class="shc-my-listings-link" href="{$listing.url}#bids">
                <span>{$listing.shc_total_bids} {$lang.shc_bids}</span>
            </a>
        </li>
        {if $listing.shc_total_bids > 0}
        <li class="nav-icon text-nowrap">
            <svg viewBox="-2 -2 14 14" class="icon shc-my-listings-icon">
                <use xlink:href="#close_icon"></use>
            </svg>
            <a class="shc-my-listings-link close-auction" title="{$lang.shc_close_auction}" href="javascript://" id="close_auction-{$listing.ID}">
                <span>{$lang.shc_close_auction}</span>
            </a>
        </li>
        {/if}
    {/if}
{/if}
