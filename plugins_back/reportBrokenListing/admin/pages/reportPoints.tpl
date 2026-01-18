<div id="grid"></div>
<script>
    lang['item_deleted'] = "{$lang.item_deleted}";
    var reportPointsGrid;

    {literal}
    $(document).ready(function () {
        reportPointsGrid = new gridObj({
            key: 'reportBrokenPoints',
            id: 'grid',
            ajaxUrl: rlPlugins + 'reportBrokenListing/admin/pages/reportPoints.php?q=ext',
            title: lang['ext_manager'],
            fields: [
                {name: 'ID', mapping: 'ID', type: 'int'},
                {name: 'Body', mapping: 'Body', type: 'string'},
                {name: 'Key', mapping: 'Key', type: 'string'},
                {name: 'Reports_to_critical', mapping: 'Reports_to_critical', type: 'int'},
                {name: 'Position', mapping: 'Position', type: 'int'},
                {name: 'Status', mapping: 'Status', type: 'string'}
            ],
            columns: [{
                header: lang['ext_id'],
                dataIndex: 'ID',
                width: 40,
                fixed: true,
                id: 'rlExt_black_bold'
            }, {
                header: lang['rbl_point_name'],
                dataIndex: 'Body',
                width: 22,
                editor: new Ext.form.TextField({
                    allowBlank: false,
                }),
                renderer: function (val) {
                    return '<span ext:qtip="' + lang['ext_click_to_edit'] + '">' + val + '</span>';
                }
            }, {
                header: lang['rbl_points_to_inactive'],
                dataIndex: 'Reports_to_critical',
                width: 250,
                fixed: true,
                editor: new Ext.form.NumberField({
                    allowBlank: false,
                    allowDecimals: false,
                    validator: function (val) {
                        return val > 0;
                    }
                }),
                renderer: function (val) {
                    return '<span ext:qtip="' + lang['ext_click_to_edit'] + '">' + val + '</span>';
                }
            }, {
                header: lang['ext_position'],
                dataIndex: 'Position',
                width: 100,
                fixed: true,
                editor: new Ext.form.NumberField({
                    allowBlank: false,
                    allowDecimals: false,
                    validator: function (val) {
                        return val >= 0;
                    }
                }),
                renderer: function (val) {
                    return '<span ext:qtip="' + lang['ext_click_to_edit'] + '">' + val + '</span>';
                }
            }, {
                header: lang['status'],
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
                    selectOnFocus: true
                })
            }, {
                header: lang['ext_actions'],
                dataIndex: 'ID',
                width: 60,
                fixed: true,
                renderer: function (id, obj, row) {
                    var out = "<center><a class='edit-reportpoint' data-key='" + row.data.Key + "' data-id='" + id + "' onclick='reportPoints.onEditPoint(this);' href='javascript:void(0);'><img class='edit' ext:qtip='" + lang['ext_edit'] + "' src='" + rlUrlHome + "img/blank.gif' /></a>";
                    out += "<img class='remove' ext:qtip='" + lang['ext_delete'] + "' src='" + rlUrlHome + "img/blank.gif' onClick='rlConfirm( \"" + lang['ext_notice_' + delete_mod] + "\",  \"reportPoints.deletePoint\", \"" + row.data.Key + "\")' /></center>";
                    return out;
                }
            }
            ]
        });

        reportPointsGrid.init();
        grid.push(reportPointsGrid.grid);
        reportPoints.setGrid(reportPointsGrid);
    });
    {/literal}
</script>
