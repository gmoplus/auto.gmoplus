{include file='head.tpl'}

{assign var='main_menu_hidden' value=false}
{if !$config.main_menu_home_page || $pageInfo.Controller == 'search_map'}
    {assign var='main_menu_hidden' value=true}
{/if}

<div class="main-wrapper d-flex flex-column">
    <header class="page-header{if $pageInfo.Key == 'search_on_map'} fixed-menu{/if}{if $main_menu_hidden} main-menu-hidden{/if}">
        <div class="point1 clearfix">
            <div class="top-navigation">
                <div class="point1 d-flex h-100 align-items-center{if !$config.main_menu_home_page} pt-2 pb-2{/if}">
                    <div class="order-2 ml-2 ml-md-0" id="logo">
                        <a href="{$rlBase}" title="{$config.site_name}">
                            <img class="mr-2"
                                 alt="{$config.site_name}"
                                 src="{$rlTplBase}img/logo.png?rev={$config.static_files_revision}"
                                 srcset="{$rlTplBase}img/@2x/logo.png?rev={$config.static_files_revision} 2x" />
                        </a>
                    </div>
                    <div class="d-flex top-user-navigation w-100 justify-content-end{if $config.main_menu_home_page} pt-1 pb-1{/if}">
                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'lang_selector.tpl'}

                        {rlHook name='tplHeaderUserNav'}

                        <div class="d-flex justify-content-end user-navbar">
                            {rlHook name='tplHeaderUserArea'}

                            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'user_navbar.tpl'}
                        </div>
                    </div>
                    <div class="order-4 top-user-menu ml-auto justify-content-end d-flex align-items-center{if $main_menu_hidden} ml-md-3{/if}">
                        <nav class="main-menu d-flex justify-content-end mr-2 mr-md-0">
                            {include file='menus'|cat:$smarty.const.RL_DS|cat:'main_menu.tpl'}
                        </nav>
                    </div>
                </div>
            </div>

            {assign var='page_menu' value=','|explode:$pageInfo.Menus}

            {if $pageInfo.Controller != 'search_map'
                && $pageInfo.Controller != 'listing_details'
                && !$pageInfo.Login
                && !$pageInfo.Plugin
                && !'2'|in_array:$page_menu}
            <section class="header-nav d-flex flex-column content-padding">
                <div class="point1 d-flex flex-fill flex-column">
                    <div class="row no-gutters flex-fill align-items-center">
                        <div class="col-xl-12 col-lg-12 order-1 order-xl-1">
                            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'home_content.tpl'}
                        </div>
                    </div>
                </div>
            </section>
            {/if}
        </div>
    </header>
