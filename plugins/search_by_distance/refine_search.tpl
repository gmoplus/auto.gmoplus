<!-- search by distance | refine search -->

<div class="col-sm-12">
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='sbdAdvanced' name=$lang.sbd_fieldset_caption}

    <div class="search-item single-field{if $search_types|@count == 1} hide{/if}">
        <div class="field">{$lang.listing_type}</div>

        {if $pageInfo.prev|strpos:'lt_' === 0}
            {assign var='prev_page_type' value=$pageInfo.prev|replace:'lt_':''}
        {/if}
        <select name="search_type">
            {foreach from=$search_types item='search_type'}
                <option value="{$search_type.Key}"{if $prev_page_type && $prev_page_type == $search_type.Key} selected="selected"{/if}>{$search_type.name}</option>
            {/foreach}
        </select>
    </div>

    {if $search_forms}
    <div class="search-forms">
        {foreach from=$search_forms item='search_form' name='searchF'}{strip}
            <form id="form_{$search_form.listing_type}" class="{if (!$prev_page_type && !$smarty.foreach.searchF.first) || ($prev_page_type && $prev_page_type != $search_form.listing_type)}hide{/if} {if $tpl_settings.sass_styles}row{/if}">
                {assign var='post_form_key' value=$search_form.listing_type}
                {foreach from=$search_form.data item='group'}
                    {if $group.Fields.0.Key == $config.sbd_zip_field
                     || $group.Fields.0.Key == $config.sbd_country_field
                     || (bool) preg_match('/^'|cat:$config.sbd_country_field|cat:'_level/', $group.Fields.0.Key)}
                        {continue}
                    {/if}

                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fields_search_box.tpl' fields=$group.Fields}
                {/foreach}
            </form>
        {/strip}{/foreach}
    </div>
    {/if}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

    <script class="fl-js-dynamic">
    {literal}

    $('select[name=search_type]').change(function(){
        $('div.search-forms > form').hide();

        var key = $(this).val();
        $('div.search-forms > #form_' + key).show();
    });

    {/literal}
    </script>
</div>

<!-- search by distance | refine search end -->
