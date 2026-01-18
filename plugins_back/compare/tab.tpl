<!-- compare listings tab -->

{assign var='call_owner_floating_buttons' value=false}
{if !$is_owner && $pageInfo.Controller == 'listing_details' && $config.show_call_owner_button && $allow_contacts}
    {assign var='call_owner_floating_buttons' value=true}
{/if}

<div class="compare-ad-container hide{if $call_owner_floating_buttons} compare-ad-container-shift{/if}">
    <div class="compare-ad-list empty no-button{if $config.pg_upload_thumbnail_height > $config.pg_upload_thumbnail_width} compare-portrait{/if}">
        <div class="title"><h3>{$lang.compare_listings_to_be_compare}</h3></div>
        <ul></ul>
        <div class="empty-state">{$lang.compare_no_listings_to_compare}</div>
        <div class="button">
            <a class="button" href="{pageUrl key='compare_listings'}">{$lang.compare_compare}</a>
        </div>
    </div>
    <div class="compare-ad-tab">
        <svg viewBox="0 0 18 18" class="compare-arrows">
            <use xlink:href="#compare-arrows"></use>
        </svg>
        <svg viewBox="0 0 14 14" class="compare-close">
            <use xlink:href="#compare-close"></use>
        </svg>
        <label>
            {$lang.compare_comparison_table}
            <span class="compare-counter"></span>
        </label>
    </div>
</div>

{assign var='no_picture_ext' value='png'}
{assign var='no_picture_file' value=$smarty.const.RL_ROOT|cat:'templates/'|cat:$config.template|cat:'/img/no-picture.svg'}

{if is_file($no_picture_file)}
    {assign var='no_picture_ext' value='svg'}
{/if}

<script id="compare-list-item-view" type="text/x-jsrender">
    <li class="item two-inline left clearfix rendering[%if !img%] compare-no-picture[%/if%]" id="compare-list-[%:id%]" data-listing-id="[%:id%]">
        <a target="_blank" href="[%:url%]">
            <img src="[%if img%][%:img%][%else%]{$rlTplBase}img/no-picture.{$no_picture_ext}[%/if%]" title="[%:title%]" alt="[%:title%]" />
        </a>
        <div>
           <div class="remove delete icon" title="{$lang.compare_remove_from_compare}"></div>
           <div class="link"><a target="_blank" href="[%:url%]">[%:title%]</a></div>
           <div class="fields">[%:fields%]</div>
       </div>
    </li>
</script>

<script class="fl-js-dynamic">
lang['compare_add_to_compare'] = '{$lang.compare_add_to_compare}';
lang['compare_remove_from_compare'] = '{$lang.compare_remove_from_compare}';

{literal}
flUtil.loadScript(rlConfig['libs_url'] + 'javascript/jsRender.js', function(){
    var compareTab = new compareTabClass();
    compareTab.init({ {/literal}
        svgSupport: {if $tpl_settings.svg_icon_fill}true{else}false{/if},
        cache: {if $config.compare_cache}true{else}false{/if},
        cachePeriod: {if $config.compare_cache_period}{$config.compare_cache_period}{else}12{/if}
        {literal}
    });
});
{/literal}
</script>

<!-- compare listings tab end -->
