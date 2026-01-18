<!-- shoppingCart plugin -->
<div id="area_shoppingCart" class="tab_area hide">
    <form method="post" action="{pageUrl page='my_profile'}" enctype="multipart/form-data" data-field-prefix="f">
        <input type="hidden" name="form" value="settings" />

        {assign var='sf_key' value='shoppingCart'}
        {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/shipping_address.tpl'}

        {if $payment_gateways}
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='shc_payment_methods' name=$lang.payment_gateways}
                {assign var='shcPaymentGateways' value=","|explode:$config.shc_payment_gateways}
                {if 'paypal'|in_array:$shcPaymentGateways && $payment_gateways.paypal}
                    <div class="submit-cell">
                        <div class="name">{$payment_gateways.paypal.name}</div>
                        <div class="field checkbox-field">
                            <label><input type="radio" {if $smarty.post.shc.paypal_enable == 1}checked="checked"{/if} name="shc[paypal_enable]" value="1" />{$lang.enabled}</label>
                            <label><input type="radio" {if $smarty.post.shc.paypal_enable == 0 || !$smarty.post.shc.paypal_enable}checked="checked"{/if} name="shc[paypal_enable]" value="0" />{$lang.disabled}</label>
                        </div>
                    </div>
                    <div class="submit-cell clearfix">
                        <div class="name">{$shcLang.paypal_account_email}</div>
                        <div class="field single-field"><input type="text" name="shc[paypal_email]" maxlength="100" value="{if $smarty.post.shc.paypal_email}{$smarty.post.shc.paypal_email}{/if}" /></div>
                    </div>
                {/if}
                {if '2co'|in_array:$shcPaymentGateways && $payment_gateways.2co}
                    <div class="divider"></div>
                    <div class="submit-cell">
                        <div class="name">{$payment_gateways.2co.name}</div>
                        <div class="field checkbox-field">
                            <label><input type="radio" {if $smarty.post.shc.2co_enable == 1}checked="checked"{/if} name="shc[2co_enable]" value="1" />{$lang.enabled}</label>
                            <label><input type="radio" {if $smarty.post.shc.2co_enable == 0 || !$smarty.post.shc.2co_enable}checked="checked"{/if} name="shc[2co_enable]" value="0" />{$lang.disabled}</label>
                        </div>
                    </div>
                    <div class="submit-cell clearfix">
                        <div class="name">{$shcLang.2co_id}</div>
                        <div class="field single-field"><input type="text" name="shc[2co_id]" maxlength="100" value="{if $smarty.post.shc.2co_id}{$smarty.post.shc.2co_id}{/if}" /></div>
                    </div>
                    <div class="submit-cell clearfix">
                        <div class="name">{$shcLang.2co_secret_word}</div>
                        <div class="field single-field"><input type="text" name="shc[2co_secret_word]" maxlength="100" value="{if $smarty.post.shc.2co_secret_word}{$smarty.post.shc.2co_secret_word}{/if}" /></div>
                    </div>
                    <div class="submit-cell clearfix">
                        <div class="name">{$shcLang.2co_secret_key}</div>
                        <div class="field single-field"><input type="text" name="shc[2co_secret_key]" maxlength="100" value="{if $smarty.post.shc.2co_secret_key}{$smarty.post.shc.2co_secret_key}{/if}" /></div>
                    </div>
                {/if}

                {rlHook name='shoppingCartAccountSettings'}

            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
        {/if}

        {if $config.shc_allow_cash && $payment_gateways}
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='shc_cash_payment' name=$lang.shc_payment_cash}

            <div class="submit-cell clearfix">
                <div class="name">{$shcLang.shc_allow_cash}</div>
                <div class="field inline-fields">
                    <span class="custom-input">
                        <label><input type="radio" {if $smarty.post.shc.allow_cash == 1}checked="checked"{/if} name="shc[allow_cash]" value="1" />{$lang.enabled}</label>
                    </span>
                    <span class="custom-input">
                        <label><input type="radio" {if $smarty.post.shc.allow_cash == 0 || !$smarty.post.shc.allow_cash}checked="checked"{/if} name="shc[allow_cash]" value="0" />{$lang.disabled}</label>
                    </span>
                </div>
            </div>

            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
        {/if}

        <div class="submit-cell buttons">
            <div class="name"></div>
            <div class="field"><input type="submit" value="{$lang.save}" /></div>
        </div>
    </form>
</div>

<script class="fl-js-dynamic">
    var tab_cart = '{$smarty.get.rlVareables}';
    {literal}
    $(document).ready(function(){
        if (tab_cart == 'shoppingCart') {
            hashTabsSwitcher(tab_cart);
        }
        flUtil.loadScript(rlConfig['tpl_base'] + 'js/form.js', function() {
            flForm.fileFieldAction();
        });
    });
    {/literal}
</script>
<!-- end shoppingCart plugin -->
