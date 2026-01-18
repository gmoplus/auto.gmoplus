
/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.8.0
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: LIB.JS
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

var weatherForecastClass = function() {
    var self = this;

    this.init = function() {
        // update website box
        if (typeof weatherForecastBox == 'object') {
            self.update(weatherForecastBox);
        }

        // update website listing widget
        if (typeof weatherForecastListing == 'object') {
            self.update(weatherForecastListing);
            self.place(weatherForecastListing);
        }
    }

    this.update = function(data) {
        if (data.cached && data.now < data.updateTime) {
            return;
        }

        var date = new Date();

        if (data.mode && data.location) {
            var cache_id = false;

            if (data.cachePeriod) {
                cache_id = 'weather-forecast-';
                cache_id += data.mode == 'id' ? data.location : 'geo';
            }

            // Get data from the cache
            if (cache_id && localStorage.getItem(cache_id) && date.getTime() < parseInt(localStorage.getItem(cache_id + '-updateTime'))) {
                setTimeout(function(){
                    self.draw(data, JSON.parse(localStorage.getItem(cache_id)));
                }, 1);
            }
            // No cache mode
            else {
                var request = {
                    item: 'weatherForecast',
                    mode: data.mode,
                    location: data.location,
                    cache: data.cached
                };
                $.post(rlConfig['ajax_url'], request, function(response){
                    if (response.status == 'OK') {
                        self.draw(data, response.results);

                        // save data
                        if (cache_id) {
                            if (typeof(Storage) !== 'undefined') {
                                localStorage.setItem(cache_id, JSON.stringify(response.results));
                                var update_time = date.getTime() + (data.cachePeriod * 3600 * 1000);
                                localStorage.setItem(cache_id + '-updateTime', update_time);
                            }
                        }
                    } else {
                        var $container = $('.weather-widget.' + data.target);
                        var city_name = $container.find('.location').text();

                        $container.find('.forecast').text(lang['weatherForecast_no_wf'].replace('{location}', city_name));
                        $container.find('.cond').text(lang['error']);
                    }
                }, 'json');
            }
        } else {
            printMessage('error', 'Wrong weather settings, please contact Flynax Support');
        }
    }

    this.draw = function(data, results) {
        // adapt data
        this.adapt(results.forecast);

        // update current condition
        var container = $('.weather-widget.' + data.target);
        var cur_cond = container.find('div.current-cond');
        cur_cond.find('.weather-icon').attr('class', 'weather-icon w'+results.forecast[0].icon);
        cur_cond.find('.temp').html(results.forecast[0].temp);
        cur_cond.find('.cond').html(results.forecast[0].name);

        // update forecast
        var forecast = container.find('ul.forecast');
        forecast.empty();
        $('#weather-forecast').tmpl(results.forecast.slice(1, results.forecast.length)).appendTo(forecast);

        // show link
        if (results.city_id) {
            container.find('.weather-cp a').attr('href', container.find('.weather-cp a').attr('href').replace(/[0-9]+$/g, results.city_id));
            container.find('.weather-cp').slideDown();
        }
    }

    this.adapt = function(forecast) {
        for (var i = 0; i < forecast.length; i++) {
            forecast[i]['name'] = weatherForecast_conditions[forecast[i].icon_id]
            forecast[i]['week_day_short'] = $.datepicker.regional[weatherForecast_lang].dayNamesShort[forecast[i]['week_day']]
        }
    }

    this.place = function(data) {
        var container = $('#weather-listing-cont');
        var target = $('#area_listing');

        if (target.find('> div:first').hasClass('row')) {
            target = target.find('> div.row > div:last > .content-section');
        } else if (target.find('> div').hasClass('content-padding')) {
            target = target.find('> .content-padding');
        } else if (target.find('> .listing-fields').length) {
            target = target.find('> .listing-fields');
        }

        if (data.position == 'bottom') {
            target.find('> div:last').after(container);
        }
        else if (data.position == 'in_group') {
            var target_group = target.find('> div.' + data.position_group + ' div.body');

            if (data.position_in_group == 'prepend') {
                target_group.prepend(container);
            } else {
                target_group.append(container);
            }
        }

        container.show();
    }
}

var weatherForecast = new weatherForecastClass();
$(function() {
    weatherForecast.init();
});
