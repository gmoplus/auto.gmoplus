<!-- shoppingCart plugin -->

<div id="shc-group" class="submit-cell hide w-100">
    {assign var='currency' value='currency'|df}

    <div class="submit-cell">
        <div class="name">
            {$lang.shc_price_format}
            <span class="red">&nbsp;*</span>
        </div>
        <div class="field inline-fields">
            {foreach from=$shcTabs item='shcTab' name='FShcTabs'}
                {if $shcTab.module}
                    {if (!$smarty.post.fshc.shc_mode && $smarty.foreach.FShcTabs.first) || $smarty.post.fshc.shc_mode == $shcTab.module}
                        {assign var='shc_mode_active' value=$shcTab.module}
                    {/if}
                    <span class="custom-input mb-2">
                        <label>
                            <input {if $isAuctionActive}disabled="disabled"{/if} {if $shc_mode_active == $shcTab.module}checked="checked"{/if} class="shc-mode" type="radio" name="fshc[shc_mode]" value="{$shcTab.module}" />{$shcTab.name}
                        </label>
                    </span>
                {/if}
            {/foreach}
        </div>
    </div>

    <div id="shc_fields_area">
        {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart'|cat:$smarty.const.RL_DS|cat:'view'|cat:$smarty.const.RL_DS|cat:'add_listing_fields.tpl'}
    </div>

    {if $config.shc_shipping_fields}
        {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart'|cat:$smarty.const.RL_DS|cat:'view'|cat:$smarty.const.RL_DS|cat:'add_listing_fields_shipping.tpl'} 
    {/if}
</div>

<script class="fl-js-dynamic">
var shc_check_settings = {if $config.shc_method == 'multi'}false{else}true{/if};

var listing_field_price = '{$config.price_tag_field}';
var shc_mode = '{if $smarty.post.fshc.shc_mode}{$smarty.post.fshc.shc_mode}{else}{$shc_mode_active}{/if}';
var isLogin = {if $isLogin}true{else}false{/if};
var isCommission = {if $config.shc_commission_enable && $config.shc_method == 'multi'}true{else}false{/if};
lang['login'] = '{$lang.login}';
lang['sign_in'] = '{$lang.sign_in}';
lang['shc_buy_now'] = "{$lang.shc_buy_now}";

{literal}

$(document).ready(function() {
    if (listing_field_price == '') {
        printMessage('warning', '{/literal}{$lang.shc_price_field_not_selected}{literal}');
    }
    else {
        shoppingCart.replaceFieldPrice(listing_field_price);
        
        $('#shc-group').show().trigger('resize');

        $('input[name="fshc[shc_mode]"]').click(function() {
            shoppingCart.priceFormatTabs($(this).val());
        });

        $('input.quantity-unlimited').change(function(){
            if ($(this).is(':checked')) {
                $('input[name="fshc[shc_quantity]"]').attr('disabled', true).addClass('disabled').val('');
            } else {
                $('input[name="fshc[shc_quantity]"]').attr('disabled', false).removeClass('disabled');
            }
        });

        if (shc_mode) {
            shoppingCart.priceFormatTabs(shc_mode, true);
        }

        // calculate commission
        if (isCommission) {
            if ($('input.price-full').val() != '') {
                if ($('input[name="fshc[shc_mode]"]:checked').val() != 'listing') {
                    shoppingCart.calculateCommission($('input.price-full').val());
                }
            }
            if ($('input.price-start').val() != '') {
                shoppingCart.calculateCommission($('input.price-start').val(), 'start');
            }
            $('input.price-full').keyup(function() {
                if ($('input[name="fshc[shc_mode]"]:checked').val() != 'listing') {
                    if ($(this).val() != '') {
                        $('.price-item').text($(this).val());
                        shoppingCart.calculateCommission($(this).val());
                    }
                }
            });
            $('input.price-full').blur(function() {
                if ($('input[name="fshc[shc_mode]"]:checked').val() != 'listing') {
                    if ($(this).val() != '') {
                        $('.price-item').text($(this).val());
                        shoppingCart.calculateCommission($(this).val());
                    }
                }
            });
            $('input.price-start').keyup(function() {
                if ($(this).val() != '') {
                    shoppingCart.calculateCommission($(this).val(), 'start');
                }
            });
            $('input.price-start').blur(function() {
                if ($(this).val() != '') {
                    shoppingCart.calculateCommission($(this).val(), 'start');
                }
            });
        }

        $('input[type="text"].qtip').each(function() {
            $(this).attr('title', '{/literal}{$lang.shc_cannot_edit_auction}{literal}');
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
