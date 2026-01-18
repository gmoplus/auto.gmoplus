{include file='head.tpl'}

    <div class="main-wrapper d-flex flex-column">
        <header class="page-header{if $pageInfo.Key == 'search_on_map'} fixed-menu{/if}">
            <div class="point1 clearfix">
                <div class="top-navigation">
                    <div class="point1 h-100 d-flex align-items-center">
                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'lang_selector.tpl'}

                        {rlHook name='tplHeaderUserNav'}

                        <span class="header-contacts d-none d-md-block font-size-xs font-weight-semibold">
                            {if $lang.header_contact_email}
                                <a class="color-gray contacts__email ml-3 mr-3" href="mailto: {$lang.header_contact_email}">
                                    <svg viewBox="0 0 12 10" class="mr-1">
                                        <use xlink:href="#envelope-small"></use>
                                    </svg>
                                    {$lang.header_contact_email}
                                </a>
                            {/if}
                            {if $lang.header_contact_phone_number}
                                <a class="d-lg-none d-xl-inline color-gray contacts__handset ml-3 mr-3" href="tel: {$lang.header_contact_phone_number}">
                                    <svg viewBox="0 0 12 12" class="mr-1">
                                        <use xlink:href="#handset"></use>
                                    </svg>
                                    {$lang.header_contact_phone_number}
                                </a>
                            {/if}
                        </span>

                        <nav class="main-menu d-flex flex-fill shrink-fix h-100 justify-content-end">
                            {include file='menus'|cat:$smarty.const.RL_DS|cat:'main_menu.tpl'}
                        </nav>
                    </div>
                </div>
                <section class="header-nav d-flex flex-column">
                    <div class="point1 d-flex flex-fill flex-column">
                        <div class="row no-gutters flex-fill align-items-center">
                            <div class="col-6 col-xl-3">
                                <div class="mr-0 mr-md-3" id="logo">
                                    <a class="d-inline-block" href="{$rlBase}" title="{$config.site_name}">
                                        <img alt="{$config.site_name}"
                                            src="{$rlTplBase}img/logo.png?rev={$config.static_files_revision}"
                                            srcset="{$rlTplBase}img/@2x/logo.png?rev={$config.static_files_revision} 2x" />
                                    </a>
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-12 order-3 order-xl-2">
                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'home_content.tpl'}
                            </div>
                            <div class="col-6 col-xl-3 order-2 order-xl-3 d-flex justify-content-end user-navbar">
                                {rlHook name='tplHeaderUserArea'}

                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'user_navbar.tpl'}
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </header>

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'categories_carousel.tpl'}
