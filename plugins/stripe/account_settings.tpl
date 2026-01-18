<!-- stripe plugin -->
<div class="shc_divider"></div>
{if $isStripeSupported}
    <div class="submit-cell">
        <div class="name">{$payment_gateways.stripe.name}</div>
        <div class="field checkbox-field">
            <label><input type="radio" {if $shcAccountSettings.stripe_enable == 1}checked="checked"{/if} name="shc[stripe_enable]" value="1" />{$lang.enabled}</label>
            <label><input type="radio" {if $shcAccountSettings.stripe_enable == 0 || !$shcAccountSettings.stripe_enable}checked="checked"{/if} name="shc[stripe_enable]" value="0" />{$lang.disabled}</label>
        </div>
    </div>
    {if $config.shc_commission_enable}
        <div class="submit-cell">
            <div class="name">{$lang.stripe_account_id}</div>
            <div class="field single-field stripe-account">
                {if $shcAccountSettings.stripe_account_id} 
                    <div class="table-cell clearfix small">
                        <div class="name">{$lang.name}</div>
                        <div class="value">{$stripeAccount.display_name}</div>
                    </div>
                    <div class="table-cell clearfix small">
                        <div class="name">{$lang.stripe_account_id}</div>
                        <div class="value">{$shcAccountSettings.stripe_account_id}</div>
                    </div>
                    {if $stripeAccount.verification}
                    <div class="table-cell clearfix small">
                        <div class="name">{$lang.stripe_verification}</div>
                        <div class="value">{$stripeAccount.verification}</div>
                    </div>
                    {/if}
                    <div class="table-cell clearfix small">
                        <div class="name">{$lang.status}</div>
                        <div class="value">{$stripeAccount.status}</div>
                    </div>
                    {if $stripeAccount.url}
                    <div class="table-cell clearfix small">
                        <div class="stripe-area"><a target="_blank" href="{$stripeAccount.url}" class="stripe-login white"><span>{$lang.stripe_login}</span></a></div>
                    </div>
                    {/if}
                    <div class="table-cell clearfix small">
                        <a href="javascript://" class="stripe-delete-account white">{$lang.stripe_delete}</a>
                    </div>
                {else}
                    <div class="stripe-area"><a href="javascript://" class="stripe-connect white"><span>{$lang.stripe_connect}</span></a></div>
                {/if}
            </div>
        </div>
        <script class="fl-js-dynamic">
        {literal}
        $(document).ready(function(){
            $('.stripe-connect').click(function() {
                var data = {
                    mode: 'stripeConnect'
                };
                flUtil.ajax(data, function(response) {
                    if (response.status == 'OK') {
                        window.location.href = response.stripeUrl;
                    }
                    if (status == 'ERROR') {
                        printMessage('error', response.message);
                    }
                });
            });
            $(document).on('click', '.stripe-delete-account', function() {
                $('.stripe-delete-account').flModal({
                    caption: '',
                    content: '{/literal}{$lang.stripe_do_you_want_delete_account}{literal}',
                    prompt: 'stripeDeleteAccount()',
                    width: 'auto',
                    height: 'auto',
                    click: false
                });
            });
        });

        var stripeDeleteAccount = function() {
            var data = {
                mode: 'stripeDeleteAccount'
            };
            flUtil.ajax(data, function(response) {
                if (response.status == 'OK') {
                    window.location.href = response.url;
                }
                if (status == 'ERROR') {
                    printMessage('error', response.message);
                }
            });
        }
        {/literal}
        </script>
    {else}
        <div class="submit-cell clearfix">
            <div class="name">{$lang.stripe_publishable_key}</div>
            <div class="field single-field"><input type="text" name="shc[stripe_publishable_key]" maxlength="255" value="{if $shcAccountSettings.stripe_publishable_key}{$shcAccountSettings.stripe_publishable_key}{/if}" /></div>
        </div>
        <div class="submit-cell clearfix">
            <div class="name">{$lang.stripe_secret_key}</div>
            <div class="field single-field"><input type="text" name="shc[stripe_secret_key]" maxlength="255" value="{if $shcAccountSettings.stripe_secret_key}{$shcAccountSettings.stripe_secret_key}{/if}" /></div>
        </div>
    {/if}
{else}
    <div class="stripe-warning">{$lang.stripe_shopping_cart_not_supported}</div>
{/if}
<!-- end stripe plugin -->
