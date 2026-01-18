<!-- invoice details -->

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='invoice_info' name=$lang.invoice_info}

<div class="table-cell">
    <div class="name"><div><span>{$lang.invoice_txn_id}</span></div></div>
    <div class="value">{$invoice_info.Txn_ID}</div>
</div>
<div class="table-cell">
    <div class="name"><div><span>{$lang.invoice_subject}</span></div></div>
    <div class="value">{$invoice_info.Subject}</div>
</div>
<div class="table-cell">
    <div class="name"><div><span>{$lang.invoice_total}</span></div></div>
    <div class="value">{if $config.system_currency_position == 'before'}{$config.system_currency}{/if} {$invoice_info.Total} {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}</div>
</div>
{if $invoice_info.Description}
    <div class="table-cell">
        <div class="name"><div><span>{$lang.invoice_description}</span></div></div>
        <div class="value">{$invoice_info.Description}</div>
    </div>
{/if}
{if $enable_pdf}
    <div class="table-cell">
        <div class="name"><div><span>{$lang.invoice_in_pdf}</span></div></div>
        <div class="value">
            <a target="_blank" title="{$lang.invoice_in_pdf}" href="{$rlBase}{if $config.mod_rewrite}{$pages.invoice_in_pdf}/service.html?item={$invoice_info.ID}{else}?page={$pages.invoice_in_pdf}&amp;type=service&amp;item={$invoice_info.ID}{/if}"><img src="{$smarty.const.RL_PLUGINS_URL}invoices/static/pdf.png" /></a>
        </div>
    </div>
{/if}

{if $invoice_info.pStatus == 'paid'}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='paymentInfo' name=$lang.invoice_payment_info}

    <div class="table-cell">
        <div class="name"><div><span>{$lang.invoice_paid_with}</span></div></div>
        <div class="value">{$invoice_info.Gateway}</div>
    </div>
    <div class="table-cell">
        <div class="name"><div><span>{$lang.txn_id}</span></div></div>
        <div class="value">{$invoice_info.Txn_gateway}</div>
    </div>
    <div class="table-cell">
        <div class="name"><div><span>{$lang.invoice_paid_date}</span></div></div>
        <div class="value">{$invoice_info.Pay_date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
    </div>

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
{/if}

{if $invoice_info.pStatus == 'unpaid'}
    <!-- payment gateways -->
    <div class="mt-3">
        <form id="form-checkout" method="post" action="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}/{$invoice_info.Txn_ID}.html{else}?page={$pageInfo.Path}&item={$invoice_info.Txn_ID}{/if}">
            <input type="hidden" name="step" value="checkout" />
            {gateways}
            <div class="form-buttons">
                <input type="submit" value="{$lang.checkout}" />
            </div>
        </form>
    </div>
    <!-- end payment gateways -->
{/if}

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

<!-- end invoice details -->
