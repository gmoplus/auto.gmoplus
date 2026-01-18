{if $smarty.get.action}
    {assign var='rlBaseC' value=$rlBaseC|cat:'module=shipping_fields&amp;'}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fields'|cat:$smarty.const.RL_DS|cat:'add_edit_form.tpl'}
{else}
    <div id="grid"></div>
    <script type="text/javascript">
    var shippingFieldsGrid;
    
    {literal}
    $(document).ready(function(){
        
        shippingFieldsGrid = new gridObj({
            key: 'shippingFields',
            id: 'grid',
            ajaxUrl: rlPlugins + 'shoppingCart/admin/shopping_cart.inc.php?q=ext_shipping_fields',
            defaultSortField: 'name',
            title: lang['ext_shipping_fields_manager'],
            remoteSortable: true,
            fields: [
                {name: 'name', mapping: 'name', type: 'string'},
                {name: 'Type', mapping: 'Type'},
                {name: 'Required', mapping: 'Required'},
                {name: 'Map', mapping: 'Map'},
                {name: 'Status', mapping: 'Status'},
                {name: 'Key', mapping: 'Key'},
                {name: 'Readonly', mapping: 'Readonly'}
            ],
            columns: [
                {
                    header: lang['ext_name'],
                    dataIndex: 'name',
                    width: 60,
                    id: 'rlExt_item_bold'
                },{
                    id: 'rlExt_item',
                    header: lang['ext_type'],
                    dataIndex: 'Type',
                    fixed: true,
                    width: 150,
                },{
                    header: lang['ext_required_field'],
                    dataIndex: 'Required',
                    fixed: true,
                    width: 110,
                    editor: new Ext.form.ComboBox({
                        store: [
                            ['1', lang['ext_yes']],
                            ['0', lang['ext_no']]
                        ],
                        displayField: 'value',
                        valueField: 'key',
                        emptyText: lang['ext_not_available'],
                        typeAhead: true,
                        mode: 'local',
                        triggerAction: 'all',
                        selectOnFocus:true
                    }),
                    renderer: function(val){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: lang['ext_status'],
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
                        selectOnFocus:true
                    })
                },{
                    header: lang['ext_actions'],
                    width: 70,
                    fixed: true,
                    dataIndex: 'Key',
                    sortable: false,
                    renderer: function(data, obj, row) {
                        var out = "<center>";
                        out += "<img class='edit' ext:qtip='" + lang['ext_edit'] + "' src='";
                        out += rlUrlHome + "img/blank.gif' onClick='location.href=\"";
                        out += rlUrlHome + "index.php?controller=" + controller + "&module=shipping_fields&action=edit&field=";
                        out += data + "\"' />";

                        if (row.data.Readonly != 1) {
                            out += "<img class='remove' ext:qtip='" +  lang['ext_delete'] + "' src='";
                            out += rlUrlHome + "img/blank.gif' onClick='rlConfirm( \"";
                            out += lang['ext_notice_delete'] + "\", \"shoppingCart.deleteShippingField\", \"";
                            out += data + "\" )' class='delete' />";
                        }
                        out += "</center>";
                        
                        return out;
                    }
                }
            ]
        });
        
        shippingFieldsGrid.init();
        grid.push(shippingFieldsGrid.grid);
        
    });
    {/literal}
    </script>
{/if}
