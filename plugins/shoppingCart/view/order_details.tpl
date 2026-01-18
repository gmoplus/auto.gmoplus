<!-- Order Details -->

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='shc_order_details' name=$lang.shc_order_details}

    <div class="table-cell">
        <div class="name"><div><span>{$lang.shc_order_key}</span></div></div>
        <div class="value">{$orderInfo.Order_key}</div>
    </div>

    {if $account_info.ID == $orderInfo.Dealer_ID}
        <div class="table-cell">
            <div class="name"><div><span>{$lang.shc_buyer}</span></div></div>
            <div class="value">
                {if $orderInfo.bOwn_address}
                    <a href="{$rlBase}{$orderInfo.bOwn_address}/">{$orderInfo.bFull_name}</a>
                {else}
                    <span>{$orderInfo.bFull_name}</span>
                {/if}
            </div>
        </div>
    {else}
        <div class="table-cell">
            <div class="name"><div><span>{$lang.shc_dealer}</span></div></div>
            <div class="value">
                {if $orderInfo.dOwn_address}
                    <a href="{$rlBase}{$orderInfo.dOwn_address}/">{$orderInfo.dFull_name}</a>
                {else}
                    <span>{$orderInfo.dFull_name}</span>
                {/if}
            </div>
        </div>
    {/if}
    {if $orderInfo.Buyer_ID == $account_info.ID && $orderInfo.Tracking_number}
        <div class="table-cell">
            <div class="name"><div><span>{$lang.shc_tracking_number}</span></div></div>
            <div class="value">
                <span>{$orderInfo.Tracking_number}</span>
            </div>
        </div>
    {/if}
    {if $orderInfo.Dealer_ID == $account_info.ID}
        <div class="table-cell">
            <div class="name"><div><span>{$lang.shc_tracking_number}</span></div></div>
            <div class="value tracking-number">
                {if $orderInfo.Tracking_number}
                    <span>{$orderInfo.Tracking_number}</span>
                {else}
                    <a href="javascript:void(0);" data-item-id="{$orderInfo.ID}" class="button low add-tracking-number">{$lang.shc_add_tracking_number}</a>
                {/if}
            </div>
        </div>
    {/if}
    <div class="table-cell">
        <div class="name"><div><span>{$lang.date}</span></div></div>
        <div class="value">{$orderInfo.Date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
    </div>
    <div class="table-cell">
        <div class="name"><div><span>{$lang.total}</span></div></div>
        <div class="value">
            <span class="price-cell shc_price">{$orderInfo.Total}</span>
        </div>
    </div>
    {if $config.shc_method == 'multi' && $config.shc_commission_enable && $orderInfo.Dealer_ID == $account_info.ID}
        <div class="table-cell">
            <div class="name"><div><span>{$lang.shc_commission}</span></div></div>
            <div class="value">
                <span class="price-cell shc_price">{$orderInfo.Commission}</span>
            </div>
        </div>
    {/if}
    {if $orderInfo.Cash}
        <div class="table-cell">
            <div class="name"><div><span>{$lang.shc_payment_type}</span></div></div>
            <div class="value"><span>{$lang.shc_payment_cash}</span></div>
        </div>
    {/if}
    <div class="table-cell">
        <div class="name"><div><span>{$lang.status}</span></div></div>
        <div class="value">
            <span class="payment-status item_{$orderInfo.Status}">{$lang[$orderInfo.Status]}</span>
            {if $orderInfo.Dealer_ID == $account_info.ID && $orderInfo.Cash && $orderInfo.Status == 'pending'}
            <a href="javascript://" class="button low make-paid">{$lang.shc_make_paid}</a>
            {/if}
            {if $orderInfo.Status == 'unpaid' && !$orderInfo.Cash}
                <a href="{pageUrl page='shc_purchases' add_url='step=checkout'}{if $config.mod_rewrite}?{else}&{/if}item={$orderInfo.ID}">
                    {$lang.checkout}
                </a>
            {/if}
        </div>
    </div>
    {if !empty($orderInfo.Txn_ID)}
        <div class="table-cell">
            <div class="name"><div><span>{$lang.txn_id}</span></div></div>
            <div class="value">{$orderInfo.Txn_ID}</div>
        </div>
        <div class="table-cell">
            <div class="name"><div><span>{$lang.gateway}</span></div></div>
            <div class="value">{$orderInfo.Gateway}</div>
        </div>
        <div class="table-cell">
            <div class="name"><div><span>{$lang.date}</span></div></div>
            <div class="value">{$orderInfo.Pay_date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
        </div>
    {/if}
    {if $orderInfo.Comment}
    <div class="table-cell">
        <div class="name"><div><span>{$lang.shc_comment}</span></div></div>
        <div class="value"><i>{$orderInfo.Comment}</i></div>
    </div>
    {/if}
{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

{if $orderInfo.Escrow_status == 'pending' && $orderInfo.Buyer_ID == $account_info.ID}
    {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/order_confirm.tpl' order_info=$orderInfo}
{else}
    {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/order_escrow.tpl' orderInfo=$orderInfo}
{/if}

{include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/shipping_info.tpl' order_info=$orderInfo}

{if $orderInfo.cart.items}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='shc_items' name=$lang.shc_order_items}

    <div class="list-table row-align-middle">
        <div class="header">
            <div class="center" style="width: 40px;">#</div>
            <div>{$lang.item}</div>
            {if $config.shc_digital_product}
                <div style="width: 90px;"></div>
            {/if}
            <div class="center" style="width: 100px;">{$lang.price}</div>
            <div class="center" style="width: 110px;">{$lang.shc_quantity}</div>
            <div class="center" style="width: 110px;">{$lang.total}</div>
        </div>

        {foreach from=$orderInfo.cart.items item='item' name='orderItemF'}
            {if !$item.shc_available}{continue}{/if}

            <div class="row">
                <div class="center iteration no-flex text-center">{$smarty.foreach.orderItemF.iteration}</div>
                <div data-caption="{$lang.item}" class="d-flex">
                    {if $item.main_photo}
                        <div class="image mr-2">
                            <a href="{$item.listing_link}" target="_blank">
                                <img alt="{$item.Item}" class="shc-item-picture" src="{$smarty.const.RL_FILES_URL}{$item.main_photo}" />
                            </a>
                        </div>
                    {/if}
                    <div>
                        <a href="{$item.listing_link}" target="_blank">{$item.Item}</a>
                        {if !$item.Digital && !$item.Digital_product}
                        <div>
                            {$lang.shc_shipping_method}:
                            <span class="font-weight-bold">
                                {if $item.shipping_item_options.title}
                                    {$item.shipping_item_options.title}
                                {else}
                                    {$item.shipping_item_options.service}
                                {/if}
                            </span>
                        </div>

                        <div>
                            {$lang.shc_shipping_price}:
                            <span class="shc_price">
                                {if $item.shipping_item_options.total > 0}
                                    {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
                                    {$item.shipping_item_options.total|number_format:2:'.':','}
                                    {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
                                {else}
                                    {$lang.free}
                                {/if}
                            </span>
                        </div>
                        {/if}
                    </div>
                </div>
                {if $config.shc_digital_product}
                    <div data-caption="{$lang.shc_download}">
                        {if $item.Digital && $item.Digital_product}
                            <a href="javascript://" class="{if $orderInfo.Status == 'unpaid'}download-unpaid{else}download{/if}" data-item="{$item.ID}">
                                <svg width="24" height="24" viewBox="0 0 24 24" class="icon {if $orderInfo.Status == 'unpaid'}download{else}grid{/if}-icon-fill align-middle"><use xlink:href="#download_product"></use></svg>
                            </a>
                        {/if}
                    </div>
                {/if}
                <div data-caption="{$lang.price}" class="center">
                    <span class="price-cell shc_price">{$item.Price}</span>
                </div>
                <div data-caption="{$lang.shc_quantity}" class="text-left text-sm-center"><span class="font-weight-bold">{$item.Quantity}</span></div>
                <div data-caption="{$lang.total}" class="center">
                    <span class="price-cell shc_price">{$item.total}</span>
                </div>
            </div>
        {/foreach}
    </div>

    <div class="d-flex total-info">
        <div class="mb-4 mr-5 ml-auto">
            <div class="table-cell">
                <div class="name">{$lang.shc_shipping_price}</div>
                <div class="value"><span class="value shc_price">{$orderInfo.Shipping_price}</span></div>
            </div>
            <div class="table-cell">
                <div class="name">{$lang.total}</div>
                <div class="value"><span class="value shc_price" id="total_{$shcDealer}">{$orderInfo.Total}</span></div>
            </div>
        </div>
    </div>

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
{/if}
<script class="fl-js-dynamic">
    var shcOrderID = '{$orderInfo.ID}';
    {literal}
    $(document).ready(function(){
        $('.download').click(function() {
            shoppingCart.download($(this).data('item'));
        });
        $('.download-unpaid').click(function () {
            printMessage('error', '{/literal}{$lang.shc_order_not_paid}{literal}');
        });
        $('.make-paid').click(function() {
            $('.make-paid').flModal({
                caption: '',
                content: '{/literal}{$lang.shc_do_you_want_make_paid}{literal}',
                prompt: 'makePaid()',
                width: 'auto',
                height: 'auto',
                click: false
            });
        });
    });

    var makePaid = function() {
        var data = {
            mode: 'shoppingCartMakePaid',
            item: shcOrderID
        };
        flUtil.ajax(data, function(response) {
            if (response.status == 'OK') {
                $('.make-paid').remove();
                $('.payment-status').removeClass('item_pending').addClass('item_paid');
                $('.payment-status').text(response.status_value);
                printMessage('notice', response.message);
            } else {
                printMessage('error', response.message);
            }
        });
    }
{/literal}
</script>

<!-- end Order Details -->
