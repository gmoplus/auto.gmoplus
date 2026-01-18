<!-- Order Details -->

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'} 

    <fieldset class="light">
        <legend id="legend_search_settings" class="up" onclick="fieldset_action('search_settings');">{$lang.shc_order_details}</legend>
        <table class="list">
            <tr>
                <td class="name">{$lang.shc_order_key}:</td>
                <td class="value"><b>{$order_info.Order_key}</b></td>
            </tr>
            <tr>
                <td class="name">{$lang.shc_buyer}:</td>
                <td class="value"><a href="{$rlBase}index.php?controller=accounts&amp;action=view&amp;userid={$order_info.Buyer_ID}">{$order_info.bFull_name}</a></td>
            </tr>
            <tr>
                <td class="name">{$lang.shc_dealer}:</td>
                <td class="value"><a href="{$rlBase}index.php?controller=accounts&amp;action=view&amp;userid={$order_info.Dealer_ID}">{$order_info.dFull_name}</a></td>
            </tr>
            <tr>
                <td class="name">{$lang.date}:</td>
                <td class="value">{$order_info.Date|date_format:$smarty.const.RL_DATE_FORMAT}</td>
            </tr>
            <tr>
                <td class="name">{$lang.total}:</td>
                <td class="value">
                    <b>{if $config.system_currency_position == 'before'}{$config.system_currency}{/if} <span id="total">{$order_info.Total|number_format:2:'.':','}</span> {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}</b>
                </td>
            </tr>
            {if $config.shc_method == 'multi' && $config.shc_commission_enable}
            <tr>
                <td class="name">{$lang.shc_commission}:</td>
                <td class="value"><b>{if $config.system_currency_position == 'before'}{$config.system_currency}{/if} <span id="total">{$order_info.Commission_total|number_format:2:'.':','}</span> {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}</b></td>
            </tr>
            {/if}
            {if $order_info.Cash}
            <tr>
                <td class="name">{$lang.shc_payment_type}:</td>
                <td class="value">{$lang.shc_payment_cash}</td>
            </tr>
            {/if}
            <tr>
                <td class="name">{$lang.status}:</td>
                <td class="value order-status">
                    {$lang[$order_info.Status]}
                    {if $order_info.Cash && $order_info.Status == 'pending'}
                        <a href="javascript://" class="button low make-paid">{$lang.shc_make_paid}</a>
                    {/if}
                </td>
            </tr>
        </table>
    </fieldset>

{if $order_info.Escrow_status == 'pending'}
    {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/admin/view/order_confirm.tpl' order_info=$order_info}
{/if}

{include file=$pluginPath|cat:'shipping_info.tpl' order_info=$order_info}

    {if $order_info.txn_info && !$order_info.Cash}
    <fieldset class="light">
        <legend id="legend_payment_details" class="up" onclick="fieldset_action('payment_details');">{$lang.transaction_info}</legend>
            <table class="list">
            <tr>
                <td class="name">{$lang.txn_id}:</td>
                <td class="value"><b>{$order_info.txn_info.Txn_ID}</b></td>
            </tr>
            <tr>
                <td class="name">{$lang.payment_gateway}:</td>
                <td class="value"><b>{$order_info.txn_info.Gateway}</b></td>
            </tr>
            <tr>
                <td class="name">{$lang.date}:</td>
                <td class="value"><b>{$order_info.txn_info.Date|date_format:$smarty.const.RL_DATE_FORMAT}</b></td>
            </tr>
        </table>
    </fieldset>
    {/if}

    {if $order_info.items}
        <fieldset class="light">
            <legend id="legend_items" class="up" onclick="fieldset_action('items');">{$lang.shc_order_items}</legend>
            <div id="items_list">
                <table class="table">
                    <tr class="header"> 
                        <td colspan="3">{$lang.item}</td>
                        <td class="divider"></td>
                        <td align="center" width="100">{$lang.price}</td>
                        <td class="divider"></td>
                        <td align="center" width="100">{$lang.shc_quantity}</td>
                        <td class="divider"></td>
                        <td align="center" width="120">{$lang.total}</td>
                    </tr>
                    {foreach from=$order_info.items item='item' name='orderItemF'}
                    <tr class="body" id="item_{$item.ID}">
                        <td class="photo" valign="top" align="center" width="80">
                            <a href="{$item.listing_link}"  target="_blank"> 
                                <img alt="{$item.title}" style="width: 70px;" src="data:image/png;base64, {$item.Image}" />
                            </a>
                        </td>
                        <td class="divider"></td>
                        <td class="text-overflow">
                            <a href="{$item.listing_link}" target="_blank">{$item.Item}</a>
                            {if (!$item.Digital && !$item.Digital_product) || !$config.shc_digital_product}
                                <div class="value">
                                    {$lang.shc_shipping_method}: {if $item.shipping_item_options.title}{$item.shipping_item_options.title}{else}{$item.shipping_item_options.service}{/if}
                                </div>
                                <div class="value">
                                    {$lang.shc_shipping_price}: {if $item.shipping_item_options.total > 0}{if $config.system_currency_position == 'before'}{$config.system_currency}{/if}{$item.shipping_item_options.total|number_format:2:'.':','}{if $config.system_currency_position == 'after'}{$config.system_currency}{/if}{else}{$lang.free}{/if}
                                </div>
                            {/if}
                            {if $item.shipping_item_options.tracking_number}
                                <div class="value">{$lang.shc_tracking_number}: {$item.shipping_item_options.tracking_number}</div>
                            {/if}
                            {if $order_info.Dealer_ID == $account_info.ID && ($item.shipping_item_options.method == 'fedex' || $item.shipping_item_options.method == 'UPS' || $item.shipping_item_options.method == 'USPS')}
                                <a href="javascript:void(0);" data-item-id="{$item.ID}" class="button add-tracking-number">{$lang.shc_add_tracking_number}</a>
                            {/if}
                            {if $config.shc_digital_product && $item.Digital && $item.Digital_product}
                                <div>
                                    <a href="javascript://" class="{if $order_info.Status == 'unpaid'}download-unpaid{else}download{/if}" data-item="{$item.ID}">{$lang.download}</a>
                                </div>
                            {/if}
                        </td>
                        <td class="divider"></td>
                        <td style="white-space: nowrap;" align="center">{if $config.system_currency_position == 'before'}{$config.system_currency}{/if} {$item.Price|number_format:2:'.':','} {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}</td>
                        <td class="divider"></td>
                        <td align="center">
                            {$item.Quantity}
                        </td>
                        <td class="divider"></td>
                        <td style="white-space: nowrap;" align="center">{if $config.system_currency_position == 'before'}{$config.system_currency}{/if} <span id="price_{$item.ID}">{$item.total|number_format:2:'.':','}</span> {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}</td>
                    </tr>
                    {/foreach}

                    <!-- Shipping -->
                    <tr>
                        <td style="text-align: right" colspan="8">
                            <b>{$lang.shc_shipping_price}</b>
                        </td>   
                        <td style="text-align: center"> 
                            <div><b>{if $config.system_currency_position == 'before'}{$config.system_currency}{/if} {$order_info.Shipping_price|number_format:2:'.':','} {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}</b></div>
                        </td>
                    </tr>

                    <!-- Total -->
                    <tr>
                        <td style="text-align: right" colspan="8">
                            <b>{$lang.total}</b>
                        </td>   
                        <td style="text-align: center"> 
                            <div><b>{if $config.system_currency_position == 'before'}{$config.system_currency}{/if} <span id="total">{$order_info.Total|number_format:2:'.':','}</span> {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}</b></div>
                        </td>
                    </tr>
                </table>
            </div>
        </fieldset>
    {/if}

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'} 

<script class="fl-js-dynamic">
    var shcOrderID = '{$order_info.ID}';
    var shcSellerID = '{$order_info.Dealer_ID}';
    {literal}
    $(document).ready(function(){
        $('.download').click(function() {
            shoppingCart.download($(this).data('item'));
        });
        $('.download-unpaid').click(function () {
            printMessage('error', '{/literal}{$lang.shc_order_not_paid}{literal}');
        });
        $('.make-paid').click(function() {
            rlConfirm(lang['shc_do_you_want_make_paid'], "makePaid", "", "load");
        });
    });
    var makePaid = function() {
        flynax.sendAjaxRequest('shoppingCartMakePaid', {orderID: shcOrderID, accountID: shcSellerID}, function(response){
            if (response.status == 'OK') {
                $('.order-status').html(response.status_value);
                printMessage('notice', response.message);
            } else {
                printMessage('error', response.message);
            }
        });
    }
{/literal}
</script>
<!-- end Order Details -->
