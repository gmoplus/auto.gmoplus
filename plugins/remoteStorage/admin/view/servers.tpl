<!-- RemoteStorage servers tpl -->

<div id="grid"></div>
<script>
    let rsServersGrid;

    {literal}
    $(function () {
        Ext.grid.defaultColumn = function(config) {
            Ext.apply(this, config);
            if (!this.id) {
                this.id = Ext.id();
            }
            this.renderer = this.renderer.createDelegate(this);
        };

        Ext.grid.defaultColumn.prototype = {
            init: function(grid) {
                this.grid = grid;
                this.grid.on('render', function() {
                    let view = this.grid.getView();
                    view.mainBody.on('mousedown', this.onMouseDown, this);
                }, this);
            },
            onMouseDown: function(e, t) {
                if (t.className && t.className.indexOf('x-grid3-cc-' + this.id) !== -1) {
                    e.stopEvent();
                    let index  = this.grid.getView().findRowIndex(t);
                    let record = this.grid.store.getAt(index);

                    if (!record.data[this.dataIndex]) {
                        rsSetMainServer(record.data.ID);
                    }
                }
            },
            renderer : function(v, p) {
                p.css += ' x-grid3-check-col-td';
                return '<div '
                    + (!v ? ('ext:qtip="' + lang.rs_main_server_notice + '"') : '')
                    + ' class="x-grid3-check-col'
                    + (v ? '-on' : '')
                    + ' x-grid3-cc-'
                    + this.id
                    + '">&#160;</div>';
            }
        };

        let defaultColumn = new Ext.grid.defaultColumn({
            header   : lang.rs_main_server,
            dataIndex: 'Main_server',
            width    : 12,
        });

        let cookiesFilters = [];

        {/literal}
        lang.item_deleted = '{$lang.item_deleted}';
        {literal}

        rsServersGrid = new gridObj({
            key             : 'rs_servers',
            id              : 'grid',
            ajaxUrl         : `${rlPlugins}remoteStorage/admin/remote_storage.inc.php?q=ext`,
            defaultSortField: 'ID',
            defaultSortType : 'ASC',
            title           : lang.ext_manager,
            remoteSortable  : true,
            filters          : cookiesFilters,
            fields           : [
                {name: 'ID', mapping: 'ID', type: 'int'},
                {name: 'Title', mapping: 'Title', type: 'string'},
                {name: 'Type', mapping: 'Type', type: 'string'},
                {name: 'Bucket', mapping: 'Bucket', type: 'string'},
                {name: 'Number_of_files', mapping: 'Number_of_files', type: 'string'},
                {name: 'Number_of_files_origin', mapping: 'Number_of_files_origin', type: 'int'},
                {name: 'Main_server', mapping: 'Main_server', type: 'boolean'},
                {name: 'Status', mapping: 'Status', type: 'string'},
                {name: 'Status_key', mapping: 'Status_key', type: 'string'},
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
                header   : lang.rs_service_provider,
                dataIndex: 'Type',
                width    : 15,
                renderer : function (type) {
                    return lang[`rs_server_type_${type}`] ? lang[`rs_server_type_${type}`] : lang.ext_not_available;
                }
            },{
                header   : lang.rs_bucket,
                dataIndex: 'Bucket',
                width    : 25,
            },{
                header   : lang.rs_number_of_files,
                dataIndex: 'Number_of_files',
                width    : 15,
            },
            defaultColumn,
            {
                header   : lang.ext_status,
                dataIndex: 'Status',
                fixed     : true,
                width    : 120,
            },{
                header   : lang.ext_actions,
                width    : 70,
                fixed     : true,
                dataIndex: 'ID',
                sortable : false,
                renderer : function(id, element, row) {
                    let imgEdit = `<img class="edit" ext:qtip="${lang.ext_edit}" src="${rlUrlHome}img/blank.gif">`,
                        onclickAttribute;

                    if (row.data.Number_of_files_origin > 0) {
                        onclickAttribute = `onclick="rsDeleteServerPopup('${lang.rs_bucket_remove_notice}'.replace('{bucket}', '${row.data.Title}'), ${id}, ${row.data.Number_of_files_origin})"`;
                    } else {
                        onclickAttribute = `onclick="rlConfirm('${lang.ext_notice_delete}', 'rsDeleteServer', '${id}')"`;
                    }

                    return `<div style="text-align: center;">
                                <a href="${rlUrlController}&action=edit&id=${id}">${imgEdit}</a>
                                <img class="remove" ext:qtip="${lang.delete}" src="${rlUrlHome}img/blank.gif" ${onclickAttribute}>
                            </div>`;
                }
            }]
        });

        rsServersGrid.plugins.push(defaultColumn);

        rsServersGrid.init();
        grid.push(rsServersGrid.grid);
    });

    /**
     * Popup with downloading uploaded files locally before removing of bucket
     * @param message
     * @param bucketID
     * @param totalFiles
     */
    const rsDeleteServerPopup = function(message, bucketID, totalFiles) {
        $(document).flModal({
            caption: lang.warning,
            width  : 750,
            height : 'auto',
            content: '<div id="modal-container">' + lang.loading + '</div>',
            onReady: function() {
                let $closeButton = $('div.modal-window > div > span:last');

                flynax.sendAjaxRequest('rsPrepareReverseMigration', {bucketID: bucketID, total: totalFiles},
                    function(response) {
                        if (response.status === 'OK' && response.html) {
                            $('#modal-container').html('').append(
                                $('<p>').html(message),
                                $('<div>', {id: 'migration', style: 'margin-top: 10px'}).html(response.html)
                            );
                        } else {
                            $closeButton.trigger('click');
                            printMessage('error', response.message ? response.message : lang.system_error);
                        }
                    }, function () {
                        printMessage('error', lang.system_error);
                    }
                );
            }
        });
    }

    /**
     * @param id
     */
    const rsDeleteServer = function(id) {
        flynax.sendAjaxRequest(
            'rsDeleteServer', {id: id},
            function(response) {
                if (response.status === 'OK') {
                    $('#cf_migration_button').hide();
                    rsServersGrid.reload();
                    printMessage('notice', lang.item_deleted);
                } else {
                    printMessage('error', response.message ? response.message : lang.system_error);
                }
            }, function (response) {
                printMessage('error', response.message ? response.message : lang.system_error);
            }
        );
    }

    /**
     * @param id
     */
    const rsSetMainServer = function(id) {
        flynax.sendAjaxRequest(
            'rsSetMainServer', {id: id},
            function(response) {
                if (response.status === 'OK') {
                    rsServersGrid.reload();
                    printMessage('notice', '{/literal}{$lang.config_saved}{literal}');
                } else {
                    printMessage('error', response.message ? response.message : lang.system_error);
                }
            }, function (response) {
                rsServersGrid.reload();
                printMessage('error', response.message ? response.message : lang.system_error);
            }
        );
    }
{/literal}</script>

<!-- RemoteStorage servers tpl end -->
