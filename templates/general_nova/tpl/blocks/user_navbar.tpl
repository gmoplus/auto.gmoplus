<!-- user navigation bar -->

<span class="circle {if $isLogin} logged-in{else} circle_content-padding{/if}{if $new_messages} notify{/if}" id="user-navbar">
    <span class="default"><span>{if $isLogin}{$isLogin}{else}{$lang.login}{/if}</span></span>
    <span class="content {if $isLogin}a-menu{/if} hide">
        {if $isLogin}
            {include file='menus/user_navbar_menu.tpl'}
        {else}
            <span class="user-navbar-container">
                {include file='blocks/login_modal.tpl'}
            </span>
        {/if}
    </span>
</span>

<!-- user navigation bar end -->
