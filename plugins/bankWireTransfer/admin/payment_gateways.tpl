<div id="payment_credentials">
    <input type="hidden" name="post_config[bankWireTransfer_payment_details]">

    {foreach from=$gateway_settings item='configItem'}
        {if $configItem.Key === 'bankWireTransfer_payment_details'}
            {assign var='paymentCredentials' value=$configItem.Default}
            {break}
        {/if}
    {/foreach}

    {if $allLangs|@count > 1}
        <ul class="tabs">
            {foreach from=$allLangs item='language' name='langF'}
            <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
            {/foreach}
        </ul>
    {/if}

    {foreach from=$allLangs item='language' name='langF'}
        {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
        {fckEditor name="bwt_payment_details_content_`$language.Code`" width='100%' height='140' value=$paymentCredentials[$language.Code]}
        {if $allLangs|@count > 1}</div>{/if}
    {/foreach}

    <div class="field_description" style="margin-top: 15px;">
        {phrase key='bwt_payment_details_notice' db_check=true}
        (<a href="javascript://" class="bwt-for-example">{$lang.bwt_for_example}</a>)
    </div>
</div>
<script>
{literal}

var popupPaymentDetails;

var ruLang = '';

if (rlLang == 'ru') {
    ruLang = '_ru';
}

$(document).ready(function(){
    $('textarea[name="post_config[bankWireTransfer_payment_details]"]').parent().html($('#payment_credentials'));
    $('#bankWireTransfer_payment_details').attr('name', 'post_config[bankWireTransfer_payment_details]');
    $(document).on('click', '.bwt-for-example', function() {
        bwtloadPopup();
    });
});

var bwtloadPopup = function() {
        var popupContent = $('<div>', {
                class: 'x-hidden',
                'id': 'bwt-for-example'}
            )
            .append($('<div>', {
                class: 'x-window-body',
                'style': 'padding: 10px 15px',
                'align': 'center'})
                .append($('<img>', {
                    'width': '650',
                    'src': '{/literal}{$smarty.const.RL_PLUGINS_URL}{literal}bankWireTransfer/static/for_example' + ruLang + '.png'})
                )
            );

    $('body').after(popupContent);

    popupPaymentDetails = new Ext.Window({
        title: '{/literal}{$lang.bwt_for_example}{literal}',
        applyTo: 'bwt-for-example',
        layout: 'fit',
        width: 700,
        height: 'auto',
        plain: true,
        modal: false,
        closable: true,
        closeAction : 'hide'
    });

    popupPaymentDetails.show();
}

{/literal}
</script>
