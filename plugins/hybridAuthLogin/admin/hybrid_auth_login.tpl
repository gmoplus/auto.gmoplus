{if $smarty.get.action}
    {assign var='sPost'  value=$smarty.post}
    {assign var='module' value=$smarty.get.module}
    {assign var='action' value=$smarty.get.action}

    <!-- Navigation bar -->
    <div id="nav_bar">
        <a href="{$rlBaseC}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.ha_modules_list}</span><span class="right"></span></a>
    </div>
    <!-- Navigation bar end-->

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

    {if $guide_link}
        <div class="provide-guide-box">{$guide_link}</div>
    {/if}

    {if $module == 'apple'}
        {$lang.ha_apple_configuration_notice}
    {else}
    <fieldset class="light">
        <legend id="legend_accounts_tab_area" class="up" onclick="fieldset_action('module_config');">{$lang.ha_module}</legend>
        <div id="module_config">
            <form action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;module={$module}{/if}" method="post" enctype="multipart/form-data">
                <input type="hidden" name="submit" value="1" />

                <table class="form">
                    {if $redirect_url}
                        <tr>
                            <td class="name">
                                {$lang.ha_copy_link}
                            </td>
                            <td class="field">
                                <input data-link="{$redirect_url}" id="ha-copy-redirect-link" type="button" value="{$lang.ha_copy}">
                            </td>
                        </tr>

                        <script type="text/javascript">
                            var shouldEnableCopyRedirectLink = {if $redirect_url}true{else}false{/if};
                            {literal}
                            $(document).ready(function () {
                                var hybridAuthAdmin = new hybridAuthAdminClass();
                                if (shouldEnableCopyRedirectLink) {
                                    hybridAuthAdmin.enableLinkCopyButton();
                                }
                            });
                            {/literal}
                        </script>
                    {/if}
                    <!-- Module configs -->
                    {foreach from=$module_settings item=configItem}
                        {include file=$hybrid_configs.path.view|cat:$smarty.const.RL_DS|cat:'module_configs.tpl'}
                    {/foreach}
                    <!-- Module configs end -->

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
    {/if}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
{else}
    <!-- hybrid auth main grid -->
    <div id="grid"></div>
    <!-- hybrid auth main grid end -->

    <script type="text/javascript">
        var haLang = [];
        haLang['ha_ap_modules'] = '{$lang.ha_ap_modules}';
        haIsFacebookConnectEnabled = {$ha_is_facebook_connect_enabled};
        {literal}
        $(document).ready(function () {
            var hybridAuthAdmin = new hybridAuthAdminClass();
            if (!haIsFacebookConnectEnabled) {
                hybridAuthAdmin.enableMainGrid();
            }
        });
        {/literal}
    </script>
{/if}

