/**copyright**/

/**
 * @since 2.1.0
 *
 * @constructor
 */
var CarSpecsClass = function () {
    /**
     * @type {CarSpecsClass}
     */

    var self = this;
    /**
     * 'Create your Add' button
     * @type {d.fn.init|jQuery|HTMLElement}
     */
    this.$createYourAdButton = $('#reg_next');

    /**
     * Send AJAX request to the 'request.ajax.php' file
     *
     * @param {object}   data     - Sending data
     * @param {function} callback - Callback function
     *
     */
    this.sendAjax = function(data, callback) {
        if (typeof flUtilClass == 'function') {
            flUtil.ajax(data, function(response, status) {
                callback(response, status);
            });
            return;
        }

        self._sendAjax(data, callback);
    };

    /**
     * Send AJAX request to the 'request.ajax.php' file in case if flUtil.ajax function didn't found
     *
     * @param {object}   data     - Sending data
     * @param {function} callback - Callback function
     */
    this._sendAjax = function (data, callback) {
        $.post(rlConfig['ajax_url'], data,
            function (response) {
                callback(response);
            }, 'json');
    };

    /**
     * Enable all click handlers of the class
     */
    this.enableClickHandlers = function () {
        self.$createYourAdButton.click(function () {
            if ($(this).is(':disabled')) {
                return false;
            }

            var previousText = $(this).text();
            var reg_number = $('input[name=reg-number]').val();
            var odometr = $('input[name=odometr]').val();

            $(this).attr('disabled', 'disabled').addClass('disabled').text(lang['loading']);

            var data = {
                mode: 'cs_checkRegNumber',
                reg_number: reg_number,
                odometr: odometr,
            };

            self.sendAjax(data, function (response, status) {
                self.$createYourAdButton.removeAttr('disabled').removeClass('disabled').text(previousText);

                if (response.status === 'OK' && response.Category.url) {
                    window.location = response.Category.url;
                }

                var errorMessage = response.message ? response.message : lang['cs_something_went_wrong'];
                printMessage('error', errorMessage);
            });
        });
    };
};

/**
 * Car Specs utils class (working as static class)
 *
 * @since 2.1.0
 *
 * @constructor
 */
var CarSpecsUtilClass = function () {
    var self = this;

    this.sendAjax = function (data, callback) {
        var carSpecs = new CarSpecsClass();
        carSpecs.sendAjax(data, callback);
    };

    return {
        sendAajx: function(data, callback) {
            self.sendAjax(data, callback);
        }
    }
};

$('#next_step').click(function () {
    $('#cs-container').hide();
});
