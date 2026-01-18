<!-- street view in gallery -->

<div class="street-view hide">
    {assign var='replace' value=`$smarty.ldelim`address`$smarty.rdelim`}
    {assign var='no_loc_phrase' value=$lang.street_view_no_location|replace:$replace:$location.search}

    {include file=$smarty.const.RL_PLUGINS|cat:'street_view/config.js.tpl'}

    <script class="fl-js-dynamic">
    var stree_view_point = "{$location.direct}";
    {literal}

    $(function(){
        var street_view_map = false;

        $('.map-group .street-view').click(function(){
            if (street_view_map) {
                return;
            }

            street_view_map = true;
            streetViewInit(stree_view_point);
        });
    });
    {/literal}
    </script>

    <div id="street_view"></div>
    <div id="no_street_view" class="hide info">{$no_loc_phrase}</div>
</div>

<!-- street view in gallery end -->
