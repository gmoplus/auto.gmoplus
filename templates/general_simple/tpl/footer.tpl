{include file='../img/social.svg'}

<footer class="page-footer">
    <div class="point1 position-relative">
        <span class="scroll-top"></span>

        <div class="row">
            <div class="col-lg-3 col-12">
                <div id="footer_logo" class="mx-auto ml-lg-0">
                    <a href="{$rlBase}" title="{$config.site_name}">
                        <img alt="{$config.site_name}" src="{$rlTplBase}img/blank.gif"/>
                    </a>
                </div>
                <div class="copyright">
                    <div class="text-center text-lg-left mt-3">
                        &copy; {$smarty.now|date_format:'%Y'}, {$lang.powered_by} <br>
                        <a title="{$lang.powered_by} {$lang.copy_rights}"
                           href="{$lang.flynax_url}">{$lang.copy_rights}</a>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-7 col-6 d-none d-lg-block">
                <nav class="footer-menu">
                    {include file='menus'|cat:$smarty.const.RL_DS|cat:'footer_menu.tpl'}
                </nav>
            </div>
            <div class="col-xl-3 col-lg-2 col-12 no-clear">
                <div class="footer-data mt-3 mt-lg-0">
                    <div class="icons justify-content-center justify-content-lg-end d-flex">
                        {include file='menus/footer_social_icons.tpl'}
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

{rlHook name='tplFooter'}

{if !$isLogin}
    <div id="login_modal_source" class="hide">
        <div class="tmp-dom">
            {include file='blocks/login_modal.tpl'}
        </div>
    </div>
{/if}

{include file='footerScript.tpl'}
