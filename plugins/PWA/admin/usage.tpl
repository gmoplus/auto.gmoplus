<!-- PWA/Usage statistics tpl -->

<div id="grid"></div>
<script>{literal}
var pwaUsageGrid;

$(function () {
    pwaUsageGrid = new gridObj({
        key: 'pwa_usage',
        id: 'grid',
        ajaxUrl: rlPlugins + 'PWA/admin/pwa.inc.php?q=ext&mode=usage',
        defaultSortField: 'Date',
        defaultSortType: 'DESC',
        title: '{/literal}{$lang.pwa_usage_stat}{literal}',
        remoteSortable: true,
        fields: [
            {name: 'ID', mapping: 'ID', type: 'int'},
            {name: 'IP', mapping: 'IP'},
            {name: 'OS', mapping: 'OS'},
            {name: 'Browser', mapping: 'Browser'},
            {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {/literal}{if $plugins.ipgeo}{literal}
            {name: 'Country', mapping: 'Country'},
            {name: 'State', mapping: 'State'},
            {name: 'City', mapping: 'City'},
            {/literal}{/if}{literal}
        ],
        columns: [
            {
                header: lang['ext_id'],
                dataIndex: 'ID',
                width: 40,
            },{
                header: '{/literal}{$lang.pwa_stat_ip}{literal}',
                dataIndex: 'IP',
                width: 150,
            },{
                header: '{/literal}{$lang.pwa_stat_os}{literal}',
                dataIndex: 'OS',
                width: 150,
            },{
                header: '{/literal}{$lang.pwa_stat_browser}{literal}',
                dataIndex: 'Browser',
                width: 150,
            },{
                header: lang.ext_date,
                dataIndex: 'Date',
                width: 150,
                renderer: function(val) {
                    return Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))(val);
                }
            },
            {/literal}{if $plugins.ipgeo}{literal}
            {
                header: '{/literal}{$lang.pwa_country}{literal}',
                dataIndex: 'Country',
                width: 150,
                renderer: function(val) {
                    return val ? val : lang.ext_not_available;
                }
            },{
                header: '{/literal}{$lang.pwa_state}{literal}',
                dataIndex: 'State',
                width: 150,
                renderer: function(val) {
                    return val ? val : lang.ext_not_available;
                }
            },{
                header: '{/literal}{$lang.pwa_city}{literal}',
                dataIndex: 'City',
                width: 150,
                renderer: function(val) {
                    return val ? val : lang.ext_not_available;
                }
            },
            {/literal}{/if}{literal}
        ]
    });

    pwaUsageGrid.init();
    grid.push(pwaUsageGrid.grid);
});
{/literal}</script>

<!-- PWA/Usage statistics tpl end -->
