
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: LIB.JS
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
 * @since 2.0.0
 * @constructor
 */
WordPressBridgeClass = function () {
    /**
     * Check connection with WordPress site.
     * If connection is successful, print a success message.
     * If connection fails, open a modal window with a form to enter WP login credentials.
     * On success, the window is closed and a message is printed.
     * On failure, the window stays open and an error message is printed.
     * @since 2.1.2
     * @function
     */
    this.wpConnectChecking = function name() {
        let $wpUrlInput = $('input[name="post_config[wp_path][value]"][type="text"]');
        let wpUrl = $wpUrlInput.val();

        if (!wpUrl) {
            return false;
        }

        // Add button to check connection with wordpress site
        $wpUrlInput.after(
            $('<input>', {
                type: 'button',
                class: 'button',
                value: lang.wpb_check_connection,
                style: 'padding: 5px 10px 5px; margin-left: 10px; margin-top: unset;',
                click: function () {
                    let $button = $(this);
                    $button.attr('disabled', 'disabled').addClass('disabled').val(lang.loading);

                    flUtil.ajax({mode: 'wp_check_connection', item: 'wp_check_connection'}, function(response) {
                        $button.removeClass('disabled').removeAttr('disabled').val(lang.wpb_check_connection);

                        if (response && response.status) {
                            if (response.status === 'OK' && response.message) {
                                printMessage('notice', response.message);
                            } else {
                                wpAuthWindow();
                            }
                        } else {
                            printMessage('error', lang.system_error);
                        }
                    });
                }
            })
        );

        /**
         * Open a modal window with a form to enter WP login credentials.
         * On success, the window is closed and a message is printed.
         * On failure, the window stays open and an error message is printed.
         * @since 2.1.2
         * @function wpAuthWindow
         */
        let wpAuthWindow = function () {
            $('body').flModal({
                click  : false,
                width  : 400,
                height : 'auto',
                caption: lang.wpb_check_connection,
                content: '<div id="modal_content">' + lang.loading + '</div>',
                onReady: function() {
                    var $closeButton   = $('div.modal-window div span:last');
                    let $loginButton   = $('<input>', {type: 'button', name: 'wpb_login', value: lang.ext_login});
                    let $usernameInput = $('<input>', {type: 'text', name: 'wpb_username', value: '', autocomplete: 'off', placeholder: lang.ext_username});
                    let $passwordInput = $('<input>', {type: 'password', name: 'wpb_password', value: '', autocomplete: 'off', placeholder: lang.password});
                    let $modalContent  = $('<div>', {id: 'wpb_auth'}).append(
                        $('<form action="javascript://" name="wpb_auth_form">').append(
                            $('<p>', {html: lang.wpb_check_connection_text}),
                            $('<div>', {css: {marginTop: '15px'}}).append($usernameInput),
                            $('<div>', {css: {marginTop: '5px', marginBottom: '15px'}}).append($passwordInput)
                        ),
                        $('<p>').append(
                            $loginButton,
                            $('<a>', {href: 'javascript://', class: 'cancel', html: lang.cancel})
                        )
                    );

                    $('#modal_content').html($modalContent);

                    $loginButton.click(function () {
                        if (!$usernameInput.val() || !$passwordInput.val()) {
                            if (!$usernameInput.val()) {
                                $usernameInput.addClass('error');
                            }
                            if (!$passwordInput.val()) {
                                $passwordInput.addClass('error');
                            }
                            return false;
                        }

                        $usernameInput.removeClass('error');
                        $passwordInput.removeClass('error');

                        let $button = $(this);
                        $button.attr('disabled', 'disabled').addClass('disabled').val(lang.loading);
                        $('#modal_content a.cancel').hide();
                        $usernameInput.attr('disabled', 'disabled').addClass('disabled');
                        $passwordInput.attr('disabled', 'disabled').addClass('disabled');

                        flUtil.ajax(
                            {mode: 'wp_login', item: 'wp_login', username: $usernameInput.val(), password: $passwordInput.val()},
                            function(response) {
                                $closeButton.click();

                                if (response && response.status && response.message) {
                                    let messageType = response.status === 'OK' ? 'notice' : 'error';
                                    printMessage(messageType, response.message);
                                } else {
                                    printMessage('error', lang.system_error);
                                }
                            }
                        );
                    });

                    $('#modal_content a.cancel').click(function () {
                        $closeButton.click();
                    });
                }
            });
        }
    }

    /**
     * @deprecated 2.1.2
     */
    this.sendAjax = function(data, callback) {};
};

/**
 * @since 2.0.0
 *
 * @constructor
 *
 * @returns {{refreshCache: refreshCache}}
 */
var WordPressBridgeCacheClass = function() {
    var self = this;

    this._refreshCache = function(callback) {
        flUtil.ajax({mode: 'wpb_update_cache', item: 'wpb_update_cache'}, function(response) {
            callback(response);
        });
    };
    return {
        refreshCache: function(callback) {
            self._refreshCache(callback);
        }
    };
};


