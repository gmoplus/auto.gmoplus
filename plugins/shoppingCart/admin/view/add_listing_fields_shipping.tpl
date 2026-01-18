<table class="form" id="fs_shipping_details">
	<tr class="auction fixed">
		<td class="name">
			{$lang.shc_shipping_details}
		</td>
		<td class="field">
			<fieldset class="light">
				<legend id="legend_shipping_details" class="up" onclick="fieldset_action('shipping_details');">{$lang.shc_shipping_details}</legend>
				<div id="shipping_details">
					<table class="form">
						<tr>
							<td class="name">
								{$lang.shc_handling_time}
								<img class="qtip" alt="" title="{$lang.shc_handling_time_des}" id="fd_shc_dimensions" src="{$rlTplBase}img/blank.gif" />
							</td>
							<td class="field" id="sf_field_shc_handling_time">
								<select name="fshc[shc_handling_time]">
									<option value="-1">{$lang.select}</option>
									{foreach from=$shc_handling_time item='htime' key='key'}
										<option value="{$key}" {if $smarty.post.fshc.shc_handling_time == $key}selected="selected"{/if}>{$htime}</option>
									{/foreach}
								</select>
							</td>
						</tr>
						<tr>
							<td class="name">
								{$lang.shc_package_type}
								<img class="qtip" alt="" title="{$lang.shc_package_type_des}" id="fd_shc_package_type" src="{$rlTplBase}img/blank.gif" />
							</td>
							<td class="field" id="sf_field_shc_package_type">
								<select name="fshc[shc_package_type]">
									<option value="-1">{$lang.select}</option>
									{foreach from=$shc_package_type item='ptype' key='key'}
										<option value="{$key}" {if $smarty.post.fshc.shc_package_type == $key}selected="selected"{/if}>{$ptype}</option>
									{/foreach}
								</select>
							</td>
						</tr>
						<tr>
							<td class="name">
								{$lang.shc_weight}
							</td>
							<td class="field" id="sf_field_shc_bid_weight">
								<input class="numeric wauto" size="8" type="text" name="fshc[shc_weight]" maxlength="11" value="{if $smarty.post.fshc.shc_weight}{$smarty.post.fshc.shc_weight}{else}0{/if}" />
								{$config.shc_weight_type|lower}
							</td>
						</tr>
						<tr>
							<td class="name">
								{$lang.shc_dimensions}
								<img class="qtip" alt="" title="{$lang.shc_dimensions_des}" id="fd_shc_dimensions" src="{$rlTplBase}img/blank.gif" />
							</td>
							<td class="field" id="sf_field_shc_dimensions">
                                <input type="text" class="numeric wauto" name="fshc[shc_dimensions][length]" value="{if $smarty.post.fshc.shc_dimensions}{$smarty.post.fshc.shc_dimensions.length}{/if}" size="8" />{$lang.in}.&nbsp;
								X&nbsp;<input type="text" class="numeric wauto" name="fshc[shc_dimensions][width]" value="{if $smarty.post.fshc.shc_dimensions}{$smarty.post.fshc.shc_dimensions.width}{/if}"  size="8" />{$lang.in}.&nbsp;
                                X&nbsp;<input type="text" class="numeric wauto" name="fshc[shc_dimensions][height]" value="{if $smarty.post.fshc.shc_dimensions}{$smarty.post.fshc.shc_dimensions.height}{/if}" size="8" />{$lang.in}.&nbsp;
							</td>
						</tr>
						<tr>
							<td class="name">
								{$lang.shc_shipping_price_type}
							</td>
							<td class="field" id="sf_field_shc_dimensions">
								<span class="custom-input"><label><input {if $smarty.post.fshc.shc_shipping_price_type == 'free' || !$smarty.post.fshc.shc_shipping_price_type}checked="checked"{/if} class="shc-price-type" type="radio" name="fshc[shc_shipping_price_type]" value="free" />{$lang.shc_shipping_price_type_free}</label></span>
								<span class="custom-input"><label><input {if $smarty.post.fshc.shc_shipping_price_type == 'fixed'}checked="checked"{/if} class="shc-price-type" type="radio" name="fshc[shc_shipping_price_type]" value="fixed" />{$lang.shc_shipping_price_type_fixed}</label></span>
                                {if $is_calculated}
								    <span class="custom-input">
                                        <label>
                                            <input {if $smarty.post.fshc.shc_shipping_price_type == 'calculate'}checked="checked"{/if} class="shc-price-type" type="radio" name="fshc[shc_shipping_price_type]" value="calculate" />
                                            {$lang.shc_shipping_price_type_calculate}
                                        </label>
                                    </span>
                                {/if}
							</td>
						</tr>
						<tr class="{if $smarty.post.fshc.shc_shipping_price_type != 'fixed'}hide{/if}">
							<td class="name">
								{$lang.shc_shipping_price}
							</td>
							<td class="field" id="sf_field_shc_shipping_price">
                                {if $config.shc_shipping_price_fixed == 'multi'}
                                    <div class="fixed-prices">
                                        {if $smarty.post.fshc.shc_shipping_fixed_prices}
                                            {foreach from=$smarty.post.fshc.shc_shipping_fixed_prices item='price_item' key='key'}
                                                <div class="price-item" data-index="{$key}">
                                                    <span class="shc-currency">{$currency.0.name}</span>
                                                    <input class="numeric wauto price" size="8" type="text" name="fshc[shc_shipping_fixed_prices][{$key}][price]" maxlength="11" value="{$price_item.price}" />
                                                    <input class="wauto" type="text" name="fshc[shc_shipping_fixed_prices][{$key}][name]" value="{$price_item.name}" />
                                                    <a title="{$lang.delete}" class="delete-price-item" data-index="{$key}" href="javascript:;">
                                                        <img src="{$rlTplBase}img/blank.gif" class="remove" />
                                                    </a>
                                                </div>
                                            {/foreach}
                                        {/if}
                                    </div>
                                    <div><a href="javascript://" class="add-price-item">{$lang.shc_add_price_item}</a></div>
                                {else}
                                    <input class="numeric wauto" size="8" type="text" name="fshc[shc_shipping_price]" maxlength="11" value="{if $smarty.post.fshc.shc_shipping_price}{$smarty.post.fshc.shc_shipping_price}{else}0{/if}" />
                                    <span class="shc-currency">{$currency.0.name}</span>
                                {/if}
							</td>
						</tr>
                        <tr class="{if $smarty.post.fshc.shc_shipping_price_type != 'fixed'} hide{/if}">
                            <td class="name">
                                {$lang.shc_shipping_discount}
                            </td>
                            <td class="field" id="sf_field_shc_shipping_discount">
                                <input class="numeric wauto" size="8" type="text" name="fshc[shc_shipping_discount]" maxlength="11" value="{if $smarty.post.fshc.shc_shipping_discount}{$smarty.post.fshc.shc_shipping_discount}{else}0{/if}" />
                                <span>%,&nbsp;</span>
                                <span class="dimension-divider">{$lang.shc_shipping_discount_at}</span>
                                <input class="numeric wauto" size="8" type="text" name="fshc[shc_shipping_discount_at]" maxlength="11" value="{if $smarty.post.fshc.shc_shipping_discount_at}{$smarty.post.fshc.shc_shipping_discount_at}{else}0{/if}" />
                            </td>
                        </tr>
                        <tr class="auction fixed {if $smarty.post.fshc.shc_shipping_price_type != 'fixed' && $smarty.post.fshc.shc_shipping_price_type != 'free' && !empty($smarty.post.fshc.shc_shipping_price_type)} hide{/if}">
                            <td class="name">
                                <span class="red">&nbsp;*</span>&nbsp;
                                {$lang.shc_shipping_method}
                            </td>
                            <td class="field" id="sf_field_shc_shipping_method">
                                <span class="custom-input"><label><input {if 'courier'|in_array:$smarty.post.fshc.shipping_method_fixed}checked="checked"{/if} type="checkbox" name="fshc[shipping_method_fixed][]" value="courier" />{$lang.shc_courier}</label></span>
                                <span class="custom-input"><label><input {if 'pickup'|in_array:$smarty.post.fshc.shipping_method_fixed}checked="checked"{/if} type="checkbox" name="fshc[shipping_method_fixed][]" value="pickup" />{$lang.shc_pickup}</label></span>
                            </td>
                        </tr>
						<tr id="shipping-methods" class="hide">
							<td class="name">
								{$lang.shc_shipping_methods}
							</td>
							<td class="field">
								<table class="form">
								{foreach from=$shipping_methods item=shipping_method}
									<tr>
										<td class="divider first">
											<div class="inner" style="padding: 5px 8px 5px 8px;">
												<input type="checkbox" {if $smarty.post.shipping[$shipping_method.Key].enable}checked="checked"{/if} name="shipping[{$shipping_method.Key}][enable]" value="1" id="shmid_{$shipping_method.Key}" class="enable-shipping-method" /> <label for="shmid_{$shipping_method.Key}">{$shipping_method.name}</label>
											</div>
										</td>
									</tr>
									<tr>
										<td colspan="2">
											<div id="shipping-method-settings-{$shipping_method.Key}" class="hide" style="padding-bottom: 15px;">
												<div class="submit-cell clearfix">
													<div class="name">{$lang.shc_fixed_shipping_price}</div>
													<div class="field single-field">
														<input type="text" class="wauto numeric shipping-fixed-price" size="8" name="shipping[{$shipping_method.Key}][price]" value="{if $smarty.post.shipping[$shipping_method.Key].price}{$smarty.post.shipping[$shipping_method.Key].price}{elseif $shipping_method.Type == 'offline'}0{/if}" />
														{if $shipping_method.Type == 'online'}
															&nbsp;&nbsp;-{$lang.or}-&nbsp;&nbsp;<label><input type="radio" class="shipping-auto-price" {if $smarty.post.shipping[$shipping_method.Key].auto || !$smarty.post.shipping[$shipping_method.Key].price}checked="checked"{/if} name="shipping[{$shipping_method.Key}][auto]" value="1" />{$lang.shc_auto_shipping_calculate}</label>
														{/if}
													</div>
												</div>
												<div class="clearfix">
													{include file=$shipping_methods_path|cat:$shipping_method.Key|cat:'/view/ap_add_listing.tpl'}
												</div>
											</div>
										</td>
									</tr>
								{/foreach}
								</table>
							</td>
						</tr>
					</table>
				</div>
			</fieldset>
		</td>
	</tr>
</table>
<script type="text/javascript">
var price_item_index = 0;
var shippingElemets = [];
var price_item_tpl = '<div class="price-item" data-index="[index]"><span class="shc-currency">{$currency.0.name}</span>&nbsp;<input class="numeric wauto price" size="8" type="text" name="fshc[shc_shipping_fixed_prices][[index]][price]" maxlength="11" value="" />&nbsp;<input class="wauto" type="text" name="fshc[shc_shipping_fixed_prices][[index]][name]" value="" /><a class="delete-price-item" data-index="[index]" title="{$lang.delete}" href="javascript:;"><img src="{$rlTplBase}img/blank.gif" class="remove" /></a></div>';
{literal}
$(document).ready(function() {
    $('.enable-shipping-method').each(function() {
        if ($(this).is(':checked')) {
            $(this).parent().next().show();
        }
    });

    $('.enable-shipping-method').click(function() {
        if ($(this).is(':checked')) {
            $(this).parent().next().show();
        } else {
            $(this).parent().next().hide();
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

        var price_item_html = price_item_tpl.replace(/\[index\]/g, price_item_index);
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
});
{/literal}
</script>
