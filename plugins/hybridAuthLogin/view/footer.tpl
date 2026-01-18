<!-- hybridAuth footer -->

{include file=$smarty.const.RL_PLUGINS|cat:'hybridAuthLogin/static/icons.svg'}

<div id="ha-popup-source" class="hide">
    {if $smarty.session.ha_login_fail.show_modal}
        <div class="ha-modal-notice">
            {$lang.ha_email_doesnt_exist}
        </div>
    {/if}
    <form class="ha-validating-form" action="" method="post">
        <div class="tmp-dom">
            <div class="submit-cell">
                <div class="field">
                    <select name="ha-account-type" id="ha-account-type-selector" data-validate="require" class="ha-width-100">
                        <option value="">{$lang.ha_select_account_type}</option>
                    </select>
                </div>
            </div>

            <div class="ha-gender-field hide"></div>

            <div class="ha-agreements-container hide">
                <div class="submit-cell">
                    <div class="field"></div>
                </div>
            </div>

            <div class="submit-cell">
                <div class="field">
                    <input id="ha-submit" data-role="submit" class="ha-width-100" type="button" value="{$lang.login}" />
                </div>
            </div>
        </div>
    </form>
</div>

<div id="ha-password-verify-source" class="hide">
    <div class="ha-modal-notice">
        {$lang.ha_user_isnt_synchonized}
    </div>
    <div class="submit-cell">
        <div class="field">
            <input id="ha-verify-password" type="password" value="" placeholder="{$lang.password}">
        </div>
    </div>
    <div class="submit-cell">
        <div class="field">
            <input id="ha-verify-submit" data-role="submit" class="ha-width-100" type="button" value="{$lang.send}" />
        </div>
    </div>
</div>

<script class="fl-js-dynamic">
    var ha_autoShowModal = {if $smarty.session.ha_login_fail.show_modal}true{else}false{/if};
    var ha_showVerifyModal = {if $smarty.session.ha_non_verified}true{else}false{/if};
    var ha_failedProvider = {if $smarty.session.ha_login_fail.provider}"{$smarty.session.ha_login_fail.provider}"{else}""{/if};
    var ha_isEscort = {if $smarty.const.IS_ESCORT === true}true{else}false{/if};
    var haLang = [];
    haLang['notice_field_empty'] = '{$lang.notice_field_empty}';
    haLang['ha_social_login'] = '{$lang.ha_social_login}';
    haLang['ha_verify_account'] = '{$lang.ha_verify_account}';
    haLang['ha_cant_synchronize'] = '{$lang.ha_cant_synchronize}';
    haLang['ha_account_type'] = '{$lang.account_type}';
    lang['required_fields'] = '{$lang.required_fields}';

    {literal}
    $(document).ready(function () {
        var hybridAuth = new HybridAuthClass();
        if (ha_autoShowModal) {
            hybridAuth.isEscort = ha_isEscort;

            var $providerButton = $('.ha-' + ha_failedProvider + '-provider:first');

            hybridAuth.clickOnSocialIcons($providerButton);
        }

        if (ha_showVerifyModal) {
            hybridAuth.showVerifyModal();
        }
    });
    {/literal}
</script>

<!-- hybridAuth footer end -->
