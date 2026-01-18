
/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.7.0
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: ACCOUNT_FIELDS.JS
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
/**
 * All logic which is related to the Account fields synchronization should be described here
 *
 * @constructor
 */
var AccountSyncAccountFieldsClass = function () {
    /**
     * @type {AccountSyncAccountFieldsClass}
     */
    var self = this;

    /**
     * Initialize all events of the class
     */
    this.init = function () {
        self.registerEvents();
    };

    /**
     * Does field is exist and synchronized with provided domain
     *
     * @param {string} fieldKey - Registration field key
     * @param {string} domain - Domain with which you want to check synchronization
     *
     * @returns {boolean}
     */
    this.isFieldExistInDomain = function (fieldKey, domain) {
        return $('div[data-fields-of=\'' + domain + '\']').find(
            'div[data-key=\'' + fieldKey + '\']:not(".field_unsync")').length > 0;
    };

    /**
     * Get first synchronized domain of the provided registration field
     *
     * @param {string} fieldKey - Registration field key
     *
     * @returns {string}
     */
    this.getFirstDomainOfField = function (fieldKey) {
        if (self.isFieldExistInDomain(fieldKey, rlAccountSync['url'])) {
            return rlAccountSync['url'];
        }

        var $blockWithFields = $('div[data-key=\'' + fieldKey + '\']:not(".field_unsync")');
        return $blockWithFields.length > 0 ? $blockWithFields.first().parent().data('fields-of') : '';
    };

    /**
     *
     * @param fieldKey
     * @param domain
     * @returns {jQuery}
     */
    this.getUnsyncFieldByDomain = function (fieldKey, domain) {
        return $('div[data-fields-of="' + domain + '"]').find('div.' + fieldKey + '.field_unsync');
    };

    /**
     *
     * @param fieldKey
     * @param fromDomain
     * @param toDomain
     * @param callback
     * @returns {boolean}
     */
    this.syncFieldWithDomain = function (fieldKey, fromDomain, toDomain, callback) {
        if (!fieldKey || !fromDomain || !toDomain) {
            return false;
        }

        var data = {
            item: 'as_syncAccountField',
            from: {
                domain: fromDomain,
                fieldKey: fieldKey,
            },
            to: {
                domain: toDomain,
                fieldKey: fieldKey,
            },
        };

        AccountSyncAdminUtils().sendAjax(data, function (response) {
            if ('function' === typeof callback) {
                callback(response);
            }
        });
    };

    /**
     * Register all events of the class
     */
    this.registerEvents = function () {
        $('.field_unsync').off('click').click(function (e) {
            var $selectedField = $(this);
            var fieldKey = $(this).data('key');
            var selectedDomain = $(this).parent().data('fields-of');
            var syncFrom = {
                domain: self.getFirstDomainOfField(fieldKey),
                fieldKey: fieldKey,
            };

            $allAsyncFields = $('div[data-key=\'' + fieldKey + '\'].field_unsync');
            if ($allAsyncFields.length > 1) {
                var refused_content = '<div class="x-hidden" id="sync-field-window">';
                refused_content += '<div class="x-window-header">' + lang['as_synchronize_with_domains'] + '</div>';
                refused_content += '<div class="x-window-body" style="padding:10px 15px">';
                refused_content += '<div class="sync-with-domains">';
                refused_content += '<input id="sync-from-domain" type="hidden" value="' + syncFrom.domain + '"> ';

                $allAsyncFields.parents('fieldset').each(function (index, item) {
                    var domain = $(item).find('.legend_form_section').data('url');
                    var host = $(item).find('.legend_form_section').data('host');
                    var isChecked = domain === selectedDomain ? 'checked = \'checked\'' : '';

                    refused_content += '<div class="sync-domain">';
                    refused_content += '<label for="domain_' + index + '">';
                    refused_content += '<input  ' + isChecked + ' id="domain_' + index + '" class="sync-with-domain" ';
                    refused_content += 'value="' + domain + '" type="checkbox"> ' + host;
                    refused_content += '</label>';
                    refused_content += '</div>';
                });

                refused_content += '</div>';
                refused_content += '<div id="sync-actions"><button id="run-sync" type="button">'+lang['as_sync']+'</button></div>';
                refused_content += '</div>';

                $('body').after(refused_content);

                var popup = new Ext.Window({
                    applyTo: 'sync-field-window',
                    layout: 'fit',
                    width: 400,
                    height: 'auto',
                    closeAction: 'hide',
                    plain: true,
                });

                popup.show();

                $('#run-sync').click(function () {
                    var $runSyncBtn = $(this);
                    var previousValue = $runSyncBtn.text();
                    var count = 0;
                    $runSyncBtn.text(lang['loading']);

                    $('.sync-with-domain:checked').each(function (index, item) {
                        var domains = {
                            from: $('#sync-from-domain').val(),
                            to: $(item).val(),
                        };

                        self.syncFieldWithDomain(fieldKey, domains.from, domains.to, function (response) {
                            count++;

                            self.getUnsyncFieldByDomain(fieldKey, domains.to).removeClass('field_unsync');
                            if (response.status == 'OK') {
                                self.getUnsyncFieldByDomain(fieldKey, domains.to).removeClass('field_unsync');
                            }

                            if (count === $('.sync-with-domain:checked').length) {
                                $runSyncBtn.text(previousValue);
                                $('.x-tool-close').trigger('click');
                                AccountSyncAdminUtils().updateCache();
                                printMessage('notice', lang['as_fields_synchronized']);
                            }
                        });
                    });
                });

                return;
            }

            Ext.MessageBox.confirm(lang['confirm'], lang['as_do_you_want_sync_field'], function (btn) {
                if (btn === 'yes') {
                    var toDomain = $selectedField.parent().data('fields-of');

                    self.syncFieldWithDomain(syncFrom.fieldKey, syncFrom.domain, toDomain, function (response) {
                        AccountSyncAdminUtils().updateCache();
                    });
                }
            });
        });
    };
};

var accountSyncAccountFields = new AccountSyncAccountFieldsClass();
accountSyncAccountFields.init();
