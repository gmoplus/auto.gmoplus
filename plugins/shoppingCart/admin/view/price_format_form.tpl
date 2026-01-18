<!-- shoppingCart plugin -->
<tr>
    <td colspan="2">
        <div id="shc-group" class="hide">
            {assign var='currency' value='currency'|df}

            <table class="form">
                <tr>
                    <td class="name"><span class="red">*</span> {$lang.shc_price_format}</td>
                    <td class="field" id="sf_field_shc_dimensions">
                        {foreach from=$shcTabs item='shcTab' name='FShcTabs'}
                            {if $shcTab.module}
                                {if $smarty.foreach.FShcTabs.first}
                                    {assign var='shc_mode_active' value=$shcTab.module}
                                {/if}
                                {if $smarty.post.fshc.shc_mode == $shcTab.module}
                                    {assign var='shc_mode_active' value=$shcTab.module}
                                {/if}
                                <span class="custom-input">
                                    <label>
                                        <input type="radio" {if $shc_mode_active == $shcTab.module}checked="checked"{/if} class="numeric wauto" name="fshc[shc_mode]" value="{$shcTab.module}" />{$shcTab.name}
                                    </label>
                                </span>
                            {/if}
                        {/foreach}
                    </td>
                </tr>
            </table>
            <div id="shc_fields_area">
                {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart'|cat:$smarty.const.RL_DS|cat:'admin'|cat:$smarty.const.RL_DS|cat:'view'|cat:$smarty.const.RL_DS|cat:'add_listing_fields.tpl'}

                {if !$config.shc_hide_shipping_fields}
                    {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart'|cat:$smarty.const.RL_DS|cat:'admin'|cat:$smarty.const.RL_DS|cat:'view'|cat:$smarty.const.RL_DS|cat:'add_listing_fields_shipping.tpl'} 
                {/if}
            </div>
        </div>
    </td>
</tr>

<script type="text/javascript">
var listing_field_price = '{$config.price_tag_field}';
var isCommission = {if $config.shc_commission_enable && $config.shc_method == 'multi'}true{else}false{/if};
var shc_mode = '{if $smarty.post.fshc.shc_mode}{$smarty.post.fshc.shc_mode}{else}{$shc_mode_active}{/if}';
var shc_check_settings = true;

{literal}

$(document).ready(function() {
    if (listing_field_price == '') {
        printMessage('warning', '{/literal}{$lang.shc_price_field_not_selected}{literal}');
    } else {
        if (shc_mode) {
            shoppingCart.priceFormatTabs(shc_mode);
        }

        $('input[name="fshc[shc_mode]"]').click(function() {
            shoppingCart.priceFormatTabs($(this).val());
        });

        var $priceInput = $('input[name="f[' + listing_field_price + '][value]"]');
        var $priceCont = $priceInput.closest('tr');
        $('#shc_fields_area .price_item.listing').append(
            $priceInput.closest('.field').children()
        );
        $priceCont.remove();
    }

    $('.enable-shipping-method').each(function() {
        if ($(this).is(':checked')) {
            $('#shipping-method-settings-' + $(this).attr('id').split('_')[1]).show();
        }
    });

    $('.enable-shipping-method').click(function() {
        if ($(this).is(':checked')) {
            $('#shipping-method-settings-' + $(this).attr('id').split('_')[1]).show();
        } else {
            $('#shipping-method-settings-' + $(this).attr('id').split('_')[1]).hide();
        }
    });

    $('input.shipping-fixed-price').keyup(function() {
        if ($(this).val() != '') {
            $(this).next().find('input[type="radio"]').prop('checked', false);
        } else {
            $(this).next().find('input[type="radio"]').prop('checked', 'checked');
        }
    });
    
    $('input.shipping-auto-price').change(function() {
        if ($(this).is(':checked')) {
            $(this).parent().prev('input[type="text"]').val('');
        }
    });
    
    shoppingCart.handleShippingPriceType('{/literal}{$smarty.post.fshc.shc_shipping_price_type}{literal}');

    // calculate comission
    if (isCommission) {
        if ($('input.price-full').val() != '') {
            shoppingCart.calculateCommission($('input.price-full').val());  
        }

        if ($('input.price-start').val() != '') {
            shoppingCart.calculateCommission($('input.price-start').val(), 'start');    
        }

        $('input.price-full').keyup(function() {
            if ($(this).val() != '') {
                $('.price-item').text($(this).val());
                shoppingCart.calculateCommission($(this).val());
            }
        });

        $('input.price-start').keyup(function() {
            if ($(this).val() != '') {
                shoppingCart.calculateCommission($(this).val(), 'start');
            }
        });
    }

    shoppingCart.controlDigitalProduct(
        $('input[name="fshc[digital]"]:checked').val(), 
        shc_mode, 
        $('input[name="fshc[quantity_unlim]"]:checked').val()
    );

    $('input[name="fshc[digital]"]').click(function() {
        if ($(this).is(':checked') && $(this).val() == '1') {
            shoppingCart.controlDigitalProduct(1, $('input[name="fshc[shc_mode]"]:checked').val(), true);
        } else {
            shoppingCart.controlDigitalProduct(0, $('input[name="fshc[shc_mode]"]:checked').val());
        }
    });

    $('input.quantity-unlimited').click(function() {
        if ($(this).is(':checked')) {
            $('input[name="fshc[shc_quantity]"]').prop('readonly', true);
        } else {
            $('input[name="fshc[shc_quantity]"]').prop('readonly', false);
        }
    });

    $('.delete-file-product').click(function() {
        shoppingCart.deleteFile($(this).data('item'));
    });
});
{/literal}
</script>

<!-- end shoppingCart plugin -->
