<!-- claim listing button -->

{if ($listing_data.cl_direct || $seller_info.cl_direct) && $listing_data.Account_ID != $account_info.ID && $config.cl_module}
    <section class="side_block stick no-header no-style cl_button" {if $tpl_settings.name == 'escort_flatty'}style="padding: 0;{/if}">
        <a class="button" style="width: 100%; text-align: center; box-sizing: border-box;" href="{pageUrl key='claim_listing' vars='id='|cat:$listing_data.ID}" title="{$lang.cl_claim_ad}">{$lang.cl_claim_ad}</a>
    </section>
{/if}

<!-- claim listing button end -->
