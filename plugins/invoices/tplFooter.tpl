{if $pageInfo.Key == 'payment_history'}
    <script class="fl-js-dynamic">
        var invTxns = [];

        {foreach from=$transactions item='transaction' key='key'}
            invTxns[{$key}] = [];
            invTxns[{$key}]['ID'] = '{$transaction.ID}';

            var url = '{$rlBase}{if $config.mod_rewrite}{$pages.invoice_in_pdf}/payment.html?item={$transaction.ID}{else}?page={$pages.invoice_in_pdf}&amp;type=payment&amp;item={$transaction.ID}{/if}';
            var html_item = '<div data-caption="{$lang.invoice_in_pdf}"><a href="' + url + '" target="_blank" title="{$lang.payment_in_pdf}"><img src="{$smarty.const.RL_PLUGINS_URL}invoices/static/pdf.png" />&nbsp;{$lang.download}</a></div>';
            invTxns[{$key}]['html_item'] = html_item;
        {/foreach}

        {literal}
        $(document).ready(function() {
            $('.transactions > div.header > div:eq(2)').after('<div style="width: 120px;">{/literal}{$lang.invoice_in_pdf}{literal}</div>');
            for(var i = 0; i < invTxns.length; i++) {
                $('#txn-id-' + invTxns[i]['ID']).parent().parent().after(invTxns[i]['html_item']);
            }
        });
        {/literal}
    </script>
{/if}

{if $pageInfo.Key != 'invoices' && !$errors}
<script class="fl-js-dynamic">
    var unpaid_invoices = {$unpaid_invoices};
    var unpaid_invoices_message = '{$lang.unpaid_invoices_message|replace:"[here]":$invoice_link}';

    {literal}
    $(document).ready(function() {
        if(unpaid_invoices > 0) {
            printMessage('warning', unpaid_invoices_message);
        }
    });
    {/literal}
</script>
{/if}
