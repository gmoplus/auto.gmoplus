<!-- tab content -->

<div id="area_nearScl" class="tab_area content-padding hide">
    <div class="nearby-schools">
        <div class="list-table main_table">
            <div class="header">
                <div>{$lang.rating_label}</div>
                <div>{$lang.grades_label}</div>
                <div>{$lang.distance_label}</div>
            </div>
            {section loop=$schools name='school'}
                {include file=$smarty.const.RL_PLUGINS|cat:'nearbySchools/school.tpl'}
            {/section}
        </div>    
    </div>
</div>

{addCSS file=$smarty.const.RL_PLUGINS_URL|cat:'nearbySchools/static/style.css'}

<!-- tab content end -->
