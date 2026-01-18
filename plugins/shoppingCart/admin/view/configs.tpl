{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <form id="shc-configs" action="{$rlBaseC}module=configs&form=settings" method="post">
        <div id="shc_settings">
            <table class="form">
                <tr>
                    <td class="divider first" colspan="3"><div class="inner">{phrase key='config+name+general_common'}</div></td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_module}</td>
                    <td class="field">
                        <label><input {if $config.shc_module == '1'}checked="checked"{/if} type="radio" name="config[shc_module]" tab="fixed" value="1" /> {$lang.enabled}</label>
                        <label><input {if $config.shc_module == '0' || !$config.shc_module}checked="checked"{/if} type="radio" name="config[shc_module]" tab="fixed" value="0" /> {$lang.disabled}</label>
                    </td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_module_auction}</td>
                    <td class="field">
                        <label><input {if $config.shc_module_auction == '1'}checked="checked"{/if} type="radio" name="config[shc_module_auction]" tab="auction" value="1" /> {$lang.enabled}</label>
                        <label><input {if $config.shc_module_auction == '0' || !$config.shc_module_auction}checked="checked"{/if} type="radio" name="config[shc_module_auction]" tab="auction" value="0" /> {$lang.disabled}</label>
                    </td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_module_listing}</td>
                    <td class="field">
                        <label><input {if $config.shc_module_listing == '1' || !isset($config.shc_module_listing)}checked="checked"{/if} type="radio" name="config[shc_module_listing]" tab="listing" value="1" /> {$lang.enabled}</label>
                        <label><input {if $config.shc_module_listing == '0'}checked="checked"{/if} type="radio" name="config[shc_module_listing]" tab="listing" value="0" /> {$lang.disabled}</label>
                        <span class="field_description">{$shcLang.shc_module_listing_des}</span>
                    </td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_method}</td>
                    <td class="field">
                        <select name="config[shc_method]">
                            <option value="single" {if $config.shc_method == 'single'}selected="selected"{/if}>{$lang.shc_method_single}</option>
                            <option value="multi" {if $config.shc_method == 'multi'}selected="selected"{/if}>{$lang.shc_method_multi}</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_price_format_tabs}</td>
                    <td class="field">
                        <div id="fields_section">
                            <div id="fields_container" class="ui-sortable">
                                {assign var='shcPriceFormatTabs' value=","|explode:$config.shc_price_format_tabs}
                                {foreach from=$shcPriceFormatTabs item='tab'}
                                    {if $tab == 'auction'}
                                        {assign var='shc_mode_tab' value='shc_'|cat:$tab}
                                    {else}
                                        {assign var='shc_mode_tab' value='shc_mode_'|cat:$tab}
                                    {/if}
                                    <div class="field_obj tab-{$tab}">
                                        <div class="field_title" title="{$lang[$shc_mode_tab]}">
                                            <div class="title">{$lang[$shc_mode_tab]}</div>
                                            <input type="checkbox" name="config[shc_price_format_tabs][]" value="{$tab}" class="hide" checked="checked" />
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_method_currency_convert}</td>
                    <td class="field">
                        <label><input {if $config.shc_method_currency_convert == 'single'}checked="checked"{/if} type="radio" name="config[shc_method_currency_convert]" value="single" /> {$lang.shc_method_currency_convert_single}</label>
                        <label><input {if $config.shc_method_currency_convert == 'multi' || !$config.shc_method_currency_convert}checked="checked"{/if} type="radio" name="config[shc_method_currency_convert]" value="multi" /> {$lang.shc_method_currency_convert_multi}</label>
                    </td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_digital_product}</td>
                    <td class="field">
                        <label><input {if $config.shc_digital_product == '1'}checked="checked"{/if} type="radio" name="config[shc_digital_product]" value="1" /> {$lang.yes}</label>
                        <label><input {if $config.shc_digital_product == '0' || !$config.shc_digital_product}checked="checked"{/if} type="radio" name="config[shc_digital_product]" value="0" /> {$lang.no}</label>
                    </td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_show_unavailable_listings}</td>
                    <td class="field">
                        <label><input {if $config.shc_show_unavailable_listings == '1'}checked="checked"{/if} type="radio" name="config[shc_show_unavailable_listings]" value="1" /> {$lang.yes}</label>
                        <label><input {if $config.shc_show_unavailable_listings == '0' || !$config.shc_show_unavailable_listings}checked="checked"{/if} type="radio" name="config[shc_show_unavailable_listings]" value="0" /> {$lang.no}</label>
                    </td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_count_items_block}</td>
                    <td class="field">
                        <input type="text" name="config[shc_count_items_block]" value="{$config.shc_count_items_block}" />
                    </td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_orders_per_page}</td>
                    <td class="field">
                        <input type="text" name="config[shc_orders_per_page]" value="{$config.shc_orders_per_page}" />
                    </td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_items_cart_duration}</td>
                    <td class="field">
                        <label><input {if $config.shc_items_cart_duration == 'period' || !$config.shc_items_cart_duration}checked="checked"{/if} type="radio" name="config[shc_items_cart_duration]" value="period" /> {$lang.shc_items_cart_duration_period}</label>
                        <label><input {if $config.shc_items_cart_duration == 'unlimited'}checked="checked"{/if} type="radio" name="config[shc_items_cart_duration]" value="unlimited" /> {$lang.shc_items_cart_duration_unlimited}</label>
                    </td>
                </tr>
                <tr class="items-cart-duration-period{if $config.shc_items_cart_duration == 'unlimited'} hide{/if}">
                    <td class="name">{$shcLang.shc_interval_refresh_cart}</td>
                    <td class="field">
                        <input type="text" name="config[shc_interval_refresh_cart]" value="{$config.shc_interval_refresh_cart}" />
                        <span class="field_description">{$shcLang.shc_interval_refresh_cart_des}</span>
                    </td>
                </tr>
            </table>

            <table class="form">
                <tr>
                    <td class="name">{$shcLang.shc_time_format}</td>
                    <td class="field">
                        <input type="text" name="config[shc_time_format]" value="{if $config.shc_time_format}{$config.shc_time_format}{else}%H%I%S{/if}" />
                    </td>
                </tr>
                <tr>
                    <td class="name">{$lang.enable_for}</td>
                    <td class="field">
                        <fieldset class="light">
                            <legend id="legend_accounts_tab_area" class="up" onclick="fieldset_action('accounts_tab_area');">{$lang.account_type}</legend>
                            <div id="accounts_tab_area" style="padding: 0 10px 10px 10px;">
                                <table>
                                <tr>
                                    <td>
                                        <input type="hidden" name="config[shc_account_types]" value="" />
                                        {assign var='shcAccountTypes' value=","|explode:$config.shc_account_types}
                                        <table>
                                        <tr>
                                        {foreach from=$account_types item='a_type' name='ac_type'}
                                            <td>
                                                <div style="padding: 2px 8px 2px 0;">
                                                    <input {if $a_type.Key|in_array:$shcAccountTypes}checked="checked"{/if} style="margin-bottom: 0px;" type="checkbox" id="account_type_{$a_type.ID}" value="{$a_type.Key}" name="config[shc_account_types][]" /> <label for="account_type_{$a_type.ID}">{$a_type.name}</label>
                                                </div>
                                            </td>
                                            
                                        {if $smarty.foreach.ac_type.iteration%1 == 0 && !$smarty.foreach.ac_type.last}
                                        </tr>
                                        <tr>
                                        {/if}
                                        
                                        {/foreach}
                                        </tr>
                                        </table>
                                    </td>
                                    <td>
                                        {assign var='shc_account_types_help' value='config+des+shc_account_types'}
                                        <span class="field_description">{$lang[$shc_account_types_help]}</span>
                                    </td>
                                </tr>
                                </table>

                                <div class="grey_area" style="margin: 8px 0 0;">
                                    <span onclick="$('#accounts_tab_area input').prop('checked', true);" class="green_10">{$lang.check_all}</span>
                                    <span class="divider"> | </span>
                                    <span onclick="$('#accounts_tab_area input').prop('checked', false);" class="green_10">{$lang.uncheck_all}</span>
                                </div>
                            </div>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <td class="name">{$lang.shc_payment_methods}</td>
                    <td class="field">
                        <fieldset class="light">
                            <legend id="legend_payment_gateways_tab_area" class="up" onclick="fieldset_action('payment_gateways_tab_area');">{$lang.payment_gateway}</legend>
                            <div id="payment_gateways_tab_area" style="padding: 0 10px 10px 10px;">
                                <table>
                                <tr>
                                    <td>
                                        <input type="hidden" name="config[shc_payment_gateways]" value="" />
                                        {assign var='shcPaymentGateways' value=","|explode:$config.shc_payment_gateways}
                                        <table>
                                        <tr>
                                        {foreach from=$payment_gateways item='gateway' name='Fpg'}
                                            <td>
                                                <div style="padding: 2px 8px 2px 0;">
                                                    <label><input {if $gateway.Key|in_array:$shcPaymentGateways && (!$config.shc_commission_enable || $config.shc_method == 'single' || ($config.shc_commission_enable && $gateway.Parallel))}checked="checked"{/if} {if ($config.shc_commission_enable && !$gateway.Parallel) || ($config.shc_escrow && !$gateway.escrow)}disabled="disabled="{/if} style="margin-bottom: 0px;" type="checkbox" value="{$gateway.Key}" name="config[shc_payment_gateways][]" class="{if $gateway.Parallel}parallel{/if}{if $gateway.escrow} escrow{/if}" /> {$gateway.name}</label>
                                                </div>
                                            </td>
                                            
                                        {if $smarty.foreach.Fpg.iteration%1 == 0 && !$smarty.foreach.Fpg.last}
                                        </tr>
                                        <tr>
                                        {/if}
                                        
                                        {/foreach}
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                </table>

                                <div class="grey_area" style="margin: 8px 0 0;">
                                    <span onclick="$('#payment_gateways_tab_area input').prop('checked', true);" class="green_10">{$lang.check_all}</span>
                                    <span class="divider"> | </span>
                                    <span onclick="$('#payment_gateways_tab_area input').prop('checked', false);" class="green_10">{$lang.uncheck_all}</span>
                                </div>
                            </div>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_allow_cash}</td>
                    <td class="field">
                        <label><input {if $config.shc_allow_cash == '1'}checked="checked"{/if} type="radio" name="config[shc_allow_cash]" value="1" /> {$lang.yes}</label>
                        <label><input {if $config.shc_allow_cash == '0' || !$config.shc_allow_cash}checked="checked"{/if} type="radio" name="config[shc_allow_cash]" value="0" /> {$lang.no}</label>
                    </td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_escrow}</td>
                    <td class="field">
                        <label><input {if $config.shc_escrow == '1'}checked="checked"{/if} type="radio" name="config[shc_escrow]" value="1" /> {$lang.yes}</label>
                        <label><input {if $config.shc_escrow == '0' || !$config.shc_escrow}checked="checked"{/if} type="radio" name="config[shc_escrow]" value="0" /> {$lang.no}</label>
                        <span class="field_description">{$shcLang.shc_escrow_des}</span>
                    </td>
                </tr>
                <!-- commission settings -->
                <tr class="commission{if $config.shc_method == 'single'} hide{/if}">
                    <td class="divider first" colspan="3"><div class="inner">{$lang.shc_commission_settings}</div></td>
                </tr>
                <tr class="commission{if $config.shc_method == 'single'} hide{/if}">
                    <td class="name">{$shcLang.shc_commission_enable}</td>
                    <td class="field">
                        <label><input {if $config.shc_commission_enable == '1'}checked="checked"{/if} type="radio" name="config[shc_commission_enable]" value="1" /> {$lang.yes}</label>
                        <label><input {if $config.shc_commission_enable == '0' || !$config.shc_commission_enable}checked="checked"{/if} type="radio" name="config[shc_commission_enable]" value="0" /> {$lang.no}</label>
                    </td>
                </tr>
                <tr class="commission{if $config.shc_method == 'single'} hide{/if}">
                    <td class="name">{$shcLang.shc_commission_type}</td>
                    <td class="field">
                        <label><input {if $config.shc_commission_type == 'percent'}checked="checked"{/if} type="radio" name="config[shc_commission_type]" value="percent" /> {$lang.shc_commission_unit_percent}</label>
                        <label><input {if $config.shc_commission_type == 'fixed' || !$config.shc_commission_type}checked="checked"{/if} type="radio" name="config[shc_commission_type]" value="fixed" /> {$lang.shc_commission_unit_fixed}</label>
                        <span class="field_description">{$shcLang.shc_commission_type_des}</span>
                    </td>
                </tr>
                <tr class="commission{if $config.shc_method == 'single'} hide{/if}">
                    <td class="name">{$shcLang.shc_commission}</td>
                    <td class="field">
                        <input type="text" name="config[shc_commission]" value="{if $config.shc_commission}{$config.shc_commission}{else}0{/if}" />
                        <span class="field_description">{$shcLang.shc_commission_des}</span>
                    </td>
                </tr>
                <tr class="commission{if $config.shc_method == 'single'} hide{/if}">
                    <td class="name">{$shcLang.shc_commission_add}</td>
                    <td class="field">
                        <label><input {if $config.shc_commission_add == '1'}checked="checked"{/if} type="radio" name="config[shc_commission_add]" value="1" /> {$lang.yes}</label>
                        <label><input {if $config.shc_commission_add == '0' || !$config.shc_commission_type}checked="checked"{/if} type="radio" name="config[shc_commission_add]" value="0" /> {$lang.no}</label>
                        <span class="field_description">{$shcLang.shc_commission_add_des}</span>
                    </td>
                </tr>
                <!-- end /commission settings -->
                <tr>
                    <td class="divider first" colspan="3"><div class="inner">{$lang.shc_auction_settings}</div></td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_auto_rate}</td>
                    <td class="field">
                        <label><input {if $config.shc_auto_rate == '1'}checked="checked"{/if} type="radio" name="config[shc_auto_rate]" value="1" /> {$lang.enabled}</label>
                        <label><input {if $config.shc_auto_rate == '0' || !$config.shc_auto_rate}checked="checked"{/if} type="radio" name="config[shc_auto_rate]" value="0" /> {$lang.disabled}</label>
                        <span class="field_description">{$shcLang.shc_auto_rate_des}</span>
                    </td>
                </tr>
                <tr id="shc_auto_rate_period" class="{if $config.shc_auto_rate == '0'}hide{/if}">
                    <td class="name">{$shcLang.shc_auto_rate_period}</td>
                    <td class="field">
                        <input type="text" name="config[shc_auto_rate_period]" value="{if $config.shc_auto_rate_period}{$config.shc_auto_rate_period}{/if}" />
                        <span class="field_description">{$lang.shc_auto_rate_period_des}</span>
                    </td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_auction_cancel_bid_seller}</td>
                    <td class="field">
                        <label><input {if $config.shc_auction_cancel_bid_seller == '1'}checked="checked"{/if} type="radio" name="config[shc_auction_cancel_bid_seller]" value="1" /> {$lang.enabled}</label>
                        <label><input {if $config.shc_auction_cancel_bid_seller == '0' || !$config.shc_auction_cancel_bid_seller}checked="checked"{/if} type="radio" name="config[shc_auction_cancel_bid_seller]" value="0" /> {$lang.disabled}</label>
                    </td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_auction_cancel_bid_buyer}</td>
                    <td class="field">
                        <label><input {if $config.shc_auction_cancel_bid_buyer == '1' || !$config.shc_auction_cancel_bid_buyer}checked="checked"{/if} type="radio" name="config[shc_auction_cancel_bid_buyer]" value="1" /> {$lang.enabled}</label>
                        <label><input {if $config.shc_auction_cancel_bid_buyer == '0'}checked="checked"{/if} type="radio" name="config[shc_auction_cancel_bid_buyer]" value="0" /> {$lang.disabled}</label>
                    </td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_buy_now}</td>
                    <td class="field">
                        <label><input {if $config.shc_buy_now == '1' || !$config.shc_buy_now}checked="checked"{/if} type="radio" name="config[shc_buy_now]" value="1" /> {$lang.enabled}</label>
                        <label><input {if $config.shc_buy_now == '0'}checked="checked"{/if} type="radio" name="config[shc_buy_now]" value="0" /> {$lang.disabled}</label>
                    </td>
                </tr>
                <tr>
                    <td class="divider first" colspan="3"><div class="inner">{$lang.shc_shipping_settings}</div></td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_shipping_fields}</td>
                    <td class="field">
                        <label><input {if $config.shc_shipping_fields == '1'}checked="checked"{/if} type="radio" name="config[shc_shipping_fields]" value="1" /> {$lang.enabled}</label>
                        <label><input {if $config.shc_shipping_fields == '0' || !isset($config.shc_shipping_fields)}checked="checked"{/if} type="radio" name="config[shc_shipping_fields]" value="0" /> {$lang.disabled}</label>
                        <span class="field_description">{$shcLang.shc_shipping_fields_des}</span>
                    </td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_shipping_step}</td>
                    <td class="field">
                        <label><input {if $config.shc_shipping_step == '1'}checked="checked"{/if} type="radio" name="config[shc_shipping_step]" value="1" /> {$lang.enabled}</label>
                        <label><input {if $config.shc_shipping_step == '0' || !isset($config.shc_shipping_step)}checked="checked"{/if} type="radio" name="config[shc_shipping_step]" value="0" /> {$lang.disabled}</label>
                        <span class="field_description">{$shcLang.shc_shipping_step_des}</span>
                    </td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_shipping_price_fixed}</td>
                    <td class="field">
                        <label><input {if $config.shc_shipping_price_fixed == 'single' || !$config.shc_shipping_price_fixed}checked="checked"{/if} type="radio" name="config[shc_shipping_price_fixed]" value="single" /> {$lang.shc_fixed_price_single}</label>
                        <label><input {if $config.shc_shipping_price_fixed == 'multi'}checked="checked"{/if} type="radio" name="config[shc_shipping_price_fixed]" value="multi" /> {$lang.shc_fixed_price_multi}</label>
                    </td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_weight_unit}</td>
                    <td class="field">
                        <label><input {if $config.shc_weight_unit == 'kgs' || !$config.shc_weight_unit}checked="checked"{/if} type="radio" name="config[shc_weight_unit]" value="kgs" /> {$lang.shc_weight_kgs}</label>
                        <label><input {if $config.shc_weight_unit == 'lbs'}checked="checked"{/if} type="radio" name="config[shc_weight_unit]" value="lbs" /> {$lang.shc_weight_lbs}</label>
                    </td>
                </tr>
                <tr>
                    <td class="name">{$shcLang.shc_use_multifield}</td>
                    <td class="field">
                        <label><input {if $config.shc_use_multifield == '1'}checked="checked"{/if} type="radio" name="config[shc_use_multifield]" value="1" {if $config.shc_shipping_calc}disabled="disabled"{/if} /> {$lang.enabled}</label>
                        <label><input {if $config.shc_use_multifield == '0' || !isset($config.shc_use_multifield)}checked="checked"{/if} type="radio" name="config[shc_use_multifield]" {if $config.shc_shipping_calc}disabled="disabled"{/if} value="0" /> {$lang.disabled}</label>
                        <span class="field_description">{$shcLang.shc_use_multifield_des}</span>
                    </td>
                </tr>
            </table>
            <div id="shipper-address">
                <table class="form">
                    <tr>
                        <td class="divider first" colspan="3">
                            <div class="inner">{$lang.shc_shipper_address}</div>
                            <input type="hidden" name="config[shc_shipper_address]" value="" />
                        </td>
                    </tr>
                </table>
                {if $allowMultifield}
                    {include file=$smarty.const.RL_PLUGINS|cat:'multiField/admin/tplHeader.tpl'}
                {/if}

                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field.tpl' fields=$shcShippingfields}

                {if $allowMultifield}
                    {include file=$smarty.const.RL_PLUGINS|cat:'multiField/admin/tplFooter.tpl'}
                {/if}
            </div>

            <table class="form">
                <tr>
                    <td class="name no_divider"></td>
                    <td class="field">
                        <input type="hidden" name="form" value="submit" />
                        <input id="shc_button" type="submit" class="button lang_add" value="{$lang.save}" />
                    </td>
                </tr>
            </table>
        </div>
    </form>
{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

<script>

var shc_method = '{$config.shc_method}';
rlConfig['convertPrices'] = {if $smarty.get.convertPrices}true{else}false{/if};

{literal}
$(document).ready(function() {
    if (shc_method) {
        if (shc_method == 'single') {
            $('#shipper-address').removeClass('hide');
            handlePaymentGateways(false);
        } else {
            handlePaymentGateways(
                $('input[name="config[shc_commission_enable]"]:checked').val() == 1 ? true : false,
                $('input[name="config[shc_escrow]"]:checked').val() == 1 ? true : false
            );
            $('#shipper-address').addClass('hide');
        }
    }

    $('select[name="config[shc_method]"]').change(function() {
        checkSettingsFieldsByMethod($(this).val());

        if ($(this).val() == 'single') {
            $('tr.commission').hide();
            $('#shipper-address').removeClass('hide');
            handlePaymentGateways(false, $('input[name="config[shc_escrow]"]:checked').val() == 1 ? true : false);
        } else {
            $('tr.commission').show();
            $('#shipper-address').addClass('hide');
            handlePaymentGateways(
                $('input[name="config[shc_commission_enable]"]:checked').val() == 1 ? true : false,
                $('input[name="config[shc_escrow]"]:checked').val() == 1 ? true : false
            );
        }
    });

    checkSettingsFieldsByMethod(shc_method);

    $('input[name="config[shc_auto_rate]"]').change(function() {
        if($(this).is(':checked')) {
            if($(this).val() == '1') {
                $('#shc_auto_rate_period').show();
            } else {
                $('#shc_auto_rate_period').hide();
                $('input[name="config[shc_auto_rate_period]"]').val(0);
            }
        }
    });

    $('input[name="config[shc_commission_enable]"]').change(function() {
        if ($(this).val() == 1 && $(this).is(':checked')) {
            var commissionEnable = false;
            $('#payment_gateways_tab_area input[type="checkbox"]').each(function() {
                if ($(this).hasClass('parallel')) {
                    commissionEnable = true;
                    return;
                }
            });
            if (commissionEnable) {
                handlePaymentGateways(true);
            } else {
                $(this).prop('checked', false);
                printMessage('error', '{/literal}{$lang.shc_comission_enable_notice}{literal}');
            }
        } else {
            handlePaymentGateways(false);
        }
    });

    $('#fields_container').sortable({
        placeholder: 'ui-field-highlight',
        items: 'div.field_obj:not(.ui-state-disabled)',
        cursor: 'move',
        forcePlaceholderSize: true,
        helper: 'clone',
        opacity: 0.5
    }).disableSelection();

    $( "input[name^='config[shc_module']" ).each(function() {
        if ($(this).is(':checked')) {
            controlPriceFormatTabs($(this).attr('tab'), $(this).val());
        }
    });

    $( "input[name^='config[shc_module']" ).click(function() {
        controlPriceFormatTabs($(this).attr('tab'), $(this).val());
    });

    $('input[name="config[shc_method_currency_convert]"]').change(function() {
        if ($(this).val() == 'single' && $(this).is(':checked')) {
            rlConfirm(lang['shc_confirm_convert_method'], "acceptMethodCurrencyConvert", false, false, false, "cancelCurrencyConvert");
        }
    });

    $('input[name="config[shc_items_cart_duration]"]').change(function() {
        if ($(this).val() == 'period' && $(this).is(':checked')) {
            $('.items-cart-duration-period').removeClass('hide');
        } else if (!$('.items-cart-duration-period').hasClass('hide')) {
            $('.items-cart-duration-period').addClass('hide');
        }
    });
    $('input[name="config[shc_escrow]"]').change(function() {
        if ($(this).val() == 1 && $(this).is(':checked')) {
            var escrowEnable = false;
            $('#payment_gateways_tab_area input[type="checkbox"]').each(function() {
                if ($(this).hasClass('escrow')) {
                    escrowEnable = true;
                    return;
                }
            });
            if (escrowEnable) {
                handlePaymentGateways(false, true);
            } else {
                $(this).prop('checked', false);
                printMessage('error', '{/literal}{$lang.shc_escrow_enable_notice}{literal}');
            }
        } else {
            handlePaymentGateways(false, false);
        }
    });
});

$('#shc_position').change(function() {
    if ( $(this).val() == 'top' || $(this).val() == 'bottom') {
        $('#type_dom').slideUp();
    } else {
        $('#type_dom').slideDown();
    }
});

var checkSettingsFieldsByMethod = function(method) {
    $('#shc_settings table.form tr').each(function() {
        if($(this).hasClass('single') || $(this).hasClass('multi') )
        {
            if ($(this).hasClass(method)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        }
    });
}

var handlePaymentGateways = function(parallel, escrow) {
    $('#payment_gateways_tab_area input[type="checkbox"]').each(function() {
        if (!$(this).hasClass('parallel') && (parallel === true || escrow === true)) {
            $(this).prop('disabled', true);
            $(this).prop('checked', false);
        } else {
            $(this).prop('disabled', false);
        }
    });
}

var controlPriceFormatTabs = function(tab, status) {
    var el = $('#fields_container').find('div.tab-' + tab);

    if (el.length > 0) {
        if (status == 1) {
            el.removeClass('ui-state-disabled');
        } else {
            el.addClass('ui-state-disabled');
        }
    }
}

var cancelCurrencyConvert = function() {
    $('input[name="config[shc_method_currency_convert]"]').each(function() {
        if ($(this).is(':checked') && $(this).val() == 'single') {
            $(this).prop('checked', false);
        }
        if ($(this).val() == 'multi') {
            $(this).prop('checked', 'checked');
        }
    });
}

/**
 * Method fake
 */
var acceptMethodCurrencyConvert = function(){
    return;
}

// Convert exists prices
if (rlConfig['convertPrices']) {
    var convertMethod = 'shoppingCart.initConvertPrices';
}

if (convertMethod) {
    rlConfirm(lang['shc_convert_prices_prompt'], convertMethod);
}

{/literal}
</script>
