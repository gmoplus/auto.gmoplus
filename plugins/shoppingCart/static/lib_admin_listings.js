
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: LIB_ADMIN_LISTINGS.JS
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

var shoppingCartClass = function() {

    /**
    * reference to self object
    **/
    var self = this;
    
    this.priceFormatTabs = function(mode) {
        if (!mode) {
            return;
        }

        $('#shc_fields_area').attr('lang', mode);
        $('#shc_fields_area').parent().show();

        $('#shc-group table.form tr').each(function() {
            if ($(this).hasClass(mode) || !$(this).attr('class')) {
                self.controlDigitalProduct($('input[name="fshc[digital]"]:checked').val());
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        
        $('#shc-group').removeClass('hide');

        $('.price_item').each(function() {
            if ($(this).hasClass(mode) || !$(this).attr('class')) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        // Set price field in focus if mode is not standard listing, it will prevent saving empty price in listing.
        if (mode != 'listing') {
            var $priceCont = $('#shc-group .price_item.listing');
            var $startingPrice = $priceCont.find('.starting-price');
            $priceCont.find('input[type=text]').trigger('focus');

            if ($startingPrice.is(':checked')) {
                $startingPrice.trigger('click');
            }
        }

        if (mode == 'auction') {
            $('input[name="fshc[shc_quantity]"]').val(1);
            $('input[name="fshc[shc_quantity]"]').prop('disabled', true);
        }
        else if( mode == 'fixed') {
            $('input[name="fshc[shc_quantity]"]').prop('disabled', false);
        }
        else if (mode == 'listing') {
            $('input[name="fshc[shc_quantity]"]').val(1);
        }

        if ((mode == 'auction' || mode == 'fixed') && $('input[name="fshc[digital]"]:checked').val() != 1) {
            $('#fs_shipping_details').show();
            if ($('input.shc-price-type:checked').val() == 'calculate') {
                $('#fs_shipping_methdos').show();
            }
        } else {
            $('#fs_shipping_details').hide();
            if ($('#fs_shipping_methdos').is(':visible') || $('#fs_shipping_methdos').css('display') != 'none') {
                $('#fs_shipping_methdos').hide();
            }
        }
    }
    
    this.controlDigitalProduct = function(selected, mode, unlimited) {
        if (selected == 1) {
            $('.available').hide();
            $('.digital-product').show();
            $('#fs_shipping_details').hide();
            $('.digital').show();

            if (mode != 'auction') {
                if (unlimited) {
                    $('input.quantity-unlimited').prop('checked', true);
                }
                if ($('input.quantity-unlimited').is(':checked')) {
                    $('input[name="fshc[shc_quantity]"]').prop('readonly', true);
                }
                $('.quantity').show();
            } else {
                $('.quantity').hide();
            }
        } else {
            if (mode != 'auction') {
                $('input[name="fshc[shc_quantity]"]').prop('readonly', false);
            } else {
                $('.quantity').show();
            }
            $('.available').show();
            $('.digital-product').hide();
            $('#fs_shipping_details').show();
            $('.digital').hide();
        }
    }
    
    this.handleShippingPriceType = function(type) {
        var changeShippingPriceType = function(type) {
            switch (type) {
                case 'free' :
                    $('#sf_field_shc_shipping_price').parent().hide();
                    $('#sf_field_shc_shipping_discount').parent().hide();
                    $('#shipping-methods').hide();
                    $('#sf_field_shc_shipping_method').parent().show();
                    break;
                    
                case 'fixed' :
                    $('#sf_field_shc_shipping_price').parent().show();
                    $('#sf_field_shc_shipping_discount').parent().show();
                    $('#shipping-methods').hide();
                    $('#sf_field_shc_shipping_method').parent().show();
                    break;
                    
                case 'calculate' :
                    $('#sf_field_shc_shipping_price').parent().hide();
                    $('#sf_field_shc_shipping_discount').parent().hide();
                    $('#shipping-methods').show();
                    $('#sf_field_shc_shipping_method').parent().hide();
                    
                    break;
            }
        }
        $('.shc-price-type').change(function() {
            if ($(this).is(':checked')) {
                changeShippingPriceType($(this).val());
            }
        });
        if (type) {
            changeShippingPriceType(type);
        }
    }

    /**
     * Delete a file
     **/
    this.deleteFile = function(itemID) {
        flynax.sendAjaxRequest('shoppingCartDeleteFile', {itemID: itemID}, function(response){
            if (response.status == 'OK') {
                printMessage('notice', response.message);
            } else {
                printMessage('error', response.message);
            }
        });
    }

    /**
     * Calculate commission
     **/
    this.calculateCommission = function(price, is_auction) {
        var data = {
            price: price,
            is_auction: is_auction
        };
        flynax.sendAjaxRequest('calculateCommission', data, function(response){
            if (response.status == 'OK') {
                if (!is_auction) {
                    $('.commission').text(response.commission);
                    $('.price-item-total').text(response.price);
                    $('.price-item-total').parent().parent().removeClass('hide');
                } else {
                    $('.price-start-commission').text(response.commission);
                    $('.price-start-item-total').text(response.price);
                    $('.price-start-item-total').parent().parent().removeClass('hide');
                }
            } else {
                printMessage('warning', response.message);
            }
        });
    }
}

var shoppingCart = new shoppingCartClass();
