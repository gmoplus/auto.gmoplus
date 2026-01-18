<div id="account-types"></div>
<script>
    {literal}

    $(document).ready(function() {
        itemsGrid = new gridObj({
            key: 'account-types',
            id: 'account-types',
            ajaxUrl: rlPlugins + 'accountSync/admin/account_sync.inc.php?q=ext&action=build&domain_in={/literal}{$smarty.get.domain_in}{literal}',
            fieldID: 'Key',
            remoteSortable: true,
            title: lang['as_manage_domains'],
            fields: [
                {name: 'Name', mapping: 'name', type: 'string'},
                {name: 'Status', mapping: 'Status'},
                {name: 'Domain', mapping: 'Domain'}
            ],
            columns: [
                {
                    header: lang['account_type'],
                    dataIndex: 'Name',
                    id: 'rlExt_item_bold'
                }
            ]
        });

        itemsGrid.init();
        itemsGrid.store.addListener('load', function(e) {
            try {
                var data = e.data.items;
                var domains = data[0].json.domains;

                domains.forEach(function(item, index) {
                    var exjs_bw_column = {
                        header: item.name,
                        dataIndex: 'domain_' + index,
                        width: 130,
                        editor: new Ext.form.ComboBox({
                            store: [
                                ['1', 'Yes'],
                                ['0', 'No']
                            ],
                            displayField: 'value',
                            valueField: 'key',
                            typeAhead: true,
                            mode: 'local',
                            triggerAction: 'all',
                            selectOnFocus: true
                        }),
                        renderer: function(val, ext, row) {
                            return row.json.domains[index].is_sync ? 'Yes' : 'No';
                        }
                    };
                    itemsGrid.getInstance().columns.push(exjs_bw_column);
                    itemsGrid.getInstance().fields.push({name: 'domain_' + index, mapping: 'domain_' + index});
                });

                //todo:
                var actionsRow = {
                    header: 'Actions',
                    renderer: function(val, obj, row) {
                        var out = '';
                        // build
                        out += '<a href="' + rlUrlHome + 'index.php?controller=account_sync&action=build_fields&account_type=' + row.id + '">';
                        out += '<img class=\'build\' ext:qtip=\'' + lang['ext_edit'] + '\' src=\'' + rlUrlHome + 'img/blank.gif\' />';
                        out += '</a>';

                        return out;
                    }
                };
                itemsGrid.getInstance().columns.push(actionsRow);

                itemsGrid.init();
            } catch (e) {
                console.log(e.getMessage());
            }

        });

        grid.push(itemsGrid.grid);
    });
    {/literal}
</script>
