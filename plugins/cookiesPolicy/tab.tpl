<!-- cookiesPolicy tab -->

{if $config.cookiesPolicy_view == 'banner'}
    {if !$smarty.cookies.cookies_policy}
        <div class="cookies-policy pt-3 pb-3 pl-2 pr-2 pt-md-4 pb-md-4 position-fixed w-100">
            <div class="point1 container mx-auto">
                <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center">
                    <div class="cookies-policy__content flex-fill mr-0 mr-md-5">{$lang.cookies_policy_content_text}</div>
                    <div class="cookies-policy__button mt-3 mt-md-0">
                        <input type="button" class="cookie-accept w-100" value="{$lang.cookies_policy_accept}" />
                    </div>
                </div>
            </div>
        </div>

        <script class="fl-js-dynamic">
        var cpBlockAllCookies  = {if $config.cp_block_all_cookies}true{else}false{/if};
        {literal}

        $(function(){
            $('input.cookie-accept').click(function(){
                createCookie('cookies_policy', true, 365);

                if (rlPageInfo.controller === 'add_listing' || cpBlockAllCookies) {
                    document.location.reload(true);
                } else {
                    $('.cookies-policy').addClass('cookies-policy_accepted');
                }
            });

            $('.cookies-policy').on('transitionend webkitTransitionEnd oTransitionEnd', function(e){
                if ($(e.target).hasClass('cookies-policy')) {
                    $('.compare-ad-container').addClass('cookies-policy-show');

                    $(this).remove();
                }
            });
        });

        {/literal}
        </script>

        <style>
        {literal}
        .cookies-policy {
            background: rgba(0,0,0,.8);
            bottom: 0;
            left: 0;
            opacity: 1;
            z-index: 100;

            transition: opacity 0.4s ease;
        }
        .cookies-policy_accepted {
            opacity: 0;
        }
        .cookies-policy__content {
            font-weight: 300;
            color: white;
            line-height: 26px;
        }
        .cookies-policy__content a {
            filter: brightness(1.8);
        }
        @media (min-width: 768px) {
            .cookies-policy__button {
                flex: 0 0 200px;
            }
        }
        .compare-ad-container:not(.cookies-policy-show) {
            display: none !important;
        }
        {/literal}
        </style>
    {/if}
{else}
    {if $config.cookiesPolicy_position && (!$config.cookiesPolicy_hide_icon || !$smarty.cookies.cookies_policy)}
        <div id="cookies_policy_{$config.cookiesPolicy_position}"
            {if $smarty.const.RL_LANG_DIR == 'rtl'}class="cp-rtl"{/if}>
            <div class="cookies_policy_icon"
                id="cookies_policy_icon_{$config.cookiesPolicy_position}">
                <span>C</span>
            </div>

            <div id="cookies_policy_big_form"
                class="cookies_policy_big_form_{$config.cookiesPolicy_position} hide">
                <div class="header">
                    <div>{$lang.cookies_policy_cookie_control}</div>
                </div>

                <div class="content">{$lang.cookies_policy_content_text}</div>

                <div class="buttons_content">
                    {if !$smarty.cookies.cookies_policy}
                        <input type="button" class="cookie_accept" value="{$lang.cookies_policy_accept}" />
                    {/if}

                    <input type="button" class="cookie_decline" value="{$lang.cookies_policy_decline}" />
                </div>
            </div>
        </div>

        <script>
        var cpConfigs = {literal}{}{/literal};

        cpConfigs.showCookieNotice = {if !$smarty.cookies.cookies_policy}true{else}false{/if};
        cpConfigs.redirectUrl      = '{$config.cookiesPolicy_redirect_url}';
        cpConfigs.removeCookieBox  = {if $config.cookiesPolicy_hide_icon}true{else}false{/if};
        cpConfigs.blockAllCookies  = {if $config.cp_block_all_cookies}true{else}false{/if};
        </script>

        {addJS file=$smarty.const.RL_PLUGINS_URL|cat:'/cookiesPolicy/static/lib.js'}
    {/if}
{/if}

<!-- cookiesPolicy tab end -->
