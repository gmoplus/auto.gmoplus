
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: MANAGE_USERS.JS
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2025 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/
/**
 * All specific logic which is related to the 'Manage users' admin page will be located here
 */
var AccountSyncManageUsersClass = function() {
    /**
     * @type {AccountSyncManageUsersClass}
     */
    var self = this;

    /**
     * manager urers grid
     */
    var manageUsersGrid;

    /**
     * Class initialization
     */
    this.init = function() {
        self.enableGrid();
        self.enableEvents();
    };

    /**
     * Enable all event on the 'manage_users' page
     */
    this.enableEvents = function() {
        $('#fetch-users').click(function() {
            self.fetchUsers(true);
        });
    };

    this.fetchUsers = function(msg) {
        var $labelSpan = $(this).find('span.center_build');
        var previousMessage = $labelSpan.text();
        $labelSpan.text(lang['loading']);
        var data = {
            item: 'as_apFetchUsers'
        };

        AccountSyncAdminUtils().sendAjax(data, function(response) {
            var messageType = response.status === 'OK' ? 'notice' : 'error';
            var message = response.status === 'OK'
                ? lang['cache_updated']
                : lang['as_something_wrong'];
            
            $labelSpan.text(previousMessage);
            if (msg) {
                printMessage(messageType, message);
            }
            
            self.enableGrid();
        });
    }

    /**
     * Render grid of the page
     */
    this.enableGrid = function() {
        manageUsersGrid = new gridObj({
            key: 'as_manage_users_page_grid',
            id: 'manage_users',
            ajaxUrl: rlPlugins +
                'accountSync/admin/account_sync.inc.php?action=manage_users&q=ext',
            defaultSortField: 'id',
            remoteSortable: true,
            title: lang['as_manage_users'], 
            fields: [
                {name: 'Name', mapping: 'name'}
            ], columns: [
                {
                    header: lang['account_type'],
                    dataIndex: 'Name',
                    id: 'rlExt_item_bold'
                }
            ]
        });

        manageUsersGrid.init();
        manageUsersGrid.store.addListener('load', function(e) {
            try {
                var rows = e.data.items;
                var firstRowInfo = rows[0].json.info;

                firstRowInfo.forEach(function(info, index) {
                    var dynamicColumn = {
                        header: info.domain + ' ' + lang['as_total_unique'], 
                        dataIndex: 'domain_' + index,
                        fixed: false,
                        width: 350,
                        renderer: function(val, ext, row, rowIndex, cellIndex) {
                            var rowData = rows[rowIndex];
                            var cellData = rowData.json.info[cellIndex - 1];
                            var total = cellData.stat ? cellData.stat.total : 0;
                            var unique = cellData.stat
                                ? cellData.stat.unique
                                : 0;

                            var val = '<span class="unique-count">'+total+'/'+unique+'</span>';
                            if (unique) {
                                val += '<img data-domain="'+info.url+'" data-type="'+row.json.key+'"';
                                val += 'class="update" ext:qtip="'+lang['as_sync']+'" src="'+rlUrlHome+'img/blank.gif">';
                            }

                            return val;
                        }
                    };

                    manageUsersGrid.getInstance().columns.push(dynamicColumn);
                });

                manageUsersGrid.init();
            } catch (e) {

            }
        });

        grid.push(manageUsersGrid.grid);
        $("#manage_users").on('click', 'img.update', this.confirmToSync)
    };

    this.confirmToSync = function() {
        var domain = $(this).data("domain");
        var type = $(this).data("type");

        rlConfirm(lang['as_synchronize_confirm'], "accountSyncManageUsers.requestToSync", Array( domain+'"', '"'+ type));
    }

    /**
     * request to Sync account on the other domains
     * string domain - domain 
     * string type   - account type
     */
    this.requestToSync = function(domain, type) {
        
        this.setSyncOptions();

        this.syncOptions.popup = this.buildProgressPopup(lang['as_synchronize_to_websites']);

        this.usersSync(domain, type, function(info){

            if (self.duplicate) {
                var msg = '<div>'+lang['as_synchronize_to_websites_completed']+'</div><div>'+lang['as_duplicate_user_info']+'</div>';
                printMessage('notice', msg);
            }
            else {
                printMessage('notice', lang['as_synchronize_to_websites_completed']);
            }

            self.fetchUsers(false);

            self.syncOptions.start = 0;
            self.syncOptions.popup.hide();
        });

    }

    /**
     * Sync accounts
     * @param string   domain   - Redomain
     * @param string   type     - account type
     * @param object   add_data - Additional data to pass to ajax
     * @param function callback - Callback function to call on finish
     */
    this.usersSync = function(domain, type, callback){
        if (!domain || !type) {
            console.log('usersSync() failed, no mode parameter specified');
            return;
        }

        var data = {
            item: 'as_syncUsers',
            start: this.syncOptions.start,
            limit: this.syncOptions.count,
            url: domain,
            type: type,
        };

        AccountSyncAdminUtils().sendAjax(data, function(response) {
            setTimeout(function(){

                if (response.duplicate) {
                    self.duplicate = true;
                }  

                if (response.status == 'next') {
                    self.syncOptions.start = parseInt(self.syncOptions.start) + parseInt(self.syncOptions.count);

                    self.syncOptions.popup.updateProgress(response.progress);

                    self.usersSync(domain, type, callback);
                }
                else if (response.status == 'ERROR') {
                    printMessage('error', lang['as_something_wrong']);
                    self.syncOptions.popup.hide();
                    self.setSyncOptions();
                } else {
                    self.syncOptions.popup.updateProgress(1);

                    if (typeof callback == 'function') {
                        callback.call(response);
                    } else {
                        setTimeout(function(){
                            self.syncOptions.popup.hide();
                            self.setSyncOptions();
                        }, 1000);

                    }
                }
            
            }, 1000);
        });
    }

    /**
     * Set/reset sync options
     */
    this.syncOptions = {};

    /**
     * Set sync options
     */
    this.setSyncOptions = function(){
        this.syncOptions = {
            count: rlConfig['as_user_limit'],
            start: 0,
            popup: false,
            progress: false
        };
    }

    /**
     * Build ext progress popup
     * @param  string title - Popup title
     * @param  string msg   - Popup message
     * @return object       - Ext progress object
     */
    this.buildProgressPopup = function(title, msg){
        msg = msg ? msg : lang['as_synchronize_in_progress'];

        var popup = Ext.MessageBox.show({
            title: title,
            msg: msg,
            buttons : Ext.MessageBox.CANCEL,
            progress: true,
            width: 300,
            wait: false
        });

        popup.updateProgress(0);

        return popup;
    }
};
var accountSyncManageUsers = new AccountSyncManageUsersClass();
