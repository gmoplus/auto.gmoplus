<!-- street view js config -->

<script class="fl-js-dynamic">
var streetViewConfig = [];
streetViewConfig['provider'] = '{$config.street_view_provider}';
streetViewConfig['mode'] = '{$config.street_view_mode}';
streetViewConfig['google_api_key'] = '{$config.street_view_google_key}';
streetViewConfig['yandex_api_key'] = '{$config.street_view_yandex_key}';

{literal}

var streetViewInit = function(location, container){
    var lib_url = streetViewConfig['provider'] == 'google'
        ? 'https://maps.googleapis.com/maps/api/js?language=' + rlLang + '&libraries=places&key=' + streetViewConfig['google_api_key']
        : 'https://api-maps.yandex.ru/2.1/?lang=' + rlLang + '&apikey=' + streetViewConfig['yandex_api_key'];

    var showHint = function(){
        $('#street_view').hide();
        $('#no_street_view').fadeIn();
    }

    var googleStreetView = function(location){
        location = location.split(',');
        var point = new google.maps.LatLng(location[0], location[1]);
        var service = new google.maps.StreetViewService();
        var max_distance = 50; // Meters

        service.getPanoramaByLocation(point, max_distance, function(data, status) {
            if (status === google.maps.StreetViewStatus.OK) {
                new google.maps.StreetViewPanorama(document.getElementById('street_view'), options = {
                    position: point,
                    fullscreenControl: streetViewConfig['mode'] == 'gallery' ? false : true
                });
            } else {
                showHint();
            }
        });
    }

    var yandexStreetView = function(location){
        ymaps.ready(function(){
            if (!ymaps.panorama.isSupported()) {
                showHint();
            }

            location = location.split(',');

            var options = {};
            if (streetViewConfig['mode'] == 'gallery') {
                options.controls = ['zoomControl'];
            }

            ymaps.panorama.locate(location).done(
                function(panoramas) {
                    if (panoramas.length > 0) {
                        new ymaps.panorama.Player('street_view', panoramas[0], options);
                    } else {
                        showHint();
                    }
                },
                function(error) {
                    console.log('Street View / Yandex: ' + error.message);
                    showHint();
                }
            );
        });
    }

    var initMethod = streetViewConfig['provider'] == 'google'
        ? googleStreetView
        : yandexStreetView;

    flUtil.loadScript(lib_url, function(){
        initMethod.call(this, location);
    });
}

{/literal}
</script>

<!-- street view js config end -->
