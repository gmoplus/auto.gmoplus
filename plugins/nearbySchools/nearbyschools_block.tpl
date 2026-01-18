<!-- nearby schools box tpl -->

<div class="nearby-schools">
    <div class="list-table main_table">
        <div class="header">
            <div>{$lang.rating_label}</div>
            <div>{$lang.grades_label}</div>
            <div>{$lang.distance_label}</div>
        </div>

        {section loop=$schools name='school' max=$config.nbs_visible_schools}
            {include file=$smarty.const.RL_PLUGINS|cat:'nearbySchools/school.tpl'}
        {/section}
    </div>
    {if $schools|@count > $config.nbs_visible_schools}
        <div class="hidden-schools">
            <div class="list-table">
                {section loop=$schools name='school' start=$config.nbs_visible_schools}
                    {include file=$smarty.const.RL_PLUGINS|cat:'nearbySchools/school.tpl'}
                {/section}
            </div>
        </div>
        <div class="view-more-schools"><span class="link nbs-more">{$lang.nbs_view_more}</span><span
                    class="link nbs-less">{$lang.nbs_hide_schools}</span></div>
    {/if}
</div>

<script class="fl-js-dynamic">
    {literal}

    $(document).ready(function () {
        $('.view-more-schools > span.link').click(function () {
            $('.nearby-schools').toggleClass('expand');
        });
        $('.view-more-schools-uk').click(function () {
            $('.view-more-schools-uk').css('display', 'none');
            $('.view-hide-schools-uk').css('display', 'table-row');
            $('.hidden-uk-row').attr('style', 'display:table-row !important');
            //$('div').removeClass('hidden-uk-row');
        });
        $('.view-hide-schools-uk').click(function () {
            $('.view-more-schools-uk').css('display', 'table-row');
            $('.view-hide-schools-uk').css('display', 'none');
            $('.hidden-uk-row').attr('style', 'display:display !important');
        });
    });
    {/literal}
</script>

<!-- nearby schools box tpl end -->
