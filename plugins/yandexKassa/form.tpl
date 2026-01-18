<!-- yandexKassa plugin -->

<div id="yandexKassa-form">
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='yandexKassa_form' name=$lang.yandexKassa_form}
	{if $yandexKassa.complete}
    <div class="submit-cell">
        <div class="info" id="yandexKassa_notification">
			{$lang.yandexKassa_waiting}
        </div>
    </div>
	{/if}
    <div class="submit-cell">
        <div class="name">{$lang.yandexKassa_phone}</div>
        <div class="field">
			<input type="text" name="yandexKassa_phone" class="wauto" {if $yandexKassa.complete}disabled="disabled"{/if} />
        </div>
    </div>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
</div>
<script class="fl-js-dynamic">
	var yandexKassaID = '{$yandexKassa.id}';
	{literal}
	$(document).ready(function() {
		if (yandexKassaID) {
			setInterval(checkPayment, 1000 * 60 * 1);
		}
		if ($('ul#payment_gateways li input[name="gateway"]:checked').val() == 'yandexKassa') {
			$('#fs_credit_card_details, #fs_billing_details').addClass('hide');
		}
	});

	var checkPayment = function() {
		$.getJSON(rlConfig['ajax_url'], {mode: 'yandexKassaCheck', item: yandexKassaID}, function(response) {
			if (response) {
				if (response.status == 'OK' && response.url) {
					location.href = response.url;
				}
			}
		});        
	}
	
	$(document).on('click', 'ul#payment_gateways>li', function() {
		if ($(this).find('input').val() == 'yandexKassa') {
			$('#fs_credit_card_details, #fs_billing_details').addClass('hide');
		} else {
			if ($(this).data('form-type') == 'custom' || $(this).data('form-type') == 'default') {
				$('#fs_credit_card_details, #fs_billing_details').removeClass('hide');
			}
		}
	});
	{/literal}
</script>

<!-- end / yandexKassa plugin -->
