<!-- user navigation bar -->

<span class="d-none d-md-flex circle{if $isLogin} logged-in{/if}{if $new_messages} notify{/if}" id="user-navbar">
    <span class="default">
        <svg viewBox="0 0 22 22" class="header-usernav-icon-fill">
            <use xlink:href="#user-icon"></use>
        </svg>
    </span>
    <span class="content {if $isLogin}a-menu{/if} hide">
        {if $isLogin}
            <div class="account-name d-flex align-items-center">
                <a href="{pageUrl key='my_profile'}">
                {if $account_info.Photo}
                    <img class="mr-2"
                         src="{$smarty.const.RL_FILES_URL}{$account_info.Photo}"
                         srcset="{$smarty.const.RL_FILES_URL}{$account_info.Photo_x2} 2x" />
                {/if}
                {$isLogin}
                </a>
            </div>
            {include file='menus/user_navbar_menu.tpl'}
        {else}
            <span class="user-navbar-container">
                {include file='blocks/login_modal.tpl'}
            </span>
        {/if}
    </span>
</span>

<!-- user navigation bar end -->
