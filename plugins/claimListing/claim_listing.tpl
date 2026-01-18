<!-- claim listing tpl -->

{assign var="sPOST" value=$smarty.post}

{if $smarty.get.id && $config.cl_module && ($listing_info.cl_direct || $listing_info.Owner_info.cl_direct)}
    {if !$message}
        {if $listing_info.ID}
            {if $config.cl_phone_method
                && $listing_info[$config.cl_phone_field]
                && (
                    ($config.cl_sms_service === 'Clickatell'
                        && (($config.cl_clickatell_username && $config.cl_clickatell_password && $config.cl_clickatell_api_id)
                            || $config.cl_clickatell_api_key_rest)
                    )
                    || ($config.cl_sms_service === 'SMS.RU' && $config.cl_sms_ru_api_key_rest)
                )
            }
                {assign var='cl_phone_available' value=true}
            {/if}

            {if $config.cl_email_method && $listing_info[$config.cl_email_field]}
                {assign var='cl_email_available' value=true}
            {/if}

            <div class="claim_listing content-padding">
                <form method="post" action="{pageUrl key='claim_listing' vars='id='|cat:$listing_info.ID}" enctype="multipart/form-data">
                    <!-- 1 step. selection of confirmation method -->
                    <div class="submit-cell clearfix">
                        <div class="name">{$lang.cl_select_confirm_method}</div>
                        <div class="field">
                            <select name="claim_method" style="vertical-align: top;">
                                {if $cl_phone_available || $cl_email_available}
                                    <option value="0" {if !$sPOST.claim_method}selected="selected"{/if}>{$lang.select}</option>
                                {/if}
                                {if $cl_phone_available}
                                    <option value="phone" {if $sPOST.claim_method == 'phone'}selected="selected"{/if}>{$lang.cl_method_phone}</option>
                                {/if}
                                {if $cl_email_available}
                                    <option value="email" {if $sPOST.claim_method == 'email'}selected="selected"{/if}>{$lang.cl_method_email}</option>
                                {/if}
                                <option value="form" {if $sPOST.claim_method == 'form'}selected="selected"{/if}>{$lang.cl_method_form}</option>
                            </select>

                            <span class="send {if !$sPOST.claim_method || ($sPOST.claim_method && $sPOST.claim_method == 'form')} hide{/if}">
                                <a href="javascript:void(0)" class="button" rel="nofollow" data-send="{$lang.send}">{$lang.send}</a>
                            </span>

                            <div class="sms_email_notice hide" style="margin-top: 8px"></div>
                        </div>
                    </div>
                    <!-- 1 step. selection of confirmation method end -->

                    <!-- 2 step. checking confirmation code or filling form -->
                    <div class="submit-cell checking_code clearfix{if !$sPOST.claim_method || $sPOST.claim_method == 'form'} hide{/if}">
                        <div class="name">{$lang.cl_verify_code}</div>
                        <div class="field">
                            <input type="text" name="received_code" class="numeric" maxlength="4" style="vertical-align: top;" value="{if $sPOST.claim_method && $sPOST.claim_method != 'form'}{$sPOST.received_code}{/if}">
                            <a href="javascript: void(0)" class="button verify" rel="nofollow" data-verify="{$lang.cl_verify_code_button}">{$lang.cl_verify_code_button}</a>
                        </div>
                    </div>

                    <div class="submit-cell cl_form_method clearfix {if (!$sPOST.claim_method  && ($cl_phone_available || $cl_email_available)) || ($sPOST.claim_method && $sPOST.claim_method != 'form')}hide{/if}">
                        <div class="name">{$lang.cl_image}</div>
                        <div class="field">
                            <div class="file-input">
                                <input class="file" type="file" name="attached_img" style="cursor: pointer;" />
                                <input type="text" class="file-name" name="" />
                                <span>{$lang.choose}</span>
                            </div>
                            <div style="margin-top: 5px">{$lang.cl_image_description}</div>
                        </div>
                    </div>
                    <!-- 2 step. checking confirmation code or filling form end -->

                    <!-- 3 step. registration or login -->
                    {if !$isLogin}
                        <div class="submit-cell authorization{if (!$sPOST.log_username && !$sPOST.log_password && !$sPOST.reg_username && !$sPOST.reg_email) && ($cl_phone_available || $cl_email_available)} hide{/if}">
                            <div class="name"><div class="content-padding">{$lang.authorization}</div></div>
                            <div class="field light-inputs">
                                <div class="auth">{strip}
                                    <div class="cell">
                                        <div>
                                            <div class="caption">{$lang.sign_in}</div>

                                            <div class="name">{if $config.account_login_mode == 'email'}{$lang.mail}{else}{$lang.username}{/if}</div>
                                            <input class="w210" type="text" name="log_username" maxlength="25" value="{$sPOST.log_username}" />

                                            <div class="name">{$lang.password}</div>
                                            <input class="w210" type="password" name="log_password" maxlength="25" />

                                            <div style="padding-top: 15px;">
                                                {$lang.forgot_pass}&nbsp;
                                                <a target="_blank" title="{$lang.remind_pass}" href="{pageUrl key='remind'}">{$lang.remind}</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="divider">{$lang.or}</div>
                                    <div class="cell">
                                        {assign var='selected_atype' value=''}

                                        <div>
                                            <div class="caption">{$lang.sign_up}</div>

                                            {if $quick_types|@count <= 1}
                                                <div class="name">{$lang.your_name}</div>
                                                <input class="w210"
                                                    type="text"
                                                    name="reg_username"
                                                    maxlength="100"
                                                    value="{if $sPOST.reg_username}{$sPOST.reg_username}{/if}" />
                                            {/if}

                                            <div class="name">{$lang.your_email}</div>
                                            <input class="w210"
                                                type="text"
                                                name="reg_email"
                                                maxlength="150"
                                                value="{if $listing_info[$config.cl_email_field] && !$sPOST.reg_email}{$listing_info[$config.cl_email_field]}{elseif $sPOST.reg_email}{$sPOST.reg_email}{/if}"  />

                                            {if $quick_types|@count > 1}
                                                <div class="name">{$lang.account_type}</div>
                                                <select class="w120" name="reg_type">
                                                    {foreach from=$quick_types item='quick_reg_type' name='acTypes'}
                                                        {if $quick_reg_type.Status !== 'active'}
                                                            {continue}
                                                        {/if}

                                                        {if $smarty.post.register.type && $smarty.post.register.type == $quick_reg_type.ID}
                                                            {assign var='selected_atype' value=$quick_reg_type.Key}
                                                        {elseif !$smarty.post.register.type && $smarty.foreach.acTypes.first}
                                                            {assign var='selected_atype' value=$quick_reg_type.Key}
                                                        {/if}

                                                        <option value="{$quick_reg_type.ID}"
                                                            {if ($smarty.post.register.type && $smarty.post.register.type == $quick_reg_type.ID)
                                                                || (!$smarty.post.register.type && $smarty.foreach.acTypes.first)}selected="selected"{/if}
                                                            data-key="{$quick_reg_type.Key}">
                                                            {$quick_reg_type.name}
                                                        </option>
                                                    {/foreach}
                                                </select>

                                                {foreach from=$quick_types item='quick_reg_type' name='acName'}
                                                    {if $quick_reg_type.desc}
                                                        <div class="qtip_cont">{$quick_reg_type.desc}</div>
                                                        <img class="qtip {if !$smarty.foreach.acName.first}hide {/if}sc_{$quick_reg_type.ID}"
                                                            src="{$rlTplBase}img/blank.gif"
                                                            alt="" />
                                                    {/if}
                                                {/foreach}

                                                <script class="fl-js-dynamic">{literal}
                                                $(function(){
                                                    $('[name="reg_type"]').change(function(){
                                                        $('img.qtip').hide();
                                                        $('img.sc_' + $(this).val()).show();
                                                    });
                                                });
                                                {/literal}</script>
                                            {elseif $quick_types|@count == 1}
                                                {assign var='selected_atype' value=$quick_types.0.Key}
                                                <input type="hidden" name="reg_type" value="{$quick_types.0.ID}" />
                                            {/if}
                                        </div>
                                    </div>
                                {/strip}</div>
                            </div>
                        </div>
                    {/if}
                    <!-- 3 step. registration or login end -->

                    {assign var='sReplace' value=`$smarty.ldelim`count`$smarty.rdelim`}
                    {if $cl_phone_listings}
                        <div class="submit-cell clearfix cl_phone_listings {if $sPOST.claim_method != 'phone' || !$sPOST.claim_method}hide{/if}">
                            <div class="name">
                                {$lang.cl_other_listings}
                                <img class="qtip"
                                    title="{$lang.cl_by_same_phone_notice|replace:$sReplace:$cl_phone_listings}"
                                    src="{$rlTplBase}img/blank.gif" />
                            </div>
                            <div class="field inline-fields">
                                <span class="custom-input">
                                    <label title="{$lang.yes}">
                                        <input type="radio" name="phone_listings" {if $sPOST.phone_listings == '1'}checked="checked"{/if} value="1" />
                                        {$lang.yes}
                                    </label>
                                </span>
                                <span class="custom-input">
                                    <label title="{$lang.no}">
                                        <input type="radio" name="phone_listings" {if $sPOST.phone_listings == '0' || !$sPOST.phone_listings}checked="checked"{/if} value="0" />
                                        {$lang.no}
                                    </label>
                                </span>
                            </div>
                        </div>
                    {/if}

                    {if $cl_email_listings}
                        <div class="submit-cell clearfix cl_email_listings {if $sPOST.claim_method != 'email' || !$sPOST.claim_method}hide{/if}">
                            <div class="name">
                                {$lang.cl_other_listings}
                                <img class="qtip"
                                    title="{$lang.cl_by_same_email_notice|replace:$sReplace:$cl_email_listings}"
                                    src="{$rlTplBase}img/blank.gif" />
                            </div>
                            <div class="field inline-fields">
                                <span class="custom-input">
                                    <label title="{$lang.yes}">
                                        <input type="radio" name="email_listings" {if $sPOST.email_listings == '1'}checked="checked"{/if} value="1" />
                                        {$lang.yes}
                                    </label>
                                </span>
                                <span class="custom-input">
                                    <label title="{$lang.no}">
                                        <input type="radio" name="email_listings" {if $sPOST.email_listings == '0' || !$sPOST.email_listings}checked="checked"{/if} value="0" />
                                        {$lang.no}
                                    </label>
                                </span>
                            </div>
                        </div>
                    {/if}

                    <div class="submit-cell buttons claim_ad clearfix{if !$sPOST.claim_method && ($cl_phone_available || $cl_email_available)} hide{/if}">
                        <div class="name"></div>
                        <div class="field">
                            <input type="button" value="{$lang.cl_claim_ad}" />
                        </div>
                    </div>
                </form>
            </div>
        {/if}
    {else}
        <div class="content-padding">{$message}</div>
    {/if}
{/if}

{assign var='sReplace' value=`$smarty.ldelim`field`$smarty.rdelim`}
{assign var='missing_field' value=$lang.notice_field_empty|replace:$sReplace:'error_field'}

<script class="fl-js-dynamic">
var cl_listing_id = '{$listing_info.ID}';

{literal}
$(document).ready(function(){
    // send and verify code for confirmation
    claimSendCode();
    claimCheckCode();

    // handlers of selection claim method and filling form
    claimFormHandler();
    claimMethodHandler();

    // file input click handler
    claimFileFieldAction();

    flynax.qtip();
});

var claimSendCode = function(){
    $('.send').click(function(){
        var $sendButton   = $('.send > a');
        var $checkingCode = $('.checking_code');

        $sendButton.text(lang['loading']).addClass('disabled').attr('disabled', true);

        $.post(
            rlConfig['ajax_url'],
            {
                mode: 'claimSendCode',
                item: {
                    method: $('[name="claim_method"] option:selected').val(),
                    id    : cl_listing_id
                },
                lang: rlLang
            },
            function(response){
                if (response && (response.status || response.message)) {
                    $sendButton.text($sendButton.data('send')).removeClass('disabled').removeAttr('disabled');

                    if (response.status == 'OK') {
                        $checkingCode.show();
                    } else if (response.status == 'ERROR') {
                        $checkingCode.hide();
                        setTimeout(function(){ printMessage('error', response.message); }, 500);
                    }
                }
            },
            'json'
        );
    });
}

var claimCheckCode = function(){
    $('.verify').click(function(){
        var $checkButton     = $('.verify');
        var received_code    = $('[name="received_code"]').val();
        var $clPhoneListings = $('.cl_phone_listings');
        var $clEmailListings = $('.cl_email_listings');
        var selected_method  = $('[name="claim_method"] option:selected').val();

        if (!received_code) {
            printMessage('warning', '{/literal}{$lang.cl_code_empty}{literal}');
        } else {
            $checkButton.text(lang['loading']).addClass('disabled').attr('disabled', true);

            $.post(
                rlConfig['ajax_url'],
                {
                    mode: 'claimCheckCode',
                    item: $('[name="received_code"]').val(),
                    lang: rlLang
                },
                function(response){
                    if (response && (response.status || response.message)) {
                        $checkButton.text($checkButton.data('verify')).removeClass('disabled').removeAttr('disabled');

                        if (response.status == 'OK') {
                            setTimeout(function(){ printMessage('notice', response.message); }, 500);
                            $('.authorization, .claim_ad').show();

                            if (selected_method == 'phone') {
                                $clPhoneListings.removeClass('hide');
                                $clEmailListings.addClass('hide');
                            } else {
                                $clEmailListings.removeClass('hide');
                                $clPhoneListings.addClass('hide');
                            }

                        } else if (response.status == 'ERROR') {
                            setTimeout(function(){ printMessage('error', response.message); }, 500);
                        }
                    }
                },
                'json'
            );
        }
    });
}

var claimFormHandler = function(){
    var isLogin = '{/literal}{$isLogin}{literal}';

    $('.claim_listing [type="button"]').click(function(){
        var claim_method  = $('[name="claim_method"]').find('option:selected').val();
        var attached_img  = $('[name="attached_img"]').val();
        var received_code = $('[name="received_code"]').val();
        var error         = '{/literal}{$missing_field}{literal}';

        /* missing attached Image */
        if (claim_method == 'form' && attached_img == '') {
            error = error.replace('error_field', '{/literal}"<b>{$lang.cl_image}</b>"{literal}');
            printMessage('error', error, 'attached_img');
            return false;
        }

        /* file isn't image */
        if (claim_method == 'form' && !attached_img.match(/\.(jpg|JPG|jpeg|JPEG|png|PNG|gif|GIF)$/)) {
            printMessage('error', '{/literal}{$lang.not_image_file}{literal}', 'attached_img');
            return false;
        }

        /* missing received code */
        if (claim_method != 'form' && received_code == '') {
            error = error.replace('error_field', '{/literal}"<b>{$lang.cl_verify_code}</b>"{literal}');
            printMessage('error', error, 'received_code');
            return false;
        }

        if (isLogin) {
            $('.claim_ad input').val(lang['loading']).addClass('disabled').attr('disabled', true);
            $('.claim_listing form').submit();
        } else {
            if (($('[name="log_username"]').val() && $('[name="log_password"]').val())
                && (
                    ($('[name="reg_username"]').val() && !$('[name="reg_email"]').val())
                    || (!$('[name="reg_username"]').val() && $('[name="reg_email"]').val())
                )
            ) {
                $('[name="reg_username"], [name="reg_email"]').val('');
            }

            var reg_username      = $('[name="reg_username"]').val();
            var reg_email         = $('[name="reg_email"]').val();
            var log_username      = $('[name="log_username"]').val();
            var log_password      = $('[name="log_password"]').val();
            var countAccountTypes = Number({/literal}{if $quick_types}{$quick_types|@count}{else}0{/if}{literal});
            var missing_field     = '';

            /* check registration process */
            if ((reg_username && !reg_email && countAccountTypes === 1)
                || (!reg_username && reg_email && countAccountTypes === 1)
                || (countAccountTypes > 1 && !reg_email)
            ) {
                // missing email value in registration form
                if (reg_username && !reg_email) {
                    missing_field = 'reg_email';
                }

                // missing username value in registration form
                if (!reg_username && reg_email) {
                    missing_field = 'reg_username';
                }
            }

            /* login process */
            if ((!log_username && log_password) || (log_username && !log_password)) {
                // missing username value in login form
                if (!log_username && log_password) {
                    missing_field = 'log_username';
                }

                // missing password value in login form
                if (log_username && !log_password) {
                    missing_field = 'log_password';
                }
            }

            if (missing_field != '') {
                if (missing_field == 'reg_username' || missing_field == 'log_username') {
                    field_name = '{/literal}"<b>{$lang.username}</b>"{literal}';
                }

                if (missing_field == 'reg_email') {
                    field_name = '{/literal}"<b>{$lang.your_email}"</b>{literal}';
                }

                if (missing_field == 'log_password') {
                    field_name = '{/literal}"<b>{$lang.password}"</b>{literal}';
                }

                error = error.replace('error_field', field_name);
                printMessage('error', error, missing_field);
            } else {
                if (!reg_username && !reg_email && !log_username && !log_password) {
                    printMessage('error', '{/literal}{$lang.quick_signup_fail}{literal}');
                } else {
                    $('.claim_ad input').val(lang['loading']).addClass('disabled').attr('disabled', true);
                    $('.claim_listing form').submit();
                }
            }
        }
    });

    $('[name="reg_username"],[name="reg_email"]').keydown(function(){
        $('[name="log_username"],[name="log_password"]').val('');
    });
    $('[name="log_username"],[name="log_password"]').keydown(function(){
        $('[name="reg_username"],[name="reg_email"]').val('');
    });
}

var claimMethodHandler = function(){
    $('[name="claim_method"]').change(function(){
        $('.checking_code,.authorization,.claim_ad').fadeOut();
        $('[name="received_code"]').val('');

        var selected_method = $(this).find('option:selected').val();

        if (selected_method != 0) {
            if (selected_method != 'form') {
                $('.send').fadeIn('fast');
                $('.cl_form_method').fadeOut('fast');

                /* build and show notice with encoded recipient */
                var notice_recipient  = '';
                var encoded_recipient = '';
                var sms_email_notice  = '{/literal}{$lang.cl_sms_email_notice}{literal}';

                if (selected_method === 'phone') {
                    notice_recipient = '{/literal}{$plainPhoneNumber}{literal}';
                } else {
                    notice_recipient = '{/literal}{$listing_info[$config.cl_email_field]}{literal}';
                }

                /* hide part of symbols */
                if (selected_method == 'phone') {
                    for (var i = 0; i <= notice_recipient.length - 5; i++) {
                        encoded_recipient += '*';
                    };

                    encoded_recipient = encoded_recipient + notice_recipient.substr(notice_recipient.length - 4);
                } else {
                    var parsed_email = notice_recipient.match(/^([^@]*)@/);
                    var name = parsed_email[1];

                    if (name.length <= 2) {
                        for (var i = 0; i <= name.length - 1; i++) {
                            encoded_recipient += '*';
                        };
                    } else {
                        if (name.length <= 4) {
                            for (var i = 0; i <= name.length - 1; i++) {
                                encoded_recipient += i == 0 ? name[i] : '*';
                            };
                        } else {
                            for (var i = 0; i <= name.length - 1; i++) {
                                encoded_recipient += i >= 0 && i <= 2 ? name[i] : '*';
                            };
                        }
                    }

                    encoded_recipient = encoded_recipient + notice_recipient.substr(parsed_email[0].length - 1);
                }
                sms_email_notice = sms_email_notice.replace('{notice_recipient}', encoded_recipient);

                $('.sms_email_notice').text(sms_email_notice).removeClass('hide');
            } else {
                $('.send').fadeOut('fast');
                $('.cl_form_method').fadeIn('fast');
                $('.authorization,.claim_ad').slideDown('fast');
                $('.sms_email_notice, .cl_phone_listings, .cl_email_listings').addClass('hide');
            }
        } else {
            $('.cl_form_method,.send').slideUp('fast');
            $('.sms_email_notice, .cl_phone_listings, .cl_email_listings').addClass('hide');
        }
    });
}

var claimFileFieldAction = function(){
    $('.file-input input[type=file]')
        .unbind('change')
        .bind('change', function(){
            var path = $(this).val().split('\\');
            $(this).parent().find('input[type=text]')
                .removeClass('error')
                .val(path[path.length - 1]);
        });
}
{/literal}</script>

<!-- claim listing end -->
