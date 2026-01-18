<!-- checkout step -->

{if !$config.shc_shipping_step}
    {mapsAPI}
{/if}

<div id="checkout-step">
    {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/shipping_info.tpl'}

    {if !$config.shc_shipping_step && $single_seller}
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' name=$lang.shc_pickup_address}

        {if $cart.items.0.pickup_details.address}
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-2">
                    {foreach from=$cart.items.0.pickup_details.address item='address'}
                        <div class="table-cell">
                            <div class="name">{$address.name}</div>
                            <div class="value">{$address.value}</div>
                        </div>
                    {/foreach}
                    </div>
                </div>
                {if $cart.items.0.pickup_details.coordinates
                    && $cart.items.0.pickup_details.coordinates.lat != '0'
                    && $cart.items.0.pickup_details.coordinates.lng != '0'}
                <div class="col-md-6">
                    <div class="sch-map-interface w-100"></div>
                </div>

                <script class="fl-js-dynamic">
                {literal}

                $(function(){
                    flMap.init($('.sch-map-interface'), {
                        control: 'topleft',
                        zoom: 12,
                        addresses: [{
                            latLng: {/literal}'{$cart.items.0.pickup_details.coordinates.lat},{$cart.items.0.pickup_details.coordinates.lng}'{literal}
                        }]
                    });
                });

                {/literal}
                </script>
                {/if}
            </div>
        {/if}

        {if !$cart.items.0.pickup_details.address
            || ($cart.items.0.pickup_details.address && $cart.items.0.pickup_details.address|@count <= 2)
        }
            {assign var='contact_link' value='<a class="call-owner" data-listing-id="'|cat:$cart.items.0.ID|cat:'" href="javascript://">$1</a>'}
            <div class="text-notice mt-2">{$lang.shc_pickup_no_address_hint|regex_replace:'/\[(.*)\]/':$contact_link}</div>
        {/if}

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
    {/if}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' name=$lang.shc_cart_details}
    {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/items.tpl' shcItems=$cart.items preview=true}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

    <div class="d-flex">
        <div class="mb-4 mr-5 ml-auto shc-mobile-width-100">
            <div class="table-cell">
                <div class="name">{$lang.shc_shipping_price}</div>
                <div class="value">
                    <span class="value shc_price shc-total-price" id="order-shipping-price">{$order_info.Shipping_price}</span>
                </div>
            </div>
            <div class="table-cell">
                <div class="name">{$lang.total}</div>
                <div class="value">
                    <span class="value shc_price shc-total-price" id="order-total">{$order_info.Total}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- payment gateways -->
    {gateways}
    <!-- payment gateways end -->

    <script class="fl-js-dynamic">
    {literal}
    $(function() {
        $('input[name="gateway"][value="cash"]').click(function() {
            $('#custom-form').html('');
            $('#btn-checkout, #form-checkout input[type="submit"]').off('click');
        });

        // Show pickup details popup
        $('.show-pickup-details').click(function(){
            var item_id = $(this).data('item-id');

            if (!item_id || !pickup_data[item_id]) {
                return
            }

            var pd = pickup_data[item_id];
            var html = '<div class="w-100">';

            html += '<div>';
            for (var i in pd.address) {
                html += `<div class="table-cell small">
                    <div class="name">${pd.address[i].name}</div>
                    <div class="value">${pd.address[i].value}</div>
                </div>
                `;
            }
            html += '</div>';

            if (pd.coordinates) {
                html += '<div class="mt-3 sch-map-interface w-100" style="height: 300px;"></div>';
            }

            html += '</div>';

            (function(pd){
                $('body').popup({
                    click: false,
                    width: 500,
                    caption: lang.shc_pickup_address,
                    content: html,
                    onShow: function($interface){
                        if (pd.coordinates) {
                            flMap.init($interface.find('.sch-map-interface'), {
                                control: 'topleft',
                                zoom: 12,
                                addresses: [{
                                    latLng: pd.coordinates.lat +','+ pd.coordinates.lng
                                }]
                            });
                        }
                    }
                });
            })(pd);
        });
    })
    {/literal}
    </script>
</div>

<!-- checkout step end -->
