(function($) {
    $.carousel = function(element, options){
        var self = this;
        this.options = $.extend({}, $.carousel.defaultOptions, options);

        // create function
        this.create = function() {
            var self = this;
            var opts = this.options;
            this._elements();
            var elems = this.elements;
            const options = {
                center: false,
                infinite: opts.circular,
                dragFree: 0,
                slidesPerPage: opts.scroll,
                preload: 0,
                Dots: false,
                Autoplay: opts.auto > 0 ? {timeout: opts.auto} : false ,
                classNames: {
                    slide: "carousel__slide_item",
                },
                classes: {
                    slide: "f-carousel__slide_item",
                },
                on: {
                    refresh: (carousel) => {
                        self.options.li_classes = $(carousel.container).find('ul.'+opts.prefix+'carousel__track > li').first().attr("class");
                        var $track = $(carousel.container).find('ul.'+opts.prefix+'.carousel__track');
                        self.rebuildVisible();

                        if (media_query == 'mobile') {
                            carousel.options.slidesPerPage = 1;
                        }
                        else {
                            if (self.options.count <= 5 && self.options.scroll > 2) {
                                carousel.options.slidesPerPage = 2;
                            }
                            else {
                                carousel.options.slidesPerPage = self.options.scroll;
                            }
                        }

                        if (!$track.hasClass('featured')) {
                            $track.addClass('featured clearfix with-pictures');
                        }
                    },
                    "Panzoom.beforeTransform": (carousel) => {
                        var self = this;
                        if (self.elements.count != self.elements.ul.children('li').size()) {
                            self.afterLoadAjax(self.options.box_key);
                            self._elements();
                        }
                    },
                }
            }

            if ( rlCarousel[elems.id] > 0 ) {
                var data = {
                    mode: 'listing_carousel',
                    item: 'listing_carousel',
                    lang: rlLang,
                    limit: rlCarousel[elems.id],
                    box_key: opts.box_key,
                    options: opts.options,
                    number: rlCarousel[elems.id],
                    price_tag: opts.priceTag,
                    side_bar_exists: opts.side_bar_exists,
                    page_key: rlPageInfo['key']
                }
                flUtil.ajax(data, function(response, status) {
                    if (status === 'success' && response.status === 'ok') {
                        rlCarousel[elems.id] = 0;

                        if (response.results) {
                            for(var i=0; i < response.results.length; i++) {
                                var item = response.results[i];
                                var $item = $(item);
                                $(element).find('ul.'+opts.prefix+'carousel__track').append($item.addClass(opts.prefix+'carousel__slide_item'));
                            }
                            self.afterLoadAjax(self.options.box_key);
                        }
                    }
                    self._callCarousel(element, options);
                }, true);
            }
            else {
                self._callCarousel(element, options);
            }
        }

        self._callCarousel = function(element, options) {
            var gCarousel = new Carousel($(element).get(0), options, { Autoplay });
        }

        self._elements = function () {
            var elems = this.elements = {};

            elems.curr =  0;
            elems.interval = 0;
            elems.running = false;
            elems.id = $(element).attr('id');
            elems.ul = $(element).find('ul.'+this.options.prefix+'carousel__track');
            elems.li_first = elems.ul.children('li').eq(0);
            elems.items = elems.ul.children('li');
            elems.count = elems.items.length;

            elems.items.addClass(this.options.prefix+'carousel__slide_item');

            return;
        }

        self.rebuildVisible = function() {
            var item_class = $(element).find("ul > li").attr("class").split(" ");
            var elems = this.elements;
            var bootstr = self.parseBootrstrap(item_class);

            if ( media_query == 'desktop' && large_desktop && bootstr['lg'] ) {
                val = bootstr['lg'];
            }
            else if ( media_query == 'desktop' && bootstr['md'] ) {
                val = bootstr['md'];
            }
            else if (( media_query == 'desktop' || media_query == 'tablet') && bootstr['sm'] ) {
                val = bootstr['sm'];
            }
            else if ( media_query == 'mobile' ) {
                if ( bootstr['xs'] ) {
                    val = bootstr['xs'];
                }
                else {
                    val = 12;
                }
            }
            var liSize = 100 / ( 12 / val );
            self.options.visible = 100 / liSize;
        }

        self.parseBootrstrap = function(val) {
            var a = new Array();
            for(var i=0; i < val.length; i++) {
                col = val[i].split("-");
                if(col[0]=='col') {
                    a[col[1]] = col[2];
                }
            }

            return a;
        }

        self.afterLoadAjax  = function( block_key) {
            var self = this, elems = this.elements, opts = this.options;

            if ( block_key && typeof(currencyConverter) !== 'undefined' ) {
                self.currencyPluginLoad(block_key);

            }

            flFavoritesHandler();

            if ( typeof caroselCallback == 'function' ) {
               caroselCallback();
            }

        }

        self.currencyPluginLoad  = function( block_key ) {
            if ( typeof($.convertPrice) == 'function' )
            {
                $('#carousel_' + block_key +' .price_tag > *:not(nav)').convertPrice();
            }
            else {
                currencyConverter.convertFeatured(block_key);
            }
        }

        self.create();
    }

    $.carousel.defaultOptions = {
        loading: false,
        auto: null,
        speed: 1000,
        speedSec: 1,
        direction: 'ltr',
        circular: false,
        visible: 4,
        start: 0,
        scroll: 1,
        number: 0,
        count: 0
    };

    $.fn.carousel = function(options){
        return this.each(function(){
            (new $.carousel(this, options));
        });
    };
}(jQuery));
