<!-- list of invoices -->

<div class="list-table content-padding">
    <div class="header">
        <div class="center" style="width: 40px;">#</div>
        <div>{$lang.invoice_subject}</div>
        <div style="width: 90px;">{$lang.invoice_total}</div>
        <div style="width: 120px;">{$lang.invoice_txn_id}</div>
        <div style="width: 130px;">{$lang.date}</div>
        <div style="width: 100px;">{$lang.status}</div>
        <div style="width: 120px;">{$lang.invoice_in_pdf}</div>
        <div style="width: 90px;">{$lang.actions}</div>
    </div>

    {foreach from=$invoices item='item' name='invoiceF'}
        {math assign='iteration' equation='(((current?current:1)-1)*per_page)+iter' iter=$smarty.foreach.invoiceF.iteration current=$pInfo.current per_page=$config.invoices_per_page}

        <div class="row">
            <div class="center iteration no-flex">{$iteration}</div>
            <div data-caption="{$lang.item}">
                <a href="{$rlBase}{if $config.mod_rewrite}{$pages.invoices}/{$item.Txn_ID}.html{else}?page={$pages.invoices}&amp;item={$item.Txn_ID}{/if}" rel="nofollow">{$item.Subject}</a>
            </div>
            <div data-caption="{$lang.total}">{strip}
                <span class="price-cell">
                    {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
                    {$item.Total|number_format:2:'.':','}
                    {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
                </span>
            {/strip}</div>
            <div data-caption="{$lang.invoice_number}">{$item.Txn_ID}</div>
            <div data-caption="{$lang.date}">{$item.Date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
            <div data-caption="{$lang.status}">
                <span class="invoice_{$item.pStatus}">{$lang[$item.pStatus]}</span>
            </div>
            <div data-caption="{$lang.invoice_in_pdf}">
                <a target="_blank" title="{$lang.invoice_in_pdf}" href="{$rlBase}{if $config.mod_rewrite}{$pages.invoice_in_pdf}/service.html?item={$item.ID}{else}?page={$pages.invoice_in_pdf}&amp;type=service&amp;item={$item.ID}{/if}"><img src="{$smarty.const.RL_PLUGINS_URL}invoices/static/pdf.png" />&nbsp;{$lang.download}</a>
            </div>
            <div data-caption="{$lang.actions}">
                {strip}<a href="{$rlBase}{if $config.mod_rewrite}{$pages.invoices}/{$item.Txn_ID}.html{else}?page={$pages.invoices}&amp;item={$item.Txn_ID}{/if}">
                    {if $item.pStatus == 'unpaid'}
                        {$lang.invoice_pay}
                    {else}
                        {$lang.invoice_details}
                    {/if}
                </a>{/strip}
            </div>
        </div>

    {/foreach}
</div>

{paging calc=$pInfo.calc total=$invoices|@count current=$pInfo.current per_page=$config.invoices_per_page}

<!-- end list of invoices -->
