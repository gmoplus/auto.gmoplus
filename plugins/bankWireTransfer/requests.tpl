<!-- bankWireTransfer plugin -->

{include file=$smarty.const.RL_PLUGINS|cat:'bankWireTransfer/static/icons.svg'}

<div class="highlight">
	{if !empty($txn_info)}
		{include file=$smarty.const.RL_PLUGINS|cat:'bankWireTransfer'|cat:$smarty.const.RL_DS|cat:'request_details.tpl' order_info=$txn_info}
	{elseif !isset($smarty.get.item)}
		{if $requests}
            <div class="list-table">
                <div class="header">
                    <div class="center" style="width: 40px;">#</div>
                    <div>{$lang.item}</div>
                    <div style="width: 90px;">{$lang.total}</div>
                    <div style="width: 120px;">{$lang.txn_id}</div>
                    <div style="width: 100px;">{$lang.date}</div>
                    <div style="width: 100px;">{$lang.status}</div>
                    <div style="width: 90px;">{$lang.actions}</div>
                </div>
                {foreach from=$requests item='item' name='requestF'}
                    {math assign='iteration' equation='(((current?current:1)-1)*per_page)+iter' iter=$smarty.foreach.requestF.iteration current=$pInfo.current per_page=$config.shc_orders_per_page}

                    <div class="row" id="item_{$item.ID}">
                        <div class="center iteration no-flex">{$iteration}</div>
                        <div data-caption="{$lang.item}">{$item.Item_name}</div>
                        <div data-caption="{$lang.total}">
                            <span class="price-cell shc_price">{strip}
                                {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
                                {str2money string=$item.Total}
                                {if $config.system_currency_position == 'after'}&nbsp;{$config.system_currency}{/if}
                            {/strip}</span>
                        </div>
                        <div data-caption="{$lang.shc_order_key}">{$item.Txn_ID}</div>
                        <div data-caption="{$lang.date}">{$item.Date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
                        <div data-caption="{$lang.status}" id="txn_status_{$item.ID}">
                            <span class="item_{$item.pStatus}">{$item.Status}</span>
                            {if !empty($item.Doc)}
                            <a class="d-block" href="{$smarty.const.RL_FILES_URL}{$item.Doc}" target="_blank">
                                <svg width="18" height="18" viewBox="0 0 24 24" class="icon grid-icon-fill align-middle"><use xlink:href="#download"></use></svg>&nbsp;{$lang.bwt_doc_file}
                            </a>
                            {/if}
                        </div>
                        <div data-caption="{$lang.actions}" id="bwt_{$item.ID}">
                            {if $item.pStatus == 'unpaid'}
                                <input type="button" value="{$lang.bwt_activate}" id="bwtpayment-{$item.ID}" class="accept-payment" />
                            {else}
                                <a href="{$rlBase}{if $config.mod_rewrite}{$pages.bwt_requests}.html?item={$item.ID}{else}?page={$pages.bwt_requests}&amp;item={$item.ID}{/if}">
                                    {$lang.bwt_request_details}
                                </a>
                            {/if}
                        </div>
                    </div>
                {/foreach}
            </div>
			{paging calc=$pInfo.calc total=$requests|@count current=$pInfo.current per_page=$config.listings_per_page}
		{else}
			<div class="info">{$lang.bwt_no_requests}</div>
		{/if}
	{/if}
</div>

<script type="text/javascript">
{literal}

$(document).ready(function() {
	$('.accept-payment').click(function() {
	    var item_id = $(this).attr('id').split('-')[1];
        $(this).val(lang['loading']);
        $(this).attr('disabled', true);
        $.getJSON(rlConfig['ajax_url'], {mode: 'bwtCompleteTransaction', item: item_id}, function(response) {
            if (response) {
                if (response.status == 'OK') {
                    $('#bwt_' + item_id).html(response.html);
                    $('#txn_status_' + item_id).html(response.html_status);
                    printMessage('notice', response.message_text);
                } else {
                    printMessage('error', response.message_text);
                }
            }
        });
	});
});

{/literal}
</script>

<!-- end bankWireTransfer plugin -->
