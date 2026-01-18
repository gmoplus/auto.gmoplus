
/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : LIB_ADMIN.JS
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2022
 *	https://www.flynax.com
 *
 ******************************************************************************/

var shoppingCartClass = function() {

    /**
    * reference to self object
    **/
    var self = this;

    /**
     * Convert interface options
     *
     * @since 3.0.0
     * @type Object
     */
    this.convertOptions = {};

    /**
     * Set/reset convert options
     *
     * @since 3.0.0
     */
    this.setConvertOptions = function(){
        this.convertOptions = {
            count: 10,
            start: 0,
            popup: false,
            progress: false
        };
    }

    /**
     * Initialize convert prices
     *
     * @since 3.0.0
     */
    this.initConvertPrices = function() {
        this.setConvertOptions();

        this.convertOptions.popup = flynax.buildProgressPopup(lang['shc_convert_prices'], lang['shc_convert_in_progress']);
        this.convertOptions.progress = true;

        this.convertPrices();

        $(window).bind('beforeunload', function(){
            if (self.convertOptions.progress) {
                return lang['shc_convert_in_progress'];
            }
        });
    }

    /**
     * Convert prices
     *
     * @since 3.0.0
     * @param function callback - Callback function to call on finish
     */
    this.convertPrices = function(callback){
        var data = {
            start: this.convertOptions.start,
            limit: this.convertOptions.count,
        };

        flynax.sendAjaxRequest('shoppingCartConvertPrices', data, function(response){
            if (response.action == 'next') {
                self.convertOptions.start += self.convertOptions.count;

                self.convertOptions.popup.updateProgress(response.progress / 100);
                self.convertPrices(callback);
            } else {
                self.convertOptions.popup.updateProgress(1);

                if (typeof callback == 'function') {
                    callback.call();
                } else {
                    setTimeout(function(){
                        printMessage('notice', lang['shc_convert_completed']);
                        self.convertOptions.popup.hide();
                        self.setConvertOptions();
                    }, 3000);
                }
            }
        });
    }

    /**
     * Delete order
     *
     * @since 3.0.0
     * @param id - order id
     */
    this.deleteOrder = function(id){
        if (!id) {
            return false;
        }

        flynax.sendAjaxRequest('shoppingCartDeleteOrder', {id: id}, function(response){
            if (response.status == 'OK') {
                shoppingCartGrid.reload();
                printMessage('notice', lang['shc_order_deleted_ok']);
            } else {
                printMessage('error', lang['shc_order_deleted_error']);
            }
        });
    }

    /**
     * Delete order
     *
     * @since 3.0.0
     * @param key - order key
     */
    this.deleteShippingField = function(key){
        if (!key) {
            return false;
        }

        flynax.sendAjaxRequest('shippingFieldDelete', {key: key}, function(response){
            if (response.status == 'OK') {
                shippingFieldsGrid.reload();
                printMessage('notice', response.message);
            } else {
                printMessage('error', response.message);
            }
        });
    }

    /**
     * Delete auction
     *
     * @since 3.0.0
     * @param id - order id
     */
    this.deleteAuctionItem = function(id){
        if (!id) {
            return false;
        }

        flynax.sendAjaxRequest('shoppingCartDeleteAuctionItem', {id: id}, function(response){
            if (response.status == 'OK') {
                auctionGrid.reload();
                printMessage('notice', response.message);
            } else {
                printMessage('error', response.message);
            }
        });
    }

    /**
     * Delete bid of auction
     *
     * @since 3.0.0
     * @param id - order id
     */
    this.deleteBid = function(id){
        if (!id) {
            return false;
        }

        flynax.sendAjaxRequest('shoppingCartDeleteBid', {id: id}, function(response){
            if (response.status == 'OK') {
                auctionBidsGrid.reload();
                printMessage('notice', response.message);
            } else {
                printMessage('error', response.message);
            }
        });
    }

    /**
     * Download product
     **/
    this.download = function(itemID) {
        flynax.sendAjaxRequest('shoppingDownloadFile', {itemID: itemID}, function(response){
            if (response.status == 'OK') {
                window.location.href = response.request;
            } else {
                printMessage('error', response.message);
            }
        });
    }
}

var shoppingCart = new shoppingCartClass();
