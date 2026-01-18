{assign var='shcFVal' value=$smarty.post.f}

<div class="d-none" id="price_variants">
    <div class="price_item auction mr-md-3 mb-2 mb-md-0">
        <div class="price-item-caption mb-1">{$lang.shc_start_price}</div>
        <input class="numeric price-start{if $isAuctionActive} qtip{/if}" type="text" name="fshc[shc_start_price]" size="8" maxlength="9" {if $smarty.post.fshc.shc_start_price}value="{$smarty.post.fshc.shc_start_price}"{/if} {if $isAuctionActive}readonly="readonly"{/if} />
    </div>
    <div class="price_item auction mr-md-3 mb-2 mb-md-0">
        <div class="mb-1 price-item-caption">{$lang.shc_reserved_price}</div>
        <input class="numeric{if $isAuctionActive} qtip{/if}" type="text" name="fshc[shc_reserved_price]" size="8" maxlength="9" {if $smarty.post.fshc.shc_reserved_price}value="{$smarty.post.fshc.shc_reserved_price}"{/if} {if $isAuctionActive}readonly="readonly"{/if} />
    </div>
</div>

{if $config.shc_method == 'multi' && $config.shc_commission > 0 && $config.shc_commission_enable}
    <div class="submit-cell auction">
        <div class="name">
            {$lang.shc_commission_start_price}
            <img class="qtip" alt="" title="{$lang.shc_commission_start_price_notice}" id="fd_shc_commission_start_price" src="{$rlTplBase}img/blank.gif" />
        </div>
        <div class="field combo-field">
            {if $config.system_currency_position == 'before'}<span class="shc-currency">{$defaultCurrencyName}</span>{/if}
            <span class="price-start-commission">0.00</span>
            {if $config.system_currency_position == 'after'} <span class="shc-currency">{$defaultCurrencyName}</span>{/if}
            ({strip}
            {$lang.shc_start_price_with_commission}:&nbsp;
            {if $config.system_currency_position == 'before'}<span class="shc-currency">{$defaultCurrencyName}</span>{/if}
            <span class="price-start-item-total">0.00</span>
            {if $config.system_currency_position == 'after'} <span class="shc-currency">{$defaultCurrencyName}</span>{/if}
            {/strip})
        </div>
    </div>
    <div class="submit-cell auction fixed">
        <div class="name">
            {$lang.shc_commission}
        </div>
        <div class="field combo-field">
            {if $config.system_currency_position == 'before'}<span class="shc-currency">{$defaultCurrencyName}</span>{/if}
            <span class="commission">{if $smarty.post.fshc.shc_commission}{$smarty.post.fshc.shc_commission}{else}0.00{/if}</span>
            {if $config.system_currency_position == 'after'} <span class="shc-currency">{$defaultCurrencyName}</span>{/if}
            ({strip}
            {$lang.shc_price_with_commission}:&nbsp;
            {if $config.system_currency_position == 'before'}<span class="shc-currency">{$defaultCurrencyName}</span>{/if}
            <span class="price-item-total">{if $shcFVal[$config.price_tag_field].value}{$shcFVal[$config.price_tag_field].value}{else}0.00{/if}</span>
            {if $config.system_currency_position == 'after'} <span class="shc-currency">{$defaultCurrencyName}</span>{/if}
            {/strip})
        </div>
    </div>
{/if}
<div class="submit-cell auction">
    <div class="name">
        {$lang.shc_bid_step}
        <span class="red">&nbsp;*</span>
    </div>
    <div class="field combo-field" id="sf_field_shc_bid_step">
        <input class="numeric wauto{if $isAuctionActive} qtip{/if}" size="8" type="text" name="fshc[shc_bid_step]" maxlength="11" {if $smarty.post.fshc.shc_bid_step}value="{$smarty.post.fshc.shc_bid_step}"{/if} {if $isAuctionActive}readonly="readonly"{/if} />
        <span class="shc-currency">{$defaultCurrencyName}</span>
    </div>
</div>

<div class="submit-cell auction">
    <div class="name">
        {$lang.shc_duration}
        <span class="red">&nbsp;*</span>
    </div>
    <div class="field combo-field" id="sf_field_shc_bid_days">
        <input class="numeric wauto{if $isAuctionActive} qtip{/if}" size="8" type="text" name="fshc[shc_days]" maxlength="11" {if $smarty.post.fshc.shc_days}value="{$smarty.post.fshc.shc_days}"{/if} {if $isAuctionActive}readonly="readonly"{/if} />
        {$lang.shc_days}
    </div>
</div>
{if $config.shc_digital_product}
    {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/digital_product.tpl'}
{/if}
<div class="submit-cell auction fixed quantity">
    <div class="name">
        {$lang.shc_quantity}
        <span class="red">&nbsp;*</span>
    </div>
    <div class="field d-flex" id="sf_field_shc_bid_quantity">
        <input class="numeric wauto" size="8" type="text" name="fshc[shc_quantity]" maxlength="11" value="{if $smarty.post.fshc.shc_quantity}{$smarty.post.fshc.shc_quantity}{else}0{/if}" {if $smarty.post.fshc.quantity_unlim == '1'}readonly="readonly"{/if} />
        <span class="mt-2 ml-2 mr-2 digital {if !$smarty.post.fshc.digital == '1'} hide{/if}">{$lang.or}</span>
        <span class="mt-2 custom-input digital {if !$smarty.post.fshc.digital == '1'} hide{/if}"><label><input type="checkbox" class="quantity-unlimited" name="fshc[quantity_unlim]" value="1" {if $smarty.post.fshc.quantity_unlim == '1'}checked="checked"{/if}>{$lang.unlimited}</label></span>
    </div>
</div>
<div class="submit-cell fixed available">
    <div class="name">
        {$lang.shc_available}
    </div>
    <div class="field inline-fields" id="sf_field_shc_bid_available">
        <span class="custom-input"><label><input type="radio" value="1" name="fshc[shc_available]" {if $smarty.post.fshc.shc_available == '1' || $smarty.post.fshc.shc_available == ''}checked="checked"{/if} />{$lang.yes}</label></span>
        <span class="custom-input"><label><input type="radio" value="0" name="fshc[shc_available]" {if $smarty.post.fshc.shc_available == '0'}checked="checked"{/if} />{$lang.no}</label></span>
    </div>
</div>

{if $pageInfo.Controller == 'edit_listing' && !$isAuctionActive}
<div class="submit-cell auction">
    <div class="name"></div>
    <div class="field inline-fields" id="sf_field_shc_update_start_time">
        <label><input type="checkbox" value="1" name="fshc[shc_update_start_time]" {if $smarty.post.fshc.shc_update_start_time == '1'}checked="checked"{/if} /> {$lang.shc_update_start_time}</label>
        <input type="hidden" name="fshc[shc_edit]" value="1" />
        {if $smarty.post.fshc.shc_mode != 'auction'}<input type="hidden" name="fshc[shc_first_edit]" value="1" />{/if}
    </div>
</div>
{/if}
