
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

var shoppingCartClass = function() {

	/**
	* reference to self object
	**/
	var self = this;
	
	this.price_tpl = '';

    /**
     * Check account settings
     **/
    this.checkAccountSettings = function() {
        var data = {
            mode: 'shoppingCheckAccountSettings' 
        };
        flUtil.ajax(data, function(response) {
            if (status != 'OK') {
                printMessage('warning', response.message);
            }
        });
    }

    /**
     * Calculate commission
     **/
    this.calculateCommission = function(price, is_auction) {
        var data = {
            mode: 'calculateCommission',
            item: price,
            is_auction: is_auction
        };
        flUtil.ajax(data, function(response) {
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

    /**
     * Add bid
     **/
    this.addBid = function(id, rate) {
        var $addButton = $('#shc_add_bid');
        $addButton.addClass('disabled', true).text(lang['loading']);

        var data = {
            mode: 'auctionAddBid',
            item: id,
            rate: rate 
        };
        flUtil.ajax(data, function(response) {
            if (response.status == 'OK') {
                $('#bid-history-list').html(response.content);
                $('#rate_bid').val('');
                $('#total_bids').html(response.number);
                $('#bh_bidders').html(response.bidders);
                $('#bh_total_bids').html(response.count);
                $('#current_price').html(response.rate);
                $('#shc_min_bid').html(response.min_bid);
                $('#rate_bid').attr('placeholder', response.min_bid);

                if (response.hide_buy_now) {
                    $('#shc_by_now_item').parent('div').remove();
                }

                printMessage('notice', response.message);
            } else {
                printMessage('error', response.message);
            }

            $addButton.removeClass('disabled', false).text($addButton.data('phrase'));
        });
    }

    /**
     * Cancel bid
     **/
    this.cancelBid = function(id, itemID) {
        var data = {
            mode: 'auctionCancelBid',
            item: id,
            itemID: itemID,
            controller: rlPageInfo['controller']
        };
        flUtil.ajax(data, function(response) {
            if (response.status == 'OK') {
                printMessage('notice', response.message);
                if (response.url) {
                    setTimeout(function() {
                        window.location.href = response.url
                    }, 1000);
                }
                $('#bid-' + id).remove();
                $('#total_bids').html(response.count);
                $('#bh_total_bids').html(response.count);
                $('#bh_bidders').html(response.bidders);
                $('#current_price').html(response.price);
                $('#shc_min_bid').html(response.min_bid);
                $('#rate_bid').attr('placeholder', response.min_bid);
                $('#bid-history-list').html(response.content);
            } else {
                printMessage('error', response.message);
            }
        });
    }

    /**
     * Renew auction
     **/
    this.renewAuction = function(item_id) {
        var data = {
            mode: 'renewAuction',
            item: item_id 
        };
        flUtil.ajax(data, function(response) {
            if (response.status == 'OK') {
                $('#renew_auction-' + item_id + '').parent('li').remove();
                printMessage('notice', response.message);
            } else {
                printMessage('error', response.message);
            }
        });
    }

    /**
     * Close auction
     **/
    this.closeAuction = function(item_id) {
        var data = {
            mode: 'closeAuction',
            item: item_id 
        };
        flUtil.ajax(data, function(response) {
            if (response.status == 'OK') {
                $('#close_auction-' + item_id + '').parent('li').remove();
                printMessage('notice', response.message);
            } else {
                printMessage('error', response.message);
            }
        });
    }

    /**
     * Change shipping status
     **/
    this.changeShippingStatus = function(status, item_id) {
        var data = {
            mode: 'shoppingCartChangeShippingStatus',
            status: status,
            item: item_id 
        };
        flUtil.ajax(data, function(response) {
            if (response.status == 'OK') {
                if (status == 'delivered') {
                    $('#shs_' + item_id).attr('disabled', 'disabled');
                }
                printMessage('notice', response.message);
            } else {
                printMessage('error', response.message);
            }
        });
    }

    /**
     * Save tracking number
     **/
    this.saveTrackingNumber = function(item_id, number) {
        var data = {
            mode: 'shoppingCartSaveTrackingNumber',
            item: item_id,
            number: number 
        };
        flUtil.ajax(data, function(response) {
            if (response.status == 'OK') {
                $('.close').click();
                $('.tracking-number').html(response.content);
                printMessage('notice', response.message);
            } else {
                printMessage('error', response.message);
            }
        });
    }
    
    /**
     * Wrap default price field by plugin price management panel
     *
     * @since 3.1.2
     *
     * @param  string field - Field key
     */
    this.replaceFieldPrice = function(field) {
        var $priceCont = $('#sf_field_' + field);
        var $priceInput = $priceCont.find('input[type=text]');
        var $priceCell = $priceCont.closest('.submit-cell');

        $priceCont.children().wrapAll($('<div class="price_item auction fixed listing">'));
        $priceCont.addClass('d-flex flex-column flex-md-row align-items-md-center');
        $priceCont.find('.price_item:first').prepend($('<div class="price-item-caption mb-1">').text(lang.shc_buy_now));
        $priceInput.addClass('mr-3');

        $priceCell.before($('#shc-group'));
        $('#shc_fields_area').before($priceCell);

        $priceCont.prepend($('#price_variants').find('> *'));
    }
    
    this.priceFormatTabs = function(mode, isFirst) {
        if (!isLogin && (mode == 'fixed' || mode == 'auction')) {
            $('input[name="fshc[shc_mode]"]').each(function() {
                if ($(this).val() == 'listing') {
                    $(this).trigger('click');
                    $(this).prop('checked', true);
                } else {
                    $(this).prop('checked', false);
                }
            });

            if (!isFirst) {
                flUtil.loadScript([
                    rlConfig['tpl_base'] + 'components/popup/_popup.js',
                ], function(){
                    $('#shc-group').popup({
                        click: false,
                        scroll: false,
                        closeOnOutsideClick: false,
                        content: $('#login_modal_source').html(),
                        caption: lang['sign_in'],
                        onShow: function(content){
                            content.find('.caption_padding').hide();
                            content.find('div.tmp-dom').attr('id', 'shc-auth');
                            content.find('div.tmp-dom input[type="submit"]').next().remove();
                            content.find('form').submit(function() {
                                var username = $(this).find('input[name="username"]');
                                var password = $(this).find('input[name="password"]');

                                if (username.val() && password.val()) {
                                    $('input[name="login[username]"]').val(username.val());
                                    $('input[name="login[password]"]').val(password.val());
                                    $('div.popup .close').trigger('click');
                                    flynax.slideTo('div.auth');
                                } else {
                                    username.addClass('error');
                                    password.addClass('error');
                                }

                                username.focus(function() {
                                    $(this).removeClass('error');
                                });

                                password.focus(function() {
                                    $(this).removeClass('error');
                                });

                                return false;
                            });
                        }
                    });
                });
            }
            return;
        }

        $('#shc-group').attr('data-mode', mode);

        // Set price field in focus if mode is not standard listing, it will prevent saving empty price in listing.
        if (mode != 'listing') {
            var $priceCont = $('#shc-group .price_item.listing');
            var $startingPrice = $priceCont.find('.starting-price');
            $priceCont.find('input[type=text]').trigger('focus');

            if ($startingPrice.is(':checked')) {
                $startingPrice.trigger('click');
            }
        }

        $('#shc_fields_area table.form tr,#shc_fields_area div.submit-cell').each(function() {
            if ($(this).hasClass(mode) || !$(this).attr('class')) {
                self.controlDigitalProduct($('input[name="fshc[digital]"]:checked').val());
                $(this).removeClass('hide');
            } else {
                $(this).addClass('hide');
            }
        });

        $('.price_item').each(function() {
            if ($(this).hasClass(mode) || !$(this).attr('class')) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        if ((mode == 'auction' || mode == 'fixed') && !shc_check_settings) {
            self.checkAccountSettings();
            shc_check_settings = true;
        }

        if (mode == 'auction') {
            $('input[name="fshc[shc_quantity]"]').val(1);
            $('input[name="fshc[shc_quantity]"]').attr('disabled', 'disabled').addClass('disabled');
            $('.digital').addClass('hide');

            if ($('input[name="fshc[digital]"]:checked').val()) {
                $('.quantity').addClass('hide');
            }
        }
        else if( mode == 'fixed') {
            if ($('input.quantity-unlimited').is(':checked')) {
                $('input[name="fshc[shc_quantity]"]').attr('disabled', 'disabled').addClass('disabled').val('');
            } else {
                $('input[name="fshc[shc_quantity]"]').removeAttr('disabled').removeClass('disabled');
            }
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
            $('.available').addClass('hide');
            $('.digital-product').removeClass('hide');
            $('#fs_shipping_details').hide();
            $('.digital').removeClass('hide');
            if (mode != 'auction') {
                if (unlimited) {
                    $('input.quantity-unlimited').prop('checked', true);
                }
                if ($('input.quantity-unlimited').is(':checked')) {
                    $('input[name="fshc[shc_quantity]"]').attr('disabled', 'disabled').addClass('disabled').val('');
                }
                $('.quantity').removeClass('hide');
            } else {
                $('.quantity').addClass('hide');
            }
        } else {
            $('.digital-product').addClass('hide');
            if (mode != 'listing') {
                if (mode != 'auction') {
                    $('input[name="fshc[shc_quantity]"]').removeAttr('disabled').removeClass('disabled');
                } else {
                    $('.quantity').removeClass('hide');
                }
                $('.available').removeClass('hide');
                $('.digital').addClass('hide');
            }
        }
    }
    
    this.handleShippingPriceType = function(type) {
        var changeShippingPriceType = function(type) {
            switch (type) {
                case 'free' :
                    $('#sf_field_shc_shipping_price').parent().addClass('hide');
                    $('#sf_field_shc_shipping_discount').parent().addClass('hide');
                    $('#shipping-methods').addClass('hide');
                    $('#shc-system-shipping-config').addClass('hide');
                    $('#sf_field_shc_shipping_method').parent().removeClass('hide');
                    break;
                    
                case 'fixed' :
                    $('#sf_field_shc_shipping_price').parent().removeClass('hide');
                    $('#sf_field_shc_shipping_discount').parent().removeClass('hide');
                    $('#shipping-methods').addClass('hide');
                    $('#shc-system-shipping-config').removeClass('hide');
                    $('#sf_field_shc_shipping_method').parent().removeClass('hide');
                    break;
                    
                case 'calculate' :
                    $('#sf_field_shc_shipping_price').parent().addClass('hide');
                    $('#sf_field_shc_shipping_discount').parent().addClass('hide');
                    $('#shc-system-shipping-config').removeClass('hide');
                    $('#shipping-methods').removeClass('hide');
                    $('#sf_field_shc_shipping_method').parent().addClass('hide');
                    
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
     * handler events of items
     **/
    this.handlerItems = function() {
        $('span.increase').click(function() {
            var quantityEl = $(this).prev();
            var available = parseFloat(quantityEl.attr('data-available-quantity'));

            if (available <= 1) {
                printMessage('error', lang['shc_available_quantity_error']);
                return;
            } else {
                available--;
                quantityEl.attr('data-available-quantity', available);
            }
            var current = quantityEl.val();
            current++;
 
            quantityEl.val(current);
            quantityEl.attr('data-prev-quantity', current);
            var shc_dealer = $(this).parents('form').attr('id').split('_')[1];
            self.recalculateTotal(shc_dealer, quantityEl.attr('id').split('_')[1], current);
        });

        $('span.decrease').click(function() {
            var quantityEl = $(this).next();
            var current = quantityEl.val();

            if (current > 1) {
                current--;
                var available = parseFloat(quantityEl.attr('data-available-quantity'));
                available++;
                quantityEl.attr('data-available-quantity', available); 
            }

            quantityEl.val(current);
            quantityEl.attr('data-prev-quantity', current);
            var shc_dealer = $(this).parents('form').attr('id').split('_')[1];
            self.recalculateTotal(shc_dealer, quantityEl.attr('id').split('_')[1], current);
        });

        $('input.quantity').keyup(function() {
            self.handleInputQuantity($(this));
        });

        $('input.quantity').blur(function() {
            self.handleInputQuantity($(this));
        });
        
        $('input.quantity').focus(function() { 
            $(this).select(); 
        });
    }

    /**
     * recalculate total method
     **/
    this.handleInputQuantity = function(quantityEl) {
        var available = parseFloat(quantityEl.attr('data-available-quantity'));
        var prevQuantity = parseFloat(quantityEl.attr('data-prev-quantity'));
        var current = quantityEl.val();
        var currentDiff = current - prevQuantity;

        if (current < 1) {
            quantityEl.val(1);
            return;
        }

        if ((available <= 0 && currentDiff > 0) || available < currentDiff) {
            quantityEl.val(prevQuantity);
            printMessage('error', lang['shc_available_quantity_error']);
            return;
        } else {
            available = available - currentDiff;
            quantityEl.attr('data-available-quantity', available);
            quantityEl.attr('data-prev-quantity', current);
        }

        var shc_dealer = quantityEl.parents('form').attr('id').split('_')[1];
        self.recalculateTotal(shc_dealer, quantityEl.attr('id').split('_')[1], quantityEl.val());
    }

    /**
     * Set the price taking into account currency converter plugin availability
     * Prepares price before set as text
     *
     * @since 3.1.1
     *
     * @param object $obj - jQuery element
     * @param array data  - Separated currency and price data
     */
    this.setPrice = function($obj, data) {
        if (!$obj.length || !data) {
            return;
        }

        if (typeof currencyConverter !== 'undefined' && currencyConverterAvailable) {
            if ($obj.find('> span.tmp').length) {
                $obj = $obj.find('> span.tmp');
            }
        }

        $obj.text(this.buildPrice(data));
    }

    /**
     * Get price taking into account currency converter plugin availability
     * Parse the price string
     *
     * @since 3.1.1
     *
     * @param  object $obj - jQuery element
     * @return array       - Separated currency and price data
     */
    this.getPrice = function($obj){
        if (!$obj.length) {
            return [];
        }

        if (typeof currencyConverter !== 'undefined' && currencyConverterAvailable) {
            if ($obj.find('> span.tmp').length) {
                $obj = $obj.find('> span.tmp');
            }
        }

        return this.parsePrice($obj.text());
    }

    /**
     * Parse price string
     *
     * @since 3.1.1
     *
     * @param  string str - Price string with currency code
     * @return array      - Separated currency and price data
     */
    this.parsePrice = function(str){
        if (!str) {
            return [];
        }

        var out = new Array();

        // remove html
        str = trim(str.replace(/(<.*?>)/ig, ''));

        var signs = '\\'+rlConfig['price_delimiter'];
        signs += '\\'+rlConfig['price_separator'];

        // parse currency
        var pattern = '^([\\D]{1,5})?\\s?([0-9\.\,\'\:\;\"\\s' + signs + ']+)\\s?([\\D]{1,5})?$';
        var matches = str.match(pattern);

        if (!matches) {
            return [];
        }

        if (matches[1] || matches[3]) {
            var currency = matches[1] ? matches[1] : matches[3];
            out['currency'] = trim(currency.replace(',', ''));

            // find currency key by currency symbol/code
            if (typeof currencyConverter !== 'undefined' && currencyConverterAvailable) {
                for (var i in currencyConverter.rates) {
                    if ($.inArray(out['currency'], currencyConverter.rates[i][1]) >= 0) {
                        out['key'] = i;
                        break;
                    }
                }
            }

            str = matches[2];
        } else {
            return [];
        }

        // remove millesimal sign
        if (rlConfig['price_delimiter'] && rlConfig['price_delimiter'] != '') {
            var pattern = new RegExp('\\' + rlConfig['price_delimiter'], 'gi');
            str = str.replace(pattern, '');
        }

        // replace cents separation sign to dot
        if (rlConfig['price_separator'] && rlConfig['price_separator'] != '.') {
            var pattern = new RegExp('\\' + rlConfig['price_separator'], 'gi');
            str = str.replace(pattern, '.');
        }

        str = parseFloat(str);
        out['price'] = str;

        return out;
    }

    /**
     * Build price string
     *
     * @since 3.1.1
     *
     * @param  array data - Price data
     * @return string     - Formatted price string
     */
    this.buildPrice = function(data) {
        var price = '';

        if (rlConfig['system_currency_position'] == 'before') {
            price = data['currency'] + ' ' + data['price'];
        } else {
            price = data['price'] + ' ' + data['currency'];
        }

        return price;
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
     * recalculate total method
     **/
    this.recalculateTotal = function(dealer, id, quantity) {
        var $itemsCont = $('.cart-items-table');
        var $itemRow = $itemsCont.find('#cart-item-' + id);
        var itemPrice = this.getPrice($itemRow.find('.item-price > span'));

        // Calculate item total
        itemPrice['price'] = itemPrice['price'] * quantity;
        this.setPrice($itemRow.find('.item-total > span'), itemPrice)

        this.recalculateItems(dealer);
    }

    /**
     * Round price
     *
     * @since 3.1.3
     * @param  number price - Price to round
     * @return string       - Rounded price
     */
    this.roundPrice = function(price) {
        price = parseFloat(price);
        price = Math.round(price * 100) / 100;
        price = price.toFixed(currencyConverter.config.show_cents && price < 1000 ? 2 : 0);

        return parseFloat(price);
    }

    /**
     * Recalculate total
     *
     * @since - Method completely updated
     * @param  string dealer - Dealer ID or "single" string if there are single dealer items in cart
     */
    this.recalculateItems = function(dealer) {
        var curCon = (typeof currencyConverter !== 'undefined' && currencyConverterAvailable);

        var $total = $('#total_' + dealer);
        var currentTotal = this.getPrice($total);
        var total = 0;

        $('.cart-items-table .item-total').each(function(){
            $item = $(this).find('.shc_price');
            var tmpPrice = self.getPrice($item);
            if (tmpPrice) {
                // Convert price to the system currency
                if (curCon && tmpPrice.key) {
                    tmpPrice['price'] = self.roundPrice(currencyConverter.convertPrice(tmpPrice));
                }
                total += tmpPrice['price'];
            }
        });

        currentTotal['price'] = total;
        this.setPrice($total, currentTotal);

        if (curCon) {
            this.updateConvertedPrices();
        }
    }

    /**
     * Calculate shipping fixed price
     **/
    this.calculateShippingPrice = function() {
        var orderTotalTmp = order_total;
        var shippingPrice = 0;

        // handle fixed price of multi type
        $('select.item-fixed-price option:selected, select.item-shipping-methods option:selected').each(function() {
            var selectedMethod = '';
            if ($(this).closest('div.table-cell').next('div').find('span input.shipping-method-fixed').length > 0) {
                selectedMethod = $(this).closest('div.table-cell').next('div').find('span input.shipping-method-fixed:checked').val();
            }

            if (!selectedMethod || selectedMethod == 'courier') {
                var itemID = $(this).parent('select').data('item');
                var tmpPrice = 0;
                if ($(this).val() != '' && $(this).attr('data-fixed-price').length > 0) {
                    tmpPrice = parseFloat($(this).attr('data-fixed-price'));
                    if (shcItems[itemID]['Quantity'] >= shcItems[itemID]['Shipping_discount_at'] && shcItems[itemID]['Shipping_discount_at'] > 0) {
                        tmpPrice = tmpPrice - (tmpPrice * shcItems[itemID]['Shipping_discount'] / 100);
                    }
                }
                shippingPrice += tmpPrice;
            }
        });

        // handle fixed price of single type
        $('.cart-items-table [class^=shipping-fixed-price-').each(function() {
            var selectedMethod = '';
            if ($(this).next('div').find('span input.shipping-method-fixed').length > 0) {
                selectedMethod = $(this).next('div').find('span input.shipping-method-fixed:checked').val();
            }

            if (!selectedMethod || selectedMethod == 'courier') {
                var itemID = $(this).data('item');
                var price_data = shoppingCart.getPrice($(this).find('.shc_price'));
                var tmpPrice = price_data['price'];

                if (shcItems[itemID]['Quantity'] >= shcItems[itemID]['Shipping_discount_at'] && shcItems[itemID]['Shipping_discount_at'] > 0) {
                    tmpPrice = tmpPrice - ((tmpPrice * shcItems[itemID]['Shipping_discount']) / 100);
                }

                shippingPrice += tmpPrice;
            }
        });

        orderTotalTmp += shippingPrice;

        shoppingCart.setPrice($('#order-shipping-price'), {currency: rlConfig['system_currency'], price: shippingPrice});
        shoppingCart.setPrice($('#order-total'), {currency: rlConfig['system_currency'], price: orderTotalTmp});
        shoppingCart.updateConvertedPrices();
    }

    /**
     * Update left time
     *
     * @param string date
     * @param int period
     * @param string tpl
     */
    this.updateLeftTime = function(date, period, tpl) {
        var auctionStart = new Date(date.replace(/ /g, 'T')).getTime() + (period * 86400 * 1000);

        // Update the count down every 1 second
        var run = setInterval(function() {
            var currentUtcTime = new Date(); // This is in UTC

            // Converts the UTC time to a locale specific format, including adjusting for timezone.
            var currentTime = new Date(currentUtcTime.toLocaleString('en-US', { timeZone: shcTimeZone }));
            currentTime = currentTime.getTime();
            var diff = auctionStart - currentTime;

            var years = Math.floor(diff / (1000 * 365 * 60 * 60 * 24));
            var months = Math.floor(diff / (1000 * 30 * 60 * 60 * 24));
            var days = Math.floor(diff / (1000 * 60 * 60 * 24));
            var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

            var timeAttr = tpl.split('|');

            var date = years > 0 ? years + timeAttr[0] + ' ' : '';
            date += months > 0 ? months + timeAttr[1] + ' ' : '';
            date += days > 0 ? days + timeAttr[2] + ' ' : '';
            date += hours > 0 ? hours + timeAttr[3] + ' ' : '';
            date += minutes + timeAttr[4];

            $('#time-left').html(date);

            if (diff < 0) {
                clearInterval(run);
                $('#time-left').html(date);
            }
        }, 1000);
    }

    /**
     * Convert date by selected timezone
     * @param  int timestamp
     * @return string - Converted date
     */
    this.getDateByTimeZone = function(timestamp){
        if (!timestamp) {
            return '';
        }

        if (timestamp) {
            return moment.tz(new Date(timestamp), shcTimeZone).format();
        }
    }

    /**
     * Handle shipping settings
     **/
    this.handleShippingSettings = function(currentMethod, itemID) {
        if (currentMethod) {
            for(var i = 0; i < shcShippingMethods.length; i++) {
                if (currentMethod == shcShippingMethods[i]) {
                    $('#shipping-method-' + itemID + '-' + shcShippingMethods[i]).show(); 
                } else {
                    $('#shipping-method-' + itemID + '-' + shcShippingMethods[i]).hide(); 
                }
            }
        }
    }

    /**
     * Download product
     **/
    this.download = function(itemID) {
        var data = {
            mode: 'shoppingDownloadFile',
            item: itemID 
        };
        flUtil.ajax(data, function(response) {
            if (response.status == 'OK') {
                window.location.href = response.request;
            } else {
                printMessage('error', response.message);
            }
        });
    }

    /**
     * Delete file
     **/
    this.deleteFile = function(itemID) {
        // Uploaded file remove handler
        flUtil.loadScript(rlConfig['tpl_base'] + 'components/popup/_popup.js', function(){
            var $interface = $('<span>').text(lang['confirm_notice']);

            $('.file-data .delete-file-product').popup({
                click: false,
                content: $interface,
                caption: lang['delete_file'],
                navigation: {
                    okButton: {
                        text: lang['delete'],
                        onClick: function(popup){
                            var $button = $(this);

                            $button
                                .addClass('disabled')
                                .prop('disabled', true)
                                .val(lang['loading']);

                            var data = {
                                mode: 'shoppingCartDeleteFile',
                                item: itemID 
                            };
                            flUtil.ajax(data, function(response) {
                                if (response.status == 'OK') {
                                    printMessage('notice', response.message);
                                    $('#digital_product_file').remove();
                                } else {
                                    $button
                                        .removeClass()
                                        .prop('disabled', false)
                                        .val(lang['save']);
                                    printMessage('error', lang['system_error']);
                                }
                                popup.close();
                            }, true);
                        }
                    },
                    cancelButton: {
                        text: lang['cancel'],
                        class: 'cancel'
                    }
                }
            });
        });
    }
}

var shoppingCart = new shoppingCartClass();
