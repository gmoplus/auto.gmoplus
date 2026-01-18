<!-- categories header block tpl -->

{if $category.ID > 0 && $smarty.const.REALM != 'admin'}
    {foreach from=$categories item='baseCategory' name='fCats'}
        {if $cfCategoryCounts}
            {foreach from=$cfCategoryCounts item='cfCategory' key='cf_category_key'}
                {if $cfCategory.Category_ID == $baseCategory.ID && $cfCategory.Number}
                    {assign var='cfCategoryCount' value=$cfCategory.Number}
                    {break}
                {else}
                    {assign var='cfCategoryCount' value=false}
                {/if}
            {/foreach}
        {else}
            {assign var='cfCategoryCount' value=$baseCategory.Count}
        {/if}

        {assign var='countExist' value=false}
        {if $filter.Items}
            {if $cfCategoryCount}
                {assign var='countExist' value=true}
                {break}
            {/if}
        {else}
            {assign var='countExist' value=true}
        {/if}
    {/foreach}

    {math assign='bcCount' equation='count-2' count=$bread_crumbs|@count}

    <div{if !empty($categories) && $countExist} style="padding: 0 0 15px 0;"{/if}>
        {$category.name}

        {if ($cfInfo.Mode == 'search_results' || $cfInfo.Mode == 'field_bound_boxes') && $cfActiveFilters.category_id}
            {if $cfCountActiveFilters > 1}
                {buildActiveFiltersURL assign='cfBackCategoryUrl' baseURL=$cfBaseUrl}
            {else}
                {if $category.Parent_ID}
                    {assign var='cfBackCategoryUrl' value=$cfBaseUrl}
                {else}
                    {assign var='cfBackCategoryUrl' value=$cfCancelUrl}
                {/if}
            {/if}

            {if $category.Parent_ID}
                {if $config.mod_rewrite}
                    {assign var='cfBackCategoryUrl' value=$cfBackCategoryUrl|cat:'category-id:'|cat:$category.Parent_ID|cat:'/'}
                {else}
                    {assign var='cfBackCategoryUrl' value=$cfBackCategoryUrl|cat:'&cf-category-id='|cat:$category.Parent_ID}
                {/if}
            {/if}

            <a href="{$cfBackCategoryUrl}">
                <img title="{$lang.category_filter_remove_filter}"
                    alt=""
                    class="remove"
                    src="{$rlTplBase}img/blank.gif" />
            </a>
        {else}
            <a href="{strip}{if $category.Parent_ID}
                    {categoryUrl category=$bread_crumbs[$bcCount]}
                {else}
                    {pageUrl key=$pageInfo.Key}
                {/if}{/strip}"
            >
                <img title="{$lang.category_filter_remove_filter}"
                    alt=""
                    class="remove"
                    src="{$rlTplBase}img/blank.gif" />
            </a>
        {/if}
    </div>
{/if}

<!-- categories header block tpl end -->
