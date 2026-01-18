<!-- compare table tpl -->

{addJS file=$smarty.const.RL_LIBS_URL|cat:'javascript/jsRender.js'}
{addJS file=$smarty.const.RL_PLUGINS_URL|cat:'compare/static/page-lib.js'}

{assign var='no_picture_ext' value='png'}
{assign var='no_picture_file' value=$smarty.const.RL_ROOT|cat:'templates/'|cat:$config.template|cat:'/img/no-picture.svg'}

{if is_file($no_picture_file)}
    {assign var='no_picture_ext' value='svg'}
{/if}

<div class="highlight">
    {if $compare_listings}
        <!-- fields column -->
        <div class="compare-table two-inline left clearfix">
            <div class="fields-column">
                <div class="table">
                    {foreach from=$compare_fields item='c_field'}
                        <div class="item name">{$c_field.name}</div>
                    {/foreach}
                </div>
            </div>
            <div class="fields-content">
                <div class="scroll">
                    <div class="table">
                    {foreach from=$compare_fields item='c_field'}
                        <div class="in{if $c_field.Key == 'listing_title'} title sticky{elseif $c_field.Key == 'Main_photo'} sticky{/if}" data-field="{$c_field.name}">
                        {foreach from=$compare_listings item='compare_listing'}
                            {if $c_field.Key == 'Main_photo'}
                                <div class="item sticky" id="compare-item-{$compare_listing.ID}">
                                    <div class="preview{if !$compare_listing.Main_photo} preview_no-picture{/if}">
                                        <a href="{$compare_listing.listing_link}">
                                            <img alt="{$compare_listing.listing_title}"
                                             src="{strip}
                                                {if $compare_listing.Main_photo}
                                                    {$smarty.const.RL_FILES_URL}{$compare_listing.Main_photo}
                                                {else}
                                                    {$rlTplBase}img/no-picture.{$no_picture_ext}
                                                {/if}
                                             {/strip}" />
                                        </a>
                                        {if $delete_allowed}
                                            <div class="remove delete icon" id="remove_from_compare_{$compare_listing.ID}"></div>
                                        {/if}
                                    </div>
                                </div>
                            {elseif $c_field.Key == 'listing_title'}
                                <div class="item">
                                    <a href="{$compare_listing.listing_link}">{$compare_listing.listing_title}</a>
                                </div>
                            {else}
                                <div class="item{if $c_field.Key == $config.price_tag_field} price{/if}">
                                    {if $compare_listing.fields[$c_field.Key].value != ''}
                                        {$compare_listing.fields[$c_field.Key].value}
                                    {else}
                                        -
                                    {/if}
                                </div>
                            {/if}
                        {/foreach}
                        </div>
                    {/foreach}
                    </div>
                </div>
            </div>
        </div>
        <!-- fields column end -->

        <span id="no-ads-state" class="text-notice content-padding hide">{$lang.compare_no_listings_to_compare}</span>

        <script id="compare-fullscreen-view" type="text/x-jsrender">
            <div class="compare-fullscreen-area">
                <div class="compare-header">
                    <div class="two-inline left clearfix">
                        <div><h1>{$lang.compare_comparison_table}</h1></div>
                        <div class="ralign">
                            <a class="button compare-default" title="" href="javascript:void(0)">
                                <span>{$lang.compare_default_view}</span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="compare-body table-hidden"></div>
            </div>
        </script>
    {elseif $saved_table && !$compare_listings}
        <span class="text-notice content-padding">{$lang.compare_listings_unavailable}</span>
    {else}
        <span class="text-notice content-padding">{$lang.compare_no_listings_to_compare}</span>
    {/if}

    <script class="fl-js-dynamic">
        lang['compare_remove_notice']       = "{$lang.compare_remove_notice}";
        lang['compare_delete_table_notice'] = "{$lang.compare_delete_table_notice}";
        lang['compare_save_results']        = "{$lang.compare_save_results}";
        lang['compare_table_removed']       = "{$lang.compare_table_removed}";

        var compare = new compareClass();
        compare.init({literal}{{/literal}
            cache: {if $config.compare_cache}true{else}false{/if},
            savedTable: {if $saved_table}{$saved_table.ID}{else}0{/if},
        {literal}}){/literal};
        </script>
</div>

<div class="tmp-dom hide" id="compare-save-container">
    <div class="content-padding">
        {if $isLogin}
            <form name="save-table" action="" method="POST">
                <div class="submit-cell">
                    <div class="name">{$lang.name} <span class="red">*</span></div>
                    <div class="field single-field">
                        <input size="32" maxlength="32" type="text" name="name" value="{$smarty.post.name}" />
                        <div class="red error-info hide">{$lang.compare_name_error}</div>
                    </div>
                </div>

                <div class="submit-cell">
                    <div class="name">{$lang.compare_save_as}</div>
                    <div class="field inline-fields">
                        <span class="custom-input">
                            <label><input type="radio" name="type" value="private" checked="checked" /> {$lang.compare_private}</label>
                        </span>
                        <span class="custom-input">
                            <label><input type="radio" name="type" value="public" /> {$lang.compare_public}</label>
                        </span>
                    </div>
                </div>

                <div class="submit-cell buttons">
                    <div class="name"></div>
                    <div class="field"><input type="submit" data-value="{$lang.save}" value="{$lang.save}" /></div>
                </div>
            </form>
        {else}
            <div class="compare-notice">{$lang.compare_save_table_login_notice}</div>
            {include file='menus'|cat:$smarty.const.RL_DS|cat:'account_menu.tpl'}
        {/if}
    </div>
</div>

<!-- compare table tpl end -->
