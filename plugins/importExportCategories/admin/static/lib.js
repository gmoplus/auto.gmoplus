
/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
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

var importCategoriesClass = function() {
    var self = this;
    var window = false;

    this.window = function() {
        if (!window) {
            window = Ext.MessageBox.show({
                msg: 'Saving your data, please wait...',
                progressText: 'Saving...',
                width:300,
                wait:true,
                waitConfig: {interval: 200}
            });
        }
        return window;
    };

    this.import = function(stack, callback) {
        if (stack === 0) {
            self.window().show();
        }

        $.ajax({
            url: rlConfig['ajax_url'],
            data: {
                'item': 'importExportCategories_importStack',
                'stack': stack
            },
            success: function(response) {
                var success = false;

                if (response) {
                    if (response.next === true && response.stack > stack) {
                        return self.import(response.stack);
                    } else {
                        success = true;
                    }
                }

                if (callback instanceof Function) {
                    callback(success)
                }
            }
        });
    };
};

var importCategories = new importCategoriesClass();
