<!-- coinGate plugin -->
<div class="shc_divider"></div>
<div class="submit-cell">
    <div class="name">{$lang.coinGate_payment}</div>
    <div class="field checkbox-field">
        <label><input type="radio" {if $smarty.post.shc.coinGate_enable == 1}checked="checked"{/if} name="shc[coinGate_enable]" value="1" />{$lang.enabled}</label>
        <label><input type="radio" {if $smarty.post.shc.coinGate_enable == 0 || !$smarty.post.shc.coinGate_enable}checked="checked"{/if} name="shc[coinGate_enable]" value="0" />{$lang.disabled}</label>
    </div>
</div>
<div class="submit-cell clearfix">
    <div class="name">{$lang.coinGate_auth_token}</div>
    <div class="field single-field"><input type="text" name="shc[coinGate_auth_token]" maxlength="100" value="{if $smarty.post.shc.coinGate_auth_token}{$smarty.post.shc.coinGate_auth_token}{/if}" /></div>
</div>
<div class="submit-cell clearfix">
    <div class="name">{$lang.coinGate_receive_currency}</div>
    <div class="field single-field"><input type="text" name="shc[coinGate_receive_currency]" maxlength="100" value="{if $smarty.post.shc.coinGate_receive_currency}{$smarty.post.shc.coinGate_receive_currency}{/if}" /></div>
</div>
<!-- end coinGate plugin -->
