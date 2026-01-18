<!-- listing navigation plugin -->

{if $lnp_return_link || $lnp_data_prev || $lnp_data_next}
    <div class="ln-container d-flex ml-3 mr-3 mb-4 mt-2 justify-content-sm-around flex-wrap">
        {if $lnp_return_link}
            <div class="ln-back-link flex-fill flex-sm-grow-0 mr-md-3 mb-3 mb-sm-0">
                <a title="{$lang.back_to_search_results}" href="{$lnp_return_link}">
                    {if $smarty.const.RL_LANG_DIR == 'rtl'}&rarr;{else}&larr;{/if} {$lang.back_to_search_results}
                </a>
            </div>
        {/if}

        <div class="d-flex justify-content-between flex-fill flex-sm-grow-0">
            {if $lnp_data_prev}
                <div class="ln-prev mr-1 mr-sm-3 mr-md-5">
                    <a title="{$lang.listingNav_prev}{if $lnp_data_prev.listing_title}: {$lnp_data_prev.listing_title}{/if}" href="{$lnp_data_prev.href}">
                        <&nbsp;{$lang.listingNav_prev}
                    </a>
                </div>
            {/if}
            {if $lnp_data_next}
                <div class="ln-next ml-1 ml-sm-3 ml-md-5 text-right">
                    <a title="{$lang.listingNav_next}{if $lnp_data_next.listing_title}: {$lnp_data_next.listing_title}{/if}" href="{$lnp_data_next.href}">
                        {$lang.listingNav_next}&nbsp;>
                    </a>
                </div>
            {/if}
        </div>
    </div>
{/if}

<!-- listing navigation plugin end -->
