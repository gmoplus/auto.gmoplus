
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

var compareTabClass = function(){
    var self = this;

    this.options        = {};
    this.isStorage      = typeof(Storage) !== 'undefined';
    this.storageKey     = 'compare-ads';
    this.transactionEnd = 'transitionend webkitTransitionEnd oTransitionEnd';

    this.$container     = $('.compare-ad-container');
    this.$tab           = this.$container.find('.compare-ad-tab');
    this.$list          = this.$container.find('.compare-ad-list > ul');

    this.init = function(params){
        this.options = params;

        if (!params.cache) {
            this.isStorage = false;
        }

        this.tab();
        this.setListeners();
        this.restore();
    }

    this.tab = function(){
        this.$tab.click(function(e){
            self.$container.toggleClass('active');
            $('body').toggleClass('compare-active');
        });
    }

    this.openTab = function(){
        if (this.$container.hasClass('active')) {
            return;
        }

        this.$container.addClass('active');
        $('body').addClass('compare-active');
    }

    this.setListeners = function(){
        // Icons click handler
        $('#main_container').on('click', '.compare-grid-icon', function(){
            self.actIcon($(this));
            self.manage($(this));
        });

        // Compare tab remove icon handler
        this.$list.on('click', '> li:not(.removing) div.icon.remove', function(){
            var $item = $(this).closest('li');
            var id    = $item.data('listing-id');

            // Manage data
            self.manage($item);

            // Act icon if the source listing available on the page
            if ($('[data-listing-id=' + id + ']').length) {
                self.actIcon($('[data-listing-id=' + id + ']'));
            }
        });
    }

    this.actIcon = function($obj){
        var postfix = $obj.hasClass('active') ? '' : '-rev';
        var key     = $obj.hasClass('active') ? 'add_to' : 'remove_from';

        $obj.find('use').attr('xlink:href', '#compare-ad-icon' + postfix);
        $obj.find('.link').text(lang['compare_' + key + '_compare']);
        $obj.toggleClass('active');

        // Legacy icons support
        if (!this.options.svgSupport) {
            $obj.toggleClass('remove_from_compare');
            $obj.toggleClass('add_to_compare');
            $obj.toggleClass('remove');
        }
    }

    this.manage = function($obj){
        if (!$obj) {
            return;
        }

        var id     = $obj.data('listing-id').toString();
        var cookie = readCookie('compare_listings');
        var ids    = cookie ? cookie.split(',') : [];

        if (ids.indexOf(id) >= 0) {
            ids.splice(ids.indexOf(id), 1);
            this.remove(id);
        } else {
            ids.push(id);
            this.add($obj, id);
            this.openTab();
        }

        // Recount items
        this.recount();

        createCookie('compare_listings', ids.join(','), 93);
    }

    this.add = function($obj, id){
        // Clear
        if (!this.$list.find('> li').length) {
            this.$list.empty();
        }

        var img = '';
        
        // Append item
        var item = {
            id: id,
            url: $obj.data('listing-url') ? $obj.data('listing-url') : window.location.href,
            img: $obj.data('listing-picture'),
            title: $obj.data('listing-title'),
            fields: $obj.data('listing-fields')
        };
        this.$list.append(
            $('#compare-list-item-view').render(item)
        );

        // Scroll list done
        this.$list.scrollTop(this.$list.height());

        // Remove rendering class to run transition
        setTimeout(function(){
            self.$list.find('.rendering').removeClass('rendering');
        }, 10);

        // Add to the storage
        this.addToStorage(item);
    }

    this.remove = function(id){
        if (this.$container.hasClass('active')) {
            this.$list.find('#compare-list-' + id)
                .addClass('removing')
                .on(self.transactionEnd, function(){
                    $(this).remove();
                });
        } else {
            this.$list.find('#compare-list-' + id).remove();
        }

        this.removeFromStorage(id);
    }

    this.addToStorage = function(item){
        if (!this.isStorage || !item) {
            return;
        }

        var data = JSON.parse(localStorage.getItem(this.storageKey)) || [];
        data.push(item);

        this.setStorageItem(data);
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

    this.setStorageItem = function(data){
        localStorage.setItem(this.storageKey, JSON.stringify(data));

        var date = new Date();
        var update_time = date.getTime() + (this.options.cachePeriod * 3600 * 1000);
        localStorage.setItem(this.storageKey + '-updateTime', update_time);
    }

    this.restore = function(){
        var cookie = readCookie('compare_listings');

        if (cookie) {
            var update_cache = false;
            var date = new Date();
            var cache_time = localStorage.getItem(this.storageKey + '-updateTime');
            cache_time = cache_time ? parseInt(cache_time) : 0;

            // Check for expired cache
            if (this.isStorage && this.options.cache && date.getTime() > cache_time) {
                update_cache = true;
            }

            // Restore from the local storage
            if (this.isStorage && this.options.cache && !update_cache) {
                this.build(JSON.parse(localStorage.getItem(this.storageKey)));
            }
            // Restore by ajax call
            else {
                var data = {
                    mode: 'compareFetch',
                    ids: cookie,
                    lang: rlLang
                };
                flUtil.ajax(data, function(response, status){
                    if (status == 'success') {
                        if (response.status == 'OK') {
                            self.build(response.results);

                            // Save updated data
                            if (update_cache) {
                                self.setStorageItem(response.results);
                            }
                        } else {
                            console.log('Compare Ads plugin: Unable to fetch listings by ajax call (compareFetch)');
                        }
                    } else {
                        console.log('Compare Ads plugin: ajax call (compareFetch) failed');
                    }
                });
            }
        }
    }

    this.build = function(listings){
        if (!listings) {
            return;
        }

        this.$list.empty();

        this.$list.append(
            $('#compare-list-item-view').render(listings)
        );
        this.$list.find('.rendering').removeClass('rendering');

        this.recount();
    }

    this.recount = function(){
        var count = this.$list.find('> li:not(.removing)').length;
        var counter = count > 0 ? '(' + count + ')' : '';

        // Change counter
        this.$tab.find('.compare-counter').text(counter);

        // Mark as affected
        this.$list.parent()[
            count > 0 ? 'removeClass' : 'addClass'
        ]('empty');

        // Show button
        this.$list.parent()[
            count > 1 ? 'removeClass' : 'addClass'
        ]('no-button');

        // Show empty box message
        if (count == 0) {
            this.$list.empty();
        }
    }
}
