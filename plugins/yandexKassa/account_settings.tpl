<!-- yandexKassa plugin -->
<div class="divider"></div>
<div class="submit-cell">
	<div class="name">{$lang.yandexKassa_module}</div>
	<div class="field inline-fields">
		<span class="custom-input">
            <label><input type="radio" {if $smarty.post.shc.yandexKassa_enable == 1}checked="checked"{/if} name="shc[yandexKassa_enable]" value="1" />{$lang.enabled}</label>
        </span>
        <span class="custom-input">
            <label><input type="radio" {if $smarty.post.shc.yandexKassa_enable == 0 || !$smarty.post.shc.yandexKassa_enable}checked="checked"{/if} name="shc[yandexKassa_enable]" value="0" />{$lang.disabled}</label>
        </span>
	</div>
</div>
{if $config.shc_escrow}
    {include file=$smarty.const.RL_PLUGINS|cat:'yandexKassa/payout.tpl'}
{else}
    {if $config.shc_commission_enable && $config.shc_commission > 0}
        <div class="submit-cell clearfix">
            <div class="name">{phrase key='config+name+yandexKassa_api_id' db_check=true}</div>
            <div class="field single-field"><input type="text" name="shc[yandexKassa_account_id]" maxlength="255" value="{if $smarty.post.shc.yandexKassa_account_id}{$smarty.post.shc.yandexKassa_account_id}{/if}" /></div>
        </div>
    {else}
        <div class="submit-cell clearfix">
            <div class="name">{phrase key='config+name+yandexKassa_api_id' db_check=true}</div>
            <div class="field single-field"><input type="text" name="shc[yandexKassa_api_id]" maxlength="255" value="{if $smarty.post.shc.yandexKassa_api_id}{$smarty.post.shc.yandexKassa_api_id}{/if}" /></div>
        </div>
        <div class="submit-cell clearfix">
            <div class="name">{phrase key='config+name+yandexKassa_secret_key' db_check=true}</div>
            <div class="field single-field"><input type="text" name="shc[yandexKassa_secret_key]" maxlength="255" value="{if $smarty.post.shc.yandexKassa_secret_key}{$smarty.post.shc.yandexKassa_secret_key}{/if}" /></div>
        </div>
        <div class="submit-cell clearfix">
            <div class="name">{phrase key='config+name+yandexKassa_payment_method' db_check=true}</div>
            <div class="field single-field">
                <select name="shc[yandexKassa_payment_method]">
                    {foreach from=$yk_methods item='method'}
                        <option value="{$method.key}" {if $smarty.post.shc.yandexKassa_payment_method == $method.Key}selected="selected"{/if}>{$method.name}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="submit-cell clearfix hide">
            <div class="name">{phrase key='config+name+yandexKassa_qiwi_phone' db_check=true}</div>
            <div class="field single-field"><input type="text" name="shc[yandexKassa_qiwi_phone]" maxlength="255" value="{if $smarty.post.shc.yandexKassa_qiwi_phone}{$smarty.post.shc.yandexKassa_qiwi_phone}{/if}" /></div>
        </div>
        <script class="fl-js-dynamic">
        {literal}
            $(document).ready(function() {
                controlYandexMethods($('select[name="shc[yandexKassa_payment_method]"] option:selected').val());

                $('select[name="shc[yandexKassa_payment_method]"]').change(function() {
                    controlYandexMethods($(this).val());
                });
            });

            var controlYandexMethods = function(method) {
                if (method == 'qiwi') {
                    $('input[name="shc[yandexKassa_qiwi_phone]"]').parent().parent('div.submit-cell').removeClass('hide');
                } else {
                    $('input[name="shc[yandexKassa_qiwi_phone]"]').parent().parent('div.submit-cell').addClass('hide');
                }
            }
        {/literal}
        </script>
    {/if}
{/if}
<!-- end yandexKassa plugin -->
