<!-- RemoteStorage system controller tpl -->

{include file="`$smarty.const.RL_PLUGINS`remoteStorage/admin/view/main_plugin_notice.tpl"}
{include file="`$smarty.const.RL_PLUGINS`remoteStorage/admin/view/bucket_down_notice.tpl"}
{include file="`$smarty.const.RL_PLUGINS`remoteStorage/admin/view/navbar.tpl"}

{if $smarty.get.action}
    {include file="`$smarty.const.RL_PLUGINS`remoteStorage/admin/view/form.tpl"}
{elseif $smarty.get.mode === 'migration'}
    {include file="`$smarty.const.RL_PLUGINS`remoteStorage/admin/view/migration.tpl"}
{else}
    {include file="`$smarty.const.RL_PLUGINS`remoteStorage/admin/view/servers.tpl"}
{/if}

<!-- RemoteStorage system controller tpl -->
