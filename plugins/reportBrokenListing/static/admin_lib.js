
/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : ADMIN_LIB.JS
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2024
 *	https://www.flynax.com
 *
 ******************************************************************************/

/**
 * Report point object
 */
var ReportPoint = function () {
    /**
     * @type {ReportPoint}
     */
    var self = this;
    /**
     * @type {ReportBrokenListings}
     */
    this.reportBrokenListings = false;

    /**
     * @type {string}
     */
    this.activeAction = '';

    /**
     * @type  {boolean}
     */
    this.panelVisibility = false;

    /**
     * @type {gridObj}
     */
    this.grid = false;

    /**
     * Initial Method
     */
    this.init = function () {
        if (typeof(ReportBrokenListings ) == 'function') {
            self.reportBrokenListings = new ReportBrokenListings();
        }

        $('#add-point').click(function () {
            self.renderSubmitBtn('add');
        });
    };

    /**
     * Adding Report Point form validation
     * @param pointInputs {array} - All inputs array
     * @returns {boolean}         - Is form valid
     */
    this.isValid = function (pointInputs) {
        var is_valid = true;

        pointInputs.forEach(function (element, index, array) {
            if (element.data('required')) {
                var rule = element.data('required-rule') ? element.data('required-rule') : 'required';
                if (!self.validate(element.val(), rule)) {
                    element.addClass('error');
                    is_valid = false;
                } else {
                    element.removeClass('error');
                }
            }
        });

        return is_valid;
    };

    /**
     * Validate value by rule.
     *
     * @param {any}    value - Validating value
     * @param {string} rule  - Validation rule
     * @returns {boolean}    - Does value has error
     */
    this.validate = function(value, rule) {
        var valid = true;

        if (!value) {
            valid = false;
        }

        switch (rule) {
            case '>0':
                if (value <= 0) {
                    valid = false;
                }
                break;
        }

        return valid;
    };

    /**
     * Save Report Points to database
     *
     * @param event {object} - Submit button click event
     * @returns {boolean}    - Is saving has been passed succsfully
     */
    this.addPoints = function (event) {
        var formData = self.collectFormData(event.target);

        if (!formData) {
            return false;
        }

        var data = {
            item: 'RBLAddReportItem',
            formData: formData
        };

        self.reportBrokenListings.sendAjax(data, function (response) {
            if (response.status == 'OK') {
                printMessage('notice', response.message);
                self.hidePanel();
                self.clearForm();
                self.grid.reload();
            } else {
                printMessage('error', response.message);
            }
        });
    };

    this.setGrid = function (grid) {
        self.grid = grid;
    };

    /**
     * Edit report points in database
     * @param event {object} - Submit button click event
     * @returns {boolean}    - Edit action is succesfull
     */
    this.editPoints = function (event) {
        var formData = self.collectFormData(event.target);
        var key = $(event.target).attr('datakey');

        if (!formData || !key) {
            return false;
        }

        var data = {
            item: 'RBLEditReportItem',
            key: key,
            formData: formData
        };

        self.reportBrokenListings.sendAjax(data, function (response) {
            if (response.status == 'OK') {
                printMessage('notice', response.message);
                self.hidePanel();
                self.clearForm();
                self.grid.reload();
            } else {
                printMessage('error', response.message);
            }
        });
    };

    /**
     * Render Submit button on the Report point panel
     *
     * @param {string} action      - Submit button action type: {edit, add}
     * @param {string} clickedElem - Clicked element which was triggered this method
     */
    this.renderSubmitBtn = function (action, clickedElem) {
        if (action == 'add') {
            self.clearForm();
        }

        self.removeErrorClass();
        self.togglePanel(action);
        self.activeAction = action;
        var $submit_area = $('#submit_area');
        $submit_area.unbind('click');
        $submit_area.find('input.rendered-btn').remove();

        var id = 'edit_report_points_submit';
        var text = lang['edit'];
        var callback = 'editPoints';

        if (action == 'add') {
            id = 'add_report_points_submit';
            text = lang['add'];
            callback = 'addPoints';
        }

        var attributes = {
            id: id,
            type: 'button',
            class: 'rendered-btn',
            value: text
        };

        if (clickedElem && $(clickedElem).attr('data-key')) {
            attributes.dataKey = $(clickedElem).attr('data-key');
        }

        var button = $('<input>', attributes);
        $submit_area.prepend(button);

        $(button).click(function (e) {
            self[callback](e);
        });
    };

    /**
     * Clear report point panel form
     */
    this.clearForm = function () {
        $('#add_report_point').find('input[type="text"]').each(function () {
            $(this).removeClass('error');
            $(this).val('');
        });
    };

    /**
     * Clear all marked as error fields
     */
    this.removeErrorClass = function() {
        $('#add_report_point').find('input[type="text"]').each(function () {
            $(this).removeClass('error');
        });
    };

    /**
     * Toggle report point panel bettween collapsed and expanded
     *
     * @param {string} action - {add, edit}
     */
    this.togglePanel = function (action) {
        if (self.isPanelVisible()) {
            self.hidePanel();
            if (self.activeAction && self.activeAction != action && action == 'add') {
                self.showPanel();
            }
            return;
        }

        self.showPanel();
    };

    /**
     * Show report point panel
     */
    this.showPanel = function(){
        self.panelVisibility = true;
        $('#add_report_point').slideDown('slow');
    };

    /**
     * Hide report point panel
     */
    this.hidePanel = function () {
        self.panelVisibility = false;
        $('#add_report_point').slideUp('slow');
    };

    /**
     * Is report point panel visible
     * @returns {boolean}
     */
    this.isPanelVisible = function () {
        return self.panelVisibility;
    };

    /**
     * Trigger after clicking on the "Edit" action in the ExtJs grid
     *
     * @param {object}  elem - Clicked event object
     * @param {gridObj} grid - ExtJs grid object
     */
    this.onEditPoint = function(elem, grid) {
        var loadingMask = new Ext.LoadMask(Ext.get('grid'), {msg: lang['loading']});
        self.hidePanel();

        loadingMask.show();
        var ajaxData = {
            item: 'RBLGetPointsByKey',
            key: $(elem).attr('data-key')
        };

        self.reportBrokenListings.sendAjax(ajaxData, function (response) {
            loadingMask.hide();
            if (response.status == 'OK') {
                $.each(response.body.Phrases, function (key, value) {
                    $('#add-report-points').find('input[name="' + key + '"]').val(value);
                });
                $('#reports_count_to_critical').val(response.body.Reports_to_critical);
                $('#point-status').find('option[value="' + response.body.Status + '"]').prop('selected', true);

                self.renderSubmitBtn('edit', elem);
            }
        });
    };

    /**
     * Collect grid panel form data
     *
     * @param   {object} submit_input - Form submit button object
     * @returns {array}  formData     - Collected form data
     */
    this.collectFormData = function (submit_input) {
        var pointInputs = new Array();
        var should_collect = ['textarea', 'select', 'input[type="text"]'];

        should_collect.forEach(function(element){
           $(submit_input).parents('form').find(element).each(function(){
                pointInputs.push($(this));
           });
        });

        if (!self.isValid(pointInputs)) {
            printMessage('error', lang['required_fields']);
            return false;
        }

        var formData = {};
        pointInputs.forEach(function (element) {
            element.removeClass('error');
            var name = element.attr('name');
            formData[name] = element.val();
        });

        return formData;
    };

    /**
     * Delete point
     * @param   {string} key - Removing point key
     * @returns {boolean}    - Status of the removing process
     */
    this.deletePoint = function (key) {
        if (!key) {
            return false;
        }
        var data = {
            item: 'RBLDeleteReportPoint',
            key: key
        };

        self.reportBrokenListings.sendAjax(data, function (response) {
            if (response.status == 'OK') {
                printMessage('notice', response.message);

                $assigningOption = $('#assigning-to').find('option[value="' + key + '"]');
                $assigningOption.attr('disabled', true);
                $assigningOption.remove();

                self.grid.reload();
            } else {
                if (response.body.reports && response.body.reports.length > 0) {
                    show('delete_block');

                    var $removeByDropDown = $('.remove_by');
                    $removeByDropDown.prop('checked', false);
                    self.fillRemoveBlockInfo(response.body.reports.length);

                    $removeByDropDown.change(function () {
                        $('#delete-finally-btn').removeAttr('disabled');
                        var remove_by = $(this).attr('id');
                        var $allLabels = $('#assigning-to');
                        if (remove_by == 'to-another-label') {

                            $allLabels.find('option[value="' + key + '"]').attr('disabled', true);
                            $allLabels.find('option:not([disabled]):first').attr('selected', true);
                            $('#all-labels').show();
                        } else {
                            $allLabels.find('option[value="' + key + '"]').attr('disabled', false);
                            $('#all-labels').hide();
                        }
                    });

                    $('#delete_block').find('.cancel').click(function () {
                        show('delete_block');

                        $removeByDropDown.unbind('change');
                        $('#all-labels').hide();
                    });

                    $('#delete-finally-btn').click(function(){
                        var to_key = $('.remove_by:checked').attr('id') == 'to-another-label'
                            ? $('#assigning-to').val()
                            : false;
                        self.deleteWithAssign(key, to_key);

                        $removeByDropDown.unbind('change');
                        $('#all-labels').hide();
                    });


                }
            }
        });
    };

    /**
     * Fill Remove block with information regarding associated listing.
     *
     * @param {number} reports_number - Reports count
     */
    this.fillRemoveBlockInfo = function (reports_number) {
        var $removing_block_info = $('.delete-block-info');

        $removing_block_info.text($removing_block_info.text().replace('{number}', reports_number));
    };


    /**
     * Delete label with assigning it to another
     *
     * @param {string} removing_key - Removing method
     * @param {string} to_point     - Assign to the point
     */
    this.deleteWithAssign = function (removing_key, to_point) {

        var data = {
            item: 'RBLDeleteCompletely',
            key: removing_key
        };

        if (to_point) {
            data.point = to_point;
        }

        self.reportBrokenListings.sendAjax(data, function (response) {
            if (response.status == 'OK') {
                $('#assigning-to').find('option[value="' + removing_key + '"]').remove();
                self.grid.reload();
                show('delete_block');

                if (response.body.removed_listings && response.body.removed_listings.length > 0) {
                    response.body.removed_listings.forEach(function (element, index) {
                        self.removeIDFromCookie(element);
                    });
                }

                printMessage('notice', lang['item_deleted']);
            }
        });
    };

};

/**
 * Admin part object of the "Report broken listings" plugin
 * @constructor
 */
var ReportBrokenListings = function () {
    /**
     * @type {ReportBrokenListings}
     */
    var self = this;


    /**
     * @type {gridObj}
     */
    this.grid = false;

    /**
     * Sending ajax request
     *
     * @param data {object} - Sending data
     * @param callback {string} - Callback method, which will be calleds
     */
    this.sendAjax = function (data, callback) {
        $.post(rlConfig["ajax_url"], data,
            function (response) {
                callback(response);
            }, 'json');
    };

    /**
     * Active grid setter
     *
     * @param   {gridObj} obj - Grid Object
     * @returns {boolean}
     */
    this.setActiveGrid = function (obj) {
        if (typeof obj === 'object') {
            self.grid = obj;
            return true;
        }

        return false;
    };

    /**
     * Remove listing
     *
     * @param {integer} listingID
     */
    this.removeListing = function (listingID) {
        if (!listingID) {
            return false;
        }

        var data = {
            item: 'RBLRemoveListing',
            id: listingID
        };

        self.sendAjax(data, function (response) {
            if (response.status == 'OK' && response.body.redirectTo) {
                window.location.href = response.body.redirectTo;
            }
        });
    };

    /**
     * Active grid getter
     * @returns {gridObj}
     */
    this.getActiveGrid = function () {
        return self.grid;
    };

    this.removeAllListingReports = function (listing_id) {
        if (!listing_id) {
            return false;
        }

        var data = {
            'item': 'RBLRemoveAllReportsOfTheListing',
            id: listing_id
        };
        
        self.sendAjax(data, function (response) {
            if (response.status == 'OK') {
                self.getActiveGrid().reload();
            }
        });
    };
};

var Reports = function () {
    var self = this;
    /**
     * @type {gridObj}
     */
    this.grid = false;
    /**
     * @type {ReportBrokenListings}
     */
    this.reportBrokenListings = false;

    this.init = function () {
        self.reportBrokenListings = new ReportBrokenListings();

        self.enableClickHandlers();
    };

    this.enableClickHandlers = function () {
        $('#filter-points').click(function () {
            show('filter_reports', '#action_blocks div');
        });

        $('#filter_reports').find('a.cancel').click(function () {
            show('filter_reports', '#action_blocks div');
        });

        $('#filter_button').click(function(){
            self.filter();
        });
        $('#reset_filter_button').click(function(){
            self.resetFilter();
        });
    };

    this.filter = function () {
        var filters = new Array();
        var filterFields = ['point', 'criticality', 'date_from', 'date_to'];

        filterFields.forEach(function (element, index) {
            filters[index] = [element, $('#' + element).val()];
        });
        filters.push(['filter', 1]);

        self.grid.filters = filters;
        self.grid.reload();
    };

    this.resetFilter = function () {
        var filterFields = ['point', 'criticality', 'date_from', 'date_to'];

        filterFields.forEach(function (element, index) {
            var $current = $('#' + element);

            if ($current.is('input')) {
                $current.val('');
            }
        });

        self.grid.filters = [];
        self.grid.reload();
    };

    this.setGrid = function (grid) {
        self.grid = grid;
    };

    this.getGrid = function () {
        return self.grid;
    };

    this.changeListingStatus = function (listing_id, new_status) {
        var data = {
            item: 'changeListingId',
            listing_id: listing_id,
            status: new_status
        };

        self.reportBrokenListings.sendAjax(data, function (response) {

        });
    };

    this.deleteReport = function (report_id, reason) {
        var data = {
            item: 'RBLDeleteReport',
            id: report_id,
            reason: reason
        };

        self.reportBrokenListings.sendAjax(data, function(response){
            if (response.status == 'OK') {
                self.grid.reload();
            } else {
                printMessage('error', response.message);
            }
        });
    };
};

var reportPoints = new ReportPoint();
reportPoints.init();
