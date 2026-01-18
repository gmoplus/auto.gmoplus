

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.7.0
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: ADMIN.JS
 *
 *	The software is a commercial product delivered under single, non-exclusive,
 *	non-transferable license for one domain or IP address. Therefore distribution,
 *	sale or transfer of the file in whole or in part without permission of Flynax
 *	respective owners is considered to be illegal and breach of Flynax License End
 *	User Agreement.
 *
 *	You are not allowed to remove this information from the file without permission
 *	of Flynax respective owners.
 *
 *	Flynax Classifieds Software 2022 |  All copyrights reserved.
 *
 *	https://www.flynax.com/
 *
 ******************************************************************************/

var AccountSyncClass = function () {
    var self = this;
    this.grid = {};

    this.ui = {
        $grid: $('#grid'),
        form: {
            $url: $('#as-flynax-url'),
            admin: {
                $username: $('#as-admin-username'),
                $password: $('#as-admin-password'),
            },
            $submit: $('#as-synchronize'),
            submit_name: $('#as-synchronize').val(),
        },
        triggerForm: function () {
            show('new_listing', '#action_blocks div');
        },
    };

    this.init = function () {
        self.enableEvents();
    };

    this.enableGridEvents = function () {

        self.ui.$grid.on('click', '.account-sync-disconnect', function () {
            var domain = $(this).data('domain');
            Ext.MessageBox.confirm(lang['confirm'], lang['as_disconnected_confirm'], function (btn) {
                if (btn === 'yes') {
                    self.disconnectByDomain(domain, function () {
                        itemsGrid.reload();
                    });
                }
            });
        });
    };

    this.updateCache = function (callback) {
        var $clickedButton = $(this);
        var buttonText = $clickedButton.find('.center_list').text();
        $clickedButton.find('.center_list').text(lang['loading']);

        var data = {
            item: 'as_updateCache',
            mode: 'as_updateCache',
        };

        self.sendAjax(data, function (response, status) {
            if ('function' === typeof callback) {
                callback(response, status);
            }
            $clickedButton.find('.center_list').text(buttonText);

            if (response.status == 'OK') {
                printMessage('notice', response.message);
                self.refreshGrid();
            }
        });
    };

    this.edit = function (id) {
        self.getTokenInfo(id, function (tokenInfo) {
            self.ui.triggerForm();
        });
    };

    this.getTokenInfo = function (id, callback) {
        var data = {
            item: 'ac_synchronize',
            mode: 'ac_synchronize',
            id: id,
        };
    };

    this.disconnectByDomain = function (domain, callback) {
        var data = {
            item: 'ac_disconnect',
            mode: 'ac_disconnect',
            domain: domain,
        };

        self.sendAjax(data, function (response, status) {
            var messageType = status === 'ERROR' ? 'error' : 'notice';
            var message = response.message ? response.message : '';

            printMessage(messageType, message);
            callback(status);
        });
    };

    this.enableEvents = function () {
        //  Move #sync-cache declaration to the UI class
        $('#new_listing input').keypress(function (e) {
            if (e.which === 13) {
                self.ui.form.$submit.trigger('click');
            }
        });

        $('#sync-cache').click(function () {
            var $clickedButton = $(this);
            var buttonText = $clickedButton.find('.center_list').text();
            $clickedButton.find('.center_list').text(lang['loading']);

            self.updateCache(function (response, status) {
                $clickedButton.find('.center_list').text(buttonText);

                if (status == 'OK') {
                    printMessage('notice', response.message);
                    // self.refreshGrid();
                }
            });
        });

        self.ui.form.$submit.click(function () {
            var buttonText = $(this).val();
            $(this).val(lang['ext_loading']);
            var validationRules = {};
            validationRules[self.ui.form.$url.selector] = 'required';
            validationRules[self.ui.form.admin.$username.selector] = 'required';
            validationRules[self.ui.form.admin.$password.selector] = 'required';

            if (!self.validate(validationRules)) {
                self.ui.form.$submit.val(self.ui.form.submit_name);
                return false;
            }


            var url = self.ui.form.$url.val();
            var admin = {
                'username': self.ui.form.admin.$username.val(),
                'password': self.ui.form.admin.$password.val(),
            };

            var data = {
                item: 'ac_synchronize',
                mode: 'ac_synchronize',
                url: url,
                admin: admin,
            };

            self.sendAjax(data, function (response, status) {
                self.ui.form.$submit.val(self.ui.form.submit_name);
                if (response.status === 'OK') {
                    itemsGrid.reload();
                    show('new_listing', '#action_blocks div');
                    printMessage('notice', response.message);

                    self.clearForm(true);

                    self.exchangeAccountTypesWith(url);
                }
                else if (response.status === 'ERROR' && response.message) {
                    printMessage('error', response.message);
                }
               
            });
        });
    };

    this.exchangeAccountTypesWith = function (domain) {
        var data = {
            item: 'ac_exchangeAccountTypes',
            mode: 'ac_exchangeAccountTypes',
            domain: domain,
        };

        self.sendAjax(data, function () {});
    };

    this.validate = function (rules) {
        var isValid = true;
        self.clearForm();

        $.each(rules, function (selector, rule) {
            if (!$(selector).val()) {
                isValid = false;
                $(selector).addClass('error');
            }
            else if (selector == '#as-flynax-url' && !self.isURL($(selector).val())) {
                isValid = false;
                $(selector).addClass('error');
            }
        });
       
        if (!isValid) {
            printMessage('error', lang['required']);
        }

        return isValid;
    };

    this.isURL = function(str){
        return /^(https?):\/\/((?:[a-z0-9.-]|%[0-9A-F]{2}){3,})(?::(\d+))?((?:\/(?:[a-z0-9-._~!$&'()*+,;=:@]|%[0-9A-F]{2})*)*)(?:\?((?:[a-z0-9-._~!$&'()*+,;=:\/?@]|%[0-9A-F]{2})*))?(?:#((?:[a-z0-9-._~!$&'()*+,;=:\/?@]|%[0-9A-F]{2})*))?$/i.test(str);
    }

    this.clearForm = function (withData) {
        self.ui.form.$url.removeClass('error');
        self.ui.form.admin.$username.removeClass('error');
        self.ui.form.admin.$password.removeClass('error');

        if (withData) {
            self.ui.form.$url.val('');
            self.ui.form.admin.$username.val('');
            self.ui.form.admin.$password.val('');
        }
    };

    this.sendAjax = function (data, callback) {
        if (typeof flUtilClass == 'function') {
            var flUtil = new flUtilClass();
            flUtil.ajax(data, function (response, status) {
                callback(response, status);
            });

            return;
        }

        $.post(rlConfig['ajax_url'], data,
            function (response) {
                callback(response, response.status);
            }, 'json');
    };

    this.refreshGrid = function () {

        if (typeof itemsGrid !== undefined) {
            return false;
        }
        
        itemsGrid.reload();
    };

    this.setGrid = function (gridObj) {
        if (typeof gridObj === 'object' && itemsGrid.constructor.name === 'gridObj') {
            self.grid = gridObj;
        }
    };

    this.getGrid = function () {
        return self.grid;
    };

    return self;
};

var AccountSyncAdminUtils = function () {
    sendAjax = function (data, callback) {
        $.post(rlConfig['ajax_url'], data,
            function (response) {
                callback(response);
            }, 'json');
    };

    return {
        sendAjax: function (data, callback) {
            sendAjax(data, callback);
        },
        updateCache: function () {
            var accountSync = new AccountSyncClass();
            accountSync.updateCache();
        },
    };
};
