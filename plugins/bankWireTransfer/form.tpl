<!-- bankWireTransfer plugin -->

<div id="bankWireTransfer-form">
	
	{if $txn_info}
		{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='order_information' name=$lang.bwt_order_information}
			<div class="table-cell">
				<div class="name">{$lang.item}</div>
				<div class="value">{if $txn_info.Item}{$txn_info.Item}{else}{$smarty.session.complete_payment.item_name}{/if}</div>
			</div>
			<div class="table-cell">
				<div class="name">{$lang.txn_id}</div>
				<div class="value">{if $txn_info.Txn_ID}{$txn_info.Txn_ID}{else}{$txn_id}{/if}</div>
			</div>
			<div class="table-cell">
				<div class="name">{$lang.price}</div>
				<div class="value">{if $config.system_currency_position == 'before'}{$config.system_currency}{/if}{$txn_info.Total|number_format:2:'.':','} {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}</div>
			</div>
		{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
	{/if}
	    
	{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='payment_info' name=$lang.bwt_payment_details}
        {if $payment_details}
            <div class="table-cell">
                <div class="value">
                    {$payment_details.content}
                </div>
            </div>
        {else}
            <div class="static-content">{$lang.bwt_missing_payment_details}</div>
        {/if}
	{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

	{if $completed}
		<div class="table-cell">
            {if $txn_info.Txn_ID}
                {assign var='txnID' value=$txn_info.Txn_ID}
            {else}
                {assign var='txnID' value=$txn_id}
            {/if}
            <a class="button" href="{$smarty.session.complete_payment.success_url}">{$lang.bwt_continue}</a>&nbsp;&nbsp;
			<a target="_blank" class="margin" href="{pageUrl page='bwt_print' vars='txn_id='|cat:$txnID}">{$lang.print_page}</a>
		</div>
	{/if}
</div>

<script  class="fl-js-dynammic">
    {if $completed}
        {literal}
        $('ul#payment_gateways li input[type="radio"]').each(function() {
            if ($(this).val() == 'bankWireTransfer' && $(this).is(':checked')) {
                $('#fs_credit_card_details, #fs_billing_details').addClass('hide');
            }
        });
        $(document).ready(function(){
            $('#fs_order_details').remove();
            $('#payment_gateways').parent().parent().parent().remove();
            if ($('#form-checkout').find('div.form-buttons').length) {
                $('#form-checkout').find('div.form-buttons').remove();
            }
            // remove checkout button
            if ($('#form-checkout').find('input[type="submit"]').length) {
                $('#form-checkout').find('input[type="submit"]').remove();    
            }
            // remove cancel button
            if ($('#form-checkout').find('a.close').length) {
                $('#form-checkout').find('a.close').remove();    
            }
        });
        {/literal}
    {/if}
</script>

<!-- end bankWireTransfer plugin -->
