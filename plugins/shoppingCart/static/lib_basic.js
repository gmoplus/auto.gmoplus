
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: LIB_BASIC.JS
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

var shoppingCartBasicClass = function() {
    /**
    * reference to self object
    **/
    var self = this;

    /**
     * Currency Converter plugin availability flag
     *
     * @since 3.1.1
     * @type boolean
     */
    var curCon = false;

    /**
    * Initialize shopping cart & bidding options
    */
    this.init = function() {
        $('.add-to-cart').on('click', function() {
            var id = $(this).data('item-id');
            self.addItem(id);
        });

        $(document).on('click', '.clear-cart', function() {
            $('.clear-cart').flModal({
                caption: '',
                content: lang['shc_do_you_want_clear_cart'],
                prompt: 'shoppingCartBasic.clearCart()',
                width: 'auto',
                height: 'auto',
                click: false,
                caption: lang['shc_notice']
            });
        });

        $(document).on('click', '.delete-item-from-cart', function() {
            var id = $(this).data('id');
            var item_id = $(this).data('item-id');

            $(this).flModal({
                type: 'notice',
                content: lang['shc_notice_delete_item'],
                prompt: 'shoppingCartBasic.deleteItem(' + id + ',' + item_id + ')',
                width: 'auto',
                height: 'auto',
                click: false,
                caption: lang['shc_notice']
            });
        });

        $(document).on('click', '.auction-listing', function() {
            var itemID = $(this).data('item-id');
            self.showAuctionInfo(itemID);
        });

        this.curCon = (typeof currencyConverter !== 'undefined' && currencyConverterAvailable);
    }
    
    /**
     * Update converted price
     *
     * @since 3.1.1
     */
    this.updateConvertedPrices = function(){
        for (var i in window.currencyConvertorElements) {
            window.currencyConvertorElements[i].init();
        }
    }

    /**
     * Set new summery to the cart badge in the header
     *
     * @since 3.1.1
     * @param string total - New total with currency
     */
    this.setSummary = function(total){
        var $summary = $('.cart-icon-container span.button > span.summary');

        if (this.curCon) {
            $summary.find('.tmp').text(total);
            this.updateConvertedPrices();
        } else {
            $summary.text(total);
        }
    }

    /**
     * Add item to cart
     * 
     * @param number  id
     * @param boolean is_auction
     * @param string url
     **/ 
    this.addItem = function(id, is_auction, url) {
        var $cartContainer = $('#shopping_cart_block');
        var data = {
            mode: 'shoppingCartAddItem',
            item: id,
            is_auction: is_auction 
        };
        flUtil.ajax(data, function(response) {
            if (response.status == 'OK') {
                $cartContainer.html(response.content);
                $('.cart-icon-container').removeClass('empty');
                $('.cart-icon-container span.button > span.count').text(response.count);

                if (self.curCon) {
                    $cartContainer.find('.shc_price').convertPrice();
                }

                self.setSummary(response.total);

                if ($('#df_field_shc_quantity').length) {
                    $('#df_field_shc_quantity>td.value').html(response.count_item);
                }
                printMessage('notice', response.message);

                if (is_auction && url) {
                    setTimeout(function() {
                        location.href = url
                    },
                    3000);
                }
            } else {
                printMessage('error', response.message);
            }
        });
    }

    /**
     * Delete item from the cart
     * 
     * @param number id
     * @param number item_id
     **/
    this.deleteItem = function(id, item_id) {
        var data = {
            mode: 'shoppingCartDeleteItem',
            item: id,
            item_id: item_id,
            step: self.getProcessingStep(),
        };
        flUtil.ajax(data, function(response) {
            if (response.status == 'OK') {
                $('#shopping_cart_block').html(response.content);
                $('.cart-icon-container').removeClass('empty');
                $('.cart-icon-container span.button > span.count').text(response.count);

                self.setSummary(response.total);

                if ($('#df_field_shc_quantity').length) {
                    $('#df_field_shc_quantity>td.value').html(response.count_item);
                }
                printMessage('notice', response.message);

                if (rlPageInfo['controller'] == 'my_shopping_cart' && response.count <= 0) {
                    let elCartItems = $('#controller_area');
                    elCartItems.html('');
                    elCartItems.append(
                        $('<div>', {class: 'text-message'}).html(response.empty_cart)
                    );
                } else {
                    let elCartItems = $('#cart-item-' + id);
                    let dealerID = elCartItems.closest('form').attr('id');
                    elCartItems.remove();

                    if (typeof shoppingCart == 'object') {
                        if (dealerID.indexOf('form_') === 0) {
                            dealerID = dealerID.replace('form_', '');
                        }
                        shoppingCart.recalculateItems(dealerID);
                    }
                }
            } else {
                printMessage('error', response.message);
            }
        });
    }

    /**
     * Clear cart
     **/
    this.clearCart = function() {
        var data = {
            mode: 'shoppingCartClearCart',
            lang: rlLang,
            step: self.getProcessingStep(),
            key: rlConfig['shcOrderKey']
        };
        flUtil.ajax(data, function(response) {
            if (response.status == 'OK') {
                $('#shopping_cart_block').html(response.content);
                $('.cart-icon-container').removeClass('empty');
                $('.cart-icon-container span.button > span.count').text(response.count);

                self.setSummary(response.total);

                printMessage('notice', response.message);

                if (rlPageInfo['controller'] == 'my_shopping_cart') {
                    if (response.url) {
                        window.location.href = response.url;
                    }
                    $('#cart_items').html(response.content);
                }
            } else {
                printMessage('error', response.message);
            }
        });
    }

    /**
     * Get processing step key
     **/
    this.getProcessingStep = function() {
        let step = '';

        if ($('.step_area').length > 0) {
            let stepClass = $('.step_area').attr('class');
            stepClass = stepClass.split(/\s+/);

            for (const classCSS in stepClass) {
                if (stepClass[classCSS].indexOf('area_') > -1) {
                    step = stepClass[classCSS].split('_')[1];
                }
            }
        }

        return step;
    }

    /**
     * Show auction info in popup on browse page
     **/
    this.showAuctionInfo = function(itemID) {
        var data = {
            mode: 'shoppingShowAuctionInfo',
            item: itemID 
        };
        flUtil.ajax(data, function(response) {
            if (response.status == 'OK') {
                flUtil.loadScript([
                    rlConfig['tpl_base'] + 'components/popup/_popup.js',
                ], function(){
                    $('#shc-item-' + itemID).popup({
                        width: 300,
                        click: false,
                        scroll: true,
                        closeOnOutsideClick: true,
                        content: response.content,
                        caption: lang['shc_auction_details'],
                        navigation: {
                            cancelButton: {
                                text: lang['close'],
                                class: 'cancel'
                            }
                        }
                    });
                });
            } else {
                printMessage('error', response.message);
            }
        });

    }
}

var shoppingCartBasic = new shoppingCartBasicClass();

/**
 * Temporary solution
 *
 * @todo remove this xajax method from templates
 **/
var xajax_clearShoppingCart = function() { 
    shoppingCartBasic.clearCart(); 
}
