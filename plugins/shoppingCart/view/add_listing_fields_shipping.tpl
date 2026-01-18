<!-- shipping methods -->

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='shipping_details' name=$lang.shc_shipping_details}

<div id="package_details">
    <div class="submit-cell clearfix">
        <div class="name">
            {$lang.shc_handling_time}
        </div>
        <div class="field single-field" id="sf_field_shc_handling_time">
            <select name="fshc[shc_handling_time]">
                <option value="">{$lang.select}</option>
                {foreach from=$shc_handling_time item='htime' key='key'}
                    <option value="{$key}" {if $key == $smarty.post.fshc.shc_handling_time}selected="selected"{/if}>{$htime}</option>
                {/foreach}
            </select>
        </div>
    </div>
    <div class="submit-cell fixed">
        <div class="name">
            {$lang.shc_package_type}
        </div>
        <div class="field single-field" id="sf_field_shc_package_type">
            <select name="fshc[shc_package_type]">
                <option value="">{$lang.select}</option>
                {foreach from=$shc_package_type item='ptype' key='key'}
                    <option value="{$key}" {if $key == $smarty.post.fshc.shc_package_type}selected="selected"{/if}>{$ptype}</option>
                {/foreach}
            </select>
        </div>
    </div>
    <div class="submit-cell fixed">
        <div class="name">
            {$lang.shc_weight}
        </div>
        <div class="field combo-field" id="sf_field_shc_bid_weight">
            <input class="numeric wauto" size="8" type="text" name="fshc[shc_weight]" maxlength="11" value="{if $smarty.post.fshc.shc_weight}{$smarty.post.fshc.shc_weight}{else}0{/if}" />
            {assign var='shc_weight_unit' value='shc_weight_'|cat:$config.shc_weight_unit}
            {$lang[$shc_weight_unit]}
        </div>
    </div>
    <div class="submit-cell">
        <div class="name">
            {$lang.shc_dimensions}
        </div>
        <div class="field combo-field" id="sf_field_shc_dimensions">
            <input type="text" class="numeric wauto mr-0" name="fshc[shc_dimensions][length]" value="{$smarty.post.fshc.shc_dimensions.length}" size="7" />
            <span class="dimension-divider">x</span>
            <input type="text" class="numeric wauto mr-0" name="fshc[shc_dimensions][width]" value="{$smarty.post.fshc.shc_dimensions.width}"  size="7" />
            <span class="dimension-divider">x</span>
            <input type="text" class="numeric wauto mr-0" name="fshc[shc_dimensions][height]" value="{$smarty.post.fshc.shc_dimensions.height}" size="7" />
        </div>
    </div>
    {if $config.shc_shipping_step}
        <div class="submit-cell fixed">
            <div class="name">
                {$lang.shc_shipping_price_type}
            </div>
            <div class="field inline-fields">
                <span class="custom-input"><label><input {if $smarty.post.fshc.shc_shipping_price_type == 'free' || !$smarty.post.fshc.shc_shipping_price_type}checked="checked"{/if} class="shc-price-type" type="radio" name="fshc[shc_shipping_price_type]" value="free" />{$lang.shc_shipping_price_type_free}</label></span>
                <span class="custom-input"><label><input {if $smarty.post.fshc.shc_shipping_price_type == 'fixed'}checked="checked"{/if} class="shc-price-type" type="radio" name="fshc[shc_shipping_price_type]" value="fixed" />{if $config.shc_shipping_price_fixed == 'multi'}{$lang.shc_fixed_price_multi}{else}{$lang.shc_shipping_price_type_fixed}{/if}</label></span>
                {if $is_calculated}
                    <span class="custom-input">
                        <label>
                            <input {if $smarty.post.fshc.shc_shipping_price_type == 'calculate'}checked="checked"{/if} class="shc-price-type" type="radio" name="fshc[shc_shipping_price_type]" value="calculate" />
                            {$lang.shc_shipping_price_type_calculate}
                        </label>
                    </span>
                {/if}
            </div>
        </div>
    {else}
        <input type="hidden" name="fshc[shc_shipping_price_type]" value="free" />
    {/if}
    <div class="submit-cell fixed {if $smarty.post.fshc.shc_shipping_price_type != 'fixed'} hide{/if}">
        <div class="name">
            {$lang.shc_shipping_price}
        </div>
        <div class="field combo-field" id="sf_field_shc_shipping_price">
            {if $config.shc_shipping_price_fixed == 'multi'}
                <div class="fixed-prices">
                    {if $smarty.post.fshc.shc_shipping_fixed_prices}
                        {foreach from=$smarty.post.fshc.shc_shipping_fixed_prices item='price_item' key='key'}
                            <div class="price-item mb-2" data-index="{$key}">
                                <input class="numeric wauto price mr-1" size="8" type="text" name="fshc[shc_shipping_fixed_prices][{$key}][price]" maxlength="11" value="{$price_item.price}" />
                                <span class="shc-currency mr-2">{$defaultCurrencyName}</span>
                                <input class="wauto" type="text" name="fshc[shc_shipping_fixed_prices][{$key}][name]" value="{$price_item.name}" />
                                <a class="icon delete delete-price-item" data-index="{$key}" href="javascript://"></a>
                            </div>
                        {/foreach}
                    {/if}
                </div>
                <div><a href="javascript://" class="add-price-item pt-2 pb-3 d-inline-block">{$lang.shc_add_price_item}</a></div>
            {else}
                <input class="numeric wauto" size="8" type="text" name="fshc[shc_shipping_price]" maxlength="11" value="{if $smarty.post.fshc.shc_shipping_price}{$smarty.post.fshc.shc_shipping_price}{else}0{/if}" />
                <span class="shc-currency">{$currency.0.name}</span>
            {/if}
        </div>
    </div>
    <div class="submit-cell fixed {if $smarty.post.fshc.shc_shipping_price_type != 'fixed'} hide{/if}">
        <div class="name">
            {$lang.shc_shipping_discount}
            <img class="qtip" alt="" title="{$lang.shc_shipping_discount_notice}" id="fd_shc_shipping_discount" src="{$rlTplBase}img/blank.gif" />
        </div>
        <div class="field combo-field" id="sf_field_shc_shipping_discount">
            <input class="numeric wauto" size="5" type="text" name="fshc[shc_shipping_discount]" maxlength="3" value="{if $smarty.post.fshc.shc_shipping_discount}{$smarty.post.fshc.shc_shipping_discount}{else}0{/if}" />
            <span>%,&nbsp;</span>
            <span class="dimension-divider">{$lang.shc_shipping_discount_at}</span>
            <input class="numeric wauto" size="5" type="text" name="fshc[shc_shipping_discount_at]" maxlength="11" value="{if $smarty.post.fshc.shc_shipping_discount_at}{$smarty.post.fshc.shc_shipping_discount_at}{else}0{/if}" />
        </div>
    </div>
    <div class="submit-cell fixed {if $smarty.post.fshc.shc_shipping_price_type != 'fixed' && $smarty.post.fshc.shc_shipping_price_type != 'free' && !empty($smarty.post.fshc.shc_shipping_price_type)} hide{/if}">
        <div class="name">
            {$lang.shc_shipping_method}
            {if $config.shc_shipping_step}
                <span class="red">&nbsp;*</span>
            {/if}
        </div>
        <div class="field checkbox-field" id="sf_field_shc_shipping_method">
            {if $config.shc_shipping_step}
                <span class="custom-input mr-3">
                    <label>
                        <input type="checkbox" {if 'courier'|in_array:$smarty.post.fshc.shipping_method_fixed}checked="checked"{/if} name="fshc[shipping_method_fixed][]" value="courier" />{$lang.shc_courier}
                    </label>
                </span>
                <span class="custom-input">
                    <label>
                        <input type="checkbox" {if 'pickup'|in_array:$smarty.post.fshc.shipping_method_fixed}checked="checked"{/if} name="fshc[shipping_method_fixed][]" value="pickup" />{$lang.shc_pickup}
                    </label>
                </span>
            {else}
                {$lang.shc_pickup}
                <input type="hidden" name="fshc[shipping_method_fixed][]" value="pickup" />
            {/if}
        </div>
    </div>

    <div class="submit-cell hide" id="shipping-methods">
        <div class="name">
            {$lang.shc_shipping_price_type_calculate}
        </div>
        <div class="field">
            {foreach from=$shipping_methods item='method'}
                <div class="submit-cell clearfix">
                    <div class="name">
                        <label><input type="checkbox" {if $smarty.post.shipping[$method.Key].enable}checked="checked"{/if} name="shipping[{$method.Key}][enable]" value="1" class="enable-shipping-method" />{$method.name}</label>
                    </div>
                    <div class="field single-field hide">
                        <a href="javascript:;" class="button shipping-method-settings" data-settings="{$method.Key}" data-method-name="{$method.name}">{$lang.manage}</a>
                    </div>
                </div>
                <div id="shipping-method-settings-{$method.Key}" class="hide">
                    <div class="tmp-dom tmp-dom-{$method.Key}">
                        <div class="submit-cell clearfix">
                            <div class="name">{$lang.shc_fixed_shipping_price}</div>
                            <div class="field single-field">
                                <input type="text" class="wauto numeric shipping-fixed-price" size="8" name="shipping[{$method.Key}][price]" value="{if $smarty.post.shipping[$method.Key].price}{$smarty.post.shipping[$method.Key].price}{else}0{/if}" id="shipping-fixed-price-{$method.Key}" />
                                <span class="shc-currency">{$currency.0.name}</span>
                                <span style="display: block;">&nbsp;&nbsp;-{$lang.or}-&nbsp;&nbsp;</span>
                                <label>
                                    <input type="checkbox" class="shipping-auto-price" {if $smarty.post.shipping[$method.Key].auto || !$smarty.post.shipping[$method.Key].price}checked="checked"{/if} name="shipping[{$method.Key}][auto]" value="1" />{$lang.shc_auto_shipping_calculate}
                                </label>
                            </div>
                        </div>
                        {include file=$shipping_methods_path|cat:$method.Key|cat:'/view/add_listing.tpl'}

                        <div class="submit-cell buttons">
                            <div class="name"></div>
                            <div class="field">
                                <input type="button" class="button-apply" value="{$lang.apply}" />&nbsp;&nbsp;
                                <input type="button" class="button-cancel" value="{$lang.cancel}" />
                            </div>
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>
    </div>
</div>

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

<script class="fl-js-dynamic">
var shcPriceField = '{$config.price_tag_field}';
var price_item_index = 0;
var shippingElemets = [];
var shcPlaveHolderLcation = '{$lang.shc_location}';
{literal}
$(document).ready(function() {
    $('a.shipping-method-settings').click(function() {
        var name_method = '{/literal}{$lang.shc_configure_shipping_method}{literal}';
        name_method = name_method.replace('{method}', $(this).attr('data-method-name'));
        var method = $(this).attr('data-settings');
        var el = '#shipping-method-settings-' + method;

        flUtil.loadScript([
            rlConfig['tpl_base'] + 'components/popup/_popup.js',
        ], function(){
            $('#shipping-methods').popup({
                click: false,
                scroll: true,
                closeOnOutsideClick: false,
                content: $(el).html(),
                caption: name_method,
                onShow: function(content){
                    $(el).html('');
                    var self = this;

                    synchronizeFormValues(content);

                    content.find('.button-apply').click(function() {
                        shippingElemets = [];
                        $('.popup div.body input[type=checkbox], .popup div.body input[type=text], .popup div.body select option:selected').each(function() {
                            if ($(this).prop('tagName') == 'INPUT') {
                                if (['text'].indexOf($(this).prop('type')) >= 0) {
                                    shippingElemets.push({id: $(this).attr('id'), 'val': $(this).val()});
                                } else {
                                    if ($(this).is(':checked')) {
                                        shippingElemets.push({id: $(this).attr('id'), 'val': $(this).val()});
                                    }
                                }
                            } else if ($(this).prop('tagName') == 'OPTION') {
                                shippingElemets.push({id: $(this).parent().attr('id'), 'val': $(this).val()});
                            }
                        });
                        $(el).html($('.popup').find('div.body').html());

                        synchronizeFormValues($('#shipping-methods'));
                        self.close();
                    });

                    content.find('.button-cancel').click(function() {
                        $(el).html($('.popup').find('div.body').html());
                        self.close();
                    });
                }
            });
        });
    });

    $('.enable-shipping-method').each(function() {
        if ($(this).is(':checked')) {
            $(this).parent().next().removeClass('hide');
        }
    });

    $('.enable-shipping-method').click(function() {
        if ($(this).is(':checked')) {
            $(this).parent().next().removeClass('hide');
        } else {
            $(this).parent().next().addClass('hide');
        }
    });
    
    $(document).on('keyup', 'input.shipping-fixed-price', function() {
        if ($(this).val() != '') {
            $(this).parent().find('input.shipping-auto-price').prop('checked', false);
        } else {
            $(this).parent().find('input.shipping-auto-price').prop('checked', 'checked');
        }
    });
    
    $(document).on('change', 'input.shipping-auto-price', function() {
        if ($(this).is(':checked')) {
            $(this).parent().find('input[type="text"]').val('');
        }
    });

    shoppingCart.handleShippingPriceType('{/literal}{$smarty.post.fshc.shc_shipping_price_type}{literal}');

    $('.add-price-item').click(function() {
        if ($('.fixed-prices .price-item:last').length) {
            price_item_index = parseInt($('.fixed-prices .price-item:last').attr('data-index'));
            price_item_index++;
        } else {
            price_item_index = 0;
        }

        if ($('select[name="f[' + shcPriceField + '][currency]"]').length > 0) {
            var currencyCode = $('select[name="f[' + shcPriceField + '][currency]"] option:selected').text();
        } else {
            var currencyCode = $('input[name="f[' + shcPriceField + '][currency]"]').next().text();
        }

        var price_item_html = $('<div>', {
                class: 'price-item mb-2', 
                'data-index': price_item_index}
            )
            .append($('<input>', {
                class: 'numeric wauto price mr-1', 
                type: 'text', 
                name: 'fshc[shc_shipping_fixed_prices]['+price_item_index+'][price]', 
                maxlength: 11}).attr('size', 8).attr('placeholder', lang.price)
            )
            .append($('<span>', {
                class: 'shc-currency mr-2', 
                'data-index': price_item_index, 
                text: currencyCode})
            )
            .append($('<input>', {
                class: 'wauto mr-2', 
                type: 'text', 
                name: 'fshc[shc_shipping_fixed_prices]['+price_item_index+'][name]'}).attr('placeholder', shcPlaveHolderLcation)
            )
            .append($('<a>', {
                class: 'icon delete delete-price-item', 
                'data-index': price_item_index, 
                href: 'javascript://'})
            );

        $('.fixed-prices').append(price_item_html);
    });

    $(document).on('click', '.delete-price-item', function() {
        var index = parseInt($(this).attr('data-index'));

        $('.fixed-prices .price-item').each(function() {
            index_item = parseInt($(this).attr('data-index'));

            if (index_item == index) {
                $(this).remove();
            }
        });
    });

    if ($('input[name="fshc[shc_shipping_price_type]"]:checked').val() == 'calculate') {
        $('#shipping-methods').removeClass('hide');
    }

    $(document).on('change', 'select#shc_ups_origin', function() {
        handleUPSShippingServices($(this).val());
    });
});

var synchronizeFormValues = function(content) {
    for (var i in shippingElemets) {
        var $element = content.find('#' + shippingElemets[i].id);

        if ($element.prop('tagName') == 'INPUT') {
            if (['text'].indexOf($element.prop('type')) >= 0) {
                content.find('#' + shippingElemets[i].id).val(shippingElemets[i].val);
            } else {
                content.find('#' + shippingElemets[i].id).prop('checked', true);
            }
        } else if ($element.prop('tagName') == 'SELECT') {
            content.find('#' + shippingElemets[i].id).val(shippingElemets[i].val).trigger('change');
        }
    }
}

var handleUPSShippingServices = function(origin) {
    $('input.ups-origin').each(function() {
        var origins = $(this).attr('accesskey').split(',');
        if (origins.indexOf(origin) >= 0) {
            $(this).parent('span').removeClass('hide');
        } else {
            $(this).prop('checked', false);
            $(this).parent('span').addClass('hide');
        }
    });
}
{/literal}
</script>

<!-- end shipping methods -->
