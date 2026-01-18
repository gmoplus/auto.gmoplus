{include file='head.tpl'}

    {if $pageInfo.Key != 'search_on_map' && $config.header_banner_space}
    <div class="header-banner-cont">
        <div class="container">
            <div id="header-banner">
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'header_banner.tpl'}
            </div>
        </div>
    </div>
    {/if}
    <div id="mobile-header"{if $pageInfo.Controller != 'search_map'} class="{if $pageInfo.Controller == 'listing_details'}d-xl-none{else}d-lg-none{/if}"{/if}>
        <div class="container">
            <div class="row">
                <div class="col-12 header-content"></div>
            </div>
        </div>
    </div>
