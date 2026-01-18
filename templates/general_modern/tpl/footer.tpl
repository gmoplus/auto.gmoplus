    <footer class="page-footer">
        <div class="point1 d-flex flex-column-reverse flex-lg-row flex-wrap">
            <span class="scroll-top"></span>

            <div class="d-flex w-100 justify-content-between">
                <nav class="footer-menu">
                    {include file='menus'|cat:$smarty.const.RL_DS|cat:'footer_menu.tpl'}
                </nav>

                {include file='footer_data.tpl' noCP=true}
            </div>

            <div class="copy-rights text-center text-lg-left w-100 mt-3 mb-3">
                &copy; {$smarty.now|date_format:'%Y'}, {$lang.powered_by}
                <a title="{$lang.powered_by} {$lang.copy_rights}" href="{$lang.flynax_url}">{$lang.copy_rights}</a>
            </div>
        </div>
    </footer>

    {php}
    $GLOBALS['tpl_settings']['name'] = 'general_modern'; // hack again, recently viewed styles
    {/php}

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
