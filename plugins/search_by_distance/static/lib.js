
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
 *	Copyrights Flynax Classifieds Software | 2022
 *	https://www.flynax.com
 *
 ******************************************************************************/

var sbdLocationAutocomplete = function(selector, field_name){
    var $elem = $(selector);

    if ($elem.length == 0) {
        return;
    }

    var $lat = $('input[name=block_lat]');
    var $lng = $('input[name=block_lng]');

    if (field_name) {
        $lat = $('input[name="f['+field_name+'][lat]"]');
        $lng = $('input[name="f['+field_name+'][lng]"]');
    }

    flUtil.loadStyle(rlConfig['tpl_base'] + 'components/geo-autocomplete/geo-autocomplete.css');

    flUtil.loadScript(rlConfig['libs_url'] + 'maps/geoAutocomplete.js', function(){
        $elem.geoAutocomplete({
            onSelect: function(name, lat, lng){
                $lat.val(lat);
                $lng.val(lng);
            }
        });
    });

    var getCountryKeyByCode = function(code){
        if (!Object.keys(sbdConfig.countryISO).length) {
            return null;
        }

        for (var i in sbdConfig.countryISO) {
            if (sbdConfig.countryISO[i].toLowerCase() == code.toLowerCase()) {
                return i;
            }
        }

        return null;
    }

    var setRestriction = function($field){
        if (typeof $field == 'object') {
            var $option = $field.find('option:selected');
            var value   = $field.val();
            var code = value === '0' || !value ? '' : value;
            var country    = $option.data('key') ? $option.data('key') : sbdConfig.countryISO[code];
        } else {
            var country = getCountryKeyByCode($field);
            var code    = $field;
        }

        if (!code || !country) {
            return;
        }

        $elem.attr({
            'data-filter-country': country.replace('_', ' '),
            'data-filter-country-code': code
        });
    }

    if (typeof sbdConfig != 'object') {
        return;
    }

    if (sbdConfig.countryFieldKey) {
        // Define country field
        var $country_field = $('.sbd-box select[name=block_country]');

        if (selector != 'input#block_location_search') {
            $country_field = $(selector).closest('form').find('select[name="f['+ sbdConfig.countryFieldKey +']"]');
        }

        // Set on country change listener
        $country_field.change(function(){
            setRestriction($(this));
        });

        // Set default country restriction
        if ($country_field.length) {
            setRestriction($country_field);
        }
    } else if (sbdConfig.defaultCountry) {
        setRestriction(sbdConfig.defaultCountry);
    }
}
