<!-- weather forecase | footer js code statement -->

<script>
lang['error'] = "{$lang.error}";
lang['weatherForecast_no_wf'] = "{$lang.weatherForecast_no_wf}";
var weatherForecast_lang = "{if $smarty.const.RL_LANG_CODE == 'en'}en-GB{else}{$smarty.const.RL_LANG_CODE|lower}{/if}";
var weatherForecast_conditions = new Array();
{foreach from=$condition_codes item='weather_condition' key='weather_code'}
    weatherForecast_conditions[{$weather_code}] = '{assign var='cond_phrase' value='weatherForecast_cond_'|cat:$weather_condition}{$lang.$cond_phrase}';
{/foreach}
</script>

<script id="weather-forecast" type="text/x-jquery-tmpl">
{literal}

<li>
    <div class="two-inline left clearfix">
        <div class="day">${week_day_short}</div>
        <div class="day-forecast">
            <ul class="weather-icon w${icon}">
                <li class="base"></li>
                <li class="pheno"></li>
            </ul>
            <div class="cond">${name}</div>
            <div class="temp">
                <span><span>{/literal}{$lang.weatherForecast_high}{literal}</span> ${temp_max}<span>, </span></span>
                <span><span>{/literal}{$lang.weatherForecast_low}{literal}</span> ${temp_min}</span>
            </div>
        </div>
    </div>
</li>

{/literal}
</script>

<!-- weather forecase | footer js code statement end -->
