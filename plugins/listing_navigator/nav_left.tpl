<!-- prev listing navigation on listing details > 4.9.0 -->

<div class="ln-item ln-{$tpl_settings.listing_details_nav_mode} ln-item-prev align-self-center{if !$lnp_data_prev} ln-hidden{/if}">
    {if $lnp_data_prev}
        <a title="{$lang.listingNav_prev}{if $lnp_data_prev.listing_title}: {$lnp_data_prev.listing_title}{/if}"
           href="{$lnp_data_prev.href}"
           class="d-flex align-items-baseline{if !$tpl_settings.listing_details_nav_mode} mb-3{elseif $tpl_settings.listing_details_nav_mode == 'h1_mixed'} mr-3{/if}">
            <svg viewBox="0 0 {if $tpl_settings.listing_details_nav_mode == 'h1_mixed'}10 18{else}8 14{/if}" class="ln-item-icon details-icon-fill">
                <use xlink:href="#icon-horizontal-arrow{if $tpl_settings.listing_details_nav_mode == 'h1_mixed'}-tight{/if}"></use>
            </svg>
            {if $tpl_settings.listing_details_nav_mode != 'h1_mixed'}
                <span class="ml-2">{$lang.listingNav_prev}</span>
            {/if}
        </a>
    {else}
        <span></span>
    {/if}
</div>

<!-- prev listing navigation on listing details > 4.9.0 end -->
