<!-- my purchases | shopping cart -->

<div class="content-padding">
    {if !empty($itemID)}
        {if $step == 'checkout'}
            <form id="form-checkout" method="post" action="{pageUrl page='shc_purchases' add_url='step=checkout' vars='item='|cat:$order_info.ID}">
                {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/checkout.tpl'}

                <input type="hidden" name="step" value="checkout" />
                <span class="form-buttons" style="padding-top: 0;">
                    <input type="submit" value="{$lang.checkout}" />
                    &nbsp;&nbsp;<a href="{pageUrl page='shc_purchases'}">{$lang.cancel}</a>
                </span>
            </form>
        {else}
            {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/order_details.tpl' orderInfo=$orderInfo}
        {/if}
    {else}
        {if $orders}
            <div class="list-table">
                <div class="header">
                    <div class="center" style="width: 40px;">#</div>
                    <div>{$lang.item}</div>
                    <div style="width: 120px;">{$lang.total}</div>
                    <div style="width: 130px;">{$lang.shc_order_key}</div>
                    <div style="width: 120px;">{$lang.date}</div>
                    <div style="width: 120px;">{$lang.shc_shipping_status}</div>
                    <div style="width: 100px;">{$lang.status}</div>
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
                        <div data-caption="{$lang.total}">
                            <span class="price-cell shc_price">{$item.Total}</span>
                        </div>
                        <div data-caption="{$lang.shc_order_key}">{$item.Order_key}</div>
                        <div data-caption="{$lang.date}">{$item.Date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
                        <div data-caption="{$lang.shc_shipping_status}">
                            {assign var='shippingStatus' value='shc_'|cat:$item.Shipping_status}
                            {if $item.Shipping_status == 'pending'}
                                {assign var='shippingStatus' value=$item.Shipping_status}
                            {/if}
                            <span>{$lang[$shippingStatus]}</span>
                        </div>
                        <div data-caption="{$lang.status}">
                            <span class="item_{$item.Status}">{$lang[$item.Status]}</span>
                            {if $item.Cash}<small class="cash">{$lang.shc_payment_cash}</small>{/if}
                            {if $config.shc_escrow}<small class="item_paid escrow-status {$item.Escrow_status}" title="{$lang.shc_escrow_item}">{$item.Escrow_status_name}</small>{/if}
                            {if $item.Bank_transfer && $item.Status == 'unpaid'}
                                <a href="{pageUrl page='payment_history'}"><small class="cash">{$lang.shc_bank_transfer}</small></a>
                            {/if}
                        </div>
                        <div data-caption="{$lang.actions}">
                            {if $item.Status == 'unpaid' && !$item.Cash}
                                <a href="{pageUrl page='shc_purchases' add_url='step=checkout'}{if $config.mod_rewrite}?{else}&{/if}item={$item.ID}">
                                    {$lang.checkout}
                                </a>
                            {/if}
                            <a class="d-block" title="{$lang.view_details}" href="{pageUrl page='shc_purchases'}{if $config.mod_rewrite}?{else}&{/if}item={$item.ID}">
                                {$lang.view_details}
                            </a>
                        </div>
                    </div>
                {/foreach}
            </div>

            {paging calc=$pInfo.calc total=$orders|@count current=$pInfo.current per_page=$config.shc_orders_per_page}
        {else}
            <div class="text-notice">{$lang.shc_no_purchases}</div>
        {/if}
    {/if}
</div>
<!-- my purchases end | shopping cart -->
