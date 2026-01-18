<div id="nav_bar">
    {assign var='action' value=$smarty.get.action}

    {if !$smarty.get.action}
        <a onclick="show('new_listing', '#action_blocks div');" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add}</span><span class="right"></span></a>
    {/if}

    {if !$action}
        <a href="{$rlBaseC}action=build" class="button_bar"><span class="left"></span><span class="center_build">{$lang.as_manage_a_type}</span><span class="right"></span></a>
        <a href="{$rlBaseC}action=manage_users" class="button_bar"><span class="left"></span><span class="center_build">{$lang.as_manage_users}</span><span class="right"></span></a>
    {/if}

    {if $action == 'manage_users'}
        <a href="javascript:void(0);" id="fetch-users" class="button_bar"><span class="left"></span><span class="center_build">{$lang.as_fetch_data}</span><span class="right"></span></a>
    {/if}

    {if $action != 'manage_users'}
        <a id="sync-cache" class="button_bar"><span class="left"></span><span class="center_list">{$lang.update_cache}</span><span class="right"></span></a>
    {/if}
</div>
{if $smarty.get.action}
    {if $smarty.get.action == 'build'}
        {include file=$asConfigs.path.view|cat:'/pages/admin/build.tpl'}
    {elseif $smarty.get.action == 'manage_users'}
        {include file=$asConfigs.path.view|cat:'/pages/admin/manage_users.tpl'}
    {elseif $smarty.get.action == 'build_fields'}
        {include file=$asConfigs.path.view|cat:'/pages/admin/build_fields.tpl'}
    {/if}
{else}
    <div id="action_blocks">
        <div id="new_listing" class="hide">
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.as_sync_new_site}
            <table class="form">
                <tbody>
                <tr>
                    <td class="name w130">{$lang.url}<span class="red">*</span></td>
                    <td class="field">
                        <input id="as-flynax-url" placeholder="{$smarty.const.RL_URL_HOME}"  class="filters" type="text" required >
                    </td>
                </tr>
                <tr>
                    <td class="name w130">{$lang.as_admin_username}<span class="red">*</span></td>
                    <td class="field">
                        <input id="as-admin-username" class="filters" type="text" maxlength="60" required>
                    </td>
                </tr>
                <tr>
                    <td class="name w130">{$lang.password}<span class="red">*</span></td>
                    <td class="field">
                        <input id="as-admin-password" class="filters" type="password" maxlength="60" required>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td class="field">
                        <input id="as-synchronize" type="button" value="{$lang.add}">
                    </td>
                </tr>
                </tbody>
            </table>
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
        </div>
    </div>

    <div id="grid"></div>

    <script type="text/javascript">//<![CDATA[
        lang['ap_modules'] = '{$lang.ap_modules}';
        lang['id'] = '{$lang.id}';
        lang['key'] = '{$lang.key}';
        lang['name'] = '{$lang.name}';
        var itemsGrid;
        {literal}
        $(document).ready(function() {
            var fields = [
                {name: 'id', mapping: 'ID', type: 'int'},
                {name: 'key', mapping: 'Key', type: 'string'},
                {name: 'Status', mapping: 'Status'},
                {name: 'Domain', mapping: 'Domain'}
            ];

            var accountSyncAdmin = new AccountSyncClass();

            itemsGrid = new gridObj({
                key: 'autoposter_grid',
                id: 'grid',
                ajaxUrl: rlPlugins + 'accountSync/admin/account_sync.inc.php?q=ext',
                defaultSortField: 'id',
                remoteSortable: true,
                title: lang['as_account_synchronization'],
                fields: [
                    {name: 'id', mapping: 'ID', type: 'int'},
                    {name: 'key', mapping: 'Key', type: 'string'},
                    {name: 'Status', mapping: 'Status'},
                    {name: 'Domain', mapping: 'Domain'},
                ],
                columns: [
                    {
                        header: lang['id'],
                        dataIndex: 'id',
                        id: 'ap_id',
                        width: 40,
                        fixed: true
                    }, {
                        header: lang['domain'],
                        dataIndex: 'Domain',
                        id: 'rlExt_item_bold'
                    }, {
                        header: lang['ext_status'],
                        dataIndex: 'Status',
                        width: 100,
                        fixed: true
                    }, {
                        header: lang['ext_actions'],
                        width: 70,
                        fixed: true,
                        dataIndex: 'Key',
                        sortable: false,
                        renderer: function(val, obj, row) {
                            var id = row.data.id;
                            var domain = row.data.Domain;
                            var out = '';

                            // disconnect
                            out += '<a>';
                            out += '<img  data-domain="' + domain + '" class="remove account-sync-disconnect" ext:qtip=\'' + lang['ext_edit'] + '\' src=\'' + rlUrlHome + 'img/blank.gif\' />';
                            out += '</a>';

                            return out;
                        }
                    }
                ]
            });
            itemsGrid.init();
            grid.push(itemsGrid.grid);

            accountSyncAdmin.enableGridEvents();
            accountSyncAdmin.setGrid(itemsGrid);

            if ('function' === typeof AccountTypesGrid) {
                AccountTypesGrid().run();
            }
        });
        {/literal}
    </script>
{/if}

