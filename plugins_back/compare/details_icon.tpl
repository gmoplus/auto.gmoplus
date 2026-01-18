<!-- compare listings details icon -->

{assign var='compare_in' value=false}
{if $listing_data.ID|in_array:$compare_cookie_ids}
    {assign var='compare_in' value=true}
{/if}

<span class="compare-details compare-grid-icon compare-icon{if $compare_in} active{if !$tpl_settings.svg_icon_fill} remove{/if}{/if}"
      title="{if $compare_in}{$lang.compare_remove_from_compare}{else}{$lang.compare_add_to_compare}{/if}"
      data-listing-id="{$listing_data.ID}"
      data-listing-url=""
      data-listing-title="{$pageInfo.title|escape}"
      data-listing-fields="{$compare_ad_fields}"
      {if $listing_data.Main_photo}
      data-listing-picture="{$smarty.const.RL_FILES_URL}{$listing_data.Main_photo}"
      {/if}>
    {if $tpl_settings.svg_icon_fill}
        <svg viewBox="0 0 18 18" class="icon details-icon-fill">
            <use xlink:href="#compare-ad-icon{if $compare_in}-rev{/if}"></use>
        </svg>
    {else}
        <span></span>
    {/if}
</span>

<!-- compare listings details icon end -->
