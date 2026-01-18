<a href="javascript:void(0);" id="line_color" class="hide"></a>
<ul class="tabs{if $tpl_settings.name|strpos:'auto_main' === 0} auto_main_tpl{/if}">
    <li class="active" id="tab_price_history_table" >
        <a href="#price_history_table" data-target="price_history_table">{$lang.ph_table}</a>
    </li>
    {if $price_history|@count > 1}
        <li id="tab_price_history_chart">
            <a href="#price_history_chart" data-target="price_history_chart">{$lang.ph_chart}</a>
        </li>
    {/if}
</ul>

<!-- Price history table -->
<div id="area_price_history_table">
    <div class="list-table">
        <div class="header">
            <div class="first">{$lang.date}</div>
            <div>{$lang.ph_event}</div>
            <div>{$lang.price}</div>
            {if $config.ph_sqft_enable}<div>{$lang.ph_sqft}</div>{/if}
        </div>
        {foreach from=$price_history item='item'}
            <div class="row">
                 <div class="first">{$item.Date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
                 <div>{$item.Event}</div>
                 <div>
                        {$item.Price_value}
                        {if $item.price_diff}
                            <span class='price-diff {$item.price_diff_class}'>{$item.precent_diff} %</span>
                        {/if}
                 </div>
                 {if $config.ph_sqft_enable}
                    <div>{$item.square_feet_price}</div>
                 {/if}
            </div>
         {/foreach}
    </div>
</div>
<!-- Price history table end -->

<!-- Price history chart -->
<div id="area_price_history_chart" class="hide">
    <div id="chart"></div>
</div>
<!-- Price history chart end -->

<script class="fl-js-dynamic">
    var date_range = [], values_array  = [];
    lang.sys_currency = '{$price_history[0].Currency}';
    lang.system_currency_position = '{$config.system_currency_position}';
    {foreach from=$price_history item='item' name='ph_element'}
        date_range.push(new Date('{$item.Date}'));
        values_array.push({if $item.tmp_price}{$item.tmp_price|round}{else}{$item.Price|round}{/if});
    {/foreach}

    {literal}
      $(function () {
        var price_history = new priceHistoryClass(date_range, values_array);
        price_history.tabsHandler();
      });
    {/literal}
</script>
