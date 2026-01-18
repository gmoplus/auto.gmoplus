{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='payment_info' name=$lang.bwt_order_information style='fg'}
    <div class="txn-fields">
        <div class="table-cell clearfix">
            <div class="name">{$lang.item}</div>
            <div class="value">
                {$txn_info.Item}
            </div>
        </div>
        <div class="table-cell clearfix">
            <div class="name">{$lang.txn_id}</div>
            <div class="value">
                {$txn_info.Txn_ID}
            </div>
        </div>
        <div class="table-cell clearfix">
            <div class="name">{$lang.shc_buyer}</div>
            <div class="value">
                {$buyer.Full_name}
            </div>
        </div>
        <div class="table-cell clearfix">
            <div class="name">{$lang.total}</div>
            <div class="value">
                <b>{if $config.system_currency_position == 'before'}{$config.system_currency}{/if} {$txn_info.Total} {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}</b>
            </div>
        </div>
        <div class="table-cell clearfix">
            <div class="name">{$lang.date}</div>
            <div class="value">
                {$txn_info.Date|date_format:$smarty.const.RL_DATE_FORMAT}
            </div>
        </div>
        <div class="table-cell clearfix">
            <div class="name">{$lang.status}</div>
            <div class="value">
                {$txn_info.Status}
            </div>
        </div>
    </div>
{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='payment_info' name=$lang.bwt_payment_details style='fg'}

    <!-- Payment Information -->
    {if !empty($payment_details)}
        {if $pageInfo.Controller == 'payment_history'}
            <div class="name"><b>{$lang.bwt_payment_details}:</b></div>
        {/if}
        <div class="sLine"></div>
        {$payment_details}
    {else}
        <div class="static-content">{$lang.bwt_missing_payment_details}</div>
    {/if}
    <!-- end Payment Information -->

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
