<!-- shipping info  -->

<div id="shipping_fields">
    {if $shcShippingfields}
        {assign var='mf_form_prefix' value='f'}
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='shc_shipping_location_details' name=$lang.shc_shipping_details}

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field.tpl' fields=$shcShippingfields}

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
    {/if}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='shc_cart_details' name=$lang.shc_cart_details}
    <div class="submit-cell clearfix">
        <div class="name"></div>
        <div class="field">
            <div class="list-table cart-items-table">
                {foreach from=$cart.items item='item' name='itemsF'}
                {if !$item.shc_available}{continue}{/if}

                <input type="hidden" name="items[{$item.ID}][id]" value="{$item.Item_ID}">
                <div class="row no-gutters pl-0" id="item_{$item.ID}">
                    <div data-caption="{$lang.item}" class="d-flex flex-column flex-md-row {if $smarty.foreach.itemsF.first}pt-0{else}pt-3{/if} pb-3">
                        <a href="{$item.listing_link}" target="_blank" class="mr-2">
                            <img alt="{$item.title}" class="shc-item-picture" src="{if empty($item.main_photo)}{$rlTplBase}img/no-picture.jpg{else}{$smarty.const.RL_URL_HOME}files/{$item.main_photo}{/if}" />
                        </a>

                        <div class="mt-2 mt-md-0">
                            <a href="{$item.listing_link}" target="_blank">
                                {$item.Item} {strip}(
                                <span class="shc_price" id="price_{$item.ID}">
                                    {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
                                    {str2money string=$item.total}
                                    {if $config.system_currency_position == 'after'} {$config.system_currency}{/if}
                                </span>
                                ){/strip}
                            </a>
                            {if !$item.Digital}
                                <div class="mt-1">
                                    {if $item.Quantity_changed}
                                        {assign var='quantity_phrase' value=`$smarty.ldelim`quantity`$smarty.rdelim`}
                                        <div class="mb-1 red">{$lang.shc_quantity_changed_hint|replace:$quantity_phrase:$item.Quantity}</div>
                                    {/if}

                                    {if $item.Free_shipping || $item.Shipping_price_type == 'free'}
                                        <div class="mb-1">
                                            {$lang.shc_shipping_price}: {$lang.free}
                                        </div>
                                        <div class="d-flex flex-wrap">
                                            {if is_array($item.Shipping_method_fixed) && $item.Shipping_method_fixed|@count > 1}
                                                <span class="mr-2 mb-1">{$lang.shc_shipping_method}:</span>
                                                <span>
                                                {foreach from=$item.Shipping_method_fixed item='shippingMethod' name='shippingMethodF'}
                                                    {assign var='shippingMethodVal' value='shc_'|cat:$shippingMethod}
                                                        <span class="custom-input mr-2">
                                                        <label><input {if $smarty.post.items[$item.ID].shipping_method_fixed == $shippingMethod || $smarty.foreach.shippingMethodF.first}checked="checked"{/if} type="radio" name="items[{$item.ID}][shipping_method_fixed]" value="{$shippingMethod}" />{$lang[$shippingMethodVal]}</label>
                                                    </span>
                                                {/foreach}
                                                </span>
                                            {else}
                                                {assign var='shippingMethodVal' value='shc_'|cat:$item.Shipping_method_fixed.0}
                                                {$lang.shc_shipping_method}: {if !empty($lang[$shippingMethodVal])}{$lang[$shippingMethodVal]}{else}{$lang.shc_pickup}{/if}
                                            {/if}
                                        </div>
                                    {elseif $item.Shipping_price_type == 'fixed'}
                                        {if $item.Shipping_fixed_prices|count > 0 && $config.shc_shipping_price_fixed == 'multi'}
                                            {assign var='currency' value='currency'|df}
                                            <div data-item="{$item.ID}" class="table-cell shipping-fixed-price-{$item.ID}">
                                                <div class="name">{$lang.shc_shipping_price} <span class="red">*</span></div>
                                                <div class="field single-field">
                                                    <select id="item-fixed-price-{$item.ID}" name="items[{$item.ID}][fixed_price]" class="item-fixed-price" data-item="{$item.ID}">
                                                        <option value="">{$lang.select}</option>
                                                        {foreach from=$item.Shipping_fixed_prices item='fixed_price' key='key'}
                                                            <option value="{$key}" data-fixed-price="{$fixed_price.price}" {if $smarty.post.items[$item.ID].fixed_index == $key}selected="selected"{/if}>{$currency.0.name}{$fixed_price.price} - {$fixed_price.name}</option>
                                                        {/foreach}
                                                    </select>
                                                </div>
                                            </div>
                                        {else}
                                            <div data-item="{$item.ID}" class="shipping-fixed-price-{$item.ID}">
                                                {$lang.shc_shipping_price}:
                                                <span class="shc_price" title="{$lang.shc_shipping_price_type_fixed}">
                                                    {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
                                                    {str2money string=$item.Shipping_price}
                                                    {if $config.system_currency_position == 'after'} {$config.system_currency}{/if}
                                                </span>
                                            </div>
                                        {/if}

                                        <div class="d-flex flex-wrap">
                                            {if $item.Shipping_method_fixed|@count > 1}
                                                <span class="mr-2 mb-1">{$lang.shc_shipping_method}:</span>
                                                <span>
                                                {foreach from=$item.Shipping_method_fixed item='shippingMethod' name='shippingMethodF'}
                                                    {assign var='shippingMethodVal' value='shc_'|cat:$shippingMethod}
                                                    <span class="custom-input mr-2">
                                                        <label><input {if $smarty.post.items[$item.ID].shipping_method_fixed == $shippingMethod || $smarty.foreach.shippingMethodF.first}checked="checked"{/if} type="radio" name="items[{$item.ID}][shipping_method_fixed]" class="shipping-method-fixed" value="{$shippingMethod}" data-item="{$item.ID}" />{$lang[$shippingMethodVal]}</label>
                                                    </span>
                                                {/foreach}
                                                </span>
                                            {else}
                                                {assign var='shippingMethodVal' value='shc_'|cat:$item.Shipping_method_fixed.0}
                                                {$lang.shc_shipping_method}: {$lang[$shippingMethodVal]}
                                            {/if}
                                        </div>

                                        {if $item.Shipping_discount_at && $item.Quantity >= $item.Shipping_discount_at}
                                            <div>
                                                {$lang.shc_shipping_discount}: {$item.Shipping_discount}%
                                            </div>
                                        {/if}
                                    {else}
                                        <div class="submit-cell">
                                            <div class="name">{$lang.shc_shipping_method} <span class="red">*</span></div>
                                            <div class="field single-field">
                                                <select id="item-shipping-method-{$item.ID}" name="items[{$item.ID}][method]" class="item-shipping-methods" data-item="{$item.ID}">
                                                    <option value="">{$lang.select}</option>
                                                    {foreach from=$shipping_methods item='method'}
                                                        {if $item.shipping[$method.Key].enable}
                                                            <option value="{$method.Key}" data-fixed-price="{$item.shipping[$method.Key].price}" {if $smarty.post.items[$item.ID].method == $method.Key}selected="selected"{/if}>{$method.name}</option>
                                                        {/if}
                                                    {/foreach}
                                                </select>
                                            </div>
                                        </div>
                                        {foreach from=$shipping_methods item='method'}
                                            {if $item.shipping[$method.Key].enable}
                                                <div id="shipping-method-{$item.ID}-{$method.Key}" class="hide">
                                                    {if $item.shipping[$method.Key].price}
                                                        {$lang.shc_fixed_shipping_price}:&nbsp;<span id="shipipng-price-fixed-{$item.ID}">{if $config.system_currency_position == 'before'}{$config.system_currency}{/if}{str2money string=$item.shipping[$method.Key].price} {if $config.system_currency_position == 'after'} {$config.system_currency}{/if}</span>
                                                    {else}
                                                        {assign var='methodKey' value=$method.Key|lower}
                                                        {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/shipping/'|cat:$methodKey|cat:'/view/cart_step.tpl' item_id=$item.ID item_data=$item.shipping services=$item.services}
                                                    {/if}
                                                </div>
                                            {/if}
                                        {/foreach}
                                    {/if}
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>
                {/foreach}
            </div>
        </div>
    </div>

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

    <div class="mt-3 shc-mobile-inline-cell">
        <div class="table-cell">
            <div class="name">{$lang.shc_shipping_price}</div>
            <div class="value inline-fields">
                <span class="value shc_price shc-total-price" id="order-shipping-price">{strip}
                    {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
                    {str2money string=$cart.shipping_price}
                    {if $config.system_currency_position == 'after'} {$config.system_currency}{/if}
                {/strip}</span>
            </div>
        </div>

        <div class="table-cell">
            <div class="name">{$lang.total}</div>
            <div class="value inline-fields">
                <span class="value shc_price shc-total-price" id="order-total">{strip}
                {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
                {str2money string=$cart.total}
                {if $config.system_currency_position == 'after'} {$config.system_currency}{/if}
            {/strip}</span>
            </div>
        </div>
    </div>

    {rlHook name='shoppingCartShippingField'}
</div>
<!-- end shipping info -->

<script class="fl-js-dynamic">
    flynax.qtip();
    var shcItems = new Array();
    lang['notice_field_empty'] = '{$lang.notice_field_empty}';
    var shcCountry = $('select[name="f[country]"] option:selected').val();
    var shippingPrice = parseFloat({$cart.shipping_price|number_format:2:'.':','});
    var shcShippingMethods = [{foreach from=$shipping_methods item='method' name='FshippingMethods'}'{$method.Key}'{if !$smarty.foreach.FshippingMethods.last},{/if}{/foreach}];
    var order_total = 0;

    {foreach from=$cart.items item='item'}
        shcItems[{$item.ID}] = new Array();
        shcItems[{$item.ID}]['ID'] = {$item.ID};
        shcItems[{$item.ID}]['Price'] = {$item.Price};
        shcItems[{$item.ID}]['Quantity'] = {$item.Quantity};
        shcItems[{$item.ID}]['Shipping_discount'] = {$item.Shipping_discount};
        shcItems[{$item.ID}]['Shipping_discount_at'] = {$item.Shipping_discount_at};
    {/foreach}

    {literal}
    $(document).ready(function(){
        var total_price_data = shoppingCart.getPrice($('#order-total'));
        order_total = total_price_data ? total_price_data['price'] : 0;

        shoppingCart.calculateShippingPrice();  
        $('select.item-fixed-price').change(function() {
            shoppingCart.calculateShippingPrice();
        });

        $('select.item-shipping-methods').each(function() {
            if ($(this).val() != '') {
                shoppingCart.handleShippingSettings($(this).val(), $(this).attr('data-item'));  
            }
        });
        $('select.item-shipping-methods').change(function() {
            $('select.service-' + $(this).val()).empty();
            $('input.service-single-' + $(this).val()).val('');
            shoppingCart.handleShippingSettings($(this).val(), $(this).attr('data-item'));
            shoppingCart.calculateShippingPrice();
            handleUSPSOrigin($('select[name="f[location_level1]"] option:selected').val());
        });
        $('select[name="f[location_level1]"]').change(function() {
            handleUSPSOrigin($(this).val());
        });

        if (shcCountry) {
            handleUSPSOrigin(shcCountry);
        }

        // get quote
        $('.calculate-rate').click(function() {
            let shcErrors = [];
            let errorFields = [];
            var checkFields = ['location_level1', 'location_level2', 'location_level3', 'zip', 'address'];

            for(var i = 0; i < checkFields.length; i++) {
                var pattern = new RegExp(/location_level/, 'gi');
                if (checkFields[i].match(pattern)) {
                    var fEl = $('select[name="f['+checkFields[i]+']"]');
                    if (!fEl.length) {
                        var fEl = $('input[name="f['+checkFields[i]+']"]');
                    }
                } else {
                    var fEl = $('input[name="f['+checkFields[i]+']"]');
                }
                if (!fEl.val()) {
                    errorFields.push('f['+checkFields[i]+']');
                    shcErrors.push(lang['notice_field_empty'].replace('{field}', fEl.parent().prev('div.name').html()));
                }
            }

            if (shcErrors.length > 0) {
                printMessage('error', shcErrors, errorFields) 
                return;
            }

            let elBtn = $(this);
            var currentMethod = elBtn.attr('method');
            var itemID = elBtn.attr('item');

            var tmpName = elBtn.html();
            elBtn.text(lang['loading']);
            $('select.service-' + currentMethod).closest('div.submit-cell').addClass('hide');

            var data = {
                mode: 'shoppingCartGetQuote',
                item: itemID,
                method: currentMethod,
                form: $.param($('#shipping-form').serializeArray()) 
            };
            flUtil.ajax(data, function(response) {
                if (response.status == 'OK') {
                    $('select.service-' + currentMethod).empty();
                    $('input.service-single-' + currentMethod).val('');
                    if (response.multi) {
                        for (var i in response.quote) {
                            var selected = '';
                            var _i = response.quote[i];
                            if (i == 0) {
                                selected = 'selected="selected"';
                            }
                            $('select.service-' + currentMethod).append('<option value="'+ _i.service +'" data-fixed-price="'+ _i.total +'" '+selected+'>'+ _i.service +' - '+ _i.total +'</option>');
                        }
                        $('select.service-' + currentMethod).closest('div.submit-cell').removeClass('hide');
                    } else {
                        $('input.service-single-' + currentMethod).val(response.quote.total);
                    }
                    elBtn.text(tmpName);
                    shoppingCart.calculateShippingPrice();
                } else {
                    printMessage('error', response.quote.error);
                }
                elBtn.text(tmpName);
            });
        });

        $('.shipping-method-fixed').click(function() {
            shoppingCart.calculateShippingPrice();

            var itemID = $(this).data('item');
            if ($(this).is(':checked') && $(this).val() == 'courier') {
                $('.shipping-fixed-price-' + itemID).removeClass('hide');
            } else {
                $('.shipping-fixed-price-' + itemID).addClass('hide');
            }
        });

        $('.shipping-method-fixed').each(function() {
            var itemID = $(this).data('item');
            if ($(this).is(':checked')) {
                if ($(this).val() == 'courier') {
                    $('.shipping-fixed-price-' + itemID).removeClass('hide');
                } else {
                    $('.shipping-fixed-price-' + itemID).addClass('hide');
                }
            }
        });
    });
    
    var handleUSPSOrigin = function(country) {
        var pattern = new RegExp('/united_states/', 'gm');
        $('.shc-usps-domestic-services').each(function() {
            if (country.match(pattern) || country == 'US') {
                $(this).removeClass('hide');
            } else {
                $(this).addClass('hide');
            }
        });
        $('.shc-usps-international-services').each(function() {
            if (country.match(pattern) || country == 'US') {
                $(this).addClass('hide');
            } else {
                $(this).removeClass('hide');
            }
        });
    }
    {/literal}
</script>
