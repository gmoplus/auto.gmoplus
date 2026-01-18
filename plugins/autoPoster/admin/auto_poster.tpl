<!-- AutoPoster admin area tpl -->

<script src="{$smarty.const.RL_PLUGINS_URL}autoPoster/static/admin_lib.js"></script>
<link href='{$smarty.const.RL_PLUGINS_URL}autoPoster/static/admin_style.css' type='text/css' rel='stylesheet' />

{if $smarty.get.action}
    {assign var='sPost'  value=$smarty.post}
    {assign var='module' value=$smarty.get.module}
    {assign var='action' value=$smarty.get.action}

    <!-- Navigation bar -->
    <div id="nav_bar">
        <a href="{$rlBaseC}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.ap_modules_list}</span><span class="right"></span></a>

        {if $action != 'edit'}
            <a href="{$rlBaseC}action=edit&module={$module}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.ap_module_configuration}</span><span class="right"></span></a>
        {/if}

    </div>
    <!-- Navigation bar end-->

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

    {if $action == 'edit'}
        {if $guide_link}
            <div class="provide-guide-box">
                {$guide_link}
            </div>
        {/if}

        <fieldset class="light">
            <legend id="legend_accounts_tab_area" class="up" onclick="fieldset_action('module_config');">{$lang.ap_module_settings}</legend>

            <div id="module_config">
                <form  action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;module={$module}{/if}" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="submit" value="1" />
                    <table class="form">
                        {if $OAuthLink}
                            <tr>
                                <td class="name">{$lang.ap_copy_valid_oauth}</td>
                                <td class="field">
                                    <input  id="copy-correct-link" data-url="{$OAuthLink}" type="button" name="{$lang.copy}" value="{$lang.ap_copy}">
                                </td>
                            </tr>
                            <script>
                                var succesfully_copied_phrase = '{$lang.ap_link_copied}';
                                {literal}
                                $(document).ready(function () {
                                    var autoPosterAdmin = new autoPosterAdminClass();
                                    $('#copy-correct-link').click(function (e) {
                                        var link = $(this).data('url');
                                        if (autoPosterAdmin.copyTextToClipBoard(link)) {
                                            printMessage('notice', succesfully_copied_phrase);
                                        }
                                    });
                                });
                                {/literal}
                            </script>
                        {/if}
                        <!-- Module configs -->
                        {foreach from=$module_configs item=configItem}
                            {include file=$admin_options.path.view|cat:$smarty.const.RL_DS|cat:'module_configs.tpl'}
                        {/foreach}
                        <!-- Module configs end -->

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
                                <input type="submit" value="{$lang.edit}" />
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        </fieldset>

        <!-- Module management -->
        {if $allow_management}
            <fieldset class="light">
                <legend id="legend_accounts_tab_area" class="up" onclick="fieldset_action('module_management');">{$lang.ap_module_managment}</legend>
                <div id="module_management">
                    {include file=$admin_options.path.view|cat:$smarty.const.RL_DS|cat:'modules'|cat:$smarty.const.RL_DS|cat:$smarty.get.module|cat:'.tpl'}
                </div>
            </fieldset>
        {/if}

        {if $smarty.get.module == 'telegram'}
            <script>
            {literal}

            $(function(){
                $('.get-chat-id').click(function(){
                    var data = {
                        token: $('input[name="post_config[ap_telegram_bot_token]"]').val()
                    };
                    flynax.sendAjaxRequest('ap_get_telegram_chat_id', data, function(response){
                        $('input[name="post_config[ap_telegram_chat_id]"]').val(response.results);
                    });
                });
            });

            {/literal}
            </script>
        {elseif $smarty.get.module == 'facebook'}
            <script>{literal}
            $(function () {
                let $destinatonType = $('[name="post_config[ap_facebook_post_to]"]');

                $destinatonType.change(function () {
                    fbDestinationHandler();
                });

                /**
                 * Show/hide page/group id field by value of the "Post listings to" field
                 * It must be hidden when selected "Business page", because ID will be getting automatically
                 */
                function fbDestinationHandler () {
                    let destinationType = $destinatonType.find('option:selected').val(),
                        $pageGroupIDTr = $('[name="post_config[ap_facebook_subject_id]"]').closest('tr');

                    if (!destinationType || destinationType === 'to_page') {
                        $pageGroupIDTr.slideUp();
                    } else {
                        $pageGroupIDTr.slideDown();
                    }
                }
            });
            {/literal}</script>
        {/if}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    <!-- Module management end -->

    {/if}

{else}
    <!-- auto poster grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
        lang['ap_modules'] = '{$lang.ap_modules}';
        lang['id'] = '{$lang.id}';
        lang['key'] = '{$lang.key}';
        lang['name'] = '{$lang.name}';

        {literal}
        $(document).ready(function(){
            var autoPosterGrid = new autoPosterModulesGridClass();
            autoPosterGrid.init();
        });
        {/literal}
    </script>
    <!-- auto poster grid end -->

    {if $error_log}
        <div class="error-log">
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.system_error}
            {foreach from=$error_log item='error_line'}
            <div class="error-log_item">
                <div class="error-log_date">{$error_line.date}</div>
                <div class="error-log_message">{$error_line.message}</div>
            </div>
            {/foreach}
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

            <div id="nav_bar">
                <a href="javascript://" id="clear_logs" class="button_bar"><span class="left"></span><span class="center_remove">{$lang.ap_clear_log}</span><span class="right"></span></a>
            </div>
        </div>

        <script>
        lang['confirm_notice'] = "{$lang.confirm_notice}";
        {literal}

        $(function(){
            $('#clear_logs').click(function(){
                Ext.MessageBox.confirm(lang['ext_confirm'], lang['confirm_notice'], function(btn){
                    if (btn == 'yes'){
                        flynax.sendAjaxRequest('ap_clear_log', {'clear': 1}, function(response, status){
                            if (response.status == 'OK') {
                                $('.error-log').hide();
                            } else {
                                printMessage('error', lang.system_error);
                            }
                        });
                    }
                });
            });
        });

        {/literal}
        </script>
    {/if}
{/if}

<!-- AutoPoster admin area tpl end -->
