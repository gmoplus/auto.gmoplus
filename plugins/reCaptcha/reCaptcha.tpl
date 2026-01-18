<!-- reCaptcha tpl -->

{counter assign="recounter"}

<script>
    function afterCaptcha{$recounter}{literal}(res) {
        $('input[name=security_code{/literal}{if $captcha_id}_{$captcha_id}{/if}{literal}]').val(res +'flgcaptcha{/literal}{$captcha_id}{literal}' );
    }{/literal}
</script>

<div class="gptwdg" id="gcaptcha_widget{$recounter}" data-recaptcha-index="{$recounter}"></div>
<input type="hidden" name="security_code{if $captcha_id}_{$captcha_id}{/if}" id="{if $captcha_id}{$captcha_id}_{/if}security_code" value="" />

<!-- reCaptcha tpl end -->
