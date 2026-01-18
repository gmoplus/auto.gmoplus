{if $config.shc_escrow && $orderInfo.Status == 'paid'}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='shc_items' name=$lang.shc_escrow_item}
    <div class="table-cell">
        <div class="name"><span>{$lang.status}</span></div>
        {assign var='statusKey' value='shc_escrow_'|cat:$orderInfo.Escrow_status}
        <div class="value">{if $orderInfo.Escrow_status == 'pending'}{$lang[$orderInfo.Escrow_status]}{else}{$lang[$statusKey]}{/if}</div>
    </div>
    {if $orderInfo.Deal_ID}
    <div class="table-cell">
        <div class="name"><span>{$lang.shc_deal_id}</span></div>
        <div class="value">{$orderInfo.Deal_ID}</div>
    </div>
    <div class="table-cell">
        <div class="name"><div><span>{$lang.shc_escrow_expiration}</span></div></div>
        <div class="value">{$orderInfo.Escrow_date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
    </div>
    {/if}
    {if $orderInfo.Payout_ID}
    <div class="table-cell">
        <div class="name"><span>{$lang.shc_payout_id}</span></div>
        <div class="value">{$orderInfo.Payout_ID}</div>
    </div>
    {/if}
    {if $orderInfo.Refund_ID}
        <div class="table-cell">
            <div class="name"<span>{$lang.shc_refund_id}</span></div>
            <div class="value">{$orderInfo.Refund_ID}</div>
        </div>
    {/if}
    {if $orderInfo.Refund_reason}
        <div class="table-cell">
            <div class="name"<span>{$lang.shc_cancel_reason}</span></div>
        <div class="value">{$orderInfo.Refund_reason}</div>
        </div>
    {/if}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
{/if}
