
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

$(function() {
    if (!UpUp || typeof pwaConfig === 'undefined') {
        return;
    }

    // UpUp.debug();
    UpUp.start({
        'content-url'       : './offline/index.html',
        'service-worker-url': pwaConfig.rlUrlHome + 'upup.sw.min.js',
        'assets'            : pwaConfig.assets,
    });

    /**
     * Handle push notifications
     */
    if (PWA().isPushSupportedInBrowser() && rlAccountInfo && rlAccountInfo.ID) {
        var notifications = new PWAPushNotificationClass();
        notifications.init();
    }

    PWA().addPwaButtonInFooter();
});

/**
 * Device detecting class
 */
var DeviceDetectorClass = function() {
    var self = this;

    /**
     * Current user agent of the device
     * @type {string}
     */
    this.userAgent = window.navigator.userAgent.toLowerCase();

    return {
        /**
         * @returns {boolean}
         */
        isOs: function() {
            return /iphone|ipad|ipod/.test(self.userAgent);
        },
        /**
         * @returns {boolean}
         */
        isInStandaloneMode: function() {
            return 'standalone' in window.navigator &&
                window.navigator.standalone;
        },
        isIpad: function() {
            return /ipad/.test(self.userAgent);
        },
        /**
         * @returns {string | boolean}
         */
        isSafari: function() {
            return navigator.vendor && navigator.vendor.indexOf('Apple') > -1 &&
                navigator.userAgent &&
                navigator.userAgent.indexOf('CriOS') == -1 &&
                navigator.userAgent.indexOf('FxiOS') == -1;
        }
    };
};

/**
 * PWA plugin main js class
 * @constructor
 */
var PWA = function() {
    var self = this;

    /**
     * Send ajax and handle response
     *
     * @param {object} data     - Sending data with 'mode' object key in it
     * @param {object} callback - Handle response of the ajax by callback
     */
    this.sendAjax = function(data, callback) {
        if (typeof flUtilClass == 'function') {
            flUtil.ajax(data, function(response, status) {
                if (typeof callback == 'function') {
                    callback(response, status);
                }
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
    this._sendAjax = function(data, callback) {
        $.post(rlConfig['ajax_url'], data,
            function(response) {
                if (typeof callback == 'function') {
                    callback(response);
                }
            }, 'json');
    };

    return {
        /**
         * @inheritDoc
         */
        sendAjax: function(data, callback) {
            self.sendAjax(data, callback);
        },
        showIOsBanner: function(force) {
            var isOSBanner = new iOSBannerClass();
            isOSBanner.showBanner(DeviceDetectorClass().isIpad(), force);
        },
        urlBase64ToUint8Array: function(base64String) {
            var padding = '='.repeat((4 - base64String.length % 4) % 4);
            var base64 = (base64String + padding).replace(/\-/g, '+').
                replace(/_/g, '/');

            var rawData = window.atob(base64);
            var outputArray = new Uint8Array(rawData.length);

            for (var i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        },
        /**
         * Detects a full support of the push notifications in browser
         * @since 1.1.1
         * @return {boolean}
         */
        isPushSupportedInBrowser: function() {
            return 'Notification' in window && 'serviceWorker' in navigator && 'PushManager' in window;
        },
        /**
         * Add PWA install button in footer
         * @since 1.3.0
         * @return {void}
         */
        addPwaButtonInFooter: function() {
            // Add PWA install button in footer
            let $appIconsContainer = $('footer .mobile-apps');
            let $pwaInstallButton  = $('<a>', {class: 'd-inline-block pt-3 pt-lg-3 ml-lg-0 ml-sm-3 pwa-install-button', href: 'javascript:void(0);'}).append(
                $('<img>', {src: rlConfig.plugins_url + 'PWA/static/pwa.svg', alt: 'PWA icon', style: 'width: 135px; height: 40px;'}),
            );

            if (!$appIconsContainer.length ) {
                let $navMenu = $('footer nav.footer-menu');
                if ($navMenu.find('> div > ul').length) {
                    $appIconsContainer = $('<div>', {class: 'mobile-apps col-lg-3'}).append(
                        $('<h4>', {class: 'footer-menu-title'}).html(lang.footer_menu_mobile_apps)
                    );
                    $navMenu.find('> div').append($appIconsContainer);

                    // Update structure of columns in footer menu
                    $navMenu.find('ul.col-md-4').removeClass('col-md-4').addClass('col-md-3');
                } else {
                    return;
                }
            }

            $appIconsContainer.append($pwaInstallButton);

            // Remove unnecessary margin if PWA install button is the only one
            if ($appIconsContainer.find('a').length === 1) {
                $pwaInstallButton.removeClass('pt-3 pt-lg-3 ml-sm-3');
            }

            let deferredPrompt;
            window.addEventListener('beforeinstallprompt', (e) => {
                // Prevent the mini-infobar from appearing on mobile
                e.preventDefault();
                // Stash the event so it can be triggered later.
                deferredPrompt = e;
            });

            $pwaInstallButton.click(function (e) {
                e.preventDefault();

                if (DeviceDetectorClass().isOs() && !DeviceDetectorClass().isInStandaloneMode()) {
                    PWA().showIOsBanner(true);
                    return;
                }

                // Browser does not provide ability to control PWA prompt
                if (!deferredPrompt) {
                    $('body').popup({
                        click  : false,
                        caption: 'PWA',
                        content: lang.pwa_installed,
                        width  : 350,
                    });

                    return;
                }

                // Show the install prompt
                deferredPrompt.prompt();

                // Wait for the user to respond to the prompt
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                    } else {
                        console.log('User dismissed the install prompt');
                    }
                });
            });
        }
    };
};

/**
 * Showing/Hiding banner for iOS devices
 */
var iOSBannerClass = function() {
    var self = this;

    /**
     * UI element of the banner class responsibility
     */
    this.ui = {
        $banner: $('#pwa-ios-banner'),
        $closeBtn: $('.pwa-banner-close'),
        $arrow: $('.pwa-banner-arrow')
    };

    /**
     * Constructor
     */
    this.init = function() {
        self.enableEvents();
    };

    /**
     * Enable all events of the banner class instance
     */
    this.enableEvents = function() {
        self.ui.$closeBtn.click(function() {
            self.hideBanner();
            self.disableEvents();
        });
    };

    /**
     * Disable all events of the banner class instance
     */
    this.disableEvents = function() {
        self.ui.$closeBtn.off('click');
    };

    /**
     * Show iOS banner
     */
    this.showBanner = function(forIPad, force) {
        if (readCookie('pwaiOSBannerDisabled') && !force) {
            return;
        }

        self.ui.$arrow.attr('data-placement', forIPad ? 'top' : 'bottom');
        self.ui.$banner.removeClass('hide');
        self.enableEvents();

        // Show banner in center for non-Safari browsers on iOS
        if (!DeviceDetectorClass().isSafari()) {
            self.ui.$arrow.remove();
            self.ui.$banner.css({'top': '45%', 'bottom': 'initial'});
        }
    };

    /**
     * Hide iOS banner
     */
    this.hideBanner = function() {
        self.ui.$banner.addClass('hide');
        createCookie('pwaiOSBannerDisabled', true, 93);
    };
};

var PWAPushNotificationClass = function() {
    var self = this;
    this.ui  = {
        $checkbox: $('<label>', {class: 'pt-2 w-100'}).append(
            $('<input>', {type: 'checkbox', name: 'push_notifications', value: '1'}),
            '&nbsp;',
            lang.pwa_receive_notifications
        ),
        $checkboxAlerts: $('<label>', {class: 'pt-2 w-100 d-none'}).append(
            $('<input>', {type: 'checkbox', name: 'push[alerts]', value: '1'}),
            '&nbsp;',
            lang.pwa_push_alerts
        ),
        $checkboxMessages: $('<label>', {class: 'pt-2 w-100 d-none'}).append(
            $('<input>', {type: 'checkbox', name: 'push[messages]', value: '1'}),
            '&nbsp;',
            lang.pwa_push_messages
        ),
        $pushSection: $('<div>').addClass('submit-cell push-notifications'),
        $pushPermissionsField: $('<div>').addClass('field'),
        $noticeField: $('<div>').addClass('pt-2 notice_message'),
    };

    this.init = function() {
        rlAccountInfo.isPwaRequested   = false;
        rlAccountInfo.PWA_Subscription = null;
        rlAccountInfo.pwaSubscriptions = [];

        self.showSubscriptionButton();

        if (!readCookie('pushNotificationsPopup')) {
            flUtil.loadStyle(rlConfig.tpl_base + 'components/popup/popup.css');
            flUtil.loadScript(rlConfig.tpl_base + 'components/popup/_popup.js', function() {
                $('body').popup({
                    click  : false,
                    caption: lang.pwa_push_subscription,
                    content: lang.pwa_popup_content,
                    width  : 350,
                });
            });

            createCookie('pushNotificationsPopup', true, 93);
        }
    };

    this.showSubscriptionButton = function() {
        if (!rlPageInfo || rlPageInfo.key !== 'my_profile') {
            return;
        }

        self.ui.$pushSection.append(
            $('<div>').addClass('name').text(lang.pwa_push_subscription),
            self.ui.$pushPermissionsField
        );

        $('#profile_submit').closest('div.submit-cell').before(self.ui.$pushSection);

        self.ui.$pushPermissionsField.append(
            self.ui.$checkbox,
            self.ui.$checkboxAlerts,
            self.ui.$checkboxMessages,
            self.ui.$noticeField
        );

        if (typeof flynaxTpl === 'object' && typeof flynaxTpl.customInput === 'function') {
            flynaxTpl.customInput();

            // Reassign containers to real checkboxes after flynaxTpl.customInput()
            self.ui.$checkbox         = self.ui.$checkbox.prev('input');
            self.ui.$checkboxAlerts   = self.ui.$checkboxAlerts.prev('input');
            self.ui.$checkboxMessages = self.ui.$checkboxMessages.prev('input');
        }

        self.enableEvents();

        if (Notification && Notification.permission) {
            if (Notification.permission === 'granted') {
                PWA().sendAjax({mode: 'pwa_get_subscriptions'}, function (response) {
                    if (response && response.status && response.status === 'OK') {
                        if (response.subscriptions) {
                            response.subscriptions.forEach(function (subscription) {
                                rlAccountInfo.pwaSubscriptions.push({
                                    endpoint: subscription.Endpoint,
                                    p256dh  : subscription.P256dh,
                                    auth    : subscription.Auth,
                                    status  : subscription.Subscription,
                                    alerts  : subscription.Alerts,
                                    messages: subscription.Messages,
                                });
                            });
                        }

                        self.configurePushSubscription();
                    } else {
                        printMessage('error', lang.system_error);
                    }
                });
            } else if (Notification.permission === 'denied') {
                self.blockNotifications();
            }
        }
    };

    this.enableEvents = function() {
        if (!self.ui.$checkbox.length) {
            return;
        }

        self.ui.$checkbox.click(function() {
            if (rlAccountInfo.PWA_Subscription
                && rlAccountInfo.PWA_Subscription.status
                && rlAccountInfo.PWA_Subscription.status !== 'blocked'
            ) {
                if (self.ui.$checkbox.is(':checked')) {
                    self.ui.$checkboxAlerts.attr('checked', 'checked').trigger('click');
                    self.ui.$checkboxAlerts.next('label').removeClass('d-none');
                    self.ui.$checkboxMessages.attr('checked', 'checked').trigger('click');
                    self.ui.$checkboxMessages.next('label').removeClass('d-none');

                    self.subscribe();
                } else {
                    self.ui.$checkboxAlerts.removeAttr('checked');
                    self.ui.$checkboxAlerts.next('label').addClass('d-none');
                    self.ui.$checkboxMessages.removeAttr('checked');
                    self.ui.$checkboxMessages.next('label').addClass('d-none');

                    self.unsubscribe();
                }
            } else {
                self.askPermissions();
            }
        });
    };

    this.askPermissions = function() {
        Notification.requestPermission(function(result) {
            if (result === 'denied') {
                self.blockNotifications();
                return;
            }

            self.configurePushSubscription();
        });
    };

    this.blockNotifications = function () {
        PWA().sendAjax({mode: 'pwa_push_blocked'}, function (response) {
            if (response && response.status && response.status == 'OK') {
                rlAccountInfo.PWA_Subscription = {status: 'blocked'};
                rlAccountInfo.isPwaRequested   = true;

                self.ui.$checkbox.removeAttr('checked');
                self.ui.$noticeField.text(lang.pwa_push_blocked_notice);
                self.ui.$checkboxAlerts.removeAttr('checked');
                self.ui.$checkboxAlerts.next('label').addClass('d-none');
                self.ui.$checkboxMessages.removeAttr('checked');
                self.ui.$checkboxMessages.next('label').addClass('d-none');
            } else {
                printMessage('error', lang.system_error);
            }
        });
    }

    this.subscribe = function (subscription) {
        var subscribeUser = true;
        if (subscription && rlAccountInfo.pwaSubscriptions) {
            rlAccountInfo.pwaSubscriptions.forEach(function (pwaSubscription) {
                if (pwaSubscription.endpoint === subscription.endpoint) {
                    rlAccountInfo.isPwaRequested   = true;
                    rlAccountInfo.PWA_Subscription = pwaSubscription;
                    subscribeUser = false;
                }
            });
        }

        if (subscribeUser) {
            PWA().sendAjax({mode: 'pwa_subscribe', subscription: JSON.stringify(subscription)}, function (response) {
                if (response && response.status && response.status == 'OK') {
                    rlAccountInfo.isPwaRequested   = true;

                    if (subscription) {
                        rlAccountInfo.pwaSubscriptions.push({
                            endpoint: subscription.Endpoint,
                            p256dh  : subscription.P256dh,
                            auth    : subscription.Auth,
                            status  : 'active',
                        });

                        rlAccountInfo.PWA_Subscription = subscription;
                        rlAccountInfo.PWA_Subscription.status = 'active';

                        if (!rlAccountInfo.PWA_Subscription.alerts) {
                            self.ui.$checkboxAlerts.attr('checked', 'checked');
                        }
                        self.ui.$checkboxAlerts.next('label').removeClass('d-none');

                        if (!rlAccountInfo.PWA_Subscription.messages) {
                            self.ui.$checkboxMessages.attr('checked', 'checked');
                        }
                        self.ui.$checkboxMessages.next('label').removeClass('d-none');

                    }
                } else {
                    printMessage('error', lang.system_error);
                }
            });
        }

        self.ui.$noticeField.text('');

        if (subscription
            && rlAccountInfo.PWA_Subscription
            && rlAccountInfo.PWA_Subscription.status
            && rlAccountInfo.PWA_Subscription.status === 'active'
        ) {
            self.ui.$checkbox.attr('checked', 'checked');

            if (rlAccountInfo.PWA_Subscription.alerts && rlAccountInfo.PWA_Subscription.alerts === '1') {
                self.ui.$checkboxAlerts.attr('checked', 'checked');
            }
            self.ui.$checkboxAlerts.next('label').removeClass('d-none');

            if (rlAccountInfo.PWA_Subscription.messages && rlAccountInfo.PWA_Subscription.messages === '1') {
                self.ui.$checkboxMessages.attr('checked', 'checked');
            }
            self.ui.$checkboxMessages.next('label').removeClass('d-none');
        }
    };

    this.unsubscribe = function () {
        PWA().sendAjax({mode: 'pwa_unsubscribe', id: rlAccountInfo.ID}, function (response) {
            if (response && response.status && response.status == 'OK') {
                rlAccountInfo.PWA_Subscription.status = 'inactive';

                self.ui.$checkbox.removeAttr('checked');
                self.ui.$noticeField.text('');
                self.ui.$checkboxAlerts.removeAttr('checked');
                self.ui.$checkboxAlerts.next('label').addClass('d-none');
                self.ui.$checkboxMessages.removeAttr('checked');
                self.ui.$checkboxMessages.next('label').addClass('d-none');
            } else {
                printMessage('error', lang.system_error);
            }
        });
    };

    this.configurePushSubscription = function() {
        if (!PWA().isPushSupportedInBrowser()) {
            return;
        }

        const applicationServerKey = PWA().urlBase64ToUint8Array(pwaConfig.vapid_public);

        var registeredServiceWorker;
        navigator.serviceWorker.ready.then(function(serviceWorker) {
            registeredServiceWorker = serviceWorker;
            return serviceWorker.pushManager.getSubscription();
        }).then(function(subscriptions) {
            if (subscriptions) {
                self.subscribe(subscriptions);
                return;
            }

            registeredServiceWorker.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: applicationServerKey
            }).then(function (subscription) {
                self.subscribe(subscription);
            }).catch(function (error) {
                console.log(error.message);
            });
        });
    };

    this.showNotification = function(title, options) {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.ready.then(function(activeServiceWorker) {
                activeServiceWorker.showNotification(title, options);
            });

            return;
        }

        new Notification(title, options);
    };
};

/**
 * Fired after PWA application has been installed on the users phone/desktop devices
 */
window.addEventListener('appinstalled', function() {
    PWA().sendAjax({
        mode: 'pwa_installed'
    });
});

/**
 * Detect device and show banner if device is iPhone or iPad and browser is safari
 */
if (DeviceDetectorClass().isOs() && !DeviceDetectorClass().isInStandaloneMode() && DeviceDetectorClass().isSafari()) {
    PWA().showIOsBanner();
}
