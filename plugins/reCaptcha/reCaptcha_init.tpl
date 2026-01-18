<!-- reCaptcha_init.tpl file -->

<script class="fl-js-dynamic">{literal}
    $(function () {
        flUtil.loadScript(`${rlConfig.plugins_url}reCaptcha/static/lib.js`, function () {
            ReCaptcha.init({
                type : '{/literal}{$config.reCaptcha_type}{literal}',
                key  : '{/literal}{if $config.reCaptcha_public_key}{$config.reCaptcha_public_key}{else}sitekey{/if}{literal}',
                theme: '{/literal}{$config.reCaptcha2_theme}{literal}',
                size : '{/literal}{if $config.reCaptcha2_compact}compact{/if}{literal}',
                badge: '{/literal}{$config.reCaptcha_position}{literal}',
            });
        });
    });
{/literal}
</script>

<!-- reCaptcha_init.tpl file end -->
