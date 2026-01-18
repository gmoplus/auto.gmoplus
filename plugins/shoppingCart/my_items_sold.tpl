<!-- my purchases | shopping cart -->

<div class="content-padding">
    {if !empty($itemID)}
        {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/order_details.tpl' orderInfo=$orderInfo}
    {else}
        {if $orders}
            <div class="list-table">
                <div class="header">
                    <div class="center" style="width: 40px;">#</div>
                    <div>{$lang.item}</div>
                    <div style="width: 120px;">{$lang.total}</div>
                    {if $config.shc_method == 'multi' && $config.shc_commission_enable}
                        <div style="width: 120px;">{$lang.shc_commission}</div>
                    {/if}
                    <div style="width: 130px;">{$lang.shc_order_key}</div>
                    <div style="width: 120px;">{$lang.date}</div>
                    <div style="width: 120px;">{$lang.shc_payment_status}</div>
                    <div style="width: 140px;">{$lang.shc_shipping_status}</div>
                    <div style="width: 100px;">{$lang.actions}</div>
                </div>

                {foreach from=$orders item='item' name='orderF'}
                    {math assign='iteration' equation='(((current?current:1)-1)*per_page)+iter' iter=$smarty.foreach.orderF.iteration current=$pInfo.current per_page=$config.shc_orders_per_page}

                    <div class="row">
                        <div class="center iteration no-flex">{$iteration}</div>
                        <div data-caption="{$lang.item}">
                            <ul>
                            {foreach from=$item.items item='iVal'}
                                <li>{$iVal.Item}</li>
                            {/foreach}
                            </ul>
                        </div>
                        <div data-caption="{$lang.shc_total}">
                            <span class="price-cell shc_price">{$item.Total}</span>
                        </div>
                        {if $config.shc_method == 'multi' && $config.shc_commission_enable} 
                            <div data-caption="{$lang.shc_commission}">
                                <span class="price-cell shc_price">{$item.Commission}</span>
                            </div>
                        {/if}
                        <div data-caption="{$lang.shc_order_key}">{$item.Order_key}</div>
                        <div data-caption="{$lang.date}">{$item.Date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
                        <div data-caption="{$lang.shc_payment_status}">
                            <span class="item_{$item.Status}">{$lang[$item.Status]}</span>
                            {if $item.Cash}<small class="cash">{$lang.shc_payment_cash}</small>{/if}
                            {if $config.shc_escrow}<small class="item_paid escrow-status {$item.Escrow_status}" title="{$lang.shc_escrow_item}">{$item.Escrow_status_name}</small>{/if}
                        </div>
                        <div data-caption="{$lang.shc_shipping_status}">
                            <div></div>
                            <select id="shs_{$item.ID}" class="w70 shipping_status" {if $item.Shipping_status == 'delivered'}disabled="disabled"{/if}>
                                {foreach from=$shipping_statuses item='shs'}
                                    <option value="{$shs.Key}" {if $shs.Key == $item.Shipping_status}selected="selected"{/if}>{$shs.name}</option>  
                                {/foreach}
                            </select>
                        </div>
                        <div data-caption="{$lang.actions}">
                            <a title="{$lang.view_details}" href="{pageUrl page='shc_my_items_sold'}{if $config.mod_rewrite}?{else}&{/if}item={$item.ID}">
                                {$lang.view_details}
                            </a>
                        </div>
                    </div>
                {/foreach}
            </div>

            {paging calc=$pInfo.calc total=$orders|@count current=$pInfo.current per_page=$config.shc_orders_per_page}
        {else}
            <div class="text-notice">{$lang.shc_no_sold_items}</div>
        {/if}
    {/if}
</div>
<div id="tracking_number_form" class="hide">
    <input type="hidden" id="order_item_id" value="">
    <div id="shipping_city" class="submit-cell courier dhl ups">
        <div class="name">{$lang.shc_tracking_number}</div>
        <div class="field single-field">
            <input class="wauto" size="25" type="text" value="" id="order_tracking_number" />
        </div>
    </div>
</div>
<script class="fl-js-dynamic">
    var tmpItemID = 0;
    {literal}
    $(document).ready(function(){
        $('.add-tracking-number').click(function() {
            tmpItemID = $(this).attr('data-item-id');
            var el = '#tracking_number_form';

            flUtil.loadScript([
                rlConfig['tpl_base'] + 'components/popup/_popup.js',
            ], function(){
                $('#fs_shc_order_details').popup({
                    click: false,
                    scroll: true,
                    closeOnOutsideClick: false,
                    content: $(el).html(),
                    caption: '{/literal}{$lang.shc_add_tracking_number}{literal}',
                    navigation: {
                        okButton: {
                            text: lang['save'],
                            onClick: function(popup){
                                var order_tracking_number = popup.$interface.find('#order_tracking_number').val();
                                shoppingCart.saveTrackingNumber(tmpItemID, order_tracking_number);

                                popup.close();
                            }
                        },
                        cancelButton: {
                            text: lang['cancel'],
                            class: 'cancel'
                        }
                    }
                });
            });
        });

        $('select.shipping_status').change(function() {
            if($(this).val() != '') {
                shoppingCart.changeShippingStatus($(this).val(), $(this).attr('id').split('_')[1]);
            }
        });
    });
{/literal}
</script>
<!-- my purchases end | shopping cart -->
