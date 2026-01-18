<div id="events-calendar">
    {strip}
        <div class="calender-header">
            <div class="calendar-arrow hide left lalign">
                <svg class="grid-icon-fill" viewBox="0 0 8 14">
                    <use xlink:href="#events-arrow"></use>
                </svg>
            </div>
            <div class="title align-center"></div>
            <div class="calendar-arrow hide right ralign">
                <svg class="grid-icon-fill" viewBox="0 0 8 14">
                    <use xlink:href="#events-arrow"></use>
                </svg>
            </div>
        </div>
        <div class="calendar-body events-days-view">
            <div class="weeks"></div>
            <div class="dates">
                <div class="align-center">{$lang.loading}</div>
            </div>
            <div class="month"></div>
            <div class="years"></div>
        </div>
        <div class="calender-footer"></div>
    {/strip}
</div>

{include file=$smarty.const.RL_PLUGINS|cat:'events/static/arrow.svg'}
<script>
    var ev_cachedJson = {if $cache_events}JSON.parse('{$cache_events}'){else}null{/if} ;
    var ev_firstDayOfWeek = {if $config.ev_sunday_first}0{else}1{/if};
    var ev_showPassedEvents = {if $config.ev_show_passed_events}0{else}1{/if};
    var eventDate = {if $eventDate}'{$eventDate}'{else}null{/if};
    var category_id = {if $category.ID}{$category.ID}{else}0{/if};
    {literal}

    $(document).ready(function () {
        var options = {
            cache: ev_cachedJson,
            firstDayOfWeek: ev_firstDayOfWeek,
            showPassedEvents: ev_showPassedEvents,
            eventDate: eventDate,
            category_id: category_id,
            locale: rlLang,
        };

        eventsCalendar.init(options);
    });
{/literal}</script>
