{assign var='shcFVal' value=$smarty.post.f}
<table class="form">
    <tr class="auction fixed listing">
        <td class="name price_name">
            <span class="red">*</span> {$lang.price}
        </td>
        <td class="field" id="sf_field_shc_start_price" valign="bottom">
            <div class="price_item auction">
                <div class="price-caption">{$lang.shc_start_price}</div>
                <input class="numeric w70 price-start" type="text" style="width: 70px;" name="fshc[shc_start_price]" size="8" maxlength="15" {if $smarty.post.fshc.shc_start_price}value="{$smarty.post.fshc.shc_start_price}"{/if} />
            </div>
            <div class="price_item auction">
                <div class="price-caption">{$lang.shc_reserved_price}</div>
                <input class="numeric w70" type="text" style="width: 70px;" name="fshc[shc_reserved_price]" size="8" maxlength="15" {if $smarty.post.fshc.shc_reserved_price}value="{$smarty.post.fshc.shc_reserved_price}"{/if} />
            </div>
            <div class="price_item auction fixed listing">
                <div class="price-caption">{$lang.shc_buy_now}</div>
                <!-- price field will be placed here -->
            </div>
        </td>
    </tr>
    {if $config.shc_method == 'multi' && $config.shc_commission > 0  && $config.shc_commission_enable}
    <tr class="auction">
        <td class="name">
            {$lang.shc_commission_start_price}
            <img class="qtip" alt="" src="{$rlTplBase}img/blank.gif" title="{$lang.shc_commission_start_price_notice}" />
        </td>
        <td class="field">
            {if $config.system_currency_position == 'before'}<span class="shc-currency">{$currency.0.name}</span>{/if}
            <span class="price-start-commission">0.00</span>
            {if $config.system_currency_position == 'after'} <span class="shc-currency">{$currency.0.name}</span>{/if}
            ({strip}
            {$lang.shc_start_price_with_commission}:&nbsp;
            {if $config.system_currency_position == 'before'}<span class="shc-currency">{$currency.0.name}</span>{/if}
            <span class="price-start-item-total">0.00</span>
            {if $config.system_currency_position == 'after'} <span class="shc-currency">{$currency.0.name}</span>{/if}
            {/strip})
        </td>
    </tr>
    <tr class="auction fixed">
        <td class="name">
            {$lang.shc_commission}
        </td>
        <td class="field">
            {if $config.system_currency_position == 'before'}<span class="shc-currency">{$currency.0.name}</span>{/if}
            <span class="commission">{if $smarty.post.fshc.shc_commission}{$smarty.post.fshc.shc_commission}{else}0.00{/if}</span>
            {if $config.system_currency_position == 'after'} <span class="shc-currency">{$currency.0.name}</span>{/if}
            ({strip}
            {$lang.shc_price_with_commission}:&nbsp;
            {if $config.system_currency_position == 'before'}<span class="shc-currency">{$currency.0.name}</span>{/if}
            <span class="price-item-total">{if $shcFVal[$config.price_tag_field].value}{$shcFVal[$config.price_tag_field].value}{else}0.00{/if}</span>
            {if $config.system_currency_position == 'after'} <span class="shc-currency">{$currency.0.name}</span>{/if}
            {/strip})
        </td>
    </tr>
    {/if}
    <tr class="auction">
        <td class="name">
            {$lang.shc_bid_step}
        </td>
        <td class="field" id="sf_field_shc_bid_step">
            <input class="numeric w50" type="text" style="width: 70px;" name="fshc[shc_bid_step]" maxlength="11" {if $smarty.post.fshc.shc_bid_step}value="{$smarty.post.fshc.shc_bid_step}"{/if} />&nbsp;<span class="shc-currency">{$currency.0.name}</span>
        </td>
    </tr>
    <tr class="auction">
        <td class="name">
            {$lang.shc_duration}
        </td>
        <td class="field" id="sf_field_shc_days">
            <input class="numeric w50" type="text" style="width: 70px;" name="fshc[shc_days]" maxlength="11" {if $smarty.post.fshc.shc_days}value="{$smarty.post.fshc.shc_days}"{/if} />&nbsp;<span>{$lang.shc_days}</span>
        </td>
    </tr>
    {if $config.shc_digital_product}
        {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/admin/view/digital_product.tpl'}
    {/if}
    <tr class="auction fixed quantity">
        <td class="name">
            {$lang.shc_quantity}
        </td>
        <td class="field" id="sf_field_shc_quantity">
            <input class="numeric w50" type="text" style="width: 70px;" name="fshc[shc_quantity]" maxlength="11" value="{if $smarty.post.fshc.shc_quantity}{$smarty.post.fshc.shc_quantity}{else}0{/if}" {if $smarty.post.fshc.quantity_unlim == '1'}readonly="readonly"{/if} />
            <span class="digital {if !$smarty.post.fshc.digital == '1'} hide{/if}">&nbsp;{$lang.or}&nbsp;</span>
            <span class="digital {if !$smarty.post.fshc.digital == '1'} hide{/if}"><label><input type="checkbox" class="quantity-unlimited" name="fshc[quantity_unlim]" value="1" {if $smarty.post.fshc.quantity_unlim == '1'}checked="checked"{/if}>&nbsp;{$lang.unlimited}</label></span>
        </td>
    </tr>
    <tr class="fixed available">
        <td class="name">
            {$lang.shc_available}
        </td>
        <td class="field" id="sf_field_shc_available">
            <label><input type="radio" value="1" name="fshc[shc_available]" {if $smarty.post.fshc.shc_available == '1' || !isset($smarty.post.fshc.shc_available)}checked="checked"{/if} /> {$lang.yes}</label>
            <label><input type="radio" value="0" name="fshc[shc_available]" {if $smarty.post.fshc.shc_available == '0'}checked="checked"{/if} /> {$lang.no}</label>
        </td>
    </tr>
    {if $smarty.get.action == 'edit'}
    <tr class="auction">
        <td class="name"></td>
        <td class="field" id="sf_field_shc_update_start_time">
            <label><input type="checkbox" value="1" name="fshc[shc_update_start_time]" {if $smarty.post.fshc.shc_update_start_time == '1'}checked="checked"{/if} /> {$lang.shc_update_start_time}</label>
            <input type="hidden" name="fshc[shc_edit]" value="1" />
        </td>
    </tr>
    {/if}
</table>
