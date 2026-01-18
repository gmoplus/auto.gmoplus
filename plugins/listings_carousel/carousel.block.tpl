<!-- carousel block -->

{if $listings_carousel}
    {rlHook name='featuredTop'}

    {assign var='carousel_option' value=$carousel_options[$block.ID]}
    {assign var='phrase_sale' value='listing_fields+name+sale_rent_1'}

    {if $config.rl_version|version_compare:"4.9.3" >= 0}
        {assign var='carouselPrefix' value='f-'}
    {/if}

    <div id="carousel_{$block.Key}" class="{$carouselPrefix}carousel {$carousel_option.Direction}">
        <div class="carousel_block {$carouselPrefix}carousel__viewport overflow-hidden position-relative m-0">
        <ul class="{$carouselPrefix}carousel__track featured with-pictures row flex-nowrap">
            {foreach from=$listings_carousel item='featured_listing' key='key'}
                {assign var='type' value=$featured_listing.Listing_type}
                {assign var='page_key' value=$listing_types.$type.Page_key}
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'featured_item.tpl'}
            {/foreach}
        </ul>
        </div>
    </div>

    <script class="fl-js-dynamic">
    {literal}

    if (typeof rlCarousel == "undefined") {
        var rlCarousel = new Array();
    }
    {/literal}rlCarousel['carousel_{$block.Key}'] = {$carousel_option.Number}-{$listings_carousel|@count};{literal}
    $(document).ready(function(){
        $("#{/literal}carousel_{$block.Key}{literal}").carousel({
            prefix: {/literal}'{$carouselPrefix}'{literal},
            box_key: {/literal}'{$block.Key}'{literal},
            options: {/literal}'{$block.options}'{literal},
            side_bar_exists: {/literal}'{$side_bar_exists}'{literal},
            priceTag: {/literal}{if $tpl_settings.featured_price_tag}true{else}false{/if}{literal},
            vertical: {/literal}{if $carousel_option.Direction == 'vertical'}true{else}false{/if}{literal},
            circular: {/literal}{if $carousel_option.Round == 1}true{else}false{/if}{literal},
            direction: {/literal}'{$smarty.const.RL_LANG_DIR}'{literal},
            visible: {/literal}{$carousel_option.Visible}{literal},
            scroll: {/literal}{$carousel_option.Per_slide}{literal},
            number: {/literal}{$carousel_option.Number}{literal},
            count: {/literal}{$listings_carousel|@count}{literal},
            auto: {/literal}{if $carousel_option.Delay > 0}{$carousel_option.Delay}000{else}null{/if}{literal}
        });
    });
    {/literal}
    </script>
{else}
    {if $listing_types.$type.Page && $pages.add_listing}
        {pageUrl assign='href' key='add_listing'}
        {assign var='link' value='<a href="'|cat:$href|cat:'">$1</a>'}
        {$lang.no_listings_here|regex_replace:'/\[(.+)\]/':$link}
    {else}
        {phrase key='no_listings_here_submit_deny' db_check=true}
    {/if}
{/if}

<!-- carousel block end -->
