<div id="vin-audit-report" class="hide">
    <div class="vin-report-loader">{$lang.loading}</div>
    <div class="vin-audit-report-container hide">
        <iframe src="#" scrolling="auto" height="600" frameborder="0" width="100%"></iframe>
    </div>
</div>
<script class="fl-js-dynamic">
    var csLang = [];
    csLang['cs_fetching_vin'] = '{$lang.cs_fetching_vin}';
    csLang['cs_something_went_wrong'] = '{$lang.cs_something_went_wrong}';
</script>

<script class="fl-js-static">{literal}
    $(document).ready(function() {
        $('#df_field_vin .value a').off('click').click(function() {
            var $button = $(this);

            if ($button.hasClass('disabled')) {
                return false;
            }

            var previousText = $button.text();
            $button.text(csLang['cs_fetching_vin']);
            $button.addClass('disabled');

            var data = {
                mode: 'cs_getPdfReport',
                vin: vin
            };

            CarSpecsUtilClass().sendAajx(data, function(response, status) {
                $button.text(previousText);
                $button.removeClass('disabled');

                if (response.status === 'OK' && response.url) {
                    $(this).flModal({
                        source: '#vin-audit-report',
                        width: 900,
                        height: 640,
                        click: false,
                        ready: function() {
                            $('.vin-audit-report-container').find('iframe').attr('src', response.url).load(function() {
                                $('.vin-audit-report-container').removeClass('hide');
                                $('.vin-report-loader').addClass('hide');
                            });
                        }
                    });

                    return;
                }

                if (response.status === 'ERROR') {
                    var message = response.message ? response.message : csLang['cs_something_went_wrong'];
                    printMessage('error', message);
                }
            });
        });
    });
{/literal}</script>
