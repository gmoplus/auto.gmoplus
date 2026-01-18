<!-- weather forecast block -->

{if $forecast || ($config.weatherForecast_use_geo && $smarty.session.GEOLocationData->City)}
    <div class="weather-widget weather-box clearfix">
        <div class="current-cond">
            <div class="location">
                <b>
                {assign var='lf_lang_code' value=$smarty.const.RL_LANG_CODE}
                {if $config.weatherForecast_use_geo}
                    {if $smarty.session.GEOLocationData->City_names
                        && is_array($smarty.session.GEOLocationData->City_names)
                        && $smarty.session.GEOLocationData->City_names.$lf_lang_code
                    }
                        {$smarty.session.GEOLocationData->City_names.$lf_lang_code}
                    {else}
                        {$smarty.session.GEOLocationData->City}
                    {/if}
                {else}
                    {$forecast.location}
                {/if}
                </b>
            </div>
            <div class="hborder" title="{$lang.weatherForecast_cur_cond}">
                <div class="two-inline left clearfix">
                    <ul class="weather-icon w{if !$config.weatherForecast_use_geo}{$forecast.forecast[0].icon}{/if}">
                        <li class="base"></li>
                        <li class="pheno"></li>
                    </ul>
                    <div class="weather-info">
                        <div class="temp">{if $config.weatherForecast_use_geo}-- {if $config.weatherForecast_units == 'Celsius'}°C{else}°F{/if}{else}{$forecast.forecast[0].temp}{/if}</div>
                        <div class="cond">{if $config.weatherForecast_use_geo}{$lang.loading}{else}{weatherCondition id=$forecast.forecast[0].icon_id icon=$forecast.forecast[0].icon}{/if}</div>
                    </div>
                </div>
            </div>
        </div>

        <ul class="forecast">
            {if $forecast}
                {foreach from=$forecast.forecast item='day' name='forecastF'}
                    {if $smarty.foreach.forecastF.first}{continue}{/if}

                    <li>
                        <div class="two-inline left clearfix">
                            <div class="day">{$day.date|date_format:'%a'}</div>
                            <div class="day-forecast">
                                <ul class="weather-icon w{if !$config.weatherForecast_use_geo}{$day.icon}{/if}">
                                    <li class="base"></li>
                                    <li class="pheno"></li>
                                </ul>
                                <div class="cond">{if $config.weatherForecast_use_geo}{$lang.loading}{else}{weatherCondition id=$day.icon_id icon=$day.icon}{/if}</div>
                                <div class="temp">
                                    <span><span>{$lang.weatherForecast_high}</span> {if $config.weatherForecast_use_geo}--{else}{$day.temp_max}{/if}<span>, </span></span>
                                    <span><span>{$lang.weatherForecast_low}</span> {if $config.weatherForecast_use_geo}--{else}{$day.temp_min}{/if}</span>
                                </div>
                            </div>
                        </div>
                    </li>
                {/foreach}
            {else}

            {/if}
        </ul>

        <div class="weather-cp{if $config.weatherForecast_use_geo} hide{/if}">
            <div>
                {assign var='replace' value='<a target="_blank" href="http://openweathermap.org/city/'|cat:$config.weatherForecast_wb_location_id|cat:'">$1</a>'}
                {$lang.weatherForecast_more_forecast|regex_replace:'/\[(.*)\]/':$replace}
            </div>
        </div>
    </div>

    <script>
    var weatherForecastBox = new Object();
    weatherForecastBox.target = 'weather-box';
    weatherForecastBox.mode = '{if $config.weatherForecast_use_geo}location{else}id{/if}';
    weatherForecastBox.location = {if $config.weatherForecast_use_geo}{if $smarty.session.GEOLocationData->Country_code && $smarty.session.GEOLocationData->City}"{$smarty.session.GEOLocationData->City},{$smarty.session.GEOLocationData->Country_code}"{else}false{/if}{else}{$config.weatherForecast_wb_location_id}{/if};
    weatherForecastBox.now = {$smarty.now};
    weatherForecastBox.updateTime = {$next_update};
    weatherForecastBox.cachePeriod = {if $config.weatherForecast_cache}{$config.weatherForecast_cache}{else}1{/if};
    weatherForecastBox.cached = {if !$config.weatherForecast_use_geo && $forecast}true{else}false{/if};
    </script>
{else}
    <span class="text-notice">{$lang.weatherForecast_no_location}</span>
{/if}

<!-- weather forecast block end -->
