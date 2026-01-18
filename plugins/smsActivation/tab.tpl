<!-- sms activation tab -->

<div id="area_smsActivation" class="tab_area hide">
    <div>{$response_message}</div>

    <div id="smsActivation_box" class="pt-3">
        {if ($isLogin && $account_info.smsActivation) || $smarty.session.registration.smsActivation}
            <div class="info">{$lang.smsActivation_activated_aa}</div>
        {else}
            <div class="d-flex flex-wrap align-items-center justify-content-center">
                <div class="d-flex flex-grow-1 flex-md-grow-0 mb-2">
                    <input class="flex-grow-1"
                           placeholder="{$lang.smsActivation_code}"
                           type="text"
                           id="sms_code"
                           name="sms_code"
                           maxlength="{$config.sms_activation_code_length}" />
                    <input class="ml-2" type="button" id="sms_check" value="{$lang.smsActivation_confirm}" />
                </div>
                <div class="pl-3 mb-2">
                    <span>{$lang.smsActivation_or}</span>
                    <span class="link ml-2" id="new_code">{$lang.smsActivation_get_code}</span>
                </div>
            </div>
        {/if}
    </div>

    <script class="fl-js-dynamic">
        {literal}
        $('#new_code').flModal({
            caption: '{/literal}{$lang.warning}{literal}',
            content: '{/literal}{$lang.smsActivation_get_code_confirm}{literal}',
            prompt: 'sendSMSCode()',
            width: 'auto',
            height: 'auto'
        });

        $(document).ready(function() {
            $('#area_account').after($('#area_smsActivation'));

            $('input#sms_check').click(function() {
                checkSMSCode($('#sms_code').val());
            });
        });

        var sendSMSCode = function() {
            $('input#new_code').val(lang['loading']);
            $.getJSON(rlConfig['ajax_url'], {mode: 'smsActivationSend', lang: rlLang}, function(response) {
                if (response) {
                    if (response.status == 'OK') {
                        printMessage('notice', response.message_text);
                    } else {
                        printMessage('error', response.message_text);
                    }
                }
                $('input#new_code').val('{/literal}{$lang.smsActivation_get_code}{literal}');
            });
        }

        var checkSMSCode = function(code) {
            $('input#sms_check').val(lang['loading']);
            $.getJSON(rlConfig['ajax_url'], {mode: 'smsActivationCheck', item: code, profile: true, lang: rlLang}, function(response) {
                if (response) {
                    if (response.status == 'OK') {
                        location.href = response.url;
                        return;
                    } else {
                        printMessage('error', response.message_text);
                    }
                }
                $('input#sms_check').val('{/literal}{$lang.smsActivation_confirm}{literal}');
            });
        }
        {/literal}
    </script>
</div>
<!-- sms activation tab end -->
