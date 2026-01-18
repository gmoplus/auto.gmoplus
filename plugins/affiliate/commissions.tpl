<!-- Affiliate Commissions  page tpl -->

{if $isLogin}
    {if $commissions}
        <div class="transactions list-table content-padding">
            <div class="header">
                <div class="center" style="width: 40px;">#</div>
                <div>{$lang.aff_commissions_date}</div>
                <div>{$lang.aff_commissions_commission}</div>
                <div>{$lang.aff_details_item_admin_item}</div>
                <div>{$lang.aff_commissions_deposit_date}</div>
                <div>{$lang.aff_commissions_status}</div>
            </div>

            {foreach from=$commissions item='item' name='commissionsAff'}
                {math assign='iteration' equation='(((current?current:1)-1)*per_page)+iter' iter=$smarty.foreach.commissionsAff.iteration current=$pInfo.current per_page=$config.aff_items_per_page}
                <div class="row">
                    <div class="center iteration no-flex">{$iteration}</div>
                    <div data-caption="{$lang.aff_commissions_date}">{$item.Date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
                    <div data-caption="{$lang.aff_commissions_commission}">{$item.Commission}</div>
                    <div data-caption="{$lang.aff_details_item_admin_item}">
                        <img class="qtip middle-bottom" alt="" title="{$item.Description}" src="{$rlTplBase}img/blank.gif" />
                        {$item.Item}</div>
                    <div data-caption="{$lang.aff_commissions_deposit_date}">{$item.Deposit_date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
                    <div data-caption="{$lang.aff_commissions_status}">{$item.Status}</div>
                </div>
            {/foreach}
        </div>

        <!-- paging block -->
        {paging calc=$pInfo.calc total=$commissions|@count current=$pInfo.current per_page=$config.aff_items_per_page}
        <!-- paging block end -->

        <script class="fl-js-static">flynax.qtip()</script>
    {else}
        {$lang.aff_commissions_not_exist}
    {/if}
{/if}

<!-- Affiliate Commissions page tpl end -->
