<!-- Affiliate Traffic Log page tpl -->

{if $isLogin}
    {if $traffic_log}
        <div class="transactions list-table content-padding">
            <div class="header">
                <div class="center" style="width: 40px;">#</div>
                <div>{$lang.aff_traffic_ip}</div>
                <div>{$lang.aff_traffic_details}</div>
                <div>{$lang.aff_traffic_type}</div>
            </div>

            {foreach from=$traffic_log item='item' name='trafficAff'}
                {math assign='iteration' equation='(((current?current:1)-1)*per_page)+iter' iter=$smarty.foreach.trafficAff.iteration current=$pInfo.current per_page=$config.aff_items_per_page}
                <div class="row">
                    <div class="center iteration no-flex">{$iteration}</div>
                    <div data-caption="{$lang.aff_traffic_ip}" class="content">{$item.IP}</div>

                    <div class="no-flex default">
                        <div class="table-cell clearfix small">
                            <div class="name">{$lang.aff_traffic_date}</div>
                            <div class="value">{$item.Date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
                        </div>
                        <div class="table-cell clearfix small">
                            <div class="name">{$lang.aff_traffic_country}</div>
                            <div class="value">{if $item.Country_name}{$item.Country_name}{else}{$lang.not_available}{/if}</div>
                        </div>
                        <div class="table-cell clearfix small">
                            <div class="name">{$lang.aff_traffic_region_city}</div>
                            <div class="value">{if $item.Region || $item.City}{$item.Region}{if $item.Region && $item.City},{/if} {$item.City}{else}{$lang.not_available}{/if}</div>
                        </div>

                        {if $item.Referring_Url}
                            <div class="table-cell clearfix small">
                                <div class="name">{$lang.aff_traffic_referring_url}</div>
                                <div class="value"><a alt="" href="{$item.Referring_Url}">{$item.Referring_Url}</a></div>
                            </div>
                        {/if}
                    </div>
                    <div data-caption="{$lang.aff_traffic_type}">{$item.Type}</div>
                </div>
            {/foreach}
        </div>

        <!-- paging block -->
        {paging calc=$pInfo.calc total=$traffic_log|@count current=$pInfo.current per_page=$config.aff_items_per_page}
        <!-- paging block end -->
    {else}
        {$lang.aff_traffic_not_exist}
    {/if}
{/if}

<!-- Affiliate Traffic Log page tpl end -->
