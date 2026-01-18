
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
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

(function($) {
    $.convertPrice = function(element, options){
        var self = this;

        this.element = $(element).prop('tagName') == 'SPAN' && $(element).parent().hasClass('price_tag')
            ? $(element).parent()
            : $(element);
        this.rates = new Array();
        this.config = new Array();
        this.options = $.extend({}, $.convertPrice.defaultOptions, options);

        /**
         * Plugin initializer
         */
        this.init = function(){
            if (!this.element.length) {
                return;
            }

            // set data
            this.setData();

            // wrap default price in span
            this.wrap();

            // parse price
            this.convert();
        }

        /**
         * Set default data
         */
        this.setData = function(){
            var variables = ['rates', 'config'];

            // Set variables
            for (var i in variables) {
                // Validate data
                if (!currencyConverter || typeof currencyConverter[variables[i]] != 'object') {
                    console.log('Currency Converter FAILED: The ' + variables[i] + ' data is unavailabe in JS');
                    return;
                }

                // Set data by cloning the object
                this[variables[i]] = jQuery.extend(true, {}, currencyConverter[variables[i]]);
            }

            // Override configs
            this.config.currency = this.options.currencyKey ? this.options.currencyKey : this.config.currency;
            this.config.show_cents = this.options.showCents !== null ? this.options.showCents : this.config.show_cents;

            // Move usd to the beginning of the rates array
            if (this.rates['dollar']) {
                var usd = [];
                usd['dollar'] = this.rates['dollar'];
                delete this.rates['dollar'];
                this.rates = jQuery.extend(true, usd, this.rates);
            }
        }

        this.wrap = function(){
            // No default currency selected
            if (!this.config.currency) {
                return false;
            }

            if (!this.element.find('> span').length){
                this.element.wrapInner(
                    $('<span>').addClass('tmp')
                );
            }
        }

        /**
         * Find and convert price in given element
         */
        this.convert = function(){
            // No default currency selected
            if (!this.config.currency) {
                return false;
            }

            var $initial  = this.element.find('> span:not(.converted-price)');
            var price_tag = $initial.text().trim();
            var price     = this.parse(price_tag);

            if (!price) {
                console.log('Currency Converter FAILED: Unable to parse price tag: ' + price_tag);
                return false;
            }

            // Define converted price container
            if (this.element.find('.converted-price').length) {
                var $converted = this.element.find('.converted-price');
            }
            // Create new span for converted price
            else {
                var $converted = $('<span>').addClass('converted-price');
                this.element.find('*:first').after($converted);
            }

            // Show initial price
            if (price.key == self.config.currency && !this.options.shortView) {
                $converted.addClass('hide');
                $initial.removeClass('hide');
            }
            // Prepare and show converted price
            else {
                var converted_price = self.convertPrice(price);
                var prepared_price = self.prepare(converted_price, this.options.noCurrency, this.options.plainNumber);

                $converted
                    .text(prepared_price)
                    .removeClass('hide');
                $initial.addClass('hide');
            }
        }

        /**
         * Prepare final view of the converted price
         *
         * @param double price     - Default price
         * @param bool no_currency - Do not show currency
         * @return string          - Final string of formated price with currency
         */
        this.prepare = function(price, no_currency, plain_number){
            // round price
            price = Math.round(price * 100) / 100;
            price = price.toFixed(this.config.show_cents && price < 1000 ? 2 : 0);

            // Convert to short view
            if (this.options.shortView) {
                price = this.shortPrice(price);
            } else {
                // replace cents separator
                price = price.replace('\.', this.config.cents_separator);

                // add millesimal delimiter
                if (!plain_number) {
                    price = price.replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1' + this.config.price_delimiter);
                }
            }

            // add currency
            if (!no_currency) {
                if (this.config.currency_position == 'before') {
                    price = this.getCurrency(this.config.currency) + ' ' + price;
                } else {
                    price = price + ' ' + this.getCurrency(this.config.currency);
                }
            }

            return price;
        }

        /**
         * Convert price to the short view
         *
         * @since 3.2.0
         *
         * @param  string string - Price to convert
         * @return string        - Converted price
         */
        this.shortPrice = function(string){
            var matches = string.match(/^([\d\.\,\']+)$/i);
            if (matches && matches[1]) {
                var conv_price = matches[1].split(eval('/\\' + this.config['cents_separator'] + '[0-9]{0,2}/'));
                var pattern = new RegExp('[' + this.config['price_delimiter'] + ']', 'gi');
                conv_price = conv_price[0].replace(pattern, '');
                var plain_number = conv_price;
                var comma_count = Math.floor(conv_price.length/3);
                comma_count -= conv_price.length%3 == 0 ? 1 : 0;

                if (comma_count > 0) {
                    var sign, short;
                    if (comma_count == 1) {
                        sign = lang['short_price_k'];
                        short = plain_number/1000;
                    } else if (comma_count == 2) {
                        sign = lang['short_price_m'];
                        short = plain_number/1000000;
                    } else if (comma_count > 2) {
                        sign = lang['short_price_b'];
                        short = plain_number/1000000000;
                    }

                    if (sign) {
                        short = Math.round(short*10)/10;
                        short = short >= 100 ? parseInt(short) : short;
                        string = short + sign;
                    }
                }
            }

            return string;
        }

        /**
         * Get currency code by currency key
         *
         * @param string currency_key - Currency key
         * @return string             - Currency code
         */
        this.getCurrency = function(currency_key){
            var index = this.rates[currency_key][1].length > 1 ? 1 : 0;
            return this.rates[currency_key][1][index];
        }

        /**
         * Convert price
         *
         * @param double price - Price to convert
         * @return mixed       - false if rate doesn't exists and converter otherwise
         */
        this.convertPrice = function(price){
            var rate = self.isRate(price.currency);
            var dollar_rate = price.price / rate;
            var new_rate = self.isRate(this.config.currency, true);

            return rate ? dollar_rate * new_rate : false;
        }

        /**
         * Is rate exists in available range
         *
         * @param string val     - Requested value
         * @param bool keySearch - Key search mode
         * @return mixed         - false if rate doesn't exists and key otherwise
         */
        this.isRate = function(val, keySearch){
            if (!this.rates) {
                return false;
            }

            if (keySearch) {
                return this.rates[val][0];
            } else {
                for (var i in this.rates) {
                    if ($.inArray(val, this.rates[i][1]) >= 0) {
                        return this.rates[i][0];
                    }
                }
            }

            return false;
        }

        /**
         * Parse price string, obtain price as number, currency as rate key and code
         *
         * @param string str - Price string
         * @return array     - Key search mode
         */
        this.parse = function(str){
            if (!str) {
                return false;
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
                return false;
            }

            if (matches[1] || matches[3]) {
                var currency = matches[1] ? matches[1] : matches[3];
                out['currency'] = trim(currency.replace(',', ''));

                // find currency key by currency symbol/code
                for (var i in this.rates) {
                    if ( $.inArray(out['currency'], this.rates[i][1]) >= 0 ) {
                        out['key'] = i;
                        break;
                    }
                }

                str = matches[2];
            }
            else {
                return false;
            }

            // remove millesimal sign
            if (this.config.price_delimiter && this.config.price_delimiter != '') {
                var pattern = new RegExp('\\' + this.config.price_delimiter, 'gi');
                str = str.replace(pattern, '');
            }

            // replace cents separation sign to dot
            if (this.config.cents_separator && this.config.cents_separator != '.') {
                var pattern = new RegExp('\\' + this.config.cents_separator, 'gi');
                str = str.replace(pattern, '.');
            }

            str = parseFloat(str);
            out['price'] = str;

            return out;
        }

        self.init();
    }

    // default options
    $.convertPrice.defaultOptions = {
        currencyKey: false, // use custom currency instead of default
        noCurrency: false, // hide currency sign in converted price
        plainNumber: false, // show price as plain number
        showCents: null, // ignore core setting and show/hide cents manually
        shortView: false // short view of price, such as 2.2k
    };

    $.fn.convertPrice = function(options){
        return this.each(function(){
            if (typeof this.convertPriceInited == 'undefined') {
                var item = new $.convertPrice(this, options);
                window.currencyConvertorElements.push(item);
                this.convertPriceInited = true;
            }
        });
    };
}(jQuery));

(function(){
    "use strict";

    window.currencyConvertorElements = new Array();

    var $container = $('#currency_selector');
    var $items = $container.find('li');
    var $scroll_area = $container.find('span.content > div');
    var $indicator = $container.find('> .default');
    var $indicator_sign = $indicator.find('> span');

    // currency dropdown handler
    $indicator.click(function(){
        $('span.circle_opened').not($container).removeClass('circle_opened');

        if ($(this).parent().hasClass('circle')) {
            $(this).parent().toggleClass('circle_opened');
        } else {
            $(this).next().toggle();
        }
    });

    $scroll_area.mCustomScrollbar();

    $(document).bind('click touchstart', function(event){
        if (!$(event.target).parents().hasClass('circle_opened')) {
            $('#currency_selector').removeClass('circle_opened');
        }
    });

    // currency change listener
    $items.click(function(){
        /* Craigslist cloning DOM fallback */
        // Should be ised the same vars without "_ref" postfix
        var $items_ref = $(this).parent().find('li');
        var $indicator_ref = $(this).closest('#currency_selector').find('> .default');
        var $indicator_sign_ref = $indicator_ref.find('> span');
        /* Craigslist cloning DOM fallback end */

        $items_ref.filter('.active').removeClass('active');
        $items_ref.find('> a.active').removeClass('active');
        $(this).addClass('active');
        $(this).find('> a').addClass('active');

        var code = $(this).data('code');
        createCookie('curConv_code', code, 31);

        currencyConverter.config['currency'] = code;

        if (currencyConverter.rates[code][1][1]) {
            var sign = currencyConverter.rates[code][1][1];
            $indicator_sign_ref.attr('class', 'symbol');
        } else {
            var sign = currencyConverter.rates[code][1][0];
            $indicator_sign_ref.attr('class', 'code');
        }

        $indicator_sign_ref.text(sign);
        $indicator.trigger('click');

        for (var i in window.currencyConvertorElements) {
            window.currencyConvertorElements[i].init();
        }
    });

    // Default conversions
    $('.price-tag').convertPrice();
    $('.price_tag > *:not(nav)').each(function(){
        if (!$(this).parents().hasClass('featured_gallery')) {
            $(this).convertPrice();
        }
    });

    // Featured Gallery conversion by timeout
    setTimeout(function(){
        $('.featured_gallery').find('.price_tag > span:not(.icon)')
            .convertPrice();
    }, 10);

    // Escort package conversion
    $('.escort-rates-chart > li > div:last-child').convertPrice();

    // Previous version usage fallback
    currencyConverter.convertFeatured = function(key){
        if (!key) {
            return;
        }

        $('#carousel_' + key)
            .find('.price_tag.two-inline > *')
            .convertPrice();
    };

    currencyConverter.featuredGallery = function(){
        $('div.featured_gallery div.fg-price').convertPrice();
    }

    var converter = new $.convertPrice();
    converter.setData();
    currencyConverter.inRange = converter.isRate;
    currencyConverter.encodePrice = function(price, original, show_plain_number){
        return converter.prepare(price, show_plain_number, show_plain_number);
    };
    currencyConverter.convertPrice = function(curLine){
        return converter.convertPrice(curLine);
    }
    currencyConverter.decodePrice = function(str){
        return converter.parse(str);
    }
})();

// Temprorary solution to convert prices after ajax requests
if (typeof flFavoritesHandler == 'function') {
    flFavoritesHandlerBkp = flFavoritesHandler;
    flFavoritesHandlerCurrency = function(){
        this.flFavoritesHandlerBkp();

        $(function(){
            $('.price-tag').convertPrice();
        });
    }
    flFavoritesHandler = flFavoritesHandlerCurrency;
}
