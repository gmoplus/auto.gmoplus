<!-- my listings -->

{if !empty($listings)}

    {if $sorting}

        {php}
            $types = array('asc' => 'ascending', 'desc' => 'descending'); $this -> assign('sort_types', $types);
            $sort = array('price', 'number', 'date'); $this -> assign('sf_types', $sort);
        {/php}

        <div class="grid_navbar">
            <div class="sorting">
                <div class="current{if $grid_mode == 'map'} disabled{/if}">
                    {$lang.sort_by}:
                    <span class="link">{if $sort_by}{$sorting[$sort_by].name}{else}{$lang.date}{/if}</span>
                    <span class="arrow"></span>
                </div>
                <ul class="fields">
                    {foreach from=$sorting item='field_item' key='sort_key' name='fSorting'}
                        {if $field_item.Type|in_array:$sf_types}
                            {foreach from=$sort_types key='st_key' item='st'}
                                <li><a rel="nofollow" {if $sort_by == $sort_key && $sort_type == $st_key}class="active"{/if} title="{$lang.sort_listings_by} {$field_item.name} ({$lang[$st]})" href="{if $config.mod_rewrite}?{else}index.php?{$pageInfo.query_string}&{/if}sort_by={$sort_key}&sort_type={$st_key}">{$field_item.name} ({$lang[$st]})</a></li>
                            {/foreach}
                        {else}
                            <li><a rel="nofollow" {if $sort_by == $sort_key}class="active"{/if} title="{$lang.sort_listings_by} {$field_item.name}" href="{if $config.mod_rewrite}?{else}index.php?{$pageInfo.query_string}&{/if}sort_by={$sort_key}&sort_type=asc">{$field_item.name}</a></li>
                        {/if}
                    {/foreach}
                    {rlHook name='myListingsAfterSorting'}
                </ul>
            </div>
        </div>
    {/if}

    {rlHook name='myListingsBeforeListings'}

    <section id="listings" class="my-listings list">
        {foreach from=$listings item='listing' key='key'}
            {if $listing.Subscription_ID}
                {assign var='hasSubscriptions' value=true}
            {/if}
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'my_listing.tpl'}
        {/foreach}
    </section>

    <!-- paging block -->
    {if $search_results_mode && $refine_search_form}
        {assign var='myads_paging_url' value=$search_results_url}
    {else}
        {assign var='myads_paging_url' value=false}
    {/if}
    {paging calc=$pInfo.calc total=$listings|@count current=$pInfo.current per_page=$config.listings_per_page url=$myads_paging_url method=$listing_type.Submit_method}
    <!-- paging block end -->

    <script class="fl-js-dynamic">{literal}
        $(document).ready(function(){
            $('.my-listings .delete').each(function(){
                $(this).flModal({
                    caption: '{/literal}{$lang.warning}{literal}',
                    content: '{/literal}{$lang.notice_delete_listing}{literal}',
                    prompt: 'xajax_deleteListing('+ $(this).attr('id').split('_')[2] +')',
                    width: 'auto',
                    height: 'auto'
                });
            });

            {/literal}{if $hasSubscriptions}{literal}
            $('.my-listings .unsubscription').each(function() {
                $(this).flModal({
                    caption: '',
                    content: '{/literal}{$lang.stripe_unsubscripbe_confirmation}{literal}',
                    prompt: 'flSubscription.cancelSubscription(\''+ $(this).attr('accesskey').split('-')[2] +'\', \''+ $(this).attr('accesskey').split('-')[0] +'\', '+ $(this).attr('accesskey').split('-')[1] +', false)',
                    width: 'auto',
                    height: 'auto'
                });
            });
            {/literal}{/if}{literal}

            // GMO Plus TASK 8 & 9: Listing Refresh System JavaScript
            
            // Initialize refresh system
            initRefreshSystem();
            
            // Refresh button click handler
            $('.my-listings .refresh-listing').click(function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var listingId = $button.data('listing-id');
                var listingType = $button.data('listing-type') || 'general';
                
                if (!listingId) {
                    printMessage('error', 'İlan ID bulunamadı.');
                    return;
                }
                
                // Confirmation dialog
                if (!confirm('{/literal}{$lang.refresh_confirm_message}{literal}')) {
                    return;
                }
                
                // Disable button and show loading
                $button.prop('disabled', true);
                var originalText = $button.find('span').text();
                $button.find('span').text('{/literal}{$lang.refresh_in_progress}{literal}');
                $button.addClass('loading');
                
                // Call AJAX refresh function - Use fallback directly
                console.log('🚀 Using fallback refresh directly (xajax disabled)');
                refreshListingFallback(listingId);
            });

            $('label.switcher-status input[type="checkbox"]').change(function() {
                var element = $(this);
                var id = $(this).val();
                var status = $(this).is(':checked') ? 'active' : 'approval';

                $.getJSON(
                    rlConfig['ajax_url'],
                    {mode: 'changeListingStatus', item: id, value: status, lang: rlLang},
                    function(response) {
                        if (response) {
                            if (response.status == 'ok') {
                                printMessage('notice', response.message_text);
                            } else {
                                printMessage('error', response.message_text);
                                element.prop('checked', false);
                            }
                        }
                    }
                );
            });

            $('label.switcher-featured input[type="checkbox"]').change(function() {
                var element = $(this);
                var id = $(this).val();
                var status = $(this).is(':checked') ? 'featured': 'standard';

                $.getJSON(
                    rlConfig['ajax_url'],
                    {mode: 'changeListingFeaturedStatus', item: id, value: status, lang: rlLang},
                    function(response) {
                        if (response) {
                            if (response.status == 'ok') {
                                if (status == 'featured') {
                                    $('article#listing_' + id).addClass('featured');
                                    $('article#listing_'+ id +' div.nav div.info .picture').prepend('<div class="label"><div title="{/literal}{$lang.featured}{literal}">{/literal}{$lang.featured}{literal}</div></div></div>');
                                } else {
                                    $('article#listing_'+ id +' div.nav div.info .picture').find('div.label').remove();
                                    $('article#listing_' + id).removeClass('featured');
                                }
                                printMessage('notice', response.message_text);
                            } else {
                                printMessage('error', response.message_text);
                                if (element.is(':checked')) {
                                    element.prop('checked', false);
                                } else {
                                    element.prop('checked', 'checked');
                                }
                            }
                        }
                    }
                );
            });
        });
        
        // GMO Plus TASK 8 & 9: Refresh System Functions
        
        function initRefreshSystem() {
            // Load refresh availability for all listings
            $('.refresh-remaining-count').each(function() {
                var $element = $(this);
                var listingId = $element.data('listing-id');
                var listingType = $element.data('listing-type') || 'general';
                
                if (listingId) {
                    loadRefreshAvailability(listingId, listingType);
                }
            });
        }
        
        function loadRefreshAvailability(listingId, listingType) {
            var $countElement = $('.refresh-remaining-count[data-listing-id="' + listingId + '"]');
            
            if (!$countElement.length) {
                console.log('❌ Refresh count element not found for listing:', listingId);
                return;
            }
            
            console.log('🔄 Loading refresh availability for listing:', listingId, 'type:', listingType);
            
            // Show loading
            $countElement.find('.loading-icon').show();
            $countElement.find('.count').text('...');
            
            // Use fallback AJAX directly (xajax disabled due to version conflict)
            console.log('🚀 Using fallback AJAX directly (xajax disabled)');
            loadRefreshAvailabilityFallback(listingId, listingType);
        }
        
        function updateRefreshUI(listingId, data) {
            console.log('📊 UpdateRefreshUI called for listing:', listingId, 'data:', data);
            
            var $countElement = $('.refresh-remaining-count[data-listing-id="' + listingId + '"]');
            var $button = $('#refresh_listing_' + listingId);
            
            if (!$countElement.length) {
                console.log('❌ Refresh count element not found in updateRefreshUI for listing:', listingId);
                return;
            }
            
            // Hide loading
            $countElement.find('.loading-icon').hide();
            
            if (data.status === 'ok') {
                console.log('✅ Refresh data OK - remaining:', data.remaining_refreshes, 'can_refresh:', data.can_refresh);
                
                // Update remaining count
                $countElement.find('.count').text(data.remaining_refreshes || 0);
                
                // Enable/disable button
                if (data.can_refresh) {
                    $button.prop('disabled', false).removeClass('disabled');
                    console.log('✅ Button enabled for listing:', listingId);
                } else {
                    $button.prop('disabled', true).addClass('disabled');
                    console.log('🚫 Button disabled for listing:', listingId, 'reason:', data.message);
                }
                
                // Update button title
                if (data.message) {
                    $button.attr('title', data.message);
                }
            } else {
                console.log('❌ Refresh data error:', data);
                $countElement.find('.count').text('0');
                $button.prop('disabled', true).addClass('disabled');
            }
        }
        
        function handleRefreshResponse(data, listingId) {
            var $button = $('#refresh_listing_' + listingId);
            
            // Reset button
            $button.prop('disabled', false);
            $button.find('span').text('{/literal}{$lang.refresh_listing}{literal}');
            $button.removeClass('loading');
            
            if (data.status === 'ok') {
                printMessage('notice', data.message);
                
                // Update refresh availability
                var listingType = $button.data('listing-type') || 'general';
                loadRefreshAvailability(listingId, listingType);
                
                // Refresh page after 2 seconds to show updated listing position
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
                
            } else {
                printMessage('error', data.message || '{/literal}{$lang.refresh_failed}{literal}');
            }
        }
        
        // Fallback AJAX for when xajax fails
        function loadRefreshAvailabilityFallback(listingId, listingType) {
            console.log('🔄 Using fallback AJAX for listing:', listingId);
            console.log('🌐 AJAX URL:', rlConfig['ajax_url']);
            console.log('📤 POST data:', {
                mode: 'checkRefreshAvailability',
                listing_id: listingId,
                listing_type: listingType,
                lang: rlLang
            });
            
            var $countElement = $('.refresh-remaining-count[data-listing-id="' + listingId + '"]');
            
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: {
                    mode: 'checkRefreshAvailability',
                    listing_id: listingId,
                    listing_type: listingType,
                    lang: rlLang
                },
                dataType: 'json',
                success: function(response) {
                    console.log('✅ Fallback AJAX success - Raw response:', response);
                    console.log('✅ Response type:', typeof response);
                    console.log('✅ Response length:', response ? response.length : 'null');
                    
                    if (response && response.status === 'OK') {
                        var data = {
                            status: 'ok',
                            can_refresh: response.data.can_refresh || false,
                            remaining_refreshes: response.data.remaining_refreshes || 0,
                            message: response.data.message || ''
                        };
                        updateRefreshUI(listingId, data);
                    } else {
                        $countElement.find('.loading-icon').hide();
                        $countElement.find('.count').text('0');
                        $('#refresh_listing_' + listingId).prop('disabled', true).addClass('disabled');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Fallback AJAX error:', error);
                    console.error('❌ XHR status:', xhr.status);
                    console.error('❌ XHR responseText:', xhr.responseText);
                    console.error('❌ AJAX status:', status);
                    $countElement.find('.loading-icon').hide();
                    $countElement.find('.count').text('ERR');
                }
            });
        }
        
        // Fallback refresh function
        function refreshListingFallback(listingId) {
            console.log('🔄 Using fallback refresh for listing:', listingId);
            console.log('🌐 AJAX URL:', rlConfig['ajax_url']);
            
            var $button = $('#refresh_listing_' + listingId);
            var listingType = $button.data('listing-type') || 'general';
            
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: {
                    mode: 'refreshListing',
                    listing_id: listingId,
                    listing_type: listingType,
                    lang: rlLang
                },
                dataType: 'json',
                success: function(response) {
                    console.log('✅ Fallback refresh success - Raw response:', response);
                    console.log('✅ Response type:', typeof response);
                    console.log('✅ Response length:', response ? response.length : 'null');
                    
                    var data = {
                        status: response.status === 'OK' ? 'ok' : 'error',
                        message: response.message || response.message_text || 'İşlem tamamlandı'
                    };
                    
                    handleRefreshResponse(data, listingId);
                },
                error: function(xhr, status, error) {
                    console.error('❌ Fallback refresh error:', error);
                    console.error('❌ XHR status:', xhr.status);
                    console.error('❌ XHR responseText:', xhr.responseText);
                    console.error('❌ AJAX status:', status);
                    var data = {
                        status: 'error',
                        message: 'Bağlantı hatası. Lütfen tekrar deneyin.'
                    };
                    handleRefreshResponse(data, listingId);
                }
            });
        }
        
        {/literal}
    </script>
    
    <!-- GMO Plus TASK 8 & 9: Refresh System CSS Styles -->
    <style>
        /* Refresh button styling */
        .my-listings .refresh-listing:before {
            content: '';
            background-image: url('../img/gallery.png');
            background-repeat: no-repeat;
            background-position: 0 -555px; /* Refresh icon position */
            width: 16px;
            height: 16px;
            left: 0;
            top: 2px;
            position: absolute;
            opacity: 0.5;
            transition: opacity 0.3s;
        }
        
        .my-listings .refresh-listing:hover:before {
            opacity: 0.8;
        }
        
        .my-listings .refresh-listing.loading:before {
            background-position: 0 -510px; /* Loading icon position */
            animation: spin 1s linear infinite;
        }
        
        .my-listings .refresh-listing.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .my-listings .refresh-listing.disabled:before {
            opacity: 0.3;
        }
        
        /* Refresh info styling */
        .my-listings .refresh-info {
            color: #28a745;
            font-size: 0.9em;
        }
        
        .my-listings .refresh-remaining-count .count {
            font-weight: bold;
            color: #007bff;
        }
        
        .my-listings .refresh-remaining-count .loading-icon {
            font-size: 12px;
            animation: pulse 1s infinite;
        }
        
        /* Animations */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* Responsive styles */
        @media screen and (max-width: 575px) {
            .my-listings .refresh-listing span {
                display: none;
            }
            
            .my-listings .refresh-info {
                font-size: 0.8em;
            }
        }
    </style>
{else}
    <div class="info">
        {if $pages.add_listing}
            {assign var='link' value='<a href="'|cat:$add_listing_href|cat:'">$1</a>'}
            {$lang.no_listings_here|regex_replace:'/\[(.+)\]/':$link}
        {else}
            {phrase key='no_listings_found_deny_posting' db_check='true'}
        {/if}
    </div>
{/if}

{rlHook name='myListingsBottom'}

{if $hasSubscriptions}
    {addJS file=$rlTplBase|cat:'js/subscription.js' id='subscription'}
{/if}

<!-- my listings end -->
