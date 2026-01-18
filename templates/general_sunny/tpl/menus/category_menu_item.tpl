{if $item.sub_categories || isset($item.Links_type)}
    {assign var='tag' value='div'}
{else}
    {assign var='tag' value='a'}
{/if}

<{$tag}
    {if $tag == 'div'}
    data-id="{$id}"
    data-type="{$typeKey}"
    {else}
    href="{categoryUrl category=$item}"
    {/if}
    class="d-flex align-items-center category-menu__category-item">
    <div class="category-menu__category-icon mr-2 flex-shrink-0">
        {if $item.Menu_icon}
            {fetch file=$smarty.const.RL_LIBS|cat:'icons/svg-line-set/'|cat:$item.Menu_icon}
        {else}
            <svg viewBox="0 0 24 24">
               <use xlink:href="#default-category-icon"></use>
            </svg>
        {/if}
    </div>

    <div class="category-menu__category-name ml-1 flex-fill">{$item.name}</div>

    {if $tag == 'div'}
    <svg viewBox="0 0 7 12" class="category-menu__category-arrow ml-2 flex-shrink-0">
       <use xlink:href="#arrow-right-icon"></use>
    </svg>
    {/if}
</{$tag}>
