<!-- VK manager tool -->

<table class="form">
    {if $tokenInfo}
        <tr>
            <td class="name">{$lang.ap_token}</td>
            <td class="value">{$tokenInfo.token}</td>
        </tr>
    {/if}
    <tr>
        <td class="name"></td>
        <td class="value">
            <a href="{$redirectUrl}" class="login-button vk">{$lang.ap_login_via_vk}</a>
        </td>
    </tr>
</table>

<!-- VK manager tool end -->
