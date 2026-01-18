<!-- auto_reg_prevent.tpl -->

<!-- navigation bar -->
<div id="nav_bar">{strip}
    <a href="javascript:void(0)" onclick="$('div#add_prevent').toggleClass('hide');" class="button_bar">
        <span class="left"></span><span class="center_add">{$lang.add}</span><span class="right"></span>
    </a>
{/strip}</div>
<!-- navigation bar end -->

<div id="action_blocks">
    <div id="add_prevent" class="hide">
        {include file='blocks/m_block_start.tpl' block_caption=$lang.autoRegPrevent_addItem}
        <form id="form-add-to-spam-list" onsubmit="addToSpamList();return false;" method="post">
        <table class="form">
        <tr>
            <td class="name">{$lang.username}</td>
            <td class="field">
                <input type="text" id="arp_username" style="width: 200px;" maxlength="60" />
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.mail}</td>
            <td class="field">
                <input type="text" id="arp_mail" style="width: 200px;" maxlength="60" />
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.autoRegPrevent_ext_ip}</td>
            <td class="field">
                <input type="text" id="arp_ip" style="width: 200px;" maxlength="60" />
            </td>
        </tr>
        <tr>
            <td></td>
            <td class="field">
                <input type="submit" name="item_submit" value="{$lang.add}" />
                <a onclick="$('div#add_prevent').addClass('hide')" href="javascript:void(0)" class="cancel">
                    {$lang.close}
                </a>
            </td>
        </tr>
        </table>
        </form>
        {include file='blocks/m_block_end.tpl'}

        <script>{literal}
            function restoreSubmitButtonTitle() {
                $('input[name=item_submit]').val('{/literal}{$lang.add}{literal}');
            }

            function postAjaxItem(item, data, callback) {
                var url = rlConfig['ajax_url'] + '?item=' + item;

                if (arguments.length === 2 && data instanceof Function) {
                    callback = data;
                    data = [];
                }

                $.post(url, data, function (response) {
                    if (!(callback instanceof Function)) {
                        return;
                    }

                    if (response && response.status && response.message) {
                        var success = response.status === 'OK';
                        callback(success, response);
                    } else {
                        callback(false, response);
                    }
                }, 'json').fail(function (error) {
                    callback(false, error);
                });
            }

            function funcDelete(entryId) {
                var data = {
                    id: entryId
                };
                postAjaxItem('autoRegPrevent_deleteEntry', data, function (success, response) {
                    if (success) {
                        printMessage('notice', response.message);
                        autoRegPreventGrid.reload();
                    }
                });
            }

            function addToSpamList() {
                let data = {
                    item: 'autoRegPrevent_addToSpamList',
                    username: $.trim($('input#arp_username').val()),
                    email: $.trim($('input#arp_mail').val()),
                    ip: $.trim($('input#arp_ip').val())
                };

                if (data.ip && !/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/.test(data.ip)) {
                    printMessage('error', lang.autoRegPrevent_invalidIp);
                    return;
                }

                $('input[name=item_submit]').val(lang['ext_loading']);

                postAjaxItem('autoRegPrevent_addToSpamList', data, function (success, response) {
                    if (success) {
                        printMessage('notice', response.message);
                        document.getElementById('form-add-to-spam-list').reset();
                        autoRegPreventGrid.reload();
                    } else {
                        if (response.status === 'ERROR' && response.message) {
                            printMessage('error', response.message);
                        } else {
                            printMessage('error', lang['ext_error_saving_changes']);
                        }
                    }
                    restoreSubmitButtonTitle();
                });
            }
        {/literal}</script>
    </div>
</div>

<div id="grid"></div>
<script>{literal}
var autoRegPreventGrid;

$(document).ready(function(){
    autoRegPreventGrid = new gridObj({
        key: 'autoRegPrevent',
        id: 'grid',
        ajaxUrl: rlPlugins + 'autoRegPrevent/admin/auto_reg_prevent.inc.php?q=ext',
        defaultSortField: 'Date',
        defaultSortType: 'DESC',
        title: lang['autoRegPrevent_ext_manager'],
        fields: [
            {name: 'ID', mapping: 'ID'},
            {name: 'Username', mapping: 'Username'},
            {name: 'Mail', mapping: 'Mail'},
            {name: 'IP', mapping: 'IP'},
            {name: 'Reason', mapping: 'Reason'},
            {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'Status', mapping: 'Status'}
        ],
        columns: [
            {
                header: lang['ext_username'],
                dataIndex: 'Username',
                width: 40
            },{
                header: lang['ext_email'],
                dataIndex: 'Mail',
                width: 60
            },{
                header: '{/literal}{$lang.autoRegPrevent_ext_ip}{literal}',
                dataIndex: 'IP',
                width: 30
            },{
                header: lang['autoRegPrevent_ext_reason'],
                dataIndex: 'Reason',
                width: 35,
                id: 'rlExt_item'
            },{
                header: lang['autoRegPrevent_ext_date_reg'],
                dataIndex: 'Date',
                width: 25,
                renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))
            },{
                header: lang['ext_status'],
                dataIndex: 'Status',
                width: 20,
                editor: new Ext.form.ComboBox({
                    {/literal}
                    store: [
                        ['block', '{$lang.autoRegPrevent_status_block}'],
                        ['unblock', '{$lang.autoRegPrevent_status_unblock}']
                    ],
                    {literal}
                    mode: 'local',
                    typeAhead: true,
                    triggerAction: 'all',
                    selectOnFocus: true
                }),
                renderer: function(rowValue, row) {
                    if (rowValue === '{/literal}{$lang.autoRegPrevent_status_block}{literal}') {
                        row.style += 'background: #d2e798;';
                    } else if (rowValue === '{/literal}{$lang.autoRegPrevent_status_unblock}{literal}') {
                        row.style += 'background: #ffe7ad;';
                    }

                    return '<div ext:qtip="' + lang['ext_click_to_edit'] + '">' + rowValue + '</div>';
                }
            },{
                header: lang['ext_actions'],
                width: 55,
                fixed: true,
                dataIndex: 'ID',
                sortable: false,
                renderer: function(id) {
                    var out = '<img ';
                    out += 'ext:qtip="' + lang['ext_delete'] + '"';
                    out += 'class="remove"';
                    out += 'src="' + rlUrlHome + 'img/blank.gif"';
                    out += 'style="display:block;margin-left:auto;margin-right:auto;"';
                    out += 'onclick="rlConfirm(\'' + lang['ext_notice_delete'] + '\', \'funcDelete\', ' + id + ')"';
                    out += ' />';

                    return out;
                }
            }
        ]
    });

    autoRegPreventGrid.init();
    grid.push(autoRegPreventGrid.grid);
});
{/literal}</script>

<!-- auto_reg_prevent.tpl end -->
