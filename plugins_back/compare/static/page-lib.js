
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLCOMPARE.CLASS.PHP
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
 *  Flynax Classifieds Software 2024 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

var compareClass = function(){
    var self = this;

    this.options        = {};
    this.isStorage      = typeof(Storage) !== 'undefined';
    this.storageKey     = 'compare-ads';
    this.transactionEnd = 'transitionend webkitTransitionEnd oTransitionEnd';

    this.init = function(params){
        this.options = params;

        if (!params.cache) {
            this.isStorage = false;
        }

        this.fixHeight();
        this.rowHover();
        this.setListeners();
        this.fullScreen();
        this.scroll();
        this.saveResults();
        this.removeTableHandler();
        this.currencyConversion();
    }

    this.fixHeight = function(){
        var $table = $('.compare-table');

        $table.find('div.in').removeAttr('style');
        $table.find('.fields-column .item').each(function(index, item){
            var $row = $table.find('div.scroll div.table > .in:eq('+index+')')

            var name_height  = $(this).outerHeight();
            var value_height = $row.outerHeight() || 0;

            if (name_height > value_height) {
                $row.height(name_height);
            } else if (name_height < value_height) {
                $(this).css('height', value_height);
            }
        });
    }

    this.rowHover = function(){
        $table = $('.compare-table div.table');

        $table.on('mouseenter mouseleave', '.name,.in', function(e){
            var index     = $(this).parent().find('> div').index(this);
            var $target   = $table.find('> div:eq(' + index + ')');

            if (index == 0) {
                return;
            }

            var last_text = trim($target.find('.item').filter(':eq(0)').text());

            $target
                .toggleClass('hover')
                .find('.item')
                .removeClass('same')
                .each(function(index){
                    if (index == 0 || e.type != 'mouseenter') {
                        return;
                    }

                    var text = trim($(this).text());

                    if (text == last_text && text != '-') {
                        $target.filter(':eq(1)').find('.item:first').addClass('same');
                        $(this).addClass('same');

                        last_text = text;
                    }
                });
        });
    }

    this.setListeners = function(){
        // Remove from the table icon listener
        $('div.scroll .preview .remove').click(function(){
            var id = $(this).closest('.item').attr('id').split('-')[2];

            $(document).flModal({
                click: false,
                caption: lang['notice'],
                content: lang['compare_remove_notice'],
                prompt: 'compare.delayedRemove("' + id + '")',
                width: 'auto',
                height: 'auto'
            });
        });

        // Fix the cell height on the transition end
        $('div.scroll div.table > div.in:last > div').on(self.transactionEnd, function(){
            var $rows = $('div.scroll div.table > div.in');
            var index = $(this).parent().find('> div').index(this);

            $rows.find('> div:eq(' + index + ')').remove();

            if ($rows.filter(':first').find('> div').length) {
                self.fixHeight();
            } else {
                $('.compare-table').after(
                    $('#no-ads-state').removeClass('hide')
                ).remove();

                $('.compare-results-box a.button').remove();

                if (!$('#compare-saved-tables > li').length) {
                    $('#compare-data-message').removeClass('hide');
                }
            }
        });

        // flModal fix
        $('body').on('click', '.modal_block', function(){
            setTimeout(function(){
                $(window).trigger('scroll');
            }, 1);
        });
    }

    this.delayedRemove = function(id){
        setTimeout(function(){
            self.remove(id);
        }, 1);
    }

    this.remove = function(id){
        var success = true;

        // Remove from the saved tables
        if (this.options.savedTable) {
            var data = {
                mode: 'compareRemoveItem',
                itemID: id,
                tableID: this.options.savedTable
            };
            flUtil.ajax(data, function(response, status){
                if (status == 'success') {
                    if (response.errorCode == 'NOT_LOGGED_IN') {
                        location.reload();
                    } else {
                        success = false;
                        console.log('Compare Ads plugin: Unable to remove item from the table by ajax call (compareRemoveItem)');
                    }
                } else {
                    success = false;
                    console.log('Compare Ads plugin: ajax call (compareRemoveItem) failed');
                }
            });
        }
        // Remove from the tmp cookie table
        else {
            var cookie = readCookie('compare_listings');
            var ids    = cookie ? cookie.split(',') : [];

            // Remove from storage
            ids.splice(ids.indexOf(id), 1);
            this.removeFromStorage(id);
            createCookie('compare_listings', ids.join(','), 93);
        }

        if (!success) {
            printMessage('error', lang['system_error']);
            return;
        }

        // Remove listing from the DOM table
        var $icon = $('#compare-item-' + id);
        var index = $icon.closest('.in').find('> div').index($icon);
        $('div.scroll div.table > div.in')
            .find('> div:eq(' + index + ')')
            .addClass('removing');
    }

    this.fullScreen = function(){
        // Switch to fullscreen view
        $('.compare-fullscreen').click(function(){
            $('body > *:visible').addClass('tmp-hidden');
            $('body').append($('#compare-fullscreen-view').render());

            $area = $('body > div.compare-fullscreen-area');
            $area.find('> .compare-body').append($('.compare-table').parent());

            setTimeout(function(){
                $area.find('> .compare-body').removeClass('table-hidden');
            }, 1);

            self.fixHeight();
        });

        // Switch to default view
        $('body').on('click', '.compare-default', function(){
            $('body > div.compare-fullscreen-area .compare-body')
                .addClass('table-hidden')
                .on(self.transactionEnd, function(){
                    $('#controller_area').prepend($(this).find('> .highlight'));
                    $('body > *.tmp-hidden').removeClass('tmp-hidden');

                    $(this).parent().remove();
                    self.fixHeight();
                });
        });
    }

    this.scroll = function(){
        var top_reached    = false;
        var bottom_reached = false;
        var bg             = $('body').css('backgroundColor');
        var bg_default     = 'rgba(0, 0, 0, 0)';
        var sticky_height  = 8; // 8px is missing top padding of the first row
        var $clone         = null;

        // Fix bg
        if (bg == bg_default) {
            var bg_alt = $('.main-wrapper').css('backgroundColor');
            bg = bg_alt == bg_default ? 'white' : bg_alt;
        }

        // Get sticky rows height
        $('.scroll div.table > .in.sticky').each(function(){
            sticky_height += $(this).outerHeight(true);
        });

        // Vertical windlow scroll listener
        $(window).on('touchmove scroll', function(event){
            if (media_query != 'mobile') {
                return;
            }

            var $target       = $('.scroll div.table > .in');
            var scroll_top    = $(this).scrollTop();
            var top_offset    = $target.filter(':eq(0)').offset().top;
            var bottom_offset = top_offset + $('.fields-content').height() - sticky_height;

            if (scroll_top >= top_offset && !top_reached) {
                $clone = $target.filter(':lt(2)').clone(true);
                $clone
                    .addClass('fixed')
                    .css('backgroundColor', bg)
                    .css('left', $('.scroll').scrollLeft() * -1)

                $target.filter(':lt(2)').addClass('hidden');
                $target.parent().append($clone);

                top_reached = true;
            } else if (scroll_top < top_offset && top_reached) {
                $target.filter('.hidden').removeClass('hidden');
                $target.filter('.fixed').remove();

                top_reached = false;
            } else if (scroll_top >= bottom_offset && !bottom_reached) {
                $clone.removeClass('fixed');
                $(document).scrollTop(bottom_offset + sticky_height);

                bottom_reached = true;
            } else if (scroll_top < bottom_offset && bottom_reached) {
                $clone.addClass('fixed');
                $(document).scrollTop(bottom_offset - sticky_height);

                bottom_reached = false;
            }
        });

        // Horizontal container scroll listener
        $('.scroll').scroll(function(){
            if (!top_reached) {
                return;
            }

            $clone.css('left', $(this).scrollLeft() * -1);
        });
    }

    this.saveResults = function(){
        $('.compare-results-box a.button').flModal({
            caption: lang['compare_save_results'],
            source: '#compare-save-container',
            ready: function(){
                var $form   = $('form[name=save-table]');
                var $name   = $form.find('input[name=name]');

                var buttonState = function(loading){
                    $form.find('input[type=submit]')
                        .attr('disabled', loading)
                        .val(loading ? lang['loading'] : $button.data('value'));
                };

                $form.submit(function(){
                    var $type = $(this).find('input[name=type]:checked');
                    var name  = $name.val();
                    var type  = $type.val();
                    
                    if (name.length < 3) {
                        $name.addClass('error');
                    } else {
                        buttonState(true);

                        var data = {
                            mode: 'compareSaveTable',
                            name: name,
                            type: type,
                            lang: rlLang
                        };
                        flUtil.ajax(data, function(response, status){
                            if (status == 'success') {
                                if (response.status == 'OK') {
                                    eraseCookie('compare_listings');
                                    self.eraseStorage();

                                    var url = rlConfig['seo_url'];
                                    url += rlConfig['mod_rewrite']
                                        ? rlPageInfo['path'] + '/' + response.results + '.html'
                                        : '?page=' + rlPageInfo['path'] + '&sid=' + response.results;

                                    location.href = url;
                                } else if (response.errorCode == 'NOT_LOGGED_IN') {
                                    location.reload();
                                } else {
                                    buttonState(false);
                                    console.log('Compare Ads plugin: Unable to save table by ajax call (compareSaveTable)');
                                }
                            } else {
                                buttonState(false);
                                console.log('Compare Ads plugin: ajax call (compareSaveTable) failed');
                            }
                        });
                    }

                    return false;
                });

                $name.on('keyup', function(){
                    if ($(this).hasClass('error') && $(this).val().length >= 3) {
                        $(this).removeClass('error');
                    }
                });
            },
            width: 'auto',
            height: 'auto'
        });
    }

    this.removeTableHandler = function(){
        $('#compare-saved-tables > li .remove').click(function(){
            var $li = $(this).closest('li');
            var id  = $li.data('table-id');

            $(document).flModal({
                click: false,
                caption: lang['notice'],
                content: lang['compare_delete_table_notice'],
                prompt: 'compare.removeTable(' + id + ')',
                width: 'auto',
                height: 'auto'
            });
        });
    }

    this.removeTable = function(id){
        if (!id) {
            return;
        }

        var data = {
            mode: 'compareRemoveTable',
            id: id,
            savedTable: this.options.savedTable,
            lang: rlLang
        };
        flUtil.ajax(data, function(response, status){
            if (status == 'success') {
                if (response.status == 'OK') {
                    // On related page remove mode
                    // Redirect to the parent page only
                    if (self.options.savedTable) {
                        var url = rlConfig['seo_url'];
                        url += rlConfig['mod_rewrite']
                            ? rlPageInfo['path'] + '.html'
                            : '?page=' + rlPageInfo['path'];

                        location.href = url;
                    }
                    // In list remove mode
                    else {
                        printMessage('notice', lang['compare_table_removed']);

                        $('#compare-saved-tables > li[data-table-id=' + id + ']')
                            .addClass('removing')
                            .on(self.transactionEnd, function(){
                                $(this).remove();

                                if (!$('#compare-saved-tables > li').length) {
                                    $('#compare-data-message').removeClass('hide');
                                }
                            });
                    }
                } else {
                    $button.val($button.data('value'));
                    console.log('Compare Ads plugin: Unable to remove table by ajax call (compareRemoveTable)');

                    if (response.message) {
                        printMessage('error', response.message);
                    }
                }
            } else {
                $button.val($button.data('value'));
                console.log('Compare Ads plugin: ajax call (compareRemoveTable) failed');
            }
        });
    }

    this.currencyConversion = function(){
        if (typeof $.convertPrice == 'function') {
            $('.fields-content .price').convertPrice();
        }
    }

    this.removeFromStorage = function(id){
        if (!this.isStorage || !id) {
            return;
        }

        var data = JSON.parse(localStorage.getItem(this.storageKey));

        if (!data) {
            return;
        }

        data.forEach(function(item, index){
            if (item.id == id) {
                data.splice(index, 1);
                return;
            }
        });

        localStorage.setItem(this.storageKey, JSON.stringify(data));
    }

    this.eraseStorage = function(){
        if (!this.isStorage) {
            return;
        }

        localStorage.removeItem(this.storageKey);
        localStorage.removeItem(this.storageKey + '-updateTime');
    }
};
