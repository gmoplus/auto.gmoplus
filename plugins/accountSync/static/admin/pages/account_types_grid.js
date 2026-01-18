
/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.7.0
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: ACCOUNT_TYPES_GRID.JS
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
 * Account type grid of the plugin
 * @returns {{run: run}}
 * @constructor
 */
var AccountTypesGrid = function() {
    var self = this;
    this.grid = {};

    this.init = function() {
        var options = {
            key: 'account_sync',
            id: 'account-types',
            title: lang['ext_title'],
            store: store
        };

        var itemGrid = new gridObj(options);
        itemGrid.init();
    };

    return {
        run: function() {
            self.init();
        }
    };
};


