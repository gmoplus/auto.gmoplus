<!-- StreeView javascript on settings page -->

<script>
{literal}

(function(){
    var $provider  = $('select[name="post_config[street_view_provider][value]"]');
    var $googleRow = $('[name="post_config[street_view_google_key][value]"]').closest('tr');
    var $yandexRow = $('[name="post_config[street_view_yandex_key][value]"]').closest('tr');

    var streetViewHandler = function(){
        if ($provider.val() == 'google') {
            $yandexRow.hide();
            $googleRow.show();
        } else {
            $googleRow.hide();
            $yandexRow.show();
        }
    }

    $provider.change(function(){
        streetViewHandler();
    });

    streetViewHandler();
})();

{/literal}
</script>

<!-- StreeView javascript on settings page end -->
