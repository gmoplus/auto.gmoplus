<!-- who's online block -->

<div class="row">
    <div class="fieldset col-12{if $block.Side == 'middle' || $block.Side == 'top' || $block.Side == 'bottom'} col-sm-6{elseif $block.Side == 'left'} col-sm-6 col-lg-12{/if}">
        <b>{$lang.online_statistics_text}</b>
        <div class="body">
            <div {if $block.Side == 'middle' || $block.Side == 'top' || $block.Side == 'bottom'}class="pb-sm-0"{/if}>
                <div class="table-cell d-flex">
                    <div class="name mw-100 w-auto flex-fill">{$lang.online_count_last_hour_text}</div>
                    <div class="value text-center">{$onlineStatistics.lastHour}</div>
                </div>
                <div class="table-cell d-flex">
                    <div class="name mw-100 w-auto flex-fill">{$lang.online_count_last_day_text}</div>
                    <div class="value text-center">{$onlineStatistics.lastDay}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="fieldset mb-0 col-12{if $block.Side == 'middle' || $block.Side == 'top' || $block.Side == 'bottom'} col-sm-6{elseif $block.Side == 'left'} col-sm-6 col-lg-12{/if}">
        <b>{$lang.online_count_all_text|replace:'[number]':$onlineStatistics.total}</b>
        <div class="body">
            <div class="pb-0">
                <div class="table-cell d-flex">
                    <div class="name mw-100 w-auto flex-fill">{$lang.online_count_users_text}</div>
                    <div class="value text-center">{$onlineStatistics.users}</div>
                </div>
                <div class="table-cell d-flex">
                    <div class="name mw-100 w-auto flex-fill">{$lang.online_count_guests_text}</div>
                    <div class="value text-center">{$onlineStatistics.guests}</div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- who's online block end -->
