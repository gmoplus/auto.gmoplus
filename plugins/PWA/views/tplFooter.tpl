<!-- PWA iOS banner tpl -->
<div id="pwa-ios-banner" class="hide">
    <button class="pwa-banner-close">
        <svg width="14" height="14" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg"
             class="BaseTooltip_closeIcon_1X4sK">
            <path d="M13.7.3c-.4-.4-1-.4-1.4 0L7 5.6 1.7.3C1.3-.1.7-.1.3.3c-.4.4-.4 1 0 1.4L5.6 7 .3 12.3c-.4.4-.4 1 0 1.4.2.2.4.3.7.3.3 0 .5-.1.7-.3L7 8.4l5.3 5.3c.2.2.5.3.7.3.2 0 .5-.1.7-.3.4-.4.4-1 0-1.4L8.4 7l5.3-5.3c.4-.4.4-1 0-1.4z" fill="currentColor"></path>
        </svg>
    </button>
    <div>
        <div class="pwa-banner-text-container">
            <svg width="58" height="58" viewBox="0 0 58 58" xmlns="http://www.w3.org/2000/svg" class="pwa-add-banner-icon">
                <g fill="none" fill-rule="evenodd">
                    <rect fill="#686871" fill-rule="nonzero" width="58" height="58" rx="12"></rect>
                    <path d="M39.5 30.75h-8.75v8.75c0 1.288-.462 2.333-1.75 2.333s-1.75-1.045-1.75-2.333v-8.75H18.5c-1.29 0-2.333-.46-2.333-1.75 0-1.288 1.044-1.75 2.333-1.75h8.75V18.5c0-1.288.462-2.333 1.75-2.333s1.75 1.045 1.75 2.333v8.75h8.75c1.288 0 2.333.462 2.333 1.75 0 1.29-1.045 1.75-2.333 1.75" fill="#FFF"></path>
                </g>
            </svg>
            <span class="pwa-banner-notice-box">
                {$lang.pwa_install_webapp_1}
                <svg width="16" height="16" viewBox="0 0 20 27.706" xmlns="http://www.w3.org/2000/svg" class="HomescreenTooltip_tooltipContentShareIcon_3pTxh"><path d="M19.5 27.206H.5v-19h7v1h-6v17h17v-17h-6v-1h7v19zm-9-9.5h-1V2.592L6.214 5.884 5.5 5.175 9.969.706 14.5 5.175l-.714.709L10.5 2.646v15.06z" fill="#007aff" data-name="Action 2" class="cls-1"></path></svg>
                {$lang.pwa_install_webapp_2}
            </span>
        </div>
    </div>
    <span data-placement="bottom" class="pwa-banner-arrow"></span>
</div>

{assign var='pwaLogo' value=$rlTplBase|cat:'img/logo.png'}
{assign var='pwa2xLogo' value=$rlTplBase|cat:'img/@2x/logo.png'}

{* SVG logo in rainbow templates *}
{if is_file($smarty.const.RL_ROOT|cat:'templates/'|cat:$config.template|cat:'/img/logo.svg')}
    {assign var='pwaLogo' value=$rlTplBase|cat:'img/logo.svg'}
    {assign var='pwa2xLogo' value=''}
{/if}

<script class="fl-js-dynamic">
    var pwaConfig          = [];
    pwaConfig.vapid_public = '{$config.pwa_vapid_public}';
    pwaConfig.assets       = ['{$pwaLogo}', '{$pwa2xLogo}'];
    pwaConfig.rlUrlHome    = '{$smarty.const.RL_URL_HOME}';

    lang.footer_menu_mobile_apps = '{$lang.footer_menu_mobile_apps}';
</script>
<!-- PWA iOS banner tpl end -->
