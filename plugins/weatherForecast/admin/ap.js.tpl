<!-- admin panel js for weatherForecast plugin settings -->

{geoAutocompleteAPI}

<script>
var weather_default_location_id = '{$config.weatherForecast_wb_location_id}';
{literal}

$(document).ready(function(){
    var $input = $('input[name="post_config[weatherForecast_wb_location][value]"][type=text]');
    var weather_prev_locatin = $input.val();

    // Add hidden input
    $input.after('<input type="hidden" name="weather_default_location_id" value="'+weather_default_location_id+'" />');

    $input.geoAutocomplete({
        onSelect: function(name, lat, lng){
            $input.closest('table.form').find('input[type=submit]').val(lang['loading']).attr('disabled', true);

            var data = {
                item: 'weatherForecast',
                mode: 'coordinates',
                location: lat + ',' + lng,
                cache: true,
            };

            $.post(rlConfig['ajax_url'], data, function(response){
                $input.closest('table.form').find('input[type=submit]').val(lang['save']).attr('disabled', false);

                if (response.status == 'OK') {
                    $('input[name=weather_default_location_id]').val(response.results.city_id);
                } else {
                    $input.val(weather_prev_locatin);
                    printMessage('error', response.message);
                }
            }, 'json');
        }
    });

    // widget positioning handler
    var field_position = $('select[name="post_config[weatherForecast_position][value]"]');
    var field_type = $('input[name="post_config[weatherForecast_group_possition][value]"]');
    var field_group = $('select[name="post_config[weatherForecast_group][value]"]');

    var weatherForecast_check = function(){
        var val = field_position.val();

        if (val == 'in_group') {
            field_type.closest('tr').show();
            field_group.closest('tr').show();
        } else {
            field_type.closest('tr').hide();
            field_group.closest('tr').hide();
        }
    }

    weatherForecast_check();
    field_position.change(function(){
        weatherForecast_check();
    });
});

{/literal}
</script>

<!-- admin panel js for weatherForecast plugin settings end -->
