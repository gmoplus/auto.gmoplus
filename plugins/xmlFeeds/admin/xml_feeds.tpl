{if $smarty.get.action == 'add_feed' || $smarty.get.action == 'edit_feed'}
    <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.categoryDropdown.js"></script>
{/if}

<!-- navigation bar -->
<div id="nav_bar">
    {if $smarty.get.action == 'mapping'}
        <a href="javascript:void(0)" onclick="show('add_mapping_item')" class="button_bar"><span class="left"></span><span class="center_add">{$lang.xf_add_mapping_item}</span><span class="right"></span></a>
        {if !$smarty.get.field}
            <a href="javascript:void(0)" onclick="rlConfirm( '{$lang.delete_confirm}', 'xajax_clearMapping', Array('{$smarty.get.format}') );" class="button_bar"><span class="left"></span><span class="center_remove">{$lang.xf_clear_mapping}</span><span class="right"></span></a>
        {/if}
    {/if}

    {if $smarty.get.mode == 'formats' && (!$smarty.get.action || $smarty.get.action == 'formats')}
        <a href="{$rlBaseC}action=add_format" class="button_bar"><span class="left"></span><span class="center_add">{$lang.xf_add_format}</span><span class="right"></span></a>
    {/if}

    {if $smarty.get.mode == 'feeds' || (!$smarty.get.action && !$smarty.get.mode )}
        <a href="{$rlBaseC}action=add_feed" class="button_bar"><span class="left"></span><span class="center_add">{$lang.xf_add_feed}</span><span class="right"></span></a>
        <a href="javascript:void(0)" onclick="show('filters', '#action_blocks div');" class="button_bar"><span class="left"></span><span class="center_search">{$lang.filters}</span><span class="right"></span></a>
    {/if}

    {if $smarty.get.action == 'edit_feed'}
        <a href="{$rlBaseC}action=statistics&feed={$smarty.get.feed}" class="button_bar"><span class="left"></span><span class="center_import">{$lang.xf_statistics}</span><span class="right"></span></a>
    {/if}

    {if $smarty.get.action == 'statistics'}
        <a href="{$rlBaseC}action=mapping&format={$format_info.Key}" target="_blank" class="button_bar"><span class="left"></span><span class="center">{$lang.xf_build_mapping}</span><span class="right"></span></a>
        <a href="javascript:void(0)" onclick="xajax_runFeed('{$smarty.get.feed}', '{$smarty.get.account_id}', $('#debug_local_field').val(), $('#debug_listing_id').val(), $('#debug_xml_ref').val() )" class="button_bar"><span class="left"></span><span class="center_import">{$lang.xf_run_import}</span><span class="right"></span></a>
        <a target="_blank" href="{$rlBase}index.php?controller=listings&amp;feed={$smarty.get.feed}{if $smarty.get.account_id}&amp;username={$account_username}{/if}" class="button_bar"><span class="left"></span><span class="center_search">{$lang.xf_view_listings}</span><span class="right"></span></a>
        <a href="javascript:void(0)" onclick="xajax_clearStatistics('{$smarty.get.feed}'{if $smarty.get.account_id}, {$smarty.get.account_id} {/if})" class="button_bar"><span class="left"></span><span class="center_remove">{$lang.xf_clear_statistics}</span><span class="right"></span></a>
        <a href="{$rlBaseC}action=edit_feed&feed={$smarty.get.feed}" class="button_bar"><span class="left"></span><span class="center_edit">{$lang.xf_edit_feed}</span><span class="right"></span></a>
    {/if}

    {if $formats_mode}
    {else}
        {if $smarty.get.mode != 'feeds' && ($smarty.get.mode || $smarty.get.action)}
            <a href="{$rlBaseC}mode=feeds" class="button_bar"><span class="left"></span><span class="center_list">{$lang.xf_manage_feeds}</span><span class="right"></span></a>
        {/if}
        {if $smarty.get.mode != 'formats'}
             <a href="{$rlBaseC}mode=formats" class="button_bar"><span class="left"></span><span class="center_list">{$lang.xf_manage_formats}</span><span class="right"></span></a>
         {/if}
        {if $smarty.get.action != 'export'}
            <a href="{$rlBaseC}action=export" class="button_bar"><span class="left"></span><span class="center_export">{$lang.xf_export}</span><span class="right"></span></a>
        {/if}
    {/if}
</div>
<!-- navigation bar end -->

{if $info && !$errors}
<script>
    {if $info|@count > 1}
        var info_message = '<ul>{foreach from=$info item="mess"}<li>{$mess}</li>{/foreach}</ul>';
    {else}
        var info_message = '{$info.0}';
    {/if}

    {literal}
    $(document).ready(function(){
        printMessage('info', info_message);
    });
    {/literal}
</script>
{/if}

{if ($smarty.get.action == 'edit_feed' && $smarty.get.feed) || $smarty.get.action == 'add_feed'}
    {assign var='sPost' value=$smarty.post}

    <!-- add/edit -->
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <form action="{$rlBaseC}action={if $smarty.get.action == 'add_feed'}add_feed{elseif $smarty.get.action == 'edit_feed'}edit_feed&amp;feed={$smarty.get.feed}{/if}" method="post">
        <input type="hidden" name="submit" value="1" />
        {if $smarty.get.action == 'edit_feed'}
            <input type="hidden" name="fromPost" value="1" />
        {/if}

        <table class="form">
        <tr>
            <td class="name"><span class="red">*</span>{$lang.name}</td>
            <td class="field">
                <input type="text" name="name" value="{$sPost.name}" style="width: 250px;" />
            </td>
        </tr>
        <tr>
            <td class="name"><span class="red">*</span>{$lang.file_type}</td>
            <td class="field">
                <select name="file_type">
                    {foreach from=$file_types item='file_type'}
                    <option {if $sPost.file_type == $file_type}selected="selected" {/if}value="{$file_type}">{$file_type}</option>
                    {/foreach}
                </select>
            </td>
        </tr>
        <tr>
            <td class="name"><span class="red">*</span>{$lang.xf_feed_url}</td>
            <td class="field">
                <input name="url" type="text" value="{$sPost.url}" />
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.xf_read_method}</td>
            <td class="field">
                <label><input {if $sPost.access_method == 'direct'}checked="checked"{/if} class="lang_add" type="radio" name="access_method" value="direct" /> {$lang.xf_read_type_direct}</label>
                <label><input {if $sPost.access_method == 'copy' || !$sPost.access_method}checked="checked"{/if} class="lang_add" type="radio" name="access_method" value="copy" /> {$lang.xf_read_type_copy}</label>
                <label><input {if $sPost.access_method == 'stream'}checked="checked"{/if} class="lang_add" type="radio" name="access_method" value="stream" /> {$lang.xf_read_type_stream_copy}</label>

                <span class="field_description">{$lang.xf_read_hint}</span>
            </td>
        </tr>

        <tr>
            <td class="name">{$lang.xf_skip_imported}</td>
            <td class="field">
                {if $sPost.skip_imported == '1'}
                    {assign var='skip_imported_yes' value='checked="checked"'}
                {elseif $sPost.skip_imported == '0'}
                    {assign var='skip_imported_no' value='checked="checked"'}
                {else}
                    {assign var='skip_imported_no' value='checked="checked"'}
                {/if}
                <label><input {$skip_imported_yes} type="radio" name="skip_imported" value="1" /> {$lang.yes} </label>
                <label><input {$skip_imported_no} type="radio"  name="skip_imported" value="0" /> {$lang.no} </label>
            </td>
        </tr>
        </table>

        <div id="import_limit_cont" class="hide">
            <table class="form">
            <tr>
                <td class="name">{$lang.xf_import_limit}</td>
                <td class="field">
                    <input type="text" class="numeric" name="import_limit" value="{if $sPost.import_limit}{$sPost.import_limit}{else}0{/if}" />
                    <span class="field_description">{$lang.xf_import_limit_hint}</span>
                </td>
            </tr>
            </table>
        </div>

        <div id="update_photos_cont" class="hide">
            <table class="form">
            <tr>
                <td class="name">{$lang.xf_update_photos}</td>
                <td class="field">
                    {if $sPost.update_photos == '1'}
                        {assign var='update_photos_yes' value='checked="checked"'}
                    {elseif $sPost.update_photos == '0'}
                        {assign var='update_photos_no' value='checked="checked"'}
                    {else}
                        {assign var='update_photos_no' value='checked="checked"'}
                    {/if}
                    <label><input {$update_photos_yes} type="radio" name="update_photos" value="1" /> {$lang.yes} </label>
                    <label><input {$update_photos_no} type="radio"  name="update_photos" value="0" /> {$lang.no} </label>
                </td>
            </tr>
            </table>
        </div>

        <div id="delayed_photos_cont" class="hide">
            <table class="form">
            <tr>
                <td class="name">{$lang.xf_delayed_photos}</td>
                <td class="field">
                    {if $sPost.delayed_photos == '1'}
                        {assign var='delayed_photos_yes' value='checked="checked"'}
                    {elseif $sPost.delayed_photos == '0'}
                        {assign var='delayed_photos_no' value='checked="checked"'}
                    {else}
                        {assign var='delayed_photos_no' value='checked="checked"'}
                    {/if}
                    <label><input {$delayed_photos_yes} type="radio" name="delayed_photos" value="1" /> {$lang.yes} </label>
                    <label><input {$delayed_photos_no} type="radio"  name="delayed_photos" value="0" /> {$lang.no} </label>
                    <span class="field_description">{$lang.xf_delayed_photos_hint}</span>
                </td>
            </tr>
            </table>
        </div>

        <div id="not_delayed_photos_cont" class="hide">
            <table class="form">
            <tr>
                <td class="name">{$lang.xf_not_delayed_photos_number}</td>
                <td class="field">
                    <input type="text" class="numeric" name="not_delayed_photos" value="{if $sPost.not_delayed_photos}{$sPost.not_delayed_photos}{else}0{/if}" />
                    <span class="field_description">{$lang.xf_not_delayed_photos_number_hint}</span>
                </td>
            </tr>
            </table>
        </div>

        <table class="form">
        <tr>
            <td class="name">{$lang.xf_read_auth}</td>
            <td class="field">
                {if $sPost.http_auth == '1'}
                    {assign var='http_auth_yes' value='checked="checked"'}
                {elseif $sPost.http_auth == '0'}
                    {assign var='http_auth_no' value='checked="checked"'}
                {else}
                    {assign var='http_auth_no' value='checked="checked"'}
                {/if}
                <label><input {$http_auth_yes} type="radio" id="http_auth_yes" name="http_auth" value="1" /> {$lang.yes} </label>
                <label><input {$http_auth_no} type="radio" id="http_auth_no" name="http_auth" value="0" /> {$lang.no} </label>
            </td>
        </tr>
        </table>

        <div id="http_auth_details" class="hide">
            <table class="form">
            <tr>
                <td class="name">{$lang.xf_auth_details}</td>
                <td class="field">
                    <label><input class="lang_add" type="text" name="http_auth_login" value="{$sPost.http_auth_login}" /> {$lang.username}</label>
                    <label><input class="lang_add" type="text" name="http_auth_pass" value="{$sPost.http_auth_pass}" /> {$lang.password}</label>
                </td>
            </tr>
            </table>
        </div>

        <table class="form">
        <tr>
            <td class="name"><span class="red">*</span>{$lang.xf_format}</td>
            <td class="field">
                <select name="format">
                    <option value="0">{$lang.select}</option>
                    {foreach from=$formats item="format"}
                        <option value="{$format.Key}" {if $sPost.format == $format.Key}selected="selected"{/if}>{$format.Name}</option>
                    {/foreach}
                </select>

                {if $smarty.get.action == 'add_feed'}
                    <label>
                        <input type="checkbox" name="create_format" {if $smarty.post.create_format}checked="checked"{/if} value="1" />{$lang.xf_autocreate_format}
                    </label>
                {/if}
            </td>
        </tr>
        </table>

        {if $smarty.get.action == 'add_feed'}
            <div id="format_xpath_cont" class="hide">
                <table class="form">
                <tr>
                    <td class="name"><span class="red">*</span>{$lang.xf_xpath}</td>
                    <td class="field">
                        <input type="text" name="format_xpath" value="{$sPost.format_xpath}" style="width: 250px;" />
                        <span class="field_description"><a class="xpath-sample" href="javascript://">{$lang.xf_example}</a></span>
                    </td>
                </tr>
                </table>
            </div>
        {/if}

        <table class="form">
        <tr>
            <td class="name"><span class="red">*</span>{$lang.listing_package}</td>
            <td class="field">
                <select name="plan_id" {if $config.membership_module && !$config.allow_listing_plans}disabled="disabled" class="disabled"{/if}>
                    <option value="0">{$lang.select}</option>
                    {foreach from=$plans item="plan"}
                        <option value="{$plan.ID}" {if $sPost.plan_id == $plan.ID}selected="selected"{/if}>{$plan.name}</option>
                    {/foreach}
                </select>

                {if $config.membership_module && !$config.allow_listing_plans}
                    <span class="field_description">
                        {$lang.xf_membership_no_default_plan_allowed_hint}
                    </span>
                {/if}
            </td>
        </tr>
        {if $listing_types|@count > 1}
            <tr>
                <td class="name"><span class="red">*</span>{$lang.listing_type}</td>
                <td class="field">
                    <select name="listing_type" id="Type">
                        <option value="0">{$lang.all}</option>
                        {foreach from=$listing_types key="key" item="listing_type" name="ltLoop"}
                            <option value="{$listing_type.Key}" {if $sPost.listing_type == $listing_type.Key}selected="selected"{/if} {if $smarty.foreach.ltLoop.first}selected="selected"{/if}>{$listing_type.name}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
        {else}
            {foreach from=$listing_types key="key" item="listing_type" name="ltLoop"}
                <input type="hidden" id="Type" value="{$listing_type.Key}" />
            {/foreach}
        {/if}
        <tr>
            <td class="name"><span class="red">*</span>{$lang.xf_default_category}</td>
            <td class="field">
                <select id="Category_ID" name="default_category">
                    <option value="0">{$lang.any}</option>
                </select>

                <span class="field_description">
                    {$lang.xf_default_category_hint}
                </span>
            </td>
        </tr>

        <tr>
            <td class="name">{$lang.xf_removed_ads_action}</td>
            <td class="field">
                <select name="removed_ads_action">
                    <option {if !$sPost.removed_ads_action}selected="selected"{/if} value="">{$lang.xf_raa_no_action}</option>
                    <option {if $sPost.removed_ads_action == 'remove'}selected="selected"{/if} value="remove">{$lang.xf_raa_remove}</option>
                    <option {if $sPost.removed_ads_action == 'expire'}selected="selected"{/if} value="expire">{$lang.xf_raa_expire}</option>
                </select>
            </td>
        </tr>

        <tr>
            <td class="name"><span class="red">*</span>{$lang.xf_sys_status}</td>
            <td class="field">
                <select name="listings_status">
                    <option {if $sPost.listings_status == 'active'}selected="selected"{/if} value="active">{$lang.active}</option>
                    <option {if $sPost.listings_status == 'approval'}selected="selected"{/if} value="approval">{$lang.approval}</option>
                </select>
            </td>
        </tr>

        <tr>
            <td class="name"><span class="red">*</span>{$lang.username}</td>
            <td class="field">
                <input type="text" name="account_id" id="account_id" value="{foreach from=$accounts item='account'}{if $sPost.account_id == $account.ID}{$account.Username}{/if}{/foreach}" />

                <span class="field_description">
                    {$lang.xf_username_hint}
                </span>

                <script type="text/javascript">
                var post_account_id = {if $sPost.account_id}{$sPost.account_id}{else}false{/if};
                {literal}
                    $('#account_id').rlAutoComplete({add_id: true, id: post_account_id});
                {/literal}
                </script>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.status}</td>
            <td class="field">
                <select name="status">
                    <option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                    <option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
                </select>
            </td>
        </tr>
        <tr>
            <td></td>
            <td class="field">
                <input type="submit" value="{if $smarty.get.action == 'edit_feed'}{$lang.edit}{else}{$lang.add}{/if}" />
            </td>
        </tr>
        </table>
    </form>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

    <script type="text/javascript">
        var category_selected = '{$smarty.post.default_category}';

        {literal}
        $(document).ready(function(){
            $('#Category_ID').categoryDropdown({
                listingType: '#Type',
                default_selection: category_selected,
                phrases: { {/literal}
                    no_categories_available: "{$lang.no_categories_available}",
                    select: "{$lang.select}",
                    select_category: "{$lang.select_category}"
                {literal} }
            });
        });
        {/literal}
    </script>

    {if $smarty.get.action == 'add_feed'}
        <script type="text/javascript">
            {literal}
            $(document).ready(function() {
                $('input[name=create_format]').click(function() {
                    formatAutoCreateHandler();
                });
                formatAutoCreateHandler();
            });

            function formatAutoCreateHandler() {
                $('select[name="format"]').removeClass('error');
                if ($('input[name=create_format]').is(':checked')) {
                    $('#format_xpath_cont').slideDown();
                    $('select[name="format"]').attr('disabled', true).addClass('disabled');
                } else {
                    $('#format_xpath_cont').slideUp();
                    $('select[name="format"]').attr('disabled', false).removeClass('disabled');
                }
            }
            {/literal}
        </script>
    {/if}

    <script type="text/javascript">
        {literal}
            $(document).ready(function(){
                httpAuthHandler();
            });

            var httpAuthHandler = function() {
                if ($('input[name=http_auth]:checked').val() == '1') {
                    $('#http_auth_details').slideDown();
                } else {
                    $('#http_auth_details').slideUp();
                }
            }
            $('input[name=http_auth]').change(function() {
                httpAuthHandler();
            });
        {/literal}
    </script>

     <script type="text/javascript">
        {literal}
            $(document).ready(function(){
                importLimitHandler();
            });

            var importLimitHandler = function() {
                if ($('input[name=skip_imported]:checked').val() == '1') {
                    $('input[name=update_photos][value=0]').attr('checked', 'checked').click();
                    updatePhotosHandler();

                    $('#import_limit_cont').slideDown();
                    $('#update_photos_cont').slideUp();
                } else {
                    $('#import_limit_cont').slideUp();
                    $('#update_photos_cont').slideDown();
                }
            }
            $('input[name=skip_imported]').change(function() {
                importLimitHandler();
            });
        {/literal}
    </script>

    <script type="text/javascript">
        {literal}
            $(document).ready(function(){
                delayedPhotosHandler();
            });

            var delayedPhotosHandler = function() {
                if ($('input[name=delayed_photos]:checked').val() == '1') {
                    $('#not_delayed_photos_cont').slideDown();
                } else {
                    $('#not_delayed_photos_cont').slideUp();
                }
            }
            $('input[name=delayed_photos]').change(function() {
                delayedPhotosHandler();
            });
        {/literal}
    </script>

    <script type="text/javascript">
        {literal}
            $(document).ready(function(){
                updatePhotosHandler();
            });

            var updatePhotosHandler = function() {
                if ($('input[name=update_photos]:checked').val() == '0') {
                    $('#delayed_photos_cont').slideDown();
                } else {
                    $('#delayed_photos_cont').slideUp();

                    $('input[name=delayed_photos][value=0]').attr('checked', 'checked').click();
                    delayedPhotosHandler();
                }
            }
            $('input[name=update_photos]').change(function() {
                updatePhotosHandler();
            });
        {/literal}
    </script>

{elseif ($smarty.get.action == 'edit_format' && $smarty.get.format) || $smarty.get.action == 'add_format'}

    {assign var='sPost' value=$smarty.post}
    <!-- add/edit -->
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <form action="{$rlBaseC}action={if $smarty.get.action == 'add_format'}add_format{elseif $smarty.get.action == 'edit_format'}edit_format&amp;format={$smarty.get.format}{/if}" method="post">
        <input type="hidden" name="submit" value="1" />
        {if $smarty.get.action == 'edit_format'}
            <input type="hidden" name="fromPost" value="1" />
        {/if}

        <table class="form">
            <tr>
                <td class="name"><span class="red">*</span>{$lang.name}</td>
                <td class="field">
                    <input type="text" name="name" value="{$sPost.name}" style="width: 250px;" />
                </td>
            </tr>
            <tr>
                <td class="name"><span class="red">*</span>{$lang.xf_xpath}</td>
                <td class="field">
                    <input name="xpath" type="text" value="{$sPost.xpath}" />
                    <span class="field_description"><a class="xpath-sample" href="javascript://">{$lang.xf_example}</a></span>
                </td>
            </tr>
            <tr>
                <td class="name"><span class="red">*</span>{$lang.xf_format_for}</td>
                <td class="field">
                    <label>
                        <input name="format_for[import]"
                               type="checkbox"
                               value="import"
                               {if $smarty.post.format_for && 'import'|in_array:$smarty.post.format_for}checked="checked"{/if}
                        /> {$lang.xf_import_label}
                    </label>
                    <label>
                        <input name="format_for[export]"
                               type="checkbox"
                               value="export"
                               {if $smarty.post.format_for && 'export'|in_array:$smarty.post.format_for}checked="checked"{/if}
                        /> {$lang.xf_export_label}
                    </label>
                </td>
            </tr>
            <tr>
                <td class="name">{$lang.status}</td>
                <td class="field">
                    <select name="status">
                        <option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                        <option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td></td>
                <td class="field">
                    <input type="submit" value="{if $smarty.get.action == 'edit_format'}{$lang.edit}{else}{$lang.add}{/if}" />
                </td>
            </tr>
        </table>
    </form>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

{elseif $smarty.get.action == 'statistics'}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <table class="lTable" style="text-align:center">
        <tr class="header">
            <td>
                <div>{$lang.xf_format}</div>
            </td>
            <td class="clear"></td>
            <td>
                <div>{$lang.listing_package}</div>
            </td>
            <td class="clear"></td>
            <td>
                <div>{$lang.username}</div>
            </td>
            <td class="clear"></td>
            <td>
                <div>{$lang.xf_default_category}</div>
            </td>
            <td class="clear"></td>
            <td>
                <div>{$lang.xf_feed_url}</div>
            </td>
        </tr>
        <tr class="body">
            <td class="list_td">
                <a target="_blank" href="{$rlBaseC}action=edit_format&format={$feed_info.Format}">{$feed_info.Format_name}</a>
            </td>
            <td class="clear" style="width: 3px;"></td>
            <td class="list_td">
                <a target="_blank" href="{$rlBase}index.php?controller=listing_plans&action=edit&id={$feed_info.Plan_ID}">{$feed_info.Plan_name}</a>
            </td>
            <td class="clear" style="width: 3px;"></td>
            <td class="list_td">
                <a target="_blank" href="{$rlBase}index.php?controller=accounts&action=view&userid={$feed_info.Account_ID}">{$feed_info.Username}</a>
            </td>
            <td class="clear" style="width: 3px;"></td>
            <td class="list_td">
                <a target="_blank" href="{$rlBase}index.php?controller=browse&id={$feed_info.Default_category}">{$feed_info.Category_name}</a>
            </td>
            <td class="clear" style="width: 3px;"></td>
            <td class="list_td">
                <a target="_blank" href="{$feed_info.Url}">{$feed_info.Url|truncate:100}</a>
            </td>
        </tr>
    </table>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

    {if isset($smarty.get.debug)}
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
        <label>Local field:
            <input type="text" value="" id="debug_local_field" />
        </label>
        <label>Listing ID:
            <input type="text" value="" id="debug_listing_id" />
        </label>
        <label>XML REF:
            <input type="text" value="" id="debug_xml_ref" />
        </label>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    {/if}

    <div id="manual_import_cont" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
            <div id="manual_import_dom"></div>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    {if $statistics}
        <table class="lTable" style="text-align:center" id="stats_table">
            <tr class="header">
                <td style="height: 24px;">
                    <div>
                        {$lang.xf_stats_date}
                    </div>
                </td>
                <td class="clear" style="width: 3px;"></td>
                <td style="height: 24px;">
                    <div>
                        {$lang.account}
                    </div>
                </td>
                <td class="clear" style="width: 3px;"></td>
                <td style="height: 24px;">
                    <div>
                        {$lang.xf_stats_updated}
                    </div>
                </td>
                <td class="clear" style="width: 3px;"></td>
                <td style="height: 24px;">
                    <div>
                        {$lang.xf_stats_inserted}
                    </div>
                </td>
                <td class="clear" style="width: 3px;"></td>
                <td style="height: 24px;">
                    <div>
                        {$lang.xf_stats_deleted}
                    </div>
                </td>
            </tr>
            <tr>
            </tr>
            {assign var="date_format" value=$smarty.const.RL_DATE_FORMAT|cat:' %H:%M'}
            {foreach from=$statistics item="entry" name="statsLoop"}
                {if $smarty.foreach.statsLoop.iteration%2 == 0}
                    {assign var="td_style" value='_light'}
                {else}
                    {assign var="td_style" value=''}
                {/if}
                <tr class="body">
                    <td class="list_td{$td_style}">
                        {$entry.Date|date_format:$date_format}
                    </td>
                    <td class="clear" style="width: 3px;"></td>

                    <td class="list_td{$td_style}">
                        <a href="{$rlBase}index.php?controller=accounts&action=view&userid={$entry.Account_ID}">{$entry.Username}</a>
                    </td>
                    <td class="clear" style="width: 3px;"></td>

                    <td class="list_td{$td_style}">
                        {$entry.Listings_updated}
                    </td>
                    <td class="clear" style="width: 3px;"></td>

                    <td class="list_td{$td_style}">
                        {$entry.Listings_inserted}
                    </td>
                    <td class="clear" style="width: 3px;"></td>

                    <td class="list_td{$td_style}">
                        {$entry.Listings_deleted}
                    </td>
                    <td class="clear" style="width: 3px;"></td>
                </tr>
            {/foreach}
        </table>
    {else}
        <div>{$lang.xf_no_stats}</div>
    {/if}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

{elseif $smarty.get.action == 'mapping' && $smarty.get.format}
    {*if !$smarty.get.field}
        help section
        xml_ref - field that is necessary to build to have update process working, import will not work without the field.
        duplicate_field - when you need to use the same field to more than one field
        divide -
        photos -
    {/if*}

    <!-- add new mapping item -->
    <div id="add_mapping_item" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.add_item}
        <table class="form">
        <tr>
            <td class="name">{$lang.xf_remote_field}</td>
            <td class="field">
                <input id="mapping_item_remote" type="text" class="w250" />

                {$lang.default}

                <input id="mapping_item_default" type="text" class="w250" />
            </td>
        </tr>

        <tr>
            <td class="name">{$lang.xf_local_field}</td>
            <td class="field">
                <select id="mapping_item_local" class="w250">
                    <option value="0">{$lang.select}</option>
                    {if $smarty.get.field}
                        {foreach from=$local_values item="local_value"}
                            <option value="{$local_value.Key}">{$local_value.name}</option>
                        {/foreach}
                    {else}
                        <optgroup label="{$lang.xf_listingfields_label}">
                        {foreach from=$listing_fields item="field"}
                            <option value="{$field.Key}" {if $field.Key == $xml_field.fl}selected="selected"{/if} >{$field.name} ({$field.Type_name})</option>
                        {/foreach}
                        </optgroup>
                        {if $system_fields}
                            <optgroup label="{$lang.xf_sysfields_label}">
                            {foreach from=$system_fields item="field"}
                                <option value="{$field.Key}" {if $field.Key == $xml_field.fl}selected="selected"{/if} >{$field.name} ({$lang.xf_sys_type})</option>
                            {/foreach}
                            </optgroup>
                        {/if}
                    {/if}
                </select>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.status}</td>
            <td class="field">
                <select id="ni_status">
                    <option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                    <option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
                </select>
            </td>
        </tr>

        <tr>
            <td></td>
            <td class="field">
                <input type="button" name="item_submit" value="{$lang.add}" />
                <a onclick="$('#add_mapping_item').slideUp('normal')" href="javascript:void(0)" class="cancel">{$lang.close}</a>
            </td>
        </tr>
        </table>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>

    <script type="text/javascript">
        {literal}
            $(document).ready(function(){
                $('#mapping_item_local').change(function(){
                    if (!$('#mapping_item_remote').val()) {
                        $('#mapping_item_remote').val($(this).val());
                    }
                });
            });

            $('input[name=item_submit]').click(function(){
                $(this).val(lang['loading']);

                if ($('#mapping_item_local').val() && ($('#mapping_item_remote').val() || $('#mapping_item_default').val())) {
                    xajax_addMappingItem($('#mapping_item_local').val(), $('#mapping_item_remote').val(), $('#mapping_item_default').val());
                } else {
                    $('input[name=item_submit]').val(lang['add']);
                    printMessage("error", "Fill all fields");
                }
            });
        {/literal}
    </script>

    {if !$smarty.get.field}
        <div id="grid"></div>
        <script type="text/javascript">//<![CDATA[
        lang['xf_unset'] = "{$lang.xf_unset}";

        {literal}
            var xmlMappingGrid;

            $(document).ready(function(){
                xmlMappingGrid = new gridObj({
                    key: 'xml_mapping',
                    id: 'grid',
                    ajaxUrl: rlPlugins + 'xmlFeeds/admin/xml_feeds.inc.php?q=ext_mapping&format={/literal}{$smarty.get.format}{literal}',
                    defaultSortField: 'Data_remote',
                    title: lang['ext_xml_formats_manager'],
                    fields: [
                        {name: 'ID', mapping: 'ID', type: 'int'},
                        {name: 'Data_remote', mapping: 'Data_remote', type: 'string'},
                        {name: 'Data_local', mapping: 'Data_local', type: 'string'},
                        {name: 'Local_field_name', mapping: 'Local_field_name', type: 'string'},
                        {name: 'Format', mapping: 'Format', type: 'string'},
                        {name: 'Format_name', mapping: 'Format_name', type: 'string'},
                        {name: 'Example_value', mapping: 'Example_value',  type: 'string'},
                        {name: 'Default', mapping: 'Default'},
                        {name: 'Status', mapping: 'Status'},
                        {name: 'Build_url', mapping: 'Build_url'}
                    ],
                    columns: [{
                            header: '{/literal}{$lang.xf_remote_field}{literal}',
                            dataIndex: 'Data_remote',
                            width: 15
                        },{
                            header: '{/literal}{$lang.xf_local_field}{literal}',
                            dataIndex: 'Local_field_name',
                            width: 11,
                            editor: new Ext.form.ComboBox({
                                store: [
                                    ['unset', '-- ' + lang['xf_unset'] + ' --'],
                                {/literal}{foreach from=$listing_fields item="field"}
                                    ['{$field.Key}', '{$field.name}  ({$field.Type_name})'],
                                {/foreach}
                                {foreach from=$system_fields item="field" name="sysFieldsLoop"}
                                    ['{$field.Key}', '{$field.name} ({$lang.xf_sys_type})']{if !$smarty.foreach.sysFieldsLoop.last},{/if}
                                {/foreach}{literal}
                                ],
                                displayField: 'value',
                                valueField: 'key',
                                typeAhead: true,
                                mode: 'local',
                                triggerAction: 'all',
                                selectOnFocus:true
                            }),
                            renderer: function(val){
                                return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                            }
                        },{
                            header: '{/literal}{$lang.xf_map_example_value}{literal}',
                            dataIndex: 'Example_value',
                            width: 11
                        },{
                            header: '{/literal}{$lang.xf_mapping_default}{literal}',
                            dataIndex: 'Default',
                            width: 8,
                            editor: new Ext.form.TextArea({
                                allowBlank: false
                            }),
                            renderer: function(val){
                                return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                            }
                        },{
                            header: lang['ext_status'],
                            dataIndex: 'Status',
                            width: 100,
                            fixed: true,
                            editor: new Ext.form.ComboBox({
                                store: [
                                    ['active', lang['ext_active']],
                                    ['approval', lang['ext_approval']]
                                ],
                                displayField: 'value',
                                valueField: 'key',
                                typeAhead: true,
                                mode: 'local',
                                triggerAction: 'all',
                                selectOnFocus:true
                            })
                        },{
                            header: lang['ext_actions'],
                            width: 90,
                            fixed: true,
                            dataIndex: 'ID',
                            sortable: false,
                            renderer: function(val, obj, row){
                                var out = "<center>";
                                var splitter = false;
                                var format_key = row.data.Format;
                                var item_key = row.data.Data_remote;

                                if (row.data.Build_url) {
                                    out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&amp;action=mapping&amp;format="+format_key+"&amp;";
                                    out += row.data.Build_url+"'><img class='build'ext:qtip='"+lang['ext_build']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                                }
                                out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \"{/literal}{$lang.drop_confirm}{literal}\", \"xajax_deleteMappingItem\", \""+item_key+"\", \"section_load\" )' />";
                                out += "</center>";

                                return out;
                            }
                        }
                    ]
                });

                xmlMappingGrid.init();
                grid.push(xmlMappingGrid.grid);

                xmlMappingGrid.grid.addListener('afteredit', function(editEvent) {
                    setTimeout(function(){ xmlMappingGrid.reload() }, 500);
                });
            });
            {/literal}
        //]]>
        </script>
    {else}

        <!-- items mapping grid -->

        <div id="grid"></div>
        <script type="text/javascript">//<![CDATA[
        {literal}
            var xmlItemMappingGrid;

            $(document).ready(function(){
                xmlItemMappingGrid = new gridObj({
                    key: 'xml_mapping',
                    id: 'grid',
                    ajaxUrl: rlPlugins + 'xmlFeeds/admin/xml_feeds.inc.php?q=ext_item_mapping&format={/literal}{$smarty.get.format}&field={$smarty.get.field} \
                    {if $smarty.get.parent}&parent={$smarty.get.parent}{/if}{literal}',
                    defaultSortField: 'Data_remote',
                    title: lang['ext_xml_formats_manager'],
                    fields: [
                        {name: 'ID', mapping: 'ID', type: 'int'},
                        {name: 'Data_remote', mapping: 'Data_remote', type: 'string'},
                        {name: 'Data_local', mapping: 'Data_local', type: 'string'},
                        {name: 'Local_field_name', mapping: 'Local_field_name', type: 'string'},
                        {name: 'Local_field_type', mapping: 'Local_field_type', type: 'string'},
                        {name: 'Format', mapping: 'Format', type: 'string'},
                        {name: 'Format_name', mapping: 'Format_name', type: 'string'},
                        {name: 'Status', mapping: 'Status'},
                        {name: 'Build_url', mapping: 'Build_url', type: 'string'}
                    ],
                    columns: [{
                            header: '{/literal}{$lang.xf_remote_data}{literal}',
                            dataIndex: 'Data_remote',
                            id: 'rlExt_item_bold',
                            width: 30
                        },{
                            header: '{/literal}{$lang.xf_local_data}{literal}',
                            dataIndex: "Data_local",
                            width: 30,
                            editor: new Ext.form.ComboBox({
                                store: [
                                {/literal}{foreach from=$listing_fields item="field"}
                                    ['{$field.Key}', '{$field.name}'],
                                {/foreach}
                                {foreach from=$local_values item="value" name="sysFieldsLoop"}
                                    ['{$value.Key}', '{$value.name|escape:"javascript"}']{if !$smarty.foreach.sysFieldsLoop.last},{/if}
                                {/foreach}{literal}
                                ],
                                displayField: 'value',
                                valueField: 'key',
                                typeAhead: true,
                                mode: 'local',
                                triggerAction: 'all',
                                selectOnFocus:true
                            }),
                            renderer: function(val){
                                return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                            }
                        },{
                            header: lang['ext_status'],
                            dataIndex: 'Status',
                            width: 10,
                            editor: new Ext.form.ComboBox({
                                store: [
                                    ['active', lang['ext_active']],
                                    ['approval', lang['ext_approval']]
                                ],
                                displayField: 'value',
                                valueField: 'key',
                                typeAhead: true,
                                mode: 'local',
                                triggerAction: 'all',
                                selectOnFocus:true
                            }),
                            renderer: function(val){
                                return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                            }
                        },{
                            header: lang['ext_actions'],
                            width: 90,
                            fixed: true,
                            dataIndex: 'ID',
                            sortable: false,
                            renderer: function(val, obj, row){
                                var out = "<center>";
                                var splitter = false;
                                var format_key = row.data.Format;
                                var item_key = row.data.Data_remote;

                                // if (row.data.Build_url) {
                                //     out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=mapping&amp;format="+format_key+"&amp;";
                                //     out += row.data.Build_url+"'><img class='build'ext:qtip='"+lang['ext_build']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                                // }

                                {/literal}{if $smarty.get.field != 'sys_dealer_id'
                                           && $smarty.get.field != 'sys_lang_codes'}{literal}
                                if (!row.data.Data_local) {
                                    out += "<img class='export' ext:qtip='"+lang['xf_copy_mapping_item']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+lang['xf_copy_mapping_item_confirm']+"\", \"xajax_copyMappingItem\", \""+item_key+"\", \"section_load\" )' />";
                                }
                                {/literal}{/if}{literal}

                                out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+lang['xf_ext_notice_delete_item']+"\", \"xajax_deleteMappingItem\", \""+item_key+"\", \"section_load\" )' />";
                                out += "</center>";

                                return out;
                            }
                        }
                    ]
                });

                xmlItemMappingGrid.init();
                grid.push(xmlItemMappingGrid.grid);

                xmlItemMappingGrid.grid.addListener('afteredit', function(editEvent) {
                    setTimeout(function(){ xmlItemMappingGrid.reload() }, 500);
                });
            });
            {/literal}
        //]]>
        </script>
    {/if}
{elseif $smarty.get.mode == 'formats'}
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    {literal}
        var xmlFormatsGrid;

        $(document).ready(function(){
            xmlFormatsGrid = new gridObj({
                key: 'xml_formats',
                id: 'grid',
                ajaxUrl: rlPlugins + 'xmlFeeds/admin/xml_feeds.inc.php?q=ext_formats',
                defaultSortField: 'ID',
                title: lang['ext_xml_formats_manager'],
                fields: [
                    {name: 'ID', mapping: 'ID', type: 'int'},
                    {name: 'Name', mapping: 'Name', type: 'string'},
                    {name: 'Status', mapping: 'Status'},
                    {name: 'Format_for', mapping: 'Format_for'},
                    {name: 'Key', mapping: 'Key'}
                ],
                columns: [{
                        header: lang['ext_name'],
                        dataIndex: 'Name',
                        id: 'rlExt_item_bold',
                        width: 50
                    },{
                        header: '{/literal}{$lang.xf_format_for}{literal}',
                        dataIndex: 'Format_for',
                        width: 20
                    },{
                        header: lang['ext_status'],
                        dataIndex: 'Status',
                        width: 20,
                        editor: new Ext.form.ComboBox({
                            store: [
                                ['active', lang['ext_active']],
                                ['approval', lang['ext_approval']]
                            ],
                            displayField: 'value',
                            valueField: 'key',
                            typeAhead: true,
                            mode: 'local',
                            triggerAction: 'all',
                            selectOnFocus:true
                        }),
                        renderer: function(val){
                            return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                        }
                    },{
                        header: lang['ext_actions'],
                        width: 10,
                        fixed: false,
                        dataIndex: 'ID',
                        sortable: false,
                        renderer: function(val, obj, row){
                            var out = "<center>";
                            var splitter = false;
                            var format_key = row.data.Key;

                            out += "<a href="+rlUrlHome+"index.php?controller="+controller+"&action=mapping&amp;format="+format_key+"><img class='build' ext:qtip='"+lang['ext_build']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";

                            out += "<a href="+rlUrlHome+"index.php?controller="+controller+"&action=edit_format&amp;format="+format_key+"><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                            out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+lang['ext_notice_delete_xml_format']+"\", \"xajax_deleteFormat\", \""+format_key+"\", \"section_load\" )' />";
                            out += "</center>";

                            return out;
                        }
                    }
                ]
            });

            xmlFormatsGrid.init();
            grid.push(xmlFormatsGrid.grid);

        });
        {/literal}
    //]]>
    </script>
{elseif $smarty.get.action == 'export'}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

    <div id="build_url">
        <table class="form" >
            <tr>
                <td class="name"><span class="red">*</span>{$lang.xf_format}</td>
                <td class="field">
                    <select name="format">
                    <option value="0">{$lang.select}</option>
                        {foreach from=$formats item="format"}
                            <option value="{$format.Key}" {if $sPost.format == $format.Key}selected="selected"{/if}>{$format.Name}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
            <tr>
            <td class="name"><span class="red">*</span>{$lang.xf_export_type}</td>
            <td class="field">
                <label><input class="lang_add" type="radio" name="export_type" value="one" /> {$lang.xf_etype_one}</label>
                <label><input checked="checked" class="lang_add" type="radio" name="export_type" value="all" /> {$lang.xf_etype_all}</label>
            </td>
            </tr>
            {if $listing_types|@count > 1}
            <tr>
                <td class="name">
                    {$lang.listing_type}
                </td>
                <td class="field">
                    <select name="listing_type" >
                        <option value="0">{$lang.all}</option>
                        {foreach from=$listing_types key="key" item="listing_type" name="ltLoop"}
                            <option value="{$listing_type.Key}" {if $smarty.foreach.ltLoop.first}selected="selected"{/if}>{$listing_type.name}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
            {/if}
        </table>

        <div id="user_settings">
            <table class="form">
            <tr>
                <td class="name"><span class="red">*</span>{$lang.username}</td>
                <td class="field">
                    <input type="text" id="account_id" name="account_id" value="{foreach from=$accounts item='account'}{if $sPost.account_id == $account.ID}{$account.Username}{/if}{/foreach}" />

                    <script type="text/javascript">
                        var post_account_id = {if $sPost.account_id}{$sPost.account_id}{else}false{/if};
                        {literal}
                            $('#account_id').rlAutoComplete({add_id: false});
                        {/literal}
                    </script>
                </td>
            </tr>
            </table>
        </div>

    <table class="form">
        <tr>
            <td class="name">{$lang.xf_export_limit}</td>
            <td class="field">
                <input type="text" class="numeric" value="" name="limit" />
            </td>
        </tr>
        <tr>
            <td class="name">
                {$lang.xf_rewrite_rule}
            </td>
            <td class="field">
                <input type="hidden" id="actual_rewrite" value="{if $rewrite}{$smarty.const.RL_URL_HOME}{$rewrite}{else}{$smarty.const.RL_PLUGINS_URL}xmlFeeds/export.php?[format][params]{/if}" />
                <input type="hidden" id="rewrited" value="{if $rewrite}1{else}0{/if}" />

                <input value="{if $rewrite}{$rewrite}{else}{$default_rewrite}{/if}" class="w250" name="rewrite" >
                <input style="padding:3px 14px 3px" id="apply_rule" type="button" value="{if $rewrite}{$lang.xf_htrule_edit}{else}{$lang.xf_htrule_edit}{/if}" />
                <span class="field_description" class="hide" id="hint">
                    {$lang.xf_rewrite_hint}
                </span>
            </td>
        </tr>
        <tr>
            <td class="name">
                {$lang.xf_feed_url}
            </td>
            <td class="field">
                <textarea cols="5" rows="1" style="height:60px" readonly="true" id="out"></textarea>
            </td>
        </tr>
    </table>
    </div>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

    <script type="text/javascript">
        {literal}
            $(document).ready(function(){
                $('input[name=export_type]').change(function(){
                    handleEType();
                });
                handleEType();

                $('#apply_rule').click(function(){
                    $('#apply_rule').val('{/literal}{$lang.loading}{literal}');
                    xajax_applyRule( $('input[name=rewrite]').val() );
                });

                $('#build_url input,select').change(function(){
                    if( $(this).attr('name') != 'account_id' )
                    {
                        buildUrl();
                    }
                });

                $('#account_id').blur(function(){
                    setTimeout(function(){ buildUrl() },100);
                });

                $('#out').click(function(){ $(this).select() });
            });

            var handleEType = function()
            {
                if( $('input[name=export_type]:checked').val() == 'all' )
                {
                    $('#user_settings').slideUp();
                }else
                {
                    $('#user_settings').slideDown();
                }
            };

            function buildUrl()
            {
                var actual_rewrite = $('#actual_rewrite').val();
                var params = new Array();
                var params_string= '';
                var format = false;
                var rewrited = $('#rewrited').val();

                $('#build_url input,select').each(function(){
                    var name = $(this).attr('name');
                    if( name == 'format' )
                    {
                        format = $(this).val() != 0 && $(this).val() ? $(this).val() : '';
                        if( rewrited == 0)
                        {
                            format = 'format='+format;
                        }
                    }else if( name == 'account_id' && $(this).val() && $('input[name=export_type]:checked').val() == 'one')
                    {
                        params['account_id'] = $(this).val();
                    }
                    else if( $(this).val() && name && name != 'export_type' && name != 'rewrite' && name != 'account_id')
                    {
                        params[ name ] = $(this).val();
                    }
                });

                if( format )
                {
                    var delim = '&';

                    if( actual_rewrite.indexOf('?') < 0 )
                    {
                        delim = '?';
                    }

                    for( var i in params )
                    {
                        if( typeof(params[i]) != 'function' )
                        {
                            params_string += delim + i + '=' + params[i];
                            delim = '&';
                        }
                    }
                    actual_rewrite = actual_rewrite.replace('[format]', format);
                    actual_rewrite = actual_rewrite.replace('[params]', params_string)

                    $('#out').html(actual_rewrite);
                }
            }
        {/literal}
    </script>

{else}

    <div id="action_blocks">
        {if !isset($smarty.get.action)}
            <!-- filters -->
            <div id="filters" class="hide">
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.filter_by}

                <table>
                <tr>
                    <td valign="top">
                        <table class="form">
                        <tr>
                            <td class="name w130">{$lang.username}</td>
                            <td class="field">
                                <input class="filters" type="text" maxlength="255" id="Account" />
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="field nowrap">
                                <input type="button" class="button" value="{$lang.filter}" id="filter_button" />
                                <input type="button" class="button" value="{$lang.reset}" id="reset_filter_button" />
                                <a class="cancel" href="javascript:void(0)" onclick="show('filters')">{$lang.cancel}</a>
                            </td>
                        </tr>
                        </table>
                    </td>
                    <td style="width: 50px;"></td>
                    <td valign="top">
                        <table class="form">
                        <tr>
                            <td class="name w130">{$lang.xf_format}</td>
                            <td class="field">
                                <select class="filters w200" id="format">
                                    <option value="">{$lang.select}</option>
                                    {foreach from=$filter_formats item='format'}
                                        <option value="{$format.Key}">{$format.Name}</option>
                                    {/foreach}
                                </select>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>

                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
            </div>

            <script type="text/javascript">
            {literal}
            var filters = new Array();
            var step = 0;

            $(document).ready(function(){

                if ( readCookie('xml_sc') )
                {
                    $('#filters').show();
                    var cookie_filters = readCookie('xml_sc').split(',');

                    for (var i in cookie_filters)
                    {
                        if ( typeof(cookie_filters[i]) == 'string' )
                        {
                            var item = cookie_filters[i].split('||');
                            $('#'+item[0]).selectOptions(item[1]);
                        }
                    }
                }

                $('#filter_button').click(function(){
                    filters = new Array();
                    write_filters = new Array();

                    createCookie('xml_pn', 0, 1);

                    $('.filters').each(function(){
                        if ($(this).val() != 0) {
                            filters.push(new Array($(this).attr('id'), $(this).val()));
                            write_filters.push($(this).attr('id')+'||'+$(this).val());
                        }
                    });

                    // save search criteria
                    createCookie('xml_sc', write_filters, 1);

                    // reload grid
                    xmlFeedsGrid.filters = filters;
                    xmlFeedsGrid.reload();
                });

                $('#reset_filter_button').click(function(){
                    eraseCookie('xml_sc');
                    xmlFeedsGrid.reset();

                    $("#filters select option[value='']").attr('selected', true);
                    $("#filters input[type=text]").val('');
                    $("#Category_ID option").show();
                });

                /* autocomplete js */
                $('#Account').rlAutoComplete();
            });
            {/literal}
            </script>
            <!-- filters end -->
        {/if}
    </div>

    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    {literal}
        var xmlFeedsGrid;

        /* read cookies filters */
        var cookies_filters = false;

        if ( readCookie('xml_sc') )
            cookies_filters = readCookie('xml_sc').split(',');


        $(document).ready(function(){
            xmlFeedsGrid = new gridObj({
                key: 'xml_feeds',
                id: 'grid',
                ajaxUrl: rlPlugins + 'xmlFeeds/admin/xml_feeds.inc.php?q=ext_feeds',
                defaultSortField: 'ID',
                title: lang['ext_xml_feeds_manager'],
                filters: cookies_filters,
                filtersPrefix: true,
                fields: [
                    {name: 'ID', mapping: 'ID', type: 'int'},
                    {name: 'Name', mapping: 'Name', type: 'string'},
                    {name: 'Status', mapping: 'Status'},
                    {name: 'Key', mapping: 'Key'},
                    {name: 'account', mapping: 'account'},
                    {name: 'Format', mapping: 'Format'}
                ],
                columns: [{
                        header: lang['ext_name'],
                        dataIndex: 'Name',
                        id: 'rlExt_item_bold',
                        width: 20
                    },{
                        header: lang['ext_owner'],
                        dataIndex: 'account',
                        width: 20
                    },{
                        header: {/literal}'{$lang.xf_format}'{literal},
                        dataIndex: 'Format',
                        width: 40
                    },{
                        header: lang['ext_status'],
                        dataIndex: 'Status',
                        width: 10,
                        editor: new Ext.form.ComboBox({
                            store: [
                                ['active', lang['ext_active']],
                                ['approval', lang['ext_approval']]
                            ],
                            displayField: 'value',
                            valueField: 'key',
                            typeAhead: true,
                            mode: 'local',
                            triggerAction: 'all',
                            selectOnFocus:true
                        }),
                        renderer: function(val){
                            return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                        }
                    },{
                        header: lang['ext_actions'],
                        width: 90,
                        fixed: true,
                        dataIndex: 'ID',
                        sortable: false,
                        renderer: function(val, obj, row){
                            var out = "<center>";
                            var splitter = false;
                            var feed_key = row.data.Key;

                            out += "<a href="+rlUrlHome+"index.php?controller="+controller+"&action=statistics&amp;feed="+feed_key+"><img class='manage' ext:qtip='{/literal}{$lang.xf_statistics}{literal}' src='"+rlUrlHome+"img/blank.gif' /></a>";
                            out += "<a href="+rlUrlHome+"index.php?controller="+controller+"&action=edit_feed&amp;feed="+feed_key+"><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                            out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+lang['ext_notice_delete_feed']+"\", \"xajax_deleteFeed\", \""+feed_key+"\", \"section_load\" )' />";
                            out += "</center>";

                            return out;
                        }
                    }
                ]
            });

            xmlFeedsGrid.init();
            grid.push(xmlFeedsGrid.grid);
        });
        {/literal}
    //]]>
    </script>
{/if}

<script>
lang['xf_example'] = '{$lang.xf_example}';
{literal}

$('.xpath-sample').flModal({
    width: 940,
    height: 880,
    caption: lang['xf_example'],
    content: '<div style="height: 800px"><img style="width: 100%;height: 100%;object-fit: contain;" src="' + rlPlugins + 'xmlFeeds/static/examples/path-to-field-data.png" /></div>'
});

{/literal}
</script>
