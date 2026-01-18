
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
 *	Copyrights Flynax Classifieds Software | 2023
 *	https://www.flynax.com
 *
 ******************************************************************************/

var prev_account = false;

$(function(){
    $('#jCodeOut').focus(function(){
        $(this).select();
        $(this).mouseup(function(){
            $(this).unbind('mouseup');
            return false;
        });
    });

    $('#Account').change(function(){
        if (prev_account != $('#Account').val()) {
            $('#ac_hidden').val('');
        }

        setTimeout(function(){
            if ($('#ac_hidden').val() && $('#ac_hidden').val() != 'false') {
                adurl['account_id'] = $('#ac_hidden').val();
                buildBox(adurl);
            } else {
                setTimeout(function(){
                    if ($('#ac_hidden').val() && $('#ac_hidden').val() != 'false') {
                        adurl['account_id'] = $('#ac_hidden').val();
                        buildBox(adurl);
                    }
                }, 500);
            }

            prev_account_id = $('#Account').val();
        }, 50);
    });

    $('#field_names_switch input[type=radio]').change(function(){
        adurl['field_names'] = $(this).val();
        buildBox(adurl);
    });

    $('.colorSelector').each(function(){
        $(this).ColorPicker({
            color: $(this).find('> div').attr('style').replace('background-color: ', ''),
            onShow: function (colpkr) {
                $(colpkr).fadeIn(500);
                return false;
            },
            onHide: function (colpkr) {
                $(colpkr).fadeOut(500);
                refreshBox();
                return false;
            },
            onChange: function (hsb, hex, rgb) {
                var cur_id = $(this).attr('id');

                $('div.colorSelector').each(function(){
                    if ($(this).data('colorpickerId') == cur_id) {
                        $(this).children('div').css('backgroundColor', '#' + hex);
                        $(this).prev().val(hex);
                    }
                });
            }
        });
    });

    $('[name=category_id]').categoryDropdown({
        listingType: '[name=listing_type]',
        phrases: {
            no_categories_available: lang['no_categories_available'],
            select: lang['select'],
            select_category: lang['select_category']
        },
        onChange: function(id){
            adurl['category_id']  = id;
            adurl['listing_type'] = $('[name=listing_type]').val();

            buildBox(adurl);
        }
    });

    $('select[name=listing_type]').change(function() {
        adurl['category_id']  = false;
        adurl['listing_type'] = $(this).val();

        $('#categories_cont')[$(this).val() == '0'
            ? 'addClass'
            : 'removeClass'
        ]('hide');

        buildBox(adurl);
    });

    $('input[name=box_view]').change(function() {
        var view = $('input[name=box_view]:checked').val();
        adurl['box_view'] = view;

        if (view == 'list') {
            adurl['per_row'] = false;
        }

        $('#per_row_section')[view == 'list'
            ? 'addClass'
            : 'removeClass'
        ]('hide');

        $('.img-size-option')[view == 'list'
            ? 'removeClass'
            : 'addClass'
        ]('hide');

        buildBox(adurl);
    });

    $('input[name=per_page]').change(function() {
        if ($(this).val()) {
            adurl['per_page'] = $(this).val();
        }

        buildBox(adurl);
    });

    $('[name=per_row]').change(function() {
        adurl['per_row'] = $(this).val();
        buildBox(adurl);
    });

    $('input[name=limit]').change(function() {
        if ($(this).val()) {
            adurl['limit'] = $(this).val();
        }

        buildBox(adurl);
    });

    $('#jParams input[type=text]').not('#Account').change(function() {
        refreshBox();
    });

    $('#jParams select').change(function() {
        refreshBox();
    });
});

var buildBox = function(adurl) {
    if (!adurl['listing_type']
        && $('select[name=listing_type]').val()
        && $('select[name=listing_type]').val() != '0'
    ) {
        adurl['listing_type'] = $('select[name=listing_type]').val();
    }

    aurl = '';
    for (var x in adurl) {
        if (adurl[x] && typeof(adurl[x]) != 'function') {
            aurl += '&' + x + "=" + adurl[x];
        }
    }

    // Save box ID to session before updating of box
    // To prevent generating the cached version of box content
    if (rlConfig && rlConfig.ajax_url) {
        $.post(
            rlConfig.ajax_url,
            {mode: 'raSaveBoxIDInSession', item: 'raSaveBoxIDInSession', id: boxID},
            function(response) {
                if (response && response.status && response.status == 'OK') {
                    $.getScript(url + aurl, function(data, textStatus, jqxhr) {
                        refreshBox();
                    });
                }
            },
            'json'
        );
    }
};

var refreshBox = function() {
    var params, jconf, value, colorPkrFields, sizeFields;

    params         = [];
    jconf          = [];
    value          = false;
    colorPkrFields = [
        'conf_advert_bg',
        'conf_advert_border_color',
        'conf_image_bg',
        'conf_field_first_color',
        'conf_field_color',
        'conf_price_field_color',
    ];
    sizeFields     = ['conf_img_width', 'conf_img_height', 'conf_border_radius'];

    var box_mode = $('input[name=box_view]:checked').val();

    $('#jParams input').each(function() {
        var name = $(this).attr('name');
        var abbr = $(this).attr('abbr');

        if (['text', 'hidden'].indexOf($(this).attr('type')) >= 0
            && $(this).val()
            && typeof abbr != 'undefined'
            && !(box_mode == 'grid' && ['conf_img_width', 'conf_img_height'].indexOf(name) >= 0)
        ) {
            if ($.inArray(name, colorPkrFields) >= 0) {
                value = '#' + $(this).val();
            } else if ($.inArray(name, sizeFields) >= 0) {
                value = $(this).val() + 'px';
            } else {
                value = $(this).val();
            }

            params = abbr.split('|');
            setStyleByClass(params[0], params[1], params[2], value);

            switch (params[1]) {
                case 'jListingField_value':
                    setStyleByClass(params[0], 'jListingField_name', params[2], value);
                    break;
                case 'jListingItem':
                    if (['background', 'borderColor', 'borderRadius'].indexOf(params[2]) >= 0) {
                        setStyleByClass('span', 'jListingPageItem', params[2], value);
                        setStyleByClass('span', 'jListingPageItem-active', params[2], value);
                    }
                    break;
            }

            jconf[name] = value;
        }
    });

    refreshCode(jconf);
};

var refreshCode = function(jconf) {
    // Refresh unique ID of box
    boxID = 'ra' + Math.floor(Math.random() * 2147483648);
    iout  = iout.replace(/(ra[0-9]+)/gm, boxID);
    acurl = acurl.replace(/(ra[0-9]+)/gm, boxID);

    var jconfOut = '', styles = '', out = '', jParams = '';

    if (jconf) {
        for (var x in jconf) {
            if (typeof(jconf[x]) != 'function') {
                styles += '\r\n\t' + x + ": '" + jconf[x] + "',";
            }
        }

        if (styles) {
            jconfOut = 'if (typeof raDataStyles == \'undefined\') { var raDataStyles = {}; }\r\n';
            jconfOut += 'raDataStyles.' + boxID + ' = {' + styles + '\r\n};\r\n';
        }
    }

    if (jconfOut) {
        out = '<script type="text/javascript">\r\n';
        out += jconfOut;
        out += "<\/script>\r\n";
    }

    out += '';
    out += iout.replace('[aurl]', acurl + aurl);

    $('#jCodeOut').val(out);
};
