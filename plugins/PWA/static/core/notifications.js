
/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : NOTIFICATIONS.JS
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

self.addEventListener('notificationclick', function(event) {
    var notification = event.notification;

    if (typeof notification.data.link != 'undefined') {
        event.waitUntil(
            clients.matchAll().then(function (clis) {
                var client = clis.find(function (c) {
                    return c.visibilityState === 'visible';
                });

                if (client !== undefined) {
                    client.navigate(notification.data.link);
                    client.focus();
                } else {
                    clients.openWindow(notification.data.link);
                }

                notification.close();
            })
        );
    }
});

self.addEventListener('push', function(event) {
    if (!event.data) {
        return false;
    }

    var pwaUtils = new PWAUtils();
    pwaUtils.setServiceWorker(self.registration);

    data = JSON.parse(event.data.text());

    var options = {
        body: data.message,
        renotify: true,
        tag: 'pwa-notification'
    };

    const notRequiredOptions = [
        'image', 'dir', 'lang', 'badge', 'vibrate', 'tag', 'icon'
    ];

    notRequiredOptions.forEach(function(item) {
        if (data[item]) {
            options[item] = data[item];
        }
    });

    if (data.link) {
        options.data = {
            'link': data.link
        };
    }

    var pwaUtils = new PWAUtils();
    pwaUtils.setServiceWorker(self.registration);

    event.waitUntil(
        pwaUtils.sendPush(data.title, options)
    );
});
