<!-- js config of search by distance -->

{if $smarty.const.SBD_CONFIG !== true}
    <script class="fl-js-dynamic">
    var sbdConfig = {literal} { {/literal}
        countryFieldKey: '{$config.sbd_country_field}',
        defaultCountry: '{$config.sbd_default_country}',
        countryISO: []
    {literal} } {/literal};

    {foreach from=$sbd_country_iso item='iso' key='key'}
        sbdConfig.countryISO['{$key}'] = '{$iso}';
    {/foreach}
    </script>

    {php}
    define('SBD_CONFIG', true);
    {/php}
{/if}

<!-- js config of search by distance end -->
