<!-- comparison results block -->

<div class="compare-results-box">
    {if $saved_tables}
        <ul id="compare-saved-tables">
            {foreach from=$saved_tables item='item'}
            <li data-table-id="{$item.ID}">
                <a href="{$rlBase}{if $config.mod_rewrite}{$pages.compare_listings}/{$item.Path}.html{else}?page={$pages.compare_listings}&sid={$item.Path}{/if}"
                   {if $item.ID == $saved_table.ID} class="red" {/if}>{$item.Name}</a>
                <img title="{$lang.remove}" class="remove" src="{$rlTplBase}img/blank.gif" />
            </li>
            {/foreach}
        </ul>
    {/if}

    {if !$saved_table && $compare_listings}
        <a class="button" title="{$lang.compare_save_results}" href="javascript://">{$lang.compare_save_results}</a>
    {/if}

    <span id="compare-data-message"{if $saved_tables || $compare_listings} class="hide"{/if}>{$lang.compare_no_listings_to_save}</span>
</div>

<!-- comparison results block end -->
