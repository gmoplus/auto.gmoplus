<!-- shoppingCart plugin -->

{if $listing_data.shc_mode == 'auction' || $listing_data.shc_mode == 'fixed'}
    {assign var='is_aution_active' value=false}
    {if $listing_data.shc.Status == 'active' && $listing_data.shc.time_left_value > 0 && $listing_data.shc_auction_status != 'closed' && $listing_data.shc_quantity > 0  && $config.shc_module_auction}
        {assign var='is_aution_active' value=true}
    {/if}
    <div class="shc-group mb-4">
        {if $listing_data.shc_mode == 'auction'}
            <div class="auction-details{if !$is_aution_active} closed{/if}{if !$isLogin} not-logged-in{/if} hborder mb-3">
                {if $is_aution_active}
                    <ul class="d-flex">
                        <li class="flex-fill">
                            <div class="name date mb-2">{$lang.shc_time_left}</div>
                            <div class="value" id="time-left">{$listing_data.shc.time_left}</div>
                        </li>

                        <li class="flex-fill">
                            <div class="name date mb-2">{if $listing_data.shc.total_bids > 0}{$lang.shc_current_bid}{else}{$lang.shc_starting_bid}{/if}</div>
                            <span class="value">
                                {if $config.system_currency_position == 'before'}{$listing_data.shc.currency}{/if}
                                <span id="current_price">{str2money string=$listing_data.shc.current_bid showCents=false}</span>
                                {if $config.system_currency_position == 'after'} {$listing_data.shc.currency}{/if}
                            </span>
                            <span class="bid-info">[<a href="javascript:void(0);" id="bid_history"><span id="total_bids">{$listing_data.shc.total_bids}</span> {$lang.shc_bids}</a>]</span>
                        </li>
                    </ul>

                    {if $isLogin}
                        <div class="mt-3 d-flex">
                            <input placeholder="{$listing_data.shc.shc_min_bid}" type="text" class="numeric flex-fill shrink-fix" name="rate_bid" id="rate_bid" />
                            <a class="ml-2 button flex-shrink-0" href="javascript:void(0);" id="shc_add_bid" data-phrase="{$lang.shc_add_bid}">{$lang.shc_add_bid}</a>
                        </div>
                    {else}
                        <div class="mt-2">{$shc_add_bid_not_login}</div>
                    {/if}
                {elseif $listing_data.shc_auction_status != 'closed' && $listing_data.shc_quantity <= 0}
                    {$lang.shc_auction_reserved}
                {elseif !$config.shc_module_auction}
                    {$lang.shc_auction_is_disabled}
                {else}
                    {$lang.shc_auction_closed}
                {/if}

                {if $winner_info}
                    <div class="table-cell mt-2">
                        <div class="name">{$lang.shc_winner}:</div>
                        <div class="value">{$winner_info.Full_name}</div>
                    </div>
                {/if}
            </div>

            <div class="d-flex mb-3">
                {if $is_aution_active && $listing_data.price && $config.shc_buy_now && $listing_data.shc.buy_now_allowed}
                    <a class="button flex-fill text-center" href="javascript:void(0);" id="shc_by_now_item">{$lang.shc_buy_now}</a>
                    {if $listing_data.shc_quantity > 0}
                        <a class="button add-to-cart flex-fill ml-2 text-center" data-item-id="{$listing_data.ID}" href="javascript:void(0);" id="shc-item-{$listing_data.ID}">{$lang.shc_add_to_cart}</a>
                    {/if}
                {/if}
            </div>
        {else}
            {if $listing_data.shc_available && $listing_data.shc_quantity > 0 && $config.shc_module}
                <a class="button w-100 add-to-cart text-center mb-3" data-item-id="{$listing_data.ID}" href="javascript:void(0);" id="shc-item-{$listing_data.ID}">{$lang.shc_add_to_cart}</a>
            {/if}
        {/if}

        <div class="listing-fields">
            {if $listing_data.shc_mode == 'fixed' && !$listing_data.Digital}
            <div class="table-cell clearfix">
                <div class="name">{$lang.shc_left_in_stock}</div>
                <div class="value">
                    {if $listing_data.shc_quantity > 0 && $listing_data.shc_available}
                        {$listing_data.shc_quantity}
                    {else}
                        <span class="red">{$lang.shc_not_available}</span>
                    {/if}
                </div>
            </div>
            {/if}
            {if $listing_data.Handling_time != '' && $listing_data.Handling_time != '-1'}
            <div class="table-cell clearfix">
                <div class="name">{$lang.shc_handling_time}</div>
                <div class="value">
                    {assign var='shc_lf_value' value='shc_handling_time_'|cat:$listing_data.Handling_time}
                    {$lang[$shc_lf_value]}
                </div>
            </div>
            {/if}
            {if $listing_data.Package_type != '' && $listing_data.Package_type != '-1'}
            <div class="table-cell clearfix">
                <div class="name">{$lang.shc_package_type}</div>
                <div class="value">
                    {assign var='shc_lf_value' value='shc_package_type_'|cat:$listing_data.Package_type}
                    {$lang[$shc_lf_value]}
                </div>
            </div>
            {/if}
            {if $listing_data.Weight}
            <div class="table-cell clearfix">
                <div class="name">{$lang.shc_weight}</div>
                <div class="value">
                    {$listing_data.Weight} {$config.shc_weight_unit|replace:'s':''}
                </div>
            </div>
            {/if}
            {if !empty($listing_data.Dimensions.length) && !empty($listing_data.Dimensions.width) && !empty($listing_data.Dimensions.height)}
            <div class="table-cell clearfix">
                <div class="name">{$lang.shc_dimensions}</div>
                <div class="value">
                    {$listing_data.Dimensions.length}&nbsp;<i>x</i>&nbsp;{$listing_data.Dimensions.width}&nbsp;<i>x</i>&nbsp;{$listing_data.Dimensions.height} {$config.shc_length_type}
                </div>
            </div>
            {/if}
            {if $listing_data.Shipping_price_type == 'fixed'}
            <div class="table-cell clearfix">
                <div class="name">{$lang.shc_shipping_price}</div>
                <div class="value">
                    {if $config.shc_shipping_price_fixed == 'single' || (!$listing_data.Shipping_fixed_prices && $listing_data.Shipping_price)}
                        <span class="shc_price">
                            {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
                            {str2money string=$listing_data.Shipping_price}
                            {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
                        </span>
                    {elseif $listing_data.Shipping_fixed_prices}
                        {foreach from=$listing_data.Shipping_fixed_prices item='fixedPrice'}
                        <div>
                            {$fixedPrice.name} - 
                            <span class="shc_price">
                                {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
                                {str2money string=$fixedPrice.price}
                                {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
                            </span>
                        </div>
                        {/foreach}
                    {/if}
                    {if $listing_data.Shipping_discount > 0}
                    <div>
                        <small>- {$listing_data.Shipping_discount}% {$lang.shc_after_more}</small>
                    </div>
                    {/if}
                </div>
            </div>
            {/if}
            {if $listing_data.Shipping_price_type == 'free' && !$listing_data.Digital}
            <div class="table-cell clearfix">
                <div class="name">{$lang.shc_shipping_price}</div>
                <div class="value">
                    {$lang.free}
                </div>
            </div>
            {/if}
            {if $listing_data.Shipping_method_fixed|@count > 0 && ($listing_data.Shipping_price_type == 'fixed' || $listing_data.Shipping_price_type == 'free') && !$listing_data.Digital}
            <div class="table-cell clearfix">
                <div class="name">{$lang.shc_shipping_method}</div>
                <div class="value">
                    <ul class="shc-payment-gateways d-flex">
                    {foreach from=$listing_data.Shipping_method_fixed item='shippingMethod'}
                        {assign var='shippingMethodVal' value='shc_'|cat:$shippingMethod}
                        <li>{$lang[$shippingMethodVal]}</li>
                    {/foreach}
                    </ul>
                </div>
            </div>
            {/if}
            {if $payment_gateways && !$config.shc_allow_cash}
            <div class="table-cell clearfix">
                <div class="name">{$lang.payment_gateways}</div>
                <div class="value">
                    <ul class="shc-payment-gateways d-flex flex-wrap">
                        {if $payment_gateways}
                            {foreach from=$payment_gateways item='gateway' name='gateways'}
                            <li class="mr-2 mb-2">{strip}
                                {if $gateway.Key == 'paypal' || $gateway.Key == '2co'}  
                                    <img alt="" src="{$smarty.const.RL_LIBS_URL}payment/{$gateway.Key}/{$gateway.Key}.png" />
                                {else}
                                    {rlHook name='shoppingCartListingDetailsGatewaysTpl'}
                                    <img alt="" src="{$smarty.const.RL_PLUGINS_URL}{$gateway.Key}/static/{$gateway.Key}.png" />
                                {/if}
                            {/strip}</li>
                            {/foreach}
                        {else}
                            <li><div class="notice">{$lang.shc_not_available_payment_gateways}</div></li>
                        {/if}
                    </ul>
                    {if $config.shc_escrow}
                        <div class="escrow-item">
                            <img alt="" src="{$smarty.const.RL_PLUGINS_URL}shoppingCart/static/escrow.svg" />
                            <span class="green">{$lang.shc_escrow_item}</span>
                        </div>
                    {/if}
                </div>
            </div>
            {/if}
            {if $config.shc_allow_cash && (!$payment_gateways || ($payment_gateways && $dealer_options.allow_cash))}
                <div class="table-cell clearfix">
                    <div class="name">{$lang.shc_payment_cash}</div>
                    <div class="value">
                        {$lang.yes}
                    </div>
                </div>
            {/if}
            {if $listing_data.has_shipping}
            <div class="table-cell clearfix">
                <div class="name">{$lang.shc_available}</div>
                <div class="value">
                    <ul class="checkboxes clearfix">
                        {foreach from=$listing_data.Shipping_options item='method' key='key'}
                            {if $method.enable}
                                {assign var='shc_shm_name' value='shipping_methods+name+'|cat:$key}
                                <li class="active" title="{$lang[$shc_shm_name]}"><img alt="" src="{$rlTplBase}img/blank.gif" />{$lang[$shc_shm_name]}</li>
                            {/if}
                        {/foreach}
                    </ul>
                </div>
            </div>
            {/if}
        </div>
    </div>

    {if $listing_data.shc_mode == 'auction' && $config.shc_module_auction}
    {addCSS file=$rlTplBase|cat:'components/popup/popup.css'}
    {addJS file=$rlTplBase|cat:'components/popup/_popup.js'}

    <script class="fl-js-dynamic">
        lang['shc_empty_bid_value'] = "{$lang.shc_empty_bid_value}";
        lang['shc_do_you_want_to_add_bid'] = "{$lang.shc_do_you_want_to_add_bid}";
        lang['shc_add_bid'] = "{$lang.shc_add_bid}";
        var shcTimeZone = '{if $config.timezone}{$config.timezone}{else}America/New_York{/if}';
        var shcListingID = {$listing_data.ID};
        {literal}

        $(document).ready(function(){
            {/literal}shoppingCart.updateLeftTime('{$listing_data.shc_start_time}', {$listing_data.shc_days}, '{$lang.shc_auction_time_attr}');{literal}

            $('#shc_add_bid').each(function(){
                $(this).click(function(){
                    shcAddBid($(this));
                });
            });

            $('#bid_history').click(function() {
                $('#tab_shoppingCart a').trigger('click');
                flynax.slideTo('.bid-history-header');
            });

            $('#shc_by_now_item').click(function() {
                shoppingCartBasic.addItem(
                    {/literal}{$listing_data.ID}{literal}, 
                    true, 
                    '{/literal}{pageUrl page='shc_my_shopping_cart' vars='item='|cat:$listing_data.ID}{literal}'
                );
            });
            $('#price_buy_now').html($('#df_field_price').html());
        });

        var shcAddBid = function($button) {
            if ($button.hasClass('disabled')) {
                return;
            }

            var $rateBid = $('#rate_bid');

            if (!$rateBid.val()) {
                printMessage('error', lang['shc_empty_bid_value']);
                $rateBid.focus();
                return;
            }

            var data = {
                click: false,
                caption: lang['shc_do_you_want_to_add_bid'],
                width: 'auto',
                height: 'auto'
            };
            if (isLogin) {
                data.navigation = {
                    okButton: {
                        text: lang['shc_add_bid'],
                        class: 'low',
                        onClick: function($popup){
                            shoppingCart.addBid(shcListingID, $rateBid.val())
                            $popup.close();
                        }
                    },
                    cancelButton: {
                        text: lang.cancel,
                        class: 'low cancel',
                    }
                };
            } else {
                data.content = '#login_modal_source';
            }
            $('body').popup(data);
        }

        {/literal}
    </script>
    {/if}
{/if}

<!-- end shoppingCart plugin -->
