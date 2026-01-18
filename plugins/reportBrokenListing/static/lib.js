
/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : LIB.JS
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

var ReportBrokenListings = function () {
    /**
     * @type {ReportBrokenListings}
     */
    var self = this;

    /**
     * Listing ID
     * @type {number}
     */
    this.listing_id = 0;

    /**
     * Initializing method
     * @returns {boolean}
     */
    this.init = function () {
        if (!self.listing_id) {
            return false;
        }

        if (self.isReportExist(self.listing_id) !== false) {
            $('#remove-report').removeClass('hide');
        } else {
            $('#report-broken-listing').removeClass('hide');
        }

        /* cancel report modal */
        $('#remove-report').unbind('click').flModal({
            source: '#remove_report_form',
            width: 'auto',
            height: 'auto',
            ready: function () {
                $('#remove-report-button').click(function () {
                    var index = self.isReportExist(self.listing_id);

                    if (false !== index) {
                        var ids = self.readBrokenListingsCookies();
                        self.removeReport(ids[index].report_id);
                    }
                });
            }
        });

        /* add report modal */
        $('#report-broken-listing').flModal({
            source: '#reportBrokenListing_form',
            width: 500,
            height: 'auto',
            scroll: false,
            ready: function () {
                var listing_id = $('#report-broken-listing').data('lid');
                var data = {
                    mode: 'RBLGetAllPoints',
                    lang: rlLang
                };

                self.sendAjax(data, function(response){

                    if (response.status == 'OK') {
                        var is_single_mode = true;
                        $('.rbl_loading').hide();

                        if (response.body && response.body.length > 0) {
                            is_single_mode = false;

                            response.body.forEach(function (item, index) {
                                var is_checked = !index;
                                $('#points').append(self.renderPoints(item, is_checked));
                            });
                            flynaxTpl.customInput();

                            var data = {
                                Value: lang['rbl_other'],
                                Key: 'custom'
                            };

                            $('#points').append(self.renderPoints(data, false));
                        }

                        var phrase = is_single_mode
                            ? lang['rbl_provide_a_reason']
                            : lang['reportbroken_add_comment'].replace(/&quot;/g, '"');

                        $('.modal_content').find('div.caption').text(phrase).removeClass('hide');
                        self.addOtherTextArea(is_single_mode);
                        $('.report-nav').removeClass('hide');

                        self.enableModalWindowHandlers();

                        // Fix popup position
                        if (media_query != 'mobile') {
                            var box_margin = ($(window).height() - $('#modal_block').height()) / 2 + $(window).scrollTop();
                            $('#modal_block').css({marginTop: box_margin});
                        }
                    }
                });

            }
        });
    };

    /**
     * Enable all events after modal windows loads
     */
    this.enableModalWindowHandlers = function () {
        $('#points').mCustomScrollbar();

        $('#custom-message').textareaCount({
            'maxCharacterSize': rlConfig['reportBroken_message_length'],
            'warningNumber': 20
        });

        $('.rbl-point').change(function () {
            $('#user-point')[
                $(this).val() === 'custom'
                    ? 'show'
                    : 'hide'
            ]()
        });

        $('#add-report').click(function () {
            self.addReport($('.rbl-point:checked').val(), self.listing_id);
        });
    };

    /**
     * Render Report Listing point
     *
     * @param   {object}  data        - Point info
     * @param   {boolean} is_checked  - Is point already checked
     * @returns {jQuery}              - jQuery object to implode to the DOM
     */
    this.renderPoints = function(data, is_checked) {
        var $label = $('<label/>', {
            title: data.Value,
            text: data.Value
        }).append($('<input/>', {
            type: 'radio',
            class: 'rbl-point',
            name: 'point',
            checked: is_checked,
            value: data.Key
        }));

        var $submitCell = $('<div/>', {
            class: 'rbl-item'
        }).append($('<span/>', {
            class: 'custom-input'
        }).append($label));

        return $submitCell;
    };

    /**
     * Render texarea of the "Other" point
     * @param {boolean} single - Is textarea is showing for non points view
     */
    this.addOtherTextArea = function (single) {
        var point_class = !single ? 'hide mt-3' : '';
        var $reportModalNav = $('.report-nav');

        var $customTextArea = $('<div/>', {
            id: 'user-point',
            class: point_class
        }).append(
            $('<textarea/>', {
                id: 'custom-message',
                name: 'custom-message',
                rows: 5,
                placeholder: lang['message']
            })
        );

        /* add hidden point in case if additional points are missing */
        if (single) {
            var data = {
                Value: lang['rbl_other'],
                Key: 'custom'
            };

            $reportModalNav.prepend(self.renderPoints(data, true).hide());
        }

        $reportModalNav.prepend($customTextArea);
        flynaxTpl.customInput();
    };

    /**
     * Read cookie of the Report Broken listing plugin
     *
     * @returns {Array} - cookies in array type
     */
    this.readBrokenListingsCookies = function () {
        if (readCookie('reportBrokenListings')) {
            var cookies = [];
            try {
                cookies = JSON.parse(readCookie('reportBrokenListings'));
            } catch (e) {
                eraseCookie('reportBrokenListings');
            }
            return cookies;
        }
        return [];
    };

    /**
     * Adding report
     *
     * @param {string} selectedPoint - Selected point key
     * @param {number} listing_id    - ID of the reported listing
     */
    this.addReport = function (selectedPoint, listing_id) {

        if(!self.isValidForm(selectedPoint)) {
            return false;
        }

        var data = {
            mode: 'RBLAddReport',
            key: selectedPoint,
            listing_id: listing_id,
            lang: rlLang
        };

        if(selectedPoint === 'custom') {
            data.custom_message = $('#custom-message').val();
        }

        self.sendAjax(data, function (response) {
            if (response.status === 'OK') {
                var report_id = response.body ? response.body : 0;
                self.addIDToCookie(listing_id, report_id);
                $("#report-broken-listing").hide();
                $('#remove-report').show();

                printMessage('notice', response.message);
            } else {
                printMessage('error', response.message);
            }

            $('.modal_block').find('.close').trigger('click');
        });
    };

    this.isValidForm = function (selectedPoint) {
        var errors = [];

        if (selectedPoint === 'custom' && !$('#custom-message').val()) {
            errors.push({
                message: lang['rbl_provide_a_reason'],
                field: 'custom-message'
            });
        }

        /* display an errors */
        if(errors.length > 0) {
            self.renderErrors(errors);
            return false;
        }

        return true;
    };

    this.renderErrors = function (errors) {
        if (errors.length === 1) {
            printMessage('error', errors[0].message);
            return true;
        }

        var error_message = '<ul>';
        errors.forEach(function (item) {
            error_message += '<li>' + item.message + '</li>';
        });
        error_message += '</ul>';

        printMessage('error', error_message);
        return true;
    };

    /**
     * Remove report from DB
     *
     * @param {number} report_id - ID of the removing report
     */
    this.removeReport = function (report_id) {
        var data = {
            mode: 'RBLRemoveReport',
            id: report_id,
            lang: rlLang
        };

        self.sendAjax(data, function (response) {
            if(response.status === 'OK') {
                self.removeIDFromCookie(self.listing_id);

                $('#report-broken-listing').show();
                $('#remove-report').hide();
                $('.close').trigger('click');
                printMessage('notice', response.message);
            }
        });
    };

    /**
     * Adding listing ID to the cookie array
     *
     * @param   {integer} listing_id
     * @returns {boolean} Does adding listing process has been succesfully complited
     */
    this.addIDToCookie = function (listing_id, report_id) {
        if (!listing_id || !report_id) {
            return false;
        }

        var ids = self.readBrokenListingsCookies();

        if (!self.isReportExist(listing_id)) {
            var data = {
                'listing_id': listing_id,
                'report_id': report_id
            };

            ids.push(data);
            createCookie('reportBrokenListings', JSON.stringify(ids));
        }
    };

    /**
     * Is reports is exist for the specified listing
     *
     * @param  {number}            listing_id  - ID of the checking listing
     * @returns {boolean}|{number}             - False if nothing found | Index of the element in the Cookie data array
     */
    this.isReportExist = function(listing_id) {
        var ids = self.readBrokenListingsCookies();
        var index = false;

        if (0 === ids.length) {
            return false;
        }

        for (var i = 0; i < ids.length; i++) {
            if (ids[i].listing_id === listing_id) {
                index = i;
            }
        }

        return index;
    };

    /**
     * Removing listing id from Cookie array
     *
     * @param   {number} listing_id
     * @returns {boolean} does removing process has been succesfully complited
     */
    this.removeIDFromCookie = function (listing_id) {
        var ids = self.readBrokenListingsCookies();
        var index = self.isReportExist(listing_id);

        if (index !== false) {
            ids.splice(index, 1);

            if (ids.length > 0) {
                createCookie('reportBrokenListings', JSON.stringify(ids));
                return true;
            }

            eraseCookie('reportBrokenListings');
            return true;
        }

        return false;
    };

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
};
