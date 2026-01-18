<!-- authorizeNet plugin -->

<script type="text/javascript">
	{literal}
	$(document).ready(function(){
		if ($('#subscription_period option:selected').val() == 'day') {
			$('#period_total').empty();
			$('#period_total').append($('<option value="">{/literal}{$lang.select}{literal}</option>')); 
			for (var i = 1; i < 31; i++) {
				if (i == period_total) {
					$('#period_total').append($('<option value="'+i+'" selected="selected">'+i+'</option>'));
				} else {
					$('#period_total').append($('<option value="'+i+'">'+i+'</option>'));
				}
			}
			$('#subscription_period_total').show();	
		}
		$('input[name="sop[sop_authorizenet_interval_length]"]').after('<span class="field_description">{/literal}{$lang.aNet_interval_length_notice}{literal}</span>');
	});
	{/literal}
</script>

<!-- end / authorizeNet plugin -->