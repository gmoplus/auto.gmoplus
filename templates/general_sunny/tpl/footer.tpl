    <footer class="page-footer content-padding">
        <div class="point1 clearfix">
            <div class="row no-gutters">
                {if $plugins.massmailer_newsletter}
                    <div class="newsletter col-12 col-xl-3 order-xl-2 mb-4">
                        <div class="row">
                            <p class="newsletter__text col-xl-12 col-md-6">{$lang.footer_newsletter_text}</p>
                            <div class="col-xl-12 col-md-6" id="nova-newsletter-cont">

                            </div>
                        </div>
                    </div>
                {/if}

                <nav class="footer-menu col-12{if $plugins.massmailer_newsletter} col-xl-9{/if}">
                    <div class="row">
                        {include file='menus'|cat:$smarty.const.RL_DS|cat:'footer_menu.tpl'}

                        {if $config.ios_app_url || $config.android_app_url}
                        <div class="mobile-apps col-lg-3">
                            <h4 class="footer-menu-title">{$lang.footer_menu_mobile_apps}</h4>
                            {if $config.ios_app_url}
                            <a class="d-inline-block pt-0 pt-lg-2 mb-lg-3" target="_blank" href="{$config.ios_app_url}">
                                <img src="{$rlTplBase}img/app-store-icon.svg" alt="App store icon" />
                            </a>
                            {/if}
                            {if $config.android_app_url}
                            <a class="d-inline-block ml-3 ml-lg-0 mt-0" target="_blank" href="{$config.android_app_url}">
                                <img src="{$rlTplBase}img/play-market-icon.svg" alt="Play market icon" />
                            </a>
                            {/if}
                        </div>
                        {/if}
                    </div>
                </nav>
            </div>

            {include file='footer_data.tpl'}
        </div>
    </footer>

    {rlHook name='tplFooter'}
</div>

{if !$isLogin}
    <div id="login_modal_source" class="hide">
        <div class="tmp-dom">
            {include file='blocks/login_modal.tpl'}
        </div>
    </div>
{/if}

{include file='footerScript.tpl'}
