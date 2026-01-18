<!-- recently viewed listings tpl -->
{if !empty($listings)}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'grid.tpl' periods=true}

    <!-- paging block -->
    {paging calc=$pInfo.calc total=$listings|@count current=$pInfo.current per_page=$config.listings_per_page}
    <!-- paging block end -->
{else}
    <div class="info">
        {if $isLogin && $smarty.session.sync_rv_complete}{$lang.rv_no_listings}{else}{$lang.loading}{/if}
    </div>
{/if}

<script class="fl-js-dynamic">
var notice            = '{$lang.notice|escape:"javascript"}';
var storage_item_name = '{$smarty.const.RL_URL_HOME|parse_url:$smarty.const.PHP_URL_HOST|replace:".":"_"|cat:"_"}';
storage_item_name     += '{if $smarty.const.RL_DIR}{$smarty.const.RL_DIR|replace:"/":""}{/if}';
var rv_history_link   = "{pageUrl key='rv_listings'}";
var rv_pg             = '{$smarty.get.pg}';

{literal}
$(function() {
    xdLocalStorageInit(function () {
        {/literal}
        {if $isLogin && !$smarty.session.sync_rv_complete}
            syncListings();
        {elseif !$isLogin}
            ajaxLoadRvListings();
        {/if}

        {if $inactive_listings}
            xdLocalStorage.setItem('rv_listings_' + storage_item_name, JSON.stringify({$rvStorageListings|@json_encode}));
        {/if}
        {literal}
    });

    addTriggerToIcons();
});
{/literal}</script>

<!-- recently viewed listings tpl end -->
