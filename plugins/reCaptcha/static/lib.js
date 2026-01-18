
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
 *	Copyrights Flynax Classifieds Software | 2023
 *	https://www.flynax.com
 *
 ******************************************************************************/

/**
 * ReCaptcha Library Constructor
 * @since 3.0.0
 * @constructor
 */
const ReCaptchaClass = function () {
    let self = this;

    /**
     * List of configs
     * @type {{apiUrl: string}}
     */
    this.config = {apiUrl : 'https://www.google.com/recaptcha/api.js'};

    /**
     * List of inputs with reCaptcha responses
     * @type {null}
     */
    this.$reCaptchaInputs = null;

    /**
     * List of all reCaptcha IDs on page
     * @type {*[]}
     */
    this.reCaptchaIDs = [];

    /**
     * Library initialization
     * @param config
     */
    this.init = function (config) {
        self.$reCaptchaInputs = $('.gptwdg');

        if (!config || self.$reCaptchaInputs.length <= 0) {
            return;
        }

        $.extend(self.config, config);

        if (self.config.type === 'v2_checkbox' || self.config.type === 'v2_invisible') {
            self.config.version = 2;
        } else if (self.config.type === 'v3') {
            self.config.version = 3;
        }

        window.onloadCallback = function () {
            // Prevent resetting invisible reCaptcha by template/plugin
            if (self.reCaptchaIDs.length && self.config.type !== 'v2_checkbox') {
                return;
            }

            self.$reCaptchaInputs.each(function () {
                self.reCaptchaWidgetHandler($(this).attr('data-recaptcha-index'));
            })
        }

        if (self.config.type === 'v2_checkbox' || self.config.type === 'v2_invisible') {
            flUtil.loadScript(`${self.config.apiUrl}?onload=onloadCallback&render=explicit&hl=${rlLang}`);
        } else if (self.config.type === 'v3') {
            flUtil.loadScript(
                `${self.config.apiUrl}?render=${self.config.key}&badge=${self.config.badge}&hl=${rlLang}`,
                function () {
                    onloadCallback();
                }
            );
        }
    };

    /**
     * Get reCaptcha options
     * @returns {{sitekey: *, callback: any, theme: *}}
     */
    this.getOptions = function (index) {
        let options = {
            'sitekey' : self.config.key,
            'callback': eval('afterCaptcha' + index),
        };

        if (self.config.theme === 'auto') {
            if (readCookie('colorTheme')) {
                options.theme = readCookie('colorTheme');
            } else {
                options.theme = window.matchMedia
                    ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                    : 'light';
            }
        }

        if (self.config.type === 'v2_checkbox') {
            options.tabindex = index;
            options.size     = self.config.size;
        } else if (self.config.type === 'v2_invisible') {
            options.size  = 'invisible';
            options.badge = self.config.badge;
        }

        return options;
    }

    /**
     * Handler which will activate reCaptcha on page and run events
     *
     * @since 3.0.0
     *
     * @param index - Index of reCaptcha widget on page
     */
    this.reCaptchaWidgetHandler = function (index) {
        let $widget = $('#gcaptcha_widget' + index), reCaptchaID;

        if (!$widget.length || typeof grecaptcha !== 'object') {
            return;
        }

        if (!$widget.html() && (self.config.type === 'v2_checkbox' || self.config.type === 'v2_invisible')) {
            reCaptchaID = grecaptcha.render('gcaptcha_widget' + index, self.getOptions(index));
            self.reCaptchaIDs[reCaptchaID] = {'widgetIndex': index, 'reCaptchaID': reCaptchaID};
        } else {
            reCaptchaID = self.getReCaptchaIDByIndex(index);
        }

        if (self.config.type === 'v2_checkbox') {
            return;
        }

        let $reCaptchaContainer = $widget.closest('.submit-cell');
        let $fieldWithResponse  = $widget.next('[name^="security_code"]');

        let captchaID = '';
        if ($fieldWithResponse.attr('id').indexOf('_security_code') > 0) {
            captchaID = $fieldWithResponse.attr('id').replace('_security_code', '');
        }

        // Hide empty captcha field from page
        if (typeof self.config.badge != 'undefined' && self.config.badge !== 'inline') {
            $reCaptchaContainer.find('.name').addClass('hide d-none');
            $reCaptchaContainer.find('.field').css('min-height', 0);
            $reCaptchaContainer.css({height: 0, padding: 0, 'min-height': 0, margin: 0});
        }

        let $parentContainer = $reCaptchaContainer.parent(), $submitButton = {};

        if (rlPageInfo.controller === 'add_listing') {
            // Posting listings in one step mode
            if ($parentContainer.closest('.listing-form').length) {
                $submitButton = $parentContainer.closest('.listing-form').find('.form-buttons input[type="submit"]');
            } else {
                $submitButton = $parentContainer.find('.form-buttons input[type="submit"]');
            }
        } else {
            $submitButton = $parentContainer.find('input[type="button"],input[type="submit"]');
        }

        // If button missing in form then try to find it in additional parent containers
        if ($submitButton.length <= 0) {
            $parentContainer = $parentContainer.parent().parent();
            $submitButton    = $parentContainer.find('input[type="button"],input[type="submit"]');
        }

        if (!$submitButton.length) {
            console.log('ReCaptcha error: submit button not found in form.');
            return;
        }

        // Remove old fake button after recaptcha resetting
        if ($submitButton.next('.reCaptcha-submit').length > 0) {
            $submitButton.next('.reCaptcha-submit').remove();
            $submitButton.removeClass('d-none');
        }

        /**
         * Add fake "submit" button to form and hide origin button:
         *  - when user will click to button, the reCaptcha will send request to api
         *  - when response will be returns, it will be added to form
         *  - and after origin submit button will be clicked
         */
        let $fakeSubmitButton = $('<a>', {href: 'javascript://', class: $submitButton.attr('class')})
            .html($submitButton.val())
            .addClass('button text-center reCaptcha-submit');

        if (rlPageInfo.controller === 'add_listing') {
            $fakeSubmitButton.addClass('d-inline-flex');
        }

        $submitButton.after($fakeSubmitButton).addClass('d-none');

        $fakeSubmitButton.click(function () {
            if (self.config.version === 2) {
                grecaptcha.execute(reCaptchaID).then(function () {
                    let responseWaitInterval = setInterval(function () {
                        if ($fieldWithResponse.val()) {
                            clearInterval(responseWaitInterval);
                            self.doOriginSubmit($submitButton, $fakeSubmitButton);
                        }
                    }, 10);
                });
            } else if (self.config.version === 3) {
                grecaptcha.ready(function () {
                    grecaptcha.execute(self.config.key, {action: 'submit'}).then(function (token) {
                        $fieldWithResponse.val(token + 'flgcaptcha' + captchaID);
                        self.doOriginSubmit($submitButton, $fakeSubmitButton);
                    });
                });
            }
        });
    };

    /**
     * Remove fake submit button and emulate click in origin submit button
     * @param $submitButton
     * @param $fakeSubmitButton
     */
    this.doOriginSubmit = function ($submitButton, $fakeSubmitButton) {
        $fakeSubmitButton.remove();
        $submitButton.removeClass('d-none').click();
    }

    /**
     * Reset all available ReCaptcha widgets
     */
    this.resetAllWidgets = function () {
        // Add timeout for prevent problems with updating buttons names in xAjax response (like in "Tell a friend")
        setTimeout(function () {
            for (let reCaptchaIDKey in self.reCaptchaIDs) {
                self.resetWidgetByID(self.reCaptchaIDs[reCaptchaIDKey].reCaptchaID);
            }
        }, 100);
    }

    /**
     * Reset necessary ReCaptcha Widget by ID (reset available only for "V2 Checkbox" type)
     * @param ID
     */
    this.resetWidgetByID = function (ID) {
        if (self.config.type !== 'v2_checkbox') {
            return;
        }

        grecaptcha.reset(ID);

        let $response = ID > 0 ? $('textarea#g-recaptcha-response-' + ID) : $('textarea#g-recaptcha-response');

        if ($response.length) {
            $response.closest('.gptwdg').next('input[type=hidden]').val('');
        }
    }

    /**
     * Reset necessary ReCaptcha Widget by index of widget
     */
    this.resetWidgetByIndex = function (index) {
        self.resetWidgetByID(self.getReCaptchaIDByIndex(index));
    }

    /**
     * Get ID of reCaptcha by index of widget
     * @param index
     * @returns null|int
     */
    this.getReCaptchaIDByIndex = function (index) {
        let reCaptchaID;

        for (let reCaptchaIDKey in self.reCaptchaIDs) {
            if (index === self.reCaptchaIDs[reCaptchaIDKey].widgetIndex) {
                reCaptchaID = self.reCaptchaIDs[reCaptchaIDKey].reCaptchaID;
                break;
            }
        }

        return reCaptchaID;
    }
}

const ReCaptcha = new ReCaptchaClass();
