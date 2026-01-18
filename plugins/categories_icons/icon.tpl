<!-- categories_icons plugin -->

{if $cat.Icon && (
    ($block.Key|strpos:'ltcategories_' === 0 && $config.categories_icons_type_page)
    || $block.Key|strpos:'ltcb_' === 0
    )}
    {if $block.Key|strpos:'ltcategories_' === 0}
        {assign var='ci_icon_width' value=$config.categories_icons_width_type_page}
        {assign var='ci_icon_height' value=$config.categories_icons_height_type_page}
    {else}
        {assign var='ci_icon_width' value=$config.categories_icons_width}
        {assign var='ci_icon_height' value=$config.categories_icons_height}
    {/if}

    {assign var='lt_tmp' value=$listing_types[$cat.Type]}
    {assign var='lt_page_path' value='lt_'|cat:$lt_tmp.Key}

    <div class="{if $config.categories_icons_position == 'left'}mr-3 mb-1 mt-1{elseif $config.categories_icons_position == 'right'}ml-3 mb-1 mt-1{elseif $config.categories_icons_position == 'top'}mt-3 mb-2 flex-basis-100{elseif $config.categories_icons_position == 'bottom'}mt-2 mb-3 flex-basis-100{/if}{if isset($cat.Links_type) && ($config.categories_icons_position == 'left' || $config.categories_icons_position == 'right')} d-inline align-middle{/if}">
        <a class="category-icon" title="{$cat.name}"
        {if $ltCatBlock} 
            href="{pageUrl key=$lt_page_path custom_lang=$smarty.const.RL_LANG_CODE}"
        {else} 
            href="{categoryUrl id=$cat.ID}"
        {/if}>
            {assign var='icon_is_svg' value=false}

            {if $cat.Icon|strpos:'.svg' !== false}
                {assign var='src' value=$smarty.const.RL_LIBS|cat:'icons/svg-line-set/'|cat:$cat.Icon}
                {fetch file=$src}
            {else}
                <img {if $ci_icon_width || $ci_icon_height}loading="lazy" style="width: {$ci_icon_width}px;height: {$ci_icon_height}px;"{/if} src="{$smarty.const.RL_URL_HOME|cat:'files/'|cat:$cat.Icon}" title="{$cat.name}" alt="{$cat.name}" />
            {/if}
        </a>
    </div>
{/if}

<!-- end categories_icons plugin -->
