
/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : UTILS.JS
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

var PWAUtils = function() {
    var self = this;
    this.serviceWorker = null;

    this.setServiceWorker = function(serviceWorker) {
        self.serviceWorker = serviceWorker;
    };

    this.sendPush = function(title, options) {
        if (!self.serviceWorker) {
            return false;
        }

        self.serviceWorker.showNotification(title, options);
    };
};
