{if $config.shc_module}
    {if $featured_listing.shc_mode == 'fixed'}
        <span class="add-to-cart" data-item-id="{$featured_listing.ID}">
            <svg id="shc-item-{$featured_listing.ID}" viewBox="0 0 18 18" class="icon grid-icon-fill" title="{$lang.shc_add_to_cart}">
                <use xlink:href="#add-to-cart-listing"></use>
            </svg>
        </span>
    {/if}
    {if $featured_listing.shc_mode == 'auction'}
        <span class="auction-listing" title="{$featured_listing.shc_total_bids|@intval} {$lang.shc_bids}, {$lang.shc_left_time}: {$featured_listing.left_time}" id="shc-item-{$featured_listing.ID}" data-item-id="{$featured_listing.ID}">
            <svg viewBox="0 0 18 18" class="icon grid-icon-fill">
                <use xlink:href="#auction_bids"></use>
            </svg>
        </span>
    {/if}
{/if}
