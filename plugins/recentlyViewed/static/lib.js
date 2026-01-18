
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

window.xdLocalStorageInitted = false;
var xdLocalStorageInit = function (callback) {
    if (window.xdLocalStorageInitted) {
        if (callback && typeof callback === 'function') {
            callback.call();
        }

        return;
    }

    xdLocalStorage.init({
        iframeUrl: rlConfig.plugins_url + 'recentlyViewed/static/xdLocalStorage.html',
        initCallback: function () {
            window.xdLocalStorageInitted = true;

            if (callback && typeof callback === 'function') {
                callback.call();
            }
        }
    });
}

var isLocalStorageAvailable = function () {
    try {
        return 'localStorage' in window && window['localStorage'] !== null;
    } catch (e) {
        return false;
    }
}

/**
 * Adds a listing to the "Recently Viewed" list and saves it to localStorage.
 *
 * @since 1.4.0 Added parameter "isShowBox"
 *
 * @param {Array}   listing   - An array with the listing ID and title
 * @param {Boolean} isShowBox - Whether to show the "Recently Viewed" box
 */
var rvAddListing = function(listing, isShowBox) {
    rvGetListings(function (rv_listings) {
        let new_rv_listings = [];

        if (rv_listings) {
            for (let i = 0; i < rv_listings.length; i++) {
                if (rv_listings[i][0] != listing[0]) {
                    new_rv_listings.splice(i, 0, rv_listings[i]);
                }
            }
        }

        new_rv_listings.splice(0, 0, listing);

        if (new_rv_listings.length > rv_total_count) {
            let difference = (new_rv_listings.length - rv_total_count);
            new_rv_listings.splice(rv_total_count, difference);
        }

        try {
            xdLocalStorage.setItem('rv_listings_' + storage_item_name, JSON.stringify(new_rv_listings), function () {
                if (isShowBox) {
                    loadRvListingsToBlock();
                }
            });
        } catch (e) {
            if (e === QUOTA_EXCEEDED_ERR) {
                console.log('Error. Web storage is full');
            }
        }
    });
}

var rvRemoveListing = function(listing_id) {
    rvGetListings(function (rv_listings) {
        let new_rv_listings = [];

        if (rv_listings) {
            for (var i = 0; i < rv_listings.length; i++) {
                if (rv_listings[i][0] != listing_id) {
                    new_rv_listings.splice(i, 0, rv_listings[i]);
                }
            }
        }

        try {
            xdLocalStorage.setItem('rv_listings_' + storage_item_name, JSON.stringify(new_rv_listings));
        } catch (e) {
            if (e === QUOTA_EXCEEDED_ERR) {
                console.log('Error. Web storage is full');
            }
        }
    });
}

var rvGetListings = function (callback) {
    xdLocalStorage.getItem('rv_listings_' + storage_item_name, function (data) {
        if (callback && typeof callback === 'function') {
            callback.call(null, data.value ? JSON.parse(data.value) : []);
        }
    });
}

var rvRemoveListings = function() {
    xdLocalStorage.removeItem('rv_listings_' + storage_item_name);
}

var addTriggerToIcons = function() {
    $('#listings .fieldset').addClass('hide');

    $('.rv_remove span').each(function(){
        $(this).flModal({
            caption: notice,
            content: lang.rv_del_listing_notice,
            prompt: 'ajaxRemoveRvListing(' + $(this).parent().attr('id').split('_')[1] + ')',
            width: 'auto',
            height: 'auto'
        });
    });

    $('.rv_del_listings').each(function(){
        $(this).flModal({
            caption: notice,
            content: lang.rv_del_listings_notice,
            prompt: 'ajaxRemoveAllRvListings()',
            width: 'auto',
            height: 'auto'
        });
    });

    flFavoritesHandler();
}

var syncListings = function() {
    if (!isLogin) {
        return;
    }

    rvGetListings(function (rv_storage) {
        var rv_ids = getListingIDs(rv_storage);

        $.post(rlConfig['ajax_url'], {mode: 'rvSyncListings', item: rv_ids, lang: rlLang},
            function(response){
                if (response && (response.status || response.message)) {
                    if (response.status === 'OK' && response.data) {
                        xdLocalStorage.setItem(
                            'rv_listings_' + storage_item_name,
                            response.data,
                            function (data) {
                                if (rlPageInfo.controller === 'rv_listings') {
                                    document.location.href = rv_history_link;
                                } else {
                                    loadRvListingsToBlock();
                                }
                            }
                        );
                    } else if (response.message) {
                        setTimeout(function(){ printMessage('error', response.message); }, 500);
                    }
                }
            },
            'json'
        );
    });
}

var getListingIDs = function(rvStorage) {
    let ids = '';

    if (rvStorage) {
        for (let i = rvStorage.length - 1; i >= 0; i--) {
            ids = ids ? rvStorage[i][0] + ',' + ids : rvStorage[i][0];
        }
    }

    return ids;
}

var bottomBarBox = function(rv_listings) {
    if (!rv_listings || rv_listings.length === 0) {
        return;
    }

    let max_count = rv_listings.length <= 12 ? rv_listings.length : 12, media_class = '';

    if (max_count >= 8) {
        media_class += ' rv-md';
    }
    if (max_count >= 10) {
        media_class += ' rv-lg';
    }
    if (max_count >= 12) {
        media_class += ' rv-xl';
    }

    let content = '<div class="col-md-12 col-sm-12"';
    content += '><section id="rv_listings" class="side_block no-header' + media_class;
    content += template_name.indexOf('escort_') === 0 ? ' rv-escort' : '';
    content += '"><div><div class="rv-container">';
    content += '<div class="rv_first">' + lang.rv_listings + '</div>';
    content += '<div class="rv_items">';

    let image_path = rlConfig.tpl_base + 'img/no-picture.png';

    for (let i = 0; i < max_count; i++) {
        let real_image_path = '';

        if (rv_listings[i][1]) {
            real_image_path = rlConfig.files_url + rv_listings[i][1];
        }

        // Build the listing path
        let listing_path = '';
        if (rv_listings[i].length === 6 && rv_listings[i][5] != '') {
            listing_path = rv_listings[i][5];
        } else {
            listing_path = rlConfig['seo_url'];
            listing_path += rlConfig['mod_rewrite']
                ? rv_listings[i][2] + '/' + rv_listings[i][3] + '-' + rv_listings[i][0] + '.html'
                : '?page=' + rv_listings[i][2] + '&amp;id=' + rv_listings[i][0];
        }

        content += '<div class="item"><a href="' + listing_path + '" target="_blank"><img ';
        content += 'accesskey="' + real_image_path + '" ';
        content += 'class="hint"';
        content += 'src="' + rlConfig['tpl_base'] + 'img/blank.gif" style="background-image: url(\'' + image_path + '\')" ';
        content += 'title="' + rv_listings[i][4] + '" alt="' + rv_listings[i][4] + '">';
        content += '</a></div>';
    }

    content += '</div>';
    content += '<div class="rv_last"><a href="' + rv_history_link + '" target="_self">';
    content += lang.rv_history_link + '</a></div>';
    content += '</div></div></section></div>';

    $('section#main_container > div.inside-container section#content').after(content);

    let tmp_style = jQuery.extend({}, qtip_style);
    tmp_style.tip = 'bottomMiddle';

    $('#rv_listings .hint').each(function(){
        $(this).qtip({
            content: $(this).attr('title') ? $(this).attr('title') : $(this).prev('div.qtip_cont').html(),
            show: 'mouseover',
            hide: 'mouseout',
            position: {
                corner: {
                    target: 'topMiddle',
                    tooltip: 'bottomMiddle'
                }
            },
            style: tmp_style
        }).attr('title', '');
    });

    // check of existing listing photos
    $('.rv_items .item img').each(function(){
        let image_url = $(this).attr('accesskey');

        if (image_url) {
            let img = new Image();
            img.src = image_url;
            img.onload = function(){
                $('.rv_items .item img[accesskey="'+ image_url +'"]')
                    .attr('style', 'background-image: url(' + image_url + ')')
                    .removeAttr('accesskey');
            };
        }
    });
}

var standardBox = function(rv_listings) {
    var ids = getListingIDs(rv_listings);

    if (ids) {
        var ids_array = ids.split(',');
        var limit = ids_array.slice(0, 5);
        ids = limit.join(',');

        var data = {
            mode: 'rvGetStandardBoxListings',
            item: ids
        };
        flUtil.ajax(data, function(response, status){
            var $box = $('section.recentlyViewed');
            var $boxBody = $box.find('.rv_listings_dom');

            if (status == 'success' && response.status == 'OK') {
                $box.addClass('rv-rendered');
                $boxBody.empty().append(response.html);
                flFavoritesHandler();
            } else {
                $boxBody.append(lang['system_error']);
            }
        });
    }
}

var loadRvListingsToBlock = function() {
    var method_name = rlConfig['rv_box_type'] == 'standard_block' ? standardBox : bottomBarBox;

    rvGetListings(method_name);
}

/**
 * Remove all viewed listings from DB and storage
 */
var ajaxRemoveAllRvListings = function() {
    if (isLogin) {
        $.post(rlConfig['ajax_url'], {mode: 'rvRemoveAllListings', lang: rlLang},
            function(response){
                if (response && (response.status || response.message)) {
                    if (response.status === 'OK' && response.data) {
                        rvRemoveListings();
                        $('#controller_area').html('<div class="info">' + lang.rv_no_listings + '</div>');
                        $('.rv_del_listings').remove();

                        setTimeout(function(){ printMessage('notice', response.data); }, 500);
                    } else if (response.message) {
                        setTimeout(function(){ printMessage('error', response.message); }, 500);
                    }
                }
            },
            'json'
        );
    } else {
        rvRemoveListings();
        $('#controller_area').html('<div class="info">' + lang.rv_no_listings + '</div>');
        setTimeout(function(){ printMessage('notice', lang['rv_del_listings_success']); }, 500);
    }
}

/**
 * Remove selected listing
 *
 * @param {int} id
 */
var ajaxRemoveRvListing = function(id) {
    rvRemoveListing(id);

    if (isLogin) {
        $.post(rlConfig['ajax_url'], {mode: 'rvRemoveListing', item: id, lang: rlLang},
            function(response){
                if (response && (response.status || response.message)) {
                    if (response.status === 'OK' && response.data) {
                        setTimeout(function(){ printMessage('notice', response.data); }, 500);
                    } else if (response.message) {
                        setTimeout(function(){ printMessage('error', response.message); }, 500);
                    }
                }
            },
            'json'
        );
    } else {
        setTimeout(function(){ printMessage('notice', lang.rv_del_listing_success); }, 500);
    }

    $('#rv_' + id + '.rv_remove').closest('article').remove();

    rvGetListings(function (storage) {
        // Redirect to first page if all listings have been removed in current page
        if ((storage.length === 0 || $('#listings article').length === 0) && rv_history_link) {
            document.location.href = rv_history_link;
        }
    })


}

/**
 * Load listings to page (for not logged users only)
 */
var ajaxLoadRvListings = function(){
    if (isLogin) {
        return;
    }

    rvGetListings(function (rv_storage) {
        let rv_ids = '';
        if (rv_storage) {
            for (let i = rv_storage.length - 1; i >= 0; i--) {
                rv_ids = rv_ids ? rv_storage[i][0] + ',' + rv_ids : rv_storage[i][0];
            }
        }

        let $dataContainer = $('#controller_area');

        if (rv_ids) {
            $.post(
                rlConfig['ajax_url'],
                {
                    mode : 'rvLoadListings',
                    item : {
                        ids     : rv_ids,
                        pg      : rv_pg,
                        storage : rv_storage
                    },
                    lang : rlLang
                },
                function(response){
                    if (response && (response.status || response.message)) {
                        if (response.status === 'OK' && response.data) {
                            // update data of listings in storage
                            if (response.data.storage && response.data.listings) {
                                xdLocalStorage.setItem(
                                    'rv_listings_' + storage_item_name,
                                    response.data.storage,
                                    function (data) { /* callback */ }
                                );
                            } else if (!response.data.storage && !response.data.listings) {
                                rvRemoveListings();
                            }

                            if (response.data.listings) {
                                $dataContainer.html(response.data.listings);
                                addTriggerToIcons();
                            } else {
                                $dataContainer.html('<div class="info">' + lang.rv_no_listings + '</div>');
                            }
                        } else if (response.message) {
                            setTimeout(function(){ printMessage('error', response.message); }, 500);
                        }
                    }
                },
                'json'
            );
        } else {
            $dataContainer.html('<div class="info">' + lang.rv_no_listings + '</div>');
        }
    });
}
