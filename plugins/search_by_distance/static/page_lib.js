
/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : PAGE_LIB.JS
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

var searchByDistanceClass = function(config){
    var self = this;

    this.listingType;
    this.formData = [];
    this.lat = 37.7648179; // set fallback location: San Francisco, CA
    this.lng = -122.4462026;
    this.zip;
    this.lastZip;
    this.map;
    this.mapClass;
    this.zoom = 10;
    this.distanceInput = $('select[name=block_distance]');
    this.zipInput = $('input[name=block_zip]');
    this.countryInput = $('select[name=block_country]');
    this.submitInput = $('.sbd-box input[type=submit]');
    this.htmlCont = $('#sbd_dom');
    this.circle;
    this.circleCenter = '';
    this.distance = 0;
    this.$label = null;
    this.timer = false;
    this.markers = [];
    this.lastQueryCount = 0;
    this.page = 1;
    this.sortingField = 'date';
    this.sortingType = 'desc';

    this.init = function(){
        flMap.init($('#sbd_map'), {
            center: [this.lat, this.lng],
            zoom: this.zoom,
            markerCluster: {
                groupCount: true
            },
            interactive: true,
            minimizePrice: {
                centSeparator: rlConfig['price_separator'],
                priceDelimiter: rlConfig['price_delimiter'],
                kPhrase: lang['short_price_k'],
                mPhrase: lang['short_price_m'],
                bPhrase: lang['short_price_b']
            },
            userLocation: true,
            idle: function(map){
                self.map = map;
                self.mapClass = this;

                self.set();
                self.defineLocation();
                self.setListeners();
            }
        });
    }

    this.set = function(){
        this.lastZip = this.zip;
        this.zip = this.zipInput.val();
        this.distance = this.distanceInput.val() * 1000;
        this.distance = config.unit == 'miles' ? this.distance * 1.609344 : this.distance;

        this.listingType = $('select[name=search_type]').val();
        this.formData = $('#form_' + this.listingType).find('select,input[type=radio]:checked,input[type=checkbox]:checked,input[type=text],input[type=number],input[type=hidden]').serializeArray();
    }

    this.defineLocation = function(){
        if ($('input[name=block_lat]').val() && $('input[name=block_lng]').val()) {
            this.lat = $('input[name=block_lat]').val();
            this.lng = $('input[name=block_lng]').val();
            this.setLocation();
        } else {
            this.submitInput.val(lang['loading']);

            var country_code = this.countryInput.val() || config.defaultCountryCode;
            var country_name = this.countryInput.find('> option:selected').text() || config.defaultCountry;

            if (this.zip && country_code) {
                var data = {
                    country: country_code,
                    postalcode: this.zip
                };

                geocoder(data, function(results, status){
                    if (status == 'success') {
                        if (results.status == 'OK') {
                            self.lat = results.results[0].lat;
                            self.lng = results.results[0].lng;
                        } else {
                            printMessage('error', config.lang.locationNotFound);
                        }
                    } else {
                        printMessage('error', lang['system_error']);
                    }

                    self.setLocation();
                });
            } else {
                var location = config.fromPost && country_name ? country_name : config.geoLocation;
                if (location) {
                    geocoder({query: location}, function(results, status){
                        if (status == 'success') {
                            if (results.status == 'OK') {
                                self.lat = results.results[0].lat;
                                self.lng = results.results[0].lng;
                            } else {
                                printMessage('error', config.lang.locationNotFound);
                            }
                        } else {
                            printMessage('error', lang['system_error']);
                        }
                        self.setLocation();
                    });
                } else {
                    printMessage('error', config.lang.locationNotFound);
                    this.setLocation();
                }
            }
        }
    }

    this.setLocation = function(){
        var latLng = new L.LatLng(this.lat, this.lng);
        self.map.panTo(latLng);
        
        this.setCircle(false, latLng);
        this.setZoom();

        this.submitInput.val(this.submitInput.attr('accesskey'));
    }

    /**
     * @since 5.1.0 - The second param latLng added
     */
    this.setCircle = function(preventCenterChange, latLng){
        if (!this.circle) {
            this.circle = L.circle(this.map.getCenter(), {
                radius: this.distance,
                color: '#db8a33',
                weight: 2,
                opacity: .8,
                fillColor: '#ff9e36',
                draggable: true,
                editing: {},
                original: {}
            }).addTo(this.map);

            this.circle.editing.enable();
            this.circleCenter = this.circle.getBounds().getCenter().toString();

            this.map.on('touchend', function(e){
                // Resizeend listener
                if (self.distance != self.circle.getRadius()) {
                    self.distance = self.circle.getRadius();
                    self.onCircleResize();
                }

                // Moveend listener
                var center = self.circle.getBounds().getCenter();
                if (center.toString() !== self.circleCenter) {
                    self.circleCenter = center.toString();
                    self.lat = center.lat;
                    self.lng = center.lng;

                    self.onCircleMove();
                }
            });

            this.setLabel();
            this.getMarkers();
        } else {
            this.circle.editing.disable();

            if (!preventCenterChange) {
                var mapCenter = latLng ? latLng : this.map.getCenter();
                this.circle.setLatLng(mapCenter);
            }

            this.circle.setRadius(this.distance);
            this.circle.editing.enable();

            // Redraw label
            this.$label = null;
            this.setLabel();

            var circleCenter = this.circle.getBounds().getCenter().toString();

            if (circleCenter != this.circleCenter) {
                this.circleCenter = circleCenter;
                this.getMarkers();
            }
        }
    }

    this.onCircleResize = function(){
        this.search();
        this.setLabel();
    }

    this.onCircleMove = function(){
        this.search();
    }

    this.setLabel = function(){
        // Calculate radius and get position
        var radius = this.distance / 1000;
        var unit = config.lang.kmShort;

        if (config.unit == 'miles')  {
            radius /= 1.609344;
            unit = config.lang.miShort;
        }

        radius = Math.round(radius*10)/10;
        radius += ' '+unit;
        
        if (this.$label) {
            this.$label.text(radius);
        } else {
            var $el = $('.leaflet-editing-icon.leaflet-edit-resize');

            if (!$el.length) {
                console.log('ERROR: Search by distance, no point to append distance label found, please contact Flynax Support.')
                return;
            }

            // Create and append label
            this.$label = $('<div>')
                            .addClass('sbd_label')
                            .text(radius);

            $el.append(this.$label);
        }
    }

    this.setZoom = function(){
        this.map.fitBounds(this.circle.getBounds());
    }

    this.search = function(){
        clearTimeout(this.timer);

        this.page = 1;

        this.timer = setTimeout(function(){
            self.getMarkers()
        }, 1000);
    }

    this.getMarkers = function(){
        this.loading();

        var data = {
            mode: 'sbdSearch',
            ajaxKey: 'searchByDistance',
            lang: rlLang,
            lat: this.lat,
            lng: this.lng,
            distance: this.circle.getRadius(),
            type: this.listingType,
            form: this.formData,
            sortingField: this.sortingField,
            sortingType: this.sortingType,
            sidebar: $('body').hasClass('no-sidebar') ? false : true
        };

        flUtil.ajax(data, function(results, status){
            if (status == 'success') {
                self.lastQueryCount = 0;

                if (results.status == 'ok') {
                    var listingIDs = [];

                    for (var i in results.listings) {
                        results.listings[i].preview = {
                            id: results.listings[i].ID,
                            onClick: function(){
                                self.preventCall = true;
                            }
                        }
                        results.listings[i].label = results.listings[i].price;

                        listingIDs.push(parseInt(results.listings[i].ID));
                    }

                    self.mapClass.addMarkers(results.listings);
                    self.mapClass.removeAllMarkersExcept(listingIDs);

                    self.htmlCont.html(results.html);
                    self.lastQueryCount = results.count;
                    self.addJSSupport();

                    // show limit message
                    if (results.count > config.limit) {
                        printMessage('warning', config.lang.limitExceeded);
                    }
                } else {
                    self.htmlCont.html(results.message);
                    self.mapClass.removeAllMarkers();
                    self.markers = [];
                }
            } else {
                printMessage('error', lang['system_error']);
            }

            self.loading(true);
        });
    }

    this.getListings = function(){
        this.loading();

        var data = {
            mode: 'sbdSearch',
            ajaxKey: 'searchByDistance',
            lang: rlLang,
            lat: this.lat,
            lng: this.lng,
            distance: this.circle.getRadius(),
            page: this.page,
            type: this.listingType,
            form: this.formData,
            sortingField: this.sortingField,
            sortingType: this.sortingType,
            sidebar: $('body').hasClass('no-sidebar') ? false : true
        };

        flUtil.ajax(data, function(results, status){
            if (status == 'success') {
                if (results.status == 'ok') {
                    self.htmlCont.html(results.html);
                    self.addJSSupport();
                } else {
                    self.htmlCont.html(results.message);
                }
            } else {
                printMessage('error', lang['system_error']);
                self.page = parseInt($('#sbd_dom ul.pagination input[type=text]').val());
            }

            self.loading(true);
        });
    }

    this.addJSSupport = function(){
        // system methods call
        self.gridView();
        flFavoritesHandler();

        // pagination click
        $('#sbd_dom ul.pagination li > a').click(function(e){
            e.stopPropagation();

            if ($(this).parent().hasClass('rs')) {
                self.page++;
            } else {
                self.page--;
            }
            
            self.scrollTop();
            self.getListings();

            return false;
        });

        // pagination jump
        $('#sbd_dom ul.pagination li input').keypress(function(e){
            if (e.keyCode == 13) {
                var jump_to = parseInt($(this).val());
                if (jump_to > 0 && jump_to <= $(this).parent().find('input[name=stats]').val().split('|')[1]) {
                    self.page = jump_to;
                    self.getListings();
                }
            }
        });

        // sorting clicks
        $('div.sorting div.current').tplToggle({
            cont: $('div.sorting ul.fields'),
            parent: 'sorting'
        });
        $('div.sorting ul.fields a').click(function(event){
            event.stopPropagation();

            if ($(this).hasClass('active')) {
                return false;
            }

            var sorting_field = $(this).attr('href').match(/sort\_by\=(.*)?\&/);
            var sorting_type = $(this).attr('href').match(/sort\_type\=(.*)/);
            self.sortingField = sorting_field[1];
            self.sortingType = sorting_type[1];

            self.getListings();

            return false;
        });

        // Feel free to add plugin handlers below
    }

    this.gridView = function(){
        var $buttons    = $('div.switcher > div.buttons > div');
        var currentView = readCookie('grid_mode');
        var $listings   = $('#listings');

        $buttons.click(function(){
            $buttons.filter('.active').removeClass('active');

            var view         = $(this).data('type');
            var currentClass = $listings.attr('class').split(' ')[0];

            createCookie('grid_mode', view, 365);

            $(this).addClass('active');

            $listings.attr('class', $listings.attr('class').replace(currentClass, view));
        });

        if (typeof listings_map_data == 'undefined' || listings_map_data.length <= 0) {
            $buttons.filter('.map').remove();

            if (currentView == 'map') {
                $buttons.filter('.list').trigger('click');
            }
        } else if (currentView == 'map') {
            $buttons.filter('.map').trigger('click');
        }
    }

    this.loading = function(stop){
        var text = stop 
        ? config.lang.adsFound.replace('{number}', this.lastQueryCount)
        : lang['loading'];

        $('h1.sbd-state').text(text);
    }

    this.setListeners = function(){
        this.submitInput.click(function(){
            self.set();

            var scroll_to_map = false;

            if (self.zip != self.lastZip) {
                self.defineLocation();
                scroll_to_map = true;
            } else {
                self.setCircle(true);
                self.setZoom();

                self.scrollTop(scroll_to_map);
                self.getMarkers();
            }
        });

        this.map.on('locationfound', function(e){
            var mapCenter = self.map.getCenter();
            self.circle.setLatLng(mapCenter);

            self.lat = mapCenter.lat;
            self.lng = mapCenter.lng;

            self.setCircle(false);
            self.setZoom();

            self.search();
        });
    }

    this.scrollTop = function(map){
        flynax.slideTo(map ? '#sbd_map' : 'h1.sbd-state');
    }

    this.init();
}

$(function(){
    if (typeof sbdPageConfig == 'object') {
        var searchByDistance = new searchByDistanceClass(sbdPageConfig);
    } else {
        console.log("Search By Distance: No js configs for plugin script found.");
    }
});
