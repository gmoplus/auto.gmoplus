{if $plugins.massmailer_newsletter && $pageInfo.Controller != 'search_map'}
    <div class="newsletter">
        <div class="point1 clearfix mx-auto">
            <div class="content-padding">
                <div class="row mb-0">
                    <p class="newsletter__text col-lg-6 col-md-5 col-sm-12 align-self-center">{$lang.footer_newsletter_text}</p>
                    <div class="col-lg-6 col-md-7 col-sm-12" id="nova-newsletter-cont">

                    </div>
                </div>
            </div>
        </div>
    </div>
{/if}
<footer class="page-footer content-padding">
    <div class="point1 clearfix">
        <div class="row">
            <nav class="footer-menu col-12 col-xl-12">
                <div class="row">
                    {include file='menus'|cat:$smarty.const.RL_DS|cat:'footer_menu.tpl'}

                    {if $config.ios_app_url || $config.android_app_url}
                    <div class="mobile-apps col-sm-6 col-md-3 d-block d-md-flex flex-column">
                        <h4 class="footer__menu-title">{$lang.footer_menu_mobile_apps}</h4>
                        {if $config.ios_app_url}
                        <a class="d-inline-block pt-0 mt-sm-2" target="_blank" href="{$config.ios_app_url}">
                            <img src="{$rlTplBase}img/app-store-icon.svg" alt="App store icon" />
                        </a>
                        {/if}
                        {if $config.android_app_url}
                        <a class="d-inline-block mt-0 mt-sm-3" target="_blank" href="{$config.android_app_url}">
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

{include file='../img/gallery.svg'}

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
