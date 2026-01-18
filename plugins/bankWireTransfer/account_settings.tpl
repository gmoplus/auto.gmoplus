<!-- bankWireTransfer plugin -->

{addJS file=$smarty.const.RL_LIBS_URL|cat:'ckeditor/ckeditor.js'}
{addJS file=$rlTplBase|cat:'js/form.js'}

<div class="shc_divider"></div>
<div class="submit-cell">
    {assign var='bwt_module' value='payment_gateways+name+bankWireTransfer'}
    <div class="name">{$lang[$bwt_module]}</div>
    <div class="field">
        <label><input type="radio" {if $smarty.post.shc.bankWireTransfer_enable == 1}checked="checked"{/if} name="shc[bankWireTransfer_enable]" value="1" />{$lang.enabled}</label>
        <label><input type="radio" {if $smarty.post.shc.bankWireTransfer_enable == 0 || !$smarty.post.shc.bankWireTransfer_enable}checked="checked"{/if} name="shc[bankWireTransfer_enable]" value="0" />{$lang.disabled}</label>
    </div>
</div>
<div class="submit-cell" style="min-width: 400px;">
    <div class="name">{$lang.bwt_payment_details}</div>
    <div class="field single-field">
        <textarea name="shc[bankWireTransfer_details]" rows="5" id="textarea_bankWireTransfer_details">{$smarty.post.shc.bankWireTransfer_details}</textarea>
        <script>
        if (!window.textarea_fields['textarea_bankWireTransfer_details']) {literal} { {/literal}
            window.textarea_fields['textarea_bankWireTransfer_details'] = {literal} { {/literal}
                type: 'html',
                length: '500'
            {literal} } {/literal};
        {literal} } {/literal}
        </script>
        <div class="mt-3" id="bwt-for-example">
            {$lang.bwt_payment_details_notice}
            (<a href="javascript://" class="bwt-for-example">{$lang.bwt_for_example}</a>)
        </div>
    </div>
</div>

<script class="fl-js-dynamic">
{literal}

$(function(){
    flForm.fields();
    $(document).on('click', '.bwt-for-example', function() {
        var popupContent = $('<div>', {
                class: 'align-center'}
            )
            .append($('<img>', {
                'width': '650', 
                'src': '{/literal}{$smarty.const.RL_PLUGINS_URL}{literal}bankWireTransfer/static/for_example.png'})
            );

        flUtil.loadScript([
            rlConfig['tpl_base'] + 'components/popup/_popup.js',
        ], function(){
            $('#bwt-for-example').popup({
                click: false,
                scroll: true,
                closeOnOutsideClick: true,
                content: popupContent,
                caption: '{/literal}{$lang.bwt_for_example}{literal}'
            });
        });
    });
});

{/literal}
</script>
<!-- end bankWireTransfer plugin -->
