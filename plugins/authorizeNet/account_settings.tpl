<!-- authorizeNet plugin -->
<div class="shc_divider"></div>
<div class="submit-cell">
	<div class="name">{$lang.authorizeNet_module}</div>
	<div class="field checkbox-field">
		<label><input type="radio" {if $smarty.post.shc.authorizeNet_enable == 1}checked="checked"{/if} name="shc[shc_authorizeNet_enable]" value="1" />{$lang.enabled}</label>
		<label><input type="radio" {if $smarty.post.shc.authorizeNet_enable == 0 || !$smarty.post.shc.authorizeNet_enable}checked="checked"{/if} name="shc[shc_authorizeNet_enable]" value="0" />{$lang.disabled}</label>
	</div>
</div>
<div class="submit-cell clearfix">
	<div class="name">{$lang.authorizeNet_account_id}</div>
	<div class="field single-field"><input type="text" name="shc[authorizeNet_account_id]" maxlength="100" value="{if $smarty.post.shc.authorizeNet_account_id}{$smarty.post.shc.authorizeNet_account_id}{/if}" /></div>
</div>
<div class="submit-cell clearfix">
	<div class="name">{$lang.authorizeNet_transaction_key}</div>
	<div class="field single-field"><input type="text" name="shc[authorizeNet_transaction_key]" maxlength="100" value="{if $smarty.post.shc.authorizeNet_transaction_key}{$smarty.post.shc.authorizeNet_transaction_key}{/if}" /></div>
</div>
<!-- end authorizeNet plugin -->
