<!-- home page content tpl -->

<section class="horizontal-search{if $plugins.search_by_distance} sbd-exists{/if}">
    <div class="point1">
        {if $config.home_page_h1}
            <h1>{if $pageInfo.h1}{$pageInfo.h1}{else}{$pageInfo.name}{/if}</h1>
        {/if}

        {if is_array($category_dropdown_types)}
            {assign var='cdt_count' value=$category_dropdown_types|@count}

            {if $cdt_count == 1}
                {assign var='spage_key' value=$listing_types[$category_dropdown_types.0].Page_key}
                {assign var='spage_path' value=$pages[$spage_key]}
            {elseif $cdt_count > 1}
                {assign var='spage_path' value=`$smarty.ldelim`type`$smarty.rdelim`}
            {/if}
        {/if}

        <form accesskey="{pageUrl key='search'}#keyword_tab" method="post" action="{if $cdt_count == 0}{pageUrl key='search'}{else}{$rlBase}{if $config.mod_rewrite}{$spage_path}/{$search_results_url}.html{else}?page={$spage_path}&{$search_results_url}{/if}{/if}">
            <input type="hidden" name="action" value="search" />
            <input type="hidden" name="form" value="keyword_search" />
            <input type="hidden" name="post_form_key" value="{if $cdt_count == 1}{$category_dropdown_types.0}_{if $listing_types[$category_dropdown_types.0].Advanced_search}advanced{else}quick{/if}{/if}" />

            <div id="search_area">{strip}
                <div class="search-group">
                    {assign var='any_replace' value=`$smarty.ldelim`field`$smarty.rdelim`}
                    <input class="tags-autocomplete" type="text" placeholder="{$lang.keyword_search_hint}" name="f[keyword_search]" />

                    {if $cdt_count > 0}
                        <select name="f[Category_ID]">
                            <option value=""></option>
                        </select>

                        <script class="fl-js-dynamic">
                        var categoryDropdownTypes = {if $cdt_count == 1}'{$category_dropdown_types.0}'{elseif is_array($category_dropdown_types)}Array('{"', '"|@implode:$category_dropdown_types}'){else}''{/if};
                        var categoryDropdownData = null;

                        {if $cdt_count > 1}
                            categoryDropdownData = new Array();
                            {foreach from=$category_dropdown_types item='dropdown_type' name='fSearchForms'}
                                {assign var='type_page_key' value=$listing_types[$dropdown_type].Page_key}

                                categoryDropdownData.push({literal} { {/literal}
                                    ID: '{$dropdown_type}',
                                    Key: '{$dropdown_type}',
                                    Link_type: '{$listing_types[$dropdown_type].Links_type}',
                                    Path: '{$pages.$type_page_key}',
                                    name: '{phrase key='pages+name+lt_'|cat:$dropdown_type}',
                                    Sub_cat: {$smarty.foreach.fSearchForms.iteration},
                                    Advanced_search: {$listing_types[$dropdown_type].Advanced_search}
                                {literal} } {/literal});
                            {/foreach}
                        {/if}

                        {literal}

                        flUtil.loadScript(rlConfig.tpl_base  + 'js/categoryDropdown.js', function() {
                            $('section.horizontal-search select[name="f[Category_ID]"]').categoryDropdown({
                                listingTypeKey: categoryDropdownTypes,
                                typesData: categoryDropdownData,
                                phrases: { {/literal}
                                    no_categories_available: "{$lang.no_categories_available}",
                                    select: "{$lang.any_field_value|replace:$any_replace:$lang.category}",
                                    select_category: "{$lang.any_field_value|replace:$any_replace:$lang.category}"
                                {literal} }
                            });
                        });

                        {/literal}
                        </script>
                    {/if}
                </div>

                {if $plugins.search_by_distance}
                    {addJS file=$smarty.const.RL_PLUGINS_URL|cat:'search_by_distance/static/lib.js'}
                    {include file=$smarty.const.RL_PLUGINS|cat:'search_by_distance'|cat:$smarty.const.RL_DS|cat:'config.js.tpl'}

                    <script class="fl-js-dynamic">
                    var sbd_zip_field = '{$config.sbd_zip_field}';

                    {literal}
                    $(function(){
                        if (typeof sbdLocationAutocomplete != 'undefined') {
                            sbdLocationAutocomplete('.horizontal-search.sbd-exists input#location_search', sbd_zip_field);
                        }
                    });
                    {/literal}
                    </script>

                    <div class="location-group">
                        <input type="text" placeholder="{if $config.sbd_search_mode == 'mixed'}{$lang.sbd_location_search_hint}{else}{$lang.sbd_zipcode}{/if}" name="f[{$config.sbd_zip_field}][zip]" id="location_search" />
                        <select name="f[{$config.sbd_zip_field}][distance]">
                            {foreach from=','|explode:$config.sbd_distance_items item='distance'}
                                <option {if $smarty.post.block_distance == $distance}selected="selected"{elseif $distance == $config.sbd_default_distance}selected="selected"{/if} value="{$distance}">{$distance} {if $config.sbd_units == 'miles'}{$lang.sbd_mi}{else}{$lang.sbd_km}{/if}</option>
                            {/foreach}
                        </select>

                        <input type="hidden" name="f[{$config.sbd_zip_field}][lat]" />
                        <input type="hidden" name="f[{$config.sbd_zip_field}][lng]" />
                    </div>
                {/if}

                <div class="submit-group">
                    <input type="submit" value="{$lang.search}" />
                </div>
            {/strip}</div>
        </form>
    </div>
</section>

<!-- home page content tpl end -->
