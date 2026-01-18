{if $watermark}
	<table>
		<tr>
			<td>
			<div class="status_box">
				<input type="hidden" name="watermark{if $large}Large{/if}[{$code}]" value="{$watermark}"/>
				<img class="status" src="{$smarty.const.RL_URL_HOME}files/watermark/{$watermark}">
                <img src="{$rlTplBase}img/blank.gif" class="delete_item" alt="{$lang.delete}" title="{$lang.delete}" data-key="{$sPost.key}" data-code="{$code}" data-watermark="{$watermark}" data-large="{$large}" />

			</div>
			</td>
			<td>
				<span class="field_description_noicon">{if $large}{$lang.ls_label_large}{else}{$lang.ls_label}{/if}</span>
			</td>
		</tr>
	</table>
{else}
	<input type="file" name="watermark{if $large}Large{/if}[{$code}]" accept="image/*" />
	<span class="field_description_noicon">{if $large}{$lang.ls_label_large}{else}{$lang.ls_label}{/if}</span><span class="field_description">{if $large}{$lang.ls_watermarks_large_label_hint}{else}{$lang.ls_watermarks_label_hint}{/if}</span>
{/if}
