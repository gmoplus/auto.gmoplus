<!-- Listing label pictures refresh button -->
<tr class="body">
	<td class="list_td">{$lang.ls_rebuild_label}</td>
	<td class="list_td" align="center">
		<input id="ls_rebuild" type="button" value="{$lang.rebuild}" style="margin: 0;width: 100px;" />
	</td>
</tr>
<tr>
	<td style="height: 5px;" colspan="3">
		<script type="text/javascript">
        lang['rebuild']          = "{$lang.rebuild}";
        lang['lb_rebuilding']    = "{$lang.lb_rebuilding}";
		lang['ls_rebuild_label'] = "{$lang.ls_rebuild_label}";
		{literal}

		var labelRefreshStack = 1;

		$(document).ready(function(){
			$('#ls_rebuild').click(function(){
				$(this).val(lang['loading']);
                printMessage('alert', lang['loading']);
				refreshLabel();
			});
		});

		var refreshLabel = function(){
			var url = rlUrlHome + 'request.ajax.php';
			$.getJSON(url, {item: 'refreshLabel', stack: labelRefreshStack}, function(response){
				if ( response.stack == '1' ) {
					labelRefreshStack++;
					setTimeout('refreshLabel()', 500);
				}
				else if ( response.stack == '0' ) {
					labelRefreshStack = 0;
					$('#ls_rebuild').val(lang['rebuild']);
					printMessage('notice', lang['lb_rebuilding']);
				}
			});
		};

		{/literal}
		</script>
	</td>
</tr>
<!-- Listing label pictures refresh button end -->
