{if $shcShippingfields}
    {assign var='mf_form_prefix' value='f'}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='shc_payment_methods' name=$lang.shc_billing_address}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field.tpl' fields=$shcShippingfields}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
{/if}
