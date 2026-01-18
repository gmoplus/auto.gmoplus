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
                <span>{$orderInfo.bFull_name}</span>
            </div>
        </div>
    {else}
        <div class="table-cell">
            <div class="name"><div><span>{$lang.shc_dealer}</span></div></div>
            <div class="value">
                <span>{$orderInfo.dFull_name}</span>
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
    <div class="table-cell">
        <div class="name"><div><span>{$lang.status}</span></div></div>
        <div class="value"><span class="item_{$orderInfo.Status}">{$lang[$orderInfo.Status]}</span></div>
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

{include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/shipping_info.tpl' order_info=$orderInfo}

{if $orderInfo.cart.items}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='shc_items' name=$lang.shc_order_items}
    <table class="sTable" width="100%">
        <tr class="header">
            <td width="40"></td>
            <td class="divider"></td>
            <td width="60%">{$lang.item}</td>
            <td class="divider"></td>
            <td width="15%" align="center">{$lang.price}</td>
            <td class="divider"></td>
            <td width="10%" align="center">{$lang.shc_quantity}</td>
            <td class="divider"></td>
            <td width="15%" align="center">{$lang.total}</td>
        </tr>
        {foreach from=$orderInfo.cart.items item='item' name='orderItemF'}
        <tr class="body" id="item_{$item.ID}">
            <td align="center" class="value">
                {$smarty.foreach.orderItemF.iteration}
            </td>
            <td class="divider"></td>
            <td class="text-overflow value">
                {if $item.main_photo}
                    <div style="float: left;">
                        <a href="{$item.listing_link}" target="_blank"><img alt="{$item.Item}" style="width: 70px;margin-{$text_dir_rev}: 10px;" src="{$smarty.const.RL_FILES_URL}{$item.main_photo}" /></a>
                    </div>
                {/if}
                <div>
                    <div class="value"><a href="{$item.listing_link}" target="_blank">{$item.Item}</a></div>
                    <div class="value">{$lang.shc_shipping_method}: {if $item.shipping_item_options.title}{$item.shipping_item_options.title}{else}{$item.shipping_item_options.service}{/if}</div>
                    <div class="value">{$lang.shc_shipping_price}: {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}{$item.shipping_item_options.total|number_format:2:'.':','}{if $config.system_currency_position == 'after'}{$config.system_currency}{/if}</div>
                </div>
            </td>
            <td class="divider"></td>
            <td align="center" class="value">
                <span class="price-cell shc_price">{$item.Price}</span>
            </td>
            <td class="divider"></td>
            <td align="center" class="value">
                <span class="price-cell shc_price">{$item.Quantity}</span>
            </td>
            <td class="divider"></td>
            <td align="center" class="value">
                <span class="price-cell shc_price">{$item.total}</span>
            </td>
        </tr>
        {/foreach}
        <tr class="body">
            <td colspan="8" align="right" class="value">{$lang.shc_shipping_price}:</td>
            <td align="center" class="value">
                <span class="value shc_price" id="total_{$shcDealer}">{$orderInfo.Shipping_price}</span>
            </td>
        </tr>
        <tr class="body">
            <td colspan="8" align="right" class="value">{$lang.total}:</td>
            <td align="center" class="value">
                <span class="value shc_price" id="total_{$shcDealer}">{$orderInfo.Total}</span>
            </td>
        </tr>
    </table>

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
{/if}

<!-- end Order Details -->
