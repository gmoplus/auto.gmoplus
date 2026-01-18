<!-- subcategory icon -->

<span class="{if $config.categories_icons_subcategory_position == 'left'}mr-2{else}ml-2{/if}">
    <a class="category category-icon" title="{$sub_cat.name}" href="{categoryUrl category=$sub_cat}">
        {assign var='icon_is_svg' value=false}

        {if $sub_cat.Icon|strpos:'.svg' !== false}
            {assign var='src' value=$smarty.const.RL_LIBS|cat:'icons/svg-line-set/'|cat:$sub_cat.Icon}
            {fetch file=$src}
        {else}
            <img {if $config.categories_icons_crop_module}loading="lazy" style="width: {$config.categories_icons_width}px;height: {$config.categories_icons_height}px;"{/if} src="{$smarty.const.RL_URL_HOME|cat:'files/'|cat:$sub_cat.Icon}" title="{$sub_cat.name}" alt="{$sub_cat.name}" />
        {/if}
    </a>
</span>

<!-- subcategory icon end -->
