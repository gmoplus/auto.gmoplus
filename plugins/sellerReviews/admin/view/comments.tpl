<!-- SellerReviews comments tpl -->

<div id="grid"></div>
<script>
    let srrCommentsGrid;

    {literal}
    $(function () {
        let cookiesFilters = [];
        if (readCookie('srr_comment_filters')) {
            $('#search').show();
            let commentFilters = readCookie('srr_comment_filters').split(',');

            for (let i in commentFilters) {
                if (commentFilters[i] && typeof commentFilters[i] === 'string') {
                    let item = commentFilters[i].split('||');
                    $('#' + item[0]).selectOptions(item[1]);

                    let filter = commentFilters[i].split('||');

                    if (filter[0] && filter[1]) {
                        cookiesFilters.push([filter[0], filter[1]]);
                    }
                }
            }

            cookiesFilters.push(['srr_search', 1]);
        }

        {/literal}
        lang.item_deleted = '{$lang.item_deleted}';
        lang.account      = '{$lang.account}';
        lang.srr_rating   = '{$lang.srr_rating}';
        {literal}

        srrCommentsGrid = new gridObj({
            key             : 'srr_comments',
            id              : 'grid',
            ajaxUrl         : rlPlugins + 'sellerReviews/admin/seller_reviews.inc.php?q=ext',
            defaultSortField: 'Date',
            defaultSortType : 'DESC',
            title           : lang.ext_manager,
            remoteSortable  : true,
            filters          : cookiesFilters,
            fields           : [
                {name: 'ID', mapping: 'ID', type: 'int'},
                {name: 'Title', mapping: 'Title', type: 'string'},
                {name: 'Rating', mapping: 'Rating', type: 'int'},
                {name: 'Author', mapping: 'Author'},
                {name: 'Account', mapping: 'Account'},
                {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                {name: 'Status', mapping: 'Status', type: 'string'},
            ],
            columns: [{
                header   : lang.ext_id,
                dataIndex: 'ID',
                width    : 50,
                fixed     : true,
                id       : 'rlExt_black_bold'
            },{
                header   : lang.ext_title,
                dataIndex: 'Title',
                width    : 60,
                id       : 'rlExt_item'
            },{
                header   : lang.srr_rating,
                dataIndex: 'Rating',
                width    : 5,
                renderer : function (rating) {
                    return rating ? rating : lang.ext_not_available;
                }
            },{
                header   : lang.srr_author,
                dataIndex: 'Author',
                width    : 12,
                renderer : function (author) {
                    return author && author.ID && author.Full_name
                        ? (`<a href="${rlUrlHome}index.php?controller=accounts`
                            + `&action=view&userid=${author.ID}">`
                            + `${author && author.Full_name}</a>`
                        )
                        : author;
                }
            },{
                header   : lang.account,
                dataIndex: 'Account',
                width    : 12,
                renderer : function (account) {
                    return account && account.ID && account.Full_name
                        ? (`<a href="${rlUrlHome}index.php?controller=accounts`
                            + `&action=view&userid=${account.ID}">`
                            + `${account && account.Full_name}</a>`
                        )
                        : account;
                }
            },{
                header   : lang.ext_date,
                dataIndex: 'Date',
                fixed     : true,
                width    : 100,
                renderer : Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))
            },{
                header   : lang.ext_status,
                dataIndex: 'Status',
                fixed     : true,
                width    : 80,
                editor   : new Ext.form.ComboBox({
                    store        : [['active', lang.ext_active], ['approval', lang.ext_approval]],
                    displayField : 'value',
                    valueField   : 'key',
                    typeAhead    : true,
                    mode         : 'local',
                    triggerAction: 'all',
                    selectOnFocus: true
                }),
            },{
                header   : lang.ext_actions,
                width    : 70,
                fixed     : true,
                dataIndex: 'ID',
                sortable : false,
                renderer : function(id) {
                    let imgEdit = `<img class="edit" ext:qtip="${lang.ext_edit}" src="${rlUrlHome}img/blank.gif">`;

                    return `<div style="text-align: center;">
                                <a href="${rlUrlController}&action=edit&id=${id}">${imgEdit}</a>
                                <img class="remove"
                                    ext:qtip="${lang.delete}"
                                    src="${rlUrlHome}img/blank.gif"
                                    onclick="rlConfirm('${lang.ext_notice_delete}', 'srrDeleteComment', '${id}')"
                                >
                            </div>`;
                }
            }]
        });

        srrCommentsGrid.init();
        grid.push(srrCommentsGrid.grid);
    });

    let srrDeleteComment = function(id) {
        flynax.sendAjaxRequest('srrDeleteComment', {id: id}, function(response) {
            if (response.status === 'OK') {
                srrCommentsGrid.reload();
                printMessage('notice', lang.item_deleted);
            } else {
                printMessage('error', lang.system_error);
            }
        });
    }
{/literal}</script>

<!-- SellerReviews comments tpl -->
