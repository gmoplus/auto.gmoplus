<!-- Recently Viewed Listings Box tpl -->

{if $rvShowBox}
    {if $config.rv_box_type == 'standard_block'}
        <div class="rv_listings_dom"></div>

        <div class="text-center">
            <a href="{pageUrl key='rv_listings'}" class="button">{$lang.rv_history_link}</a>
        </div>
    {/if}

    <script class="fl-js-dynamic">
    lang['rv_listings'] = "{$lang.rv_listings}";
    rlConfig['rv_box_type'] = '{$config.rv_box_type}';

    var template_name     = '{$tpl_settings.name}';
    var template_version  = '{$tpl_settings.version}';
    var rv_history_link   = "{pageUrl key='rv_listings'}";
    var storage_item_name = '{$smarty.const.RL_URL_HOME|parse_url:$smarty.const.PHP_URL_HOST|replace:".":"_"|cat:"_"}';
    storage_item_name     += '{if $smarty.const.RL_DIR}{$smarty.const.RL_DIR|replace:"/":""}{/if}';

    {literal}
    $(function(){
        var $target = rlConfig['rv_box_type'] == 'bottom_bar'
            ? $('section#main_container > div.inside-container section#content')
            : $('.rv_listings_dom');
        var offset = $target.offset();
        var target_offset = offset.top;
        var window_height = $(window).height();
        var target_reached = false;

        if (rlConfig['rv_box_type'] == 'bottom_bar') {
            target_offset += $target.height();
        }

        $(window).scroll(function(){
            if (!target_reached) {
                var scroll_top = $(window).scrollTop();

                if ((scroll_top + window_height) > (target_offset - (window_height / 1.5))) {
                    xdLocalStorageInit(function() {
                        {/literal}
                        {if $isLogin && !$smarty.session.sync_rv_complete}
                            syncListings();
                        {else}
                            loadRvListingsToBlock();
                        {/if}
                        {literal}
                    });

                    target_reached = true;
                }
            }
        });
    });
    {/literal}</script>
{/if}

<!-- Recently Viewed Listings Box tpl end -->
