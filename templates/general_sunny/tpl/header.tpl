{include file='head.tpl'}

{include file='../img/gallery.svg'}

<div class="main-wrapper d-flex flex-column{if $pageInfo.Key == 'home' && !$home_slides} no-slides{/if}">
    <header class="page-header{if $pageInfo.Key == 'home' && !$config.main_menu_home_page} main-menu-hidden{/if}{if $pageInfo.Key == 'search_on_map'} fixed-menu{/if}">
        <div class="top-header">
            <div class="point1 mx-auto">
                <div class="d-flex">
                    <span class="circle mr-md-auto mr-auto mr-md-0 ml-0" id="theme-switcher">
                        <span class="default pl-0">
                            <span class="theme">
                                {if $smarty.cookies.colorTheme}
                                    {if $smarty.cookies.colorTheme == 'dark'}{$lang.sunny_theme_light}{else}{$lang.sunny_theme_dark}{/if}
                                {/if}
                            </span>
                        </span>
                    </span>

                    {rlHook name='tplHeaderUserNav'}
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'lang_selector.tpl'}
                </div>
            </div>
        </div>
        <div class="point1 clearfix">
            <div class="top-navigation">
                <div class="point1 d-flex mx-auto flex-wrap no-gutters justify-content-between">
                    <div class="d-flex flex-wrap align-items-center justify-content-between flex-fill col-auto col-md-12 position-relative">
                        <div class="mr-auto mr-md-3 pl-0 col-auto order-1" id="logo">
                            <a href="{$rlBase}" title="{$config.site_name}">
                                <img alt="{$config.site_name}" src="{$rlTplBase}img/logo.svg?rev={$config.static_files_revision}" />
                            </a>
                        </div>
                        {if $pageInfo.Key != 'search_on_map'}
                        <div class="d-flex col-12 col-md w-100 justify-content-center order-5 order-md-2">
                            {include file='blocks/smart_search.tpl'}
                        </div>
                        {/if}
                        <div class="col-auto d-flex justify-content-end user-navbar pr-0 order-3">
                            {rlHook name='tplHeaderUserArea'}

                            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'user_navbar.tpl'}
                        </div>

                        <nav class="main-menu col-auto col-md-12 order-4 d-flex flex-wrap">
                            {include file='menus'|cat:$smarty.const.RL_DS|cat:'category_menu.tpl'}
                            {include file='menus'|cat:$smarty.const.RL_DS|cat:'main_menu.tpl'}
                        </nav>

                        <div class="col-md order-6 d-flex d-md-none">
                            <div class="category-menu__button w-100 btn user-select-none mt-3 justify-content-start">
                                <span class="category-menu__button-icon"><span></span></span>

                                {$lang.all_categores}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {assign var='page_menu' value=','|explode:$pageInfo.Menus}

        {if $pageInfo.Key == 'home'}
        <section class="header-nav d-flex flex-column">
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'home_content.tpl'}
        </section>
        {/if}
    </header>
