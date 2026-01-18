<!-- invoice tpl -->

{addCSS file=$smarty.const.RL_PLUGINS_URL|cat:'invoices/static/style.css'}

<div class="highlight">

{if !empty($invoice_info)}
    {include file=$smarty.const.RL_PLUGINS|cat:'invoices'|cat:$smarty.const.RL_DS|cat:'invoice_details.tpl'}
{else}
    {if $invoices}
        {include file=$smarty.const.RL_PLUGINS|cat:'invoices'|cat:$smarty.const.RL_DS|cat:'list.tpl'}
    {else}
        <div class="info">{$lang.no_account_invoices}</div>
    {/if}
{/if}

</div>

<!-- invoice tpl end -->
