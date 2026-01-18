<!-- search by distance tpl -->

<svg class="hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
    {include file='../img/svg/userLocation.svg'}
</svg>

<div id="sbd_map"></div>

<h1 class="sbd-state">{$lang.loading}</h1>
<div id="sbd_dom"></div>

<script class="fl-js-dynamic">
var sbdPageConfig = {literal} { {/literal}
    unit: '{$config.sbd_units}',
    limit: {if $config.sbd_listings_limit}{$config.sbd_listings_limit}{else}200{/if},
    distance: {if $config.sbd_default_distance}{$config.sbd_default_distance}*1000{if $config.sbd_units == 'miles'}*1.609344{/if}{else}10000{/if},
    geoLocation: "{$smarty.session.GEOLocationData->Country_name}{if $smarty.session.GEOLocationData->Region}, {$smarty.session.GEOLocationData->Region}{/if}{if $smarty.session.GEOLocationData->City}, {$smarty.session.GEOLocationData->City}{/if}",
    defaultCountry: "{$country_name}",
    defaultCountryCode: "{$config.sbd_default_country}",
    fromPost: {if $smarty.post.sbd_block}true{else}false{/if},
    lang: {literal} { {/literal}
        zipNotFound: "{$lang.sbd_zip_not_found}",
        locationNotFound: "{$lang.sbd_location_not_found}",
        kmShort: "{$lang.sbd_km_short}",
        miShort: "{$lang.sbd_mi_short}",
        zipCode: "{$lang.sbd_zipcode}",
        adsFound: "{$lang.sbd_number_ads_found}",
        noAdsFound: "{$lang.sbd_no_ads_found}",
        limitExceeded: "{$lang.sbd_search_limit_exceeded}",
    {literal} } {/literal}
{literal} } {/literal};
</script>

{mapsAPI}

{addJS file=$smarty.const.RL_PLUGINS_URL|cat:'search_by_distance/static/LeafletDraw/Leaflet.draw.js'}
{addJS file=$smarty.const.RL_PLUGINS_URL|cat:'search_by_distance/static/LeafletDraw/Leaflet.Draw.Event.js'}

{addJS file=$smarty.const.RL_PLUGINS_URL|cat:'search_by_distance/static/LeafletDraw/TouchEvents.js'}

{addJS file=$smarty.const.RL_PLUGINS_URL|cat:'search_by_distance/static/LeafletDraw/Edit.SimpleShape.js'}
{addJS file=$smarty.const.RL_PLUGINS_URL|cat:'search_by_distance/static/LeafletDraw/Edit.CircleMarker.js'}
{addJS file=$smarty.const.RL_PLUGINS_URL|cat:'search_by_distance/static/LeafletDraw/Edit.Circle.js'}

{addJS file=$smarty.const.RL_PLUGINS_URL|cat:'search_by_distance/static/leafletExtendTouch.js'}

{addJS file=$smarty.const.RL_LIBS_URL|cat:'maps/geocoder.js'}
{addJS file=$smarty.const.RL_PLUGINS_URL|cat:'search_by_distance/static/page_lib.js'}

<!-- search by distance tpl end -->
