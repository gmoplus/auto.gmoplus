<!-- user navigation bar -->

<span class="circle{if $isLogin} logged-in{/if}{if $new_messages} notify{/if}" id="user-navbar">
    <span class="default">{if $new_messages}<span class="count">{$new_messages}</span>{/if}</span>
    <span class="content {if $isLogin}a-menu{/if} hide">
        {if $isLogin}
            {include file='menus/user_navbar_menu.tpl'}
        {else}
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'login_modal.tpl'}
        {/if}
    </span>
</span>

<!-- user navigation bar end -->
