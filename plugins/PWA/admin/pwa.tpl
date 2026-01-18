<!-- PWA system controller tpl -->

<div id="nav_bar">{strip}
    {if $smarty.get.mode}
        <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar">
            <span class="left"></span>
            <span class="center_list">{$lang.settings}</span>
            <span class="right"></span>
        </a>
    {else}
        <a href="{$rlBase}index.php?controller={$smarty.get.controller}&mode=usage" class="button_bar">
            <span class="left"></span>
            <span class="center_list">{$lang.pwa_usage_stat}</span>
            <span class="right"></span>
        </a>
    {/if}
{/strip}</div>

{if !$smarty.get.mode}
    {include file=$smarty.const.RL_PLUGINS|cat:'PWA/admin/settings.tpl'}
{else}
    {include file=$smarty.const.RL_PLUGINS|cat:'PWA/admin/usage.tpl'}
{/if}

<!-- PWA system controller tpl end -->
