<!-- Navigation bar the RemoteStorage plugin tpl -->

<div id="nav_bar">
    {if !$smarty.get.action && !$smarty.get.mode}
        <a href="{$rlBaseC}action=add" class="button_bar">{strip}
            <span class="left"></span>
            <span class="center_add">{$lang.add}</span>
            <span class="right"></span>
        {/strip}</a>
    {else}
        <a href="{$rlBaseC}" class="button_bar">{strip}
            <span class="left"></span>
            <span class="center_list">{$lang.items_list}</span>
            <span class="right"></span>
        {/strip}</a>
    {/if}

    {if $config.rs_main_server && !$smarty.get.action && !$smarty.get.mode}
        <a id="cf_migration_button" href="{$rlBaseC}mode=migration" class="button_bar">{strip}
            <span class="left"></span>
            <span class="center_import">{$lang.rs_migration}</span>
            <span class="right"></span>
        {/strip}</a>
    {/if}
</div>

<!-- Navigation bar the RemoteStorage plugin tpl end -->
