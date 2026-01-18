    <footer class="page-footer">
        <div class="point1 clearfix">
            <span class="scroll-top"></span>

            <nav class="footer-menu">
                {include file='menus'|cat:$smarty.const.RL_DS|cat:'footer_menu.tpl'}
            </nav>

            {include file='footer_data.tpl'}
        </div>
    </footer>

    {rlHook name='tplFooter'}

</div>

{if !$isLogin}
    <div id="login_modal_source" class="hide">
        <div class="tmp-dom user-navbar-container">
            {include file='blocks/login_modal.tpl'}
        </div>
    </div>
{/if}

{include file='footerScript.tpl'}
