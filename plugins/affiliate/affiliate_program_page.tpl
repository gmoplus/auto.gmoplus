<!-- affiliate program page tpl -->

{assign var='aff_terms_of_use_page_name' value='pages+name+aff_terms_of_use_program_page'}
{if $config.mod_rewrite}
    {assign var='aff_terms_of_use_link' value=$rlBase|cat:$pages.aff_terms_of_use_program_page|cat:'.html'}
{else}
    {assign var='aff_terms_of_use_link' value=$rlBase|cat:'index.php?page='|cat:$pages.aff_terms_of_use_program_page}
{/if}
{assign var='aff_terms_of_use_link' value='<a class="aff_terms_of_use_link" href="'|cat:$aff_terms_of_use_link|cat:'" target="_blank" title="'|cat:$lang[$aff_terms_of_use_page_name]|cat:'">'|cat:$lang[$aff_terms_of_use_page_name]|cat:'</a>'}

<!-- content of the Affiliate page -->
<div class="content-padding affiliate">
    {assign var="affiliate_page_static_content" value="pages+content+aff_program_page"}
    {$lang[$affiliate_page_static_content]}
</div>
<!-- content of the Affiliate page end -->

<!-- login/registration process only for affiliate accounts -->
{if !$isLogin || ($isLogin && $account_info.Type != 'affiliate')}
    <div class="affiliate">
        <div class="auth">{strip}
            <div class="cell">
                <div>
                    <div class="caption">{$lang.sign_in}</div>
                    <div>
                        {if $account_info.Type != 'affiliate'}
                            {assign var="isLogin" value=false}

                            {php}
                                $GLOBALS['config']['security_login_attempt_user_module_tmp'] = $GLOBALS['config']['security_login_attempt_user_module'];
                                $GLOBALS['config']['security_login_attempt_user_module'] = false;
                            {/php}
                        {/if}

                        {include file='menus'|cat:$smarty.const.RL_DS|cat:'account_menu.tpl'}

                        {if $account_info.Type != 'affiliate'}
                            {assign var="isLogin" value=true}

                            {php}
                                $GLOBALS['config']['security_login_attempt_user_module'] = $GLOBALS['config']['security_login_attempt_user_module_tmp'];
                                unset($GLOBALS['config']['security_login_attempt_user_module_tmp']);
                            {/php}
                        {/if}
                    </div>
                </div>
            </div>
            <div class="divider">{$lang.or}</div>
            <div class="cell">
                <div>
                    <div class="caption">{$lang.sign_up}</div>
                    <div>
                        <form class="register-form-affiliate" action="" method="post">
                            <input type="text" name="register[name]" maxlength="100" value="{$smarty.post.register.name}" placeholder="{$lang.your_name}" />
                            <input type="text" name="register[email]" maxlength="150" value="{$smarty.post.register.email}" placeholder="{$lang.your_email}" />

                            <div class="name accept_checkbox">
                                <label><input {if $smarty.post.register.accept == 'yes'}checked="checked"{/if} type="checkbox" name="register[accept]" value="yes" class="policy" /> {$lang.aff_affiliate_accept} {$aff_terms_of_use_link}</label>
                            </div>

                            <input name="affiliate_join_button" {if !$smarty.post.register.accept}disabled="disabled" class="disabled"{/if} type="submit" value="{$lang.registration}" />
                        </form>
                    </div>
                </div>
            </div>
        {/strip}</div>
    </div>

    {assign var='sReplace' value=`$smarty.ldelim`field`$smarty.rdelim`}
    {assign var='missing_field' value=$lang.notice_field_empty|replace:$sReplace:'error_field'}

    <script class="fl-js-dynamic">{literal}
        $(function() {
            let $button = $('input[name="affiliate_join_button"]'),
                $form   = $('form.register-form-affiliate'),
                $name   = $('input[name="register[name]"]'),
                $email  = $('input[name="register[email]"]');

            $('input[name="register[accept]"]').click(function() {
                if ($(this).is(':checked')) {
                    $button.removeAttr('disabled').removeClass('disabled');
                } else {
                    $button.attr('disabled', 'disabled').addClass('disabled');
                }
            });

            $('<input/>', {'type' : 'hidden', 'name' : 'affiliate_log_form', 'value' : '1'}).appendTo(
                'div.auth form.login-form'
            );

            let error = '{/literal}{$missing_field}{literal}', errorField = '', fieldName = '';

            $button.click(function() {
                $form.submit(function() { return false; });

                let nameValue = $name.val(), emailValue = $email.val();

                if (nameValue === '' || emailValue === '') {
                    if (nameValue === '' && emailValue !== '') {
                        fieldName = '{/literal}"<b>{$lang.your_name}</b>"{literal}';
                        errorField = 'register[name]';
                    }

                    if (nameValue !== '' && emailValue === '') {
                        fieldName = '{/literal}"<b>{$lang.your_email}</b>"{literal}';
                        errorField = 'register[email]';
                    }

                    if (nameValue === '' && emailValue === '') {
                        fieldName = '';
                        errorField = 'register[name],register[email]';
                    }

                    printMessage('error', error.replace('error_field', fieldName), errorField);
                } else {
                    $form.off('submit').submit(function() { return true; });
                }
            })
        });
    {/literal}</script>
{else}
    <a class="button" title="{$lang.aff_go_to_account}" href="{$general_stats_url}">{$lang.aff_go_to_account}</a>
{/if}
<!-- login/registration process only for affiliate accounts end -->

<!-- affiliate program page end -->
