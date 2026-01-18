<!-- Settings handler of the AutoPoster plugin tpl -->

<script>
    const isXlsPluginAvailable = {if $plugins.export_import && $plugins.export_import|version_compare:'3.9.0':'>'}true{else}false{/if},
        isXmlPluginAvailable = {if $plugins.xmlFeeds && $plugins.xmlFeeds|version_compare:'3.5.0':'>'}true{else}false{/if};

{literal}
    $(function() {
        let $apOwnCronField = $('[name="post_config[ap_own_cron][value]"]').closest('td');

        autoPosterHandler($apOwnCronField.find('input:checked').val());

        $apOwnCronField.change(function() {
            autoPosterHandler($apOwnCronField.find('input:checked').val());
        });

        if (!isXlsPluginAvailable) {
            let $inputs = $('[name="post_config[ap_xls_frontend][value]"],[name="post_config[ap_xls_backend][value]"]');
            $inputs.filter('[value=0]').trigger('click');
            $inputs.prop('disabled', true);
        }

        if (!isXmlPluginAvailable) {
            let $inputs = $('[name="post_config[ap_xml_backend][value]"]');
            $inputs.filter('[value=0]').trigger('click');
            $inputs.prop('disabled', true);
        }
    });

    /**
     * Show/hide necessary fields of selected autoposter own cron
     */
    const autoPosterHandler = function(ownCron) {
        let $cronAdsLimit = $('[name="post_config[ap_cron_ads_limit][value]"]').filter('[type="text"]');

        if (Number(ownCron)) {
            $cronAdsLimit.prop('disabled', false).removeClass('disabled');
        } else {
            $cronAdsLimit.prop('disabled', true).addClass('disabled');
        }
    }
{/literal}</script>

<!-- Settings handler of the AutoPoster plugin tpl end -->
