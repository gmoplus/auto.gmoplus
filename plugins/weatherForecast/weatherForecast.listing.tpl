<!-- weather forecast listing -->

{if !$wf_error && !$weatherForecast_listing_city}
    <script>console.log('Weather Forecast ERROR: No listing city specified, widget disabled.')</script>
{else}
    <div id="weather-listing-cont"{if $config.weatherForecast_position != 'top'} class="hide"{/if}>
        {if $config.weatherForecast_position == 'top' || $config.weatherForecast_position == 'bottom'}
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='weather_forecast' name=$lang.weatherForecast_weather_foreacst line=true}
        {/if}   

        <div class="weather-widget weather-listing clearfix">
            <div class="location"><b>
                {if $config.weatherForecast_position == 'in_group'}
                    {assign var='replace' value=`$smarty.ldelim`location`$smarty.rdelim`}
                    {$lang.weatherForecast_weather_in|replace:$replace:$weatherForecast_listing_city}
                {else}
                    {$weatherForecast_listing_city}
                {/if}
            </b></div>
            
            <div class="current-cond">
                <div class="hborder" title="{$lang.weatherForecast_cur_cond}">
                    <div class="two-inline left clearfix">
                        <ul class="weather-icon">
                            <li class="base"></li>
                            <li class="pheno"></li>
                        </ul>
                        <div class="weather-info">
                            <div class="temp">-- {if $config.weatherForecast_units == 'Celsius'}°C{else}°F{/if}</div>
                            <div class="cond">{if $wf_error}{$lang.error}{else}{$lang.loading}{/if}</div>
                        </div>
                    </div>
                </div>

                <div class="weather-cp hide">
                    <div>
                        {assign var='replace' value='<a target="_blank" href="http://openweathermap.org/city/'|cat:$config.weatherForecast_wb_location_id|cat:'">$1</a>'}
                        {$lang.weatherForecast_more_forecast|regex_replace:'/\[(.*)\]/':$replace}
                    </div>
                </div>
            </div>

            {if $wf_error}
                <div class="forecast">{$wf_error}</div>
            {else}
                <ul class="forecast">
                    {section loop=3 name='wf_loop' max=3}
                        <li>
                            <div class="two-inline left clearfix">
                                <div class="day">{$smarty.now|date_format:'%a'}</div>
                                <div class="day-forecast">
                                    <ul class="weather-icon w">
                                        <li class="base"></li>
                                        <li class="pheno"></li>
                                    </ul>
                                    <div class="cond">{$lang.loading}</div>
                                    <div class="temp">
                                        <span><span>{$lang.weatherForecast_high}</span> --<span>, </span></span>
                                        <span><span>{$lang.weatherForecast_low}</span> --</span>
                                    </div>
                                </div>
                            </div>
                        </li>
                    {/section}
                </ul>
            {/if}
        </div>
        
        {if $config.weatherForecast_position == 'top' || $config.weatherForecast_position == 'bottom'}
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
        {/if}
    </div>

    <script>
    var weatherForecastListing = new Object();
    weatherForecastListing.target = 'weather-listing';
    weatherForecastListing.mode = '{if $weatherForecast_listing_coordinates}coordinates{else}location{/if}';
    weatherForecastListing.location = "{$weatherForecast_listing_location}";
    weatherForecastListing.position = '{$config.weatherForecast_position}';
    weatherForecastListing.position_group = '{$config.weatherForecast_group}';
    weatherForecastListing.position_in_group = '{$config.weatherForecast_group_possition}';
    </script>
{/if}

<!-- weather forecast listing -->
