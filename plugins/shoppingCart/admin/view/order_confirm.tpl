{if $config.shc_escrow}
    <fieldset class="light">
        <legend id="legend_payment_details" class="up" onclick="fieldset_action('shc_escrow');">{$lang.shc_escrow_item}</legend>
        <table id="shc_escrow" class="list">
            <tr>
                <td class="name">{$lang.status}:</td>
                <td class="value" id="escrow-status">
                    {if $order_info.Escrow_status == 'pending'}
                        <div>{$lang.pending}</div>
                        <input type="button" class="confirm-order" value="{$lang.shc_order_confirm}" />
                    {elseif $order_info.Escrow_status == 'confirmed'}
                        <span>{$lang.shc_escrow_confirmed}</span>
                    {elseif $order_info.Escrow_status == 'canceled'}
                        <span>{$lang.shc_escrow_canceled}</span>
                    {/if}
                </td>
            </tr>
            {if $order_info.Deal_ID}
            <tr>
                <td class="name">{$lang.shc_deal_id}:</td>
                <td class="value">{$order_info.Deal_ID}</td>
            </tr>
            {/if}
            {if $order_info.Payout_ID}
            <tr>
                <td class="name">{$lang.shc_payout_id}:</td>
                <td class="value">{$order_info.Payout_ID}</td>
            </tr>
            {/if}
            {if $order_info.Refund_ID}
            <tr>
                <td class="name">{$lang.shc_refund_id}:</td>
                <td class="value">{$order_info.Refund_ID}</td>
            </tr>
            {/if}
        </table>
    </fieldset>
    <script>
        var shcOrderID = {$order_info.ID};
        var buyerID = {$order_info.Buyer_ID};
        {literal}
        $(document).ready(function() {
            $('.confirm-order').click(function () {
                rlConfirm(lang['shc_do_you_want_confirm_order'], 'confirmOrder', Array(shcOrderID, buyerID), 'load');
            });
        });
        {/literal}
    </script>
{/if}
