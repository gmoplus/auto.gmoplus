<!-- grid -->

<div id="grid"></div>
<script type="text/javascript">
var testimonialsGrid;

{literal}
$(document).ready(function(){
    testimonialsGrid = new gridObj({
        key: 'testimonials',
        id: 'grid',
        ajaxUrl: rlPlugins + 'testimonials/admin/testimonials.inc.php?q=ext',
        updateMethod: 'POST',
        defaultSortField: 'Date',
        defaultSortType: 'DESC',
        title: lang['ext_manager'],
        fields: [
            {name: 'Author', mapping: 'Author', type: 'string'},
            {name: 'Account_ID', mapping: 'Account_ID', type: 'int'},
            {name: 'Status', mapping: 'Status', type: 'string'},
            {name: 'Testimonial', mapping: 'Testimonial', type: 'string'},
            {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'Email', mapping: 'Email', type: 'string'},
            {name: 'ID', mapping: 'ID'}
        ],
        columns: [
            {
                    header: lang['ext_id'],
                    dataIndex: 'ID',
                    fixed: true,
                    width: 40,
                },{
                header: lang['ext_name'],
                dataIndex: 'Author',
                width: 15,
                renderer: function(author, obj, row) {
                    if ( row.data.Account_ID ) {
                        var out = "<a target='_blank' ext:qtip='"+lang['ext_click_to_view_details']+"' href='"+rlUrlHome+"index.php?controller=accounts&action=view&userid="+row.data.Account_ID+"'>"+author+"</a>"
                    }
                    else {
                        var out = author;
                    }

                    return out;
                }
            },{
                header: "{/literal}{$lang.testimonials_testimonial}{literal}",
                dataIndex: 'Testimonial',
                width: 60,
                editor: new Ext.form.TextArea({
                    allowBlank: false
                }),
                renderer: function(val){
                    return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                }
            },{
                header: lang['ext_date'],
                dataIndex: 'Date',
                fixed: true,
                width: 100,
                sortable: true,
                renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))
            },{
                header: lang['ext_status'],
                dataIndex: 'Status',
                fixed: true,
                width: 100,
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
                width: 80,
                fixed: true,
                dataIndex: 'ID',
                renderer: function(id, obj, row) {
                    var out = "<center>";
                    out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+lang['ext_notice_delete']+"\", \"deleteTestimonial\", \"" + id + "\" )' />";
                    out += "</center>";

                    return out;
                }
            }
        ]
    });

    testimonialsGrid.init();
    grid.push(testimonialsGrid.grid);

    deleteTestimonial = function(id) {
        flUtil.ajax({
            mode: 'deleteTestimonial',
            tmID: id,
        },
        function(response) {
            if (response) {
                if (response.status === "OK") {
                    testimonialsGrid.init();
                    printMessage('notice', '{/literal}{$lang.item_deleted}{literal}');
                }
            } else {
                printMessage('error', "{/literal}{$lang.system_error}{literal}");
            }
        })
    }
});
{/literal}
</script>
<!-- grid end -->
