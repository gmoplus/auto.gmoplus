<!-- next listing navigation on listing details > 4.9.0 -->

<div class="ln-item ln-{$tpl_settings.listing_details_nav_mode} ln-item-next align-self-center{if !$lnp_data_next} ln-hidden{/if}">
    {if $lnp_data_next}
        <a title="{$lang.listingNav_next}{if $lnp_data_next.listing_title}: {$lnp_data_next.listing_title}{/if}"
           href="{$lnp_data_next.href}"
           class="d-flex align-items-baseline justify-content-end text-right{if !$tpl_settings.listing_details_nav_mode} mb-3{elseif $tpl_settings.listing_details_nav_mode == 'h1_mixed'} mr-3{/if}">
            {if $tpl_settings.listing_details_nav_mode != 'h1_mixed'}
                <span class="mr-2">{$lang.listingNav_next}</span>
            {/if}
            <svg viewBox="0 0 {if $tpl_settings.listing_details_nav_mode == 'h1_mixed'}10 18{else}8 14{/if}" class="ln-item-icon details-icon-fill">
                <use xlink:href="#icon-horizontal-arrow{if $tpl_settings.listing_details_nav_mode == 'h1_mixed'}-tight{/if}"></use>
            </svg>
        </a>
    {else}
        <span></span>
    {/if}
</div>

<!-- next listing navigation on listing details > 4.9.0 end -->
