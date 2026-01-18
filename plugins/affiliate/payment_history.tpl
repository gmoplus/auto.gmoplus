<!-- Affiliate Payment History page tpl -->

{if $isLogin}
    {if $smarty.get.id && $payout}
        <div class="content-padding">
            <div class="table-cell small">
                <div class="name">{$lang.aff_payout_date}</div>
                <div class="value">{$payout.Date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
            </div>
            <div class="table-cell small">
                <div class="name">{$lang.aff_payout_count}</div>
                <div class="value">{$payout.Count_deals}</div>
            </div>
            <div class="table-cell small">
                <div class="name">{$lang.aff_payout_amount}</div>
                <div class="value">{$payout.Amount}</div>
            </div>

            <a target="_blank"
                class="button print_payout"
                href="{$smarty.const.RL_PLUGINS_URL}affiliate/static/save_payout_as_pdf.php?id={$payout.ID}">
                {$lang.aff_title_pdf_export}
            </a>

            <div class="transactions list-table content-padding">
                <div class="header">
                    <div class="center" style="width: 40px;">#</div>
                    <div>{$lang.aff_commissions_date}</div>
                    <div>{$lang.aff_details_item_admin_commission}</div>
                    <div>{$lang.aff_details_item_admin_item}</div>
                </div>

                {foreach from=$payout.Deals item='item' name='payoutDetailsAff'}
                    <div class="row">
                        <div class="center iteration no-flex">{$smarty.foreach.payoutDetailsAff.iteration}</div>
                        <div data-caption="{$lang.aff_commissions_date}">{$item.Posted|date_format:$smarty.const.RL_DATE_FORMAT}</div>
                        <div data-caption="{$lang.aff_details_item_admin_commission}">{$item.Commission}</div>
                        <div data-caption="{$lang.aff_details_item_admin_item}">
                            <img class="qtip middle-bottom" alt="" title="{$item.Description}" src="{$rlTplBase}img/blank.gif" />
                            {$item.Item}
                        </div>
                    </div>
                {/foreach}
            </div>
        </div>

        <script class="fl-js-static">flynax.qtip()</script>
    {else}
        {if $payouts}
            <div class="transactions list-table content-padding">
                <div class="header">
                    <div class="center" style="width: 40px;">#</div>
                    <div>{$lang.aff_payout_date}</div>
                    <div>{$lang.aff_payout_count}</div>
                    <div>{$lang.aff_payout_amount}</div>
                    <div></div>
                </div>

                {foreach from=$payouts item='item' name='payoutsAff'}
                    {math assign='iteration' equation='(((current?current:1)-1)*per_page)+iter' iter=$smarty.foreach.payoutsAff.iteration current=$pInfo.current per_page=$config.aff_items_per_page}
                    <div class="row">
                        <div class="center iteration no-flex">{$iteration}</div>
                        <div data-caption="{$lang.aff_payout_date}">{$item.Date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
                        <div data-caption="{$lang.aff_payout_count}">{$item.Payouts_count}</div>
                        <div data-caption="{$lang.aff_payout_amount}">{$item.Amount}</div>
                        <div data-caption=""><a href="{$item.Details_link}">{$lang.view_details}</a></div>
                    </div>
                {/foreach}
            </div>

            <!-- paging block -->
            {paging calc=$pInfo.calc total=$payouts|@count current=$pInfo.current per_page=$config.aff_items_per_page}
            <!-- paging block end -->
        {else}
            {$lang.aff_payouts_not_exist}
        {/if}
    {/if}
{/if}

<!-- Affiliate payment History page tpl end -->
