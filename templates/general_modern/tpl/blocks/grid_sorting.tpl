<!-- grid bavbar sorting -->

<div class="sorting">
    <div class="current{if $grid_mode == 'map'} disabled{/if}">
        {$lang.sort_by}:
        <span class="link">{$sorting[$sort_by].name}</span>
    </div>
    <ul class="fields {$sort_type}">
    {foreach from=$sorting item='field_item' key='sort_key' name='fSorting'}
        {if (isset($field_item.Details_page) && $field_item.Details_page == '0') 
            || $field_item.Type == 'checkbox'
            || $field_item.Key == 'keyword_search'
            || $field_item.Key == 'check_availability'
        }{continue}{/if}

        {if $field_item.Type|in_array:$sf_types}
            {foreach from=$sort_types key='st_key' item='st'}
                <li class="{$st_key}{if $sort_by == $sort_key} active{/if}{if $sort_type == $st_key} selected{/if}"><a rel="nofollow" title="{$lang.sort_listings_by} {$field_item.name} ({$lang[$st]})" href="{if $config.mod_rewrite}?{else}index.php?{$pageInfo.query_string}&{/if}sort_by={$sort_key}&sort_type={$st_key}">{$field_item.name}<span> ({$lang[$st]})</span></a></li>
            {/foreach}
        {else}
            <li{if $sort_by == $sort_key} class="active"{/if}><a rel="nofollow" title="{$lang.sort_listings_by} {$field_item.name}" href="{if $config.mod_rewrite}?{else}index.php?{$pageInfo.query_string}&{/if}sort_by={$sort_key}&sort_type=asc">{$field_item.name}</a></li>
        {/if}
    {/foreach}

    {rlHook name=$hookName}
    </ul>
</div>

<!-- grid bavbar sorting end -->