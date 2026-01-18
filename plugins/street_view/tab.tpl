<!-- street view tab content -->

<div id="area_streetView" class="tab_area hide">
    {assign var='replace' value=`$smarty.ldelim`address`$smarty.rdelim`}
    {assign var='no_loc_phrase' value=$lang.street_view_no_location|replace:$replace:$location.search}

    {include file=$smarty.const.RL_PLUGINS|cat:'street_view/config.js.tpl'}

    <script class="fl-js-dynamic">
    var stree_view_point = "{$location.direct}";
    {literal}

    $(function(){
        var street_view_map = false;

        $('.tabs li').click(function(){
            if (!street_view_map && $(this).attr('id') == 'tab_streetView') {
                streetViewInit(stree_view_point);
                street_view_map = true;
            }
        });

        if (!street_view_map && flynax.getHash() == 'streetView_tab') {
            streetViewInit(stree_view_point);
            street_view_map = true;
        }
    });
    {/literal}
    </script>

    <div id="street_view" style="height: 65vh;"></div>
    <div id="no_street_view" class="hide info">{$no_loc_phrase}</div>
</div>

<!-- street view tab content end -->
