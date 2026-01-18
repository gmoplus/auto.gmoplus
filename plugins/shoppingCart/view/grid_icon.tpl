{if $config.shc_module}
    {if $listing.shc_mode == 'fixed'}
        <li class="add-to-cart" title="{$lang.shc_add_to_cart}" id="shc-item-{$listing.ID}" data-item-id="{$listing.ID}">
            <svg viewBox="0 0 18 18" class="icon grid-icon-fill">
                <use xlink:href="#add-to-cart-listing"></use>
            </svg>
            <span class="link">{$lang.shc_add_to_cart}</span>
        </li>
    {/if}
    {if $listing.shc_mode == 'auction'}
        <li class="auction-listing" title="{$listing.shc_total_bids|@intval} {$lang.shc_bids}, {$lang.shc_left_time}: {$listing.left_time}" id="shc-item-{$listing.ID}" data-item-id="{$listing.ID}">
            <svg viewBox="0 0 18 18" class="icon grid-icon-fill">
                <use xlink:href="#auction_bids"></use>
            </svg>
            <span class="link">{$listing.shc_total_bids|@intval} {$lang.shc_bids}{if $listing.left_time != $lang.shc_auction_closed}, {$listing.left_time}{/if}</span>
        </li>
    {/if}
{/if}
