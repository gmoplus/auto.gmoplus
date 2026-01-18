<!-- home page content tpl -->

<section class="horizontal-search{if $plugins.search_by_distance} sbd-exists{/if}">
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

		<div class="d-flex flex-column flex-md-row cd-form">
            {assign var='any_replace' value=`$smarty.ldelim`field`$smarty.rdelim`}

            {if $cdt_count > 0}
                <select name="f[Category_ID]" class="cd-dropdown">
                    <option value="">{$lang.any_field_value|replace:$any_replace:$lang.category}</option>
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

            <input class="tags-autocomplete flex-fill" type="text" placeholder="{$lang.keyword_search_hint}" name="f[keyword_search]" />

            {if $aHooks.search_by_distance}
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

                    <input class="flex-fill"
                           type="text"
                           placeholder="{if $config.sbd_search_mode == 'mixed'}{$lang.sbd_location_search_hint}{else}{$lang.sbd_zipcode}{/if}"
                           name="f[{$config.sbd_zip_field}][zip]"
                           id="location_search" />
                    <input type="hidden"
                           name="f[{$config.sbd_zip_field}][distance]"
                           value="{assign var='sbd_distance' value=','|explode:$config.sbd_distance_items}{$sbd_distance.1}" />
                    {*<select name="f[{$config.sbd_zip_field}][distance]">
                        {foreach from=','|explode:$config.sbd_distance_items item='distance'}
                            <option {if $smarty.post.block_distance == $distance}selected="selected"{elseif $distance == $config.sbd_default_distance}selected="selected"{/if} value="{$distance}">{$distance} {if $config.sbd_units == 'miles'}{$lang.sbd_mi}{else}{$lang.sbd_km}{/if}</option>
                        {/foreach}
                    </select>*}

                    <input type="hidden" name="f[{$config.sbd_zip_field}][lat]" />
                    <input type="hidden" name="f[{$config.sbd_zip_field}][lng]" />
            {/if}

            <input class="d-block d-md-none" type="submit" value="{$lang.search}" />
            <input class="button-lens d-none d-md-block" type="submit" value="" />
		</div>
    </form>
</section>

<!-- home page content tpl end -->
