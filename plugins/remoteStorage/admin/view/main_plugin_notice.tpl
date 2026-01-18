<!-- RemoteStorage: Show main plugin notice tpl -->

{if !$smarty.get.action && !$smarty.get.mode}
    {include file='blocks/m_block_start.tpl'}
    <div>{$lang.rs_main_notice}</div>
    {include file='blocks/m_block_end.tpl'}
{/if}

<!-- RemoteStorage: Show main plugin notice tpl end -->
