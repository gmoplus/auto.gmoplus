<!-- Facebook manager tool -->
<table class="form">
    {if $tokenInfo}
        <tr>
            <td class="name">{$lang.ap_facebook_application}</td>
            <td class="value">{$tokenInfo.application}</td>
        </tr>
        <tr>
            <td class="name">{$lang.ap_facebook_token_expire}</td>
            <td class="value">{if $tokenInfo.expiredAt}{$tokenInfo.expiredAt}{else}{$lang.ap_never}{/if}</td>
        </tr>
    {/if}
    <tr>
        <td class="name"></td>
        <td class="value">
            <a href="{$redirectUrl}" class="login-button facebook">{$lang.ap_login_via_facebook}</a>
        </td>
    </tr>
</table>
<!-- Facebook manager tool end -->
