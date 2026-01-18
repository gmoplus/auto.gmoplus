<!-- SellerReviews footer tpl -->

<script class="fl-js-dynamic">{literal}
    let srrTabOpened = false;
    if (location.hash && location.hash === '#srr_comments_tab') {
        if (isLogin || !srrConfigs.loginToAccess) {
            sellerReviews.loadComments();
            srrTabOpened = true;
        } else {
            printMessage('warning', lang.srr_login_to_see_comments);
        }
    }

    if (isLogin || !srrConfigs.loginToAccess) {
        if (rlPageInfo.controller === 'account_type' && srrConfigs.displayMode === 'tab') {
            let $controllerArea = $('#controller_area');
            let $tabs = $controllerArea.find('ul.tabs');
            let $srrTab = $('<li>', {id: 'tab_srr_comments'}).append(
                $('<a>', {'href': '#srr_comments', 'data-target': 'srr_comments'}).html(lang.srr_tab)
            ).click(function () {
                if (srrTabOpened === false) {
                    sellerReviews.loadComments();
                }
                srrTabOpened = true;
            });
            let $srrTabContent = $('<div>', {id: 'area_srr_comments', class: 'tab_area hide'}).append(
                $('<section>', {id: 'srr_comments'}).html(lang.loading),
                $('<section>', {id: 'srr_pagination'}),
            );

            if ($tabs.length > 0) {
                // Add new tab with comments to list
                $tabs.find('li:last').after($srrTab);
                $controllerArea.find('div[id^="area_"]:last').after($srrTabContent);
            } else {
                // Create list of tabs and put new tab with comments in end
                let $listingsContainer = $('<div>', {id: 'area_listings', class: 'tab_area'});
                $controllerArea.append($listingsContainer);
                $controllerArea.find('> *').appendTo($listingsContainer);

                $controllerArea.prepend(
                    $('<ul>', {class: 'tabs tabs-hash'}).append(
                        $('<li>', {id: 'tab_listings', class: 'active'}).append(
                            $('<a>', {'href': '#listings', 'data-target': 'listings'}).html('{/literal}{$lang.listings}{literal}')
                        ),
                        $srrTab
                    )
                )
                $controllerArea.find('div[id="area_listings"]').after($srrTabContent);
            }
        }
    }
    {/literal}
</script>

<!-- SellerReviews footer tpl end -->
