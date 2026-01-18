<!-- Settings handler of the Claim Listing plugin tpl -->

<script>{literal}
$(function () {
    let $smsService = $('[name="post_config[cl_sms_service][value]"]');

    smsServiceHandler();

    $smsService.change(function () {
        smsServiceHandler();
    });

    /**
     * Show/hide necessary fields of selected SMS provider
     */
    function smsServiceHandler () {
        let selectedService            = $smsService.find('option:selected').val(),
            $clickatellRestApiKey      = $('[name="post_config[cl_clickatell_api_key_rest][value]"]'),
            $clickatellRestApiKeyTr    = $clickatellRestApiKey.closest('tr'),
            $clickatellRestApiDivider  = $clickatellRestApiKeyTr.prev(),
            $clickatellRestApiPhone    = $clickatellRestApiKeyTr.next(),
            $clickatellHttpApiDivider  = $clickatellRestApiPhone.next(),
            $clickatellHttpApiKey      = $clickatellHttpApiDivider.next(),
            $clickatellHttpApiUsername = $clickatellHttpApiKey.next(),
            $clickatellHttpApiPassword = $clickatellHttpApiUsername.next(),
            $smsRuApiKey               = $('[name="post_config[cl_sms_ru_api_key_rest][value]"]'),
            $smsRuApiKeyTr             = $smsRuApiKey.closest('tr'),
            $smsRuApiDivider           = $smsRuApiKeyTr.prev(),
            clickatellCondition        = selectedService === 'SMS.RU' ? 'hide' : 'show',
            smsRuCondition             = selectedService === 'Clickatell' ? 'hide' : 'show';

        $clickatellRestApiDivider[clickatellCondition]();
        $clickatellRestApiKeyTr[clickatellCondition]();
        $clickatellRestApiPhone[clickatellCondition]();
        $clickatellHttpApiDivider[clickatellCondition]();
        $clickatellHttpApiKey[clickatellCondition]();
        $clickatellHttpApiUsername[clickatellCondition]();
        $clickatellHttpApiPassword[clickatellCondition]();

        $smsRuApiKeyTr[smsRuCondition]();
        $smsRuApiDivider[smsRuCondition]();
    }
});
{/literal}</script>

<!-- Settings handler of the Claim Listing plugin tpl end -->
